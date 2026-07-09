# ERP Garantias - Runbook de aplicacion

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: preparado; no ejecutar sin autorizacion.

## Objetivo

Aplicar de forma controlada el esquema base de Garantias ERP y sus permisos, sin tocar inventario, ventas reales ni reclamos productivos.

## Precondiciones obligatorias

1. Respaldo externo de la BD fuera de `C:\xampp\htdocs\panel`.
2. Confirmar ruta o referencia del respaldo.
3. Autorizar token:
   - `GARANTIAS_DDL_BASE`
4. Tener acceso con permiso:
   - `sistema.soporte`
5. Confirmar que no hay operacion activa usando Ventas/POS o Almacen durante la aplicacion.

## Alcance de DDL base

Tablas propuestas:

- `erp_garantias_politicas`
- `erp_garantias_politicas_reglas`
- `erp_ventas_detalle_garantias`
- `erp_garantias_reclamos`
- `erp_garantias_reclamos_eventos`
- `erp_garantias_adjuntos`
- `erp_garantias_proveedor_seguimiento`

No incluye:

- UI final de politicas.
- Reclamos reales.
- Movimientos de inventario.
- Integracion productiva con POS.
- Seguimiento real con proveedores.

## Secuencia recomendada

### 1. Auditoria previa

Endpoint:

- `GET /Garantias/esquema_auditar_garantias_erp`

Esperado antes de aplicar:

- `tipo=warning`
- 7 tablas faltantes.

### 2. Dry-run DDL

Endpoint:

- `POST /Garantias/esquema_actualizar_garantias_erp`

Parametros:

- `ejecutar=0`

Esperado:

- SQL generado sin ejecutar.
- Resumen con pendientes.
- 0 ejecutadas.
- 0 errores.

### 3. Aplicacion autorizada

Endpoint:

- `POST /Garantias/esquema_actualizar_garantias_erp`

Parametros:

- `ejecutar=1`
- `autorizar=GARANTIAS_DDL_BASE`
- `respaldo=RUTA_O_REFERENCIA_EXTERNA`

Reglas:

- Si falta token, no ejecutar.
- Si el respaldo apunta dentro del proyecto, no ejecutar.
- Si hay errores DDL, detener y documentar.

### 4. Auditoria posterior

Endpoint:

- `GET /Garantias/esquema_auditar_garantias_erp`

Esperado:

- `tipo=success`
- 0 pendientes.

### 5. Aplicar permisos

Los permisos ya estan declarados en `SeguridadEsquema.php`, pero no quedan activos hasta ejecutar el flujo de esquema/semillas de seguridad correspondiente.

Permisos esperados:

- `garantias.ver`
- `garantias.politicas`
- `garantias.reclamos.crear`
- `garantias.reclamos.resolver`
- `garantias.autorizar`
- `garantias.adjuntos`
- `garantias.reportes`

No aplicar permisos manualmente en SQL suelto si existe endpoint/flujo formal de Seguridad.

### 6. Validacion post-permisos

Validar con usuario administrador ERP:

- `GET /Garantias/resolver_sku_erp?id_sku_erp=ID`
- `POST /Garantias/venta_snapshot_dryrun_erp`
- `POST /Garantias/reclamo_dryrun_erp`

Esperado:

- Ya no debe bloquear por `esquema_pendiente`.
- Puede devolver `politica_no_configurada` si no hay politicas creadas, lo cual es correcto.

## Rollback conceptual

Si falla aplicacion DDL:

1. No usar el modulo Garantias.
2. Conservar evidencia del error.
3. Restaurar respaldo externo solo si el error dejo la BD inconsistente.
4. Reintentar solo despues de corregir DDL y documentar causa.

No borrar tablas manualmente sin revisar dependencias.

## Evidencia minima a guardar

- Ruta/referencia del respaldo externo.
- Salida de auditoria previa.
- Salida de dry-run DDL.
- Salida de aplicacion DDL.
- Salida de auditoria posterior.
- Validacion de permisos.

## Criterio de cierre

El esquema queda listo cuando:

- Auditoria de Garantias no tiene pendientes.
- Permisos existen y estan asignados a roles definidos.
- Resolver SKU responde sin `esquema_pendiente`.
- Snapshot dry-run responde sin bloqueo de esquema.
- Reclamo dry-run responde sin bloqueo de esquema.
