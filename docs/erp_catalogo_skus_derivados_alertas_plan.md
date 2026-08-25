# ERP Catalogo - SKUs derivados, precios y costos

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-21  
Proyecto canonico: `C:\xampp\htdocs\panel_de_control`  
Estado: plan de arquitectura, sin DDL aplicado

## Objetivo

Preparar Catalogo ERP para manejar SKUs principales y SKUs vendibles derivados sin mezclar responsabilidades de precio, costo, inventario o ventas.

Catalogo define:

- Que SKUs existen.
- De que SKU origen dependen, cuando aplica.
- Que tipo de derivacion tienen.
- Que unidad usan para venderse o controlarse.
- Que factor, precision, incremento minimo y merma aplican.
- Si el SKU queda operativo o requiere completar configuracion.

Catalogo no define:

- Precio final de venta.
- Costo comercial vigente.
- Margen esperado.
- Existencia fisica.
- Aperturas o preparaciones ya ejecutadas en almacen.

## Auditoria de estructura actual

### SKU origen

Tabla principal: `erp_catalogo_skus`.

Campos relevantes ya existentes:

- `id_sku`
- `id_producto_erp`
- `sku`
- `nombre`
- `tipo_inventario`
- `id_unidad_base`
- `factor_unidad_base`
- `estatus`

Uso correcto:

- Representa una unidad vendible o controlable.
- Puede ser un SKU normal, variante, presentacion, granel, paquete o resultado de apertura.
- No debe guardar precio operativo.
- `costo_referencia` debe quedar como dato historico/fallback, no como costo operativo.

### Reglas de inventario y venta fraccionaria

Tabla: `erp_catalogo_sku_reglas_inventario`.

Campos relevantes ya existentes:

- `controla_inventario`
- `permite_venta_fraccionaria`
- `precision_decimal`
- `incremento_minimo_venta`
- `unidad_venta_label`
- campos de recepcion variable configurados en Catalogo

Uso correcto:

- Define como se captura o valida cantidad para el SKU.
- `permite_venta_fraccionaria` significa que el SKU puede vender cantidades decimales; no significa que el SKU sea el unico modo de venta.
- Si un producto se vende cerrado y abierto, deben existir SKUs separados y relacionados.

### Presentaciones

Tabla: `erp_catalogo_sku_presentaciones`.

Campos relevantes:

- `id_sku_base`
- `id_sku_presentacion`
- `factor_salida_base`
- `modo_disponibilidad`
- `consume_stock_base_en`
- `requiere_empaque`
- `merma_porcentaje`
- `estatus`

Uso correcto:

- Modela un SKU vendible derivado desde un SKU base.
- Ejemplos: 500 g, 1 kg, corte estandar de 51 cm, corte de 60 cm.
- No debe capturar precio ni costo manual.
- Rentabilidad debe calcular costo desde el SKU base, factor y merma.

### Apertura de empaques

Tabla: `erp_catalogo_sku_aperturas_empaque`.

Campos relevantes:

- `id_sku_origen`
- `id_sku_destino`
- `factor_conversion`
- `requiere_unidad_fisica`
- `conserva_lote`
- `conserva_caducidad`
- `permite_merma`
- `merma_porcentaje_default`
- `estatus`

Uso correcto:

- Modela la relacion entre SKU cerrado y SKU abierto/granel.
- Ejemplo: pieza cerrada de 20 kg -> SKU granel kg.
- Catalogo solo define la regla.
- Almacen/Inventario ejecutan la apertura real y registran existencia.

### Paquetes y recetas

Tablas existentes:

- `erp_catalogo_sku_paquetes`
- `erp_catalogo_sku_paquete_componentes`
- `erp_catalogo_sku_paquete_grupos`
- `erp_catalogo_sku_paquete_opciones`

Uso correcto:

- Modelan paquete simple, receta fija o paquete configurable.
- Un paquete vendible debe tener su propio SKU.
- Los componentes, grupos y opciones forman la receta.
- Rentabilidad calcula costo por suma de componentes o rango de opciones.

### Variantes

Situacion actual:

- Las variantes viven como SKUs del mismo producto y atributos relacionados.
- No existe una tabla unica de "SKU derivado" para variantes.

Decision:

- Si la variante se compra como SKU propio, su costo viene de proveedor/compra/inventario para ese SKU.
- Si la variante nace fisicamente desde otro SKU, no debe modelarse como variante simple; debe modelarse como presentacion, apertura o transformacion segun el caso.

## Contrato normalizado recomendado

Antes de agregar DDL, conviene crear un resolutor read-only en Catalogo:

`CatalogoErpDatos::resolverContextoSkuVendible($idSku)`

Debe devolver:

- `id_sku`
- `sku`
- `id_producto_erp`
- `tipo_derivacion`: `normal`, `variante`, `granel`, `presentacion`, `apertura_empaque`, `paquete`
- `id_sku_origen`
- `factor_conversion`
- `unidad_base`
- `unidad_venta`
- `merma_porcentaje`
- `permite_venta_fraccionaria`
- `precision_decimal`
- `incremento_minimo_venta`
- `estatus_operativo`
- `es_vendible`
- `requiere_precio_lista`
- `tiene_precio_vigente`
- `requiere_costo`
- `costo_derivable`
- `responsable_precio`: `comercial_listas`
- `responsable_costo`: `rentabilidad`

El `tipo_derivacion` puede inferirse sin nueva columna:

- `paquete`: existe en `erp_catalogo_sku_paquetes.id_sku_paquete`.
- `presentacion`: existe en `erp_catalogo_sku_presentaciones.id_sku_presentacion`.
- `apertura_empaque` o `granel`: existe en `erp_catalogo_sku_aperturas_empaque.id_sku_destino`.
- `variante`: pertenece a producto con variantes y tiene atributos, sin regla de conversion fisica.
- `normal`: no cae en ninguna regla anterior.

## Regla de precios

La fuente operativa de precio es Comercial > Listas de precios:

- `erp_listas_precios`
- `erp_listas_precios_detalle`

Catalogo debe generar pendiente cuando:

- Se crea un SKU vendible.
- Se activa un SKU vendible.
- Se crea o activa una presentacion.
- Se crea o activa una apertura de empaque cuyo destino es vendible.
- Se crea o activa un paquete/receta vendible.
- Se cambia factor, unidad, merma, precision o incremento de un SKU derivado que ya tenia precio.

Catalogo no debe calcular ni guardar el precio final.

## Regla de costos

El responsable de resolver costos es Rentabilidad/Costos.

Resolutor existente:

`RentabilidadErp::resolverCostoVigenteSku($idSku, $contexto)`

Uso esperado:

- Listas consulta este resolutor para mostrar margen al capturar precio.
- Catalogo puede consultar el resolutor solo para informar si el costo es derivable, sin guardar costo.

Reglas por tipo:

| Tipo | Costo esperado |
| --- | --- |
| `normal` | Costo directo por proveedor, compra, inventario o fallback historico. |
| `variante` | Costo propio si se compra como SKU separado; si nace de otro SKU debe modelarse con relacion operativa. |
| `presentacion` | Costo derivado desde SKU base, factor y merma. |
| `apertura_empaque` | Costo estimado desde SKU origen, factor y merma default; costo real cuando Almacen ejecute apertura. |
| `granel` | Costo desde apertura confirmada o desde regla origen si aun no hay movimiento fisico. |
| `paquete` | Suma de componentes o rango de costo segun opciones configurables. |

## Pendientes y alertas

### Pendiente para Comercial/Listas

Tipo sugerido:

`catalogo_sku_vendible_sin_precio_lista`

Se genera cuando un SKU vendible no tiene precio activo vigente en listas.

Responsable:

- Area: `comercial_listas`
- Permiso para ver: `ventas.listas.ver`
- Permiso para resolver: `ventas.listas.editar`
- Ruta sugerida: `/comercial/listas_precios`

Payload minimo:

- `id_sku`
- `sku`
- `id_producto_erp`
- `tipo_derivacion`
- `id_sku_origen`
- `factor_conversion`
- `unidad_base`
- `unidad_venta`
- `merma_porcentaje`
- `precision_decimal`
- `incremento_minimo_venta`
- `evento_origen`

Resolucion:

- Se resuelve cuando existe un detalle activo y vigente en `erp_listas_precios_detalle` para ese SKU.

### Pendiente para Rentabilidad/Costos

Tipo sugerido:

`catalogo_sku_derivado_costo_no_resuelto`

Se genera cuando el SKU es vendible pero `RentabilidadErp::resolverCostoVigenteSku` no puede calcular costo confiable.

Responsable:

- Area: `rentabilidad_costos`
- Permiso pendiente de auditar antes de implementar.
- Ruta sugerida: modulo de Rentabilidad/Costos, no Catalogo.

Payload minimo:

- `id_sku`
- `sku`
- `tipo_derivacion`
- `id_sku_origen`
- `formula_esperada`
- `faltante_detectado`
- `confianza_costo`

Resolucion:

- Se resuelve cuando Rentabilidad puede devolver costo vigente confiable para el SKU.

## Casos operativos

### Costal 20 kg

Catalogo:

- SKU cerrado: pieza cerrada con contenido equivalente de 20 kg.
- SKU granel: kg, venta fraccionaria, precision decimal e incremento minimo.
- Apertura de empaque: SKU cerrado -> SKU granel, factor 20, merma opcional.

Listas:

- Precio del SKU cerrado.
- Precio del SKU granel.

Rentabilidad:

- Costo del granel desde apertura/regla y costo del SKU cerrado.

### Rollo 50 m

Catalogo:

- SKU cerrado: rollo completo como unidad vendible.
- SKU granel/corte: metro o cortes estandar como SKUs derivados.
- Apertura o presentacion segun si se abre el rollo o si se prepara corte fijo.

Listas:

- Precio por rollo, por metro o por corte.

Rentabilidad:

- Costo proporcional con merma si aplica.

### Presentacion 500 g o 1 kg

Catalogo:

- SKU presentacion vendible.
- Relacion en `erp_catalogo_sku_presentaciones`.
- Factor numerico y merma.

Listas:

- Precio por presentacion.

Rentabilidad:

- Costo derivado desde SKU base.

### Paquete/receta

Catalogo:

- SKU paquete vendible.
- Componentes fijos y/o grupos configurables.

Listas:

- Precio del paquete.

Rentabilidad:

- Costo por suma de componentes o rango de seleccion.

## Decision de arquitectura

No conviene agregar precio ni costo en Catalogo para resolver este flujo.

La division robusta es:

- Catalogo: estructura del SKU y derivacion.
- Rentabilidad/Costos: costo vigente, costo derivado, confianza y margen tecnico.
- Comercial/Listas: precio de venta vigente por canal/lista.
- Almacen/Inventario: existencia y conversion fisica.
- POS/Ecommerce: venta segun SKUs vendibles, precio vigente y existencia permitida.

## Orden de implementacion recomendado

1. Crear resolutor read-only de contexto de SKU vendible en Catalogo.
2. Crear auditoria read-only de SKUs vendibles sin precio vigente en Listas.
3. Crear auditoria read-only de SKUs derivados sin costo resoluble por Rentabilidad.
4. Integrar generacion de pendientes al guardar/activar SKU, presentacion, apertura o paquete.
5. Mostrar en Catalogo badges de seguimiento: `Pendiente en Listas` y `Costo no resuelto`.
6. En Listas, consumir el contexto del SKU y el resolutor de costo para prevalidar margen.
7. En Rentabilidad, exponer detalle de formula usada para cada tipo de derivacion.

## Criterio de cierre para Catalogo

Catalogo queda listo cuando:

- Todo SKU vendible puede identificarse como normal, variante, granel, presentacion, apertura o paquete.
- Todo SKU derivado conoce su origen cuando aplica.
- La UI muestra si falta precio en Listas sin pedir captura en Catalogo.
- El sistema puede generar pendiente para Comercial/Listas si falta precio.
- El sistema puede generar pendiente para Rentabilidad/Costos si el costo no es resoluble.
- No se duplican costos ni precios en Catalogo.

## Avance 2026-08-21

Implementado:

- `CatalogoErpDatos::resolverContextoSkuVendible($idSku)`.
- `CatalogoErp/contexto_sku_vendible?id_sku=ID`.
- `storage/uat/uat_catalogo_contexto_sku_vendible_readonly.php`.

Alcance aplicado:

- Solo lectura.
- Sin DDL.
- Sin persistir notificaciones.
- Sin exponer importes de costo desde Catalogo.
- Sin guardar precios ni costos.

Pendiente inmediato:

- Completar validacion desde navegador con sesion activa.
- Crear auditorias por lote para `sin_precio_lista` y `costo_no_resuelto`.
- Despues integrar pendientes persistentes idempotentes.

Evidencia UAT read-only:

- MySQL local consultado por puerto `3406`.
- SKU `PROD0965-1500G`: `tipo_derivacion=presentacion`, origen `PROD0965`, costo resoluble sin exponer monto.
- SKU `INOS-PESS-1500GR`: `tipo_derivacion=presentacion`, `es_vendible=1`, `tiene_precio_vigente=0`, alerta sugerida para Comercial/Listas.

## Avance 2026-08-22 - Contrato de salida hacia Rentabilidad

Proyecto aplicado: `C:\xampp\htdocs\panel_de_control`.

Se valido y amplio `CatalogoErpDatos::resolverContextoSkuVendible($idSku)` como contrato read-only para Rentabilidad.

Campos de salida cubiertos:

- `id_sku_derivado`
- `sku_derivado`
- `id_sku_origen`
- `sku_origen`
- `tipo_derivacion`
- `factor_conversion`
- `unidad_base`
- `unidad_venta`
- `merma_porcentaje`
- `componentes`
- `modo_inventario`
- `estatus_operativo`
- `fecha_actualizacion`
- `advertencias_configuracion`

Notas de compatibilidad:

- El campo `tipo_derivacion` conserva `normal` para compatibilidad con la UI actual. Para el contrato funcional tambien se expone `tipo_derivacion_rentabilidad`, donde `normal` equivale a `sku_normal`.
- `componentes` siempre existe. En SKUs que no son paquete devuelve `componentes_fijos=[]` y `grupos_configurables=[]`.
- `modo_inventario` devuelve una de estas claves: `stock_propio`, `descuenta_origen`, `preparacion_al_momento`, `no_inventariable`.
- `advertencias_configuracion` normaliza faltantes estructurales con claves `CAT-DER-*` para que Rentabilidad no dependa de textos de UI.

Validacion UAT:

- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`: OK.
- `storage\uat\uat_catalogo_contexto_sku_vendible_readonly.php`: OK.
- SKU `PROD0965-1500G`: devuelve `id_sku_derivado`, `modo_inventario=stock_propio`, fecha y advertencias vacias.
- SKU `NUEC-A20K-GRANEL`: devuelve `advertencias_configuracion` con `CAT-DER-001` y `CAT-DER-002` por falta de origen/regla de apertura.
- SKU paquete `PER-05-01`: devuelve `componentes_fijos` con `PERA-5030` y `ATPP087`.

Pendiente no implementado aun:

- Generar pendientes persistentes idempotentes al crear, activar o cambiar SKU derivado vendible. Por ahora el contrato y auditorias son read-only y accionables desde UI.

## Decision 2026-08-23 - Incidencias persistentes para Rentabilidad

Se aclara que los links desde Catalogo hacia Rentabilidad son solo atajos visuales. El flujo robusto debe ser una incidencia persistente e idempotente en `erp_notificaciones` cuando Catalogo cree, active o cambie una receta vendible que afecte costo.

Documento rector de este plan:

```text
docs/erp_catalogo_incidencias_rentabilidad_skus_derivados_plan.md
```

Decision:

- Catalogo genera incidencia `catalogo_sku_derivado_costo_pendiente`.
- Rentabilidad intenta resolver costo automaticamente con `RentabilidadErp::resolverCostoVigenteSku`.
- Si puede resolver, cierra la incidencia y deja trazable formula/fuente/confianza.
- Si no puede resolver, deja bloqueo con responsable claro: Catalogo, Rentabilidad, Compras, Proveedores, Almacen/Tienda o Comercial/Listas.
- Comercial/Listas debe recibir pendiente de precio solo cuando el costo ya sea confiable o cuando se decida permitir precio con advertencia.
