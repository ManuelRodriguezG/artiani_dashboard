# ERP Ventas/POS - Bloqueo MySQL UAT

Fecha: 2026-07-03

## Estado

Durante la preparacion del preflight consolidado del siguiente ciclo UAT POS, MySQL local no se mantuvo estable.

## Evidencia

- `mysqladmin ping` devolvio `Can't connect to MySQL server on '127.0.0.1' (10061)`.
- Se intento iniciar `mysqld` de XAMPP.
- `mysqladmin ping` respondio temporalmente `mysqld is alive`.
- Al ejecutar consultas read-only, MariaDB devolvio `MySQL server has gone away`.
- El proceso `mysqld` dejo de estar activo.
- `C:\xampp\mysql\data\mysql_error.log` contiene historial de errores InnoDB:
  - `InnoDB: corruption in the InnoDB tablespace`;
  - `InnoDB: Assertion failure`;
  - recomendacion de recovery modes.

## Respaldo disponible

- Ruta: `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`
- Tamano verificado: `27216189` bytes.
- Fecha archivo: `2026-06-25 22:20:08`.

## Preflight read-only de recuperacion

- Script: `storage/uat/uat_mysql_pos_recovery_preflight_readonly.php`
- Validacion:
  - respaldo existe: `true`;
  - respaldo bytes: `27216189`;
  - `C:\xampp\mysql\data` existe: `true`;
  - `C:\xampp\mysql\bin\mysqld.exe` existe: `true`;
  - `C:\xampp\mysql\bin\mysql.exe` existe: `true`;
  - `C:\xampp\mysql\bin\my.ini` existe: `true`;
  - bloqueos: `[]`.
- Contrato:
  - no escribe BD;
  - no detiene MySQL;
  - no modifica `my.ini`;
  - no mueve `data`;
  - no importa SQL.

## Revalidacion posterior

- Se preparo aplicador protegido:
  - `storage/uat/uat_mysql_pos_recovery_apply_authorized.php`.
- Validaciones:
  - sintaxis PHP: OK;
  - sin token: bloqueado correctamente;
  - fase `diagnostico` con token: no copia data, no arranca servicio, no importa SQL, solo valida entradas y ping.
- Resultado diagnostico:
  - `mysqladmin ping`: `mysqld is alive`.
- Readiness POS posterior:
  - `storage/uat/uat_ventas_pos_readiness_readonly.php --compact=1`: OK;
  - turno abierto `10`;
  - cierre diferencia `0`;
  - ticket `28` lineas;
  - reserva bloqueada por stock;
  - abono bloqueado por folio inexistente;
  - devoluciones fisicas pendientes `1`.

## Estado actualizado

MySQL respondio nuevamente a lectura read-only. Se conserva este documento como antecedente de inestabilidad; antes de operaciones reales conviene revalidar `mysqladmin ping` y readiness compacto.

## Impacto en POS

- No se puede ejecutar cierre real de turno POS mientras MySQL este inestable.
- No se puede validar post-cierre, stock, venta ni pedidos/apartados con seguridad.
- Los scripts POS preparados quedan listos, pero deben esperar recuperacion de MySQL.

## Contrato de seguridad

- No se modifico `my.ini`.
- No se ejecuto restauracion.
- No se ejecuto `innodb_force_recovery`.
- No se escribio BD desde POS en esta revision.
- No se cerro turno.
- No se cargo stock.

## Opciones recomendadas

1. Recuperacion controlada de MySQL UAT:
   - arrancar MariaDB en modo recuperacion si hace falta;
   - obtener dump de seguridad si el motor permite lectura;
   - validar tablas criticas;
   - decidir si continuar sobre la BD actual o restaurar desde respaldo.

2. Restauracion UAT desde respaldo externo:
   - detener MySQL;
   - respaldar carpeta `C:\xampp\mysql\data` como evidencia local antes de tocarla;
   - restaurar servicio limpio;
   - importar `artianilocal_respaldo_completo_20260625_post_repair.sql`;
   - ejecutar scripts de esquema/semillas POS que ya fueron autorizados anteriormente si el respaldo no contiene lo ultimo.

## Autorizacion robusta sugerida

```text
AUTORIZO RECUPERAR MYSQL UAT POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token MYSQL_UAT_POS_RECOVERY permitiendo respaldo previo de C:\xampp\mysql\data, arranque controlado de MariaDB, diagnostico InnoDB y restauracion/importacion solo si es necesario para continuar UAT POS
```

## Siguiente paso despues de recuperar MySQL

1. Ejecutar `storage/uat/uat_ventas_pos_ciclo_uat_preflight_readonly.php`.
2. Cerrar turno `TUR-20260630-002-002` si diferencia sigue `0`.
3. Verificar post-cierre.
4. Abrir turno siguiente.
5. Cargar stock UAT.
6. Probar venta y pedidos/apartados.
