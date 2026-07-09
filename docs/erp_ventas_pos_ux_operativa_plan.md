# ERP Ventas POS - UX operativa robusta

## Objetivo

Diseñar un POS moderno, rapido e intuitivo para mostrador, sin sacrificar inventario, caja, cliente CRM, garantias, autorizaciones, devoluciones ni trazabilidad.

## Principios

- El operador debe poder vender principalmente con escaner, busqueda, teclado numerico y pocos clics.
- La pantalla principal debe priorizar: busqueda, resultados, carrito, pagos, totales y cobro.
- Las funciones de soporte deben vivir como modulos compactos, no como una columna larga de botones.
- El backend decide precio, stock, descuento, caja, kardex y cliente; el JS solo captura y muestra.
- El POS real no debe permitir elegir libremente tienda/caja: debe abrir con asignacion oficial.
- Toda pantalla debe distinguir visualmente:
  - `Accion real`: escribe BD o afecta caja/inventario;
  - `Simulacion`: valida reglas y muestra resultado esperado, pero no escribe;
  - `Solo consulta`: lee informacion y no modifica nada.

## Estructura propuesta de pantalla

### Zona superior

- Operador activo.
- Terminal/caja/turno asignados.
- Documento: venta, pedido, apartado.
- Buscador principal con foco rapido.
- Modulos compactos: Prevalidar, Simular, Pedido, Ticket, Cliente, Autorizar, Atenciones, Caja, Corte.

### Zona media

- Tarjetas de producto con imagen, precio, disponibilidad y modo de venta.
- Scroll horizontal para resultados cuando haya muchos productos.
- Carrito completo en tabla, con cantidad o peso editable segun el SKU.

### Zona inferior

- Pagos siempre visibles.
- Totales y saldo siempre visibles.
- Boton primario unico: Cobrar.
- Alertas operativas debajo de pagos, no mezcladas con botones.

## Atajos iniciales seguros

- `F2`: enfoca busqueda/producto.
- `Ctrl+K`: enfoca busqueda/producto.
- `Alt+1`: agrega pago rapido efectivo.
- `Alt+2`: agrega pago rapido tarjeta.
- `Alt+3`: agrega pago rapido transferencia.
- `F4`: abre cliente/lista.
- `F6`: enfoca el primer pago o crea pago efectivo si no existe.
- `F8`: abre caja.
- `F9`: prevalidar.
- `Ctrl+Enter`: cobrar.

No usar `F5`, `Ctrl+R`, `Ctrl+L` ni `Ctrl+P` para acciones criticas porque chocan con navegador.

## Configuracion POS por tienda/caja

### Modelo recomendado

- `erp_pos_terminales`: terminal fisica o navegador autorizado.
- `erp_pos_cajas`: caja operativa por almacen.
- `erp_pos_usuarios_cajas`: asigna usuario a almacen, caja y terminal.
- `erp_pos_turnos`: turno abierto ligado a caja, usuario y almacen.

### Regla operativa

- Al abrir POS, el sistema resuelve asignacion activa por usuario y terminal.
- Si hay asignacion oficial, bloquea selectores de almacen/caja.
- Si falta asignacion, muestra estado de terminal no configurada y solo permite UAT/configuracion.
- Cambiar tienda/caja debe ser accion administrativa, no decision del cajero en venta.
- En dispositivo compartido, la terminal debe tener identidad local y validarse contra la asignacion de BD.

## Avance 2026-06-30

- Se movieron acciones del bloque inferior a una barra horizontal de modulos sobre el carrito.
- La parte baja queda concentrada en pagos, totales y `Cobrar`.
- Se agregaron atajos JS read-only/UX; no escriben BD por si solos.
- Se separo en el modal de cliente:
  - busqueda CRM read-only sin carrito;
  - resolucion de lista/precio con carrito;
  - alta express solo como dry-run.
- La seleccion de cliente CRM ahora deja el boton como `Seleccionado`.
- Se versiono el asset POS como `20260630-pos-ux-cliente3`.

## Pendientes

- Validar visualmente con Playwright/sesion real.
- Convertir pagos a panel de inputs siempre abiertos por metodo.
- Crear pantalla administrativa de asignacion oficial usuario/terminal/caja.
- Exponer alta real de cliente CRM desde POS solo con autorizacion y permiso.
- Medir flujo real de operador: escanear, pesar, pagar, imprimir ticket.

## Decision de arquitectura 2026-07-01 - Separar POS, caja y configuracion

El POS principal no debe concentrar configuracion, corte, movimientos sensibles, devoluciones y administracion de tiendas/cajas. Durante UAT se exponen como modales para acelerar pruebas, pero la arquitectura final debe separar responsabilidades.

### Navegacion recomendada

- `Ventas > POS`: venta rapida, cliente, carrito, pagos, cobrar, ticket inmediato y captura minima de atencion.
- `Ventas > Ventas`: tablero read-only/operativo de ventas, folios, tickets, pagos, estado, cliente, origen y acciones permitidas por rol.
- `Ventas > Pedidos/Apartados`: pedidos, reservas, abonos, vencimientos y conversion a venta.
- `Ventas > Devoluciones`: cancelaciones, devoluciones, saldos a favor, reembolsos, reimpresion de comprobantes y seguimiento postventa.
- `Caja > Turnos`: abrir turno, cerrar turno, corte, arqueo, diferencias y bitacora.
- `Caja > Movimientos`: gastos de caja, retiros, entradas extraordinarias, vales, reembolsos y evidencias.
- `Caja > Evidencias`: revision, correcciones y aprobaciones de comprobantes sensibles.
- `Configuracion POS > Tiendas y cajas`: CRUD de tiendas/sucursales/almacenes vendibles, cajas, terminales y asignaciones usuario-caja.
- `Configuracion POS > Politicas`: metodos de pago permitidos, limites por caja, autorizaciones requeridas, folios, impresoras y reglas de descuento.

### Regla UX

- En POS solo deben quedar accesos operativos de alta frecuencia.
- Abrir/cerrar turno puede estar visible como estado o acceso rapido, pero debe resolverse en `Caja > Turnos`.
- Crear tienda, caja o terminal nunca debe hacerse desde la pantalla de cobro.
- El cajero no debe elegir libremente sucursal/caja en una venta real: el sistema debe resolverlo por terminal, usuario y turno abierto.
- Las devoluciones deben iniciarse desde una venta/folio, pero vivir como flujo propio con permisos, motivo, decision financiera, decision fisica y ticket.

### Estado actual detectado

- Ya existen tablas y endpoints base de cajas, terminales, asignaciones, turnos, movimientos y evidencias.
- Ya hay POS operativo con cobro real, kardex, caja, garantia y ticket.
- La pantalla `/ventas/pos` aun contiene modales UAT de Caja, Corte, Evidencias y Devoluciones fisicas.
- El menu actual solo expone `Tablero de ventas`, `POS` y `Pedidos`; falta menu/vistas CRUD para Caja y Configuracion POS.
- No existe todavia CRUD visual productivo para crear/editar tiendas, cajas, terminales ni asignaciones.

### Plan de implementacion recomendado

1. Crear vistas read-only iniciales:
   - `/ventas/caja_turnos`;
   - `/ventas/caja_movimientos`;
   - `/ventas/pos_configuracion`.
2. Mover UI de corte desde modal POS hacia `Caja > Turnos`.
3. Mover UI de movimientos/evidencias desde modal POS hacia `Caja > Movimientos` y `Caja > Evidencias`.
4. Crear CRUD administrativo de cajas/terminales/asignaciones con dry-run primero.
5. Dejar en POS solo un indicador compacto:
   - tienda;
   - caja;
   - turno;
   - operador;
   - boton contextual `Abrir/Cerrar turno` que lleve al modulo de caja.
6. Crear tablero de ventas con acciones por folio:
   - ver ticket;
   - reimprimir;
   - iniciar devolucion;
   - ver pagos;
   - ver kardex;
   - ver garantia.
7. Crear modulo formal de devoluciones conectado a venta, caja, inventario, garantia y CRM.

## Estado de configuracion UAT 2026-06-30

- `id_usuario=1` tiene `ventas.ver` y `ventas.operar`.
- Hay 2 cajas POS, 2 terminales y 2 asignaciones activas.
- La asignacion oficial para `id_usuario=1` existe y el POS debe abrir en modo `asignacion_oficial`.
- No hay turno abierto; esto bloquea venta real, pero es correcto si la caja no se ha abierto.
- Host local correcto para pruebas: `http://dashboard.com.local`.
- Playwright autenticado cargo POS, busqueda CRM y seleccion de cliente sin errores JS del flujo.
- `localhost` apunta a otro proyecto en esta instalacion y no debe usarse para UAT POS.
- En asignacion oficial, almacen, caja y turno quedan deshabilitados para evitar seleccion manual del cajero.
- Cuando no hay turno abierto, `Cobrar` queda deshabilitado y muestra `Abrir turno para cobrar`.

## Avance 2026-07-02 - etiquetas de modo operativo

- POS muestra que `Cobrar` es accion real.
- POS muestra que `Prevalidar/Simular` no escribe datos.
- Caja muestra que `Simular corte` no cierra turno.
- Configuracion POS muestra que `Validar alta` no crea registros.
- Devoluciones muestra que `Simular reversa` no reembolsa, no mueve inventario y no crea kardex.
- Assets versionados con `20260702-modos-accion1`.

## Avance 2026-07-02 - movimientos/evidencias como modulos propios

- Se agrego `/ventas/caja_movimientos` para simular movimientos no venta:
  - gasto de caja;
  - retiro de efectivo;
  - entrada extraordinaria;
  - vale interno;
  - reembolso cliente.
- Se agrego `/ventas/caja_evidencias` para seguimiento documental:
  - pendientes por estado;
  - detalle por movimiento;
  - correcciones visibles en modo consulta.
- Regla UX:
  - POS mostrador no debe cargar aprobaciones documentales;
  - Caja y Turnos concentra apertura/cierre/corte;
  - Movimientos concentra dinero no venta;
  - Evidencias concentra comprobantes/revision;
  - acciones reales siguen fuera de estas pantallas hasta autorizacion y permisos finos.

## Avance 2026-07-02 - POS mostrador mas ligero

- En la barra operativa del POS:
  - `Movimientos` abre el modulo dedicado de caja;
  - `Evidencias` abre el modulo documental;
  - `Corte` abre caja/turnos.
- Ya no se promueve operar caja sensible dentro del modal de cobro.
- El atajo `F8` abre Movimientos de caja.
- Siguiente limpieza recomendada:
  - retirar HTML legacy de modal Caja/Corte del POS cuando las pantallas dedicadas cubran toda la operacion;
  - mantener en POS solo estado compacto de caja/turno y accesos rapidos.

## Avance 2026-07-02 - detalle de venta por folio

- Se agrego pantalla read-only de venta:
  - `/ventas/venta_detalle?folio=...`.
- Esta pantalla debe ser el punto natural para:
  - revisar ticket;
  - ver pagos;
  - ver garantia;
  - ver kardex/trazabilidad;
  - iniciar devolucion desde folio.
- Regla UX:
  - el listado es para buscar y seleccionar;
  - el detalle es para entender y auditar una venta;
  - las acciones sensibles siguen en Devoluciones, Caja, Evidencias o Configuracion POS.

## Avance 2026-07-02 - retiro de caja/corte del POS

- Se retiro el HTML legacy de modales Caja y Corte dentro de `/ventas/pos`.
- El POS queda mas enfocado en:
  - buscar producto;
  - carrito;
  - cliente;
  - pagos;
  - cobro;
  - ticket;
  - accesos rapidos a modulos.
- Post-cobro, `Ver venta` abre el detalle read-only por folio.
- Caja operativa sensible queda en:
  - `/ventas/caja_turnos`;
  - `/ventas/caja_movimientos`;
  - `/ventas/caja_evidencias`.

## Avance 2026-07-02 - pedidos/apartados separados

- `/ventas/pedidos` ahora tiene pantalla dedicada.
- La pantalla separa:
  - busqueda y seguimiento de pedidos/apartados;
  - detalle por folio;
  - simulacion de abonos.
- Regla UX:
  - el POS mostrador puede iniciar un pedido/apartado, pero el seguimiento vive en Pedidos/Apartados;
  - los abonos reales deben pasar por caja/turno y quedar trazados como pagos de tipo `abono`;
  - entregar mercancia debe revalidar inventario, reserva y saldo antes de descontar.

## Avance 2026-07-03 - claridad POS vs Pedidos

- En `/ventas/pos`, el boton interno de simulacion se nombra `Simular pedido`.
- En `/ventas/pos`, el boton `Pedidos` abre `/ventas/pedidos`.
- Diferencia operativa:
  - `Simular pedido`: valida el carrito actual sin crear folio, reserva, pago, caja ni kardex;
  - `Pedidos`: abre el modulo de seguimiento y simulacion de pedidos/apartados.
- Atajos:
  - `F9`: prevalidar carrito;
  - `F10`: abrir modulo Pedidos/Apartados.
- Regla UX:
  - los textos de botones deben decir si una accion crea algo real o solo simula;
  - el POS no debe prometer que un pedido fue creado si el backend solo hizo dry-run.

## Avance 2026-07-03 - Configuracion POS como administracion

- Configuracion POS queda como modulo administrativo separado:
  - `/ventas/pos_configuracion`.
- El POS mostrador no debe permitir al cajero elegir libremente tienda, almacen, caja o terminal.
- El contexto operativo se resuelve por:
  - usuario autenticado;
  - asignacion activa;
  - caja;
  - terminal;
  - turno abierto.
- El CRUD real de configuracion debe tener permisos finos y auditoria propia.
- Runbook preparado:
  - `docs/erp_ventas_pos_configuracion_real_runbook.md`.
- Regla UX:
  - en mostrador solo se muestra estado compacto de tienda/caja/turno;
  - si falta asignacion o turno, el POS explica el bloqueo y envia a Caja/Configuracion segun permiso;
  - altas, ediciones y desactivaciones de cajas/terminales/asignaciones no viven dentro del flujo de cobro.

## Ajuste UX 2026-07-03 - texto de validacion sin escritura

- En `/ventas/pos_configuracion`, la etiqueta `Validar alta = no crea registros` se cambio por `Validar sin crear = no guarda registros`.
- Motivo:
  - evitar que el operador interprete la validacion como alta real;
  - mantener claro que la pantalla actual consulta/simula hasta autorizar CRUD real.
- Asset versionado:
  - `pos_configuracion.js?v=20260703-validar-sin-crear1`.
- No cambia reglas de negocio ni escribe BD.
