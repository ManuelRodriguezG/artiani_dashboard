# ERP Compras - Tablero de tareas vivas (consolidado)

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-15  
Objetivo: Unificar las tareas de Compras en un solo tablero accionable para pruebas reales.

Este tablero se apoya en:

- docs/erp_compras_plan_modulo.md
- docs/erp_compras_vision_operativa.md
- docs/erp_compras_solicitudes_avance.md
- docs/erp_compras_solicitudes_trabajo.md
- docs/erp_compras_orden_nueva_trabajo.md
- docs/erp_compras_orden_editar_trabajo.md
- docs/erp_compras_orden_ver_trabajo.md
- docs/erp_compras_uat_resultados.md

Regla de uso:

- Cada tarea se conserva en este archivo como fuente viva de estado.
- Los documentos individuales quedan como detalle o historial de diseno.
- Cambiar [ ] por [x] solo cuando la funcionalidad este implementada, probada y validada con su documento de cierre.
- Si una tarea queda bloqueada por regla de negocio, anotar `Bloqueada:` con causa y fecha.

---

## 1) Estado real de Solicitudes de Compra

- [x] Esquema ERP de solicitudes revisado y con lineas base (campos/estados/auditoria definidos).
- [x] Estados y permisos backend: borrador/pendiente/aprobada/rechazada/orden_generada/cancelada.
- [x] Nueva solicitud (borrador + enviar a pendiente + validaciones minimas).
- [x] Editar solicitud con control por estatus.
- [x] Ver solicitud completa (solo lectura y acciones segun permiso).
- [x] Documento formal (PDF o HTML imprimible, solo lectura).
- [x] Listado de solicitudes con filtros y acciones por permisos.
- [x] Generar orden desde solicitud (solo aprobada, sin duplicados, traza a detalle).
- [x] Diferencias solicitud vs orden (faltantes, adicionales, costos, cantidades): endpoint `orden_diferencias_solicitud_erp` y modelo `OrdenesCompraErp::compararConSolicitud`.
- [x] Pendientes para catalogo desde solicitud (productos nuevos o datos incompletos).
- Cierre UI Solicitudes 2026-06-15: vista de consulta, documento imprimible y listado operativo quedan implementados para pruebas reales.

Notas de cierre:

- Se cerró la integración en backend:
  - `Compra::orden_generar_desde_solicitud_erp` consume `OrdenesCompraErp::generarDesdeSolicitud`.
  - `OrdenesCompraErp::crearDesdeSolicitud` protege estado `aprobada`, evita duplicados de orden activa, conserva trazabilidad `id_solicitud` y cambia la solicitud a `orden_generada`.
  - `SolicitudesCompraErp::guardar` registra incidencias en `erp_catalogo_incidencias_calidad` con tipo `compra_producto_propuesto` para items sin `id_sku_erp`.

### Bloqueos y validaciones detectadas para el siguiente ciclo (Solicitud)

- [x] Unificar endpoint de consulta de SKUs para que siempre use `erp_catalogo_sku_proveedores` como fuente confiable, y mostrar sugerencias de listas auxiliares con marca clara de no confiable.
- [x] Cerrar reglas de apertura de edicion para estatus no editables: backend valida y la UI desactiva captura fuera de `borrador`.
- [x] Asegurar que la solicitud rechazada/cancelada no pueda generar orden por ningun camino del flujo ERP nuevo.

Evidencia 2026-06-15:

- `OrdenesCompraErp::crearDesdeSolicitud` solo genera orden si la solicitud esta `aprobada` y no tiene orden activa.
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js` solo muestra editar para `borrador` y generar orden para `aprobada` sin orden relacionada.
- Las rutas legadas `cambio_estatus_solicitud_de_compra` y `generar_orden_de_compra` estan deshabilitadas antes de ejecutar logica antigua.

---

## 2) Estado real de Ordenes de Compra

- [x] Crear orden en borrador y editar orden en borrador con flujo ERP separado de ecom.
- [x] Enviar orden y preparar recepcion de almacen.
- [x] Envio condicionado por politica de ordenes con productos nuevos o pendientes fiscales: borrador flexible; envio bloquea productos fisicos sin SKU ERP; fiscales incompletos quedan como advertencia.
- [x] Edicion por estatus:
  - Borrador editable completo.
  - Enviada / Parcial / Recibida / Cancelada en modo seguimiento con bloqueos claros.
- [x] XML temporal en orden nueva (sin ID) para carga inicial que preserve productos ya cargados.
- [x] Importacion XML formal para orden guardada con conciliacion no destructiva.
- [x] Deteccion de producto ERP desde XML por SKU proveedor/ERP/no_identificacion/descripcion normalizada.
- [x] Carga/edicion de productos nuevos y marca de estado (nuevo/pedido/pendiente catalogo/no inventariable).
- [x] Cargos no inventariables (envio, empaque, maniobra, etc.) en partidas con impacto al total pero fuera de inventario.
- [x] Precio unitario sin IVA y con IVA visible y consistente (sin sobreescritura inesperada).
- [x] Descuento masivo (por porcentaje o importe) aplicado de forma reversible y auditada.
- [x] Adjuntos completos con referencia/observacion y estado de cancelacion.
- [x] Pagos/notas persistidos y saldo recalculado desde backend.
- [x] Vista de ver orden en modo solo lectura para todos los estatus.
- [x] Diferencias orden vs solicitud + orden vs XML visibles como pendientes accionables.
- [x] Fin de ciclo: cierre con recepcion + trazabilidad financiera + estatus final.

### Bloqueos o pendientes de validacion para el siguiente ciclo (Orden)

- El bug de busqueda que reportaste parece ligado a la fuente de datos y no al front:
  - La logica actual esta en `Proveedores::skusComprablesParaComprasErp()` y puede filtrar solo por catalogo activo del proveedor.
  - Confirmar que `erp_catalogo_sku_proveedores` tenga el mapeo activo de todos los productos esperados del proveedor antes de asumir un problema de UI.
  - Cierre 2026-06-15: Solicitudes y Ordenes consumen `Proveedores::skusComprablesParaComprasErp()`; no se activa fallback legacy en Compras para no mezclar productos no confiables. La UI ya indica cuando falta relacion activa proveedor-SKU.
- Revisar y unificar en una sola pantalla de partidas la conciliacion visible: eliminar duplicidad operativa entre tabla principal y tabla auxiliar de XML.
- Cierre 2026-06-15: Ordenes permite clasificar partidas sin SKU ERP como `servicio`, `cargo`, `adicional` o `no_inventariable`; esas partidas impactan totales pero no bloquean envio ni cuentan como producto pendiente de alta.
- Cierre 2026-06-17: Ordenes agrega captura directa de cargo/servicio no inventariable dentro del bloque de productos, para no depender de XML ni de trucos de reclasificacion al capturar flete, maniobra, empaque u otros cargos de factura.
- Cierre 2026-06-17: Ordenes agrega captura directa de producto fisico pendiente con SKU/nombre/costo; se puede guardar en borrador y genera incidencia operativa, pero el envio queda bloqueado hasta resolver Catalogo/Proveedores.
- Cierre 2026-06-15: La orden solo permite guardar partidas en `borrador`; la UI bloquea captura fuera de borrador y el modal fiscal queda en modo consulta. XML valida borrador, pagos/notas validan orden no borrador y adjuntos bloquean canceladas.
- Cierre 2026-06-15: La carga XML desde productos no exige guardar primero; al encontrar partidas existentes actualiza cantidad/costo con lo facturado, conserva partidas no incluidas y solo acumula cuando el mismo XML trae conceptos repetidos.
- Cierre 2026-06-16: Si el XML ya fue registrado por UUID/hash, la orden puede reutilizar sus conceptos para captura sin duplicar el documento fiscal; esto soporta proveedores que facturan varias marcas/listas en un solo CFDI.
- Cierre 2026-06-15: El parseo temporal de XML usa el proveedor seleccionado para reconocer relaciones activas en `erp_catalogo_sku_proveedores` por `NoIdentificacion`, SKU ERP, SKU proveedor o nombre exacto normalizado; no crea ni modifica productos.
- Cierre 2026-06-15: Las partidas muestran tipo y estado operativo; productos sin SKU quedan como pendientes de alta, y cargos/servicios no inventariables quedan separados.
- Cierre 2026-06-15: La tabla distingue `Costo capturado` y `Costo sin IVA`; al reabrir una orden con costo capturado con IVA, la UI reconstruye el bruto visual para no dividir dos veces el impuesto.
- Cierre 2026-06-15: Adjuntos ERP guardan referencia/observacion, permiten vista/descarga, cancelan con historial y eliminan ruta fisica al cancelar.
- Cierre 2026-06-15: Pagos y notas de credito persisten referencia/observacion/estado, bloquean borrador/cancelada en backend y recalculan `saldo_pendiente` desde `PagosCompraErp`.
- Cierre 2026-06-17: Pagos por tarjeta/transferencia y notas de credito exigen folio o referencia en backend y en validacion cliente para trazabilidad financiera.
- Cierre 2026-06-15: `ver_orden_compra` usa modo consulta sin permisos operativos, y el listado separa acciones `Ver` y `Editar` para evitar ediciones accidentales.
- Cierre 2026-06-17: El listado agrega accion `Seguimiento` para ordenes no canceladas; permite operar pagos/notas/adjuntos segun permisos sin reabrir edicion de productos.
- Cierre 2026-06-15: La orden muestra diferencias contra solicitud mediante `orden_diferencias_solicitud_erp` y pendientes XML desde conciliacion; resolver/mover/descartar XML queda limitado a borrador editable.
- Cierre transversal 2026-06-15: Rutas ERP nuevas de Compras usan permisos finos (`compras.*`, `finanzas.*`) y las acciones sensibles llaman auditoria explicita en `Compra.php`.
- Cierre transversal 2026-06-15: Las validaciones de estatus viven en backend: guardar solo borrador, XML solo borrador, finanzas no borrador/cancelada, adjuntos no cancelada.
- Cierre transversal 2026-06-15: Orden nueva puede usar XML y adjuntos con borrador automatico; pagos/notas quedan disponibles al enviar la orden, no durante captura inicial.
- Cierre 2026-06-15: Descuento masivo pide motivo opcional, puede revertirse aplicando 0%, conserva antes/despues por partida en el payload y registra evento `orden_descuento_masivo` en auditoria al guardar.
- Cierre 2026-06-15: Al guardar recepcion de almacen, `Almacenes::sincronizar_estatus_orden_desde_recepcion` actualiza la orden a `parcial` o `recibida`; finanzas conserva saldo aplicado/pendiente desde backend.
- Cierre 2026-06-16: Al guardar una orden, Compras genera/actualiza incidencias interdepartamentales en `erp_catalogo_incidencias_calidad` para productos fisicos sin SKU ERP y SKUs sin relacion proveedor; Compras detecta, Catalogo/Proveedores atienden.

---

## 3) Cross-cutting para modulo Compras (para pruebas reales)

- [x] Verificar permisos finos por accion en rutas clave de `app/controladores/Compra.php`:
  - `compras.ver/compras.crear/compras.editar/compras.aprobar/compras.cancelar/compras.adjuntos`
  - `finanzas.ver/finanzas.operar`
  - `catalogo.ver/catalogo.editar`
  - `almacen.ver/almacen.recibir`
- [x] Confirmar que todos los endpoints sensibles validen estatus en backend (no solo en UI).
- [x] Auditoria: revisar que acciones sensibles queden registradas y consultables por orden (estatus, cambios de partidas, pagos/notas, recepcion, cancelaciones).
- [x] UX operativo: mantener acciones de carga (XML/adjuntos/pago/nota) con friccion baja, idealmente con borrador automatico.
- [ ] Pruebas de aceptacion real (UAT) por escenarios:
  - [x] UAT-COM-001: solicitud nueva -> pendiente -> aprobada -> orden generada.
  - [x] UAT-COM-002: orden directa con productos ERP del proveedor.
  - [x] UAT-COM-003: carga XML antes de guardar borrador.
  - [x] UAT-COM-004: XML con productos adicionales y productos no surtidos.
  - [x] orden con/without descuento real
  - productos nuevos
  - no surtidos
  - pago parcial
  - nota de credito
  - [x] adjuntos imagen/PDF/XML
  - cambio de estatus completo

### Guia UAT Compras

Ejecutar en local con datos reales controlados y anotar evidencia por folio:

Plantilla de evidencia:

- `docs/erp_compras_uat_resultados.md`

1. Solicitud nueva con productos registrados.
   - Crear borrador, enviar a pendiente, aprobar y generar orden.
   - Validar que la orden conserve proveedor, almacen, partidas y folio de solicitud.
2. Orden directa desde proveedor.
   - Buscar SKU activo del proveedor y agregarlo.
   - Cargar XML antes de guardar y confirmar que reconoce relaciones activas.
   - Guardar borrador, reabrir y validar costos, impuestos, descuentos y datos fiscales.
3. XML con productos no incluidos en solicitud.
   - Confirmar que no borra partidas previas.
   - Confirmar adicionales y pendientes XML visibles.
   - Marcar cargo/no inventariable cuando aplique.
4. Descuento masivo.
   - Aplicar porcentaje con motivo.
   - Guardar, reabrir y validar totales.
   - Aplicar 0% para revertir y confirmar auditoria `orden_descuento_masivo`.
5. Adjuntos.
   - Subir PDF, imagen y XML como adjuntos.
   - Validar referencia/observacion, vista, descarga y cancelacion.
6. Envio y almacen.
   - Enviar orden.
   - Confirmar recepcion creada en almacen.
   - Recibir parcial y validar orden `parcial`.
   - Recibir completo y validar orden `recibida`.
7. Finanzas.
   - Registrar pago parcial.
   - Registrar nota de credito aplicada.
   - Validar saldo pendiente y marca de pagada.
8. Cancelaciones.
   - Cancelar pago/nota/adjunto.
   - Intentar cancelar orden con recepcion iniciada y validar bloqueo.

---

## 4) Siguiente autorizacion recomendada

La siguiente autorizacion no deberia ser de codigo ni de esquema; deberia ser de prueba operativa controlada.

Objetivo:

- Ejecutar `UAT-COM-001` a `UAT-COM-015` en local.
- Registrar evidencia en `docs/erp_compras_uat_resultados.md`.
- Corregir solo hallazgos reales y trazables, evitando reabrir reglas ya cerradas sin motivo operativo.

Orden recomendado:

1. Solicitudes: `UAT-COM-001`.
2. Orden directa y XML: `UAT-COM-002` a `UAT-COM-006`.
3. Estatus y almacen: `UAT-COM-007` a `UAT-COM-009`.
4. Finanzas: `UAT-COM-010` y `UAT-COM-011`.
5. Bloqueos y solo lectura: `UAT-COM-012` a `UAT-COM-015`.

Prompt recomendado para el siguiente bloque:

```text
Vamos a ejecutar UAT-COM-001 de Compras.
Usa AGENTS.md, docs/erp_compras_tareas_vivas.md y docs/erp_compras_uat_resultados.md.
No cambies codigo salvo que aparezca un error real durante la prueba.
Primero guiame paso a paso y registra el resultado en la matriz UAT.
```

## 5) Tareas de limpieza documental (opcional para ordenar tu repo)

Se recomienda conservar como vivos:

- `docs/erp_compras_plan_modulo.md`
- `docs/erp_compras_vision_operativa.md`
- `docs/erp_compras_solicitudes_avance.md`
- `docs/erp_compras_tareas_vivas.md` (este documento)

Se recomienda marcar como historicos/operativos (no borrar aun):

- `docs/erp_compras_solicitudes_*_trabajo.md`
- `docs/erp_compras_orden_*_trabajo.md`
- `docs/erp_compras_cierre_modulo.md`

Despues de UAT, se puede preparar una limpieza documental por fases:

- fase 1: mover documentos de trabajo ya cerrados a historicos,
- fase 2: dejar solo tablero vivo, plan maestro, vision operativa y evidencias UAT como documentos activos,
- fase 3: depurar documentos de detalle menos usados cuando ya no aporten contexto operativo.
