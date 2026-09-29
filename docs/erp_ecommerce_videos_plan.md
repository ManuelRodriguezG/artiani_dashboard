# ERP Ecommerce publico - Modulo de videos

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado: contrato/API fixture inicial y CMS interno read-only implementados; sin DDL aplicado.

## Actualizacion 2026-09-28 - Contrato API fixture inicial

Implementado sin escrituras de base de datos:

- Modelo `app/modelos/EcommerceVideosErp.php`.
- Endpoint publico `GET /ecommercePublico/videos`.
- Endpoint publico `GET /ecommercePublico/videos/{slug}`.
- Endpoint publico `GET /ecommercePublico/videos_manifest`.
- Endpoint publico `GET /ecommercePublico/producto/{slug}/videos`.
- Endpoint publico futuro `GET /ecommercePublico/categoria/{path_slug}/videos`.
- Endpoint interno protegido `GET /ecommercePublico/esquema_auditar_videos`.
- Endpoint interno protegido `GET /ecommercePublico/esquema_plan_videos`.

Decision tecnica:

- La primera version responde con `fuente=fixture_contrato` mientras no exista la tabla `erp_ecommerce_videos`.
- Los listados y secciones embebidas devuelven cards con thumbnail y CTAs, sin iframe.
- El detalle devuelve `embed_url`, pero el contrato indica que el frontend debe cargar el player solo con clic.
- La auditoria de esquema solo consulta `INFORMATION_SCHEMA`; no ejecuta DDL.
- El plan de esquema devuelve DDL completo en modo read-only para:
  - `erp_ecommerce_videos`;
  - `erp_ecommerce_video_producto`;
  - `erp_ecommerce_video_categoria`.
- El plan no aplica cambios; cualquier DDL futuro requiere respaldo externo, autorizacion explicita y token operativo.
- UAT read-only agregado: `storage/uat/uat_ecommerce_videos_readonly.php`.
- Resultado UAT: `senal_frontend_videos=verde_contrato_videos_fixture`, con 3 tablas faltantes esperadas porque el esquema aun no se ha aplicado.
- El modelo `EcommerceVideosErp` quedo preparado para cambiar automaticamente de `fuente=fixture_contrato` a `fuente=bd_videos` cuando la auditoria detecte completas las tres tablas requeridas.
- El frontend no debe depender de la fuente; debe consumir el mismo contrato en ambos modos.

## Actualizacion 2026-09-28 - TikTok como proveedor de video

Decision del dueno:

- Los videos se cargaran en TikTok.
- El ERP/ecommerce no alojara el archivo de video.
- El modulo guardara el enlace de TikTok, el `tiktok_post_id` cuando se pueda extraer, el autor/cuenta, y el `embed_url` para incrustar el player.
- En listados, tarjetas y secciones de producto/categoria se debe mostrar solo miniatura propia; no se debe cargar el iframe de TikTok hasta que el usuario haga clic.
- El copy de TikTok, hashtags, titulo, descripcion corta/larga y un campo `texto_busqueda` se usaran para buscar y entender de que trata el video.
- La miniatura debe guardarse como recurso propio/controlado por Artiani para no depender de portadas temporales externas.

Impacto en esquema:

- `provider` pasa a tener `tiktok` como valor por defecto.
- Se agregan al plan read-only: `tiktok_post_id`, `tiktok_author`, `copy_tiktok`, `hashtags` y `texto_busqueda`.
- `video_url` guarda la URL publica de TikTok, por ejemplo `https://www.tiktok.com/@artiani/video/{post_id}`.
- `embed_url` guarda el player embebible, recomendado como `https://www.tiktok.com/player/v1/{post_id}?autoplay=0&description=0`.

Regla frontend:

- La tarjeta usa `thumbnail.url`.
- Al hacer clic, el frontend reemplaza la miniatura por un iframe con `video.embed_url`.
- No activar autoplay.
- No depender del texto visible del iframe para busqueda; la busqueda del sitio usa los campos propios del API.

Actualizacion CMS:

- La pantalla `CMS > Videos` debe capturar TikTok como proveedor unico inicial.
- El operador pega la URL completa del video de TikTok.
- El sistema intenta extraer `tiktok_post_id` y sugerir `embed_url`.
- Si se pega un enlace corto que no permite extraer el ID, se debe pedir URL completa o embed manual.
- Campos operativos de captura: titulo, slug, URL de TikTok, embed calculado, thumbnail propia, descripcion, copy de TikTok, hashtags, texto de busqueda, duracion y metadata.
- El CMS no descarga videos ni usa TikTok como fuente de miniatura obligatoria; la miniatura debe quedar controlada por Artiani.
- Implementado primer CMS interno:
  - ruta vista: `/cms/videos`;
  - modelo: `app/modelos/EcommerceVideosCms.php`;
  - vista: `app/vistas/paginas/apps/erp/cms/videos.php`;
  - JS: `public/assets/js/custom/apps/erp/cms/videos.js`;
  - endpoints internos: `/cms/videos_admin_estado_erp`, `/cms/videos_admin_listar_erp`, `/cms/videos_admin_consultar_erp`, `/cms/videos_guardar_erp`, `/cms/videos_estatus_erp`.
- La captura incluye relaciones comerciales en JSON:
  - producto principal;
  - productos relacionados;
  - categorias relacionadas.
- La persistencia real sigue bloqueada hasta aplicar esquema autorizado; sin tablas, el CMS responde pendiente de esquema.
- UAT admin read-only agregado: `storage/uat/uat_ecommerce_videos_cms_admin_readonly.php`.
- Resultado UAT admin: `senal_cms_videos=verde_cms_videos_admin_readonly`, con `requiere_ddl=true` esperado mientras no existan las tablas.

## Actualizacion 2026-09-28 - Kit frontend publico

Implementado sin depender de tablas ni escribir BD:

- JS publico: `public/assets/js/custom/apps/ecommerce/videos-publico.js`.
- CSS publico: `public/assets/css/custom/apps/ecommerce/videos-publico.css`.
- UAT read-only: `storage/uat/uat_ecommerce_videos_frontend_assets_readonly.php`.
- Resultado UAT frontend assets: `senal_frontend_assets_videos=verde_videos_frontend_assets`.

Montaje recomendado en frontend externo:

```html
<link rel="stylesheet" href="/assets/css/custom/apps/ecommerce/videos-publico.css">
<script src="/assets/js/custom/apps/ecommerce/videos-publico.js"></script>
<script>
  ArtianiEcommerceVideos.init({ endpointBase: "", autoMount: true });
</script>
```

Contenedores soportados:

```html
<div data-artiani-videos-list data-limite="12"></div>
<div data-artiani-video-detail="filtro-cascada-sunny-shf-600-funcionamiento"></div>
<div data-artiani-product-videos="filtro-cascada-sunny-shf-600" data-limite="6"></div>
<div data-artiani-category-videos="peces/filtracion/filtros" data-limite="6"></div>
```

Reglas del kit:

- consume solo `/ecommercePublico/videos`, `/ecommercePublico/videos/{slug}`, `/ecommercePublico/producto/{slug}/videos` y `/ecommercePublico/categoria/{path_slug}/videos`;
- renderiza cards con miniatura y CTA;
- no inyecta iframe TikTok en listados;
- en detalle, reemplaza miniatura por iframe solo al hacer clic;
- envia eventos `video_view`, `video_play`, `video_producto_click` y `video_categoria_click` a `/ecommercePublico/analytics_evento`;
- si no hay persistencia analytics autorizada, el backend acepta el evento y responde sin persistir.

## Actualizacion 2026-09-28 - Puente Analytics Videos

Implementado:

- `public/assets/js/custom/apps/ecommerce/analytics-tracker-publico.js` expone `rawPost(path, payload, options)`.
- `videos-publico.js` usa `ArtianiEcommerceAnalytics.rawPost` cuando el SDK general esta cargado.
- Si el SDK general no existe, `videos-publico.js` mantiene fallback propio hacia `/ecommercePublico/analytics_evento`.
- `EcommercePublico::analytics_evento()` rutea eventos `video_*` hacia `EcommerceVideosErp`.
- `EcommerceVideosErp::registrarAnalyticsEvento()` valida eventos permitidos y responde sin persistir mientras no exista schema analytics autorizado.

UAT agregado:

- `storage/uat/uat_ecommerce_videos_analytics_bridge_readonly.php`.
- Resultado: `senal_analytics_bridge_videos=verde_videos_analytics_bridge`.

Eventos permitidos:

- `video_view`
- `video_play`
- `video_producto_click`
- `video_add_to_cart`
- `video_categoria_click`
- `video_whatsapp_click`

## Actualizacion 2026-09-28 - Primer video TikTok real para prueba frontend

Se agrego al fixture read-only el primer enlace real compartido por el dueno:

- URL TikTok: `https://www.tiktok.com/@articulos_para_animales/video/7526057146073566471`
- Slug API/frontend: `tiktok-articulos-para-animales-7526057146073566471`
- Provider: `tiktok`
- `tiktok_post_id`: `7526057146073566471`
- `tiktok_author`: `articulos_para_animales`
- `embed_url`: `https://www.tiktok.com/player/v1/7526057146073566471?autoplay=0&description=0`
- Thumbnail temporal local: `/assets/fixtures/videos/tiktok-articulos-para-animales-7526057146073566471.svg`

Notas operativas:

- La miniatura es temporal y debe reemplazarse por una miniatura comercial final antes de publicar en produccion.
- No se relaciono a producto porque falta confirmar SKU/publicacion destino.
- SEO del fixture queda con `robots=noindex,follow` para evitar tratarlo como contenido final.
- UAT read-only agregado: `storage/uat/uat_ecommerce_videos_tiktok_prueba_readonly.php`.
- Resultado: `senal_tiktok_prueba=verde_tiktok_prueba_frontend`.

## Actualizacion 2026-09-28 - Activacion de tablas y primer seed real

Activacion aplicada con respaldo externo:

- Base activa: `artianicom_sys`.
- Respaldo: `C:\xampp\panel_db_backups\artianicom_sys_panel_20260928_214345_antes_ecommerce_videos_schema.sql`.
- Tamano respaldo: `50806604` bytes.
- DDL aplicado con token `ECOMMERCE_VIDEOS_DDL`.
- Script: `storage/uat/uat_ecommerce_videos_schema_apply_authorized.php`.

Tablas creadas:

- `erp_ecommerce_videos`
- `erp_ecommerce_video_producto`
- `erp_ecommerce_video_categoria`

Postcheck:

- `senal_schema_postcheck=esquema_videos_completo`
- `tablas_faltantes=0`
- `columnas_faltantes_total=0`
- `indices_faltantes_total=0`

Seed aplicado:

- Script: `storage/uat/uat_ecommerce_videos_seed_tiktok_prueba_authorized.php`.
- Token: `ECOMMERCE_VIDEOS_SEED_PRUEBA`.
- `id_video=1`.
- `slug=tiktok-articulos-para-animales-7526057146073566471`.
- `estado=publicado`.
- `provider=tiktok`.
- `tiktok_post_id=7526057146073566471`.

Validaciones posteriores:

- `storage/uat/uat_ecommerce_videos_readonly.php`: `senal_frontend_videos=verde_contrato_videos`, `fuente=bd_videos`, `listado_items=1`.
- `storage/uat/uat_ecommerce_videos_frontend_assets_readonly.php`: `senal_frontend_assets_videos=verde_videos_frontend_assets`, `listado_fuente=bd_videos`.
- `storage/uat/uat_ecommerce_videos_cms_admin_readonly.php`: `senal_cms_videos=verde_cms_videos_admin_readonly`, `requiere_ddl=false`.
- `storage/uat/uat_ecommerce_videos_tiktok_prueba_readonly.php`: `senal_tiktok_prueba=verde_tiktok_prueba_frontend`.

Pendiente operativo:

- Relacionar el video con producto/SKU/publicacion cuando el dueno confirme cual corresponde.
- Reemplazar la miniatura temporal SVG por una miniatura comercial final.

## Actualizacion 2026-09-28 - Primer video real categorizado como Tortugueros

El primer video TikTok real dejo de ser solo seed generico de prueba y quedo como contenido editorial de categoria:

- Slug API/frontend: `tiktok-articulos-para-animales-7526057146073566471`.
- Titulo: `Tortugueros con zona seca, filtración y espacio adecuado`.
- Tipo: `consejo_rapido`.
- Categoria relacionada: `reptiles-anfibios-e-invertebrados/tortugas/tortugueros`.
- URL categoria: `/categoria/reptiles-anfibios-e-invertebrados/tortugas/tortugueros`.
- Miniatura temporal local: `/assets/media/cms/ecommerce/videos/tortugueros-tiktok-7526057146073566471.png`.
- No se relaciona a producto especifico; `producto_items=0` es el comportamiento esperado.

Copy registrado para busqueda y detalle:

```text
❌ Una tortuga en una pecera común puede enfermarse.
✅ Un tortuguero bien hecho tiene zona seca, buena filtración y el espacio que necesita.

Adaptamos las plataformas a la medida de tu tortuga, para que viva como debe.

¿Tienes una tortuga o piensas tener una? Escríbenos y te asesoramos.
```

Hashtags:

- `#Tortugas`
- `#Tortugueros`
- `#HábitatParaTortugas`
- `#CuidaATuTortuga`
- `#MascotasFelices`
- `#TortugasSaludables`
- `#PecerasPersonalizadas`

Actualizacion aplicada con escritura controlada:

- Script: `storage/uat/uat_ecommerce_videos_update_tortugueros_authorized.php`.
- Token: `ECOMMERCE_VIDEOS_TORTUGUEROS_UPDATE`.
- Respaldo usado: `C:\xampp\panel_db_backups\artianicom_sys_panel_20260928_214345_antes_ecommerce_videos_schema.sql`.
- Guardrails: no descarga video, no toca catalogo, no toca productos, no toca ventas, no toca inventario.

Validaciones:

- `storage/uat/uat_ecommerce_videos_categoria_tortugueros_readonly.php`: encontro categoria real `Tortugueros`, id `496`, con 13 productos publicados.
- `storage/uat/uat_ecommerce_videos_tortugueros_readonly.php`: `senal_video_tortugueros=verde_video_tortugueros_categoria`.
- `storage/uat/uat_ecommerce_videos_readonly.php`: `senal_frontend_videos=verde_contrato_videos`, `producto_items=0`, `categoria_items=1`.
- `storage/uat/uat_ecommerce_videos_frontend_assets_readonly.php`: `senal_frontend_assets_videos=verde_videos_frontend_assets`.

## Actualizacion 2026-09-28 - Primer video real categorizado como Tortugueros

El primer video TikTok real dejo de ser solo seed generico de prueba y quedo como contenido editorial de categoria:

- Slug API/frontend: `tiktok-articulos-para-animales-7526057146073566471`.
- Titulo: `Tortugueros con zona seca, filtración y espacio adecuado`.
- Tipo: `consejo_rapido`.
- Categoria relacionada: `reptiles-anfibios-e-invertebrados/tortugas/tortugueros`.
- URL categoria: `/categoria/reptiles-anfibios-e-invertebrados/tortugas/tortugueros`.
- Miniatura temporal local: `/assets/media/cms/ecommerce/videos/tortugueros-tiktok-7526057146073566471.png`.
- No se relaciona a producto especifico; `producto_items=0` es el comportamiento esperado.

Copy registrado para busqueda y detalle:

```text
❌ Una tortuga en una pecera común puede enfermarse.
✅ Un tortuguero bien hecho tiene zona seca, buena filtración y el espacio que necesita.

Adaptamos las plataformas a la medida de tu tortuga, para que viva como debe.

¿Tienes una tortuga o piensas tener una? Escríbenos y te asesoramos.
```

Hashtags:

- `#Tortugas`
- `#Tortugueros`
- `#HábitatParaTortugas`
- `#CuidaATuTortuga`
- `#MascotasFelices`
- `#TortugasSaludables`
- `#PecerasPersonalizadas`

Actualizacion aplicada con escritura controlada:

- Script: `storage/uat/uat_ecommerce_videos_update_tortugueros_authorized.php`.
- Token: `ECOMMERCE_VIDEOS_TORTUGUEROS_UPDATE`.
- Respaldo usado: `C:\xampp\panel_db_backups\artianicom_sys_panel_20260928_214345_antes_ecommerce_videos_schema.sql`.
- Guardrails: no descarga video, no toca catalogo, no toca productos, no toca ventas, no toca inventario.

Validaciones:

- `storage/uat/uat_ecommerce_videos_categoria_tortugueros_readonly.php`: encontro categoria real `Tortugueros`, id `496`, con 13 productos publicados.
- `storage/uat/uat_ecommerce_videos_tortugueros_readonly.php`: `senal_video_tortugueros=verde_video_tortugueros_categoria`.
- `storage/uat/uat_ecommerce_videos_readonly.php`: `senal_frontend_videos=verde_contrato_videos`, `producto_items=0`, `categoria_items=1`.
- `storage/uat/uat_ecommerce_videos_frontend_assets_readonly.php`: `senal_frontend_assets_videos=verde_videos_frontend_assets`.

## Actualizacion 2026-09-28 - Compuerta de activacion de esquema

Scripts preparados:

- `storage/uat/uat_ecommerce_videos_schema_postcheck_readonly.php`
- `storage/uat/uat_ecommerce_videos_schema_apply_authorized.php`

Documentos de autorizacion preparados:

- `docs/erp_ecommerce_videos_schema_solicitud_autorizacion.md`
- `docs/erp_ecommerce_videos_schema_runbook_aplicacion.md`
- `docs/erp_ecommerce_videos_schema_plan_reversa.md`

Resultado postcheck read-only:

- `senal_schema_postcheck=esquema_videos_pendiente`
- `tablas_faltantes=3`
- `columnas_faltantes_total=45`
- `indices_faltantes_total=15`

Reglas de activacion:

- No ejecutar `apply_authorized` sin autorizacion explicita del dueno.
- Antes de aplicar DDL debe existir respaldo externo en `C:\xampp\panel_db_backups`.
- Token requerido: `ECOMMERCE_VIDEOS_DDL`.
- Comando preparado, no ejecutado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_videos_schema_apply_authorized.php --autorizar=ECOMMERCE_VIDEOS_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

Guardrails del apply:

- crea solo tablas de videos si no existen;
- no publica videos;
- no descarga videos;
- no toca ventas;
- no toca inventario;
- no modifica publicaciones ecommerce existentes.

## Objetivo

Crear un modulo independiente de videos para el ecommerce Artiani que ayude a captar clientes, explicar productos, generar confianza y empujar conversion hacia producto, carrito local o WhatsApp.

La primera etapa debe ser simple, vendible y rapida. El producto sigue siendo la base comercial. El video es contenido de apoyo para demostrar uso real, comparar, explicar instalacion, resolver dudas y mejorar la decision de compra.

No se agrega todavia logica de mascotas registradas, usuarios, comunidad, blog ni personalizacion.

## Decision de arquitectura

- El frontend publico tendra rutas canonicas `/videos` y `/videos/{slug}`.
- El ERP no renderiza esas paginas; solo expone API y administra contenido.
- Las rutas API viven bajo `GET /ecommercePublico/...`.
- Ninguna URL canonica SEO debe iniciar con `/ecommercePublico`.
- En listados, cards y secciones embebidas solo se renderizan thumbnails; el iframe/player se carga hasta que el usuario hace clic.
- El CMS/admin vive dentro del panel ERP, protegido por sesion, permisos, CSRF y auditoria.
- Mientras el esquema real no exista, el frontend puede usar fixtures con la misma forma del contrato.

## Alcance fase 1

Debe incluir:

- listado publico `/videos`;
- detalle publico `/videos/{slug}`;
- API publica read-only para listado, detalle y videos por producto;
- seccion "Videos de este producto" dentro de `/producto/{slug}`;
- estructura CMS/admin para cargar videos como contenido independiente;
- relaciones video-producto y video-categoria;
- analytics anonimo para ver, reproducir, ir a producto, agregar a carrito, ir a categoria y abrir WhatsApp;
- SEO por listado y detalle;
- fixtures para frontend externo.

No debe incluir todavia:

- usuarios registrados;
- mascotas registradas;
- recomendaciones personalizadas;
- comunidad;
- comentarios;
- likes;
- suscripciones;
- reproduccion automatica;
- carga inicial de iframes;
- lectura directa de tablas desde frontend.

## Modelo de datos propuesto

Tabla principal:

```sql
erp_ecommerce_videos
```

Campos:

- `id_video`
- `titulo`
- `slug`
- `descripcion_corta`
- `descripcion_larga`
- `tipo_video`
- `provider`
- `video_url`
- `embed_url`
- `thumbnail_url`
- `thumbnail_alt`
- `orientacion`
- `duracion_segundos`
- `estado`
- `fecha_publicacion`
- `orden`
- `destacado`
- `seo_title`
- `seo_description`
- `seo_canonical`
- `og_image`
- `created_at`
- `updated_at`
- `creado_por`
- `actualizado_por`

Estados recomendados:

- `borrador`
- `publicado`
- `pausado`
- `archivado`

Relaciones con producto:

```sql
erp_ecommerce_video_producto
```

Campos:

- `id_video_producto`
- `id_video`
- `id_publicacion`
- `id_sku`
- `slug_producto`
- `producto_principal`
- `orden`

Regla:

- La relacion comercial preferida debe apuntar a `id_publicacion` cuando exista.
- `slug_producto` sirve como snapshot/fallback para frontend y SEO, no como fuente primaria.
- Un video puede tener un producto principal y varios productos relacionados.

Relaciones con categoria:

```sql
erp_ecommerce_video_categoria
```

Campos:

- `id_video_categoria`
- `id_video`
- `categoria_path_slug`
- `url_categoria`
- `orden`

Regla:

- El frontend debe usar siempre `url_categoria` entregada por API.
- `categoria_path_slug` ayuda a buscar y relacionar, pero no debe obligar al frontend a reconstruir URLs.

## Backend/CMS

Modelo sugerido:

- `app/modelos/EcommerceVideosErp.php`
- `app/modelos/EcommerceVideosEsquema.php`

Controlador:

- extender `app/controladores/EcommercePublico.php` con metodos publicos read-only e internos protegidos.

Vista interna:

- `app/vistas/paginas/apps/erp/ecommerce/videos.php`

JS interno:

- `public/assets/js/custom/apps/erp/ecommerce/videos.js`

Permisos sugeridos:

- `catalogo.ver`: puede consultar videos y relaciones.
- `catalogo.editar`: puede crear, editar, publicar, pausar y ordenar videos.

Rutas internas protegidas sugeridas:

```http
GET  /ecommercePublico/videos_admin
GET  /ecommercePublico/videos_listar_erp
GET  /ecommercePublico/video_consultar_erp/{id}
POST /ecommercePublico/video_guardar_erp
POST /ecommercePublico/video_estatus_erp
POST /ecommercePublico/video_relaciones_guardar_erp
GET  /ecommercePublico/esquema_auditar_videos
GET  /ecommercePublico/esquema_plan_videos
```

Guardrails:

- No ejecutar DDL sin respaldo externo y autorizacion explicita.
- No subir videos pesados al ERP en fase 1; usar proveedor externo controlado, por ejemplo YouTube.
- Validar `provider` y `embed_url` para evitar embeds arbitrarios.
- No permitir contenido publicado sin thumbnail.
- Registrar auditoria explicita al publicar, pausar o cambiar relaciones comerciales.

## API publica

Listado:

```http
GET /ecommercePublico/videos?pagina=1&limite=12&tipo=&producto=&categoria=&destacado=
```

Detalle:

```http
GET /ecommercePublico/videos/{slug}
```

Videos de producto:

```http
GET /ecommercePublico/producto/{slug}/videos
```

Videos de categoria, fase posterior:

```http
GET /ecommercePublico/categoria/{path_slug}/videos
```

Respuesta base:

```json
{
  "error": false,
  "tipo": "success",
  "mensaje": "Videos ecommerce",
  "depurar": {
    "items": [],
    "paginacion": {},
    "guardrails": {
      "solo_publicados": true,
      "no_iframe_en_listado": true,
      "producto_base_comercial": true
    }
  }
}
```

Item de video:

```json
{
  "id": 1,
  "titulo": "Filtro de cascada SUNNY SHF-600 en funcionamiento",
  "slug": "filtro-cascada-sunny-shf-600-funcionamiento",
  "tipo_video": "demo_producto",
  "descripcion_corta": "Vista rapida del flujo y montaje del filtro.",
  "descripcion_larga": "Texto opcional para explicar uso, cuidados o compatibilidad.",
  "thumbnail": {
    "url": "https://...",
    "alt": "Filtro de cascada funcionando"
  },
  "video": {
    "provider": "youtube",
    "url": "https://...",
    "embed_url": "https://www.youtube.com/embed/...",
    "duracion_segundos": 45,
    "orientacion": "vertical"
  },
  "producto_principal": {},
  "productos_relacionados": [],
  "categorias_relacionadas": [],
  "videos_relacionados": [],
  "seo": {
    "title": "Filtro de cascada SUNNY SHF-600 en funcionamiento | Artiani",
    "description": "Mira el flujo y montaje del filtro SUNNY SHF-600.",
    "canonical": "/videos/filtro-cascada-sunny-shf-600-funcionamiento",
    "robots": "index,follow",
    "og_image": "https://..."
  }
}
```

## Frontend publico

Ruta `/videos`:

- grid responsive de cards verticales;
- thumbnail 9:16 obligatoria;
- icono/boton play visual sobre miniatura;
- badge de tipo de video;
- titulo;
- descripcion corta;
- producto principal si existe;
- CTA "Ver video";
- CTA "Ver producto" si tiene producto principal;
- sin iframe ni player en render inicial.

Ruta `/videos/{slug}`:

- hero de video con thumbnail principal;
- boton "Ver video";
- al hacer clic, reemplazar thumbnail por iframe/player;
- titulo, tipo y descripcion;
- producto principal destacado;
- productos relacionados con las mismas cards actuales del ecommerce;
- categorias relacionadas usando `url_categoria`;
- videos relacionados;
- CTA WhatsApp contextual.

Integracion en `/producto/{slug}`:

- llamar `GET /ecommercePublico/producto/{slug}/videos`;
- mostrar seccion "Videos de este producto";
- usar cards compactas con thumbnail;
- link principal a `/videos/{slug}`;
- no cargar iframes;
- CTA secundario "Ver video";
- si el producto esta disponible, permitir "Agregar al carrito" usando el flujo existente del producto.

Integracion futura en `/categoria/{path_slug}`:

- llamar `GET /ecommercePublico/categoria/{path_slug}/videos`;
- mostrar "Videos de esta categoria";
- usar categoria como navegacion visual y editorial, no como personalizacion por mascota.

## Tipos de video

Tipos iniciales:

- `demo_producto`: mostrar funcionamiento real.
- `instalacion`: explicar armado, montaje o configuracion.
- `uso`: mostrar uso cotidiano.
- `comparativa`: ayudar a elegir entre opciones.
- `unboxing`: mostrar contenido, tamano y presentacion.
- `mantenimiento`: explicar limpieza, cuidado o reemplazo.
- `consejo_rapido`: resolver duda comun en menos tiempo.

Regla de uso:

- Todo video debe tener un tipo principal.
- El tipo orienta miniatura, guion, analytics y filtros.
- No usar tipo como reemplazo de categoria comercial.

## Miniaturas

Formato:

- vertical 9:16;
- recomendado 1080 x 1920;
- producto real visible;
- fondo limpio;
- alto contraste;
- texto corto de 3 a 6 palabras;
- etiqueta visual consistente: Demo, Instalacion, Comparativa, Tip rapido;
- evitar saturacion, texto excesivo o fondos que oculten el producto.

Textos utiles:

- "Asi funciona"
- "Tamano real"
- "Antes de comprar"
- "Filtro en accion"
- "Como instalarlo"
- "Ideal para acuarios"
- "Comparativa rapida"
- "Mira el flujo"
- "Tip de uso"
- "Que incluye"

## Guiones rapidos

Guion de 15 segundos:

- 0-2s: mostrar producto o resultado.
- 3-6s: decir que es y para que sirve.
- 7-11s: mostrar el detalle que resuelve la duda.
- 12-15s: CTA: "Lo encuentras en Artiani" o "Te ayudamos por WhatsApp".

Guion de 30 segundos:

- 0-2s: gancho visual fuerte.
- 3-8s: presentar producto y problema que resuelve.
- 9-20s: mostrar uso real, tamano, textura, flujo o compatibilidad.
- 21-26s: beneficio practico y recomendacion.
- 27-30s: CTA hacia producto/carrito/WhatsApp.

Guion de 45 segundos:

- 0-2s: gancho visual.
- 3-8s: que es el producto y para quien sirve.
- 9-20s: demostracion principal.
- 21-32s: detalle tecnico, cuidado, instalacion o comparacion.
- 33-40s: recomendacion honesta o limitacion util.
- 41-45s: CTA comercial natural.

## Primeros 30 videos recomendados

Prioridad acuario:

1. Filtro de cascada en funcionamiento.
2. Como instalar un filtro de cascada.
3. Flujo real de filtro interno.
4. Comparativa filtro chico vs filtro grande.
5. Como elegir filtro segun tamano de acuario.
6. Termostato: donde colocarlo.
7. Aireador: que incluye y como se ve funcionando.
8. Piedra difusora: burbuja fina vs normal.
9. Alimento para peces: tamano real del granulo.
10. Como alimentar sin ensuciar el agua.

Prioridad mascotas generales:

11. Transportadora: tamano real y ventilacion.
12. Correa o pechera: como ajustar.
13. Plato/bebedero: material y limpieza.
14. Juguete resistente: textura y tamano.
15. Cama: grosor y tamano real.
16. Cepillo: tipo de pelo recomendado.
17. Shampoo: cuando usarlo y como aplicarlo.
18. Premios: tamano real y uso sugerido.
19. Arena/sustrato: textura y absorcion.
20. Comedero automatico o dispensador en accion.

Prioridad reptiles/aves:

21. Lampara o foco: para que sirve.
22. Sustrato para reptil: textura y uso.
23. Refugio: tamano real dentro del habitat.
24. Bebedero reptil: estabilidad y limpieza.
25. Percha para ave: tamano y agarre.
26. Juguete para ave: movimiento y seguridad.
27. Alimento de ave: presentacion real.
28. Jaula/accesorio: detalle de cierre o montaje.

Videos de confianza:

29. Que incluye tu pedido Artiani.
30. Como pedir ayuda por WhatsApp antes de comprar.

## Flujo operativo rapido

1. Elegir producto publicado o candidato con demanda.
2. Definir tipo de video y duda que resuelve.
3. Grabar vertical 9:16 en 20 a 45 segundos.
4. Editar corte limpio, subtitulos breves y CTA final.
5. Crear miniatura consistente.
6. Subir a proveedor externo.
7. Cargar en CMS: titulo, slug, tipo, thumbnail, URLs y relaciones.
8. Revisar preview en detalle de video y ficha de producto.
9. Publicar.
10. Reutilizar en Reels/TikTok/Shorts y enviar por WhatsApp cuando aplique.
11. Revisar analytics semanal y decidir si repetir formato, mejorar miniatura o cambiar CTA.

## Herramientas sugeridas

Edicion:

- CapCut para videos cortos y subtitulos.
- Canva para miniaturas rapidas.
- YouTube Studio para alojar y revisar reproducciones basicas.

Organizacion:

- Carpeta por producto/SKU.
- Nombre de archivo: `sku_tipo_slug_fecha`.
- Plantilla visual fija para miniaturas.

## Reutilizacion comercial

Ecommerce:

- listado `/videos`;
- detalle `/videos/{slug}`;
- seccion en producto;
- seccion futura en categoria;
- cards en home o colecciones cuando exista curaduria.

Redes:

- publicar el mismo video vertical como Reel, Short o TikTok;
- usar el primer comentario o descripcion con enlace al producto;
- evitar depender de Marketplace como canal principal.

WhatsApp:

- enviar video o link de detalle cuando el cliente pregunta "como funciona", "que tamano tiene" o "me sirve para...".
- usar mensaje corto: "Te dejo un video rapido para verlo funcionando. Desde ahi puedes abrir el producto o escribirme."

## Analytics

Eventos nuevos sugeridos:

- `video_view`
- `video_play`
- `video_producto_click`
- `video_add_to_cart`
- `video_categoria_click`
- `video_whatsapp_click`

Reglas:

- usar `session_id` anonimo como analytics actual;
- no enviar PII;
- no enviar stock exacto;
- incluir `id_video`, `slug_video`, `tipo_video`, `id_publicacion`, `id_sku`, `slug_producto` cuando exista;
- registrar `video_add_to_cart` como conversion anonima, sin crear venta ni pedido.

Metricas semanales:

- videos vistos;
- tasa de play: plays / views;
- clicks a producto;
- agregados a carrito desde video;
- WhatsApp desde video;
- conversion por tipo de video;
- productos con video vs productos sin video.

## SEO y datos estructurados

Frontend debe renderizar:

- title;
- description;
- canonical `/videos/{slug}`;
- Open Graph con `og_image`;
- `robots=index,follow` para publicados;
- JSON-LD tipo `VideoObject` cuando haya `embed_url`, thumbnail y fecha de publicacion;
- enlaces internos hacia producto principal y relacionados.

Listado `/videos`:

- canonical `/videos`;
- contenido indexable sin iframes iniciales;
- paginacion crawlable si aplica.

## Preparacion para fase futura

Dejar preparado sin activarlo:

- campos/metadata para tags editoriales;
- relacion por categoria y producto;
- analytics por sesion anonima;
- estructura para recomendaciones por mascota en una tabla futura separada;
- posibilidad de relacionar video con necesidad/mascota sin guardar mascota de usuario.

No agregar todavia:

- `usuario_id`;
- `mascota_id`;
- `mascota_usuario_id`;
- perfilamiento personalizado;
- recomendaciones privadas.

## Plan de implementacion recomendado

Fase 0 - contrato y fixtures:

- documentar contrato final;
- crear fixture de listado, detalle y videos por producto;
- entregar al frontend externo para UI sin BD.

Fase 1 - backend read-only real:

- crear `EcommerceVideosEsquema` con auditoria/plan;
- crear `EcommerceVideosErp` con consultas read-only y fallback fixture;
- agregar endpoints publicos;
- agregar endpoints internos de auditoria/plan;
- no aplicar DDL hasta autorizacion.

Fase 2 - CMS/admin:

- vista interna de administracion;
- alta/edicion de video;
- relacion con productos publicados;
- relacion con categorias;
- publicar/pausar;
- auditoria.

Fase 3 - frontend publico:

- `/videos`;
- `/videos/{slug}`;
- seccion en `/producto/{slug}`;
- analytics anonimo;
- SEO y JSON-LD.

Fase 4 - categoria:

- `GET /ecommercePublico/categoria/{path_slug}/videos`;
- seccion "Videos de esta categoria";
- curaduria por categoria.

## Handoff / continuidad

Fecha: 2026-09-28

- Contexto actual: se definio que videos es modulo ecommerce independiente, centrado en producto y conversion, sin usuarios/mascotas registradas.
- Decision clave: rutas publicas del frontend son `/videos` y `/videos/{slug}`; API del ERP vive bajo `/ecommercePublico`.
- Pendiente tecnico: implementar esquema audit/plan, modelo, endpoints y fixture sin aplicar DDL.
- Pendiente operativo: confirmar proveedor inicial de video, probablemente YouTube, y crear los primeros thumbnails.
- Riesgo: construir demasiado grande; mantener fase 1 limitada a videos publicados, producto principal, relacionados, categoria y analytics anonimo.
- Siguiente paso recomendado: implementar contrato/fixtures y endpoints read-only antes de CMS completo.
