# MySQL XAMPP - Runbook de recuperacion local

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-19  
Estado: runbook operativo local; no autoriza reparaciones por si mismo.

## Contexto

Durante el trabajo de ERP Comercial/Listas de precios, MySQL local dejo de responder y `mysql_error.log` mostro fallos de Aria:

- `Aria recovery failed`;
- `Plugin 'Aria' registration as a STORAGE ENGINE failed`;
- `Could not open mysql.plugin table`;
- `Failed to initialize plugins`;
- `Aborting`.

Despues de varios intentos, el log mostro `Server socket created` y MySQL volvio a responder con usuario local.

## Regla de seguridad

No ejecutar reparaciones de motor ni borrar archivos de `C:\xampp\mysql\data` sin autorizacion explicita del dueno.

Antes de cualquier reparacion destructiva o semidestructiva:

1. Confirmar que no hay procesos `mysqld.exe` activos.
2. Copiar externamente `C:\xampp\mysql\data` o tener respaldo externo vigente.
3. Documentar ruta del respaldo/copia fuera de `C:\xampp\htdocs\panel_de_control`.
4. Ejecutar solo el comando autorizado.
5. Validar que `artianilocal` responde y que las suites read-only del ERP pasan.

## Diagnostico read-only

Comandos seguros:

```powershell
Get-Process | Where-Object { $_.ProcessName -like '*mysql*' }
C:\xampp\mysql\bin\mysqladmin.exe --user=root ping
C:\xampp\mysql\bin\mysql.exe --user=root --database=artianilocal --execute="SELECT 1 AS ok"
C:\xampp\php\php.exe storage\uat\uat_mysql_xampp_health_readonly.php
```

Leer log:

```powershell
Get-Content -Path C:\xampp\mysql\data\mysql_error.log | Select-Object -Last 120
```

## Validaciones ERP despues de levantar MySQL

Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_filtros_margen_readonly.php 2
C:\xampp\php\php.exe storage\uat\uat_listas_precios_revision_activacion_readonly.php 2
C:\xampp\php\php.exe storage\uat\uat_listas_precios_lote_dryrun_readonly.php 2 1760 315
C:\xampp\php\php.exe storage\uat\uat_listas_precios_segmentos_post_apply_suite_readonly.php --id_lista_precio=2 --codigo_segmento=RECURRENTE --id_cliente_crm=2 --id_sku=1760 --id_almacen=5 --canal=pos --ventas_total=23 --ventas_max_id=26 --detalle_total=24 --detalle_max_id=27
```

Resultado esperado:

- `PASS_FILTROS_MARGEN`;
- `PASS_REVISION_ACTIVACION`;
- `PASS_LOTE_DRYRUN`;
- `PASS_SUITE_POST_APPLY`;
- baseline de ventas intacta.

## Reparacion Aria

Si vuelve a aparecer `Aria recovery failed`, no ejecutar automaticamente:

- `aria_chk --recover`;
- `aria_chk --safe-recover`;
- borrado de `aria_log.*`;
- reemplazo de tablas del schema `mysql`.

Esas acciones requieren autorizacion explicita y una copia externa previa del data dir o respaldo verificable.
