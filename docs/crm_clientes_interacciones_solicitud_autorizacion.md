# CRM Clientes - solicitud de autorizacion para interaccion

Fecha: 2026-06-30

## Alcance

Crear una sola interaccion operativa para un cliente CRM existente, usando la tabla `crm_clientes_interacciones`.

Este flujo sirve para registrar contactos reales como llamada, WhatsApp, correo, visita, seguimiento comercial, postventa, garantia, apartado, devolucion o calidad de datos.

## Token

`CRM_CLIENTES_INTERACCION`

## Requisitos previos

- Respaldo externo valido y legible.
- Tabla `crm_clientes_interacciones` creada mediante `CRM_CLIENTES_SEGUIMIENTO_DDL`.
- Cliente existente en `crm_clientes_maestro`.
- Dry-run exitoso en `/crm/cliente_interaccion_dryrun_erp`.

## Lo que si hace

- Inserta una interaccion en `crm_clientes_interacciones`.
- Registra evento `interaccion_creada` si existe `crm_clientes_eventos`.
- Conserva canal, direccion, resultado, resumen, detalle, origen y fecha de interaccion.

## Lo que no hace

- No modifica datos maestros del cliente.
- No crea, cierra ni reasigna tareas.
- No crea notificaciones SYS.
- No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- No fusiona clientes ni migra fuentes antiguas.

## Frase de autorizacion

`AUTORIZO CREAR INTERACCION CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_INTERACCION para cliente CRM [ID_CLIENTE_CRM], tipo [TIPO], canal [CANAL] y resumen [RESUMEN]. Entiendo que solo crea una interaccion CRM y su evento, no modifica clientes, no crea/cierra tareas, no crea notificaciones SYS, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.`

## Preparacion UAT

- Fecha: 2026-06-30.
- Scripts agregados:
  - `storage/uat/uat_crm_clientes_interaccion_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_interaccion_apply_authorized.php`.
- Validaciones:
  - sintaxis PHP correcta en ambos scripts;
  - el apply rechaza respaldos placeholder como `[RUTA_RESPALDO]`;
  - el apply rechaza cliente/tipo/canal/resumen con placeholders;
  - el dry-run no escribe BD.
- Dry-run UAT validado sin escritura:
  - cliente CRM `1`;
  - tipo `seguimiento`;
  - canal `whatsapp`;
  - direccion `saliente`;
  - resultado `registrado`;
  - resumen `UAT seguimiento CRM sin escritura`;
  - origen `UAT-CRM-INTERACCION-001`;
  - resultado `puede_guardar=true`.
- Pendiente:
  - autorizacion fuerte con datos concretos para crear una interaccion real.
