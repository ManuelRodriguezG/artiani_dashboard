# CRM Clientes - solicitud de autorizacion comercial

Fecha: 2026-06-30

## DDL comercial

Token: `CRM_CLIENTES_COMERCIAL_DDL`

Endpoint:

```text
/crm/esquema_actualizar_clientes_comercial_crm
```

Alcance:

- Crea `crm_clientes_condiciones`.
- No modifica clientes existentes.
- No crea segmentos.
- No crea ni modifica listas de precios.
- No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

Frase sugerida:

```text
AUTORIZO CREAR ESQUEMA CRM COMERCIAL usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_COMERCIAL_DDL. Entiendo que solo crea crm_clientes_condiciones, no modifica clientes, no crea segmentos, no crea ni modifica listas de precios, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

## Asignacion de segmento

Token: `CRM_CLIENTES_SEGMENTO`

Endpoints:

```text
/crm/cliente_segmento_dryrun_erp
/crm/cliente_segmento_asignar_autorizado_erp
```

Alcance:

- Crea una relacion activa en `crm_clientes_segmentos_rel`.
- Puede marcarla como principal.
- Puede actualizar `crm_clientes_maestro.id_segmento_default` si se solicita.
- Registra evento `segmento_asignado` si existe `crm_clientes_eventos`.

Fuera de alcance:

- No modifica listas de precios.
- No crea recompensas.
- No cambia condiciones comerciales.
- No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

Frase sugerida:

```text
AUTORIZO ASIGNAR SEGMENTO CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_SEGMENTO para cliente CRM [ID_CLIENTE_CRM] y segmento CRM [ID_SEGMENTO_CRM]. Entiendo que solo crea una relacion cliente-segmento CRM y su evento, opcionalmente actualiza segmento default, no modifica listas de precios, no crea recompensas, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

## Preferencias de contacto

Token: `CRM_CLIENTES_PREFERENCIAS`

Endpoints:

```text
/crm/cliente_preferencias_dryrun_erp
/crm/cliente_preferencias_guardar_autorizado_erp
```

Alcance:

- Guarda preferencias en `crm_clientes_condiciones.preferencias`.
- Puede crear el registro de condiciones si no existe.
- Registra evento `preferencias_actualizadas` si existe `crm_clientes_eventos`.
- Maneja canal preferido, canales permitidos, horario, frecuencia, temas y bandera de no contactar.

Fuera de alcance:

- No otorga consentimiento legal.
- No modifica contactos.
- No cambia datos del cliente.
- No crea campanas.
- No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

Frase sugerida:

```text
AUTORIZO GUARDAR PREFERENCIAS CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_PREFERENCIAS para cliente CRM [ID_CLIENTE_CRM]. Entiendo que solo guarda preferencias en condiciones CRM y su evento, no otorga consentimiento legal, no modifica contactos, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```
