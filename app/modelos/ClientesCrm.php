<?php

class ClientesCrm extends CRUD {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: diagnosticar el dominio Clientes/CRM sin escribir datos.
   * Impacto: separa CRM de Ventas/POS y Ecommerce antes de migrar clientes.
   * Contrato: read-only; no inserta, no actualiza, no fusiona y no normaliza datos en BD.
   */
  public function diagnosticoDominioClientes() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para diagnosticar CRM", array(
          "conexion_mysql" => false
        ));
      }

      $tablasCanonicas = array(
        "crm_clientes_maestro",
        "crm_clientes_identificadores",
        "crm_clientes_contactos",
        "crm_clientes_direcciones",
        "crm_clientes_fiscales",
        "crm_clientes_vinculos_externos"
      );
      $fuentesActuales = array(
        "crm_clientes",
        "erp_clientes",
        "erp_clientes_identificadores",
        "erp_clientes_listas_precios",
        "erp_ventas",
        "ecom_pedidos",
        "ecom_clientes"
      );

      $canonicas = $this->estadoTablasConteo($db, $tablasCanonicas);
      $fuentes = $this->estadoTablasConteo($db, $fuentesActuales);
      $hallazgos = array();

      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        $hallazgos[] = array(
          "id" => "CRM-CLI-001",
          "severidad" => "alta",
          "mensaje" => "No existe tabla canonica crm_clientes_maestro"
        );
      }
      if ($this->tablaExiste($db, "crm_clientes")) {
        $hallazgos[] = array(
          "id" => "CRM-CLI-002",
          "severidad" => "media",
          "mensaje" => "Existe crm_clientes legacy; requiere auditoria antes de vincular o migrar"
        );
      }
      if ($this->tablaExiste($db, "erp_clientes")) {
        $hallazgos[] = array(
          "id" => "CRM-CLI-003",
          "severidad" => "media",
          "mensaje" => "Existe erp_clientes de POS/UAT; debe quedar como fuente/vinculo o migrarse a CRM canonico"
        );
      }

      return $this->respuesta(false, empty($hallazgos) ? "success" : "warning", "Diagnostico CRM Clientes generado", array(
        "canonicas" => $canonicas,
        "fuentes_actuales" => $fuentes,
        "hallazgos" => $hallazgos,
        "contrato" => array(
          "crm_dueno_cliente" => true,
          "pos_consume_crm" => true,
          "venta_publico_general_sin_cliente" => true,
          "legacy_no_se_mezcla_sin_auditoria" => true,
          "solo_lectura" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: normalizar identificadores de cliente para busqueda express.
   * Impacto: CRM/POS; define contrato estable antes de escribir altas reales.
   * Contrato: funcion pura; no consulta ni escribe BD.
   */
  public function normalizarIdentificador($valor) {
    $valor = trim(strtolower((string) $valor));
    if ($valor === "") {
      return array("tipo" => "", "valor_normalizado" => "");
    }
    if (strpos($valor, "@") !== false) {
      return array(
        "tipo" => "correo",
        "valor_normalizado" => preg_replace('/\s+/', '', $valor)
      );
    }
    $soloDigitos = preg_replace('/\D+/', '', $valor);
    if ($soloDigitos !== "" && strlen($soloDigitos) >= 7) {
      return array(
        "tipo" => "telefono",
        "valor_normalizado" => $soloDigitos
      );
    }
    return array(
      "tipo" => "codigo",
      "valor_normalizado" => preg_replace('/\s+/', ' ', $valor)
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: simular busqueda express CRM por telefono, correo, codigo o nombre.
   * Impacto: POS podra consumir busqueda rapida sin depender de tablas legacy.
   * Contrato: read-only; usa CRM canonico si existe y reporta fuentes antiguas como pendientes.
   */
  public function buscarExpressDryRun($filtros = array()) {
    try {
      $db = $this->getConexion();
      $q = trim((string) $this->valor($filtros, "q", ""));
      $normalizado = $this->normalizarIdentificador($q);
      $resultados = array();
      $avisos = array();

      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para buscar clientes");
      }
      if ($q === "") {
        return $this->respuesta(false, "warning", "Captura telefono, correo, codigo o nombre", array(
          "resultados" => array(),
          "bloqueos" => array("busqueda_vacia")
        ));
      }

      if ($this->tablaExiste($db, "crm_clientes_maestro") && $this->tablaExiste($db, "crm_clientes_identificadores")) {
        $sql = "SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
                i.tipo, i.valor
            FROM crm_clientes_maestro c
            LEFT JOIN crm_clientes_identificadores i ON i.id_cliente_crm=c.id_cliente_crm
                AND i.estatus='activo'
            WHERE c.estatus IN ('activo','bloqueado','inactivo')
              AND (
                c.codigo_cliente=:codigo
                OR c.nombre_publico LIKE :nombre
                OR i.valor_normalizado=:normalizado
              )
            ORDER BY CASE WHEN i.valor_normalizado=:normalizado_orden THEN 0 ELSE 1 END,
                     c.id_cliente_crm DESC
            LIMIT 10";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
          ":codigo" => $q,
          ":nombre" => "%" . $q . "%",
          ":normalizado" => $normalizado["valor_normalizado"],
          ":normalizado_orden" => $normalizado["valor_normalizado"]
        ));
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } else {
        $avisos[] = "Esquema CRM canonico pendiente; busqueda real debe esperar crm_clientes_maestro.";
      }

      if ($this->tablaExiste($db, "erp_clientes")) {
        $avisos[] = "erp_clientes existe como fuente POS/UAT; no se consulta como canonica en CRM.";
      }
      if ($this->tablaExiste($db, "crm_clientes")) {
        $avisos[] = "crm_clientes legacy existe; requiere vinculo externo antes de mezclarse.";
      }

      return $this->respuesta(false, "success", "Busqueda express CRM simulada", array(
        "dry_run" => true,
        "q" => $q,
        "normalizado" => $normalizado,
        "resultados" => $resultados,
        "avisos" => $avisos,
        "requiere_alta_rapida" => empty($resultados),
        "solo_lectura" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: validar alta express CRM desde POS o CRM sin crear registros.
   * Impacto: mueve la responsabilidad de clientes desde POS/ERP hacia CRM canonico.
   * Contrato: dry-run; no inserta cliente, identificador, consentimiento, vinculo ni evento.
   */
  public function altaRapidaDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $nombre = trim((string) $this->valor($datos, "nombre_publico", $this->valor($datos, "nombre", "")));
      $identificador = trim((string) $this->valor($datos, "identificador", $this->valor($datos, "telefono", "")));
      $idSucursal = intval($this->valor($datos, "id_sucursal_alta", $this->valor($datos, "id_almacen", 0)));
      $idUsuario = intval($this->valor($datos, "id_usuario", 0));
      $origenAlta = trim((string) $this->valor($datos, "origen_alta", "pos"));
      $consentimientoContacto = intval($this->valor($datos, "consentimiento_contacto", 0)) === 1;
      $normalizado = $this->normalizarIdentificador($identificador);
      $bloqueos = array();
      $avisos = array();
      $tablasRequeridas = array("crm_clientes_maestro", "crm_clientes_identificadores", "crm_clientes_consentimientos", "crm_clientes_eventos");
      $faltantes = array();

      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para validar alta express CRM");
      }
      foreach ($tablasRequeridas as $tabla) {
        if (!$this->tablaExiste($db, $tabla)) {
          $faltantes[] = $tabla;
        }
      }
      if (!empty($faltantes)) {
        $bloqueos[] = "Esquema CRM Clientes pendiente de autorizacion";
      }
      if ($nombre === "" || strlen($nombre) < 2) {
        $bloqueos[] = "Captura nombre publico o alias del cliente";
      }
      if ($identificador === "" || $normalizado["valor_normalizado"] === "") {
        $bloqueos[] = "Captura telefono, correo o codigo de cliente";
      }
      if (!in_array($normalizado["tipo"], array("telefono", "correo", "codigo"), true)) {
        $bloqueos[] = "Tipo de identificador no soportado para alta express";
      }
      if ($normalizado["tipo"] === "telefono" && strlen($normalizado["valor_normalizado"]) < 10) {
        $bloqueos[] = "Telefono incompleto para alta express";
      }
      if ($normalizado["tipo"] === "correo" && strpos($normalizado["valor_normalizado"], "@") === false) {
        $bloqueos[] = "Correo invalido para alta express";
      }
      if (strpos($origenAlta, "pos") === 0 && $idSucursal <= 0) {
        $bloqueos[] = "Selecciona sucursal/almacen de alta";
      }
      if (!$consentimientoContacto) {
        $avisos[] = "Sin consentimiento de contacto: usar solo para identificacion operativa, no marketing";
      }

      $coincidencias = array();
      if (empty($faltantes) && $normalizado["valor_normalizado"] !== "") {
        $stmt = $db->prepare("SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
                   i.tipo, i.valor, i.valor_normalizado
            FROM crm_clientes_identificadores i
            INNER JOIN crm_clientes_maestro c ON c.id_cliente_crm=i.id_cliente_crm
            WHERE i.valor_normalizado=:valor AND i.estatus='activo'
            ORDER BY i.principal DESC, c.id_cliente_crm ASC
            LIMIT 10");
        $stmt->execute(array(":valor" => $normalizado["valor_normalizado"]));
        $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($coincidencias)) {
          $bloqueos[] = "Ya existe cliente CRM con ese identificador; selecciona coincidencia antes de crear";
        }
      }

      $origenCodigo = preg_replace('/[^A-Z0-9]+/', '', strtoupper($origenAlta));
      $codigoSugerido = empty($faltantes) ? $this->sugerirCodigoClienteCrm($db, $origenAlta) : "CRM-" . ($origenCodigo !== "" ? $origenCodigo : "CRM") . "-" . date("Ymd") . "-###";
      $clientePropuesto = array(
        "codigo_cliente" => $codigoSugerido,
        "tipo_cliente" => "persona",
        "nombre_publico" => $nombre,
        "nombre_legal" => null,
        "estatus" => "activo",
        "calidad_datos" => "express",
        "origen_alta" => $origenAlta !== "" ? $origenAlta : "pos",
        "id_sucursal_alta" => $idSucursal > 0 ? $idSucursal : null,
        "creado_por" => $idUsuario > 0 ? $idUsuario : null
      );
      $identificadorPropuesto = array(
        "tipo" => $normalizado["tipo"],
        "valor" => $identificador,
        "valor_normalizado" => $normalizado["valor_normalizado"],
        "principal" => 1,
        "verificado" => 0,
        "estatus" => "activo"
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Alta express CRM validada en dry-run" : "Alta express CRM con bloqueos", array(
        "dry_run" => true,
        "puede_crear" => empty($bloqueos),
        "bloqueos" => array_values(array_unique($bloqueos)),
        "avisos" => array_values(array_unique($avisos)),
        "faltantes" => $faltantes,
        "coincidencias" => $coincidencias,
        "cliente_propuesto" => $clientePropuesto,
        "identificador_propuesto" => $identificadorPropuesto,
        "contrato" => array(
          "no_escribe_bd" => true,
          "crea_crm_clientes_maestro" => true,
          "crea_crm_clientes_identificadores" => true,
          "puede_crear_consentimiento_operativo" => true,
          "puede_crear_evento_alta" => true,
          "no_mezcla_legacy_ecommerce" => true,
          "venta_puede_continuar_sin_cliente" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: crear cliente express CRM con identificador unico despues de autorizacion externa.
   * Impacto: habilita POS/CRM sobre cliente canonico sin usar `erp_clientes` como dueno.
   * Contrato: escribe BD; requiere flujo autorizado con respaldo externo antes de llamarse.
   */
  public function altaRapidaCrearAutorizado($datos = array()) {
    $db = $this->getConexion();
    $lockName = null;
    try {
      $preflight = $this->altaRapidaDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_crear"])) {
        return $this->respuesta(false, "warning", "Alta express CRM bloqueada por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $cliente = $depurar["cliente_propuesto"];
      $identificador = $depurar["identificador_propuesto"];
      $normalizado = trim((string) $identificador["valor_normalizado"]);
      $lockName = "crm_cliente_ident_" . sha1($normalizado);
      $stmtLock = $db->prepare("SELECT GET_LOCK(:lock_name, 10)");
      $stmtLock->execute(array(":lock_name" => $lockName));
      if (intval($stmtLock->fetchColumn()) !== 1) {
        return $this->respuesta(false, "warning", "No se pudo bloquear identificador para alta express CRM", array(
          "bloqueos" => array("lock_identificador_no_disponible")
        ));
      }

      $db->beginTransaction();
      $stmtDuplicado = $db->prepare("SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico
          FROM crm_clientes_identificadores i
          INNER JOIN crm_clientes_maestro c ON c.id_cliente_crm=i.id_cliente_crm
          WHERE i.valor_normalizado=:valor AND i.estatus='activo'
          LIMIT 1
          FOR UPDATE");
      $stmtDuplicado->execute(array(":valor" => $normalizado));
      $duplicado = $stmtDuplicado->fetch(PDO::FETCH_ASSOC);
      if ($duplicado) {
        throw new Exception("Ya existe cliente CRM con ese identificador: " . $duplicado["codigo_cliente"]);
      }

      $codigo = $this->sugerirCodigoClienteCrm($db, $cliente["origen_alta"]);
      $stmtCliente = $db->prepare("INSERT INTO crm_clientes_maestro
          (codigo_cliente, tipo_cliente, nombre_publico, nombre_legal, estatus, calidad_datos,
           origen_alta, id_sucursal_alta, id_lista_precio_default, id_segmento_default,
           creado_por, fecha_actualizacion)
          VALUES (:codigo, :tipo, :nombre, :legal, 'activo', 'express',
           :origen, :sucursal, NULL, NULL, :usuario, CURRENT_TIMESTAMP)");
      $stmtCliente->execute(array(
        ":codigo" => $codigo,
        ":tipo" => $cliente["tipo_cliente"],
        ":nombre" => $cliente["nombre_publico"],
        ":legal" => $cliente["nombre_legal"],
        ":origen" => $cliente["origen_alta"],
        ":sucursal" => $cliente["id_sucursal_alta"],
        ":usuario" => $cliente["creado_por"]
      ));
      $idClienteCrm = intval($db->lastInsertId());

      $stmtIdentificador = $db->prepare("INSERT INTO crm_clientes_identificadores
          (id_cliente_crm, tipo, valor, valor_normalizado, principal, verificado, estatus)
          VALUES (:cliente, :tipo, :valor, :normalizado, 1, 0, 'activo')");
      $stmtIdentificador->execute(array(
        ":cliente" => $idClienteCrm,
        ":tipo" => $identificador["tipo"],
        ":valor" => $identificador["valor"],
        ":normalizado" => $normalizado
      ));
      $idIdentificador = intval($db->lastInsertId());

      if (intval($this->valor($datos, "consentimiento_contacto", 0)) === 1) {
        $stmtConsentimiento = $db->prepare("INSERT INTO crm_clientes_consentimientos
            (id_cliente_crm, tipo, otorgado, medio, evidencia, registrado_por)
            VALUES (:cliente, 'contacto_operativo', 1, :medio, :evidencia, :usuario)");
        $stmtConsentimiento->execute(array(
          ":cliente" => $idClienteCrm,
          ":medio" => $cliente["origen_alta"],
          ":evidencia" => "alta_express_" . $cliente["origen_alta"],
          ":usuario" => $cliente["creado_por"]
        ));
      }

      $snapshot = json_encode(array(
        "cliente" => array(
          "id_cliente_crm" => $idClienteCrm,
          "codigo_cliente" => $codigo,
          "nombre_publico" => $cliente["nombre_publico"],
          "calidad_datos" => "express"
        ),
        "identificador" => $identificador,
        "origen_alta" => $cliente["origen_alta"]
      ), JSON_UNESCAPED_UNICODE);
      $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'alta_express', 'crm', :origen_tipo, :origen_id, :resumen, :snapshot, :usuario)");
      $stmtEvento->execute(array(
        ":cliente" => $idClienteCrm,
        ":origen_tipo" => $cliente["origen_alta"],
        ":origen_id" => $codigo,
        ":resumen" => "Alta express CRM " . $codigo,
        ":snapshot" => $snapshot,
        ":usuario" => $cliente["creado_por"]
      ));

      $db->commit();
      $this->liberarLockCliente($db, $lockName);
      return $this->respuesta(false, "success", "Cliente CRM creado en alta express", array(
        "id_cliente_crm" => $idClienteCrm,
        "codigo_cliente" => $codigo,
        "id_cliente_identificador" => $idIdentificador,
        "nombre_publico" => $cliente["nombre_publico"],
        "identificador" => $identificador,
        "contrato" => array(
          "crea_crm_clientes_maestro" => true,
          "crea_crm_clientes_identificadores" => true,
          "crea_evento_alta_express" => true,
          "no_crea_ficha_completa" => true,
          "no_mezcla_legacy_ecommerce" => true,
          "calidad_datos" => "express"
        )
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      if ($lockName !== null) {
        $this->liberarLockCliente($db, $lockName);
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: listar clientes CRM canonicos sin consultar legacy como fuente principal.
   * Impacto: alimenta listado operativo CRM y busqueda POS/CRM.
   * Contrato: read-only; no mezcla clientes legacy/POS salvo por vinculos explicitos futuros.
   */
  public function listarClientesCanonicos($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para listar clientes CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        return $this->respuesta(false, "warning", "Esquema CRM Clientes pendiente", array(
          "clientes" => array(),
          "total" => 0
        ));
      }
      $q = trim((string) $this->valor($filtros, "q", ""));
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 25))));
      $where = array("1=1");
      $params = array();
      if ($q !== "") {
        $normalizado = $this->normalizarIdentificador($q);
        $where[] = "(c.codigo_cliente=:codigo OR c.nombre_publico LIKE :q OR i.valor_normalizado=:normalizado)";
        $params[":codigo"] = $q;
        $params[":q"] = "%" . $q . "%";
        $params[":normalizado"] = $normalizado["valor_normalizado"];
      }
      $selectIdentificadores = $this->tablaExiste($db, "crm_clientes_identificadores")
        ? "(SELECT COUNT(*) FROM crm_clientes_identificadores ii WHERE ii.id_cliente_crm=c.id_cliente_crm AND ii.estatus='activo' AND COALESCE(ii.valor_normalizado, '') <> '') identificadores_activos"
        : "0 identificadores_activos";
      $selectContactos = $this->tablaExiste($db, "crm_clientes_contactos")
        ? "(SELECT COUNT(*) FROM crm_clientes_contactos cc WHERE cc.id_cliente_crm=c.id_cliente_crm AND cc.estatus='activo' AND cc.tipo IN ('telefono','whatsapp','correo') AND COALESCE(cc.valor, '') <> '') contactos_utiles,
           (SELECT COUNT(*) FROM crm_clientes_contactos cc WHERE cc.id_cliente_crm=c.id_cliente_crm AND cc.estatus='activo' AND cc.tipo IN ('telefono','whatsapp','correo') AND COALESCE(cc.valor, '') <> '' AND cc.permite_contacto=1) contactos_permitidos"
        : "0 contactos_utiles, 0 contactos_permitidos";
      $selectConsentimientos = $this->tablaExiste($db, "crm_clientes_consentimientos")
        ? "(SELECT COUNT(*) FROM crm_clientes_consentimientos cs WHERE cs.id_cliente_crm=c.id_cliente_crm AND cs.otorgado=1) consentimientos_otorgados"
        : "0 consentimientos_otorgados";
      $sql = "SELECT c.id_cliente_crm, c.codigo_cliente, c.tipo_cliente, c.nombre_publico, c.estatus,
              c.calidad_datos, c.origen_alta, c.fecha_registro, c.fecha_actualizacion,
              i.tipo identificador_tipo, i.valor identificador_valor,
              " . $selectIdentificadores . ",
              " . $selectContactos . ",
              " . $selectConsentimientos . "
          FROM crm_clientes_maestro c
          LEFT JOIN crm_clientes_identificadores i ON i.id_cliente_crm=c.id_cliente_crm
              AND i.principal=1 AND i.estatus='activo'
          WHERE " . implode(" AND ", $where) . "
          ORDER BY c.id_cliente_crm DESC
          LIMIT " . intval($limite);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($clientes as $idx => $cliente) {
        $clientes[$idx]["calidad_operativa_resumen"] = $this->evaluarCalidadListadoCliente($cliente);
      }
      return $this->respuesta(false, "success", "Clientes CRM listados", array(
        "clientes" => $clientes,
        "total" => count($clientes),
        "limite" => $limite,
        "solo_crm_canonico" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: resumir calidad CRM en listados sin consultar ficha completa.
   * Impacto: permite priorizar clientes nuevos reales por accion pendiente.
   * Contrato: funcion pura sobre una fila agregada; no escribe BD.
   */
  private function evaluarCalidadListadoCliente($cliente) {
    $identificadores = intval($this->valor($cliente, "identificadores_activos", 0));
    $contactos = intval($this->valor($cliente, "contactos_utiles", 0));
    $permitidos = intval($this->valor($cliente, "contactos_permitidos", 0));
    $consentimientos = intval($this->valor($cliente, "consentimientos_otorgados", 0));
    $legacy = (string) $this->valor($cliente, "origen_alta", "") === "legacy_crm_clientes";
    $pendiente = "";
    if ($identificadores <= 0) {
      $pendiente = "Agregar identificador";
    } elseif ($contactos <= 0) {
      $pendiente = "Agregar contacto";
    } elseif ($permitidos <= 0) {
      $pendiente = "Confirmar permiso";
    } elseif ($legacy) {
      $pendiente = "Revisar origen legacy";
    } elseif ($consentimientos <= 0) {
      $pendiente = "Registrar consentimiento";
    } else {
      $pendiente = "Ficha operativa";
    }

    return array(
      "pos" => $identificadores > 0,
      "contacto" => $contactos > 0 && $permitidos > 0,
      "comercial" => $contactos > 0 && $permitidos > 0 && $consentimientos > 0 && !$legacy,
      "pendiente_principal" => $pendiente,
      "identificadores_activos" => $identificadores,
      "contactos_utiles" => $contactos,
      "contactos_permitidos" => $permitidos,
      "consentimientos_otorgados" => $consentimientos,
      "legacy" => $legacy
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: generar cola read-only de calidad operativa CRM.
   * Impacto: ordena clientes que requieren completar ficha sin escribir ni migrar legacy.
   * Contrato: read-only; no crea tareas persistentes.
   */
  public function colaCalidadOperativa($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para cola de calidad CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        return $this->respuesta(false, "warning", "Esquema CRM Clientes pendiente", array(
          "resumen" => array(),
          "items" => array()
        ));
      }

      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 30))));
      $selectIdentificadores = $this->tablaExiste($db, "crm_clientes_identificadores")
        ? "(SELECT COUNT(*) FROM crm_clientes_identificadores ii WHERE ii.id_cliente_crm=c.id_cliente_crm AND ii.estatus='activo' AND COALESCE(ii.valor_normalizado, '') <> '') identificadores_activos"
        : "0 identificadores_activos";
      $selectIdentificadorPrincipal = $this->tablaExiste($db, "crm_clientes_identificadores")
        ? "i.tipo identificador_tipo, i.valor identificador_valor"
        : "NULL identificador_tipo, NULL identificador_valor";
      $joinIdentificadorPrincipal = $this->tablaExiste($db, "crm_clientes_identificadores")
        ? "LEFT JOIN crm_clientes_identificadores i ON i.id_cliente_crm=c.id_cliente_crm AND i.principal=1 AND i.estatus='activo'"
        : "";
      $selectContactos = $this->tablaExiste($db, "crm_clientes_contactos")
        ? "(SELECT COUNT(*) FROM crm_clientes_contactos cc WHERE cc.id_cliente_crm=c.id_cliente_crm AND cc.estatus='activo' AND cc.tipo IN ('telefono','whatsapp','correo') AND COALESCE(cc.valor, '') <> '') contactos_utiles,
              (SELECT COUNT(*) FROM crm_clientes_contactos cc WHERE cc.id_cliente_crm=c.id_cliente_crm AND cc.estatus='activo' AND cc.tipo IN ('telefono','whatsapp','correo') AND COALESCE(cc.valor, '') <> '' AND cc.permite_contacto=1) contactos_permitidos"
        : "0 contactos_utiles, 0 contactos_permitidos";
      $selectConsentimientos = $this->tablaExiste($db, "crm_clientes_consentimientos")
        ? "(SELECT COUNT(*) FROM crm_clientes_consentimientos cs WHERE cs.id_cliente_crm=c.id_cliente_crm AND cs.otorgado=1) consentimientos_otorgados"
        : "0 consentimientos_otorgados";
      $sql = "SELECT c.id_cliente_crm, c.codigo_cliente, c.tipo_cliente, c.nombre_publico, c.estatus,
              c.calidad_datos, c.origen_alta, c.fecha_registro, c.fecha_actualizacion,
              " . $selectIdentificadorPrincipal . ",
              " . $selectIdentificadores . ",
              " . $selectContactos . ",
              " . $selectConsentimientos . "
          FROM crm_clientes_maestro c
          " . $joinIdentificadorPrincipal . "
          ORDER BY c.id_cliente_crm DESC
          LIMIT " . intval($limite);
      $stmt = $db->prepare($sql);
      $stmt->execute();
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $resumen = array(
        "total_revisado" => count($rows),
        "pos_pendiente" => 0,
        "contacto_pendiente" => 0,
        "permiso_pendiente" => 0,
        "consentimiento_pendiente" => 0,
        "legacy_revision" => 0,
        "operativos" => 0
      );
      $items = array();
      foreach ($rows as $row) {
        $calidad = $this->evaluarCalidadListadoCliente($row);
        if (!$calidad["pos"]) {
          $resumen["pos_pendiente"]++;
        } elseif (intval($calidad["contactos_utiles"]) <= 0) {
          $resumen["contacto_pendiente"]++;
        } elseif (intval($calidad["contactos_permitidos"]) <= 0) {
          $resumen["permiso_pendiente"]++;
        } elseif (!empty($calidad["legacy"])) {
          $resumen["legacy_revision"]++;
        } elseif (intval($calidad["consentimientos_otorgados"]) <= 0) {
          $resumen["consentimiento_pendiente"]++;
        } else {
          $resumen["operativos"]++;
        }
        $items[] = array(
          "id_cliente_crm" => intval($row["id_cliente_crm"]),
          "codigo_cliente" => $row["codigo_cliente"],
          "nombre_publico" => $row["nombre_publico"],
          "estatus" => $row["estatus"],
          "origen_alta" => $row["origen_alta"],
          "identificador_tipo" => $row["identificador_tipo"],
          "identificador_valor" => $row["identificador_valor"],
          "calidad_operativa_resumen" => $calidad
        );
      }

      return $this->respuesta(false, "success", "Cola de calidad CRM generada", array(
        "resumen" => $resumen,
        "items" => $items,
        "limite" => $limite,
        "no_crea_tareas" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar una tarea de seguimiento CRM sin escribir datos.
   * Impacto: prepara paso de cola read-only a tareas persistentes con autorizacion futura.
   * Contrato: dry-run; no inserta en crm_clientes_tareas ni crea notificaciones SYS.
   */
  public function tareaSeguimientoDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
      $tipo = trim((string) $this->valor($datos, "tipo", "calidad_datos"));
      $prioridad = trim((string) $this->valor($datos, "prioridad", "normal"));
      $titulo = trim((string) $this->valor($datos, "titulo", ""));
      $descripcion = trim((string) $this->valor($datos, "descripcion", ""));
      $fechaVencimiento = trim((string) $this->valor($datos, "fecha_vencimiento", ""));
      $responsable = intval($this->valor($datos, "id_usuario_responsable", 0));
      $origenTipo = trim((string) $this->valor($datos, "origen_tipo", "cola_calidad"));
      $origenId = trim((string) $this->valor($datos, "origen_id", ""));
      $bloqueos = array();
      $avisos = array();
      $cliente = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar tarea CRM";
      }
      if ($idClienteCrm <= 0) {
        $bloqueos[] = "Cliente CRM invalido";
      }
      if (!in_array($tipo, array("calidad_datos", "contacto", "consentimiento", "postventa", "comercial", "garantia", "apartado", "devolucion", "otro"), true)) {
        $bloqueos[] = "Tipo de tarea CRM invalido";
      }
      if (!in_array($prioridad, array("baja", "normal", "alta", "urgente"), true)) {
        $bloqueos[] = "Prioridad de tarea invalida";
      }
      if ($titulo === "" || strlen($titulo) < 5) {
        $bloqueos[] = "Captura titulo de tarea mas descriptivo";
      }
      if ($fechaVencimiento !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $fechaVencimiento)) {
        $bloqueos[] = "Fecha de vencimiento invalida";
      }
      if ($responsable < 0) {
        $bloqueos[] = "Responsable invalido";
      }

      if ($db && $idClienteCrm > 0 && $this->tablaExiste($db, "crm_clientes_maestro")) {
        $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, origen_alta FROM crm_clientes_maestro WHERE id_cliente_crm=:cliente LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
          $bloqueos[] = "Cliente CRM no encontrado";
        } elseif ((string) $cliente["estatus"] === "bloqueado") {
          $avisos[] = "Cliente bloqueado; validar politica antes de crear seguimiento";
        }
      }

      if ($db && !$this->tablaExiste($db, "crm_clientes_tareas")) {
        $avisos[] = "Tabla crm_clientes_tareas pendiente; apply real requiere DDL CRM_CLIENTES_SEGUIMIENTO_DDL";
      }

      $tarea = array(
        "id_cliente_crm" => $idClienteCrm,
        "tipo" => $tipo,
        "prioridad" => $prioridad,
        "estatus" => "pendiente",
        "titulo" => $titulo,
        "descripcion" => $descripcion,
        "fecha_vencimiento" => $fechaVencimiento,
        "id_usuario_responsable" => $responsable > 0 ? $responsable : null,
        "origen_modulo" => "crm",
        "origen_tipo" => $origenTipo,
        "origen_id" => $origenId !== "" ? $origenId : (string) $idClienteCrm
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Tarea CRM validada en dry-run" : "Tarea CRM con bloqueos", array(
        "dry_run" => true,
        "puede_guardar" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "cliente" => $cliente,
        "tarea_propuesta" => $tarea,
        "requiere_ddl_seguimiento" => in_array("Tabla crm_clientes_tareas pendiente; apply real requiere DDL CRM_CLIENTES_SEGUIMIENTO_DDL", $avisos, true),
        "requiere_autorizacion_apply" => true,
        "no_crea_tarea" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear una tarea de seguimiento CRM despues de autorizacion fuerte.
   * Impacto: convierte hallazgos de calidad en seguimiento persistente auditable.
   * Contrato: escribe BD; requiere DDL seguimiento, respaldo y token en controlador.
   */
  public function tareaSeguimientoCrearAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para crear tarea CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_tareas")) {
        return $this->respuesta(true, "warning", "Tabla crm_clientes_tareas pendiente; primero autoriza CRM_CLIENTES_SEGUIMIENTO_DDL");
      }
      $preflight = $this->tareaSeguimientoDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Tarea CRM bloqueada por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $tarea = $depurar["tarea_propuesta"];
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO crm_clientes_tareas
          (id_cliente_crm, tipo, prioridad, estatus, titulo, descripcion, fecha_vencimiento,
           id_usuario_responsable, origen_modulo, origen_tipo, origen_id, creado_por)
          VALUES (:cliente, :tipo, :prioridad, 'pendiente', :titulo, :descripcion, :fecha_vencimiento,
           :responsable, :origen_modulo, :origen_tipo, :origen_id, :usuario)");
      $stmt->execute(array(
        ":cliente" => intval($tarea["id_cliente_crm"]),
        ":tipo" => $tarea["tipo"],
        ":prioridad" => $tarea["prioridad"],
        ":titulo" => $tarea["titulo"],
        ":descripcion" => $tarea["descripcion"] !== "" ? $tarea["descripcion"] : null,
        ":fecha_vencimiento" => $tarea["fecha_vencimiento"] !== "" ? $tarea["fecha_vencimiento"] : null,
        ":responsable" => $tarea["id_usuario_responsable"],
        ":origen_modulo" => $tarea["origen_modulo"],
        ":origen_tipo" => $tarea["origen_tipo"],
        ":origen_id" => $tarea["origen_id"],
        ":usuario" => $idUsuario
      ));
      $idTarea = intval($db->lastInsertId());

      if ($this->tablaExiste($db, "crm_clientes_eventos")) {
        $snapshot = json_encode(array("id_cliente_tarea" => $idTarea, "tarea" => $tarea), JSON_UNESCAPED_UNICODE);
        $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'tarea_creada', 'crm', 'cliente_tarea', :origen_id, :resumen, :snapshot, :usuario)");
        $stmtEvento->execute(array(
          ":cliente" => intval($tarea["id_cliente_crm"]),
          ":origen_id" => (string) $idTarea,
          ":resumen" => "Tarea CRM creada: " . $tarea["titulo"],
          ":snapshot" => $snapshot,
          ":usuario" => $idUsuario
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Tarea CRM creada", array(
        "id_cliente_crm" => intval($tarea["id_cliente_crm"]),
        "id_cliente_tarea" => $idTarea,
        "tarea" => $tarea,
        "crea_evento" => true,
        "no_crea_notificacion_sys" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: listar tareas CRM en modo lectura.
   * Impacto: prepara bandeja operativa de seguimiento sin modificar tareas ni clientes.
   * Contrato: read-only; no crea, no cierra y no reasigna tareas.
   */
  public function tareasSeguimientoListar($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para listar tareas CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_tareas")) {
        return $this->respuesta(false, "warning", "Esquema CRM Seguimiento pendiente", array(
          "resumen" => array(
            "pendientes" => 0,
            "vencidas" => 0,
            "alta_prioridad" => 0,
            "requiere_ddl_seguimiento" => true
          ),
          "tareas" => array(),
          "no_escribe_bd" => true
        ));
      }

      $estatus = trim((string) $this->valor($filtros, "estatus", "pendiente"));
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 30))));
      if (!in_array($estatus, array("pendiente", "en_proceso", "cerrada", "cancelada", "todas"), true)) {
        $estatus = "pendiente";
      }
      $where = array("1=1");
      $params = array();
      if ($estatus !== "todas") {
        $where[] = "t.estatus=:estatus";
        $params[":estatus"] = $estatus;
      }

      $sql = "SELECT t.id_cliente_tarea, t.id_cliente_crm, t.tipo, t.prioridad, t.estatus, t.titulo,
              t.descripcion, t.fecha_programada, t.fecha_vencimiento, t.fecha_cierre,
              t.resultado_cierre, t.id_usuario_responsable, t.origen_modulo, t.origen_tipo, t.origen_id,
              t.fecha_registro, t.fecha_actualizacion,
              c.codigo_cliente, c.nombre_publico, c.estatus cliente_estatus
          FROM crm_clientes_tareas t
          LEFT JOIN crm_clientes_maestro c ON c.id_cliente_crm=t.id_cliente_crm
          WHERE " . implode(" AND ", $where) . "
          ORDER BY
            CASE t.prioridad WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
            CASE WHEN t.fecha_vencimiento IS NULL THEN 1 ELSE 0 END,
            t.fecha_vencimiento ASC,
            t.id_cliente_tarea DESC
          LIMIT " . intval($limite);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $resumen = array(
        "pendientes" => 0,
        "vencidas" => 0,
        "alta_prioridad" => 0,
        "requiere_ddl_seguimiento" => false
      );
      $ahora = date("Y-m-d H:i:s");
      foreach ($tareas as $idx => $tarea) {
        $vencida = !empty($tarea["fecha_vencimiento"]) && $tarea["fecha_vencimiento"] < $ahora && !in_array($tarea["estatus"], array("cerrada", "cancelada"), true);
        $alta = in_array($tarea["prioridad"], array("alta", "urgente"), true);
        if ((string) $tarea["estatus"] === "pendiente") {
          $resumen["pendientes"]++;
        }
        if ($vencida) {
          $resumen["vencidas"]++;
        }
        if ($alta) {
          $resumen["alta_prioridad"]++;
        }
        $tareas[$idx]["vencida"] = $vencida;
        $tareas[$idx]["alta_prioridad"] = $alta;
      }

      return $this->respuesta(false, "success", "Tareas CRM listadas", array(
        "resumen" => $resumen,
        "tareas" => $tareas,
        "limite" => $limite,
        "estatus" => $estatus,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: resumir preparacion comercial CRM sin escribir datos.
   * Impacto: permite medir segmentacion, listas y condiciones antes de usarlas en POS/ventas.
   * Contrato: read-only; no modifica clientes, segmentos ni condiciones.
   */
  public function resumenComercialClientes($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para resumen comercial CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        return $this->respuesta(false, "warning", "Esquema CRM Clientes pendiente", array("resumen" => array()));
      }

      $resumen = array(
        "clientes_total" => $this->contarTabla($db, "crm_clientes_maestro"),
        "clientes_con_segmento_default" => 0,
        "clientes_con_lista_default" => 0,
        "segmentos_activos" => 0,
        "relaciones_segmento_activas" => 0,
        "condiciones_comerciales" => 0,
        "requiere_ddl_comercial" => !$this->tablaExiste($db, "crm_clientes_condiciones"),
        "no_escribe_bd" => true
      );

      $stmt = $db->prepare("SELECT
          SUM(CASE WHEN id_segmento_default IS NOT NULL THEN 1 ELSE 0 END) clientes_con_segmento_default,
          SUM(CASE WHEN id_lista_precio_default IS NOT NULL THEN 1 ELSE 0 END) clientes_con_lista_default
        FROM crm_clientes_maestro");
      $stmt->execute();
      $base = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($base) {
        $resumen["clientes_con_segmento_default"] = intval($base["clientes_con_segmento_default"]);
        $resumen["clientes_con_lista_default"] = intval($base["clientes_con_lista_default"]);
      }
      if ($this->tablaExiste($db, "crm_clientes_segmentos")) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_segmentos WHERE estatus='activo'");
        $stmt->execute();
        $resumen["segmentos_activos"] = intval($stmt->fetchColumn());
      }
      if ($this->tablaExiste($db, "crm_clientes_segmentos_rel")) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_segmentos_rel WHERE estatus='activo'");
        $stmt->execute();
        $resumen["relaciones_segmento_activas"] = intval($stmt->fetchColumn());
      }
      if ($this->tablaExiste($db, "crm_clientes_condiciones")) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_condiciones WHERE estatus='activo'");
        $stmt->execute();
        $resumen["condiciones_comerciales"] = intval($stmt->fetchColumn());
      }

      return $this->respuesta(false, "success", "Resumen comercial CRM generado", array(
        "resumen" => $resumen,
        "contrato" => array(
          "crm_dueno_segmentacion" => true,
          "pos_solo_consume_condiciones_aprobadas" => true,
          "listas_no_se_modifican_desde_crm" => true,
          "recompensas_y_garantias_relacionables_al_cliente" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-16
   * Proposito: listar segmentos CRM configurables con conteo de clientes sin escribir datos.
   * Impacto: permite administrar tipos de cliente para listas de precios sin hardcodearlos.
   * Contrato: read-only; no crea, pausa ni cancela segmentos.
   */
  public function segmentosCatalogoReadOnly($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para consultar segmentos CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_segmentos")) {
        return $this->respuesta(false, "warning", "Catalogo de segmentos CRM pendiente", array("segmentos" => array(), "schema_pendiente" => true));
      }

      $q = trim((string) $this->valor($filtros, "q", ""));
      $estatus = trim((string) $this->valor($filtros, "estatus", ""));
      $tipo = trim((string) $this->valor($filtros, "tipo", ""));
      $limite = intval($this->valor($filtros, "limite", 100));
      $limite = $limite > 0 && $limite <= 200 ? $limite : 100;
      $where = array("1=1");
      $params = array();
      if ($q !== "") {
        $where[] = "(s.codigo LIKE :q OR s.nombre LIKE :q OR s.descripcion LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      if ($estatus !== "") {
        $where[] = "s.estatus=:estatus";
        $params[":estatus"] = $estatus;
      }
      if ($tipo !== "") {
        $where[] = "s.tipo=:tipo";
        $params[":tipo"] = $tipo;
      }
      $conteoClientes = $this->tablaExiste($db, "crm_clientes_segmentos_rel")
        ? "(SELECT COUNT(*) FROM crm_clientes_segmentos_rel r WHERE r.id_segmento_crm=s.id_segmento_crm AND r.estatus='activo') clientes_activos"
        : "0 clientes_activos";
      $stmt = $db->prepare("SELECT s.id_segmento_crm, s.codigo, s.nombre, s.tipo, s.descripcion, s.estatus,
          s.fecha_registro, s.fecha_actualizacion, $conteoClientes
        FROM crm_clientes_segmentos s
        WHERE " . implode(" AND ", $where) . "
        ORDER BY s.estatus='activo' DESC, s.tipo ASC, s.nombre ASC
        LIMIT " . intval($limite));
      $stmt->execute($params);
      return $this->respuesta(false, "success", "Segmentos CRM consultados", array(
        "segmentos" => $stmt->fetchAll(PDO::FETCH_ASSOC),
        "schema_pendiente" => false,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-16
   * Proposito: validar alta/edicion/cancelacion de segmento CRM configurable sin escribir BD.
   * Impacto: prepara catalogo de tipos de cliente para listas de precios, CRM y futuras recompensas.
   * Contrato: dry-run; no modifica segmentos ni clientes.
   */
  public function segmentoCatalogoDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idSegmento = intval($this->valor($datos, "id_segmento_crm", 0));
      $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
      $nombre = trim((string) $this->valor($datos, "nombre", ""));
      $tipo = trim((string) $this->valor($datos, "tipo", "comercial"));
      $descripcion = trim((string) $this->valor($datos, "descripcion", ""));
      $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
      $bloqueos = array();
      $avisos = array();
      $actual = null;
      $duplicado = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar segmento CRM";
      }
      if ($db && !$this->tablaExiste($db, "crm_clientes_segmentos")) {
        $bloqueos[] = "Tabla crm_clientes_segmentos pendiente";
      }
      if (!preg_match('/^[A-Z0-9_\\-]{3,60}$/', $codigo)) {
        $bloqueos[] = "Codigo obligatorio de 3 a 60 caracteres; usar letras, numeros, guion o guion bajo";
      }
      if ($nombre === "" || strlen($nombre) > 160) {
        $bloqueos[] = "Nombre obligatorio de maximo 160 caracteres";
      }
      if (!in_array($tipo, array("comercial", "operativo", "marketing", "postventa", "riesgo", "otro"), true)) {
        $bloqueos[] = "Tipo de segmento no permitido";
      }
      if (!in_array($estatus, array("activo", "pausado", "cancelado"), true)) {
        $bloqueos[] = "Estatus de segmento no permitido";
      }
      if ($estatus === "cancelado") {
        $avisos[] = "Cancelar segmento no elimina relaciones historicas; solo lo deja fuera de nuevas reglas";
      }

      if ($db && $this->tablaExiste($db, "crm_clientes_segmentos")) {
        if ($idSegmento > 0) {
          $stmt = $db->prepare("SELECT * FROM crm_clientes_segmentos WHERE id_segmento_crm=:id LIMIT 1");
          $stmt->execute(array(":id" => $idSegmento));
          $actual = $stmt->fetch(PDO::FETCH_ASSOC);
          if (!$actual) {
            $bloqueos[] = "Segmento CRM no encontrado para edicion";
          }
        }
        if ($codigo !== "") {
          $stmt = $db->prepare("SELECT id_segmento_crm, codigo, nombre, estatus FROM crm_clientes_segmentos WHERE codigo=:codigo AND id_segmento_crm<>:id LIMIT 1");
          $stmt->execute(array(":codigo" => $codigo, ":id" => $idSegmento));
          $duplicado = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($duplicado) {
            $bloqueos[] = "Ya existe un segmento CRM con ese codigo";
          }
        }
      }

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Segmento CRM valido en dry-run" : "Segmento CRM con bloqueos", array(
        "dry_run" => true,
        "puede_guardar" => empty($bloqueos),
        "segmento_normalizado" => array(
          "id_segmento_crm" => $idSegmento,
          "codigo" => $codigo,
          "nombre" => $nombre,
          "tipo" => $tipo,
          "descripcion" => $descripcion,
          "estatus" => $estatus
        ),
        "actual" => $actual,
        "duplicado" => $duplicado,
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-16
   * Proposito: guardar segmento CRM configurable despues de autorizacion fuerte.
   * Impacto: permite crear, editar, pausar o cancelar tipos de cliente sin hardcodear reglas.
   * Contrato: escribe BD; requiere respaldo/token en controlador y no modifica listas ni ventas.
   */
  public function segmentoCatalogoGuardarAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para guardar segmento CRM");
      }
      $preflight = $this->segmentoCatalogoDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      if (!empty($preflight["error"]) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Segmento CRM bloqueado por preflight", array("preflight" => $preflight));
      }
      $segmento = $depurar["segmento_normalizado"];
      $idSegmento = intval($segmento["id_segmento_crm"]);
      if ($idSegmento > 0) {
        $stmt = $db->prepare("UPDATE crm_clientes_segmentos
          SET codigo=:codigo, nombre=:nombre, tipo=:tipo, descripcion=:descripcion, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_segmento_crm=:id");
        $stmt->execute(array(
          ":codigo" => $segmento["codigo"],
          ":nombre" => $segmento["nombre"],
          ":tipo" => $segmento["tipo"],
          ":descripcion" => $segmento["descripcion"] !== "" ? $segmento["descripcion"] : null,
          ":estatus" => $segmento["estatus"],
          ":id" => $idSegmento
        ));
      } else {
        $stmt = $db->prepare("INSERT INTO crm_clientes_segmentos
          (codigo, nombre, tipo, descripcion, estatus)
          VALUES (:codigo, :nombre, :tipo, :descripcion, :estatus)");
        $stmt->execute(array(
          ":codigo" => $segmento["codigo"],
          ":nombre" => $segmento["nombre"],
          ":tipo" => $segmento["tipo"],
          ":descripcion" => $segmento["descripcion"] !== "" ? $segmento["descripcion"] : null,
          ":estatus" => $segmento["estatus"]
        ));
        $idSegmento = intval($db->lastInsertId());
      }
      return $this->respuesta(false, "success", "Segmento CRM guardado", array(
        "id_segmento_crm" => $idSegmento,
        "no_modifica_clientes" => true,
        "no_modifica_listas" => true,
        "no_toca_pos_ventas" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: generar reportes operativos CRM sin escribir datos.
   * Impacto: muestra contactabilidad, campanas, recompensas/garantias y bloqueos antes de automatizar procesos.
   * Contrato: read-only; no crea tareas, segmentos, condiciones ni snapshots.
   */
  public function reportesOperativosClientes($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para reportes CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        return $this->respuesta(false, "warning", "Esquema CRM Clientes pendiente", array(
          "resumen" => array(),
          "no_escribe_bd" => true
        ));
      }

      $total = $this->contarTabla($db, "crm_clientes_maestro");
      $resumen = array(
        "clientes_total" => intval($total),
        "identificables_pos" => 0,
        "contactables_operativos" => 0,
        "aptos_campana" => 0,
        "pendientes_contacto" => 0,
        "pendientes_consentimiento" => 0,
        "legacy_no_campanas" => 0,
        "bloqueados_comercial" => 0,
        "elegibles_recompensas" => 0,
        "elegibles_garantia_extendida" => 0,
        "requiere_ddl_comercial" => !$this->tablaExiste($db, "crm_clientes_condiciones"),
        "no_escribe_bd" => true
      );

      if ($this->tablaExiste($db, "crm_clientes_identificadores")) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT id_cliente_crm)
          FROM crm_clientes_identificadores
          WHERE estatus='activo' AND COALESCE(valor_normalizado, '') <> ''");
        $stmt->execute();
        $resumen["identificables_pos"] = intval($stmt->fetchColumn());
      }

      if ($this->tablaExiste($db, "crm_clientes_contactos")) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT id_cliente_crm)
          FROM crm_clientes_contactos
          WHERE estatus='activo' AND tipo IN ('telefono','whatsapp','correo')
            AND COALESCE(valor, '') <> '' AND permite_contacto=1");
        $stmt->execute();
        $resumen["contactables_operativos"] = intval($stmt->fetchColumn());
      }

      if ($this->tablaExiste($db, "crm_clientes_consentimientos")) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT id_cliente_crm)
          FROM crm_clientes_consentimientos
          WHERE otorgado=1 AND tipo IN ('marketing','contacto_comercial','contacto_operativo','whatsapp')");
        $stmt->execute();
        $conConsentimiento = intval($stmt->fetchColumn());
        $resumen["pendientes_consentimiento"] = max(0, intval($total) - $conConsentimiento);

        $sqlCampana = "SELECT COUNT(DISTINCT c.id_cliente_crm)
          FROM crm_clientes_maestro c
          INNER JOIN crm_clientes_contactos co ON co.id_cliente_crm=c.id_cliente_crm
            AND co.estatus='activo' AND co.tipo IN ('telefono','whatsapp','correo')
            AND COALESCE(co.valor, '') <> '' AND co.permite_contacto=1
          INNER JOIN crm_clientes_consentimientos cs ON cs.id_cliente_crm=c.id_cliente_crm
            AND cs.otorgado=1 AND cs.tipo IN ('marketing','contacto_comercial','contacto_operativo','whatsapp')
          WHERE c.estatus='activo' AND c.origen_alta<>'legacy_crm_clientes'";
        if ($this->tablaExiste($db, "crm_clientes_condiciones")) {
          $sqlCampana .= " AND NOT EXISTS (
            SELECT 1 FROM crm_clientes_condiciones cc
            WHERE cc.id_cliente_crm=c.id_cliente_crm AND cc.estatus='activo' AND cc.bloqueo_comercial=1
          )";
        }
        $stmt = $db->prepare($sqlCampana);
        $stmt->execute();
        $resumen["aptos_campana"] = intval($stmt->fetchColumn());
      } else {
        $resumen["pendientes_consentimiento"] = intval($total);
      }

      $resumen["pendientes_contacto"] = max(0, intval($total) - intval($resumen["contactables_operativos"]));

      $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_maestro WHERE origen_alta='legacy_crm_clientes'");
      $stmt->execute();
      $resumen["legacy_no_campanas"] = intval($stmt->fetchColumn());

      if ($this->tablaExiste($db, "crm_clientes_condiciones")) {
        $stmt = $db->prepare("SELECT
            SUM(CASE WHEN bloqueo_comercial=1 AND estatus='activo' THEN 1 ELSE 0 END) bloqueados,
            SUM(CASE WHEN permite_recompensas=1 AND bloqueo_comercial=0 AND estatus='activo' THEN 1 ELSE 0 END) recompensas,
            SUM(CASE WHEN permite_garantia_extendida=1 AND bloqueo_comercial=0 AND estatus='activo' THEN 1 ELSE 0 END) garantias
          FROM crm_clientes_condiciones");
        $stmt->execute();
        $cond = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cond) {
          $resumen["bloqueados_comercial"] = intval($cond["bloqueados"]);
          $resumen["elegibles_recompensas"] = intval($cond["recompensas"]);
          $resumen["elegibles_garantia_extendida"] = intval($cond["garantias"]);
        }
      } else {
        $resumen["elegibles_recompensas"] = intval($resumen["aptos_campana"]);
        $resumen["elegibles_garantia_extendida"] = intval($resumen["identificables_pos"]);
      }

      $indicadores = array(
        array(
          "clave" => "contactabilidad",
          "titulo" => "Clientes contactables",
          "valor" => $resumen["contactables_operativos"],
          "total" => $total,
          "riesgo" => $resumen["pendientes_contacto"] > 0 ? "medio" : "bajo"
        ),
        array(
          "clave" => "campanas",
          "titulo" => "Aptos para campanas",
          "valor" => $resumen["aptos_campana"],
          "total" => $total,
          "riesgo" => $resumen["aptos_campana"] <= 0 ? "alto" : "bajo"
        ),
        array(
          "clave" => "legacy",
          "titulo" => "Legacy fuera de campanas",
          "valor" => $resumen["legacy_no_campanas"],
          "total" => $total,
          "riesgo" => $resumen["legacy_no_campanas"] > 0 ? "medio" : "bajo"
        ),
        array(
          "clave" => "condiciones",
          "titulo" => "Condiciones comerciales",
          "valor" => $resumen["requiere_ddl_comercial"] ? 0 : ($resumen["elegibles_recompensas"] + $resumen["bloqueados_comercial"]),
          "total" => $total,
          "riesgo" => $resumen["requiere_ddl_comercial"] ? "medio" : "bajo"
        )
      );

      return $this->respuesta(false, "success", "Reportes operativos CRM generados", array(
        "resumen" => $resumen,
        "indicadores" => $indicadores,
        "contrato" => array(
          "solo_lectura" => true,
          "no_crea_tareas" => true,
          "no_modifica_clientes" => true,
          "no_usa_legacy_para_campanas" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar preferencias de contacto CRM sin escribir datos.
   * Impacto: separa preferencias operativas de consentimiento legal y de POS.
   * Contrato: dry-run; no actualiza crm_clientes_condiciones.
   */
  public function preferenciasContactoDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
      $canalPreferido = trim((string) $this->valor($datos, "canal_preferido", "whatsapp"));
      $canalesPermitidosRaw = trim((string) $this->valor($datos, "canales_permitidos", ""));
      $horario = trim((string) $this->valor($datos, "horario_contacto", ""));
      $frecuencia = trim((string) $this->valor($datos, "frecuencia_contacto", "normal"));
      $temasRaw = trim((string) $this->valor($datos, "temas_interes", ""));
      $noContactar = intval($this->valor($datos, "no_contactar", 0)) === 1;
      $motivoNoContactar = trim((string) $this->valor($datos, "motivo_no_contactar", ""));
      $bloqueos = array();
      $avisos = array();
      $cliente = null;
      $condicion = null;
      $canalesValidos = array("whatsapp", "telefono", "correo", "presencial", "ninguno");
      $frecuenciasValidas = array("baja", "normal", "alta", "solo_operativo");

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar preferencias CRM";
      }
      if ($idClienteCrm <= 0) {
        $bloqueos[] = "Cliente CRM invalido";
      }
      if (!in_array($canalPreferido, $canalesValidos, true)) {
        $bloqueos[] = "Canal preferido invalido";
      }
      if (!in_array($frecuencia, $frecuenciasValidas, true)) {
        $bloqueos[] = "Frecuencia de contacto invalida";
      }
      if ($horario !== "" && strlen($horario) > 120) {
        $bloqueos[] = "Horario de contacto demasiado largo";
      }
      if ($noContactar && $motivoNoContactar === "") {
        $bloqueos[] = "Captura motivo si el cliente no debe contactarse";
      }

      $canalesPermitidos = array();
      foreach (explode(",", $canalesPermitidosRaw) as $canal) {
        $canal = trim($canal);
        if ($canal !== "") {
          if (!in_array($canal, $canalesValidos, true)) {
            $bloqueos[] = "Canal permitido invalido: " . $canal;
          } else {
            $canalesPermitidos[] = $canal;
          }
        }
      }
      $canalesPermitidos = array_values(array_unique($canalesPermitidos));
      if (empty($canalesPermitidos) && !$noContactar) {
        $canalesPermitidos[] = $canalPreferido;
      }
      if ($noContactar) {
        $canalPreferido = "ninguno";
        $canalesPermitidos = array("ninguno");
        $avisos[] = "No contactar bloquea campanas aunque existan consentimientos.";
      }

      $temas = array();
      foreach (explode(",", $temasRaw) as $tema) {
        $tema = trim($tema);
        if ($tema !== "") {
          $temas[] = substr($tema, 0, 80);
        }
      }
      $temas = array_values(array_unique($temas));

      if ($db && $idClienteCrm > 0 && $this->tablaExiste($db, "crm_clientes_maestro")) {
        $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus FROM crm_clientes_maestro WHERE id_cliente_crm=:cliente LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
          $bloqueos[] = "Cliente CRM no encontrado";
        } elseif ((string) $cliente["estatus"] === "bloqueado") {
          $avisos[] = "Cliente bloqueado; preferencias solo deben usarse con revision operativa.";
        }
      }
      if ($db && !$this->tablaExiste($db, "crm_clientes_condiciones")) {
        $avisos[] = "Tabla crm_clientes_condiciones pendiente; apply real requiere DDL CRM_CLIENTES_COMERCIAL_DDL";
      }
      if ($db && $this->tablaExiste($db, "crm_clientes_condiciones") && $idClienteCrm > 0) {
        $stmt = $db->prepare("SELECT * FROM crm_clientes_condiciones WHERE id_cliente_crm=:cliente LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $condicion = $stmt->fetch(PDO::FETCH_ASSOC);
      }

      $preferencias = array(
        "canal_preferido" => $canalPreferido,
        "canales_permitidos" => $canalesPermitidos,
        "horario_contacto" => $horario,
        "frecuencia_contacto" => $frecuencia,
        "temas_interes" => $temas,
        "no_contactar" => $noContactar,
        "motivo_no_contactar" => $motivoNoContactar
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Preferencias CRM validadas en dry-run" : "Preferencias CRM con bloqueos", array(
        "dry_run" => true,
        "puede_guardar" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "cliente" => $cliente,
        "condicion_actual" => $condicion,
        "preferencias_propuestas" => $preferencias,
        "requiere_ddl_comercial" => in_array("Tabla crm_clientes_condiciones pendiente; apply real requiere DDL CRM_CLIENTES_COMERCIAL_DDL", $avisos, true),
        "no_otorga_consentimiento" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: guardar preferencias CRM despues de autorizacion fuerte.
   * Impacto: actualiza condiciones comerciales sin tocar consentimiento legal ni POS.
   * Contrato: escribe BD; requiere DDL comercial, respaldo y token en controlador.
   */
  public function preferenciasContactoGuardarAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para guardar preferencias CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_condiciones")) {
        return $this->respuesta(true, "warning", "Tabla crm_clientes_condiciones pendiente; primero autoriza CRM_CLIENTES_COMERCIAL_DDL");
      }
      $preflight = $this->preferenciasContactoDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Preferencias CRM bloqueadas por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $preferencias = $depurar["preferencias_propuestas"];
      $preferenciasJson = json_encode($preferencias, JSON_UNESCAPED_UNICODE);

      $db->beginTransaction();
      if (!empty($depurar["condicion_actual"])) {
        $stmt = $db->prepare("UPDATE crm_clientes_condiciones
          SET preferencias=:preferencias, actualizado_por=:usuario, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_cliente_crm=:cliente");
        $stmt->execute(array(":preferencias" => $preferenciasJson, ":usuario" => $idUsuario, ":cliente" => $idClienteCrm));
      } else {
        $stmt = $db->prepare("INSERT INTO crm_clientes_condiciones
          (id_cliente_crm, preferencias, estatus, creado_por)
          VALUES (:cliente, :preferencias, 'activo', :usuario)");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":preferencias" => $preferenciasJson, ":usuario" => $idUsuario));
      }

      if ($this->tablaExiste($db, "crm_clientes_eventos")) {
        $snapshot = json_encode(array("preferencias" => $preferencias), JSON_UNESCAPED_UNICODE);
        $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'preferencias_actualizadas', 'crm', 'cliente_preferencias', :origen_id, :resumen, :snapshot, :usuario)");
        $stmtEvento->execute(array(
          ":cliente" => $idClienteCrm,
          ":origen_id" => (string) $idClienteCrm,
          ":resumen" => "Preferencias CRM actualizadas",
          ":snapshot" => $snapshot,
          ":usuario" => $idUsuario
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Preferencias CRM guardadas", array(
        "id_cliente_crm" => $idClienteCrm,
        "preferencias" => $preferencias,
        "no_otorga_consentimiento" => true,
        "no_toca_pos_ventas" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: resumir recompensas CRM sin escribir datos.
   * Impacto: mide elegibilidad y preparacion de cuentas antes de conectar ventas/POS.
   * Contrato: read-only; no crea programas, cuentas ni movimientos.
   */
  public function resumenRecompensasClientes($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para recompensas CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        return $this->respuesta(false, "warning", "Esquema CRM Clientes pendiente", array("resumen" => array()));
      }
      $total = intval($this->contarTabla($db, "crm_clientes_maestro"));
      $resumen = array(
        "clientes_total" => $total,
        "programas_activos" => 0,
        "cuentas_activas" => 0,
        "movimientos_aplicados" => 0,
        "saldo_puntos_total" => 0,
        "elegibles_recompensas" => 0,
        "bloqueados_recompensas" => 0,
        "legacy_no_elegible" => 0,
        "requiere_ddl_recompensas" => !$this->tablaExiste($db, "crm_clientes_recompensas_cuentas"),
        "requiere_ddl_comercial" => !$this->tablaExiste($db, "crm_clientes_condiciones"),
        "no_escribe_bd" => true
      );

      $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_maestro WHERE origen_alta='legacy_crm_clientes'");
      $stmt->execute();
      $resumen["legacy_no_elegible"] = intval($stmt->fetchColumn());

      if ($this->tablaExiste($db, "crm_clientes_condiciones")) {
        $stmt = $db->prepare("SELECT
            SUM(CASE WHEN permite_recompensas=1 AND bloqueo_comercial=0 AND estatus='activo' THEN 1 ELSE 0 END) elegibles,
            SUM(CASE WHEN (permite_recompensas=0 OR bloqueo_comercial=1) AND estatus='activo' THEN 1 ELSE 0 END) bloqueados
          FROM crm_clientes_condiciones");
        $stmt->execute();
        $cond = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cond) {
          $resumen["elegibles_recompensas"] = intval($cond["elegibles"]);
          $resumen["bloqueados_recompensas"] = intval($cond["bloqueados"]);
        }
      } else {
        $resumen["elegibles_recompensas"] = max(0, $total - intval($resumen["legacy_no_elegible"]));
      }

      if ($this->tablaExiste($db, "crm_recompensas_programas")) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM crm_recompensas_programas WHERE estatus='activo'");
        $stmt->execute();
        $resumen["programas_activos"] = intval($stmt->fetchColumn());
      }
      if ($this->tablaExiste($db, "crm_clientes_recompensas_cuentas")) {
        $stmt = $db->prepare("SELECT COUNT(*), COALESCE(SUM(saldo_puntos), 0)
          FROM crm_clientes_recompensas_cuentas
          WHERE estatus='activo'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if ($row) {
          $resumen["cuentas_activas"] = intval($row[0]);
          $resumen["saldo_puntos_total"] = floatval($row[1]);
        }
      }
      if ($this->tablaExiste($db, "crm_clientes_recompensas_movimientos")) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_recompensas_movimientos WHERE estatus='aplicado'");
        $stmt->execute();
        $resumen["movimientos_aplicados"] = intval($stmt->fetchColumn());
      }

      return $this->respuesta(false, "success", "Resumen CRM Recompensas generado", array(
        "resumen" => $resumen,
        "contrato" => array(
          "solo_lectura" => true,
          "no_otorga_puntos" => true,
          "no_redime_puntos" => true,
          "legacy_no_elegible_sin_revision" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: listar detalle operativo de recompensas CRM sin escribir datos.
   * Impacto: alimenta pantalla dedicada de programas, cuentas y movimientos.
   * Contrato: read-only; no crea programas, cuentas, movimientos ni cambia saldos.
   */
  public function detalleRecompensasClientes($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para detalle de recompensas CRM");
      }

      $resumenRespuesta = $this->resumenRecompensasClientes($filtros);
      $resumen = isset($resumenRespuesta["depurar"]["resumen"]) ? $resumenRespuesta["depurar"]["resumen"] : array();
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 25))));

      $programas = array();
      if ($this->tablaExiste($db, "crm_recompensas_programas")) {
        $stmt = $db->prepare("SELECT id_programa_recompensa, codigo, nombre, tipo, estatus, fecha_registro
          FROM crm_recompensas_programas
          ORDER BY estatus='activo' DESC, id_programa_recompensa DESC
          LIMIT " . intval($limite));
        $stmt->execute();
        $programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      $cuentas = array();
      if ($this->tablaExiste($db, "crm_clientes_recompensas_cuentas")) {
        $stmt = $db->prepare("SELECT c.id_cliente_recompensa_cuenta, c.id_cliente_crm, cm.codigo_cliente, cm.nombre_publico,
            c.id_programa_recompensa, p.codigo programa_codigo, p.nombre programa_nombre, p.tipo programa_tipo,
            c.saldo_puntos, c.saldo_monetario_equivalente, c.nivel, c.estatus, c.fecha_alta, c.fecha_actualizacion
          FROM crm_clientes_recompensas_cuentas c
          LEFT JOIN crm_clientes_maestro cm ON cm.id_cliente_crm=c.id_cliente_crm
          LEFT JOIN crm_recompensas_programas p ON p.id_programa_recompensa=c.id_programa_recompensa
          ORDER BY c.estatus='activo' DESC, c.fecha_actualizacion DESC, c.id_cliente_recompensa_cuenta DESC
          LIMIT " . intval($limite));
        $stmt->execute();
        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      $movimientos = array();
      if ($this->tablaExiste($db, "crm_clientes_recompensas_movimientos")) {
        $stmt = $db->prepare("SELECT m.id_cliente_recompensa_movimiento, m.id_cliente_recompensa_cuenta, m.id_cliente_crm,
            cm.codigo_cliente, cm.nombre_publico, p.codigo programa_codigo, p.nombre programa_nombre,
            m.tipo, m.puntos, m.saldo_resultante, m.origen_modulo, m.origen_tipo, m.origen_id,
            m.descripcion, m.estatus, m.fecha_registro
          FROM crm_clientes_recompensas_movimientos m
          LEFT JOIN crm_clientes_maestro cm ON cm.id_cliente_crm=m.id_cliente_crm
          LEFT JOIN crm_clientes_recompensas_cuentas c ON c.id_cliente_recompensa_cuenta=m.id_cliente_recompensa_cuenta
          LEFT JOIN crm_recompensas_programas p ON p.id_programa_recompensa=c.id_programa_recompensa
          ORDER BY m.fecha_registro DESC, m.id_cliente_recompensa_movimiento DESC
          LIMIT " . intval($limite));
        $stmt->execute();
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      return $this->respuesta(false, "success", "Detalle CRM Recompensas generado", array(
        "resumen" => $resumen,
        "programas" => $programas,
        "cuentas" => $cuentas,
        "movimientos" => $movimientos,
        "limite" => $limite,
        "no_escribe_bd" => true,
        "no_otorga_puntos" => true,
        "no_redime_puntos" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar movimiento de recompensas sin escribir datos.
   * Impacto: define contrato futuro para ventas, ajustes y redenciones.
   * Contrato: dry-run; no crea cuenta, no crea movimiento y no cambia saldos.
   */
  public function recompensaMovimientoDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
      $idPrograma = intval($this->valor($datos, "id_programa_recompensa", 0));
      $tipo = trim((string) $this->valor($datos, "tipo", "acumulacion"));
      $puntos = floatval($this->valor($datos, "puntos", 0));
      $origenModulo = trim((string) $this->valor($datos, "origen_modulo", "crm"));
      $origenTipo = trim((string) $this->valor($datos, "origen_tipo", "dryrun"));
      $origenId = trim((string) $this->valor($datos, "origen_id", ""));
      $descripcion = trim((string) $this->valor($datos, "descripcion", ""));
      $bloqueos = array();
      $avisos = array();
      $cliente = null;
      $programa = null;
      $cuenta = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar recompensas CRM";
      }
      if ($idClienteCrm <= 0) {
        $bloqueos[] = "Cliente CRM invalido";
      }
      if (!in_array($tipo, array("acumulacion", "redencion", "ajuste", "caducidad"), true)) {
        $bloqueos[] = "Tipo de movimiento de recompensa invalido";
      }
      if ($puntos <= 0) {
        $bloqueos[] = "Puntos deben ser mayores a cero";
      }
      if ($descripcion === "" || strlen($descripcion) < 5) {
        $bloqueos[] = "Captura descripcion de movimiento";
      }
      if (in_array($origenModulo, array("pos", "ventas"), true)) {
        $bloqueos[] = "Origen POS/Ventas aun no autorizado para movimientos de recompensas";
      }
      foreach (array("crm_recompensas_programas", "crm_clientes_recompensas_cuentas", "crm_clientes_recompensas_movimientos") as $tabla) {
        if ($db && !$this->tablaExiste($db, $tabla)) {
          $avisos[] = "Tabla pendiente para recompensas: " . $tabla;
        }
      }

      if ($db && $idClienteCrm > 0 && $this->tablaExiste($db, "crm_clientes_maestro")) {
        $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, origen_alta FROM crm_clientes_maestro WHERE id_cliente_crm=:cliente LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
          $bloqueos[] = "Cliente CRM no encontrado";
        } else {
          if ((string) $cliente["estatus"] !== "activo") {
            $bloqueos[] = "Cliente CRM no activo";
          }
          if ((string) $cliente["origen_alta"] === "legacy_crm_clientes") {
            $avisos[] = "Cliente legacy no debe recibir recompensas sin revision puntual";
          }
        }
      }
      if ($db && $this->tablaExiste($db, "crm_clientes_condiciones") && $idClienteCrm > 0) {
        $stmt = $db->prepare("SELECT permite_recompensas, bloqueo_comercial, motivo_bloqueo FROM crm_clientes_condiciones WHERE id_cliente_crm=:cliente AND estatus='activo' LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cond = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cond && (intval($cond["permite_recompensas"]) !== 1 || intval($cond["bloqueo_comercial"]) === 1)) {
          $bloqueos[] = "Cliente no elegible para recompensas por condiciones comerciales";
        }
      }
      if ($db && $idPrograma > 0 && $this->tablaExiste($db, "crm_recompensas_programas")) {
        $stmt = $db->prepare("SELECT id_programa_recompensa, codigo, nombre, tipo, estatus FROM crm_recompensas_programas WHERE id_programa_recompensa=:programa LIMIT 1");
        $stmt->execute(array(":programa" => $idPrograma));
        $programa = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$programa) {
          $bloqueos[] = "Programa de recompensas no encontrado";
        } elseif ((string) $programa["estatus"] !== "activo") {
          $bloqueos[] = "Programa de recompensas no activo";
        }
      } elseif ($idPrograma <= 0) {
        $avisos[] = "Programa no indicado; dry-run solo valida forma del movimiento";
      }
      if ($db && $idClienteCrm > 0 && $idPrograma > 0 && $this->tablaExiste($db, "crm_clientes_recompensas_cuentas")) {
        $stmt = $db->prepare("SELECT * FROM crm_clientes_recompensas_cuentas WHERE id_cliente_crm=:cliente AND id_programa_recompensa=:programa LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":programa" => $idPrograma));
        $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cuenta && $tipo !== "acumulacion") {
          $bloqueos[] = "Cuenta de recompensas inexistente para movimiento no acumulable";
        }
        if ($cuenta && in_array($tipo, array("redencion", "caducidad"), true) && floatval($cuenta["saldo_puntos"]) < $puntos) {
          $bloqueos[] = "Saldo insuficiente para movimiento de salida";
        }
      }

      $saldoActual = $cuenta ? floatval($cuenta["saldo_puntos"]) : 0;
      $delta = in_array($tipo, array("redencion", "caducidad"), true) ? -1 * $puntos : $puntos;
      $movimiento = array(
        "id_cliente_crm" => $idClienteCrm,
        "id_programa_recompensa" => $idPrograma > 0 ? $idPrograma : null,
        "tipo" => $tipo,
        "puntos" => $puntos,
        "saldo_actual" => $saldoActual,
        "saldo_resultante" => $saldoActual + $delta,
        "origen_modulo" => $origenModulo,
        "origen_tipo" => $origenTipo,
        "origen_id" => $origenId,
        "descripcion" => $descripcion
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Movimiento de recompensa validado en dry-run" : "Movimiento de recompensa con bloqueos", array(
        "dry_run" => true,
        "puede_aplicar" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => array_values(array_unique($avisos)),
        "cliente" => $cliente,
        "programa" => $programa,
        "cuenta" => $cuenta,
        "movimiento_propuesto" => $movimiento,
        "requiere_ddl_recompensas" => !$db || !$this->tablaExiste($db, "crm_clientes_recompensas_movimientos"),
        "no_crea_movimiento" => true,
        "no_cambia_saldo" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: aplicar movimiento CRM Recompensas solo desde flujo autorizado.
   * Impacto: inserta movimiento y actualiza saldo de cuenta en transaccion.
   * Contrato: escribe BD; no conecta POS/Ventas y requiere respaldo/token en controlador.
   */
  public function recompensaMovimientoCrearAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para crear movimiento de recompensas");
      }
      $preflight = $this->recompensaMovimientoDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_aplicar"])) {
        return $this->respuesta(false, "warning", "Movimiento de recompensas bloqueado por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $movimiento = $depurar["movimiento_propuesto"];
      $cuenta = isset($depurar["cuenta"]) && is_array($depurar["cuenta"]) ? $depurar["cuenta"] : null;
      if (!$cuenta || empty($cuenta["id_cliente_recompensa_cuenta"])) {
        return $this->respuesta(false, "warning", "No existe cuenta de recompensas para aplicar movimiento");
      }

      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $snapshot = array(
        "cliente" => isset($depurar["cliente"]) ? $depurar["cliente"] : null,
        "programa" => isset($depurar["programa"]) ? $depurar["programa"] : null,
        "cuenta_antes" => $cuenta,
        "movimiento" => $movimiento
      );

      $db->beginTransaction();
      $stmt = $db->prepare("UPDATE crm_clientes_recompensas_cuentas
        SET saldo_puntos=:saldo, fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_cliente_recompensa_cuenta=:cuenta AND id_cliente_crm=:cliente AND id_programa_recompensa=:programa");
      $stmt->execute(array(
        ":saldo" => floatval($movimiento["saldo_resultante"]),
        ":cuenta" => intval($cuenta["id_cliente_recompensa_cuenta"]),
        ":cliente" => intval($movimiento["id_cliente_crm"]),
        ":programa" => intval($movimiento["id_programa_recompensa"])
      ));

      $stmt = $db->prepare("INSERT INTO crm_clientes_recompensas_movimientos
        (id_cliente_recompensa_cuenta, id_cliente_crm, tipo, puntos, saldo_resultante, origen_modulo, origen_tipo, origen_id, descripcion, datos_snapshot, estatus, creado_por)
        VALUES (:cuenta, :cliente, :tipo, :puntos, :saldo, :origen_modulo, :origen_tipo, :origen_id, :descripcion, :snapshot, 'aplicado', :usuario)");
      $stmt->execute(array(
        ":cuenta" => intval($cuenta["id_cliente_recompensa_cuenta"]),
        ":cliente" => intval($movimiento["id_cliente_crm"]),
        ":tipo" => $movimiento["tipo"],
        ":puntos" => floatval($movimiento["puntos"]),
        ":saldo" => floatval($movimiento["saldo_resultante"]),
        ":origen_modulo" => $movimiento["origen_modulo"],
        ":origen_tipo" => $movimiento["origen_tipo"],
        ":origen_id" => $movimiento["origen_id"],
        ":descripcion" => $movimiento["descripcion"],
        ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
        ":usuario" => $idUsuario
      ));
      $idMovimiento = intval($db->lastInsertId());

      if ($this->tablaExiste($db, "crm_clientes_eventos")) {
        $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'recompensas_movimiento_aplicado', 'crm', 'recompensas_movimiento', :origen_id, :resumen, :snapshot, :usuario)");
        $stmtEvento->execute(array(
          ":cliente" => intval($movimiento["id_cliente_crm"]),
          ":origen_id" => (string)$idMovimiento,
          ":resumen" => "Movimiento CRM Recompensas aplicado",
          ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
          ":usuario" => $idUsuario
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Movimiento de recompensas CRM aplicado", array(
        "id_cliente_recompensa_movimiento" => $idMovimiento,
        "id_cliente_recompensa_cuenta" => intval($cuenta["id_cliente_recompensa_cuenta"]),
        "id_cliente_crm" => intval($movimiento["id_cliente_crm"]),
        "tipo" => $movimiento["tipo"],
        "puntos" => floatval($movimiento["puntos"]),
        "saldo_resultante" => floatval($movimiento["saldo_resultante"]),
        "origen_modulo" => $movimiento["origen_modulo"],
        "no_toca_pos_ventas" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar un programa de recompensas CRM sin escribir datos.
   * Impacto: permite revisar politica base antes de crear programas reales.
   * Contrato: dry-run; no crea programa, cuentas ni movimientos.
   */
  public function recompensaProgramaDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
      $nombre = trim((string) $this->valor($datos, "nombre", ""));
      $tipo = trim((string) $this->valor($datos, "tipo", "puntos"));
      $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
      $reglasEntrada = $this->valor($datos, "reglas", array());
      $bloqueos = array();
      $avisos = array();
      $programaExistente = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar programa de recompensas";
      }
      if ($db && !$this->tablaExiste($db, "crm_recompensas_programas")) {
        $bloqueos[] = "Tabla crm_recompensas_programas pendiente";
      }
      if ($codigo === "" || !preg_match('/^[A-Z0-9_\\-]{3,60}$/', $codigo)) {
        $bloqueos[] = "Codigo de programa invalido";
      }
      if ($nombre === "" || strlen($nombre) < 4 || strlen($nombre) > 160) {
        $bloqueos[] = "Nombre de programa invalido";
      }
      if (!in_array($tipo, array("puntos", "monedero", "niveles", "mixto"), true)) {
        $bloqueos[] = "Tipo de programa invalido";
      }
      if (!in_array($estatus, array("activo", "pausado", "inactivo"), true)) {
        $bloqueos[] = "Estatus de programa invalido";
      }

      $reglas = $this->normalizarReglasProgramaRecompensas($reglasEntrada, $avisos, $bloqueos);
      if (empty($reglas["acumulacion"]["base"])) {
        $avisos[] = "Regla de acumulacion sin base definida; se recomienda monto_pagado antes de conectar POS";
      }
      if (empty($reglas["redencion"]["modo"])) {
        $avisos[] = "Regla de redencion pendiente; no habilitar redenciones hasta definir equivalencia";
      }

      if ($db && $codigo !== "" && $this->tablaExiste($db, "crm_recompensas_programas")) {
        $stmt = $db->prepare("SELECT id_programa_recompensa, codigo, nombre, tipo, estatus
          FROM crm_recompensas_programas
          WHERE codigo=:codigo
          LIMIT 1");
        $stmt->execute(array(":codigo" => $codigo));
        $programaExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($programaExistente) {
          $bloqueos[] = "Ya existe un programa de recompensas con ese codigo";
        }
      }

      $propuesta = array(
        "codigo" => $codigo,
        "nombre" => $nombre,
        "tipo" => $tipo,
        "estatus" => $estatus,
        "reglas" => $reglas
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Programa de recompensas validado en dry-run" : "Programa de recompensas con bloqueos", array(
        "dry_run" => true,
        "puede_crear" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => array_values(array_unique($avisos)),
        "programa_existente" => $programaExistente,
        "programa_propuesto" => $propuesta,
        "requiere_autorizacion_apply" => true,
        "no_crea_programa" => true,
        "no_crea_cuentas" => true,
        "no_otorga_puntos" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear un programa CRM Recompensas solo desde flujo autorizado.
   * Impacto: habilita catalogo de programas; no crea cuentas ni movimientos.
   * Contrato: escribe BD; requiere respaldo y token en controlador.
   */
  public function recompensaProgramaCrearAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para crear programa de recompensas");
      }
      $preflight = $this->recompensaProgramaDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_crear"])) {
        return $this->respuesta(false, "warning", "Creacion de programa de recompensas bloqueada por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $programa = $depurar["programa_propuesto"];
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $stmt = $db->prepare("INSERT INTO crm_recompensas_programas
        (codigo, nombre, tipo, reglas, estatus, creado_por)
        VALUES (:codigo, :nombre, :tipo, :reglas, :estatus, :usuario)");
      $stmt->execute(array(
        ":codigo" => $programa["codigo"],
        ":nombre" => $programa["nombre"],
        ":tipo" => $programa["tipo"],
        ":reglas" => json_encode($programa["reglas"], JSON_UNESCAPED_UNICODE),
        ":estatus" => $programa["estatus"],
        ":usuario" => $idUsuario
      ));
      $idPrograma = intval($db->lastInsertId());

      return $this->respuesta(false, "success", "Programa de recompensas CRM creado", array(
        "id_programa_recompensa" => $idPrograma,
        "programa" => $programa,
        "no_crea_cuentas" => true,
        "no_crea_movimientos" => true,
        "no_otorga_puntos" => true,
        "no_toca_pos_ventas" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar alta de cuenta CRM Recompensas sin escribir datos.
   * Impacto: prepara elegibilidad por cliente/programa antes de otorgar puntos.
   * Contrato: dry-run; no crea cuenta ni movimiento y no cambia saldos.
   */
  public function recompensaCuentaDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
      $idPrograma = intval($this->valor($datos, "id_programa_recompensa", 0));
      $nivel = trim((string) $this->valor($datos, "nivel", ""));
      $bloqueos = array();
      $avisos = array();
      $cliente = null;
      $programa = null;
      $condicion = null;
      $cuentaExistente = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar cuenta de recompensas";
      }
      if ($idClienteCrm <= 0) {
        $bloqueos[] = "Cliente CRM invalido";
      }
      if ($idPrograma <= 0) {
        $bloqueos[] = "Programa de recompensas invalido";
      }
      foreach (array("crm_clientes_maestro", "crm_recompensas_programas", "crm_clientes_recompensas_cuentas") as $tabla) {
        if ($db && !$this->tablaExiste($db, $tabla)) {
          $bloqueos[] = "Tabla requerida pendiente: " . $tabla;
        }
      }
      if ($nivel !== "" && !preg_match('/^[A-Za-z0-9_\\- ]{1,60}$/', $nivel)) {
        $bloqueos[] = "Nivel de recompensa invalido";
      }

      if ($db && empty($bloqueos)) {
        $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, origen_alta
          FROM crm_clientes_maestro
          WHERE id_cliente_crm=:cliente
          LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
          $bloqueos[] = "Cliente CRM no encontrado";
        } elseif ((string)$cliente["estatus"] !== "activo") {
          $bloqueos[] = "Cliente CRM no activo";
        } elseif ((string)$cliente["origen_alta"] === "legacy_crm_clientes") {
          $bloqueos[] = "Cliente legacy no elegible para recompensas sin revision puntual";
        }

        $stmt = $db->prepare("SELECT id_programa_recompensa, codigo, nombre, tipo, estatus
          FROM crm_recompensas_programas
          WHERE id_programa_recompensa=:programa
          LIMIT 1");
        $stmt->execute(array(":programa" => $idPrograma));
        $programa = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$programa) {
          $bloqueos[] = "Programa de recompensas no encontrado";
        } elseif ((string)$programa["estatus"] !== "activo") {
          $bloqueos[] = "Programa de recompensas no activo";
        }

        if ($this->tablaExiste($db, "crm_clientes_condiciones")) {
          $stmt = $db->prepare("SELECT permite_recompensas, bloqueo_comercial, motivo_bloqueo, estatus
            FROM crm_clientes_condiciones
            WHERE id_cliente_crm=:cliente AND estatus='activo'
            LIMIT 1");
          $stmt->execute(array(":cliente" => $idClienteCrm));
          $condicion = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($condicion && (intval($condicion["permite_recompensas"]) !== 1 || intval($condicion["bloqueo_comercial"]) === 1)) {
            $bloqueos[] = "Cliente no elegible para recompensas por condiciones comerciales";
          }
        } else {
          $avisos[] = "Condiciones comerciales no aplicadas; elegibilidad fina queda pendiente";
        }

        $stmt = $db->prepare("SELECT id_cliente_recompensa_cuenta, saldo_puntos, estatus, nivel
          FROM crm_clientes_recompensas_cuentas
          WHERE id_cliente_crm=:cliente AND id_programa_recompensa=:programa
          LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":programa" => $idPrograma));
        $cuentaExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cuentaExistente) {
          $bloqueos[] = "El cliente ya tiene cuenta para este programa";
        }
      }

      $propuesta = array(
        "id_cliente_crm" => $idClienteCrm,
        "id_programa_recompensa" => $idPrograma,
        "saldo_puntos" => 0,
        "saldo_monetario_equivalente" => 0,
        "nivel" => $nivel !== "" ? $nivel : null,
        "estatus" => "activo"
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Cuenta de recompensas validada en dry-run" : "Cuenta de recompensas con bloqueos", array(
        "dry_run" => true,
        "puede_crear" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => array_values(array_unique($avisos)),
        "cliente" => $cliente,
        "programa" => $programa,
        "condicion" => $condicion,
        "cuenta_existente" => $cuentaExistente,
        "cuenta_propuesta" => $propuesta,
        "requiere_autorizacion_apply" => true,
        "no_crea_cuenta" => true,
        "no_crea_movimiento" => true,
        "no_otorga_puntos" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear cuenta CRM Recompensas solo desde flujo autorizado.
   * Impacto: habilita una cuenta con saldo cero para cliente canonico.
   * Contrato: escribe BD; requiere respaldo y token en controlador.
   */
  public function recompensaCuentaCrearAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para crear cuenta de recompensas");
      }
      $preflight = $this->recompensaCuentaDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_crear"])) {
        return $this->respuesta(false, "warning", "Creacion de cuenta de recompensas bloqueada por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $cuenta = $depurar["cuenta_propuesta"];
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO crm_clientes_recompensas_cuentas
        (id_cliente_crm, id_programa_recompensa, saldo_puntos, saldo_monetario_equivalente, nivel, estatus)
        VALUES (:cliente, :programa, 0, 0, :nivel, 'activo')");
      $stmt->execute(array(
        ":cliente" => intval($cuenta["id_cliente_crm"]),
        ":programa" => intval($cuenta["id_programa_recompensa"]),
        ":nivel" => $cuenta["nivel"]
      ));
      $idCuenta = intval($db->lastInsertId());

      if ($this->tablaExiste($db, "crm_clientes_eventos")) {
        $snapshot = json_encode(array("cuenta" => $cuenta, "programa" => $depurar["programa"]), JSON_UNESCAPED_UNICODE);
        $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'recompensas_cuenta_creada', 'crm', 'recompensas_cuenta', :origen_id, :resumen, :snapshot, :usuario)");
        $stmtEvento->execute(array(
          ":cliente" => intval($cuenta["id_cliente_crm"]),
          ":origen_id" => (string)$idCuenta,
          ":resumen" => "Cuenta CRM Recompensas creada con saldo cero",
          ":snapshot" => $snapshot,
          ":usuario" => $idUsuario
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Cuenta de recompensas CRM creada", array(
        "id_cliente_recompensa_cuenta" => $idCuenta,
        "id_cliente_crm" => intval($cuenta["id_cliente_crm"]),
        "id_programa_recompensa" => intval($cuenta["id_programa_recompensa"]),
        "saldo_inicial" => 0,
        "no_crea_movimiento" => true,
        "no_otorga_puntos" => true,
        "no_toca_pos_ventas" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar asignacion de segmento CRM sin escribir.
   * Impacto: prepara clasificacion comercial de clientes canonicos nuevos.
   * Contrato: dry-run; no inserta relacion ni actualiza segmento default.
   */
  public function segmentoAsignarDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
      $idSegmentoCrm = intval($this->valor($datos, "id_segmento_crm", 0));
      $principal = intval($this->valor($datos, "principal", 1)) === 1 ? 1 : 0;
      $actualizarDefault = intval($this->valor($datos, "actualizar_default", $principal)) === 1;
      $bloqueos = array();
      $avisos = array();
      $cliente = null;
      $segmento = null;
      $relacionExistente = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar segmento CRM";
      }
      if ($idClienteCrm <= 0) {
        $bloqueos[] = "Cliente CRM invalido";
      }
      if ($idSegmentoCrm <= 0) {
        $bloqueos[] = "Segmento CRM invalido";
      }
      foreach (array("crm_clientes_maestro", "crm_clientes_segmentos", "crm_clientes_segmentos_rel") as $tabla) {
        if ($db && !$this->tablaExiste($db, $tabla)) {
          $bloqueos[] = "Tabla requerida pendiente: " . $tabla;
        }
      }

      if ($db && empty($bloqueos)) {
        $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, origen_alta FROM crm_clientes_maestro WHERE id_cliente_crm=:cliente LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
          $bloqueos[] = "Cliente CRM no encontrado";
        } elseif ((string) $cliente["estatus"] === "bloqueado") {
          $avisos[] = "Cliente bloqueado; revisar politica antes de segmentar";
        }

        $stmt = $db->prepare("SELECT id_segmento_crm, codigo, nombre, tipo, estatus FROM crm_clientes_segmentos WHERE id_segmento_crm=:segmento LIMIT 1");
        $stmt->execute(array(":segmento" => $idSegmentoCrm));
        $segmento = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$segmento) {
          $bloqueos[] = "Segmento CRM no encontrado";
        } elseif ((string) $segmento["estatus"] !== "activo") {
          $bloqueos[] = "Segmento CRM no activo";
        }

        $stmt = $db->prepare("SELECT id_cliente_segmento, principal, estatus
            FROM crm_clientes_segmentos_rel
            WHERE id_cliente_crm=:cliente AND id_segmento_crm=:segmento AND estatus='activo'
            LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":segmento" => $idSegmentoCrm));
        $relacionExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($relacionExistente) {
          $bloqueos[] = "El cliente ya tiene ese segmento activo";
        }
      }

      $propuesta = array(
        "id_cliente_crm" => $idClienteCrm,
        "id_segmento_crm" => $idSegmentoCrm,
        "principal" => $principal,
        "actualizar_default" => $actualizarDefault ? 1 : 0,
        "estatus" => "activo"
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Segmento CRM validado en dry-run" : "Segmento CRM con bloqueos", array(
        "dry_run" => true,
        "puede_guardar" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "cliente" => $cliente,
        "segmento" => $segmento,
        "relacion_existente" => $relacionExistente,
        "propuesta" => $propuesta,
        "requiere_autorizacion_apply" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: asignar segmento CRM despues de autorizacion fuerte.
   * Impacto: crea relacion comercial auditable sin tocar POS ni ventas.
   * Contrato: escribe BD; requiere respaldo y token en controlador.
   */
  public function segmentoAsignarAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para asignar segmento CRM");
      }
      $preflight = $this->segmentoAsignarDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Asignacion de segmento CRM bloqueada por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $propuesta = $depurar["propuesta"];
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $db->beginTransaction();
      if (intval($propuesta["principal"]) === 1) {
        $stmt = $db->prepare("UPDATE crm_clientes_segmentos_rel
            SET principal=0
            WHERE id_cliente_crm=:cliente AND estatus='activo'");
        $stmt->execute(array(":cliente" => intval($propuesta["id_cliente_crm"])));
      }
      $stmt = $db->prepare("INSERT INTO crm_clientes_segmentos_rel
          (id_cliente_crm, id_segmento_crm, principal, estatus, creado_por)
          VALUES (:cliente, :segmento, :principal, 'activo', :usuario)");
      $stmt->execute(array(
        ":cliente" => intval($propuesta["id_cliente_crm"]),
        ":segmento" => intval($propuesta["id_segmento_crm"]),
        ":principal" => intval($propuesta["principal"]),
        ":usuario" => $idUsuario
      ));
      $idRelacion = intval($db->lastInsertId());

      if (intval($propuesta["actualizar_default"]) === 1) {
        $stmt = $db->prepare("UPDATE crm_clientes_maestro
            SET id_segmento_default=:segmento, actualizado_por=:usuario, fecha_actualizacion=CURRENT_TIMESTAMP
            WHERE id_cliente_crm=:cliente");
        $stmt->execute(array(
          ":segmento" => intval($propuesta["id_segmento_crm"]),
          ":usuario" => $idUsuario,
          ":cliente" => intval($propuesta["id_cliente_crm"])
        ));
      }
      if ($this->tablaExiste($db, "crm_clientes_eventos")) {
        $snapshot = json_encode(array("segmento" => $depurar["segmento"], "propuesta" => $propuesta), JSON_UNESCAPED_UNICODE);
        $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'segmento_asignado', 'crm', 'cliente_segmento', :origen_id, :resumen, :snapshot, :usuario)");
        $stmtEvento->execute(array(
          ":cliente" => intval($propuesta["id_cliente_crm"]),
          ":origen_id" => (string) $idRelacion,
          ":resumen" => "Segmento CRM asignado",
          ":snapshot" => $snapshot,
          ":usuario" => $idUsuario
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Segmento CRM asignado", array(
        "id_cliente_crm" => intval($propuesta["id_cliente_crm"]),
        "id_cliente_segmento" => $idRelacion,
        "id_segmento_crm" => intval($propuesta["id_segmento_crm"]),
        "actualizo_default" => intval($propuesta["actualizar_default"]) === 1,
        "no_modifica_listas" => true,
        "no_toca_pos_ventas" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar cambio de estatus de una tarea CRM sin escribir datos.
   * Impacto: define ciclo de vida de seguimiento antes de permitir cierres reales.
   * Contrato: dry-run; no actualiza crm_clientes_tareas ni crea eventos.
   */
  public function tareaEstatusDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idTarea = intval($this->valor($datos, "id_cliente_tarea", 0));
      $estatusNuevo = trim((string) $this->valor($datos, "estatus", ""));
      $resultado = trim((string) $this->valor($datos, "resultado_cierre", ""));
      $nota = trim((string) $this->valor($datos, "nota", ""));
      $bloqueos = array();
      $avisos = array();
      $tarea = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar tarea CRM";
      }
      if ($idTarea <= 0) {
        $bloqueos[] = "Tarea CRM invalida";
      }
      if (!in_array($estatusNuevo, array("en_proceso", "cerrada", "cancelada"), true)) {
        $bloqueos[] = "Estatus destino invalido";
      }
      if (in_array($estatusNuevo, array("cerrada", "cancelada"), true) && $resultado === "") {
        $bloqueos[] = "Captura resultado de cierre o cancelacion";
      }

      if ($db && !$this->tablaExiste($db, "crm_clientes_tareas")) {
        $bloqueos[] = "Tabla crm_clientes_tareas pendiente; primero autoriza CRM_CLIENTES_SEGUIMIENTO_DDL";
      }
      if ($db && $idTarea > 0 && $this->tablaExiste($db, "crm_clientes_tareas")) {
        $stmt = $db->prepare("SELECT t.*, c.codigo_cliente, c.nombre_publico
            FROM crm_clientes_tareas t
            LEFT JOIN crm_clientes_maestro c ON c.id_cliente_crm=t.id_cliente_crm
            WHERE t.id_cliente_tarea=:tarea
            LIMIT 1");
        $stmt->execute(array(":tarea" => $idTarea));
        $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tarea) {
          $bloqueos[] = "Tarea CRM no encontrada";
        } elseif (in_array((string) $tarea["estatus"], array("cerrada", "cancelada"), true)) {
          $bloqueos[] = "La tarea ya esta cerrada o cancelada";
        } elseif ((string) $tarea["estatus"] === $estatusNuevo) {
          $avisos[] = "La tarea ya esta en el estatus solicitado";
        }
      }

      $cambio = array(
        "id_cliente_tarea" => $idTarea,
        "estatus" => $estatusNuevo,
        "resultado_cierre" => $resultado,
        "nota" => $nota,
        "fecha_cierre" => in_array($estatusNuevo, array("cerrada", "cancelada"), true) ? date("Y-m-d H:i:s") : null
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Cambio de tarea CRM validado en dry-run" : "Cambio de tarea CRM con bloqueos", array(
        "dry_run" => true,
        "puede_guardar" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "tarea" => $tarea,
        "cambio_propuesto" => $cambio,
        "requiere_autorizacion_apply" => true,
        "no_modifica_tarea" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: cambiar estatus de una tarea CRM despues de autorizacion fuerte.
   * Impacto: permite cerrar/cancelar seguimiento sin modificar la ficha del cliente.
   * Contrato: escribe BD; requiere respaldo y token en controlador.
   */
  public function tareaEstatusAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para actualizar tarea CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_tareas")) {
        return $this->respuesta(true, "warning", "Tabla crm_clientes_tareas pendiente; primero autoriza CRM_CLIENTES_SEGUIMIENTO_DDL");
      }
      $preflight = $this->tareaEstatusDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Cambio de tarea CRM bloqueado por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $cambio = $depurar["cambio_propuesto"];
      $tarea = $depurar["tarea"];
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $fechaCierre = !empty($cambio["fecha_cierre"]) ? $cambio["fecha_cierre"] : null;
      $db->beginTransaction();
      $stmt = $db->prepare("UPDATE crm_clientes_tareas
          SET estatus=:estatus, resultado_cierre=:resultado, fecha_cierre=:fecha_cierre,
              actualizado_por=:usuario, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_cliente_tarea=:tarea");
      $stmt->execute(array(
        ":estatus" => $cambio["estatus"],
        ":resultado" => $cambio["resultado_cierre"] !== "" ? $cambio["resultado_cierre"] : null,
        ":fecha_cierre" => $fechaCierre,
        ":usuario" => $idUsuario,
        ":tarea" => intval($cambio["id_cliente_tarea"])
      ));

      if ($this->tablaExiste($db, "crm_clientes_eventos")) {
        $snapshot = json_encode(array("tarea" => $tarea, "cambio" => $cambio), JSON_UNESCAPED_UNICODE);
        $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'tarea_estatus', 'crm', 'cliente_tarea', :origen_id, :resumen, :snapshot, :usuario)");
        $stmtEvento->execute(array(
          ":cliente" => intval($tarea["id_cliente_crm"]),
          ":origen_id" => (string) $cambio["id_cliente_tarea"],
          ":resumen" => "Tarea CRM " . $cambio["estatus"] . ": " . $tarea["titulo"],
          ":snapshot" => $snapshot,
          ":usuario" => $idUsuario
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Tarea CRM actualizada", array(
        "id_cliente_tarea" => intval($cambio["id_cliente_tarea"]),
        "id_cliente_crm" => intval($tarea["id_cliente_crm"]),
        "estatus" => $cambio["estatus"],
        "crea_evento" => true,
        "no_modifica_cliente" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar una interaccion CRM sin escribir datos.
   * Impacto: prepara historial operativo de contacto independiente de POS/ventas.
   * Contrato: dry-run; no inserta en crm_clientes_interacciones ni actualiza tareas.
   */
  public function interaccionDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
      $tipo = trim((string) $this->valor($datos, "tipo", "contacto"));
      $canal = trim((string) $this->valor($datos, "canal", "whatsapp"));
      $direccion = trim((string) $this->valor($datos, "direccion", "saliente"));
      $resultado = trim((string) $this->valor($datos, "resultado", "registrado"));
      $resumen = trim((string) $this->valor($datos, "resumen", ""));
      $detalle = trim((string) $this->valor($datos, "detalle", ""));
      $fechaInteraccion = trim((string) $this->valor($datos, "fecha_interaccion", ""));
      $origenTipo = trim((string) $this->valor($datos, "origen_tipo", "ficha_cliente"));
      $origenId = trim((string) $this->valor($datos, "origen_id", ""));
      $bloqueos = array();
      $avisos = array();
      $cliente = null;

      if (!$db) {
        $bloqueos[] = "No hay conexion MySQL para validar interaccion CRM";
      }
      if ($idClienteCrm <= 0) {
        $bloqueos[] = "Cliente CRM invalido";
      }
      if (!in_array($tipo, array("contacto", "seguimiento", "postventa", "comercial", "garantia", "apartado", "devolucion", "calidad_datos", "otro"), true)) {
        $bloqueos[] = "Tipo de interaccion CRM invalido";
      }
      if (!in_array($canal, array("whatsapp", "telefono", "correo", "presencial", "sistema", "otro"), true)) {
        $bloqueos[] = "Canal de interaccion invalido";
      }
      if (!in_array($direccion, array("entrante", "saliente", "interna"), true)) {
        $bloqueos[] = "Direccion de interaccion invalida";
      }
      if (!in_array($resultado, array("registrado", "contactado", "sin_respuesta", "pendiente", "resuelto", "no_procede", "otro"), true)) {
        $bloqueos[] = "Resultado de interaccion invalido";
      }
      if ($resumen === "" || strlen($resumen) < 5) {
        $bloqueos[] = "Captura resumen de interaccion mas descriptivo";
      }
      if ($fechaInteraccion !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $fechaInteraccion)) {
        $bloqueos[] = "Fecha de interaccion invalida";
      }

      if ($db && $idClienteCrm > 0 && $this->tablaExiste($db, "crm_clientes_maestro")) {
        $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, origen_alta FROM crm_clientes_maestro WHERE id_cliente_crm=:cliente LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
          $bloqueos[] = "Cliente CRM no encontrado";
        } elseif ((string) $cliente["estatus"] === "bloqueado") {
          $avisos[] = "Cliente bloqueado; registrar interaccion solo si existe justificacion operativa";
        }
      }

      if ($db && !$this->tablaExiste($db, "crm_clientes_interacciones")) {
        $avisos[] = "Tabla crm_clientes_interacciones pendiente; apply real requiere DDL CRM_CLIENTES_SEGUIMIENTO_DDL";
      }

      $interaccion = array(
        "id_cliente_crm" => $idClienteCrm,
        "tipo" => $tipo,
        "canal" => $canal,
        "direccion" => $direccion,
        "resultado" => $resultado,
        "resumen" => $resumen,
        "detalle" => $detalle,
        "origen_modulo" => "crm",
        "origen_tipo" => $origenTipo,
        "origen_id" => $origenId !== "" ? $origenId : (string) $idClienteCrm,
        "fecha_interaccion" => $fechaInteraccion !== "" ? $fechaInteraccion : date("Y-m-d H:i:s")
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Interaccion CRM validada en dry-run" : "Interaccion CRM con bloqueos", array(
        "dry_run" => true,
        "puede_guardar" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "cliente" => $cliente,
        "interaccion_propuesta" => $interaccion,
        "requiere_ddl_seguimiento" => in_array("Tabla crm_clientes_interacciones pendiente; apply real requiere DDL CRM_CLIENTES_SEGUIMIENTO_DDL", $avisos, true),
        "requiere_autorizacion_apply" => true,
        "no_crea_interaccion" => true,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear una interaccion CRM despues de autorizacion fuerte.
   * Impacto: guarda historial operativo auditable del cliente.
   * Contrato: escribe BD; requiere DDL seguimiento, respaldo y token en controlador.
   */
  public function interaccionCrearAutorizada($datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para crear interaccion CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_interacciones")) {
        return $this->respuesta(true, "warning", "Tabla crm_clientes_interacciones pendiente; primero autoriza CRM_CLIENTES_SEGUIMIENTO_DDL");
      }
      $preflight = $this->interaccionDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Interaccion CRM bloqueada por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }

      $interaccion = $depurar["interaccion_propuesta"];
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO crm_clientes_interacciones
          (id_cliente_crm, tipo, canal, direccion, resultado, resumen, detalle,
           origen_modulo, origen_tipo, origen_id, fecha_interaccion, creado_por)
          VALUES (:cliente, :tipo, :canal, :direccion, :resultado, :resumen, :detalle,
           :origen_modulo, :origen_tipo, :origen_id, :fecha_interaccion, :usuario)");
      $stmt->execute(array(
        ":cliente" => intval($interaccion["id_cliente_crm"]),
        ":tipo" => $interaccion["tipo"],
        ":canal" => $interaccion["canal"],
        ":direccion" => $interaccion["direccion"],
        ":resultado" => $interaccion["resultado"],
        ":resumen" => $interaccion["resumen"],
        ":detalle" => $interaccion["detalle"] !== "" ? $interaccion["detalle"] : null,
        ":origen_modulo" => $interaccion["origen_modulo"],
        ":origen_tipo" => $interaccion["origen_tipo"],
        ":origen_id" => $interaccion["origen_id"],
        ":fecha_interaccion" => $interaccion["fecha_interaccion"],
        ":usuario" => $idUsuario
      ));
      $idInteraccion = intval($db->lastInsertId());

      if ($this->tablaExiste($db, "crm_clientes_eventos")) {
        $snapshot = json_encode(array("id_cliente_interaccion" => $idInteraccion, "interaccion" => $interaccion), JSON_UNESCAPED_UNICODE);
        $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'interaccion_creada', 'crm', 'cliente_interaccion', :origen_id, :resumen, :snapshot, :usuario)");
        $stmtEvento->execute(array(
          ":cliente" => intval($interaccion["id_cliente_crm"]),
          ":origen_id" => (string) $idInteraccion,
          ":resumen" => "Interaccion CRM creada: " . $interaccion["resumen"],
          ":snapshot" => $snapshot,
          ":usuario" => $idUsuario
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Interaccion CRM creada", array(
        "id_cliente_crm" => intval($interaccion["id_cliente_crm"]),
        "id_cliente_interaccion" => $idInteraccion,
        "interaccion" => $interaccion,
        "crea_evento" => true,
        "no_modifica_tareas" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: listar interacciones CRM en modo lectura.
   * Impacto: alimenta historial operativo de ficha y seguimiento sin modificar datos.
   * Contrato: read-only; no crea, no edita y no enlaza tareas.
   */
  public function interaccionesListar($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para listar interacciones CRM");
      }
      if (!$this->tablaExiste($db, "crm_clientes_interacciones")) {
        return $this->respuesta(false, "warning", "Esquema CRM Seguimiento pendiente", array(
          "resumen" => array(
            "total" => 0,
            "requiere_ddl_seguimiento" => true
          ),
          "interacciones" => array(),
          "no_escribe_bd" => true
        ));
      }

      $idClienteCrm = intval($this->valor($filtros, "id_cliente_crm", 0));
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 30))));
      $where = array("1=1");
      $params = array();
      if ($idClienteCrm > 0) {
        $where[] = "i.id_cliente_crm=:cliente";
        $params[":cliente"] = $idClienteCrm;
      }

      $sql = "SELECT i.id_cliente_interaccion, i.id_cliente_crm, i.tipo, i.canal, i.direccion,
              i.resultado, i.resumen, i.detalle, i.origen_modulo, i.origen_tipo, i.origen_id,
              i.fecha_interaccion, i.creado_por, i.fecha_registro,
              c.codigo_cliente, c.nombre_publico, c.estatus cliente_estatus
          FROM crm_clientes_interacciones i
          LEFT JOIN crm_clientes_maestro c ON c.id_cliente_crm=i.id_cliente_crm
          WHERE " . implode(" AND ", $where) . "
          ORDER BY i.fecha_interaccion DESC, i.id_cliente_interaccion DESC
          LIMIT " . intval($limite);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $interacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $resumen = array(
        "total" => count($interacciones),
        "requiere_ddl_seguimiento" => false
      );

      return $this->respuesta(false, "success", "Interacciones CRM listadas", array(
        "resumen" => $resumen,
        "interacciones" => $interacciones,
        "limite" => $limite,
        "id_cliente_crm" => $idClienteCrm,
        "no_escribe_bd" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: consultar ficha CRM completa en modo lectura.
   * Impacto: centraliza identidad, identificadores, contactos, direcciones, fiscales, notas e historial.
   * Contrato: read-only; no consulta legacy salvo vinculos externos registrados.
   */
  public function consultarFicha($idClienteCrm) {
    try {
      $db = $this->getConexion();
      $idClienteCrm = intval($idClienteCrm);
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para consultar ficha CRM");
      }
      if ($idClienteCrm <= 0) {
        return $this->respuesta(true, "warning", "Cliente CRM invalido");
      }
      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        return $this->respuesta(true, "warning", "Esquema CRM Clientes pendiente");
      }
      $stmt = $db->prepare("SELECT * FROM crm_clientes_maestro WHERE id_cliente_crm=:cliente LIMIT 1");
      $stmt->execute(array(":cliente" => $idClienteCrm));
      $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cliente) {
        return $this->respuesta(true, "warning", "Cliente CRM no encontrado");
      }

      $ficha = array(
        "cliente" => $cliente,
        "identificadores" => $this->consultarFilasCliente($db, "crm_clientes_identificadores", $idClienteCrm, "principal DESC, id_cliente_identificador ASC"),
        "contactos" => $this->consultarFilasCliente($db, "crm_clientes_contactos", $idClienteCrm, "principal DESC, id_cliente_contacto ASC"),
        "direcciones" => $this->consultarFilasCliente($db, "crm_clientes_direcciones", $idClienteCrm, "principal DESC, id_cliente_direccion ASC"),
        "fiscales" => $this->consultarFilasCliente($db, "crm_clientes_fiscales", $idClienteCrm, "principal DESC, id_cliente_fiscal ASC"),
        "consentimientos" => $this->consultarFilasCliente($db, "crm_clientes_consentimientos", $idClienteCrm, "id_cliente_consentimiento DESC"),
        "condiciones" => $this->consultarFilasCliente($db, "crm_clientes_condiciones", $idClienteCrm, "id_cliente_condicion DESC"),
        "notas" => $this->consultarFilasCliente($db, "crm_clientes_notas", $idClienteCrm, "id_cliente_nota DESC"),
        "interacciones" => $this->consultarFilasCliente($db, "crm_clientes_interacciones", $idClienteCrm, "fecha_interaccion DESC, id_cliente_interaccion DESC"),
        "eventos" => $this->consultarFilasCliente($db, "crm_clientes_eventos", $idClienteCrm, "fecha_registro DESC, id_cliente_evento DESC"),
        "vinculos_externos" => $this->consultarFilasCliente($db, "crm_clientes_vinculos_externos", $idClienteCrm, "id_cliente_vinculo DESC"),
        "recompensas" => $this->consultarRecompensasCliente($db, $idClienteCrm),
        "solo_lectura" => true
      );
      $ficha["calidad_operativa"] = $this->evaluarCalidadOperativaFicha($ficha);

      return $this->respuesta(false, "success", "Ficha CRM consultada", $ficha);
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: consultar recompensas de un cliente para ficha CRM.
   * Impacto: muestra programas, cuentas, saldos y movimientos sin tocar POS/Ventas.
   * Contrato: read-only; tolera DDL pendiente y no modifica BD.
   */
  private function consultarRecompensasCliente($db, $idClienteCrm) {
    $resultado = array(
      "disponible" => false,
      "resumen" => array(
        "cuentas" => 0,
        "movimientos" => 0,
        "saldo_puntos_total" => 0
      ),
      "cuentas" => array(),
      "movimientos" => array()
    );
    if (!$db || !$this->tablaExiste($db, "crm_clientes_recompensas_cuentas") || !$this->tablaExiste($db, "crm_clientes_recompensas_movimientos")) {
      return $resultado;
    }

    $resultado["disponible"] = true;
    $stmt = $db->prepare("SELECT c.id_cliente_recompensa_cuenta, c.id_cliente_crm, c.id_programa_recompensa,
        c.saldo_puntos, c.saldo_monetario_equivalente, c.nivel, c.estatus, c.fecha_alta, c.fecha_actualizacion,
        p.codigo AS programa_codigo, p.nombre AS programa_nombre, p.tipo AS programa_tipo
      FROM crm_clientes_recompensas_cuentas c
      LEFT JOIN crm_recompensas_programas p ON p.id_programa_recompensa=c.id_programa_recompensa
      WHERE c.id_cliente_crm=:cliente
      ORDER BY c.estatus='activo' DESC, c.id_cliente_recompensa_cuenta DESC");
    $stmt->execute(array(":cliente" => $idClienteCrm));
    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT m.id_cliente_recompensa_movimiento, m.id_cliente_recompensa_cuenta, m.id_cliente_crm,
        m.tipo, m.puntos, m.saldo_resultante, m.origen_modulo, m.origen_tipo, m.origen_id,
        m.descripcion, m.estatus, m.fecha_registro,
        p.codigo AS programa_codigo, p.nombre AS programa_nombre
      FROM crm_clientes_recompensas_movimientos m
      LEFT JOIN crm_clientes_recompensas_cuentas c ON c.id_cliente_recompensa_cuenta=m.id_cliente_recompensa_cuenta
      LEFT JOIN crm_recompensas_programas p ON p.id_programa_recompensa=c.id_programa_recompensa
      WHERE m.id_cliente_crm=:cliente
      ORDER BY m.fecha_registro DESC, m.id_cliente_recompensa_movimiento DESC
      LIMIT 30");
    $stmt->execute(array(":cliente" => $idClienteCrm));
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $saldo = 0;
    foreach ($cuentas as $cuenta) {
      if ((string)$cuenta["estatus"] === "activo") {
        $saldo += floatval($cuenta["saldo_puntos"]);
      }
    }

    $resultado["resumen"] = array(
      "cuentas" => count($cuentas),
      "movimientos" => count($movimientos),
      "saldo_puntos_total" => $saldo
    );
    $resultado["cuentas"] = $cuentas;
    $resultado["movimientos"] = $movimientos;
    return $resultado;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: evaluar si una ficha CRM es util para POS, contacto y crecimiento comercial.
   * Impacto: guia captura operativa sin migrar legacy ni escribir cambios.
   * Contrato: funcion pura; recibe ficha consultada y devuelve pendientes, fortalezas y banderas.
   */
  private function evaluarCalidadOperativaFicha($ficha) {
    $cliente = isset($ficha["cliente"]) && is_array($ficha["cliente"]) ? $ficha["cliente"] : array();
    $identificadores = isset($ficha["identificadores"]) && is_array($ficha["identificadores"]) ? $ficha["identificadores"] : array();
    $contactos = isset($ficha["contactos"]) && is_array($ficha["contactos"]) ? $ficha["contactos"] : array();
    $direcciones = isset($ficha["direcciones"]) && is_array($ficha["direcciones"]) ? $ficha["direcciones"] : array();
    $fiscales = isset($ficha["fiscales"]) && is_array($ficha["fiscales"]) ? $ficha["fiscales"] : array();
    $consentimientos = isset($ficha["consentimientos"]) && is_array($ficha["consentimientos"]) ? $ficha["consentimientos"] : array();
    $notas = isset($ficha["notas"]) && is_array($ficha["notas"]) ? $ficha["notas"] : array();
    $vinculos = isset($ficha["vinculos_externos"]) && is_array($ficha["vinculos_externos"]) ? $ficha["vinculos_externos"] : array();

    $puntaje = 0;
    $pendientes = array();
    $fortalezas = array();
    $avisos = array();
    $tieneIdentificadorActivo = false;
    $tieneContactoUtil = false;
    $tieneContactoPermitido = false;
    $tieneConsentimientoComercial = false;
    $tieneOrigenLegacy = false;

    if (trim((string) $this->valor($cliente, "nombre_publico", "")) !== "") {
      $puntaje += 15;
      $fortalezas[] = "Nombre publico definido";
    } else {
      $pendientes[] = "Capturar nombre publico del cliente";
    }

    foreach ($identificadores as $identificador) {
      if (trim((string) $this->valor($identificador, "valor_normalizado", "")) !== "" && $this->valor($identificador, "estatus", "activo") === "activo") {
        $tieneIdentificadorActivo = true;
        break;
      }
    }
    if ($tieneIdentificadorActivo) {
      $puntaje += 25;
      $fortalezas[] = "Tiene identificador activo para busqueda rapida";
    } else {
      $pendientes[] = "Agregar telefono, correo o codigo identificador activo";
    }

    foreach ($contactos as $contacto) {
      $tipo = (string) $this->valor($contacto, "tipo", "");
      $valor = trim((string) $this->valor($contacto, "valor", ""));
      $estatus = (string) $this->valor($contacto, "estatus", "activo");
      if ($valor !== "" && $estatus === "activo" && in_array($tipo, array("telefono", "whatsapp", "correo"), true)) {
        $tieneContactoUtil = true;
        if (intval($this->valor($contacto, "permite_contacto", 0)) === 1) {
          $tieneContactoPermitido = true;
        }
      }
    }
    if ($tieneContactoUtil) {
      $puntaje += 20;
      $fortalezas[] = "Tiene medio de contacto util";
    } else {
      $pendientes[] = "Agregar contacto util: telefono, WhatsApp o correo";
    }

    if ($tieneContactoPermitido) {
      $puntaje += 10;
      $fortalezas[] = "Contacto permitido operativamente";
    } else {
      $pendientes[] = "Marcar permiso de contacto operativo cuando el cliente lo autorice";
    }

    foreach ($consentimientos as $consentimiento) {
      $tipoConsentimiento = (string) $this->valor($consentimiento, "tipo", "");
      if (intval($this->valor($consentimiento, "otorgado", 0)) === 1 && in_array($tipoConsentimiento, array("marketing", "contacto_comercial", "contacto_operativo"), true)) {
        $tieneConsentimientoComercial = true;
        break;
      }
    }
    if ($tieneConsentimientoComercial) {
      $puntaje += 10;
      $fortalezas[] = "Tiene consentimiento registrado";
    }

    if (!empty($direcciones)) {
      $puntaje += 5;
      $fortalezas[] = "Tiene direccion registrada";
    }
    if (!empty($fiscales)) {
      $puntaje += 5;
      $fortalezas[] = "Tiene datos fiscales";
    }
    if (!empty($notas)) {
      $puntaje += 5;
      $fortalezas[] = "Tiene contexto operativo en notas";
    }

    if ((string) $this->valor($cliente, "origen_alta", "") === "legacy_crm_clientes") {
      $tieneOrigenLegacy = true;
    }
    foreach ($vinculos as $vinculo) {
      if ((string) $this->valor($vinculo, "sistema_origen", "") === "legacy" || (string) $this->valor($vinculo, "entidad_origen", "") === "crm_clientes") {
        $tieneOrigenLegacy = true;
        break;
      }
    }
    if ($tieneOrigenLegacy) {
      $avisos[] = "Cliente originado o vinculado a legacy; no usar para campanas sin revision";
    } else {
      $puntaje += 5;
      $fortalezas[] = "No depende de migracion legacy";
    }

    $puntaje = max(0, min(100, $puntaje));
    $nivel = "incompleta";
    if ($puntaje >= 80 && $tieneContactoPermitido) {
      $nivel = "comercial";
    } elseif ($puntaje >= 60 && $tieneContactoUtil) {
      $nivel = "operativa";
    } elseif ($puntaje >= 35 && $tieneIdentificadorActivo) {
      $nivel = "basica_pos";
    }

    return array(
      "puntaje" => $puntaje,
      "nivel" => $nivel,
      "puede_usarse_pos" => $tieneIdentificadorActivo,
      "puede_contactarse" => $tieneContactoUtil && $tieneContactoPermitido,
      "apto_comercial" => $tieneContactoUtil && $tieneContactoPermitido && $tieneConsentimientoComercial && !$tieneOrigenLegacy,
      "tiene_origen_legacy" => $tieneOrigenLegacy,
      "pendientes" => $pendientes,
      "fortalezas" => $fortalezas,
      "avisos" => $avisos
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: validar cambios basicos de ficha CRM sin escribir.
   * Impacto: prepara edicion controlada con permisos, auditoria y snapshot posterior.
   * Contrato: dry-run; no actualiza cliente, contactos, fiscales ni direcciones.
   */
  public function fichaBasicaGuardarDryRun($datos = array()) {
    $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
    $nombre = trim((string) $this->valor($datos, "nombre_publico", ""));
    $tipoCliente = trim((string) $this->valor($datos, "tipo_cliente", "persona"));
    $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
    $observaciones = trim((string) $this->valor($datos, "observaciones_operativas", ""));
    $bloqueos = array();
    if ($idClienteCrm <= 0) {
      $bloqueos[] = "Cliente CRM invalido";
    }
    if ($nombre === "" || strlen($nombre) < 2) {
      $bloqueos[] = "Captura nombre publico";
    }
    if (!in_array($tipoCliente, array("persona", "empresa", "institucion"), true)) {
      $bloqueos[] = "Tipo de cliente invalido";
    }
    if (!in_array($estatus, array("activo", "inactivo", "bloqueado"), true)) {
      $bloqueos[] = "Estatus invalido";
    }
    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Ficha basica CRM validada en dry-run" : "Ficha basica CRM con bloqueos", array(
      "dry_run" => true,
      "puede_guardar" => empty($bloqueos),
      "bloqueos" => $bloqueos,
      "cambios_propuestos" => array(
        "id_cliente_crm" => $idClienteCrm,
        "nombre_publico" => $nombre,
        "tipo_cliente" => $tipoCliente,
        "estatus" => $estatus,
        "observaciones_operativas" => $observaciones
      ),
      "requiere_autorizacion_apply" => true,
      "no_escribe_bd" => true
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: guardar cambios basicos de ficha CRM despues de autorizacion externa.
   * Impacto: permite corregir identidad operativa conservando evento y snapshot.
   * Contrato: escribe BD; requiere flujo autorizado con respaldo externo antes de llamarse.
   */
  public function fichaBasicaGuardarAutorizado($datos = array()) {
    $db = $this->getConexion();
    try {
      $preflight = $this->fichaBasicaGuardarDryRun($datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Edicion basica CRM bloqueada por preflight", array(
          "preflight" => $preflight,
          "bloqueos" => $bloqueos
        ));
      }
      $cambios = $depurar["cambios_propuestos"];
      $idClienteCrm = intval($cambios["id_cliente_crm"]);
      $stmtActual = $db->prepare("SELECT * FROM crm_clientes_maestro WHERE id_cliente_crm=:cliente LIMIT 1");
      $stmtActual->execute(array(":cliente" => $idClienteCrm));
      $actual = $stmtActual->fetch(PDO::FETCH_ASSOC);
      if (!$actual) {
        return $this->respuesta(true, "warning", "Cliente CRM no encontrado");
      }

      $db->beginTransaction();
      $stmt = $db->prepare("UPDATE crm_clientes_maestro
          SET nombre_publico=:nombre,
              tipo_cliente=:tipo,
              estatus=:estatus,
              observaciones_operativas=:observaciones,
              actualizado_por=:usuario,
              fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_cliente_crm=:cliente");
      $stmt->execute(array(
        ":nombre" => $cambios["nombre_publico"],
        ":tipo" => $cambios["tipo_cliente"],
        ":estatus" => $cambios["estatus"],
        ":observaciones" => $cambios["observaciones_operativas"] !== "" ? $cambios["observaciones_operativas"] : null,
        ":usuario" => intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null,
        ":cliente" => $idClienteCrm
      ));
      $snapshot = json_encode(array(
        "antes" => array(
          "nombre_publico" => $actual["nombre_publico"],
          "tipo_cliente" => $actual["tipo_cliente"],
          "estatus" => $actual["estatus"],
          "observaciones_operativas" => $actual["observaciones_operativas"]
        ),
        "despues" => $cambios
      ), JSON_UNESCAPED_UNICODE);
      $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'edicion_basica', 'crm', 'ficha_basica', :origen_id, :resumen, :snapshot, :usuario)");
      $stmtEvento->execute(array(
        ":cliente" => $idClienteCrm,
        ":origen_id" => (string) $idClienteCrm,
        ":resumen" => "Edicion basica CRM",
        ":snapshot" => $snapshot,
        ":usuario" => intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null
      ));
      $db->commit();

      return $this->respuesta(false, "success", "Ficha basica CRM actualizada", array(
        "id_cliente_crm" => $idClienteCrm,
        "cambios" => $cambios,
        "crea_evento_edicion" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: validar complementos de ficha CRM sin escribir.
   * Impacto: prepara contactos, direcciones, fiscales y notas con reglas minimas.
   * Contrato: dry-run; no inserta ni actualiza BD.
   */
  public function complementoGuardarDryRun($tipoComplemento, $datos = array()) {
    $tipoComplemento = trim((string) $tipoComplemento);
    $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", 0));
    $bloqueos = array();
    $propuesto = array("id_cliente_crm" => $idClienteCrm, "tipo_complemento" => $tipoComplemento);
    if ($idClienteCrm <= 0) {
      $bloqueos[] = "Cliente CRM invalido";
    }
    if (!in_array($tipoComplemento, array("contacto", "direccion", "fiscal", "nota", "consentimiento"), true)) {
      $bloqueos[] = "Tipo de complemento CRM invalido";
    }

    if ($tipoComplemento === "contacto") {
      $tipo = trim((string) $this->valor($datos, "tipo", "telefono"));
      $valor = trim((string) $this->valor($datos, "valor", ""));
      $normalizado = $this->normalizarIdentificador($valor);
      if (!in_array($tipo, array("telefono", "correo", "whatsapp", "otro"), true)) {
        $bloqueos[] = "Tipo de contacto invalido";
      }
      if ($valor === "") {
        $bloqueos[] = "Captura valor de contacto";
      }
      if (($tipo === "telefono" || $tipo === "whatsapp") && strlen(preg_replace('/\D+/', '', $valor)) < 7) {
        $bloqueos[] = "Telefono/contacto incompleto";
      }
      if ($tipo === "correo" && strpos($normalizado["valor_normalizado"], "@") === false) {
        $bloqueos[] = "Correo invalido";
      }
      $propuesto = array_merge($propuesto, array(
        "tipo" => $tipo,
        "etiqueta" => trim((string) $this->valor($datos, "etiqueta", "")),
        "valor" => $valor,
        "valor_normalizado" => $normalizado["valor_normalizado"],
        "nombre_contacto" => trim((string) $this->valor($datos, "nombre_contacto", "")),
        "principal" => intval($this->valor($datos, "principal", 0)) === 1 ? 1 : 0,
        "permite_contacto" => intval($this->valor($datos, "permite_contacto", 0)) === 1 ? 1 : 0
      ));
    }

    if ($tipoComplemento === "direccion") {
      $tipo = trim((string) $this->valor($datos, "tipo", "entrega"));
      if (!in_array($tipo, array("entrega", "facturacion", "fiscal", "referencia"), true)) {
        $bloqueos[] = "Tipo de direccion invalido";
      }
      $calle = trim((string) $this->valor($datos, "calle", ""));
      $codigoPostal = trim((string) $this->valor($datos, "codigo_postal", ""));
      if ($calle === "" && $codigoPostal === "") {
        $bloqueos[] = "Captura al menos calle o codigo postal";
      }
      $propuesto = array_merge($propuesto, array(
        "tipo" => $tipo,
        "alias" => trim((string) $this->valor($datos, "alias", "")),
        "pais" => trim((string) $this->valor($datos, "pais", "Mexico")),
        "estado" => trim((string) $this->valor($datos, "estado", "")),
        "ciudad" => trim((string) $this->valor($datos, "ciudad", "")),
        "municipio" => trim((string) $this->valor($datos, "municipio", "")),
        "colonia" => trim((string) $this->valor($datos, "colonia", "")),
        "calle" => $calle,
        "numero_exterior" => trim((string) $this->valor($datos, "numero_exterior", "")),
        "numero_interior" => trim((string) $this->valor($datos, "numero_interior", "")),
        "codigo_postal" => $codigoPostal,
        "referencias" => trim((string) $this->valor($datos, "referencias", "")),
        "principal" => intval($this->valor($datos, "principal", 0)) === 1 ? 1 : 0
      ));
    }

    if ($tipoComplemento === "fiscal") {
      $rfc = strtoupper(trim((string) $this->valor($datos, "rfc", "")));
      $razonSocial = trim((string) $this->valor($datos, "razon_social", ""));
      if ($rfc === "" || strlen($rfc) < 12 || strlen($rfc) > 13) {
        $bloqueos[] = "RFC invalido";
      }
      if ($razonSocial === "") {
        $bloqueos[] = "Captura razon social";
      }
      $propuesto = array_merge($propuesto, array(
        "rfc" => $rfc,
        "razon_social" => $razonSocial,
        "regimen_fiscal" => trim((string) $this->valor($datos, "regimen_fiscal", "")),
        "uso_cfdi_default" => trim((string) $this->valor($datos, "uso_cfdi_default", "")),
        "codigo_postal_fiscal" => trim((string) $this->valor($datos, "codigo_postal_fiscal", "")),
        "principal" => intval($this->valor($datos, "principal", 0)) === 1 ? 1 : 0
      ));
    }

    if ($tipoComplemento === "nota") {
      $tipo = trim((string) $this->valor($datos, "tipo", "operativa"));
      $nota = trim((string) $this->valor($datos, "nota", ""));
      if (!in_array($tipo, array("operativa", "comercial", "postventa", "interna"), true)) {
        $bloqueos[] = "Tipo de nota invalido";
      }
      if (strlen($nota) < 5) {
        $bloqueos[] = "Captura una nota mas descriptiva";
      }
      $propuesto = array_merge($propuesto, array(
        "tipo" => $tipo,
        "nota" => $nota,
        "visibilidad" => trim((string) $this->valor($datos, "visibilidad", "interna"))
      ));
    }

    if ($tipoComplemento === "consentimiento") {
      $tipo = trim((string) $this->valor($datos, "tipo", "contacto_operativo"));
      $otorgado = intval($this->valor($datos, "otorgado", 1)) === 1 ? 1 : 0;
      $medio = trim((string) $this->valor($datos, "medio", "verbal"));
      $evidencia = trim((string) $this->valor($datos, "evidencia", ""));
      if (!in_array($tipo, array("contacto_operativo", "contacto_comercial", "marketing", "privacidad", "whatsapp"), true)) {
        $bloqueos[] = "Tipo de consentimiento invalido";
      }
      if (!in_array($medio, array("verbal", "whatsapp", "correo", "formulario", "documento", "otro"), true)) {
        $bloqueos[] = "Medio de consentimiento invalido";
      }
      if ($otorgado === 0 && $evidencia === "") {
        $bloqueos[] = "Captura evidencia o motivo de revocacion";
      }
      $propuesto = array_merge($propuesto, array(
        "tipo" => $tipo,
        "otorgado" => $otorgado,
        "medio" => $medio,
        "evidencia" => $evidencia
      ));
    }

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Complemento CRM validado en dry-run" : "Complemento CRM con bloqueos", array(
      "dry_run" => true,
      "puede_guardar" => empty($bloqueos),
      "bloqueos" => $bloqueos,
      "complemento_propuesto" => $propuesto,
      "requiere_autorizacion_apply" => true,
      "no_escribe_bd" => true
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: guardar complemento CRM despues de autorizacion externa.
   * Impacto: permite crecer ficha completa con evento de trazabilidad.
   * Contrato: escribe BD; debe llamarse solo desde flujo autorizado con respaldo.
   */
  public function complementoGuardarAutorizado($tipoComplemento, $datos = array()) {
    $db = $this->getConexion();
    try {
      $preflight = $this->complementoGuardarDryRun($tipoComplemento, $datos);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
      if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_guardar"])) {
        return $this->respuesta(false, "warning", "Complemento CRM bloqueado por preflight", array("preflight" => $preflight, "bloqueos" => $bloqueos));
      }
      $c = $depurar["complemento_propuesto"];
      $idClienteCrm = intval($c["id_cliente_crm"]);
      $idUsuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $db->beginTransaction();
      $idNuevo = 0;
      if ($tipoComplemento === "contacto") {
        $stmt = $db->prepare("INSERT INTO crm_clientes_contactos
          (id_cliente_crm, tipo, etiqueta, valor, valor_normalizado, nombre_contacto, principal, permite_contacto, estatus)
          VALUES (:cliente, :tipo, :etiqueta, :valor, :normalizado, :nombre, :principal, :permite, 'activo')");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":tipo" => $c["tipo"], ":etiqueta" => $c["etiqueta"] ?: null, ":valor" => $c["valor"], ":normalizado" => $c["valor_normalizado"] ?: null, ":nombre" => $c["nombre_contacto"] ?: null, ":principal" => $c["principal"], ":permite" => $c["permite_contacto"]));
        $idNuevo = intval($db->lastInsertId());
      } elseif ($tipoComplemento === "direccion") {
        $stmt = $db->prepare("INSERT INTO crm_clientes_direcciones
          (id_cliente_crm, tipo, alias, pais, estado, ciudad, municipio, colonia, calle, numero_exterior, numero_interior, codigo_postal, referencias, principal, estatus)
          VALUES (:cliente, :tipo, :alias, :pais, :estado, :ciudad, :municipio, :colonia, :calle, :ext, :int, :cp, :referencias, :principal, 'activo')");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":tipo" => $c["tipo"], ":alias" => $c["alias"] ?: null, ":pais" => $c["pais"] ?: null, ":estado" => $c["estado"] ?: null, ":ciudad" => $c["ciudad"] ?: null, ":municipio" => $c["municipio"] ?: null, ":colonia" => $c["colonia"] ?: null, ":calle" => $c["calle"] ?: null, ":ext" => $c["numero_exterior"] ?: null, ":int" => $c["numero_interior"] ?: null, ":cp" => $c["codigo_postal"] ?: null, ":referencias" => $c["referencias"] ?: null, ":principal" => $c["principal"]));
        $idNuevo = intval($db->lastInsertId());
      } elseif ($tipoComplemento === "fiscal") {
        $stmt = $db->prepare("INSERT INTO crm_clientes_fiscales
          (id_cliente_crm, rfc, razon_social, regimen_fiscal, uso_cfdi_default, codigo_postal_fiscal, principal, estatus)
          VALUES (:cliente, :rfc, :razon, :regimen, :uso, :cp, :principal, 'activo')");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":rfc" => $c["rfc"], ":razon" => $c["razon_social"], ":regimen" => $c["regimen_fiscal"] ?: null, ":uso" => $c["uso_cfdi_default"] ?: null, ":cp" => $c["codigo_postal_fiscal"] ?: null, ":principal" => $c["principal"]));
        $idNuevo = intval($db->lastInsertId());
      } elseif ($tipoComplemento === "nota") {
        $stmt = $db->prepare("INSERT INTO crm_clientes_notas
          (id_cliente_crm, tipo, nota, visibilidad, estatus, creado_por)
          VALUES (:cliente, :tipo, :nota, :visibilidad, 'activa', :usuario)");
        $stmt->execute(array(":cliente" => $idClienteCrm, ":tipo" => $c["tipo"], ":nota" => $c["nota"], ":visibilidad" => $c["visibilidad"], ":usuario" => $idUsuario));
        $idNuevo = intval($db->lastInsertId());
      } elseif ($tipoComplemento === "consentimiento") {
        $stmt = $db->prepare("INSERT INTO crm_clientes_consentimientos
          (id_cliente_crm, tipo, otorgado, medio, evidencia, fecha_consentimiento, fecha_revocacion, registrado_por)
          VALUES (:cliente, :tipo, :otorgado, :medio, :evidencia, CURRENT_TIMESTAMP, :fecha_revocacion, :usuario)");
        $stmt->execute(array(
          ":cliente" => $idClienteCrm,
          ":tipo" => $c["tipo"],
          ":otorgado" => $c["otorgado"],
          ":medio" => $c["medio"] ?: null,
          ":evidencia" => $c["evidencia"] ?: null,
          ":fecha_revocacion" => intval($c["otorgado"]) === 1 ? null : date("Y-m-d H:i:s"),
          ":usuario" => $idUsuario
        ));
        $idNuevo = intval($db->lastInsertId());
      }
      $snapshot = json_encode(array("tipo_complemento" => $tipoComplemento, "id_nuevo" => $idNuevo, "datos" => $c), JSON_UNESCAPED_UNICODE);
      $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
        (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
        VALUES (:cliente, 'complemento_creado', 'crm', :origen_tipo, :origen_id, :resumen, :snapshot, :usuario)");
      $stmtEvento->execute(array(":cliente" => $idClienteCrm, ":origen_tipo" => $tipoComplemento, ":origen_id" => (string) $idNuevo, ":resumen" => "Complemento CRM creado: " . $tipoComplemento, ":snapshot" => $snapshot, ":usuario" => $idUsuario));
      $db->commit();
      return $this->respuesta(false, "success", "Complemento CRM creado", array("id_cliente_crm" => $idClienteCrm, "tipo_complemento" => $tipoComplemento, "id_nuevo" => $idNuevo, "crea_evento" => true));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: auditar fuentes actuales de clientes y calidad de identificadores.
   * Impacto: prepara migracion CRM sin asumir que legacy, POS y ecommerce son la misma identidad.
   * Contrato: read-only; calcula duplicados en memoria y no modifica datos.
   */
  public function auditarFuentesClientes() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para auditar fuentes");
      }

      $fuentes = array(
        "crm_clientes" => $this->auditarFuenteCrmLegacy($db),
        "erp_clientes" => $this->auditarFuenteErpClientes($db),
        "erp_ventas" => $this->auditarFuenteVentasSnapshot($db)
      );

      $hallazgos = array();
      if (!empty($fuentes["crm_clientes"]["existe"]) && intval($fuentes["crm_clientes"]["registros"]) > 0) {
        $hallazgos[] = array(
          "id" => "CRM-FUENTE-001",
          "severidad" => "media",
          "mensaje" => "crm_clientes legacy contiene datos utiles pero no debe ser canonico sin migracion auditada"
        );
      }
      if (!empty($fuentes["crm_clientes"]["duplicados_identificador"])) {
        $hallazgos[] = array(
          "id" => "CRM-FUENTE-002",
          "severidad" => "alta",
          "mensaje" => "Hay identificadores repetidos en legacy; requieren resolucion antes de crear unicidad CRM"
        );
      }
      if (!empty($fuentes["erp_clientes"]["existe"]) && intval($fuentes["erp_clientes"]["registros"]) > 0) {
        $hallazgos[] = array(
          "id" => "CRM-FUENTE-003",
          "severidad" => "media",
          "mensaje" => "erp_clientes existe por POS/UAT; debe vincularse como origen externo o migrarse despues"
        );
      }

      return $this->respuesta(false, empty($hallazgos) ? "success" : "warning", "Auditoria de fuentes CRM generada", array(
        "fuentes" => $fuentes,
        "hallazgos" => $hallazgos,
        "solo_lectura" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: proponer migracion inicial desde legacy/POS hacia CRM canonico.
   * Impacto: define pasos seguros antes de pedir respaldo y autorizacion.
   * Contrato: dry-run; no crea clientes, no vincula fuentes y no ejecuta DDL.
   */
  public function planMigracionClientesDryRun() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para planear migracion");
      }

      $auditoria = $this->auditarFuentesClientes();
      $fuentes = isset($auditoria["depurar"]["fuentes"]) ? $auditoria["depurar"]["fuentes"] : array();
      $bloqueos = array();
      $pasos = array();

      if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
        $bloqueos[] = "Falta aplicar esquema CRM canonico antes de migrar clientes";
      }
      if (!$this->tablaExiste($db, "crm_clientes_vinculos_externos")) {
        $bloqueos[] = "Falta tabla de vinculos externos para no perder relacion con legacy/POS";
      }
      if (!empty($fuentes["crm_clientes"]["duplicados_identificador"])) {
        $bloqueos[] = "Resolver o marcar duplicados de identificador legacy antes de imponer unicidad CRM";
      }

      $pasos[] = array(
        "orden" => 1,
        "accion" => "aplicar_esquema_crm",
        "descripcion" => "Crear tablas canonicas crm_clientes_* con respaldo externo y autorizacion"
      );
      $pasos[] = array(
        "orden" => 2,
        "accion" => "migrar_legacy_como_borrador",
        "descripcion" => "Crear cliente CRM por cada crm_clientes legacy con calidad_datos=revisar cuando falten identificadores"
      );
      $pasos[] = array(
        "orden" => 3,
        "accion" => "crear_identificadores",
        "descripcion" => "Normalizar correo/contacto1/contacto2 y crear identificadores activos solo si no chocan"
      );
      $pasos[] = array(
        "orden" => 4,
        "accion" => "crear_vinculos_externos",
        "descripcion" => "Relacionar cada cliente CRM con crm_clientes.id_cliente y erp_clientes.id_cliente cuando aplique"
      );
      $pasos[] = array(
        "orden" => 5,
        "accion" => "revisar_duplicados",
        "descripcion" => "Generar cola de revision/fusion para duplicados probables sin borrar historial"
      );
      $pasos[] = array(
        "orden" => 6,
        "accion" => "pos_consumir_crm",
        "descripcion" => "Cambiar POS a buscar CRM canonico y conservar snapshot historico en venta"
      );

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Plan de migracion CRM listo para autorizacion futura" : "Plan de migracion CRM con bloqueos", array(
        "dry_run" => true,
        "bloqueos" => $bloqueos,
        "pasos" => $pasos,
        "fuentes" => $fuentes,
        "requiere_respaldo_externo" => true,
        "requiere_autorizacion_textual" => true,
        "no_ejecuta_migracion" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: detallar duplicados probables entre fuentes de clientes antes de migrar.
   * Impacto: CRM; permite revisar casos sensibles sin imponer unicidad ni fusionar.
   * Contrato: read-only; no marca, no fusiona y no modifica fuentes legacy/POS.
   */
  public function duplicadosProbablesDryRun($limite = 50) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para analizar duplicados");
      }
      $limite = max(1, min(200, intval($limite)));
      $grupos = array();
      $indices = array();

      if ($this->tablaExiste($db, "crm_clientes")) {
        $rows = $db->query("SELECT id_cliente, alias, nombres, apellido_paterno, apellido_materno, correo, contacto1, contacto2, estatus FROM crm_clientes")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
          $nombre = $this->nombreLegacy($row);
          foreach (array("correo", "contacto1", "contacto2") as $campo) {
            $valor = trim((string) $this->valor($row, $campo, ""));
            if ($valor === "" || $valor === "0000000000") {
              continue;
            }
            $normalizado = $this->normalizarIdentificador($valor);
            if ($normalizado["valor_normalizado"] === "") {
              continue;
            }
            $llave = $normalizado["tipo"] . ":" . $normalizado["valor_normalizado"];
            $indices[$llave][] = array(
              "fuente" => "crm_clientes",
              "id_origen" => intval($row["id_cliente"]),
              "nombre" => $nombre,
              "campo" => $campo,
              "valor" => $valor,
              "estatus" => $row["estatus"]
            );
          }
        }
      }

      if ($this->tablaExiste($db, "erp_clientes") && $this->tablaExiste($db, "erp_clientes_identificadores")) {
        $stmt = $db->query("SELECT c.id_cliente, c.codigo_cliente, c.nombre_publico, c.estatus, i.tipo, i.valor, i.valor_normalizado
            FROM erp_clientes c
            INNER JOIN erp_clientes_identificadores i ON i.id_cliente=c.id_cliente
            WHERE i.estatus='activo'");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
          $llave = $row["tipo"] . ":" . $row["valor_normalizado"];
          $indices[$llave][] = array(
            "fuente" => "erp_clientes",
            "id_origen" => intval($row["id_cliente"]),
            "codigo" => $row["codigo_cliente"],
            "nombre" => $row["nombre_publico"],
            "campo" => $row["tipo"],
            "valor" => $row["valor"],
            "estatus" => $row["estatus"]
          );
        }
      }

      foreach ($indices as $identificador => $items) {
        if (count($items) <= 1) {
          continue;
        }
        $fuentes = array_values(array_unique(array_map(function ($item) {
          return $item["fuente"];
        }, $items)));
        $grupos[] = array(
          "identificador" => $identificador,
          "total" => count($items),
          "fuentes" => $fuentes,
          "severidad" => count($fuentes) > 1 ? "alta" : "media",
          "items" => $items,
          "recomendacion" => "Revisar si son duplicados reales, contactos compartidos o datos de prueba antes de migrar"
        );
      }

      usort($grupos, function ($a, $b) {
        if ($a["severidad"] !== $b["severidad"]) {
          return $a["severidad"] === "alta" ? -1 : 1;
        }
        return $b["total"] - $a["total"];
      });

      return $this->respuesta(false, empty($grupos) ? "success" : "warning", empty($grupos) ? "Sin duplicados probables en fuentes auditadas" : "Duplicados probables detectados", array(
        "dry_run" => true,
        "total_grupos" => count($grupos),
        "grupos" => array_slice($grupos, 0, $limite),
        "limite" => $limite,
        "no_fusiona" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: analizar un grupo duplicado legacy/POS sin marcar ni fusionar.
   * Impacto: prepara cola de revision previa a migracion CRM.
   * Contrato: read-only; no crea cola, no marca registros y no fusiona.
   */
  public function duplicadoRevisionDryRun($identificador) {
    try {
      $db = $this->getConexion();
      $identificador = trim((string) $identificador);
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para revisar duplicado");
      }
      if ($identificador === "" || strpos($identificador, ":") === false) {
        return $this->respuesta(true, "warning", "Identificador duplicado invalido");
      }
      list($tipoBuscado, $valorBuscado) = explode(":", $identificador, 2);
      $items = array();

      if ($this->tablaExiste($db, "crm_clientes")) {
        $rows = $db->query("SELECT id_cliente, alias, nombres, apellido_paterno, apellido_materno, correo, contacto1, contacto2, estatus, fch_r FROM crm_clientes")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
          foreach (array("correo", "contacto1", "contacto2") as $campo) {
            $valor = trim((string) $this->valor($row, $campo, ""));
            if ($valor === "" || $valor === "0000000000") {
              continue;
            }
            $normalizado = $this->normalizarIdentificador($valor);
            if ($normalizado["tipo"] . ":" . $normalizado["valor_normalizado"] !== $identificador) {
              continue;
            }
            $items[] = array(
              "fuente" => "crm_clientes",
              "id_origen" => intval($row["id_cliente"]),
              "nombre" => $this->nombreLegacy($row),
              "campo" => $campo,
              "valor" => $valor,
              "estatus" => $row["estatus"],
              "fecha_registro" => $row["fch_r"],
              "payload" => $row
            );
          }
        }
      }

      if ($this->tablaExiste($db, "erp_clientes") && $this->tablaExiste($db, "erp_clientes_identificadores")) {
        $stmt = $db->prepare("SELECT c.id_cliente, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
                i.tipo, i.valor, i.valor_normalizado
            FROM erp_clientes_identificadores i
            INNER JOIN erp_clientes c ON c.id_cliente=i.id_cliente
            WHERE i.tipo=:tipo AND i.valor_normalizado=:valor AND i.estatus='activo'");
        $stmt->execute(array(":tipo" => $tipoBuscado, ":valor" => $valorBuscado));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
          $items[] = array(
            "fuente" => "erp_clientes",
            "id_origen" => intval($row["id_cliente"]),
            "codigo" => $row["codigo_cliente"],
            "nombre" => $row["nombre_publico"],
            "campo" => $row["tipo"],
            "valor" => $row["valor"],
            "estatus" => $row["estatus"],
            "fecha_registro" => null,
            "payload" => $row
          );
        }
      }

      $nombres = array_values(array_unique(array_map(function ($item) {
        return trim(strtolower(preg_replace('/\s+/', ' ', (string) $item["nombre"])));
      }, $items)));
      $nombres = array_values(array_filter($nombres, function ($nombre) {
        return $nombre !== "";
      }));
      $fuentes = array_values(array_unique(array_map(function ($item) {
        return $item["fuente"];
      }, $items)));
      $severidad = count($fuentes) > 1 ? "alta" : "media";
      $accion = "revision_manual";
      $motivos = array();
      if (count($items) <= 1) {
        $accion = "sin_duplicado";
        $motivos[] = "El identificador ya no aparece repetido en fuentes auditadas";
      } elseif (count($nombres) === 1) {
        $accion = "probable_mismo_cliente";
        $motivos[] = "Los nombres normalizados coinciden";
      } else {
        $motivos[] = "Hay nombres distintos o incompletos para el mismo identificador";
      }
      if (in_array("erp_clientes", $fuentes, true)) {
        $motivos[] = "Existe relacion POS/UAT; requiere vinculo externo antes de migrar";
      }

      return $this->respuesta(false, "success", "Revision de duplicado generada", array(
        "dry_run" => true,
        "identificador" => array("tipo" => $tipoBuscado, "valor_normalizado" => $valorBuscado, "llave" => $identificador),
        "total_items" => count($items),
        "fuentes" => $fuentes,
        "severidad" => $severidad,
        "items" => $items,
        "recomendacion" => array(
          "accion_sugerida" => $accion,
          "motivos" => $motivos,
          "requiere_decision_humana" => $accion !== "sin_duplicado",
          "no_fusiona" => true,
          "no_migra" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: previsualizar el mapeo de clientes legacy hacia CRM canonico.
   * Impacto: permite revisar nombres, calidad de datos e identificadores antes de migrar.
   * Contrato: dry-run; no inserta clientes ni vinculos.
   */
  public function previewMigracionLegacyDryRun($limite = 25) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para preview de migracion");
      }
      if (!$this->tablaExiste($db, "crm_clientes")) {
        return $this->respuesta(false, "warning", "No existe crm_clientes legacy para previsualizar", array(
          "preview" => array()
        ));
      }
      $limite = max(1, min(100, intval($limite)));
      $stmt = $db->query("SELECT id_cliente, alias, nombres, apellido_paterno, apellido_materno, correo, contacto1, contacto2, estatus, fch_r
          FROM crm_clientes
          ORDER BY id_cliente ASC
          LIMIT " . intval($limite));
      $preview = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $identificadores = array();
        foreach (array("correo", "contacto1", "contacto2") as $campo) {
          $valor = trim((string) $this->valor($row, $campo, ""));
          if ($valor === "" || $valor === "0000000000") {
            continue;
          }
          $normalizado = $this->normalizarIdentificador($valor);
          if ($normalizado["valor_normalizado"] === "") {
            continue;
          }
          $identificadores[] = array(
            "tipo" => $normalizado["tipo"],
            "valor" => $valor,
            "valor_normalizado" => $normalizado["valor_normalizado"],
            "principal" => empty($identificadores) ? 1 : 0,
            "origen_campo" => $campo
          );
        }
        $nombre = $this->nombreLegacy($row);
        $calidad = empty($identificadores) ? "revisar" : "basica";
        $preview[] = array(
          "origen" => array(
            "sistema_origen" => "legacy",
            "entidad_origen" => "crm_clientes",
            "id_origen" => intval($row["id_cliente"])
          ),
          "cliente_propuesto" => array(
            "codigo_cliente" => "CRM-LEG-" . str_pad((string) intval($row["id_cliente"]), 6, "0", STR_PAD_LEFT),
            "tipo_cliente" => "persona",
            "nombre_publico" => $nombre !== "" ? $nombre : "Cliente legacy " . intval($row["id_cliente"]),
            "nombre_legal" => $nombre !== "" ? $nombre : null,
            "estatus" => intval($row["estatus"]) === 0 && $row["estatus"] !== null ? "inactivo" : "activo",
            "calidad_datos" => $calidad,
            "origen_alta" => "legacy_crm_clientes"
          ),
          "identificadores_propuestos" => $identificadores,
          "vinculo_externo" => array(
            "sistema_origen" => "legacy",
            "entidad_origen" => "crm_clientes",
            "id_origen" => (string) intval($row["id_cliente"]),
            "confianza" => empty($identificadores) ? "pendiente" : "media"
          ),
          "requiere_revision" => empty($identificadores)
        );
      }

      return $this->respuesta(false, "success", "Preview de migracion legacy generado", array(
        "dry_run" => true,
        "limite" => $limite,
        "preview" => $preview,
        "no_inserta" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: preparar lote de migracion legacy como borrador no aplicado.
   * Impacto: permite separar registros migrables, bloqueados por duplicado y pendientes de revision.
   * Contrato: dry-run; no inserta clientes CRM, identificadores ni vinculos externos.
   */
  public function migracionLegacyBorradorDryRun($offset = 0, $limite = 50) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para preparar borrador de migracion");
      }
      if (!$this->tablaExiste($db, "crm_clientes")) {
        return $this->respuesta(false, "warning", "No existe crm_clientes legacy para preparar migracion", array(
          "lote" => array(),
          "resumen" => array("total_lote" => 0)
        ));
      }
      $offset = max(0, intval($offset));
      $limite = max(1, min(200, intval($limite)));
      $duplicados = $this->indicesDuplicadosLegacy($db);
      $vinculosLegacy = $this->indicesVinculosLegacyActivos($db);
      $identificadoresCrm = $this->indicesIdentificadoresCrmActivos($db);
      $totalLegacy = intval($db->query("SELECT COUNT(*) FROM crm_clientes")->fetchColumn());
      $stmt = $db->query("SELECT id_cliente, alias, nombres, apellido_paterno, apellido_materno, correo, contacto1, contacto2, estatus, fch_r
          FROM crm_clientes
          ORDER BY id_cliente ASC
          LIMIT " . intval($limite) . " OFFSET " . intval($offset));
      $lote = array();
      $resumen = array(
        "total_legacy" => $totalLegacy,
        "offset" => $offset,
        "limite" => $limite,
        "total_lote" => 0,
        "migrables" => 0,
        "bloqueados_duplicado" => 0,
        "bloqueados_vinculo" => 0,
        "bloqueados_crm_existente" => 0,
        "requieren_revision" => 0,
        "sin_identificador" => 0
      );

      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $idOrigen = intval($row["id_cliente"]);
        $identificadores = $this->identificadoresLegacyRow($row);
        $bloqueos = array();
        $avisos = array();
        $estado = "migrable_borrador";
        $tipoBloqueo = "";
        if (isset($vinculosLegacy[$idOrigen])) {
          $bloqueos[] = "Legacy ya vinculado a CRM: " . $vinculosLegacy[$idOrigen];
          $tipoBloqueo = "vinculo";
        } else {
          foreach ($identificadores as $identificador) {
            $llave = $identificador["tipo"] . ":" . $identificador["valor_normalizado"];
            if (isset($duplicados[$llave]) && count($duplicados[$llave]) > 1) {
              $bloqueos[] = "Identificador duplicado: " . $llave;
              $tipoBloqueo = $tipoBloqueo !== "" ? $tipoBloqueo : "duplicado";
            }
            if (isset($identificadoresCrm[$llave])) {
              $bloqueos[] = "Identificador ya existe en CRM: " . $llave;
              $tipoBloqueo = "crm_existente";
            }
          }
        }
        if (empty($identificadores)) {
          $avisos[] = "Sin identificador util; migrar solo como borrador con revision";
          $estado = "requiere_revision";
          $resumen["sin_identificador"]++;
        }
        if (!empty($bloqueos)) {
          if ($tipoBloqueo === "vinculo") {
            $estado = "bloqueado_vinculado";
            $resumen["bloqueados_vinculo"]++;
          } elseif ($tipoBloqueo === "crm_existente") {
            $estado = "bloqueado_crm_existente";
            $resumen["bloqueados_crm_existente"]++;
          } else {
            $estado = "bloqueado_duplicado";
            $resumen["bloqueados_duplicado"]++;
          }
        } elseif ($estado === "requiere_revision") {
          $resumen["requieren_revision"]++;
        } else {
          $resumen["migrables"]++;
        }
        $nombre = $this->nombreLegacy($row);
        $lote[] = array(
          "origen" => array(
            "sistema_origen" => "legacy",
            "entidad_origen" => "crm_clientes",
            "id_origen" => $idOrigen
          ),
          "estado_borrador" => $estado,
          "bloqueos" => array_values(array_unique($bloqueos)),
          "avisos" => array_values(array_unique($avisos)),
          "cliente_propuesto" => array(
            "codigo_cliente" => "CRM-LEG-" . str_pad((string) $idOrigen, 6, "0", STR_PAD_LEFT),
            "tipo_cliente" => "persona",
            "nombre_publico" => $nombre !== "" ? $nombre : "Cliente legacy " . $idOrigen,
            "nombre_legal" => $nombre !== "" ? $nombre : null,
            "estatus" => intval($row["estatus"]) === 0 && $row["estatus"] !== null ? "inactivo" : "activo",
            "calidad_datos" => $estado === "migrable_borrador" ? "basica" : "revisar",
            "origen_alta" => "legacy_crm_clientes"
          ),
          "identificadores_propuestos" => $identificadores,
          "vinculo_externo_propuesto" => array(
            "sistema_origen" => "legacy",
            "entidad_origen" => "crm_clientes",
            "id_origen" => (string) $idOrigen,
            "confianza" => $estado === "migrable_borrador" ? "media" : "pendiente"
          )
        );
      }
      $resumen["total_lote"] = count($lote);

      return $this->respuesta(false, "success", "Borrador de migracion legacy generado", array(
        "dry_run" => true,
        "resumen" => $resumen,
        "lote" => $lote,
        "no_inserta" => true,
        "no_vincula" => true,
        "requiere_autorizacion_apply" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: aplicar un lote controlado de migracion legacy previamente validado como migrable.
   * Impacto: crea clientes CRM canonicos con identificadores, vinculo externo y evento de trazabilidad.
   * Contrato: escribe BD; requiere flujo autorizado externo, respaldo valido y bloquea duplicados.
   */
  public function migracionLegacyAplicarAutorizado($offset = 0, $limite = 25, $datos = array()) {
    $db = $this->getConexion();
    try {
      if (!$db) {
        return $this->respuesta(true, "warning", "No hay conexion MySQL para aplicar migracion legacy CRM");
      }
      $offset = max(0, intval($offset));
      $limite = max(1, min(100, intval($limite)));
      $preflight = $this->migracionLegacyBorradorDryRun($offset, $limite);
      $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $lote = isset($depurar["lote"]) && is_array($depurar["lote"]) ? $depurar["lote"] : array();
      if (!empty($preflight["error"]) || empty($lote)) {
        return $this->respuesta(false, "warning", "Migracion legacy bloqueada por preflight", array(
          "preflight" => $preflight
        ));
      }

      $bloqueados = array();
      foreach ($lote as $item) {
        if (!isset($item["estado_borrador"]) || $item["estado_borrador"] !== "migrable_borrador") {
          $bloqueados[] = array(
            "id_origen" => isset($item["origen"]["id_origen"]) ? intval($item["origen"]["id_origen"]) : 0,
            "estado" => isset($item["estado_borrador"]) ? $item["estado_borrador"] : "desconocido",
            "bloqueos" => isset($item["bloqueos"]) ? $item["bloqueos"] : array()
          );
        }
      }
      if (!empty($bloqueados)) {
        return $this->respuesta(false, "warning", "Migracion legacy bloqueada: el lote contiene registros no migrables", array(
          "bloqueados" => $bloqueados,
          "regla" => "Use un lote que contenga solo estado migrable_borrador"
        ));
      }

      $usuario = intval($this->valor($datos, "id_usuario", 0)) > 0 ? intval($this->valor($datos, "id_usuario", 0)) : null;
      $migrados = array();
      $db->beginTransaction();

      $stmtVinculoExiste = $db->prepare("SELECT id_cliente_vinculo, id_cliente_crm
          FROM crm_clientes_vinculos_externos
          WHERE sistema_origen='legacy' AND entidad_origen='crm_clientes' AND id_origen=:id_origen AND estatus='activo'
          LIMIT 1
          FOR UPDATE");
      $stmtIdentExiste = $db->prepare("SELECT i.id_cliente_crm, c.codigo_cliente
          FROM crm_clientes_identificadores i
          INNER JOIN crm_clientes_maestro c ON c.id_cliente_crm=i.id_cliente_crm
          WHERE i.tipo=:tipo AND i.valor_normalizado=:normalizado AND i.estatus='activo'
          LIMIT 1
          FOR UPDATE");
      $stmtCliente = $db->prepare("INSERT INTO crm_clientes_maestro
          (codigo_cliente, tipo_cliente, nombre_publico, nombre_legal, estatus, calidad_datos,
           origen_alta, id_sucursal_alta, creado_por, fecha_actualizacion)
          VALUES (:codigo, :tipo, :nombre, :legal, :estatus, :calidad, 'legacy_crm_clientes', NULL, :usuario, CURRENT_TIMESTAMP)");
      $stmtIdentificador = $db->prepare("INSERT INTO crm_clientes_identificadores
          (id_cliente_crm, tipo, valor, valor_normalizado, principal, verificado, estatus)
          VALUES (:cliente, :tipo, :valor, :normalizado, :principal, 0, 'activo')");
      $stmtVinculo = $db->prepare("INSERT INTO crm_clientes_vinculos_externos
          (id_cliente_crm, sistema_origen, entidad_origen, id_origen, confianza, estatus, datos_snapshot, creado_por)
          VALUES (:cliente, 'legacy', 'crm_clientes', :id_origen, 'media', 'activo', :snapshot, :usuario)");
      $stmtEvento = $db->prepare("INSERT INTO crm_clientes_eventos
          (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
          VALUES (:cliente, 'migracion_legacy', 'crm', 'legacy_crm_clientes', :origen_id, :resumen, :snapshot, :usuario)");

      foreach ($lote as $item) {
        $idOrigen = intval($item["origen"]["id_origen"]);
        $stmtVinculoExiste->execute(array(":id_origen" => (string) $idOrigen));
        $vinculoExiste = $stmtVinculoExiste->fetch(PDO::FETCH_ASSOC);
        if ($vinculoExiste) {
          throw new Exception("Cliente legacy ya vinculado: " . $idOrigen);
        }

        foreach ($item["identificadores_propuestos"] as $identificador) {
          $stmtIdentExiste->execute(array(
            ":tipo" => $identificador["tipo"],
            ":normalizado" => $identificador["valor_normalizado"]
          ));
          $identExiste = $stmtIdentExiste->fetch(PDO::FETCH_ASSOC);
          if ($identExiste) {
            throw new Exception("Identificador ya existe en CRM para legacy " . $idOrigen . ": " . $identificador["tipo"] . ":" . $identificador["valor_normalizado"]);
          }
        }

        $cliente = $item["cliente_propuesto"];
        $stmtCliente->execute(array(
          ":codigo" => $cliente["codigo_cliente"],
          ":tipo" => $cliente["tipo_cliente"],
          ":nombre" => $cliente["nombre_publico"],
          ":legal" => $cliente["nombre_legal"],
          ":estatus" => $cliente["estatus"],
          ":calidad" => $cliente["calidad_datos"],
          ":usuario" => $usuario
        ));
        $idClienteCrm = intval($db->lastInsertId());
        $identificadoresInsertados = array();

        foreach ($item["identificadores_propuestos"] as $identificador) {
          $stmtIdentificador->execute(array(
            ":cliente" => $idClienteCrm,
            ":tipo" => $identificador["tipo"],
            ":valor" => $identificador["valor"],
            ":normalizado" => $identificador["valor_normalizado"],
            ":principal" => intval($identificador["principal"]) === 1 ? 1 : 0
          ));
          $identificadoresInsertados[] = array(
            "id_cliente_identificador" => intval($db->lastInsertId()),
            "tipo" => $identificador["tipo"],
            "valor_normalizado" => $identificador["valor_normalizado"]
          );
        }

        $snapshot = json_encode(array(
          "origen" => $item["origen"],
          "cliente_propuesto" => $cliente,
          "identificadores_propuestos" => $item["identificadores_propuestos"]
        ), JSON_UNESCAPED_UNICODE);
        $stmtVinculo->execute(array(
          ":cliente" => $idClienteCrm,
          ":id_origen" => (string) $idOrigen,
          ":snapshot" => $snapshot,
          ":usuario" => $usuario
        ));
        $stmtEvento->execute(array(
          ":cliente" => $idClienteCrm,
          ":origen_id" => (string) $idOrigen,
          ":resumen" => "Migracion legacy crm_clientes #" . $idOrigen,
          ":snapshot" => $snapshot,
          ":usuario" => $usuario
        ));

        $migrados[] = array(
          "id_origen" => $idOrigen,
          "id_cliente_crm" => $idClienteCrm,
          "codigo_cliente" => $cliente["codigo_cliente"],
          "identificadores" => $identificadoresInsertados
        );
      }

      $db->commit();
      return $this->respuesta(false, "success", "Migracion legacy CRM aplicada", array(
        "offset" => $offset,
        "limite" => $limite,
        "migrados" => $migrados,
        "total_migrados" => count($migrados),
        "contrato" => array(
          "solo_migrable_borrador" => true,
          "crea_vinculo_externo" => true,
          "crea_evento_migracion" => true,
          "no_modifica_legacy" => true,
          "no_fusiona_duplicados" => true
        )
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function auditarFuenteCrmLegacy($db) {
    $tabla = "crm_clientes";
    if (!$this->tablaExiste($db, $tabla)) {
      return array("existe" => false, "registros" => null);
    }
    $rows = $db->query("SELECT id_cliente, alias, nombres, apellido_paterno, apellido_materno, correo, contacto1, contacto2, estatus FROM crm_clientes")->fetchAll(PDO::FETCH_ASSOC);
    $identificadores = array();
    $sinIdentificador = 0;
    $conCorreo = 0;
    $conTelefono = 0;
    foreach ($rows as $row) {
      $idsFila = array();
      foreach (array("correo", "contacto1", "contacto2") as $campo) {
        $valor = trim((string) $this->valor($row, $campo, ""));
        if ($valor === "" || $valor === "0000000000") {
          continue;
        }
        $normalizado = $this->normalizarIdentificador($valor);
        if ($normalizado["valor_normalizado"] === "") {
          continue;
        }
        if ($normalizado["tipo"] === "correo") {
          $conCorreo++;
        }
        if ($normalizado["tipo"] === "telefono") {
          $conTelefono++;
        }
        $llave = $normalizado["tipo"] . ":" . $normalizado["valor_normalizado"];
        $identificadores[$llave][] = intval($row["id_cliente"]);
        $idsFila[] = $llave;
      }
      if (empty($idsFila)) {
        $sinIdentificador++;
      }
    }
    return array(
      "existe" => true,
      "registros" => count($rows),
      "columnas" => $this->listarColumnas($db, $tabla),
      "con_correo" => $conCorreo,
      "con_telefono" => $conTelefono,
      "sin_identificador_util" => $sinIdentificador,
      "duplicados_identificador" => $this->duplicadosIdentificador($identificadores),
      "muestra" => array_slice($rows, 0, 5)
    );
  }

  private function identificadoresLegacyRow($row) {
    $identificadores = array();
    foreach (array("correo", "contacto1", "contacto2") as $campo) {
      $valor = trim((string) $this->valor($row, $campo, ""));
      if ($valor === "" || $valor === "0000000000") {
        continue;
      }
      $normalizado = $this->normalizarIdentificador($valor);
      if ($normalizado["valor_normalizado"] === "") {
        continue;
      }
      if (!in_array($normalizado["tipo"], array("telefono", "correo"), true)) {
        continue;
      }
      if ($normalizado["tipo"] === "telefono" && preg_match('/^0{3,}/', $normalizado["valor_normalizado"]) === 1) {
        continue;
      }
      if ($normalizado["tipo"] === "telefono" && strlen($normalizado["valor_normalizado"]) < 10) {
        continue;
      }
      $identificadores[] = array(
        "tipo" => $normalizado["tipo"],
        "valor" => $valor,
        "valor_normalizado" => $normalizado["valor_normalizado"],
        "principal" => empty($identificadores) ? 1 : 0,
        "origen_campo" => $campo
      );
    }
    return $identificadores;
  }

  private function indicesDuplicadosLegacy($db) {
    $indices = array();
    if (!$this->tablaExiste($db, "crm_clientes")) {
      return $indices;
    }
    $rows = $db->query("SELECT id_cliente, correo, contacto1, contacto2 FROM crm_clientes")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      foreach ($this->identificadoresLegacyRow($row) as $identificador) {
        $llave = $identificador["tipo"] . ":" . $identificador["valor_normalizado"];
        $indices[$llave][] = intval($row["id_cliente"]);
      }
    }
    foreach ($indices as $llave => $ids) {
      $indices[$llave] = array_values(array_unique($ids));
    }
    return array_filter($indices, function ($ids) {
      return count($ids) > 1;
    });
  }

  private function indicesVinculosLegacyActivos($db) {
    $indices = array();
    if (!$this->tablaExiste($db, "crm_clientes_vinculos_externos")) {
      return $indices;
    }
    $stmt = $db->query("SELECT id_origen, id_cliente_crm
        FROM crm_clientes_vinculos_externos
        WHERE sistema_origen='legacy' AND entidad_origen='crm_clientes' AND estatus='activo'");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $indices[intval($row["id_origen"])] = intval($row["id_cliente_crm"]);
    }
    return $indices;
  }

  private function indicesIdentificadoresCrmActivos($db) {
    $indices = array();
    if (!$this->tablaExiste($db, "crm_clientes_identificadores")) {
      return $indices;
    }
    $stmt = $db->query("SELECT tipo, valor_normalizado, id_cliente_crm
        FROM crm_clientes_identificadores
        WHERE estatus='activo'");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $llave = $row["tipo"] . ":" . $row["valor_normalizado"];
      $indices[$llave] = intval($row["id_cliente_crm"]);
    }
    return $indices;
  }

  private function auditarFuenteErpClientes($db) {
    $tabla = "erp_clientes";
    if (!$this->tablaExiste($db, $tabla)) {
      return array("existe" => false, "registros" => null);
    }
    $registros = $this->contarTabla($db, $tabla);
    $identificadores = $this->tablaExiste($db, "erp_clientes_identificadores")
        ? $this->contarTabla($db, "erp_clientes_identificadores")
        : null;
    return array(
      "existe" => true,
      "registros" => $registros,
      "identificadores" => $identificadores,
      "columnas" => $this->listarColumnas($db, $tabla),
      "uso_recomendado" => "fuente_pos_uat_o_vinculo_externo"
    );
  }

  private function auditarFuenteVentasSnapshot($db) {
    $tabla = "erp_ventas";
    if (!$this->tablaExiste($db, $tabla)) {
      return array("existe" => false, "registros" => null);
    }
    $sql = "SELECT
        COUNT(*) total,
        SUM(CASE WHEN id_cliente IS NULL THEN 1 ELSE 0 END) publico_general,
        SUM(CASE WHEN id_cliente IS NOT NULL THEN 1 ELSE 0 END) con_cliente,
        SUM(CASE WHEN cliente_nombre_publico IS NOT NULL AND cliente_nombre_publico<>'' THEN 1 ELSE 0 END) con_snapshot_nombre
      FROM erp_ventas";
    $resumen = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    return array(
      "existe" => true,
      "registros" => intval($resumen["total"]),
      "publico_general" => intval($resumen["publico_general"]),
      "con_cliente" => intval($resumen["con_cliente"]),
      "con_snapshot_nombre" => intval($resumen["con_snapshot_nombre"]),
      "contrato" => "ventas_pasadas_deben_conservar_snapshot"
    );
  }

  private function nombreLegacy($row) {
    $partes = array(
      trim((string) $this->valor($row, "alias", "")),
      trim((string) $this->valor($row, "nombres", "")),
      trim((string) $this->valor($row, "apellido_paterno", "")),
      trim((string) $this->valor($row, "apellido_materno", ""))
    );
    $partes = array_values(array_filter($partes, function ($valor) {
      return $valor !== "";
    }));
    return trim(preg_replace('/\s+/', ' ', implode(" ", $partes)));
  }

  private function listarColumnas($db, $tabla) {
    if (!$this->tablaExiste($db, $tabla)) {
      return array();
    }
    $stmt = $db->prepare("SELECT COLUMN_NAME columna, COLUMN_TYPE tipo, IS_NULLABLE permite_null, COLUMN_KEY llave
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla
        ORDER BY ORDINAL_POSITION");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function duplicadosIdentificador($identificadores) {
    $duplicados = array();
    foreach ($identificadores as $llave => $ids) {
      $idsUnicos = array_values(array_unique($ids));
      if (count($idsUnicos) > 1) {
        $duplicados[] = array(
          "identificador" => $llave,
          "clientes" => $idsUnicos,
          "total" => count($idsUnicos)
        );
      }
    }
    return array_slice($duplicados, 0, 20);
  }

  private function sugerirCodigoClienteCrm($db, $origenAlta = "crm") {
    $origen = preg_replace('/[^A-Z0-9]+/', '', strtoupper((string) $origenAlta));
    if ($origen === "") {
      $origen = "CRM";
    }
    $prefijo = "CRM-" . $origen . "-" . date("Ymd") . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_maestro WHERE codigo_cliente LIKE :prefijo");
    $stmt->execute(array(":prefijo" => $prefijo . "%"));
    return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 4, "0", STR_PAD_LEFT);
  }

  private function consultarFilasCliente($db, $tabla, $idClienteCrm, $orden) {
    if (!$this->tablaExiste($db, $tabla)) {
      return array();
    }
    $ordenSeguro = preg_replace('/[^a-zA-Z0-9_,. ]+/', '', (string) $orden);
    $stmt = $db->prepare("SELECT * FROM `$tabla` WHERE id_cliente_crm=:cliente ORDER BY " . $ordenSeguro);
    $stmt->execute(array(":cliente" => intval($idClienteCrm)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function liberarLockCliente($db, $lockName) {
    if (!$db || trim((string) $lockName) === "") {
      return;
    }
    try {
      $stmt = $db->prepare("SELECT RELEASE_LOCK(:lock_name)");
      $stmt->execute(array(":lock_name" => $lockName));
    } catch (Exception $e) {
    }
  }

  private function estadoTablasConteo($db, $tablas) {
    $estado = array();
    foreach ($tablas as $tabla) {
      $existe = $this->tablaExiste($db, $tabla);
      $estado[] = array(
        "tabla" => $tabla,
        "existe" => $existe,
        "registros" => $existe ? $this->contarTabla($db, $tabla) : null
      );
    }
    return $estado;
  }

  private function tablaExiste($db, $tabla) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return false;
    }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function contarTabla($db, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return null;
    }
    try {
      return intval($db->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn());
    } catch (Exception $e) {
      return null;
    }
  }

  private function normalizarReglasProgramaRecompensas($entrada, &$avisos, &$bloqueos) {
    if (is_string($entrada)) {
      $texto = trim($entrada);
      if ($texto === "") {
        $entrada = array();
      } else {
        $decodificado = json_decode($texto, true);
        if (!is_array($decodificado)) {
          $bloqueos[] = "Reglas deben ser JSON valido";
          $decodificado = array();
        }
        $entrada = $decodificado;
      }
    }
    if (!is_array($entrada)) {
      $entrada = array();
    }

    $base = isset($entrada["acumulacion"]["base"]) ? trim((string)$entrada["acumulacion"]["base"]) : "monto_pagado";
    $puntosPorUnidad = isset($entrada["acumulacion"]["puntos_por_unidad"]) ? floatval($entrada["acumulacion"]["puntos_por_unidad"]) : 1;
    $unidadMonto = isset($entrada["acumulacion"]["unidad_monto"]) ? floatval($entrada["acumulacion"]["unidad_monto"]) : 10;
    $incluyeImpuestos = isset($entrada["acumulacion"]["incluye_impuestos"]) ? intval($entrada["acumulacion"]["incluye_impuestos"]) === 1 : true;
    $modoRedencion = isset($entrada["redencion"]["modo"]) ? trim((string)$entrada["redencion"]["modo"]) : "pendiente";
    $valorPunto = isset($entrada["redencion"]["valor_punto"]) ? floatval($entrada["redencion"]["valor_punto"]) : 0;
    $caducidadDias = isset($entrada["caducidad"]["dias"]) ? intval($entrada["caducidad"]["dias"]) : 0;
    $redencionMinima = isset($entrada["redencion"]["minimo_puntos"]) ? floatval($entrada["redencion"]["minimo_puntos"]) : 0;

    if (!in_array($base, array("monto_pagado", "margen", "sku", "categoria", "manual", "campana"), true)) {
      $bloqueos[] = "Base de acumulacion invalida";
    }
    if ($puntosPorUnidad <= 0) {
      $bloqueos[] = "Puntos por unidad debe ser mayor a cero";
    }
    if ($unidadMonto <= 0) {
      $bloqueos[] = "Unidad de monto debe ser mayor a cero";
    }
    if (!in_array($modoRedencion, array("pendiente", "descuento_pos", "monedero", "manual"), true)) {
      $bloqueos[] = "Modo de redencion invalido";
    }
    if ($modoRedencion !== "pendiente" && $valorPunto <= 0) {
      $bloqueos[] = "Valor de punto requerido para redencion activa";
    }
    if ($caducidadDias > 0 && $caducidadDias < 30) {
      $avisos[] = "Caducidad menor a 30 dias puede generar mala experiencia de cliente";
    }
    if ($redencionMinima < 0) {
      $bloqueos[] = "Minimo de redencion no puede ser negativo";
    }

    return array(
      "acumulacion" => array(
        "base" => $base,
        "puntos_por_unidad" => $puntosPorUnidad,
        "unidad_monto" => $unidadMonto,
        "incluye_impuestos" => $incluyeImpuestos
      ),
      "redencion" => array(
        "modo" => $modoRedencion,
        "valor_punto" => $valorPunto,
        "minimo_puntos" => $redencionMinima
      ),
      "caducidad" => array(
        "dias" => $caducidadDias
      ),
      "restricciones" => isset($entrada["restricciones"]) && is_array($entrada["restricciones"]) ? $entrada["restricciones"] : array(),
      "notas" => isset($entrada["notas"]) ? trim((string)$entrada["notas"]) : ""
    );
  }

  private function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
