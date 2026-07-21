<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: ejecutar el semaforo real de fase 1 desde el modelo de Listas de precios.
 * Impacto: valida readiness operativo contra BD local sin pasar por navegador.
 * Contrato: read-only; no crea listas, no ejecuta DDL, no modifica ventas, CRM ni ecommerce.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ListasPreciosErp.php";

$modelo = new ListasPreciosErp();
$respuesta = $modelo->fase1ReadinessReadOnly();
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "read-only",
    "resultado" => !empty($depurar["puede_piloto_pos"]) ? "PASS_FASE1_MODELO_LISTO_PILOTO_POS" : "PENDIENTE_FASE1_MODELO",
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
    "estado" => isset($depurar["estado"]) ? $depurar["estado"] : "",
    "puede_piloto_pos" => !empty($depurar["puede_piloto_pos"]),
    "puede_ecommerce" => !empty($depurar["puede_ecommerce"]),
    "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "recomendaciones" => isset($depurar["recomendaciones"]) ? $depurar["recomendaciones"] : array(),
    "pendientes_fase_2" => isset($depurar["pendientes_fase_2"]) ? $depurar["pendientes_fase_2"] : array(),
    "kpis" => isset($depurar["kpis"]) ? $depurar["kpis"] : array(),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($respuesta["error"]) ? 0 : 1);
