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
    $itemsOriginales = $this->valor($datos, "items", array());
    $itemsEntrada = $this->itemsNormalizados($itemsOriginales);
    if (empty($itemsEntrada)) {
      return $this->respuesta(true, "warning", "Agrega partidas con SKU y cantidad mayor a cero para cotizar", array(
        "codigo" => "partidas_invalidas",
        "no_crea_folio" => true
      ));
    }
    $datos["items"] = $itemsEntrada;
    require_once RUTA_APP . "/modelos/DistribucionCatalogoApi.php";
    $catalogo = new DistribucionCatalogoApi();
    $precios = $catalogo->resolverPrecios($datos, $contexto);
    $disponibilidad = $catalogo->resolverDisponibilidad($datos, $contexto);
    $itemsPrecio = $this->valor($this->valor($precios, "depurar", array()), "items", array());
    $itemsDisponibilidad = $this->valor($this->valor($disponibilidad, "depurar", array()), "items", array());
    $comentariosEntrada = array();
    foreach ($itemsOriginales as $entradaItem) {
      $idSkuComentario = intval($this->valor($entradaItem, "id_sku", 0));
      if ($idSkuComentario > 0) {
        $comentariosEntrada[$idSkuComentario] = trim((string) $this->valor($entradaItem, "comentario", ""));
      }
    }
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
        "comentario" => $this->valor($comentariosEntrada, intval($this->valor($item, "id_sku", 0)), ""),
        "precio_unitario" => $precioUnitario,
        "subtotal" => $subtotalLinea,
        "disponibilidad" => $disp,
        "valido" => $precioVisible && !empty($item["visible_canal"]),
        "mensaje" => $precioVisible ? null : "Solicitar precio"
      );
    }
    $bloqueos = array_values(array_unique($bloqueos));
    $bloqueaVisibilidadCliente = false;
    foreach ($bloqueos as $bloqueo) {
      if (strpos((string) $bloqueo, "sku_no_visible_canal_") === 0) {
        $bloqueaVisibilidadCliente = true;
        break;
      }
    }
    return $this->respuesta($bloqueaVisibilidadCliente, $bloqueaVisibilidadCliente ? "warning" : (empty($bloqueos) ? "success" : "info"), $bloqueaVisibilidadCliente ? "La cotizacion contiene SKUs no disponibles para este cliente" : (empty($bloqueos) ? "Cotizacion dry-run Distribucion validada" : "Cotizacion dry-run Distribucion con observaciones"), array(
      "configurado" => !empty($this->valor($this->valor($precios, "depurar", array()), "configurado", false)) && !empty($this->valor($this->valor($disponibilidad, "depurar", array()), "configurado", false)),
      "totales" => array(
        "moneda" => "MXN",
        "subtotal" => $subtotalCompleto ? round($subtotal, 6) : null,
        "total_estimado" => $subtotalCompleto ? round($subtotal, 6) : null
      ),
      "items" => $items,
      "bloqueos" => $bloqueos,
      "codigo" => $bloqueaVisibilidadCliente ? "sku_no_visible_cliente" : null,
      "no_crea_folio" => $bloqueaVisibilidadCliente,
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
   * IA: Codex GPT-5
   * Fecha: 2026-10-02
   * Proposito: listar cotizaciones propias del cliente externo separadas de pedidos.
   * Impacto: Portal Distribucion; permite historial comercial sin mezclar solicitudes formales.
   * Contrato: GET autenticado; no expone datos de otros clientes ni crea documentos.
   */
  public function cotizacionesCliente($filtros = array(), $contexto = array()) {
    $permiso = $this->validarClienteCotizacion($contexto);
    if ($permiso) { return $permiso; }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(false, "warning", "Cotizaciones Distribucion pendientes de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 50))));
      $tieneTipo = $this->columnasDocumentoDisponibles($db);
      $whereTipo = $this->condicionDocumentoCotizacion($db, "co");
      $extra = $tieneTipo ? "nombre_documento, tipo_documento, id_pedido_relacionado," : "NULL nombre_documento, 'cotizacion' tipo_documento, NULL id_pedido_relacionado,";
      $stmt = $db->prepare("SELECT id_cotizacion_distribucion, folio, " . $extra . " estatus, moneda, subtotal, total_estimado, comentarios, fecha_registro, fecha_actualizacion,
          (SELECT COUNT(*) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion) partidas,
          (SELECT COALESCE(SUM(i.cantidad),0) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion) cantidad_total
        FROM erp_distribucion_cotizaciones co
        WHERE id_cliente_distribucion=:cliente
          AND " . $whereTipo . "
        ORDER BY id_cotizacion_distribucion DESC
        LIMIT " . intval($limite));
      $stmt->execute(array(":cliente" => $idCliente));
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $fila["pedido_generado"] = intval($this->valor($fila, "id_pedido_relacionado", 0)) > 0 || (string) $fila["estatus"] === "enviada_como_pedido" ? 1 : 0;
        $fila["id_pedido_distribucion"] = intval($this->valor($fila, "id_pedido_relacionado", 0)) > 0 ? intval($fila["id_pedido_relacionado"]) : null;
        $fila["acciones"] = $this->accionesCotizacionCliente($fila["estatus"]);
        $items[] = $fila;
      }
      return $this->respuesta(false, "success", "Cotizaciones Distribucion consultadas", array("configurado" => true, "items" => $items));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar cotizaciones", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-02
   * Proposito: consultar detalle de cotizacion propia separada de pedido.
   * Impacto: Portal Distribucion; permite editar/duplicar/enviar borradores sin tocar pedidos previos.
   */
  public function cotizacionDetalleCliente($filtros = array(), $contexto = array()) {
    $permiso = $this->validarClienteCotizacion($contexto);
    if ($permiso) { return $permiso; }
    $id = intval($this->valor($filtros, "id_cotizacion_distribucion", $this->valor($filtros, "id", 0)));
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Cotizacion requerida");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->tablaExiste($db, "erp_distribucion_cotizacion_items")) {
      return $this->respuesta(false, "warning", "Detalle de cotizacion pendiente de esquema", array("configurado" => false, "cotizacion" => null, "items" => array()));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $whereTipo = "AND " . $this->condicionDocumentoCotizacion($db);
      $stmt = $db->prepare("SELECT * FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id AND id_cliente_distribucion=:cliente " . $whereTipo . " LIMIT 1");
      $stmt->execute(array(":id" => $id, ":cliente" => $idCliente));
      $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cotizacion) {
        return $this->respuesta(true, "warning", "Cotizacion no encontrada");
      }
      $stmtItems = $db->prepare("SELECT i.id_cotizacion_item, i.id_sku, i.sku_snapshot, i.nombre_snapshot, i.cantidad,
          i.precio_unitario_snapshot, i.subtotal_snapshot, i.disponibilidad_snapshot, i.snapshot_json,
          s.sku sku_actual, COALESCE(NULLIF(s.nombre,''), p.nombre, i.nombre_snapshot) producto_actual
        FROM erp_distribucion_cotizacion_items i
        LEFT JOIN erp_catalogo_skus s ON s.id_sku=i.id_sku
        LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        WHERE i.id_cotizacion_distribucion=:id
        ORDER BY i.id_cotizacion_item ASC");
      $stmtItems->execute(array(":id" => $id));
      $items = array();
      foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $snapshot = json_decode((string) $this->valor($item, "snapshot_json", ""), true);
        $item["comentario_cliente"] = is_array($snapshot) ? $this->valor($snapshot, "comentario", "") : "";
        unset($item["snapshot_json"]);
        $items[] = $item;
      }
      $cotizacion["pedido_generado"] = intval($this->valor($cotizacion, "id_pedido_relacionado", 0)) > 0 || (string) $cotizacion["estatus"] === "enviada_como_pedido" ? 1 : 0;
      $cotizacion["id_pedido_distribucion"] = intval($this->valor($cotizacion, "id_pedido_relacionado", 0)) > 0 ? intval($cotizacion["id_pedido_relacionado"]) : null;
      $cotizacion["acciones"] = $this->accionesCotizacionCliente($cotizacion["estatus"]);
      return $this->respuesta(false, "success", "Cotizacion Distribucion consultada", array("configurado" => true, "cotizacion" => $cotizacion, "items" => $items));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar cotizacion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-02
   * Proposito: crear o actualizar borrador de cotizacion del cliente recalculando precios ERP.
   * Impacto: Portal Distribucion; conserva snapshot comercial sin apartar inventario ni crear pedido.
   */
  public function guardarBorradorCliente($datos = array(), $contexto = array()) {
    $permiso = $this->validarClienteCotizacion($contexto);
    if ($permiso) { return $permiso; }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasDocumentoDisponibles($db)) {
      return $this->respuesta(true, "warning", "Borradores de cotizacion pendientes de esquema", array("configurado" => false));
    }
    $itemsEntrada = $this->itemsNormalizados($this->valor($datos, "items", array()));
    if (empty($itemsEntrada)) {
      return $this->respuesta(true, "warning", "Agrega partidas para guardar cotizacion");
    }
    $dry = $this->dryRun($datos, $contexto);
    $depurarDry = $this->valor($dry, "depurar", array());
    $items = $this->valor($depurarDry, "items", array());
    if (empty($items)) {
      return $this->respuesta(true, "warning", "No hay partidas validas para cotizar");
    }
    $comentariosEntrada = array();
    foreach ($this->valor($datos, "items", array()) as $entradaItem) {
      $idSkuComentario = intval($this->valor($entradaItem, "id_sku", 0));
      if ($idSkuComentario > 0) {
        $comentariosEntrada[$idSkuComentario] = trim((string) $this->valor($entradaItem, "comentario", ""));
      }
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $id = intval($this->valor($datos, "id_cotizacion_distribucion", 0));
      $nombre = substr(trim((string) $this->valor($datos, "nombre", "")), 0, 180);
      $comentarios = trim((string) $this->valor($datos, "comentarios", ""));
      $totales = $this->valor($depurarDry, "totales", array());
      $skuInfo = $this->skuInfo($db, $items);
      $db->beginTransaction();
      if ($id > 0) {
        $stmtExiste = $db->prepare("SELECT id_cotizacion_distribucion, estatus FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id AND id_cliente_distribucion=:cliente AND " . $this->condicionDocumentoCotizacion($db) . " LIMIT 1");
        $stmtExiste->execute(array(":id" => $id, ":cliente" => $idCliente));
        $actual = $stmtExiste->fetch(PDO::FETCH_ASSOC);
        if (!$actual) { throw new Exception("cotizacion_no_encontrada"); }
        if ((string) $actual["estatus"] !== "borrador") { throw new Exception("cotizacion_no_editable"); }
        $db->prepare("UPDATE erp_distribucion_cotizaciones
          SET nombre_documento=:nombre, comentarios=:comentarios, subtotal=:subtotal, total_estimado=:total, snapshot_json=:snapshot, fecha_actualizacion=NOW()
          WHERE id_cotizacion_distribucion=:id")
          ->execute(array(
            ":nombre" => $nombre,
            ":comentarios" => $comentarios,
            ":subtotal" => $this->decimalONull($this->valor($totales, "subtotal", null)),
            ":total" => $this->decimalONull($this->valor($totales, "total_estimado", null)),
            ":snapshot" => json_encode(array("tipo" => "cotizacion_borrador", "entrada" => $datos, "dry_run" => $depurarDry, "contexto" => $this->contextoAuditable($contexto)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ":id" => $id
          ));
        $db->prepare("DELETE FROM erp_distribucion_cotizacion_items WHERE id_cotizacion_distribucion=:id")->execute(array(":id" => $id));
      } else {
        $folio = $this->folioCotizacion($db);
        $db->prepare("INSERT INTO erp_distribucion_cotizaciones
          (folio, nombre_documento, id_cliente_distribucion, tipo_documento, estatus, moneda, subtotal, total_estimado, comentarios, snapshot_json, fecha_registro, fecha_actualizacion)
          VALUES (:folio, :nombre, :cliente, 'cotizacion', 'borrador', 'MXN', :subtotal, :total, :comentarios, :snapshot, NOW(), NOW())")
          ->execute(array(
            ":folio" => $folio,
            ":nombre" => $nombre,
            ":cliente" => $idCliente,
            ":subtotal" => $this->decimalONull($this->valor($totales, "subtotal", null)),
            ":total" => $this->decimalONull($this->valor($totales, "total_estimado", null)),
            ":comentarios" => $comentarios,
            ":snapshot" => json_encode(array("tipo" => "cotizacion_borrador", "entrada" => $datos, "dry_run" => $depurarDry, "contexto" => $this->contextoAuditable($contexto)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
          ));
        $id = intval($db->lastInsertId());
      }
      $this->insertarItemsSnapshot($db, $id, $items, $skuInfo, $comentariosEntrada);
      $this->registrarAuditoria($db, "cotizacion", $id, "guardar_borrador", "ok", "Borrador Distribucion guardado", array("items" => count($items)), null, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Cotizacion guardada como borrador", array(
        "id_cotizacion_distribucion" => $id,
        "folio" => isset($folio) ? $folio : null,
        "estatus" => "borrador",
        "total_estimado" => $this->valor($totales, "total_estimado", null),
        "items" => $items
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      $mensaje = $e->getMessage() === "cotizacion_no_editable" ? "Solo se pueden editar cotizaciones en borrador" : "No se pudo guardar cotizacion";
      return $this->respuesta(true, "danger", $mensaje, array("detalle" => "error_controlado"));
    }
  }

  public function cancelarCotizacionCliente($datos = array(), $contexto = array()) {
    $permiso = $this->validarClienteCotizacion($contexto);
    if ($permiso) { return $permiso; }
    return $this->actualizarCotizacionClienteEstatus($datos, $contexto, "cancelada", "cancelar");
  }

  public function duplicarCotizacionCliente($datos = array(), $contexto = array()) {
    $permiso = $this->validarClienteCotizacion($contexto);
    if ($permiso) { return $permiso; }
    $id = intval($this->valor($datos, "id_cotizacion_distribucion", 0));
    if ($id <= 0) { return $this->respuesta(true, "warning", "Cotizacion requerida"); }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema de cotizaciones Distribucion no disponible");
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT * FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id AND id_cliente_distribucion=:cliente AND " . $this->condicionDocumentoCotizacion($db) . " LIMIT 1");
      $stmt->execute(array(":id" => $id, ":cliente" => $idCliente));
      $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cotizacion) { return $this->respuesta(true, "warning", "Cotizacion no encontrada"); }
      $stmtItems = $db->prepare("SELECT id_sku, cantidad, snapshot_json FROM erp_distribucion_cotizacion_items WHERE id_cotizacion_distribucion=:id ORDER BY id_cotizacion_item ASC");
      $stmtItems->execute(array(":id" => $id));
      $items = array();
      foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $snapshot = json_decode((string) $this->valor($item, "snapshot_json", ""), true);
        $items[] = array(
          "id_sku" => intval($item["id_sku"]),
          "cantidad" => floatval($item["cantidad"]),
          "comentario" => is_array($snapshot) ? $this->valor($snapshot, "comentario", "") : ""
        );
      }
      return $this->guardarBorradorCliente(array(
        "nombre" => trim((string) $this->valor($cotizacion, "nombre_documento", "")),
        "comentarios" => trim((string) $this->valor($cotizacion, "comentarios", "")),
        "items" => $items
      ), $contexto);
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo duplicar cotizacion", array("detalle" => "error_controlado"));
    }
  }

  public function enviarCotizacionComoPedido($datos = array(), $contexto = array()) {
    $permiso = $this->validarClienteCotizacion($contexto);
    if ($permiso) { return $permiso; }
    $id = intval($this->valor($datos, "id_cotizacion_distribucion", 0));
    if ($id <= 0) { return $this->respuesta(true, "warning", "Cotizacion requerida"); }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasDocumentoDisponibles($db)) {
      return $this->respuesta(true, "warning", "Envio de cotizacion pendiente de esquema", array("configurado" => false));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT * FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id AND id_cliente_distribucion=:cliente AND " . $this->condicionDocumentoCotizacion($db) . " LIMIT 1");
      $stmt->execute(array(":id" => $id, ":cliente" => $idCliente));
      $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cotizacion) { return $this->respuesta(true, "warning", "Cotizacion no encontrada"); }
      if ((string) $cotizacion["estatus"] !== "borrador") {
        return $this->respuesta(true, "warning", "Solo se pueden enviar cotizaciones en borrador");
      }
      $stmtItems = $db->prepare("SELECT id_sku, cantidad, snapshot_json FROM erp_distribucion_cotizacion_items WHERE id_cotizacion_distribucion=:id ORDER BY id_cotizacion_item ASC");
      $stmtItems->execute(array(":id" => $id));
      $items = array();
      foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $snapshot = json_decode((string) $this->valor($item, "snapshot_json", ""), true);
        $items[] = array(
          "id_sku" => intval($item["id_sku"]),
          "cantidad" => floatval($item["cantidad"]),
          "comentario" => is_array($snapshot) ? $this->valor($snapshot, "comentario", "") : ""
        );
      }
      $entrega = $this->valor($datos, "entrega", array());
      $pedidoDatos = array(
        "items" => $items,
        "comentarios" => trim((string) $this->valor($cotizacion, "comentarios", "")),
        "id_cotizacion_origen" => $id,
        "tipo_entrega" => is_array($entrega) ? $this->valor($entrega, "tipo_entrega", $this->valor($datos, "tipo_entrega", "por_definir")) : $this->valor($datos, "tipo_entrega", "por_definir"),
        "direccion_envio" => is_array($entrega) ? $this->valor($entrega, "direccion_envio", array()) : array(),
        "requiere_factura" => $this->valor($datos, "requiere_factura", 0),
        "facturacion" => $this->valor($datos, "facturacion", array())
      );
      $pedido = $this->registrarPedidoPreliminar($pedidoDatos, $contexto);
      if (!empty($pedido["error"])) { return $pedido; }
      $idPedido = intval($this->valor($this->valor($pedido, "depurar", array()), "id_cotizacion_distribucion", 0));
      if ($idPedido > 0) {
        $db->beginTransaction();
        $db->prepare("UPDATE erp_distribucion_cotizaciones SET estatus='enviada_como_pedido', id_pedido_relacionado=:pedido, fecha_actualizacion=NOW() WHERE id_cotizacion_distribucion=:id")
          ->execute(array(":pedido" => $idPedido, ":id" => $id));
        $this->registrarAuditoria($db, "cotizacion", $id, "enviar_como_pedido", "ok", "Cotizacion enviada como pedido", array("id_pedido_distribucion" => $idPedido), null, $idCliente);
        $db->commit();
      }
      return $this->respuesta(false, "success", "Cotizacion enviada como pedido", array(
        "id_cotizacion_distribucion" => $id,
        "id_pedido_distribucion" => $idPedido,
        "folio_pedido" => $this->valor($this->valor($pedido, "depurar", array()), "folio", null),
        "estatus_pedido" => $this->valor($this->valor($pedido, "depurar", array()), "estatus", "pedido_solicitado")
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo enviar cotizacion como pedido", array("detalle" => "error_controlado"));
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

    require_once RUTA_APP . "/modelos/DistribucionCatalogoApi.php";
    $precios = (new DistribucionCatalogoApi())->resolverPrecios($datos, $contexto);
    $depurarPrecios = $this->valor($precios, "depurar", array());
    $itemsPrecio = $this->valor($depurarPrecios, "items", array());
    foreach ($itemsPrecio as $itemPrecioCanal) {
      if (empty($itemPrecioCanal["visible_canal"])) {
        $bloqueos[] = "sku_no_visible_cliente_" . intval($this->valor($itemPrecioCanal, "id_sku", 0));
      }
    }
    if (!empty($bloqueos)) {
      return $this->respuesta(true, "warning", "La solicitud contiene SKUs no disponibles para este cliente", array("bloqueos" => array_values(array_unique($bloqueos))));
    }
    $preciosSnapshot = array();
    $totalEstimado = 0.0;
    $totalCompleto = empty($precios["error"]) && !empty($itemsPrecio);
    foreach ($items as $item) {
      $idSkuInicial = intval($this->valor($item, "id_sku", 0));
      if ($idSkuInicial <= 0) { continue; }
      $preciosSnapshot[$idSkuInicial] = array(
        "precio_unitario" => null,
        "subtotal" => null,
        "mensaje" => "pendiente_revision_precio",
        "motivo" => "precio_no_resuelto"
      );
    }
    foreach ($itemsPrecio as $itemPrecio) {
      $idSkuPrecio = intval($this->valor($itemPrecio, "id_sku", 0));
      if ($idSkuPrecio <= 0) { continue; }
      $precio = $this->valor($itemPrecio, "precio", array());
      $precioVisible = !empty($precio["visible"]) && $this->valor($precio, "monto", null) !== null;
      $cantidad = floatval($this->valor($itemPrecio, "cantidad", 1));
      $precioUnitario = $precioVisible ? floatval($precio["monto"]) : null;
      $subtotalLinea = $precioVisible ? round($precioUnitario * $cantidad, 6) : null;
      if ($precioVisible) {
        $totalEstimado += $subtotalLinea;
      } else {
        $totalCompleto = false;
      }
      $preciosSnapshot[$idSkuPrecio] = array(
        "precio_unitario" => $precioUnitario,
        "subtotal" => $subtotalLinea,
        "mensaje" => $precioVisible ? null : $this->valor($precio, "mensaje", "precio_no_visible"),
        "motivo" => $precioVisible ? null : $this->valor($precio, "tipo", "precio_no_visible")
      );
    }
    foreach ($items as $item) {
      $idSkuSolicitado = intval($this->valor($item, "id_sku", 0));
      if ($idSkuSolicitado > 0 && (!isset($preciosSnapshot[$idSkuSolicitado]) || $this->valor($preciosSnapshot[$idSkuSolicitado], "precio_unitario", null) === null)) {
        $totalCompleto = false;
      }
    }

    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $folio = $this->folioPedido($db);
      $comentarios = trim((string) $this->valor($datos, "comentarios", ""));
      $entrega = $this->normalizarEntregaPedido($datos, array());
      $facturacion = $this->normalizarFacturacionPedido($datos, array());
      $skuInfo = $this->skuInfo($db, $items);
      $db->beginTransaction();
      $columnasEntrega = $this->columnasEntregaPedidoDisponibles($db);
      $columnas = array("folio", "id_cliente_distribucion", "estatus", "moneda", "subtotal", "total_estimado", "comentarios", "snapshot_json", "fecha_registro", "fecha_actualizacion");
      $valores = array(":folio", ":cliente", "'pedido_solicitado'", "'MXN'", ":subtotal", ":total", ":comentarios", ":snapshot", "NOW()", "NOW()");
      $paramsPedido = array(
        ":folio" => $folio,
        ":cliente" => $idCliente,
        ":subtotal" => $this->decimalONull($totalCompleto ? round($totalEstimado, 6) : null),
        ":total" => $this->decimalONull($totalCompleto ? round($totalEstimado, 6) : null),
        ":comentarios" => $comentarios,
        ":snapshot" => json_encode(array(
          "tipo" => "pedido_preliminar",
          "entrada" => $datos,
          "precios" => $depurarPrecios,
          "entrega" => $entrega,
          "facturacion" => $facturacion,
          "contexto" => $this->contextoAuditable($contexto),
          "guardrails" => array("existencia_no_expuesta" => true, "requiere_revision_interna" => true)
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      );
      if ($this->columnasDocumentoDisponibles($db)) {
        $columnas = array_merge($columnas, array("tipo_documento", "id_cotizacion_origen"));
        $valores = array_merge($valores, array("'pedido'", ":cotizacion_origen"));
        $paramsPedido[":cotizacion_origen"] = intval($this->valor($datos, "id_cotizacion_origen", 0)) > 0 ? intval($this->valor($datos, "id_cotizacion_origen", 0)) : null;
      }
      if ($columnasEntrega) {
        $columnas = array_merge($columnas, array("tipo_entrega", "entrega_habilitar_envio", "entrega_habilitar_recoger_tienda", "costo_envio", "direccion_envio_json"));
        $valores = array_merge($valores, array(":tipo_entrega", ":habilitar_envio", ":habilitar_recoger", ":costo_envio", ":direccion_envio"));
        $paramsPedido[":tipo_entrega"] = $entrega["tipo_entrega"];
        $paramsPedido[":habilitar_envio"] = $entrega["entrega_habilitar_envio"];
        $paramsPedido[":habilitar_recoger"] = $entrega["entrega_habilitar_recoger_tienda"];
        $paramsPedido[":costo_envio"] = $this->decimalONull($entrega["costo_envio"]);
        $paramsPedido[":direccion_envio"] = json_encode($entrega["direccion_envio"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      if ($this->columnasFacturacionPedidoDisponibles($db)) {
        $columnas = array_merge($columnas, array("requiere_factura", "facturacion_json"));
        $valores = array_merge($valores, array(":requiere_factura", ":facturacion"));
        $paramsPedido[":requiere_factura"] = $facturacion["requiere_factura"];
        $paramsPedido[":facturacion"] = json_encode($facturacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      $stmt = $db->prepare("INSERT INTO erp_distribucion_cotizaciones (" . implode(", ", $columnas) . ") VALUES (" . implode(", ", $valores) . ")");
      $stmt->execute($paramsPedido);
      $idPedido = intval($db->lastInsertId());
      $stmtItem = $db->prepare("INSERT INTO erp_distribucion_cotizacion_items
        (id_cotizacion_distribucion, id_sku, sku_snapshot, nombre_snapshot, cantidad, precio_unitario_snapshot, subtotal_snapshot, disponibilidad_snapshot, snapshot_json, fecha_registro)
        VALUES (:pedido, :sku, :sku_snapshot, :nombre, :cantidad, :precio, :subtotal, 'por_confirmar', :snapshot, NOW())");
      foreach ($items as $item) {
        $idSku = intval($this->valor($item, "id_sku", 0));
        $info = isset($skuInfo[$idSku]) ? $skuInfo[$idSku] : array("sku" => null, "nombre" => null);
        $precioSnapshot = isset($preciosSnapshot[$idSku]) ? $preciosSnapshot[$idSku] : array();
        $stmtItem->execute(array(
          ":pedido" => $idPedido,
          ":sku" => $idSku,
          ":sku_snapshot" => $this->valor($info, "sku", null),
          ":nombre" => $this->valor($info, "nombre", null),
          ":cantidad" => floatval($this->valor($item, "cantidad", 1)),
          ":precio" => $this->decimalONull($this->valor($precioSnapshot, "precio_unitario", null)),
          ":subtotal" => $this->decimalONull($this->valor($precioSnapshot, "subtotal", null)),
          ":snapshot" => json_encode(array(
            "id_sku" => $idSku,
            "cantidad" => floatval($this->valor($item, "cantidad", 1)),
            "precio_unitario" => $this->valor($precioSnapshot, "precio_unitario", null),
            "subtotal" => $this->valor($precioSnapshot, "subtotal", null),
            "precio_mensaje" => $this->valor($precioSnapshot, "mensaje", null),
            "precio_motivo" => $this->valor($precioSnapshot, "motivo", null),
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
        "subtotal" => $totalCompleto ? round($totalEstimado, 6) : null,
        "total_estimado" => $totalCompleto ? round($totalEstimado, 6) : null,
        "precio_snapshot_completo" => $totalCompleto,
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
      $estatus = trim((string) $this->valor($filtros, "estatus", ""));
      $q = trim((string) $this->valor($filtros, "q", ""));
      $fechaDesde = trim((string) $this->valor($filtros, "fecha_desde", ""));
      $fechaHasta = trim((string) $this->valor($filtros, "fecha_hasta", ""));
      $where = array("1=1");
      $params = array();
      if ($estatus !== "") {
        $where[] = "co.estatus=:estatus";
        $params[":estatus"] = $estatus;
      }
      if ($q !== "") {
        $where[] = "(co.folio LIKE :q OR c.nombre LIKE :q OR c.empresa LIKE :q OR c.correo LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      if ($fechaDesde !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
        $where[] = "co.fecha_registro>=:fecha_desde";
        $params[":fecha_desde"] = $fechaDesde . " 00:00:00";
      }
      if ($fechaHasta !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
        $where[] = "co.fecha_registro<=:fecha_hasta";
        $params[":fecha_hasta"] = $fechaHasta . " 23:59:59";
      }
      $joinCliente = $this->tablaExiste($db, "erp_distribucion_clientes") ? "LEFT JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=co.id_cliente_distribucion" : "LEFT JOIN (SELECT NULL id_cliente_distribucion, NULL nombre, NULL empresa, NULL correo) c ON 1=0";
      $tieneItems = $this->tablaExiste($db, "erp_distribucion_cotizacion_items");
      $tieneRevision = $tieneItems && $this->columnasRevisionDisponibles($db);
      $itemsSelect = $tieneItems ? "(SELECT COUNT(*) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion)" : "0";
      $itemsPendientesSelect = $tieneRevision ? "(SELECT COUNT(*) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion AND (i.estatus_revision IS NULL OR i.estatus_revision='' OR i.estatus_revision IN ('por_confirmar','requiere_revision','pendiente_proveedor')))" : "0";
      $itemsRevisadosSelect = $tieneRevision ? "(SELECT COUNT(*) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion AND i.estatus_revision IS NOT NULL AND i.estatus_revision<>'' AND i.estatus_revision NOT IN ('por_confirmar','requiere_revision','pendiente_proveedor'))" : "0";
      $cantidadSolicitadaSelect = $tieneItems ? "(SELECT COALESCE(SUM(i.cantidad),0) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion)" : "0";
      $cantidadConfirmadaSelect = $tieneRevision ? "(SELECT COALESCE(SUM(i.cantidad_confirmada),0) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion)" : "0";
      $totalSolicitadoSelect = $tieneItems ? "(SELECT SUM(i.subtotal_snapshot) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion)" : "NULL";
      $totalConfirmadoItemsSelect = $tieneRevision ? "(SELECT SUM(COALESCE(i.cantidad_confirmada,0) * COALESCE(i.precio_unitario_snapshot,0)) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion)" : "NULL";
      $extra = $this->columnasEntregaPedidoDisponibles($db) ? "co.total_confirmado, co.tipo_entrega, co.costo_envio, co.respuesta_cliente_estatus, co.fecha_respuesta_cliente, co.fecha_respuesta_erp," : "NULL total_confirmado, NULL tipo_entrega, NULL costo_envio, NULL respuesta_cliente_estatus, NULL fecha_respuesta_cliente, NULL fecha_respuesta_erp,";
      $extra .= $this->columnasFacturacionPedidoDisponibles($db) ? "co.requiere_factura, co.facturacion_json," : "NULL requiere_factura, NULL facturacion_json,";
      $stmt = $db->prepare("SELECT co.id_cotizacion_distribucion, co.folio, co.id_cliente_distribucion, co.estatus, co.moneda, co.subtotal, co.total_estimado, " . $extra . " co.fecha_registro,
          c.nombre cliente, c.empresa, c.correo,
          " . $itemsSelect . " partidas,
          " . $itemsPendientesSelect . " partidas_pendientes,
          " . $itemsRevisadosSelect . " partidas_revisadas,
          " . $cantidadSolicitadaSelect . " cantidad_solicitada,
          " . $cantidadConfirmadaSelect . " cantidad_confirmada,
          " . $totalSolicitadoSelect . " total_solicitado_items,
          " . $totalConfirmadoItemsSelect . " total_confirmado_items
        FROM erp_distribucion_cotizaciones co
        " . $joinCliente . "
        WHERE " . implode(" AND ", $where) . "
        ORDER BY co.id_cotizacion_distribucion DESC
        LIMIT " . intval($limite));
      $stmt->execute($params);
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
      $joinCliente = $this->tablaExiste($db, "erp_distribucion_clientes") ? "LEFT JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=co.id_cliente_distribucion" : "LEFT JOIN (SELECT NULL nombre, NULL empresa, NULL correo, NULL telefono) c ON 1=0";
      $stmt = $db->prepare("SELECT co.*, c.nombre cliente, c.empresa, c.correo, c.telefono
        FROM erp_distribucion_cotizaciones co
        " . $joinCliente . "
        WHERE co.id_cotizacion_distribucion=:id LIMIT 1");
      $stmt->execute(array(":id" => $id));
      $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmtItems = $db->prepare("SELECT i.*, s.sku sku_actual, COALESCE(NULLIF(s.nombre,''), p.nombre, i.nombre_snapshot) producto_actual
        FROM erp_distribucion_cotizacion_items i
        LEFT JOIN erp_catalogo_skus s ON s.id_sku=i.id_sku
        LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        WHERE i.id_cotizacion_distribucion=:id
        ORDER BY i.id_cotizacion_item ASC");
      $stmtItems->execute(array(":id" => $id));
      return $this->respuesta(false, "success", "Cotizacion Distribucion consultada", array("configurado" => true, "cotizacion" => $cotizacion ?: null, "items" => $stmtItems->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar cotizacion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: registrar revision interna por partida de una solicitud Distribucion.
   * Impacto: Pedidos/cotizaciones Distribucion; permite confirmar, parcializar o marcar proveedor sin apartar inventario.
   * Contrato: requiere columnas cantidad_confirmada, estatus_revision, comentario_revision, fecha_revision e id_usuario_revision.
   */
  public function revisionPartidaInterna($datos = array(), $idUsuario = null) {
    $idItem = intval($this->valor($datos, "id_cotizacion_item", $this->valor($datos, "id", 0)));
    if ($idItem <= 0) {
      return $this->respuesta(true, "warning", "Partida requerida");
    }
    $estatus = trim((string) $this->valor($datos, "estatus_revision", ""));
    if (!in_array($estatus, array("por_confirmar", "confirmado", "parcial", "no_disponible", "pendiente_proveedor", "requiere_revision"), true)) {
      return $this->respuesta(true, "warning", "Estatus de revision no valido");
    }
    $cantidadConfirmada = max(0, min(999999, floatval($this->valor($datos, "cantidad_confirmada", 0))));
    $comentario = substr(trim((string) $this->valor($datos, "comentario_revision", "")), 0, 2000);
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasRevisionDisponibles($db)) {
      return $this->respuesta(true, "warning", "Revision por partida pendiente de esquema", array(
        "configurado" => false,
        "requiere_plan_esquema" => true,
        "columnas" => array("cantidad_confirmada", "estatus_revision", "comentario_revision", "fecha_revision", "id_usuario_revision")
      ));
    }
    try {
      $stmt = $db->prepare("SELECT i.id_cotizacion_item, i.id_cotizacion_distribucion, c.id_cliente_distribucion, c.estatus
        FROM erp_distribucion_cotizacion_items i
        INNER JOIN erp_distribucion_cotizaciones c ON c.id_cotizacion_distribucion=i.id_cotizacion_distribucion
        WHERE i.id_cotizacion_item=:id
        LIMIT 1");
      $stmt->execute(array(":id" => $idItem));
      $item = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$item) {
        return $this->respuesta(true, "warning", "Partida no encontrada");
      }
      $db->beginTransaction();
      $db->prepare("UPDATE erp_distribucion_cotizacion_items
        SET cantidad_confirmada=:cantidad, estatus_revision=:estatus, comentario_revision=:comentario,
            fecha_revision=NOW(), id_usuario_revision=:usuario
        WHERE id_cotizacion_item=:id")
        ->execute(array(
          ":cantidad" => $cantidadConfirmada,
          ":estatus" => $estatus,
          ":comentario" => $comentario,
          ":usuario" => $idUsuario,
          ":id" => $idItem
        ));
      if (in_array((string) $item["estatus"], array("pedido_solicitado", "recibida", "recibida_revision"), true)) {
        $db->prepare("UPDATE erp_distribucion_cotizaciones SET estatus='en_revision', fecha_actualizacion=NOW() WHERE id_cotizacion_distribucion=:id")
          ->execute(array(":id" => intval($item["id_cotizacion_distribucion"])));
      }
      $this->registrarAuditoria($db, "cotizacion_item", $idItem, "revision_partida", "ok", "Partida Distribucion revisada", array(
        "cantidad_confirmada" => $cantidadConfirmada,
        "estatus_revision" => $estatus,
        "comentario_revision" => $comentario
      ), $idUsuario, intval($item["id_cliente_distribucion"]));
      $db->commit();
      return $this->respuesta(false, "success", "Revision de partida guardada", array(
        "ejecutado" => true,
        "id_cotizacion_item" => $idItem,
        "estatus_revision" => $estatus,
        "guardrails" => array("no_aparta_inventario" => true, "no_crea_venta" => true, "no_crea_pedido_erp" => true)
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo guardar revision de partida", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-30
   * Proposito: guardar respuesta interna de surtido y entrega para que el cliente la revise en Distribucion.
   * Impacto: Pedidos Distribucion; no aparta inventario ni crea venta/pedido ERP.
   * Contrato: POST interno protegido; requiere columnas de entrega en erp_distribucion_cotizaciones.
   */
  public function configurarEntregaInterna($datos = array(), $idUsuario = null) {
    $id = intval($this->valor($datos, "id_cotizacion_distribucion", $this->valor($datos, "id", 0)));
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Pedido requerido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasEntregaPedidoDisponibles($db) || !$this->columnasFacturacionPedidoDisponibles($db)) {
      return $this->respuesta(true, "warning", "Configuracion de entrega pendiente de esquema", array("configurado" => false, "requiere_plan_esquema" => true));
    }
    $entrega = $this->normalizarEntregaPedido($datos, array());
    $facturacion = $this->normalizarFacturacionPedido($datos, array());
    try {
      $stmt = $db->prepare("SELECT id_cotizacion_distribucion, id_cliente_distribucion, estatus FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id LIMIT 1");
      $stmt->execute(array(":id" => $id));
      $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$pedido) {
        return $this->respuesta(true, "warning", "Pedido no encontrado");
      }
      $db->beginTransaction();
      $total = $this->totalConfirmadoPedido($db, $id, $entrega["costo_envio"]);
      $db->prepare("UPDATE erp_distribucion_cotizaciones
        SET tipo_entrega=:tipo_entrega,
            entrega_habilitar_envio=:habilitar_envio,
            entrega_habilitar_recoger_tienda=:habilitar_recoger,
            costo_envio=:costo_envio,
            direccion_envio_json=:direccion_envio,
            requiere_factura=:requiere_factura,
            facturacion_json=:facturacion,
            total_confirmado=:total_confirmado,
            estatus='respondida',
            fecha_respuesta_erp=NOW(),
            id_usuario_respuesta_erp=:usuario,
            fecha_actualizacion=NOW()
        WHERE id_cotizacion_distribucion=:id")
        ->execute(array(
          ":tipo_entrega" => $entrega["tipo_entrega"],
          ":habilitar_envio" => $entrega["entrega_habilitar_envio"],
          ":habilitar_recoger" => $entrega["entrega_habilitar_recoger_tienda"],
          ":costo_envio" => $this->decimalONull($entrega["costo_envio"]),
          ":direccion_envio" => json_encode($entrega["direccion_envio"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          ":requiere_factura" => $facturacion["requiere_factura"],
          ":facturacion" => json_encode($facturacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          ":total_confirmado" => $this->decimalONull($total),
          ":usuario" => $idUsuario,
          ":id" => $id
        ));
      $this->registrarAuditoria($db, "cotizacion", $id, "configurar_entrega", "ok", "Respuesta de pedido Distribucion guardada", array(
        "estatus_anterior" => $pedido["estatus"],
        "estatus" => "respondida",
        "entrega" => $entrega,
        "facturacion" => $facturacion,
        "total_confirmado" => $total,
        "nota" => trim((string) $this->valor($datos, "nota", ""))
      ), $idUsuario, intval($pedido["id_cliente_distribucion"]));
      $db->commit();
      return $this->respuesta(false, "success", "Respuesta de pedido guardada para el cliente", array(
        "ejecutado" => true,
        "id_cotizacion_distribucion" => $id,
        "estatus" => "respondida",
        "total_confirmado" => $total,
        "entrega" => $entrega
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo guardar respuesta de pedido", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-30
   * Proposito: listar pedidos propios para el portal Distribucion.
   * Impacto: Cliente externo; consulta solo documentos de su cuenta y no expone stock interno.
   */
  public function pedidosCliente($filtros = array(), $contexto = array()) {
    $permiso = $this->validarClientePedido($contexto);
    if ($permiso) { return $permiso; }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(false, "warning", "Pedidos Distribucion pendientes de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 50))));
      $extra = $this->columnasEntregaPedidoDisponibles($db) ? "total_confirmado, tipo_entrega, entrega_habilitar_envio, entrega_habilitar_recoger_tienda, costo_envio, respuesta_cliente_estatus, fecha_respuesta_cliente, fecha_respuesta_erp," : "NULL total_confirmado, NULL tipo_entrega, NULL entrega_habilitar_envio, NULL entrega_habilitar_recoger_tienda, NULL costo_envio, NULL respuesta_cliente_estatus, NULL fecha_respuesta_cliente, NULL fecha_respuesta_erp,";
      $extra .= $this->columnasFacturacionPedidoDisponibles($db) ? "requiere_factura, facturacion_json," : "NULL requiere_factura, NULL facturacion_json,";
      $extraDocumento = $this->columnasDocumentoDisponibles($db) ? "tipo_documento, id_cotizacion_origen," : "'pedido' tipo_documento, NULL id_cotizacion_origen,";
      $stmt = $db->prepare("SELECT id_cotizacion_distribucion, id_cotizacion_distribucion id_pedido_distribucion, folio, " . $extraDocumento . " estatus, moneda, subtotal, total_estimado, " . $extra . " comentarios, fecha_registro, fecha_actualizacion,
          (SELECT COUNT(*) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion) partidas,
          (SELECT COALESCE(SUM(i.cantidad),0) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion) cantidad_solicitada,
          (SELECT COALESCE(SUM(i.cantidad_confirmada),0) FROM erp_distribucion_cotizacion_items i WHERE i.id_cotizacion_distribucion=co.id_cotizacion_distribucion) cantidad_confirmada
        FROM erp_distribucion_cotizaciones co
        WHERE id_cliente_distribucion=:cliente
          AND " . $this->condicionDocumentoPedido($db, "co") . "
        ORDER BY id_cotizacion_distribucion DESC
        LIMIT " . intval($limite));
      $stmt->execute(array(":cliente" => $idCliente));
      return $this->respuesta(false, "success", "Pedidos Distribucion consultados", array("configurado" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar pedidos", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-30
   * Proposito: consultar detalle de pedido propio con cantidades confirmadas por ERP.
   * Impacto: Cliente externo; permite decidir si acepta sin consultar tablas internas.
   */
  public function pedidoDetalleCliente($filtros = array(), $contexto = array()) {
    $permiso = $this->validarClientePedido($contexto);
    if ($permiso) { return $permiso; }
    $id = intval($this->valor($filtros, "id_cotizacion_distribucion", $this->valor($filtros, "id", 0)));
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Pedido requerido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->tablaExiste($db, "erp_distribucion_cotizacion_items")) {
      return $this->respuesta(false, "warning", "Detalle de pedido pendiente de esquema", array("configurado" => false, "pedido" => null, "items" => array()));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT * FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id AND id_cliente_distribucion=:cliente AND " . $this->condicionDocumentoPedido($db) . " LIMIT 1");
      $stmt->execute(array(":id" => $id, ":cliente" => $idCliente));
      $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$pedido) {
        return $this->respuesta(true, "warning", "Pedido no encontrado");
      }
      $pedido["id_pedido_distribucion"] = intval($pedido["id_cotizacion_distribucion"]);
      if (!isset($pedido["tipo_documento"]) || $pedido["tipo_documento"] === null || $pedido["tipo_documento"] === "") {
        $pedido["tipo_documento"] = "pedido";
      }
      $stmtItems = $db->prepare("SELECT i.id_cotizacion_item, i.id_sku, i.sku_snapshot, i.nombre_snapshot, i.cantidad,
          i.precio_unitario_snapshot, i.subtotal_snapshot, i.disponibilidad_snapshot,
          i.cantidad_confirmada, i.estatus_revision, i.comentario_revision, i.fecha_revision,
          s.sku sku_actual, COALESCE(NULLIF(s.nombre,''), p.nombre, i.nombre_snapshot) producto_actual
        FROM erp_distribucion_cotizacion_items i
        LEFT JOIN erp_catalogo_skus s ON s.id_sku=i.id_sku
        LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        WHERE i.id_cotizacion_distribucion=:id
        ORDER BY i.id_cotizacion_item ASC");
      $stmtItems->execute(array(":id" => $id));
      return $this->respuesta(false, "success", "Pedido Distribucion consultado", array(
        "configurado" => true,
        "pedido" => $pedido,
        "items" => $stmtItems->fetchAll(PDO::FETCH_ASSOC),
        "opciones_entrega" => array(
          "envio" => intval($this->valor($pedido, "entrega_habilitar_envio", 1)) === 1,
          "recoger_tienda" => intval($this->valor($pedido, "entrega_habilitar_recoger_tienda", 1)) === 1
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar pedido", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-30
   * Proposito: registrar aceptacion o ajuste solicitado por el cliente sobre la respuesta del ERP.
   * Impacto: Pedidos Distribucion; conserva trazabilidad antes de surtir.
   */
  public function responderPedidoCliente($datos = array(), $contexto = array()) {
    $permiso = $this->validarClientePedido($contexto);
    if ($permiso) { return $permiso; }
    $id = intval($this->valor($datos, "id_cotizacion_distribucion", $this->valor($datos, "id", 0)));
    $respuesta = trim((string) $this->valor($datos, "respuesta", ""));
    if (!in_array($respuesta, array("aceptado", "rechazado", "requiere_ajuste"), true)) {
      return $this->respuesta(true, "warning", "Respuesta no valida");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasEntregaPedidoDisponibles($db)) {
      return $this->respuesta(true, "warning", "Respuesta de pedido pendiente de esquema", array("configurado" => false));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT id_cotizacion_distribucion, id_cliente_distribucion, estatus FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id AND id_cliente_distribucion=:cliente AND " . $this->condicionDocumentoPedido($db) . " LIMIT 1");
      $stmt->execute(array(":id" => $id, ":cliente" => $idCliente));
      $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$pedido) {
        return $this->respuesta(true, "warning", "Pedido no encontrado");
      }
      if (!in_array((string) $pedido["estatus"], array("en_revision", "respondida", "respondido_por_erp"), true)) {
        return $this->respuesta(true, "warning", "Este pedido aun no tiene una propuesta comercial para responder.");
      }
      $estatusNuevo = $respuesta === "aceptado" ? "cliente_acepto" : ($respuesta === "rechazado" ? "cliente_rechazo" : "requiere_ajuste_cliente");
      $comentario = substr(trim((string) $this->valor($datos, "comentario", "")), 0, 2000);
      $entrega = $this->normalizarEntregaPedido($datos, $pedido);
      $db->beginTransaction();
      $db->prepare("UPDATE erp_distribucion_cotizaciones
        SET respuesta_cliente_estatus=:respuesta,
            respuesta_cliente_comentario=:comentario,
            tipo_entrega=:tipo_entrega,
            estatus=:estatus,
            fecha_respuesta_cliente=NOW(),
            fecha_actualizacion=NOW()
        WHERE id_cotizacion_distribucion=:id")
        ->execute(array(
          ":respuesta" => $respuesta,
          ":comentario" => $comentario,
          ":tipo_entrega" => $entrega["tipo_entrega"],
          ":estatus" => $estatusNuevo,
          ":id" => $id
        ));
      $this->registrarAuditoria($db, "cotizacion", $id, "respuesta_cliente", "ok", "Cliente respondio pedido Distribucion", array(
        "estatus_anterior" => $pedido["estatus"],
        "estatus" => $estatusNuevo,
        "respuesta" => $respuesta,
        "comentario" => $comentario,
        "tipo_entrega" => $entrega["tipo_entrega"]
      ), null, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Respuesta registrada", array(
        "ejecutado" => true,
        "id_cotizacion_distribucion" => $id,
        "respuesta" => $respuesta,
        "estatus" => $estatusNuevo
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo registrar respuesta", array("detalle" => "error_controlado"));
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
      $cantidad = floatval($this->valor($item, "cantidad", 1));
      if ($cantidad <= 0) { continue; }
      $salida[] = array(
        "id_sku" => $idSku,
        "cantidad" => min(9999, $cantidad)
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

  private function columnaExiste($db, $tabla, $columna) {
    try {
      $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function columnasRevisionDisponibles($db) {
    foreach (array("cantidad_confirmada", "estatus_revision", "comentario_revision", "fecha_revision", "id_usuario_revision") as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_cotizacion_items", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function columnasEntregaPedidoDisponibles($db) {
    foreach (array("total_confirmado", "tipo_entrega", "entrega_habilitar_envio", "entrega_habilitar_recoger_tienda", "costo_envio", "direccion_envio_json", "respuesta_cliente_estatus", "respuesta_cliente_comentario", "fecha_respuesta_cliente", "fecha_respuesta_erp", "id_usuario_respuesta_erp") as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_cotizaciones", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function columnasFacturacionPedidoDisponibles($db) {
    foreach (array("requiere_factura", "facturacion_json") as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_cotizaciones", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function columnasDocumentoDisponibles($db) {
    foreach (array("tipo_documento", "id_cotizacion_origen", "id_pedido_relacionado", "nombre_documento") as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_cotizaciones", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function condicionDocumentoPedido($db, $alias = "") {
    $prefijo = $alias !== "" ? $alias . "." : "";
    $estatusPedido = "('pedido_solicitado','en_revision','respondida','respondido_por_erp','cliente_acepto','requiere_ajuste_cliente','cliente_rechazo','cancelado','cerrado')";
    $condicionLegacy = "(" . $prefijo . "folio LIKE 'DPED-%' OR " . $prefijo . "estatus IN " . $estatusPedido . ")";
    if ($this->columnasDocumentoDisponibles($db)) {
      return "(" . $prefijo . "tipo_documento='pedido' OR " . $condicionLegacy . ")";
    }
    return $condicionLegacy;
  }

  private function condicionDocumentoCotizacion($db, $alias = "") {
    $prefijo = $alias !== "" ? $alias . "." : "";
    $noPedido = "NOT " . $this->condicionDocumentoPedido($db, $alias);
    if ($this->columnasDocumentoDisponibles($db)) {
      return "(" . $prefijo . "tipo_documento='cotizacion' AND " . $noPedido . ")";
    }
    return "(((" . $prefijo . "folio LIKE 'DCOT-%') OR " . $prefijo . "estatus IN ('borrador','enviada_como_pedido','cancelada','vencida','recibida','recibida_revision')) AND " . $noPedido . ")";
  }

  private function insertarItemsSnapshot($db, $idCotizacion, $items, $skuInfo, $comentariosEntrada = array()) {
    $stmtItem = $db->prepare("INSERT INTO erp_distribucion_cotizacion_items
      (id_cotizacion_distribucion, id_sku, sku_snapshot, nombre_snapshot, cantidad, precio_unitario_snapshot, subtotal_snapshot, disponibilidad_snapshot, snapshot_json, fecha_registro)
      VALUES (:cotizacion, :sku, :sku_snapshot, :nombre, :cantidad, :precio, :subtotal, :disponibilidad, :snapshot, NOW())");
    foreach ($items as $item) {
      $idSku = intval($this->valor($item, "id_sku", 0));
      if ($idSku <= 0) { continue; }
      $info = isset($skuInfo[$idSku]) ? $skuInfo[$idSku] : array("sku" => null, "nombre" => null);
      $comentario = $this->valor($comentariosEntrada, $idSku, $this->valor($item, "comentario", ""));
      $snapshot = $item;
      $snapshot["comentario"] = $comentario;
      $stmtItem->execute(array(
        ":cotizacion" => intval($idCotizacion),
        ":sku" => $idSku,
        ":sku_snapshot" => $this->valor($info, "sku", null),
        ":nombre" => $this->valor($info, "nombre", null),
        ":cantidad" => floatval($this->valor($item, "cantidad", 1)),
        ":precio" => $this->decimalONull($this->valor($item, "precio_unitario", null)),
        ":subtotal" => $this->decimalONull($this->valor($item, "subtotal", null)),
        ":disponibilidad" => $this->valor($item, "disponibilidad", null),
        ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      ));
    }
  }

  private function actualizarCotizacionClienteEstatus($datos, $contexto, $estatus, $accion) {
    $id = intval($this->valor($datos, "id_cotizacion_distribucion", 0));
    if ($id <= 0) { return $this->respuesta(true, "warning", "Cotizacion requerida"); }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasDocumentoDisponibles($db)) {
      return $this->respuesta(true, "warning", "Cotizaciones Distribucion pendientes de esquema", array("configurado" => false));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT id_cotizacion_distribucion, estatus FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id AND id_cliente_distribucion=:cliente AND " . $this->condicionDocumentoCotizacion($db) . " LIMIT 1");
      $stmt->execute(array(":id" => $id, ":cliente" => $idCliente));
      $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cotizacion) { return $this->respuesta(true, "warning", "Cotizacion no encontrada"); }
      if ((string) $cotizacion["estatus"] !== "borrador") {
        return $this->respuesta(true, "warning", "Solo se pueden cancelar cotizaciones en borrador");
      }
      $db->beginTransaction();
      $db->prepare("UPDATE erp_distribucion_cotizaciones SET estatus=:estatus, fecha_actualizacion=NOW() WHERE id_cotizacion_distribucion=:id")
        ->execute(array(":estatus" => $estatus, ":id" => $id));
      $this->registrarAuditoria($db, "cotizacion", $id, $accion, "ok", "Cotizacion Distribucion actualizada", array(
        "estatus_anterior" => $cotizacion["estatus"],
        "estatus" => $estatus,
        "comentario" => trim((string) $this->valor($datos, "comentario", ""))
      ), null, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Cotizacion actualizada", array("id_cotizacion_distribucion" => $id, "estatus" => $estatus));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo actualizar cotizacion", array("detalle" => "error_controlado"));
    }
  }

  private function validarClienteCotizacion($contexto) {
    if (empty($contexto["autenticado"])) {
      return $this->respuesta(true, "warning", "Debes iniciar sesion para consultar cotizaciones", array("requiere_autenticacion" => true));
    }
    $permisos = $this->valor($contexto, "permisos", array());
    if (!is_array($permisos) || !in_array("distribucion.cotizacion.solicitar", $permisos, true)) {
      return $this->respuesta(true, "warning", "No tienes permiso para cotizaciones", array("requiere_permiso" => "distribucion.cotizacion.solicitar"));
    }
    if (intval($this->valor($contexto, "id_cliente_distribucion", 0)) <= 0) {
      return $this->respuesta(true, "warning", "Cliente Distribucion requerido");
    }
    return null;
  }

  private function accionesCotizacionCliente($estatus) {
    if ($estatus === "borrador") {
      return array("ver", "editar", "imprimir", "enviar_como_pedido", "cancelar", "duplicar");
    }
    if ($estatus === "enviada_como_pedido") {
      return array("ver", "imprimir", "duplicar", "ver_pedido");
    }
    if (in_array($estatus, array("cancelada", "vencida"), true)) {
      return array("ver", "duplicar");
    }
    return array("ver", "imprimir", "duplicar");
  }

  private function normalizarEntregaPedido($datos, $base = array()) {
    $tipo = trim((string) $this->valor($datos, "tipo_entrega", $this->valor($datos, "metodo_entrega", $this->valor($base, "tipo_entrega", "por_definir"))));
    if (!in_array($tipo, array("por_definir", "envio", "recoger_tienda"), true)) {
      $tipo = "por_definir";
    }
    $habilitarEnvio = intval($this->valor($datos, "entrega_habilitar_envio", $this->valor($base, "entrega_habilitar_envio", 1))) === 1 ? 1 : 0;
    $habilitarRecoger = intval($this->valor($datos, "entrega_habilitar_recoger_tienda", $this->valor($base, "entrega_habilitar_recoger_tienda", 1))) === 1 ? 1 : 0;
    if ($tipo === "envio" && $habilitarEnvio !== 1) { $tipo = "por_definir"; }
    if ($tipo === "recoger_tienda" && $habilitarRecoger !== 1) { $tipo = "por_definir"; }
    $direccion = $this->valor($datos, "direccion_envio", array());
    if (is_string($direccion)) {
      $decodificada = json_decode($direccion, true);
      $direccion = is_array($decodificada) ? $decodificada : array("texto" => substr($direccion, 0, 1000));
    }
    if (!is_array($direccion)) { $direccion = array(); }
    return array(
      "tipo_entrega" => $tipo,
      "entrega_habilitar_envio" => $habilitarEnvio,
      "entrega_habilitar_recoger_tienda" => $habilitarRecoger,
      "costo_envio" => max(0, floatval($this->valor($datos, "costo_envio", $this->valor($base, "costo_envio", 0)))),
      "direccion_envio" => $direccion
    );
  }

  private function normalizarFacturacionPedido($datos, $base = array()) {
    $entrada = $this->valor($datos, "facturacion", array());
    if (is_string($entrada)) {
      $decodificada = json_decode($entrada, true);
      $entrada = is_array($decodificada) ? $decodificada : array();
    }
    if (!is_array($entrada)) { $entrada = array(); }
    $baseJson = $this->valor($base, "facturacion_json", "");
    if (is_string($baseJson) && $baseJson !== "") {
      $baseDecodificada = json_decode($baseJson, true);
      if (is_array($baseDecodificada)) {
        $base = array_merge($base, $baseDecodificada);
      }
    }
    $requiere = intval($this->valor($datos, "requiere_factura", $this->valor($entrada, "requiere_factura", $this->valor($base, "requiere_factura", 0)))) === 1 ? 1 : 0;
    $salida = array(
      "requiere_factura" => $requiere,
      "rfc" => strtoupper(substr(trim((string) $this->valor($entrada, "rfc", $this->valor($datos, "rfc_facturacion", $this->valor($base, "rfc", "")))), 0, 20)),
      "razon_social" => substr(trim((string) $this->valor($entrada, "razon_social", $this->valor($datos, "razon_social", $this->valor($base, "razon_social", "")))), 0, 220),
      "regimen_fiscal" => substr(trim((string) $this->valor($entrada, "regimen_fiscal", $this->valor($datos, "regimen_fiscal", $this->valor($base, "regimen_fiscal", "")))), 0, 120),
      "uso_cfdi" => strtoupper(substr(trim((string) $this->valor($entrada, "uso_cfdi", $this->valor($datos, "uso_cfdi", $this->valor($base, "uso_cfdi", "")))), 0, 20)),
      "codigo_postal_fiscal" => substr(trim((string) $this->valor($entrada, "codigo_postal_fiscal", $this->valor($datos, "codigo_postal_fiscal", $this->valor($base, "codigo_postal_fiscal", "")))), 0, 20),
      "correo_facturacion" => strtolower(substr(trim((string) $this->valor($entrada, "correo_facturacion", $this->valor($datos, "correo_facturacion", $this->valor($base, "correo_facturacion", "")))), 0, 180)),
      "comentarios_facturacion" => substr(trim((string) $this->valor($entrada, "comentarios_facturacion", $this->valor($datos, "comentarios_facturacion", $this->valor($base, "comentarios_facturacion", "")))), 0, 1000)
    );
    if ($salida["requiere_factura"] !== 1) {
      $salida["rfc"] = "";
      $salida["razon_social"] = "";
      $salida["regimen_fiscal"] = "";
      $salida["uso_cfdi"] = "";
      $salida["codigo_postal_fiscal"] = "";
      $salida["correo_facturacion"] = "";
      $salida["comentarios_facturacion"] = "";
    }
    return $salida;
  }

  private function validarClientePedido($contexto) {
    if (empty($contexto["autenticado"])) {
      return $this->respuesta(true, "warning", "Debes iniciar sesion para consultar pedidos", array("requiere_autenticacion" => true));
    }
    $permisos = $this->valor($contexto, "permisos", array());
    if (!is_array($permisos) || (!in_array("distribucion.pedido.preliminar", $permisos, true) && !in_array("distribucion.cotizacion.solicitar", $permisos, true))) {
      return $this->respuesta(true, "warning", "No tienes permiso para consultar pedidos", array("requiere_permiso" => "distribucion.pedido.preliminar"));
    }
    if (intval($this->valor($contexto, "id_cliente_distribucion", 0)) <= 0) {
      return $this->respuesta(true, "warning", "Cliente Distribucion requerido");
    }
    return null;
  }

  private function totalConfirmadoPedido($db, $idCotizacion, $costoEnvio) {
    if (!$this->tablaExiste($db, "erp_distribucion_cotizacion_items")) {
      return $this->decimalONull($costoEnvio);
    }
    $stmt = $db->prepare("SELECT SUM(COALESCE(cantidad_confirmada, 0) * COALESCE(precio_unitario_snapshot, 0)) subtotal
      FROM erp_distribucion_cotizacion_items
      WHERE id_cotizacion_distribucion=:id");
    $stmt->execute(array(":id" => intval($idCotizacion)));
    $subtotal = $stmt->fetchColumn();
    if ($subtotal === null || $subtotal === false) {
      $subtotal = 0;
    }
    return round(floatval($subtotal) + max(0, floatval($costoEnvio)), 6);
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }
}
