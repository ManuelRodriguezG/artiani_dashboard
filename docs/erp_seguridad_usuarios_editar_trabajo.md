# ERP Seguridad - Editar usuario interno

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Cerrado para edicion base y perfil robusto inicial de usuario  
Aplica a: administracion interna de usuarios desde Seguridad

## Proposito

Permitir que un administrador edite datos base de usuarios sin tocar base de datos, sin usar el registro legacy y sin mezclar restablecimiento de contrasena con edicion normal.

## Archivos

- `app/modelos/SeguridadPermisos.php`
- `app/controladores/Sistema.php`
- `app/core/Core.php`
- `public/assets/js/custom/security/users-roles.js`
- `app/vistas/paginas/apps/erp/seguridad/usuarios_roles.php`

## Alcance cerrado

- Boton `Editar` por usuario visible solo cuando existe `seguridad.administrar`.
- Edicion de datos base:
  - `nombres`
  - `apellido_paterno`
  - `apellido_materno`
  - `celular`
  - `estatus`
- Edicion de perfil robusto:
  - `alias` como usuario interno.
  - `correo`
  - `telefono`
  - `nombre_mostrar`
  - `area_departamento`
  - `puesto`
  - `telefono_secundario`
  - `notas_admin`
- Validacion de datos obligatorios: nombre y celular.
- Validacion de celular unico contra otros usuarios.
- Validacion de `alias` y `correo` unicos cuando se capturan.
- Proteccion para no desactivar al propio usuario desde edicion.
- Proteccion para no dejar al sistema sin administrador activo.
- Auditoria explicita `seguridad.editar_usuario` con `datos_antes` y `datos_despues`.
- `Sistema.seguridad_usuario_editar` se agrega a auditoria explicita de `Core.php` para evitar duplicado generico.

## Auditoria de esquema 2026-06-10

Columnas ya existentes en `sys_usuarios`:

- `alias`
- `correo`
- `telefono`

Columnas agregadas por `SeguridadEsquema::planActualizarSeguridad(true)`:

- `nombre_mostrar`
- `area_departamento`
- `puesto`
- `telefono_secundario`
- `notas_admin`

Indices agregados:

- `idx_sys_usuarios_alias`
- `idx_sys_usuarios_correo`

Decision de naming:

- No se agrega columna `usuario` porque la tabla ya tenia `alias`; `alias` queda como el usuario interno/clave visible.

## Decisiones

- La contrasena no se modifica desde edicion normal.
- Restablecer contrasena queda como accion separada futura.
- Los campos robustos de perfil quedan dentro de Seguridad porque son atributos administrativos del usuario interno.
- Alcance por almacen/inventario no debe ser un campo simple del usuario; debe planearse como tabla separada de permisos/alcances por almacen.

## Verificaciones

- `php -l app\modelos\SeguridadPermisos.php`
- `php -l app\modelos\SeguridadEsquema.php`
- `php -l app\controladores\Sistema.php`
- `php -l app\core\Core.php`
- `node --check public\assets\js\custom\security\users-roles.js`
- `SHOW COLUMNS FROM sys_usuarios`

## Pendientes futuros

- Restablecimiento de contrasena por administrador.
- Filtros/paginacion de usuarios si crece la operacion.
- Alcance por almacen mediante tabla dedicada.
