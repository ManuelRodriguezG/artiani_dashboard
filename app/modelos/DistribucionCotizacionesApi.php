<?php

class DistribucionCotizacionesApi extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: validar estructura de cotizacion Distribucion sin guardar ni tocar inventario.
   * Impacto: Cotizaciones Distribucion; respeta permiso comercial asignado al cliente externo.
   * Contrato: POST JSON autenticado con `distribucion.cotizacion.solicitar`; no aparta inventario ni crea venta/pedido.
   */
  public function dryRun($datos = array(), $contexto = array()) {
    if (empty($contexto["autenticado"])) {
      return $this->respuesta(true, "warning", "Debes iniciar sesion para cotizar", array(
        "requiere_autenticacion" => true,
        "requiere_permiso" => "distribucion.cotizacion.solicitar"
      ));
    }
    $permisos = $this->valor($contexto, "permisos", array());
    if (!is_array($permisos) || !in_array("distribucion.cotizacion.solicitar", $permisos, true)) {
      return $this->respuesta(true, "warning", "No tienes permiso para cotizar", array("requiere_permiso" => "distribucion.cotizacion.solicitar"));
    }
    require_once RUTA_APP . "/modelos/DistribucionCatalogoApi.php";
    $catalogo = new DistribucionCatalogoApi();
    $precios = $catalogo->resolverPrecios($datos, $contexto);
    $disponibilidad = $catalogo->resolverDisponibilidad($datos, $contexto);
    $itemsPrecio = $this->valor($this->valor($precios, "depurar", array()), "items", array());
    $itemsDisponibilidad = $this->valor($this->valor($disponibilidad, "depurar", array()), "items", array());
    $items = array();
    $subtotal = 0.0;
    $subtotalCompleto = true;
    $bloqueos = array();
    foreach ($itemsPrecio as $i => $item) {
      $disp = isset($itemsDisponibilidad[$i]["disponibilidad"]) ? $itemsDisponibilidad[$i]["disponibilidad"]["estado"] : "consultar_disponibilidad";
      $precio = $this->valor($item, "precio", array());
      $precioVisible = !empty($precio["visible"]) && $this->valor($precio, "monto", null) !== null;
      $cantidad = floatval($this->valor($item, "cantidad", 1));
      $precioUnitario = $precioVisible ? floatval($precio["monto"]) : null;
      $subtotalLinea = $precioVisible ? round($precioUnitario * $cantidad, 6) : null;
      if ($precioVisible) {
        $subtotal += $subtotalLinea;
      } else {
        $subtotalCompleto = false;
        $bloqueos[] = "precio_no_visible_sku_" . intval($this->valor($item, "id_sku", 0));
      }
      if (empty($item["visible_canal"])) {
        $bloqueos[] = "sku_no_visible_canal_" . intval($this->valor($item, "id_sku", 0));
      }
      $items[] = array(
        "id_sku" => intval($this->valor($item, "id_sku", 0)),
        "cantidad" => $cantidad,
        "precio_unitario" => $precioUnitario,
        "subtotal" => $subtotalLinea,
        "disponibilidad" => $disp,
        "valido" => $precioVisible && !empty($item["visible_canal"]),
        "mensaje" => $precioVisible ? null : "Solicitar precio"
      );
    }
    $bloqueos = array_values(array_unique($bloqueos));
    return $this->respuesta(false, empty($bloqueos) ? "success" : "info", empty($bloqueos) ? "Cotizacion dry-run Distribucion validada" : "Cotizacion dry-run Distribucion con observaciones", array(
      "configurado" => !empty($this->valor($this->valor($precios, "depurar", array()), "configurado", false)) && !empty($this->valor($this->valor($disponibilidad, "depurar", array()), "configurado", false)),
      "totales" => array(
        "moneda" => "MXN",
        "subtotal" => $subtotalCompleto ? round($subtotal, 6) : null,
        "total_estimado" => $subtotalCompleto ? round($subtotal, 6) : null
      ),
      "items" => $items,
      "bloqueos" => $bloqueos,
      "guardrails" => array(
        "no_aparta_inventario" => true,
        "no_crea_venta" => true,
        "no_crea_pedido" => true,
        "requiere_revision_interna" => true
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: registrar cotizacion Distribucion como solicitud interna revisable.
   * Impacto: Cotizaciones Distribucion; guarda snapshot comercial sin apartar inventario ni crear venta/pedido.
   * Contrato: POST autenticado con permiso externo; recalcula precios/disponibilidad en ERP antes de persistir.
   */
  public function registrar($datos = array(), $contexto = array()) {
    if (empty($contexto["autenticado"])) {
      return $this->respuesta(true, "warning", "Debes iniciar sesion para registrar cotizaciones", array(
        "folio" => null,
        "estatus" => null,
        "requiere_autenticacion" => true
      ));
    }
    $permisos = $this->valor($contexto, "permisos", array());
    if (!is_array($permisos) || !in_array("distribucion.cotizacion.solicitar", $permisos, true)) {
      return $this->respuesta(true, "warning", "No tienes permiso para registrar cotizaciones", array("requiere_permiso" => "distribucion.cotizacion.solicitar"));
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema de cotizaciones Distribucion no disponible", array("configurado" => false));
    }
    $dry = $this->dryRun($datos, $contexto);
    $depurarDry = $this->valor($dry, "depurar", array());
    $items = $this->valor($depurarDry, "items", array());
    $bloqueos = $this->valor($depurarDry, "bloqueos", array());
    if (empty($items)) {
      return $this->respuesta(true, "warning", "Agrega partidas para registrar cotizacion");
    }
    foreach ($bloqueos as $bloqueo) {
      if (strpos((string) $bloqueo, "sku_no_visible_canal_") === 0) {
        return $this->respuesta(true, "warning", "La cotizacion contiene SKUs no disponibles para Distribucion", array("bloqueos" => $bloqueos));
      }
    }

    $itemsValidos = 0;
    foreach ($items as $item) {
      if (!empty($item["valido"]) || intval($this->valor($item, "id_sku", 0)) > 0) { $itemsValidos++; }
    }
    if ($itemsValidos <= 0) {
      return $this->respuesta(true, "warning", "No hay partidas validas para cotizar");
    }

    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $estatus = empty($bloqueos) ? "recibida" : "recibida_revision";
      $totales = $this->valor($depurarDry, "totales", array());
      $folio = $this->folioCotizacion($db);
      $comentarios = trim((string) $this->valor($datos, "comentarios", ""));
      $skuInfo = $this->skuInfo($db, $items);
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_distribucion_cotizaciones
        (folio, id_cliente_distribucion, estatus, moneda, subtotal, total_estimado, comentarios, snapshot_json, fecha_registro, fecha_actualizacion)
        VALUES (:folio, :cliente, :estatus, 'MXN', :subtotal, :total, :comentarios, :snapshot, NOW(), NOW())");
      $stmt->execute(array(
        ":folio" => $folio,
        ":cliente" => $idCliente,
        ":estatus" => $estatus,
        ":subtotal" => $this->decimalONull($this->valor($totales, "subtotal", null)),
        ":total" => $this->decimalONull($this->valor($totales, "total_estimado", null)),
        ":comentarios" => $comentarios,
        ":snapshot" => json_encode(array("entrada" => $datos, "dry_run" => $depurarDry, "contexto" => $this->contextoAuditable($contexto)))
      ));
      $idCotizacion = intval($db->lastInsertId());
      $stmtItem = $db->prepare("INSERT INTO erp_distribucion_cotizacion_items
        (id_cotizacion_distribucion, id_sku, sku_snapshot, nombre_snapshot, cantidad, precio_unitario_snapshot, subtotal_snapshot, disponibilidad_snapshot, snapshot_json, fecha_registro)
        VALUES (:cotizacion, :sku, :sku_snapshot, :nombre, :cantidad, :precio, :subtotal, :disponibilidad, :snapshot, NOW())");
      foreach ($items as $item) {
        $idSku = intval($this->valor($item, "id_sku", 0));
        if ($idSku <= 0) { continue; }
        $info = isset($skuInfo[$idSku]) ? $skuInfo[$idSku] : array("sku" => null, "nombre" => null);
        $stmtItem->execute(array(
          ":cotizacion" => $idCotizacion,
          ":sku" => $idSku,
          ":sku_snapshot" => $this->valor($info, "sku", null),
          ":nombre" => $this->valor($info, "nombre", null),
          ":cantidad" => floatval($this->valor($item, "cantidad", 1)),
          ":precio" => $this->decimalONull($this->valor($item, "precio_unitario", null)),
          ":subtotal" => $this->decimalONull($this->valor($item, "subtotal", null)),
          ":disponibilidad" => $this->valor($item, "disponibilidad", null),
          ":snapshot" => json_encode($item)
        ));
      }
      $this->registrarAuditoria($db, "cotizacion", $idCotizacion, "registrar", "ok", "Cotizacion Distribucion registrada", array(
        "folio" => $folio,
        "estatus" => $estatus,
        "bloqueos" => $bloqueos,
        "no_aparta_inventario" => true,
        "no_crea_venta" => true,
        "no_crea_pedido" => true
      ), null, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Cotizacion Distribucion registrada", array(
        "configurado" => true,
        "ejecutado" => true,
        "folio" => $folio,
        "id_cotizacion_distribucion" => $idCotizacion,
        "estatus" => $estatus,
        "bloqueos" => $bloqueos,
        "guardrails" => array("no_aparta_inventario" => true, "no_crea_venta" => true, "no_crea_pedido" => true)
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo registrar cotizacion Distribucion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: registrar solicitud de pedido Distribucion para revision interna de existencias y surtido.
   * Impacto: Distribucion; permite pedir sin exponer existencia, apartar inventario, crear venta o crear pedido ERP.
   * Contrato: POST autenticado con `distribucion.pedido.preliminar`; disponibilidad queda `por_confirmar`.
   */
  public function registrarPedidoPreliminar($datos = array(), $contexto = array()) {
    if (empty($contexto["autenticado"])) {
      return $this->respuesta(true, "warning", "Debes iniciar sesion para solicitar pedido", array(
        "folio" => null,
        "estatus" => null,
        "requiere_autenticacion" => true,
        "requiere_permiso" => "distribucion.pedido.preliminar"
      ));
    }
    $permisos = $this->valor($contexto, "permisos", array());
    if (!is_array($permisos) || !in_array("distribucion.pedido.preliminar", $permisos, true)) {
      return $this->respuesta(true, "warning", "No tienes permiso para solicitar pedidos", array("requiere_permiso" => "distribucion.pedido.preliminar"));
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema de pedidos Distribucion no disponible", array("configurado" => false));
    }
    $items = $this->itemsNormalizados($this->valor($datos, "items", array()));
    if (empty($items)) {
      return $this->respuesta(true, "warning", "Agrega partidas para solicitar pedido");
    }
    $visibles = $this->skusVisiblesCanal($db, $items);
    $bloqueos = array();
    foreach ($items as $item) {
      $idSku = intval($this->valor($item, "id_sku", 0));
      if ($idSku <= 0 || !isset($visibles[$idSku])) {
        $bloqueos[] = "sku_no_visible_canal_" . $idSku;
      }
    }
    if (!empty($bloqueos)) {
      return $this->respuesta(true, "warning", "La solicitud contiene SKUs no disponibles para Distribucion", array("bloqueos" => array_values(array_unique($bloqueos))));
    }

    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $folio = $this->folioPedido($db);
      $comentarios = trim((string) $this->valor($datos, "comentarios", ""));
      $skuInfo = $this->skuInfo($db, $items);
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_distribucion_cotizaciones
        (folio, id_cliente_distribucion, estatus, moneda, subtotal, total_estimado, comentarios, snapshot_json, fecha_registro, fecha_actualizacion)
        VALUES (:folio, :cliente, 'pedido_solicitado', 'MXN', NULL, NULL, :comentarios, :snapshot, NOW(), NOW())");
      $stmt->execute(array(
        ":folio" => $folio,
        ":cliente" => $idCliente,
        ":comentarios" => $comentarios,
        ":snapshot" => json_encode(array(
          "tipo" => "pedido_preliminar",
          "entrada" => $datos,
          "contexto" => $this->contextoAuditable($contexto),
          "guardrails" => array("existencia_no_expuesta" => true, "requiere_revision_interna" => true)
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      ));
      $idPedido = intval($db->lastInsertId());
      $stmtItem = $db->prepare("INSERT INTO erp_distribucion_cotizacion_items
        (id_cotizacion_distribucion, id_sku, sku_snapshot, nombre_snapshot, cantidad, precio_unitario_snapshot, subtotal_snapshot, disponibilidad_snapshot, snapshot_json, fecha_registro)
        VALUES (:pedido, :sku, :sku_snapshot, :nombre, :cantidad, NULL, NULL, 'por_confirmar', :snapshot, NOW())");
      foreach ($items as $item) {
        $idSku = intval($this->valor($item, "id_sku", 0));
        $info = isset($skuInfo[$idSku]) ? $skuInfo[$idSku] : array("sku" => null, "nombre" => null);
        $stmtItem->execute(array(
          ":pedido" => $idPedido,
          ":sku" => $idSku,
          ":sku_snapshot" => $this->valor($info, "sku", null),
          ":nombre" => $this->valor($info, "nombre", null),
          ":cantidad" => floatval($this->valor($item, "cantidad", 1)),
          ":snapshot" => json_encode(array(
            "id_sku" => $idSku,
            "cantidad" => floatval($this->valor($item, "cantidad", 1)),
            "disponibilidad" => "por_confirmar",
            "revision_erp_requerida" => true
          ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ));
      }
      $this->registrarAuditoria($db, "pedido_preliminar", $idPedido, "registrar", "ok", "Pedido preliminar Distribucion registrado", array(
        "folio" => $folio,
        "estatus" => "pedido_solicitado",
        "items" => count($items),
        "existencia_no_expuesta" => true,
        "no_aparta_inventario" => true,
        "no_crea_venta" => true,
        "no_crea_pedido_erp" => true
      ), null, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Solicitud de pedido recibida. Revisaremos existencias y surtido.", array(
        "configurado" => true,
        "ejecutado" => true,
        "folio" => $folio,
        "id_pedido_preliminar_distribucion" => $idPedido,
        "id_cotizacion_distribucion" => $idPedido,
        "estatus" => "pedido_solicitado",
        "disponibilidad" => "por_confirmar",
        "guardrails" => array("existencia_no_expuesta" => true, "no_aparta_inventario" => true, "no_crea_venta" => true, "no_crea_pedido_erp" => true)
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo registrar solicitud de pedido", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar cotizaciones Distribucion para administracion ERP.
   * Impacto: Admin ERP Distribucion; permite bandeja read-only de solicitudes recibidas.
   * Contrato: GET interno protegido; lista vacia segura si falta esquema.
   */
  public function cotizacionesInternas($filtros = array()) {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_cotizaciones")) {
      return $this->respuesta(false, "warning", "Cotizaciones Distribucion pendientes de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $limite = max(1, min(200, intval($this->valor($filtros, "limite", 50))));
      $stmt = $db->prepare("SELECT id_cotizacion_distribucion, folio, id_cliente_distribucion, estatus, moneda, subtotal, total_estimado, fecha_registro
        FROM erp_distribucion_cotizaciones
        ORDER BY id_cotizacion_distribucion DESC
        LIMIT " . intval($limite));
      $stmt->execute();
      return $this->respuesta(false, "success", "Cotizaciones Distribucion consultadas", array("configurado" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar cotizaciones", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar detalle interno de cotizacion Distribucion.
   * Impacto: Admin ERP Distribucion; revisa snapshot comercial sin tocar inventario.
   * Contrato: GET interno protegido; read-only.
   */
  public function cotizacionDetalleInterna($filtros = array()) {
    $id = intval($this->valor($filtros, "id_cotizacion_distribucion", $this->valor($filtros, "id", 0)));
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Cotizacion requerida");
    }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_cotizaciones") || !$this->tablaExiste($db, "erp_distribucion_cotizacion_items")) {
      return $this->respuesta(false, "warning", "Detalle de cotizacion pendiente de esquema", array("configurado" => false, "cotizacion" => null, "items" => array()));
    }
    try {
      $stmt = $db->prepare("SELECT * FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id LIMIT 1");
      $stmt->execute(array(":id" => $id));
      $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmtItems = $db->prepare("SELECT * FROM erp_distribucion_cotizacion_items WHERE id_cotizacion_distribucion=:id ORDER BY id_cotizacion_item ASC");
      $stmtItems->execute(array(":id" => $id));
      return $this->respuesta(false, "success", "Cotizacion Distribucion consultada", array("configurado" => true, "cotizacion" => $cotizacion ?: null, "items" => $stmtItems->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar cotizacion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: registrar accion operativa sobre cotizacion Distribucion.
   * Impacto: Admin ERP Distribucion; prepara seguimiento con auditoria futura.
   * Contrato: POST interno protegido; puede cambiar estatus, no crea pedido ni venta.
   */
  public function cotizacionAccionPlanInterna($datos = array(), $idUsuario = null) {
    $accion = trim((string) $this->valor($datos, "accion", ""));
    $estatusPorAccion = array(
      "tomar" => "en_revision",
      "marcar_en_revision" => "en_revision",
      "solicitar_info" => "requiere_info",
      "responder" => "respondida",
      "cancelar" => "cancelada",
      "cerrar" => "cerrada"
    );
    if (!isset($estatusPorAccion[$accion])) {
      return $this->respuesta(true, "warning", "Accion de cotizacion no valida");
    }
    return $this->actualizarEstatusCotizacion($datos, $estatusPorAccion[$accion], $accion, $idUsuario);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar conversion futura de cotizacion Distribucion a documento ERP.
   * Impacto: Admin ERP Distribucion; bloquea conversion automatica en MVP.
   * Contrato: POST interno protegido; devuelve plan sin crear pedido/venta.
   */
  public function cotizacionConvertirPlanInterna($datos = array(), $idUsuario = null) {
    return $this->planCotizacion($datos, "convertir_plan", "bloqueado_mvp", $idUsuario);
  }

  private function actualizarEstatusCotizacion($datos, $estatus, $accion, $idUsuario) {
    $id = intval($this->valor($datos, "id_cotizacion_distribucion", $this->valor($datos, "id", 0)));
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Cotizacion requerida");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema de cotizaciones Distribucion no disponible", array("configurado" => false));
    }
    try {
      $stmtExiste = $db->prepare("SELECT id_cotizacion_distribucion, id_cliente_distribucion, estatus FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id LIMIT 1");
      $stmtExiste->execute(array(":id" => $id));
      $cotizacion = $stmtExiste->fetch(PDO::FETCH_ASSOC);
      if (!$cotizacion) {
        return $this->respuesta(true, "warning", "Cotizacion no encontrada");
      }
      $db->beginTransaction();
      $db->prepare("UPDATE erp_distribucion_cotizaciones SET estatus=:estatus, fecha_actualizacion=NOW() WHERE id_cotizacion_distribucion=:id")
        ->execute(array(":estatus" => $estatus, ":id" => $id));
      $this->registrarAuditoria($db, "cotizacion", $id, $accion, "ok", "Cotizacion Distribucion actualizada", array(
        "estatus_anterior" => $cotizacion["estatus"],
        "estatus" => $estatus,
        "nota" => trim((string) $this->valor($datos, "nota", ""))
      ), $idUsuario, intval($cotizacion["id_cliente_distribucion"]));
      $db->commit();
      return $this->respuesta(false, "success", "Cotizacion Distribucion actualizada", array(
        "ejecutado" => true,
        "id_cotizacion_distribucion" => $id,
        "estatus" => $estatus,
        "guardrails" => array("no_crea_venta" => true, "no_crea_pedido" => true, "no_aparta_inventario" => true)
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo actualizar cotizacion Distribucion", array("detalle" => "error_controlado"));
    }
  }

  private function planCotizacion($datos, $accion, $valor, $idUsuario) {
    $id = intval($this->valor($datos, "id_cotizacion_distribucion", $this->valor($datos, "id", 0)));
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Cotizacion requerida");
    }
    return $this->respuesta(false, "info", "Plan de cotizacion Distribucion generado sin ejecutar", array(
      "ejecutado" => false,
      "id_cotizacion_distribucion" => $id,
      "accion" => $accion,
      "valor" => $valor,
      "id_usuario_erp" => $idUsuario,
      "guardrails" => array(
        "no_crea_venta" => true,
        "no_crea_pedido" => true,
        "no_aparta_inventario" => true,
        "requiere_revision_interna" => true
      )
    ));
  }

  private function esquemaOperativo($db) {
    return $db
      && $this->tablaExiste($db, "erp_distribucion_cotizaciones")
      && $this->tablaExiste($db, "erp_distribucion_cotizacion_items")
      && $this->tablaExiste($db, "erp_distribucion_auditoria");
  }

  private function folioCotizacion($db) {
    $prefijo = "DCOT-" . date("Ymd") . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_distribucion_cotizaciones WHERE folio LIKE :prefijo");
    $stmt->execute(array(":prefijo" => $prefijo . "%"));
    return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 4, "0", STR_PAD_LEFT);
  }

  private function folioPedido($db) {
    $prefijo = "DPED-" . date("Ymd") . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_distribucion_cotizaciones WHERE folio LIKE :prefijo");
    $stmt->execute(array(":prefijo" => $prefijo . "%"));
    return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 4, "0", STR_PAD_LEFT);
  }

  private function skuInfo($db, $items) {
    $ids = array();
    foreach ($items as $item) {
      $idSku = intval($this->valor($item, "id_sku", 0));
      if ($idSku > 0) { $ids[] = $idSku; }
    }
    $ids = array_values(array_unique($ids));
    if (empty($ids) || !$this->tablaExiste($db, "erp_catalogo_skus") || !$this->tablaExiste($db, "erp_catalogo_productos")) {
      return array();
    }
    $params = array();
    $placeholders = array();
    foreach ($ids as $i => $idSku) {
      $ph = ":sku" . $i;
      $placeholders[] = $ph;
      $params[$ph] = $idSku;
    }
    $stmt = $db->prepare("SELECT s.id_sku, s.sku, p.nombre
      FROM erp_catalogo_skus s
      LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE s.id_sku IN (" . implode(",", $placeholders) . ")");
    $stmt->execute($params);
    $mapa = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $mapa[intval($fila["id_sku"])] = array("sku" => $fila["sku"], "nombre" => $fila["nombre"]);
    }
    return $mapa;
  }

  private function skusVisiblesCanal($db, $items) {
    $ids = array();
    foreach ($items as $item) {
      $idSku = intval($this->valor($item, "id_sku", 0));
      if ($idSku > 0) { $ids[] = $idSku; }
    }
    $ids = array_values(array_unique($ids));
    if (empty($ids) || !$this->tablaExiste($db, "erp_catalogo_canales_vinculos")) {
      return array();
    }
    $params = array();
    $placeholders = array();
    foreach ($ids as $i => $idSku) {
      $ph = ":sku" . $i;
      $placeholders[] = $ph;
      $params[$ph] = $idSku;
    }
    $stmt = $db->prepare("SELECT id_sku
      FROM erp_catalogo_canales_vinculos
      WHERE canal='distribucion'
        AND estatus='activo'
        AND sincronizar_catalogo=1
        AND id_sku IN (" . implode(",", $placeholders) . ")");
    $stmt->execute($params);
    $mapa = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $mapa[intval($fila["id_sku"])] = true;
    }
    return $mapa;
  }

  private function decimalONull($valor) {
    return $valor === null || $valor === "" ? null : floatval($valor);
  }

  private function contextoAuditable($contexto) {
    return array(
      "canal" => $this->valor($contexto, "canal", "distribucion"),
      "id_cliente_distribucion" => $this->valor($contexto, "id_cliente_distribucion", null),
      "tipo_cliente" => $this->valor($contexto, "tipo_cliente", null),
      "id_lista_precio" => $this->valor($contexto, "id_lista_precio", null),
      "permisos" => $this->valor($contexto, "permisos", array())
    );
  }

  private function itemsNormalizados($items) {
    $items = is_array($items) ? array_slice($items, 0, 100) : array();
    $salida = array();
    foreach ($items as $item) {
      if (!is_array($item)) { continue; }
      $idSku = intval($this->valor($item, "id_sku", 0));
      if ($idSku <= 0) { continue; }
      $salida[] = array(
        "id_sku" => $idSku,
        "cantidad" => max(0.001, min(9999, floatval($this->valor($item, "cantidad", 1))))
      );
    }
    return $salida;
  }

  private function registrarAuditoria($db, $entidad, $idEntidad, $accion, $resultado, $mensaje, $detalle, $idUsuario = null, $idCliente = null) {
    if (!$this->tablaExiste($db, "erp_distribucion_auditoria")) { return; }
    $stmt = $db->prepare("INSERT INTO erp_distribucion_auditoria
      (entidad, id_entidad, accion, resultado, mensaje, detalle_json, id_usuario_erp, id_cliente_distribucion, fecha_registro)
      VALUES (:entidad, :id_entidad, :accion, :resultado, :mensaje, :detalle, :usuario, :cliente, NOW())");
    $stmt->execute(array(
      ":entidad" => $entidad,
      ":id_entidad" => $idEntidad,
      ":accion" => $accion,
      ":resultado" => $resultado,
      ":mensaje" => $mensaje,
      ":detalle" => json_encode($detalle),
      ":usuario" => $idUsuario,
      ":cliente" => $idCliente
    ));
  }

  private function tablaExiste($db, $tabla) {
    try {
      $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }
}
