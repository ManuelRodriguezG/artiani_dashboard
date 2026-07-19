<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar guardrails de revision/activacion de listas de precios.
 * Impacto: protege activacion de listas con alcance riesgoso, vigencia invalida o pendientes locales.
 * Contrato: read-only; no guarda listas, no ejecuta DDL y no modifica ventas.
 */

$idLista = isset($argv[1]) ? intval($argv[1]) : 2;
$raiz = dirname(__DIR__, 2);
$modeloArchivo = $raiz . DIRECTORY_SEPARATOR . "app/modelos/ListasPreciosErp.php";
$jsArchivo = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

$modelo = new ListasPreciosErp();
$revision = $modelo->revisionListaReadOnly($idLista);
$modeloContenido = is_readable($modeloArchivo) ? file_get_contents($modeloArchivo) : "";
$jsContenido = is_readable($jsArchivo) ? file_get_contents($jsArchivo) : "";

$checks = array(
    checkRevision("revision_responde", empty($revision["error"]), "Revision real responde sin error tecnico"),
    checkRevision("revision_con_contrato", isset($revision["depurar"]["puede_activar"]) && isset($revision["depurar"]["margen"]), "Revision devuelve contrato de activacion y margen"),
    checkRevision("guardrail_ecommerce", strpos($modeloContenido, "Lista ecommerce requiere asignacion explicita") !== false, "Backend bloquea ecommerce sin alcance explicito"),
    checkRevision("guardrail_vigencia", strpos($modeloContenido, "La vigencia de la lista ya termino") !== false, "Backend bloquea vigencia vencida"),
    checkRevision("guardrail_general_global", strpos($modeloContenido, "Lista general global") !== false, "Backend avisa alcance general global"),
    checkRevision("guardrail_local_ui", strpos($jsContenido, "function revisionLocalPantalla") !== false, "UI considera pendientes locales antes de activar")
);

$bloqueos = array();
foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["id"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_REVISION_ACTIVACION" : "FAIL_REVISION_ACTIVACION",
    "id_lista_precio" => $idLista,
    "checks" => $checks,
    "revision" => array(
        "mensaje" => $revision["mensaje"] ?? "",
        "puede_activar" => $revision["depurar"]["puede_activar"] ?? null,
        "bloqueos" => $revision["depurar"]["bloqueos"] ?? array(),
        "avisos" => $revision["depurar"]["avisos"] ?? array(),
        "margen" => $revision["depurar"]["margen"] ?? null
    ),
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_guarda_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkRevision($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}
