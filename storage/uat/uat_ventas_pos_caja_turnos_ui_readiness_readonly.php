<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: auditar Caja/Turnos POS para apertura/cierre manual desde UI sin escribir BD.
 * Impacto: valida endpoint, modelo, vista, JS, asignacion oficial y guardrails de confirmacion.
 * Contrato: read-only; no abre turno, no cierra turno, no mueve caja, no crea ventas ni modifica inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idCaja = 2;
$montoInicial = 500;

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(str_replace(",", ".", trim(substr($arg, 16), "\"' ")));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$root = realpath(__DIR__ . "/../..");
$controllerPath = $root . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "controladores" . DIRECTORY_SEPARATOR . "Ventas.php";
$modelPath = $root . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "modelos" . DIRECTORY_SEPARATOR . "VentasErp.php";
$viewPath = $root . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "vistas" . DIRECTORY_SEPARATOR . "paginas" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "caja_turnos.php";
$jsPath = $root . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "caja_turnos.js";

$controller = is_file($controllerPath) ? file_get_contents($controllerPath) : "";
$model = is_file($modelPath) ? file_get_contents($modelPath) : "";
$view = is_file($viewPath) ? file_get_contents($viewPath) : "";
$js = is_file($jsPath) ? file_get_contents($jsPath) : "";

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$aperturaDryRun = $ventas->aperturaTurnoDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "monto_inicial" => $montoInicial
));
$aperturaSinConfirmacion = $ventas->abrirTurnoRealPos(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "monto_inicial" => $montoInicial,
    "confirmacion" => "NO"
));
$cierreSinConfirmacion = $ventas->cerrarTurnoRealPos(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => 0,
    "monto_contado" => $montoInicial,
    "confirmacion" => "NO"
));

$depurarAsignacion = valor($asignacion, "depurar", array());
$turnoAbierto = valor($depurarAsignacion, "turno_abierto", null);
$checks = array(
    "controlador_apertura_real" => strpos($controller, "public function turno_apertura_real_erp") !== false
        && strpos($controller, "abrirTurnoRealPos") !== false
        && strpos($controller, "ventas.operar") !== false
        && strpos($controller, "pos_turno_") !== false,
    "controlador_cierre_real" => strpos($controller, "public function turno_cierre_real_erp") !== false
        && strpos($controller, "cerrarTurnoRealPos") !== false,
    "modelo_apertura_real_transaccional" => strpos($model, "public function abrirTurnoRealPos") !== false
        && strpos($model, "beginTransaction") !== false
        && strpos($model, "ABRIR TURNO") !== false
        && strpos($model, "siguienteFolioTurnoPos") !== false,
    "modelo_cierre_real_transaccional" => strpos($model, "public function cerrarTurnoRealPos") !== false
        && strpos($model, "CERRAR TURNO") !== false,
    "vista_caja_turnos_productiva" => strpos($view, "Abrir/cerrar real exige confirmacion") !== false
        && strpos($view, "20260718-apertura-real1") !== false,
    "js_apertura_real_ui" => strpos($js, "/ventas/turno_apertura_real_erp") !== false
        && strpos($js, "function abrirTurnoReal") !== false
        && strpos($js, "ABRIR TURNO") !== false,
    "js_cierre_real_ui" => strpos($js, "/ventas/turno_cierre_real_erp") !== false
        && strpos($js, "function cerrarTurnoReal") !== false
        && strpos($js, "CERRAR TURNO") !== false,
    "asignacion_oficial_activa" => !empty($depurarAsignacion["asignacion_activa"]),
    "apertura_dryrun_sin_bloqueos" => empty(valor(valor($aperturaDryRun, "depurar", array()), "bloqueos", array())),
    "apertura_bloquea_sin_confirmacion" => in_array("Escribe ABRIR TURNO para confirmar", valor(valor($aperturaSinConfirmacion, "depurar", array()), "bloqueos", array()), true),
    "cierre_bloquea_sin_confirmacion" => in_array("Escribe CERRAR TURNO para confirmar", valor(valor($cierreSinConfirmacion, "depurar", array()), "bloqueos", array()), true)
);

$bloqueos = array();
foreach ($checks as $check => $ok) {
    if (!$ok) {
        $bloqueos[] = "No cumple check: " . $check;
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_caja_turnos_ui_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "monto_inicial" => $montoInicial
    ),
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "estado_operativo" => array(
        "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
        "turno_abierto" => !empty($turnoAbierto),
        "turno_abierto_folio" => is_array($turnoAbierto) ? valor($turnoAbierto, "folio", null) : null,
        "folio_sugerido_apertura" => valor(valor($aperturaDryRun, "depurar", array()), "folio_sugerido", null),
        "apertura_bloqueos" => valor(valor($aperturaDryRun, "depurar", array()), "bloqueos", array())
    ),
    "guardrails" => array(
        "apertura_sin_confirmacion" => valor(valor($aperturaSinConfirmacion, "depurar", array()), "bloqueos", array()),
        "cierre_sin_confirmacion" => valor(valor($cierreSinConfirmacion, "depurar", array()), "bloqueos", array())
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cierra_turno" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    ),
    "siguiente_uat_visual" => array(
        "abrir_desde_ui" => "/ventas/caja_turnos -> Validar apertura -> escribir ABRIR TURNO",
        "cerrar_desde_ui" => "/ventas/caja_turnos -> Validar corte -> escribir CERRAR TURNO"
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
