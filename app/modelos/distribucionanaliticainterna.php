<?php

class DistribucionAnaliticaInterna extends CRUD {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: concentrar lectura operativa interna para Dashboard y Demanda de Distribucion.
   * Impacto: ERP Distribucion; permite anticipar solicitudes, interes, inventario cliente y publicaciones sin tocar esquema ni inventario ERP.
   * Contrato: read-only; devuelve contadores y rankings seguros para el panel interno.
   */
  public function resumenInterno($filtros = array()) {
    $db = $this->getConexion();
    if (!$db) {
      return $this->respuesta(false, "warning", "Resumen Distribucion sin conexion disponible", array("configurado" => false, "metricas" => array(), "alertas" => array()));
    }

    $metricas = array(
      "solicitudes_pendientes" => $this->contar($db, "erp_distribucion_solicitudes", "estatus='pendiente'"),
      "clientes_aprobados_activos" => $this->contar($db, "erp_distribucion_clientes", "estatus='aprobado'"),
      "clientes_sin_lista" => $this->contar($db, "erp_distribucion_clientes", "estatus='aprobado' AND (id_lista_precio IS NULL OR id_lista_precio=0)"),
      "clientes_sin_permisos_completos" => $this->clientesSinPermisosCompletos($db),
      "productos_publicados" => $this->contar($db, "erp_catalogo_canales_vinculos", "canal='distribucion' AND estatus='activo' AND sincronizar_catalogo=1"),
      "productos_candidatos" => $this->productosCandidatos($db),
      "productos_mi_catalogo" => $this->contar($db, "erp_distribucion_cliente_productos", "estatus='activo'"),
      "sugeridos_pendientes" => $this->contar($db, "erp_distribucion_cliente_inventario", "estatus='activo' AND minimo>0 AND maximo>existencia_cliente AND existencia_cliente<=minimo"),
      "pedidos_pendientes" => $this->contar($db, "erp_distribucion_cotizaciones", "estatus IN ('pedido_solicitado','recibida','recibida_revision','en_revision')"),
      "productos_demandados_sin_precio" => $this->productosDemandadosSinPrecio($db)
    );

    return $this->respuesta(false, "success", "Resumen Distribucion consultado", array(
      "configurado" => true,
      "metricas" => $metricas,
      "recientes_mi_catalogo" => $this->topMiCatalogo($db, "recientes", 8),
      "top_mi_catalogo" => $this->topMiCatalogo($db, "productos", 8),
      "top_pedidos" => $this->topPedidos($db, 8),
      "alertas" => $this->alertasOperativas($metricas)
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: agrupar demanda comercial por producto, cliente, marca, categoria y proveedor.
   * Impacto: ERP Distribucion; prepara lectura comercial para compras/publicacion sin crear pedidos ERP.
   * Contrato: read-only; combina Mi catalogo, cotizaciones/pedidos preliminares e inventario cliente.
   */
  public function demandaInterna($filtros = array()) {
    $db = $this->getConexion();
    if (!$db) {
      return $this->respuesta(false, "warning", "Demanda Distribucion sin conexion disponible", array("configurado" => false));
    }
    return $this->respuesta(false, "success", "Demanda Distribucion consultada", array(
      "configurado" => true,
      "productos_mi_catalogo" => $this->topMiCatalogo($db, "productos", 20),
      "productos_pedidos" => $this->topPedidos($db, 20),
      "clientes_activos" => $this->clientesActivos($db, 20),
      "marcas" => $this->topDimension($db, "marca", 20),
      "categorias" => $this->topDimension($db, "categoria", 20),
      "proveedores" => $this->topDimension($db, "proveedor", 20),
      "sin_precio" => $this->demandadosSinPrecioDetalle($db, 20),
      "resurtido" => $this->resurtidoPorProveedor($db, 20)
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: entregar catalogos de filtro internos para la consola Distribucion.
   * Impacto: UI Distribucion; evita hardcodear marcas, categorias o proveedores.
   * Contrato: read-only; usa tablas ERP cuando existen.
   */
  public function catalogosInternos() {
    $db = $this->getConexion();
    if (!$db) {
      return $this->respuesta(false, "warning", "Catalogos Distribucion no disponibles", array("configurado" => false));
    }
    return $this->respuesta(false, "success", "Catalogos Distribucion consultados", array(
      "configurado" => true,
      "marcas" => $this->listarCatalogo($db, "erp_catalogo_marcas", "id_marca_erp", "nombre", "estatus='activa'"),
      "categorias" => $this->listarCatalogo($db, "erp_catalogo_categorias", "id_categoria_erp", "COALESCE(ruta,nombre)", "estatus='activa' AND permite_productos=1"),
      "proveedores" => $this->listarCatalogo($db, "erp_proveedores", "id_proveedor", "proveedor", "1=1")
    ));
  }

  private function topMiCatalogo($db, $modo, $limite) {
    if (!$this->tablasExisten($db, array("erp_distribucion_cliente_productos", "erp_distribucion_clientes", "erp_catalogo_skus", "erp_catalogo_productos"))) { return array(); }
    $limite = max(1, min(50, intval($limite)));
    if ($modo === "recientes") {
      $stmt = $db->prepare("SELECT cp.id_cliente_producto, c.nombre cliente, c.empresa, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) producto,
          cp.alias_cliente, cp.ubicacion_cliente, cp.prioridad, cp.fecha_actualizacion
        FROM erp_distribucion_cliente_productos cp
        INNER JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=cp.id_cliente_distribucion
        LEFT JOIN erp_catalogo_skus s ON s.id_sku=cp.id_sku
        LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        WHERE cp.estatus='activo'
        ORDER BY cp.fecha_actualizacion DESC, cp.id_cliente_producto DESC
        LIMIT " . intval($limite));
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $stmt = $db->prepare("SELECT cp.id_sku, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) producto, COUNT(DISTINCT cp.id_cliente_distribucion) clientes, COUNT(*) total
      FROM erp_distribucion_cliente_productos cp
      LEFT JOIN erp_catalogo_skus s ON s.id_sku=cp.id_sku
      LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE cp.estatus='activo'
      GROUP BY cp.id_sku, s.sku, producto
      ORDER BY clientes DESC, total DESC, producto ASC
      LIMIT " . intval($limite));
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function topPedidos($db, $limite) {
    if (!$this->tablasExisten($db, array("erp_distribucion_cotizacion_items", "erp_distribucion_cotizaciones"))) { return array(); }
    $limite = max(1, min(50, intval($limite)));
    $stmt = $db->prepare("SELECT i.id_sku, i.sku_snapshot sku, i.nombre_snapshot producto, COUNT(DISTINCT c.id_cotizacion_distribucion) solicitudes,
        SUM(i.cantidad) cantidad_solicitada
      FROM erp_distribucion_cotizacion_items i
      INNER JOIN erp_distribucion_cotizaciones c ON c.id_cotizacion_distribucion=i.id_cotizacion_distribucion
      GROUP BY i.id_sku, i.sku_snapshot, i.nombre_snapshot
      ORDER BY cantidad_solicitada DESC, solicitudes DESC
      LIMIT " . intval($limite));
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function clientesActivos($db, $limite) {
    if (!$this->tablaExiste($db, "erp_distribucion_clientes")) { return array(); }
    $limite = max(1, min(50, intval($limite)));
    $miCatalogo = $this->tablaExiste($db, "erp_distribucion_cliente_productos") ? "(SELECT COUNT(*) FROM erp_distribucion_cliente_productos cp WHERE cp.id_cliente_distribucion=c.id_cliente_distribucion AND cp.estatus='activo')" : "0";
    $pedidos = $this->tablaExiste($db, "erp_distribucion_cotizaciones") ? "(SELECT COUNT(*) FROM erp_distribucion_cotizaciones co WHERE co.id_cliente_distribucion=c.id_cliente_distribucion)" : "0";
    $stmt = $db->prepare("SELECT c.id_cliente_distribucion, c.nombre cliente, c.empresa, c.correo, c.fecha_ultimo_login,
        " . $miCatalogo . " productos_mi_catalogo, " . $pedidos . " solicitudes
      FROM erp_distribucion_clientes c
      WHERE c.estatus='aprobado'
      ORDER BY productos_mi_catalogo DESC, solicitudes DESC, c.fecha_ultimo_login DESC
      LIMIT " . intval($limite));
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function topDimension($db, $dimension, $limite) {
    if (!$this->tablasExisten($db, array("erp_distribucion_cliente_productos", "erp_catalogo_skus", "erp_catalogo_productos"))) { return array(); }
    $limite = max(1, min(50, intval($limite)));
    $joins = "";
    $campo = "''";
    $where = "cp.estatus='activo'";
    if ($dimension === "marca" && $this->tablaExiste($db, "erp_catalogo_marcas")) {
      $joins = "LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp";
      $campo = "COALESCE(m.nombre,'Sin marca')";
    } elseif ($dimension === "categoria" && $this->tablasExisten($db, array("erp_catalogo_producto_categorias", "erp_catalogo_categorias"))) {
      $joins = "LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
        LEFT JOIN erp_catalogo_categorias cat ON cat.id_categoria_erp=pc.id_categoria_erp";
      $campo = "COALESCE(cat.ruta, cat.nombre, 'Sin categoria')";
    } elseif ($dimension === "proveedor" && $this->tablasExisten($db, array("erp_catalogo_sku_proveedores", "erp_proveedores"))) {
      $joins = "LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1
        LEFT JOIN erp_proveedores pr ON pr.id_proveedor=sp.id_proveedor";
      $campo = "COALESCE(pr.proveedor,'Sin proveedor preferido')";
    } else {
      return array();
    }
    $stmt = $db->prepare("SELECT " . $campo . " nombre, COUNT(*) total, COUNT(DISTINCT cp.id_cliente_distribucion) clientes
      FROM erp_distribucion_cliente_productos cp
      INNER JOIN erp_catalogo_skus s ON s.id_sku=cp.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      " . $joins . "
      WHERE " . $where . "
      GROUP BY nombre
      ORDER BY total DESC, clientes DESC, nombre ASC
      LIMIT " . intval($limite));
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function demandadosSinPrecioDetalle($db, $limite) {
    if (!$this->tablasExisten($db, array("erp_distribucion_cliente_productos", "erp_catalogo_skus", "erp_catalogo_productos"))) { return array(); }
    if (!$this->tablasExisten($db, array("erp_listas_precios", "erp_listas_precios_detalle"))) { return array(); }
    $stmt = $db->prepare("SELECT cp.id_sku, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) producto, COUNT(*) demanda
      FROM erp_distribucion_cliente_productos cp
      INNER JOIN erp_catalogo_skus s ON s.id_sku=cp.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE cp.estatus='activo'
        AND NOT EXISTS (
          SELECT 1 FROM erp_listas_precios_detalle d
          INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio
          WHERE d.id_sku=cp.id_sku AND d.estatus='activo' AND d.precio>0 AND l.estatus='activa'
        )
      GROUP BY cp.id_sku, s.sku, producto
      ORDER BY demanda DESC
      LIMIT " . intval(max(1, min(50, $limite))));
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function resurtidoPorProveedor($db, $limite) {
    if (!$this->tablasExisten($db, array("erp_distribucion_cliente_inventario", "erp_catalogo_skus", "erp_catalogo_productos"))) { return array(); }
    $joinProveedor = $this->tablasExisten($db, array("erp_catalogo_sku_proveedores", "erp_proveedores"))
      ? "LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1 LEFT JOIN erp_proveedores pr ON pr.id_proveedor=sp.id_proveedor"
      : "";
    $campoProveedor = $joinProveedor !== "" ? "COALESCE(pr.proveedor,'Sin proveedor preferido')" : "'Sin proveedor'";
    $stmt = $db->prepare("SELECT " . $campoProveedor . " proveedor, COUNT(*) productos, SUM(ci.maximo-ci.existencia_cliente) cantidad_sugerida
      FROM erp_distribucion_cliente_inventario ci
      INNER JOIN erp_catalogo_skus s ON s.id_sku=ci.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      " . $joinProveedor . "
      WHERE ci.estatus='activo' AND ci.minimo>0 AND ci.maximo>ci.existencia_cliente AND ci.existencia_cliente<=ci.minimo
      GROUP BY proveedor
      ORDER BY cantidad_sugerida DESC
      LIMIT " . intval(max(1, min(50, $limite))));
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function productosCandidatos($db) {
    if (!$this->tablasExisten($db, array("erp_catalogo_skus", "erp_catalogo_productos"))) { return 0; }
    $precio = $this->tablasExisten($db, array("erp_listas_precios", "erp_listas_precios_detalle")) ? " AND EXISTS (SELECT 1 FROM erp_listas_precios_detalle d INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio WHERE d.id_sku=s.id_sku AND d.estatus='activo' AND d.precio>0 AND l.estatus='activa')" : "";
    $canal = $this->tablaExiste($db, "erp_catalogo_canales_vinculos") ? " AND NOT EXISTS (SELECT 1 FROM erp_catalogo_canales_vinculos cv WHERE cv.id_sku=s.id_sku AND cv.canal='distribucion' AND cv.estatus='activo' AND cv.sincronizar_catalogo=1)" : "";
    return $this->contarSql($db, "SELECT COUNT(*) FROM erp_catalogo_skus s INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp WHERE s.estatus='activo' AND p.estatus='activo'" . $precio . $canal);
  }

  private function productosDemandadosSinPrecio($db) {
    return count($this->demandadosSinPrecioDetalle($db, 500));
  }

  private function clientesSinPermisosCompletos($db) {
    if (!$this->tablasExisten($db, array("erp_distribucion_clientes", "erp_distribucion_cliente_permisos"))) { return 0; }
    return $this->contarSql($db, "SELECT COUNT(*) FROM erp_distribucion_clientes c
      WHERE c.estatus='aprobado' AND NOT EXISTS (
        SELECT 1 FROM erp_distribucion_cliente_permisos cp
        WHERE cp.id_cliente_distribucion=c.id_cliente_distribucion AND cp.estatus='activo'
      )");
  }

  private function alertasOperativas($metricas) {
    $alertas = array();
    foreach ($metricas as $clave => $valor) {
      if (intval($valor) > 0 && in_array($clave, array("solicitudes_pendientes", "clientes_sin_lista", "clientes_sin_permisos_completos", "productos_candidatos", "sugeridos_pendientes", "pedidos_pendientes", "productos_demandados_sin_precio"), true)) {
        $alertas[] = array("clave" => $clave, "total" => intval($valor));
      }
    }
    return $alertas;
  }

  private function listarCatalogo($db, $tabla, $id, $nombre, $where) {
    if (!$this->tablaExiste($db, $tabla)) { return array(); }
    try {
      $stmt = $db->prepare("SELECT " . $id . " id, " . $nombre . " nombre FROM " . $tabla . " WHERE " . $where . " ORDER BY nombre ASC LIMIT 500");
      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return array();
    }
  }

  private function contar($db, $tabla, $where) {
    if (!$this->tablaExiste($db, $tabla)) { return 0; }
    return $this->contarSql($db, "SELECT COUNT(*) FROM " . $tabla . " WHERE " . $where);
  }

  private function contarSql($db, $sql) {
    try {
      return intval($db->query($sql)->fetchColumn());
    } catch (Exception $e) {
      return 0;
    }
  }

  private function tablasExisten($db, $tablas) {
    foreach ($tablas as $tabla) {
      if (!$this->tablaExiste($db, $tabla)) { return false; }
    }
    return true;
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
}
