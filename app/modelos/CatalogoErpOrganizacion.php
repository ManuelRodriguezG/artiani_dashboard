<?php

class CatalogoErpOrganizacion extends CRUD {

  public function listarPropuestasNombres() {
    try {
      $db = $this->getConexion();
      $filas = $db->query("SELECT r.id_revision_nombre, r.nombre_actual, r.nombre_proveedor, r.nombre_propuesto, r.estatus,
        s.id_sku, s.sku, p.id_producto_erp, p.nombre nombre_maestro
        FROM erp_catalogo_revision_nombres r
        INNER JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=r.id_producto_erp
        ORDER BY FIELD(r.estatus, 'pendiente', 'aprobado', 'descartado'), r.id_revision_nombre DESC")->fetchAll(PDO::FETCH_ASSOC);
      return array("error" => false, "tipo" => "success", "mensaje" => "Propuestas consultadas", "depurar" => $filas);
    } catch (Exception $e) {
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => array());
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: expone el historial de fusiones de productos maestros para auditoria operativa.
   * Impacto: Catalogo ERP; solo lectura, no modifica fusiones ni habilita reversas automaticas.
   */
  public function listarFusiones() {
    try {
      $db = $this->getConexion();
      $filas = $db->query("SELECT f.id_fusion, f.id_producto_origen, f.id_producto_destino, f.motivo,
        f.skus_movidos, f.usuario_id, f.fecha_registro,
        origen.codigo_producto codigo_origen, origen.nombre nombre_origen, origen.estatus estatus_origen,
        destino.codigo_producto codigo_destino, destino.nombre nombre_destino, destino.estatus estatus_destino
        FROM erp_catalogo_productos_fusiones f
        LEFT JOIN erp_catalogo_productos origen ON origen.id_producto_erp=f.id_producto_origen
        LEFT JOIN erp_catalogo_productos destino ON destino.id_producto_erp=f.id_producto_destino
        ORDER BY f.id_fusion DESC
        LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
      return array("error" => false, "tipo" => "success", "mensaje" => "Historial de fusiones consultado", "depurar" => $filas);
    } catch (Exception $e) {
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => array());
    }
  }

  public function resolverPropuestaNombre($datos) {
    $id = intval(isset($datos["id_revision_nombre"]) ? $datos["id_revision_nombre"] : 0);
    $accion = isset($datos["accion"]) ? $datos["accion"] : "";
    $nombre = trim(isset($datos["nombre_propuesto"]) ? $datos["nombre_propuesto"] : "");
    if ($id <= 0 || !in_array($accion, array("aprobar", "descartar"), true)) {
      return array("error" => true, "tipo" => "warning", "mensaje" => "La decisión no es válida", "depurar" => null);
    }
    if ($accion === "aprobar" && $nombre === "") {
      return array("error" => true, "tipo" => "warning", "mensaje" => "El nombre propuesto no puede quedar vacío", "depurar" => null);
    }
    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_revision_nombres WHERE id_revision_nombre=:id FOR UPDATE");
      $stmt->execute(array(":id" => $id));
      $idSku = intval($stmt->fetchColumn());
      if ($idSku <= 0) {
        throw new Exception("La propuesta no existe");
      }
      if ($accion === "aprobar") {
        $db->prepare("UPDATE erp_catalogo_skus SET nombre=:nombre, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_sku=:sku")
          ->execute(array(":nombre" => substr($nombre, 0, 255), ":sku" => $idSku));
      }
      $db->prepare("UPDATE erp_catalogo_revision_nombres SET nombre_propuesto=:nombre, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_revision_nombre=:id")
        ->execute(array(":nombre" => substr($nombre, 0, 255), ":estatus" => $accion === "aprobar" ? "aprobado" : "descartado", ":id" => $id));
      $db->commit();
      return array("error" => false, "tipo" => "success", "mensaje" => $accion === "aprobar" ? "Nombre del SKU actualizado" : "Propuesta descartada", "depurar" => array("id_sku" => $idSku));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
    }
  }

  public function buscarProductosFusion($termino) {
    $termino = trim((string) $termino);
    if (mb_strlen($termino, "UTF-8") < 2) {
      return array("error" => false, "tipo" => "success", "mensaje" => "Captura al menos 2 caracteres", "depurar" => array());
    }
    try {
      $db = $this->getConexion();
      $like = "%" . $termino . "%";
      $stmt = $db->prepare("SELECT p.id_producto_erp, p.codigo_producto, p.nombre, p.estatus,
        COUNT(DISTINCT s.id_sku) total_skus,
        GROUP_CONCAT(DISTINCT s.sku ORDER BY s.sku SEPARATOR ', ') skus,
        COUNT(DISTINCT i.id_imagen_erp) total_imagenes
        FROM erp_catalogo_productos p
        LEFT JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp
        LEFT JOIN erp_catalogo_imagenes i ON i.id_producto_erp=p.id_producto_erp AND i.estatus='activo'
        WHERE p.estatus<>'fusionado'
          AND (p.codigo_producto LIKE :termino OR p.nombre LIKE :termino OR s.sku LIKE :termino OR s.nombre LIKE :termino)
        GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre, p.estatus
        ORDER BY
          CASE
            WHEN p.codigo_producto=:exacto THEN 0
            WHEN s.sku=:exacto THEN 1
            WHEN p.nombre LIKE :inicio THEN 2
            ELSE 3
          END,
          p.nombre
        LIMIT 20");
      $stmt->execute(array(":termino" => $like, ":exacto" => $termino, ":inicio" => $termino . "%"));
      return array("error" => false, "tipo" => "success", "mensaje" => "Productos encontrados", "depurar" => $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => array());
    }
  }

  public function previsualizarFusion($idOrigen, $idDestino) {
    try {
      $db = $this->getConexion();
      $idOrigen = intval($idOrigen);
      $idDestino = intval($idDestino);
      if ($idOrigen <= 0 || $idDestino <= 0 || $idOrigen === $idDestino) {
        throw new Exception("Selecciona dos productos maestros diferentes");
      }
      return array("error" => false, "tipo" => "success", "mensaje" => "Previsualización de fusión", "depurar" => array(
        "origen" => $this->productoResumen($db, $idOrigen),
        "destino" => $this->productoResumen($db, $idDestino),
        "skus_origen" => $this->skusProducto($db, $idOrigen),
        "skus_destino" => $this->skusProducto($db, $idDestino),
        "imagenes_origen" => $this->imagenesProducto($db, $idOrigen),
        "imagenes_destino" => $this->imagenesProducto($db, $idDestino)
      ));
    } catch (Exception $e) {
      return array("error" => true, "tipo" => "warning", "mensaje" => $e->getMessage(), "depurar" => null);
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: fusiona productos maestros solo con motivo explicito y trazabilidad minima.
   * Impacto: Catalogo ERP; accion irreversible automaticamente sin snapshot, por eso exige motivo operativo.
   */
  public function fusionarProductos($datos, $usuarioId = null) {
    $idOrigen = intval(isset($datos["id_producto_origen"]) ? $datos["id_producto_origen"] : 0);
    $idDestino = intval(isset($datos["id_producto_destino"]) ? $datos["id_producto_destino"] : 0);
    $motivo = trim(isset($datos["motivo"]) ? $datos["motivo"] : "");
    if ($idOrigen <= 0 || $idDestino <= 0 || $idOrigen === $idDestino) {
      return array("error" => true, "tipo" => "warning", "mensaje" => "Selecciona origen y destino diferentes", "depurar" => null);
    }
    if (mb_strlen($motivo, "UTF-8") < 10) {
      return array("error" => true, "tipo" => "warning", "mensaje" => "Indica un motivo claro de al menos 10 caracteres para fusionar", "depurar" => null);
    }
    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $origen = $this->productoResumen($db, $idOrigen, true);
      $destino = $this->productoResumen($db, $idDestino, true);
      if (!$origen || !$destino) {
        throw new Exception("No se encontró alguno de los productos maestros");
      }
      $skusOrigen = $this->skusProducto($db, $idOrigen);
      if (empty($skusOrigen)) {
        throw new Exception("El producto origen no tiene SKU para mover");
      }

      $db->prepare("UPDATE erp_catalogo_skus SET id_producto_erp=:destino, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:origen")
        ->execute(array(":destino" => $idDestino, ":origen" => $idOrigen));
      $db->prepare("UPDATE erp_catalogo_canales_vinculos SET id_producto_erp=:destino, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:origen")
        ->execute(array(":destino" => $idDestino, ":origen" => $idOrigen));
      $db->prepare("UPDATE erp_catalogo_imagenes SET id_producto_erp=:destino, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:origen")
        ->execute(array(":destino" => $idDestino, ":origen" => $idOrigen));
      $db->prepare("UPDATE erp_catalogo_revision_nombres SET id_producto_erp=:destino, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:origen")
        ->execute(array(":destino" => $idDestino, ":origen" => $idOrigen));
      $db->prepare("INSERT IGNORE INTO erp_catalogo_producto_categorias (id_producto_erp, id_categoria_erp, es_principal)
        SELECT :destino, id_categoria_erp, 0 FROM erp_catalogo_producto_categorias WHERE id_producto_erp=:origen")
        ->execute(array(":destino" => $idDestino, ":origen" => $idOrigen));
      $db->prepare("UPDATE erp_catalogo_productos SET maneja_variantes=1, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:destino")
        ->execute(array(":destino" => $idDestino));
      $db->prepare("UPDATE erp_catalogo_productos SET estatus='fusionado', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:origen")
        ->execute(array(":origen" => $idOrigen));

      if ($this->columnaExiste($db, "erp_inventario_existencias", "id_producto")) {
        $db->prepare("UPDATE erp_inventario_existencias SET id_producto=:destino WHERE id_producto=:origen")
          ->execute(array(":destino" => $idDestino, ":origen" => $idOrigen));
      }
      if ($this->columnaExiste($db, "erp_inventario_movimientos", "id_producto")) {
        $db->prepare("UPDATE erp_inventario_movimientos SET id_producto=:destino WHERE id_producto=:origen")
          ->execute(array(":destino" => $idDestino, ":origen" => $idOrigen));
      }

      $db->prepare("INSERT INTO erp_catalogo_productos_fusiones (id_producto_origen, id_producto_destino, motivo, skus_movidos, usuario_id)
        VALUES (:origen, :destino, :motivo, :skus, :usuario)")
        ->execute(array(":origen" => $idOrigen, ":destino" => $idDestino, ":motivo" => $motivo ?: null, ":skus" => count($skusOrigen), ":usuario" => $usuarioId));
      $db->commit();
      return array("error" => false, "tipo" => "success", "mensaje" => "Productos maestros fusionados", "depurar" => array("skus_movidos" => count($skusOrigen)));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
    }
  }

  public function generarPropuestasNombres() {
    try {
      $db = $this->getConexion();
      $tablaProveedores = $this->tablaProveedoresFuente($db);
      $stmt = $db->query("SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre nombre_actual,
        pl.nombre nombre_proveedor, pl.marca marca_proveedor, pl.id_lista_proveedor
        FROM erp_catalogo_skus s
        INNER JOIN " . $tablaProveedores . " pl ON LOWER(TRIM(pl.sku))=LOWER(TRIM(s.sku))
        INNER JOIN (
          SELECT LOWER(TRIM(sku)) sku_clave, MAX(id_producto) id_producto
          FROM " . $tablaProveedores . "
          WHERE sku IS NOT NULL AND TRIM(sku)<>''
          GROUP BY LOWER(TRIM(sku))
        ) ultima ON ultima.id_producto=pl.id_producto
        ORDER BY s.id_sku");
      $guardar = $db->prepare("INSERT INTO erp_catalogo_revision_nombres
        (id_producto_erp, id_sku, nombre_actual, nombre_proveedor, nombre_propuesto, evidencia_json, estatus)
        VALUES (:producto, :sku, :actual, :proveedor, :propuesto, :evidencia, 'pendiente')
        ON DUPLICATE KEY UPDATE nombre_actual=VALUES(nombre_actual), nombre_proveedor=VALUES(nombre_proveedor),
          nombre_propuesto=IF(estatus='pendiente', VALUES(nombre_propuesto), nombre_propuesto),
          evidencia_json=VALUES(evidencia_json), fecha_actualizacion=CURRENT_TIMESTAMP");
      $total = 0;
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $propuesto = $this->normalizarNombre($fila["nombre_proveedor"]);
        if ($propuesto === "" || $this->claveNombre($propuesto) === $this->claveNombre($fila["nombre_actual"])) {
          continue;
        }
        $guardar->execute(array(
          ":producto" => intval($fila["id_producto_erp"]),
          ":sku" => intval($fila["id_sku"]),
          ":actual" => $fila["nombre_actual"],
          ":proveedor" => $fila["nombre_proveedor"],
          ":propuesto" => $propuesto,
          ":evidencia" => json_encode(array(
            "fuente" => "erp_proveedores_listas_productos",
            "sku" => $fila["sku"],
            "marca_proveedor" => $fila["marca_proveedor"],
            "id_lista_proveedor" => $fila["id_lista_proveedor"]
          ), JSON_UNESCAPED_UNICODE)
        ));
        $total++;
      }
      return array("error" => false, "tipo" => "success", "mensaje" => "Propuestas de nombres preparadas", "depurar" => array("propuestas" => $total));
    } catch (Exception $e) {
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
    }
  }

  private function normalizarNombre($nombre) {
    $nombre = preg_replace('/\s+/', ' ', trim((string) $nombre));
    if ($nombre === "" || !mb_check_encoding($nombre, "UTF-8") || preg_match('/[¢£ �]/u', $nombre)) {
      return "";
    }
    $nombre = preg_replace('/\s+by\s+/i', ' ', $nombre);
    $nombre = preg_replace('/\bIMPORTADO\b/i', '', $nombre);
    $nombre = preg_replace('/\s+/', ' ', trim($nombre));
    if ($nombre === "") {
      return "";
    }
    $nombre = mb_strtolower($nombre, "UTF-8");
    $nombre = preg_replace_callback('/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)(?:\s*x\s*(\d+(?:\.\d+)?))?/i', function ($m) {
      return $m[1] . " × " . $m[2] . (isset($m[3]) && $m[3] !== "" ? " × " . $m[3] : "");
    }, $nombre);
    return mb_strtoupper(mb_substr($nombre, 0, 1, "UTF-8"), "UTF-8") . mb_substr($nombre, 1, null, "UTF-8");
  }

  private function claveNombre($nombre) {
    return mb_strtolower(preg_replace('/[^[:alnum:]]+/u', '', (string) $nombre), "UTF-8");
  }

  private function productoResumen($db, $idProducto, $bloquear = false) {
    $stmt = $db->prepare("SELECT p.id_producto_erp, p.codigo_producto, p.nombre, p.estatus,
      COUNT(DISTINCT s.id_sku) total_skus,
      COUNT(DISTINCT i.id_imagen_erp) total_imagenes
      FROM erp_catalogo_productos p
      LEFT JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp
      LEFT JOIN erp_catalogo_imagenes i ON i.id_producto_erp=p.id_producto_erp AND i.estatus='activo'
      WHERE p.id_producto_erp=:producto
      GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre, p.estatus" . ($bloquear ? " FOR UPDATE" : ""));
    $stmt->execute(array(":producto" => intval($idProducto)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function skusProducto($db, $idProducto) {
    $stmt = $db->prepare("SELECT id_sku, sku, nombre, estatus FROM erp_catalogo_skus WHERE id_producto_erp=:producto ORDER BY sku");
    $stmt->execute(array(":producto" => intval($idProducto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function imagenesProducto($db, $idProducto) {
    $stmt = $db->prepare("SELECT id_imagen_erp, tipo_imagen, url_imagen, fuente, estatus
      FROM erp_catalogo_imagenes
      WHERE id_producto_erp=:producto
      ORDER BY FIELD(estatus, 'activo', 'inactivo'), FIELD(tipo_imagen, 'portada', 'galeria', 'detalle', 'empaque', 'referencia'), orden, id_imagen_erp
      LIMIT 8");
    $stmt->execute(array(":producto" => intval($idProducto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function columnaExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetchColumn();
  }

  private function tablaProveedoresFuente($db) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='artianilocal_productivo_staging' AND TABLE_NAME='erp_proveedores_listas_productos' LIMIT 1");
    $stmt->execute();
    return $stmt->fetchColumn()
      ? "artianilocal_productivo_staging.erp_proveedores_listas_productos"
      : "erp_proveedores_listas_productos";
  }
}
