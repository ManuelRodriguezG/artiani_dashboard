# Ecommerce Analytics - Handoff Frontend

Documentacion IA: Codex GPT-5 | Fecha: 2026-08-31

## Estado

El frontend debe integrar analytics desde la primera version publica usando los endpoints de `http://panel.com.local/ecommercePublico`.

Los endpoints pueden trabajar en dos modos con el mismo contrato:

- `valida_sin_guardar`: modo preflight; valida payloads y no escribe BD.
- `registra_bd`: modo persistencia real; registra sesion, eventos, busquedas y conversiones anonimas.

Analytics v1 ya esta activo en modo `registra_bd` para el backend configurado en `panel.com.local`. Frontend no cambia endpoints entre modos.

Nota operativa: `panel.com.local` apunta a la base productiva efectiva del backend. Cualquier prueba desde frontend con payload valido registrara filas reales anonimas en `erp_ecommerce_analytics_*`.

## Endpoint de contrato

Consultar antes de integrar o para diagnostico:

```text
GET /ecommercePublico/analytics_contrato
```

Campos importantes de respuesta:

```text
depurar.estado
depurar.persistencia.activa
depurar.persistencia.modo_actual
depurar.eventos_permitidos
depurar.datos_permitidos
depurar.datos_prohibidos
```

## Regla de cliente anonimo

Frontend debe generar un `session_id` anonimo y persistente en `localStorage`.

Recomendado:

```js
const KEY = "artiani_ecommerce_session_id";
let sessionId = localStorage.getItem(KEY);
if (!sessionId) {
  sessionId = crypto.randomUUID();
  localStorage.setItem(KEY, sessionId);
}
```

Ese `session_id` se manda en todos los eventos.

## SDK publico opcional

Se deja un helper vanilla para acelerar la integracion del frontend externo:

```html
<script src="http://panel.com.local/assets/js/custom/apps/ecommerce/analytics-tracker-publico.js?v=20260831"></script>
<script>
  window.ArtianiEcommerceAnalytics.init({
    endpointBase: "http://panel.com.local",
    canal: "web_publica",
    autoSession: true,
    autoPageView: true
  });
</script>
```

Uso recomendado:

```js
window.ArtianiEcommerceAnalytics.viewProduct({
  id_publicacion: producto.id_publicacion,
  id_sku: producto.id_sku,
  slug: producto.slug,
  metadata: {
    categoria_slug: producto.categoria_slug,
    marca_slug: producto.marca_slug
  }
});

window.ArtianiEcommerceAnalytics.search({
  query: termino,
  resultados_total: resultados.length,
  sin_resultados: resultados.length === 0,
  filtros: filtrosPublicos
});

window.ArtianiEcommerceAnalytics.conversion("add_to_quote", {
  id_publicacion: producto.id_publicacion,
  id_sku: producto.id_sku,
  slug: producto.slug,
  metadata: {
    cantidad: cantidad,
    origen: "product_detail"
  }
});

window.ArtianiEcommerceAnalytics.openWhatsapp({
  metadata: {
    items_total: carrito.length,
    origen: "cart_whatsapp_button"
  }
});
```

El SDK filtra claves prohibidas antes de enviar, pero esa limpieza no sustituye la regla principal: el frontend no debe construir payloads de analytics con datos personales ni stock exacto.

No enviar datos personales en analytics:

```text
nombre
telefono
correo
email
rfc
razon_social
direccion
datos_fiscales
stock_exacto
```

Cuando el usuario mande cotizacion, WhatsApp, contacto o facturacion, frontend debe incluir el mismo `session_id` dentro de ese flujo. El backend enlazara despues la sesion anonima con el cliente real por un modulo separado, sin contaminar analytics crudo con PII.

Estado backend validado:

```text
depurar.estado=persistencia_publica_activa
depurar.persistencia.activa=true
depurar.persistencia.modo_actual=registra_bd
```

## Endpoints publicos

```text
POST /ecommercePublico/analytics_sesion
POST /ecommercePublico/evento_navegacion
POST /ecommercePublico/busqueda_registrar
POST /ecommercePublico/analytics_conversion
```

Headers:

```text
Content-Type: application/json
```

## Sesion

Disparar una vez al cargar el sitio y repetir cuando cambie mucho la ruta o UTM.

```http
POST /ecommercePublico/analytics_sesion
```

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "ruta": "/",
  "referrer": "https://google.com",
  "utm_source": "google",
  "utm_medium": "organic",
  "utm_campaign": "",
  "dispositivo": "desktop",
  "metadata": {
    "frontend": "artiani-publico",
    "version": "v1"
  }
}
```

## Evento de navegacion

Disparar en cada cambio de ruta publica.

```http
POST /ecommercePublico/evento_navegacion
```

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "tipo_evento": "page_view",
  "ruta": "/categoria/acuario-y-peces/alimentacion",
  "referrer": "https://artiani.mx/",
  "utm_source": "",
  "utm_medium": "",
  "utm_campaign": "",
  "dispositivo": "desktop",
  "metadata": {
    "categoria_slug": "acuario-y-peces/alimentacion"
  }
}
```

## Producto visto

Disparar al entrar a `/producto/{slug}`.

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "tipo_evento": "view_product",
  "ruta": "/producto/alimento-x",
  "dispositivo": "desktop",
  "id_publicacion": 123,
  "id_sku": 456,
  "slug": "alimento-x",
  "metadata": {
    "categoria_slug": "perros/alimentos",
    "marca_slug": "marca-x"
  }
}
```

## Busqueda

Disparar cuando el usuario confirme busqueda o cuando se rendericen resultados.

```http
POST /ecommercePublico/busqueda_registrar
```

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "query": "croquetas cachorro",
  "ruta": "/buscar/croquetas-cachorro",
  "resultados_total": 18,
  "sin_resultados": false,
  "filtros": {
    "categoria_slug": "perros/alimentos",
    "marca_slug": "",
    "mascota": "perro",
    "necesidad": "alimentacion"
  },
  "metadata": {
    "orden": "relevancia"
  }
}
```

## Conversiones

Usar `analytics_conversion` para acciones importantes del embudo.

```http
POST /ecommercePublico/analytics_conversion
```

Eventos permitidos para conversion:

```text
add_to_quote
remove_from_quote
quote_dryrun
quote_preflight
open_whatsapp
facturacion_submit
```

Ejemplo al agregar al carrito/cotizacion:

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "tipo_conversion": "add_to_quote",
  "ruta": "/producto/alimento-x",
  "id_publicacion": 123,
  "id_sku": 456,
  "slug": "alimento-x",
  "metadata": {
    "cantidad": 1,
    "origen": "product_detail"
  }
}
```

Ejemplo antes de abrir WhatsApp:

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "tipo_conversion": "open_whatsapp",
  "ruta": "/carrito",
  "metadata": {
    "items_total": 3,
    "origen": "cart_whatsapp_button"
  }
}
```

## Eventos recomendados por pantalla

Home:

```text
analytics_sesion
page_view
```

Categoria:

```text
page_view
metadata.categoria_slug
metadata.categoria_path
```

Marca:

```text
page_view
metadata.marca_slug
```

Producto:

```text
page_view
view_product
id_publicacion
id_sku
slug
```

Busqueda:

```text
page_view
busqueda_registrar
```

Carrito/cotizacion:

```text
add_to_quote
remove_from_quote
quote_dryrun
quote_preflight
open_whatsapp
```

Facturacion:

```text
facturacion_view
facturacion_submit
```

## Reglas de implementacion frontend

- No bloquear la navegacion si analytics falla.
- Enviar con `fetch` normal o `navigator.sendBeacon` para `open_whatsapp` si aplica.
- No mandar telefono, nombre, email, RFC ni direccion en analytics.
- No mandar stock exacto.
- No enviar eventos duplicados en renders repetidos; `page_view` debe dispararse por cambio real de URL.
- Usar siempre los slugs/ids que entrega la API, no inventarlos en frontend.
- Guardar `session_id` en `localStorage`; si el usuario borra almacenamiento, se considera nueva sesion.
- Incluir `session_id` tambien en payloads de cotizacion/contacto/facturacion para permitir enlace futuro.

## Criterios de aceptacion

- `GET /ecommercePublico/analytics_contrato` responde `success`.
- Cada endpoint POST responde `success` o `warning` con `bloqueos` claros.
- Con payload valido no debe aparecer `session_id_anonimo_requerido`.
- Si frontend manda datos personales, API debe bloquear con `payload_no_debe_incluir_datos_personales`.
- En modo activo, payload valido responde con `escribe_bd=true`.
- Si se envia PII o stock exacto, la respuesta bloquea con `no_escribe_bd=true`.
- Si en otro ambiente la persistencia no esta activa, la respuesta indicara `no_escribe_bd=true`; frontend no debe cambiar codigo ni endpoints.
