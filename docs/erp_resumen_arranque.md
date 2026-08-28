# ERP Resumen - Arranque

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-29  
Estado: version read-only implementada

## Proposito

La pantalla Resumen es la primera vista del panel ERP. Debe mostrar lo que requiere atencion operativa inmediata y accesos directos a los modulos responsables.

## Decision

El Resumen no debe ser una copia del dashboard demo de Metronic ni una portada estatica. Usa componentes visuales compatibles con Metronic, pero la informacion proviene de datos reales del ERP.

Primera version:

- Sin migraciones ni cambios de esquema.
- Solo consultas read-only.
- Respeta permisos del usuario.
- Tolera tablas faltantes y muestra bloque pendiente cuando aplica.
- Usa notificaciones operativas como fuente principal de trabajo.

Segunda iteracion:

- Agrega CRM Clientes y TMS Delivery al resumen cuando el usuario tenga permisos.
- CRM muestra clientes, activos, calidad de datos por revisar, tareas pendientes/vencidas e interacciones del dia.
- TMS muestra servicios, abiertos, en ruta, reintentos, cobro pendiente y prioridades altas.
- Mantiene fallback de esquema pendiente si las tablas aun no estan creadas.

Tercera iteracion:

- Agrega Catalogos comerciales como bloque independiente del Catalogo maestro.
- La tarjeta usa la ruta operativa `/catalogoerp/catalogos_comerciales`.
- Muestra activos, borradores, items activos y catalogos actualizados en los ultimos 7 dias.
- Agrega accesos rapidos a listado y a nuevo catalogo comercial segun permisos actuales.

## Configuracion futura de secciones

El Resumen debe evolucionar hacia una pantalla configurable sin romper permisos ni duplicar el sidebar.

Propuesta inicial:

- Crear una tabla `erp_resumen_secciones` con clave estable, titulo, modulo, ruta, permiso, icono, orden, visible_default y estatus.
- Crear una tabla `erp_resumen_secciones_usuario` para preferencias por usuario: visible, orden y tamano de bloque.
- Resolver visibilidad final como: permiso vigente + seccion activa + preferencia del usuario o default.
- Administrar configuracion desde Sistema > Resumen, protegida por `configuracion.administrar` o un permiso futuro `resumen.configurar`.
- Mantener las consultas de negocio en `ResumenErp` o en modelos resumen por modulo; la configuracion solo decide que se muestra, no altera reglas de negocio.
- Para la primera fase configurable, evitar DDL hasta que el dueno autorice esquema; se puede iniciar con un arreglo base versionado en codigo.

## Archivos

- `app/controladores/Inicio.php`
- `app/modelos/ResumenErp.php`
- `app/vistas/paginas/apps/erp/resumen/index.php`
- `public/assets/js/custom/apps/erp/resumen/resumen.js`

## Contrato

Endpoint:

- `/inicio/resumen_erp`

Salida:

- `error`
- `tipo`
- `mensaje`
- `depurar.fecha`
- `depurar.notificaciones`
- `depurar.modulos`
- `depurar.acciones`

## Siguientes pasos recomendados

- Validar visualmente en `http://panel.com.local/`.
- Ajustar nombres de KPIs si se prefiere lenguaje mas operativo.
- Convertir consultas repetidas de alto costo en metodos resumen por modulo si el tablero crece.
- Validar si conviene que cada modulo exponga su propio metodo resumen para reutilizar reglas internas.
- Diseñar y autorizar el esquema de configuracion de secciones antes de crear pantalla administrativa.
