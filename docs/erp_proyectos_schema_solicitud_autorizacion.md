# Panel Proyectos - Solicitud de autorizacion DDL y permisos

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-25  
Estado: solicitud preparada; no ejecutada.

## Objetivo

Crear el esquema base del modulo general Proyectos y sincronizar permisos `proyectos.*` para empezar a capturar proyectos y tareas desde el panel.

Proyectos es transversal: puede coordinar CRM, ERP/POS, operacion, administracion, tecnologia o cualquier iniciativa del negocio.

## Tablas incluidas

- `erp_proyectos`
- `erp_proyecto_objetivos`
- `erp_proyecto_tareas`
- `erp_proyecto_comentarios`
- `erp_proyecto_adjuntos`
- `erp_proyecto_eventos`

## Alcance permitido del DDL

La aplicacion DDL puede:

- crear tablas `erp_proyecto*` si no existen;
- crear indices para estatus, prioridad, responsable, modulo y vencimiento;
- dejar tablas vacias, listas para captura manual o por modulo.

## Alcance prohibido del DDL

Esta autorizacion no permite:

- crear proyectos reales;
- crear tareas reales;
- importar pendientes de Compras, POS, Catalogo, TMS, CRM ni ningun otro modulo;
- modificar documentos vivos existentes;
- modificar ventas, inventario, compras, catalogo, proveedores, CRM, TMS, garantias o rentabilidad;
- asignar usuarios directos fuera de roles/permisos base.

## Permisos incluidos

- `proyectos.ver`
- `proyectos.crear`
- `proyectos.editar`
- `proyectos.asignar`
- `proyectos.cerrar`
- `proyectos.auditoria`
- `proyectos.configurar`

## Validacion previa recomendada

```powershell
C:\xampp\php\php.exe -l app\controladores\Proyecto.php
C:\xampp\php\php.exe -l app\modelos\ProyectosErp.php
C:\xampp\php\php.exe -l app\modelos\ProyectosEsquema.php
C:\xampp\php\php.exe storage\uat\uat_proyectos_schema_readonly.php
C:\xampp\php\php.exe storage\uat\uat_proyectos_permisos_readonly.php
```

## Texto de autorizacion para permisos

```text
AUTORIZO SEMBRAR PERMISOS PROYECTOS BASE usando respaldo [RUTA_RESPALDO] con token PROYECTOS_PERMISOS_BASE. Entiendo que solo crea permisos proyectos.* y vinculos con roles base, no crea proyectos, no crea tareas, no modifica otros modulos ni asigna usuarios directos.
```

## Texto de autorizacion para DDL

```text
AUTORIZO CREAR ESQUEMA PROYECTOS usando respaldo [RUTA_RESPALDO] con token PROYECTOS_DDL_BASE. Entiendo que solo crea tablas erp_proyecto* vacias para proyectos, objetivos, tareas, comentarios, adjuntos y eventos; no crea proyectos reales, no crea tareas reales, no importa pendientes de otros modulos y no modifica ventas, inventario, compras, catalogo, proveedores, CRM, TMS, garantias ni rentabilidad.
```

## Verificacion posterior esperada

```powershell
C:\xampp\php\php.exe storage\uat\uat_proyectos_schema_readonly.php
C:\xampp\php\php.exe storage\uat\uat_proyectos_permisos_readonly.php
```

Resultados esperados:

- `ddl_pendientes=0`.
- permisos `proyectos.*` existentes.
- roles base con permisos segun perfil.
- `/proyecto` visible para usuarios con `proyectos.ver` como seccion general del panel, no como submodulo ERP.
- bandeja vacia hasta que se capture el primer proyecto.

## Handoff

Aplicar permisos y DDL en pasos separados, ambos con respaldo externo. Despues de aplicar, entrar a `/proyecto`, crear un proyecto manual de prueba si el dueno lo autoriza y luego empezar a registrar tareas desde cada modulo solo cuando el dueno lo indique.
