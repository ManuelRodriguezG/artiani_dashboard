<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar auditoria visible de Listas de precios sin escribir.
 * Impacto: asegura filtros, eventos enriquecidos y contrato read-only para bitacora comercial.
 * Contrato: read-only; no guarda eventos, no modifica listas ni ventas.
 */

$idLista = isset($argv[1]) ? intval($argv[1]) : 2;
$raiz = dirname(__DIR__, 2);
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$modeloArchivo = $raiz . DIRECTORY_SEPARATOR . "app/modelos/ListasPreciosErp.php";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ListasPreciosErp.php";

class LpAuditoriaOperativaReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$modelo = new ListasPreciosErp();
$db = (new LpAuditoriaOperativaReadonlyDb())->db();
$idDetalle = $db ? detalleAuditoria($db, $idLista) : 0;
$auditoria = $modelo->auditoriaReadOnly(array("id_lista_precio" => $idLista, "limite" => 10));
$auditoriaDetalle = $idDetalle > 0 ? $modelo->auditoriaReadOnly(array("id_lista_precio" => $idLista, "id_lista_precio_detalle" => $idDetalle, "limite" => 10)) : null;
$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";
$contenidoModelo = is_readable($modeloArchivo) ? file_get_contents($modeloArchivo) : "";
$eventos = $auditoria["depurar"]["eventos"] ?? array();
$primerEvento = !empty($eventos) ? $eventos[0] : array();

$checks = array(
    checkAuditoria("auditoria_responde", empty($auditoria["error"]), "Auditoria responde sin error tecnico"),
    checkAuditoria("contrato_eventos", isset($auditoria["depurar"]["schema_pendiente"]) && isset($auditoria["depurar"]["eventos"]), "Auditoria devuelve contrato estable"),
    checkAuditoria("backend_enriquece", strpos($contenidoModelo, "function enriquecerEventosAuditoria") !== false, "Backend enriquece eventos para UI"),
    checkAuditoria("backend_oculta_json_crudo", empty($primerEvento) || (!array_key_exists("datos_antes", $primerEvento) && !array_key_exists("datos_despues", $primerEvento)), "Backend no expone JSON crudo en la UI operativa"),
    checkAuditoria("ui_filtros", strpos($contenidoVista, "lp_auditoria_tipo") !== false, "Vista incluye filtros de auditoria"),
    checkAuditoria("ui_badges", strpos($contenidoJs, "function badgeAuditoria") !== false, "JS renderiza badges por tipo de evento"),
    checkAuditoria("ui_historial_sku", strpos($contenidoJs, "function cargarAuditoriaSku") !== false && strpos($contenidoJs, "Historial SKU") !== false, "JS permite abrir historial de precio por SKU"),
    checkAuditoria("backend_filtro_detalle", $idDetalle <= 0 || ($auditoriaDetalle && empty($auditoriaDetalle["error"]) && intval($auditoriaDetalle["depurar"]["filtros"]["id_lista_precio_detalle"] ?? 0) === $idDetalle), "Backend filtra auditoria por detalle de precio")
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
    "resultado" => empty($bloqueos) ? "PASS_AUDITORIA_OPERATIVA" : "FAIL_AUDITORIA_OPERATIVA",
    "id_lista_precio" => $idLista,
    "id_lista_precio_detalle_uat" => $idDetalle,
    "total_eventos" => count($eventos),
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_guarda_eventos" => true,
        "no_modifica_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkAuditoria($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function detalleAuditoria($db, $idLista) {
    $stmt = $db->prepare("SELECT id_lista_precio_detalle FROM erp_listas_precios_detalle WHERE id_lista_precio=:lista AND estatus<>'cancelado' ORDER BY id_lista_precio_detalle DESC LIMIT 1");
    $stmt->execute(array(":lista" => intval($idLista)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? intval($fila["id_lista_precio_detalle"]) : 0;
}
