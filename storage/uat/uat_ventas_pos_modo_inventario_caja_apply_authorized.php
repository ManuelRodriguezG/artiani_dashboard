<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-18.
 * Proposito: cambiar el modo de inventario de una caja POS con autorizacion explicita.
 * Impacto: actualiza solo `erp_pos_cajas`; no abre turnos, no cobra, no mueve inventario y no modifica ventas historicas.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_MODO_INVENTARIO_CAJA_REAL, respaldo valido, id_caja y modo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idCaja = 0;
$modo = "normal";
$generarAlertas = 1;
$motivo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(substr($arg, 10));
    } elseif (strpos($arg, "--modo=") === 0) {
        $modo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--generar_alertas=") === 0) {
        $generarAlertas = intval(substr($arg, 18)) ? 1 : 0;
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_MODO_INVENTARIO_CAJA_REAL" || !$validacionRespaldo["ok"] || $idCaja <= 0 || !in_array($modo, array("normal", "piloto_sin_inventario"), true)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se cambio modo de inventario de caja POS. Falta autorizacion, respaldo, id_caja o modo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_MODO_INVENTARIO_CAJA_REAL",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_caja=ID",
            "--modo=normal|piloto_sin_inventario"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class ConexionUatModoInventarioPos extends CRUD {
    public function pdo() {
        return $this->getConexion();
    }
}

$schema = new DBSchema();
if (!$schema->tablaExiste("erp_pos_cajas") || !$schema->columnaExiste("erp_pos_cajas", "afectar_inventario")) {
    responder(array(
        "ok" => false,
        "modo" => "schema_pendiente",
        "mensaje" => "Falta aplicar DDL de modo inventario POS antes de configurar la caja."
    ));
}

$conexion = new ConexionUatModoInventarioPos();
$db = $conexion->pdo();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "conexion_no_disponible",
        "mensaje" => "No fue posible conectar a la base de datos para actualizar la caja POS."
    ));
}
$stmt = $db->prepare("SELECT id_caja, codigo, nombre, id_almacen, afectar_inventario, modo_operacion_inventario, generar_alertas_inventario, estatus
    FROM erp_pos_cajas
    WHERE id_caja=:caja
    LIMIT 1");
$stmt->execute(array(":caja" => $idCaja));
$antes = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$antes) {
    responder(array(
        "ok" => false,
        "modo" => "no_encontrada",
        "mensaje" => "No existe la caja POS solicitada",
        "id_caja" => $idCaja
    ));
}

$afectarInventario = $modo === "piloto_sin_inventario" ? 0 : 1;
$stmtUpdate = $db->prepare("UPDATE erp_pos_cajas
    SET afectar_inventario=:afectar,
        modo_operacion_inventario=:modo,
        generar_alertas_inventario=:alertas,
        observaciones=TRIM(CONCAT(COALESCE(observaciones, ''), '\n', :motivo)),
        fecha_actualizacion=NOW()
    WHERE id_caja=:caja");
$stmtUpdate->execute(array(
    ":afectar" => $afectarInventario,
    ":modo" => $modo,
    ":alertas" => $generarAlertas,
    ":motivo" => $motivo !== "" ? "[Modo inventario POS] " . $motivo : "[Modo inventario POS] Cambio autorizado a " . $modo,
    ":caja" => $idCaja
));

$stmt->execute(array(":caja" => $idCaja));
$despues = $stmt->fetch(PDO::FETCH_ASSOC);

responder(array(
    "ok" => true,
    "modo" => "modo_inventario_caja_actualizado",
    "respaldo_ref" => $respaldo,
    "antes" => $antes,
    "despues" => $despues,
    "siguiente_paso" => "Abrir turno y ejecutar venta POS real para confirmar comportamiento de caja/reportes sin afectar inventario si esta en piloto."
));

function validarRespaldo($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "referencia_presente" => $okReferencia,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
