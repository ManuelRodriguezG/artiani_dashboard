# Entrega a frontend: descripciones y agrupacion

IA: Codex GPT-5 | Fecha: 2026-09-25

Este documento es una entrega autocontenida. Frontend no necesita acceso al ERP,
sus archivos ni sus tablas para implementar o comprobar este contrato.

## Estado y activacion

Actualizacion 2026-09-26: la API en https://sys.artiani.com.mx/ecommercePublico
ya paso 30 comprobaciones HTTP de activacion (7 GET). Entrega el contrato nuevo,
churro devuelve 2 tarjetas/6 SKUs y erizo conserva 8 grupos distintos. La evidencia
del 2026-09-25 correspondia a la version anterior y queda superada por esta prueba.
Frontend puede integrar el modo explicito; aun requiere su QA visual y de navegacion.

Base API: https://sys.artiani.com.mx/ecommercePublico

1. Consultar GET /catalogo_manifest y comprobar
   depurar.presentacion_catalogo.version = presentacion_catalogo_v1.
2. El contrato completo tambien estara en GET /frontend_handoff,
   depurar.contratos_ui.presentacion_catalogo.
3. Comprobar en GET /busqueda?q=Alimento%20para%20erizo&limite=12&vista=card
   que depurar.motor_version = terminos_and_sql_v2.
4. Si falta la version, reportar API pendiente de despliegue/cache. No deduplicar
   localmente ni descargar todas las paginas como sustituto. Mantener el modo SKU
   compatible hasta que backend y frontend puedan activarse juntos.

## Listados, busqueda y paginacion

Agregar agrupacion=producto a catalogo, busqueda y busqueda_sugerencias:

```text
GET /catalogo?q=churro&agrupacion=producto&pagina=1&limite=12&vista=card
GET /catalogo?categoria_slug={slug_entregado_por_API}&incluir_hijos=1&agrupacion=producto&pagina=1&limite=24&vista=card
GET /catalogo?marca={id_entregado_por_API}&agrupacion=producto&pagina=1&limite=24&vista=card
GET /busqueda?q=Alimento%20para%20erizo&agrupacion=producto&pagina=1&limite=12&vista=card
GET /busqueda_sugerencias?q=Alimento%20para%20erizo&agrupacion=producto&limite=6
```

Usar /busqueda para resultados inteligentes. /catalogo?q mantiene busqueda literal;
no intercambiar endpoints entre pagina inicial y paginas siguientes. Enviar siempre
la frase original, sin sustituirla por interpretaciones como "alimento".

La API filtra, elige variante coincidente, agrupa, ordena y luego pagina.
Pintar depurar.items directamente. No fusionar por nombre ni eliminar repetidos
despues de paginar. Sin agrupacion explicita el contrato sigue siendo SKU.

Usar depurar.paginacion.total y total_paginas para el listado. Con modo producto,
unidad=grupos y total_skus es un contador separado. Los conteos de facetas,
categorias y marcas siguen siendo SKUs, no sirven para calcular paginas agrupadas.
Seguir los enlaces de paginacion de API conservando origen API, filtros, orden y modo;
no utilizarlos como canonical ni como URL visible del frontend.

Sugerencias devuelve productos en depurar.grupos.productos; valor es el slug,
no el ID. total_productos cuenta grupos cuando agrupacion=producto. Comparar su
prefijo con /busqueda por relevancia y mismos filtros, no con otros ordenamientos.

## Variantes e identidad

La tarjeta sigue representando un SKU real. Conservar id_publicacion, id_sku,
nombre, slug/url/canonical, imagen, precio y permisos de esa seleccion.
grupo_producto.id_producto_erp es identidad del grupo, nunca identidad de carrito.

Si grupo_producto.agrupable=true, mostrar selector con variantes_preview (hasta 6,
incluida seleccion actual). variantes_preview_completo indica si estan todas;
si es false, seguir variantes_url paginada cuando el usuario abra el selector.
El preview incluye variantes publicadas del producto, aunque no coincidan todas
con el filtro original. La tarjeta representativa SI coincide.
Producto simple: agrupable=false, preview vacio; pintar item, sin selector.

Cada variante tiene sus propios ID, nombre/label, presentacion, URL, precio, moneda,
permisos, imagen, imagen_fuente y atributos_selector (codigo/nombre/valor/unidad).
No prestar precio, permisos o imagen de otra variante. imagen_fuente=producto
es fallback explicito del producto; sin imagen usar placeholder del frontend.
No presentar precio null como cero ni calcular un precio "desde" localmente.
Cotizacion y WhatsApp dependen de los permisos de la seleccion, no del grupo.

Al cambiar variante, navegar a su URL y consultar su ficha para cargar descripcion,
galeria y SEO propios. Actualizar carrito con sus ID reales; validar con preflight/
dryrun existentes. No habilitar checkout ni pagos. Secciones, relacionados y las
alternativas del detalle siguen declarados como SKU, no como listados agrupados.

## Descripcion solo en ficha

GET /producto/{slug} entrega depurar.descripcion_version=descripcion_editorial_v1
y depurar.item.descripcion_publica_fuente=publicacion_ecommerce.
Usar descripcion_publica; vacio es intencional: ocultar el bloque, no rescatar notas
ERP, otro campo ni descripcion de otra presentacion. El alias descripcion ya coincide
con el texto publico saneado. Las tarjetas vista=card no incluyen descripciones.

descripcion_publica_formato=texto: escapar HTML y conservar saltos de linea.
Formato html: contenido editorial saneado, sin atributos; permitir p, br, ul, ol,
li, strong, b, em, i, h2, h3, h4 y mantener la proteccion HTML del frontend.
No inventar texto comercial. Usar SEO plano de la API para metadatos.
Churro 100 g no hereda la descripcion ERP de 4 kg: queda vacio hasta aprobacion editorial.

## URLs, cache y aceptacion

No cambian slugs, URLs por SKU, canonicals, relaciones de redireccion ni sitemap.
No crear una URL de grupo desde el nombre. Respetar las URLs entregadas por API.
Incluir agrupacion, vista, filtros, pagina, limite y orden en las claves de cache.
Al activar la version invalidar fichas/listados/sugerencias/contratos antiguos;
no desactivar permanentemente la cache ni borrar relaciones SEO.

Con los datos publicados del diagnostico del 2026-09-25 comprobar:
- Churro: seis SKUs, dos tarjetas (grupos 50 y 1016); grupo 50 ofrece cinco variantes.
- Limite 1 paginas 1/2 da los mismos representantes/orden que limite 12, sin repetidos.
- Alimento para erizo: ocho grupos distintos; sugerencias 6 son prefijo de resultados.
- Buscar presentacion 100 g elige esa variante, no la de 4 kg.
- Texto vacio, imagen ausente y precio null se muestran sin contenido/identidad prestados.
- Categoria principal/alterna/rama, marca, orden, navegacion atras/adelante y seleccion
  de variante conservan filtros, URL, precio/permisos e identidad de carrito.
- API vacia o fallida muestra su estado, nunca tarjetas ficticias ni carga masiva.

Pendientes: integrar y probar frontend, invalidar sus respuestas antiguas y completar
textos comerciales aprobados. La aceptacion HTTP del backend ya paso el 2026-09-26;
no sustituye la verificacion visual en frontend ni certifica todo el sitio para lanzamiento.
