# ERP Ecommerce publico - Runbook expansion a 6 productos

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-18  
Estado: runbook previo a autorizacion; no autoriza escrituras por si solo.

## Objetivo

Ampliar el catalogo publico de 2 a 6 productos sin checkout, sin cobro online, sin descontar inventario y sin tocar legacy `ecom_*`.

## SKUs de expansion

```text
415  - SAL-50L  - Lampara 50 cm led con tapa - pez/habitat - disponible
866  - SCF-800  - Filtro de canastilla presurizado 960 l/hr - pez/habitat - pocas piezas
386  - SHF-600  - Filtro de cascada 650 l/hr - pez/habitat - pocas piezas
1138 - SP-2823  - Jaula para aves tipo cilindro - ave/habitat - pocas piezas
```

## Checklist read-only

Antes de cualquier escritura:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_bundle_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138 --min_actual=2 --min_objetivo=6
```

Resultado esperado:

```text
ok=true
senal_actual=verde_datos_reales
senal_expansion=lista_para_autorizacion
actual.publicadas=2
expansion.listos_para_borrador=4
expansion.publicaciones_estimadas_post_expansion=6
expansion.bloqueos=[]
```

Checklist detallado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138
```

Resultado esperado:

```text
ok=true
respaldo.ok=true
estado_actual.ready=true
estado_actual.ddl_pendiente=false
estado_actual.publicadas_actuales=2
expansion.total_skus=4
expansion.listos_para_borrador=4
expansion.publicaciones_esperadas_si_se_publican_todos=6
bloqueos=[]
```

## Preview para frontend antes de publicar

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus=415,866,386,1138 --resumen=1
```

Resultado esperado:

```text
ok=true
modo=preview_expansion_readonly
publicadas_actuales=2
preview_incluye_no_publicados=4
publicaciones_preview_total=6
cors_origin_permitido=true
whatsapp_configurado=true
bloqueos=[]
```

Este preview sirve para que el frontend avance con grid, filtros, carrito local y WhatsApp usando una muestra mas realista. No autoriza ni reemplaza la publicacion real.

## Orden seguro si el dueño autoriza

1. Guardar los 4 como borrador con token `ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR`.
2. Revisar en consola interna `http://panel.com.local/ecommercePublico/publicaciones`.
3. Publicar cada borrador con token `ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR` y `--confirmar_revision=1`.
4. Validar `post_apply`, `green_gate` y snapshot frontend.

## Texto de autorizacion para borradores

```text
Autorizo crear borradores ecommerce publico Fase 1 para expansion a 6 productos con token ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql para SKUs 415, 866, 386 y 1138. Confirmo que no se publican automaticamente ni se afecta inventario.
```

## Texto de autorizacion para publicacion

```text
Autorizo publicar los borradores ecommerce publico Fase 1 para expansion a 6 productos con token ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql para SKUs 415, 866, 386 y 1138. Confirmo que revise slug, titulo, mascota, necesidad, precio y disponibilidad publica.
```

## Verificaciones posteriores

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --min_publicaciones=6
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=6 --min_publicaciones=6
```

## Guardrails

- No ejecutar este runbook sin autorizacion explicita.
- No publicar agotados como disponibles.
- No mostrar stock exacto.
- No descontar inventario.
- No registrar cotizaciones reales en Fase 1.
- `cotizacion_registrar` debe seguir bloqueado.
