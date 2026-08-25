# ERP Catalogo - Plan de incidencias hacia Rentabilidad por SKUs derivados

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-23  
Proyecto canonico: `C:\xampp\htdocs\panel_de_control`  
Modulo origen: Catalogo ERP  
Modulo responsable: Rentabilidad/Costos  
Estado: plan de implementacion; sin DDL aplicado en esta tarea

## Objetivo

Cambiar el flujo actual de atajos visuales hacia Rentabilidad por un flujo operativo real basado en incidencias/notificaciones persistentes.

Cuando Catalogo cree, active o cambie una receta vendible que pueda afectar costo, el ERP debe registrar una incidencia para Rentabilidad. Rentabilidad debe intentar resolver el costo con su resolutor y dejar trazable si lo resolvio automaticamente o si queda bloqueado por informacion faltante.

Este plan aplica a:

- Presentaciones vendibles.
- Aperturas de empaque.
- Granel derivado.
- Paquetes/recetas.
- Variantes vendibles derivadas.
- Cualquier SKU derivado que requiera costo calculable para margen.

No aplica a:

- Capturar precio de venta.
- Guardar costos manuales en Catalogo.
- Modificar Listas de precios.
- Ejecutar movimientos de inventario.

## Decision de arquitectura

El link desde Catalogo hacia Rentabilidad puede quedarse como atajo secundario, pero no debe ser el flujo principal.

Flujo correcto:

```text
Catalogo define o cambia estructura vendible
-> Catalogo genera incidencia persistente idempotente
-> Rentabilidad intenta resolver costo
-> Si resuelve, cierra incidencia o la marca resuelta automaticamente
-> Si no resuelve, conserva incidencia con bloqueo y responsable
-> Si ya hay costo confiable y falta precio, Comercial/Listas recibe su pendiente
```

## Tabla/infraestructura a reutilizar

Usar la tabla transversal existente:

```text
erp_notificaciones
```

Usar helper existente:

```text
NotificacionesErp::guardarOperativaEnConexion($db, $datos)
NotificacionesErp::resolverOperativaPorHuellaEnConexion($db, $tipo, $huella)
```

No se requiere DDL inicial si `erp_notificaciones` existe y tiene las columnas documentadas en `docs/erp_notificaciones_alertas_trabajo.md`.

## Tipo de incidencia principal

Tipo recomendado:

```text
catalogo_sku_derivado_costo_pendiente
```

Responsable:

```text
rentabilidad_costos
```

Permiso para ver:

```text
rentabilidad.ver
```

Permiso para resolver:

```text
rentabilidad.operar
```

Si `rentabilidad.operar` no existe, auditar permisos antes de implementarlo. No inventar permisos sin revisar `SeguridadPermisos.php`, `SeguridadEsquema.php` y convenciones del modulo.

Ruta accion sugerida:

```text
/rentabilidad/skus?id_sku={id_sku}&origen=catalogo_incidencia_costo
```

La ruta es una ayuda de navegacion, no reemplaza la incidencia.

## Eventos que deben generar incidencia

Catalogo debe generar o actualizar una incidencia cuando ocurra cualquiera de estos eventos:

1. SKU derivado pasa a estatus operativo/vendible.
2. Se crea o edita una presentacion vendible.
3. Se crea o edita una apertura de empaque.
4. Se crea o edita un paquete/receta.
5. Se crea o edita una variante vendible que dependa de otro SKU.
6. Cambia `id_sku_origen`.
7. Cambia `factor_conversion`.
8. Cambia `merma_porcentaje`.
9. Cambia unidad base, unidad de venta, precision decimal o incremento minimo.
10. Cambia el modo de inventario: `stock_propio`, `descuenta_origen`, `preparacion_al_momento`.

No debe generarse incidencia si el SKU no es vendible, esta inactivo/no operativo o no requiere costo.

## Huella idempotente

La incidencia debe ser idempotente para evitar duplicados.

Formato recomendado de huella base:

```text
catalogo|costo_derivado|sku:{id_sku_derivado}|tipo:{tipo_derivacion_rentabilidad}|origen:{id_sku_origen}|hash_receta:{hash_receta}
```

`hash_receta` debe calcularse con los datos que cambian el costo:

- `id_sku_origen`.
- `tipo_derivacion_rentabilidad`.
- `factor_conversion`.
- `merma_porcentaje`.
- unidades.
- precision/incremento.
- componentes fijos y grupos/opciones si es paquete.
- modo inventario.

Si cambia la receta, la huella cambia o se actualiza la incidencia con nueva evidencia. La decision final dependera de implementacion:

- Para cambios menores del mismo SKU, actualizar incidencia activa existente.
- Para cambios estructurales que invalidan una resolucion anterior, crear nueva incidencia y conservar trazabilidad de la anterior.

## Payload minimo

El `payload_json` debe incluir:

```json
{
  "huella": "...",
  "id_sku_derivado": 0,
  "sku_derivado": "",
  "id_producto_erp": 0,
  "tipo_derivacion_rentabilidad": "presentacion",
  "id_sku_origen": 0,
  "sku_origen": "",
  "factor_conversion": 0,
  "merma_porcentaje": 0,
  "unidad_base": {},
  "unidad_venta": {},
  "modo_inventario": "descuenta_origen",
  "componentes": {},
  "advertencias_configuracion": [],
  "evento_origen": "guardar_presentacion",
  "fecha_contexto_catalogo": "YYYY-MM-DD HH:MM:SS",
  "responsable_sugerido": "rentabilidad_costos"
}
```

## Estados esperados

Usar estados de `erp_notificaciones`:

- `pendiente`: Rentabilidad aun no la revisa.
- `en_revision`: Rentabilidad la esta trabajando.
- `bloqueada`: falta estructura o evidencia.
- `resuelta`: Rentabilidad ya puede devolver costo confiable.
- `descartada`: no aplica con motivo.
- `cancelada`: el SKU o receta dejo de estar vigente.

## Resolucion automatica vs manual

Rentabilidad debe intentar resolver automaticamente cuando reciba o consulte la incidencia.

Resultado automatico posible:

1. **Resuelta automaticamente**
   - Tiene costo origen confiable.
   - Factor y merma validos.
   - Componentes con costo si es paquete.
   - Formula calculable.

2. **Bloqueada por Catalogo**
   - Falta origen.
   - Factor invalido.
   - Paquete sin componentes.
   - Variante mal modelada.
   - Advertencias `CAT-DER-*` estructurales.

3. **Bloqueada por evidencia de costo**
   - Origen sin costo.
   - Componente sin costo.
   - Falta compra/proveedor/XML/inventario.
   - Apertura fisica confirmada sin costo real, si aplica.

4. **Pendiente de revision manual**
   - Formula calculable pero confianza media/baja.
   - Hay merma o configuracion nueva que conviene validar.

## Relacion con Comercial/Listas

La incidencia de Rentabilidad no debe crear precio.

Cuando Rentabilidad resuelve costo confiable de un SKU vendible, entonces puede quedar disponible el siguiente pendiente para Comercial/Listas:

```text
catalogo_sku_vendible_sin_precio_lista
```

Regla:

```text
sin costo confiable -> no empujar como listo para precio final
con costo confiable + sin precio vigente -> pendiente para Listas
```

## Responsabilidad por faltante

Rentabilidad debe mostrar claramente quien debe corregir:

| Faltante | Responsable |
| --- | --- |
| Falta SKU origen | Catalogo |
| Factor invalido | Catalogo |
| Paquete sin componentes | Catalogo |
| Variante mal modelada | Catalogo |
| SKU origen sin costo | Rentabilidad/Compras/Proveedores segun fuente |
| Componente sin costo | Rentabilidad/Compras/Proveedores |
| Apertura fisica sin costo real | Almacen/Tienda/Rentabilidad |
| Falta precio de venta | Comercial/Listas |

## Implementacion recomendada

### Fase 1 - Catalogo genera incidencia

Agregar helper en Catalogo:

```text
CatalogoErpDatos::registrarIncidenciaCostoDerivadoSku($db, $idSku, $eventoOrigen, $idUsuario)
```

El helper debe:

- consultar `resolverContextoSkuVendible($idSku)`;
- validar si requiere costo;
- construir huella idempotente;
- llamar a `NotificacionesErp::guardarOperativaEnConexion`;
- no calcular ni guardar importes de costo;
- devolver `id_notificacion` y estatus.

### Fase 2 - Rentabilidad consume incidencias

Agregar en Rentabilidad una bandeja filtrada:

```text
/rentabilidad/incidencias_costos_derivados
```

O integrar en la vista actual de calidad, siempre que quede claro que son incidencias persistentes y no solo auditoria.

Debe mostrar:

- SKU.
- Tipo derivacion.
- Origen.
- Factor.
- Modo inventario.
- Formula esperada.
- Bloqueo.
- Responsable.
- Accion: resolver/reintentar/descartar con motivo.

### Fase 3 - Resolucion de incidencia

Rentabilidad debe usar:

```text
RentabilidadErp::resolverCostoVigenteSku($idSku, $contexto)
```

Si devuelve costo confiable:

- marcar incidencia `resuelta`;
- guardar resumen en `payload_json` o en bitacora futura;
- si falta precio, generar o actualizar pendiente para Listas.

Si no devuelve costo confiable:

- mantener `pendiente` o `bloqueada`;
- actualizar payload con `bloqueos`, `advertencias`, `siguiente_paso` y `responsable`.

## UAT recomendado

1. Crear/editar una presentacion vendible.
   - Debe crear una notificacion `catalogo_sku_derivado_costo_pendiente`.
   - Si se vuelve a guardar sin cambios, no debe duplicar.

2. Cambiar factor de una presentacion.
   - Debe actualizar la incidencia activa o crear una nueva segun politica de huella.

3. Crear paquete con componentes completos.
   - Rentabilidad debe resolver o calcular rango.

4. Crear paquete con un componente sin costo.
   - Debe quedar bloqueado por componente, no por Catalogo.

5. Crear granel/apertura sin SKU origen.
   - Debe quedar bloqueado por Catalogo con `CAT-DER-002`.

6. Resolver costo y confirmar que si falta precio se genera pendiente para Listas.

## Criterio de cierre

El plan queda implementado cuando:

- Catalogo genera incidencia persistente al crear/activar/cambiar receta vendible.
- La incidencia es idempotente y no ensucia la bandeja con duplicados.
- Rentabilidad puede resolver automatico cuando tenga informacion suficiente.
- Rentabilidad bloquea con responsable claro cuando falta estructura o evidencia.
- Listas solo recibe pendiente cuando ya hay costo confiable o cuando se decide permitir precio con advertencia.
- Catalogo no guarda costos ni precios finales.

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
## Avance 2026-08-23 - Incidencia manual desde Pendientes comerciales

Proyecto aplicado: `C:\xampp\htdocs\panel_de_control`.

Se agrego una accion manual en la seccion `Pendientes comerciales de SKUs` para generar la incidencia de costo hacia Rentabilidad sobre SKUs derivados ya creados.

Codigo:

- `CatalogoErpDatos::generarIncidenciaCostoDerivadoManual($datos, $idUsuario)`.
- `CatalogoErp::incidencia_costo_derivado_generar()`.
- Boton `Generar incidencia` en la auditoria de costos de `public/assets/js/custom/apps/erp/catalogo/productos.js`.

Reglas aplicadas:

- La accion no calcula costo ni guarda precio.
- Reutiliza la misma huella idempotente de `catalogo_sku_derivado_costo_pendiente`, por lo que no debe duplicar incidencias activas del mismo SKU/tipo.
- Solo aplica si el SKU es vendible, requiere costo y su tipo de derivacion no es `sku_normal`.
- Queda protegida por `catalogo.editar` porque crea una notificacion persistente.
- Sirve para regularizar SKUs derivados existentes que fueron creados antes de conectar los eventos automaticos.

UAT recomendado:

1. Ir a Catalogo ERP > Productos.
2. En `Pendientes comerciales de SKUs`, presionar `Revisar costos`.
3. En un SKU derivado pendiente, presionar `Generar incidencia`.
4. Confirmar respuesta `Incidencia de costo enviada a Rentabilidad`.
5. Revisar en `erp_notificaciones` una fila tipo `catalogo_sku_derivado_costo_pendiente` con `payload_json.evento_origen=manual_pendientes_comerciales`.
6. Repetir el click y confirmar que no se crean duplicados innecesarios.
