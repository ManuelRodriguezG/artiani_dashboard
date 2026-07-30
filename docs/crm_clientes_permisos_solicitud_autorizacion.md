# CRM Clientes - Solicitud de autorizacion permisos base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: pendiente de autorizacion del dueno.

## Objetivo

Activar el dominio de permisos CRM para que el modulo `CRM > Clientes` pueda verse y operarse bajo control de seguridad, sin tocar clientes, ventas, POS, ecommerce ni esquema CRM.

## Alcance autorizado por este token

Token requerido:

```text
CRM_PERMISOS_BASE
```

Incluye:

- crear el rol `crm` si no existe;
- crear permisos `crm.ver`, `crm.crear`, `crm.editar`, `crm.fusionar`, `crm.auditoria` si no existen;
- vincular permisos CRM a roles base `direccion`, `administrador_erp`, `ventas` y `crm` segun el mapa definido en `SeguridadEsquema.php`.

## Fuera de alcance

Este token no autoriza:

- asignar usuarios al rol `crm`;
- crear, editar, fusionar o migrar clientes;
- crear tablas `crm_clientes_*`;
- modificar POS, ventas, garantias, apartados, ecommerce o legacy;
- borrar permisos existentes;
- cambiar contrasenas, sesiones o reglas de acceso no relacionadas.

## Evidencia previa

Scripts read-only:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_plan_readonly.php
```

Estado observado:

- los permisos `crm.*` aun no existen en BD;
- el grupo de sidebar `CRM > Clientes` ya esta preparado en codigo;
- el enlace depende de `crm.ver`, por lo que puede no aparecer hasta sembrar permisos;
- el script apply se bloquea correctamente sin token y respaldo.

## Comando autorizado propuesto

Reemplazar la ruta de respaldo por un respaldo externo real y reciente:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_apply_authorized.php --autorizar=CRM_PERMISOS_BASE --respaldo="C:\ruta\externa\respaldo.sql"
```

## Frase sugerida de autorizacion

```text
AUTORIZO SEMBRAR PERMISOS CRM BASE usando respaldo [RUTA_RESPALDO] con token CRM_PERMISOS_BASE. Entiendo que solo crea rol/permisos CRM y vinculos con roles base, y no toca clientes, ventas, POS, ecommerce ni esquema CRM.
```

## Verificacion posterior

Despues del apply:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_readonly.php
C:\xampp\php\php.exe storage\uat\uat_crm_permisos_plan_readonly.php
```

Resultado esperado:

- permisos `crm.*` existentes;
- rol `crm` existente;
- relaciones rol-permiso CRM creadas para roles base;
- el enlace `CRM > Clientes` visible para usuarios cuyo rol tenga `crm.ver`.

## Fase siguiente - permisos finos POS/CRM

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-30  
Estado: preparado en codigo; pendiente de autorizacion para sembrar/ajustar BD.

La operacion de POS no debe requerir acceso completo a la consola CRM. Para separar responsabilidades, se preparan permisos finos:

- `crm.pos.buscar`: buscar y seleccionar clientes CRM desde POS sin abrir `CRM > Clientes`.
- `crm.pos.alta_express`: validar altas express de clientes desde POS sin editar ficha completa CRM.

Mapa objetivo:

- rol `ventas`: `crm.pos.buscar`, `crm.pos.alta_express`; no debe conservar `crm.ver` ni `crm.crear` salvo que el usuario tambien opere CRM completo;
- rol `crm`: conserva `crm.ver`, `crm.crear`, `crm.editar`, `crm.fusionar`, `crm.auditoria` y tambien los permisos POS para soporte operativo;
- rol `administrador_erp`: conserva todos los permisos CRM;
- rol `direccion`: conserva consulta/auditoria CRM, no operacion POS express por defecto.

Autorizacion futura sugerida:

```text
AUTORIZO AJUSTAR PERMISOS FINOS CRM POS usando respaldo [RUTA_RESPALDO] con token CRM_POS_PERMISOS_FINOS. Entiendo que crea permisos crm.pos.buscar y crm.pos.alta_express, los vincula a roles base segun SeguridadEsquema.php, retira crm.ver y crm.crear del rol ventas si existen, no modifica clientes, ventas, POS, ecommerce, garantias, apartados, devoluciones ni legacy.
```

Preflight read-only:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_pos_permisos_finos_readonly.php
```

Apply autorizado:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_pos_permisos_finos_apply_authorized.php --autorizar=CRM_POS_PERMISOS_FINOS --respaldo="[RUTA_RESPALDO]"
```

Estado observado el 2026-07-30:

- `crm.pos.buscar` no existe aun en BD.
- `crm.pos.alta_express` no existe aun en BD.
- rol `ventas` conserva `crm.ver` y `crm.crear`; por eso ve CRM completo.
