# ERP Compras - Resultados UAT

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-16  
Objetivo: registrar evidencia de pruebas reales controladas para cerrar Solicitudes y Ordenes de Compra sin depender de memoria o chat.

## Regla de uso

- Ejecutar pruebas en local con datos reales controlados.
- Registrar folio, usuario, fecha, resultado y evidencia.
- Si algo falla, no marcar el escenario como aprobado; crear hallazgo con archivo, endpoint o pantalla afectada.
- No cambiar reglas de negocio durante UAT sin actualizar `docs/erp_compras_plan_modulo.md` y `docs/erp_compras_tareas_vivas.md`.

## Matriz de pruebas

| ID | Escenario | Folio/Orden | Usuario/Rol | Resultado | Evidencia | Hallazgo |
| --- | --- | --- | --- | --- | --- | --- |
| UAT-COM-001 | Solicitud nueva -> pendiente -> aprobada -> orden generada | SC-2026-000001 / OC-2026-000017 | Usuario actual | Aprobado | Usuario reporta flujo completo sin errores |  |
| UAT-COM-002 | Orden directa con productos ERP del proveedor | OC-2026-000018 | Usuario actual | Aprobado | Usuario reporta orden directa generada sin errores |  |
| UAT-COM-003 | Carga XML antes de guardar borrador | OC-2026-000020 | Usuario actual | Aprobado | XML cargado; verificacion DB: 13/13 partidas con JSON fiscal, clave SAT, clave unidad, objeto impuesto e IVA |  |
| UAT-COM-004 | XML con productos adicionales y productos no surtidos | OC-2026-000020 | Usuario actual | Aprobado | Usuario valida carga XML reutilizada: no duplica SKUs, reconoce producto relacionado TP-40372 como registrado, conserva/agrega partidas y permite guardar/reabrir con datos correctos |  |
| UAT-COM-005 | Descuento masivo y reversa con 0% | OC-2026-000020 | Usuario actual | Aprobado | Usuario aplica 50% masivo, guarda y valida totales; despues aplica 0%, guarda y valida reversa con totales correctos |  |
| UAT-COM-006 | Adjuntos PDF, imagen y XML con referencia/observacion | OC-2026-000020 | Usuario actual | Aprobado | Usuario valida PDF correcto e imagen de comprobante visible/descargable despues de reforzar endpoint y miniatura para image/* |  |
| UAT-COM-007 | Enviar orden y crear recepcion de almacen | OC-2026-000020 / REC-OC-20 | Usuario actual | Aprobado | Orden en estatus enviada; recepcion creada con 13 partidas, 105 unidades ordenadas, 0 recibidas y 105 pendientes |  |
| UAT-COM-008 | Recepcion parcial actualiza orden a parcial | OC-2026-000024 / REC-OC-24 | Ejecucion tecnica local | Aprobado | Almacen recibio 2/5 de `SAL-50L`; recepcion y orden quedaron `parcial`; cargo no inventariable siguio fuera de recepcion |  |
| UAT-COM-009 | Recepcion completa actualiza orden a recibida | OC-2026-000024 / REC-OC-24 | Ejecucion tecnica local | Aprobado | Almacen completo 5/5 de `SAL-50L`; recepcion y orden quedaron `recibida`; existencias/movimientos suman 5 |  |
| UAT-COM-010 | Pago parcial y saldo pendiente | OC-2026-000020 | Usuario actual | Aprobado | Pago aplicado por transferencia de 10000.00; saldo recalculado correctamente |  |
| UAT-COM-011 | Nota de credito aplicada y saldo pendiente | OC-2026-000020 | Usuario actual | Aprobado | Nota de credito aplicada por 3000.00; total 13396.89, aplicado 13000.00, saldo pendiente 396.89 |  |
| UAT-COM-012 | Cancelacion bloqueada con recepcion o movimientos aplicados | OC-2026-000020 | Usuario actual | Aprobado | Evidencia no destructiva: orden enviada con pagos aplicados 10000.00 y nota aplicada 3000.00; backend bloquea cancelacion con pagos/notas aplicadas |  |
| UAT-COM-013 | Producto fisico sin SKU ERP bloquea envio | OC-2026-000019 / OC-2026-000016 | Usuario actual | Aprobado | Evidencia de borradores con partidas `producto_nuevo`, `id_sku_erp=0`, `requiere_revision=1`; backend bloquea envio para productos fisicos sin SKU ERP |  |
| UAT-COM-014 | Cargo/servicio no inventariable permite envio sin SKU ERP | OC-2026-000024 / REC-OC-24 | Usuario actual | Aprobado | Orden enviada con cargo; recepcion corregida y verificada con 1 partida fisica, 0 no inventariables | COM-H005 |
| UAT-COM-015 | Ver orden queda en solo lectura | OC-2026-000024 | Usuario actual | Aprobado | Usuario confirma que la vista Ver es solo lectura y no permite modificar datos |  |
| UAT-NOT-001 | Visibilidad por permisos en bandeja de notificaciones |  |  | Pendiente |  |  |
| UAT-NOT-002 | Navbar muestra contador y dropdown de alertas |  |  | Pendiente |  |  |
| UAT-NOT-003 | Bandeja lista, filtra y marca lectura sin resolver |  |  | Pendiente |  |  |
| UAT-NOT-005 | Compras genera alertas para Catalogo/Proveedores/Almacen | OC-2026-000020 / OC-2026-000023 | Usuario actual | Parcial | Compras genero alerta a Catalogo por producto pendiente y alerta a Almacen por orden enviada; falta caso Proveedores |  |
| UAT-NOT-006 | Recepcion de almacen cierra alerta pendiente | OC-2026-000024 / REC-OC-24 | Ejecucion tecnica local | Aprobado | Notificacion 13 de `compra_orden_enviada_recepcion_pendiente` quedo `resuelta` al guardar recepcion parcial |  |
| UAT-NOT-007 | XML con conceptos pendientes genera/cierra alerta |  |  | Pendiente |  |  |

## Prevalidaciones tecnicas

### 2026-06-16 - Guia UAT-NOT

Documentacion IA: Codex GPT-5

Orden recomendado:

1. Abrir `/sistema/notificaciones` con usuario que tenga `notificaciones.ver`.
2. Confirmar que el navbar no rompe ninguna pantalla ERP.
3. Crear una orden en borrador con un producto fisico sin SKU ERP.
4. Guardar borrador y validar alerta `compra_producto_pendiente_alta`.
5. Corregir la partida relacionandola a SKU ERP o eliminandola; guardar y validar alerta `resuelta`.
6. Crear/guardar una partida con SKU ERP pero sin relacion proveedor-SKU y validar alerta `compra_sku_sin_relacion_proveedor`.
7. Enviar orden y validar alerta `compra_orden_enviada_recepcion_pendiente`.
8. Recibir parcialmente o completo desde Almacen y validar que esa alerta quede `resuelta`.
9. Importar XML con conceptos sin coincidencia y validar alerta `compra_xml_conceptos_revision`.
10. Resolver/descartar conceptos XML y validar cierre de alerta.

Consultas de evidencia:

```sql
SELECT id_notificacion, tipo, modulo_origen, entidad_origen, id_entidad_origen,
       area_responsable, permiso_requerido, titulo, prioridad, estatus,
       fecha_registro, fecha_actualizacion, fecha_resolucion
FROM erp_notificaciones
WHERE modulo_origen='compras'
ORDER BY id_notificacion DESC
LIMIT 30;
```

```sql
SELECT id_notificacion, tipo, estatus, titulo, payload_json
FROM erp_notificaciones
WHERE id_entidad_origen = :id_orden_compra
ORDER BY id_notificacion DESC;
```

```sql
SELECT n.id_notificacion, n.tipo, n.estatus, l.id_usuario, l.leida, l.fecha_lectura
FROM erp_notificaciones n
LEFT JOIN erp_notificaciones_lecturas l ON l.id_notificacion=n.id_notificacion
WHERE n.id_entidad_origen = :id_orden_compra
ORDER BY n.id_notificacion DESC;
```

Resultado esperado:

- No se duplican alertas activas para la misma huella.
- `Marcar leida` no cambia `estatus`; solo registra lectura.
- Las correcciones reales cambian `estatus` a `resuelta`.

### 2026-06-17 - UAT-NOT-005 parcial

Evidencia:

- `OC-2026-000023` genero notificacion `compra_producto_pendiente_alta` para area `catalogo`, permiso `catalogo.editar`, prioridad `alta`, estatus `pendiente`.
- `OC-2026-000020` genero notificacion `compra_orden_enviada_recepcion_pendiente` para area `almacen`, permiso `almacen.recibir`, estatus `pendiente`.
- Falta provocar un caso de `compra_sku_sin_relacion_proveedor` desde Compras para aprobar el escenario completo de Catalogo/Proveedores/Almacen.
- La visibilidad depende de `permiso_requerido`.

### 2026-06-16 - UAT-COM-001

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/controladores/Compra.php`.
- Sintaxis PHP correcta en `app/modelos/SolicitudesCompraErp.php`.
- Sintaxis PHP correcta en `app/modelos/OrdenesCompraErp.php`.
- Sintaxis PHP correcta en vistas de solicitudes `formulario.php` y `listado.php`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/solicitudes/listado.js`.

Revision puntual:

- Guardar solicitud usa `/compra/solicitud_guardar_erp`.
- Cambiar estatus usa `/compra/solicitud_estatus_erp`.
- Generar orden usa `/compra/orden_generar_desde_solicitud_erp`.
- `OrdenesCompraErp::generarDesdeSolicitud` crea cabecera, copia partidas desde `erp_compras_solicitudes_detalle`, valida SKU/proveedor, calcula totales y evita orden duplicada activa.
- La prueba funcional en navegador sigue pendiente; no se marca el escenario como aprobado hasta tener folio y evidencia.

### 2026-06-16 - UAT-COM-002

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/controladores/Compra.php`.
- Sintaxis PHP correcta en `app/modelos/OrdenesCompraErp.php`.
- Sintaxis PHP correcta en `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.

Revision puntual:

- Crear/guardar orden directa usa `/compra/orden_guardar_erp`.
- Buscar productos usa `/compra/orden_buscar_skus_erp`.
- Reabrir orden usa `/compra/orden_consultar_erp`.
- `OrdenesCompraErp::guardar` crea cabecera directa si no hay `id_orden_compra`, exige al menos una partida, valida que el SKU pertenezca al proveedor y guarda totales calculados en backend.
- Si un producto esperado no aparece en busqueda, debe revisarse su relacion activa en `erp_catalogo_sku_proveedores` antes de asumir fallo de UI.
- La prueba funcional en navegador sigue pendiente; no se marca el escenario como aprobado hasta tener folio y evidencia.

### 2026-06-16 - UAT-COM-003

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/controladores/Compra.php`.
- Sintaxis PHP correcta en `app/modelos/ComprasXmlErp.php`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.

Revision puntual:

- La carga XML desde orden nueva sin ID usa `/compra/orden_xml_parse_erp`.
- El parseo temporal exige permiso `compras.crear`.
- El JS envia `id_proveedor` seleccionado para enriquecer conceptos contra `erp_catalogo_sku_proveedores`.
- `ComprasXmlErp::parsear` valida archivo, limite 5 MB, bloquea `DOCTYPE/ENTITY`, lee CFDI y no persiste documento fiscal.
- `ComprasXmlErp::enriquecerConceptosConProveedor` intenta reconocer SKU por `NoIdentificacion`, SKU ERP, SKU proveedor o descripcion exacta normalizada.
- `incorporarConceptosAItems` agrega o actualiza partidas en pantalla sin borrar productos existentes.
- La prueba funcional en navegador sigue pendiente; no se marca el escenario como aprobado hasta tener folio/evidencia.

### 2026-06-16 - UAT-COM-004

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/modelos/ComprasXmlErp.php`.
- Sintaxis PHP correcta en `app/modelos/OrdenesCompraErp.php`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.

Revision puntual:

- `OrdenesCompraErp::compararConSolicitud` detecta faltantes, adicionales y cambios entre solicitud y orden.
- `Compra::orden_diferencias_solicitud_erp` expone esa comparacion para la vista de orden.
- `ComprasXmlErp::sincronizarPendientesOrden` registra en `erp_compras_ordenes_productos_atencion` las partidas esperadas no localizadas en XML con motivo `no_incluido_xml`.
- La conciliacion XML muestra conceptos sin relacion y pendientes de productos no incluidos.
- La carga XML no debe borrar productos previos de solicitud; los no incluidos se reflejan como pendientes o diferencias.
- La prueba funcional en navegador sigue pendiente; no se marca el escenario como aprobado hasta tener folio/evidencia.

### 2026-06-16 - UAT-COM-005

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.
- Sintaxis PHP correcta en `app/controladores/Compra.php`.

Revision puntual:

- El descuento masivo opera sobre partidas seleccionadas.
- Permite aplicar porcentaje de 0 a 100.
- Aplicar 0% funciona como reversa porque recalcula descuento nuevo en cero.
- El frontend conserva evento con fecha cliente, porcentaje, motivo, partida, costo base, descuento anterior y descuento nuevo.
- `orden_guardar_erp` recibe `descuento_masivo_eventos` y registra auditoria `orden_descuento_masivo` si la orden se guarda correctamente.
- La prueba funcional en navegador sigue pendiente; no se marca el escenario como aprobado hasta tener folio/evidencia.

### 2026-06-16 - UAT-COM-006

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/modelos/AdjuntosCompraErp.php`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.

Revision puntual:

- Listar adjuntos usa `/compra/orden_adjuntos_listar_erp` con permiso `compras.ver`.
- Subir adjunto usa `/compra/orden_adjunto_subir_erp` con permiso `compras.adjuntos`.
- Descargar/ver adjunto usa `/compra/orden_adjunto_archivo_erp` con permiso `compras.ver`.
- Cancelar adjunto usa `/compra/orden_adjunto_cancelar_erp` con permiso `compras.adjuntos`.
- El frontend envia `tipo_documento`, `referencia`, `observaciones` y `archivo`.
- El backend guarda `referencia` y `observaciones`, valida MIME, limita a 15 MB, evita duplicado activo por hash y almacena fuera de `public` en `storage/erp/compras/ordenes/`.
- La prueba funcional en navegador sigue pendiente; no se marca el escenario como aprobado hasta tener folio/evidencia.

### 2026-06-16 - UAT-COM-007

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/controladores/Compra.php`.
- Sintaxis PHP correcta en `app/modelos/OrdenesCompraErp.php`.
- Sintaxis PHP correcta en `app/modelos/Almacenes.php`.
- Sintaxis PHP correcta en `app/modelos/AlmacenEsquema.php`.

Revision puntual:

- Enviar orden usa `/compra/orden_guardar_erp` con `estatus=enviada`.
- `Compra::orden_guardar_erp` exige permiso `compras.aprobar` cuando el destino es `enviada`.
- `OrdenesCompraErp::guardar` valida moneda, almacen destino, total mayor a cero, costo unitario y politica de envio antes de cambiar estatus.
- Al enviar, `Compra::preparar_recepcion_almacen_si_enviada` valida que existan tablas/columnas de almacen.
- `Almacenes::preparar_recepcion_desde_orden_compra` crea recepcion solo si la orden esta `enviada`.
- Si ya existe recepcion para la orden, reutiliza la cabecera y solo registra detalles faltantes; los detalles existentes se sincronizan para evitar duplicados.
- Evidencia funcional 2026-06-17: `OC-2026-000020` quedo en estatus `enviada` y genero `REC-OC-20` en `erp_almacen_recepciones`.
- Detalle de recepcion: 13 partidas, 105 unidades ordenadas, 0 recibidas y 105 pendientes.
- Escenario aprobado para Compras. La recepcion fisica parcial/completa se conserva para el modulo Almacen.

### 2026-06-16 - UAT-COM-013

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/modelos/OrdenesCompraErp.php`.

Revision puntual:

- `OrdenesCompraErp::validarPoliticaEnvioOrdenErp` bloquea enviar productos fisicos sin SKU ERP.
- Las partidas tipo `servicio`, `cargo`, `no_inventariable` o `adicional` no bloquean por falta de SKU ERP.
- Al guardar una orden, `OrdenesCompraErp::registrarIncidenciasCatalogoDesdeOrden` genera/actualiza incidencias en `erp_catalogo_incidencias_calidad` para productos fisicos sin SKU ERP y para SKUs sin relacion proveedor.
- La incidencia usa huella unica por orden/proveedor/SKU/nombre para evitar duplicados.
- Si la tabla de incidencias no estuviera lista, el guardado de la orden no se rompe; la incidencia se considera una alerta operativa no bloqueante.
- Evidencia no destructiva 2026-06-17: existen borradores `OC-2026-000019` y `OC-2026-000016` con partidas `producto_nuevo`, `id_sku_erp=0` y `requiere_revision=1`.
- La politica backend bloquea `estatus=enviada` para esas partidas si no se resuelven en Catalogo/Proveedores o se reclasifican como cargo/servicio no inventariable.
- Escenario aprobado por evidencia de regla y datos existentes.

### 2026-06-17 - UAT-COM-010 y UAT-COM-011

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta en `app/modelos/PagosCompraErp.php`.
- Sintaxis PHP correcta en `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.

Revision puntual:

- `orden_finanzas_consultar_erp` requiere `finanzas.ver`.
- Registrar/cancelar pagos y notas requiere `finanzas.operar`.
- El backend bloquea movimientos financieros en orden `borrador` o `cancelada`.
- Pagos `aplicado` o `conciliado` reducen saldo; pagos `pendiente` no reducen saldo.
- Notas de credito `aplicada` reducen saldo; notas `pendiente` no reducen saldo.
- Backend evita sobrepago contra saldo pendiente.
- Pago por tarjeta o transferencia exige folio/referencia.
- Nota de credito exige folio/referencia.
- La UI valida referencia antes de enviar y el backend conserva la validacion como autoridad.
- El listado de ordenes muestra accion `Seguimiento` para ordenes no canceladas cuando el usuario tiene permisos operativos.
- `Seguimiento` mantiene productos/cabecera en solo lectura, pero habilita pagos/notas/adjuntos segun permisos.
- Evidencia funcional 2026-06-17:
  - Orden: `OC-2026-000020`.
  - Total orden: 13396.89.
  - Pago aplicado: transferencia por 10000.00, referencia `Pago COT. 34543`.
  - Nota de credito aplicada: 3000.00, referencia `NOTA: 3432`.
  - Saldo pendiente: 396.89.
- Escenarios aprobados.

### 2026-06-17 - UAT-COM-012 y UAT-COM-015

Documentacion IA: Codex GPT-5

Resultado:

- Sintaxis PHP correcta previamente validada en `app/modelos/OrdenesCompraErp.php`.
- Sintaxis JS correcta en `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.

Revision puntual:

- Cancelar orden requiere permiso `compras.cancelar`.
- Backend solo permite cancelar orden en `borrador` o `enviada`.
- Backend bloquea cancelacion si existe cantidad recibida.
- Backend bloquea cancelacion si la recepcion de almacen ya no esta `pendiente` o `cancelada`.
- Backend bloquea cancelacion con pagos o notas aplicadas.
- Si cancela una orden pendiente de recepcion, cancela la recepcion pendiente relacionada.
- Evidencia no destructiva 2026-06-17: `OC-2026-000020` tiene pagos aplicados por 10000.00, nota aplicada por 3000.00 y saldo pendiente 396.89. Por regla backend, esa orden queda bloqueada para cancelacion.
- Vista `ver_orden_compra` entra con `modo=ver` y sin permisos operativos.
- UI oculta controles `.orden-edicion`, deshabilita cabecera y mantiene fiscal en modo consulta.
- Pagos, notas, adjuntos y XML quedan visibles segun permiso de consulta, sin acciones operativas en modo ver.
- `UAT-COM-012` aprobado sin ejecutar cancelacion destructiva. `UAT-COM-015` sigue pendiente de confirmacion visual en navegador.

## Guia de ejecucion por escenario

### UAT-COM-001 - Solicitud nueva -> pendiente -> aprobada -> orden generada

Precondiciones:

- Usuario con permisos `compras.ver`, `compras.crear`, `compras.editar` y `compras.aprobar`.
- Proveedor ERP con al menos un SKU activo en `erp_catalogo_sku_proveedores`.
- Almacen destino activo.

Pasos:

1. Crear solicitud en borrador con proveedor, almacen destino y fecha requerida.
2. Agregar al menos un producto ERP del proveedor.
3. Guardar borrador y reabrir.
4. Enviar a pendiente.
5. Aprobar la solicitud.
6. Generar orden desde la solicitud.
7. Abrir la solicitud en modo ver.

Resultado esperado:

- La solicitud pasa por `borrador -> pendiente -> aprobada -> orden_generada`.
- La orden conserva proveedor, almacen destino, partidas e identificador de solicitud.
- No se genera una segunda orden si se intenta repetir la accion.
- La solicitud en modo ver muestra orden relacionada y diferencias cuando aplique.

### UAT-COM-002 - Orden directa con productos ERP del proveedor

Precondiciones:

- Usuario con permisos `compras.ver`, `compras.crear` y `compras.editar`.
- Proveedor con relaciones activas proveedor-SKU en ERP.

Pasos:

1. Crear orden directa.
2. Seleccionar proveedor.
3. Buscar un producto que pertenezca al proveedor.
4. Agregarlo a partidas.
5. Capturar cantidad, costo capturado, costo sin IVA, descuento si aplica y precio incluye IVA.
6. Guardar borrador.
7. Reabrir la orden.

Resultado esperado:

- La busqueda solo usa fuente ERP confiable.
- La partida conserva SKU ERP, SKU proveedor si existe, costo, impuestos, descuento y totales.
- Al reabrir no cambia el costo capturado ni recalcula doble el IVA.

### UAT-COM-003 - Carga XML antes de guardar borrador

Precondiciones:

- XML de factura valido del proveedor seleccionado.
- Orden nueva sin ID todavia.

Pasos:

1. Crear orden directa.
2. Seleccionar proveedor.
3. Cargar XML desde la zona de productos.
4. Revisar conceptos importados.
5. Guardar borrador.
6. Reabrir orden.

Resultado esperado:

- El XML se puede cargar sin guardar manualmente primero.
- Los conceptos se convierten en partidas de orden.
- Cuando el XML coincide con SKU ERP/proveedor, la partida queda relacionada.
- Los productos no identificados quedan marcados como pendientes o producto nuevo, no como producto ERP falso.

### UAT-COM-004 - XML con adicionales y no surtidos

Precondiciones:

- Orden generada desde solicitud con productos previos.
- XML que no coincida exactamente con todo lo solicitado.

Pasos:

1. Abrir orden en borrador.
2. Cargar XML.
3. Revisar partidas solicitadas no incluidas en XML.
4. Revisar conceptos XML adicionales.
5. Guardar y reabrir.

Resultado esperado:

- El XML no borra automaticamente productos de la solicitud.
- Los adicionales quedan visibles y accionables.
- Los no surtidos quedan detectables para futuras solicitudes o seguimiento.

### UAT-COM-005 - Descuento masivo y reversa

Precondiciones:

- Orden en borrador con dos o mas partidas.

Pasos:

1. Aplicar descuento masivo por porcentaje.
2. Capturar motivo si se solicita.
3. Revisar que todas las partidas actualicen subtotal, impuesto y total.
4. Guardar y reabrir.
5. Aplicar 0% para revertir.
6. Guardar nuevamente.

Resultado esperado:

- El descuento se aplica de forma consistente en todas las partidas.
- La reversa deja descuentos en cero.
- Se registra auditoria `orden_descuento_masivo` con antes/despues.

### UAT-COM-006 - Adjuntos PDF, imagen y XML

Precondiciones:

- Usuario con permiso `compras.adjuntos`.
- Archivos de prueba PDF, imagen y XML.

Pasos:

1. Subir PDF con tipo, referencia y observacion.
2. Subir imagen con tipo, referencia y observacion.
3. Subir XML como adjunto documental si aplica.
4. Reabrir orden.
5. Ver/descargar cada archivo.
6. Cancelar un adjunto con motivo.

Resultado esperado:

- Los adjuntos conservan tipo, referencia y observacion.
- El archivo se sirve por endpoint autenticado.
- Al cancelar, el registro queda historico y el archivo fisico se elimina o queda marcado segun regla vigente.

### UAT-COM-007 - Enviar orden y crear recepcion de almacen

Precondiciones:

- Orden en borrador con productos fisicos relacionados a SKU ERP.
- Usuario con permiso `compras.aprobar`.

Pasos:

1. Cambiar orden a `enviada`.
2. Guardar.
3. Abrir listado o modulo de recepciones.

Resultado esperado:

- La orden cambia a `enviada`.
- Se crea o prepara recepcion de almacen una sola vez.
- No se duplican detalles de recepcion si se vuelve a consultar o guardar sin cambios.

### UAT-COM-008 - Recepcion parcial actualiza orden

Precondiciones:

- Orden enviada con recepcion preparada.
- Usuario con permiso `almacen.recibir`.

Pasos:

1. Abrir recepcion de almacen.
2. Recibir menos cantidad que la ordenada.
3. Guardar recepcion.
4. Consultar orden.

Resultado esperado:

- La recepcion queda parcial.
- La orden cambia a `parcial`.
- Compras ya no puede editar partidas operativas desde orden.

Evidencia 2026-06-18:

- Respaldo previo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_081947_antes_uat_alm_rec_oc_24.sql`.
- Folio usado: `OC-2026-000024` / `REC-OC-24`.
- Se recibieron `2.0000` de `5.0000` unidades de `SAL-50L`.
- Recepcion `REC-OC-24`: estatus `parcial`, pendiente `3.0000`.
- Orden `OC-2026-000024`: estatus `parcial`.
- Detalle de orden `SAL-50L`: cantidad recibida `2.000000`.
- Cargo no inventariable: permanece como `tipo_item=cargo`, cantidad recibida `0.000000`, sin detalle de recepcion.
- Inventario: lote `LOT-2-18`, movimiento `18`, cantidad `2.0000`.

Resultado: Aprobado desde flujo Almacen.

### UAT-COM-009 - Recepcion completa actualiza orden

Precondiciones:

- Orden enviada o parcial.

Pasos:

1. Completar cantidades recibidas.
2. Guardar recepcion.
3. Consultar orden.

Resultado esperado:

- La recepcion queda completa.
- La orden cambia a `recibida`.
- La orden queda en vista de seguimiento, no editable desde Compras.

Evidencia 2026-06-18:

- Folio usado: `OC-2026-000024` / `REC-OC-24`.
- Se recibieron las `3.0000` unidades restantes de `SAL-50L`.
- Recepcion `REC-OC-24`: estatus `recibida`, recibida `5.0000`, pendiente `0.0000`, fecha cierre `2026-06-18 00:20:47`.
- Orden `OC-2026-000024`: estatus `recibida`.
- Detalle de orden `SAL-50L`: cantidad recibida `5.000000`.
- Inventario: lotes `LOT-2-18` por `2.0000` y `LOT-2-19` por `3.0000`; movimientos `18` y `19`; existencia disponible total `5.0000`.
- Reintento posterior sobre recepcion cerrada fue bloqueado con mensaje `La recepcion ya no permite movimientos`; no se duplicaron lotes ni movimientos.

Resultado: Aprobado desde flujo Almacen.

### UAT-COM-010 - Pago parcial y saldo pendiente

Precondiciones:

- Orden enviada o recibida.
- Usuario con permisos `finanzas.ver` y `finanzas.operar`.

Pasos:

1. Consultar resumen financiero.
2. Registrar pago parcial con metodo, monto y referencia.
3. Reabrir orden.

Resultado esperado:

- El pago aplicado reduce saldo pendiente.
- No se permite sobrepago.
- El saldo se calcula desde backend.

### UAT-COM-011 - Nota de credito aplicada

Precondiciones:

- Orden con saldo pendiente.
- Usuario con permiso `finanzas.operar`.

Pasos:

1. Registrar nota de credito con folio/referencia y monto aplicado.
2. Reabrir orden.

Resultado esperado:

- La nota aplicada reduce saldo.
- La referencia queda visible para Contabilidad.
- No se permite aplicar nota mayor al saldo segun regla vigente.

### UAT-COM-012 - Cancelaciones bloqueadas

Precondiciones:

- Orden con recepcion iniciada o pagos/notas aplicadas.

Pasos:

1. Intentar cancelar la orden.
2. Intentar cancelar pago/nota/adjunto desde sus acciones permitidas.

Resultado esperado:

- La orden no se cancela si tiene recepcion iniciada o movimientos financieros aplicados.
- Pagos/notas/adjuntos se cancelan logicamente con motivo cuando la regla lo permita.

### UAT-COM-013 - Producto fisico sin SKU ERP bloquea envio

Precondiciones:

- Orden en borrador con una partida fisica sin SKU ERP.

Pasos:

1. Agregar producto nuevo o no relacionado como producto fisico.
2. Intentar enviar orden.

Resultado esperado:

- El backend bloquea el envio.
- El mensaje indica que un producto fisico inventariable requiere SKU ERP.
- Se conserva el borrador para corregir o reclasificar.

### UAT-COM-014 - Cargo/servicio no inventariable permite envio sin SKU ERP

Precondiciones:

- Orden en borrador con productos ERP validos.

Pasos:

1. Agregar una partida sin SKU ERP.
2. Clasificarla como cargo, servicio, adicional o no inventariable.
3. Enviar orden.

Resultado esperado:

- La partida impacta total.
- No bloquea envio por falta de SKU ERP.
- No debe generar movimiento de inventario en recepcion.

Prevalidacion tecnica 2026-06-17:

- La vista de orden ahora permite agregar un `Cargo o servicio` desde el bloque de productos, sin depender de XML ni de catalogo.
- La partida se genera con `tipo_item` no inventariable, `id_sku_erp=0` y `requiere_revision=0`.
- El backend ya exenta `servicio`, `cargo`, `adicional` y `no_inventariable` de la regla que bloquea productos fisicos sin SKU ERP.
- El backend valida cargos/servicios por tipo + concepto, no por SKU, para permitir guardarlos sin codigo.
- Evidencia parcial `OC-2026-000023`: `Empaque y maniobra` quedo guardado como `cargo`, `id_sku_erp=0`, `requiere_revision=0`, subtotal/total 250.00.
- La misma orden guardo `SKU-Prueba / Producto de prueba` como `producto_nuevo`, `requiere_revision=1`; genero incidencia `compra_producto_pendiente_alta` y notificacion a Catalogo.
- Pendiente de aprobacion real: crear o editar una orden en borrador, agregar un cargo, enviarla y confirmar que la recepcion no genera detalle inventariable para esa partida.

### UAT-COM-015 - Ver orden queda en solo lectura

Precondiciones:

- Orden existente en cualquier estatus.

Pasos:

1. Abrir accion `Ver` desde listado.
2. Revisar cabecera, productos, XML, adjuntos, pagos/notas y recepcion.
3. Intentar modificar cantidad, costos, adjuntos o pagos desde esa vista.

Resultado esperado:

- No hay inputs editables ni acciones operativas.
- Editar solo aparece desde listado cuando la orden esta en `borrador` y el usuario tiene permiso.

Prevalidacion tecnica 2026-06-17:

- `/compra/ver_orden_compra/{id}` carga el formulario con `modo=ver`, `puede_aprobar=false`, `puede_cancelar=false`, `puede_operar_finanzas=false` y `puede_gestionar_adjuntos=false`.
- El JS calcula `puedeEditar = modo === "editar" && estatus === "borrador"`; en `modo=ver` ejecuta `deshabilitar()`, oculta `.orden-edicion` y deshabilita cabecera.
- El listado siempre muestra `Ver`; `Editar` solo aparece para ordenes `borrador` con permiso, y `Seguimiento` queda separado para pagos/notas/adjuntos segun permisos.
- Evidencia funcional 2026-06-17: usuario confirma con `OC-2026-000024` que `Ver` es solo lectura y no permite modificar datos.
- `UAT-COM-015` aprobado.

## Hallazgos

Usar este formato por cada error:

```text
ID:
Fecha:
Usuario/Rol:
Folio/Orden:
Pantalla:
Endpoint:
Pasos:
Resultado esperado:
Resultado obtenido:
Evidencia:
Prioridad:
Decision:
```

### COM-H001 - Orden desde solicitud no permitia agregar productos/XML

Fecha: 2026-06-16  
Usuario/Rol: Usuario actual  
Folio/Orden: SC-2026-000001 / OC-2026-000017  
Pantalla: Compras > Ordenes > Editar orden  
Endpoint: `/compra/orden_guardar_erp`, `/compra/orden_buscar_skus_erp`, `/compra/orden_xml_parse_erp`  
Resultado esperado: una orden generada desde solicitud, mientras este en borrador, debe permitir agregar productos adicionales y cargar XML para reflejar cambios reales de compra.  
Resultado obtenido: el bloque de busqueda/XML se ocultaba para ordenes con origen `solicitud`; ademas backend exigia `id_solicitud_detalle` para cualquier SKU en orden con solicitud origen.  
Prioridad: Alta  
Decision: Corregido. La UI muestra busqueda/XML tambien en ordenes desde solicitud en borrador, y backend solo valida `id_solicitud_detalle` cuando la partida declara venir de la solicitud; las nuevas partidas quedan como adicionales.

### COM-H002 - XML no llenaba claves fiscales SAT en partidas

Fecha: 2026-06-16  
Usuario/Rol: Usuario actual  
Pantalla: Compras > Ordenes > Carga XML  
Endpoint: `/compra/orden_xml_parse_erp`  
Resultado esperado: al cargar un XML CFDI, cada concepto debe precargar clave SAT, clave unidad SAT, unidad, objeto impuesto e IVA disponible.  
Resultado obtenido: backend leia `clave_producto_sat` y `clave_unidad_sat`, pero el JS solo buscaba alias incompletos y no los pasaba a `datos_fiscales`.  
Prioridad: Alta  
Decision: Corregido. El mapeo JS ahora contempla `clave_producto_sat` y `clave_unidad_sat` tanto en XML temporal como en conciliacion formal.

### COM-H003 - Controles masivos de costo/descuento no eran intuitivos

Fecha: 2026-06-16  
Usuario/Rol: Usuario actual  
Pantalla: Compras > Ordenes > Tabla de partidas  
Resultado esperado: el capturista debe cambiar costo sin/con IVA y descuento masivo desde el encabezado de la columna afectada, sin pasos extra.  
Resultado obtenido: descuento masivo y precio incluye IVA estaban como controles separados, dependian de seleccion previa y hacian mas lenta la captura.  
Prioridad: Media-Alta  
Decision: Corregido. `Costo capturado` ahora tiene check masivo `Sin IVA` en encabezado; `Descuento` tiene input `%` en encabezado que aplica a todas las partidas. Se conserva eliminar masivo con seleccion.

### COM-H004 - Orden no re-detectaba relacion proveedor/SKU al reabrir

Fecha: 2026-06-16  
Usuario/Rol: Usuario actual  
Pantalla: Compras > Ordenes > Editar orden  
Endpoint: `/compra/orden_consultar_erp`  
Resultado esperado: si Catalogo o Proveedores relacionan un SKU despues de guardar la orden, al volver a abrirla Compras debe reconocer la relacion sin obligar a borrar y recapturar cantidad, costos o descuento.  
Resultado obtenido: la orden mostraba la partida como pendiente/no relacionada porque se consultaba solo el detalle guardado originalmente.  
Prioridad: Alta  
Decision: Corregido. Al consultar una orden, el modelo intenta enriquecer partidas fisicas sin relacion usando una coincidencia unica activa contra `erp_catalogo_sku_proveedores` y `erp_catalogo_skus`. No modifica la base de datos durante la consulta; la relacion queda persistida cuando el usuario vuelve a guardar la orden.

Revision adicional:

- En `OC-2026-000020`, `SFF-03` ya aparece relacionado correctamente en detalle con `id_sku_erp=171`, `id_sku_proveedor=62`, `producto_registrado=1` y `requiere_revision=0`.
- La alerta pendiente encontrada corresponde a `SFF-303`, que no tiene relacion activa para proveedor `SUNNY` ni SKU ERP activo detectado.
- Se reforzo el backend para comparar SKU exacto y SKU normalizado sin guiones/espacios al reabrir la orden.
- Se corrigio el cierre de incidencias/notificaciones para que, al guardar la orden, las alertas de productos ya resueltos queden en `resuelta` y no permanezcan como ruido operativo.

### COM-H005 - Recepcion incluyo cargo no inventariable

Fecha: 2026-06-17  
Usuario/Rol: Usuario actual  
Folio/Orden: `OC-2026-000024` / `REC-OC-24`  
Pantalla: Compras > Ordenes / Almacen > Recepciones  
Endpoint: `/compra/orden_guardar_erp` -> preparacion de recepcion desde orden  
Resultado esperado: al enviar una orden con producto fisico + cargo no inventariable, la recepcion de almacen debe incluir solo productos que controlan inventario.  
Resultado obtenido: la recepcion incluyo tambien `Empaque y maniobra` como detalle pendiente.  
Evidencia: `REC-OC-24` genero 2 detalles; uno de ellos corresponde a `tipo_item=cargo`.  
Prioridad: Alta  
Decision: Corregido en codigo para futuras recepciones. `Almacenes::consultar_detalle_orden_compra_para_recepcion` excluye `servicio`, `cargo`, `adicional` y `no_inventariable`. Falta autorizacion para limpiar el detalle ya insertado en `REC-OC-24`.

Revision posterior:

- Respaldo externo generado antes de limpiar base: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260617_232935_antes_limpieza_rec_oc_24.sql`.
- Se elimino 1 detalle no inventariable de `REC-OC-24`.
- Verificacion final: `REC-OC-24` conserva solo la partida fisica `SAL-50L`, cantidad ordenada 5; total de no inventariables en recepcion: 0.
- `UAT-COM-014` queda aprobado.

## Criterio de cierre

Compras queda listo para pruebas reales extendidas cuando:

- Todos los escenarios criticos terminan en `Aprobado`.
- Los hallazgos criticos quedan corregidos o bloqueados con decision del dueno.
- La documentacion viva refleja cualquier cambio de regla, UX o permiso descubierto durante la prueba.
