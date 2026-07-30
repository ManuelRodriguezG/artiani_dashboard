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
        array("valor" => "entrega_tercero", "texto" => "Entrega por tercero")
      ),
      "estatus_servicio" => array("cotizada", "solicitada", "programada", "preparando", "lista_para_salida", "en_ruta", "entregada", "no_entregada", "reprogramada", "pendiente_cliente", "cancelada"),
      "estatus_cobro" => array("incluida_cortesia", "cobrada", "por_cobrar", "pendiente", "bonificada"),
      "resultados_logisticos" => array("pendiente", "completa", "parcial", "sin_entrega", "cliente_recogera", "nuevo_intento_requerido", "cerrada_sin_entrega"),
      "prioridades" => array("normal", "express", "urgente"),
      "motivos_logisticos" => array("servicio_inicial", "reintento", "recoleccion", "entrega_tercero", "cortesia_autorizada", "cliente_no_disponible", "otro"),
      "modulos_solicitantes" => array("manual", "pos", "ecommerce", "crm", "operacion"),
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
   * Fecha: 2026-07-30
   * Proposito: generar comprobante termico read-only para un servicio logistico TMS.
   * Impacto: TMS Delivery; separa el comprobante logistico del ticket POS y de garantias/productos.
   * Contrato: read-only; no modifica TMS, Ventas, caja, inventario ni garantias.
   */
  public function ticketServicioReadOnly($datos = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para ticket TMS", array("conexion_mysql" => false));
      }
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema TMS pendiente; no se puede generar ticket", array(
          "schema_pendiente" => true,
          "contrato" => $this->contratoDominio()
        ));
      }

      $servicio = $this->buscarServicioTicket($db, $datos);
      if (!$servicio) {
        return $this->respuesta(true, "warning", "Servicio TMS no encontrado para ticket", array(
          "filtros" => array(
            "id_tms_servicio" => intval($this->valor($datos, "id_tms_servicio", 0)),
            "folio" => $this->texto($datos, "folio"),
            "referencia_externa" => $this->texto($datos, "referencia_externa")
          )
        ));
      }

      $detalle = $this->detalleServicioTicket($db, intval($servicio["id_tms_servicio"]));
      $costo = $this->costoServicioTicket($db, intval($servicio["id_tms_servicio"]));
      $conteos = $this->conteosServicioTicket($db, intval($servicio["id_tms_servicio"]));
      $ticket = $this->textoTicketServicio($servicio, $detalle, $costo, $conteos);

      return $this->respuesta(false, "success", "Comprobante TMS generado", array(
        "ticket_texto" => $ticket,
        "servicio" => $servicio,
        "detalle" => $detalle,
        "costo" => $costo,
        "conteos" => $conteos,
        "configuracion" => array(
          "nombre_servicio_cliente" => "ARTIANI Entregas",
          "titulo" => "Comprobante logistico",
          "ticket_ancho_mm" => 80,
          "ticket_columnas" => 42,
          "impresion_modo" => "navegador",
          "leyenda_separacion" => "Este comprobante corresponde unicamente al servicio logistico de entrega.",
          "leyenda_garantia" => "La garantia de producto se atiende conforme a las politicas del local."
        ),
        "contrato" => $this->contratoDominio(),
        "no_escritura_bd" => true
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
   * Fecha: 2026-07-28
   * Proposito: adaptar una solicitud operativa nacida en POS al contrato logistico TMS sin escribir BD.
   * Impacto: TMS Delivery; permite validar entregas de mostrador como servicio separado de cualquier operacion comercial.
   * Contrato: dry-run; no crea servicio, no confirma operaciones comerciales, no cobra productos y no mueve inventario.
   */
  public function servicioDesdePosDryRun($datos = array()) {
    $normalizado = $datos;
    $normalizado["solicitado_por_modulo"] = "pos";

    $tipoOrigen = $this->texto($datos, "solicitado_por_tipo", "solicitud_pos");
    if ($tipoOrigen === "pos") {
      $tipoOrigen = "solicitud_pos";
    }
    $normalizado["solicitado_por_tipo"] = $tipoOrigen;

    $referencia = $this->texto($datos, "referencia_externa", $this->texto($datos, "referencia_operativa"));
    $normalizado["referencia_externa"] = $referencia;

    if ($this->texto($normalizado, "tipo_servicio") === "") {
      $normalizado["tipo_servicio"] = "entrega_programada";
    }
    if ($this->texto($normalizado, "prioridad") === "") {
      $normalizado["prioridad"] = "normal";
    }
    if ($this->texto($normalizado, "estatus_cobro") === "") {
      $normalizado["estatus_cobro"] = "por_cobrar";
    }

    $validacion = $this->servicioDryRun($normalizado);
    $depurar = isset($validacion["depurar"]) && is_array($validacion["depurar"]) ? $validacion["depurar"] : array();
    $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
    $advertencias = isset($depurar["advertencias"]) && is_array($depurar["advertencias"]) ? $depurar["advertencias"] : array();

    if (!in_array($tipoOrigen, array("solicitud_pos", "solicitud_mostrador", "servicio_cliente"), true)) {
      $bloqueos[] = "Tipo de solicitud POS no soportado para TMS";
    }
    if ($referencia === "" && intval($this->valor($datos, "solicitado_por_id", 0)) <= 0) {
      $advertencias[] = "Sin referencia operativa; TMS puede continuar como servicio logistico independiente";
    }
    if ($this->texto($datos, "venta_estatus") !== "") {
      $advertencias[] = "TMS ignora datos comerciales; solo conserva informacion logistica";
    }

    $puedeGuardarFuturo = empty($bloqueos);
    $depurar["puede_guardar_futuro"] = $puedeGuardarFuturo;
    $depurar["bloqueos"] = array_values(array_unique($bloqueos));
    $depurar["advertencias"] = array_values(array_unique($advertencias));
    $depurar["origen_pos"] = array(
      "solicitado_por_modulo" => "pos",
      "solicitado_por_tipo" => $tipoOrigen,
      "solicitado_por_id" => intval($this->valor($datos, "solicitado_por_id", 0)),
      "referencia_externa" => $referencia
    );
    $depurar["reglas_pos_tms"] = array(
      "pos_solo_captura_solicitud_logistica" => true,
      "tms_solo_compromiso_logistico" => true,
      "importe_logistico_separado" => true,
      "fallo_entrega_solo_cierra_o_reprograma_servicio" => true,
      "sin_garantia_o_postventa_en_tms" => true
    );

    return $this->respuesta(false, $puedeGuardarFuturo ? "success" : "warning", $puedeGuardarFuturo ? "Solicitud POS -> TMS valida en dry-run" : "Solicitud POS -> TMS bloqueada en dry-run", $depurar);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: crear un folio TMS real cuando el esquema ya este aplicado.
   * Impacto: TMS Delivery; crea solo servicio logistico, costo, detalle y evento inicial.
   * Contrato: escritura transaccional; solo crea/actualiza TMS, no decide operaciones comerciales ni mueve inventario.
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
        ":motivo" => $this->texto($datos, "motivo_logistico", "servicio_inicial"),
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
   * Proposito: operar cambios de estado TMS una vez que exista el esquema.
   * Impacto: TMS Delivery; actualiza solo el servicio logistico y registra evento.
   * Contrato: escritura transaccional sobre TMS; no toca operaciones comerciales, inventario ni postventa.
   */
  public function aplicarAccionServicio($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para operar TMS", array("conexion_mysql" => false));
      }
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema TMS pendiente; no se puede operar servicio", array(
          "schema_pendiente" => true,
          "tablas_requeridas" => array($this->tabla_servicios, $this->tabla_detalle, $this->tabla_costos, $this->tabla_eventos, $this->tabla_evidencias),
          "regla" => "Aplicar DDL TMS con autorizacion antes de habilitar cambios de estado."
        ));
      }

      $idServicio = intval($this->valor($datos, "id_tms_servicio", 0));
      $accion = $this->texto($datos, "accion");
      $contrato = $this->accionesTmsContratoArray();
      if ($idServicio <= 0 || !isset($contrato[$accion])) {
        return $this->respuesta(true, "warning", "Accion TMS invalida", array(
          "id_tms_servicio" => $idServicio,
          "accion" => $accion,
          "acciones_validas" => array_keys($contrato)
        ));
      }

      $bloqueos = $this->validarRequisitosAccion($datos, $contrato[$accion]["requiere"]);
      if (!empty($bloqueos)) {
        return $this->respuesta(true, "warning", "Accion TMS incompleta", array("bloqueos" => $bloqueos, "accion" => $accion));
      }

      $db->beginTransaction();
      $stmt = $db->prepare("SELECT * FROM {$this->tabla_servicios} WHERE id_tms_servicio=:id FOR UPDATE");
      $stmt->execute(array(":id" => $idServicio));
      $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$servicio) {
        $db->rollBack();
        return $this->respuesta(true, "warning", "Servicio TMS no encontrado", array("id_tms_servicio" => $idServicio));
      }

      $antes = array(
        "estatus_servicio" => $servicio["estatus_servicio"],
        "resultado_logistico" => $servicio["resultado_logistico"]
      );
      $actualizacion = $this->actualizacionPorAccion($accion, $datos, $servicio);
      $sets = $actualizacion["sets"];
      $params = $actualizacion["params"];
      $params[":id"] = $idServicio;

      $sql = "UPDATE {$this->tabla_servicios} SET " . implode(", ", $sets) . ", fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_tms_servicio=:id";
      $stmtUpdate = $db->prepare($sql);
      $stmtUpdate->execute($params);

      $despues = array(
        "estatus_servicio" => isset($params[":estatus_servicio"]) ? $params[":estatus_servicio"] : $servicio["estatus_servicio"],
        "resultado_logistico" => isset($params[":resultado_logistico"]) ? $params[":resultado_logistico"] : $servicio["resultado_logistico"]
      );
      $this->registrarEvento($db, $idServicio, $accion, $antes["estatus_servicio"], $despues["estatus_servicio"], $this->texto($datos, "comentario", $this->texto($datos, "motivo")), $idUsuario, array(
        "antes" => $antes,
        "despues" => $despues,
        "contrato" => $this->contratoDominio()
      ));

      $db->commit();

      return $this->respuesta(false, "success", "Accion TMS aplicada", array(
        "id_tms_servicio" => $idServicio,
        "accion" => $accion,
        "antes" => $antes,
        "despues" => $despues,
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
   * Proposito: listar evidencias ligadas a un folio TMS.
   * Impacto: TMS Delivery; permite trazabilidad operativa sin tocar Ventas ni garantias.
   * Contrato: read-only; si falta esquema devuelve lista vacia controlada.
   */
  public function listarEvidencias($idServicio) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para consultar evidencias TMS", array("conexion_mysql" => false));
      }
      if (!$this->tablaExiste($db, $this->tabla_evidencias)) {
        return $this->respuesta(false, "info", "Esquema TMS pendiente; no hay evidencias para listar", array(
          "schema_pendiente" => true,
          "evidencias" => array()
        ));
      }

      $stmt = $db->prepare("SELECT id_tms_evidencia, id_tms_servicio, tipo_evidencia, ruta,
          nombre_original, descripcion, payload_json, estatus, creado_por, fecha_registro, fecha_cancelacion
        FROM {$this->tabla_evidencias}
        WHERE id_tms_servicio=:servicio
        ORDER BY id_tms_evidencia DESC");
      $stmt->execute(array(":servicio" => intval($idServicio)));

      return $this->respuesta(false, "success", "Evidencias TMS consultadas", array(
        "schema_pendiente" => false,
        "evidencias" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: consultar indicadores basicos TMS Delivery.
   * Impacto: TMS Delivery; mide servicio logistico sin recalcular operaciones comerciales.
   * Contrato: read-only; si falta esquema devuelve metricas en cero con schema_pendiente=true.
   */
  public function resumenReportes($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para reportes TMS", array("conexion_mysql" => false));
      }
      if (!$this->tablaExiste($db, $this->tabla_servicios)) {
        return $this->respuesta(false, "info", "Esquema TMS pendiente; reportes sin datos reales", array(
          "schema_pendiente" => true,
          "kpis" => $this->kpisTmsVacios(),
          "por_tipo" => array(),
          "por_resultado" => array(),
          "por_zona" => array(),
          "contrato" => $this->contratoDominio()
        ));
      }

      $where = array("s.estatus <> 'cancelado'");
      $params = array();
      $desde = $this->texto($filtros, "desde");
      $hasta = $this->texto($filtros, "hasta");
      if ($desde !== "") {
        $where[] = "DATE(s.fecha_solicitud) >= :desde";
        $params[":desde"] = $desde;
      }
      if ($hasta !== "") {
        $where[] = "DATE(s.fecha_solicitud) <= :hasta";
        $params[":hasta"] = $hasta;
      }
      $whereSql = implode(" AND ", $where);

      $kpis = $this->kpisTmsVacios();
      $stmt = $db->prepare("SELECT
          COUNT(*) total,
          SUM(CASE WHEN s.resultado_logistico='completa' THEN 1 ELSE 0 END) completas,
          SUM(CASE WHEN s.tipo_servicio='entrega_express' THEN 1 ELSE 0 END) express,
          SUM(CASE WHEN s.estatus_servicio='no_entregada' OR s.resultado_logistico='sin_entrega' THEN 1 ELSE 0 END) no_entregadas,
          SUM(CASE WHEN s.estatus_servicio='pendiente_cliente' OR s.resultado_logistico='cliente_recogera' THEN 1 ELSE 0 END) pendiente_cliente,
          SUM(CASE WHEN s.estatus_cobro='bonificada' OR s.estatus_cobro='incluida_cortesia' THEN 1 ELSE 0 END) bonificadas,
          COALESCE(SUM(c.precio_cobrado), 0) ingresos_logisticos,
          COALESCE(SUM(c.costo_real), 0) costo_real,
          COALESCE(SUM(CASE WHEN s.estatus_cobro='bonificada' OR s.estatus_cobro='incluida_cortesia' THEN c.precio_cobrado ELSE 0 END), 0) monto_bonificado,
          AVG(TIMESTAMPDIFF(MINUTE, s.fecha_solicitud, s.fecha_cierre)) tiempo_promedio_minutos
        FROM {$this->tabla_servicios} s
        LEFT JOIN {$this->tabla_costos} c ON c.id_tms_servicio=s.id_tms_servicio AND c.estatus='activo'
        WHERE {$whereSql}");
      $stmt->execute($params);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        foreach ($kpis as $key => $value) {
          $kpis[$key] = isset($row[$key]) ? (float) $row[$key] : $value;
        }
      }

      return $this->respuesta(false, "success", "Reportes TMS consultados", array(
        "schema_pendiente" => false,
        "kpis" => $kpis,
        "por_tipo" => $this->agregadoSimple($db, "s.tipo_servicio", $whereSql, $params),
        "por_resultado" => $this->agregadoSimple($db, "s.resultado_logistico", $whereSql, $params),
        "por_zona" => $this->agregadoSimple($db, "COALESCE(NULLIF(s.zona_snapshot, ''), 'Sin zona')", $whereSql, $params, 10),
        "filtros" => $filtros,
        "contrato" => $this->contratoDominio()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: registrar evidencia operativa para un servicio TMS.
   * Impacto: TMS Delivery; agrega trazabilidad sin resolver postventa ni tocar operaciones comerciales.
   * Contrato: escritura transaccional sobre TMS; no sube archivo fisico en esta fase.
   */
  public function registrarEvidencia($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para registrar evidencia TMS", array("conexion_mysql" => false));
      }
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema TMS pendiente; no se puede registrar evidencia", array(
          "schema_pendiente" => true,
          "tablas_requeridas" => array($this->tabla_servicios, $this->tabla_evidencias, $this->tabla_eventos),
          "regla" => "Aplicar DDL TMS con autorizacion antes de habilitar evidencias."
        ));
      }

      $idServicio = intval($this->valor($datos, "id_tms_servicio", 0));
      $tipo = $this->texto($datos, "tipo_evidencia", "nota");
      $descripcion = $this->texto($datos, "descripcion");
      $tiposValidos = array("foto", "firma", "nota", "comprobante", "ubicacion", "chat_snapshot");
      if ($idServicio <= 0 || !in_array($tipo, $tiposValidos, true) || $descripcion === "") {
        return $this->respuesta(true, "warning", "Evidencia TMS incompleta", array(
          "bloqueos" => array("Indica servicio, tipo valido y descripcion"),
          "tipos_validos" => $tiposValidos
        ));
      }

      $db->beginTransaction();
      $servicio = $this->consultarServicioParaUpdate($db, $idServicio);
      if (!$servicio) {
        $db->rollBack();
        return $this->respuesta(true, "warning", "Servicio TMS no encontrado", array("id_tms_servicio" => $idServicio));
      }

      $payload = array(
        "latitud" => $this->texto($datos, "latitud"),
        "longitud" => $this->texto($datos, "longitud"),
        "capturado_desde" => $this->texto($datos, "capturado_desde", "manual"),
        "contrato" => $this->contratoDominio()
      );

      $stmt = $db->prepare("INSERT INTO {$this->tabla_evidencias}
        (id_tms_servicio, tipo_evidencia, ruta, nombre_original, descripcion, payload_json, estatus, creado_por)
        VALUES (:servicio, :tipo, :ruta, :nombre, :descripcion, :payload, 'activa', :usuario)");
      $stmt->execute(array(
        ":servicio" => $idServicio,
        ":tipo" => $tipo,
        ":ruta" => $this->texto($datos, "ruta"),
        ":nombre" => $this->texto($datos, "nombre_original"),
        ":descripcion" => $descripcion,
        ":payload" => json_encode($payload),
        ":usuario" => intval($idUsuario) > 0 ? intval($idUsuario) : null
      ));
      $idEvidencia = intval($db->lastInsertId());

      $this->registrarEvento($db, $idServicio, "evidencia_registrada", $servicio["estatus_servicio"], $servicio["estatus_servicio"], $descripcion, $idUsuario, array(
        "id_tms_evidencia" => $idEvidencia,
        "tipo_evidencia" => $tipo
      ));

      $db->commit();

      return $this->respuesta(false, "success", "Evidencia TMS registrada", array(
        "id_tms_evidencia" => $idEvidencia,
        "id_tms_servicio" => $idServicio,
        "tipo_evidencia" => $tipo,
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
   * Proposito: cancelar logicamente una evidencia TMS.
   * Impacto: TMS Delivery; conserva historial operativo sin borrar evidencia.
   * Contrato: baja logica; no elimina archivos fisicos en esta fase.
   */
  public function cancelarEvidencia($datos = array(), $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para cancelar evidencia TMS", array("conexion_mysql" => false));
      }
      if (!$this->schemaCompleto($db)) {
        return $this->respuesta(true, "warning", "Esquema TMS pendiente; no se puede cancelar evidencia", array("schema_pendiente" => true));
      }

      $idEvidencia = intval($this->valor($datos, "id_tms_evidencia", 0));
      $motivo = $this->texto($datos, "motivo");
      if ($idEvidencia <= 0 || $motivo === "") {
        return $this->respuesta(true, "warning", "Captura evidencia y motivo de cancelacion", array("id_tms_evidencia" => $idEvidencia));
      }

      $db->beginTransaction();
      $stmt = $db->prepare("SELECT * FROM {$this->tabla_evidencias} WHERE id_tms_evidencia=:id FOR UPDATE");
      $stmt->execute(array(":id" => $idEvidencia));
      $evidencia = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$evidencia) {
        $db->rollBack();
        return $this->respuesta(true, "warning", "Evidencia TMS no encontrada", array("id_tms_evidencia" => $idEvidencia));
      }

      $stmtUpdate = $db->prepare("UPDATE {$this->tabla_evidencias}
        SET estatus='cancelada', fecha_cancelacion=CURRENT_TIMESTAMP
        WHERE id_tms_evidencia=:id");
      $stmtUpdate->execute(array(":id" => $idEvidencia));

      $this->registrarEvento($db, intval($evidencia["id_tms_servicio"]), "evidencia_cancelada", null, null, $motivo, $idUsuario, array(
        "id_tms_evidencia" => $idEvidencia,
        "tipo_evidencia" => $evidencia["tipo_evidencia"]
      ));

      $db->commit();

      return $this->respuesta(false, "success", "Evidencia TMS cancelada", array(
        "id_tms_evidencia" => $idEvidencia,
        "baja_logica" => true
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
    $acciones = array();
    foreach ($this->accionesTmsContratoArray() as $accion => $contrato) {
      $acciones[] = array(
        "accion" => $accion,
        "permiso" => $contrato["permiso"],
        "requiere" => $contrato["requiere"]
      );
    }
    return $this->respuesta(false, "success", "Contrato de acciones TMS consultado", array(
      "acciones" => $acciones,
      "regla" => "Estas acciones modificaran solo TMS cuando se implementen; no tocan operaciones comerciales ni postventa."
    ));
  }

  private function accionesTmsContratoArray() {
    return array(
      "programar" => array("permiso" => "tms.programar", "requiere" => array("id_tms_servicio", "fecha_programada", "ventana_inicio", "ventana_fin")),
      "asignar_responsable" => array("permiso" => "tms.programar", "requiere" => array("id_tms_servicio", "responsable_asignado")),
      "marcar_lista_salida" => array("permiso" => "tms.operar", "requiere" => array("id_tms_servicio")),
      "iniciar_ruta" => array("permiso" => "tms.operar", "requiere" => array("id_tms_servicio")),
      "entregar" => array("permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "resultado_logistico", "evidencia_o_comentario")),
      "no_entregada" => array("permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "motivo")),
      "pendiente_cliente" => array("permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "motivo")),
      "reprogramar" => array("permiso" => "tms.programar", "requiere" => array("id_tms_servicio", "fecha_programada", "ventana_inicio", "ventana_fin", "motivo")),
      "cancelar_servicio" => array("permiso" => "tms.operar", "requiere" => array("id_tms_servicio", "motivo"))
    );
  }

  private function validarRequisitosAccion($datos, $requisitos) {
    $bloqueos = array();
    foreach ($requisitos as $campo) {
      if ($campo === "evidencia_o_comentario") {
        if ($this->texto($datos, "comentario") === "" && $this->texto($datos, "evidencia") === "") {
          $bloqueos[] = "Captura comentario o evidencia para cerrar la entrega";
        }
        continue;
      }
      if ($campo === "id_tms_servicio" || $campo === "responsable_asignado") {
        if (intval($this->valor($datos, $campo, 0)) <= 0) {
          $bloqueos[] = "Falta " . $campo;
        }
        continue;
      }
      if ($this->texto($datos, $campo) === "") {
        $bloqueos[] = "Falta " . $campo;
      }
    }
    return $bloqueos;
  }

  private function actualizacionPorAccion($accion, $datos, $servicio) {
    $sets = array();
    $params = array();

    if ($accion === "programar" || $accion === "reprogramar") {
      $sets[] = "estatus_servicio=:estatus_servicio";
      $sets[] = "resultado_logistico=:resultado_logistico";
      $sets[] = "fecha_programada=:fecha_programada";
      $sets[] = "ventana_inicio=:ventana_inicio";
      $sets[] = "ventana_fin=:ventana_fin";
      $params[":estatus_servicio"] = $accion === "programar" ? "programada" : "reprogramada";
      $params[":resultado_logistico"] = $accion === "programar" ? $servicio["resultado_logistico"] : "nuevo_intento_requerido";
      $params[":fecha_programada"] = $this->nullSiVacio($this->texto($datos, "fecha_programada"));
      $params[":ventana_inicio"] = $this->nullSiVacio($this->texto($datos, "ventana_inicio"));
      $params[":ventana_fin"] = $this->nullSiVacio($this->texto($datos, "ventana_fin"));
      return array("sets" => $sets, "params" => $params);
    }

    if ($accion === "asignar_responsable") {
      $sets[] = "responsable_asignado=:responsable_asignado";
      $params[":responsable_asignado"] = intval($this->valor($datos, "responsable_asignado", 0));
      return array("sets" => $sets, "params" => $params);
    }

    $mapa = array(
      "marcar_lista_salida" => array("estatus" => "lista_para_salida", "resultado" => $servicio["resultado_logistico"], "fecha_cierre" => false, "fecha_salida" => false),
      "iniciar_ruta" => array("estatus" => "en_ruta", "resultado" => $servicio["resultado_logistico"], "fecha_cierre" => false, "fecha_salida" => true),
      "entregar" => array("estatus" => "entregada", "resultado" => $this->texto($datos, "resultado_logistico", "completa"), "fecha_cierre" => true, "fecha_salida" => false),
      "no_entregada" => array("estatus" => "no_entregada", "resultado" => "sin_entrega", "fecha_cierre" => true, "fecha_salida" => false),
      "pendiente_cliente" => array("estatus" => "pendiente_cliente", "resultado" => "cliente_recogera", "fecha_cierre" => false, "fecha_salida" => false),
      "cancelar_servicio" => array("estatus" => "cancelada", "resultado" => "cerrada_sin_entrega", "fecha_cierre" => true, "fecha_salida" => false)
    );

    $destino = isset($mapa[$accion]) ? $mapa[$accion] : array("estatus" => $servicio["estatus_servicio"], "resultado" => $servicio["resultado_logistico"], "fecha_cierre" => false, "fecha_salida" => false);
    $sets[] = "estatus_servicio=:estatus_servicio";
    $sets[] = "resultado_logistico=:resultado_logistico";
    $params[":estatus_servicio"] = $destino["estatus"];
    $params[":resultado_logistico"] = $destino["resultado"];
    if (!empty($destino["fecha_salida"])) {
      $sets[] = "fecha_salida=COALESCE(fecha_salida, CURRENT_TIMESTAMP)";
    }
    if (!empty($destino["fecha_cierre"])) {
      $sets[] = "fecha_cierre=CURRENT_TIMESTAMP";
    }
    if ($accion === "cancelar_servicio") {
      $sets[] = "estatus='cancelado'";
    }

    return array("sets" => $sets, "params" => $params);
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
      "tms_solo_compromiso_logistico" => true,
      "acciones_base" => array("recoger", "preparar", "llevar", "evidenciar", "cerrar", "reprogramar"),
      "sin_estados_comerciales" => true,
      "sin_garantias_o_postventa" => true,
      "no_mueve_inventario_por_si_mismo" => true,
      "resultado_no_entregado_solo_afecta_tms" => true
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

  private function consultarServicioParaUpdate($db, $idServicio) {
    $stmt = $db->prepare("SELECT * FROM {$this->tabla_servicios} WHERE id_tms_servicio=:id FOR UPDATE");
    $stmt->execute(array(":id" => intval($idServicio)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function buscarServicioTicket($db, $datos) {
    $where = array();
    $params = array();
    $idServicio = intval($this->valor($datos, "id_tms_servicio", 0));
    $folio = $this->texto($datos, "folio");
    $referencia = $this->texto($datos, "referencia_externa");

    if ($idServicio > 0) {
      $where[] = "s.id_tms_servicio=:id";
      $params[":id"] = $idServicio;
    } elseif ($folio !== "") {
      $where[] = "s.folio=:folio";
      $params[":folio"] = $folio;
    } elseif ($referencia !== "") {
      $where[] = "s.referencia_externa=:referencia";
      $params[":referencia"] = $referencia;
    } else {
      return null;
    }

    $stmt = $db->prepare("SELECT s.*
      FROM {$this->tabla_servicios} s
      WHERE " . implode(" AND ", $where) . "
      LIMIT 1");
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  private function detalleServicioTicket($db, $idServicio) {
    $stmt = $db->prepare("SELECT referencia_item_origen, cantidad, descripcion_snapshot,
        requiere_cuidado_especial, observaciones
      FROM {$this->tabla_detalle}
      WHERE id_tms_servicio=:id AND estatus='activo'
      ORDER BY id_tms_servicio_detalle ASC");
    $stmt->execute(array(":id" => intval($idServicio)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function costoServicioTicket($db, $idServicio) {
    $stmt = $db->prepare("SELECT precio_cobrado, costo_estimado, costo_real, metodo_cobro,
        motivo_bonificacion, estatus
      FROM {$this->tabla_costos}
      WHERE id_tms_servicio=:id AND estatus='activo'
      LIMIT 1");
    $stmt->execute(array(":id" => intval($idServicio)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: array("precio_cobrado" => 0, "costo_estimado" => 0, "costo_real" => null, "metodo_cobro" => "no_aplica", "motivo_bonificacion" => "", "estatus" => "sin_costo");
  }

  private function conteosServicioTicket($db, $idServicio) {
    $stmtEventos = $db->prepare("SELECT COUNT(*) total FROM {$this->tabla_eventos} WHERE id_tms_servicio=:id");
    $stmtEventos->execute(array(":id" => intval($idServicio)));
    $stmtEvidencias = $db->prepare("SELECT COUNT(*) total FROM {$this->tabla_evidencias} WHERE id_tms_servicio=:id AND estatus='activa'");
    $stmtEvidencias->execute(array(":id" => intval($idServicio)));
    return array(
      "eventos" => intval($stmtEventos->fetch(PDO::FETCH_ASSOC)["total"]),
      "evidencias" => intval($stmtEvidencias->fetch(PDO::FETCH_ASSOC)["total"])
    );
  }

  private function textoTicketServicio($servicio, $detalle, $costo, $conteos) {
    $lineas = array();
    $lineas[] = $this->centrarTicket("ARTIANI ENTREGAS");
    $lineas[] = $this->centrarTicket("COMPROBANTE LOGISTICO");
    $lineas[] = $this->separadorTicket();
    $lineas[] = "Folio TMS: " . $servicio["folio"];
    $lineas[] = "Referencia: " . $this->textoTicket($servicio["referencia_externa"]);
    $lineas[] = "Fecha solicitud: " . $this->fechaTicket($servicio["fecha_solicitud"]);
    $lineas[] = "Fecha programada: " . $this->fechaTicket($servicio["fecha_programada"], false);
    $lineas[] = "Ventana: " . $this->horaTicket($servicio["ventana_inicio"]) . " - " . $this->horaTicket($servicio["ventana_fin"]);
    $lineas[] = $this->separadorTicket();
    $lineas[] = "CLIENTE";
    $lineas[] = $this->textoTicket($servicio["cliente_nombre_snapshot"]);
    $lineas[] = "Contacto: " . $this->textoTicket($servicio["cliente_contacto_snapshot"]);
    $lineas[] = "Zona: " . $this->textoTicket($servicio["zona_snapshot"]);
    $lineas[] = "";
    $lineas[] = "DIRECCION";
    foreach ($this->envolverTicket($this->textoTicket($servicio["direccion_snapshot"]), 42) as $linea) {
      $lineas[] = $linea;
    }
    $lineas[] = $this->separadorTicket();
    $lineas[] = "SERVICIO";
    $lineas[] = "Tipo: " . $this->humanizarTicket($servicio["tipo_servicio"]);
    $lineas[] = "Prioridad: " . $this->humanizarTicket($servicio["prioridad"]);
    $lineas[] = "Estado: " . $this->humanizarTicket($servicio["estatus_servicio"]);
    $lineas[] = "Resultado: " . $this->humanizarTicket($servicio["resultado_logistico"]);
    $lineas[] = $this->separadorTicket();
    $lineas[] = "COBRO LOGISTICO";
    $lineas[] = $this->parTicket("Importe entrega:", "$" . number_format(floatval($costo["precio_cobrado"]), 2));
    $lineas[] = $this->parTicket("Estatus cobro:", $this->humanizarTicket($servicio["estatus_cobro"]));
    $lineas[] = $this->parTicket("Metodo:", $this->humanizarTicket($costo["metodo_cobro"]));
    $lineas[] = $this->separadorTicket();
    $lineas[] = "PAQUETE / REFERENCIA FISICA";
    if (empty($detalle)) {
      $lineas[] = "Sin detalle capturado.";
    }
    foreach ($detalle as $item) {
      $lineas[] = number_format(floatval($item["cantidad"]), 0) . " x " . $this->textoTicket($item["descripcion_snapshot"]);
      $lineas[] = "Cuidado especial: " . (intval($item["requiere_cuidado_especial"]) === 1 ? "Si" : "No");
    }
    $lineas[] = $this->separadorTicket();
    $lineas[] = "EVIDENCIA";
    $lineas[] = "Eventos registrados: " . intval($conteos["eventos"]);
    $lineas[] = "Evidencias registradas: " . intval($conteos["evidencias"]);
    $lineas[] = $this->separadorTicket();
    foreach ($this->envolverTicket("Este comprobante corresponde unicamente al servicio logistico de entrega.", 42) as $linea) {
      $lineas[] = $linea;
    }
    $lineas[] = "";
    foreach ($this->envolverTicket("No modifica garantias, cambios, devoluciones, pagos ni condiciones de los productos.", 42) as $linea) {
      $lineas[] = $linea;
    }
    $lineas[] = "";
    foreach ($this->envolverTicket("La garantia de producto se atiende conforme a las politicas del local.", 42) as $linea) {
      $lineas[] = $linea;
    }
    $lineas[] = $this->separadorTicket();
    $lineas[] = $this->centrarTicket("Gracias por confiar en ARTIANI.");
    return implode("\n", $lineas);
  }

  private function kpisTmsVacios() {
    return array(
      "total" => 0,
      "completas" => 0,
      "express" => 0,
      "no_entregadas" => 0,
      "pendiente_cliente" => 0,
      "bonificadas" => 0,
      "ingresos_logisticos" => 0,
      "costo_real" => 0,
      "monto_bonificado" => 0,
      "tiempo_promedio_minutos" => 0
    );
  }

  private function agregadoSimple($db, $campo, $whereSql, $params, $limite = 20) {
    $stmt = $db->prepare("SELECT {$campo} etiqueta, COUNT(*) total
      FROM {$this->tabla_servicios} s
      WHERE {$whereSql}
      GROUP BY etiqueta
      ORDER BY total DESC
      LIMIT " . intval($limite));
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

  private function separadorTicket() {
    return str_repeat("-", 42);
  }

  private function centrarTicket($texto) {
    $texto = substr($this->textoTicket($texto), 0, 42);
    $espacios = max(0, intval(floor((42 - strlen($texto)) / 2)));
    return str_repeat(" ", $espacios) . $texto;
  }

  private function parTicket($izquierda, $derecha) {
    $izquierda = $this->textoTicket($izquierda);
    $derecha = $this->textoTicket($derecha);
    $espacios = max(1, 42 - strlen($izquierda) - strlen($derecha));
    return substr($izquierda . str_repeat(" ", $espacios) . $derecha, 0, 42);
  }

  private function textoTicket($valor) {
    $valor = trim((string) $valor);
    $valor = str_replace(array("\r", "\n", "\t"), " ", $valor);
    return $valor === "" ? "-" : $valor;
  }

  private function humanizarTicket($valor) {
    $valor = str_replace("_", " ", $this->textoTicket($valor));
    return ucfirst($valor);
  }

  private function fechaTicket($valor, $conHora = true) {
    $valor = trim((string) $valor);
    if ($valor === "" || $valor === "0000-00-00" || $valor === "0000-00-00 00:00:00") {
      return "-";
    }
    $ts = strtotime($valor);
    if (!$ts) {
      return $valor;
    }
    return $conHora ? date("d/m/Y H:i", $ts) : date("d/m/Y", $ts);
  }

  private function horaTicket($valor) {
    $valor = trim((string) $valor);
    if ($valor === "") {
      return "-";
    }
    $ts = strtotime($valor);
    return $ts ? date("H:i", $ts) : substr($valor, 0, 5);
  }

  private function envolverTicket($texto, $ancho = 42) {
    $texto = $this->textoTicket($texto);
    if ($texto === "-") {
      return array("-");
    }
    $lineas = explode("\n", wordwrap($texto, $ancho, "\n", true));
    return array_values(array_filter($lineas, function ($linea) {
      return trim($linea) !== "";
    }));
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
