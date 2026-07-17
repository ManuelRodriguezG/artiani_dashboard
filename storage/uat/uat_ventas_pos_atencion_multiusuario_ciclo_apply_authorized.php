<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: orquestar el ciclo UAT real de atencion POS multiusuario en una sola corrida autorizada.
 * Impacto: si se autoriza, abre turno, carga stock, cobra atencion, valida conversion y cierra turno usando scripts oficiales.
 * Contrato: BLOQUEADO por defecto; requiere token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL y respaldo vigente.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$idAtencion = 2;
$cantidadStock = 1;
$pago = 295;
$montoInicial = 500;
$montoContado = 795;
$referenciaStock = "INV-INICIAL-POS-UAT-ATENCION-MULTI-1760";
$observacionesApertura = "Apertura UAT POS cobro atencion multiusuario";
$observacionesCierre = "Cierre UAT POS atencion multiusuario convertida";
$soloPrevalidar = false;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--cantidad_stock=") === 0) {
        $cantidadStock = floatval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--referencia_stock=") === 0) {
        $referenciaStock = trim(substr($arg, 19), "\"' ");
    } elseif (strpos($arg, "--prevalidar=") === 0) {
        $soloPrevalidar = intval(trim(substr($arg, 13), "\"' ")) === 1;
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if (!$soloPrevalidar && ($autorizar !== "VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idAlmacen <= 0 || $idSku <= 0 || $idAtencion <= 0 || $cantidadStock <= 0 || $pago <= 0)) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se ejecuto ciclo UAT multiusuario. Falta token, respaldo o parametros minimos.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL",
            "--respaldo=UAT_POS_VIGENTE",
            "--id_usuario=1",
            "--id_almacen=5",
            "--id_sku=1760",
            "--id_atencion=2",
            "--cantidad_stock=1",
            "--pago=295",
            "--monto_inicial=500",
            "--monto_contado=795"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "alcance_si_autorizado" => array(
            "abre_turno" => true,
            "carga_stock" => true,
            "cobra_atencion" => true,
            "crea_venta" => true,
            "mueve_caja" => true,
            "mueve_inventario" => true,
            "cierra_turno" => true,
            "postchecks_readonly" => true
        )
    ));
}

$prevalidacionInicial = prevalidarAntesDeEscrituras($idUsuario, $idAlmacen, $idSku, $idAtencion, $referenciaStock);
if ($soloPrevalidar) {
    responder(array(
        "ok" => empty($prevalidacionInicial["bloqueos"]),
        "modo" => "prevalidacion_inicial_readonly",
        "host" => "panel.com.local",
        "prevalidacion" => $prevalidacionInicial,
        "contrato" => array(
            "read_only" => true,
            "no_abre_turno" => true,
            "no_carga_stock" => true,
            "no_cobra" => true,
            "no_cierra_turno" => true
        )
    ));
}
if (!empty($prevalidacionInicial["bloqueos"])) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "prevalidacion_inicial_readonly",
        "mensaje" => "No se inicio el ciclo porque hay bloqueos antes de cualquier escritura.",
        "prevalidacion" => $prevalidacionInicial
    ));
}

$base = __DIR__;
$pasos = array();

$pasos[] = ejecutar("pre_semaforo", array(
    "uat_ventas_pos_cierre_modulo_readiness_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_atencion=" . $idAtencion,
    "--id_sku=" . $idSku,
    "--compact=1"
), true);

$pasos[] = ejecutar("abrir_turno", array(
    "uat_ventas_pos_turno_apertura_apply_authorized.php",
    "--autorizar=VENTAS_POS_TURNO_APERTURA",
    "--respaldo=" . $respaldo,
    "--id_usuario=" . $idUsuario,
    "--monto_inicial=" . $montoInicial,
    "--observaciones=" . $observacionesApertura
), false);
detenerSiFalla($pasos);

$pasos[] = ejecutar("cargar_stock", array(
    "uat_ventas_pos_stock_uat_apply_authorized.php",
    "--autorizar=VENTAS_POS_STOCK_UAT",
    "--respaldo=" . $respaldo,
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--cantidad=" . $cantidadStock,
    "--referencia=" . $referenciaStock
), false);
detenerSiFalla($pasos);

$pasos[] = ejecutar("post_stock_semaforo", array(
    "uat_ventas_pos_cierre_modulo_readiness_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_atencion=" . $idAtencion,
    "--id_sku=" . $idSku,
    "--compact=1"
), true);

$pasos[] = ejecutar("cobrar_atencion", array(
    "uat_ventas_pos_atencion_cobro_apply_authorized.php",
    "--autorizar=VENTAS_POS_ATENCION_TOMAR_COBRAR_REAL",
    "--respaldo=" . $respaldo,
    "--id_usuario=" . $idUsuario,
    "--id_atencion=" . $idAtencion,
    "--pago=" . $pago,
    "--referencia_pago=UAT-ATENCION-MULTI-" . $idAtencion
), false);
detenerSiFalla($pasos);

$ventaGenerada = extraerVentaGenerada($pasos);
if (empty($ventaGenerada["folio"]) || intval($ventaGenerada["id_venta"]) <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "ciclo_interrumpido",
        "mensaje" => "El cobro no devolvio folio/id_venta; no se continua para evitar cerrar una UAT incompleta.",
        "venta_generada" => $ventaGenerada,
        "pasos" => $pasos
    ));
}

$pasos[] = ejecutar("post_conversion", array(
    "uat_ventas_pos_atencion_conversion_post_readonly.php",
    "--id_atencion=" . $idAtencion,
    "--compact=1"
), false);
detenerSiFalla($pasos);

$pasos[] = ejecutar("ticket_formal", array(
    "uat_ventas_pos_ticket_formal_readonly.php",
    "--folio=" . $ventaGenerada["folio"],
    "--compact=1"
), false);
detenerSiFalla($pasos);

$pasos[] = ejecutar("post_venta", array(
    "uat_ventas_pos_post_venta_readonly.php",
    "--folio=" . $ventaGenerada["folio"]
), false);
detenerSiFalla($pasos);

$pasos[] = ejecutar("cerrar_turno", array(
    "uat_ventas_pos_turno_cierre_apply_authorized.php",
    "--autorizar=VENTAS_POS_TURNO_CIERRE",
    "--respaldo=" . $respaldo,
    "--id_usuario=" . $idUsuario,
    "--monto_contado=" . $montoContado,
    "--observaciones=" . $observacionesCierre
), false);
detenerSiFalla($pasos);

$pasos[] = ejecutar("post_cierre_semaforo", array(
    "uat_ventas_pos_cierre_modulo_readiness_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_atencion=" . $idAtencion,
    "--id_sku=" . $idSku,
    "--compact=1"
), true);

responder(array(
    "ok" => true,
    "modo" => "ventas_pos_atencion_multiusuario_ciclo_apply_authorized",
    "host" => "panel.com.local",
    "respaldo_ref" => $respaldo,
    "venta_generada" => $ventaGenerada,
    "pasos" => $pasos,
    "siguiente_paso" => "Registrar folios reales en docs/erp_ventas_pos_uat_manual.md y revisar ticket/postventa por folio generado."
));

function ejecutar($nombre, $argumentos, $permitirFalla = false) {
    $script = array_shift($argumentos);
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $script;
    $cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($ruta);
    foreach ($argumentos as $arg) {
        $cmd .= " " . escapeshellarg($arg);
    }
    $lineas = array();
    $codigo = 0;
    exec($cmd, $lineas, $codigo);
    $texto = implode("\n", $lineas);
    $json = json_decode($texto, true);
    return array(
        "paso" => $nombre,
        "ok" => $codigo === 0 || $permitirFalla,
        "exit_code" => $codigo,
        "permitir_falla" => $permitirFalla,
        "salida_json" => is_array($json) ? $json : null,
        "salida_texto" => is_array($json) ? null : $texto
    );
}

function detenerSiFalla($pasos) {
    $ultimo = end($pasos);
    if (empty($ultimo["ok"])) {
        responder(array(
            "ok" => false,
            "modo" => "ciclo_interrumpido",
            "mensaje" => "El ciclo se detuvo para evitar efectos encadenados tras una falla.",
            "pasos" => $pasos
        ));
    }
}

function extraerVentaGenerada($pasos) {
    foreach ($pasos as $paso) {
        if (!isset($paso["paso"]) || $paso["paso"] !== "cobrar_atencion") {
            continue;
        }
        $salida = isset($paso["salida_json"]) && is_array($paso["salida_json"]) ? $paso["salida_json"] : array();
        $resultado = isset($salida["resultado"]) && is_array($salida["resultado"]) ? $salida["resultado"] : array();
        $depurar = isset($resultado["depurar"]) && is_array($resultado["depurar"]) ? $resultado["depurar"] : array();
        return array(
            "folio" => isset($depurar["folio"]) ? (string) $depurar["folio"] : "",
            "id_venta" => isset($depurar["id_venta"]) ? intval($depurar["id_venta"]) : 0
        );
    }
    return array("folio" => "", "id_venta" => 0);
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $pareceRuta) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    return array(
        "ok" => $respaldo !== "" && strlen($respaldo) >= 8 && (!$pareceRuta || ($existe && $legible && $tamano !== null && $tamano > 0)),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $pareceRuta ? $existe : null,
        "archivo_legible" => $pareceRuta ? $legible : null,
        "tamano" => $tamano
    );
}

function prevalidarAntesDeEscrituras($idUsuario, $idAlmacen, $idSku, $idAtencion, $referenciaStock) {
    chdir(__DIR__ . "/../../public");
    $_SERVER["SERVER_NAME"] = "panel.com.local";
    require_once "../app/iniciador.php";
    require_once "../app/modelos/VentasErp.php";

    if (!class_exists("UatVentasPosAtencionMultiusuarioCicloDb", false)) {
        class UatVentasPosAtencionMultiusuarioCicloDb extends CRUD {
            public function db() {
                return $this->getConexion();
            }
        }
    }

    $db = (new UatVentasPosAtencionMultiusuarioCicloDb())->db();
    $ventas = new VentasErp();
    $bloqueos = array();
    $avisos = array();

    $tablas = array("erp_pos_atenciones", "erp_pos_atenciones_detalle", "erp_inventario_movimientos", "erp_pos_turnos");
    foreach ($tablas as $tabla) {
        $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
        $stmt->execute(array(":tabla" => $tabla));
        if (!$stmt->fetchColumn()) {
            $bloqueos[] = "Falta tabla requerida: " . $tabla;
        }
    }
    if (!empty($bloqueos)) {
        return array("bloqueos" => $bloqueos, "avisos" => $avisos);
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=:referencia");
    $stmt->execute(array(":referencia" => $referenciaStock));
    if (intval($stmt->fetchColumn()) > 0) {
        $bloqueos[] = "La referencia de stock ya fue usada: " . $referenciaStock;
    }

    $stmt = $db->prepare("SELECT id_atencion_pos, folio_temporal, id_almacen, estatus, total, id_venta_convertida
        FROM erp_pos_atenciones
        WHERE id_atencion_pos=:atencion
        LIMIT 1");
    $stmt->execute(array(":atencion" => $idAtencion));
    $atencion = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$atencion) {
        $bloqueos[] = "Atencion POS no encontrada: " . $idAtencion;
    } else {
        if (intval($atencion["id_almacen"]) !== intval($idAlmacen)) {
            $bloqueos[] = "La atencion pertenece a otro almacen";
        }
        if (!in_array($atencion["estatus"], array("abierta", "lista_para_cobro", "tomada_por_caja"), true)) {
            $bloqueos[] = "La atencion no esta disponible para cobro: " . $atencion["estatus"];
        }
        if (intval($atencion["id_venta_convertida"]) > 0) {
            $bloqueos[] = "La atencion ya tiene venta convertida";
        }
    }

    $detalle = $ventas->atencionDetalleReadOnly(array("id_atencion" => $idAtencion, "id_almacen" => $idAlmacen));
    $partidas = isset($detalle["depurar"]["partidas"]) && is_array($detalle["depurar"]["partidas"]) ? $detalle["depurar"]["partidas"] : array();
    if (!empty($detalle["error"]) || count($partidas) <= 0) {
        $bloqueos[] = "La atencion no tiene detalle activo para cobrar";
    }

    $asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
    $depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
    $datosAsignacion = isset($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();
    if (!empty($asignacion["error"]) || empty($depurarAsignacion["asignacion_activa"]) || empty($datosAsignacion)) {
        $bloqueos[] = "Usuario sin asignacion POS activa";
    } elseif (intval($datosAsignacion["id_almacen"]) !== intval($idAlmacen)) {
        $bloqueos[] = "La asignacion POS del usuario no corresponde al almacen solicitado";
    }
    if (!empty($depurarAsignacion["turno_abierto"])) {
        $avisos[] = "Ya existe un turno abierto; el paso de apertura puede bloquear para evitar duplicidad";
    }

    return array(
        "bloqueos" => array_values(array_unique($bloqueos)),
        "avisos" => array_values(array_unique($avisos)),
        "atencion" => $atencion ?: null,
        "partidas_atencion" => count($partidas),
        "asignacion" => $datosAsignacion
    );
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
