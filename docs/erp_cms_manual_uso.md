# CMS - Manual de uso operativo

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-10  
Estado: Manual vivo por secciones del modulo CMS

## Proposito

Este manual explica como usar el modulo CMS del ERP para administrar contenido ecommerce y, despues, plantillas frontend. Se actualiza por seccion conforme cada parte queda lista.

## Cambio de rumbo 2026-08-13

El CMS ya no debe enfocarse en un constructor generico tipo Wix/WordPress. El camino principal ahora es adaptar el CMS al frontend ecommerce actual y al contrato:

`C:\xampp\htdocs\frontend\ecommerce-publico\docs\CONTRATO_CMS_FRONTEND_ECOMMERCE.md`

Flujo de coordinacion con frontend:

- El frontend documentara en ese contrato las secciones nuevas o modificadas que necesiten contenido administrable.
- El CMS debe implementar cada necesidad como una seccion concreta y usable del panel, por ejemplo marcas destacadas, categorias destacadas o bloques editoriales de Home.
- Antes de crear campos nuevos, revisar el contrato para respetar nombres, estructura JSON, rutas esperadas y reglas de render.
- El CMS no debe inventar contenido de catalogo: solo administra la capa editorial, imagenes, orden, textos, CTA y referencias que el frontend consumira por API.
- Cuando una seccion quede implementada, este manual debe indicar como capturarla, como publicarla y como verificar que la API ya la entrega.

Ruta principal:

- `/cms`
- `/cms/frontend/home`

Esta pantalla organiza lo que realmente necesita el frontend actual:

- `global`: header, footer, WhatsApp y SEO.
- `home`: hero carrusel, categorias destacadas, productos destacados, colecciones y banners.
- `categoria`: imagen banner, imagen card y destacados.
- `producto`: galeria, badges comerciales y recomendados.
- `carrito`: textos, pasos y mensaje base WhatsApp.
- `estados vacios`: sin resultados, carrito vacio y mensajes publicos.

Las pantallas de constructor generico quedan fuera del flujo principal.

## Estructura operativa del CMS Frontend

El CMS Frontend se organiza por paginas reales del ecommerce, no por contratos tecnicos:

- `/cms/frontend/home`: portada del ecommerce.
- `/cms/frontend/categorias`: banners e imagenes editoriales por categoria.
- `/cms/frontend/producto`: galeria, badges y recomendados.
- `/cms/frontend/carrito`: textos, pasos y CTA de WhatsApp.
- `/cms/frontend/global`: header, footer, WhatsApp y SEO.
- `/cms/media`: biblioteca de imagenes y archivos del CMS.

La ruta `/cms` es el mapa general del CMS Frontend. Desde ahi se abren vistas dedicadas por pagina o grupo, por ejemplo `/cms/frontend/home`.

La captura de imagenes no debe quedarse en pegar URL manual para siempre. El siguiente paso es preparar `CMS > Media / Archivos` como biblioteca para subir, seleccionar, reutilizar, archivar y limpiar imagenes.

## CMS > Frontend actual > Home hero carrusel

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.hero_carrusel` ya tiene editor local operativo.
- Permite editar configuracion general: visible, autoplay, intervalo y estilo.
- Permite editar slides: titulo, subtitulo, eyebrow, imagen desktop, imagen mobile, alt, CTA principal y CTA secundario.
- Permite agregar, duplicar, ocultar/mostrar y eliminar slides.
- Actualiza el `Preview JSON esperado` con el contrato que consumiria el frontend.
- Los campos de imagen tienen boton `Media` para elegir una imagen desde la biblioteca local de `/cms/media`.
- Todavia no guarda en BD ni publica endpoint final; es el primer paso para cerrar el contrato exacto antes de persistir.

Reglas para usarlo:

- Toda imagen debe tener `alt`.
- Hero desktop recomendado: 1920x820 px.
- Hero mobile recomendado: 768x980 px.
- URLs de botones deben ser rutas publicas del frontend, por ejemplo `/#productos`, `/buscar`, `/categoria/{slug}`.

## CMS > Frontend > Global

Ruta: `/cms/frontend/global`.

Estado actual:

- Pantalla operativa inicial para configuracion global del ecommerce.
- Permite editar datos del negocio: nombre comercial, razon social, slogan, descripcion, WhatsApp, telefono y emails.
- Permite preparar logo principal, logo blanco, favicon e imagen OG usando campos con boton `Media`.
- Permite capturar direccion publica, URL de Google Maps, embed, latitud y longitud.
- Permite editar horarios visibles de forma inicial.
- Permite capturar redes sociales.
- Permite editar SEO global: `site_name`, `title_default`, `description_default`, `og_image_default` y `robots_default`.
- Permite preparar menu principal y columnas de footer como JSON controlado.
- Genera `Preview JSON esperado`.
- Guarda borrador local automaticamente mientras capturas para evitar perdida al recargar.
- Incluye boton `Guardar borrador local` para confirmar la copia del navegador.
- Incluye boton `Cargar publicado` para recuperar lo que ya entrega `/ecommercePublico/configuracion_inicial`.
- Incluye boton `Ver API publicada` para revisar si el frontend ya recibe nombre, logo, favicon, mapa y SEO.
- Ya cuenta con boton `Guardar y publicar global`, que guarda el bloque tecnico `frontend_global_publicado`.
- `/ecommercePublico/configuracion_inicial` entrega la llave `cms_global` cuando hay configuracion global publicada.

Reglas para usarlo:

- No guardar secretos, tokens ni llaves privadas.
- No guardar rutas internas del ERP como imagen publica.
- `/ecommercePublico/configuracion_inicial` sigue siendo el endpoint principal de arranque del frontend.
- Para logos, favicon e imagen OG, usar imagenes marcadas como `Servidor BD`; las temporales locales no salen en API.
- Google Maps acepta dos capturas: puedes pegar el iframe completo de Google Maps o pegar solo el URL del atributo `src`. El CMS extrae y guarda `mapa.embed_url` limpio.
- Para logo principal usa `Uso: Global` y `Tipo: Logo principal`.
- Para logo blanco usa `Uso: Global` y `Tipo: Logo blanco`.
- Para favicon usa `Uso: Global` y `Tipo: Favicon`; se permite archivo `.ico`.
- Para la imagen social/SEO usa `Uso: Global` y `Tipo: Imagen social SEO`.

### WhatsApp multi contacto

Ruta: `/cms/frontend/global`, seccion `global_whatsapp_chat`.

Uso:

- Activa o desactiva todo el modulo con `Modulo activo`.
- Captura titulo, subtitulo, texto del boton y mensaje default.
- Agrega uno o varios contactos con `Agregar contacto`.
- Cada contacto visible necesita nombre y telefono en formato internacional sin espacios. Para Mexico usa `521XXXXXXXXXX`.
- Si el mensaje del contacto queda vacio, el frontend puede usar `mensaje_default`.
- El avatar es opcional; si lo usas, selecciona una imagen guardada en Media CMS. Recomendado: WebP/PNG cuadrado 400x400.
- Usa `Guardar y publicar WhatsApp` para que salga en la API.
- Usa `Ver API global` para validar `GET /ecommercePublico/contenido_pagina?pagina=global`.

Contrato publicado:

- Slot: `global.whatsapp_chat`.
- Tipo: `whatsapp_chat`.
- Layout: `floating_multi_contact`.
- Campo directo para frontend: `whatsapp_contactos`.
- Cada contacto publicado incluye `id`, `nombre`, `telefono`, `etiqueta`, `descripcion`, `horario`, `mensaje_default`, `activo` y `orden`.
- El frontend debe ordenar por `orden`, mostrar solo `visible=true` y no mostrar contactos sin telefono valido.
- Cada contacto abre `https://wa.me/{telefono}?text={mensaje_url_encoded}`.
- Si el modulo esta desactivado, el frontend no debe mostrar el boton flotante.
- `/ecommercePublico/configuracion_inicial` tambien expone `depurar.whatsapp_contactos` como acceso rapido; si viene vacio, frontend puede usar su fallback `whatsapp_numero_principal`.

## CMS > Frontend > Navegacion

Ruta: `/cms/frontend/navegacion`.

Estado actual:

- Pantalla operativa inicial para header y footer del ecommerce.
- Permite editar la franja superior o topbar.
- Permite administrar enlaces del menu principal: etiqueta, tipo, URL, visible, subir, bajar y eliminar.
- Permite administrar columnas del footer y sus links.
- Permite preparar un CTA global del footer.
- Genera `Preview JSON esperado` orientado a `/ecommercePublico/configuracion_inicial`.

Reglas para usarlo:

- Usar solo rutas publicas del frontend, por ejemplo `/`, `/categorias`, `/contacto` o `/categoria/peces`.
- No usar rutas internas `/cms/*`, `/app/*`, `/public/assets/*` ni rutas fisicas del ERP.
- `Categorias` puede quedar como tipo especial para que despues el frontend lo alimente desde `/ecommercePublico/categorias`.
- La captura actual es local; persistencia real de navegacion queda pendiente.

## CMS > Frontend > Catalogo

Ruta: `/cms/frontend/catalogo`.

Estado actual:

- Pantalla operativa para la capa editorial del listado publico de productos.
- Permite editar titulo, subtitulo, texto superior, CTA, SEO y textos de sin resultados.
- Publica el bloque `catalogo_configuracion` en el slot `catalogo.encabezado`.
- El endpoint publico de verificacion es `/ecommercePublico/cms_frontend?pagina=catalogo`.
- No cambia productos, filtros, precios, disponibilidad ni inventario.

Reglas para usarlo:

- Usa `Guardar borrador` si solo quieres conservar el avance en este navegador.
- Usa `Guardar y publicar catalogo` para que frontend pueda recibirlo por API.
- Usa `Ver API publicada` para confirmar que existe contenido en `catalogo.encabezado`.
- El campo `Texto superior` permite HTML basico: `p`, `br`, `strong`, `b`, `em`, `i`, `ul`, `ol`, `li`.
- No pegues scripts, iframes ni estilos en linea.
- El texto de sin resultados debe ser publico y amable; no debe mencionar ERP, stock exacto ni procesos internos.

## CMS > Frontend > Categorias

Ruta: `/cms/frontend/categorias`.

Estado actual:

- Pantalla operativa inicial para enriquecer categorias publicas.
- Permite preparar titulo y subtitulo del listado de categorias.
- Permite controlar si las categorias se muestran en Home y en menu.
- Separa la captura en dos componentes: `Categorias destacadas` para cards/vitrinas y `Banners por categoria` para landings de categoria.
- `Categorias destacadas` administra ID ERP, path slug, titulo, subtitulo, descripcion SEO, URL publica, imagen card, alt card, visible, destacado y orden.
- `Banners por categoria` administra ID ERP, path slug, titulo/subtitulo de banner, URL canonica, banner desktop, banner mobile, alt banner, activo y herencia.
- Permite seleccionar una categoria real desde el catalogo publico para completar ID ERP, titulo, slug, URL publica y alt automaticamente.
- Permite seleccionar imagen card, `Banner principal ecommerce` y banner mobile desde `Media`.
- Permite seleccionar `Banner ecommerce mobile`; si queda vacio, la API usa el banner desktop como fallback mobile.
- Permite usar imagen de categoria cuando la categoria real ya tenga una imagen editorial disponible.
- Permite definir `Heredar banner`: si una subcategoria no tiene imagen propia, `/ecommercePublico/categorias` resuelve los campos de banner usando el primer padre de la jerarquia que tenga imagen disponible.
- Genera `Preview JSON esperado` orientado a `/ecommercePublico/categorias`.
- Ya cuenta con boton `Guardar y publicar categorias`.
- Cuenta con boton `Ver API categoria`, que consulta `/ecommercePublico/categorias` y `/ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug}` para confirmar que el banner resuelto llega al frontend.
- `/ecommercePublico/categorias` conserva las categorias reales del ERP y agrega el enriquecimiento CMS cuando existe.

Reglas para usarlo:

- La categoria real debe existir en ERP/API; el CMS no crea categorias de catalogo.
- No modificar precios, inventario ni publicacion de producto desde esta pantalla.
- Cada imagen publica debe tener alt text.
- Los slugs y URLs deben corresponder a rutas publicas del frontend, por ejemplo `/categoria/peces`.
- Para jerarquias de categoria/subcategoria usa `path_slug`, por ejemplo `acuario-y-peces/alimentacion/alimentos-de-acuario`.
- Para imagenes, usar Media marcada como `Servidor BD` o una ruta publica persistente; las temporales locales no salen en API.
- Frontend debe preferir los campos directos `imagen_banner`, `imagen_banner_mobile`, `banner_alt`, `banner_titulo` y `banner_subtitulo`; `banner_ecommerce` queda como detalle compatible/diagnostico.
- Si la categoria tiene banner propio, ese gana. Si no tiene y `Heredar banner` esta en `Si`, gana el primer banner de su jerarquia padre.
- `/ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug}` usa el mismo `banner_ecommerce` resuelto para el bloque `categoria.banner`, incluyendo titulo, subtitulo, alt text y desktop/mobile.
- El endpoint publico tambien entrega campos directos para frontend: `imagen_banner`, `imagen_banner_mobile`, `imagen_card`, `banner_alt`, `banner_titulo` y `banner_subtitulo`.
- La prioridad visual por categoria es: `imagen_banner`, `banner`, `banner_url`, `imagen_portada`, `portada`, `imagen_card`, `imagen`; si no hay imagen propia, hereda desde el padre inmediato y sube hasta la categoria principal de la jerarquia.
- Usa siempre `path_slug` jerarquico y `url` canonica; no construyas banners ni rutas por `slug_corto`.
- Medidas sugeridas: banner desktop 1920 x 520 px en webp/jpg menor a 350 KB; banner mobile 900 x 760 px menor a 250 KB; card 700 x 700 px o 900 x 700 px.

## CMS > Frontend > Home > Orden y categorias destacadas

Ruta: `/cms/frontend/home`.

Orden del Home:

- La seccion `home_orden_componentes` permite acomodar la portada sin tocar codigo del frontend.
- El hero/banner principal se mantiene fijo arriba por fase 1.
- Usa las flechas para subir o bajar secciones como promos, categorias destacadas, productos, marcas, colecciones, esenciales y compra guiada.
- Puedes ocultar una seccion desde `Visible`.
- El campo `Orden` se guarda en el borrador local y se envia cuando publiques cada seccion.
- El endpoint `/ecommercePublico/contenido_pagina?pagina=home` marca `home.hero` con `fijo=true` y ordena las demas secciones por `orden`.
- Los bloques publicados incluyen `tracking.section_id`, `tracking.section_slot`, `tracking.section_type` y `tracking.section_order` para que frontend pinte atributos `data-section-*`.

Categorias destacadas Home:

- En cada tarjeta usa `Seleccionar categoria` para elegir una categoria real del catalogo publico.
- Al seleccionar, el CMS completa `categoria_id`, titulo, URL publica y `path_slug`.
- Usa `Usar imagen de categoria` para copiar `imagen_card` y `imagen_banner` cuando la categoria ya tenga imagen disponible.
- Si la imagen de categoria no te gusta, reemplazala desde el boton `Media` en `Imagen card` o `Imagen banner`.
- Para publicar, cada categoria visible necesita categoria real o slug, imagen publica y alt.

Pendiente siguiente: permitir duplicar secciones completas como varios `home.marcas` o varios `home.esenciales` independientes. Hoy se pueden publicar varias colecciones dentro de `home_colecciones` y varios items dentro de cada seccion, pero las secciones base siguen siendo una por tipo.

## CMS > Frontend > Home > Esenciales Artiani

Ruta: `/cms/frontend/home`.

Uso recomendado:

- En `home_esenciales_artiani`, selecciona la categoria principal y las categorias de cada card.
- Si la categoria real ya tiene imagen, el CMS puede copiarla al bloque con `Usar imagen de categoria`.
- Si el campo imagen esta vacio, al publicar Esenciales el CMS intenta completar la imagen desde la categoria seleccionada.
- Si la imagen de categoria no te gusta, puedes reemplazarla con una imagen de `Media` solo para esa card.
- Para estas cards se recomienda imagen editorial 1200 x 900 px o card limpia 1200 x 1200 px, segun el recorte del frontend.

Reglas:

- Cada card visible necesita titulo, URL/path_slug e imagen.
- La categoria real ayuda a llenar datos, pero el CMS no crea ni modifica categorias del ERP.
- No dupliques imagenes si ya existe una adecuada en la categoria; primero prueba `Usar imagen de categoria`.

## CMS > Frontend > Marcas

Ruta: `/cms/frontend/marcas`.

Estado actual:

- Pantalla operativa inicial para enriquecer marcas publicas.
- Permite preparar titulo y subtitulo del listado de marcas.
- Permite controlar si las marcas se muestran en Home y en menu.
- Permite administrar por marca: ID ERP, slug, titulo, subtitulo, descripcion SEO, URL publica, visible, destacado y orden.
- Permite seleccionar logo y banner desde `Media`.
- Genera `Preview JSON esperado` orientado a `/ecommercePublico/marcas`.

Reglas para usarlo:

- La marca real debe existir en ERP/API; el CMS no crea marcas de catalogo.
- No modificar precios, inventario ni publicacion de producto desde esta pantalla.
- Cada logo o banner publico debe tener alt text.
- Los slugs y URLs deben corresponder a rutas publicas del frontend, por ejemplo `/marca/eheim`.
- La captura actual es local; persistencia real queda pendiente.

## CMS > Frontend > Paginas

Ruta: `/cms/frontend/paginas`.

Estado actual:

- Pantalla operativa inicial para paginas informativas del ecommerce.
- Permite preparar titulo y subtitulo del listado de paginas.
- Permite administrar paginas como `como-comprar`, `facturacion` o `contacto`.
- Permite editar por pagina: slug, titulo, subtitulo, resumen, contenido, imagen principal, alt text, SEO title, SEO description, visible, orden y URL publica.
- Permite seleccionar imagen principal desde `Media`.
- Genera `Preview JSON esperado` orientado a `/ecommercePublico/paginas`.

Reglas para usarlo:

- Usar rutas publicas del frontend, por ejemplo `/como-comprar`.
- No pegar scripts, iframes no autorizados ni codigo externo.
- La sanitizacion estricta final se hara en backend antes de publicar.
- No usar rutas internas del ERP.
- La captura actual es local; persistencia real queda pendiente.

## CMS > Frontend > Politicas

Ruta: `/cms/frontend/politicas`.

Estado actual:

- Pantalla operativa inicial para politicas publicas del ecommerce.
- Permite preparar titulo y subtitulo del listado de politicas.
- Permite administrar avisos como privacidad, envios, devoluciones y terminos.
- Permite editar por politica: slug, titulo, resumen, contenido, version, estatus, vigencia, SEO title, SEO description, visible, orden y URL publica.
- Genera `Preview JSON esperado` orientado a `/ecommercePublico/politicas` y `/ecommercePublico/politica/{slug}`.

Reglas para usarlo:

- Mantener textos legales en `borrador` hasta revision.
- No pegar scripts, iframes no autorizados ni codigo externo.
- La sanitizacion estricta final se hara en backend antes de publicar.
- No usar rutas internas del ERP.
- La captura actual es local; persistencia real queda pendiente.

## CMS > Frontend actual > Home categorias destacadas

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.categorias_destacadas` ya tiene editor operativo.
- Permite editar titulo, subtitulo, visibilidad, variante visual y columnas desktop/mobile.
- Permite editar tarjetas de categoria: `categoria_id`, `slug`, titulo, subtitulo, URL publica, imagen card, imagen banner y alt text.
- Permite agregar, duplicar, ocultar/mostrar y eliminar tarjetas.
- Actualiza el `Preview JSON esperado` dentro de `secciones` para que el frontend pueda renderizar un grid de categorias.
- Los campos `imagen_card` e `imagen_banner` tienen boton `Media` para elegir imagen desde Media CMS.
- El boton `Guardar y publicar categorias Home` persiste la seccion en BD y la expone en `/ecommercePublico/contenido_pagina?pagina=home`, slot `home.categorias`.
- El boton `Ver API publicada` consulta la API publica y muestra las categorias que recibira el frontend.

Reglas para usarlo:

- La categoria real se administra en catalogo; el CMS solo decide como se muestra en Home.
- Usa `slug` estable, por ejemplo `peces`, `perros`, `gatos`.
- Usa URL publica del frontend, por ejemplo `/categoria/peces`.
- Si usas imagenes, selecciona archivos con etiqueta `Servidor BD`.
- `imagen_card` es para la tarjeta de Home.
- `imagen_banner` queda preparada para reutilizarla despues en pagina de categoria.
- Toda tarjeta visible debe tener `alt`.

## CMS > Frontend actual > Home productos destacados

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.productos_destacados` ya tiene editor operativo.
- Permite editar titulo, subtitulo, visibilidad, limite de productos, variante visual y CTA.
- Permite elegir fuente por `criterio automatico` o `lista manual`.
- En modo criterio se puede indicar criterio, categoria slug y marca slug.
- En lista manual se pueden capturar referencias por `producto_id`, `sku` o `slug`.
- Permite agregar, duplicar y eliminar referencias manuales.
- Actualiza el `Preview JSON esperado` con `fuente`, `limite`, `cta` y `config`.
- El boton `Guardar y publicar productos Home` persiste la vitrina en BD y la expone en `/ecommercePublico/contenido_pagina?pagina=home`, slot `home.destacados`.
- El boton `Ver API publicada` consulta la API publica y muestra el modo, criterio, limite y referencias manuales que recibira el frontend.

Reglas para usarlo:

- El CMS no edita precio, stock, inventario ni publicacion del producto.
- Si quieres una vitrina general, usa `criterio automatico`.
- Si quieres forzar una seleccion concreta, usa `lista manual` y referencia productos por SKU, ID o slug.
- El frontend es quien resuelve los productos finales desde la API publica de catalogo/producto.
- No mostrar disponibilidad ni stock exacto en esta seccion.
- El texto del CTA debe llevar a una ruta publica, por ejemplo `/#productos`, `/buscar` o `/categoria/peces`.

## CMS > Frontend actual > Home colecciones de productos

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.coleccion_productos` ya tiene editor operativo como grupo de colecciones repetibles.
- Permite editar titulo/subtitulo general, visibilidad y variante del grupo.
- Permite crear varias colecciones independientes, por ejemplo novedades, basicos, destacados o campañas.
- Cada coleccion permite titulo, subtitulo, criterio, categoria slug, marca slug, limite, CTA y variante visual.
- Permite capturar referencias manuales separadas por coma en `SKUs/IDs/slugs manuales`.
- Permite agregar, duplicar, ocultar/mostrar y eliminar colecciones.
- Actualiza el `Preview JSON esperado` con `items`, donde cada item es una vitrina que el frontend puede renderizar.
- El boton `Guardar y publicar colecciones` persiste el grupo en BD y lo expone en `/ecommercePublico/contenido_pagina?pagina=home`, slot `home.destacados`.
- El boton `Ver API publicada` consulta la API publica y muestra las colecciones que recibira el frontend.
- El frontend resolvera los productos finales desde API publica segun el criterio o referencias manuales.

Como decidir:

- Usa `Productos destacados` cuando quieres una sola vitrina principal de Home.
- Usa `Colecciones` cuando necesitas varias filas o carruseles, por ejemplo `Temporada acuario`, `Novedades`, `Basicos para perro`.
- `Productos destacados` y `Colecciones` pueden convivir en el slot `home.destacados`; productos usa el orden principal y colecciones se publica despues.
- Si la coleccion cambia con frecuencia por campaña, usa `codigo` estable y cambia titulo, criterio o referencias manuales.
- El CMS no edita productos, precios, stock ni inventario.

## CMS > Frontend actual > Home banner

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.banner` ya tiene editor local operativo como banner de Home.
- Por ahora esta pensado como imagen estatica: desktop, mobile, alt text, titulo, subtitulo y CTA.
- La estructura usa `items` para que despues pueda crecer a slides si el frontend lo soporta.
- Permite visible/oculto, variante visual y modo `estatico` o `slides futuro`.
- Permite agregar, duplicar, ocultar/mostrar y eliminar items de banner.
- Los campos de imagen tienen boton `Media` para elegir imagen desde Media CMS.
- Actualiza el `Preview JSON esperado` con `banner_simple`.
- El boton `Guardar y publicar banner` persiste el banner en BD y lo expone en `/ecommercePublico/contenido_pagina?pagina=home`.
- El boton `Ver API publicada` consulta la API publica y muestra la imagen desktop, mobile, alt, titulo y fuente que ya recibira el frontend.

Reglas para usarlo:

- Usalo para la imagen estatica actual del Home.
- Primero sube o selecciona una imagen con etiqueta `Servidor BD`; las imagenes `Temporal local` solo sirven para previsualizar antes de subir.
- No lo amarres a temporada, promociones o campañas salvo que ese sea el contenido puntual del momento.
- Toda imagen visible debe tener `alt`.
- Si despues se requiere carrusel, se activa desde frontend usando los `items` ya preparados.

## CMS > Frontend actual > Home promo

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.promo` ya tiene editor operativo para avisos cortos del Home.
- Permite capturar icono, texto visible, CTA, tono visual y variante.
- El boton `Guardar y publicar promo` persiste la franja en BD y la expone en `/ecommercePublico/contenido_pagina?pagina=home`, slot `home.promo`.
- El boton `Ver API publicada` consulta la API publica y muestra los avisos que recibira el frontend.

Reglas para usarlo:

- Usalo para mensajes breves: WhatsApp, envios, entrega, horarios o aviso comercial puntual.
- No lo uses para editar precios, descuentos reales, inventario ni productos.
- Si el CTA queda vacio, el frontend puede renderizar solo el texto.

## CMS > Frontend actual > Home promos por categoria

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.promos_categoria` ya tiene editor operativo.
- Permite editar titulo/subtitulo interno, visibilidad, variante y cards promocionales.
- Cada card permite titulo, subtitulo, `categoria_id`, URL publica, `path_slug`, imagen, alt y orden.
- El boton `Guardar y publicar promos` persiste la seccion en BD y la expone en `/ecommercePublico/contenido_pagina?pagina=home`, slot `home.promos`.
- El boton `Ver API publicada` confirma si frontend ya recibe el slot.

Reglas para usarlo:

- Usar rutas canonicas completas, por ejemplo `/categoria/acuario-y-peces/alimentacion/alimentos-de-acuario`.
- `path_slug` debe coincidir sin el prefijo `/categoria/`.
- Cada card visible necesita titulo, URL e imagen.
- No crea categorias ni modifica catalogo; solo dirige al cliente a una categoria real.

## CMS > Frontend actual > Home marcas destacadas

Ruta: `/cms/frontend/home`.

Estado actual:

- `home.marcas_destacadas` ya tiene editor operativo.
- Permite editar titulo, subtitulo, orden, variante visual y categoria origen.
- La categoria origen define el contexto comercial, por ejemplo una categoria principal o subcategoria con productos publicados.
- Puede trabajar en modo `mixto`, `automatico_categoria` o `manual`.
- Al seleccionar la categoria, el bloque publica tambien `imagen`, `imagen_menu`, `imagen_card`, `imagen_banner` e `imagenes_catalogo` dentro de `categoria_contexto`.
- Cada marca manual permite `marca_id`, nombre, slug, URL publica, logo, banner opcional, alt y descripcion corta.
- Incluye el boton `Ver marcas de la categoria` para consultar una preview real antes de publicar.
- La preview muestra cada marca detectada por la categoria, su total aproximado de productos y si esta `Con imagen` o `Sin imagen`.
- Desde la preview puedes agregar al borrador manual las marcas que ya tienen logo o banner publico.
- La API publica tambien reutiliza imagenes maestras cargadas en Catalogo ERP (`erp_catalogo_marca_imagenes` / `/uploads/erp/catalogo/marcas`) aunque no se hayan duplicado en CMS Marcas.
- El boton `Guardar y publicar marcas` persiste la seccion en BD y la expone en `/ecommercePublico/contenido_pagina?pagina=home`, slot `home.marcas`.
- Si no capturas marcas manuales y usas modo automatico/mixto, el backend completa `items` con marcas reales que tienen productos publicados en esa categoria.

Reglas para usarlo:

- Primero selecciona una categoria real en `Categoria origen`.
- En `Modo`, usa `Mixto` si quieres que la categoria mande y ademas priorizar algunas marcas manuales.
- Usa `Automatico por categoria` cuando quieras mostrar todas las marcas publicadas de esa categoria. En `Limite 0=todas`, deja `0` para que el backend mande hasta 100 marcas.
- Usa `Manual` solo si vas a seleccionar marcas especificas una por una desde el selector `Marca real`.
- Si una marca aparece como `Sin imagen`, primero cargale logo o banner desde `CMS > Frontend > Marcas` o desde Media CMS antes de esperar que se vea como tarjeta visual en Home.
- Usar URL publica limpia, por ejemplo `/marca/tropical`.
- Para logos usa Media CMS con `Uso: Home` o `Global` y `Tipo: Logo principal`.
- El frontend debe leer `depurar.slots[]`, buscar el objeto con `slot: "home.marcas"` y luego renderizar `bloques[0].items[].logo` o `items[].imagen_banner`.

## CMS > Frontend actual > Home Esenciales Artiani

Ruta: `/cms/frontend/home`.

Estado actual:

- `home_esenciales_artiani` ya tiene editor operativo para `bloque_editorial_cards`.
- Permite configurar una categoria principal y hasta 3 cards de categorias destacadas.
- La categoria principal y cada card tienen selector de categoria real desde `/ecommercePublico/categorias`.
- Al seleccionar categoria, el CMS completa titulo, URL publica y `path_slug` canonico.
- Si la categoria ya tiene `imagen_banner`, `imagen_card` o `imagen_menu`, puedes usar `Usar imagen de categoria` para copiarla al bloque.
- Tambien puedes usar `Guardar borrador` aunque falte imagen; publicar sigue validando imagen porque afecta la API publica.
- Cada card permite ajustar titulo comercial, subtitulo, imagen, alt y objetivo interno.
- El boton `Guardar y publicar esenciales` persiste la seccion en BD y la expone en `/ecommercePublico/contenido_pagina?pagina=home`, slot `home.esenciales`.
- El frontend puede usar esta seccion para reemplazar el fallback editorial de Esenciales Artiani.

Reglas para usarlo:

- Maximo 3 cards visibles.
- Cada card visible necesita categoria seleccionada o `path_slug` canonico, mas imagen.
- Evita escribir URL y slug a mano; usalos solo como ajuste excepcional si el frontend necesita una ruta especial.
- Para imagen maestra de categoria se recomienda formato cuadrado `1200 x 1200` en JPG/WebP optimizado. Tu formato anterior `900 x 900` funciona, pero `1200 x 1200` da mas margen para pantallas retina.
- Para imagen especifica de Esenciales/Home se recomienda `1200 x 900` o `1600 x 1200`, porque se adapta mejor a cards editoriales.
- Usar esta seccion para guiar al cliente dentro de una familia principal, no para reemplazar el catalogo real.

## Reglas generales

- El modulo CMS vive en el menu lateral dentro de la seccion `Ecommerce`.
- La entrada `/cms` funciona como mapa general de paginas y grupos.
- Las pantallas operativas viven separadas, por ejemplo `/cms/frontend/home`, `/cms/frontend/global`, `/cms/frontend/categorias`, `/cms/frontend/marcas`, `/cms/frontend/paginas` y `/cms/frontend/politicas`.
- La entrada principal del CMS debe permitir elegir la pagina o grupo; despues cada pantalla debe mostrar solo lo que pertenece a esa pagina para evitar ruido operativo.
- La seccion `Avanzado contenido` conserva las herramientas tecnicas: bloques, slots, media, JSON y persistencia de contenido.
- La seccion `Paginas ecommerce` prepara constructor visual, plantillas de vista, layouts, componentes, variantes y activaciones.
- Wokiee es el primer tema visual activo, pero el CMS debe permitir registrar otros temas visuales futuros.
- En la fase actual `CMS > Contenido` ya puede guardar bloques, colocarlos en slots y publicar/pausar esas colocaciones. La API publica lee publicaciones vigentes desde BD y usa fallback default cuando no hay contenido publicado.
- El endpoint recomendado de arranque para el frontend es `/ecommercePublico/configuracion_inicial`.
- `/ecommercePublico/bootstrap` existe solo como alias legacy.
- El CMS no modifica catalogo, precios, inventario ni publicaciones de producto.

## CMS > Constructor de paginas ecommerce

Ruta: `/cms` o `/cms/frontend_constructor`

### Para que sirve

Esta es la pantalla principal para operar el CMS como administrador de la tienda. La idea es trabajar por pagina, no por conceptos tecnicos:

- `Home`
- `Categoria`
- `Producto`
- `Carrito`
- `Header`
- `Footer`

En esta etapa Home ya tiene maqueta funcional con secciones visuales. Las demas paginas aparecen preparadas para crearles plantilla visual y secciones administrables.

### Flujo recomendado

1. Abre `CMS > Paginas ecommerce > Constructor de paginas`.
2. Elige `Home`.
3. Revisa la maqueta central de la tienda.
4. Selecciona una seccion: portada/carrusel, promo, categorias o productos destacados.
5. Usa `Edicion rapida` para modificar titulo, texto, boton, URL, imagen desktop/mobile, alt text, vigencia y estatus.
6. Pulsa `Aplicar a maqueta` para ver el cambio inmediato sin guardar.
7. Pulsa `Guardar borrador en CMS` para guardar el bloque y colocarlo en esa seccion como borrador controlado.
8. Pulsa `Publicar seccion` cuando el contenido ya este revisado.
9. Pulsa `Pausar seccion` si quieres quitar esa seccion publicada sin borrar su contenido.
10. Usa `Agregar modulo` o la paleta `Agregar modulos` para insertar nuevas secciones en Home.
11. Usa el mapa `Secciones de esta pagina` para seleccionar, subir/bajar, ocultar/mostrar o duplicar secciones en la maqueta.
12. Pulsa `Previsualizar Home` para abrir la página completa en modo preview.
13. Revisa `Estado de Home`.
14. Usa `Abrir editor avanzado` solo cuando necesites editar campos tecnicos, JSON de cards o reglas mas finas.
15. Cuando una pagina diga `pendiente` o `preparar`, significa que todavia falta definir su plantilla visual CMS antes de administrarla completo.

### Como pensarlo

- La pagina es lo que tu administras: Home, Producto, Carrito, Header, Footer.
- La seccion es una parte visible: portada, carrusel, banner, cards, productos, CTA, footer.
- El componente es como el frontend la dibujara: HeroSlider, PromoStrip, CategoryGrid, ProductCarousel.
- El bloque es el contenido editable: imagen, titulo, texto, boton, vigencia.

El objetivo del siguiente paso es que el constructor permita editar imagenes, textos y botones directo ahi, sin obligarte a entrar al editor tecnico salvo para casos avanzados.

Estado 2026-08-13:

- Home ya tiene `Edicion rapida` dentro del constructor.
- La edicion rapida guarda bloques CMS y los coloca en la seccion como borrador.
- `Publicar seccion` usa validacion del backend antes de marcar la colocacion como publicada.
- `Pausar seccion` cambia solo el estatus de la colocacion; no borra el bloque.
- El mapa de secciones permite cambios locales de maqueta: seleccionar, subir, bajar, ocultar, mostrar y duplicar.
- Los cambios de estructura del mapa se guardan en `localStorage` como maqueta local; todavia no escriben la plantilla frontend en BD.
- `Agregar modulos` permite insertar nuevos bloques visuales en Home como maqueta local: portada/carrusel, promo, cards, productos o contenido.
- `Previsualizar Home` abre una vista completa de la pagina armada desde el constructor.
- `Estado de Home` resume secciones listas, borradores, publicadas y errores antes de conectar la pagina al frontend real.
- Producto, Carrito, Header y Footer siguen pendientes de plantilla visual administrable.

### Estructura tipo constructor

El flujo buscado para el modulo es:

1. Abrir la pagina que quieres trabajar, por ejemplo `Home`.
2. Ver la pagina armada en el centro.
3. Agregar modulos visuales desde una paleta.
4. Ordenar, ocultar, mostrar o duplicar secciones.
5. Editar textos, imagenes, botones y vigencia.
6. Previsualizar la pagina completa.
7. Publicar secciones listas.
8. Despues conectar el frontend real para consumir solo lo publicado.

En esta fase, el contenido ya puede guardarse y publicarse por seccion. La estructura agregada como modulo nuevo todavia es maqueta local hasta activar persistencia real de plantillas frontend.

### Estado de Home

El panel `Estado de Home` aparece arriba de la maqueta y sirve como checklist operativo.

- `Listas`: secciones visibles con contenido y sin bloqueos locales.
- `Borrador`: secciones con contenido guardado o preparado, pero no publicadas.
- `Publicadas`: secciones marcadas como publicadas dentro del CMS.
- `Errores`: problemas que bloquean publicar, por ejemplo falta de contenido, falta de alt text o vigencia invalida.

Cada tarjeta de seccion se puede pulsar para seleccionarla en el constructor.

En `CMS > Frontend > Home` tambien existe el panel `Estado publicado de Home`. Ese panel consulta `/ecommercePublico/contenido_pagina?pagina=home` y muestra lo que realmente esta publicado para frontend por slot:

- `home.hero`: banner principal.
- `home.promo`: franja de avisos.
- `home.categorias`: categorias destacadas de portada.
- `home.destacados`: productos destacados y colecciones.

Flujo recomendado:

1. Edita la seccion en Home.
2. Pulsa `Guardar y publicar`.
3. Revisa `Estado publicado de Home`.
4. Usa `Ver detalle` o `Ver API publicada` para confirmar que frontend ya recibe el contenido.

## CMS > Editor avanzado de contenido

Ruta: `/cms/contenido`

### Para que sirve

Esta pantalla es la mesa avanzada de trabajo editorial. Permite armar y revisar el contenido que despues consumira el frontend ecommerce por API:

- banners
- tiras promocionales
- cards con imagen
- colecciones de productos
- contenido HTML seguro
- CTAs
- orden de bloques
- estatus
- vigencia

### Flujo recomendado

1. Selecciona la pagina:
   - `Home`
   - `Categoria`
   - `Catalogo`

2. Si la pagina es `Categoria`, captura la categoria de prueba.

3. Verifica la plantilla activa. En esta fase normalmente sera `artiani_default`.

4. Revisa el `Resumen editorial`.
   - `Bloques`: total de bloques en preview local.
   - `Publicados`: bloques listos conceptualmente para publicar.
   - `Borrador`: bloques pendientes.
   - `Pausados`: bloques que no deberian mostrarse.
   - `Vigentes`: bloques dentro del rango actual.
   - `Futuros`: bloques programados.
   - `Vencidos`: bloques que requieren revision.
   - `Sin vigencia`: bloques sin fecha inicial/final.

5. Si quieres ver como se acomodaria visualmente, abre `/cms/frontend_constructor`.
   - Contenido no es la tienda final.
   - Contenido solo administra datos editoriales.
   - El constructor visual muestra plantilla, secciones, componentes y slots.

6. Selecciona un slot en el panel izquierdo.

7. Revisa `Publicabilidad por slot`.
   - `Publicable`: el slot cumple reglas locales.
   - `Con alertas`: puede revisarse, pero tiene advertencias.
   - `No publicable`: tiene errores que deben corregirse.
   - `Vacio opcional`: el slot puede quedarse sin bloques.
   - `Incompleto`: falta contenido requerido.

8. En `Bloques del slot`, revisa los bloques existentes.

9. Usa las acciones locales:
   - editar
   - duplicar
   - subir/bajar orden
   - pausar
   - quitar

10. En el editor, ajusta:
   - tipo de bloque
   - estatus
   - titulo
   - subtitulo/texto
   - CTA texto/URL
   - imagen desktop/mobile
   - alt text
   - source endpoint
   - vigencia desde/hasta
   - payload HTML seguro/items JSON

11. Pulsa `Aplicar a preview`.

12. Pulsa `Validar`.

13. Guarda borrador local si quieres conservar el preview en el navegador.
    - Ese borrador puede revisarse despues en `/cms/frontend_constructor`.

14. Pulsa `Guardar borrador en BD` cuando el bloque ya este listo para persistirse.
    - Guarda solo el bloque activo.
    - Si el estatus visual era `Publicado`, el bloque base en BD se guarda como `Borrador`; la publicacion se hace sobre el slot, no sobre el bloque base.
    - No coloca el bloque en un slot publico.
    - No cambia `/ecommercePublico/contenido_pagina`.

15. Usa `Biblioteca BD` para reutilizar bloques guardados.
    - Pulsa `Recargar BD`.
    - Elige `Cargar` en un bloque compatible con el slot activo.
    - El bloque se agrega al preview del slot y queda listo para editarse o guardarse de nuevo.
    - Esto no publica el bloque; solo lo carga al espacio de trabajo.

16. Usa la accion `Pausar` sobre un bloque guardado en BD cuando no quieras seguirlo usando.
    - Si el bloque ya tiene `id_bloque`, el cambio se guarda en BD.
    - Al volver a pulsar la accion, regresa a `Borrador`.
    - No existe publicacion real desde este boton.

17. Pulsa `Colocar en slot BD` para armar la pagina interna.
    - Requiere que el bloque ya este guardado en BD.
    - Guarda una publicacion interna en el slot activo, con pagina, contexto, orden y vigencia.
    - Despues abre `/cms/frontend_constructor` para ver la maqueta visual construida desde BD interna.
    - Todavia no aparece en API publica hasta pulsar `Publicar slot`.

18. Pulsa `Publicar slot` cuando esa colocacion ya este revisada.
    - Cambia solo la publicacion del slot a `publicado`.
    - No cambia el bloque base ni productos del catalogo.
    - La API publica podra leerla si esta vigente y el bloque base no esta pausado.
    - El servidor puede bloquear la publicacion si faltan campos minimos o hay HTML no permitido.

19. Pulsa `Pausar slot` si quieres retirar temporalmente esa colocacion dentro del CMS.
    - Cambia la publicacion del slot a `pausado`.
    - Conserva el historial y permite volver a publicarla despues.

### Importante

- `Aplicar a preview` no guarda en BD.
- `Borrador local` usa el navegador, no la base de datos.
- `Guardar borrador en BD` guarda el bloque en `erp_ecommerce_contenido_bloques`.
- `Biblioteca BD` lista bloques `borrador`/`pausado` desde `erp_ecommerce_contenido_bloques`.
- `Pausar` cambia estatus real solo cuando el bloque ya esta guardado en BD.
- `Colocar en slot BD` guarda la relacion bloque-slot para preview administrativo.
- `Publicar slot` marca esa relacion como publicada dentro del CMS.
- `Publicar slot` valida tambien en backend; la UI no es la autoridad final.
- `Pausar slot` pausa esa relacion dentro del CMS.
- `Restaurar defaults` vuelve al contenido default read-only.
- La vista visual esta separada en `/cms/frontend_constructor`.
- El constructor visual no genera archivos ni HTML productivo para el frontend.
- Subir media estructurada, ampliar sanitizacion HTML y completar el renderer final siguen pendientes.

### Errores comunes

- Crear un bloque de tipo no permitido en el slot.
- Dejar un hero/banner sin `alt text`.
- Exceder el maximo de bloques de un slot.
- Poner `Vigente hasta` antes de `Vigente desde`.
- Dejar una coleccion de productos sin endpoint source.
- Usar `<script>` en `content_html_safe`.

### Cierre de persistencia parcial de contenido

La seccion `CMS > Contenido ecommerce` queda preparada cuando se cumple:

- puedes cargar pagina/contexto/plantilla
- puedes seleccionar slots
- puedes crear y editar bloques locales
- puedes ordenar, duplicar, pausar y quitar bloques en preview
- puedes revisar resumen editorial
- puedes revisar publicabilidad por slot
- puedes validar reglas locales
- puedes guardar/cargar borrador local del navegador
- puedes guardar el bloque activo en BD como borrador
- puedes recargar la biblioteca BD y cargar bloques guardados al slot activo
- puedes pausar/reactivar bloques guardados en BD
- puedes colocar bloques guardados en slots internos para que el constructor visual pinte una pagina desde BD
- puedes revisar/exportar el JSON desde `CMS > Preview JSON`

Sigue bloqueado hasta autorizacion:

- publicar contenido real
- subir media real
- activar POST de publicaciones/media/estatus
- validar en `/ecommercePublico/contenido_pagina` si la fuente es `bd_publicada` o fallback `default_readonly`

Siguiente paso operativo:

1. Revisar visualmente las pantallas de CMS Contenido.
2. Autorizar respaldo y DDL cuando se quiera activar persistencia.
3. Activar endpoints POST con CSRF/auditoria.
4. Validar endpoints publicos con BD publicada y fallback default.

## CMS > Slots

Ruta: `/cms/slots`

### Para que sirve

Esta pantalla muestra la estructura de espacios disponibles dentro de la plantilla activa. Sirve para entender donde puede aparecer cada bloque antes de editar contenido.

Vista estructural read-only: ayuda a revisar reglas, limites y tipos permitidos, pero no captura ni guarda bloques.

Un slot es un espacio de la plantilla, por ejemplo:

- `home.hero`
- `home.promo`
- `home.categorias`
- `home.destacados`
- `categoria.banner`
- `categoria.productos`
- `catalogo.encabezado`

### Flujo recomendado

1. Selecciona la pagina:
   - `Home`
   - `Categoria`
   - `Catalogo`

2. Si revisas `Categoria`, captura una categoria de prueba.

3. Confirma la plantilla activa.

4. Revisa el listado `Slots declarados`.

5. Haz clic en un slot.

6. Revisa `Detalle del slot`.
   - `Pagina`: pagina donde aplica.
   - `Maximo`: limite de bloques permitidos.
   - `Bloques preview`: bloques actuales cargados en el preview.
   - `Contexto`: alcance del slot.
   - `Requerido/Opcional`: si debe tener contenido para publicar.
   - `Tipos permitidos`: que tipo de bloque puede colocarse ahi.

7. Revisa el `JSON de pagina` para confirmar como el frontend recibira la estructura.

8. Si necesitas editar contenido de un slot, ve a `/cms/contenido`.

### Como interpretar el detalle

- Si un slot es `Requerido`, debe tener al menos un bloque publicable antes de publicar contenido real.
- Si `Maximo` es `1`, solo debe existir un bloque en ese slot.
- Si un tipo no aparece en `Tipos permitidos`, no debe usarse en ese slot.
- `Bloques preview` indica el contenido actual del preview local/default; todavia no representa BD publicada.

### Importante

- Esta pantalla no edita bloques.
- Esta pantalla no guarda cambios.
- Esta pantalla ayuda a evitar errores antes de capturar contenido.
- Los limites y tipos permitidos deben respetarse tambien en backend cuando se active persistencia real.

### Errores comunes

- Intentar usar `product_collection` en un slot que solo acepta `hero_banner`.
- Poner mas bloques que el maximo permitido.
- Dejar vacio un slot requerido.
- Confundir slot con componente visual frontend. El slot define el espacio; el componente frontend define como se renderiza.

## CMS > Plantillas contenido

Ruta: `/cms/plantillas`

### Para que sirve

Esta pantalla muestra la plantilla de contenido activa del CMS. No es la plantilla visual del frontend; es la estructura editorial que define:

- paginas soportadas
- slots disponibles
- tipos de bloque permitidos
- esquema propuesto de persistencia
- manifest read-only
- contratos internos y publicos

La plantilla base actual es `artiani_default`.

### Diferencia contra plantillas frontend

- `Plantillas contenido` define espacios y reglas editoriales.
- `Plantillas frontend` define layouts, componentes visuales y variantes.
- El contenido dice que va en cada slot.
- El frontend decide como renderiza cada slot con componentes seguros.

Ejemplo:

- Slot de contenido: `home.hero`
- Tipo de bloque: `hero_banner`
- Componente frontend futuro: `HeroSlider`

### Flujo recomendado

1. Abre `/cms/plantillas`.

2. Revisa los KPIs:
   - plantilla activa
   - slots declarados
   - tipos de bloque
   - estado de persistencia

3. Revisa `Contratos del CMS`.
   - estado actual
   - endpoints internos
   - endpoints publicos futuros

4. Revisa `Tipos de bloque`.
   - `hero_banner`
   - `category_banner`
   - `product_collection`
   - `promo_strip`
   - `image_card_grid`
   - `content_html_safe`

5. Revisa `Esquema propuesto`.
   - tablas existentes
   - tablas pendientes
   - DDL total propuesto

6. Revisa `Manifest read-only`.

7. Si necesitas editar bloques, ve a `/cms/contenido`.

8. Si necesitas entender slots, ve a `/cms/slots`.

### Como interpretar los tipos de bloque

- `hero_banner`: banner principal, normalmente en `home.hero`.
- `category_banner`: banner de categoria, normalmente en `categoria.banner`.
- `product_collection`: coleccion dinamica desde catalogo publico.
- `promo_strip`: texto promocional o aviso corto.
- `image_card_grid`: tarjetas con imagen.
- `content_html_safe`: contenido editorial sanitizado.

### Importante

- Esta pantalla no crea plantillas reales en BD todavia.
- Esta pantalla no modifica frontend.
- Esta pantalla no permite HTML/CSS/JS libre.
- El esquema propuesto no se aplica hasta respaldo y autorizacion.
- Para cambiar la vista visual del ecommerce se usara la seccion `CMS > Frontend`.

### Errores comunes

- Pensar que `artiani_default` ya es el diseno visual final del ecommerce.
- Confundir tipo de bloque con componente frontend.
- Tomar el DDL propuesto como aplicado.
- Intentar resolver media o imagenes desde esta pantalla.

## CMS > Media / Archivos

Ruta: `/cms/media`

### Para que sirve

Esta pantalla administra la biblioteca Media CMS para el frontend. Su objetivo es dejar de pegar URLs manualmente y subir imagenes publicas controladas desde el panel.

En la fase actual:

- sube imagenes JPG, PNG o WebP al servidor
- guarda la referencia en `erp_ecommerce_media_archivos`
- valida tipo MIME real, extension, peso maximo de 2 MB, dimensiones y hash SHA-256
- exige `alt text`
- clasifica por uso: Home, Categoria, Producto, Global o Blog futuro
- acepta JPG, PNG, WebP e ICO para favicon
- distingue origen `Servidor BD` contra `Temporal local`
- permite eliminar temporales locales del navegador
- permite eliminar archivos reales de Media CMS solo si no estan usados por contenido publicado
- sincroniza con BD y quita automaticamente referencias antiguas `Servidor BD` que ya no existan en servidor
- incluye boton `Limpiar temporales` para borrar de la galeria lo que solo vive en el navegador
- clasifica por tipo: logo principal, logo blanco, favicon, imagen social SEO, banner, hero, card, thumbnail o editorial
- lista imagenes guardadas desde `/cms/media_admin_listar_erp`
- permite copiar una referencia estructurada para usarla en secciones del CMS

Tambien consulta `/cms/media_admin_preflight_erp`, que muestra carpeta publica, tablas, limites y endpoints disponibles.

Importante: la subida real ya esta activa; editar metadatos, archivar en BD y registrar usos reales siguen pendientes.

### Flujo recomendado

1. Abre `/cms/media`.

2. Pulsa `Archivo` y selecciona una imagen.

3. Elige `Uso`:
   - `Home`
   - `Categoria`
   - `Producto`
   - `Global`
   - `Favicon`
   - `Blog futuro`

4. Elige `Tipo`:
   - `Logo principal`
   - `Logo blanco`
   - `Favicon`
   - `Imagen social SEO`
   - `Banner`
   - `Hero`
   - `Card`
   - `Thumbnail`
   - `Editorial`

5. Captura `Alt text`.

6. Pulsa `Subir a biblioteca`.

7. Selecciona la imagen en `Biblioteca`.

8. Revisa `Detalle`.

9. Usa `Copiar referencia` si necesitas revisar el contrato que despues usara Home.

10. Usa `Copiar referencia` para revisar el contrato con `media_id`, `codigo`, `url`, `alt`, `uso` y `tipo`.

11. Por ahora `Archivar` y `Limpiar archivados` solo afectan la vista local; el archivado real de BD queda pendiente.

### Como usarla desde Home

Hay dos caminos:

- Cargar la imagen directamente desde el modal de Home.
- Administrar la biblioteca completa desde `/cms/media`.

Flujo rapido desde Home:

1. Abre `/cms/frontend/home`.

2. En Hero, Categorias o Banner, pulsa el boton `Media` junto al campo de imagen.

3. En el modal puedes:
   - cargar una nueva imagen
   - capturar `alt text`
   - elegir uso/tipo
   - subirla a Media CMS y usarla inmediatamente
   - seleccionar una imagen ya existente desde la galeria disponible

4. Si eliges una imagen existente, primero se muestra en `Preview seleccionado`.

5. Pulsa `Usar imagen seleccionada` para aplicarla al campo.

6. El CMS rellena la imagen y, si el campo `alt` esta vacio, usa el `alt text` capturado.

7. Revisa el `Preview JSON esperado`.

La galeria del modal muestra previsualizaciones desde Media CMS. El campo de texto es solo `Filtro opcional`; no necesitas depender del nombre del archivo si la imagen se reconoce visualmente.

Nota API: mientras no exista una publicacion CMS formal para `home.hero`, `/ecommercePublico/contenido_pagina?pagina=home` puede usar como fallback la ultima imagen activa de Media CMS con `uso=home` y `tipo=hero`, `banner` o `principal`.

### Imagenes Home y API publicada

Regla general: al publicar Home, no pegues `data:image`, `blob:`, `file:`, rutas `C:\...`, `/app/...`, `/storage/...` ni temporales. Usa una URL persistente de Media CMS, normalmente `/assets/media/cms/ecommerce/...`. Si una imagen ya existe en Media CMS, abre el modal, previsualizala y pulsa `Usar imagen seleccionada`; no la vuelvas a subir.

- Hero / Banner principal: publica en `home.hero`. Debe entregar `media.imagen_desktop`, `media.imagen_mobile`, `media.alt` y tambien `items[].imagen_desktop`, `items[].imagen_mobile`, `items[].alt`. Desktop recomendado: 1920 x 700 u 800. Mobile recomendado: 900 x 1200.
- Home promos por categoria: cada item publica `imagen`, `alt`, `url`, `path_slug` y `categoria_id`. Si seleccionas una categoria real, el panel completa URL y path; si la categoria ya tiene imagen puedes usar `Usar imagen de categoria`, y si no te gusta puedes reemplazarla con Media CMS. Medidas: 1200 x 900 o 1200 x 1200.
- Categorias destacadas Home: cada item publica `imagen`, `imagen_card`, `imagen_banner`, `alt`, `alt_card`, `alt_banner`, `url`, `path_slug` y `categoria_id`. No dupliques imagen si la categoria ya tiene `imagen_card` o `imagen_banner`; usa la imagen de categoria o reemplazala con Media CMS. Card: 1200 x 1200. Banner: 1920 x 500 o 600.
- Home Esenciales Artiani: publica `categoria_principal.imagen`, `categoria_principal.imagen_card`, `categoria_principal.imagen_banner` y cards con `items[].imagen`, `items[].alt`, `items[].categoria_id`, `items[].path_slug`, `items[].url`. Puedes guardar borrador aunque falten imagenes; para publicar se autocompletan desde categoria cuando existan.
- Home marcas destacadas: publica `categoria_contexto.imagen`, `imagen_menu`, `imagen_card`, `imagen_banner`, `imagenes_catalogo`, `path_slug`, `url`, y por marca `logo`, `imagen_banner`, `url`, `slug`, `marca_id`. En modo `automatico_categoria` entrega marcas reales de la categoria; `Limite 0=todas` resuelve hasta 100.

Para verificar, usa `Ver API publicada` en cada seccion. El panel consulta `/ecommercePublico/cms_frontend?pagina=home` o `/ecommercePublico/contenido_pagina?pagina=home`, muestra la fuente `bd_publicada`, cantidad de items y las primeras imagenes que llegan. Si el resumen muestra `sin imagen` o una imagen temporal, no publiques: corrige desde Media CMS o usa la imagen de categoria disponible.

Diferencias de imagen:

- Imagen de categoria: vive en el catalogo publico de categorias y sirve como base editorial para cards o banners.
- Imagen de Home: es una imagen elegida para una seccion especifica de Home; puede reemplazar a la imagen de categoria sin cambiar catalogo.
- Banner principal: es la imagen de primer impacto de `home.hero`; debe tener desktop, mobile y alt propio.

### Como interpretar la vista

- `Biblioteca` muestra imagenes disponibles para seleccionar en secciones futuras.
- `Detalle` muestra uso, tipo, formato, peso, estatus y preview.
- `Archivado` en esta fase solo cambia la maqueta local; todavia no archiva la fila en BD.
- `Quitar` elimina la entrada local del navegador, no borra la imagen del servidor.
- `Copiar referencia` genera una referencia JSON con `media_id`, `codigo`, `url`, `alt`, `uso` y `tipo`.

### Importante

- Esta pantalla ya sube archivos al servidor usando `/cms/media_admin_subir_erp`.
- Esta pantalla todavia no borra archivos fisicos.
- Esta pantalla ya guarda media en BD dentro de `erp_ecommerce_media_archivos`.
- La carpeta publica activa es `/assets/media/cms/ecommerce`.
- Las tablas del modulo son `erp_ecommerce_media_archivos` y `erp_ecommerce_media_usos`.
- El endpoint `/cms/media_admin_preflight_erp` solo lee contrato.
- El endpoint `/cms/media_admin_listar_erp` es solo lectura.
- Los endpoints `/cms/media_admin_actualizar_erp`, `/cms/media_admin_archivar_erp` y `/cms/media_admin_usos_erp` siguen bloqueados.
- No se deben guardar rutas internas del ERP para que el frontend las lea directamente.

### Errores comunes

- Agregar una imagen sin `alt text`.
- Pensar que subir una imagen ya la coloca automaticamente en Home; todavia hay que seleccionarla en la seccion correspondiente.
- Borrar una entrada local pensando que ya se borro del servidor.
- Usar imagenes demasiado pesadas sin politica de optimizacion.
- Guardar rutas internas del ERP como si fueran URLs publicas.

## CMS > Preview JSON

Ruta: `/cms/json`

### Para que sirve

Esta pantalla muestra el contrato JSON que despues consumira el frontend ecommerce. Sirve para revisar la respuesta de contenido antes de conectar persistencia real y antes de que el frontend renderice la plantilla.

### Flujo recomendado

1. Selecciona la pagina:
   - `Home`
   - `Categoria`
   - `Catalogo`

2. Si revisas `Categoria`, captura una categoria de prueba.

3. Confirma la plantilla activa.

4. Revisa `Contrato API`.
   - endpoints internos del panel
   - endpoints publicos futuros
   - modo actual read-only
   - persistencia pendiente

5. Revisa `Arranque del frontend`.
   - recomendado: `/ecommercePublico/configuracion_inicial`
   - legacy: `/ecommercePublico/bootstrap`

6. Pulsa `Previsualizar`.

7. Revisa `Respuesta JSON`.

8. Usa `Copiar` si necesitas pegar el JSON en otra herramienta.

9. Usa `Exportar` si quieres guardar un preview local como archivo `.json`.

10. Usa `Importar preview` para pegar un JSON exportado y cargarlo en la pantalla.

### Como interpretar la respuesta

- `fuente: preview_local_panel` indica que el JSON fue armado en el panel, sin persistencia real.
- `persistencia_real: false` indica que todavia no viene de BD publicada.
- `slots` contiene los espacios de la plantilla.
- Cada slot contiene sus `bloques`.
- Cada bloque tiene `tipo`, contenido, media, CTA, estatus y vigencia cuando aplique.
- `guardrails` explica que no se escribio BD, no se modifico catalogo y no se afecto inventario.

### Importante

- Esta pantalla no publica contenido.
- Exportar JSON no equivale a guardar en BD.
- Importar JSON solo modifica el preview local del navegador.
- El frontend ecommerce debe iniciar desde `/ecommercePublico/configuracion_inicial`.
- `/ecommercePublico/bootstrap` queda solo como alias legacy.
- El frontend no debe leer archivos internos del ERP.

### Errores comunes

- Tomar un JSON exportado como contenido publicado real.
- Usar `/bootstrap` como endpoint nuevo recomendado.
- Olvidar que los cambios importados son locales.
- Conectar el frontend a rutas internas `/cms/*` en lugar de rutas publicas `/ecommercePublico/*`.
- Esperar que `contenido_pagina` lea BD antes de activar persistencia real.

## CMS > Persistencia

Ruta: `/cms/persistencia`

### Para que sirve

Esta pantalla prepara y documenta la persistencia del CMS. Muestra esquema aplicado, contratos POST, alcance activo y checklist antes de publicar contenido real.

En la fase actual no ejecuta DDL desde la UI, no publica contenido y no sube media. El guardado activo se limita a bloques de contenido en borrador desde `/cms/contenido`.

Estado actual posterior a autorizacion del 2026-08-12:

- Respaldo generado: `C:\xampp\panel_db_backups\artianilocal_panel_20260812_094259_antes_cms_ecommerce_persistencia.sql`.
- DDL base aplicado: 11 tablas CMS.
- Semilla estructural aplicada:
  - `artiani_default`
  - `wokiee_artiani`
  - 7 slots de contenido
  - 3 layouts frontend
  - 6 componentes frontend
  - 3 plantillas de vista
  - 7 secciones frontend
  - 3 activaciones frontend
- Lectura interna activa:
  - `/cms/contenido_admin_manifest_erp` lee estructura de contenido desde BD semilla.
  - `/cms/frontend_admin_manifest_erp` lee tema, layouts, componentes, plantillas y activaciones desde BD semilla.
- `POST /cms/contenido_bloque_guardar_erp` esta activo para guardar bloques borrador/pausados.
- `POST /cms/contenido_bloque_estatus_erp` esta activo para pausar/reactivar bloques guardados.
- `POST /cms/contenido_publicacion_guardar_erp` esta activo para colocar bloques en slots internos como borrador/pausado.
- `GET /cms/contenido_admin_bloques_erp` esta activo para listar bloques borrador/pausados en el panel.
- Endpoint POST de publicar estatus, media y frontend siguen bloqueados.
- No hay publicaciones de contenido ni media guardada por el CMS.
- API publica sigue en fallback default/read-only.

### Flujo recomendado

1. Abre `/cms/persistencia`.

2. Revisa el aviso de alcance de persistencia.

3. Revisa `Planes DDL read-only`.
   - `CMS Contenido`: plantillas editoriales, slots, bloques, publicaciones y media.
   - `CMS Frontend`: temas visuales, layouts, componentes, plantillas de vista, secciones y activaciones.
   - tablas existentes
   - tablas pendientes
   - total de DDL propuesto

4. Revisa el `Checklist de autorizacion`.

5. Revisa `Alcance de persistencia`.
   - contenido editorial
   - frontend visual
   - API publica
   - guardrail contra catalogo/precios/inventario

6. Confirma que `contenido_bloque_guardar_erp` aparece como activo controlado y que publicaciones/frontend siguen bloqueados.

7. Revisa `Contratos del CMS` para confirmar endpoints internos y publicos futuros.

8. No intentes activar escrituras sin implementar validaciones, permisos, CSRF y auditoria.

### Checklist antes de activar escrituras

Completado para DDL base:

- Preflight read-only ejecutado.
- Respaldo externo generado y documentado.
- DDL de 11 tablas aplicado.
- Auditoria posterior: tablas faltantes `0`.
- Semilla estructural base aplicada y validada con:
  - `php storage/uat/uat_cms_seed_readonly.php`

Tablas CMS Contenido aplicadas:

- `erp_ecommerce_plantillas`
- `erp_ecommerce_plantilla_slots`
- `erp_ecommerce_contenido_bloques`
- `erp_ecommerce_contenido_publicaciones`
- `erp_ecommerce_contenido_media`

Tablas CMS Frontend aplicadas:

- `erp_ecommerce_frontend_temas`
- `erp_ecommerce_frontend_layouts`
- `erp_ecommerce_frontend_componentes`
- `erp_ecommerce_frontend_plantillas`
- `erp_ecommerce_frontend_plantilla_secciones`
- `erp_ecommerce_frontend_plantilla_activas`

Completado para primera escritura controlada:

- Respaldo externo.
- DDL base.
- Semilla estructural.
- CSRF en POST.
- Auditoria explicita para `contenido_bloque_guardar_erp`.
- Validacion de permiso `cms.editar` o puente `catalogo.editar`.
- Validacion de tipo de bloque y JSON.
- Bloqueo basico de `<script>`, handlers inline y `javascript:` en `content_html_safe`.
- Listado read-only de bloques guardados para biblioteca editorial.
- Cambio de estatus `borrador`/`pausado` con auditoria explicita.
- Publicacion interna borrador por slot/pagina/contexto para preview visual BD.
- Publicacion/pausa interna de colocaciones por slot con `contenido_publicacion_estatus_erp`, `cms.publicar`, CSRF y auditoria explicita.

Pendiente para completar contenido:

1. Definir politica de media:
   - rutas publicas
   - formatos permitidos
   - tamanos
   - nombres de archivo
   - alt text obligatorio
2. Ampliar sanitizacion HTML con lista permitida.
3. Conectar el renderer final del frontend a `plantilla_vista + contenido.slots`.
4. Usar `storage/uat/uat_cms_publico_bd_temporal_rollback.php` para validar lectura publica BD con rollback y bloqueos server-side.

### Endpoints POST futuros

Activo:

- `/cms/contenido_bloque_guardar_erp`
- `/cms/contenido_bloque_estatus_erp`
- `/cms/contenido_publicacion_guardar_erp`

Actualmente existen como contratos bloqueados:

- `/cms/contenido_publicacion_estatus_erp`
- `/cms/frontend_plantilla_guardar_erp`
- `/cms/frontend_plantilla_estatus_erp`
- `/cms/frontend_seccion_guardar_erp`
- `/cms/frontend_seccion_estatus_erp`

Los endpoints restantes deberan:

- validar sesion
- validar CSRF
- validar permisos
- validar payload
- aplicar reglas de slot/tipo/maximo
- sanitizar HTML
- registrar auditoria
- responder con el contrato JSON del ERP

### Importante

- No ejecutar DDL desde esta pantalla en la fase actual.
- No activar escrituras sin respaldo.
- No guardar rutas internas del ERP como media publica.
- No mezclar CMS con catalogo, precios, inventario o publicaciones de producto.
- No publicar contenido vencido o con errores de publicabilidad.
- No activar tema o plantilla frontend sin comprobar que sus componentes existen en el frontend ecommerce.

### Errores comunes

- Confundir plan DDL con DDL aplicado.
- Activar POST sin CSRF/auditoria.
- Guardar HTML sin sanitizar.
- Subir media sin politica de nombres/tamanos.
- Hacer que el frontend lea archivos internos del ERP.

## CMS > Paginas ecommerce / Frontend

Rutas:

- `/cms/frontend_constructor`
- `/cms/frontend_plantillas`
- `/cms/frontend_componentes`
- `/cms/frontend_activaciones`

Documento tecnico de implementacion frontend: `docs/erp_cms_frontend_renderer_contrato.md`
Plan builder visual Wokiee/Artiani: `docs/erp_cms_visual_builder_wokiee_plan.md`

### Navegacion del submodulo

La ruta `/cms` funciona como mapa general de paginas y grupos. Ahi puedes ver que existe Home, Global, Navegacion, Categorias, Marcas, Paginas y Politicas sin mezclar todavia la captura diaria.

Cuando seleccionas una pagina o grupo, el sistema abre una vista dedicada. Ejemplos:

- `/cms/frontend/home`: solo Home.
- `/cms/frontend/global`: solo configuracion global.
- `/cms/frontend/categorias`: solo categorias.
- `/cms/frontend/marcas`: solo marcas.
- `/cms/frontend/paginas`: solo paginas informativas.
- `/cms/frontend/politicas`: solo politicas.

En el menu lateral el grupo operativo se muestra como `Contenido tienda` para que el uso diario empiece por paginas reales de la tienda. Las vistas tecnicas se conservan separadas:

- `Plantillas`
- `Paginas`
- `Componentes`
- `Activaciones`

Usala para revisar el flujo completo sin volver al menu lateral:

1. `Paginas`: muestra la pagina armada con plantilla, secciones, componentes y slots.
2. `Plantillas`: define la composicion visual por pagina y queda como vista tecnica.
3. `Componentes`: valida que puede renderizar cada seccion.
4. `Activaciones`: confirma que plantilla aplica por pagina/canal/contexto.

## CMS > Frontend > Constructor visual

Nombre operativo en menu: `CMS > Paginas ecommerce > Constructor de paginas`.

Ruta: `/cms/frontend_constructor`

### Para que sirve

Esta es la pantalla donde debe vivir la parte visual del CMS y la entrada principal de `/cms`.

Muestra como se ensamblara una pagina del ecommerce usando paginas comprensibles para operacion:

- Home;
- Categoria;
- Producto;
- Carrito;
- Header;
- Footer.

Internamente cada pagina sigue usando:

- tema visual, por ejemplo `wokiee_artiani`;
- plantilla de vista, por ejemplo `wokiee_home_default`;
- secciones de la pagina;
- slots como `home.hero` o `home.destacados`;
- componentes como `HeroSlider`, `PromoStrip` o `ProductCarousel`;
- variantes visuales permitidas.

No es el frontend real y no genera HTML productivo. Es una previsualizacion administrativa para entender la estructura antes de que el frontend renderice con sus propios componentes.

Actualizacion visual 2026-08-13:

- El centro de `/cms/frontend_constructor` muestra una maqueta tipo tienda.
- La maqueta pinta header, hero, promos, cards, carrusel de productos y footer segun las secciones de la plantilla.
- Si un bloque tiene imagen desktop/mobile capturada desde `CMS > Contenido`, el hero o card la usa como referencia visual.
- Sigue sin ser el HTML productivo del ecommerce; el frontend real implementara sus componentes con la misma estructura JSON.

### Flujo recomendado

1. Primero crea o revisa contenido en `/cms/contenido`.
   - banners
   - imagenes
   - textos
   - CTAs
   - colecciones

2. Abre `/cms/frontend_constructor`.

3. Elige la pagina:
   - Home
   - Categoria
   - Producto
   - Carrito
   - Header
   - Footer

4. Revisa el canvas central.
   - Cada seccion representa un slot de la pagina.
   - Cada slot muestra el componente frontend que lo renderizara.
   - Cuando el indicador dice `Contenido conectado`, el preview ya cruzo plantilla frontend con contenido read-only de `/cms/contenido_admin_pagina_erp`.
   - Cuando el indicador dice `Borrador local conectado`, el preview esta usando el borrador guardado desde `/cms/contenido`.

5. Da clic en una seccion.
   - El inspector muestra plantilla, slot, componente, variante y bloques permitidos.
   - Tambien muestra cuantos bloques estan conectados a ese slot.
   - Usa `Editar contenido de este slot` para abrir `/cms/contenido` directamente en esa pagina/slot.

6. Usa `Edicion rapida`.
   - `Aplicar a maqueta`: actualiza el preview y guarda borrador local del navegador.
   - `Guardar borrador en CMS`: guarda el bloque en BD y lo coloca en la seccion seleccionada como borrador.
   - `Publicar seccion`: guarda el contenido actual, valida en servidor y publica la colocacion CMS si no hay bloqueos.
   - `Pausar seccion`: pausa la colocacion CMS existente.

Reglas para publicar desde el constructor:

- La seccion debe tener titulo o texto principal.
- Banners y hero necesitan `alt text`.
- La fecha `Hasta` no puede ser menor que `Desde`.
- El backend puede bloquear la publicacion aunque el preview local se vea bien.
- Publicar no modifica catalogo, precios ni inventario.

7. Revisa `Secciones de esta pagina`.
   - Te muestra el mapa de partes visibles de la pagina.
   - Home muestra portada/carrusel, franja promocional, categorias destacadas y productos destacados.
   - Puedes seleccionar una seccion desde el mapa.
   - Puedes subirla o bajarla para probar otro orden visual.
   - Puedes ocultarla o mostrarla sin borrar contenido.
   - Puedes duplicarla para ensayar una composicion de maqueta.
   - Estos cambios de estructura son locales del constructor hasta activar persistencia de plantillas frontend.
   - Producto, Carrito, Header y Footer quedan marcadas como preparadas cuando aun no tienen plantilla visual.

8. Revisa `Estado de Home`.
   - Si muestra `Con errores`, corrige las tarjetas marcadas.
   - Si muestra `Requiere cierre`, normalmente faltan publicaciones o hay secciones pausadas.
   - Si muestra `Lista para frontend`, Home ya esta consistente desde el punto de vista del CMS.

9. Revisa `Componentes disponibles`.
   - Confirma que el componente exista y tenga variantes compatibles.

10. Revisa `Como lo consumira frontend`.
   - El arranque recomendado sigue siendo `/ecommercePublico/configuracion_inicial`.
   - La pagina especifica usara `/ecommercePublico/contenido_pagina`.

11. Usa los botones de fuente de contenido:
   - `Usar borrador local`: intenta cargar el ultimo borrador guardado en el navegador desde `/cms/contenido`.
   - `Usar API read-only`: ignora el borrador local y vuelve al contenido default/API interna.

### Diferencia contra CMS Contenido

- `CMS > Contenido` administra datos editoriales.
- `CMS > Frontend > Constructor visual` muestra como se acomodarian esos datos en una plantilla.
- `CMS > Frontend > Plantillas de vista` lista y audita la estructura declarada.
- `CMS > Frontend > Componentes` audita los componentes permitidos.
- `CMS > Frontend > Activaciones` define que plantilla aplica por pagina/canal/contexto.

### Importante

- Esta pantalla es read-only.
- No cambia la tienda real.
- No guarda contenido.
- No edita archivos del frontend.
- No permite HTML/CSS/JS libre.
- El render final lo hace el proyecto frontend ecommerce.
- El CMS solo entrega JSON estructurado.

## CMS > Frontend > Plantillas de vista

Ruta: `/cms/frontend_plantillas`

### Para que sirve

Esta pantalla prepara las plantillas visuales que el frontend ecommerce podra renderizar. No edita archivos del frontend; administra un contrato JSON seguro con:

- layout
- pagina
- secciones
- slot de contenido
- componente frontend
- variante visual
- orden de aparicion

Ejemplo conceptual:

- plantilla: `wokiee_home_default`
- layout: `storefront_wokiee_v1`
- slot: `home.hero`
- componente: `HeroSlider`
- variante: `full_width`

### Flujo recomendado

1. Abre `/cms/frontend_plantillas`.

2. Revisa los KPIs:
   - layouts
   - componentes
   - plantillas
   - tema activo

3. En `Builder visual read-only`, revisa `Tema visual`.
   - Por ahora aparece `wokiee_artiani`.
   - El selector esta deshabilitado porque todavia no hay persistencia real.
   - La arquitectura permite registrar otros temas despues.

4. En la columna `Plantillas`, selecciona la pagina que quieres revisar.
   - `wokiee_home_default`
   - `wokiee_categoria_default`
   - `wokiee_catalogo_default`

5. Revisa el canvas central.
   - Muestra una simulacion local de header, secciones y footer.
   - Cada seccion representa un slot conectado a un componente frontend.
   - Esta vista no genera archivos ni HTML productivo; solo ayuda a negocio a entender la composicion.

6. Haz clic en una seccion del canvas.

7. Revisa `Inspector`.
   - plantilla
   - pagina
   - layout
   - slot
   - componente
   - variante
   - orden
   - bloques permitidos

8. Revisa `Paleta de componentes`.
   - Muestra componentes disponibles dentro del tema activo.
   - Cada componente tiene variantes permitidas.
   - En modo futuro, de aqui saldran las secciones que se podran agregar a una plantilla.

9. Revisa `Contrato renderer`.
   - endpoint de arranque
   - endpoint de pagina
   - contrato `plantilla_vista + contenido.slots`
   - guardrails contra HTML/CSS/JS libre

10. Revisa `Plantillas declaradas`.

11. Para cada plantilla, revisa:
   - codigo
   - layout
   - pagina
   - estatus
   - secciones
   - orden
   - slot
   - componente
   - variante

12. Si necesitas revisar componentes permitidos, ve a `/cms/frontend_componentes`.

13. Si necesitas editar contenido de un slot, ve a `/cms/contenido`.

### Como se consume en frontend

El frontend debe llamar endpoints publicos, no rutas internas `/cms/*`.

Flujo esperado:

1. `GET /ecommercePublico/configuracion_inicial`
2. La respuesta trae `contenido_inicial.home` con `plantilla_vista` y `slots` en modo default/read-only.
3. Para refrescar o navegar paginas especificas, usar `GET /ecommercePublico/contenido_pagina?pagina=home`.
4. El frontend usa un mapa de componentes permitidos.

Ejemplo de renderer frontend:

```js
const componentes = {
  HeroSlider,
  PromoStrip,
  CategoryGrid,
  ProductCarousel
};
```

El CMS solo manda nombres de componentes y variantes permitidas. El frontend decide como renderizarlos con codigo ya programado.

### Importante

- Esta pantalla es read-only en la fase actual.
- No edita archivos `.vue`, `.jsx`, `.php`, `.css` ni `.js` del frontend.
- No permite HTML/CSS/JS libre.
- No sustituye al frontend; solo prepara el contrato de render.
- El builder visual es una previsualizacion administrativa, no el render final del ecommerce.
- Una plantilla tipo Wokiee debe desarmarse en componentes seguros antes de administrarse desde CMS.
- La persistencia futura de plantillas frontend usara tablas separadas para layouts, componentes, plantillas, secciones y activaciones.
- La tabla de temas visuales permite que hoy se use `wokiee_artiani` y despues se active otra plantilla sin rehacer el CMS.
- Los endpoints POST de plantillas y secciones frontend existen solo como contratos bloqueados hasta autorizar persistencia real.

### Endpoints POST futuros

Actualmente existen como contratos bloqueados:

- `/cms/frontend_plantilla_guardar_erp`
- `/cms/frontend_plantilla_estatus_erp`
- `/cms/frontend_seccion_guardar_erp`
- `/cms/frontend_seccion_estatus_erp`

Cuando se activen deberan validar permisos, CSRF, compatibilidad slot/componente/variante, auditoria explicita y que el componente exista en el frontend.

### Errores comunes

- Intentar pegar HTML completo de una plantilla Wokiee en el CMS.
- Pensar que `Plantillas de vista` guarda contenido editorial.
- Conectar el frontend a `/cms/frontend_admin_manifest_erp` en produccion.
- Crear variantes que no existen en el frontend.
- Confundir slot de contenido con componente visual.

## CMS > Frontend > Home > Compra guiada

Ruta: `/cms/frontend/home`

### Para que sirve

Esta seccion controla el bloque `home_compra_guiada`, pensado para que el frontend muestre una entrada de ayuda tipo "compra por mascota" o "compra por necesidad".

No crea categorias, no edita productos y no modifica la taxonomia real. Solo entrega configuracion editorial en JSON para que el frontend renderice sus opciones con datos de catalogo/taxonomia.

### Campos

- `Titulo`: texto principal visible.
- `Subtitulo`: texto de apoyo.
- `Orden`: posicion sugerida dentro de Home.
- `Mostrar mascotas`: permite que frontend pinte accesos por mascota.
- `Mostrar necesidades`: permite que frontend pinte accesos por necesidad.
- `Prioridad`: indica si la seccion debe tratarse como principal o secundaria.
- `Variante frontend`: nombre de variante programada que el frontend reconoce.

### Flujo recomendado

1. Abre `/cms/frontend/home`.
2. Ve a `home_compra_guiada`.
3. Captura titulo y subtitulo.
4. Activa `Mostrar mascotas`, `Mostrar necesidades` o ambas.
5. Usa `Guardar borrador` si todavia no quieres enviarlo a frontend.
6. Usa `Guardar y publicar compra guiada` para publicarlo en el slot `home.compra_guiada`.
7. Usa `Ver API publicada` para comprobar que `/ecommercePublico/cms_frontend?pagina=home` ya entrega el bloque.

### Contrato para frontend

El bloque publicado se entrega en:

- Endpoint: `/ecommercePublico/cms_frontend?pagina=home`
- Slot: `home.compra_guiada`
- Tipo: `compra_guiada`
- Codigo: `home_compra_guiada`

El frontend debe mapear `tipo=compra_guiada` al componente visual que tenga programado, por ejemplo `GuidedBuying`.

### Checklist publico para frontend

Ruta publica:

- `/ecommercePublico/frontend_requerimientos`

Este endpoint lista que secciones CMS ya estan listas, parciales o pendientes para que el frontend pueda priorizar integracion sin leer archivos internos del ERP.

## CMS > Frontend > Componentes

Ruta: `/cms/frontend_componentes`

### Para que sirve

Esta pantalla muestra el catalogo de componentes frontend permitidos para las plantillas de vista. Cada componente define:

- codigo
- nombre
- bloques de contenido permitidos
- variantes visuales permitidas
- slots compatibles

El ERP/CMS no crea el componente visual; solo registra que el frontend lo puede renderizar.

### Componentes iniciales

- `HeroSlider`
- `PromoStrip`
- `CategoryGrid`
- `ProductCarousel`
- `ImageCardGrid`
- `GuidedBuying`
- `SafeHtmlBlock`

### Flujo recomendado

1. Abre `/cms/frontend_componentes`.

2. Revisa los KPIs:
   - layouts
   - componentes
   - plantillas
   - tema activo

3. En `Explorador visual de componentes`, revisa el `Tema visual`.
   - Por ahora aparece `wokiee_artiani`.
   - El selector esta deshabilitado hasta activar persistencia real.

4. En `Componentes del tema`, selecciona un componente.
   - El preview cambia segun el componente seleccionado.
   - La vista es una representacion administrativa; no ejecuta codigo frontend.

5. Revisa `Compatibilidad`.
   - `Bloques permitidos`: tipos de bloque de CMS Contenido que puede recibir.
   - `Variantes`: estilos programados que el frontend debera reconocer.
   - `Slots compatibles`: espacios donde se puede usar.

6. Revisa `Uso en plantillas`.
   - Muestra en que plantilla aparece el componente.
   - Muestra pagina, slot, variante y orden.
   - Si no aparece, significa que esta disponible pero aun no se usa en una plantilla declarada.

7. Revisa `Contrato renderer`.
   - arranque recomendado
   - endpoint de pagina
   - guardrails contra codigo libre

8. Revisa `Componentes permitidos`.

9. Para cada componente, valida:
   - bloques permitidos
   - variantes disponibles
   - slots compatibles

10. Si una plantilla necesita una seccion nueva, primero confirma si ya existe un componente compatible.

11. Si el componente no existe, debe implementarse en el frontend antes de permitirlo en CMS.

### Como interpretar componentes

- `HeroSlider`: renderiza banners principales o banners de categoria.
- `PromoStrip`: renderiza textos promocionales o avisos.
- `CategoryGrid`: renderiza cards de categorias.
- `ProductCarousel`: renderiza colecciones dinamicas de productos.
- `ImageCardGrid`: renderiza grids de cards con imagen.
- `GuidedBuying`: renderiza entradas de compra guiada por mascota o necesidad.
- `SafeHtmlBlock`: renderiza contenido editorial sanitizado.

### Relacion componente / bloque / slot

Ejemplo:

- Slot: `home.hero`
- Bloque permitido: `hero_banner`
- Componente: `HeroSlider`
- Variante: `full_width`

La compatibilidad debe cumplirse en los tres niveles:

1. El slot acepta el tipo de bloque.
2. El componente acepta ese tipo de bloque.
3. La plantilla de vista permite ese componente en ese slot.

### Importante

- Esta pantalla es read-only en la fase actual.
- No permite cargar componentes nuevos desde el ERP.
- No permite JS/CSS/HTML libre.
- No debe apuntar a archivos internos del ERP.
- Cualquier componente nuevo debe existir primero en el frontend.
- Las variantes deben estar programadas en el frontend antes de usarse en CMS.
- El esquema propuesto mantiene estos componentes como catalogo seguro, no como codigo ejecutable.
- El preview del explorador es administrativo; el render final pertenece al frontend ecommerce.

### Errores comunes

- Agregar una variante que el frontend no conoce.
- Usar un componente en un slot incompatible.
- Usar un componente con un tipo de bloque no permitido.
- Pensar que registrar un componente en CMS lo crea automaticamente en el frontend.
- Permitir HTML libre para resolver un componente faltante.

## CMS > Frontend > Activaciones

Ruta: `/cms/frontend_activaciones`

### Para que sirve

Esta pantalla muestra que tema visual y plantilla de vista aplicarian para cada pagina/canal/contexto cuando se active la persistencia real.

Actualmente es read-only y sirve para entender la matriz de activacion sin publicar cambios.

Ejemplos iniciales:

- `home` usa tema `wokiee_artiani` y plantilla `wokiee_home_default`.
- `categoria` usa tema `wokiee_artiani` y plantilla `wokiee_categoria_default`.
- `catalogo` usa tema `wokiee_artiani` y plantilla `wokiee_catalogo_default`.

### Flujo recomendado

1. Abre `/cms/frontend_activaciones`.

2. Revisa los KPIs:
   - layouts
   - componentes
   - activaciones
   - tema activo

3. Revisa `Matriz de activacion`.
   - pagina
   - canal
   - contexto
   - tema
   - plantilla
   - layout
   - vigencia
   - endpoint publico
   - secciones incluidas

4. Revisa `Flujo futuro para cambiar plantilla`.
   - duplicar
   - editar
   - validar
   - programar
   - activar

5. Revisa `Contrato renderer`.
   - el frontend debe consumir `/ecommercePublico/configuracion_inicial`
   - para paginas especificas debe consumir `/ecommercePublico/contenido_pagina`
   - nunca debe llamar rutas internas `/cms/*`

6. Revisa `Persistencia frontend propuesta`.
   - la tabla clave para esta pantalla sera `erp_ecommerce_frontend_plantilla_activas`
   - no se ejecuta DDL en esta fase

### Importante

- Esta pantalla no publica cambios.
- Esta pantalla no cambia el tema activo.
- Esta pantalla no cambia plantillas en BD.
- Esta pantalla no edita archivos del frontend.
- Cuando se active persistencia real, cada activacion debera validar permisos, CSRF, auditoria, vigencia y compatibilidad de plantilla.

### Errores comunes

- Pensar que cambiar una tarjeta read-only ya cambia el ecommerce.
- Activar una plantilla sin verificar que todos sus componentes existan en frontend.
- Usar una plantilla de categoria para home sin validar slots.
- Conectar el frontend a rutas internas del CMS.
