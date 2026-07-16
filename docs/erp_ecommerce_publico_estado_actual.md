# ERP Ecommerce publico - Estado actual Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: contratos y activacion preparada; datos reales aun no activados.

## Senal actual

```text
senal_frontend=amarillo_mock_contratos
puede_iniciar_frontend_mock=true
puede_integrar_datos_reales=false
```

Esto significa:

- el frontend externo puede iniciar como maqueta tecnica/cliente API;
- aun no debe prometer catalogo vivo real;
- aun no hay publicaciones reales;
- aun falta DDL, configuracion WhatsApp/CORS y publicar SKUs.

Tambien puede verse desde la consola interna del ERP:

```text
http://panel.com.local/ecommercePublico/publicaciones
```

La consola muestra el semaforo del frontend, la base API recomendada, bloqueos para datos reales y siguientes pasos. Es una vista interna protegida; no es la tienda publica.

Tambien muestra comandos operativos separados en dos grupos:

- comandos `read-only` para validar readiness, bundle y secuencia sugerida;
- compuerta verde final `uat_ecommerce_publico_green_gate_readonly.php`, que exige item real en catalogo y dry-run con publicacion real;
- comandos `apply autorizado`, solo para usarse con respaldo externo y autorizacion explicita.

## Host verificado

El host correcto para endpoints publicos es:

```text
http://panel.com.local/ecommercePublico
```

No usar como base en este entorno:

```text
http://localhost/panel_de_control/ecommercePublico
```

Esa ruta puede resolver a login u otra configuracion de Apache.

## Endpoints publicos preparados

- `GET /ecommercePublico/contratos`
- `GET /ecommercePublico/estado`
- `GET /ecommercePublico/configuracion`
- `GET /ecommercePublico/seo`
- `GET /ecommercePublico/filtros`
- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`
- `GET /ecommercePublico/disponibilidad`
- `POST /ecommercePublico/cotizacion_dryrun`

Bloqueado:

- `POST /ecommercePublico/cotizacion_registrar`

## Tablas planificadas

DDL Fase 1 propone 5 tablas:

- `erp_ecommerce_publicaciones`
- `erp_ecommerce_configuracion`
- `erp_ecommerce_cotizaciones`
- `erp_ecommerce_cotizaciones_detalle`
- `erp_ecommerce_cotizaciones_eventos`

Estado actual:

```text
tablas_faltantes=5
ddl_pendiente=true
```

## Scripts read-only principales

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_readiness_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://localhost:5173
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://localhost:5173
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_openapi_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_carrito_whatsapp_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_activacion_bundle_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --lote=8
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_secuencia_activacion_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --id_sku=ID_SKU
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

Todos estos son read-only.

El paquete mas util para abrir el nuevo proyecto frontend es:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
```

Ese comando concentra endpoints, documentos, scripts, senal actual y bloqueos para pasar de mocks a datos reales.

Herramientas nuevas para iniciar el otro proyecto:

- `uat_ecommerce_publico_frontend_env_readonly.php`: variables `.env` y proxy local sugerido.
- `uat_ecommerce_publico_postman_collection_readonly.php`: coleccion Postman/Insomnia para probar contratos.
- `docs/erp_ecommerce_publico_frontend_herramientas_integracion.md`: indice de herramientas para el frontend.

## Scripts apply autorizados

No ejecutar sin respaldo externo y autorizacion explicita.

DDL:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=RUTA_O_REFERENCIA
```

Configuracion:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND
```

Crear borrador:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=RUTA_O_REFERENCIA --id_sku=ID_SKU
```

Publicar borrador:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicar_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR --respaldo=RUTA_O_REFERENCIA --id_sku=ID_SKU --confirmar_revision=1
```

Si el SKU esta agotado y se decide publicarlo:

```bash
--confirmar_agotado=1
```

## Candados implementados

- No se usa `ecom_*` como fuente nueva.
- No se publica automaticamente todo SKU activo.
- No se descuenta inventario desde ecommerce.
- No se muestra stock exacto.
- El carrito `dry-run` no escribe BD.
- El registro real de cotizacion sigue bloqueado.
- Las publicaciones reales requieren DDL, token, respaldo y permiso.
- Publicar un borrador exige revision.
- Publicar agotados exige confirmacion adicional.
- CORS queda cerrado hasta configurar origenes exactos.

## Senal verde esperada

Solo avisar al proyecto frontend cuando:

```text
senal_frontend=verde_datos_reales
puede_integrar_datos_reales=true
```

Ese estado requiere:

- DDL aplicado;
- WhatsApp configurado;
- CORS configurado;
- al menos una publicacion activa;
- `/catalogo` devolviendo productos reales;
- `cotizacion_dryrun` respondiendo con publicaciones reales.

Mensaje a enviar cuando ocurra:

```text
Ya puedes iniciar/integrar la vista del ecommerce externo con datos reales.
El ERP tiene DDL aplicado, CORS configurado, WhatsApp configurado y primeras publicaciones activas.
Usa docs/erp_ecommerce_publico_frontend_handoff.md y docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt.
```
