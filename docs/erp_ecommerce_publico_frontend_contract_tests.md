# ERP Ecommerce publico - Contract tests para frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-01  
Estado: guia para pruebas del proyecto ecommerce externo con contrato robusto Fase 1.

## Objetivo

El frontend debe probar que consume el contrato real sin asumir BD, tablas internas ni checkout.

## Configuracion

Variables esperadas:

```env
VITE_ERP_API_BASE_URL=http://panel.com.local
VITE_ERP_ECOMMERCE_BASE_PATH=/ecommercePublico
VITE_ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12
```

## Assertions minimas

### Wrapper comun

Todas las respuestas deben tener:

- `error`
- `tipo`
- `mensaje`
- `api.version`
- `api.modo`
- `api.fuente_verdad`
- `depurar`

### Estado

Validar:

- `depurar.ready` existe;
- `depurar.schema.ddl_pendiente` existe;
- `depurar.publicaciones.total_publicadas` existe;
- `depurar.seguridad.post_dryrun_disponible=true`;
- `depurar.seguridad.post_registro_bloqueado=true`.

### Frontend handoff

Validar:

- `GET /ecommercePublico/frontend_handoff?limite=2` responde wrapper comun;
- `depurar.estado_actual.senal_frontend` existe;
- `depurar.variables_env_frontend.VITE_ERP_API_BASE_URL` existe;
- `depurar.variables_env_frontend.VITE_ERP_ECOMMERCE_BASE_PATH === "/ecommercePublico"`;
- `depurar.endpoints_para_consumir` tiene al menos 20 entradas;
- `depurar.pruebas_con_api` tiene ejemplos HTTP;
- `depurar.contratos_ui` existe;
- `depurar.guardrails.no_requiere_filesystem === true`.

El proyecto frontend externo no debe abrir `docs/*.md` ni navegar archivos de `panel_de_control` para conocer el estado de la API.

### Bootstrap

Validar:

- `depurar.estado` existe;
- `depurar.configuracion` existe;
- `depurar.filtros` existe;
- `depurar.navegacion` existe;
- `depurar.secciones` existe;
- `depurar.politicas` existe;
- `depurar.canales` existe;
- `depurar.guardrails.no_expone_secretos=true`.

El frontend puede usar `bootstrap` para la carga inicial, pero debe poder refrescar catalogo, filtros, navegacion y secciones desde endpoints separados.

### Configuracion

Validar:

- `depurar.configuracion.moneda_default`;
- `depurar.configuracion.whatsapp_numero_principal`;
- `depurar.configuracion.whatsapp_mensaje_base`;
- `depurar.configuracion.mostrar_stock_exacto === "0"`.

No hardcodear WhatsApp si viene vacio.

### SEO

Validar:

- `depurar.meta.title_default`;
- `depurar.meta.description_default`;
- `depurar.robots.robots_txt_sugerido`;
- `depurar.sitemap.rutas_estaticas` es array;
- `depurar.rutas` es array;
- `depurar.sitemap_xml_sugerido` es string;
- `depurar.resumen` existe;
- `depurar.json_ld` existe.

El frontend genera `robots.txt`, `sitemap.xml`, meta tags y JSON-LD usando este contrato. Si `url_sitio_publico`/`canonical_base` viene vacio, no inventar canonical definitivo.

### Catalogo

Validar:

- `depurar.items` es array;
- `depurar.paginacion` existe cuando `configurado=true`;
- `depurar.filtros_aplicados` existe;
- `depurar.ordenamientos_disponibles` es array;
- `depurar.frontend.hay_resultados` existe;
- `depurar.frontend.rango_visible.texto` existe;
- `depurar.frontend.guardrails_ui.cotizacion_requiere_dryrun=true`;
- si `configurado=false`, UI muestra catalogo en preparacion.

Probar al menos:

- `GET /catalogo?limite=3`;
- `GET /catalogo?disponibilidad=disponible&orden=precio_asc&limite=3`;
- `GET /catalogo?destacado=1&limite=3`.
- `GET /catalogo?q=__sin_resultados_catalogo_frontend__&limite=3` debe activar `depurar.frontend.estado_vacio.mostrar=true`.

### Detalle de producto

El frontend debe probar un producto real usando un slug obtenido desde `GET /catalogo?limite=3`.

Validar en `GET /producto/{slug}`:

- `depurar.item` existe;
- `depurar.breadcrumbs` tiene al menos 2 elementos;
- `depurar.relacionados` existe como array;
- `depurar.seo.title` no viene vacio;
- `depurar.acciones.puede_cotizar=true`;
- `depurar.guardrails.no_mostrar_stock_exacto=true`.

### Filtros, navegacion y secciones

Validar:

- `GET /filtros` devuelve `mascotas`, `necesidades`, `marcas`, `categorias` y `disponibilidad`;
- `GET /navegacion` devuelve `primaria`, `mascotas`, `necesidades`, `categorias`, `marcas` y `disponibilidad`;
- `GET /secciones` devuelve `secciones` como array;
- ninguna respuesta muestra stock exacto.

### Busqueda sugerencias

Validar:

- `GET /busqueda_sugerencias?q=filtro&limite=4` devuelve `depurar.grupos`;
- existen grupos `productos`, `marcas`, `categorias`, `mascotas` y `necesidades`;
- no asumir que la busqueda queda registrada.

### Canales/API

Validar:

- `GET /canales_estado` devuelve `modo`;
- devuelve `tablas`, `canales`, `autenticacion`, `activacion` y `guardrails`;
- `depurar.guardrails.no_expone_api_secret=true`;
- no intentar autenticar con API key/HMAC en Fase 1 read-only.

### Item

Cada item debe soportar:

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

### Disponibilidad

Solo permitir:

- `disponible`
- `pocas_piezas`
- `consultar_disponibilidad`
- `agotado`

Nunca renderizar cantidad exacta.

Validar tambien:

- `depurar.frontend.estado` existe;
- `depurar.frontend.badge.label` existe;
- `depurar.frontend.cta.label` existe;
- `depurar.frontend.mostrar_stock_exacto=false`;
- `depurar.frontend.requiere_dryrun_antes_de_whatsapp=true`.

### Cotizacion dry-run

Validar:

- `depurar.dry_run=true`;
- `depurar.no_escribe_bd=true`;
- `depurar.no_descuenta_inventario=true`;
- `depurar.lineas` es array;
- `depurar.totales.moneda` existe;
- `depurar.whatsapp_preview` existe cuando hay lineas.
- `depurar.frontend.estado` existe;
- `depurar.frontend.puede_continuar_preflight=true` con payload valido;
- `depurar.frontend.cta_principal.endpoint_siguiente="/ecommercePublico/cotizacion_preflight"`;
- `depurar.frontend.guardrails_ui.no_usar_precio_local_como_total=true`.

### Registro real

`POST /cotizacion_registrar` no debe usarse en Fase 1.

Si se prueba, debe responder:

- `error=true`;
- `depurar.bloqueado=true`;
- `depurar.no_escribe_bd=true`.

## Pruebas ERP fuente

Desde el ERP:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_bootstrap_readonly.php --base=http://panel.com.local --limite=3
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_navegacion_readonly.php --base=http://panel.com.local --limite=5
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_busqueda_sugerencias_readonly.php --base=http://panel.com.local --q=filtro --limite=4
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_seo_robusto_readonly.php --base=http://panel.com.local --limite=20
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_estado_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_catalogo_robusto_readonly.php --base=http://panel.com.local --limite=3
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
```

Nota CORS:

- `OPTIONS` no requiere body JSON.
- En estado actual debe reportar `cors_abierto_para_origin=true` para `http://artiani.com.local`.
- Debe mantener `cors_sin_wildcard=true`.
- Para origenes no configurados, el navegador debe quedar bloqueado por CORS.

## Fixtures

Para pruebas visuales mientras no haya datos reales:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

Usar fixtures solo con:

```text
senal_frontend=amarillo_mock_contratos
```

## Prueba post-expansion a 6 productos

Cuando se autorice y publique la expansion, validar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --min_publicaciones=6
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=6 --min_publicaciones=6
```

Ambos deben devolver `ok=true` antes de considerar listo el frontend con el catalogo ampliado.
