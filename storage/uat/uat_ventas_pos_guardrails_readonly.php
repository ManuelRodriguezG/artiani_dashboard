<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: comprobar que scripts con escritura de Ventas/POS siguen bloqueados sin autorizacion correcta.
 * Impacto: valida guardrails previos a DDL base sin escribir BD.
 * Contrato: solo ejecuta escenarios bloqueados; no pasa tokens validos de escritura real.
 */

$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$tests = array(
    array(
        "id" => "GUARD-DDL-OLD-TOKEN",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL --respaldo=" . escapeshellarg($respaldo),
        "espera_ok" => false,
        "espera_modo" => "bloqueado"
    ),
    array(
        "id" => "GUARD-DDL-NO-BACKUP",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_BASE",
        "espera_ok" => false,
        "espera_modo" => "bloqueado"
    ),
    array(
        "id" => "GUARD-SEED-NO-AUTH",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_seed_apply_authorized.php --respaldo=" . escapeshellarg($respaldo) . " --id_usuario=1",
        "espera_ok" => false,
        "espera_modo" => "bloqueado"
    ),
    array(
        "id" => "GUARD-TURNO-NO-AUTH",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_turno_apertura_apply_authorized.php --respaldo=" . escapeshellarg($respaldo) . " --id_usuario=1 --monto_inicial=500",
        "espera_ok" => false,
        "espera_modo" => "bloqueado"
    ),
    array(
        "id" => "GUARD-VENTA-NO-AUTH",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_venta_apply_authorized.php --respaldo=" . escapeshellarg($respaldo) . " --id_usuario=1",
        "espera_ok" => false,
        "espera_modo" => "guardrail"
    )
);

$resultados = array();
$fallas = array();
foreach ($tests as $test) {
    $salida = array();
    $codigo = 0;
    exec($test["comando"], $salida, $codigo);
    $json = json_decode(implode("\n", $salida), true);
    $jsonOk = is_array($json);
    $okReal = $jsonOk && !empty($json["ok"]);
    $modoReal = $jsonOk && isset($json["modo"]) ? $json["modo"] : "";
    $pasa = $jsonOk && $okReal === $test["espera_ok"] && $modoReal === $test["espera_modo"];
    if (!$pasa) {
        $fallas[] = $test["id"];
    }
    $resultados[] = array(
        "id" => $test["id"],
        "pasa" => $pasa,
        "exit_code" => $codigo,
        "ok_real" => $okReal,
        "modo_real" => $modoReal,
        "mensaje" => $jsonOk && isset($json["mensaje"]) ? $json["mensaje"] : "Salida no JSON"
    );
}

echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "read-only",
    "resultados" => $resultados,
    "fallas" => $fallas,
    "siguiente_paso" => empty($fallas)
        ? "Guardrails correctos; ninguna escritura se ejecuta sin autorizacion valida."
        : "Corregir guardrails antes de autorizar DDL base."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
