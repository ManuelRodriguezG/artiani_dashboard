# CMS Blog - Estado actual

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado vigente 2026-11-01: ocho tablas creadas con respaldo/autorizacion; primer articulo registrado como borrador; carga de imagenes integrada al editor local.

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

1. Subir portada e imagenes de apoyo del primer borrador mediante el editor y revisar sus relaciones antes de autorizar publicacion. Respaldo/DDL completados el 2026-11-01.
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

### Auditoria de storage y regla general / 2026-11-06

- IA: Codex GPT-6. El dueño pide evitar residuos y analizar storage. Regla durable añadida a `AGENTS.md`, seccion Storage y archivos temporales de agentes: trabajo efimero en temporal del sistema, limpieza al terminar, imagenes finales y resumen en docs; herramientas mantenidas y datos operativos se conservan.
- Inventario local antes: 986 archivos / 60.82 MiB. Retirados otros 49 archivos / 42,462,371 bytes (40.50 MiB): 45 auxiliares de piton (15 PNG de generaciones anteriores, referencias de productos, capturas, HTML/JSON y ZIP duplicado) y cuatro scripts exclusivos de esa carga/revision ya realizada. Despues: 937 archivos / 20.32 MiB. Estas cifras son posteriores a la limpieza de camaleon documentada abajo.
- Piton conserva solo cinco `catalogo-v3-*.webp` en `storage/editorial/blog_piton_bola/imagenes_revision/`. Se comprobaron contra los archivos activos y sus hashes; no se modificaron las copias publicas de ninguno de los dos articulos. `storage/editorial` conserva diez WebP finales en total (2.52 MiB). Sin escrituras BD, cambios de estado, publicacion ni despliegue.
- Los cuatro scripts retirados de `storage/uat` son `uat_blog_local_browser.js`, `uat_cms_blog_piton_apply_authorized.php`, `uat_cms_blog_referencias_apply_authorized.php` y `uat_cms_blog_imagenes_apply_authorized.php`. Sus entradas eran snapshots exclusivos de Blog1; no son herramientas generales mantenidas. No hay llamadas a ellos desde app, JS del CMS u otras pruebas.
- Esta entrada reemplaza como instrucciones vigentes las referencias historicas de este documento a manifest/payloads/ZIP/runners de piton retirados. Fuente actual: CMS1 y Media39–43; para desplegar despues usar sus cinco archivos existentes en `public/assets/media/cms/ecommerce/`, sin nuevas altas ni renombrar. Cualquier ZIP necesario se genera temporalmente fuera del repo y se retira al entregar.

| Area conservada | Archivos | MiB | Evaluacion |
| --- | ---: | ---: | --- |
| `editorial` | 10 | 2.52 | Imagenes finales de piton y camaleon. |
| `erp` | 57 | 2.65 | Adjuntos de ordenes y listas de proveedores; las rutas se usan en modelos. No depurar sin verificar registros y politica operativa. |
| `tmp` | 62 | 7.29 | Mezcla de reportes SEO/catalogo/Meta y scripts de analisis. El reporte `ecommerce_destinos_redirecciones_http_*_solo_errores_accionables.json` alimenta `seoRedireccionesErroresReporteInterno`; el nombre tmp no implica descarte seguro. Resto pendiente de revisar con cada modulo, no declarado necesario ni basura. |
| `uat` | 806 | 6.72 | Pruebas/herramientas y algunas evidencias. Hay referencias desde modelos, manuales y otras pruebas; no borrar en bloque. Falta revision individual de reutilizacion/entradas/salidas antes de depurar mas. |
| `backups` | 1 | 1.15 | `codex/Compra.php.corrupt-20260820-231819.bak`: copia historica de codigo, no SQL. Conservada hasta comprobar que no es unica evidencia de recuperacion. No crear mas respaldos aqui. |
| `cms_blog_preview_local` | 1 | <0.01 | Configuracion privada necesaria para preview local; no exponer su contenido. |

- `cms_media_resguardo` esta vacio y se conserva: es infraestructura de recuperacion usada por Media. Tampoco se tocan respaldos externos, archivos publicos ni recursos de otros trabajos.
- Alcance de la auditoria: inventario completo y busquedas dirigidas de dependencias; no certifica como descartables todos los archivos restantes. No se ejecutaron scripts de importacion/prueba/migracion ni limpieza por antiguedad.
- Siguiente del blog: revisar camaleon en frontend local y ajustar el borrador existente; publicacion y hosting siguen pendientes de autorizacion. Siguiente de mantenimiento: si se retoma, revisar tmp/UAT por modulo con dependencias y resumen vigente antes de retirar mas archivos.

### Limpieza de archivos del camaleon y criterio de almacenamiento / 2026-11-06

- IA: Codex GPT-6. El dueño solicita conservar solo las imagenes del blog y no acumular investigacion, pruebas, payloads ni copias de trabajo en storage. Regla vigente para futuros articulos: conservar imagenes finales necesarias, contenido/relaciones en CMS y un resumen en esta documentacion existente. Limpiar auxiliares al terminar; no generar otro paquete de respaldo dentro de storage ni usarlo como bitacora permanente.
- Camaleon: conservar exclusivamente los cinco WebP finales en `storage/editorial/blog_camaleon/imagenes_revision/`: portada,terrario-ventilacion,iluminacion-calor,hidratacion-decoracion,alimentacion-suplementos. Retirar46auxiliares (PNG originales,copias de referencias,capturas,JSON,HTML y scripts de preparacion), y los tres runners/UAT temporales exclusivos del camaleon en storage/uat.
- Resultado comprobado:49archivos auxiliares retirados,17.09MiB liberados y solo cinco WebP finales en la carpeta, con hashes sin cambios. Lectura del CMS confirmo ID2/borrador,portada+cuatro apoyos,ocho productos,seis categorias; las cinco copias publicas Media46–50 conservan sus hashes. Sin escrituras BD.
- Fuentes operativas: CMS articulo2 y Media46–50, con archivos activos en `public/assets/media/cms/ecommerce/`. Antes de limpiar se verificaron los cinco archivos publicos y hashes originales. No borrar ni modificar esos archivos, la BD, auditoria, respaldos externos ni configuracion privada de preview local.
- Esta entrada tiene precedencia sobre referencias historicas a archivos auxiliares/runners del camaleon eliminados. Para continuar consultar CMS2/Media46–50 y los cinco WebP finales; no reconstruir automaticamente los paquetes retirados.
- Siguiente: revisar/editar el articulo existente desde CMS y frontend local; no crear duplicados ni acumular nuevas capturas/payloads persistentes salvo peticion expresa.

### Camaleon registrado como borrador y visible localmente / 2026-11-05

- IA: Codex GPT-6. Autorizacion directa: cinco imagenes y registro de borrador para frontend local **sin publicar**. Ejecutado: **ID2**,slug `camaleon-preparar-terrario`,**borrador**,portada+cuatro apoyos,ocho productos,seis categorias,cero pines/videos. Editor `/cms/blog?id=2`.
- Media **46 portada,47 terrario,48 luz/calor,49 agua/decoracion,50 alimento/suplementos**. Cinco GET locales sin sesion/query nueva devuelven200,image/webp y hash original. URLs/recibos en `storage/editorial/blog_camaleon/manifest_media_registrada.json`; payload aplicado con URLs reales en `payload_formulario_registrado.json`. Plantillas v1 con tokens son historicas: NO reenviarlas.
- Respaldo puntual externo antes de altas: `C:/xampp/panel_db_backups/productivo_panel_20261105_004249_antes_camaleon_blog_media_fd4ea2.json`,826bytes,SHA256 `c04a8effc0c07db0a8282828dd22369055295a0b4d8935d818c5b1d70abb3d68`. Sin DDL ni cambios a catalogo/inventario. Piton ya estaba **publicado** al iniciar esta tarea y conserva estado/hash; no devolverlo a borrador por asumir el historial.
- Runner `storage/uat/uat_cms_blog_camaleon_apply_authorized.php`: preflight de lectura por defecto, token para aplicar, origen/hash restringidos, respaldo verificado,deduplicacion/recibos parciales,transaccion del borrador,auditoria sin suplantar sesion y guardia de slug/idempotencia. No ejecutar importadores de piton ni repetir carga a ciegas.
- Preview ampliado a lista explicita ID/slug en configuracion privada, conservando piton y agregando camaleon. Controlador `EcommercePublico::blog_preview_local` y adaptador frontend `blogLocalPreview` admiten allowlist/filtro/paginacion; mantienen GET,host/IP exactos,clave por header,no-store/noindex,sin CORS. No habilitar otros borradores por querystring ni empaquetar configuracion privada.
- Revisar **http://artiani.com.local/blog/camaleon-preparar-terrario** con aviso Vista previa local · Borrador. Listado local conserva ambos articulos. API publica normal no entrega camaleon aun con flag de preview. Esto no despliega archivos al hosting: fisicamente estan en XAMPP.
- QA:16 comprobaciones GET (`uat_blog_camaleon_local_readonly.php`),12 offline de adaptador/aviso,sintaxis PHP/JS y navegador aislado desktop1440/mobile390 con POST bloqueados:200,cinco imagenes,ocho tarjetas,cero pines,sin overflow,listado con ambos. Capturas `qa_cms_registrado/desktop.png` y `mobile.png` inspeccionadas. UAT antiguas que fijaban piton=borrador son historicas y no representan su estado actual.
- Apache estaba apagado; se inicio para revisar. Lanzamiento separado dejo de responder; sesion persistente de httpd quedo atendiendo pruebas finales. Sin cambios de configuracion ni instalacion de servicio.
- Siguiente: revision humana local y ajustes del mismo ID2. Hosting/publicacion solo con instruccion posterior; conservar originales y nombres Media. No regenerar imagenes ni activar demo.

### Primer borrador completo de camaleon preparado / 2026-11-05

- IA: Codex GPT-6. Se recibe entrega del chat de contenido con autorizacion para preparar paquete completo, explicitamente **sin registrar, publicar o modificar BD**. Se respeta: ninguna alta Blog/Media ni POST, ningun pin, ninguna habilitacion de preview privada o cambio a piton.
- Vista local independiente `storage/editorial/blog_camaleon/revision_cms_v1.html`: titulo/extracto, portada1600x1200, cuatro apoyos1536x1024 con ALT/captions EXACTOS del manifest, texto breve neutral y productos propuestos al final. No es pagina frontend publicada ni fila CMS. Fuentes editoriales originales intactas en articulo.html; HTML integrado sin H1 duplicado en `articulo_integrado_cms_v1.html`.
- `payload_formulario_v1.json` preparado para contrato POST autenticado de `/cms/blog_publicacion_guardar_erp`: campos JSON serializados, borrador, fecha vacia, autor/SEO, portada y cuatro relaciones de imagen, ocho productos, seis categorias, videos/pines vacios. **NO enviar aun:** contiene cinco tokens `@@MEDIA_CAMALEON_*_URL@@` porque no se subieron imagenes a Media; resolver con URLs reales antes de usar. No lleva clave CSRF persistida ni ID inventado. `preparacion_cms_v1.json` marca listo_para_enviar=false y contiene ficha, validaciones, hashes, condiciones comerciales y pendientes.
- Seleccion provisional de productos fotografiados:269 TERAL-12,2174 CAM-01,191 HPT2186,1733 HPT2110,134 grillo base millar,137 tenebrio1kg,2673 zophoba1kg,310 FL2151/calcioD3. Se revalidan con tarjetasPublicasPorIds del modelo actual de API, solo SELECT; objetos completos en `productos_tarjetas_verificadas_v1.json`. No son kit ni aprobacion biologica/electrica. Availability de consulta:191/134/137 consultar_disponibilidad; los otros5 agotado. No trasladar valores del snapshot a promesas ni texto.
- Categorias editoriales actuales verificadas:486 Reptiles generales,594 Terrarios generales,489 Calefaccion,490 Decoracion/aditamentos,488 Alimentos vivos,487 Alimentos para reptiles. No copiar automaticamente al Blog la categoria principal aves/iguana de algunos productos: seleccion editorial conserva enfoque camaleon, sin modificar taxonomia del catalogo.
- Runner `preparar_borrador_v1.php`: solo lectura BD y artefactos locales. Once comprobaciones correctas: integridad cinco WebP/ALT/caption, cuatro figuras, texto neutral, sanitizacion real sin perdida, ocho identidades, seis categorias, sin pines/duplicado/fecha de publicacion ni CSRF guardado. Sanitizacion ensayada en memoria con URLs ficticias, nunca guardado/rollback en productivo. Nota small pasa a parrafo porque CMS no admite esa etiqueta, conservando texto.
- QA `qa_preparacion_v1.js`: Chromium aislado, solo archivos locales, sin red externa/POST; escritorio1440 y movil390, cinco imagenes y ocho tarjetas cargadas, sin overflow. Capturas `qa_preparacion_v1/escritorio.png`, `movil.png` y `alimentacion.png`; escritorio/movil inspeccionadas. Primer intento sandbox bloqueo lectura de imagenes; ejecucion permitida completa paso. Se forzo carga de imagenes lazy para evitar espera indefinida de decode.
- Siguiente: revision humana del paquete (texto, cinco imagenes, condiciones de productos). Despues solicitar/recibir autorizacion separada para subir Media y registrar borrador; resolver URLs, comprobar acceso, revalidar slug/catalogo y conservar auditoria/CSRF. Publicar y habilitar revision frontend del nuevo ID son pasos posteriores. No reejecutar importadores de piton ni generar otras imagenes.

### Camaleon: encargo visual de alimento y D3 / 2026-11-05

- IA: Codex GPT-6. El dueño aclara que la ampliacion tambien debe integrarse visualmente. Mensajes anteriores al chat de contenido indicaban conservar cuatro imagenes y no generar extras; esa instruccion queda sustituida por apoyo adicional de alimentacion/suplementos1536x1024(3:2),WebP menor2MB. Mantener las cuatro primeras, sin rehacer todo ni cargar CMS.
- Referencias originales adicionales copiadas desde archivos publicos locales: grillo ALI-GLOMDM/publicacion134,tenebrio ALI-TEBRIO1K/137,zophoba ALI-ZOBA1KG/2673; en `storage/editorial/blog_camaleon/referencias_catalogo/`. CalcioD3 FL2151/310 ya disponible. Las fotos base de grillo/tenebrio no representan paquetes25piezas de IDs138/140; no intercambiar etiquetado ni pines.
- `encargo_contenido.md` y `revision.json` actualizados. Se envia instruccion concreta al chat Estrategia y Produccion de Contenido…(01a16c4f-e796-7690-b667-2f1268a4d00d), conforme al encargo directo del dueño. Cuerpo breve neutral conserva enfoque; sin precios, dosis o promesas visuales de dieta completa.
- Siguiente: revisar nueva composicion nutricional junto con las cuatro imagenes anteriores y el texto breve; despues preparar carga de borrador cuando se autorice. No generar en paralelo desde CMS/BLOG ni escribir BD/Media.

### Criterio editorial: guia breve y neutral

- IA: Codex GPT-6. Correccion expresa del dueño: camaleon debe parecerse a piton bola, corto/conciso/profesional, sin repetir Artiani ni convertir cada seccion educativa en oferta. Regla reusable del Blog: productos en imagenes y relacion final; cuerpo orientado a resolver dudas, con cierre comercial discreto. No insertar modelos, SKU, watts maximos de accesorios ni enlaces de compra en cada tema.
- `storage/editorial/blog_camaleon/articulo.html` reescrito con secciones breves de terrario/ventilacion, luz/calor, agua/medicion, ramas/sustrato y alimentacion/suplementos, mas referencia final a productos relacionados. Conserva cuidado general, calcioD3, alimento vivo y fuentes compactas. Se retiran ficha TERAL-12, datos CAM-01 y frases de tienda del cuerpo; siguen en ficha interna para imagenes y tarjetas. Esta correccion reemplaza el enfoque comercial de notas anteriores.
- `revision.json` actualiza CTA, tono, SEO y siguiente paso; `encargo_contenido.md` registra la correccion con precedencia sobre mensajes previos. No cambiar fotos reales ni regenerar cuatro imagenes por una edicion de texto. No escrituras BD/Media ni registro CMS.
- Siguiente: revisar texto breve con el dueño y las cuatro imagenes; seleccionar productos finales compatibles y preparar carga como borrador cuando se autorice. Conservar estas preferencias al redactar publicaciones nuevas.

### Camaleon: alimento vivo y calcio con D3 incorporados / 2026-11-04

- IA: Codex GPT-6. El dueño confirma que vende calcio con vitaminaD3 y alimento vivo y solicita incorporarlo a la ficha. Antes solo habia nutricion generica y suplementos candidatos; ahora `articulo.html` incluye seccion dedicada y CTA con alimento/suplementos, sin dosis o frecuencia universal. Fuente5 agregada: MSD Providing a Home for a Reptile, dieta/suplementacion.
- `revision.json.alimentacion_y_suplementos` documenta oferta y variantes. Consulta read-only actual del catalogo: grillos medianos(4 publicaciones,producto77), tenebrios(5,producto604), zophobas(2,producto1563), FL2151/publicacion310 calcioD3 y FL2152/publicacion193 calcio minerales. Enlaces editoriales elegidos138,140,2673,310; no alta de relaciones ni copia de precios. Paquete de alimento no es racion, D3 no implica multivitaminico completo. Disponibilidad se revalida al consumir API; FL2151/zophobas figuran agotados, no anunciar entrega inmediata.
- Evidencia completa `storage/editorial/blog_camaleon/catalogo_publico_alimentacion.json`. GET local fallo por conexion rechazada Apache(HTTP0,curl7); no iniciar servicios por esta tarea. Runner `consultar_catalogo.php --alimentacion --modelo` reutiliza modelo publico por CLI, conexion habitual productiva exclusivamente SELECT; no hay escrituras BD/DDL.
- Progreso visual confirmado por snapshot del chat y archivos: cuatro WebP entregadas para revision humana, manifest `manifest_imagenes_revision.json` (ruta corregida en revision.json). Portada1600x1200 y3apoyos1536x1024,215654/142924/267438bytes y portada245722bytes. No cargadas a Media ni CMS. No regenerar solo por ampliar texto nutricional.
- `encargo_contenido.md` y copy de redes actualizados. Siguiente: revisar texto/ampliacion junto con las cuatro imagenes; seleccionar tarjetas representativas sin duplicar todas las presentaciones; preparar payload y registrar borrador cuando el dueño lo autorice. Camaleon todavia no tiene articulo CMS ni preview frontend habilitado; no confundirlo con ID1/piton.

### Aviso visible de borrador en frontend local / 2026-11-04

- IA: Codex GPT-6. El dueño confirma necesidad de ver borradores localmente antes de publicarlos. Se aclara: endpoint privado local SI entrega el borrador habilitado; endpoint publico normal NO lo entrega. No confundir ambos contratos al comunicar avances.
- Se agrega aviso «Vista previa local · Borrador» en listado/detalle del frontend (`BlogCard.php`, paginas blog/blog-detail y CSS). La etiqueta usa estado real y solo aparece con entorno/host/IP locales y respuesta del transporte privado. No cambia CMS ni acceso, ni expone clave, ni habilita preview por querystring.
- Se conserva configuracion de revision privada ID1. No se publica en BD compartida por revisar frontend. Camaleon aun no esta registrado; habilitar su revision cuando corresponda. No afirmar que todos los borradores ya se muestran automaticamente.
- Verificacion:12 checks offline de adaptador/aviso y7 controles GET del endpoint/estado, sin escrituras BD. QA local aislado escritorio/movil con POST bloqueados revisa aviso en listado/detalle y conserva tarjetas/imagenes.
- Siguiente: dueño revisa articulo local con aviso. Sigue pendiente como tarea del editor CMS mostrar estado real y revisar guardado de publicados; no alterar ese flujo solo para resolver la vista previa. Despliegue/publicacion requieren el paso posterior indicado por el dueño.

### Aclaracion de estado y revision local / 2026-11-04

- IA: Codex GPT-6. El dueño pregunta por que ve piton en frontend aunque CMS indica borrador y el selector solo muestra borrador/pausado. Verificacion GET `uat_blog_preview_local_readonly.php`: ID1 conserva borrador, preview privado local permite revision y API publica normal no entrega ese borrador. No se publico por mostrarlo en artiani.com.local.
- UI actual: `cms_blog_estado` es Estado de trabajo con borrador/pausado; Publicar es boton separado `cms_blog_publicar_btn` al final del formulario. El filtro/listado si conoce publicado. La interfaz no explica suficientemente esta distincion.
- Pendiente UX confirmado por lectura: cargarItem mapea todo estado distinto de pausado a borrador y adminGuardar solo admite borrador/pausado. Un ajuste futuro debe mostrar estado real y revisar el flujo de edicion de publicados para evitar degradarlos accidentalmente al guardar. No resolver agregando solo una opcion publicado al select: el backend la normaliza a borrador y la accion de publicar tiene validaciones/permisos propios. Evaluar como tarea especifica, sin escrituras productivas para diagnosticar.
- Siguiente: aclarar visualmente estado real, guardado y accion Publicar; indicar vista previa local en la revision. Despliegue/publicacion real siguen pendientes del flujo documentado y aprobacion del dueño.

### Encargo visual enviado a contenido / 2026-11-04

- IA: Codex GPT-6. Autorizacion expresa del dueño para enviar informacion/fotos y medidas al chat existente `Estrategia y Produccion de Contenido…` (local,01a16c4f-e796-7690-b667-2f1268a4d00d). `send_message_to_thread` confirmado; snapshot posterior muestra turno en progreso. Se conserva modelo/configuracion del chat, sin crear otro.
- Paquete local `storage/editorial/blog_camaleon/encargo_contenido.md`, `manifest_referencias_contenido.json` y nueve originales900x900 en `referencias_catalogo/`: TERAL-12,CAM-01/02/03,HPT2186/2187/2110,FL2152/2151. Descarga soloGET desde catalogo, identidad/hash/dimensiones documentadas; sin transformaciones ni altas Media. Runner `preparar_referencias_contenido.php`.
- Solicitud de cuatro WebP: portada1600x1200(4:3,coincide con cover vigente app.css del frontend); apoyos terrario/ventilacion, luz/calor e hidratacion/decoracion1536x1024(3:2). Cada archivo menor2097152bytes; objetivo250–600KB. Entrega en `imagenes_revision/`, preview y manifest para revision humana.
- Se enviaron decisiones aluminio/vidrio TERAL-12 frente a madera Magno, foto real obligatoria, condicion de talla, limites de campanas/UVB/suplementos, sin kit/precios/stock inventados y sin pines antes de imagen final. Productos condicionales como referencias, no montaje aprobado. No generar/publicar desde CMS/BLOG en paralelo.
- Siguiente: el dueño revisa las cuatro imagenes en el chat de contenido. Recuperar rutas/manifest compartido al completarse; revisar assets antes de preparar carga CMS. El envio no autoriza escritura BD, Media ni publicacion.

### Revision TERAL-12 y campanas con fabricante / 2026-11-04

- IA: Codex GPT-6. El dueño precisa fabricacion propia para camaleon, aluminio/malla metalica y ventilacion en dos costados y arriba; aporta enlace/SKU TERAL-12. La revision anterior usaba tarjetas de listado y era insuficiente para conocer construccion: revisar siempre descripcion detallada y fotografia antes de descartar productos por material o uso.
- GET del frontend local responde200. Detalle API de publicacion269 confirma TERAL-12,50x40x80cm, marcos aluminio, cristal6mm, puertas deslizables y malla metalica en costados/techo. Foto original900x900 revisada. Evidencia local: `fichas_teral12_campanas.json`, `ficha_teral12_frontend.html`, `teral12-referencia-1.webp`; runner CLI `revisar_teral12.php` solo GET, sin JS/analytics ni BD.
- Articulo local incorpora TERAL-12 con enlace real y ventajas de material/ventilacion. No equivale a validar tamaño definitivo de cualquier adulto: conserva referencia61x61x122cm exclusiva de velado y seleccion por especie/talla. No atribuir drenaje, cierre antiescape, ausencia de corrosion ni accesorios incluidos sin evidencia.
- Campanas: CAM-01(publicacion2174) declara E26,max150W,110-120V; capacidad maxima del accesorio no es potencia recomendada al animal. CAM-02(2175) y CAM-03(2176) no declaran esos valores: no heredarlos. Articulo enlaza CAM-01 como accesorio, sin atribuir UVB/control termico ni montaje automatico. Las cuatro fichas consultadas indican agotado; no anunciar existencia inmediata.
- Aclaracion final del dueño2026-11-04: TERAL-12 es un modelo distinto de aluminio y vidrio; Magno2096/2099 y los demas modelos comentados siguen fabricandose de madera como indica su catalogo. Materiales confirmados, pendiente cerrado; no volver a preguntar ni extender aluminio a toda la linea. `revision.json` actualizado; sin alta CMS/Media ni escrituras BD.
- Siguiente: revisar texto comercial y usar foto real TERAL-12 como referencia para las imagenes si se solicitan. Conservar producto relacionado completo del catalogo al preparar futura carga.

### Segunda propuesta editorial: camaleon / 2026-11-04

- IA: Codex GPT-6. El dueno solicita informacion para otra publicacion y elige guia general al no conocer la especie mas atendida. Alcance: camaleones arboricolas comunes (velado/pantera), sin parametros universales ni mezcla de necesidades por especie.
- Preparacion local en `storage/editorial/blog_camaleon/`: `articulo.html`, `revision.json`, dos snapshots de catalogo con objetos completos y `consultar_catalogo.php` (CLI, solo GET; `--complemento` amplía evidencia). Sin alta CMS ni escrituras BD/DDL/Media. Imagenes solo en briefs; no generadas.
- Fuentes consultadas: MSD manejo reptiles, RVC velado (texto indexado; PDF directo403), Aurora Animal Hospital y Chameleon Academy. Redaccion original, referencias compactas, sin copiar imagenes/tablas. Las fuentes discrepan en rangos: no publicar promedio ni receta general. Referencia61x61x122cm exclusiva del velado; no tratarla como universal.
- Catalogo: 45 terrarios,24 calefaccion,15 decoracion;4 UVB por consulta puntual. Magno2096/2099 miden50x50x120cm, menores en base que referencia de velado: candidatos a revision, no alojamiento adulto aprobado. Compactos191/2053 requieren validar cobertura/montaje; campana2174 y foco1733 no completan sistema. Suplementos193/310 requieren formula/plan veterinario. Ningun ID aprobado aun para relaciones CMS.
- Sin producto publico validado para goteo/pulverizacion, higrómetro, controlador aereo, UVB lineal o ramas seguras. Busquedas tienen falsos positivos de acuario/perros/aves; no reutilizarlos. Ausencia publica no demuestra ausencia en ERP. No ofrecer sustrato de piton como obligatorio ni luz nocturna ni placa como solucion principal de asoleo.
- Siguiente: revisar contenido y compatibilidad comercial; despues producir imagenes cuando se solicite, preparar payload, registrar borrador con autorizacion y conservar contrato normal de tarjetas. No publicar ni modificar el articulo de piton bola por esta tarea.

### Productos relacionados con contrato normal de catalogo / 2026-11-02

- IA: Codex GPT-6. El dueno requiere pintar productos relacionados con la misma tarjeta usada en catalogo. Se elimina la proyeccion reducida del Blog que enviaba imagen/precio vacios y confundia presentacion con marca.
- `EcommerceBlogPublico::productosRelacionados` consulta IDs en orden editorial y delega a `EcommerceCatalogoPublico::tarjetasPublicasPorIds`. Este metodo compone `sqlPublicacionesBase`, `formatearPublicacion` y `publicacionCardPublica`, exactamente como catalogo `vista=card&agrupacion=sku`. Seleccion por lote hasta 50 IDs unicos; el detalle publico conserva limite8 y el editor50. Sin endpoint nuevo ni llamadas HTTP individuales.
- Contrato: `depurar.productos_relacionados[]` del detalle publico y preview local contiene la tarjeta normal completa: IDs publicacion/producto/SKU, sku, nombre, slug/slug_publico, URL/url_publica/canonical, imagen/ALT/fuente, precio/moneda/mostrar_precio, disponibilidad, permisos de cotizacion/WhatsApp, marca/marca_obj, categoria/categoria_obj, mascotas/necesidades y grupo_producto. No es el detalle de ficha con galeria/descripcion: es el objeto normal de listado; frontend reutiliza `product_card` y `normalizeItem`, sin otra consulta por producto. El manifest documenta esta integracion.
- La publicacion/SKU concretos elegidos por CMS se conservan, sin reagrupar ni sustituirlos por otro SKU del mismo producto. Catalogo aplica reglas de activos, publicados, lista vigente, precios ocultos, granel y stock no exacto; Blog no calcula ni guarda copias comerciales. Relaciones a productos que ya no sean visibles se omiten.
- Prueba real read-only `storage/uat/uat_blog_tarjetas_catalogo_readonly.php`: cinco relacionados identicos campo por campo a catalogo normal, contrato completo, IDs invalidos vacios, orden/deduplicacion/inexistentes, sin costos/stock exacto, borrador permanece oculto publicamente. Los cinco tienen imagen; marca vacia en origen para IDs2747/2754/2174/2175, no se inventa ni se escribe BD para completarla.
- QA de navegador aislado actualizado: escritorio1440/movil390 responden200, cinco tarjetas con IDs/SKU y precios publicos coherentes; las cinco imagenes de producto cargan con dimensiones naturales positivas. Portada/apoyos intactos, sin overflow. Se permiten solo GET de imagenes publicas Artiani; POST y otros externos bloqueados. Capturas de tarjetas `qa_local/desktop-productos.png` y `mobile-productos.png` inspeccionadas. Tambien pasan siete pruebas offline de adaptador y siete controles del preview privado.
- No hay escrituras BD, publicacion ni despliegue. Siguiente: revisar tarjetas en `http://artiani.com.local/blog/piton-bola-preparar-habitat`; al aprobar despliegue incluir juntos ambos modelos y las cinco imagenes, excluyendo la configuracion privada de preview. Completar marcas solo cuando el dueno valide sus datos en Catalogo.

### Revision funcional en frontend local / 2026-11-01

- IA: Codex GPT-6.1-sol. El dueno indica primero revisar local y subir hosting despues. Esta instruccion reemplaza la publicacion previa pendiente: **no cambiar estado productivo para una revision local**.
- Listado `http://artiani.com.local/blog` y detalle `http://artiani.com.local/blog/piton-bola-preparar-habitat` responden 200 y muestran el articulo real, portada, cuatro apoyos y dos bloques/seis pines. No es fixture ni demo.
- Frontend `app/Api/ErpEcommerceApi.php` y `app/Pages/blog.php` dejan de forzar demo=1. No se usa fallback a fixture para Blog normal. HTML de imagenes y relaciones de media/bloques se resuelven contra host API local; links comerciales permanecen del frontend.
- Para conservar Blog1 como borrador en BD compartida se agrega endpoint privado de revision `/ecommercePublico/blog_preview_local/{slug}`: GET, Host panel.com.local, IP loopback y clave privada servidor-servidor. Solo permite el ID/slug habilitados. Sin clave o con slug/host distinto no entrega contenido. Sin CORS ni cache, noindex.
- Activacion local `storage/uat/activar_blog_preview_local.php`, sin BD: crea clave en `storage/cms_blog_preview_local/access.json`, fuera de public y excluido de Git. **No mostrar ni empaquetar esa carpeta/clave**. Para desactivar, habilitado=false en el archivo privado; el frontend vuelve al API publico normal.
- El adaptador frontend usa esa revision solo con APP_ENV=local, Host artiani.com.local, IP loopback y API http://panel.com.local. Lee configuracion local por servidor; no conecta MySQL ni transmite clave a navegador. No cachea la revision ni la mezcla con fixtures. Otros entornos consultan publicaciones publicas reales.
- Compatibilidad corregida sin escribir BD: tipo de bloque historico imagen-productos se entrega como imagen_productos para el renderer. Por eso ahora se ven los dos bloques reales.
- QA: 7 comprobaciones offline de adaptador, 7 controles de acceso/estado por GET, 37 contratos catalogo y 126 defensas publicas, sintaxis PHP. Navegador Chromium headless aislado (sin perfil/sesion de Chrome usuario) bloqueo POST y externos: escritorio1440 y movil390, 200, siete elementos img correspondientes a cinco WebP1536x1024, todos cargados, seis pines, sin overflow. Capturas `storage/editorial/blog_piton_bola/qa_local/desktop.png` y `mobile.png` inspeccionadas.
- No se cambio DB ni se publico/desplego en esta tarea. Imagenes siguen faltando en hosting; el paquete de cinco archivos y manifest anteriores siguen vigentes. Cambios concurrentes Videos del repo frontend se conservaron intactos.
- Siguiente: revision humana en el enlace local. Despues del visto bueno, sincronizar cinco archivos al hosting, desactivar/excluir preview privado, desplegar codigo compatible y solo entonces autorizar publicacion real. No generar nuevamente articulo/imagenes ni reactivar demo.

### Cinco imagenes registradas y vinculadas / 2026-11-01

- IA: Codex GPT-6.1-sol. El dueno solicita subir cinco WebP, vincular y verificar antes de publicar; despues indica usar el sistema del proyecto. Se completa importacion CLI con validadores Media existentes, sin suplantar sesiones.
- Media IDs 39 portada, 40 terrario, 41 temperatura, 42 humedad/sustrato, 43 refugios/agua. Las cinco son WebP 1536x1024. URL/hash/bytes y estado de acceso en `storage/editorial/blog_piton_bola/manifest_media_registrada.json`.
- Articulo productivo ID1 queda en **borrador** con portada, cuatro apoyos, dos bloques interactivos, cinco productos y seis categorias. HTML conserva fuentes compactas v4 y sustituye todos los tokens por URLs reales.
- Los cinco archivos fisicos estan en `public/assets/media/cms/ecommerce/`. GET local con query unica sin sesion: 200 image/webp y SHA256 exacto en los cinco. La misma URL en `https://sys.artiani.com.mx` devuelve 404 HTML para los cinco. Base compartida no implica almacenamiento fisico compartido; NO publicar hasta transferir y verificar hosting.
- ZIP de despliegue: `storage/editorial/blog_piton_bola/imagenes_blog_piton_para_hosting.zip`; extraer conservando nombres exactos en `assets/media/cms/ecommerce` del public del hosting del panel. No volver a dar de alta los medios ni generar otros nombres; las filas ya existen.
- Bloqueo adicional frontend comprobado por lectura: `C:\xampp\htdocs\frontend\ecommerce-publico\app\Api\ErpEcommerceApi.php`, `getBlog` y `getBlogPost` fuerzan demo=1. Debe retirarse del consumo normal. Revisar ademas URLs de imagenes de contenido/bloques contra el host API: el normalizador adapta portada pero las relaciones se agregan despues y HTML conserva src relativos. No se editaron archivos del repo frontend.
- Control visual del navegador no disponible: la herramienta rechazo Google Chrome por falta de permiso. No se afirma inspeccion visual en frontend. Busqueda de integracion para SFTP/cPanel no encontro opcion pertinente; no hay conexion de hosting configurada en el proyecto inspeccionado.
- Implementacion: `EcommerceCatalogoPublico::mediaImportarEditorialCli` restringe fuentes a storage/editorial y PHP CLI. Comparte inspeccion, deduplicacion, nombres, permisos y registro con upload HTTP; el flujo HTTP conserva is_uploaded_file/move_uploaded_file. Solo CLI copia el original aprobado; no abre endpoint de importacion HTTP.
- Runner aplicado una vez: `storage/uat/uat_cms_blog_imagenes_apply_authorized.php`; no reejecutar sobre articulo ya modificado, guardia de contenido evita sobrescribir. Conserva recibos parciales en caso de fallo; revisar biblioteca antes de reintentar.
- Respaldo externo puntual previo: `C:\xampp\panel_db_backups\productivo_panel_20261101_205112_antes_blog1_imagenes.json`, SHA256 `fdf1dde4aaecca6e5fa3ce8bfeb7583f8fb21182e1dce438f16c4c401a4a7e82`. Contiene fila del articulo, relaciones de media/bloques y coincidencias previas de hash; no es respaldo completo.
- Pruebas: sintaxis PHP y 17 escenarios Media aislados correctos tras adaptar el harness al servicio comun. Primera ejecucion sandbox bloqueo rename de fixtures; prueba elevada completa paso. No hubo DDL ni cambios a productos/inventario.
- Siguiente: transferir cinco archivos al hosting, comprobar 200/hash sin sesion y corregir consumo demo del frontend. Publicacion ya solicitada por el dueno, pero NO ejecutada al fallar requisitos de despliegue. Tras resolverlos usar `adminEstatus` con auditoria y verificar API/listado/detalle/frontend; no pedir nueva autorizacion para la misma publicacion salvo cambio de alcance.

### Referencias editoriales compactas / 2026-11-01

- IA: Codex GPT-6.1-sol. Ajuste solicitado y aplicado al borrador productivo ID1: referencias numeradas en texto y tres enlaces agrupados al final bajo `Fuentes de consulta`.
- Conserva datos de cuidado; agrega nota orientativa y aclara que las fuentes no avalan Artiani ni sus productos. Enlaces externos al final abren aparte con noopener/noreferrer; enlaces a categorias intactos.
- Se modificaron solo contenido_html/contenido_texto/fecha_actualizacion. Conserva borrador, SEO, imagenes y relaciones; no publica. Verificacion posterior: contenido exacto y auditoria confirmados.
- Fuente actual para captura con imagenes: `storage/editorial/blog_piton_bola/payload_formulario_v4.json` (sustituye editorialmente v3, conserva tokens Media pendientes). Texto ya guardado: `articulo_cms_referencias_v4.html`. No reimportar v3 porque regresaria la presentacion anterior de fuentes.
- Respaldo puntual externo: `C:\xampp\panel_db_backups\productivo_panel_20261101_191929_antes_blog1_referencias.sql`, 6878 bytes, SHA256 `ad6e9e9334e24497b61162ef6094fe12183804cc270b013752a763b897f2d661`. No contiene respaldo completo de BD; restaura solo los tres campos previos del articulo con plan autorizado.
- Runner `storage/uat/uat_cms_blog_referencias_apply_authorized.php`: CLI/token, verifica estado/slug/contenido bajo bloqueo, no sobrescribe cambios concurrentes y es idempotente si ya esta aplicado. Archivo de cambio v4 conserva texto anterior/nuevo.
- Siguiente: subir las imagenes aprobadas en editor y revisar el borrador antes de solicitar publicacion. Sin cambios a esquema o Media en esta tarea.

### Activacion productiva y carga en Blog / 2026-11-01

- IA: Codex GPT-6.1-sol. El dueno autoriza expresamente respaldo y ocho tablas tras confirmar conexion productiva `artianicom_sys`. No inferir que XAMPP es base de pruebas.
- Respaldo completo: `C:\xampp\panel_db_backups\productivo_panel_20261101_antes_activar_blog_190451.sql`, 265906487 bytes, SHA256 `f0a6be024f27b91558a08e84e304ab5459639268a98cdcd3c7b67bb076f42c1f`; mysqldump codigo 0 y marca final verificada. Referencia registrada en `docs/erp_respaldo_bd_estandar.md`.
- Se aplico exclusivamente `EcommercePublicoEsquema::planActualizarCmsBlog(true)` (ocho CREATE del submodulo). Auditoria final: ocho disponibles, cero faltantes, cero DDL pendientes. No cambios de esquema a catalogo, inventario ni Videos.
- Runner CLI con token/respaldo: `storage/uat/uat_cms_blog_piton_apply_authorized.php`. Reusa modelo, transaccion del borrador y comprobacion por slug; reejecutar no sobrescribe articulo existente. Registra evento de auditoria con origen CLI sin suplantar usuario. DDL no es transaccional; ante error revisar auditoria antes de reintentar.
- Articulo real ID **1**, slug `piton-bola-preparar-habitat`, estado **borrador**, cinco productos y seis categorias. Sin portada ni imagenes, sin tokens Media en el HTML. API publica no lo devuelve. Abrir `/cms/blog?id=1`.
- `adminGuardar` permite borradores sin portada para capturar texto primero; al adjuntar imagen exige URL permitida/ALT. Publicacion exige portada con URL y ALT. No se puede publicar el borrador incompleto.
- Vista/JS Blog agregan `Subir imagen al Blog`: original hasta 2 MB, ALT, nombre opcional, destino portada/apoyo al final/solo biblioteca. Reusa POST `media_admin_subir_erp`, permiso existente de edicion, CSRF, uso blog/tipo editorial y comprobacion publica `CmsMediaTools.verificarDisponibilidad`. No agrega endpoints ni permisos.
- Una carga confirmada con advertencia no se aplica automaticamente ni se reintenta. Si el operador cambia de articulo durante la carga, queda en biblioteca sin asignarla al nuevo articulo. Despues de asignar debe pulsar Guardar borrador. Portada conserva dimensiones reales; carga de apoyo registra referencia y figura HTML.
- UI disponible en checkout `panel_de_control`; desplegar juntos modelo, vista y JS Blog al hosting si se opera desde sys.artiani.com.mx. Incluir `media_tools.js` vigente que ya usa el CMS.
- Verificaciones: PHP/JS sintaxis, UAT Blog read-only con conexion productiva confirmada, bloqueo de publicar sin portada y nueve escenarios de carga aislada (`storage/uat/uat_cms_blog_upload_aislada.js`, sin red/BD). Consulta posterior confirma ID1/borrador/5 productos/6 categorias/0 imagenes y oculto publicamente. No se probo subida real ni inspeccion visual en navegador.
- Siguiente: usar el boton de carga para los cinco WebP aprobados en `imagenes_revision/catalogo-v3-*.webp`, guardar y revisar portada/apoyos. Los pines se incorporan solo despues de tener las URLs reales. Publicar requiere autorizacion posterior. No volver a crear el primer articulo ni duplicar Media.

### Preparacion CMS con imagenes v3 / 2026-11-01

- IA: Codex GPT-6.1-sol. Se recibe serie visual como aprobada desde contenido. Cinco WebP inspeccionados: 1536x1024 (3:2), entre 239670 y 403336 bytes. No se alteraron imagenes ni se subieron a Media.
- Entrega actual: `storage/editorial/blog_piton_bola/revision_cms_v3.html` (articulo ilustrado y pines), `articulo_v3.html`, `payload_formulario_v3.json` y `preparacion_cms_v3.json` (manifest local, ALT, relaciones y checklist). Preserva paquetes anteriores.
- GET actual confirma publicaciones 2128 (TERA-8050), 2747 (coco), 2754 (corteza), 2174 (CAM-01), 2175 (CAM-02): todas agotadas. Son alternativas condicionales, no kit ni compra inmediata. No hay precios ni stock exacto.
- Dos bloques de pines propuestos sobre recorte 3:2 completo: portada (terrario/coco/CAM-01/CAM-02) y sustratos (coco/corteza). PRIVO-CUTROMD y PRIVO-CUTROCH quedan bloqueados fuera del payload por falta de publicacion/categoria/medidas; no atribuir aptitud para adultos. Medidores, controlador, foco, guardia, soporte, agua y decoracion genericos sin pin.
- Las escenas no demuestran componentes incluidos ni montaje tecnico correcto. Captions explican alcance; campanas requieren validar proteccion/control/potencia/distancia. Los ALT describen la escena sin prometer especificaciones.
- Payload conserva estado borrador y relaciones candidatas. Usa tokens `@@MEDIA_*_URL@@` en portada, HTML, SEO, imagenes y bloques: NO enviar sin resolverlos con URLs reales del alta Media autorizada. No usar rutas storage como URLs publicas. Obtener CSRF desde sesion al ejecutar.
- Siguiente: revision humana del conjunto; autorizar subida de estos cinco WebP, resolver tokens y comprobar acceso publico/esquema antes de guardar borrador autorizado. Publicar requiere instruccion posterior. No hubo escrituras BD, DDL, altas Media ni relaciones persistidas.

### Borrador editorial piton bola / 2026-10-31

- IA: Codex GPT-6.1-sol. Paquete local en `storage/editorial/blog_piton_bola/`: `revision.html` (articulo, matriz y briefs), `articulo.html` (HTML del cuerpo), `paquete_revision.json` (evidencia y checklist), `payload_formulario.json` (campos POST, no ejecutados).
- Articulo breve: `Piton bola: lo esencial para preparar su habitat`. Ofrece alternativas por talla y categoria; no impone kit. Clasificacion editorial independiente de disponibilidad.
- Se comprobaron por GET las categorias 486, 594, 489, 597, 490 y 505. Matriz de 44 candidatos con IDs de publicacion, SKU, categoria, referencia de imagen, disponibilidad y motivo de condicion/omision. No hay precios ni stock exacto.
- Terrarios y sustratos candidatos agotados; PRIVO refugios/bebederos no encontrados por busqueda publica (no implica inexistencia en ERP). Termometros 1802/1805 son de acuario/no validados; busquedas de higrometro/termohigrometro sin resultados. No reemplazar controlador de calefaccion por calentador sumergible de acuario.
- Cinco briefs con ALT propuestos: portada, terrario, temperatura, humedad/sustrato y refugios/agua. No hay imagenes generadas ni altas Media. Cinco referencias de producto responden HEAD 200 con MIME imagen local; revision visual y acceso desde frontend externo pendientes.
- Payload compatible con `$_POST` (multipart/form-data o formulario codificado); JSON serializado en imagen_portada/seo y relaciones. URL portada vacia, productos/imagenes/puntos vacios intencionalmente: completar antes de guardar. CSRF se obtiene de sesion al ejecutar, nunca se incluye en archivos.
- Limites detectados: relacion de producto solo guarda ID/orden, sin etapa/compatibilidad; detalle publico limita a ocho y devuelve imagen/precio vacios, sin disponibilidad. Resolver tarjetas desde API publica y coordinar etiquetas antes de ampliar contrato. Coordenadas interactivas solo sobre imagen final revisada.
- Estado de esquema no confirmado: intento GET de auditoria no devolvio JSON interpretable; revisar estado interno autenticado. No se aplico DDL ni se guardo/publico contenido.
- Siguiente: aprobar redaccion y alternativas, producir/revisar imagenes, completar URLs y puntos del payload, validar esquema read-only y obtener autorizacion final de guardado/publicacion.

Para continuar sin gastar tokens:

1. Leer este archivo.
2. Revisar `git diff -- app/modelos/EcommerceBlogPublico.php app/vistas/paginas/apps/erp/cms/blog.php public/assets/js/custom/apps/erp/cms/blog.js app/controladores/EcommercePublico.php app/controladores/Cms.php app/modelos/EcommercePublicoEsquema.php`.
3. Si se va a tocar UX, abrir solo `app/vistas/paginas/apps/erp/cms/blog.php` y `public/assets/js/custom/apps/erp/cms/blog.js`.
4. Si se va a tocar persistencia/reglas, abrir solo `app/modelos/EcommerceBlogPublico.php` y `app/modelos/EcommercePublicoEsquema.php`.
