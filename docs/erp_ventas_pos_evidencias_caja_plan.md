# Plan - Evidencias de caja POS

Fecha: 2026-06-30

## Objetivo

Controlar comprobantes de movimientos sensibles de caja: reembolsos a cliente, gastos de caja, vales y retiros. El POS no debe permitir que estos movimientos queden invisibles despues del cierre.

## Estado actual

- `erp_pos_movimientos_caja` ya soporta:
  - `requiere_evidencia`;
  - `evidencia_estado`;
  - `evidencia_ruta`;
  - `requiere_autorizacion`;
  - `autorizado_por`;
  - `fecha_autorizacion`.
- Reembolso POS real validado:
  - movimiento `20`;
  - referencia `DEV-20260630-000002`;
  - `requiere_evidencia=1`;
  - `evidencia_estado=pendiente`.
- Consulta read-only implementada:
  - `VentasErp::evidenciasCajaPendientesReadOnly`;
  - endpoint `/ventas/caja_evidencias_pendientes_erp`;
  - script `storage/uat/uat_ventas_pos_caja_evidencias_readonly.php`.

## Flujo operativo recomendado

1. Al registrar un movimiento sensible, el sistema marca `requiere_evidencia=1`.
2. Si el movimiento sale de caja, debe tener autorizacion o regla de supervisor.
3. El cierre de turno puede cerrar con evidencia pendiente, pero debe dejar alerta persistente.
4. La bandeja de caja muestra pendientes por tienda, caja, turno, tipo y estado.
5. El responsable adjunta comprobante:
   - foto de ticket firmado;
   - nota interna;
   - comprobante de transferencia;
   - recibo de gasto;
   - firma digital futura.
6. Un usuario autorizado revisa y cambia estado:
   - `pendiente`;
   - `recibida`;
   - `aprobada`;
   - `rechazada`.
7. Si se rechaza, debe capturarse motivo y quedar alerta hasta corregir.

## Reglas por tipo

- `reembolso_cliente`:
  - requiere devolucion/cancelacion ligada;
  - requiere ticket de devolucion;
  - requiere evidencia;
  - requiere autorizacion.
- `gasto_caja`:
  - requiere motivo;
  - debe capturar responsable o beneficiario cuando aplique;
  - requiere evidencia.
- `retiro_efectivo`:
  - requiere referencia/responsable;
  - puede requerir evidencia segun politica de tienda.
- `vale_interno`:
  - requiere responsable;
  - debe convertirse despues en gasto, reembolso o cancelacion.

## Datos que faltan para robustecer

- Tabla formal de adjuntos/evidencias de caja, recomendada:
  - `erp_pos_movimientos_caja_evidencias`;
  - id movimiento;
  - tipo evidencia;
  - ruta/archivo;
  - hash/tamano/mime;
  - notas;
  - creado_por;
  - revisado_por;
  - estatus;
  - motivo rechazo.
- Estados controlados en catalogo o constante de dominio.
- Endpoint de adjuntar evidencia con validacion de archivo.
- Endpoint de aprobar/rechazar evidencia con permiso puntual.
- Alertas operativas persistentes para pendientes vencidos.

## UAT read-only actual

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_caja_evidencias_readonly.php --id_turno_caja=9 --evidencia_estado=pendiente
```

Resultado:

- `ok=true`;
- `total_registros=1`;
- monto total `295`;
- movimiento `20`;
- tipo `reembolso`;
- referencia `DEV-20260630-000002`;
- venta `POS-20260630-000001`;
- turno `TUR-20260630-002-001`;
- tienda `Mascotas Mina 971`;
- estado `pendiente`.

## Siguiente autorizacion futura

Cuando se quiera permitir adjuntar comprobantes reales:

```text
AUTORIZO APLICAR DDL EVIDENCIAS CAJA POS usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_DDL para UAT POS
```

Despues:

```text
AUTORIZO ADJUNTAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_movimiento_caja=20 tipo_evidencia=ticket_firmado referencia=DEV-20260630-000002
```

## Aplicador UAT preparado

- Archivo:
  - `storage/uat/uat_ventas_pos_caja_evidencia_apply_authorized.php`.
- Metodo:
  - `VentasErp::registrarEvidenciaCajaPosReal`.
- Token requerido:
  - `VENTAS_POS_CAJA_EVIDENCIA_REAL`.
- Alcance:
  - inserta evidencia en `erp_pos_movimientos_caja_evidencias`;
  - cambia `erp_pos_movimientos_caja.evidencia_estado` a `recibida`;
  - no aprueba evidencia;
  - no modifica monto ni turno;
  - no sube archivo fisico en UAT si se usa referencia externa.

Autorizacion sugerida para el movimiento UAT `20`:

```text
AUTORIZO REGISTRAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario=1 id_movimiento_caja=20 tipo_evidencia=ticket_firmado referencia_externa=DEV-20260630-000002 descripcion="Comprobante UAT de reembolso de caja firmado por cliente"
```

## Registro UAT ejecutado

- Fecha: 2026-06-30
- Evidencia:
  - `id_evidencia_caja=1`;
  - `id_movimiento_caja=20`;
  - tipo `ticket_firmado`;
  - referencia externa `DEV-20260630-000002`;
  - estado `recibida`;
  - requiere revision posterior.
- Validacion posterior:
  - pendientes turno `9`: `0`;
  - recibidas turno `9`: `1`;
  - detalle evidencia `1`: `ok=true`, venta `POS-20260630-000001`, devolucion `DEV-20260630-000002`, monto `295`.

## Revision/aprobacion preparada

- Metodo:
  - `VentasErp::evidenciasCajaDetalleReadOnly`;
  - `VentasErp::revisarEvidenciaCajaPosReal`.
- Endpoints:
  - `/ventas/caja_evidencias_detalle_erp`;
  - `/ventas/caja_evidencia_revisar_erp`.
- Scripts UAT:
  - `storage/uat/uat_ventas_pos_caja_evidencias_detalle_readonly.php`;
  - `storage/uat/uat_ventas_pos_caja_evidencia_revision_apply_authorized.php`.
- Reglas:
  - solo revisa evidencias en estado `recibida`;
  - decision permitida: `aprobada` o `rechazada`;
  - rechazo exige motivo;
  - por defecto bloquea que el usuario que registro la evidencia sea quien la revise;
  - requiere permiso `ventas.autorizar_excepcion_comercial` mientras se define permiso fino de caja;
  - no mueve dinero;
  - no modifica inventario;
  - no cambia importes ni turno.
- Guardrail:
  - script sin parametros bloqueado correctamente;
  - no escribio BD.

Autorizacion sugerida para aprobar evidencia con usuario revisor distinto al creador:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=ID_REVISOR id_evidencia_caja=1 decision=aprobada
```

Si solo existe un usuario para UAT y se acepta excepcion de prueba controlada:

```text
AUTORIZO REVISAR EVIDENCIA REEMBOLSO CAJA POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL id_usuario=1 id_evidencia_caja=1 decision=aprobada permitir_mismo_usuario=1
```
