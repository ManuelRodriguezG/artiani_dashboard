# ERP Ecommerce publico - Contratos API Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-12  
Estado: contrato read-only para frontend ecommerce externo.

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
- `cotizacion_habilitada`
- `mostrar_stock_exacto`
- `modo_sin_stock`
- `texto_total_estimado`
- `url_sitio_publico`

Si `erp_ecommerce_configuracion` aun no existe, responde `configurado=false` con defaults seguros y sin numero WhatsApp hardcodeado.

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

- Solo GET read-only en esta etapa.
- Todas las respuestas incluyen metadatos `api.version`, `api.modo` y `api.fuente_verdad`.
- No usar `ecom_*` como fuente.
- No mostrar costos, proveedor, lotes, ubicaciones ni stock exacto.
- No crear checkout.
- No cobrar online.
- No descontar ni apartar inventario.
- No registrar cotizaciones hasta autorizar esquema y contrato POST.

## Seguridad antes de produccion

- CORS restringido al dominio del ecommerce externo.
- API key o firma HMAC si el ecommerce estara en otro dominio publico.
- Rate limit para endpoints publicos.
- Captcha o proteccion equivalente antes de formularios POST.
- Logs de errores sin exponer SQL ni datos internos.
