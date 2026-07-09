# ERP Almacen/Recepciones - Auditoria de unidad de compra y conversion a inventario

Fecha: 2026-06-24
Modulo: ERP > Almacen > Recepciones
Fase: auditoria y diseno previo a cambios

## Objetivo

Preparar Recepcion para que convierta lo recibido fisicamente en existencia real de inventario sin romper Compras ni mezclar flujos legacy. Compras registra la intencion de compra; Recepcion debe validar SKU, unidad de compra, factor de conversion, variante y reglas de inventario antes de generar existencia vendible.

## Regla operativa

- Catalogo ERP define identidad del SKU, unidad base, reglas de granel/fraccionable, variantes y presentaciones.
- Proveedores/listas define como se compra: SKU proveedor, unidad de compra, factor de conversion, costo y evidencia.
- Compras usa esa configuracion para ordenar, pero no convierte existencia.
- Recepcion confirma lo recibido fisicamente y convierte a unidad base de inventario cuando aplica.
- Inventario solo registra saldos/movimientos resultantes; no debe resolver la preparacion ni la clasificacion de compra.
- No se generan presentaciones preparadas desde Catalogo ni desde Recepcion si se compro producto base a granel. Las presentaciones propias nacen en Preparacion/Empaque.

## Auditoria del flujo actual

### 1. Obtencion de renglones de OC

`Almacenes::preparar_recepcion_desde_orden_compra()` obtiene el encabezado y detalle de la OC, crea o reutiliza `erp_almacen_recepciones` y registra `erp_almacen_recepciones_detalle`.

El detalle viene de `consultar_detalle_orden_compra_para_recepcion()`. Actualmente toma:

- `id_sku_erp`
- `id_producto_proveedor`
- `sku`
- `nombre_producto`
- `unidad`
- `cantidad`
- `costo_unitario`

No toma todavia:

- `id_sku_proveedor`
- `id_unidad_compra`
- `factor_conversion`
- unidad base del SKU
- estatus del SKU
- senales de variante o clasificacion pendiente

### 2. SKU ERP

Recepcion si puede trabajar con `id_sku_erp` cuando la OC lo trae. Si falta, intenta resolver por SKU con `resolver_identidad_sku_erp()`.

Riesgo: si el SKU esta `fusionado`, la funcion lo excluye y termina como "Sin SKU maestro ERP", pero no deja una razon operativa clara. Si el SKU esta `inactivo`, `descontinuado` o `en_revision`, no hay advertencia especifica antes de recibir.

### 3. SKU proveedor y unidad de compra

Compras moderna guarda `id_sku_proveedor` en `erp_compras_ordenes_detalle`, pero Recepcion todavia persiste `id_producto_proveedor` en `erp_almacen_recepciones_detalle`.

Esto provoca que Recepcion pierda la relacion proveedor donde vive la unidad/factor real de compra.

Evidencia:

- OC 20, detalle 62, SKU `TP-40372`:
  - `id_sku_erp=146`
  - `id_sku_proveedor=2310`
  - unidad compra configurada: `caja`
  - `factor_conversion=4.000000`
  - unidad base inventario: `kg`
  - cantidad comprada: `5.000000`
- REC-OC-20, detalle 13:
  - `id_sku_erp=146`
  - `id_producto_proveedor=0`
  - `unidad=pza`
  - `cantidad_ordenada=5.0000`
  - `cantidad_recibida=5.0000`

La recepcion guardada no conserva `id_sku_proveedor=2310`, no conserva `caja`, no conserva factor `4`, y por eso no puede demostrar que debio entrar `20 kg`.

### 4. Cantidades y conversion

Hoy Recepcion usa una sola cantidad para todo:

- cantidad ordenada
- cantidad recibida
- lote recibido
- existencia
- movimiento de inventario

En productos pza contra pza esto funciona. En granel o empaque proveedor no funciona, porque se necesita separar:

- cantidad de compra fisica: ejemplo `5 caja`
- cantidad base de inventario: ejemplo `20 kg`

### 5. Generacion de existencia

`guardar_recepcion_almacen()` llama a:

- `insertar_recepcion_lote()`
- `actualizar_existencia()`
- `insertar_movimiento_inventario()`
- `generar_unidades_inventario()`

Todas usan la cantidad capturada por el operador sin aplicar `factor_conversion`.

El costo tiene una correccion parcial en `costo_unitario_inventario()`, porque divide por `factor_unidad_base` cuando es mayor a 1. Eso no basta si la cantidad no se convierte. El movimiento puede quedar con costo unitario base, pero cantidad de compra, provocando total incorrecto.

### 6. Alertas e incidencias

Existe `erp_almacen_recepciones_incidencias`, pero el flujo actual se usa principalmente para caducidad/excedente. No hay incidencias especificas para:

- unidad/factor de compra pendiente
- factor sospechoso
- variante ambigua
- SKU fusionado
- SKU inactivo/descontinuado
- SKU proveedor ausente cuando el renglon viene de proveedor

### 7. Lote, caducidad, serie y etiquetas

Recepcion ya consulta reglas de inventario por SKU y puede exigir:

- lote
- caducidad
- serie/codigo interno
- etiqueta de trazabilidad

Esto esta bien ubicado en Recepcion. La brecha es que esas reglas se aplican despues de elegir el SKU, pero todavia no se valida la calidad de la relacion proveedor/unidad/factor antes de generar existencia.

### 8. Variantes ambiguas

Catalogo ya tiene estructura para atributos de variante (`erp_catalogo_sku_atributos`) y atributos marcados como variante (`erp_catalogo_atributos.es_variante`).

Recepcion no tiene flujo para decir: "este renglon de proveedor no identifica variante interna; selecciona variante o distribuye cantidades". Si el renglon llega con un SKU ERP ambiguo o sin variante suficiente, el sistema no debe meter existencia vendible a un SKU incorrecto.

## Hallazgos

### ALM-REC-CAT-H001 - Recepcion pierde `id_sku_proveedor`

Compras usa `erp_compras_ordenes_detalle.id_sku_proveedor`, pero Recepcion solo copia `id_producto_proveedor`. Resultado: se pierde la relacion moderna proveedor-SKU donde vive unidad/factor.

Impacto: alto. Sin esa relacion no se puede convertir correctamente compra fisica a inventario.

### ALM-REC-CAT-H002 - No hay doble cantidad compra/base

Las tablas de recepcion detalle y lotes guardan una sola cantidad. No distinguen entre "5 cajas recibidas" y "20 kg a inventario".

Impacto: alto. Afecta granel, cajas, costales, bolsas proveedor y cualquier compra distinta a unidad base.

### ALM-REC-CAT-H003 - La existencia se genera con la cantidad capturada sin conversion

`actualizar_existencia()` e `insertar_movimiento_inventario()` reciben la cantidad del formulario. No aplican `factor_conversion`.

Impacto: alto. Puede generar existencia vendible incorrecta.

### ALM-REC-CAT-H004 - Costo convertido parcialmente

El costo unitario puede dividirse por `factor_unidad_base`, pero la cantidad no se convierte. En recepciones con factor, costo y cantidad quedan desalineados.

Impacto: alto para valuacion y kardex.

### ALM-REC-CAT-H005 - UI no muestra unidad de compra, factor ni cantidad base

La pantalla muestra unidad textual del detalle (`pza`, `Servicio`, etc.) y cantidad pendiente, pero no muestra:

- unidad de compra configurada
- factor de conversion
- cantidad resultante en unidad base
- advertencia si falta factor

Impacto: medio-alto. El operador no tiene contexto suficiente para validar fisicamente.

### ALM-REC-CAT-H006 - Falta bloqueo/incidencia por unidad/factor pendiente

Si falta unidad o factor, el sistema no debe asumir equivalencias. Hoy no existe una incidencia dedicada que detenga la entrada vendible.

Impacto: alto.

### ALM-REC-CAT-H007 - SKU fusionado/inactivo/descontinuado no tiene decision operativa clara

El SKU fusionado queda como no encontrado; otros estatus no generan advertencia suficiente.

Impacto: medio-alto.

### ALM-REC-CAT-H008 - Variante ambigua sin flujo de resolucion

No hay una pantalla/accion para seleccionar variante interna o distribuir cantidades antes de ingresar existencia.

Impacto: alto cuando el proveedor no identifica variante.

### ALM-REC-CAT-H009 - Precision insuficiente para algunos casos de granel

Recepcion usa `DECIMAL(12,4)` en detalle/lotes. Catalogo y proveedores usan factores `DECIMAL(18,6)`. Para empaques pequenos puede requerirse guardar 6 decimales.

Impacto: medio. Conviene estandarizar cantidades operativas nuevas en `DECIMAL(18,6)`.

## Flujo recomendado

### Preparar recepcion desde OC

1. Leer detalle de OC con `id_sku_erp` e `id_sku_proveedor`.
2. Enriquecer cada renglon con `erp_catalogo_sku_proveedores`:
   - unidad de compra
   - factor de conversion
   - estatus de relacion proveedor
3. Enriquecer con `erp_catalogo_skus`:
   - unidad base
   - estatus del SKU
   - reglas de inventario
   - producto padre/variantes cuando aplique
4. Guardar snapshot en recepcion para que una recepcion historica no cambie si Catalogo cambia despues.

### Validar antes de recibir

Bloquear entrada vendible o mandar a incidencia cuando:

- no hay `id_sku_erp`
- el SKU esta `fusionado`
- el SKU requiere variante y no esta resuelta
- falta unidad de compra o factor cuando el renglon viene de proveedor
- `factor_conversion <= 0`
- el factor parece sospechoso por unidad base distinta a unidad compra
- faltan lote/caducidad/serie/etiqueta cuando la regla del SKU lo exige

Advertir y requerir decision cuando:

- SKU inactivo/descontinuado con compra pendiente
- relacion proveedor inactiva
- unidad textual de OC no coincide con unidad proveedor configurada

### Captura del operador

La pantalla debe mostrar, por partida:

- SKU ERP y descripcion
- unidad de compra: ejemplo `caja`
- cantidad comprada: ejemplo `5 caja`
- factor: ejemplo `1 caja = 4.000 kg`
- cantidad base esperada: ejemplo `20.000 kg`
- cantidad fisica recibida en unidad compra
- cantidad base calculada, solo lectura
- lote/caducidad/ubicacion/serie/etiquetas segun regla

Para recibos parciales por lote:

- el operador captura cantidad en unidad compra
- el sistema calcula cantidad base por lote
- el kardex usa cantidad base
- el avance contra OC usa cantidad compra

### Guardado

Al guardar:

- recepcion detalle actualiza recibido/pendiente en unidad compra
- lotes guardan cantidad compra y cantidad base
- existencias guardan cantidad base
- movimientos de inventario guardan cantidad base
- costo unitario base = costo unitario compra / factor conversion
- movimiento y lote conservan folio comun de recepcion

## Propuesta de estructura

No aplicada. Requiere respaldo externo y autorizacion antes de ejecutar.

### `erp_almacen_recepciones_detalle`

Agregar campos snapshot:

```sql
ALTER TABLE erp_almacen_recepciones_detalle
  ADD COLUMN id_sku_proveedor BIGINT NULL AFTER id_sku_erp,
  ADD COLUMN id_unidad_compra INT NULL AFTER id_sku_proveedor,
  ADD COLUMN unidad_compra VARCHAR(40) NULL AFTER id_unidad_compra,
  ADD COLUMN id_unidad_base INT NULL AFTER unidad_compra,
  ADD COLUMN unidad_base VARCHAR(40) NULL AFTER id_unidad_base,
  ADD COLUMN factor_conversion DECIMAL(18,6) NOT NULL DEFAULT 1.000000 AFTER unidad_base,
  ADD COLUMN cantidad_ordenada_base DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER cantidad_ordenada,
  ADD COLUMN cantidad_recibida_base DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER cantidad_recibida,
  ADD COLUMN cantidad_pendiente_base DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER cantidad_pendiente,
  ADD COLUMN estatus_sku_recepcion VARCHAR(40) NULL AFTER estatus,
  ADD COLUMN requiere_clasificacion TINYINT(1) NOT NULL DEFAULT 0 AFTER estatus_sku_recepcion;
```

Nota: conservar `cantidad_ordenada`, `cantidad_recibida` y `cantidad_pendiente` como cantidad compra para no romper sincronizacion con Compras.

### `erp_almacen_recepciones_lotes`

Agregar campos snapshot por lote:

```sql
ALTER TABLE erp_almacen_recepciones_lotes
  ADD COLUMN cantidad_compra DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER ubicacion,
  ADD COLUMN unidad_compra VARCHAR(40) NULL AFTER cantidad_compra,
  ADD COLUMN cantidad_base DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER cantidad,
  ADD COLUMN unidad_base VARCHAR(40) NULL AFTER cantidad_base,
  ADD COLUMN factor_conversion DECIMAL(18,6) NOT NULL DEFAULT 1.000000 AFTER unidad_base,
  ADD COLUMN costo_unitario_base DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER costo_unitario;
```

Nota: durante la migracion, `cantidad` puede mantenerse como cantidad base para compatibilidad gradual. El nombre debe aclararse en codigo y documentos.

### Incidencias

Usar la tabla existente `erp_almacen_recepciones_incidencias` con nuevos tipos:

- `unidad_factor_pendiente`
- `factor_sospechoso`
- `sku_fusionado`
- `sku_inactivo`
- `sku_descontinuado`
- `variante_pendiente`
- `relacion_proveedor_inactiva`

## Impacto por caso operativo

### Compra con unidad proveedor configurada

Recepcion debe mostrar cantidad comprada y unidad de compra. Si existe factor, calcula cantidad base.

Ejemplo `TP-40372`:

- compra: `5 caja`
- factor: `1 caja = 4 kg`
- entrada inventario: `20 kg`

### Unidad/factor faltante

Recepcion no debe asumir. Debe crear incidencia `unidad_factor_pendiente` y bloquear existencia vendible hasta corregir Catalogo/Proveedor o autorizar una clasificacion controlada.

### Producto a granel/fraccionable

Recepcion entra existencia en unidad base. No crea bolsas teoricas. Si se recibe bolsa proveedor lista para vender, la OC debe traer el SKU presentacion.

### Variantes ambiguas

Si no se identifica variante:

- seleccionar variante interna antes de recibir, o
- distribuir cantidad entre variantes, o
- mandar a incidencia `variante_pendiente`

No debe entrar existencia vendible al SKU base incorrecto.

### SKU descontinuado/inactivo/fusionado

- `fusionado`: bloquear recepcion normal.
- `inactivo/descontinuado`: advertencia y decision operativa.
- existencia o compra pendiente: resolver antes de recibir como vendible.

## Orden recomendado de implementacion

1. Ajustar lectura de OC en Recepcion para usar `id_sku_proveedor` y enriquecer unidad/factor desde Catalogo/Proveedor.
2. Agregar validaciones no destructivas y alertas en respuesta de `consultar_recepcion()`.
3. Actualizar UI para mostrar unidad compra, factor y cantidad base calculada.
4. Preparar DDL con respaldo externo para snapshot de unidad/factor/cantidad base.
5. Ejecutar migracion autorizada.
6. Cambiar guardado para separar cantidad compra contra OC y cantidad base contra inventario.
7. Agregar incidencias de unidad/factor/SKU/variante.
8. Ejecutar UAT controlado con `REC-OC-20` y una nueva recepcion de prueba.

## UAT propuesto

### UAT-ALM-REC-CAT-001 - TP-40372 con unidad proveedor configurada

Datos:

- OC 20, detalle 62
- SKU `TP-40372`
- relacion proveedor `2310`
- compra `5 caja`
- factor `4 kg`

Esperado:

- Recepcion muestra `5 caja`
- muestra `1 caja = 4.000 kg`
- muestra entrada base `20.000 kg`
- movimiento de inventario queda en `20.000 kg`
- avance contra OC queda en `5 caja`

### UAT-ALM-REC-CAT-002 - Unidad/factor faltante

Preparar renglon de prueba con `id_sku_proveedor` sin unidad o factor `0`.

Esperado:

- alerta "unidad/factor de compra pendiente"
- no se genera existencia vendible
- se registra incidencia

### UAT-ALM-REC-CAT-003 - Variante pendiente

Preparar renglon donde proveedor no identifica variante.

Esperado:

- Recepcion pide seleccionar variante o distribuir cantidad
- si no se resuelve, incidencia `variante_pendiente`
- no hay entrada vendible al SKU incorrecto

## Cierre de auditoria

Recepcion tiene buena base para lotes, caducidad, serie, etiquetas, existencias y movimientos. La brecha principal no esta en Inventario ni en Ventas, sino en la union entre Compras/Catalogo/Proveedor y Recepcion:

- tomar `id_sku_proveedor`
- conservar snapshot unidad/factor
- separar cantidad compra de cantidad base
- bloquear conversiones inciertas
- resolver variantes antes de existencia vendible

No se aplicaron migraciones ni escrituras de esquema en esta auditoria.

## Avance aplicado sin migracion

Fecha: 2026-06-24

Se aplico una primera fase no destructiva:

- `consultar_recepcion_almacen_completa()` ahora enriquece el detalle con `id_sku_proveedor`, unidad de compra, factor de conversion, unidad base, estatus de SKU y cantidades base calculadas desde Catalogo/Proveedor.
- La UI de Recepcion muestra cantidad compra y cantidad base calculada.
- Si una partida requiere conversion real a unidad base y el guardado aun no tiene snapshot/kardex convertido, se marca como alerta bloqueante para evitar generar existencia vendible incorrecta.
- Se valido `REC-OC-20` / `TP-40372`: `5 caja`, factor `4.000000`, base calculada `20 kg`, bloqueada hasta aplicar el flujo persistente.

Pendiente con autorizacion:

- respaldo externo;
- migracion de snapshot unidad/factor/cantidad base;
- cambio de guardado para separar cantidad compra contra OC y cantidad base contra inventario;
- UAT con folio controlado despues de la migracion.

## Avance aplicado con migracion autorizada

Fecha: 2026-06-24

Respaldo previo:

- `storage/backups/artianilocal_pre_almacen_recepcion_conversion_20260624_211840.sql`

Cambios aplicados:

- `erp_almacen_recepciones_detalle` ahora conserva snapshot de:
  - `id_sku_proveedor`
  - unidad de compra
  - unidad base
  - factor de conversion
  - cantidad ordenada/recibida/pendiente en unidad base
  - costo unitario base
  - estatus del SKU al momento de recepcion
  - bandera de clasificacion pendiente
- `erp_almacen_recepciones_lotes` ahora conserva:
  - cantidad compra
  - cantidad base
  - unidad compra/base
  - factor de conversion
  - costo unitario base
- Recepcion guarda:
  - avance contra OC en cantidad compra;
  - lotes/existencias/movimientos en cantidad base;
  - costo base calculado como costo compra / factor conversion.

Validacion puntual:

- `REC-OC-20` / `TP-40372`
  - compra: `5 caja`
  - factor: `4.000000`
  - inventario base calculado: `20 kg`
  - costo compra: `737.0690`
  - costo base: `184.267250`

Pendiente:

- UAT con una recepcion nueva/controlada para verificar kardex real: salida esperada del guardado debe ser OC en cantidad compra e inventario/movimiento en cantidad base.

## UAT preparado siguiente

### UAT-ALM-REC-CAT-004 - Recepcion real con factor de compra

Estado al 2026-06-24:

- No hay recepciones pendientes con `factor_conversion <> 1`.
- No se creo una OC falsa ni una recepcion inventada para no ensuciar Compras/Almacen.

Cuando exista una OC enviada con un SKU proveedor configurado con factor distinto de 1, ejecutar:

1. Abrir la recepcion desde Almacen > Recepciones.
2. Confirmar que la partida muestra:
   - cantidad compra;
   - unidad compra;
   - factor;
   - cantidad base de inventario;
   - unidad base.
3. Recibir una cantidad parcial pequena y controlada.
4. Validar en BD:

```sql
SELECT rd.sku, rd.cantidad_recibida, rd.unidad_compra,
       rd.cantidad_recibida_base, rd.unidad_base, rd.factor_conversion
FROM erp_almacen_recepciones_detalle rd
WHERE rd.id_recepcion_detalle = :id_recepcion_detalle;

SELECT l.cantidad_compra, l.unidad_compra, l.cantidad,
       l.cantidad_base, l.unidad_base, l.factor_conversion
FROM erp_almacen_recepciones_lotes l
WHERE l.id_recepcion_detalle = :id_recepcion_detalle;

SELECT m.cantidad, m.costo_unitario, m.costo_total, m.origen_tipo
FROM erp_inventario_movimientos m
WHERE m.origen_detalle_id = :id_recepcion_detalle
ORDER BY m.id_movimiento_inventario DESC;
```

Esperado:

- `cantidad_recibida` y `cantidad_compra` quedan en unidad compra.
- `cantidad_recibida_base`, `cantidad_base`, `l.cantidad` y `m.cantidad` quedan en unidad base.
- `origen_tipo = recepcion_compra`.
- Compras no convierte inventario; solo recibe el avance en unidad compra.

### Resultado UAT-ALM-REC-CAT-004 - REC-OC-25

Fecha: 2026-06-24

Folio:

- Recepcion: `REC-OC-25`
- OC: `OC-2026-000025`
- SKU: `TP-40311`
- Producto: `Alimento churro mix para peces agranel`

Captura real:

- Cantidad compra recibida: `3.0000`
- Factor conversion: `4.000000`
- Cantidad base inventario: `12.000000 kg`
- Lotes capturados:
  - `L01`, caducidad `2026-12-31`, `1.000000` unidad compra -> `4.000000 kg`
  - `L01`, caducidad `2027-01-24`, `2.000000` unidades compra -> `8.000000 kg`

Evidencia BD:

- `erp_almacen_recepciones_detalle`
  - `cantidad_recibida = 3.0000`
  - `cantidad_recibida_base = 12.000000`
  - `cantidad_pendiente = 0.0000`
  - `cantidad_pendiente_base = 0.000000`
  - estatus detalle: `recibida`
- `erp_almacen_recepciones_lotes`
  - lote `28`: `cantidad_compra = 1.000000`, `cantidad = 4.0000`, `cantidad_base = 4.000000`
  - lote `29`: `cantidad_compra = 2.000000`, `cantidad = 8.0000`, `cantidad_base = 8.000000`
- `erp_inventario_movimientos`
  - movimiento `46`: entrada `4.0000`, costo `272.6293`, total `1090.5172`
  - movimiento `47`: entrada `8.0000`, costo `272.6293`, total `2181.0344`
  - total movimientos: `12.0000`, costo total `3271.5516`
- `erp_inventario_existencias`
  - existencia `32`: `4.0000 kg`, disponible, lote `L01`, caducidad `2026-12-31`
  - existencia `33`: `8.0000 kg`, disponible, lote `L01`, caducidad `2027-01-24`
- `erp_compras_ordenes_detalle`
  - `cantidad = 3.000000`
  - `cantidad_recibida = 3.000000`
- `erp_compras_ordenes`
  - estatus: `recibida`
- Incidencias recepcion: `0`
- Unidades/etiquetas generadas: `3`

Resultado:

- Aprobado. Compras avanzo con cantidad compra (`3`) e Inventario recibio cantidad base (`12 kg`) con kardex por lote.

## Auditoria de unidades fisicas cerradas - REC-OC-25

Fecha: 2026-06-25

Pregunta operativa:

- Si se reciben `3` unidades fisicas cerradas de proveedor, cada una con contenido de `4 kg`, como `TP-40311`, como se identifica despues una unidad completa para venderla o abrirla en Preparacion/Empaque?

Estado actual observado:

- `REC-OC-25` genero `3` registros en `erp_inventario_unidades`:
  - `INV-00003-28-0001`, ligado a existencia `32`, lote recepcion `28`
  - `INV-00003-29-0001`, ligado a existencia `33`, lote recepcion `29`
  - `INV-00003-29-0002`, ligado a existencia `33`, lote recepcion `29`
- Las existencias quedaron:
  - existencia `32`: `4 kg`
  - existencia `33`: `8 kg`
- Los lotes de recepcion guardan:
  - lote recepcion `28`: `cantidad_compra = 1`, `cantidad_base = 4 kg`
  - lote recepcion `29`: `cantidad_compra = 2`, `cantidad_base = 8 kg`

Conclusion:

- El sistema puede inferir que cada unidad fisica cerrada equivale a `4 kg` usando:
  - `cantidad_base / cantidad_compra`
- Pero `erp_inventario_unidades` no guarda aun, por cada etiqueta:
  - cantidad base original;
  - cantidad base disponible;
  - estado fisico de la unidad cerrada;
  - si fue abierta para preparacion;
  - si fue consumida parcialmente o totalmente.

Hallazgo:

### ALM-REC-CAT-H010 - Unidad fisica trazable sin contenido propio

Las etiquetas/unidades de inventario identifican la unidad fisica, lote, caducidad, existencia y origen, pero no guardan el contenido base por unidad ni su estado fisico operativo.

Impacto:

- Venta futura de unidad completa no puede descontar una unidad especifica de `4 kg` con trazabilidad perfecta sin una regla adicional.
- Preparacion/Empaque consume hoy una `existencia`, no una `unidad fisica` especifica.
- Si se abre una unidad cerrada para preparar bolsitas, el sistema puede descontar `4 kg` del lote, pero todavia no marca explicitamente cual etiqueta fue abierta/consumida.

Modelo robusto recomendado:

- Mantener `erp_inventario_existencias` como saldo contable por SKU/lote/caducidad/ubicacion.
- Usar `erp_inventario_unidades` como identidad fisica cerrada cuando hay etiquetas.
- Agregar, con migracion autorizada, campos o tabla complementaria para:
  - `cantidad_base_original`
  - `cantidad_base_disponible`
  - `unidad_base`
  - `estado_fisico`: `cerrada`, `abierta`, `consumida`, `vendida`, `cancelada`
  - referencia a preparacion/venta que consumio la unidad, cuando aplique.

Regla operativa sugerida:

- Venta de unidad cerrada:
  - seleccionar/leer etiqueta;
  - descontar `cantidad_base_disponible` de la existencia;
  - marcar unidad como `vendida`.
- Preparacion/Empaque desde unidad cerrada:
  - seleccionar etiqueta/unidad fisica origen;
  - descontar su contenido base;
  - marcar unidad como `abierta` o `consumida`;
  - generar unidades/etiquetas de presentaciones resultantes.
- Preparacion/Empaque desde saldo suelto:
  - permitir consumir existencia sin etiqueta fisica cuando no haya unidad cerrada identificable.

Pendiente:

- Disenar `ALM-UNIDAD-001`: trazabilidad de unidad fisica cerrada y consumo por etiqueta.
- No aplicar migracion hasta respaldar y autorizar.
