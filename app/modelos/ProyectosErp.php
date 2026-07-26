<?php

require_once __DIR__ . "/NotificacionesErp.php";

class ProyectosErp extends CRUD {

  private $tabla_proyectos = "erp_proyectos";
  private $tabla_objetivos = "erp_proyecto_objetivos";
  private $tabla_tareas = "erp_proyecto_tareas";
  private $tabla_comentarios = "erp_proyecto_comentarios";
  private $tabla_eventos = "erp_proyecto_eventos";
  private $columnasCache = array();

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: exponer catalogos operativos del modulo Proyectos sin escritura.
   * Impacto: Proyectos transversal; alimenta formularios y filtros.
   * Contrato: read-only; no precarga tareas reales de otros modulos.
   */
  public function catalogosProyectos() {
    return $this->respuesta(false, "success", "Catalogos de Proyectos consultados", array(
      "tipos_proyecto" => array(
        array("valor" => "construccion_erp", "texto" => "Construccion ERP/POS"),
        array("valor" => "operacion_negocio", "texto" => "Operacion del negocio"),
        array("valor" => "mejora_proceso", "texto" => "Mejora de proceso"),
        array("valor" => "incidencia", "texto" => "Incidencia"),
        array("valor" => "implementacion_modulo", "texto" => "Implementacion de modulo")
      ),
      "estatus_proyecto" => array("borrador", "activo", "pausado", "bloqueado", "cerrado", "cancelado"),
      "estatus_tarea" => array("pendiente", "en_proceso", "en_revision", "bloqueada", "completada", "descartada"),
      "prioridades" => array("info", "normal", "alta", "critica"),
      "origenes" => array("manual", "whatsapp", "chat_ia", "uat", "operacion", "cliente", "auditoria"),
      "modulos" => array("general", "catalogo", "compras", "proveedores", "almacen", "inventario", "ventas", "pos", "crm", "tms", "garantias", "rentabilidad", "sistema")
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: consultar resumen operativo de proyectos y tareas.
   * Impacto: Proyectos transversal; alimenta KPIs sin crear datos.
   * Contrato: read-only; si falta esquema devuelve resumen vacio controlado.
   */
  public function resumenProyectos($idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(false, "info", "Esquema Proyectos pendiente; resumen vacio", array(
          "schema_pendiente" => true,
          "kpis" => $this->kpisVacios()
        ));
      }

      $kpis = $this->kpisVacios();
      $kpis["proyectos_activos"] = $this->conteo($db, $this->tabla_proyectos, "estatus IN ('activo','bloqueado','pausado')");
      $kpis["tareas_pendientes"] = $this->conteo($db, $this->tabla_tareas, "estatus IN ('pendiente','en_proceso','en_revision','bloqueada')");
      $kpis["tareas_vencidas"] = $this->conteo($db, $this->tabla_tareas, "estatus IN ('pendiente','en_proceso','en_revision','bloqueada') AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE()");
      $kpis["tareas_bloqueadas"] = $this->conteo($db, $this->tabla_tareas, "estatus='bloqueada'");
      if (intval($idUsuario) > 0) {
        $kpis["mis_tareas"] = $this->conteo($db, $this->tabla_tareas, "estatus IN ('pendiente','en_proceso','en_revision','bloqueada') AND id_responsable=" . intval($idUsuario));
      }
      $totalTareas = $this->conteo($db, $this->tabla_tareas, "1=1");
      $tareasCompletadas = $this->conteo($db, $this->tabla_tareas, "estatus='completada'");
      $kpis["tareas_totales"] = $totalTareas;
      $kpis["tareas_completadas"] = $tareasCompletadas;
      $kpis["avance_general"] = $totalTareas > 0 ? round(($tareasCompletadas / $totalTareas) * 100, 2) : 0;

      return $this->respuesta(false, "success", "Resumen de Proyectos consultado", array(
        "schema_pendiente" => false,
        "kpis" => $kpis,
        "por_estatus" => $this->agregadoSimple($db, $this->tabla_tareas, "estatus", "1=1"),
        "por_modulo" => $this->agregadoSimple($db, $this->tabla_tareas, "COALESCE(modulo_relacionado,'general')", "1=1"),
        "por_prioridad" => $this->agregadoSimple($db, $this->tabla_tareas, "prioridad", "estatus IN ('pendiente','en_proceso','en_revision','bloqueada')"),
        "proyectos_riesgo" => $this->proyectosRiesgo($db)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar proyectos visibles con conteo agregado de tareas.
   * Impacto: Proyectos transversal; no consulta avances externos por modulo.
   * Contrato: read-only; filtros simples por estatus, tipo, modulo y texto.
   */
  public function listarProyectos($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(false, "info", "Esquema Proyectos pendiente; no hay proyectos para listar", array(
          "schema_pendiente" => true,
          "proyectos" => array()
        ));
      }

      $where = array("p.estatus <> 'cancelado'");
      $params = array();
      $this->aplicarFiltroTexto($where, $params, "p.nombre", "buscar", $filtros);
      $this->aplicarFiltroIgual($where, $params, "p.estatus", "estatus", $filtros);
      $this->aplicarFiltroIgual($where, $params, "p.tipo", "tipo", $filtros);
      $this->aplicarFiltroIgual($where, $params, "p.modulo_relacionado", "modulo", $filtros);

      $usuarioNombreExpr = $this->expresionNombreUsuario("u");
      $sql = "SELECT p.*,
          {$usuarioNombreExpr} responsable_nombre,
          COUNT(t.id_tarea) total_tareas,
          SUM(CASE WHEN t.estatus IN ('pendiente','en_proceso','en_revision','bloqueada') THEN 1 ELSE 0 END) tareas_abiertas,
          SUM(CASE WHEN t.estatus='completada' THEN 1 ELSE 0 END) tareas_completadas,
          SUM(CASE WHEN t.estatus='bloqueada' THEN 1 ELSE 0 END) tareas_bloqueadas
        FROM {$this->tabla_proyectos} p
        LEFT JOIN {$this->tabla_tareas} t ON t.id_proyecto=p.id_proyecto
        LEFT JOIN sys_usuarios u ON u.id_usuario=p.id_responsable
        WHERE " . implode(" AND ", $where) . "
        GROUP BY p.id_proyecto
        ORDER BY FIELD(p.prioridad,'critica','alta','normal','info'), p.id_proyecto DESC
        LIMIT 80";
      $stmt = $db->prepare($sql);
      $stmt->execute($params);

      return $this->respuesta(false, "success", "Proyectos consultados", array(
        "schema_pendiente" => false,
        "proyectos" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar tareas por filtros de operacion y bandeja personal.
   * Impacto: Proyectos transversal; permite seguimiento sin depender de WhatsApp o memoria de chat.
   * Contrato: read-only; no crea ni cierra tareas.
   */
  public function listarTareas($filtros = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(false, "info", "Esquema Proyectos pendiente; no hay tareas para listar", array(
          "schema_pendiente" => true,
          "tareas" => array()
        ));
      }

      $where = array("t.estatus <> 'descartada'");
      $params = array();
      $this->aplicarFiltroTexto($where, $params, "t.titulo", "buscar", $filtros);
      $estatusFiltro = $this->texto($filtros, "estatus");
      if ($estatusFiltro !== "") {
        $where[] = "t.estatus=:estatus";
        $params[":estatus"] = $estatusFiltro;
      } else {
        $where[] = "t.estatus IN ('pendiente','en_proceso','en_revision','bloqueada')";
      }
      $this->aplicarFiltroIgual($where, $params, "t.prioridad", "prioridad", $filtros);
      $this->aplicarFiltroIgual($where, $params, "t.modulo_relacionado", "modulo", $filtros);
      $idProyecto = intval($this->valor($filtros, "id_proyecto", 0));
      if ($idProyecto > 0) {
        $where[] = "t.id_proyecto=:id_proyecto";
        $params[":id_proyecto"] = $idProyecto;
      }
      if ($this->texto($filtros, "mias") === "1" && intval($idUsuario) > 0) {
        $where[] = "t.id_responsable=:usuario";
        $params[":usuario"] = intval($idUsuario);
      }

      $usuarioNombreExpr = $this->expresionNombreUsuario("u");
      $sql = "SELECT t.*, p.folio proyecto_folio, p.nombre proyecto_nombre,
          {$usuarioNombreExpr} responsable_nombre
        FROM {$this->tabla_tareas} t
        INNER JOIN {$this->tabla_proyectos} p ON p.id_proyecto=t.id_proyecto
        LEFT JOIN sys_usuarios u ON u.id_usuario=t.id_responsable
        WHERE " . implode(" AND ", $where) . "
        ORDER BY FIELD(t.prioridad,'critica','alta','normal','info'),
          t.fecha_vencimiento IS NULL ASC, t.fecha_vencimiento ASC, t.id_tarea DESC
        LIMIT 120";
      $stmt = $db->prepare($sql);
      $stmt->execute($params);

      return $this->respuesta(false, "success", "Tareas consultadas", array(
        "schema_pendiente" => false,
        "tareas" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-25
   * Proposito: listar usuarios internos activos para asignacion de proyectos y tareas.
   * Impacto: Proyectos transversal; solo consulta sys_usuarios.
   * Contrato: read-only; no asigna roles ni crea usuarios.
   */
  public function listarUsuariosAsignables() {
    try {
      $db = $this->getConexion();
      if (!$this->tablaExiste($db, "sys_usuarios")) {
        return $this->respuesta(false, "info", "No hay tabla de usuarios disponible", array("usuarios" => array()));
      }
      $nombreExpr = $this->expresionNombreUsuario();
      $areaExpr = $this->expresionAreaUsuario();
      $stmt = $db->query("SELECT id_usuario,
          {$nombreExpr} nombre,
          {$areaExpr} area_departamento
        FROM sys_usuarios
        WHERE COALESCE(estatus,1)=1
        ORDER BY nombre ASC, id_usuario ASC");

      return $this->respuesta(false, "success", "Usuarios asignables consultados", array(
        "usuarios" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: crear o editar un proyecto operativo vacio de avances externos.
   * Impacto: Proyectos transversal; registra evento local y no modifica otros modulos.
   * Contrato: escritura transaccional; requiere nombre, tipo, estatus y prioridad validos.
   */
  public function guardarProyecto($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema Proyectos pendiente; no se puede guardar", array("schema_pendiente" => true));
      }

      $validacion = $this->validarProyecto($datos);
      if (!empty($validacion["bloqueos"])) {
        return $this->respuesta(true, "warning", "Proyecto incompleto", $validacion);
      }

      $idProyecto = intval($this->valor($datos, "id_proyecto", 0));
      $db->beginTransaction();
      if ($idProyecto > 0) {
        $stmt = $db->prepare("UPDATE {$this->tabla_proyectos} SET
            nombre=:nombre, descripcion=:descripcion, tipo=:tipo, modulo_relacionado=:modulo,
            estatus=:estatus, prioridad=:prioridad, id_responsable=:responsable,
            fecha_inicio=:fecha_inicio, fecha_objetivo=:fecha_objetivo,
            fecha_cierre=:fecha_cierre, fecha_actualizacion=NOW()
          WHERE id_proyecto=:id");
        $stmt->execute(array(
          ":nombre" => $validacion["proyecto"]["nombre"],
          ":descripcion" => $validacion["proyecto"]["descripcion"],
          ":tipo" => $validacion["proyecto"]["tipo"],
          ":modulo" => $validacion["proyecto"]["modulo_relacionado"],
          ":estatus" => $validacion["proyecto"]["estatus"],
          ":prioridad" => $validacion["proyecto"]["prioridad"],
          ":responsable" => $validacion["proyecto"]["id_responsable"],
          ":fecha_inicio" => $validacion["proyecto"]["fecha_inicio"],
          ":fecha_objetivo" => $validacion["proyecto"]["fecha_objetivo"],
          ":fecha_cierre" => in_array($validacion["proyecto"]["estatus"], array("cerrado", "cancelado"), true) ? date("Y-m-d H:i:s") : null,
          ":id" => $idProyecto
        ));
        $accion = "proyecto_actualizado";
      } else {
        $folio = $this->generarFolioProyecto();
        $stmt = $db->prepare("INSERT INTO {$this->tabla_proyectos}
          (folio, nombre, descripcion, tipo, modulo_relacionado, estatus, prioridad,
           id_responsable, creado_por, fecha_inicio, fecha_objetivo)
          VALUES
          (:folio, :nombre, :descripcion, :tipo, :modulo, :estatus, :prioridad,
           :responsable, :creado_por, :fecha_inicio, :fecha_objetivo)");
        $stmt->execute(array(
          ":folio" => $folio,
          ":nombre" => $validacion["proyecto"]["nombre"],
          ":descripcion" => $validacion["proyecto"]["descripcion"],
          ":tipo" => $validacion["proyecto"]["tipo"],
          ":modulo" => $validacion["proyecto"]["modulo_relacionado"],
          ":estatus" => $validacion["proyecto"]["estatus"],
          ":prioridad" => $validacion["proyecto"]["prioridad"],
          ":responsable" => $validacion["proyecto"]["id_responsable"],
          ":creado_por" => intval($idUsuario) > 0 ? intval($idUsuario) : null,
          ":fecha_inicio" => $validacion["proyecto"]["fecha_inicio"],
          ":fecha_objetivo" => $validacion["proyecto"]["fecha_objetivo"]
        ));
        $idProyecto = intval($db->lastInsertId());
        $accion = "proyecto_creado";
      }
      $this->registrarEvento($db, $idProyecto, null, $accion, $validacion["proyecto"], $idUsuario);
      $this->confirmarTransaccionSiActiva($db);

      return $this->respuesta(false, "success", "Proyecto guardado correctamente", array(
        "id_proyecto" => $idProyecto,
        "accion" => $accion
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: crear o editar tareas accionables dentro de un proyecto.
   * Impacto: Proyectos transversal y Notificaciones; no modifica el modulo relacionado.
   * Contrato: escritura transaccional; integra notificacion si hay responsable, area o prioridad alta.
   */
  public function guardarTarea($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema Proyectos pendiente; no se puede guardar tarea", array("schema_pendiente" => true));
      }

      $validacion = $this->validarTarea($db, $datos);
      if (!empty($validacion["bloqueos"])) {
        return $this->respuesta(true, "warning", "Tarea incompleta", $validacion);
      }

      $idTarea = intval($this->valor($datos, "id_tarea", 0));
      $db->beginTransaction();
      if ($idTarea > 0) {
        $stmt = $db->prepare("UPDATE {$this->tabla_tareas} SET
            id_proyecto=:proyecto, id_objetivo=:objetivo, titulo=:titulo, descripcion=:descripcion,
            estatus=:estatus, prioridad=:prioridad, id_responsable=:responsable,
            area_responsable=:area, modulo_relacionado=:modulo, origen=:origen,
            url_contexto=:url, requiere_autorizacion=:autorizacion,
            fecha_vencimiento=:vencimiento, fecha_cierre=:cierre, fecha_actualizacion=NOW()
          WHERE id_tarea=:id");
        $stmt->execute($this->paramsTarea($validacion["tarea"], $idTarea));
        $accion = "tarea_actualizada";
      } else {
        $stmt = $db->prepare("INSERT INTO {$this->tabla_tareas}
          (id_proyecto, id_objetivo, titulo, descripcion, estatus, prioridad,
           id_responsable, area_responsable, modulo_relacionado, origen, url_contexto,
           requiere_autorizacion, fecha_vencimiento, fecha_cierre, creado_por)
          VALUES
          (:proyecto, :objetivo, :titulo, :descripcion, :estatus, :prioridad,
           :responsable, :area, :modulo, :origen, :url, :autorizacion,
           :vencimiento, :cierre, :creado_por)");
        $params = $this->paramsTarea($validacion["tarea"]);
        $params[":creado_por"] = intval($idUsuario) > 0 ? intval($idUsuario) : null;
        $stmt->execute($params);
        $idTarea = intval($db->lastInsertId());
        $accion = "tarea_creada";
      }
      $this->registrarEvento($db, $validacion["tarea"]["id_proyecto"], $idTarea, $accion, $validacion["tarea"], $idUsuario);
      $this->confirmarTransaccionSiActiva($db);
      $notificacion = $this->sincronizarNotificacionTareaSegura($idTarea, $idUsuario);

      return $this->respuesta(false, "success", "Tarea guardada correctamente", array(
        "id_tarea" => $idTarea,
        "accion" => $accion,
        "notificacion" => $notificacion
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: cambiar estado de una tarea con motivo opcional.
   * Impacto: Proyectos transversal y Notificaciones; resuelve notificacion cuando la tarea cierra.
   * Contrato: solo modifica la tarea indicada y registra evento local.
   */
  public function cambiarEstatusTarea($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema Proyectos pendiente; no se puede cambiar estatus", array("schema_pendiente" => true));
      }
      $idTarea = intval($this->valor($datos, "id_tarea", 0));
      $estatus = $this->texto($datos, "estatus");
      $comentario = $this->texto($datos, "comentario");
      if ($idTarea <= 0 || !in_array($estatus, $this->catalogoValores("estatus_tarea"), true)) {
        return $this->respuesta(true, "warning", "Tarea o estatus no valido");
      }
      if (in_array($estatus, array("bloqueada", "descartada"), true) && $comentario === "") {
        return $this->respuesta(true, "warning", "Indica motivo para bloquear o descartar la tarea");
      }

      $db->beginTransaction();
      $tarea = $this->consultarTareaParaUpdate($db, $idTarea);
      if (!$tarea) {
        throw new Exception("Tarea no encontrada");
      }
      $stmt = $db->prepare("UPDATE {$this->tabla_tareas}
        SET estatus=:estatus, fecha_cierre=:cierre, fecha_actualizacion=NOW()
        WHERE id_tarea=:id");
      $stmt->execute(array(
        ":estatus" => $estatus,
        ":cierre" => in_array($estatus, array("completada", "descartada"), true) ? date("Y-m-d H:i:s") : null,
        ":id" => $idTarea
      ));
      if ($comentario !== "") {
        $this->registrarComentarioEnConexion($db, intval($tarea["id_proyecto"]), $idTarea, "estatus", $comentario, $idUsuario);
      }
      $this->registrarEvento($db, intval($tarea["id_proyecto"]), $idTarea, "tarea_estatus", array(
        "estatus_anterior" => $tarea["estatus"],
        "estatus_nuevo" => $estatus,
        "comentario" => $comentario
      ), $idUsuario);
      $this->confirmarTransaccionSiActiva($db);
      $notificacion = $this->sincronizarNotificacionTareaSegura($idTarea, $idUsuario);

      return $this->respuesta(false, "success", "Estatus de tarea actualizado", array(
        "id_tarea" => $idTarea,
        "estatus" => $estatus,
        "notificacion" => $notificacion
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: consultar detalle completo de proyecto con objetivos, tareas y actividad.
   * Impacto: Proyectos transversal; vista de trabajo sin escribir BD.
   * Contrato: read-only; requiere id_proyecto.
   */
  public function consultarProyecto($idProyecto) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(false, "info", "Esquema Proyectos pendiente", array("schema_pendiente" => true));
      }
      $idProyecto = intval($idProyecto);
      $stmt = $db->prepare("SELECT * FROM {$this->tabla_proyectos} WHERE id_proyecto=:id LIMIT 1");
      $stmt->execute(array(":id" => $idProyecto));
      $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$proyecto) {
        return $this->respuesta(true, "warning", "Proyecto no encontrado");
      }

      return $this->respuesta(false, "success", "Proyecto consultado", array(
        "schema_pendiente" => false,
        "proyecto" => $proyecto,
        "objetivos" => $this->listarObjetivosProyecto($db, $idProyecto),
        "tareas" => $this->listarTareasProyecto($db, $idProyecto),
        "comentarios" => $this->listarComentariosProyecto($db, $idProyecto),
        "eventos" => $this->listarEventosProyecto($db, $idProyecto)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: registrar comentario operativo en proyecto o tarea.
   * Impacto: Proyectos transversal; conserva decisiones cortas sin reemplazar docs vivos.
   * Contrato: escritura simple; no modifica estado por si mismo.
   */
  public function registrarComentario($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema Proyectos pendiente; no se puede comentar", array("schema_pendiente" => true));
      }
      $idProyecto = intval($this->valor($datos, "id_proyecto", 0));
      $idTarea = intval($this->valor($datos, "id_tarea", 0));
      $comentario = $this->texto($datos, "comentario");
      $tipo = $this->texto($datos, "tipo", "comentario");
      if ($idProyecto <= 0 || $comentario === "") {
        return $this->respuesta(true, "warning", "Falta proyecto o comentario");
      }
      $this->registrarComentarioEnConexion($db, $idProyecto, $idTarea > 0 ? $idTarea : null, $tipo, $comentario, $idUsuario);
      return $this->respuesta(false, "success", "Comentario guardado");
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function validarProyecto($datos) {
    $catalogos = $this->catalogosProyectos();
    $nombre = $this->texto($datos, "nombre");
    $tipo = $this->texto($datos, "tipo", "operacion_negocio");
    $estatus = $this->texto($datos, "estatus", "activo");
    $prioridad = $this->texto($datos, "prioridad", "normal");
    $bloqueos = array();
    if ($nombre === "") {
      $bloqueos[] = "Captura nombre del proyecto";
    }
    if (!in_array($tipo, $this->valoresCatalogo($catalogos["depurar"], "tipos_proyecto"), true)) {
      $bloqueos[] = "Tipo de proyecto no valido";
    }
    if (!in_array($estatus, $catalogos["depurar"]["estatus_proyecto"], true)) {
      $bloqueos[] = "Estatus de proyecto no valido";
    }
    if (!in_array($prioridad, $catalogos["depurar"]["prioridades"], true)) {
      $bloqueos[] = "Prioridad no valida";
    }
    return array(
      "bloqueos" => $bloqueos,
      "proyecto" => array(
        "nombre" => $nombre,
        "descripcion" => $this->nullSiVacio($this->texto($datos, "descripcion")),
        "tipo" => $tipo,
        "modulo_relacionado" => $this->nullSiVacio($this->texto($datos, "modulo_relacionado")),
        "estatus" => $estatus,
        "prioridad" => $prioridad,
        "id_responsable" => intval($this->valor($datos, "id_responsable", 0)) > 0 ? intval($this->valor($datos, "id_responsable", 0)) : null,
        "fecha_inicio" => $this->fechaSql($this->texto($datos, "fecha_inicio")),
        "fecha_objetivo" => $this->fechaSql($this->texto($datos, "fecha_objetivo"))
      )
    );
  }

  private function validarTarea($db, $datos) {
    $catalogos = $this->catalogosProyectos();
    $idProyecto = intval($this->valor($datos, "id_proyecto", 0));
    $titulo = $this->texto($datos, "titulo");
    $estatus = $this->texto($datos, "estatus", "pendiente");
    $prioridad = $this->texto($datos, "prioridad", "normal");
    $origen = $this->texto($datos, "origen", "manual");
    $bloqueos = array();
    if ($idProyecto <= 0 || !$this->existeProyecto($db, $idProyecto)) {
      $bloqueos[] = "Selecciona un proyecto valido";
    }
    if ($titulo === "") {
      $bloqueos[] = "Captura titulo de la tarea";
    }
    if (!in_array($estatus, $catalogos["depurar"]["estatus_tarea"], true)) {
      $bloqueos[] = "Estatus de tarea no valido";
    }
    if (!in_array($prioridad, $catalogos["depurar"]["prioridades"], true)) {
      $bloqueos[] = "Prioridad no valida";
    }
    if (!in_array($origen, $catalogos["depurar"]["origenes"], true)) {
      $origen = "manual";
    }
    return array(
      "bloqueos" => $bloqueos,
      "tarea" => array(
        "id_proyecto" => $idProyecto,
        "id_objetivo" => intval($this->valor($datos, "id_objetivo", 0)) > 0 ? intval($this->valor($datos, "id_objetivo", 0)) : null,
        "titulo" => $titulo,
        "descripcion" => $this->nullSiVacio($this->texto($datos, "descripcion")),
        "estatus" => $estatus,
        "prioridad" => $prioridad,
        "id_responsable" => intval($this->valor($datos, "id_responsable", 0)) > 0 ? intval($this->valor($datos, "id_responsable", 0)) : null,
        "area_responsable" => $this->nullSiVacio($this->texto($datos, "area_responsable")),
        "modulo_relacionado" => $this->nullSiVacio($this->texto($datos, "modulo_relacionado")),
        "origen" => $origen,
        "url_contexto" => $this->normalizarUrlContexto($this->texto($datos, "url_contexto")),
        "requiere_autorizacion" => intval($this->valor($datos, "requiere_autorizacion", 0)) === 1 ? 1 : 0,
        "fecha_vencimiento" => $this->fechaSql($this->texto($datos, "fecha_vencimiento")),
        "fecha_cierre" => in_array($estatus, array("completada", "descartada"), true) ? date("Y-m-d H:i:s") : null
      )
    );
  }

  private function paramsTarea($tarea, $idTarea = null) {
    $params = array(
      ":proyecto" => $tarea["id_proyecto"],
      ":objetivo" => $tarea["id_objetivo"],
      ":titulo" => $tarea["titulo"],
      ":descripcion" => $tarea["descripcion"],
      ":estatus" => $tarea["estatus"],
      ":prioridad" => $tarea["prioridad"],
      ":responsable" => $tarea["id_responsable"],
      ":area" => $tarea["area_responsable"],
      ":modulo" => $tarea["modulo_relacionado"],
      ":origen" => $tarea["origen"],
      ":url" => $tarea["url_contexto"],
      ":autorizacion" => $tarea["requiere_autorizacion"],
      ":vencimiento" => $tarea["fecha_vencimiento"],
      ":cierre" => $tarea["fecha_cierre"]
    );
    if ($idTarea !== null) {
      $params[":id"] = intval($idTarea);
    }
    return $params;
  }

  private function sincronizarNotificacionTarea($db, $idTarea, $idUsuario) {
    if (!$this->tablaExiste($db, "erp_notificaciones")) {
      return 0;
    }
    $stmt = $db->prepare("SELECT t.*, p.nombre proyecto_nombre
      FROM {$this->tabla_tareas} t
      INNER JOIN {$this->tabla_proyectos} p ON p.id_proyecto=t.id_proyecto
      WHERE t.id_tarea=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($idTarea)));
    $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tarea) {
      return 0;
    }
    $huella = "proyecto_tarea_" . intval($idTarea);
    if (in_array($tarea["estatus"], array("completada", "descartada"), true)) {
      $notificaciones = new NotificacionesErp();
      return $notificaciones->resolverOperativaPorHuellaEnConexion($db, "proyecto_tarea", $huella);
    }
    if (empty($tarea["id_responsable"]) && empty($tarea["area_responsable"]) && !in_array($tarea["prioridad"], array("alta", "critica"), true)) {
      return 0;
    }
    $notificaciones = new NotificacionesErp();
    return $notificaciones->guardarOperativaEnConexion($db, array(
      "tipo" => "proyecto_tarea",
      "modulo_origen" => "proyectos",
      "entidad_origen" => "erp_proyecto_tareas",
      "id_entidad_origen" => intval($idTarea),
      "area_responsable" => $tarea["area_responsable"] ?: "proyectos",
      "permiso_requerido" => "proyectos.ver",
      "titulo" => $tarea["titulo"],
      "descripcion" => "Proyecto: " . $tarea["proyecto_nombre"],
      "prioridad" => $tarea["prioridad"],
      "estatus" => $tarea["estatus"] === "bloqueada" ? "bloqueada" : "pendiente",
      "url_accion" => "/proyecto?proyecto=" . intval($tarea["id_proyecto"]),
      "payload_json" => array("huella" => $huella, "id_tarea" => intval($idTarea)),
      "creado_por" => intval($idUsuario) > 0 ? intval($idUsuario) : null,
      "asignado_a" => intval($tarea["id_responsable"]) > 0 ? intval($tarea["id_responsable"]) : null
    ));
  }

  private function confirmarTransaccionSiActiva($db) {
    if ($db && $db->inTransaction()) {
      $db->commit();
    }
  }

  private function sincronizarNotificacionTareaSegura($idTarea, $idUsuario) {
    try {
      $db = $this->getConexion();
      if (!$db || $db->inTransaction()) {
        return array("error" => true, "mensaje" => "Sincronizacion omitida por transaccion activa");
      }
      return array(
        "error" => false,
        "afectadas" => $this->sincronizarNotificacionTarea($db, $idTarea, $idUsuario)
      );
    } catch (Exception $e) {
      return array("error" => true, "mensaje" => $e->getMessage());
    }
  }

  private function registrarEvento($db, $idProyecto, $idTarea, $tipo, $datos, $idUsuario) {
    $stmt = $db->prepare("INSERT INTO {$this->tabla_eventos}
      (id_proyecto, id_tarea, tipo, descripcion, datos_json, creado_por)
      VALUES (:proyecto, :tarea, :tipo, :descripcion, :datos, :usuario)");
    $stmt->execute(array(
      ":proyecto" => intval($idProyecto),
      ":tarea" => $idTarea !== null ? intval($idTarea) : null,
      ":tipo" => $tipo,
      ":descripcion" => $tipo,
      ":datos" => json_encode($datos, JSON_UNESCAPED_UNICODE),
      ":usuario" => intval($idUsuario) > 0 ? intval($idUsuario) : null
    ));
  }

  private function registrarComentarioEnConexion($db, $idProyecto, $idTarea, $tipo, $comentario, $idUsuario) {
    $stmt = $db->prepare("INSERT INTO {$this->tabla_comentarios}
      (id_proyecto, id_tarea, tipo, comentario, creado_por)
      VALUES (:proyecto, :tarea, :tipo, :comentario, :usuario)");
    $stmt->execute(array(
      ":proyecto" => intval($idProyecto),
      ":tarea" => $idTarea !== null ? intval($idTarea) : null,
      ":tipo" => $tipo,
      ":comentario" => $comentario,
      ":usuario" => intval($idUsuario) > 0 ? intval($idUsuario) : null
    ));
  }

  private function listarObjetivosProyecto($db, $idProyecto) {
    $stmt = $db->prepare("SELECT * FROM {$this->tabla_objetivos} WHERE id_proyecto=:id ORDER BY orden, id_objetivo");
    $stmt->execute(array(":id" => intval($idProyecto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function listarTareasProyecto($db, $idProyecto) {
    $stmt = $db->prepare("SELECT * FROM {$this->tabla_tareas} WHERE id_proyecto=:id ORDER BY FIELD(prioridad,'critica','alta','normal','info'), fecha_vencimiento IS NULL ASC, fecha_vencimiento ASC, id_tarea DESC");
    $stmt->execute(array(":id" => intval($idProyecto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function listarComentariosProyecto($db, $idProyecto) {
    $stmt = $db->prepare("SELECT * FROM {$this->tabla_comentarios} WHERE id_proyecto=:id ORDER BY id_comentario DESC LIMIT 60");
    $stmt->execute(array(":id" => intval($idProyecto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function listarEventosProyecto($db, $idProyecto) {
    $stmt = $db->prepare("SELECT * FROM {$this->tabla_eventos} WHERE id_proyecto=:id ORDER BY id_evento DESC LIMIT 80");
    $stmt->execute(array(":id" => intval($idProyecto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarTareaParaUpdate($db, $idTarea) {
    $stmt = $db->prepare("SELECT * FROM {$this->tabla_tareas} WHERE id_tarea=:id FOR UPDATE");
    $stmt->execute(array(":id" => intval($idTarea)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function existeProyecto($db, $idProyecto) {
    $stmt = $db->prepare("SELECT id_proyecto FROM {$this->tabla_proyectos} WHERE id_proyecto=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($idProyecto)));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function conteo($db, $tabla, $where) {
    $stmt = $db->query("SELECT COUNT(*) FROM {$tabla} WHERE {$where}");
    return intval($stmt->fetchColumn());
  }

  private function agregadoSimple($db, $tabla, $campo, $where) {
    $stmt = $db->query("SELECT {$campo} etiqueta, COUNT(*) total
      FROM {$tabla}
      WHERE {$where}
      GROUP BY etiqueta
      ORDER BY total DESC, etiqueta ASC
      LIMIT 12");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function proyectosRiesgo($db) {
    $stmt = $db->query("SELECT p.id_proyecto, p.folio, p.nombre, p.prioridad,
        SUM(CASE WHEN t.estatus='bloqueada' THEN 1 ELSE 0 END) bloqueadas,
        SUM(CASE WHEN t.estatus IN ('pendiente','en_proceso','en_revision','bloqueada')
          AND t.fecha_vencimiento IS NOT NULL AND t.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END) vencidas,
        COUNT(t.id_tarea) total_tareas,
        SUM(CASE WHEN t.estatus='completada' THEN 1 ELSE 0 END) completadas
      FROM {$this->tabla_proyectos} p
      LEFT JOIN {$this->tabla_tareas} t ON t.id_proyecto=p.id_proyecto
      WHERE p.estatus IN ('activo','bloqueado','pausado')
      GROUP BY p.id_proyecto
      HAVING bloqueadas > 0 OR vencidas > 0
      ORDER BY vencidas DESC, bloqueadas DESC, FIELD(p.prioridad,'critica','alta','normal','info')
      LIMIT 8");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function aplicarFiltroIgual(&$where, &$params, $columna, $campo, $filtros) {
    $valor = $this->texto($filtros, $campo);
    if ($valor !== "") {
      $clave = ":" . $campo;
      $where[] = $columna . "=" . $clave;
      $params[$clave] = $valor;
    }
  }

  private function aplicarFiltroTexto(&$where, &$params, $columna, $campo, $filtros) {
    $valor = $this->texto($filtros, $campo);
    if ($valor !== "") {
      $clave = ":" . $campo;
      $where[] = $columna . " LIKE " . $clave;
      $params[$clave] = "%" . $valor . "%";
    }
  }

  private function schemaCompleto($db) {
    if (!$db) {
      return false;
    }
    foreach (array($this->tabla_proyectos, $this->tabla_objetivos, $this->tabla_tareas, $this->tabla_comentarios, $this->tabla_eventos) as $tabla) {
      if (!$this->tablaExiste($db, $tabla)) {
        return false;
      }
    }
    return true;
  }

  private function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function columnaExiste($db, $tabla, $columna) {
    $clave = $tabla . "." . $columna;
    if (isset($this->columnasCache[$clave])) {
      return $this->columnasCache[$clave];
    }
    try {
      $stmt = $db->prepare("SHOW COLUMNS FROM {$tabla} LIKE :columna");
      $stmt->execute(array(":columna" => $columna));
      $this->columnasCache[$clave] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $this->columnasCache[$clave] = false;
    }
    return $this->columnasCache[$clave];
  }

  private function prefijoAlias($alias) {
    $alias = trim((string) $alias);
    return $alias !== "" ? $alias . "." : "";
  }

  private function expresionNombreUsuario($alias = "") {
    $db = $this->getConexion();
    $prefijo = $this->prefijoAlias($alias);
    $opciones = array();
    if ($this->columnaExiste($db, "sys_usuarios", "nombre_mostrar")) {
      $opciones[] = "NULLIF(TRIM(" . $prefijo . "nombre_mostrar), '')";
    }
    if ($this->columnaExiste($db, "sys_usuarios", "nombres")) {
      $partes = array("COALESCE(" . $prefijo . "nombres,'')");
      if ($this->columnaExiste($db, "sys_usuarios", "apellido_paterno")) {
        $partes[] = "COALESCE(" . $prefijo . "apellido_paterno,'')";
      }
      if ($this->columnaExiste($db, "sys_usuarios", "apellido_materno")) {
        $partes[] = "COALESCE(" . $prefijo . "apellido_materno,'')";
      }
      $opciones[] = "NULLIF(TRIM(CONCAT(" . implode(", ' ', ", $partes) . ")), '')";
    }
    if ($this->columnaExiste($db, "sys_usuarios", "alias")) {
      $opciones[] = "NULLIF(TRIM(" . $prefijo . "alias), '')";
    }
    if ($this->columnaExiste($db, "sys_usuarios", "correo")) {
      $opciones[] = "NULLIF(TRIM(" . $prefijo . "correo), '')";
    }
    $opciones[] = "CONCAT('Usuario ', " . $prefijo . "id_usuario)";
    return "COALESCE(" . implode(", ", $opciones) . ")";
  }

  private function expresionAreaUsuario($alias = "") {
    if ($this->columnaExiste($this->getConexion(), "sys_usuarios", "area_departamento")) {
      return $this->prefijoAlias($alias) . "area_departamento";
    }
    return "NULL";
  }

  private function catalogoValores($clave) {
    $catalogos = $this->catalogosProyectos();
    return isset($catalogos["depurar"][$clave]) ? $catalogos["depurar"][$clave] : array();
  }

  private function valoresCatalogo($catalogos, $clave) {
    $valores = array();
    if (!isset($catalogos[$clave]) || !is_array($catalogos[$clave])) {
      return $valores;
    }
    foreach ($catalogos[$clave] as $item) {
      if (is_array($item) && isset($item["valor"])) {
        $valores[] = $item["valor"];
      } else {
        $valores[] = $item;
      }
    }
    return $valores;
  }

  private function generarFolioProyecto() {
    return "PROY-" . date("Ymd-His") . "-" . random_int(100, 999);
  }

  private function normalizarUrlContexto($url) {
    $url = trim((string) $url);
    if ($url === "") {
      return null;
    }
    if (strpos($url, "/") === 0 || strpos($url, "http://") === 0 || strpos($url, "https://") === 0) {
      return $url;
    }
    return null;
  }

  private function fechaSql($fecha) {
    $fecha = trim((string) $fecha);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : null;
  }

  private function kpisVacios() {
    return array(
      "proyectos_activos" => 0,
      "tareas_pendientes" => 0,
      "tareas_vencidas" => 0,
      "tareas_bloqueadas" => 0,
      "mis_tareas" => 0,
      "tareas_totales" => 0,
      "tareas_completadas" => 0,
      "avance_general" => 0
    );
  }

  private function texto($datos, $campo, $default = "") {
    return trim((string) $this->valor($datos, $campo, $default));
  }

  private function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
  }

  private function nullSiVacio($valor) {
    $valor = trim((string) $valor);
    return $valor === "" ? null : $valor;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
