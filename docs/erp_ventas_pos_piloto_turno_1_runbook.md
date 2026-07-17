# ERP Ventas POS - Runbook piloto turno 1

Documentacion IA: Codex GPT-5, 2026-07-17.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico: `http://panel.com.local/`.

## Objetivo

Ejecutar el primer turno piloto de POS con alcance controlado, evidencia clara y sin habilitar procesos todavia sensibles.

## Alcance permitido

- 1 sucursal.
- 1 caja.
- 1 turno corto.
- 1 a 2 usuarios con cuenta propia.
- Venta normal de productos con existencia disponible.
- Precio normal o precio autorizado por politica ya existente.
- Pagos simples con referencia cuando aplique.

## Fuera del primer turno

- Inventario pendiente productivo.
- Descuentos libres.
- Devoluciones reales.
- Apartados nuevos.
- Cambios de precio sin politica/autorizacion.
- Correcciones de ecommerce desde POS.
- Ajustes manuales de inventario desde POS.

## Antes de abrir tienda

1. Entrar por `http://panel.com.local/ventas/pos`.
2. Confirmar que arriba se vea el usuario correcto.
3. Confirmar sucursal/caja/terminal asignadas.
4. Ejecutar semaforos read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_surface_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_operativo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
```

5. Abrir turno desde Caja/Turnos con monto inicial contado.

## Durante el turno

Por cada venta:

- Confirmar producto correcto antes de cobrar.
- Confirmar cantidad.
- Confirmar precio.
- Confirmar metodo de pago.
- Ver ticket despues del cobro.
- Anotar folio POS si algo se ve raro.

Si algo no cuadra:

- No repetir el cobro sin revisar ticket/listado de ventas.
- No corregir inventario desde POS.
- No hacer devolucion productiva sin autorizacion separada.
- Documentar folio, usuario, caja, hora y descripcion corta.

## Cierre de turno

1. Ir a Caja/Turnos.
2. Contar efectivo fisico.
3. Registrar monto contado.
4. Cerrar aunque exista diferencia.
5. Escribir observacion clara si hay sobrante/faltante.

La diferencia no bloquea el cierre. Debe quedar visible para reportes y revision.

## Evidencia minima

- Folio de turno.
- Monto inicial.
- Folios POS vendidos.
- Usuario que cobro cada venta.
- Metodo de pago.
- Ticket consultable.
- Kardex por folio POS.
- Monto esperado.
- Monto contado.
- Diferencia.
- Observaciones de cierre.

## Despues del turno

Ejecutar checks read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_operativo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
```

Revisar:

- que no existan bloqueos altos;
- que el cierre de caja este registrado;
- que las ventas tengan ticket;
- que inventario tenga kardex;
- que cualquier diferencia de caja tenga observacion.

## Decision

Si el turno piloto cierra sin bloqueos altos, se puede ampliar lentamente:

- mas productos;
- mas usuarios;
- mas turnos;
- despues mas cajas/sucursales.

No ampliar a devoluciones, apartados, descuentos libres ni inventario pendiente productivo hasta cerrar sus UAT y permisos productivos.
## Condiciones Previas Detectadas

Antes del primer piloto real:

- Confirmar stock disponible del SKU que se vendera. El ultimo semaforo detecto SKU `1760` sin disponible en almacen `5`.
- Si no hay stock, no forzar venta normal. Primero cargar inventario por recepcion/ajuste autorizado o usar una carga UAT autorizada.
- Revisar evidencias de caja pendientes. La evidencia historica `GASTO-UAT-001` por `$50.00` no bloquea venta normal, pero debe cerrarse por control administrativo.
