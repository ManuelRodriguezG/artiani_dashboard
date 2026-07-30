# ERP Ecommerce publico - Revision de calidad expansion 2026-07-29

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-29  
Estado: revision read-only; no autoriza escrituras.

## Resultado

La base ecommerce actual sigue sana para frontend basico con 2 productos reales, pero la expansion a 6 productos no debe autorizarse todavia.

Senal actual:

```text
senal_actual=verde_datos_reales
senal_expansion=revisar_expansion
```

## Candidatos limpios

- SKU `415` / `SAL-50L`: lampara LED para acuario, `pez/habitat`, disponible.
- SKU `866` / `SCF-800`: filtro de canastilla, `pez/habitat`, pocas piezas.
- SKU `386` / `SHF-600`: filtro de cascada, `pez/habitat`, pocas piezas.

## Candidato con revision

- SKU `1138` / `SP-2823`: jaula para aves, `ave/habitat`, pocas piezas.
- Bloqueo: `validar_texto_publico`.
- Motivo: el titulo trae un caracter de reemplazo en la medida.
- Texto actual detectado: `Jaula para aves (maxi) tipo cilindro monte verde 33 ? 56 cm`.
- Texto sugerido para revision: `Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm`.

No corregir automaticamente sin confirmar la medida real del producto.

## Alternativa evaluada

SKU `173` / `ALI-GLOMDM` puede planearse como alimento vivo, pero no entra como reemplazo automatico porque:

- requiere decision de mascota: probablemente `reptil/alimento` o `ave/alimento`;
- no tiene marca en catalogo;
- su uso comercial puede requerir reglas especiales de disponibilidad/manejo.

## Impacto frontend

Frontend puede avanzar:

- catalogo real basico con 2 productos publicados;
- UI con preview de expansion para layout, marcando que SKU `1138` no es copy final;
- politicas;
- facturacion UI sin POST;
- navegacion mascota/necesidad;
- carrito local y WhatsApp con `cotizacion_dryrun` real solo para publicados.

Frontend no debe tratar como publicados:

- SKU `415`;
- SKU `866`;
- SKU `386`;
- SKU `1138`;
- SKU `173`.

## Comandos de verificacion

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_bundle_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138 --min_actual=2 --min_objetivo=6
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus=415,866,386,1138 --resumen=1
```

## Decision recomendada

Mantener la expansion en revision hasta corregir el texto publico del SKU `1138` en Catalogo ERP o elegir otro candidato con metadata completa.

No avanzar a produccion todavia.

## Ruta curada disponible

Se valido un titulo publico curado sin escribir BD:

```text
Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm
```

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicacion_texto_curado_readonly.php --id_sku=1138 --titulo="Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm" --mascota=ave --necesidades=habitat
```

Resultado:

```text
ok=true
bloqueos=[]
sha256_sql=3710aec7edd3caeb51ab0b3ca0c3af80e0184cb494bec741cac3b06d87b77c7e
```

Compuerta de expansion curada:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_curada_6_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql
```

Resultado:

```text
senal_expansion_curada=verde_expansion_curada_6_lista_para_revision
publicaciones_estimadas_post_expansion=6
```

Esta ruta no toca el nombre maestro del SKU. Solo propone `titulo_publico` para la publicacion ecommerce.
