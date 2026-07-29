# Handoff - Catalogo ERP hacia Almacen/Apertura de empaques

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-28  
Estado: preparado desde Catalogo; pendiente DDL y ajuste en Almacen

## Contexto

Catalogo ERP separo formalmente `Presentaciones` de `Apertura de empaques`.

- `Presentaciones` queda para presentaciones comerciales/preparadas.
- `Apertura de empaques` queda para abrir un SKU cerrado y generar stock de un SKU granel/fraccionario.

No se debe reutilizar `erp_catalogo_sku_presentaciones` para apertura de empaques.

## Tabla contrato de Catalogo

Tabla nueva propuesta:

- `erp_catalogo_sku_aperturas_empaque`

Campos principales:

- `id_sku_origen`: SKU cerrado que se abre.
- `id_sku_destino`: SKU granel/fraccionario que recibe el contenido.
- `factor_conversion`: cantidad destino generada por una unidad cerrada origen.
- `requiere_unidad_fisica`: si Almacen debe seleccionar etiqueta/unidad cerrada real.
- `conserva_lote`: el destino hereda lote.
- `conserva_caducidad`: el destino hereda caducidad.
- `permite_merma`: Almacen puede capturar merma.
- `merma_porcentaje_default`: merma sugerida.
- `instrucciones_operativas`: nota para el operador.
- `estatus`: activo/inactivo.

DDL preparado en:

- `docs/erp_catalogo_apertura_empaques_ddl.sql`

## Hallazgo en Almacen actual

Almacen ya tiene pantalla/flujo `apertura_empaques`, pero la fuente actual de configuracion usa paquete/receta:

- `Almacenes::consultar_skus_apertura_empaque()` consulta `erp_catalogo_sku_paquetes` con `permite_desarmar=1`.
- `Almacenes::consultar_receta_apertura_empaque()` consulta componentes de paquete.
- `Almacenes::guardar_borrador_apertura_empaque()` recibe `id_paquete` y normaliza resultados por componentes.
- `Almacenes::normalizar_resultados_apertura_empaque()` valida que el resultado pertenezca a la receta de paquete.

Eso sirve para desarmar paquetes, pero no para el caso nuevo de abrir un empaque cerrado hacia granel.

## Cambio requerido en Almacen

Almacen/Apertura de empaques debe consultar `erp_catalogo_sku_aperturas_empaque`, no `Presentaciones` ni paquetes, cuando se trate de apertura cerrada -> granel.

Flujo esperado:

1. Usuario selecciona almacen con `permite_apertura_empaque=1`.
2. Usuario selecciona o escanea SKU cerrado origen.
3. Backend consulta reglas activas en `erp_catalogo_sku_aperturas_empaque` por `id_sku_origen`.
4. UI muestra SKU destino granel permitido, factor e instrucciones.
5. Usuario selecciona existencia/unidad fisica cerrada real si la regla lo exige.
6. Al confirmar:
   - baja SKU origen cerrado;
   - alta SKU destino granel;
   - usa `factor_conversion` como cantidad esperada destino;
   - permite capturar cantidad real y merma si aplica;
   - hereda lote/caducidad segun regla;
   - registra movimientos con `origen_tipo='apertura_empaque'`.

## Diferencia con paquetes

Paquetes/desarme:

- Origen: SKU paquete.
- Destino: varios componentes.
- Tabla fuente actual: `erp_catalogo_sku_paquetes` + componentes.
- Sigue siendo valido para paquetes configurables o kits.

Apertura cerrada -> granel:

- Origen: un SKU cerrado.
- Destino: un SKU granel/fraccionario.
- Tabla fuente nueva: `erp_catalogo_sku_aperturas_empaque`.
- No debe depender de `id_paquete`.

## Pendientes para el modulo Almacen

- Auditar si conviene mantener el flujo actual para paquetes y agregar un submodo `apertura_granel`.
- Cambiar endpoints o agregar nuevos endpoints para consultar reglas de `erp_catalogo_sku_aperturas_empaque`.
- Evitar que UI pida `id_paquete` cuando el origen sea apertura cerrada -> granel.
- Ajustar guardado de borrador para aceptar `id_apertura_catalogo` o campo equivalente.
- Ajustar resultados para una sola salida destino granel, no componentes multiples.
- Mantener validaciones de existencia, unidad fisica, lote/caducidad y merma.

## Criterio de prueba futura

Caso ejemplo:

- SKU origen cerrado: costal/bolsa/caja cerrada.
- SKU destino granel: mismo producto en KG/L/M con `Venta fraccionaria` activa.
- Factor: cantidad destino generada por una unidad cerrada.

Prueba:

1. Crear regla en Catalogo.
2. Recibir existencia del SKU cerrado.
3. En Almacen/Apertura, abrir una unidad cerrada.
4. Confirmar baja del SKU cerrado y alta del SKU granel.
5. Validar kardex `apertura_empaque`.
6. Validar que POS pueda vender el SKU granel solo desde stock abierto.

## Estado

- Catalogo ya tiene UI/backend preparado de forma tolerante.
- DDL de Catalogo aplicado el 2026-07-28; tabla `erp_catalogo_sku_aperturas_empaque` existe y queda vacia hasta crear reglas desde UI.
- Almacen no modificado en esta tarea.
- No se tocaron Inventario, POS, Ecommerce ni Listas de precios.
## Estado post-DDL Catalogo

Fecha: 2026-07-28

- Tabla Catalogo `erp_catalogo_sku_aperturas_empaque` creada.
- Registros actuales: `0`.
- Primer candidato recomendado para prueba, pendiente de confirmacion:
  - SKU origen: `1205:NUEC-C20K`.
  - SKU destino: `1786:NUEC-C20K-GRANEL`.
  - Factor sugerido por nombre: `20.000000`.
- Almacen no debe asumir ese factor hasta que la regla exista en Catalogo.
