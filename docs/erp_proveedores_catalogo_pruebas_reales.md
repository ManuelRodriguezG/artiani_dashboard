# ERP Proveedores + Catalogo - pruebas reales puente

Documento vivo para probar el flujo entre Proveedores y Catalogo cuando una lista de proveedor trae productos que todavia no existen como SKU ERP confiable.

Uso:

- Marcar pruebas reales cuando se ejecuten.
- Borrar o mover a evidencia las pruebas ya cerradas.
- Convertir hallazgos en tareas nuevas cuando algo falle o falte.

## 1. Objetivo

Validar el ciclo:

1. Proveedor tiene producto en lista.
2. Proveedores no encuentra SKU ERP confiable.
3. Proveedores crea incidencia individual hacia Catalogo.
4. Catalogo crea producto/SKU temporal en `borrador`.
5. Proveedores vuelve a hacer matching.
6. Proveedores selecciona el SKU temporal.
7. Proveedores completa unidad/factor/cantidad minima.
8. Proveedores aplica relacion proveedor-SKU solo si el caso es confiable.

## 2. Reglas

- No crear incidencias en lote.
- No activar SKU temporal automaticamente.
- No aplicar costos automaticamente.
- No actualizar `costo_referencia`.
- No crear relacion proveedor-SKU desde Catalogo para este flujo.
- No usar productos temporales para venta hasta completar Catalogo.
- Si una regla de negocio no esta clara, documentar pregunta y detener esa parte.

## 3. Preparacion

- [ ] Elegir un proveedor real.
- [ ] Elegir una lista real cargada.
- [ ] Elegir un renglon sin SKU ERP confiable.
- [ ] Confirmar que el renglon realmente se quiere evaluar para compra.
- [ ] Tener usuario con permisos `proveedores.listas`, `proveedores.matching`, `proveedores.autorizar`.
- [ ] Tener usuario con permiso `catalogo.editar`.
- [ ] Tener clara la unidad base que usara Catalogo para el SKU temporal.

## 4. Crear pendiente desde Proveedores

Checklist:

- [ ] Abrir proveedor.
- [ ] Abrir lista.
- [ ] Abrir `Pendientes y resolucion`.
- [ ] Filtrar por `proveedor_sku_sin_match`.
- [ ] Confirmar que el renglon es el producto correcto.
- [ ] Crear incidencia individual hacia Catalogo.
- [ ] Confirmar mensaje de exito.
- [ ] Confirmar que no se duplico la incidencia al repetir.

Evidencia:

| Campo | Valor |
| --- | --- |
| Proveedor |  |
| Lista/version |  |
| ID renglon |  |
| SKU proveedor |  |
| Codigo proveedor |  |
| Descripcion proveedor |  |
| ID incidencia Catalogo |  |

## 5. Crear SKU temporal desde Catalogo

Checklist:

- [ ] Abrir Catalogo.
- [ ] Revisar tarjeta `Incidencias de calidad`.
- [ ] Localizar incidencia origen `proveedores`.
- [ ] Abrir `SKU temporal`.
- [ ] Revisar nombre/codigo/SKU precargados.
- [ ] Seleccionar unidad base explicita.
- [ ] Crear borrador.
- [ ] Confirmar que producto queda `borrador`.
- [ ] Confirmar que SKU queda `borrador`.
- [ ] Confirmar que incidencia queda `en_revision`.
- [ ] Confirmar que incidencia queda ligada a `id_producto_erp` e `id_sku`.

No debe pasar:

- [ ] No debe activar SKU.
- [ ] No debe aplicar costo.
- [ ] No debe crear relacion proveedor-SKU.
- [ ] No debe cerrar incidencia como `resuelta`.

Evidencia:

| Campo | Valor |
| --- | --- |
| ID incidencia |  |
| ID producto ERP |  |
| ID SKU ERP |  |
| SKU temporal |  |
| Unidad base |  |
| Estado producto |  |
| Estado SKU |  |

## 6. Matching posterior en Proveedores

Checklist:

- [ ] Volver al proveedor/lista.
- [ ] Ejecutar matching dry-run.
- [ ] Confirmar que aparece candidato con criterio `incidencia_catalogo_sku_temporal`.
- [ ] Seleccionar ese SKU.
- [ ] Guardar decision de matching.
- [ ] Confirmar que el renglon queda `match_seleccionado`.
- [ ] Confirmar badge `Completar compra` si falta unidad/factor/cantidad minima.
- [ ] Completar unidad compra.
- [ ] Completar factor conversion.
- [ ] Completar cantidad minima.
- [ ] Confirmar badge `Listo relacion`.

No debe pasar:

- [ ] No debe aplicar relacion sin unidad/factor/cantidad minima.
- [ ] No debe aplicar costo antes de relacion.

## 7. Aplicar relacion y costo

Checklist:

- [ ] Aplicar relacion proveedor-SKU.
- [ ] Confirmar que queda `relacion_aplicada`.
- [ ] Confirmar que se creo/actualizo `erp_catalogo_sku_proveedores`.
- [ ] Capturar costo positivo y moneda.
- [ ] Aplicar costo individual.
- [ ] Confirmar costo vigente en historial.

No hacer todavia:

- [ ] No aplicar costos en lote.
- [ ] No tocar `costo_referencia`.
- [ ] No marcar proveedor/lista como final sin revisar.

## 8. Resultado de prueba

| Fecha | Proveedor | Lista | Renglon | Incidencia | SKU temporal | Resultado | Tarea nueva |
| --- | --- | --- | --- | --- | --- | --- | --- |
|  |  |  |  |  |  |  |  |

## 9. Hallazgos y decisiones

| Fecha | Hallazgo | Impacto | Decision requerida | Estado |
| --- | --- | --- | --- | --- |
|  |  |  |  |  |

## 10. Caso real SFF-303 - Proveedor relacionado pero Compras no lo muestra

Contexto:

- Proveedor/lista: el renglon `SFF-303` ya puede quedar vinculado a un SKU ERP.
- Catalogo: el producto/SKU temporal puede quedar creado.
- Proveedores: la relacion proveedor-SKU puede quedar activa.
- Compras: no debe mostrarlo mientras el SKU ERP siga en `borrador`.

Checklist:

- [ ] En Proveedores, buscar `SFF-303` en detalle de lista.
- [ ] Confirmar que tiene `id_sku` y `id_sku_proveedor`.
- [ ] Confirmar que el estado del renglon esta como `match_seleccionado`, `relacion_aplicada` o equivalente operativo.
- [ ] En Catalogo ERP, buscar `SFF-303`.
- [ ] Abrir detalle del producto.
- [ ] Entrar a pestana `SKUs`.
- [ ] Editar SKU `SFF-303`.
- [ ] Confirmar si el SKU esta en `borrador`.
- [ ] Cambiar `Estado` a `Activo` solo si Catalogo valida que puede usarse en Compras.
- [ ] Guardar SKU.
- [ ] Volver a Compras y buscar `SFF-303` en solicitud/orden.
- [ ] Confirmar que ya aparece como SKU comprable del proveedor.

No debe pasar:

- [ ] No activar SKU temporal sin revision de Catalogo.
- [ ] No cambiar directo por SQL sin evidencia.
- [ ] No considerar la relacion proveedor-SKU suficiente si el SKU ERP sigue en `borrador`.

Evidencia:

| Campo | Valor |
| --- | --- |
| Fecha prueba |  |
| Proveedor |  |
| SKU proveedor | SFF-303 |
| ID producto ERP |  |
| ID SKU ERP |  |
| Estado SKU antes |  |
| Estado SKU despues |  |
| Relacion proveedor-SKU activa |  |
| Compras lo encuentra despues |  |
| Observaciones |  |
