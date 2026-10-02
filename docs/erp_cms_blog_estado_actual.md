# CMS Blog - Estado actual

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado: backend y UI editorial preparados; persistencia real pendiente de DDL autorizado

## Proposito

Crear el submodulo Blog / Guias / Contenido comercial para el ecommerce publico de Artiani.

El blog debe servir para SEO, educacion del cliente, venta asistida, contenido relacionado en productos/categorias, busqueda propia/global e incorporacion diferida de videos administrados por otro modulo.

## Arquitectura decidida

- Blog/CMS es independiente de los slots de Home y de las publicaciones de producto.
- El blog tiene busqueda propia: `GET /ecommercePublico/blog?q={termino}`.
- La busqueda global compone fuentes independientes: `GET /ecommercePublico/buscar?q={termino}`.
- El frontend no debe consultar tablas ni rutas internas `/cms/*`.
- No usar `ecom_*`.
- Solo publicaciones con `estado=publicado` son visibles publicamente.
- Videos sera un modulo CMS propio. Blog no debe ser el maestro de videos; solo debe incorporar videos ya administrados por ese modulo.
- Videos externos, inicialmente TikTok, deben exponerse como thumbnail + embed URL; el frontend carga el iframe solo al hacer clic.

## Archivos principales

- Modelo: `app/modelos/EcommerceBlogPublico.php`
- Esquema: `app/modelos/EcommercePublicoEsquema.php`
- Controlador publico: `app/controladores/EcommercePublico.php`
- Controlador CMS: `app/controladores/Cms.php`
- Vista CMS: `app/vistas/paginas/apps/erp/cms/blog.php`
- JS CMS: `public/assets/js/custom/apps/erp/cms/blog.js`
- Sidebar: `app/vistas/includes/header/sidebar.php` muestra `CMS > Contenido tienda > Blog / Guias`
- Documento largo relacionado: `docs/erp_cms_contenido_ecommerce.md`
- UAT read-only: `storage/uat/uat_cms_blog_readonly.php`

## Endpoints publicos preparados

- `GET /ecommercePublico/blog_manifest`
- `GET /ecommercePublico/blog?pagina=1&limite=12&q=pecera`
- `GET /ecommercePublico/blog/{slug}`
- `GET /ecommercePublico/blog?demo=1`
- `GET /ecommercePublico/blog/guia-acuario-comunitario-artiani?demo=1`
- `GET /ecommercePublico/buscar?q=pecera`
- `GET /ecommercePublico/buscar?q=acuario&demo=1`
- `GET /ecommercePublico/producto/{slug}/contenido_relacionado`
- `GET /ecommercePublico/categoria/{path_slug}/contenido_relacionado`
- `POST /ecommercePublico/analytics_evento`

## Endpoints CMS preparados

- `GET /cms/blog`
- `GET /cms/blog_admin_estado_erp`
- `GET /cms/blog_admin_listar_erp`
- `GET /cms/blog_admin_consultar_erp?id_blog_publicacion=1`
- `POST /cms/blog_publicacion_guardar_erp`
- `POST /cms/blog_publicacion_estatus_erp`

## Tablas propuestas

- `erp_ecommerce_blog_publicaciones`
- `erp_ecommerce_blog_media`
- `erp_ecommerce_blog_video_relaciones`
- `erp_ecommerce_blog_productos`
- `erp_ecommerce_blog_categorias`
- `erp_ecommerce_blog_bloques_interactivos`
- `erp_ecommerce_blog_slugs`
- `erp_ecommerce_blog_analytics`

El plan DDL se consulta con:

```text
GET /ecommercePublico/esquema_plan_cms_blog
GET /ecommercePublico/esquema_auditar_cms_blog
```

## Acceso en panel

- Sidebar: `CMS > Contenido tienda > Blog / Guias`.
- Ruta visible: `/cms/frontend/blog`.
- Ruta directa equivalente: `/cms/blog`.

## UI actual

`/cms/blog` ya permite:

- cargar con el layout completo del panel ERP/Metronic: header, sidebar, footer, CSS global y scripts globales;
- revisar estado de esquema;
- listar publicaciones;
- crear/editar borrador inicial;
- capturar tipo, titulo, slug, autor, fecha, orden editorial, destacado, portada URL, ALT, extracto, contenido HTML y SEO;
- elegir portada desde Media CMS usando `/cms/media_admin_listar_erp`;
- agregar imagenes internas desde Media CMS usando `/cms/media_admin_listar_erp`;
- agregar productos relacionados desde `/ecommercePublico/catalogo`;
- agregar categorias relacionadas desde `/ecommercePublico/categorias`;
- construir un bloque interactivo de imagen/productos desde controles visuales: titulo, imagen, coordenadas X/Y, selector de producto y vista de puntos;
- previsualizar localmente el articulo en iframe aislado, sin guardar ni publicar;
- guardar borrador;
- publicar o pausar;
- mantener el JSON tecnico sincronizado para videos futuros, productos relacionados, categorias relacionadas, imagenes internas y bloques interactivos.
- sanitizar HTML en backend con lista blanca de etiquetas/atributos editoriales antes de persistir.
- validar contratos con UAT read-only: `senal_cms_blog=verde_contrato_blog_readonly`.

Mientras no exista el esquema, el backend responde `requiere_ddl` y no escribe BD.

## Muestra read-only para frontend

Fecha: 2026-09-29

- Listado demo: `/ecommercePublico/blog?demo=1`.
- Detalle demo: `/ecommercePublico/blog/guia-acuario-comunitario-artiani?demo=1`.
- Busqueda global demo: `/ecommercePublico/buscar?q=acuario&demo=1`.
- La muestra no consulta ni escribe BD; existe para maquetar listado, detalle, portada, SEO, categorias, productos relacionados y bloque interactivo de imagen/productos.
- El SEO demo usa `robots=noindex,nofollow` para que frontend lo trate como fixture de maquetacion, no como contenido indexable real.

## Pendientes priorizados

1. Autorizar respaldo externo y DDL antes de persistir contenido real. La base actual debe tratarse como productiva.
2. Pulir UX visual post-DDL con pruebas reales: reordenar relaciones, validar guardado/publicacion y revisar preview con datos persistidos.
3. Despues del DDL autorizado, agregar pruebas con transaccion/rollback para guardar/publicar/pausar.

## UAT read-only

Ejecutar:

```text
C:\xampp\php\php.exe storage\uat\uat_cms_blog_readonly.php
```

Resultado 2026-09-28:

- `senal_cms_blog=verde_contrato_blog_readonly`
- `tablas_total=8`
- `tablas_faltantes=8`
- `ddl_pendientes=8`
- sanitizacion HTML: OK
- sin escrituras, sin DDL, sin analytics persistente.

## Guardrails

- No ejecutar DDL, migraciones ni escrituras masivas sin autorizacion explicita.
- No tocar `C:\xampp\htdocs\panel`.
- No exponer credenciales.
- No mostrar stock exacto ni calcular precios en frontend.
- No publicar borradores.
- No cargar iframes de video en primer render.

## Handoff

Para continuar sin gastar tokens:

1. Leer este archivo.
2. Revisar `git diff -- app/modelos/EcommerceBlogPublico.php app/vistas/paginas/apps/erp/cms/blog.php public/assets/js/custom/apps/erp/cms/blog.js app/controladores/EcommercePublico.php app/controladores/Cms.php app/modelos/EcommercePublicoEsquema.php`.
3. Si se va a tocar UX, abrir solo `app/vistas/paginas/apps/erp/cms/blog.php` y `public/assets/js/custom/apps/erp/cms/blog.js`.
4. Si se va a tocar persistencia/reglas, abrir solo `app/modelos/EcommerceBlogPublico.php` y `app/modelos/EcommercePublicoEsquema.php`.
