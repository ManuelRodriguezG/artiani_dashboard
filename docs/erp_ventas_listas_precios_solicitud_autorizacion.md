# ERP Ventas/Listas de precios - solicitud de autorizacion

Fecha: 2026-07-12  
Estado: propuesta para autorizacion; no ejecutada.

## Objetivo

Autorizar la preparacion minima de BD para que el modulo ERP > Ventas/Listas de precios pueda guardar listas, detalles y asignaciones CRM con trazabilidad, sin afectar ventas pasadas.

## Alcance autorizado propuesto

1. Agregar contrato CRM canonico a `erp_clientes_listas_precios`:
   - columna `id_cliente_crm`;
   - permitir `id_cliente` nullable como compatibilidad temporal;
   - indice `idx_cliente_lista_cliente_crm`.
2. Crear auditoria comercial:
   - tabla `erp_listas_precios_eventos`.
3. Sembrar permisos finos:
   - `ventas.listas.ver`;
   - `ventas.listas.crear`;
   - `ventas.listas.editar`;
   - `ventas.listas.activar`;
   - `ventas.listas.pausar`;
   - `ventas.listas.cancelar`;
   - `ventas.listas.asignar_cliente`;
   - `ventas.listas.auditoria`.

## Fuera de alcance

- No crear promociones.
- No activar ecommerce.
- No recalcular ventas pasadas.
- No cambiar snapshots de `erp_ventas_detalle`.
- No modificar inventario.
- No convertir listas POS internas en listas ecommerce.
- No crear venta real desde esta autorizacion.

## Preflight obligatorio

Ejecutar antes de cualquier autorizacion:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_preflight_readonly.php
```

Debe cumplir:

- `ok=true`;
- `permisos.ventas_listas_total=8`;
- `schema.sql_eventos_total=1`;
- sin bloqueos;
- avisos permitidos si indican que falta aplicar CRM/listas o auditoria.

## Orden de ejecucion recomendado

1. Generar respaldo externo de esquema fuera del proyecto justo antes de aplicar DDL.
2. Aplicar DDL CRM/listas y auditoria.

Opcion CLI autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_schema_apply_authorized.php --autorizar_crm=VENTAS_LISTAS_PRECIOS_CRM_DDL --autorizar_auditoria=VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL --generar_respaldo=1
```

El respaldo se genera fuera del proyecto, por defecto en `Documents\RespaldosBD\panel`. Se puede indicar otro directorio externo con `--directorio_respaldo=RUTA_EXTERNA`.

Opcion endpoints:

CRM/listas:

```text
POST /ventas/esquema_actualizar_listas_precios_crm
ejecutar=1
autorizar=VENTAS_LISTAS_PRECIOS_CRM_DDL
respaldo=REFERENCIA_RESPALDO
```

Auditoria:

```text
POST /ventas/esquema_actualizar_auditoria_listas_precios
ejecutar=1
autorizar=VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL
respaldo=REFERENCIA_RESPALDO
```

3. Aplicar permisos `ventas.listas.*`.

Preflight de permisos:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_permisos_readonly.php
```

Aplicacion autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_permisos_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_PERMISOS --id_usuario=ID_USUARIO_AUTORIZA
```

Esta siembra no requiere respaldo externo porque no modifica esquema; queda auditada como cambio de seguridad.

4. Ejecutar nuevamente preflight.
5. Probar guardado UAT con lista nueva en `borrador`.

Preflight de guardado en borrador:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_guardado_borrador_readonly.php
```

Aplicacion CLI autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_guardado_borrador_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT --id_usuario=ID_USUARIO_AUTORIZA
```

Este guardado no requiere respaldo externo porque es CRUD normal. Si falta `erp_listas_precios_eventos` o el permiso `ventas.listas.crear`, el script se bloquea antes de escribir.

6. Probar detalle UAT para SKU `1760`.

Preflight de detalle:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_detalle_sku_readonly.php --codigo_lista=LP-UAT-BORRADOR-01 --id_sku=1760 --precio=315.00
```

Aplicacion CLI autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_detalle_sku_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT --id_usuario=ID_USUARIO_AUTORIZA --codigo_lista=LP-UAT-BORRADOR-01 --id_sku=1760 --precio=315.00
```

Este guardado tampoco requiere respaldo externo; requiere auditoria comercial, permiso `ventas.listas.editar`, token UAT y lista existente.

7. Probar asignacion UAT de cliente CRM.

Preflight de asignacion:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_asignacion_cliente_readonly.php --codigo_lista=LP-UAT-BORRADOR-01 --id_cliente_crm=1
```

Aplicacion CLI autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_asignacion_cliente_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT --id_usuario=ID_USUARIO_AUTORIZA --codigo_lista=LP-UAT-BORRADOR-01 --id_cliente_crm=1
```

Este guardado requiere contrato CRM/listas aplicado (`id_cliente_crm`), auditoria comercial, permiso `ventas.listas.asignar_cliente`, token UAT y lista existente.

8. Activar lista UAT solo despues de tener detalle activo.

Preflight de activacion:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_activar_lista_readonly.php --codigo_lista=LP-UAT-BORRADOR-01
```

Aplicacion CLI autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_activar_lista_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT --id_usuario=ID_USUARIO_AUTORIZA --codigo_lista=LP-UAT-BORRADOR-01
```

Activar lista requiere auditoria comercial, permisos `ventas.listas.editar` y `ventas.listas.activar`, token UAT, lista existente y al menos un detalle activo.

9. Validar resolutor POS post-UAT sin venta real.

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_resolutor_post_uat_readonly.php --codigo_lista=LP-UAT-BORRADOR-01 --id_sku=1760 --id_cliente_crm=1 --precio_esperado=315.00
```

Debe confirmar que con cliente CRM gana `lista_cliente`, devuelve `id_lista_precio`, `lista_precio_snapshot` y precio esperado. No crea venta ni snapshot real.

Preflight maestro read-only:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_flujo_completo_readonly.php
```

Resume DDL, permisos, lista, detalle, asignacion, activacion y resolutor sin escribir BD.

10. Validar snapshot despues de venta POS UAT.

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_snapshot_venta_readonly.php --folio=FOLIO_VENTA_UAT --codigo_lista=LP-UAT-BORRADOR-01 --id_sku=1760 --precio_esperado=315.00 --origen_esperado=lista_cliente
```

Debe confirmar `id_lista_precio`, `lista_precio_snapshot`, `regla_precio_origen` y `precio_aplicado` en `erp_ventas_detalle`. La auditoria fina del precio historico se valida en detalle, no en encabezado.

11. Cambiar precio posterior y revalidar snapshot.

Preflight:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_cambio_posterior_readonly.php --codigo_lista=LP-UAT-BORRADOR-01 --id_sku=1760 --precio_nuevo=325.00
```

Aplicacion autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_cambio_posterior_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT --id_usuario=ID_USUARIO_AUTORIZA --codigo_lista=LP-UAT-BORRADOR-01 --id_sku=1760 --precio_nuevo=325.00
```

Revalidacion:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_snapshot_venta_readonly.php --folio=FOLIO_VENTA_UAT --codigo_lista=LP-UAT-BORRADOR-01 --id_sku=1760 --precio_esperado=315.00 --precio_lista_actual_esperado=325.00 --origen_esperado=lista_cliente
```

Debe conservar `precio_aplicado=315.00` en la venta, aunque la lista actual ya tenga `325.00`.

12. Validar auditoria comercial de la lista.

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_auditoria_eventos_readonly.php --codigo_lista=LP-UAT-BORRADOR-01
```

Debe mostrar eventos de creacion/edicion de lista, detalle, asignacion y cambio posterior con `datos_antes`, `datos_despues`, usuario y motivo.

Regla de respaldo:

- Respaldo externo obligatorio solo para DDL/cambios de esquema.
- Guardados normales de listas, detalles y asignaciones no requieren respaldo de BD; requieren permisos, token UAT y auditoria comercial.

## Prueba UAT inicial de escritura

Usar UI `/ventas/listas_precios`.

Lista:

- codigo: `LP-UAT-BORRADOR-01`
- nombre: `Lista UAT borrador`
- canal: `pos`
- almacen: `5`
- prioridad: `100`
- estatus: `borrador`
- token: `VENTAS_LISTAS_PRECIOS_GUARDAR_UAT`
- motivo: `UAT inicial listas de precios`

Resultado esperado:

- Se crea lista en borrador.
- Se crea evento en `erp_listas_precios_eventos`.
- No se activa precio en POS si no hay detalle activo.
- No cambia ninguna venta pasada.

## UAT posterior

1. Crear detalle para SKU `1760` con precio controlado.
2. Validar dry-run POS sin cliente:
   - `regla_precio_origen=lista_canal_sucursal`.
3. Crear/asignar cliente CRM con lista especial.
4. Validar dry-run POS con cliente:
   - debe ganar `lista_cliente`.
5. Ejecutar venta UAT real solo cuando POS/inventario/caja esten autorizados.
6. Confirmar snapshot en venta.
7. Cambiar precio posterior y confirmar que la venta pasada no cambia.

## Reversa operativa

Si falla UAT despues de aplicar DDL:

- No borrar tablas ni columnas.
- Pausar o cancelar listas creadas en UAT.
- Mantener eventos de auditoria.
- Revocar permisos de roles si se necesita cerrar acceso temporalmente.
- Conservar snapshot de cualquier venta UAT creada.

## Criterio de aceptacion

- Preflight sin bloqueos.
- Permisos `ventas.listas.*` sembrados.
- Auditoria `erp_listas_precios_eventos` existente.
- `id_cliente_crm` existente en `erp_clientes_listas_precios`.
- UI bloquea guardado sin token UAT.
- Guardado UAT deja evento auditable.
- Resolutor backend mantiene prioridad:
  1. excepcion POS autorizada;
  2. lista cliente CRM;
  3. lista cliente default;
  4. lista canal/sucursal;
  5. lista general ERP;
  6. fallback catalogo `general`.
