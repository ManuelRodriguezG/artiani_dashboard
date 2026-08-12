# CMS - Builder visual Wokiee ecommerce

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-11  
Estado: Plan vivo para CMS visual administrable basado en componentes reales del frontend

## Proposito

Convertir el CMS ecommerce en una herramienta administrable para negocio, parecida en objetivo a WordPress/Wix, pero controlada por temas visuales y componentes seguros del frontend.

La meta es que el negocio pueda cambiar banners, imagenes editoriales, ofertas, secciones, orden, textos, CTAs, tema visual activo y plantillas de pagina sin abrir el proyecto `C:\xampp\htdocs\frontend`.

## Diagnostico del frontend revisado

Proyecto revisado:

- `C:\xampp\htdocs\frontend\ecommerce-publico`
- Plantilla comprada:
  - `C:\xampp\htdocs\frontend\themeforest-BMN9hFaX-wokiee-ecommerce-html-template`

Documentos del frontend:

- `ecommerce-publico/AGENTS.md`
- `ecommerce-publico/docs/ESTRATEGIA_COMPONENTES_WOKIEE.md`

Regla confirmada del frontend:

- El frontend no se conecta a MySQL.
- El ERP es fuente de verdad.
- Wokiee se usa como referencia visual y fuente selectiva de assets.
- No se debe copiar completa la logica vieja de Wokiee.

## Estado actual observado

El frontend ya tiene base visual Wokiee y componentes/estructura:

- `app/Components/Layout.php`: header, menu, carrito, favoritos, footer, assets Wokiee.
- `app/Components/ProductCard.php`: card de producto estilo Wokiee.
- `app/Components/Listing.php`: heroes/listados para categoria, marca y busqueda.
- `app/Pages/catalog.php`: hoy funciona como home/catalogo principal.
- `app/Pages/category.php`: pagina de categoria.
- `app/Pages/product.php`: detalle de producto.
- `app/Pages/quote.php`: carrito/cotizacion.
- `app/Pages/wishlist.php`: favoritos.

La home/catalogo actual tiene muchas secciones editoriales hardcodeadas en `catalog.php` y helpers:

- Hero principal con imagen `img/banners/categorias/articulos_peceras.webp`.
- Promos superiores con `tt-layout-promo-box` y `tt-promo-box`.
- Comprar por categoria.
- Marcas destacadas.
- Compra guiada por mascota/necesidad.
- Comprar por mascota.
- Esenciales Artiani.
- Secciones vivas desde API.
- Destacados por ERP.
- Productos destacados/listado.
- Servicios.

Tambien hay imagenes editoriales locales en:

- `public/assets/img/banners`
- `public/assets/img/banners/categorias`
- `public/assets/img/banners/alimentos`
- `public/assets/img/categorias`
- `public/assets/img/mascotas`

Estas imagenes no siempre pertenecen al catalogo de producto; son assets editoriales de pagina. Por eso deben administrarse desde CMS Media/Contenido, no desde catalogo/precios/inventario.

## Decision de arquitectura

No conviene que el CMS guarde HTML libre ni que genere archivos frontend.

Si el CMS guardara HTML/CSS/JS libre:

- Se acopla el ERP a la plantilla.
- Se rompe seguridad.
- Se vuelve dificil mantener responsive/mobile.
- Se vuelve facil romper carrito, busqueda, SEO o accesibilidad.

La ruta correcta es:

1. El CMS registra temas visuales, por ejemplo `wokiee_artiani`.
2. Cada tema define layouts, componentes, variantes y plantillas de vista.
3. El frontend implementa componentes reales para el tema activo.
4. El CMS administra contenido, media, orden, plantilla activa y tema activo.
5. La API publica entrega JSON.
6. El frontend renderiza HTML final con sus componentes.

Wokiee debe ser el primer tema visual, no una dependencia permanente. En el futuro podran existir temas como `artiani_custom_v2`, `wokiee_alt`, `minimal_storefront` o una nueva plantilla comprada.

## Esquema generico por temas

El CMS frontend debe guardar esta jerarquia:

- `tema`: paquete visual disponible, por ejemplo `wokiee_artiani`.
- `layout`: estructura base dentro del tema, por ejemplo `storefront_wokiee_v1`.
- `componente`: renderer permitido por el tema, por ejemplo `WokieePromoBoxGrid`.
- `plantilla_vista`: composicion por pagina, por ejemplo `wokiee_home_default`.
- `seccion`: instancia ordenada de un componente en un slot.
- `plantilla_activa`: seleccion vigente por pagina/canal/contexto.

Tablas propuestas:

- `erp_ecommerce_frontend_temas`
- `erp_ecommerce_frontend_layouts`
- `erp_ecommerce_frontend_componentes`
- `erp_ecommerce_frontend_plantillas`
- `erp_ecommerce_frontend_plantilla_secciones`
- `erp_ecommerce_frontend_plantilla_activas`

## Concepto builder seguro

El CMS visual debe operar con tres niveles:

- `Pagina`: home, categoria, producto, carrito, favoritos, politicas, como-comprar.
- `Plantilla visual`: estructura de la pagina, por ejemplo `wokiee_home_default`.
- `Seccion`: bloque visual renderizable, por ejemplo hero, promo grid, category grid, product carousel.

Cada seccion tiene:

- slot de contenido
- componente frontend
- variante
- orden
- visibilidad
- vigencia
- configuracion permitida

Cada componente tiene:

- nombre tecnico
- nombre para usuario
- tipos de bloque aceptados
- campos editables
- variantes visuales disponibles
- slots compatibles
- renderer frontend requerido

## Componentes Wokiee/Artiani iniciales

Componentes del primer tema `wokiee_artiani`, alineados con la home actual:

- `WokieeHeroRevolution`
  - Base: `slider-revolution revolution-default`
  - Uso: hero principal/carrusel.
  - Campos: titulo, subtitulo, imagen desktop, imagen mobile, CTA principal, CTA secundario, puntos/beneficios.

- `WokieePromoBoxGrid`
  - Base: `row tt-layout-promo-box` + `tt-promo-box hover-type-2`
  - Uso: promos superiores, categorias destacadas, tarjetas de oferta.
  - Campos: titulo, subtitulo, imagen, URL, columnas.

- `WokieeCategoryTiles`
  - Base: `tt-promo-box tt-one-child`
  - Uso: comprar por categoria.
  - Campos: titulo seccion, descripcion, items con imagen/URL.

- `WokieePetPromoGrid`
  - Base: `tt-layout-promo-box` + `artiani-pet-promo-box`
  - Uso: comprar por mascota.
  - Campos: titulo, descripcion, items mascota.

- `WokieeBrandCarousel`
  - Base: `tt-carousel-brands`
  - Uso: marcas destacadas.
  - Campos: titulo, descripcion, fuente dinamica o items manuales.

- `ArtianiTaxonomyFinder`
  - Base: bloque custom actual.
  - Uso: compra guiada mascota/necesidad.
  - Campos: titulo, descripcion, fuentes de taxonomia, CTAs.

- `ArtianiEditorialGrid`
  - Base: `artiani-editorial-grid`
  - Uso: esenciales, ofertas, campañas, compra validada.
  - Campos: cards con imagen, titulo, subtitulo, URL, variante wide/normal.

- `WokieeProductSection`
  - Base: `tt-block-title` + `row tt-layout-product-item` + `ProductCard`
  - Uso: destacados, disponibles, ofertas, colecciones.
  - Campos: titulo, descripcion, source endpoint, limite, CTA.

- `WokieeServicesRow`
  - Base: `tt-services-listing` + `tt-services-block`
  - Uso: beneficios/servicios.
  - Campos: icono permitido, titulo, texto, URL.

- `WokieeListingHero`
  - Base: `artiani-listing-hero`
  - Uso: categoria, busqueda, marca, filtros.
  - Campos: eyebrow, titulo, descripcion, imagen, total/fuente.

- `WokieeProductDetailLayout`
  - Base: pagina producto actual.
  - Uso: detalle producto.
  - Campos administrables: banners auxiliares, tabs informativos, relacionados, textos de confianza.

- `WokieeCartLayout`
  - Base: `tt-shopcart-table`, `tt-shopcart-wrapper`, `tt-shopcart-box`
  - Uso: carrito/cotizacion.
  - Campos administrables: mensajes de ayuda, beneficios, politicas visibles.

## Que debe ser administrable

Debe poder cambiarse desde el CMS:

- Imagen principal de home.
- Slides del hero.
- Banners por categoria.
- Imagenes de categorias/mascotas.
- Cards editoriales.
- Secciones de ofertas.
- Colecciones dinamicas de productos.
- Orden de secciones.
- Estatus borrador/publicado/pausado.
- Vigencia desde/hasta.
- Textos y CTAs.
- Menu visible y orden basico.
- Footer editorial basico.
- Servicios/beneficios.
- Paginas informativas como politicas, como comprar y facturacion, con HTML sanitizado.

No debe controlarse desde CMS:

- Precio.
- Inventario.
- Disponibilidad real.
- Producto publicado.
- Logica de carrito.
- Logica de cotizacion.
- Checkout/pagos.
- JavaScript arbitrario.
- CSS arbitrario.
- Archivos PHP/Vue/JS del frontend.

## Paginas iniciales del builder

Primera fase:

- Home/catalogo principal `/`
- Categoria `/categoria/{slug}`
- Catalogo/listado `/#productos`

Segunda fase:

- Producto detalle `/producto/{slug}`
- Buscar `/buscar`
- Marca `/marca/{slug}`
- Mascota/necesidad

Tercera fase:

- Carrito/cotizacion `/carrito`
- Favoritos `/favoritos`
- Como comprar
- Facturacion
- Politicas/footer

## API publica esperada

El frontend debe consumir:

- `GET /ecommercePublico/configuracion_inicial`
- `GET /ecommercePublico/contenido_manifest`
- `GET /ecommercePublico/contenido_pagina?pagina=home`
- `GET /ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug}`
- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`

Extensiones futuras:

- `GET /ecommercePublico/contenido_pagina?pagina=producto&slug={slug}`
- `GET /ecommercePublico/contenido_pagina?pagina=carrito`
- `GET /ecommercePublico/contenido_pagina?pagina=informativa&slug=como-comprar`

## Diferencia entre preview CMS y HTML final

El CMS puede mostrar un preview visual para ayudar al usuario a decidir.

Ese preview:

- usa el mismo JSON
- simula componentes
- ayuda a validar orden, imagenes y textos
- puede verse dentro del ERP

Pero el HTML final:

- lo construye el frontend
- usa clases Wokiee reales
- mantiene carrito, busqueda, SEO, responsive y scripts propios
- no se guarda como HTML en BD

## Implementacion recomendada

Fase A: Inventario visual

1. Mapear componentes reales de `catalog.php`, `Layout.php`, `ProductCard.php`, `Listing.php`, `product.php` y `quote.php`.
2. Crear registro de componentes Wokiee/Artiani en CMS.
3. Declarar campos editables y variantes por componente.
4. Documentar imagenes editoriales faltantes y su rol.

Fase B: Builder read-only

1. Mejorar `/cms/frontend_plantillas` para mostrar estructura tipo builder.
2. Agregar vista de preview visual por plantilla.
3. Agregar paleta de componentes permitidos.
4. Agregar inspector de seccion.
5. Mantener todo en memoria/read-only.

Fase C: Persistencia

1. Respaldar BD.
2. Aplicar tablas CMS contenido y CMS frontend.
3. Persistir plantillas, secciones y media.
4. Activar endpoints POST con CSRF, permisos y auditoria.

Fase D: Integracion frontend

1. Agregar cliente API para `contenido_manifest` y `contenido_pagina`.
2. Crear renderers PHP por componente Wokiee.
3. Reemplazar secciones hardcodeadas de `catalog.php` por render dinamico desde CMS.
4. Mantener fallback hardcodeado si la API no trae contenido publicado.

Fase E: Editor de negocio

1. Permitir duplicar plantillas.
2. Permitir crear campañas/ofertas.
3. Permitir programar secciones.
4. Permitir subir/seleccionar media publica.
5. Permitir publicar con checklist.

## Prioridad inmediata

Antes de tocar el frontend:

1. Convertir el CMS Frontend en builder visual read-only real.
2. Mostrar paleta de componentes Wokiee/Artiani.
3. Mostrar estructura de pagina con secciones arrastrables en preview local.
4. Mostrar inspector de seccion y campos requeridos.
5. Preparar contrato JSON que despues implementara el frontend.

Esto permite validar con negocio sin arriesgar la tienda actual.
