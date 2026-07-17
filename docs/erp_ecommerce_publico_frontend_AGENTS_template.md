# AGENTS.md - Frontend ecommerce publico mascotas

Proyecto: frontend publico separado del ERP para catalogo vivo de mascotas.  
ERP fuente: `C:\xampp\htdocs\panel_de_control`  
Base API local: `http://panel.com.local/ecommercePublico`

## Objetivo Fase 1

Construir una experiencia publica de catalogo para mascotas:

- catalogo de productos publicados desde ERP;
- filtros por mascota y necesidad;
- buscador rapido;
- ficha de producto;
- carrito local tipo cotizacion;
- validacion con `POST /ecommercePublico/cotizacion_dryrun`;
- envio por WhatsApp;
- sin checkout, sin pagos online, sin pedido confirmado y sin descuento de inventario.

## Fuente de verdad

El ERP es la fuente de verdad. El frontend no debe leer BD, tablas internas ni `ecom_*`.

Usar solo endpoints publicos:

- `GET /ecommercePublico/contratos`
- `GET /ecommercePublico/estado`
- `GET /ecommercePublico/configuracion`
- `GET /ecommercePublico/seo`
- `GET /ecommercePublico/filtros`
- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`
- `GET /ecommercePublico/disponibilidad`
- `POST /ecommercePublico/cotizacion_dryrun`

No usar `POST /ecommercePublico/cotizacion_registrar` en Fase 1.

## Variables sugeridas

```env
ERP_API_BASE_URL=http://panel.com.local
ERP_ECOMMERCE_BASE_PATH=/ecommercePublico
ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12
```

No guardar secretos en variables publicas del navegador.

## Estados de integracion

- `amarillo_mock_contratos`: construir UI con fixtures y cliente API, no prometer datos reales.
- `verde_datos_reales`: usar API real cuando el ERP `green gate` devuelva `ok=true`.

Comando ERP:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

## Reglas UX

- No hacer landing generica; la primera pantalla debe ser catalogo usable.
- Disenar para compradores de productos de mascotas, no una tienda generica.
- Mostrar disponibilidad simple: `disponible`, `pocas_piezas`, `consultar_disponibilidad`, `agotado`.
- Nunca mostrar stock exacto.
- El carrito debe recalcularse con dry-run antes de enviar WhatsApp.
- Si el catalogo real esta vacio, mostrar estado de preparacion.
- Usar fixtures solo mientras el ERP este en amarillo.

## Validaciones minimas

Antes de conectar datos reales:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local
```

## Documentos fuente ERP

- `docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt`
- `docs/erp_ecommerce_publico_prompt_inicio_frontend.txt`
- `docs/erp_ecommerce_publico_frontend_handoff.md`
- `docs/erp_ecommerce_publico_api_contratos.md`
- `docs/erp_ecommerce_publico_cliente_api_frontend.md`
- `docs/erp_ecommerce_publico_frontend_estados_ui.md`
- `docs/erp_ecommerce_publico_fixtures_frontend.md`
- `docs/erp_ecommerce_publico_carrito_whatsapp_frontend.md`
- `docs/erp_ecommerce_publico_seo_frontend.md`
