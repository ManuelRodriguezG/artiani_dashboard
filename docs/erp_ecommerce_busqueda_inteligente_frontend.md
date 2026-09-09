# Ecommerce Publico - Busqueda Inteligente Frontend

Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09

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
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_busqueda_inteligente_readonly.php --base=http://panel.com.local --q="Filtro para pecera de 40 litros" --limite=3
```

Debe responder:

```text
ok=true
senal_frontend=busqueda_inteligente_lista
busqueda.fase=busqueda_inteligente_v1
sugerencias.fase=busqueda_sugerencias_inteligente_v1
```
