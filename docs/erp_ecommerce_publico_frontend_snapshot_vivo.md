# ERP Ecommerce publico - Snapshot vivo para frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-18  
Estado: entregable read-only para conectar el frontend externo con datos reales Fase 1.

## Objetivo

Dar al proyecto frontend una sola salida JSON con:

- base API correcta;
- origen CORS configurado;
- variables `.env` sugeridas;
- endpoints a consumir;
- productos reales publicados;
- filtros disponibles;
- ejemplo de producto detalle;
- ejemplo de disponibilidad publica;
- payload y respuesta resumida de `cotizacion_dryrun`;
- guardrails de Fase 1.

## Comando

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=2
```

## Compuerta recomendada

Antes del snapshot, usar esta compuerta compacta:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_entregable_gate_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus_preview=415,866,386,1138 --min_publicadas=2 --min_preview=6
```

Debe devolver `senal_entregable_frontend=verde_entregable_frontend`.

## Resultado esperado

```text
ok=true
senal_frontend=verde_datos_reales
base_api=http://panel.com.local/ecommercePublico
origin_frontend=http://artiani.com.local
cors.origin_permitido=true
cors.sin_wildcard=true
resumen.ready=true
resumen.ddl_pendiente=false
resumen.publicadas=2
resumen.dryrun_ok=true
bloqueos=[]
```

## Uso en el frontend

El frontend debe tomar el snapshot como referencia de integracion, no como fuente estatica. La fuente real sigue siendo el API:

```text
GET /ecommercePublico/estado
GET /ecommercePublico/configuracion
GET /ecommercePublico/filtros
GET /ecommercePublico/catalogo
GET /ecommercePublico/producto/{slug}
GET /ecommercePublico/disponibilidad?id_sku=...
POST /ecommercePublico/cotizacion_dryrun
```

## Preview de expansion

Para diseno visual con 6 tarjetas antes de publicar mas productos:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus=415,866,386,1138 --resumen=1
```

Este comando es read-only. Los items marcados como `preview_no_publicado=true` son candidatos reales del ERP, pero todavia no forman parte de `/ecommercePublico/catalogo`.

## Variables actuales

```env
VITE_ERP_API_BASE_URL=http://panel.com.local
VITE_ERP_ECOMMERCE_BASE_PATH=/ecommercePublico
VITE_ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12
```

## Reglas

- No usar `POST /ecommercePublico/cotizacion_registrar` en Fase 1.
- No mostrar stock exacto.
- No descontar inventario.
- No usar tablas internas ni legacy `ecom_*`.
- Antes de generar WhatsApp, llamar siempre `POST /ecommercePublico/cotizacion_dryrun`.
- El total y lineas finales deben salir del dry-run, no del carrito local.
