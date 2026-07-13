<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: aplicar cambio posterior autorizado de precio para validar snapshot historico.
 * Impacto: edita `erp_listas_precios_detalle` y registra evento comercial; no modifica ventas pasadas.
 * Contrato: BLOQUEADO por defecto; requiere token, usuario, permiso y auditoria comercial.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$idUsuario = 0;
$codigoLista = "LP-UAT-BORRADOR-01";
$idSku = 1760;
$precioNuevo = 325.00;
$moneda = "MXN";
$motivo = "UAT cambio posterior de precio para validar snapshot historico";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--precio_nuevo=") === 0) {
        $precioNuevo = round(floatval(trim(substr($arg, 15), "\"' ")), 6);
    } elseif (strpos($arg, "--moneda=") === 0) {
        $moneda = strtoupper(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT" || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se cambio precio posterior. Falta autorizacion explicita o id_usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        ),
        "requiere_respaldo_externo" => false
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpCambioPosteriorApply extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpCambioPosteriorApply();
$db = $listas->conexionUat();
$lista = buscarListaPorCodigo($db, $codigoLista);
$idLista = intval(valor($lista, "id_lista_precio", 0));
$detalle = $idLista > 0 ? buscarDetalleListaSku($db, $idLista, $idSku) : null;
$idDetalle = intval(valor($detalle, "id_lista_precio_detalle", 0));
$entrada = array(
    "id_lista_precio_detalle" => $idDetalle,
    "id_lista_precio" => $idLista,
    "id_sku" => $idSku,
    "precio" => $precioNuevo,
    "moneda" => $moneda,
    "estatus" => "activo",
    "motivo" => $motivo
);
$dryRun = $listas->detalleDryRun($entrada);
$bloqueos = array();

if (!$lista) {
    $bloqueos[] = "No existe la lista objetivo";
}
if (!$detalle) {
    $bloqueos[] = "No existe detalle activo para SKU objetivo";
}
if (!tablaExiste($db, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta erp_listas_precios_eventos; no se permite cambiar sin auditoria comercial";
}
if (!permisoExiste($db, "ventas.listas.editar")) {
    $bloqueos[] = "Falta permiso ventas.listas.editar en BD";
}
if ($detalle && abs(floatval(valor($detalle, "precio", 0)) - $precioNuevo) <= 0.0001) {
    $bloqueos[] = "El detalle ya tiene el precio nuevo; no hay cambio posterior que probar";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de cambio posterior no permite guardar";
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico cambio posterior; faltan precondiciones UAT.",
        "entrada" => $entrada,
        "lista" => $lista,
        "detalle_actual" => $detalle,
        "dry_run" => $dryRun,
        "bloqueos" => $bloqueos,
        "requiere_respaldo_externo" => false
    ));
}

$resultado = $listas->detalleGuardarAutorizado($entrada, $idUsuario);
responder(array(
    "ok" => empty($resultado["error"]),
    "modo" => "ventas_listas_precios_cambio_posterior_apply_authorized",
    "entrada" => $entrada,
    "resultado" => $resultado,
    "id_usuario" => $idUsuario,
    "requiere_respaldo_externo" => false,
    "siguiente_paso" => empty($resultado["error"])
        ? "Revalidar snapshot por folio esperando precio historico anterior y lista actual nueva."
        : "Resolver bloqueo reportado antes de intentar nuevo cambio."
));

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

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function permisoExiste($db, $permiso) {
    $stmt = $db->prepare("SELECT id_permiso FROM sys_permisos WHERE permiso=:permiso AND estatus=1 LIMIT 1");
    $stmt->execute(array(":permiso" => $permiso));
    return (bool) $stmt->fetchColumn();
}

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
