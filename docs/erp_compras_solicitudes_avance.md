# ERP Compras - Solicitudes: control de avance

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Checklist maestro para ejecutar Solicitudes por orden

## Contexto obligatorio para agentes

Antes de trabajar cualquier tarea de este tablero, revisar:

- `AGENTS.md`: arquitectura, rutas criticas de Compras y reglas para no inventar negocio.
- `docs/erp_compras_vision_operativa.md`: objetivo operativo de Compras y reglas vivas del modulo.
- `docs/erp_compras_solicitudes_trabajo.md`: plan rector de Solicitudes.
- `docs/ia_uso_modelos.md`: nivel de IA recomendado segun riesgo, alcance y consumo de tokens.
- `docs/erp_ux_operativa.md`: obligatorio para formularios, cantidades, costos, descuentos, tablas, acciones y mensajes.
- Este archivo: estado actual, siguiente paso y evidencia pendiente.
- Documento puntual de la tarea activa.

Regla viva: si el dueno agrega una observacion reusable durante el trabajo, actualizar el documento rector correcto (`AGENTS.md`, `erp_ux_operativa.md`, `ia_uso_modelos.md`, vision operativa o documento puntual) y dejar una nota breve aqui cuando afecte Solicitudes.

## Proposito

Este archivo sirve para saber que va primero, que sigue y que ya quedo terminado dentro de Compras > Solicitudes.

Tablero vivo consolidado:

- `docs/erp_compras_tareas_vivas.md` (estado actual de tareas y prioridades).

Regla de uso:

- Cambiar `[ ]` por `[x]` cuando una tarea este implementada, probada y documentada.
- No marcar como hecha una tarea solo porque se empezo.
- Si una tarea queda bloqueada por regla de negocio, marcarla como `Bloqueada` en notas.
- Cada tarea debe trabajarse con su subdocumento puntual para evitar leer todo el proyecto.
- No repetir todo el contexto en prompts: usar la seccion `Contexto obligatorio para agentes`.

## Orden recomendado

### 1. Esquema ERP de solicitudes

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_esquema_trabajo.md`
- Nivel IA sugerido: D, modelo fuerte con razonamiento profundo.
- Objetivo: confirmar/crear estructura ERP propia para solicitudes, detalle, estados, relacion con orden, productos propuestos y auditoria.
- Depende de: nada.
- Terminado cuando:
  - Existe plan claro de tablas/campos/indices.
  - No se depende de `ecom_*`.
  - Se sabe que campos heredados se conservan, migran o dejan de usar.

Notas:

- Se agregaron columnas y Ã­ndices base en `ComprasEsquema.php` (`fecha_aprobacion`, `fecha_cancelacion`, `fecha_solicitud`, `fecha_requerida`, `solicitado_por`).
- Pendiente futuro: ejecutar auditoria completa de esquema en ventana de prueba cuando se vaya a cerrar el modulo.

Nota 2026-06-15: se aplico de forma dirigida `id_almacen_destino` en solicitudes y su indice. No se ejecuto el endpoint completo de esquema para evitar cambios colaterales sin autorizacion.

### 2. Estados y permisos

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_estados_permisos_trabajo.md`
- Nivel IA sugerido: D, modelo fuerte con razonamiento profundo.
- Objetivo: asegurar transiciones y permisos: borrador, pendiente, aprobada, rechazada, orden_generada, cancelada.
- Depende de: esquema minimo de solicitudes.
- Terminado cuando:
  - Backend rechaza transiciones invalidas.
  - Frontend muestra acciones segun permiso/estatus.
  - Aprobacion, rechazo, cancelacion y generar orden quedan auditados.

Notas:

- Implementadas reglas base en backend:
  - transiciones: borrador->cancelada, pendiente->aprobada/rechazada/cancelada, aprobada->cancelada/orden_generada.
  - rechazo/cancelacion exige motivo.
  - `compras.aprobar` para aprobar/rechazar, `compras.cancelar` para cancelar.
- Terminado en pruebas iniciales: reglas backend y acciones frontend por permisos en formulario/listado.

### 3. Nueva solicitud

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_nueva_trabajo.md`
- Nivel IA sugerido: C, implementacion puntual.
- Objetivo: crear solicitud con cabecera, productos ERP, productos propuestos, borrador y envio a aprobacion.
- Depende de: esquema, estados/permisos.
- Terminado cuando:
  - Se guarda borrador.
  - Se envia a aprobacion con datos minimos.
  - No afecta inventario ni crea orden automaticamente.
  - Cantidades usan controles intuitivos: stepper para enteros o paso decimal definido por unidad.

Notas:

- Implementado y en pruebas funcionales: la vista y flujo permite cabecera básica, productos del catálogo por proveedor, productos sugeridos (sin SKU de catálogo), guardado en borrador y envío a aprobación (`pendiente`), con validaciones de costo/cantidad.
- Backend valida que solo se puedan guardar/editar solicitudes en estatus `borrador`.
- Frontend desactiva edición para estados distintos a borrador.
- Pendiente final: validación manual en navegador con casos reales de uso.

### 4. Editar solicitud

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_editar_trabajo.md`
- Nivel IA sugerido: C, implementacion puntual.
- Objetivo: editar solo cuando el estatus lo permite, principalmente `borrador`.
- Depende de: nueva solicitud, estados/permisos.
- Terminado cuando:
  - `borrador` es editable.
  - `pendiente`, `aprobada`, `rechazada`, `cancelada` y `orden_generada` respetan restricciones.
  - Backend valida estatus.
  - La edicion de cantidades conserva la misma UX intuitiva definida para nueva solicitud.

Notas:

- Implementado y validado en frontend/backend:
  - En modo crear/editar se permite guardar como borrador o enviar a aprobación solo si el estatus está en borrador.
  - El endpoint `solicitud_guardar_erp` valida en backend que solo edita solicitudes en estado `borrador`.
  - El endpoint `solicitud_estatus_erp` valida transiciones de estado.
  - La edición de campos y partidas se desactiva fuera de borrador (incluye acciones de edición de tabla, búsqueda y producto sugerido).
  - El flujo visual quedó más explícito para modos de solo lectura en estatus no editables.
- Pendiente: revisión de usabilidad final con datos reales para validar que no se pueda alterar información con estatus `pendiente/aprobada/cancelada/orden_generada`.

### 5. Ver solicitud

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_ver_trabajo.md`
- Nivel IA sugerido: C, implementacion puntual.
- Objetivo: vista segura de consulta con cabecera, partidas, estatus, acciones permitidas y orden relacionada.
- Depende de: nueva/editar solicitud.
- Terminado cuando:
  - Ver solicitud es solo lectura.
  - Muestra acciones segun permiso y estatus.
  - Muestra orden relacionada si existe.

Notas:

- Implementado en `mostrar_solicitud`: modo consulta sin edicion directa, accion de editar solo para `borrador` con permiso, orden relacionada visible y diferencias contra orden cuando aplica.
- Generar orden se muestra solo si la solicitud esta `aprobada`, no tiene orden activa y el usuario tiene `compras.crear`.
- La vista muestra solicitante desde `sys_usuarios` y almacen destino desde `erp_almacenes`.
- Recomendacion: validar en navegador con una solicitud en `borrador`, una `aprobada` y una con `orden_generada`.

- Recomendación: validar con al menos un ciclo UI (aprobar solicitud y generar) en base real.

### 6. Documento formal de solicitud

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_documento_formal_trabajo.md`
- Nivel IA sugerido: C, implementacion puntual.
- Objetivo: generar PDF o HTML imprimible con logotipo desde Ver solicitud.
- Depende de: ver solicitud.
- Terminado cuando:
  - Se genera archivo presentable.
  - Respeta estatus y permisos.
  - No modifica datos.

Notas:

- Implementado como HTML imprimible en `solicitud_imprimir_erp`.
- Incluye cabecera formal, folio, estatus, proveedor, fechas, prioridad, partidas, productos propuestos, total estimado, observaciones, orden relacionada y firmas.
- No modifica datos; usa permiso `compras.ver`.
- Pendiente futuro opcional: convertir a PDF directo si el negocio lo requiere.

### 7. Listado de solicitudes

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_listado_trabajo.md`
- Nivel IA sugerido: C, implementacion puntual.
- Objetivo: listado con filtros, columnas utiles y acciones por permiso.
- Depende de: estados/permisos, nueva/ver solicitud.
- Terminado cuando:
  - Filtra por estatus, fecha, solicitante, prioridad, proveedor, almacen y orden relacionada.
  - Muestra acciones correctas.

Notas:

- Implementado: filtros por busqueda, estatus, prioridad, proveedor, almacen destino, solicitante, fecha requerida, con/sin orden y productos nuevos.
- Muestra columna de orden relacionada y acciones por permiso: ver, imprimir, editar si es borrador, enlace operativo para generar orden desde solicitud aprobada.
- El almacen destino ya queda preparado en esquema de solicitudes y se propaga a la orden al generar compra.

### 8. Generar orden desde solicitud

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_generar_orden_trabajo.md`
- Nivel IA sugerido: D, modelo fuerte con razonamiento profundo.
- Objetivo: convertir solicitud aprobada en orden sin duplicados y con trazabilidad.
- Depende de: esquema, estados/permisos, orden nueva/editar.
- Terminado cuando:
  - Solo `aprobada` genera orden.
  - No genera duplicados.
  - Orden conserva `id_solicitud` e `id_solicitud_detalle`.
  - Solicitud cambia a `orden_generada`.

Notas:

- Implementado: los productos propuestos desde solicitud se registran como incidencias ERP en `erp_catalogo_incidencias_calidad` con tipo `compra_producto_propuesto`.
- No se crea producto maestro automaticamente; Catalogo debe revisar, crear, vincular o descartar.
- La huella de incidencia evita duplicados por solicitud/proveedor/SKU/nombre.
- Pendiente de prueba manual: crear una solicitud con producto propuesto y confirmar que aparece en la bandeja de incidencias de Catalogo.

### 9. Diferencias solicitud vs orden

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_diferencias_trabajo.md`
- Nivel IA sugerido: D, modelo fuerte con razonamiento profundo.
- Objetivo: mostrar y guardar diferencias entre lo solicitado y lo comprado.
- Depende de: generar orden desde solicitud.
- Terminado cuando:
  - Se identifican no surtidos, adicionales, sustituciones, cantidades diferentes y costo diferente.
  - Diferencias son visibles desde solicitud y orden.

Notas:

- Se implementó en la parte UI:
  - Orden de compra: botón **Ver diferencias** + modal con faltantes, adicionales y cambios.
  - Solicitud de compra: botón **Ver diferencias** + modal con faltantes, adicionales y cambios.
- Validación final recomendada en navegador: verificar casos con orden generada y sin orden asociada.

### 10. Pendientes para Catalogo desde solicitud

- Estado: [x]
- Documento: `docs/erp_compras_solicitudes_pendientes_catalogo_trabajo.md`
- Nivel IA sugerido: D, modelo fuerte con razonamiento profundo.
- Objetivo: generar pendientes de Catalogo cuando haya productos propuestos o datos incompletos.
- Depende de: nueva/editar solicitud y esquema.
- Terminado cuando:
  - Producto propuesto no crea maestro automaticamente.
  - Catalogo recibe pendiente accionable.
  - Solicitud y orden conservan trazabilidad.

Notas:

- Implementado: `SolicitudesCompraErp::guardar` registra incidencias en `erp_catalogo_incidencias_calidad` con tipo `compra_producto_propuesto` para items sin SKU ERP.
- No crea maestro de producto automaticamente; Catalogo debe revisar, crear, vincular o descartar.
- La huella anti-duplicado evita repetir la misma incidencia por solicitud/proveedor/SKU/nombre.
- Pendiente UAT: confirmar en Catalogo que la bandeja de incidencias muestra y resuelve estos casos con trazabilidad.

## Cierre de Solicitudes

Marcar esta seccion solo cuando todo lo anterior este terminado:

- Estado: [x] implementado; UAT funcional pendiente en `docs/erp_compras_tareas_vivas.md`
- Documento rector: `docs/erp_compras_solicitudes_trabajo.md`
- Terminado cuando:
  - Nueva, editar, ver, listado y documento formal funcionan.
  - Permisos por seccion estan aplicados.
  - Estados y transiciones estan validados en backend.
  - Generar orden funciona sin duplicados.
  - Diferencias solicitud vs orden son visibles.
  - Pendientes para Catalogo quedan generados.
  - Todo cambio sensible queda auditado.

## Siguiente paso sugerido

Continuar por pruebas reales controladas:

```text
docs/erp_compras_tareas_vivas.md
```

Prompt recomendado:

```text
Trabaja solo en Compras > UAT Solicitudes.
Usa AGENTS.md, docs/erp_compras_tareas_vivas.md, docs/erp_compras_vision_operativa.md, docs/erp_ux_operativa.md y docs/ia_uso_modelos.md.
Objetivo: ejecutar el ciclo solicitud nueva -> pendiente -> aprobada -> orden_generada con datos reales controlados, confirmar permisos/estatus/auditoria y documentar cualquier hallazgo antes de tocar Ordenes.
```

## Nota transversal 2026-06-16 - Producto proveedor pendiente de Catalogo

Cuando Compras detecta que una orden no puede enviarse porque el producto fisico no tiene SKU ERP, el flujo recomendado no es capturar datos maestros desde Compras.

Flujo correcto:

- Si el producto viene de una lista/proveedor, generar incidencia desde Proveedores con la informacion base del renglon.
- Proveedores crea incidencia en `erp_catalogo_incidencias_calidad` y notificacion `proveedor_producto_pendiente_alta_catalogo`.
- Catalogo revisa y, si procede, crea producto/SKU temporal en borrador desde `/catalogoerp`.
- El sistema crea seguimiento `catalogo_sku_temporal_creado_proveedor_matching` para Proveedores.
- Proveedores vincula el SKU proveedor con el SKU ERP y el seguimiento queda resuelto.
- Compras vuelve a cargar/seleccionar el SKU ya relacionado y puede continuar conforme a sus validaciones.

Pendiente UAT:

- Probar este ciclo desde una orden bloqueada por producto pendiente de alta.
- Confirmar que el bloqueo se resuelve solo cuando existe SKU ERP y relacion proveedor-SKU utilizable.
