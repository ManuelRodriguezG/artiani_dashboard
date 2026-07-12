<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: validar resolucion read-only de listas de precios para POS/CRM.
 * Impacto: prueba contrato backend de precios sin crear listas, clientes, ventas ni snapshots.
 * Contrato: no escribe BD; valida prioridad cliente CRM sobre canal/sucursal y auditoria de esquema.
 */

$args = isset($argv) ? $argv : array();
$idSku = 1760;
$idAlmacen = 5;
$idClienteCrm = 1;
$canal = "pos";

foreach ($args as $arg) {
    if (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--canal=") === 0) {
        $canal = trim(substr($arg, 8), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

$ventas = new VentasErp();
$esquema = new VentasErpEsquema();
$fallos = array();

$auditoriaCrm = $esquema->auditarListasPreciosCrm();
$planCrm = $esquema->planActualizarListasPreciosCrm(false);

$sinCliente = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => $canal,
    "id_cliente" => 0,
    "items" => json_encode(array(array("id_sku" => $idSku, "cantidad" => 1)))
));

$conCliente = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => $canal,
    "id_cliente" => $idClienteCrm,
    "items" => json_encode(array(array("id_sku" => $idSku, "cantidad" => 1)))
));

$partidaSinCliente = extraerPrimeraPartida($sinCliente);
$partidaConCliente = extraerPrimeraPartida($conCliente);

if (!empty($sinCliente["error"])) {
    $fallos[] = "Dry-run sin cliente devolvio error: " . valor($sinCliente, "mensaje", "");
}
if (!empty($conCliente["error"])) {
    $fallos[] = "Dry-run con cliente CRM devolvio error: " . valor($conCliente, "mensaje", "");
}
if (valor($partidaSinCliente, "regla_precio_origen", "") !== "lista_canal_sucursal") {
    $fallos[] = "Sin cliente se esperaba lista_canal_sucursal para semilla UAT; obtenido " . valor($partidaSinCliente, "regla_precio_origen", "sin_partida");
}
if (valor($partidaConCliente, "regla_precio_origen", "") !== "lista_cliente") {
    $fallos[] = "Con cliente CRM se esperaba lista_cliente; obtenido " . valor($partidaConCliente, "regla_precio_origen", "sin_partida");
}
if (intval(valor($partidaConCliente, "id_lista_precio", 0)) <= 0) {
    $fallos[] = "Con cliente CRM se esperaba id_lista_precio informado.";
}
if (trim((string) valor($partidaConCliente, "lista_precio_snapshot", "")) === "") {
    $fallos[] = "Con cliente CRM se esperaba lista_precio_snapshot informado.";
}

responder(empty($fallos), $fallos, array(
    "entrada" => array(
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "id_cliente_crm" => $idClienteCrm,
        "canal" => $canal
    ),
    "auditoria_crm_listas" => $auditoriaCrm,
    "plan_crm_listas_sin_ejecutar" => $planCrm,
    "sin_cliente" => $sinCliente,
    "con_cliente_crm" => $conCliente
));

function extraerPrimeraPartida($respuesta) {
    if (!is_array($respuesta) || empty($respuesta["depurar"]["partidas"]) || !is_array($respuesta["depurar"]["partidas"])) {
        return array();
    }
    return $respuesta["depurar"]["partidas"][0];
}

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}

function responder($ok, $fallos, $resultados) {
    echo json_encode(array(
        "ok" => $ok,
        "modo" => "ventas_listas_precios_resolutor_readonly",
        "read_only" => true,
        "fallos" => $fallos,
        "resultados" => $resultados,
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_crea_listas" => true,
            "no_crea_clientes" => true,
            "no_crea_ventas" => true,
            "backend_resuelve_precio" => true
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($ok ? 0 : 1);
}
