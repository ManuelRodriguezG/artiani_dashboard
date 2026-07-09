# ERP - Pruebas reales por modulo

Este documento concentra las pruebas reales por modulo ERP. La idea es usarlo como checklist vivo cuando el flujo ya esta listo para probar con datos reales, sin mezclarlo con archivos de avance tecnico.

Regla general:

- Probar primero con pocos casos reales y trazables.
- No ejecutar migraciones masivas desde pruebas.
- No aplicar costos en lote, importaciones completas, bloqueos definitivos ni cambios de estado global sin autorizacion.
- Anotar proveedor/producto/orden usada, resultado esperado, resultado real y decision pendiente.
- Si una prueba revela una regla de negocio no definida, detener esa parte y documentar la pregunta.

## Proveedores

Estado: guia inicial generada el 2026-06-13 desde `docs/erp_proveedores_avance.md`.

Archivo especifico:

- `docs/erp_proveedores_pruebas_reales.md`.

Nota:

- Usar el archivo especifico para marcar pruebas reales de Proveedores. Esta seccion queda como resumen historico y referencia general.

Objetivo:

- Validar con datos reales el flujo individual de proveedor, lista, matching, relacion proveedor-SKU, costo vigente y consumo desde Compras.
- Confirmar que las advertencias operativas ayudan sin frenar innecesariamente.
- Detectar que reglas deben seguir como aviso, cuales deben pedir confirmacion y cuales deberian bloquear en una etapa futura.

Preparacion recomendada:

1. Elegir 1 proveedor confiable con datos relativamente completos.
2. Elegir 1 proveedor con datos incompletos o lista dudosa.
3. Elegir 3 a 5 SKUs reales que se compren con frecuencia.
4. Usar un usuario con permisos `proveedores.*`.
5. Usar otro usuario con permisos de Compras para validar el flujo desde Solicitudes y Ordenes.

Flujo minimo de prueba:

1. Abrir `Proveedores > Maestro proveedores`.
2. Revisar la pestaña `Preparacion` para conocer estado inicial del proveedor.
3. Crear o editar datos generales del proveedor.
4. Registrar datos fiscales, contactos y condiciones.
5. Registrar documentos solo como metadata, sin archivo fisico.
6. Crear encabezado de lista versionada.
7. Capturar algunos renglones reales de lista.
8. Ejecutar matching dry-run.
9. Guardar decision de matching en un renglon.
10. Aplicar relacion proveedor-SKU para un caso confiable.
11. Aplicar costo individual como vigente para ese renglon.
12. Revisar historial de costos.
13. Volver a la pestaña `Preparacion` y confirmar si aumento el porcentaje.
14. Probar `Pendientes` desde la lista y crear una incidencia individual si el caso lo amerita.
15. Crear una Solicitud de compra con ese proveedor y verificar advertencias.
16. Crear una Orden de compra y verificar advertencias.
17. Enviar Orden y confirmar que el modal aparece solo cuando hay costo vigente faltante o unidad/factor incompleto.

Evidencia a anotar:

| Campo | Valor |
| --- | --- |
| Fecha de prueba |  |
| Usuario Proveedores |  |
| Usuario Compras |  |
| Proveedor |  |
| Lista/version |  |
| SKU ERP |  |
| SKU proveedor |  |
| Costo capturado |  |
| Moneda |  |
| Advertencias mostradas |  |
| Preparacion inicial |  |
| Preparacion final |  |
| Resultado esperado |  |
| Resultado real |  |
| Decision pendiente |  |

Validaciones esperadas:

- La ficha del proveedor carga datos generales, fiscales, contactos, condiciones, documentos metadata, listas, renglones, matching, costos e incidencias.
- El matching dry-run propone candidatos sin escribir relaciones.
- La decision de matching queda registrada en el renglon.
- La relacion proveedor-SKU se aplica solo cuando el caso es confiable.
- El costo individual queda como vigente y el anterior se historiza si existia.
- Solicitudes y Ordenes consumen el contrato central de Proveedores.
- Solicitudes solo muestran advertencias.
- Ordenes muestran advertencias y piden confirmacion no bloqueante al enviar si falta costo vigente o unidad/factor.
- La pestaña `Preparacion` resume avance sin modificar datos.

No hacer todavia:

- Importar listas completas desde archivo.
- Aplicar costos en lote.
- Crear incidencias en lote.
- Actualizar `erp_catalogo_skus.costo_referencia`.
- Cambiar estados definitivos de proveedor.
- Bloquear Solicitudes u Ordenes por reglas nuevas.
- Migrar o transformar datos legacy masivamente.
- Ejecutar directo los SQL de `db/productivo`.

Preguntas de negocio a observar durante pruebas:

- Que advertencias realmente deben bloquear en Orden.
- Que advertencias solo deben pedir confirmacion.
- Que advertencias son solo informativas.
- Que datos debe revisar Compras y que datos corresponden a Catalogo, Almacen o Finanzas.
- Cuando un proveedor debe quedar activo para compras, suspendido o activo solo para pagos pendientes.
- Si el costo vigente debe actualizar `costo_referencia` o solo servir como referencia de compra.

Reutilizacion legacy a evaluar:

- Los archivos `db/productivo/erp_proveedores.sql`, `db/productivo/erp_proveedores_listas.sql` y `db/productivo/erp_proveedores_listas_productos.sql` contienen informacion aprovechable.
- La migracion debe ser selectiva y con vista previa.
- Los renglones legacy deben entrar primero como evidencia de lista, no como relaciones/costos oficiales automaticos.
- El matching, relaciones y costos deben aplicarse despues con revision.

## Catalogo

Estado: guia inicial generada el 2026-06-14 desde `docs/erp_catalogo_avance.md` y el flujo Proveedores -> Catalogo.

Archivo especifico:

- `docs/erp_catalogo_pruebas_reales.md`.
- `docs/erp_proveedores_catalogo_pruebas_reales.md` para el flujo puente Proveedores -> Catalogo -> Proveedores.

Objetivo:

- Validar incidencias de calidad, SKU temporal desde Proveedores, completado de Catalogo y matching posterior en Proveedores.

## Compras

Estado: pendiente de cargar guia especifica.

## Almacen e inventario

Estado: pendiente de cargar guia especifica.

## Finanzas / pagos

Estado: pendiente de cargar guia especifica.
