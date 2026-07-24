<?php

class Catalogoerp extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  public function index() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/catalogo/productos");
  }

  public function configuracion() {
    $this->requerirPermiso("catalogo.editar");
    $this->vista("apps/erp/catalogo/configuracion");
  }

  public function migracion_ecommerce() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/catalogo/migracion_ecommerce");
  }

  public function organizacion() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/catalogo/organizacion");
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-07-23
   * Proposito: abrir el MVP interno de catalogos comerciales alimentado por Catalogo ERP.
   * Impacto: Catalogo ERP/Comercial; permite validar candidatos y tarjetas sin persistencia ni exportacion formal.
   * Contrato: vista protegida por `catalogo.ver`; no escribe BD y usa seleccion temporal en navegador.
   */
  public function catalogos_comerciales() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/catalogo/catalogos_comerciales");
  }

  public function propuestas_nombres() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpOrganizacion")->listarPropuestasNombres());
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: publica el historial de fusiones para auditoria operativa de solo lectura.
   * Impacto: Catalogo ERP; no modifica datos ni habilita reversas automaticas.
   */
  public function fusiones_listar() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpOrganizacion")->listarFusiones());
  }

  public function propuesta_nombre_resolver() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpOrganizacion")->resolverPropuestaNombre($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "resolver_propuesta_nombre", array(
      "entidad" => "erp_catalogo_revision_nombres",
      "entidad_id" => isset($_POST["id_revision_nombre"]) ? intval($_POST["id_revision_nombre"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function fusion_buscar_productos() {
    $this->requerirPermiso("catalogo.ver");
    $termino = isset($_GET["q"]) ? $_GET["q"] : "";
    return json_encode($this->modelo("CatalogoErpOrganizacion")->buscarProductosFusion($termino));
  }

  public function fusion_previsualizar() {
    $this->requerirPermiso("catalogo.ver");
    $origen = isset($_GET["id_producto_origen"]) ? intval($_GET["id_producto_origen"]) : 0;
    $destino = isset($_GET["id_producto_destino"]) ? intval($_GET["id_producto_destino"]) : 0;
    return json_encode($this->modelo("CatalogoErpOrganizacion")->previsualizarFusion($origen, $destino));
  }

  public function fusionar_productos() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpOrganizacion")->fusionarProductos($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "fusionar_productos_maestros", array(
      "entidad" => "erp_catalogo_productos",
      "entidad_id" => isset($_POST["id_producto_destino"]) ? intval($_POST["id_producto_destino"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function incidencias_migracion_ecommerce() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarIncidenciasMigracionEcommerce());
  }

  public function incidencia_migracion_detalle() {
    $this->requerirPermiso("catalogo.ver");
    $id = isset($_GET["id_incidencia"]) ? intval($_GET["id_incidencia"]) : 0;
    return json_encode($this->modelo("CatalogoErpMigracionEcommerce")->consultarIncidencia($id));
  }

  public function incidencia_migracion_resolver() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpMigracionEcommerce")->resolverIncidencia($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "resolver_incidencia_migracion", array(
      "entidad" => "erp_catalogo_migracion_ecom_incidencias",
      "entidad_id" => isset($_POST["id_incidencia"]) ? intval($_POST["id_incidencia"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function incidencia_migracion_vincular_existente() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpMigracionEcommerce")->vincularIncidenciaProductoExistente($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "vincular_incidencia_producto_existente", array(
      "entidad" => "erp_catalogo_migracion_ecom_incidencias",
      "entidad_id" => isset($_POST["id_incidencia"]) ? intval($_POST["id_incidencia"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function incidencia_nombre_resolver() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpMigracionEcommerce")->resolverNombreCodificacion($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "resolver_nombre_codificacion", array(
      "entidad" => "erp_catalogo_migracion_ecom_incidencias",
      "entidad_id" => isset($_POST["id_incidencia"]) ? intval($_POST["id_incidencia"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function incidencia_migracion_descartar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpMigracionEcommerce")->descartarIncidencia($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "descartar_incidencia_migracion", array(
      "entidad" => "erp_catalogo_migracion_ecom_incidencias",
      "entidad_id" => isset($_POST["id_incidencia"]) ? intval($_POST["id_incidencia"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function auxiliares_listar() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarCatalogosAdministrativos());
  }

  public function auditoria_calidad() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->auditarCalidadCatalogo());
  }

  public function incidencias_calidad() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarIncidenciasCalidad($_GET));
  }

  public function incidencia_calidad_estatus() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->cambiarEstatusIncidenciaCalidad($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "actualizar_incidencia_calidad", array(
      "entidad" => "erp_catalogo_incidencias_calidad",
      "entidad_id" => isset($_POST["id_incidencia_calidad"]) ? intval($_POST["id_incidencia_calidad"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function incidencia_proveedor_crear_sku_temporal() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->crearSkuTemporalDesdeIncidenciaProveedor($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "crear_sku_temporal_desde_proveedor", array(
      "entidad" => "erp_catalogo_skus",
      "entidad_id" => isset($respuesta["depurar"]["id_sku"]) ? intval($respuesta["depurar"]["id_sku"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function incidencias_reglas_inventario_sincronizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarIncidenciasReglasInventario($this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_incidencias_reglas_inventario", array(
      "entidad" => "erp_catalogo_incidencias_calidad",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function incidencias_variantes_sincronizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarIncidenciasVariantes($this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_incidencias_variantes", array(
      "entidad" => "erp_catalogo_incidencias_calidad",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function incidencias_bloqueos_criticos_sincronizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarIncidenciasBloqueosCriticos($this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_incidencias_bloqueos_criticos", array(
      "entidad" => "erp_catalogo_incidencias_calidad",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function incidencias_compras_xml_sincronizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarIncidenciasComprasXml($this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_incidencias_compras_xml", array(
      "entidad" => "erp_catalogo_incidencias_calidad",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: consulta propuestas de costo solo para perfiles autorizados a ver costos.
   * Impacto: Catalogo ERP; evita exponer costos a capturistas de producto sin permiso especifico.
   */
  public function propuestas_costos_proveedor() {
    $this->requerirPermiso("catalogo.costos");
    return json_encode($this->modelo("CatalogoErpDatos")->listarPropuestasCostosProveedor());
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: consulta relaciones historicas proveedor-SKU solo para perfiles con permiso de costos.
   * Impacto: Catalogo ERP; protege costos de listas y mantiene proveedor/costo fuera del rol normal de captura.
   */
  public function relaciones_proveedor_historicas() {
    $this->requerirPermiso("catalogo.costos");
    return json_encode($this->modelo("CatalogoErpDatos")->listarRelacionesProveedorHistoricas());
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: sincroniza relaciones proveedor-SKU desde listas historicas con permiso restringido.
   * Impacto: Catalogo ERP; accion puente hasta Proveedores/Compras, auditada por modificar relaciones.
   */
  public function relaciones_proveedor_sincronizar() {
    $this->requerirPermiso("catalogo.costos");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarRelacionesProveedorHistoricas();
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_relaciones_proveedor", array(
      "entidad" => "erp_catalogo_sku_proveedores",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-25
   * Proposito: aplica relaciones proveedor-SKU seleccionadas desde coincidencias exactas revisadas por usuario.
   * Impacto: Catalogo ERP; evita alta masiva ciega de proveedores y mantiene auditoria de la decision.
   */
  public function relaciones_proveedor_aplicar_seleccion() {
    $this->requerirPermiso("catalogo.costos");
    $respuesta = $this->modelo("CatalogoErpDatos")->aplicarRelacionesProveedorSeleccionadas($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "aplicar_relaciones_proveedor_seleccion", array(
      "entidad" => "erp_catalogo_sku_proveedores",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: actualiza costos de referencia provisionales desde listas proveedor con permiso restringido.
   * Impacto: Catalogo ERP; no sustituye costo validado de Proveedores/Costos y queda auditado.
   */
  public function propuestas_costos_aplicar() {
    $this->requerirPermiso("catalogo.costos");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarCostosProveedor();
    SesionSeguridad::registrarAuditoria("catalogo", "aplicar_costos_proveedor", array(
      "entidad" => "erp_catalogo_skus",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function propuestas_reorden() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarPropuestasReorden($_GET));
  }

  public function propuestas_reorden_aplicar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->aplicarPropuestasReorden($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "aplicar_reglas_reorden", array(
      "entidad" => "erp_catalogo_sku_reglas_inventario",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function metadatos_sincronizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarMetadatosCatalogo();
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_metadatos", array(
      "entidad" => "erp_catalogo_productos",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function metadatos_revision() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarRevisionMetadatosCatalogo());
  }

  public function metadatos_revision_aplicar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->aplicarRevisionMetadatosCatalogo($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "aplicar_revision_metadatos", array(
      "entidad" => "erp_catalogo_productos",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-07-17
   * Proposito: cambia el estado maestro de productos seleccionados desde el listado de Catalogo.
   * Impacto: Catalogo ERP; evita editar uno por uno y mantiene fusionado fuera de cambios manuales.
   * Contrato: recibe `ids_productos` JSON y `estatus` permitido por el maestro de producto.
   */
  public function productos_estatus_masivo() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->actualizarEstatusProductosMasivo($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "actualizar_estatus_productos_masivo", array(
      "entidad" => "erp_catalogo_productos",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function taxonomias_listar() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarTaxonomiasCatalogo());
  }

  public function taxonomia_ecommerce_sincronizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarTaxonomiaEcommerce();
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_taxonomia_ecommerce", array(
      "entidad" => "erp_catalogo_taxonomias",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function categorias_arbol_preparar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->prepararArbolCategoriasMaestro();
    SesionSeguridad::registrarAuditoria("catalogo", "preparar_arbol_categorias_maestro", array(
      "entidad" => "erp_catalogo_categorias",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function categorias_relaciones_sincronizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->sincronizarRelacionesCategoriasMaestras($this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "sincronizar_relaciones_categorias_maestras", array(
      "entidad" => "erp_catalogo_categoria_equivalencias",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function auxiliar_guardar() {
    $this->requerirPermiso("catalogo.editar");
    $tipo = isset($_POST["tipo_catalogo"]) ? $_POST["tipo_catalogo"] : "";
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarCatalogoAuxiliar($tipo, $_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_" . $tipo, array(
      "entidad" => "erp_catalogo_" . $tipo,
      "entidad_id" => isset($respuesta["depurar"]["id"]) ? $respuesta["depurar"]["id"] : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function listar() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarProductos());
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-07-23
   * Proposito: exponer candidatos read-only para armar catalogos comerciales desde Catalogo ERP.
   * Impacto: Catalogo ERP/Comercial; prepara galeria comercial sin publicar, escribir BD ni generar archivos.
   * Contrato: GET protegido por `catalogo.ver`; devuelve datos y alertas, no costos ni stock exacto.
   */
  public function catalogos_comerciales_candidatos() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->listarCandidatosCatalogoComercial($_GET));
  }

  public function catalogos() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("CatalogoErpDatos")->catalogosFormulario());
  }

  public function consultar() {
    $this->requerirPermiso("catalogo.ver");
    $idProducto = isset($_GET["id_producto_erp"]) ? intval($_GET["id_producto_erp"]) : 0;
    return json_encode($this->modelo("CatalogoErpDatos")->consultarProducto($idProducto));
  }

  public function registrar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->crearProductoConSku($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "crear_producto_erp", array(
      "entidad" => "erp_catalogo_productos",
      "entidad_id" => isset($respuesta["depurar"]["id_producto_erp"]) ? $respuesta["depurar"]["id_producto_erp"] : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-07-19
   * Proposito: crea un producto nuevo usando otro producto como plantilla controlada.
   * Impacto: Catalogo ERP; acelera altas similares sin copiar imagenes, codigos, proveedores ni movimientos.
   * Contrato: requiere producto origen, codigo nuevo, SKU nuevo, nota y opciones explicitas de copia.
   */
  public function duplicar_producto() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->duplicarProducto($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "duplicar_producto", array(
      "entidad" => "erp_catalogo_productos",
      "entidad_id" => isset($respuesta["depurar"]["id_producto_erp"]) ? intval($respuesta["depurar"]["id_producto_erp"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function actualizar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->actualizarProducto($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "actualizar_producto_erp", array(
      "entidad" => "erp_catalogo_productos",
      "entidad_id" => isset($_POST["id_producto_erp"]) ? intval($_POST["id_producto_erp"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function agregar_sku() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->agregarSku($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "agregar_sku_erp", array(
      "entidad" => "erp_catalogo_skus",
      "entidad_id" => isset($respuesta["depurar"]["id_sku"]) ? $respuesta["depurar"]["id_sku"] : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function actualizar_sku() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->actualizarSku($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "actualizar_sku_erp", array(
      "entidad" => "erp_catalogo_skus",
      "entidad_id" => isset($_POST["id_sku"]) ? intval($_POST["id_sku"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "cambios_identidad" => isset($respuesta["depurar"]["cambios_identidad"]) ? $respuesta["depurar"]["cambios_identidad"] : array()
    ));
    return json_encode($respuesta);
  }

  public function sku_base() {
    $this->requerirPermiso("catalogo.ver");
    $idProducto = isset($_GET["id_producto_erp"]) ? intval($_GET["id_producto_erp"]) : 0;
    return json_encode($this->modelo("CatalogoErpDatos")->baseSkuProducto($idProducto));
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: busca SKUs candidatos para recetas de paquete sin restringirlos al producto abierto.
   * Impacto: Catalogo ERP; prepara paquetes simples/configurables con componentes de multiples productos.
   */
  public function paquetes_buscar_skus() {
    $this->requerirPermiso("catalogo.ver");
    $termino = isset($_GET["q"]) ? $_GET["q"] : "";
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 20;
    return json_encode($this->modelo("CatalogoErpDatos")->buscarSkusParaPaquete($termino, $limite));
  }

  public function guardar_sku_proveedor() {
    $this->requerirPermiso("catalogo.costos");
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarSkuProveedor($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_sku_proveedor", array(
      "entidad" => "erp_catalogo_sku_proveedores",
      "entidad_id" => isset($_POST["id_sku"]) ? intval($_POST["id_sku"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: asigna un proveedor comun a SKUs activos sin proveedor activo desde seleccion masiva de productos.
   * Impacto: Catalogo ERP; acelera saneamiento sin sobrescribir relaciones existentes.
   */
  public function proveedor_masivo_skus_sin_proveedor() {
    $this->requerirPermiso("catalogo.costos");
    $respuesta = $this->modelo("CatalogoErpDatos")->asignarProveedorMasivoSkusSinProveedor($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "asignar_proveedor_masivo_skus_sin_proveedor", array(
      "entidad" => "erp_catalogo_sku_proveedores",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-25
   * Proposito: sanea proveedor principal solo en SKUs con un unico proveedor activo.
   * Impacto: Catalogo ERP; no decide entre multiples proveedores ni modifica costos.
   */
  public function proveedor_unico_preferido() {
    $this->requerirPermiso("catalogo.costos");
    $idProducto = isset($_POST["id_producto_erp"]) ? intval($_POST["id_producto_erp"]) : 0;
    $respuesta = $this->modelo("CatalogoErpDatos")->marcarProveedorUnicoPreferido($idProducto);
    SesionSeguridad::registrarAuditoria("catalogo", "marcar_proveedor_unico_preferido", array(
      "entidad" => "erp_catalogo_productos",
      "entidad_id" => $idProducto ?: null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function guardar_sku_presentacion() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarSkuPresentacion($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_sku_presentacion", array(
      "entidad" => "erp_catalogo_sku_presentaciones",
      "entidad_id" => isset($_POST["id_sku_presentacion"]) ? intval($_POST["id_sku_presentacion"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  public function desactivar_sku_presentacion() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->desactivarSkuPresentacion($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "desactivar_sku_presentacion", array(
      "entidad" => "erp_catalogo_sku_presentaciones",
      "entidad_id" => isset($_POST["id_sku_presentacion_regla"]) ? intval($_POST["id_sku_presentacion_regla"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: guarda receta simple de paquete cuando el esquema ERP ya fue aplicado.
   * Impacto: Catalogo ERP; no afecta Ventas, Almacen ni Inventario.
   */
  public function guardar_paquete_simple() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarPaqueteSimple($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_paquete_simple", array(
      "entidad" => "erp_catalogo_sku_paquetes",
      "entidad_id" => isset($respuesta["depurar"]["id_paquete"]) ? intval($respuesta["depurar"]["id_paquete"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: desactiva receta de paquete sin borrar su configuracion.
   * Impacto: Catalogo ERP; conserva trazabilidad y evita uso de recetas obsoletas.
   */
  public function desactivar_paquete() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->desactivarPaquete($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "desactivar_paquete", array(
      "entidad" => "erp_catalogo_sku_paquetes",
      "entidad_id" => isset($_POST["id_paquete"]) ? intval($_POST["id_paquete"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: guarda grupo de seleccion de paquete configurable.
   * Impacto: Catalogo ERP; define opciones elegibles sin tocar ventas ni inventario.
   */
  public function guardar_paquete_grupo() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarPaqueteGrupo($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_paquete_grupo", array(
      "entidad" => "erp_catalogo_sku_paquete_grupos",
      "entidad_id" => isset($respuesta["depurar"]["id_grupo"]) ? intval($respuesta["depurar"]["id_grupo"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: desactiva grupo configurable y sus opciones sin borrarlos.
   * Impacto: Catalogo ERP; evita uso de reglas incompletas u obsoletas.
   */
  public function desactivar_paquete_grupo() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->desactivarPaqueteGrupo($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "desactivar_paquete_grupo", array(
      "entidad" => "erp_catalogo_sku_paquete_grupos",
      "entidad_id" => isset($_POST["id_grupo"]) ? intval($_POST["id_grupo"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: guarda opcion SKU dentro de un grupo configurable de paquete.
   * Impacto: Catalogo ERP; no define precio final ni movimiento de inventario.
   */
  public function guardar_paquete_opcion() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarPaqueteGrupoOpcion($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_paquete_opcion", array(
      "entidad" => "erp_catalogo_sku_paquete_grupo_opciones",
      "entidad_id" => isset($respuesta["depurar"]["id_opcion"]) ? intval($respuesta["depurar"]["id_opcion"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: desactiva opcion configurable sin borrar la receta.
   * Impacto: Catalogo ERP; conserva trazabilidad de alternativas obsoletas.
   */
  public function desactivar_paquete_opcion() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->desactivarPaqueteGrupoOpcion($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "desactivar_paquete_opcion", array(
      "entidad" => "erp_catalogo_sku_paquete_grupo_opciones",
      "entidad_id" => isset($_POST["id_opcion"]) ? intval($_POST["id_opcion"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function guardar_variantes() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarVariantesProducto($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_variantes_producto", array(
      "entidad" => "erp_catalogo_productos",
      "entidad_id" => isset($_POST["id_producto_erp"]) ? intval($_POST["id_producto_erp"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  public function guardar_imagen() {
    $this->requerirPermiso("catalogo.editar");
    $datos = $_POST;
    $archivo = isset($_FILES["archivo_imagen"]) ? $_FILES["archivo_imagen"] : null;
    if ($archivo && isset($archivo["error"]) && intval($archivo["error"]) !== UPLOAD_ERR_NO_FILE) {
      $carga = $this->guardarArchivoImagenProducto($archivo, isset($datos["id_producto_erp"]) ? intval($datos["id_producto_erp"]) : 0);
      if ($carga["error"]) {
        $respuesta = $carga;
      } else {
        $datos["url_imagen"] = $carga["depurar"]["url_imagen"];
        $datos["fuente"] = "upload";
        $respuesta = $this->modelo("CatalogoErpDatos")->guardarImagenProducto($datos);
      }
    } else {
      $respuesta = $this->modelo("CatalogoErpDatos")->guardarImagenProducto($datos);
    }
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_imagen_producto_erp", array(
      "entidad" => "erp_catalogo_imagenes",
      "entidad_id" => isset($respuesta["depurar"]["id_imagen_erp"]) ? $respuesta["depurar"]["id_imagen_erp"] : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-07-21
   * Proposito: recibe archivos de imagen para productos nuevos de Catalogo y genera una ruta publica segura.
   * Impacto: Catalogo ERP; no cambia esquema ni toca imagenes existentes, solo prepara `url_imagen` para persistencia.
   * Contrato: acepta JPG, PNG, WEBP o GIF de hasta 5 MB y guarda en `public/uploads/erp/catalogo/productos/{id}`.
   */
  private function guardarArchivoImagenProducto($archivo, $idProducto) {
    if ($idProducto <= 0) {
      return array("error" => true, "tipo" => "warning", "mensaje" => "Selecciona producto antes de cargar imagen", "depurar" => null);
    }
    if (!isset($archivo["error"]) || intval($archivo["error"]) !== UPLOAD_ERR_OK) {
      $errores = array(
        UPLOAD_ERR_INI_SIZE => "La imagen excede el limite permitido por PHP",
        UPLOAD_ERR_FORM_SIZE => "La imagen excede el limite permitido por el formulario",
        UPLOAD_ERR_PARTIAL => "La imagen se recibio incompleta",
        UPLOAD_ERR_NO_TMP_DIR => "No existe carpeta temporal para recibir la imagen",
        UPLOAD_ERR_CANT_WRITE => "No fue posible escribir la imagen temporal",
        UPLOAD_ERR_EXTENSION => "Una extension de PHP bloqueo la carga de la imagen"
      );
      $codigo = isset($archivo["error"]) ? intval($archivo["error"]) : -1;
      $mensaje = isset($errores[$codigo]) ? $errores[$codigo] : "No fue posible recibir el archivo de imagen";
      return array("error" => true, "tipo" => "warning", "mensaje" => $mensaje, "depurar" => array("upload_error" => $codigo));
    }
    if (intval($archivo["size"]) <= 0 || intval($archivo["size"]) > 5 * 1024 * 1024) {
      return array("error" => true, "tipo" => "warning", "mensaje" => "La imagen debe pesar maximo 5 MB", "depurar" => null);
    }

    $tmp = isset($archivo["tmp_name"]) ? $archivo["tmp_name"] : "";
    $finfo = function_exists("finfo_open") ? finfo_open(FILEINFO_MIME_TYPE) : null;
    $mime = $finfo ? finfo_file($finfo, $tmp) : "";
    if ($finfo) {
      finfo_close($finfo);
    }
    $permitidos = array(
      "image/jpeg" => "jpg",
      "image/png" => "png",
      "image/webp" => "webp",
      "image/gif" => "gif"
    );
    if (!isset($permitidos[$mime])) {
      return array("error" => true, "tipo" => "warning", "mensaje" => "Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF", "depurar" => array("mime" => $mime));
    }

    $relativoDirectorio = "uploads/erp/catalogo/productos/" . intval($idProducto);
    $directorio = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativoDirectorio);
    if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
      return array("error" => true, "tipo" => "danger", "mensaje" => "No fue posible crear la carpeta de imagenes", "depurar" => null);
    }
    $nombre = "producto-" . intval($idProducto) . "-" . date("YmdHis") . "-" . bin2hex(random_bytes(4)) . "." . $permitidos[$mime];
    $destino = $directorio . DIRECTORY_SEPARATOR . $nombre;
    if (!move_uploaded_file($tmp, $destino)) {
      return array("error" => true, "tipo" => "danger", "mensaje" => "No fue posible guardar el archivo de imagen", "depurar" => null);
    }

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "Archivo cargado",
      "depurar" => array("url_imagen" => $relativoDirectorio . "/" . $nombre)
    );
  }

  public function desactivar_imagen() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->desactivarImagenProducto($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "desactivar_imagen_producto_erp", array(
      "entidad" => "erp_catalogo_imagenes",
      "entidad_id" => isset($_POST["id_imagen_erp"]) ? intval($_POST["id_imagen_erp"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-29
   * Proposito: lista imagenes de marca/categoria con respuesta segura si el esquema aun no fue autorizado.
   * Impacto: Catalogo ERP; prepara Configuracion sin ejecutar DDL ni tocar otros modulos.
   */
  public function imagenes_maestro_listar() {
    $this->requerirPermiso("catalogo.ver");
    $tipo = isset($_GET["tipo_entidad"]) ? $_GET["tipo_entidad"] : "";
    $id = isset($_GET["id_entidad"]) ? intval($_GET["id_entidad"]) : 0;
    return json_encode($this->modelo("CatalogoErpDatos")->listarImagenesCatalogoMaestro($tipo, $id));
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-29
   * Proposito: guarda imagen de marca/categoria solo cuando el esquema visual ya existe.
   * Impacto: Catalogo ERP; no modifica productos, ventas, almacen ni inventario.
   */
  public function imagen_maestro_guardar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->guardarImagenCatalogoMaestro($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "guardar_imagen_catalogo_maestro", array(
      "entidad" => isset($_POST["tipo_entidad"]) && $_POST["tipo_entidad"] === "categoria" ? "erp_catalogo_categoria_imagenes" : "erp_catalogo_marca_imagenes",
      "entidad_id" => isset($respuesta["depurar"]["id_imagen"]) ? intval($respuesta["depurar"]["id_imagen"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-29
   * Proposito: desactiva imagen de marca/categoria sin borrado fisico.
   * Impacto: Catalogo ERP; conserva trazabilidad de catalogos maestros.
   */
  public function imagen_maestro_desactivar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->desactivarImagenCatalogoMaestro($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "desactivar_imagen_catalogo_maestro", array(
      "entidad" => isset($_POST["tipo_entidad"]) && $_POST["tipo_entidad"] === "categoria" ? "erp_catalogo_categoria_imagenes" : "erp_catalogo_marca_imagenes",
      "entidad_id" => isset($_POST["id_imagen"]) ? intval($_POST["id_imagen"]) : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"]
    ));
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: expone auditoria de imagenes ecommerce recuperables para saneamiento de Catalogo ERP.
   * Impacto: Catalogo ERP; solo lectura, no modifica imagenes ni datos maestros.
   */
  public function imagenes_ecommerce_auditar() {
    $this->requerirPermiso("catalogo.ver");
    $idProducto = isset($_GET["id_producto_erp"]) ? intval($_GET["id_producto_erp"]) : 0;
    return json_encode($this->modelo("CatalogoErpDatos")->auditarRecuperacionImagenesEcommerce($idProducto));
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: relaciona una imagen ecommerce seleccionada con el producto ERP abierto.
   * Impacto: Catalogo ERP; evita recuperaciones masivas desde la edicion de un producto.
   */
  public function imagenes_ecommerce_recuperar() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("CatalogoErpDatos")->recuperarImagenEcommerceSeleccionada($_POST);
    SesionSeguridad::registrarAuditoria("catalogo", "recuperar_imagenes_ecommerce", array(
      "entidad" => "erp_catalogo_imagenes",
      "entidad_id" => isset($respuesta["depurar"]["id_imagen_erp"]) ? $respuesta["depurar"]["id_imagen_erp"] : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "mensaje" => $respuesta["mensaje"],
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
    return json_encode($respuesta);
  }
}
