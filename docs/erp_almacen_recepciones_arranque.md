# ERP Almacen - Arranque de Recepciones

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-17  
Estado: Guia de traspaso desde Compras hacia Almacen/Recepciones.

## Objetivo del nuevo chat

Continuar el ERP desde el modulo de Almacen/Recepciones, usando la misma metodologia aplicada en Compras:

- Trabajar por tareas pequenas y verificables.
- Leer primero la documentacion viva.
- No inventar reglas de negocio.
- No tocar base de datos sin respaldo externo previo.
- Documentar cada hallazgo, decision y evidencia.
- Marcar UAT como aprobado solo con prueba real o evidencia tecnica suficiente.

El objetivo operativo inmediato es validar y cerrar el flujo:

Solicitud/Orden de compra -> Orden enviada -> Recepcion preparada -> Recepcion parcial -> Orden parcial -> Recepcion completa -> Orden recibida -> Notificaciones cerradas.

## Instrucciones para el nuevo chat

Prompt sugerido para iniciar:

```text
Estoy retomando el ERP en el modulo Almacen/Recepciones despues de cerrar Compras.

Lee primero:
- AGENTS.md
- docs/erp_plan_maestro_fundamentos.md
- docs/erp_almacen_recepciones_arranque.md
- docs/erp_compras_uat_resultados.md
- docs/erp_compras_plan_modulo.md, solo secciones de Almacen/Recepcion
- docs/erp_notificaciones_alertas_trabajo.md

Objetivo:
Cerrar recepciones de almacen con la misma metodologia usada en Compras: tareas pequenas, UAT documentado, evidencia por folio, hallazgos con ID, respaldo externo antes de escrituras de BD y sin mezclar flujo ERP nuevo con legacy.

Empieza auditando el estado real de Almacen/Recepciones, especialmente REC-OC-24 y REC-OC-20, y dime que falta para ejecutar UAT-COM-008 y UAT-COM-009 sin romper Compras.
```

## Archivos que debe contemplar

### Base obligatoria

- `AGENTS.md`
- `docs/erp_plan_maestro_fundamentos.md`
- `docs/erp_almacen_recepciones_arranque.md`
- `docs/erp_compras_uat_resultados.md`
- `docs/erp_compras_plan_modulo.md`
- `docs/erp_compras_tareas_vivas.md`
- `docs/erp_notificaciones_alertas_trabajo.md`
- `docs/erp_gastos_cargos_compra_trabajo.md`

### Controladores y modelos

- `app/controladores/Almacen.php`
- `app/controladores/Inventario.php`
- `app/controladores/Compra.php`
- `app/modelos/Almacenes.php`
- `app/modelos/AlmacenEsquema.php`
- `app/modelos/InventarioErp.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/modelos/NotificacionesErp.php`
- `app/modelos/NotificacionesEsquema.php`

### Vistas y JS

- `app/vistas/paginas/apps/erp/almacen/mostrar_recepciones.php`
- `app/vistas/paginas/apps/erp/almacen/recibir.php`
- `public/assets/js/custom/apps/erp/almacen/mostrar_recepciones/listing_recepciones.js`
- `public/assets/js/custom/apps/erp/almacen/recibir/recibir.js`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

### Inventario relacionado

- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`

## Estado heredado desde Compras

Compras quedo practicamente cerrado para pasar a Almacen.

UAT Compras aprobados:

- `UAT-COM-001` a `UAT-COM-007`
- `UAT-COM-010` a `UAT-COM-015`

Pendientes que pertenecen al flujo Almacen:

- `UAT-COM-008`: Recepcion parcial actualiza orden a `parcial`.
- `UAT-COM-009`: Recepcion completa actualiza orden a `recibida`.
- `UAT-NOT-006`: Recepcion de almacen cierra alerta `compra_orden_enviada_recepcion_pendiente`.

Ordenes/recepciones utiles:

- `OC-2026-000020` / `REC-OC-20`
  - Orden enviada.
  - Recepcion creada.
  - 13 partidas.
  - 105 unidades ordenadas.
  - 0 recibidas.
  - 105 pendientes.
  - Tiene pagos/notas aplicadas; usar con cuidado si se prueba cancelacion.

- `OC-2026-000024` / `REC-OC-24`
  - Orden enviada.
  - Recepcion creada.
  - Partida fisica: `SAL-50L`, cantidad ordenada 5.
  - Cargo `Empaque y maniobra` quedo fuera de recepcion despues de correccion.
  - Recomendado para prueba corta de recepcion parcial/completa.

Correccion importante ya aplicada:

- `Almacenes::consultar_detalle_orden_compra_para_recepcion` excluye `servicio`, `cargo`, `adicional` y `no_inventariable`.
- La recepcion de almacen no debe incluir cargos ni servicios no inventariables.

Respaldo externo reciente:

- `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260617_232935_antes_limpieza_rec_oc_24.sql`

## Reglas de negocio que no deben romperse

### Compras

- Crear/editar orden no afecta inventario.
- Enviar orden prepara recepcion, pero no recibe mercancia.
- Productos fisicos sin SKU ERP bloquean envio.
- Cargos/servicios no inventariables pueden enviarse, suman al total, pero no pasan a recepcion.
- Pagos/notas no deben depender de Almacen para existir.

### Almacen

- Almacen recibe productos fisicos inventariables.
- Almacen confirma cantidades reales, lote, caducidad, ubicacion y observaciones.
- Almacen actualiza `cantidad_recibida` del detalle de orden.
- Almacen cambia orden a:
  - `parcial` si hay productos recibidos parcialmente.
  - `recibida` si todo lo inventariable fue recibido.
- Almacen no modifica costos comerciales/fiscales de la orden.
- Cargos/servicios no generan existencia, lote ni movimiento de inventario.

### Inventario

- Existencias y movimientos nacen desde recepcion confirmada.
- Si el producto requiere lote/caducidad/serie, la recepcion debe capturarlo o generar incidencia segun politica.
- No crear movimientos duplicados si se guarda dos veces.

### Notificaciones

- Al enviar una orden se genera alerta para Almacen.
- Al recibir parcial o completo, debe actualizar/cerrar la alerta correspondiente.
- Marcar lectura no equivale a resolver.
- Resolver implica que el evento operativo fue atendido.

## Metodologia obligatoria de trabajo

### 1. Auditar antes de tocar

Antes de modificar codigo:

- Buscar endpoints y modelos exactos con `rg`.
- Leer solo secciones necesarias.
- Confirmar tablas/columnas con consultas puntuales.
- Identificar si el flujo es ERP nuevo o legacy.

### 2. Probar con evidencia

Cada prueba debe registrar:

- Folio de orden.
- Folio de recepcion.
- Usuario/rol si aplica.
- Resultado esperado.
- Resultado obtenido.
- Consultas de evidencia.
- Hallazgo si falla.

### 3. Documentar en vivo

Usar o crear documentos:

- `docs/erp_almacen_recepciones_uat_resultados.md`
- `docs/erp_almacen_recepciones_tareas_vivas.md`

Si se toca Compras por contrato con Almacen, actualizar tambien:

- `docs/erp_compras_uat_resultados.md`
- `docs/erp_compras_tareas_vivas.md`

### 4. Respaldo antes de escrituras en BD

Antes de cualquier `UPDATE`, `DELETE`, migracion, correccion de datos o esquema:

1. Generar respaldo externo en:

```text
C:\Users\aleja\Documents\RespaldosBD\panel
```

2. Nombrar el respaldo con fecha, base y motivo.
3. Documentar la ruta en el archivo UAT/hallazgo.
4. Ejecutar la escritura minima necesaria.
5. Verificar con SELECT.

### 5. No aprobar con supuestos

Un UAT solo queda aprobado si:

- El usuario lo confirma funcionalmente, o
- Hay evidencia tecnica suficiente y no destructiva.

Si algo falla:

- No marcar aprobado.
- Crear hallazgo.
- Corregir de forma acotada.
- Repetir prueba.

## UAT inicial de Almacen

Crear documento `docs/erp_almacen_recepciones_uat_resultados.md` con matriz inicial:

| ID | Escenario | Recepcion/Orden | Resultado | Evidencia | Hallazgo |
| --- | --- | --- | --- | --- | --- |
| UAT-ALM-001 | Listado de recepciones muestra ordenes enviadas | REC-OC-24 | Pendiente |  |  |
| UAT-ALM-002 | Ver recepcion carga detalle correcto | REC-OC-24 | Pendiente |  |  |
| UAT-ALM-003 | Recepcion parcial actualiza recepcion y orden parcial | REC-OC-24 | Pendiente |  |  |
| UAT-ALM-004 | Recepcion completa actualiza recepcion y orden recibida | REC-OC-24 | Pendiente |  |  |
| UAT-ALM-005 | No duplica movimientos al guardar dos veces |  | Pendiente |  |  |
| UAT-ALM-006 | Lote/caducidad/ubicacion requeridos segun reglas |  | Pendiente |  |  |
| UAT-ALM-007 | Existencias se actualizan correctamente |  | Pendiente |  |  |
| UAT-ALM-008 | Notificacion de recepcion pendiente se cierra al atender | REC-OC-24 | Pendiente |  |  |

Mapeo con Compras:

- `UAT-ALM-003` cierra `UAT-COM-008`.
- `UAT-ALM-004` cierra `UAT-COM-009`.
- `UAT-ALM-008` cierra `UAT-NOT-006`.

## Primeras tareas recomendadas

### Bloque 1 - Auditoria sin cambios

1. Revisar `Almacen.php`, `Almacenes.php`, `recibir.js` y `recibir.php`.
2. Confirmar endpoints:
   - `obtener_recepciones`
   - `consultar_recepcion`
   - `guardar_recepcion`
3. Consultar `REC-OC-24`:
   - cabecera;
   - detalle;
   - estatus;
   - notificacion vinculada.
4. Confirmar que el cargo no inventariable no aparece en la recepcion.

### Bloque 2 - UX de recepcion

Validar que la pantalla permita:

- Ver orden/proveedor/almacen.
- Ver cantidad ordenada, recibida y pendiente.
- Capturar cantidad recibida.
- Capturar lote.
- Capturar caducidad.
- Capturar ubicacion.
- Capturar observaciones/incidencias.
- Guardar parcial.
- Guardar completo.

Anotar cualquier UX confuso en documento, no dejarlo solo en chat.

### Bloque 3 - Guardado parcial

Usar `REC-OC-24`:

- Recibir menos de 5 unidades de `SAL-50L`.
- Confirmar:
  - recepcion queda parcial;
  - orden queda parcial;
  - detalle orden actualiza cantidad recibida;
  - existencia/movimiento se genera solo por cantidad recibida;
  - notificacion sigue pendiente o cambia segun politica definida.

### Bloque 4 - Guardado completo

Completar las unidades restantes.

Confirmar:

- recepcion queda completa/recibida;
- orden queda `recibida`;
- notificacion de almacen queda resuelta;
- no se duplican movimientos;
- existencias finales cuadran.

## Consultas utiles de evidencia

Orden y recepcion:

```sql
SELECT id_orden_compra, folio, estatus, total
FROM erp_compras_ordenes
WHERE folio IN ('OC-2026-000024','OC-2026-000020');
```

```sql
SELECT id_recepcion_almacen, folio, id_orden_compra, estatus
FROM erp_almacen_recepciones
WHERE folio IN ('REC-OC-24','REC-OC-20');
```

Detalle de recepcion:

```sql
SELECT rd.id_recepcion_detalle, rd.id_orden_compra_detalle, rd.id_sku_erp,
       rd.sku, rd.nombre_producto, rd.cantidad_ordenada,
       rd.cantidad_recibida, rd.cantidad_pendiente, rd.estatus
FROM erp_almacen_recepciones_detalle rd
INNER JOIN erp_almacen_recepciones r
  ON r.id_recepcion_almacen = rd.id_recepcion_almacen
WHERE r.folio = 'REC-OC-24'
ORDER BY rd.id_recepcion_detalle;
```

Confirmar que no hay cargos en recepcion:

```sql
SELECT COUNT(*) total,
       SUM(CASE WHEN d.tipo_item IN ('cargo','servicio','adicional','no_inventariable') THEN 1 ELSE 0 END) no_inventariables
FROM erp_almacen_recepciones_detalle rd
INNER JOIN erp_almacen_recepciones r
  ON r.id_recepcion_almacen = rd.id_recepcion_almacen
INNER JOIN erp_compras_ordenes_detalle d
  ON d.id_detalle = rd.id_orden_compra_detalle
WHERE r.folio = 'REC-OC-24';
```

Notificaciones:

```sql
SELECT id_notificacion, tipo, area_responsable, permiso_requerido,
       titulo, prioridad, estatus, id_entidad_origen
FROM erp_notificaciones
WHERE modulo_origen='compras'
  AND entidad_origen='erp_compras_ordenes'
  AND id_entidad_origen IN (20,24)
ORDER BY id_notificacion;
```

## Criterio de cierre del modulo base

Almacen/Recepciones queda listo para la siguiente fase cuando:

- Recepcion parcial funciona.
- Recepcion completa funciona.
- Orden cambia a `parcial` y `recibida` correctamente.
- Existencias/movimientos cuadran.
- No hay duplicados al guardar.
- Cargos/servicios no llegan a recepcion.
- Alertas de almacen se cierran cuando corresponde.
- Todo queda documentado con folios y evidencia.
