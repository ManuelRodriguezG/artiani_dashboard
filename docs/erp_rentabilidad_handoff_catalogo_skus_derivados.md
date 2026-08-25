# ERP Rentabilidad - Handoff desde Catalogo para costos de SKUs derivados

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-22  
Proyecto canonico: `C:\xampp\htdocs\panel_de_control`  
Origen: Catalogo ERP  
Destino: Rentabilidad/Costos  
Estado: instrucciones de continuidad para implementar la parte de Rentabilidad

## Objetivo

Preparar Rentabilidad para calcular y auditar costos de SKUs vendibles derivados usando el contrato estructural que ahora expone Catalogo.

Aplica a:

- SKU normal.
- Granel.
- Presentacion.
- Apertura de empaque.
- Paquete/receta.
- Variante.

No aplica a:

- Precio final de venta.
- Guardado en Listas de precios.
- Movimientos de inventario.
- Ejecucion fisica de apertura o preparacion.

## Fuente de verdad desde Catalogo

Endpoint read-only:

```text
/catalogoerp/contexto_sku_vendible?id_sku=ID
```

Modelo origen:

```text
CatalogoErpDatos::resolverContextoSkuVendible($idSku)
```

Permiso actual:

```text
catalogo.ver
```

Nota para Rentabilidad:

- En backend PHP, si se consume desde modelo, preferir llamar directamente al modelo `CatalogoErpDatos` en vez de hacer HTTP interno.
- En UI, si se abre desde Catalogo, usar `/rentabilidad/skus?sku=SKU&id_sku=ID&origen_costo=sin_costo`.

## Contrato de entrada esperado por Rentabilidad

Campos principales:

- `id_sku_derivado`
- `sku_derivado`
- `id_sku`
- `sku`
- `id_producto_erp`
- `tipo_derivacion`
- `tipo_derivacion_rentabilidad`
- `relacion_operativa`
- `id_sku_origen`
- `sku_origen`
- `factor_conversion`
- `modo_inventario`
- `unidad_base`
- `unidad_venta`
- `merma_porcentaje`
- `permite_venta_fraccionaria`
- `precision_decimal`
- `incremento_minimo_venta`
- `controla_inventario`
- `estatus_operativo`
- `es_vendible`
- `requiere_costo`
- `costo_derivable`
- `costo_resumen`
- `componentes`
- `fecha_actualizacion`
- `advertencias_configuracion`

Tipos normalizados para Rentabilidad:

- `sku_normal`
- `granel`
- `presentacion`
- `apertura_empaque`
- `paquete`
- `variante`

`tipo_derivacion` conserva `normal` por compatibilidad de Catalogo, pero Rentabilidad debe preferir `tipo_derivacion_rentabilidad`.

Modos de inventario:

- `stock_propio`: el SKU tiene existencia propia o se costea como entidad propia.
- `descuenta_origen`: el SKU vendible consume costo desde SKU origen/componentes.
- `preparacion_al_momento`: el costo depende de preparar/armar al vender o antes de vender.
- `no_inventariable`: no debe requerir costo de inventario.

## Reglas por tipo

### sku_normal

Fuente esperada:

- compra;
- proveedor;
- inventario promedio;
- XML;
- fallback historico solo si se autoriza.

Rentabilidad no debe pedir origen si el SKU normal tiene costo directo.

### presentacion

Datos requeridos:

- `id_sku_origen`
- `factor_conversion`
- `merma_porcentaje`
- `modo_inventario`

Formula esperada:

```text
costo_origen normalizado * factor_conversion * (1 + merma)
```

Si `modo_inventario=stock_propio`, puede existir costo real por preparacion/inventario. Si no existe, usar costo derivado desde origen como estimacion tecnica.

### granel

Datos requeridos:

- `id_sku_origen` cuando nace de una apertura de empaque.
- `factor_conversion`.
- unidad base decimal.
- precision e incremento.

Si falta `id_sku_origen`, Rentabilidad debe marcar pendiente estructural y devolver bloqueo accionable para Catalogo.

### apertura_empaque

Datos requeridos:

- SKU cerrado origen.
- SKU destino abierto/granel.
- factor de conversion.
- merma default.
- evidencia real de apertura cuando exista.

Prioridad:

1. Costo real de apertura confirmada por Almacen/Tienda.
2. Costo estimado desde regla de Catalogo y costo del SKU origen.
3. Bloqueo si no hay regla ni costo origen.

### paquete

Datos requeridos:

- `componentes.componentes_fijos`.
- `componentes.grupos_configurables`.
- cantidades y factores por componente/opcion.

Regla:

- Paquete simple: suma de componentes fijos.
- Paquete configurable: calcular rango minimo/maximo o escenarios segun opciones permitidas.
- Si un componente no tiene costo, el paquete debe quedar con advertencia o bloqueo segun severidad.

### variante

Regla:

- Si se compra como SKU propio: costo directo.
- Si nace de otro SKU: Catalogo debe modelarla como presentacion, apertura o transformacion, no como variante simple.

## Advertencias de configuracion desde Catalogo

Rentabilidad debe interpretar `advertencias_configuracion` antes de calcular margen.

Claves actuales:

- `CAT-DER-001`: configuracion derivada incompleta.
- `CAT-DER-002`: SKU derivado sin SKU origen configurado.
- `CAT-DER-003`: factor de conversion invalido.
- `CAT-DER-004`: paquete sin componentes ni grupos activos.
- `CAT-DER-005`: variante detectada con bandera inconsistente.

Regla:

- Si hay `CAT-DER-*`, Rentabilidad puede mostrar costo no resoluble, pero la correccion pertenece a Catalogo cuando el campo faltante sea estructural.
- Si el faltante es evidencia de costo, compra, proveedor, inventario o XML, la correccion pertenece a Compras/Proveedores/Almacen/Rentabilidad.

## Trabajo recomendado en Rentabilidad

1. Agregar un helper interno para consumir el contexto de Catalogo:

```text
RentabilidadErp::resolverContextoCatalogoSkuDerivado($idSku)
```

2. Hacer que `resolverCostoVigenteSku($idSku, $contexto)` use el contexto de Catalogo cuando:

- `tipo=auto`;
- `tipo` no venga definido;
- el SKU tenga `tipo_derivacion_rentabilidad` derivado.

3. Ajustar `auditarPendientesCostoDerivado($filtros)` para:

- incluir `advertencias_configuracion`;
- distinguir pendiente estructural de Catalogo vs pendiente de evidencia de costo;
- agrupar por `tipo_derivacion_rentabilidad`.

4. En UI `/rentabilidad/skus`, cuando llegue `?sku=...&id_sku=...`:

- mostrar el SKU filtrado;
- mostrar bloque "Contexto de Catalogo";
- mostrar formula esperada;
- mostrar si falta estructura de Catalogo o evidencia de costo.

5. En UI `/rentabilidad/calidad`, en "Costos derivados pendientes":

- mostrar columnas: SKU, tipo, modo inventario, origen, factor, faltante, responsable.

6. No guardar costo manual en Catalogo.

## UAT recomendado

### Caso paquete

SKU:

```text
PER-05-01
```

Validar:

- `tipo_derivacion_rentabilidad=paquete`.
- `componentes_fijos` contiene `PERA-5030` y `ATPP087`.
- Rentabilidad intenta resolver costo por componentes.
- Si un componente no tiene costo, mostrar bloqueo por componente, no por paquete completo mal configurado.

### Caso granel incompleto

SKU:

```text
NUEC-A20K-GRANEL
```

Validar:

- `tipo_derivacion_rentabilidad=granel`.
- `advertencias_configuracion` contiene `CAT-DER-001` y `CAT-DER-002`.
- Rentabilidad debe marcar pendiente estructural para Catalogo, no pedir costo manual.

### Caso presentacion

Usar un SKU con `tipo_derivacion_rentabilidad=presentacion`, por ejemplo detectado por:

```text
storage\uat\uat_catalogo_contexto_sku_vendible_readonly.php
```

Validar:

- origen;
- factor;
- merma;
- costo derivado desde origen;
- advertencias si falta costo origen.

## Comandos utiles

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_contexto_sku_vendible_readonly.php --id_sku=1764
C:\xampp\php\php.exe storage\uat\uat_catalogo_contexto_sku_vendible_readonly.php --id_sku=1877
C:\xampp\php\php.exe storage\uat\uat_catalogo_skus_vendibles_pendientes_readonly.php --modo=costo --limite=5
```

## Criterio de cierre en Rentabilidad

Rentabilidad queda lista cuando:

- Puede calcular costo de SKU normal, presentacion, apertura, granel, paquete y variante.
- Usa el contrato de Catalogo para estructura y no vuelve a deducir desde UI.
- Distingue faltante estructural de faltante de evidencia de costo.
- Expone formula, confianza, fuente y siguiente paso.
- No guarda precio final ni modifica Listas.
- No guarda costo manual en Catalogo.

## Plan 2026-08-23 - Incidencias desde Catalogo por costo derivado

Catalogo no debe depender de links manuales para que Rentabilidad atienda costos de presentaciones, aperturas, granel, paquetes o variantes.

Documento de continuidad:

```text
docs/erp_catalogo_incidencias_rentabilidad_skus_derivados_plan.md
```

Rentabilidad debe recibir incidencias persistentes tipo `catalogo_sku_derivado_costo_pendiente`, intentar resolucion automatica con el resolutor de costo vigente y mantener la incidencia como `bloqueada` cuando falte estructura o evidencia. El costo resuelto no modifica precio; si falta precio despues de costo confiable, corresponde pendiente para Comercial/Listas.

## Avance 2026-08-23 - Fase 1 implementada en Catalogo

Proyecto aplicado: `C:\xampp\htdocs\panel_de_control`.

Se implemento la primera fase del plan de incidencias hacia Rentabilidad para SKUs derivados.

Codigo:

- `CatalogoErpDatos::registrarIncidenciaCostoDerivadoSku($db, $idSku, $eventoOrigen, $idUsuario)`.
- `CatalogoErpDatos::cancelarIncidenciasCostoDerivadoSku($db, $idSku, $motivo)`.
- Reutiliza `erp_notificaciones` y `NotificacionesErp::guardarOperativaEnConexion`.
- Tipo de incidencia: `catalogo_sku_derivado_costo_pendiente`.
- Area responsable: `rentabilidad_costos`.
- Permiso de vista sugerido: `rentabilidad.ver`.

Eventos conectados:

- Guardar paquete.
- Guardar grupo de paquete.
- Desactivar grupo de paquete.
- Guardar opcion de paquete configurable.
- Desactivar opcion de paquete configurable.
- Guardar presentacion.
- Guardar apertura de empaque.
- Actualizar SKU, para cubrir activacion/cambio operativo de un SKU derivado.

Reglas aplicadas:

- Solo registra si el SKU es vendible, requiere costo y no es `sku_normal`.
- La huella es estable por SKU/tipo para evitar duplicados.
- El `payload_json` incluye `hash_receta` para detectar cambios de receta sin ensuciar la bandeja.
- Si se desactiva presentacion, apertura o paquete completo, se cancelan incidencias activas del SKU derivado.
- Si no existe `erp_notificaciones`, el guardado de Catalogo no se rompe; devuelve omision en `incidencia_costo_derivado`.
- Catalogo no calcula ni guarda costos ni precios.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\CatalogoErp.php`: OK.
- `storage\uat\uat_catalogo_contexto_sku_vendible_readonly.php --id_sku=1764`: OK.
- `storage\uat\uat_catalogo_skus_vendibles_pendientes_readonly.php --modo=costo --limite=3`: OK.

Pendiente siguiente:

- Probar en UI guardar una presentacion o paquete activo y confirmar que aparece una notificacion en `erp_notificaciones` sin duplicarse al guardar dos veces.
- En Rentabilidad, construir la bandeja/resolucion de incidencias `catalogo_sku_derivado_costo_pendiente`.
