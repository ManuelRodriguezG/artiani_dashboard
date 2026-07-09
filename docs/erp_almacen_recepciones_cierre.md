# ERP Almacen - Cierre de Recepciones

Documentacion IA: Codex GPT-5  
Fecha de cierre: 2026-06-19  
Estado: modulo Almacen/Recepciones cerrado funcionalmente para pasar a Inventario/Existencias.

## Forma de trabajo aplicada

- Tareas pequenas y verificables.
- UAT documentado por folio.
- Hallazgos con ID `ALM-H###`.
- Respaldos externos antes de escrituras en BD.
- Escrituras reales solo con autorizacion.
- Sin mezclar ERP nuevo con endpoints legacy.
- Pruebas visuales manuales por usuario cuando implicaban navegador/impresion.

## Folios usados

### REC-OC-24 / OC-2026-000024

Uso: UAT base corto de recepcion parcial, recepcion completa, no duplicidad, existencias y notificacion.

Estado final:

- Recepcion: `recibida`.
- Orden: `recibida`.
- SKU principal: `SAL-50L`.
- Cantidad recibida: `5.0000` de `5.0000`.
- Movimientos de recepcion: 2.
- Lotes: 2.
- Existencia disponible total: `5.0000`.
- Notificacion de recepcion pendiente: resuelta.

### REC-OC-20 / OC-2026-000020

Uso: UAT extendido para lote/caducidad, etiquetas, recepcion normal y pruebas controladas.

Estado final al cierre:

- Recepcion: `parcial`.
- Orden: `parcial`.
- Total ordenado: `105.0000`.
- Total recibido: `15.0000`.
- Total pendiente: `90.0000`.
- Partidas recibidas: 4.
- Partidas parciales: 1.
- Partidas pendientes: 8.

Partidas movidas:

- `TP-7838`: recibido `5.0000` de `5.0000`, con lote/caducidad.
- `TP-7840`: recibido `5.0000` de `5.0000`, con lote/caducidad.
- `SCF-800`: recibido `1.0000` de `1.0000`, con etiqueta unitaria.
- `SHF-600`: recibido `3.0000` de `3.0000`, con 3 etiquetas unitarias.
- `SP-2823`: recibido `1.0000` de `5.0000`, recepcion normal sin lote/etiqueta.

Recomendacion: no mover mas `REC-OC-20` sin nueva autorizacion; queda como folio parcial con evidencia rica.

## UAT aprobados

- `UAT-ALM-001`: listado de recepciones.
- `UAT-ALM-002`: carga de detalle de recepcion.
- `UAT-ALM-003`: recepcion parcial actualiza recepcion y orden.
- `UAT-ALM-004`: recepcion completa actualiza recepcion y orden.
- `UAT-ALM-005`: reintento no duplica movimientos.
- `UAT-ALM-006`: reglas de lote/caducidad bloquean faltantes y permiten recepcion correcta.
- `UAT-ALM-007`: existencias/kardex se actualizan correctamente.
- `UAT-ALM-008`: notificacion de recepcion pendiente se resuelve.
- `UAT-ALM-009`: etiqueta de trazabilidad unitaria generada desde recepcion.
- `UAT-ALM-010`: estado de etiqueta `pendiente_impresion -> impresa -> pegada`.
- `UAT-ALM-011`: bloqueo de duplicado/retroceso de etiqueta.
- `UAT-ALM-012`: impresion y pegado de multiples etiquetas.
- `UAT-ALM-013`: recepcion normal sin lote, caducidad ni etiqueta.

## Hallazgos cerrados

- `ALM-H001`: idempotencia/reintento posterior a cierre validado.
- `ALM-H002`: codificacion mixta corregida por bloques con respaldo.
- `ALM-H003`: recepcion pendiente mostraba `completo` antes de guardar; UX corregida.
- `ALM-H004`: reglas de lote/caducidad apagadas; piloto aplicado y probado.
- `ALM-H005`: etiqueta interna separada de serie de fabricante; esquema y flujo aplicados.

## Respaldos externos principales

- `artianilocal_panel_20260618_081947_antes_uat_alm_rec_oc_24.sql`
- `artianilocal_panel_20260618_almacen_codificacion_erp_almacenes.sql`
- `artianilocal_panel_20260618_almacen_codificacion_catalogo_nombres.sql`
- `artianilocal_panel_20260618_almacen_codificacion_catalogo_descripciones.sql`
- `artianilocal_panel_20260618_almacen_normalizacion_almacenes.sql`
- `artianilocal_panel_20260619_almacen_reglas_piloto_rec_oc_20.sql`
- `artianilocal_panel_20260619_antes_etiquetas_series_schema.sql`
- `artianilocal_panel_20260619_antes_uat_alm_009_etiqueta_scf800.sql`
- `artianilocal_panel_20260619_antes_uat_alm_010_etiqueta_impresa_pegada.sql`
- `artianilocal_panel_20260619_antes_uat_alm_011_bloqueo_duplicado_etiqueta.sql`
- `artianilocal_panel_20260619_antes_uat_alm_012_etiquetas_shf600.sql`
- `artianilocal_panel_20260619_antes_uat_alm_013_recepcion_normal_sp2823.sql`

## Entregables tecnicos

### Flujo Recepciones

- Recepciones consultables desde `Almacen > Recepciones`.
- Recepcion parcial y completa por modelo `Almacenes`.
- Validacion de lote/caducidad por regla de catalogo.
- Actualizacion de existencias, lotes, movimientos y orden de compra.
- Resolucion de notificacion de recepcion pendiente.

### Etiquetado

- Pantalla operativa: `Almacen > Etiquetado`.
- Ruta: `/almacen/etiquetado`.
- Listado por estado, almacen y busqueda.
- Impresion individual y por seleccion.
- Etiqueta fisica limpia: producto, SKU, Code128 y codigo interno; no imprime OC ni recepcion.
- Marcado individual y masivo como `impresa`.
- Marcado individual y masivo como `pegada`.
- Auditoria explicita para acciones de etiquetado.

### Datos de etiqueta

Regla:

- Catalogo decide si un SKU genera etiqueta interna.
- Almacen recibe, imprime y pega.
- Inventario guarda la unidad.
- Ventas/Garantias consumiran el codigo despues.

Codigos generados:

- `ART-00001-23-0001`: `SCF-800`, estado `pegada`.
- `ART-00001-24-0001`, `ART-00001-24-0002`, `ART-00001-24-0003`: `SHF-600`, estado `pegada`.

## Archivos clave modificados o creados

- `app/controladores/Almacen.php`
- `app/core/Core.php`
- `app/modelos/Almacenes.php`
- `app/modelos/AlmacenEsquema.php`
- `app/modelos/InventarioErp.php`
- `app/vistas/includes/header/sidebar.php`
- `app/vistas/paginas/apps/erp/almacen/recibir.php`
- `app/vistas/paginas/apps/erp/almacen/etiquetado.php`
- `public/assets/js/custom/apps/erp/almacen/recibir/recibir.js`
- `public/assets/js/custom/apps/erp/almacen/etiquetado/etiquetado.js`
- `docs/erp_almacen_recepciones_tareas_vivas.md`
- `docs/erp_almacen_inventario_auditoria_esquema.md`
- `docs/erp_almacen_reglas_inventario_propuesta.md`
- `docs/erp_etiquetas_series_trazabilidad_diseno.md`
- `docs/erp_etiquetas_series_trazabilidad_schema_propuesta.sql`
- `storage/uat/uat_alm_012_recibir_shf600.php`
- `storage/uat/uat_alm_013_recibir_sp2823_normal.php`

## Validaciones tecnicas recientes

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app\core\Core.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\etiquetado.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\etiquetado\etiquetado.js`: OK.
- `C:\xampp\php\php.exe -l storage\uat\uat_alm_013_recibir_sp2823_normal.php`: OK.

## Pendientes fuera de alcance

- Doble submit simultaneo real a nivel navegador/red, si se requiere prueba de concurrencia mas estricta.
- UAT visual del boton `Marcar pegadas` masivo cuando exista una nueva tanda en `impresa`.
- Activacion masiva de reglas por familia/categoria desde Catalogo.
- Integracion de etiqueta con Ventas.
- Integracion de etiqueta con Garantias/Devoluciones.

## Siguiente modulo recomendado

Siguiente modulo: **Inventario/Existencias**.

Razon:

- Recepciones ya alimentan existencias y movimientos.
- Antes de Ventas/Garantias conviene asegurar consulta de stock, ajustes, traspasos, disponibilidad y kardex.
- Ventas debe consumir stock confiable; no conviene iniciar ventas si Inventario todavia no esta auditado operativamente.

Documento de arranque sugerido:

- `docs/erp_inventario_existencias_arranque.md`
