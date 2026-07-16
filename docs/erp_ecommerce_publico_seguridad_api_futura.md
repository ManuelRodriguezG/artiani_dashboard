# ERP Ecommerce publico - Seguridad API futura

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: especificacion futura; no activar en Fase 1 read-only.

## Decision

En Fase 1 la API publica permite `GET` read-only y `POST /cotizacion_dryrun` sin persistencia. El registro real queda bloqueado:

```http
POST /ecommercePublico/cotizacion_registrar
```

Antes de desbloquearlo se requiere seguridad de canal, rate limit y politica operativa de seguimiento.

## CORS

Regla:

- permitir solo origenes exactos configurados en `erp_ecommerce_configuracion.cors_origenes_permitidos`;
- no usar `*`;
- no confiar en CORS como autenticacion;
- validar desde ERP con:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://localhost:5173
```

## Headers futuros

Cuando exista backend propio del ecommerce, usar:

```http
X-Ecommerce-Api-Key: {public_key}
X-Ecommerce-Timestamp: 2026-07-15T12:30:00Z
X-Ecommerce-Nonce: uuid-o-random
X-Ecommerce-Signature: hex_hmac_sha256
```

No poner `api_secret` en navegador. Si el ecommerce es SPA estatica, mantener solo endpoints read-only o crear un backend ligero para firmar solicitudes sensibles.

## Firma HMAC propuesta

Base canonica:

```text
METHOD
PATH
QUERY_STRING_NORMALIZADO
SHA256_BODY
TIMESTAMP
NONCE
```

Firma:

```text
hex(hmac_sha256(base_canonica, api_secret))
```

Ventana de tiempo sugerida:

- 5 minutos maximo entre `X-Ecommerce-Timestamp` y hora ERP.

Nonce:

- guardar nonces usados por ventana corta para evitar replay.

## Rate limit

Aplicar antes de activar registro real:

- por IP;
- por telefono/contacto;
- por API key;
- por endpoint.

Acciones:

- limitar `cotizacion_registrar`;
- observar `cotizacion_dryrun` si empieza a recibir abuso;
- captcha si hay formulario publico sin usuario autenticado.

## Registro real de cotizacion

No activar hasta cumplir:

- DDL `erp_ecommerce_*` aplicado con respaldo externo;
- CORS restringido al dominio real;
- API key/HMAC o backend confiable definido;
- rate limit;
- politica CRM de seguimiento;
- WhatsApp configurado en ERP;
- green gate `ok=true`.

## Datos que no debe aceptar como verdad

El frontend nunca manda como verdad:

- precio;
- descuento;
- stock;
- disponibilidad;
- costo;
- almacen;
- lote;
- ubicacion.

El ERP recalcula todo desde publicaciones vivas y disponibilidad publica simple.

## Fuera de alcance

- OAuth de clientes.
- pagos online.
- checkout.
- apartado automatico de inventario.
- conversion automatica a pedido confirmado.

