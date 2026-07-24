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
- UI inicial creada:
  - `app/vistas/paginas/apps/tms/servicios.php`;
  - `public/assets/js/custom/apps/tms/servicios.js`.
- Acceso de sidebar agregado en `ERP > Delivery > Servicios TMS`, condicionado a `tms.ver`.
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
- Scripts de aplicacion autorizada preparados y validados en modo bloqueado:
  - `storage/uat/uat_tms_delivery_permisos_apply_authorized.php`;
  - `storage/uat/uat_tms_delivery_schema_apply_authorized.php`.
- Resultado UAT:
  - permisos `tms.*` pendientes en BD;
  - cinco tablas `erp_tms_*` pendientes en BD;
  - catalogos, listado sin esquema y dry-run de solicitud TMS responden sin escritura.
  - guardado real responde bloqueo controlado por esquema pendiente.

Pendiente:

- Sincronizar permisos `tms.*` en BD con autorizacion `TMS_PERMISOS_BASE`.
- Aplicar DDL TMS en autorizacion separada futura `TMS_DELIVERY_DDL_BASE`.
- No integrar POS/Ventas hasta que TMS tenga permisos, esquema aplicado y guardado real controlado.
