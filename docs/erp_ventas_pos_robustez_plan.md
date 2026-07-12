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
  - Crea venta, pago, movimiento de caja, expediente pendiente y evento solo si existe turno abierto y pago completo.

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
- Pruebas read-only:
  - Bandeja almacén 5: `1` pendiente, folio `PINV-20260711-000001`, estatus `pendiente_revision`.
  - Consulta expediente: `1` evento, `0` existencias actuales.
  - Dry-run conteo fisico `0`, decision `cerrar_sin_ajuste`: cierra sin kardex.
  - Dry-run conteo fisico `5`, decision `ajustar_a_conteo`: propone ajuste `entrada` por `5`, referencia `PINV-RES-PINV-20260711-000001`, cerrar pendiente.
- Apply real preparado pero no ejecutado; requiere token `INVENTARIO_POS_PENDIENTE_RESOLVER_REAL` y respaldo vigente.

Siguiente autorizacion posterior para UAT real con caja abierta:

`AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS inventario pendiente"`

`AUTORIZO EJECUTAR UAT VENTA POS CON INVENTARIO PENDIENTE usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_REAL id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 pago=295 id_metodo_pago=1 motivo="UAT venta con inventario pendiente"`

Siguiente autorizacion para resolver pendiente desde Inventario:

`AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario=1 folio=PINV-20260711-000001 cantidad_fisica=5 decision=ajustar_a_conteo motivo="UAT conteo encuentra 5 piezas despues de venta POS pendiente"`
