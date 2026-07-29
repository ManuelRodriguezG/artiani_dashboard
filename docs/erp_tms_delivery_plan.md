# ERP TMS Delivery - Plan maestro operativo

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: plan rector inicial; no implica cambios de esquema, codigo ni BD.

## Proposito

Construir un modulo TMS/Delivery para administrar entregas a cliente como una capacidad fuerte del negocio, sin mezclar el valor del producto con el servicio logistico.

El negocio vende productos de acuario y accesorios para mascotas, con una ventaja operativa clara: responder rapido por redes sociales, concretar pedidos en el mismo dia y, en casos express, entregar incluso en menos de una hora. El sistema debe proteger esa ventaja sin convertir el envio en un regalo invisible ni en una obligacion ilimitada de postventa.

## Decision principal

La entrega no debe modelarse como un producto inventariable ni como una partida comun del catalogo.

Debe modelarse como un servicio logistico independiente con folio propio, costo, precio cobrado, responsable, ruta, evidencia y condiciones de servicio. Puede nacer porque POS, ecommerce, un operador o postventa solicitan mover un paquete, pero TMS no debe gobernar si una venta existe, si esta pagada, si tiene garantia o si procede una devolucion.

Regla superior:

- Ventas vende y cobra productos.
- TMS intenta entregar un paquete o ejecutar un traslado.
- CRM aporta datos de contacto/direccion cuando existan.
- Almacen prepara fisicamente cuando aplique.
- Garantias/Postventa resuelve reclamos de producto, si existen.

TMS solo necesita saber que hay un servicio logistico solicitado, que debe cumplirse o quedar documentado como no entregado, reprogramado, cancelado o pendiente de decision.

Regla operativa:

- El producto conserva su precio, garantia, inventario, costo y trazabilidad.
- La entrega, recoleccion, traslado o segunda visita conserva su propio precio, costo operativo, evidencia y estatus.
- Una garantia del producto no implica automaticamente recoleccion, entrega adicional ni visita gratis.
- Si el negocio decide absorber un traslado por cortesia, excepcion o estrategia comercial, debe quedar registrado como excepcion comercial/logistica autorizada.
- El estatus de una entrega no debe cambiar automaticamente el estatus de una venta. Si la entrega falla, TMS registra la falla y queda disponible para reprogramar, cancelar el servicio o esperar decision del cliente.

## Problema de negocio que resuelve

Hoy el cliente puede interpretar que, si el negocio llevo el producto, tambien debe recogerlo o cambiarlo a domicilio cuando exista un reclamo. Esto crea riesgo porque:

- se diluye la diferencia entre garantia del producto y servicio de entrega;
- se vuelve dificil medir rentabilidad real de ventas express;
- se normalizan viajes no cobrados;
- no queda claro cuando una garantia debe atenderse fisicamente en local;
- se pierde evidencia de entrega, condiciones recibidas y acuerdos con el cliente.

El modulo debe permitir vender con agilidad sin regalar estructura operativa.

## Principios

- Rapidez primero, pero con trazabilidad minima.
- Separar producto, servicio logistico, garantia y devolucion.
- Mostrar al operador las condiciones antes de confirmar.
- No castigar la venta express con captura pesada.
- No permitir que el delivery mueva inventario por fuera de Ventas/Almacen.
- No usar auditoria, WhatsApp o notas sueltas como bandeja de entregas.
- Toda entrega debe poder tener evidencia: quien preparo, quien llevo, cuando salio, cuando llego, quien recibio y observaciones.

## Responsabilidades por modulo

### Ventas/POS

Puede:

- crear una solicitud de entrega desde venta inmediata, pedido o apartado;
- cobrar el servicio logistico como componente separado, si el flujo POS lo permite;
- mostrar en ticket el producto y el servicio de entrega por separado;
- consultar estatus de entrega.

No debe:

- decidir rutas;
- cerrar entregas sin evidencia;
- mover inventario por entrega sin pasar por el flujo de venta/pedido;
- convertir garantia de producto en entrega gratis.
- depender de TMS para decidir si la venta fue valida o no.

Regla:

- La configuracion de cobrar producto, cobrar envio, cobrar contra entrega o cobrar solo envio pendiente pertenece a POS/Ventas/Caja.
- TMS solo recibe el dato operativo de si el servicio logistico esta pagado, por cobrar o bonificado.

### Ecommerce publico

Puede:

- solicitar cotizacion o promesa de entrega;
- crear pedido con direccion y ventana de entrega;
- mostrar condiciones de servicio logistico.

No debe:

- prometer disponibilidad express sin validacion de stock, zona, horario y capacidad;
- ocultar el costo logistico dentro del producto si el negocio quiere separarlo.

### CRM

Debe ser dueno de:

- cliente canonico;
- telefonos/contactos;
- direcciones de entrega;
- preferencias de contacto;
- historial de interacciones relacionadas.

No debe:

- cobrar entregas;
- decidir rutas;
- modificar ventas o inventario.

### Almacen/Inventario

Debe participar cuando una entrega requiere preparacion fisica:

- surtido/picking;
- empaque;
- salida preparada;
- devolucion fisica, cuarentena o reingreso cuando exista retorno.

No debe:

- vender el servicio logistico;
- resolver condiciones comerciales con el cliente.
- decidir si una venta se cancela porque no se logro entregar.

### Garantias/Postventa

Debe administrar el caso de producto:

- elegibilidad;
- requisitos;
- diagnostico;
- decision;
- evidencia.

Regla clave:

- Un caso de postventa puede solicitar un servicio logistico nuevo: recoleccion, entrega posterior, visita tecnica o traslado al local.
- Ese servicio puede cobrarse, bonificarse o absorberse, pero debe registrarse como decision separada de la garantia del producto.
- El tipo de servicio no debe llamarse `garantia` ni `reentrega_garantia`, porque ese nombre puede comunicar que la garantia incluye el traslado. La garantia puede ser el origen documental; el servicio logistico sigue siendo independiente.

### Finanzas/Caja

Debe registrar:

- cobro de entrega;
- costo operativo si se mide por repartidor, gasolina, plataforma o tercero;
- excepciones/bonificaciones;
- saldo pendiente si el servicio se cobra contra entrega.

No debe:

- mezclar el cobro del envio con el valor del producto sin desglose operativo.

## Tipos de servicio logistico

Tipos iniciales recomendados:

- `entrega_local`: entrega programada en zona normal.
- `entrega_express`: entrega prioritaria, por ejemplo menos de 60 minutos si hay capacidad.
- `entrega_programada`: entrega en dia/ventana acordada.
- `recoleccion_cliente`: recoger producto en domicilio del cliente.
- `entrega_postventa`: traslado posterior a la venta, por ejemplo llevar un cambio, reparacion o producto acordado.
- `traslado_revision`: mover producto hacia/desde local para revision, si el negocio decide ofrecerlo.
- `visita_revision`: visita para revisar, medir o diagnosticar, si el negocio decide ofrecerla.
- `envio_tercero`: plataforma, paqueteria o repartidor externo.

No todos deben activarse en fase 1. Para arrancar conviene usar:

- entrega local;
- entrega express;
- recoleccion cliente;
- entrega postventa.

## Estados recomendados

Estados del servicio:

- `cotizada`: precio/condiciones calculadas, no confirmada.
- `solicitada`: el cliente acepto y la venta/pedido la requiere.
- `programada`: tiene fecha/ventana y responsable.
- `preparando`: productos en surtido/empaque.
- `lista_para_salida`: paquete listo.
- `en_ruta`: salio con repartidor o tercero.
- `entregada`: cliente recibio.
- `no_entregada`: no se pudo completar en el intento.
- `reprogramada`: cambia ventana por causa documentada.
- `pendiente_cliente`: queda esperando que el cliente recoja, confirme nueva fecha o de instrucciones.
- `cancelada`: ya no se hara.

Estados de cobro:

- `incluida_cortesia`: no se cobra por decision comercial.
- `cobrada`: servicio pagado.
- `por_cobrar`: se cobrara al entregar.
- `pendiente`: no se ha definido forma/cobro.
- `bonificada`: se absorbe por cortesia, queja, autorizacion o politica comercial explicita.

Estados de resultado logistico:

- `completa`: el paquete/servicio se entrego conforme a la solicitud.
- `parcial`: solo se entrego una parte o hubo condicion pendiente.
- `sin_entrega`: no se entrego nada.
- `cliente_recogera`: el paquete queda para recoleccion del cliente.
- `nuevo_intento_requerido`: se necesita reprogramar.
- `cerrada_sin_entrega`: se cancela el servicio logistico sin decidir automaticamente sobre venta/producto.

## Condiciones operativas que deben quedar visibles

En ticket, pedido, WhatsApp/export futuro o comprobante interno debe poder mostrarse:

- el producto tiene garantia segun politica vigente;
- la garantia se atiende en local salvo politica distinta;
- la entrega es un servicio logistico separado;
- recolecciones, entregas posteriores o visitas pueden generar costo logistico adicional aunque exista garantia del producto;
- cualquier cortesia debe aparecer como bonificacion o excepcion, no desaparecer.

Redaccion operativa sugerida para ticket:

```text
Entrega: servicio logistico independiente del producto.
Garantias: se atienden conforme a politica del producto. Traslados, recolecciones o entregas adicionales se cotizan por separado salvo autorizacion.
```

## Modelo de datos propuesto

### `erp_tms_servicios`

Encabezado del servicio logistico.

Campos sugeridos:

- `id_tms_servicio`
- `folio`
- `solicitado_por_modulo`: ventas, ecommerce, postventa, crm, manual
- `solicitado_por_tipo`: pos, pedido, apartado, reclamo_postventa, solicitud_manual
- `solicitado_por_id`
- `referencia_externa`: folio visible o referencia libre, opcional
- `motivo_logistico`: venta_inicial, entrega_adicional, recoleccion, revision, cambio_acordado, cortesia_autorizada, otro
- `id_cliente_crm`
- `id_direccion_crm`
- `cliente_nombre_snapshot`
- `cliente_contacto_snapshot`
- `direccion_snapshot`
- `tipo_servicio`
- `estatus_servicio`
- `estatus_cobro`
- `resultado_logistico`
- `prioridad`: normal, express, urgente
- `fecha_solicitud`
- `fecha_programada`
- `ventana_inicio`
- `ventana_fin`
- `creado_por`
- `responsable_asignado`
- `observaciones`

### `erp_tms_servicios_detalle`

Lineas fisicas, paquetes o referencias logisticas. No debe depender de `erp_ventas_detalle` para existir.

Campos sugeridos:

- `id_tms_servicio_detalle`
- `id_tms_servicio`
- `referencia_item_origen`
- `id_sku_erp` opcional
- `id_inventario_unidad` opcional
- `cantidad`
- `descripcion_snapshot`
- `requiere_cuidado_especial`
- `estatus_preparacion`

### `erp_tms_servicios_costos`

Separacion financiera del servicio.

Campos sugeridos:

- `id_tms_servicio_costo`
- `id_tms_servicio`
- `precio_cobrado`
- `costo_estimado`
- `costo_real`
- `metodo_cobro`
- `motivo_bonificacion`
- `autorizado_por`
- `datos_snapshot`

### `erp_tms_eventos`

Historial operativo.

Campos sugeridos:

- `id_tms_evento`
- `id_tms_servicio`
- `tipo_evento`
- `estatus_anterior`
- `estatus_nuevo`
- `comentario`
- `latitud`
- `longitud`
- `creado_por`
- `fecha_registro`

### `erp_tms_evidencias`

Evidencias de entrega, recoleccion o incidencia.

Campos sugeridos:

- `id_tms_evidencia`
- `id_tms_servicio`
- `tipo_evidencia`: foto, firma, nota, comprobante, ubicacion, chat_snapshot
- `ruta`
- `nombre_original`
- `descripcion`
- `estatus`
- `creado_por`

## Reglas para garantias y recolecciones

- El reclamo de garantia se abre en Garantias/Postventa.
- Si el cliente requiere que el negocio recoja el producto, Postventa puede solicitar un servicio TMS `recoleccion_cliente`, pero ese servicio se cotiza o bonifica aparte.
- Si despues de resolver el caso se debe llevar un reemplazo, reparacion o producto acordado, se crea `entrega_postventa`.
- El campo `solicitado_por_tipo='reclamo_postventa'` solo explica quien solicito el traslado; no significa que el traslado este incluido por garantia.
- El precio logistico puede ser:
  - cobrado al cliente;
  - bonificado por autorizacion;
  - absorbido por politica especifica;
  - no aplica si el cliente acude al local.
- La decision debe quedar en el caso de garantia y en el folio TMS.
- El producto reclamado, al entrar fisicamente, debe pasar por Almacen/Inventario: cuarentena, devoluciones, reparacion, merma o reingreso.

Ejemplo con pecera:

1. Venta POS/pedido cobra producto y entrega.
2. Ticket muestra producto y entrega separados.
3. TMS intenta entregar el paquete.
4. Si entrega completo, cierra `completa`.
5. Si no logra entregar, registra `no_entregada` con evidencia/motivo.
6. El servicio puede quedar `pendiente_cliente`, reprogramarse o cancelarse.
7. La venta no cambia automaticamente por el resultado de TMS. Si despues el negocio decide cancelar venta, devolver dinero o generar otro movimiento, eso pertenece a Ventas/Postventa, no a TMS.

Ejemplo de postventa:

1. Cliente reporta fuga.
2. Garantias/Postventa valida politica de producto.
3. Si el negocio decide mover fisicamente algo, solicita un servicio TMS independiente.
4. TMS solo opera el traslado solicitado y su cobro/bonificacion logistica.
5. La decision de garantia sigue viviendo fuera de TMS.

## Cotizacion y reglas comerciales

La cotizacion no debe depender solo del monto de venta.

Variables recomendadas:

- zona/colonia/codigo postal;
- distancia aproximada;
- tipo de servicio;
- urgencia;
- horario;
- volumen/peso/cuidado especial;
- repartidor interno o tercero;
- capacidad del momento;
- cliente/segmento CRM si aplica;
- politica comercial vigente.

Reglas recomendadas para fase 1:

- entrega local precio fijo por zona;
- entrega express con sobrecargo;
- recoleccion o entrega postventa siempre visible, aunque se bonifique;
- entrega gratis solo como promocion o autorizacion registrada;
- no mezclar descuento al producto con bonificacion del envio.
- TMS puede cobrar o marcar por cobrar el servicio de entrega, pero no cobra productos ni decide saldos de venta.

## UX recomendada

### Desde POS/pedido

- Boton o seccion `Entrega`.
- Selector: sin entrega, local, express, programada.
- Buscar/seleccionar direccion CRM o capturar direccion express.
- Mostrar precio logistico separado del subtotal de productos.
- Mostrar ventana prometida y condiciones.
- Confirmar con una previsualizacion corta.
- Al confirmar, POS crea una solicitud TMS con snapshot logistico. Desde ahi TMS opera su propio folio.

### Bandeja TMS

Columnas:

- folio;
- tipo;
- origen;
- cliente;
- zona;
- ventana;
- estatus;
- cobro;
- responsable;
- prioridad;
- acciones.

Acciones:

- programar;
- asignar repartidor;
- marcar lista para salida;
- iniciar ruta;
- registrar entrega;
- registrar no entregada/reprogramada;
- marcar pendiente de recoleccion por cliente;
- cancelar servicio logistico;
- anexar evidencia.

### Garantias/Postventa

Al crear reclamo:

- mostrar si la politica exige atencion en local;
- permitir crear recoleccion o entrega postventa solo como servicio separado;
- pedir motivo si se bonifica.

## Notificaciones

Eventos TMS que deben generar alerta:

- entrega express solicitada;
- servicio programado sin responsable;
- ventana proxima a vencer;
- entrega no completada;
- paquete pendiente de recoleccion por cliente;
- recoleccion postventa pendiente;
- evidencia de entrega faltante;
- servicio bonificado pendiente de autorizacion;
- producto listo para salida desde Almacen.

Permisos sugeridos:

- `tms.ver`
- `tms.programar`
- `tms.operar`
- `tms.evidencias`
- `tms.costos`
- `tms.autorizar`
- `tms.reportes`

Areas:

- Ventas ve servicios ligados a sus ventas/pedidos.
- TMS/Delivery opera rutas.
- Almacen ve pendientes de preparacion/salida.
- Garantias/Postventa ve servicios logisticos ligados a reclamos, sin tratarlos como cobertura automatica.
- Direccion/finanzas ve costos, bonificaciones y reportes.

## Reportes necesarios

- servicios solicitados por POS/ecommerce/manual/postventa;
- ingresos por servicio logistico;
- costo estimado vs costo real;
- servicios bonificados;
- entregas express por tiempo de respuesta;
- entregas fallidas por causa;
- reclamos postventa que generaron servicios logisticos;
- zonas rentables/no rentables;
- tiempo promedio desde venta hasta entrega;
- ventas cerradas gracias a entrega express.
- servicios no entregados que quedaron para recoleccion del cliente.

## Fases recomendadas

### Fase 0 - Plan y contratos

Estado: este documento.

Cierre:

- responsabilidades aceptadas;
- reglas producto vs entrega documentadas;
- dudas de negocio respondidas.

### Fase 1 - TMS documental ligado a POS/pedidos

Objetivo:

- crear folio TMS desde POS, pedido, ecommerce o solicitud manual sin optimizacion de rutas avanzada.

Debe incluir:

- esquema TMS;
- permisos;
- crear servicio desde POS/pedido;
- precio separado de producto;
- estados basicos;
- ticket con entrega separada;
- bandeja TMS;
- notificaciones iniciales.
- resultado no entregado con opciones: reprogramar, pendiente cliente o cancelar servicio.

### Fase 2 - Operacion de ruta y evidencia

Objetivo:

- controlar salidas reales.

Debe incluir:

- asignacion a repartidor;
- lista para salida;
- en ruta;
- entregada/fallida;
- evidencia/foto/nota;
- reporte de cumplimiento.

### Fase 3 - Integracion con Garantias/Postventa

Objetivo:

- separar reclamo de producto y logistica de recoleccion, traslado o entrega posterior.

Debe incluir:

- crear servicio desde reclamo;
- reglas de cobro/bonificacion;
- autorizacion de excepciones;
- entrada fisica a Almacen cuando hay recoleccion;
- reporte de costo logistico por garantia.

### Fase 4 - Cotizador y zonas

Objetivo:

- cotizar rapido y con reglas repetibles.

Debe incluir:

- zonas;
- tarifas;
- horarios;
- restricciones por tipo de producto;
- capacidad por repartidor;
- promesa express solo si hay condiciones.

### Fase 5 - Optimizar rutas

Objetivo:

- agrupar servicios, secuenciar rutas y medir eficiencia.

Debe incluir:

- rutas por repartidor;
- paradas;
- orden sugerido;
- tiempos reales;
- costos por ruta;
- integracion futura con mapas si se autoriza.

## Orden recomendado antes de implementar

1. Auditar `VentasErpEsquema.php` para identificar como POS solicitara servicios TMS sin que TMS dependa de la venta para operar.
2. Auditar CRM direcciones para reutilizar `crm_clientes_direcciones`.
3. Auditar Almacen para definir si la preparacion/salida de paquete requiere bandeja propia o solo snapshot inicial.
4. Proponer DDL TMS separado.
5. Proponer permisos en Seguridad.
6. Preparar endpoints read-only/dry-run antes de escritura real.
7. Implementar fase 1 sin rutas avanzadas.

## Dudas de negocio pendientes

- Zonas iniciales de entrega y precios base.
- Que se considera express: 30, 45, 60 o 90 minutos.
- Cuando la entrega se cobra contra entrega.
- Quien puede bonificar entrega, recoleccion, traslado o entrega postventa.
- Si habra repartidores internos, terceros o ambos desde fase 1.
- Texto final de condiciones que aparecera en ticket y mensajes al cliente.
- Que debe pasar por defecto cuando no se entrega: reprogramar, dejar para recoleccion del cliente o cancelar servicio.

## Handoff

Este modulo debe crearse como dominio propio `Tms` o `Delivery`, no como campo suelto en Ventas. Ventas/POS puede solicitar un servicio, CRM aporta cliente/direccion, Almacen prepara fisicamente cuando aplique, Garantias/Postventa puede solicitar servicios logisticos relacionados con un reclamo sin que eso signifique cobertura automatica, Finanzas mide cobros/costos logisticos y Notificaciones convierte atrasos o pendientes en trabajo visible.

La decision mas importante ya tomada es separar producto/venta/garantia frente al servicio logistico. TMS no decide si una venta se hizo, si una garantia procede o si se cancela una venta. TMS cumple, evidencia o cierra intentos de entrega. Si no se entrega, el servicio puede reprogramarse, quedar para recoleccion del cliente o cancelarse hasta nueva solicitud.

## Avance tecnico inicial

Fecha: 2026-07-24

Estado:

- Plan rector creado.
- Tareas vivas creadas en `docs/erp_tms_delivery_tareas.md`.
- DDL propuesto creado en `docs/erp_tms_delivery_schema_propuesta.sql`; no ejecutado.
- Permisos propuestos y solicitud de autorizacion creados:
  - `docs/erp_tms_delivery_permisos_plan.md`;
  - `docs/erp_tms_delivery_permisos_solicitud_autorizacion.md`.
- Modelo de esquema dry-run creado: `app/modelos/TmsEsquema.php`.
- Modelo de dominio read-only/dry-run creado: `app/modelos/TmsDelivery.php`.
- Controlador base creado: `app/controladores/Tms.php`.
- `Tms` agregado a controladores protegidos en `app/core/Core.php`.
- Guardado real preparado en codigo, bloqueado por esquema pendiente:
  - modelo `TmsDelivery::guardarServicio($datos, $idUsuario = 0)`;
  - endpoint `/tms/servicio_guardar_erp`;
  - crea encabezado, detalle, costo logistico y evento inicial cuando existan tablas;
  - no afecta Ventas, garantias ni inventario.
- Operacion de estados preparada en codigo, bloqueada por esquema pendiente:
  - modelo `TmsDelivery::aplicarAccionServicio($datos, $idUsuario = 0)`;
  - endpoint `/tms/servicio_accion_erp`;
  - acciones: programar, asignar responsable, lista para salida, iniciar ruta, entregar, no entregada, pendiente cliente, reprogramar y cancelar servicio;
  - registra evento TMS cuando existan tablas;
  - no afecta Ventas, garantias ni inventario.
- Evidencias TMS preparadas en codigo, bloqueadas por esquema pendiente:
  - modelo `TmsDelivery::listarEvidencias($idServicio)`;
  - modelo `TmsDelivery::registrarEvidencia($datos, $idUsuario = 0)`;
  - modelo `TmsDelivery::cancelarEvidencia($datos, $idUsuario = 0)`;
  - endpoints `/tms/evidencias_listar_erp`, `/tms/evidencia_registrar_erp`, `/tms/evidencia_cancelar_erp`;
  - tipos iniciales: foto, firma, nota, comprobante, ubicacion y chat_snapshot;
  - no afecta Ventas, garantias ni inventario.
- Reportes TMS preparados en codigo, read-only:
  - modelo `TmsDelivery::resumenReportes($filtros = array())`;
  - endpoint `/tms/reportes_resumen_erp`;
  - vista `app/vistas/paginas/apps/tms/reportes.php`;
  - JS `public/assets/js/custom/apps/tms/reportes.js`;
  - mide servicios, express, entregas completas/no entregadas, pendientes de cliente, ingresos logisticos, bonificaciones, tiempo promedio y agrupaciones por tipo/resultado/zona.
- Pantallas internas TMS ampliadas en modo read-only:
  - `Operacion y rutas` consulta cola TMS y KPIs operativos;
  - `Costos logisticos` consulta resumen financiero logistico;
  - `Configuracion delivery` muestra catalogos, contrato de dominio y acciones disponibles;
  - JS creados: `operacion.js`, `costos.js`, `configuracion.js`.
- UI inicial creada:
  - `app/vistas/paginas/apps/tms/servicios.php`;
  - `public/assets/js/custom/apps/tms/servicios.js`.
- Acceso de sidebar agregado como modulo padre `TMS`, separado de ERP/Ventas, condicionado a permisos `tms.*`.
- Sidebar TMS creado como modulo padre `TMS`, con grupo interno `Delivery`:
  - `Bandeja TMS` (`tms.ver`);
  - `Operacion y rutas` (`tms.operar`);
  - `Costos logisticos` (`tms.costos`);
  - `Reportes delivery` (`tms.reportes`);
  - `Configuracion delivery` (`tms.autorizar`).
- Pantallas base creadas para operacion, costos, reportes y configuracion; no ejecutan escrituras.
- UAT sidebar creado y ejecutado:
  - `storage/uat/uat_tms_delivery_sidebar_readonly.php`;
  - resultado `ok=true` para titulos, rutas, permisos, metodos y vistas.
- UAT read-only creado y ejecutado:
  - `storage/uat/uat_tms_delivery_permisos_readonly.php`;
  - `storage/uat/uat_tms_delivery_schema_readonly.php`;
  - `storage/uat/uat_tms_delivery_dryrun_readonly.php`.
- UAT go/no-go consolidado creado y ejecutado:
  - `storage/uat/uat_tms_delivery_go_nogo_readonly.php`;
  - salida resumida por defecto;
  - salida detallada con `--detalle=1`;
  - resultado 2026-07-24: `ok=true`, 45/45 checks correctos, estado `go_con_activaciones_pendientes`.
- Preflight de activacion controlada creado y ejecutado:
  - `storage/uat/uat_tms_delivery_preactivacion_readonly.php`;
  - resultado 2026-07-24: `ok=true`, 9/9 checks correctos, estado `preactivacion_preparada`;
  - propone respaldos y comandos separados para permisos, esquema y UAT manual.
- Verificacion post-permisos creada y ejecutada:
  - `storage/uat/uat_tms_delivery_permisos_postapply_readonly.php`;
  - resultado 2026-07-24: estado `permisos_tms_pendientes`, permisos 0/8, roles 0/8, menu TMS listo;
  - resultado esperado antes de aplicar autorizacion `TMS_PERMISOS_BASE`.
- Verificacion post-DDL creada y ejecutada:
  - `storage/uat/uat_tms_delivery_schema_postapply_readonly.php`;
  - resultado 2026-07-24: estado `schema_tms_pendiente`, pendientes schema 5/5, dry-run valido;
  - resultado esperado antes de aplicar autorizacion `TMS_DELIVERY_DDL_BASE`.
- UAT autorizado de servicio manual preparado:
  - `storage/uat/uat_tms_delivery_servicio_manual_apply_authorized.php`;
  - solicitud formal: `docs/erp_tms_delivery_uat_manual_solicitud_autorizacion.md`;
  - bloqueado sin token `TMS_UAT_SERVICIO_MANUAL` y respaldo valido;
  - en ejecucion futura crea solo servicio TMS de prueba, eventos y evidencia; no toca Ventas/POS/Garantias/Inventario.
- Preflight read-only de reversa DDL preparado:
  - `storage/uat/uat_tms_delivery_reversa_preflight_readonly.php`;
  - no ejecuta `DROP` ni borra datos;
  - resultado inicial 2026-07-25: `sin_conexion_mysql` por MariaDB local sin arrancar;
  - resultado actualizado 2026-07-25: `reversa_no_aplica_schema_pendiente`, conexion MySQL activa, tablas TMS existentes 0/5;
  - no hay token activo para reversa.
- Checklist de activacion creado y ejecutado:
  - `storage/uat/uat_tms_delivery_activacion_checklist_readonly.php`;
  - resultado 2026-07-25: `listo_para_permisos_tms`;
  - siguiente token permitido: `TMS_PERMISOS_BASE`;
  - DDL y UAT manual quedan bloqueados por dependencias.
- Respaldo previo a permisos TMS creado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_permisos.sql`;
  - tamano: 32809505 bytes;
  - no aplica permisos, no ejecuta DDL y no crea servicios.
- Permisos TMS aplicados en BD con autorizacion:
  - token: `TMS_PERMISOS_BASE`;
  - permisos sincronizados: 8;
  - roles validados: 8/8;
  - menu TMS listo;
  - no crea tablas TMS ni servicios TMS.
- Respaldo previo a DDL TMS creado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_delivery_schema.sql`;
  - tamano: 32811490 bytes;
  - script DDL probado sin token y bloqueado correctamente.
- DDL TMS aplicado en BD con autorizacion:
  - token: `TMS_DELIVERY_DDL_BASE`;
  - tablas creadas: 5;
  - post-DDL: `schema_tms_listo`;
  - no crea servicios TMS ni toca Ventas/POS/Inventario/Garantias.
- Respaldo previo a UAT manual TMS creado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_uat_manual.sql`;
  - tamano: 32820083 bytes;
  - script UAT probado sin token y bloqueado correctamente.
- UAT manual TMS ejecutado:
  - token: `TMS_UAT_SERVICIO_MANUAL`;
  - folio creado: `TMS-20260725-212914-255`;
  - servicio cerrado como `entregada` / `completa`;
  - evidencia tipo `nota` registrada;
  - no toca Ventas/POS/Inventario/Garantias.
- Validacion UI/datos TMS completada:
  - `storage/uat/uat_tms_delivery_ui_datos_readonly.php`;
  - resultado 2026-07-28: `ui_tms_datos_listos`;
  - checks: 49/49;
  - valida vistas, JS, folio UAT, KPIs y conteos TMS.
- Contrato POS -> TMS preparado:
  - `docs/erp_tms_delivery_integracion_pos_plan.md`;
  - `storage/uat/uat_tms_delivery_pos_contract_readonly.php`;
  - resultado 2026-07-28: `pos_tms_integracion_planificada`;
  - checks iniciales: 31/31;
  - checks con adapter dry-run: 35/35;
  - POS queda como solicitante logistico;
  - adapter creado: `/tms/servicio_pos_dryrun_erp`;
  - UI POS opt-in agregada en modo dry-run solamente;
  - UAT UI POS/TMS preparado: `storage/uat/uat_tms_delivery_pos_ui_readonly.php`;
  - preflight para creacion real futura preparado: `storage/uat/uat_tms_delivery_pos_real_preflight_readonly.php`;
  - autorizacion futura documentada en `docs/erp_tms_delivery_pos_real_solicitud_autorizacion.md`;
  - UAT UI POS/TMS: 24/24;
  - preflight real futuro: 21/21;
  - la creacion real futura debe ocurrir despues de venta/pedido real exitoso;
  - no crea servicios y no toca Ventas reales/Inventario/Garantias.
- Scripts de aplicacion autorizada preparados y validados en modo bloqueado:
  - `storage/uat/uat_tms_delivery_permisos_apply_authorized.php`;
  - `storage/uat/uat_tms_delivery_schema_apply_authorized.php`.
- Resultado UAT:
  - permisos `tms.*` listos en BD;
  - cinco tablas `erp_tms_*` creadas en BD;
  - catalogos, listado sin esquema y dry-run de solicitud TMS responden sin escritura.
  - guardado real validado con servicio TMS de prueba.
  - acciones operativas y evidencias validadas con servicio TMS de prueba.
  - reportes y pantallas internas ya pueden leer datos TMS reales de prueba.
  - go/no-go confirma codigo, esquema, UI/datos y contrato POS/TMS: 52/52.
  - UI/datos valida 49/49 checks con folio UAT.
  - contrato POS -> TMS valida 45/45 con UI POS opt-in.
  - UI POS/TMS valida 24/24.
  - preflight real futuro POS/TMS valida 21/21.
  - checklist de activacion confirma `activacion_base_completa`.
  - reversa DDL queda bloqueada por datos TMS existentes.
  - preactivacion confirma orden recomendado: permisos primero, DDL despues.
  - post-permisos deja preparada validacion inmediata despues de sincronizar seguridad TMS.
  - post-DDL deja preparada validacion inmediata despues de crear tablas `erp_tms_*`.
  - UAT manual deja preparada la primera prueba real sin integrar POS.
  - reversa DDL queda cubierta por diagnostico read-only; cualquier reversa futura requiere autorizacion separada.

Pendiente:

- Validar UI opt-in POS en navegador.
- Validar UI TMS en navegador con datos de prueba.
- No crear servicios TMS desde POS hasta tener UAT y autorizacion separada.
