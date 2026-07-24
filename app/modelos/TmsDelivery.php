<?php

class TmsDelivery extends CRUD {

  private $tabla_servicios = "erp_tms_servicios";
  private $tabla_detalle = "erp_tms_servicios_detalle";
  private $tabla_costos = "erp_tms_servicios_costos";
  private $tabla_eventos = "erp_tms_eventos";
  private $tabla_evidencias = "erp_tms_evidencias";

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: exponer catalogos operativos iniciales de TMS sin depender de Ventas.
   * Impacto: TMS Delivery; alimenta UI y dry-runs sin escribir BD.
   * Contrato: read-only; no consulta ni modifica servicios.
   */
  public function catalogosTms() {
    return $this->respuesta(false, "success", "Catalogos TMS consultados", array(
      "tipos_servicio" => array(
        array("valor" => "entrega_local", "texto" => "Entrega local"),
        array("valor" => "entrega_express", "texto" => "Entrega express"),
        array("valor" => "entrega_programada", "texto" => "Entrega programada"),
        array("valor" => "recoleccion_cliente", "texto" => "Recoleccion con cliente"),
        array("valor" => "entrega_postventa", "texto" => "Entrega postventa"),
        array("valor" => "traslado_revision", "texto" => "Traslado para revision"),
        array("valor" => "visita_revision", "texto" => "Visita de revision"),
        array("valor" => "envio_tercero", "texto" => "Envio por tercero")
      ),
      "estatus_servicio" => array("cotizada", "solicitada", "programada", "preparando", "lista_para_salida", "en_ruta", "entregada", "no_entregada", "reprogramada", "pendiente_cliente", "cancelada"),
      "estatus_cobro" => array("incluida_cortesia", "cobrada", "por_cobrar", "pendiente", "bonificada"),
      "resultados_logisticos" => array("pendiente", "completa", "parcial", "sin_entrega", "cliente_recogera", "nuevo_intento_requerido", "cerrada_sin_entrega"),
      "prioridades" => array("normal", "express", "urgente"),
      "motivos_logisticos" => array("venta_inicial", "entrega_adicional", "recoleccion", "revision", "cambio_acordado", "cortesia_autorizada", "otro"),
      "modulos_solicitantes" => array("ventas", "ecommerce", "postventa", "crm", "manual"),
      "contrato" => $this->contratoDominio()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar servicios TMS si el esquema existe, o devolver estado vacio controlado.
   * Impacto: TMS Delivery; permite preparar bandeja sin tocar Ventas ni inventario.
   * Contrato: read-only; no crea, actualiza ni resuelve servicios.
   */
  public function listarServicios($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para consultar TMS", array("conexion_mysql" => false));
      }
      if (!$this->tablaExiste($db, $this->tabla_servicios)) {
        return $this->respuesta(false, "info", "Esquema TMS pendiente; no hay servicios para listar", array(
          "schema_pendiente" => true,
          "servicios" => array(),
          "contrato" => $this->contratoDominio()
        ));
      }

      $where = array("s.estatus <> 'cancelado'");
      $params = array();
      $estatus = $this->texto($filtros, "estatus_servicio");
      $cobro = $this->texto($filtros, "estatus_cobro");
      $tipo = $this->texto($filtros, "tipo_servicio");
      $cliente = intval($this->valor($filtros, "id_cliente_crm", 0));
      $responsable = intval($this->valor($filtros, "responsable_asignado", 0));

      if ($estatus !== "") {
        $where[] = "s.estatus_servicio=:estatus";
        $params[":estatus"] = $estatus;
      }
      if ($cobro !== "") {
        $where[] = "s.estatus_cobro=:cobro";
        $params[":cobro"] = $cobro;
      }
      if ($tipo !== "") {
        $where[] = "s.tipo_servicio=:tipo";
        $params[":tipo"] = $tipo;
      }
      if ($cliente > 0) {
        $where[] = "s.id_cliente_crm=:cliente";
        $params[":cliente"] = $cliente;
      }
      if ($responsable > 0) {
        $where[] = "s.responsable_asignado=:responsable";
        $params[":responsable"] = $responsable;
      }

      $limite = intval($this->valor($filtros, "limite", 50));
      if ($limite <= 0 || $limite > 100) {
        $limite = 50;
      }

      $sql = "SELECT s.id_tms_servicio, s.folio, s.solicitado_por_modulo, s.solicitado_por_tipo,
          s.referencia_externa, s.tipo_servicio, s.estatus_servicio, s.estatus_cobro,
          s.resultado_logistico, s.prioridad, s.id_cliente_crm, s.cliente_nombre_snapshot,
          s.cliente_contacto_snapshot, s.zona_snapshot, s.fecha_solicitud, s.fecha_programada,
          s.ventana_inicio, s.ventana_fin, s.responsable_asignado, s.fecha_cierre
        FROM {$this->tabla_servicios} s
        WHERE " . implode(" AND ", $where) . "
        ORDER BY s.fecha_programada IS NULL ASC, s.fecha_programada ASC, s.ventana_inicio ASC, s.id_tms_servicio DESC
        LIMIT " . $limite;
      $stmt = $db->prepare($sql);
      $stmt->execute($params);

      return $this->respuesta(false, "success", "Servicios TMS consultados", array(
        "schema_pendiente" => false,
        "servicios" => $stmt->fetchAll(PDO::FETCH_ASSOC),
        "filtros" => $filtros
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: validar una solicitud TMS antes de permitir guardado real futuro.
   * Impacto: TMS Delivery; fija requisitos minimos del servicio sin vender, cobrar productos ni mover inventario.
   * Contrato: dry-run; no inserta servicio, no genera folio real y no modifica BD.
   */
  public function servicioDryRun($datos = array()) {
    $catalogos = $this->catalogosTms();
    $depurarCatalogos = isset($catalogos["depurar"]) ? $catalogos["depurar"] : array();

    $tipoServicio = $this->texto($datos, "tipo_servicio");
    $prioridad = $this->texto($datos, "prioridad", "normal");
    $estatusCobro = $this->texto($datos, "estatus_cobro", "pendiente");
    $modulo = $this->texto($datos, "solicitado_por_modulo", "manual");
    $tipoOrigen = $this->texto($datos, "solicitado_por_tipo", "solicitud_manual");
    $clienteNombre = $this->texto($datos, "cliente_nombre_snapshot");
    $clienteContacto = $this->texto($datos, "cliente_contacto_snapshot");
    $direccion = $this->texto($datos, "direccion_snapshot");
    $fechaProgramada = $this->texto($datos, "fecha_programada");
    $ventanaInicio = $this->texto($datos, "ventana_inicio");
    $ventanaFin = $this->texto($datos, "ventana_fin");
    $precioCobrado = floatval($this->valor($datos, "precio_cobrado", 0));
    $detalle = $this->normalizarDetalle($datos);
    $bloqueos = array();
    $advertencias = array();

    if (!in_array($tipoServicio, $this->valoresCatalogo($depurarCatalogos, "tipos_servicio"), true)) {
      $bloqueos[] = "Selecciona un tipo de servicio logistico valido";
    }
    if (!in_array($prioridad, $this->valor($depurarCatalogos, "prioridades", array()), true)) {
      $bloqueos[] = "Selecciona prioridad valida";
    }
    if (!in_array($estatusCobro, $this->valor($depurarCatalogos, "estatus_cobro", array()), true)) {
      $bloqueos[] = "Selecciona estatus de cobro logistico valido";
    }
    if (!in_array($modulo, $this->valor($depurarCatalogos, "modulos_solicitantes", array()), true)) {
      $bloqueos[] = "El modulo solicitante no es valido";
    }
    if ($tipoOrigen === "") {
      $bloqueos[] = "Indica el tipo de solicitud logistico";
    }
    if ($clienteNombre === "" && intval($this->valor($datos, "id_cliente_crm", 0)) <= 0) {
      $bloqueos[] = "Captura cliente o referencia de contacto para la entrega";
    }
    if ($clienteContacto === "") {
      $advertencias[] = "Conviene capturar telefono/contacto para coordinar entrega";
    }
    if ($direccion === "") {
      $bloqueos[] = "Captura direccion o punto de entrega";
    }
    if ($fechaProgramada === "") {
      $advertencias[] = "Sin fecha programada; quedara como solicitud pendiente de programacion";
    }
    if (($ventanaInicio === "" && $ventanaFin !== "") || ($ventanaInicio !== "" && $ventanaFin === "")) {
      $bloqueos[] = "Captura ventana completa de entrega";
    }
    if ($estatusCobro === "cobrada" && $precioCobrado <= 0) {
      $advertencias[] = "El servicio aparece cobrado con importe cero; valida si es cortesia o captura precio";
    }
    if (in_array($estatusCobro, array("bonificada", "incluida_cortesia"), true) && $this->texto($datos, "motivo_bonificacion") === "") {
      $bloqueos[] = "Indica motivo de bonificacion o cortesia logistica";
    }
    if (empty($detalle)) {
      $advertencias[] = "Sin detalle de paquete; se podra operar solo como servicio general";
    }

    $puedeGuardarFuturo = empty($bloqueos);

    return $this->respuesta(false, $puedeGuardarFuturo ? "success" : "warning", $puedeGuardarFuturo ? "Solicitud TMS valida en dry-run" : "Solicitud TMS bloqueada en dry-run", array(
      "puede_guardar_futuro" => $puedeGuardarFuturo,
      "bloqueos" => $bloqueos,
      "advertencias" => $advertencias,
      "servicio_preview" => array(
        "folio_preview" => "TMS-" . date("Ymd") . "-PREVIEW",
        "solicitado_por_modulo" => $modulo,
        "solicitado_por_tipo" => $tipoOrigen,
        "tipo_servicio" => $tipoServicio,
        "prioridad" => $prioridad,
        "estatus_servicio_inicial" => "solicitada",
        "estatus_cobro" => $estatusCobro,
        "resultado_logistico" => "pendiente",
        "cliente_nombre_snapshot" => $clienteNombre,
        "cliente_contacto_snapshot" => $clienteContacto,
        "direccion_snapshot" => $direccion,
        "fecha_programada" => $fechaProgramada,
        "ventana_inicio" => $ventanaInicio,
        "ventana_fin" => $ventanaFin,
        "precio_cobrado" => $precioCobrado,
        "detalle" => $detalle
      ),
      "contrato" => $this->contratoDominio(),
      "no_escritura_bd" => true
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: crear un folio TMS real cuando el esquema ya este aplicado.
   * Impacto: TMS Delivery; crea solo servicio logistico, costo, detalle y evento inicial.
   * Contrato: escritura transaccional; no confirma ventas, no cancela ventas, no decide garantias y no mueve inventario.
   */
  public function guardarServicio($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para guardar TMS", array("conexion_mysql" => false));
      }
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema TMS pendiente; no se puede crear servicio", array(
          "schema_pendiente" => true,
          "tablas_requeridas" => array($this->tabla_servicios, $this->tabla_detalle, $this->tabla_costos, $this->tabla_eventos, $this->tabla_evidencias),
          "regla" => "Aplicar DDL TMS con autorizacion antes de habilitar guardado real."
        ));
      }

      $validacion = $this->servicioDryRun($datos);
      $depurarValidacion = isset($validacion["depurar"]) ? $validacion["depurar"] : array();
      if (empty($depurarValidacion["puede_guardar_futuro"])) {
        return $this->respuesta(true, "warning", "Solicitud TMS incompleta; no se guardo", $depurarValidacion);
      }

      $preview = $depurarValidacion["servicio_preview"];
      $folio = $this->generarFolioTms();
      $idUsuario = intval($idUsuario) > 0 ? intval($idUsuario) : null;
      $idSolicitadoPor = intval($this->valor($datos, "solicitado_por_id", 0)) > 0 ? intval($this->valor($datos, "solicitado_por_id", 0)) : null;
      $idCliente = intval($this->valor($datos, "id_cliente_crm", 0)) > 0 ? intval($this->valor($datos, "id_cliente_crm", 0)) : null;
      $idDireccion = intval($this->valor($datos, "id_direccion_crm", 0)) > 0 ? intval($this->valor($datos, "id_direccion_crm", 0)) : null;
      $responsable = intval($this->valor($datos, "responsable_asignado", 0)) > 0 ? intval($this->valor($datos, "responsable_asignado", 0)) : null;

      $db->beginTransaction();

      $stmt = $db->prepare("INSERT INTO {$this->tabla_servicios}
        (folio, solicitado_por_modulo, solicitado_por_tipo, solicitado_por_id, referencia_externa,
         motivo_logistico, id_cliente_crm, id_direccion_crm, cliente_nombre_snapshot,
         cliente_contacto_snapshot, direccion_snapshot, zona_snapshot, tipo_servicio, estatus_servicio,
         estatus_cobro, resultado_logistico, prioridad, fecha_programada, ventana_inicio, ventana_fin,
         creado_por, responsable_asignado, observaciones, estatus, fecha_actualizacion)
        VALUES
        (:folio, :modulo, :tipo_origen, :origen_id, :referencia,
         :motivo, :cliente, :direccion_id, :cliente_nombre,
         :cliente_contacto, :direccion_snapshot, :zona, :tipo_servicio, 'solicitada',
         :estatus_cobro, 'pendiente', :prioridad, :fecha_programada, :ventana_inicio, :ventana_fin,
         :creado_por, :responsable, :observaciones, 'activo', CURRENT_TIMESTAMP)");
      $stmt->execute(array(
        ":folio" => $folio,
        ":modulo" => $preview["solicitado_por_modulo"],
        ":tipo_origen" => $preview["solicitado_por_tipo"],
        ":origen_id" => $idSolicitadoPor,
        ":referencia" => $this->texto($datos, "referencia_externa"),
        ":motivo" => $this->texto($datos, "motivo_logistico", "venta_inicial"),
        ":cliente" => $idCliente,
        ":direccion_id" => $idDireccion,
        ":cliente_nombre" => $preview["cliente_nombre_snapshot"],
        ":cliente_contacto" => $preview["cliente_contacto_snapshot"],
        ":direccion_snapshot" => $preview["direccion_snapshot"],
        ":zona" => $this->texto($datos, "zona_snapshot"),
        ":tipo_servicio" => $preview["tipo_servicio"],
        ":estatus_cobro" => $preview["estatus_cobro"],
        ":prioridad" => $preview["prioridad"],
        ":fecha_programada" => $this->nullSiVacio($preview["fecha_programada"]),
        ":ventana_inicio" => $this->nullSiVacio($preview["ventana_inicio"]),
        ":ventana_fin" => $this->nullSiVacio($preview["ventana_fin"]),
        ":creado_por" => $idUsuario,
        ":responsable" => $responsable,
        ":observaciones" => $this->texto($datos, "observaciones")
      ));
      $idServicio = intval($db->lastInsertId());

      foreach ($preview["detalle"] as $item) {
        $stmtDetalle = $db->prepare("INSERT INTO {$this->tabla_detalle}
          (id_tms_servicio, referencia_item_origen, id_sku_erp, id_inventario_unidad, cantidad,
           descripcion_snapshot, requiere_cuidado_especial, estatus_preparacion, observaciones, estatus, fecha_actualizacion)
          VALUES
          (:servicio, :referencia_item, :sku, :unidad, :cantidad,
           :descripcion, :cuidado, 'pendiente', :observaciones, 'activo', CURRENT_TIMESTAMP)");
        $stmtDetalle->execute(array(
          ":servicio" => $idServicio,
          ":referencia_item" => $item["referencia_item_origen"],
          ":sku" => intval($item["id_sku_erp"]) > 0 ? intval($item["id_sku_erp"]) : null,
          ":unidad" => intval($item["id_inventario_unidad"]) > 0 ? intval($item["id_inventario_unidad"]) : null,
          ":cantidad" => floatval($item["cantidad"]),
          ":descripcion" => $item["descripcion_snapshot"],
          ":cuidado" => intval($item["requiere_cuidado_especial"]),
          ":observaciones" => $this->texto($item, "observaciones")
        ));
      }

      $stmtCosto = $db->prepare("INSERT INTO {$this->tabla_costos}
        (id_tms_servicio, precio_cobrado, costo_estimado, costo_real, metodo_cobro,
         motivo_bonificacion, autorizado_por, datos_snapshot, estatus, fecha_actualizacion)
        VALUES
        (:servicio, :precio, :costo_estimado, :costo_real, :metodo_cobro,
         :motivo_bonificacion, :autorizado_por, :datos_snapshot, 'activo', CURRENT_TIMESTAMP)");
      $stmtCosto->execute(array(
        ":servicio" => $idServicio,
        ":precio" => floatval($this->valor($datos, "precio_cobrado", 0)),
        ":costo_estimado" => floatval($this->valor($datos, "costo_estimado", 0)),
        ":costo_real" => $this->valor($datos, "costo_real", null) === null || $this->valor($datos, "costo_real", "") === "" ? null : floatval($this->valor($datos, "costo_real", 0)),
        ":metodo_cobro" => $this->texto($datos, "metodo_cobro", "no_aplica"),
        ":motivo_bonificacion" => $this->texto($datos, "motivo_bonificacion"),
        ":autorizado_por" => intval($this->valor($datos, "autorizado_por", 0)) > 0 ? intval($this->valor($datos, "autorizado_por", 0)) : null,
        ":datos_snapshot" => json_encode(array(
          "estatus_cobro" => $preview["estatus_cobro"],
          "origen" => array(
            "modulo" => $preview["solicitado_por_modulo"],
            "tipo" => $preview["solicitado_por_tipo"],
            "id" => $idSolicitadoPor
          )
        ))
      ));

      $this->registrarEvento($db, $idServicio, "servicio_creado", null, "solicitada", "Servicio TMS creado", $idUsuario, array(
        "folio" => $folio,
        "contrato" => $this->contratoDominio()
      ));

      $db->commit();

      return $this->respuesta(false, "success", "Servicio TMS creado", array(
        "id_tms_servicio" => $idServicio,
        "folio" => $folio,
        "contrato" => $this->contratoDominio()
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
   * Proposito: documentar contrato de acciones TMS antes de implementar cambios de estado reales.
   * Impacto: TMS Delivery; permite preparar UI/UAT sin modificar servicios.
   * Contrato: read-only; no programa, no asigna, no entrega y no cancela servicios.
   */
  public function accionesContratoReadOnly() {
    return $this->respuesta(false, "success", "Contrato de acciones TMS consultado", array(
      "acciones" => array(
        array("accion" => "programar", "permiso" => "tms.programar", "requiere" => array("id_tms_servicio", "fecha_programada", "ventana_inicio", "ventana_fin")),
        array("accion" => "asignar_responsable", "permiso" => "tms.programar", "requiere" => array("id_tms_servicio", "responsable_asignado")),
        array("accion" => "marcar_lista_salida", "permiso" => "tms.operar", "requiere" => array("id_tms_servicio")),
        array("accion" => "iniciar_ruta", "permiso" => "tms.operar", "requiere" => array("id_tms_servicio")),
        array("accion" => "entregar", "permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "resultado_logistico", "evidencia_o_comentario")),
        array("accion" => "no_entregada", "permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "motivo")),
        array("accion" => "pendiente_cliente", "permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "motivo")),
        array("accion" => "cancelar_servicio", "permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "motivo"))
      ),
      "regla" => "Estas acciones modificaran solo TMS cuando se implementen; no cancelan ventas ni deciden garantias."
    ));
  }

  private function normalizarDetalle($datos) {
    $detalle = $this->valor($datos, "detalle", array());
    if (is_string($detalle) && trim($detalle) !== "") {
      $json = json_decode($detalle, true);
      $detalle = is_array($json) ? $json : array();
    }
    if (!is_array($detalle)) {
      return array();
    }
    $normalizado = array();
    foreach ($detalle as $item) {
      if (!is_array($item)) {
        continue;
      }
      $descripcion = $this->texto($item, "descripcion_snapshot", $this->texto($item, "descripcion"));
      if ($descripcion === "") {
        continue;
      }
      $normalizado[] = array(
        "referencia_item_origen" => $this->texto($item, "referencia_item_origen"),
        "id_sku_erp" => intval($this->valor($item, "id_sku_erp", 0)),
        "id_inventario_unidad" => intval($this->valor($item, "id_inventario_unidad", 0)),
        "cantidad" => max(0.0001, floatval($this->valor($item, "cantidad", 1))),
        "descripcion_snapshot" => $descripcion,
        "requiere_cuidado_especial" => intval($this->valor($item, "requiere_cuidado_especial", 0)) === 1 ? 1 : 0
      );
    }
    return $normalizado;
  }

  private function valoresCatalogo($catalogos, $clave) {
    $items = $this->valor($catalogos, $clave, array());
    $valores = array();
    foreach ($items as $item) {
      if (is_array($item) && isset($item["valor"])) {
        $valores[] = $item["valor"];
      } elseif (is_string($item)) {
        $valores[] = $item;
      }
    }
    return $valores;
  }

  private function contratoDominio() {
    return array(
      "tms_independiente_de_ventas" => true,
      "no_confirma_ventas" => true,
      "no_cancela_ventas" => true,
      "no_decide_garantias" => true,
      "no_mueve_inventario_por_si_mismo" => true,
      "resultado_no_entregado_no_cambia_venta" => true
    );
  }

  private function registrarEvento($db, $idServicio, $tipoEvento, $estatusAnterior, $estatusNuevo, $comentario, $idUsuario = null, $payload = array()) {
    $stmt = $db->prepare("INSERT INTO {$this->tabla_eventos}
      (id_tms_servicio, tipo_evento, estatus_anterior, estatus_nuevo, resultado_anterior,
       resultado_nuevo, comentario, payload_json, creado_por)
      VALUES
      (:servicio, :tipo_evento, :estatus_anterior, :estatus_nuevo, NULL,
       'pendiente', :comentario, :payload, :creado_por)");
    $stmt->execute(array(
      ":servicio" => intval($idServicio),
      ":tipo_evento" => $tipoEvento,
      ":estatus_anterior" => $estatusAnterior,
      ":estatus_nuevo" => $estatusNuevo,
      ":comentario" => $comentario,
      ":payload" => json_encode($payload),
      ":creado_por" => intval($idUsuario) > 0 ? intval($idUsuario) : null
    ));
  }

  private function schemaCompleto($db) {
    foreach (array($this->tabla_servicios, $this->tabla_detalle, $this->tabla_costos, $this->tabla_eventos, $this->tabla_evidencias) as $tabla) {
      if (!$this->tablaExiste($db, $tabla)) {
        return false;
      }
    }
    return true;
  }

  private function generarFolioTms() {
    return "TMS-" . date("Ymd-His") . "-" . random_int(100, 999);
  }

  private function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
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
