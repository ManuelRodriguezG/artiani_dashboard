# CRM Clientes - Solicitud de autorizacion ficha basica

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: pendiente de autorizacion del dueno.

## Objetivo

Actualizar datos basicos de un cliente CRM canonico con evento de cambio, sin modificar identificadores, contactos, fiscales, direcciones, ventas, legacy ni ecommerce.

## Alcance autorizado por este token

Token requerido:

```text
CRM_CLIENTES_FICHA_BASICA
```

Incluye actualizar en `crm_clientes_maestro`:

- `nombre_publico`;
- `tipo_cliente`;
- `estatus`;
- `observaciones_operativas`;
- `actualizado_por`;
- `fecha_actualizacion`.

Tambien crea un evento `edicion_basica` en `crm_clientes_eventos` con snapshot antes/despues.

## Fuera de alcance

Este token no autoriza:

- modificar identificadores;
- crear contactos, direcciones o fiscales;
- migrar `crm_clientes` legacy;
- vincular ecommerce/POS;
- modificar ventas historicas;
- borrar registros.

## Evidencia previa

Scripts read-only/dry-run:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_ficha_readonly.php --id=1
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_ficha_basica_dryrun_readonly.php --id=1 --nombre="Cliente Express UAT" --tipo=persona --estatus=activo --observaciones="Validacion UAT"
```

Estado observado:

- ficha CRM del cliente `id_cliente_crm=1` existe;
- dry-run valida cambios sin escribir;
- apply se mantiene bloqueado sin token y respaldo.

## Comando autorizado propuesto

Reemplazar valores por el cambio real:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_ficha_basica_apply_authorized.php --autorizar=CRM_CLIENTES_FICHA_BASICA --respaldo="C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql" --id=1 --nombre="Cliente Express UAT" --tipo=persona --estatus=activo --observaciones="Validacion UAT"
```

## Frase sugerida de autorizacion

```text
AUTORIZO ACTUALIZAR FICHA BASICA CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_FICHA_BASICA para cliente [ID_CLIENTE_CRM], nombre [NOMBRE], tipo [TIPO], estatus [ESTATUS] y observaciones [OBSERVACIONES]. Entiendo que solo actualiza datos basicos del cliente CRM y crea evento, sin modificar identificadores, contactos, ventas, legacy ni ecommerce.
```

## Verificacion posterior

Despues del apply:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_ficha_readonly.php --id=[ID_CLIENTE_CRM]
```

Resultado esperado:

- datos basicos actualizados;
- conteo de eventos aumenta en 1;
- identificadores, contactos, fiscales y vinculos no cambian.
