<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: validar eventos de auditoria comercial de Listas de precios sin escribir BD.
 * Impacto: confirma trazabilidad de altas, cambios, asignaciones y cambios posteriores.
 * Contrato: read-only; no crea eventos ni modifica listas.
 */

$args = isset($argv) ? $argv : array();
$idLista = 0;
$codigoLista = "LP-UAT-BORRADOR-01";
$accion = "";
$limite = 50;

foreach ($args as $arg) {
    if (strpos($arg, "--id_lista_precio=") === 0) {
        $idLista = intval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--accion=") === 0) {
        $accion = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = intval(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpAuditoriaEventosReadonly extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpAuditoriaEventosReadonly();
$db = $listas->conexionUat();
if ($idLista <= 0 && $codigoLista !== "") {
    $idLista = buscarListaPorCodigo($db, $codigoLista);
}

$resultado = $listas->auditoriaReadOnly(array(
    "id_lista_precio" => $idLista,
    "accion" => $accion,
    "limite" => $limite
));
$eventos = isset($resultado["depurar"]["eventos"]) && is_array($resultado["depurar"]["eventos"])
    ? $resultado["depurar"]["eventos"]
    : array();
$bloqueos = array();

if (!empty($resultado["depurar"]["schema_pendiente"])) {
    $bloqueos[] = "Falta erp_listas_precios_eventos";
}
if ($idLista <= 0) {
    $bloqueos[] = "No existe lista objetivo o no se indico id_lista_precio";
}
if ($idLista > 0 && empty($eventos)) {
    $bloqueos[] = "No hay eventos de auditoria para la lista objetivo";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_auditoria_eventos_readonly",
    "read_only" => true,
    "entrada" => array(
        "codigo_lista" => $codigoLista,
        "id_lista_precio" => $idLista,
        "accion" => $accion,
        "limite" => $limite
    ),
    "auditoria" => $resultado,
    "bloqueos" => $bloqueos,
    "contrato" => array(
        "no_escribe_bd" => true,
        "valida_eventos_lista" => true,
        "valida_datos_antes_despues" => true,
        "valida_motivo" => true
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Auditoria comercial de la lista validada."
        : "Aplicar auditoria, crear lista o ejecutar operaciones UAT para generar eventos."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function buscarListaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT id_lista_precio FROM erp_listas_precios WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    return intval($stmt->fetchColumn());
}
