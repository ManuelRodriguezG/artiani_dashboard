# ERP Ecommerce Videos - Solicitud de autorizacion DDL

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado: solicitud preparada; no autoriza ni ejecuta cambios por si sola.

## Proposito

Preparar la autorizacion formal para crear el esquema minimo del modulo de videos ecommerce. El modulo usara videos alojados en TikTok; el ERP solo guardara metadatos, miniatura propia, copy, busqueda y relaciones comerciales.

## Alcance solicitado

Crear, si no existen, las tablas:

- `erp_ecommerce_videos`
- `erp_ecommerce_video_producto`
- `erp_ecommerce_video_categoria`

Estas tablas permiten:

- registrar enlaces y embeds de TikTok;
- guardar titulo, slug, descripcion, copy, hashtags y texto de busqueda;
- guardar miniatura propia controlada por Artiani;
- relacionar videos con producto principal;
- relacionar videos con productos relacionados;
- relacionar videos con categorias publicas.

## Fuera de alcance

- Descargar videos desde TikTok.
- Alojar videos en el ERP.
- Publicar videos automaticamente.
- Crear ventas, pedidos, cotizaciones o leads.
- Reservar o descontar inventario.
- Modificar publicaciones ecommerce existentes.
- Activar personalizacion por usuario o mascotas registradas.

## Requisitos antes de autorizar

1. Respaldo externo existente en:

```text
C:\xampp\panel_db_backups
```

2. Postcheck read-only antes de aplicar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_schema_postcheck_readonly.php
```

3. Confirmacion textual del dueno del proyecto con el token:

```text
ECOMMERCE_VIDEOS_DDL
```

4. Aplicacion solo mediante script bloqueado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_schema_apply_authorized.php --autorizar=ECOMMERCE_VIDEOS_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

## Riesgo operativo

Riesgo bajo si se ejecuta despues de respaldo: son tablas nuevas y no alteran tablas existentes. Aun asi, se exige respaldo porque la base operativa es productiva y porque el esquema habilita persistencia nueva.

## Decision recomendada

Autorizar solo despues de confirmar que el primer flujo sera:

- TikTok como proveedor inicial;
- miniatura propia;
- carga diferida del iframe;
- busqueda por titulo, descripcion, copy, hashtags y texto interno;
- relaciones a productos/categorias sin tocar inventario ni ventas.
