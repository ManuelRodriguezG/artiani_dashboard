<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: consolidar readiness de salida a piloto operativo POS.
 * Impacto: revisa Go/No-Go, productivo read-only, documentos vivos y scripts criticos sin escribir BD.
 * Contrato: read-only; no abre turno, no cobra, no resuelve pendientes, no mueve caja ni inventario.
 */

date_default_timezone_set("America/Mexico_City");

$idUsuario = 1;
$idAlmacen = 5;
$idCaja = 2;
$idTerminal = 2;
$idSku = 1760;
$idAtencion = 2;
$cantidad = 1;
$usuarios = "1,2,3";
$compact = false;

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_terminal=") === 0) {
        $idTerminal = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(str_replace(",", ".", trim(substr($arg, 11), "\"' ")));
    } elseif (strpos($arg, "--usuarios=") === 0) {
        $usuarios = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--compact=") === 0) {
        $compact = in_array(strtolower(trim(substr($arg, 10), "\"' ")), array("1", "true", "si", "yes"), true);
    }
}

$root = realpath(__DIR__ . "/../..");

$checks = array(
    "entorno_canonico" => ejecutar("uat_ventas_pos_entorno_canonico_readiness_readonly.php", array()),
    "operadores_identidad" => ejecutar("uat_ventas_pos_operadores_identidad_readonly.php", array(
        "--usuarios=" . $usuarios,
    )),
    "impresion" => ejecutar("uat_ventas_pos_impresion_readiness_readonly.php", array()),
    "go_nogo" => ejecutar("uat_ventas_pos_piloto_go_nogo_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen,
        "--id_caja=" . $idCaja,
        "--id_terminal=" . $idTerminal,
        "--id_sku=" . $idSku,
        "--id_atencion=" . $idAtencion,
        "--cantidad=" . $cantidad,
        "--usuarios=" . $usuarios,
    )),
    "productivo" => ejecutar("uat_ventas_pos_productivo_readiness_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen,
        "--id_sku=" . $idSku,
        "--cantidad=" . $cantidad,
    )),
);

$docs = array(
    "manual_cajero" => array(
        "ruta" => "docs/erp_ventas_pos_manual_cajero.md",
        "tokens" => array(
            "Ventas > Caja/Turnos" => "apertura/cierre desde modulo caja",
            "ABRIR TURNO" => "confirmacion apertura",
            "CERRAR TURNO" => "confirmacion cierre",
            "F3" => "atajo escaner",
            "Ctrl+Enter" => "atajo cobro",
            "ticket" => "ticket operativo",
            "kardex" => "kardex/trazabilidad",
        ),
    ),
    "runbook_turno_1" => array(
        "ruta" => "docs/erp_ventas_pos_piloto_turno_1_runbook.md",
        "tokens" => array(
            "Primer piloto" => "alcance piloto",
            "uat_ventas_pos_piloto_go_nogo_readonly.php" => "go-nogo documentado",
            "uat_ventas_pos_atajos_ui_readiness_readonly.php" => "atajos documentados",
            "uat_ventas_pos_ticket_trazabilidad_readiness_readonly.php" => "ticket/trazabilidad documentado",
            "PINV-20260717-000001" => "pendiente inventario vigente visible",
            "GASTO-UAT-001" => "evidencia caja pendiente visible",
        ),
    ),
    "handoff" => array(
        "ruta" => "docs/erp_ventas_pos_handoff_contexto.md",
        "tokens" => array(
            "C:\\xampp\\htdocs\\panel_de_control" => "proyecto canonico",
            "http://panel.com.local/" => "host canonico",
            "turnos_ui" => "turnos integrados",
            "atajos_ui" => "atajos integrados",
            "ticket_trazabilidad" => "ticket/trazabilidad integrado",
            "reportes_piloto" => "reportes integrados",
        ),
    ),
    "checklist" => array(
        "ruta" => "docs/erp_ventas_pos_piloto_operativo_checklist.md",
        "tokens" => array(
            "apertura/cierre manual de turnos desde UI" => "turnos UI en checklist",
            "atajos rapidos POS" => "atajos en checklist",
            "ticket formal, garantia snapshot y trazabilidad" => "ticket/trazabilidad en checklist",
            "multiusuario" => "multiusuario en checklist",
            "reportes piloto" => "reportes en checklist",
        ),
    ),
    "estado_cierre_modulo" => array(
        "ruta" => "docs/erp_ventas_pos_estado_cierre_modulo.md",
        "tokens" => array(
            "listo_para_piloto_controlado_con_condiciones" => "decision vigente",
            "PINV-20260717-000001" => "pendiente inventario vigente",
            "GASTO-UAT-001" => "evidencia caja vigente",
            "Scanner POS" => "scanner POS documentado",
            "Siguiente autorizacion fuerte posible" => "siguiente autorizacion documentada",
        ),
    ),
);

$docsResultado = array();
$bloqueos = array();
$avisos = array();

foreach ($checks as $nombre => $check) {
    if (empty(valor($check, "ok", false))) {
        $bloqueos[] = "Check " . $nombre . " no esta en ok";
    }
    foreach (valor($check, "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = $nombre . ": " . (is_array($bloqueo) ? json_encode($bloqueo, JSON_UNESCAPED_UNICODE) : $bloqueo);
    }
    foreach (valor($check, "avisos", array()) as $aviso) {
        $avisos[] = $nombre . ": " . (is_array($aviso) ? json_encode($aviso, JSON_UNESCAPED_UNICODE) : $aviso);
    }
}

foreach ($docs as $clave => $doc) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $doc["ruta"]);
    $contenido = is_file($ruta) ? file_get_contents($ruta) : "";
    $docsResultado[$clave] = array("ruta" => $doc["ruta"], "existe" => is_file($ruta), "checks" => array());
    if (!is_file($ruta)) {
        $bloqueos[] = "Falta documento operativo: " . $doc["ruta"];
        continue;
    }
    foreach ($doc["tokens"] as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $docsResultado[$clave]["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = "Documento " . $doc["ruta"] . " no menciona " . $descripcion . " [" . $token . "]";
        }
    }
}

$goNogo = valor($checks, "go_nogo", array());
$productivo = valor($checks, "productivo", array());
$entornoCanonico = valor($checks, "entorno_canonico", array());
$decision = empty($bloqueos) ? "listo_para_piloto_controlado_con_condiciones" : "no_listo";
$condiciones = array_values(array_unique(array_filter(array(
    "Abrir turno antes de vender.",
    "Usar SKU con existencia disponible o resolver/cargar inventario con autorizacion.",
    "Resolver o mantener identificado el pendiente PINV-20260717-000001.",
    "Cerrar/documentar evidencia caja GASTO-UAT-001.",
    "Mantener fuera del primer piloto devoluciones reales, descuentos libres, apartados nuevos e inventario pendiente productivo.",
))));

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_salida_operativa_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "decision" => $decision,
    "resumen" => array(
        "entorno_canonico_ok" => !empty(valor($entornoCanonico, "ok", false)),
        "go_nogo_ok" => !empty(valor($goNogo, "ok", false)),
        "go_nogo_decision" => valor($goNogo, "decision", null),
        "multiusuario_listo" => valor($goNogo, "multiusuario_listo", null),
        "productivo_ok" => !empty(valor($productivo, "ok", false)),
        "documentos_revisados" => count($docsResultado),
        "bloqueos_total" => count(array_unique($bloqueos)),
        "avisos_total" => count(array_unique($avisos)),
    ),
    "condiciones_para_piloto" => $condiciones,
    "checks" => $checks,
    "documentos" => $docsResultado,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_resuelve_pendientes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

if ($compact) {
    $respuesta = array(
        "ok" => $respuesta["ok"],
        "modo" => $respuesta["modo"],
        "read_only" => true,
        "proyecto_canonico" => $respuesta["proyecto_canonico"],
        "host" => $respuesta["host"],
        "decision" => $respuesta["decision"],
        "resumen" => $respuesta["resumen"],
        "condiciones_para_piloto" => $respuesta["condiciones_para_piloto"],
        "bloqueos" => $respuesta["bloqueos"],
        "avisos" => $respuesta["avisos"],
        "contrato" => $respuesta["contrato"],
    );
}

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);

function ejecutar($script, $args)
{
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $script;
    if (!is_file($ruta)) {
        return array("ok" => false, "bloqueos" => array("Script no encontrado: " . $script));
    }
    $cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($ruta);
    foreach ($args as $arg) {
        $cmd .= " " . escapeshellarg($arg);
    }
    $lineas = array();
    $codigo = 0;
    exec($cmd, $lineas, $codigo);
    $json = json_decode(implode("\n", $lineas), true);
    if (!is_array($json)) {
        return array("ok" => false, "exit_code" => $codigo, "bloqueos" => array("Salida no JSON de " . $script));
    }
    $json["exit_code"] = $codigo;
    return $json;
}

function valor($datos, $campo, $default = null)
{
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
