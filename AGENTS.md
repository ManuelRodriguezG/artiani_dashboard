# AGENTS.md - Guia de arquitectura e indexacion critica

## Proyecto activo y ruta canonica

- La ruta activa de este proyecto es `C:\xampp\htdocs\panel_de_control`.
- El host local canonico es `http://panel.com.local/`.
- No realizar cambios en `C:\xampp\htdocs\panel` para este proyecto salvo que el dueno lo pida explicitamente.
- Si la sesion, IDE o herramienta muestra otra ruta, confirmar y usar `C:\xampp\htdocs\panel_de_control` como `workdir` antes de editar.
- Los documentos, controladores, modelos, vistas y assets nuevos de ERP/POS deben crearse en `panel_de_control`.
Este archivo es la puerta de entrada para agentes que trabajen en este proyecto. Su objetivo es reducir lecturas repetidas, ubicar rapido las reglas de negocio y evitar inventar decisiones. Si una regla de negocio no esta clara en codigo o docs, pregunta al dueno del proyecto antes de implementarla.

## Lectura inicial recomendada

1. Lee este archivo completo.
2. Para plan maestro de ERP, lee `docs/erp_plan_maestro_fundamentos.md`.
3. Para Compras, lee despues `docs/erp_compras_vision_operativa.md` y, si necesitas plan/tareas, `docs/erp_compras_plan_modulo.md`.
4. Para notificaciones/alertas operativas transversales, lee `docs/erp_notificaciones_alertas_trabajo.md`.
5. Para configurar el entorno local, lee `docs/entorno_local_xampp_panel.md`.
6. Usa busquedas puntuales por controlador, modelo, endpoint o metodo antes de abrir archivos grandes.
7. No abras `dbesquema/artianilocal.sql` salvo que necesites validar una tabla/columna/indice especifico.
8. No copies ni expongas credenciales desde `app/config/mysql.php`; solo menciona que define la conexion segun `SERVER_NAME`.

## Arquitectura base

- Proyecto PHP MVC propio bajo XAMPP/cPanel, sin framework externo evidente.
- `public/index.php` es el front controller: incluye `../app/iniciador.php` y crea `new Core`.
- `app/iniciador.php` carga configuracion, conexion MySQL y autoload para clases de `app/core`.
- `app/core/Core.php` resuelve rutas con el formato `/controlador/metodo/parametros`.
- Controlador por defecto: `Inicio`; metodo por defecto: `index`.
- Los controladores viven en `app/controladores` y normalmente extienden `Controlador`.
- `app/core/Controlador.php` carga modelos desde `app/modelos` y vistas desde `app/vistas/paginas`.
- Los modelos de datos normalmente extienden `CRUD`, que hereda conexion PDO desde `MySqlDB`.
- Las clases `*Esquema.php` normalmente extienden `DBSchema` para auditar o planear cambios de tablas, columnas e indices.
- Las vistas PHP estan en `app/vistas/paginas`; los JS de modulos ERP estan en `public/assets/js/custom/apps/erp`.

## Rutas, seguridad y reglas transversales

- `Core.php` decide controlador/metodo, instancia el controlador y ejecuta `call_user_func_array`.
- `Core.php` contiene una lista de controladores protegidos que requieren sesion.
- Para POST autenticados, `Core.php` valida CSRF salvo rutas exentas.
- `Core.php` tambien registra auditoria generica para POST autenticados cuando la accion no esta en la lista de auditoria explicita.
- `app/core/SesionSeguridad.php` concentra sesion, expiracion, CSRF, hash/verificacion de contrasenas, permisos en sesion y registro de auditoria.
- `Controlador::requerirPermiso($permiso)` valida permisos finos con `SeguridadPermisos` y responde JSON/403 segun el tipo de request.
- Antes de agregar un endpoint sensible:
  - Validar sesion o confirmar que el controlador ya esta protegido.
  - Validar CSRF para POST.
  - Validar permiso puntual si modifica datos o expone informacion sensible.
  - Registrar auditoria explicita cuando la accion tenga relevancia operativa.
- No inventes permisos nuevos sin revisar `SeguridadPermisos.php`, `SeguridadEsquema.php`, `Sistema.php` y las convenciones del modulo afectado.

## Puntos modulares de reglas de negocio

Las reglas de negocio deben vivir principalmente en modelos de dominio y controladores; las vistas y JS deben orquestar UI, captura y llamadas a endpoints.

- Controlador: valida request, permisos, sesion, transforma entrada basica, llama modelos, responde JSON/vista.
- Modelo de dominio: aplica reglas de negocio, transacciones, estatus, calculos y persistencia.
- Modelo `*Esquema`: define tablas, columnas, indices, auditorias y planes de actualizacion.
- Vista PHP: estructura visual y datos iniciales minimos.
- JS de modulo: UX, eventos, validacion de formulario de cliente, llamadas AJAX y render dinamico.

Cuando una regla involucre estados, dinero, inventario, XML fiscal, costos o permisos, revisa el modelo correspondiente antes de tocar solo el JS.

## Mapa de modulos criticos

### Compras

Archivos principales:

- `app/controladores/Compra.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/modelos/ComprasXmlErp.php`
- `app/modelos/PagosCompraErp.php`
- `app/modelos/AdjuntosCompraErp.php`
- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`
- `app/vistas/paginas/apps/erp/compras/solicitudes/listado.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `app/vistas/paginas/apps/erp/compras/ordenes/listado.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`
- `public/assets/js/custom/apps/erp/compras/solicitudes/listado.js`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`
- `public/assets/js/custom/apps/erp/compras/ordenes/listado.js`

Rutas/acciones ERP relevantes en `Compra.php`:

- Solicitudes: `solicitud_compra_nueva`, `mostrar_solicitud`, `editar_solicitud`, `mostrar_solicitudes`, `solicitudes_catalogos_erp`, `solicitudes_buscar_skus_erp`, `solicitudes_listar_erp`, `solicitud_consultar_erp`, `solicitud_guardar_erp`, `solicitud_estatus_erp`.
- Ordenes: `mostrar_compra_ordenes`, `crear_orden_compra`, `nueva_orden_compra`, `editar_orden_compra`, `ver_orden_compra`, `ordenes_catalogos_erp`, `ordenes_listar_erp`, `orden_buscar_skus_erp`, `orden_generar_desde_solicitud_erp`, `orden_consultar_erp`, `orden_guardar_erp`, `orden_cancelar_erp`.
- Finanzas/adjuntos/XML: `orden_finanzas_consultar_erp`, `orden_pago_registrar_erp`, `orden_pago_cancelar_erp`, `orden_nota_credito_registrar_erp`, `orden_nota_credito_cancelar_erp`, `orden_adjuntos_listar_erp`, `orden_adjunto_subir_erp`, `orden_adjunto_cancelar_erp`, `orden_adjunto_archivo_erp`, `orden_xml_importar_erp`, `orden_xml_parse_erp`, `orden_xml_listar_erp`, `orden_xml_conciliacion_erp`, `orden_xml_resolver_concepto_erp`, `orden_xml_mover_conceptos_erp`, `orden_xml_descartar_conceptos_erp`.
- Esquema/legado: `esquema_actualizar_orden_compra` y metodos legados posteriores. No mezclar flujo ERP nuevo con flujo legado sin revisar impacto.

Reglas observadas/documentadas:

- Crear/editar/ver/cancelar/adjuntar/pagar/aprobar son acciones separadas.
- No afectar inventario al crear o editar una orden; inventario se afecta en recepcion de almacen.
- Pagos y notas no deben borrarse fisicamente; se cancelan logicamente.
- Adjuntos cancelados conservan historial, pero el archivo fisico puede eliminarse para ahorrar almacenamiento.
- XML acelera captura y conciliacion, pero no debe destruir informacion importante de solicitud.
- Productos no encontrados, no surtidos o no relacionados deben generar pendientes accionables.
- Para reglas finas de estatus y permisos de Compras, revisar primero `docs/erp_compras_vision_operativa.md`.

### Almacen e inventario

Archivos principales:

- `app/controladores/Almacen.php`
- `app/controladores/Inventario.php`
- `app/modelos/Almacenes.php`
- `app/modelos/AlmacenEsquema.php`
- `app/modelos/InventarioErp.php`
- `app/vistas/paginas/apps/erp/almacen/mostrar_recepciones.php`
- `app/vistas/paginas/apps/erp/almacen/recibir.php`
- `app/vistas/paginas/apps/erp/inventarios/existencias.php`
- `app/vistas/paginas/apps/erp/inventarios/operacion.php`
- `public/assets/js/custom/apps/erp/almacen/mostrar_recepciones/listing_recepciones.js`
- `public/assets/js/custom/apps/erp/almacen/recibir/recibir.js`
- `public/assets/js/custom/apps/erp/inventarios/existencias_erp.js`
- `public/assets/js/custom/apps/erp/inventarios/operacion_erp.js`

Rutas relevantes:

- Almacen: `mostrar_recepciones`, `recibir`, `consultar_almacenes`, `obtener_recepciones`, `consultar_recepcion`, `guardar_recepcion`, `esquema_auditar_almacen_inventario`, `esquema_actualizar_almacen_inventario`.
- Inventario ERP: `productos_existencias`, `inicial`, `ajuste`, `transpaso`, `mostrar`, `transpasos`, `editar`, `editar_transpaso`, `buscar_skus_erp`, `catalogos_erp`, `existencias_erp`, `movimientos_erp`, `ajustar_erp`, `traspasar_erp`.

Notas:

- `Inventario.php` tiene varios endpoints legados deshabilitados con `legadoDeshabilitado`; no reactivarlos sin confirmacion del dueno.
- `Almacenes.php` contiene la logica critica de recepcion, lotes, ubicaciones, existencias, movimientos e incidencias.
- Si una orden enviada prepara recepcion de almacen, revisar interaccion entre `Compra.php`, `OrdenesCompraErp.php` y `Almacenes.php`.

### Catalogo ERP

Archivos principales:

- `app/controladores/CatalogoErp.php`
- `app/modelos/CatalogoErpDatos.php`
- `app/modelos/CatalogoErpEsquema.php`
- `app/modelos/CatalogoErpMigracionEcommerce.php`
- `app/modelos/CatalogoErpOrganizacion.php`
- `app/vistas/paginas/apps/erp/catalogo/productos.php`
- `app/vistas/paginas/apps/erp/catalogo/configuracion.php`
- `app/vistas/paginas/apps/erp/catalogo/migracion_ecommerce.php`
- `app/vistas/paginas/apps/erp/catalogo/organizacion.php`
- `public/assets/js/custom/apps/erp/catalogo/productos.js`
- `public/assets/js/custom/apps/erp/catalogo/configuracion.js`
- `public/assets/js/custom/apps/erp/catalogo/migracion_ecommerce.js`
- `public/assets/js/custom/apps/erp/catalogo/organizacion.js`

Zonas de negocio:

- Alta/edicion de productos ERP, SKU base, SKU proveedor, imagenes, variantes, catalogos auxiliares.
- Migracion desde ecommerce, incidencias de migracion, vinculacion con productos existentes.
- Calidad de catalogo, propuestas de costos, relaciones historicas de proveedor, reorden, metadatos y taxonomia.
- Preparacion de arbol de categorias y sincronizacion de relaciones.

### Seguridad y sistema

Archivos principales:

- `app/controladores/Sistema.php`
- `app/modelos/SeguridadPermisos.php`
- `app/modelos/SeguridadEsquema.php`
- `app/core/SesionSeguridad.php`
- `app/vistas/paginas/apps/erp/seguridad/usuarios_roles.php`

Rutas relevantes:

- Catalogos simples: `metodos_pago_listar`, `puntos_entrega_listar`.
- Esquema: `esquema_actualizar_seguridad`, `esquema_auditar_catalogo_erp`, `esquema_actualizar_catalogo_erp`.
- Seguridad: `seguridad`, `seguridad_roles_listar`, `seguridad_rol_permisos_consultar`, `seguridad_rol_permisos_guardar`, `seguridad_usuarios_roles_listar`, `seguridad_auditoria_listar`, `seguridad_usuario_rol_asignar`, `seguridad_usuario_rol_quitar`, `seguridad_usuario_estatus`.

### Costos, proveedores, utilidad, ventas y legado

- Costos: `app/controladores/Costo.php`, `app/modelos/Costos.php`, vistas/JS en `apps/erp/costos`.
- Proveedores/listas/pedidos: `app/controladores/Proveedor.php`, `app/modelos/Proveedores.php`, vistas/JS en `apps/erp/proveedores`.
- Utilidad: `app/controladores/Utilidad.php`, `app/modelos/Utilidades.php`, vistas/JS en `apps/erp/utilidad`.
- Ventas: `app/controladores/Ventas.php`, `app/modelos/Venta.php`, JS en `apps/erp/ventas`.
- Productos ecommerce/legado: `app/controladores/Producto.php`, `app/modelos/Productos.php`, ademas de controladores como `Categoria`, `Marca`, `Paquetes`, `Clientes`.

Muchos archivos de vistas ERP legadas son muy grandes. Antes de abrirlos, busca el asset JS o el endpoint exacto que necesitas.

## Convenciones de respuesta JSON

El patron comun de modelos/controladores devuelve arreglos con:

- `error`: booleano.
- `tipo`: `success`, `info`, `warning`, `danger` u otro tipo usado por UI.
- `mensaje`: texto para usuario.
- `depurar`: datos tecnicos o payload.

Conserva este contrato salvo que el modulo ya use otro formato.

## Esquema y base de datos

- `app/core/DBSchema.php` provee helpers para listar tablas, verificar columnas/indices y generar/ejecutar DDL.
- Los modelos `*Esquema.php` deben preferirse para evolucionar tablas del modulo.
- Los endpoints de esquema suelen separar auditoria de actualizacion.
- El dump `dbesquema/artianilocal.sql` es pesado y debe ser ultima opcion para busquedas puntuales.
- Si falta una tabla/columna, primero busca si existe un `*Esquema.php` que ya la declare.
- Para respaldos externos antes de DDL o scripts `apply_authorized`, usar la ruta estandar `C:\xampp\panel_db_backups` y documentar el archivo en `docs/erp_respaldo_bd_estandar.md`.

## Comandos y verificaciones utiles

- Sintaxis PHP puntual: `php -l ruta/al/archivo.php`.
- Sintaxis JS puntual: `node --check ruta/al/archivo.js`.
- Estado de cambios: `git status --short`.
- Diferencias puntuales: `git diff -- ruta/al/archivo`.

No ejecutes migraciones, escrituras masivas, formateadores o cambios de esquema sin que el usuario lo pida explicitamente.

## Reglas para agentes

- No inventes reglas de negocio. Si no estan en codigo/docs y afectan operacion, pregunta.
- No mezcles flujo ERP nuevo con flujo legado sin comprobar endpoints, modelos y docs vivas.
- No propagues secretos de configuracion.
- No cambies archivos no relacionados para "limpiar" el repo.
- Si hay cambios existentes del usuario, trabaja alrededor de ellos y no los reviertas.
- Para cambios de UI, revisa el JS del modulo y la vista asociada; para reglas, revisa controlador y modelo.
- Para formularios, tablas, cantidades, costos, descuentos, acciones visibles y mensajes, revisa `docs/erp_ux_operativa.md` antes de proponer o implementar.
- Para elegir profundidad de analisis/modelo y cuidar tokens, revisa `docs/ia_uso_modelos.md`.
- Si el usuario agrega una observacion reusable sobre arquitectura, negocio, UX, permisos o uso de IA, alimenta el documento vivo correspondiente y no lo dejes solo en la conversacion.
- No tomes sugerencias operativas del usuario como especificacion literal cuando afecten arquitectura de ERP. Interpreta la necesidad de fondo, propone una solucion robusta y documenta la decision antes de consolidarla. Ejemplo: "capturar telefono rapido" no significa que telefono sea el modelo de clientes; significa que POS necesita identificacion agil sin sacrificar un modulo futuro de clientes, precios, incentivos y trazabilidad.
- Para permisos, revisa `SeguridadPermisos.php`, `SeguridadEsquema.php`, `Core.php` y el controlador del modulo.
- Para auditoria, revisa primero `Core.php` y los helpers privados del controlador.
- Para alertas/notificaciones operativas, revisa `docs/erp_notificaciones_alertas_trabajo.md`; no uses auditoria ni chat como reemplazo de pendientes persistentes.
- Para Compras, respeta la documentacion viva en `docs/` y confirma con el dueno cualquier cambio de objetivo o politica.
- Para codigo nuevo o funciones modificadas por IA, documenta segun `docs/erp_estandar_documentacion_codigo.md`: version IA, fecha, proposito, impacto y contrato cuando aplique.

