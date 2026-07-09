# ERP Ventas/POS - Plan maestro para POS robusto

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: plan rector; no implica escrituras en BD.

## Objetivo

Construir un POS formal, rapido y robusto para tienda fisica, con control completo de caja, inventario, clientes, precios, atenciones compartidas, pedidos/apartados, ticket, garantias, devoluciones y reportes.

El POS no debe ser solo una pantalla de cobro. Debe ser el centro operativo de la tienda.

## Principios

- Todo POS opera ligado a sucursal, almacen, caja, turno y usuario.
- Toda venta que controla inventario genera kardex y trazabilidad.
- Todo movimiento de dinero queda en caja y afecta corte.
- Todo precio/descuento se calcula y autoriza en backend.
- Todo cliente, garantia, devolucion y apartado conserva historial.
- No se mezclan tablas legacy/ecommerce como fuente operativa.
- No se escribe BD sin respaldo externo y autorizacion.

## Fase 0 - Validacion visual y cierre de base

Objetivo: confirmar que el POS base ya funciona visualmente y cerrar el ciclo minimo caja-venta-inventario.

Tareas:

1. Validar con Playwright `/ventas/pos` con usuario real o sesion de prueba explicita.
2. Confirmar que aparecen operador, sucursal, caja y turno.
3. Buscar SKU con stock real y agregar al carrito.
4. Validar carrito, cantidad, pago y totales.
5. Validar que la venta real UAT ya generada se ve en tablero.
6. Implementar cierre de turno real.
7. UAT: abrir turno, vender, cerrar turno, validar diferencia.

Entregable:

- POS base funcional, venta real validada y turno cerrable.

Avance 2026-06-29:

- Existe aplicador UAT autorizado de venta real por CLI con token `VENTAS_POS_VENTA_REAL`.
- El aplicador real ya cubre:
  - turno/caja/asignacion;
  - atencion compartida opcional;
  - excepcion comercial autorizada opcional;
  - snapshot CRM;
  - detalle de venta;
  - pagos;
  - caja;
  - kardex;
  - garantia snapshot;
  - trazabilidad detalle-inventario.
- En la medicion inicial aun no existia endpoint real `/ventas/pos_confirmar_erp` para POS UI.
- Se agrego readiness read-only `storage/uat/uat_ventas_pos_cobro_ui_readiness_readonly.php`.
- Resultado actual:
  - usuario `1` tiene `ventas.operar`;
  - asignacion POS activa a almacen `5` y caja `2`;
  - no hay turno abierto;
  - SKU 1760 sin existencia suficiente;
  - endpoints de soporte dry-run existen;
  - boton `Cobrar` debe seguir bloqueado hasta autorizacion fuerte.
- Decision:
  - el primer endpoint real de cobro puede usar `ventas.operar` como permiso base porque la convencion actual describe ese permiso como registrar ventas/pedidos/apartados;
  - precios manuales y descuentos no se autorizan con `ventas.operar`, sino con folio previo de excepcion comercial autorizado;
  - la venta real desde UI debe recalcular todo en backend y consumir folio de excepcion dentro de la misma transaccion.

Autorizacion requerida para el siguiente salto:

```text
AUTORIZO EXPONER COBRO REAL POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS
```

Avance posterior 2026-06-29:

- Autorizacion recibida y aplicada.
- Endpoint real `/ventas/pos_confirmar_erp` expuesto.
- Modelo `VentasErp::confirmarVentaPosReal`.
- UI `Cobrar` conectada con confirmacion.
- Contrato:
  - sesion, CSRF y `ventas.operar`;
  - auditoria explicita;
  - transaccion con rollback;
  - recalculo backend;
  - consumo transaccional de excepcion comercial si aplica;
  - venta, pagos, caja, kardex, garantia snapshot y trazabilidad.
- Readiness posterior:
  - endpoint existe;
  - sin escrituras;
  - bloqueado por falta de turno abierto y existencia insuficiente.
- Siguiente paso:
  - abrir turno y cargar stock UAT con autorizacion;
  - ejecutar UAT desde navegador o aplicador autorizado;
  - validar post-venta, ticket, kardex y cierre.

## Fase 1 - Caja completa y caja chica

Objetivo: que el corte explique todo el dinero, no solo ventas.

Decision tecnica 2026-06-27:

- La tabla base `erp_pos_movimientos_caja` ya permite apertura y venta POS UAT.
- Para gastos/retiros/vales/reembolsos reales, primero debe aplicarse DDL de caja completa.
- No se deben registrar movimientos reales de caja chica sin estatus, autorizacion, responsable, evidencia y cancelacion logica.
- El token separado para esta evolucion sera `VENTAS_POS_CAJA_DDL`.

Tareas:

1. Extender `erp_pos_movimientos_caja` con categoria, estatus, autorizacion, evidencia y relaciones opcionales.
2. Crear catalogo de categorias de movimientos de caja.
3. Crear permisos:
   - `pos.movimiento_caja`
   - `pos.gasto_caja`
   - `pos.retiro_caja`
   - `pos.autorizar_movimiento_caja`
   - `pos.cancelar_movimiento_caja`
4. Crear endpoints:
   - ingreso extraordinario;
   - retiro;
   - gasto;
   - vale;
   - reembolso;
   - cancelar movimiento.
5. Agregar panel `Caja` en POS.
6. Crear cierre de turno con:
   - fondo inicial;
   - ventas por metodo;
   - otros ingresos;
   - gastos;
   - retiros;
   - vales;
   - esperado;
   - contado;
   - diferencia.
7. Permitir cierre con diferencia distinta de cero:
   - faltante;
   - sobrante;
   - observaciones obligatorias;
   - reporte por empleado/caja/sucursal;
   - revision posterior.
8. UAT: venta + gasto + retiro + cierre cuadrado y cierre con diferencia controlada.

Decision operativa 2026-07-03:

- El turno puede cerrarse aunque la diferencia no sea `0`.
- La diferencia no bloquea el cierre; se guarda y se reporta.
- Un faltante/sobrante debe alimentar supervision, tendencias por empleado y revision de evidencias.
- Plan de reportes:
  - `docs/erp_ventas_pos_reportes_caja_plan.md`.

Avance 2026-06-27:

- Existe dry-run backend de movimientos no venta.
- Existe modal `Caja` en POS para simular gasto/retiro/entrada/vale/reembolso.
- Existe modal `Corte` en POS para simular cierre de turno con esperado, contado, diferencia, ventas, pagos y movimientos.
- El registro real sigue bloqueado hasta aplicar DDL de caja completa y autorizacion separada.

## Fase 2 - Atenciones compartidas / pedidos de mostrador

Objetivo: que varios vendedores puedan levantar cuentas desde varios dispositivos y caja pueda cobrarlas.

Avance 2026-06-27:

- Existe dry-run backend de bandeja de atenciones.
- Existe dry-run backend para simular convertir cuenta local en atencion persistente.
- Existe modal `Atenciones` en POS.
- DDL expandido de atenciones/clientes/listas/apartados ya fue aplicado el 2026-06-27.
- La bandeja read-only consulta sin esquema pendiente y sin atenciones abiertas.
- El flujo real sigue bloqueado hasta autorizar escritura de atencion persistente y conversion a venta/pedido/apartado.

Tareas:

1. Crear esquema:
   - `erp_pos_atenciones`
   - `erp_pos_atenciones_detalle`
   - `erp_pos_atenciones_eventos`
   - `erp_pos_atenciones_bloqueos`
2. Crear estados:
   - `borrador`
   - `lista_para_cobro`
   - `tomada_por_caja`
   - `convertida`
   - `cancelada`
   - `expirada`
3. Crear endpoints para abrir, editar, tomar, liberar, cancelar y convertir atencion.
4. Agregar bandeja `Atenciones` al POS.
5. Crear pantalla simple `Atender cliente` para vendedor.
6. Preparar lectura por codigo/lector.
7. Despues, agregar escaner por camara.
8. UAT multiusuario: vendedor crea atencion, caja la cobra.

## Fase 3 - Clientes ERP

Objetivo: identificar clientes sin frenar caja y preparar historial, listas y recompensas.

Estado actual:

- Existe endpoint dry-run `/ventas/pos_cliente_precio_dryrun_erp`.
- Existe modal POS `Cliente/precios` para resolver identificador contra backend.
- El esquema de clientes ya existe.
- Si no hay coincidencia, el flujo marca posible alta rapida futura.
- Existe endpoint dry-run `/ventas/pos_cliente_alta_rapida_dryrun_erp`.
- UI POS ya permite validar alta rapida sin escribir BD.
- Aplicador autorizado CLI preparado para alta rapida real.
- No se ha ejecutado alta rapida real UAT.

Tareas:

1. Auditar clientes legacy/ecommerce.
2. Crear esquema Clientes ERP.
3. Soportar publico general.
4. Crear busqueda express por telefono, nombre, correo o codigo.
5. Crear aplicador autorizado de alta rapida desde POS.
6. Crear ficha completa fuera del cobro.
7. Crear deteccion de duplicados y fusion.
8. Guardar snapshot de cliente en venta.
9. UAT: venta anonima, venta con cliente rapido, venta con cliente existente.

## Fase 4 - Precios, listas y descuentos

Objetivo: que el precio sea flexible sin perder control de margen y auditoria.

Estado actual:

- El resolutor backend dry-run ya devuelve precio base, precio aplicado, regla, lista y snapshot requerido.
- El POS muestra el resultado, pero no puede decidir descuentos ni modificar precios desde JS.
- El esquema de listas de precios ya existe, pero todavia no hay semillas UAT de listas especiales.
- Existe dry-run de excepcion comercial para precio manual, descuento por partida y descuento general.
- Precio manual y descuento general siguen bloqueados para venta real hasta definir permisos, autorizacion, margen minimo y snapshot persistente.

Tareas:

1. Crear listas de precios con vigencia, canal, sucursal, cliente y segmento.
2. Crear resolutor backend de precio.
3. Prioridad sugerida:
   - precio manual autorizado;
   - promocion vigente;
   - lista de cliente;
   - lista de segmento;
   - lista por canal/sucursal;
   - lista general.
4. Agregar descuento por partida.
5. Agregar descuento general.
6. Crear permisos de descuento y precio manual.
7. Guardar snapshot de regla/precio/descuento.
8. UAT: lista especial, descuento autorizado, bloqueo sin permiso.

Avance 2026-06-28:

- Endpoint `/ventas/pos_excepcion_comercial_dryrun_erp`.
- Modelo `VentasErp::excepcionComercialDryRun`.
- UI POS dentro de `Cliente y precios` para simular:
  - precio manual;
  - descuento por partida;
  - descuento general.
- Script read-only `storage/uat/uat_ventas_pos_excepcion_comercial_dryrun_readonly.php`.
- Contrato:
  - backend resuelve precio base/lista;
  - JS no decide precio final;
  - motivo obligatorio;
  - autorizacion obligatoria;
  - venta real debe guardar precio base, lista, aplicado, descuento, motivo y autorizacion.
- Guardrail agregado:
  - `prevalidarCarritoPos` recalcula precio por backend;
  - si `precio_unitario` enviado por POS difiere del precio backend, bloquea la prevalidacion;
  - subtotal y pagos se calculan contra precio backend, no contra precio enviado por navegador;
  - script read-only `storage/uat/uat_ventas_pos_precio_guardrail_readonly.php`.
- Permisos preparados en codigo:
  - `ventas.precio_manual`;
  - `ventas.descuento_partida`;
  - `ventas.descuento_general`;
  - `ventas.autorizar_excepcion_comercial`.
- Roles base en codigo:
  - `direccion`;
  - `administrador_erp`.
- Auditoria read-only:
  - `storage/uat/uat_ventas_pos_excepcion_permisos_readonly.php`;
  - BD actual aun no tiene esos permisos sembrados;
  - requiere autorizacion de siembra con respaldo externo.

Avance 2026-06-29:

- DDL de persistencia comercial preparado, no aplicado:
  - tabla `erp_ventas_politicas_comerciales`;
  - tabla `erp_ventas_excepciones_comerciales`;
  - columnas de excepcion general en `erp_ventas`;
  - columnas de excepcion por partida en `erp_ventas_detalle`;
  - indices de busqueda por excepcion.
- Scripts:
  - read-only `storage/uat/uat_ventas_pos_excepcion_schema_readonly.php`;
  - aplicador bloqueado `storage/uat/uat_ventas_pos_excepcion_schema_apply_authorized.php`;
  - token requerido `VENTAS_POS_EXCEPCION_DDL`.
- Auditoria actual:
  - faltan 2 tablas;
  - faltan 9 columnas;
  - faltan 2 indices;
  - no se ejecuto DDL.

## Fase 5 - Ticket formal

Objetivo: emitir comprobante operativo profesional.

Tareas:

1. Crear plantilla 58/80 mm.
2. Incluir:
   - negocio;
   - sucursal;
   - fecha/hora;
   - folio;
   - caja;
   - turno;
   - cajero;
   - cliente;
   - partidas;
   - descuentos;
   - impuestos;
   - pagos;
   - cambio;
   - garantia/devolucion;
   - QR o codigo de barras del folio.
3. Crear vista previa.
4. Crear impresion.
5. Crear reimpresion con permiso y auditoria.
6. Crear ticket de apartado, abono, devolucion y garantia.

## Fase 6 - Apartados, pedidos y abonos

Objetivo: apartar producto, recibir pagos parciales y entregar con trazabilidad.

Tareas:

1. Definir politica de apartado.
2. Crear/activar reservas de inventario.
3. Crear pedido sin pago.
4. Crear apartado con anticipo.
5. Crear abonos parciales.
6. Crear liquidacion.
7. Crear entrega final.
8. Crear cancelacion/liberacion de reserva.
9. UAT: apartado, abono, liquidacion, entrega, cancelacion.

## Fase 7 - Garantias y postventa

Objetivo: que cada producto vendido pueda tener politica de garantia clara y consultable.

Guia especifica:

- `docs/erp_garantias_plan.md`

Decision de modulo:

- Catalogo es el modulo dueno de la politica de garantia.
- Ventas guarda el snapshot de garantia al vender.
- Postventa/Ventas gestiona reclamos.
- Inventario/Almacen decide destino fisico del producto devuelto.
- Proveedores participa si la garantia se reclama al proveedor.

Tareas:

1. Catalogo:
   - crear politicas de garantia;
   - asignarlas por SKU, categoria, marca o proveedor;
   - definir plazo, cobertura, requisitos y exclusiones.
2. Ventas:
   - guardar snapshot de garantia en detalle de venta;
   - mostrar garantia en ticket;
   - permitir consulta por folio/cliente/SKU.
3. Postventa:
   - registrar reclamo;
   - adjuntar evidencia;
   - resolver como cambio, reparacion, rechazo, devolucion o nota.
4. Inventario/Almacen:
   - recibir producto a cuarentena;
   - reingresar, enviar a merma o devolver a proveedor.
5. Reportes:
   - garantias por SKU;
   - garantias por proveedor;
   - causas frecuentes;
   - costo por garantia.

## Fase 8 - Devoluciones y cancelaciones

Objetivo: revertir operaciones sin borrar historial.

Tareas:

1. Cancelacion antes de entrega.
2. Devolucion parcial.
3. Reembolso.
4. Reingreso a inventario.
5. Cuarentena.
6. Merma.
7. Devolucion a proveedor.
8. Reversa de descuentos, puntos o beneficios futuros.
9. UAT por folio.

Estado 2026-06-29:

- Dry-run robusto aplicado:
  - valida venta POS original;
  - valida detalle y cantidad disponible;
  - calcula reembolso estimado con precio historico;
  - bloquea cantidad excedente y motivo vacio;
  - cancelacion completa puede tomar todas las partidas.
- DDL de reversas preparado, no aplicado:
  - caja/almacen/turno en devolucion;
  - movimiento de caja;
  - decision financiera;
  - reembolso y saldo a favor;
  - autorizador/aplicador;
  - snapshot;
  - existencia y almacen destino por detalle.
- Readiness actual:
  - devolucion de `POS-20260629-000003` valida en dry-run;
  - falta aplicar DDL;
  - reembolso por caja requiere turno abierto o politica de saldo a favor.
- Siguiente decision:
  - aplicar DDL de reversas POS con autorizacion;
  - luego implementar reversa real transaccional.

## Fase 9 - Reportes gerenciales POS

Objetivo: visibilidad diaria por tienda, caja, turno, vendedor y producto.

Reportes:

- ventas por sucursal;
- ventas por caja/turno;
- ventas por vendedor/cajero;
- margen estimado;
- productos mas vendidos;
- stock bajo;
- gastos de caja;
- retiros;
- diferencias de corte;
- apartados pendientes;
- garantias/devoluciones;
- atenciones convertidas y perdidas.

## Fase 10 - Promociones, recompensas e incentivos

Objetivo: fidelizar sin romper contabilidad ni auditoria.

Orden recomendado:

1. Promociones simples.
2. Cupones.
3. Monedero/puntos.
4. Niveles de cliente.
5. Reversa por devolucion.
6. Incentivos a vendedor separados de recompensas de cliente.

## UAT minimo para considerar POS robusto

- Venta normal con efectivo.
- Venta con pago mixto.
- Venta con descuento autorizado.
- Venta con cliente.
- Venta con lista especial.
- Gasto de caja.
- Retiro de efectivo.
- Cierre de turno cuadrado.
- Atencion creada por vendedor y cobrada por caja.
- Apartado con anticipo y abono.
- Ticket formal impreso/reimpreso.
- Garantia consultada por folio.
- Devolucion parcial con destino inventario.
- Reporte por turno y sucursal.

## Estado UAT actualizado 2026-06-27

- Validado: venta POS real con efectivo.
- Validado: atencion persistente creada por vendedor y cobrada por caja.
- Validado: descuento de inventario con kardex en venta real.
- Validado: corte de turno real cuadrado, `TUR-20260626-002-001`, monto esperado `1090`, contado `1090`, diferencia `0`.
- Validado: DDL de caja completa aplicado para gastos, retiros, entradas, vales, reembolsos, evidencia y cancelacion.
- Validado: nuevo turno UAT `TUR-20260627-002-001` abierto con movimiento inicial trazable.
- Validado: gasto caja real `GASTO-UAT-001` por `50`, esperado del turno actualizado a `450`.
- Validado: cierre real del turno `TUR-20260627-002-001` con gasto incluido, contado `450`, diferencia `0`.
- Validado: stock UAT SKU `1760` recargado a disponible `2` por kardex `55`.
- Validado: nuevo turno POS UAT `TUR-20260627-002-002` abierto para venta/ticket.
- Validado: venta POS UAT real `POS-20260627-000002`, SKU `1760`, pago efectivo `295`, kardex `56`, existencia disponible `1`.
- Validado: cierre real del turno `TUR-20260627-002-002` con monto contado `795` y diferencia `0`.
- Validado: ticket formal read-only por folio con precio/lista/caja/turno/pago/inventario.
- Validado: guardado transaccional de snapshot de garantia al confirmar venta POS real.
- Validado: venta POS UAT real `POS-20260627-000003`, SKU `1760`, garantia snapshot `Sin garantia`, kardex `57`, existencia disponible `0`.
- Validado: ticket formal read-only de `POS-20260627-000003` sin hallazgo `VENTAS-TICKET-001`.
- Validado: cierre real del turno `TUR-20260627-002-003` con monto contado `795` y diferencia `0`.
- Preparado: impresion/reimpresion UI del ticket formal desde tablero Ventas.
- Pendiente: validar visualmente reimpresion UI con sesion web real, devoluciones, apartados con abonos y pagos mixtos.

## Estado UAT actualizado 2026-06-29

- Validado: permisos POS de excepcion comercial sembrados para precio manual, descuento partida, descuento general y autorizacion comercial.
- Validado: DDL de excepcion comercial con politicas, excepciones y columnas de trazabilidad en venta/detalle.
- Validado: politicas UAT activas para precio manual, descuento por partida y descuento general.
- Validado: guardrail de precio contra navegador; el backend no confia en precio alterado por JS.
- Validado: excepcion comercial autorizada y aplicada en venta real:
  - excepcion `EXC-20260628-000001`, venta `POS-20260628-000001`;
  - total/pago `285`, descuento `10`;
  - kardex `59`;
  - cierre `TUR-20260628-002-001` con diferencia `0`.
- Validado: hardening UI read-only para capturar y validar folio `EXC-*` desde POS antes de cobrar.
- Validado: UAT robusta completa con nuevo folio:
  - turno `TUR-20260629-002-001`;
  - excepcion `EXC-20260629-000001`;
  - venta `POS-20260629-000001`;
  - kardex `61`;
  - ticket formal sin hallazgos;
  - cierre esperado/contado `785`, diferencia `0`;
  - reutilizacion del folio bloqueada.
- Observacion vigente: la resolucion CRM/POS por identificador todavia no quedo cerrada en la UAT robusta; `EXC-20260629-000001` quedo con `id_cliente=NULL`.
- Proximo bloque recomendado: cerrar contrato CRM/POS antes de robustecer listas especiales, recompensas, apartados por cliente y postventa personalizada.

## Avance contrato CRM/POS 2026-06-29

- Diagnostico read-only creado:
  - `storage/uat/uat_ventas_pos_crm_contrato_readonly.php`.
- Resultado con `5550000000`:
  - existe en `erp_clientes` POS/UAT;
  - no existe en CRM canonico;
  - por eso la UAT robusta quedo como venta mostrador.
- Resultado con `3312345678`:
  - existe en CRM canonico;
  - `id_cliente_crm=1`;
  - POS/Ventas lo resuelve correctamente en dry-run.
- DDL preparado, no aplicado:
  - `VentasErpEsquema::planActualizarContratoCrmPos`;
  - `VentasErpEsquema::auditarContratoCrmPos`;
  - `storage/uat/uat_ventas_pos_crm_contrato_schema_readonly.php`;
  - `storage/uat/uat_ventas_pos_crm_contrato_schema_apply_authorized.php`.
- Faltantes actuales:
  - 9 columnas para `id_cliente_crm` y snapshots en ventas/excepciones;
  - 2 indices por cliente CRM.
- Modelo preparado:
  - `VentasErp::registrarExcepcionComercialAutorizada` guardara `id_cliente_crm` y snapshot si el DDL autorizado ya existe;
  - mientras no existan columnas, conserva compatibilidad actual.
- Siguiente autorizacion sugerida:
  - `AUTORIZO APLICAR DDL CONTRATO CRM POS VENTAS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`.

## Estado contrato CRM/POS aplicado 2026-06-29

- DDL aplicado con autorizacion `VENTAS_POS_CRM_CONTRATO_DDL`.
- `erp_ventas` ya tiene:
  - `id_cliente_crm`;
  - `cliente_codigo_snapshot`;
  - `cliente_origen_snapshot`;
  - `cliente_snapshot`;
  - indice `idx_ventas_cliente_crm_fecha`.
- `erp_ventas_excepciones_comerciales` ya tiene:
  - `id_cliente_crm`;
  - `cliente_codigo_snapshot`;
  - `cliente_nombre_snapshot`;
  - `cliente_identificador_snapshot`;
  - `cliente_origen_snapshot`;
  - indice `idx_ventas_excepcion_cliente_crm`.
- Verificacion read-only posterior: sin faltantes.
- Dry-run post-DDL con `3312345678`:
  - cliente CRM `id_cliente_crm=1`;
  - excepcion simulada sin bloqueos;
  - total estimado `285`, descuento `10`.
- Siguiente paso: UAT real con cliente CRM canonico para validar excepcion, venta, ticket, kardex y caja guardando `id_cliente_crm`.

## Estado UAT CRM/POS real 2026-06-29

- UAT robusta con cliente CRM canonico aprobada:
  - cliente `CRM-POSUAT-20260628-0001`;
  - `id_cliente_crm=1`;
  - identificador `3312345678`.
- Folios:
  - turno `TUR-20260629-002-002`;
  - excepcion `EXC-20260629-000002`;
  - venta `POS-20260629-000002`.
- Validaciones:
  - excepcion comercial guarda `id_cliente_crm=1` y snapshot CRM;
  - venta guarda `id_cliente_crm=1`, nombre, codigo, identificador y origen `crm`;
  - ticket formal muestra `Cliente Express UAT`;
  - total/pago `285`, descuento `10`;
  - kardex `63`;
  - cierre de turno esperado/contado `785`, diferencia `0`;
  - reutilizacion de excepcion bloqueada.
- Decision:
  - contrato CRM/POS validado en venta real;
  - ya se puede avanzar a flujo UI productivo de excepciones comerciales o a alta express CRM desde POS.

## Avance UI excepcion comercial POS - 2026-06-29

- Estado: hardening frontend aplicado, sin escrituras de BD.
- POS ahora muestra una excepcion comercial validada como estado activo del cobro:
  - folio;
  - cliente CRM/POS snapshot;
  - descuento;
  - total autorizado.
- La excepcion activa queda ligada a la cuenta local en atencion para no mezclar clientes simultaneos.
- Totales y pagos rapidos consideran el total con excepcion mientras el folio siga vigente.
- La excepcion se invalida si cambia algo que afecte el cobro o trazabilidad:
  - agregar/quitar producto;
  - cambiar cantidad/peso;
  - cambiar modo stock/pieza/granel;
  - agregar/quitar/editar pagos;
  - vaciar carrito.
- Verificado:
  - JS sintacticamente correcto;
  - vista PHP sintacticamente correcta.
- Siguiente bloque recomendado:
  - convertir la excepcion comercial de modal tecnico a flujo POS productivo con solicitud, autorizacion supervisor, motivo obligatorio y evidencia en ticket.

## Avance UX productiva autorizacion POS - 2026-06-29

- Estado: aplicado en frontend, sin escrituras de BD.
- El panel lateral separa:
  - `Cliente/lista`;
  - `Autorizacion`.
- El modal queda organizado en tabs:
  - `Cliente`: identificador, lista/precio, alta rapida dry-run;
  - `Autorizacion`: precio manual/descuento con motivo y supervisor;
  - `Folio`: aplicar folio autorizado contra carrito/pagos.
- La autorizacion comercial ya no usa implicitamente el primer SKU:
  - POS llena `pos_excepcion_sku_objetivo` con las partidas del carrito;
  - precio manual y descuento por partida mandan el SKU seleccionado;
  - descuento general no manda SKU objetivo.
- UX de campos:
  - precio manual solo habilita precio;
  - descuento por partida/general habilita monto y porcentaje;
  - descuento general deshabilita partida.
- Asset POS: `20260629-excepcion3`.
- Verificado:
  - `node --check` sin errores;
  - `php -l` de la vista sin errores.
- Siguiente bloque recomendado:
  - prueba visual con sesion POS;
  - luego definir aplicador real de solicitud/autorizacion comercial desde UI, con supervisor, permiso y respaldo.

## Estado registro real autorizacion comercial POS UI - 2026-06-29

- Autorizacion recibida y aplicada.
- Endpoint productivo:
  - `/ventas/pos_excepcion_comercial_registrar_erp`.
- Permisos:
  - `ventas.operar`;
  - `ventas.autorizar_excepcion_comercial`.
- Seguridad:
  - POST con CSRF;
  - sesion requerida;
  - auditoria explicita.
- Alcance del endpoint:
  - registra folio `EXC-*`;
  - recalcula reglas en backend;
  - guarda snapshot;
  - no crea venta;
  - no mueve caja;
  - no descuenta inventario.
- POS:
  - boton `Registrar folio autorizado`;
  - al registrar folio, llena el campo de folio y abre tab `Folio`.
- Protecciones adicionales:
  - confirmacion previa al registro real;
  - boton deshabilitado durante el registro para evitar doble clic;
  - backend detecta duplicado reciente equivalente y reutiliza el folio existente.
- Asset POS:
  - `20260629-excepcion5`.
- Siguiente bloque recomendado:
  - UAT visual: registrar folio desde POS, aplicar folio contra carrito/pagos, validar panel de excepcion activa.
