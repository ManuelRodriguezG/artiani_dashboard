# CMS - Contenido ecommerce

## Reparacion puntual de acceso Media / 2026-09-25

Documentacion IA: Codex GPT-6. El dueno confirma mediante la respuesta productiva que el medio28 existe, PHP lo lee y sus permisos son `0600`; solicita poder modificarlos y ver la advertencia.

- Se agrega **Reparar acceso** a la imagen seleccionada, tanto en la biblioteca como en el selector. Usa `POST /cms/media_admin_reparar_acceso_erp` con ID y CSRF. Exige `cms.editar` y `cms.publicar` (alternativa existente `catalogo.editar`), igual que reemplazar un medio utilizado.
- El servidor bloquea la ficha durante la revision, obtiene la ruta desde BD, comprueba que sea una imagen valida y que peso/hash coincidan, y habilita `0644` solamente en ese archivo. Conserva ID, contenido, URL, aliases y usos; no cambia directorios, no usa `777` ni ejecuta reparaciones masivas.
- Si falta el archivo, su contenido difiere, hay un enlace/ruta no valida o el hosting impide modificar sus permisos, devuelve un mensaje accionable. La reparacion no reemplaza archivos ni corrige el propietario del sistema operativo.
- La operacion es repetible y deja auditoria explicita de permisos antes/despues. Un fallo posterior de BD al cerrar la revision no se describe como una reversion de chmod, porque el permiso ya aplicado no es transaccional. No se escriben filas de Media; solo auditoria de la accion.
- Despues de habilitar lectura se vuelve a probar la URL sin sesion. Un permiso correcto no descarta otras restricciones del servidor.
- `error:false, tipo:warning` requiere un aviso visible. Los avisos de estas operaciones reciben foco/scroll, se conservan junto al detalle seleccionado y no deben quedar ocultos por la recarga de la lista. El diagnostico se conserva al recargar solo mientras ID/ruta/hash/peso coincidan. Las versiones de los JS y las vistas se incrementan para evitar mezclar el backend nuevo con el selector anterior en cache.

Pruebas aisladas cubren reparacion correcta e idempotente, permiso denegado, registro/archivo ausentes, ruta/contenido ajenos, integridad antes de chmod y fallos al cerrar la revision. No se modifico el archivo productivo desde esta tarea: despues del despliegue, el operador debe seleccionar el logotipo y pulsar **Reparar acceso**. Desplegar modelo/trait, controlador, Core (auditoria), JS CMS y ambas vistas juntos.

## Validacion de cargas Media / 2026-09-25

Documentacion IA: Codex GPT-6. Solicitud del dueno: comprobar al subir que la imagen existe en el servidor y distinguir errores de permisos/acceso.

- Alta y reemplazo comprueban el archivo final antes de registrar/confirmar: existencia, lectura por PHP, permisos de lectura POSIX cuando aplican, peso y SHA256 contra el archivo recibido. Si la copia esta incompleta o alterada, no se confirma; el reemplazo recupera el original.
- La respuesta del POST incluye `validacion_archivo` en el item y su `hash_sha256`. Tambien se comprueban altas duplicadas y reemplazos sin cambios; existir en BD no demuestra que exista el archivo. El listado no recalcula hashes de toda la biblioteca.
- La biblioteca `/cms/media` y el selector del editor comprueban despues la URL publica mediante GET del mismo servidor, sin sesion, sin seguir redirecciones y con una version unica para evitar una copia antigua. Esperan como maximo 10 segundos y revisan HTTP, tipo, formato/peso y hash si el navegador dispone de WebCrypto.
- Solo se anuncia acceso confirmado cuando pasan ambas comprobaciones. Los avisos distinguen archivo ausente/no legible/permisos restringidos/contenido distinto, URL404, acceso401/403, redireccion, HTML en vez de imagen, fallo del servidor o tiempo agotado. Un403 indica acceso denegado; no prueba por si solo que el permiso del archivo sea su unica causa.
- Si ya se guardo, un fallo HTTP o de refresco del listado se muestra como advertencia; no se borra, revierte ni invita a duplicar la carga. El selector conserva la imagen en biblioteca y no la asigna automaticamente al contenido cuando la comprobacion falla. Las advertencias previas del backend tambien se conservan.
- Si el INSERT termino pero falla consultar su ficha, el alta conserva archivo/recibo y devuelve advertencia. Fallos de prepare/execute previos a guardar limpian el archivo nuevo. No se ejecutan reparaciones masivas ni DDL.

Verificacion aislada: `uat_cms_media_upload_aislada.php` cubre altas, duplicados, corrupcion y fallos de persistencia/consulta; `uat_cms_media_gestion_aislada.php` cubre integridad y recuperacion; `uat_cms_media_permisos_aislada.php` diferencia permisos y contenido; `uat_cms_media_picker.js` cubre ambas interfaces y respuestas HTTP simuladas. Ninguna prueba carga imagenes reales ni modifica la BD.

Para activar en productivo deben subirse juntos los cambios de `CmsMediaArchivo`, `EcommerceMediaGestion`, alta/formato de item en `EcommerceCatalogoPublico`, auditoria en `Cms`, los tres JS CMS (`media_tools`, `media`, `frontend_actual`) y sus vistas con la version de assets actualizada. La verificacion se ejecuta al subir/reemplazar en ese servidor; los resultados de XAMPP no diagnostican los permisos de cPanel.

## Incidente Media en productivo / continuidad 2026-09-25

Documentacion IA: Codex GPT-6. Revision de solo lectura contra `https://sys.artiani.com.mx`, host productivo documentado; pendiente confirmar que es el host usado por el operador.

- La URL reportada `/assets/media/cms/ecommerce/imagen-logotipo-artiani-en-blanaco-png-transparente-c1d4bdaf.png?v=b33e8c14a034e8d9` devuelve `302` a `/autenticacion/login`, no una imagen. El mismo resultado ocurre con otra query y con Referer de `/cms/media`.
- Sin query devuelve `200 image/png`, pero con `CF-Cache-Status: HIT`: es una copia de cache, no prueba de disponibilidad del origen. Pesa 568527 bytes y coincide con el archivo del checkout. La ficha BD del medio 28 conserva esa ruta, sin aliases, pero su peso/hash difieren de esa copia. No se modificaron archivo ni ficha para hacerlos coincidir.
- Otras imagenes PNG/WebP y el JS de Media responden `200` incluso con query nueva. Por ello no se debe quitar `?v` globalmente ni atribuirlo a extension PNG: ocultaria el fallo y podria mostrar una version antigua tras reemplazar.
- El controlador publico directo `index.php?url=EcommercePublico/media_alias/ARCHIVO` responde `404` para esta ruta actual (correcto: no es alias); `/ecommercePublico/estado` responde JSON. No se puede inferir el despliegue completo de `.htaccess` ni los permisos fisicos a partir de estas respuestas HTTP.

### Correccion preventiva realizada

Se detecto un defecto independiente verificable: `tempnam` crea archivos `0600` en Linux; copiar el original sobre ese temporal y renombrarlo a publico conserva esos permisos. Lo mismo ocurria al recuperar una copia de resguardo. Esto puede impedir que el servidor web sirva el medio aunque PHP lo lea.

- `CmsMediaArchivo::asegurarLecturaPublica()` establece y comprueba `0644`, sin escritura de grupo/otros, antes de publicar.
- Alta valida permisos antes de INSERT; reemplazo/renombrado antes de retirar el original; recuperacion antes de restaurar. Si falla, no se confirma la operacion y se conserva el resguardo necesario.
- El directorio de resguardo permanece privado `0700`. No se ejecutaron reparaciones masivas, DDL, ni cambios sobre imagenes reales.
- Pasaron sintaxis PHP, UAT aislada de gestion/rollback/aliases y UAT de permisos POSIX simulados. Windows no permite demostrar los permisos reales de cPanel.

### Pendiente en el servidor productivo

1. Desplegar juntos `app/core/CmsMediaArchivo.php`, `app/modelos/EcommerceMediaGestion.php` y el bloque de alta de `app/modelos/EcommerceCatalogoPublico.php`, preservando otros cambios del proyecto. Esto previene el defecto; no repara retroactivamente archivos existentes.
2. Revisar el archivo exacto en `public/assets/media/cms/ecommerce`: existencia, propietario, permisos y version. Si existe con permisos privados, la correccion puntual esperada es `0644`; revisar acceso de recorrido a sus directorios sin abrir permisos generales ni aplicar `777`.
3. Comparar las reglas `.htaccess` desplegadas con las del proyecto y revisar si un error `403/404` termina redirigido al login. Si el archivo falta, recuperarlo desde la version correcta o reemplazarlo desde su ficha; no crear otra ficha que rompa referencias.
4. Confirmar HTTP `200` con `Content-Type` de imagen usando la URL con `?v`, y comprobar su peso/version. Solo despues revisar la cache de la URL concreta si todavia sirve bytes antiguos.

Diagnostico opcional por terminal en el servidor (CLI, solo lectura, no necesita publicar un endpoint):

```sh
php storage/uat/uat_cms_media_archivo_diagnostico_readonly.php --host=sys.artiani.com.mx --ruta=/assets/media/cms/ecommerce/imagen-logotipo-artiani-en-blanaco-png-transparente-c1d4bdaf.png
```

El script informa existencia, lectura por PHP, permisos POSIX, coincidencia de peso/hash con BD y alias. `ok=true` significa que termino la consulta, no que el acceso HTTP funcione. Para probar el checkout usar `--host=panel.com.local`; sus resultados no describen el filesystem productivo. Queda pendiente ejecutar esta lectura en cPanel y validar la correccion en origen; no se tiene acceso al filesystem de ese servidor en esta tarea.

## Handoff Media / continuidad 2026-09-24

Documentacion IA: Codex GPT-6.

### Ampliacion autorizada: formatos y nombres SEO

- El dueno solicita reemplazar PNG/JPG por WebP y mejorar nombres. Se supera la restriccion previa de mismo formato: ID y codigo siguen estables; la URL canonica cambia si cambia formato o nombre descriptivo.
- Las URLs antiguas se conservan como aliases en `metadata_json.rutas_anteriores` y responden 301 al archivo actual, con extension/MIME reales. No se reescriben masivamente payloads ni se ejecuta DDL. Los enlaces guardados siguen funcionando y nuevas selecciones usan la URL canonica descriptiva.
- El detector de usos debe revisar tambien TODOS los aliases. Los archivos anteriores se retiran solo dentro de la operacion individual con resguardo/rollback, para que no se sirva una copia obsoleta en lugar de redirigir. Las caches externas previas pueden tardar en revalidar.
- `nombre_seo` es una descripcion propuesta por el operador, normalizada con guiones; sufijo corto evita colisiones. No se inventan etiquetas comerciales ni se renombran imagenes existentes automaticamente. El alt es independiente y su edicion en biblioteca no sustituye textos contextuales previamente publicados.
- Conversion a WebP explicita para JPG/PNG/WebP estaticos; ICO y animaciones conservan originales por defecto. La biblioteca muestra peso antes/despues y permite subir un WebP ya preparado.
- Verificacion ampliada: UAT de gestion incluye cambio real de formato en fixtures, renombrados sucesivos y rollback; UAT de aliases revisa URLs inseguras/ambiguas y referencias historicas; UAT JS cubre conversion y guardado de nombre/alt sin archivo. HTTP local de solo lectura comprobo imagen actual200, alias desconocido404 y ausencia de bucles. La biblioteca real (14 imagenes) sigue compatible sin cambios de datos.

- Necesidad del dueno: localizar imagenes pesadas, eliminarlas de forma segura y reemplazarlas sin romper usos existentes; respetar formatos como ICO. El servidor conserva originales y el navegador ofrece optimizacion voluntaria del mismo formato.
- Biblioteca `/cms/media` y modal del editor comparten `media_tools.js`: JPG/JPEG, PNG, WebP, GIF, AVIF e ICO; 2 MB finales. Optimizacion JPG/PNG/WebP estaticos hasta 2560 px, fuente hasta 20 MB, calidad82 cuando aplica, solo si ahorra bytes. No aplana animaciones ni convierte favicons.
- Reemplazo individual: mismo ID/codigo; cambia URL al renombrar/cambiar formato, con aliases301 hacia la actual. Afecta todos sus usos. Exige `cms.editar` y `cms.publicar` con equivalencia existente `catalogo.editar`; POST/CSRF y auditoria explicita.
- Eliminacion individual: comprueba TODOS los estados guardados de CMS, Blog, usos registrados y fallback Home/categorias. Corrige la busqueda INSTR que recibia porcentajes literales y omitia usos publicados independientes. No detecta referencias externas o borradores exclusivos de otros navegadores.
- Archivos: `EcommerceMediaGestion.php` extiende el modelo principal; `CmsMediaArchivo.php` valida originales/rutas; `CmsMediaReferencias.php` revisa dependencias. Resguardo temporal privado en `storage/cms_media_resguardo`; rollback recupera archivo ante fallo, limpieza tras exito.
- Contratos: `media_admin_listar_erp` acepta offset/limite (max120) y orden=peso, responde total/hay_mas. `preview_url` lleva hash; `url` es la canonica, `urls_anteriores` el historial y `nombre_seo` la descripcion. `media_admin_usos_erp` GET devuelve usos/total/puede_eliminar. `media_admin_reemplazar_erp` POST recibe id_media_archivo, archivo opcional, nombre_seo/alt opcionales. El optimizador/conversor entrega archivo validado al mismo endpoint. `public/.htaccess` deriva archivos CMS faltantes a `EcommercePublico/media_alias`, que responde301 solo a un destino CMS existente y diferente.
- Sin migraciones ni cambios de esquema. No se alteraron imagenes/filas existentes durante implementacion. Nombre SEO/alt estan disponibles; otros metadatos y archivado real siguen pendientes. Notas historicas que describen usos como bloqueados o exigen mismo formato quedan superadas por este avance.
- Verificacion: `storage/uat/uat_cms_media_gestion_readonly.php` revisa biblioteca real sin escribir (14 imagenes, 10 con usos, columnas de reemplazo disponibles). `storage/uat/uat_cms_media_gestion_aislada.php` prueba recuperacion ante fallos y formatos ICO/AVIF con fixtures privados; `storage/uat/uat_cms_media_picker.js` verifica selector y optimizador de forma aislada. El preflight historico de 2026-08 solo comprobaba cadenas de codigo, no seguridad de reemplazo/borrado. La inspeccion visual no se completo: Computer Use no pudo determinar de forma fiable la URL de Chrome y se detuvo.
- Continuidad: nuevas fuentes que guarden URLs Media deben agregarse al detector de referencias. No quitar proteccion de borradores o fallback para permitir borrados; retirar/guardar referencias o reemplazar el archivo. Si se necesita versionar URLs publicas o resolverlas por ID, coordinar contrato con frontend antes de cambiarlo.

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

Avance Media 2026-08-21:

- `/cms/media` ya funciona como biblioteca Media CMS con subida real autorizada.
- Permite seleccionar imagenes JPG/PNG/WebP desde el equipo, validar peso/tipo/MIME/dimensiones/hash, capturar alt text, clasificar uso/tipo, previsualizar y copiar referencia.
- El respaldo previo a DDL de Media quedo generado en `C:\xampp\panel_db_backups\artianilocal_panel_20260821_141529_antes_cms_media_persistencia.sql`.
- Las tablas `erp_ecommerce_media_archivos` y `erp_ecommerce_media_usos` quedaron aplicadas y la carpeta publica activa es `/assets/media/cms/ecommerce`.
- `/cms/frontend/home` ya puede abrir un selector `Media` en campos de imagen de Hero, Categorias y Banner; toma imagen y alt text desde la biblioteca local.
- El modal `Media` de Home tambien permite cargar una imagen nueva y usarla en el momento, sin obligar a ir primero a `/cms/media`; la galeria queda visible con filtro opcional.
- La galeria del modal ya muestra `Preview seleccionado` antes de aplicar una imagen existente; el usuario confirma con `Usar imagen seleccionada`.
- `/cms/media_admin_subir_erp` ya sube imagen publica con CSRF, permisos y auditoria explicita.
- El modal `Media` de `/cms/frontend/home` ya usa `/cms/media_admin_subir_erp` y `/cms/media_admin_listar_erp`; ya no debe guardar nuevas imagenes solo como `dataUrl` local.
- `/ecommercePublico/contenido_pagina?pagina=home` puede usar como fallback del hero la ultima imagen activa de `erp_ecommerce_media_archivos` con `uso_sugerido=home` y `tipo_sugerido` `hero`, `banner` o `principal`, mientras no exista publicacion CMS formal.
- `/cms/media_admin_actualizar_erp`, `/cms/media_admin_archivar_erp` y `/cms/media_admin_usos_erp` siguen bloqueados hasta cerrar edicion de metadatos, archivado seguro y trazabilidad de usos.

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
- `/cms/frontend/global` ya tiene editor local inicial para negocio, ubicacion, horarios, redes, SEO global, navegacion y footer.
- `/cms/frontend/navegacion` ya tiene editor local inicial para topbar, menu principal, columnas de footer y CTA global, sin obligar al usuario a editar JSON crudo.
- `/cms/frontend/categorias` ya tiene editor local inicial para imagen card, banner, alt text, SEO, destacado, visibilidad y orden editorial de categorias publicas, sin crear ni modificar categorias ERP.
- `/cms/frontend/marcas` ya tiene editor local inicial para logo, banner, alt text, SEO, destacado, visibilidad y orden editorial de marcas publicas, sin crear ni modificar marcas ERP.
- `/cms/frontend/paginas` ya tiene editor local inicial para paginas informativas con slug, URL publica, contenido, imagen principal, alt text y SEO, sin editar archivos frontend.
- `/cms/frontend/politicas` ya tiene editor local inicial para privacidad, envios, devoluciones y terminos con version, estatus, vigencia, contenido y SEO, sujeto a revision legal antes de publicar.
- `/cms/media` ya consulta `/cms/media_admin_preflight_erp`, endpoint GET que informa carpeta publica, limites, MIME permitidos, tablas `erp_ecommerce_media_archivos`/`erp_ecommerce_media_usos` y estado de upload activo.
- `/cms/media_admin_listar_erp` ya existe como GET read-only para listar archivos sin moverlos ni modificarlos.
- `storage/uat/uat_cms_media_persistencia_preflight.php` valida que Media tenga upload activo controlado sin ejecutar cargas durante UAT.
- El editor produce `Preview JSON esperado` con el formato del contrato frontend.
- Aun no guarda este contrato en BD ni publica `/ecommercePublico/cms_frontend`; primero se cerrara el shape exacto con el frontend actual.

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-10  
Estado: Diseno vivo, vistas separadas, UI funcional, DDL base aplicado, semilla estructural desde BD y primer POST activo para guardar bloques borrador

Manual operativo: `docs/erp_cms_manual_uso.md`
Plan CMS/API ecommerce publico: `docs/erp_cms_api_ecommerce_publico_artiani_plan.md`
Contrato frontend renderer: `docs/erp_cms_frontend_renderer_contrato.md`
Plan builder visual Wokiee: `docs/erp_cms_visual_builder_wokiee_plan.md`

Estado de cierre contenido: respaldo externo generado, DDL de tablas CMS aplicado y semilla estructural base cargada el 2026-08-12. Los endpoints internos de manifest ya leen estructura desde BD semilla (`bd_seed`) para contenido y plantillas frontend. El guardado real esta activo para bloques de contenido en borrador desde `/cms/contenido_bloque_guardar_erp`; el panel tambien puede listar esos bloques desde `/cms/contenido_admin_bloques_erp`, cargarlos a la biblioteca editorial del slot activo, pausar/reactivar borradores con `/cms/contenido_bloque_estatus_erp`, colocarlos en slots internos con `/cms/contenido_publicacion_guardar_erp` y publicar/pausar esas colocaciones con `/cms/contenido_publicacion_estatus_erp`. La API publica ya intenta leer BD publicada/vigente y conserva fallback default si no hay publicaciones publicadas. Media CMS ya tiene upload real; quedan pendientes editar metadatos, archivar seguro, registrar usos y cerrar renderer final del frontend.

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

## Blog / guias / contenido comercial

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-11  
Estado: backend inicial y contratos publicos preparados; DDL pendiente de autorizacion/aplicacion

Objetivo:

- Administrar publicaciones editoriales/comerciales del ecommerce publico: articulos, guias, noticias, videos, inspiracion, casos de cliente y recomendaciones de producto.
- Servir SEO, educacion al cliente, venta asistida, contenido relacionado en producto/categoria y videos embebidos con carga diferida.
- Mantener Blog/CMS como submodulo independiente de los slots de Home/Categorias y del catalogo ERP.

Decision de arquitectura:

- El blog tiene busqueda propia en `GET /ecommercePublico/blog?q={termino}`.
- La busqueda global vive en `GET /ecommercePublico/buscar?q={termino}` y compone fuentes independientes: catalogo/productos, categorias relacionadas y blog.
- El blog no debe acoplarse al buscador de productos ni consultar `ecom_*`.
- El frontend debe usar componentes separados: buscador global, buscador interno de blog, resultados globales, listado blog y tarjeta de publicacion.

Backend preparado:

- Modelo nuevo: `app/modelos/EcommerceBlogPublico.php`.
- Vista inicial: `app/vistas/paginas/apps/erp/cms/blog.php`.
- JS inicial: `public/assets/js/custom/apps/erp/cms/blog.js`.
- Esquema read-only: `EcommercePublicoEsquema::planActualizarCmsBlog(false)` y `auditarCmsBlog()`.
- La vista `/cms/blog` ya tiene editor inicial para titulo, slug, tipo, estado, autor, fecha, portada con ALT, extracto, contenido HTML seguro y SEO basico.
- El editor permite capturar relaciones avanzadas como JSON controlado: videos, productos relacionados, categorias relacionadas, imagenes internas y bloques interactivos.
- El backend guarda esas relaciones si el esquema existe; antes del DDL responde `requiere_ddl` sin escribir BD.

Endpoints publicos:

- `GET /ecommercePublico/blog_manifest`
- `GET /ecommercePublico/blog?pagina=1&limite=12&q=pecera`
- `GET /ecommercePublico/blog/{slug}`
- `GET /ecommercePublico/buscar?q=pecera`
- `GET /ecommercePublico/producto/{slug}/contenido_relacionado`
- `GET /ecommercePublico/categoria/{path_slug}/contenido_relacionado`
- `POST /ecommercePublico/analytics_evento`

Endpoints internos CMS:

- `GET /cms/blog`
- `GET /cms/blog_admin_estado_erp`
- `GET /cms/blog_admin_listar_erp`
- `GET /cms/blog_admin_consultar_erp?id_blog_publicacion=1`
- `POST /cms/blog_publicacion_guardar_erp`
- `POST /cms/blog_publicacion_estatus_erp`

Tablas propuestas:

- `erp_ecommerce_blog_publicaciones`
- `erp_ecommerce_blog_media`
- `erp_ecommerce_blog_videos`
- `erp_ecommerce_blog_productos`
- `erp_ecommerce_blog_categorias`
- `erp_ecommerce_blog_bloques_interactivos`
- `erp_ecommerce_blog_slugs`
- `erp_ecommerce_blog_analytics`

Guardrails:

- Solo contenido con `estado=publicado` queda visible en API publica.
- No mostrar borradores ni pausados.
- No cargar iframes de video en primer render; API entrega thumbnail y embed URL.
- No mostrar stock exacto.
- No calcular precios en frontend.
- Usar URLs publicas entregadas por API.
- El sitemap publico agrega `/blog` y `/blog/{slug}` desde publicaciones vigentes.

Pendientes:

- Autorizar respaldo externo y DDL antes de usar persistencia real.
- Convertir los JSON avanzados en selectores visuales: Media CMS, productos publicados, categorias publicas, videos y puntos interactivos.
- Conectar selector de Media CMS para portada e imagenes internas.
- Agregar administracion de relaciones a productos publicados y categorias publicas.
- Fortalecer sanitizacion HTML permitida por lista blanca antes de captura masiva.

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
