# Ecommerce Publico - Busqueda Inteligente Frontend

Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09

## Correccion previa al lanzamiento / continuidad

IA: Codex GPT-5 | Fecha: 2026-09-26

Estado actual: revision contextual v3 implementada localmente, pendiente despliegue
Git y nueva validacion HTTP. La verificacion anterior en sys certifico v2, no v3.
No se ejecutaron migraciones ni escrituras de productos.

Causa corregida: se elegia `alimento` antes de la frase completa y se ordenaba una
muestra ya limitada de la primera pagina. Esto perdia `erizo`, cambiaba el prefijo
de sugerencias y repetia productos entre paginas.

Contrato vigente (mantiene `fase` v1 por compatibilidad):

- `/busqueda`, `/busqueda_sugerencias` y `/busqueda_manifest` entregan
  `depurar.motor_version=intencion_contextual_sql_v3` para verificar el despliegue.
- AND entre conceptos esenciales de producto/contexto; OR entre sinonimos/plurales.
  Conectores se descartan. `terminos_consulta` conserva conceptos originales;
  `terminos_requeridos` excluye la capacidad, que se declara en `preferencias`.
  No se descarta erizo para ampliar a todos los alimentos ni se usa popularidad como
  sustituto de pertinencia. Prioridad comercial de terminos sigue configurada en CMS.
- La ultima palabra admite prefijo desde dos letras para mantener autocompletado
  (`alimento eriz`). Ambos endpoints aplican el mismo criterio; no amplia numeros.
- Se comparan titulo publico, nombre SKU/producto, SKU, marca y presentacion.
  La extension de agrupacion tambien permite coincidencias con valores de atributos
  de selector activos (`es_variante=1`) del SKU, nunca con atributos administrativos.
  Para pecera/acuario tambien se consulta categoria principal/alterna como contexto:
  un filtro de categoria Acuario no necesita repetir pecera en el titulo. Esto no
  vuelve opcional la especie solicitada ni convierte alimento de perro en erizo.
- La base de datos filtra, calcula relevancia y ordena antes de `LIMIT/OFFSET`.
  El desempate final es `id_publicacion`. Los boosts de nombre/categoria/marca/SKU,
  categoria probable, imagen y precio se aplican al ranking global. Orden explicito por nombre, precio
  o recientes se respeta en todas las paginas.
- `items`, `total` y `paginacion.total` son coincidencias principales. No se amplia
  silenciosamente a una sola palabra si no hay coincidencias completas.
- `recomendaciones_ampliadas=[]` queda separado y actualmente vacio. Usar
  `sugerencias` y `categorias_relacionadas` para ofrecer otra consulta al cliente.
- `/busqueda_sugerencias` devuelve en `grupos.productos` un prefijo del mismo orden
  de `/busqueda?orden=relevancia`, con los mismos filtros. `total_productos` cuenta
  todas las coincidencias; `resumen.productos` cuenta solo las sugerencias devueltas.
- `paginacion.primera/anterior/siguiente/ultima` apunta a `/ecommercePublico/busqueda`
  y conserva la frase original, filtros, limite y orden. Frontend debe seguir esos
  enlaces, sin descargar todo, recalcular totales, reordenar ni deduplicar paginas.
- Capacidad en litros es preferencia de ranking antes de paginar, NO restriccion.
  Se prioriza coincidencia textual de 40 litros/40l; 40 cm, 400 litros y caudal
  40 l/h no reciben ese boost. Se permiten otras opciones del producto/contexto,
  con mensaje_cliente y coincidencia_busqueda.compatibilidad_capacidad_confirmada=false.
  Nunca certificar capacidad tecnica por coincidencia textual ni prometer que todas
  las opciones son compatibles. No hay descarga masiva/filtro posterior en frontend.
- Busqueda y sugerencias siempre devuelven SKU; ignoran agrupacion=producto legado.
  Categoria en /catalogo agrupa por defecto y permite override explicito; filtros
  de categoria dentro de /busqueda NO cambian su modo SKU.
- Una consulta vacia, solo conectores o sin coincidencias devuelve cero productos.
  Los fallos de consulta conservan `error=true`; no son un cero exitoso.
- `/catalogo?q=...` conserva busqueda literal legacy y puede dar un conjunto distinto.
  No usarlo para reemplazar un cero valido de `/busqueda` ni para paginar sus resultados.
- Slugs, canonical, redirecciones y sitemap no se modifican por esta correccion.

Validacion contextual del 2026-09-26: 71 comprobaciones aprobadas con codigo local
y conexion configurada en READ ONLY (incluye fixtures SQL):

- `Alimento para erizo`: 8 alimentos del diagnostico; incluye Premium.
- Sugerencias de 6 son exactamente los primeros 6 de limite 12 y de busqueda.
- Recorrido con limite 3 recupera los 8 sin duplicados ni omisiones.
- `alimento` con paginas de 12 coincide con los primeros 24 en una sola pagina.
- Singular/plural, mayusculas, acentos y sinonimo `comida` conservan los 8 alimentos.
- `areneros`: 59 publicaciones con los datos actuales.
- `Filtro para pecera de 40 litros`: 45 opciones de producto/contexto; capacidad
  orientativa. Sugerencias 6/12, pagina 2 y limite 24 conservan ranking estable.
  Fixtures verifican orden preferido para 40 l, contexto en categoria sin titulo
  y no confundir caudal 40 l/h con capacidad. 40l y 40 litros recuperan lo mismo.

Pruebas reproducibles:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_busqueda_relevancia_readonly.php --model
C:\xampp\php\php.exe storage\uat\uat_ecommerce_busqueda_relevancia_readonly.php --base=https://sys.artiani.com.mx
```

El fixture de ocho slugs es evidencia del diagnostico, no una regla del motor. Si
cambian publicaciones o slugs, actualizar el fixture de prueba de forma explicita.
El modo HTTP valida endpoints; los fixtures SQL controlados corren en modo `--model`.

Siguiente paso: desplegar modelo y trait por Git y repetir UAT HTTP de v3.
Si frontend conserva respuestas cacheadas de busqueda/sugerencias, invalidarlas al
desplegar esta version. No hace falta cambiar URLs de productos ni redirecciones.

Evidencia historica del 2026-09-25: sys devolvia `total=178`,
`query_usada_catalogo=alimento` y no entrega `motor_version` para el caso erizo.
La verificacion HTTP de v2 posterior recupero erizo=8 pero filtro 40 litros=0.
El dueno rechazo ese cero por regla excesiva y solicito la correccion contextual v3.

Se revoca la instruccion anterior de propagar agrupacion=producto a busqueda y
sugerencias. La agrupacion automatica es para categorias. El despliegue incluye
`app/modelos/EcommerceCatalogoPublico.php` y `app/modelos/EcommerceCatalogoPresentacion.php`.

## Endpoint recomendado

Frontend debe preferir este endpoint para la pagina publica `/buscar/{termino}`:

```text
GET /ecommercePublico/busqueda?q={texto}&pagina=1&limite=24
```

Tambien acepta:

```text
GET /ecommercePublico/busqueda?q={texto}&categoria_slug={path_slug}&marca_slug={slug}&orden=relevancia&pagina=1&limite=24
```

No elimina `GET /ecommercePublico/catalogo?q=...`; queda como fallback tecnico.

## Manifest de busqueda

Para diagnostico y alineacion con CMS existe:

```text
GET /ecommercePublico/busqueda_manifest
```

Devuelve:

```text
depurar.fase=busqueda_inteligente_v1
depurar.fuente=defaults_codigo|bd_configuracion|defaults_codigo_error_config
depurar.configuracion.sinonimos
depurar.configuracion.stopwords
depurar.configuracion.prioridad_terminos
depurar.configuracion.categorias_probables
depurar.configuracion.boosts
depurar.configuracion.mensajes
depurar.cms.clave_configuracion=busqueda_inteligente_config
```

Cuando CMS publique reglas, debe guardarlas en `erp_ecommerce_configuracion.clave=busqueda_inteligente_config` como JSON activo. Si no existe configuracion, API usa defaults seguros en codigo.

## Autocomplete

Para sugerencias en vivo usar:

```text
GET /ecommercePublico/busqueda_sugerencias?q={texto}&limite=6
```

Tambien usa interpretacion inteligente v1:

```text
depurar.fase=busqueda_sugerencias_inteligente_v1
depurar.query_usada_productos
depurar.interpretacion
depurar.sugerencias
depurar.terminos_relacionados
```

Los productos sugeridos usan URL publica:

```text
/producto/{slug_publico}
```

No usar:

```text
/ecommercePublico/producto/{slug}
```

## Respuesta

Los productos llegan en:

```text
depurar.items
```

Cada item conserva la misma estructura de `GET /ecommercePublico/catalogo`, incluyendo:

```text
slug
slug_publico
url
url_publica
canonical_url
nombre
marca
marca_obj
categoria
categoria_obj
categorias[]
presentacion
imagen
imagenes[]
precio
moneda
disponibilidad
grupo_producto
```

La interpretacion llega en:

```text
depurar.interpretacion
```

Campos utiles:

```text
texto_original
texto_normalizado
terminos[]
termino_principal
intencion
categoria_probable
atributos_detectados
sinonimos_aplicados[]
```

Cuando no haya coincidencias exactas, frontend debe usar:

```text
depurar.mensaje_cliente
depurar.sugerencias[]
depurar.categorias_relacionadas[]
depurar.marcas_relacionadas[]
depurar.terminos_relacionados[]
```

## Ejemplo

```text
GET /ecommercePublico/busqueda?q=Filtro%20para%20pecera%20de%2040%20litros&limite=3
```

Respuesta esperada:

```text
depurar.fase=busqueda_inteligente_v1
depurar.interpretacion.termino_principal=filtro
depurar.interpretacion.atributos_detectados.capacidad_litros=40
depurar.interpretacion.atributos_detectados.mascota=peces
depurar.interpretacion.atributos_detectados.habitat=acuario
depurar.interpretacion.categoria_probable.url=/categoria/acuario-y-peces/equipamiento-tecnico/filtracion-y-oxigenacion
```

## Categorias y marcas relacionadas

`depurar.categorias_relacionadas[]` entrega rutas publicas para guiar landings o estados sin resultados:

```text
nombre
path_slug
url
total_estimado
```

`depurar.marcas_relacionadas[]` se llena cuando la busqueda coincide con una marca publica:

```text
id
nombre
slug_publico
url
logo
imagen_banner
total_productos
```

El frontend debe usar `url` directamente. No construir `/categoria/...` ni `/marca/...` desde nombres.

## Reglas frontend

- No generar slugs desde el nombre.
- Usar `url`, `slug_publico`, `path_slug` y `canonical_url` enviados por API.
- En autocomplete, usar `depurar.grupos.productos[].url` como ruta publica.
- En resultados completos, usar `depurar.items[].url` como ruta publica.
- No calcular precios en frontend.
- No mostrar stock exacto.
- No mostrar JSON tecnico al cliente.
- Si `depurar.items` tiene productos, pintar listado normal.
- Si `depurar.items` esta vacio, mostrar mensaje amable, sugerencias, categorias relacionadas y CTA a WhatsApp.
- Registrar la busqueda con `POST /ecommercePublico/busqueda_registrar` usando el `session_id` anonimo.

## Analytics recomendado

Despues de renderizar resultados:

```text
POST /ecommercePublico/busqueda_registrar
```

Payload:

```json
{
  "session_id": "uuid-localstorage",
  "canal": "web_publica",
  "query": "Filtro para pecera de 40 litros",
  "ruta": "/buscar/filtro-para-pecera-de-40-litros",
  "resultados_total": 38,
  "sin_resultados": false,
  "filtros": {
    "categoria_sugerida": "acuario-y-peces/equipamiento-tecnico/filtracion-y-oxigenacion"
  },
  "metadata": {
    "endpoint": "/ecommercePublico/busqueda",
    "fase": "busqueda_inteligente_v1"
  }
}
```

## Guardrails API

- Solo lectura.
- No registra busquedas por si solo.
- Solo usa publicaciones vigentes.
- No usa legacy `ecom_*` como fuente publica.
- No devuelve stock exacto.
- No devuelve productos a granel.
- No expone costos.

## Pendiente CMS/Ecommerce

En una fase posterior se debe crear administracion para:

- Diccionario de sinonimos.
- Reglas de intencion.
- Mensajes para busquedas sin resultados.
- Boosts por categoria, marca o producto destacado.

## UAT read-only

Para validar el contrato local:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_busqueda_inteligente_readonly.php --base=http://panel.com.local --q="Alimento para erizo" --limite=3
```

Debe responder:

```text
ok=true
senal_frontend=busqueda_inteligente_lista
busqueda.fase=busqueda_inteligente_v1
sugerencias.fase=busqueda_sugerencias_inteligente_v1
manifest.clave_configuracion=busqueda_inteligente_config
```
