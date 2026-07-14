<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: arnes autorizado para futura prueba RES-T011 politicas tienda/SKU.
 * Impacto: define token, respaldo y alcance para reglas min/max/reorden por tienda/SKU.
 * Contrato actual: bloqueado por defecto; con DDL aplicado y autorizacion guarda politica sin mover inventario.
 */

$opciones = getopt("", array("autorizar::", "confirmacion::", "respaldo::", "almacen::", "sku::", "min::", "max::", "reorden::", "sugerida::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$confirmacion = isset($opciones["confirmacion"]) ? trim((string) $opciones["confirmacion"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$idAlmacen = isset($opciones["almacen"]) ? intval($opciones["almacen"]) : 4;
$idSku = isset($opciones["sku"]) ? intval($opciones["sku"]) : 1;
$minimo = isset($opciones["min"]) ? $opciones["min"] : 1;
$maximo = isset($opciones["max"]) ? $opciones["max"] : 10;
$reorden = isset($opciones["reorden"]) ? $opciones["reorden"] : 2;
$sugerida = isset($opciones["sugerida"]) ? $opciones["sugerida"] : 5;
$token = "ALMACEN_RESURTIDO_POLITICA_UAT";
$frase = "AUTORIZO UAT POLITICA RESURTIDO usando respaldo RUTA_O_REFERENCIA";
$validacion = validarRespaldoResurtidoAccion($respaldo);

if ($autorizar !== $token || $confirmacion !== $frase || !$validacion["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se guardo politica tienda/SKU. Falta token, confirmacion textual o respaldo valido.",
        "requerido" => array(
            "autorizar" => $token,
            "confirmacion" => $frase,
            "respaldo" => "RUTA_O_REFERENCIA_RESPALDO",
            "almacen" => "ID_ALMACEN_TIENDA",
            "sku" => "ID_SKU_ERP"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => alcancePolitica()
    ), 1);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";

$modelo = new Almacenes();
$respuesta = $modelo->guardar_politica_resurtido_pendiente(array(
    "id_almacen" => $idAlmacen,
    "id_sku_erp" => $idSku,
    "stock_minimo" => $minimo,
    "stock_maximo" => $maximo,
    "punto_reorden" => $reorden,
    "cantidad_sugerida" => $sugerida
), 0);

$okBackend = empty($respuesta["error"]) && intval(isset($respuesta["depurar"]["guardado"]) ? $respuesta["depurar"]["guardado"] : 0) === 1;

responder(array(
    "ok" => $okBackend,
    "modo" => $okBackend ? "almacen_resurtido_politica_uat" : "backend_pendiente_o_bloqueado",
    "mensaje" => $okBackend ? "Politica guardada por backend." : "Arnes autorizado ejecuto contrato backend; no se guardo politica.",
    "id_almacen" => $idAlmacen,
    "id_sku_erp" => $idSku,
    "respuesta_backend" => $respuesta,
    "validacion_respaldo" => $validacion,
    "alcance" => alcancePolitica(),
    "guardrails" => array(
        "guardo_politica" => $okBackend,
        "no_genero_alertas" => true,
        "no_ejecuto_movimientos" => true,
        "no_toco_pos_ecommerce" => true
    )
), $okBackend ? 0 : 1);

function validarRespaldoResurtidoAccion($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "referencia_presente" => $okReferencia,
        "referencia" => $respaldo,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function alcancePolitica() {
    return array(
        "futuro_crea_politica_tienda_sku" => true,
        "futuro_actualiza_politica_tienda_sku" => true,
        "futuro_genera_alertas" => false,
        "futuro_mueve_inventario" => false,
        "toca_pos" => false,
        "toca_ecommerce" => false,
        "implementacion_actual" => "lista_post_ddl"
    );
}

function responder($datos, $codigoSalida) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($codigoSalida);
}
