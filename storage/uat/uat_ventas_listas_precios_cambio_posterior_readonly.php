<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar cambio posterior de precio de lista despues de venta UAT.
 * Impacto: prepara prueba de inmutabilidad del snapshot historico sin escribir BD.
 * Contrato: read-only; no cambia lista, detalle, venta ni snapshot.
 */

$args = isset($argv) ? $argv : array();
$codigoLista = "LP-UAT-BORRADOR-01";
$idSku = 1760;
$precioNuevo = 325.00;
$moneda = "MXN";

foreach ($args as $arg) {
    if (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--precio_nuevo=") === 0) {
        $precioNuevo = round(floatval(trim(substr($arg, 15), "\"' ")), 6);
    } elseif (strpos($arg, "--moneda=") === 0) {
        $moneda = strtoupper(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpCambioPosteriorReadonly extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpCambioPosteriorReadonly();
$db = $listas->conexionUat();
$lista = buscarListaPorCodigo($db, $codigoLista);
$idLista = intval(valor($lista, "id_lista_precio", 0));
$detalle = $idLista > 0 ? buscarDetalleListaSku($db, $idLista, $idSku) : null;
$idDetalle = intval(valor($detalle, "id_lista_precio_detalle", 0));
$esquema = new VentasErpEsquema();
$auditoriaEventos = $esquema->auditarAuditoriaListasPrecios();

$entrada = array(
    "id_lista_precio_detalle" => $idDetalle,
    "id_lista_precio" => $idLista,
    "id_sku" => $idSku,
    "precio" => $precioNuevo,
    "moneda" => $moneda,
    "estatus" => "activo"
);
$dryRun = $listas->detalleDryRun($entrada);
$bloqueos = array();

if (!$lista) {
    $bloqueos[] = "No existe la lista objetivo";
}
if (!$detalle) {
    $bloqueos[] = "No existe detalle activo para SKU objetivo";
}
if (!tablaExisteEnAuditoria($auditoriaEventos, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta DDL de auditoria erp_listas_precios_eventos";
}
if ($detalle && abs(floatval(valor($detalle, "precio", 0)) - $precioNuevo) <= 0.0001) {
    $bloqueos[] = "El detalle ya tiene el precio nuevo; no hay cambio posterior que probar";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de cambio posterior no permite guardar";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_cambio_posterior_readonly",
    "read_only" => true,
    "entrada" => $entrada,
    "lista" => $lista,
    "detalle_actual" => $detalle,
    "precio_actual" => $detalle ? floatval(valor($detalle, "precio", 0)) : null,
    "precio_nuevo" => $precioNuevo,
    "dry_run" => $dryRun,
    "auditoria_eventos" => $auditoriaEventos,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "token_guardado" => "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
        "permiso" => "ventas.listas.editar",
        "requiere_respaldo_externo" => false,
        "motivo" => "Cambio posterior de precio es CRUD comercial auditado; no cambia esquema."
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para aplicar cambio posterior autorizado y revalidar snapshot por folio."
        : "Completar lista/detalle/auditoria o elegir un precio nuevo distinto."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function buscarListaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT * FROM erp_listas_precios WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function buscarDetalleListaSku($db, $idLista, $idSku) {
    $stmt = $db->prepare("SELECT * FROM erp_listas_precios_detalle
        WHERE id_lista_precio=:lista AND id_sku=:sku AND estatus='activo'
        ORDER BY id_lista_precio_detalle DESC LIMIT 1");
    $stmt->execute(array(":lista" => intval($idLista), ":sku" => intval($idSku)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function tablaExisteEnAuditoria($auditoria, $tabla) {
    $tablas = valorRuta($auditoria, array("depurar", "tablas"), array());
    foreach ($tablas as $item) {
        if (isset($item["tabla"]) && $item["tabla"] === $tabla) {
            return !empty($item["existe"]);
        }
    }
    return false;
}

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}

function valorRuta($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
