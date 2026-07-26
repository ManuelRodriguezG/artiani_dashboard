# ERP Ventas/POS - Estado de cierre del modulo

Documento vivo. Ultima actualizacion: 2026-07-25.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico local: `http://panel.com.local/`.

## Corte 2026-07-25 - limpieza operativa POS y semaforo ampliado

Se limpio la superficie operativa del POS para reducir lenguaje tecnico en la pantalla principal, ticket preview, cobro confirmado e inventario pendiente:

- La vista principal ya habla de `Prevalidar = revisar antes de cobrar` y `Ticket = vista previa`, sin presentar dry-run/token/respaldo como parte del flujo normal del cajero.
- El menu avanzado mantiene revisiones internas, pero con copy orientado a operacion: revisar venta, revisar pedido/apartado e inventario pendiente.
- El mensaje posterior a venta ya muestra `Devolucion` en vez de `Simular devolucion`.
- Inventario pendiente muestra `Resultado`, `Clave de autorizacion` y explicacion operativa para supervisor; no esta pensado como texto de cliente.
- Ticket preview conserva formato de revision antes de confirmar cobro y no debe imprimir bloqueos internos en el ticket del cliente.
- Las leyendas configurables del ticket usan salto de linea en vez de recorte con puntos suspensivos.
- Se retiro la funcion interna de prueba de ticket 80mm del POS; el flujo visible queda limitado a ticket real o vista previa.
- El backend de vista previa responde `Vista previa de ticket generada`, evitando copy mixto en la UI.
- Se actualizo cache buster POS a `20260725-operativo1`.

Semaforos read-only ejecutados:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php
C:\xampp\php\php.exe -l app\modelos\VentasErp.php
node --check public\assets\js\custom\apps\erp\ventas\pos.js
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_impresion_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_impresion_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- `bloqueos_total=0`.
- Decision: `pos_apto_para_piloto_controlado_con_condiciones`.
- UX operativa: `ux_pos_operativa_lista`.
- Impresion: `ventas_pos_impresion_readiness_readonly`, sin bloqueos.
- Scanner POS: validado por semaforo ampliado.
- Ticket formal read-only para `POS-20260724-000001`: texto/imprimir OK; mantiene hallazgo historico por venta rapida UAT sin snapshot de garantia, esperado en ventas de producto por clasificar hasta completar garantia/catalogo.

Pendientes reales que siguen vivos y no son fallas de codigo:

- Abrir turno antes de cobrar.
- Usar stock disponible o resolver/cargar inventario con autorizacion.
- Mantener identificado o resolver `PINV-20260717-000001`.
- Cerrar administrativamente `GASTO-UAT-001`.
- No usar devoluciones reales, inventario pendiente o descuentos libres como rutina del primer piloto.

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
- Aviso anterior de nombre visual de usuario `3` ya no aparece en el preflight vigente; los usuarios 1, 2 y 3 pasan sin problemas visuales reportados.
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
- Venta rapida controlada: DDL aplicado el 2026-07-23 para `Producto por clasificar`; UAT real ejecutada con venta `POS-20260723-000001` y pendiente `VRP-20260723-000001`, sin SKU definitivo y sin kardex. Turno `TUR-20260723-002-001` cerrado con diferencia `$0.00`; cobro real expuesto desde UI con token interno de controlador. Falta UAT UI desde navegador.
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

Resultado vigente: `ok=true`, `scripts_total=28`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

Este semaforo incluye MySQL, preflight, salida operativa, operacion basica, postcheck, navegacion, atajos, UX operativa, scanner, impresion, caja/turnos, reportes, productivo, inventario SKU, pendientes piloto, plan de accion piloto, paquete de autorizacion piloto, salida a operacion documentada, pedidos/apartados, reversa saldo favor, ticket venta, ticket devolucion, contrato CRM, listas de precios, encoding/BOM y guardrails.

Semaforo de pendientes piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=1760 --usuarios=1,2,3
```

Resultado vigente: `ok=true`, `pendientes_total=4`. Pendientes visibles antes de piloto amplio:

- `TURNO_ABIERTO`: abrir turno antes de cobrar.
- `STOCK_SKU`: SKU `1760` sin disponible en almacen `5`.
- `INVENTARIO_PENDIENTE`: resolver o mantener identificado `PINV-20260717-000001`.
- `EVIDENCIA_CAJA`: cerrar evidencia `GASTO-UAT-001` por `$50.00`.

Plan de accion piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --usuarios=1,2,3
```

Resultado vigente: `ok=true`, decision `listo_para_piloto_con_pendientes_accionables`, `pendientes_total=4`, `acciones_total=6`.

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
- Venta rapida controlada POS probada en UAT real: caja, venta, detalle provisional, pendiente `VRP`, evento y notificacion a Catalogo quedaron trazables; turno UAT cerrado sin diferencia. UI expuesta para cobro real; siguiente paso: UAT UI desde `/ventas/pos`.

## UAT UI real venta rapida controlada 2026-07-24

Autorizacion ejecutada: `VENTAS_POS_VENTA_RAPIDA_UI_REAL` con venta rapida desde `/ventas/pos`.

Evidencia:

- Turno abierto: `TUR-20260724-002-001`, id `27`, caja `2`, almacen `5`, monto inicial `$500.00`.
- Venta UI real: `POS-20260724-000001`, id `28`, total `$100.00`, estatus `pagada`, operador `id_usuario=1`.
- Detalle provisional: id `29`, SKU snapshot `VENTA-RAPIDA`, tipo `venta_rapida`, origen `venta_rapida_controlada`, descripcion `Producto UAT UI por clasificar`.
- Pendiente generado: `VRP-20260724-000001`, id `2`, estatus `pendiente_catalogo`, inventario `pendiente_regularizacion`.
- Evento VRP: `1` evento ligado al pendiente.
- Caja: movimiento apertura id `57` por `$500.00`; movimiento venta id `58` por `$100.00`; turno cerrado con monto esperado `$600.00`, contado `$600.00` y diferencia `$0.00`.
- Inventario/kardex: `0` movimientos para `POS-20260724-000001`, correcto para venta rapida no clasificada.
- Garantia: `0` snapshots para `POS-20260724-000001`, correcto hasta clasificar SKU.
- Evidencia UI: Playwright confirmo flujo de navegador y cobro real; no quedo PNG local persistido en esta corrida.

Ajuste UX aplicado despues de la prueba: el mensaje post-cobro ahora distingue venta normal de venta rapida. Para venta rapida debe decir que caja quedo registrada y que el pendiente va a Catalogo/Inventario, sin afirmar kardex o garantia.

Cierre real ejecutado: turno `TUR-20260724-002-001` cerrado por `id_usuario=1`; no quedan turnos abiertos despues de la UAT.

## Corte 2026-07-25 - limpieza operativa de submodulos POS

Proyecto validado: `C:\xampp\htdocs\panel_de_control` con host `http://panel.com.local/`.

Cambios sin escritura de BD:

- Caja/Turnos: se removieron folios UAT precargados en la revision operativa, se ocultaron textos internos de autorizacion sugerida y se dejo apertura/cierre real desde UI con confirmacion `ABRIR TURNO` / `CERRAR TURNO`.
- Movimientos de caja: la pantalla ahora habla de `Validar movimiento` en lugar de simular, dejando claro que el registro real requiere confirmacion y evidencia cuando aplique.
- Devoluciones: la reversa se presenta como `Prevalidar devolucion/cancelacion`; los textos visibles ya no usan `dry-run` para el operador.
- Pedidos/apartados: la pantalla usa `Validar pedido/apartado`, `Validar reserva` y `Validar abono`, sin lenguaje de prueba visible en botones principales.
- Scripts read-only actualizados para aceptar el cache buster operativo `20260725-operativo1`.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\caja_turnos.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\caja_movimientos.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\devoluciones.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pedidos.php
node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js
node --check public\assets\js\custom\apps\erp\ventas\caja_movimientos.js
node --check public\assets\js\custom\apps\erp\ventas\devoluciones.js
node --check public\assets\js\custom\apps\erp\ventas\pedidos.js
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_turnos_ui_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Sintaxis PHP/JS: sin errores.
- Caja/Turnos UI readiness: `ok=true`, sin bloqueos.
- Cierre ampliado POS: `ok=true`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

Condiciones operativas aun vigentes antes de venta normal:

- Abrir turno antes de cobrar.
- SKU piloto `1760` sin disponible en almacen `5`; cargar stock o usar flujo autorizado de inventario pendiente.
- Mantener identificado o resolver `PINV-20260717-000001`.
- Cerrar administrativamente evidencia de caja `GASTO-UAT-001`.
## Corte 2026-07-25 - limpieza operativa de tablero, reportes y cliente/precio

Cambios sin escritura de BD:

- Tablero de ventas: `Consulta read-only` se cambio por `Consulta de ticket`; tooltip de devolucion ahora dice `Prevalidar devolucion`.
- Reportes POS: `Consulta read-only` se cambio por `Consulta de corte`; los importes se describen como consulta operativa.
- Checador de precios: mensajes visibles ahora dicen `Consulta de precio`; el POS sigue revalidando precio e inventario al cobrar.
- POS cliente/precios: se removieron mensajes visibles tipo `Read-only` y `Contrato: backend...`; ahora se explica que la consulta no crea cliente ni cambia precios, y que los descuentos/precios manuales requieren autorizacion de supervisor cuando aplique.

Validaciones ejecutadas:

```powershell
node --check public\assets\js\custom\apps\erp\ventas\pos.js
node --check public\assets\js\custom\apps\erp\ventas\listado.js
node --check public\assets\js\custom\apps\erp\ventas\reportes.js
node --check public\assets\js\custom\apps\erp\ventas\checador_precios.js
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listado.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\reportes.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\checador_precios.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_navegacion_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_impresion_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Navegacion: `ok=true`.
- UX operativa: `ok=true`.
- Impresion: `ok=true`.
- Cierre ampliado POS: `ok=true`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - semaforo de lenguaje operativo POS

Cambio sin escritura de BD:

- Se agrego el semaforo `storage/uat/uat_ventas_pos_lenguaje_operativo_readonly.php` al cierre ampliado POS.
- El objetivo del semaforo es detectar textos tecnicos visibles para operador/cliente como `read-only`, `dry-run`, `simular`, textos de autorizacion o referencias a respaldo en pantallas operativas.
- Se mantienen permitidos nombres internos de endpoints, funciones y contratos tecnicos cuando no son texto visible para usuario.

Validacion ejecutada:

```powershell
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Cierre ampliado POS: `ok=true`, `scripts_total=28`, `bloqueos_total=0`.
- Nuevo semaforo de lenguaje operativo: `ok=true`, `avisos_total=0`.
- Decision general: `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - limpieza de lenguaje en reportes tecnicos de soporte

Cambios sin escritura de BD:

- Los avisos de soporte de Pedidos/Apartados ahora reportan `prevalidacion` en lugar de `dry-run`.
- El readiness productivo describe inventario pendiente como `validacion previa en UI` y endpoint supervisado.
- Listas de precios ya no muestra `read-only` ni `dry-run` como texto operativo visible.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_productivo_readiness_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_readiness_readonly.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios_manual.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Sintaxis PHP: sin errores.
- Busqueda acotada en vistas/JS de Ventas: sin textos visibles `read-only`, `dry-run`, `AUTORIZO` o `respaldo`.
- Cierre ampliado POS: `ok=true`, `scripts_total=28`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - Configuracion POS en semaforo operativo

Cambios sin escritura de BD:

- Configuracion POS queda incluida en el semaforo de lenguaje operativo: vista y JS.
- El aviso de esquema incompleto ahora dice que la administracion queda disponible solo para revision, sin hablar de modos tecnicos.
- Cache buster de Configuracion POS actualizado a `20260725-operativo1`.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos_configuracion.php
node --check public\assets\js\custom\apps\erp\ventas\pos_configuracion.js
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Lenguaje operativo: `ok=true`, `archivos_revisados=18`, `bloqueos=[]`.
- Cierre ampliado POS: `ok=true`, `scripts_total=28`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - Manual POS incluido en lenguaje operativo

Cambios sin escritura de BD:

- El Manual POS se agrego al semaforo de lenguaje operativo.
- La seccion `Mas` del POS en el manual ya habla de revisiones avanzadas e inventario pendiente, sin llamar `simulaciones` al flujo del operador.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\manual_pos.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Lenguaje operativo: `ok=true`, `archivos_revisados=19`, `bloqueos=[]`.
- Cierre ampliado POS: `ok=true`, `scripts_total=28`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - pendientes piloto actualizados

Cambios sin escritura de BD:

- Se actualizo documentacion y guardias documentales al preflight vigente.
- El pendiente visual anterior del usuario `3` ya no aparece; usuarios 1, 2 y 3 pasan sin problemas visuales reportados.
- Los pendientes piloto vigentes bajan a `4`.
- Las acciones recomendadas vigentes bajan a `6`.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=1760 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_docs_estado_vigente_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_salida_operacion_doc_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Pendientes piloto: `ok=true`, `pendientes_total=4`.
- Plan de accion piloto: `ok=true`, `acciones_total=6`.
- Documentos POS vigentes: `ok=true`, `bloqueos=[]`.
- Cierre ampliado POS: `ok=true`, `scripts_total=28`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - limpieza de contratos internos de Ventas

Cambios sin escritura de BD:

- Se limpio lenguaje tecnico residual en comentarios internos de `app/controladores/Ventas.php`.
- No se cambiaron rutas, nombres de endpoints, permisos, contratos JSON ni reglas de negocio.
- Los endpoints de soporte conservan sus guardrails de token/respaldo/confirmacion para operaciones fuertes.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\controladores\Ventas.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Sintaxis controlador Ventas: sin errores.
- Cierre ampliado POS: `ok=true`, `scripts_total=28`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - lenguaje operativo visible POS/Listas

Cambios sin escritura de BD:

- Se limpio lenguaje visible en POS, Manual POS, Evidencias, Detalle de venta y Listas de precios para reducir terminos tecnicos de soporte.
- El manual ya no presenta candados tecnicos como tarea normal del cajero; los explica como autorizacion administrativa.
- Se conservaron nombres internos de funciones, IDs HTML y endpoints para no romper eventos ni contratos existentes.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\manual_pos.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\listas_precios_inicio.php
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\pos.js
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\listas_precios.js
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\caja_evidencias.js
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\venta_detalle.js
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Lenguaje operativo visible: `ok=true`, `archivos_revisados=19`, `bloqueos=[]`.
- Cierre ampliado POS: `ok=true`, `scripts_total=28`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - semaforo de operacion basica POS

Cambios sin escritura de BD:

- Se agrego `storage/uat/uat_ventas_pos_operacion_basica_readonly.php`.
- El cierre ampliado ahora integra este semaforo como `operacion_basica`.
- La guia de primer turno y la salida controlada ya explican este check para saber si se puede cobrar ahora.
- El check valida asignacion usuario/caja/terminal, turno abierto, stock del SKU, ticket efectivo, pendientes de inventario, evidencias de caja y ventas del dia.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_operacion_basica_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_operacion_basica_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --compact=1
```

Resultado vigente:

- Operacion basica: `puede_cobrar_ahora=false` por falta de turno abierto y stock insuficiente para SKU `1760`.
- Ticket efectivo: configurado para `ARTIANI`, ancho `80`.
- Avisos administrativos: `PINV-20260717-000001` y `GASTO-UAT-001`.
