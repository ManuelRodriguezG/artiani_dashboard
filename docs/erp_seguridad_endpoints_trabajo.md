# ERP Seguridad - Endpoints y controladores

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Cerrado para controladores auditados; queda pendiente modernizacion funcional de Proveedores  
Aplica a: proteccion de rutas, permisos y endpoints sensibles

## Proposito

Auditar que los endpoints del ERP no dependan solo de sesion manual y que cada accion sensible valide permiso en backend.

## Archivos iniciales

- `app/core/Core.php`
- `app/core/Controlador.php`
- `app/core/SesionSeguridad.php`
- `app/controladores/Sistema.php`
- `app/controladores/CatalogoErp.php`
- `app/controladores/Compra.php`
- `app/controladores/Almacen.php`
- `app/controladores/Inventario.php`
- `app/controladores/Proveedor.php`

## Reglas

- Todo controlador ERP debe requerir sesion.
- Todo endpoint sensible debe requerir permiso puntual.
- No basta ocultar botones.
- POST sensible debe validar CSRF.
- Acciones sensibles deben auditarse.
- Endpoints legados deben marcarse como protegidos, deshabilitados o pendientes.

## Riesgo ya observado

- Algunos controladores antiguos usan `if (!$_SESSION['id_usuario'])` en constructor.
- Algunos endpoints de `Proveedor.php` no muestran permiso puntual.
- Antes de usar Proveedores como base ERP, hay que proteger endpoints y separar legado/nuevo.

## Clasificacion de endpoints

- Consulta simple: requiere permiso `*.ver`.
- Crear/editar: requiere `*.crear` o `*.editar`.
- Editar usuarios de Seguridad: requiere `seguridad.administrar`.
- Aprobar/enviar: requiere `*.aprobar`.
- Cancelar: requiere `*.cancelar`.
- Operacion financiera: requiere `finanzas.operar`.
- Esquema/soporte: requiere `sistema.soporte`.
- Auditoria: requiere `auditoria.ver`.

## Criterios de terminado

- Endpoints ERP sensibles tienen `requerirPermiso`.
- Endpoints legacy quedan documentados.
- No hay acciones POST operativas sin CSRF.
- Auditoria cubre cambios sensibles.

## Resultado de auditoria inicial 2026-06-10

Estado: cerrado para proteccion de permisos.

Controladores con cobertura aceptable:

- `Inventario.php`: flujo ERP nuevo protegido con `inventario.ver`, `inventario.ajustar`, `inventario.traspasar`; endpoints legados devuelven 409.
- `CatalogoErp.php`: endpoints revisados usan `catalogo.ver`, `catalogo.editar` o `catalogo.costos`.
- `Compra.php`: flujo ERP nuevo usa permisos separados para compras y finanzas; varios metodos legados devuelven 409 antes de operar.

Pendientes por controlador:

| Controlador | Endpoint | Riesgo | Permiso recomendado | Prioridad |
| --- | --- | --- | --- | --- |
| `Sistema` | `esquema_actualizar_seguridad` | Ejecuta o prepara cambios de seguridad solo con sesion | Corregido: `sistema.soporte` | Cerrado |
| `Almacen` | `esquema_auditar_almacen_inventario` | Expone auditoria tecnica de esquema | Corregido: `sistema.soporte` | Cerrado |
| `Almacen` | `esquema_actualizar_almacen_inventario` | Puede ejecutar cambios de esquema | Corregido: `sistema.soporte` | Cerrado |
| `Almacen` | `consultar_almacenes` | Consulta catalogo operativo sin permiso puntual | Corregido: `almacen.ver` | Cerrado |
| `Compra` | `esquema_actualizar_orden_compra` | Puede ejecutar plan de esquema de compras | Corregido: `sistema.soporte` | Cerrado |
| `Compra` | `enriquecer_productos_compra` | Expone/enriquece datos de productos/proveedor | Corregido: `compras.ver` | Cerrado |
| `Compra` | `subir_adjuntos_orden_compra` | Metodo legado/mixto sin permiso antes de operar | Corregido: `compras.adjuntos` | Cerrado |
| `Compra` | `consultar_orden_de_compra` | Metodo legado/mixto sin permiso puntual | Corregido: `compras.ver` | Cerrado |
| `Proveedor` | multiples vistas y POST (`registrar`, `actualizar_pedido`, listas, pedidos, estatus, carga de listas) | Modulo legado con sesion manual y permisos incompletos | Corregido con `compras.ver`, `compras.crear`, `compras.editar` segun accion | Cerrado |

Notas:

- `Core.php` ya valida CSRF para POST autenticados; el riesgo principal aqui es permiso puntual y alcance legacy.
- Antes de integrar Proveedores al ERP nuevo, aun conviene crear un controlador/modelo ERP nuevo para proveedores/listas; por ahora el legado quedo protegido por permisos de Compras.
- Pendiente nuevo: agregar endpoint interno para editar usuario desde Seguridad, documentado en `docs/erp_seguridad_usuarios_editar_trabajo.md`.
- Si se agregan campos nuevos de perfil ERP, el endpoint debe validar esquema, unicidad, permisos y auditoria antes de exponerlos a UI.

Correccion aplicada 2026-06-10:

- Se agregaron permisos puntuales en `Sistema.php`, `Almacen.php` y metodos mixtos de `Compra.php`.
- Verificacion: `php -l` correcto en los tres controladores.

Correccion aplicada 2026-06-10 para Proveedor:

- `Proveedor::__construct()` usa `requerirSesion()` en lugar de validacion manual de `$_SESSION`.
- Vistas/listados/consultas usan `compras.ver`.
- Altas de proveedor, pedidos y generacion de orden usan `compras.crear`.
- Edicion, estatus, carga de listas, actualizacion de producto/lista e imagenes usan `compras.editar`.
- Helpers internos `crear_identificador`, `consultar_categorias_producto` y `arreglo_id_listas` dejaron de ser rutas publicas y ahora son `private`.
- Verificacion: `php -l app\controladores\Proveedor.php` correcto.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > Endpoints.
Objetivo: auditar controladores Sistema, CatalogoErp, Compra, Almacen, Inventario y Proveedor para detectar endpoints sin permiso puntual.
No implementes cambios todavia; entrega tabla endpoint, riesgo, permiso recomendado y prioridad.
Criterio: actualizar docs/erp_seguridad_roles_avance.md con hallazgos por controlador.
```
