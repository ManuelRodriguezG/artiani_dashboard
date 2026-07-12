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
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_preflight_readonly.php --respaldo=REFERENCIA_RESPALDO
```

Debe cumplir:

- `ok=true`;
- `permisos.ventas_listas_total=8`;
- `schema.sql_eventos_total=1`;
- sin bloqueos;
- avisos permitidos si indican que falta aplicar CRM/listas o auditoria.

## Orden de ejecucion recomendado

1. Confirmar respaldo externo legible o referencia formal.
2. Aplicar DDL CRM/listas y auditoria.

Opcion CLI autorizada:

```text
C:\xampp\php\php.exe storage\uat\uat_ventas_listas_precios_schema_apply_authorized.php --autorizar_crm=VENTAS_LISTAS_PRECIOS_CRM_DDL --autorizar_auditoria=VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL --respaldo=REFERENCIA_RESPALDO
```

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

3. Aplicar semilla de seguridad con permisos base ERP desde el flujo autorizado de Seguridad.
4. Ejecutar nuevamente preflight.
5. Probar guardado UAT con lista nueva en `borrador`.

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
- respaldo: referencia validada
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
- UI bloquea guardado sin token/respaldo.
- Guardado UAT deja evento auditable.
- Resolutor backend mantiene prioridad:
  1. excepcion POS autorizada;
  2. lista cliente CRM;
  3. lista cliente default;
  4. lista canal/sucursal;
  5. lista general ERP;
  6. fallback catalogo `general`.
