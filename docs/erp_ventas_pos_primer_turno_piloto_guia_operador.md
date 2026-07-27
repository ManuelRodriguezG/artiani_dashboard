# ERP Ventas/POS - Guia primer turno piloto

Documento vivo. Ultima actualizacion: 2026-07-26.

Proyecto: `C:\xampp\htdocs\panel_de_control`.

URL local: `http://panel.com.local/`.

## Antes de empezar

Ejecutar semaforo consolidado ampliado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Debe salir:

- `ok=true`.
- `scripts_total=30`.
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

Avisos esperados al corte vigente posterior a limpieza:

- No hay turno abierto antes de iniciar.
- No quedan pendientes historicos de inventario POS abiertos.
- No quedan evidencias de caja pendientes.
- Usuarios 1, 2 y 3 no presentan problemas visuales en el preflight vigente.

Ejecutar plan de accion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --usuarios=1,2,3
```

Para el piloto limpio con SKU `173`, debe salir `ok=true`, `pendientes_total=1` y la accion real esperada debe ser abrir turno antes de cobrar.

Ejecutar operacion basica:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_operacion_basica_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --usuarios=1,2,3 --compact=1
```

Debe indicar si se puede cobrar ahora, si hay turno abierto, si hay stock suficiente, si el ticket esta configurado y que acciones faltan antes de vender. Quita `--compact=1` solo si necesitas ver asignacion, turno y esquema completo.

Ejecutar arranque local:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_arranque_local_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
```

Debe salir `ok=true`. Si no hay turno abierto pero el resto esta listo, la decision esperada es `listo_para_arrancar_al_abrir_turno`. Este semaforo resume lo necesario para el uso diario: turno, ticket, stock, SKU recomendado, pendientes administrativos y pasos del operador.

Ejecutar siguiente piloto recomendado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_siguiente_piloto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
```

Debe salir `ok=true`. Si el SKU preferido no tiene stock, puede recomendar un SKU alternativo con existencia y precio vigente para evitar cargar stock UAT innecesario.

Ejecutar paquete recomendado si se quiere hacer piloto limpio sin cargar stock UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_recomendado_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
```

Resultado vigente esperado:

- `ok=true`.
- `decision=paquete_recomendado_preparado`.
- SKU recomendado: `173`.
- Total venta sugerido: `$1000.00`.
- Monto contado sugerido si no hubo otros movimientos: `$1500.00`.

Los pendientes previos `PINV-20260717-000001` y `GASTO-UAT-001` ya quedaron resueltos/aprobados; este paquete sirve para ejecutar una prueba limpia con un SKU que ya tiene stock disponible.

Revisar tambien en `Ventas > Manual POS > Checklist arranque` el bloque `Semaforo diario antes de cobrar`. Ese bloque resume las cuatro condiciones que debe entender el responsable de tienda: turno abierto, operadores correctos, inventario disponible o autorizado, y ticket configurado.

Ejecutar paquete de autorizacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_autorizacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --cantidad_fisica=CONTEO_REAL --monto_contado=MONTO_CONTADO_REAL
```

Debe salir `ok=true`, `decision=paquete_autorizacion_preparado`, `pasos_total=6` y `bloqueos_total=0`.

## Preparacion administrativa recomendada

Antes de abrir el turno piloto amplio, conviene atender estos puntos:

1. Confirmar que no haya pendientes nuevos en Inventario/Existencias.
2. Confirmar que no haya evidencias nuevas pendientes en Caja/Evidencias.
3. Cargar stock suficiente o elegir un SKU con disponible para la prueba.

Si se hara un piloto muy controlado, los puntos 1 y 2 pueden quedar como pendientes visibles, pero no deben ignorarse ni borrarse.

## Acciones administrativas previas

Estas acciones no las debe resolver el cajero durante la venta. Deben quedar atendidas por administracion, inventario o soporte antes de abrir el turno:

1. Resolver el mini inventario pendiente si se quiere iniciar sin alertas previas.
2. Cargar existencia real o elegir productos con disponible.
3. Confirmar que el usuario esta ligado a su tienda, caja y terminal.
4. Confirmar que la politica de inventario pendiente solo se usara si fue aprobada para el arranque.
5. Confirmar que el ticket ya tiene datos del negocio y formato correcto.

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
