# ERP - Seguridad, usuarios, roles y auditoria: control de avance

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Checklist maestro de seguridad ERP  
Relacionado: `docs/erp_plan_maestro_fundamentos.md`

## Contexto obligatorio para agentes

Antes de trabajar cualquier tarea de este tablero, revisar:

- `AGENTS.md`: arquitectura, rutas criticas, reglas para no inventar negocio y no mezclar ERP nuevo con legado.
- `docs/erp_plan_maestro_fundamentos.md`: orden general del ERP y razon por la que Seguridad va primero.
- `docs/ia_uso_modelos.md`: nivel de IA recomendado segun riesgo, alcance y consumo de tokens.
- `docs/erp_ux_operativa.md`: obligatorio si la tarea toca vistas, formularios, tablas, botones, mensajes, roles visibles o asignacion de permisos.
- Este archivo: estado actual, siguiente paso y evidencia pendiente.
- Documento puntual del paso activo, listado en `Subdocumentos puntuales`.

Regla viva: si el dueno agrega una observacion reusable durante el trabajo, actualizar el documento rector correcto (`AGENTS.md`, `erp_ux_operativa.md`, `ia_uso_modelos.md`, plan maestro o documento puntual) y dejar una nota breve aqui cuando afecte Seguridad.

## Proposito

Este archivo sirve para cerrar el primer cimiento del ERP: seguridad, usuarios, roles, permisos, sesion y auditoria. Sin esto completo, no conviene delegar Compras, Catalogo, Almacen o Finanzas por rol.

## Diagnostico rapido

Ya existe:

- Tablas de seguridad en `SeguridadEsquema.php`.
- Roles base ERP.
- Permisos base ERP.
- Asignacion de roles a usuarios.
- Guardado de permisos por rol.
- Auditoria de eventos.
- Validacion `requerirPermiso`.
- Sesion, CSRF y expiracion en `SesionSeguridad.php`.

Pendiente de auditar:

- Que todos los controladores nuevos usen `requerirSesion`/`requerirPermiso`.
- Que endpoints legados no queden expuestos sin permiso.
- Que la UI de seguridad sea usable para asignar roles/permisos.
- Que auditoria cubra acciones sensibles reales.

## Subdocumentos puntuales

Usa este archivo como tablero. Para trabajar cada parte sin releer todo el proyecto, abre solo el documento puntual del paso activo:

- Paso 1, esquema: `docs/erp_seguridad_esquema_trabajo.md`.
- Paso 2, roles base: `docs/erp_seguridad_roles_base_trabajo.md`.
- Paso 3, permisos base: `docs/erp_seguridad_permisos_base_trabajo.md`.
- Paso 4, asignacion rol-permiso: `docs/erp_seguridad_roles_permisos_trabajo.md`.
- Paso 5, endpoints: `docs/erp_seguridad_endpoints_trabajo.md`.
- Paso 6, UI usuarios/roles: `docs/erp_seguridad_ui_usuarios_roles_trabajo.md`.
- Paso 7, auditoria: `docs/erp_seguridad_auditoria_trabajo.md`.
- Paso 8, cierre: `docs/erp_seguridad_cierre_trabajo.md`.
- Paso 9, editar usuario interno: `docs/erp_seguridad_usuarios_editar_trabajo.md`.

Regla de uso: cuando un paso quede terminado, marca su estado en este archivo y deja la evidencia minima en notas. Si aparece una regla de negocio que no esta definida, se pregunta al dueno antes de implementarla.

## Orden recomendado

### 1. Auditar esquema de seguridad

- Estado: [x]
- Nivel IA sugerido: D.
- Documento puntual: `docs/erp_seguridad_esquema_trabajo.md`.
- Archivos:
  - `app/modelos/SeguridadEsquema.php`
  - `app/modelos/SeguridadPermisos.php`
- Terminado cuando:
  - Tablas requeridas existen o tienen plan de actualizacion.
  - Roles/permisos base estan definidos.
  - No falta indice critico.

Notas:

- 2026-06-10: Auditoria local contra `artianilocal` completada.
- Existen las tablas base: `sys_roles`, `sys_permisos`, `sys_roles_permisos`, `sys_usuarios_roles`, `sys_auditoria_eventos`.
- Indices criticos presentes: roles por `rol`, permisos por `permiso` y `modulo/accion`, rol-permiso unico por `id_rol/id_permiso`, usuario-rol unico por `id_usuario/id_rol`, auditoria por usuario, modulo/accion, entidad y fecha.
- No se detectaron permisos ni roles duplicados.
- Dry-run de `SeguridadEsquema::planActualizarSeguridad(false)` generado sin errores: 172 pasos.
- Semillas actuales observadas: `administrador_erp` 26 permisos, `compras` 13 permisos, `soporte_sistema` 13 permisos.
- Usuario 1 activo con roles `administrador_erp` y `soporte_sistema`.
- Riesgo trasladado a Paso 5: `Sistema::esquema_actualizar_seguridad()` esta protegido por sesion, pero aun debe exigir permiso explicito `sistema.soporte` o `seguridad.administrar` antes de permitir ejecutar el plan.

### 2. Auditar roles base

- Estado: [x]
- Nivel IA sugerido: D.
- Documento puntual: `docs/erp_seguridad_roles_base_trabajo.md`.
- Objetivo: confirmar roles reales del negocio.
- Roles actuales a revisar:
  - direccion
  - administrador_erp
  - compras
  - almacen
  - inventario
  - ventas
  - ecommerce
  - catalogo_productos
  - finanzas_contabilidad
  - auditor
  - solo_lectura
  - soporte_sistema
- Terminado cuando:
  - Roles representan personas reales o futuras.
  - No hay roles redundantes/confusos.
  - Cada rol tiene descripcion clara.

Notas:

- 2026-06-10: Paso cerrado como definicion base de roles.
- Roles activos en base local: 12/12 definidos en `SeguridadEsquema::rolesBaseERP()`.
- Todos tienen descripcion y `estatus=1`.
- Usuarios activos asignados: usuario 1 tiene `administrador_erp` y `soporte_sistema`; el resto de roles estan listos pero sin usuarios activos.
- No se detectan roles tecnicamente duplicados.
- `compras` queda definido como proveedores, solicitudes, ordenes y seguimiento; pagos/notas se resuelven en Paso 4 por permiso.
- `direccion` queda definido como consulta ejecutiva, aprobaciones y reportes; no como administrador de usuarios.
- `ecommerce` se conserva como rol separado para canal digital futuro.
- `soporte_sistema` queda definido como diagnostico tecnico, auditoria de esquema y soporte; ejecucion real depende de `sistema.soporte`.

### 3. Auditar permisos base

- Estado: [x]
- Nivel IA sugerido: D.
- Documento puntual: `docs/erp_seguridad_permisos_base_trabajo.md`.
- Objetivo: confirmar permisos por modulo.
- Modulos actuales:
  - seguridad
  - configuracion
  - catalogo
  - compras
  - almacen
  - inventario
  - ventas
  - ecommerce
  - finanzas
  - auditoria
  - reportes
  - sistema
- Terminado cuando:
  - Cada permiso tiene modulo, accion, descripcion.
  - Compras/Catalogo/Almacen/Finanzas tienen permisos suficientes.
  - No hay permisos demasiado amplios para acciones sensibles.

Notas:

- 2026-06-10: Auditoria de permisos base completada contra semillas, base local y usos `requerirPermiso()`.
- Permisos activos en base local: 26.
- Modulos cubiertos: seguridad, configuracion, catalogo, compras, almacen, inventario, ventas, ecommerce, finanzas, auditoria, reportes, sistema.
- Compras esta separado en `compras.ver`, `compras.crear`, `compras.editar`, `compras.aprobar`, `compras.cancelar`, `compras.adjuntos`.
- Finanzas esta separado en `finanzas.ver` y `finanzas.operar`.
- Almacen e Inventario tienen permisos operativos separados.
- No se detectaron permisos usados por `requerirPermiso()` que falten en `sys_permisos`.
- Observacion de diseno: Catalogo usa `catalogo.editar` para varias acciones de configuracion/organizacion; evaluar `catalogo.configurar` si se quiere separar configuracion de edicion operativa.
- Riesgo trasladado a Paso 5: hay endpoints/controladores con cobertura parcial o legado que deben auditarse por ruta, no por existencia de permiso.

### 4. Auditar asignacion rol-permiso

- Estado: [x]
- Nivel IA sugerido: D.
- Documento puntual: `docs/erp_seguridad_roles_permisos_trabajo.md`.
- Objetivo: revisar permisos asignados a cada rol.
- Terminado cuando:
  - Compras no tiene poderes innecesarios si el negocio quiere separar finanzas.
  - Catalogo puede resolver productos/fiscales.
  - Almacen puede recibir sin operar compras.
  - Finanzas puede operar pagos/notas.
  - Auditor puede ver sin modificar.
  - Administrador conserva seguridad.

Notas:

- 2026-06-10: Matriz rol-permiso cerrada en `SeguridadEsquema::permisosPorRolBaseERP()` con criterio conservador.
- Correcto: `administrador_erp` conserva `seguridad.ver` y `seguridad.administrar`.
- Correcto: `auditor` no tiene permisos de modificacion operativa.
- Correcto: `almacen` puede consultar compras, pero no crear, aprobar, cancelar ni operar finanzas.
- Correcto: `inventario` no opera compras ni finanzas.
- Corregido: `compras` queda sin `finanzas.operar`; conserva `finanzas.ver`.
- Corregido: `finanzas_contabilidad` queda sin `compras.aprobar`; conserva `finanzas.operar`.
- Corregido: `direccion` queda sin `configuracion.administrar`; conserva aprobacion/consulta/reportes.
- Corregido: `soporte_sistema` queda sin permisos operativos de compras/finanzas; conserva `sistema.soporte`, `seguridad.ver`, `auditoria.ver` y `reportes.ver`.
- Nota: la semilla usa `INSERT IGNORE`; en bases existentes se debe sincronizar `sys_roles_permisos` desde UI o migracion puntual para retirar asignaciones antiguas.

### 5. Auditar controladores/endpoints

- Estado: [x]
- Nivel IA sugerido: D.
- Documento puntual: `docs/erp_seguridad_endpoints_trabajo.md`.
- Objetivo: detectar endpoints sin permiso puntual.
- Archivos iniciales:
  - `app/controladores/Sistema.php`
  - `app/controladores/CatalogoErp.php`
  - `app/controladores/Compra.php`
  - `app/controladores/Almacen.php`
  - `app/controladores/Inventario.php`
  - `app/controladores/Proveedor.php`
- Terminado cuando:
  - Cada endpoint sensible tiene permiso.
  - Endpoints legados quedan marcados como pendientes o protegidos.
  - No se depende solo de constructor con `$_SESSION`.

Notas:

- `Proveedor.php` fue protegido con sesion centralizada y permisos puntuales de Compras; aun conviene modernizarlo antes de usarlo como base ERP nueva.
- 2026-06-10: Auditoria inicial de endpoints completada para controladores principales.
- Correcto: `Inventario.php` nuevo usa `requerirSesion()` y permisos puntuales; endpoints legados devuelven 409 con `legadoDeshabilitado()`.
- Correcto en general: `CatalogoErp.php` usa `catalogo.ver`, `catalogo.editar` y `catalogo.costos` en endpoints revisados.
- Correcto en general: `Compra.php` nuevo ya separa ver/crear/editar/aprobar/cancelar/adjuntos/finanzas; metodos legados principales devuelven 409 antes de operar.
- Corregido 2026-06-10: `Sistema::esquema_actualizar_seguridad()` exige `sistema.soporte`.
- Corregido 2026-06-10: `Almacen::esquema_auditar_almacen_inventario()` y `Almacen::esquema_actualizar_almacen_inventario()` exigen `sistema.soporte`.
- Corregido 2026-06-10: `Almacen::consultar_almacenes()` exige `almacen.ver`.
- Corregido 2026-06-10: `Compra::esquema_actualizar_orden_compra()` exige `sistema.soporte`.
- Corregido 2026-06-10: `Compra::enriquecer_productos_compra()` exige `compras.ver`.
- Corregido 2026-06-10: metodos mixtos de `Compra.php` `subir_adjuntos_orden_compra()` y `consultar_orden_de_compra()` exigen `compras.adjuntos` y `compras.ver`.
- Corregido 2026-06-10: `Proveedor.php` usa `requerirSesion()`, protege endpoints publicos con `compras.ver`, `compras.crear` o `compras.editar`, y convierte helpers internos en `private`.
- Nota: Proveedores sigue siendo modulo legado; para ERP nuevo conviene separar controlador/modelo propio, pero ya no queda abierto sin permiso puntual.

### 6. Auditar UI de usuarios/roles

- Estado: [x]
- Nivel IA sugerido: C.
- Documento puntual: `docs/erp_seguridad_ui_usuarios_roles_trabajo.md`.
- Archivos:
  - `app/vistas/paginas/apps/erp/seguridad/usuarios_roles.php`
  - JS relacionado si existe.
- Terminado cuando:
  - Se pueden consultar roles.
  - Se pueden asignar/quitar roles.
  - Se pueden guardar permisos por rol.
  - Se pueden activar/desactivar usuarios.
  - Mensajes son claros.

Notas:

- 2026-06-10: UI auditada en `usuarios_roles.php` y `users-roles.js`.
- Correcto: se listan roles y usuarios con roles.
- Correcto: se consultan permisos por rol agrupados por modulo.
- Correcto: se puede guardar la seleccion de permisos por rol con confirmacion previa.
- Correcto: si el usuario no tiene `seguridad.administrar`, la UI deja permisos y asignacion en solo consulta.
- Correcto: auditoria reciente solo se muestra si existe `auditoria.ver`.
- Corregido 2026-06-10: boton `Nuevo usuario` solo aparece con `seguridad.administrar`, ya no apunta a `/autenticacion/registro` y abre alta interna protegida.
- Corregido 2026-06-10: `Sistema::seguridad_usuario_crear()` crea usuario, asigna rol inicial opcional y audita sin registrar contrasena.
- Corregido 2026-06-10: `users-roles.js` envia `X-CSRF-Token` en POST protegidos.
- Corregido 2026-06-10: registro legacy deshabilitado; `/autenticacion/registro` redirige a Seguridad y `/autenticacion/registrar_usuario` responde 410.
- Corregido 2026-06-10: quitar rol y cambiar estatus de usuario requieren confirmacion.
- Corregido 2026-06-10: selector principal de roles muestra clave, descripcion y total de permisos.
- Corregido 2026-06-10: accion `Editar` permite actualizar datos base y perfil robusto inicial de usuario desde Seguridad.
- Corregido 2026-06-10: se audito `sys_usuarios`; ya existian `alias`, `correo` y `telefono`.
- Corregido 2026-06-10: se agregaron al esquema y base local `nombre_mostrar`, `area_departamento`, `puesto`, `telefono_secundario` y `notas_admin`.
- Pendiente futuro: filtros/paginacion y CRUD de roles.
- Pendiente medio: no hay paginacion; aceptable para pocos usuarios, pero se debe resolver antes de crecer.

### 7. Auditar auditoria

- Estado: [x]
- Nivel IA sugerido: D.
- Documento puntual: `docs/erp_seguridad_auditoria_trabajo.md`.
- Objetivo: asegurar trazabilidad minima.
- Terminado cuando:
  - Seguridad registra asignacion/quitar rol, permisos, estatus usuario.
  - Compras registra aprobaciones, cancelaciones, pagos, adjuntos y XML.
  - Catalogo registra cambios maestros.
  - Auditoria se puede consultar por permiso `auditoria.ver`.

Notas:

- 2026-06-10: Auditoria revisada en `SesionSeguridad`, `Core`, `Sistema`, `Compra`, `CatalogoErp`, `Almacen` e `Inventario`.
- Correcto: tabla `sys_auditoria_eventos` existe y registra usuario, modulo, accion, entidad, resultado, IP, user agent, datos y fecha.
- Correcto: Seguridad registra asignar/quitar rol, actualizar permisos de rol y estatus de usuario.
- Correcto: `Controlador::requerirPermiso()` registra `permiso_denegado`; CSRF invalido se registra en `SesionSeguridad`.
- Correcto: Compras ERP registra solicitudes, ordenes, pagos/notas, adjuntos y XML.
- Correcto: Catalogo ERP registra resolucion de incidencias, fusiones, costos, metadatos, taxonomia, productos, SKUs e imagenes.
- Correcto: Almacen registra recepcion; Inventario registra ajustes y traspasos.
- Corregido 2026-06-10: `listarAuditoria()` acepta filtros por usuario, modulo, accion, resultado, fecha desde y fecha hasta.
- Corregido 2026-06-10: la UI de Seguridad permite filtrar actividad reciente.
- Corregido 2026-06-10: endpoints legacy de `Proveedor.php` quedaron protegidos por permisos en Paso 5.
- Pendiente futuro: fortalecer `datos_antes` por modulo y agregar paginacion real si la tabla crece mucho.

### 8. Cierre de seguridad base

- Estado: [x]
- Documento puntual: `docs/erp_seguridad_cierre_trabajo.md`.
- Terminado cuando:
  - Roles/permisos estan confirmados.
  - Usuarios pueden asignarse a roles.
  - Endpoints sensibles tienen permiso.
  - Auditoria basica funciona.
  - Administrador queda protegido.
  - Hay decision sobre permisos de Finanzas para Compras.

Notas:

- 2026-06-10: Seguridad/Roles queda cerrado como base tecnica para continuar ERP.
- Matriz conservadora sincronizada en `sys_roles_permisos`.
- Base local verificada: `administrador_erp` 26 permisos, `direccion` 12, `compras` 12, `finanzas_contabilidad` 8, `soporte_sistema` 4.
- Verificaciones de sintaxis PHP/JS ejecutadas correctamente.
- Pendientes de fase avanzada: paginacion real de auditoria y `datos_antes` mas completo por modulo.

### 9. Editar usuario interno

- Estado: [x]
- Nivel IA sugerido: C.
- Documento puntual: `docs/erp_seguridad_usuarios_editar_trabajo.md`.
- Objetivo: permitir que un administrador edite datos base de usuario desde Seguridad sin tocar base de datos ni usar registro legacy.
- Depende de: UI usuarios/roles, endpoints protegidos, auditoria.
- Terminado cuando:
  - Existe accion `Editar` por usuario solo para `seguridad.administrar`.
  - Backend permite actualizar datos base existentes: `nombres`, `apellido_paterno`, `apellido_materno`, `celular` y `estatus` con validaciones.
  - Se audita y define si se agregan campos robustos de perfil: `correo`, `usuario`/`alias`, `nombre_mostrar`, `area_departamento`, `puesto`, `telefono_secundario` y `notas_admin`.
  - Si el usuario requiere operar almacen/inventario, se planea alcance por almacen en tabla separada, no como campo obligatorio del usuario.
  - La contrasena puede restablecerse desde la edicion de usuario si el operador captura y confirma una nueva; si se deja vacia, se conserva la actual.
  - No se duplica `celular`, `correo` o `alias` cuando apliquen.
  - Cambio queda auditado como `seguridad.editar_usuario`.
  - Listado refleja cambios.

Notas:

- 2026-06-10: Pendiente agregado por observacion del dueno.
- 2026-06-10: Ajuste de alcance: el ERP contempla perfil de usuario robusto. `alias`, `correo` y `telefono` ya existian; se agregaron `nombre_mostrar`, `area_departamento`, `puesto`, `telefono_secundario` y `notas_admin`.
- 2026-06-10: `almacen_principal` no debe ser campo base del usuario; para almacen/inventario se debe planear alcance por almacen en tabla separada.
- 2026-06-10: Implementado `Sistema::seguridad_usuario_editar()` y `SeguridadPermisos::actualizarUsuarioInterno()`.
- 2026-06-10: UI agrega boton `Editar` por usuario con `seguridad.administrar`.
- 2026-06-10: Edicion permite `nombres`, `apellido_paterno`, `apellido_materno`, `celular`, `estatus`, `alias`, `correo`, `telefono`, `nombre_mostrar`, `area_departamento`, `puesto`, `telefono_secundario` y `notas_admin`.
- 2026-06-10: Se validan `celular`, `alias` y `correo` contra duplicados.
- 2026-06-10: Se audita como `seguridad.editar_usuario` con `datos_antes` y `datos_despues`.
- 2026-06-10: `Sistema.seguridad_usuario_editar` agregado a auditoria explicita de `Core.php`.
- 2026-07-22: Se habilito restablecimiento opcional de contrasena desde `Editar / contrasena`; backend valida minimo 8 caracteres, confirmacion y persiste solo hash.
- 2026-07-22: Auditoria de edicion solo registra bandera `contrasenia_actualizada`, sin contrasena ni hash.
- 2026-07-22: `SesionSeguridad` conserva datos de perfil en sesion (`alias`, `correo`, `nombre_mostrar`, `area_departamento`, `puesto`) para consumo de layout.
- 2026-07-22: Navbar de usuario deja de usar datos demo; ahora renderiza nombre/rol de sesion y enlaces internos a Seguridad, Notificaciones y Cerrar sesion.

## Prompt recomendado para empezar

```text
Trabaja solo en ERP > Seguridad y roles > Auditoria inicial.
Usa AGENTS.md, docs/erp_plan_maestro_fundamentos.md, docs/ia_uso_modelos.md, docs/erp_ux_operativa.md, docs/erp_seguridad_roles_avance.md y el documento puntual del paso activo.
Objetivo: auditar esquema, roles, permisos y endpoints sensibles para detectar que falta antes de seguir con Catalogo/Compras.
No implementes cambios todavia; entrega hallazgos, riesgos y orden de implementacion.
Criterio: actualizar docs/erp_seguridad_roles_avance.md con notas concretas por cada punto.
```

## Ultimo avance registrado

- Fecha: 2026-07-22.
- Pasos cerrados: 1. Esquema; 2. Roles base; 3. Permisos base; 4. Asignacion rol-permiso; 5. Endpoints; 6. UI usuarios/roles; 7. Auditoria; 8. Cierre; 9. Editar usuario interno.
- Evidencia: tablas/indices verificados en MySQL local, duplicados revisados, dry-run de seguridad sin errores, matriz rol-permiso sincronizada, endpoints protegidos, UI y auditoria corregidas, edicion base y perfil robusto inicial de usuarios implementado. El 2026-07-22 se agrego restablecimiento opcional de contrasena y navbar funcional con datos de sesion.
- Siguiente paso recomendado: probar alta/edicion/restablecimiento/asignacion con un usuario real de rol administrativo y despues validar un usuario `compras`.
