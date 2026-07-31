# ERP - Runbook de activacion Migraciones BD

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-31  
Estado: preparacion; no ejecutar sin respaldo externo y autorizacion explicita

## Objetivo

Activar el esquema tecnico `sys_migraciones_*` para que la consola `/migracionBd` pueda guardar politicas, paquetes dry-run, SQL generado y bitacoras de ejecucion futuras.

Esta activacion no migra catalogo, proveedores, compras, ventas, inventario, clientes ni productivo. Solo crea tablas tecnicas del modulo de migraciones.

## Preflight desde UI

Abrir:

```text
/migracionBd
```

Ir a la pestaña:

```text
Activacion
```

Validar:

- esquema tecnico pendiente/listo;
- ruta sugerida de respaldo;
- respaldo externo existente;
- texto de autorizacion.

## Respaldo requerido

Ruta estandar:

```text
C:\xampp\panel_db_backups
```

Nombre sugerido:

```text
artianilocal_panel_YYYYMMDD_HHmmss_antes_migracion_bd_schema.sql
```

Comando base:

```text
C:\xampp\mysql\bin\mysqldump.exe --host=localhost --user=root --result-file="C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_HHmmss_antes_migracion_bd_schema.sql" artianilocal
```

Validar:

- archivo existe;
- archivo legible;
- tamano mayor a `0`;
- archivo fuera de `C:\xampp\htdocs\panel_de_control`.

## Autorizacion requerida

Texto recomendado:

```text
AUTORIZO CREAR ESQUEMA TECNICO MIGRACIONES BD usando respaldo [RUTA_RESPALDO] con token MIGRACIONES_BD_SCHEMA. Entiendo que solo crea tablas tecnicas sys_migraciones_* para preparacion, politicas, paquetes y ejecuciones; no migra catalogo, proveedores, compras, ventas, inventario, clientes, usuarios operativos ni productivo.
```

## Aplicacion

Endpoint tecnico:

```text
POST /migracionBd/esquema_actualizar
```

Parametros:

```text
ejecutar=1
respaldo=RUTA_RESPALDO_VALIDO
autorizar=MIGRACIONES_BD_SCHEMA
confirmacion=AUTORIZO CREAR ESQUEMA TECNICO MIGRACIONES BD ...
```

Permiso:

```text
sistema.soporte
```

Nota: el endpoint bloquea `ejecutar=1` si falta token, respaldo valido o confirmacion literal. Antes de usarlo en una sesion de trabajo, confirmar respaldo externo y autorizacion literal del dueno.

## Post-aplicacion

1. Abrir `/migracionBd`.
2. Confirmar que el diagnostico muestra esquema tecnico listo.
3. Ejecutar `Politicas`.
4. Guardar politicas iniciales.
5. Configurar destino en `app/config/migraciones_ambientes.local.php`.
6. Comparar local vs destino.
7. Crear paquete dry-run.

## Reversa

La reversa preferida ante error serio es restaurar el respaldo externo completo en ambiente controlado.

No borrar tablas `sys_migraciones_*` manualmente si ya contienen paquetes, politicas o bitacoras reales sin autorizacion especifica.
