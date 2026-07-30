# ERP Ecommerce publico - Flujo futuro de cotizaciones registradas

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-29  
Estado: plan read-only; no desbloquea `cotizacion_registrar`.

## Objetivo

Preparar el camino para que el carrito del frontend no se pierda y despues pueda convertirse manualmente en seguimiento, pedido o venta, sin activar checkout ni afectar inventario en Fase 1.

## Flujo actual permitido

1. El frontend arma carrito local.
2. Llama `POST /ecommercePublico/cotizacion_dryrun`.
3. Llama `POST /ecommercePublico/cotizacion_preflight`.
4. Abre `depurar.whatsapp.url`.
5. El cierre sigue por WhatsApp/POS/Pedidos.

`cotizacion_preflight` devuelve:

- `folio_preliminar`;
- `folio_no_persistido=true`;
- `listo_para_whatsapp`;
- `listo_para_registro_futuro`;
- `whatsapp.url`;
- snapshot recalculado por ERP.

Ese folio preliminar ayuda a llevar contexto en WhatsApp, pero no es una cotizacion real guardada.

## Registro futuro

Endpoint reservado:

```http
POST /ecommercePublico/cotizacion_registrar
```

Sigue bloqueado hasta autorizacion. Cuando se active debe:

- recalcular otra vez con `cotizacion_dryrun`;
- validar contacto y consentimiento con `cotizacion_preflight`;
- crear folio real `ECOM-YYYYMMDD-000001`;
- guardar encabezado en `erp_ecommerce_cotizaciones`;
- guardar snapshots de productos en `erp_ecommerce_cotizaciones_detalle`;
- guardar evento inicial en `erp_ecommerce_cotizaciones_eventos`;
- no apartar inventario;
- no crear pedido confirmado;
- no crear venta;
- no cobrar.

## Plan read-only validado

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
```

Resultado actual:

```text
preflight_ok=true
plan_persistencia_ok=true
folio_planeado=ECOM-YYYYMMDD-000001
tablas=true
endpoint_registro_permanece_bloqueado_por_politica_fase1
```

El plan incluye SQL de encabezado, detalle y evento, pero no lo ejecuta.

## Bandeja interna ERP futura

Antes de habilitar registro real conviene crear una bandeja interna para seguimiento:

- nuevas cotizaciones recibidas por WhatsApp;
- cotizaciones en seguimiento;
- cotizaciones convertidas manualmente a pedido o venta;
- cotizaciones descartadas;
- filtro por fecha, estatus, telefono, origen y producto;
- liga futura a cliente CRM si existe coincidencia por telefono;
- tarea CRM opcional, sin crear cliente automaticamente.

Endpoints internos read-only preparados:

```http
GET /ecommercePublico/cotizaciones_bandeja_erp
GET /ecommercePublico/cotizacion_detalle_erp/{folio}
POST /ecommercePublico/cotizacion_accion_plan_erp
```

Estos endpoints son protegidos por permiso interno `catalogo.ver` y no son para el frontend publico.

Pantalla interna read-only:

```text
http://panel.com.local/ecommercePublico/cotizaciones
```

La pantalla muestra KPIs, filtros basicos, listado y detalle snapshot. En el estado actual puede verse vacia porque `cotizacion_registrar` sigue bloqueado.

## Acciones futuras planificadas

`cotizacion_accion_plan_erp` acepta:

- `marcar_seguimiento`;
- `descartar`;
- `preparar_pedido_manual`;
- `preparar_venta_pos_manual`.

Todas responden en modo plan/read-only. El plan puede incluir estatus destino, evento planeado y SQL ilustrativo si la cotizacion existe, pero no ejecuta nada.

Reglas:

- `descartar`, `preparar_pedido_manual` y `preparar_venta_pos_manual` requieren motivo.
- conversion a pedido o venta siempre requiere revision humana;
- no hay conversion automatica desde frontend;
- no se aparta inventario desde ecommerce.

UAT:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizaciones_bandeja_readonly.php
```

Resultado esperado mientras no haya cotizaciones reales:

```text
ok=true
items_total_pagina=0
no_crea_pedido=true
no_descuenta_inventario=true
```

## Guardrails

- No usar `ecom_*`.
- No guardar precio del frontend como verdad.
- No descontar inventario.
- No crear pedido confirmado.
- No crear cliente CRM automaticamente.
- No publicar `cotizacion_registrar` sin rate limit/API key/HMAC o backend intermedio.
- No exponer secretos en navegador.
