# Solicitud de autorizacion - DDL evidencias de caja POS

Fecha: 2026-06-30

## Proposito

Crear la estructura formal para adjuntar y revisar comprobantes de movimientos sensibles de caja POS, especialmente reembolsos a cliente como `DEV-20260630-000002`.

## Estado read-only

Auditoria ejecutada:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_schema_readonly.php
```

Resultado:

- `erp_pos_movimientos_caja` existe;
- `erp_pos_movimientos_caja_evidencias` no existe;
- faltan columnas de evidencia/archivo/revision;
- falta indice `idx_pos_movimiento_evidencia`.

## DDL propuesto

Crear tabla:

- `erp_pos_movimientos_caja_evidencias`

Campos principales:

- `id_evidencia_caja`;
- `id_movimiento_caja`;
- `tipo_evidencia`;
- `estatus`;
- `titulo`;
- `descripcion`;
- `archivo_ruta`;
- `archivo_nombre`;
- `archivo_mime`;
- `archivo_tamano`;
- `archivo_hash`;
- `referencia_externa`;
- `datos_snapshot`;
- `creado_por`;
- `fecha_registro`;
- `revisado_por`;
- `fecha_revision`;
- `motivo_rechazo`;
- `fecha_actualizacion`.

Indices:

- `idx_caja_evidencia_movimiento`;
- `idx_caja_evidencia_estado`;
- `idx_caja_evidencia_hash`;
- `idx_pos_movimiento_evidencia` en `erp_pos_movimientos_caja`.

## Archivos preparados

- `VentasErpEsquema::planActualizarEvidenciasCajaPos`;
- `VentasErpEsquema::auditarEvidenciasCajaPos`;
- endpoint `/ventas/esquema_auditar_evidencias_caja_pos`;
- endpoint `/ventas/esquema_actualizar_evidencias_caja_pos`;
- script read-only `storage/uat/uat_ventas_pos_caja_evidencias_schema_readonly.php`;
- aplicador protegido `storage/uat/uat_ventas_pos_caja_evidencias_schema_apply_authorized.php`.

## Reglas

- No adjunta archivos.
- No aprueba evidencias.
- No modifica movimientos de caja existentes.
- No mueve dinero.
- No mueve inventario.
- Requiere respaldo externo y token.

## Autorizacion requerida

```text
AUTORIZO APLICAR DDL EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_DDL para UAT POS
```

## Comando equivalente

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_schema_apply_authorized.php --autorizar=VENTAS_POS_CAJA_EVIDENCIAS_DDL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql
```

## Validacion posterior esperada

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_schema_readonly.php
```

Resultado esperado:

- tabla `erp_pos_movimientos_caja_evidencias` existente;
- indices existentes;
- plan posterior con mensajes de tabla/indice ya existente;
- sin adjuntos reales aun.
