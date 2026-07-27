# ERP Ecommerce publico - API canales y partners

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-25  
Estado: diseno tecnico vivo; no activa tokens ni escrituras todavia.

## Objetivo

Evolucionar la API ecommerce para dos usos sin perder el foco principal:

- frontend propio Artiani;
- partners/mayoristas autorizados que puedan mostrar catalogo y generar oportunidades de venta.

El ERP sigue siendo la fuente de verdad. La API no debe convertirse en una copia del catalogo ni en un canal sin control.

## Decision

La API debe ser multi-canal:

- `frontend_propio`: sitio Artiani, dominio `http://artiani.com.local` y futuro `https://artiani.com.mx`.
- `partner_mayoreo`: amigo/aliado autorizado que puede consumir catalogo segun permisos.
- `integracion_entregas`: futuro API de entregas, separada de catalogo y ventas.

Cada canal debe tener:

- estatus;
- origenes CORS permitidos;
- scopes;
- politicas de precio;
- productos permitidos;
- rate limit;
- auditoria;
- credenciales rotables.

## Regla importante sobre tokens

No entregar un `api_secret` para pegarlo en JavaScript publico.

Si el partner construye una web publica estatica, cualquier token embebido en navegador puede copiarse. Para acciones sensibles se requiere:

- backend/proxy del partner que firme peticiones; o
- backend ligero nuestro para recibir solicitudes del frontend partner y aplicar politicas.

Para endpoints read-only se puede iniciar con CORS + API key publica limitada, pero eso no debe considerarse seguridad fuerte.

## Modelo de seguridad recomendado

Headers:

```http
X-Ecommerce-Api-Key: {api_key}
X-Ecommerce-Timestamp: 2026-07-25T12:30:00Z
X-Ecommerce-Nonce: uuid
X-Ecommerce-Signature: hex_hmac_sha256
```

Firma canonica:

```text
METHOD
PATH
QUERY_STRING_NORMALIZADO
SHA256_BODY
TIMESTAMP
NONCE
```

Reglas:

- ventana maxima sugerida: 5 minutos;
- nonce de un solo uso;
- secreto guardado solo como hash o cifrado operativo, nunca visible despues de emitirse;
- rotacion de credenciales;
- logs por canal;
- rate limit por canal, IP y endpoint.

## Scopes iniciales

```text
catalogo:leer
producto:leer
filtros:leer
disponibilidad:leer
cotizacion:dryrun
cotizacion:registrar
entregas:consultar_futuro
```

Fase actual:

- Artiani propio: `catalogo:leer`, `producto:leer`, `filtros:leer`, `disponibilidad:leer`, `cotizacion:dryrun`.
- Partner mayorista inicial: `catalogo:leer`, `producto:leer`, `filtros:leer`, `disponibilidad:leer`, `cotizacion:dryrun`.
- `cotizacion:registrar` queda pendiente hasta activar persistencia segura.

## Productos por canal

No todos los productos publicados para Artiani deben salir automaticamente a un partner.

Modelo:

- `erp_ecommerce_publicaciones`: publicacion base del ERP.
- `erp_ecommerce_canal_publicaciones`: allowlist por canal.

Esto permite:

- habilitar solo ciertas marcas/productos al partner;
- pausar producto para partner sin ocultarlo en Artiani;
- definir orden/destacado por canal;
- futuro precio publico, mayoreo o consultar.

## Precios

Fase 1:

- precio publico Artiani desde publicacion viva;
- partner puede iniciar con precio publico o `consultar`, segun politica.

Fase futura:

- integrar listas de precios ERP por canal;
- precio partner desde lista autorizada;
- nunca aceptar precio enviado por frontend como verdad.

## Carritos y pedidos

El partner no debe crear ventas directas en Fase 1.

Flujo recomendado:

1. Cliente arma carrito en web Artiani o partner.
2. Web llama `cotizacion_dryrun`.
3. ERP recalcula precio/disponibilidad.
4. Web abre WhatsApp o formulario.
5. Futuro: `cotizacion_registrar` guarda intencion con `id_canal_api`.
6. ERP muestra bandeja de cotizaciones ecommerce.
7. Vendedor convierte manualmente a pedido o venta.

El folio debe incluir canal:

```text
WEB-ART-20260725-000001
WEB-PAR-20260725-000001
```

## Tablas propuestas

- `erp_ecommerce_canales_api`: canal, partner, origenes, scopes, limites y politica.
- `erp_ecommerce_api_credenciales`: API key, hash de control, secreto cifrado si aplica, estatus y rotacion.
- `erp_ecommerce_canal_publicaciones`: productos permitidos por canal.
- `erp_ecommerce_api_nonces`: proteccion anti replay.
- `erp_ecommerce_api_logs`: auditoria tecnica de consumo.

Plan read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_partner_api_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --partner_origin=https://partner.example.com
```

Simulador read-only de firma HMAC:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_hmac_contract_readonly.php
```

Este simulador usa un secreto demo, no consulta secretos reales y no activa autenticacion.

Guard de apply:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_api_apply_guard_readonly.php
```

Apply futuro, solo con autorizacion explicita:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_api_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CANALES_API_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

Plan de semillas de canales:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_seed_plan_readonly.php --base=http://panel.com.local --artiani_origin=http://artiani.com.local --artiani_prod=https://artiani.com.mx --partner_codigo=partner_mayoreo_001 --partner_origin=https://partner.example.com
```

Guard de seed:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_seed_apply_guard_readonly.php
```

Plan de allowlist por canal:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canal_allowlist_plan_readonly.php --canal=partner_mayoreo_001 --publicaciones=1,2 --modo_precio=publico
```

Guard de apply allowlist:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canal_allowlist_apply_guard_readonly.php
```

Plan de credencial:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_credencial_plan_readonly.php --canal=partner_mayoreo_001 --modo=hmac
```

Guard de emision:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_credencial_emitir_apply_guard_readonly.php
```

Para emitir HMAC real se requiere `ECOMMERCE_API_SECRET_ENCRYPTION_KEY` en el entorno del ERP. Sin esa llave, la emision queda bloqueada aunque exista respaldo.

Modo observacion de autenticacion:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_auth_observacion_readonly.php --method=GET --path=/ecommercePublico/catalogo --query=limite=2 --origin=http://artiani.com.local
```

Este modo analiza headers y tablas de canal sin bloquear requests. Sirve para probar API key/HMAC antes de hacer obligatoria la autenticacion.

Endpoints internos protegidos:

```http
GET /ecommercePublico/esquema_auditar_canales_api
GET /ecommercePublico/esquema_plan_canales_api
```

## Activacion gradual

1. Mantener frontend Artiani funcionando sin ruptura.
2. Aplicar DDL de canales con respaldo y autorizacion.
3. Crear canal `artiani_web`.
4. Crear canal partner en estatus `borrador`.
5. Asociar productos permitidos al partner.
6. Emitir credencial una sola vez.
7. Activar modo observacion de autenticacion.
8. Validar read-only con rate limit/logs.
9. Despues activar `cotizacion_registrar` con folio y bandeja ERP.

## Reglas de implementacion para partner

- Si el partner tiene backend, puede recibir `api_key` y `api_secret` una sola vez.
- Si el partner solo tiene frontend estatico, no recibe `api_secret`.
- El partner nunca debe recibir costos, proveedores, stock exacto, lotes ni ubicaciones.
- Los productos del partner salen de allowlist, no de todo el catalogo publicado.
- Las cotizaciones del partner deben guardar `id_canal_api` cuando se active persistencia.

## Fuera de alcance inmediato

- checkout;
- pago online;
- venta automatica;
- apartado de inventario;
- stock exacto;
- entrega API;
- OAuth de clientes finales;
- comisiones automáticas de partner.
