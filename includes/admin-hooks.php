<?php
if (!defined('ABSPATH')) {
    exit;
}

function nbe_plugin_action_links($links)
{
    $settings = '<a href="' . get_admin_url(null, 'options-general.php?page=nbe') . '">Ajustes</a>';
    array_unshift($links, $settings);
    return $links;
}

function nbe_menu()
{
    add_options_page('Conector NBE', 'Conector NBE', 'manage_options', 'nbe', 'nbe_options_page');
    add_management_page('NBElectric Logs', 'NBElectric Logs', 'manage_options', 'nbe-logs', 'nbe_logs_page');
}

/**
 * Encolar estilos CSS solo en las páginas del plugin
 * Evita conflictos con otros plugins
 */
function nbe_enqueue_admin_styles($hook) {
    // Solo cargar en nuestras páginas
    if ($hook !== 'settings_page_nbe' && $hook !== 'tools_page_nbe-logs') {
        return;
    }

    // Nuevo diseño Vercel-inspired
    wp_enqueue_style(
        'nbe-admin-styles-v2',
        plugin_dir_url(__FILE__) . '../assets/admin-styles-v2.css',
        array(),
        VERSION_NBE . '.2'
    );
}
add_action('admin_enqueue_scripts', 'nbe_enqueue_admin_styles');

/**
 * Watchdog: si el evento de cron se perdió (por ejemplo, tras una
 * actualización sin reactivar el plugin), lo reprograma. Corre solo
 * en las páginas del propio plugin para no pagar el costo en cada
 * carga de /wp-admin/ del sitio.
 */
function nbe_cron_watchdog($screen)
{
    if ($screen->id !== 'settings_page_nbe' && $screen->id !== 'tools_page_nbe-logs') {
        return;
    }

    if (!wp_next_scheduled('nbe_cron_sync_event')) {
        nbe_update_cron_schedule();
        nbe_log('Watchdog: evento nbe_cron_sync_event no estaba programado, se reprogramó automáticamente', 'warning');
    }
}
add_action('current_screen', 'nbe_cron_watchdog');

function nbe_register_settings()
{
    register_setting('nbe_options', 'nbe_user');
    register_setting('nbe_options', 'nbe_password');
    register_setting('nbe_options', 'nbe_token');
    register_setting('nbe_options', 'nbe_prefix');
    register_setting('nbe_options', 'nbe_sync_no_iva');
    register_setting('nbe_options', 'nbe_sync_usd');
    register_setting('nbe_options', 'nbe_description');
    register_setting('nbe_options', 'nbe_sync_interval');
    register_setting('nbe_options', 'nbe_tax_class_map', array(
        'type'              => 'array',
        'sanitize_callback' => 'nbe_sanitize_tax_class_map',
        'default'           => array(),
    ));
}

/**
 * Sanea el mapa IVA -> slug de clase fiscal.
 * Claves permitidas: standard, reduced, exempt. Valores: slug (sanitize_key), '' válido.
 *
 * @param mixed $value
 * @return array
 */
function nbe_sanitize_tax_class_map($value)
{
    if (!is_array($value)) {
        return array();
    }

    $out = array();
    foreach (array('standard', 'reduced', 'exempt') as $key) {
        if (!array_key_exists($key, $value)) {
            continue;
        }
        $slug = (string) $value[$key];
        $out[$key] = $slug === '' ? '' : sanitize_key($slug);
    }

    return $out;
}

function nbe_activation()
{
    nbe_update_cron_schedule();
    
    // Crear directorios necesarios con permisos correctos
    nbe_create_plugin_directories();
}

/**
 * Crea los directorios necesarios para el plugin con permisos de escritura
 */
function nbe_create_plugin_directories()
{
    $plugin_dir = plugin_dir_path(__FILE__) . '../';
    
    $directories = array(
        $plugin_dir . 'nbe-products/',
        $plugin_dir . 'logs-sync-nbe/',
        $plugin_dir . 'nbe-descriptions/'
    );
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            // Crear directorio con permisos 0755
            if (!mkdir($dir, 0755, true)) {
                wp_mkdir_p($dir);
            }
        }
        
        // Asegurar permisos de escritura
        if (is_dir($dir) && !is_writable($dir)) {
            @chmod($dir, 0755);
            if (!is_writable($dir)) {
                @chmod($dir, 0775);
            }
        }
        
        // Crear archivo index.php de protección
        $index_file = $dir . 'index.php';
        if (!file_exists($index_file) && is_writable($dir)) {
            file_put_contents($index_file, "<?php\n// Silence is golden.\n");
        }
    }
    
    // Crear .htaccess en logs-sync-nbe para protección adicional
    $htaccess_file = $plugin_dir . 'logs-sync-nbe/.htaccess';
    if (!file_exists($htaccess_file) && is_writable($plugin_dir . 'logs-sync-nbe/')) {
        file_put_contents($htaccess_file, "Deny from all\n");
    }
}

function nbe_deactivation()
{
    // Limpiar agresivamente todos los eventos programados
    $timestamp = wp_next_scheduled('nbe_cron_sync_event');
    
    // Método 1: Desprogramar el próximo evento
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'nbe_cron_sync_event');
    }
    
    // Método 2: Limpiar TODOS los eventos de este hook (por si hay múltiples)
    wp_clear_scheduled_hook('nbe_cron_sync_event');
    
    // Método 3: Verificar y limpiar cualquier evento residual en el cron array
    $cron_array = _get_cron_array();
    if (is_array($cron_array)) {
        foreach ($cron_array as $timestamp => $cron) {
            if (isset($cron['nbe_cron_sync_event'])) {
                unset($cron_array[$timestamp]['nbe_cron_sync_event']);
                if (empty($cron_array[$timestamp])) {
                    unset($cron_array[$timestamp]);
                }
            }
        }
        _set_cron_array($cron_array);
    }
    
    // Liberar cualquier lock de sincronización que haya quedado
    delete_transient('nbe_sync_running');

    // Log de desactivación
    error_log('[NBElectric] Plugin desactivado - Todos los cron events eliminados: ' . date('Y-m-d H:i:s'));
}

/**
 * Función de desinstalación completa
 * Se ejecuta cuando el plugin es eliminado completamente desde WordPress
 * Limpia TODAS las opciones, cron events y datos del plugin
 */
function nbe_uninstall()
{
    // 1. Limpiar todos los cron events (triple verificación)
    wp_clear_scheduled_hook('nbe_cron_sync_event');
    
    $cron_array = _get_cron_array();
    if (is_array($cron_array)) {
        foreach ($cron_array as $timestamp => $cron) {
            if (isset($cron['nbe_cron_sync_event'])) {
                unset($cron_array[$timestamp]['nbe_cron_sync_event']);
                if (empty($cron_array[$timestamp])) {
                    unset($cron_array[$timestamp]);
                }
            }
        }
        _set_cron_array($cron_array);
    }
    
    // 2. Eliminar todas las opciones del plugin
    delete_option('nbe_user');
    delete_option('nbe_password');
    delete_option('nbe_token');
    delete_option('nbe_token_expiry');
    delete_option('nbe_prefix');
    delete_option('nbe_sync_no_iva');
    delete_option('nbe_sync_usd');
    delete_option('nbe_description');
    delete_option('nbe_sync_interval');
    delete_option('nbe_tax_class_map');
    delete_option('nbe_last_update');
    delete_option('nbe_last_auto_sync');
    delete_option('nbe_last_manual_sync');
    delete_option('nbe_last_description_update');

    // 3. Limpiar transients relacionados (si existen)
    delete_transient('nbe_api_token');
    delete_transient('nbe_sync_status');
    delete_transient('nbe_sync_running');
    delete_transient('nbe_sync_stats');
    delete_transient('nbe_description_stats');

    // 3b. Borrar el directorio de logs (wp-content/uploads/nbe-logs/)
    if (function_exists('nbe_log_dir')) {
        $log_dir = nbe_log_dir();
        foreach ((array) glob($log_dir . '*') as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir($log_dir);
    }
    
    // 4. Limpiar logs (opcional - comentado por si el usuario quiere mantener historial)
    // $logs_dir = plugin_dir_path(__FILE__) . '../logs-sync-nbe/';
    // if (is_dir($logs_dir)) {
    //     $files = glob($logs_dir . '*');
    //     foreach ($files as $file) {
    //         if (is_file($file)) {
    //             unlink($file);
    //         }
    //     }
    //     rmdir($logs_dir);
    // }
    
    // 5. Log de desinstalación
    error_log('[NBElectric] Plugin DESINSTALADO completamente - Todas las opciones y cron events eliminados: ' . date('Y-m-d H:i:s'));
    
    // 6. Nota: Los productos de WooCommerce NO se eliminan automáticamente
    // El usuario debe eliminarlos manualmente si lo desea
}

// Los hooks se registran en el archivo principal woocommerce-nbelectric.php
// para evitar duplicados y problemas de plugin_basename
