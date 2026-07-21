# ERP Ventas/POS - Runbook recuperacion MySQL UAT 2026-07-19

Documento vivo. Ultima actualizacion: 2026-07-19.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

## Estado

MariaDB/XAMPP no arranca al corte 2026-07-19.

El bloqueo afecta validaciones POS que consultan BD:

- preflight productivo;
- postcheck de piloto;
- reportes;
- inventario pendiente;
- multiusuario con permisos reales.

No afecta los cambios de archivos ya validados:

- impresion directa de ticket POS;
- modal de ticket con titulo dinamico;
- semaforos read-only que solo revisan archivos;
- documentacion de piloto.

## Evidencia read-only

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_mysql_health_readonly.php
```

Resultado vigente:

- `ok=false`.
- `Aria recovery failed`.
- `MariaDB solicita aria_chk -r`.
- `Indice corrupto en mysql.plugin`.
- `No se puede abrir mysql.plugin`.
- `Failed to initialize plugins`.
- `MariaDB aborta durante arranque`.

Archivos observados:

- `C:\xampp\mysql\data\mysql_error.log`.
- `C:\xampp\mysql\data\mysql\plugin.MAI`.
- `C:\xampp\mysql\data\mysql\plugin.MAD`.
- `C:\xampp\mysql\data\aria_log.00000001`.
- PID historicos en `C:\xampp\mysql\data`.

## Respaldo candidato

No restaurar automaticamente.

Respaldo mas reciente observado:

```text
C:\xampp\htdocs\panel\artianilocal_respaldo_20260715_0850.sql
```

Respaldos historicos tambien observados:

- `C:\xampp\htdocs\panel\artianilocal_respaldo_actual_20260706.sql`.
- `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.

Antes de restaurar se debe decidir cual respaldo representa mejor el estado UAT vigente.

## Regla de seguridad

No borrar `aria_log.*`, PID files, tablas internas ni carpeta `data` sin respaldo previo de `C:\xampp\mysql\data`.

No importar SQL si MariaDB puede recuperarse sin restaurar.

No continuar pruebas POS con escritura si MySQL no queda estable.

## Autorizacion requerida

```text
AUTORIZO RECUPERAR MYSQL UAT POS usando respaldo UAT POS vigente con token MYSQL_UAT_POS_RECOVERY permitiendo respaldo previo de C:\xampp\mysql\data, arranque controlado de MariaDB, diagnostico InnoDB, reparacion Aria con aria_chk y restauracion/importacion solo si es necesario para continuar UAT POS
```

## Secuencia propuesta despues de autorizacion

1. Respaldar carpeta `C:\xampp\mysql\data` a una carpeta con timestamp.
2. Confirmar que no haya proceso `mysqld.exe` vivo antes de reparar.
3. Ejecutar diagnostico con `aria_chk --check` sobre tablas Aria criticas.
4. Reparar Aria con `aria_chk --recover` solo si el diagnostico confirma corrupcion.
5. Intentar arranque normal de MariaDB.
6. Si arranca, correr `mysqladmin ping`.
7. Si responde, ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_mysql_health_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_preflight_compacto_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760
```

8. Si no arranca, decidir restauracion desde respaldo externo vigente.

## Scripts preparados

Preflight read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_mysql_pos_recovery_preflight_readonly.php --respaldo="C:\xampp\htdocs\panel\artianilocal_respaldo_20260715_0850.sql"
```

El preflight no escribe datos, no arranca MariaDB y no repara tablas. Debe mostrar:

- `aria_chk`.
- `mysql_plugin_mai`.
- `mysql_plugin_mad`.
- `aria_logs`.
- comandos propuestos no ejecutados para check/reparacion Aria, arranque e importacion.

Script autorizado, bloqueado por defecto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_mysql_pos_recovery_apply_authorized.php
```

Debe responder `modo=bloqueado` si no recibe token y respaldo.

Fases disponibles tras autorizacion:

- `diagnostico`: valida entradas y ping; no repara.
- `copia_data`: requiere `--permitir_copia_data=1`; copia `C:\xampp\mysql\data` antes de tocar Aria.
- `aria_check_mysql_plugin`: ejecuta `aria_chk --check` sobre `mysql\plugin.MAI`.
- `aria_check_mysql_system`: ejecuta `aria_chk --check` sobre `mysql\*.MAI`.
- `aria_repair_mysql_plugin`: requiere `--permitir_aria_repair=1`, `--backup_verificado=RUTA_BACKUP_DATA_EXISTENTE` y `--confirmar=REPARAR_ARIA`.
- `arranque_normal`: requiere `--permitir_arranque=1`.
- `arranque_recovery_1`: requiere `--permitir_recovery=1`.

La reparacion Aria esta intencionalmente separada de la copia de `data`. No se debe ejecutar `aria_repair_mysql_plugin` si antes no existe y se verifico un respaldo externo de `C:\xampp\mysql\data`.

## Criterio para reanudar POS

Reanudar UAT POS solo cuando:

- `mysqladmin ping` responda;
- `uat_ventas_pos_mysql_health_readonly.php` no tenga bloqueos activos;
- preflight POS vuelva a `ok=true`;
- postcheck POS pueda leer turnos/ventas/reportes.
