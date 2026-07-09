# ERP Garantias - Solicitud de autorizacion

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: pendiente de autorizacion del dueno del proyecto.

## Objetivo

Autorizar la aplicacion controlada del esquema base y permisos del modulo Garantias ERP.

## Alcance autorizado solicitado

Aplicar DDL base para crear:

- `erp_garantias_politicas`
- `erp_garantias_politicas_reglas`
- `erp_ventas_detalle_garantias`
- `erp_garantias_reclamos`
- `erp_garantias_reclamos_eventos`
- `erp_garantias_adjuntos`
- `erp_garantias_proveedor_seguimiento`

Aplicar permisos declarados en `SeguridadEsquema.php`:

- `garantias.ver`
- `garantias.politicas`
- `garantias.reclamos.crear`
- `garantias.reclamos.resolver`
- `garantias.autorizar`
- `garantias.adjuntos`
- `garantias.reportes`

## Fuera de alcance

Esta autorizacion no incluye:

- Crear reclamos reales.
- Confirmar ventas.
- Mover inventario.
- Crear devoluciones.
- Integrar UI final de Catalogo/Ventas/Almacen.
- Insertar politicas iniciales sin revision.
- Modificar tablas legacy de ecommerce.

## Precondiciones

- Respaldo externo de BD fuera de `C:\xampp\htdocs\panel`.
- Confirmar referencia/ruta del respaldo.
- Validar auditoria previa:
  - `GET /Garantias/esquema_auditar_garantias_erp`
- Validar dry-run:
  - `POST /Garantias/esquema_actualizar_garantias_erp` con `ejecutar=0`.

## Token requerido

Para aplicar DDL base:

```text
GARANTIAS_DDL_BASE
```

## Parametros esperados para ejecucion

```text
ejecutar=1
autorizar=GARANTIAS_DDL_BASE
respaldo=RUTA_O_REFERENCIA_EXTERNA
```

## Evidencia que debe guardarse

- Auditoria previa.
- Dry-run DDL.
- Respaldo externo.
- Resultado de aplicacion DDL.
- Auditoria posterior.
- Resultado de permisos/semillas.
- Pruebas dry-run posteriores:
  - resolver SKU;
  - snapshot de venta;
  - reclamo dry-run.

## Criterio de exito

- Auditoria de Garantias sin pendientes.
- Permisos disponibles para roles definidos.
- Resolver SKU ya no bloquea por `esquema_pendiente`.
- Snapshot dry-run ya no bloquea por esquema.
- Reclamo dry-run ya no bloquea por esquema.

## Criterio de no ejecucion

No ejecutar si:

- no hay respaldo externo;
- el respaldo esta dentro del proyecto;
- falta token exacto;
- hay dudas sobre el alcance;
- hay operacion activa critica en Ventas/POS o Almacen;
- la auditoria previa muestra conflictos distintos a tablas faltantes esperadas.
