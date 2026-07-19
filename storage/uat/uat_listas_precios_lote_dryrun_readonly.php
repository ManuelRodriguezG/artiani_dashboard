<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar prevalidacion backend de lote de precios sin guardar.
 * Impacto: protege importacion CSV y guardado masivo antes de persistir cambios.
 * Contrato: read-only; no escribe BD, no ejecuta DDL, no guarda listas ni modifica ventas.
 */

$idLista = isset($argv[1]) ? intval($argv[1]) : 2;
$idSku = isset($argv[2]) ? intval($argv[2]) : 1760;
$precioValido = isset($argv[3]) ? floatval($argv[3]) : 315;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ListasPreciosErp.php";

class LpLoteDryRunReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$modelo = new ListasPreciosErp();
$db = (new LpLoteDryRunReadonlyDb())->db();
$idDetalleExistente = $db ? detalleExistenteLote($db, $idLista, $idSku) : 0;

$payloadValido = array(
    "id_lista_precio" => $idLista,
    "precios_json" => json_encode(array(
        array(
            "id_lista_precio_detalle" => $idDetalleExistente > 0 ? $idDetalleExistente : "",
            "id_sku" => $idSku,
            "id_producto_erp" => "",
            "precio" => $precioValido,
            "moneda" => "MXN",
            "estatus" => "activo"
        )
    ))
);

$payloadInvalido = array(
    "id_lista_precio" => $idLista,
    "precios_json" => json_encode(array(
        array(
            "id_lista_precio_detalle" => $idDetalleExistente > 0 ? $idDetalleExistente : "",
            "id_sku" => $idSku,
            "id_producto_erp" => "",
            "precio" => 0,
            "moneda" => "MXN",
            "estatus" => "activo"
        )
    ))
);

$valido = $modelo->detallesLoteDryRun($payloadValido);
$invalido = $modelo->detallesLoteDryRun($payloadInvalido);

$checks = array(
    checkLote("valido_sin_error", empty($valido["error"]), "Lote valido responde sin error tecnico"),
    checkLote("valido_puede_guardar", !empty($valido["depurar"]["puede_guardar"]), "Lote valido queda guardable"),
    checkLote("valido_no_escribe", !empty($valido["depurar"]["no_escribe_bd"]), "Lote valido declara contrato read-only"),
    checkLote("invalido_bloquea", empty($invalido["depurar"]["puede_guardar"]), "Lote invalido no queda guardable"),
    checkLote("invalido_reporta_error", intval($invalido["depurar"]["resumen"]["errores"] ?? 0) > 0, "Lote invalido reporta errores")
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
    "resultado" => empty($bloqueos) ? "PASS_LOTE_DRYRUN" : "FAIL_LOTE_DRYRUN",
    "parametros" => array("id_lista_precio" => $idLista, "id_sku" => $idSku, "precio_valido" => $precioValido),
    "checks" => $checks,
    "valido" => array(
        "mensaje" => $valido["mensaje"] ?? "",
        "resumen" => $valido["depurar"]["resumen"] ?? null,
        "avisos" => $valido["depurar"]["avisos"] ?? array()
    ),
    "invalido" => array(
        "mensaje" => $invalido["mensaje"] ?? "",
        "resumen" => $invalido["depurar"]["resumen"] ?? null,
        "errores" => $invalido["depurar"]["errores"] ?? array()
    ),
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_guarda_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkLote($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function detalleExistenteLote($db, $idLista, $idSku) {
    $stmt = $db->prepare("SELECT id_lista_precio_detalle FROM erp_listas_precios_detalle WHERE id_lista_precio=:lista AND id_sku=:sku AND estatus<>'cancelado' ORDER BY id_lista_precio_detalle DESC LIMIT 1");
    $stmt->execute(array(":lista" => intval($idLista), ":sku" => intval($idSku)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? intval($fila["id_lista_precio_detalle"]) : 0;
}
