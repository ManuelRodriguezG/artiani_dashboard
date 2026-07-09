<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: validar que el cierre real POS UI bloquea sin confirmacion exacta.
 * Impacto: cubre guardrail del endpoint/modelo sin cerrar turnos aunque exista uno abierto.
 * Contrato: no debe escribir BD; usa confirmacion incorrecta a proposito.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->cerrarTurnoRealPos(array(
    "id_usuario" => 1,
    "id_almacen" => 5,
    "id_caja" => 2,
    "id_turno_caja" => 13,
    "monto_contado" => 795,
    "confirmacion" => "NO CERRAR",
    "observaciones" => "UAT guard cierre real UI sin confirmacion"
));

$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();

echo json_encode(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "warning" && in_array("Escribe CERRAR TURNO para confirmar", $bloqueos, true),
    "modo" => "cierre_real_ui_guard_readonly",
    "respuesta" => $respuesta,
    "contrato" => array(
        "confirmacion_incorrecta" => true,
        "no_debe_cerrar_turno" => true,
        "no_debe_mover_caja" => true,
        "no_debe_mover_inventario" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
