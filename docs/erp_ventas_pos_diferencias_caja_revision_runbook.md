# ERP Ventas POS - Revision formal de diferencias de caja

## Objetivo

Convertir faltantes/sobrantes de cierre en un flujo administrativo trazable:

- detectar diferencia al cerrar turno;
- mantener intacto `erp_pos_turnos`;
- crear expediente de revision;
- registrar motivo, responsable, evidencia y decision;
- cerrar la diferencia como explicada, aceptada, ajustada o escalada.

## Estado actual

- La caja puede cerrar con diferencia distinta de cero.
- La diferencia queda en `erp_pos_turnos.diferencia`.
- `/ventas/reportes` muestra:
  - KPIs;
  - turnos con diferencia;
  - empleado;
  - sucursal/caja;
  - seguimiento read-only.
- UAT vigente:
  - turno `TUR-20260703-002-002`;
  - esperado `795`;
  - contado `785`;
  - diferencia `-10`;
  - estado sintetico `pendiente_revision`.

## Tabla propuesta

`erp_pos_turnos_diferencias_revision`

Campos recomendados:

- `id_diferencia_revision` PK.
- `folio` unico.
- `id_turno_caja` unico por revision activa.
- `id_almacen`.
- `id_caja`.
- `tipo_diferencia`: `faltante`, `sobrante`.
- `monto_diferencia`.
- `estatus`: `pendiente_revision`, `en_revision`, `explicada`, `aceptada`, `ajustada`, `escalada`, `cancelada`.
- `motivo`.
- `diagnostico`.
- `decision`.
- `evidencia_referencia`.
- `responsable_revision`.
- `solicitado_por`.
- `resuelto_por`.
- `fecha_revision`.
- `fecha_resolucion`.
- `datos_snapshot` JSON/TEXT.
- `fecha_registro`.
- `fecha_actualizacion`.

## Reglas

- No modificar montos historicos del turno.
- No borrar diferencias.
- Una diferencia puede iniciar en `pendiente_revision`.
- Pasar a `en_revision` cuando un supervisor toma el caso.
- Cerrar como:
  - `explicada`: hay razon documentada, sin ajuste monetario.
  - `aceptada`: se reconoce el faltante/sobrante sin accion adicional.
  - `ajustada`: se generara ajuste autorizado en caja/finanzas mediante otro flujo.
  - `escalada`: requiere investigacion mayor.
- Evidencia puede ser referencia externa, texto o adjunto futuro.
- Ajustes de dinero no deben resolverse desde esta tabla; deben ir por movimiento autorizado de caja/finanzas.

## Permisos recomendados

- `ventas.caja_diferencias.ver`
- `ventas.caja_diferencias.revisar`
- `ventas.caja_diferencias.resolver`

Compatibilidad temporal:

- `ventas.ver` permite consulta.
- `ventas.operar` no debe resolver diferencias en productivo sin permiso fino.

## UAT propuesta

1. Auditar esquema.
2. Aplicar DDL autorizado.
3. Consultar `/ventas/reportes` y confirmar que el faltante `-10` queda pendiente.
4. Registrar expediente de revision para turno `12`.
5. Marcar en revision.
6. Resolver como `explicada` con motivo UAT.
7. Confirmar que:
   - `erp_pos_turnos.diferencia` sigue en `-10`;
   - la bandeja ya muestra estado formal;
   - no hay movimientos de caja nuevos;
   - no hay inventario afectado.

## Autorizacion requerida

```text
AUTORIZO PREPARAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

## Estado tecnico 2026-07-03

Preparado:

- `VentasErpEsquema::planActualizarRevisionDiferenciasCajaPos`.
- `VentasErpEsquema::auditarRevisionDiferenciasCajaPos`.
- endpoint `/ventas/esquema_auditar_revision_diferencias_caja_pos`.
- endpoint `/ventas/esquema_actualizar_revision_diferencias_caja_pos`.
- script read-only `storage/uat/uat_ventas_pos_diferencias_revision_schema_readonly.php`.
- script protegido `storage/uat/uat_ventas_pos_diferencias_revision_schema_apply_authorized.php`.

Auditoria read-only:

- tabla `erp_pos_turnos_diferencias_revision`: no existe.
- columnas esperadas: no existen.
- indices esperados: no existen.
- SQL generado sin ejecutar.

Prueba negativa:

- aplicador sin parametros queda bloqueado;
- no crea tabla;
- no modifica turnos;
- no mueve caja.

Siguiente autorizacion para aplicar:

```text
AUTORIZO APLICAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

## DDL aplicado 2026-07-03

Autorizacion recibida:

```text
AUTORIZO APLICAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS
```

Resultado:

- tabla `erp_pos_turnos_diferencias_revision` creada;
- columnas esperadas existen;
- indices esperados existen;
- no se modifico `erp_pos_turnos`;
- no se movio caja;
- no se resolvieron diferencias.

Verificacion posterior:

- bandeja de diferencias:
  - `schema_revision_pendiente=false`;
  - turno `TUR-20260703-002-002`;
  - faltante `-10`;
  - estado `pendiente_revision`;
  - sin expediente formal aun.
- reporte caja:
  - faltantes total `10`;
  - diferencia neta `-10`;
  - turno clasificado como `faltante`.

## Expediente registrado 2026-07-03

Autorizacion recibida:

```text
AUTORIZO REGISTRAR REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_REVISION_REAL id_usuario=1 id_turno_caja=12 motivo="UAT faltante de caja controlado" responsable="Supervisor UAT" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

Resultado:

- expediente `id_diferencia_revision=1`;
- folio `DIF-CAJ-20260703-000001`;
- turno `TUR-20260703-002-002`;
- tipo `faltante`;
- monto `-10`;
- estatus `pendiente_revision`;
- responsable `Supervisor UAT`;
- evidencia referencia `FALTANTE-UAT-001`.

Validacion:

- bandeja de diferencias muestra `id_diferencia_revision=1`;
- estado `pendiente_revision`;
- reporte caja conserva faltante total `10`;
- `erp_pos_turnos.diferencia` sigue en `-10`;
- no se movio caja;
- no se movio inventario.

Siguiente etapa:

- preparar resolucion formal del expediente sin modificar el turno;
- resolver como `explicada`, `aceptada`, `ajustada` o `escalada` segun diagnostico.

## Resolucion preparada 2026-07-03

Preparado sin ejecucion real:

- `VentasErp::resolverRevisionDiferenciaCajaPosReal`;
- script protegido `storage/uat/uat_ventas_pos_diferencia_revision_resolver_apply_authorized.php`.

Reglas:

- requiere `id_usuario`;
- requiere `folio` o `id_diferencia_revision`;
- decisiones permitidas: `explicada`, `aceptada`, `ajustada`, `escalada`, `cancelada`;
- exige motivo;
- solo resuelve expedientes en `pendiente_revision` o `en_revision`;
- no modifica `erp_pos_turnos`;
- no mueve caja;
- no mueve inventario.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
- `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_diferencia_revision_resolver_apply_authorized.php`: sin errores;
- script sin parametros: bloqueado, no escribio BD;
- bandeja posterior conserva expediente `DIF-CAJ-20260703-000001` en `pendiente_revision`.

Siguiente autorizacion para ejecutar:

```text
AUTORIZO RESOLVER REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL id_usuario=1 folio=DIF-CAJ-20260703-000001 decision=explicada motivo="UAT faltante explicado sin ajuste de caja" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

## Resolucion ejecutada 2026-07-03

Autorizacion recibida:

```text
AUTORIZO RESOLVER REVISION DIFERENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL id_usuario=1 folio=DIF-CAJ-20260703-000001 decision=explicada motivo="UAT faltante explicado sin ajuste de caja" evidencia_referencia=FALTANTE-UAT-001 para UAT POS
```

Resultado:

- expediente `DIF-CAJ-20260703-000001`;
- `id_diferencia_revision=1`;
- decision `explicada`;
- estatus `explicada`;
- motivo de resolucion `UAT faltante explicado sin ajuste de caja`;
- turno `TUR-20260703-002-002`;
- diferencia historica del turno `-10`;
- no modifico turno;
- no movio caja;
- no movio inventario.

Verificacion:

- bandeja `estado_revision=todos` muestra `1` registro en estado `explicada`;
- bandeja `estado_revision=pendiente_revision` muestra `0` registros;
- reporte caja con `solo_diferencias=1` conserva `1` turno con faltante;
- faltantes total `10`;
- diferencia neta `-10`.

## UI de resolucion preparada 2026-07-03

Cambios:

- endpoint POST `/ventas/reportes_diferencia_caja_resolver_erp`;
- auditoria explicita `pos_diferencia_caja_resolver`;
- `/ventas/reportes` agrega filtro de estado de revision;
- tabla de seguimiento muestra pendientes, explicadas, aceptadas, ajustadas, escaladas y canceladas;
- accion `Resolver` visible solo para expedientes `pendiente_revision` o `en_revision`;
- modal captura decision, motivo y referencia de evidencia.

Contrato UI:

- requiere sesion, CSRF y permiso `ventas.caja_diferencias.resolver`;
- no modifica turno;
- no mueve caja;
- no mueve inventario;
- el faltante/sobrante historico sigue visible en reportes aunque el expediente se resuelva.

Validaciones:

- `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores;
- `node --check public\assets\js\custom\apps\erp\ventas\reportes.js`: sin errores;
- lectura read-only confirma `folio_revision=DIF-CAJ-20260703-000001` y estado `explicada`.

## UAT visual reportes 2026-07-03

Script:

- `storage/uat/uat_ventas_pos_reportes_playwright_uat.js`.

Resultado:

- login local correcto;
- URL final `/ventas/reportes`;
- filtro de estado existe;
- estado seleccionado `todos`;
- muestra `DIF-CAJ-20260703-000001` / `TUR-20260703-002-002` como `explicada`;
- filas de seguimiento `1`;
- botones `Resolver` visibles `0`, correcto porque el expediente ya esta cerrado;
- no ejecuta resolucion;
- no mueve caja;
- no mueve inventario.

Evidencia:

- `public/storage/uat/pos_reportes_diferencias_uat.png`.

Observacion:

- consola muestra recursos externos bloqueados por red (`ERR_NETWORK_ACCESS_DENIED`), sin afectar el flujo validado.

## Permisos finos preparados 2026-07-03

Permisos definidos:

- `ventas.caja_diferencias.ver`;
- `ventas.caja_diferencias.revisar`;
- `ventas.caja_diferencias.resolver`.

Cambios preparados:

- `SeguridadEsquema::permisosBaseERP` incluye los tres permisos;
- roles base propuestos:
  - `direccion`: ver, revisar, resolver;
  - `administrador_erp`: ver, revisar, resolver;
  - `finanzas_contabilidad`: ver, revisar, resolver;
  - `auditor`: ver;
  - `ventas`: ver;
- `Ventas::reportes_diferencia_caja_resolver_erp` requiere `ventas.caja_diferencias.resolver`;
- `VentasErp::resolverRevisionDiferenciaCajaPosReal` valida `ventas.caja_diferencias.resolver`;
- script read-only `storage/uat/uat_ventas_pos_diferencias_permisos_readonly.php`;
- script protegido `storage/uat/uat_ventas_pos_diferencias_permisos_apply_authorized.php`.

Auditoria actual:

- `sys_permisos`, `sys_roles`, `sys_roles_permisos` existen;
- los tres permisos de diferencias no existen todavia en BD;
- relaciones por rol `0`;
- aplicador sin token queda bloqueado.

Validaciones:

- `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
- `C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php`: sin errores;
- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
- `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_diferencias_permisos_readonly.php`: sin errores;
- `C:\xampp\php\php.exe -l storage\uat\uat_ventas_pos_diferencias_permisos_apply_authorized.php`: sin errores.

Siguiente autorizacion:

```text
AUTORIZO SEMBRAR PERMISOS DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS para UAT POS
```

## Permisos finos sembrados 2026-07-03

Autorizacion recibida:

```text
AUTORIZO SEMBRAR PERMISOS DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS para UAT POS
```

Resultado:

- permisos sembrados `3`;
- relaciones intentadas `11`;
- roles detectados: `administrador_erp`, `auditor`, `direccion`, `finanzas_contabilidad`, `ventas`.

Auditoria posterior:

- `ventas.caja_diferencias.ver`: existe;
- `ventas.caja_diferencias.revisar`: existe;
- `ventas.caja_diferencias.resolver`: existe;
- faltantes `[]`;
- `administrador_erp`, `direccion` y `finanzas_contabilidad`: ver/revisar/resolver;
- `auditor`: ver;
- `ventas`: ver.

Contrato cumplido:

- no asigna usuarios directos;
- no toca turnos;
- no mueve caja;
- no mueve inventario.

Nota operativa:

- cerrar sesion o refrescar permisos para que la sesion actual vea los permisos nuevos.

## UAT visual post-permisos 2026-07-03

Resultado:

- login local correcto;
- URL final `/ventas/reportes`;
- filtro estado revision existe;
- estado seleccionado `todos`;
- diferencia explicada visible;
- filas seguimiento `1`;
- botones resolver visibles `0`, correcto porque el expediente esta cerrado;
- auditoria read-only confirma permisos existentes y faltantes `[]`;
- no ejecuta resolucion;
- no mueve caja;
- no mueve inventario.

Evidencia:

- `public/storage/uat/pos_reportes_diferencias_uat.png`.

## Cierre permisos resolver 2026-07-03

Cambio:

- se retiro compatibilidad temporal `ventas.operar` para resolver diferencias de caja;
- endpoint `/ventas/reportes_diferencia_caja_resolver_erp` requiere `ventas.caja_diferencias.resolver`;
- modelo `VentasErp::resolverRevisionDiferenciaCajaPosReal` requiere `ventas.caja_diferencias.resolver`.

Validacion:

- usuario UAT `id_usuario=1` tiene:
  - `ventas.caja_diferencias.ver=true`;
  - `ventas.caja_diferencias.revisar=true`;
  - `ventas.caja_diferencias.resolver=true`;
  - `ventas.operar=true`.
- `C:\xampp\php\php.exe -l app\controladores\Ventas.php`: sin errores;
- `C:\xampp\php\php.exe -l app\modelos\VentasErp.php`: sin errores;
- UAT visual `/ventas/reportes`: `ok=true`;
- no ejecuta resolucion;
- no mueve caja;
- no mueve inventario.
