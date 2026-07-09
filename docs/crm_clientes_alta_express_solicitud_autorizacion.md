# CRM Clientes - Solicitud de autorizacion alta express

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: pendiente de autorizacion del dueno.

## Objetivo

Crear un cliente express CRM desde POS/CRM con identificador unico, sin migrar clientes legacy, sin tocar ecommerce y sin crear ficha completa.

## Alcance autorizado por este token

Token requerido:

```text
CRM_CLIENTES_ALTA_EXPRESS
```

Incluye crear, para un cliente puntual:

- registro en `crm_clientes_maestro` con `calidad_datos=express`;
- identificador principal en `crm_clientes_identificadores`;
- consentimiento operativo si fue marcado;
- evento `alta_express` en `crm_clientes_eventos`.

## Fuera de alcance

Este token no autoriza:

- migrar `crm_clientes` legacy;
- copiar `erp_clientes`;
- vincular ecommerce;
- fusionar duplicados;
- crear datos fiscales, direcciones o ficha completa;
- modificar ventas historicas;
- borrar registros.

## Evidencia previa

Scripts read-only:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_alta_rapida_dryrun_readonly.php --nombre="Cliente Express UAT" --identificador=3312345678 --almacen=1 --consentimiento=1
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_schema_readonly.php
```

Estado observado:

- esquema CRM Clientes existe;
- dry-run propone cliente e identificador sin escribir BD;
- tablas CRM canonicas siguen vacias hasta autorizar alta real;
- migracion legacy sigue bloqueada por duplicados.

## Comando autorizado propuesto

Reemplazar datos de prueba por el cliente real a crear:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_alta_rapida_apply_authorized.php --autorizar=CRM_CLIENTES_ALTA_EXPRESS --respaldo="C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql" --nombre="Cliente Express UAT" --identificador=3312345678 --almacen=1 --consentimiento=1
```

## Frase sugerida de autorizacion

```text
AUTORIZO CREAR CLIENTE EXPRESS CRM usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_ALTA_EXPRESS para nombre [NOMBRE], identificador [IDENTIFICADOR] y almacen [ID_ALMACEN]. Entiendo que solo crea un cliente express CRM, su identificador y evento, y no migra legacy, POS ni ecommerce.
```

## Verificacion posterior

Despues del apply:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_alta_rapida_dryrun_readonly.php --nombre="[NOMBRE]" --identificador=[IDENTIFICADOR] --almacen=[ID_ALMACEN]
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_auditoria_readonly.php
```

Resultado esperado:

- el dry-run posterior debe bloquear por coincidencia existente;
- `crm_clientes_maestro` debe aumentar en 1;
- `crm_clientes_identificadores` debe aumentar en 1;
- no debe cambiar `crm_clientes` legacy ni `erp_clientes`.
