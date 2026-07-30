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
