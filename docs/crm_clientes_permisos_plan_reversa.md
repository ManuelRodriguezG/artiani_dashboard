# CRM Clientes - Plan de reversa permisos base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: plan preventivo; no ejecutar sin autorizacion explicita.

## Proposito

Definir como revertir la activacion de permisos CRM base si el sembrado genera un problema operativo.

## Preferencia de reversa

La reversa preferida es restaurar el respaldo externo tomado antes del apply, porque conserva el estado exacto de seguridad y auditoria.

## Reversa logica alternativa

Si no se restaura respaldo y el dueno autoriza una reversa logica, el alcance seria:

- retirar relaciones de permisos `crm.*` con roles base;
- conservar o desactivar el rol `crm` segun decision del dueno;
- conservar permisos `crm.*` si ya fueron referenciados por auditoria o configuracion futura.

No se recomienda borrar permisos si ya existieron sesiones, auditoria o configuraciones relacionadas.

## Fuera de alcance

La reversa de permisos no debe:

- borrar clientes;
- borrar tablas `crm_clientes_*`;
- modificar ventas, POS o ecommerce;
- revertir cambios de codigo;
- tocar usuarios o contrasenas salvo autorizacion separada.

## Verificacion posterior

Despues de una reversa:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_readonly.php
```

Resultado esperado segun tipo de reversa:

- con respaldo: estado identico al previo al apply;
- logica: roles base sin acceso CRM y sidebar oculto para usuarios sin `crm.ver`.
