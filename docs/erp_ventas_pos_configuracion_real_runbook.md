# ERP Ventas/POS - Runbook CRUD real Configuracion POS

Fecha: 2026-07-03

Este runbook prepara el CRUD real de configuracion POS. No autoriza ni ejecuta escrituras por si mismo.

## Objetivo

Permitir administrar tiendas/almacenes vendibles, cajas, terminales y asignaciones usuario/caja para que el POS abra ya configurado por sucursal y caja, sin selector libre para el cajero.

## Principios

- El cajero no elige libremente tienda, almacen o caja al cobrar.
- El POS resuelve el contexto operativo por usuario, caja y terminal activa.
- La configuracion queda fuera del POS mostrador.
- Todo cambio real debe registrar usuario, fecha, estatus y auditoria.
- No cerrar, abrir turno ni mover caja desde el CRUD de configuracion.
- No mezclar cajas POS nuevas con tablas legacy sin auditoria.

## Tablas objetivo actuales

- `erp_pos_cajas`
- `erp_pos_terminales`
- `erp_pos_usuarios_cajas`
- Tablas relacionadas de solo consulta:
  - almacenes vendibles del modulo Inventario/Almacen;
  - usuarios y permisos de Seguridad;
  - `erp_pos_turnos`;
  - `erp_pos_movimientos_caja`.

## Estados propuestos

Caja:

- `activa`
- `inactiva`

Terminal:

- `activa`
- `inactiva`
- `mantenimiento`

Asignacion usuario/caja:

- `activo`
- `inactivo`
- `suspendido`

Regla: desactivar debe ser baja logica. No borrar fisicamente registros con turnos, ventas, movimientos o auditoria.

## Permisos recomendados

Inicialmente puede consultarse con `ventas.ver`, pero el CRUD real debe separar permisos:

- `ventas.pos_config.ver`
- `ventas.pos_config.crear`
- `ventas.pos_config.editar`
- `ventas.pos_config.desactivar`
- `ventas.pos_config.asignar_usuario`

Estado 2026-07-04:

- Los permisos finos ya quedaron declarados en `SeguridadEsquema`.
- Los endpoints reales ya no dependen de `ventas.operar`.
- `/ventas/pos_configuracion` y el resumen ahora requieren `ventas.pos_config.ver`.
- Crear/editar caja o terminal requiere `ventas.pos_config.crear` o `ventas.pos_config.editar`.
- Asignar usuario requiere `ventas.pos_config.asignar_usuario`.
- Desactivar requiere `ventas.pos_config.desactivar`.
- Mientras esos permisos no se siembren en BD, la pantalla queda protegida y no debe usarse el permiso general `ventas.operar` como sustituto.

## Endpoints a implementar

### `/ventas/pos_configuracion_caja_guardar_erp`

Crea o edita una caja POS.

Validaciones:

- usuario autenticado;
- permiso de configuracion;
- `id_almacen` vendible y activo;
- `codigo` unico, 3 a 40 caracteres, letras/numeros/guion/guion bajo;
- `nombre` obligatorio, maximo 120 caracteres;
- al menos un metodo permitido: efectivo, tarjeta o transferencia;
- no permitir cambiar caja de almacen si ya tiene turnos o ventas.

Escrituras:

- alta o actualizacion en `erp_pos_cajas`;
- evento/auditoria de configuracion;
- no abre turno;
- no mueve caja.

### `/ventas/pos_configuracion_terminal_guardar_erp`

Crea o edita una terminal POS.

Validaciones:

- `id_almacen` vendible y activo;
- `id_caja` activa y perteneciente al almacen;
- `codigo` unico, 3 a 60 caracteres;
- `nombre` obligatorio, maximo 150 caracteres;
- `identificador_terminal` recomendado para productivo;
- no permitir mover terminal de caja si tiene turno abierto o venta reciente sin cierre.

Escrituras:

- alta o actualizacion en `erp_pos_terminales`;
- auditoria de configuracion;
- no abre turno;
- no mueve caja.

### `/ventas/pos_configuracion_asignacion_guardar_erp`

Crea o edita asignacion usuario/caja/terminal.

Validaciones:

- usuario existente y activo;
- usuario con permiso/rol operativo para POS;
- almacen vendible y activo;
- caja activa del mismo almacen;
- terminal activa de la misma caja cuando aplique;
- bloquear duplicado activo para la misma combinacion usuario/almacen/caja/terminal;
- si un usuario tiene varias asignaciones activas, resolver por prioridad y avisar en UI.

Escrituras:

- alta o actualizacion en `erp_pos_usuarios_cajas`;
- auditoria de configuracion;
- no abre turno;
- no mueve caja.

### `/ventas/pos_configuracion_desactivar_erp`

Desactiva caja, terminal o asignacion.

Validaciones:

- motivo obligatorio;
- no desactivar caja con turno abierto;
- no desactivar terminal ligada a turno abierto;
- no desactivar la unica asignacion operativa de una caja abierta;
- baja logica, nunca borrado fisico.

Escrituras:

- cambiar estatus;
- registrar motivo;
- auditoria.

## Secuencia tecnica recomendada

1. Mantener `/ventas/pos_configuracion` como pantalla separada del mostrador. Hecho.
2. Reutilizar dry-runs actuales. Hecho:
   - `configuracionCajaDryRun`;
   - `configuracionTerminalDryRun`;
   - `configuracionAsignacionDryRun`.
3. Crear helpers privados en `VentasErp`. Hecho:
   - guardar caja;
   - guardar terminal;
   - guardar asignacion;
   - desactivar entidad;
   - validar dependencias abiertas;
   - auditar cambio.
4. Agregar endpoints reales en `Ventas.php` con permisos finos. Hecho; falta sembrar permisos en BD.
5. Crear scripts UAT apply-authorized. Hecho como script unico por accion:
   - `storage/uat/uat_ventas_pos_configuracion_apply_authorized.php`;
   - alta caja;
   - alta terminal;
   - asignacion usuario;
   - desactivacion segura.
6. Conectar la UI solo despues de tener permisos y auditoria.
7. Mantener botones visibles como `Validar` hasta que el flujo real este autorizado; despues separar `Validar` de `Guardar`.

## Implementacion preparada

Modelo:

- `VentasErp::configuracionCajaGuardarReal`
- `VentasErp::configuracionTerminalGuardarReal`
- `VentasErp::configuracionAsignacionGuardarReal`
- `VentasErp::configuracionPosDesactivarReal`

Controlador:

- `/ventas/pos_configuracion_caja_guardar_erp`
- `/ventas/pos_configuracion_terminal_guardar_erp`
- `/ventas/pos_configuracion_asignacion_guardar_erp`
- `/ventas/pos_configuracion_desactivar_erp`

Script UAT:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_configuracion_apply_authorized.php --autorizar=VENTAS_POS_CONFIG_CRUD --respaldo="UAT POS vigente" --id_usuario=1 --accion=caja
```

Acciones soportadas:

- `caja`
- `terminal`
- `asignacion`
- `desactivar`

El script queda bloqueado por defecto y no ejecuta escrituras sin token, respaldo, usuario y accion valida.

Permisos:

- `storage/uat/uat_ventas_pos_configuracion_permisos_readonly.php`
- `storage/uat/uat_ventas_pos_configuracion_permisos_apply_authorized.php`

Auditoria read-only actual:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_configuracion_permisos_readonly.php --compact=1 --id_usuario=1
```

Resultado 2026-07-04:

- `ventas.pos_config.ver`: faltante en BD;
- `ventas.pos_config.crear`: faltante en BD;
- `ventas.pos_config.editar`: faltante en BD;
- `ventas.pos_config.desactivar`: faltante en BD;
- `ventas.pos_config.asignar_usuario`: faltante en BD.

## UAT minima

### UAT-CONFIG-001 Caja nueva

Datos:

- almacen vendible;
- codigo nuevo;
- nombre claro;
- efectivo/tarjeta/transferencia segun operacion.

Esperado:

- caja creada `activa`;
- aparece en `/ventas/pos_configuracion`;
- no abre turno;
- no crea movimiento de caja.

### UAT-CONFIG-002 Terminal nueva

Esperado:

- terminal creada ligada a almacen/caja;
- identificador capturado o aviso productivo;
- aparece en configuracion;
- no afecta turnos.

### UAT-CONFIG-003 Asignacion usuario

Esperado:

- usuario queda ligado a almacen/caja/terminal;
- `/ventas/pos` abre con contexto oficial;
- no hay selector libre operativo;
- si no hay turno abierto, `Cobrar` sigue bloqueado.

### UAT-CONFIG-004 Desactivacion segura

Esperado:

- caja con turno abierto no se desactiva;
- terminal con turno abierto no se desactiva;
- asignacion activa usada por turno abierto no se desactiva;
- desactivacion valida queda como baja logica con motivo.

## Estado UAT actual

- Usuario `1` tiene `ventas.ver` y `ventas.operar`.
- Asignacion activa:
  - almacen `5`;
  - caja `2`;
  - terminal `2`.
- Configuracion base:
  - `2` cajas;
  - `2` terminales;
  - `2` asignaciones;
  - `1` turno abierto.
- Hallazgos read-only actuales: `[]`.

## Autorizacion requerida

```text
AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD para UAT POS
```

Siguiente autorizacion requerida antes de probar la pantalla protegida:

```text
AUTORIZO SEMBRAR PERMISOS CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_PERMISOS para UAT POS
```

## Limite de esta fase

Este bloque no debe resolver CRM, listas de precios, recompensas ni garantias. Solo garantiza que el POS abra amarrado a tienda/caja/terminal y que el cajero opere en el contexto correcto.
