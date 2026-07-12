# ERP Almacen - Resurtido y traspasos entre tiendas

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Modulo: ERP > Almacen > Resurtido / Traspasos entre tiendas  
Estado: auditoria inicial y diseno de arranque; sin escrituras de BD.

## Objetivo

Construir un flujo robusto para que tiendas como Acuario y Mascotas tengan stock propio y puedan solicitar resurtido desde bodega central sin mezclar ERP nuevo con legacy, POS o ecommerce.

El modulo debe permitir:

- stock por tienda/almacen;
- minimos, maximos y punto de reorden por tienda/SKU;
- solicitud de resurtido por tienda;
- autorizacion por almacen central o responsable;
- preparacion fisica del surtido;
- salida de origen;
- transito controlado;
- confirmacion de recepcion en tienda;
- registro de diferencias por faltante, danado, lote distinto, caducidad distinta o unidad no recibida;
- conservacion de lote, caducidad, ubicacion, unidad fisica y trazabilidad.

## Alcance de esta fase

Incluye:

- Almacen/Inventario ERP nuevo.
- Politicas de resurtido por almacen/SKU.
- Flujo documental de solicitud, preparacion, envio y recepcion.
- UAT por folio, SKU, almacen origen y tienda destino.

Excluye por ahora:

- Ventas/POS.
- Ecommerce.
- Sincronizacion de stock por canal.
- Reorden automatico con compras/proveedores.
- Escrituras de BD sin respaldo externo y autorizacion.

## Reglas de trabajo

- Tareas pequenas.
- UAT documentado por folio/SKU.
- Hallazgos con ID `RES-H###`.
- Tareas con ID `RES-T###`.
- UAT con ID `RES-UAT-###`.
- No ejecutar migraciones ni SQL sin respaldo externo y autorizacion explicita.
- No mezclar con endpoints legacy.
- No tocar Ventas/POS/ecommerce; solo documentar handoff futuro.
- Antes de cualquier escritura de BD: respaldo externo, evidencia antes/despues y autorizacion.

## Auditoria inicial

### 1. Flujo actual de traspasos

Existe un traspaso ERP directo en:

- `app/controladores/Inventario.php`
- `app/modelos/InventarioErp.php`
- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`

Ruta principal:

- `/inventario/transpaso`
- `/inventario/traspasar_erp`

El controlador llama:

```text
InventarioErp::aplicarTraspaso()
```

Comportamiento actual:

- valida almacen origen y destino;
- genera referencia `TRA-*`;
- toma existencias disponibles del origen;
- descuenta del origen;
- crea o actualiza existencia en destino;
- inserta movimiento de salida y movimiento de entrada;
- todo ocurre en la misma transaccion.

Conclusion:

- Sirve para traspaso simple inmediato.
- No sirve como resurtido multi-tienda robusto porque no tiene solicitud, autorizacion, preparacion, envio, transito, recepcion ni diferencias.

### 2. Solicitudes de resurtido

No se encontraron tablas ni modelo especifico para solicitudes de resurtido entre tiendas.

`AlmacenEsquema::tablasAlmacenInventario()` cubre:

- almacenes;
- recepciones de compra;
- existencias;
- movimientos;
- unidades fisicas;
- ubicaciones;
- preparacion/empaque;
- transformaciones.

No cubre:

- encabezado de resurtido;
- detalle solicitado/autorizado/preparado/enviado/recibido;
- paquetes/envios;
- recepcion de traspaso;
- diferencias de recepcion;
- politicas por tienda/SKU.

### 3. Almacenes, tiendas y ubicaciones

La tabla oficial para lugares con inventario es:

```text
erp_almacenes
```

La tabla oficial para ubicaciones internas es:

```text
erp_almacen_ubicaciones
```

Tipos soportados en codigo:

- `punto_venta`
- `sucursal`
- `bodega`
- `principal`
- `transito`
- `devoluciones`
- `merma`
- `cuarentena`

Documento relacionado:

- `docs/erp_almacen_sucursales_almacenes_arranque.md`

Decision operativa ya documentada:

- Acuario debe ser almacen/punto de venta propio.
- Mascotas debe ser almacen/punto de venta propio.
- Bodega trasera debe ser almacen tipo `bodega`.
- Transito debe existir como almacen tecnico o como estado documental equivalente.
- `erp_sucursales` queda fuera como fuente de inventario por ahora.

### 4. Estados de traspaso actuales

El traspaso directo actual no tiene estados documentales.

Estados requeridos para modulo robusto:

- `solicitado`
- `autorizado`
- `rechazado`
- `preparando`
- `preparado`
- `enviado`
- `recibido_parcial`
- `recibido`
- `cerrado`
- `cancelado`

Regla recomendada:

- Un traspaso directo no debe reemplazar al flujo de resurtido.
- El traspaso directo puede conservarse como herramienta de inventario controlada para ajustes logisticos simples.
- El resurtido debe tener documento propio y estados.

### 5. Lote, caducidad y unidad fisica

El traspaso actual conserva lote y caducidad a nivel de existencia agregada:

- lee existencia origen;
- copia `lote`;
- copia `fecha_caducidad`;
- usa ubicacion destino indicada por el operador.

Limitacion critica:

- No mueve ni actualiza explicitamente `erp_inventario_unidades`.
- No asigna unidad fisica a transito.
- No conserva estado de etiqueta/unidad como parte del documento de traspaso.

Impacto:

- Para stock agregado puede funcionar.
- Para unidades cerradas, abiertas, etiquetas internas y trazabilidad fina no alcanza.

### 6. Unidad abierta y granel

Inventario ya soporta:

- existencia agregada en unidad base;
- unidad fisica cerrada;
- unidad fisica abierta;
- unidad consumida/agotada;
- contenido base original/disponible;
- diagnostico saldo contable vs unidades trazables.

Pero el traspaso actual:

- mueve cantidad agregada;
- no mueve una unidad fisica especifica;
- no conserva snapshots de contenido antes/despues por unidad;
- no distingue unidad abierta como stock disponible no cerrado vendible.

Regla para resurtido:

- Si la existencia origen tiene unidades fisicas disponibles, el flujo debe permitir seleccionar unidad exacta o paquete preparado exacto.
- Si se envia una unidad abierta, debe viajar como unidad abierta con contenido disponible.
- Si se envia granel agregado no trazable, debe quedar marcado como saldo agregado sin unidad fisica.

### 7. Alertas de stock bajo por tienda/SKU

Catalogo ERP ya tiene politica global por SKU en:

```text
erp_catalogo_sku_reglas_inventario
```

Campos relevantes:

- `stock_minimo`
- `stock_maximo`
- `punto_reorden`

Limitacion:

- No son por tienda.
- No consideran demanda o objetivo distinto entre Acuario, Mascotas y Bodega.
- No generan pendientes persistentes por responsable.

Recomendacion:

- Crear politica local por `id_almacen + id_sku`.
- Usar la politica global de Catalogo como default opcional.
- Generar alertas/pendientes persistentes desde Notificaciones cuando el stock disponible local sea menor o igual al punto de reorden local.

### 8. UAT minimo propuesto

| ID | Objetivo | Evidencia esperada |
| --- | --- | --- |
| `RES-UAT-001` | Tienda solicita resurtido por SKU bajo reorden | Folio `RES-*`, tienda, SKU, cantidad solicitada |
| `RES-UAT-002` | Central autoriza cantidad distinta | solicitado vs autorizado conservado |
| `RES-UAT-003` | Central prepara desde lote/caducidad especifica | existencia origen, lote, caducidad y ubicacion |
| `RES-UAT-004` | Envio pasa a transito | stock sale de bodega y no entra disponible a tienda |
| `RES-UAT-005` | Tienda recibe completo | entrada a tienda con mismo lote/caducidad/unidad |
| `RES-UAT-006` | Tienda recibe menos | diferencia persistente por faltante |
| `RES-UAT-007` | Traspaso de unidad fisica cerrada | etiqueta/unidad cambia de almacen conservando identidad |
| `RES-UAT-008` | Traspaso de unidad abierta/granel | contenido disponible viaja y se conserva |
| `RES-UAT-009` | Stock bajo por tienda/SKU | pendiente de resurtido generado |
| `RES-UAT-010` | Handoff POS/ecommerce | sin afectacion a ventas; regla documentada |

## Hallazgos

| ID | Severidad | Hallazgo | Recomendacion |
| --- | --- | --- | --- |
| `RES-H001` | Alta | El traspaso actual es inmediato y no documental. | Crear flujo de resurtido con estados y folio propio. |
| `RES-H002` | Alta | No existen tablas/modelo de solicitudes de resurtido. | Proponer DDL para encabezado, detalle, preparacion, envio, recepcion y diferencias. |
| `RES-H003` | Media | Almacenes/tiendas estan encaminados pero falta confirmar estructura operativa actual. | Validar Acuario, Mascotas, Bodega y Transito antes de UAT real. |
| `RES-H004` | Media | Lote/caducidad se conserva solo en existencia agregada. | Mantener lote/caducidad por linea de resurtido y recepcion. |
| `RES-H005` | Alta | El traspaso actual no mueve unidades fisicas/etiquetas. | Agregar seleccion y movimiento de `erp_inventario_unidades` en flujo nuevo. |
| `RES-H006` | Alta | Unidad abierta y granel no tienen soporte de transito/recepcion en traspaso. | Guardar snapshots de contenido base y estado fisico por linea/unidad. |
| `RES-H007` | Media | Reorden existe solo como politica global de SKU. | Crear politica por almacen/SKU con default desde Catalogo. |
| `RES-H008` | Media | No hay alertas persistentes de stock bajo por tienda. | Integrar con Notificaciones despues de tener politica local. |
| `RES-H009` | Media | El preflight read-only puede detectar stock bajo con politica global, pero todavia no con politica local por tienda/SKU. | Usarlo como arranque tecnico y reemplazar/fusionar con `erp_inventario_politicas_almacen_sku` cuando se autorice DDL. |

## Decision recomendada

Usar solicitud de resurtido + traspaso documental con transito, no solo traspaso directo.

Razon:

- El traspaso directo solo responde "mueve de A a B".
- El resurtido real responde "quien pidio, quien autorizo, que se preparo, que salio, que viajo, que llego y que diferencia hubo".
- Para tiendas con stock propio, unidades fisicas, lote/caducidad y granel, la evidencia documental importa tanto como el movimiento contable.

Impacto de elegir solo traspaso directo:

- No habria aprobacion.
- No habria transito real.
- No se detectarian faltantes al recibir.
- No habria diferencia entre solicitado, surtido y recibido.
- Se perderia trazabilidad fina de unidad fisica en escenarios con etiquetas.

## Modelo propuesto de alto nivel

Documentos:

- Politica local de resurtido por tienda/SKU.
- Solicitud de resurtido.
- Preparacion/surtido en bodega.
- Envio/transito.
- Recepcion de tienda.
- Diferencias.

Movimientos de inventario recomendados:

1. Preparacion autorizada:
   - no mueve stock todavia o reserva/aparta si se implementa reserva logistica.
2. Envio:
   - salida de origen;
   - entrada a almacen tecnico `TRANSITO` o estado documental de transito.
3. Recepcion:
   - salida de transito;
   - entrada a tienda.
4. Diferencias:
   - faltante: queda pendiente de investigacion;
   - danado: puede ir a `cuarentena`, `devoluciones` o `merma`;
   - lote distinto: requiere recepcion con incidencia.

## Handoff futuro a POS/ecommerce

POS futuro:

- Debe vender solo desde el almacen/tienda asignado al cajero/caja/turno.
- Puede consumir stock recibido por resurtido cuando el documento este recibido/cerrado.
- Unidad abierta solo puede venderse a granel si el SKU y politica POS lo permiten.

Ecommerce futuro:

- No debe tomar unidades abiertas como unidades cerradas vendibles.
- Debe leer disponibilidad por canal/almacen segun politica futura.
- No debe consumir stock en transito como disponible.

## Primeras tareas recomendadas

1. `RES-T001`: cerrar documentacion de auditoria y arranque. Estado: hecho en este documento.
2. `RES-T002`: preparar DDL propuesto sin ejecutar.
3. `RES-T003`: definir contrato de estados y permisos.
4. `RES-T004`: disenar metodos read-only/preflight para stock bajo por tienda/SKU.
5. `RES-T005`: disenar pantallas de listado y captura operativa.
6. `RES-T006`: agregar auditoria de esquema en `AlmacenEsquema` solo en modo plan.
7. `RES-T007`: con autorizacion posterior, respaldo externo y migracion.
