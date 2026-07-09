# ERP Catalogo - Cantidad variable en recepcion

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: Diseno, UI y backend preparados; sin migraciones aplicadas  
Alcance: Catalogo ERP > configuracion maestra para recepcion variable

## Objetivo

Definir una configuracion generica para SKUs que requieren capturar cantidad real variable al recibir.

Ejemplo operativo:

- El proveedor entrega empaques fisicos cuyo contenido real puede variar.
- Catalogo no debe crear unidades como `costal`, `saco`, `paca` o `bolsa` solo porque se mencionen en operacion.
- La unidad formal del SKU sigue siendo configurable y medible: `KG`, `L`, `M`, `PZA` u otra unidad maestra.
- Lo variable es la cantidad real que Almacen captura al recibir.

## Regla principal

Cantidad variable en recepcion no es lo mismo que venta a granel.

- Venta a granel: define como se vende o descuenta en salida.
- Cantidad variable en recepcion: define que Almacen debe capturar la cantidad real recibida, aunque Compras haya pedido una cantidad teorica o empaques fisicos.

Catalogo solo guarda la regla maestra. Almacen/Recepcion debe ejecutar la captura real y generar existencia.

## Auditoria de esquema actual

### Unidad base del SKU

Vive en `erp_catalogo_skus`:

- `id_unidad_base`
- `factor_unidad_base`

Uso esperado:

- `id_unidad_base` define en que unidad se controla el inventario del SKU.
- `factor_unidad_base` es configurable por SKU, pero no debe usarse para crear unidades operativas como costal o saco.
- Si el inventario se controla por peso, la unidad base debe ser `KG` o equivalente formal.

### Reglas de granel/fraccionable

Viven en `erp_catalogo_sku_reglas_inventario`:

- `permite_venta_fraccionaria`
- `precision_decimal`
- `incremento_minimo_venta`
- `unidad_venta_label`
- `permite_etiqueta_fraccionada`

Uso esperado:

- Sirven para venta/captura fraccionaria.
- No resuelven por si solas la captura de cantidad real en recepcion.

### Presentaciones preparables

Viven en `erp_catalogo_sku_presentaciones`:

- `id_sku_base`
- `id_sku_presentacion`
- `factor_salida_base`
- `modo_disponibilidad`
- `consume_stock_base_en`
- `requiere_empaque`

Uso esperado:

- Sirven para presentaciones de venta/preparacion.
- No son la estructura correcta para recepcion variable.

### Proveedor y conversion compra -> inventario

Vive en `erp_catalogo_sku_proveedores`:

- `id_unidad_compra`
- `factor_conversion`
- `cantidad_minima`

Uso esperado:

- Define conversion teorica o pactada por proveedor.
- Sirve para decir: `1 unidad de compra = N unidades base`.
- No reemplaza la necesidad de capturar peso/volumen/cantidad real cuando el contenido varia.

### Etiquetas y unidades fisicas

Viven parcialmente en `erp_catalogo_sku_reglas_inventario`:

- `requiere_serie`
- `requiere_serie_fabricante`
- `generar_etiqueta_interna`
- `requiere_escaneo_venta`
- `prefijo_etiqueta_interna`
- `plantilla_etiqueta`
- `tipo_etiqueta_seguridad`
- `instrucciones_etiquetado`

Hallazgo:

- Ya existe configuracion de trazabilidad/etiquetado.
- No existe un campo claro que indique "al recibir se deben capturar unidades fisicas" separado de serie/etiqueta.
- Para recepcion variable puede requerirse capturar varios bultos fisicos con cantidad real por bulto, sin que cada bulto sea serie de fabricante.

### Campo para cantidad variable en recepcion

No se encontro un campo explicito en Catálogo ERP para:

- `requiere_cantidad_variable_recepcion`
- `requiere_unidades_fisicas_recepcion`
- tolerancia de recepcion variable
- nota operativa de recepcion variable

## Estructura recomendada

La configuracion debe vivir en `erp_catalogo_sku_reglas_inventario`, porque es una regla fisica/operativa del SKU que Almacen debe obedecer.

No recomiendo ponerla en `erp_catalogo_sku_proveedores` como regla principal, porque:

- La necesidad de pesar/medir al recibir pertenece al SKU.
- Un mismo SKU puede venir de varios proveedores y seguir requiriendo cantidad real.
- El proveedor puede aportar unidad/factor teorico, pero Almacen debe capturar lo real.

Si despues hay tolerancias distintas por proveedor, se puede agregar una regla secundaria en proveedor.

## Campos propuestos

No aplicar sin respaldo externo y autorizacion.

```sql
ALTER TABLE erp_catalogo_sku_reglas_inventario
  ADD COLUMN requiere_cantidad_variable_recepcion TINYINT(1) NOT NULL DEFAULT 0
    AFTER dias_minimos_recepcion,
  ADD COLUMN requiere_unidades_fisicas_recepcion TINYINT(1) NOT NULL DEFAULT 0
    AFTER requiere_cantidad_variable_recepcion,
  ADD COLUMN tolerancia_recepcion_porcentaje DECIMAL(9,4) NULL
    AFTER requiere_unidades_fisicas_recepcion,
  ADD COLUMN nota_recepcion_variable VARCHAR(255) NULL
    AFTER tolerancia_recepcion_porcentaje,
  ADD KEY idx_catalogo_regla_recepcion_variable (requiere_cantidad_variable_recepcion);
```

Significado:

- `requiere_cantidad_variable_recepcion`: Almacen debe capturar cantidad real recibida en unidad base.
- `requiere_unidades_fisicas_recepcion`: Almacen debe capturar desglose por unidad fisica/bulto/recipiente cuando aplique.
- `tolerancia_recepcion_porcentaje`: diferencia aceptable entre cantidad esperada y cantidad real antes de marcar incidencia. Puede quedar vacia.
- `nota_recepcion_variable`: instruccion corta para el operador de recepcion.

## Validaciones recomendadas

En Catalogo:

- Si `requiere_cantidad_variable_recepcion = 1`, el SKU debe controlar inventario.
- La unidad base debe estar definida.
- Si la unidad base permite decimales, la precision debe revisarse contra granel o contra regla de inventario.
- `tolerancia_recepcion_porcentaje` debe ser mayor o igual a `0` y menor a `100` si se captura.
- No obligar a activar venta fraccionaria solo por requerir recepcion variable.

En Almacen/Recepcion:

- Si el SKU requiere cantidad variable, no cerrar recepcion solo con cantidad teorica.
- Capturar cantidad real total en unidad base.
- Si requiere unidades fisicas, capturar cada bulto/unidad fisica con su cantidad real.
- Generar incidencia si cantidad real excede tolerancia configurada.
- Guardar evidencia operacional en recepcion, no en Catalogo.

## Ejemplos

### Producto controlado por peso

- Unidad base: `KG`.
- Compra: el proveedor puede mandar empaques fisicos.
- Regla: requiere cantidad variable en recepcion.
- Recepcion: el operador captura peso real total o peso por bulto.

### Producto controlado por litros

- Unidad base: `L`.
- Compra: contenedor o envase con volumen variable.
- Regla: requiere cantidad variable en recepcion.
- Recepcion: captura litros reales recibidos.

### Producto por pieza con cajas incompletas

- Unidad base: `PZA`.
- Compra: cajas o paquetes.
- Regla: puede requerir unidades fisicas si se reciben cajas con conteo real.
- Recepcion: captura piezas reales por caja si el proveedor no garantiza el contenido.

## UX propuesta en Catalogo

No saturar el modal.

Recomendacion:

- Agregar los campos dentro de la seccion `Control fisico`.
- Mostrar solo el switch principal: `Cantidad real al recibir`.
- Al activarlo, mostrar:
  - `Capturar unidades fisicas`
  - `Tolerancia %`
  - `Nota para recepcion`
- Agregar ayuda contextual:
  - "Usalo cuando Almacen debe pesar, medir o contar lo real al recibir. No crea unidades nuevas."
- En la tabla de SKUs mostrar badge discreto: `Recepcion variable`.

## Contrato hacia Almacen/Recepciones

Almacen debe leer estas reglas desde Catalogo:

- Si `requiere_cantidad_variable_recepcion = 1`, la cantidad esperada no debe convertirse automaticamente en existencia final sin captura real.
- Si `requiere_unidades_fisicas_recepcion = 1`, debe permitir renglones de detalle por bulto/unidad fisica.
- La existencia generada debe quedar en `id_unidad_base`.
- La diferencia entre esperado y real debe generar incidencia si excede tolerancia.

## Contrato hacia Compras

Compras sigue comprando segun proveedor:

- SKU proveedor.
- Unidad de compra.
- Factor teorico.
- Cantidad comprada.

Compras no debe intentar resolver el peso real. La diferencia se resuelve en Almacen/Recepcion.

## Orden de implementacion

1. Aprobar este diseno.
2. Respaldar base de datos fuera del proyecto.
3. Agregar campos en `CatalogoErpEsquema`.
4. Agregar lectura/escritura en `CatalogoErpDatos`.
5. Agregar UI bajo `Control fisico` en Productos.
6. Agregar badges/alertas de calidad en Catalogo.
7. Documentar handoff para Almacen/Recepciones.
8. Implementar uso real en Almacen/Recepciones.

## Criterio de cierre

Estado al 2026-06-26:

- Catalogo ya tiene UI preparada en alta/edicion de SKU.
- Catalogo ya tiene backend preparado para leer campos con fallback si no existen.
- Catalogo ya tiene backend preparado para guardar cuando las columnas existan.
- La migracion sigue pendiente por respaldo externo y autorizacion.
- Almacen/Recepciones aun no usa estas reglas.

Cierre de esta fase:

- Documento de diseño listo.
- Propuesta de DDL lista.
- UI/backend de Catalogo preparados.
- Sin migraciones aplicadas.

Cierre operativo futuro:

- Aplicar DDL con respaldo/autorizacion.
- Validar persistencia en Catalogo.
- Implementar uso en Almacen/Recepciones.

Garantias de esta tarea:

- Catalogo puede marcar un SKU como recepcion variable.
- No se crean unidades operativas por costumbre verbal.
- No se modifica Almacen/Inventario en esta tarea.
- DDL queda propuesto, no ejecutado.
