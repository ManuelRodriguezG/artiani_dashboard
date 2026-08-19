<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-13
 * Proposito: declarar el indice navegable del buscador global del sistema.
 * Impacto: Header global; permite buscar pantallas reales respetando permisos de sesion.
 * Contrato: devuelve metadatos de navegacion; no consulta BD ni modifica datos.
 */

function erpHeaderCatalogoBusqueda()
{
    $grupos = array(
        array('seccion' => 'General', 'titulo' => 'Inicio', 'icono' => 'bi-speedometer2', 'items' => array(
            array('titulo' => 'Resumen', 'ruta' => '/', 'permiso' => '', 'detalle' => 'Tablero principal indicadores inicio dashboard')
        )),
        array('seccion' => 'Proyectos', 'titulo' => 'Proyectos', 'icono' => 'bi-kanban', 'items' => array(
            array('titulo' => 'Proyectos y tareas', 'ruta' => '/proyecto', 'permiso' => 'proyectos.ver', 'detalle' => 'Gestion de proyectos actividades pendientes')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Catalogo', 'icono' => 'bi-box-seam', 'items' => array(
            array('titulo' => 'Productos ERP', 'ruta' => '/catalogoerp', 'permiso' => 'catalogo.ver', 'detalle' => 'Productos SKU catalogo maestro'),
            array('titulo' => 'Organizacion catalogo', 'ruta' => '/catalogoerp/organizacion', 'permiso' => 'catalogo.ver', 'detalle' => 'Categorias taxonomia familias'),
            array('titulo' => 'Revision migracion', 'ruta' => '/catalogoerp/migracion_ecommerce', 'permiso' => 'catalogo.ver', 'detalle' => 'Migracion ecommerce productos incidencias'),
            array('titulo' => 'Configuracion catalogo', 'ruta' => '/catalogoerp/configuracion', 'permiso' => 'catalogo.editar', 'detalle' => 'Parametros catalogo ERP')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Comercial', 'icono' => 'bi-tags', 'items' => array(
            array('titulo' => 'Catalogos comerciales', 'ruta' => '/catalogoerp/catalogos_comerciales', 'permiso' => 'catalogo.ver', 'detalle' => 'Marcas categorias comerciales'),
            array('titulo' => 'Listas de precios', 'ruta' => '/comercial/listas_precios', 'permiso' => 'ventas.listas.ver', 'detalle' => 'Precios clientes listas comerciales'),
            array('titulo' => 'Manual listas de precios', 'ruta' => '/comercial/listas_precios_manual', 'permiso' => 'ventas.listas.ver', 'detalle' => 'Ayuda listas precios')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Rentabilidad', 'icono' => 'bi-graph-up-arrow', 'items' => array(
            array('titulo' => 'Resumen ejecutivo', 'ruta' => '/rentabilidad/analisis', 'permiso' => 'rentabilidad.ver', 'detalle' => 'Analisis rentabilidad margen utilidad'),
            array('titulo' => 'SKU y escenarios', 'ruta' => '/rentabilidad/skus', 'permiso' => 'rentabilidad.ver', 'detalle' => 'Escenarios SKU costos precios'),
            array('titulo' => 'Cierre comercial', 'ruta' => '/rentabilidad/cierre', 'permiso' => 'rentabilidad.ver', 'detalle' => 'Cierre resultados comercial'),
            array('titulo' => 'Aprobaciones', 'ruta' => '/rentabilidad/aprobaciones', 'permiso' => 'rentabilidad.ver', 'detalle' => 'Autorizaciones rentabilidad'),
            array('titulo' => 'Calidad de datos', 'ruta' => '/rentabilidad/calidad', 'permiso' => 'rentabilidad.ver', 'detalle' => 'Revision datos incidencias'),
            array('titulo' => 'Historial', 'ruta' => '/rentabilidad/historial', 'permiso' => 'rentabilidad.ver', 'detalle' => 'Historico rentabilidad'),
            array('titulo' => 'Manual de uso', 'ruta' => '/rentabilidad/manual', 'permiso' => 'rentabilidad.ver', 'detalle' => 'Ayuda rentabilidad')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Ventas y POS', 'icono' => 'bi-receipt', 'items' => array(
            array('titulo' => 'Tablero de ventas', 'ruta' => '/ventas/mostrar', 'permiso' => 'ventas.ver', 'detalle' => 'Ventas tablero indicadores'),
            array('titulo' => 'POS', 'ruta' => '/ventas/pos', 'permiso' => 'ventas.operar', 'detalle' => 'Punto de venta caja cobrar'),
            array('titulo' => 'Manual POS', 'ruta' => '/ventas/manual_pos', 'permiso' => 'ventas.ver', 'detalle' => 'Ayuda punto de venta'),
            array('titulo' => 'Checador de precios', 'ruta' => '/ventas/checador_precios', 'permiso' => 'ventas.ver', 'detalle' => 'Consultar precio SKU producto'),
            array('titulo' => 'Pendientes venta rapida', 'ruta' => '/ventas/venta_rapida_pendientes', 'permiso' => 'ventas.ver', 'detalle' => 'Ventas rapidas pendientes'),
            array('titulo' => 'Pedidos', 'ruta' => '/ventas/pedidos', 'permiso' => 'ventas.ver', 'detalle' => 'Pedidos ventas clientes'),
            array('titulo' => 'Devoluciones', 'ruta' => '/ventas/devoluciones', 'permiso' => 'ventas.ver', 'detalle' => 'Devoluciones ventas'),
            array('titulo' => 'Caja y turnos', 'ruta' => '/ventas/caja_turnos', 'permiso' => 'ventas.ver', 'detalle' => 'Turnos caja apertura cierre'),
            array('titulo' => 'Movimientos caja', 'ruta' => '/ventas/caja_movimientos', 'permiso' => 'ventas.ver', 'detalle' => 'Entradas salidas efectivo caja'),
            array('titulo' => 'Evidencias caja', 'ruta' => '/ventas/caja_evidencias', 'permiso' => 'ventas.ver', 'detalle' => 'Comprobantes evidencias caja'),
            array('titulo' => 'Reportes POS', 'ruta' => '/ventas/reportes', 'permiso' => 'ventas.ver', 'detalle' => 'Reportes punto venta'),
            array('titulo' => 'Configuracion POS', 'ruta' => '/ventas/pos_configuracion', 'permiso' => 'ventas.pos_config.ver', 'detalle' => 'Parametros punto venta')
        )),
        array('seccion' => 'TMS', 'titulo' => 'Delivery', 'icono' => 'bi-truck', 'items' => array(
            array('titulo' => 'Bandeja TMS', 'ruta' => '/tms/servicios', 'permiso' => 'tms.ver', 'detalle' => 'Servicios entregas delivery'),
            array('titulo' => 'Operacion y rutas', 'ruta' => '/tms/operacion', 'permiso' => 'tms.operar', 'detalle' => 'Rutas entregas operacion'),
            array('titulo' => 'Costos logisticos', 'ruta' => '/tms/costos', 'permiso' => 'tms.costos', 'detalle' => 'Costos fletes logistica'),
            array('titulo' => 'Reportes delivery', 'ruta' => '/tms/reportes', 'permiso' => 'tms.reportes', 'detalle' => 'Reportes TMS entregas'),
            array('titulo' => 'Configuracion delivery', 'ruta' => '/tms/configuracion', 'permiso' => 'tms.autorizar', 'detalle' => 'Parametros delivery TMS')
        )),
        array('seccion' => 'CRM', 'titulo' => 'CRM', 'icono' => 'bi-person-vcard', 'items' => array(
            array('titulo' => 'Clientes', 'ruta' => '/crm/clientes#crm_tab_clientes', 'permiso' => array('crm.ver', 'crm.clientes.ver'), 'detalle' => 'Clientes CRM contactos'),
            array('titulo' => 'Seguimiento', 'ruta' => '/crm/seguimiento', 'permiso' => array('crm.ver', 'crm.seguimiento.ver'), 'detalle' => 'Seguimiento clientes CRM'),
            array('titulo' => 'Comercial', 'ruta' => '/crm/comercial', 'permiso' => array('crm.ver', 'crm.comercial.ver'), 'detalle' => 'CRM comercial oportunidades'),
            array('titulo' => 'Recompensas', 'ruta' => '/crm/recompensas', 'permiso' => array('crm.ver', 'crm.recompensas.ver'), 'detalle' => 'Puntos recompensas clientes'),
            array('titulo' => 'Reportes', 'ruta' => '/crm/reportes', 'permiso' => array('crm.ver', 'crm.reportes.ver'), 'detalle' => 'Reportes CRM'),
            array('titulo' => 'Auditoria', 'ruta' => '/crm/clientes#crm_tab_auditoria', 'permiso' => 'crm.auditoria', 'detalle' => 'Auditoria clientes CRM')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Compras', 'icono' => 'bi-cart-check', 'items' => array(
            array('titulo' => 'Ordenes de compra', 'ruta' => '/compra/mostrar_compra_ordenes', 'permiso' => 'compras.ver', 'detalle' => 'Compras ordenes proveedores'),
            array('titulo' => 'Nueva orden', 'ruta' => '/compra/crear_orden_compra', 'permiso' => 'compras.crear', 'detalle' => 'Crear orden compra'),
            array('titulo' => 'Solicitudes', 'ruta' => '/compra/mostrar_solicitudes', 'permiso' => 'compras.ver', 'detalle' => 'Solicitudes requisiciones compra'),
            array('titulo' => 'Nueva solicitud', 'ruta' => '/compra/solicitud_compra_nueva', 'permiso' => 'compras.crear', 'detalle' => 'Crear solicitud compra'),
            array('titulo' => 'Documentos', 'ruta' => '/compra/documentos_configuracion', 'permiso' => 'compras.editar', 'detalle' => 'Configuracion documentos compras')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Proveedores', 'icono' => 'bi-truck', 'items' => array(
            array('titulo' => 'Maestro proveedores', 'ruta' => '/proveedor/mostrar_proveedores_erp', 'permiso' => 'proveedores.ver', 'detalle' => 'Proveedores maestro datos'),
            array('titulo' => 'Analisis abastecimiento', 'ruta' => '/proveedor/analisis_abastecimiento_erp', 'permiso' => 'proveedores.ver', 'detalle' => 'Abastecimiento proveedores compras'),
            array('titulo' => 'Manual de uso', 'ruta' => '/proveedor/manual_erp', 'permiso' => 'proveedores.ver', 'detalle' => 'Ayuda proveedores'),
            array('titulo' => 'Auditoria proveedores', 'ruta' => '/proveedor/auditoria_erp', 'permiso' => 'proveedores.auditoria', 'detalle' => 'Auditoria proveedor cambios'),
            array('titulo' => 'Proveedores legacy', 'ruta' => '/proveedor/listas_mostrar', 'permiso' => 'compras.ver', 'detalle' => 'Proveedores anterior listas')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Produccion', 'icono' => 'bi-tools', 'items' => array(
            array('titulo' => 'Peceras y vidrio', 'ruta' => '/produccion/peceras', 'permiso' => array('compras.ver', 'catalogo.ver', 'inventario.ver'), 'detalle' => 'Produccion peceras vidrio'),
            array('titulo' => 'Perfiles de peceras', 'ruta' => '/produccion/peceras_perfiles', 'permiso' => array('compras.ver', 'catalogo.ver', 'inventario.ver'), 'detalle' => 'Perfiles produccion peceras'),
            array('titulo' => 'Pedido multiple vidrio', 'ruta' => '/produccion/peceras_pedido_vidrio', 'permiso' => array('compras.ver', 'catalogo.ver', 'inventario.ver'), 'detalle' => 'Pedidos vidrio produccion')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Almacen', 'icono' => 'bi-building', 'items' => array(
            array('titulo' => 'Recepciones', 'ruta' => '/almacen/mostrar_recepciones', 'permiso' => 'almacen.ver', 'detalle' => 'Recibir mercancia ordenes'),
            array('titulo' => 'Resurtido', 'ruta' => '/almacen/resurtido', 'permiso' => 'almacen.ver', 'detalle' => 'Resurtido almacen'),
            array('titulo' => 'Preparacion/Empaque', 'ruta' => '/almacen/preparacion_empaque', 'permiso' => 'almacen.ver', 'detalle' => 'Preparacion empaque pedidos'),
            array('titulo' => 'Apertura de empaques', 'ruta' => '/almacen/apertura_empaques', 'permiso' => 'almacen.ver', 'detalle' => 'Abrir empaques almacen'),
            array('titulo' => 'Etiquetado', 'ruta' => '/almacen/etiquetado', 'permiso' => 'almacen.ver', 'detalle' => 'Etiquetas almacen productos'),
            array('titulo' => 'Configuracion', 'ruta' => '/almacen/configuracion', 'permiso' => 'almacen.ubicaciones', 'detalle' => 'Almacenes ubicaciones')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Inventario', 'icono' => 'bi-clipboard-data', 'items' => array(
            array('titulo' => 'Existencias', 'ruta' => '/inventario/productos_existencias', 'permiso' => 'inventario.ver', 'detalle' => 'Stock inventario existencias productos'),
            array('titulo' => 'Ajuste de inventario', 'ruta' => '/inventario/inicial', 'permiso' => 'inventario.ajustar', 'detalle' => 'Ajustes conteo inventario'),
            array('titulo' => 'Reclasificacion', 'ruta' => '/inventario/reclasificacion', 'permiso' => array('inventario.reclasificar', 'inventario.ajustar'), 'detalle' => 'Reclasificar inventario productos'),
            array('titulo' => 'Traspaso entre almacenes', 'ruta' => '/inventario/transpaso', 'permiso' => 'inventario.traspasar', 'detalle' => 'Traspasos inventario almacenes')
        )),
        array('seccion' => 'ERP', 'titulo' => 'Postventa', 'icono' => 'bi-shield-check', 'items' => array(
            array('titulo' => 'Politicas y reglas', 'ruta' => '/garantias/politicas', 'permiso' => 'garantias.ver', 'detalle' => 'Garantias postventa politicas')
        )),
        array('seccion' => 'CMS', 'titulo' => 'Avanzado contenido', 'icono' => 'bi-layout-text-window-reverse', 'items' => array(
            array('titulo' => 'Editor de bloques', 'ruta' => '/cms/contenido', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS contenido ecommerce'),
            array('titulo' => 'Plantillas contenido', 'ruta' => '/cms/plantillas', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Plantillas CMS contenido'),
            array('titulo' => 'Persistencia', 'ruta' => '/cms/persistencia', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Persistencia contenido CMS'),
            array('titulo' => 'Slots', 'ruta' => '/cms/slots', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Slots bloques contenido'),
            array('titulo' => 'Media', 'ruta' => '/cms/media', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Imagenes archivos media'),
            array('titulo' => 'Preview JSON', 'ruta' => '/cms/json', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Previsualizar JSON CMS')
        )),
        array('seccion' => 'CMS', 'titulo' => 'Frontend', 'icono' => 'bi-window-sidebar', 'items' => array(
            array('titulo' => 'Home', 'ruta' => '/cms/frontend/home', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend home'),
            array('titulo' => 'Categorias', 'ruta' => '/cms/frontend/categorias', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend categorias'),
            array('titulo' => 'Producto', 'ruta' => '/cms/frontend/producto', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend producto'),
            array('titulo' => 'Carrito', 'ruta' => '/cms/frontend/carrito', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend carrito'),
            array('titulo' => 'Global', 'ruta' => '/cms/frontend/global', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend header footer SEO'),
            array('titulo' => 'Navegacion', 'ruta' => '/cms/frontend/navegacion', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend menu header footer'),
            array('titulo' => 'Marcas', 'ruta' => '/cms/frontend/marcas', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend marcas publicas'),
            array('titulo' => 'Paginas', 'ruta' => '/cms/frontend/paginas', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend paginas estaticas'),
            array('titulo' => 'Politicas', 'ruta' => '/cms/frontend/politicas', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'CMS frontend politicas legales'),
            array('titulo' => 'Media / Archivos', 'ruta' => '/cms/media', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Biblioteca de imagenes CMS'),
            array('titulo' => 'Plantillas de vista', 'ruta' => '/cms/frontend_plantillas', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Plantillas vistas frontend'),
            array('titulo' => 'Componentes', 'ruta' => '/cms/frontend_componentes', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Componentes frontend'),
            array('titulo' => 'Activaciones', 'ruta' => '/cms/frontend_activaciones', 'permiso' => array('cms.ver', 'catalogo.ver'), 'detalle' => 'Activar frontend vistas')
        )),
        array('seccion' => 'Ecommerce', 'titulo' => 'Ecommerce', 'icono' => 'bi-shop', 'items' => array(
            array('titulo' => 'Control Artiani', 'ruta' => '/ecommercePublico/control', 'permiso' => 'catalogo.ver', 'detalle' => 'Control ecommerce publico'),
            array('titulo' => 'Ecommerce publico', 'ruta' => '/ecommercePublico/publicaciones', 'permiso' => 'catalogo.ver', 'detalle' => 'Publicaciones tienda en linea'),
            array('titulo' => 'Analytics', 'ruta' => '/ecommercePublico/analytics', 'permiso' => 'catalogo.ver', 'detalle' => 'Analitica ecommerce'),
            array('titulo' => 'Catalogo ecommerce', 'ruta' => '/producto/catalogo', 'permiso' => 'ecommerce.ver', 'detalle' => 'Productos ecommerce catalogo anterior')
        )),
        array('seccion' => 'Administracion', 'titulo' => 'Administracion', 'icono' => 'bi-shield-lock', 'items' => array(
            array('titulo' => 'Configuracion del sistema', 'ruta' => '/sistema/configuracion', 'permiso' => 'configuracion.administrar', 'detalle' => 'Branding logos favicon parametros sistema'),
            array('titulo' => 'Migraciones BD', 'ruta' => '/migracionBd', 'permiso' => array('migraciones.ver', 'sistema.soporte'), 'detalle' => 'Base de datos migraciones esquema'),
            array('titulo' => 'Usuarios y roles', 'ruta' => '/sistema/seguridad', 'permiso' => 'seguridad.ver', 'detalle' => 'Seguridad permisos usuarios roles'),
            array('titulo' => 'Notificaciones', 'ruta' => '/sistema/notificaciones', 'permiso' => 'notificaciones.ver', 'detalle' => 'Alertas notificaciones sistema')
        ))
    );

    $accesos = array();
    foreach ($grupos as $grupo) {
        foreach ($grupo['items'] as $item) {
            $item['seccion'] = $grupo['seccion'];
            $item['grupo'] = $grupo['titulo'];
            $item['icono'] = $grupo['icono'];
            $accesos[] = $item;
        }
    }

    return $accesos;
}
