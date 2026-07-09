# ERP Compras - Estructura para cerrar el modulo

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-08  
Estado: Documento rector para terminar Compras  
Relacionados: `erp_compras_vision_operativa.md`, `erp_compras_plan_modulo.md`, `erp_compras_solicitudes_trabajo.md`, `erp_compras_orden_nueva_trabajo.md`, `erp_compras_orden_editar_trabajo.md`, `erp_compras_orden_ver_trabajo.md`

## Proposito

Este documento define que debe incluir el modulo de Compras para considerarse terminado en un ERP robusto. No reemplaza reglas de negocio del dueno; organiza el modulo para convertirlo en tareas delegables por rol y evitar rehacer analisis en cada prompt.

Compras debe servir para controlar que se necesita comprar, que se compro, a quien, con que documentos, con que costos, que diferencias hubo y que informacion nueva debe alimentar Catalogo, Finanzas, Almacen e Inventario.

## Principio central

Compras puede capturar mucha informacion, pero no debe convertirse en la verdad final de todo.

- Compras define la intencion y documenta la compra.
- Catalogo valida y completa productos, SKUs, fiscales, unidades y reglas maestras.
- Finanzas valida saldos, pagos, notas, cuentas por pagar y conciliacion.
- Almacen confirma recepcion fisica, lotes, caducidad, series y ubicaciones.
- Inventario se afecta desde recepciones, ajustes, traspasos y ventas, no desde crear/editar una orden.
- Direccion aprueba excepciones o compras sensibles.

## Secciones obligatorias del modulo

### 1. Solicitudes de compra

Objetivo:

- Registrar una necesidad antes de comprar.
- Permitir aprobacion, rechazo, cancelacion y generacion de orden.
- Evitar duplicar ordenes desde una misma solicitud.

Debe incluir:

- Cabecera: solicitante, area, proveedor sugerido, almacen destino sugerido, prioridad, fecha requerida, observaciones.
- Detalle: SKU ERP si existe, descripcion libre si no existe, cantidad, unidad, motivo, costo estimado opcional.
- Estados: `borrador`, `pendiente`, `aprobada`, `rechazada`, `orden_generada`, `cancelada`.
- Permisos: crear, editar, aprobar, cancelar, ver.
- Auditoria de cambios de estatus y generacion de orden.

Criterio de terminado:

- Una solicitud aprobada genera maximo una orden activa.
- Una solicitud rechazada/cancelada no genera orden.
- La orden conserva trazabilidad al detalle original.

### 2. Orden nueva y orden de compra

Objetivo:

- Capturar una compra real desde productos ERP, desde XML o desde una mezcla de ambos.
- Mantener la orden en `borrador` mientras se captura.
- Enviar la orden cuando ya sea operativamente valida.

Debe incluir:

- Cabecera: proveedor, almacen destino, fecha estimada, folio proveedor, moneda, tipo de cambio, contacto, telefono, direccion, observaciones.
- Detalle: producto/SKU ERP o concepto pendiente, cantidad, costo unitario, descuento, impuesto, si costo incluye IVA, total, datos fiscales.
- Origen de partidas: ERP, XML, solicitud, captura manual futura si se autoriza.
- Resumen: subtotal, impuestos, total, partidas registradas, partidas nuevas, fiscales completos, fiscales pendientes.
- Estados: `borrador`, `enviada`, `parcial`, `recibida`, `cancelada`.

Reglas:

- `borrador` permite editar.
- `enviada` prepara recepcion de almacen.
- `parcial` y `recibida` vienen de Almacen, no de Compras.
- `cancelada` no permite cambios operativos.
- No se afecta inventario al guardar o enviar.

Criterio de terminado:

- El usuario puede crear orden desde productos ERP o desde XML sin perder captura.
- El sistema distingue producto registrado vs producto pendiente.
- El sistema distingue dato fiscal capturado/propuesto vs dato fiscal maestro validado.
- Enviar orden valida minimo: proveedor, almacen, partidas, cantidades, costos, permisos y estatus editable.

### 3. Carga XML y conciliacion

Objetivo:

- Usar XML CFDI para acelerar captura y detectar diferencias.
- Extraer conceptos, datos fiscales, importes, UUID y proveedor fiscal.
- Conciliar contra partidas de la orden sin destruir informacion existente.

Debe incluir:

- Parseo sin orden guardada para cargar productos rapidamente en orden nueva.
- Importacion formal ligada a orden cuando ya existe ID.
- Registro de documento fiscal, conceptos, UUID/hash y resultado de conciliacion.
- Comparacion por SKU proveedor, SKU ERP, descripcion, cantidad, costo e impuestos.
- Pendientes por producto nuevo, producto no relacionado, producto faltante, cantidad diferente, costo diferente, fiscal incompleto.

Reglas:

- XML no reemplaza automaticamente datos maestros de Catalogo.
- XML puede proponer o completar datos fiscales faltantes de la partida.
- No pisar datos fiscales existentes con valores vacios.
- Conceptos no conciliados deben quedar accionables.

Criterio de terminado:

- XML en orden nueva carga partidas temporales.
- XML en orden guardada queda registrado y conciliado.
- Diferencias quedan visibles y resolubles.
- El usuario puede relacionar, descartar o convertir conceptos en pendientes.

### 4. Enriquecimiento de productos y datos fiscales

Objetivo:

- Convertir informacion capturada en compras en pendientes utiles para Catalogo.
- Mejorar la verdad maestra sin permitir que Compras ensucie el catalogo final.

Debe incluir:

- Resumen de productos registrados vs nuevos.
- Resumen de fiscales completos vs pendientes.
- Captura/propuesta de clave SAT, clave unidad SAT, unidad, objeto impuesto, IVA, IEPS, si incluye IVA, si requiere factura.
- Registro de origen del dato: ERP, XML, usuario compras, catalogo validado.
- Pendientes delegables para Catalogo.

Reglas:

- Compras puede proponer datos fiscales y de proveedor.
- Catalogo valida alta definitiva de producto/SKU.
- Si un producto ya existe, no sobrescribir datos maestros sin permiso/regla explicita.
- Si el XML trae mejor informacion, generar propuesta o pendiente, no cambio silencioso.

Criterio de terminado:

- Cada partida sabe si esta lista para orden, lista para catalogo o pendiente.
- Catalogo puede tomar pendientes exactos desde Compras.
- No hay informacion fiscal importante atrapada solo en la UI.

### 5. Costos y precios de compra

Objetivo:

- Registrar costo real de compra, descuentos e impuestos.
- Alimentar analisis posterior sin actualizar precios finales de forma peligrosa.

Debe incluir:

- Ultimo costo ERP mostrado al buscar SKU.
- Costo capturado en orden.
- Costo XML.
- Costo sin IVA y con IVA cuando aplique.
- Descuento por partida y descuento masivo.
- Moneda y tipo de cambio.
- Diferencia contra costo anterior.
- Propuesta de actualizacion de costo para Catalogo/Costos.

Reglas:

- Costo de compra no necesariamente actualiza costo maestro al guardar orden.
- Actualizacion de costos debe quedar como propuesta o regla aprobada.
- Costo promedio debe calcularse con recepcion/inventario, no solo con orden enviada.

Criterio de terminado:

- Se puede consultar que costo se compro, contra que costo anterior y que diferencia hubo.
- Se puede generar pendiente/propuesta de costo sin afectar inventario indebidamente.

### 6. Pagos, notas y saldos

Objetivo:

- Registrar informacion financiera ligada a la orden sin perder control contable.

Debe incluir:

- Resumen financiero: total orden, aplicado, saldo.
- Pagos: metodo, estado, monto, fecha, referencia, observaciones, comprobante futuro.
- Notas de credito: estado, monto, fecha, referencia, observaciones.
- Estados de pago: pendiente, aplicado, conciliado, cancelado.
- Estados de nota: pendiente, aplicada, cancelada.

Reglas:

- Finanzas opera o valida pagos/notas segun permisos.
- Pagos/notas aplicados reducen saldo; pendientes no.
- No permitir sobrepago.
- No cancelar orden con pagos/notas aplicadas sin regla explicita.

Criterio de terminado:

- Compras puede capturar si el rol lo permite.
- Finanzas puede consultar y operar sin depender de la pantalla de compras.
- La orden muestra saldo correcto desde backend.

### 7. Adjuntos

Objetivo:

- Conservar evidencia operativa y documental de la compra.

Debe incluir:

- Tipos: cotizacion, factura, comprobante de pago, nota de credito, orden firmada, otro.
- Archivo, referencia, observaciones, fecha, estado.
- Descarga por endpoint autenticado.
- Cancelacion logica con auditoria.
- Almacenamiento fuera de `public`.

Reglas:

- Adjuntos son evidencia; no deben confundirse con XML usado para cargar productos.
- Cancelar adjunto conserva historial.
- Algunos adjuntos pueden volverse obligatorios segun estatus o tipo de compra, si el dueno lo define.

Criterio de terminado:

- Adjuntar, listar, descargar y cancelar funciona con permisos.
- Se puede identificar que documento soporta cada pago/nota/factura cuando se implemente la relacion fina.

### 8. Envio a almacen y recepcion

Objetivo:

- Pasar a Almacen una orden enviada para confirmar recepcion fisica.

Debe incluir:

- Preparacion de recepcion al enviar.
- Liga orden-recepcion.
- Resumen de recepcion visible desde orden.
- Estados de recepcion: pendiente, parcial, recibida, incidencias.
- Manejo de faltantes, excedentes, danados, cambiados.

Reglas:

- Compras no marca `parcial` ni `recibida`.
- Si ya hubo recepcion, cancelacion debe estar restringida o requerir autorizacion.
- Lote, caducidad, serie y ubicacion pertenecen a Almacen/Inventario.

Criterio de terminado:

- Flujo probado: solicitud -> orden -> XML/productos -> envio -> recepcion parcial -> recepcion completa.

### 9. Reportes y control

Objetivo:

- Convertir compras en informacion para decisiones.

Debe incluir:

- Ordenes por estatus, proveedor, fecha, almacen, usuario.
- Productos comprados, costo anterior vs actual, pendientes fiscales/catalogo.
- Ordenes con saldo pendiente.
- Ordenes con XML no conciliado.
- Ordenes pendientes de recepcion.
- Productos no surtidos o faltantes.

Criterio de terminado:

- Direccion puede saber que se compro, cuanto costo, que falta recibir, que falta pagar y que productos requieren limpieza.

## Roles y responsabilidades delegables

- Compras: solicitudes, ordenes, XML operativo, diferencias, adjuntos, comunicacion con proveedor.
- Catalogo: productos nuevos, SKUs, datos fiscales maestros, unidades, reglas de inventario, costos autorizados.
- Finanzas: pagos, notas, saldos, conciliacion, comprobantes, cuentas por pagar.
- Almacen: recepcion, lotes, caducidad, ubicacion, incidencias fisicas.
- Direccion: aprobaciones, excepciones, compras sensibles, proveedores no autorizados.
- Auditoria/soporte: consulta de trazabilidad y diagnostico.

## Regla de permisos por seccion

Cada documento y tarea del modulo debe definir permisos por seccion, no solo por pantalla.

- Vista general de compras: normalmente `compras.ver`.
- Crear/editar solicitudes u ordenes: `compras.crear` / `compras.editar`.
- Enviar/aprobar: `compras.aprobar`.
- Cancelar: `compras.cancelar`.
- Adjuntos: `compras.adjuntos` para subir/cancelar; `compras.ver` para consultar/descargar.
- Finanzas: `finanzas.ver` para consultar; `finanzas.operar` para pagos/notas.
- Almacen: `almacen.ver` para consultar recepciones; `almacen.recibir` para operar recepcion.
- Catalogo: `catalogo.ver` para consultar; `catalogo.editar` para resolver maestros; `catalogo.costos` para costos/margenes.
- Auditoria: usar permiso/rol especifico si se expone bitacora completa.

Regla tecnica: ocultar botones en frontend no basta; cada endpoint sensible debe validar permiso en backend.

## Orden recomendado de cierre

1. Cerrar solicitudes: nueva, editar, ver, listado, aprobacion y generacion de orden sin duplicados.
2. Cerrar orden nueva: productos ERP + XML + datos fiscales + guardado sin perder captura.
3. Cerrar editar orden: orden directa guardada, orden desde solicitud, modos por estatus y trazabilidad.
4. Cerrar ver orden: consulta segura, permisos por seccion, seguimiento y trazabilidad.
5. Cerrar persistencia de partidas: guardar todos los campos relevantes de costos/fiscales/origen.
6. Cerrar XML formal: parseo temporal, importacion formal, conciliacion interna, pendientes.
7. Cerrar pendientes para Catalogo: productos nuevos, fiscales incompletos, propuestas de costo.
8. Cerrar finanzas basica: pagos/notas/saldos con permisos.
9. Cerrar adjuntos y evidencia.
10. Cerrar envio a almacen y recepcion completa.
11. Cerrar reportes y tablero de pendientes.

## Decisiones que debe confirmar el dueno

- Si Compras puede operar pagos reales o solo capturar pagos reportados.
- Si una orden puede enviarse con productos nuevos pendientes de alta en Catalogo.
- Si una orden puede enviarse con datos fiscales incompletos.
- Que datos del XML pueden actualizar datos maestros y cuales solo generan propuesta.
- Cuando se actualiza ultimo costo, costo promedio y costo maestro.
- Que adjuntos son obligatorios por tipo/estatus.
- Que diferencias requieren aprobacion de Direccion.

## Como usar este documento en prompts

Cuando pidas una tarea, especifica:

- Seccion del modulo.
- Estado actual esperado.
- Rol responsable.
- Archivos a tocar.
- Reglas de negocio aplicables.
- Que no debe modificarse.
- Criterio de aceptacion.

Ejemplo:

```text
Trabaja solo en Compras > Orden nueva > XML temporal.
Objetivo: que al cargar XML sin orden guardada se agreguen partidas temporales con datos fiscales y origen XML.
No guardar documento fiscal formal hasta que exista id_orden_compra.
No modificar inventario ni catalogo maestro.
Archivos esperados: formulario.js y, solo si hace falta, Compra.php/ComprasXmlErp.php.
Criterio: productos ERP y XML conviven, se recalculan totales y se muestran pendientes fiscales.
```
