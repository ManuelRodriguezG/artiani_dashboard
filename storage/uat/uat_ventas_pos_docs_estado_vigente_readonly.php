<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar que documentos POS reflejen el estado vigente del piloto.
 * Impacto: evita operar con instrucciones antiguas cuando ya hay atencion convertida, pendiente inventario y salida operativa lista.
 * Contrato: read-only; no consulta BD, no escribe BD y no ejecuta acciones POS.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$docs = array(
    "pase_prueba_real" => array(
        "ruta" => "docs/erp_ventas_pos_pase_prueba_real_checklist.md",
        "tokens" => array(
            "Resultado vigente 2026-07-19" => "corte vigente productivo",
            "PINV-20260717-000001" => "pendiente actual",
            "No repetir esta atencion" => "atencion convertida no repetible",
            "ciclo_real_ya_completado=true" => "ciclo real completado",
            "pendientes_pos_abiertos=1" => "pendiente inventario cuantificado",
        ),
    ),
    "piloto_operativo" => array(
        "ruta" => "docs/erp_ventas_pos_piloto_operativo_checklist.md",
        "tokens" => array(
            "listo_para_piloto_controlado_con_condiciones" => "salida operativa consolidada",
            "PINV-20260717-000001" => "pendiente actual",
            "GASTO-UAT-001" => "evidencia caja actual",
            "ticket formal, garantia snapshot y trazabilidad" => "ticket/trazabilidad en checklist",
        ),
    ),
    "runbook_turno_1" => array(
        "ruta" => "docs/erp_ventas_pos_piloto_turno_1_runbook.md",
        "tokens" => array(
            "Primer piloto POS controlado" => "etiqueta piloto",
            "PINV-20260717-000001" => "pendiente actual visible",
            "uat_ventas_pos_salida_operativa_readiness_readonly.php" => "salida operativa documentada",
        ),
    ),
    "handoff" => array(
        "ruta" => "docs/erp_ventas_pos_handoff_contexto.md",
        "tokens" => array(
            "salida_operativa" => "salida operativa en handoff",
            "ticket_trazabilidad" => "ticket/trazabilidad en handoff",
            "atajos_ui" => "atajos en handoff",
            "turnos_ui" => "turnos en handoff",
        ),
    ),
    "estado_cierre_modulo" => array(
        "ruta" => "docs/erp_ventas_pos_estado_cierre_modulo.md",
        "tokens" => array(
            "listo_para_piloto_controlado_con_condiciones" => "decision vigente",
            "C:\\xampp\\htdocs\\panel_de_control" => "proyecto canonico",
            "http://panel.com.local/" => "host canonico",
            "PINV-20260717-000001" => "pendiente inventario vigente",
            "GASTO-UAT-001" => "evidencia caja vigente",
            "Scanner POS" => "scanner POS documentado",
            "Impresion directa de ticket" => "impresion POS documentada",
            "uat_ventas_pos_piloto_preflight_compacto_readonly.php" => "preflight compacto documentado",
            "uat_ventas_pos_piloto_postcheck_compacto_readonly.php" => "postcheck compacto documentado",
        ),
    ),
    "guia_primer_turno" => array(
        "ruta" => "docs/erp_ventas_pos_primer_turno_piloto_guia_operador.md",
        "tokens" => array(
            "ABRIR TURNO" => "apertura real",
            "CERRAR TURNO" => "cierre real",
            "puede_iniciar_piloto_controlado=true" => "preflight esperado",
            "uat_ventas_pos_piloto_postcheck_compacto_readonly.php" => "postcheck documentado",
            "La caja puede cerrar aunque no cuadre en cero" => "diferencias permitidas con revision",
            "Inventario/Existencias" => "pendientes inventario visibles",
        ),
    ),
);

$bloqueos = array();
$detalle = array();
foreach ($docs as $clave => $doc) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $doc["ruta"]);
    $contenido = is_file($ruta) ? file_get_contents($ruta) : "";
    $detalle[$clave] = array("ruta" => $doc["ruta"], "existe" => is_file($ruta), "checks" => array());
    if (!is_file($ruta)) {
        $bloqueos[] = "Falta documento " . $doc["ruta"];
        continue;
    }
    foreach ($doc["tokens"] as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $detalle[$clave]["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = "Documento " . $doc["ruta"] . " no menciona " . $descripcion . " [" . $token . "]";
        }
    }
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_docs_estado_vigente_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);
