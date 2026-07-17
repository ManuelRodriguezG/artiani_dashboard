# ERP Ventas POS - Checklist de piloto operativo

Documentacion IA: Codex GPT-5, 2026-07-17.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico: `http://panel.com.local/`.

## Objetivo

Definir que debe revisarse despues del ciclo real UAT multiusuario para decidir si POS puede pasar a piloto operativo controlado en tienda.

## Antes del piloto

Debe existir evidencia de:

- Semaforo final POS `ok=true`.
- Paquete de autorizacion `ok=true`.
- Ciclo real UAT multiusuario ejecutado y cerrado.
- Postcheck de evidencia sin hallazgos altos.
- Turno cerrado con diferencia esperada.
- Venta ligada a atencion.
- Ticket formal consultable.
- Kardex/trazabilidad de inventario por folio POS.

Comandos read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_final_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ciclo_evidencia_readonly.php --id_atencion=2 --id_usuario=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_operativo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
```

El semaforo de piloto debe devolver:

- `ok=true`;
- `decision=apto_para_piloto_controlado`;
- `ciclo_real_completo=true`;
- `folio_detectado` con folio POS real;
- `id_venta_detectado` mayor que cero.

Antes del ciclo real autorizado es correcto que devuelva `ok=false` con bloqueo `Falta ejecutar o evidenciar el ciclo real multiusuario antes del piloto`.

Superficie minima de UI/rutas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_surface_readonly.php
```

Resultado requerido:

- `ok=true`;
- archivos POS, caja, ventas, detalle, reportes, configuracion y checador existentes;
- metodos de controlador para POS, Caja/Turnos, ventas, reportes, configuracion, atenciones y ticket existentes;
- marcas UI basicas para buscador, ticket preview, atenciones, ticket real y cliente CRM.

Resultado 2026-07-17:

- `ok=true`;
- `archivos_revisados=20`;
- `metodos_revisados=18`;
- bloqueos `[]`;
- avisos `[]`.

## Criterios para permitir piloto

- Cada usuario entra con su propia cuenta.
- El POS muestra operador correcto.
- El POS no permite seleccionar libremente tienda/caja en operacion real.
- Caja/turno se abre antes de vender.
- Venta normal descuenta inventario con kardex.
- Ticket muestra folio, caja, turno, operador, partidas, pagos y total.
- Cierre de caja permite diferencia y la deja visible para revision.
- Las devoluciones quedan fuera del piloto salvo autorizacion separada.
- Inventario pendiente queda en modo controlado; no se usa como venta normal.

## Piloto recomendado

Duracion inicial:

- 1 turno corto.
- 1 sucursal.
- 1 caja.
- 1 a 2 usuarios.

Ventas permitidas:

- Productos con existencia disponible.
- Productos con precio normal.
- Pagos simples: efectivo, tarjeta o transferencia con referencia.

No permitir en el primer piloto:

- Descuentos manuales sin supervisor.
- Venta con inventario pendiente productivo.
- Devoluciones reales.
- Apartados reales nuevos.
- Cambios de precio fuera de listas/precio autorizado.
- Uso de ecommerce como correccion de POS.

## Evidencia del piloto

Por cada venta revisar:

- Folio POS.
- Usuario que cobro.
- Caja y turno.
- SKU vendido.
- Cantidad.
- Precio aplicado.
- Metodo de pago.
- Ticket.
- Movimiento de caja.
- Kardex.

Al cierre revisar:

- Monto inicial.
- Ventas efectivo.
- Otros metodos de pago.
- Gastos/retiros si existieron.
- Monto esperado.
- Monto contado.
- Diferencia.
- Observaciones del cajero.

## Pendientes no bloqueantes para piloto

- Permiso fino `ventas.pos.inventario_pendiente.autorizar`.
- Endpoint productivo de inventario pendiente sin token UAT.
- Reportes gerenciales completos por tienda/caja/usuario.
- CRM completo para clientes, recompensas e historial.
- Listas de precios avanzadas por cliente/canal/presentacion/granel.
- Devoluciones con destino final de cuarentena productivo.
- Impresora ticket/etiquetas configurada formalmente.

## Decision

POS puede pasar a piloto operativo solo si:

- el ciclo real UAT multiusuario queda completo;
- los postchecks no muestran bloqueos;
- el usuario puede explicar como abrir turno, vender, consultar ticket y cerrar caja;
- el negocio acepta iniciar con alcance limitado y controlado.

## Resultado vigente para piloto

Fecha de evidencia: 2026-07-16 / 2026-07-17.

El semaforo de piloto operativo ya devuelve:

- `ok=true`.
- `decision=apto_para_piloto_controlado`.
- `ciclo_real_completo=true`.
- `folio_detectado=POS-20260716-000001`.
- `id_venta_detectado=24`.
- Bloqueos `[]`.

Decision vigente:

- POS multiusuario puede pasar a piloto operativo controlado.
- Primer piloto: 1 sucursal, 1 caja, 1 turno corto, 1 a 2 usuarios.
- Mantener fuera del piloto inicial: inventario pendiente productivo, devoluciones reales, descuentos libres y apartados nuevos.
## Corte read-only 2026-07-17 - dependencias piloto POS

Proyecto canonico validado: `C:\xampp\htdocs\panel_de_control` con host operativo `panel.com.local`.

Verificaciones ejecutadas despues del ciclo real `POS-20260716-000001`:

- CRM saldos cliente `id_cliente_crm=157`: `ok=true`, saldo disponible MXN `$100.00`, movimientos trazados a POS/devolucion/apartado.
- Postventa `POS-20260716-000001`: `ok=true`, venta `pagada`, total `$295.00`, pago `$295.00`, una partida, una garantia snapshot y una trazabilidad/kardex.
- Diferencias de caja: `ok=true`, sin diferencias pendientes en el rango revisado.
- Inventario pendiente/notificaciones: `ok=true`, pendientes abiertos `0`, ultimos pendientes resueltos, notificacion POS resuelta.
- Configuracion POS: `ok=true`, usuario `1` asignado a almacen `5`, caja `2`, terminal `2`; sin turno abierto actualmente, normal fuera de operacion.
- Readiness productivo: `ok=true`, sin bloqueos; avisos solo por permiso fino de inventario pendiente productivo y ausencia de turno abierto.
- Superficie piloto UI: `ok=true`, 20 archivos y 18 metodos esperados presentes.
- Reportes caja: `ok=true`, ultimos 6 turnos, diferencias `0`, ventas total `$2,655.00`.
- Sintaxis JS: `node --check` correcto para `pos.js`, `caja_turnos.js`, `reportes.js`, `pos_configuracion.js` y `checador_precios.js`.

Decision operativa vigente:

- POS base queda listo para piloto controlado con ventas normales, atenciones multiusuario, caja/turnos, ticket, kardex, garantia snapshot, reportes basicos y trazabilidad.
- Mantener fuera del primer piloto: inventario pendiente productivo, devoluciones reales, descuentos libres, apartados nuevos y reglas avanzadas de precios sin UAT separada.
- Para operar, abrir turno desde Caja/Turnos antes de cobrar; el cierre puede registrar diferencia si el contado no cuadra, y esa diferencia debe entrar al flujo de revision.
