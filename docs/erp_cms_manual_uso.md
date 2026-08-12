# CMS - Manual de uso operativo

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-10  
Estado: Manual vivo por secciones del modulo CMS

## Proposito

Este manual explica como usar el modulo CMS del ERP para administrar contenido ecommerce y, despues, plantillas frontend. Se actualiza por seccion conforme cada parte queda lista.

## Reglas generales

- El modulo CMS vive en el menu lateral `CMS`.
- La seccion `Contenido` administra bloques, slots, media, JSON y persistencia de contenido.
- La seccion `Frontend` prepara plantillas de vista, layouts, componentes y variantes.
- Wokiee es el primer tema visual activo, pero el CMS debe permitir registrar otros temas visuales futuros.
- En la fase actual todo lo editable es preview local/read-only: no escribe BD y no publica contenido real.
- El endpoint recomendado de arranque para el frontend es `/ecommercePublico/configuracion_inicial`.
- `/ecommercePublico/bootstrap` existe solo como alias legacy.
- El CMS no modifica catalogo, precios, inventario ni publicaciones de producto.

## CMS > Contenido ecommerce

Ruta: `/cms/contenido`

### Para que sirve

Esta pantalla es la mesa principal de trabajo editorial. Permite armar y revisar el contenido que despues consumira el frontend ecommerce por API:

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

5. Revisa `Plantilla visual de la pagina`.
   - `Plantilla de vista`: plantilla visual que el frontend renderizara, por ejemplo `wokiee_home_default`.
   - `Layout`: base visual, por ejemplo `storefront_wokiee_v1`.
   - `Secciones visuales`: relacion `slot -> componente -> variante -> orden`.
   - Usa `Ver plantillas de vista` para abrir `/cms/frontend_plantillas`.

6. Revisa `Preview visual frontend`.
   - Muestra una simulacion local de tienda con hero, promociones, cards y carruseles segun el JSON actual.
   - No es el HTML final del frontend.
   - El frontend ecommerce debe implementar sus propios componentes y consumir `/ecommercePublico/configuracion_inicial` o `/ecommercePublico/contenido_pagina`.

7. Selecciona un slot en el panel izquierdo.

8. Revisa `Publicabilidad por slot`.
   - `Publicable`: el slot cumple reglas locales.
   - `Con alertas`: puede revisarse, pero tiene advertencias.
   - `No publicable`: tiene errores que deben corregirse.
   - `Vacio opcional`: el slot puede quedarse sin bloques.
   - `Incompleto`: falta contenido requerido.

9. En `Bloques del slot`, revisa los bloques existentes.

10. Usa las acciones locales:
   - editar
   - duplicar
   - subir/bajar orden
   - pausar
   - quitar

11. En el editor, ajusta:
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

12. Pulsa `Aplicar a preview`.

13. Pulsa `Validar`.

14. Guarda borrador local si quieres conservar el preview en el navegador.

### Importante

- `Aplicar a preview` no guarda en BD.
- `Borrador local` usa el navegador, no la base de datos.
- `Restaurar defaults` vuelve al contenido default read-only.
- `Preview visual frontend` es una simulacion del panel; no genera archivos ni HTML productivo para el frontend.
- Los endpoints POST reales siguen bloqueados hasta autorizar respaldo y persistencia.

### Errores comunes

- Crear un bloque de tipo no permitido en el slot.
- Dejar un hero/banner sin `alt text`.
- Exceder el maximo de bloques de un slot.
- Poner `Vigente hasta` antes de `Vigente desde`.
- Dejar una coleccion de productos sin endpoint source.
- Usar `<script>` en `content_html_safe`.

### Cierre read-only de contenido

La seccion `CMS > Contenido ecommerce` queda preparada cuando se cumple:

- puedes cargar pagina/contexto/plantilla
- puedes seleccionar slots
- puedes crear y editar bloques locales
- puedes ordenar, duplicar, pausar y quitar bloques en preview
- puedes revisar resumen editorial
- puedes revisar publicabilidad por slot
- puedes validar reglas locales
- puedes guardar/cargar borrador local del navegador
- puedes revisar/exportar el JSON desde `CMS > Preview JSON`

Sigue bloqueado hasta autorizacion:

- guardar en BD
- publicar contenido real
- subir media real
- ejecutar DDL
- activar POST reales
- hacer que `/ecommercePublico/contenido_pagina` lea BD publicada

Siguiente paso operativo:

1. Revisar visualmente las pantallas de CMS Contenido.
2. Autorizar respaldo y DDL cuando se quiera activar persistencia.
3. Activar endpoints POST con CSRF/auditoria.
4. Conectar endpoints publicos a BD publicada con fallback default.

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

## CMS > Media

Ruta: `/cms/media`

### Para que sirve

Esta pantalla permite revisar visualmente la media asociada a los bloques del CMS:

- imagen desktop
- imagen mobile
- alt text
- bloque seleccionado
- slot donde vive el bloque
- estatus del bloque

En la fase actual es una Vista de inspeccion read-only. Todavia no sube archivos ni guarda rutas en BD.

### Flujo recomendado

1. Selecciona la pagina:
   - `Home`
   - `Categoria`
   - `Catalogo`

2. Si revisas `Categoria`, captura una categoria de prueba.

3. Selecciona un slot en el panel izquierdo.

4. En `Bloques`, elige el bloque que quieres revisar.

5. Observa el `Preview visual`.

6. Verifica que el bloque tenga:
   - imagen desktop cuando aplique
   - imagen mobile cuando aplique
   - alt text claro
   - CTA coherente si el bloque lo usa

7. Si detectas un problema, ve a `/cms/contenido` y ajusta el bloque en el editor.

### Como interpretar la vista

- Si aparece `Sin imagen`, el bloque todavia no tiene media declarada.
- Si el bloque es `hero_banner` o `category_banner`, debe tener alt text.
- Si el bloque es una coleccion de productos, la media puede venir desde productos del catalogo, no necesariamente desde el bloque CMS.
- `Media` solo revisa; no debe mostrar acciones destructivas.

### Importante

- Esta pantalla no sube archivos.
- Esta pantalla no reemplaza la politica futura de media.
- La carga real de imagenes requiere definir ruta publica, tamanos, formatos, nombres y respaldo.
- No se deben guardar rutas internas del ERP para que el frontend las lea directamente.

### Errores comunes

- Usar imagen desktop pero olvidar imagen mobile.
- Dejar alt text vacio en banners.
- Usar una imagen con ruta local/interna no publica.
- Confundir preview visual con publicacion real.
- Intentar editar contenido desde Media; la edicion debe hacerse en `/cms/contenido`.

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

Esta pantalla prepara la activacion real de la persistencia del CMS. Muestra el esquema propuesto, los contratos POST futuros y el checklist necesario antes de guardar contenido en BD.

En la fase actual es una vista read-only: no ejecuta DDL, no guarda bloques, no publica contenido y no sube media.

### Flujo recomendado

1. Abre `/cms/persistencia`.

2. Revisa el aviso `Persistencia real bloqueada`.

3. Revisa `Esquema propuesto`.
   - tablas existentes
   - tablas pendientes
   - total de DDL propuesto

4. Revisa el `Checklist de autorizacion`.

5. Confirma que los contratos POST aparecen como `Bloqueado`.

6. Revisa `Contratos del CMS` para confirmar endpoints internos y publicos futuros.

7. No intentes activar persistencia sin respaldo y autorizacion explicita.

### Checklist antes de activar escrituras

1. Generar respaldo externo en `C:\xampp\panel_db_backups`.
2. Documentar el respaldo en `docs/erp_respaldo_bd_estandar.md`.
3. Autorizar DDL de tablas CMS.
4. Aplicar tablas:
   - `erp_ecommerce_plantillas`
   - `erp_ecommerce_plantilla_slots`
   - `erp_ecommerce_contenido_bloques`
   - `erp_ecommerce_contenido_publicaciones`
   - `erp_ecommerce_contenido_media`
5. Activar endpoints POST con CSRF.
6. Agregar auditoria explicita.
7. Validar permisos `cms.editar` y `cms.publicar`.
8. Implementar sanitizacion para `content_html_safe`.
9. Definir politica de media:
   - rutas publicas
   - formatos permitidos
   - tamanos
   - nombres de archivo
   - alt text obligatorio
10. Hacer que endpoints publicos lean contenido publicado desde BD.
11. Mantener fallback default si no existe contenido publicado.

### Endpoints POST futuros

Actualmente existen como contratos bloqueados:

- `/cms/contenido_bloque_guardar_erp`
- `/cms/contenido_bloque_estatus_erp`
- `/cms/contenido_publicacion_guardar_erp`
- `/cms/contenido_publicacion_estatus_erp`

Cuando se active persistencia, estos endpoints deberan:

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

### Errores comunes

- Confundir plan DDL con DDL aplicado.
- Activar POST sin CSRF/auditoria.
- Guardar HTML sin sanitizar.
- Subir media sin politica de nombres/tamanos.
- Hacer que el frontend lea archivos internos del ERP.

## CMS > Frontend

Rutas:

- `/cms/frontend_plantillas`
- `/cms/frontend_componentes`

Documento tecnico de implementacion frontend: `docs/erp_cms_frontend_renderer_contrato.md`
Plan builder visual Wokiee/Artiani: `docs/erp_cms_visual_builder_wokiee_plan.md`

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
- `SafeHtmlBlock`

### Flujo recomendado

1. Abre `/cms/frontend_componentes`.

2. Revisa los KPIs:
   - layouts
   - componentes
   - plantillas
   - home activa

3. Revisa `Contrato renderer`.
   - arranque recomendado
   - endpoint de pagina
   - guardrails contra codigo libre

4. Revisa `Componentes permitidos`.

5. Para cada componente, valida:
   - bloques permitidos
   - variantes disponibles
   - slots compatibles

6. Si una plantilla necesita una seccion nueva, primero confirma si ya existe un componente compatible.

7. Si el componente no existe, debe implementarse en el frontend antes de permitirlo en CMS.

### Como interpretar componentes

- `HeroSlider`: renderiza banners principales o banners de categoria.
- `PromoStrip`: renderiza textos promocionales o avisos.
- `CategoryGrid`: renderiza cards de categorias.
- `ProductCarousel`: renderiza colecciones dinamicas de productos.
- `ImageCardGrid`: renderiza grids de cards con imagen.
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

### Errores comunes

- Agregar una variante que el frontend no conoce.
- Usar un componente en un slot incompatible.
- Usar un componente con un tipo de bloque no permitido.
- Pensar que registrar un componente en CMS lo crea automaticamente en el frontend.
- Permitir HTML libre para resolver un componente faltante.
