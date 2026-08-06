# ERP Ecommerce publico - Estado actual Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: verde con datos reales Fase 1; catalogo publico activado sin checkout.

Actualizacion 2026-07-30:

- La consola interna `http://panel.com.local/ecommercePublico/publicaciones` ya permite preparar curaduria, guardar/actualizar borrador y publicar productos ecommerce desde el panel.
- La consola tambien permite buscar por SKU/nombre/marca/categoria y filtrar por estado: sin publicacion, borrador, publicado o pausado.
- Si una publicacion ya esta publicada, el boton `Guardar cambios` actualiza curaduria sin regresarla a borrador.
- La consola permite seleccion multiple para `Guardar borradores` y `Publicar borradores`.
- Los POST internos quedan protegidos por sesion ERP, permiso `catalogo.editar`, CSRF, token interno de accion y auditoria explicita.
- Se agrego el endpoint interno `POST /ecommercePublico/publicaciones_publicar_borrador_erp`.
- Se agrego el endpoint interno `POST /ecommercePublico/publicaciones_guardar_curaduria_erp`.
- Se agregaron endpoints internos `POST /ecommercePublico/publicaciones_lote_borrador_erp` y `POST /ecommercePublico/publicaciones_lote_publicar_erp`.
- El flujo no toca inventario, no modifica precios/imagenes del Catalogo ERP y no usa legacy `ecom_*`.
- Respaldo externo usado antes de publicar lote: `C:\xampp\panel_db_backups\artianilocal_panel_20260729_225744_antes_ecommerce_publicaciones_panel.sql`.
- Catalogo publico real despues de la activacion: `6` productos publicados en `GET /ecommercePublico/catalogo`.
- SKUs publicados en esta activacion: `415`, `866`, `386`, `1138`.
- UAT panel: `C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_panel_publicaciones_readonly.php`; valida publicados, busqueda y bloqueo de acciones sin token.

Actualizacion 2026-07-16:

- Entorno local `http://panel.com.local` responde JSON para `/ecommercePublico/estado`.
- MySQL acepta conexion TCP en `127.0.0.1:3306`.
- Contratos API Fase 1 pasan validacion de shape.
- Preflight read-only detecta SKUs publicables Fase 1. El conteo es vivo y puede cambiar si Catalogo/Inventario cambian; consultar `uat_ecommerce_publico_autorizacion_paquete_readonly.php`.
- Lote solo disponibles detecta `2` SKUs disponibles; ambos de peces/alimento.
- Lote amplio puede incluir perro/gato, pero hoy arrastra muchos SKUs agotados y requiere decision de politica.
- Las 5 tablas `erp_ecommerce_*` ya fueron creadas.
- Casos negativos pasan: `cotizacion_registrar` sigue bloqueado y `cotizacion_dryrun` no escribe BD.
- CORS permite `http://artiani.com.local` y permanece cerrado para origenes no configurados.
- El log historico de MySQL conserva advertencia sobre `mysql.plugin`; revisar si reaparece, pero no bloquea contratos mientras API y MySQL respondan.
- WhatsApp recibido: `3322068429`; configuracion sugerida para `wa.me`: `523322068429`.
- Origen frontend local definido: `http://artiani.com.local`.
- URL frontend de pruebas definida: `http://artiani.com.local`.
- URL frontend futura de produccion: `https://artiani.com.mx`.
- Ruta estandar de respaldos externos: `C:\xampp\panel_db_backups` segun `docs/erp_respaldo_bd_estandar.md`.
- DDL ecommerce publico aplicado: 5 tablas `erp_ecommerce_*`.
- Configuracion publica aplicada: WhatsApp/CORS/URL local.
- Publicaciones activas: `2`.
- Green gate: `ok=true`, `senal_frontend=verde_datos_reales`.
- CORS preflight: `cors_abierto_para_origin=true`, `cors_sin_wildcard=true` para `http://artiani.com.local`.
- Productos publicados:
  - `1759` - Alimento churro blanco para peces 100 gr - `disponible` - precio `85`.
  - `1757` - Alimento churro blanco para peces 25 gr - `disponible` - precio `25`.
- Expansion auditada read-only despues de excluir publicados:
  - candidatos evaluados sin publicacion: `1490`;
  - disponibles: `1`;
  - pocas piezas: `3`;
  - agotados: `1486`.
  - paquete de publicacion read-only: 4 SKUs listos sin revision (`415`, `866`, `386`, `1138`).
  - expansion bundle read-only: `ok=true`, `senal_expansion=lista_para_autorizacion`.
  - checklist apply read-only: `ok=true`, publicaciones esperadas si se publican todos: `6`.
  - Ver `docs/erp_ecommerce_publico_expansion_catalogo_plan.md`.
  - Ver `docs/erp_ecommerce_publico_expansion_6_productos_runbook.md`.

Actualizacion 2026-07-25:

- Se decide evolucionar la API de ecommerce publico a modelo multi-canal sin romper el frontend propio Artiani.
- Canal principal: `frontend_propio` / `artiani_web`.
- Canal futuro: `partner_mayoreo` para aliados autorizados que consuman catalogo y generen oportunidades.
- No se debe entregar `api_secret` para pegarlo en JavaScript publico; las acciones sensibles requieren backend/proxy y firma HMAC.
- Se documenta la arquitectura en `docs/erp_ecommerce_publico_api_canales_partners.md`.
- Se agrega plan DDL read-only para:
  - `erp_ecommerce_canales_api`;
  - `erp_ecommerce_api_credenciales`;
  - `erp_ecommerce_canal_publicaciones`;
  - `erp_ecommerce_api_nonces`;
  - `erp_ecommerce_api_logs`.
- Estado del plan partner: `frontend_artiani_verde_partner_en_diseno`.
- Bloqueo para partner productivo: `ddl_canales_api_pendiente`.
- Comando read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_partner_api_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --partner_origin=https://partner.example.com
```

- Apply DDL canales API preparado y bloqueado por token/respaldo:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_api_apply_guard_readonly.php
```

- Plan de semillas de canales preparado read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_seed_plan_readonly.php --base=http://panel.com.local --artiani_origin=http://artiani.com.local --artiani_prod=https://artiani.com.mx --partner_codigo=partner_mayoreo_001 --partner_origin=https://partner.example.com
```

- Plan y apply bloqueado de allowlist por canal preparados:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canal_allowlist_plan_readonly.php --canal=partner_mayoreo_001 --publicaciones=1,2 --modo_precio=publico
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canal_allowlist_apply_guard_readonly.php
```

Estado esperado antes de DDL canales:

```text
allowlist_plan.ok=false
bloqueos=tabla_pendiente_erp_ecommerce_canales_api, tabla_pendiente_erp_ecommerce_canal_publicaciones
allowlist_apply_guard.ok=true
```

- Plan y apply bloqueado de credenciales preparados:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_credencial_plan_readonly.php --canal=partner_mayoreo_001 --modo=hmac
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_credencial_emitir_apply_guard_readonly.php
```

Estado esperado antes de DDL/canal/llave:

```text
credencial_plan.ok=false
bloqueos=tabla_pendiente_erp_ecommerce_canales_api, tabla_pendiente_erp_ecommerce_api_credenciales
advertencias=hmac_requiere_ECOMMERCE_API_SECRET_ENCRYPTION_KEY_para_apply_real
credencial_emitir_apply_guard.ok=true
```

- Modo observacion de autenticacion preparado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_auth_observacion_readonly.php --method=GET --path=/ecommercePublico/catalogo --query=limite=2 --origin=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_auth_observacion_readonly.php --method=GET --path=/ecommercePublico/catalogo --query=limite=2 --origin=https://partner.example.com --api_key=ak_demo_partner_mayoreo_001 --timestamp=2026-07-26T12:00:00Z --nonce=demo --signature=demo
```

Estado esperado actual:

```text
auth_obligatoria_actual=false
decision_observada=permitir_por_fase_actual
no_bloquea_frontend_artiani=true
bloqueos_para_auth_productiva=tablas_canales_api_pendientes
```

Actualizacion 2026-07-26:

- Se incorpora alcance de experiencia cliente ecommerce:
  - politicas publicas;
  - facturacion por folio;
  - historial de busqueda;
  - historial de navegacion;
  - panel analitico ERP futuro;
  - taxonomia/navegacion por mascota y necesidad;
  - registro futuro de clientes/mascotas contemplado, no activo.
- Documento vivo: `docs/erp_ecommerce_publico_experiencia_cliente_politicas_facturacion_analytics.md`.
- Plan DDL read-only preparado para:
  - `erp_ecommerce_politicas`;
  - `erp_ecommerce_facturacion_solicitudes`;
  - `erp_ecommerce_eventos_navegacion`;
  - `erp_ecommerce_busquedas`;
  - `erp_ecommerce_taxonomia_mascotas`.
- Frontend puede avanzar ahora con:
  - paginas de politicas;
  - pantalla `/facturacion` sin POST real;
  - navegacion por mascota/necesidad;
  - tracking local/mock;
  - panel visual/mock de analytics.
- Frontend no debe conectar todavia:
  - `POST /ecommercePublico/facturacion_solicitar`;
  - `POST /ecommercePublico/evento_navegacion`;
  - `POST /ecommercePublico/busqueda_registrar`.

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_plan_readonly.php
```

## Senal actual

```text
senal_frontend=verde_datos_reales
puede_iniciar_frontend_mock=true
puede_integrar_datos_reales=true
```

Esto significa:

- el frontend externo ya puede integrar datos reales;
- el catalogo publico devuelve productos publicados;
- `cotizacion_dryrun` funciona con publicaciones reales;
- `cotizacion_registrar` sigue bloqueado en Fase 1.

Tambien puede verse desde la consola interna del ERP:

```text
http://panel.com.local/ecommercePublico/publicaciones
```

La consola muestra el semaforo del frontend, la base API recomendada, bloqueos para datos reales y siguientes pasos. Es una vista interna protegida; no es la tienda publica.

Tambien muestra comandos operativos separados en dos grupos:

- comandos `read-only` para validar readiness, bundle y secuencia sugerida;
- compuerta verde final `uat_ecommerce_publico_green_gate_readonly.php`, que exige item real en catalogo y dry-run con publicacion real;
- comandos `apply autorizado`, solo para usarse con respaldo externo y autorizacion explicita.

## Host verificado

El host correcto para endpoints publicos es:

```text
http://panel.com.local/ecommercePublico
```

No usar como base en este entorno:

```text
http://localhost/panel_de_control/ecommercePublico
```

Esa ruta puede resolver a login u otra configuracion de Apache.

## Endpoints publicos preparados

- `GET /ecommercePublico/contratos`
- `GET /ecommercePublico/estado`
- `GET /ecommercePublico/configuracion`
- `GET /ecommercePublico/seo`
- `GET /ecommercePublico/filtros`
- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`
- `GET /ecommercePublico/disponibilidad`
- `POST /ecommercePublico/cotizacion_dryrun`

Bloqueado:

- `POST /ecommercePublico/cotizacion_registrar`

## Tablas planificadas

DDL Fase 1 propone 5 tablas:

- `erp_ecommerce_publicaciones`
- `erp_ecommerce_configuracion`
- `erp_ecommerce_cotizaciones`
- `erp_ecommerce_cotizaciones_detalle`
- `erp_ecommerce_cotizaciones_eventos`

Estado actual:

```text
tablas_faltantes=0
ddl_pendiente=false
```

## Scripts read-only principales

Primero validar el entorno local. Si este comando reporta MySQL caido, HTTP sin JSON o corrupcion de tablas de sistema, no interpretar como falla de contratos ecommerce:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_entorno_readonly.php --base=http://panel.com.local
```

Luego validar suite ecommerce completa:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_activacion_suite_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
```

Luego validar contratos puntuales:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_openapi_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_carrito_whatsapp_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_activacion_bundle_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --lote=8
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --sku1=1759 --sku2=1757
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_secuencia_activacion_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --id_sku=ID_SKU
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

Todos estos son read-only.

El paquete mas util para abrir el nuevo proyecto frontend es:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
```

Ese comando concentra endpoints, documentos, scripts, senal actual y bloqueos para pasar de mocks a datos reales.

Herramientas nuevas para iniciar el otro proyecto:

- `docs/erp_ecommerce_publico_frontend_AGENTS_template.md`: plantilla de `AGENTS.md` para el proyecto frontend externo.
- `docs/erp_ecommerce_publico_frontend_archivos_iniciales.md`: estructura inicial recomendada, `.env`, cliente API y pruebas contractuales.
- `docs/erp_ecommerce_publico_orden_activacion_autorizada.md`: plantilla con datos requeridos y textos de autorizacion para pasar a datos reales.
- `uat_ecommerce_publico_activacion_suite_readonly.php`: suite principal para conocer semaforo, bloqueos y siguiente paso sin escribir BD.
- `uat_ecommerce_publico_frontend_env_readonly.php`: variables `.env` y proxy local sugerido.
- `uat_ecommerce_publico_postman_collection_readonly.php`: coleccion Postman/Insomnia para probar contratos.
- `uat_ecommerce_publico_entorno_readonly.php`: diagnostico de XAMPP/MySQL/API antes de probar el frontend.
- `uat_ecommerce_publico_apply_checklist_readonly.php`: valida datos reales antes de copiar comandos `apply_authorized`.
- `uat_ecommerce_publico_autorizacion_paquete_readonly.php`: genera paquete compacto de autorizacion con hashes, SKUs sugeridos y comandos no ejecutados.
- `uat_ecommerce_publico_cotizacion_registro_plan_readonly.php`: plan del registro interno futuro de carrito/prospecto sin desbloquear escrituras publicas.
- `uat_ecommerce_publico_post_apply_verificacion_readonly.php`: identifica etapa posterior a DDL/config/publicacion.
- `uat_ecommerce_publico_reversa_preflight_readonly.php`: valida si una reversa tecnica siquiera aplica, sin ejecutar `DROP TABLE`.
- `docs/erp_ecommerce_publico_frontend_herramientas_integracion.md`: indice de herramientas para el frontend.
- `docs/erp_ecommerce_publico_diagnostico_entorno.md`: como distinguir entorno caido de contrato ecommerce roto.
- `docs/erp_ecommerce_publico_decision_activacion_fase1.md`: decision compacta para DDL, configuracion y primer lote.

## Scripts apply autorizados

No ejecutar sin respaldo externo y autorizacion explicita.

DDL:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=RUTA_O_REFERENCIA
```

Configuracion:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND
```

Crear borrador:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=RUTA_O_REFERENCIA --id_sku=ID_SKU
```

Publicar borrador:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicar_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR --respaldo=RUTA_O_REFERENCIA --id_sku=ID_SKU --confirmar_revision=1
```

Si el SKU esta agotado y se decide publicarlo:

```bash
--confirmar_agotado=1
```

## Candados implementados

- No se usa `ecom_*` como fuente nueva.
- No se publica automaticamente todo SKU activo.
- No se descuenta inventario desde ecommerce.
- No se muestra stock exacto.
- El carrito `dry-run` no escribe BD.
- El registro real de cotizacion sigue bloqueado.
- Las publicaciones reales requieren DDL, token, respaldo y permiso.
- Publicar un borrador exige revision.
- Publicar agotados exige confirmacion adicional.
- CORS queda restringido a origenes exactos configurados.

## Senal verde esperada

Solo avisar al proyecto frontend cuando:

```text
senal_frontend=verde_datos_reales
puede_integrar_datos_reales=true
```

Ese estado requiere:

- DDL aplicado;
- WhatsApp configurado;
- CORS configurado;
- al menos una publicacion activa;
- `/catalogo` devolviendo productos reales;
- `cotizacion_dryrun` respondiendo con publicaciones reales.

Mensaje a enviar cuando ocurra:

```text
Ya puedes iniciar/integrar la vista del ecommerce externo con datos reales.
El ERP tiene DDL aplicado, CORS configurado, WhatsApp configurado y primeras publicaciones activas.
Usa docs/erp_ecommerce_publico_frontend_handoff.md y docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt.
```

## Actualizacion 2026-07-26 - Experiencia cliente inicial

- Se contempla desde Fase 1 la base de politicas, facturacion por folio, navegacion por mascota/necesidad e inteligencia de busquedas.
- Se agregan endpoints publicos read-only:
  - `GET /ecommercePublico/politicas`;
  - `GET /ecommercePublico/politica/{slug}`;
  - `GET /ecommercePublico/taxonomia_mascotas`.
- Estos endpoints responden defaults seguros con `configurado=false` si las tablas futuras aun no existen.
- Prueba HTTP:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_http_readonly.php --base=http://panel.com.local
```

- Senal actual:

```text
senal_frontend_experiencia_http=verde_politicas_taxonomia_readonly
```

- El frontend puede avanzar ya:
  - paginas de politicas desde API;
  - pantalla `/facturacion` con formulario por folio, sin POST real;
  - navegacion por mascota/necesidad desde API;
  - tracking local/mock para busquedas y navegacion.
- No conectar todavia:
  - `POST /ecommercePublico/facturacion_solicitar`;
  - `POST /ecommercePublico/evento_navegacion`;
  - `POST /ecommercePublico/busqueda_registrar`.

## Actualizacion 2026-07-27 - Base cimentada antes de produccion

- Se separa la compuerta de base local/funcional de la compuerta productiva.
- Produccion queda como fase posterior; primero se valida que el frontend basico tenga contratos y datos suficientes.
- Nueva compuerta:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_base_cimentada_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --min_publicadas=2 --min_preview=6 --skus_preview=415,866,386,1138
```

- Resultado actual:

```text
senal_base_ecommerce=verde_base_cimentada_frontend_basico
endpoints_total=13
publicadas=2
preview_total=6
cors_local_permitido=true
whatsapp_configurado=true
politicas_ok=true
taxonomia_ok=true
cotizacion_dryrun_ok=true
cotizacion_preflight_ok=true
cotizacion_registrar_bloqueado=true
```

- Esta senal permite que el frontend avance con entregable basico local.
- No significa salida productiva. Produccion se valida despues con `uat_ecommerce_publico_frontend_productivo_gate_readonly.php`.

## Actualizacion 2026-07-29 - Calidad de expansion

- Se detecto que la expansion a 6 productos no debe autorizarse todavia.
- Motivo: SKU `1138` / `SP-2823` contiene caracter de reemplazo en el titulo publico.
- Se agregaron candados read-only de texto sospechoso en:
  - `uat_ecommerce_publico_expansion_publicacion_paquete_readonly.php`;
  - `uat_ecommerce_publico_expansion_apply_checklist_readonly.php`;
  - `uat_ecommerce_publico_expansion_bundle_readonly.php`;
  - `uat_ecommerce_publico_frontend_preview_expansion_readonly.php`;
  - `uat_ecommerce_publico_frontend_entregable_gate_readonly.php`;
  - `uat_ecommerce_publico_base_cimentada_readonly.php`.
- Resultado actual de expansion:

```text
senal_actual=verde_datos_reales
senal_expansion=revisar_expansion
listos_para_borrador=3
sku_1138=validar_texto_publico
```

- Documento de revision:

```text
docs/erp_ecommerce_publico_expansion_revision_calidad_20260729.md
```

- Frontend puede seguir con catalogo real basico de 2 productos y usar preview solo para layout, sin tratar candidatos como publicados.

## Actualizacion 2026-07-29 - Ruta curada de expansion

- Se agrego validacion read-only para corregir solo el `titulo_publico` ecommerce del SKU `1138`.
- Titulo publico curado propuesto: `Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm`.
- Compuerta nueva:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_curada_6_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql
```

- Resultado validado:

```text
senal_expansion_curada=verde_expansion_curada_6_lista_para_revision
publicaciones_estimadas_post_expansion=6
```

- Esto no publica productos ni escribe BD. Solo deja lista una ruta revisable para pasar de 2 a 6 publicaciones cuando exista autorizacion explicita.

## Actualizacion 2026-07-29 - Compuertas frontend con preview curado

- `uat_ecommerce_publico_base_cimentada_readonly.php` reconoce la ruta curada del SKU `1138` como preview valido si el unico bloqueo es `validar_texto_publico`.
- `uat_ecommerce_publico_frontend_entregable_gate_readonly.php` aplica el mismo criterio.
- Resultado validado:

```text
senal_base_ecommerce=verde_base_cimentada_frontend_basico
senal_entregable_frontend=verde_entregable_frontend
preview_curado.aplicado=true
preview_curado.id_sku=1138
```

- El frontend puede avanzar a entregable local con 2 productos publicados reales y preview curado de 6 tarjetas.
- Produccion sigue fuera de alcance hasta pasar `uat_ecommerce_publico_frontend_productivo_gate_readonly.php`.

## Actualizacion 2026-07-29 - Cotizacion preflight

- Se agrego `POST /ecommercePublico/cotizacion_preflight`.
- El endpoint valida carrito, contacto, consentimiento, politicas aceptadas y WhatsApp sin persistir.
- Devuelve `folio_preliminar` con `folio_no_persistido=true`.
- Devuelve `listo_para_whatsapp` y `listo_para_registro_futuro`.
- No registra prospecto, no crea cotizacion real, no descuenta inventario y no crea pedido.

## Actualizacion 2026-07-29 - Plan de registro futuro

- Se agrego plan read-only de persistencia para `cotizacion_registrar`.
- Endpoint interno protegido: `/ecommercePublico/cotizacion_registro_plan_erp`.
- Script validado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
```

- Resultado:

```text
preflight_ok=true
plan_persistencia_ok=true
folio_planeado=ECOM-YYYYMMDD-000001
```

- El registro real sigue bloqueado por politica Fase 1.
- Documento: `docs/erp_ecommerce_publico_cotizaciones_flujo_registro_futuro.md`.

## Actualizacion 2026-07-29 - Bandeja interna cotizaciones

- Existe pantalla interna read-only: `http://panel.com.local/ecommercePublico/cotizaciones`.
- Endpoints internos protegidos:
  - `GET /ecommercePublico/cotizaciones_bandeja_erp`;
  - `GET /ecommercePublico/cotizacion_detalle_erp/{folio}`;
  - `POST /ecommercePublico/cotizacion_accion_plan_erp`.
- UAT validado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizaciones_bandeja_readonly.php
```

- Resultado actual:

```text
ok=true
configurado=true
items_total_pagina=0
no_crea_pedido=true
no_descuenta_inventario=true
```

- La bandeja esta lista como base operativa, pero seguira vacia hasta habilitar `cotizacion_registrar` con autorizacion.

## Actualizacion 2026-07-29 - Bandeja interna read-only

- Se agregaron endpoints internos protegidos:
  - `/ecommercePublico/cotizaciones_bandeja_erp`;
  - `/ecommercePublico/cotizacion_detalle_erp/{folio}`.
- Se agrego pantalla interna read-only:
  - `http://panel.com.local/ecommercePublico/cotizaciones`.
- Sirven para seguimiento futuro de cotizaciones recibidas por WhatsApp.
- No crean pedido, venta, cliente CRM ni movimiento de inventario.
- UAT:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizaciones_bandeja_readonly.php
```

- Tambien se agrego plan read-only de acciones:
  - `marcar_seguimiento`;
  - `descartar`;
  - `preparar_pedido_manual`;
  - `preparar_venta_pos_manual`.

## Actualizacion 2026-07-30 - Gobierno del canal Artiani

- Se agrega panel interno:
  - `http://panel.com.local/ecommercePublico/control`.
- Objetivo: controlar que se muestra en el ecommerce Artiani antes de abrir gobierno multi-canal para terceros.
- El panel permite:
  - buscar por SKU, nombre, marca y categoria;
  - filtrar por estatus de publicacion;
  - filtrar por disponibilidad publica;
  - excluir o aislar productos de granel;
  - guardar borradores por seleccion;
  - publicar/reactivar productos seleccionados;
  - pausar productos seleccionados para ocultarlos del API publico;
  - editar curaduria por producto;
  - controlar banderas por publicacion: destacado, mostrar precio, mostrar disponibilidad, permitir cotizacion y permitir WhatsApp.
- No requiere DDL nuevo porque usa `erp_ecommerce_publicaciones`.
- No toca Catalogo ERP, precios, imagenes, inventario ni legacy `ecom_*`.
- Las escrituras internas requieren `catalogo.editar`, CSRF, token interno y auditoria explicita.
- Para publicar productos agotados sigue siendo necesaria confirmacion operativa (`confirmar_agotado`).
- Decision: primero nutrir y gobernar bien el canal propio `catalogo_publico`/Artiani; despues activar la capa de terceros con `erp_ecommerce_canales_api`, credenciales, allowlist y logs.

## Actualizacion 2026-07-30 - Plan mascotas y recomendaciones

- Se documenta el plan para convertir el ecommerce en una experiencia orientada a mascotas, no solo productos/categorias.
- Documento vivo:
  - `docs/erp_ecommerce_mascotas_perfilado_recomendaciones_plan.md`.
- Decision:
  - usar una taxonomia viva de mascotas: especie, atributos, necesidades, restricciones y reglas de compatibilidad;
  - iniciar con navegacion por mascota/necesidad;
  - evolucionar hacia perfil temporal, recomendaciones preview, configuracion ERP y mascotas registradas en CRM;
  - no prometer diagnostico medico ni obligar al cliente a registrarse en fases iniciales.

## Actualizacion 2026-07-30 - API catalogo robusta para frontend

- Se fortalece `GET /ecommercePublico/catalogo` para que frontend pueda construir vistas reales del ecommerce sin hardcodear bloques.
- Nuevos filtros publicos:
  - `disponibilidad`: `disponible`, `pocas_piezas`, `consultar_disponibilidad`, `agotado`;
  - `destacado=1`;
  - `orden`: `relevancia`, `nombre`, `precio_asc`, `precio_desc`, `recientes`.
- La respuesta de catalogo ahora incluye:
  - `filtros_aplicados`;
  - `ordenamientos_disponibles`.
- `GET /ecommercePublico/filtros` ahora tambien devuelve conteos por disponibilidad publica.
- Se agrega `GET /ecommercePublico/secciones` para entregar bloques listos para home/frontend:
  - destacados;
  - recien agregados;
  - disponibles;
  - pocas piezas;
  - bloques por mascota detectada;
  - bloques por necesidad detectada.
- Guardrails conservados:
  - solo publicaciones activas;
  - no expone stock exacto;
  - no usa legacy `ecom_*` como fuente publica;
  - no descuenta inventario;
  - no escribe base de datos.

## Actualizacion 2026-07-30 - Detalle publico de producto robusto

- Se fortalece `GET /ecommercePublico/producto/{slug}` manteniendo compatible `depurar.item`.
- La respuesta ahora agrega contexto para vistas de detalle:
  - `variantes`: otras publicaciones activas del mismo producto ERP;
  - `relacionados`: publicaciones activas por categoria, mascota y necesidades;
  - `breadcrumbs`: ruta simple para navegacion frontend;
  - `seo`: title, description, canonical path, imagen y JSON-LD basico `Product`;
  - `acciones`: banderas para cotizacion, WhatsApp, precio y disponibilidad.
- El endpoint sigue siendo read-only:
  - solo devuelve publicaciones activas;
  - solo relacionados publicados;
  - no expone stock exacto;
  - no descuenta inventario;
  - no escribe base de datos.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_producto_detalle_readonly.php
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=detalle_producto_robusto`;
  - producto ejemplo: `alimento-churro-blanco-para-peces-100-gr-g-tp-40372-100gr`;
  - `variantes=1`;
  - `relacionados=3`;
  - `breadcrumbs=5`.

## Actualizacion 2026-07-30 - Cotizacion preflight robusta

- Se fortalece `POST /ecommercePublico/cotizacion_dryrun`.
- La respuesta agrega:
  - `resumen`: items recibidos, lineas validas, cantidad total y disponibilidad agregada;
  - `advertencias`: senales de pocas piezas, agotado o disponibilidad por confirmar;
  - `frontend`: limites y mensajes para que la vista no dependa de reglas hardcodeadas.
- Se fortalece `POST /ecommercePublico/cotizacion_preflight`.
- La respuesta agrega:
  - `validacion_contacto`: campos presentes y validez para registro futuro;
  - `consentimiento`: WhatsApp, aviso de privacidad y politicas aceptadas;
  - `cta`: accion lista para frontend, incluyendo URL WhatsApp cuando aplica;
  - `frontend`: pasos sugeridos del flujo carrito -> contacto -> confirmacion -> WhatsApp.
- El registro real sigue bloqueado en Fase 1:
  - no inserta cotizaciones;
  - no crea pedido;
  - no descuenta inventario;
  - no convierte cliente CRM.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_preflight_robusto_readonly.php
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=cotizacion_preflight_robusto`;
  - `listo_para_whatsapp=true`;
  - `listo_para_registro_futuro=true`;
  - `cta_tipo=whatsapp`;
  - `cta_url_generada=true`.

## Actualizacion 2026-07-31 - Estado publico seguro de canales API

- Se agrega `GET /ecommercePublico/canales_estado`.
- Objetivo:
  - informar si la capa multi-canal/API para Artiani y partners esta en diseno, pendiente de DDL o disponible;
  - exponer scopes, estado de tablas, conteos seguros y pasos de activacion;
  - no exponer secretos, hashes, tokens, API secrets ni configuracion sensible.
- Respuesta actual esperada antes de DDL canales:
  - `tipo=info`;
  - `configurado=false`;
  - `modo=multi_canal_diseno_readonly`;
  - `bloqueos_total=5`.
- Guardrails:
  - read-only;
  - no genera credenciales;
  - no expone `api_secret`;
  - no activa autenticacion obligatoria;
  - no modifica CORS;
  - no cambia publicaciones;
  - no registra cotizaciones.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_estado_readonly.php --base=http://panel.com.local
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=canales_estado_seguro`;
  - `modo=multi_canal_diseno_readonly`;
  - `bloqueos_total=5`;
  - `credenciales_activas=0`.

## Observacion entorno 2026-07-31 - Health MySQL corregido a puerto del proyecto

- El proyecto local usa `MYSQLPORT=3406`.
- Se detecto que una validacion antigua consultaba MySQL sin respetar `MYSQLPORT`, generando falso negativo contra el puerto por defecto.
- Se actualiza `storage/uat/uat_mysql_xampp_health_readonly.php` para conectar con `MYSQLHOST`, `MYSQLPORT` y `MYSQLBASE`.
- Resultado validado:
  - `PASS_MYSQL_XAMPP_HEALTH`;
  - `pdo_ok=true`;
  - `puerto=3406`;
  - `bloqueos=[]`.
- El green gate ecommerce vuelve a verde:
  - `ok=true`;
  - `senal_frontend=verde_datos_reales`;
  - `publicadas=6`;
  - `whatsapp_configurado=true`;
  - `cors_configurado=true`.
- Nota:
  - Windows mantiene excluido el rango `3240-3339`, por lo que no conviene usar `3306` ni `3307` en esta maquina;
  - `3406` queda como puerto local operativo para este proyecto.

## Actualizacion 2026-07-31 - Bootstrap frontend ecommerce

- Se agrega `GET /ecommercePublico/bootstrap`.
- Objetivo:
  - permitir que el frontend arranque con una sola llamada;
  - agrupar estado, configuracion, filtros, secciones, politicas y estado de canales;
  - evitar que la primera vista tenga que orquestar multiples requests.
- Parametro:
  - `limite_secciones`: 1-12, default 6.
- Respuesta incluye:
  - `ready`;
  - `estado`;
  - `configuracion`;
  - `filtros`;
  - `secciones`;
  - `politicas`;
  - `canales`;
  - `frontend`;
  - `guardrails`.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_bootstrap_readonly.php --base=http://panel.com.local --limite=3
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=bootstrap_frontend_listo`;
  - `ready=true`;
  - `publicadas=6`;
  - `secciones=7`;
  - `mascotas=2`;
  - `necesidades=2`;
  - `canales_modo=multi_canal_diseno_readonly`.
- Guardrails:
  - no escribe BD;
  - no expone secretos;
  - no muestra stock exacto;
  - no descuenta inventario;
  - no registra cotizacion.

## Actualizacion 2026-07-31 - Sugerencias publicas de busqueda

- Se agrega `GET /ecommercePublico/busqueda_sugerencias`.
- Objetivo:
  - habilitar buscador/autocomplete en frontend;
  - sugerir productos publicados, marcas, categorias, mascotas y necesidades;
  - mantener separado buscar/mostrar de registrar analitica futura.
- Parametros:
  - `q`: texto opcional;
  - `limite`: 1-12 por grupo, default 6.
- Respuesta incluye:
  - `grupos.productos`;
  - `grupos.marcas`;
  - `grupos.categorias`;
  - `grupos.mascotas`;
  - `grupos.necesidades`;
  - `resumen`;
  - `frontend`;
  - `guardrails`.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_busqueda_sugerencias_readonly.php --base=http://panel.com.local --q=filtro --limite=4
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=busqueda_sugerencias_lista`;
  - `total_sugerencias=2`;
  - `productos=2`;
  - primer producto: `Filtro de canastilla presurizado 960 l/hr`.
- Guardrails:
  - no escribe BD;
  - no registra busqueda;
  - solo publicados;
  - no expone stock exacto;
  - no expone costos.

## Actualizacion 2026-07-31 - Navegacion publica ecommerce

- Se agrega `GET /ecommercePublico/navegacion`.
- Objetivo:
  - entregar menus/chips/rutas listas para frontend;
  - evitar hardcodear navegacion por mascota, necesidad, categoria, marca o disponibilidad;
  - mantener filtros y navegacion como contratos distintos.
- Tambien se integra `navegacion` dentro de `GET /ecommercePublico/bootstrap`.
- Parametro:
  - `limite`: 1-30 por grupo, default 12.
- Respuesta incluye:
  - `primaria`;
  - `mascotas`;
  - `necesidades`;
  - `categorias`;
  - `marcas`;
  - `disponibilidad`;
  - `resumen`;
  - `guardrails`.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_navegacion_readonly.php --base=http://panel.com.local --limite=5
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=navegacion_publica_lista`;
  - `total_items=16`;
  - `primaria=4`;
  - `mascotas=2`;
  - `necesidades=2`;
  - `categorias=4`;
  - `marcas=2`;
  - `disponibilidad=2`;
  - `bootstrap_incluye_navegacion=true`.
- `uat_ecommerce_publico_bootstrap_readonly.php` ahora reporta `navegacion=16`.
- Guardrails:
  - no escribe BD;
  - solo derivado de publicaciones;
  - no expone secretos.

## Actualizacion 2026-07-31 - SEO publico robusto

- Se fortalece `GET /ecommercePublico/seo`.
- Objetivo:
  - entregar a frontend insumos listos para `robots.txt`, `sitemap.xml`, rutas SEO y JSON-LD;
  - incluir productos publicados y rutas por filtros navegables;
  - mantener al ERP como fuente de verdad sin generar archivos fisicos.
- La respuesta ahora incluye:
  - `sitemap_xml_sugerido`;
  - `rutas`;
  - `resumen`;
  - filtros SEO para mascotas, necesidades, categorias, marcas y disponibilidad.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_seo_robusto_readonly.php --base=http://panel.com.local --limite=20
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=seo_robusto_listo`;
  - `rutas_total=21`;
  - `productos=6`;
  - `mascotas=2`;
  - `necesidades=2`;
  - `categorias=4`;
  - `marcas=2`;
  - `disponibilidad=2`;
  - `sitemap_xml_generado=true`;
  - `robots_txt_generado=true`.
- Guardrails:
  - no escribe BD;
  - no genera archivos reales;
  - no muestra stock exacto;
  - no usa legacy `ecom_*`.

## Actualizacion 2026-07-31 - Handoff tecnico frontend actualizado

- Se actualizan entregables read-only para que frontend consuma la API ecommerce sin leer backend ni tablas internas.
- Scripts actualizados:
  - `storage/uat/uat_ecommerce_publico_openapi_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_postman_collection_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_frontend_package_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_contract_shape_readonly.php`.
- El handoff ahora incluye estos endpoints nuevos/fortalecidos:
  - `GET /ecommercePublico/bootstrap`;
  - `GET /ecommercePublico/secciones`;
  - `GET /ecommercePublico/busqueda_sugerencias`;
  - `GET /ecommercePublico/navegacion`;
  - `GET /ecommercePublico/canales_estado`;
  - `GET /ecommercePublico/seo` con rutas y sitemap XML sugerido;
  - `GET /ecommercePublico/catalogo` con `disponibilidad`, `destacado` y `orden`.
- Se elimina de `no_usar` la indicacion obsoleta de evitar `/bootstrap`; ahora es el endpoint recomendado para cargar la vista inicial.
- El contract-shape valida 21 endpoints publicos esperados y los wrappers principales:
  - bootstrap;
  - filtros con disponibilidad;
  - navegacion;
  - secciones;
  - sugerencias de busqueda;
  - canales/API en modo seguro read-only.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

- Resultado validado:
  - `contract_shape.ok=true`;
  - `endpoints_publicos_esperados=21`;
  - `frontend_package.senal_frontend_actual=verde_datos_reales`;
  - `frontend_package.puede_integrar_datos_reales=true`;
  - `green_gate.ok=true`;
  - publicaciones reales publicadas: `6`.

## Actualizacion 2026-07-31 - Documentos frontend alineados al contrato robusto

- Se actualizan documentos de consumo frontend:
  - `docs/erp_ecommerce_publico_frontend_handoff.md`;
  - `docs/erp_ecommerce_publico_api_contratos.md`;
  - `docs/erp_ecommerce_publico_cliente_api_frontend.md`;
  - `docs/erp_ecommerce_publico_frontend_contract_tests.md`.
- Cambios clave:
  - `/bootstrap` queda como carga inicial recomendada;
  - se documenta total actual de 21 endpoints publicos;
  - se agregan `busqueda_sugerencias`, `navegacion`, `secciones` y `canales_estado`;
  - se documentan `disponibilidad`, `destacado` y `orden` en catalogo;
  - el cliente TypeScript de referencia incluye metodos y tipos para las rutas nuevas;
  - contract tests frontend incluyen SEO robusto, filtros con disponibilidad, navegacion, secciones, sugerencias y canales.
- No se modifica BD ni reglas operativas; es alineacion documental y de integracion.

## Actualizacion 2026-07-31 - Smoke HTTP ampliado para frontend robusto

- Se actualiza `storage/uat/uat_ecommerce_publico_http_smoke_readonly.php`.
- Ahora prueba por HTTP:
  - `GET /ecommercePublico/bootstrap?limite_secciones=3`;
  - `GET /ecommercePublico/seo?limite=20`;
  - `GET /ecommercePublico/busqueda_sugerencias?q=filtro&limite=4`;
  - `GET /ecommercePublico/navegacion?limite=5`;
  - `GET /ecommercePublico/secciones?limite=3`;
  - `GET /ecommercePublico/catalogo?disponibilidad=disponible&orden=precio_asc&limite=3`;
  - `GET /ecommercePublico/canales_estado`.
- Tambien conserva pruebas previas de estado, contratos, configuracion, filtros, catalogo, producto no publicado, disponibilidad, dry-run, preflight y endpoints POST bloqueados/preflight.
- Resultado validado:
  - `ok=true`;
  - `seo.rutas_total=21`;
  - `navegacion.navegacion_total=16`;
  - `busqueda_sugerencias.sugerencias_total=2`;
  - `secciones.secciones_total=7`;
  - `catalogo_disponible_ordenado.items_total=3`;
  - `canales_estado.tipo=info`.
- Guardrails:
  - no escribe BD;
  - no registra cotizacion;
  - no mueve inventario.

## Actualizacion 2026-07-31 - Snapshot vivo frontend robusto

- Se actualiza `storage/uat/uat_ecommerce_publico_frontend_snapshot_readonly.php`.
- El snapshot ahora incluye ejemplos reales/resumidos de:
  - `bootstrap`;
  - navegacion publica;
  - secciones;
  - busqueda_sugerencias;
  - SEO/rutas/sitemap XML sugerido;
  - catalogo normal;
  - catalogo filtrado por `disponibilidad=disponible` y `orden=precio_asc`;
  - producto detalle con variantes, relacionados y breadcrumbs;
  - disponibilidad publica;
  - dry-run de cotizacion.
- Se actualiza `docs/erp_ecommerce_publico_frontend_snapshot_vivo.md`.
- UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=3 --min_publicaciones=6
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=verde_datos_reales`;
  - `publicadas=6`;
  - `catalogo_items_snapshot=3`;
  - `catalogo_disponible_ordenado_items=3`;
  - `secciones_total=7`;
  - `navegacion_total=16`;
  - `sugerencias_total=2`;
  - `seo_rutas_total=21`.
- Guardrails:
  - no escribe BD;
  - no registra cotizacion;
  - no descuenta inventario;
  - no expone stock exacto.

## Actualizacion 2026-08-01 - Catalogo publico con metadatos UI

- Se fortalece `GET /ecommercePublico/catalogo`.
- La respuesta ahora incluye `depurar.frontend` con:
  - `hay_resultados`;
  - `items_en_pagina`;
  - `total_paginas`;
  - `pagina_anterior`;
  - `pagina_siguiente`;
  - `rango_visible`;
  - `filtros_activos`;
  - `filtros_activos_total`;
  - `estado_vacio`;
  - `guardrails_ui`.
- Objetivo:
  - facilitar paginacion y contador de resultados;
  - permitir chips de filtros activos;
  - entregar estado vacio consistente cuando no hay resultados;
  - recordar al frontend que precio es estimado y cotizacion requiere dry-run.
- Se agrega UAT read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_catalogo_robusto_readonly.php --base=http://panel.com.local --limite=3
```

- Resultado validado:
  - `ok=true`;
  - `senal_frontend=catalogo_robusto_listo`;
  - `base_items=3`;
  - `base_total=6`;
  - `disponibles_items=3`;
  - `pocas_piezas_items=3`;
  - `sin_resultados_estado_vacio=true`;
  - ordenamientos: `relevancia`, `nombre`, `precio_asc`, `precio_desc`, `recientes`.
- Se actualizan:
  - `storage/uat/uat_ecommerce_publico_contract_shape_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_frontend_package_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_frontend_snapshot_readonly.php`;
  - `docs/erp_ecommerce_publico_api_contratos.md`;
  - `docs/erp_ecommerce_publico_cliente_api_frontend.md`;
  - `docs/erp_ecommerce_publico_frontend_contract_tests.md`.
- Guardrails:
  - no escribe BD;
  - no ejecuta DDL;
  - no mueve inventario;
  - no expone stock exacto.

## Actualizacion 2026-08-01 - Fixtures frontend alineados al contrato robusto

- Se actualiza `storage/uat/uat_ecommerce_publico_frontend_fixtures_readonly.php`.
- Los fixtures ahora incluyen:
  - `bootstrap`;
  - `navegacion`;
  - `secciones`;
  - `busqueda_sugerencias`;
  - `canales_estado`;
  - `catalogo` con `filtros_aplicados`, `ordenamientos_disponibles` y `frontend`;
  - `catalogo_sin_resultados` con estado vacio;
  - `producto` con relacionados, breadcrumbs, SEO y acciones;
  - `seo` con `rutas`, `sitemap_xml_sugerido` y `resumen`;
  - filtros con `disponibilidad`.
- Se actualiza `docs/erp_ecommerce_publico_fixtures_frontend.md`.
- Nota operativa:
  - el flujo principal ya debe usar API real porque `green_gate.ok=true`;
  - fixtures quedan para UI, pruebas unitarias y fallback local;
  - no deben mezclarse con ventas ni cotizaciones reales.
- UAT read-only:

```bash
C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

- Resultado validado:
  - sintaxis PHP OK;
  - salida JSON valida;
  - incluye wrapper `error/tipo/mensaje/api/depurar` en secciones principales;
  - no consulta BD;
  - no escribe BD.

## Actualizacion 2026-08-01 - Smoke HTTP valida metadatos UI de catalogo

- Se actualiza `storage/uat/uat_ecommerce_publico_http_smoke_readonly.php`.
- Ahora prueba tambien:
  - `GET /ecommercePublico/catalogo?q=__sin_resultados_catalogo_frontend__&limite=3`;
  - `catalogo.depurar.frontend.hay_resultados`;
  - `catalogo.depurar.frontend.rango_visible.texto`;
  - `catalogo.depurar.frontend.guardrails_ui.cotizacion_requiere_dryrun`;
  - `catalogo_sin_resultados.depurar.frontend.estado_vacio.mostrar`.
- Se actualiza `docs/erp_ecommerce_publico_frontend_estados_ui.md` para usar los campos `depurar.frontend` del API.
- Se limpia en `docs/erp_ecommerce_publico_fixtures_frontend.md` la referencia antigua a catalogo real vacio como estado principal.
- UAT read-only:

```bash
C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_http_smoke_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
```

- Resultado validado:
  - `ok=true`;
  - `catalogo_frontend_hay_resultados=true`;
  - `catalogo_frontend_rango_texto=Mostrando 1-3 de 6`;
  - `catalogo_frontend_requiere_dryrun=true`;
  - `catalogo_sin_resultados.catalogo_frontend_estado_vacio=true`.

## Actualizacion 2026-08-01 - Smoke HTTP valida detalle real de producto

- Se refuerza `storage/uat/uat_ecommerce_publico_http_smoke_readonly.php`.
- El smoke ahora toma el primer `slug` real desde `GET /ecommercePublico/catalogo?limite=3` y consulta `GET /ecommercePublico/producto/{slug}`.
- La validacion evita que frontend avance con una ficha de producto incompleta:
  - `depurar.item` presente;
  - breadcrumbs disponibles;
  - relacionados disponibles como array;
  - SEO de producto con `title`;
  - `acciones.puede_cotizar=true`.
- UAT read-only validado:

```bash
C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_http_smoke_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
```

- Resultado validado:
  - `ok=true`;
  - producto ejemplo: `alimento-churro-blanco-para-peces-100-gr-g-tp-40372-100gr`;
  - `producto_variantes_total=1`;
  - `producto_relacionados_total=3`;
  - `producto_breadcrumbs_total=5`;
  - `producto_seo_title=Alimento churro blanco para peces 100 gr | Artiani`;
  - `producto_acciones_puede_cotizar=true`.

## Actualizacion 2026-08-01 - OpenAPI/Postman alineados a catalogo y producto robustos

- Se actualiza `storage/uat/uat_ecommerce_publico_openapi_readonly.php`.
- OpenAPI ahora expone schemas especificos:
  - `CatalogoResponse`;
  - `CatalogoDepurar`;
  - `CatalogoFrontend`;
  - `ProductoDetalleResponse`;
  - `ProductoDetalleDepurar`.
- Se actualiza `storage/uat/uat_ecommerce_publico_postman_collection_readonly.php`.
- La coleccion ahora usa un slug real publicado por defecto y conserva prueba separada para producto no publicado.
- Tambien agrega request de catalogo sin resultados para validar estado vacio frontend.
- UAT read-only validado:

```bash
C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_openapi_readonly.php
C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_postman_collection_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_openapi_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local
```

- Resultado validado:
  - sintaxis PHP OK;
  - salida JSON valida;
  - `/catalogo` referencia `CatalogoResponse`;
  - `/producto/{slug}` referencia `ProductoDetalleResponse`;
  - variable Postman `producto_slug=alimento-churro-blanco-para-peces-100-gr-g-tp-40372-100gr`.

## Actualizacion 2026-08-01 - Disponibilidad con metadatos UI

- Se fortalece `GET /ecommercePublico/disponibilidad`.
- La respuesta ahora agrega `depurar.frontend` para que frontend pinte ficha/tarjeta sin duplicar reglas:
  - `estado`;
  - `badge.label`;
  - `badge.tono`;
  - `mensaje`;
  - `cta.label`;
  - `cta.habilitado`;
  - `cta.accion=cotizacion_dryrun`;
  - `mostrar_stock_exacto=false`;
  - `precio_es_estimado=true`;
  - `requiere_dryrun_antes_de_whatsapp=true`.
- Se actualizan:
  - `storage/uat/uat_ecommerce_publico_contract_shape_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_http_smoke_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_openapi_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_frontend_fixtures_readonly.php`;
  - `docs/erp_ecommerce_publico_api_contratos.md`;
  - `docs/erp_ecommerce_publico_frontend_contract_tests.md`;
  - `docs/erp_ecommerce_publico_fixtures_frontend.md`.
- UAT read-only validado:

```bash
C:\xampp\php\php.exe -l app\modelos\EcommerceCatalogoPublico.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=2
```

- Resultado validado:
  - `ok=true`;
  - disponibilidad real ejemplo: `disponible`;
  - badge: `Disponible`;
  - CTA: `Agregar a cotizacion`;
  - `mostrar_stock_exacto=false`;
  - `requiere_dryrun_antes_de_whatsapp=true`.

## Actualizacion 2026-08-01 - Cotizacion dry-run con estado UI de carrito

- Se fortalece `POST /ecommercePublico/cotizacion_dryrun`.
- La respuesta ahora agrega `depurar.frontend` para que frontend controle el carrito sin duplicar reglas:
  - `estado`: `vacio`, `listo`, `observaciones` o `bloqueado`;
  - `mensaje`;
  - `lineas_total`;
  - `bloqueos_total`;
  - `advertencias_total`;
  - `puede_continuar_preflight`;
  - `mostrar_total_estimado`;
  - `mostrar_whatsapp_preview`;
  - `total_estimado_texto`;
  - `cta_principal.endpoint_siguiente=/ecommercePublico/cotizacion_preflight`;
  - `guardrails_ui.no_usar_precio_local_como_total=true`.
- Se actualizan:
  - `app/modelos/EcommerceCatalogoPublico.php`;
  - `storage/uat/uat_ecommerce_publico_contract_shape_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_http_smoke_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_openapi_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_frontend_fixtures_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_frontend_snapshot_readonly.php`;
  - `docs/erp_ecommerce_publico_api_contratos.md`;
  - `docs/erp_ecommerce_publico_carrito_whatsapp_frontend.md`;
  - `docs/erp_ecommerce_publico_frontend_contract_tests.md`.
- UAT read-only validado:

```bash
C:\xampp\php\php.exe -l app\modelos\EcommerceCatalogoPublico.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_openapi_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=2
```

- Resultado validado:
  - `ok=true`;
  - `dryrun.frontend.estado=listo`;
  - `puede_continuar_preflight=true`;
  - CTA siguiente: `/ecommercePublico/cotizacion_preflight`;
  - `guardrails_ui.no_usar_precio_local_como_total=true`;
  - no escribe BD, no registra cotizacion, no descuenta inventario.

## Actualizacion 2026-08-01 - Handoff frontend consumible por API

- Se agrega `GET /ecommercePublico/frontend_handoff?limite=2`.
- Objetivo: que el proyecto frontend externo no tenga que abrir rutas fisicas ni documentos dentro de `panel_de_control`.
- El endpoint entrega:
  - `estado_actual.senal_frontend`;
  - `variables_env_frontend`;
  - `endpoints_para_consumir`;
  - `orden_recomendado_integracion`;
  - `pruebas_con_api`;
  - `contratos_ui`;
  - `ejemplos`;
  - `no_usar`;
  - `guardrails.no_requiere_filesystem=true`.
- Se actualizan:
  - `app/controladores/EcommercePublico.php`;
  - `app/modelos/EcommerceCatalogoPublico.php`;
  - `storage/uat/uat_ecommerce_publico_contract_shape_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_http_smoke_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_openapi_readonly.php`;
  - `storage/uat/uat_ecommerce_publico_frontend_package_readonly.php`;
  - `docs/erp_ecommerce_publico_api_contratos.md`;
  - `docs/erp_ecommerce_publico_frontend_contract_tests.md`.

Contrato operativo:

```http
GET http://panel.com.local/ecommercePublico/frontend_handoff?limite=2
```

Regla: los documentos `docs/*.md` quedan como memoria interna del ERP; frontend debe descubrir contratos, ejemplos y readiness por HTTP.

UAT read-only validado:

```bash
C:\xampp\php\php.exe -l app\controladores\EcommercePublico.php
C:\xampp\php\php.exe -l app\modelos\EcommerceCatalogoPublico.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

Resultado validado:

- `frontend_handoff` responde por HTTP;
- `handoff_senal_frontend=verde_datos_reales`;
- `handoff_endpoints_total=22`;
- `handoff_pruebas_total=9`;
- `guardrails.no_requiere_filesystem=true`;
- `green_gate.ok=true`.
## Actualizacion 2026-08-04 - Ecommerce / Analytics Fase 1

- Se abre modulo dedicado `Ecommerce / Analytics` separado de ventas, checkout, inventario y legacy `ecom_*`.
- Nuevo documento vivo: `docs/erp_ecommerce_analytics_fase1.md`.
- Nuevos modelos:
  - `app/modelos/EcommerceAnalyticsErp.php`;
  - `app/modelos/EcommerceAnalyticsEsquema.php`.
- Nueva vista interna read-only:
  - `GET /ecommercePublico/analytics`;
  - asset `public/assets/js/custom/apps/erp/ecommerce/analytics.js`.
- Nuevos endpoints publicos/preflight:
  - `GET /ecommercePublico/analytics_contrato`;
  - `POST /ecommercePublico/analytics_sesion`;
  - `POST /ecommercePublico/analytics_conversion`.
- Los endpoints existentes `POST /ecommercePublico/evento_navegacion` y `POST /ecommercePublico/busqueda_registrar` ahora validan contra el contrato dedicado de analytics y siguen sin escribir BD.
- Nuevos endpoints internos read-only:
  - `GET /ecommercePublico/analytics_dashboard_erp`;
  - `GET /ecommercePublico/analytics_persistencia_plan_erp`;
  - `GET /ecommercePublico/analytics_resumen_plan_erp`;
  - `GET /ecommercePublico/analytics_retencion_plan_erp`;
  - `GET /ecommercePublico/esquema_auditar_analytics`;
  - `GET /ecommercePublico/esquema_plan_analytics`.
- Nuevos UATs:
  - `storage/uat/uat_ecommerce_analytics_plan_readonly.php`;
  - `storage/uat/uat_ecommerce_analytics_http_readonly.php`;
  - `storage/uat/uat_ecommerce_analytics_dashboard_readonly.php`;
  - `storage/uat/uat_ecommerce_analytics_persistencia_guard_readonly.php`;
  - `storage/uat/uat_ecommerce_analytics_resumen_guard_readonly.php`;
  - `storage/uat/uat_ecommerce_analytics_retencion_guard_readonly.php`;
  - `storage/uat/uat_ecommerce_analytics_schema_postcheck_readonly.php`;
  - `storage/uat/uat_ecommerce_analytics_schema_apply_authorized.php`.
- Tablas propuestas sin aplicar:
  - `erp_ecommerce_analytics_sesiones`;
  - `erp_ecommerce_analytics_eventos`;
  - `erp_ecommerce_analytics_busquedas`;
  - `erp_ecommerce_analytics_conversiones`;
  - `erp_ecommerce_analytics_resumen_diario`.
- Guardrails activos: no datos personales, no stock exacto, no checkout/pagos, no ventas, no descuento de inventario, no `ecom_*` como fuente.
- El modelo tiene persistencia write-ready bloqueada por token `ECOMMERCE_ANALYTICS_TRACKING`, no conectada a POST publicos en Fase 1.
- El resumen diario queda write-ready bloqueado por token `ECOMMERCE_ANALYTICS_RESUMEN_DIARIO`, sin jobs activos ni endpoint publico.
- El dashboard ya soporta `fuente_metricas=resumen_diario` cuando existan agregados; si no, cae a `eventos_crudos`.
- La retencion queda write-ready bloqueada por token `ECOMMERCE_ANALYTICS_RETENCION`; conserva resumen diario y no ejecuta purgas en Fase 1.
- La auditoria de esquema analytics ahora valida columnas e indices criticos, no solo existencia de tablas.
- Persistencia real queda pendiente de autorizacion explicita, respaldo externo, rate limit, cookie/consent y UAT `apply_authorized`.
