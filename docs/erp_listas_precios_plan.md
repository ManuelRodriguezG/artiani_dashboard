# ERP Comercial - Listas de precios

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: decision viva de arquitectura; no implica DDL ni escrituras en BD.

## Principio rector

Listas de precios no debe depender de asignar clientes uno por uno. Esa opcion debe existir, pero como excepcion comercial o trato negociado. El camino escalable para miles de clientes es asignar listas a segmentos/tipos de cliente y dejar que el resolutor backend elija el precio por prioridad, vigencia, canal, almacen y elegibilidad.

## Modelo comercial recomendado

La administracion de listas debe soportar estos niveles:

1. Lista general ERP.
2. Lista por canal y, cuando aplique, almacen.
3. Lista por segmento/tipo de cliente CRM.
4. Lista default del cliente CRM.
5. Lista asignada directamente a cliente CRM.
6. Precio manual autorizado en venta como excepcion.

El cliente individual no debe usarse para resolver todos los casos recurrentes, porque no escala cuando existan miles de clientes. Debe reservarse para convenios especiales, mayoristas concretos, cuentas clave o excepciones autorizadas.

## Segmentos/tipos de cliente

CRM ya contempla segmentos mediante:

- `crm_clientes_segmentos`
- `crm_clientes_segmentos_rel`
- `crm_clientes_maestro.id_segmento_default`

Listas de precios debe apoyarse en ese modelo para casos como:

- publico general;
- frecuente;
- recurrente;
- mayoreo;
- instalador;
- acuarista frecuente;
- cuenta comercial;
- cliente VIP autorizado;
- ecommerce registrado;
- convenio especial.

Los nombres finales deben definirse operativamente, pero la arquitectura debe permitir crearlos, pausarlos, auditarlos y asignarlos sin tocar ventas pasadas.

## Tabla futura sugerida

Cuando se autorice DDL, crear o validar una tabla equivalente a:

- `erp_segmentos_listas_precios`

Campos sugeridos:

- `id_segmento_lista_precio`
- `id_segmento_crm`
- `id_lista_precio`
- `canal`
- `id_almacen`
- `prioridad`
- `fecha_inicio`
- `fecha_fin`
- `estatus`
- `motivo`
- `creado_por`
- `fecha_registro`
- `actualizado_por`
- `fecha_actualizacion`

Debe tener auditoria comercial similar a cliente-lista, porque cambiar una lista por segmento puede afectar a muchos clientes.

## Plan tecnico preparado

Estado 2026-07-16:

- Auditoria read-only: `/comercial/esquema_auditar_segmentos_listas_precios`
- Plan DDL protegido: `/comercial/esquema_actualizar_segmentos_listas_precios`
- Token requerido para ejecutar: `VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL`
- Requiere respaldo externo valido antes de ejecutar.
- Auditoria CLI read-only ejecutada: CRM segmentos existe, CRM segmentos relacion existe, `erp_listas_precios` existe, `erp_segmentos_listas_precios` no existe.
- Plan DDL generado sin ejecutar; crea solo `erp_segmentos_listas_precios` con indices por segmento, lista, alcance y vigencia.
- Resolutor backend preparado de forma defensiva: si la tabla puente existe y el cliente tiene `id_segmento_default`, puede devolver `regla_precio_origen=lista_segmento_cliente`; si la tabla no existe, conserva el flujo actual.
- UAT read-only `storage/uat/uat_listas_precios_segmentos_readonly.php 2` ejecutado: no hay segmentos CRM activos visibles; dry-run de asignacion queda bloqueado por falta de tabla puente y segmento activo.
- Catalogo inicial sugerido documentado en `docs/erp_listas_precios_segmentos_catalogo_inicial.md`.
- Segmentos base quedan como catalogo CRM configurable, no hardcodeado. Endpoints preparados: listar, dry-run y guardado autorizado con token `CRM_CLIENTES_SEGMENTO_CATALOGO`.
- UAT read-only `storage/uat/uat_crm_segmentos_catalogo_readonly.php` ejecutado: no hay segmentos actuales y los 7 segmentos base sugeridos pasan dry-run sin bloqueos.
- Comercial/Listas ya expone seleccion y validacion de segmentos CRM desde la vista operativa. El guardado de vinculo segmento/lista queda preparado en backend y UI, pero bloquea mientras falte `erp_segmentos_listas_precios`.
- Endpoint preparado para guardado futuro de vinculo segmento/lista: `/comercial/listas_precios_segmento_guardar_operativo_erp`. No crea segmentos, no asigna clientes y no modifica ventas pasadas.
- La consulta de lista ya devuelve `asignaciones_segmentos` y `schema.segmentos_listas`; antes del DDL la UI muestra el estado pendiente sin romper el flujo.
- Revision de lista ya contempla conteo de segmentos activos y advierte que listas de `mayoreo`/`ecommerce` deben vincularse por segmento cuando exista el puente.
- UAT read-only `storage/uat/uat_listas_precios_segmentos_readonly.php 2` valida auditoria, SQL planeado, consulta de lista, segmentos candidatos y dry-run sin ejecutar DDL.
- Respaldo externo generado para la fase: `C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql`.
- Scripts protegidos preparados:
  - `storage/uat/uat_crm_segmentos_catalogo_apply_authorized.php` para sembrar segmentos base con token `CRM_CLIENTES_SEGMENTO_CATALOGO`.
  - `storage/uat/uat_listas_precios_segmentos_schema_apply_authorized.php` para crear `erp_segmentos_listas_precios` con token `VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL`.
  - `storage/uat/uat_listas_precios_segmento_vinculo_apply_authorized.php` para vincular lista/segmento con token `VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL`.
- Los scripts protegidos fueron validados en modo bloqueado; con token incorrecto no ejecutan escritura.
- La siembra de segmentos base es idempotente: si el codigo ya existe, carga `id_segmento_crm` y actualiza en vez de duplicar.
- Verificador read-only preparado: `storage/uat/uat_listas_precios_segmento_resolutor_readonly.php 2 RECURRENTE 1760 5`.
- Candidatos cliente/segmento read-only preparado: `storage/uat/uat_crm_cliente_segmento_candidatos_readonly.php RECURRENTE 10`.
- Apply protegido para asignar cliente a segmento preparado: `storage/uat/uat_crm_cliente_segmento_apply_authorized.php` con token `CRM_CLIENTES_SEGMENTO`; actualiza `id_segmento_default` si se solicita y no toca listas ni ventas.
- Suite read-only de preparacion completa: `storage/uat/uat_listas_precios_segmentos_suite_readonly.php 2 RECURRENTE 1760 5`.
- Estado actual de la suite: lista UAT 2 existe con SKU 1760 y cliente candidato 1 existe; faltan segmento `RECURRENTE` y tabla puente `erp_segmentos_listas_precios`.

Reglas del apply:

- No crea segmentos CRM.
- No asigna listas a segmentos.
- No modifica ventas pasadas.
- Solo prepara la tabla puente `erp_segmentos_listas_precios`.

## Prioridad v1 ajustada

Orden recomendado del resolutor backend:

1. Precio manual autorizado en POS, con permiso, motivo y auditoria.
2. Lista asignada directamente al cliente CRM.
3. Lista default del cliente CRM.
4. Lista por segmento/tipo de cliente CRM.
5. Lista por canal/almacen.
6. Lista general ERP.
7. Fallback temporal a `erp_catalogo_sku_precios` lista `general`.

Promociones, cupones y recompensas quedan fuera de esta fase.

## Reglas de autorizacion

Asignar una lista mejor a un segmento debe requerir permiso y motivo cuando:

- baja margen esperado;
- aplica a muchos clientes;
- aplica a ecommerce;
- tiene prioridad alta;
- sustituye una lista vigente;
- usa vigencia abierta sin fecha fin.

El sistema debe permitir que un cliente suba de segmento por autorizacion futura, por ejemplo de `recurrente` a `vip`, sin cambiar ventas pasadas. Las ventas confirmadas deben conservar snapshot de lista, origen y precio aplicado.

## UI recomendada

La pantalla de Listas de precios debe evolucionar a tres formas de alcance:

- Alcance por canal/almacen.
- Alcance por segmento/tipo de cliente.
- Excepcion por cliente especifico.

La asignacion por cliente debe presentarse visualmente como excepcion, no como el mecanismo principal.

## UAT futuro

Casos minimos:

- Cliente sin segmento usa lista canal/almacen o general.
- Cliente con segmento `recurrente` usa lista del segmento.
- Cliente con lista directa gana sobre segmento.
- Cliente con `id_lista_precio_default` gana sobre segmento si la prioridad definida lo indica.
- Cambio posterior de segmento no modifica ventas pasadas.
- Pausar lista por segmento hace caer a canal/almacen o general.
- Ecommerce no consume listas internas POS si no hay regla explicita.
