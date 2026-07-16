# ERP Ecommerce publico - Fixtures frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: fixtures para UI; no son datos reales ni activan ecommerce.

## Objetivo

Permitir que el proyecto frontend externo avance en modo `amarillo_mock_contratos` aunque `/catalogo` real siga vacio.

Estos fixtures sirven para:

- disenar catalogo;
- probar filtros por mascota y necesidad;
- construir ficha de producto;
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

## Secciones del JSON

- `estado`
- `configuracion`
- `seo`
- `filtros`
- `catalogo`
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

## Guardrail

No crear endpoint `/bootstrap`. El frontend debe consumir contratos separados:

- `GET /estado`
- `GET /configuracion`
- `GET /seo`
- `GET /filtros`
- `GET /catalogo`
- `GET /producto/{slug}`
- `GET /disponibilidad`
- `POST /cotizacion_dryrun`
