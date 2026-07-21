# ERP Ventas/POS - Guia primer turno piloto

Documento vivo. Ultima actualizacion: 2026-07-20.

Proyecto: `C:\xampp\htdocs\panel_de_control`.

URL local: `http://panel.com.local/`.

## Antes de empezar

Ejecutar semaforo consolidado ampliado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Debe salir:

- `ok=true`.
- `scripts_total=26`.
- `bloqueos_total=0`.
- `decision=pos_apto_para_piloto_controlado_con_condiciones`.

Ejecutar preflight compacto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_preflight_compacto_readonly.php
```

Debe salir:

- `ok=true`.
- `puede_iniciar_piloto_controlado=true`.
- `bloqueos=[]`.

Avisos esperados al corte vigente:

- No hay turno abierto antes de iniciar.
- Existe pendiente `PINV-20260717-000001`.
- Existe evidencia administrativa `GASTO-UAT-001`.
- Usuario `3` puede mostrar caracteres raros en nombre visible.

Ejecutar plan de accion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --usuarios=1,2,3
```

Debe salir `ok=true`, `pendientes_total=5` y `acciones_total=7`.

Ejecutar paquete de autorizacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_autorizacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --cantidad_fisica=CONTEO_REAL --monto_contado=MONTO_CONTADO_REAL
```

Debe salir `ok=true`, `decision=paquete_autorizacion_preparado`, `pasos_total=6` y `bloqueos_total=0`.

## Preparacion administrativa recomendada

Antes de abrir el turno piloto amplio, conviene atender estos puntos:

1. Resolver o mantener documentado `PINV-20260717-000001`.
2. Registrar evidencia administrativa de `GASTO-UAT-001`.
3. Corregir visualmente el nombre del usuario `3` desde Seguridad > Usuarios.
4. Cargar stock real o UAT suficiente para el SKU que se usara en la prueba.

Si se hara un piloto muy controlado, los puntos 1 y 2 pueden quedar como pendientes visibles, pero no deben ignorarse ni borrarse.

## Autorizacion agrupada sugerida

El paquete de autorizacion genera frases humanas por paso. Para avanzar ordenado, usar este bloque como guia:

```text
AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario=1 folio=PINV-20260717-000001 cantidad_fisica=CONTEO_REAL decision=ajustar_a_conteo confirmacion="RESOLVER PENDIENTE" motivo="Resolver mini inventario POS pendiente antes de piloto"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-PILOTO-AAAAMMDD-A5-S1760

AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura piloto POS"
```

Despues de vender:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=MONTO_CONTADO_REAL observaciones="Cierre piloto POS"
```

Nota: el cierre puede quedar con diferencia. Esa diferencia es informacion operativa y debe revisarse en reportes, no corregirse manualmente.

## Apertura

1. Entrar a `http://panel.com.local/` con usuario propio.
2. Ir a `Ventas > Caja y turnos`.
3. Confirmar visualmente sucursal, caja y terminal.
4. Capturar monto inicial real contado.
5. Validar apertura.
6. Escribir `ABRIR TURNO`.
7. Abrir turno real.

## Venta piloto

1. Ir a `Ventas > POS`.
2. Confirmar que el operador visible sea el usuario correcto.
3. Confirmar que no se este eligiendo libremente tienda/caja desde POS; debe venir por configuracion/asignacion.
4. Buscar producto por texto, SKU o scanner POS.
5. Agregar solo productos con existencia disponible.
6. Revisar cantidad, precio, descuento y total antes de pago.
7. Capturar pago.
8. Prevalidar.
9. Cobrar.
10. Revisar ticket y, si aplica, imprimirlo.

Si hay duda durante el uso, abrir `Ventas > Manual POS`. Ese manual explica Prevalidar, Cobrar, Cliente, Autorizar, Atenciones, pagos rapidos, `Compromiso`, stock, pieza, granel e inventario pendiente.

Atajos utiles:

- `F2` o `Ctrl+K`: enfocar buscador de productos.
- `F3`: abrir scanner POS con camara.
- `F6`: enfocar monto de pago.
- `F9`: prevalidar.
- `Ctrl+Enter`: cobrar.
- `F8`: movimientos de caja.
- `F10`: pedidos/apartados.

## Que no usar en el primer turno

- Devoluciones reales.
- Apartados nuevos.
- Descuentos libres.
- Inventario pendiente como operacion normal.
- Cambios manuales de precio sin politica/autorizacion.

## Cierre

1. Ir a `Ventas > Caja y turnos`.
2. Validar corte.
3. Contar efectivo real.
4. Capturar monto contado real.
5. Escribir observaciones.
6. Escribir `CERRAR TURNO`.
7. Cerrar turno real.

La caja puede cerrar aunque no cuadre en cero. La diferencia queda registrada para revision; no se borra ni se corrige manualmente.

## Despues del cierre

Ejecutar postcheck compacto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760
```

Debe salir sin bloqueos. Puede salir con observaciones administrativas si hay evidencias o pendientes visibles.

Ejecutar pendientes piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=1760 --usuarios=1,2,3
```

Debe mostrar si quedaron pendientes de inventario, evidencia, turno o usuario.

## Criterio de exito

- La venta aparece en reportes.
- El ticket se puede consultar.
- Kardex/trazabilidad quedan visibles.
- El turno queda cerrado.
- Si hay diferencia, queda registrada.
- Si hay pendiente de inventario, queda visible para Inventario/Existencias.

## Evidencia minima a anotar

- Folio del turno.
- Usuario que abrio turno.
- Usuario que cobro.
- Folio POS.
- SKU vendido.
- Total cobrado.
- Metodo de pago.
- Monto inicial.
- Monto contado.
- Diferencia.
- Observaciones.
- Si el ticket se pudo ver/imprimir.
- Si el kardex/trazabilidad aparecen en postcheck.
