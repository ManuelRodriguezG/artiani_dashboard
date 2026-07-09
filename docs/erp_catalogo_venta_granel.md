# ERP Catalogo - Venta a granel

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-19  
Estado: Diseno aprobado funcionalmente; esquema y captura base de Catalogo implementados  
Alcance: Catalogo ERP > reglas de inventario y unidades para venta a granel

## Objetivo

Definir desde Catalogo ERP como se configura un SKU que se compra en una presentacion, se inventaria en unidad base y puede venderse fraccionado.

Esta tarea no modifica Almacen, Inventario ni Ventas. Es una preparacion de contrato para que esos modulos usen la misma verdad despues.

## Auditoria del esquema actual

### `erp_catalogo_skus`

Campos actuales relevantes:

- `id_unidad_base`: unidad base del SKU. Ya existe y es obligatoria.
- `factor_unidad_base`: existe como `DECIMAL(18,6)`, pero hoy todos los SKU auditados tienen `1.000000`.
- `tipo_inventario`: hoy todos los SKU auditados son `inventariable`.
- `permite_venta_sin_existencia`: existe, pero no significa venta fraccionaria.

Hallazgo:

- La tabla ya puede decir "este SKU se mide en kg/l/m", usando `id_unidad_base`.
- No tiene campo explicito para "permite venta fraccionaria", "precision decimal" ni "incremento minimo de venta".
- `factor_unidad_base` no se usa en captura actual: altas y ediciones insertan `1`.

### `erp_catalogo_sku_reglas_inventario`

Campos actuales relevantes:

- `controla_inventario`
- `permite_existencia_negativa`
- `requiere_lote`
- `requiere_caducidad`
- `requiere_serie`
- `requiere_serie_fabricante`
- `generar_etiqueta_interna`
- `requiere_escaneo_venta`
- `estrategia_salida`
- `stock_minimo`
- `stock_maximo`
- `punto_reorden`
- `dias_alerta_caducidad`
- `dias_minimos_recepcion`

Hallazgo:

- La tabla ya concentra reglas fisicas y operativas del SKU.
- No tiene campos explicitos para:
  - venta fraccionaria;
  - precision decimal por SKU;
  - incremento minimo de venta;
  - unidad base de inventario alternativa, porque esa vive en `erp_catalogo_skus.id_unidad_base`;
  - conversion compra a inventario, porque esa vive por proveedor en `erp_catalogo_sku_proveedores.factor_conversion`.

### `erp_catalogo_unidades`

Campos actuales relevantes:

- `codigo`
- `nombre`
- `abreviatura`
- `tipo_magnitud`
- `decimales_permitidos`
- `clave_sat`
- `estatus`

Unidades existentes:

| Codigo | Unidad | Abreviatura | Magnitud | Decimales |
| --- | --- | --- | --- | --- |
| `PZA` | Pieza | `pza` | unidad | 0 |
| `KG` | Kilogramo | `kg` | masa | 1 |
| `G` | Gramo | `g` | masa | 1 |
| `L` | Litro | `L` | volumen | 1 |
| `ML` | Mililitro | `ml` | volumen | 1 |
| `M` | Metro | `m` | longitud | 1 |
| `CM` | Centimetro | `cm` | longitud | 1 |
| `CAJA` | Caja | `caja` | empaque | 0 |
| `PAQ` | Paquete | `paq` | empaque | 0 |
| `SERV` | Servicio | `serv` | servicio | 1 |

Hallazgo:

- La tabla distingue magnitud y si una unidad permite decimales.
- `decimales_permitidos` es booleano, no precision numerica. No alcanza para validar entre 0 y 6 decimales por SKU.
- Aunque existen unidades decimales, todos los SKU actuales usan `PZA`.

### `erp_catalogo_sku_proveedores`

Campos actuales relevantes:

- `id_unidad_compra`: unidad/presentacion en la que compra el proveedor.
- `factor_conversion`: unidades base por unidad de compra.
- `cantidad_minima`: minimo de compra.
- `costo_ultimo`
- `sku_proveedor`
- `es_preferido`
- `estatus`

Hallazgo:

- Esta tabla ya es el lugar correcto para la conversion de compra a inventario por proveedor.
- Ejemplo actual previsto por el modelo: comprar `1 caja` y convertir a `N unidades base`.
- En datos actuales, las relaciones auditadas usan `PZA` y `factor_conversion=1`; todavia no hay conversiones reales de granel.

## Decision recomendada

Reutilizar la estructura actual y agregar solo lo que falta:

- `erp_catalogo_skus.id_unidad_base` sigue siendo la unidad base de inventario.
- `erp_catalogo_unidades` sigue definiendo magnitud y si la unidad admite decimales.
- `erp_catalogo_sku_proveedores.id_unidad_compra` + `factor_conversion` siguen definiendo conversion compra -> inventario.
- `erp_catalogo_sku_reglas_inventario` debe recibir las reglas propias de venta fraccionaria.

No recomiendo crear SKU por fraccion vendida. Un costal, garrafon o rollo comprado debe convertirse a una existencia base y Ventas debera descontar fracciones de esa existencia.

## Campos faltantes propuestos

Agregar a `erp_catalogo_sku_reglas_inventario`:

- `permite_venta_fraccionaria TINYINT(1) NOT NULL DEFAULT 0`
  - Indica si el SKU puede venderse en cantidades decimales.
- `precision_decimal TINYINT UNSIGNED NOT NULL DEFAULT 0`
  - Precision operativa para captura y saldos visibles. Rango recomendado: 0 a 6.
- `incremento_minimo_venta DECIMAL(18,6) NOT NULL DEFAULT 1.000000`
  - Paso minimo de venta. Ejemplos: `0.001 kg`, `0.250 kg`, `0.100 L`, `0.010 m`.
- `unidad_venta_label VARCHAR(30) NULL`
  - Etiqueta visual opcional para la UI si se requiere mostrar una unidad comercial distinta sin cambiar la unidad base.
- `permite_etiqueta_fraccionada TINYINT(1) NOT NULL DEFAULT 0`
  - Excepcion controlada para casos donde una fraccion vendida necesita etiqueta especial.

No agregar por ahora:

- `id_unidad_base_inventario`: duplicaria `erp_catalogo_skus.id_unidad_base`.
- `factor_compra_inventario` en reglas: ya existe por proveedor en `erp_catalogo_sku_proveedores.factor_conversion`.
- conversion venta -> inventario: si la unidad base se elige bien, Ventas captura en unidad base o convierte desde una presentacion autorizada en un flujo posterior.

## Ajuste de criterio 2026-06-26 - Unidades operativas y recepcion variable

Regla:

- No crear unidades como `costal`, `saco`, `paca` o `bolsa` solo porque se mencionen en la operacion diaria.
- La unidad formal debe seguir siendo una unidad maestra clara: `KG`, `L`, `M`, `PZA` u otra unidad realmente necesaria.
- Si el contenido fisico varia al recibir, la solucion no es crear una unidad nueva; la solucion es marcar el SKU como recepcion variable y capturar la cantidad real en Almacen/Recepcion.
- Si se requiere una presentacion de venta, usar `erp_catalogo_sku_presentaciones`.
- Si el proveedor vende una unidad cerrada generica, usar `erp_catalogo_sku_proveedores.id_unidad_compra` y `factor_conversion`, sin asumir que el factor sera igual para todos los SKUs.

Documento relacionado:

- `docs/erp_catalogo_recepcion_cantidad_variable.md`

## Glosario operativo de campos

Este glosario explica los titulos visibles en Catalogo ERP para que soporte, administracion o desarrollo sepan que significa cada campo y que regla protege.

### Unidad base

Unidad en la que el ERP va a contar el inventario real del SKU.

- Ejemplos: `PZA`, `KG`, `L`, `M`.
- Para granel debe ser una unidad medible que permita decimales, como `KG`, `L` o `M`.
- No es necesariamente la unidad en la que se compra. Un proveedor puede vender `1 costal`, pero el inventario se guarda en `KG`.

### Factor conversion del SKU

Factor configurable junto a la unidad base del SKU.

- Vive en `erp_catalogo_skus.factor_unidad_base`.
- Debe capturarse siempre que se capture unidad base.
- Debe ser mayor a `0`.
- No pertenece a la unidad del catalogo general; pertenece al SKU.
- Ejemplo: SKU con unidad base `KG` y factor `4.000000` significa que la configuracion base del SKU representa una equivalencia operativa de `4 kg`.
- Ejemplo: SKU con unidad base `PZA` y factor `10.000000` puede representar una configuracion operativa de `10 piezas`.

Este factor es configurable por SKU. No se deben crear unidades como `COSTAL` solo para resolver equivalencias; la equivalencia debe vivir en el factor.

### Venta fraccionaria

Indica que el SKU puede venderse en cantidades decimales.

- Activo: se puede vender `0.250 kg`, `1.500 L`, `2.500 m`.
- Inactivo: se espera venta entera, normalmente `1 pza`, `2 pza`, etc.
- Solo debe activarse si la unidad base permite decimales.

### Precision decimal

Cantidad maxima de decimales operativos permitidos para capturar y mostrar cantidades de ese SKU.

- `0`: solo enteros.
- `1`: decimos, ejemplo `1.5`.
- `2`: centesimos, ejemplo `1.25`.
- `3`: milesimos, ejemplo `0.250 kg` o `1.500 L`.
- Rango permitido: `1` a `6` cuando hay venta fraccionaria.

Recomendacion inicial:

- Peso en `KG`: precision `3`, porque permite gramos (`0.001 kg`).
- Liquidos en `L`: precision `3`, porque permite mililitros (`0.001 L`).
- Longitud en `M`: precision `3`, porque permite milimetros (`0.001 m`) o centimetros (`0.010 m`).

### Incremento minimo venta

Paso minimo con el que se permite vender el producto.

- Si es `0.001 kg`, se puede vender `0.001`, `0.002`, `0.250`, `1.000`.
- Si es `0.250 kg`, se puede vender por cuartos de kilo: `0.250`, `0.500`, `0.750`, `1.000`.
- Si es `0.100 L`, se puede vender por decimos de litro: `0.100`, `0.200`, `1.500`.

Debe ser mayor a cero y no puede tener mas decimales que `Precision decimal`.

### Etiqueta unidad venta

Texto corto que se muestra al usuario para identificar la unidad en captura o lectura.

- Ejemplos: `kg`, `L`, `m`.
- No cambia la unidad real de inventario; solo ayuda visualmente.
- Si se deja vacio, el sistema puede usar la abreviatura de la unidad base.

### Etiqueta fraccionada

Excepcion para permitir etiqueta o serie en productos vendidos fraccionados.

- Normalmente un producto granel no debe generar etiqueta individual por cada fraccion vendida.
- Se activa solo si existe una regla especial del negocio, por ejemplo fracciones prepesadas, precortadas o etiquetadas antes de venderse.
- Si no esta activa, el sistema bloquea serie individual y etiqueta de trazabilidad en SKU granel.

### Prefijo etiqueta

Texto inicial usado para generar codigos internos de etiquetas de trazabilidad.

- Ejemplo: `ART` puede generar codigos tipo `ART-00001-...`.
- Aplica a productos etiquetados individualmente, no al granel comun.
- No es SKU, no es codigo de barras comercial y no debe usarse para representar presentaciones de compra.

### Plantilla etiqueta

Nombre de la plantilla que se usaria para imprimir o renderizar la etiqueta.

- Ejemplo: `estandar_qr`.
- Define formato visual/logico de etiqueta, no reglas de inventario.
- Si no hay flujo de impresion activo para ese SKU, puede quedar vacia.

### Tipo seguridad

Describe el tipo de seguridad fisica o visual de la etiqueta.

- Ejemplo: `void`, holograma, sello, QR interno.
- Sirve para trazabilidad o control interno.
- No debe confundirse con permisos del sistema.

### Instrucciones etiqueta

Indicaciones operativas para pegar o manejar la etiqueta.

- Ejemplo: `Pegar en zona visible`, `No cubrir numero de serie fabricante`.
- Sirve para Almacen/operacion cuando exista etiquetado fisico.

### Serie

Indica que cada unidad fisica necesita un numero de serie interno o control individual.

- Aplica a piezas unitarias rastreables.
- No aplica al granel comun, porque no hay una unidad fisica unica por cada fraccion.

### Serie fabricante

Indica que se registra o respeta el numero de serie que trae el fabricante.

- Aplica a productos serializados de origen.
- En venta fraccionaria solo debe permitirse si tambien existe regla de etiqueta fraccionada.

### Etiqueta de trazabilidad

Indica que el ERP debe generar o controlar una etiqueta interna para rastrear unidades.

- Aplica a bienes unitarios, lotes o piezas etiquetables.
- Para granel comun debe estar desactivada, salvo regla especial con `Etiqueta fraccionada`.

### Escanear venta

Indica que la salida en venta requiere escaneo para validar trazabilidad.

- Util para productos etiquetados o serializados.
- Para granel, debe revisarse con cuidado porque Ventas normalmente capturara cantidad, no una etiqueta por fraccion.

### Unidad de compra

Unidad en la que el proveedor vende el producto.

- Ejemplos: `PZA`, `CAJA`, `PAQ`.
- Es parte de SKU-proveedor, no de la regla de granel del SKU.

Uso recomendado de `PAQ`:

- `PAQ` significa paquete y debe usarse cuando el proveedor vende un conjunto cerrado que no conviene llamar caja.
- Ejemplos:
  - paquete con `3` piezas promocionales;
  - paquete con `6` repuestos;
  - paquete proveedor que no es caja fisica, pero si contiene varias unidades base;
  - blister o paquete comercial que se compra como conjunto.
- La equivalencia vive siempre en `factor_conversion`.
  - Ejemplo: unidad base `PZA`, unidad compra `PAQ`, factor `6.000000` significa `1 paquete = 6 piezas`.
  - Ejemplo: unidad base `KG`, unidad compra `PAQ`, factor `4.000000` significa `1 paquete/empaque = 4 kg`.
- No debe usarse `PAQ` si la compra realmente se captura como pieza individual y el factor es `1`.
- No debe reemplazar una presentacion de venta; para venta existen SKUs de presentacion y `erp_catalogo_sku_presentaciones`.

Si la unidad `PAQ` no se usa en ningun proveedor o SKU despues de estabilizar compras, se puede evaluar quitarla, pero por ahora es util como unidad generica de empaque sin crear catalogos excesivos.

### Unidades base por compra

Factor de conversion desde la unidad de compra hacia la unidad base de inventario.

- Costal 25 kg: unidad compra `PZA` o `COSTAL`, factor `25.000000`, unidad base `KG`.
- Garrafon 20 L: unidad compra `PZA` o `GARRAFON`, factor `20.000000`, unidad base `L`.
- Rollo 100 m: unidad compra `PZA` o `ROLLO`, factor `100.000000`, unidad base `M`.

Este campo es critico para Compras y Almacen: al recibir `1` unidad de compra, Inventario debe aumentar `factor_conversion` unidades base.

### Compra minima

Cantidad minima que el proveedor permite comprar en su unidad de compra.

- Si compra minima es `1`, se compra al menos `1 costal`.
- Si compra minima es `5`, se compra al menos `5 cajas`.
- No es stock minimo ni punto de reorden.

### Codigo interno / barras

Codigo escaneable de uso interno para identificar un SKU en Catalogo, Almacen o preparacion.

- Puede generarse desde el SKU con prefijo `INT-`.
- Ejemplo: `TP-40372-BOLSA-25G` puede generar `INT-TP-40372-BOLSA-25G`.
- Se guarda como codigo principal en `erp_catalogo_sku_codigos`.
- No sustituye un codigo comercial oficial `EAN`, `UPC` o `GTIN`.
- Si el codigo ya pertenece a otro SKU, el backend bloquea el guardado.

Uso recomendado:

- Para bolsas propias, presentaciones embolsadas y etiquetas internas.
- Para escaneo interno con lector compatible con codigos alfanumericos, como Code 128.
- Para marketplaces o retail que exijan codigo oficial, registrar/asignar un GTIN real por GS1 u organismo correspondiente.

## DDL base aplicado

Este DDL requirio respaldo externo y autorizacion explicita antes de aplicarse.

El 2026-06-19 se preparo `CatalogoErpEsquema` para auditar y planear estas columnas mediante `planActualizarCatalogoErp(false/true)`.

El 2026-06-19, con autorizacion del dueno del proyecto, se genero respaldo externo local en `storage/backups/artianilocal_catalogo_granel_20260619_0902.sql` y se aplico DDL directo solo para estas cinco columnas en `erp_catalogo_sku_reglas_inventario`. No se ejecuto el plan completo de Catalogo para evitar cambios fuera de alcance.

```sql
ALTER TABLE erp_catalogo_sku_reglas_inventario
  ADD COLUMN permite_venta_fraccionaria TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_escaneo_venta,
  ADD COLUMN precision_decimal TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER permite_venta_fraccionaria,
  ADD COLUMN incremento_minimo_venta DECIMAL(18,6) NOT NULL DEFAULT 1.000000 AFTER precision_decimal,
  ADD COLUMN unidad_venta_label VARCHAR(30) NULL AFTER incremento_minimo_venta,
  ADD COLUMN permite_etiqueta_fraccionada TINYINT(1) NOT NULL DEFAULT 0 AFTER unidad_venta_label;
```

Validaciones recomendadas en backend despues de aplicar DDL:

```sql
ALTER TABLE erp_catalogo_sku_reglas_inventario
  ADD CONSTRAINT chk_catalogo_granel_precision
    CHECK (precision_decimal BETWEEN 0 AND 6),
  ADD CONSTRAINT chk_catalogo_granel_incremento
    CHECK (incremento_minimo_venta > 0);
```

Nota: si la version de MySQL/MariaDB no aplica `CHECK` de forma confiable, estas validaciones deben vivir en `CatalogoErpDatos`.

## Validaciones de negocio propuestas

Para SKU sin venta fraccionaria:

- `precision_decimal` debe ser `0`.
- `incremento_minimo_venta` debe ser `1`.
- Si la unidad base permite decimales pero el SKU no es fraccionario, se permite inventario decimal solo si se justifica por recepcion/merma; queda pendiente de decision.

Para SKU con venta fraccionaria:

- `id_unidad_base` es obligatorio y debe apuntar a unidad activa.
- La unidad base debe tener magnitud medible: `masa`, `volumen` o `longitud`.
- `erp_catalogo_unidades.decimales_permitidos` debe ser `1`.
- `precision_decimal` debe estar entre `1` y `6`.
- `incremento_minimo_venta` debe ser mayor a `0`.
- `incremento_minimo_venta` no debe tener mas decimales que `precision_decimal`.
- `controla_inventario` debe ser `1`.
- No permitir `generar_etiqueta_interna=1` ni `requiere_serie=1` si `permite_venta_fraccionaria=1`, salvo que `permite_etiqueta_fraccionada=1` y exista una regla especial documentada.
- Si `requiere_caducidad=1`, tambien debe exigir `requiere_lote=1`; esta regla ya existe.

Estado en Catalogo 2026-06-19:

- Validaciones base implementadas en backend y JS para precision, incremento, unidad decimal, etiqueta individual y serie individual.
- `incremento_minimo_venta` se valida para que no exceda la precision decimal configurada.

Para proveedor/SKU proveedor:

- `id_unidad_compra` obligatorio.
- `factor_conversion > 0`.
- `cantidad_minima > 0`.
- Para compra de presentaciones cerradas, `id_unidad_compra` puede ser `CAJA`, `PAQ`, `PZA` u otra presentacion.
- El `factor_conversion` siempre debe expresarse en unidades base del SKU.

Estado en Catalogo 2026-06-19:

- `guardarSkuProveedor` valida SKU ERP vigente, unidad de compra activa, factor de conversion positivo y compra minima positiva.
- La UI de SKU-proveedor muestra una vista previa de conversion: `1 unidad de compra = N unidad base de inventario`.

## Ejemplos operativos

### Costal 25 kg -> inventario kg

Catalogo SKU:

- `id_unidad_base`: `KG`
- `tipo_inventario`: `inventariable`
- `controla_inventario`: `1`
- `permite_venta_fraccionaria`: `1`
- `precision_decimal`: `3`
- `incremento_minimo_venta`: `0.001`
- `generar_etiqueta_interna`: `0`

Proveedor:

- `id_unidad_compra`: `PZA` o `COSTAL` si se crea la unidad/presentacion.
- `factor_conversion`: `25.000000`
- Resultado futuro en Almacen: recibir `1 costal` debe sumar `25.000 kg`.
- Resultado futuro en Ventas: vender `0.250 kg` debe descontar `0.250 kg`.

### Garrafon 20 l -> inventario litro

Catalogo SKU:

- `id_unidad_base`: `L`
- `permite_venta_fraccionaria`: `1`
- `precision_decimal`: `3`
- `incremento_minimo_venta`: `0.100` o el minimo operativo autorizado.

Proveedor:

- `id_unidad_compra`: `PZA` o `GARRAFON` si se crea la presentacion.
- `factor_conversion`: `20.000000`
- Resultado futuro en Almacen: recibir `1 garrafon` debe sumar `20.000 L`.
- Resultado futuro en Ventas: vender `1.500 L` debe descontar `1.500 L`.

### Rollo 100 m -> inventario metro

Catalogo SKU:

- `id_unidad_base`: `M`
- `permite_venta_fraccionaria`: `1`
- `precision_decimal`: `3`
- `incremento_minimo_venta`: `0.010` si se vende por centimetro, o `0.001` si se requiere milimetro.

Proveedor:

- `id_unidad_compra`: `PZA` o `ROLLO` si se crea la presentacion.
- `factor_conversion`: `100.000000`
- Resultado futuro en Almacen: recibir `1 rollo` debe sumar `100.000 m`.
- Resultado futuro en Ventas: vender `2.500 m` debe descontar `2.500 m`.

### Presentaciones de venta derivadas de un mismo producto

Caso ejemplo: producto comprado en costales cerrados de `4 kg`, pero vendido en presentaciones de `500 g` de marca/proveedor y tambien en bolsitas propias de `25 g`, `50 g` y `100 g`.

Decision recomendada:

- Mantener un producto maestro unico, por ejemplo `TP-40372`.
- Mantener la materia/inventario base en `KG`.
- Registrar la compra proveedor como unidad de compra `PZA` o `COSTAL` con `factor_conversion=4.000000`.
- Crear SKUs de venta/presentacion separados cuando la presentacion sea comercialmente distinta:
  - `TP-40372-BOLSA-500G` -> descuenta `0.500 kg`.
  - `TP-40372-BOLSA-100G` -> descuenta `0.100 kg`.
  - `TP-40372-BOLSA-50G` -> descuenta `0.050 kg`.
  - `TP-40372-BOLSA-25G` -> descuenta `0.025 kg`.

Regla de negocio:

- El costal de `4 kg` es una presentacion de compra.
- El inventario base real es `KG`.
- Las bolsas de `500 g`, `100 g`, `50 g` y `25 g` son presentaciones de venta.
- Cada presentacion de venta debe poder tener su propio SKU, codigo de barras, imagen, precio, canal ecommerce, nombre comercial y regla de disponibilidad.

No conviene resolver esto solo con `incremento_minimo_venta`, porque `incremento_minimo_venta=0.025 kg` permitiria vender cantidades como `0.075 kg` o `0.175 kg`, aunque esas no existan como bolsa comercial autorizada.

Para venta ecommerce, la recomendacion es vender SKUs de presentacion cerrada, no cantidad libre:

- Producto web: bolsa `25 g`, bolsa `50 g`, bolsa `100 g`, bolsa `500 g`.
- Inventario descuenta siempre contra `KG`.
- Si el embolsado propio consume material y bolsa/etiqueta, ese proceso debe tratarse despues como conversion/produccion ligera o empaque, no como otro producto maestro sin relacion.

Pendiente futuro de esquema/flujo:

- Definir si Catalogo necesita una tabla de `presentaciones_venta` o si se modelara como SKUs hijos/variantes con `factor_salida_base`.
- Antes de Ventas/ecommerce, se debe decidir como se reservara stock para presentaciones pre-embolsadas vs stock a granel disponible para embolsar.

#### Existencia por presentacion vs existencia base

Para presentaciones que requieren trabajo previo, como embolsar `25 g`, `50 g` o `100 g`, no basta con calcular stock teorico desde el costal.

Ejemplo:

- Inventario base disponible: `4.000 kg`.
- Presentacion `25 g`: teoricamente alcanza para `160` bolsas.
- Pero si solo hay `20` bolsas ya embolsadas, la existencia vendible inmediata de esa presentacion debe ser `20`, no `160`.

Regla recomendada:

- Separar `stock base disponible` de `stock terminado por presentacion`.
- El stock base responde: "cuanto material tengo".
- El stock terminado responde: "cuantas bolsas listas puedo vender o surtir hoy".

Modo de disponibilidad por presentacion:

1. `preparada`
   - Solo se vende si hay bolsas ya embolsadas/terminadas.
   - Ecommerce muestra existencia real de esa presentacion.
   - Ejemplo: hay `20` bolsas de `25 g`; se pueden vender `20`.

2. `bajo_demanda`
   - Se puede vender aunque no este embolsada, usando stock base disponible.
   - Debe generar pendiente de empaque/surtido antes de entregar.
   - Conviene limitar por capacidad operativa diaria.

3. `mixta`
   - Primero vende stock terminado.
   - Si se agota, permite venta bajo demanda solo si el negocio lo autoriza.

Para ecommerce, recomendacion conservadora:

- Publicar como disponible solo el stock terminado si la presentacion toma tiempo de preparacion.
- Usar venta bajo demanda solo si la pagina muestra tiempo de preparacion o si el equipo acepta ese compromiso operativo.

Flujo futuro sugerido:

1. Recibir costal: aumenta stock base `KG`.
2. Embolsar: consume stock base y genera stock terminado de una presentacion.
3. Vender bolsa: descuenta stock terminado.
4. Si se permite bajo demanda: la venta reserva stock base y crea tarea de empaque.

Ejemplo con `TP-40372`:

- Stock base: `4.000 kg`.
- Bolsa `25 g`: factor salida `0.025 kg`.
- Bolsa `50 g`: factor salida `0.050 kg`.
- Bolsa `100 g`: factor salida `0.100 kg`.
- Bolsa `500 g`: factor salida `0.500 kg`.

Si se embolsan manualmente:

- Producir `20` bolsas de `25 g` consume `0.500 kg` del stock base.
- Aumenta stock terminado de `TP-40372-BOLSA-25G` en `20`.
- El stock base restante queda en `3.500 kg`.

Pendiente de diseno:

- Definir tabla o flujo para `stock terminado por presentacion`.
- Definir si el embolsado sera operacion de Almacen, produccion ligera o ajuste controlado.
- Definir si ecommerce consultara solo stock terminado o tambien capacidad bajo demanda.

#### Propuesta de estructura configurable

Auditoria rapida:

- Catalogo ya tiene producto maestro, SKUs, atributos de variante, imagenes, precios y vinculos ecommerce.
- Eso alcanza para mostrar/vender cada presentacion como SKU comercial.
- Lo que falta es una relacion formal que diga: "este SKU presentacion consume esta cantidad del SKU base".

Decision recomendada:

- Usar `erp_catalogo_skus` para cada presentacion vendible.
- Agregar una tabla de relacion de presentaciones, no solo un atributo de variante.
- No duplicar producto maestro.

Tabla propuesta: `erp_catalogo_sku_presentaciones`

Campos sugeridos:

- `id_sku_presentacion`
  - Identificador interno.
- `id_sku_base`
  - SKU que representa la materia/inventario base. Ejemplo: `TP-40372` en `KG`.
- `id_sku_presentacion`
  - SKU vendible de la presentacion. Ejemplo: `TP-40372-BOLSA-25G`.
- `factor_salida_base`
  - Cantidad de unidad base que consume una unidad de la presentacion.
  - Ejemplo: bolsa `25 g` consume `0.025 kg`.
- `modo_disponibilidad`
  - `preparada`, `bajo_demanda`, `mixta`.
- `consume_stock_base_en`
  - `preparacion` o `venta`.
  - `preparacion`: al embolsar se descuenta base y sube stock terminado.
  - `venta`: al vender se descuenta base directamente; util para bajo demanda.
- `requiere_empaque`
  - `1` si requiere trabajo fisico previo.
- `capacidad_diaria`
  - Cantidad maxima sugerida que se puede preparar/vender bajo demanda por dia.
- `merma_porcentaje`
  - Merma esperada al preparar esa presentacion, si aplica.
- `estatus`
  - `activa`, `inactiva`.

Indices/contratos:

- Unico recomendado: `id_sku_presentacion` para que un SKU vendible tenga una sola regla de presentacion activa.
- Indice por `id_sku_base` para ver todas las presentaciones derivadas de un producto base.
- Foreign keys a `erp_catalogo_skus`.

Reglas:

- `id_sku_base` y `id_sku_presentacion` no pueden ser iguales.
- Ambos SKUs deben pertenecer preferentemente al mismo producto maestro.
- `factor_salida_base` debe ser mayor a `0`.
- Si `modo_disponibilidad='preparada'`, ecommerce debe consultar stock terminado de la presentacion.
- Si `modo_disponibilidad='bajo_demanda'`, ecommerce debe consultar stock base teorico y respetar capacidad operativa.
- Si `consume_stock_base_en='preparacion'`, la venta no descuenta base; descuenta stock terminado.
- Si `consume_stock_base_en='venta'`, la venta descuenta base y debe crear/registrar tarea de empaque si `requiere_empaque=1`.

Ejemplo `TP-40372`:

| SKU base | SKU presentacion | Factor salida base | Modo | Consume base en |
| --- | --- | ---: | --- | --- |
| `TP-40372` | `TP-40372-BOLSA-500G` | `0.500000` | `preparada` o `mixta` | `preparacion` |
| `TP-40372` | `TP-40372-BOLSA-100G` | `0.100000` | `preparada` | `preparacion` |
| `TP-40372` | `TP-40372-BOLSA-50G` | `0.050000` | `preparada` | `preparacion` |
| `TP-40372` | `TP-40372-BOLSA-25G` | `0.025000` | `preparada` | `preparacion` |

DDL propuesto, no ejecutado:

```sql
CREATE TABLE erp_catalogo_sku_presentaciones (
  id_sku_presentacion_regla BIGINT NOT NULL AUTO_INCREMENT,
  id_sku_base BIGINT NOT NULL,
  id_sku_presentacion BIGINT NOT NULL,
  factor_salida_base DECIMAL(18,6) NOT NULL,
  modo_disponibilidad VARCHAR(30) NOT NULL DEFAULT 'preparada',
  consume_stock_base_en VARCHAR(30) NOT NULL DEFAULT 'preparacion',
  requiere_empaque TINYINT(1) NOT NULL DEFAULT 1,
  capacidad_diaria DECIMAL(18,6) NULL,
  merma_porcentaje DECIMAL(9,4) NOT NULL DEFAULT 0.0000,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activa',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_sku_presentacion_regla),
  UNIQUE KEY idx_catalogo_presentacion_sku (id_sku_presentacion),
  KEY idx_catalogo_presentacion_base (id_sku_base),
  CONSTRAINT fk_catalogo_presentacion_base FOREIGN KEY (id_sku_base) REFERENCES erp_catalogo_skus (id_sku),
  CONSTRAINT fk_catalogo_presentacion_sku FOREIGN KEY (id_sku_presentacion) REFERENCES erp_catalogo_skus (id_sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

No aplicar este DDL hasta decidir el flujo de stock terminado y tener respaldo/autorizacion.

#### Glosario de captura: Configurar presentacion de venta

Estos son los campos visibles en la pestana `Presentaciones` del modal de producto.

##### SKU base

SKU que guarda la existencia real o materia disponible.

Ejemplo: `TP-40372` configurado en `KG`.

Si se compra un costal de `4 kg`, Almacen deberia aumentar el stock base en `4.000 kg` cuando esa recepcion exista en el flujo futuro.

##### SKU presentacion

SKU comercial que se vende como una presentacion especifica.

Ejemplos:

- `TP-40372-BOLSA-25G`
- `TP-40372-BOLSA-50G`
- `TP-40372-BOLSA-100G`
- `TP-40372-BOLSA-500G`

Cada presentacion puede tener nombre, precio, imagen, codigo interno/codigo de barras y canal ecommerce propio.

##### Factor salida base

Cantidad del SKU base que consume una unidad de la presentacion.

Ejemplos si el SKU base esta en `KG`:

- Bolsa `25 g` -> `0.025000`
- Bolsa `50 g` -> `0.050000`
- Bolsa `100 g` -> `0.100000`
- Bolsa `500 g` -> `0.500000`

Este factor no crea stock por si solo; solo define la equivalencia de Catalogo.

##### Modo

Define como se considera disponible la presentacion.

- `Preparada`: solo se vende si ya hay unidades listas. Ejemplo: ya embolsaste 20 bolsas de 25 g.
- `Bajo demanda`: se puede vender usando stock base, pero requiere prepararla antes de entregar.
- `Mixta`: primero usa presentaciones ya preparadas y despues permite preparar bajo demanda si el negocio lo autoriza.

Para ecommerce, la recomendacion conservadora es usar `Preparada` cuando embolsar toma tiempo.

##### Consume base en

Indica en que momento se descuenta la materia base.

- `Preparacion`: se descuenta cuando se embolsa o se arma la presentacion. Recomendado para stock terminado.
- `Venta`: se descuenta cuando se vende. Util para venta bajo demanda.

Regla actual:

- Si `Modo = Preparada`, debe consumir en `Preparacion`.
- Si `Modo = Bajo demanda`, debe consumir en `Venta`.

##### Capacidad diaria

Limite operativo sugerido de cuantas presentaciones se pueden preparar en un dia.

Ejemplo: si solo puedes embolsar 100 bolsitas de 25 g al dia, captura `100`.

Puede quedar vacio si no se va a controlar todavia.

##### Merma %

Porcentaje de perdida esperada al preparar la presentacion.

Ejemplo: si al embolsar se pierde un poco de producto, podrias capturar `1.0000`.

Si no aplica, dejar `0`.

##### Estado

Controla si la regla se usa o queda solo como historial.

- `Activa`: la relacion puede usarse.
- `Inactiva`: la relacion queda guardada, pero ya no debe usarse operativamente.

##### Requiere empaque/preparacion

Indica si esa presentacion necesita trabajo fisico antes de venderse.

Ejemplos con `Si`:

- Bolsas propias de `25 g`, `50 g`, `100 g`.
- Paquetes armados internamente.

Ejemplos con `No`:

- Bolsa de `500 g` que ya llega cerrada y etiquetada por proveedor.
- Presentacion comercial lista para vender.

Si una presentacion llega cerrada por proveedor, conviene ademas configurar su relacion proveedor en la pestana `Proveedores`.

## Impacto por modulo futuro

Catalogo:

- Ya captura y valida reglas de granel por SKU.
- Ya muestra unidad base, precision e incremento en la ficha SKU.
- Falta implementar presentaciones derivadas con relacion `SKU presentacion -> SKU base` antes de exponer bolsas cerradas en ecommerce.

Almacen:

- Debe recibir en unidad de compra y convertir a unidad base antes de afectar existencia.
- Debe conservar lote/caducidad por la cantidad base recibida.
- Debe ser el modulo que genere etiquetas fisicas cuando el producto ya existe fisicamente:
  - en Recepcion, si se compro una presentacion ya empacada/lista para vender;
  - en Preparacion/Empaque, si se embolso o armo internamente desde existencia base.
- Debe contemplar una operacion futura de preparacion que consuma SKU base y cree existencia de SKU presentacion.

Inventario:

- Debe mostrar saldos en unidad base.
- Debe aceptar movimientos decimales conforme a precision del SKU.
- Debe mostrar saldos separados para SKU base y SKU presentacion cuando existan presentaciones preparadas.
- Debe explicar por kardex la conversion/preparacion, pero no debe ser la pantalla donde se embolsa o produce la presentacion.

Ventas:

- Debe capturar cantidad fraccionaria segun `incremento_minimo_venta`.
- Debe descontar por unidad base y respetar FEFO/lote cuando aplique.
- Si se vende una presentacion en modo `Preparada`, debe validar existencia de esa presentacion.
- Si se permite una presentacion `Bajo demanda`, debe generar o exigir una tarea de preparacion antes de entrega, no asumir existencia teorica.

Costos:

- Debe calcular costo promedio por unidad base.
- Ejemplo: costal 25 kg con costo 500 -> costo base 20 por kg.

## Propuesta de implementacion

1. Diseno funcional aprobado.
2. `CatalogoErpEsquema` preparado para auditar/crear columnas faltantes.
3. Con respaldo externo y autorizacion explicita, agregar columnas propuestas en `erp_catalogo_sku_reglas_inventario`.
4. Actualizar `CatalogoErpDatos`:
   - consultar reglas de granel;
   - guardar reglas de granel;
   - validar precision, incremento, unidad y etiqueta.
   - Estado: hecho el 2026-06-19.
5. Actualizar `productos.php` y `productos.js` para capturar:
   - venta fraccionaria;
   - precision decimal;
   - incremento minimo;
   - etiqueta de unidad opcional;
   - excepcion de etiqueta fraccionada.
   - Estado: hecho el 2026-06-19.
6. Crear incidencias de calidad para SKU con unidad decimal pero sin regla de granel definida, solo si el dueno decide tratarlos como pendientes.
   - Estado: no crear incidencias automaticas todavia; se agrego alerta visual `Unidad decimal` en ficha SKU para revision operativa.
7. Despues de aprobar Catalogo, abrir tareas separadas para Almacen, Inventario y Ventas.

## Decision pendiente

Confirmar politica de unidad base por familia/SKU:

- Peso: recomendacion `KG` con precision `3`.
- Liquidos: recomendacion `L` con precision `3`, salvo que operativamente convenga `ML` entero.
- Longitud: recomendacion `M` con precision `3`.

Confirmar si basta con unidades generales como `PZA`, `CAJA` y `PAQ` mas `factor_conversion` por proveedor. Recomendacion actual: no crear unidades por cada empaque fisico si la equivalencia se puede expresar en la relacion proveedor-SKU.

## Decision transversal - Existencia y etiquetas de presentaciones

Fecha: 2026-06-20  
Estado: Documentado; pendiente diseno en Almacen

Catalogo queda como fuente de configuracion, no como fuente de existencia fisica.

Regla:

- Si el proveedor entrega una presentacion cerrada/lista para vender, la existencia nace en Almacen Recepcion usando el SKU presentacion.
- Si el negocio embolsa, corta o arma una presentacion internamente, la existencia nace en Almacen Preparacion/Empaque.
- Las etiquetas se generan cuando existe la unidad fisica:
  - al recibir, para unidades/presentaciones compradas ya listas;
  - al preparar, para bolsas o presentaciones armadas internamente.
- Catalogo solo guarda el codigo interno, regla, factor y datos de venta de la presentacion.

Ejemplo `TP-40372`:

- Se compra costal cerrado de `4 kg`: recepcion crea existencia base `4.000 kg`.
- Se compra bolsa de proveedor `500 g` lista para vender: recepcion crea existencia del SKU presentacion `TP-40372-BOLSA-500G`.
- Se embolsan internamente bolsas de `25 g`, `50 g` o `100 g`: preparacion consume existencia base y crea existencia de esas presentaciones.
- Las etiquetas internas de bolsas propias se generan en la preparacion, no al configurar el SKU.

## Hallazgo CAT-GRANEL-H001 - Factor de compra a inventario incompleto

Fecha: 2026-06-21  
SKU: `TP-40372`  
Severidad: Alta  
Estado: Documentado; requiere correccion controlada de configuracion y decision sobre saldo historico

Sintoma:

- En Preparacion/Empaque solo aparecen `5 kg` disponibles.
- Operativamente se recibieron `5 costales de 4 kg`.
- Existencia esperada: `20 kg`.
- Existencia registrada: `5 kg`.

Evidencia consultada:

- `erp_catalogo_skus`:
  - `TP-40372`
  - `id_sku=146`
  - unidad base `KG`
  - `factor_unidad_base=1.000000`
  - estatus `activo`
- `erp_catalogo_sku_proveedores`:
  - `id_sku_proveedor=2310`
  - proveedor `SUNNY`
  - unidad compra `CAJA`
  - `factor_conversion=1.000000`
  - estatus `activo`
- `erp_compras_ordenes_detalle`:
  - orden `20`
  - `id_detalle=62`
  - `id_sku_proveedor=2310`
  - unidad `pza`
  - cantidad `5.000000`
  - cantidad recibida `5.000000`
- `erp_almacen_recepciones_detalle`:
  - recepcion `REC-OC-20`
  - unidad `pza`
  - cantidad recibida `5.0000`
- `erp_inventario_movimientos`:
  - movimientos `33` y `34`
  - entradas `4.0000` y `1.0000`
  - total inventario generado `5.0000`

Conclusion:

- La unidad base del SKU esta bien: `KG`.
- El lugar correcto para la conversion compra -> inventario ya existe: `erp_catalogo_sku_proveedores.factor_conversion`.
- La relacion proveedor-SKU de `TP-40372` esta incompleta para este caso: hoy dice `1 unidad de compra = 1 kg`.
- Para la compra real debe decir `1 costal = 4 kg`.
- Si no existe unidad `COSTAL`, se puede usar temporalmente `PZA` o `CAJA` como unidad de compra, pero el `factor_conversion` debe ser `4.000000` y la UI debe mostrar claramente la equivalencia.

Criterio robusto:

- `erp_catalogo_skus.id_unidad_base` responde: "en que unidad vive el inventario".
- `erp_catalogo_skus.factor_unidad_base` responde: "que factor configurable usa este SKU junto a su unidad base".
- `erp_catalogo_sku_proveedores.id_unidad_compra` responde: "como lo vende este proveedor".
- `erp_catalogo_sku_proveedores.factor_conversion` responde: "que factor de compra aplica para este proveedor/SKU cuando el proveedor vende en otra presentacion".
- `erp_catalogo_sku_presentaciones.factor_salida_base` responde: "cuanto consume una presentacion de venta".
- No crear unidades especificas para costal, garrafon o rollo si el factor configurable del SKU/proveedor resuelve la equivalencia.

Configuracion esperada para `TP-40372`:

- SKU base: `TP-40372`.
- Unidad base inventario: `KG`.
- Factor conversion del SKU: `4.000000`.
- Proveedor: `SUNNY`.
- Unidad de compra: una unidad operativa general, por ejemplo `CAJA`, `PZA` o `PAQ`, segun como se capture la compra.
- Factor conversion: `4.000000`.
- Lectura operativa: `1 unidad de compra = 4.000 kg`.
- Compra de `5` unidades de compra debe generar `20.000 kg` de inventario base.

Riesgo actual:

- Compras y Recepcion arrastran la cantidad de la orden sin convertirla a cantidad base cuando guardan lotes/existencias/movimientos.
- Si se corrige solo el factor en Catalogo, las compras futuras tendran el contrato correcto, pero Recepcion tambien debe aplicar o visualizar la conversion antes de afectar inventario.
- La recepcion historica `REC-OC-20` ya genero movimientos y existencias por `5 kg`; corregirla requiere respaldo externo y una decision de ajuste/reversa controlada.

Acciones recomendadas:

1. Configuracion hacia adelante:
   - Con respaldo y autorizacion, actualizar `erp_catalogo_sku_proveedores.id_unidad_compra` y `factor_conversion` de `id_sku_proveedor=2310` a la equivalencia correcta.
   - No crear una unidad distinta por cada empaque fisico si el factor de compra lo resuelve; la unidad debe ser generica y el factor debe guardar la equivalencia.
   - La vista de Catalogo > Producto > Proveedores debe mostrar la equivalencia completa, no solo el factor: `1 unidad compra = N unidad base`.
   - Si la unidad base permite decimales, la unidad de compra es distinta y el factor queda en `1`, la UI debe marcar `Revisar factor`.
2. Flujo Compras/Recepcion:
   - La orden debe mostrar cantidad comprada en unidad proveedor y cantidad base esperada.
   - Recepcion debe validar que `cantidad_recibida_compra * factor_conversion = cantidad_base_inventario`.
   - Inventario debe recibir cantidad base, no cantidad fisica de costales.
3. Dato historico:
   - No editar directamente existencias/movimientos sin respaldo.
   - Resolver `REC-OC-20` como ajuste documentado o correccion transaccional, dejando evidencia del motivo: `CAT-GRANEL-H001`.

Contrato recomendado para Compras/Recepcion:

- La cantidad de compra debe conservarse en la unidad del proveedor.
  - Ejemplo: `5 costales`.
- La cantidad base debe calcularse para inventario.
  - Ejemplo: `5 * 4.000000 = 20.000 kg`.
- El costo unitario de compra puede seguir representando costo por costal.
- El costo unitario de inventario debe derivarse para la unidad base cuando se afecte existencia.
  - Ejemplo: si el costal cuesta `737.068966`, el costo base por kg es `184.2672415`.
- La recepcion debe mostrar ambas lecturas antes de confirmar:
  - `Recibido: 5 costales`.
  - `Entrada inventario: 20 kg`.
- El pendiente de la orden debe descontarse en cantidad de compra.
- Existencias, movimientos y preparacion deben operar en cantidad base.

Estructura faltante probable:

- Las tablas actuales de recepcion/orden conservan una sola `cantidad` y un texto `unidad`, por lo que no distinguen explicitamente:
  - cantidad comprada;
  - unidad comprada;
  - factor aplicado;
  - cantidad base inventario;
  - costo unitario base.
- Antes de convertir automaticamente en Recepcion, se debe auditar si conviene agregar columnas o una tabla de detalle de conversion para no perder trazabilidad ni distorsionar costos/pendientes.

Correccion de configuracion aplicada 2026-06-21:

- Respaldo externo previo:
  - `storage/backups/artianilocal_catalogo_granel_tp40372_20260621.sql`.
- Se actualizo relacion proveedor-SKU:
  - `id_sku_proveedor=2310`
  - SKU `TP-40372`
  - proveedor `SUNNY`
  - unidad compra `COSTAL`
  - `factor_conversion=4.000000`
- Verificacion:
  - `1 costal = 4.000000 kg`
  - `5 costales = 20.000000 kg`

Correccion posterior de criterio 2026-06-21:

- Respaldo externo previo:
  - `storage/backups/artianilocal_catalogo_granel_revert_costal_20260621.sql`.
- Se elimino la unidad `COSTAL`, porque no se deben crear unidades por cada empaque fisico si la equivalencia pertenece a la relacion proveedor-SKU.
- La relacion `id_sku_proveedor=2310` quedo:
  - unidad compra `CAJA`
  - `factor_conversion=4.000000`
  - unidad base del SKU `KG`
- Lectura actual:
  - `1 caja/unidad de compra = 4.000000 kg`
  - `5 cajas/unidades de compra = 20.000000 kg`
- Si operativamente se quiere mostrar "costal" al comprador, debe resolverse como descripcion/presentacion de compra o texto operativo de la relacion proveedor-SKU, no como unidad base ni como unidad con factor propio.

Correccion de UI y SKU aplicada 2026-06-21:

- Se agrego input editable `Factor conversion` junto a `Unidad base` en:
  - alta de producto/SKU;
  - alta/edicion de SKU en modal de producto;
  - creacion de SKU temporal desde incidencia.
- El backend guarda `factor_unidad_base` en `erp_catalogo_skus`.
- Se valida que el factor sea mayor a `0`.
- `TP-40372` quedo:
  - unidad base `KG`;
  - `factor_unidad_base=4.000000`.
- La tabla de SKU en Catalogo muestra la unidad y el factor para que no quede oculto.

Alcance de la correccion:

- Corrige la configuracion para compras futuras y para la lectura operativa en Catalogo.
- No corrige automaticamente la recepcion historica `REC-OC-20`, porque ya genero lotes, movimientos y existencias por `5 kg`.
- La correccion de `REC-OC-20` debe hacerse como tarea separada con respaldo y movimiento/ajuste documentado.

## Cierre de esta auditoria

- Auditoria de esquema actual: hecha.
- Propuesta de campos y ubicacion: hecha.
- DDL base de columnas: aplicado con respaldo previo.
- Migraciones amplias de Catalogo: no ejecutadas.
- Verificacion: `erp_catalogo_sku_reglas_inventario` ya no reporta faltantes para las cinco columnas de granel.
- Captura/guardado base en Catalogo: implementado en `CatalogoErpDatos`, `productos.php` y `productos.js`.
- Refinamiento de validaciones: implementado para serie individual, decimales de incremento y unidad/SKU proveedor activos.
- UX de captura: los campos de precision, incremento y etiqueta de unidad se muestran solo cuando `Venta fraccionaria` esta activa; el JS de productos quedo versionado para evitar cache obsoleta.
- UX de proveedor: el factor de conversion muestra vista previa contra la unidad base del SKU y valida factor/compra minima antes de enviar.
- UX del modal: la pestaña Inventario organiza los campos en secciones `Reglas de stock`, `Control fisico`, `Venta a granel` y `Etiquetado y trazabilidad`; los campos clave tienen ayuda contextual con icono de pregunta.
- Calidad visual: SKU activo con unidad base decimal y sin venta fraccionaria muestra alerta `Unidad decimal`; SKU granel activo sin proveedor muestra `Granel sin proveedor`.
- Documentacion operativa: se agrego glosario de campos/titulos para granel, etiquetado y conversion proveedor -> inventario.
- Presentaciones derivadas: documentada propuesta `erp_catalogo_sku_presentaciones`; DDL propuesto no ejecutado.
- Esquema de presentaciones derivadas: `CatalogoErpEsquema` ya incluye `erp_catalogo_sku_presentaciones` en auditoria critica y plan de actualizacion.
- Migracion de presentaciones derivadas: ejecutada el 2026-06-19 con respaldo previo `storage/backups/artianilocal_catalogo_presentaciones_20260619_ddl.sql`; auditoria posterior sin faltantes para columnas e indices de `erp_catalogo_sku_presentaciones`.
- Backend de presentaciones derivadas: `CatalogoErpDatos` ya consulta, guarda y desactiva reglas `SKU base -> SKU presentacion`; `CatalogoErp` expone endpoints con permiso `catalogo.editar` y auditoria.
- UI de presentaciones derivadas: el modal de producto ya tiene pestana `Presentaciones` para listar y capturar factor, modo de disponibilidad, momento de consumo, empaque, capacidad y merma.
- Edicion de presentaciones: la UI permite cargar una regla existente al formulario, guardar cambios o cancelar edicion sin crear otra regla accidental.
- Calidad de presentaciones: la tabla muestra alertas cuando el factor usa decimales sobre una unidad base que no permite decimales o cuando la magnitud de unidad base/presentacion no coincide.
- UX de alta de SKU: al pasar de editar un SKU a agregar otro, el formulario limpia identidad (`id_sku`, SKU, nombre, codigo de barras y motivo) y solo conserva reglas de inventario/fiscal/precio como plantilla visible, para evitar actualizar o duplicar por confusion.
- Codigos internos: alta/edicion de SKU ya permiten generar un codigo interno escaneable desde el SKU con prefijo `INT-`; queda diferenciado de codigos comerciales oficiales.
- UAT tecnico backend sin escritura: `storage/uat/uat_catalogo_granel_validaciones.php`.
  - Resultado 2026-06-19: `ok=true`, sin fallas.
  - Casos cubiertos: `KG` valido, `PZA` no decimal bloqueado, precision fuera de rango bloqueada, incremento con mas decimales bloqueado, serie sin etiqueta fraccionada bloqueada, serie con etiqueta fraccionada permitida.
- Pendiente: validacion visual y UAT funcional de alta/edicion de SKU granel antes de pasar reglas a Almacen, Inventario o Ventas.
- Modulos Almacen, Inventario y Ventas: no modificados.

## Checklist UAT visual pendiente

1. Abrir Catalogo ERP > Productos.
2. Editar o crear un SKU de prueba con unidad base `KG`, `L` o `M`.
3. Activar `Venta fraccionaria`.
4. Confirmar que aparecen precision, incremento minimo y etiqueta de unidad.
5. Guardar con precision `3`, incremento `0.001` y unidad venta `kg`/`L`/`m`.
6. Reabrir el producto y confirmar que los valores recargan.
7. Vincular proveedor y confirmar preview: `1 unidad de compra = N unidad base de inventario`.
8. Probar errores visuales:
   - `PZA` con venta fraccionaria debe bloquear.
   - precision `7` debe bloquear.
   - incremento `0.0015` con precision `3` debe bloquear.
   - serie/etiqueta individual sin etiqueta fraccionada debe bloquear.
