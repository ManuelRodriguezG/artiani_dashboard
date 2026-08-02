# ERP Ecommerce publico - Contratos API Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-01  
Estado: contrato read-only para frontend ecommerce externo; API publica Fase 1 cubierta por smoke HTTP y handoff tecnico.

## Decision

El ERP no renderiza la tienda publica. El proyecto ecommerce vive aparte y consume endpoints del ERP. Este sistema prepara informacion viva y controlada: productos publicados, filtros, detalle y disponibilidad simple.

## Manifiesto

Endpoint:

```http
GET /ecommercePublico/contratos
```

Uso:

- permite al frontend ecommerce conocer rutas, parametros y guardrails;
- no consulta tablas sensibles;
- no escribe BD.

## Endpoints publicos Fase 1

La API publica se mantiene en contratos separados, con `frontend_handoff` como punto de entrada para proyectos externos y `bootstrap` como agregador inicial recomendado para evitar muchas llamadas en el primer render.

Total actual: 22 endpoints publicos read-only/dry-run/preflight.

### Frontend handoff

```http
GET /ecommercePublico/frontend_handoff?limite=2
```

Uso recomendado para cualquier frontend que viva fuera de `panel_de_control`. Devuelve todo lo necesario para integrarse sin abrir archivos del ERP:

- `estado_actual.senal_frontend`;
- `variables_env_frontend`;
- `endpoints_para_consumir`;
- `orden_recomendado_integracion`;
- `pruebas_con_api`;
- `contratos_ui`;
- `ejemplos`;
- `no_usar`;
- `guardrails.no_requiere_filesystem=true`.

El frontend no debe consultar `docs/*.md`, tablas internas ni rutas fisicas del ERP. Si necesita descubrir estado o contrato, debe usar este endpoint, `/contratos`, `/estado` y `/bootstrap`.

### Estado/readiness

```http
GET /ecommercePublico/estado
```

Devuelve readiness del API para el proyecto ecommerce:

- esquema de publicaciones disponible;
- esquema de configuracion disponible;
- total de publicaciones publicadas;
- SKUs publicables detectados en ERP;
- guardrails activos;
- pendientes de seguridad antes de produccion.

### Bootstrap frontend

```http
GET /ecommercePublico/bootstrap?limite_secciones=6
```

Uso recomendado para el primer render del frontend publico. Devuelve en un solo payload:

- `estado`;
- `configuracion`;
- `filtros`;
- `navegacion`;
- `secciones`;
- `politicas`;
- `canales`;
- `guardrails`.

No reemplaza los endpoints separados; los agrupa para home/layout inicial. Mantiene modo read-only, no expone secretos, no muestra stock exacto y no registra cotizaciones.

### Catalogo

```http
GET /ecommercePublico/catalogo?q=&mascota=&necesidad=&marca=&categoria=&disponibilidad=&destacado=&orden=relevancia&pagina=1&limite=24
```

Devuelve solo publicaciones con `estatus_publicacion='publicado'`. Si el esquema aun no existe, responde `configurado=false` e `items=[]`.

Parametros adicionales vigentes:

- `disponibilidad`: `disponible`, `pocas_piezas`, `consultar_disponibilidad` o `agotado`;
- `destacado`: `1` para mostrar solo destacados;
- `orden`: `relevancia`, `nombre`, `precio_asc`, `precio_desc` o `recientes`.

La respuesta incluye `depurar.frontend` con:

- `hay_resultados`;
- `items_en_pagina`;
- `total_paginas`;
- `pagina_anterior`;
- `pagina_siguiente`;
- `rango_visible`;
- `filtros_activos`;
- `estado_vacio`;
- `guardrails_ui`.

Esto permite construir paginacion, contador de resultados, chips de filtros y estado vacio sin duplicar reglas en el frontend.

La especificacion OpenAPI expone `CatalogoResponse`, `CatalogoDepurar` y `CatalogoFrontend` para generar tipos o mocks sin leer codigo PHP.

### Producto

```http
GET /ecommercePublico/producto/{slug}
```

Devuelve una sola publicacion publicada. No muestra productos pausados, borradores ni SKUs inactivos.

La especificacion OpenAPI expone `ProductoDetalleResponse` y `ProductoDetalleDepurar`. El contrato esperado incluye `item`, `variantes`, `relacionados`, `breadcrumbs`, `seo`, `acciones` y `guardrails`.

### Filtros

```http
GET /ecommercePublico/filtros
```

Devuelve mascotas, necesidades, marcas y categorias derivadas de publicaciones vigentes.

Tambien devuelve `disponibilidad` para construir filtros publicos sin mostrar cantidades exactas.

### Busqueda sugerencias

```http
GET /ecommercePublico/busqueda_sugerencias?q=filtro&limite=4
```

Devuelve sugerencias para autocomplete/buscador:

- productos publicados;
- marcas;
- categorias;
- mascotas;
- necesidades.

No registra busquedas ni escribe BD. Para analytics futuro existe `POST /busqueda_registrar` como preflight separado.

### Navegacion publica

```http
GET /ecommercePublico/navegacion?limite=8
```

Devuelve estructura lista para menus, chips y rutas:

- `primaria`;
- `mascotas`;
- `necesidades`;
- `categorias`;
- `marcas`;
- `disponibilidad`.

La navegacion se deriva de publicaciones vigentes y se incluye tambien dentro de `bootstrap`.

### Secciones home/catalogo

```http
GET /ecommercePublico/secciones?limite=4
```

Devuelve bloques de productos listos para home o secciones editoriales basicas:

- destacados;
- disponibles;
- grupos por mascota;
- grupos por necesidad.

No crea contenido ni guarda preferencias. Solo organiza publicaciones publicas existentes.

### Politicas publicas

```http
GET /ecommercePublico/politicas
GET /ecommercePublico/politica/{slug}
```

Devuelve politicas publicas minimas para terminos, privacidad, cotizacion por WhatsApp, precios/disponibilidad, facturacion, cambios/devoluciones y cookies/tracking.

Si la tabla `erp_ecommerce_politicas` aun no existe, responde defaults seguros de Fase 1 con `configurado=false`. Esto permite que el frontend avance sin hardcodear reglas operativas. No registra aceptaciones ni escribe BD.

Slugs iniciales:

- `terminos-condiciones`
- `aviso-privacidad`
- `cotizacion-whatsapp`
- `precios-disponibilidad`
- `facturacion`
- `cambios-devoluciones`
- `cookies-tracking`

### Taxonomia mascotas

```http
GET /ecommercePublico/taxonomia_mascotas
```

Devuelve mascotas y necesidades para navegacion guiada. Si la tabla `erp_ecommerce_taxonomia_mascotas` aun no existe, responde defaults de Fase 1:

- mascotas: `perro`, `gato`, `pez`, `ave`, `reptil`, `roedor`, `otra`;
- necesidades: `alimento`, `premio`, `higiene`, `salud`, `paseo`, `habitat`, `juguete`, `estetica`.

No requiere cliente registrado ni mascotas guardadas.

### Disponibilidad

```http
GET /ecommercePublico/disponibilidad?id_sku=123
GET /ecommercePublico/disponibilidad?slug=producto-publico
```

Estados publicos permitidos:

- `disponible`
- `pocas_piezas`
- `consultar_disponibilidad`
- `agotado`

Nunca devuelve cantidad exacta.

La respuesta incluye `depurar.frontend` con:

- `estado`;
- `badge.label` y `badge.tono`;
- `mensaje`;
- `cta.label`, `cta.habilitado` y `cta.accion`;
- `mostrar_stock_exacto=false`;
- `precio_es_estimado=true`;
- `requiere_dryrun_antes_de_whatsapp=true`.

El frontend debe usar este bloque para pintar badges, botones y mensajes de ficha/tarjeta sin duplicar reglas operativas.

### Configuracion publica

```http
GET /ecommercePublico/configuracion
```

Devuelve solo claves publicables para el frontend:

- `moneda_default`
- `whatsapp_numero_principal`
- `whatsapp_mensaje_base`
- `cors_origenes_permitidos`
- `cotizacion_habilitada`
- `mostrar_stock_exacto`
- `modo_sin_stock`
- `texto_total_estimado`
- `url_sitio_publico`

Si `erp_ecommerce_configuracion` aun no existe, responde `configurado=false` con defaults seguros y sin numero WhatsApp hardcodeado.

### Canales/API estado

```http
GET /ecommercePublico/canales_estado
```

Devuelve el estado seguro de la futura capa multi-canal/API para Artiani y partners:

- tablas requeridas;
- canales configurados;
- credenciales activas como conteo, sin secretos;
- bloqueos de activacion;
- guardrails.

En Fase 1 puede responder `tipo=info` y `modo=multi_canal_diseno_readonly`. No genera credenciales, no activa autenticacion obligatoria y no expone secrets.

### SEO/descubrimiento

```http
GET /ecommercePublico/seo
```

Devuelve metadatos para que el frontend externo genere:

- title/description por defecto;
- robots sugerido;
- rutas para sitemap;
- rutas de productos publicados cuando existan;
- rutas navegables por filtros;
- `sitemap_xml_sugerido`;
- contrato JSON-LD base para `PetStore` y `Product`.

El ERP no renderiza `robots.txt` ni `sitemap.xml` en Fase 1; el frontend los genera usando este contrato.

### Cotizacion dry-run

```http
POST /ecommercePublico/cotizacion_dryrun
```

Valida y recalcula un carrito sin guardar nada.

Body sugerido:

```json
{
  "items": [
    {"id_publicacion": 1, "cantidad": 2},
    {"slug": "producto-publico", "cantidad": 1}
  ],
  "contacto": {
    "nombre": "Cliente",
    "telefono": "5555555555",
    "mensaje": "Quiero confirmar disponibilidad"
  },
  "utm": {}
}
```

Reglas:

- No acepta precio del frontend como verdad.
- Recalcula precio desde publicaciones vivas del ERP.
- Devuelve disponibilidad publica simple.
- No guarda cotizacion.
- No aparta ni descuenta inventario.
- No crea pedido, venta ni atencion POS.
- Si el esquema aun no existe, responde `configurado=false`.
- En estado amarillo, puede responder `configurado=false` antes de validar si `items` viene vacio.

`depurar.frontend` incluye:

- `estado`: `vacio`, `listo`, `observaciones` o `bloqueado`;
- `mensaje`;
- `puede_continuar_preflight`;
- `mostrar_total_estimado`;
- `mostrar_whatsapp_preview`;
- `total_estimado_texto`;
- `cta_principal.label`;
- `cta_principal.endpoint_siguiente=/ecommercePublico/cotizacion_preflight`;
- `guardrails_ui.no_usar_precio_local_como_total=true`.

### Cotizacion preflight

```http
POST /ecommercePublico/cotizacion_preflight
```

Valida carrito, contacto, consentimiento y WhatsApp antes de abrir la conversacion o preparar el registro futuro.

Body sugerido:

```json
{
  "items": [
    {"id_publicacion": 1, "cantidad": 2}
  ],
  "contacto": {
    "nombre": "Cliente",
    "telefono": "3322068429",
    "correo": "",
    "mensaje": "Quiero confirmar disponibilidad"
  },
  "acepta_contacto_whatsapp": true,
  "politicas_aceptadas": ["aviso-privacidad", "cotizacion-whatsapp"],
  "utm": {
    "source": "web"
  }
}
```

Reglas:

- Internamente ejecuta `cotizacion_dryrun`.
- No guarda cotizacion.
- Devuelve `folio_preliminar`, pero `folio_no_persistido=true`.
- Devuelve `listo_para_whatsapp`.
- Devuelve `listo_para_registro_futuro` para indicar si, cuando se active persistencia, el payload ya tiene contacto minimo.
- Devuelve `whatsapp.url` si el ERP tiene numero configurado.
- No aparta ni descuenta inventario.
- No crea pedido, venta ni prospecto real.

### Registro de cotizacion futuro

```http
POST /ecommercePublico/cotizacion_registrar
```

Estado Fase 1:

- Bloqueado por defecto.
- No escribe BD.
- No registra cotizacion real.
- No crea pedido, venta ni atencion POS.
- No aparta ni descuenta inventario.

Requisitos para activarlo:

- DDL `erp_ecommerce_*` aplicado con respaldo externo.
- API key/firma HMAC activa.
- CORS restringido al dominio real del ecommerce.
- Rate limit definido.
- Politica de contacto/seguimiento CRM definida.
- Numero WhatsApp configurado desde ERP.

Plan read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
```

### Facturacion solicitar preflight

```http
POST /ecommercePublico/facturacion_solicitar
```

Estado actual:

- Activo como preflight sin persistencia.
- Valida folio de compra, datos fiscales basicos, correo y aviso de privacidad.
- Devuelve `folio_solicitud_preliminar`, pero `folio_no_persistido=true`.
- No guarda datos fiscales.
- No emite factura.
- No crea cliente ni solicitud real.
- Devuelve `sql_plan` para la futura bandeja interna de facturacion.

### Evento navegacion preflight

```http
POST /ecommercePublico/evento_navegacion
```

Estado actual:

- Activo como preflight sin persistencia.
- Valida eventos anonimos: `page_view`, `select_mascota`, `select_necesidad`, `search`, `view_product`, `add_to_quote`, `open_whatsapp`, `facturacion_view`, `facturacion_submit`.
- Bloquea metadata con datos personales detectables como correo, telefono, RFC, nombre o razon social.
- No registra tracking real todavia.

### Busqueda registrar preflight

```http
POST /ecommercePublico/busqueda_registrar
```

Estado actual:

- Activo como preflight sin persistencia.
- Valida `session_id`, `query`, mascota/necesidad opcional y total de resultados.
- Devuelve `sin_resultados=true` cuando `resultados_total<=0`.
- Bloquea busquedas o filtros con datos personales detectables.
- No guarda historial real todavia.

Ese plan documenta el payload futuro, tablas destino, folio planeado, snapshot y bloqueos vigentes sin desbloquear escrituras.

Documento de flujo:

```text
docs/erp_ecommerce_publico_cotizaciones_flujo_registro_futuro.md
```

## Item de catalogo

Campos esperados:

- `id_publicacion`
- `id_producto_erp`
- `id_sku`
- `slug`
- `sku`
- `nombre`
- `marca`
- `categoria`
- `presentacion`
- `descripcion`
- `imagen`
- `precio`
- `moneda`
- `disponibilidad`
- `mascota_especie`
- `necesidades`
- `permite_cotizacion`
- `permite_whatsapp`

## Guardrails

- GET publicos read-only, `POST /cotizacion_dryrun` y `POST /cotizacion_preflight` sin persistencia.
- `POST /cotizacion_dryrun` existe solo para validacion sin persistencia.
- `POST /cotizacion_preflight` existe para validar datos antes de WhatsApp y preparar contrato futuro de registro.
- `POST /cotizacion_registrar` queda bloqueado hasta autorizar persistencia.
- Todas las respuestas incluyen metadatos `api.version`, `api.modo` y `api.fuente_verdad`.
- No usar `ecom_*` como fuente.
- No mostrar costos, proveedor, lotes, ubicaciones ni stock exacto.
- No crear checkout.
- No cobrar online.
- No descontar ni apartar inventario.
- No registrar cotizaciones hasta autorizar esquema y contrato POST.

## Seguridad antes de produccion

- CORS restringido al dominio del ecommerce externo.
- CORS queda cerrado por defecto si `cors_origenes_permitidos` esta vacio o no existe.
- Cuando el origen esta permitido, CORS acepta `GET`, `POST` y `OPTIONS` para soportar `cotizacion_dryrun`.
- Header de version: `X-ERP-Ecommerce-API-Version`.
- Header de modo: `X-ERP-Ecommerce-Mode`.
- API key o firma HMAC si el ecommerce estara en otro dominio publico.
- Rate limit para endpoints publicos.
- Captcha o proteccion equivalente antes de formularios POST.
- Logs de errores sin exponer SQL ni datos internos.

## Canales y partners

La API se debe preparar como multi-canal:

- `frontend_propio` para Artiani;
- `partner_mayoreo` para aliados autorizados;
- `integracion_entregas` como futura capa separada.

No entregar secretos para pegarlos en JavaScript publico. Para partners, las acciones sensibles deben firmarse desde backend con API key + HMAC.

Documento vivo:

```text
docs/erp_ecommerce_publico_api_canales_partners.md
```

Plan read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_partner_api_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --partner_origin=https://partner.example.com
```

Simulador de firma:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_hmac_contract_readonly.php
```

## Experiencia cliente

La API debe contemplar politicas publicas, solicitud de facturacion por folio, historial de busqueda/navegacion y taxonomia de mascotas.

Documento vivo:

```text
docs/erp_ecommerce_publico_experiencia_cliente_politicas_facturacion_analytics.md
```

Plan read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_plan_readonly.php
```

Prueba HTTP read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_http_readonly.php --base=http://panel.com.local
```

Senal esperada:

```text
senal_frontend_experiencia_http=verde_politicas_taxonomia_readonly
```

## UAT read-only

Script:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_api_contracts_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_openapi_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_carrito_whatsapp_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
```

Valida:

- manifiesto `/ecommercePublico/contratos`;
- shape minimo de wrappers `error/tipo/mensaje/api/depurar`;
- casos negativos controlados para metodos, parametros y slugs invalidos;
- endpoint de estado/readiness;
- configuracion publica;
- catalogo publico sin usar `ecom_*`;
- producto por slug no publicado devolviendo JSON controlado;
- disponibilidad sin cantidad exacta;
- cotizacion dry-run;
- registro real de cotizacion bloqueado;
- guardado interno de publicacion bloqueado.
- preflight CORS abierto solo para origenes exactos configurados; actualmente `http://artiani.com.local`.
- variables de entorno/proxy para el frontend externo.
- coleccion Postman/Insomnia para probar endpoints publicos y el POST bloqueado.

No escribe BD, no ejecuta DDL, no toca inventario y no registra cotizaciones.

## Fixtures para frontend

Como respaldo de UI si el ERP local no esta disponible, el frontend puede usar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

Incluye respuestas ejemplo para:

- `estado`
- `configuracion`
- `filtros`
- `catalogo`
- `producto`
- `disponibilidad`
- `cotizacion_dryrun`

Estos fixtures son solo para UI. No representan productos reales. En el estado actual el `green_gate` ya devuelve `ok=true`, por lo que el flujo principal del frontend debe consumir API real.

## Snapshot vivo para frontend

Para generar ejemplos reales actuales de catalogo, producto, disponibilidad y dry-run:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=2
```

Este snapshot es read-only y sirve como paquete de integracion para el proyecto ecommerce externo.

## OpenAPI basico

Para generar una especificacion OpenAPI 3.0.3 basica:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_openapi_readonly.php
```

La especificacion es de apoyo para mocks, docs o generadores de cliente. El contrato fuente sigue siendo el endpoint:

```http
GET /ecommercePublico/contratos
```

## Autenticacion futura de canal

Estado Fase 1:

- No requerida para endpoints GET read-only mientras el API esta en preparacion/local.
- Debe activarse antes de exponer POST publicos, cotizaciones reales o dominios publicos no controlados.

Modo recomendado:

- API key publica para identificar canal.
- Firma HMAC-SHA256 con secreto privado no expuesto.

Headers previstos:

- `X-Ecommerce-Api-Key`
- `X-Ecommerce-Timestamp`
- `X-Ecommerce-Nonce`
- `X-Ecommerce-Signature`

String canonico sugerido:

```text
HTTP_METHOD
REQUEST_PATH
QUERY_STRING_ORDENADO
X_ECOMMERCE_TIMESTAMP
X_ECOMMERCE_NONCE
SHA256_BODY_HEX
```

Reglas:

- No exponer secreto por `configuracion`.
- No loggear secreto ni firma completa.
- Rechazar timestamp fuera de tolerancia cuando se active.
- Registrar intentos fallidos sin bloquear operacion ERP interna.
- Mantener CORS restringido aunque exista firma.
