<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar que el dry-run de precios POS no truene con fatal si MySQL no esta disponible.
 * Impacto: mantiene el contrato JSON del resolutor de listas aun cuando el servicio de BD esta caido.
 * Contrato: read-only; solo lee archivo local, no carga MVC ni conecta MySQL.
 */

$raiz = dirname(__DIR__, 2);
$archivo = $raiz . DIRECTORY_SEPARATOR . "app/modelos/VentasErp.php";
$contenido = is_readable($archivo) ? file_get_contents($archivo) : "";

$metodo = extraerFuncion($contenido, "clientePrecioDryRun");

$checks = array(
    checkGuard("archivo_legible", is_readable($archivo), "VentasErp.php legible"),
    checkGuard("metodo_localizado", $metodo !== "", "Se localiza clientePrecioDryRun"),
    checkGuard("guard_conexion", strpos($metodo, "Conexion MySQL no disponible para resolver precios POS en dry-run") !== false, "clientePrecioDryRun responde error controlado si no hay conexion"),
    checkGuard("sin_precio_confiable", strpos($metodo, "sin_conexion_no_hay_precio_confiable") !== false, "El contrato declara que sin conexion no hay precio confiable"),
    checkGuard("antes_de_consultar_sku", posicionAntes($metodo, "Conexion MySQL no disponible para resolver precios POS en dry-run", "consultarSkuVenta"), "El guard ocurre antes de consultar SKU")
);

$bloqueos = array();
foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["id"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_RESOLUTOR_CONEXION_GUARD" : "FAIL_RESOLUTOR_CONEXION_GUARD",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkGuard($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function posicionAntes($contenido, $primero, $segundo) {
    $a = strpos($contenido, $primero);
    $b = strpos($contenido, $segundo);
    return $a !== false && $b !== false && $a < $b;
}

function extraerFuncion($contenido, $nombre) {
    $inicio = strpos($contenido, "function " . $nombre);
    if ($inicio === false) {
        return "";
    }
    $siguiente = strpos($contenido, "\n    public function ", $inicio + 10);
    if ($siguiente === false) {
        $siguiente = strpos($contenido, "\n    private function ", $inicio + 10);
    }
    if ($siguiente === false) {
        return substr($contenido, $inicio);
    }
    return substr($contenido, $inicio, $siguiente - $inicio);
}
