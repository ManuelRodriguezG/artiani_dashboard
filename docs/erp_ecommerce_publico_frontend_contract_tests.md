# ERP Ecommerce publico - Contract tests para frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: guia para pruebas del proyecto ecommerce externo.

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
- `depurar.json_ld` existe.

El frontend genera `robots.txt`, `sitemap.xml`, meta tags y JSON-LD usando este contrato. Si `url_sitio_publico`/`canonical_base` viene vacio, no inventar canonical definitivo.

### Catalogo

Validar:

- `depurar.items` es array;
- `depurar.paginacion` existe cuando `configurado=true`;
- si `configurado=false`, UI muestra catalogo en preparacion.

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

### Cotizacion dry-run

Validar:

- `depurar.dry_run=true`;
- `depurar.no_escribe_bd=true`;
- `depurar.no_descuenta_inventario=true`;
- `depurar.lineas` es array;
- `depurar.totales.moneda` existe;
- `depurar.whatsapp_preview` existe cuando hay lineas.

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
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://localhost:5173
```

Nota CORS:

- `OPTIONS` no requiere body JSON.
- En estado actual debe reportar `cors_abierto_para_origin=false`.
- Cuando se configure `cors_origenes_permitidos`, debe abrir solo el origen exacto del frontend.

## Fixtures

Para pruebas visuales mientras no haya datos reales:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

Usar fixtures solo con:

```text
senal_frontend=amarillo_mock_contratos
```
