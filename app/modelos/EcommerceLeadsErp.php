<?php

class EcommerceLeadsErp extends CRUD {

  private $eventosPermitidos = array("cart_update", "cart_view", "quantity_change", "quote_dryrun", "quote_preflight", "open_whatsapp", "contact_submit", "facturacion_submit", "abandono_estimado");
  private $estatusPermitidos = array("anonimo_activo", "contacto_pendiente", "whatsapp_generado", "whatsapp_abierto", "abandonado", "en_seguimiento", "convertido", "descartado");

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: exponer contrato de Ecommerce Leads/Carritos para frontend e integraciones internas.
   * Impacto: separa intencion comercial de analytics anonimo y de ventas reales.
   * Contrato: solo lectura; no escribe BD ni expone datos internos sensibles.
   */
  public function contratoFrontend() {
    return $this->respuesta(false, "success", "Contrato Ecommerce Leads / Carritos", array(
      "version" => "fase3-leads-carritos-2026-08-30",
      "estado" => $this->persistenciaPublicaActiva() ? "persistencia_publica_activa" : "preflight_listo_para_persistencia",
      "endpoints_publicos" => array(
        array("metodo" => "POST", "ruta" => "/ecommercePublico/carrito_sincronizar", "uso" => "Enviar snapshot completo del carrito cuando cambia o entra a /carrito."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/carrito_evento", "uso" => "Registrar evento comercial puntual ligado a session_id."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/intento_pedido", "uso" => "Capturar intento de pedido o WhatsApp con snapshot y mensaje generado."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/contacto_registrar", "uso" => "Enlazar carrito anonimo con contacto escrito explicitamente por el cliente."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/facturacion_solicitud_registrar", "uso" => "Registrar solicitud fiscal ligada a session_id/carrito sin facturar automaticamente.")
      ),
      "endpoints_internos" => array(
        "GET /ecommercePublico/carritos_dashboard_erp",
        "GET /ecommercePublico/carrito_detalle_erp/{id}",
        "POST /ecommercePublico/carrito_accion_plan_erp"
      ),
      "estados" => $this->estatusPermitidos,
      "eventos_permitidos" => $this->eventosPermitidos,
      "guardrails" => $this->guardrails(false)
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: validar/sincronizar snapshot comercial de carrito ecommerce.
   * Impacto: conserva productos, cantidades y precios snapshot sin crear pedido ni tocar inventario.
   * Contrato: POST publico; persiste solo si existe esquema y ECOMMERCE_LEADS_PUBLICO=true.
   */
  public function carritoSincronizar($datos = array()) {
    $normalizado = $this->normalizarPayload($datos, "cart_update");
    return $this->resolverPersistenciaPublica($normalizado, "carrito_sincronizar");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: validar evento comercial de carrito ecommerce.
   * Impacto: permite rastrear etapa comercial sin contaminar analytics anonimo.
   * Contrato: POST publico; no acepta datos personales fuera del bloque contacto.
   */
  public function carritoEvento($datos = array()) {
    $tipo = $this->limpiarToken($this->valor($datos, "tipo_evento", "cart_update"), 70);
    $normalizado = $this->normalizarPayload($datos, $tipo);
    return $this->resolverPersistenciaPublica($normalizado, "carrito_evento");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: validar intento de pedido/WhatsApp con snapshot completo antes de que el cliente envie mensaje.
   * Impacto: habilita seguimiento aun si el cliente borra el texto de WhatsApp o no lo manda.
   * Contrato: POST publico; no crea pedido, venta, cotizacion real ni movimiento de inventario.
   */
  public function intentoPedido($datos = array()) {
    $tipo = $this->limpiarToken($this->valor($datos, "tipo_intento", "open_whatsapp"), 70);
    $normalizado = $this->normalizarPayload($datos, $tipo);
    return $this->resolverPersistenciaPublica($normalizado, "intento_pedido");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: validar contacto escrito por el cliente y enlazarlo al carrito anonimo por session_id.
   * Impacto: prepara seguimiento comercial respetando privacidad.
   * Contrato: POST publico; solo usa datos dentro de contacto y conserva session_id como hash.
   */
  public function contactoRegistrar($datos = array()) {
    $normalizado = $this->normalizarPayload($datos, "contact_submit");
    return $this->resolverPersistenciaPublica($normalizado, "contacto_registrar");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: validar solicitud publica de facturacion ligada a carrito/intencion comercial.
   * Impacto: separa solicitud fiscal de emision real y de ventas.
   * Contrato: POST publico; no emite factura ni inventa cliente.
   */
  public function facturacionSolicitudRegistrar($datos = array()) {
    $normalizado = $this->normalizarPayload($datos, "facturacion_submit");
    $normalizado["solicito_facturacion"] = true;
    return $this->resolverPersistenciaPublica($normalizado, "facturacion_solicitud_registrar");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: consultar bandeja interna de carritos/leads ecommerce.
   * Impacto: permite seguimiento operativo sin consultar analytics ni ventas.
   * Contrato: GET protegido por permiso; solo lectura.
   */
  public function dashboardInterno($filtros = array()) {
    $db = $this->getConexion();
    $tablas = $this->tablasDisponibles($db);
    if (!$db || empty($tablas["carritos"])) {
      return $this->respuesta(false, "info", "Bandeja Ecommerce Leads aun sin esquema", array(
        "configurado" => false,
        "items" => array(),
        "resumen" => $this->resumenVacio(),
        "tablas" => $tablas,
        "guardrails" => $this->guardrails(false)
      ));
    }
    try {
      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $limite = max(5, min(80, intval($this->valor($filtros, "limite", 25))));
      $offset = ($pagina - 1) * $limite;
      $where = array("1=1");
      $params = array();
      $estatus = $this->limpiarToken($this->valor($filtros, "estatus", ""), 40);
      if ($estatus !== "" && in_array($estatus, $this->estatusPermitidos, true)) {
        $where[] = "estatus=:estatus";
        $params[":estatus"] = $estatus;
      }
      $q = $this->limpiarTexto($this->valor($filtros, "q", ""), 120);
      if ($q !== "") {
        $where[] = "(nombre_contacto LIKE :q OR telefono_contacto LIKE :q OR correo_contacto LIKE :q OR session_id_hash LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      $sqlWhere = implode(" AND ", $where);
      $stmtTotal = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_leads_carritos WHERE " . $sqlWhere);
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());
      $stmt = $db->prepare("SELECT * FROM erp_ecommerce_leads_carritos WHERE " . $sqlWhere . " ORDER BY COALESCE(fecha_ultima_actividad, fecha_registro) DESC, id_carrito_lead DESC LIMIT " . intval($limite) . " OFFSET " . intval($offset));
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = $this->formatearCarrito($fila);
      }
      return $this->respuesta(false, "success", "Bandeja Ecommerce Leads consultada", array(
        "configurado" => true,
        "items" => $items,
        "resumen" => $this->resumenItems($items),
        "paginacion" => array("pagina" => $pagina, "limite" => $limite, "total" => $total),
        "filtros" => array("estatus" => $estatus, "q" => $q),
        "tablas" => $tablas,
        "guardrails" => $this->guardrails(false)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("items" => array(), "read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: consultar detalle interno de un carrito/lead ecommerce.
   * Impacto: muestra snapshot comercial, eventos y notas sin crear documentos operativos.
   * Contrato: GET protegido; solo lectura.
   */
  public function detalleInterno($filtros = array()) {
    $db = $this->getConexion();
    $tablas = $this->tablasDisponibles($db);
    if (!$db || empty($tablas["carritos"])) {
      return $this->respuesta(false, "info", "Detalle Ecommerce Lead aun sin esquema", array("configurado" => false, "item" => null, "detalle" => array(), "eventos" => array(), "notas" => array()));
    }
    try {
      $id = intval($this->valor($filtros, "id_carrito_lead", $this->valor($filtros, "id", 0)));
      if ($id <= 0) {
        return $this->respuesta(true, "warning", "Indica id_carrito_lead", array("item" => null));
      }
      $stmt = $db->prepare("SELECT * FROM erp_ecommerce_leads_carritos WHERE id_carrito_lead=:id LIMIT 1");
      $stmt->execute(array(":id" => $id));
      $carrito = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$carrito) {
        return $this->respuesta(false, "info", "Carrito ecommerce no encontrado", array("item" => null, "detalle" => array(), "eventos" => array(), "notas" => array()));
      }
      $detalle = array();
      if (!empty($tablas["items"])) {
        $stmtDetalle = $db->prepare("SELECT * FROM erp_ecommerce_leads_carrito_items WHERE id_carrito_lead=:id ORDER BY renglon ASC, id_carrito_lead_item ASC");
        $stmtDetalle->execute(array(":id" => $id));
        $detalle = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
      }
      $eventos = array();
      if (!empty($tablas["eventos"])) {
        $stmtEventos = $db->prepare("SELECT * FROM erp_ecommerce_leads_eventos WHERE id_carrito_lead=:id ORDER BY fecha_registro ASC, id_lead_evento ASC");
        $stmtEventos->execute(array(":id" => $id));
        $eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
      }
      $notas = array();
      if (!empty($tablas["notas"])) {
        $stmtNotas = $db->prepare("SELECT * FROM erp_ecommerce_leads_notas WHERE id_carrito_lead=:id AND estatus='activa' ORDER BY fecha_registro DESC, id_lead_nota DESC");
        $stmtNotas->execute(array(":id" => $id));
        $notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);
      }
      return $this->respuesta(false, "success", "Detalle Ecommerce Lead consultado", array(
        "configurado" => true,
        "item" => $this->formatearCarrito($carrito),
        "detalle" => $detalle,
        "eventos" => $eventos,
        "notas" => $notas,
        "acciones" => array("marcar_en_seguimiento", "marcar_convertido", "marcar_descartado", "agregar_nota", "copiar_resumen_whatsapp"),
        "guardrails" => $this->guardrails(false)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("item" => null, "read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: planear acciones internas sobre un carrito/lead sin ejecutarlas.
   * Impacto: define seguimiento, conversion, descarte o nota sin crear pedido ni venta.
   * Contrato: POST protegido; read-only hasta autorizar acciones reales.
   */
  public function accionPlanInterna($datos = array()) {
    $accion = $this->limpiarToken($this->valor($datos, "accion", ""), 60);
    $id = intval($this->valor($datos, "id_carrito_lead", $this->valor($datos, "id", 0)));
    $nota = $this->limpiarTexto($this->valor($datos, "nota", $this->valor($datos, "motivo", "")), 1000);
    $permitidas = array("marcar_en_seguimiento", "marcar_convertido", "marcar_descartado", "agregar_nota", "copiar_resumen_whatsapp");
    $bloqueos = array();
    if (!in_array($accion, $permitidas, true)) { $bloqueos[] = "accion_no_permitida"; }
    if ($id <= 0) { $bloqueos[] = "id_carrito_lead_requerido"; }
    if (in_array($accion, array("marcar_descartado", "agregar_nota"), true) && $nota === "") { $bloqueos[] = "nota_o_motivo_requerido"; }
    $estatusDestino = $accion === "marcar_en_seguimiento" ? "en_seguimiento" : ($accion === "marcar_convertido" ? "convertido" : ($accion === "marcar_descartado" ? "descartado" : null));
    $sql = array();
    if (empty($bloqueos) && $estatusDestino !== null) {
      $sql[] = "UPDATE `erp_ecommerce_leads_carritos` SET `estatus`='" . $estatusDestino . "', `fecha_actualizacion`=NOW() WHERE `id_carrito_lead`=" . intval($id) . " LIMIT 1;";
    }
    if (empty($bloqueos) && $nota !== "") {
      $sql[] = "INSERT INTO `erp_ecommerce_leads_notas` (`id_carrito_lead`, `nota`, `creado_por`, `fecha_registro`) VALUES (" . intval($id) . ", ..., " . intval(SesionSeguridad::usuarioId()) . ", NOW());";
    }
    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Plan de accion Ecommerce Lead listo" : "Plan de accion con bloqueos", array(
      "read_only" => true,
      "no_escribe_bd" => true,
      "accion" => $accion,
      "acciones_permitidas" => $permitidas,
      "id_carrito_lead" => $id,
      "estatus_destino_planeado" => $estatusDestino,
      "sql_plan" => $sql,
      "bloqueos" => array_values(array_unique($bloqueos)),
      "guardrails" => $this->guardrails(false)
    ));
  }

  private function resolverPersistenciaPublica($normalizado, $origen) {
    $bloqueos = $this->bloqueosPayload($normalizado);
    $tablas = $this->tablasDisponibles($this->getConexion());
    $activo = $this->persistenciaPublicaActiva($tablas);
    if (!$activo) { $bloqueos[] = "persistencia_leads_no_activa"; }
    foreach (array("carritos", "items", "eventos") as $tabla) {
      if (empty($tablas[$tabla])) { $bloqueos[] = "tabla_leads_" . $tabla . "_pendiente"; }
    }
    if (empty($bloqueos)) {
      return $this->persistirLeadAutorizado($normalizado, $origen, $tablas);
    }
    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Lead ecommerce listo para registro" : "Lead ecommerce validado sin guardar", array(
      "preflight" => true,
      "read_only" => !empty($bloqueos),
      "no_escribe_bd" => !empty($bloqueos),
      "origen" => $origen,
      "lead_normalizado" => $normalizado,
      "tablas" => $tablas,
      "persistencia_activa" => $activo,
      "listo_para_registro" => empty($bloqueos),
      "sql_plan" => empty($bloqueos) ? $this->sqlPlan($normalizado) : $this->sqlPlan($normalizado),
      "bloqueos" => array_values(array_unique($bloqueos)),
      "guardrails" => $this->guardrails(empty($bloqueos))
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: persistir carrito/lead ecommerce ya validado cuando la bandera publica esta activa.
   * Impacto: crea o actualiza snapshot comercial sin pedido, venta, cotizacion real ni inventario.
   * Contrato: requiere tablas disponibles y `ECOMMERCE_LEADS_PUBLICO=true`; usa transaccion y no guarda session_id crudo.
   */
  private function persistirLeadAutorizado($normalizado, $origen, $tablas) {
    $db = $this->getConexion();
    if (!$db) {
      return $this->respuesta(true, "danger", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
    }
    try {
      $db->beginTransaction();
      $idCarrito = $this->upsertCarrito($db, $normalizado);
      $this->reemplazarItemsCarrito($db, $idCarrito, $normalizado["items"]);
      $idEvento = $this->insertarEventoLead($db, $idCarrito, $normalizado, $origen);
      $db->commit();
      return $this->respuesta(false, "success", "Lead ecommerce registrado", array(
        "preflight" => false,
        "read_only" => false,
        "no_escribe_bd" => false,
        "escribe_bd" => true,
        "id_carrito_lead" => $idCarrito,
        "id_lead_evento" => $idEvento,
        "lead_normalizado" => $normalizado,
        "tablas" => $tablas,
        "guardrails" => $this->guardrails(true)
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "no_escribe_bd" => true,
        "rollback" => true,
        "origen" => $origen,
        "guardrails" => $this->guardrails(false)
      ));
    }
  }

  private function upsertCarrito($db, $normalizado) {
    $contacto = $normalizado["contacto"];
    $whatsapp = $normalizado["whatsapp"];
    $totales = $normalizado["totales"];
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_leads_carritos
        (session_id_hash, canal, estado_carrito, estatus, etapa_actual, ultima_ruta, nombre_contacto, telefono_contacto, correo_contacto,
         acepta_whatsapp, acepta_politicas, whatsapp_mensaje_generado, whatsapp_url_generada, whatsapp_abierto, solicito_facturacion,
         items_total, piezas_total, subtotal_estimado, moneda, metadata_json, fecha_ultima_actividad, fecha_actualizacion)
      VALUES
        (:session_id_hash, :canal, :estado_carrito, :estatus, :etapa_actual, :ultima_ruta, :nombre_contacto, :telefono_contacto, :correo_contacto,
         :acepta_whatsapp, :acepta_politicas, :whatsapp_mensaje_generado, :whatsapp_url_generada, :whatsapp_abierto, :solicito_facturacion,
         :items_total, :piezas_total, :subtotal_estimado, :moneda, :metadata_json, NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        estado_carrito=VALUES(estado_carrito),
        estatus=VALUES(estatus),
        etapa_actual=VALUES(etapa_actual),
        ultima_ruta=VALUES(ultima_ruta),
        nombre_contacto=COALESCE(NULLIF(VALUES(nombre_contacto), ''), nombre_contacto),
        telefono_contacto=COALESCE(NULLIF(VALUES(telefono_contacto), ''), telefono_contacto),
        correo_contacto=COALESCE(NULLIF(VALUES(correo_contacto), ''), correo_contacto),
        acepta_whatsapp=GREATEST(acepta_whatsapp, VALUES(acepta_whatsapp)),
        acepta_politicas=GREATEST(acepta_politicas, VALUES(acepta_politicas)),
        whatsapp_mensaje_generado=COALESCE(NULLIF(VALUES(whatsapp_mensaje_generado), ''), whatsapp_mensaje_generado),
        whatsapp_url_generada=COALESCE(NULLIF(VALUES(whatsapp_url_generada), ''), whatsapp_url_generada),
        whatsapp_abierto=GREATEST(whatsapp_abierto, VALUES(whatsapp_abierto)),
        solicito_facturacion=GREATEST(solicito_facturacion, VALUES(solicito_facturacion)),
        items_total=VALUES(items_total),
        piezas_total=VALUES(piezas_total),
        subtotal_estimado=VALUES(subtotal_estimado),
        moneda=VALUES(moneda),
        metadata_json=VALUES(metadata_json),
        fecha_ultima_actividad=NOW(),
        fecha_actualizacion=NOW()");
    $stmt->execute(array(
      ":session_id_hash" => $normalizado["session_id_hash"],
      ":canal" => $normalizado["canal"],
      ":estado_carrito" => $normalizado["estado_carrito"],
      ":estatus" => $normalizado["estatus"],
      ":etapa_actual" => $normalizado["tipo_evento"],
      ":ultima_ruta" => $normalizado["ruta"],
      ":nombre_contacto" => $contacto["nombre"],
      ":telefono_contacto" => $contacto["telefono"],
      ":correo_contacto" => $contacto["correo"],
      ":acepta_whatsapp" => $contacto["acepta_whatsapp"] ? 1 : 0,
      ":acepta_politicas" => $contacto["acepta_politicas"] ? 1 : 0,
      ":whatsapp_mensaje_generado" => $whatsapp["mensaje_generado"],
      ":whatsapp_url_generada" => $whatsapp["url_generada"],
      ":whatsapp_abierto" => $whatsapp["abierto"] ? 1 : 0,
      ":solicito_facturacion" => !empty($normalizado["solicito_facturacion"]) ? 1 : 0,
      ":items_total" => intval($totales["items_total"]),
      ":piezas_total" => floatval($totales["piezas_total"]),
      ":subtotal_estimado" => floatval($totales["subtotal"]),
      ":moneda" => $totales["moneda"],
      ":metadata_json" => json_encode($normalizado["metadata"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ));
    $stmtId = $db->prepare("SELECT id_carrito_lead FROM erp_ecommerce_leads_carritos WHERE session_id_hash=:session_id_hash AND canal=:canal LIMIT 1");
    $stmtId->execute(array(":session_id_hash" => $normalizado["session_id_hash"], ":canal" => $normalizado["canal"]));
    return intval($stmtId->fetchColumn());
  }

  private function reemplazarItemsCarrito($db, $idCarrito, $items) {
    $stmtDelete = $db->prepare("DELETE FROM erp_ecommerce_leads_carrito_items WHERE id_carrito_lead=:id");
    $stmtDelete->execute(array(":id" => $idCarrito));
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_leads_carrito_items
        (id_carrito_lead, renglon, id_publicacion, id_sku, slug, sku_snapshot, nombre_snapshot, cantidad, precio_unitario_snapshot, subtotal_snapshot, moneda_snapshot, validacion_publicacion, metadata_json, fecha_registro)
      VALUES
        (:id_carrito_lead, :renglon, :id_publicacion, :id_sku, :slug, :sku_snapshot, :nombre_snapshot, :cantidad, :precio_unitario_snapshot, :subtotal_snapshot, :moneda_snapshot, :validacion_publicacion, :metadata_json, NOW())");
    foreach ($items as $item) {
      $stmt->execute(array(
        ":id_carrito_lead" => $idCarrito,
        ":renglon" => intval($item["renglon"]),
        ":id_publicacion" => intval($item["id_publicacion"]) > 0 ? intval($item["id_publicacion"]) : null,
        ":id_sku" => intval($item["id_sku"]) > 0 ? intval($item["id_sku"]) : null,
        ":slug" => $item["slug"],
        ":sku_snapshot" => $item["sku"],
        ":nombre_snapshot" => $item["nombre"],
        ":cantidad" => floatval($item["cantidad"]),
        ":precio_unitario_snapshot" => floatval($item["precio_unitario"]),
        ":subtotal_snapshot" => floatval($item["subtotal"]),
        ":moneda_snapshot" => "MXN",
        ":validacion_publicacion" => $item["validacion_publicacion"],
        ":metadata_json" => json_encode($this->valor($item, "validacion_detalle", array()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      ));
    }
  }

  private function insertarEventoLead($db, $idCarrito, $normalizado, $origen) {
    $detalle = array(
      "origen" => $origen,
      "estatus" => $normalizado["estatus"],
      "items_total" => intval($normalizado["totales"]["items_total"]),
      "piezas_total" => floatval($normalizado["totales"]["piezas_total"]),
      "subtotal" => floatval($normalizado["totales"]["subtotal"]),
      "whatsapp_abierto" => !empty($normalizado["whatsapp"]["abierto"]),
      "contacto_explicito" => $normalizado["contacto"]["telefono"] !== "" || $normalizado["contacto"]["correo"] !== ""
    );
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_leads_eventos
        (id_carrito_lead, session_id_hash, tipo_evento, canal, ruta, etapa, resultado, detalle_json, fecha_registro, creado_por)
      VALUES
        (:id_carrito_lead, :session_id_hash, :tipo_evento, :canal, :ruta, :etapa, :resultado, :detalle_json, NOW(), NULL)");
    $stmt->execute(array(
      ":id_carrito_lead" => $idCarrito,
      ":session_id_hash" => $normalizado["session_id_hash"],
      ":tipo_evento" => $normalizado["tipo_evento"],
      ":canal" => $normalizado["canal"],
      ":ruta" => $normalizado["ruta"],
      ":etapa" => $normalizado["estatus"],
      ":resultado" => "registrado",
      ":detalle_json" => json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ));
    return intval($db->lastInsertId());
  }

  private function normalizarPayload($datos, $tipoEvento) {
    $contactoRaw = is_array($this->valor($datos, "contacto", array())) ? $this->valor($datos, "contacto", array()) : array();
    $whatsappRaw = is_array($this->valor($datos, "whatsapp", array())) ? $this->valor($datos, "whatsapp", array()) : array();
    $items = $this->normalizarItems($this->valor($datos, "items", array()));
    $validacionItems = $this->validarItemsPublicados($items);
    $items = $validacionItems["items"];
    $totales = is_array($this->valor($datos, "totales", array())) ? $this->valor($datos, "totales", array()) : array();
    $subtotal = $this->numero($this->valor($totales, "subtotal", 0));
    if ($subtotal <= 0) {
      foreach ($items as $item) { $subtotal += $item["subtotal"]; }
    }
    $estatus = $this->estatusSugerido($contactoRaw, $whatsappRaw, $tipoEvento);
    return array(
      "session_id_hash" => $this->hashSession($this->sessionIdLimpio($this->valor($datos, "session_id", ""))),
      "canal" => $this->limpiarToken($this->valor($datos, "canal", "web_publica"), 50),
      "ruta" => $this->limpiarRuta($this->valor($datos, "ruta", "")),
      "estado_carrito" => $this->limpiarToken($this->valor($datos, "estado_carrito", "activo"), 40),
      "estatus" => $estatus,
      "tipo_evento" => in_array($tipoEvento, $this->eventosPermitidos, true) ? $tipoEvento : "cart_update",
      "contacto" => $this->normalizarContacto($contactoRaw),
      "whatsapp" => array(
        "mensaje_generado" => $this->limpiarTexto($this->valor($whatsappRaw, "mensaje_generado", ""), 3000),
        "url_generada" => $this->limpiarRuta($this->valor($whatsappRaw, "url_generada", "")),
        "abierto" => $this->bool($this->valor($whatsappRaw, "abierto", $tipoEvento === "open_whatsapp"))
      ),
      "items" => $items,
      "validacion_items" => $validacionItems["resumen"],
      "totales" => array(
        "items_total" => intval($this->valor($totales, "items_total", count($items))),
        "piezas_total" => $this->numero($this->valor($totales, "piezas_total", $this->sumarPiezas($items))),
        "subtotal" => round($subtotal, 6),
        "moneda" => "MXN"
      ),
      "metadata" => $this->limpiarMetadata($this->valor($datos, "metadata", array())),
      "solicito_facturacion" => false,
      "pii_fuera_contacto" => $this->detectarPiiFueraContacto($datos)
    );
  }

  private function normalizarItems($items) {
    $salida = array();
    if (!is_array($items)) { return $salida; }
    foreach (array_slice($items, 0, 80) as $index => $item) {
      if (!is_array($item)) { continue; }
      $cantidad = max(0, min(99999, $this->numero($this->valor($item, "cantidad", 1))));
      if ($cantidad <= 0) { continue; }
      $precio = max(0, $this->numero($this->valor($item, "precio_unitario", 0)));
      $subtotal = $this->numero($this->valor($item, "subtotal", $precio * $cantidad));
      $salida[] = array(
        "renglon" => $index + 1,
        "id_publicacion" => max(0, intval($this->valor($item, "id_publicacion", 0))),
        "id_sku" => max(0, intval($this->valor($item, "id_sku", 0))),
        "slug" => $this->limpiarSlug($this->valor($item, "slug", "")),
        "sku" => $this->limpiarTexto($this->valor($item, "sku", ""), 120),
        "nombre" => $this->limpiarTexto($this->valor($item, "nombre", ""), 255),
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "subtotal" => round($subtotal, 6),
        "validacion_publicacion" => "pendiente"
      );
    }
    return $salida;
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: enriquecer cada item del lead con su estado frente al catalogo publico vivo.
   * Impacto: permite distinguir carritos accionables de SKUs no publicados o inactivos antes de seguimiento.
   * Contrato: solo lectura; no bloquea el preflight ni escribe catalogo, ventas o inventario.
   */
  private function validarItemsPublicados($items) {
    $resumen = array(
      "catalogo_disponible" => false,
      "total" => count($items),
      "vigentes" => 0,
      "requieren_revision" => 0,
      "estatus" => array()
    );
    if (empty($items)) {
      return array("items" => $items, "resumen" => $resumen);
    }
    $db = $this->getConexion();
    $tablas = array(
      "publicaciones" => $this->tablaExisteDb($db, "erp_ecommerce_publicaciones"),
      "skus" => $this->tablaExisteDb($db, "erp_catalogo_skus"),
      "productos" => $this->tablaExisteDb($db, "erp_catalogo_productos")
    );
    if (!$db || empty($tablas["publicaciones"]) || empty($tablas["skus"]) || empty($tablas["productos"])) {
      foreach ($items as $i => $item) {
        $items[$i]["validacion_publicacion"] = "validacion_no_disponible";
        $items[$i]["validacion_detalle"] = array("motivo" => "tablas_catalogo_no_disponibles", "tablas" => $tablas);
      }
      $resumen["estatus"]["validacion_no_disponible"] = count($items);
      $resumen["requieren_revision"] = count($items);
      return array("items" => $items, "resumen" => $resumen);
    }
    $resumen["catalogo_disponible"] = true;
    foreach ($items as $i => $item) {
      $validacion = $this->validarItemPublicado($db, $item);
      $estatus = $validacion["estatus"];
      $items[$i]["validacion_publicacion"] = $estatus;
      $items[$i]["validacion_detalle"] = $validacion["detalle"];
      if (intval($items[$i]["id_publicacion"]) <= 0 && !empty($validacion["detalle"]["id_publicacion"])) {
        $items[$i]["id_publicacion"] = intval($validacion["detalle"]["id_publicacion"]);
      }
      if (intval($items[$i]["id_sku"]) <= 0 && !empty($validacion["detalle"]["id_sku"])) {
        $items[$i]["id_sku"] = intval($validacion["detalle"]["id_sku"]);
      }
      if ($items[$i]["slug"] === "" && !empty($validacion["detalle"]["slug"])) {
        $items[$i]["slug"] = $validacion["detalle"]["slug"];
      }
      if (!isset($resumen["estatus"][$estatus])) { $resumen["estatus"][$estatus] = 0; }
      $resumen["estatus"][$estatus]++;
      if ($estatus === "publicacion_vigente") { $resumen["vigentes"]++; } else { $resumen["requieren_revision"]++; }
    }
    return array("items" => $items, "resumen" => $resumen);
  }

  private function validarItemPublicado($db, $item) {
    $idPublicacion = intval($this->valor($item, "id_publicacion", 0));
    $idSku = intval($this->valor($item, "id_sku", 0));
    $slug = $this->limpiarSlug($this->valor($item, "slug", ""));
    if ($idPublicacion <= 0 && $idSku <= 0 && $slug === "") {
      return array("estatus" => "sin_identificador", "detalle" => array("motivo" => "item_sin_id_publicacion_slug_o_id_sku"));
    }
    try {
      $publicacion = $this->consultarPublicacionLead($db, $idPublicacion, $idSku, $slug);
      if ($publicacion) {
        $estatus = "publicacion_vigente";
        if (($idSku > 0 && $idSku !== intval($publicacion["id_sku"])) || ($slug !== "" && $slug !== (string) $publicacion["slug"])) {
          $estatus = "identificadores_inconsistentes";
        } elseif ((string) $publicacion["estatus_publicacion"] !== "publicado") {
          $estatus = "publicacion_no_publicada";
        } elseif ((string) $publicacion["producto_estatus"] !== "activo") {
          $estatus = "producto_inactivo";
        } elseif ((string) $publicacion["sku_estatus"] !== "activo") {
          $estatus = "sku_inactivo";
        }
        return array("estatus" => $estatus, "detalle" => array(
          "id_publicacion" => intval($publicacion["id_publicacion"]),
          "id_sku" => intval($publicacion["id_sku"]),
          "slug" => (string) $publicacion["slug"],
          "estatus_publicacion" => (string) $publicacion["estatus_publicacion"],
          "estatus_producto" => (string) $publicacion["producto_estatus"],
          "estatus_sku" => (string) $publicacion["sku_estatus"],
          "sku_catalogo" => (string) $publicacion["sku"],
          "nombre_catalogo" => (string) $publicacion["sku_nombre"]
        ));
      }
      $sku = $this->consultarSkuLead($db, $idSku, $this->valor($item, "sku", ""));
      if ($sku) {
        $estatus = "sku_sin_publicacion";
        if ((string) $sku["producto_estatus"] !== "activo") { $estatus = "producto_inactivo"; }
        elseif ((string) $sku["sku_estatus"] !== "activo") { $estatus = "sku_inactivo"; }
        return array("estatus" => $estatus, "detalle" => array(
          "id_sku" => intval($sku["id_sku"]),
          "estatus_producto" => (string) $sku["producto_estatus"],
          "estatus_sku" => (string) $sku["sku_estatus"],
          "sku_catalogo" => (string) $sku["sku"],
          "nombre_catalogo" => (string) $sku["sku_nombre"]
        ));
      }
      return array("estatus" => "no_encontrado", "detalle" => array("motivo" => "sin_coincidencia_en_publicaciones_ni_skus"));
    } catch (Exception $e) {
      return array("estatus" => "validacion_error", "detalle" => array("motivo" => $e->getMessage()));
    }
  }

  private function consultarPublicacionLead($db, $idPublicacion, $idSku, $slug) {
    $where = array();
    $params = array();
    if ($idPublicacion > 0) {
      $where[] = "pub.id_publicacion=:id_publicacion";
      $params[":id_publicacion"] = $idPublicacion;
    } elseif ($slug !== "") {
      $where[] = "pub.slug=:slug";
      $params[":slug"] = $slug;
    } elseif ($idSku > 0) {
      $where[] = "pub.id_sku=:id_sku";
      $params[":id_sku"] = $idSku;
    }
    if (empty($where)) { return null; }
    $stmt = $db->prepare("SELECT pub.id_publicacion, pub.id_sku, pub.slug, pub.estatus_publicacion,
        s.sku, s.nombre AS sku_nombre, s.estatus AS sku_estatus,
        p.id_producto_erp, p.nombre AS producto_nombre, p.estatus AS producto_estatus
      FROM erp_ecommerce_publicaciones pub
      INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
      WHERE " . implode(" AND ", $where) . "
      ORDER BY FIELD(pub.estatus_publicacion, 'publicado', 'borrador', 'pausado', 'archivado'), pub.id_publicacion DESC
      LIMIT 1");
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
  }

  private function consultarSkuLead($db, $idSku, $skuTexto) {
    $skuTexto = $this->limpiarTexto($skuTexto, 150);
    if ($idSku <= 0 && $skuTexto === "") { return null; }
    $where = array();
    $params = array();
    if ($idSku > 0) {
      $where[] = "s.id_sku=:id_sku";
      $params[":id_sku"] = $idSku;
    } else {
      $where[] = "LOWER(TRIM(s.sku))=LOWER(TRIM(:sku))";
      $params[":sku"] = $skuTexto;
    }
    $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre AS sku_nombre, s.estatus AS sku_estatus,
        p.id_producto_erp, p.nombre AS producto_nombre, p.estatus AS producto_estatus
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE " . implode(" AND ", $where) . "
      LIMIT 1");
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
  }

  private function normalizarContacto($contacto) {
    return array(
      "nombre" => $this->limpiarTexto($this->valor($contacto, "nombre", ""), 220),
      "telefono" => $this->limpiarTexto($this->valor($contacto, "telefono", ""), 80),
      "correo" => $this->limpiarTexto($this->valor($contacto, "correo", $this->valor($contacto, "email", "")), 220),
      "mensaje" => $this->limpiarTexto($this->valor($contacto, "mensaje", ""), 3000),
      "acepta_whatsapp" => $this->bool($this->valor($contacto, "acepta_whatsapp", false)),
      "acepta_politicas" => $this->bool($this->valor($contacto, "acepta_politicas", false))
    );
  }

  private function bloqueosPayload($normalizado) {
    $bloqueos = array();
    if ($normalizado["session_id_hash"] === "") { $bloqueos[] = "session_id_anonimo_requerido"; }
    if (empty($normalizado["items"]) && !in_array($normalizado["tipo_evento"], array("contact_submit", "facturacion_submit"), true)) { $bloqueos[] = "items_requeridos"; }
    if (!empty($normalizado["pii_fuera_contacto"])) { $bloqueos[] = "datos_personales_fuera_de_contacto"; }
    return $bloqueos;
  }

  private function sqlPlan($normalizado) {
    return array(
      "UPSERT `erp_ecommerce_leads_carritos` por (`session_id_hash`, `canal`) con estatus, contacto explicito, totales, WhatsApp y metadata.",
      "DELETE/INSERT snapshot en `erp_ecommerce_leads_carrito_items` para conservar carrito actual por renglon.",
      "INSERT `erp_ecommerce_leads_eventos` con tipo_evento, ruta, etapa y detalle_json."
    );
  }

  private function tablasDisponibles($db) {
    return array(
      "carritos" => $this->tablaExisteDb($db, "erp_ecommerce_leads_carritos"),
      "items" => $this->tablaExisteDb($db, "erp_ecommerce_leads_carrito_items"),
      "eventos" => $this->tablaExisteDb($db, "erp_ecommerce_leads_eventos"),
      "notas" => $this->tablaExisteDb($db, "erp_ecommerce_leads_notas")
    );
  }

  private function persistenciaPublicaActiva($tablas = null) {
    if (!defined("ECOMMERCE_LEADS_PUBLICO") || ECOMMERCE_LEADS_PUBLICO !== true) { return false; }
    if ($tablas === null) { $tablas = $this->tablasDisponibles($this->getConexion()); }
    return !empty($tablas["carritos"]) && !empty($tablas["items"]) && !empty($tablas["eventos"]);
  }

  private function tablaExisteDb($db, $tabla) {
    if (!$db) { return false; }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
  }

  private function formatearCarrito($fila) {
    return array(
      "id_carrito_lead" => intval($fila["id_carrito_lead"]),
      "session_id_hash" => substr((string) $fila["session_id_hash"], 0, 16),
      "canal" => $fila["canal"],
      "estatus" => $fila["estatus"],
      "etapa_actual" => $fila["etapa_actual"],
      "ultima_ruta" => $fila["ultima_ruta"],
      "nombre_contacto" => $fila["nombre_contacto"],
      "telefono_contacto" => $fila["telefono_contacto"],
      "correo_contacto" => $fila["correo_contacto"],
      "items_total" => intval($fila["items_total"]),
      "piezas_total" => floatval($fila["piezas_total"]),
      "subtotal_estimado" => floatval($fila["subtotal_estimado"]),
      "moneda" => $fila["moneda"],
      "whatsapp_abierto" => intval($fila["whatsapp_abierto"]) === 1,
      "acepta_politicas" => intval($fila["acepta_politicas"]) === 1,
      "solicito_facturacion" => intval($fila["solicito_facturacion"]) === 1,
      "responsable_seguimiento" => $fila["responsable_seguimiento"],
      "fecha_registro" => $fila["fecha_registro"],
      "fecha_ultima_actividad" => $fila["fecha_ultima_actividad"]
    );
  }

  private function resumenVacio() {
    return array("total" => 0, "anonimos" => 0, "con_contacto" => 0, "whatsapp_abierto" => 0, "subtotal_estimado" => 0);
  }

  private function resumenItems($items) {
    $resumen = $this->resumenVacio();
    $resumen["total"] = count($items);
    foreach ($items as $item) {
      if (empty($item["telefono_contacto"]) && empty($item["correo_contacto"])) { $resumen["anonimos"]++; } else { $resumen["con_contacto"]++; }
      if (!empty($item["whatsapp_abierto"])) { $resumen["whatsapp_abierto"]++; }
      $resumen["subtotal_estimado"] += floatval($item["subtotal_estimado"]);
    }
    return $resumen;
  }

  private function estatusSugerido($contacto, $whatsapp, $tipoEvento) {
    if ($tipoEvento === "open_whatsapp" && $this->bool($this->valor($whatsapp, "abierto", true))) { return "whatsapp_abierto"; }
    if ($this->limpiarTexto($this->valor($whatsapp, "mensaje_generado", ""), 20) !== "") { return "whatsapp_generado"; }
    if ($this->limpiarTexto($this->valor($contacto, "telefono", $this->valor($contacto, "correo", "")), 220) !== "") { return "contacto_pendiente"; }
    return "anonimo_activo";
  }

  private function detectarPiiFueraContacto($datos) {
    $copia = is_array($datos) ? $datos : array();
    unset($copia["contacto"]);
    unset($copia["items"]);
    unset($copia["whatsapp"]);
    return $this->detectarPii($copia);
  }

  private function detectarPii($datos, $prefijo = "") {
    $detectados = array();
    if (!is_array($datos)) {
      $valor = trim((string) $datos);
      if ($valor !== "" && preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $valor)) { $detectados[] = $prefijo . "correo"; }
      if ($valor !== "" && preg_match('/(?:\D|^)(\d{10})(?:\D|$)/', $valor)) { $detectados[] = $prefijo . "telefono"; }
      if ($valor !== "" && preg_match('/\b[A-Z&\x{00D1}]{3,4}\d{6}[A-Z0-9]{3}\b/u', strtoupper($valor))) { $detectados[] = $prefijo . "rfc"; }
      return $detectados;
    }
    foreach ($datos as $clave => $valor) {
      $claveLimpia = strtolower(trim((string) $clave));
      if (in_array($claveLimpia, array("nombre", "telefono", "celular", "correo", "email", "rfc", "razon_social", "direccion"), true)) { $detectados[] = $prefijo . $claveLimpia; }
      $detectados = array_merge($detectados, $this->detectarPii($valor, $prefijo . $claveLimpia . "."));
    }
    return array_values(array_unique($detectados));
  }

  private function limpiarMetadata($datos, $nivel = 0) {
    if (!is_array($datos) || $nivel > 2) { return is_scalar($datos) ? $this->limpiarTexto($datos, 160) : null; }
    $salida = array();
    foreach ($datos as $clave => $valor) {
      $claveLimpia = $this->limpiarToken($clave, 60);
      if ($claveLimpia === "" || in_array($claveLimpia, array("nombre", "telefono", "correo", "email", "rfc", "razon_social", "direccion", "stock", "existencia"), true)) { continue; }
      $salida[$claveLimpia] = is_array($valor) ? $this->limpiarMetadata($valor, $nivel + 1) : $this->limpiarTexto($valor, 160);
      if (count($salida) >= 40) { break; }
    }
    return $salida;
  }

  private function sumarPiezas($items) {
    $total = 0;
    foreach ($items as $item) { $total += floatval($item["cantidad"]); }
    return $total;
  }

  private function hashSession($valor) {
    return $valor === "" ? "" : hash("sha256", "ecommerce_leads|" . $valor);
  }

  private function sessionIdLimpio($valor) {
    return substr(preg_replace('/[^a-zA-Z0-9_\-.]/', '', trim((string) $valor)), 0, 100);
  }

  private function limpiarToken($valor, $limite) {
    return substr(preg_replace('/[^a-z0-9_\-]/', '', strtolower(trim((string) $valor))), 0, $limite);
  }

  private function limpiarSlug($valor) {
    return substr(preg_replace('/[^a-z0-9\-]/', '', strtolower(trim((string) $valor))), 0, 180);
  }

  private function limpiarRuta($valor) {
    return substr(preg_replace('/[\x00-\x1F\x7F]/', '', trim((string) $valor)), 0, 700);
  }

  private function limpiarTexto($valor, $limite) {
    return substr(preg_replace('/[\x00-\x1F\x7F]/', '', trim((string) $valor)), 0, $limite);
  }

  private function numero($valor) {
    return is_numeric($valor) ? floatval($valor) : 0.0;
  }

  private function bool($valor) {
    return $valor === true || $valor === 1 || $valor === "1" || $valor === "true";
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function guardrails($escrituraActiva) {
    return array(
      "no_escribe_bd" => !$escrituraActiva,
      "persistencia_requiere_ECOMMERCE_LEADS_PUBLICO" => !$escrituraActiva,
      "no_crear_pedido" => true,
      "no_crear_venta" => true,
      "no_descuenta_inventario" => true,
      "no_sustituye_analytics" => true,
      "session_id_se_guarda_hash" => true,
      "datos_personales_solo_bloque_contacto" => true,
      "snapshot_precio_conservado" => true
    );
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array(
      "error" => $error,
      "tipo" => $tipo,
      "mensaje" => $mensaje,
      "api" => array("nombre" => "ERP Ecommerce Leads", "version" => "fase3-leads-carritos-2026-08-30", "modo" => "intencion_comercial"),
      "depurar" => $depurar
    );
  }
}
