<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$ejecutar = in_array("--execute", isset($argv) ? $argv : array(), true);
$respaldo = "";
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = substr($arg, 11);
    }
}

$datos = array(
    "q" => "TP-40372",
    "canal" => "menudeo",
    "riesgo" => "",
    "descuento_pct" => "0",
    "gasto_pct" => "18",
    "comision_pct" => "0",
    "margen_objetivo_pct" => "25"
);

if ($ejecutar) {
    $semilla = $modelo->sembrarEscenariosBase(0);
    $datos["respaldo_externo_ref"] = $respaldo;
    $datos["confirmar_autorizacion"] = "AUTORIZO GUARDAR SNAPSHOT";
    $snapshot = $modelo->guardarSnapshot($datos, 0);
} else {
    $semilla = $modelo->auditarEscenariosComerciales();
    $snapshot = $modelo->analizarSkus($datos);
}

echo json_encode(array(
    "ok" => !$semilla["error"] && !$snapshot["error"],
    "modo" => $ejecutar ? "execute" : "preflight",
    "semilla_o_auditoria" => array(
        "error" => $semilla["error"],
        "mensaje" => $semilla["mensaje"],
        "depurar" => isset($semilla["depurar"]) ? $semilla["depurar"] : null
    ),
    "snapshot_o_analisis" => array(
        "error" => $snapshot["error"],
        "mensaje" => $snapshot["mensaje"],
        "depurar" => isset($snapshot["depurar"]) ? $snapshot["depurar"] : null
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
