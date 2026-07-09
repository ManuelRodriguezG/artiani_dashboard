# ERP Compras - Punto actual: editar orden de compra

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Guia operativa para prompts y tareas del punto editar orden  
Aplica a: orden directa guardada, orden generada desde solicitud y orden en seguimiento  
Vista relacionada: `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

## Proposito

Este documento separa el plan de `editar orden de compra` del plan de `nueva orden`, porque editar no siempre significa capturar libremente. Una orden editada puede venir de:

- Una orden directa creada desde la vista de nueva orden.
- Una orden generada desde una solicitud aprobada.
- Una orden ya enviada que solo debe consultarse o tener acciones controladas.
- Una orden con XML, adjuntos, pagos, notas o recepciones relacionadas.

La meta es permitir correcciones y enriquecimiento sin perder trazabilidad, sin duplicar documentos y sin romper reglas de solicitud, almacen, inventario, catalogo o finanzas.

## Principio central

Editar una orden debe respetar su origen y estatus.

- Si esta en `borrador`, se puede editar con una experiencia similar a nueva orden.
- Si viene de solicitud, debe conservar relacion con solicitud y marcar diferencias.
- Si esta `enviada`, ya no es captura libre; cualquier cambio debe estar controlado.
- Si esta `parcial` o `recibida`, Almacen ya intervino y Compras no debe alterar partidas como si nada.
- Si esta `cancelada`, debe ser solo lectura salvo acciones administrativas autorizadas.

## Archivos exactos de trabajo

Frontend:

- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Backend:

- `app/controladores/Compra.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/ComprasXmlErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/modelos/CatalogoErpEsquema.php` cuando falten campos ERP maestros o fiscales.

Relacionados bajo demanda:

- `app/modelos/PagosCompraErp.php`
- `app/modelos/AdjuntosCompraErp.php`
- `app/modelos/Almacenes.php`
- `app/modelos/CatalogoErpDatos.php`

No tocar salvo necesidad justificada:

- Flujo legado de compras.
- Tablas `ecom_*` como fuente principal.
- Inventario fisico desde Compras.
- Recepcion de almacen desde la vista de edicion de orden.

## Tipos de edicion

### 1. Editar orden directa en borrador

Caso:

- La orden fue creada desde nueva orden.
- Aun no se envia a almacen.
- No hay recepcion.

Debe permitir:

- Cambiar proveedor si no hay reglas que lo impidan.
- Cambiar almacen destino.
- Agregar/quitar productos ERP.
- Cargar XML y detectar productos ERP en la misma tabla.
- Agregar productos nuevos/propuestos.
- Agregar cargos/gastos no inventariables.
- Ajustar precios sin IVA/con IVA, impuestos y descuentos.
- Completar datos fiscales de partida como propuesta.
- Guardar borrador o enviar.

Criterio:

- Funciona igual que nueva orden, pero conservando `id_orden_compra` y auditoria de cambios.

### 2. Editar orden generada desde solicitud

Caso:

- La orden nace de una solicitud aprobada.
- Las partidas tienen referencia a solicitud/detalle.

Debe permitir:

- Revisar lo solicitado vs lo comprado.
- Ajustar cantidades/costos si la regla de negocio lo permite.
- Agregar productos adicionales si se justifica.
- Marcar productos solicitados no surtidos.
- Detectar productos XML no solicitados.
- Mantener trazabilidad a solicitud.

Reglas:

- No perder `id_solicitud` ni `id_solicitud_detalle`.
- No borrar silenciosamente partidas heredadas de solicitud.
- Si una partida solicitada se elimina o cambia, registrar diferencia.
- Si XML trae conceptos adicionales, marcarlos como no solicitados o producto nuevo.
- Si una solicitud ya genero orden activa, no generar duplicados.

Criterio:

- La orden muestra y conserva que viene de solicitud.
- Diferencias solicitud vs orden quedan visibles y accionables.

### 3. Editar orden enviada

Caso:

- Compras ya envio orden.
- Puede existir recepcion preparada.

Debe permitir solo acciones controladas:

- Consultar orden.
- Adjuntar documentos.
- Registrar informacion financiera si permisos lo permiten.
- Cargar XML como documento/soporte si la regla lo permite.
- Cancelar solo si no hay pagos/notas aplicadas ni recepcion, o si existe flujo autorizado.

No debe permitir libremente:

- Cambiar proveedor.
- Cambiar almacen destino.
- Quitar productos.
- Cambiar cantidades/costos sin flujo de ajuste.
- Marcar parcial/recibida desde Compras.

Criterio:

- La UI distingue claramente modo edicion vs modo seguimiento.

### 4. Orden parcial o recibida

Caso:

- Almacen ya recibio parcial o totalmente.

Debe ser principalmente consulta/seguimiento:

- Ver resumen de recepcion.
- Ver pendientes/faltantes.
- Ver pagos/notas/adjuntos.
- Ver diferencias contra XML si existen.

No debe permitir:

- Cambiar partidas recibidas desde Compras.
- Cancelar libremente.
- Afectar inventario.

Criterio:

- Cualquier correccion posterior se maneja como ajuste controlado, no como edicion normal.

### 5. Orden cancelada

Caso:

- La orden esta cerrada sin operacion activa.

Debe permitir:

- Consulta.
- Adjuntos/auditoria solo si se define.

No debe permitir:

- Edicion operativa.
- Pagos nuevos.
- Recepcion.
- XML que cambie partidas.

## Reglas por origen

### Origen directa

- `origen = directa`.
- La tabla puede iniciar vacia o desde XML/productos.
- No requiere comparacion contra solicitud.
- Pendientes se generan contra Catalogo/Costos/Fiscales.

### Origen solicitud

- `origen = solicitud`.
- La tabla debe conservar referencia a solicitud.
- Debe existir vista o indicadores de:
  - solicitado,
  - comprado,
  - no surtido,
  - adicional no solicitado,
  - modificado.
- La solicitud no debe quedar editable por la orden; la orden solo registra diferencias.

### Origen XML

- XML no es origen de la orden completa necesariamente; es origen de partidas o datos.
- Un XML puede enriquecer una orden directa o una orden desde solicitud.
- XML debe detectar productos ERP igual que el buscador manual.
- XML sin coincidencia genera producto nuevo/propuesto.
- La conciliacion no debe obligar a usar una segunda tabla visual repetitiva.

## Esquema ERP propio requerido

Editar orden depende de la misma decision estructural de nueva orden:

- ERP debe tener sus propias tablas y campos.
- No depender de `ecom_*`.
- Auditar estructura vieja antes de adaptar codigo nuevo.
- No eliminar campos heredados sin plan de migracion.

Para editar orden, confirmar o agregar estructura para:

- Origen de orden: directa, solicitud.
- Referencia a solicitud y detalle de solicitud.
- Estado de diferencia contra solicitud.
- Tipo de partida: inventariable, no inventariable/servicio, cargo/gasto.
- Origen de partida: ERP, XML, solicitud, manual.
- Datos fiscales usados/propuestos por partida.
- Bitacora o auditoria de cambios relevantes.
- Pendientes generados desde diferencias.

## Pantalla esperada para editar

La pantalla puede reutilizar el formulario de orden, pero debe comportarse segun modo:

Cabecera:

- Mostrar folio de orden.
- Mostrar estatus.
- Mostrar origen: directa o desde solicitud.
- Si viene de solicitud, mostrar enlace o referencia.
- Bloquear campos segun estatus.

Tabla principal:

- Una sola tabla operativa de partidas.
- Mostrar estado: registrado, nuevo, XML, cargo, solicitado, adicional, no surtido, pendiente.
- Detectar productos ERP desde XML igual que el buscador.
- Permitir productos nuevos solo en borrador.
- Permitir cargos/gastos no inventariables solo cuando estatus lo permita.
- Mantener precios con/sin IVA claros y sincronizados.
- Mantener descuento general junto a columna descuento.

Secciones:

- Adjuntos: visible cuando existe orden.
- Finanzas: visible segun permisos.
- Resumen de solicitud: visible si origen es solicitud.
- Resumen de recepcion: visible si orden enviada/parcial/recibida.
- Documentos fiscales/conciliacion: no como seccion principal repetitiva; si se conserva, que sea resumen o herramienta tecnica bajo demanda.

## Diferencias solicitud vs orden

Cuando la orden viene de solicitud, se deben detectar:

- Producto solicitado eliminado.
- Cantidad comprada menor.
- Cantidad comprada mayor.
- Producto adicional.
- Producto sustituido.
- Costo diferente al estimado.
- Producto no surtido.

Cada diferencia debe tener:

- Tipo.
- Partida de solicitud relacionada si existe.
- Partida de orden relacionada si existe.
- Justificacion opcional.
- Estado: pendiente, aceptada, resuelta.

Reglas:

- No borrar una partida solicitada sin dejar evidencia.
- Si se compra algo distinto, registrar sustitucion o adicional.
- Si XML trae algo no solicitado, marcar como adicional no solicitado o pendiente.

## Diferencias XML vs orden

El XML debe enriquecer la misma tabla principal.

Diferencias a mostrar como pendientes/alertas:

- SKU XML no encontrado en ERP.
- SKU proveedor no relacionado.
- Descripcion XML parecida pero no confirmada.
- Cantidad XML diferente.
- Costo XML diferente.
- Impuesto/fiscal diferente.
- Concepto XML no incluido en orden.
- Partida de orden no incluida en XML.

Reglas:

- XML no debe sobrescribir datos maestros sin permiso.
- XML puede actualizar datos de partida o proponer pendientes.
- Documento XML formal se registra si existe orden, pero no obliga a operar doble captura.

## Edicion de costos, descuentos y cargos

Aplicar las reglas de nueva orden:

- Precio sin IVA por defecto.
- Precio con IVA y precio sin IVA claros/sincronizados.
- Descuento general con tipo: porcentaje base 100, porcentaje decimal o importe SAT.
- Guardar importe final de descuento.
- Cargos/gastos no inventariables suman al total pero no generan recepcion/inventario.

Reglas extra en editar:

- Si orden viene de solicitud, costo diferente al estimado debe marcar diferencia.
- Si orden ya fue enviada, cambios de costo requieren flujo controlado o permiso especial.
- Si existen pagos/notas, cambios de total deben recalcular saldo y validar reglas financieras.

## Permisos

Permisos base:

- `compras.ver`: consultar orden.
- `compras.editar`: editar orden en borrador.
- `compras.aprobar`: enviar orden.
- `compras.cancelar`: cancelar orden.
- `compras.adjuntos`: operar adjuntos.
- `finanzas.ver`: ver resumen financiero.
- `finanzas.operar`: operar pagos/notas.

Reglas:

- Editar borrador requiere `compras.editar`.
- Enviar requiere `compras.aprobar`.
- Cancelar requiere `compras.cancelar`.
- Cambios despues de enviada deben requerir permiso/regla especial si se implementan.
- Finanzas no debe depender de permiso de compras para operar pagos/notas si el modulo se separa por rol.

## Orden recomendado antes de codificar

1. Auditar esquema ERP para ordenes y solicitudes.
2. Confirmar campos de origen, solicitud relacionada y detalle relacionado.
3. Confirmar campos para tipo de partida, origen de partida, fiscales y cargos.
4. Definir como se guardan diferencias solicitud vs orden.
5. Definir como se guardan diferencias XML vs orden.
6. Ajustar UI para modos: borrador editable, enviada seguimiento, parcial/recibida consulta, cancelada lectura.
7. Implementar tareas por bloques pequenos.

## Secciones de trabajo y prompts listos

### Tarea 1: Auditar esquema de editar orden

```text
Trabaja solo en Compras > Editar orden > Esquema.
Objetivo: auditar si el esquema ERP permite distinguir orden directa vs orden desde solicitud, detalle relacionado, tipo/origen de partida, fiscales por partida, cargos y diferencias.
Revisar: ComprasEsquema.php, OrdenesCompraErp.php, SolicitudesCompraErp.php y CatalogoErpEsquema.php.
No usar tablas ecom_* como fuente principal.
No eliminar campos heredados sin plan de migracion.
Criterio: entregar lista exacta de tablas/campos a crear, conservar, migrar o dejar de usar.
```

### Tarea 2: Modo editar segun estatus

```text
Trabaja solo en Compras > Editar orden > Modo por estatus.
Objetivo: hacer que la vista bloquee o permita acciones segun estatus: borrador editable, enviada seguimiento, parcial/recibida consulta, cancelada lectura.
Archivos esperados: formulario.php, formulario.js, Compra.php y OrdenesCompraErp.php si falta validacion backend.
No confiar solo en frontend; backend debe rechazar cambios no permitidos.
Criterio: una orden no editable no permite modificar cabecera/partidas aunque el usuario manipule la UI.
```

### Tarea 3: Editar orden generada desde solicitud

```text
Trabaja solo en Compras > Editar orden > Origen solicitud.
Objetivo: mostrar y conservar relacion con solicitud, detectar diferencias entre solicitado y comprado, y evitar borrar partidas de solicitud sin evidencia.
Archivos esperados: OrdenesCompraErp.php, SolicitudesCompraErp.php, formulario.js y vista si hace falta resumen.
No generar orden duplicada desde la misma solicitud.
Criterio: la orden muestra productos solicitados, adicionales, modificados y no surtidos con trazabilidad.
```

### Tarea 4: XML en editar orden

```text
Trabaja solo en Compras > Editar orden > XML.
Objetivo: al cargar XML en orden guardada, detectar productos ERP igual que el buscador, enriquecer la tabla principal y convertir diferencias en pendientes/alertas sin usar una tabla repetitiva de conciliacion como paso obligatorio.
Archivos esperados: formulario.js, Compra.php, ComprasXmlErp.php y OrdenesCompraErp.php si hace falta persistencia.
No actualizar Catalogo maestro automaticamente.
Criterio: productos XML existentes quedan registrados con id_sku_erp; productos no encontrados quedan como pendientes; diferencias son visibles en la misma experiencia de partidas.
```

### Tarea 5: Productos nuevos y cargos en editar borrador

```text
Trabaja solo en Compras > Editar orden > Productos nuevos y cargos.
Objetivo: permitir en orden borrador agregar productos nuevos/propuestos y cargos no inventariables que sumen al total sin afectar recepcion/inventario.
Archivos esperados: formulario.js, OrdenesCompraErp.php y ComprasEsquema.php si falta tipo de partida.
No crear producto maestro automaticamente.
Criterio: al guardar y recargar se conserva tipo de partida, origen, costo, fiscal y estado pendiente.
```

### Tarea 6: Diferencias y auditoria de cambios

```text
Trabaja solo en Compras > Editar orden > Auditoria de cambios.
Objetivo: registrar cambios relevantes de cabecera, partidas, costos, descuentos, fiscales, eliminaciones y diferencias contra solicitud/XML.
Revisar Core.php para auditoria generica y helpers privados de Compra.php.
No duplicar auditoria si ya existe registro explicito suficiente.
Criterio: se puede saber quien cambio que, cuando y por que en operaciones sensibles.
```

### Tarea 7: Totales, pagos y restricciones financieras

```text
Trabaja solo en Compras > Editar orden > Totales y finanzas.
Objetivo: si se edita una orden con pagos/notas, recalcular saldo desde backend y bloquear cambios que generen inconsistencia financiera.
Archivos esperados: OrdenesCompraErp.php, PagosCompraErp.php, Compra.php y formulario.js.
Antes de codificar pregunta si se permite cambiar total cuando ya hay pagos aplicados.
Criterio: no hay sobrepago, saldo queda correcto y los cambios sensibles quedan bloqueados o auditados.
```

### Tarea 8: Resumen de recepcion en editar/ver orden

```text
Trabaja solo en Compras > Editar/ver orden > Resumen recepcion.
Objetivo: mostrar resumen de recepcion cuando la orden esta enviada, parcial o recibida, sin permitir que Compras modifique inventario.
Archivos esperados: Almacenes.php, OrdenesCompraErp.php, Compra.php y vista/JS de orden.
No registrar recepcion desde Compras.
Criterio: el usuario ve recibido, faltante, incidencias y enlace a recepcion si aplica.
```

## Checklist de terminado de editar orden

- La orden directa en borrador se puede editar con seguridad.
- La orden generada desde solicitud conserva trazabilidad.
- Las diferencias solicitud vs orden quedan visibles.
- XML en orden guardada enriquece la tabla principal.
- Productos ERP desde XML se detectan como registrados.
- Productos nuevos quedan como pendientes de Catalogo.
- Cargos/gastos no inventariables suman al total sin afectar inventario.
- Precio sin IVA/con IVA y descuentos mantienen la misma UX definida para nueva orden.
- Estatus enviada/parcial/recibida/cancelada bloquean edicion operativa segun reglas.
- Pagos/notas restringen cambios de total si aplica.
- Recepcion se muestra como seguimiento, no como edicion desde Compras.
- Todo cambio sensible queda auditado o trazable.

## Decisiones pendientes del dueno

- Se permite editar proveedor/almacen en borrador si la orden viene de solicitud?
- Se permite eliminar una partida heredada de solicitud o solo marcar no surtida?
- Que diferencias contra solicitud requieren autorizacion?
- Se permite cambiar total si ya existen pagos/notas aplicadas?
- Se permite modificar una orden enviada antes de recepcion o debe cancelarse y rehacerse?
- Que cambios despues de enviada requieren permiso de Direccion?
- Como deben tratarse cargos no inventariables: gasto separado o prorrateo al costo de productos?
