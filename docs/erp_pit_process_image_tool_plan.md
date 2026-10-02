# PIT - Process Image Tool, plan transversal

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-30  
Estado: MVP independiente iniciado, sin cambios de esquema aplicados

## Proposito

PIT debe ser una herramienta interna transversal para cargar, revisar, procesar y reutilizar imagenes dentro del sistema.

No debe nacer como una pestaña mas de imagenes de producto. Su valor esta en crear una biblioteca visual general que permita preparar assets una sola vez y asignarlos despues a Catalogo, CMS, ecommerce, marcas, categorias, materiales comerciales u otros modulos futuros.

## Contexto observado

- Catalogo ERP ya usa `erp_catalogo_imagenes` para imagenes de producto/SKU.
- Catalogo tambien tiene imagenes de marca y categoria en tablas propias.
- La pantalla de productos permite subir JPG, PNG, WEBP o GIF hasta 5 MB y guardar una `url_imagen` asociada al producto o SKU.
- La recuperacion ecommerce conserva imagenes heredadas como `fuente='ecommerce'`.
- POS, Compras, catalogos comerciales y ecommerce ya consumen `url_imagen` desde Catalogo ERP.
- CMS Media ya tiene una biblioteca avanzada basada en `erp_ecommerce_media_archivos`, con hash SHA-256, MIME, extension, bytes, ancho, alto, alt text, uso sugerido, tipo sugerido, reemplazo conservador y validacion de acceso.
- `public/assets/js/custom/apps/erp/cms/media_tools.js` ya permite optimizar o convertir a WebP desde navegador por decision explicita del usuario.

Conclusion: PIT debe reutilizar o generalizar la base de Media CMS, no duplicarla con otra biblioteca aislada.

## Decision corregida por el dueno del proyecto

El PIT no debe comenzar dentro de Catalogo. Debe nacer como modulo independiente y despues conectarse con los modulos que utilicen imagenes.

Orden correcto:

1. Crear PIT como herramienta propia de carga, revision y procesamiento de imagenes.
2. Reutilizar la biblioteca Media existente solo como infraestructura interna de almacenamiento y validacion.
3. Mantener Catalogo, CMS y otros consumidores intactos mientras PIT madura.
4. Agregar adaptadores de asignacion despues: producto/SKU, categorias, marcas, CMS, galerias comerciales, etc.

Decision aplicada en el MVP:

- Ruta propia: `/pit`.
- Controlador propio: `app/controladores/pit.php`.
- Vista propia: `app/vistas/paginas/apps/erp/pit/index.php`.
- JS propio: `public/assets/js/custom/apps/erp/pit/index.js`.
- Endpoints propios: `/pit/media_preflight_erp`, `/pit/media_listar_erp`, `/pit/media_subir_erp`, `/pit/media_usos_erp`.
- Editor masivo inicial: seleccion multiple, redimension maxima, calidad y formato de salida `original`, `JPG`, `PNG` o `WebP`.
- Separacion de vista: el Editor PIT y la Biblioteca PIT deben vivir en espacios visuales separados dentro del modulo para no mezclar trabajo de procesamiento con consulta de assets guardados.
- Sin DDL.
- Sin tocar `erp_catalogo_imagenes`.
- Sin cambiar el flujo actual de imagenes de producto.

## Decision de editor masivo

PIT debe tener un editor por lotes como capacidad central.

Necesidades confirmadas:

- cargar varias imagenes al mismo tiempo;
- preparar imagenes para diferentes destinos sin entrar todavia a Catalogo, CMS u otro modulo consumidor;
- elegir extension/formato de salida segun el destino: conservar original, JPG, PNG o WebP;
- redimensionar por ancho/alto maximo;
- ajustar calidad para JPG/WebP;
- mostrar una estimacion aproximada del peso final antes de subir;
- permitir nombre SEO individual por imagen del lote;
- permitir orientacion fija, por ejemplo cuadrada 1:1, 4:3, 16:9, 3:4 o 9:16;
- permitir dos modos de encuadre: encajar completa o recortar al formato;
- usar la vista previa como editor de foco cuando el modo sea recortar;
- el foco de recorte debe ser arrastrable, no solo por clic, para permitir ajuste fino;
- permitir zoom del encuadre para mover una parte especifica de la imagen dentro del marco;
- permitir fondo transparente, blanco o color personalizado cuando la salida deje espacio libre;
- subir a la biblioteca solo el resultado final validado;
- conservar el archivo original fuera del proceso destructivo;
- impedir que formatos no seguros para canvas, como GIF/AVIF/ICO, se aplanen accidentalmente.

Regla operativa:

- JPG, PNG y WebP pueden redimensionarse o convertirse desde el navegador.
- GIF, AVIF e ICO se conservan como archivo original en esta fase; si se requiere convertirlos, se debe reemplazar con una version preparada o agregar un procesador servidor autorizado.
- Cada salida final debe respetar el limite vigente de Media: 2 MB por archivo.

Regla de interfaz:

- La pantalla principal debe abrir en modo Editor PIT.
- La biblioteca debe consultarse en una vista separada dentro del mismo modulo.
- No mezclar grid de biblioteca, detalle de asset guardado y controles de edicion masiva en el mismo espacio visual.
- La seccion `Procesar y cargar` debe ocupar el ancho completo del editor; la cola de trabajo vive debajo para revisar cada archivo.
- Uso sugerido y tipo sugerido son opcionales/avanzados, no campos que bloqueen la carga.
- La vista previa del editor debe servir para revisar encuadre; en modo recorte el usuario puede marcar el punto importante de la imagen antes de procesar.
- El marco de vista previa debe mostrar la orientacion elegida y guias visuales; el usuario debe poder deslizar el marcador de foco dentro del marco.
- El arrastre debe comportarse como movimiento directo de la imagen: si el usuario arrastra hacia arriba, la imagen debe subir; si arrastra a la izquierda, la imagen debe moverse a la izquierda. Los limites se calculan con el zoom y el espacio real sobrante.
- Antes de implementar eliminacion de fondo avanzada, el editor base debe cubrir arrastre, zoom y fondo configurable.

## Decision tecnica recomendada

Crear PIT como capa transversal sobre una biblioteca media unica.

La biblioteca conserva el activo visual y sus derivados. Las tablas de Catalogo, CMS u otros modulos solo deben guardar relaciones de uso, rol de imagen, orden y contexto operativo.

Decision de integracion futura:

- No romper ni reemplazar el flujo actual de imagenes de Catalogo.
- Usar PIT como procesador y biblioteca de preparacion.
- Cuando una imagen ya este lista, asignarla al producto/SKU usando el contrato que Catalogo ya entiende.
- En Catalogo, conservar los datos que ya necesita: producto, SKU opcional, tipo de imagen, orden, estado, texto alternativo y ruta publica.
- La integracion debe ser gradual: primero seleccion/asignacion desde PIT; despues trazabilidad fuerte con IDs de media si se autoriza esquema.

Separacion recomendada:

- Archivo/asset: identidad fisica, hash, MIME, peso, dimensiones, ruta publica, alt base, metadata tecnica.
- Derivado: version procesada del asset, por ejemplo WebP, thumbnail, cuadrada, banner, fondo transparente o recorte para ecommerce.
- Asignacion: relacion entre asset/derivado y una entidad del ERP, por ejemplo producto, SKU, categoria, marca, bloque CMS o galeria comercial.
- Uso publicado: donde se esta usando realmente para impedir borrados o reemplazos peligrosos.

## Nombre y alcance

Nombre operativo: PIT - Process Image Tool.

Rutas sugeridas:

- `/pit` o `/media/pit` para tablero general.
- Si se prefiere agrupar en CMS al inicio: `/cms/media` puede evolucionar visualmente, pero PIT no debe quedar conceptualmente limitado a CMS.

Permisos sugeridos:

- `pit.ver`: consultar biblioteca, filtros, usos y previews.
- `pit.subir`: cargar imagenes nuevas.
- `pit.procesar`: generar derivados, convertir, recortar, optimizar.
- `pit.asignar`: relacionar imagenes con productos, categorias, marcas, CMS u otros modulos.
- `pit.eliminar`: baja logica/fisica controlada sin referencias activas.

Para MVP se puede mapear temporalmente a `cms.editar` y `catalogo.editar`, pero el destino sano son permisos propios.

## MVP recomendado

### 1. Biblioteca unificada

Objetivo: ver y cargar imagenes reutilizables.

Debe incluir:

- grid de imagenes con busqueda;
- filtros por uso: producto, categoria, marca, home, blog, global, comercial, general;
- filtros por formato, peso, dimensiones, estado y origen;
- detalle con ruta publica, hash, alt, dimensiones, peso, fecha, usuario y usos detectados;
- subida con validacion de MIME, extension, peso, hash, dimensiones y alt text;
- deteccion de duplicados por hash.

Base tecnica recomendada:

- Reutilizar `erp_ecommerce_media_archivos` como punto de partida o migrarlo a nombre neutral futuro.
- Evitar mover imagenes de Catalogo existentes al inicio; primero referenciarlas y luego planear migracion gradual.

### 2. Procesamiento controlado

Objetivo: preparar versiones utiles sin perder el original.

Acciones MVP:

- optimizar conservando formato;
- convertir a WebP por decision explicita;
- generar thumbnail;
- generar version cuadrada 1:1 para producto/card;
- generar version 4:3 o 16:9 para banners/cards;
- renombrar SEO conservando alias o referencia anterior cuando aplique.

Reglas:

- nunca modificar el original sin crear una nueva version o reemplazo auditado;
- no procesar GIF/AVIF/ICO animados como imagen estatica;
- conservar transparencia;
- bloquear resultados mas pesados salvo confirmacion clara;
- guardar metadata del proceso: accion, parametros, archivo origen, archivo destino, usuario y fecha.

### 3. Asignacion a Catalogo

Objetivo: desde PIT poder seleccionar imagenes procesadas y asignarlas a productos/SKUs.

Debe permitir:

- buscar producto/SKU ERP;
- elegir alcance: producto maestro o SKU especifico;
- elegir tipo: portada, galeria, detalle, empaque, referencia;
- elegir derivado recomendado segun uso;
- enviar la relacion a `erp_catalogo_imagenes` o a una tabla puente futura;
- mantener la regla actual: imagen de producto y de SKU tienen alcances distintos.

Recomendacion para no romper consumo actual:

- En fase 1, al asignar desde PIT insertar en `erp_catalogo_imagenes.url_imagen` la ruta publica del asset/derivado, con `fuente='pit'` o `fuente='media'`.
- En fase 2, agregar `id_media_archivo`/`id_media_derivado` a relaciones para trazabilidad fuerte.

Flujo operativo propuesto:

1. El usuario sube una o varias imagenes a PIT.
2. PIT valida formato, peso, dimensiones, duplicados, hash y alt base.
3. El usuario procesa si hace falta: optimizar, convertir a WebP, generar cuadrada 1:1, generar banner o thumbnail.
4. La imagen queda como `lista para asignar`.
5. El usuario elige `Asignar a Catalogo`.
6. PIT abre un panel de asignacion con buscador de producto/SKU ERP.
7. El usuario selecciona:
   - producto maestro;
   - SKU especifico opcional;
   - tipo de imagen: portada, galeria, detalle, empaque o referencia;
   - orden;
   - texto alternativo especifico si difiere del alt base;
   - estado: activo o inactivo.
8. PIT confirma mostrando preview, producto, SKU, tipo y ruta publica.
9. Backend crea o actualiza el registro en `erp_catalogo_imagenes`.
10. Catalogo, POS, Compras, catalogos comerciales y ecommerce siguen leyendo `url_imagen` como hoy.

Contrato minimo de asignacion hacia `erp_catalogo_imagenes`:

- `id_producto_erp`: obligatorio.
- `id_sku`: opcional; vacio significa producto maestro.
- `tipo_imagen`: `portada`, `galeria`, `detalle`, `empaque`, `referencia`.
- `url_imagen`: ruta publica del archivo listo en PIT/Media.
- `texto_alternativo`: alt especifico para Catalogo.
- `orden`: numero entero.
- `fuente`: `pit` o `media`.
- `id_externo`: opcional en fase 1; puede guardar codigo/id media como texto mientras no exista columna dedicada.
- `estatus`: `activo` o `inactivo`.

Reglas para portada:

- Si se asigna una nueva `portada` activa, Catalogo debe decidir si degrada la portada anterior a `galeria` o pide confirmacion.
- Recomendacion: pedir confirmacion visible: `Usar como portada y mover portada actual a galeria`.
- No borrar la imagen anterior automaticamente.

Reglas para SKU:

- Si `id_sku` viene vacio, la imagen es general del producto.
- Si `id_sku` viene informado, la imagen solo representa esa variante/presentacion/SKU.
- PIT debe mostrar la diferencia con texto operativo corto antes de confirmar.

Reglas para no duplicar:

- Antes de insertar, buscar si ya existe una imagen activa/inactiva del mismo producto/SKU con la misma `url_imagen`.
- Si existe, ofrecer actualizar tipo/orden/alt/estado en vez de crear duplicado.
- El hash vive en Media/PIT; Catalogo solo necesita la ruta para seguir funcionando.

### 4. Galerias reutilizables

Objetivo: una misma biblioteca puede alimentar varias salidas.

Usos iniciales:

- Galeria de producto.
- Galeria de SKU/variante.
- Portadas de categoria.
- Logos/portadas de marca.
- Banners CMS.
- Materiales comerciales.
- Futuro: evidencias, garantias, TMS o postventa, si se separan de imagenes publicas.

Regla: una galeria no es dueña del archivo; solo ordena y contextualiza assets.

## Modelo de datos propuesto

Fase conservadora:

- Conservar `erp_ecommerce_media_archivos` para archivos compartidos.
- Conservar `erp_catalogo_imagenes`, `erp_catalogo_marca_imagenes` y `erp_catalogo_categoria_imagenes` como relaciones operativas actuales.
- Agregar solo cuando se autorice DDL.

Fase robusta futura:

- `erp_media_archivos`: version neutral de `erp_ecommerce_media_archivos`.
- `erp_media_derivados`: versiones procesadas vinculadas al archivo original.
- `erp_media_galerias`: agrupadores reutilizables.
- `erp_media_galeria_items`: orden y rol dentro de una galeria.
- `erp_media_asignaciones`: relacion generica con entidad tipo/id.
- `erp_media_procesos`: bitacora de transformaciones y parametros.

Campos clave para `erp_media_derivados`:

- `id_media_derivado`
- `id_media_archivo_origen`
- `codigo`
- `ruta_publica`
- `mime`
- `extension`
- `bytes`
- `ancho`
- `alto`
- `hash_sha256`
- `tipo_derivado`: original, webp, thumb, producto_1_1, banner_16_9, categoria_hero, etc.
- `parametros_json`
- `estatus`
- `creado_por`
- `fecha_registro`

Campos clave para `erp_media_asignaciones`:

- `id_media_asignacion`
- `id_media_archivo`
- `id_media_derivado`
- `entidad_tipo`: producto_erp, sku_erp, categoria_erp, marca_erp, cms_slot, catalogo_comercial, etc.
- `entidad_id`
- `rol`: portada, galeria, detalle, empaque, hero, banner, logo, thumbnail, og_image
- `orden`
- `alt_override`
- `estatus`
- `creado_por`
- `fecha_registro`

## UX operativa

PIT debe sentirse como una mesa de trabajo visual:

- Zona superior: busqueda, filtros, subir, procesar lote.
- Cuerpo principal: biblioteca en grid denso, con peso/formato/dimensiones visibles.
- Panel lateral o inferior: detalle, usos, versiones y acciones.
- Modo lote: seleccionar varias imagenes, aplicar conversion/thumbnail/asignacion.
- Modo asignar: buscar producto/SKU/categoria/marca y confirmar alcance.
- Avisos claros: duplicado, archivo pesado, falta alt, imagen sin uso, imagen en uso.

No debe esconder acciones peligrosas detras de texto ambiguo:

- `Reemplazar conservando URL`
- `Crear version WebP`
- `Asignar como portada`
- `Desactivar relacion`
- `Eliminar archivo sin usos`

## Integracion con Catalogo ERP

Contratos a respetar:

- `id_sku_erp` sigue siendo la identidad operativa de SKU.
- Las imagenes de producto maestro no deben confundirse con imagenes especificas de SKU.
- Duplicar producto no debe copiar imagenes automaticamente.
- Fusion de productos debe mover o revisar imagenes con auditoria, no perderlas.
- Productos sin imagen siguen siendo una alerta de calidad/publicacion.

Cambios recomendados por etapa:

1. Agregar boton en Catalogo > Producto > Imagenes: `Elegir desde PIT`.
2. Permitir que PIT cree una relacion `erp_catalogo_imagenes` sin subir archivo nuevo.
3. Mostrar en Catalogo si la imagen viene de PIT/Media y si tiene derivados.
4. En listado de productos, seguir consumiendo `url_imagen` como hoy para no romper POS/Compras/ecommerce.

Implementacion conservadora recomendada:

- No retirar el formulario actual de carga directa en Catalogo.
- Agregar `Elegir desde PIT` como opcion adicional.
- Mantener `Guardar imagen` actual para casos rapidos o contingencia.
- En el modal de producto, PIT debe devolver solo una ruta publica y metadata sugerida; Catalogo guarda con su endpoint/contrato actual.
- No mover fisicamente imagenes legacy ni ecommerce recuperadas en esta etapa.
- No cambiar consultas existentes que resuelven portada desde `erp_catalogo_imagenes`.

Pantallas sugeridas:

- En PIT: boton `Asignar a producto`.
- En Catalogo > Imagenes: boton `Elegir desde PIT`.
- En el detalle de imagen de Catalogo: badge `PIT/Media` cuando `fuente` sea `pit` o `media`.

Endpoints sugeridos para fase 1:

- `GET /pit/media_listar`: lista imagenes procesadas/listas.
- `POST /pit/media_subir`: sube a biblioteca.
- `POST /pit/media_procesar`: genera derivado.
- `GET /pit/catalogo_buscar_productos`: busca producto/SKU para asignar, o reutiliza buscador existente si encaja.
- `POST /pit/catalogo_asignar_imagen`: valida permiso y crea/actualiza `erp_catalogo_imagenes`.

Alternativa aun mas conservadora:

- No crear endpoint de asignacion en PIT al inicio.
- Desde Catalogo, abrir picker PIT, recibir `url_imagen`, `alt` y `tipo_sugerido`, llenar el formulario actual `catalogo_form_imagen` y guardar con `/catalogoerp/guardar_imagen`.
- Ventaja: reutiliza permisos, auditoria y modelo ya existentes.
- Desventaja: PIT depende mas de la UI de Catalogo para completar la asignacion.

Recomendacion: iniciar con la alternativa conservadora desde Catalogo. Cuando se valide, agregar asignacion directa desde PIT para trabajo masivo.

## Integracion con CMS y ecommerce

CMS Media ya cubre parte del MVP. PIT debe absorber esa experiencia:

- mantener alt obligatorio para imagenes publicas;
- conservar nombre SEO;
- validar hash y acceso publico;
- permitir reemplazos conservadores;
- consultar usos antes de eliminar;
- usar picker desde frontend, blog, home, categorias y productos.

Si PIT se vuelve modulo independiente, CMS debe consumirlo como picker, no duplicar biblioteca.

## Riesgos y limites

- No ejecutar DDL ni migracion de media sin respaldo externo y autorizacion explicita.
- No mover archivos existentes de Catalogo/ecommerce en la primera fase.
- No eliminar imagenes fisicas con relaciones activas.
- No depender de `url_imagen` como unica trazabilidad a largo plazo.
- No mezclar imagenes publicas de producto/CMS con evidencias privadas de garantias, caja, compras o TMS sin clasificacion y permisos.
- No procesar masivamente imagenes productivas sin dry-run, conteos y muestra de resultados.

## Orden de implementacion recomendado

1. Auditar estado de Media CMS y confirmar si `erp_ecommerce_media_archivos` ya esta completo en productivo.
2. Revisar si el procesamiento actual de `media_tools.js` cubre las necesidades inmediatas de producto.
3. Crear vista/picker PIT reutilizando biblioteca Media existente.
4. Agregar derivados basicos para Catalogo: cuadrado 1:1, WebP optimizado, thumbnail y version detalle.
5. Agregar `Elegir desde PIT` en Catalogo Producto > Imagenes, sin quitar la carga actual.
6. El picker devuelve `url_imagen`, `alt`, `mime`, dimensiones y tipo sugerido.
7. Catalogo llena su formulario actual y guarda por `/catalogoerp/guardar_imagen`.
8. Registrar `fuente='pit'` o `fuente='media'` en `erp_catalogo_imagenes`.
9. Crear auditoria read-only de productos sin imagen y candidatas PIT.
10. Preparar asignacion masiva asistida para productos sin imagen, nunca automatica ciega.
11. Extender picker a categorias, marcas y CMS.
12. Planear migracion neutral de `erp_ecommerce_media_archivos` a `erp_media_archivos` solo si el uso transversal ya lo justifica.

## Preguntas de negocio antes de implementar

- PIT debe vivir visualmente bajo `CMS`, `Catalogo`, `Sistema` o como modulo propio?
- Las imagenes de producto deben admitir fondo transparente como criterio comercial?
- Cuales son los formatos obligatorios para producto: portada cuadrada, galeria, empaque, detalle?
- Cual sera el limite final por archivo original y por derivado?
- Se quiere conservar todos los originales aunque pesen mucho, o solo derivados optimizados?
- Quien puede asignar imagenes a productos: Catalogo, Ecommerce, Direccion?
- Las imagenes para evidencias operativas privadas deben quedar fuera de PIT publico?

## Handoff / continuidad

- Contexto actual: Catalogo necesita completar imagenes de productos y existe una base real de Media CMS reutilizable.
- Decision: PIT debe ser transversal y apoyarse en biblioteca media unica, pero la integracion inicial con Catalogo debe conservar el flujo actual y solo agregar seleccion/asignacion desde PIT.
- No se hizo: codigo, DDL, migraciones, movimientos de archivos ni escrituras en BD.
- Siguiente paso recomendado: auditar con lectura el estado real de `erp_ecommerce_media_archivos`, revisar si la pantalla `/cms/media` esta activa en el entorno y disenar el picker `Elegir desde PIT` para Catalogo > Producto > Imagenes.
- Nivel IA recomendado: Nivel D para esquema/permisos y Nivel C para UI/endpoints puntuales.
