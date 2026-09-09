<?php

class PreciosCanalesErp extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: resolver precios comerciales por canal desde listas ERP activas.
   * Impacto: Precios multi-canal; permite a Distribucion consumir precios sin duplicar reglas en frontend.
   * Contrato: read-only; valida canal/SKU visible, vigencia y permisos; no devuelve costos, margenes ni proveedores.
   */
  public function resolverPreciosCanal($canal, $items = array(), $contexto = array()) {
    try {
      $db = $this->getConexion();
      $readiness = $this->readiness($db);
      $items = $this->itemsNormalizados($items);
      if (!$readiness["ready"]) {
        return $this->respuesta(false, "info", "Precios de canal pendientes de esquema ERP", array(
          "configurado" => false,
          "items" => $this->itemsPrecioOculto($items, "esquema_pendiente"),
          "readiness" => $readiness
        ));
      }
      if (empty($items)) {
        return $this->respuesta(true, "warning", "Agrega SKUs para resolver precios", array("configurado" => true, "items" => array()));
      }

      $ids = array();
      foreach ($items as $item) { $ids[] = intval($item["id_sku"]); }
      $visibles = $this->skusVisiblesCanal($db, $canal, $ids);
      $precios = $this->preciosPorSku($db, $canal, array_keys($visibles), $contexto);
      $tipoPrecio = $this->tipoPrecioContexto($contexto);

      $salida = array();
      foreach ($items as $item) {
        $idSku = intval($item["id_sku"]);
        if (!isset($visibles[$idSku])) {
          $salida[] = $this->itemPrecioOculto($item, "sku_no_visible_canal", false);
          continue;
        }
        if ($tipoPrecio === "sin_permiso") {
          $salida[] = $this->itemPrecioOculto($item, "sin_permiso", true);
          continue;
        }
        if (!isset($precios[$idSku])) {
          $salida[] = $this->itemPrecioOculto($item, "precio_no_disponible", true);
          continue;
        }
        $precio = $precios[$idSku];
        $salida[] = array(
          "id_sku" => $idSku,
          "cantidad" => $item["cantidad"],
          "precio" => array(
            "visible" => true,
            "tipo" => $precio["tipo"],
            "moneda" => $precio["moneda"],
            "monto" => floatval($precio["precio"]),
            "mensaje" => null,
            "id_lista_precio" => intval($precio["id_lista_precio"])
          ),
          "visible_canal" => true
        );
      }

      return $this->respuesta(false, "success", "Precios de canal resueltos", array(
        "configurado" => true,
        "tipo_resolucion" => $tipoPrecio,
        "items" => $salida,
        "readiness" => $readiness,
        "guardrails" => array("no_costos" => true, "no_margenes" => true, "no_proveedores" => true)
      ));
    } catch (Exception $e) {
      $depurar = array("detalle" => "error_controlado");
      if (defined("DISTRIBUCION_API_DEBUG") && DISTRIBUCION_API_DEBUG) {
        $depurar["debug_error"] = $e->getMessage();
      }
      return $this->respuesta(true, "danger", "No se pudieron resolver precios de canal", $depurar);
    }
  }

  private function preciosPorSku($db, $canal, $idsSku, $contexto) {
    $idsSku = array_values(array_unique(array_map("intval", $idsSku)));
    if (empty($idsSku) || $this->tipoPrecioContexto($contexto) === "sin_permiso") {
      return array();
    }

    $params = array();
    $placeholders = array();
    foreach ($idsSku as $i => $idSku) {
      $ph = ":sku" . $i;
      $placeholders[] = $ph;
      $params[$ph] = $idSku;
    }

    $idLista = intval($this->valor($contexto, "id_lista_precio", 0));
    $whereLista = "(l.canal=:canal OR l.canal IS NULL OR l.canal='')";
    $params[":canal"] = $canal;
    $tipo = $this->tipoPrecioContexto($contexto);
    if ($tipo === "lista_asignada" && $idLista > 0) {
      $whereLista = "l.id_lista_precio=:lista";
      unset($params[":canal"]);
      $params[":lista"] = $idLista;
    }

    $sql = "SELECT d.id_sku, d.precio, COALESCE(NULLIF(d.moneda, ''), 'MXN') moneda,
        l.id_lista_precio, l.codigo lista_codigo, l.nombre lista_nombre, l.prioridad
      FROM erp_listas_precios_detalle d
      INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio
      WHERE d.id_sku IN (" . implode(",", $placeholders) . ")
        AND d.estatus='activo'
        AND d.precio>0
        AND COALESCE(NULLIF(d.moneda, ''), 'MXN')='MXN'
        AND l.estatus='activa'
        AND " . $whereLista . "
        AND (d.fecha_inicio IS NULL OR d.fecha_inicio<=NOW())
        AND (d.fecha_fin IS NULL OR d.fecha_fin>=NOW())
        AND (l.fecha_inicio IS NULL OR l.fecha_inicio<=NOW())
        AND (l.fecha_fin IS NULL OR l.fecha_fin>=NOW())
      ORDER BY d.id_sku ASC, l.prioridad ASC, d.id_lista_precio_detalle DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $mapa = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $idSku = intval($fila["id_sku"]);
      if (isset($mapa[$idSku])) { continue; }
      $fila["tipo"] = $tipo;
      $mapa[$idSku] = $fila;
    }
    return $mapa;
  }

  private function skusVisiblesCanal($db, $canal, $idsSku) {
    $idsSku = array_values(array_unique(array_map("intval", $idsSku)));
    if (empty($idsSku)) { return array(); }
    $params = array(":canal" => $canal);
    $placeholders = array();
    foreach ($idsSku as $i => $idSku) {
      $ph = ":sku" . $i;
      $placeholders[] = $ph;
      $params[$ph] = $idSku;
    }
    $sql = "SELECT s.id_sku
      FROM erp_catalogo_canales_vinculos cv
      INNER JOIN erp_catalogo_skus s ON s.id_sku=cv.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE cv.canal=:canal
        AND cv.sincronizar_catalogo=1
        AND cv.sincronizar_precio=1
        AND cv.estatus IN ('activo','publicado','aprobado')
        AND p.estatus='activo'
        AND s.estatus='activo'
        AND s.id_sku IN (" . implode(",", $placeholders) . ")";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $mapa = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $mapa[intval($fila["id_sku"])] = true;
    }
    return $mapa;
  }

  private function tipoPrecioContexto($contexto) {
    $permisos = $this->valor($contexto, "permisos", array());
    $permisos = is_array($permisos) ? $permisos : array();
    if (in_array("distribucion.precio.ver_lista_asignada", $permisos, true) && intval($this->valor($contexto, "id_lista_precio", 0)) > 0) {
      return "lista_asignada";
    }
    if (in_array("distribucion.precio.ver_publico", $permisos, true)) {
      return "publico_autorizado";
    }
    if (in_array("distribucion.precio.ver_mayoreo", $permisos, true)) {
      return "mayoreo_erp";
    }
    return "sin_permiso";
  }

  private function readiness($db) {
    $tablas = array("erp_catalogo_canales_vinculos", "erp_catalogo_productos", "erp_catalogo_skus", "erp_listas_precios", "erp_listas_precios_detalle");
    $faltantes = array();
    if (!$db) {
      return array("ready" => false, "faltantes" => $tablas, "conexion" => false);
    }
    foreach ($tablas as $tabla) {
      if (!$this->tablaExiste($db, $tabla)) { $faltantes[] = $tabla; }
    }
    return array("ready" => empty($faltantes), "faltantes" => $faltantes, "conexion" => true);
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

  private function itemsNormalizados($items) {
    $items = is_array($items) ? array_slice($items, 0, 50) : array();
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

  private function itemsPrecioOculto($items, $motivo) {
    $salida = array();
    foreach ($items as $item) {
      $salida[] = $this->itemPrecioOculto($item, $motivo, false);
    }
    return $salida;
  }

  private function itemPrecioOculto($item, $motivo, $visibleCanal) {
    return array(
      "id_sku" => intval($this->valor($item, "id_sku", 0)),
      "cantidad" => floatval($this->valor($item, "cantidad", 1)),
      "precio" => array("visible" => false, "tipo" => $motivo, "moneda" => "MXN", "monto" => null, "mensaje" => "Solicitar precio"),
      "visible_canal" => (bool) $visibleCanal
    );
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }
}
