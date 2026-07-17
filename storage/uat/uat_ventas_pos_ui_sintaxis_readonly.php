<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: validar sintaxis de archivos UI/backend inmediatos del POS antes de prueba real.
 * Impacto: detecta errores de PHP/JS en controlador, modelo, vistas y assets principales sin tocar BD.
 * Contrato: read-only; no consulta BD, no modifica archivos y no ejecuta endpoints de negocio.
 */

$raiz = realpath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
$archivosPhp = array(
    "controlador_ventas" => "app/controladores/Ventas.php",
    "modelo_ventas_erp" => "app/modelos/VentasErp.php",
    "modelo_venta_legacy" => "app/modelos/Venta.php",
    "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
    "vista_checador" => "app/vistas/paginas/apps/erp/ventas/checador_precios.php"
);
$archivosJs = array(
    "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js",
    "js_checador" => "public/assets/js/custom/apps/erp/ventas/checador_precios.js"
);

$resultados = array();
$bloqueos = array();

foreach ($archivosPhp as $clave => $relativo) {
    $resultado = revisar($raiz, $clave, $relativo, array(PHP_BINARY, "-l"));
    $resultados[] = $resultado;
    if (!$resultado["ok"]) {
        $bloqueos[] = $resultado["mensaje"];
    }
}

foreach ($archivosJs as $clave => $relativo) {
    $resultado = revisar($raiz, $clave, $relativo, array("node", "--check"));
    $resultados[] = $resultado;
    if (!$resultado["ok"]) {
        $bloqueos[] = $resultado["mensaje"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_ui_sintaxis_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "resumen" => array(
        "archivos_revisados" => count($resultados),
        "bloqueos" => $bloqueos,
        "ui_sintaxis_ok" => empty($bloqueos)
    ),
    "archivos" => $resultados,
    "contrato" => array(
        "read_only" => true,
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_ejecuta_endpoints" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function revisar($raiz, $clave, $relativo, $comandoBase) {
    $ruta = $raiz . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
    $existe = is_file($ruta);
    $legible = $existe && is_readable($ruta);
    if (!$existe || !$legible) {
        return array(
            "clave" => $clave,
            "archivo" => $relativo,
            "existe" => $existe,
            "legible" => $legible,
            "ok" => false,
            "exit_code" => null,
            "salida" => "",
            "mensaje" => (!$existe ? "Falta archivo: " : "Archivo no legible: ") . $relativo
        );
    }

    $cmd = "";
    foreach ($comandoBase as $parte) {
        $cmd .= ($cmd === "" ? "" : " ") . escapeshellarg($parte);
    }
    $cmd .= " " . escapeshellarg($ruta);

    $lineas = array();
    $codigo = 0;
    exec($cmd, $lineas, $codigo);
    $salida = implode("\n", $lineas);

    return array(
        "clave" => $clave,
        "archivo" => $relativo,
        "existe" => true,
        "legible" => true,
        "ok" => $codigo === 0,
        "exit_code" => $codigo,
        "salida" => $salida,
        "mensaje" => $codigo === 0 ? "OK" : "Sintaxis invalida: " . $relativo
    );
}
