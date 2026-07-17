# ERP Ecommerce publico - Archivos iniciales frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: plantilla para crear el proyecto frontend separado; no pertenece al ERP.

## Estructura sugerida

```text
frontend-ecommerce-mascotas/
  AGENTS.md
  .env.example
  package.json
  src/
    api/
      erpEcommerceClient.ts
      erpEcommerceTypes.ts
    fixtures/
      ecommerceFixtures.ts
    state/
      cartStore.ts
    pages/
      CatalogPage.tsx
      ProductPage.tsx
      QuotePage.tsx
    components/
      ProductCard.tsx
      ProductFilters.tsx
      AvailabilityBadge.tsx
      CartDrawer.tsx
    tests/
      ecommerceContract.test.ts
```

## AGENTS.md

Usar como base:

```text
docs/erp_ecommerce_publico_frontend_AGENTS_template.md
```

## .env.example

```env
VITE_ERP_API_BASE_URL=http://panel.com.local
VITE_ERP_ECOMMERCE_BASE_PATH=/ecommercePublico
VITE_ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12
```

No agregar secretos en variables `VITE_*`.

## Cliente API minimo

El cliente debe construir URLs con:

```text
{VITE_ERP_API_BASE_URL}{VITE_ERP_ECOMMERCE_BASE_PATH}
```

Endpoints obligatorios:

- `getEstado()`
- `getContratos()`
- `getConfiguracion()`
- `getSeo()`
- `getFiltros()`
- `getCatalogo(params)`
- `getProducto(slug)`
- `getDisponibilidad(params)`
- `cotizacionDryRun(payload)`

No implementar `cotizacionRegistrar()` salvo que devuelva error controlado de fase futura.

## Fixtures

Generar fixtures desde ERP:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

Usarlos solo mientras:

```text
senal_frontend_actual=amarillo_mock_contratos
```

## Estados UI obligatorios

- cargando;
- API no disponible;
- catalogo en preparacion;
- sin resultados;
- producto no disponible;
- carrito vacio;
- dry-run con bloqueos;
- WhatsApp no configurado;
- datos reales habilitados.

## Contract tests minimos

Antes de consumir datos reales, el frontend debe validar:

- wrapper `{ error, tipo, mensaje, api, depurar }`;
- `api.version === "fase1-2026-07-12"`;
- catalogo contiene `items` y `paginacion`;
- productos tienen disponibilidad en lista permitida;
- dry-run indica `no_escribe_bd=true`;
- `cotizacion_registrar` no se usa en Fase 1.

## Corte hacia datos reales

No cambiar de fixtures a API real hasta que el ERP devuelva:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

con:

```text
ok=true
```
