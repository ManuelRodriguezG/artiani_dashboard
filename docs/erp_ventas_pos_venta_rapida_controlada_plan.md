# ERP Ventas/POS - Venta rapida controlada

Documento vivo. Ultima actualizacion: 2026-07-23.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico local: `http://panel.com.local/`.

## Objetivo

Permitir que la tienda pueda vender durante el arranque operativo aunque un producto aun no este capturado correctamente en Catalogo ERP, sin perder control financiero, auditoria ni seguimiento para completar el dato maestro despues.

Esto no debe ser una venta libre sin trazabilidad. Debe ser una venta rapida controlada: POS cobra, ticket documenta, caja cuadra y Catálogo recibe un pendiente accionable para crear o vincular el SKU correcto.

## Nombre operativo recomendado

En POS:

- `Venta rapida`
- `Producto por clasificar`

En Catálogo/pendientes:

- `Pendiente de clasificacion POS`
- `Producto vendido sin SKU definitivo`

## Reglas principales

- Solo aplica a canal POS/mostrador.
- No aplica a ecommerce.
- No crea productos definitivos automaticamente.
- No inventa proveedor, marca, costo ni garantia como datos maestros.
- No descuenta inventario de un SKU definitivo si el SKU no existe o no fue vinculado.
- Debe quedar ligado a venta, detalle de venta, operador, sucursal, almacen, caja y turno.
- Debe generar alerta o pendiente para Catálogo ERP.
- Si el producto controla inventario, debe generar tambien pendiente para Inventario/Existencias o quedar marcado para regularizacion posterior.
- Si requiere proveedor, lista de precios, garantia o costo, Catálogo debe completar esos datos al resolver.

## Campos minimos en POS

Obligatorios:

- Descripcion detallada del producto.
- Cantidad.
- Precio unitario.
- Motivo: producto no registrado, codigo no encontrado, urgencia de venta u otro motivo controlado.
- Confirmacion del operador.

Recomendados:

- Codigo de barras capturado, si existe.
- Categoria provisional.
- Marca provisional.
- Proveedor probable.
- Foto del producto o etiqueta.
- Costo estimado, si el operador lo conoce y la politica lo permite.
- Observaciones para Catálogo.

## Flujo POS

1. El operador intenta buscar o escanear el producto.
2. Si no existe en Catalogo ERP, usa `Venta rapida`.
3. Captura descripcion detallada, cantidad, precio y motivo.
4. POS previsualiza el impacto: venta sin SKU definitivo, sin descuento automatico de inventario y con pendiente a Catálogo.
5. El operador confirma.
6. POS cobra la venta real y genera ticket.
7. El detalle queda como `Producto por clasificar`.
8. Se genera pendiente para Catálogo ERP con folio y evidencia de venta.

## Flujo posterior en Catálogo

1. Catálogo consulta pendientes POS.
2. Decide si el producto ya existe o si debe crearse nuevo SKU.
3. Si existe, vincula el pendiente al SKU correcto.
4. Si no existe, crea SKU con datos completos: categoria, marca, unidad, codigos, proveedor, imagen, reglas de inventario y garantias.
5. Completa relacion con proveedor y costos cuando aplique.
6. Define lista de precios o confirma precio publico.
7. Cierra el pendiente conservando el texto original capturado en POS como snapshot.

## Inventario y regularizacion

Si al vender rapido no existe SKU definitivo, POS no puede hacer kardex contra un producto real. Por eso el movimiento correcto es:

- La venta queda cobrada y auditada.
- El detalle queda pendiente de clasificacion.
- Inventario recibe pendiente si el producto debe controlar existencias.
- Al resolverse el SKU, Inventario decide como regularizar: recepcion pendiente, ajuste inicial, conteo fisico, salida historica o incidencia.

Cuando el producto si existe pero no tiene stock suficiente, no debe usarse venta rapida. Debe usarse el flujo ya preparado de inventario pendiente POS.

## Controles recomendados

- Permiso: `ventas.pos.venta_rapida.crear`.
- Permiso supervisor para montos altos o productos sensibles.
- Limite por linea.
- Limite por turno.
- Motivo obligatorio.
- Reporte de ventas rapidas por operador, caja, sucursal y monto.
- Alerta visible hasta que Catálogo cierre el pendiente.
- Bloqueo para devoluciones complejas hasta clasificar o decision supervisor.
- Garantia provisional solo si se selecciona politica manual permitida.

## Reportes necesarios

- Ventas rapidas abiertas.
- Ventas rapidas por antiguedad.
- Ventas rapidas por operador.
- Ventas rapidas por sucursal/caja/turno.
- Monto vendido sin SKU definitivo.
- Pendientes ya vinculados a SKU.
- Pendientes que requieren proveedor/costo/lista de precio/garantia.

## UAT requerida

- Crear venta rapida con descripcion obligatoria.
- Bloquear venta rapida sin precio.
- Bloquear venta rapida sin motivo.
- Cobrar venta rapida y validar caja/ticket.
- Validar que no genera kardex de SKU inexistente.
- Validar pendiente a Catálogo.
- Vincular pendiente a SKU existente.
- Crear SKU desde pendiente.
- Validar reporte de pendientes.
- Validar devolucion de venta rapida antes y despues de clasificar.

## Estado tecnico 2026-07-23

DDL aplicado en UAT POS con autorizacion:

```text
AUTORIZO APLICAR DDL VENTA RAPIDA CONTROLADA POS usando respaldo UAT POS vigente con token VENTAS_POS_VENTA_RAPIDA_DDL confirmacion="APLICAR VENTA RAPIDA POS" para UAT POS/Catalogo/Inventario
```

Resultado:

- Tablas creadas: `erp_pos_venta_rapida_pendientes`, `erp_pos_venta_rapida_eventos`.
- Columnas agregadas a `erp_ventas_detalle` para identificar `origen_partida`, pendiente relacionado, descripcion manual y regularizacion de inventario.
- Indices aplicados para consultar por folio, venta/detalle, estatus, almacen, operador, codigo de barras y SKU resuelto.
- Auditoria posterior: `tablas_faltantes=0`, `columnas_faltantes=0`, `indices_faltantes=0`.
- No se creo venta, no se creo SKU definitivo, no se movio caja, no se movio inventario y no se toco ecommerce.

Modelo real preparado con autorizacion:

```text
AUTORIZO PREPARAR MODELO REAL VENTA RAPIDA CONTROLADA POS usando respaldo UAT POS vigente con token VENTAS_POS_VENTA_RAPIDA_REAL_MODELO para UAT POS/Catalogo/Inventario
```

Alcance preparado:

- `confirmarVentaPosReal` puede aceptar partidas `venta_rapida` solo con autorizacion explicita.
- Inserta detalle provisional en `erp_ventas_detalle` sin `id_sku_erp` ni `id_producto_erp`.
- Marca `tipo_partida='venta_rapida'` y `origen_partida='venta_rapida_controlada'`.
- Crea pendiente `VRP-YYYYMMDD-000001` en `erp_pos_venta_rapida_pendientes`.
- Registra evento en `erp_pos_venta_rapida_eventos`.
- Genera notificacion operativa para Catalogo si existe `erp_notificaciones`.
- No genera kardex ni snapshot de garantia para la partida provisional porque no hay SKU definitivo.
- UI mantiene el cobro bloqueado hasta autorizar exposicion/UAT real.

Semaforo read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_rapida_modelo_real_readiness_readonly.php
```

## UAT real 2026-07-23

Autorizacion ejecutada:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT venta rapida controlada"

AUTORIZO EJECUTAR UAT REAL VENTA RAPIDA CONTROLADA POS usando respaldo UAT POS vigente con token VENTAS_POS_VENTA_RAPIDA_REAL id_usuario=1 descripcion="Producto UAT por clasificar" cantidad=1 precio=100 pago=100 motivo="UAT venta rapida controlada" para UAT POS/Catalogo/Inventario
```

Evidencia:

- Turno abierto: `TUR-20260723-002-001`, `id_turno_caja=26`, caja `2`, almacen `5`, monto inicial `$500.00`.
- Venta creada: `POS-20260723-000001`, `id_venta=27`, estatus `pagada`, total `$100.00`.
- Detalle creado: `id_venta_detalle=28`, `sku='VENTA-RAPIDA'`, `tipo_partida='venta_rapida'`, `origen_partida='venta_rapida_controlada'`.
- Pendiente creado: `VRP-20260723-000001`, `id_venta_rapida_pendiente=1`, estatus `pendiente_catalogo`, inventario `pendiente_regularizacion`.
- Evento creado: `pendiente_creado`.
- Notificacion creada: `id_notificacion=38`, tipo `pendiente_catalogo_pos`, area `catalogo`, estatus `pendiente`.
- Caja: movimiento apertura `55`, movimiento venta `56`, turno con `monto_esperado=$600.00`.
- Inventario: `0` movimientos en `erp_inventario_movimientos` para esta venta rapida; correcto porque no hay SKU definitivo.
- Garantias: no se genero snapshot; correcto porque no hay SKU definitivo.
- Turno cerrado: `TUR-20260723-002-001`, monto contado `$600.00`, diferencia `$0.00`, sin turnos abiertos para caja `2`/almacen `5`.
- UI expuesta con autorizacion `VENTAS_POS_VENTA_RAPIDA_UI_REAL`: el controlador inyecta token interno cuando detecta `venta_rapida`, el JS permite cobrar y muestra aviso de pendiente a Catalogo/Inventario.

## Siguiente autorizacion fuerte

Para ejecutar UAT real desde la pantalla `/ventas/pos`:

```text
AUTORIZO UAT UI REAL VENTA RAPIDA POS usando respaldo UAT POS vigente con token VENTAS_POS_VENTA_RAPIDA_UI_REAL id_usuario=1 monto_inicial=500 descripcion="Producto UAT UI por clasificar" cantidad=1 precio=100 pago=100 motivo="UAT UI venta rapida controlada" para UAT POS/Catalogo/Inventario
```
