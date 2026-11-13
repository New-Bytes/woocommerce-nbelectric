<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página de configuración del Conector NBElectric
 * Versión 3 - UX Mejorada con Tabs, Dashboard, Toast y más
 */

function nbe_options_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $is_authenticated = nbe_check_auth_status();
    $plugin_url = plugin_dir_url(__FILE__);
    $icon_url = $plugin_url . '../assets/icon-128x128.png';
    $latest_commit = get_latest_version_nbe();
    $show_new_version_button = (
        preg_match('/^\d+(\.\d+)*$/', (string) $latest_commit)
        && version_compare($latest_commit, VERSION_NBE, '>')
    );

    // Obtener estadísticas de productos
    $product_stats = nbe_get_product_stats();
    
    // Información de sincronización
    $interval = intval(get_option('nbe_sync_interval', 3600));
    $next_sync = wp_next_scheduled('nbe_cron_sync_event');
    $last_update = get_option('nbe_last_update');
    
    if (!$next_sync) {
        $next_sync = time() + $interval;
    }
    $time_diff = max(0, $next_sync - time());
    $minutes_remaining = floor($time_diff / 60);

    echo '<div class="nbe-wrap">';
    echo '<div class="nbe-container" style="max-width: 900px;">';

    // Toast container
    echo '<div id="nbe-toast-container" class="nbe-toast-container"></div>';

    // ============================================
    // Header
    // ============================================
    echo '<header class="nbe-page-header">';
    echo '<div class="nbe-page-header-left">';
    echo '<img src="' . esc_url($icon_url) . '" alt="NBElectric" class="nbe-logo">';
    echo '<div>';
    echo '<h1 class="nbe-page-title">Conector NBElectric</h1>';
    echo '<p class="nbe-page-subtitle">Sincronización de productos con WooCommerce</p>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="nbe-flex nbe-items-center nbe-gap-4">';
    if ($show_new_version_button) {
        echo '<button type="button" id="update-connector-btn" class="nbe-btn nbe-btn-sm nbe-btn-secondary nbe-version-badge-new">';
        echo '<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>';
        echo 'v' . esc_html($latest_commit);
        echo '</button>';
    } else {
        echo '<span class="nbe-version-badge">v' . VERSION_NBE . '</span>';
    }
    echo '</div>';
    echo '</header>';

    // ============================================
    // Dashboard de Estado
    // ============================================
    echo '<div class="nbe-dashboard">';
    
    // Productos totales
    echo '<div class="nbe-dashboard-card">';
    echo '<svg class="nbe-dashboard-card-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>';
    echo '<div class="nbe-dashboard-value">' . number_format($product_stats['total']) . '</div>';
    echo '<div class="nbe-dashboard-label">Productos NBE</div>';
    echo '</div>';
    
    // Con stock
    echo '<div class="nbe-dashboard-card">';
    echo '<svg class="nbe-dashboard-card-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    echo '<div class="nbe-dashboard-value" style="color: var(--nbe-success);">' . number_format($product_stats['in_stock']) . '</div>';
    echo '<div class="nbe-dashboard-label">Con stock</div>';
    echo '</div>';
    
    // Próxima sync
    echo '<div class="nbe-dashboard-card">';
    echo '<svg class="nbe-dashboard-card-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    echo '<div class="nbe-dashboard-value">' . $minutes_remaining . '<span style="font-size: 14px; font-weight: 400;">m</span></div>';
    echo '<div class="nbe-dashboard-label">Próxima sync</div>';
    echo '</div>';
    
    // Estado conexión
    echo '<div class="nbe-dashboard-card">';
    echo '<svg class="nbe-dashboard-card-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>';
    $status_color = $is_authenticated ? 'var(--nbe-success)' : 'var(--nbe-error)';
    $status_text = $is_authenticated ? 'Online' : 'Offline';
    echo '<div class="nbe-dashboard-value" style="color: ' . $status_color . ';">' . $status_text . '</div>';
    echo '<div class="nbe-dashboard-label">Estado API</div>';
    echo '</div>';
    
    echo '</div>'; // dashboard

    // ============================================
    // Alerta FIFU
    // ============================================
    if (!nbe_is_fifu_active()) {
        echo '<div class="nbe-alert nbe-alert-warning nbe-mb-4">';
        echo '<svg class="nbe-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
        echo '<div class="nbe-alert-content">';
        echo '<p class="nbe-alert-title">Plugin requerido</p>';
        echo '<p class="nbe-alert-text">Para las imágenes se requiere <a href="' . wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=featured-image-from-url'), 'install-plugin_featured-image-from-url') . '" style="text-decoration: underline;">FIFU</a></p>';
        echo '</div>';
        echo '</div>';
    }

    // ============================================
    // Tabs Navigation
    // ============================================
    echo '<div class="nbe-card">';
    echo '<div class="nbe-tabs">';
    echo '<button class="nbe-tab active" data-tab="credentials">Credenciales</button>';
    echo '<button class="nbe-tab" data-tab="sync">Sincronización</button>';
    echo '<button class="nbe-tab" data-tab="tools">Herramientas</button>';
    echo '</div>';

    // ============================================
    // Tab: Credenciales
    // ============================================
    echo '<div id="tab-credentials" class="nbe-tab-content active">';
    echo '<div class="nbe-card-body">';
    
    // Estado de conexión
    echo '<div class="nbe-connection-status" id="connection-status">';
    $indicator_class = $is_authenticated ? 'connected' : 'disconnected';
    echo '<div class="nbe-connection-indicator ' . $indicator_class . '" id="connection-indicator"></div>';
    echo '<div class="nbe-connection-text">';
    echo '<div class="nbe-connection-title" id="connection-title">' . ($is_authenticated ? 'Conectado a NBElectric API' : 'Sin conexión') . '</div>';
    echo '<div class="nbe-connection-subtitle" id="connection-subtitle">' . ($is_authenticated ? 'Las credenciales son válidas' : 'Verifica tus credenciales') . '</div>';
    echo '</div>';
    echo '<button type="button" id="test-connection-btn" class="nbe-btn nbe-btn-sm nbe-btn-secondary">';
    echo '<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
    echo 'Probar';
    echo '</button>';
    echo '</div>';
    
    echo '<form id="nbe-credentials-form">';
    
    // Usuario
    echo '<div class="nbe-form-group">';
    echo '<label for="nbe_user" class="nbe-form-label">Usuario</label>';
    echo '<input type="text" name="nbe_user" id="nbe_user" value="' . esc_attr(get_option('nbe_user')) . '" class="nbe-input" placeholder="Tu usuario de NBElectric" autocomplete="username" />';
    echo '</div>';
    
    // Contraseña
    echo '<div class="nbe-form-group">';
    echo '<label for="nbe_password" class="nbe-form-label">Contraseña</label>';
    echo '<input type="password" name="nbe_password" id="nbe_password" value="' . esc_attr(get_option('nbe_password')) . '" class="nbe-input" placeholder="••••••••" autocomplete="current-password" />';
    echo '</div>';
    
    // Prefijo SKU con preview
    echo '<div class="nbe-form-group">';
    echo '<div class="nbe-flex nbe-items-center nbe-gap-2">';
    echo '<label for="nbe_prefix" class="nbe-form-label" style="margin-bottom: 0;">Prefijo SKU</label>';
    echo '<div class="nbe-tooltip-wrapper">';
    echo '<span class="nbe-tooltip-trigger"><svg class="nbe-icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>';
    echo '<span class="nbe-tooltip">Identifica productos NBElectric en tu catálogo</span>';
    echo '</div>';
    echo '</div>';
    echo '<input type="text" name="nbe_prefix" id="nbe_prefix" value="' . esc_attr(get_option('nbe_prefix')) . '" class="nbe-input" placeholder="NBE_" />';
    echo '<div class="nbe-sku-preview">';
    echo '<span class="nbe-sku-preview-label">Preview:</span>';
    echo '<span class="nbe-sku-preview-value" id="sku-preview">' . esc_html(get_option('nbe_prefix', 'NBE_')) . '12345</span>';
    echo '</div>';
    echo '</div>';
    
    echo '<button type="submit" class="nbe-btn nbe-btn-primary">';
    echo '<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    echo 'Guardar credenciales';
    echo '</button>';
    
    echo '</form>';
    echo '</div>'; // card-body
    echo '</div>'; // tab-credentials

    // ============================================
    // Tab: Sincronización
    // ============================================
    echo '<div id="tab-sync" class="nbe-tab-content">';
    echo '<div class="nbe-card-body">';
    
    echo '<form id="nbe-sync-settings-form">';
    
    // Intervalo
    echo '<div class="nbe-form-group">';
    echo '<label for="nbe_sync_interval" class="nbe-form-label">Intervalo de sincronización automática</label>';
    echo '<select name="nbe_sync_interval" id="nbe_sync_interval" class="nbe-input nbe-select">';
    $intervals = array(
        '3600'  => 'Cada 1 hora',
        '7200'  => 'Cada 2 horas',
        '10800' => 'Cada 3 horas',
        '14400' => 'Cada 4 horas',
        '21600' => 'Cada 6 horas',
        '28800' => 'Cada 8 horas',
        '43200' => 'Cada 12 horas'
    );
    $current_interval = get_option('nbe_sync_interval', 3600);
    foreach ($intervals as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($current_interval, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="nbe-form-hint">El intervalo define cada cuánto <em>debe</em> intentarse la sincronización, no un temporizador exacto: se ejecuta la primera vez que alguien visita el sitio después de vencido ese tiempo. Si el sitio recibe poco tráfico, la sync puede tardar más que el intervalo elegido — no es un error.</p>';
    echo '</div>';

    // Checkboxes de precio
    echo '<div class="nbe-form-group">';
    echo '<label class="nbe-form-label">Opciones de precio</label>';
    echo '<label class="nbe-checkbox-group" style="margin-bottom: 8px;">';
    echo '<input type="checkbox" name="nbe_sync_no_iva" id="nbe_sync_no_iva" value="1" ' . checked(1, get_option('nbe_sync_no_iva'), false) . ' class="nbe-checkbox" />';
    echo '<span class="nbe-checkbox-label">Sincronizar precios sin IVA</span>';
    echo '</label>';
    echo '<label class="nbe-checkbox-group">';
    echo '<input type="checkbox" name="nbe_sync_usd" id="nbe_sync_usd" value="1" ' . checked(1, get_option('nbe_sync_usd'), false) . ' class="nbe-checkbox" />';
    echo '<span class="nbe-checkbox-label">Sincronizar precios en USD</span>';
    echo '</label>';
    echo '</div>';

    // Nota sobre clases fiscales de IVA
    echo '<div class="nbe-alert nbe-alert-info nbe-mt-2">';
    echo '<svg class="nbe-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    echo '<div class="nbe-alert-content">';
    echo '<p class="nbe-alert-title">Configuración de IVA en WooCommerce</p>';
    echo '<p class="nbe-alert-text">El conector asigna automáticamente la clase fiscal de cada producto según el IVA que informa la API (21% → Estándar, 10,5% → Reducido, 0% → Exento). Configurá las tasas correspondientes en <a href="' . admin_url('admin.php?page=wc-settings&tab=tax') . '" style="text-decoration: underline;">WooCommerce → Ajustes → Impuestos</a>.</p>';
    echo '<p class="nbe-alert-text" style="margin-top:8px;"><strong>Precio + IVA.</strong> El campo <em>“Sincronizar precios sin IVA”</em> debe combinarse con el ajuste <em>“Precios introducidos con impuestos”</em> de WooCommerce:</p>';
    echo '<ul class="nbe-alert-text" style="margin:4px 0 0 16px; list-style:disc;">';
    echo '<li><strong>Recomendado:</strong> tildá “sin IVA” y dejá WooCommerce en “Precios con impuestos: No”. WooCommerce agrega el IVA según la clase fiscal.</li>';
    echo '<li>Alternativa: destildá “sin IVA” y poné WooCommerce en “Precios con impuestos: Sí”.</li>';
    echo '<li><strong>Evitá:</strong> destildado + “Precios con impuestos: No” → el IVA se cobra dos veces.</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    
    // Descripción adicional
    echo '<div class="nbe-form-group">';
    echo '<label for="nbe_description" class="nbe-form-label">Descripción adicional (opcional)</label>';
    echo '<textarea name="nbe_description" id="nbe_description" class="nbe-input nbe-textarea" placeholder="Texto que se agregará a todos los productos...">' . esc_textarea(get_option('nbe_description')) . '</textarea>';
    echo '<p class="nbe-form-hint">Se añadirá al final de la descripción de cada producto</p>';
    echo '</div>';
    
    // Estado de salud de la sincronización automática
    $cron_health = nbe_get_cron_health();
    $diagnostic_text = nbe_build_diagnostic_text($cron_health);
    $severity_class = array(
        'ok'      => 'nbe-alert-success',
        'warning' => 'nbe-alert-warning',
        'error'   => 'nbe-alert-error',
    )[$cron_health['severity']];

    echo '<div class="nbe-alert ' . $severity_class . ' nbe-mb-4" id="nbe-cron-health-banner">';
    echo '<svg class="nbe-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    echo '<div class="nbe-alert-content">';
    echo '<p class="nbe-alert-title">Estado de sincronización automática</p>';
    echo '<p class="nbe-alert-text">' . esc_html($cron_health['message']) . '</p>';

    echo '<div class="nbe-data-row" style="margin-top:8px;">';
    echo '<span class="nbe-data-label">Última sync automática</span>';
    echo '<span class="nbe-data-value">' . esc_html($cron_health['last_auto_sync'] ? human_time_diff(strtotime($cron_health['last_auto_sync'])) . ' atrás' : 'Nunca') . '</span>';
    echo '</div>';
    echo '<div class="nbe-data-row">';
    echo '<span class="nbe-data-label">Última sync manual</span>';
    echo '<span class="nbe-data-value">' . esc_html($cron_health['last_manual_sync'] ? human_time_diff(strtotime($cron_health['last_manual_sync'])) . ' atrás' : 'Nunca') . '</span>';
    echo '</div>';

    if ($cron_health['severity'] !== 'ok') {
        echo '<button type="button" id="nbe-sync-now-from-banner-btn" class="nbe-btn nbe-btn-sm nbe-btn-primary nbe-mt-2" style="margin-right:8px;">Sincronizar ahora</button>';
    }
    echo '<button type="button" id="nbe-copy-diagnostics-btn" class="nbe-btn nbe-btn-sm nbe-btn-secondary nbe-mt-2" data-diagnostic="' . esc_attr($diagnostic_text) . '">Copiar diagnóstico</button>';
    echo '</div>'; // nbe-alert-content
    echo '</div>'; // nbe-alert

    // Info última sync
    $last_update_formatted = $last_update ? date('d/m/Y H:i', strtotime($last_update . '-3 hours')) : 'Nunca';
    echo '<div class="nbe-data-row" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--nbe-border-primary);">';
    echo '<span class="nbe-data-label">Última sincronización</span>';
    echo '<span class="nbe-data-value" id="last-sync-time">' . esc_html($last_update_formatted) . '</span>';
    echo '</div>';
    
    echo '<button type="submit" class="nbe-btn nbe-btn-primary nbe-mt-4">';
    echo '<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    echo 'Guardar configuración';
    echo '</button>';
    
    echo '</form>';
    echo '</div>'; // card-body
    echo '</div>'; // tab-sync

    // ============================================
    // Tab: Herramientas
    // ============================================
    echo '<div id="tab-tools" class="nbe-tab-content">';
    echo '<div class="nbe-card-body">';
    
    // Sincronizar productos
    echo '<div class="nbe-action-card nbe-mb-4">';
    echo '<div class="nbe-action-info">';
    echo '<div class="nbe-action-icon">';
    echo '<svg class="nbe-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
    echo '</div>';
    echo '<div class="nbe-action-text">';
    echo '<h4>Sincronizar productos</h4>';
    echo '<p>Actualiza todos los productos desde la API de NBElectric</p>';
    echo '</div>';
    echo '</div>';
    echo '<button type="button" id="btn-prepare-sync" class="nbe-btn nbe-btn-primary">';
    echo '<span id="btn-prepare-sync-text">Sincronizar ahora</span>';
    echo '<span id="btn-prepare-sync-spinner" class="nbe-hidden"><span class="nbe-spinner"></span></span>';
    echo '</button>';
    echo '</div>';
    
    // Ver logs
    echo '<div class="nbe-action-card nbe-mb-4">';
    echo '<div class="nbe-action-info">';
    echo '<div class="nbe-action-icon">';
    echo '<svg class="nbe-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
    echo '</div>';
    echo '<div class="nbe-action-text">';
    echo '<h4>Historial de sincronizaciones</h4>';
    echo '<p>Consulta los logs detallados de cada sincronización</p>';
    echo '</div>';
    echo '</div>';
    echo '<a href="' . admin_url('tools.php?page=nbe-logs') . '" class="nbe-btn nbe-btn-secondary">Ver logs</a>';
    echo '</div>';
    
    // Sincronizar descripciones
    echo '<div class="nbe-action-card">';
    echo '<div class="nbe-action-info">';
    echo '<div class="nbe-action-icon">';
    echo '<svg class="nbe-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
    echo '</div>';
    echo '<div class="nbe-action-text">';
    echo '<h4>Actualizar descripciones</h4>';
    echo '<p>Reemplaza las descripciones con las de la API</p>';
    echo '</div>';
    echo '</div>';
    echo '<button type="button" id="update-description-btn" class="nbe-btn nbe-btn-secondary">Actualizar</button>';
    echo '</div>';
    
    // Zona de peligro
    echo '<div class="nbe-danger-zone">';
    echo '<div class="nbe-danger-zone-header" onclick="this.parentElement.classList.toggle(\'is-open\')">';
    echo '<div class="nbe-danger-zone-title">';
    echo '<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
    echo 'Zona de peligro';
    echo '</div>';
    echo '<div class="nbe-danger-zone-toggle">';
    echo '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
    echo '</div>';
    echo '</div>';
    echo '<div class="nbe-danger-zone-content">';
    echo '<div class="nbe-action-card">';
    echo '<div class="nbe-action-info">';
    echo '<div class="nbe-action-icon" style="background: var(--nbe-error-bg); color: var(--nbe-error);">';
    echo '<svg class="nbe-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
    echo '</div>';
    echo '<div class="nbe-action-text">';
    echo '<h4>Eliminar todos los productos</h4>';
    echo '<p>Elimina permanentemente todos los productos de NBElectric</p>';
    echo '</div>';
    echo '</div>';
    echo '<button type="button" id="delete-all-btn" class="nbe-btn nbe-btn-danger">Eliminar</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // card-body
    echo '</div>'; // tab-tools

    echo '</div>'; // card

    // Footer
    echo '<div class="nbe-text-center nbe-mt-6" style="color: var(--nbe-text-tertiary); font-size: 13px;">';
    echo '<p>¿Necesitas ayuda? <a href="https://conecta.nb.com.ar/" target="_blank" style="color: var(--nbe-text-secondary); text-decoration: underline;">Documentación</a></p>';
    echo '</div>';

    // Nonces
    echo '<input type="hidden" id="nbe_sync_nonce" value="' . wp_create_nonce('nbe_sync_nonce') . '" />';
    echo '<input type="hidden" id="nbe_settings_nonce" value="' . wp_create_nonce('nbe_settings_nonce') . '" />';

    echo '</div>'; // container
    echo '</div>'; // wrap

    // Modales
    nbe_render_modals_v3();
    
    // JavaScript
    nbe_render_scripts_v3();
}

/**
 * Obtener estadísticas de productos NBElectric
 */
function nbe_get_product_stats() {
    global $wpdb;
    
    $prefix = get_option('nbe_prefix', 'NBE_');
    
    // Total de productos con el prefijo
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = '_sku' 
         AND pm.meta_value LIKE %s
         AND p.post_type = 'product'
         AND p.post_status = 'publish'",
        $prefix . '%'
    ));
    
    // Productos con stock
    $in_stock = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = '_sku' 
         AND pm.meta_value LIKE %s
         AND pm2.meta_key = '_stock'
         AND CAST(pm2.meta_value AS SIGNED) > 0
         AND p.post_type = 'product'
         AND p.post_status = 'publish'",
        $prefix . '%'
    ));
    
    return array(
        'total' => intval($total),
        'in_stock' => intval($in_stock),
        'out_of_stock' => intval($total) - intval($in_stock)
    );
}

/**
 * Renderiza los modales
 */
function nbe_render_modals_v3()
{
    // Modal: Confirmar eliminación
    echo '<div id="delete-confirm-modal" class="nbe-modal hidden">';
    echo '<div class="nbe-modal-content">';
    echo '<div class="nbe-modal-header">';
    echo '<div class="nbe-modal-icon nbe-modal-icon-warning">';
    echo '<svg class="nbe-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
    echo '</div>';
    echo '<h3 class="nbe-modal-title">Eliminar productos</h3>';
    echo '<p class="nbe-modal-text">Esta acción eliminará todos los productos de NBElectric. No se puede deshacer.</p>';
    echo '</div>';
    echo '<form id="confirm-delete-form">';
    echo '<input type="hidden" name="action" value="nbe_delete_products" />';
    echo '<input type="hidden" name="delete_all" value="1" />';
    wp_nonce_field('nbe_delete_all', 'nbe_delete_all_nonce');
    echo '<div class="nbe-modal-footer">';
    echo '<button type="button" id="cancel-delete" class="nbe-btn nbe-btn-secondary">Cancelar</button>';
    echo '<button type="button" id="confirm-delete-btn" class="nbe-btn nbe-btn-danger">Eliminar todo</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    echo '</div>';

    // Modal: Confirmar actualización de descripciones (paso 1 - preparar)
    echo '<div id="nbe-modal-desc-confirm" class="nbe-modal hidden">';
    echo '<div class="nbe-modal-content">';
    echo '<div class="nbe-modal-header">';
    echo '<div class="nbe-modal-icon nbe-modal-icon-info">';
    echo '<svg class="nbe-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
    echo '</div>';
    echo '<h3 class="nbe-modal-title">Actualizar descripciones</h3>';
    echo '</div>';
    echo '<div class="nbe-modal-body">';
    echo '<div class="nbe-stats-grid">';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value" id="nbe-desc-total">-</div><div class="nbe-stat-label">Descripciones</div></div>';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value nbe-stat-value-info" id="nbe-desc-file">-</div><div class="nbe-stat-label">Archivo JSON</div></div>';
    echo '</div>';
    echo '<p class="nbe-mt-4 nbe-text-center" style="font-size: 13px; color: var(--nbe-text-tertiary);">Tiempo estimado: <strong id="nbe-desc-time">-</strong></p>';
    echo '<p class="nbe-mt-2 nbe-text-center" style="font-size: 12px; color: var(--nbe-text-tertiary);">Se descargará el catálogo de descripciones y se procesará localmente.</p>';
    echo '</div>';
    echo '<div class="nbe-modal-footer">';
    echo '<button type="button" id="nbe-btn-cancel-desc" class="nbe-btn nbe-btn-secondary">Cancelar</button>';
    echo '<button type="button" id="nbe-btn-confirm-desc" class="nbe-btn nbe-btn-primary">Iniciar actualización</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Modal: Progreso de actualización de descripciones
    echo '<div id="nbe-modal-desc-progress" class="nbe-modal hidden">';
    echo '<div class="nbe-modal-content">';
    echo '<div class="nbe-modal-header">';
    echo '<div class="nbe-modal-icon nbe-modal-icon-info" id="nbe-desc-progress-icon-loading">';
    echo '<svg class="nbe-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation: nbe-spin 1s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
    echo '</div>';
    echo '<div class="nbe-modal-icon nbe-modal-icon-success nbe-hidden" id="nbe-desc-progress-icon-success">';
    echo '<svg class="nbe-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    echo '</div>';
    echo '<h3 class="nbe-modal-title" id="nbe-desc-progress-title">Actualizando descripciones...</h3>';
    echo '</div>';
    echo '<div class="nbe-modal-body">';
    echo '<div class="nbe-progress-wrapper">';
    echo '<div class="nbe-progress-header">';
    echo '<span class="nbe-progress-label" id="nbe-desc-progress-text">0 / 0</span>';
    echo '<span class="nbe-progress-value" id="nbe-desc-progress-percent">0%</span>';
    echo '</div>';
    echo '<div class="nbe-progress-track"><div class="nbe-progress-fill" id="nbe-desc-progress-bar" style="width: 0%;"></div></div>';
    echo '</div>';
    echo '<div class="nbe-stats-grid nbe-mt-4">';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value nbe-stat-value-success" id="nbe-desc-stat-updated">0</div><div class="nbe-stat-label">Actualizadas</div></div>';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value nbe-stat-value-warning" id="nbe-desc-stat-notfound">0</div><div class="nbe-stat-label">No encontrados</div></div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="nbe-modal-footer nbe-hidden" id="nbe-desc-progress-close-container">';
    echo '<button type="button" id="nbe-btn-close-desc-progress" class="nbe-btn nbe-btn-success nbe-w-full">Cerrar</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Modal: Confirmar sincronización
    echo '<div id="nbe-modal-sync-confirm" class="nbe-modal hidden">';
    echo '<div class="nbe-modal-content">';
    echo '<div class="nbe-modal-header">';
    echo '<div class="nbe-modal-icon nbe-modal-icon-info">';
    echo '<svg class="nbe-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
    echo '</div>';
    echo '<h3 class="nbe-modal-title">Confirmar sincronización</h3>';
    echo '</div>';
    echo '<div class="nbe-modal-body">';
    echo '<div class="nbe-stats-grid">';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value" id="nbe-sync-total">-</div><div class="nbe-stat-label">Total</div></div>';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value nbe-stat-value-success" id="nbe-sync-with-stock">-</div><div class="nbe-stat-label">Con stock</div></div>';
    echo '</div>';
    echo '<p class="nbe-mt-4 nbe-text-center" style="font-size: 13px; color: var(--nbe-text-tertiary);">Tiempo estimado: <strong id="nbe-sync-time">-</strong></p>';
    echo '</div>';
    echo '<div class="nbe-modal-footer">';
    echo '<button type="button" id="nbe-btn-cancel-sync" class="nbe-btn nbe-btn-secondary">Cancelar</button>';
    echo '<button type="button" id="nbe-btn-confirm-sync" class="nbe-btn nbe-btn-primary">Iniciar</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Modal: Progreso de sincronización
    echo '<div id="nbe-modal-sync-progress" class="nbe-modal hidden">';
    echo '<div class="nbe-modal-content">';
    echo '<div class="nbe-modal-header">';
    echo '<div class="nbe-modal-icon nbe-modal-icon-info" id="nbe-progress-icon-loading">';
    echo '<svg class="nbe-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation: nbe-spin 1s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
    echo '</div>';
    echo '<div class="nbe-modal-icon nbe-modal-icon-success nbe-hidden" id="nbe-progress-icon-success">';
    echo '<svg class="nbe-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    echo '</div>';
    echo '<h3 class="nbe-modal-title" id="nbe-progress-title">Sincronizando...</h3>';
    echo '</div>';
    echo '<div class="nbe-modal-body">';
    echo '<div class="nbe-progress-wrapper">';
    echo '<div class="nbe-progress-header">';
    echo '<span class="nbe-progress-label" id="nbe-progress-text">0 / 0</span>';
    echo '<span class="nbe-progress-value" id="nbe-progress-percent">0%</span>';
    echo '</div>';
    echo '<div class="nbe-progress-track"><div class="nbe-progress-fill" id="nbe-progress-bar" style="width: 0%;"></div></div>';
    echo '</div>';
    echo '<div class="nbe-stats-grid nbe-mt-4">';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value nbe-stat-value-success" id="nbe-stat-created">0</div><div class="nbe-stat-label">Creados</div></div>';
    echo '<div class="nbe-stat-card"><div class="nbe-stat-value nbe-stat-value-info" id="nbe-stat-updated">0</div><div class="nbe-stat-label">Actualizados</div></div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="nbe-modal-footer nbe-hidden" id="nbe-progress-close-container">';
    echo '<button type="button" id="nbe-btn-close-progress" class="nbe-btn nbe-btn-success nbe-w-full">Cerrar</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

/**
 * Renderiza los scripts JavaScript
 */
function nbe_render_scripts_v3()
{
    $ajax_url = esc_url(admin_url('admin-ajax.php'));
    ?>
    <script>
    jQuery(document).ready(function($) {
        // ============================================
        // Toast System
        // ============================================
        function showToast(type, title, message) {
            var icons = {
                success: '<svg class="nbe-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                error: '<svg class="nbe-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                warning: '<svg class="nbe-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                info: '<svg class="nbe-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            
            var $toast = $('<div class="nbe-toast nbe-toast-' + type + '">' +
                icons[type] +
                '<div class="nbe-toast-content">' +
                '<p class="nbe-toast-title">' + title + '</p>' +
                (message ? '<p class="nbe-toast-message">' + message + '</p>' : '') +
                '</div>' +
                '<button class="nbe-toast-close"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>' +
                '</div>');
            
            $('#nbe-toast-container').append($toast);
            
            $toast.find('.nbe-toast-close').on('click', function() {
                $toast.addClass('hiding');
                setTimeout(function() { $toast.remove(); }, 200);
            });
            
            setTimeout(function() {
                $toast.addClass('hiding');
                setTimeout(function() { $toast.remove(); }, 200);
            }, 5000);
        }

        // ============================================
        // Tabs Navigation
        // ============================================
        $('.nbe-tab').on('click', function() {
            var tab = $(this).data('tab');
            $('.nbe-tab').removeClass('active');
            $(this).addClass('active');
            $('.nbe-tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
        });

        // ============================================
        // Copiar diagnóstico de salud del cron
        // ============================================
        $('#nbe-copy-diagnostics-btn').on('click', function() {
            var text = $(this).data('diagnostic');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('success', 'Copiado', 'Diagnóstico copiado al portapapeles');
                }, function() {
                    showToast('error', 'Error', 'No se pudo copiar. Copiá manualmente el texto.');
                });
            } else {
                var $tmp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $tmp.remove();
                showToast('success', 'Copiado', 'Diagnóstico copiado al portapapeles');
            }
        });

        // ============================================
        // SKU Preview
        // ============================================
        $('#nbe_prefix').on('input', function() {
            var prefix = $(this).val() || 'NBE_';
            $('#sku-preview').text(prefix + '12345');
        });

        // ============================================
        // Test Connection
        // ============================================
        $('#test-connection-btn').on('click', function() {
            var $btn = $(this);
            var $indicator = $('#connection-indicator');
            var $title = $('#connection-title');
            var $subtitle = $('#connection-subtitle');
            
            $btn.prop('disabled', true).html('<span class="nbe-spinner"></span>');
            $indicator.removeClass('connected disconnected').addClass('checking');
            $title.text('Verificando conexión...');
            $subtitle.text('');
            
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_test_connection',
                    nonce: $('#nbe_settings_nonce').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).html('<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Probar');
                    $indicator.removeClass('checking');
                    
                    if (response.success) {
                        $indicator.addClass('connected');
                        $title.text('Conectado a NBElectric API');
                        $subtitle.text('Las credenciales son válidas');
                        showToast('success', 'Conexión exitosa', 'API de NBElectric accesible');
                    } else {
                        $indicator.addClass('disconnected');
                        $title.text('Sin conexión');
                        $subtitle.text(response.data || 'Verifica tus credenciales');
                        showToast('error', 'Error de conexión', response.data || 'No se pudo conectar');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Probar');
                    $indicator.removeClass('checking').addClass('disconnected');
                    $title.text('Error de red');
                    $subtitle.text('No se pudo realizar la prueba');
                    showToast('error', 'Error', 'Error de conexión');
                }
            });
        });

        // ============================================
        // Save Credentials Form
        // ============================================
        $('#nbe-credentials-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<span class="nbe-spinner"></span> Guardando...');
            
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_save_credentials',
                    nonce: $('#nbe_settings_nonce').val(),
                    nbe_user: $('#nbe_user').val(),
                    nbe_password: $('#nbe_password').val(),
                    nbe_prefix: $('#nbe_prefix').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).html('<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar credenciales');
                    if (response.success) {
                        showToast('success', 'Guardado', 'Credenciales actualizadas correctamente');
                        $('#test-connection-btn').click();
                    } else {
                        showToast('error', 'Error', response.data || 'No se pudo guardar');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar credenciales');
                    showToast('error', 'Error', 'Error de conexión');
                }
            });
        });

        // ============================================
        // Save Sync Settings Form
        // ============================================
        $('#nbe-sync-settings-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<span class="nbe-spinner"></span> Guardando...');
            
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_save_sync_settings',
                    nonce: $('#nbe_settings_nonce').val(),
                    nbe_sync_interval: $('#nbe_sync_interval').val(),
                    nbe_sync_no_iva: $('#nbe_sync_no_iva').is(':checked') ? 1 : 0,
                    nbe_sync_usd: $('#nbe_sync_usd').is(':checked') ? 1 : 0,
                    nbe_description: $('#nbe_description').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).html('<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar configuración');
                    if (response.success) {
                        showToast('success', 'Guardado', 'Configuración actualizada');
                    } else {
                        showToast('error', 'Error', response.data || 'No se pudo guardar');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<svg class="nbe-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Guardar configuración');
                    showToast('error', 'Error', 'Error de conexión');
                }
            });
        });

        // ============================================
        // Update Connector
        // ============================================
        $('#update-connector-btn').on('click', function() {
            if (!confirm('¿Actualizar el conector a la última versión?')) return;
            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="nbe-spinner"></span>');
            
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_update_connector',
                    nonce: $('#nbe_settings_nonce').val()
                },
                success: function() {
                    showToast('success', 'Actualizado', 'Recargando página...');
                    setTimeout(function() { location.reload(); }, 1000);
                },
                error: function() {
                    $btn.prop('disabled', false).text('Actualizar');
                    showToast('error', 'Error', 'No se pudo actualizar');
                }
            });
        });

        // ============================================
        // Delete Products
        // ============================================
        $('#delete-all-btn').on('click', function() {
            $('#delete-confirm-modal').removeClass('hidden').addClass('flex');
        });

        $('#cancel-delete').on('click', function() {
            $('#delete-confirm-modal').addClass('hidden').removeClass('flex');
        });

        $('#confirm-delete-btn').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="nbe-spinner"></span> Eliminando...');
            
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: $('#confirm-delete-form').serialize(),
                success: function(response) {
                    $('#delete-confirm-modal').addClass('hidden').removeClass('flex');
                    $btn.prop('disabled', false).text('Eliminar todo');
                    if (response.success) {
                        showToast('success', 'Eliminado', 'Productos eliminados correctamente');
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        showToast('error', 'Error', response.data || 'No se pudo eliminar');
                    }
                },
                error: function() {
                    $('#delete-confirm-modal').addClass('hidden').removeClass('flex');
                    $btn.prop('disabled', false).text('Eliminar todo');
                    showToast('error', 'Error', 'Error de conexión');
                }
            });
        });

        // ============================================
        // Update Descriptions (nuevo flujo con descarga masiva)
        // ============================================
        var descData = {};
        var totalDescUpdated = 0;
        var totalDescNotFound = 0;

        $('#update-description-btn').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="nbe-spinner"></span>');
            
            // Primero preparar: descargar JSON de descripciones
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_prepare_descriptions',
                    nonce: $('#nbe_sync_nonce').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Actualizar');
                    
                    if (response.success) {
                        descData = response.data;
                        $('#nbe-desc-total').text(descData.total_descriptions);
                        $('#nbe-desc-file').html('<span style="font-size: 10px;">✓</span>');
                        $('#nbe-desc-time').text('~' + descData.estimated_time);
                        $('#nbe-modal-desc-confirm').removeClass('hidden').addClass('flex');
                    } else {
                        showToast('error', 'Error', response.data.message || 'No se pudo descargar');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Actualizar');
                    showToast('error', 'Error', 'Error de conexión al descargar descripciones');
                }
            });
        });

        $('#nbe-btn-cancel-desc').on('click', function() {
            $('#nbe-modal-desc-confirm').addClass('hidden').removeClass('flex');
        });

        $('#nbe-btn-confirm-desc').on('click', function() {
            $('#nbe-modal-desc-confirm').addClass('hidden').removeClass('flex');
            
            // Resetear estadísticas
            totalDescUpdated = 0;
            totalDescNotFound = 0;
            
            // Mostrar modal de progreso
            $('#nbe-desc-progress-title').text('Actualizando descripciones...');
            $('#nbe-desc-progress-icon-loading').removeClass('nbe-hidden');
            $('#nbe-desc-progress-icon-success').addClass('nbe-hidden');
            $('#nbe-desc-progress-close-container').addClass('nbe-hidden');
            $('#nbe-desc-progress-bar').css('width', '0%');
            $('#nbe-desc-progress-percent').text('0%');
            $('#nbe-desc-progress-text').text('0 / ' + descData.total_descriptions);
            $('#nbe-desc-stat-updated').text('0');
            $('#nbe-desc-stat-notfound').text('0');
            $('#nbe-modal-desc-progress').removeClass('hidden').addClass('flex');
            
            // Iniciar procesamiento por lotes
            processDescriptionsBatch(0);
        });

        function processDescriptionsBatch(offset) {
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_process_descriptions_batch',
                    nonce: $('#nbe_sync_nonce').val(),
                    offset: offset,
                    batch_size: 50
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        // Actualizar estadísticas
                        totalDescUpdated += data.batch_updated || 0;
                        totalDescNotFound += data.batch_not_found || 0;
                        
                        // Actualizar UI
                        var percent = Math.round((data.processed / data.total) * 100);
                        $('#nbe-desc-progress-bar').css('width', percent + '%');
                        $('#nbe-desc-progress-percent').text(percent + '%');
                        $('#nbe-desc-progress-text').text(data.processed + ' / ' + data.total);
                        $('#nbe-desc-stat-updated').text(totalDescUpdated);
                        $('#nbe-desc-stat-notfound').text(totalDescNotFound);
                        
                        if (data.completed) {
                            // Completado
                            $('#nbe-desc-progress-title').text('¡Completado!');
                            $('#nbe-desc-progress-icon-loading').addClass('nbe-hidden');
                            $('#nbe-desc-progress-icon-success').removeClass('nbe-hidden');
                            $('#nbe-desc-progress-close-container').removeClass('nbe-hidden');
                            
                            if (data.stats) {
                                $('#nbe-desc-stat-updated').text(data.stats.updated);
                                $('#nbe-desc-stat-notfound').text(data.stats.not_found);
                            }
                        } else {
                            // Continuar con el siguiente lote
                            processDescriptionsBatch(data.processed);
                        }
                    } else {
                        showToast('error', 'Error', response.data.message);
                        $('#nbe-modal-desc-progress').addClass('hidden').removeClass('flex');
                    }
                },
                error: function() {
                    showToast('error', 'Error', 'Error de conexión durante la actualización');
                    $('#nbe-modal-desc-progress').addClass('hidden').removeClass('flex');
                }
            });
        }

        $('#nbe-btn-close-desc-progress').on('click', function() {
            $('#nbe-modal-desc-progress').addClass('hidden').removeClass('flex');
            location.reload();
        });

        // ============================================
        // Sync with Progress
        // ============================================
        var syncData = {};
        var totalCreated = 0;
        var totalUpdated = 0;

        function startSync($btn) {
            $('#btn-prepare-sync-text').addClass('nbe-hidden');
            $('#btn-prepare-sync-spinner').removeClass('nbe-hidden');
            if ($btn) {
                $btn.prop('disabled', true);
            }

            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_prepare_sync',
                    nonce: $('#nbe_sync_nonce').val()
                },
                success: function(response) {
                    $('#btn-prepare-sync-text').removeClass('nbe-hidden');
                    $('#btn-prepare-sync-spinner').addClass('nbe-hidden');
                    if ($btn) {
                        $btn.prop('disabled', false);
                    }

                    if (response.success) {
                        syncData = response.data;
                        $('#nbe-sync-total').text(syncData.total_products);
                        $('#nbe-sync-with-stock').text(syncData.products_with_stock);
                        $('#nbe-sync-time').text('~' + syncData.estimated_time);
                        $('#nbe-modal-sync-confirm').removeClass('hidden').addClass('flex');
                    } else {
                        showToast('error', 'Error', response.data.message || 'No se pudo preparar');
                    }
                },
                error: function() {
                    $('#btn-prepare-sync-text').removeClass('nbe-hidden');
                    $('#btn-prepare-sync-spinner').addClass('nbe-hidden');
                    if ($btn) {
                        $btn.prop('disabled', false);
                    }
                    showToast('error', 'Error', 'Error de conexión');
                }
            });
        }

        $('#btn-prepare-sync').on('click', function() {
            startSync($(this));
        });

        $('#nbe-sync-now-from-banner-btn').on('click', function() {
            startSync($(this));
        });

        $('#nbe-btn-cancel-sync').on('click', function() {
            $('#nbe-modal-sync-confirm').addClass('hidden').removeClass('flex');
        });

        $('#nbe-btn-confirm-sync').on('click', function() {
            $('#nbe-modal-sync-confirm').addClass('hidden').removeClass('flex');
            
            totalCreated = 0;
            totalUpdated = 0;
            
            $('#nbe-progress-title').text('Sincronizando...');
            $('#nbe-progress-icon-loading').removeClass('nbe-hidden');
            $('#nbe-progress-icon-success').addClass('nbe-hidden');
            $('#nbe-progress-close-container').addClass('nbe-hidden');
            $('#nbe-progress-bar').css('width', '0%').removeClass('nbe-progress-fill-success');
            $('#nbe-progress-percent').text('0%');
            $('#nbe-progress-text').text('0 / ' + syncData.total_products);
            $('#nbe-stat-created').text('0');
            $('#nbe-stat-updated').text('0');
            $('#nbe-modal-sync-progress').removeClass('hidden').addClass('flex');
            
            processBatch(0);
        });

        function processBatch(offset) {
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'nbe_process_batch',
                    nonce: $('#nbe_sync_nonce').val(),
                    offset: offset,
                    batch_size: 50,
                    sync_description: 'false'
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        totalCreated += data.batch_created || 0;
                        totalUpdated += data.batch_updated || 0;
                        
                        var percent = Math.round((data.processed / data.total) * 100);
                        $('#nbe-progress-bar').css('width', percent + '%');
                        $('#nbe-progress-percent').text(percent + '%');
                        $('#nbe-progress-text').text(data.processed + ' / ' + data.total);
                        $('#nbe-stat-created').text(totalCreated);
                        $('#nbe-stat-updated').text(totalUpdated);
                        
                        if (data.completed) {
                            $('#nbe-progress-title').text('¡Completado!');
                            $('#nbe-progress-icon-loading').addClass('nbe-hidden');
                            $('#nbe-progress-icon-success').removeClass('nbe-hidden');
                            $('#nbe-progress-close-container').removeClass('nbe-hidden');
                            $('#nbe-progress-bar').addClass('nbe-progress-fill-success');
                            
                            if (data.stats) {
                                $('#nbe-stat-created').text(data.stats.created);
                                $('#nbe-stat-updated').text(data.stats.updated);
                            }
                        } else {
                            processBatch(data.processed);
                        }
                    } else {
                        showToast('error', 'Error', response.data.message);
                        $('#nbe-modal-sync-progress').addClass('hidden').removeClass('flex');
                    }
                },
                error: function() {
                    showToast('error', 'Error', 'Error durante la sincronización');
                    $('#nbe-modal-sync-progress').addClass('hidden').removeClass('flex');
                }
            });
        }

        $('#nbe-btn-close-progress').on('click', function() {
            $('#nbe-modal-sync-progress').addClass('hidden').removeClass('flex');
            location.reload();
        });

        // Close modals on backdrop click
        $('.nbe-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).addClass('hidden').removeClass('flex');
            }
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.nbe-modal').addClass('hidden').removeClass('flex');
            }
        });
    });
    </script>
    <?php
}

/**
 * Aviso: escenario de "doble IVA".
 *
 * Si "Sincronizar precios sin IVA" está destildado y la tienda NO está en modo
 * "precios con impuestos incluidos", el precio (que ya trae IVA) recibe IVA otra
 * vez. Sólo se muestra en las páginas del propio plugin. No modifica nada.
 *
 * Ver la sección "Configuración de IVA" del README.
 */
add_action('admin_notices', 'nbe_notice_double_iva');
function nbe_notice_double_iva()
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->id, array('settings_page_nbe', 'tools_page_nbe-logs'), true)) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }

    $no_iva_off = !get_option('nbe_sync_no_iva');
    $prices_excl = function_exists('wc_prices_include_tax') && !wc_prices_include_tax();

    if ($no_iva_off && $prices_excl) {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Conector NBElectric:</strong> tenés “Sincronizar precios sin IVA” <strong>destildado</strong> y WooCommerce está en “precios sin impuestos incluidos”. ';
        echo 'En esa combinación el IVA se aplica dos veces. Recomendado: tildar “Sincronizar precios sin IVA” en la pestaña ';
        echo '<a href="' . esc_url(admin_url('options-general.php?page=nbe')) . '">Sincronización</a>.';
        echo '</p></div>';
    }
}

// ============================================
// AJAX Handlers
// ============================================

add_action('wp_ajax_nbe_test_connection', 'nbe_ajax_test_connection');
function nbe_ajax_test_connection() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
    }
    check_ajax_referer('nbe_settings_nonce', 'nonce');

    $token = nbe_get_token();
    if ($token) {
        wp_send_json_success();
    } else {
        wp_send_json_error('No se pudo autenticar');
    }
}

add_action('wp_ajax_nbe_save_credentials', 'nbe_ajax_save_credentials');
function nbe_ajax_save_credentials() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
    }
    check_ajax_referer('nbe_settings_nonce', 'nonce');

    $user = sanitize_text_field(wp_unslash($_POST['nbe_user'] ?? ''));
    // La contraseña NO se pasa por sanitize_text_field (mutila espacios/símbolos).
    $password = (string) wp_unslash($_POST['nbe_password'] ?? '');
    $prefix = sanitize_text_field(wp_unslash($_POST['nbe_prefix'] ?? ''));

    if ($user === '' || $password === '') {
        wp_send_json_error('Usuario y contraseña son obligatorios');
    }

    update_option('nbe_user', $user);
    update_option('nbe_password', $password);
    update_option('nbe_prefix', $prefix);

    // Limpiar token para forzar re-autenticación
    delete_option('nbe_token');
    delete_option('nbe_token_expiry');

    wp_send_json_success();
}

add_action('wp_ajax_nbe_save_sync_settings', 'nbe_ajax_save_sync_settings');
function nbe_ajax_save_sync_settings() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
    }
    check_ajax_referer('nbe_settings_nonce', 'nonce');

    $old_interval = get_option('nbe_sync_interval');
    $new_interval = absint($_POST['nbe_sync_interval'] ?? 0);
    if ($new_interval < 60) {
        $new_interval = 3600;
    }

    update_option('nbe_sync_interval', $new_interval);
    update_option('nbe_sync_no_iva', empty($_POST['nbe_sync_no_iva']) ? 0 : 1);
    update_option('nbe_sync_usd', empty($_POST['nbe_sync_usd']) ? 0 : 1);
    update_option('nbe_description', sanitize_textarea_field(wp_unslash($_POST['nbe_description'] ?? '')));

    // Actualizar cron si cambió el intervalo
    if ($old_interval != $new_interval) {
        nbe_update_cron_schedule();
    }

    wp_send_json_success();
}

add_action('wp_ajax_nbe_update_connector', 'nbe_update_connector');
function nbe_update_connector()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
    }
    check_ajax_referer('nbe_settings_nonce', 'nonce');

    $zip_url = 'https://github.com/' . NBE_GITHUB_REPO . '/archive/refs/heads/main.zip';
    $upload_dir = wp_upload_dir();
    $zip_file = $upload_dir['path'] . '/woocommerce-nbelectric-main.zip';

    $response = wp_remote_get($zip_url, array('timeout' => 300));
    if (is_wp_error($response)) {
        wp_die('Error de descarga');
    }

    $zip_data = wp_remote_retrieve_body($response);
    if (empty($zip_data)) {
        wp_die('Respuesta vacía');
    }

    if (!file_put_contents($zip_file, $zip_data)) {
        wp_die('Error al guardar');
    }

    if (!class_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    WP_Filesystem();
    $unzip_result = unzip_file($zip_file, WP_PLUGIN_DIR);

    if (is_wp_error($unzip_result)) {
        wp_die('Error al descomprimir');
    }

    unlink($zip_file);
    wp_send_json_success();
}

function get_latest_version_nbe()
{
    $file_url = 'https://raw.githubusercontent.com/' . NBE_GITHUB_REPO . '/main/woocommerce-nbelectric.php';
    $response = wp_remote_get($file_url, array('timeout' => 5));

    if (is_wp_error($response)) {
        return VERSION_NBE;
    }

    $body = wp_remote_retrieve_body($response);
    preg_match('/Version:\s*(\S+)/', $body, $matches);

    return isset($matches[1]) ? trim($matches[1]) : VERSION_NBE;
}
