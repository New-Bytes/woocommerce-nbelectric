# Conector NBElectric

Plugin de WordPress que sincroniza los productos del catálogo de NBElectric con tu tienda WooCommerce: precios, stock, imágenes, categorías y descripciones.

Documentación: https://conecta.nb.com.ar/

## Requisitos

- WordPress con WooCommerce activo.
- **FIFU (Featured Image From URL)** para mostrar las imágenes de los productos.
- Usuario y contraseña de NBElectric.

## Instalación

1. Descargá el archivo `.zip` del plugin.
2. En WordPress, andá a `Plugins` > `Añadir nuevo` > `Subir plugin`.
3. Elegí el `.zip`, hacé clic en `Instalar ahora` y activalo.

## Configuración

Andá a `Ajustes` > `Conector NBE` y completá:

- **Usuario** y **Contraseña** de NBElectric.
- **Prefijo SKU**: se agrega al inicio de cada SKU. Por defecto `NBE_`.
- **Descripción corta**: texto que se agrega a todos los productos.

Con el botón **Probar conexión** podés verificar que los datos sean correctos.

## Sincronización

Los productos se sincronizan automáticamente cada cierto tiempo. También podés lanzar una sincronización manual desde la página de ajustes y ver el detalle de cada una en `Herramientas` > `NBElectric Logs`.

## IVA

Cada producto recibe la clase de impuesto que corresponde a su IVA (21% → Estándar, 10,5% → Reducido, 0% → Exento). Configurá esas tasas en `WooCommerce` > `Ajustes` > `Impuestos`.

La opción **"Sincronizar precios sin IVA"** tiene que coincidir con el ajuste de WooCommerce **"Precios introducidos con impuestos"**:

| "Sincronizar sin IVA" | "Precios con impuestos" | Resultado |
|---|---|---|
| Tildado (recomendado) | No | Correcto: WooCommerce suma el IVA. |
| Destildado | Sí | Correcto: el precio ya incluye IVA. |
| Destildado | No | El IVA se cobra dos veces. Evitar. |

Si la configuración es incorrecta, el plugin muestra un aviso.
