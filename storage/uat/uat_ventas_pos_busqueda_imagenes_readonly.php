<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-18.
 * Proposito: revisar que la busqueda POS no duplique SKUs por imagenes genericas de producto.
 * Impacto: solo consulta el modelo de Ventas/POS; no escribe BD ni toca inventario/caja.
 * Contrato: read-only. Parametros opcionales --q=texto --id_almacen=ID --limite=N.
 */

$q = "spf";
$idAlmacen = 5;
$limite = 20;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--q=") === 0) {
        $q = trim(substr($arg, 4), "\"' ");
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(substr($arg, 13));
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = intval(substr($arg, 9));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$modelo = new VentasErp();
$respuesta = $modelo->buscarSkusPos(array(
    "q" => $q,
    "id_almacen" => $idAlmacen,
    "limite" => $limite
));

$filas = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$conteo = array();
$duplicados = array();
foreach ($filas as $fila) {
    $idSku = isset($fila["id_sku"]) ? (string) $fila["id_sku"] : "";
    if (!isset($conteo[$idSku])) {
        $conteo[$idSku] = 0;
    }
    $conteo[$idSku]++;
}
foreach ($conteo as $idSku => $veces) {
    if ($idSku !== "" && $veces > 1) {
        $duplicados[$idSku] = $veces;
    }
}

echo json_encode(array(
    "ok" => !$respuesta["error"] && count($duplicados) === 0,
    "error_modelo" => $respuesta["error"],
    "mensaje" => $respuesta["mensaje"],
    "q" => $q,
    "id_almacen" => $idAlmacen,
    "total_filas" => count($filas),
    "duplicados_por_id_sku" => $duplicados,
    "muestra" => array_map(function ($fila) {
        return array(
            "id_sku" => isset($fila["id_sku"]) ? $fila["id_sku"] : null,
            "sku" => isset($fila["sku"]) ? $fila["sku"] : "",
            "producto" => isset($fila["producto"]) ? $fila["producto"] : "",
            "url_imagen" => isset($fila["url_imagen"]) ? $fila["url_imagen"] : ""
        );
    }, $filas)
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
