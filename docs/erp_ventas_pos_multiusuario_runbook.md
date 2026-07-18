# Runbook Piloto POS Multiusuario

## Objetivo

Validar que varios usuarios puedan operar el POS sobre la misma tienda/caja/terminal sin perder trazabilidad:

- quien creo la atencion;
- quien tomo la cuenta;
- quien cobro;
- que turno/caja recibio el movimiento;
- que venta genero kardex, ticket y caja.

## Alcance Del Piloto

Incluido:

- 1 tienda/almacen.
- 1 caja.
- 1 terminal POS.
- 2 o 3 usuarios con sesion propia.
- Venta normal con stock disponible.
- Escaneo o busqueda para agregar productos.
- Cobro real.
- Cierre de turno con diferencia permitida si existe.

Fuera del primer piloto:

- Inventario pendiente productivo.
- Devoluciones reales.
- Apartados nuevos.
- Descuentos libres o precios manuales sin folio autorizado.
- Cambios directos de inventario desde POS.

## Precondiciones

Ejecutar semaforo general:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_go_nogo_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --id_atencion=2 --cantidad=1 --usuarios=1,2,3
```

Para piloto multiusuario deben cumplirse estas condiciones:

- Usuarios participantes activos.
- Usuarios con rol/permisos de ventas.
- Usuarios asignados al mismo almacen/caja/terminal.
- Stock disponible para el SKU de prueba.
- Turno abierto en la caja antes de cobrar.

## Flujo Sugerido

1. Supervisor abre turno desde Caja/Turnos.
2. Usuario A entra al POS con su propia sesion.
3. Usuario A crea una atencion/cuenta con cliente identificable.
4. Usuario A agrega una partida por buscador, escaner fisico o boton de camara `F3`.
5. Usuario B entra al POS con su propia sesion.
6. Usuario B consulta atenciones.
7. Usuario B toma la atencion de Usuario A.
8. Usuario B revisa partidas, cantidades, cliente y total.
9. Usuario B cobra.
10. Supervisor revisa ticket, venta, caja, kardex y cierre.
11. Supervisor cierra turno aunque exista diferencia, dejando observacion.

## Evidencia Minima

- Usuario que abre turno.
- Folio de turno.
- Usuario que crea atencion.
- Folio/id de atencion.
- Usuario que toma la atencion.
- Usuario que cobra.
- Folio POS.
- Metodo de pago.
- Ticket consultable.
- Movimiento de caja.
- Kardex de salida.
- Garantia snapshot si aplica.
- Monto esperado, contado y diferencia.

## Criterios De Aceptacion

El piloto pasa si:

- Cada usuario opera con su propia sesion.
- El POS no permite operar usuarios sin asignacion oficial.
- La atencion no se duplica al tomar/cobrar.
- El cobro queda ligado al turno correcto.
- La venta descuenta inventario con kardex.
- El ticket muestra folio formal y datos suficientes.
- El cierre de caja permite diferencia y la deja trazable.

## Autorizacion Para Habilitar Usuarios

Si el preflight detecta usuarios activos sin rol/asignacion POS, usar autorizacion fuerte:

```text
AUTORIZO HABILITAR USUARIOS POS PILOTO usando respaldo UAT POS vigente con token VENTAS_POS_MULTIUSUARIO_PILOTO id_usuarios=2,3 id_rol_ventas=6 id_almacen=5 id_caja=2 id_terminal=2 para UAT POS
```

## Estado Post-Habilitacion

Usuarios listos para piloto multiusuario en almacen `5`, caja `2`, terminal `2`:

- Usuario `1`: listo.
- Usuario `2`: listo, asignacion `id_usuario_caja=4`.
- Usuario `3`: listo, asignacion `id_usuario_caja=5`.

Pendientes antes de cobro real:

- Cargar stock o elegir SKU con existencia disponible.
- Abrir turno de caja.
- Mantener fuera del piloto devoluciones, apartados nuevos, descuentos libres e inventario pendiente productivo.

## Readiness Compacto Para Venta Piloto

Antes de cargar stock o abrir turno, se puede ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_multiusuario_venta_readiness_readonly.php --id_usuario_supervisor=1 --id_usuario_cobra=2 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --monto_inicial=500 --usuarios=1,2,3 --cliente="Cliente UAT POS multiusuario" --compact=1
```

Orden esperado para el piloto:

1. Cargar stock UAT o usar SKU con disponible.
2. Abrir turno.
3. Repetir readiness compacto.
4. Ejecutar venta/cobro real desde POS UI o aplicador autorizado.
5. Cerrar turno.

## Estado Del Piloto Actual

Venta real ejecutada:

- Turno `TUR-20260717-002-001`, `id_turno_caja=24`, cerrado.
- Caja `2`, almacen `5`, terminal `2`.
- Usuarios `1`, `2`, `3` listos para piloto.
- Usuario cobrador UAT: `2`.
- Venta `POS-20260717-000001`, `id_venta=25`, estatus `pagada`.
- Movimiento caja `52`, pago efectivo `$295.00`.
- Kardex salida `97`, existencia `34` de `1.0000` a `0.0000`.
- Ticket formal generado sin hallazgos.
- Cierre: esperado `$795.00`, contado `$795.00`, diferencia `$0.00`.

Piloto multiusuario base cerrado correctamente.

Siguiente foco operativo: UAT productiva de inventario pendiente con turno nuevo.

## Semaforo Estado Actual

Para evitar que pruebas historicas contaminen el pase del piloto, usar el semaforo por folio actual:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_multiusuario_estado_actual_readonly.php --folio=POS-20260717-000001 --id_usuario_supervisor=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --usuarios=1,2,3 --monto_contado=795
```

Estado vigente:

- Turno piloto cerrado despues del semaforo inicial.
- Venta pagada, ticket formal, kardex, garantia snapshot y pago de caja completos.
- Usuarios `1`, `2`, `3` habilitados para piloto.
- Readiness productivo sin bloqueos.
- Turno `TUR-20260717-002-001` cerrado con diferencia `$0.00`.
- Aviso no bloqueante: queda 1 evidencia historica de caja pendiente por administracion.
