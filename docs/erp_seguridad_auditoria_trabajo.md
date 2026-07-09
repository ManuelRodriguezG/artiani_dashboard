# ERP Seguridad - Auditoria

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Cerrado para auditoria base y consulta filtrable  
Aplica a: bitacora, trazabilidad y consulta de eventos

## Proposito

Definir auditoria minima para un ERP robusto: quien hizo que, cuando, sobre que entidad y con que resultado.

## Archivos

- `app/core/SesionSeguridad.php`
- `app/modelos/SeguridadPermisos.php`
- `app/modelos/SeguridadEsquema.php`
- `app/controladores/Sistema.php`
- Controladores de modulos: `Compra.php`, `CatalogoErp.php`, `Almacen.php`, `Inventario.php`.

## Eventos minimos

Seguridad:

- Asignar rol.
- Quitar rol.
- Cambiar permisos de rol.
- Activar/desactivar usuario.
- CSRF invalido.
- Permiso denegado.

Compras:

- Crear/editar solicitud.
- Aprobar/rechazar/cancelar solicitud.
- Generar orden.
- Guardar/enviar/cancelar orden.
- Registrar/cancelar pagos/notas.
- Subir/cancelar adjuntos.
- Importar/resolver XML.

Catalogo:

- Crear/editar producto/SKU.
- Resolver incidencia de migracion.
- Cambiar fiscales/costos.
- Fusionar productos.

Almacen/Inventario:

- Guardar recepcion.
- Ajustes.
- Traspasos.
- Incidencias.

## Campos requeridos

- Usuario.
- Modulo.
- Accion.
- Entidad.
- Entidad ID.
- Resultado.
- IP.
- User agent.
- Datos antes.
- Datos despues.
- Mensaje.
- Fecha.

## Permisos

- Ver auditoria: `auditoria.ver`.
- Soporte tecnico: `sistema.soporte` si se exponen filtros avanzados.

## Criterios de terminado

- Eventos sensibles se registran.
- Auditoria es consultable.
- No se registran contrasenas ni secretos.
- Errores tambien quedan trazables.

## Resultado de auditoria 2026-06-10

Estado: cerrado para cobertura base.

Cobertura observada:

- Seguridad:
  - `asignar_rol`.
  - `quitar_rol`.
  - `actualizar_permisos_rol`.
  - `actualizar_estatus_usuario`.
  - `permiso_denegado`.
  - `csrf_invalido`.
- Autenticacion:
  - `inicio_session`.
  - `reautenticar_session`.
  - `cerrar_session`.
- Compras ERP:
  - Solicitud guardar/cambiar estatus.
  - Orden guardar/generar/cancelar.
  - Pagos y notas.
  - Adjuntos.
  - XML importar/resolver/mover/descartar conceptos.
- Catalogo ERP:
  - Productos/SKUs/imagenes.
  - Incidencias de migracion.
  - Fusion de productos.
  - Costos/proveedor.
  - Metadatos, taxonomia y categorias.
- Almacen/Inventario:
  - Recepcion.
  - Ajustes.
  - Traspasos.

Evidencia local:

- `sys_auditoria_eventos` contiene eventos recientes de autenticacion, catalogo, compras, ventas y seguridad.
- Se observan resultados `ok` y `error`, lo que confirma trazabilidad de fallos.
- `listarAuditoria()` permite consultar los ultimos eventos con `auditoria.ver`.

Corregido 2026-06-10:

- `SeguridadPermisos::listarAuditoria()` acepta filtros por usuario, modulo, accion, resultado, fecha desde y fecha hasta.
- `Sistema::seguridad_auditoria_listar()` expone esos filtros con `auditoria.ver`.
- La UI de Seguridad agrega filtros sobre actividad reciente.
- Endpoints legacy de `Proveedor.php` quedaron protegidos por permisos en Paso 5, por lo que no dependen solo de auditoria generica.
- Verificaciones: `php -l` correcto en `SeguridadPermisos.php`, `Sistema.php`, vista de seguridad y `node --check` correcto en `users-roles.js`.

Pendientes de fase avanzada:

- Fortalecer `datos_antes` en cambios de catalogo, compras, configuracion y entidades criticas.
- Agregar paginacion real con total de registros si la tabla de auditoria crece mucho.
- Revisar periodicamente la lista de auditoria explicita en `Core.php` cuando se agreguen endpoints sensibles nuevos.

## Prompt puntual

```text
Trabaja solo en ERP > Seguridad > Auditoria.
Objetivo: auditar cobertura de SesionSeguridad::registrarAuditoria en seguridad, compras, catalogo, almacen e inventario.
No registres secretos ni contrasenas.
Criterio: entregar lista de eventos cubiertos, faltantes y prioridad de implementacion.
```
