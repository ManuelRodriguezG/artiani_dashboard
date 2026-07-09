# CRM Clientes - Solicitud de autorizacion tarea de seguimiento

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-30  
Estado: pendiente de autorizacion del dueno.

## Objetivo

Crear una tarea de seguimiento CRM para un cliente canonico existente, despues de validar dry-run y solo si el esquema de seguimiento ya existe.

## Token requerido

```text
CRM_CLIENTES_TAREA
```

## Endpoint preparado

Dry-run:

```text
/crm/cliente_tarea_dryrun_erp
```

Apply autorizado:

```text
/crm/cliente_tarea_crear_autorizado_erp
```

## Alcance autorizado

Este token permite crear un registro en:

- `crm_clientes_tareas`

Y, si existe la tabla de eventos, registrar evento:

- `crm_clientes_eventos` con tipo `tarea_creada`

## Fuera de alcance

Este token no autoriza:

- crear el esquema de seguimiento;
- modificar datos del cliente;
- crear contactos, direcciones, fiscales, notas o consentimientos;
- crear notificaciones SYS;
- tocar POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy;
- cerrar o cancelar tareas existentes con este token. Para eso usar `CRM_CLIENTES_TAREA_ESTATUS`.

## Preflight obligatorio

Antes del apply:

- ejecutar `/crm/cliente_tarea_dryrun_erp`;
- confirmar `puede_guardar=true`;
- confirmar que no hay bloqueos;
- confirmar que `crm_clientes_tareas` ya existe.

## Frase sugerida de autorizacion

```text
AUTORIZO CREAR TAREA CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_TAREA para cliente CRM [ID_CLIENTE_CRM], tipo [TIPO], prioridad [PRIORIDAD] y titulo [TITULO]. Entiendo que solo crea una tarea CRM y su evento, no modifica clientes, no crea notificaciones SYS, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

## Verificacion posterior

Despues del apply:

- consultar ficha del cliente;
- verificar evento `tarea_creada`;
- verificar que no cambiaron datos basicos del cliente;
- verificar que no se creo notificacion SYS.

## Cambio de estatus de tarea

Token:

```text
CRM_CLIENTES_TAREA_ESTATUS
```

Endpoints preparados:

```text
/crm/cliente_tarea_estatus_dryrun_erp
/crm/cliente_tarea_estatus_autorizado_erp
```

Este token permite mover una tarea existente a:

- `en_proceso`
- `cerrada`
- `cancelada`

Reglas:

- requiere tabla `crm_clientes_tareas`;
- requiere tarea existente y no cerrada/cancelada previamente;
- requiere dry-run exitoso;
- requiere resultado si el destino es `cerrada` o `cancelada`;
- solo actualiza la tarea indicada;
- registra evento `tarea_estatus` si existe `crm_clientes_eventos`;
- no modifica datos del cliente;
- no crea interacciones automaticamente;
- no crea notificaciones SYS;
- no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

Frase sugerida:

```text
AUTORIZO CAMBIAR ESTATUS TAREA CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_TAREA_ESTATUS para tarea CRM [ID_CLIENTE_TAREA], estatus [ESTATUS] y resultado [RESULTADO]. Entiendo que solo actualiza esa tarea CRM y su evento, no modifica clientes, no crea interacciones automaticas, no crea notificaciones SYS, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

## Preparacion UAT

- Fecha: 2026-06-30.
- Scripts agregados para crear tarea:
  - `storage/uat/uat_crm_clientes_tarea_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_tarea_apply_authorized.php`.
- Validaciones:
  - sintaxis PHP correcta en ambos scripts;
  - el apply rechaza respaldos placeholder como `[RUTA_RESPALDO]`;
  - el apply rechaza cliente/tipo/prioridad/titulo con placeholders;
  - el dry-run no escribe BD.
- Dry-run UAT validado sin escritura:
  - cliente CRM `1`;
  - tipo `calidad_datos`;
  - prioridad `normal`;
  - titulo `Completar contacto CRM UAT`;
  - origen `UAT-CRM-TAREA-001`;
  - resultado `puede_guardar=true`.
- Pendiente:
  - autorizacion fuerte con datos concretos para crear una tarea real;
  - preparar scripts UAT para cambio de estatus si se crea una tarea real.

## Preparacion UAT cambio de estatus

- Fecha: 2026-06-30.
- Scripts agregados:
  - `storage/uat/uat_crm_clientes_tarea_estatus_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_tarea_estatus_apply_authorized.php`.
- Validaciones:
  - sintaxis PHP correcta en ambos scripts;
  - el apply rechaza respaldos placeholder como `[RUTA_RESPALDO]`;
  - el apply rechaza tarea/estatus/resultado con placeholders;
  - dry-run contra tarea inexistente `999999` bloquea con `Tarea CRM no encontrada`;
  - la bandeja read-only lista tareas con `0` registros.
- Verificador general:
  - `storage/uat/uat_crm_clientes_seguimiento_post_apply_readonly.php`;
  - confirma tareas/interacciones globales y por cliente sin escribir BD;
  - estado actual: `0` tareas y `0` interacciones.
- Pendiente:
  - crear una tarea real con `CRM_CLIENTES_TAREA`;
  - despues validar y autorizar cambio de estatus con `CRM_CLIENTES_TAREA_ESTATUS`.
