# BI Publicidad / Temporadas - Reportes Legacy

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-31  
Estado: diagnostico inicial; pendiente recuperar tablas legacy con datos historicos.

## Objetivo

Usar el historico de comportamiento captado en la pagina anterior para decidir que productos, categorias, clasificaciones y marcas conviene promover por temporada, rango de fechas y tendencia.

Tablas legacy indicadas por el dueno:

- `bi_busquedas`
- `bi_seguimiento_consumibles`

## Diagnostico local

Fecha de revision: 2026-08-31

- En la base local `artianilocal` no existen `bi_busquedas` ni `bi_seguimiento_consumibles`.
- Si existen tablas catalogo legacy utiles para cruzar el historico:
  - `ecom_productos`
  - `ecom_categorias`
  - `ecom_clasificaciones`
  - `ecom_marcas`
- El modelo legacy `app/modelos/BI_Modelo.php` confirma el uso de `bi_seguimiento_consumibles` para visitas por producto.
- El archivo `db/querys/querys.php` conserva consultas base para productos, categorias, clasificaciones y marcas visitadas.
- El archivo `public/js/busquedaJs/main.js` contiene llamadas a `https://panoramex.mx/busquedas/show`; puede ser pista de que parte del historico de busquedas se registro en un backend externo/anterior.

## Diagnostico productivo principal

Fecha de revision: 2026-08-31  
Base validada por conexion del proyecto: `artianicom_artiani`

- `bi_seguimiento_consumibles` existe:
  - columnas: `id_seguimiento`, `tipo`, `accion`, `identificador`, `fch_r`;
  - rango detectado: 2023-02-09 22:44:12 a 2026-08-30 21:50:30;
  - total detectado: 928141 eventos.
- `bi_busquedas` existe:
  - columnas: `id_busqueda`, `busqueda`, `fch_r`;
  - rango detectado: 2023-07-03 00:02:42 a 2026-08-29 17:24:51;
  - total detectado: 12460 busquedas.
- `bi_busquedas` no contiene bandera/campo de resultados, por lo que el reporte "busquedas sin resultado" no se puede calcular con el historico legacy actual.

Prueba read-only de dashboard para 2026-01-01 a 2026-08-31:

- eventos consumibles: 327394;
- visitas a productos: 156320;
- visitas a categorias: 137289;
- visitas a clasificaciones: 23857;
- visitas a marcas: 4001;
- busquedas: 4549.

Primeras senales detectadas en esa prueba:

- productos vistos: `Arenero craft`, `Pecera panoramica equipo basico 50 litros`, `Pez guppy dumbo`;
- busquedas frecuentes: `terrario`, `pecera`, `tortuguero`.

## Campos minimos esperados

De acuerdo con el codigo legacy, `bi_seguimiento_consumibles` debe tener al menos:

- `tipo`: tipo de entidad consultada o visitada (`producto`, `categoria`, `clasificacion`, `marca`).
- `identificador`: id de la entidad correspondiente.
- `fch_r`: fecha/hora de registro.

Para `bi_busquedas` falta confirmar estructura desde respaldo o base anterior. Campos utiles esperados para analisis:

- texto de busqueda o query normalizada;
- fecha/hora de registro;
- resultados encontrados o bandera de sin resultado;
- canal, ruta o origen si existiera.

## Reportes recomendados

1. Demanda por mes
   - Mide volumen mensual de visitas y busquedas.
   - Sirve para detectar meses fuertes antes de invertir publicidad.

2. Top productos por temporada
   - Agrupa visitas por producto y mes.
   - Sirve para elegir productos concretos para anuncios.

3. Top categorias y clasificaciones por temporada
   - Agrupa interes por familia.
   - Sirve para campanas mas amplias cuando no conviene anunciar SKU por SKU.

4. Marcas con interes recurrente
   - Detecta marcas que atraen trafico.
   - Sirve para negociar compra, surtido o campanas por marca.

5. Busquedas sin resultado
   - Detecta productos que el cliente queria pero no encontro.
   - Sirve para comprar, publicar o crear contenido.

6. Productos con pico estacional
   - Compara cada mes contra el promedio anual del mismo producto.
   - Sirve para anticipar promociones, inventario y publicaciones.

7. Calendario comercial sugerido
   - Convierte los hallazgos en recomendaciones por mes:
     - promover;
     - revisar inventario;
     - mejorar ficha/fotos;
     - buscar proveedor;
     - pausar o bajar prioridad.

## SQL base read-only

### Rango de datos

```sql
SELECT
  MIN(fch_r) AS desde,
  MAX(fch_r) AS hasta,
  COUNT(*) AS total
FROM bi_seguimiento_consumibles;
```

### Visitas por mes y tipo

```sql
SELECT
  DATE_FORMAT(fch_r, '%Y-%m') AS mes,
  tipo,
  COUNT(*) AS eventos
FROM bi_seguimiento_consumibles
GROUP BY DATE_FORMAT(fch_r, '%Y-%m'), tipo
ORDER BY mes, eventos DESC;
```

### Top productos por mes

```sql
SELECT
  DATE_FORMAT(bisc.fch_r, '%Y-%m') AS mes,
  ecomp.id_producto,
  ecomp.sku,
  ecomp.nombre,
  COUNT(*) AS visitas
FROM bi_seguimiento_consumibles bisc
INNER JOIN ecom_productos ecomp ON ecomp.id_producto = bisc.identificador
WHERE bisc.tipo = 'producto'
GROUP BY DATE_FORMAT(bisc.fch_r, '%Y-%m'), ecomp.id_producto, ecomp.sku, ecomp.nombre
ORDER BY mes, visitas DESC;
```

### Top categorias por mes

```sql
SELECT
  DATE_FORMAT(bisc.fch_r, '%Y-%m') AS mes,
  ecat.id_categoria,
  ecat.categoria,
  COUNT(*) AS visitas
FROM bi_seguimiento_consumibles bisc
INNER JOIN ecom_categorias ecat ON ecat.id_categoria = bisc.identificador
WHERE bisc.tipo = 'categoria'
GROUP BY DATE_FORMAT(bisc.fch_r, '%Y-%m'), ecat.id_categoria, ecat.categoria
ORDER BY mes, visitas DESC;
```

### Top clasificaciones por mes

```sql
SELECT
  DATE_FORMAT(bisc.fch_r, '%Y-%m') AS mes,
  ecla.id_clasificacion,
  ecla.clasificacion,
  COUNT(*) AS visitas
FROM bi_seguimiento_consumibles bisc
INNER JOIN ecom_clasificaciones ecla ON ecla.id_clasificacion = bisc.identificador
WHERE bisc.tipo = 'clasificacion'
GROUP BY DATE_FORMAT(bisc.fch_r, '%Y-%m'), ecla.id_clasificacion, ecla.clasificacion
ORDER BY mes, visitas DESC;
```

### Top marcas por mes

```sql
SELECT
  DATE_FORMAT(bisc.fch_r, '%Y-%m') AS mes,
  emar.id_marca,
  emar.marca,
  COUNT(*) AS visitas
FROM bi_seguimiento_consumibles bisc
INNER JOIN ecom_marcas emar ON emar.id_marca = bisc.identificador
WHERE bisc.tipo = 'marca'
GROUP BY DATE_FORMAT(bisc.fch_r, '%Y-%m'), emar.id_marca, emar.marca
ORDER BY mes, visitas DESC;
```

## Criterios de interpretacion

- No tomar una visita aislada como senal de compra; priorizar patrones repetidos por mes o por temporada.
- Si un producto tiene muchas visitas pero poca venta futura, revisar precio, foto, descripcion, stock y facilidad de compra.
- Si una busqueda se repite y no tiene resultado, tratarla como oportunidad de catalogo o compra.
- Para publicidad inicial, priorizar grupos con demanda historica y producto disponible, no solo gustos personales.
- Cruzar estos reportes con ventas/POS cuando el modulo lo permita, para separar interes de conversion real.

## Pendientes

- Recuperar respaldo o base anterior que contenga `bi_busquedas` y `bi_seguimiento_consumibles`.
- Confirmar columnas reales de `bi_busquedas`.
- Revisar si el endpoint anterior `panoramex.mx/busquedas/show` tenia exportacion o base asociada con el historico de busquedas.
- Generar CSV/XLSX con top por mes y calendario comercial cuando haya datos.
- Decidir si estos reportes se integran al dashboard nuevo de ecommerce analytics o quedan como importacion historica separada.
- Agregar lectura de `accion` en `bi_seguimiento_consumibles` para separar visita, clic u otro evento si el dato historico lo permite.

## Implementacion inicial separada

Fecha: 2026-08-31

- Se crea modulo aislado `BusinessIntelligence`.
- Ruta inicial: `/businessintelligence/publicidad_temporadas`.
- Endpoint read-only: `/businessintelligence/publicidad_dashboard_erp`.
- Endpoint diagnostico: `/businessintelligence/diagnostico_erp`.
- Modelo: `BusinessIntelligenceErp`.
- Vista: `apps/erp/bi/publicidad_temporadas`.
- JS: `public/assets/js/custom/apps/erp/bi/publicidad_temporadas.js`.
- La pantalla incluye KPIs, tendencia mensual, acciones historicas, recomendaciones iniciales, tops por producto/busqueda/categoria/clasificacion/marca y calendario comercial.

Guardrails:

- no escribe BD;
- no crea DDL;
- no toca ventas;
- no toca inventario;
- no mezcla los datos legacy `bi_*` con Ecommerce / Analytics nuevo;
- usa permisos existentes `reportes.ver`, `finanzas.ver`, `ventas.ver`, `catalogo.ver` o `ecommerce.ver` hasta autorizar permisos finos `bi.ver`.

## Iteracion visual inicial

Fecha: 2026-08-31

- `bi_seguimiento_consumibles.accion` existe y en la muestra productiva se observo principalmente `visita`.
- Se agrega serie `tendencia_mensual` para graficar productos, categorias y busquedas por mes.
- Se agrega `acciones_consumibles` para leer el tipo de evento captado por el tracking historico.
- Se agrega `recomendaciones_publicidad` como primera capa gerencial:
  - busquedas frecuentes para campanas o landings;
  - productos con interes historico para validar stock, precio, utilidad y contenido;
  - categorias con demanda agrupada para campanas por familia.

## Correccion de fuente BI

Fecha: 2026-08-31

- En `panel.com.local`, la conexion activa puede apuntar a `artianicom_sys`, donde no existen `bi_busquedas` ni `bi_seguimiento_consumibles`.
- El modulo BI ahora detecta esa condicion y usa una conexion read-only a la base historica `artianicom_artiani` cuando ahi existan las tablas legacy.
- El resto del ERP conserva su conexion activa; el fallback solo aplica dentro de `BusinessIntelligenceErp`.
- La UI muestra la fuente usada en el diagnostico para evitar confundir base activa con base historica BI.
