<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar que Caja/Turnos exponga apertura y cierre manual reales con confirmacion fuerte.
 * Impacto: POS/Caja; revisa contratos UI/controlador sin escribir BD ni invocar HTTP.
 * Contrato: read-only; no abre turno, no cierra turno, no mueve caja ni inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$archivos = array(
    "vista" => $root . "/app/vistas/paginas/apps/erp/ventas/caja_turnos.php",
    "js" => $root . "/public/assets/js/custom/apps/erp/ventas/caja_turnos.js",
    "controlador" => $root . "/app/controladores/Ventas.php",
    "modelo" => $root . "/app/modelos/VentasErp.php",
);

$checks = array(
    "vista" => array(
        "pos_caja_apertura_dryrun" => "boton validar apertura",
        "pos_caja_apertura_resultado" => "resultado apertura",
        "pos_caja_corte_dryrun" => "boton validar corte",
        "pos_caja_monto_contado" => "monto contado alimentado por arqueo",
        "pos_caja_corte_resultado" => "resultado cierre",
        "Diferencias quedan en reportes" => "mensaje operativo diferencias",
    ),
    "js" => array(
        "/ventas/turno_apertura_dryrun_erp" => "endpoint dry-run apertura",
        "/ventas/turno_apertura_real_erp" => "endpoint real apertura",
        "/ventas/turno_cierre_dryrun_erp" => "endpoint dry-run cierre",
        "/ventas/turno_cierre_real_erp" => "endpoint real cierre",
        "ABRIR TURNO" => "confirmacion fuerte apertura",
        "CERRAR TURNO" => "confirmacion fuerte cierre",
        "calcularArqueo()" => "arqueo alimenta monto contado",
        "quedara registrado para revision y reportes" => "diferencia no bloquea cierre",
        "pos_caja_apertura_real" => "boton dinamico apertura real",
        "pos_caja_cierre_real" => "boton dinamico cierre real",
    ),
    "controlador" => array(
        "function turno_apertura_dryrun_erp" => "controlador dry-run apertura",
        "function turno_apertura_real_erp" => "controlador real apertura",
        "function turno_cierre_dryrun_erp" => "controlador dry-run cierre",
        "function turno_cierre_real_erp" => "controlador real cierre",
        '$this->requerirPermiso("ventas.operar")' => "permiso operar ventas",
        "confirmacion `CERRAR TURNO`" => "contrato documentado cierre",
    ),
    "modelo" => array(
        "function aperturaTurnoDryRun" => "modelo dry-run apertura",
        "function abrirTurnoRealPos" => "modelo apertura real",
        "function cierreTurnoDryRun" => "modelo dry-run cierre",
        "function cerrarTurnoRealPos" => "modelo cierre real",
        "diferencia" => "calculo/registros de diferencias",
    ),
);

$bloqueos = array();
$detalle = array();

foreach ($archivos as $tipo => $archivo) {
    $contenido = is_file($archivo) ? file_get_contents($archivo) : "";
    $detalle[$tipo] = array("archivo" => normalizarRuta($archivo, $root), "existe" => is_file($archivo), "checks" => array());

    if (!is_file($archivo)) {
        $bloqueos[] = "No existe archivo " . $tipo . ": " . normalizarRuta($archivo, $root);
        continue;
    }

    foreach ($checks[$tipo] as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $detalle[$tipo]["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = $tipo . ": falta " . $descripcion . " [" . $token . "]";
        }
    }
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_turnos_ui_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_abre_turno" => true,
        "no_cierra_turno" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function normalizarRuta($archivo, $root)
{
    return str_replace("\\", "/", str_replace($root . DIRECTORY_SEPARATOR, "", $archivo));
}
