# CMS - Manual de uso operativo

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-10  
Estado: Manual vivo por secciones del modulo CMS

## Proposito

Este manual explica como usar el modulo CMS del ERP para administrar contenido ecommerce y, despues, plantillas frontend. Se actualiza por seccion conforme cada parte queda lista.

## Reglas generales

- El modulo CMS vive en el menu lateral `CMS`.
- La seccion `Contenido` administra bloques, slots, media, JSON y persistencia de contenido.
- La seccion `Frontend` prepara constructor visual, plantillas de vista, layouts, componentes, variantes y activaciones.
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

### Importante

- `Aplicar a preview` no guarda en BD.
- `Borrador local` usa el navegador, no la base de datos.
- `Restaurar defaults` vuelve al contenido default read-only.
- La vista visual esta separada en `/cms/frontend_constructor`.
- El constructor visual no genera archivos ni HTML productivo para el frontend.
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
- Endpoints POST siguen bloqueados.
- No hay bloques comerciales, publicaciones de contenido ni media guardada.
- API publica sigue en fallback default/read-only.

### Flujo recomendado

1. Abre `/cms/persistencia`.

2. Revisa el aviso `Persistencia real bloqueada`.

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

6. Confirma que los contratos POST aparecen como `Bloqueado`.

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

Pendiente para escrituras reales:

1. Ejecutar el preflight read-only:
   - `php storage/uat/uat_cms_persistencia_preflight.php`
2. Ejecutar validacion de semilla:
   - `php storage/uat/uat_cms_seed_readonly.php`
3. Activar endpoints POST con CSRF para guardar bloques y publicaciones de contenido.
4. Agregar auditoria explicita.
5. Validar permisos `cms.editar` y `cms.publicar`.
6. Implementar sanitizacion para `content_html_safe`.
7. Definir politica de media:
   - rutas publicas
   - formatos permitidos
   - tamanos
   - nombres de archivo
   - alt text obligatorio
8. Hacer que endpoints publicos lean contenido publicado desde BD.
9. Mantener fallback default si no existe contenido publicado.

### Endpoints POST futuros

Actualmente existen como contratos bloqueados:

- `/cms/contenido_bloque_guardar_erp`
- `/cms/contenido_bloque_estatus_erp`
- `/cms/contenido_publicacion_guardar_erp`
- `/cms/contenido_publicacion_estatus_erp`
- `/cms/frontend_plantilla_guardar_erp`
- `/cms/frontend_plantilla_estatus_erp`
- `/cms/frontend_seccion_guardar_erp`
- `/cms/frontend_seccion_estatus_erp`

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
- No activar tema o plantilla frontend sin comprobar que sus componentes existen en el frontend ecommerce.

### Errores comunes

- Confundir plan DDL con DDL aplicado.
- Activar POST sin CSRF/auditoria.
- Guardar HTML sin sanitizar.
- Subir media sin politica de nombres/tamanos.
- Hacer que el frontend lea archivos internos del ERP.

## CMS > Frontend

Rutas:

- `/cms/frontend_constructor`
- `/cms/frontend_plantillas`
- `/cms/frontend_componentes`
- `/cms/frontend_activaciones`

Documento tecnico de implementacion frontend: `docs/erp_cms_frontend_renderer_contrato.md`
Plan builder visual Wokiee/Artiani: `docs/erp_cms_visual_builder_wokiee_plan.md`

### Navegacion del submodulo

Las tres pantallas de CMS Frontend tienen una subnavegacion visible:

- `Plantillas`
- `Constructor`
- `Componentes`
- `Activaciones`

Usala para revisar el flujo completo sin volver al menu lateral:

1. `Constructor`: muestra la pagina armada con plantilla, secciones, componentes y slots.
2. `Plantillas`: define la composicion visual por pagina.
3. `Componentes`: valida que puede renderizar cada seccion.
4. `Activaciones`: confirma que plantilla aplica por pagina/canal/contexto.

## CMS > Frontend > Constructor visual

Ruta: `/cms/frontend_constructor`

### Para que sirve

Esta es la pantalla donde debe vivir la parte visual del CMS.

Muestra como se ensamblara una pagina del ecommerce usando:

- tema visual, por ejemplo `wokiee_artiani`;
- plantilla de vista, por ejemplo `wokiee_home_default`;
- secciones de la pagina;
- slots como `home.hero` o `home.destacados`;
- componentes como `HeroSlider`, `PromoStrip` o `ProductCarousel`;
- variantes visuales permitidas.

No es el frontend real y no genera HTML productivo. Es una previsualizacion administrativa para entender la estructura antes de que el frontend renderice con sus propios componentes.

### Flujo recomendado

1. Primero crea o revisa contenido en `/cms/contenido`.
   - banners
   - imagenes
   - textos
   - CTAs
   - colecciones

2. Abre `/cms/frontend_constructor`.

3. Elige la pagina o plantilla:
   - Home
   - Categoria
   - Catalogo

4. Revisa el canvas central.
   - Cada seccion representa un slot de la pagina.
   - Cada slot muestra el componente frontend que lo renderizara.
   - Cuando el indicador dice `Contenido conectado`, el preview ya cruzo plantilla frontend con contenido read-only de `/cms/contenido_admin_pagina_erp`.

5. Da clic en una seccion.
   - El inspector muestra plantilla, slot, componente, variante y bloques permitidos.
   - Tambien muestra cuantos bloques estan conectados a ese slot.

6. Revisa `Componentes disponibles`.
   - Confirma que el componente exista y tenga variantes compatibles.

7. Revisa `Como lo consumira frontend`.
   - El arranque recomendado sigue siendo `/ecommercePublico/configuracion_inicial`.
   - La pagina especifica usara `/ecommercePublico/contenido_pagina`.

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
