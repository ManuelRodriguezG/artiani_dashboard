# ERP Ecommerce / Analytics - Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-31  
Estado: Analytics v1 con persistencia publica real anonima activa.

## Objetivo

Crear una capa profesional de analytics ecommerce separada de ventas, checkout, inventario y legacy `ecom_*`.

La API publica sigue siendo la fuente para el frontend externo. El frontend no debe leer docs ni archivos internos del ERP; debe consumir:

```http
GET /ecommercePublico/analytics_contrato
POST /ecommercePublico/analytics_sesion
POST /ecommercePublico/evento_navegacion
POST /ecommercePublico/busqueda_registrar
POST /ecommercePublico/analytics_conversion
```

## Guardrails

- No guardar datos personales en analytics.
- No guardar nombre, telefono, correo, RFC, razon social, direccion ni datos fiscales.
- Usar `session_id_hash` derivado de `session_id` anonimo.
- No guardar ni mostrar stock exacto.
- No crear checkout ni pagos.
- No crear ventas, pedidos ni cotizaciones reales.
- No descontar inventario.
- No usar legacy `ecom_*` como fuente.
- No mezclar analytics con ventas reales todavia.

## Eventos permitidos

```text
page_view
view_product
search
select_mascota
select_necesidad
add_to_quote
remove_from_quote
quote_dryrun
quote_preflight
open_whatsapp
facturacion_view
facturacion_submit
```

## Esquema propuesto

El esquema dedicado nuevo usa tablas con prefijo `erp_ecommerce_analytics_*`:

- `erp_ecommerce_analytics_sesiones`
- `erp_ecommerce_analytics_eventos`
- `erp_ecommerce_analytics_busquedas`
- `erp_ecommerce_analytics_conversiones`
- `erp_ecommerce_analytics_resumen_diario`

Endpoints internos read-only:

```http
GET /ecommercePublico/esquema_auditar_analytics
GET /ecommercePublico/esquema_plan_analytics
```

Aplicar DDL queda pendiente de autorizacion explicita con respaldo externo y UAT `apply_authorized`.

La auditoria `GET /ecommercePublico/esquema_auditar_analytics` valida existencia de tablas, columnas e indices criticos.

UATs:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_plan_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_http_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_dashboard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_persistencia_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_resumen_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_retencion_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_schema_postcheck_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_schema_apply_authorized.php --autorizar=ECOMMERCE_ANALYTICS_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_persistencia_publica_http.php --base=http://panel.com.local
```

El comando `schema_apply_authorized` no debe ejecutarse sin autorizacion operativa explicita y respaldo existente.

## Activacion real 2026-08-31

Base efectiva por override local para `panel.com.local`:

```text
MYSQLHOST=201.131.127.234
MYSQLPORT=3306
MYSQLBASE=artianicom_sys
```

Respaldo externo generado antes del DDL:

```text
archivo=C:\xampp\panel_db_backups\artianicom_sys_panel_20260830_215637_antes_ecommerce_analytics_v1.sql
tamano_bytes=39514261
sha256=0c9d62f08c76c6056e017fad0f341dd4a3533c5506f777f277dcada8acde6e42
```

Resultado de esquema:

```text
tablas_faltantes_antes=5
tablas_faltantes_despues=0
columnas_faltantes_total=0
indices_faltantes_total=0
```

Bandera activa:

```text
ECOMMERCE_ANALYTICS_TRACKING_PUBLICO=true
```

Contrato publico actual:

```text
depurar.estado=persistencia_publica_activa
depurar.persistencia.activa=true
depurar.persistencia.modo_actual=registra_bd
```

UAT publico:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_persistencia_publica_http.php --base=http://panel.com.local
senal_frontend_analytics_http=verde_persistencia_publica_anonima
```

El UAT registra eventos anonimos de prueba y confirma que payloads con PII o stock exacto quedan bloqueados con `no_escribe_bd=true`.

## Persistencia publica activa

El modelo `EcommerceAnalyticsErp` contiene funciones de persistencia autorizada:

- `registrarSesionAutorizada`;
- `registrarEventoAutorizado`;
- `registrarBusquedaAutorizada`;
- `registrarConversionAutorizada`.

Estan conectadas a los POST publicos mediante la bandera `ECOMMERCE_ANALYTICS_TRACKING_PUBLICO=true`. Internamente conservan token operativo:

```text
ECOMMERCE_ANALYTICS_TRACKING
```

Sin ese token responden bloqueadas con `no_escribe_bd=true`. Aunque el token exista, tambien bloquean si faltan tablas, PII, stock exacto o tipo de evento no permitido.

Reglas de registro v1:

- `analytics_sesion` crea/actualiza `erp_ecommerce_analytics_sesiones`.
- `evento_navegacion` crea/actualiza sesion ligera y registra `erp_ecommerce_analytics_eventos`.
- `busqueda_registrar` crea/actualiza sesion ligera y registra `erp_ecommerce_analytics_busquedas`.
- `analytics_conversion` crea/actualiza sesion ligera, registra evento de embudo y registra `erp_ecommerce_analytics_conversiones`.
- Conversiones permitidas: `add_to_quote`, `remove_from_quote`, `quote_dryrun`, `quote_preflight`, `open_whatsapp`, `facturacion_submit`.

Endpoint interno de readiness:

```http
GET /ecommercePublico/analytics_persistencia_plan_erp
```

## Resumen diario write-ready bloqueado

El modelo tambien contiene `recalcularResumenDiarioAutorizado` para alimentar `erp_ecommerce_analytics_resumen_diario`.

No esta conectado a jobs ni endpoints publicos en Fase 1. Requiere token separado:

```text
ECOMMERCE_ANALYTICS_RESUMEN_DIARIO
```

Reglas:

- rango maximo por recalc: 31 dias;
- agregacion anonima por fecha/canal;
- no guarda PII;
- no toca ventas, checkout ni inventario.

Endpoint interno de plan:

```http
GET /ecommercePublico/analytics_resumen_plan_erp?desde=YYYY-MM-DD&hasta=YYYY-MM-DD
```

## Retencion write-ready bloqueada

El modelo contiene `purgarRetencionAutorizada` para eliminar datos crudos anonimos antiguos de:

- sesiones;
- eventos;
- busquedas;
- conversiones.

No borra `erp_ecommerce_analytics_resumen_diario`. No esta conectada a jobs ni endpoints publicos en Fase 1. Requiere token:

```text
ECOMMERCE_ANALYTICS_RETENCION
```

Politica inicial sugerida para plan:

- `dias_retencion=180`;
- minimo permitido por codigo: 30 dias;
- maximo permitido por codigo: 730 dias;
- conservar resumen diario para analisis historico agregado.

Endpoint interno de plan:

```http
GET /ecommercePublico/analytics_retencion_plan_erp?dias_retencion=180
```

## Dashboard interno

Vista:

```http
GET /ecommercePublico/analytics
```

Endpoint de datos:

```http
GET /ecommercePublico/analytics_dashboard_erp?desde=YYYY-MM-DD&hasta=YYYY-MM-DD&limite=10
```

Metricas previstas:

- sesiones recientes por hash corto;
- canales;
- visitas por dia;
- URLs mas vistas;
- productos mas vistos;
- productos agregados a cotizacion;
- conversiones por tipo;
- aperturas de WhatsApp;
- facturacion view/submit;
- busquedas mas frecuentes;
- busquedas sin resultados;
- embudo visita > producto > cotizacion > dry-run > preflight > WhatsApp;
- abandono por etapa;
- mascotas mas consultadas;
- necesidades mas consultadas;
- productos con interes pero sin conversion;
- oportunidades para publicar mas productos.

Si el esquema aun no existe, el dashboard responde `configurado=false` con arreglos vacios.

Cuando `erp_ecommerce_analytics_resumen_diario` exista y tenga filas en el rango, el dashboard usa `fuente_metricas=resumen_diario` para KPIs, visitas por dia y embudo. Si no hay resumen diario, conserva `fuente_metricas=eventos_crudos` y calcula desde eventos/busquedas disponibles.

La vista interna v1 muestra un tablero operativo inicial con KPIs, sesiones anonimas recientes, canales, URLs, productos, busquedas, conversiones, facturacion, embudo y abandono por etapa. No muestra `session_id` completo, datos personales ni stock exacto.

UAT especifico:

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_analytics_dashboard_readonly.php
```

## Enlace futuro con cotizaciones/contactos/facturacion

Analytics crudo se mantiene anonimo por `session_id_hash`; no debe guardar nombre, telefono, correo, RFC, razon social, direccion ni datos fiscales.

Cuando se active un modulo separado de atencion ecommerce, cotizaciones/contactos/facturacion deberan recibir el `session_id` anonimo del frontend y calcular el mismo hash en backend. Ese modulo podra guardar una tabla puente con el identificador real del flujo y `session_id_hash`, por ejemplo:

```text
erp_ecommerce_atencion_analytics_links
  id_link
  session_id_hash
  entidad_tipo: cotizacion | contacto | facturacion | pedido_futuro
  entidad_id
  fecha_registro
```

La tabla puente vive fuera de analytics crudo. Analytics no debe incorporar datos personales aunque exista un cliente identificado despues.

## Pendientes posteriores

- definir y publicar politica/cookie consent;
- agregar rate limit por canal/IP hash antes de alto trafico;
- automatizar resumen diario con token `ECOMMERCE_ANALYTICS_RESUMEN_DIARIO`;
- definir retencion inicial de eventos crudos, sugerido `dias_retencion=180`;
- crear tabla puente de atencion ecommerce para enlazar `session_id_hash` con cotizaciones/contactos/facturacion sin contaminar analytics crudo.

Token sugerido para DDL futuro:

```text
ECOMMERCE_ANALYTICS_DDL
```
