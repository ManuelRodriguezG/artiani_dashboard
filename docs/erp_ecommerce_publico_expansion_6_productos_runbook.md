# ERP Ecommerce publico - Runbook expansion a 6 productos

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-29  
Estado: runbook previo a autorizacion; no autoriza escrituras por si solo.

## Objetivo

Ampliar el catalogo publico de 2 a 6 productos sin checkout, sin cobro online, sin descontar inventario y sin tocar legacy `ecom_*`.

## SKUs de expansion

```text
415  - SAL-50L  - Lampara 50 cm led con tapa - pez/habitat - disponible
866  - SCF-800  - Filtro de canastilla presurizado 960 l/hr - pez/habitat - pocas piezas
386  - SHF-600  - Filtro de cascada 650 l/hr - pez/habitat - pocas piezas
1138 - SP-2823  - Jaula para aves tipo cilindro - ave/habitat - pocas piezas - requiere corregir texto publico
```

## Checklist read-only

Antes de cualquier escritura:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_bundle_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138 --min_actual=2 --min_objetivo=6
```

Resultado esperado despues del candado de calidad de texto:

```text
ok=false
senal_actual=verde_datos_reales
senal_expansion=revisar_expansion
actual.publicadas=2
expansion.listos_para_borrador=3
expansion.publicaciones_estimadas_post_expansion=5
expansion.bloqueos=expansion_no_alcanza_minimo_objetivo_6, sku_1138_no_listo
```

Checklist detallado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138
```

Resultado esperado despues del candado de calidad de texto:

```text
ok=false
respaldo.ok=true
estado_actual.ready=true
estado_actual.ddl_pendiente=false
estado_actual.publicadas_actuales=2
expansion.total_skus=4
expansion.listos_para_borrador=3
expansion.publicaciones_esperadas_si_se_publican_todos=5
bloqueos=sku_1138_no_listo_para_borrador
```

Motivo:

- SKU `1138` trae un caracter de reemplazo en el titulo publico actual.
- Corregir antes de crear/publicar borrador. Titulo sugerido para revision de negocio: `Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm`.

## Preview para frontend antes de publicar

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus=415,866,386,1138 --resumen=1
```

Resultado esperado actual:

```text
ok=false
modo=preview_expansion_readonly
publicadas_actuales=2
preview_incluye_no_publicados=4
publicaciones_preview_total=6
cors_origin_permitido=true
whatsapp_configurado=true
bloqueos=sku_1138_bloqueos_validar_texto_publico
```

Este preview sirve para que el frontend avance con grid, filtros, carrito local y WhatsApp usando una muestra mas realista. No autoriza ni reemplaza la publicacion real.

Si el preview reporta `validar_texto_publico`, no usar ese item como referencia final de copy; usarlo solo para layout o corregir el texto primero.

## Orden seguro si el dueño autoriza

1. Corregir o confirmar texto publico del SKU `1138`.
2. Guardar los SKUs limpios como borrador con token `ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR`.
3. Revisar en consola interna `http://panel.com.local/ecommercePublico/publicaciones`.
4. Publicar cada borrador con token `ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR` y `--confirmar_revision=1`.
5. Validar `post_apply`, `green_gate` y snapshot frontend.

## Ruta curada a 6 productos

Antes de pedir autorizacion de escritura, validar el texto publico curado del SKU `1138`:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicacion_texto_curado_readonly.php --id_sku=1138 --titulo="Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm" --mascota=ave --necesidades=habitat
```

Resultado esperado:

```text
ok=true
bloqueos=[]
```

Compuerta curada:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_curada_6_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql
```

Resultado esperado:

```text
senal_expansion_curada=verde_expansion_curada_6_lista_para_revision
publicaciones_estimadas_post_expansion=6
```

Esta ruta solo propone `titulo_publico` ecommerce para la publicacion. No cambia el nombre maestro del SKU y no escribe BD.

## Texto de autorizacion para borradores

```text
Autorizo crear borradores ecommerce publico Fase 1 para expansion controlada con token ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql para SKUs revisados. Confirmo que no se publican automaticamente ni se afecta inventario, y que los textos publicos fueron revisados.
```

## Texto de autorizacion para publicacion

```text
Autorizo publicar los borradores ecommerce publico Fase 1 para expansion controlada con token ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql para SKUs revisados. Confirmo que revise slug, titulo, mascota, necesidad, precio y disponibilidad publica.
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
