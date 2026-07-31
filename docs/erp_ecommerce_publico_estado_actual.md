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
