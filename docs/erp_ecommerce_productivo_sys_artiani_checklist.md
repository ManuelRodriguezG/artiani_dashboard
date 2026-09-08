# Ecommerce Publico - Checklist Productivo sys.artiani.com.mx

Documentacion IA: Codex GPT-5 | Fecha: 2026-09-07

## Objetivo

Preparar el cambio del API ecommerce publico cuando el ERP opere en:

```text
https://sys.artiani.com.mx
```

Frontend publico previsto:

```text
https://artiani.com.mx
```

## Estado de configuracion en codigo

`app/config/configuracion.php` ya contempla el host productivo:

```text
SERVER_NAME=sys.artiani.com.mx
RUTA_URL=https://sys.artiani.com.mx/
RUTA_RECURSOS=https://sys.artiani.com.mx/
RUTA_URL_FRONT=https://artiani.com.mx/
RUTA_RECURSOS_IMG=https://sys.artiani.com.mx/
```

Las banderas de escritura publica quedan protegidas:

```text
ECOMMERCE_ANALYTICS_TRACKING_PUBLICO
ECOMMERCE_LEADS_PUBLICO
```

Regla actual:

- En local quedan activas para pruebas.
- En dominios productivos quedan apagadas por defecto.
- Para activar escritura publica en productivo se requiere decision operativa explicita.

## Base API productiva esperada

Frontend debe consumir:

```text
https://sys.artiani.com.mx/ecommercePublico
```

No debe consumir:

```text
http://panel.com.local/ecommercePublico
```

## CORS productivo

El API solo responde CORS si `erp_ecommerce_configuracion.cors_origenes_permitidos` contiene el origen exacto.

Valor esperado para frontend productivo:

```text
https://artiani.com.mx
```

Si el frontend usa tambien `https://www.artiani.com.mx`, debe agregarse como origen separado:

```text
https://artiani.com.mx
https://www.artiani.com.mx
```

No usar:

```text
*
```

## Configuracion publica minima en BD

Antes de salida productiva, validar que existan valores publicos:

```text
whatsapp_numero_principal
whatsapp_mensaje_base
cors_origenes_permitidos
url_sitio_publico
moneda_default=MXN
mostrar_stock_exacto=0
cotizacion_habilitada=1
modo_sin_stock=consultar
```

Valor recomendado:

```text
url_sitio_publico=https://artiani.com.mx
cors_origenes_permitidos=https://artiani.com.mx
```

## Validacion read-only antes de aplicar configuracion

Local:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_configuracion_plan_readonly.php --whatsapp=NUMERO_WHATSAPP --cors=https://artiani.com.mx --url=https://artiani.com.mx --moneda=MXN
```

Productivo, cuando se ejecute en servidor:

```text
php storage/uat/uat_ecommerce_publico_configuracion_plan_readonly.php --whatsapp=NUMERO_WHATSAPP --cors=https://artiani.com.mx --url=https://artiani.com.mx --moneda=MXN
```

## Aplicar configuracion productiva

Solo cuando exista respaldo y autorizacion explicita:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=RUTA_O_REFERENCIA_RESPALDO --whatsapp=NUMERO_WHATSAPP --cors=https://artiani.com.mx --url=https://artiani.com.mx --moneda=MXN
```

En servidor productivo:

```text
php storage/uat/uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=RUTA_O_REFERENCIA_RESPALDO --whatsapp=NUMERO_WHATSAPP --cors=https://artiani.com.mx --url=https://artiani.com.mx --moneda=MXN
```

## Gate productivo frontend

Antes de liberar frontend, correr:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_productivo_gate_readonly.php --base=https://sys.artiani.com.mx --origin=https://artiani.com.mx --url=https://artiani.com.mx --min_publicadas=6
```

En servidor productivo:

```text
php storage/uat/uat_ecommerce_publico_frontend_productivo_gate_readonly.php --base=https://sys.artiani.com.mx --origin=https://artiani.com.mx --url=https://artiani.com.mx --min_publicadas=6
```

Debe regresar:

```text
ok=true
senal_productivo_frontend=verde_productivo_frontend_basico
```

## Endpoints publicos a validar

```text
GET https://sys.artiani.com.mx/ecommercePublico/estado
GET https://sys.artiani.com.mx/ecommercePublico/configuracion_inicial
GET https://sys.artiani.com.mx/ecommercePublico/configuracion
GET https://sys.artiani.com.mx/ecommercePublico/catalogo?limite=24
GET https://sys.artiani.com.mx/ecommercePublico/categorias
GET https://sys.artiani.com.mx/ecommercePublico/marcas
GET https://sys.artiani.com.mx/ecommercePublico/filtros
GET https://sys.artiani.com.mx/ecommercePublico/navegacion
GET https://sys.artiani.com.mx/ecommercePublico/contenido_pagina?pagina=home
GET https://sys.artiani.com.mx/ecommercePublico/analytics_contrato
```

POST a validar:

```text
POST https://sys.artiani.com.mx/ecommercePublico/cotizacion_dryrun
POST https://sys.artiani.com.mx/ecommercePublico/cotizacion_preflight
POST https://sys.artiani.com.mx/ecommercePublico/analytics_sesion
POST https://sys.artiani.com.mx/ecommercePublico/evento_navegacion
POST https://sys.artiani.com.mx/ecommercePublico/busqueda_registrar
POST https://sys.artiani.com.mx/ecommercePublico/analytics_conversion
```

Si `ECOMMERCE_LEADS_PUBLICO` se activa en productivo:

```text
POST https://sys.artiani.com.mx/ecommercePublico/carrito_sincronizar
POST https://sys.artiani.com.mx/ecommercePublico/carrito_evento
POST https://sys.artiani.com.mx/ecommercePublico/intento_pedido
POST https://sys.artiani.com.mx/ecommercePublico/contacto_registrar
POST https://sys.artiani.com.mx/ecommercePublico/facturacion_solicitud_registrar
```

## Reglas de salida

- No exponer credenciales.
- No usar CORS wildcard.
- No mostrar stock exacto.
- No publicar productos a granel.
- No aceptar productos sin precio activo.
- No activar pedidos/pagos online en esta fase.
- No descontar inventario desde ecommerce publico.
- No activar analytics/leads productivo sin tablas y decision operativa.
- Frontend debe usar `https://sys.artiani.com.mx/ecommercePublico` como base API productiva.

## Pendientes antes de liberar

- Confirmar dominio final del frontend:
  - `https://artiani.com.mx`;
  - o tambien `https://www.artiani.com.mx`.
- Confirmar numero WhatsApp productivo.
- Confirmar respaldo antes de aplicar configuracion.
- Ejecutar gate productivo.
- Probar preflight CORS desde el origen real.
- Validar que CMS publicado entregue imagenes y banners reales.
- Validar que marcas/categorias lleguen con slugs e imagenes cuando existan.
- Validar cotizacion dryrun/preflight con productos reales publicados.
