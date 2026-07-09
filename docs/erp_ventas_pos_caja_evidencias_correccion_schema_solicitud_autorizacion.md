# Solicitud autorizacion - DDL correcciones evidencias caja POS

Fecha: 2026-06-30

## Objetivo

Crear una estructura formal para correcciones de evidencias de caja ya aprobadas.

La correccion no debe editar ni borrar la evidencia historica. Debe abrir un folio separado con motivo, estado, snapshot y resolucion.

## Tabla propuesta

- `erp_pos_movimientos_caja_evidencias_correcciones`

Campos principales:

- `id_correccion_evidencia_caja`
- `folio`
- `id_evidencia_caja`
- `id_movimiento_caja`
- `estatus`
- `tipo_correccion`
- `motivo`
- `evidencia_estado_anterior`
- `datos_snapshot`
- `solicitado_por`
- `fecha_solicitud`
- `resuelto_por`
- `fecha_resolucion`
- `decision`
- `motivo_resolucion`
- `id_evidencia_caja_nueva`

## Reglas de negocio

- Una evidencia aprobada no se edita directamente.
- Una correccion debe tener folio unico.
- La solicitud exige motivo obligatorio.
- Resolver exige decision y motivo de resolucion.
- Si se reemplaza evidencia, la nueva evidencia debe quedar ligada a la correccion.
- No debe mover dinero.
- No debe mover inventario.
- No debe modificar importes ni turno.

## Evidencia read-only

Script:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_correccion_schema_readonly.php
```

Resultado:

- `erp_pos_movimientos_caja_evidencias` existe;
- `erp_pos_movimientos_caja_evidencias_correcciones` no existe;
- faltan columnas e indices de correccion;
- plan genera `CREATE TABLE` sin ejecutar;
- no escribio BD.

## Guardrail

Script autorizado sin parametros:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_correccion_schema_apply_authorized.php
```

Resultado:

- `ok=false`;
- modo `bloqueado`;
- no aplico DDL.

## Autorizacion requerida

```text
AUTORIZO APLICAR DDL CORRECCIONES EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL para UAT POS
```

## Impacto esperado

- Crea tabla de correcciones.
- No modifica evidencias existentes.
- No modifica movimientos de caja.
- No mueve dinero.
- No mueve inventario.
- No toca ecommerce.
