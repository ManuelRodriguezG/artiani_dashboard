# ERP Seguridad - UI usuarios y roles

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Cerrado para administracion base de roles/permisos/usuarios  
Aplica a: pantalla de administracion de usuarios, roles y permisos

## Proposito

Hacer que la administracion de seguridad sea usable. El dueno o administrador debe poder asignar roles y permisos sin tocar base de datos.

## Archivos

- `app/vistas/paginas/apps/erp/seguridad/usuarios_roles.php`
- JS relacionado en `public/assets/js/custom/apps/erp/...` si existe.
- `app/controladores/Sistema.php`
- `app/modelos/SeguridadPermisos.php`

## Funciones esperadas

- Listar roles.
- Consultar permisos de rol.
- Guardar permisos de rol.
- Listar usuarios con roles.
- Asignar rol a usuario.
- Quitar rol a usuario.
- Editar datos base de usuario.
- Activar/desactivar usuario.
- Consultar auditoria si permiso lo permite.

## UX esperada

- Roles visibles con descripcion.
- Permisos agrupados por modulo.
- Cambios claros antes de guardar.
- Confirmacion para quitar permisos sensibles.
- Editar usuario en modal separado de asignacion de roles y contrasenas.
- Perfil robusto de usuario ERP cubierto en fase inicial: alias/usuario interno, correo, area/departamento y puesto.
- Para almacen/inventario, considerar alcance por almacen en una relacion separada, no como campo obligatorio del usuario.
- No permitir quitar el ultimo administrador.
- Mensajes claros para errores.

## Permisos

- Ver pantalla: `seguridad.ver`.
- Administrar roles/permisos/usuarios: `seguridad.administrar`.
- Ver auditoria: `auditoria.ver`.

## Criterios de terminado

- Un administrador puede asignar roles sin SQL manual.
- Permisos por rol se guardan correctamente.
- No se puede romper el ultimo administrador.
- UI muestra errores claros.

## Resultado de auditoria 2026-06-10

Estado: cerrado para UX base.

Funciona:

- Lista usuarios y roles desde endpoints de `Sistema`.
- Lista roles disponibles.
- Permite consultar permisos por rol.
- Renderiza permisos agrupados por modulo con opcion "Todos".
- Permite guardar permisos por rol con confirmacion previa.
- Permite asignar/quitar roles y activar/desactivar usuarios.
- Muestra errores de backend con `Swal`.
- Oculta/deshabilita controles administrativos cuando falta `seguridad.administrar`.
- Muestra auditoria reciente solo con `auditoria.ver`.

Corregido 2026-06-10:

- El boton `Nuevo usuario` solo se muestra con `seguridad.administrar` y ya no apunta a `/autenticacion/registro`.
- El boton abre alta interna protegida por `seguridad.administrar`.
- El alta interna captura nombres, apellidos, celular, contrasena inicial y rol inicial opcional.
- El endpoint `Sistema::seguridad_usuario_crear()` crea usuario, asigna rol inicial si aplica y audita `seguridad.crear_usuario` sin registrar contrasena.
- Los POST de `users-roles.js` envian `X-CSRF-Token` desde `window.ERP_CSRF_TOKEN`.
- El flujo legacy `/autenticacion/registro` redirige a Seguridad y `/autenticacion/registrar_usuario` responde 410 para evitar altas por fuera del modulo.
- El login ya no conserva enlace activo a `/autenticacion/registro`.
- La tabla de usuarios incluye accion `Editar` para administradores.
- La edicion interna actualiza datos base: nombres, apellidos, celular y estatus.
- La edicion interna actualiza perfil robusto inicial: `alias`, `correo`, `telefono`, `nombre_mostrar`, `area_departamento`, `puesto`, `telefono_secundario` y `notas_admin`.
- La edicion valida celular, alias y correo unicos, protege el ultimo administrador activo y audita `seguridad.editar_usuario`.
- Quitar rol y cambiar estatus de usuario requieren confirmacion con `Swal`.
- El selector principal de roles muestra clave, descripcion y total de permisos.
- Verificaciones: `node --check public\assets\js\custom\security\users-roles.js`, `php -l app\modelos\SeguridadPermisos.php`, `php -l app\controladores\Sistema.php`, `php -l app\core\Core.php` y `php -l app\vistas\paginas\apps\erp\seguridad\usuarios_roles.php` correctos.

Pendientes de fase avanzada:

- Restablecimiento de contrasena como accion separada, no dentro de editar usuario.
- Agregar paginacion y filtros por estado/rol para operacion con muchos usuarios.
- Crear CRUD de roles: crear, editar, duplicar o desactivar roles.

Recomendacion posterior:

- Seguir con restablecimiento de contrasena antes de CRUD de roles.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > UI usuarios roles.
Objetivo: auditar/mejorar la pantalla usuarios_roles para administrar roles, permisos y usuarios con UX clara.
Usa docs/erp_ux_operativa.md.
No cambies reglas backend sin revisar SeguridadPermisos.php.
Criterio: administrador puede asignar/quitar roles y guardar permisos sin tocar base de datos.
```
