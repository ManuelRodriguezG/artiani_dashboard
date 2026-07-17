# ERP Ventas POS - Checklist de pase a prueba real

Documentacion IA: Codex GPT-5, 2026-07-15.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico: `http://panel.com.local/`.

## Objetivo

Dejar claro que falta para pasar POS de UAT controlada a prueba real operativa en tienda, sin confundir pruebas read-only, scripts autorizados y uso diario.

## Estado listo para UAT real controlada

- POS opera por usuario asignado a almacen/caja/terminal.
- No debe usarse `localhost`; usar `panel.com.local`.
- Atenciones multiusuario:
  - se pueden crear como cuentas persistentes;
  - se pueden consultar en bandeja;
  - se pueden cargar en caja;
  - se convierten a venta solo por backend real con atencion bloqueada.
- Caja/turno:
  - apertura real con monto inicial;
  - cierre real con monto contado;
  - diferencias permitidas y auditables.
- Inventario:
  - venta normal descuenta con kardex;
  - venta con inventario pendiente genera pendiente para mini inventario;
  - resolucion de pendiente POS ya fue validada en UAT anterior.
- Ticket:
  - ticket formal read-only por folio;
  - snapshot de garantia revisable por venta.
- Checador:
  - checador de precios read-only;
  - escaneo con camara en POS preparado como herramienta de captura, separado del checador.

## UAT agrupada siguiente

Suite read-only de pase a prueba real:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pase_prueba_real_suite_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2
```

Resultado 2026-07-16:

- `ok=true`.
- Checks ejecutados: `12` desde 2026-07-17.
- Sin bloqueos.
- Dependencias ciclo real:
  - scripts revisados: `11`;
  - todos existen;
  - todos son legibles;
  - todos pasan sintaxis PHP;
  - `listo_dependencias_ciclo_real=true`.
- Sintaxis UI/backend visible:
  - archivos revisados: `7`;
  - controlador `Ventas.php`: OK;
  - modelos `VentasErp.php` y `Venta.php`: OK;
  - vistas POS y checador: OK;
  - JS POS y checador: OK;
  - `ui_sintaxis_ok=true`.
- Superficie de rutas POS:
  - metodos esperados en `Ventas.php`: `22`;
  - metodos encontrados en controlador: `98`;
  - URLs JS criticas revisadas: `12`;
  - sin metodos faltantes;
  - sin URLs JS faltantes;
  - `surface_ok=true`.
- Encoding/BOM:
  - archivos revisados: `10`;
  - sin BOM UTF-8 en controlador, modelos, vistas, JS ni scripts UAT criticos;
  - `encoding_bom_ok=true`.
- Guardrail ciclo real:
  - sin token/respaldo el orquestador responde `bloqueado=true`;
  - no abre turno;
  - no carga stock;
  - no cobra;
  - no cierra caja.
- Permisos usuario `1`:
  - puede operar POS;
  - roles detectados: `administrador_erp`, `soporte_sistema`;
  - sin faltantes de operacion;
  - sin faltantes de configuracion;
  - permisos sensibles presentes para UAT controlada.
- Configuracion POS:
  - esquema POS completo para configuracion;
  - almacenes vendibles: `2`;
  - cajas POS: `2`;
  - terminales POS: `3`;
  - asignaciones usuario/caja/terminal: `3`;
  - en almacen `5`: `1` caja, `2` terminales, `2` asignaciones;
  - usuario `1` asignado a caja `2`, terminal `2`.
- Inventario SKU UAT:
  - SKU `1760` / `TP-40352-500GR`;
  - producto activo;
  - existencia en almacen `5`: `0` disponible;
  - reservas activas: `0`;
  - pendientes POS abiertos: `0`;
  - no cubre cantidad requerida antes de UAT real, lo cual es esperado porque el ciclo autorizado carga `1` pieza antes de cobrar.
- Avisos esperados antes de UAT real:
  - no hay turno abierto;
  - falta existencia;
  - la atencion sigue pendiente y por eso el post-check de conversion aun marca hallazgos.
- `listo_para_autorizacion_agrupada=true`.

Prevalidacion read-only validada:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_atencion_multiusuario_ciclo_apply_authorized.php --prevalidar=1 --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad_stock=1 --pago=295 --monto_inicial=500 --monto_contado=795
```

Resultado actual:

- Sin bloqueos antes de escrituras.
- `id_atencion_pos=2` esta `lista_para_cobro`.
- Usuario `1` asignado a caja `2`, terminal `2`, almacen `5`.

Autorizacion para ciclo real:

```text
AUTORIZO EJECUTAR CICLO REAL ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL id_usuario=1 id_almacen=5 id_sku=1760 id_atencion=2 cantidad_stock=1 pago=295 monto_inicial=500 monto_contado=795 para UAT POS
```

## Criterio de exito de la UAT agrupada

- Turno abierto.
- Stock cargado con referencia unica.
- Atencion cobrada.
- Venta generada con `folio` e `id_venta`.
- Atencion queda `convertida`.
- Ticket formal read-only sin hallazgos altos.
- Post-venta read-only sin hallazgos altos:
  - venta;
  - detalle;
  - pagos;
  - movimiento de caja;
  - kardex/trazabilidad;
  - garantia.
- Turno cerrado con monto contado.
- Semaforo final sin bloqueos operativos inesperados.

Suite de evidencia posterior:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ciclo_evidencia_readonly.php --id_atencion=2 --id_usuario=1
```

Diagnostico read-only si el ciclo se interrumpe:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ciclo_recuperacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2
```

Este diagnostico no corrige, no revierte, no cierra turno y no mueve inventario. Solo resume si el usuario esta asignado, si hay turno abierto, si el inventario cubre la venta, si la atencion ya fue convertida y que accion corresponde despues.

Estado antes de UAT real:

- `ok=true`.
- Sin folio detectado, esperado antes de ejecutar ciclo real.
- Post-conversion muestra atencion aun `lista_para_cobro`.

Estado esperado despues de UAT real:

- Folio POS detectado.
- `id_venta` detectado.
- Ticket formal read-only ejecutado.
- Post-venta read-only ejecutado.
- Atencion convertida sin hallazgos altos.

## Para pasar a prueba real en tienda

Antes de operar con clientes reales:

- Definir al menos una caja y terminal por tienda real.
- Asignar usuarios reales a caja/terminal/almacen.
- Confirmar permisos por rol:
  - operar POS;
  - abrir/cerrar turno;
  - registrar gastos/retiros;
  - revisar diferencias de caja;
  - autorizar excepciones comerciales;
  - resolver pendientes de inventario.
- Definir politica de stock:
  - venta solo con existencia;
  - o venta con inventario pendiente limitada por tienda/SKU/importe.
- Tener impresora/ticket definido:
  - formato 58/80 mm;
  - leyenda de cambios/devoluciones;
  - datos de tienda;
  - garantia por producto.
- Tener flujo de cierre diario:
  - apertura;
  - cortes parciales si aplica;
  - gastos/retiros;
  - cierre con diferencia;
  - revision de diferencia.

## Readiness Productivo

Validacion 2026-07-17 en `C:\xampp\htdocs\panel_de_control`:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_productivo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1
```

Resultado:

- `ok=true`.
- Sin bloqueos para POS base.
- Tablas base presentes:
  - ventas;
  - detalle;
  - pagos;
  - kardex/trazabilidad;
  - garantias;
  - cajas;
  - terminales;
  - turnos;
  - movimientos de caja;
  - inventario pendiente;
  - notificaciones;
  - CRM saldos;
  - listas de precios.
- Usuario `1` tiene permisos base para operar POS, CRM, inventario, listas y revisiones de caja.
- Usuario `1` tiene asignacion activa a almacen `5`, caja `2`, terminal `2`.
- Turnos abiertos: `0`; esperado fuera de horario, pero bloquea cobro real hasta abrir turno.
- Pendientes POS de inventario abiertos: `0`.
- Notificaciones POS abiertas: `0`.
- Aviso pendiente para promocion productiva completa:
  - falta sembrar permiso fino `ventas.pos.inventario_pendiente.autorizar`;
  - inventario pendiente productivo debe reemplazar token UAT por permiso, motivo obligatorio, auditoria explicita, politica por sucursal/SKU/canal y alerta a Inventario/Existencias.

## Semaforo Final POS

Comando unico read-only para cierre del modulo:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_final_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
```

Resultado 2026-07-17:

- `ok=true`.
- `pase_uat_multiusuario_listo=true`.
- `pos_base_productivo_sin_bloqueos=true`.
- `atencion_sigue_pendiente_pre_ciclo=true`.
- Checks consolidados: `4`.
- Bloqueos: `[]`.
- Estado de recuperacion:
  - usuario asignado: `true`;
  - turno abierto: `false`;
  - inventario cubre: `false`;
  - reservas activas: `0`;
  - pendientes POS abiertos: `0`;
  - atencion convertida: `false`;
  - venta ligada: `false`.
- Avisos esperados:
  - no hay turno abierto;
  - falta stock antes de UAT real;
  - la atencion sigue pendiente antes del cobro real;
  - falta permiso fino productivo para inventario pendiente.

## Paquete de Autorizacion

Comando read-only para generar el paquete antes del ciclo real:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_autorizacion_ciclo_multiusuario_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad_stock=1 --pago=295 --monto_inicial=500 --monto_contado=795
```

Resultado 2026-07-17:

- `ok=true`.
- Precheck `cierre_final`: `ok=true`.
- Precheck guardrail sin token: `bloqueado=true`.
- Bloqueos: `[]`.
- Alcance si se autoriza:
  - abre turno;
  - carga stock UAT;
  - cobra atencion;
  - crea venta;
  - mueve caja;
  - mueve inventario/kardex;
  - convierte atencion;
  - valida ticket;
  - valida postventa;
  - cierra turno.

Autorizacion humana vigente:

```text
AUTORIZO EJECUTAR CICLO REAL ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL id_usuario=1 id_almacen=5 id_sku=1760 id_atencion=2 cantidad_stock=1 pago=295 monto_inicial=500 monto_contado=795 para UAT POS
```

Postchecks despues de autorizar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ciclo_evidencia_readonly.php --id_atencion=2 --id_usuario=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_final_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --id_atencion=2 --cantidad=1
```

Guia manual para validar en navegador:

- Ver `docs/erp_ventas_pos_prueba_real_usuario.md`.

Checklist para pasar a piloto operativo:

- Ver `docs/erp_ventas_pos_piloto_operativo_checklist.md`.
- Solo aplica despues de ejecutar el ciclo real autorizado y confirmar postchecks sin bloqueos.

## Pendientes antes de considerar POS completo

- UI final de configuracion POS:
  - tiendas;
  - cajas;
  - terminales;
  - asignaciones de usuario.
- UI final de cierre/caja separada de venta.
- UI de atenciones multiusuario mas pulida:
  - tomar cuenta;
  - ver quien la creo;
  - ver ultima modificacion;
  - evitar doble cobro con estado visible.
- Listas de precios integradas desde modulo de precios.
- CRM final para cliente formal, historial y saldos.
- Reportes operativos:
  - venta por caja/turno/usuario;
  - diferencias de caja;
  - productos vendidos sin stock;
  - pendientes de inventario;
  - descuentos/excepciones.
- Devoluciones desde UI final con evidencia, inspeccion y destino de inventario.

## Regla de seguridad

No ejecutar escrituras masivas ni UAT real sin autorizacion explicita. Las pruebas read-only pueden ejecutarse para diagnostico y preparacion.

## Nota operativa MariaDB 2026-07-16 / 2026-07-17

Durante la preparacion del diagnostico de recuperacion, MariaDB local dejo de responder y el log activo `C:\xampp\mysql\data\LAPTOP-F4272OI7.err` reporto corrupcion de indice en la tabla interna `mysql.plugin`.

Accion segura realizada:

- Se detuvieron procesos colgados `mysqladmin`/`mysqld`.
- Se intento levantar MariaDB desde XAMPP.
- Se ejecuto `aria_chk --check mysql\plugin.MAI` desde `C:\xampp\mysql\data`.

Resultado:

- MariaDB no quedo escuchando en el puerto `3306`.
- `aria_chk` confirmo: `Index is corrupted`.

El 2026-07-17 el usuario levanto MariaDB desde XAMPP. La conexion del proyecto volvio a responder y se reejecutaron los checks POS read-only.

Resultado vigente 2026-07-17:

- Suite de pase a prueba real: `ok=true`.
- Checks ejecutados: `12`.
- Dependencias de ciclo real: `ok=true`, `11` scripts revisados, sin bloqueos.
- Sintaxis UI/backend POS: `ok=true`, `7` archivos revisados, sin bloqueos.
- Superficie rutas/JS POS: `ok=true`, `22` metodos esperados y `12` URLs criticas revisadas.
- Encoding/BOM POS: `ok=true`, `10` archivos revisados, sin BOM.
- Guardrail sin token/respaldo: `bloqueado=true`, sin escrituras.
- Diagnostico de recuperacion: `ok=true`.
- No hay bloqueo activo de MariaDB para continuar POS.
- Validacion en proyecto nuevo `C:\xampp\htdocs\panel_de_control`:
  - suite read-only ejecutada desde `panel_de_control`;
  - `ok=true`;
  - `bloqueos=[]`;
  - `listo_para_autorizacion_agrupada=true`;
  - semaforo muestra como aviso esperado que, antes de cargar stock UAT, la politica POS de inventario pendiente exige flujo con alerta y trazabilidad.
- Estado POS:
  - usuario `1` asignado a almacen `5`, caja `2`, terminal `2`;
  - atencion `2` en `lista_para_cobro`;
  - no hay turno abierto;
  - SKU `1760` sin stock disponible antes de la UAT real;
  - sin reservas activas;
  - sin pendientes POS abiertos.

Si MariaDB vuelve a fallar con el mismo error, la autorizacion requerida seria:

```text
AUTORIZO REPARAR TABLA INTERNA MYSQL PLUGIN usando respaldo UAT POS vigente con token MYSQL_PLUGIN_ARIA_RECOVERY para recuperar MariaDB local y continuar UAT POS
```

Despues de cualquier recuperacion de MariaDB, correr nuevamente la suite read-only de pase a prueba real antes de cualquier escritura UAT.

## Resultado vigente post ciclo real multiusuario

Fecha de evidencia: 2026-07-16 / 2026-07-17.

El ciclo real autorizado ya fue ejecutado en `C:\xampp\htdocs\panel_de_control`.

- Atencion convertida: `ATN-20260713-210522-889`, `id_atencion=2`.
- Venta POS: `POS-20260716-000001`, `id_venta=24`.
- Turno: `TUR-20260716-002-001`, `id_turno_caja=23`.
- Monto inicial: `$500.00`.
- Cobro: `$295.00` efectivo.
- Cierre: contado `$795.00`, diferencia `$0.00`.
- Ticket formal: `ok=true`, `28` lineas, hallazgos `[]`.
- Conversion atencion/postventa: `ok=true`, hallazgos `[]`.
- Semaforo final: `ok=true`, bloqueos `[]`, `ciclo_real_completo=true`.
- Semaforo piloto: `ok=true`, decision `apto_para_piloto_controlado`.

Lectura correcta del documento:

- Las secciones anteriores conservan el contexto previo y la autorizacion usada.
- Para el estado actual, tomar este bloque como vigente.
- No repetir el ciclo sobre la misma atencion porque ya esta convertida.