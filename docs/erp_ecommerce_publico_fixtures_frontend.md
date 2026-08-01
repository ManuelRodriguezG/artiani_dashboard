# ERP Ecommerce publico - Fixtures frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-01  
Estado: fixtures para UI; no son datos reales ni activan ecommerce.

## Objetivo

Permitir que el proyecto frontend externo avance en modo mock cuando el API real no este disponible en el entorno local.

Nota 2026-08-01: el `green_gate` ya esta en verde con datos reales. Estos fixtures quedan como herramienta de desarrollo visual, pruebas unitarias o fallback local, no como flujo principal.

Estos fixtures sirven para:

- disenar catalogo;
- disenar bootstrap/home;
- probar navegacion publica y secciones;
- probar sugerencias de busqueda;
- probar filtros por mascota y necesidad;
- construir ficha de producto;
- construir estados vacios de catalogo;
- construir carrito local;
- probar `cotizacion_dryrun` en UI;
- armar mensaje WhatsApp.

No sirven para:

- vender;
- publicar productos reales;
- validar precios reales;
- descontar inventario;
- registrar cotizaciones;
- reemplazar respuestas reales del ERP cuando el green gate este en verde.

## Comando

Desde `C:\xampp\htdocs\panel_de_control`:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_fixtures_readonly.php
```

El script no consulta BD y no escribe nada.

Por eso no debe usarse como diagnostico del ERP real. Para validar si el host `http://panel.com.local` y MySQL estan sanos, usar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_entorno_readonly.php --base=http://panel.com.local
```

## Secciones del JSON

- `estado`
- `bootstrap`
- `configuracion`
- `seo`
- `filtros`
- `busqueda_sugerencias`
- `navegacion`
- `secciones`
- `canales_estado`
- `catalogo`
- `catalogo_sin_resultados`
- `producto`
- `disponibilidad`
- `cotizacion_dryrun`

Cada seccion imita el wrapper real:

```json
{
  "error": false,
  "tipo": "success",
  "mensaje": "...",
  "api": {
    "version": "fase1-2026-07-12"
  },
  "depurar": {}
}
```

## Regla para el frontend

Usar fixtures solo cuando:

```text
senal_frontend=amarillo_mock_contratos
```

Cambiar a API real solo cuando:

```text
uat_ecommerce_publico_green_gate_readonly.php -> ok=true
```

## Guardrails

El frontend debe preferir API real cuando `green_gate.ok=true`. Si usa fixtures, debe consumirlos con la misma forma de contrato robusto:

- `GET /bootstrap`
- `GET /estado`
- `GET /configuracion`
- `GET /seo`
- `GET /filtros`
- `GET /busqueda_sugerencias`
- `GET /navegacion`
- `GET /secciones`
- `GET /catalogo`
- `GET /producto/{slug}`
- `GET /disponibilidad`
- `POST /cotizacion_dryrun`

Reglas:

- No usar fixtures para vender.
- No tratar precios fixture como reales.
- No mezclar fixtures con cotizaciones reales.
- No mostrar stock exacto.
- Antes de WhatsApp, el flujo real debe llamar `POST /cotizacion_dryrun`.
