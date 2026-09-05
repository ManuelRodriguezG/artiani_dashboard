# ERP Ecommerce Leads / Carritos - Plan e implementacion inicial

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-30  
Estado: contrato, DDL y persistencia publica activados con respaldo productivo previo.

## Objetivo

Capturar intencion comercial del ecommerce publico Artiani sin crear pedidos reales, ventas ni movimientos de inventario.

El modulo cubre:

- carritos armados en frontend;
- intentos de pedido;
- cotizaciones dry-run/preflight;
- aperturas de WhatsApp;
- contactos desde ecommerce;
- solicitudes de facturacion;
- posibles abandonos;
- historial comercial de productos que el cliente intento comprar.

## Diferencia con Analytics

Analytics guarda comportamiento anonimo agregado o semi-crudo:

- `page_view`;
- `view_product`;
- `search`;
- `add_to_quote`;
- `open_whatsapp`.

Ecommerce Leads guarda intencion comercial:

- carrito actual;
- productos, cantidades y precio snapshot;
- datos de contacto solo si el cliente los escribio explicitamente;
- mensaje generado para WhatsApp;
- etapa donde se quedo;
- seguimiento interno.

Ambos se conectan por `session_id`, pero en BD se guarda hash irreversible. Analytics usa su propio hash y Leads usa hash separado para evitar cruces accidentales fuera del backend.

## Estado actual observado

- `EcommercePublico` expone catalogo publico real, politicas, CMS, SEO y disponibilidad sin stock exacto.
- `cotizacion_dryrun` recalcula carrito contra publicaciones vivas y no escribe BD.
- `cotizacion_preflight` valida contacto/WhatsApp y genera folio preliminar no persistido.
- `EcommerceAnalyticsErp` ya separa tracking anonimo y bloquea PII.
- `EcommercePublico` no esta protegido globalmente por `Core`; los endpoints internos se protegen por metodo con `requerirPermiso("catalogo.ver")` o `catalogo.editar`.
- Existen `Prospectos` y `Carritos` legacy/CRM, pero no deben ser base del nuevo modulo.
- Activacion 2026-08-31: DDL aplicado contra `artianicom_sys`, `ECOMMERCE_LEADS_PUBLICO=true`, UAT HTTP `verde_persistencia_publica_leads`.
- Respaldo usado: `C:\xampp\panel_db_backups\artianicom_sys_panel_20260831_140027_antes_ecommerce_leads.sql`.
- Actualizacion 2026-09-05: la vista interna de Leads incluye seccion `Productos agregados por sesion`, alimentada por `GET /ecommercePublico/productos_leads_erp`.
- Esta seccion permite filtrar por producto/SKU/contacto/session hash y por `validacion_publicacion`, sin crear pedidos ni ventas.

## Endpoints implementados

Publicos:

```http
GET  /ecommercePublico/leads_contrato
POST /ecommercePublico/carrito_sincronizar
POST /ecommercePublico/carrito_evento
POST /ecommercePublico/intento_pedido
POST /ecommercePublico/contacto_registrar
POST /ecommercePublico/facturacion_solicitud_registrar
```

Internos:

```http
GET  /ecommercePublico/leads
GET  /ecommercePublico/carritos_dashboard_erp
GET  /ecommercePublico/carrito_detalle_erp/{id}
GET  /ecommercePublico/productos_leads_erp
POST /ecommercePublico/carrito_accion_plan_erp
GET  /ecommercePublico/esquema_auditar_leads
GET  /ecommercePublico/esquema_plan_leads
```

## Esquema propuesto

Tablas:

- `erp_ecommerce_leads_carritos`: encabezado por `session_id_hash` + canal, contacto explicito, etapa, estatus, totales y WhatsApp.
- `erp_ecommerce_leads_carrito_items`: snapshot actual de productos, cantidades, precio enviado/validado y validacion futura de publicacion.
- `erp_ecommerce_leads_eventos`: historial de eventos comerciales por carrito/session.
- `erp_ecommerce_leads_notas`: notas internas de seguimiento.

Estados:

```text
anonimo_activo
contacto_pendiente
whatsapp_generado
whatsapp_abierto
abandonado
en_seguimiento
convertido
descartado
```

## Guardrails

- No crear pedido real automaticamente.
- No registrar venta.
- No descontar inventario.
- No sustituir analytics.
- No usar legacy `ecom_*` como fuente.
- No guardar stock exacto para frontend publico.
- Guardar snapshot comercial del carrito para seguimiento interno cuando se active persistencia.
- Conservar precio snapshot si el precio cambia despues.
- Validar items contra `erp_ecommerce_publicaciones`, `erp_catalogo_skus` y `erp_catalogo_productos` cuando el catalogo este disponible.
- Guardar `validacion_publicacion` por item como snapshot: `publicacion_vigente`, `publicacion_no_publicada`, `sku_sin_publicacion`, `producto_inactivo`, `sku_inactivo`, `identificadores_inconsistentes`, `no_encontrado`, `sin_identificador`, `validacion_no_disponible` o `validacion_error`.
- No guardar datos personales si el cliente no los escribio explicitamente.
- Si solo existe `session_id`, guardar como carrito anonimo.
- Si despues deja telefono/contacto, enlazar el carrito por `session_id_hash`.

## Orden recomendado de implementacion siguiente

1. Ejecutar auditoria read-only de esquema con `/ecommercePublico/esquema_auditar_leads`.
2. Revisar plan DDL con `/ecommercePublico/esquema_plan_leads`.
3. Preparar UAT read-only HTTP para payloads: carrito anonimo, WhatsApp con mensaje vacio, contacto, facturacion y PII fuera de `contacto`.
4. Definir permiso fino futuro recomendado: `ecommerce.leads.ver` y `ecommerce.leads.operar`; mientras tanto se usa `catalogo.ver` para lectura interna.
5. Preparar limpieza/etiquetado de registros UAT si operacion lo solicita.

## Seccion copiable para frontend

Contexto:

El ecommerce Artiani todavia no crea pedidos reales ni descuenta inventario desde frontend. Debe enviar dos tipos de informacion al ERP:

- Analytics anonimo: comportamiento general.
- Ecommerce Leads: intencion comercial y snapshot del carrito.

Regla base:

Frontend debe generar un `session_id` anonimo estable en `localStorage` y enviarlo en analytics, carrito, cotizacion, contacto y facturacion.

No enviar datos personales en analytics. En Leads, enviar datos personales solo dentro del objeto `contacto` cuando el cliente los escribio explicitamente.

Base API local:

```text
http://panel.com.local
```

Contrato:

```http
GET /ecommercePublico/leads_contrato
```

Endpoints de Leads:

```http
POST /ecommercePublico/carrito_sincronizar
POST /ecommercePublico/carrito_evento
POST /ecommercePublico/intento_pedido
POST /ecommercePublico/contacto_registrar
POST /ecommercePublico/facturacion_solicitud_registrar
```

Cuándo llamar:

- `carrito_sincronizar`: al agregar producto, eliminar producto, cambiar cantidad, cargar `/carrito`, o antes de cotizacion dry-run/preflight.
- `carrito_evento`: para eventos puntuales como `cart_view`, `quantity_change`, `quote_dryrun`, `quote_preflight`, `abandono_estimado`.
- `intento_pedido`: justo antes de abrir WhatsApp o generar intento de pedido.
- `contacto_registrar`: cuando el cliente escribe nombre, telefono, correo o mensaje en un formulario.
- `facturacion_solicitud_registrar`: cuando el cliente solicita facturacion desde ecommerce.

Estados/eventos esperados:

```text
cart_update
cart_view
quantity_change
quote_dryrun
quote_preflight
open_whatsapp
contact_submit
facturacion_submit
abandono_estimado
```

Payload minimo de carrito anonimo:

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "ruta": "/carrito",
  "estado_carrito": "activo",
  "items": [
    {
      "id_publicacion": 123,
      "id_sku": 456,
      "slug": "alimento-x",
      "sku": "ABC-123",
      "nombre": "Producto ejemplo",
      "cantidad": 2,
      "precio_unitario": 150.00,
      "subtotal": 300.00
    }
  ],
  "totales": {
    "items_total": 1,
    "piezas_total": 2,
    "subtotal": 300.00
  },
  "metadata": {
    "origen": "cart_update",
    "frontend_version": "v1"
  }
}
```

Identificadores de producto:

- Enviar `id_publicacion` siempre que venga del `GET /ecommercePublico/catalogo`.
- Enviar tambien `slug` e `id_sku` si el frontend los tiene en el item.
- El ERP valida cada renglon contra publicaciones vivas. En preflight respondera `lead_normalizado.validacion_items` y cada item traera `validacion_publicacion`.
- Para seguimiento comercial, considerar ideal `publicacion_vigente`. Otros estados no impiden el preflight, pero deben revisarse antes de prometer disponibilidad/precio.

Payload para WhatsApp/intento:

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "ruta": "/carrito",
  "tipo_intento": "open_whatsapp",
  "contacto": {
    "nombre": "Cliente",
    "telefono": "3322068429",
    "mensaje": "",
    "acepta_whatsapp": true,
    "acepta_politicas": true
  },
  "whatsapp": {
    "mensaje_generado": "Hola, quiero cotizar estos productos...",
    "url_generada": "https://wa.me/523322068429?text=...",
    "abierto": true
  },
  "items": [
    {
      "id_publicacion": 123,
      "id_sku": 456,
      "slug": "alimento-x",
      "cantidad": 2,
      "precio_unitario": 150.00
    }
  ],
  "totales": {
    "items_total": 1,
    "piezas_total": 2,
    "subtotal": 300.00
  }
}
```

Ejemplo JS minimo:

```js
const API_BASE = "http://panel.com.local";

function getSessionId() {
  const key = "artiani_session_id";
  let value = localStorage.getItem(key);
  if (!value) {
    value = crypto.randomUUID();
    localStorage.setItem(key, value);
  }
  return value;
}

async function postLead(path, payload) {
  const response = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      session_id: getSessionId(),
      canal: "web_publica",
      ruta: window.location.pathname,
      ...payload
    })
  });
  return response.json();
}

export function syncCartLead(cart) {
  return postLead("/ecommercePublico/carrito_sincronizar", {
    estado_carrito: "activo",
    items: cart.items,
    totales: cart.totales,
    metadata: { origen: "cart_update", frontend_version: "v1" }
  });
}

export function trackWhatsappLead(cart, contacto, whatsapp) {
  return postLead("/ecommercePublico/intento_pedido", {
    tipo_intento: "open_whatsapp",
    contacto,
    whatsapp,
    items: cart.items,
    totales: cart.totales
  });
}
```

Respuesta esperada en esta fase:

```json
{
  "error": false,
  "tipo": "warning",
  "mensaje": "Lead ecommerce validado sin guardar",
  "depurar": {
    "preflight": true,
    "no_escribe_bd": true,
    "lead_normalizado": {},
    "bloqueos": [
      "persistencia_leads_no_activa"
    ]
  }
}
```

Mientras no se active persistencia, frontend puede integrar y revisar shape, pero el panel aun no vera carritos reales.

## Activacion backend para registro real

Estado actual:

- `ECOMMERCE_LEADS_PUBLICO=false`.
- El codigo de persistencia real ya existe, pero no se ejecuta si faltan tablas o si la bandera no esta activa.
- La activacion requiere respaldo externo y autorizacion explicita.
- Vista interna preparada: `/ecommercePublico/leads`.

UATs disponibles:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_leads_plan_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_leads_http_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_leads_schema_postcheck_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_leads_persistencia_publica_http.php --base=http://panel.com.local
```

Apply DDL autorizado:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_leads_schema_apply_authorized.php --autorizar=ECOMMERCE_LEADS_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

Secuencia para registrar de verdad:

1. Generar respaldo externo de BD en `C:\xampp\panel_db_backups`.
2. Ejecutar apply autorizado de DDL con token `ECOMMERCE_LEADS_DDL`.
3. Correr `uat_ecommerce_leads_schema_postcheck_readonly.php`.
4. Cambiar `ECOMMERCE_LEADS_PUBLICO` a `true`.
5. Correr `uat_ecommerce_leads_persistencia_publica_http.php`.
6. Probar dashboard interno `/ecommercePublico/carritos_dashboard_erp`.

## Handoff / continuidad

Fecha: 2026-08-30

- Contexto actual: modulo nuevo creado como `EcommerceLeadsErp` y `EcommerceLeadsEsquema`, conectado desde `EcommercePublico`.
- Cambios recientes: endpoints publicos e internos agregados; persistencia real transaccional queda preparada detras de `ECOMMERCE_LEADS_PUBLICO=true`; vista interna `/ecommercePublico/leads` agregada; no se aplico DDL.
- Decisiones: Leads acepta PII solo en bloque `contacto`; analytics sigue rechazando PII siempre.
- Pendientes: persistencia transaccional real, UATs, vista interna, permisos finos y posible enlace manual futuro con CRM/cotizacion/pedido.
- Impacta a: Ecommerce publico, CRM futuro, Ventas/Pedidos futuros, Facturacion operativa.
- Siguiente paso recomendado: crear UAT read-only para validar contratos antes de autorizar DDL.
