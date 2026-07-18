# ERP Ventas/POS - Plan de robustez funcional

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: evaluacion y roadmap; no implica escrituras en BD.

## Objetivo

Definir que tan completo esta el POS ERP y que falta para considerarlo robusto, moderno y operativamente confiable.

## Evaluacion actual

### Ya preparado

- UI POS moderna con productos, imagenes, carrito, granel, piezas y pagos.
- Separacion de POS ERP contra legacy/ecommerce.
- Prevalidacion de inventario read-only.
- Disponibilidad por sucursal/almacen.
- Reglas de unidad cerrada y unidad abierta.
- Dry-run de venta, pedido/reserva, ticket y devolucion.
- Plan de caja, terminal, asignacion y turno.
- Aplicadores autorizados con guardrails para DDL, semillas, turno y venta UAT.
- Plantilla de evidencia UAT.
- Handoff de contexto vivo.

### Parcial

- Cliente en POS: existe captura express, pero falta cliente ERP real.
- Listas de precios: planeadas, no implementadas.
- Apartados/pedidos con abonos: contrato conceptual, falta esquema y flujo.
- Movimientos de caja no venta: existe tabla base, falta flujo formal de gastos, retiros, ingresos y autorizaciones.
- Devoluciones: dry-run, falta transaccion real y politica de destino.
- Reportes: tablero inicial, falta venta/margen/caja por sucursal.
- Permisos finos: deben revisarse antes de activar escritura.

### Pendiente critico

- Crear esquema BD autorizado.
- Asignacion oficial usuario/caja/terminal.
- Apertura/cierre de turno real.
- Venta real UAT con kardex.
- Post-venta por folio sin hallazgos.
- Clientes ERP.
- Resolutor backend de precios.
- Apartados con saldo y abonos.
- Politicas de cancelacion/devolucion/apartado.
- Politicas de gastos/retiros/ingresos de caja.

## Componentes de un POS robusto

### 1. Caja y turno

Debe soportar:

- apertura de turno;
- fondo inicial;
- ventas por turno;
- ingresos/retiros;
- gastos o pagos desde caja;
- caja chica;
- vales internos;
- cierre con contado;
- diferencias;
- auditoria;
- bloqueo de venta sin turno.

Estado actual:

- Disenado y con scripts UAT, pendiente de DDL/autorizacion.

### 1.1 Movimientos de caja no venta

Un POS formal debe controlar todo movimiento de dinero que entra o sale de caja, no solo ventas.

Tipos requeridos:

- `ingreso_venta`: cobro por venta.
- `ingreso_abono`: abono de pedido/apartado.
- `entrada_fondo`: fondo inicial o aumento de caja.
- `entrada_extraordinaria`: entrada manual documentada.
- `retiro_efectivo`: retiro parcial para resguardo.
- `gasto_caja`: compra menor pagada desde caja.
- `pago_proveedor_caja`: pago autorizado a proveedor desde caja.
- `vale_interno`: salida temporal con responsable.
- `devolucion_efectivo`: reembolso a cliente.
- `ajuste_corte`: diferencia documentada al cierre.

Reglas:

- Todo movimiento no venta debe estar ligado a turno, caja, usuario y sucursal.
- Debe tener motivo/categoria, importe, observacion y evidencia si aplica.
- Algunos movimientos deben requerir autorizacion de supervisor.
- Un gasto no debe confundirse con compra ERP ni con pago formal de cuentas por pagar; si es gasto menor, queda en caja chica; si es proveedor, debe poder relacionarse despues con Compras/Finanzas.
- Todo egreso afecta el monto esperado del corte.
- Nada se borra: se cancela con motivo y usuario.

Categorias sugeridas:

- limpieza;
- papeleria;
- mantenimiento menor;
- flete/mensajeria;
- comida/insumo operativo;
- emergencia;
- proveedor;
- retiro a resguardo;
- diferencia de caja;
- otro.

Evidencia:

- foto/comprobante opcional para gasto menor;
- referencia obligatoria si es transferencia o pago externo;
- responsable obligatorio si es vale.

Permisos sugeridos:

- `pos.movimiento_caja`
- `pos.gasto_caja`
- `pos.retiro_caja`
- `pos.autorizar_movimiento_caja`
- `pos.cancelar_movimiento_caja`

UX:

- Boton `Caja` dentro del POS.
- Panel con `Entrada`, `Retiro`, `Gasto`, `Vale`, `Corte`.
- Formulario rapido con importe, categoria, motivo y evidencia.
- Resumen del turno: fondo inicial, ventas efectivo, otros ingresos, retiros, gastos, esperado, contado, diferencia.

Modelo:

- La tabla base `erp_pos_movimientos_caja` sirve para el registro primario.
- Debe extenderse con categoria, estatus, autorizacion, evidencia y relacion opcional a proveedor/compra.
- Los reportes deben separar ventas de movimientos no venta para no inflar ingresos comerciales.

### 2. Inventario

Debe soportar:

- existencia por sucursal;
- unidad cerrada;
- unidad abierta/granel;
- reservas;
- kardex;
- trazabilidad por lote/ubicacion/unidad;
- bloqueo de stock insuficiente;
- no vender unidades abiertas como cerradas en ecommerce.

Estado actual:

- Prevalidacion y contrato listos; venta real pendiente.
### 2.1 Checador de precios read-only

Objetivo:

- Consultar precio, imagen y disponibilidad desde mostrador o celular sin abrir una venta.
- Escanear codigo de barras con camara cuando el navegador lo soporte.
- Mantener busqueda manual siempre disponible para escaner USB, SKU, etiqueta o nombre.

Reglas:

- No cobra.
- No crea carrito.
- No reserva.
- No descuenta inventario.
- No sustituye la validacion transaccional del POS al cobrar.
- Si la camara no funciona por navegador/HTTPS, la pantalla sigue siendo util con busqueda manual.

Estado 2026-07-10:

- Ruta activa del proyecto: `C:\xampp\htdocs\panel_de_control`.
- Se agrego ruta `Ventas::checador_precios`.
- Se agrego endpoint read-only `Ventas::pos_checador_precio_erp`.
- Se agrego vista `apps/erp/ventas/checador_precios`.
- El backend consulta Catalogo/Inventario/Ventas y devuelve contrato `no_cobra`, `no_reserva`, `no_mueve_inventario`.

### 3. Clientes

Debe soportar:

- publico general;
- busqueda express;
- alta rapida;
- ficha completa;
- duplicados/fusion;
- privacidad;
- historial.

Estado actual:

- Plan completo creado, pendiente de esquema.

Decision:

- POS debe soportar venta a publico general.
- POS debe permitir buscar/crear cliente rapido sin frenar la caja.
- Alta rapida minima: telefono y nombre visible; despues se completa la ficha.
- El cliente rapido no debe ser solo texto libre: debe poder fusionarse, depurarse y convertirse en cliente ERP formal.
- La venta debe guardar snapshot del cliente para conservar historial aunque la ficha cambie despues.

### 4. Precios

Debe soportar:

- lista general;
- lista por cliente;
- lista por segmento;
- lista por sucursal/canal;
- promociones;
- descuentos autorizados;
- snapshot en venta.

Estado actual:

- Planeado; falta resolutor backend y tablas.

Reglas adicionales:

- El precio final debe resolverlo backend, no JavaScript.
- Precio manual requiere permiso, motivo y snapshot.
- Descuento por partida y descuento general son reglas separadas.
- Descuentos altos deben requerir autorizacion de supervisor.
- Toda venta debe guardar origen del precio: lista, promocion, cliente, segmento, canal, sucursal o autorizacion manual.

Permisos sugeridos:

- `ventas.precio_manual`
- `ventas.descuento`
- `ventas.descuento_supervisor`
- `ventas.cancelar`
- `ventas.devolucion`

### 5. Apartados y pedidos

Debe soportar:

- pedido sin pago;
- apartado con anticipo;
- abonos parciales;
- saldo;
- vencimiento;
- reserva;
- entrega final;
- cancelacion/liberacion.

Estado actual:

- Dry-run de pedido/reserva existe; falta modelo robusto de abonos y politicas.

### 6. Pagos

Debe soportar:

- efectivo;
- tarjeta;
- transferencia;
- pagos mixtos;
- referencia obligatoria en transferencia;
- cambio;
- abonos;
- devoluciones/reembolsos.

Estado actual:

- UI y prevalidacion; escritura real pendiente.

Pendiente formal:

- pagos mixtos reales desde UI;
- cambio de efectivo visible;
- referencia obligatoria configurable por metodo;
- autorizacion bancaria para tarjeta;
- corte por metodo de pago;
- reimpresion de comprobante con permiso;
- conciliacion posterior.

### 7. Devoluciones/cancelaciones

Debe soportar:

- cancelacion antes de entrega;
- devolucion parcial;
- devolucion a cuarentena;
- reembolso;
- reversa de puntos/promociones;
- no borrar historial.

Estado actual:

- Dry-run; falta transaccion real.

### 8. Recompensas futuras

Debe soportar:

- puntos;
- monedero;
- cupones;
- niveles;
- reversas;
- reportes.

Estado actual:

- Se prepara arquitectura, no debe implementarse aun.

### 9. UX de alta velocidad

Debe soportar:

- busqueda/escaneo rapido;
- productos con imagen;
- carrito visible;
- varias cuentas/carritos abiertos en la misma terminal;
- peso directo para granel;
- pagos rapidos;
- mensajes claros;
- no depender de selects lentos;
- teclado/lector como flujo principal.

Estado actual:

- En iteracion; Playwright con maqueta valida layout actual.
- Se agregaron cuentas locales en POS para atender varios clientes sin mezclar partidas.

## Cuentas simultaneas en POS

Necesidad:

- En mostrador puede haber varios clientes tomando productos al mismo tiempo.
- El cajero necesita escanear productos y mantener cuentas separadas.
- El cliente puede preguntar cuanto lleva antes de cerrar venta o apartado.

Decision actual:

- Implementar `Cuentas en atencion` como carritos locales en el navegador/usuario.
- No escriben BD.
- No reservan inventario.
- No generan folio.
- No cobran ni descuentan.

Reglas:

- Cada cuenta tiene carrito, pagos temporales y datos express de cliente.
- Cambiar de cuenta cambia el carrito activo.
- Cerrar cuenta limpia solo ese carrito.
- Si se quiere que sobrevivan entre terminales, usuarios o reinicios, debe evolucionar a carritos persistentes autorizados.

Futuro robusto:

- `erp_pos_atenciones`
- `erp_pos_atenciones_detalle`
- `erp_pos_atenciones_pagos_temporales`

Estos registros futuros deberian ser borradores operativos, no ventas, y expirar por turno o cierre de caja.

## Atencion compartida entre vendedores y caja

Decision:

- Las cuentas locales son insuficientes para varios dispositivos o varios usuarios.
- El flujo formal debe ser `Atenciones/Pedidos de mostrador` persistentes.
- Cada vendedor puede iniciar una atencion desde celular, tablet o terminal secundaria.
- Caja/POS consulta atenciones abiertas, revisa partidas y cobra.

Modelo operativo:

1. Vendedor abre una atencion ligada a tienda y operador.
2. Agrega productos por escaner fisico, busqueda manual o camara.
3. La atencion queda en `borrador` o `lista_para_cobro`.
4. Caja ve la bandeja de atenciones abiertas por sucursal.
5. Caja toma una atencion, la bloquea temporalmente y la convierte en venta, pedido o apartado.
6. Si el cliente no compra, la atencion se cancela o expira sin mover inventario.

Reglas:

- Una atencion no descuenta inventario.
- Una atencion puede mostrar disponibilidad estimada, pero debe revalidarse al cobrar.
- Si se requiere prometer stock, debe convertirse a pedido/apartado con reserva formal.
- Cada atencion debe guardar operador origen y cajero que cobra.
- Caja debe poder fusionar, dividir o cancelar atenciones con auditoria.
- Las atenciones deben expirar por tiempo, cierre de turno o cancelacion manual.

Tablas propuestas:

- `erp_pos_atenciones`
- `erp_pos_atenciones_detalle`
- `erp_pos_atenciones_eventos`
- `erp_pos_atenciones_bloqueos`

Estados:

- `borrador`
- `lista_para_cobro`
- `tomada_por_caja`
- `convertida`
- `cancelada`
- `expirada`

UX recomendada:

- Pantalla `Atender cliente` para vendedores: simple, rapida, con total provisional.
- Pantalla `Bandeja de atenciones` en caja: vendedor, cliente, articulos, total estimado, antiguedad y accion `Cobrar`.
- En POS, boton visible `Atenciones` para traer cuentas abiertas.
- Si dos usuarios editan lo mismo, usar bloqueo optimista: mostrar quien la tiene abierta y permitir liberar con permiso.

Escaner con camara:

- Debe servir para vendedor movil y tambien para POS cuando no haya lector fisico.
- En caja, el lector USB tipo teclado sigue siendo el flujo principal por velocidad.
- La camara debe leer codigos contra `erp_catalogo_sku_codigos`, etiquetas internas y unidades fisicas.
- Si el codigo corresponde a unidad fisica, debe respetar si esta cerrada, abierta, vendida o bloqueada.

Prioridad:

1. Persistir atenciones/pedidos de mostrador.
2. Bandeja de atenciones en POS.
3. Captura movil simple con busqueda/lector.
4. Camara como mejora despues de estabilizar el flujo persistente.

## Ticket formal

El ticket debe ser un documento operativo formal.

Debe incluir:

- nombre comercial;
- sucursal/direccion;
- RFC si aplica;
- folio de venta;
- fecha/hora;
- caja;
- turno;
- cajero;
- cliente o publico general;
- SKU/codigo;
- descripcion;
- cantidad;
- unidad;
- precio unitario;
- descuento;
- subtotal;
- impuestos si aplica;
- total;
- pagos por metodo;
- cambio;
- politica de garantia/devolucion;
- codigo QR o codigo de barras del folio.

Pendiente:

- plantilla HTML ticket 58/80 mm;
- vista previa;
- impresion navegador;
- reimpresion con permiso;
- registro de reimpresiones;
- ticket de apartado/abono;
- ticket de devolucion.

## Garantias

La garantia debe iniciar en Catalogo, aplicarse en Ventas y consultarse en postventa.

Catalogo define:

- si el SKU tiene garantia;
- tipo de garantia;
- plazo;
- cobertura: cambio, reparacion, proveedor, tienda o sin garantia;
- requisitos: ticket, empaque, serie, lote, fotos o diagnostico;
- exclusiones.

Ventas guarda snapshot:

- politica de garantia vigente al momento de venta;
- fecha inicio;
- fecha vencimiento;
- numero de serie/unidad si aplica;
- folio de venta;
- cliente si existe.

Postventa debe soportar:

- consulta por folio/ticket/SKU/cliente;
- reclamo de garantia;
- resolucion: cambio, reparacion, rechazo, devolucion o nota;
- evidencia adjunta;
- trazabilidad a inventario si entra producto defectuoso.

Modulos involucrados:

- Catalogo: politicas por SKU/categoria/marca.
- Ventas: snapshot y ticket.
- Inventario/Almacen: devolucion, cuarentena, merma o reingreso.
- Proveedores: garantia con proveedor cuando aplique.
- Reportes: garantias por producto/proveedor/sucursal.

## Roadmap recomendado

### Etapa A - Cerrar POS base

1. Autorizar DDL y semillas POS.
2. Configurar cajas/terminales/asignaciones.
3. Abrir turno UAT.
4. Ejecutar venta real UAT.
5. Validar post-venta por folio.
6. Ajustar UI con evidencia real.

Estado 2026-06-27:

- DDL base aplicado.
- Semillas POS aplicadas.
- Turno UAT abierto.
- Primera venta real UAT ejecutada y validada por folio.
- Pendiente UI real con Playwright y cierre de turno.

### Etapa B - Caja completa

1. Cierre de turno real con contado.
2. Movimientos de caja no venta: ingresos, retiros, gastos, vales y reembolsos.
3. Categorias y permisos por tipo de movimiento.
4. Evidencia/adjuntos para gastos.
5. Corte por metodo de pago.
6. UAT: venta + gasto + retiro + cierre con diferencia cero.

### Etapa C - Atenciones compartidas

1. Crear esquema de atenciones persistentes.
2. Crear endpoints para abrir/agregar/quitar/cancelar/tomar atencion.
3. Crear bandeja de atenciones en POS.
4. Crear pantalla simple de vendedor.
5. Convertir atencion a venta/pedido/apartado.
6. UAT multiusuario: vendedor A agrega, vendedor B consulta, caja cobra.

### Etapa D - Clientes

1. Auditar clientes legacy/ecommerce.
2. Crear esquema Clientes ERP.
3. Alta rapida desde POS.
4. Ficha completa fuera de POS.
5. Duplicados/fusion.
6. UAT por telefono/codigo/correo.

### Etapa E - Listas de precios

1. Crear listas y vigencias.
2. Resolver precio backend.
3. Mostrar lista/precio en POS.
4. Guardar snapshot.
5. Permisos de excepcion/precio manual.

### Etapa F - Apartados y abonos

1. Definir politica de apartado.
2. Extender esquema de ventas para saldos/eventos.
3. Crear preflight de apartado con anticipo.
4. Crear abono autorizado.
5. Crear entrega final.
6. UAT con reserva, abono, liquidacion y cancelacion.

### Etapa G - Ticket y postventa

1. Crear plantilla ticket 58/80 mm.
2. Vista previa y reimpresion controlada.
3. Politicas de garantia desde Catalogo.
4. Snapshot de garantia en venta.
5. Consulta por folio/QR para devolucion o garantia.

### Etapa H - Promociones y recompensas

1. Promociones simples.
2. Cupones.
3. Puntos/monedero.
4. Reversion por devolucion.
5. Reportes.

## Definicion de "POS robusto listo"

El POS se considera robusto cuando:

- vende con caja/turno;
- registra ingresos, retiros, gastos y vales sin perder corte;
- descuenta inventario con kardex;
- soporta granel y piezas sin ambiguedad;
- maneja pagos mixtos;
- soporta pedidos/apartados con saldo;
- identifica cliente sin frenar caja;
- aplica precios desde backend;
- conserva auditoria y snapshots;
- puede validar una venta completa por folio;
- tiene UAT documentado por SKU, folio, caja, turno y cliente.


## Venta POS con inventario pendiente controlado

Estado 2026-07-12: DDL base y DDL de politicas aplicado en UAT POS; politica UAT `PINV-UAT-A5-S1760-POS` sembrada para almacen 5 / SKU 1760 con limite 1 unidad y $295; dry-run funcional preparado para calcular faltante sin escribir ventas, pendientes, notificaciones ni kardex.

Objetivo operativo:

- Permitir que caja venda un SKU aunque el ERP todavia no tenga existencia validada en esa sucursal, solo cuando la politica POS lo autorice.
- Registrar el faltante como pendiente formal para Inventario/Existencias.
- Generar trazabilidad por venta, detalle, SKU, almacen, cajero y folio.
- Mantener separado el cobro real de la correccion de inventario.
- Resolver el pendiente con mini inventario: conteo fisico, ajuste autorizado y cierre de alerta.

Reglas:

- No se debe usar para ecommerce como disponibilidad vendible.
- No debe tomar unidades abiertas como unidad cerrada.
- No reemplaza kardex: si existe stock, se descuenta con kardex normal; si falta stock, se registra pendiente y se genera alerta operativa.
- Flujo mixto 2026-07-12: si `cantidad_cubierta > 0` y `cantidad_pendiente > 0`, la parte cubierta debe descontarse en la venta con kardex y trazabilidad normal; solo el faltante debe quedar como expediente pendiente para Inventario/Existencias.
- El pendiente debe mostrar cantidad vendida, cantidad cubierta y cantidad faltante.
- La resolucion pertenece a Inventario/Existencias, no a caja.
- El ajuste posterior debe quedar ligado al pendiente y no borrar la evidencia de venta original.
- Para permitir inventario pendiente en POS manda la politica activa de `erp_pos_politicas_venta_inventario` por sucursal/SKU/canal. Las banderas globales del SKU quedan como referencia informativa para no activar faltantes en ecommerce por accidente.

DDL aplicado:

- Columnas de estado en `erp_ventas`, `erp_ventas_detalle` y `erp_ventas_detalle_inventario`.
- Tabla `erp_pos_politicas_venta_inventario` para autorizar inventario pendiente por POS/sucursal/SKU/canal.
- Tabla `erp_pos_inventario_pendientes` para el expediente del faltante.
- Tabla `erp_pos_inventario_pendientes_eventos` para historial operativo.
- Endpoints de esquema:
  - `/ventas/esquema_auditar_inventario_pendiente_pos`
  - `/ventas/esquema_actualizar_inventario_pendiente_pos`
- Endpoint dry-run funcional:
  - `/ventas/pos_inventario_pendiente_dryrun_erp`
  - `/ventas/esquema_auditar_politicas_inventario_pendiente_pos`
  - `/ventas/esquema_actualizar_politicas_inventario_pendiente_pos`
- Endpoint protegido preparado y probado para sembrar politica UAT por sucursal/SKU/canal:
  - `/ventas/pos_politica_inventario_pendiente_guardar_erp`
- Endpoint real preparado para UAT de venta con inventario pendiente:
  - `/ventas/pos_inventario_pendiente_real_erp`
  - Crea venta, pago, movimiento de caja, expediente pendiente, evento y notificacion global para Inventario solo si existe turno abierto y pago completo.

Evidencia UAT politica sembrada:

- `id_politica_inventario_pos=1`
- `codigo=PINV-UAT-A5-S1760-POS`
- `id_almacen=5`
- `id_sku_erp=1760`
- `canal=pos`
- `cantidad_maxima_pendiente=1`
- `monto_maximo=295`
- `estatus=activa`
- Dry-run posterior: `estado=pendiente_autorizable`, `bloqueos=[]`, `politica_id=1`.
- Intento de UAT real 2026-07-12: bloqueado correctamente sin escrituras por `turno_abierto_pendiente`; contadores posteriores `ventas_pendientes_inv=0`, `pendientes=0`, `eventos=0`.
- UAT real 2026-07-11 ejecutada con turno `TUR-20260711-002-001`:
  - Venta `POS-20260711-000001`, `id_venta=18`, total `$295`, estatus `pagada`, `inventario_validacion_estado=pendiente_inventario`.
  - Detalle `id_venta_detalle=19`, SKU `1760`, cantidad pendiente `1`, `id_inventario_pendiente=1`.
  - Pendiente `PINV-20260711-000001`, estatus `pendiente_revision`, cantidad cubierta `0`, cantidad pendiente `1`.
  - Evento `creacion_pos`, referencia `POS-20260711-000001`.
  - Pago efectivo `id_venta_pago=23`, movimiento caja `id_movimiento_caja=42`, monto `$295`, ligado a `id_venta=18`.
  - Turno esperado paso de `$500` a `$795`.
  - No se genero `id_movimiento_inventario`; la salida quedo como `tipo_asignacion=inventario_pendiente`.
  - Cierre de turno ejecutado: `TUR-20260711-002-001`, monto esperado `$795`, contado `$795`, diferencia `$0`, cerrado por `id_usuario=1`.

Resolucion Inventario/Existencias preparada:

- Endpoints Inventario:
  - `/inventario/pos_pendientes_inventario_erp`
  - `/inventario/pos_pendiente_inventario_consultar_erp`
  - `/inventario/pos_pendiente_inventario_resolucion_dryrun_erp`
  - `/inventario/pos_pendiente_inventario_resolver_erp`
- UI Inventario/Existencias:
  - Pestaña `Pendientes POS` agregada para consultar pendientes, estado, venta relacionada, cantidades y expediente.
  - Modal de expediente muestra producto, almacen, conteo/resolucion, existencias actuales y eventos.
  - Modal permite simular resolucion con cantidad fisica, decision y motivo usando dry-run; no escribe BD ni mueve inventario.
  - Despues de un dry-run exitoso, la UI puede preparar resolucion real con token, respaldo, texto `RESOLVER PENDIENTE` y confirmacion; el backend revalida permisos y contrato antes de escribir.
  - Hash directo soportado: `/inventario/productos_existencias#pendientes-pos`.
- Notificaciones:
  - Al crear una venta POS con inventario pendiente se registra/actualiza notificacion `pos_venta_inventario_pendiente` para area `inventario`, permiso `inventario.ver`, URL `/inventario/productos_existencias#pendientes-pos`.
  - Al resolver el pendiente desde Inventario/Existencias se cierra la notificacion por huella `pos_inventario_pendiente|ID`.
- Flujo mixto preparado en codigo:
  - `VentasErp::ventaInventarioPendienteDryRun` marca el caso mixto como `pendiente_autorizable` si la politica cubre solo el faltante.
  - `VentasErp::ventaInventarioPendienteReal` usa `aplicarSalidaInventarioPosReal` para descontar `cantidad_cubierta` con kardex y trazabilidad `erp_ventas_detalle_inventario`.
  - La misma venta crea expediente `erp_pos_inventario_pendientes` solo por `cantidad_pendiente`.
  - El detalle queda con `modo_salida=mixto_kardex_pendiente_pos` e `inventario_estado=pendiente_inventario_parcial`.
  - `InventarioErp::resolverPendientePosInventarioDryRun` calcula el conteo como inventario fisico actual post-venta, suma la cantidad pendiente vendida para obtener la existencia objetivo antes de salida y propone ajuste preventa + salida de venta pendiente.
  - `InventarioErp::resolverPendientePosInventarioReal` crea el ajuste preventa cuando aplique y despues registra kardex de salida `venta_pos` para la cantidad pendiente vendida; la trazabilidad del detalle pendiente apunta al movimiento de salida.
  - Script UAT protegido preparado: `storage/uat/uat_ventas_pos_inventario_pendiente_apply_authorized.php`.
  - Guardrail del script validado sin parametros: queda bloqueado, no escribe BD.
- Preflight read-only:
  - Script: `storage/uat/uat_pos_inventario_pendiente_notificaciones_readonly.php`.
  - Script complementario de saldos/estatus: `storage/uat/uat_inventario_estatus_existencias_readonly.php`.
  - Script preparado, no ejecutado, para normalizacion autorizada: `storage/uat/uat_inventario_estatus_existencias_apply_authorized.php`.
  - Resultado 2026-07-12: `ok=true`; tablas/columnas requeridas presentes; politica `PINV-UAT-A5-S1760-POS` activa; no hay pendientes abiertos; no hay notificaciones POS pendientes.
  - Aviso tecnico: existencia `EXI-1016-34` tiene `cantidad_disponible=5` pero `estatus_existencia=agotada`; normalizar antes o durante la siguiente UAT visual para evitar confusion operativa.
  - Auditoria estatus 2026-07-12: `total_inconsistencias=1`; `disponible_marcado_agotado=1`; muestra `EXI-1016-34`, almacen `5`, SKU `1760`, cantidad `5`, disponible `5`, estatus `agotada`.
  - Apply autorizado 2026-07-12 con token `INVENTARIO_ESTATUS_EXISTENCIAS_NORMALIZAR`: `EXI-1016-34` paso de `agotada` a `disponible`, manteniendo cantidad `5`, disponible `5`, apartado `0`; no creo kardex ni modifico cantidades.
  - Auditoria posterior 2026-07-12: `total_inconsistencias=0` para almacen `5` / SKU `1760`.
- Hallazgo `POS-PINV-H001`: antes del guardrail, una venta con existencia parcial podia calcular `cantidad_cubierta` y `cantidad_pendiente`, pero el flujo real de pendiente no descontaba con kardex la parte cubierta. Se bloqueo el caso mixto en dry-run y real hasta implementar el descuento mixto completo.
- Resolucion `POS-PINV-H001`: flujo mixto preparado en backend; pendiente UAT real. Dry-run 2026-07-12 con almacen `5`, SKU `1760`, cantidad `5`: disponible `4`, cubierta `4`, pendiente `1`, estado `pendiente_autorizable`, bloqueos `[]`.
- Hallazgo `POS-PINV-H002`: la resolucion anterior del pendiente puro ajustaba stock al conteo fisico actual, pero no generaba la salida/kardex de la cantidad pendiente vendida. Se corrigio prospectivamente para que la resolucion cree ajuste preventa y salida `venta_pos` pendiente; la UAT historica `PINV-20260711-000001` queda documentada como anterior a esta mejora.
- Prueba negativa 2026-07-12: dry-run mixto con cantidad `6` queda `bloqueado` porque el faltante `2` supera politica `cantidad_maxima_pendiente=1` y `monto_maximo=295`.

Siguiente UAT real mixta sugerida:

`AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS inventario pendiente mixto"`

`AUTORIZO EJECUTAR UAT VENTA POS CON INVENTARIO PENDIENTE MIXTO usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_REAL id_usuario=1 id_almacen=5 id_sku=1760 cantidad=5 pago=1475 id_metodo_pago=1 motivo="UAT venta mixta POS: 4 con kardex y 1 pendiente inventario" cliente="Cliente UAT POS pendiente mixto"`

Validaciones esperadas despues de venta mixta:

- Venta nueva `POS-...` pagada por `$1475`.
- Kardex de salida por cantidad cubierta `4`, existencia `EXI-1016-34` de `4` a `0`.
- Expediente `PINV-...` por cantidad pendiente `1`.
- Notificacion `pos_venta_inventario_pendiente` abierta para Inventario/Existencias.
- Turno esperado aumenta `$1475`.
- La resolucion posterior del pendiente debe usar conteo fisico actual post-venta y crear ajuste preventa + salida `venta_pos` pendiente.
- Pruebas read-only:
  - Bandeja almacén 5: `1` pendiente, folio `PINV-20260711-000001`, estatus `pendiente_revision`.
  - Consulta expediente: `1` evento, `0` existencias actuales.
  - Dry-run conteo fisico `0`, decision `cerrar_sin_ajuste`: cierra sin kardex.
  - Dry-run conteo fisico `5`, decision `ajustar_a_conteo`: propone ajuste `entrada` por `5`, referencia `PINV-RES-PINV-20260711-000001`, cerrar pendiente.
- Apply real UAT ejecutado:
  - Pendiente `PINV-20260711-000001` resuelto.
  - Conteo fisico validado `5`.
  - Movimiento kardex `86`, tipo `entrada`, referencia `PINV-RES-PINV-20260711-000001`.
  - Venta `POS-20260711-000001` marcada como `validado_post_venta`.

Siguiente autorizacion posterior para UAT real con caja abierta:

`AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS inventario pendiente"`

`AUTORIZO EJECUTAR UAT VENTA POS CON INVENTARIO PENDIENTE usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_REAL id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 pago=295 id_metodo_pago=1 motivo="UAT venta con inventario pendiente"`

Siguiente autorizacion para repetir ciclo de pendiente desde Inventario con nuevo folio:

`AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario=1 folio=PINV-NUEVO cantidad_fisica=CONTEO decision=ajustar_a_conteo confirmacion="RESOLVER PENDIENTE" motivo="UAT conteo fisico posterior a venta POS pendiente"`

Siguiente autorizacion para normalizar solo estatus de existencias:

`AUTORIZO NORMALIZAR ESTATUS EXISTENCIAS INVENTARIO UAT usando respaldo UAT POS vigente con token INVENTARIO_ESTATUS_EXISTENCIAS_NORMALIZAR confirmacion="NORMALIZAR ESTATUS" para corregir existencias con saldo disponible y estatus agotada sin mover kardex ni modificar cantidades`

Siguiente tarea UI/UAT:

- Generar un nuevo pendiente abierto, probar desde Inventario/Existencias el expediente, dry-run y resolucion real con token operativo.
- Validar que la notificacion global se crea al vender con inventario pendiente y se resuelve al cerrar el pendiente.
- Revisar normalizacion de `estatus_existencia` cuando un ajuste posterior devuelve disponibilidad a una existencia previamente agotada.

## Readiness POS normal post normalizacion

Evidencia 2026-07-12:

- Script: `storage/uat/uat_ventas_pos_cobro_ui_readiness_readonly.php`.
- Parametros: usuario `1`, almacen asignado `5`, caja `2`, SKU `1760`, cantidad `1`, precio/pago `$295`, cliente `3312345678`.
- Resultado read-only: no escribio ventas, pagos, movimientos caja, kardex, existencias ni garantias; conteos antes/despues iguales.
- Esquema requerido presente y endpoint real `/ventas/pos_confirmar_erp` expuesto.
- Usuario `1` conserva permiso `ventas.operar`.
- Stock actual suficiente: SKU `1760` tiene `5` disponibles despues de normalizar estatus.
- Bloqueo actual: no hay turno abierto para la caja asignada; `id_turno_caja=0`.

Siguiente autorizacion para venta POS normal con kardex:

`AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS venta normal post normalizacion"`

`AUTORIZO EJECUTAR COBRO REAL POS UI UAT usando respaldo UAT POS vigente con token VENTAS_POS_COBRO_UI_REAL id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295 cliente="Cliente UAT POS normal post normalizacion"`

Evidencia UAT real 2026-07-12:

- Apertura autorizada:
  - Turno `TUR-20260712-002-001`, `id_turno_caja=21`.
  - Caja `2`, almacen `5`, usuario `1`.
  - Movimiento caja inicial `43`, monto `$500`.
- Readiness previo con turno abierto:
  - `ok=true`, bloqueos `[]`.
  - No escribio BD; conteos antes/despues iguales.
- Cobro real POS UI:
  - Venta `POS-20260712-000001`, `id_venta=19`, estatus `pagada`.
  - Cliente publico `Cliente UAT POS normal post normalizacion`, identificador `3312345678`.
  - Total `$295`, pagado `$295`, saldo `$0`.
  - Detalle `id_venta_detalle=20`, SKU `1760`, cantidad `1`, lista `Lista UAT POS`.
  - Pago `id_venta_pago=24`, movimiento caja `44`, metodo `Efectivo`, referencia `UAT-POS-UI`.
  - Kardex salida `id_movimiento_inventario=87`, existencia `EXI-1016-34` de `5` a `4`.
  - Trazabilidad `erp_ventas_detalle_inventario.id_venta_detalle_inventario=19`.
  - Snapshot garantia `id_venta_detalle_garantia=12`, resumen `Sin garantia`.
- Verificaciones read-only posteriores:
  - Post-venta `ok=true`, hallazgos `[]`.
  - Auditoria estatus existencias: `total_inconsistencias=0`.
  - Ticket formal read-only: `ok=true`, `28` lineas, hallazgos `[]`.
  - Turno esperado `$795`, movimientos caja `2` (`500` inicial + `295` venta), turno sigue abierto.

Siguiente autorizacion para cerrar este turno:

`AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS venta normal post normalizacion POS-20260712-000001"`

Cierre UAT real 2026-07-12:

- Preflight cierre:
  - Turno `TUR-20260712-002-001`, monto esperado `$795`, contado `$795`, diferencia `$0`.
  - Bloqueos `[]`; no escribio BD.
- Cierre aplicado:
  - `id_turno_caja=21`, estatus `cerrado`.
  - `id_usuario_cierre=1`.
  - `monto_inicial=$500`, `monto_esperado=$795`, `monto_contado=$795`, `diferencia=$0`.
  - Ventas `1`, total `$295`, pagado `$295`, saldo `$0`.
  - Movimientos caja:
    - `43` entrada monto inicial `$500`.
    - `44` ingreso venta POS `$295`.
- Verificaciones posteriores:
  - Post-cierre `ok=true`, hallazgos `[]`.
  - Asignacion usuario/caja sigue activa, pero `turno_abierto=null`.
  - Readiness de nueva venta bloquea correctamente por falta de turno abierto y no escribe BD.
  - Post-venta `POS-20260712-000001` sigue `ok=true`, hallazgos `[]`.

## UAT POS inventario pendiente mixto

Evidencia 2026-07-12:

- Apertura autorizada:
  - Turno `TUR-20260712-002-002`, `id_turno_caja=22`.
  - Caja `2`, almacen `5`, usuario `1`.
  - Movimiento inicial `45`, monto `$500`.
- Preflight venta mixta:
  - SKU `1760`, almacen `5`, cantidad solicitada `5`.
  - Disponible ERP antes de venta: `4`.
  - Cantidad cubierta con kardex: `4`.
  - Cantidad pendiente de inventario: `1`.
  - Politica activa `PINV-UAT-A5-S1760-POS`, maximo pendiente `1`, monto maximo `$295`.
  - Estado `pendiente_autorizable`, bloqueos `[]`.
- Hallazgo tecnico corregido:
  - `POS-PINV-H003`: registrar notificacion con una nueva instancia de modelo durante una transaccion POS podia cerrar la transaccion por PDO persistente.
  - Correccion: `registrarNotificacionInventarioPendientePos()` inserta/actualiza `erp_notificaciones` con la misma conexion transaccional y liga `id_notificacion` al expediente pendiente.
  - Se agrego guardia `asegurarTransaccionPosReal()` para detectar perdida de transaccion con paso diagnostico.
- Venta mixta real:
  - Venta `POS-20260712-000002`, `id_venta=23`, estatus `pagada`.
  - Total `$1475`, pagado `$1475`, saldo `$0`.
  - Detalle `id_venta_detalle=24`, SKU `1760`, cantidad `5`, modo `mixto_kardex_pendiente_pos`.
  - Kardex cubierto `id_movimiento_inventario=91`, existencia `EXI-1016-34` de `4` a `0`.
  - Pendiente `PINV-20260712-000001`, `id_inventario_pendiente=5`, cantidad pendiente `1`, estatus `pendiente_revision`.
  - Notificacion operativa `id_notificacion=18`, tipo `pos_venta_inventario_pendiente`, estatus `pendiente`.
  - Pago `id_venta_pago=27`, movimiento caja real `48`, metodo `Efectivo`, monto `$1475`.
- Validador post-venta:
  - Cuadre detalle/pagos correcto.
  - Trazabilidad mixta correcta: una fila `existencia` con kardex y una fila `inventario_pendiente` por validar.
  - Hallazgo restante `VENTAS-POST-009`: sin snapshot de garantia para SKU; corresponde a cierre del modulo Garantias/Catalogo, no bloquea inventario pendiente.
- Caja pendiente de correccion:
  - Los intentos fallidos previos dejaron movimientos caja huerfanos `46` y `47`, ambos `venta_pos` por `$1475`, ligados a ventas inexistentes `20` y `21`.
  - El movimiento real valido es `48`, ligado a `POS-20260712-000002`.
  - Se preparo `storage/uat/uat_pos_caja_orfanos_corregir_apply_authorized.php`, bloqueado por token, para cancelar solo esos huerfanos y recalcular turno excluyendo venta_pos sin venta valida.

Siguiente autorizacion para corregir caja huerfana del turno abierto:

`AUTORIZO CORREGIR CAJA ORFANA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_ORFANOS_CORREGIR id_usuario=1 id_turno_caja=22 ids_movimiento_caja=46,47 confirmacion="CORREGIR CAJA ORFANA" motivo="UAT limpiar movimientos caja huerfanos por intento fallido de venta mixta"`

Correccion aplicada 2026-07-13:

- Script: `storage/uat/uat_pos_caja_orfanos_corregir_apply_authorized.php`.
- Movimientos cancelados:
  - `46`, ingreso venta POS `$1475`, ligado a venta inexistente `20`.
  - `47`, ingreso venta POS `$1475`, ligado a venta inexistente `21`.
- Movimiento valido conservado:
  - `48`, ingreso venta POS `$1475`, ligado a venta `23` / `POS-20260712-000002`.
- Turno `TUR-20260712-002-002` recalculado:
  - Monto inicial `$500`.
  - Monto esperado `$1975`.
  - Estatus `abierto`.
- Verificacion read-only:
  - Venta `POS-20260712-000002` sigue `pagada`, total/pagado `$1475`.
  - Pendiente `PINV-20260712-000001` sigue `pendiente_revision`.
  - Existencia `EXI-1016-34` sigue en `0` disponible, ultimo kardex `91`.
  - Post-venta solo conserva hallazgo `VENTAS-POST-009` por garantia pendiente de catalogo/garantias.

Despues de corregir caja:

- Resolver `PINV-20260712-000001` desde Inventario/Existencias con conteo fisico.
- Si el conteo fisico actual confirma `0` piezas, la resolucion debe crear ajuste de entrada a existencia teorica previa y salida de venta pendiente, dejando final `0`.
- Cerrar turno `TUR-20260712-002-002` con monto contado esperado `$1975` si no hay otros movimientos.

Resolucion aplicada 2026-07-13:

- Script: `storage/uat/uat_inventario_pos_pendiente_resolver_apply_authorized.php`.
- Pendiente resuelto:
  - `PINV-20260712-000001`, `id_inventario_pendiente=5`.
  - Conteo fisico actual `0`.
  - Decision `ajustar_a_conteo`.
  - Motivo `UAT conteo fisico post venta mixta confirma cero piezas`.
- Dry-run previo:
  - Disponible ERP actual `0`.
  - Pendiente vendido `1`.
  - Existencia objetivo antes de salida `1`.
  - Ajuste requerido: entrada `1`.
  - Salida venta pendiente requerida: `1`.
  - Disponible final estimado `0`.
- Apply real:
  - Ajuste entrada `id_movimiento_ajuste=92`.
  - Salida venta pendiente `id_movimiento_salida_pendiente=93`.
  - Notificacion `18` resuelta.
  - Pendiente `PINV-20260712-000001` estatus `resuelto`.
  - Venta `POS-20260712-000002` inventario `validado_post_venta`.
  - Detalle `24` inventario `validado_post_venta`.
  - Trazabilidad pendiente `erp_ventas_detalle_inventario.id_movimiento_inventario=93`, estatus `validado_post_venta`.
  - Existencia `EXI-1016-34` queda `0` disponible, ultimo movimiento `93`.
- Verificacion read-only:
  - Pendientes abiertos `0`.
  - Notificaciones abiertas `0`, resueltas `1`.
  - Turno `TUR-20260712-002-002` sigue abierto con monto esperado `$1975`.
  - Post-venta conserva solo `VENTAS-POST-009` por garantia pendiente de catalogo/garantias.

Siguiente autorizacion para cerrar turno de venta mixta:

`AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=1975 observaciones="Cierre UAT POS venta mixta inventario pendiente POS-20260712-000002"`

Cierre aplicado 2026-07-13:

- Script: `storage/uat/uat_ventas_pos_turno_cierre_apply_authorized.php`.
- Turno cerrado:
  - `TUR-20260712-002-002`, `id_turno_caja=22`.
  - Monto inicial `$500`.
  - Venta real `POS-20260712-000002` por `$1475`.
  - Monto esperado `$1975`.
  - Monto contado `$1975`.
  - Diferencia `$0`.
  - Estatus `cerrado`.
- Verificacion post-cierre:
  - Ventas validas `1`, total `$1475`.
  - Pagos validos `1`, total `$1475`.
  - Movimientos caja validos `2`, total esperado `$1975`:
    - `45` apertura `$500`.
    - `48` ingreso venta POS `$1475`.
  - Movimientos caja excluidos por correccion:
    - `46` cancelado, venta inexistente `20`.
    - `47` cancelado, venta inexistente `21`.
  - Hallazgos `[]`.
  - Asignacion POS sigue activa, pero no queda turno abierto.
- Correccion de calculo/reportes:
  - `VentasErp::cierreTurnoDryRun()` ahora cuenta pagos solo si tienen venta valida.
  - `VentasErp::cierreTurnoDryRun()` ahora cuenta movimientos caja solo con estatus `registrado/aprobado` y no cuenta `venta_pos` sin venta valida.
  - `storage/uat/uat_ventas_pos_turno_post_cierre_readonly.php` separa movimientos validos de excluidos para evidencia de auditoria.

## Snapshot de garantia en venta POS pendiente/mixta

Hallazgo 2026-07-13:

- La venta normal POS ya guardaba snapshot de garantia.
- La venta POS con inventario pendiente/mixto no guardaba snapshot de garantia.
- El post-venta de `POS-20260712-000002` reportaba `VENTAS-POST-009` porque `id_venta_detalle=24` no tenia registro en `erp_ventas_detalle_garantias`.
- El resolutor de garantias para SKU `1760` devuelve:
  - tipo `sin_garantia`;
  - resumen ticket `Sin garantia`;
  - alerta `politica_no_configurada`.

Correccion prospectiva:

- `VentasErp::ventaInventarioPendienteReal()` ahora llama `GarantiasErp::guardarSnapshotsVenta()` despues de insertar el detalle y antes de pagos/commit.
- El resultado de venta pendiente/mixta devuelve `garantias` en depuracion igual que el cobro POS normal.
- Se agrego guardia de transaccion posterior a `garantia_snapshot_insertado`.

Auditoria read-only historica:

- Script: `storage/uat/uat_ventas_pos_garantia_snapshot_backfill_readonly.php`.
- Folio auditado: `POS-20260712-000002`.
- Total faltantes: `1`.
- Detalle faltante: `id_venta_detalle=24`, SKU `1760`.
- Snapshot sugerido: `Sin garantia`.

Backfill preparado, no ejecutado:

- Script: `storage/uat/uat_ventas_pos_garantia_snapshot_backfill_apply_authorized.php`.
- Guardrail validado: bloquea sin token, respaldo, usuario, folio, confirmacion y motivo.

Siguiente autorizacion para completar snapshot historico de garantia:

`AUTORIZO BACKFILL SNAPSHOT GARANTIA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_GARANTIA_SNAPSHOT_BACKFILL id_usuario=1 folio=POS-20260712-000002 confirmacion="BACKFILL GARANTIA POS" motivo="UAT completar snapshot garantia historico POS"`

Backfill aplicado 2026-07-13:

- Script: `storage/uat/uat_ventas_pos_garantia_snapshot_backfill_apply_authorized.php`.
- Folio `POS-20260712-000002`.
- Snapshot creado:
  - `id_venta_detalle_garantia=13`.
  - `id_venta_detalle=24`.
  - SKU `1760`.
  - Tipo `sin_garantia`.
  - Resumen ticket `Sin garantia`.
- Verificaciones read-only:
  - `storage/uat/uat_ventas_pos_post_venta_readonly.php --folio=POS-20260712-000002`: `ok=true`, hallazgos `[]`, garantias `1`.
  - `storage/uat/uat_ventas_pos_garantia_snapshot_backfill_readonly.php --folio=POS-20260712-000002`: faltantes `0`, existentes `1`.
  - `storage/uat/uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260712-000002`: `ok=true`, ticket `28` lineas, hallazgos `[]`, muestra `Garantia: Sin garantia`.

## UAT final read-only POS venta mixta

Evidencia 2026-07-13:

- Turno/caja:
  - Script: `storage/uat/uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=22`.
  - Turno `TUR-20260712-002-002` cerrado.
  - Monto inicial `$500`, esperado `$1975`, contado `$1975`, diferencia `$0`.
  - Ventas validas `1`, total `$1475`.
  - Pagos validos `1`, total `$1475`.
  - Movimientos caja validos `2`, total `$1975`.
  - Movimientos excluidos visibles para auditoria: `46` y `47`, ambos cancelados.
  - Hallazgos `[]`.
  - Asignacion POS activa, sin turno abierto.
- Pendientes/notificaciones:
  - Script: `storage/uat/uat_pos_inventario_pendiente_notificaciones_readonly.php`.
  - Pendientes abiertos `0`.
  - Pendientes resueltos `2`.
  - Notificaciones abiertas `0`.
  - Notificacion `18` resuelta.
  - Stock SKU `1760` almacen `5`: `0` disponible, sin inconsistencias.
- Post-venta:
  - Script: `storage/uat/uat_ventas_pos_post_venta_readonly.php --folio=POS-20260712-000002`.
  - `ok=true`, hallazgos `[]`.
  - Detalle `24` con inventario `validado_post_venta`.
  - Garantia snapshot `13`, resumen `Sin garantia`.
  - Trazabilidad inventario `2`: salida cubierta `91` y salida pendiente validada `93`.
- Ticket:
  - Script: `storage/uat/uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260712-000002`.
  - `ok=true`, hallazgos `[]`.
  - Ticket `28` lineas.
  - Muestra tienda, caja, turno, cliente, lista de precio, garantia `Sin garantia`, pago y trazabilidad.

Dictamen:

- Flujo POS venta mixta con inventario pendiente queda validado end-to-end en UAT.
- Contratos cubiertos: caja/turno, pago, kardex parcial, pendiente de inventario, notificacion, resolucion de pendiente, cierre de caja, garantia snapshot y ticket.
- Pendiente no bloqueante: limpiar/estandarizar scripts UAT antiguos que aun calculan por patrones previos si se usan fuera del flujo actual.

## UI POS para inventario pendiente

Avance 2026-07-13:

- Se agrego en `/ventas/pos` una accion visible `Inventario pendiente`.
- La accion llama `/ventas/pos_inventario_pendiente_dryrun_erp` y no escribe BD.
- La prevalidacion normal ahora muestra boton contextual `Revisar inventario pendiente` cuando el backend detecta que existe politica POS para permitir faltante, pero que el cobro normal debe pasar por flujo especializado.
- La UI muestra:
  - estado del flujo (`normal`, `pendiente_autorizable` o `bloqueado`);
  - disponible actual;
  - cantidad cubierta con kardex;
  - cantidad pendiente para Inventario/Existencias;
  - politica POS aplicable;
  - total estimado y advertencias.
- Restricciones intencionales:
  - solo valida una partida por vez;
  - solo aplica a salida por existencia agregada/stock;
  - no convierte unidades fisicas cerradas o abiertas en pendiente;
  - no ejecuta cobro real ni crea alerta desde UI sin autorizacion operacional.

Pendiente de decision/productivo:

- Definir si el cobro real con inventario pendiente queda como:
  - accion de supervisor dentro de POS con permiso fino y motivo obligatorio; o
  - flujo administrativo separado hasta que Inventario/Existencias tenga tablero de mini inventarios completamente operativo.
- Para productivo, el endpoint real no debe depender de token UAT; debe usar permiso granular, politica vigente, confirmacion UI, auditoria y evidencia en reportes.

## Readiness Productivo POS

Avance 2026-07-13:

- Se agrego auditor read-only `storage/uat/uat_ventas_pos_productivo_readiness_readonly.php`.
- El auditor revisa sin escribir BD:
  - tablas base de ventas, caja, inventario, garantias, CRM y listas;
  - permisos esperados y permisos del usuario auditado;
  - asignacion POS usuario/tienda/caja/terminal;
  - turno abierto/cerrado;
  - politicas de inventario pendiente;
  - pendientes y notificaciones POS;
  - ventas recientes;
  - dry-run de inventario pendiente.
- Ejecucion UAT:
  - Comando: `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_productivo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1`.
  - Resultado: `ok=true`.
  - Bloqueos: `[]`.
  - Avisos:
    - falta sembrar permiso fino `ventas.pos.inventario_pendiente.autorizar`;
    - no hay turno abierto para el usuario, esperado fuera de horario y bloqueante solo para cobro real.
- Estado detectado:
  - usuario `1` tiene asignacion POS activa a almacen `5`, caja `2`, terminal `2`;
  - no hay turnos abiertos;
  - ultimo turno auditado `TUR-20260712-002-002` cerrado con diferencia `0`;
  - no hay pendientes de inventario POS abiertos;
  - notificacion de pendiente POS resuelta;
  - politica de inventario pendiente activa para almacen `5`, SKU `1760`, canal `pos`;
  - dry-run de inventario pendiente para cantidad `1` queda `pendiente_autorizable`.

Dictamen productivo:

- POS base esta listo para pruebas operativas con turno abierto.
- Inventario pendiente productivo NO debe liberarse con token UAT.
- Siguiente cambio fuerte recomendado:
  - sembrar permiso `ventas.pos.inventario_pendiente.autorizar`;
  - crear endpoint real productivo con `ventas.operar` + permiso supervisor;
  - exigir confirmacion UI, motivo obligatorio y politica activa;
  - conservar no-ecommerce y alerta a Inventario/Existencias.

Preparacion sin ejecucion:

- Se preparo `storage/uat/uat_ventas_pos_inventario_pendiente_permiso_apply_authorized.php`.
- El script esta bloqueado por defecto y requiere:
  - token `VENTAS_POS_INVENTARIO_PENDIENTE_PERMISO`;
  - respaldo externo o referencia valida;
  - `id_usuario`.
- Validacion realizada:
  - `php -l` sin errores;
  - ejecucion sin parametros queda bloqueada correctamente.

Siguiente autorizacion robusta sugerida:

`AUTORIZO SEMBRAR PERMISO INVENTARIO PENDIENTE POS PRODUCTIVO usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_PERMISO id_usuario=1 para UAT POS`

## Contrato productivo para venta POS con inventario pendiente

Fecha: 2026-07-13

Objetivo:

- Permitir ventas controladas cuando la tienda fisicamente puede vender, pero el ERP aun no tiene existencia formal suficiente.
- Crear una alerta accionable para Inventario/Existencias y resolverla con mini inventario.
- Evitar que esta excepcion se convierta en venta negativa libre o en atajo para ecommerce.

Estado actual:

- UI POS ya tiene dry-run en `/ventas/pos`.
- Backend ya puede simular con `/ventas/pos_inventario_pendiente_dryrun_erp`.
- Flujo real UAT existe protegido por token/respaldo.
- Falta version productiva sin token UAT.

Reglas obligatorias del endpoint productivo:

- Requiere sesion vigente.
- Requiere turno POS abierto.
- Requiere asignacion activa de usuario a tienda/almacen/caja/terminal.
- Requiere permiso base `ventas.operar`.
- Requiere permiso supervisor `ventas.pos.inventario_pendiente.autorizar`.
- Requiere politica activa por almacen, SKU y canal `pos`.
- Requiere motivo obligatorio escrito por operador/supervisor.
- Requiere confirmacion explicita desde UI, por ejemplo `AUTORIZAR INVENTARIO PENDIENTE`.
- Debe registrar auditoria explicita.
- Debe generar folio de venta, pago, movimiento de caja y ticket como venta POS formal.
- Debe descontar con kardex la parte cubierta por inventario disponible.
- Debe crear expediente `erp_pos_inventario_pendientes` solo por la cantidad faltante.
- Debe crear notificacion persistente a Inventario/Existencias.
- Debe dejar trazabilidad por detalle: cantidad cubierta, cantidad pendiente y politica aplicada.
- Debe respetar garantia snapshot.

Reglas de bloqueo:

- No aplica a ecommerce.
- No aplica a unidad fisica cerrada: si no hay unidad disponible/cerrada, bloquea.
- No aplica a unidad abierta vendida como unidad cerrada.
- No aplica a granel si el SKU no permite granel.
- No permite saltar limites de cantidad o monto de la politica.
- No permite operar sin turno abierto.
- No permite operar sin caja asignada.
- No permite pagos incompletos.
- No permite generar movimientos de caja antes de confirmar toda la transaccion.

Contrato UX:

- El operador primero usa `Prevalidar` o `Inventario pendiente`.
- La UI debe mostrar disponible, cubierto con kardex, faltante, total y politica.
- Si el resultado es autorizable, la accion real debe verse como excepcion supervisada, no como boton normal de cobro.
- El motivo debe capturarse antes de cobrar.
- Despues del cobro, el ticket y detalle deben indicar que hubo validacion pendiente de inventario.
- La alerta debe llevar a Inventario/Existencias, no a auditoria generica.

Prueba minima posterior a autorizacion:

1. Sembrar permiso fino.
2. Abrir turno POS.
3. Ejecutar venta normal para confirmar que no se rompio el cobro base.
4. Ejecutar dry-run de inventario pendiente.
5. Ejecutar venta real con faltante dentro de politica.
6. Confirmar caja, ticket, kardex parcial, expediente pendiente y notificacion.
7. Resolver pendiente desde Inventario/Existencias con conteo fisico.
8. Cerrar turno con diferencia cero o diferencia documentada.

Autorizacion fuerte pendiente:

```text
AUTORIZO SEMBRAR PERMISO INVENTARIO PENDIENTE POS PRODUCTIVO usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_PERMISO id_usuario=1 para UAT POS
```

Autorizacion posterior a esa, todavia no solicitada:

```text
AUTORIZO PREPARAR ENDPOINT PRODUCTIVO VENTA INVENTARIO PENDIENTE POS usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_PRODUCTIVO_ENDPOINT para UAT POS/Inventario
```

## Readiness Endpoint Productivo Inventario Pendiente

Fecha: 2026-07-18

Se agrego `storage/uat/uat_ventas_pos_inventario_pendiente_endpoint_productivo_readiness.php` para auditar el pase de UAT/token a flujo productivo.

Resultado vigente:

- Permiso `ventas.pos.inventario_pendiente.autorizar` ya esta sembrado.
- Usuario `1` tiene `ventas.operar`, `ventas.pos.inventario_pendiente.autorizar` e `inventario.ver`.
- Politica POS activa para almacen `5`, SKU `1760`, canal `pos`.
- El controlador conserva el endpoint real como UAT protegido por `sistema.soporte` y token.
- El modelo real transaccional ya existe y debe reutilizarse.
- UI POS no cobra inventario pendiente; solo hace dry-run y muestra venta real protegida.
- Dry-run de SKU `1760` cantidad `1` queda `pendiente_autorizable`.
- Bloqueos `[]`.

Dictamen:

- Ya no falta permiso.
- Endpoint productivo separado ya fue preparado con permiso, confirmacion, motivo obligatorio y auditoria.
- Falta UAT real controlada con turno abierto para validar venta, caja, expediente pendiente y notificacion desde la UI/endpoint productivo.

Validaciones posteriores:

- `php -l app/controladores/Ventas.php`: sin errores.
- `node --check public/assets/js/custom/apps/erp/ventas/pos.js`: sin errores.
- `uat_ventas_pos_inventario_pendiente_endpoint_productivo_readiness.php`: `ok=true`.
- `uat_ventas_pos_productivo_readiness_readonly.php`: `ok=true`.

Autorizacion siguiente para prueba real:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT endpoint productivo inventario pendiente POS"

AUTORIZO EJECUTAR UAT PRODUCTIVA VENTA INVENTARIO PENDIENTE POS usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_PRODUCTIVO_REAL id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 pago=295 motivo="UAT endpoint productivo inventario pendiente POS" confirmacion="AUTORIZAR INVENTARIO PENDIENTE" para UAT POS/Inventario
```

## Avance 2026-07-18 - endpoint productivo inventario pendiente validado en UAT

Estado:

- Endpoint productivo preparado en `Ventas::pos_inventario_pendiente_cobrar_erp`.
- UI POS preparada para mostrar autorizacion supervisada despues de dry-run autorizable.
- UAT real ejecutada con venta `POS-20260717-000002`.
- Caja ligada a turno `TUR-20260717-002-002`.
- Pendiente inventario creado `PINV-20260717-000001`.
- Notificacion creada `id_notificacion=19`.
- Ticket formal read-only generado sin hallazgos.
- Garantia snapshot creada como `Sin garantia`.

Pendiente antes de cerrar este ciclo:

- Cerrar turno `TUR-20260717-002-002` con monto contado esperado `$795.00`.
- Resolver `PINV-20260717-000001` desde Inventario/Existencias con conteo fisico autorizado.
- Revisar que reportes/cortes no dependan de `erp_pos_movimientos_caja.id_venta` cuando el enlace formal existe por `erp_ventas_pagos.id_movimiento_caja`.

Siguiente bloque recomendable despues del cierre:

- UAT de endpoint productivo desde navegador con sesion real y CSRF, no solo aplicador CLI.
- Prueba UX: dry-run visible, motivo, confirmacion exacta y cobro.
- Prueba negativa: sin permiso, sin motivo o confirmacion incorrecta debe bloquear.
- Prueba de resolucion inventario pendiente y cierre de notificacion.
- Reporte operativo: ventas normales, ventas con inventario pendiente, pendientes abiertos, pendientes resueltos y diferencia de caja por turno.

## Avance 2026-07-18 - apertura/cierre manual desde Caja/Turnos

Decision:

- Apertura y cierre de turno pertenecen a `/ventas/caja_turnos`, no al mostrador POS.
- POS debe mostrar si hay turno abierto, pero no debe mezclar administracion de caja con cobro.
- Abrir/cerrar desde UI requiere dry-run previo y confirmacion escrita para evitar errores de operador.

Implementado:

- Apertura real:
  - modelo `VentasErp::abrirTurnoRealPos`;
  - endpoint `/ventas/turno_apertura_real_erp`;
  - UI en `/ventas/caja_turnos` despues de validar apertura;
  - confirmacion `ABRIR TURNO`;
  - crea turno y movimiento inicial;
  - bloquea doble turno abierto.
- Cierre real:
  - ya existia `VentasErp::cerrarTurnoRealPos`;
  - endpoint `/ventas/turno_cierre_real_erp`;
  - UI despues de validar corte;
  - confirmacion `CERRAR TURNO`;
  - permite diferencia y la deja trazable para reportes.

Pendiente:

- UAT visual completa desde navegador:
  - abrir turno real;
  - vender;
  - cerrar turno real;
  - revisar corte imprimible;
  - confirmar que POS bloquea cobro si no hay turno abierto.
- Resolver pendiente `PINV-20260717-000001` para dejar inventario/notificacion sin abiertos antes de piloto.

Readiness agregado:

- Script `storage/uat/uat_ventas_pos_caja_turnos_ui_readiness_readonly.php`.
- Valida sin escribir:
  - endpoint de apertura real;
  - endpoint de cierre real;
  - modelo transaccional;
  - UI de confirmacion;
  - asignacion oficial;
  - bloqueo por falta de `ABRIR TURNO`;
  - bloqueo por falta de `CERRAR TURNO`.
- Ultimo resultado: `ok=true`, folio sugerido `TUR-20260718-002-001`, turnos abiertos `0`.

## Revalidacion Readiness POS

Fecha: 2026-07-13

Comando ejecutado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_productivo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1
```

Resultado:

- `ok=true`.
- Read-only confirmado.
- Tablas base de ventas, caja, inventario, CRM, saldos cliente y listas de precios presentes.
- Usuario `1` con asignacion POS activa:
  - almacen `5`;
  - caja `2`;
  - terminal `2`;
  - tienda `Mascotas Mina 971`.
- Turnos abiertos: `0`.
- Ultimo turno auditado: `TUR-20260712-002-002`, cerrado con diferencia `0`.
- Pendientes POS abiertos: `0`.
- Pendientes POS resueltos: `2`.
- Notificaciones POS abiertas: `0`.
- Politica activa de inventario pendiente:
  - `PINV-UAT-A5-S1760-POS`;
  - cantidad maxima pendiente `1`;
  - monto maximo `$295`;
  - permiso requerido `ventas.pos.inventario_pendiente.autorizar`.
- Dry-run SKU `1760`, almacen `5`, cantidad `1`:
  - estado `pendiente_autorizable`;
  - disponible `0`;
  - cubierta con kardex `0`;
  - pendiente propuesta `1`;
  - total estimado `$295`.

Avisos actuales:

- Falta sembrar permiso fino `ventas.pos.inventario_pendiente.autorizar`.
- No hay turno abierto, esperado fuera de operacion y bloqueante solo para cobro real.

Dictamen:

- POS base queda listo para pruebas operativas con turno abierto.
- Inventario pendiente productivo sigue detenido correctamente hasta permiso fino y endpoint real sin token UAT.
