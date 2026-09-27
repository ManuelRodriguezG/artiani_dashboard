# Ecommerce: descripciones editoriales y tarjetas agrupadas

IA: Codex GPT-6 | Fecha: 2026-09-25

## Decision y alcance

Acuerdo recibido de frontend: descripcion publica vacia es intencional; no importar
notas del ERP. Agrupar antes de paginar conservando URLs e identidades de SKU.

Decision corregida por el dueno el 2026-09-26: agrupar por defecto las categorias
desde la API, no la busqueda. En catalogo con categoria/categoria_id/categoria_slug
se usa producto sin parametro adicional. Catalogo permite override explicito; sin
categoria sigue SKU. Busqueda y sugerencias siempre SKU, aun si reciben parametro
producto de las instrucciones anteriores. Selector por producto_id sigue SKU.
El contrato publica revision=categorias_default_v2; requiere nuevo despliegue Git.

La regla actual `grupo_producto.agrupable` corresponde a mas de una publicacion
visible del mismo id_producto_erp. Un producto con una sola publicacion sigue como
tarjeta individual. No se fusionan IDs distintos ni se cambia pertenencia comercial.

Orden: reglas de visibilidad -> filtros/busqueda -> elegir representante por orden
solicitado -> agrupar por producto -> ordenar tarjetas -> paginar. La tarjeta conserva
precio, permisos, imagen y URL de ese representante, sin precios minimos inventados.
No hay migraciones, escrituras masivas, cambios de slug ni de relaciones SEO.

## Descripciones

La ficha consume solo descripcion_publica editorial, tambien cuando esta vacia.
Los campos compatibles de salida contienen el mismo texto saneado. Preparar o
guardar un campo vacio no vuelve a copiar la descripcion de catalogo. El ERP puede
mantener la referencia interna para que un operador redacte/apruebe contenido.
HTML permitido: p, br, ul, ol, li, strong, b, em, i, h2, h3 y h4, sin atributos.
No se generan textos comerciales por inferencia, titulo ni nombre de proveedor.

Caso verificado por GET en sys: churro 100 g, id_publicacion=1, id_sku=1759,
descripcion_publica_fuente=catalogo_erp_fallback. El texto de 4 kg no estaba
aprobado en su campo publico. Al quitar el fallback la ficha queda sin ese texto;
su redaccion comercial sigue pendiente del operador.

## Continuidad

Estado actual: revision categorias_default_v2 e intencion_contextual_sql_v3 locales,
pendientes nuevo despliegue Git y QA HTTP/frontend. El contrato anterior se verifico
en sys con 30 checks, pero esa evidencia no certifica esta revision posterior.
No se hizo commit, push ni deploy desde esta sesion. Ver entrega frontend actualizada.
Las descripciones existentes que ya contengan notas internas en el propio campo
publico requieren revision editorial: sanear HTML no certifica contenido comercial.

## Contrato final consultable

- `GET /ecommercePublico/catalogo_manifest`: `depurar.parametros_soportados.agrupacion`
  y `depurar.presentacion_catalogo.version=presentacion_catalogo_v1`.
- `GET /ecommercePublico/frontend_handoff`: `depurar.contratos_ui.presentacion_catalogo`.
- `GET /ecommercePublico/catalogo?q=churro&agrupacion=producto&limite=12&vista=card`:
  `depurar.agrupacion=producto`, `items` son representantes, `paginacion.total=2`,
  `paginacion.total_skus=6`, `paginacion.unidad=grupos` con los datos del diagnostico.
- `GET /ecommercePublico/busqueda?q=Alimento%20para%20erizo&agrupacion=producto`:
  ocho SKU (ignora agrupacion=producto), motor_version=intencion_contextual_sql_v3.
- `GET /ecommercePublico/busqueda_sugerencias?q=churro&agrupacion=producto&limite=6`:
  mismo prefijo de SKU que busqueda por relevancia con mismos filtros; sin agrupacion.
- Categoria/alternas/rama y marca: mismo endpoint catalogo y filtros actuales,
  agrupa automaticamente cuando hay categoria, incluso combinada con marca.
  Solo marca o catalogo general sigue SKU salvo modo explicito. Enlaces conservan modo.
- Para las variantes de una tarjeta seguir `grupo_producto.variantes_url`, equivalente
  a `/ecommercePublico/catalogo?producto_id=50&agrupacion=sku&vista=card&limite=24`.
  Su paginacion cuenta SKUs y permite recuperar grupos mayores que el preview.

`grupo_producto` conserva `id_producto_erp`, `agrupable`, `tipo`,
`total_variantes_publicadas` y `seleccion_actual`. En tarjetas agrupadas y detalle:

- `variantes_preview`: en productos agrupables, maximo seis publicaciones visibles
  del grupo, incluye actual. En productos simples es vacio: usar item sin selector.
- `variantes_preview_completo`: indica si el preview cubre todas las variantes.
- Cada variante: id_publicacion, id_sku, sku, slug/slug_publico, url, canonical_url,
  nombre/label, presentacion, precio, moneda, mostrar_precio, mostrar_disponibilidad,
  permite_cotizacion, permite_whatsapp, disponibilidad, imagen, imagen_fuente,
  atributos_selector y actual.
- Los atributos de selector vienen solo de atributos activos con `es_variante=1`.
  Los terminos de busqueda tambien pueden coincidir con esos valores del SKU;
  el representante se elige despues de aplicar esa restriccion.
- El preview contiene variantes publicadas del producto, no solo las que coinciden
  con la consulta. La tarjeta representativa SI cumple los filtros originales.
- `imagen_fuente=sku|producto|''`: nunca se toma la imagen de otro SKU. Sin imagen
  valida se devuelve null y frontend puede mostrar su placeholder.
- Precio null se conserva; permisos de una variante no autorizan las demas.
- `frontend.deduplicar_catalogo_opcional=false`: frontend no vuelve a agrupar paginas.
- Las cards en modo sku conservan payload ligero sin anidar previews repetidos.

Detalle: `depurar.descripcion_version=descripcion_editorial_v1`,
`item.descripcion_publica_fuente=publicacion_ecommerce`, `descripcion_publica_formato`
es texto o html. El alias `descripcion` coincide con el campo publico saneado, incluso
si esta vacio. Preparacion y planes de guardado respetan ese vacio. El texto del ERP
solo queda como `producto_vivo_erp.descripcion_catalogo_referencia` en preparacion
interna, para revision del operador; no es fuente de la API publica.

Secciones y relacionados de ficha siguen siendo SKUs y lo declaran en sus respuestas.
Conteos de facetas/categorias/marcas siguen siendo SKUs: usar paginacion.total para
el total visible del listado agrupado. No hay URL ni canonical nuevo para el grupo.

## Pruebas y despliegue

Pruebas read-only:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_presentacion_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_busqueda_relevancia_readonly.php --model
```

Cobertura: churro 6 SKUs/2 tarjetas, ocho erizos independientes, filtros por categoria
principal/alterna/rama y marca, representantes coincidentes, paginas llenas de grupos,
limites 6/12/24, ordenamientos, preview/identidades/imagenes/permisos, vacio editorial,
plan de guardado sin ejecutar, HTML malicioso, SEO plano y busqueda por atributo.
Fixtures SQL usan SELECT de constantes, sin crear tablas ni insertar registros.
Motor real verificado: MariaDB 10.6.25, compatible con ROW_NUMBER().
Resultado final local: 209 comprobaciones de presentacion aprobadas y 48 regresiones
de busqueda aprobadas. PHP sin errores de sintaxis. Sin escrituras de productos,
contenido, inventario, precios, slugs o redirecciones.

Desplegar juntos `app/modelos/EcommerceCatalogoPublico.php` y el nuevo trait
`app/modelos/EcommerceCatalogoPresentacion.php`. No requiere cambios de esquema.
Verificar manifest en sys, invalidar cache de fichas/listados/sugerencias del frontend,
activar agrupacion explicita y repetir QA de navegacion, selector y carrito.
No usar este cambio para alterar sitemap, canonicals o redirecciones existentes.

Pendiente editorial del dueno: redactar la descripcion publica de cada presentacion
de churro y revisar contenido ya copiado anteriormente a campos publicos. No se
reescribieron ni aprobaron descripciones comerciales automaticamente.

## Verificacion de activacion y entrega

Seguimiento 2026-09-25: se agrego
`storage/uat/uat_ecommerce_presentacion_release_readonly.php`, solo CLI. Comprueba
el contrato con GET reales o el modelo local en READ ONLY; exit 1 ante HTTP/JSON
incorrecto o contrato antiguo. No carga configuracion ni conecta a BD en modo HTTP.

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_presentacion_release_readonly.php --model
C:\xampp\php\php.exe storage\uat\uat_ecommerce_presentacion_release_readonly.php --base=https://sys.artiani.com.mx/ecommercePublico
```

Resultado local: 30 comprobaciones aprobadas mediante 7 consultas al modelo.
Resultado HTTP en sys: falla correctamente en la primera comprobacion porque
catalogo_manifest aun no declara presentacion_catalogo_v1. Otra consulta GET confirma
que catalogo?q=churro&agrupacion=producto devuelve 6 tarjetas, sin agrupacion ni
presentacion_version. El despliegue NO esta certificado ni realizado desde este chat.
Los conteos son fixtures del diagnostico: si cambian publicaciones comerciales,
revisar la evidencia y el fixture; no desactivar el control de version o agrupacion.

Entrega autocontenida para frontend: `docs/erp_ecommerce_entrega_frontend_presentacion.md`.
Su contenido puede compartirse completo: frontend consulta los contratos en la API
y no necesita acceso a este archivo ni al proyecto ERP.

Secuencia pendiente:
1. El dueno confirmo el 2026-09-26 que despliega por Git. La rama local es master;
   no se conoce aun si el servidor hace pull manual o despliegue automatico. No
   inferir el commit exacto del servidor a partir de las respuestas HTTP.
2. Desplegar modelo y trait juntos, conservando respaldo de la version previa.
   Si la carga es manual, subir el trait antes del modelo para evitar dependencia
   ausente. No incluir credenciales, imagenes CMS ni cambios paralelos de otros chats.
3. Refrescar OPcache del servidor si corresponde y caches de contratos/respuestas;
   repetir la prueba HTTP. El rollback debe restaurar ambos archivos como conjunto.
4. Frontend activa el parametro solo tras verificar version, invalida sus caches
   afectadas y realiza QA visual de variantes, filtros, ficha, navegacion y carrito.
5. Completar revision editorial de descripciones. No requiere migraciones ni cambia
   URLs, slugs, relaciones de redireccion, precios o inventario.

Actualizacion de esta secuencia al 2026-09-26: la prueba HTTP ya paso sus 30
comprobaciones en sys; quedan pendientes los pasos de frontend y contenido editorial.
La evidencia historica de contrato ausente del 2026-09-25 ya no representa el estado
actual. No se ejecuto despliegue, escritura de BD ni operacion Git remota en esta sesion.

## Correccion posterior solicitada por el dueno

La instruccion previa de agrupar busqueda/sugerencias queda revocada. La API resuelve
las categorias agrupadas sin exigir logica adicional al frontend. Si frontend manda
agrupacion=sku de forma explicita en categorias, debe omitirlo para usar el default.
Los totales/paginas son grupos, antes de LIMIT; facetas y bloques legacy mantienen SKU.
Busqueda mantiene SKU incluso con agrupacion=producto enviado por cliente antiguo.

El cero de filtro para pecera de 40 litros se debia a AND literal sobre la capacidad
y al contexto acuario ausente en nombres. v3 conserva filtro + contexto (incluidas
categorias principales/alternas) y trata litros como preferencia de relevancia, sin
certificar compatibilidad. El mensaje publico invita a revisar capacidad. No se
eliminan especies como erizo ni se rellenan consultas sin coincidencias pertinentes.

Regresion local READ ONLY: 218 comprobaciones de presentacion, 71 de busqueda y
39 de release aprobadas (328 en total). Filtro 40 litros devuelve 45 opciones,
erizo 8, areneros 59. La prueba de release exige revision de categorias, modo SKU de busqueda, ID/slug de categoria
y continuidad entre paginas/limites. No incluye escrituras ni modificaciones SEO.
Pendiente: desplegar ambos archivos de modelo por Git y repetir aceptacion HTTP;
despues invalidar caches del frontend y revisar visualmente. No reutilizar el resultado
HTTP de v2 para declarar esta revision publicada.
