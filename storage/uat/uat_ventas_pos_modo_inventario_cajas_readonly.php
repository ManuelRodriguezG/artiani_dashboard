<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-18.
 * Proposito: auditar cajas POS y su modo de afectacion de inventario.
 * Impacto: solo lectura; ayuda a elegir que caja poner en modo piloto o normal.
 * Contrato: no escribe BD, no abre turnos, no cobra y no mueve inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$modelo = new VentasErp();
$respuesta = $modelo->configuracionPosReadOnly();
$cajas = array();
if (!$respuesta["error"] && isset($respuesta["depurar"]["cajas"]) && is_array($respuesta["depurar"]["cajas"])) {
    foreach ($respuesta["depurar"]["cajas"] as $caja) {
        $cajas[] = array(
            "id_caja" => isset($caja["id_caja"]) ? $caja["id_caja"] : null,
            "codigo" => isset($caja["codigo"]) ? $caja["codigo"] : "",
            "nombre" => isset($caja["nombre"]) ? $caja["nombre"] : "",
            "id_almacen" => isset($caja["id_almacen"]) ? $caja["id_almacen"] : null,
            "almacen" => isset($caja["almacen"]) ? $caja["almacen"] : "",
            "estatus" => isset($caja["estatus"]) ? $caja["estatus"] : "",
            "afectar_inventario" => isset($caja["afectar_inventario"]) ? $caja["afectar_inventario"] : "NA",
            "modo_operacion_inventario" => isset($caja["modo_operacion_inventario"]) ? $caja["modo_operacion_inventario"] : "NA",
            "generar_alertas_inventario" => isset($caja["generar_alertas_inventario"]) ? $caja["generar_alertas_inventario"] : "NA"
        );
    }
}

echo json_encode(array(
    "ok" => !$respuesta["error"],
    "mensaje" => $respuesta["mensaje"],
    "total_cajas" => count($cajas),
    "cajas" => $cajas
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
