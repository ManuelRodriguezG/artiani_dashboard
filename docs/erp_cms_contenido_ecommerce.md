# CMS - Contenido ecommerce

## Cambio de rumbo 2026-08-13

Se detiene el enfoque de constructor generico tipo WordPress/Wix para no seguir invirtiendo tiempo en una abstraccion que no esta conectada al frontend actual.

La ruta principal `/cms` ahora abre `/cms/frontend/home`, una vista alineada al contrato real definido por el frontend en:

`C:\xampp\htdocs\frontend\ecommerce-publico\docs\CONTRATO_CMS_FRONTEND_ECOMMERCE.md`

Decision UX 2026-08-14:

- La ruta principal operativa pasa a `/cms/frontend/home`.
- `/cms/frontend_actual` queda como alias tecnico/legacy hacia Home.
- El CMS Frontend se ordena por paginas reales: Home, Categorias, Producto, Carrito y Global.
- `CMS > Media / Archivos` sera la biblioteca para subir, seleccionar, reutilizar, archivar y limpiar imagenes del frontend.
- Los campos de imagen por URL son temporales; el flujo profesional sera seleccionar media desde la biblioteca del CMS.

Avance Media 2026-08-14:

- `/cms/media` ya funciona como biblioteca local inicial.
- Permite seleccionar imagenes JPG/PNG/WebP desde el equipo, validar peso/tipo, capturar alt text, clasificar uso/tipo, previsualizar, copiar referencia, archivar y limpiar archivados.
- `/cms/frontend/home` ya puede abrir un selector `Media` en campos de imagen de Hero, Categorias y Banner; toma imagen y alt text desde la biblioteca local.
- El modal `Media` de Home tambien permite cargar una imagen nueva y usarla en el momento, sin obligar a ir primero a `/cms/media`; la galeria queda visible con filtro opcional.
- No sube archivos al servidor, no borra archivos fisicos y no guarda BD; prepara el flujo antes de activar persistencia real de media.

Prioridad nueva:

1. `global`: header, footer, WhatsApp y SEO defaults.
2. `home.hero_carrusel`.
3. `home.categorias_destacadas`.
4. `home.productos_destacados`.
5. `home.coleccion_productos`.
6. Imagenes por categoria.
7. Galeria/recomendados por producto.
8. Textos de carrito/WhatsApp.
9. Estados vacios.
10. Paginas de ayuda/facturacion/politicas.

Las vistas de constructor/plantillas/componentes anteriores quedan como material avanzado o experimental; no deben ser el camino operativo principal.

Avance 2026-08-13:

- `/cms/frontend_actual` ya renderiza el contrato por grupos.
- `home.hero_carrusel` ya tiene editor local operativo para configuracion general y slides.
- `home.categorias_destacadas` ya tiene editor local operativo para titulo, subtitulo, columnas, variante visual y tarjetas con `categoria_id`, `slug`, URL, imagen card/banner y alt text.
- `home.productos_destacados` ya tiene editor local operativo para titulo, subtitulo, limite, CTA, variante visual, fuente por criterio y lista manual de referencias por producto/SKU/slug.
- `home.coleccion_productos` ya tiene editor local operativo para colecciones repetibles con titulo, subtitulo, criterio, categoria/marca, limite, CTA, variante visual y referencias manuales por CSV.
- `home.banner` ya tiene editor local operativo para el banner de Home actual: imagen desktop/mobile, alt text, titulo, subtitulo, CTA y estructura `items` preparada para slides futuros.
- El editor produce `Preview JSON esperado` con el formato del contrato frontend.
- Aun no guarda este contrato en BD ni publica `/ecommercePublico/cms_frontend`; primero se cerrara el shape exacto con el frontend actual.

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-10  
Estado: Diseno vivo, vistas separadas, UI funcional, DDL base aplicado, semilla estructural desde BD y primer POST activo para guardar bloques borrador

Manual operativo: `docs/erp_cms_manual_uso.md`
Contrato frontend renderer: `docs/erp_cms_frontend_renderer_contrato.md`
Plan builder visual Wokiee: `docs/erp_cms_visual_builder_wokiee_plan.md`

Estado de cierre contenido: respaldo externo generado, DDL de tablas CMS aplicado y semilla estructural base cargada el 2026-08-12. Los endpoints internos de manifest ya leen estructura desde BD semilla (`bd_seed`) para contenido y plantillas frontend. El guardado real esta activo para bloques de contenido en borrador desde `/cms/contenido_bloque_guardar_erp`; el panel tambien puede listar esos bloques desde `/cms/contenido_admin_bloques_erp`, cargarlos a la biblioteca editorial del slot activo, pausar/reactivar borradores con `/cms/contenido_bloque_estatus_erp`, colocarlos en slots internos con `/cms/contenido_publicacion_guardar_erp` y publicar/pausar esas colocaciones con `/cms/contenido_publicacion_estatus_erp`. La API publica ya intenta leer BD publicada/vigente y conserva fallback default si no hay publicaciones publicadas. Media estructurada y renderer final del frontend siguen pendientes.

Respaldo usado antes de DDL:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260812_094259_antes_cms_ecommerce_persistencia.sql
```

DDL aplicado:

- CMS Contenido: 5 tablas.
- CMS Frontend: 6 tablas.
- Total: 11 tablas.

Semilla base aplicada:

- Plantilla contenido: `artiani_default`.
- Slots contenido: 7.
- Tema frontend: `wokiee_artiani`.
- Layouts frontend: 3.
- Componentes frontend: 6.
- Plantillas de vista: 3.
- Secciones frontend: 7.
- Activaciones frontend: 3.

La semilla no creo bloques comerciales, publicaciones de contenido ni media.

Guardrails vigentes despues del DDL y primer guardado:

- Solo se inserto semilla estructural base, sin contenido comercial.
- Los manifests internos pueden leer `artiani_default`, `wokiee_artiani`, slots, layouts, componentes, plantillas de vista y activaciones desde BD.
- `POST /cms/contenido_bloque_guardar_erp` puede crear/actualizar bloques en `erp_ecommerce_contenido_bloques` como `borrador` o `pausado`.
- `POST /cms/contenido_bloque_estatus_erp` puede cambiar bloques entre `borrador` y `pausado`; no acepta `publicado`.
- `POST /cms/contenido_publicacion_guardar_erp` coloca un bloque guardado en un slot/pagina/contexto como publicacion interna `borrador` o `pausado`.
- `POST /cms/contenido_publicacion_estatus_erp` cambia esa colocacion entre `borrador`, `pausado` y `publicado`.
- Antes de publicar, el backend valida tipo compatible, titulo/texto, vigencia, alt text en banners, endpoint en colecciones, cards requeridas y HTML seguro.
- `GET /cms/contenido_admin_bloques_erp` lista bloques `borrador`/`pausado` para reutilizarlos en el panel.
- El guardado de publicacion interna arma la pagina para preview administrativo; al publicar la colocacion, la API publica puede leerla si esta vigente.
- Los endpoints POST frontend siguen bloqueados.
- La API publica de contenido lee primero publicaciones `publicado` y vigentes desde BD; si no existen, conserva fallback default/read-only.
- No se modifico catalogo, precios, inventario ni publicaciones de producto.
- El frontend ecommerce consume `/ecommercePublico/contenido_pagina`; recibira `fuente=bd_publicada` cuando exista contenido publicado vigente o `fuente=default_readonly` como fallback.

## Proposito

Construir un modulo CMS interno y headless para administrar contenido editorial que el frontend ecommerce externo consumira por API. El ERP conserva gobierno, vigencia, estatus, media y orden; el frontend renderiza segun su plantilla sin leer archivos internos del panel.

## Alcance inicial

- Banner principal de home.
- Banners por categoria.
- Bloques de contenido y textos promocionales.
- Colecciones de productos referenciadas por reglas/API, no por edicion directa de catalogo.
- Cards con imagen, CTAs, orden, vigencia y estatus.
- Plantilla activa y slots disponibles.
- Previsualizacion del JSON que consumira frontend.

Fuera de alcance:

- No modifica catalogo, precios, inventario ni publicaciones de producto.
- No sube archivos; solo guarda bloques editoriales borrador en BD despues del respaldo/DDL autorizado.
- No convierte el ERP en page builder visual pesado.
- No expone secretos ni obliga al frontend a leer docs o rutas fisicas del ERP.

## Modelo conceptual

- `plantilla`: estructura disponible, por ejemplo `artiani_default`.
- `slot`: espacio dentro de una plantilla, por ejemplo `home.hero`.
- `bloque`: pieza de contenido editable, por ejemplo `hero_banner`.
- `publicacion`: bloque colocado en un slot con orden, estatus, pagina, contexto y vigencia.
- `media`: imagen desktop/mobile, alt text y metadatos asociados a un bloque.
- `plantilla_vista`: layout frontend administrable, por ejemplo `wokiee_home_default`.
- `componente_frontend`: componente seguro que el frontend ya tiene programado, por ejemplo `HeroSlider`.
- `variante_frontend`: presentacion controlada de un componente, por ejemplo `full_width` o `compact_cards`.

## Slots iniciales

- `home.hero`
- `home.promo`
- `home.categorias`
- `home.destacados`
- `categoria.banner`
- `categoria.productos`
- `catalogo.encabezado`

## Tipos de bloque iniciales

- `hero_banner`
- `category_banner`
- `product_collection`
- `promo_strip`
- `image_card_grid`
- `content_html_safe`

## Plantillas frontend

El CMS tambien prepara plantillas de vista para que el frontend pueda cambiar layout sin editar archivos desde el ERP.

Reglas:

- El ERP/CMS administra configuracion JSON segura.
- El frontend ecommerce implementa componentes predefinidos y renderiza segun la API.
- No se envia HTML, CSS ni JS libre desde el CMS.
- Una plantilla tipo Wokiee se divide en componentes reutilizables: `HeroSlider`, `PromoStrip`, `CategoryGrid`, `ProductCarousel`, `ImageCardGrid`, `SafeHtmlBlock`.
- Cada seccion mapea `slot -> componente -> variante -> orden`.

Ejemplo:

```json
{
  "codigo": "wokiee_home_default",
  "pagina": "home",
  "layout": "storefront_wokiee_v1",
  "secciones": [
    { "slot": "home.hero", "componente": "HeroSlider", "variante": "full_width", "orden": 1 },
    { "slot": "home.destacados", "componente": "ProductCarousel", "variante": "compact_cards", "orden": 4 }
  ]
}
```

Esquema frontend propuesto:

- `erp_ecommerce_frontend_temas`: temas visuales disponibles, tema activo y proveedor/base visual.
- `erp_ecommerce_frontend_layouts`: layouts base versionados, por ejemplo `storefront_wokiee_v1`.
- `erp_ecommerce_frontend_componentes`: componentes permitidos, variantes, slots compatibles y tipos de bloque aceptados.
- `erp_ecommerce_frontend_plantillas`: plantillas de vista por pagina, por ejemplo `wokiee_home_default`.
- `erp_ecommerce_frontend_plantilla_secciones`: orden visual de secciones y mapeo `slot -> componente -> variante`.
- `erp_ecommerce_frontend_plantilla_activas`: seleccion activa por pagina/canal/contexto y vigencia.

El plan DDL read-only se consulta desde `EcommercePublicoEsquema::planActualizarCmsFrontend(false)` y no ejecuta cambios. Wokiee queda registrado como primer tema visual (`wokiee_artiani`), no como unica plantilla permanente.

## Esquema propuesto

Tablas:

- `erp_ecommerce_plantillas`: plantillas CMS, plantilla activa y version.
- `erp_ecommerce_plantilla_slots`: slots por plantilla, pagina, tipos permitidos y limites.
- `erp_ecommerce_contenido_bloques`: contenido editable por tipo con payload JSON.
- `erp_ecommerce_contenido_publicaciones`: colocacion de bloques en slots, orden, estatus, vigencia y contexto.
- `erp_ecommerce_contenido_media`: imagenes desktop/mobile y metadatos de accesibilidad.

El plan DDL read-only se consulta desde `EcommercePublicoEsquema::planActualizarCmsContenido(false)` y no ejecuta cambios.

## Endpoints internos read-only del modulo CMS

- `GET /cms/contenido_admin_estado_erp`
- `GET /cms/contenido_admin_manifest_erp`
- `GET /cms/contenido_admin_pagina_erp?pagina=home`
- `GET /cms/contenido_admin_pagina_erp?pagina=categoria&categoria=peces`
- `GET /cms/contenido_admin_bloques_erp`

Estos endpoints requieren `catalogo.ver`, no escriben BD y devuelven guardrails para indicar que la persistencia real sigue pendiente.

## Endpoints internos de escritura

- Activo controlado:
  - `POST /cms/contenido_bloque_guardar_erp`: guarda/actualiza solo bloques en borrador o pausado, con `cms.editar`/`catalogo.editar`, CSRF, auditoria explicita, validacion de tipo y bloqueo basico de HTML peligroso.
  - `POST /cms/contenido_bloque_estatus_erp`: cambia bloques guardados entre borrador y pausado, con CSRF y auditoria explicita. Publicar sigue reservado a publicaciones por slot.
  - `POST /cms/contenido_publicacion_guardar_erp`: coloca bloques en slots internos para que `/cms/contenido_admin_pagina_erp` y `/cms/frontend_constructor` puedan armar la pagina desde BD. No expone contenido a `/ecommercePublico/*`.
  - `POST /cms/contenido_publicacion_estatus_erp`: cambia la colocacion del slot entre borrador, pausado y publicado, con `cms.publicar`/`catalogo.editar`, CSRF y auditoria explicita.

Bloqueados:

- `POST /cms/frontend_plantilla_guardar_erp`
- `POST /cms/frontend_plantilla_estatus_erp`
- `POST /cms/frontend_seccion_guardar_erp`
- `POST /cms/frontend_seccion_estatus_erp`

Los endpoints activos de contenido no modifican catalogo, precios ni inventario. Las publicaciones marcadas como `publicado` pueden ser leidas por la API publica si estan vigentes y el bloque base no esta pausado.

## Vistas internas

- Rutas:
  - `/cms`: entrada directa al modulo; carga la vista principal de contenido.
  - `/cms/contenido`: editor editorial de bloques por slot.
  - `/cms/plantillas`: plantilla activa, tipos de bloque, esquema propuesto, contratos CMS y manifest read-only.
  - `/cms/persistencia`: plan read-only de tablas, checklist de autorizacion y endpoints POST bloqueados.
  - `/cms/slots`: mapa de slots por pagina, contexto, plantilla y detalle del slot seleccionado.
  - `/cms/media`: revision visual de imagenes y alt text; solo selecciona bloques para inspeccion.
  - `/cms/json`: contratos API, endpoint de arranque recomendado, preview, copia, importacion y exportacion del JSON.
  - `/cms/frontend_constructor`: constructor visual administrativo de pagina, plantilla, secciones, componentes y slots.
  - `/cms/frontend_plantillas`: plantillas de vista frontend, layouts y mapeo slot-componente-variante.
  - `/cms/frontend_componentes`: catalogo de componentes frontend permitidos, variantes y slots compatibles.
  - `/cms/frontend_activaciones`: matriz de activacion por pagina, canal y contexto.
- Vistas:
  - `app/vistas/paginas/apps/erp/cms/contenido.php`
  - `app/vistas/paginas/apps/erp/cms/plantillas.php`
  - `app/vistas/paginas/apps/erp/cms/persistencia.php`
  - `app/vistas/paginas/apps/erp/cms/slots.php`
  - `app/vistas/paginas/apps/erp/cms/media.php`
  - `app/vistas/paginas/apps/erp/cms/json.php`
  - `app/vistas/paginas/apps/erp/cms/frontend_constructor.php`
  - `app/vistas/paginas/apps/erp/cms/frontend_plantillas.php`
  - `app/vistas/paginas/apps/erp/cms/frontend_componentes.php`
  - `app/vistas/paginas/apps/erp/cms/frontend_activaciones.php`
- JS: `public/assets/js/custom/apps/erp/cms/contenido.js`
- JS frontend CMS: `public/assets/js/custom/apps/erp/cms/frontend.js`
- Sidebar: seccion separada `CMS` con accesos a cada vista del modulo.
- Permisos: transicionalmente acepta `cms.ver` o `catalogo.ver`; el permiso dueno futuro es `cms.ver`.

Decision UX: el CMS no usa pestanas internas para secciones principales. Cada seccion abre una vista/ruta propia para evitar mezclar captura editorial, estructura, media y contrato API.
El sidebar del modulo CMS se divide en grupos internos: `Avanzado contenido` y `Paginas ecommerce`.

Decision UX 2026-08-13: `/cms` debe abrir el constructor de paginas ecommerce, no el editor tecnico de bloques. Para el usuario operativo el modelo mental es `pagina -> secciones visibles -> contenido editable -> publicar`. Los slots, JSON, contratos y plantillas tecnicas quedan como herramientas avanzadas del mismo modulo.

La pantalla principal es operativa con persistencia parcial: muestra readiness, selector de pagina/contexto, resumen editorial, slots, publicabilidad por slot, bloques del slot, editor, biblioteca de bloques BD, validacion local y guardado de bloque en BD como borrador. Las acciones de bloques completas viven en esta pantalla; vistas como `Media` reducen sus acciones a seleccion/revision para evitar operaciones fuera de contexto.

Decision UX 2026-08-12: la parte visual no vive dentro de `/cms/contenido`. Contenido queda como captura editorial de datos editables. La previsualizacion visual de pagina vive en `/cms/frontend_constructor`, donde se puede revisar la relacion `pagina -> plantilla -> secciones -> slot -> componente -> variante`.

`/cms/frontend_constructor` no genera HTML productivo ni reemplaza al frontend ecommerce. Es una vista administrativa para entender como el frontend renderizaria el JSON con sus componentes programados. En esta etapa ya cruza la plantilla frontend con el contenido read-only de `/cms/contenido_admin_pagina_erp`, mostrando cuantos bloques llegan a cada slot y usando textos/CTAs/colecciones del contrato de contenido cuando existen. Tambien puede leer el borrador local guardado desde `/cms/contenido` mediante `localStorage`, para revisar cambios editoriales antes de persistirlos en BD.

El inspector del constructor incluye acceso directo a `/cms/contenido?pagina={pagina}&slot={slot}&bloque={bloque}`. La vista de contenido interpreta esos parametros para abrir la pagina y slot indicados, facilitando el flujo visual -> editorial.

Decision visual 2026-08-13: el constructor debe parecer una pagina construida, no solo una lista tecnica de slots. La vista central muestra una maqueta de tienda con header, hero, promos, grid, carrusel y footer; usa imagenes declaradas en bloques cuando existen y mantiene etiquetas administrativas para saber que slot/componente genera cada seccion.

Decision visual 2026-08-13 tarde: el constructor muestra primero paginas ecommerce (`Home`, `Categoria`, `Producto`, `Carrito`, `Header`, `Footer`). Home es la primera pagina funcional; Producto, Carrito, Header y Footer pueden mostrarse como pendientes hasta definir su plantilla visual. El inspector traduce slots tecnicos a nombres humanos como `Portada / carrusel principal`, `Franja promocional`, `Categorias destacadas` y `Productos destacados`.

Decision operativa 2026-08-13: Home inicia edicion directa desde `/cms/frontend_constructor`. El panel `Edicion rapida` permite modificar titulo, subtitulo/texto, CTA, imagen desktop/mobile, alt text, vigencia y estatus del bloque principal de la seccion seleccionada. `Aplicar a maqueta` actualiza preview/localStorage. `Guardar borrador en CMS` guarda el bloque en BD y crea/actualiza su colocacion en el slot como borrador; no publica automaticamente para evitar cambios visibles no revisados.

Decision operativa 2026-08-13 cierre: el constructor puede ejecutar `Publicar seccion` y `Pausar seccion` sobre la colocacion CMS. Publicar primero guarda el bloque y la publicacion interna, despues llama `contenido_publicacion_estatus_erp` con validacion server-side (`cmsValidarPublicacionAntesDePublicar`). Los bloqueos se devuelven al usuario en el panel rapido. Pausar no borra contenido ni toca catalogo/precios/inventario.

Decision UX 2026-08-13 estructura local: el mapa `Secciones de esta pagina` permite seleccionar, subir, bajar, ocultar/mostrar y duplicar secciones en la maqueta del constructor. Este cambio es local (`localStorage`, alcance `maqueta_local_no_persistida`) y no escribe tablas CMS Frontend. La persistencia real del orden/estructura de plantillas queda para el submodulo de plantillas frontend con respaldo y autorizacion.

Decision UX 2026-08-13 readiness Home: el constructor incluye `Estado de Home`, un checklist operativo previo a conectar frontend real. Resume secciones listas, borradores, publicadas y errores. Cada seccion reporta estado, vigencia, cantidad de bloques y siguiente paso. Los errores locales cubren falta de contenido, titulo/texto, alt text requerido en banners, coleccion faltante y vigencia invalida.

Decision UX 2026-08-13 constructor tipo WordPress: el modulo debe abrir una pagina real de trabajo (`Home`) y ofrecer una paleta de modulos. `Agregar modulos` inserta secciones locales en Home: portada/carrusel, promo, cards, productos y contenido seguro. `Previsualizar Home` abre una vista completa de la pagina armada. En esta fase los modulos agregados a estructura son maqueta local; la persistencia real de estructura visual queda para CMS Frontend, mientras que el contenido de secciones existentes ya usa flujo CMS con BD.

El resumen editorial de `/cms/contenido` consolida cantidad de bloques, estatus (`publicado`, `borrador`, `pausado`) y vigencia (`vigente`, `futuro`, `vencido`, `sin vigencia`) del preview local. Esto ayuda a revisar contenido antes de habilitar persistencia real.

El semaforo de publicabilidad por slot revisa cada espacio de plantilla contra reglas locales: requerido, maximo de bloques, tipos permitidos, alt text, endpoints de colecciones, vigencia y estatus. Su objetivo es detectar problemas antes de pasar a persistencia/publicacion real.

Las vistas de `Plantillas` y `JSON` muestran un panel dinamico de contratos alimentado por `contenido_admin_estado_erp`: endpoints internos del panel, endpoints publicos futuros, fase actual, modo read-only y persistencia pendiente.

La vista `JSON` muestra explicitamente `/ecommercePublico/configuracion_inicial` como endpoint publico recomendado para iniciar el frontend ecommerce. `/ecommercePublico/bootstrap` queda visible solo como alias legacy de compatibilidad y no debe usarse como nombre nuevo.

La vista `Persistencia` concentra el plan de tablas, checklist de autorizacion y endpoints POST bloqueados. Esta decision evita mezclar administracion de plantillas con tareas de respaldo, DDL, auditoria y activacion de escrituras.

La vista `Slots` muestra un detalle contextual del slot seleccionado: pagina, maximo de bloques, cantidad de bloques del preview, contexto, obligatoriedad y tipos permitidos. No edita contenido; sirve para entender la estructura antes de usar `/cms/contenido`.

Las vistas `Frontend` preparan el contrato de render: constructor visual, plantillas de vista, layouts, componentes, variantes, slots compatibles, activaciones y guardrails. Son read-only hasta definir persistencia real y hasta que el frontend implemente su mapa de componentes.

El endpoint publico `/ecommercePublico/contenido_pagina` ya entrega `plantilla_vista` junto con `slots`. Primero intenta leer publicaciones `publicado` y vigentes desde `erp_ecommerce_contenido_publicaciones`; si no hay contenido publicado para la pagina/contexto, usa contenido default/read-only.

El endpoint publico `/ecommercePublico/configuracion_inicial` ya incluye `contenido_inicial.home` con `plantilla_vista`, `slots`, `resumen` y `fuente`, que puede ser `bd_publicada` o `default_readonly`.

El endpoint publico `/ecommercePublico/contenido_manifest` ya expone `plantillas_vista` y `componentes_frontend` en modo default/read-only para que el frontend pueda descubrir layouts/componentes permitidos sin llamar rutas internas `/cms/*`.

El contrato operativo para implementar el renderer del frontend queda documentado en `docs/erp_cms_frontend_renderer_contrato.md`. Ese documento define endpoints publicos permitidos, forma de usar `plantilla_vista.secciones`, compatibilidad componente/bloque/slot y guardrails para no consumir rutas internas `/cms/*`.

El plan para evolucionar de CMS de contenido a builder visual controlado por componentes Wokiee/Artiani queda documentado en `docs/erp_cms_visual_builder_wokiee_plan.md`. La decision central es que el CMS no guardara HTML/CSS/JS libre; administrara componentes, variantes, media, orden y vigencia para que el frontend construya el HTML final.

Flujo funcional actual:

- Carga contenido default desde `contenido_admin_pagina_erp`.
- Permite elegir pagina, categoria, plantilla y slot.
- Permite crear bloques locales en memoria segun los tipos permitidos por slot.
- Permite editar titulo, subtitulo/texto, CTA, imagen desktop/mobile, alt text, source endpoint, vigencia y estatus.
- Permite ordenar, pausar/reactivar y quitar bloques en el preview local.
- Permite duplicar bloques para acelerar variantes.
- Permite guardar/cargar/descartar un borrador local en navegador usando `localStorage`, sin BD.
- Permite guardar el bloque activo en BD como borrador/pausado usando `POST /cms/contenido_bloque_guardar_erp`.
- Permite listar bloques guardados con `GET /cms/contenido_admin_bloques_erp` y cargarlos al slot activo para editarlos.
- Permite pausar/reactivar bloques guardados en BD sin publicarlos.
- Permite colocar el bloque guardado en el slot activo como publicacion interna borrador para preview visual BD.
- Permite publicar/pausar la colocacion del slot con permiso y auditoria.
- La publicacion server-side bloquea contenido con errores y devuelve `bloqueos_publicacion` legibles.
- Permite filtrar bloques por estatus dentro del slot activo.
- Permite exportar/importar JSON de preview para revisar o compartir borradores sin escribir BD.
- Valida reglas locales de slot requerido, maximo de bloques, tipo permitido por slot, alt text, endpoint de coleccion, vigencia y HTML con `<script>`.
- Genera un JSON `preview_local_panel` que simula el payload que despues consumira la API publica.
- El guardado/listado en BD persiste y reutiliza bloques. La publicacion de slot alimenta el preview administrativo y, cuando esta en `publicado` y vigente, tambien puede alimentar la API publica.

## Pendiente para completar CMS contenido

1. Definir almacenamiento de media y reglas de nombres/tamanos.
2. Ampliar sanitizacion estricta para `content_html_safe` con lista permitida de tags/atributos.
3. Ejecutar semilla de seguridad autorizada para activar `cms.ver`, `cms.editar` y `cms.publicar`; despues retirar el puente `catalogo.ver`.
4. Activar endpoints POST frontend bloqueados: `/cms/frontend_plantilla_guardar_erp`, `/cms/frontend_plantilla_estatus_erp`, `/cms/frontend_seccion_guardar_erp` y `/cms/frontend_seccion_estatus_erp`.

## UAT CMS contenido

Script: `storage/uat/uat_cms_contenido_readonly.php`

Script de conexion publica con rollback: `storage/uat/uat_cms_publico_bd_temporal_rollback.php`

Criterios:

- Manifest interno expone plantilla, slots y tipos.
- Pagina home expone slots principales.
- Pagina categoria expone `categoria.banner`.
- Estado interno declara persistencia de contenido interna y no ejecuta DDL.
- Esquema CMS frontend declara 5 tablas propuestas y permanece read-only.
- Vista de contenido contiene editor, listado de contenido por slot y validacion local.
- Vista de contenido contiene resumen editorial de estatus y vigencia.
- Vista de contenido contiene publicabilidad por slot.
- Vistas separadas existen para `/cms/plantillas`, `/cms/slots`, `/cms/media` y `/cms/json`.
- Vista separada `/cms/persistencia` existe para plan de BD y contratos POST bloqueados.
- Vistas separadas `/cms/frontend_plantillas` y `/cms/frontend_componentes` existen para plantillas de vista y componentes.
- Sidebar contiene accesos reales bajo seccion `CMS`, no hashes dentro de una sola pantalla y no dentro de `Ecommerce`.
- Seguridad declara permisos `cms.ver`, `cms.editar` y `cms.publicar` en el plan de permisos base.
- JS contiene acciones locales para nuevo bloque, aplicar a preview, duplicar, ordenar, pausar, quitar, validar, guardar borrador local, guardar bloque en BD, cambiar estatus BD, colocar en slot BD, listar/cargar biblioteca BD, filtrar estatus e importar/exportar JSON.
- No se ejecuta DDL ni se modifican catalogo, precios, inventario o publicaciones de producto.
- La API publica lee una publicacion temporal `publicado`/vigente desde BD dentro de una transaccion y el rollback no deja bloques permanentes.
- La publicacion de un hero temporal sin `alt text` se bloquea server-side antes de exponerlo.
