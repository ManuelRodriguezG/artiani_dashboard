# ERP Ecommerce Videos - Runbook de aplicacion DDL

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado: guia operativa; no ejecutar sin autorizacion explicita.

## Objetivo

Aplicar el esquema minimo del modulo Videos para que `CMS > Videos` pueda guardar videos reales enlazados desde TikTok.

## Preflight read-only

Ejecutar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_schema_postcheck_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_readonly.php
```

Validar que:

- `modo=read-only`;
- `no_ejecuta_ddl=true`;
- `no_escribe_bd=true`;
- el contrato frontend siga en verde;
- si aun no se aplica el esquema, `senal_schema_postcheck=esquema_videos_pendiente`.

## Respaldo

Antes de aplicar, generar o confirmar respaldo externo en:

```text
C:\xampp\panel_db_backups
```

El archivo de respaldo debe existir fisicamente porque el script `apply_authorized` lo valida antes de ejecutar DDL.

## Aplicacion autorizada

Ejecutar solo con autorizacion textual del dueno:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_schema_apply_authorized.php --autorizar=ECOMMERCE_VIDEOS_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

El script debe devolver:

- `modo=apply_authorized`;
- `ejecutado=true`;
- `ok=true`;
- tablas ejecutadas:
  - `erp_ecommerce_videos`;
  - `erp_ecommerce_video_producto`;
  - `erp_ecommerce_video_categoria`.

## Verificacion posterior

Ejecutar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_schema_postcheck_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_readonly.php
```

Resultado esperado:

- `senal_schema_postcheck=esquema_videos_completo`;
- `tablas_faltantes=0`;
- `columnas_faltantes_total=0`;
- `indices_faltantes_total=0`;
- contrato frontend sigue verde.

Tambien validar en ERP:

- abrir `/cms/videos`;
- revisar estado del modulo;
- intentar listar videos;
- confirmar que guardar un borrador ya no responde `requiere_ddl=true`.

## No hacer durante esta aplicacion

- No cargar videos masivos.
- No publicar videos sin miniatura propia.
- No usar enlaces que no sean TikTok en la fase inicial.
- No tocar inventario, ventas, POS, CRM ni publicaciones ecommerce existentes.
- No activar personalizacion por usuario o mascotas.

## Siguiente paso despues de aplicar

Crear el primer video real desde `CMS > Videos`, vincularlo a un producto publicado y validar:

- `GET /ecommercePublico/videos`;
- `GET /ecommercePublico/videos/{slug}`;
- `GET /ecommercePublico/producto/{slug}/videos`.
