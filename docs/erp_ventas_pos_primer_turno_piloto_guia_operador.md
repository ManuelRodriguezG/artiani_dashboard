# ERP Ventas/POS - Guia primer turno piloto

Documento vivo. Ultima actualizacion: 2026-07-19.

Proyecto: `C:\xampp\htdocs\panel_de_control`.

URL local: `http://panel.com.local/`.

## Antes de empezar

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
3. Buscar producto por texto, SKU o scanner POS.
4. Agregar solo productos con existencia disponible.
5. Capturar pago.
6. Prevalidar.
7. Cobrar.
8. Revisar ticket.

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

## Criterio de exito

- La venta aparece en reportes.
- El ticket se puede consultar.
- Kardex/trazabilidad quedan visibles.
- El turno queda cerrado.
- Si hay diferencia, queda registrada.
- Si hay pendiente de inventario, queda visible para Inventario/Existencias.

