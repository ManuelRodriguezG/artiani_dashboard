# CRM Clientes - Solicitud de autorizacion DDL seguimiento

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: aplicado el 2026-06-30 con autorizacion del dueno.

## Objetivo

Crear la capa de seguimiento operativo CRM para registrar interacciones y tareas futuras de clientes, sin crear tareas reales y sin modificar clientes existentes.

## Token requerido

```text
CRM_CLIENTES_SEGUIMIENTO_DDL
```

## Alcance autorizado

Crear, si no existen:

- `crm_clientes_interacciones`
- `crm_clientes_tareas`

## Uso previsto

- Registrar llamadas, WhatsApp, correos, visitas, notas de seguimiento y contactos hechos.
- Crear tareas persistentes de seguimiento, postventa, cobranza futura, recompensas, garantia, apartado o campana.
- Mantener responsable, vencimiento, prioridad, estado y origen de la tarea.

## Fuera de alcance

Este token no autoriza:

- crear tareas reales;
- modificar clientes existentes;
- modificar contactos, consentimientos, ventas o POS;
- migrar legacy;
- vincular ecommerce;
- crear notificaciones SYS;
- tocar garantias, apartados, devoluciones ni recompensas.

## Endpoint preparado

Plan read-only:

```text
/crm/esquema_plan_clientes_seguimiento_crm
```

Apply autorizado:

```text
/crm/esquema_actualizar_clientes_seguimiento_crm
```

## Frase sugerida de autorizacion

```text
AUTORIZO CREAR ESQUEMA CRM SEGUIMIENTO usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_SEGUIMIENTO_DDL. Entiendo que solo crea tablas crm_clientes_interacciones y crm_clientes_tareas, no crea tareas reales, no modifica clientes, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

## Verificacion posterior

Despues del apply, revisar:

- las dos tablas existen;
- estan vacias;
- la consola CRM sigue cargando;
- la cola de calidad sigue siendo read-only hasta autorizar creacion de tareas.

## Aplicacion autorizada

- Fecha: 2026-06-30.
- Token usado: `CRM_CLIENTES_SEGUIMIENTO_DDL`.
- Respaldo externo generado y usado:
  - `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_seguimiento.sql`
  - tamano verificado: `27816043` bytes.
- Script UAT:
  - `storage/uat/uat_crm_clientes_seguimiento_schema_apply_authorized.php`.
- Resultado:
  - `crm_clientes_interacciones` creada con `0` filas;
  - `crm_clientes_tareas` creada con `0` filas.
- Alcance respetado:
  - no crea tareas reales;
  - no crea interacciones reales;
  - no modifica clientes;
  - no crea notificaciones SYS;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Verificacion read-only:
  - ficha CRM consultada correctamente para `id_cliente_crm=1`;
  - listado CRM consultado correctamente;
  - re-ejecucion idempotente detecta tablas existentes y conserva `0` filas.
