# ERP Ecommerce publico - Handoff para frontend externo

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: guia de integracion read-only para proyecto ecommerce separado.

## Decision

El ecommerce publico se construira como proyecto separado. Este ERP solo expone contratos/API y administra la publicacion de productos, configuracion y futuras cotizaciones.

## Variables sugeridas en el proyecto ecommerce

```env
ERP_API_BASE_URL=http://panel.com.local
ERP_ECOMMERCE_BASE_PATH=/ecommercePublico
ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12
ERP_ECOMMERCE_API_KEY=
ERP_ECOMMERCE_API_SECRET=
```

Notas:

- `ERP_ECOMMERCE_API_KEY` y `ERP_ECOMMERCE_API_SECRET` no se usan todavia en Fase 1 read-only.
- No guardar secretos en frontend publico. Si se activa HMAC, la firma debe hacerse desde backend/server del ecommerce.
- Host local verificado para este ERP: `http://panel.com.local`.
- No usar `http://localhost/panel_de_control` en este entorno.

## Documentos de apoyo

- `docs/erp_ecommerce_publico_prompt_inicio_frontend.txt`
- `docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt`
- `docs/erp_ecommerce_publico_cliente_api_frontend.md`
- `docs/erp_ecommerce_publico_frontend_contract_tests.md`
- `docs/erp_ecommerce_publico_frontend_estados_ui.md`
- `docs/erp_ecommerce_publico_fixtures_frontend.md`
- `docs/erp_ecommerce_publico_carrito_whatsapp_frontend.md`
- `docs/erp_ecommerce_publico_frontend_herramientas_integracion.md`
- `docs/erp_ecommerce_publico_seguridad_api_futura.md`
- `docs/erp_ecommerce_publico_seo_frontend.md`

## Paquete compacto

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
```

Uso:

- confirma la base API correcta;
- lista endpoints publicos Fase 1;
- lista documentos y scripts que debe recibir el proyecto frontend;
- reporta `senal_frontend_actual`;
- reporta bloqueos para pasar a datos reales.

## Fixtures mientras el ERP esta en amarillo

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

Uso:

- construir UI sin datos reales;
- probar tarjetas, filtros, ficha y carrito;
- validar forma de `cotizacion_dryrun`.

No usar fixtures como productos reales. Cambiar a API real solo cuando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

devuelva `ok=true`.

## Herramientas para el frontend

Variables de entorno/proxy local:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://localhost:5173
```

Coleccion Postman/Insomnia:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local
```

## Orden recomendado de consumo

1. `GET /ecommercePublico/estado`
2. `GET /ecommercePublico/contratos`
3. `GET /ecommercePublico/configuracion`
4. `GET /ecommercePublico/seo`
5. `GET /ecommercePublico/filtros`
6. `GET /ecommercePublico/catalogo`
7. `GET /ecommercePublico/producto/{slug}`
8. `GET /ecommercePublico/disponibilidad?id_sku=...`
9. `POST /ecommercePublico/cotizacion_dryrun`

No usar `POST /ecommercePublico/cotizacion_registrar` hasta que el ERP lo reporte como desbloqueado en una fase posterior.

## Readiness

Request:

```http
GET {ERP_API_BASE_URL}/ecommercePublico/estado
```

Campos importantes:

- `depurar.ready`
- `depurar.schema.ddl_pendiente`
- `depurar.publicaciones.total_publicadas`
- `depurar.publicaciones.skus_publicables_fase_1`
- `depurar.seguridad.post_dryrun_disponible`
- `depurar.seguridad.post_registro_bloqueado`

Comportamiento recomendado:

- Si `ready=false`, la web puede mostrar estado de preparacion o consumir catalogo vacio.
- Si `total_publicadas=0`, mostrar catalogo en preparacion.
- Si `ddl_pendiente=true`, no intentar registrar cotizaciones reales.

## Contract tests ERP

Antes de conectar el frontend contra API real, validar desde el ERP:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://localhost:5173
```

Esperado actual:

- smoke HTTP `ok=true`;
- shape `ok=true`;
- CORS cerrado hasta configurar `cors_origenes_permitidos`.

## Catalogo

Request:

```http
GET {ERP_API_BASE_URL}/ecommercePublico/catalogo?q=alimento&mascota=perro&necesidad=alimento&pagina=1&limite=24
```

Respuesta esperada:

```json
{
  "error": false,
  "tipo": "success",
  "api": {"version": "fase1-2026-07-12"},
  "depurar": {
    "configurado": true,
    "items": [],
    "paginacion": {"pagina": 1, "limite": 24, "total": 0}
  }
}
```

Si aun no hay esquema/publicaciones:

```json
{
  "error": false,
  "tipo": "info",
  "depurar": {
    "configurado": false,
    "items": []
  }
}
```

## Item de catalogo

Campos principales:

```json
{
  "id_publicacion": 1,
  "id_producto_erp": 10,
  "id_sku": 20,
  "slug": "producto-publico",
  "sku": "SKU-001",
  "nombre": "Producto",
  "marca": "Marca",
  "categoria": "Categoria",
  "presentacion": "Bolsa 2 kg",
  "imagen": "/media/...",
  "precio": 295,
  "moneda": "MXN",
  "disponibilidad": "disponible",
  "mascota_especie": "perro",
  "necesidades": ["alimento"],
  "permite_cotizacion": true,
  "permite_whatsapp": true
}
```

Disponibilidad permitida:

- `disponible`
- `pocas_piezas`
- `consultar_disponibilidad`
- `agotado`

Nunca mostrar stock exacto.

## Configuracion

Request:

```http
GET {ERP_API_BASE_URL}/ecommercePublico/configuracion
```

Claves publicas:

- `moneda_default`
- `whatsapp_numero_principal`
- `whatsapp_mensaje_base`
- `cors_origenes_permitidos`
- `cotizacion_habilitada`
- `mostrar_stock_exacto`
- `modo_sin_stock`
- `texto_total_estimado`
- `url_sitio_publico`

Regla:

- Si `whatsapp_numero_principal` viene vacio, no hardcodear numero en frontend. Mostrar accion de cotizacion como pendiente de configuracion o usar canal controlado por backend.

## Cotizacion dry-run

Request:

```http
POST {ERP_API_BASE_URL}/ecommercePublico/cotizacion_dryrun
Content-Type: application/json
```

Body:

```json
{
  "items": [
    {"id_publicacion": 1, "cantidad": 2},
    {"slug": "producto-publico", "cantidad": 1}
  ],
  "contacto": {
    "nombre": "Cliente",
    "telefono": "5555555555",
    "mensaje": "Quiero confirmar disponibilidad"
  },
  "utm": {
    "source": "web"
  }
}
```

Reglas:

- No enviar precio como verdad.
- El ERP recalcula precio.
- El ERP devuelve total estimado.
- No descuenta inventario.
- No guarda cotizacion.

## Registro real de cotizacion

Endpoint reservado:

```http
POST {ERP_API_BASE_URL}/ecommercePublico/cotizacion_registrar
```

En Fase 1 responde bloqueado. No usar para produccion hasta que:

- exista DDL aplicado;
- API key/firma este activa;
- rate limit este definido;
- se configure WhatsApp;
- se defina seguimiento CRM/POS.

## CORS y autenticacion

Fase 1:

- CORS cerrado por defecto.
- Solo se abre si el ERP configura `cors_origenes_permitidos`.
- API key/HMAC documentado pero no requerido.

Produccion:

- No usar `*`.
- Si se activa HMAC, firmar desde backend del ecommerce.
- No exponer secretos en navegador.

## UAT para validar integracion

Desde el ERP:

```bash
php storage/uat/uat_ecommerce_publico_api_contracts_readonly.php
```

Resultado esperado actual:

- `ok=true`
- `modo=read-only`
- `endpoints_total=9`
- `ready=false`
- `ddl_pendiente=true`
- `registro_cotizacion_bloqueado=true`
- `guardado_publicacion_bloqueado=true`

## No hacer en el frontend externo

- No leer tablas internas.
- No consumir `ecom_*`.
- No asumir stock exacto.
- No mandar precio como fuente de verdad.
- No guardar secretos HMAC en navegador.
- No implementar checkout/pasarela en Fase 1.
- No marcar venta pagada ni pedido confirmado desde la web.
