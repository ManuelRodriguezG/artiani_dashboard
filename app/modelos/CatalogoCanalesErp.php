<?php

class CatalogoCanalesErp extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar SKUs visibles por canal comercial desde Catalogo ERP.
   * Impacto: Catalogo multi-canal; permite a Distribucion consumir datos ERP sin consultar tablas directas.
   * Contrato: read-only; filtra canal, productos/SKUs activos y no devuelve costos, margenes ni proveedores.
   */
  public function catalogoCanal($canal, $filtros = array()) {
    try {
      $db = $this->getConexion();
      $readiness = $this->readiness($db);
      if (!$readiness["ready"]) {
        return $this->respuesta(false, "info", "Catalogo de canal pendiente de esquema ERP", array(
          "configurado" => false,
          "items" => array(),
          "paginacion" => array("pagina" => 1, "limite" => 24, "total" => 0),
          "readiness" => $readiness
        ));
      }

      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $limite = max(1, min(60, intval($this->valor($filtros, "limite", 24))));
      $offset = ($pagina - 1) * $limite;
      $where = array(
        "cv.canal=:canal",
        "cv.sincronizar_catalogo=1",
        "cv.estatus IN ('activo','publicado','aprobado')",
        "p.estatus='activo'",
        "s.estatus='activo'"
      );
      $params = array(":canal" => $canal);

      $q = trim((string) $this->valor($filtros, "q", ""));
      if ($q !== "") {
        $where[] = "(p.nombre LIKE :q OR s.nombre LIKE :q OR s.sku LIKE :q OR m.nombre LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }

      $idExterno = trim((string) $this->valor($filtros, "id_externo", ""));
      if ($idExterno !== "") {
        $where[] = "cv.id_externo=:id_externo";
        $params[":id_externo"] = $idExterno;
      }

      $marca = intval($this->valor($filtros, "marca", 0));
      if ($marca > 0) {
        $where[] = "p.id_marca_erp=:marca";
        $params[":marca"] = $marca;
      }

      $categoria = intval($this->valor($filtros, "categoria", 0));
      if ($categoria > 0) {
        $where[] = "EXISTS (
          SELECT 1 FROM erp_catalogo_producto_categorias pcf
          WHERE pcf.id_producto_erp=p.id_producto_erp AND pcf.id_categoria_erp=:categoria
        )";
        $params[":categoria"] = $categoria;
      }

      $sqlBase = $this->sqlBase($where);
      $stmtTotal = $db->prepare("SELECT COUNT(*) FROM (" . $sqlBase . ") t");
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());

      $sql = $sqlBase . " ORDER BY " . $this->ordenSql($this->valor($filtros, "orden", "relevancia")) . " LIMIT " . intval($limite) . " OFFSET " . intval($offset);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = $this->formatearItem($fila);
      }

      return $this->respuesta(false, "success", "Catalogo de canal consultado", array(
        "configurado" => true,
        "items" => $items,
        "paginacion" => array("pagina" => $pagina, "limite" => $limite, "total" => $total),
        "filtros_aplicados" => array(
          "q" => $q,
          "categoria" => $categoria,
          "marca" => $marca,
          "orden" => $this->ordenNormalizado($this->valor($filtros, "orden", "relevancia"))
        ),
        "readiness" => $readiness
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar catalogo de canal", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar ficha comercial por slug/id externo de canal.
   * Impacto: Catalogo multi-canal; evita usar slugs locales en Distribucion como fuente de verdad.
   * Contrato: read-only; devuelve un item sanitizado o null.
   */
  public function productoCanal($canal, $slug) {
    $slug = trim((string) $slug);
    if ($slug === "") {
      return $this->respuesta(true, "warning", "Slug requerido", array("item" => null));
    }
    $respuesta = $this->catalogoCanal($canal, array("limite" => 1, "id_externo" => $slug));
    $depurar = $this->valor($respuesta, "depurar", array());
    $items = $this->valor($depurar, "items", array());
    return $this->respuesta(false, empty($items) ? "info" : "success", empty($items) ? "Producto no disponible para canal" : "Producto de canal consultado", array(
      "configurado" => $this->valor($depurar, "configurado", false),
      "item" => empty($items) ? null : $items[0],
      "readiness" => $this->valor($depurar, "readiness", array())
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar categorias presentes en SKUs visibles por canal.
   * Impacto: Navegacion multi-canal; evita filtros que no tengan productos autorizados.
   * Contrato: read-only; solo conteos comerciales, sin costos ni stock.
   */
  public function categoriasCanal($canal) {
    return $this->facetaCanal($canal, "categorias");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar marcas presentes en SKUs visibles por canal.
   * Impacto: Navegacion multi-canal; evita exponer marcas sin producto autorizado.
   * Contrato: read-only; solo conteos comerciales.
   */
  public function marcasCanal($canal) {
    return $this->facetaCanal($canal, "marcas");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: resolver disponibilidad comercial por canal sin exponer stock exacto.
   * Impacto: Inventario multi-canal; permite cotizacion externa con estados seguros.
   * Contrato: read-only; valida que el SKU este vinculado al canal y no aparta inventario.
   */
  public function disponibilidadCanal($canal, $items = array()) {
    try {
      $db = $this->getConexion();
      $readiness = $this->readiness($db);
      if (!$readiness["ready"]) {
        return $this->respuesta(false, "info", "Disponibilidad de canal pendiente de esquema ERP", array("configurado" => false, "items" => array(), "readiness" => $readiness));
      }

      $items = $this->itemsNormalizados($items);
      if (empty($items)) {
        return $this->respuesta(true, "warning", "Agrega SKUs para resolver disponibilidad", array("configurado" => true, "items" => array()));
      }

      $ids = array();
      foreach ($items as $item) { $ids[] = intval($item["id_sku"]); }
      $placeholders = array();
      $params = array(":canal" => $canal);
      foreach (array_values(array_unique($ids)) as $i => $idSku) {
        $ph = ":sku" . $i;
        $placeholders[] = $ph;
        $params[$ph] = $idSku;
      }

      $sql = "SELECT s.id_sku, COALESCE(inv.cantidad_disponible, 0) existencia_disponible
        FROM erp_catalogo_canales_vinculos cv
        INNER JOIN erp_catalogo_skus s ON s.id_sku=cv.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN (
          SELECT id_sku_erp, SUM(cantidad_disponible) cantidad_disponible
          FROM erp_inventario_existencias
          WHERE estatus_existencia IN ('disponible','agotada')
          GROUP BY id_sku_erp
        ) inv ON inv.id_sku_erp=s.id_sku
        WHERE cv.canal=:canal
          AND cv.sincronizar_catalogo=1
          AND cv.sincronizar_existencia=1
          AND cv.estatus IN ('activo','publicado','aprobado')
          AND p.estatus='activo'
          AND s.estatus='activo'
          AND s.id_sku IN (" . implode(",", $placeholders) . ")";
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $mapa = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $mapa[intval($fila["id_sku"])] = floatval($fila["existencia_disponible"]);
      }

      $salida = array();
      foreach ($items as $item) {
        $idSku = intval($item["id_sku"]);
        if (!array_key_exists($idSku, $mapa)) {
          $salida[] = array(
            "id_sku" => $idSku,
            "cantidad" => $item["cantidad"],
            "disponibilidad" => array("visible" => true, "estado" => "consultar_disponibilidad", "mensaje" => "Consultar disponibilidad"),
            "visible_canal" => false
          );
          continue;
        }
        $estado = $this->estadoDisponibilidad($mapa[$idSku]);
        $salida[] = array(
          "id_sku" => $idSku,
          "cantidad" => $item["cantidad"],
          "disponibilidad" => array("visible" => true, "estado" => $estado, "mensaje" => $this->mensajeDisponibilidad($estado)),
          "visible_canal" => true
        );
      }

      return $this->respuesta(false, "success", "Disponibilidad de canal resuelta", array(
        "configurado" => true,
        "items" => $salida,
        "guardrails" => array("no_stock_exacto" => true, "no_aparta_inventario" => true),
        "readiness" => $readiness
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo resolver disponibilidad de canal", array("detalle" => "error_controlado"));
    }
  }

  private function facetaCanal($canal, $tipo) {
    try {
      $db = $this->getConexion();
      $readiness = $this->readiness($db);
      if (!$readiness["ready"]) {
        return $this->respuesta(false, "info", "Facetas de canal pendientes de esquema ERP", array("configurado" => false, "items" => array(), "readiness" => $readiness));
      }

      if ($tipo === "marcas") {
        $sql = "SELECT m.id_marca_erp id, m.nombre, COUNT(DISTINCT s.id_sku) total
          FROM erp_catalogo_canales_vinculos cv
          INNER JOIN erp_catalogo_skus s ON s.id_sku=cv.id_sku
          INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
          INNER JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
          WHERE cv.canal=:canal AND cv.sincronizar_catalogo=1 AND cv.estatus IN ('activo','publicado','aprobado') AND p.estatus='activo' AND s.estatus='activo' AND m.estatus='activo'
          GROUP BY m.id_marca_erp, m.nombre
          ORDER BY m.nombre ASC";
      } else {
        $sql = "SELECT c.id_categoria_erp id, c.nombre, COALESCE(c.ruta, c.nombre) ruta, c.id_categoria_padre, COUNT(DISTINCT s.id_sku) total
          FROM erp_catalogo_canales_vinculos cv
          INNER JOIN erp_catalogo_skus s ON s.id_sku=cv.id_sku
          INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
          INNER JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp
          INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
          WHERE cv.canal=:canal AND cv.sincronizar_catalogo=1 AND cv.estatus IN ('activo','publicado','aprobado') AND p.estatus='activo' AND s.estatus='activo' AND c.estatus='activo'
          GROUP BY c.id_categoria_erp, c.nombre, c.ruta, c.id_categoria_padre
          ORDER BY COALESCE(c.ruta, c.nombre) ASC";
      }
      $stmt = $db->prepare($sql);
      $stmt->execute(array(":canal" => $canal));
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = array(
          "id" => intval($fila["id"]),
          "nombre" => $fila["nombre"],
          "slug" => $this->slugificar($fila["nombre"]),
          "ruta" => $this->valor($fila, "ruta", $fila["nombre"]),
          "id_padre" => isset($fila["id_categoria_padre"]) ? intval($fila["id_categoria_padre"]) : null,
          "total" => intval($fila["total"])
        );
      }
      return $this->respuesta(false, "success", "Facetas de canal consultadas", array("configurado" => true, "items" => $items, "readiness" => $readiness));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar facetas de canal", array("detalle" => "error_controlado"));
    }
  }

  private function sqlBase($where) {
    return "SELECT cv.id_canal_vinculo, cv.id_externo, cv.sku_externo, cv.sincronizar_precio, cv.sincronizar_existencia,
        p.id_producto_erp, p.codigo_producto, p.nombre nombre_producto,
        s.id_sku, s.sku, COALESCE(NULLIF(s.nombre, ''), p.nombre) nombre_sku, s.tipo_inventario, s.factor_unidad_base,
        m.nombre marca, m.id_marca_erp,
        c.id_categoria_erp, c.nombre categoria_nombre, COALESCE(c.ruta, c.nombre) categoria,
        img.url_imagen imagen_principal,
        COALESCE(inv.cantidad_disponible, 0) existencia_disponible
      FROM erp_catalogo_canales_vinculos cv
      INNER JOIN erp_catalogo_skus s ON s.id_sku=cv.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
      LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
      LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      LEFT JOIN erp_catalogo_imagenes img ON img.id_imagen_erp=(
        SELECT i.id_imagen_erp
        FROM erp_catalogo_imagenes i
        WHERE i.id_producto_erp=p.id_producto_erp
          AND (i.id_sku=s.id_sku OR i.id_sku IS NULL OR i.id_sku=0)
          AND i.estatus='activo'
          AND TRIM(COALESCE(i.url_imagen,''))<>''
        ORDER BY CASE WHEN i.id_sku=s.id_sku THEN 0 ELSE 1 END, CASE WHEN i.tipo_imagen='principal' THEN 0 ELSE 1 END, i.orden ASC, i.id_imagen_erp ASC
        LIMIT 1
      )
      LEFT JOIN (
        SELECT id_sku_erp, SUM(cantidad_disponible) cantidad_disponible
        FROM erp_inventario_existencias
        WHERE estatus_existencia IN ('disponible','agotada')
        GROUP BY id_sku_erp
      ) inv ON inv.id_sku_erp=s.id_sku
      WHERE " . implode(" AND ", $where);
  }

  private function formatearItem($fila) {
    $estado = $this->estadoDisponibilidad(floatval($this->valor($fila, "existencia_disponible", 0)));
    return array(
      "id_producto" => intval($fila["id_producto_erp"]),
      "id_sku" => intval($fila["id_sku"]),
      "slug" => trim((string) $this->valor($fila, "id_externo", "")),
      "sku" => $fila["sku"],
      "nombre" => $fila["nombre_sku"],
      "marca" => $this->valor($fila, "marca", ""),
      "categoria" => $this->valor($fila, "categoria", ""),
      "imagen_principal" => $this->urlRecurso($this->valor($fila, "imagen_principal", null)),
      "presentacion" => $this->presentacion($fila),
      "precio" => array("visible" => false, "tipo" => "sin_permiso", "moneda" => "MXN", "monto" => null, "mensaje" => "Solicitar precio"),
      "disponibilidad" => array("visible" => true, "estado" => $estado, "mensaje" => $this->mensajeDisponibilidad($estado)),
      "acciones" => array("ver_detalle" => true, "solicitar_precio" => true, "agregar_cotizacion" => false)
    );
  }

  private function readiness($db) {
    $tablas = array("erp_catalogo_canales_vinculos", "erp_catalogo_productos", "erp_catalogo_skus", "erp_catalogo_marcas", "erp_catalogo_categorias", "erp_catalogo_producto_categorias", "erp_catalogo_imagenes", "erp_inventario_existencias");
    $faltantes = array();
    if (!$db) {
      return array("ready" => false, "faltantes" => $tablas, "conexion" => false);
    }
    foreach ($tablas as $tabla) {
      if (!$this->tablaExiste($db, $tabla)) { $faltantes[] = $tabla; }
    }
    return array("ready" => empty($faltantes), "faltantes" => $faltantes, "conexion" => true);
  }

  private function urlRecurso($url) {
    $url = trim((string) $url);
    if ($url === "") { return null; }
    if (preg_match('/^https?:\/\//i', $url) || strpos($url, "/") === 0) {
      return $url;
    }
    $base = defined("RUTA_RECURSOS_IMG") ? RUTA_RECURSOS_IMG : (defined("RUTA_URL") ? RUTA_URL : "");
    return rtrim($base, "/") . "/" . ltrim($url, "/");
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

  private function tablaExiste($db, $tabla) {
    try {
      $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
      $stmt->execute(array(":tabla" => $tabla));
      return (bool) $stmt->fetchColumn();
    } catch (Exception $e) {
      return false;
    }
  }

  private function estadoDisponibilidad($cantidad) {
    if ($cantidad <= 0) { return "consultar_disponibilidad"; }
    if ($cantidad <= 3) { return "pocas_piezas"; }
    return "disponible";
  }

  private function mensajeDisponibilidad($estado) {
    if ($estado === "disponible") { return "Disponible para cotizacion"; }
    if ($estado === "pocas_piezas") { return "Pocas piezas, sujeto a confirmacion"; }
    return "Consultar disponibilidad";
  }

  private function presentacion($fila) {
    $factor = floatval($this->valor($fila, "factor_unidad_base", 1));
    return $factor > 1 ? "Presentacion x " . rtrim(rtrim(number_format($factor, 6, ".", ""), "0"), ".") : "Unidad";
  }

  private function ordenSql($orden) {
    $orden = $this->ordenNormalizado($orden);
    if ($orden === "nombre") { return "nombre_sku ASC, s.sku ASC"; }
    if ($orden === "marca") { return "m.nombre ASC, nombre_sku ASC"; }
    return "cv.id_canal_vinculo DESC";
  }

  private function ordenNormalizado($orden) {
    $orden = trim((string) $orden);
    return in_array($orden, array("relevancia", "nombre", "marca"), true) ? $orden : "relevancia";
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function slugificar($texto) {
    $texto = strtolower(trim((string) $texto));
    $texto = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, "-");
  }
}
