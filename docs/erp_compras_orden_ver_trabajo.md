# ERP Compras - Punto actual: ver orden de compra

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Guia operativa para prompts y tareas del punto ver orden  
Aplica a: consulta de orden directa, orden desde solicitud, orden enviada, parcial, recibida o cancelada  
Vista relacionada: `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php` y `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

## Proposito

Este documento define como debe funcionar `ver orden de compra`. Ver no debe ser una copia editable de la orden, sino una vista de consulta, trazabilidad y seguimiento por permisos.

La vista debe ayudar a responder:

- Que se compro.
- A quien se compro.
- De donde viene la orden: directa o solicitud.
- Que productos/cargos incluye.
- Que XML, adjuntos, pagos, notas y recepciones existen.
- Que esta pendiente de Catalogo, Finanzas, Almacen o Compras.
- Quien puede ver cada seccion y quien puede ejecutar acciones.

## Principio central

`Ver orden` es la vista segura para consultar estado y trazabilidad.

- No debe permitir editar partidas directamente.
- No debe modificar inventario.
- No debe marcar recepcion desde Compras.
- No debe actualizar Catalogo maestro.
- Puede mostrar acciones controladas segun permisos y estatus.
- Debe mostrar informacion suficiente para operar, auditar y tomar decisiones.

## Archivos exactos de trabajo

Frontend:

- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Backend:

- `app/controladores/Compra.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/ComprasXmlErp.php`
- `app/modelos/PagosCompraErp.php`
- `app/modelos/AdjuntosCompraErp.php`
- `app/modelos/Almacenes.php`

Relacionados bajo demanda:

- `app/modelos/SeguridadPermisos.php`
- `app/modelos/CatalogoErpDatos.php`
- `app/modelos/ComprasEsquema.php`

No tocar salvo necesidad justificada:

- Flujo legado de compras.
- Tablas `ecom_*` como fuente principal.
- Recepcion/inventario desde esta vista.
- Catalogo maestro desde esta vista.

## Niveles de permisos por seccion

Regla general:

- La vista completa requiere `compras.ver`.
- Cada seccion sensible requiere permiso adicional.
- Las acciones se muestran solo si el permiso y el estatus lo permiten.
- Backend debe validar permisos aunque frontend oculte botones.

### Matriz base

- Cabecera y partidas: `compras.ver`.
- Origen solicitud y diferencias: `compras.ver`; detalle ampliado puede requerir `compras.ver` y permiso de solicitud si se separa despues.
- Adjuntos: ver/descargar requiere `compras.ver`; subir/cancelar requiere `compras.adjuntos`.
- XML/resumen fiscal: ver requiere `compras.ver`; resolver diferencias o importar requiere permiso definido en flujo de edicion, normalmente `compras.editar` en borrador.
- Finanzas: resumen requiere `finanzas.ver`; operar pagos/notas requiere `finanzas.operar`.
- Recepcion/almacen: resumen requiere `almacen.ver` o `compras.ver` con alcance limitado; operar recepcion requiere `almacen.recibir` fuera de esta vista.
- Catalogo/pendientes: ver pendientes requiere `catalogo.ver` o `compras.ver` con resumen; resolver maestro requiere `catalogo.editar`.
- Costos/propuestas: ver costos puede requerir `catalogo.costos` si se muestran margenes o informacion sensible.
- Cancelar orden: requiere `compras.cancelar`.
- Enviar/reenviar orden: requiere `compras.aprobar`.
- Auditoria: ver bitacora completa requiere rol auditor/soporte o permiso especifico si se crea.

## Modos de vista segun estatus

### Borrador

Debe mostrar:

- Resumen completo.
- Boton para editar si tiene `compras.editar`.
- Boton para enviar si tiene `compras.aprobar`.
- Adjuntos si hay orden guardada.
- Finanzas solo si tiene `finanzas.ver`.

No debe permitir:

- Recepcion.
- Afectar inventario.

### Enviada

Debe mostrar:

- Orden emitida.
- Resumen de recepcion pendiente/preparada.
- Adjuntos.
- XML/resumen de diferencias.
- Finanzas si permisos.
- Acciones controladas: cancelar solo si reglas lo permiten.

No debe permitir:

- Editar libremente partidas.
- Cambiar proveedor/almacen.
- Marcar recibida desde Compras.

### Parcial

Debe mostrar:

- Cantidad comprada.
- Cantidad recibida.
- Cantidad faltante.
- Incidencias.
- Pagos/notas/saldo.
- Adjuntos.

No debe permitir:

- Editar partidas recibidas.
- Cancelacion libre.
- Afectar inventario.

### Recibida

Debe mostrar:

- Resumen final de recepcion.
- Diferencias finales.
- Costo capturado y, si permisos, informacion de costos.
- Pagos/notas/saldo.
- Adjuntos y auditoria.

No debe permitir:

- Cambios operativos de compra sin flujo especial.
- Recepcion adicional si ya esta cerrada, salvo flujo de ajuste.

### Cancelada

Debe mostrar:

- Motivo/fecha/usuario de cancelacion si existe.
- Estado final.
- Adjuntos historicos.
- Auditoria.

No debe permitir:

- Editar.
- Pagar.
- Recibir.
- Cargar XML que cambie partidas.

## Secciones esperadas

### 1. Cabecera de orden

Debe mostrar:

- Folio.
- Estatus.
- Origen: directa o solicitud.
- Proveedor.
- Almacen destino.
- Fecha estimada.
- Folio proveedor.
- Moneda y tipo de cambio.
- Contacto, telefono, direccion.
- Observaciones.

Permisos:

- Ver: `compras.ver`.
- Editar desde boton: `compras.editar` y solo si estatus editable.

Criterio:

- Usuario entiende que orden esta consultandose y si puede editarla o no.

### 2. Origen solicitud

Visible si la orden viene de solicitud.

Debe mostrar:

- Folio/id de solicitud.
- Estado de solicitud.
- Solicitante/area si existe.
- Resumen solicitado vs comprado.
- Diferencias: adicional, no surtido, modificado, sustituido.

Permisos:

- Ver resumen: `compras.ver`.
- Ver detalle completo: `compras.ver`; si despues se separa, permiso de solicitudes.
- Editar diferencias: solo desde editar orden con `compras.editar`.

Criterio:

- La solicitud no se pierde como contexto.
- La orden no parece directa si nacio de solicitud.

### 3. Partidas de orden

Debe mostrar en una sola tabla:

- Tipo: producto ERP, producto nuevo, XML, cargo/gasto, no inventariable.
- SKU/referencia.
- Producto/concepto.
- Cantidad.
- Unidad.
- Precio sin IVA.
- Precio con IVA si aplica.
- Impuesto.
- Descuento.
- Total.
- Estado fiscal.
- Estado catalogo.
- Estado recepcion si aplica.

Permisos:

- Ver partidas: `compras.ver`.
- Ver costos sensibles/margenes: `catalogo.costos` si se muestran datos mas alla del costo de compra.
- Editar: no desde esta vista; boton a editar requiere `compras.editar` y estatus editable.

Criterio:

- La tabla explica todo lo comprado sin duplicar conceptos XML en otra tabla operativa.

### 4. XML y documentos fiscales

Decision UX:

- No mostrar una tabla repetitiva de conciliacion como seccion operativa principal.
- Mostrar resumen compacto de XML si existe.
- Mostrar diferencias XML como pendientes/alertas.
- Mantener detalle tecnico bajo demanda si se necesita auditoria.

Debe mostrar:

- XML cargado o no cargado.
- UUID/folio si existe.
- Total XML vs total orden.
- Conceptos detectados como ERP.
- Conceptos nuevos/no detectados.
- Diferencias de cantidad, costo, impuesto o fiscal.

Permisos:

- Ver resumen XML: `compras.ver`.
- Descargar XML si esta como adjunto: `compras.ver`.
- Cargar/resolver XML: no en modo ver salvo accion permitida; normalmente `compras.editar` en borrador.

Criterio:

- El usuario entiende si el XML cuadra sin capturar dos veces.

### 5. Adjuntos

Debe mostrar:

- Tipo de documento.
- Referencia.
- Archivo.
- Observaciones.
- Fecha.
- Estado.
- Acciones permitidas.

Permisos:

- Ver/listar/descargar: `compras.ver`.
- Subir/cancelar: `compras.adjuntos` y estatus permitido.

Criterio:

- Adjuntos son la seccion documental principal visible.
- No se confunden con XML usado para carga de productos.

### 6. Finanzas

Debe mostrar:

- Total orden.
- Total aplicado.
- Saldo.
- Pagos.
- Notas de credito.
- Estados: pendiente, aplicado, conciliado, cancelado.

Permisos:

- Ver resumen y detalle: `finanzas.ver`.
- Registrar/cancelar pagos/notas: `finanzas.operar`.
- Compras sin permiso financiero no debe ver datos sensibles si el negocio decide separarlo.

Criterio:

- Direccion/Finanzas pueden saber si la orden esta pagada, pendiente o con saldo.

### 7. Recepcion y almacen

Debe mostrar si aplica:

- Recepcion relacionada.
- Estatus de recepcion.
- Recibido, faltante, excedente.
- Incidencias: danado, cambiado, faltante.
- Lote/caducidad/ubicacion solo si permiso lo permite.
- Enlace a vista de Almacen.

Permisos:

- Ver resumen basico desde Compras: `compras.ver`.
- Ver detalle de almacen: `almacen.ver`.
- Operar recepcion: `almacen.recibir`, fuera de esta vista.

Criterio:

- Compras puede dar seguimiento sin operar inventario.

### 8. Pendientes para Catalogo/Costos/Fiscales

Debe mostrar:

- Productos nuevos.
- Fiscales incompletos.
- Fiscales diferentes XML vs maestro.
- SKU proveedor sin relacion.
- Costo diferente.
- Unidad diferente.

Permisos:

- Ver resumen: `compras.ver`.
- Ver detalle/resolver desde Catalogo: `catalogo.ver` / `catalogo.editar`.
- Ver/operar costos: `catalogo.costos` si aplica.

Criterio:

- La orden sirve para delegar trabajo sin que Compras ensucie Catalogo maestro.

### 9. Auditoria y trazabilidad

Debe mostrar o permitir consultar:

- Creacion.
- Ediciones.
- Envio.
- Cancelacion.
- Cambios de estatus.
- Pagos/notas.
- Adjuntos.
- XML.
- Recepciones.

Permisos:

- Resumen basico: `compras.ver`.
- Bitacora completa: permiso de auditoria/soporte si se crea.

Criterio:

- Se puede responder quien hizo que y cuando.

## Acciones visibles segun permisos

- Editar orden: `compras.editar`, solo si estatus editable.
- Enviar orden: `compras.aprobar`, solo si borrador y reglas completas.
- Cancelar orden: `compras.cancelar`, si reglas lo permiten.
- Adjuntar archivo: `compras.adjuntos`, si estatus lo permite.
- Descargar adjunto: `compras.ver`.
- Registrar pago/nota: `finanzas.operar`, si reglas financieras lo permiten.
- Ir a recepcion: `almacen.ver` o `almacen.recibir` segun destino.
- Resolver pendiente catalogo: `catalogo.editar`, desde modulo Catalogo.

## Orden recomendado antes de codificar

1. Confirmar que `ver_orden_compra` use modo solo lectura real.
2. Definir que secciones se muestran con `compras.ver` y cuales requieren permisos extra.
3. Separar acciones visibles de acciones permitidas por backend.
4. Quitar o esconder conciliacion repetitiva de la vista principal.
5. Agregar resumen solicitud si origen es solicitud.
6. Agregar resumen recepcion si existe recepcion.
7. Agregar resumen de pendientes por area.
8. Validar que estados no editables no permitan cambios por manipulacion de UI.

## Secciones de trabajo y prompts listos

### Tarea 1: Modo ver solo lectura

```text
Trabaja solo en Compras > Ver orden > Solo lectura.
Objetivo: asegurar que la vista de ver orden no permita editar cabecera ni partidas, aunque reutilice formulario.php/formulario.js.
Archivos esperados: formulario.php, formulario.js, Compra.php.
Backend debe rechazar cambios si el modo no es editar.
Criterio: ver orden muestra datos y acciones permitidas, pero no inputs editables de captura.
```

### Tarea 2: Permisos por seccion

```text
Trabaja solo en Compras > Ver orden > Permisos por seccion.
Objetivo: aplicar visibilidad por permisos: compras.ver, finanzas.ver, finanzas.operar, compras.adjuntos, almacen.ver, catalogo.ver/catalogo.editar/catalogo.costos.
No confiar solo en frontend; validar endpoints.
Criterio: cada usuario ve solo secciones y acciones que su rol permite.
```

### Tarea 3: Resumen de solicitud

```text
Trabaja solo en Compras > Ver orden > Origen solicitud.
Objetivo: si la orden viene de solicitud, mostrar resumen solicitado vs comprado y diferencias sin permitir editar desde vista ver.
Archivos esperados: OrdenesCompraErp.php, SolicitudesCompraErp.php, Compra.php y vista/JS.
Criterio: la vista permite entender que se pidio, que se compro y que quedo diferente.
```

### Tarea 4: Resumen XML sin conciliacion repetitiva

```text
Trabaja solo en Compras > Ver orden > Resumen XML.
Objetivo: mostrar un resumen compacto del XML y diferencias detectadas, sin tabla duplicada de conciliacion como flujo principal.
Conservar trazabilidad tecnica si backend la usa.
Criterio: el usuario ve si XML cuadra o no, pero no captura dos veces.
```

### Tarea 5: Resumen recepcion

```text
Trabaja solo en Compras > Ver orden > Recepcion.
Objetivo: mostrar recepcion relacionada, recibido, faltante, incidencias y enlace a Almacen, sin permitir operar inventario desde Compras.
Archivos esperados: Almacenes.php, OrdenesCompraErp.php, Compra.php y vista/JS.
Criterio: Compras da seguimiento; Almacen opera recepcion.
```

### Tarea 6: Pendientes por area

```text
Trabaja solo en Compras > Ver orden > Pendientes por area.
Objetivo: mostrar pendientes separados para Catalogo, Finanzas, Almacen y Compras.
No resolver pendientes desde ver orden salvo acciones con permisos y flujo definido.
Criterio: la vista sirve como tablero de seguimiento de una orden.
```

### Tarea 7: Acciones por estatus

```text
Trabaja solo en Compras > Ver orden > Acciones por estatus.
Objetivo: mostrar botones de editar, enviar, cancelar, adjuntar, pagar o ir a recepcion solo cuando permiso y estatus lo permitan.
Backend debe validar cada accion.
Criterio: una orden cancelada/recibida no muestra acciones operativas indebidas.
```

## Checklist de terminado de ver orden

- La vista requiere `compras.ver`.
- Cada seccion sensible respeta permisos adicionales.
- Ver orden no permite editar partidas.
- Orden desde solicitud muestra origen y diferencias.
- Orden con XML muestra resumen sin duplicar captura.
- Adjuntos son visibles/descargables segun permiso.
- Finanzas se muestra solo con permiso financiero.
- Recepcion se muestra como seguimiento y no como operacion de inventario.
- Pendientes por area son visibles y accionables solo desde su rol.
- Acciones se filtran por permiso y estatus.
- Backend valida permisos aunque frontend oculte botones.

## Decisiones pendientes del dueno

- Compras puede ver saldos financieros o solo Finanzas/Direccion?
- Compras puede ver detalle de almacen o solo resumen de recepcion?
- Auditoria completa debe tener permiso propio?
- XML debe poder descargarse desde ver orden o solo desde adjuntos?
- Que acciones se permiten desde una orden enviada antes de recepcion?
- Que informacion de costos/margenes se oculta a roles no administrativos?
