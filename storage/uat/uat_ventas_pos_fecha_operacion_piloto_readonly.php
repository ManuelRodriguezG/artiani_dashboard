<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-29.
 * Proposito: validar reglas de fecha operativa temporal POS sin cobrar ni escribir BD.
 * Impacto: confirma que fecha manual solo aplica en modo piloto sin afectar inventario.
 * Contrato: read-only; usa reflexion sobre el modelo para probar reglas internas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$modelo = new VentasErp();
$ref = new ReflectionClass($modelo);
$metodo = $ref->getMethod("resolverFechaOperacionVentaPos");
$metodo->setAccessible(true);

$ayer = date("Y-m-d\TH:i", strtotime("-1 day"));
$futuro = date("Y-m-d\TH:i", strtotime("+1 day"));
$antigua = date("Y-m-d\TH:i", strtotime("-181 days"));

$modoNormal = array("afectar_inventario" => 1, "modo_operacion_inventario" => "normal");
$modoPiloto = array("afectar_inventario" => 0, "modo_operacion_inventario" => "piloto_sin_inventario");

$casos = array(
    "vacia_sistema" => $metodo->invoke($modelo, "", $modoNormal),
    "normal_bloquea_manual" => $metodo->invoke($modelo, $ayer, $modoNormal),
    "piloto_permite_ayer" => $metodo->invoke($modelo, $ayer, $modoPiloto),
    "piloto_bloquea_futuro" => $metodo->invoke($modelo, $futuro, $modoPiloto),
    "piloto_bloquea_mayor_180_dias" => $metodo->invoke($modelo, $antigua, $modoPiloto)
);

echo json_encode(array(
    "ok" => empty($casos["vacia_sistema"]["error"])
        && !empty($casos["normal_bloquea_manual"]["error"])
        && empty($casos["piloto_permite_ayer"]["error"])
        && !empty($casos["piloto_bloquea_futuro"]["error"])
        && !empty($casos["piloto_bloquea_mayor_180_dias"]["error"]),
    "casos" => $casos
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
