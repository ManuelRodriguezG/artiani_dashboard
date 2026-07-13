# ERP Ventas - Listas de precios

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-12  
Estado: documento vivo inicial; auditoria sin escritura en BD.

## Proposito

Construir el modulo ERP de Listas de precios como fuente formal para resolver precios de venta desde backend.

El objetivo no es solo capturar precios: el modulo debe permitir que POS, CRM y futuro ecommerce obtengan un precio determinista, auditable y congelable en cada venta.

Regla central:

- POS no decide precios por su cuenta.
- CRM no administra listas; CRM guarda identidad, condiciones y preferencias comerciales del cliente.
- Catalogo no es dueno del precio final; conserva precio provisional/fallback mientras exista el modulo formal.
- Ecommerce futuro debe consumir reglas permitidas para canal ecommerce, no reutilizar listas internas POS sin regla explicita.

## Alcance fase 1

Incluye:

- Listas de precios por SKU/producto.
- Vigencia de encabezado y detalle.
- Canal.
- Sucursal/almacen.
- Prioridad.
- Asignacion directa a cliente CRM.
- Lista default del cliente CRM.
- Snapshot en venta y detalle.
- Auditoria de cambios comerciales.
- UAT por SKU, cliente, lista, sucursal y folio.

No incluye todavia:

- Promociones.
- Cupones.
- Recompensas.
- Precios dinamicos por volumen.
- Ecommerce activo.
- Margen minimo automatico.
- Sincronizacion externa.

## Estado detectado

Auditoria read-only realizada el 2026-07-12 en `C:\xampp\htdocs\panel_de_control`.

Tablas existentes:

| Tabla | Estado | Registros detectados | Nota |
| --- | --- | ---: | --- |
| `erp_listas_precios` | existe | 1 | Lista UAT POS. |
| `erp_listas_precios_detalle` | existe | 1 | SKU `1760` con precio UAT. |
| `erp_clientes_listas_precios` | existe | 1 | Asignacion heredada por `id_cliente`. |
| `erp_catalogo_sku_precios` | existe | 1752 | Fallback/provisional desde Catalogo. |
| `crm_clientes_maestro` | existe | 157 | CRM canonico activo. |
| `crm_clientes_condiciones` | no existe | 0 | Planeado para condiciones comerciales CRM. |
| `erp_ventas` | existe | 17 | Tiene columnas de cliente/lista/snapshot. |
| `erp_ventas_detalle` | existe | 18 | Ya guarda lista/origen en ventas POS recientes. |

Semilla UAT detectada:

- Lista `LP-UAT-POS`, `id_lista_precio=1`.
- Canal `pos`.
- Almacen `5`.
- Prioridad `10`.
- Detalle SKU `1760`, producto `1016`, precio `295.000000`, moneda `MXN`.
- Asignacion cliente-lista con `id_cliente=1`.

## Codigo existente relacionado

Backend:

- `app/modelos/VentasErp.php`
  - `clientePrecioDryRun()`.
  - `resolverPrecioSkuDryRun()`.
  - `checadorPrecioPosReadOnly()`.
  - `confirmarVentaPosReal()`.
- `app/modelos/VentasErpEsquema.php`
  - Define `erp_listas_precios`.
  - Define `erp_listas_precios_detalle`.
  - Define `erp_clientes_listas_precios`.
  - Agrega columnas de snapshot de cliente CRM en ventas.
- `app/modelos/CatalogoErpEsquema.php`
  - Define `erp_catalogo_sku_precios` como precio provisional/fallback.
- `app/modelos/ClientesCrmEsquema.php`
  - Define `crm_clientes_maestro.id_lista_precio_default`.
  - Planea `crm_clientes_condiciones.id_lista_precio_default`.

Frontend:

- `public/assets/js/custom/apps/erp/ventas/pos.js`
  - Manda carrito a backend para resolver cliente/lista/precio.
  - Muestra origen de precio y lista.
- `public/assets/js/custom/apps/erp/ventas/checador_precios.js`
  - Muestra lista y origen del precio en consulta read-only.

Pantallas:

- No existe todavia vista CRUD de Listas de precios.
- No existe JS dedicado para administrar listas.
- No existe ruta dedicada de modulo en `Ventas.php`.

## Hallazgos importantes

### 1. La base tecnica ya existe, pero no el modulo operativo

Las tablas basicas existen y POS ya puede recibir `id_lista_precio`, `lista_precio_snapshot` y `regla_precio_origen`.

Falta construir el modulo real para administrarlas con permisos, auditoria, validaciones, UAT y contrato CRM/POS.

### 2. El contrato cliente-lista esta desalineado con CRM canonico

CRM canonico usa `crm_clientes_maestro.id_cliente_crm`.

La tabla `erp_clientes_listas_precios` usa `id_cliente`.

El POS envia el cliente CRM como `id_cliente` hacia algunos endpoints por compatibilidad, pero `resolverClienteDryRun()` devuelve:

- `id_cliente_crm` con el cliente real.
- `id_cliente=0`.

Resultado observado:

- Dry-run con cliente CRM `1`, SKU `1760`, almacen `5`, canal `pos` resolvio la lista UAT.
- Pero el origen fue `lista_canal_sucursal`, no `lista_cliente`.

Esto significa que la prioridad de lista directa por cliente no esta garantizada con el contrato CRM actual.

### 3. La venta guarda snapshot por detalle, pero el encabezado queda incompleto

Ventas POS recientes guardan en `erp_ventas_detalle`:

- `id_lista_precio`.
- `lista_precio_snapshot`.
- `regla_precio_origen`.

Pero `erp_ventas.id_lista_precio` y `erp_ventas.lista_precio_nombre_snapshot` aparecen `NULL` en ventas revisadas.

Decision recomendada:

- El snapshot obligatorio debe vivir en detalle.
- El encabezado puede guardar lista dominante/resumen solo si todas las partidas usan la misma lista o como snapshot comercial del documento.
- No depender del encabezado para auditoria fina de precios por partida.

### 4. El resolutor actual no cubre toda la prioridad v1

Hoy resuelve:

1. Lista por cliente solo si encuentra relacion en `erp_clientes_listas_precios` con `id_cliente`.
2. Lista por canal/sucursal.
3. Fallback a `catalogo_general`.

Falta:

- Lista asignada por `id_cliente_crm`.
- Lista default del cliente CRM.
- Lista por segmento CRM.
- Lista general ERP separada del fallback de Catalogo.
- Orden determinista por especificidad.
- Validacion de moneda.
- Validacion de detalles duplicados/traslapados.

## Prioridad de precio v1

El orden recomendado para fase 1 es:

1. Excepcion comercial autorizada en POS.
2. Lista asignada directamente al cliente CRM.
3. Lista default del cliente CRM o condicion comercial CRM.
4. Lista por segmento CRM, si la tabla existe.
5. Lista por canal/sucursal.
6. Lista general ERP.
7. Fallback temporal a `erp_catalogo_sku_precios` con lista `general`.

Notas:

- Promociones quedan fuera de fase 1.
- Descuentos manuales no son listas de precios; son excepciones comerciales con permiso, motivo y auditoria.
- Si dos reglas empatan, gana la mas especifica y luego la menor prioridad numerica.

## Modelo recomendado

### Mantener

- `erp_listas_precios`
- `erp_listas_precios_detalle`
- `erp_catalogo_sku_precios` como fallback provisional

### Ajustar o extender

`erp_clientes_listas_precios` debe corregirse para CRM canonico.

Opcion recomendada:

- Agregar `id_cliente_crm BIGINT NULL`.
- Mantener `id_cliente` temporal por compatibilidad legacy.
- Cambiar el resolutor para preferir `id_cliente_crm`.
- Documentar que nuevas asignaciones deben usar `id_cliente_crm`.

Ventaja:

- Menos ruptura inmediata.
- Permite migrar la semilla UAT y cualquier dato anterior.
- Evita crear una tabla paralela que duplique responsabilidad.

Alternativa:

- Crear `crm_clientes_listas_precios` como relacion comercial CRM.

No recomendada para fase 1 porque mueve la administracion de listas hacia CRM y puede mezclar responsabilidades.

### Futuro documentado

- `erp_segmentos_listas_precios`
- Debe apuntar a `id_segmento_crm`, no a segmentos legacy.
- Implementar cuando CRM Segmentos este operativo y probado.

## Granel, presentaciones y unidad completa

Fecha: 2026-07-12  
Estado: regla de diseno a contemplar antes del CRUD definitivo.

El modulo de Listas de precios debe soportar productos que se compran o reciben como unidad completa, pero se venden por unidad base fraccionaria, presentacion estandar o unidad completa.

Caso operativo:

- Compra/Recepcion recibe un rollo completo de poster/fondo para acuario, por ejemplo 50 m o 100 m.
- Inventario conserva saldo real en unidad base `M`.
- POS puede vender:
  - una medida libre, por ejemplo `0.55 m`;
  - una presentacion estandar, por ejemplo `51 cm = $35`;
  - un rollo completo, por ejemplo `50 m`.

Regla central:

- POS no debe asumir precios por aproximacion visual o por una presentacion parecida.
- Backend debe resolver el precio segun modo de venta, lista vigente y politica de medida/corte.
- La venta debe guardar snapshot de precio, modo, cantidad solicitada, cantidad cobrada y cantidad descontada de inventario.

### Responsabilidades por modulo

Catalogo:

- Define unidad base, por ejemplo `M`, `KG`, `L`.
- Define si el SKU permite venta fraccionaria.
- Define precision decimal.
- Define incremento minimo operativo.
- Define presentaciones disponibles cuando aplique.
- No decide precio final.

Compras/Recepcion:

- Compra y recibe rollo completo.
- Convierte a unidad base de inventario.
- No necesita conocer cada medida vendible.

Inventario:

- Guarda saldo real en metros, kilos, litros u otra unidad base.
- Descuenta la medida real vendida o equivalencia base de la presentacion.
- Si una presentacion esta precortada fisicamente, puede controlarse como presentacion preparada con stock propio.
- Si se corta al momento, descuenta del SKU base.

Listas de precios:

- Deben soportar precio por unidad base/fraccionaria, por ejemplo `$X / m`.
- Deben soportar precio por presentacion estandar, por ejemplo `51 cm = $35`.
- Deben soportar precio por paquete, rollo completo o unidad cerrada.
- Deben guardar vigencia, canal, sucursal, cliente, segmento y prioridad cuando aplique.
- Deben devolver origen y snapshot del precio usado.

POS:

- Debe permitir elegir modo de venta: medida libre, presentacion estandar o rollo completo.
- Debe mandar modo, cantidad y presentacion seleccionada al backend.
- No inventa precio ni redondeo.
- Si backend aplica redondeo por politica de corte, POS debe mostrar cantidad solicitada y cantidad cobrada.

### Politicas para medidas no exactas

Si existe una presentacion estandar `51 cm = $35`, pero el cliente pide `55 cm`, el sistema no debe cobrar automaticamente `$35`.

Debe existir una politica clara por SKU/lista/canal/sucursal cuando aplique:

- Recomendado: cobrar por medida real, por ejemplo `0.55 m * precio por metro`.
- Alternativa: cobrar por incremento minimo configurable, por ejemplo redondear `0.55 m` a `0.60 m` si el corte es cada `10 cm`.
- Alternativa para productos muy estandarizados: cobrar la siguiente presentacion definida, por ejemplo `60 cm`.

El resolutor debe devolver al POS:

- modo de precio aplicado;
- cantidad solicitada;
- cantidad cobrada;
- unidad de cobro;
- precio unitario;
- precio final;
- politica de redondeo/incremento aplicada;
- equivalencia base para inventario;
- lista/origen/snapshot.

### Implicacion de esquema futura

Antes del CRUD real conviene evaluar si `erp_listas_precios_detalle` requiere columnas adicionales o una tabla hija para alcance por presentacion:

- `modo_precio`: `unidad_base`, `presentacion`, `paquete`, `rollo_completo`.
- `id_presentacion` o referencia equivalente definida en Catalogo.
- `cantidad_presentacion`.
- `unidad_presentacion`.
- `cantidad_base_equivalente`.
- `politica_redondeo`: `sin_redondeo`, `incremento_minimo`, `siguiente_presentacion`.
- `incremento_minimo_cobro`.
- `unidad_cobro`.

Decision pendiente:

- Confirmar si las presentaciones se modelaran primero en Catalogo como entidad formal reutilizable por Inventario/POS, o si Listas de precios tendra una estructura temporal de presentaciones comerciales. La recomendacion ERP es que Catalogo defina la presentacion y Listas solo le asigne precio.

## Validaciones que debe tener el modulo

Encabezado de lista:

- Codigo obligatorio y unico.
- Nombre obligatorio.
- Canal opcional pero controlado: `pos`, `pedido_tienda`, `ecommerce`, `mayoreo`, etc.
- Almacen opcional.
- Prioridad obligatoria.
- Estatus controlado: `borrador`, `activa`, `pausada`, `cancelada`.
- Vigencia de inicio menor o igual a vigencia final.

Detalle:

- Debe tener `id_sku` o `id_producto_erp`, preferir SKU.
- Precio mayor a cero.
- Moneda obligatoria.
- No permitir duplicados activos equivalentes dentro de la misma lista.
- Detectar traslapes de vigencia por lista/SKU/moneda.
- Si se captura producto en vez de SKU, mostrar impacto por variantes.

Asignacion a cliente:

- Usar `id_cliente_crm`.
- Cliente debe estar activo.
- Lista debe estar activa o en borrador segun flujo.
- Vigencia obligatoria o default razonable.
- No permitir mas de una asignacion activa equivalente con misma prioridad y vigencia traslapada.

## Permisos sugeridos

Crear permisos finos antes del CRUD real:

- `ventas.listas.ver`
- `ventas.listas.crear`
- `ventas.listas.editar`
- `ventas.listas.activar`
- `ventas.listas.pausar`
- `ventas.listas.cancelar`
- `ventas.listas.asignar_cliente`
- `ventas.listas.auditoria`

Permisos existentes relacionados:

- `ventas.precio_manual`
- `ventas.descuento_partida`
- `ventas.descuento_general`
- `ventas.autorizar_excepcion_comercial`

Regla:

- Administrar una lista no debe equivaler a autorizar excepciones manuales en POS.
- Autorizar descuentos no debe permitir editar listas base.

## Auditoria recomendada

Debe registrarse evento explicito para:

- Crear lista.
- Editar encabezado.
- Agregar detalle.
- Cambiar precio.
- Pausar/cancelar/reactivar lista.
- Cambiar vigencia.
- Asignar lista a cliente CRM.
- Cancelar asignacion.
- Importar carga masiva futura.

Snapshot minimo del evento:

- Tabla afectada.
- ID afectado.
- Valor anterior.
- Valor nuevo.
- Usuario.
- Fecha.
- Motivo cuando aplique.

## Plan de implementacion sugerido

### Etapa 0 - Documentacion y contrato

Estado: iniciado con este documento.

Objetivo:

- Dejar claro que Listas de precios pertenece a Ventas/Comercial.
- CRM provee cliente y condiciones; POS consume precio resuelto.
- Catalogo solo mantiene fallback provisional.

### Etapa 1 - Resolver contrato CRM-listas

Objetivo:

- Ajustar esquema planeado para `id_cliente_crm`.
- Auditar columnas reales.
- Preparar DDL no ejecutado.
- Cambiar resolutor para preferir CRM canonico.

Criterio de salida:

- Cliente CRM `1` con lista asignada debe resolver `regla_precio_origen=lista_cliente`.
- Sin cliente debe resolver `lista_canal_sucursal` o `lista_general_erp`.

### Etapa 2 - Resolutor determinista

Objetivo:

- Reescribir/ajustar `resolverPrecioSkuDryRun()` para prioridad v1.
- Devolver razon auditable: regla, lista, prioridad, canal, almacen, vigencia, fuente.
- Separar `lista_general_erp` de `catalogo_general`.

Criterio de salida:

- Misma entrada produce siempre misma lista.
- La respuesta explica por que gano esa lista.

### Etapa 3 - Endpoints read-only y dry-run del modulo

Objetivo:

- Listar listas.
- Consultar detalle.
- Simular alta/edicion.
- Simular asignacion a cliente.
- Detectar traslapes.

Sin escritura real todavia.

### Etapa 4 - UI operativa

Objetivo:

- Pantalla `ERP > Ventas > Listas de precios`.
- Vista de listas.
- Detalle por SKU.
- Asignaciones a cliente CRM.
- Validacion visual de vigencias y conflictos.

Regla UX:

- Debe ser pantalla operativa, no landing.
- Mostrar claramente canal, almacen, vigencia, prioridad y estatus.

### Etapa 5 - Escritura real autorizada

Objetivo:

- Guardar listas y detalles con permisos.
- Registrar auditoria.
- Baja logica, nunca borrado fisico.

Requisito:

- Respaldo externo.
- Token de autorizacion explicito.
- UAT documentado.

### Etapa 6 - UAT POS/CRM

Casos minimos:

| Caso | Entrada | Esperado |
| --- | --- | --- |
| Lista canal/sucursal | SKU `1760`, almacen `5`, sin cliente | `lista_canal_sucursal`. |
| Lista cliente CRM | SKU `1760`, cliente CRM con lista asignada | `lista_cliente`. |
| Lista default CRM | Cliente con `id_lista_precio_default` | `lista_cliente_default`. |
| Fallback catalogo | SKU sin lista ERP activa | `catalogo_general`. |
| Snapshot venta | Venta real POS | Detalle guarda lista/origen/precio. |
| Cambio posterior | Editar precio de lista despues de venta | Venta pasada conserva snapshot. |

## UAT inicial recomendado

No ejecutar escrituras sin autorizacion.

Preparar primero scripts/read-only que validen:

- Estado de tablas.
- Columnas de cliente CRM.
- Conteo de listas activas.
- Detalles duplicados.
- Vigencias traslapadas.
- Resolucion de SKU `1760` sin cliente.
- Resolucion de SKU `1760` con cliente CRM `1`.
- Snapshot de ultimas ventas POS.

## Riesgos abiertos

- La tabla `erp_clientes_listas_precios` puede estar usando IDs compatibles por casualidad con CRM UAT, pero el contrato no es limpio.
- Si se construye CRUD antes de corregir `id_cliente_crm`, se consolida deuda tecnica.
- Si el encabezado de venta se usa para auditoria de precios, puede faltar informacion cuando hay listas mixtas por partida.
- `erp_catalogo_sku_precios` sigue siendo necesario como fallback, pero no debe confundirse con lista comercial formal.
- Ecommerce debe esperar contrato de canal antes de consumir estas listas.

## Siguiente paso recomendado

Primero hacer una tarea tecnica pequena:

1. Preparar ajuste de esquema para `erp_clientes_listas_precios.id_cliente_crm` sin ejecutarlo.
2. Ajustar el resolutor para leer `id_cliente_crm`, `id_lista_precio_default` y lista canal/sucursal con prioridad clara.
3. Crear prueba read-only/dry-run que demuestre:
   - cliente CRM gana sobre canal/sucursal;
   - canal/sucursal gana sobre general;
   - catalogo general queda solo como fallback.

Despues de eso conviene construir el CRUD.

## Handoff / continuidad

Fecha: 2026-07-12

- Contexto actual: el proyecto activo es `C:\xampp\htdocs\panel_de_control`; no trabajar en `C:\xampp\htdocs\panel`.
- Decision: Listas de precios sera modulo de Ventas/Comercial y fuente formal de precio base aplicable.
- Decision: CRM no administra listas, pero puede guardar lista default/condiciones del cliente.
- Decision: POS solo consume precio resuelto por backend y guarda snapshot.
- Hallazgo critico: asignacion cliente-lista actual usa `id_cliente`, pero CRM canonico usa `id_cliente_crm`.
- Hallazgo critico: dry-run con cliente CRM `1` y SKU `1760` devuelve `lista_canal_sucursal`, no `lista_cliente`.
- Pendiente inmediato: corregir contrato CRM-listas y resolutor antes del CRUD.
- Impacta a: POS, CRM, Catalogo, futuro ecommerce, reportes de ventas y auditoria comercial.

## Avance tecnico 2026-07-12

Primera iteracion implementada sin escribir BD:

- `VentasErp::resolverPrecioSkuDryRun()` ahora reconoce `id_cliente_crm`.
- Mientras no exista la columna `erp_clientes_listas_precios.id_cliente_crm`, el resolutor conserva compatibilidad con la semilla UAT guardada en `id_cliente`.
- Cuando exista `id_cliente_crm`, el resolutor preferira esa columna y solo usara `id_cliente` como compatibilidad si `id_cliente_crm` esta vacio.
- El origen de precio ya separa:
  - `lista_cliente`;
  - `lista_cliente_default`;
  - `lista_canal_sucursal`;
  - `lista_general_erp`;
  - `catalogo_general`.
- `VentasErpEsquema` ya prepara, sin ejecutar, el ajuste CRM de `erp_clientes_listas_precios`:
  - agregar `id_cliente_crm BIGINT NULL`;
  - hacer `id_cliente INT NULL`;
  - agregar indice `idx_cliente_lista_cliente_crm`.
- `Ventas.php` expone endpoints formales:
  - `/ventas/esquema_auditar_listas_precios_crm`;
  - `/ventas/esquema_actualizar_listas_precios_crm`.
- La ejecucion real del DDL queda bloqueada por respaldo externo y token `VENTAS_LISTAS_PRECIOS_CRM_DDL`.
- Se creo UAT read-only:
  - `storage/uat/uat_ventas_listas_precios_resolutor_readonly.php`.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l app\controladores\Ventas.php
C:\xampp\php\php.exe -l app\modelos\VentasErp.php
C:\xampp\php\php.exe -l app\modelos\VentasErpEsquema.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_resolutor_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_resolutor_readonly.php
```

Resultado UAT:

- `ok=true`.
- Sin cliente CRM: SKU `1760`, almacen `5`, canal `pos` resuelve `lista_canal_sucursal`.
- Con cliente CRM `1`: SKU `1760`, almacen `5`, canal `pos` resuelve `lista_cliente`.
- Auditoria confirma que en BD actual todavia falta `erp_clientes_listas_precios.id_cliente_crm` e indice `idx_cliente_lista_cliente_crm`.
- El plan DDL se genero sin ejecutar.

Siguiente paso recomendado:

1. Revisar/aprobar el DDL CRM de listas con respaldo externo cuando el dueno lo autorice.
2. Preparar CRUD read-only del modulo Listas de precios: listado, detalle, asignaciones y detector de conflictos.
3. Antes de permitir escritura real, crear permisos finos y auditoria comercial.

## Avance tecnico 2026-07-12 - modulo read-only

Se preparo la primera pantalla operativa sin escritura:

- Ruta de vista: `/ventas/listas_precios`.
- Vista: `app/vistas/paginas/apps/erp/ventas/listas_precios.php`.
- JS: `public/assets/js/custom/apps/erp/ventas/listas_precios.js`.
- Modelo: `app/modelos/ListasPreciosErp.php`.

Endpoints read-only:

- `/ventas/listas_precios_resumen_erp`
- `/ventas/listas_precios_listar_erp`
- `/ventas/listas_precios_consultar_erp?id_lista_precio=...`
- `/ventas/listas_precios_conflictos_erp`

La pantalla muestra:

- KPIs de listas activas, total, detalles activos y asignaciones.
- Filtros por texto, estatus, canal y almacen.
- Tabla de listas con canal, almacen, prioridad, conteos y rango de precio.
- Detalle de lista con SKUs/productos y asignaciones CRM.
- Panel de conflictos.

Validaciones ejecutadas:

```text
C:\xampp\php\php.exe -l app\modelos\ListasPreciosErp.php
C:\xampp\php\php.exe -l app\controladores\Ventas.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios.php
node --check public\assets\js\custom\apps\erp\ventas\listas_precios.js
```

Validacion CLI read-only del modelo:

- `resumenReadOnly()` respondio `listas_activas=1`, `detalles_activos=1`, `asignaciones_activas=1`.
- Detecto `1` conflicto esperado: falta `id_cliente_crm` en `erp_clientes_listas_precios`.
- `consultarReadOnly(1)` respondio lista `LP-UAT-POS`, `1` detalle y `1` asignacion.

Siguiente paso:

- Crear validadores dry-run para alta/edicion de lista y detalle, todavia sin guardar.
- Definir permisos finos `ventas.listas.*`.
- Despues preparar auditoria comercial antes de escritura real.

## Avance tecnico 2026-07-12 - validadores dry-run

Se agregaron validadores sin escritura para preparar el CRUD real:

- `/ventas/listas_precios_lista_dryrun_erp`
- `/ventas/listas_precios_detalle_dryrun_erp`
- `/ventas/listas_precios_asignacion_dryrun_erp`

La pantalla `/ventas/listas_precios` ahora incluye pestañas de validacion:

- Lista: codigo, nombre, canal, almacen, prioridad y estatus.
- Detalle: lista, SKU/producto, precio, moneda y estatus.
- Cliente CRM: lista, cliente, prioridad y estatus.

Reglas ya validadas:

- Codigo obligatorio y unico.
- Canal acotado a fase 1.
- Vigencias coherentes cuando se capturen.
- Precio mayor a cero.
- SKU/producto existente y activo.
- Duplicado por SKU dentro de la misma lista bloqueado aunque el detalle existente tambien tenga producto.
- Cliente CRM existente y activo.
- Asignacion duplicada cliente/lista bloqueada.
- Si falta `id_cliente_crm` en `erp_clientes_listas_precios`, se muestra aviso y no se considera lista para asignacion real.

Validaciones ejecutadas:

```text
C:\xampp\php\php.exe -l app\modelos\ListasPreciosErp.php
C:\xampp\php\php.exe -l app\controladores\Ventas.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios.php
node --check public\assets\js\custom\apps\erp\ventas\listas_precios.js
```

Pruebas CLI read-only:

- Lista nueva `LP-NUEVA-UAT`: valida `puede_guardar=true`.
- Detalle duplicado SKU `1760` en lista `1`: bloquea duplicado.
- Asignacion cliente CRM `1` a lista `1`: bloquea duplicado y avisa que falta columna `id_cliente_crm`.

Siguiente paso recomendado:

1. Definir permisos finos `ventas.listas.*` en Seguridad.
2. Agregar auditoria comercial para futuros guardados.
3. Preparar guardado real de lista/detalle/asignacion, bloqueado por permisos y sin activar en UI productiva hasta autorizacion.

## Avance tecnico 2026-07-12 - permisos y auditoria comercial preparada

Se agregaron al catalogo base de Seguridad los permisos finos:

- `ventas.listas.ver`
- `ventas.listas.crear`
- `ventas.listas.editar`
- `ventas.listas.activar`
- `ventas.listas.pausar`
- `ventas.listas.cancelar`
- `ventas.listas.asignar_cliente`
- `ventas.listas.auditoria`

Asignacion base propuesta por rol:

- `direccion` y `administrador_erp`: gestion completa de listas.
- `ventas`, `crm`, `ecommerce`, `solo_lectura`: consulta de listas.
- `finanzas_contabilidad` y `auditor`: consulta y auditoria.

Nota operativa: estos permisos quedan definidos en `SeguridadEsquema`, pero no se ejecutaron semillas ni cambios en BD. Las rutas del modulo read-only siguen usando temporalmente `ventas.ver` para no bloquear la pantalla antes de aplicar la semilla de seguridad con respaldo.

Se preparo auditoria comercial profunda con la tabla planificada `erp_listas_precios_eventos`.

Campos principales:

- Entidad afectada: lista, detalle o asignacion cliente-lista.
- Accion y tipo de evento.
- Resultado, resumen y motivo.
- Snapshot `datos_antes` y `datos_despues`.
- Usuario, IP, user agent y fecha.

Endpoints preparados:

- `/ventas/esquema_auditar_auditoria_listas_precios`
- `/ventas/esquema_actualizar_auditoria_listas_precios`

Guardrail:

- El endpoint de actualizacion solo ejecuta DDL si se envia `ejecutar=1`, permiso `sistema.soporte`, respaldo externo valido y token `VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL`.
- No se ejecuto DDL.
- No se modificaron listas, precios, clientes ni ventas.

Siguiente paso recomendado:

1. Validar sintaxis de archivos tocados.
2. Generar dry-run de plan de seguridad y auditoria de esquema para confirmar SQL esperado sin ejecutar.
3. Preparar metodos reales de guardado en `ListasPreciosErp` con transaccion, permisos finos y registro en `erp_listas_precios_eventos`, pero mantener UI productiva sin escritura hasta autorizar DDL/semillas.

## Avance tecnico 2026-07-12 - guardado real preparado y cerrado

Se prepararon metodos reales en `ListasPreciosErp`, pero quedan bloqueados por guardrails:

- `listaGuardarAutorizado($datos, $idUsuario)`
- `detalleGuardarAutorizado($datos, $idUsuario)`
- `asignacionClienteGuardarAutorizado($datos, $idUsuario)`

Comportamiento:

- Reutilizan los dry-run existentes antes de abrir transaccion.
- Usan transaccion `beginTransaction/commit/rollBack`.
- Guardan snapshot `datos_antes` y `datos_despues` en `erp_listas_precios_eventos`.
- Bloquean guardado si no existe la tabla de auditoria `erp_listas_precios_eventos`.
- La asignacion cliente/lista bloquea si no existe `id_cliente_crm`, para evitar contrato ambiguo con CRM.
- No recalculan ventas pasadas; el snapshot POS sigue siendo la fuente historica.

Endpoints reales preparados en controlador:

- `/ventas/listas_precios_lista_guardar_erp`
- `/ventas/listas_precios_detalle_guardar_erp`
- `/ventas/listas_precios_asignacion_guardar_erp`

Guardrails de controlador:

- Encabezado:
  - crear requiere `ventas.listas.crear`;
  - editar requiere `ventas.listas.editar`;
  - activar requiere tambien `ventas.listas.activar`;
  - pausar requiere tambien `ventas.listas.pausar`;
  - cancelar requiere tambien `ventas.listas.cancelar`.
- Detalle requiere `ventas.listas.editar`.
- Asignacion cliente CRM requiere `ventas.listas.asignar_cliente`.
- Todos requieren token `VENTAS_LISTAS_PRECIOS_GUARDAR_UAT`; el respaldo externo queda reservado para DDL/cambios de esquema.

Validaciones ejecutadas:

```text
C:\xampp\php\php.exe -l app\modelos\ListasPreciosErp.php
C:\xampp\php\php.exe -l app\controladores\Ventas.php
```

Prueba segura de bloqueo:

- Se llamo `listaGuardarAutorizado()` con datos validos desde CLI.
- Resultado esperado: `error=true`, `tipo=warning`, mensaje `Falta auditoria comercial de listas; no se permite guardar sin trazabilidad`.
- No se escribio BD.

Siguiente paso recomendado:

1. Aplicar en ambiente autorizado, con respaldo, el DDL de auditoria `erp_listas_precios_eventos`.
2. Aplicar en ambiente autorizado la semilla de permisos `ventas.listas.*`.
3. Cambiar rutas read-only de `ventas.ver` a `ventas.listas.ver` cuando los permisos ya existan en BD.
4. Conectar botones de guardado en UI solo para UAT controlado.

## Avance tecnico 2026-07-12 - UI UAT de guardado conectada

La pantalla `/ventas/listas_precios` ya permite pasar de validacion dry-run a intento de guardado UAT, pero con candados visibles y backend:

- Se agregaron campos UAT:
  - respaldo externo;
  - token;
  - motivo.
- Se agregaron botones:
  - Guardar lista;
  - Guardar detalle;
  - Guardar cliente/lista.
- Los botones llaman a:
  - `/ventas/listas_precios_lista_guardar_erp`;
  - `/ventas/listas_precios_detalle_guardar_erp`;
  - `/ventas/listas_precios_asignacion_guardar_erp`.

Guardrails vigentes:

- Si falta token `VENTAS_LISTAS_PRECIOS_GUARDAR_UAT`, backend rechaza.
- Si faltan permisos finos sembrados, backend rechaza.
- Si falta `erp_listas_precios_eventos`, el modelo rechaza antes de escribir.
- Si falta `id_cliente_crm`, la asignacion cliente/lista rechaza antes de escribir.
- Los guardados normales de listas no requieren respaldo de BD; deben quedar auditados en eventos.

Validaciones ejecutadas:

```text
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios.php
node --check public\assets\js\custom\apps\erp\ventas\listas_precios.js
```

No se ejecuto guardado real desde UI ni se escribio BD.

Siguiente paso recomendado:

1. Preparar una solicitud de autorizacion concreta para ejecutar DDL de auditoria y CRM/listas.
2. Preparar una prueba UAT de escritura real con una lista nueva en borrador, no activa.
3. Despues de confirmar escritura/auditoria, activar UAT para SKU `1760` y validar POS.

## Avance tecnico 2026-07-12 - preflight y solicitud de autorizacion

Se agrego preflight read-only:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_preflight_readonly.php
```

Archivo:

- `storage/uat/uat_ventas_listas_precios_preflight_readonly.php`

Valida sin escribir:

- permisos `ventas.listas.*` esperados;
- roles con permisos de listas;
- auditoria CRM/listas;
- plan DDL CRM/listas sin ejecutar;
- auditoria de eventos `erp_listas_precios_eventos`;
- plan DDL de eventos sin ejecutar;
- existencia de vista y JS;
- resumen read-only del modulo.

Se agrego solicitud/runbook de autorizacion:

- `docs/erp_ventas_listas_precios_solicitud_autorizacion.md`

Incluye:

- alcance autorizado propuesto;
- fuera de alcance;
- orden de ejecucion;
- tokens requeridos;
- prueba UAT inicial en borrador;
- UAT posterior con SKU `1760`;
- reversa operativa;
- criterio de aceptacion.

Se agrego script CLI de aplicacion autorizada:

- `storage/uat/uat_ventas_listas_precios_schema_apply_authorized.php`

Guardrails:

- bloqueado por defecto;
- requiere `--autorizar_crm=VENTAS_LISTAS_PRECIOS_CRM_DDL`;
- requiere `--autorizar_auditoria=VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL`;
- puede generar respaldo externo automaticamente con `--generar_respaldo=1`;
- permite `--directorio_respaldo=RUTA_EXTERNA` para elegir carpeta fuera del proyecto;
- tambien permite `--respaldo=REFERENCIA_RESPALDO` si ya existe un respaldo externo;
- audita antes/despues de CRM/listas y eventos.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_schema_apply_authorized.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_schema_apply_authorized.php
```

Resultado esperado sin tokens:

- `ok=false`;
- `modo=bloqueado`;
- no se aplico DDL.

Siguiente paso recomendado:

1. Ejecutar preflight read-only.
2. Si no hay bloqueos, generar respaldo externo automaticamente al aplicar DDL autorizado.
3. Despues aplicar semillas de permisos y pasar rutas read-only a `ventas.listas.ver`.

## Decision operativa 2026-07-12 - respaldo externo reservado para DDL

Regla actualizada por el dueno:

- El agente puede generar el respaldo externo.
- El respaldo debe guardarse fuera del proyecto.
- El respaldo se hace cuando se modifica el esquema de la base de datos.
- No se debe pedir respaldo de BD para CRUD normal de listas, detalles o asignaciones.

Aplicacion en Listas de precios:

- `uat_ventas_listas_precios_schema_apply_authorized.php` genera respaldo de esquema con `--generar_respaldo=1` antes de aplicar DDL.
- La ruta default queda fuera del proyecto: `Documents\RespaldosBD\panel`.
- La UI UAT de guardado de listas ya no pide respaldo; solo token, motivo/referencia operativa, permisos y auditoria.
- Los endpoints de DDL siguen bloqueados por autorizacion y respaldo; el camino recomendado para aplicar DDL es el script CLI autorizado con respaldo automatico.

## Avance tecnico 2026-07-12 - permisos separados de DDL

Se agregaron scripts UAT para permisos de Listas de precios:

- `storage/uat/uat_ventas_listas_precios_permisos_readonly.php`
- `storage/uat/uat_ventas_listas_precios_permisos_apply_authorized.php`

Regla:

- Sembrar permisos no modifica esquema.
- No requiere respaldo externo.
- Siembra bloqueada por token `VENTAS_LISTAS_PRECIOS_PERMISOS` e `id_usuario`.
- Registra auditoria en `sys_auditoria_eventos` cuando la tabla esta disponible.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_permisos_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_permisos_apply_authorized.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_permisos_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_permisos_apply_authorized.php
```

Resultado:

- Read-only detecto los 8 permisos en codigo.
- BD actual todavia no tiene los 8 permisos `ventas.listas.*`.
- Apply sin token quedo bloqueado.
- No se escribio BD.

Siguiente paso recomendado:

1. Autorizar/aplicar DDL de CRM/listas y auditoria con respaldo automatico.
2. Autorizar/aplicar permisos con `VENTAS_LISTAS_PRECIOS_PERMISOS`.
3. Repetir preflight.
4. Cambiar rutas read-only a `ventas.listas.ver`.

## Avance tecnico 2026-07-12 - guia operativa en UI

Se agrego a `/ventas/listas_precios` una guia breve de responsabilidades para evitar que el precio vuelva a mezclarse con Catalogo:

- Catalogo conserva identidad del SKU, unidad, marca, categoria y precio provisional solo como fallback.
- Listas de precios es la fuente comercial formal por SKU/producto, canal, cliente CRM, almacen, prioridad y vigencia.
- Costos y rentabilidad validan margen/utilidad, pero no sustituyen la lista comercial.
- POS y Checador deben consumir el precio resuelto por backend; no deben decidir precio final en frontend.

Decision:

- El precio que una persona debe mantener para venta pertenece a Ventas/Comercial, no a Catalogo.
- El precio de Catalogo queda como dato transitorio/provisional para no romper busquedas o flujos existentes mientras Listas de precios madura.
- El modulo de Catalogo debe mostrar alertas de completitud comercial, pero no obligar al capturista de producto a conocer costo ni precio final.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios.php
```

Siguiente paso recomendado:

1. Mantener sin aplicar DDL de Listas hasta autorizacion explicita.
2. Preparar, cuando corresponda, la autorizacion para DDL CRM/listas y auditoria comercial.

Nota de cruce con Catalogo:

- `CatalogoErpDatos::auditarCalidadCatalogo()` ya muestra el problema como `SKU sin precio provisional`.
- `productos.js` ya muestra `Venta sin precio prov.` y aclara que el precio final vive despues en Listas de precios/canal.
- No se requiere cambio adicional en Catalogo para este punto.

## Avance tecnico 2026-07-12 - UAT guardado de lista borrador preparado

Se agregaron scripts para el primer guardado real controlado de Listas de precios:

- `storage/uat/uat_ventas_listas_precios_guardado_borrador_readonly.php`
- `storage/uat/uat_ventas_listas_precios_guardado_borrador_apply_authorized.php`

Reglas:

- El preflight es read-only y no escribe BD.
- El apply queda bloqueado por defecto.
- Para escribir requiere token `VENTAS_LISTAS_PRECIOS_GUARDAR_UAT` e `id_usuario`.
- No requiere respaldo externo porque guardar una lista en borrador es CRUD normal.
- Antes de escribir valida que exista `erp_listas_precios_eventos`.
- Antes de escribir valida que exista `ventas.listas.crear` en BD.
- La falta de `id_cliente_crm` queda como aviso para asignaciones CRM; no bloquea guardar solo el encabezado de lista.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_guardado_borrador_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_guardado_borrador_apply_authorized.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_guardado_borrador_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_guardado_borrador_apply_authorized.php
```

Resultado:

- Sintaxis correcta en ambos scripts.
- Apply sin token quedo bloqueado y no escribio BD.
- Read-only valido que `LP-UAT-BORRADOR-01` puede guardarse por reglas de negocio.
- Bloqueo actual: falta `erp_listas_precios_eventos`.
- Aviso actual: falta `id_cliente_crm`, relevante para asignaciones CRM pero no para encabezado borrador.

Siguiente paso recomendado:

1. Validar estos scripts sin token.
2. Aplicar DDL y permisos solo con autorizacion.
3. Ejecutar el guardado UAT en borrador.
4. Despues preparar UAT de detalle SKU `1760`.

## Avance tecnico 2026-07-12 - UAT detalle SKU preparado

Se agregaron scripts para validar y aplicar un precio UAT por SKU dentro de una lista existente:

- `storage/uat/uat_ventas_listas_precios_detalle_sku_readonly.php`
- `storage/uat/uat_ventas_listas_precios_detalle_sku_apply_authorized.php`

Valores default:

- Lista por codigo: `LP-UAT-BORRADOR-01`
- SKU: `1760`
- Precio: `315.00`
- Moneda: `MXN`
- Estatus: `activo`

Reglas:

- El preflight es read-only.
- El apply queda bloqueado por defecto.
- Para escribir requiere token `VENTAS_LISTAS_PRECIOS_GUARDAR_UAT` e `id_usuario`.
- No requiere respaldo externo porque es CRUD normal.
- Antes de escribir valida que exista la lista objetivo.
- Antes de escribir valida `erp_listas_precios_eventos`.
- Antes de escribir valida permiso `ventas.listas.editar` en BD.
- Reutiliza `detalleDryRun()` para impedir precio invalido o duplicado.

Siguiente paso recomendado:

1. Validar sintaxis y bloqueo sin token.
2. Despues de crear `LP-UAT-BORRADOR-01`, ejecutar preflight de detalle.
3. Aplicar detalle autorizado.
4. Prevalidar resolutor POS antes de activar la lista.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_detalle_sku_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_detalle_sku_apply_authorized.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_detalle_sku_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_detalle_sku_apply_authorized.php
```

Resultado:

- Sintaxis correcta en ambos scripts.
- Apply sin token quedo bloqueado y no escribio BD.
- Read-only bloqueo porque aun no existe `LP-UAT-BORRADOR-01`.
- Read-only bloqueo porque falta `erp_listas_precios_eventos`.
- El SKU `1760`, precio `315.00` y moneda `MXN` quedaron como entrada UAT propuesta.

## Avance tecnico 2026-07-12 - UAT asignacion cliente CRM preparado

Se agregaron scripts para validar y aplicar una asignacion directa cliente CRM-lista:

- `storage/uat/uat_ventas_listas_precios_asignacion_cliente_readonly.php`
- `storage/uat/uat_ventas_listas_precios_asignacion_cliente_apply_authorized.php`

Valores default:

- Lista por codigo: `LP-UAT-BORRADOR-01`
- Cliente CRM: `1`
- Prioridad: `1`
- Estatus: `activo`

Reglas:

- El preflight es read-only.
- El apply queda bloqueado por defecto.
- Para escribir requiere token `VENTAS_LISTAS_PRECIOS_GUARDAR_UAT` e `id_usuario`.
- No requiere respaldo externo porque es CRUD normal.
- Antes de escribir valida que exista la lista objetivo.
- Antes de escribir valida `id_cliente_crm` en `erp_clientes_listas_precios`.
- Antes de escribir valida `erp_listas_precios_eventos`.
- Antes de escribir valida permiso `ventas.listas.asignar_cliente` en BD.
- Reutiliza `asignacionClienteDryRun()` para impedir cliente/lista invalida o duplicada.

Siguiente paso recomendado:

1. Validar sintaxis y bloqueo sin token.
2. Aplicar DDL CRM/listas y auditoria solo con autorizacion y respaldo externo.
3. Sembrar permisos.
4. Crear lista borrador, detalle SKU y asignacion cliente.
5. Ejecutar resolutor POS con cliente CRM para comprobar `lista_cliente`.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_asignacion_cliente_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_asignacion_cliente_apply_authorized.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_asignacion_cliente_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_asignacion_cliente_apply_authorized.php
```

Resultado:

- Sintaxis correcta en ambos scripts.
- Apply sin token quedo bloqueado y no escribio BD.
- Read-only bloqueo porque aun no existe `LP-UAT-BORRADOR-01`.
- Read-only bloqueo porque falta `id_cliente_crm` en `erp_clientes_listas_precios`.
- Read-only bloqueo porque falta `erp_listas_precios_eventos`.
- Cliente CRM `1` quedo como entrada UAT propuesta.

## Avance tecnico 2026-07-12 - UAT activacion de lista preparado

Se agregaron scripts para activar una lista UAT solo despues de tener detalle activo:

- `storage/uat/uat_ventas_listas_precios_activar_lista_readonly.php`
- `storage/uat/uat_ventas_listas_precios_activar_lista_apply_authorized.php`

Reglas:

- El preflight es read-only.
- El apply queda bloqueado por defecto.
- Para escribir requiere token `VENTAS_LISTAS_PRECIOS_GUARDAR_UAT` e `id_usuario`.
- No requiere respaldo externo porque es cambio comercial, no DDL.
- Antes de activar valida que exista la lista objetivo.
- Antes de activar valida que exista al menos un detalle activo.
- Antes de activar valida `erp_listas_precios_eventos`.
- Antes de activar valida permisos `ventas.listas.editar` y `ventas.listas.activar`.
- Reutiliza `listaGuardarAutorizado()` para dejar evento comercial.

Siguiente paso recomendado:

1. Validar sintaxis y bloqueo sin token.
2. Despues de crear lista/detalle, ejecutar preflight de activacion.
3. Activar lista UAT.
4. Ejecutar resolutor POS read-only antes de venta real.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_activar_lista_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_activar_lista_apply_authorized.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_activar_lista_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_activar_lista_apply_authorized.php
```

Resultado:

- Sintaxis correcta en ambos scripts.
- Apply sin token quedo bloqueado y no escribio BD.
- Read-only bloqueo porque aun no existe `LP-UAT-BORRADOR-01`.
- Read-only bloqueo porque no hay detalles activos.
- Read-only bloqueo porque falta `erp_listas_precios_eventos`.

## Avance tecnico 2026-07-12 - UAT resolutor post-aplicacion preparado

Se agrego verificador final read-only:

- `storage/uat/uat_ventas_listas_precios_resolutor_post_uat_readonly.php`

Valida:

- Lista UAT existe y esta activa.
- Detalle activo del SKU `1760` existe con precio esperado.
- Asignacion activa cliente CRM/lista existe.
- El resolutor backend con cliente CRM devuelve:
  - `regla_precio_origen=lista_cliente`;
  - `id_lista_precio` de la lista UAT;
  - precio esperado;
  - `lista_precio_snapshot`.

Regla:

- No crea venta real.
- No crea snapshot real.
- No escribe BD.
- Sirve como ultimo semaforo antes de autorizar venta UAT POS.

Siguiente paso recomendado:

1. Validar sintaxis.
2. Ejecutar el read-only ahora para confirmar bloqueos esperados.
3. Despues de aplicar DDL/permisos/CRUD, repetirlo hasta `ok=true`.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_resolutor_post_uat_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_resolutor_post_uat_readonly.php
```

Resultado:

- Sintaxis correcta.
- No escribio BD.
- El resolutor actual sigue funcionando con la semilla existente `Lista UAT POS` y precio `295`.
- El verificador post-UAT bloqueo correctamente porque aun no existe la lista objetivo `LP-UAT-BORRADOR-01`.
- Tambien bloqueo porque falta detalle/asignacion de esa lista y no coincide el precio esperado `315.00`.

## Avance tecnico 2026-07-12 - preflight maestro UAT

Se agrego:

- `storage/uat/uat_ventas_listas_precios_flujo_completo_readonly.php`

Objetivo:

- Consolidar en una sola salida el estado completo del flujo UAT:
  - DDL CRM/listas;
  - DDL auditoria eventos;
  - permisos;
  - lista UAT;
  - detalle SKU;
  - asignacion cliente;
  - activacion;
  - resolutor POS.

Tambien se corrigio el verificador post-UAT para leer `precio_aplicado`, que es el campo real devuelto por el dry-run POS, dejando `precio_unitario` solo como compatibilidad.

Siguiente paso recomendado:

1. Validar sintaxis del preflight maestro.
2. Ejecutarlo para confirmar bloqueos actuales.
3. Usarlo como semaforo unico despues de cada paso autorizado.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_flujo_completo_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_resolutor_post_uat_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_flujo_completo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_resolutor_post_uat_readonly.php
```

Resultado:

- Sintaxis correcta.
- No escribio BD.
- El preflight maestro reporta pendientes las 8 etapas:
  - DDL CRM/listas;
  - DDL auditoria eventos;
  - permisos;
  - lista UAT;
  - detalle SKU;
  - asignacion cliente;
  - activacion;
  - resolutor cliente.
- El resolutor actual sigue devolviendo la semilla existente `Lista UAT POS` con precio `295`.
- El nuevo precio UAT esperado `315.00` queda reservado para cuando se cree y active `LP-UAT-BORRADOR-01`.

## Avance tecnico 2026-07-12 - verificador snapshot venta UAT

Se agrego:

- `storage/uat/uat_ventas_listas_precios_snapshot_venta_readonly.php`

Objetivo:

- Validar una venta POS UAT ya emitida por `folio`.
- Confirmar que el snapshot de precio historico quedo en `erp_ventas_detalle`:
  - `id_lista_precio`;
  - `lista_precio_snapshot`;
  - `regla_precio_origen`;
  - `precio_base`;
  - `precio_aplicado`.
- Comparar contra la lista actual para confirmar que una venta pasada no depende de recalcular la lista.

Regla:

- El script exige `--folio=...`.
- No modifica ventas, listas, inventario ni pagos.
- El detalle de venta es la fuente historica fina; el encabezado puede quedar incompleto si hay listas mixtas.

Siguiente paso recomendado:

1. Validar sintaxis.
2. Ejecutarlo sin folio para confirmar bloqueo seguro.
3. Usarlo despues de la venta POS UAT real.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_snapshot_venta_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_snapshot_venta_readonly.php
```

Resultado:

- Sintaxis correcta.
- Sin `--folio`, bloquea con mensaje claro.
- No escribio BD.
- Queda listo para ejecutarse despues de la venta POS UAT real.

## Avance tecnico 2026-07-12 - cambio posterior de precio

Se agregaron scripts para cerrar la prueba de inmutabilidad historica:

- `storage/uat/uat_ventas_listas_precios_cambio_posterior_readonly.php`
- `storage/uat/uat_ventas_listas_precios_cambio_posterior_apply_authorized.php`

Tambien se extendio:

- `storage/uat/uat_ventas_listas_precios_snapshot_venta_readonly.php`

Nuevo parametro:

- `--precio_lista_actual_esperado=325.00`

Flujo:

1. Emitir venta UAT con precio historico `315.00`.
2. Validar snapshot de venta.
3. Cambiar precio actual de la lista a `325.00`.
4. Revalidar la misma venta:
   - venta debe conservar `precio_aplicado=315.00`;
   - lista actual puede tener `325.00`.

Regla:

- Cambiar una lista afecta resoluciones futuras.
- No debe alterar ventas pasadas.
- El cambio posterior es CRUD comercial auditado, no DDL; no requiere respaldo externo.

Siguiente paso recomendado:

1. Validar sintaxis.
2. Ejecutar apply sin token para confirmar bloqueo.
3. Ejecutar read-only para confirmar bloqueos actuales.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_cambio_posterior_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_cambio_posterior_apply_authorized.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_snapshot_venta_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_cambio_posterior_apply_authorized.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_cambio_posterior_readonly.php
```

Resultado:

- Sintaxis correcta.
- Apply sin token quedo bloqueado y no escribio BD.
- Read-only bloqueo porque aun no existe `LP-UAT-BORRADOR-01`.
- Read-only bloqueo porque aun no existe detalle activo del SKU `1760`.
- Read-only bloqueo porque falta `erp_listas_precios_eventos`.
- Quedo preparado el cambio posterior de `315.00` a `325.00` para validar snapshot historico.

## Avance tecnico 2026-07-13 - lectura de auditoria comercial

Se agrego lectura formal de eventos:

- Modelo: `ListasPreciosErp::auditoriaReadOnly()`
- Endpoint: `/ventas/listas_precios_auditoria_erp`
- UAT: `storage/uat/uat_ventas_listas_precios_auditoria_eventos_readonly.php`

Valida:

- Eventos por lista.
- Accion filtrada cuando aplique.
- `datos_antes`.
- `datos_despues`.
- Usuario.
- Motivo.

Regla:

- Es read-only.
- Si `erp_listas_precios_eventos` no existe, devuelve pendiente de esquema.
- El endpoint queda temporalmente protegido por `ventas.ver`; al sembrar permisos se debe cambiar a `ventas.listas.auditoria`.

Siguiente paso recomendado:

1. Validar sintaxis.
2. Ejecutar UAT read-only de auditoria para confirmar bloqueo actual.
3. Despues de operaciones UAT, usarlo para comprobar trazabilidad.

Validacion ejecutada:

```text
C:\xampp\php\php.exe -l app\modelos\ListasPreciosErp.php
C:\xampp\php\php.exe -l app\controladores\Ventas.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_listas_precios_auditoria_eventos_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_auditoria_eventos_readonly.php
```

Resultado:

- Sintaxis correcta.
- No escribio BD.
- UAT read-only bloqueo porque falta `erp_listas_precios_eventos`.
- UAT read-only bloqueo porque aun no existe `LP-UAT-BORRADOR-01`.
- Queda listo para comprobar eventos despues de DDL y operaciones UAT.

## Decision de arquitectura 2026-07-13 - ubicacion del modulo

Listas de precios queda ubicado conceptualmente en `ERP > Comercial > Listas de precios`.

Razon:

- No es una pantalla propia de POS; POS solo consume el precio ya resuelto por backend.
- No es solo Catalogo; Catalogo define identidad, unidad, presentaciones y atributos del producto, pero no decide el precio final.
- No es solo Ventas; Ventas registra la operacion y guarda snapshot, pero no debe gobernar politicas comerciales globales.
- Comercial concentra precio base, vigencia, prioridad, canal, cliente, segmento, sucursal/almacen y excepciones controladas.

Implementacion inicial:

- Ruta canonica de UI: `/comercial/listas_precios`.
- Controlador de organizacion: `Comercial::listas_precios()`.
- Vista reutilizada: `apps/erp/ventas/listas_precios`.
- Endpoints tecnicos existentes permanecen temporalmente en `/ventas/listas_precios_*` para no romper POS, Checador ni UAT.

Regla operativa:

- `ERP > Comercial` gobierna precios.
- `ERP > Ventas y POS` vende y muestra el precio aplicado.
- `CRM` es modulo hermano del ERP; desde CRM se asignan condiciones/listas a clientes.
- `ERP > Catalogo` define productos, unidades, presentaciones y datos base.
- `Ecommerce` es modulo hermano y debera consumir listas permitidas para canal ecommerce, nunca listas POS internas sin regla explicita.

Decision de navegacion:

- El sidebar usa menus principales por modulo de sistema: `ERP`, `CRM`, `Ecommerce` y `Administracion`.
- Cada modulo principal tiene icono propio en el mismo estilo visual de los menus existentes.
- Dentro de `ERP` viven submodulos operativos como acordeones: Catalogo, Comercial, Ventas y POS, Compras, Proveedores, Almacen, Inventario y Postventa.
- `Listas de precios` queda en `ERP > Comercial` porque es politica comercial base, no una funcion exclusiva de POS.

Siguiente ajuste recomendado:

1. Mantener compatibilidad con endpoints actuales.
2. Crear permisos finos `ventas.listas.ver`, `ventas.listas.editar`, `ventas.listas.auditoria` o renombrarlos a `comercial.listas.*` antes de abrir CRUD completo.
3. Cuando el CRUD este estable, evaluar mover endpoints de `/ventas/listas_precios_*` a `/comercial/listas_precios_*` con aliases temporales.

## Avance tecnico 2026-07-13 - endpoints canonicos Comercial

Se agregaron aliases canonicos en `Comercial`:

- `/comercial/listas_precios_resumen_erp`
- `/comercial/listas_precios_listar_erp`
- `/comercial/listas_precios_consultar_erp`
- `/comercial/listas_precios_conflictos_erp`
- `/comercial/listas_precios_auditoria_erp`
- `/comercial/listas_precios_lista_dryrun_erp`
- `/comercial/listas_precios_detalle_dryrun_erp`
- `/comercial/listas_precios_asignacion_dryrun_erp`
- `/comercial/listas_precios_lista_guardar_erp`
- `/comercial/listas_precios_detalle_guardar_erp`
- `/comercial/listas_precios_asignacion_guardar_erp`
- `/comercial/esquema_auditar_listas_precios_crm`
- `/comercial/esquema_actualizar_listas_precios_crm`
- `/comercial/esquema_auditar_auditoria_listas_precios`
- `/comercial/esquema_actualizar_auditoria_listas_precios`

Regla:

- La UI canonica `ERP > Comercial > Listas de precios` consume `/comercial/listas_precios_*`.
- Los endpoints viejos `/ventas/listas_precios_*` se conservan como compatibilidad temporal para no romper UAT ni enlaces existentes.
- Los guardados siguen bloqueados por permisos finos, token UAT y auditoria comercial.
- Los endpoints web de DDL en Comercial requieren `sistema.soporte`, token especifico y respaldo externo valido.
- No se escribio BD ni se ejecuto DDL.

Validacion ejecutada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_flujo_completo_readonly.php
```

Resultado:

- Preflight maestro sigue bloqueado por falta de DDL CRM/listas, auditoria de eventos, permisos sembrados y datos UAT.
- El resolutor actual sigue devolviendo la semilla previa `Lista UAT POS` con precio `295`.
- El precio UAT nuevo `315.00` queda pendiente hasta crear y activar `LP-UAT-BORRADOR-01`.

## Avance tecnico 2026-07-13 - DDL, permisos y UAT aplicados

Se ejecuto DDL autorizado con respaldo externo automatico.

Respaldo generado fuera del proyecto:

```text
C:\Users\aleja\Documents\RespaldosBD\panel\panel_artianilocal_20260712_213505_antes_ventas_listas_precios_schema.sql
```

Cambios de esquema aplicados:

- `erp_clientes_listas_precios.id_cliente_crm`.
- `erp_clientes_listas_precios.id_cliente` quedo nullable para convivencia CRM.
- Indice `idx_cliente_lista_cliente_crm`.
- Tabla `erp_listas_precios_eventos`.
- Indices de auditoria de eventos comerciales.

Permisos sembrados:

- `ventas.listas.ver`
- `ventas.listas.crear`
- `ventas.listas.editar`
- `ventas.listas.activar`
- `ventas.listas.pausar`
- `ventas.listas.cancelar`
- `ventas.listas.asignar_cliente`
- `ventas.listas.auditoria`

Datos UAT creados:

- Lista: `LP-UAT-BORRADOR-01`, `id_lista_precio=2`, canal `pos`, almacen `5`, estatus `activa`.
- Detalle: SKU `1760`, precio `315.00 MXN`, estatus `activo`.
- Asignacion: cliente CRM `1`, prioridad `1`, estatus `activo`.

Auditoria comercial:

- `crear_lista`
- `crear_detalle`
- `crear_asignacion_cliente`
- `editar_lista` para activacion

Hallazgo y correccion:

- El resolutor seguia ganando con la lista legacy `LP-UAT-POS` (`id_lista_precio=1`, precio `295`) porque trataba `id_cliente=1` como compatibilidad CRM con la misma prioridad que `id_cliente_crm=1`.
- Se ajusto `VentasErp::resolverPrecioSkuDryRun()` para ordenar primero la coincidencia CRM canonica `id_cliente_crm`, despues compatibilidad legacy `id_cliente`.
- El preflight maestro quedo `ok=true`.

Validacion final:

- Con cliente CRM `1`, SKU `1760`, almacen `5`, canal `pos`:
  - `id_lista_precio=2`;
  - `precio_aplicado=315`;
  - `regla_precio_origen=lista_cliente`;
  - `lista_precio_snapshot=Lista UAT borrador`.
- Sin cliente, sigue ganando la lista canal/sucursal previa `LP-UAT-POS` con precio `295`, como comparativo de prioridad.

Siguiente paso:

1. Ejecutar venta POS UAT real solo con autorizacion operativa.
2. Validar snapshot en `erp_ventas_detalle` con `storage/uat/uat_ventas_listas_precios_snapshot_venta_readonly.php --folio=...`.
3. Probar cambio posterior de precio y confirmar que la venta pasada conserva snapshot historico.
