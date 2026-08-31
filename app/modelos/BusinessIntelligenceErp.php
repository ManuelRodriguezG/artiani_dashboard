<?php

class BusinessIntelligenceErp extends CRUD {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-31
   * Proposito: diagnosticar disponibilidad de fuentes legacy para BI comercial.
   * Impacto: Business Intelligence; no modifica tablas ni crea esquema nuevo.
   * Contrato: devuelve tablas, columnas y rangos detectados en la conexion activa.
   */
  public function diagnostico() {
    $db = $this->getConexion();
    $tablas = $this->tablasDisponibles($db);
    $columnas = array();
    foreach (array_keys($tablas) as $tabla) {
      $columnas[$tabla] = $tablas[$tabla] ? $this->columnasTabla($db, $tabla) : array();
    }

    return $this->respuesta(false, "success", "Diagnostico BI consultado", array(
      "read_only" => true,
      "base" => defined("MYSQLBASE") ? MYSQLBASE : "",
      "tablas" => $tablas,
      "columnas" => $columnas,
      "rangos" => $this->rangosDisponibles($db, $tablas, $columnas),
      "contrato" => $this->contrato()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-31
   * Proposito: generar dashboard inicial de demanda historica para publicidad por temporada.
   * Impacto: Business Intelligence; cruza `bi_*` con catalogos legacy disponibles sin escribir BD.
   * Contrato: filtros GET `desde`, `hasta`, `limite`; salida agregada para UI.
   */
  public function publicidadTemporadasDashboard($filtros = array()) {
    $db = $this->getConexion();
    $desde = $this->fechaFiltro($this->valor($filtros, "desde", date("Y-m-d", strtotime("-365 days"))), date("Y-m-d", strtotime("-365 days")));
    $hasta = $this->fechaFiltro($this->valor($filtros, "hasta", date("Y-m-d")), date("Y-m-d"));
    if (strtotime($hasta) < strtotime($desde)) {
      $temporal = $desde;
      $desde = $hasta;
      $hasta = $temporal;
    }
    $limite = max(5, min(100, intval($this->valor($filtros, "limite", 20))));
    $inicio = $desde . " 00:00:00";
    $fin = $hasta . " 23:59:59";

    $depurar = array(
      "read_only" => true,
      "rango" => array("desde" => $desde, "hasta" => $hasta, "limite" => $limite),
      "tablas" => array(),
      "columnas" => array(),
      "resumen" => $this->resumenVacio(),
      "visitas_por_mes_tipo" => array(),
      "acciones_consumibles" => array(),
      "tendencia_mensual" => array(),
      "productos_top" => array(),
      "productos_por_mes" => array(),
      "categorias_top" => array(),
      "clasificaciones_top" => array(),
      "marcas_top" => array(),
      "busquedas_top" => array(),
      "busquedas_por_mes" => array(),
      "busquedas_por_mes_total" => array(),
      "busquedas_sin_resultado" => array(),
      "calendario_comercial" => array(),
      "recomendaciones_publicidad" => array(),
      "avisos" => array(),
      "contrato" => $this->contrato()
    );

    if (!$db) {
      return $this->respuesta(true, "warning", "Conexion MySQL no disponible", $depurar);
    }

    try {
      $tablas = $this->tablasDisponibles($db);
      $columnas = array();
      foreach (array_keys($tablas) as $tabla) {
        $columnas[$tabla] = $tablas[$tabla] ? $this->columnasTabla($db, $tabla) : array();
      }
      $depurar["tablas"] = $tablas;
      $depurar["columnas"] = $columnas;
      $depurar["resumen"]["fuentes_disponibles"] = count(array_filter($tablas));

      if (!$tablas["bi_seguimiento_consumibles"]) {
        $depurar["avisos"][] = "Tabla bi_seguimiento_consumibles no disponible en la conexion activa.";
      } else {
        $this->cargarSeguimientoConsumibles($db, $depurar, $tablas, $columnas, $inicio, $fin, $limite);
      }

      if (!$tablas["bi_busquedas"]) {
        $depurar["avisos"][] = "Tabla bi_busquedas no disponible en la conexion activa.";
      } else {
        $this->cargarBusquedas($db, $depurar, $columnas, $inicio, $fin, $limite);
      }

      $depurar["calendario_comercial"] = $this->calendarioComercial($depurar, $limite);
      $depurar["tendencia_mensual"] = $this->tendenciaMensual($depurar);
      $depurar["recomendaciones_publicidad"] = $this->recomendacionesPublicidad($depurar);
      return $this->respuesta(false, "success", "Dashboard BI publicidad consultado", $depurar);
    } catch (Exception $e) {
      $depurar["avisos"][] = $e->getMessage();
      return $this->respuesta(true, "danger", $e->getMessage(), $depurar);
    }
  }

  private function cargarSeguimientoConsumibles($db, &$depurar, $tablas, $columnas, $inicio, $fin, $limite) {
    $cols = $columnas["bi_seguimiento_consumibles"];
    foreach (array("tipo", "identificador", "fch_r") as $columna) {
      if (!in_array($columna, $cols, true)) {
        $depurar["avisos"][] = "bi_seguimiento_consumibles no tiene columna requerida: " . $columna;
        return;
      }
    }

    $stmt = $db->prepare("SELECT COUNT(*) total,
        SUM(CASE WHEN tipo='producto' THEN 1 ELSE 0 END) productos,
        SUM(CASE WHEN tipo='categoria' THEN 1 ELSE 0 END) categorias,
        SUM(CASE WHEN tipo='clasificacion' THEN 1 ELSE 0 END) clasificaciones,
        SUM(CASE WHEN tipo='marca' THEN 1 ELSE 0 END) marcas
      FROM bi_seguimiento_consumibles
      WHERE fch_r BETWEEN :inicio AND :fin");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $depurar["resumen"]["eventos_consumibles"] = intval($this->valor($fila, "total", 0));
    $depurar["resumen"]["visitas_productos"] = intval($this->valor($fila, "productos", 0));
    $depurar["resumen"]["visitas_categorias"] = intval($this->valor($fila, "categorias", 0));
    $depurar["resumen"]["visitas_clasificaciones"] = intval($this->valor($fila, "clasificaciones", 0));
    $depurar["resumen"]["visitas_marcas"] = intval($this->valor($fila, "marcas", 0));

    if (in_array("accion", $cols, true)) {
      $depurar["acciones_consumibles"] = $this->consulta($db, "SELECT tipo, accion, COUNT(*) total
        FROM bi_seguimiento_consumibles
        WHERE fch_r BETWEEN :inicio AND :fin
        GROUP BY tipo, accion
        ORDER BY total DESC, tipo ASC, accion ASC
        LIMIT " . intval(max(20, $limite)), array(":inicio" => $inicio, ":fin" => $fin));
    }

    $depurar["visitas_por_mes_tipo"] = $this->consulta($db, "SELECT DATE_FORMAT(fch_r, '%Y-%m') mes, tipo, COUNT(*) total
      FROM bi_seguimiento_consumibles
      WHERE fch_r BETWEEN :inicio AND :fin
      GROUP BY DATE_FORMAT(fch_r, '%Y-%m'), tipo
      ORDER BY mes ASC, total DESC", array(":inicio" => $inicio, ":fin" => $fin));

    if ($tablas["ecom_productos"]) {
      $depurar["productos_top"] = $this->consulta($db, "SELECT ecomp.id_producto, ecomp.sku, ecomp.nombre, COUNT(*) total, MAX(bisc.fch_r) ultima_fecha
        FROM bi_seguimiento_consumibles bisc
        INNER JOIN ecom_productos ecomp ON ecomp.id_producto = CAST(bisc.identificador AS UNSIGNED)
        WHERE bisc.tipo='producto' AND bisc.fch_r BETWEEN :inicio AND :fin
        GROUP BY ecomp.id_producto, ecomp.sku, ecomp.nombre
        ORDER BY total DESC, ecomp.nombre ASC
        LIMIT " . intval($limite), array(":inicio" => $inicio, ":fin" => $fin));

      $depurar["productos_por_mes"] = $this->consulta($db, "SELECT DATE_FORMAT(bisc.fch_r, '%Y-%m') mes, ecomp.id_producto, ecomp.sku, ecomp.nombre, COUNT(*) total
        FROM bi_seguimiento_consumibles bisc
        INNER JOIN ecom_productos ecomp ON ecomp.id_producto = CAST(bisc.identificador AS UNSIGNED)
        WHERE bisc.tipo='producto' AND bisc.fch_r BETWEEN :inicio AND :fin
        GROUP BY DATE_FORMAT(bisc.fch_r, '%Y-%m'), ecomp.id_producto, ecomp.sku, ecomp.nombre
        ORDER BY mes ASC, total DESC, ecomp.nombre ASC
        LIMIT " . intval($limite * 12), array(":inicio" => $inicio, ":fin" => $fin));
    }

    if ($tablas["ecom_categorias"]) {
      $depurar["categorias_top"] = $this->topLegacy($db, "categoria", "ecom_categorias", "id_categoria", "categoria", $inicio, $fin, $limite);
    }
    if ($tablas["ecom_clasificaciones"]) {
      $depurar["clasificaciones_top"] = $this->topLegacy($db, "clasificacion", "ecom_clasificaciones", "id_clasificacion", "clasificacion", $inicio, $fin, $limite);
    }
    if ($tablas["ecom_marcas"]) {
      $depurar["marcas_top"] = $this->topLegacy($db, "marca", "ecom_marcas", "id_marca", "marca", $inicio, $fin, $limite);
    }
  }

  private function cargarBusquedas($db, &$depurar, $columnas, $inicio, $fin, $limite) {
    $cols = $columnas["bi_busquedas"];
    $fecha = $this->primeraColumna($cols, array("fch_r", "fecha_registro", "created_at", "fecha", "fch"));
    $query = $this->primeraColumna($cols, array("busqueda", "query", "query_normalizada", "termino", "palabra", "texto", "q"));
    if ($fecha === "" || $query === "") {
      $depurar["avisos"][] = "bi_busquedas existe, pero falta confirmar columna de fecha o texto de busqueda.";
      return;
    }

    $stmt = $db->prepare("SELECT COUNT(*) total FROM bi_busquedas WHERE `" . $fecha . "` BETWEEN :inicio AND :fin");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $depurar["resumen"]["busquedas_total"] = intval($stmt->fetchColumn());

    $depurar["busquedas_top"] = $this->consulta($db, "SELECT LOWER(TRIM(`" . $query . "`)) termino, COUNT(*) total, MAX(`" . $fecha . "`) ultima_fecha
      FROM bi_busquedas
      WHERE `" . $fecha . "` BETWEEN :inicio AND :fin AND TRIM(COALESCE(`" . $query . "`, '')) <> ''
      GROUP BY LOWER(TRIM(`" . $query . "`))
      ORDER BY total DESC, termino ASC
      LIMIT " . intval($limite), array(":inicio" => $inicio, ":fin" => $fin));

    $depurar["busquedas_por_mes"] = $this->consulta($db, "SELECT DATE_FORMAT(`" . $fecha . "`, '%Y-%m') mes, LOWER(TRIM(`" . $query . "`)) termino, COUNT(*) total
      FROM bi_busquedas
      WHERE `" . $fecha . "` BETWEEN :inicio AND :fin AND TRIM(COALESCE(`" . $query . "`, '')) <> ''
      GROUP BY DATE_FORMAT(`" . $fecha . "`, '%Y-%m'), LOWER(TRIM(`" . $query . "`))
      ORDER BY mes ASC, total DESC, termino ASC
      LIMIT " . intval($limite * 12), array(":inicio" => $inicio, ":fin" => $fin));

    $depurar["busquedas_por_mes_total"] = $this->consulta($db, "SELECT DATE_FORMAT(`" . $fecha . "`, '%Y-%m') mes, COUNT(*) total
      FROM bi_busquedas
      WHERE `" . $fecha . "` BETWEEN :inicio AND :fin
      GROUP BY DATE_FORMAT(`" . $fecha . "`, '%Y-%m')
      ORDER BY mes ASC", array(":inicio" => $inicio, ":fin" => $fin));

    $sinResultado = $this->condicionSinResultado($cols);
    if ($sinResultado !== "") {
      $depurar["busquedas_sin_resultado"] = $this->consulta($db, "SELECT LOWER(TRIM(`" . $query . "`)) termino, COUNT(*) total, MAX(`" . $fecha . "`) ultima_fecha
        FROM bi_busquedas
        WHERE `" . $fecha . "` BETWEEN :inicio AND :fin AND " . $sinResultado . " AND TRIM(COALESCE(`" . $query . "`, '')) <> ''
        GROUP BY LOWER(TRIM(`" . $query . "`))
        ORDER BY total DESC, termino ASC
        LIMIT " . intval($limite), array(":inicio" => $inicio, ":fin" => $fin));
      $depurar["resumen"]["busquedas_sin_resultado"] = array_sum(array_map(function ($fila) {
        return intval($this->valor($fila, "total", 0));
      }, $depurar["busquedas_sin_resultado"]));
    }
  }

  private function calendarioComercial($depurar, $limite) {
    $porMes = array();
    foreach ($depurar["productos_por_mes"] as $fila) {
      $mes = $this->valor($fila, "mes", "");
      if ($mes === "") { continue; }
      if (!isset($porMes[$mes])) { $porMes[$mes] = array("mes" => $mes, "productos" => array(), "busquedas" => array(), "total_interes" => 0); }
      if (count($porMes[$mes]["productos"]) < 5) {
        $porMes[$mes]["productos"][] = array(
          "id_producto" => intval($this->valor($fila, "id_producto", 0)),
          "sku" => $this->valor($fila, "sku", ""),
          "nombre" => $this->valor($fila, "nombre", ""),
          "total" => intval($this->valor($fila, "total", 0))
        );
      }
      $porMes[$mes]["total_interes"] += intval($this->valor($fila, "total", 0));
    }
    foreach ($depurar["busquedas_por_mes"] as $fila) {
      $mes = $this->valor($fila, "mes", "");
      if ($mes === "") { continue; }
      if (!isset($porMes[$mes])) { $porMes[$mes] = array("mes" => $mes, "productos" => array(), "busquedas" => array(), "total_interes" => 0); }
      if (count($porMes[$mes]["busquedas"]) < 5) {
        $porMes[$mes]["busquedas"][] = array(
          "termino" => $this->valor($fila, "termino", ""),
          "total" => intval($this->valor($fila, "total", 0))
        );
      }
      $porMes[$mes]["total_interes"] += intval($this->valor($fila, "total", 0));
    }
    usort($porMes, function ($a, $b) {
      if ($a["mes"] === $b["mes"]) { return 0; }
      return $a["mes"] < $b["mes"] ? -1 : 1;
    });
    return array_slice(array_values($porMes), 0, max(12, intval($limite)));
  }

  private function tendenciaMensual($depurar) {
    $meses = array();
    foreach ($depurar["visitas_por_mes_tipo"] as $fila) {
      $mes = $this->valor($fila, "mes", "");
      if ($mes === "") { continue; }
      if (!isset($meses[$mes])) {
        $meses[$mes] = array("mes" => $mes, "productos" => 0, "categorias" => 0, "clasificaciones" => 0, "marcas" => 0, "busquedas" => 0, "total" => 0);
      }
      $tipo = $this->valor($fila, "tipo", "");
      $total = intval($this->valor($fila, "total", 0));
      if ($tipo === "producto") { $meses[$mes]["productos"] += $total; }
      if ($tipo === "categoria") { $meses[$mes]["categorias"] += $total; }
      if ($tipo === "clasificacion") { $meses[$mes]["clasificaciones"] += $total; }
      if ($tipo === "marca") { $meses[$mes]["marcas"] += $total; }
      $meses[$mes]["total"] += $total;
    }
    foreach ($depurar["busquedas_por_mes_total"] as $fila) {
      $mes = $this->valor($fila, "mes", "");
      if ($mes === "") { continue; }
      if (!isset($meses[$mes])) {
        $meses[$mes] = array("mes" => $mes, "productos" => 0, "categorias" => 0, "clasificaciones" => 0, "marcas" => 0, "busquedas" => 0, "total" => 0);
      }
      $total = intval($this->valor($fila, "total", 0));
      $meses[$mes]["busquedas"] += $total;
      $meses[$mes]["total"] += $total;
    }
    usort($meses, function ($a, $b) {
      if ($a["mes"] === $b["mes"]) { return 0; }
      return $a["mes"] < $b["mes"] ? -1 : 1;
    });
    return array_values($meses);
  }

  private function recomendacionesPublicidad($depurar) {
    $salida = array();
    foreach (array_slice($depurar["busquedas_top"], 0, 5) as $fila) {
      $termino = trim((string) $this->valor($fila, "termino", ""));
      if ($termino === "") { continue; }
      $salida[] = array(
        "tipo" => "busqueda",
        "prioridad" => "alta",
        "titulo" => "Crear campana o landing para \"" . $termino . "\"",
        "detalle" => "Busqueda frecuente en el rango; validar productos disponibles, ficha, fotos y margen antes de invertir.",
        "total" => intval($this->valor($fila, "total", 0))
      );
    }
    foreach (array_slice($depurar["productos_top"], 0, 5) as $fila) {
      $nombre = trim((string) $this->valor($fila, "nombre", ""));
      if ($nombre === "") { continue; }
      $salida[] = array(
        "tipo" => "producto",
        "prioridad" => "media",
        "titulo" => "Promover producto con interes historico",
        "detalle" => $nombre . " concentra visitas; revisar stock, precio, utilidad y contenido antes de pauta.",
        "total" => intval($this->valor($fila, "total", 0))
      );
    }
    foreach (array_slice($depurar["categorias_top"], 0, 3) as $fila) {
      $nombre = trim((string) $this->valor($fila, "nombre", ""));
      if ($nombre === "") { continue; }
      $salida[] = array(
        "tipo" => "categoria",
        "prioridad" => "media",
        "titulo" => "Preparar coleccion por categoria",
        "detalle" => $nombre . " tiene demanda agrupada; conviene probar anuncio por familia si hay surtido suficiente.",
        "total" => intval($this->valor($fila, "total", 0))
      );
    }
    usort($salida, function ($a, $b) {
      if ($a["total"] === $b["total"]) { return strcmp($a["tipo"], $b["tipo"]); }
      return $a["total"] > $b["total"] ? -1 : 1;
    });
    return array_slice($salida, 0, 12);
  }

  private function topLegacy($db, $tipo, $tabla, $id, $nombre, $inicio, $fin, $limite) {
    return $this->consulta($db, "SELECT ref.`" . $id . "` id, ref.`" . $nombre . "` nombre, COUNT(*) total, MAX(bisc.fch_r) ultima_fecha
      FROM bi_seguimiento_consumibles bisc
      INNER JOIN `" . $tabla . "` ref ON ref.`" . $id . "` = CAST(bisc.identificador AS UNSIGNED)
      WHERE bisc.tipo=:tipo AND bisc.fch_r BETWEEN :inicio AND :fin
      GROUP BY ref.`" . $id . "`, ref.`" . $nombre . "`
      ORDER BY total DESC, nombre ASC
      LIMIT " . intval($limite), array(":tipo" => $tipo, ":inicio" => $inicio, ":fin" => $fin));
  }

  private function tablasDisponibles($db) {
    $tablas = array("bi_busquedas", "bi_seguimiento_consumibles", "ecom_productos", "ecom_categorias", "ecom_clasificaciones", "ecom_marcas");
    $salida = array();
    foreach ($tablas as $tabla) {
      $salida[$tabla] = $this->tablaExiste($db, $tabla);
    }
    return $salida;
  }

  private function rangosDisponibles($db, $tablas, $columnas) {
    $rangos = array();
    if ($tablas["bi_seguimiento_consumibles"] && in_array("fch_r", $columnas["bi_seguimiento_consumibles"], true)) {
      $rangos["bi_seguimiento_consumibles"] = $this->rangoTabla($db, "bi_seguimiento_consumibles", "fch_r");
    }
    if ($tablas["bi_busquedas"]) {
      $fecha = $this->primeraColumna($columnas["bi_busquedas"], array("fch_r", "fecha_registro", "created_at", "fecha", "fch"));
      if ($fecha !== "") { $rangos["bi_busquedas"] = $this->rangoTabla($db, "bi_busquedas", $fecha); }
    }
    return $rangos;
  }

  private function rangoTabla($db, $tabla, $fecha) {
    $stmt = $db->prepare("SELECT MIN(`" . $fecha . "`) desde, MAX(`" . $fecha . "`) hasta, COUNT(*) total FROM `" . $tabla . "`");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: array("desde" => null, "hasta" => null, "total" => 0);
  }

  private function tablaExiste($db, $tabla) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) { return false; }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
  }

  private function columnasTabla($db, $tabla) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) { return array(); }
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla ORDER BY ORDINAL_POSITION");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: array();
  }

  private function primeraColumna($columnas, $candidatas) {
    foreach ($candidatas as $columna) {
      if (in_array($columna, $columnas, true)) { return $columna; }
    }
    return "";
  }

  private function condicionSinResultado($columnas) {
    if (in_array("sin_resultado", $columnas, true)) { return "`sin_resultado`=1"; }
    if (in_array("sin_resultados", $columnas, true)) { return "`sin_resultados`=1"; }
    if (in_array("resultados_total", $columnas, true)) { return "COALESCE(`resultados_total`,0)=0"; }
    if (in_array("total_resultados", $columnas, true)) { return "COALESCE(`total_resultados`,0)=0"; }
    if (in_array("resultados", $columnas, true)) { return "COALESCE(`resultados`,0)=0"; }
    return "";
  }

  private function consulta($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
  }

  private function fechaFiltro($valor, $fallback) {
    $valor = trim((string) $valor);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : $fallback;
  }

  private function resumenVacio() {
    return array(
      "fuentes_disponibles" => 0,
      "eventos_consumibles" => 0,
      "visitas_productos" => 0,
      "visitas_categorias" => 0,
      "visitas_clasificaciones" => 0,
      "visitas_marcas" => 0,
      "busquedas_total" => 0,
      "busquedas_sin_resultado" => 0
    );
  }

  private function contrato() {
    return array(
      "modulo" => "business_intelligence",
      "solo_lectura" => true,
      "no_escribe_bd" => true,
      "no_crea_ddl" => true,
      "no_toca_ventas" => true,
      "no_toca_inventario" => true,
      "fuentes_legacy" => array("bi_busquedas", "bi_seguimiento_consumibles"),
      "catalogos_apoyo" => array("ecom_productos", "ecom_categorias", "ecom_clasificaciones", "ecom_marcas")
    );
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
