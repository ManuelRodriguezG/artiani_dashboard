# CMS - Contenido ecommerce

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-10  
Estado: Diseno vivo inicial, vistas separadas, UI funcional, DDL base aplicado, semilla estructural leida desde BD en endpoints internos y endpoints POST aun bloqueados

Manual operativo: `docs/erp_cms_manual_uso.md`
Contrato frontend renderer: `docs/erp_cms_frontend_renderer_contrato.md`
Plan builder visual Wokiee: `docs/erp_cms_visual_builder_wokiee_plan.md`

Estado de cierre contenido: respaldo externo generado, DDL de tablas CMS aplicado y semilla estructural base cargada el 2026-08-12. Los endpoints internos de manifest ya leen estructura desde BD semilla (`bd_seed`) para contenido y plantillas frontend. La persistencia editorial desde panel sigue pendiente: endpoints POST continuan bloqueados y la API publica conserva fallback default/read-only hasta que existan bloques publicados reales.

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

Guardrails vigentes despues del DDL:

- Solo se inserto semilla estructural base, sin contenido comercial.
- Los manifests internos pueden leer `artiani_default`, `wokiee_artiani`, slots, layouts, componentes, plantillas de vista y activaciones desde BD.
- No se activaron endpoints POST.
- No se cambio la API publica para contenido publicado; conserva fallback default/read-only.
- No se modifico catalogo, precios, inventario ni publicaciones de producto.
- El frontend ecommerce sigue consumiendo contenido default/read-only hasta conectar lectura de BD publicada.

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
- No sube archivos ni escribe BD hasta autorizacion con respaldo.
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

Estos endpoints requieren `catalogo.ver`, no escriben BD y devuelven guardrails para indicar que la persistencia real sigue pendiente.

## Endpoints internos de escritura bloqueados

- `POST /cms/contenido_bloque_guardar_erp`
- `POST /cms/contenido_bloque_estatus_erp`
- `POST /cms/contenido_publicacion_guardar_erp`
- `POST /cms/contenido_publicacion_estatus_erp`

Estos contratos existen para que la UI y las integraciones internas tengan nombres estables, pero responden bloqueado en fase read-only. Requieren `cms.editar` o el puente transicional `catalogo.editar`, pasan por CSRF del core para POST protegidos y no escriben BD hasta completar respaldo, DDL autorizado, auditoria explicita, sanitizacion HTML y politica de media.

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
El sidebar del modulo CMS se divide en grupos internos: `Contenido` y `Frontend`.

La pantalla principal es operativa y read-only: muestra readiness, selector de pagina/contexto, resumen editorial, slots, publicabilidad por slot, bloques del slot, editor y validacion local. Las acciones de bloques completas viven en esta pantalla; vistas como `Media` reducen sus acciones a seleccion/revision para evitar operaciones fuera de contexto.

Decision UX 2026-08-12: la parte visual no vive dentro de `/cms/contenido`. Contenido queda como captura editorial de datos editables. La previsualizacion visual de pagina vive en `/cms/frontend_constructor`, donde se puede revisar la relacion `pagina -> plantilla -> secciones -> slot -> componente -> variante`.

`/cms/frontend_constructor` no genera HTML productivo ni reemplaza al frontend ecommerce. Es una vista administrativa para entender como el frontend renderizaria el JSON con sus componentes programados. En esta etapa ya cruza la plantilla frontend con el contenido read-only de `/cms/contenido_admin_pagina_erp`, mostrando cuantos bloques llegan a cada slot y usando textos/CTAs/colecciones del contrato de contenido cuando existen.

El resumen editorial de `/cms/contenido` consolida cantidad de bloques, estatus (`publicado`, `borrador`, `pausado`) y vigencia (`vigente`, `futuro`, `vencido`, `sin vigencia`) del preview local. Esto ayuda a revisar contenido antes de habilitar persistencia real.

El semaforo de publicabilidad por slot revisa cada espacio de plantilla contra reglas locales: requerido, maximo de bloques, tipos permitidos, alt text, endpoints de colecciones, vigencia y estatus. Su objetivo es detectar problemas antes de pasar a persistencia/publicacion real.

Las vistas de `Plantillas` y `JSON` muestran un panel dinamico de contratos alimentado por `contenido_admin_estado_erp`: endpoints internos del panel, endpoints publicos futuros, fase actual, modo read-only y persistencia pendiente.

La vista `JSON` muestra explicitamente `/ecommercePublico/configuracion_inicial` como endpoint publico recomendado para iniciar el frontend ecommerce. `/ecommercePublico/bootstrap` queda visible solo como alias legacy de compatibilidad y no debe usarse como nombre nuevo.

La vista `Persistencia` concentra el plan de tablas, checklist de autorizacion y endpoints POST bloqueados. Esta decision evita mezclar administracion de plantillas con tareas de respaldo, DDL, auditoria y activacion de escrituras.

La vista `Slots` muestra un detalle contextual del slot seleccionado: pagina, maximo de bloques, cantidad de bloques del preview, contexto, obligatoriedad y tipos permitidos. No edita contenido; sirve para entender la estructura antes de usar `/cms/contenido`.

Las vistas `Frontend` preparan el contrato de render: constructor visual, plantillas de vista, layouts, componentes, variantes, slots compatibles, activaciones y guardrails. Son read-only hasta definir persistencia real y hasta que el frontend implemente su mapa de componentes.

El endpoint publico `/ecommercePublico/contenido_pagina` ya entrega `plantilla_vista` en modo default/read-only junto con `slots`. Esto permite que el frontend pruebe el renderer con el contrato `plantilla_vista + contenido.slots` antes de activar persistencia real.

El endpoint publico `/ecommercePublico/configuracion_inicial` ya incluye `contenido_inicial.home` con `plantilla_vista`, `slots`, `resumen` y fuente default/read-only para que el frontend pueda hacer un primer render sin llamadas adicionales obligatorias.

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
- Permite filtrar bloques por estatus dentro del slot activo.
- Permite exportar/importar JSON de preview para revisar o compartir borradores sin escribir BD.
- Valida reglas locales de slot requerido, maximo de bloques, tipo permitido por slot, alt text, endpoint de coleccion, vigencia y HTML con `<script>`.
- Genera un JSON `preview_local_panel` que simula el payload que despues consumira la API publica.
- No guarda en BD ni sube media; todo cambio se pierde al recargar defaults.

## Pendiente para persistencia real

1. Generar respaldo externo en `C:\xampp\panel_db_backups` y documentarlo en `docs/erp_respaldo_bd_estandar.md`.
2. Autorizar aplicacion de DDL.
3. Aplicar tablas CMS.
4. Crear endpoints POST con `catalogo.editar`, CSRF, token interno y auditoria explicita.
5. Implementar sanitizacion estricta para `content_html_safe`.
6. Definir almacenamiento de media y reglas de nombres/tamanos.
7. Hacer que `contenido_manifest` y `contenido_pagina` lean primero BD publicada y usen defaults solo como fallback.
8. Integrar `contenido_pagina` publicado dentro de `/ecommercePublico/configuracion_inicial`; `/bootstrap` queda alias legacy.
9. Sustituir el borrador en memoria del panel por persistencia real y validaciones backend.
10. Ejecutar semilla de seguridad autorizada para activar `cms.ver`, `cms.editar` y `cms.publicar`; despues retirar el puente `catalogo.ver`.
11. Autorizar y aplicar, en una fase separada, el esquema CMS frontend para persistir temas, layouts, componentes, plantillas de vista, secciones y activaciones.
12. Activar endpoints POST frontend bloqueados: `/cms/frontend_plantilla_guardar_erp`, `/cms/frontend_plantilla_estatus_erp`, `/cms/frontend_seccion_guardar_erp` y `/cms/frontend_seccion_estatus_erp`.

## UAT read-only

Script: `storage/uat/uat_cms_contenido_readonly.php`

Criterios:

- Manifest interno expone plantilla, slots y tipos.
- Pagina home expone slots principales.
- Pagina categoria expone `categoria.banner`.
- Estado interno declara modo read-only y plan de tablas propuesto.
- Esquema CMS frontend declara 5 tablas propuestas y permanece read-only.
- Vista de contenido contiene editor, listado de contenido por slot y validacion local.
- Vista de contenido contiene resumen editorial de estatus y vigencia.
- Vista de contenido contiene publicabilidad por slot.
- Vistas separadas existen para `/cms/plantillas`, `/cms/slots`, `/cms/media` y `/cms/json`.
- Vista separada `/cms/persistencia` existe para plan de BD y contratos POST bloqueados.
- Vistas separadas `/cms/frontend_plantillas` y `/cms/frontend_componentes` existen para plantillas de vista y componentes.
- Sidebar contiene accesos reales bajo seccion `CMS`, no hashes dentro de una sola pantalla y no dentro de `Ecommerce`.
- Seguridad declara permisos `cms.ver`, `cms.editar` y `cms.publicar` en el plan de permisos base.
- JS contiene acciones locales para nuevo bloque, aplicar a preview, duplicar, ordenar, pausar, quitar, validar, guardar borrador local, filtrar estatus e importar/exportar JSON.
- No se ejecuta DDL ni se modifican catalogo, precios, inventario o publicaciones de producto.
