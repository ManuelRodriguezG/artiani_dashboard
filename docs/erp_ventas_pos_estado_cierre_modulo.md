# ERP Ventas/POS - Estado de cierre del modulo

Documento vivo. Ultima actualizacion: 2026-07-26.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico local: `http://panel.com.local/`.

## Corte 2026-07-26 - granel operativo con inventario pendiente

Se ajusto el POS para diferenciar dos formas de venta fraccionaria:

- `Granel/stock`: captura cantidad o peso desde existencia agregada de tienda. Es el flujo operativo para arranque de inventario cuando el SKU permite venta fraccionaria, aunque todavia no exista una unidad fisica abierta registrada.
- `Granel trazable`: captura cantidad o peso desde una unidad fisica abierta registrada. Conserva trazabilidad de la unidad abierta y por eso sigue bloqueado cuando no hay unidad abierta disponible.

Decision operativa:

- Para vender productos como croqueta por kilogramo durante arranque, el operador debe usar `Granel/stock`.
- Si el stock agregado no alcanza, debe usar `Venta con faltante`; al cobrar se genera el pendiente `PINV` para Inventario/Existencias.
- La alerta de Inventario no nace al agregar al carrito; nace hasta el cobro real con folio, operador, caja, pago y motivo. Asi se evitan alertas por carritos abandonados.
- Ecommerce no debe usar este flujo como unidad cerrada ni como stock ecommerce disponible.

Archivos ajustados:

- `public/assets/js/custom/apps/erp/ventas/pos.js`
- `app/vistas/paginas/apps/erp/ventas/manual_pos.php`

Validaciones ejecutadas:

```powershell
node --check public\assets\js\custom\apps\erp\ventas\pos.js
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\manual_pos.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
```

Resultado:

- JS POS: sintaxis `ok`.
- Manual POS: sintaxis `ok`.
- UX operativa: `ok=true`, `bloqueos=[]`.
- Lenguaje operativo visible: `ok=true`, `bloqueos=[]`.

## Corte 2026-07-26 - manual operativo ampliado para gastos, apartados y devoluciones

Se amplio `Ventas > Manual POS` para explicar flujos que no eran evidentes al operador.

Cambios sin escritura de BD:

- Se agrego seccion de `Atenciones compartidas`: cuentas creadas por un operador y cobradas por otro, sin mover caja/inventario hasta el cobro.
- Se amplio `Pedidos y apartados`: diferencia entre pedido/apartado, estados, abonos, entrega y comportamiento cuando no hay existencia.
- Se amplio `Devoluciones y reversas`: casos frecuentes, decision financiera, decision inventario, evidencia y pasos posteriores.
- Se amplio `Caja y turnos`: que entra durante el turno y como cerrar con diferencia real.
- Se amplio `Movimientos caja`: tipos de movimiento, flujo recomendado para gasto de caja y advertencia de que validar no registra dinero.
- Se amplio `Evidencias caja`: cuando aparece un gasto, que debe contener una evidencia y como corregir sin borrar historial.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\manual_pos.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
```

Resultado:

- Manual POS: sintaxis `ok`.
- UX operativa: `ok=true`, `bloqueos=[]`.
- Lenguaje operativo visible: `ok=true`, `bloqueos=[]`.
- Pendiente funcional detectado: `Ventas > Movimientos` valida y consulta gastos/retiros/entradas, pero el boton de registro real operativo aun no esta expuesto para uso normal de tienda. Siguiente tarea recomendada: exponer `Registrar movimiento real` con confirmacion, permisos, evidencia requerida y postcheck.

## Corte 2026-07-26 - defaults operativos alineados al piloto limpio

Se alinearon los semaforos y comandos operativos de salida al SKU recomendado `173`, que tiene stock disponible real para el piloto controlado.

Cambios sin escritura de BD:

- `uat_ventas_pos_cierre_ampliado_readonly.php` usa `id_sku=173` en sus checks de salida, operacion basica, inventario, pendientes, plan y arranque.
- `uat_ventas_pos_piloto_preflight_compacto_readonly.php` usa `id_sku=173` como default.
- `uat_ventas_pos_salida_operativa_readiness_readonly.php`, `uat_ventas_pos_productivo_readiness_readonly.php`, `uat_ventas_pos_arranque_local_readonly.php` y `uat_ventas_pos_piloto_paquete_recomendado_readonly.php` usan `173` como referencia inicial.
- `docs/erp_ventas_pos_salida_operacion_controlada.md` quedo con comandos vigentes para `173` y precio `1000`.
- Los folios `PINV-20260717-000001` y `GASTO-UAT-001` quedan documentados solo como historicos resueltos/aprobados.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_preflight_compacto_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_salida_operativa_readiness_readonly.php --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_arranque_local_readonly.php --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_recomendado_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12
```

Resultado vigente:

- Preflight: `ok=true`, `puede_iniciar_piloto_controlado=true`.
- Salida operativa: `ok=true`, `bloqueos_total=0`.
- Arranque local: `ok=true`, decision `listo_para_arrancar_al_abrir_turno`, stock SKU `173` = `999`.
- Paquete recomendado: `ok=true`, pasos abrir turno, vender SKU `173`, cerrar turno.
- Cierre ampliado: `ok=true`, `bloqueos_total=0`, `avisos_total=23`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

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

Pendientes reales vigentes para el piloto limpio:

- Abrir turno antes de cobrar.
- Usar stock disponible o resolver/cargar inventario con autorizacion.
- `PINV-20260717-000001` ya fue resuelto el 2026-07-26.
- `GASTO-UAT-001` ya tiene evidencia `id_evidencia_caja=4` aprobada.
- No usar devoluciones reales, inventario pendiente o descuentos libres como rutina del primer piloto.

## Decision vigente

POS esta listo para piloto controlado con condiciones.

Semaforo consolidado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_salida_operativa_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=173 --id_atencion=2 --cantidad=1 --usuarios=1,2,3 --compact=1
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
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173
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
- Postcheck vigente: `postcheck_apto_con_observaciones`; los pendientes historicos de caja e inventario quedaron cerrados/aprobados el 2026-07-26.
- Entorno MySQL recuperado al corte 2026-07-20: MariaDB responde `mysqladmin ping` y las validaciones POS con BD vuelven a ejecutar. El log conserva errores historicos de Aria/`mysql.plugin`, pero ya no bloquean mientras el servicio responda.
- Cobro UI vigente fuera de turno: bloqueado correctamente porque no hay turno abierto. Esto no es falla de POS; es guardrail operativo.

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

## Condiciones antes del siguiente piloto real

- Abrir turno desde `Ventas > Caja/Turnos`.
- Usar productos con existencia disponible o cargar/recibir inventario con autorizacion.
- Pendientes historicos `PINV-20260717-000001` y `GASTO-UAT-001`: cerrados/aprobados.
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

Resultado vigente: `ok=true`, `scripts_total=30`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

Este semaforo incluye MySQL, preflight, salida operativa, operacion basica, postcheck, navegacion, atajos, UX operativa, scanner, impresion, caja/turnos, reportes, productivo, inventario SKU, pendientes piloto, plan de accion piloto, siguiente piloto recomendado, arranque local, paquete de autorizacion piloto, salida a operacion documentada, pedidos/apartados, reversa saldo favor, ticket venta, ticket devolucion, contrato CRM, listas de precios, encoding/BOM y guardrails.

Semaforo de pendientes piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=173 --usuarios=1,2,3
```

Resultado vigente posterior a limpieza 2026-07-26 para SKU `173`: `ok=true`, `pendientes_total=1`. Pendiente visible antes de vender:

- `TURNO_ABIERTO`: abrir turno antes de cobrar.
- SKU recomendado limpio: `173`, disponible `999` en almacen `5`.
- `PINV-20260717-000001`: resuelto.
- `GASTO-UAT-001`: evidencia aprobada.

Plan de accion piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --precio=1000 --monto_inicial=500 --usuarios=1,2,3
```

Resultado vigente posterior a limpieza: `ok=true`, decision `listo_para_piloto_al_abrir_turno` cuando se usa SKU `173`; la accion real requerida es abrir turno antes de cobrar.

Paquete de autorizacion piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_autorizacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --precio=1000 --monto_inicial=500 --cantidad_fisica=CONTEO_REAL --monto_contado=MONTO_CONTADO_REAL
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
- `PINV-20260717-000001` resuelto y `GASTO-UAT-001` aprobado; solo abrir turno para vender.
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

- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`.
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
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

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
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

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
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - pendientes piloto actualizados

Cambios sin escritura de BD:

- Se actualizo documentacion y guardias documentales al preflight vigente.
- El pendiente visual anterior del usuario `3` ya no aparece; usuarios 1, 2 y 3 pasan sin problemas visuales reportados.
- Los pendientes piloto vigentes bajan a `4`.
- Las acciones recomendadas vigentes bajan a `6`.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=173 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --precio=1000 --monto_inicial=500 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_docs_estado_vigente_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_salida_operacion_doc_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente:

- Pendientes piloto posterior a limpieza: `ok=true`, `pendientes_total=1` usando SKU `173`; solo falta turno abierto.
- Plan de accion piloto: abrir turno, vender con stock disponible y cerrar turno.
- Documentos POS vigentes: `ok=true`, `bloqueos=[]`.
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

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
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

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
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`, decision `pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - semaforo de operacion basica POS

Cambios sin escritura de BD:

- Se agrego `storage/uat/uat_ventas_pos_operacion_basica_readonly.php`.
- El cierre ampliado ahora integra este semaforo como `operacion_basica`.
- La guia de primer turno y la salida controlada ya explican este check para saber si se puede cobrar ahora.
- El check valida asignacion usuario/caja/terminal, turno abierto, stock del SKU, ticket efectivo, pendientes de inventario, evidencias de caja y ventas del dia.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_operacion_basica_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_operacion_basica_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --usuarios=1,2,3 --compact=1
```

Ampliacion multiusuario:

- El semaforo acepta `--usuarios=1,2,3`.
- Reporta si cada operador tiene asignacion POS y si coincide con el almacen, caja y terminal base.
- No crea usuarios, no cambia permisos y no reasigna cajas; solo consulta.

Resultado vigente:

- Operacion basica: `puede_cobrar_ahora=false` por falta de turno abierto y stock insuficiente para SKU `1760`.
- Ticket efectivo: configurado para `ARTIANI`, ancho `80`.
- Avisos administrativos: `PINV-20260717-000001` y `GASTO-UAT-001`.

## Corte 2026-07-25 - cierre ampliado con timeout por semaforo

Se reforzo `storage/uat/uat_ventas_pos_cierre_ampliado_readonly.php` para que cada semaforo interno tenga limite de ejecucion.

Objetivo operativo:

- Evitar que el cierre ampliado se quede esperando indefinidamente si MySQL, un endpoint o una consulta se vuelve lenta.
- Permitir `--timeout_script=N`; default `8` segundos.
- Reportar `timeout=true` por script cuando aplique.
- Si MySQL no responde por puerto local, omitir semaforos dependientes de BD y conservar checks de archivos/UI que no escriben.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=2
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Resultado vigente con timeout normal:

- `ok=true`.
- `scripts_total=30`.
- `timeout_script_segundos=8`.
- `mysql_disponible=true`.
- `bloqueos_total=0`.
- `decision=pos_apto_para_piloto_controlado_con_condiciones`.

## Corte 2026-07-25 - lenguaje operativo en Caja/Turnos

Se limpio la revision operativa visible en `Ventas > Caja y turnos`.

Cambios:

- `VentasErp::readinessPosReadOnly()` ya no devuelve textos visibles con `dry-run`; usa `revisar`, `validar` y `pendiente de validar`.
- `public/assets/js/custom/apps/erp/ventas/caja_turnos.js` explica que la revision solo consulta condiciones, sin cerrar turno ni crear movimientos.
- No se cambiaron endpoints, nombres internos ni contratos de seguridad.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\modelos\VentasErp.php
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
```

Resultado:

- Sintaxis PHP y JS sin errores.
- Semaforo de lenguaje operativo: `ok=true`, `bloqueos=[]`.

## Corte 2026-07-25 - semaforo diario en Manual POS

Se agrego al `Manual POS` un bloque operativo de arranque diario.

Objetivo:

- Que el responsable de tienda pueda revisar condiciones antes de cobrar sin interpretar scripts.
- Explicar turno, operadores, inventario y ticket como semaforo de uso diario.
- Aclarar el estado vigente: usuarios `1`, `2` y `3` listos en la misma caja; falta abrir turno y usar stock disponible o flujo autorizado para el SKU piloto.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\manual_pos.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_docs_estado_vigente_readonly.php
```

Resultado:

- Sintaxis del manual sin errores.
- Lenguaje operativo visible: `ok=true`.
- Documentos POS vigentes: `ok=true`.

## Corte 2026-07-25 - atajos visibles POS

Se reforzo la pantalla `/ventas/pos` para que los atajos operativos no dependan solo del manual.

Cambios:

- Junto al buscador se muestra una tira compacta con atajos frecuentes: buscar `F2`, camara `F3`, cliente `F4` y pago `F6`.
- El boton de camara del POS indica `F3` de forma visible.
- Se mantiene separado del `Checador de precios`: el checador es consulta read-only; la camara del POS agrega producto a la cuenta actual solo si hay coincidencia unica.
- En subpantallas administrativas se cambio el encabezado visible `Bloqueos` por `Pendientes por resolver` cuando se muestra el detalle de una validacion.
- No se cambio backend, no se abrieron turnos, no se cobro y no se movio inventario.

Validaciones previstas/ejecutables:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\devoluciones.js
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\pedidos.js
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\listas_precios.js
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\listas_precios_inicio.js
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12
```

## Corte 2026-07-25 - acciones POS abiertas y etiquetas operativas

Se ajusto `/ventas/pos` para que el operador no dependa de un menu escondido al atender.

Cambios:

- Las acciones `Revisar venta`, `Revisar apartado`, `Venta con faltante`, `Pedidos`, `Caja`, `Movimientos`, `Evidencias` y `Reportes` quedan visibles en la barra superior.
- Se elimino el dropdown `Mas` de esa barra para evitar pasos extra durante mostrador.
- El pago `Saldo cliente` muestra atajo visible `Alt+4` y el JS lo ejecuta igual que efectivo/tarjeta/transferencia.
- Los botones de salida de inventario en carrito ahora dicen `Stock general`, `Unidad cerrada` y `Granel`, conservando los valores internos usados por backend.
- `Stock general` se explica como descuento del stock disponible de la tienda.
- No se cambio backend, no se abrio turno, no se cobro y no se movio inventario.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\pos.php
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\pos.js
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
```

Resultado:

- Sintaxis PHP y JS sin errores.
- UX operativa: `ok=true`, `bloqueos=[]`.
- Lenguaje operativo visible: `ok=true`, `bloqueos=[]`.

## Corte 2026-07-25 - traduccion operativa de validaciones POS

Se agrego una capa de lenguaje en `public/assets/js/custom/apps/erp/ventas/pos.js`.

Objetivo:

- Evitar que el operador vea mensajes tecnicos como `BLOQUEO`, `politica POS` o instrucciones internas cuando esta atendiendo.
- Mantener la regla real en backend, pero mostrar en pantalla una accion clara: abrir turno, usar `Venta con faltante`, cargar inventario autorizado o resolver pendiente.
- Mostrar los detalles bajo el encabezado `Pendientes por resolver`.
- No modificar ticket cliente, backend, permisos, caja, inventario ni endpoints.

Validaciones ejecutadas:

```powershell
C:\Users\aleja\AppData\Local\Programs\nodejs-portable-v24.16.0\node.exe --check public\assets\js\custom\apps\erp\ventas\pos.js
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_lenguaje_operativo_readonly.php
```

Resultado:

- UX operativa: `ok=true`, `bloqueos=[]`.
- Lenguaje operativo visible: `ok=true`, `bloqueos=[]`.

## Corte 2026-07-25 - preflight compacto con SKU recomendado

Se amplio `storage/uat/uat_ventas_pos_piloto_preflight_compacto_readonly.php`.

Cambios:

- El preflight compacto ahora integra el resultado de `uat_ventas_pos_siguiente_piloto_readonly.php`.
- Si el SKU preferido `1760` sigue sin stock, propone el SKU recomendado con stock/precio.
- Resultado vigente: SKU recomendado `173`, precio `1000`, disponible `1000`, paso practico `abrir turno y vender SKU 173`.
- No escribe BD, no abre turno, no cobra, no mueve caja y no mueve inventario.

Se agrego `storage/uat/uat_ventas_pos_piloto_paquete_recomendado_readonly.php`.

Objetivo:

- Preparar autorizaciones humanas para una prueba limpia sin cargar stock UAT.
- Pasos vigentes: abrir turno, vender SKU recomendado `173`, cerrar turno.
- Monto sugerido: inicial `500` + venta `1000` = contado sugerido `1500`.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_piloto_preflight_compacto_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_preflight_compacto_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_piloto_paquete_recomendado_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_recomendado_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12
```

Resultado:

- Preflight compacto: `ok=true`, `puede_iniciar_piloto_controlado=true`.
- Paquete recomendado: `ok=true`, `decision=paquete_recomendado_preparado`, `pasos_total=3`.
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`.

## Corte 2026-07-25 - SKU alternativo para piloto sin cargar stock

Se busco un SKU con existencia disponible en almacen `5` para evitar depender siempre del SKU piloto `1760`.

Hallazgo:

- SKU ERP `173`, codigo `ALI-GLOGDM`, producto `Alimento vivo grillo por millar`.
- Disponible en almacen `5`: `1000`.
- Existencia `EXI-77-38`, estatus `disponible`.
- Pendientes POS abiertos para ese SKU/almacen: `0`.

Preflight con `id_sku=173`, `cantidad=1`, `precio=1000`, `pago=1000`:

- Inventario cubre la cantidad requerida.
- Pago cubre el total estimado.
- El unico bloqueo duro para venta normal es no tener turno abierto.
- Queda como alternativa para piloto controlado sin cargar stock adicional.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_candidatos_readonly.php --id_almacen=5 --limite=10
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_inventario_sku_readonly.php --id_almacen=5 --id_sku=173 --cantidad=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --precio=1000 --pago=1000
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_operacion_basica_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --usuarios=1,2,3 --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_siguiente_piloto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
```

## Corte 2026-07-25 - manual POS con decision rapida

Se reforzo `Ventas > Manual POS` para reducir dudas de mostrador sin tocar BD ni endpoints.

Cambios:

- Se agrego matriz `Decision rapida en mostrador` para distinguir POS normal, venta con faltante, venta rapida controlada, checador de precios, pedidos/apartados y devoluciones.
- Se agrego bloque `Minimo para iniciar en local` con condiciones basicas: usuarios asignados, ticket configurado, turno abierto, venta normal con stock, venta rapida solo para productos no capturados e inventario pendiente solo con politica aprobada.
- Venta rapida ahora deja claro que agregar al carrito no genera alerta; el pendiente `VRP` nace hasta cobrar.
- Inventario pendiente ahora deja claro que la alerta real se crea al cobrar, con folio, operador, caja, pago y motivo.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\manual_pos.php
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
```

Resultado:

- Manual POS: sintaxis `ok`.
- UX operativa: `ok=true`, `bloqueos=[]`.

## Corte 2026-07-25 - semaforo de arranque local

Se agrego `storage/uat/uat_ventas_pos_arranque_local_readonly.php`.

Objetivo:

- Dar una salida diaria simple para iniciar POS en local sin leer multiples semaforos tecnicos.
- Consolidar operacion basica, siguiente SKU recomendado, paquete recomendado, UX/manual, ticket y docs vigentes.
- Mostrar pasos del operador: abrir turno, vender, prevalidar, cobrar, revisar ticket y cerrar con monto contado real.
- Mantener contrato read-only: no abre turno, no cobra, no crea pedidos, no resuelve pendientes y no mueve caja/inventario.

Resultado vigente:

- `ok=true`.
- Decision: `listo_para_arrancar_al_abrir_turno`.
- Ticket configurado: `true`.
- SKU preferido `1760` sin stock; SKU recomendado limpio `173`.
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`.

## Corte 2026-07-25 - arranque local visible en Caja/Turnos

Se ajusto `Ventas > Caja y turnos` para que la revision operativa sea entendible como semaforo diario.

Cambios:

- La tarjeta ahora se llama `Arranque local POS`.
- El boton dice `Validar arranque`.
- El SKU de referencia carga por defecto `1760` para que la revision no salga vacia.
- El backend `VentasErp::readinessPosReadOnly()` ahora consulta configuracion efectiva de ticket aunque no exista folio de venta.
- El readiness agrega `ticket_configurado`, `ticket_nombre_comercial`, `ticket_ancho_mm`, `stock_disponible_sku` y `stock_cubre_cantidad`.
- La UI muestra KPIs de turno, ticket, stock del SKU y devoluciones fisicas.
- Todo se mantiene read-only: no abre turno, no cierra turno, no cobra, no reserva y no mueve kardex.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\modelos\VentasErp.php
C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\ventas\caja_turnos.php
node --check public\assets\js\custom\apps\erp\ventas\caja_turnos.js
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_turnos_ui_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_arranque_local_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12
```

Resultado:

- Caja/Turnos UI readiness: `ok=true`, sin bloqueos.
- Arranque local: `ok=true`, decision `listo_para_arrancar_al_abrir_turno`.
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`.

## Corte 2026-07-25 - ticket preview sin mensajes internos

Se ajusto `public/assets/js/custom/apps/erp/ventas/pos.js` para separar con claridad el comprobante del cliente de las validaciones operativas.

Cambios:

- `actualizarModalTicket()` aplica `limpiarTicketCliente()` antes de mostrar o imprimir el texto del ticket.
- La vista previa de ticket mantiene los avisos de turno/inventario/politica fuera del comprobante, como `Avisos para operador`.
- Se filtran lineas internas como `BLOQUEO`, politica POS de inventario pendiente, tokens o respaldo para evitar que aparezcan en el ticket mostrado al cliente.
- El ajuste no cambia ventas, pagos, inventario, folios, kardex ni configuracion de ticket en BD.

Validaciones ejecutadas:

```powershell
node --check public\assets\js\custom\apps\erp\ventas\pos.js
C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ux_operativa_readiness_readonly.php
```

Resultado:

- JS POS: sintaxis `ok`.
- UX operativa: `ok=true`, sin bloqueos.

## Corte 2026-07-25 - metodos de pago en ticket preview

Se ajusto `VentasErp::ticketPreviewDryRun()` para que la vista previa del ticket muestre el nombre del metodo de pago cuando el navegador envia solo `id_metodo_pago`.

Cambios:

- El preview usa los pagos ya prevalididados cuando existen.
- Si no vienen pagos prevalididados, `pagosPreviewConEtiqueta()` resuelve el nombre desde `erp_metodos_pago` en modo read-only.
- Saldo cliente conserva su etiqueta operativa como pago sin caja.
- No registra pagos, no crea movimientos de caja, no cobra y no modifica ventas.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe -l app\modelos\VentasErp.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_docs_estado_vigente_readonly.php
```

Resultado:

- Modelo VentasErp: sintaxis `ok`.
- Cierre ampliado POS: `ok=true`, `scripts_total=30`, `bloqueos_total=0`.
- Documentos POS vigentes: `ok=true`.

## Corte 2026-07-26 - piloto POS real limpio con SKU recomendado

Se ejecuto un ciclo POS real controlado en el proyecto canonico `C:\xampp\htdocs\panel_de_control`, usando el host `http://panel.com.local/` y el SKU recomendado con stock disponible.

Evidencia del ciclo:

- Turno abierto: `TUR-20260726-002-001`, `id_turno_caja=28`, caja `2`, almacen `5`, monto inicial `$500`.
- Venta real: `POS-20260726-000001`, `id_venta=29`, SKU `173`, `ALI-GLOGDM`, cantidad `1`, precio `$1000`, pago efectivo `$1000`.
- Inventario: existencia `38` bajo de `1000` a `999`, movimiento/kardex `99`, trazabilidad detalle-inventario confirmada.
- Pago/caja: `id_venta_pago=33`, `id_movimiento_caja=60`.
- Garantia: snapshot `id_venta_detalle_garantia=17`, resumen `Sin garantia`.
- Turno cerrado: esperado `$1500`, contado `$1500`, diferencia `$0`.
- Ticket formal: generado sin hallazgos para `POS-20260726-000001`.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_operacion_basica_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --usuarios=1,2,3 --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260726-000001
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --id_turno_caja=28
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=173 --usuarios=1,2,3
```

Resultado:

- Ciclo POS normal con stock disponible: `ok`.
- Ticket, pago, caja, kardex, garantia y trazabilidad: `ok`.
- SKU `173` queda con disponible `999`.
- No queda turno abierto.
- Para SKU `173`, pendiente vigente: abrir turno antes de la siguiente venta.
- El cierre ampliado general conserva avisos por historicos del SKU `1760`, especialmente `PINV-20260717-000001`; no afecta el ciclo limpio del SKU `173`.

## Corte 2026-07-26 - limpieza de pendientes historicos POS

Se limpiaron los dos pendientes historicos que afectaban el semaforo de piloto operativo.

Acciones autorizadas y ejecutadas:

- Evidencia caja `GASTO-UAT-001`: se registro evidencia administrativa para `id_movimiento_caja=5`.
- Evidencia generada: `id_evidencia_caja=4`, referencia externa `GASTO-UAT-001`; posteriormente fue revisada y aprobada.
- Inventario pendiente `PINV-20260717-000001`: se resolvio con `cantidad_fisica=0`, decision `ajustar_a_conteo`.
- Resultado inventario: pendiente `resuelto`, ajuste `id_movimiento_ajuste=100`, salida pendiente `id_movimiento_salida_pendiente=101`, notificacion POS resuelta.

Validaciones ejecutadas:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_detalle_readonly.php --id_evidencia_caja=4
C:\xampp\php\php.exe storage\uat\uat_pos_inventario_pendiente_notificaciones_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=173 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_operacion_basica_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=173 --cantidad=1 --usuarios=1,2,3 --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12
```

Resultado:

- Pendientes de inventario POS abiertos: `0`.
- Notificaciones de inventario pendiente abiertas: `0`.
- Evidencias de caja en estado `pendiente`: `0`.
- Evidencia caja `id_evidencia_caja=4`: estatus `aprobada`, revisada por usuario `1`.
- Operacion basica: sin avisos, solo requiere abrir turno antes de cobrar.
- Cierre ampliado POS: `ok=true`, `bloqueos_total=0`, avisos reducidos a `23`.
- Pendiente administrativo fino: sin pendientes historicos de caja/inventario para el piloto limpio; solo abrir turno para vender.
