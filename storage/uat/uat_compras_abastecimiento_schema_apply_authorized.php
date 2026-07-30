<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: aplicar DDL puntual para evidencia de abastecimiento en solicitudes solo con autorizacion explicita.
 * Impacto: agrega erp_compras_solicitudes_detalle.evidencia_costo_json si falta.
 * Contrato: bloqueado por defecto; exige token, frase textual y respaldo externo existente.
 */

$opciones = getopt("", array("autorizar::", "confirmacion::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$confirmacion = isset($opciones["confirmacion"]) ? trim((string) $opciones["confirmacion"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$token = "COMPRAS_ABASTECIMIENTO_SOLICITUDES_DDL";
$frase = "AUTORIZO DDL ABASTECIMIENTO SOLICITUDES usando respaldo RUTA_O_REFERENCIA";
$validacion = validarRespaldo($respaldo);

if ($autorizar !== $token || $confirmacion !== $frase || !$validacion["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto DDL. Falta token, confirmacion textual o respaldo valido.",
        "requerido" => array(
            "autorizar" => $token,
            "confirmacion" => $frase,
            "respaldo" => "RUTA_COMPLETA_RESPALDO_SQL"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => alcance()
    ), 1);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class UatComprasAbastecimientoSchemaDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatComprasAbastecimientoSchemaDb())->db();
$antes = columnaExiste($db, "erp_compras_solicitudes_detalle", "evidencia_costo_json");
$ejecutado = false;

if (!$antes) {
    $db->exec("ALTER TABLE `erp_compras_solicitudes_detalle` ADD COLUMN `evidencia_costo_json` TEXT NULL");
    $ejecutado = true;
}

$despues = columnaExiste($db, "erp_compras_solicitudes_detalle", "evidencia_costo_json");
responder(array(
    "ok" => $despues,
    "modo" => "schema_aplicado",
    "respaldo_ref" => $respaldo,
    "antes_columna_existia" => $antes,
    "despues_columna_existe" => $despues,
    "ddl_ejecutado" => $ejecutado,
    "alcance" => alcance(),
    "siguiente_paso" => "Guardar una solicitud con SKU proveedor y verificar que evidencia_costo_json se persista."
), $despues ? 0 : 1);

function columnaExiste(PDO $db, $tabla, $columna) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `" . $tabla . "` LIKE :columna");
    $stmt->execute(array(":columna" => $columna));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function validarRespaldo($ruta) {
    $ruta = trim((string) $ruta);
    if ($ruta === "") {
        return array("ok" => false, "motivo" => "respaldo_vacio");
    }
    $normalizada = str_replace("/", "\\", $ruta);
    if (stripos($normalizada, "C:\\xampp\\panel_db_backups\\") !== 0) {
        return array("ok" => false, "motivo" => "respaldo_fuera_ruta_estandar", "ruta" => $ruta);
    }
    if (!is_file($ruta)) {
        return array("ok" => false, "motivo" => "archivo_no_existe", "ruta" => $ruta);
    }
    $tamano = filesize($ruta);
    return array(
        "ok" => $tamano > 0,
        "ruta" => $ruta,
        "tamano_bytes" => $tamano
    );
}

function alcance() {
    return array(
        "tabla" => "erp_compras_solicitudes_detalle",
        "columna" => "evidencia_costo_json",
        "ddl" => "ADD COLUMN TEXT NULL si no existe",
        "no_toca" => array("solicitudes existentes", "ordenes", "proveedores", "costos", "inventario")
    );
}

function responder($payload, $codigo = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($codigo);
}
