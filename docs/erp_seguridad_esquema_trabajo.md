# ERP Seguridad - Esquema

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Subdocumento puntual de `erp_seguridad_roles_avance.md`  
Aplica a: tablas, indices y semillas base de seguridad

## Proposito

Auditar y cerrar el esquema de seguridad del ERP: roles, permisos, relacion rol-permiso, usuarios-roles y auditoria. Este punto debe quedar firme antes de delegar Compras, Catalogo, Almacen o Finanzas por rol.

## Archivos de trabajo

- `app/modelos/SeguridadEsquema.php`
- `app/modelos/SeguridadPermisos.php`
- `app/core/DBSchema.php`
- `app/controladores/Sistema.php`

## Tablas requeridas

- `sys_roles`
- `sys_permisos`
- `sys_roles_permisos`
- `sys_usuarios_roles`
- `sys_auditoria_eventos`

## Validaciones de esquema

Confirmar:

- Llaves primarias.
- Llaves unicas para evitar duplicados.
- Indices por usuario, rol, permiso, modulo/accion y fecha.
- Campos de estatus.
- Campos de fecha de registro/actualizacion.
- Soporte para auditoria JSON.

## Reglas

- No ejecutar cambios de esquema sin autorizacion explicita.
- Usar `SeguridadEsquema.php` para agregar tablas/campos/indices.
- No crear permisos manualmente fuera del plan de semillas salvo caso justificado.
- Mantener proteccion de administrador.

## Permisos

- Auditar esquema: `sistema.soporte` o usuario tecnico autorizado.
- Ejecutar plan de seguridad: `sistema.soporte` o `seguridad.administrar`, segun regla final.
- Consultar seguridad: `seguridad.ver`.
- Administrar seguridad: `seguridad.administrar`.

## Criterios de terminado

- Todas las tablas base existen o el plan de actualizacion las crea.
- Indices criticos existen.
- Semillas de roles/permisos pueden ejecutarse de forma idempotente.
- No hay duplicados de permisos.
- El plan puede correr en dry-run antes de ejecutar.

## Resultado de auditoria 2026-06-10

Estado: terminado para esquema base.

Evidencia local:

- Base auditada: `artianilocal`.
- Tablas presentes: `sys_roles`, `sys_permisos`, `sys_roles_permisos`, `sys_usuarios_roles`, `sys_auditoria_eventos`.
- Motor/collation: InnoDB y `utf8mb4_general_ci` en las cinco tablas.
- Llaves unicas presentes:
  - `idx_sys_roles_rol` en `sys_roles(rol)`.
  - `idx_sys_permisos_permiso` en `sys_permisos(permiso)`.
  - `idx_sys_roles_permisos_rol_permiso` en `sys_roles_permisos(id_rol,id_permiso)`.
  - `idx_sys_usuarios_roles_usuario_rol` en `sys_usuarios_roles(id_usuario,id_rol)`.
- Indices de consulta presentes:
  - `sys_permisos(modulo,accion)`.
  - `sys_roles_permisos(id_permiso)`.
  - `sys_usuarios_roles(id_rol)`.
  - `sys_auditoria_eventos(id_usuario)`, `(modulo,accion)`, `(entidad,entidad_id)`, `(fecha_registro)`.
- Duplicados: no se detectaron permisos duplicados ni roles duplicados.
- Dry-run: `SeguridadEsquema::planActualizarSeguridad(false)` genero 172 pasos sin errores.
- Semillas observadas: `administrador_erp` 26 permisos, `compras` 13, `soporte_sistema` 13.

Observaciones:

- En MariaDB/XAMPP, columnas declaradas como `JSON` se reportan como `longtext`; se acepta como comportamiento compatible mientras la aplicacion escriba JSON valido.
- El esquema no define foreign keys fisicas entre tablas `sys_*`; por ahora se acepta por compatibilidad con el estilo del proyecto, pero se debe vigilar integridad desde modelo/transacciones.
- Riesgo fuera de este paso: `Sistema::esquema_actualizar_seguridad()` permite llegar al plan con sesion activa y debe moverse al Paso 5 para exigir permiso explicito antes de ejecutar cambios.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > Esquema.
Usa AGENTS.md, docs/erp_plan_maestro_fundamentos.md, docs/erp_seguridad_roles_avance.md y docs/erp_seguridad_esquema_trabajo.md.
Objetivo: auditar SeguridadEsquema.php y SeguridadPermisos.php para confirmar tablas, indices y semillas base.
No ejecutes cambios de esquema todavia.
Criterio: actualizar el avance con hallazgos, riesgos y plan exacto de cambios si faltan tablas/campos/indices.
```
