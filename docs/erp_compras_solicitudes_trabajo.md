# ERP Compras - Solicitudes de compra

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Guia operativa para prompts y tareas de solicitudes  
Aplica a: nueva solicitud, editar solicitud, ver solicitud, listado y generar orden  
Vistas relacionadas: `app/vistas/paginas/apps/erp/compras/solicitudes/*` y `public/assets/js/custom/apps/erp/compras/solicitudes/*`

## Proposito

Este documento define como debe funcionar Solicitudes de compra dentro del ERP. La solicitud no es una orden; es la forma de registrar una necesidad, aprobarla, rechazarla, cancelarla o convertirla en orden con trazabilidad.

La solicitud debe ayudar a responder:

- Que se necesita comprar.
- Quien lo solicito.
- Para que area/almacen/proposito se necesita.
- Que productos o conceptos se piden.
- Que prioridad tiene.
- Quien aprobo o rechazo.
- Si ya genero orden.
- Que diferencias hubo entre lo solicitado y lo comprado.

## Subdocumentos puntuales

Usar estos archivos para trabajar cada seccion sin releer todo el modulo:

- `docs/erp_compras_solicitudes_avance.md` - checklist maestro: que va primero, que sigue y que ya quedo.
- `docs/erp_compras_solicitudes_esquema_trabajo.md`
- `docs/erp_compras_solicitudes_estados_permisos_trabajo.md`
- `docs/erp_compras_solicitudes_nueva_trabajo.md`
- `docs/erp_compras_solicitudes_editar_trabajo.md`
- `docs/erp_compras_solicitudes_ver_trabajo.md`
- `docs/erp_compras_solicitudes_documento_formal_trabajo.md`
- `docs/erp_compras_solicitudes_listado_trabajo.md`
- `docs/erp_compras_solicitudes_generar_orden_trabajo.md`
- `docs/erp_compras_solicitudes_diferencias_trabajo.md`
- `docs/erp_compras_solicitudes_pendientes_catalogo_trabajo.md`

## Principio central

Solicitudes controlan la necesidad. Ordenes controlan la compra.

- Una solicitud aprobada puede generar una orden.
- Una solicitud rechazada o cancelada no debe generar orden.
- Una solicitud con orden activa no debe generar duplicados.
- La orden debe conservar referencia a solicitud y detalle.
- Si en la orden se compra diferente, la diferencia debe quedar visible.

## Archivos exactos de trabajo

Frontend:

- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`
- `app/vistas/paginas/apps/erp/compras/solicitudes/listado.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`
- `public/assets/js/custom/apps/erp/compras/solicitudes/listado.js`

Backend:

- `app/controladores/Compra.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/modelos/CatalogoErpEsquema.php` si faltan estructuras ERP de producto/SKU/fiscal.

Relacionados bajo demanda:

- `app/modelos/CatalogoErpDatos.php`
- `app/modelos/Almacenes.php`
- `app/modelos/SeguridadPermisos.php`

No tocar salvo necesidad justificada:

- Flujo legado de compras.
- Tablas `ecom_*` como fuente principal.
- Inventario fisico.
- Finanzas/pagos.
- Recepcion de almacen.

## Niveles de permisos por seccion

Regla general:

- Cada pantalla y seccion debe declarar permisos.
- Frontend puede ocultar acciones, pero backend debe validar siempre.

Matriz base:

- Ver solicitudes/listado: `compras.ver`.
- Crear solicitud: `compras.crear`.
- Editar solicitud en `borrador`: `compras.editar`.
- Enviar solicitud a aprobacion: `compras.crear` o `compras.editar`, segun regla final.
- Aprobar/rechazar solicitud: `compras.aprobar`.
- Cancelar solicitud: `compras.cancelar`.
- Generar orden desde solicitud: `compras.crear` y regla de solicitud `aprobada`; puede requerir `compras.aprobar` si se decide que generar orden es aprobacion operativa.
- Ver productos/SKUs: `catalogo.ver` o `compras.ver` con consulta limitada.
- Resolver producto nuevo/fiscal maestro: `catalogo.editar`, fuera de solicitudes.
- Auditoria completa: permiso/rol auditor si se crea.

## Estados de solicitud

Estados recomendados:

- `borrador`: editable por quien captura o rol autorizado.
- `pendiente`: enviada para aprobacion; no debe editarse libremente.
- `aprobada`: autorizada para generar orden.
- `rechazada`: no genera orden.
- `orden_generada`: ya tiene una orden activa relacionada.
- `cancelada`: cerrada sin compra.

Reglas:

- `borrador -> pendiente`: usuario termina captura y solicita aprobacion.
- `pendiente -> aprobada`: requiere `compras.aprobar`.
- `pendiente -> rechazada`: requiere `compras.aprobar` y motivo.
- `borrador/pendiente/aprobada -> cancelada`: requiere `compras.cancelar` y motivo, con restricciones si ya hay orden.
- `aprobada -> orden_generada`: ocurre al generar orden.
- `rechazada`, `cancelada`, `orden_generada`: no se editan como captura normal.

## Nueva solicitud

Objetivo:

- Capturar una necesidad de compra antes de emitir orden.

Debe incluir cabecera:

- Solicitante.
- Area/departamento si aplica.
- Prioridad.
- Proveedor sugerido opcional.
- Almacen destino sugerido.
- Fecha requerida.
- Motivo/observaciones.

Debe incluir detalle:

- Producto/SKU ERP si existe.
- Producto nuevo/propuesto si no existe.
- Descripcion libre.
- Cantidad.
- Unidad.
- Costo estimado opcional.
- Motivo por partida opcional.
- Estado catalogo/fiscal si aplica.

Reglas:

- No afecta inventario.
- No registra pagos.
- No crea orden automaticamente.
- Puede generar pendientes para Catalogo si se solicitan productos no registrados.
- Debe guardar como `borrador` o enviar a `pendiente`.

Criterio:

- La solicitud puede guardarse incompleta como borrador si la regla lo permite.
- Para enviarse a aprobacion debe tener datos minimos y partidas validas.

## Editar solicitud

Objetivo:

- Corregir o completar una solicitud antes de aprobar o generar orden.

Debe permitir en `borrador`:

- Editar cabecera.
- Agregar/quitar partidas.
- Cambiar cantidades.
- Cambiar proveedor/almacen sugeridos.
- Agregar productos nuevos/propuestos.
- Guardar o enviar a aprobacion.

Debe restringir en `pendiente`:

- Puede ser solo lectura para el solicitante.
- Aprobador puede aprobar/rechazar.
- Edicion posterior debe requerir regresar a borrador o flujo definido.

Debe restringir en `aprobada`:

- No editar libremente.
- Permitir generar orden si no existe orden activa.

Debe ser solo lectura en:

- `rechazada`.
- `cancelada`.
- `orden_generada`.

Criterio:

- El backend rechaza ediciones no permitidas aunque el frontend sea manipulado.

## Ver solicitud

Objetivo:

- Consultar necesidad, aprobacion, estatus y relacion con orden.

Debe mostrar:

- Cabecera.
- Partidas solicitadas.
- Estatus.
- Historial de aprobacion/rechazo/cancelacion.
- Orden relacionada si existe.
- Diferencias contra orden si ya se genero.

Permisos:

- Ver: `compras.ver`.
- Aprobar/rechazar: `compras.aprobar` si estatus `pendiente`.
- Generar orden: `compras.crear` y solicitud `aprobada`.
- Cancelar: `compras.cancelar` si reglas lo permiten.

Criterio:

- Ver solicitud permite entender si ya se compro o si sigue pendiente.

## Documento formal de solicitud

Objetivo:

- Desde la accion de ver solicitud, poder generar un archivo formal, presentable y con identidad del negocio.
- Servir como soporte interno para aprobacion, archivo, auditoria o comunicacion.

Formato esperado:

- PDF o HTML imprimible; PDF recomendado para archivo formal.
- Logotipo del negocio.
- Nombre del negocio.
- Folio/id de solicitud.
- Fecha de creacion.
- Estatus.
- Solicitante.
- Area/departamento si aplica.
- Prioridad.
- Fecha requerida.
- Proveedor sugerido si existe.
- Almacen destino sugerido.
- Motivo/observaciones.
- Tabla de partidas: SKU, producto/concepto, cantidad, unidad, costo estimado, total estimado si aplica.
- Productos nuevos/propuestos claramente marcados.
- Resumen de totales estimados si se capturan costos.
- Bloque de aprobacion: aprobado/rechazado por, fecha, observaciones.
- Orden relacionada si ya fue generada.
- Pie de pagina con fecha de generacion del archivo.

Reglas:

- El documento formal no debe permitir editar datos; solo representa el estado de la solicitud al momento de generarlo.
- Si se genera desde una solicitud `borrador`, debe marcarse como borrador.
- Si se genera desde una solicitud `aprobada`, debe mostrar datos de aprobacion.
- Si se genera desde una solicitud `orden_generada`, debe mostrar la orden relacionada.
- No debe exponer informacion financiera o de costos que el rol no pueda ver si se decide restringir costos.

Permisos:

- Generar/ver archivo formal: `compras.ver`.
- Ver costos estimados dentro del archivo: `compras.ver`; si se consideran sensibles, exigir `catalogo.costos` o permiso de direccion.
- Ver aprobaciones completas/auditoria: `compras.ver`; bitacora completa puede requerir permiso auditor.

Criterio:

- Desde ver solicitud existe una accion clara para descargar/imprimir solicitud formal.
- El archivo es consistente con el estatus y permisos del usuario.
- El documento puede guardarse o enviarse sin depender de capturas de pantalla.

## Listado de solicitudes

Objetivo:

- Dar control operativo de solicitudes.

Filtros esperados:

- Estatus.
- Fecha.
- Solicitante.
- Proveedor sugerido.
- Almacen destino.
- Prioridad.
- Productos pendientes/nuevos.
- Con/sin orden generada.

Columnas esperadas:

- Folio/id.
- Solicitante.
- Prioridad.
- Estatus.
- Fecha requerida.
- Proveedor sugerido.
- Almacen destino.
- Total estimado si aplica.
- Orden relacionada.
- Acciones por permiso.

Permisos:

- Ver listado: `compras.ver`.
- Crear: `compras.crear`.
- Editar: `compras.editar` y estatus editable.
- Aprobar/rechazar: `compras.aprobar`.
- Cancelar: `compras.cancelar`.
- Generar orden: `compras.crear` y regla de aprobacion.

Criterio:

- El listado permite priorizar trabajo sin entrar a cada solicitud.

## Generar orden desde solicitud

Objetivo:

- Convertir una solicitud aprobada en orden de compra con trazabilidad.

Reglas:

- Solo solicitud `aprobada` puede generar orden.
- No generar orden si ya existe una orden activa relacionada.
- La orden debe conservar `id_solicitud`.
- Cada partida debe conservar `id_solicitud_detalle` si existe.
- Productos no registrados pasan como pendientes/propuestos en orden.
- Cantidades y costos pueden copiarse como base, pero la orden puede registrar compra real.
- La solicitud pasa a `orden_generada` cuando se crea orden activa.

Permisos:

- Generar orden: `compras.crear`.
- Si generar implica autorizacion operativa adicional, requerir tambien `compras.aprobar`.

Criterio:

- La orden generada puede editarse en borrador segun plan de editar orden.
- La solicitud conserva enlace a la orden.
- No hay duplicados.

## Diferencias solicitud vs orden

Cuando una orden ya existe, la solicitud debe poder mostrar:

- Producto solicitado comprado igual.
- Producto solicitado no surtido.
- Cantidad menor.
- Cantidad mayor.
- Producto sustituido.
- Producto adicional no solicitado.
- Costo diferente al estimado.

Cada diferencia debe tener:

- Tipo.
- Partida de solicitud.
- Partida de orden.
- Estado.
- Justificacion opcional.

Permisos:

- Ver diferencias: `compras.ver`.
- Resolver/aceptar diferencias: permiso por definir; recomendado `compras.aprobar` o rol direccion para diferencias sensibles.

Criterio:

- La solicitud no queda como documento muerto despues de generar orden; sirve para comparar necesidad vs compra real.

## Productos nuevos desde solicitud

Regla:

- Compras puede solicitar producto nuevo/propuesto.
- Solicitud no crea producto maestro automaticamente.
- Catalogo debe resolver alta definitiva.
- Si se genera orden antes de resolver Catalogo, la orden conserva producto pendiente.

Debe guardar:

- Descripcion.
- SKU proveedor opcional.
- Unidad.
- Cantidad.
- Proveedor sugerido.
- Motivo.
- Datos fiscales si se conocen.

Permisos:

- Capturar propuesta: `compras.crear` / `compras.editar`.
- Resolver maestro: `catalogo.editar`.

## Esquema ERP propio requerido

Solicitudes deben vivir en estructura ERP propia.

Confirmar o crear:

- Cabecera de solicitud.
- Detalle de solicitud.
- Estado de solicitud.
- Relacion solicitud -> orden.
- Relacion detalle solicitud -> detalle orden.
- Campos para producto ERP o producto propuesto.
- Campos de proveedor/almacen sugeridos.
- Campos de prioridad/motivo/fecha requerida.
- Auditoria de cambios de estatus.
- Pendientes generados para Catalogo.

Reglas:

- No depender de tablas `ecom_*`.
- No adaptar flujo nuevo a campos heredados ambiguos.
- Si hay version anterior de compras/solicitudes, auditar campos antes de reutilizarlos.

## Auditoria

Debe registrarse:

- Creacion.
- Edicion.
- Envio a aprobacion.
- Aprobacion.
- Rechazo.
- Cancelacion.
- Generacion de orden.
- Cambios de partidas relevantes.

Permisos:

- Resumen basico: `compras.ver`.
- Bitacora completa: permiso auditor/soporte si se crea.

## Orden recomendado antes de codificar

1. Auditar esquema actual de solicitudes.
2. Confirmar estados y transiciones.
3. Confirmar permisos por seccion.
4. Implementar nueva/editar/ver/listado con backend validando estatus.
5. Implementar generacion de orden sin duplicados.
6. Implementar diferencias solicitud vs orden.
7. Implementar pendientes para Catalogo.
8. Implementar auditoria fina.

## Secciones de trabajo y prompts listos

### Tarea 1: Auditar esquema de solicitudes

```text
Trabaja solo en Compras > Solicitudes > Esquema.
Objetivo: auditar si existe estructura ERP propia para cabecera/detalle de solicitud, estados, relacion con orden, producto propuesto y auditoria.
Revisar: ComprasEsquema.php, SolicitudesCompraErp.php, OrdenesCompraErp.php.
No usar tablas ecom_* como fuente principal.
No eliminar campos heredados sin plan de migracion.
Criterio: entregar lista exacta de tablas/campos a crear, conservar, migrar o dejar de usar.
```

### Tarea 2: Estados y permisos de solicitud

```text
Trabaja solo en Compras > Solicitudes > Estados y permisos.
Objetivo: reforzar transiciones borrador, pendiente, aprobada, rechazada, orden_generada y cancelada con permisos por accion.
Archivos esperados: SolicitudesCompraErp.php, Compra.php y JS/vistas de solicitudes.
Backend debe rechazar transiciones invalidas.
Criterio: ninguna solicitud rechazada/cancelada genera orden y ninguna pendiente se edita libremente sin regla.
```

### Tarea 3: Nueva/editar solicitud

```text
Trabaja solo en Compras > Solicitudes > Formulario.
Objetivo: permitir crear y editar solicitudes en borrador con productos ERP, productos propuestos, proveedor/almacen sugeridos, prioridad y fecha requerida.
No afectar inventario ni crear orden automaticamente.
Criterio: se puede guardar borrador y enviar a aprobacion con datos minimos.
```

### Tarea 4: Ver solicitud

```text
Trabaja solo en Compras > Solicitudes > Ver.
Objetivo: crear vista segura de consulta con cabecera, partidas, estatus, auditoria basica, orden relacionada y diferencias si ya existe orden.
Permisos: compras.ver; acciones extra segun compras.aprobar/compras.cancelar/compras.crear.
Criterio: ver solicitud no permite editar salvo acciones autorizadas por estatus.
```

### Tarea 4.1: Documento formal de solicitud

```text
Trabaja solo en Compras > Solicitudes > Documento formal.
Objetivo: agregar desde Ver solicitud una accion para generar PDF o HTML imprimible con logotipo, folio, estatus, solicitante, partidas, proveedor/almacen sugeridos, aprobacion y orden relacionada si existe.
Permisos: compras.ver; ocultar costos si el negocio decide que son sensibles para el rol.
No modificar datos al generar el archivo.
Criterio: el archivo se descarga/imprime con formato presentable y refleja el estado real de la solicitud.
```

### Tarea 5: Listado de solicitudes

```text
Trabaja solo en Compras > Solicitudes > Listado.
Objetivo: mejorar listado con filtros por estatus, fecha, solicitante, prioridad, proveedor, almacen y con/sin orden.
Mostrar acciones segun permisos.
Criterio: el listado permite priorizar aprobaciones, pendientes y solicitudes listas para orden.
```

### Tarea 6: Generar orden desde solicitud

```text
Trabaja solo en Compras > Solicitudes > Generar orden.
Objetivo: generar una orden desde solicitud aprobada, conservar id_solicitud/id_solicitud_detalle, evitar duplicados y cambiar solicitud a orden_generada.
Archivos esperados: SolicitudesCompraErp.php, OrdenesCompraErp.php, Compra.php.
No permitir generar desde rechazada/cancelada/pendiente.
Criterio: una solicitud aprobada genera una sola orden activa trazable.
```

### Tarea 7: Diferencias solicitud vs orden

```text
Trabaja solo en Compras > Solicitudes > Diferencias contra orden.
Objetivo: mostrar y guardar diferencias entre solicitado y comprado: no surtido, menor/mayor cantidad, sustituido, adicional y costo diferente.
No borrar partidas solicitadas sin evidencia.
Criterio: solicitud y orden permiten explicar que se pidio vs que se compro.
```

### Tarea 8: Pendientes para Catalogo desde solicitud

```text
Trabaja solo en Compras > Solicitudes > Pendientes Catalogo.
Objetivo: cuando una solicitud incluya producto nuevo/propuesto o datos fiscales faltantes, generar pendiente para Catalogo sin crear maestro automaticamente.
Permisos: Compras captura; Catalogo resuelve.
Criterio: el pendiente conserva descripcion, cantidad, proveedor sugerido, motivo y datos propuestos.
```

## Checklist de terminado de solicitudes

- Nueva solicitud funciona con productos ERP y productos propuestos.
- Editar solicitud respeta estatus.
- Ver solicitud es solo lectura con acciones permitidas.
- Ver solicitud puede generar archivo formal con logotipo y datos de la solicitud.
- Listado filtra y muestra acciones por permiso.
- Estados y transiciones estan validados en backend.
- Solicitud aprobada puede generar orden.
- Solicitud rechazada/cancelada/pendiente no genera orden.
- Solicitud con orden activa no genera duplicado.
- Orden conserva relacion con solicitud y detalle.
- Diferencias solicitud vs orden quedan visibles.
- Productos nuevos generan pendientes para Catalogo.
- Todo cambio sensible queda auditado.

## Decisiones pendientes del dueno

- Quien puede enviar solicitud a aprobacion: creador, compras o jefe?
- Aprobar solicitud y generar orden requieren el mismo permiso o permisos separados?
- Se permite editar solicitud `pendiente` o debe regresarse a `borrador`?
- Una solicitud aprobada puede cancelarse antes de generar orden?
- Una solicitud con orden cancelada puede generar otra orden?
- Que diferencias solicitud vs orden requieren autorizacion?
- Se permite solicitud sin proveedor sugerido?
- Se permite solicitud con productos nuevos no resueltos por Catalogo?
- El documento formal debe ser PDF desde backend o HTML imprimible desde frontend?
- El documento formal debe mostrar costos estimados a todos los roles con `compras.ver` o solo a direccion/costos?
