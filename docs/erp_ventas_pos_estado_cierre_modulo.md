# ERP Ventas/POS - Estado de cierre del modulo

Documento vivo. Ultima actualizacion: 2026-07-20.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico local: `http://panel.com.local/`.

## Decision vigente

POS esta listo para piloto controlado con condiciones.

Semaforo consolidado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_salida_operativa_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --id_atencion=2 --cantidad=1 --usuarios=1,2,3 --compact=1
```

Preflight compacto recomendado antes de iniciar un turno piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_preflight_compacto_readonly.php
```

Este comando resume si se puede iniciar piloto, condiciones, pasos de uso y acciones que deben mantenerse fuera del primer turno.

Guia humana del primer turno:

```text
docs/erp_ventas_pos_primer_turno_piloto_guia_operador.md
```

Checklist ejecutivo de salida a operacion controlada:

```text
docs/erp_ventas_pos_salida_operacion_controlada.md
```

Postcheck compacto recomendado despues de cerrar el turno piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760
```

Este comando confirma si reportes, ticket, trazabilidad, diferencias, evidencias y pendientes quedaron visibles sin mover datos.

Resultado vigente:

- `ok=true`.
- `decision=listo_para_piloto_controlado_con_condiciones`.
- `entorno_canonico_ok=true`.
- `bloqueos=[]`.
- `go_nogo_decision=apto_con_condiciones`.
- `multiusuario_listo=true`.
- MySQL activo: `mysqladmin ping` responde `mysqld is alive`.
- Aviso vigente no bloqueante: usuario `3` muestra posible mojibake en nombre visible; corregir desde Seguridad/Usuarios con autorizacion antes de piloto amplio.
- Postcheck vigente: `postcheck_apto_con_observaciones`, con evidencias de caja e inventario pendiente visibles para administracion.
- Entorno MySQL recuperado al corte 2026-07-20: MariaDB responde `mysqladmin ping` y las validaciones POS con BD vuelven a ejecutar. El log conserva errores historicos de Aria/`mysql.plugin`, pero ya no bloquean mientras el servicio responda.
- Cobro UI vigente fuera de turno: bloqueado correctamente porque no hay turno abierto y el SKU piloto `1760` no tiene disponible suficiente. Esto no es falla de POS; es guardrail operativo.

Semaforo de salud MySQL:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_mysql_health_readonly.php
```

Resultado vigente: `ok=true`; `mysql_ping.ok=true`. Los mensajes `Aria recovery failed`, `mysql.plugin` y `Failed to initialize plugins` quedan como avisos historicos del log, no como bloqueo activo mientras MariaDB responda.

Semaforo de entorno canonico:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_entorno_canonico_readiness_readonly.php
```

Valida que `AGENTS.md`, esta hoja de cierre y los scripts Playwright POS usen `C:\xampp\htdocs\panel_de_control` y `http://panel.com.local/` como referencias vigentes.

## Alcance listo para piloto

- Venta POS normal con turno abierto.
- Caja/turnos con apertura y cierre manual desde UI.
- Cierre de caja permitido con diferencia; la diferencia queda para revision y reportes.
- Multiusuario controlado con usuarios `1,2,3` en almacen `5`, caja `2`, terminal `2`.
- Trazabilidad por operador: cada cobro conserva el usuario que ejecuta la venta.
- Identidad visual de operadores auditada; si un nombre aparece con caracteres raros, POS no debe corregirlo, se corrige en datos maestros de usuario.
- Ticket formal, detalle de venta, garantia snapshot y trazabilidad de inventario/kardex.
- Impresion directa de ticket desde POS, listado de ventas y detalle de venta; impresion de corte desde Caja/Turnos y Reportes POS.
- Scanner POS con camara para agregar productos a la cuenta actual.
- Checador de precios independiente, read-only.
- Manual Ventas y POS disponible desde `Ventas > Manual POS` y desde el boton `Manual` dentro de `/ventas/pos`; cubre tablero, POS, checador, pedidos, devoluciones, caja, movimientos, evidencias, reportes y configuracion.
- UX operativa POS ajustada: acciones principales arriba con iconos sin scroll horizontal propio, pagos rapidos visibles con atajos discretos, `Compromiso` solo para pedidos/apartados y acciones avanzadas bajo `Mas`.
- Reportes piloto de turnos, ventas, diferencias, evidencias y pendientes de inventario.
- Enlaces de navegacion entre POS, Caja/Turnos, Movimientos, Evidencias, Devoluciones, Reportes y Configuracion POS.

## Condiciones antes del primer piloto real

- Abrir turno desde `Ventas > Caja/Turnos`.
- Usar productos con existencia disponible o cargar/recibir inventario con autorizacion.
- Mantener identificado o resolver el pendiente `PINV-20260717-000001`.
- Cerrar o documentar administrativamente la evidencia historica `GASTO-UAT-001`.
- Iniciar con un turno corto, una sucursal y una caja.

## Estado operativo probado 2026-07-20

Semaforos read-only verdes:

- `uat_ventas_pos_cierre_ampliado_readonly.php`.
- `uat_ventas_pos_mysql_health_readonly.php`.
- `uat_ventas_pos_piloto_preflight_compacto_readonly.php`.
- `uat_ventas_pos_salida_operativa_readiness_readonly.php`.
- `uat_ventas_pos_piloto_postcheck_compacto_readonly.php`.
- `uat_ventas_pos_navegacion_readiness_readonly.php`.
- `uat_ventas_pos_atajos_ui_readiness_readonly.php`.
- `uat_ventas_pos_ux_operativa_readiness_readonly.php`.
- `uat_ventas_pos_escaner_ui_readiness_readonly.php`.
- `uat_ventas_pos_impresion_readiness_readonly.php`.
- `uat_ventas_pos_caja_turnos_ui_readiness_readonly.php`.
- `uat_ventas_pos_reportes_piloto_readiness_readonly.php`.

Semaforo consolidado ampliado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente: `ok=true`, `scripts_total=26`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

Este semaforo incluye MySQL, preflight, salida operativa, postcheck, navegacion, atajos, UX operativa, scanner, impresion, caja/turnos, reportes, productivo, inventario SKU, pendientes piloto, plan de accion piloto, paquete de autorizacion piloto, salida a operacion documentada, pedidos/apartados, reversa saldo favor, ticket venta, ticket devolucion, contrato CRM, listas de precios, encoding/BOM y guardrails.

Semaforo de pendientes piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=1760 --usuarios=1,2,3
```

Resultado vigente: `ok=true`, `pendientes_total=5`. Pendientes visibles antes de piloto amplio:

- `TURNO_ABIERTO`: abrir turno antes de cobrar.
- `STOCK_SKU`: SKU `1760` sin disponible en almacen `5`.
- `INVENTARIO_PENDIENTE`: resolver o mantener identificado `PINV-20260717-000001`.
- `EVIDENCIA_CAJA`: cerrar evidencia `GASTO-UAT-001` por `$50.00`.
- `USUARIO_NOMBRE_VISUAL`: corregir nombre visible de usuario `3` desde Seguridad/Usuarios.

Plan de accion piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --usuarios=1,2,3
```

Resultado vigente: `ok=true`, decision `listo_para_piloto_con_pendientes_accionables`, `pendientes_total=5`, `acciones_total=7`.

Paquete de autorizacion piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_autorizacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --cantidad_fisica=CONTEO_REAL --monto_contado=MONTO_CONTADO_REAL
```

Resultado vigente: `ok=true`, decision `paquete_autorizacion_preparado`, `pasos_total=6`, `bloqueos_total=0`.

Nota: la autorizacion humana puede usar `respaldo UAT POS vigente`; los comandos tecnicos generados por el paquete usan `RUTA_RESPALDO_UAT_POS_VIGENTE.sql` como marcador y solo deben ejecutarse con la ruta real del respaldo cuando toque CLI.

Semaforo con bloqueo esperado:

- `uat_ventas_pos_cobro_ui_readiness_readonly.php`: bloquea cobro por no tener turno abierto y por inventario insuficiente/politica de inventario pendiente.

## Mantener fuera del primer piloto

- Devoluciones reales.
- Descuentos libres sin politica.
- Apartados nuevos.
- Inventario pendiente productivo como operacion cotidiana.
- Reglas avanzadas de listas de precios sin UAT dedicada.

## Que significa piloto controlado

No significa que el POS este en produccion abierta para toda la empresa.

Significa que ya puede usarse en una prueba real de tienda con alcance limitado, operadores identificados, turno abierto, caja controlada, ticket, kardex y reportes posteriores.

## Siguiente autorizacion fuerte posible

Si MariaDB vuelve a fallar, recuperar entorno UAT:

```text
AUTORIZO RECUPERAR MYSQL UAT POS usando respaldo UAT POS vigente con token MYSQL_UAT_POS_RECOVERY permitiendo respaldo previo de C:\xampp\mysql\data, arranque controlado de MariaDB, diagnostico InnoDB, reparacion Aria con aria_chk y restauracion/importacion solo si es necesario para continuar UAT POS
```

Para resolver el pendiente de mini inventario vigente:

```text
AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario=1 folio=PINV-20260717-000001 cantidad_fisica=CONTEO_REAL decision=ajustar_a_conteo confirmacion="RESOLVER PENDIENTE" motivo="Resolver mini inventario POS pendiente"
```

Usar `cantidad_fisica` con el conteo real posterior a la venta pendiente.

## Siguiente trabajo sin escritura fuerte

- Revisar UX final de POS con navegador en `http://panel.com.local/ventas/pos`.
- Revisar que el menu muestre claramente `Caja y turnos`, `Reportes POS` y `Configuracion POS`.
- Probar scanner POS solo como UI si no se va a cobrar.
- Probar impresion de ticket/corte con impresora configurada en Windows cuando se instale hardware.
- Ejecutar semaforos read-only despues de cada ajuste visual.
