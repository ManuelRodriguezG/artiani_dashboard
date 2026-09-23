# Instrucciones Frontend - Aplicacion SEO Artiani

Documentacion IA: Codex GPT-5, 2026-09-23.

## Objetivo

Implementar en el frontend publico de Artiani:

- redirecciones 301 de URLs antiguas a URLs nuevas;
- `/sitemap.xml`;
- `/robots.txt`;
- verificacion en `https://prueba.artiani.com.mx` antes de moverlo a `https://artiani.com.mx`.

El ERP/panel es la fuente de verdad. Frontend no debe mantener una lista manual separada.

## Estado Actual

- Redirecciones activas listas para aplicar: `998`.
- URLs 410 activas: `0`.
- URLs indexables para sitemap: `1816`.
- Destinos revisados en servidor de prueba: `1008`.
- Errores accionables de destino: `0`.

Pendiente importante:

- Falta validar origen 301, porque eso solo se puede confirmar cuando frontend ya aplique las redirecciones y responda con status HTTP real.

## Endpoints Que Debe Consumir Frontend

Ajustar el host base segun el ambiente real del panel/API:

```text
GET /ecommercePublico/seo_redirecciones
GET /ecommercePublico/seo_sitemap
GET /ecommercePublico/seo_robots
GET /ecommercePublico/seo_estado
```

## Aplicacion De Redirecciones

Frontend debe ejecutar esta logica antes de renderizar cualquier pagina:

1. Tomar el path solicitado por el usuario.
2. Normalizarlo:
   - quitar dominio;
   - asegurar `/` inicial;
   - quitar slash final, excepto cuando sea `/`.
3. Buscar ese path en `depurar.redirecciones[].from`.
4. Si existe coincidencia:
   - responder HTTP `301`;
   - mandar `Location` a `to`;
   - no renderizar React/Vue/app.
5. Si existe en `depurar.gone`:
   - responder HTTP `410 Gone`;
   - no renderizar la app.

Ejemplo conceptual:

```js
const path = normalizePath(request.path);
const rule = redirectMap.get(path);

if (rule) {
  return redirect(rule.to, 301);
}

const goneRule = goneMap.get(path);

if (goneRule) {
  return response("Gone", { status: 410 });
}

return renderApp();
```

Importante:

- No hacerlo solo con JavaScript del navegador.
- Google debe recibir el `301` real desde servidor, middleware, edge function o hosting.
- No redirigir todo a home. Si no hay regla, debe continuar la pagina normal o responder 404 real.

## Aplicacion De Sitemap

Frontend debe crear la ruta publica:

```text
GET /sitemap.xml
```

Debe consumir:

```text
GET /ecommercePublico/seo_sitemap
```

Y transformar `depurar.items` a XML:

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

- No incluir URLs antiguas.
- No incluir URLs que redireccionan.
- No incluir endpoints `/ecommercePublico/*`.
- No incluir dominio local.
- En produccion final, las URLs deben usar `https://artiani.com.mx`.

## Aplicacion De Robots

Frontend debe crear:

```text
GET /robots.txt
```

Debe consumir:

```text
GET /ecommercePublico/seo_robots
```

Y responder texto plano:

```text
User-agent: *
Allow: /
Sitemap: https://artiani.com.mx/sitemap.xml
```

## Cache Recomendado

Para no consultar el ERP en cada visita:

- redirecciones: cache de 10 a 60 minutos;
- sitemap: cache de 10 a 60 minutos;
- robots: cache de 10 a 60 minutos.

Si el frontend tiene build estatico, tambien puede generar estos archivos durante deploy, pero las redirecciones deben quedar disponibles en runtime o en configuracion de hosting.

## Pruebas En `prueba.artiani.com.mx`

Probar una URL vieja:

```bash
curl -I "https://prueba.artiani.com.mx/ruta-vieja"
```

Debe responder:

```text
HTTP/2 301
location: /ruta-nueva
```

Probar la URL nueva:

```bash
curl -I "https://prueba.artiani.com.mx/ruta-nueva"
```

Debe responder:

```text
HTTP/2 200
```

Probar sitemap:

```bash
curl -I "https://prueba.artiani.com.mx/sitemap.xml"
```

Debe responder `200` y `content-type: application/xml`.

Probar robots:

```bash
curl "https://prueba.artiani.com.mx/robots.txt"
```

Debe contener el `Sitemap: https://artiani.com.mx/sitemap.xml`.

## Orden Recomendado Para Frontend

1. Conectar el cliente SEO contra los endpoints del ERP.
2. Crear cache interno para redirecciones, sitemap y robots.
3. Implementar middleware o handler de redirecciones 301/410.
4. Implementar `/sitemap.xml`.
5. Implementar `/robots.txt`.
6. Subir a `https://prueba.artiani.com.mx`.
7. Validar origen 301 desde la vista SEO del panel.
8. Si todo responde correcto, publicar en dominio principal.

## Criterio Para Decir Que Ya Esta Listo

- Una URL antigua responde `301` real.
- El `Location` apunta a la URL nueva correcta.
- La URL nueva responde `200`.
- `/sitemap.xml` responde XML valido.
- `/robots.txt` responde texto plano.
- El sitemap no contiene URLs de prueba ni endpoints internos.
- La vista SEO del panel ya no muestra pendientes de origen 301.
