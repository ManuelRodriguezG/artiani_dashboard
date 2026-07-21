<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar textos UX de resolucion de pendientes POS en Inventario/Existencias.
 * Impacto: evita que operadores resuelvan mini inventarios sin entender formula, token, respaldo y confirmacion.
 * Contrato: read-only; no consulta BD, no escribe BD y no mueve inventario.
 */

$root = realpath(__DIR__ . "/../..");
$ruta = $root . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/inventarios/existencias_erp.js";
$contenido = is_file($ruta) ? file_get_contents($ruta) : "";
$tokens = array(
    "Previsualizar resolucion" => "titulo claro sin palabra simular",
    "No mueve inventario" => "contrato read-only visible",
    "Cantidad fisica actual" => "campo de conteo actual despues de venta",
    "despues de la venta" => "contexto post venta",
    "Formula:" => "formula visible al operador",
    "Ajuste preventa propuesto" => "ajuste antes de salida explicado",
    "Salida por venta pendiente" => "salida pendiente explicada",
    "Disponible final estimado" => "resultado final explicado",
    "INVENTARIO_POS_PENDIENTE_RESOLVER_REAL" => "token exacto visible",
    "UAT POS vigente" => "respaldo UAT visible",
    "RESOLVER PENDIENTE" => "confirmacion exacta visible",
);

$bloqueos = array();
$checks = array();
if (!is_file($ruta)) {
    $bloqueos[] = "Falta existencias_erp.js";
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
    "modo" => "inventario_pendientes_pos_ux_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "bloqueos" => $bloqueos,
    "checks" => $checks,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_mueve_inventario" => true,
    ),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);
