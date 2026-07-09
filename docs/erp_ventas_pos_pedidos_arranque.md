# ERP Ventas/POS/Pedidos - Arranque y plan rector

Documentacion IA: Codex GPT-5
Fecha: 2026-06-26
Estado: auditoria inicial y diseno rector previo a implementacion
Relacionados: `AGENTS.md`, `docs/erp_plan_maestro_fundamentos.md`, `docs/erp_ux_operativa.md`, `docs/erp_inventario_existencias_arranque.md`, `docs/erp_almacen_unidades_fisicas_arranque.md`, `docs/erp_inventario_existencias_unidades_abiertas_handoff.md`

## Decision de arquitectura

Ventas/POS/Pedidos debe construirse como modulo ERP nuevo, no como continuidad de las tablas legacy de ecommerce.

El dueno del proyecto autorizo libertad para limpiar, modificar, completar o crear tablas necesarias porque los registros actuales no son operacion real definitiva. Aun asi, cualquier escritura en BD requiere respaldo externo, plan de reversa y autorizacion explicita antes de ejecutarse.

Regla:

- Las tablas `ecom_*` pueden servir como referencia historica o fuente de migracion controlada.
- El nuevo POS no debe depender de `ecom_pedidos`, `ecom_pedidos_productos` ni `ecom_productos.existencia` para operar.
- La fuente de productos vendibles debe ser Catalogo ERP.
- La fuente de stock debe ser Inventario ERP.
- La salida de stock debe quedar explicada en kardex, reservas, unidad fisica y folio comercial.

## Objetivo

Disenar e implementar un POS moderno, rapido e intuitivo para tienda fisica, capaz de ser usado por personal operativo no necesariamente joven, sin perder robustez ERP.

El modulo debe permitir:

- vender productos con imagen, codigo de barras, SKU, busqueda por texto y lectura de etiqueta;
- atender varias cuentas/carritos al mismo tiempo en una terminal sin mezclar partidas;
- cobrar con uno o varios metodos de pago;
- registrar movimientos de caja no venta: gastos, retiros, entradas, vales y reembolsos;
- emitir ticket;
- consumir inventario correctamente;
- vender unidades cerradas completas;
- vender a granel desde unidad abierta solo cuando el SKU lo permita;
- apartar/reservar desde pedidos;
- consumir reservas al completar venta o entrega;
- conservar trazabilidad por folio, SKU, existencia, lote, unidad fisica y movimiento de inventario;
- preparar posteriormente integracion ecommerce sin mezclar canales.

## Auditoria actual resumida

### Hallazgos

| ID | Hallazgo | Impacto | Decision |
| --- | --- | --- | --- |
| VENTAS-H001 | `Ventas.php` y `Venta.php` usan flujo ecommerce legacy. | Alto | No extender como base del ERP nuevo. |
| VENTAS-H002 | Las partidas guardan `id_producto` legacy, no `id_sku_erp`. | Alto | Nuevo esquema debe guardar SKU ERP. |
| VENTAS-H003 | No hay descuento de `erp_inventario_existencias`. | Critico | Crear servicio transaccional de consumo de inventario. |
| VENTAS-H004 | No hay movimiento kardex por venta. | Critico | Cada salida debe insertar `erp_inventario_movimientos`. |
| VENTAS-H005 | No se usan unidades fisicas cerradas/abiertas. | Critico | Venta debe validar `erp_inventario_unidades`. |
| VENTAS-H006 | No se consumen reservas. | Alto | Pedidos deben crear/consumir/liberar reservas. |
| VENTAS-H007 | Busqueda actual usa `ecom_productos`. | Alto | Busqueda POS debe usar Catalogo ERP + Inventario ERP. |
| VENTAS-H008 | Actualizar venta legacy borra productos/pagos. | Alto | ERP debe cancelar/reversar logicamente, no borrar historia. |
| VENTAS-H009 | No existe familia de tablas `erp_ventas_*` o `erp_pos_*`. | Alto | Crear esquema nuevo. |
| VENTAS-H010 | `TP-40372` tiene unidad abierta disponible, pero Catalogo indica `permite_venta_fraccionaria=0`. | Alto para POS granel | Bloquear venta granel hasta corregir regla en Catalogo con respaldo/autorizacion. |

## Fronteras por modulo

### Catalogo ERP

Define:

- SKU vendible;
- nombre e imagen;
- unidad base;
- precio/lista/canal;
- impuestos;
- si controla inventario;
- si permite venta fraccionaria;
- precision decimal;
- incremento minimo de venta;
- si requiere escaneo o etiqueta;
- si el SKU esta activo para POS.

Catalogo no descuenta inventario y no decide de que lote sale.

### Inventario ERP

Define:

- existencia disponible por SKU/almacen/lote/ubicacion;
- cantidad apartada;
- unidades fisicas cerradas y abiertas;
- reservas;
- kardex;
- trazabilidad.

Inventario no decide precio, cliente, cobro ni canal.

### Ventas/POS/Pedidos

Define:

- documento comercial;
- canal de venta;
- sucursal/caja/cajero;
- cliente si aplica;
- partidas;
- pagos;
- descuentos autorizados;
- consumo de reservas;
- salida de inventario;
- ticket;
- movimientos de caja no venta;
- cancelacion/devolucion.

Ventas es el unico modulo que debe convertir una intencion comercial en salida de inventario por venta.

### Caja chica y movimientos no venta

El POS debe controlar todo movimiento de dinero del turno, aunque no sea venta.

Tipos minimos:

- fondo inicial;
- ingreso extraordinario;
- retiro de efectivo;
- gasto de caja;
- vale interno;
- pago menor a proveedor;
- reembolso/devolucion;
- ajuste de corte.

Reglas:

- Siempre ligado a caja, turno, sucursal y usuario.
- Debe afectar el monto esperado del corte.
- Debe separarse de ingresos por ventas en reportes.
- Debe tener categoria, motivo y autorizacion cuando aplique.
- No debe reemplazar Compras/Finanzas; si corresponde a proveedor o cuenta por pagar, debe poder relacionarse despues.
- No se borra fisicamente; se cancela con auditoria.

## Modelo operativo recomendado

### Canales

Usar canal como dato obligatorio:

- `pos`: venta inmediata en tienda.
- `pedido_tienda`: pedido o apartado capturado por personal.
- `ecommerce`: reservado para integracion futura.
- `mayoreo`: futuro si requiere listas, aprobaciones o entregas.

### Estados de venta

Estados recomendados para encabezado:

- `borrador`: carrito activo, no afecta inventario.
- `reservada`: pedido con stock apartado.
- `pagada`: cobro completo, aun puede estar pendiente de entrega.
- `entregada`: producto entregado y stock consumido.
- `cancelada`: documento cancelado sin efecto vigente.
- `devolucion_parcial`: tiene devoluciones ligadas.
- `devuelta`: todas las partidas fueron devueltas.

Para POS rapido, el flujo normal puede saltar de `borrador` a `pagada/entregada` en una sola confirmacion transaccional.

### Cuentas en atencion POS

Necesidad operativa:

- En mostrador pueden convivir varios clientes tomando productos.
- El cajero puede escanear productos de un cliente, cambiar a otro y regresar al primero.
- Cada cliente puede preguntar cuanto lleva sin cerrar venta, pedido o apartado.

Decision actual:

- `Cuentas en atencion` vive como carritos locales por navegador y usuario ERP.
- Cambiar de cuenta cambia carrito, pagos temporales y datos express de cliente.
- Cerrar una cuenta limpia solo ese carrito.
- Cambiar de almacen reinicia cuentas locales para evitar mezclar sucursales.
- No escribe BD, no reserva inventario, no genera folio y no descuenta existencias.

Alcance:

- Misma terminal/navegador y mismo usuario: conserva cuentas locales en `localStorage`.
- Mismo navegador con otro usuario ERP: usa otra llave local.
- Otro navegador/equipo: no comparte cuentas.

Si el negocio necesita continuidad entre equipos, usuarios o reinicios, evolucionar a borradores persistentes autorizados:

- `erp_pos_atenciones`
- `erp_pos_atenciones_detalle`
- `erp_pos_atenciones_pagos_temporales`

Esos borradores no deben ser ventas ni pedidos confirmados. Deben estar ligados a tienda/caja/turno/operador, expirar por cierre de turno y convertirse formalmente en venta, pedido o apartado solo al confirmar.

Decision 2026-06-27:

- El negocio si requiere continuidad entre vendedores, dispositivos y caja.
- El flujo objetivo es `Atenciones/Pedidos de mostrador` persistentes.
- Vendedores podran levantar cuentas desde dispositivos secundarios y caja las cobrara desde POS.
- La camara puede usarse como lector en dispositivos moviles, pero lector USB sigue siendo recomendado para caja.
- Una atencion persistente no descuenta inventario; solo se descuenta al cobrar venta o al confirmar pedido/apartado con reserva.
- Caja debe poder tomar, bloquear, fusionar, dividir, cancelar o convertir una atencion con auditoria.

### Estados de partida

- `borrador`
- `reservada`
- `vendida`
- `entregada`
- `cancelada`
- `devuelta_parcial`
- `devuelta`

### Regla de inventario por venta

1. Si el SKU no controla inventario, la partida no genera salida.
2. Si controla inventario, debe existir stock disponible o reserva activa.
3. Si se vende unidad cerrada:
   - requiere unidad fisica `estatus='disponible'`;
   - requiere `estado_fisico='cerrada'`;
   - descuenta todo `cantidad_base_disponible`;
   - marca unidad como `vendida`.
4. Si se vende a granel desde unidad abierta:
   - solo POS;
   - SKU debe permitir venta fraccionaria;
   - cantidad debe respetar precision e incremento minimo;
   - unidad debe estar `estado_fisico='abierta'` o `cerrada` si se abre para vender parcial;
   - descuenta cantidad base;
   - si queda saldo, unidad queda `abierta`;
   - si llega a cero, queda `vendida` o `agotada` segun politica final.
5. Si se vende desde existencia agregada sin unidad fisica:
   - permitido solo si el SKU no requiere unidad trazable;
   - debe quedar lote/caducidad/ubicacion en partida y movimiento.
6. Toda salida debe generar movimiento en `erp_inventario_movimientos` con:
   - `origen_tipo='venta_pos'` o `origen_tipo='pedido_venta'`;
   - `origen_id`;
   - `id_existencia_inventario`;
   - referencia/folio de venta;
   - existencia anterior/nueva.

### Regla de reservas

Pedidos y apartados deben usar `erp_inventario_reservas`.

Flujo:

1. Pedido confirma apartar stock.
2. Se crea reserva por existencia/lote/unidad cuando aplique.
3. La reserva baja `cantidad_disponible` y sube `cantidad_apartada`.
4. Al pagar/entregar, la venta consume la reserva:
   - baja `cantidad`;
   - baja `cantidad_apartada`;
   - actualiza `cantidad_consumida`;
   - cambia reserva a `consumida` si queda sin pendiente.
5. Al cancelar, se libera reserva:
   - baja `cantidad_apartada`;
   - sube `cantidad_disponible`;
   - reserva queda `liberada` o `cancelada`.

Falta en Inventario actual:

- metodo transaccional `consumirReserva()` para que Ventas no lo haga con SQL duplicado.

## Esquema propuesto

No aplicar sin respaldo externo y autorizacion.

### `erp_ventas`

Encabezado comercial.

Campos recomendados:

- `id_venta`
- `folio`
- `canal`
- `tipo_documento`: `venta`, `pedido`, `apartado`, `cotizacion`
- `estatus`
- `id_almacen`
- `id_caja`
- `id_turno_caja`
- `id_cliente`
- `cliente_nombre_publico`
- `moneda`
- `subtotal`
- `descuento_total`
- `impuestos_total`
- `total`
- `pagado_total`
- `saldo_total`
- `fecha_venta`
- `fecha_entrega_compromiso`
- `creado_por`
- `cancelado_por`
- `motivo_cancelacion`
- `fecha_cancelacion`

Indices:

- `folio` unico.
- `(canal, estatus, fecha_venta)`.
- `(id_almacen, fecha_venta)`.
- `(id_cliente, fecha_venta)`.

### `erp_ventas_detalle`

Partidas.

Campos recomendados:

- `id_venta_detalle`
- `id_venta`
- `renglon`
- `id_producto_erp`
- `id_sku_erp`
- `sku`
- `descripcion`
- `tipo_partida`: `producto`, `servicio`, `cargo`, `descuento`
- `controla_inventario`
- `modo_salida`: `unidad_cerrada`, `granel_unidad_abierta`, `existencia_agregada`, `sin_inventario`
- `cantidad_venta`
- `unidad_venta`
- `cantidad_base`
- `unidad_base`
- `precio_unitario`
- `precio_unitario_sin_impuesto`
- `descuento`
- `impuestos`
- `subtotal`
- `total`
- `estatus`

Indices:

- `(id_venta)`.
- `(id_sku_erp, estatus)`.

### `erp_ventas_detalle_inventario`

Asignacion exacta de inventario por partida. Una partida puede consumir varias existencias si se autoriza FEFO multi-lote.

Campos recomendados:

- `id_venta_detalle_inventario`
- `id_venta`
- `id_venta_detalle`
- `id_existencia_inventario`
- `id_inventario_unidad`
- `id_reserva_inventario`
- `id_movimiento_inventario`
- `id_almacen`
- `lote`
- `fecha_caducidad`
- `ubicacion_id`
- `cantidad_base`
- `cantidad_unidad_antes`
- `cantidad_unidad_despues`
- `estado_unidad_despues`
- `estatus`: `asignada`, `reservada`, `consumida`, `liberada`, `cancelada`, `devuelta`

### `erp_ventas_pagos`

Pagos de venta.

Campos recomendados:

- `id_venta_pago`
- `id_venta`
- `id_metodo_pago`
- `metodo_pago`
- `monto`
- `moneda`
- `referencia`
- `autorizacion`
- `estatus`: `registrado`, `aplicado`, `cancelado`, `devuelto`
- `fecha_pago`
- `creado_por`
- `cancelado_por`
- `motivo_cancelacion`

No borrar pagos; cancelar logicamente.

### `erp_pos_cajas`

Cajas fisicas/logicas.

Campos recomendados:

- `id_caja`
- `codigo`
- `nombre`
- `id_almacen`
- `estatus`
- `permite_efectivo`
- `permite_tarjeta`
- `permite_transferencia`

### `erp_pos_turnos`

Turnos o cortes de caja.

Campos recomendados:

- `id_turno_caja`
- `folio`
- `id_caja`
- `id_almacen`
- `id_usuario_apertura`
- `id_usuario_cierre`
- `monto_inicial`
- `monto_esperado`
- `monto_contado`
- `diferencia`
- `estatus`: `abierto`, `cerrado`, `cancelado`
- `fecha_apertura`
- `fecha_cierre`

### `erp_pos_movimientos_caja`

Entradas/salidas de efectivo no venta.

Campos recomendados:

- `id_movimiento_caja`
- `id_turno_caja`
- `tipo`: `entrada`, `salida`
- `motivo`
- `monto`
- `referencia`
- `creado_por`
- `fecha_registro`

### `erp_ventas_devoluciones`

Encabezado de devolucion.

Campos recomendados:

- `id_devolucion`
- `folio`
- `id_venta`
- `tipo`: `devolucion`, `cambio`, `garantia`
- `estatus`
- `motivo`
- `creado_por`
- `fecha_registro`

### `erp_ventas_devoluciones_detalle`

Detalle de devolucion.

Debe referenciar:

- partida original;
- movimiento original;
- unidad fisica original;
- cantidad devuelta;
- decision de inventario: regresa a disponible, cuarentena, merma o garantia.

## Servicio de inventario para Ventas

Crear en el modelo de dominio de Ventas o en un servicio dedicado:

- `consultarDisponibilidadVenta($idSku, $idAlmacen, $canal)`
- `preasignarInventarioVenta($partidas, $modo)`
- `reservarPedido($idVenta)`
- `consumirVentaPos($idVenta)`
- `consumirReservaVenta($idVenta)`
- `liberarReservaVenta($idVenta)`
- `reversarSalidaVenta($idVenta, $motivo)`

Regla tecnica:

- Todo consumo debe ejecutarse dentro de transaccion.
- Usar `FOR UPDATE` sobre existencias, reservas y unidades afectadas.
- No usar saldos calculados en frontend como verdad.
- El frontend solo propone; backend decide y bloquea.

## UX/UI POS recomendado

### Principios

- Primera pantalla debe ser el POS real, no una pagina informativa.
- Debe poder usarse con lector de codigo de barras.
- Debe ser rapido para mouse, teclado y tactil.
- Texto grande y contrastado para operador no joven.
- Acciones frecuentes siempre visibles.
- Evitar tablas densas en el carrito principal; usar filas claras.
- Imagen del producto cuando exista.
- Si no hay imagen, usar un placeholder sobrio con inicial/SKU.

### Layout desktop/tablet

1. Barra superior:
   - sucursal/almacen;
   - caja/turno;
   - usuario;
   - estado de conexion/sesion;
   - boton corte.
2. Busqueda grande:
   - foco automatico;
   - escaneo por codigo;
   - busqueda por nombre/SKU;
   - tecla Enter agrega si hay coincidencia exacta.
3. Panel de resultados:
   - tarjetas compactas con imagen real;
   - nombre;
   - SKU;
   - precio;
   - disponible;
   - badges: granel, lote, unidad cerrada, abierto, sin stock.
4. Carrito:
   - partidas grandes;
   - imagen pequena;
   - cantidad con stepper para enteros;
   - input decimal con unidad visible para granel;
   - boton quitar;
   - subtotal por renglon.
5. Resumen fijo:
   - subtotal;
   - descuentos;
   - impuestos;
   - total grande;
   - cobrado;
   - cambio;
   - botones `Cobrar`, `Apartar`, `Cancelar`.

### Modal de cobro

Debe permitir:

- efectivo con calculo de cambio;
- tarjeta;
- transferencia;
- pago mixto;
- referencia/autorizacion;
- monto restante visible;
- confirmacion final.

El boton final debe decir claramente:

- `Cobrar y entregar`
- `Cobrar y reservar`
- `Guardar pedido`

### Flujo granel

Cuando el SKU permite venta fraccionaria:

- mostrar unidad de venta visible: kg, l, m, pza;
- mostrar incremento minimo;
- permitir cantidad decimal segun precision;
- si viene de unidad abierta, mostrar codigo de unidad y contenido disponible;
- advertir si intenta vender como unidad cerrada una unidad abierta.

### Flujo unidad cerrada

Cuando el SKU tiene unidades fisicas:

- al escanear etiqueta exacta, agregar esa unidad;
- mostrar `Unidad cerrada`;
- bloquear edicion parcial de cantidad;
- si la unidad esta abierta, ofrecer venta granel solo si el SKU lo permite.

### Accesibilidad operativa

- Botones principales altos y anchos.
- Iconos mas texto para acciones criticas.
- Contraste fuerte para total/cambio.
- Mensajes cortos: que paso y que hacer.
- Evitar depender solo de color.
- Confirmaciones para cancelar venta, devolver, abrir caja y cerrar turno.

## Endpoints recomendados

Controlador nuevo sugerido: `VentaErp.php` o renovar `Ventas.php` separando endpoints legacy.

Rutas:

- `/ventas/pos`
- `/ventas/pos_catalogos_erp`
- `/ventas/pos_buscar_skus_erp`
- `/ventas/pos_disponibilidad_erp`
- `/ventas/pos_carrito_prevalidar_erp`
- `/ventas/pos_cobrar_erp`
- `/ventas/pedidos`
- `/ventas/pedido_guardar_erp`
- `/ventas/pedido_reservar_erp`
- `/ventas/pedido_cancelar_erp`
- `/ventas/pedido_entregar_erp`
- `/ventas/ticket_erp`
- `/ventas/caja_abrir_erp`
- `/ventas/caja_movimiento_erp`
- `/ventas/caja_cerrar_erp`

Permisos iniciales:

- `ventas.ver`
- `ventas.operar`
- `ventas.cancelar`
- `ventas.devolver`
- `ventas.descuentos`
- `ventas.caja`
- `ventas.reportes`

No agregar permisos sin revisar `SeguridadEsquema.php` antes de implementar.

## UAT propuesto

| ID | Caso | Evidencia |
| --- | --- | --- |
| VENTAS-UAT-001 | Abrir POS con turno activo. | Caja, usuario, almacen visible. |
| VENTAS-UAT-002 | Buscar SKU con imagen y agregar entero. | Partida, precio, total. |
| VENTAS-UAT-003 | Escanear unidad cerrada disponible. | `id_inventario_unidad`, movimiento, unidad `vendida`. |
| VENTAS-UAT-004 | Intentar vender unidad abierta como cerrada. | Bloqueo con mensaje claro. |
| VENTAS-UAT-005 | Vender granel desde unidad abierta permitida. | Unidad queda abierta/agota, kardex con referencia. |
| VENTAS-UAT-006 | Intentar granel en SKU no permitido. | Bloqueo backend y UI. |
| VENTAS-UAT-007 | Pedido crea reserva. | `cantidad_apartada` sube y reserva activa. |
| VENTAS-UAT-008 | Venta consume reserva. | Reserva consumida y movimiento de salida. |
| VENTAS-UAT-009 | Cancelar pedido libera reserva. | Disponible regresa. |
| VENTAS-UAT-010 | Pago mixto genera ticket. | Pagos separados y total cuadrado. |
| VENTAS-UAT-011 | Corte de caja cuadra metodos. | Totales por metodo y diferencia. |
| VENTAS-UAT-012 | Cancelacion no borra historial. | Estatus cancelado y auditoria. |

## Orden de implementacion recomendado

### VENTAS-T001 - Documento rector

Estado: este documento.

### VENTAS-T002 - Esquema propuesto

Estado: aplicado en codigo sin ejecutar DDL.

Evidencia:

- `app/modelos/VentasErpEsquema.php`
- Auditoria read-only: todas las tablas propuestas `erp_pos_*` y `erp_ventas_*` no existen todavia.

No se ejecuto migracion ni escritura de BD.

### VENTAS-T003 - Modelo de dominio

Crear `app/modelos/VentasErp.php` con consultas read-only:

- catalogos POS;
- busqueda SKU ERP;
- disponibilidad por SKU/almacen;
- disponibilidad por etiqueta/unidad.

Estado: aplicado primera version read-only.

Endpoints modelo preparados:

- `catalogosPos()`
- `buscarSkusPos()`
- `disponibilidadSku()`
- `disponibilidadUnidad()`
- `prevalidarCarritoPos()`

Validaciones tecnicas:

- Catalogos POS devuelve almacenes con `permite_venta=1`: `ACUARIO967` y `MASCOTAS971`.
- Busqueda `TP` en almacen UAT `3` devuelve SKUs ERP con imagen, precio, reglas y disponibilidad.
- Disponibilidad de `TP-40372` muestra `18.9500 kg` disponibles, unidad abierta `UAT-EXI-26-20260625-001` y lote `L1`.
- Consulta de unidad abierta devuelve dictamen POS bloqueado como unidad cerrada: `abierta_no_granel`.
- Prevalidacion de carrito con modo `unidad_cerrada` exige seleccionar unidad fisica.

Hallazgo derivado:

- `VENTAS-H010`: la unidad abierta UAT no puede venderse a granel porque la regla de Catalogo del SKU base esta en `permite_venta_fraccionaria=0`.

### VENTAS-T004 - UI POS read-only

Crear pantalla POS que:

- busca productos ERP;
- muestra imagen/precio/disponible;
- arma carrito local;
- pre-valida cantidades;
- no cobra ni descuenta todavia.

Avance 2026-06-26:

- Se agrego ruta `Ventas::pos()` con permiso `ventas.operar`.
- Se creo `app/vistas/paginas/apps/erp/ventas/pos.php`.
- Se creo `public/assets/js/custom/apps/erp/ventas/pos.js`.
- La pantalla carga almacenes POS, busca SKUs ERP, muestra imagen/precio/disponible, arma carrito local y llama prevalidacion.
- El carrito permite seleccionar modo de salida: existencia agregada, unidad cerrada o granel desde unidad abierta.
- El boton de cobro queda deshabilitado con texto de autorizacion pendiente.
- La UI muestra el operador actual de la sesion.
- La UI permite fijar la terminal a una tienda mediante configuracion local del navegador.
- Cuando la terminal esta fijada, el selector de punto de venta queda bloqueado visualmente.

Validaciones tecnicas:

- `php -l app/controladores/Ventas.php`
- `php -l app/vistas/paginas/apps/erp/ventas/pos.php`
- `node --check public/assets/js/custom/apps/erp/ventas/pos.js`

Limite operativo:

- La UI no reserva, no cobra, no genera folio y no descuenta inventario. Es intencional hasta autorizar tablas ERP de ventas/POS y transaccion de kardex.
- La configuracion local de terminal no sustituye la asignacion oficial por usuario/caja en BD; solo reduce errores durante pruebas dry-run.

Decision UX:

- En operacion real, el cajero no debe elegir libremente cualquier sucursal al vender.
- El POS debe abrir ya ligado a la tienda/caja/turno asignado.
- Cambiar tienda/caja debe ser configuracion controlada, no parte del flujo normal de venta.
- Pendiente con autorizacion: persistir asignacion usuario/terminal/caja en BD.

### VENTAS-T004B - Reemplazo navegacion/listado legacy

Avance 2026-06-26:

- `/ventas/crear` ahora abre el POS ERP nuevo y deja de cargar `apps/ecommerce/sales/agregar-venta`.
- `/ventas/mostrar` ahora abre `apps/erp/ventas/listado.php` y deja de cargar `apps/ecommerce/sales/ventas`.
- `/ventas/editar` y `/ventas/detalles` aterrizan temporalmente en el tablero ERP para no abrir pantallas legacy.
- Se agrego `/ventas/pedidos` como entrada ERP al tablero filtrado para pedidos.
- Los menus de Ventas ahora apuntan a ERP y muestran: `Tablero de ventas`, `POS`, `Pedidos`.
- Se agregaron endpoints read-only:
  - `ventas_resumen_erp`
  - `ventas_listar_erp`
- Si no existen tablas ERP nuevas, el backend responde `schema_pendiente=true` sin consultar `ecom_pedidos`.

Validaciones tecnicas:

- `php -l app/controladores/Ventas.php`
- `php -l app/modelos/VentasErp.php`
- `php -l app/vistas/paginas/apps/erp/ventas/listado.php`
- `php -l app/vistas/includes/header/header.php`
- `php -l app/vistas/includes/header/menu.php`
- `node --check public/assets/js/custom/apps/erp/ventas/listado.js`
- Prueba CLI read-only: `resumenVentasModulo()` devuelve `schema_pendiente=true`.
- Prueba CLI read-only: `listarVentasErp(tipo=pedido)` devuelve lista vacia y `schema_pendiente=true`.

Limite operativo:

- El tablero ya reemplaza la navegacion antigua del modulo, pero aun no puede mostrar folios reales ERP hasta crear las tablas `erp_ventas*` y `erp_pos*`.

### VENTAS-T004C - Diagnostico read-only de cobertura

Avance 2026-06-26:

- Se agrego endpoint `diagnostico_erp`.
- Diagnostica almacenes POS, cajas, turnos, tablas requeridas y separacion legacy.
- No consulta ventas legacy como fuente operativa.

Evidencia read-only:

- Almacenes POS detectados:
  - `ACUARIO967` / `Acuario Mina 967`.
  - `MASCOTAS971` / `Mascotas Mina 971`.
- Tablas de inventario/reservas existentes:
  - `erp_inventario_reservas`;
  - `erp_inventario_movimientos`;
  - `erp_inventario_unidades`;
  - `erp_inventario_existencias`.
- Tablas Ventas/POS pendientes:
  - `erp_pos_cajas`;
  - `erp_pos_turnos`;
  - `erp_pos_movimientos_caja`;
  - `erp_ventas`;
  - `erp_ventas_detalle`;
  - `erp_ventas_detalle_inventario`;
  - `erp_ventas_pagos`;
  - `erp_ventas_devoluciones`;
  - `erp_ventas_devoluciones_detalle`.
- Hallazgos devueltos:
  - `VENTAS-DIAG-002`: falta esquema de cajas POS.
  - `VENTAS-DIAG-004`: falta esquema de turnos POS.
  - `VENTAS-DIAG-006`: falta esquema completo de venta POS real.

Validacion tecnica:

- `php -l app/controladores/Ventas.php`
- `php -l app/modelos/VentasErp.php`

### VENTAS-T005 - Prevalidacion backend

Endpoint que recibe carrito y responde:

- partidas validas;
- stock disponible;
- lote/unidad sugerida;
- bloqueos;
- totales recalculados.

Avance 2026-06-26:

- `catalogosPos()` ahora devuelve `cajas` y `schema_cajas_pendiente`.
- La UI POS tiene selector de caja filtrado por tienda/almacen.
- `prevalidarCarritoPos()` recibe `id_caja` y agrega bloqueos operativos si no existe esquema/caja valida.
- La prevalidacion sigue siendo read-only: no reserva, no cobra y no descuenta.

Evidencia read-only:

- `catalogosPos()` devuelve almacenes POS `ACUARIO967` y `MASCOTAS971`, `cajas=[]`, `schema_cajas_pendiente=true`.
- `prevalidarCarritoPos(id_almacen=4,id_caja=0,SKU=TP-40311,cantidad=1)` devuelve `Carrito con bloqueos`.
- Bloqueo operativo: `Configura cajas POS antes de cobrar; esta prevalidacion no genera venta`.
- Bloqueo inventario: `Existencia insuficiente` para ese SKU en almacen `4`.

Hallazgos:

- `VENTAS-H011`: el POS ya tiene almacenes de venta, pero falta esquema/datos de cajas para operar ventas reales por tienda.
- `VENTAS-H012`: la existencia disponible debe validarse por almacen de la tienda; no basta que el SKU exista en otra bodega.

### VENTAS-T005B - Plan de salida inventario read-only

Avance 2026-06-26:

- Cada partida prevalidada devuelve `plan_salida_inventario`.
- El plan no descuenta inventario; solo propone asignaciones para futura salida/kardex.
- Para existencia agregada usa FIFO por caducidad/registro.
- Para unidad fisica devuelve unidad, existencia, lote, caducidad, cantidad antes y cantidad despues simulada.
- Si no alcanza inventario, devuelve `faltante`.
- La UI POS muestra el plan de salida como evidencia read-only despues de prevalidar.

Evidencia read-only:

- `TP-40311` en almacen `4`, cantidad `1`: `asignaciones=[]`, `faltante=1`.
- `TP-40372` en almacen UAT `3`, cantidad `1`: propone existencia `26`, lote `L1`, ubicacion `E1-C2-P1-A1-N3`, antes `14.95`, despues `13.95`.
- Unidad abierta `36` de `TP-40372`, modo `granel_unidad_abierta`: propone unidad `36`/existencia `26`, pero conserva bloqueo porque el SKU no permite venta fraccionaria.

Hallazgos:

- `VENTAS-H017`: la venta real debe tomar la misma asignacion que se valide dentro de la transaccion, no confiar en una prevalidacion vieja.
- `VENTAS-H018`: el plan de salida debe alimentar `erp_ventas_detalle_inventario` y `erp_inventario_movimientos` con la misma referencia/folio.

Validacion tecnica:

- `php -l app/modelos/VentasErp.php`
- `node --check public/assets/js/custom/apps/erp/ventas/pos.js`

### VENTAS-T006 - Caja y turno

Abrir/cerrar turno antes de permitir cobro real.

Avance 2026-06-26:

- `catalogosPos()` ahora devuelve `turnos_abiertos` y `schema_turnos_pendiente`.
- La UI POS tiene selector de turno filtrado por tienda/almacen y caja.
- `prevalidarCarritoPos()` recibe `id_turno_caja`.
- La prevalidacion agrega bloqueo operativo si no existe esquema de turnos o no hay turno abierto.

Evidencia read-only:

- `catalogosPos()` devuelve `turnos_abiertos=[]`, `schema_turnos_pendiente=true`.
- `prevalidarCarritoPos(id_almacen=4,id_caja=0,id_turno_caja=0,SKU=TP-40311,cantidad=1)` devuelve:
  - `Configura cajas POS antes de cobrar; esta prevalidacion no genera venta`.
  - `Abre turno de caja antes de cobrar; esta prevalidacion no genera venta`.
  - `Existencia insuficiente`.

Hallazgos:

- `VENTAS-H013`: falta esquema/datos de turnos para permitir cobro real y cortes de caja.
- `VENTAS-H014`: toda venta real debe guardar `id_almacen`, `id_caja` e `id_turno_caja` antes de afectar inventario o pagos.

### VENTAS-T006A - Plan inicial de cajas POS

Avance 2026-06-26:

- Se agrego endpoint `cajas_plan_inicial_erp`.
- El endpoint propone cajas iniciales por almacen POS sin escribir BD.
- El tablero `Ventas ERP` muestra la preparacion de cajas por tienda.

Evidencia read-only:

- `ACUARIO967` propone caja `CJ-ACUARIO967-01`, `Caja principal Acuario Mina 967`.
- `MASCOTAS971` propone caja `CJ-MASCOTAS971-01`, `Caja principal Mascotas Mina 971`.
- Ambas cajas permitirian efectivo, tarjeta y transferencia.
- `schema_cajas_pendiente=true`.

Validacion tecnica:

- `php -l app/controladores/Ventas.php`
- `php -l app/modelos/VentasErp.php`
- `php -l app/vistas/paginas/apps/erp/ventas/listado.php`
- `node --check public/assets/js/custom/apps/erp/ventas/listado.js`

### VENTAS-T006B - Dry-run apertura de turno

Avance 2026-06-26:

- Se agrego endpoint `turno_apertura_dryrun_erp`.
- Valida tienda/almacen, caja, monto inicial y esquema requerido.
- No crea turnos ni movimientos de caja.
- Devuelve contrato de apertura para futura transaccion real.
- El tablero `Ventas ERP` permite simular apertura desde UI.

Evidencia read-only:

- `aperturaTurnoDryRun(id_almacen=4,id_caja=0,monto_inicial=500)` devuelve `Dry-run de apertura bloqueado`.
- Folio sugerido por runtime PHP: `TUR-20260625-001`.
- Bloqueos:
  - `Esquema de cajas pendiente`.
  - `Esquema de turnos pendiente`.

Contrato de apertura:

- Crear turno abierto.
- Registrar monto inicial.
- Ligar usuario de apertura.
- Impedir doble turno abierto por caja.

Validacion tecnica:

- `php -l app/controladores/Ventas.php`
- `php -l app/modelos/VentasErp.php`

### VENTAS-T006C - Dry-run cierre de turno

Avance 2026-06-26:

- Se agrego endpoint `turno_cierre_dryrun_erp`.
- Calcula monto esperado, contado y diferencia.
- No cierra turno ni crea movimientos.
- El tablero `Ventas ERP` permite simular cierre desde UI.

Evidencia read-only:

- `cierreTurnoDryRun(id_almacen=4,id_caja=0,id_turno_caja=0,monto_esperado=1000,monto_contado=950)` devuelve `Dry-run de cierre bloqueado`.
- Diferencia calculada: `-50`.
- Bloqueo: `Esquema de turnos pendiente`.

Contrato de cierre:

- Validar turno abierto.
- Calcular ventas y movimientos de caja.
- Registrar monto contado.
- Guardar diferencia.
- Impedir ventas en turno cerrado.

### VENTAS-T006D - Terminal y asignacion usuario/caja

Avance 2026-06-26:

- Se agrego endpoint `terminal_plan_asignacion_erp`.
- Se agrego endpoint `terminal_asignacion_actual_erp`.
- Se agregaron tablas propuestas `erp_pos_terminales` y `erp_pos_usuarios_cajas` al esquema dry-run.
- El plan sugiere terminal por tienda y asignacion usuario/caja/terminal sin escribir BD.
- La UI actual conserva configuracion local de terminal para UAT, pero la operacion real debe leer la asignacion oficial en BD.
- La UI POS ya consulta la asignacion actual y, si existe, bloquea tienda/caja con la configuracion oficial del operador.

Evidencia read-only:

- `ACUARIO967` propone terminal `TERM-ACUARIO967-01` ligada a `CJ-ACUARIO967-01`.
- `MASCOTAS971` propone terminal `TERM-MASCOTAS971-01` ligada a `CJ-MASCOTAS971-01`.
- `schema_terminales_pendiente=true`.
- `schema_usuarios_cajas_pendiente=true`.
- `asignacionActualTerminalPos(id_usuario=0)` devuelve `schema_pendiente=true` y `modo_ui=configuracion_local_uat` mientras falten tablas.

Decision operativa:

- El cajero no debe elegir libremente la sucursal en una venta normal.
- El POS debe abrir con la asignacion activa del usuario/terminal/caja.
- Cambiar tienda, caja o terminal debe quedar como configuracion autorizada.

Validacion tecnica:

- `php -l app/controladores/Ventas.php`
- `php -l app/modelos/VentasErp.php`
- `php -l app/modelos/VentasErpEsquema.php`
- `node --check public/assets/js/custom/apps/erp/ventas/pos.js`

### VENTAS-T007A - Ticket preview read-only

Avance 2026-06-26:

- Se agrego endpoint `ticket_preview_dryrun_erp`.
- Genera texto de ticket desde la prevalidacion, sin crear folio real.
- Incluye partidas, bloqueos, totales, pagos, saldo y cambio.
- Marca `NO ES VENTA CONFIRMADA` si hay bloqueos.
- La UI POS muestra el preview en modal con texto del ticket.

Evidencia read-only:

- `ticketPreviewDryRun(id_almacen=4,id_caja=0,id_turno_caja=0,SKU=TP-40311,pago efectivo=10)` devuelve ticket con:
  - folio temporal `PREVIEW-*`;
  - partida `TP-40311 x 1 = $10.00`;
  - bloqueo `Existencia insuficiente`;
  - total `$10.00`, pagado `$10.00`, saldo `$0.00`;
  - leyenda `NO ES VENTA CONFIRMADA`.

Validacion tecnica:

- `php -l app/controladores/Ventas.php`
- `php -l app/modelos/VentasErp.php`
- `php -l app/vistas/paginas/apps/erp/ventas/listado.php`
- `php -l app/vistas/paginas/apps/erp/ventas/pos.php`
- `node --check public/assets/js/custom/apps/erp/ventas/listado.js`
- `node --check public/assets/js/custom/apps/erp/ventas/pos.js`

### VENTAS-T007 - Venta POS real con respaldo

Con respaldo externo y autorizacion:

- crear venta;
- registrar pagos;
- descontar inventario;
- generar kardex;
- ticket.

Pre-avance read-only 2026-06-26:

- `prevalidarCarritoPos()` ahora recibe `pagos` en JSON.
- Valida metodo de pago activo, monto positivo y referencia para transferencia.
- Devuelve pagos normalizados y totales: `pagado_total`, `saldo_total`, `cambio`.
- La UI POS permite agregar/quitar pagos y muestra subtotal, pagado, saldo y cambio.
- Sigue sin registrar pagos ni movimientos de caja.

Evidencia read-only:

- `prevalidarCarritoPos(id_almacen=4,id_caja=0,id_turno_caja=0,SKU=TP-40311,cantidad=1,precio=10,pago efectivo=10)` devuelve:
  - pago normalizado `Efectivo`, monto `10`;
  - `subtotal=10`, `pagado_total=10`, `saldo_total=0`, `cambio=0`;
  - bloqueos conservados por caja pendiente, turno pendiente e inventario insuficiente.

Contrato para venta real:

- Una venta POS real no puede confirmar si tiene bloqueos operativos, bloqueos de inventario o bloqueos de pago.
- La referencia de kardex debe usar el folio ERP de venta, no el id legacy.
- Cada partida inventariable debe dejar relacion en `erp_ventas_detalle_inventario`.
- Cada pago debe quedar ligado a `id_venta`, `id_caja` e `id_turno_caja`.
- El esquema propuesto de `erp_ventas_pagos` incluye `id_caja`, `id_turno_caja` e `id_movimiento_caja`.

Validacion tecnica adicional:

- `php -l app/modelos/VentasErpEsquema.php`
- `auditarVentasPos()` confirma que las tablas `erp_pos*` y `erp_ventas*` aun no existen.

Dry-run de confirmacion:

- Se agrego endpoint `pos_confirmar_dryrun_erp`.
- El dry-run ejecuta prevalidacion completa y agrega bloqueo si falta esquema `erp_pos*`/`erp_ventas*`.
- No inserta venta, no registra pagos, no reserva y no mueve inventario.
- Contrato exigido para venta real:
  - `id_almacen`;
  - `id_caja`;
  - `id_turno_caja`;
  - folio ERP;
  - kardex;
  - trazabilidad en detalle inventario.

Evidencia dry-run:

- `confirmarVentaPosDryRun(id_almacen=4,id_caja=0,id_turno_caja=0,SKU=TP-40311,pago efectivo=10)` devuelve `Dry-run bloqueado`.
- Bloqueos: caja pendiente, turno pendiente, existencia insuficiente y esquema Ventas/POS pendiente de autorizacion/respaldo externo.

### VENTAS-T008 - Pedidos/reservas

Crear pedido que aparta stock y despues se entrega/cobra.

Pre-avance read-only 2026-06-26:

- Se agrego endpoint `pedido_reserva_dryrun_erp`.
- El dry-run valida `tipo_documento` (`pedido`/`apartado`), cliente publico y fecha compromiso.
- No exige pago completo para pedido/apartado; puede existir saldo pendiente.
- Reutiliza prevalidacion de inventario, caja y turno, pero no crea reserva.
- Agrega bloqueo si falta esquema de ventas/reservas.
- La UI POS permite elegir `Venta`, `Pedido` o `Apartado`, capturar cliente y fecha compromiso.
- La UI POS tiene boton `Simular pedido/reserva`.

Contrato de reserva:

- Requiere folio de pedido.
- Requiere `id_almacen` de la tienda que aparta.
- Requiere reserva de inventario con vencimiento.
- La entrega/cobro posterior debe generar kardex y trazabilidad al convertir reserva en salida real.

Evidencia read-only:

- `pedidoReservaDryRun(id_almacen=4,tipo=pedido,cliente='',fecha='',SKU=TP-40311,cantidad=1)` devuelve `Dry-run de pedido bloqueado`.
- Bloqueos esperados: cliente faltante, fecha compromiso faltante, caja/turno pendientes, existencia insuficiente y esquema pedidos/reservas pendiente.
- Confirmado: no aparece bloqueo por pago incompleto en pedido.

Hallazgos:

- `VENTAS-H015`: pedido/apartado no debe usar la misma regla de pago completo que venta POS inmediata.
- `VENTAS-H016`: la reserva debe ser por almacen de tienda y tener vencimiento para liberar stock si no se concreta.

Validacion tecnica:

- `php -l app/vistas/paginas/apps/erp/ventas/pos.php`
- `node --check public/assets/js/custom/apps/erp/ventas/pos.js`

### VENTAS-T009 - Cancelaciones/devoluciones

Implementar reversas controladas.

Pre-avance read-only 2026-06-26:

- Se agrego endpoint `devolucion_dryrun_erp`.
- Simula cancelacion/devolucion sin afectar venta ni inventario.
- El tablero `Ventas ERP` permite simular reversa con tipo, folio, decision de inventario y motivo.
- Decisiones de inventario soportadas en dry-run:
  - `reintegrar`;
  - `cuarentena`;
  - `merma`;
  - `sin_reingreso`.

Evidencia read-only:

- `devolucionDryRun(tipo=devolucion,folio='',motivo='',decision=cuarentena,items=[])` devuelve `Dry-run de devolucion bloqueado`.
- Bloqueos esperados:
  - falta `id_venta` o folio;
  - falta motivo documentado;
  - faltan partidas a devolver;
  - esquema de devoluciones/cancelaciones pendiente.

Contrato de reversa:

- No borrar venta original.
- Registrar folio de devolucion.
- Ligar detalle original.
- Crear kardex reversa si reingresa inventario.
- Conservar trazabilidad de unidad fisica.
- Registrar motivo y usuario.

Validacion tecnica:

- `php -l app/controladores/Ventas.php`
- `php -l app/modelos/VentasErp.php`
- `php -l app/vistas/paginas/apps/erp/ventas/listado.php`
- `node --check public/assets/js/custom/apps/erp/ventas/listado.js`

### VENTAS-T010 - Impacto ecommerce

Solo documentar y preparar endpoint futuro de disponibilidad por canal. No tocar ecommerce productivo todavia.

## Criterio de listo para operar

El POS puede usarse en control inicial cuando:

- la caja/turno funciona;
- cada venta deja folio;
- cada partida inventariable queda ligada a SKU ERP;
- cada salida queda en kardex;
- unidad cerrada queda vendida;
- unidad abierta solo se vende a granel si aplica;
- reservas cuadran con apartado;
- diagnostico de Inventario queda limpio despues de UAT;
- ticket muestra folio, partidas, pagos y total;
- cancelacion/devolucion no borra historia.

## Checklist de autorizacion para pasar de dry-run a operacion real

Requisitos antes de ejecutar DDL o seed:

- Respaldo externo completo verificado.
- Autorizacion explicita del dueño para crear/modificar tablas.
- Confirmar que `ACUARIO967` y `MASCOTAS971` son tiendas POS activas.
- Confirmar cajas iniciales:
  - `CJ-ACUARIO967-01`;
  - `CJ-MASCOTAS971-01`.
- Confirmar politica de folios:
  - venta POS;
  - pedido;
  - apartado;
  - devolucion;
  - turno.
- Confirmar si venta POS inmediata exige pago completo siempre.
- Confirmar vencimiento default de reservas/apartados.
- Confirmar destinos de devolucion: reintegrar, cuarentena, merma, sin reingreso.

Orden tecnico recomendado:

1. Ejecutar respaldo externo.
2. Aplicar esquema `erp_pos*` y `erp_ventas*`.
3. Crear cajas iniciales por tienda.
4. Abrir turno UAT por caja.
5. Confirmar venta POS UAT con SKU con existencia en tienda.
6. Verificar `erp_ventas`, detalle, pagos, caja y kardex.
7. Probar pedido/apartado con reserva y vencimiento.
8. Probar devolucion/cancelacion UAT.
9. Revisar diagnostico `ventas/diagnostico_erp`.

UAT minimo despues de autorizacion:

- `VENTAS-UAT-001`: venta de unidad cerrada disponible.
- `VENTAS-UAT-002`: bloqueo de unidad abierta como unidad cerrada.
- `VENTAS-UAT-003`: venta a granel desde unidad abierta si SKU permite fraccionario.
- `VENTAS-UAT-004`: venta bloqueada por stock insuficiente en tienda aunque exista en bodega.
- `VENTAS-UAT-005`: pago efectivo con cambio.
- `VENTAS-UAT-006`: transferencia sin referencia bloqueada.
- `VENTAS-UAT-007`: apertura/cierre de turno con diferencia.
- `VENTAS-UAT-008`: pedido con reserva y saldo pendiente.
- `VENTAS-UAT-009`: devolucion a cuarentena sin borrar venta original.
- `VENTAS-UAT-010`: ecommerce no toma unidad abierta como cerrada; solo documentar impacto, sin tocar ecommerce productivo.

## UAT read-only automatizado

Archivo:

- `storage/uat/uat_ventas_pos_dryrun_readonly.php`

Cobertura:

- Diagnostico del modulo.
- Catalogos POS.
- Plan inicial de cajas.
- Prevalidacion de carrito/pagos.
- Confirmacion POS dry-run.
- Pedido/reserva dry-run.
- Apertura/cierre de turno dry-run.
- Ticket preview.
- Devolucion dry-run.
- Auditoria de esquema Ventas/POS.
- Imagenes en tarjetas POS.

Ejecucion 2026-06-26:

- Comando: `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_dryrun_readonly.php --alcance=base`
- Resultado: `ok=true`, `modo=read-only`.
- Almacenes POS: `2`.
- Cajas: `0`.
- Turnos abiertos: `0`.
- Cajas sugeridas:
  - `ACUARIO967` -> `CJ-ACUARIO967-01`;
  - `MASCOTAS971` -> `CJ-MASCOTAS971-01`.
- Hallazgos diagnostico:
  - `VENTAS-DIAG-002`;
  - `VENTAS-DIAG-007`;
  - `VENTAS-DIAG-008`;
  - `VENTAS-DIAG-004`;
  - `VENTAS-DIAG-006`.
- Tablas pendientes:
  - `erp_pos_cajas`;
  - `erp_pos_terminales`;
  - `erp_pos_usuarios_cajas`;
  - `erp_pos_turnos`;
  - `erp_pos_movimientos_caja`;
  - `erp_ventas`;
  - `erp_ventas_detalle`;
  - `erp_ventas_detalle_inventario`;
  - `erp_ventas_pagos`;
  - `erp_ventas_devoluciones`;
  - `erp_ventas_devoluciones_detalle`.
- Imagenes POS: `ok=true`, `skus_con_url=5` para busqueda `TP-40372`.

Correccion imagenes POS 2026-06-26:

- Causa: el backend entregaba `url_imagen`, pero el JS del POS buscaba `imagen`, `imagen_principal` o `portada`.
- Causa secundaria: el placeholder apuntaba a una ruta inexistente.
- Correccion UI: `pos.js` ahora usa `url_imagen` y normaliza rutas relativas como `/media/...`.
- Correccion backend: `buscarSkusPos()` ahora usa fallback de imagen por producto cuando una variante no tiene imagen propia.
- Evidencia: `TP-40372`, `TP-40372-100GR`, `TP-40372-25GR`, `TP-40372-500GR` y `TP-40372-50GR` devuelven `media/apps/ecommerce/productos/5/1670071869.png`.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_dryrun_readonly.php`

## Guia manual de pruebas

Archivo:

- `docs/erp_ventas_pos_uat_manual.md`

Incluye pasos para validar:

- operador visible;
- fijar/liberar terminal;
- imagenes en tarjetas;
- carrito;
- prevalidacion;
- simulacion de confirmacion;
- pedido/reserva;
- ticket preview;
- tablero de ventas;
- dry-runs de turno y devolucion.

## Manual operativo de cajero

Archivo:

- `docs/erp_ventas_pos_manual_cajero.md`

Incluye pasos para uso diario:

- inicio del dia;
- venta normal;
- unidad cerrada;
- unidad abierta;
- pedido o apartado;
- bloqueos comunes;
- cierre del dia;
- reglas importantes para no mezclar POS, ecommerce e inventario manual.

Este manual debe actualizarse despues de autorizar venta real, apertura/cierre de turno y formato final de ticket.

## Ajuste UX POS rapido

Fecha: 2026-06-26

Cambios aplicados:

- Carrito convertido a tabla operativa para caja rapida.
- Carrito movido debajo de resultados para trabajar a pantalla completa.
- Resultados de productos convertidos a scroll horizontal compacto para que no empujen el carrito hacia abajo.
- Modo de salida cambio de `select` a botones segmentados: `Stock`, `Pieza`, `Granel`.
- Si el SKU permite granel y hay unidad abierta disponible, al agregarlo entra directo con input de peso.
- Productos no granel conservan cantidad con botones `-` y `+`.
- Pagos se muestran como filas compactas con metodo, monto, referencia y quitar.
- Se agregaron botones rapidos de pago: `Efectivo`, `Tarjeta`, `Transferencia`.
- Se retiro el boton generico `Pago` para evitar duplicidad con botones rapidos.
- Control de pieza/peso corregido para no invadir columnas de precio/importe.
- Se sanea texto mojibake visible en metodos de pago.
- Se agrego telefono rapido de cliente sin crear aun ficha de cliente.

Evidencia Playwright 2026-06-26:

- La ruta protegida redirige a login sin sesion activa, por lo que se renderizo maqueta local con los mismos estilos del POS.
- Screenshot de maqueta: `public/storage/uat/pos_playwright_mock_ux_after.png`.
- Medicion automatica: `payButtons=0`, `productScroller=true`, `overlapsPrice=false`.

Regla UX:

- El cajero no debe entrar a un `select` para capturar peso si el producto ya es de granel. El flujo debe ser: buscar o escanear, agregar, teclear peso, cobrar/prevalidar.

## Clientes, precios e incentivos POS

Archivo:

- `docs/erp_ventas_pos_clientes_plan.md`

Principio:

- La captura de telefono/nombre en POS es solo una entrada express provisional, no el modelo final de clientes.
- El POS debe poder vender a publico general, identificar cliente rapido, o seleccionar cliente existente sin frenar la fila.
- El ERP final debe soportar `id_cliente`, listas de precios, promociones, recompensas, incentivos, historial, duplicados y privacidad.
- Precios/descuentos/recompensas deben resolverse en backend y guardarse como snapshot en venta.
- No mezclar clientes ecommerce/legacy sin auditoria y estrategia de vinculacion.

## Handoff de contexto vivo

Archivo:

- `docs/erp_ventas_pos_handoff_contexto.md`

Uso:

- Leer cuando el contexto de conversacion se compacte o cuando se retome el modulo despues de otra tarea.
- Mantener actualizado con decisiones, estado, archivos, scripts, autorizaciones pendientes y riesgos.

## Plan de robustez POS

Archivo:

- `docs/erp_ventas_pos_robustez_plan.md`

Incluye:

- evaluacion actual;
- componentes faltantes;
- roadmap por etapas;
- definicion de `POS robusto listo`;
- apartados/pedidos con pagos parciales como etapa prioritaria despues del POS base.

## Dry-run cliente/precio y abonos

Archivos y endpoints:

- `storage/uat/uat_ventas_pos_cliente_precio_apartado_readonly.php`
- `/ventas/pos_cliente_precio_dryrun_erp`
- `/ventas/apartado_abono_dryrun_erp`

Proposito:

- Simular resolucion de cliente/lista/precio sin crear cliente ni aplicar descuentos reales.
- Simular abono de apartado sin registrar pago, caja ni saldo.
- Validar que el precio lo resuelve backend y que POS/JS no decide descuentos.
- Validar que abonos deben ligarse a folio, caja, turno y evento de venta.

Ejecucion read-only 2026-06-26:

- `cliente_precio`: devuelve `schema_clientes_pendiente=true`, `schema_listas_precios_pendiente=true`, precio origen `catalogo_general`.
- `abono_apartado`: devuelve bloqueo por cajas POS, turno y esquema de apartados/abonos pendiente.
- No escribe BD.

Contrato nuevo:

- `erp_ventas` debe soportar `id_lista_precio`, snapshots de lista/segmento/beneficios, anticipo minimo y vencimiento.
- `erp_ventas_detalle` debe soportar precio base, precio aplicado, lista, promocion, regla origen y autorizacion.
- `erp_ventas_pagos` debe distinguir `pago`, `anticipo`, `abono`, `liquidacion` o equivalentes.
- `erp_ventas_eventos` debe registrar abonos, vencimientos, cambios de estatus y entrega.
- Clientes y listas de precios pasan a ser alcance de diseno, pero no de escritura hasta nueva autorizacion explicita.

## Caja y turno pendiente

Significado operativo:

- `Caja pendiente`: aun no existen cajas POS configuradas en BD para la sucursal, o el usuario no tiene una caja asignada.
- `Turno pendiente`: existe caja, pero no hay un corte/turno abierto para esa caja.

Regla:

- Una venta real no debe cobrar ni descontar inventario si no tiene `id_almacen`, `id_caja` e `id_turno_caja`.
- El turno agrupa todas las ventas y pagos del periodo para poder hacer corte de caja.
- La caja pertenece a una tienda/almacen; el cajero no debe poder vender desde otra sucursal en operacion normal.

## Plantilla de evidencia UAT

Archivo:

- `docs/erp_ventas_pos_evidencia_uat.md`

Objetivo:

- Documentar cada prueba con usuario, sucursal, almacen, caja, terminal, turno, SKU, unidad fisica, folio, movimiento kardex y hallazgos con ID.
- Evitar pruebas informales sin evidencia cuando se empiece a validar venta real, reservas, devoluciones y caja.

## Paquete dry-run de autorizacion

Archivo:

- `storage/uat/uat_ventas_pos_paquete_autorizacion_dryrun.php`

Proposito:

- Generar DDL propuesto sin ejecutar.
- Generar SQL de semillas de cajas sin ejecutar.
- Generar SQL de semillas de terminales/asignaciones sin ejecutar.
- Confirmar hallazgos diagnostico antes de pedir autorizacion.
- Validar que las asignaciones no queden con `id_usuario=0` cuando se pase un usuario real.

Parametros utiles:

- `--id_usuario=ID`: usa un mismo usuario ERP para las asignaciones sugeridas.
- `--usuario_almacen=ACUARIO967:ID`: usa usuario especifico para esa sucursal.
- `--usuario_almacen=MASCOTAS971:ID`: usa usuario especifico para esa sucursal.

Ejecucion 2026-06-26:

- Comando: `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_paquete_autorizacion_dryrun.php --alcance=base`
- Resultado: `ok=true`, `modo=dry-run`.
- DDL propuestos: `11`.
- Semillas de cajas propuestas: `2`.
- Semillas de terminales propuestas: `2`.
- Semillas de asignacion usuario/caja/terminal propuestas: `2`.
- `listo_para_autorizacion_configuracion=false` cuando no se informa usuario real.
- Hallazgos diagnostico:
  - `VENTAS-DIAG-002`;
  - `VENTAS-DIAG-007`;
  - `VENTAS-DIAG-008`;
  - `VENTAS-DIAG-004`;
  - `VENTAS-DIAG-006`.

Semillas propuestas:

- `CJ-ACUARIO967-01` para almacen `4`.
- `CJ-MASCOTAS971-01` para almacen `5`.
- `TERM-ACUARIO967-01` ligada a `CJ-ACUARIO967-01`.
- `TERM-MASCOTAS971-01` ligada a `CJ-MASCOTAS971-01`.

Nota:

- Las semillas de asignacion no deben ejecutarse con `id_usuario=0`.
- Para preparar paquete con usuario real: `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_paquete_autorizacion_dryrun.php --id_usuario=ID_USUARIO_CAJERO --alcance=base`.

Orden recomendado:

1. Verificar respaldo externo.
2. Ejecutar DDL de tablas Ventas/POS.
3. Ejecutar seed de cajas iniciales.
4. Ejecutar seed de terminales.
5. Ejecutar seed de asignacion usuario/caja/terminal.
6. Abrir turno UAT.
7. Ejecutar UAT de venta/reserva/devolucion.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_paquete_autorizacion_dryrun.php`

## Runbook read-only de autorizacion

Archivo:

- `storage/uat/uat_ventas_pos_runbook_readonly.php`

Proposito:

- Mostrar orden de ejecucion de respaldo, paquete dry-run, DDL, semillas, UAT, turno y venta real.
- Separar claramente pasos read-only de pasos con escritura.
- Evitar autorizaciones incompletas o semillas con usuario invalido.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_runbook_readonly.php`

## Usuarios candidatos POS read-only

Archivo:

- `storage/uat/uat_ventas_pos_usuarios_candidatos_readonly.php`

Proposito:

- Listar usuarios activos candidatos para asignacion POS sin exponer datos sensibles.
- Confirmar quien tiene `ventas.operar`.

Ejecucion 2026-06-26:

- Resultado: `ok=true`, `total=3`.
- Candidato con permiso POS:
  - `id_usuario=1`, roles `administrador_erp`, `soporte_sistema`, `puede_operar_pos=true`.
- Usuarios activos sin permiso POS:
  - `id_usuario=2`;
  - `id_usuario=3`.

Decision pendiente:

- Confirmar si `id_usuario=1` sera operador UAT de ambas cajas o si se asignaran usuarios por sucursal antes de sembrar POS.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_usuarios_candidatos_readonly.php`

## Aplicador de semillas POS autorizado

Archivo:

- `storage/uat/uat_ventas_pos_seed_apply_authorized.php`

Proposito:

- Crear cajas, terminales y asignaciones usuario/caja/terminal despues del DDL.
- Ejecutar en transaccion con rollback ante error.
- Bloquear si falta respaldo, autorizacion o usuario real.

Candados:

- Requiere `--autorizar=VENTAS_POS_SEED`.
- Requiere `--respaldo=RUTA_O_REFERENCIA`.
- Requiere `--id_usuario=ID` o `--usuario_almacen=CODIGO:ID`.
- Bloquea si faltan tablas `erp_pos_cajas`, `erp_pos_terminales` o `erp_pos_usuarios_cajas`.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_seed_apply_authorized.php`
- Ejecucion sin parametros: `modo=bloqueado`, no ejecuta semillas.

## UAT post-configuracion POS read-only

Archivo:

- `storage/uat/uat_ventas_pos_post_config_readonly.php`

Proposito:

- Validar despues de DDL/semillas que existan cajas, terminales y asignaciones.
- Confirmar que `terminal_asignacion_actual_erp` ya pueda devolver `asignacion_activa=true`.
- Mantener venta y turno real bloqueados hasta implementar/aprobar endpoints de escritura.

Uso esperado:

- `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=ID_USUARIO_CAJERO --alcance=base`

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_post_config_readonly.php`
- Ejecucion antes del DDL: reporta tablas pendientes y `asignacion_activa=false`, sin escribir BD.

## Turno POS preflight y apertura autorizada

Archivos:

- `storage/uat/uat_ventas_pos_turno_preflight_readonly.php`
- `storage/uat/uat_ventas_pos_turno_apertura_apply_authorized.php`

Proposito:

- Validar si el usuario/caja/terminal puede abrir turno sin escribir BD.
- Abrir turno real solo con respaldo, autorizacion y monto inicial.
- Registrar en una transaccion `erp_pos_turnos` y `erp_pos_movimientos_caja`.

Candados de apertura:

- Requiere `--autorizar=VENTAS_POS_TURNO_APERTURA`.
- Requiere `--respaldo=RUTA_O_REFERENCIA`.
- Requiere `--id_usuario=ID`.
- Requiere `--monto_inicial=MONTO`.
- Bloquea si ya existe turno abierto para esa caja.
- Bloquea si falta asignacion POS activa.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_turno_preflight_readonly.php`
- `php -l storage/uat/uat_ventas_pos_turno_apertura_apply_authorized.php`
- Ejecucion del aplicador sin parametros: `modo=bloqueado`, no abre turno.

## Venta POS real - preflight read-only y aplicador autorizado

Archivos:

- `storage/uat/uat_ventas_pos_venta_preflight_readonly.php`
- `storage/uat/uat_ventas_pos_venta_apply_authorized.php`

Proposito:

- Validar que usuario, asignacion, caja, turno, pagos y plan de salida inventario estan listos antes de ejecutar una venta real.
- Confirmar que cada partida tiene asignaciones de inventario suficientes.
- Mantener la venta real bloqueada hasta tener DDL aplicado, semillas POS, asignacion activa, turno abierto, respaldo y autorizacion textual especifica.

Contrato obligatorio de venta real:

- Crear encabezado en `erp_ventas`.
- Crear partidas en `erp_ventas_detalle`.
- Crear pagos en `erp_ventas_pagos`.
- Crear trazabilidad por asignacion en `erp_ventas_detalle_inventario`.
- Insertar salida en `erp_inventario_movimientos` con `origen_tipo='venta_pos'` y referencia de folio ERP.
- Actualizar `erp_inventario_existencias.cantidad` y `cantidad_disponible` dentro de la misma transaccion.
- Actualizar unidad fisica si aplica:
  - unidad cerrada vendida completa: `estado_fisico='vendida'`, `estatus='vendida'`, disponible `0`;
  - unidad abierta granel: reducir `cantidad_base_disponible`, conservar abierta o pasar a agotada si queda `0`;
  - existencia agregada: no inventar unidad fisica.
- Usar `FOR UPDATE` para revalidar stock, turno, caja y unidad antes de escribir.

Candados del aplicador:

- Requiere `--autorizar=VENTAS_POS_VENTA_REAL`.
- Requiere `--respaldo=RUTA_RESPALDO_EXISTENTE`.
- Requiere `--id_usuario=ID_OPERADOR_POS`.
- Requiere asignacion activa usuario/caja/terminal.
- Requiere turno abierto de la misma caja y almacen.
- Reejecuta prevalidacion y dry-run antes de abrir transaccion.
- Si falla cualquier escritura, hace rollback completo.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_venta_preflight_readonly.php`
- `php -l storage/uat/uat_ventas_pos_venta_apply_authorized.php`
- Ejecucion del aplicador sin argumentos: `modo=guardrail`, no escribe BD.
- Ejecucion antes del DDL/turno: reporta bloqueos, no escribe BD.

## Post-venta POS read-only por folio

Archivo:

- `storage/uat/uat_ventas_pos_post_venta_readonly.php`

Proposito:

- Validar una venta POS UAT por folio despues de ejecutarla con autorizacion.
- Confirmar que existen encabezado, detalle, pagos, movimiento de caja, kardex y trazabilidad detalle-inventario.
- Revisar cuadres entre total de encabezado, suma de detalle y pagos registrados.
- Generar hallazgos con ID si una partida inventariable no tiene movimiento de inventario o si el kardex no usa `origen_tipo='venta_pos'` y referencia del folio ERP.

Uso esperado:

- `C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=FOLIO_POS_GENERADO`

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_post_venta_readonly.php`
- Sin folio devuelve ayuda y no escribe BD.

## Preflight de respaldo para autorizacion

Archivo:

- `storage/uat/uat_ventas_pos_respaldo_preflight_readonly.php`

Proposito:

- Validar una referencia de respaldo externo antes de autorizar DDL Ventas/POS.
- Si es ruta local, valida existencia, lectura y tamano mayor a cero.
- No crea respaldos, no escribe archivos y no escribe BD.

Ejecucion 2026-06-26:

- Sin referencia: `ok=false`, `referencia_presente=false`.
- Con `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`: `ok=true`, archivo existe, legible, tamano `27216189` bytes.

Regla:

- Este preflight no autoriza por si solo la ejecucion de DDL. La autorizacion debe mencionar explicitamente Ventas/POS/Pedidos y el alcance `erp_pos*`/`erp_ventas*`.

Validacion tecnica:

- `php -l storage/uat/uat_ventas_pos_respaldo_preflight_readonly.php`

## Script de aplicacion autorizada con guardrail

Archivo:

- `storage/uat/uat_ventas_pos_schema_apply_authorized.php`

Proposito:

- Aplicar `planActualizarVentasPos(true)` solo cuando exista autorizacion explicita.
- Bloquear por defecto cualquier ejecucion accidental.

Requisitos para ejecutar:

- `--autorizar=VENTAS_POS_DDL_BASE` para POS/caja/ventas/devoluciones.
- `--autorizar=VENTAS_POS_DDL_EXPANDIDO` para incluir clientes/listas/atenciones POS/eventos/apartados.
- `--respaldo=RUTA_O_REFERENCIA`

Validacion 2026-06-26:

- `php -l storage/uat/uat_ventas_pos_schema_apply_authorized.php`
- Ejecucion sin argumentos devuelve:
  - `ok=false`;
  - `modo=bloqueado`;
  - `No se ejecuto DDL. Falta autorizacion explicita o respaldo valido.`

Regla:

- No ejecutar este script hasta que el dueno confirme respaldo externo y autorice explicitamente el alcance.
- Recomendacion vigente: usar primero `VENTAS_POS_DDL_BASE`, validar caja/turno/venta/kardex, y despues evaluar `VENTAS_POS_DDL_EXPANDIDO`.
