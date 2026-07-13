<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: aplicar DDL especifico de Almacen > Resurtido solo con autorizacion explicita y respaldo externo.
 * Impacto: crea tablas de politicas por tienda/SKU y flujo documental de resurtido/traspaso.
 * Contrato: BLOQUEADO por defecto; no usa el actualizador general para evitar tocar pendientes ajenos al modulo.
 */

$opciones = getopt("", array("autorizar::", "confirmacion::", "respaldo::", "sql::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$confirmacion = isset($opciones["confirmacion"]) ? trim((string) $opciones["confirmacion"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$sqlRelativo = isset($opciones["sql"]) ? trim((string) $opciones["sql"]) : "docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql";
$validacion = validarRespaldoResurtido($respaldo);
$token = "ALMACEN_RESURTIDO_DDL";
$frase = "AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA";

if ($autorizar !== $token || $confirmacion !== $frase || !$validacion["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto DDL de Resurtido. Falta token, confirmacion textual o respaldo valido.",
        "requerido" => array(
            "autorizar" => $token,
            "confirmacion" => $frase,
            "respaldo" => "RUTA_O_REFERENCIA_RESPALDO"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => alcanceResurtido()
    ), 1);
}

$raiz = realpath(__DIR__ . "/../..");
$sqlPath = realpath($raiz . DIRECTORY_SEPARATOR . $sqlRelativo);
if (!$sqlPath || strpos($sqlPath, $raiz) !== 0 || !is_file($sqlPath)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Archivo SQL de Resurtido no valido o fuera del proyecto.",
        "sql_solicitado" => $sqlRelativo
    ), 1);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/AlmacenEsquema.php";

$db = (new UatAlmacenResurtidoApplyDb())->db();
$esquema = new AlmacenEsquema();
$antes = $esquema->auditarAlmacenInventario();
$sentencias = extraerSentenciasSql(file_get_contents($sqlPath));
$ejecutadas = array();

foreach ($sentencias as $sentencia) {
    $db->exec($sentencia);
    $ejecutadas[] = primeraLinea($sentencia);
}

$despues = $esquema->auditarAlmacenInventario();
responder(array(
    "ok" => true,
    "modo" => "almacen_resurtido_schema_aplicado",
    "respaldo_ref" => $respaldo,
    "sql_path" => $sqlPath,
    "sentencias_total" => count($sentencias),
    "sentencias_ejecutadas" => $ejecutadas,
    "antes" => resumenResurtidoAuditoria($antes),
    "despues" => resumenResurtidoAuditoria($despues),
    "alcance" => alcanceResurtido(),
    "siguiente_paso" => "Ejecutar storage/uat/uat_almacen_resurtido_readonly.php y despues iniciar RES-T008 con guardado UAT controlado."
), 0);

class UatAlmacenResurtidoApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

function extraerSentenciasSql($sql) {
    $lineas = preg_split('/\R/', $sql);
    $limpio = array();
    foreach ($lineas as $linea) {
        $trim = trim($linea);
        if ($trim === "" || strpos($trim, "--") === 0) {
            continue;
        }
        $limpio[] = $linea;
    }
    $partes = explode(";", implode(PHP_EOL, $limpio));
    $sentencias = array();
    foreach ($partes as $parte) {
        $sentencia = trim($parte);
        if ($sentencia !== "") {
            $sentencias[] = $sentencia;
        }
    }
    return $sentencias;
}

function primeraLinea($sentencia) {
    $lineas = preg_split('/\R/', trim($sentencia));
    return isset($lineas[0]) ? $lineas[0] : "";
}

function validarRespaldoResurtido($respaldo) {
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
        "referencia" => $respaldo,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function resumenResurtidoAuditoria($auditoria) {
    $objetivo = array(
        "erp_inventario_politicas_almacen_sku",
        "erp_almacen_resurtidos",
        "erp_almacen_resurtido_detalle",
        "erp_almacen_resurtido_preparacion",
        "erp_almacen_resurtido_envios",
        "erp_almacen_resurtido_recepciones",
        "erp_almacen_resurtido_diferencias"
    );
    $resultado = array();
    $tablas = valor($auditoria, array("depurar", "auditoria"), array());
    foreach ($objetivo as $tabla) {
        $item = isset($tablas[$tabla]) ? $tablas[$tabla] : array();
        $resultado[$tabla] = array(
            "existe" => !empty($item["existe"]),
            "columnas_faltantes" => count(valor($item, array("columnas_faltantes"), array())),
            "indices_faltantes" => count(valor($item, array("indices_faltantes"), array())),
            "fks_faltantes" => count(valor($item, array("fks_faltantes"), array()))
        );
    }
    return $resultado;
}

function alcanceResurtido() {
    return array(
        "crea_politicas_tienda_sku" => true,
        "crea_solicitudes_resurtido" => true,
        "crea_preparacion_envio_recepcion_diferencias" => true,
        "mueve_inventario" => false,
        "crea_solicitudes_reales" => false,
        "toca_pos" => false,
        "toca_ecommerce" => false,
        "usa_actualizador_general_almacen" => false
    );
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}

function responder($datos, $codigoSalida) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($codigoSalida);
}
