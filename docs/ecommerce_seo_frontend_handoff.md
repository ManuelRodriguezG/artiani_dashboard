# Handoff Frontend - SEO, Sitemap, Robots y Redirecciones Artiani

Documentacion IA: Codex GPT-5, 2026-09-23.

## Estado Actual Validado

- Redirecciones activas: `998`.
- URLs 410 activas: `0`.
- URLs para sitemap: `1816`.
- Errores accionables de destinos: `0`.
- Destinos revisados como OK: `1008`.

La fase actual deja listo el contrato para que frontend implemente:

- `/sitemap.xml`
- `/robots.txt`
- redirecciones 301 de URLs antiguas
- pruebas de status en staging antes de pasar a dominio principal

## Endpoints ERP a Consumir

Base ERP/API actual:

```text
https://panel.artiani.com.mx/ecommercePublico
```

En local o staging interno puede variar, pero las rutas son estas:

```text
GET /ecommercePublico/seo_estado
GET /ecommercePublico/seo_redirecciones
GET /ecommercePublico/seo_sitemap
GET /ecommercePublico/seo_robots
```

Todos son públicos de solo lectura.

## Redirecciones 301

Endpoint:

```text
GET /ecommercePublico/seo_redirecciones
```

Respuesta relevante:

```json
{
  "error": false,
  "depurar": {
    "redirecciones": [
      {
        "from": "/producto/URL-vieja/SKU",
        "to": "/producto/slug-nuevo",
        "status": 301,
        "tipo": "producto",
        "activo": true,
        "revisado": true
      }
    ],
    "gone": []
  }
}
```

Regla para frontend:

1. Antes de renderizar una página, leer el path solicitado.
2. Normalizarlo:
   - sin dominio
   - conservar mayúsculas/minúsculas solo para comparar contra `from` tal como llega, o usar una comparación normalizada con `/` inicial
   - quitar slash final salvo `/`
3. Buscar coincidencia exacta en `redirecciones[].from`.
4. Si existe:
   - responder HTTP `301`
   - `Location: to`
   - no renderizar la app
5. Si existe en `gone[]`:
   - responder HTTP `410`
   - no canonical, no index

Importante:

- Las redirecciones deben resolverse en servidor, middleware, edge function o configuración de hosting.
- No deben resolverse solo en React después de cargar la página, porque Google debe recibir el status HTTP real.

## Sitemap XML

Endpoint:

```text
GET /ecommercePublico/seo_sitemap
```

Respuesta relevante:

```json
{
  "error": false,
  "depurar": {
    "items": [
      {
        "loc": "https://artiani.com.mx/producto/slug",
        "changefreq": "weekly",
        "priority": "0.8"
      }
    ]
  }
}
```

Frontend debe servir:

```text
GET /sitemap.xml
Content-Type: application/xml; charset=utf-8
```

Formato:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://artiani.com.mx/producto/slug</loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
</urlset>
```

Reglas:

- Solo usar `depurar.items`.
- No agregar URLs viejas.
- No agregar URLs 301.
- No agregar URLs 410.
- No agregar endpoints `/ecommercePublico/*`.
- No agregar URLs locales ni staging al sitemap final de producción.

## Robots TXT

Endpoint:

```text
GET /ecommercePublico/seo_robots
```

Respuesta relevante actual:

```json
{
  "depurar": {
    "robots_txt": "User-agent: *\nAllow: /\nSitemap: https://artiani.com.mx/sitemap.xml"
  }
}
```

Frontend debe servir:

```text
GET /robots.txt
Content-Type: text/plain; charset=utf-8
```

Contenido:

```text
User-agent: *
Allow: /
Sitemap: https://artiani.com.mx/sitemap.xml
```

## Orden de Implementación Recomendado

1. Crear un cliente SEO en frontend que consuma:
   - `seo_redirecciones`
   - `seo_sitemap`
   - `seo_robots`
2. Implementar middleware/server handler de redirecciones:
   - interceptar path solicitado
   - aplicar 301 antes de renderizar
3. Implementar `/sitemap.xml`:
   - transformar JSON del ERP a XML
   - cachear por 10 a 60 minutos
4. Implementar `/robots.txt`:
   - devolver texto del ERP
   - cachear por 10 a 60 minutos
5. Probar en `https://prueba.artiani.com.mx`.
6. Cuando staging esté correcto, pasar al dominio principal.

## Pruebas Obligatorias en Staging

### Redirección 301

Probar una URL vieja:

```bash
curl -I "https://prueba.artiani.com.mx/producto/URL-vieja/SKU"
```

Esperado:

```text
HTTP/2 301
location: /producto/slug-nuevo
```

Luego probar destino:

```bash
curl -I "https://prueba.artiani.com.mx/producto/slug-nuevo"
```

Esperado:

```text
HTTP/2 200
```

### Sitemap

```bash
curl -I "https://prueba.artiani.com.mx/sitemap.xml"
```

Esperado:

```text
HTTP/2 200
content-type: application/xml
```

Validar contenido:

- Debe tener `urlset`.
- Debe contener URLs con dominio `https://artiani.com.mx` para producción final.
- No debe contener `prueba.artiani.com.mx` si ya se está preparando el sitemap productivo.
- No debe contener `/ecommercePublico/`.

### Robots

```bash
curl "https://prueba.artiani.com.mx/robots.txt"
```

Esperado:

```text
User-agent: *
Allow: /
Sitemap: https://artiani.com.mx/sitemap.xml
```

## Criterio de Aprobación

Antes de publicar en dominio principal:

- Todas las URLs viejas probadas deben responder `301`.
- El destino de cada 301 debe responder `200`.
- `/sitemap.xml` debe responder `200`.
- `/robots.txt` debe responder `200`.
- El sitemap no debe incluir rutas viejas, rutas internas ni URLs de staging.
- Search Console debe recibir `https://artiani.com.mx/sitemap.xml`.

## Notas Operativas

- ERP es la fuente de verdad de redirecciones y sitemap.
- Frontend no debe inventar slugs ni mantener una lista manual separada.
- Si cambia un slug publicado en ERP, debe actualizarse la redirección antigua y volver a generar/probar sitemap.
- Si una URL vieja no tiene equivalente claro, se debe redirigir a categoría cercana o marcar 410, pero nunca mandar todo a home.
