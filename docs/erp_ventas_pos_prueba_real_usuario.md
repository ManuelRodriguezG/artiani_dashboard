# ERP Ventas POS - Prueba real guiada para usuario

Documentacion IA: Codex GPT-5, 2026-07-16.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico: `http://panel.com.local/`.

## Antes de iniciar

- Entra por `http://panel.com.local/ventas/pos`.
- Confirma que arriba se vea tu usuario/sesion correcta.
- Confirma que el POS muestre la tienda/caja asignada, no selector libre.
- No uses `localhost`.

Semaforo tecnico previo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_final_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
```

Resultado requerido:

- `ok=true`.
- `pase_uat_multiusuario_listo=true`.
- `pos_base_productivo_sin_bloqueos=true`.
- `bloqueos=[]`.

Paquete de autorizacion previo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_autorizacion_ciclo_multiusuario_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad_stock=1 --pago=295 --monto_inicial=500 --monto_contado=795
```

Resultado requerido:

- `ok=true`.
- guardrail sin token: `bloqueado=true`.
- alcance claro antes de autorizar: abre turno, carga stock, cobra atencion, mueve caja/inventario y cierra turno.

## Prueba A - Ver cuenta pendiente

1. Abre POS.
2. Entra a `Cuentas pendientes`.
3. Busca la cuenta `ATN-20260713-210522-889`.
4. Confirma:
   - cliente `Cliente UAT Atencion Multiusuario`;
   - total `$295.00`;
   - 1 partida;
   - estado pendiente/lista para cobro.

Resultado esperado:

- Puedes verla sin que se cobre.
- No cambia inventario ni caja solo por verla.

## Prueba B - Cargar cuenta a carrito

1. Desde `Cuentas pendientes`, usa la accion para cargar la cuenta.
2. Revisa el carrito.
3. Confirma:
   - aparece SKU `1760`;
   - cantidad `1`;
   - precio `$295.00`;
   - total del carrito `$295.00`.

Resultado esperado:

- El POS muestra que la cuenta viene de una atencion compartida.
- Si modificas partidas, debe desvincularse de la atencion original.

## Prueba C - Cobro despues de UAT autorizada

Esta prueba se hace despues de ejecutar la autorizacion agrupada del ciclo real.

1. Regresa a `Cuentas pendientes`.
2. Confirma que la cuenta ya no aparezca como pendiente, o que aparezca como convertida segun la UI disponible.
3. Busca el folio de venta generado.
4. Abre ticket formal/reimpresion si la vista lo permite.
5. Revisa:
   - folio POS;
   - caja/turno;
   - operador;
   - cliente;
   - SKU y descripcion;
   - total y pago;
   - garantia o leyenda de garantia.

Resultado esperado:

- La venta existe.
- La cuenta quedo convertida.
- El ticket se puede consultar/reimprimir de forma controlada.

Autorizacion agrupada que habilita esta prueba:

```text
AUTORIZO EJECUTAR CICLO REAL ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL id_usuario=1 id_almacen=5 id_sku=1760 id_atencion=2 cantidad_stock=1 pago=295 monto_inicial=500 monto_contado=795 para UAT POS
```

## Prueba D - Caja y cierre

1. Entra a `Ventas > Caja/Turnos`.
2. Revisa el turno de la caja.
3. Confirma que el cierre exista despues del ciclo autorizado.
4. Revisa:
   - monto inicial `$500.00`;
   - venta/pago `$295.00`;
   - monto contado `$795.00`;
   - diferencia `$0.00`.

Resultado esperado:

- El turno queda cerrado.
- La diferencia queda en cero para esta UAT.
- Si hubiera diferencia, el sistema debe permitir cerrar y dejarla para reportes/revision.

## Prueba E - Inventario

Antes del ciclo real, el check read-only indica:

- SKU `1760` tiene `0` disponible en almacen `5`.
- No hay reservas activas.
- No hay pendientes POS abiertos.

1. Entra a Inventario/Existencias.
2. Busca SKU `1760`.
3. Revisa movimientos/kardex.
4. Confirma que exista salida por venta POS con el folio generado.

Resultado esperado:

- El stock cargado para UAT se descuenta.
- La salida queda referenciada al folio POS.
- No debe quedar pendiente POS de inventario para esta venta si hubo stock suficiente.

## Evidencia que conviene anotar

- Folio de venta POS.
- Folio de turno.
- Folio de atencion.
- SKU vendido.
- Monto cobrado.
- Resultado del ticket.
- Resultado de kardex.
- Diferencia de cierre.

Comando read-only para recolectar evidencia tecnica:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ciclo_evidencia_readonly.php --id_atencion=2 --id_usuario=1
```

Comando read-only de cierre final despues de la prueba:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_final_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
```

Comando read-only si la prueba se interrumpe antes de terminar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ciclo_recuperacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2
```

Ese comando solo diagnostica. No cierra caja, no cobra, no corrige inventario y no modifica la atencion.

## Resultado de la prueba real ya ejecutada

La prueba real de esta guia ya fue ejecutada en `C:\xampp\htdocs\panel_de_control`.

Resultado vigente:

- Cuenta `ATN-20260713-210522-889`: convertida.
- Venta generada: `POS-20260716-000001`.
- Turno: `TUR-20260716-002-001`.
- Cierre de caja: esperado `$795.00`, contado `$795.00`, diferencia `$0.00`.
- Ticket formal: disponible y sin hallazgos.
- Kardex: salida de SKU `1760` por `1.000 kg` ligada al folio POS.
- Garantia snapshot: generado como `Sin garantia`.

Para repetir una prueba similar se debe crear una nueva atencion/cuenta; no reutilizar `id_atencion=2`.