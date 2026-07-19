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

Resultado 2026-07-19:

- `ok=true`;
- `archivos_revisados=20`;
- `metodos_revisados=18`;
- bloqueos `[]`;
- avisos `[]`;
- scanner POS validado:
  - boton de camara en POS;
  - modal propio de escaneo;
  - preview de video;
  - selector de camara;
  - `BarcodeDetector`;
  - enfoque continuo cuando el navegador lo soporta;
  - luz/torch cuando el dispositivo lo soporta;
  - agregado automatico solo con coincidencia unica.

Regla para scanner:

- El checador de precios permanece como herramienta read-only.
- El scanner dentro de POS sirve para agregar productos a la cuenta actual, sin cobrar por si solo.
- Si el codigo tiene varias coincidencias, el operador debe elegir el producto correcto.
- Si no hay coincidencia, no se agrega partida.

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

## Corte 2026-07-18 - estado posterior a inventario pendiente productivo

Resultado read-only vigente:

- POS sigue `apto_con_condiciones` para piloto controlado.
- Caja/Turnos ya cuenta con apertura real desde UI y cierre real desde UI.
- Apertura real requiere dry-run valido y confirmacion escrita `ABRIR TURNO`.
- Cierre real requiere dry-run valido y confirmacion escrita `CERRAR TURNO`.
- No hay turno abierto actualmente, normal fuera de operacion.
- Existe pendiente de mini inventario abierto: `PINV-20260717-000001`.
- Existe una evidencia historica de caja pendiente de revision: movimiento `5`, referencia `GASTO-UAT-001`, monto `$50.00`.
- SKU piloto `1760` no tiene disponible ERP al corte revisado.

Comando de verificacion Caja/Turnos UI:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_turnos_ui_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --monto_inicial=500
```

Resultado esperado:

- `ok=true`;
- `turno_abierto=false` fuera de operacion;
- `folio_sugerido_apertura` con consecutivo real;
- apertura bloqueada sin escribir `ABRIR TURNO`;
- cierre bloqueado sin escribir `CERRAR TURNO`;
- contrato read-only cumplido.

Condiciones antes de una prueba real nueva:

- Abrir turno desde `Ventas > Caja/Turnos`.
- Usar un SKU con existencia disponible o cargar stock UAT con autorizacion.
- Resolver o aceptar conscientemente el pendiente `PINV-20260717-000001`.
- Revisar posteriormente la evidencia historica `GASTO-UAT-001`.

## Corte 2026-07-19 - Go/No-Go piloto

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_go_nogo_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --id_atencion=2 --cantidad=1 --usuarios=1,2,3
```

Comando consolidado de salida operativa:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_salida_operativa_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --id_atencion=2 --cantidad=1 --usuarios=1,2,3 --compact=1
```

Resultado vigente:

- `ok=true`.
- `decision=listo_para_piloto_controlado_con_condiciones`.
- `bloqueos=[]`.
- `go_nogo_decision=apto_con_condiciones`.
- `multiusuario_listo=true`.

Resultado vigente:

- `ok=true`.
- `decision=apto_con_condiciones`.
- `bloqueos=[]`.
- `multiusuario_listo=true`.
- `autorizacion_sugerida_multiusuario=null`.
- Usuarios `1,2,3` pueden participar en piloto controlado.
- Incluye checks de:
  - navegacion POS;
  - enlaces cruzados de pantallas POS;
  - apertura/cierre manual de turnos desde UI;
  - atajos rapidos POS;
  - ticket formal, garantia snapshot y trazabilidad;
  - piloto operativo;
  - escaner POS;
  - multiusuario;
  - reportes piloto.

Navegacion validada:

- `Tablero de ventas`.
- `POS`.
- `Checador de precios`.
- `Pedidos`.
- `Devoluciones`.
- `Caja y turnos`.
- `Movimientos caja`.
- `Evidencias caja`.
- `Reportes POS`.
- `Configuracion POS`.

Enlaces internos validados:

- Caja/Turnos, Movimientos, Evidencias, Reportes, Devoluciones y POS tienen accesos cruzados para no depender solo del menu lateral.
- `Reportes POS` esta disponible desde Caja/Turnos, Movimientos, Evidencias y Devoluciones.

Condiciones:

- No hay turno abierto, esperado fuera de operacion.
- SKU `1760` no tiene disponible en almacen `5`.
- Existe pendiente `PINV-20260717-000001`.
- Existe una evidencia historica de caja pendiente `GASTO-UAT-001`.

## Corte 2026-07-19 - reportes piloto POS

Se agrego semaforo read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reportes_piloto_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760
```

Resultado vigente:

- `ok=true`.
- Turnos reportados: `6`.
- Ventas reportadas: `$2,950.00`.
- Diferencias pendientes: `0`.
- Evidencias pendientes: `1`.
- Pendientes de inventario abiertos: `1`.
- Bloqueos: `[]`.

Uso operativo:

- Ejecutar antes/despues del primer piloto para validar que caja, diferencias, evidencias, corte y pendientes sean visibles sin mover datos.
- Si aparecen diferencias, se revisan administrativamente; no se corrigen borrando ventas o movimientos.
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
