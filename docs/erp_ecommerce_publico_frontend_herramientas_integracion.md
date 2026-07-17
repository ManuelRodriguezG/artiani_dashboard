# ERP Ecommerce publico - Herramientas de integracion frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: apoyo read-only para iniciar y probar el proyecto ecommerce externo.

## Objetivo

Centralizar herramientas para que el frontend externo pruebe contratos sin leer BD, sin usar `ecom_*`, sin checkout y sin registrar cotizaciones reales.

## Paquete compacto

Antes de usar el paquete compacto, revisar salud local de XAMPP/MySQL/API:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_entorno_readonly.php --base=http://panel.com.local
```

Si ese comando reporta MySQL caido o API sin JSON, no interpretar errores posteriores como fallas del frontend.

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
```

Devuelve:

- base API correcta;
- endpoints publicos Fase 1;
- documentos fuente;
- scripts read-only;
- senal actual para frontend;
- bloqueos para pasar a datos reales.

## Variables de entorno

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://localhost:5173
```

Uso:

- copiar `.env.example` para Vite o Next;
- tomar proxy local sugerido para desarrollo;
- recordar que CORS debe configurarse con origen exacto;
- evitar `http://localhost/panel_de_control/ecommercePublico`.

## Coleccion Postman/Insomnia

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local
```

Uso:

- importar JSON en Postman o Insomnia;
- probar `contratos`, `estado`, `configuracion`, `filtros`, `catalogo`, `producto`, `disponibilidad` y `cotizacion_dryrun`;
- confirmar que `cotizacion_registrar` sigue bloqueado en Fase 1.

## OpenAPI

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_openapi_readonly.php
```

Uso:

- generar mocks o cliente de apoyo;
- documentar endpoints publicos.

El contrato fuente sigue siendo:

```http
GET http://panel.com.local/ecommercePublico/contratos
```

## Registro interno futuro

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://localhost:5173
```

Uso:

- revisar el payload futuro de `cotizacion_registrar`;
- confirmar que el endpoint sigue bloqueado en Fase 1;
- confirmar que el registro planeado no descuenta inventario, no crea pedido y no cobra;
- listar requisitos pendientes antes de desbloquear escrituras publicas.

## Fixtures

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

Uso:

- disenar tarjetas, filtros, detalle y carrito mientras el ERP esta en amarillo;
- probar estados de UI sin prometer datos reales.
- no validar salud de MySQL ni existencia de publicaciones reales.

Retirar fixtures cuando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

devuelva `ok=true`.

## Validacion minima antes de conectar UI real

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://localhost:5173
```

Estado esperado actual:

- shape `ok=true`;
- negativos `ok=true`;
- CORS cerrado hasta configurar `cors_origenes_permitidos`.
- smoke depende de que XAMPP/MySQL/API esten sanos.
