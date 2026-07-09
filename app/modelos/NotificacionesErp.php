<?php

class NotificacionesErp extends CRUD {

  private $tabla_notificaciones = "erp_notificaciones";
  private $tabla_lecturas = "erp_notificaciones_lecturas";

  public function resumenUsuario($idUsuario, $permisos) {
    $filtro = $this->filtroVisibilidadUsuario($idUsuario, $permisos);
    if ($filtro["sql"] === "") {
      return $this->respuesta(false, "success", "Sin permisos de notificaciones", array(
        "total_pendientes" => 0,
        "criticas" => 0,
        "altas" => 0,
        "por_area" => array(),
        "por_modulo" => array()
      ));
    }

    try {
      $db = $this->getConexion();
      $sql = "SELECT
          COUNT(*) total_pendientes,
          SUM(CASE WHEN n.prioridad='critica' THEN 1 ELSE 0 END) criticas,
          SUM(CASE WHEN n.prioridad='alta' THEN 1 ELSE 0 END) altas
        FROM {$this->tabla_notificaciones} n
        LEFT JOIN {$this->tabla_lecturas} l
          ON l.id_notificacion=n.id_notificacion AND l.id_usuario=:usuario_lectura
        WHERE n.estatus IN ('pendiente','en_revision','bloqueada')
          AND COALESCE(l.descartada,0)=0
          AND " . $filtro["sql"];
      $stmt = $db->prepare($sql);
      $stmt->execute(array_merge(array(":usuario_lectura" => intval($idUsuario)), $filtro["params"]));
      $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

      $sqlAreas = "SELECT n.area_responsable, COUNT(*) total
        FROM {$this->tabla_notificaciones} n
        LEFT JOIN {$this->tabla_lecturas} l
          ON l.id_notificacion=n.id_notificacion AND l.id_usuario=:usuario_lectura
        WHERE n.estatus IN ('pendiente','en_revision','bloqueada')
          AND COALESCE(l.descartada,0)=0
          AND " . $filtro["sql"] . "
        GROUP BY n.area_responsable
        ORDER BY total DESC, n.area_responsable ASC";
      $stmtAreas = $db->prepare($sqlAreas);
      $stmtAreas->execute(array_merge(array(":usuario_lectura" => intval($idUsuario)), $filtro["params"]));

      $sqlModulos = "SELECT n.modulo_origen, COUNT(*) total
        FROM {$this->tabla_notificaciones} n
        LEFT JOIN {$this->tabla_lecturas} l
          ON l.id_notificacion=n.id_notificacion AND l.id_usuario=:usuario_lectura
        WHERE n.estatus IN ('pendiente','en_revision','bloqueada')
          AND COALESCE(l.descartada,0)=0
          AND " . $filtro["sql"] . "
        GROUP BY n.modulo_origen
        ORDER BY total DESC, n.modulo_origen ASC";
      $stmtModulos = $db->prepare($sqlModulos);
      $stmtModulos->execute(array_merge(array(":usuario_lectura" => intval($idUsuario)), $filtro["params"]));

      return $this->respuesta(false, "success", "Resumen de notificaciones consultado", array(
        "total_pendientes" => intval(isset($resumen["total_pendientes"]) ? $resumen["total_pendientes"] : 0),
        "criticas" => intval(isset($resumen["criticas"]) ? $resumen["criticas"] : 0),
        "altas" => intval(isset($resumen["altas"]) ? $resumen["altas"] : 0),
        "por_area" => $stmtAreas->fetchAll(PDO::FETCH_ASSOC),
        "por_modulo" => $stmtModulos->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function listarUsuario($idUsuario, $permisos, $filtros = array()) {
    $filtro = $this->filtroVisibilidadUsuario($idUsuario, $permisos);
    if ($filtro["sql"] === "") {
      return $this->respuesta(false, "success", "Sin notificaciones visibles", array());
    }

    try {
      $db = $this->getConexion();
      $where = array(
        "COALESCE(l.descartada,0)=0",
        $filtro["sql"]
      );
      $params = array_merge(array(":usuario_lectura" => intval($idUsuario)), $filtro["params"]);

      $estatus = isset($filtros["estatus"]) ? trim((string) $filtros["estatus"]) : "";
      if ($estatus !== "") {
        $where[] = "n.estatus=:estatus";
        $params[":estatus"] = $estatus;
      } else {
        $where[] = "n.estatus IN ('pendiente','en_revision','bloqueada')";
      }

      $area = isset($filtros["area_responsable"]) ? trim((string) $filtros["area_responsable"]) : "";
      if ($area !== "") {
        $where[] = "n.area_responsable=:area";
        $params[":area"] = $area;
      }

      $limite = intval(isset($filtros["limite"]) ? $filtros["limite"] : 20);
      $limite = max(1, min(50, $limite));

      $sql = "SELECT n.*, COALESCE(l.leida,0) leida, COALESCE(l.descartada,0) descartada
        FROM {$this->tabla_notificaciones} n
        LEFT JOIN {$this->tabla_lecturas} l
          ON l.id_notificacion=n.id_notificacion AND l.id_usuario=:usuario_lectura
        WHERE " . implode(" AND ", $where) . "
        ORDER BY FIELD(n.prioridad,'critica','alta','normal','info'),
          n.fecha_vencimiento IS NULL ASC, n.fecha_vencimiento ASC,
          n.id_notificacion DESC
        LIMIT " . $limite;
      $stmt = $db->prepare($sql);
      $stmt->execute($params);

      return $this->respuesta(false, "success", "Notificaciones consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function marcarLeida($idUsuario, $idNotificacion) {
    $idNotificacion = intval($idNotificacion);
    if ($idNotificacion <= 0) {
      return $this->respuesta(true, "warning", "Falta notificacion");
    }
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("INSERT INTO {$this->tabla_lecturas}
          (id_notificacion,id_usuario,leida,fecha_lectura)
          VALUES (:notificacion,:usuario,1,NOW())
          ON DUPLICATE KEY UPDATE leida=1, fecha_lectura=NOW()");
      $stmt->execute(array(
        ":notificacion" => $idNotificacion,
        ":usuario" => intval($idUsuario)
      ));
      return $this->respuesta(false, "success", "Notificacion marcada como leida");
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function guardarOperativa($datos) {
    try {
      $id = $this->guardarOperativaEnConexion($this->getConexion(), $datos);
      return $this->respuesta(false, "success", "Notificacion operativa guardada", array("id_notificacion" => $id));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function guardarOperativaEnConexion($db, $datos) {
    $payload = isset($datos["payload_json"]) && is_array($datos["payload_json"]) ? $datos["payload_json"] : array();
    $huella = isset($payload["huella"]) ? trim((string) $payload["huella"]) : "";
    if ($huella === "") {
      $huella = hash("sha256", "notificacion|" . json_encode(array(
        isset($datos["tipo"]) ? $datos["tipo"] : "",
        isset($datos["modulo_origen"]) ? $datos["modulo_origen"] : "",
        isset($datos["entidad_origen"]) ? $datos["entidad_origen"] : "",
        isset($datos["id_entidad_origen"]) ? intval($datos["id_entidad_origen"]) : 0,
        time()
      ), JSON_UNESCAPED_UNICODE));
      $payload["huella"] = $huella;
    }
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $stmt = $db->prepare("SELECT id_notificacion
      FROM {$this->tabla_notificaciones}
      WHERE tipo=:tipo
        AND modulo_origen=:modulo
        AND entidad_origen=:entidad
        AND id_entidad_origen=:id_entidad
        AND estatus IN ('pendiente','en_revision','bloqueada')
        AND payload_json LIKE :huella
      ORDER BY id_notificacion DESC
      LIMIT 1");
    $stmt->execute(array(
      ":tipo" => $this->textoNotificacion($datos, "tipo"),
      ":modulo" => $this->textoNotificacion($datos, "modulo_origen"),
      ":entidad" => $this->textoNotificacion($datos, "entidad_origen"),
      ":id_entidad" => intval(isset($datos["id_entidad_origen"]) ? $datos["id_entidad_origen"] : 0),
      ":huella" => '%"huella":"' . $huella . '"%'
    ));
    $idNotificacion = intval($stmt->fetchColumn());

    if ($idNotificacion > 0) {
      $stmt = $db->prepare("UPDATE {$this->tabla_notificaciones} SET
          area_responsable=:area, permiso_requerido=:permiso, titulo=:titulo,
          descripcion=:descripcion, prioridad=:prioridad, url_accion=:url,
          payload_json=:payload, fecha_actualizacion=NOW()
        WHERE id_notificacion=:id");
      $stmt->execute(array(
        ":area" => $this->textoNotificacion($datos, "area_responsable"),
        ":permiso" => $this->textoNotificacion($datos, "permiso_requerido"),
        ":titulo" => $this->textoNotificacion($datos, "titulo"),
        ":descripcion" => $this->textoNotificacion($datos, "descripcion"),
        ":prioridad" => $this->textoNotificacion($datos, "prioridad", "normal"),
        ":url" => $this->textoNotificacion($datos, "url_accion"),
        ":payload" => $payloadJson,
        ":id" => $idNotificacion
      ));
      return $idNotificacion;
    }

    $stmt = $db->prepare("INSERT INTO {$this->tabla_notificaciones}
        (tipo, modulo_origen, entidad_origen, id_entidad_origen,
         area_responsable, permiso_requerido, titulo, descripcion,
         prioridad, estatus, url_accion, payload_json, creado_por, asignado_a)
      VALUES
        (:tipo, :modulo, :entidad, :id_entidad,
         :area, :permiso, :titulo, :descripcion,
         :prioridad, :estatus, :url, :payload, :usuario, :asignado)");
    $stmt->execute(array(
      ":tipo" => $this->textoNotificacion($datos, "tipo"),
      ":modulo" => $this->textoNotificacion($datos, "modulo_origen"),
      ":entidad" => $this->textoNotificacion($datos, "entidad_origen"),
      ":id_entidad" => intval(isset($datos["id_entidad_origen"]) ? $datos["id_entidad_origen"] : 0),
      ":area" => $this->textoNotificacion($datos, "area_responsable"),
      ":permiso" => $this->textoNotificacion($datos, "permiso_requerido"),
      ":titulo" => $this->textoNotificacion($datos, "titulo"),
      ":descripcion" => $this->textoNotificacion($datos, "descripcion"),
      ":prioridad" => $this->textoNotificacion($datos, "prioridad", "normal"),
      ":estatus" => $this->textoNotificacion($datos, "estatus", "pendiente"),
      ":url" => $this->textoNotificacion($datos, "url_accion"),
      ":payload" => $payloadJson,
      ":usuario" => isset($datos["creado_por"]) ? intval($datos["creado_por"]) : null,
      ":asignado" => isset($datos["asignado_a"]) && intval($datos["asignado_a"]) > 0 ? intval($datos["asignado_a"]) : null
    ));
    return intval($db->lastInsertId());
  }

  public function resolverOperativaPorHuellaEnConexion($db, $tipo, $huella) {
    $tipo = trim((string) $tipo);
    $huella = trim((string) $huella);
    if ($tipo === "" || $huella === "") {
      return 0;
    }
    $stmt = $db->prepare("UPDATE {$this->tabla_notificaciones}
      SET estatus='resuelta', fecha_resolucion=NOW(), fecha_actualizacion=NOW()
      WHERE tipo=:tipo
        AND estatus IN ('pendiente','en_revision','bloqueada')
        AND payload_json LIKE :huella");
    $stmt->execute(array(
      ":tipo" => $tipo,
      ":huella" => '%"huella":"' . $huella . '"%'
    ));
    return intval($stmt->rowCount());
  }

  private function filtroVisibilidadUsuario($idUsuario, $permisos) {
    $idUsuario = intval($idUsuario);
    $permisos = is_array($permisos) ? array_values(array_unique(array_filter($permisos))) : array();
    $partes = array();
    $params = array();

    if ($idUsuario > 0) {
      $partes[] = "n.asignado_a=:usuario_asignado";
      $params[":usuario_asignado"] = $idUsuario;
    }

    if (!empty($permisos)) {
      $marcadores = array();
      foreach ($permisos as $i => $permiso) {
        $clave = ":permiso_" . $i;
        $marcadores[] = $clave;
        $params[$clave] = $permiso;
      }
      $partes[] = "n.permiso_requerido IN (" . implode(",", $marcadores) . ")";
    }

    return array(
      "sql" => empty($partes) ? "" : "(" . implode(" OR ", $partes) . ")",
      "params" => $params
    );
  }

  private function textoNotificacion($datos, $campo, $default = "") {
    return isset($datos[$campo]) ? trim((string) $datos[$campo]) : $default;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
