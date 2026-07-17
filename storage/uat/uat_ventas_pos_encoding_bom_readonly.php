<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: detectar BOM UTF-8 o caracteres invisibles iniciales en archivos POS que puedan romper respuestas JSON.
 * Impacto: previene errores de navegador como "Unexpected token" antes de pruebas reales.
 * Contrato: read-only; no modifica archivos, no consulta BD y no ejecuta endpoints.
 */

$raiz = realpath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
$archivos = array(
    "controlador_ventas" => "app/controladores/Ventas.php",
    "modelo_ventas_erp" => "app/modelos/VentasErp.php",
    "modelo_venta" => "app/modelos/Venta.php",
    "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
    "vista_checador" => "app/vistas/paginas/apps/erp/ventas/checador_precios.php",
    "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js",
    "js_checador" => "public/assets/js/custom/apps/erp/ventas/checador_precios.js",
    "suite_pase_real" => "storage/uat/uat_ventas_pos_pase_prueba_real_suite_readonly.php",
    "ciclo_real" => "storage/uat/uat_ventas_pos_atencion_multiusuario_ciclo_apply_authorized.php",
    "ciclo_evidencia" => "storage/uat/uat_ventas_pos_ciclo_evidencia_readonly.php"
);

$bloqueos = array();
$resultados = array();

foreach ($archivos as $clave => $relativo) {
    $ruta = $raiz . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
    $resultado = array(
        "clave" => $clave,
        "archivo" => $relativo,
        "existe" => is_file($ruta),
        "legible" => false,
        "bom_utf8" => false,
        "primeros_bytes_hex" => null,
        "ok" => false,
        "mensaje" => ""
    );

    if (!$resultado["existe"]) {
        $resultado["mensaje"] = "Falta archivo";
        $bloqueos[] = "Falta archivo: " . $relativo;
        $resultados[] = $resultado;
        continue;
    }

    $resultado["legible"] = is_readable($ruta);
    if (!$resultado["legible"]) {
        $resultado["mensaje"] = "Archivo no legible";
        $bloqueos[] = "Archivo no legible: " . $relativo;
        $resultados[] = $resultado;
        continue;
    }

    $handle = fopen($ruta, "rb");
    $bytes = $handle ? fread($handle, 4) : "";
    if ($handle) {
        fclose($handle);
    }

    $resultado["primeros_bytes_hex"] = strtoupper(bin2hex($bytes));
    $resultado["bom_utf8"] = substr($bytes, 0, 3) === "\xEF\xBB\xBF";
    $resultado["ok"] = !$resultado["bom_utf8"];
    $resultado["mensaje"] = $resultado["ok"] ? "OK" : "Archivo inicia con BOM UTF-8";

    if ($resultado["bom_utf8"]) {
        $bloqueos[] = "Archivo con BOM UTF-8: " . $relativo;
    }

    $resultados[] = $resultado;
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_encoding_bom_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "resumen" => array(
        "archivos_revisados" => count($resultados),
        "bloqueos" => $bloqueos,
        "encoding_bom_ok" => empty($bloqueos)
    ),
    "archivos" => $resultados,
    "contrato" => array(
        "read_only" => true,
        "no_consulta_bd" => true,
        "no_ejecuta_endpoints" => true,
        "no_escribe_archivos" => true,
        "no_escribe_bd" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);
