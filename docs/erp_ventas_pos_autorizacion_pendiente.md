# ERP Ventas/POS/Pedidos - Autorizacion pendiente

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: listo para solicitar autorizacion de escritura en BD.

## Alcance solicitado

Crear el esquema ERP nuevo de Ventas/POS/Pedidos y sembrar configuracion inicial de POS:

- cajas POS por sucursal;
- terminales POS por sucursal;
- asignacion usuario/caja/terminal;
- tablas de ventas, detalle, pagos, trazabilidad de inventario y devoluciones.

Alcance expandido en diseno posterior:

- clientes ERP para POS;
- identificadores de cliente;
- listas de precios;
- relacion cliente-lista;
- atenciones POS persistentes para varias cuentas entre terminales/usuarios;
- eventos de venta para apartados/abonos;
- politicas de apartado.

Decision pendiente:

- Recomendado: autorizar POS base primero y clientes/listas/atenciones/apartados despues.
- Alternativa: autorizar todo el alcance expandido en una sola aplicacion DDL.

## Respaldo validado

Referencia:

- `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`

Preflight read-only:

- `ok=true`
- archivo existe y es legible;
- tamano: `27216189` bytes;
- modificado: `2026-06-26 06:20:08`.

## Paquete dry-run validado con MySQL activo

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_paquete_autorizacion_dryrun.php --id_usuario=1 --alcance=base
```

Resultado 2026-06-26:

- `ok=true`
- alcance `base`: `ddl_total=11`
- alcance `expandido`: `ddl_total=21`
- `seed_cajas_total=2`
- `seed_terminales_total=2`
- `seed_asignaciones_total=2`
- `listo_para_autorizacion_configuracion=true`

Cajas propuestas:

- `CJ-ACUARIO967-01` para almacen `4` - Acuario Mina 967.
- `CJ-MASCOTAS971-01` para almacen `5` - Mascotas Mina 971.

Terminales propuestas:

- `TERM-ACUARIO967-01`
- `TERM-MASCOTAS971-01`

Asignacion UAT propuesta:

- `id_usuario=1` queda asignado a ambas cajas/terminales para UAT.

Bloqueos esperados antes de aplicar DDL:

- faltan tablas `erp_pos*`, `erp_ventas*`, clientes/listas y atenciones POS persistentes;
- no hay turnos abiertos;
- el POS aun opera con configuracion local de UAT.

## Usuario POS UAT sugerido

- `id_usuario=1`
- nombre: `soporte_sistema`
- roles: `administrador_erp`, `soporte_sistema`
- `ventas.operar=true`

Pendiente de decision:

- Confirmar si `id_usuario=1` se usara para UAT de ambas sucursales.
- O indicar usuarios por sucursal:
  - `ACUARIO967:ID_USUARIO`
  - `MASCOTAS971:ID_USUARIO`

## Comandos read-only previos

Runbook humano recomendado:

- `docs/erp_ventas_pos_base_solicitud_autorizacion.md`
- `docs/erp_ventas_pos_base_runbook_aplicacion.md`
- `docs/erp_ventas_pos_base_plan_reversa.md`

Validar respaldo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_respaldo_preflight_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"
```

Generar paquete dry-run:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_paquete_autorizacion_dryrun.php --id_usuario=1 --alcance=base
```

Preflight consolidado de autorizacion base:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_base_autorizacion_preflight_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

Resultado 2026-06-26:

- `ok=true`
- `base_total=11`
- `expandido_total=21`
- `cajas_total=2`
- `terminales_total=2`
- `asignaciones_total=2`
- `bloqueos=[]`

Compatibilidad Catalogo/Inventario para venta base:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_base_compatibilidad_readonly.php
```

Resultado 2026-06-26:

- `ok=true`
- sin tablas faltantes en Catalogo/Inventario.
- sin columnas faltantes para venta POS base, kardex y trazabilidad.

Suite readiness base:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_base_readiness_suite_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

Resultado 2026-06-26:

- `ok=true`
- `ddl_base_total=11`
- `seed_cajas_total=2`
- `seed_terminales_total=2`
- `seed_asignaciones_total=2`
- `preflight_bloqueos=[]`
- `compatibilidad_bloqueos=[]`
- `guardrails_fallas=[]`

Guardrails de escritura:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_guardrails_readonly.php
```

Resultado 2026-06-26:

- `ok=true`
- el token viejo `VENTAS_POS_DDL` queda bloqueado;
- DDL sin respaldo queda bloqueado;
- semillas sin autorizacion quedan bloqueadas;
- apertura de turno sin autorizacion queda bloqueada;
- venta real sin autorizacion queda bloqueada.

Revisar runbook:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_runbook_readonly.php
```

## Autorizacion aplicada - Excepcion comercial real desde POS UI

Fecha: 2026-06-29  
Estado: autorizada e implementada en UI/controlador.

### Contexto

Ya existe y fue validado por UAT el metodo de dominio:

- `VentasErp::registrarExcepcionComercialAutorizada`

Ese metodo:

- escribe solo en `erp_ventas_excepciones_comerciales`;
- no crea venta;
- no mueve caja;
- no descuenta inventario;
- requiere politica comercial activa;
- valida dry-run antes de registrar;
- valida permiso `ventas.autorizar_excepcion_comercial` para el usuario autorizador;
- guarda snapshot CRM cuando existe el contrato CRM/POS.

Tambien existe script UAT autorizado:

- `storage/uat/uat_ventas_pos_excepcion_registro_apply_authorized.php`

Y ya se probo una excepcion real consumida por venta:

- excepcion `EXC-20260629-000002`;
- venta `POS-20260629-000002`;
- cliente CRM `id_cliente_crm=1`;
- cierre de turno con diferencia `0`.

### Lo que falta para POS UI productivo

Exponer una ruta de controlador para registrar una excepcion real desde el POS, probablemente:

- `/ventas/pos_excepcion_comercial_registrar_erp`

Guardrails requeridos:

- controlador protegido por sesion;
- CSRF obligatorio por POST;
- `ventas.operar` para el usuario que solicita;
- `ventas.autorizar_excepcion_comercial` para el autorizador;
- auditoria explicita;
- no aceptar precio final decidido por JS;
- recalcular todo con `VentasErp::excepcionComercialDryRun`;
- no permitir registro si falta caja/sucursal/canal cuando la politica lo requiera;
- no consumir el folio automaticamente;
- mostrar folio para aplicarlo despues contra carrito/pagos;
- registrar motivo y supervisor obligatorio;
- no tocar inventario, caja ni venta en este endpoint.

### Implementado

- Endpoint POST `/ventas/pos_excepcion_comercial_registrar_erp`.
- Boton `Registrar folio autorizado` en POS.
- Auditoria explicita.
- Permisos `ventas.operar` y `ventas.autorizar_excepcion_comercial`.
- CSRF por `Core`.
- Asset POS `20260629-excepcion4`.

### Autorizacion recibida

Frase recibida:

```text
AUTORIZO EXPONER REGISTRO REAL DE EXCEPCION COMERCIAL POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS
```

### Pendiente UAT

- Probar desde navegador autenticado con usuario autorizado.
- Registrar un folio real `EXC-*`.
- Aplicarlo contra carrito/pagos.
- Confirmar que no crea venta/caja/inventario hasta el cobro real.

## Comandos con escritura

No ejecutar sin autorizacion textual explicita.

Aplicar DDL:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_BASE --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"
```

Aplicar DDL expandido:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_EXPANDIDO --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql"
```

Aplicar semillas POS:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_seed_apply_authorized.php --autorizar=VENTAS_POS_SEED --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

## UAT posterior

Validar configuracion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=base
```

Validar UAT general:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_dryrun_readonly.php --alcance=base
```

Preflight de apertura de turno:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500
```

Abrir turno POS real requiere autorizacion adicional:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1 --monto_inicial=500
```

Preflight de venta real, sin escritura:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1
```

Venta POS real UAT requiere autorizacion adicional posterior a DDL, semillas y turno abierto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql" --id_usuario=1
```

## Criterios de exito

- No quedan tablas POS/Ventas pendientes.
- Existen `2` cajas POS.
- Existen `2` terminales POS.
- Existe asignacion activa para el usuario UAT.
- El POS abre con asignacion oficial, no con selector libre.
- Apertura de turno se autoriza por separado despues de validar asignacion.
- Venta real se autoriza por separado despues de que el preflight salga limpio y exista turno abierto.
- La venta real debe devolver folio ERP, pagos, movimientos de inventario y trazabilidad detalle-inventario.

## Frase sugerida de autorizacion base

Usar si se acepta crear primero POS/caja/ventas/devoluciones sin clientes ERP, listas de precios ni atenciones persistentes:

`AUTORIZO CREAR ESQUEMA BASE ERP VENTAS POS PEDIDOS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`

## Frase sugerida si se autoriza alcance expandido

Usar solo si se acepta incluir clientes, listas de precios, atenciones POS persistentes, eventos y politicas de apartado en el DDL inicial:

`AUTORIZO CREAR ESQUEMA EXPANDIDO ERP VENTAS POS PEDIDOS CLIENTES LISTAS ATENCIONES Y APARTADOS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 para UAT POS`

## Frase posterior para venta real UAT

Usar solo despues de:

- DDL aplicado;
- semillas aplicadas;
- asignacion POS activa;
- turno abierto;
- preflight de venta real limpio.

`AUTORIZO VENTA POS REAL UAT con kardex usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1`

## Frase sugerida para exponer correcciones de evidencias en UI

Contexto:

- El backend ya soporta solicitar, registrar evidencia correctiva y resolver correccion de evidencia de caja.
- La UI POS ya muestra el historial de correccion en modo solo lectura.
- Exponer botones de correccion en POS permitiria escrituras reales desde navegador y debe autorizarse aparte.

Alcance autorizado por esta frase:

- Mostrar acciones UI para:
  - solicitar correccion sobre evidencia aprobada;
  - registrar referencia/descripcion de evidencia correctiva;
  - resolver correccion en revision.
- Mantener permisos, CSRF y validaciones del modelo.
- No cambiar reglas de caja, dinero ni inventario.
- No subir archivos fisicos todavia; usar referencia externa/descripcion para UAT.

Frase sugerida:

```text
AUTORIZO EXPONER CORRECCIONES EVIDENCIA CAJA POS UI usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_UI para UAT POS
```

## Frase sugerida para preparar inspeccion fisica de devoluciones

Contexto:

- POS/Ventas ya puede consultar devoluciones fisicas pendientes en modo solo lectura.
- Hay partidas UAT en `cuarentena` sin movimiento de inventario de devolucion.
- Resolver la condicion fisica puede crear kardex, mover existencias o ligar garantia/proveedor, por lo que requiere autorizacion fuerte.

Alcance propuesto inicial:

- Crear contrato y DDL si hace falta para inspeccion fisica de devoluciones.
- Registrar decision por partida:
  - mantener cuarentena;
  - reintegrar a disponible;
  - enviar a merma;
  - ligar a reclamo de garantia/proveedor.
- Generar kardex solo cuando aplique.
- Mantener trazabilidad a `erp_ventas_devoluciones_detalle`.
- No mezclar con ecommerce ni legacy.

Frase sugerida para preparar/aplicar DDL, no para mover inventario todavia:

```text
AUTORIZO PREPARAR DDL INSPECCION FISICA DEVOLUCIONES POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_DEVOLUCIONES_FISICAS_DDL para UAT POS
```

La ejecucion real por folio/partida debera pedirse despues, con otra autorizacion especifica.

## Frase sugerida para primera inspeccion fisica real sin kardex

Contexto:

- DDL de inspeccion fisica ya aplicado.
- Dry-run sobre `id_devolucion_detalle=2` con `mantener_cuarentena` salio valido.
- Esta primera ejecucion real solo registra inspeccion y actualiza estado de detalle; no reintegra, no merma y no crea kardex.

Frase sugerida:

```text
AUTORIZO REGISTRAR INSPECCION FISICA DEVOLUCION POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_DEVOLUCION_FISICA_REAL id_usuario=1 id_devolucion_detalle=2 decision_fisica=mantener_cuarentena condicion_producto=pendiente_revision motivo="UAT mantener en cuarentena sin mover inventario" diagnostico="Producto pendiente de inspeccion fisica"
```

## Frase sugerida para cerrar turno UAT POS UI real

Contexto:

- Turno abierto: `TUR-20260630-002-002`.
- `id_turno_caja=10`.
- Caja `2`, almacen `5`.
- Venta confirmada: `POS-20260701-000001`.
- Fondo inicial `500`.
- Venta POS `295`.
- Dry-run de cierre ejecutado el 2026-07-01:
  - esperado `795`;
  - contado `795`;
  - diferencia `0`;
  - bloqueos `[]`.

Alcance:

- Cerrar turno real UAT.
- Registrar monto esperado, contado, diferencia, usuario de cierre, fecha de cierre y observaciones.
- No crear ventas nuevas.
- No mover inventario.
- No crear pagos.
- No modificar ecommerce ni legacy.

Frase sugerida:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS UI real POS-20260701-000001"
```

## Frase futura para CRUD real Configuracion POS

Contexto:

- Ya existe pantalla `/ventas/pos_configuracion`.
- Ya existen dry-runs para:
  - caja;
  - terminal;
  - asignacion usuario/caja/terminal.
- Pruebas dry-run:
  - caja nueva `CJ-UAT-DRY-01`: valida;
  - terminal nueva `TERM-UAT-DRY-01`: valida;
  - asignacion duplicada `id_usuario=1`, `id_almacen=5`, `id_caja=2`, `id_terminal_pos=2`: bloquea correctamente.

Alcance propuesto:

- Crear aplicadores reales protegidos para:
  - alta/edicion logica de caja POS;
  - alta/edicion logica de terminal POS;
  - alta/baja logica de asignacion usuario/caja/terminal.
- Registrar auditoria explicita.
- Validar duplicados y pertenencia tienda/caja/terminal.
- No abrir/cerrar turnos.
- No cobrar ventas.
- No mover inventario.
- No modificar ecommerce ni legacy.

Permisos recomendados antes de productivo:

- `ventas.pos_configurar` o `pos.configurar` para cajas, terminales y asignaciones.
- Mantener `ventas.operar` solo para operar POS/cobrar.
- Mantener `ventas.ver` para consulta de configuracion sin edicion.

Frase sugerida:

```text
AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD para UAT POS
```

## Frase futura para flujo real Pedidos/Apartados POS

Contexto:

- Ya existe pantalla `/ventas/pedidos`.
- Ya existe detalle read-only por folio.
- Ya existe dry-run de abono con validacion de caja, turno, metodo, folio existente, tipo documento y saldo.
- Aun no hay pedidos/apartados UAT reales en la muestra.

Alcance propuesto inicial:

- Crear pedido/apartado real desde POS o modulo dedicado.
- Generar folio ERP de pedido/apartado.
- Guardar cliente CRM/snapshot cuando exista.
- Crear partidas con precio/lista/snapshot.
- Crear reserva de inventario cuando la politica lo indique.
- Registrar abonos reales ligados a caja/turno/metodo.
- Crear eventos de pedido/apartado.
- Liquidar y entregar solo revalidando saldo, reserva e inventario.
- Generar kardex al entregar/descontar, no al crear borrador.
- No mezclar ecommerce ni legacy.

Permisos recomendados:

- `ventas.operar` para crear pedido/apartado y registrar abonos.
- `ventas.autorizar_excepcion_comercial` si hay precio/descuento manual.
- permiso futuro `ventas.pedidos.entregar` para liberar mercancia y descontar inventario.

Frase sugerida:

```text
AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS
```

## Frase inmediata para UAT cierre con diferencia POS

Contexto:

- No hay turno abierto.
- El SKU `1760` no tiene stock disponible para una venta UAT nueva.
- El reporte de caja ya muestra turnos por empleado, sucursal y caja.
- El cierre con diferencia no debe bloquearse; debe quedar visible en reportes.

Primer bloque requerido:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS para prueba diferencia caja"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-DIFERENCIA-01
```

Despues de abrir turno/cargar stock:

- ejecutar venta real UAT;
- cerrar con monto contado distinto al esperado;
- validar `/ventas/reportes` con `Solo con diferencia`;
- confirmar que aparece por turno, empleado y caja.

Bloque siguiente para generar faltante `-10`:

```text
AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295 cliente="Cliente UAT POS diferencia caja"

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=785 observaciones="Cierre UAT POS ciclo TUR-20260703-002-002"
```

Lectura esperada despues de la venta:

- monto esperado `795`;
- monto contado `785`;
- diferencia `-10`;
- reporte debe clasificar como `faltante`.

## Frase siguiente para revision formal de diferencias de caja

Contexto:

- Ya existe faltante real UAT `-10` en turno `TUR-20260703-002-002`.
- `/ventas/reportes` lo muestra en seguimiento como `pendiente_revision`.
- Aun no existe tabla formal de revision/resolucion.

Alcance propuesto:

- Crear tabla `erp_pos_turnos_diferencias_revision`.
- No modificar `erp_pos_turnos`.
- No mover dinero.
- No mover inventario.
- Preparar estados: `pendiente_revision`, `en_revision`, `explicada`, `aceptada`, `ajustada`, `escalada`, `cancelada`.

Frase sugerida:

```text
AUTORIZO PREPARAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

DDL ya preparado. Para aplicarlo:

```text
AUTORIZO APLICAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

DDL aplicado. Siguiente frase para expediente formal del faltante UAT:

```text
AUTORIZO REGISTRAR REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_REVISION_REAL id_usuario=1 id_turno_caja=12 motivo="UAT faltante de caja controlado" responsable="Supervisor UAT" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

Expediente registrado:

- `id_diferencia_revision=1`;
- folio `DIF-CAJ-20260703-000001`;
- estado `pendiente_revision`;
- faltante `-10`;
- turno `TUR-20260703-002-002`.

Siguiente autorizacion propuesta para preparar la resolucion formal:

```text
AUTORIZO PREPARAR RESOLUCION DIFERENCIA CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER para UAT POS
```

Resolucion preparada sin escritura real. Autorizacion esperada para resolver UAT:

```text
AUTORIZO RESOLVER REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL id_usuario=1 folio=DIF-CAJ-20260703-000001 decision=explicada motivo="UAT faltante explicado sin ajuste de caja" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

Ejecutado:

- expediente `DIF-CAJ-20260703-000001` quedo `explicada`;
- pendientes de revision `0`;
- reporte conserva faltante historico `-10`.

Siguiente autorizacion sugerida para exponer este flujo en UI:

```text
AUTORIZO EXPONER RESOLUCION DIFERENCIAS CAJA POS UI usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_UI para UAT POS
```

Preparado en codigo:

- `/ventas/reportes` ya muestra filtro por estado de revision;
- endpoint POST protegido para resolver;
- queda pendiente UAT visual del flujo desde navegador.

Siguiente paso sugerido:

```text
AUTORIZO EJECUTAR UAT VISUAL RESOLUCION DIFERENCIAS CAJA POS UI usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_UI_UAT para UAT POS
```

UAT visual ejecutada:

- `/ventas/reportes` carga correctamente;
- diferencia explicada visible en filtro `Todos`;
- no aparece boton `Resolver` para expediente cerrado;
- evidencia `public/storage/uat/pos_reportes_diferencias_uat.png`.

Siguiente autorizacion sugerida para permisos finos:

```text
AUTORIZO SEMBRAR PERMISOS DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS para UAT POS
```

Estado:

- codigo preparado;
- auditoria read-only confirma permisos faltantes;
- aplicador protegido bloquea sin token;
- no se tocaron permisos en BD todavia.

Ejecutado:

- permisos finos sembrados;
- faltantes posteriores `[]`;
- relaciones por rol aplicadas;
- no se tocaron turnos, caja ni inventario.

Siguiente paso sugerido:

```text
AUTORIZO EJECUTAR UAT VISUAL PERMISOS DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS_UAT para UAT POS
```

Ejecutado como UAT read-only:

- reportes carga correctamente;
- permisos existen;
- faltantes `[]`;
- no hay accion resolver para expediente cerrado;
- no se movio caja ni inventario.

Siguiente paso recomendado:

```text
AUTORIZO PREPARAR CIERRE PERMISOS DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS_CIERRE para UAT POS
```

Ejecutado sin escritura adicional de BD:

- se retiro compatibilidad temporal con `ventas.operar`;
- resolver diferencias requiere `ventas.caja_diferencias.resolver`;
- usuario UAT `1` tiene permiso fino;
- UAT visual sigue correcta.

Siguiente paso sugerido:

```text
AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS FINAL usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD_FINAL para UAT POS
```

## Ejecutado - permisos Configuracion POS

La UI CRUD final de `/ventas/pos_configuracion` ya esta preparada en codigo, pero las rutas quedaron protegidas con permisos finos. Auditoria read-only confirma que estos permisos aun faltan en BD:

- `ventas.pos_config.ver`;
- `ventas.pos_config.crear`;
- `ventas.pos_config.editar`;
- `ventas.pos_config.desactivar`;
- `ventas.pos_config.asignar_usuario`.

Autorizacion ejecutada:

```text
AUTORIZO SEMBRAR PERMISOS CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_PERMISOS para UAT POS
```

Alcance de esa autorizacion:

- crea/actualiza permisos `ventas.pos_config.*`;
- vincula roles base `direccion`, `administrador_erp` y `auditor`;
- no asigna usuarios directos;
- no abre turnos;
- no mueve caja;
- no mueve inventario.

Resultado:

- permisos sembrados `5`;
- relaciones intentadas `11`;
- usuario UAT `1` con permisos confirmados;
- faltantes `[]`.

Siguiente autorizacion sugerida:

```text
AUTORIZO EJECUTAR UAT VISUAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_UI_UAT para UAT POS
```

Ejecutado sin escritura de BD:

- UAT visual `/ventas/pos_configuracion` `ok=true`;
- botones editar/desactivar visibles;
- formularios se llenan al editar;
- no se presiono Guardar ni Desactivar;
- configuracion posterior sin cambios operativos.

Siguiente autorizacion sugerida:

```text
AUTORIZO EJECUTAR CRUD REAL CONFIGURACION POS UAT usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD id_usuario=1 para UAT POS
```

Ejecutado:

- alta caja UAT `id_caja=3`;
- alta terminal UAT `id_terminal_pos=3`;
- alta asignacion UAT `id_usuario_caja=3`;
- baja logica de asignacion, terminal y caja UAT;
- auditoria final `ok=true`;
- turnos abiertos `0`;
- no movio caja ni inventario.

Siguiente posible autorizacion si se decide probar cambios desde UI con escritura real:

```text
AUTORIZO EJECUTAR GUARDADO REAL CONFIGURACION POS DESDE UI usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_UI_REAL para UAT POS
```

## Pendiente actual - Siguiente fase POS tras cierre Caja/Turnos

La pantalla `/ventas/caja_turnos` ya valida apertura en dry-run y el ciclo real UAT fue cerrado correctamente: apertura, stock, venta, ticket, kardex, caja y cierre.

Ejecutado hasta ahora:

- turno `TUR-20260704-002-001`;
- `id_turno_caja=13`;
- almacen `5`;
- caja `2`;
- terminal `2`;
- movimiento inicial `id_movimiento_caja=27`;
- monto inicial `500`;
- stock UAT SKU `1760`, cantidad `1`, referencia `INV-INICIAL-POS-UAT-20260704-A5-S1760-CAJA-TURNOS`;
- venta `POS-20260704-000001`;
- `id_venta=13`;
- pago `295`;
- movimiento caja venta `id_movimiento_caja=28`;
- movimiento inventario `id_movimiento_inventario=75`;
- ticket formal read-only generado sin hallazgos;
- cierre real ejecutado;
- monto esperado `795`;
- monto contado `795`;
- diferencia `0`;
- turno abierto actual `false`;
- turnos abiertos `0`.

Siguiente linea de trabajo recomendada A - UAT real cierre desde UI:

- ya existe captura visual de arqueo por denominacion/metodo en `/ventas/caja_turnos`;
- ya existe cierre real controlado desde UI;
- falta probarlo con turno abierto nuevo desde navegador;
- mostrar confirmacion final con resumen previo por metodo de pago ya queda preparado;
- permitir cierre con diferencia;
- mandar diferencias a seguimiento;
- generar ticket/corte de caja imprimible;
- no mover inventario;
- no modificar ventas ya cobradas.

Autorizacion futura sugerida para preparar datos de UAT:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS para cierre real desde UI"
```

Despues de abrir turno, la prueba manual esperada es:

```text
1. Entrar a /ventas/caja_turnos.
2. Revisar que haya 1 turno abierto.
3. Capturar arqueo por denominaciones/metodos.
4. Presionar Simular corte sin cerrar.
5. Escribir CERRAR TURNO.
6. Presionar Cerrar turno real.
7. Confirmar que turnos abiertos quede en 0.
```

Estado posterior al intento automatizado:

- Se recibio autorizacion para cierre UI real del turno `14`.
- Playwright no pudo ejecutar por entorno web local:
  - `dashboard.com.local` puerto 80 rechazo conexion;
  - servidor PHP temporal por puerto `8000` entro en redireccion.
- No se ejecuto cierre por aplicador CLI porque la autorizacion especificaba UI.
- Turno `TUR-20260704-002-002` sigue abierto.
- Preflight cierre contado `500`: bloqueos `[]`, diferencia `0`.

Opciones actuales:

- Probar manualmente desde el navegador donde la app ya este abierta.
- O autorizar cierre por aplicador/modelo fuera de UI:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=500 observaciones="Cierre UAT POS turno TUR-20260704-002-002 tras bloqueo UI local"
```

Estado actualizado:

- Cierre por aplicador autorizado ejecutado.
- Turno `TUR-20260704-002-002` cerrado.
- Monto esperado `500`.
- Monto contado `500`.
- Diferencia `0`.
- Turnos abiertos `0`.
- Sigue pendiente UAT visual de cierre real desde UI cuando el entorno web local quede estable.

Siguiente linea de trabajo recomendada B - Pedidos/Apartados POS:

- crear pedido/apartado desde atencion POS;
- reservar o no reservar inventario segun politica;
- registrar abonos ligados a caja/turno;
- liquidar y convertir a venta final;
- soportar clientes CRM cuando el modulo quede listo;
- no mezclar ecommerce ni legacy.

Autorizacion futura sugerida:

```text
AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS para UAT POS
```

## Pendiente actual - Flujo real Pedidos/Apartados POS

Preflight read-only agregado:

- `storage/uat/uat_ventas_pos_pedidos_real_preflight_readonly.php`

Ejecucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedidos_real_preflight_readonly.php --compact=1
```

Resultado:

- `ok=false`;
- `read_only=true`;
- asignacion POS activa: `true`;
- turno abierto: `false`;
- politica activa de apartado: `1`;
- stock UAT suficiente para SKU `1760` en almacen `5`: `false`.

Bloqueos reales detectados:

- falta `VentasErp::pedidoGuardarReal`;
- falta `VentasErp::apartadoAbonoReal`;
- falta `VentasErp::pedidoEntregarReal`;
- falta `VentasErp::pedidoCancelarReal`;
- falta `InventarioErp::consumirReserva`.

Hallazgos operativos:

- sin turno abierto, por lo tanto no se pueden registrar anticipos/abonos reales;
- sin existencia suficiente para reserva UAT del SKU `1760` en almacen `5`.

Interpretacion:

- El esquema base/expandido y la politica de apartado ya permiten disenar el flujo real.
- La UI debe seguir en consulta/simulacion hasta implementar los metodos reales transaccionales.
- La primera fase real debe cubrir solo:
  - crear pedido/apartado con reserva;
  - registrar anticipo inicial;
  - registrar evento;
  - dejar trazabilidad de reserva.
- Entrega/cancelacion pueden quedar como segunda fase real si se desea reducir riesgo.

Autorizacion fuerte recomendada para implementar flujo real sin ejecutar una venta automaticamente:

```text
AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS
```

Despues de preparar codigo real, las UAT con escritura deberan pedirse por separado:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT Pedidos/Apartados POS"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-PEDIDOS-01

AUTORIZO CREAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio=295 anticipo=100 telefono=3312345678 cliente="Cliente UAT Apartado POS"
```

Estado actualizado 2026-07-05:

- Flujo real preparado en codigo.
- Preflight read-only final `ok=true`.
- Pendiente operativo para ejecutar UAT real:
  - abrir turno;
  - cargar stock;
  - crear apartado real.

Siguiente autorizacion recomendada:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT Pedidos/Apartados POS"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-PEDIDOS-01
```

Despues:

```text
AUTORIZO CREAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario=1 id_sku=1760 cantidad=1 precio=295 anticipo=100 telefono=3312345678 cliente="Cliente UAT Apartado POS"
```
## Pendiente 2026-07-06 - UAT real apartado multipartida desde UI/script

Contexto:

- Estado actualizado: ciclo UAT real completado por script el 2026-07-06.
- Folio probado: `APT-20260706-000001`.
- Resultado:
  - apartado creado;
  - anticipo registrado;
  - liquidacion registrada;
  - entrega realizada;
  - reservas consumidas;
  - kardex/trazabilidad generados;
  - turno `TUR-20260706-002-001` cerrado con diferencia `0`.
- La UI de `/ventas/pedidos` ya permite multiples partidas.
- El script `storage/uat/uat_ventas_pos_pedido_apartado_apply_authorized.php` ya acepta `--item=id_sku,cantidad,precio` repetible.
- Read-only con dos partidas ya valido contrato y bloqueo correctamente por:
  - falta de turno abierto;
  - stock insuficiente;
  - cantidad fraccionaria no valida para el SKU usado.

Autorizacion robusta sugerida para preparar datos:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS apartado multipartida"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=2 referencia=INV-INICIAL-POS-UAT-MULTIPARTIDA-1760
```

Autorizacion robusta sugerida para ejecutar apartado multipartida:

```text
AUTORIZO CREAR APARTADO MULTIPARTIDA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario=1 telefono=3312345678 cliente="Cliente UAT Apartado Multipartida POS" anticipo=120 item=1760,1,295 item=1760,1,295
```

Verificacion read-only posterior sugerida:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedido_multipartida_post_readonly.php --folio=FOLIO_APT --compact=1
```

Runbook read-only completo sugerido:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedido_multipartida_runbook_readonly.php --compact=1
```

Readiness actual antes de autorizar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedido_multipartida_readiness_readonly.php --compact=1
```

Secuencia posterior sugerida despues de crear `FOLIO_APT`:

```text
AUTORIZO REGISTRAR ABONO APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_APARTADO_ABONO_REAL id_usuario=1 folio=FOLIO_APT monto=470 referencia=UAT-LIQ-FOLIO_APT

AUTORIZO ENTREGAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_ENTREGA_REAL id_usuario=1 folio=FOLIO_APT

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=1090 observaciones="Cierre UAT POS apartado multipartida FOLIO_APT"
```

## Pendiente 2026-07-06 - Recuperar MySQL antes de UAT real POS

Contexto:

- Se recibio autorizacion para abrir turno y cargar stock multipartida.
- Los scripts no escribieron BD porque la conexion PDO fue `null`.
- `mysqld --console` reporto:
  - `InnoDB: Missing MLOG_CHECKPOINT`;
  - `Unknown/unsupported storage engine: InnoDB`;
  - `Aborting`.
- El preflight read-only de recuperacion confirma respaldo disponible y rutas validas.

Autorizacion robusta requerida antes de reintentar apertura/stock:

```text
AUTORIZO RECUPERAR MYSQL UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token MYSQL_UAT_POS_RECOVERY permitiendo respaldo previo de C:\xampp\mysql\data, arranque controlado de MariaDB, diagnostico InnoDB y restauracion/importacion solo si es necesario para continuar UAT POS
```

Despues de recuperar MySQL, repetir:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS apartado multipartida"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=2 referencia=INV-INICIAL-POS-UAT-MULTIPARTIDA-1760
```

Estado actualizado:

- MySQL recuperado por diagnostico.
- Turno/stock/apartado/abono/entrega/cierre ejecutados correctamente.
- No repetir estas autorizaciones con las mismas referencias.

## Pendiente 2026-07-06 - UAT cancelacion apartado antes de entrega

Contexto:

- Ya existe aplicador protegido `storage/uat/uat_ventas_pos_pedido_cancelar_apply_authorized.php`.
- Ya existe runbook read-only `storage/uat/uat_ventas_pos_pedido_cancelacion_runbook_readonly.php`.
- Objetivo: probar cancelacion antes de entrega, liberacion de reserva y ausencia de kardex de salida.
- Este flujo no reembolsa caja automaticamente; pagos previos quedan para decision financiera posterior.

Autorizacion robusta sugerida para preparar datos:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS cancelacion apartado"

AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-CANCEL-APT-1760
```

Autorizacion sugerida para crear apartado de prueba:

```text
AUTORIZO CREAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario=1 telefono=3312345678 cliente="Cliente UAT Cancelacion Apartado POS" anticipo=100 item=1760,1,295
```

Autorizacion sugerida para cancelar:

```text
AUTORIZO CANCELAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_CANCELAR_REAL id_usuario=1 folio=FOLIO_APT motivo="UAT cancelacion apartado antes de entrega"
```

Cierre sugerido:

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=600 observaciones="Cierre UAT POS cancelacion apartado FOLIO_APT"
```

## Pendiente 2026-07-06 - Decision financiera de apartado cancelado

Contexto:

- UAT cancelacion antes de entrega completada con `APT-20260706-000002`.
- El apartado quedo cancelado.
- La reserva fue liberada.
- No hubo kardex de salida.
- El anticipo de `$100.00` sigue registrado.
- Auditor read-only:
  - `storage/uat/uat_ventas_pos_pedido_cancelado_finanzas_readonly.php`;
  - `monto_pendiente_decision=100`;
  - hallazgos `[]`.

Decision de arquitectura:

- No resolver con un movimiento suelto de caja.
- Crear/implementar un flujo formal ligado al folio cancelado:
  - saldo a favor cliente/CRM;
  - reembolso de caja con turno abierto y evidencia;
  - penalizacion autorizada por politica;
  - sin reembolso solo si no hubo pago.

Siguiente trabajo sin autorizacion de BD:

- Diseñar esquema/contrato read-only para decisiones financieras de pedidos/apartados cancelados.
- Definir permisos:
  - `ventas.pedidos.finanzas.ver`;
  - `ventas.pedidos.finanzas.resolver`;
  - `ventas.pedidos.finanzas.reembolso_caja`;
  - `ventas.pedidos.finanzas.penalizacion`.
- Definir UAT:
  - saldo a favor;
  - reembolso de caja;
  - penalizacion parcial;
  - rechazo por folio no cancelado.

Estado actualizado:

- Plan creado: `docs/erp_ventas_pos_pedidos_finanzas_plan.md`.
- Auditor read-only creado: `storage/uat/uat_ventas_pos_pedidos_finanzas_schema_readonly.php`.
- Resultado auditor:
  - tabla `erp_ventas_pedidos_decisiones_financieras` no existe;
  - columnas faltantes `29`;
  - indices faltantes `7`.

Autorizacion robusta sugerida para siguiente fase DDL:

```text
AUTORIZO APLICAR DDL DECISIONES FINANCIERAS PEDIDOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_FINANZAS_DDL para UAT POS
```

Alcance de esa autorizacion:

- crear `erp_ventas_pedidos_decisiones_financieras`;
- no resolver decisiones;
- no mover caja;
- no crear saldos cliente;
- no modificar inventario.

Estado actualizado:

- DDL aplicado correctamente el 2026-07-06.
- Tabla `erp_ventas_pedidos_decisiones_financieras` existe.
- Columnas faltantes `0`.
- Indices faltantes `0`.
- Decisiones registradas iniciales `0`.

Siguiente trabajo sin autorizacion de dinero:

- Crear dry-run para solicitar decision financiera sobre `APT-20260706-000002`.
- Validar bloqueos:
  - folio no cancelado;
  - monto cero;
  - decision invalida;
  - reembolso sin turno abierto;
  - saldo favor sin cliente/identificador.

Autorizacion ejecutada:

```text
AUTORIZO REGISTRAR DECISION FINANCIERA APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_FINANZAS_REAL id_usuario=1 folio=APT-20260706-000002 decision=saldo_favor monto=100 motivo="UAT saldo a favor por cancelacion apartado"
```

Resultado:

- expediente `PFIN-20260706-000001` registrado en `erp_ventas_pedidos_decisiones_financieras`;
- decision `saldo_favor`;
- monto base `100`;
- estatus `pendiente_ledger_crm`;
- no movio caja;
- no creo saldo CRM real;
- no movio inventario.

Estado dry-run actualizado:

- Script creado: `storage/uat/uat_ventas_pos_pedidos_finanzas_decision_dryrun.php`.
- Caso `saldo_favor` para `APT-20260706-000002`:
  - `ok=true`;
  - monto `100`;
  - bloqueos `[]`.
- Bloqueos comprobados:
  - reembolso sin turno abierto;
  - folio entregado;
  - decision invalida;
  - monto mayor al pagado;
  - sin reembolso con pago registrado.

Estado DDL CRM Saldos:

- DDL CRM Saldos aplicado correctamente el 2026-07-06.
- `crm_clientes_saldos_cuentas` existe.
- `crm_clientes_saldos_movimientos` existe.
- Auditor posterior: `ddl_pendientes=0`.

Estado integracion saldo favor:

- Dry-run usado:
  - `storage/uat/uat_ventas_pos_pedidos_cliente_crm_link_dryrun.php`.
- Apply usado:
  - `storage/uat/uat_ventas_pos_pedidos_cliente_crm_link_apply_authorized.php`.
- Resultado:
  - cliente CRM `157`;
  - cuenta saldo CRM `1`;
  - movimiento `CRM-SAL-20260706-000001`;
  - saldo disponible MXN `100`;
  - decision `PFIN-20260706-000001` aplicada;
  - no movio caja;
  - no movio inventario;
  - no uso recompensas.

Siguiente autorizacion recomendada:

```text
AUTORIZO PREPARAR APPLY REAL USO SALDO CRM EN POS usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_APPLY_PREP para UAT POS
```

Alcance:

- preparar flujo real transaccional para aceptar saldo CRM como pago POS;
- no ejecutar venta real todavia;
- no descontar saldo todavia;
- no mover inventario todavia;
- no mover caja;
- dejar apply protegido para una UAT posterior con stock, turno abierto y carrito concreto.

Estado dry-run uso saldo CRM:

- Script: `storage/uat/uat_ventas_pos_saldo_crm_uso_dryrun.php`.
- Cliente CRM `157`.
- Saldo disponible `100`.
- Monto `100`.
- Resultado `ok=true`.
- Bloqueos validados:
  - saldo insuficiente;
  - cliente/cuenta inexistente;
  - monto mayor al total porque saldo CRM no genera cambio.

## Ejecutado - Venta POS real usando saldo CRM

Estado actual:

- Ciclo real ejecutado el 2026-07-07.
- Cliente CRM `157` tenia saldo disponible `100` antes de la venta y quedo en `0`.
- `VentasErp::prevalidarPagosPos` ya acepta `saldo_crm` como pago virtual.
- La ruta real de cobro ya acepta `saldo_crm` con ledger CRM y sin movimiento de caja.
- Venta `POS-20260707-000001`, turno `TUR-20260707-002-001`.
- Cierre con monto esperado `695`, contado `695`, diferencia `0`.
- Saldo CRM aplicado con movimiento `CRM-SAL-20260707-000001`.

Si MySQL vuelve a caer antes de la UAT real, se debe repetir preflight read-only antes de pedir recuperacion.

Autorizaciones consumidas:

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS saldo CRM"
```

AUTORIZO EJECUTAR VENTA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_REAL id_usuario=1 id_cliente_crm=157 id_sku=1760 cantidad=1 precio=295 monto_saldo_crm=100 pago_caja=195

AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=695 observaciones="Cierre UAT POS venta con saldo CRM"
```

Contrato de la ejecucion real:

- crear venta, detalle, kardex y trazabilidad;
- crear pago caja solo por `195`;
- crear pago `saldo_crm` por `100` con `id_movimiento_caja=NULL`;
- descontar saldo CRM de `100` a `0`;
- crear movimiento CRM `uso_saldo_pos`;
- actualizar `monto_esperado` del turno solo por el pago de caja;
- cierre esperado con monto inicial `500` y caja `195`: `695`;
- no usar recompensas.

## Ejecutado - DDL reversas POS con saldo CRM

Estado:

- DDL aplicado el 2026-07-07 con token `VENTAS_POS_REVERSA_SALDO_CRM_DDL`.
- Script: `storage/uat/uat_ventas_pos_reversa_saldo_crm_readiness_readonly.php`.
- Venta auditada: `POS-20260707-000001`.
- Resultado:
  - total venta `$295.00`;
  - caja pagada `$195.00`;
  - saldo CRM pagado `$100.00`;
  - reembolso completo desde caja queda bloqueado por `$100.00`;
  - `saldo_favor` aun no crea movimiento CRM automatico.

DDL aplicado:

- `erp_ventas_devoluciones.id_cliente_crm`;
- `erp_ventas_devoluciones.monto_reintegro_saldo_crm`;
- `erp_ventas_devoluciones.monto_no_caja`;
- `erp_ventas_devoluciones_finanzas`.

Ejecutado:

- `VENTAS_POS_REVERSA_SALDO_CRM_MODELO`;
- modelo transaccional preparado;
- aplicador real protegido creado;
- post-readonly ampliado.

Intento ejecutado:

- `VENTAS_POS_REVERSA_SALDO_CRM_REAL` se intento el 2026-07-07.
- Resultado:
  - bloqueado por falta de turno abierto;
  - no creo devolucion;
  - no movio caja;
  - no movio saldo CRM;
  - no movio inventario.

Ejecutado:

- Se abrio turno `TUR-20260707-002-002`.
- Se ejecuto reversa `DEV-20260707-000001`.
- Se cerro turno con diferencia `$0.00`.
- Venta `POS-20260707-000001` quedo `devuelta`.

Siguientes autorizaciones recomendadas:

```text
AUTORIZO REGISTRAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario=1 id_movimiento_caja=41 tipo_evidencia=ticket_firmado referencia_externa=DEV-20260707-000001 descripcion="Comprobante UAT de reembolso mixto caja y saldo CRM firmado por cliente"
```

Comando preparado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencia_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIA_REAL --respaldo=UAT_POS_VIGENTE --respaldo_uat_vigente=1 --id_usuario=1 --id_movimiento_caja=41 --tipo_evidencia=ticket_firmado --referencia_externa=DEV-20260707-000001 --descripcion="Comprobante UAT de reembolso mixto caja y saldo CRM firmado por cliente"
```

Alcance propuesto:

- registrar evidencia documental del movimiento caja `41`;
- no mover caja;
- no mover saldo CRM;
- no mover inventario.

Guardrails:

- requiere token de evidencia caja;
- conserva la evidencia del reembolso como registro operativo;
- debe quedar pendiente de revision/aprobacion segun flujo de evidencias.

## Ejecutado - Evidencia reembolso mixto caja/saldo CRM

Estado:

- Ejecutado el 2026-07-07 con token `VENTAS_POS_CAJA_EVIDENCIA_REAL`.
- Evidencia creada `id_evidencia_caja=3`.
- Movimiento caja `41`.
- Estatus evidencia `recibida`.
- Referencia externa `DEV-20260707-000001`.
- No movio caja, saldo CRM ni inventario.

Siguiente autorizacion recomendada:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=1 id_evidencia_caja=3 decision=aprobada permitir_mismo_usuario=1
```

Alcance propuesto:

- aprobar evidencia UAT del reembolso caja `41`;
- mantener trazabilidad de que el mismo usuario revisa solo por UAT controlada;
- no mover dinero;
- no mover inventario;
- no modificar la devolucion ni el saldo CRM.

Resultado ejecutado:

- Autorizacion recibida y aplicada el 2026-07-07.
- Evidencia `3` quedo `aprobada`.
- Movimiento caja `41` quedo con `evidencia_estado=aprobada`.
- Reversa `DEV-20260707-000001` valida sin hallazgos.
- No movio caja, saldo CRM ni inventario.

## Pendiente - Inspeccion fisica devolucion mixta POS

Estado read-only:

- Bandeja de devoluciones fisicas pendientes:
  - `total_registros=2`;
  - ambas en decision inventario `cuarentena`.
- Nueva devolucion mixta pendiente:
  - devolucion `DEV-20260707-000001`;
  - `id_devolucion_detalle=3`;
  - venta `POS-20260707-000001`;
  - cliente CRM `157`;
  - SKU `TP-40352-500GR`;
  - cantidad `1`;
  - estado inspeccion `pendiente`.
- Dry-run con `id_usuario=1`:
  - `ok=true`;
  - sin bloqueos;
  - decision `mantener_cuarentena`;
  - no crea kardex;
  - no mueve inventario;
  - no liga garantia.
- Scripts:
  - `storage/uat/uat_ventas_pos_devolucion_inspeccion_fisica_apply_authorized.php` acepta `UAT_POS_VIGENTE`;
  - sin token queda bloqueado correctamente.

Autorizacion recomendada despues de revisar/aprobar evidencia de caja:

```text
AUTORIZO REGISTRAR INSPECCION FISICA DEVOLUCION POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_DEVOLUCION_FISICA_REAL id_usuario=1 id_devolucion_detalle=3 decision_fisica=mantener_cuarentena condicion_producto=pendiente_revision motivo="UAT mantener en cuarentena sin mover inventario" diagnostico="Producto pendiente de inspeccion fisica"
```

Alcance:

- registrar inspeccion documental;
- confirmar cuarentena;
- no reintegrar disponible;
- no crear merma;
- no crear kardex;
- no mover inventario.

Resultado ejecutado:

- Autorizacion recibida y aplicada el 2026-07-07.
- Inspeccion `IFD-20260707-000001`, `id_inspeccion_fisica=2`.
- Devolucion `DEV-20260707-000001`, detalle `3`.
- Estado `cuarentena_confirmada`.
- No creo kardex.
- No movio inventario.
- No creo garantia.
- La bandeja pendiente queda con solo un pendiente historico: `DEV-20260630-000001`.

Siguiente decision recomendada:

- Cerrar el pendiente historico con una inspeccion documental similar, o
- avanzar interfaz operativa para que evidencias, devoluciones e inspecciones se gestionen desde POS/Backoffice sin scripts UAT.
