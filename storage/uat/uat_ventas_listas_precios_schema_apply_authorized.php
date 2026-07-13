<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: aplicar DDL minimo de Listas de precios solo con autorizacion explicita.
 * Impacto: agrega contrato CRM/listas y auditoria comercial para guardado UAT.
 * Contrato: BLOQUEADO por defecto; requiere dos tokens y respaldo externo generado o valido.
 */

$args = isset($argv) ? $argv : array();
$autorizarCrm = "";
$autorizarAuditoria = "";
$respaldo = "";
$generarRespaldo = false;
$directorioRespaldo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar_crm=") === 0) {
        $autorizarCrm = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--autorizar_auditoria=") === 0) {
        $autorizarAuditoria = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--generar_respaldo=") === 0) {
        $generarRespaldo = trim(substr($arg, 19), "\"' ") === "1";
    } elseif (strpos($arg, "--directorio_respaldo=") === 0) {
        $directorioRespaldo = trim(substr($arg, 22), "\"' ");
    }
}

$tokensValidos = $autorizarCrm === "VENTAS_LISTAS_PRECIOS_CRM_DDL" && $autorizarAuditoria === "VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL";
if (!$tokensValidos) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de Listas de precios. Falta autorizacion explicita.",
        "requerido" => array(
            "--autorizar_crm=VENTAS_LISTAS_PRECIOS_CRM_DDL",
            "--autorizar_auditoria=VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL",
            "--generar_respaldo=1 o --respaldo=RUTA_O_REFERENCIA_RESPALDO"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$respaldoGenerado = null;
if ($generarRespaldo && $respaldo === "") {
    try {
        $respaldo = generarRespaldoSchema($directorioRespaldo);
        $respaldoGenerado = $respaldo;
    } catch (Exception $e) {
        responder(array(
            "ok" => false,
            "modo" => "bloqueado",
            "mensaje" => "No se aplico DDL de Listas de precios. No se pudo generar respaldo externo.",
            "error_respaldo" => $e->getMessage()
        ));
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if (!$validacionRespaldo["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de Listas de precios. Falta respaldo externo valido.",
        "requerido" => array(
            "--generar_respaldo=1",
            "--directorio_respaldo=RUTA_EXTERNA_OPCIONAL",
            "--respaldo=RUTA_O_REFERENCIA_RESPALDO"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

$esquema = new VentasErpEsquema();
$antes = array(
    "crm" => $esquema->auditarListasPreciosCrm(),
    "auditoria" => $esquema->auditarAuditoriaListasPrecios()
);
$planCrm = $esquema->planActualizarListasPreciosCrm(true);
$planAuditoria = $esquema->planActualizarAuditoriaListasPrecios(true);
$despues = array(
    "crm" => $esquema->auditarListasPreciosCrm(),
    "auditoria" => $esquema->auditarAuditoriaListasPrecios()
);

responder(array(
    "ok" => true,
    "modo" => "ventas_listas_precios_schema_aplicado",
    "respaldo_ref" => $respaldo,
    "respaldo_generado" => $respaldoGenerado,
    "antes" => $antes,
    "plan" => array(
        "crm" => $planCrm,
        "auditoria" => $planAuditoria
    ),
    "despues" => $despues,
    "siguiente_paso" => "Ejecutar preflight, aplicar permisos ventas.listas.* y probar guardado UAT en borrador."
));

function generarRespaldoSchema($directorio) {
    $directorio = trim((string) $directorio);
    if ($directorio === "") {
        $home = getenv("USERPROFILE");
        $directorio = $home ? $home . "\\Documents\\RespaldosBD\\panel" : "C:\\respaldos";
    }
    $rutaProyecto = realpath(__DIR__ . "/../..");
    $rutaDestino = $directorio;
    if (!is_dir($rutaDestino) && !mkdir($rutaDestino, 0775, true)) {
        throw new Exception("No se pudo crear directorio externo de respaldo: " . $rutaDestino);
    }
    $realDestino = realpath($rutaDestino);
    if (!$realDestino || ($rutaProyecto && strpos(strtolower($realDestino), strtolower($rutaProyecto)) === 0)) {
        throw new Exception("El respaldo debe quedar fuera del proyecto: " . $rutaDestino);
    }
    $archivo = $realDestino . DIRECTORY_SEPARATOR . "panel_" . MYSQLBASE . "_" . date("Ymd_His") . "_antes_ventas_listas_precios_schema.sql";
    $pdo = new PDO("mysql:host=" . MYSQLHOST . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ));
    $salida = array();
    $salida[] = "-- Respaldo de esquema previo a DDL ERP Ventas/Listas de precios";
    $salida[] = "-- Base: " . MYSQLBASE;
    $salida[] = "-- Fecha: " . date("Y-m-d H:i:s");
    $salida[] = "-- No contiene datos de tablas.";
    $salida[] = "SET FOREIGN_KEY_CHECKS=0;";
    $tablas = $pdo->query("SHOW FULL TABLES")->fetchAll(PDO::FETCH_NUM);
    foreach ($tablas as $tablaInfo) {
        $tabla = $tablaInfo[0];
        $tipo = isset($tablaInfo[1]) ? $tablaInfo[1] : "BASE TABLE";
        if ($tipo !== "BASE TABLE") {
            continue;
        }
        $stmt = $pdo->query("SHOW CREATE TABLE `" . str_replace("`", "``", $tabla) . "`");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $create = isset($fila["Create Table"]) ? $fila["Create Table"] : "";
        if ($create !== "") {
            $salida[] = "";
            $salida[] = "DROP TABLE IF EXISTS `" . str_replace("`", "``", $tabla) . "`;";
            $salida[] = $create . ";";
        }
    }
    $salida[] = "SET FOREIGN_KEY_CHECKS=1;";
    if (file_put_contents($archivo, implode(PHP_EOL, $salida) . PHP_EOL) === false) {
        throw new Exception("No se pudo escribir respaldo externo: " . $archivo);
    }
    return $archivo;
}

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
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($datos["ok"]) ? 0 : 1);
}
