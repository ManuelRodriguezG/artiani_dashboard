<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar que la salida a operacion controlada POS este documentada.
 * Impacto: protege checklist operativo sin consultar ni escribir BD.
 * Contrato: read-only; solo lee docs locales y no ejecuta acciones POS.
 */

date_default_timezone_set("America/Mexico_City");

$root = dirname(__DIR__, 2);
$ruta = $root . DIRECTORY_SEPARATOR . "docs" . DIRECTORY_SEPARATOR . "erp_ventas_pos_salida_operacion_controlada.md";
$contenido = is_file($ruta) ? file_get_contents($ruta) : "";
$tokens = array(
    "Operacion controlada significa" => "definicion operativa",
    "scripts_total=26" => "semaforo consolidado vigente",
    "pendientes_total=5" => "pendientes vigentes",
    "acciones_total=7" => "plan de acciones vigente",
    "pasos_total=6" => "paquete autorizacion vigente",
    "PINV-20260717-000001" => "pendiente inventario vigente",
    "GASTO-UAT-001" => "evidencia caja vigente",
    "No permitir en primer uso" => "limites del primer uso",
    "Postcheck posterior" => "postcheck documentado",
    "Criterio para ampliar piloto" => "criterio de ampliacion",
    "Criterio para detener" => "criterio de detencion",
    "venta confirmada sin kardex" => "riesgo critico inventario",
    "operador visual incorrecto" => "riesgo critico identidad",
);

$bloqueos = array();
$checks = array();
if (!is_file($ruta)) {
    $bloqueos[] = "Falta docs/erp_ventas_pos_salida_operacion_controlada.md";
}
foreach ($tokens as $token => $descripcion) {
    $ok = $contenido !== "" && strpos($contenido, $token) !== false;
    $checks[$token] = array("descripcion" => $descripcion, "ok" => $ok);
    if (!$ok) {
        $bloqueos[] = "No se encontro " . $descripcion . " [" . $token . "]";
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_salida_operacion_doc_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "documento" => "docs/erp_ventas_pos_salida_operacion_controlada.md",
    "bloqueos" => $bloqueos,
    "checks" => $checks,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_ejecuta_pos" => true,
    ),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);
