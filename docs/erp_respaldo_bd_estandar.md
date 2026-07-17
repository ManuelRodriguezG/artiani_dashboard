# ERP - Estandar de respaldos de base de datos

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-17  
Estado: criterio operativo transversal para cambios con escritura o DDL.

## Ruta estandar local

Los respaldos externos al proyecto deben guardarse fuera de:

```text
C:\xampp\htdocs\panel_de_control
```

Ruta estandar local:

```text
C:\xampp\panel_db_backups
```

Esta ruta ya existe en el entorno XAMPP local y contiene respaldos historicos del ERP.

## Convencion de nombres

Formato recomendado:

```text
{base}_{proyecto}_{yyyymmdd_HHmmss}_antes_{modulo}_{accion}.sql
```

Ejemplo para ecommerce publico Fase 1:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_antes_ecommerce_publico_fase1.sql
```

Si se requiere mas precision:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_153000_antes_ecommerce_publico_fase1.sql
```

## Reglas

- No guardar respaldos dentro del repo.
- No guardar respaldos en `storage/`, `public/` ni `docs/`.
- No ejecutar DDL ni scripts `apply_authorized` sin respaldo externo.
- Para cambios por modulo, reutilizar siempre `C:\xampp\panel_db_backups`.
- En documentacion y comandos usar la ruta completa del respaldo o una referencia externa verificable.
- No exponer credenciales de `app/config/mysql.php` en documentos ni respuestas.

## Validacion minima

Antes de aplicar cambios con escritura:

```bash
C:\xampp\mysql\bin\mysqldump.exe --host=localhost --user=root --result-file=C:\xampp\panel_db_backups\NOMBRE_RESPALDO.sql artianilocal
```

Despues de generar el respaldo:

- confirmar que el archivo existe;
- confirmar que el tamano es mayor a `0`;
- usar esa ruta completa en los scripts `apply_authorized`.

## Uso en ecommerce publico

Para ERP Ecommerce publico Fase 1, la referencia de respaldo debe pasarse como:

```text
--respaldo=C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_HHmmss_antes_ecommerce_publico_fase1.sql
```

La activacion autorizada sigue documentada en:

```text
docs/erp_ecommerce_publico_orden_activacion_autorizada.md
```

## Respaldos generados

Ecommerce publico Fase 1:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=28561352
```

Listas de precios por segmentos CRM:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=28578042
```
