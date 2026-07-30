# ERP Ecommerce publico - Experiencia cliente, politicas, facturacion y analytics

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-26  
Estado: diseno vivo; no activa escrituras publicas todavia.

## Objetivo

Construir el ecommerce como ecosistema para mascotas, no solo como catalogo:

- politicas publicas claras;
- solicitud de factura por folio;
- navegacion guiada por mascota/necesidad;
- historial de busquedas;
- historial de navegacion;
- panel interno para entender que buscan los clientes;
- base futura para registro de clientes y mascotas.

## Prioridad inmediata

1. Politicas publicas.
2. Pantalla de solicitud de factura por folio.
3. Navegacion basica por mascota/necesidad.
4. Preparar tracking de busquedas/navegacion.
5. Panel ERP analitico read-only.

Registro de clientes y mascotas queda contemplado, pero no se activa todavia.

## Politicas publicas necesarias

Minimo recomendado:

- terminos y condiciones;
- aviso de privacidad;
- politica de cotizacion por WhatsApp;
- politica de disponibilidad;
- politica de precios sujetos a confirmacion;
- politica de cambios/devoluciones;
- politica de facturacion;
- politica de envios/entregas futura;
- politica de uso de cookies/tracking.

Regla de negocio:

- No prometer checkout si la fase es cotizacion.
- No prometer inventario exacto.
- No prometer precio final hasta confirmacion si operativamente aplica.
- La politica de tracking debe explicar que se usan busquedas/navegacion para mejorar catalogo y recomendaciones.

## Facturacion por folio

El frontend debe tener una pantalla:

```text
/facturacion
```

Flujo propuesto:

1. Cliente captura folio de compra/ticket/pedido.
2. Cliente captura datos fiscales.
3. Cliente adjunta o referencia ticket si aplica.
4. ERP registra solicitud.
5. Equipo interno revisa.
6. Contador genera factura en flujo fiscal correspondiente.
7. ERP marca estatus.

Campos iniciales:

- folio de compra;
- fecha de compra;
- importe opcional;
- RFC;
- razon social;
- regimen fiscal;
- uso CFDI;
- codigo postal fiscal;
- correo;
- telefono;
- notas;
- archivo/ticket futuro.

Estatus:

```text
nueva
en_revision
datos_incompletos
facturada
rechazada
cancelada
```

Guardrail:

- El frontend no genera factura.
- El frontend solo solicita/recolecta datos.
- El ERP/contador revisa y emite.

## Busqueda y navegacion

Queremos aprender:

- que buscan los clientes;
- que mascota seleccionan;
- que necesidades eligen;
- que busquedas no tienen resultados;
- que productos ven;
- que productos agregan a cotizacion;
- donde abandonan.

Eventos recomendados:

```text
page_view
select_mascota
select_necesidad
search
view_product
add_to_quote
open_whatsapp
facturacion_view
facturacion_submit
```

Datos permitidos:

- session_id anonimo;
- canal;
- ruta;
- query;
- mascota;
- necesidad;
- id_publicacion/id_sku si aplica;
- filtros;
- resultados_total;
- sin_resultados;
- timestamp.

Datos que no deben registrarse sin cuidado:

- datos fiscales en eventos de navegacion;
- telefono/correo en eventos genericos;
- nombres de cliente en tracking anonimo;
- IP o user agent en claro. Usar hash.

## Navegacion por mascota

La pagina debe permitir que el cliente empiece por:

```text
perro
gato
pez
ave
reptil
roedor
otra
```

Luego por necesidad:

```text
alimento
premio
higiene
salud
paseo
habitat
juguete
estetica
```

Futuro:

- edad;
- tamano;
- raza;
- condicion/necesidad especial;
- mascotas registradas;
- recomendaciones.

## Panel ERP futuro

Panel interno recomendado:

```text
Ecommerce > Inteligencia cliente
```

Endpoint interno protegido preparado:

```http
GET /ecommercePublico/inteligencia_cliente_erp
```

UAT:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_inteligencia_cliente_readonly.php
```

Si las tablas aun no existen, responde `configurado=false` con arreglos vacios. Cuando existan y haya persistencia autorizada, el mismo contrato entregara busquedas frecuentes, busquedas sin resultado, mascotas/necesidades consultadas, productos vistos, productos agregados a cotizacion, conversion a WhatsApp y solicitudes de facturacion por estatus.

Vistas:

- busquedas mas frecuentes;
- busquedas sin resultado;
- mascotas mas consultadas;
- necesidades mas consultadas;
- productos mas vistos;
- productos mas agregados a cotizacion;
- conversion a WhatsApp;
- solicitudes de facturacion;
- oportunidades para publicar productos nuevos.

## Tablas propuestas

- `erp_ecommerce_politicas`;
- `erp_ecommerce_facturacion_solicitudes`;
- `erp_ecommerce_eventos_navegacion`;
- `erp_ecommerce_busquedas`;
- `erp_ecommerce_taxonomia_mascotas`.

Endpoints internos protegidos:

```http
GET /ecommercePublico/esquema_auditar_experiencia_cliente
GET /ecommercePublico/esquema_plan_experiencia_cliente
```

Comandos de seguridad antes de aplicar DDL:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_apply_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_postcheck_readonly.php
```

Comando futuro autorizado, no ejecutar sin autorizacion explicita y respaldo externo:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_EXPERIENCIA_CLIENTE_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

Aplicar este DDL solo crea las tablas de experiencia cliente. No activa persistencia publica de facturacion, busquedas o navegacion; esos POST seguiran en preflight hasta implementar y autorizar la escritura por separado.

## Endpoints publicos read-only activos

Activos desde Fase 1 como solo lectura:

```http
GET /ecommercePublico/politicas
GET /ecommercePublico/politica/{slug}
GET /ecommercePublico/taxonomia_mascotas
```

Regla:

- Si las tablas futuras no existen, responden defaults seguros con `configurado=false`.
- No escriben BD.
- No registran aceptaciones ni sesiones.
- Pueden consumirse ya por el frontend.

## Endpoints publicos activos como preflight sin escritura

Activos para que el frontend pueda validar UX y payloads sin persistir:

```http
POST /ecommercePublico/facturacion_solicitar
POST /ecommercePublico/evento_navegacion
POST /ecommercePublico/busqueda_registrar
```

Regla actual:

- responden JSON estable con `preflight=true`;
- devuelven `no_escribe_bd=true`;
- no registran datos reales;
- `facturacion_solicitar` valida folio/datos fiscales/contacto/aviso, pero no emite factura ni crea solicitud real;
- `evento_navegacion` y `busqueda_registrar` rechazan metadata con datos personales detectables;
- sirven para que frontend construya formularios, consentimiento, errores y analytics local sin inventar contrato.

Activacion futura de persistencia:

- `POST facturacion_solicitar` requiere captcha/rate limit/politica privacidad.
- `POST evento_navegacion` y `POST busqueda_registrar` requieren consentimiento/cookie policy/rate limit.

## Lo que frontend puede avanzar ya

Puede avanzar desde ahora:

- paginas visuales de politicas consumiendo `GET /ecommercePublico/politicas`;
- pagina `/facturacion` con formulario validado contra `POST /ecommercePublico/facturacion_solicitar`;
- UI de seleccion de mascota/necesidad consumiendo `GET /ecommercePublico/taxonomia_mascotas`;
- tracking local/mock de eventos y preflight contra `POST /ecommercePublico/evento_navegacion`;
- busquedas con preflight contra `POST /ecommercePublico/busqueda_registrar`;
- panel visual/mock de analitica si lo desea;
- textos claros de privacidad y uso de datos.

No debe prometer registro real hasta que el ERP active persistencia con autorizacion.

## Señal para frontend

Estado actual:

```text
politicas_ui=puede_avanzar_desde_api
facturacion_ui=puede_avanzar_con_preflight_sin_persistencia
navegacion_mascota=puede_avanzar_desde_api
analytics_ui=puede_avanzar_mock_y_preflight
tracking_post_real=preflight_sin_persistencia
facturacion_post_real=preflight_sin_persistencia
```
