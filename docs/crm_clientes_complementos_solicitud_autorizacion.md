# CRM Clientes - Solicitud de autorizacion para complementos

Fecha: 2026-06-29

## Objetivo

Autorizar la creacion controlada de complementos en una ficha CRM canonica:

- contacto;
- direccion;
- fiscal;
- nota;
- consentimiento.

## Alcance permitido

El apply autorizado puede insertar un solo complemento para un cliente CRM existente mediante:

- endpoint: `/crm/cliente_complemento_guardar_autorizado_erp`
- token: `CRM_CLIENTES_COMPLEMENTO`
- respaldo externo valido requerido.

Cada apply debe registrar evento en `crm_clientes_eventos` con tipo `complemento_creado`.

## Alcance prohibido

Este flujo no debe:

- crear clientes nuevos;
- modificar identificadores principales;
- fusionar duplicados;
- migrar legacy;
- tocar POS, ventas, ecommerce, garantias, apartados ni devoluciones;
- modificar ventas historicas;
- ejecutar DDL.

## Preflight obligatorio

Antes del apply debe ejecutarse dry-run con:

- endpoint: `/crm/cliente_complemento_guardar_dryrun_erp`
- resultado esperado: `puede_guardar=true`
- sin bloqueos.

## Texto de autorizacion

Usar este texto sustituyendo los campos entre corchetes:

`AUTORIZO CREAR COMPLEMENTO CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_COMPLEMENTO para cliente CRM [ID_CLIENTE_CRM], tipo [TIPO_COMPLEMENTO] y datos [RESUMEN_DATOS]. Entiendo que solo crea un complemento CRM y su evento, no migra legacy, no modifica identificadores principales, no toca POS, ventas, ecommerce, garantias, apartados ni devoluciones.`

## Ejemplo UAT recomendado

Para completar una ficha nueva real:

- cliente CRM: `[ID_CLIENTE_CRM]`
- primer complemento: `contacto`
- segundo complemento: `consentimiento`

El contacto debe capturarse primero; el consentimiento debe capturarse solo si el cliente lo otorgo.
