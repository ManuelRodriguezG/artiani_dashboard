# ERP - Etiquetas, series y trazabilidad por unidad

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-18  
Estado: Esquema base aplicado; UAT de Almacen aprobado; pendiente Ventas/Garantias.

## Objetivo

Separar correctamente cuatro conceptos que parecen parecidos, pero tienen reglas distintas:

- Codigo de SKU o codigo de barras: identifica que producto es.
- Lote/caducidad: identifica grupo de producto y vencimiento.
- Serie de fabricante: identifica una pieza que ya viene marcada por fabricante.
- Etiqueta de trazabilidad interna: identifica una pieza marcada por la empresa para probar origen de venta propia.

La meta es que el ERP pueda responder:

- Que producto es.
- De que compra/recepcion salio.
- Si tenia lote/caducidad.
- Si tenia serie real de fabricante.
- Si la empresa le puso etiqueta propia.
- Si esa unidad fue vendida por la empresa y en que venta.

## Decision recomendada

No usar `requiere_serie` para todo.

En un ERP robusto conviene separar:

- `requiere_serie_fabricante`: el producto trae numero de serie real y debe capturarse o escanearse.
- `generar_etiqueta_interna`: la empresa genera una etiqueta propia aunque no exista serie de fabricante.
- `requiere_escaneo_venta`: al vender, el cajero/vendedor debe seleccionar o escanear la unidad individual.

Por que:

- La serie de fabricante no la inventa la tienda.
- La etiqueta interna si la genera la tienda.
- Si se mezclan, Almacen puede quedar bloqueado esperando una serie que el producto no trae.
- Si solo se imprime una etiqueta y no se guarda en inventario, no sirve como evidencia.

## Dueno por modulo

| Modulo | Responsabilidad |
| --- | --- |
| Catalogo ERP | Decide por SKU/familia/categoria si requiere serie o etiqueta de trazabilidad interna. |
| Almacen/Recepciones | Captura series reales, genera etiquetas de trazabilidad y registra unidades al recibir. |
| Inventario | Conserva unidad, estatus, lote, ubicacion, recepcion y venta asociada. |
| Ventas | Consume/asocia la unidad al vender cuando aplique. |
| Garantias/Devoluciones | Valida si el codigo pertenece a la empresa, si fue vendido y si esta vigente. |

Almacen no debe decidir desde cero que se etiqueta; solo debe poder ejecutar o reportar excepciones. La decision operativa nace en Catalogo porque depende del tipo de producto, valor, garantia, fraude esperado y politica comercial.

## Regla de UI en Recepcion

Recepcion no debe tener un checkbox libre llamado "Etiquetas" para decidir si una unidad se etiqueta o no. Esa decision pertenece a Catalogo.

Comportamiento correcto:

- Si Catalogo marca `generar_etiqueta_interna=1`, Recepcion muestra `Etiqueta trazabilidad` y genera unidad/codigo al guardar.
- Si Catalogo marca `requiere_serie_fabricante=1`, Recepcion debe capturar o escanear una serie real por pieza.
- Si no hay regla de Catalogo, Recepcion no genera etiquetas individuales por decision casual.
- La impresion o pegado de etiqueta debe ser una accion posterior sobre `erp_inventario_unidades`.
- Cualquier excepcion manual debe pedir motivo, permiso y auditoria.

## Esquema actual observado

Ya existe una base util:

- `erp_catalogo_sku_reglas_inventario.requiere_serie`.
- `erp_inventario_unidades.codigo_unico`.
- `Almacenes::generar_unidades_inventario()` genera codigos individuales al recibir.
- Recepciones ya muestra bandera de etiquetas individuales cuando el control lo pide.

Brechas actuales:

- `requiere_serie` funciona como codigo unico, pero no distingue serie real contra etiqueta de trazabilidad interna.
- `erp_inventario_unidades` no guarda claramente `id_sku_erp`.
- `erp_inventario_unidades` no separa `serie_fabricante` de `codigo_etiqueta_interna`.
- No hay estado explicito de impresion/pegado de etiqueta.
- Ventas y devoluciones todavia no estan conectadas a la unidad individual.

## Politicas recomendadas por tipo de producto

### Producto normal

Ejemplos:

- Accesorios baratos.
- Consumibles sin garantia por pieza.
- Productos donde no vale la pena etiquetar individualmente.

Regla:

- Sin serie.
- Sin etiqueta interna.
- Inventario por SKU/almacen/lote si aplica.

### Producto con caducidad

Ejemplos:

- Alimentos.
- Medicamentos.
- Vitaminas.
- Productos a granel.

Regla:

- Lote y caducidad.
- No etiqueta interna salvo que haya razon comercial o de garantia.

### Producto con serie de fabricante

Ejemplos:

- Equipos electricos/electronicos con serie impresa.
- Bombas, filtros, lamparas, calentadores o motores cuando el proveedor/fabricante usa serie.

Regla:

- `requiere_serie_fabricante=1`.
- Capturar o escanear una serie por pieza en Recepcion.
- Bloquear series duplicadas.
- En venta, asociar la serie exacta al ticket/factura.

### Producto sin serie, pero con etiqueta de trazabilidad

Ejemplos:

- Productos que tambien venden otros negocios.
- Equipos con garantia propia de tienda.
- Productos de valor medio/alto con riesgo de cambio fraudulento.

Regla:

- `requiere_serie_fabricante=0`.
- `generar_etiqueta_interna=1`.
- Generar un codigo propio por unidad.
- Imprimir QR o Code128 mas texto legible.
- Guardar la unidad en `erp_inventario_unidades`.
- En venta, ligar la etiqueta a la venta.

La parte de "dificil de quitar" no la resuelve la BD: se resuelve con material fisico, por ejemplo etiqueta VOID, vinil destructible, adhesivo de seguridad o etiqueta que deje evidencia al retirarse. El ERP debe guardar el codigo, plantilla y trazabilidad.

## Propuesta de datos

### Catalogo

Agregar a reglas de inventario por SKU:

- `requiere_serie_fabricante`.
- `generar_etiqueta_interna`.
- `requiere_escaneo_venta`.
- `prefijo_etiqueta_interna`.
- `plantilla_etiqueta`.
- `tipo_etiqueta_seguridad`.
- `instrucciones_etiquetado`.

### Inventario por unidad

Ampliar `erp_inventario_unidades`:

- `id_sku_erp`.
- `tipo_identidad`: `serie_fabricante`, `etiqueta_interna`, `mixta`.
- `serie_fabricante`.
- `codigo_etiqueta_interna`.
- `estado_etiqueta`: `pendiente_impresion`, `impresa`, `pegada`, `reimpresa`, `cancelada`.
- `fecha_impresion`, `impreso_por`.
- `fecha_etiquetado`, `etiquetado_por`.
- `origen_tipo`, `origen_id`, `origen_detalle_id`.

## Formato recomendado de etiqueta

Contenido minimo:

- Nombre corto o marca de la empresa.
- Codigo QR o Code128.
- Codigo corto legible.
- Folio o prefijo interno.

Formato de codigo sugerido:

```text
ART-AAAA-RECEPCION-LOTE-SECUENCIA
```

Ejemplo:

```text
ART-2026-00020-00022-0001
```

Por que:

- Es legible por humanos.
- Se puede escanear.
- Relaciona la unidad con recepcion/lote.
- No expone datos sensibles de costo o proveedor.

## Flujo objetivo

1. Catalogo marca el SKU con serie o etiqueta de trazabilidad.
2. Compra crea orden sin afectar inventario.
3. Almacen recibe.
4. Si requiere serie, captura una serie por unidad.
5. Si requiere etiqueta de trazabilidad, genera codigos por unidad.
6. Almacen imprime y pega etiquetas.
7. Inventario deja unidades disponibles.
8. Ventas escanea o selecciona unidad al vender.
9. Garantias/devoluciones buscan el codigo y validan venta propia.

## UAT propuesto

| UAT | Caso | Resultado esperado |
| --- | --- | --- |
| UAT-ALM-009 | Recibir SKU con etiqueta de trazabilidad | Genera una unidad por pieza con codigo interno unico. |
| UAT-ALM-010 | Reintentar etiqueta duplicada | Backend bloquea duplicado y no duplica unidad. |
| UAT-ALM-011 | Recibir SKU con serie fabricante | Exige capturar una serie por pieza y bloquea faltantes. |
| UAT-VTA-001 | Vender SKU con escaneo requerido | Venta queda ligada a unidad individual. |
| UAT-DEV-001 | Validar devolucion por codigo | ERP confirma si fue vendido por la empresa o lo rechaza. |

## Orden recomendado de implementacion

1. Documentar politica y aprobar nombres de campos.
2. Preparar DDL dry-run y auditoria sin ejecutar.
3. Respaldar BD externo.
4. Aplicar columnas de Catalogo e Inventario.
5. Actualizar auditorias `CatalogoErpEsquema` y `AlmacenEsquema`.
6. Cambiar backend de Recepciones para separar serie de etiqueta.
7. Agregar UI de Catalogo para configurar la politica.
8. Agregar UI de Recepcion para ver/generar/registrar unidades.
9. Hacer piloto con un SKU, sin masificacion.
10. Conectar despues Ventas y Garantias.

## Punto de autorizacion

Antes de cualquier siguiente cambio masivo o piloto con productos reales:

- Preview de columnas/indices actuales.
- SQL propuesto revisado.
- Respaldo externo inmediato.
- Autorizacion explicita.

No se debe aplicar masivamente a productos hasta tener UAT de Recepcion, Venta y Devolucion.

## Aplicacion de esquema base

Fecha: 2026-06-18.

Respaldo externo:

- `artianilocal_panel_20260619_antes_etiquetas_series_schema.sql`.

DDL aplicado:

- `erp_catalogo_sku_reglas_inventario`:
  - `requiere_serie_fabricante`.
  - `generar_etiqueta_interna`.
  - `requiere_escaneo_venta`.
  - `prefijo_etiqueta_interna`.
  - `plantilla_etiqueta`.
  - `tipo_etiqueta_seguridad`.
  - `instrucciones_etiquetado`.
- `erp_inventario_unidades`:
  - `id_sku_erp`.
  - `tipo_identidad`.
  - `serie_fabricante`.
  - `codigo_etiqueta_interna`.
  - `estado_etiqueta`.
  - campos de impresion/etiquetado.
  - campos de origen.
- Indices para SKU, serie, etiqueta, origen y estado de etiqueta.
- Unicos para `serie_fabricante` y `codigo_etiqueta_interna`.
- FK `fk_inv_unidad_sku`.

Validacion:

- Auditor `AlmacenEsquema`: `success`, sin pendientes para `erp_inventario_unidades`.
- Auditor `CatalogoErpEsquema`: `success`, sin pendientes para reglas de inventario.
- `erp_inventario_unidades` estaba vacia antes de crear unicos, por lo que no hubo duplicados a resolver.
- `REC-OC-20` sigue consultando 13 partidas correctamente despues del cambio.
- No se crearon unidades ni movimientos de inventario por este DDL.

No se activo ningun SKU masivo con etiqueta de trazabilidad.

## UAT-ALM-009 - Piloto etiqueta de trazabilidad

Fecha: 2026-06-18.

Folio:

- Recepcion: `REC-OC-20`.
- Orden: `OC-2026-000020`.
- SKU piloto: `SCF-800`.

Respaldo externo:

- `artianilocal_panel_20260619_antes_uat_alm_009_etiqueta_scf800.sql`.

Por que este SKU:

- Es equipo/filtro, no alimento.
- Tiene 1 unidad pendiente, por lo que el impacto operativo es minimo.
- Es buen candidato para probar etiqueta de trazabilidad sin masificar.

Regla aplicada:

- `requiere_serie_fabricante=0`.
- `generar_etiqueta_interna=1`.
- `requiere_escaneo_venta=1`.
- `prefijo_etiqueta_interna='ART'`.
- `plantilla_etiqueta='estandar_qr'`.
- `tipo_etiqueta_seguridad='void'`.

Recepcion aplicada:

- Cantidad: `1.0000`.
- Ubicacion: `UAT-ALM-009`.
- Sin lote/caducidad, porque este SKU no lo requiere.

Resultado:

- Detalle `SCF-800`: recibido `1.0000`, pendiente `0.0000`, estatus `recibida`.
- Lote/entrada: `id_recepcion_lote=23`, codigo `LOT-1-23`.
- Movimiento: `id_movimiento_inventario=23`, cantidad `1.0000`.
- Existencia: `id_existencia_inventario=22`, disponible `1.0000`.
- Unidad individual: `id_inventario_unidad=1`.
- Codigo interno: `ART-00001-23-0001`.
- `tipo_identidad='etiqueta_interna'`.
- `codigo_etiqueta_interna='ART-00001-23-0001'`.
- `estado_etiqueta='pendiente_impresion'`.
- Incidencias: `0`.

Estado posterior:

- `REC-OC-20`: parcial, 11/105 unidades recibidas, 94 pendientes.
- `OC-2026-000020`: parcial.
- No se activo etiqueta de trazabilidad para otros SKUs.

Decision:

- `UAT-ALM-009` queda aprobado tecnicamente para generacion de unidad individual con etiqueta de trazabilidad desde Recepcion.
- Pendiente: flujo visual de impresion/pegado de etiqueta y conexion con Ventas/Devoluciones.
