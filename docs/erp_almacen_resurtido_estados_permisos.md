# ERP Almacen - Resurtido estados, transiciones y permisos

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-13  
Modulo: ERP > Almacen > Resurtido / Traspasos entre tiendas  
Estado: diseno operativo; sin cambios de BD ni permisos reales.

## Objetivo

Definir el contrato minimo para que el resurtido entre tiendas avance por estados auditables, conserve trazabilidad y evite saltos operativos.

Este documento no crea permisos, no ejecuta migraciones y no habilita acciones reales.

## Estados de encabezado

| Estado | Significado | Inventario afectado |
| --- | --- | --- |
| `borrador` | Captura interna aun editable. | No |
| `solicitado` | Tienda ya pidio resurtido. | No |
| `autorizado` | Central aprobo total o parcial. | No |
| `rechazado` | Central rechazo la solicitud completa. | No |
| `preparando` | Bodega esta seleccionando existencias/unidades. | No, salvo reserva futura documentada |
| `preparado` | Bodega ya fijo lote/caducidad/unidad/cantidad. | No |
| `enviado` | Salio de origen y entro a transito documental o almacen tecnico. | Si |
| `recibido_parcial` | Tienda recibio parte y hay pendiente/diferencia. | Si |
| `recibido` | Tienda recibio completo sin pendientes operativos. | Si |
| `cerrado` | Folio conciliado, diferencias resueltas o aceptadas. | No adicional |
| `cancelado` | Folio cancelado antes de afectar o con reversa autorizada. | Segun etapa |

## Transiciones permitidas

| De | A | Accion | Regla |
| --- | --- | --- | --- |
| `borrador` | `solicitado` | solicitar | Requiere tienda, origen y detalle valido. |
| `borrador` | `cancelado` | cancelar | Permitido sin movimiento. |
| `solicitado` | `autorizado` | autorizar | Puede ser parcial; comentario obligatorio si reduce cantidad. |
| `solicitado` | `rechazado` | rechazar | Motivo obligatorio. |
| `solicitado` | `cancelado` | cancelar | Permitido si no hay preparacion. |
| `autorizado` | `preparando` | iniciar preparacion | Requiere al menos una linea autorizada mayor a cero. |
| `preparando` | `preparado` | confirmar preparacion | Requiere lote/caducidad/ubicacion/unidad cuando aplique. |
| `preparado` | `enviado` | enviar | Genera salida de origen y entrada a transito. |
| `enviado` | `recibido_parcial` | recibir con diferencia | Registra diferencias abiertas. |
| `enviado` | `recibido` | recibir completo | Genera entrada a tienda conservando trazabilidad. |
| `recibido_parcial` | `cerrado` | cerrar con diferencias | Requiere diferencias resueltas o aceptadas. |
| `recibido` | `cerrado` | cerrar | Cierre administrativo. |

Transiciones no permitidas:

- `solicitado` a `enviado`.
- `autorizado` a `enviado`.
- `preparado` a `recibido` sin pasar por `enviado`.
- `recibido` a `preparando`.
- `cerrado` a cualquier estado operativo.

## Estados de detalle

| Estado | Uso |
| --- | --- |
| `pendiente` | Linea capturada sin autorizacion. |
| `autorizada` | Cantidad autorizada mayor a cero. |
| `rechazada` | Linea no surtida. |
| `preparada` | Existencia/unidad/lote fijado. |
| `enviada` | Linea incluida en salida/transito. |
| `recibida` | Linea recibida completa. |
| `recibida_parcial` | Linea con faltante o diferencia. |
| `cancelada` | Linea retirada antes de surtir. |

## Permisos actuales y candidatos

Permisos actuales usados por el modulo:

| Permiso | Uso actual |
| --- | --- |
| `almacen.ver` | Pantallas y consultas read-only. |
| `almacen.recibir` | Acciones operativas de almacen existentes. |
| `almacen.ubicaciones` | Configuracion de almacenes/ubicaciones. |
| `sistema.soporte` | Auditoria/actualizacion de esquema. |

Permisos candidatos para una fase posterior:

| Permiso candidato | Acciones |
| --- | --- |
| `almacen.resurtido.solicitar` | Crear borrador y enviar solicitud. |
| `almacen.resurtido.autorizar` | Autorizar, rechazar, ajustar cantidades. |
| `almacen.resurtido.preparar` | Seleccionar existencias, lotes, unidades y confirmar preparacion. |
| `almacen.resurtido.enviar` | Generar salida/transito. |
| `almacen.resurtido.recibir` | Confirmar recepcion en tienda y diferencias. |
| `almacen.resurtido.cerrar` | Cerrar folio y diferencias aceptadas. |
| `almacen.resurtido.configurar` | Politicas min/max/reorden por tienda/SKU. |

Decision provisional:

- No crear permisos nuevos todavia.
- Mantener read-only con `almacen.ver`.
- Mantener guardado futuro protegido temporalmente por `almacen.recibir` hasta que Seguridad autorice permisos finos.

## Reglas de trazabilidad

- La solicitud no mueve inventario.
- La autorizacion no mueve inventario.
- La preparacion fija origen fisico: almacen, ubicacion, lote, caducidad y unidad si aplica.
- El envio es el primer punto que afecta inventario.
- La recepcion debe comparar enviado contra recibido.
- Las diferencias deben quedar persistentes por folio, SKU, lote, caducidad y unidad fisica cuando exista.
- Las unidades abiertas viajan con snapshot de contenido antes/despues.
- El stock en transito no debe ser vendible por POS ni ecommerce en el handoff futuro.

## Contrato preparacion/envio

Preparacion:

- Requiere encabezado `autorizado`.
- Requiere detalle `autorizada` con cantidad mayor a cero.
- Escribe futuro en `erp_almacen_resurtido_preparacion`.
- Fija `id_existencia_origen`, `id_almacen_origen`, `ubicacion_origen_id`, `lote`, `fecha_caducidad` y `cantidad_preparada`.
- Si hay unidad fisica, fija `id_inventario_unidad`, `cantidad_unidad_antes`, `cantidad_unidad_despues` y `estado_fisico_unidad`.
- No afecta inventario todavia.

Envio:

- Requiere encabezado `preparado`.
- Requiere preparaciones `preparada` no enviadas.
- Genera salida del origen.
- Genera entrada a `TRANSITO` cuando exista almacen tecnico de transito.
- Registra `erp_almacen_resurtido_envios`.
- Conserva SKU, lote, caducidad, unidad fisica y referencia `RES-*`.
- Es el primer punto que afecta inventario.

Decision recomendada:

- Usar almacen tecnico `TRANSITO` real para que el stock enviado no sea disponible ni en origen ni en tienda hasta confirmar recepcion.

## Contrato recepcion/diferencias

Recepcion:

- Requiere encabezado `enviado`.
- Requiere envios `enviado` no recibidos.
- Compara cantidad enviada contra cantidad recibida.
- Compara lote esperado contra lote recibido.
- Compara caducidad esperada contra caducidad recibida.
- Si hay unidad fisica, compara unidad enviada contra unidad recibida.
- Genera salida de `TRANSITO` y entrada a tienda cuando se usa almacen tecnico de transito.
- Escribe futuro en `erp_almacen_resurtido_recepciones`.
- Puede dejar el folio en `recibido` o `recibido_parcial`.

Diferencias minimas:

- `faltante`: llego menos de lo enviado.
- `sobrante`: llego mas de lo documentado.
- `danado`: llego fisicamente danado.
- `lote_distinto`: el lote recibido no coincide.
- `caducidad_distinta`: la caducidad recibida no coincide.
- `unidad_no_recibida`: una unidad fisica enviada no llego.
- `unidad_no_enviada`: una unidad fisica no esperada aparece en recepcion.

Regla de cierre:

- `recibido`: solo cuando todo coincide y no hay diferencias abiertas.
- `recibido_parcial`: cuando hay faltantes, diferencias o pendientes.
- `cerrado`: solo cuando las diferencias esten resueltas o aceptadas con responsable y observacion.

## Contrato politicas/alertas

Politica tienda/SKU:

- Tabla futura: `erp_inventario_politicas_almacen_sku`.
- Clave unica: `id_almacen + id_sku_erp`.
- Campos minimos: `stock_minimo`, `punto_reorden`, `prioridad`, `estatus`.
- Campos opcionales: `stock_maximo`, `cantidad_sugerida`, `dias_cobertura_objetivo`, `observaciones`.
- Si no existe politica local, el preflight actual usa reglas globales de Catalogo como fallback.

Formula:

- `umbral_usado = punto_reorden > 0 ? punto_reorden : stock_minimo`.
- `requiere_resurtido = cantidad_disponible <= umbral_usado`.
- Si hay `stock_maximo`: `cantidad_sugerida = max(stock_maximo - cantidad_disponible, 0)`.
- Si no hay `stock_maximo`: `cantidad_sugerida = max(umbral_usado - cantidad_disponible, 0)`.

Alertas futuras:

- `stock_bajo_tienda`: disponible menor o igual a punto de reorden.
- `stock_critico_tienda`: disponible menor o igual a minimo.
- `origen_insuficiente`: bodega no puede surtir la cantidad sugerida.
- `politica_faltante`: SKU operativo sin politica local.

Regla:

- Las alertas deben ser persistentes y respetar permisos.
- No usar auditoria como bandeja de trabajo.
- No crear alertas reales hasta autorizar tabla/fuente persistente correspondiente.

## UAT por transicion

| ID | Folio/SKU | Caso | Resultado esperado |
| --- | --- | --- | --- |
| `RES-UAT-EST-001` | `RES-*` + SKU bajo | `borrador` a `solicitado` | Folio solicitado sin movimiento de inventario. |
| `RES-UAT-EST-002` | `RES-*` + SKU parcial | `solicitado` a `autorizado` | Solicitado/autorizado conservados; comentario si parcial. |
| `RES-UAT-EST-003` | `RES-*` + SKU rechazado | `solicitado` a `rechazado` | Motivo obligatorio y sin preparacion. |
| `RES-UAT-EST-004` | `RES-*` + lote/caducidad | `autorizado` a `preparado` | Preparacion con origen fisico exacto. |
| `RES-UAT-EST-005` | `RES-*` + unidad cerrada | `preparado` a `enviado` | Salida origen, entrada transito, unidad conservada. |
| `RES-UAT-EST-006` | `RES-*` + unidad abierta | `preparado` a `enviado` | Snapshot de contenido y estado fisico. |
| `RES-UAT-EST-007` | `RES-*` + faltante | `enviado` a `recibido_parcial` | Diferencia abierta por cantidad faltante. |
| `RES-UAT-EST-008` | `RES-*` + lote distinto | `enviado` a `recibido_parcial` | Diferencia por lote/caducidad distinta. |
| `RES-UAT-EST-009` | `RES-*` completo | `enviado` a `recibido` | Entrada tienda con lote/unidad conservados. |
| `RES-UAT-EST-010` | `RES-*` con diferencias | `recibido_parcial` a `cerrado` | Cierre solo con diferencias resueltas o aceptadas. |

## Pendientes antes de implementar acciones reales

1. Confirmar si `TRANSITO` sera almacen tecnico real o solo documento.
2. Autorizar DDL con respaldo externo.
3. Crear permisos finos o confirmar uso temporal de permisos existentes.
4. Implementar servicios por transicion con auditoria explicita.
5. Ejecutar UAT por folio/SKU despues de tener esquema.
