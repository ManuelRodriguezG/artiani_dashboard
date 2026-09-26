# Ecommerce Publico - Busqueda Inteligente Frontend

Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09

## Correccion previa al lanzamiento / continuidad

IA: Codex GPT-6 | Fecha: 2026-09-25

Estado: implementado en codigo local y validado con la conexion configurada del proyecto
en una transaccion READ ONLY. Pendiente desplegar en `https://sys.artiani.com.mx`
y repetir QA HTTP/frontend. No se ejecutaron migraciones ni escrituras de productos.

Causa corregida: se elegia `alimento` antes de la frase completa y se ordenaba una
muestra ya limitada de la primera pagina. Esto perdia `erizo`, cambiaba el prefijo
de sugerencias y repetia productos entre paginas.

Contrato vigente (mantiene `fase` v1 por compatibilidad):

- `/busqueda`, `/busqueda_sugerencias` y `/busqueda_manifest` entregan
  `depurar.motor_version=terminos_and_sql_v2` para verificar el despliegue.
- AND entre todos los conceptos de la consulta; OR entre sus sinonimos y plurales.
  `interpretacion.terminos_requeridos` separa conceptos originales de expansiones.
- La ultima palabra admite prefijo desde dos letras para mantener autocompletado
  (`alimento eriz`). Ambos endpoints aplican el mismo criterio; no amplia numeros.
- Se comparan titulo publico, nombre SKU/producto, SKU, marca y presentacion.
  La extension de agrupacion tambien permite coincidencias con valores de atributos
  de selector activos (`es_variante=1`) del SKU, nunca con atributos administrativos.
  Las etiquetas genericas de mascota/categoria no agregan otros productos como
  coincidencias principales. Las categorias relacionadas se entregan por separado.
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
- Numero y unidad son una restriccion conjunta: `40 litros` no equivale a `40 cm`
  ni a `400 litros`. La coincidencia textual no certifica compatibilidad tecnica.
- Una consulta vacia, solo conectores o sin coincidencias devuelve cero productos.
  Los fallos de consulta conservan `error=true`; no son un cero exitoso.
- `/catalogo?q=...` conserva busqueda literal legacy y puede dar un conjunto distinto.
  No usarlo para reemplazar un cero valido de `/busqueda` ni para paginar sus resultados.
- Slugs, canonical, redirecciones y sitemap no se modifican por esta correccion.

Validacion del 2026-09-25: 48 comprobaciones aprobadas con codigo local:

- `Alimento para erizo`: 8 alimentos del diagnostico; incluye Premium.
- Sugerencias de 6 son exactamente los primeros 6 de limite 12 y de busqueda.
- Recorrido con limite 3 recupera los 8 sin duplicados ni omisiones.
- `alimento` con paginas de 12 coincide con los primeros 24 en una sola pagina.
- Singular/plural, mayusculas, acentos y sinonimo `comida` conservan los 8 alimentos.
- `areneros`: 59 publicaciones con los datos actuales.
- `Filtro para pecera de 40 litros`: 0 coincidencias completas con los campos
  actuales. Conserva capacidad 40 y categorias relacionadas. Fixtures SQL read-only
  prueban positivos de 40 l/40 litros y negativos de 40 cm/400 litros/25 litros.

Pruebas reproducibles:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_busqueda_relevancia_readonly.php --model
C:\xampp\php\php.exe storage\uat\uat_ecommerce_busqueda_relevancia_readonly.php --base=https://sys.artiani.com.mx
```

El fixture de ocho slugs es evidencia del diagnostico, no una regla del motor. Si
cambian publicaciones o slugs, actualizar el fixture de prueba de forma explicita.
El modo HTTP valida endpoints; los fixtures SQL controlados corren en modo `--model`.

Siguiente paso: desplegar `app/modelos/EcommerceCatalogoPublico.php`, consultar el
manifest para confirmar version, ejecutar UAT por HTTP y revisar buscador frontend.
Si frontend conserva respuestas cacheadas de busqueda/sugerencias, invalidarlas al
desplegar esta version. No hace falta cambiar URLs de productos ni redirecciones.

Verificacion remota posterior a las pruebas: sys todavia devuelve `total=178`,
`query_usada_catalogo=alimento` y no entrega `motor_version` para el caso erizo.
Esto confirma que el cambio local aun no esta desplegado.

Extension del mismo dia: `agrupacion=producto` activa tarjetas agrupadas antes de
paginar, conservando los ocho alimentos para erizo como productos separados. Ver
`docs/erp_ecommerce_descripciones_agrupacion.md`. El parametro se debe propagar a
busqueda y sugerencias; su default sigue siendo `sku`. El despliegue incluye ahora
tambien `app/modelos/EcommerceCatalogoPresentacion.php`.

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
