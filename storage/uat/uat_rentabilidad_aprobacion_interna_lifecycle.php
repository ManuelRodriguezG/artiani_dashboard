<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$args = isset($argv) ? $argv : array();
$executeCreate = in_array("--execute-create", $args, true);
$executeResolve = in_array("--execute-resolve", $args, true);
$respaldo = "";
$sku = "TP-40372";
$accion = "aprobar";
$idAprobacion = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11));
    } elseif (strpos($arg, "--sku=") === 0) {
        $sku = trim(substr($arg, 6));
    } elseif (strpos($arg, "--accion=") === 0) {
        $accion = trim(substr($arg, 9));
    } elseif (strpos($arg, "--id=") === 0) {
        $idAprobacion = intval(substr($arg, 5));
    }
}

$filtros = array("q" => $sku, "canal" => "menudeo", "limite" => 20);
$preflight = $modelo->preflightAprobacionesInternas($filtros);
$listadoAntes = $modelo->listarAprobacionesInternas($filtros);

$crear = null;
if ($executeCreate) {
    $crear = $modelo->guardarAprobacionInterna(array(
        "sku" => $sku,
        "canal" => "menudeo",
        "confirmar_autorizacion" => "AUTORIZO CREAR APROBACION INTERNA",
        "respaldo_externo_ref" => $respaldo,
        "comentario" => "UAT ciclo de vida aprobacion interna"
    ), 0);
    if (empty($crear["error"]) && isset($crear["depurar"]["id_aprobacion"])) {
        $idAprobacion = intval($crear["depurar"]["id_aprobacion"]);
    }
}

$resolver = null;
if ($executeResolve) {
    $resolver = $modelo->resolverAprobacionInterna(array(
        "id_aprobacion" => $idAprobacion,
        "accion" => $accion,
        "confirmar_autorizacion" => "AUTORIZO RESOLVER APROBACION INTERNA",
        "respaldo_externo_ref" => $respaldo,
        "comentario" => "UAT resolver aprobacion interna"
    ), 0);
}

$listadoDespues = $modelo->listarAprobacionesInternas($filtros);
$resPreflight = isset($preflight["depurar"]["resumen"]) ? $preflight["depurar"]["resumen"] : array();
$resListadoAntes = isset($listadoAntes["depurar"]["resumen"]) ? $listadoAntes["depurar"]["resumen"] : array();
$resListadoDespues = isset($listadoDespues["depurar"]["resumen"]) ? $listadoDespues["depurar"]["resumen"] : array();

$ok = empty($preflight["error"]) && empty($listadoAntes["error"]) && empty($listadoDespues["error"]);
if (!$executeCreate && !$executeResolve) {
    $ok = $ok && intval(isset($resListadoAntes["schema_disponible"]) ? $resListadoAntes["schema_disponible"] : 0) === 0;
}
if ($executeCreate && ($crear === null || !empty($crear["error"]))) {
    $ok = false;
}
if ($executeResolve && ($resolver === null || !empty($resolver["error"]))) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => $executeCreate || $executeResolve ? "execute" : "preflight",
    "sku" => $sku,
    "preflight" => array(
        "error" => $preflight["error"],
        "resumen" => $resPreflight
    ),
    "listado_antes" => $resListadoAntes,
    "crear" => $crear === null ? null : array(
        "error" => $crear["error"],
        "mensaje" => $crear["mensaje"],
        "depurar" => isset($crear["depurar"]) ? $crear["depurar"] : null
    ),
    "resolver" => $resolver === null ? null : array(
        "error" => $resolver["error"],
        "mensaje" => $resolver["mensaje"],
        "depurar" => isset($resolver["depurar"]) ? $resolver["depurar"] : null
    ),
    "listado_despues" => $resListadoDespues,
    "uso_execute" => array(
        "crear" => "Agregar --execute-create --respaldo=RUTA_O_REFERENCIA despues de aplicar esquema.",
        "resolver" => "Agregar --execute-resolve --id=ID --accion=aprobar|rechazar|cancelar --respaldo=RUTA_O_REFERENCIA."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

