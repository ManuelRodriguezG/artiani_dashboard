# ERP Ecommerce publico - Contratos API Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: contrato read-only para frontend ecommerce externo; API publica Fase 1 cubierta por smoke HTTP.

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

La API publica se mantiene en contratos separados. No se agrega endpoint `bootstrap` en esta fase para evitar acoplar el primer render del frontend a un payload combinado.

Total actual: 9 endpoints publicos.

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

### Catalogo

```http
GET /ecommercePublico/catalogo?q=&mascota=&necesidad=&marca=&categoria=&pagina=1&limite=24
```

Devuelve solo publicaciones con `estatus_publicacion='publicado'`. Si el esquema aun no existe, responde `configurado=false` e `items=[]`.

### Producto

```http
GET /ecommercePublico/producto/{slug}
```

Devuelve una sola publicacion publicada. No muestra productos pausados, borradores ni SKUs inactivos.

### Filtros

```http
GET /ecommercePublico/filtros
```

Devuelve mascotas, necesidades, marcas y categorias derivadas de publicaciones vigentes.

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

### SEO/descubrimiento

```http
GET /ecommercePublico/seo
```

Devuelve metadatos para que el frontend externo genere:

- title/description por defecto;
- robots sugerido;
- rutas para sitemap;
- rutas de productos publicados cuando existan;
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

- GET publicos read-only y `POST /cotizacion_dryrun` sin persistencia.
- `POST /cotizacion_dryrun` existe solo para validacion sin persistencia.
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

## UAT read-only

Script:

```bash
php storage/uat/uat_ecommerce_publico_api_contracts_readonly.php
php storage/uat/uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
php storage/uat/uat_ecommerce_publico_contract_shape_readonly.php
php storage/uat/uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local
php storage/uat/uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://localhost:5173
php storage/uat/uat_ecommerce_publico_frontend_fixtures_readonly.php
php storage/uat/uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://localhost:5173
php storage/uat/uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local
php storage/uat/uat_ecommerce_publico_openapi_readonly.php
php storage/uat/uat_ecommerce_publico_carrito_whatsapp_readonly.php
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
- preflight CORS cerrado hasta configurar `cors_origenes_permitidos`.
- variables de entorno/proxy para el frontend externo.
- coleccion Postman/Insomnia para probar los 9 endpoints publicos y el POST bloqueado.

No escribe BD, no ejecuta DDL, no toca inventario y no registra cotizaciones.

## Fixtures para frontend

Mientras `senal_frontend=amarillo_mock_contratos`, el frontend puede usar:

```bash
php storage/uat/uat_ecommerce_publico_frontend_fixtures_readonly.php
```

Incluye respuestas ejemplo para:

- `estado`
- `configuracion`
- `filtros`
- `catalogo`
- `producto`
- `disponibilidad`
- `cotizacion_dryrun`

Estos fixtures son solo para UI. No representan productos reales y deben retirarse cuando `uat_ecommerce_publico_green_gate_readonly.php` devuelva `ok=true`.

## OpenAPI basico

Para generar una especificacion OpenAPI 3.0.3 basica:

```bash
php storage/uat/uat_ecommerce_publico_openapi_readonly.php
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
