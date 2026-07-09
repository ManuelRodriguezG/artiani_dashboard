# ERP Compras y Solicitudes - Plan vivo del modulo

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-07  
Proyecto: ERP propio, modulo Compras  
Estado del documento: Vivo, se debe actualizar conforme avance el modulo

## Proposito

Este documento existe para que el modulo de Compras no se construya "a memoria" ni por impulsos. Debe servir como mapa de trabajo, checklist funcional, bitacora tecnica y guia para futuras IAs o para mantenimiento manual.

El objetivo del modulo es administrar el ciclo completo de compra:

Tablero vivo consolidado:

- `docs/erp_compras_tareas_vivas.md` (sigue el estado real por bloque y decide el siguiente paso).

1. Solicitud de compra.
2. Orden de compra.
3. Carga de XML y conciliacion.
4. Adjuntos.
5. Pagos, notas y saldos.
6. Envio a almacen.
7. Recepcion de almacen.
8. Actualizacion posterior de costos, inventario, pendientes y reportes.

La estrategia actual es construir todo en ERP, sin depender de tablas `ecom_*`. Ecommerce debe alimentarse despues desde ERP, no al reves.

## Principios del modulo

- Todo endpoint debe validar sesion.
- Todo endpoint sensible debe validar permiso puntual.
- Todo POST sensible debe quedar auditado.
- Crear, editar, ver, cancelar, adjuntar, pagar y aprobar deben ser acciones separadas.
- La vista debe ser intuitiva y reducir pasos innecesarios.
- El usuario no debe perder captura si todavia no ha guardado.
- No se debe afectar inventario al crear o editar una orden; inventario se afecta en recepcion de almacen.
- Los pagos y notas no deben borrarse fisicamente; deben cancelarse logicamente.
- Los adjuntos cancelados conservan historial, pero el archivo fisico se elimina para no consumir almacenamiento.
- XML debe acelerar captura, pero no destruir informacion importante de solicitud.
- Un mismo XML/CFDI puede reutilizarse como fuente de captura para varias ordenes cuando el proveedor emite una factura con varias marcas/listas; no debe duplicarse como documento fiscal por UUID/hash.
- Los productos no encontrados, no surtidos o no relacionados deben generar pendientes accionables.
- El modulo debe poder consultarse en modo solo lectura sin riesgo de editar.
- Las ordenes en borrador pueden capturar productos propuestos; al enviar, todo producto fisico inventariable debe tener SKU ERP. Solo cargos/servicios no inventariables pueden avanzar sin SKU ERP.

## Estado actual implementado

### Solicitudes de compra

Archivos principales:

- `app/modelos/SolicitudesCompraErp.php`
- `app/controladores/Compra.php`
- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`

Funciones actuales:

- Crear solicitud.
- Editar solicitud.
- Ver solicitud.
- Listar solicitudes.
- Cambiar estatus.
- Generar orden desde solicitud.

Pendiente de revisar:

- Permisos finos en toda accion.
- Validaciones de estatus.
- Que una solicitud con orden activa no genere duplicados.
- Que una solicitud cancelada o rechazada no pueda generar orden.
- Mejorar UX de productos pendientes para futuras solicitudes.

### Ordenes de compra

Archivos principales:

- `app/modelos/OrdenesCompraErp.php`
- `app/controladores/Compra.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Crear orden directa.
- Editar orden en borrador.
- Ver orden.
- Listar ordenes.
- Generar orden desde solicitud.
- Enviar orden.
- Cancelar orden.
- Preparar recepcion de almacen al enviar.

Reglas actuales importantes:

- `borrador` permite edicion.
- `enviada` prepara recepcion de almacen.
- `parcial` y `recibida` deben venir de almacen, no de compras.
- `cancelada` no debe permitir cambios operativos.
- Enviar requiere permiso `compras.aprobar`.
- Cancelar requiere permiso `compras.cancelar`.
- Guardar orden existente requiere `compras.editar`.
- Crear orden nueva requiere `compras.crear`.

Pendiente de mejorar:

- Reducir rodeos al crear orden nueva: pagos, notas, adjuntos y XML no deben obligar a guardar manualmente primero si el usuario ya esta capturando una compra real.
- Propuesta: implementar `borrador automatico`.
- Si el usuario intenta adjuntar, importar XML o agregar pago en una orden nueva sin ID, el sistema debe guardar automaticamente la cabecera minima y continuar.

### XML y conciliacion

Archivos principales:

- `app/modelos/ComprasXmlErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/controladores/Compra.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Importar XML.
- Guardar documento fiscal.
- Guardar conceptos.
- Conciliar conceptos contra detalle de orden.
- Resolver manualmente conceptos.
- Sincronizar pendientes.
- Evitar duplicados por UUID o hash.
- Si el XML ya existe por UUID/hash, la vista puede volver a parsearlo para cargar conceptos en otra orden, pero no debe crear otro registro fiscal ni otro archivo.

Reglas actuales:

- XML requiere orden existente.
- XML debe usarse en orden editable.
- XML no debe borrar productos de solicitud automaticamente.
- Si algo no coincide, debe ir a revision.

Pendiente de mejorar:

- Que en una orden nueva el XML pueda disparar guardado automatico de borrador.
- Mejorar comparacion por SKU proveedor, SKU ERP, descripcion y cantidad.
- Crear flujo claro para productos no incluidos en XML pero solicitados.
- Crear flujo claro para productos incluidos en XML pero no solicitados.
- Registrar fiscales faltantes de producto/SKU.
- No pisar datos fiscales existentes con valores vacios.

### Pagos, notas y saldos

Archivos principales:

- `app/modelos/PagosCompraErp.php`
- `app/controladores/Compra.php`
- `app/modelos/ComprasEsquema.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Consultar resumen financiero.
- Registrar pago.
- Cancelar pago.
- Registrar nota de credito.
- Cancelar nota de credito.
- Recalcular saldo desde backend.

Reglas actuales:

- Pagos y notas requieren `finanzas.operar`.
- Consulta financiera requiere `finanzas.ver`.
- Pago aplicado o conciliado reduce saldo.
- Nota aplicada reduce saldo.
- Pago o nota pendiente no reduce saldo.
- No se permite sobrepago.
- No se permiten pagos en orden `borrador`.
- No se permite cancelar orden con pagos o notas aplicadas.

Observacion UX importante:

Actualmente no permitir pagos en borrador es correcto contablemente, pero puede ser fastidioso si el usuario esta capturando una factura completa desde cero. Para reducir rodeos:

- La vista debe poder guardar automaticamente un borrador cuando el usuario quiera agregar pagos.
- Luego el pago se registra contra esa orden ya creada.
- El usuario no debe sentir que hizo dos pasos separados.

Pendiente:

- Crear guardado automatico previo para pagos/notas si orden nueva no tiene ID.
- Definir si pagos se capturan en compras o finanzas segun rol.
- Confirmar si compras puede registrar "intencion de pago" y finanzas concilia despues.
- Agregar referencia obligatoria para transferencia, tarjeta y nota de credito.
- Posible carga de comprobante ligada al pago.

### Adjuntos

Archivos principales:

- `app/modelos/AdjuntosCompraErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/controladores/Compra.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Listar adjuntos.
- Subir adjunto.
- Ver o descargar adjunto por endpoint autenticado.
- Cancelar adjunto.
- Eliminar archivo fisico al cancelar.
- Conservar registro historico.
- Validar MIME.
- Evitar duplicado por hash.

Reglas actuales:

- Ver adjunto requiere `compras.ver`.
- Subir/cancelar adjunto requiere `compras.adjuntos`.
- Archivos se guardan fuera de `public`, en `storage/erp/compras/ordenes/`.
- `.gitignore` excluye almacenamiento de adjuntos.

Pendiente:

- Guardado automatico de borrador si se adjunta en orden nueva.
- Vincular adjuntos a entidades concretas: orden, pago, nota, XML, proveedor.
- Clasificar adjuntos: cotizacion, factura, comprobante de pago, nota de credito, orden firmada, otro.
- Previsualizacion mejorada para PDF e imagenes.
- Mostrar motivo de cancelacion.
- Evitar cancelar adjuntos necesarios cuando la orden ya este recibida o conciliada.

### Almacen y recepcion

Archivos relacionados:

- `app/modelos/Almacenes.php`
- `app/modelos/AlmacenEsquema.php`
- `app/controladores/Almacen.php`
- `app/modelos/OrdenesCompraErp.php`

Flujo actual:

- Compras envia orden.
- Se crea o prepara recepcion de almacen.
- Almacen recibe productos.
- Almacen actualiza cantidades recibidas.
- Orden puede pasar a parcial o recibida segun recepcion.

Reglas deseadas:

- Compras no debe marcar manualmente `parcial` ni `recibida`.
- Almacen controla recepcion.
- Si se recibio algo, compras ya no debe poder cancelar libremente.
- Lote, caducidad, ubicacion, serie y existencias pertenecen a almacen/inventario, no a compras.

Pendiente:

- Revisar estatus exactos entre orden y recepcion.
- Generar enlaces desde orden a recepcion.
- Mostrar resumen de recepcion en vista Ver orden.
- Validar recepciones duplicadas.
- Probar compra completa: solicitud -> orden -> XML -> envio -> recepcion parcial -> recepcion completa.

## Permisos actuales del modulo

Compras:

- `compras.ver`: ver solicitudes, ordenes, adjuntos y documentos.
- `compras.crear`: crear solicitudes y ordenes.
- `compras.editar`: modificar solicitudes y ordenes existentes.
- `compras.aprobar`: aprobar solicitudes o enviar ordenes.
- `compras.cancelar`: cancelar documentos de compra.
- `compras.adjuntos`: subir o cancelar adjuntos.

Finanzas:

- `finanzas.ver`: consultar pagos, notas y saldo.
- `finanzas.operar`: registrar o cancelar pagos y notas.

Almacen:

- `almacen.ver`: consultar recepciones.
- `almacen.recibir`: registrar recepcion de mercancia.
- `almacen.ubicaciones`: administrar ubicaciones.

Catalogo:

- `catalogo.ver`: consultar productos, SKUs, proveedores y listas.
- `catalogo.editar`: crear o modificar productos/SKUs.
- `catalogo.costos`: ver o ajustar costos, margenes e impuestos.

Regla general:

Cada nueva funcion debe definir permiso antes de codificar endpoint.

## Roles involucrados

Roles esperados:

- `soporte_sistema`: acceso tecnico para diagnostico y mantenimiento.
- `compras`: opera solicitudes, ordenes, XML, adjuntos y puede consultar/registrar pagos si asi se decide.
- `finanzas_contabilidad`: opera pagos, notas, saldos, conciliacion y documentos contables.
- `almacen`: recibe productos, lotes, caducidades y ubicaciones.
- `catalogo_productos`: completa productos nuevos, SKU, impuestos y reglas.
- `direccion`: consulta y aprueba cuando aplique.
- `auditor`: consulta trazabilidad sin operar.

Decision pendiente:

- Confirmar si `compras` debe tener `finanzas.operar` o solo `finanzas.ver`.
- Alternativa mas controlada: compras captura "pago reportado" y finanzas lo marca `conciliado`.

## Estatus recomendados

### Solicitud de compra

- `borrador`: editable.
- `pendiente`: enviada para aprobacion.
- `aprobada`: puede generar orden.
- `rechazada`: no genera orden.
- `orden_generada`: ya tiene orden activa.
- `cancelada`: cerrada sin compra.

### Orden de compra

- `borrador`: editable por compras.
- `enviada`: ya fue emitida y genera recepcion.
- `parcial`: viene de almacen cuando hay recepcion parcial.
- `recibida`: viene de almacen cuando se recibio todo.
- `cancelada`: cerrada.

Reglas:

- Compras solo cambia `borrador -> enviada` o `borrador/enviada -> cancelada`.
- Almacen cambia a `parcial` o `recibida`.
- Una orden `parcial` o `recibida` debe ser vista, no editada desde compras.

## UX objetivo

El modulo debe ser rapido para capturar compras reales.

Problemas a evitar:

- Guardar, salir, volver a entrar, cargar XML.
- Guardar, salir, volver a entrar, cargar adjuntos.
- Guardar, salir, volver a entrar, cargar pago.
- Repetir datos de proveedor o factura.
- Perder informacion por no haber guardado.

Solucion propuesta:

### Guardado automatico de borrador

Cuando la orden es nueva y el usuario intenta:

- importar XML,
- subir adjunto,
- registrar pago,
- registrar nota,
- agregar productos pendientes,

la vista debe:

1. Validar proveedor y datos minimos.
2. Guardar borrador automaticamente.
3. Actualizar `orden_id` en la vista.
4. Continuar con la accion solicitada.

Esto mantiene backend correcto sin hacer al usuario dar rodeos.

## Productos y pendientes

Casos que deben contemplarse:

- Producto solicitado pero no comprado.
- Producto comprado en XML pero no solicitado.
- Producto existe en ERP pero no en lista del proveedor.
- Producto existe en lista proveedor pero no esta bien relacionado.
- Producto no existe en ERP.
- Producto con datos fiscales incompletos.
- Producto con costo diferente al historial.

Tablas actuales o esperadas:

- `erp_compras_ordenes_productos_atencion`
- `erp_proveedores_listas_productos_revision`
- tablas fiscales ERP del catalogo de productos/SKUs

Pendiente:

- Confirmar tabla final para fiscalidad ERP.
- Confirmar si revision de producto nuevo vive en catalogo o compras.
- Crear vista de atencion de productos pendientes.
- Generar tareas para catalogo_productos.

## Datos fiscales y SAT

Debe contemplarse:

- Clave producto SAT.
- Clave unidad SAT.
- Unidad.
- Objeto impuesto.
- Tipo impuesto.
- Porcentaje IVA.
- IEPS si aplica.
- Precio incluye IVA.
- Requiere factura.
- Fuente del dato: manual, XML, catalogo, proveedor.

Reglas:

- No pisar datos existentes con vacios.
- Si XML trae dato fiscal y ERP no lo tiene, sugerir o registrar pendiente.
- Si hay diferencia entre ERP y XML, generar alerta.

## Calculos

Calculos principales:

- Subtotal linea = cantidad * costo antes de impuesto - descuento.
- Impuesto linea = subtotal linea * porcentaje impuesto.
- Total linea = subtotal linea + impuesto.
- Total orden = suma total linea.
- Saldo pendiente = total orden - pagos aplicados - notas aplicadas.

Para cargos, servicios y gastos asociados a compras, revisar tambien `docs/erp_gastos_cargos_compra_trabajo.md`. La regla actual es: Compras los captura para cuadrar total, Almacen no los recibe como inventario, y Finanzas/Costos definira si quedan como gasto o se prorratean al costo real de productos.

Reglas:

- Calculo final debe validarse en backend.
- Frontend puede calcular para UX, pero no ser fuente unica de verdad.
- No permitir total menor que movimientos financieros aplicados.
- XML debe distinguir costo antes de impuesto y total con impuesto.

Pendiente:

- Descuentos globales.
- Retenciones.
- IEPS.
- Moneda extranjera y tipo de cambio contable.
- Redondeos por XML SAT vs calculo interno.

## Adjuntos y documentos

Tipos esperados:

- Cotizacion.
- Factura.
- XML.
- PDF de factura.
- Comprobante de pago.
- Nota de credito.
- Orden firmada.
- Captura de pantalla.
- Otro.

Pendiente:

- Vincular comprobante de pago a pago especifico.
- Vincular nota de credito a nota especifica.
- Mostrar documentos desde la vista Ver orden.
- Descargar ZIP de todos los documentos de una orden.

## Auditoria

Debe auditar:

- Crear solicitud.
- Editar solicitud.
- Aprobar/rechazar solicitud.
- Generar orden desde solicitud.
- Crear orden directa.
- Editar orden.
- Enviar orden.
- Cancelar orden.
- Importar XML.
- Resolver conciliacion XML.
- Subir/cancelar adjunto.
- Registrar/cancelar pago.
- Registrar/cancelar nota.
- Preparar recepcion.
- Recibir mercancia.

Pendiente:

- Vista de historial por orden.
- Mostrar usuario, fecha, accion y datos relevantes.

## Documentacion obligatoria en codigo

Cada funcion nueva o bloque importante debe documentarse con:

```php
/**
 * Modulo: ERP Compras
 * Funcion: nombre_funcion
 * Documentacion IA: Codex GPT-5
 * Fecha: YYYY-MM-DD
 * Descripcion: Que hace y por que existe.
 * Permisos: permiso.necesario
 * Tablas afectadas: tabla_1, tabla_2
 * Reglas: reglas de negocio relevantes
 */
```

En JavaScript:

```js
/**
 * Modulo: ERP Compras
 * Funcion: nombreFuncion
 * Documentacion IA: Codex GPT-5
 * Fecha: YYYY-MM-DD
 * Descripcion: Que controla en la vista.
 * Endpoints: /compra/endpoint
 * Notas UX: comportamiento esperado para el usuario.
 */
```

Si en el futuro se usa otra IA o version:

- Cambiar `Documentacion IA`.
- No borrar contexto anterior si explica decisiones historicas.
- Agregar nota de migracion si se cambia comportamiento.

## Tareas prioritarias siguientes

### Bloque 1 - UX sin rodeos

Estado: Implementado; UAT pendiente  
Prioridad: Alta  
Objetivo: Permitir capturar una compra completa sin guardar manualmente varias veces.

Tareas:

- [x] Crear mecanismo JS para asegurar borrador antes de acciones que necesitan ID.
- [x] Si no hay `orden_id`, guardar borrador automatico para adjuntos/XML cuando aplique.
- [x] Reutilizarlo antes de XML y adjuntos en orden nueva.
- [x] Evitar doble submit.
- [ ] UAT: probar crear orden nueva completa desde cero con XML, adjuntos y reingreso.
- [ ] Decision pendiente: pagos/notas se registran cuando la orden ya no esta en borrador; Finanzas debe operar despues de enviada.

### Bloque 2 - Flujo de estatus

Estado: Implementado; UAT pendiente  
Prioridad: Alta

Tareas:

- [x] Validar permisos por transicion.
- [x] Bloquear pagos/notas en borrador y cancelada.
- [x] Bloquear cancelacion con recepcion iniciada.
- [x] Bloquear cancelacion con pagos/notas aplicadas.
- [x] Mantener `parcial` y `recibida` como estatus controlados por Almacen.
- [x] Agregar mensajes claros por cada bloqueo operativo.
- [ ] UAT: probar ciclo borrador -> enviada -> parcial -> recibida y bloqueos de cancelacion.

### Bloque 3 - Vista Ver orden

Estado: Implementado; UAT pendiente  
Prioridad: Alta

Tareas:

- [x] Mostrar cabecera.
- [x] Mostrar productos.
- [x] Mostrar XML y conciliacion.
- [x] Mostrar adjuntos con descarga.
- [x] Mostrar pagos/notas si tiene `finanzas.ver`.
- [x] Mostrar recepcion vinculada cuando exista.
- [x] No permitir inputs editables ni acciones operativas.
- [ ] UAT: revisar vista en orden borrador, enviada, parcial, recibida y cancelada.

### Bloque 4 - Productos pendientes y revision

Estado: Implementado en generacion de pendientes; vista de atencion pendiente  
Prioridad: Alta

Tareas:

- [x] Consolidar productos pendientes por proveedor/SKU desde Compras.
- [x] No duplicar pendientes por huella/origen.
- [x] Separar en regla operativa: Catalogo atiende producto/SKU; Proveedores atiende relacion proveedor-SKU.
- [x] Generar incidencia interdepartamental desde orden de compra para producto fisico sin SKU ERP o relacion proveedor-SKU faltante.
- [ ] Crear vista de atencion.
- [ ] Resolver pendiente y vincular a producto/SKU desde el modulo responsable.

### Bloque 5 - Fiscalidad de productos

Estado: Parcial; cierre pertenece a Catalogo/Fiscalidad ERP  
Prioridad: Media-Alta

Tareas:

- [ ] Definir tablas ERP fiscales finales.
- [x] Mapear XML a fiscalidad disponible en captura de Compras.
- [x] No sobrescribir datos buenos con vacios.
- [x] Generar alertas/incidencias por faltantes o diferencias detectables.
- [ ] Crear vista de revision fiscal en Catalogo/Fiscalidad.

### Bloque 6 - Reportes y trazabilidad

Estado: Pendiente  
Prioridad: Media

Tareas:

- Reporte de compras por proveedor.
- Reporte de saldos pendientes.
- Reporte de productos no surtidos.
- Reporte de productos nuevos.
- Reporte de diferencias XML vs orden.
- Historial de costos por SKU/proveedor.

## Checklist antes de cerrar Compras

- [x] Solicitudes completas.
- [x] Orden directa completa.
- [x] Orden desde solicitud completa.
- [x] Ver orden completa.
- [x] XML completo y conciliacion usable.
- [x] Adjuntos completos.
- [x] Pagos y notas completos.
- [x] Flujo de estatus implementado.
- [x] Recepcion de almacen conectada.
- [x] Productos pendientes generados.
- [ ] Vista de atencion de productos pendientes funcionando.
- [ ] Datos fiscales cerrados en Catalogo/Fiscalidad.
- [ ] Permisos por rol probados.
- [x] Auditoria de acciones sensibles registrada.
- [ ] Pruebas con factura real sin descuento.
- [ ] Pruebas con factura real con descuento.
- [ ] Pruebas con productos nuevos.
- [ ] Pruebas con productos no surtidos.
- [ ] Pruebas con pago parcial.
- [ ] Pruebas con nota de credito.
- [ ] Pruebas con adjuntos imagen/PDF/XML.

## Bitacora de decisiones

### 2026-06-07 - Reinicio limpio del modulo ERP

Documentacion IA: Codex GPT-5

Decision:

- Separar arquitectura nueva de compras ERP.
- Evitar depender de ecommerce.
- Usar permisos finos.
- Separar pagos, adjuntos y XML en modelos/endpoints propios.

Motivo:

- El modulo anterior se rehizo varias veces y tenia mezclas entre crear/editar/ver.
- Se busca un ERP robusto, mantenible y con menos errores.

### 2026-06-07 - Pagos fuera de borrador

Documentacion IA: Codex GPT-5

Decision:

- No registrar pagos reales en orden `borrador`.

Matiz UX:

- Para no hacer tediosa la captura, se debe implementar guardado automatico de borrador cuando el usuario intente registrar pagos desde una orden nueva.

### 2026-06-07 - Adjuntos privados

Documentacion IA: Codex GPT-5

Decision:

- Guardar adjuntos fuera de `public`.
- Servir archivos por endpoint autenticado.
- Cancelar adjunto elimina archivo fisico, pero conserva registro.

Motivo:

- Seguridad, trazabilidad y control de almacenamiento.
