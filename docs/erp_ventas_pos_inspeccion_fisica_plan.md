# ERP Ventas POS - Plan inspeccion fisica avanzada

## Contexto

- Fecha: 2026-07-09
- Ruta de trabajo: `C:\xampp\htdocs\panel_de_control`
- Modulo: ERP > Ventas/POS/Postventa/Devoluciones
- Estado actual:
  - reversas POS reales ya registran devolucion, decision financiera, evidencia de caja y trazabilidad;
  - inspeccion fisica documental desde UI ya permite `mantener_cuarentena`;
  - bandeja de devoluciones fisicas pendientes queda en `0`;
  - no hay flujo real aun para reintegrar disponible, merma, garantia/proveedor o reparacion.

## Regla base

La devolucion comercial y la decision fisica son dos momentos separados.

- Devolucion comercial:
  - decide dinero: reembolso caja, saldo CRM, saldo favor o mixto;
  - deja mercancia en cuarentena si no debe volver automaticamente a disponible;
  - no debe inventar disponibilidad.
- Inspeccion fisica:
  - decide destino real del producto;
  - puede mover inventario solo con kardex y trazabilidad;
  - puede abrir garantia/proveedor/reparacion solo con folio y responsable.

## Decisiones fisicas

### Mantener cuarentena

Estado actual:

- Implementado en UI y modelo.
- Crea inspeccion documental.
- Actualiza `erp_ventas_devoluciones_detalle.inspeccion_estado = cuarentena_confirmada`.
- No crea kardex.
- No mueve inventario.
- No crea garantia.

Uso:

- Producto pendiente de revision.
- Producto sospechoso.
- Falta evidencia, diagnostico o autorizacion.

### Reintegrar disponible

Objetivo:

- Regresar el producto a inventario vendible.

Reglas requeridas:

- Solo si la inspeccion confirma que el producto esta apto para venta.
- Requiere existencia origen o almacen destino valido.
- Debe crear kardex de entrada por devolucion inspeccionada.
- Debe ligar:
  - venta original;
  - devolucion;
  - detalle de devolucion;
  - inspeccion fisica;
  - movimiento inventario;
  - existencia agregada o unidad fisica.
- Si la venta salio de unidad fisica:
  - no reintegrar automaticamente sin validar etiqueta, estado, contenido y cierre/apertura.
- Si la venta salio de existencia agregada:
  - reintegrar a existencia original o a ubicacion de devoluciones aptas, segun politica.

Pendiente tecnico:

- Definir almacen/ubicacion destino:
  - misma tienda disponible;
  - almacen devoluciones aptas;
  - cuarentena liberada.
- Crear DDL si falta estado de destino final y folio de resolucion.
- Agregar modelo real transaccional con `FOR UPDATE`.
- Agregar UAT con SKU/folio/kardex.

### Merma

Objetivo:

- Sacar o clasificar mercancia no vendible por dano, caducidad, contaminacion o perdida.

Reglas requeridas:

- Requiere motivo, diagnostico y evidencia.
- Debe crear movimiento/kardex de merma si afecta existencia.
- Debe separar merma fiscal/contable futura de merma operativa inicial.
- Debe registrar responsable y autorizador.
- No debe impactar caja.
- No debe generar saldo cliente.

Pendiente tecnico:

- Definir si merma consume desde cuarentena fisica o solo documenta producto no reintegrado.
- Definir catalogo de causas de merma.
- Definir evidencia minima.
- Agregar reporte de mermas por tienda/SKU/responsable.

### Garantia proveedor

Objetivo:

- Convertir una devolucion fisica en reclamo a proveedor/fabricante.

Reglas requeridas:

- Debe vivir coordinado con modulo Garantias y Proveedores.
- Requiere folio de reclamo.
- Debe ligar:
  - cliente/venta;
  - producto/SKU;
  - proveedor preferente o proveedor de compra si existe trazabilidad;
  - evidencia;
  - decision de inventario temporal.
- No debe reintegrar disponible.
- No debe crear reembolso adicional sin decision financiera separada.

Pendiente tecnico:

- Crear/usar modulo Garantias.
- Definir estados:
  - abierto;
  - enviado_proveedor;
  - aceptado;
  - rechazado;
  - reemplazado;
  - bonificado;
  - cerrado.
- Definir impacto si proveedor repone producto o emite nota/credito.

### Reparacion

Objetivo:

- Mandar producto a diagnostico/reparacion interna o externa.

Reglas requeridas:

- Requiere folio de reparacion.
- Requiere responsable/tecnico/proveedor.
- Debe conservar producto fuera de disponible.
- Puede terminar en:
  - reintegrar disponible;
  - merma;
  - garantia proveedor;
  - devolver al cliente;
  - reemplazo.

Pendiente tecnico:

- Definir si reparacion vive dentro de Garantias o como subflujo de Postventa.
- Definir costos de reparacion y si afectan margen/reporte.
- Definir evidencia de recepcion/entrega.

### Rechazo inspeccion

Objetivo:

- Marcar que la devolucion fisica no procede para cierto destino.

Reglas requeridas:

- No debe deshacer la devolucion financiera por si sola.
- Debe explicar decision.
- Debe dejar siguiente accion requerida:
  - mantener cuarentena;
  - escalar supervisor;
  - generar reclamo;
  - aclaracion con cliente.

## Fases recomendadas

### Fase 1 - Cierre operacional de cuarentena

Ya avanzado:

- UI para `mantener_cuarentena`.
- Bandeja de pendientes fisicos limpia.
- Ticket/reversa/evidencia caja validados.

Siguiente validacion:

- Probar manualmente UI con una devolucion nueva de UAT.
- Confirmar que el cajero/supervisor entiende que no se mueve inventario.

### Fase 2 - Reintegrar disponible

Entregable:

- Dry-run y ejecucion real protegida.
- Kardex de entrada.
- Reporte de reintegros por devolucion.
- UI con confirmacion fuerte.

Autorizacion requerida:

- DDL/modelo real por mover inventario.
- UAT con stock antes/despues, kardex y folio.

### Fase 3 - Merma

Entregable:

- Catalogo de causas.
- Evidencia obligatoria.
- Kardex/registro de merma.
- Reporte gerencial.

Autorizacion requerida:

- DDL/modelo real por afectar inventario y control interno.

### Fase 4 - Garantia proveedor / reparacion

Entregable:

- Contrato con modulo Garantias.
- Folios de reclamo/reparacion.
- Estados y responsables.
- Reportes de pendientes por proveedor/SKU/cliente.

Autorizacion requerida:

- Crear/ajustar esquema de garantias y postventa.

## UAT minima por decision

Cada decision debe documentar:

- SKU.
- Folio venta.
- Folio devolucion.
- Id detalle devolucion.
- Folio inspeccion.
- Usuario inspector.
- Estado antes/despues.
- Si movio inventario:
  - existencia antes;
  - existencia despues;
  - kardex;
  - referencia.
- Si genero garantia/reparacion:
  - folio;
  - responsable;
  - estado.

## Siguiente paso recomendado

Preparar Fase 2 `reintegrar_disponible` en modo read-only:

- auditar tablas/columnas actuales;
- definir contrato de kardex;
- generar dry-run enriquecido;
- preparar DDL si falta trazabilidad destino;
- no ejecutar BD hasta autorizacion fuerte.

## Avance 2026-07-09 - Bandeja destino final read-only

## Avance 2026-07-09 - Pieza lista para prueba real controlada

Alcance que ya puede probarse desde navegador:

- Ruta: `/ventas/devoluciones`.
- Seccion: `Devoluciones fisicas pendientes` + `Inspeccion fisica`.
- Accion real habilitada: `Confirmar cuarentena`.
- Permiso requerido: `ventas.operar`.
- Seguridad: POST con CSRF desde `window.ERP_CSRF_TOKEN`.
- Resultado esperado:
  - crea inspeccion fisica documental;
  - cambia la partida a `cuarentena_confirmada`;
  - deja trazabilidad con usuario, fecha, motivo y diagnostico;
  - no mueve inventario;
  - no crea kardex;
  - no reintegra disponible;
  - no genera merma, garantia ni reparacion.

Pasos de prueba manual:

1. Entrar a `Ventas > Devoluciones`.
2. En `Devoluciones fisicas pendientes`, filtrar:
   - decision inventario: `Cuarentena`;
   - estado inspeccion: `Pendiente`.
3. Presionar `Consultar`.
4. Si existe una partida pendiente, presionar `Inspeccionar`.
5. Revisar motivo y diagnostico.
6. Presionar `Prevalidar`.
7. Si la prevalidacion es correcta, presionar `Confirmar cuarentena`.
8. Confirmar el mensaje del navegador.
9. Volver a consultar con estado `Pendiente`; la partida ya no debe aparecer.
10. Consultar con estado `Cuarentena confirmada`; la partida debe aparecer como pendiente de destino final.

Interpretacion operativa:

- Esta parte ya sirve para que el equipo deje constancia formal de que recibio fisicamente un producto devuelto y que queda detenido.
- Todavia no decide el destino final del producto.
- La decision final debe quedar en otra fase porque puede afectar inventario, kardex, garantia/proveedor, reparacion, merma y reportes.

Bloqueo conocido antes de operar destino final:

- No usar botones directos para `reintegrar`, `merma`, `garantia` o `reparacion` hasta cerrar el modelo transaccional y UAT con kardex.

## Avance 2026-07-09 - Dry-run destino final de cuarentena

Implementado sin escrituras:

- Modelo: `VentasErp::destinoFinalCuarentenaDevolucionDryRun`.
- Endpoint: `/ventas/devolucion_destino_final_dryrun_erp`.
- Vista: `/ventas/devoluciones`, panel `Destino final de cuarentena`.
- Script UAT read-only: `storage/uat/uat_ventas_pos_destino_final_cuarentena_dryrun.php`.

Contrato:

- Solo analiza partidas en `cuarentena_confirmada`.
- No crea kardex.
- No mueve inventario.
- No cambia devolucion.
- No crea garantia, reparacion ni merma.
- Informa bloqueos, avisos, DDL requerido y plan de aplicacion futura.

UAT read-only ejecutada con:

- `id_devolucion_detalle=3`.
- Folio devolucion: `DEV-20260707-000001`.
- SKU: `TP-40352-500GR`.
- Existencia: `EXI-1016-34`.

Resultados:

- `reintegrar_disponible`:
  - bloqueos: ninguno;
  - plan: entrada a existencia `34`;
  - cantidad `1`;
  - existencia `0 -> 1`;
  - disponible `0 -> 1`;
  - requiere kardex en apply real.
- `merma`:
  - bloqueos: ninguno;
  - aviso: requiere causa, evidencia y autorizador;
  - no incrementa disponible.
- `garantia_proveedor`:
  - bloqueos: ninguno;
  - aviso: requiere folio operativo fuera de disponible.
- `reparacion`:
  - bloqueos: ninguno;
  - aviso: requiere folio operativo fuera de disponible.

DDL requerido antes de apply real:

- `erp_ventas_devoluciones_detalle.destino_final`.
- `erp_ventas_devoluciones_detalle.fecha_destino_final`.
- `erp_ventas_devoluciones_detalle.resuelto_por`.

Siguiente autorizacion robusta sugerida:

```text
AUTORIZO PREPARAR DDL DESTINO FINAL CUARENTENA POS usando respaldo UAT POS vigente con token VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL para UAT POS
```

Despues del DDL:

- preparar apply real de `reintegrar_disponible`;
- generar kardex de entrada;
- actualizar detalle de devolucion con destino final;
- ligar inspeccion, devolucion, venta, movimiento inventario y usuario;
- validar stock antes/despues.

## Avance 2026-07-09 - DDL destino final preparado

Autorizacion recibida:

```text
AUTORIZO PREPARAR DDL DESTINO FINAL CUARENTENA POS usando respaldo UAT POS vigente con token VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL para UAT POS
```

Cambios preparados sin aplicar BD:

- `VentasErpEsquema::planActualizarDestinoFinalCuarentenaPos`.
- `VentasErpEsquema::auditarDestinoFinalCuarentenaPos`.
- Endpoint read-only:
  - `/ventas/esquema_auditar_destino_final_cuarentena_pos`.
- Endpoint protegido para plan/apply:
  - `/ventas/esquema_actualizar_destino_final_cuarentena_pos`.
- Script read-only:
  - `storage/uat/uat_ventas_pos_destino_final_cuarentena_ddl_prepare.php`.

Auditoria:

- Tablas existentes:
  - `erp_ventas_devoluciones_detalle`;
  - `erp_ventas_devoluciones_inspecciones`;
  - `erp_inventario_existencias`;
  - `erp_inventario_movimientos`.
- Columnas existentes de base:
  - `inspeccion_estado`;
  - `id_inspeccion_fisica`;
  - `id_movimiento_inventario`.
- Faltantes para destino final:
  - `erp_ventas_devoluciones_detalle.destino_final`;
  - `erp_ventas_devoluciones_detalle.fecha_destino_final`;
  - `erp_ventas_devoluciones_detalle.resuelto_por`;
  - `erp_ventas_devoluciones_detalle.motivo_destino_final`;
  - `erp_ventas_devoluciones_detalle.id_movimiento_inventario_destino_final`;
  - `erp_ventas_devoluciones_inspecciones.destino_final`;
  - `erp_ventas_devoluciones_inspecciones.fecha_resolucion_destino`;
  - `erp_ventas_devoluciones_inspecciones.resuelto_por`.
- Indices faltantes:
  - `idx_devolucion_detalle_destino_final`;
  - `idx_devolucion_detalle_mov_destino`;
  - `idx_devolucion_inspeccion_destino`.

Validaciones:

- `php -l app/modelos/VentasErpEsquema.php`: sin errores.
- `php -l app/controladores/Ventas.php`: sin errores.
- `php -l storage/uat/uat_ventas_pos_destino_final_cuarentena_ddl_prepare.php`: sin errores.
- Script prepare con token valido: `ok=true`, `read_only=true`, SQL generado sin ejecutar.

Siguiente autorizacion para aplicar DDL:

```text
AUTORIZO APLICAR DDL DESTINO FINAL CUARENTENA POS usando respaldo UAT POS vigente con token VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL para UAT POS
```

- Se agrego filtro read-only `inspeccion_estado` a la consulta de devoluciones fisicas.
- La UI de `Ventas > Devoluciones` ahora permite filtrar:
  - pendientes;
  - cuarentena confirmada;
  - todos.
- Las partidas con inspeccion distinta de `pendiente` ya no muestran boton `Inspeccionar`.
- En su lugar muestran `Destino final pendiente`.
- Evidencia read-only:
  - `cuarentena + pendiente`: `0` partidas;
  - `cuarentena + cuarentena_confirmada`: `3` partidas, cantidad `3`, importe historico `$885.00`.

Partidas en cuarentena confirmada:

- `DEV-20260707-000001`, detalle `3`, inspeccion `IFD-20260707-000001`, financiera `mixta_saldo_crm`.
- `DEV-20260630-000002`, detalle `2`, inspeccion `IFD-20260630-000001`, financiera `reembolso_caja`.
- `DEV-20260630-000001`, detalle `1`, inspeccion `IFD-20260709-000001`, financiera `saldo_favor`.

Contrato de esta fase:

- Solo consulta y clasifica.
- No mueve inventario.
- No crea kardex.
- No cambia devoluciones.
- Prepara la fase de destino final.
