# ERP Seguridad - Cierre de base

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Cerrado como base de Seguridad/Roles ERP  
Aplica a: validacion final antes de avanzar Catalogo/Compras

## Proposito

Definir cuando Seguridad/Roles se considera suficientemente cerrado para continuar con Catalogo, Proveedores y Compras.

## Checklist de cierre

- Roles base confirmados por el dueno.
- Permisos base confirmados.
- Asignacion rol-permiso revisada.
- Usuario administrador protegido.
- Usuarios pueden tener multiples roles.
- Permisos se cargan en sesion.
- Endpoints sensibles tienen permiso puntual.
- POST sensible valida CSRF.
- Auditoria basica funciona.
- UI de usuarios/roles permite operar sin SQL manual.
- Decision sobre Compras y Finanzas tomada.

## Pruebas minimas

- Usuario sin sesion redirige a login.
- Usuario sin permiso recibe 403/JSON segun request.
- Administrador no puede perder ultimo administrador activo.
- Cambiar permisos de rol actualiza sesion cuando aplica.
- Asignar/quitar rol se audita.
- Seguridad UI lista roles, usuarios y permisos.
- Auditoria lista eventos.

## Criterio de terminado

Seguridad queda lista cuando un administrador real puede:

- Crear/activar usuario existente segun flujo actual.
- Asignar rol.
- Ajustar permisos.
- Consultar auditoria.
- Confirmar que roles operativos ven solo lo que deben.

## Evaluacion de cierre 2026-06-10

Estado: cerrado como base tecnica operativa.

Cerrado:

- Esquema base de seguridad auditado.
- Permisos base auditados.
- Tablas, indices y semillas existen.
- Administrador local activo y protegido.
- UI base permite asignar roles y permisos sin SQL manual.
- Auditoria base registra eventos sensibles principales.
- Roles base cerrados y descritos en `SeguridadEsquema::rolesBaseERP()`.
- Matriz rol-permiso conservadora cerrada y sincronizada en base local.
- Endpoints pendientes de Seguridad, Almacen, Compra y Proveedor protegidos con permiso puntual.
- UI reemplazo enlace a registro publico por alta interna protegida y confirmaciones sensibles.
- UI ahora permite alta interna real de usuario con rol inicial opcional.
- UI ahora permite editar datos base y perfil robusto inicial de usuario con auditoria y validaciones.
- Perfil robusto inicial: `alias`, `correo`, `telefono`, `nombre_mostrar`, `area_departamento`, `puesto`, `telefono_secundario` y `notas_admin`.
- Registro legacy deshabilitado: `/autenticacion/registro` redirige a Seguridad y `/autenticacion/registrar_usuario` responde 410.
- Auditoria consultable con filtros por usuario, modulo, accion, resultado y fechas.

Evidencia de cierre 2026-06-10:

- `php -l` correcto en `SeguridadEsquema.php`, `SeguridadPermisos.php`, `Sistema.php`, `Proveedor.php` y vista `usuarios_roles.php`.
- `php -l` correcto en `Core.php` despues de agregar `Sistema.seguridad_usuario_crear` a auditoria explicita.
- `node --check` correcto en `public\assets\js\custom\security\users-roles.js`.
- Base local sincronizada: `administrador_erp` 26 permisos, `direccion` 12, `compras` 12, `finanzas_contabilidad` 8, `soporte_sistema` 4.

Pendientes de fase avanzada:

- Restablecimiento de contrasena.
- Paginacion real de auditoria si crece la tabla.
- `datos_antes` mas completo por modulo critico.
- Modernizar Proveedores en controlador/modelo ERP nuevo antes de extenderlo.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > Cierre.
Objetivo: validar checklist final de seguridad antes de avanzar a Catalogo/Proveedores/Compras.
No agregues nuevas funcionalidades salvo fixes pequeños necesarios.
Criterio: marcar en docs/erp_seguridad_roles_avance.md que seguridad base queda cerrada o listar bloqueos concretos.
```
