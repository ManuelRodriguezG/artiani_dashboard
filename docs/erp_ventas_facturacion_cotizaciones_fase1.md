# ERP Ventas - Facturación documental y cotizaciones Fase 1

Documentación IA: Codex GPT-5  
Fecha: 2026-10-23
Estado: implementado con esquema fiscal aplicado, captura de cotizaciones, importación documental, PDF Artiani Fiscal y plantillas para Gmail; no implica emisión fiscal ni envío desde el panel.

## Objetivo de esta fase

Concentrar en Ventas una primera consola para:

- consultar cotizaciones comerciales existentes y su vigencia;
- crear cotizaciones comerciales desde Ventas con productos del catálogo o partidas manuales;
- revisar partidas, importes y eventos de una cotización;
- preparar un plan de seguimiento o conversión futura sin cambiar estatus;
- analizar y, si se confirma, conservar un XML CFDI generado por el proceso fiscal externo;
- presentar la factura en una vista legible con sus datos fiscales, importes e items;
- generar una representación imprimible/PDF vectorial con identidad del negocio, sellos y certificados; la vista del navegador conserva el QR SAT y el PDF del servidor muestra la zona de verificación con UUID cuando GD no está habilitado;
- conservar la plantilla principal **Artiani Compacta** y ofrecer una alternativa independiente **Artiani Fiscal**, inspirada en comprobantes fiscales por bloques y usando coral/teal de la identidad ARTIANI;
- acomodar el resumen de totales y certificación al final de la hoja cuando el contenido cabe, dejando que la impresión continúe en otra página cuando la tabla crece;
- consultar expedientes fiscales conservados, conceptos y eventos;
- preparar una plantilla de correo para copiarla en Gmail y adjuntar manualmente el XML/PDF; el panel no envía correos.

La fuente fiscal sigue siendo el flujo externo autorizado por el negocio. El panel no emite CFDI ni determina por sí mismo obligaciones fiscales.

## Archivos y rutas

- `app/controladores/Ventas.php`: vista, consulta de cotizaciones, impresión formal, importación, PDF, plantilla de correo y endpoints de auditoría de esquema.
- `app/modelos/VentasErp.php`: expediente fiscal, PDF Artiani Fiscal vectorial y plantilla de correo de solo lectura.
- `app/modelos/VentasErpEsquema.php`: plan y auditoría del expediente fiscal reservado para una fase posterior.
- `app/vistas/paginas/apps/erp/ventas/facturacion_cotizaciones.php`: consola operativa.
- `app/vistas/paginas/apps/erp/ventas/cotizaciones.php`: consola independiente de captura, identidad visual y seguimiento de cotizaciones.
- `public/assets/js/custom/apps/erp/ventas/facturacion_cotizaciones.js`: consulta, detalle, cotización formal, previsualización XML, guardado, PDF y selección de CFDI.
- `public/assets/js/custom/apps/erp/ventas/cotizaciones.js`: captura amplia, configuración local de identidad y representación imprimible de cotización.
- `app/vistas/includes/header/sidebar.php`: acceso `Ventas y POS > Facturacion y cotizaciones`.

Rutas principales:

- `/ventas/facturacion_cotizaciones`
- `/ventas/cotizaciones`
- `GET /ventas/cotizaciones_comerciales_listar_erp`
- `GET /ventas/cotizacion_comercial_consultar_erp`
- `POST /ventas/cotizacion_comercial_guardar_erp`
- `POST /ventas/cotizacion_comercial_accion_plan_erp`
- `POST /ventas/facturacion_cfdi_parsear_erp`
- `GET /ventas/facturacion_cfdi_listar_erp`
- `GET /ventas/facturacion_cfdi_consultar_erp`
- `POST /ventas/facturacion_cfdi_importar_erp`
- `GET /ventas/facturacion_cfdi_pdf_erp`
- `GET /ventas/facturacion_correo_plantilla_erp`
- `GET /ventas/esquema_auditar_facturacion_ventas`
- `GET /ventas/esquema_plan_facturacion_ventas`

## Límites y seguridad

- Las cotizaciones se consultan desde `EcommerceCatalogoPublico`; no se duplican tablas ni contratos.
- La conversión a pedido/venta continúa siendo manual y requiere revisar cliente, vigencia, precio, disponibilidad y reglas de inventario.
- La captura manual genera folio `COT-YYYYMMDD-######`, conserva snapshot de cliente, partidas, precios, moneda, vigencia y un evento `creada_erp`; no crea cliente CRM, pedido, venta, pago ni inventario.
- La captura de partidas puede tomar productos del catálogo mediante la búsqueda POS de solo lectura o permitir una descripción manual; el backend vuelve a validar cantidades, precios, moneda, contacto y tablas existentes.
- El XML CFDI se valida con el parser seguro existente de Compras: límite de tamaño, bloqueo de `DOCTYPE`/`ENTITY`, parseo sin red externa y lectura de conceptos.
- La previsualización no guarda el XML. La acción `Guardar` exige el esquema completo y `finanzas.operar`; registra encabezado, conceptos, hash, archivo y evento inicial.
- Los XML y PDF conservados se guardan bajo `storage/erp/ventas/fiscales/`, fuera de `public/`; el endpoint PDF valida sesión y permiso antes de entregar el archivo.
- La vista previa, importación, PDF y correo no crean ventas, no cobran y no afectan inventario.
- La plantilla de correo es de solo lectura operativa: se copia en Gmail y el usuario adjunta manualmente el XML y el PDF. No hay SMTP ni envío de correo desde el panel.
- La cotización formal se imprime o se guarda como PDF desde el navegador; es una propuesta comercial y no es CFDI.
- La consola de Cotizaciones separa la captura y la identidad comercial de Facturación documental. El logo y los datos comerciales configurados para cotización se conservan en el navegador actual y no reemplazan el branding fiscal.
- El visualizador y el PDF presentan el sello del CFDI, sello del SAT, UUID, certificados y fecha de timbrado; la vista imprimible del navegador incluye el QR de consulta.
- El visualizador también muestra, cuando el XML los contiene, lugar de expedición, forma y método de pago, uso y régimen fiscal, código postal del receptor, exportación, condiciones de pago y tasas identificadas.
- La identidad visual usa como color principal el coral de la letra A de ARTIANI, con teal para certificación y alto contraste en encabezados y totales.
- El logo de respaldo del visualizador se encuentra en `public/assets/media/logos/artiani-logo.png`; si existe branding configurado se respeta, salvo los logos genéricos del sistema.
- La salida PDF usa `public/assets/media/logos/artiani-logo-pdf.jpg`, derivado del mismo logo para mantener compatibilidad con Dompdf aun cuando GD no esté habilitado.
- El estatus inicial es `pendiente_validacion`/`pendiente`; no se consulta automáticamente al SAT ni se marca conciliado.
- Los endpoints POST usan la validación CSRF transversal de `Core.php`.
- La consulta usa `ventas.ver` o `finanzas.ver`; el análisis de estructura requiere `sistema.soporte`.
- No se crea cliente CRM, venta, pedido, pago, movimiento de caja, movimiento de inventario ni TMS.

## Importes y moneda

La interfaz separa subtotal, descuento, impuestos cuando la fuente lo entrega, total y moneda. Si el XML no trae un campo en el contrato temporal, se muestra como pendiente; no se inventa ni se recalcula como dato fiscal.

## Esquema fiscal aplicado

`VentasErpEsquema::planActualizarFacturacionVentas(true)` creó:

- `erp_ventas_documentos_fiscales`: encabezado, UUID, emisor/receptor, importes, hash y ruta del archivo;
- `erp_ventas_documentos_fiscales_conceptos`: conceptos, claves SAT, importes y resultado de conciliación;
- `erp_ventas_documentos_fiscales_eventos`: importación, validación, conciliación, envío y cambios de estatus.

El esquema reservado podría vincular opcionalmente un CFDI con `erp_ventas` y CRM en una fase posterior. La pantalla actual no vincula documentos, no crea ventas y no inserta CFDI; solo presenta temporalmente el contenido del XML.

La importación ya no está bloqueada por esquema: las tres tablas existen y fueron verificadas con cero registros iniciales. El endpoint continúa sin intentar crear tablas y exige `finanzas.operar`. La generación del PDF y el envío reutilizan ese expediente sin agregar columnas ni DDL.

Respaldo previo y rollback focalizado:

- Respaldo de esquema completo por PDO: `C:\xampp\panel_db_backups\artianicom_sys_panel_20261022_171826_antes_ventas_facturacion_cfdi_pdo_schema_detached.sql`.
- Tamaño: `343247` bytes; `311` tablas.
- SHA-256: `8f9c5a70cd1ddf6b93565cef76ceb1d5f07300119eb85b2e37f028ad282c9aad`.
- Archivo: `C:\xampp\panel_db_backups\artianicom_sys_panel_20261023_011907_antes_ventas_facturacion_cfdi_rollback.sql`.
- Tamaño: `487` bytes.
- SHA-256: `1403754198c5386fbc95b3ab684242f0fdd406fdfe409f95453a522303d37d41`.
- Alcance: confirmó que las tres tablas estaban ausentes y conserva el rollback de esas tablas nuevas.
- Nota: el `mysqldump` remoto se interrumpió por volumen; el respaldo PDO de esquema completo terminó correctamente y el rollback focalizado cubre exclusivamente las tablas nuevas del DDL aditivo.
- Respaldo focalizado previo a habilitar importación/PDF/correo: `C:\xampp\panel_db_backups\productivo_artianicom_sys_20261023_211848_antes_ventas_facturacion_documental_focalizado.sql`.
- Tamaño: `4469` bytes; contiene estructura y registros actuales de las tres tablas fiscales.

Antes de aplicar el esquema se requiere:

1. confirmar proveedor/flujo externo de emisión y qué evidencia genera XML/PDF;
2. definir si se almacenará PDF o solo referencia a la fuente externa;
3. generar respaldo externo en `C:\xampp\panel_db_backups`;
4. autorizar explícitamente el token y la aplicación del DDL;
5. ejecutar auditoría posterior y documentar resultado.

## Correo de facturas

La plantilla usa placeholders para cliente, folio, UUID, moneda, total, empresa y correo de respuesta. La pantalla permite copiar el asunto y el cuerpo para pegarlos en Gmail. El XML y el PDF se adjuntan manualmente en Gmail; el panel no lee configuración SMTP, no envía mensajes y no registra eventos de envío.

## Handoff

Siguiente orden recomendado:

1. Validar la pantalla con una cotización real y un XML no sensible de prueba.
2. Confirmar las decisiones fiscales y de archivo indicadas arriba.
3. Ejecutar una importación controlada de prueba con un XML no sensible y verificar UUID/hash, archivo, conceptos, PDF y eventos.
4. Copiar la plantilla en Gmail, adjuntar XML/PDF y validar el mensaje manualmente.
5. Diseñar la conversión controlada de cotización aceptada a Pedido/Venta sin inventario durante la cotización.
