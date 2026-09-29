<?php

class DistribucionClienteSurtidoApi extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: listar Mi catalogo del cliente externo Distribucion.
   * Impacto: Frontend Distribucion; permite separar productos de interes sin tocar inventario ERP.
   * Contrato: GET autenticado con `distribucion.mi_catalogo.gestionar`; solo devuelve SKUs publicados en canal Distribucion.
   */
  public function surtidoListar($filtros = array(), $contexto = array()) {
    $permiso = $this->requiereAlgunPermiso($contexto, array("distribucion.mi_catalogo.gestionar", "distribucion.surtido.gestionar"), "No tienes permiso para gestionar Mi catalogo");
    if ($permiso) { return $permiso; }
    $db = $this->getConexion();
    if (!$this->esquemaSurtidoOperativo($db)) {
      return $this->respuesta(false, "warning", "Mi catalogo Distribucion pendiente de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $limite = max(1, min(300, intval($this->valor($filtros, "limite", 150))));
      $q = trim((string) $this->valor($filtros, "q", ""));
      $where = array("cp.id_cliente_distribucion=:cliente", "cp.estatus='activo'", "cv.canal='distribucion'", "cv.estatus='activo'", "cv.sincronizar_catalogo=1");
      $params = array(":cliente" => $idCliente);
      if ($q !== "") {
        $where[] = "(s.sku LIKE :q OR s.nombre LIKE :q OR p.nombre LIKE :q OR cp.alias_cliente LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      $stmt = $db->prepare("SELECT cp.id_cliente_producto, cp.id_cliente_distribucion, cp.id_sku, cp.alias_cliente, cp.ubicacion_cliente,
          cp.prioridad, cp.notas, cp.fecha_registro, cp.fecha_actualizacion, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) nombre_sku,
          p.nombre producto, cv.id_externo slug
        FROM erp_distribucion_cliente_productos cp
        INNER JOIN erp_catalogo_skus s ON s.id_sku=cp.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        INNER JOIN erp_catalogo_canales_vinculos cv ON cv.id_sku=cp.id_sku
        WHERE " . implode(" AND ", $where) . "
        ORDER BY cp.prioridad DESC, p.nombre ASC, s.sku ASC
        LIMIT " . intval($limite));
      $stmt->execute($params);
      return $this->respuesta(false, "success", "Mi catalogo Distribucion consultado", array(
        "configurado" => true,
        "items" => $stmt->fetchAll(PDO::FETCH_ASSOC),
        "sesion" => $this->sesionSalida($contexto)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar Mi catalogo Distribucion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: agregar, actualizar o quitar productos de Mi catalogo del cliente externo.
   * Impacto: Frontend Distribucion; guarda interes comercial sin crear pedido ni apartar inventario.
   * Contrato: POST JSON autenticado con `distribucion.mi_catalogo.gestionar`; valida canal Distribucion.
   */
  public function surtidoGuardar($datos = array(), $contexto = array()) {
    $permiso = $this->requiereAlgunPermiso($contexto, array("distribucion.mi_catalogo.gestionar", "distribucion.surtido.gestionar"), "No tienes permiso para gestionar Mi catalogo");
    if ($permiso) { return $permiso; }
    $db = $this->getConexion();
    if (!$this->esquemaSurtidoOperativo($db)) {
      return $this->respuesta(true, "warning", "Mi catalogo Distribucion pendiente de esquema", array("configurado" => false));
    }
    $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
    $idSku = intval($this->valor($datos, "id_sku", 0));
    if ($idSku <= 0) {
      return $this->respuesta(true, "warning", "SKU requerido");
    }
    if (!$this->skuVisibleCanal($db, $idSku)) {
      return $this->respuesta(true, "warning", "SKU no disponible para Distribucion");
    }
    $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
    $estatus = in_array($estatus, array("activo", "inactivo"), true) ? $estatus : "activo";
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_distribucion_cliente_productos
        (id_cliente_distribucion, id_sku, alias_cliente, ubicacion_cliente, prioridad, estatus, origen, notas, fecha_registro, fecha_actualizacion)
        VALUES (:cliente, :sku, :alias, :ubicacion, :prioridad, :estatus, 'cliente', :notas, NOW(), NOW())
        ON DUPLICATE KEY UPDATE alias_cliente=VALUES(alias_cliente), ubicacion_cliente=VALUES(ubicacion_cliente),
          prioridad=VALUES(prioridad), estatus=VALUES(estatus), notas=VALUES(notas), fecha_actualizacion=NOW()");
      $stmt->execute(array(
        ":cliente" => $idCliente,
        ":sku" => $idSku,
        ":alias" => $this->textoNullable($this->valor($datos, "alias_cliente", null), 180),
        ":ubicacion" => $this->textoNullable($this->valor($datos, "ubicacion_cliente", null), 180),
        ":prioridad" => max(0, min(999, intval($this->valor($datos, "prioridad", 0)))),
        ":estatus" => $estatus,
        ":notas" => $this->textoNullable($this->valor($datos, "notas", null), 2000)
      ));
      $this->registrarAuditoria($db, "cliente_mi_catalogo", $idSku, $estatus === "activo" ? "guardar" : "desactivar", "ok", "Mi catalogo cliente actualizado", array(
        "id_sku" => $idSku,
        "estatus" => $estatus
      ), null, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", $estatus === "activo" ? "Producto guardado en Mi catalogo" : "Producto quitado de Mi catalogo", array(
        "configurado" => true,
        "ejecutado" => true,
        "id_sku" => $idSku,
        "estatus" => $estatus
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo guardar Mi catalogo Distribucion", array("detalle" => "error_controlado"));
    }
  }

  public function miCatalogoListar($filtros = array(), $contexto = array()) {
    return $this->surtidoListar($filtros, $contexto);
  }

  public function miCatalogoGuardar($datos = array(), $contexto = array()) {
    return $this->surtidoGuardar($datos, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: listar inventario declarado por el cliente externo.
   * Impacto: Frontend Distribucion; soporta conteos, minimos, maximos y resurtido sin exponer stock ERP.
   * Contrato: GET autenticado con `distribucion.inventario_cliente.gestionar`; no lee existencias ERP.
   */
  public function inventarioListar($filtros = array(), $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.inventario_cliente.gestionar", "No tienes permiso para gestionar tu inventario");
    if ($permiso) { return $permiso; }
    return $this->inventarioBase($filtros, $contexto, false);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: guardar conteo, minimo y maximo del inventario del cliente externo.
   * Impacto: Frontend Distribucion; alimenta sugerido de resurtido sin modificar inventario ERP.
   * Contrato: POST JSON autenticado con `distribucion.inventario_cliente.gestionar`; audita movimiento.
   */
  public function inventarioGuardarConteo($datos = array(), $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.inventario_cliente.gestionar", "No tienes permiso para gestionar tu inventario");
    if ($permiso) { return $permiso; }
    $db = $this->getConexion();
    if (!$this->esquemaInventarioOperativo($db)) {
      return $this->respuesta(true, "warning", "Inventario cliente Distribucion pendiente de esquema", array("configurado" => false));
    }
    $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
    $idSku = intval($this->valor($datos, "id_sku", 0));
    if ($idSku <= 0) {
      return $this->respuesta(true, "warning", "SKU requerido");
    }
    if (!$this->skuVisibleCanal($db, $idSku)) {
      return $this->respuesta(true, "warning", "SKU no disponible para Distribucion");
    }
    $existencia = $this->decimalSeguro($this->valor($datos, "existencia_cliente", $this->valor($datos, "existencia", 0)));
    $minimo = $this->decimalSeguro($this->valor($datos, "minimo", 0));
    $maximo = $this->decimalSeguro($this->valor($datos, "maximo", 0));
    if ($maximo > 0 && $minimo > $maximo) {
      return $this->respuesta(true, "warning", "El minimo no puede ser mayor al maximo");
    }
    try {
      $anterior = $this->inventarioActual($db, $idCliente, $idSku);
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_distribucion_cliente_inventario
        (id_cliente_distribucion, id_sku, existencia_cliente, minimo, maximo, unidad_cliente, fecha_conteo, estatus, notas, fecha_registro, fecha_actualizacion)
        VALUES (:cliente, :sku, :existencia, :minimo, :maximo, :unidad, NOW(), 'activo', :notas, NOW(), NOW())
        ON DUPLICATE KEY UPDATE existencia_cliente=VALUES(existencia_cliente), minimo=VALUES(minimo), maximo=VALUES(maximo),
          unidad_cliente=VALUES(unidad_cliente), fecha_conteo=NOW(), estatus='activo', notas=VALUES(notas), fecha_actualizacion=NOW()");
      $stmt->execute(array(
        ":cliente" => $idCliente,
        ":sku" => $idSku,
        ":existencia" => $existencia,
        ":minimo" => $minimo,
        ":maximo" => $maximo,
        ":unidad" => $this->textoNullable($this->valor($datos, "unidad_cliente", null), 40),
        ":notas" => $this->textoNullable($this->valor($datos, "notas", null), 2000)
      ));
      $this->registrarMovimientoInventario($db, $idCliente, $idSku, "conteo", $anterior, array(
        "existencia_cliente" => $existencia,
        "minimo" => $minimo,
        "maximo" => $maximo
      ), array("entrada" => $this->entradaAuditable($datos)));
      $this->registrarAuditoria($db, "cliente_inventario", $idSku, "guardar_conteo", "ok", "Inventario cliente actualizado", array(
        "id_sku" => $idSku,
        "existencia_cliente" => $existencia,
        "minimo" => $minimo,
        "maximo" => $maximo
      ), null, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Inventario guardado", array(
        "configurado" => true,
        "ejecutado" => true,
        "id_sku" => $idSku,
        "existencia_cliente" => $existencia,
        "minimo" => $minimo,
        "maximo" => $maximo,
        "sugerido" => $this->calcularSugerido($existencia, $minimo, $maximo)
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo guardar inventario cliente", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: calcular sugerido de resurtido desde inventario declarado por el cliente.
   * Impacto: Frontend Distribucion; permite armar solicitud de pedido sin mostrar existencia ERP.
   * Contrato: GET autenticado con `distribucion.resurtido.sugerido`; calcula maximo-existencia cuando existencia <= minimo.
   */
  public function sugeridoResurtido($filtros = array(), $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.resurtido.sugerido", "No tienes permiso para ver sugerido de resurtido");
    if ($permiso) { return $permiso; }
    return $this->inventarioBase($filtros, $contexto, true);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: crear solicitud de pedido preliminar desde sugerido de resurtido.
   * Impacto: Distribucion; agiliza pedido sin apartar inventario ni crear venta/pedido ERP.
   * Contrato: POST autenticado con `distribucion.resurtido.sugerido` y `distribucion.pedido.preliminar`.
   */
  public function pedidoDesdeSugerido($datos = array(), $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.resurtido.sugerido", "No tienes permiso para usar sugerido de resurtido");
    if ($permiso) { return $permiso; }
    $permisoPedido = $this->requierePermiso($contexto, "distribucion.pedido.preliminar", "No tienes permiso para solicitar pedidos");
    if ($permisoPedido) { return $permisoPedido; }
    $sugerido = $this->sugeridoResurtido(array("limite" => 300), $contexto);
    $items = array();
    foreach ($this->valor($this->valor($sugerido, "depurar", array()), "items", array()) as $item) {
      $cantidad = floatval($this->valor($item, "cantidad_sugerida", 0));
      if ($cantidad > 0) {
        $items[] = array("id_sku" => intval($this->valor($item, "id_sku", 0)), "cantidad" => $cantidad);
      }
    }
    if (empty($items)) {
      return $this->respuesta(true, "warning", "No hay productos sugeridos para solicitar");
    }
    require_once RUTA_APP . "/modelos/DistribucionCotizacionesApi.php";
    return (new DistribucionCotizacionesApi())->registrarPedidoPreliminar(array(
      "items" => $items,
      "comentarios" => trim((string) $this->valor($datos, "comentarios", "Pedido generado desde sugerido de resurtido"))
    ), $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: consultar Mi catalogo de clientes desde ERP interno.
   * Impacto: Admin Distribucion; permite observar productos seleccionados por cliente.
   * Contrato: GET interno protegido por controlador; read-only.
   */
  public function surtidosInternos($filtros = array()) {
    return $this->consultaInternaClienteProductos($filtros, "surtido");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: consultar inventarios declarados por clientes desde ERP interno.
   * Impacto: Admin Distribucion; permite prevenir demanda y revisar minimos/maximos.
   * Contrato: GET interno protegido por controlador; read-only.
   */
  public function inventariosInternos($filtros = array()) {
    return $this->consultaInternaClienteProductos($filtros, "inventario");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: consultar sugeridos de resurtido de clientes desde ERP interno.
   * Impacto: Admin Distribucion; identifica necesidades potenciales antes de pedido.
   * Contrato: GET interno protegido por controlador; read-only.
   */
  public function sugeridosInternos($filtros = array()) {
    return $this->consultaInternaClienteProductos($filtros, "sugerido");
  }

  private function inventarioBase($filtros, $contexto, $soloSugeridos) {
    $db = $this->getConexion();
    if (!$this->esquemaInventarioOperativo($db)) {
      return $this->respuesta(false, "warning", "Inventario cliente Distribucion pendiente de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $limite = max(1, min(300, intval($this->valor($filtros, "limite", 150))));
      $q = trim((string) $this->valor($filtros, "q", ""));
      $where = array("ci.id_cliente_distribucion=:cliente", "ci.estatus='activo'", "cv.canal='distribucion'", "cv.estatus='activo'", "cv.sincronizar_catalogo=1");
      $params = array(":cliente" => $idCliente);
      if ($q !== "") {
        $where[] = "(s.sku LIKE :q OR s.nombre LIKE :q OR p.nombre LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      if ($soloSugeridos) {
        $where[] = "ci.minimo > 0 AND ci.maximo > ci.existencia_cliente AND ci.existencia_cliente <= ci.minimo";
      }
      $stmt = $db->prepare("SELECT ci.id_cliente_inventario, ci.id_cliente_distribucion, ci.id_sku, ci.existencia_cliente, ci.minimo,
          ci.maximo, ci.unidad_cliente, ci.fecha_conteo, ci.notas, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) nombre_sku,
          p.nombre producto, cv.id_externo slug
        FROM erp_distribucion_cliente_inventario ci
        INNER JOIN erp_catalogo_skus s ON s.id_sku=ci.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        INNER JOIN erp_catalogo_canales_vinculos cv ON cv.id_sku=ci.id_sku
        WHERE " . implode(" AND ", $where) . "
        ORDER BY p.nombre ASC, s.sku ASC
        LIMIT " . intval($limite));
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $fila["cantidad_sugerida"] = $this->calcularSugerido(floatval($fila["existencia_cliente"]), floatval($fila["minimo"]), floatval($fila["maximo"]));
        $fila["requiere_resurtido"] = floatval($fila["cantidad_sugerida"]) > 0;
        $items[] = $fila;
      }
      return $this->respuesta(false, "success", $soloSugeridos ? "Sugerido de resurtido consultado" : "Inventario cliente consultado", array(
        "configurado" => true,
        "items" => $items,
        "sesion" => $this->sesionSalida($contexto),
        "formula" => "si existencia_cliente <= minimo, sugerido = maximo - existencia_cliente"
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar inventario cliente", array("detalle" => "error_controlado"));
    }
  }

  private function consultaInternaClienteProductos($filtros, $modo) {
    $db = $this->getConexion();
    $tablaRequerida = $modo === "surtido" ? "erp_distribucion_cliente_productos" : "erp_distribucion_cliente_inventario";
    if (!$db || !$this->tablaExiste($db, $tablaRequerida) || !$this->tablaExiste($db, "erp_distribucion_clientes")) {
      return $this->respuesta(false, "warning", "Informacion Distribucion pendiente de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $limite = max(1, min(300, intval($this->valor($filtros, "limite", 150))));
      $idCliente = intval($this->valor($filtros, "id_cliente_distribucion", 0));
      $q = trim((string) $this->valor($filtros, "q", ""));
      $idMarca = intval($this->valor($filtros, "id_marca_erp", $this->valor($filtros, "marca", 0)));
      $idCategoria = intval($this->valor($filtros, "id_categoria_erp", $this->valor($filtros, "categoria", 0)));
      $idProveedor = intval($this->valor($filtros, "id_proveedor", $this->valor($filtros, "proveedor", 0)));
      $where = array();
      $params = array();
      if ($idCliente > 0) {
        $where[] = "c.id_cliente_distribucion=:cliente";
        $params[":cliente"] = $idCliente;
      }
      if ($q !== "") {
        $where[] = "(c.nombre LIKE :q OR c.empresa LIKE :q OR c.correo LIKE :q OR s.sku LIKE :q OR s.nombre LIKE :q OR p.nombre LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      if ($idMarca > 0) {
        $where[] = "p.id_marca_erp=:marca";
        $params[":marca"] = $idMarca;
      }
      if ($idCategoria > 0 && $this->tablaExiste($db, "erp_catalogo_producto_categorias")) {
        $where[] = "EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pcf WHERE pcf.id_producto_erp=p.id_producto_erp AND pcf.id_categoria_erp=:categoria)";
        $params[":categoria"] = $idCategoria;
      }
      if ($idProveedor > 0 && $this->tablaExiste($db, "erp_catalogo_sku_proveedores")) {
        $where[] = "EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores spf WHERE spf.id_sku=s.id_sku AND spf.id_proveedor=:proveedor AND spf.estatus='activo')";
        $params[":proveedor"] = $idProveedor;
      }
      $whereSql = empty($where) ? "1=1" : implode(" AND ", $where);
      $joinMarca = $this->tablaExiste($db, "erp_catalogo_marcas") ? "LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp" : "LEFT JOIN (SELECT NULL nombre) m ON 1=0";
      $joinCategoria = $this->tablaExiste($db, "erp_catalogo_producto_categorias") && $this->tablaExiste($db, "erp_catalogo_categorias") ? "LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1 LEFT JOIN erp_catalogo_categorias cat ON cat.id_categoria_erp=pc.id_categoria_erp" : "LEFT JOIN (SELECT NULL ruta, NULL nombre) cat ON 1=0";
      $joinProveedor = $this->tablaExiste($db, "erp_catalogo_sku_proveedores") && $this->tablaExiste($db, "erp_proveedores") ? "LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1 LEFT JOIN erp_proveedores pr ON pr.id_proveedor=sp.id_proveedor" : "LEFT JOIN (SELECT NULL proveedor) pr ON 1=0";
      if ($modo === "surtido") {
        $sql = "SELECT cp.id_cliente_producto, cp.id_cliente_distribucion, c.nombre cliente, c.empresa, c.correo, cp.id_sku,
            s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) nombre_sku, p.nombre producto, cp.alias_cliente,
            cp.ubicacion_cliente, cp.prioridad, cp.estatus, cp.fecha_registro, cp.fecha_actualizacion,
            m.nombre marca, COALESCE(cat.ruta, cat.nombre) categoria, pr.proveedor proveedor_principal
          FROM erp_distribucion_cliente_productos cp
          INNER JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=cp.id_cliente_distribucion
          LEFT JOIN erp_catalogo_skus s ON s.id_sku=cp.id_sku
          LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
          " . $joinMarca . "
          " . $joinCategoria . "
          " . $joinProveedor . "
          WHERE " . $whereSql . "
          ORDER BY cp.fecha_actualizacion DESC, cp.id_cliente_producto DESC
          LIMIT " . intval($limite);
      } else {
        $extra = $modo === "sugerido" ? " AND ci.minimo > 0 AND ci.maximo > ci.existencia_cliente AND ci.existencia_cliente <= ci.minimo" : "";
        $sql = "SELECT ci.id_cliente_inventario, ci.id_cliente_distribucion, c.nombre cliente, c.empresa, c.correo, ci.id_sku,
            s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) nombre_sku, p.nombre producto, ci.existencia_cliente,
            ci.minimo, ci.maximo, (CASE WHEN ci.minimo > 0 AND ci.maximo > ci.existencia_cliente AND ci.existencia_cliente <= ci.minimo THEN ci.maximo - ci.existencia_cliente ELSE 0 END) cantidad_sugerida,
            ci.unidad_cliente, ci.fecha_conteo, ci.fecha_actualizacion, ci.notas,
            m.nombre marca, COALESCE(cat.ruta, cat.nombre) categoria, pr.proveedor proveedor_principal
          FROM erp_distribucion_cliente_inventario ci
          INNER JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=ci.id_cliente_distribucion
          LEFT JOIN erp_catalogo_skus s ON s.id_sku=ci.id_sku
          LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
          " . $joinMarca . "
          " . $joinCategoria . "
          " . $joinProveedor . "
          WHERE " . $whereSql . $extra . "
          ORDER BY cantidad_sugerida DESC, ci.fecha_actualizacion DESC
          LIMIT " . intval($limite);
      }
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      return $this->respuesta(false, "success", "Informacion Distribucion consultada", array("configurado" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar informacion Distribucion", array("detalle" => "error_controlado"));
    }
  }

  private function esquemaSurtidoOperativo($db) {
    return $db
      && $this->tablaExiste($db, "erp_distribucion_cliente_productos")
      && $this->tablaExiste($db, "erp_catalogo_skus")
      && $this->tablaExiste($db, "erp_catalogo_productos")
      && $this->tablaExiste($db, "erp_catalogo_canales_vinculos");
  }

  private function esquemaInventarioOperativo($db) {
    return $db
      && $this->tablaExiste($db, "erp_distribucion_cliente_inventario")
      && $this->tablaExiste($db, "erp_distribucion_cliente_inventario_movimientos")
      && $this->tablaExiste($db, "erp_catalogo_skus")
      && $this->tablaExiste($db, "erp_catalogo_productos")
      && $this->tablaExiste($db, "erp_catalogo_canales_vinculos");
  }

  private function requierePermiso($contexto, $permiso, $mensaje) {
    if (empty($contexto["autenticado"])) {
      return $this->respuesta(true, "warning", "Sesion Distribucion requerida", array("requiere_autenticacion" => true, "requiere_permiso" => $permiso));
    }
    $permisos = $this->valor($contexto, "permisos", array());
    if (is_array($permisos) && in_array($permiso, $permisos, true)) {
      return null;
    }
    return $this->respuesta(true, "warning", $mensaje, array("requiere_permiso" => $permiso, "sesion" => $this->sesionSalida($contexto)));
  }

  private function requiereAlgunPermiso($contexto, $permisosRequeridos, $mensaje) {
    if (empty($contexto["autenticado"])) {
      return $this->respuesta(true, "warning", "Sesion Distribucion requerida", array("requiere_autenticacion" => true, "requiere_permiso" => $permisosRequeridos));
    }
    $permisos = $this->valor($contexto, "permisos", array());
    $permisos = is_array($permisos) ? $permisos : array();
    foreach ($permisosRequeridos as $permiso) {
      if (in_array($permiso, $permisos, true)) {
        return null;
      }
    }
    return $this->respuesta(true, "warning", $mensaje, array("requiere_permiso" => $permisosRequeridos, "sesion" => $this->sesionSalida($contexto)));
  }

  private function skuVisibleCanal($db, $idSku) {
    if (!$this->tablaExiste($db, "erp_catalogo_canales_vinculos")) { return false; }
    $stmt = $db->prepare("SELECT id_canal_vinculo
      FROM erp_catalogo_canales_vinculos
      WHERE canal='distribucion' AND estatus='activo' AND sincronizar_catalogo=1 AND id_sku=:sku
      LIMIT 1");
    $stmt->execute(array(":sku" => $idSku));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function inventarioActual($db, $idCliente, $idSku) {
    $stmt = $db->prepare("SELECT existencia_cliente, minimo, maximo
      FROM erp_distribucion_cliente_inventario
      WHERE id_cliente_distribucion=:cliente AND id_sku=:sku
      LIMIT 1");
    $stmt->execute(array(":cliente" => $idCliente, ":sku" => $idSku));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: array("existencia_cliente" => null, "minimo" => null, "maximo" => null);
  }

  private function registrarMovimientoInventario($db, $idCliente, $idSku, $tipo, $anterior, $nuevo, $detalle) {
    if (!$this->tablaExiste($db, "erp_distribucion_cliente_inventario_movimientos")) { return; }
    $stmt = $db->prepare("INSERT INTO erp_distribucion_cliente_inventario_movimientos
      (id_cliente_distribucion, id_sku, tipo_movimiento, cantidad_anterior, cantidad_nueva, minimo_anterior, minimo_nuevo,
       maximo_anterior, maximo_nuevo, origen, detalle_json, fecha_registro)
      VALUES (:cliente, :sku, :tipo, :cantidad_anterior, :cantidad_nueva, :minimo_anterior, :minimo_nuevo,
       :maximo_anterior, :maximo_nuevo, 'cliente', :detalle, NOW())");
    $stmt->execute(array(
      ":cliente" => $idCliente,
      ":sku" => $idSku,
      ":tipo" => $tipo,
      ":cantidad_anterior" => $this->decimalONull($this->valor($anterior, "existencia_cliente", null)),
      ":cantidad_nueva" => $this->decimalONull($this->valor($nuevo, "existencia_cliente", null)),
      ":minimo_anterior" => $this->decimalONull($this->valor($anterior, "minimo", null)),
      ":minimo_nuevo" => $this->decimalONull($this->valor($nuevo, "minimo", null)),
      ":maximo_anterior" => $this->decimalONull($this->valor($anterior, "maximo", null)),
      ":maximo_nuevo" => $this->decimalONull($this->valor($nuevo, "maximo", null)),
      ":detalle" => json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ));
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
      ":detalle" => json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      ":usuario" => $idUsuario,
      ":cliente" => $idCliente
    ));
  }

  private function sesionSalida($contexto) {
    return array(
      "autenticado" => !empty($contexto["autenticado"]),
      "tipo_cliente" => $this->valor($contexto, "tipo_cliente", "publico"),
      "estatus" => $this->valor($contexto, "estatus", null),
      "permisos" => $this->valor($contexto, "permisos", array()),
      "acciones" => $this->valor($contexto, "acciones", array())
    );
  }

  private function calcularSugerido($existencia, $minimo, $maximo) {
    $existencia = floatval($existencia);
    $minimo = floatval($minimo);
    $maximo = floatval($maximo);
    if ($minimo <= 0 || $maximo <= $existencia || $existencia > $minimo) {
      return 0;
    }
    return round(max(0, $maximo - $existencia), 6);
  }

  private function entradaAuditable($datos) {
    $permitidos = array("id_sku", "existencia_cliente", "existencia", "minimo", "maximo", "unidad_cliente", "notas");
    $salida = array();
    foreach ($permitidos as $clave) {
      if (is_array($datos) && array_key_exists($clave, $datos)) {
        $salida[$clave] = $datos[$clave];
      }
    }
    return $salida;
  }

  private function decimalSeguro($valor) {
    return max(0, min(999999, round(floatval($valor), 6)));
  }

  private function decimalONull($valor) {
    return $valor === null || $valor === "" ? null : floatval($valor);
  }

  private function textoNullable($valor, $max) {
    $texto = trim((string) $valor);
    if ($texto === "") { return null; }
    return substr($texto, 0, $max);
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
