# ERP Catalogo - pruebas reales

Documento vivo para validar Catalogo con datos reales, especialmente cuando otros modulos mandan pendientes o necesitan SKU ERP controlado.

## 1. Reglas de prueba

- Probar con pocos casos reales y trazables.
- No hacer altas masivas.
- No activar productos temporales sin completar datos minimos.
- No crear relaciones proveedor-SKU ni costos desde Catalogo si el flujo corresponde a Proveedores.
- Si aparece una regla de negocio no definida, documentarla y detener esa parte.

## 2. Preparacion

- [ ] Tener usuario con permiso `catalogo.ver`.
- [ ] Tener usuario con permiso `catalogo.editar`.
- [ ] Tener un proveedor real con lista cargada en Proveedores.
- [ ] Tener una incidencia real origen `proveedores` tipo `proveedor_sku_sin_match`.
- [ ] Identificar unidad base correcta antes de crear SKU temporal.

## 3. Incidencias de calidad

Checklist:

- [ ] Abrir Catalogo.
- [ ] Revisar tarjeta `Incidencias de calidad`.
- [ ] Confirmar que lista incidencias abiertas.
- [ ] Confirmar que muestra origen, tipo, referencia y estatus.
- [ ] Confirmar que una incidencia ligada a SKU permite abrir el producto.
- [ ] Confirmar que una incidencia de Proveedores sin SKU muestra accion `SKU temporal`.

## 4. SKU temporal desde Proveedores

Caso: producto de proveedor que todavia no existe en Catalogo y se quiere evaluar para compra.

Checklist:

- [ ] Crear incidencia individual desde Proveedores.
- [ ] Confirmar en Catalogo que aparece como `proveedor_sku_sin_match`.
- [ ] Abrir modal `Crear SKU temporal`.
- [ ] Revisar datos precargados desde el renglon proveedor.
- [ ] Seleccionar unidad base explicitamente.
- [ ] Crear borrador.
- [ ] Confirmar que se creo `erp_catalogo_productos.estatus = borrador`.
- [ ] Confirmar que se creo `erp_catalogo_skus.estatus = borrador`.
- [ ] Confirmar que la incidencia queda `en_revision`.
- [ ] Confirmar que la incidencia queda ligada a `id_producto_erp` e `id_sku`.
- [ ] Volver a Proveedores y ejecutar matching.
- [ ] Confirmar que el SKU temporal aparece como candidato.

No debe pasar:

- [ ] No debe activarse el SKU automaticamente.
- [ ] No debe crear relacion proveedor-SKU.
- [ ] No debe aplicar costo.
- [ ] No debe actualizar `costo_referencia`.
- [ ] No debe cerrar la incidencia automaticamente como resuelta.

## 5. Completar SKU temporal

Checklist:

- [ ] Abrir producto temporal.
- [ ] Revisar nombre, SKU, codigo principal y unidad base.
- [ ] Completar marca si aplica.
- [ ] Completar categoria si aplica.
- [ ] Completar fiscal cuando aplique.
- [ ] Completar imagen o evidencia si aplica.
- [ ] Decidir cuando pasa de `borrador` a `activo`.

Preguntas a observar:

- Que campos son obligatorios antes de activar.
- Si Catalogo puede activar sin proveedor relacionado.
- Si fiscal incompleto debe bloquear activacion o solo marcar incidencia.

## 6. Matching posterior en Proveedores

Checklist:

- [ ] Abrir proveedor/lista original.
- [ ] Ejecutar matching dry-run.
- [ ] Confirmar que el SKU temporal aparece como candidato.
- [ ] Seleccionar SKU si corresponde.
- [ ] Completar unidad/factor/cantidad minima en Proveedores.
- [ ] Aplicar relacion proveedor-SKU solo si el caso es confiable.
- [ ] Aplicar costo individual solo si costo/moneda son confiables.

## 7. Matriz de prueba rapida

| Caso | Incidencia | Producto/SKU | Resultado esperado | Resultado real | Estado |
| --- | --- | --- | --- | --- | --- |
| Ver incidencias abiertas |  |  | Tarjeta lista incidencias |  | Pendiente |
| Crear SKU temporal |  |  | Producto/SKU borrador |  | Pendiente |
| Matching posterior |  |  | SKU aparece en Proveedores |  | Pendiente |
| Completar Catalogo |  |  | Datos minimos completos |  | Pendiente |
| Activar SKU |  |  | Solo con autorizacion/regla clara |  | Pendiente |

## 8. Hallazgos

| Fecha | Incidencia | Pantalla | Que paso | Impacto | Decision requerida | Estado |
| --- | --- | --- | --- | --- | --- | --- |
|  |  |  |  |  |  |  |

