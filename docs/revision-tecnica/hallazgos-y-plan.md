# Hallazgos y plan

Fecha: 2026-11-06

## Hechos verificados

- La tienda usa PHP propio y llama al ERP desde servidor mediante `ErpEcommerceApi`.
- `render_page()` carga datos globales desde la API.
- La configuracion declarada contiene `ERP_API_CACHE_SECONDS=60` y `APP_PAGE_CACHE_SECONDS=0` para produccion, pero el `.env` desplegado aun debe confirmarse.
- El cliente API tenia memo por peticion y cache de archivos, pero no coordinaba todas las regeneraciones entre procesos.
- `/api/health` ejecutaba `apiSnapshot()`, que consulta varias dependencias.
- `/estado` hace verificaciones y conteos; `/catalogo` hace conteo, consulta de elementos y procesamiento posterior.
- No se demostro recursividad ERP -> tienda -> ERP.

## Cambios implementados

### R1 - Bloqueo por clave de cache

Archivo: `C:\xampp\htdocs\frontend\ecommerce-publico\app\Api\ErpEcommerceApi.php`

Antes, varias peticiones concurrentes podian detectar el mismo cache vencido y consultar el ERP simultaneamente. Ahora las rutas GET cacheables adquieren un archivo de bloqueo derivado de la URL, con espera acotada por `ERP_API_CACHE_LOCK_WAIT_SECONDS` (0.25 segundos por defecto). Tras adquirirlo se relee la entrada.

Prioridad: alta, porque ataca directamente la autosaturacion posible.

Compatibilidad: conserva los contratos JSON y no aplica a catalogo, busqueda, producto ni POSTs no cacheables.

### R2 - Salud basica separada

Archivo: `C:\xampp\htdocs\frontend\ecommerce-publico\public\index.php`

`/api/health` ya no ejecuta consultas de negocio. El diagnostico anterior queda disponible mediante `?deep=1` o `/api/health/deep`.

Prioridad: alta si existe un monitor que consulte health con frecuencia; el incidente no prueba que ese monitor haya iniciado el pico.

## Hipotesis restantes

1. Fan-out por pagina y cache HTML desactivado: probabilidad alta por el codigo y la distribucion historica de rutas.
2. Estampida de cache: riesgo confirmado por el diseno anterior; la correccion local necesita prueba con procesos independientes en staging.
3. SQL o limites de PHP/Apache: pendientes de tiempos por etapa y logs del servidor.
4. Reintentos externos, bots o monitores: pendientes de User-Agent, IP, Referer y reglas del proxy.
5. Recursion: no demostrada.

## Matriz priorizada

| ID | Area | Evidencia | Recomendacion | Prioridad | Estado |
|---|---|---|---|---|---|
| R1 | Cache | `ErpEcommerceApi.php`, request/cache | Bloqueo por URL, rerelectura y espera acotada | Alta | Implementado, pruebas aisladas pasadas |
| R2 | Salud | `public/index.php`, apiSnapshot | Health basico y deep separado | Alta | Implementado, prueba HTTP bloqueada por sockets locales |
| R3 | Observabilidad | Falta correlacion en logs | Medir request principal, endpoint, cache, status y duracion sin cuerpos | Alta | Pendiente de staging/servidor |
| R4 | SQL | `/estado` y `/catalogo` realizan consultas reales | Medir SQL y EXPLAIN antes de cambiar indices o consultas | Media | Pendiente; no se hizo DDL |
| R5 | HTML cache | `APP_PAGE_CACHE_SECONDS=0` | Evaluar solo paginas publicas elegibles con claves e invalidacion | Media | Propuesta, no activada |
| R6 | POO | Cliente API concentra HTTP, cache y errores | Separar solo si una prueba necesita reemplazar transporte/cache | Baja | Propuesta; no reescribir |

## Seguridad preliminar

La revision no es certificacion ASVS. El frontend mantiene el cliente API en servidor y no conecta a MySQL. Se observaron contratos publicos y validaciones de salida existentes, pero faltan pruebas sinteticas de autorizacion entre usuarios en el ERP, revision completa de archivos/cargas y verificacion de dependencias con avisos primarios.

No se encontro evidencia en esta pasada de `eval` o ejecucion de comandos en el flujo publico revisado. Las consultas de negocio deben seguir auditandose por parametrizacion y control de identificadores dinamicos. No se revisaron ni se exponen valores de `.env`.

