# ERP Compras y Solicitudes - Plan vivo del modulo

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-07  
Proyecto: ERP propio, modulo Compras  
Estado del documento: Vivo, se debe actualizar conforme avance el modulo

## Proposito

Este documento existe para que el modulo de Compras no se construya "a memoria" ni por impulsos. Debe servir como mapa de trabajo, checklist funcional, bitacora tecnica y guia para futuras IAs o para mantenimiento manual.

El objetivo del modulo es administrar el ciclo completo de compra:

Tablero vivo consolidado:

- `docs/erp_compras_tareas_vivas.md` (sigue el estado real por bloque y decide el siguiente paso).

1. Solicitud de compra.
2. Orden de compra.
3. Carga de XML y conciliacion.
4. Adjuntos.
5. Pagos, notas y saldos.
6. Envio a almacen.
7. Recepcion de almacen.
8. Actualizacion posterior de costos, inventario, pendientes y reportes.

La estrategia actual es construir todo en ERP, sin depender de tablas `ecom_*`. Ecommerce debe alimentarse despues desde ERP, no al reves.

## Contexto operativo actual de base de datos

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-26  
Modulo: Compras / Solicitudes / Ordenes

- El proyecto `panel_de_control` esta trabajando actualmente contra base de datos productiva configurada por el entorno.
- No asumir que `artianilocal` es una base de pruebas para Compras; si se menciona en respaldos antiguos o dumps, tratarlo como referencia historica salvo indicacion explicita del dueno.
- Cualquier escritura en Compras, Proveedores, Catalogo, Almacen, pagos, adjuntos, XML, costos, permisos o esquemas puede afectar operacion real.
- Antes de DDL, scripts de esquema, truncados, limpiezas, recalculos masivos o actualizaciones correctivas, solicitar autorizacion explicita y generar respaldo externo en `C:\xampp\panel_db_backups`.
- Las pruebas UAT del modulo deben preferir operaciones funcionales controladas desde la UI o endpoints ya existentes; no hacer escrituras directas en base salvo autorizacion.

## Principios del modulo

- Todo endpoint debe validar sesion.
- Todo endpoint sensible debe validar permiso puntual.
- Todo POST sensible debe quedar auditado.
- Crear, editar, ver, cancelar, adjuntar, pagar y aprobar deben ser acciones separadas.
- La vista debe ser intuitiva y reducir pasos innecesarios.
- El usuario no debe perder captura si todavia no ha guardado.
- No se debe afectar inventario al crear o editar una orden; inventario se afecta en recepcion de almacen.
- Los pagos y notas no deben borrarse fisicamente; deben cancelarse logicamente.
- Los adjuntos cancelados conservan historial, pero el archivo fisico se elimina para no consumir almacenamiento.
- XML debe acelerar captura, pero no destruir informacion importante de solicitud.
- Un mismo XML/CFDI puede reutilizarse como fuente de captura para varias ordenes cuando el proveedor emite una factura con varias marcas/listas; no debe duplicarse como documento fiscal por UUID/hash.
- Los productos no encontrados, no surtidos o no relacionados deben generar pendientes accionables.
- El modulo debe poder consultarse en modo solo lectura sin riesgo de editar.
- Las ordenes en borrador pueden capturar productos propuestos; al enviar, todo producto fisico inventariable debe tener SKU ERP. Solo cargos/servicios no inventariables pueden avanzar sin SKU ERP.

## Decision operativa: lista del proveedor como origen de captura

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-28  
Proposito: ajustar Solicitudes y Ordenes para capturar compras desde el lenguaje del proveedor sin perder control interno de catalogo.  
Impacto: Solicitudes, Ordenes, XML, autorizacion de costos, pendientes de Catalogo/Proveedores y futura recepcion de Almacen.

Decision:

- Solicitudes y Ordenes deben buscar y mostrar productos desde la lista del proveedor como fuente principal de captura.
- El capturista debe ver primero `sku_proveedor`, `nombre_proveedor`, `unidad_compra` y costo del proveedor.
- El SKU ERP del catalogo es una relacion interna obligatoria para productos fisicos inventariables, no el texto principal para pedir al proveedor.
- No se deben permitir productos fisicos sin SKU ERP relacionado en solicitudes u ordenes. Si un producto del proveedor no esta relacionado, debe generar pendiente para Catalogo/Proveedores, pero no debe agregarse a la solicitud como partida comprable.
- El costo mostrado puede ser el costo de lista del proveedor si aun no existe historial de compra.
- Conforme se registren compras reales, el costo debe actualizarse o proponer actualizacion desde los documentos de compra, respetando auditoria y autorizacion.
- No es necesario que un `sku_proveedor` apunte a varios `sku_erp` en el flujo actual. Si el proveedor usa el mismo SKU para varios colores o variantes, la lista del proveedor debe conservar su SKU y descripcion reales, pero el ERP solo debe permitir seleccionar las relaciones que ya esten definidas de forma operativa.
- Para evitar confusion, el buscador debe diferenciar coincidencias exactas y parciales, y debe mostrar claramente el producto de proveedor y el SKU ERP relacionado.

Regla para autorizacion:

- La autorizacion de una solicitud debe calcularse con el costo vigente disponible de la relacion proveedor-producto.
- Si no hay costo de compra historico, se usa el costo de lista como costo estimado inicial.
- Si no hay costo valido, la partida no debe avanzar a autorizacion sin correccion de la lista o relacion.

Regla para pendientes:

- Producto de proveedor sin relacion SKU ERP: pendiente para Catalogo/Proveedores.
- Producto de proveedor con relacion pero sin costo valido: pendiente de costo/lista proveedor.
- Producto solicitado anteriormente pero no seleccionado en nueva captura/XML: pendiente de seguimiento para futura solicitud, no eliminacion silenciosa.

## Decision operativa: compras para mercancia, insumos y materia prima

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-13  
Proposito: preparar Compras para abastecer venta directa y futura Fabricacion sin crear catalogos paralelos.  
Impacto: Solicitudes, Ordenes, Catalogo ERP, Proveedores, Almacen, Inventario y futuro modulo de Fabricacion.

Decision:

- Solicitudes y Ordenes de Compra deben servir para comprar cualquier SKU abastecible: mercancia revendible, insumo operativo, materia prima, material de empaque, refaccion o servicio/cargo no inventariable.
- No se debe crear un modulo separado de "compras de fabricacion" para comprar materia prima. El proceso de compra es el mismo: proveedor, lista, costo, solicitud, orden, XML, adjuntos, pagos, recepcion si aplica.
- La diferencia no pertenece a Compras como tabla paralela; pertenece al Catalogo ERP como clasificacion operativa del SKU.
- Catalogo debe poder clasificar cada SKU por tipo operativo recomendado:
  - `mercancia`: producto comprado para venderse directamente.
  - `materia_prima`: componente que se consume en fabricacion o preparacion.
  - `insumo`: material usado por la operacion, puede o no formar parte del producto final.
  - `empaque`: material de empaque o presentacion.
  - `refaccion`: pieza para mantenimiento o reparacion interna.
  - `servicio`: servicio comprado, no inventariable.
  - `cargo`: gasto/cargo de compra como envio, maniobra u otros conceptos no inventariables.
- Compras debe filtrar/mostrar estos tipos segun el contexto de la solicitud:
  - compra general: mercancia, materia prima, insumo, empaque, refaccion;
  - compra para fabricacion: materia prima, insumo, empaque y refaccion;
  - compra para venta directa: mercancia y, si se autoriza, empaque asociado;
  - cargos/servicios: permitidos en orden para cuadrar documentos, no en solicitud inventariable salvo regla explicita.
- Almacen recibe fisicamente lo inventariable y conserva lote, caducidad, ubicacion y cantidad; no recibe cargos/servicios como inventario.
- Fabricacion consumira materia prima/insumos desde Inventario por orden de produccion o receta/BOM, no directamente desde Compras.

## Decision operativa: variantes, unidades, lotes y caducidad

Documentacion IA: Codex GPT-5
Fecha: 2026-10-14
Proposito: asegurar que Compras identifique la variante correcta y que Almacen reciba con trazabilidad fisica completa.
Impacto: Compras/Catalogo/Proveedores/Almacen/Inventario.

- El producto maestro no es suficiente para operar inventario. La unidad operativa es `id_sku_erp`, que identifica la variante vendible o comprable, por ejemplo color, medida, presentacion o modelo.
- La relacion proveedor conserva `id_sku_proveedor`, el SKU y la descripcion reales del proveedor. La partida de Compras debe guardar ambos identificadores y sus snapshots de texto.
- Compras no debe inferir una variante solamente por nombre. Si el mismo codigo del proveedor representa varios colores o variantes, el dato no es determinista: debe existir una seleccion explicita de `id_sku_erp`, una descripcion/atributo verificable o una incidencia de relacion ambigua.
- La orden puede comprar en unidad de proveedor, pero Almacen debe convertir a unidad base mediante la unidad de compra y `factor_conversion`. No se deben sumar cajas como si fueran piezas.
- Lote, caducidad, ubicacion, serie o codigo unico se capturan definitivamente en Recepcion de Almacen, porque antes de recibir la mercancia normalmente no se conocen.
- Compras puede conservar datos esperados del documento, como lote o caducidad declarada en XML, como evidencia; esos datos no crean existencia oficial hasta la recepcion.
- Si una misma partida llega en dos lotes o con distintas caducidades, Almacen debe dividir la recepcion en renglones de lote independientes, manteniendo el mismo SKU ERP y el costo correspondiente.
- Las reglas del SKU deben indicar si requiere lote, caducidad, serie/codigo unico, fecha minima de caducidad o tolerancia. Recepcion debe bloquear el cierre fisico si falta un dato obligatorio.
- Una compra preoperativa puede generar el pendiente por `id_sku_erp` y almacen. La captura posterior de existencia observada puede crear el ajuste real, pero no sustituye la recepcion formal de Almacen.

## Decision operativa: compras preoperativas con incidencias de inventario

Documentacion IA: Codex GPT-5
Fecha: 2026-10-14
Proposito: permitir que el negocio registre compras reales antes de tener el inventario formal completamente ordenado.
Impacto: Compras, Catalogo, Almacen, Inventario, Costos y Notificaciones.

### Necesidad

El negocio necesita comenzar a registrar compras para obtener valor operativo de ellas, aunque todavia no pueda enviar todas las compras a recepcion de Almacen ni afectar existencias oficiales. La compra debe servir para:

- conservar costo y proveedor;
- identificar productos nuevos;
- detectar productos existentes que necesitan regularizacion de inventario;
- capturar una cantidad conocida o estimada por almacen;
- crear pendientes para las areas responsables;
- preparar la transicion posterior a inventario formal.

### Regla de frontera

Una compra preoperativa puede registrar el documento, partidas, XML, adjuntos, pagos y costos, pero no debe crear por si sola:

- existencia oficial;
- movimiento de kardex;
- recepcion de almacen;
- reserva o disponibilidad de inventario;
- saldo de inventario confiable;
- ajuste automatico de cantidades por almacen.

La cantidad capturada por almacen se llamara `cantidad_reportada_operativa` o `conteo_declarado`, no `existencia oficial`. Es una evidencia operativa pendiente de conciliacion.

### Clasificacion automatica al cerrar la compra

Cuando una orden se cierre sin recepcion formal, Compras debe revisar cada partida inventariable:

1. Si no existe SKU ERP, crear o actualizar una incidencia para Catalogo/Proveedores de producto pendiente de alta o relacion.
2. Si existe SKU ERP y la compra corresponde a un articulo ya conocido, crear o actualizar una incidencia de Inventario por cada almacen que el usuario seleccione.
3. Si la partida es servicio, cargo, adicional o gasto no inventariable, no crear incidencia de inventario.
4. Si el usuario no conoce el almacen o la cantidad fisica, dejar el pendiente sin cantidad reportada y no inventar un cero.
5. Si el usuario conoce una cantidad aproximada, guardarla con indicador de precision `estimada`; si fue conteo fisico, usar `conteo_fisico`.

La incidencia debe ser idempotente por orden, SKU, almacen y tipo de pendiente. Reabrir o guardar nuevamente la orden no debe duplicarla.

### Resultado de auditoria y primera preparacion

Fecha: 2026-10-14

- `cerrada_sin_recepcion` ya existe en Compras y no prepara una recepcion de Almacen.
- Las partidas no inventariables ya se separan de la logica de productos fisicos.
- La incidencia de producto nuevo ya utiliza `erp_catalogo_incidencias_calidad`; no se debe duplicar en otra tabla de Compras.
- La bandeja `erp_pos_inventario_pendientes` no se reutilizara para compras: su resolucion esta ligada a ventas POS y puede generar movimientos de inventario.
- Se preparo el esquema `Compra/esquema_actualizar_incidencias_compras_preoperativas` para proponer y ejecutar la tabla `erp_inventario_incidencias_operativas`. La ejecucion autorizada creo la tabla en la base operativa.
- Se agrego la consulta `Compra/orden_incidencias_preoperativas_previsualizar_erp`, que clasifica una orden `cerrada_sin_recepcion` en pendientes de Catalogo, Inventario y partidas no inventariables sin escribir datos.
- La previsualizacion tambien devuelve la politica de inventario del SKU (`requiere_lote`, `requiere_caducidad`, `requiere_serie`, `requiere_serie_fabricante` y alertas de caducidad) para que la futura captura de Recepcion sepa que datos son obligatorios.
- Con autorizacion explicita del dueno, se creo `erp_inventario_incidencias_operativas` en la base operativa. Verificacion: tabla presente, 25 columnas y 12 indices.
- Respaldo previo validado: `C:\\xampp\\panel_db_backups\\productivo_artianicom_sys_20261015_074449_pdo_antes_compras_incidencias_inventario.sql`.
- Respaldo focalizado adicional antes de activar ajustes reales: `C:\\xampp\\panel_db_backups\\productivo_artianicom_sys_panel_20261016_121311_antes_incidencias_compras_ajuste_real_focalizado.sql`.
- SHA-256 del respaldo focalizado: `B16812E45690854E07F82DDCD2315ED79EC4540DF3582CD104DE847AD394D0D2`.
- SHA-256 del respaldo: `814C519984E4523B5521178D43320A4AA8596512C1E46FB203DD9D202E91EF73`.
- La ejecucion no inserto incidencias, no modifico existencias, no creo recepciones y no genero movimientos de kardex.
- Se implemento `OrdenesCompraErp::generarIncidenciasInventarioPreoperativas` y su endpoint `Compra/orden_incidencias_preoperativas_generar_erp`. Al guardar una orden como `cerrada_sin_recepcion`, las partidas inventariables con SKU ERP generan o sincronizan una incidencia idempotente de conteo; cargos/servicios y partidas sin SKU ERP se excluyen de esta bandeja.
- La sincronizacion usa el almacen actual capturado en la operacion y conserva incidencias ya `resueltas` o `descartadas`. Tambien crea una notificacion operativa para `inventario.conteo`.

La tabla propuesta conserva el origen de la compra, SKU, proveedor, almacen, cantidad comprada, cantidad reportada, precision del reporte, costo estimado y ciclo de atencion. Su huella unica evita duplicados al guardar o cerrar nuevamente la orden.

### Implementacion inicial de atencion por Inventario

Fecha: 2026-10-15

- `Inventario/incidencias_compras_operativas_listar_erp` consulta los pendientes de compras por almacen, estatus o texto.
- `Inventario/incidencia_compra_operativa_capturar_erp` registra una cantidad `estimada` o `conteo_fisico` y deja la incidencia en `capturado`.
- Se agrego la vista `Inventario/incidencias_compras_operativas` y su acceso en el menu de Inventario. La pantalla muestra existencia oficial, cantidad comprada y permite capturar la existencia observada.
- La captura se realiza directamente en la tabla: `Existencia registrada`, `Existencia capturada`, `Quedara` y `Diferencia` se revisan en la misma fila antes de usar `Afectar inventario`.
- La captura exige permiso `inventario.ajustar`, registra auditoria y aplica solo la diferencia como entrada o salida de kardex en el mismo almacen. `inventario.ver` solo permite consultar.
- La incidencia queda bloqueada despues de aplicar el ajuste para evitar duplicados; la recepcion formal sigue siendo responsabilidad de Almacen.
- Se mantiene el ciclo `pendiente -> en_revision -> capturado -> conciliado -> resuelto/descartado`; marcar una incidencia como leida no la resuelve.
- Los productos sin SKU ERP no se envian a la bandeja de Inventario: se mantienen como incidencia de Catalogo (`compra_producto_pendiente_alta`). Catalogo puede crear un SKU temporal en borrador o el usuario puede relacionarlo desde Proveedores con un SKU ERP existente.
- Catalogo dispone de la accion `Reanalizar` para volver a consultar las relaciones activas despues de una correccion. Si encuentra una relacion unica confiable, actualiza la identidad de la partida y resuelve la incidencia de compra; no crea una relacion ambigua automaticamente.

UAT pendiente para esta etapa:

- `UAT-COM-PRE-INV-001`: cerrar una orden con dos productos inventariables y un cargo; deben crearse dos incidencias, no una por el cargo.
- `UAT-COM-PRE-INV-002`: guardar nuevamente la misma orden; no deben duplicarse incidencias ni notificaciones.
- `UAT-COM-PRE-INV-003`: consultar la bandeja por permiso `inventario.ver`, capturar existencia con `inventario.conteo` y verificar que solo la diferencia cambie existencias y kardex.
- `UAT-COM-PRE-INV-004`: confirmar que una incidencia `resuelto` o `descartado` no pueda ser sobrescrita por una captura posterior.
- `UAT-COM-PRE-INV-005`: capturar una existencia mayor a la actual y verificar una entrada por la diferencia; repetir la accion y confirmar que no duplica el movimiento.
- `UAT-COM-PRE-INV-006`: capturar una existencia menor a la actual y verificar una salida por la diferencia; si no hay existencia disponible suficiente, la transaccion debe revertirse completa.

### Incidencia para producto nuevo

Destino: Catalogo/Proveedores.

Debe conservar como minimo:

- orden y detalle de origen;
- proveedor y SKU del proveedor;
- descripcion capturada;
- SKU ERP si posteriormente se relaciona;
- costo y unidad de compra;
- datos fiscales disponibles;
- imagen o XML relacionado cuando exista;
- estatus de atencion;
- responsable y observaciones.

La notificacion visible debe ser algo como: `Catalogo tiene un producto pendiente de alta desde la compra OC-...`. La incidencia es la fuente de trabajo; la notificacion solo avisa y enlaza.

### Incidencia para producto existente

Destino: Inventario/Almacen.

### Decision 2026-10-16 - Ajuste real por incidencia de compra

- `cantidad_comprada` representa lo comprado en la orden.
- `existencia_actual` representa la existencia oficial del SKU en el almacen antes de atender la incidencia.
- `cantidad_reportada` representa lo contado o estimado actualmente en ese almacen.
- El sistema calcula `cantidad_reportada - existencia_actual` y genera entrada, salida o confirmacion sin cambio.
- La incidencia conserva cantidad anterior, resultado, diferencia, usuario, fecha y movimientos generados.
- Una incidencia resuelta no puede volver a aplicar el ajuste.
- Los productos sin SKU ERP siguen primero en Catalogo; despues de crearlos o relacionarlos se ejecuta Reanalizar y entonces aparecen en Inventario.
- Lote, caducidad y ubicacion siguen siendo datos de trazabilidad de Almacen; no se inventan desde una incidencia global.

Debe permitir seleccionar uno o varios almacenes y capturar por cada uno:

- cantidad reportada;
- unidad;
- precision: `estimada` o `conteo_fisico`;
- fecha del reporte;
- persona que la informo;
- observaciones;
- evidencia opcional, como foto o documento.

El sistema puede mostrar como referencia la cantidad oficial actual si existe, pero debe distinguirla visualmente de la cantidad reportada. No debe reemplazar la existencia oficial hasta que Almacen/Inventario realice una conciliacion o ajuste autorizado.

Ejemplo:

- SKU `PCE-001`, compra cerrada sin recepcion;
- almacen `Tienda`, cantidad reportada `4`, precision `estimada`;
- almacen `Bodega`, cantidad reportada `8`, precision `conteo_fisico`;
- existencia oficial: se muestra aparte y puede quedar vacia;
- resultado: dos pendientes de inventario, no dos entradas de kardex.

### Estado operativo recomendado

La orden puede usar `cerrada_sin_recepcion` cuando Compras termina su parte. El pendiente de inventario debe tener su propio ciclo:

- `pendiente`: creado y sin atender;
- `en_revision`: Almacen/Inventario lo esta verificando;
- `capturado`: ya tiene cantidad reportada y evidencia suficiente;
- `conciliado`: fue comparado contra inventario oficial;
- `resuelto`: se aplico el ajuste o se confirmo que no hacia falta;
- `descartado`: se determino que no corresponde.

No conviene cambiar la orden a `recibida` o `parcial` por este flujo. Esos estados pertenecen a una recepcion fisica formal.

### Vista operativa propuesta

Dentro de Compras puede existir una accion `Cerrar sin recepcion y generar pendientes`. Antes de confirmar debe mostrar un resumen:

- productos nuevos para Catalogo;
- productos existentes pendientes de conteo por almacen;
- cargos/servicios sin incidencia de inventario;
- partidas que no tienen almacen o cantidad reportada.

Despues, el listado debe mostrar una columna `Pendientes` con enlaces a Catalogo o Inventario. Al reabrir la orden se debe ver el estado de cada pendiente, no volver a crearla.

### Fases de implementacion

| ID | Tarea | Modulo | Escritura BD | Estado |
| --- | --- | --- | --- | --- |
| COM-PRE-001 | Auditar el contrato actual de cierre sin recepcion y partidas no inventariables | Compras | No | Lista |
| COM-PRE-002 | Auditar incidencias existentes de Catalogo y pendientes POS de Inventario | Catalogo/Inventario | No | Lista |
| COM-PRE-003 | Definir contrato idempotente de pendiente por SKU/almacen/orden | Compras/Inventario | No | Lista |
| COM-PRE-004 | Preparar dry-run de generacion de incidencias desde una orden cerrada | Compras | No | Implementada, pendiente UAT |
| COM-PRE-005 | Agregar captura de almacenes y cantidades reportadas en la orden | Compras | Si, requiere respaldo/autorizacion |
| COM-PRE-006 | Generar incidencia de Catalogo para SKU nuevo o no relacionado | Compras/Catalogo | Si, requiere respaldo/autorizacion |
| COM-PRE-007 | Generar incidencia de Inventario por SKU y almacen | Compras/Inventario | Si, requiere respaldo/autorizacion |
| COM-PRE-008 | Mostrar seguimiento y notificacion en navbar por permiso | Notificaciones | Si, requiere respaldo/autorizacion |
| COM-PRE-009 | UAT con compra mixta: nuevo, existente, cargo y varios almacenes | Compras/Inventario | Si, con respaldo |

### Criterio de cierre de esta fase

El flujo queda listo cuando una compra pueda cerrarse sin recepcion, genere pendientes unicos para cada responsable, permita capturar cantidades por almacen sin mover inventario y deje preparada la conciliacion posterior.

Regla para Catalogo:

- No abrir una seccion completamente separada que duplique productos. Debe existir una seccion o campo dentro de Catalogo ERP para clasificar el tipo operativo del SKU.
- En una fase futura puede existir una vista filtrada llamada "Materia prima e insumos", pero usando las mismas tablas maestras de producto/SKU.
- Todo SKU inventariable debe mantener unidad base, unidad de compra, factor de conversion, reglas de lote/caducidad y relacion proveedor cuando vaya a comprarse.

Regla para Fabricacion futura:

- Fabricacion no compra directo; solicita o dispara necesidades de compra.
- La receta/BOM debe apuntar a `id_sku_erp` de materia prima/insumo.
- Una orden de produccion debe consumir inventario y generar producto terminado o semiterminado, con trazabilidad de lotes cuando aplique.

## Decision operativa: plantillas imprimibles de Compras

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-28  
Proposito: permitir imprimir o compartir solicitudes/ordenes con distinta informacion segun audiencia.  
Impacto: Solicitudes, Ordenes, documentos internos, documentos para proveedor, permisos, logo y configuracion futura de formato.

Decision:

- La configuracion debe vivir en el modulo de Compras, porque define como se imprimen documentos propios de Compras.
- No debe mezclarse con configuracion global hasta que exista una administracion transversal de marca/empresa.
- Deben existir plantillas por tipo de documento y audiencia:
  - `solicitud_compra_interna`
  - `solicitud_compra_proveedor`
  - `orden_compra_interna`
  - `orden_compra_proveedor`
- La version interna puede mostrar costos, totales, impuestos, observaciones internas, usuario solicitante, aprobaciones y evidencia de autorizacion.
- La version para proveedor debe mostrar por defecto solo datos operativos:
  - logo de la empresa si esta configurado,
  - nombre/datos visibles de la empresa si estan configurados,
  - titulo externo entendible para el proveedor, por ejemplo `Solicitud de cotizacion` u `Orden de compra`,
  - folio,
  - fecha,
  - proveedor,
  - SKU proveedor,
  - descripcion del proveedor,
  - unidad,
  - cantidad,
  - observacion publica.
- La version para proveedor no debe mostrar por defecto:
  - costo estimado,
  - costo con/sin impuestos,
  - totales internos,
  - margen/utilidad,
  - SKU ERP,
  - nombre interno ERP,
  - observaciones internas.
- Los costos solo se deben mostrar al proveedor si una plantilla autorizada lo habilita explicitamente.
- Los documentos para proveedor no deben usar etiquetas internas como `ERP`, `documento operativo` o textos pensados para explicar el sistema al capturista. El encabezado externo debe comunicar quien solicita, que documento es, folio, fecha y datos de contacto.

Tablas propuestas:

- `erp_compras_documentos_plantillas`
  - Identifica la plantilla: codigo, tipo_documento, audiencia, nombre, descripcion, estatus, es_default.
- `erp_compras_documentos_plantillas_config`
  - Guarda opciones visibles: mostrar_logo, logo_ruta, mostrar_costos, mostrar_impuestos, mostrar_totales, mostrar_sku_erp, mostrar_sku_proveedor, mostrar_nombre_erp, mostrar_nombre_proveedor, mostrar_observaciones_internas, mostrar_observaciones_publicas, titulo_documento, subtitulo_documento, empresa_nombre, empresa_rfc, empresa_contacto, empresa_email, empresa_telefono, empresa_direccion, columnas_json, estilos_json, pie_pagina.
- Futuro opcional: `erp_compras_documentos_generados`
  - Snapshot del documento emitido/descargado/enviado, con plantilla usada, usuario, fecha y hash del HTML/PDF cuando se requiera trazabilidad.

Regla de permisos:

- Ver/imprimir documentos internos requiere `compras.ver`.
- Configurar plantillas requiere un permiso puntual futuro, recomendado: `compras.documentos.configurar`.
- Imprimir documento para proveedor puede requerir `compras.ver`, pero si incluye costos o totales debe validar permiso operativo de aprobacion o configuracion definida.

Primera fase recomendada:

- Crear dry-run de esquema para las dos tablas de plantillas.
- Crear configuracion semilla en codigo, no en migracion automatica, para las cuatro plantillas base.
- Agregar boton/modal en solicitud:
  - plantilla: interna / proveedor,
  - mostrar logo,
  - mostrar costos si la plantilla lo permite.
- Generar primero HTML imprimible; PDF puede quedar como fase posterior.

Actualizacion 2026-08-13:

- Se agrego configuracion de encabezado externo por plantilla: titulo, subtitulo y datos visibles de empresa.
- `solicitud_compra_proveedor` queda por defecto como `Solicitud de cotizacion`.
- `orden_compra_proveedor` queda por defecto como `Orden de compra`.
- Si una plantilla para proveedor no tiene logo configurado, no se muestra marcador interno `ERP`.
- Se centralizaron logo y datos del negocio en `sys_configuracion_parametros` para reutilizarlos en todas las plantillas; las plantillas quedan como configuracion de audiencia/formato, no como maestro repetido de empresa.
- La vista `/compra/documentos_configuracion` permite cargar el logo compartido y capturar nombre comercial, razon social, RFC, contacto, email, telefono y direccion.

## Decision operativa: Sugerido de compra por proveedor

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-20  
Proposito: crear una herramienta de planeacion de compra por proveedor sin afectar inventario ni kardex.  
Impacto: Compras/Solicitudes, Catalogo ERP, Proveedores, UX operativa y futuras pruebas UAT.

Decision:

- La seccion se llamara `Sugerido de compra`.
- El objetivo es ayudar a decidir que pedir por proveedor cuando el inventario del sistema aun no es perfecto.
- No debe modificar existencias, no debe crear movimientos de kardex y no debe afectar inventario.
- Debe listar solo SKUs comprables vinculados al proveedor mediante el contrato ERP vigente (`erp_catalogo_sku_proveedores`).
- Actualizacion 2026-08-21: Sugerido de compra no debe cargar todo el catalogo del proveedor al seleccionarlo; el usuario agrega partidas buscando codigos o descripciones del proveedor.
- Actualizacion 2026-08-21: Sugerido debe usar renglones reales de lista del proveedor como origen de captura y no expandir variantes/presentaciones internas del ERP cuando comparten el mismo SKU proveedor.
- Actualizacion 2026-08-21: La busqueda de Sugerido no debe usar nombre/SKU interno ERP como criterio principal; el SKU ERP se muestra solo como referencia secundaria para trazabilidad.
- Debe mostrar el lenguaje del proveedor como primera referencia: SKU proveedor, nombre proveedor, unidad de compra, factor y costo disponible.
- Debe consultar reglas de Catalogo ERP: `stock_minimo`, `stock_maximo` y `punto_reorden` desde `erp_catalogo_sku_reglas_inventario`.
- La existencia del sistema no sera obligatoria en la primera version; el calculo debe partir de la existencia fisica revisada o estimada capturada por el usuario.
- La pantalla no debe tener tres campos editables por partida. La UX recomendada es:
  - `Existencia revisada`: editable, captura rapida fisica o estimada.
  - `Cantidad sugerida`: calculada y visible.
  - `Cantidad a solicitar`: editable, precargada con la sugerida.
- Si se requiere una version aun mas simple para operacion rapida, `Cantidad sugerida` puede mostrarse como texto auxiliar dentro de la misma celda de `Cantidad a solicitar`.
- La cantidad sugerida se calcula asi:
  - Si hay maximo: pedir hasta llegar a maximo.
  - Si no hay maximo pero hay punto de reorden: pedir diferencia contra punto de reorden.
  - Si no hay punto de reorden pero hay minimo: pedir diferencia contra minimo.
  - Si el resultado es negativo, sugerir cero.
  - Si la relacion proveedor tiene `cantidad_minima` o `factor_conversion`, redondear hacia arriba de forma operativa para compra.
- La cantidad final siempre debe poder editarse antes de generar solicitud.
- Al generar la solicitud, debe crearse una Solicitud de Compra normal, con sus partidas, costos estimados y trazabilidad de origen `sugerido_compra`.
- El flujo debe permitir guardar una revision como borrador, editarla despues, verla en solo lectura y duplicarla como nueva.
- Al duplicar una revision, no debe copiar ciegamente minimos/maximos antiguos; debe volver a consultar las reglas actuales de Catalogo ERP y recalcular sugeridos.
- Si durante una revision se detecta que un SKU no tiene minimos/maximos/punto de reorden, la pantalla debe permitir capturarlos como propuesta para futuras revisiones, pero su guardado en Catalogo debe ser una accion controlada y auditada.
- Registrar o actualizar minimos/maximos desde este flujo requiere permiso puntual y autorizacion, porque modifica reglas maestras de Catalogo, aunque no afecte inventario.

Estados recomendados de una revision:

- `borrador`: editable; aun no genera solicitud.
- `lista`: revision terminada y lista para generar solicitud.
- `solicitud_generada`: ya genero una solicitud normal; queda como evidencia.
- `cancelada`: descartada con motivo; no se borra fisicamente.

Tablas candidatas, pendientes de autorizacion y respaldo antes de cualquier DDL:

- `erp_compras_sugeridos_compra`
  - cabecera de revision: proveedor, usuario, fecha, estatus, observaciones, id_solicitud_generada.
- `erp_compras_sugeridos_compra_detalle`
  - snapshot por SKU: id_sku, id_sku_proveedor, sku_proveedor, nombre_proveedor, minimo, maximo, punto_reorden, existencia_revisada, cantidad_sugerida, cantidad_solicitar, costo_estimado, unidad_compra, factor_conversion.

UAT minimo sugerido:

- `UAT-COM-SUG-001`: seleccionar proveedor y listar solo SKUs relacionados activos.
- `UAT-COM-SUG-002`: capturar existencia revisada y calcular cantidad sugerida con minimo/maximo.
- `UAT-COM-SUG-003`: editar cantidad final y guardar revision en borrador.
- `UAT-COM-SUG-004`: duplicar revision y recalcular con minimos/maximos actuales.
- `UAT-COM-SUG-005`: generar solicitud normal desde revision sin afectar inventario.
- `UAT-COM-SUG-006`: proponer minimos/maximos faltantes sin escribir Catalogo sin permiso/autorizacion.
Implementacion preparada 2026-08-20:

- Se agrego listado operativo `/compra/mostrar_sugeridos_compra`.
- Se agrego formulario `/compra/sugerido_compra` para nueva revision o edicion.
- Se agrego vista de solo lectura `/compra/ver_sugerido_compra/{id}`.
- Se agregaron endpoints ERP nuevos: `sugeridos_listar_erp`, `sugeridos_productos_proveedor_erp`, `sugerido_consultar_erp`, `sugerido_guardar_erp`, `sugerido_duplicar_erp`, `sugerido_generar_solicitud_erp`.
- El listado permite filtrar por proveedor, estatus y busqueda libre.
- El formulario calcula sugerido desde existencia revisada, minimo, maximo, punto de reorden, factor y cantidad minima.
- Minimo, maximo y punto de reorden pueden capturarse como propuesta dentro de la revision, pero no actualizan Catalogo ERP todavia.
- Duplicar como nueva revision vuelve a consultar relaciones activas proveedor-SKU y reglas actuales antes de recalcular cantidades.
- Generar solicitud crea una Solicitud de Compra normal en borrador y conserva la relacion `id_sku_proveedor` exacta.
- Respaldo externo creado antes de DDL: `C:\xampp\db_backups\panel_de_control\artianilocal_antes_sugerido_compra_20260820-235644.sql`.
- Esquema puntual ejecutado con autorizacion explicita del dueno: `erp_compras_sugeridos_compra` y `erp_compras_sugeridos_compra_detalle`.
- Verificacion posterior: ambas tablas existen, `ComprasSugeridosCompraErp::listar()` responde `schema_pendiente=0` y sin errores.

## Estado actual implementado

### Solicitudes de compra

Archivos principales:

- `app/modelos/SolicitudesCompraErp.php`
- `app/controladores/Compra.php`
- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`

Funciones actuales:

- Crear solicitud.
- Editar solicitud.
- Ver solicitud.
- Listar solicitudes.
- Cambiar estatus.
- Generar orden desde solicitud.

Pendiente de revisar:

- Permisos finos en toda accion.
- Validaciones de estatus.
- Que una solicitud con orden activa no genere duplicados.
- Que una solicitud cancelada o rechazada no pueda generar orden.
- Mejorar UX de productos pendientes para futuras solicitudes.

### Ordenes de compra

Archivos principales:

- `app/modelos/OrdenesCompraErp.php`
- `app/controladores/Compra.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Crear orden directa.
- Editar orden en borrador.
- Ver orden.
- Listar ordenes.
- Generar orden desde solicitud.
- Enviar orden.
- Cancelar orden.
- Preparar recepcion de almacen al enviar.

Reglas actuales importantes:

- `borrador` permite edicion.
- `enviada` prepara recepcion de almacen.
- `parcial` y `recibida` deben venir de almacen, no de compras.
- `cerrada_sin_recepcion` finaliza la orden dentro de Compras sin preparar recepcion ni afectar inventario. Se usa para compras documentales, pruebas operativas, compras preoperativas o compras que sirven para alimentar catalogo/costos/proveedores sin pasar a almacen.
- `cancelada` no debe permitir cambios operativos.
- Enviar requiere permiso `compras.aprobar`.
- Finalizar sin almacen requiere permiso `compras.aprobar`.
- Cancelar requiere permiso `compras.cancelar`.
- Guardar orden existente requiere `compras.editar`.
- Crear orden nueva requiere `compras.crear`.

Politica recomendada de estatus de orden:

- `borrador`: captura editable, sin efecto operacional.
- `enviada`: compras aprueba y manda a almacen; desde aqui se prepara recepcion.
- `parcial`: almacen recibio parte de la mercancia; compras no debe establecerlo manualmente.
- `recibida`: almacen confirmo recepcion completa; compras no debe establecerlo manualmente.
- `cerrada_sin_recepcion`: compras cierra la orden sin almacen; conserva costos, pagos, adjuntos, XML e incidencias de catalogo/proveedor, pero no crea recepcion ni inventario.
- Actualizacion 2026-08-21: al cerrar sin recepcion, Compras puede consolidar costos operativos de SKUs ERP existentes y mantener alertas/incidencias de Catalogo/Proveedores; no crea recepcion, kardex ni existencias.
- `cancelada`: anula la orden cuando no hay recepcion, pagos o notas aplicadas.

Pendiente de mejorar:

- Reducir rodeos al crear orden nueva: pagos, notas, adjuntos y XML no deben obligar a guardar manualmente primero si el usuario ya esta capturando una compra real.
- Propuesta: implementar `borrador automatico`.
- Si el usuario intenta adjuntar, importar XML o agregar pago en una orden nueva sin ID, el sistema debe guardar automaticamente la cabecera minima y continuar.

### XML y conciliacion

Archivos principales:

- `app/modelos/ComprasXmlErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/controladores/Compra.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Importar XML.
- Guardar documento fiscal.
- Guardar conceptos.
- Conciliar conceptos contra detalle de orden.
- Resolver manualmente conceptos.
- Sincronizar pendientes.
- Evitar duplicados por UUID o hash.
- Si el XML ya existe por UUID/hash, la vista puede volver a parsearlo para cargar conceptos en otra orden, pero no debe crear otro registro fiscal ni otro archivo.

Reglas actuales:

- XML requiere orden existente.
- XML debe usarse en orden editable.
- XML no debe borrar productos de solicitud automaticamente.
- Si algo no coincide, debe ir a revision.

Pendiente de mejorar:

- Que en una orden nueva el XML pueda disparar guardado automatico de borrador.
- Mejorar comparacion por SKU proveedor, SKU ERP, descripcion y cantidad.
- Crear flujo claro para productos no incluidos en XML pero solicitados.
- Crear flujo claro para productos incluidos en XML pero no solicitados.
- Registrar fiscales faltantes de producto/SKU.
- No pisar datos fiscales existentes con valores vacios.

### Pagos, notas y saldos

Archivos principales:

- `app/modelos/PagosCompraErp.php`
- `app/controladores/Compra.php`
- `app/modelos/ComprasEsquema.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Consultar resumen financiero.
- Registrar pago.
- Cancelar pago.
- Registrar nota de credito.
- Cancelar nota de credito.
- Recalcular saldo desde backend.

Reglas actuales:

- Pagos y notas requieren `finanzas.operar`.
- Consulta financiera requiere `finanzas.ver`.
- Pago aplicado o conciliado reduce saldo.
- Nota aplicada reduce saldo.
- Pago o nota pendiente no reduce saldo.
- No se permite sobrepago.
- No se permiten pagos en orden `borrador`.
- No se permite cancelar orden con pagos o notas aplicadas.

Observacion UX importante:

Actualmente no permitir pagos en borrador es correcto contablemente, pero puede ser fastidioso si el usuario esta capturando una factura completa desde cero. Para reducir rodeos:

- La vista debe poder guardar automaticamente un borrador cuando el usuario quiera agregar pagos.
- Luego el pago se registra contra esa orden ya creada.
- El usuario no debe sentir que hizo dos pasos separados.

Pendiente:

- Crear guardado automatico previo para pagos/notas si orden nueva no tiene ID.
- Definir si pagos se capturan en compras o finanzas segun rol.
- Confirmar si compras puede registrar "intencion de pago" y finanzas concilia despues.
- Agregar referencia obligatoria para transferencia, tarjeta y nota de credito.
- Posible carga de comprobante ligada al pago.

### Adjuntos

Archivos principales:

- `app/modelos/AdjuntosCompraErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/controladores/Compra.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Funciones actuales:

- Listar adjuntos.
- Subir adjunto.
- Ver o descargar adjunto por endpoint autenticado.
- Cancelar adjunto.
- Eliminar archivo fisico al cancelar.
- Conservar registro historico.
- Validar MIME.
- Evitar duplicado por hash.

Reglas actuales:

- Ver adjunto requiere `compras.ver`.
- Subir/cancelar adjunto requiere `compras.adjuntos`.
- Archivos se guardan fuera de `public`, en `storage/erp/compras/ordenes/`.
- `.gitignore` excluye almacenamiento de adjuntos.

Pendiente:

- Guardado automatico de borrador si se adjunta en orden nueva.
- Vincular adjuntos a entidades concretas: orden, pago, nota, XML, proveedor.
- Clasificar adjuntos: cotizacion, factura, comprobante de pago, nota de credito, orden firmada, otro.
- Previsualizacion mejorada para PDF e imagenes.
- Mostrar motivo de cancelacion.
- Evitar cancelar adjuntos necesarios cuando la orden ya este recibida o conciliada.

### Almacen y recepcion

Archivos relacionados:

- `app/modelos/Almacenes.php`
- `app/modelos/AlmacenEsquema.php`
- `app/controladores/Almacen.php`
- `app/modelos/OrdenesCompraErp.php`

Flujo actual:

- Compras envia orden.
- Se crea o prepara recepcion de almacen.
- Almacen recibe productos.
- Almacen actualiza cantidades recibidas.
- Orden puede pasar a parcial o recibida segun recepcion.

Reglas deseadas:

- Compras no debe marcar manualmente `parcial` ni `recibida`.
- Almacen controla recepcion.
- Si se recibio algo, compras ya no debe poder cancelar libremente.
- Lote, caducidad, ubicacion, serie y existencias pertenecen a almacen/inventario, no a compras.

Pendiente:

- Revisar estatus exactos entre orden y recepcion.
- Generar enlaces desde orden a recepcion.
- Mostrar resumen de recepcion en vista Ver orden.
- Validar recepciones duplicadas.
- Probar compra completa: solicitud -> orden -> XML -> envio -> recepcion parcial -> recepcion completa.

## Permisos actuales del modulo

Compras:

- `compras.ver`: ver solicitudes, ordenes, adjuntos y documentos.
- `compras.crear`: crear solicitudes y ordenes.
- `compras.editar`: modificar solicitudes y ordenes existentes.
- `compras.aprobar`: aprobar solicitudes o enviar ordenes.
- `compras.cancelar`: cancelar documentos de compra.
- `compras.adjuntos`: subir o cancelar adjuntos.

Finanzas:

- `finanzas.ver`: consultar pagos, notas y saldo.
- `finanzas.operar`: registrar o cancelar pagos y notas.

Almacen:

- `almacen.ver`: consultar recepciones.
- `almacen.recibir`: registrar recepcion de mercancia.
- `almacen.ubicaciones`: administrar ubicaciones.

Catalogo:

- `catalogo.ver`: consultar productos, SKUs, proveedores y listas.
- `catalogo.editar`: crear o modificar productos/SKUs.
- `catalogo.costos`: ver o ajustar costos, margenes e impuestos.

Regla general:

Cada nueva funcion debe definir permiso antes de codificar endpoint.

## Roles involucrados

Roles esperados:

- `soporte_sistema`: acceso tecnico para diagnostico y mantenimiento.
- `compras`: opera solicitudes, ordenes, XML, adjuntos y puede consultar/registrar pagos si asi se decide.
- `finanzas_contabilidad`: opera pagos, notas, saldos, conciliacion y documentos contables.
- `almacen`: recibe productos, lotes, caducidades y ubicaciones.
- `catalogo_productos`: completa productos nuevos, SKU, impuestos y reglas.
- `direccion`: consulta y aprueba cuando aplique.
- `auditor`: consulta trazabilidad sin operar.

Decision pendiente:

- Confirmar si `compras` debe tener `finanzas.operar` o solo `finanzas.ver`.
- Alternativa mas controlada: compras captura "pago reportado" y finanzas lo marca `conciliado`.

## Estatus recomendados

### Solicitud de compra

- `borrador`: editable.
- `pendiente`: enviada para aprobacion.
- `aprobada`: puede generar orden.
- `rechazada`: no genera orden.
- `orden_generada`: ya tiene orden activa.
- `cancelada`: cerrada sin compra.

### Orden de compra

- `borrador`: editable por compras.
- `enviada`: ya fue emitida y genera recepcion.
- `parcial`: viene de almacen cuando hay recepcion parcial.
- `recibida`: viene de almacen cuando se recibio todo.
- `cancelada`: cerrada.

Reglas:

- Compras solo cambia `borrador -> enviada` o `borrador/enviada -> cancelada`.
- Almacen cambia a `parcial` o `recibida`.
- Una orden `parcial` o `recibida` debe ser vista, no editada desde compras.

## UX objetivo

El modulo debe ser rapido para capturar compras reales.

Problemas a evitar:

- Guardar, salir, volver a entrar, cargar XML.
- Guardar, salir, volver a entrar, cargar adjuntos.
- Guardar, salir, volver a entrar, cargar pago.
- Repetir datos de proveedor o factura.
- Perder informacion por no haber guardado.

Solucion propuesta:

### Guardado automatico de borrador

Cuando la orden es nueva y el usuario intenta:

- importar XML,
- subir adjunto,
- registrar pago,
- registrar nota,
- agregar productos pendientes,

la vista debe:

1. Validar proveedor y datos minimos.
2. Guardar borrador automaticamente.
3. Actualizar `orden_id` en la vista.
4. Continuar con la accion solicitada.

Esto mantiene backend correcto sin hacer al usuario dar rodeos.

## Productos y pendientes

Casos que deben contemplarse:

- Producto solicitado pero no comprado.
- Producto comprado en XML pero no solicitado.
- Producto existe en ERP pero no en lista del proveedor.
- Producto existe en lista proveedor pero no esta bien relacionado.
- Producto no existe en ERP.
- Producto con datos fiscales incompletos.
- Producto con costo diferente al historial.

Tablas actuales o esperadas:

- `erp_compras_ordenes_productos_atencion`
- `erp_proveedores_listas_productos_revision`
- tablas fiscales ERP del catalogo de productos/SKUs

Pendiente:

- Confirmar tabla final para fiscalidad ERP.
- Confirmar si revision de producto nuevo vive en catalogo o compras.
- Crear vista de atencion de productos pendientes.
- Generar tareas para catalogo_productos.

## Datos fiscales y SAT

Debe contemplarse:

- Clave producto SAT.
- Clave unidad SAT.
- Unidad.
- Objeto impuesto.
- Tipo impuesto.
- Porcentaje IVA.
- IEPS si aplica.
- Precio incluye IVA.
- Requiere factura.
- Fuente del dato: manual, XML, catalogo, proveedor.

Reglas:

- No pisar datos existentes con vacios.
- Si XML trae dato fiscal y ERP no lo tiene, sugerir o registrar pendiente.
- Si hay diferencia entre ERP y XML, generar alerta.

## Calculos

Calculos principales:

- Subtotal linea = cantidad * costo antes de impuesto - descuento.
- Impuesto linea = subtotal linea * porcentaje impuesto.
- Total linea = subtotal linea + impuesto.
- Total orden = suma total linea.
- Saldo pendiente = total orden - pagos aplicados - notas aplicadas.

Para cargos, servicios y gastos asociados a compras, revisar tambien `docs/erp_gastos_cargos_compra_trabajo.md`. La regla actual es: Compras los captura para cuadrar total, Almacen no los recibe como inventario, y Finanzas/Costos definira si quedan como gasto o se prorratean al costo real de productos.

Reglas:

- Calculo final debe validarse en backend.
- Frontend puede calcular para UX, pero no ser fuente unica de verdad.
- No permitir total menor que movimientos financieros aplicados.
- XML debe distinguir costo antes de impuesto y total con impuesto.

Pendiente:

- Descuentos globales.
- Retenciones.
- IEPS.
- Moneda extranjera y tipo de cambio contable.
- Redondeos por XML SAT vs calculo interno.

## Adjuntos y documentos

Tipos esperados:

- Cotizacion.
- Factura.
- XML.
- PDF de factura.
- Comprobante de pago.
- Nota de credito.
- Orden firmada.
- Captura de pantalla.
- Otro.

Pendiente:

- Vincular comprobante de pago a pago especifico.
- Vincular nota de credito a nota especifica.
- Mostrar documentos desde la vista Ver orden.
- Descargar ZIP de todos los documentos de una orden.

## Auditoria

Debe auditar:

- Crear solicitud.
- Editar solicitud.
- Aprobar/rechazar solicitud.
- Generar orden desde solicitud.
- Crear orden directa.
- Editar orden.
- Enviar orden.
- Cancelar orden.
- Importar XML.
- Resolver conciliacion XML.
- Subir/cancelar adjunto.
- Registrar/cancelar pago.
- Registrar/cancelar nota.
- Preparar recepcion.
- Recibir mercancia.

Pendiente:

- Vista de historial por orden.
- Mostrar usuario, fecha, accion y datos relevantes.

## Documentacion obligatoria en codigo

Cada funcion nueva o bloque importante debe documentarse con:

```php
/**
 * Modulo: ERP Compras
 * Funcion: nombre_funcion
 * Documentacion IA: Codex GPT-5
 * Fecha: YYYY-MM-DD
 * Descripcion: Que hace y por que existe.
 * Permisos: permiso.necesario
 * Tablas afectadas: tabla_1, tabla_2
 * Reglas: reglas de negocio relevantes
 */
```

En JavaScript:

```js
/**
 * Modulo: ERP Compras
 * Funcion: nombreFuncion
 * Documentacion IA: Codex GPT-5
 * Fecha: YYYY-MM-DD
 * Descripcion: Que controla en la vista.
 * Endpoints: /compra/endpoint
 * Notas UX: comportamiento esperado para el usuario.
 */
```

Si en el futuro se usa otra IA o version:

- Cambiar `Documentacion IA`.
- No borrar contexto anterior si explica decisiones historicas.
- Agregar nota de migracion si se cambia comportamiento.

## Tareas prioritarias siguientes

### Bloque 1 - UX sin rodeos

Estado: Implementado; UAT pendiente  
Prioridad: Alta  
Objetivo: Permitir capturar una compra completa sin guardar manualmente varias veces.

Tareas:

- [x] Crear mecanismo JS para asegurar borrador antes de acciones que necesitan ID.
- [x] Si no hay `orden_id`, guardar borrador automatico para adjuntos/XML cuando aplique.
- [x] Reutilizarlo antes de XML y adjuntos en orden nueva.
- [x] Evitar doble submit.
- [ ] UAT: probar crear orden nueva completa desde cero con XML, adjuntos y reingreso.
- [ ] Decision pendiente: pagos/notas se registran cuando la orden ya no esta en borrador; Finanzas debe operar despues de enviada.

### Bloque 2 - Flujo de estatus

Estado: Implementado; UAT pendiente  
Prioridad: Alta

Tareas:

- [x] Validar permisos por transicion.
- [x] Bloquear pagos/notas en borrador y cancelada.
- [x] Bloquear cancelacion con recepcion iniciada.
- [x] Bloquear cancelacion con pagos/notas aplicadas.
- [x] Mantener `parcial` y `recibida` como estatus controlados por Almacen.
- [x] Agregar mensajes claros por cada bloqueo operativo.
- [ ] UAT: probar ciclo borrador -> enviada -> parcial -> recibida y bloqueos de cancelacion.

### Bloque 3 - Vista Ver orden

Estado: Implementado; UAT pendiente  
Prioridad: Alta

Tareas:

- [x] Mostrar cabecera.
- [x] Mostrar productos.
- [x] Mostrar XML y conciliacion.
- [x] Mostrar adjuntos con descarga.
- [x] Mostrar pagos/notas si tiene `finanzas.ver`.
- [x] Mostrar recepcion vinculada cuando exista.
- [x] No permitir inputs editables ni acciones operativas.
- [ ] UAT: revisar vista en orden borrador, enviada, parcial, recibida y cancelada.

### Bloque 4 - Productos pendientes y revision

Estado: Implementado en generacion de pendientes; vista de atencion pendiente  
Prioridad: Alta

Tareas:

- [x] Consolidar productos pendientes por proveedor/SKU desde Compras.
- [x] No duplicar pendientes por huella/origen.
- [x] Separar en regla operativa: Catalogo atiende producto/SKU; Proveedores atiende relacion proveedor-SKU.
- [x] Generar incidencia interdepartamental desde orden de compra para producto fisico sin SKU ERP o relacion proveedor-SKU faltante.
- [ ] Crear vista de atencion.
- [ ] Resolver pendiente y vincular a producto/SKU desde el modulo responsable.

### Bloque 5 - Fiscalidad de productos

Estado: Parcial; cierre pertenece a Catalogo/Fiscalidad ERP  
Prioridad: Media-Alta

Tareas:

- [ ] Definir tablas ERP fiscales finales.
- [x] Mapear XML a fiscalidad disponible en captura de Compras.
- [x] No sobrescribir datos buenos con vacios.
- [x] Generar alertas/incidencias por faltantes o diferencias detectables.
- [ ] Crear vista de revision fiscal en Catalogo/Fiscalidad.

### Bloque 6 - Reportes y trazabilidad

Estado: Pendiente  
Prioridad: Media

Tareas:

- Reporte de compras por proveedor.
- Reporte de saldos pendientes.
- Reporte de productos no surtidos.
- Reporte de productos nuevos.
- Reporte de diferencias XML vs orden.
- Historial de costos por SKU/proveedor.

## Checklist antes de cerrar Compras

- [x] Solicitudes completas.
- [x] Orden directa completa.
- [x] Orden desde solicitud completa.
- [x] Ver orden completa.
- [x] XML completo y conciliacion usable.
- [x] Adjuntos completos.
- [x] Pagos y notas completos.
- [x] Flujo de estatus implementado.
- [x] Recepcion de almacen conectada.
- [x] Productos pendientes generados.
- [ ] Vista de atencion de productos pendientes funcionando.
- [ ] Datos fiscales cerrados en Catalogo/Fiscalidad.
- [ ] Permisos por rol probados.
- [x] Auditoria de acciones sensibles registrada.
- [ ] Pruebas con factura real sin descuento.
- [ ] Pruebas con factura real con descuento.
- [ ] Pruebas con productos nuevos.
- [ ] Pruebas con productos no surtidos.
- [ ] Pruebas con pago parcial.
- [ ] Pruebas con nota de credito.
- [ ] Pruebas con adjuntos imagen/PDF/XML.

## Bitacora de decisiones

### 2026-06-07 - Reinicio limpio del modulo ERP

Documentacion IA: Codex GPT-5

Decision:

- Separar arquitectura nueva de compras ERP.
- Evitar depender de ecommerce.
- Usar permisos finos.
- Separar pagos, adjuntos y XML en modelos/endpoints propios.

Motivo:

- El modulo anterior se rehizo varias veces y tenia mezclas entre crear/editar/ver.
- Se busca un ERP robusto, mantenible y con menos errores.

### 2026-06-07 - Pagos fuera de borrador

Documentacion IA: Codex GPT-5

Decision:

- No registrar pagos reales en orden `borrador`.

Matiz UX:

- Para no hacer tediosa la captura, se debe implementar guardado automatico de borrador cuando el usuario intente registrar pagos desde una orden nueva.

### 2026-06-07 - Adjuntos privados

Documentacion IA: Codex GPT-5

Decision:

- Guardar adjuntos fuera de `public`.
- Servir archivos por endpoint autenticado.
- Cancelar adjunto elimina archivo fisico, pero conserva registro.

Motivo:

- Seguridad, trazabilidad y control de almacenamiento.

## Decision operativa: mini inventario en Sugerido de compra

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-27  
Modulo: Compras / Sugerido de compra

- La existencia revisada capturada en Sugerido de compra es una herramienta operativa de decision, no inventario oficial.
- No debe crear kardex, movimientos ni modificar existencias reales.
- Puede mostrar un valor aproximado de inventario fisico usando `existencia revisada x costo estimado` para ayudar a dimensionar mercancia disponible.
- Ese importe debe tratarse como estimacion de trabajo, no como valuacion contable definitiva.

## Decision UX: Escaneo en Sugerido de compra

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-27  
Modulo: Compras / Sugerido de compra

- El escaneo por camara es una ayuda de captura para buscar codigos dentro del proveedor seleccionado.
- No debe listar productos fuera del proveedor ni saltarse la relacion activa proveedor-SKU ERP.
- Si el codigo escaneado coincide con un solo producto del proveedor, puede agregarse automaticamente al sugerido.
- Si hay varias coincidencias, el usuario debe elegir para evitar partidas equivocadas.
- El backend puede buscar tambien en codigos alternos del SKU ERP, pero solo despues de filtrar por proveedor y relacion activa.

## Decision tecnica: Prioridad de costo en Sugerido de compra

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-07  
Modulo: Compras / Sugerido de compra

- El costo mostrado en Sugerido de compra no debe tomar precio de venta de Catalogo.
- La prioridad correcta es: costo vigente en `erp_proveedores_sku_costos`, costo de la lista vinculada del proveedor y, al final, `erp_catalogo_sku_proveedores.costo_ultimo` como respaldo operativo.
- Si una relacion proveedor-SKU acaba de generarse desde una lista con costo, Sugerido debe reflejar ese costo de lista aunque `costo_ultimo` este desactualizado.
- Catalogo conserva datos del producto; Proveedores conserva relaciones/listas/costos; Compras guarda snapshot cuando genera solicitud u orden.

## Decision operativa: Reglas de resurtido desde Sugerido de compra

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-08  
Modulo: Compras / Sugerido de compra

- Sugerido de compra puede actualizar `stock_minimo`, `stock_maximo` y `punto_reorden` del SKU para no recapturar reglas en futuras revisiones.
- La accion escribe en `erp_catalogo_sku_reglas_inventario`, pero no modifica inventario, existencias, kardex ni recepciones.
- La actualizacion debe ser explicita: checkbox al guardar o boton `Actualizar reglas`.
- El boton puede usarse tambien en sugeridos ya convertidos a solicitud para recuperar reglas capturadas previamente sin reabrir el documento.
- La existencia revisada sigue siendo dato operativo del sugerido y no se convierte en inventario oficial.
- Al consultar un sugerido existente, si sus partidas no tienen min/max/reorden capturados, la vista debe hidratar esos campos desde las reglas actuales del SKU para permitir reutilizar la informacion guardada.

## Decision operativa: Estatus y resumen de Sugerido de compra

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-08  
Modulo: Compras / Sugerido de compra

- Los sugeridos no se borran fisicamente; se cancelan logicamente para conservar trazabilidad.
- Solo pueden cancelarse sugeridos sin solicitud generada.
- El resumen del listado debe mostrar estimados de trabajo solo de sugeridos pendientes: `borrador` y `lista` sin solicitud generada.
- `Inventario revisado estimado` es existencia revisada por costo estimado; no es inventario oficial ni contable.
- `Compra sugerida estimada` es cantidad a solicitar por costo estimado; sirve para dimensionar futuras solicitudes.
- El listado debe mostrar tambien el inventario revisado estimado por cada sugerido para comparar revisiones pendientes sin abrir una por una.

## Decision UX: Revision fisica en Sugerido duplicado

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-08  
Modulo: Compras / Sugerido de compra

- El formulario debe separar el buscador para agregar productos del proveedor del filtro sobre partidas ya agregadas.
- El filtro de partidas permite localizar rapidamente productos dentro de listas duplicadas o largas sin agregar partidas nuevas.
- El escaneo por camara puede operar en modo `agregar producto` o en modo `filtrar partidas agregadas`.
- La accion `Existencias a 0` reinicia la captura fisica revisada y recalcula cantidades sugeridas sin tocar inventario real.
- Cada partida agregada puede eliminarse del sugerido antes de guardar o generar solicitud; esta accion solo depura el documento de trabajo y no afecta catalogo, proveedor ni inventario.

## Decision operativa: Cantidad solicitada manual en Sugerido

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-10  
Modulo: Compras / Sugerido de compra

- `Cantidad sugerida` es un calculo basado en existencia revisada, minimos, maximos, punto de reorden, factor y cantidad minima.
- `Cantidad solicitada` es una decision editable del usuario y debe respetarse al guardar, aunque sea menor, mayor o igual a 0.
- El sistema solo debe reemplazar `Cantidad solicitada` cuando el usuario ejecute una accion explicita de recalculo masivo.
- El backend no debe recalcular sobre la cantidad manual solo porque haya minimos/maximos configurados.
- Los campos numericos del sugerido deben aceptar separadores comunes para evitar que una captura valida se guarde como 0 o sea sustituida por el sugerido.

## Decision UX: Imagen de producto en Sugerido

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-19  
Modulo: Compras / Sugerido de compra

- El buscador de productos del proveedor y la tabla de partidas agregadas deben mostrar una miniatura del producto cuando Catalogo ERP tenga imagen.
- La imagen se obtiene de `erp_catalogo_imagenes`, priorizando imagen del SKU y despues imagen del producto base.
- Sugerido no debe guardar ni duplicar rutas de imagen en sus tablas de detalle; solo las consulta para apoyar la captura.
- Si el producto no tiene imagen, la UI debe mostrar un placeholder discreto para evidenciar que falta saneamiento visual en Catalogo.
- La accion `Limpiar busqueda` solo borra texto y resultados del proveedor; no debe eliminar partidas ya agregadas al sugerido.

## Decision operativa: XML y relacion proveedor/SKU

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-26  
Modulo: Compras / Ordenes / XML

- Si un concepto del XML coincide con una relacion activa proveedor/SKU, debe tratarse como producto registrado aunque no exista todavia una partida relacionada en la orden.
- El resultado `coincidencia_catalogo` es una coincidencia valida y no debe generar pendiente de alta.
- Si un SKU proveedor coincide con varias relaciones activas, el resultado correcto es `ambigua`; no debe mostrarse como producto pendiente de alta, sino como pendiente de seleccionar variante/SKU ERP.
- La conciliacion de XML debe buscar por SKU del proveedor, codigo interno, codigo de barras y SKU ERP, aceptando diferencias menores de guiones, espacios y mayusculas.
- Este proceso no crea productos ni relaciones nuevas; solo usa las relaciones activas de Proveedores ERP y Catalogo ERP.
- Si despues de relacionar un SKU en proveedor/catalogo se vuelve a abrir la orden y se carga/consulta el XML, debe recalcular la coincidencia antes de marcar un producto como pendiente.

## Politica de saneamiento: relaciones proveedor/SKU

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-26  
Modulo: Compras / Proveedores / Catalogo

- Compras debe confiar primero en relaciones proveedor/SKU respaldadas por un renglon real de lista del proveedor (`erp_proveedores_listas_detalle_erp` + `erp_proveedores_listas_erp`).
- Las relaciones activas en `erp_catalogo_sku_proveedores` sin respaldo de lista pueden existir como historicas o manuales, pero no deben ganar sobre una relacion respaldada por lista cuando el XML o el buscador traen el SKU/codigo del proveedor.
- Si un SKU proveedor tiene una sola relacion respaldada por lista y varias relaciones sueltas, la relacion de lista gana automaticamente.
- Si un SKU proveedor tiene dos o mas relaciones respaldadas por lista para el mismo proveedor, el caso sigue siendo ambiguo y debe resolverse manualmente.
- No se deben borrar ni inactivar relaciones sueltas sin respaldo externo, autorizacion y reporte previo de impacto.
- Antes de limpiar datos, clasificar:
  - `mantener`: relacion respaldada por lista, costo vigente o uso operativo actual.
  - `revisar`: relacion suelta usada en solicitudes, ordenes, recepciones, costos o historial.
  - `candidata_inactivar`: relacion suelta sin lista, sin costo vigente y sin uso operativo conocido.
  - `ambigua_real`: varias relaciones respaldadas por lista para el mismo SKU proveedor.
- La limpieza debe hacerse por inactivacion logica, no por borrado fisico, salvo que el dueno autorice explicitamente otra politica.
