<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: prevalidar un folio RES-* antes de recepcion/diferencias en RES-T010.
 * Impacto: revisa folio enviado, envios pendientes y diferencias existentes sin escribir BD.
 * Contrato: read-only; no recibe, no registra diferencias, no mueve inventario y no modifica unidades.
 */

$opciones = getopt("", array("folio::", "id::"));
$folio = isset($opciones["folio"]) ? trim((string) $opciones["folio"]) : "";
$id = isset($opciones["id"]) ? intval($opciones["id"]) : 0;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";

$modelo = new Almacenes();
$consulta = $modelo->consultar_resurtido_readonly(array(
    "id_resurtido_almacen" => $id,
    "folio" => $folio
));
$contrato = $modelo->recepcion_diferencias_resurtido_contrato_readonly(array());

$bloqueos = array();
$advertencias = array();
$depurar = empty($consulta["error"]) && isset($consulta["depurar"]) ? $consulta["depurar"] : array();
$schemaPendiente = intval(valor($depurar, array("schema_pendiente"), 0));

if (!empty($consulta["error"])) {
    $bloqueos[] = array("id" => "RES-REC-PRE-001", "mensaje" => valor($consulta, array("mensaje"), "Consulta de folio fallo"));
}
if ($schemaPendiente === 1) {
    $advertencias[] = array("id" => "RES-REC-PRE-002", "mensaje" => "Esquema pendiente; no se puede prevalidar recepcion real todavia");
}

$encabezado = valor($depurar, array("encabezado"), null);
$detalle = valor($depurar, array("detalle"), array());
$preparacion = valor($depurar, array("preparacion"), array());
$envios = valor($depurar, array("envios"), array());
$recepciones = valor($depurar, array("recepciones"), array());
$diferencias = valor($depurar, array("diferencias"), array());

if ($schemaPendiente === 0) {
    if ($encabezado === null) {
        $bloqueos[] = array("id" => "RES-REC-PRE-003", "mensaje" => "Folio no encontrado");
    } else {
        $estatus = trim((string) valor($encabezado, array("estatus"), ""));
        if ($estatus !== "enviado") {
            $bloqueos[] = array("id" => "RES-REC-PRE-004", "mensaje" => "Folio no esta enviado", "estatus" => $estatus);
        }
        if (in_array($estatus, array("rechazado", "cancelado", "cerrado", "recibido"), true)) {
            $bloqueos[] = array("id" => "RES-REC-PRE-005", "mensaje" => "Folio en estado terminal o no recibible", "estatus" => $estatus);
        }
    }

    if (empty($envios)) {
        $bloqueos[] = array("id" => "RES-REC-PRE-006", "mensaje" => "Folio sin envios registrados");
    }
    if (empty($detalle)) {
        $bloqueos[] = array("id" => "RES-REC-PRE-007", "mensaje" => "Folio sin detalle");
    }

    $enviosRecibibles = 0;
    foreach ($envios as $idx => $envio) {
        $estatusEnvio = trim((string) valor($envio, array("estatus"), ""));
        $cantidadEnviada = round(floatval(valor($envio, array("cantidad_enviada"), 0)), 6);
        if ($cantidadEnviada <= 0) {
            $bloqueos[] = array("id" => "RES-REC-PRE-008", "linea" => $idx, "mensaje" => "Envio con cantidad enviada invalida");
        }
        if (!in_array($estatusEnvio, array("recibido", "cancelado"), true)) {
            $enviosRecibibles++;
        }
    }
    if (!empty($envios) && $enviosRecibibles <= 0) {
        $bloqueos[] = array("id" => "RES-REC-PRE-009", "mensaje" => "No hay envios pendientes de recibir");
    }
    if (!empty($recepciones)) {
        $advertencias[] = array("id" => "RES-REC-PRE-010", "mensaje" => "Folio ya tiene recepciones; revisar si es recepcion parcial o duplicada");
    }
    if (!empty($diferencias)) {
        $advertencias[] = array("id" => "RES-REC-PRE-011", "mensaje" => "Folio ya tiene diferencias abiertas o historicas");
    }
    if (empty($preparacion)) {
        $advertencias[] = array("id" => "RES-REC-PRE-012", "mensaje" => "Folio enviado sin preparacion visible en consulta; revisar datos antes de recibir");
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_resurtido_recepcion_diferencias_preflight",
    "read_only" => true,
    "filtros" => array(
        "folio" => $folio,
        "id_resurtido_almacen" => $id
    ),
    "consulta" => resumenRespuesta($consulta),
    "contrato" => resumenRespuesta($contrato),
    "resumen" => array(
        "schema_pendiente" => $schemaPendiente,
        "encabezado_existe" => $encabezado !== null,
        "detalle_lineas" => is_array($detalle) ? count($detalle) : 0,
        "preparacion_lineas" => is_array($preparacion) ? count($preparacion) : 0,
        "envios" => is_array($envios) ? count($envios) : 0,
        "recepciones" => is_array($recepciones) ? count($recepciones) : 0,
        "diferencias" => is_array($diferencias) ? count($diferencias) : 0
    ),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_recibe" => true,
        "no_registra_diferencias" => true,
        "no_mueve_kardex" => true,
        "no_modifica_unidades" => true,
        "no_toca_pos_ecommerce" => true
    ),
    "bloqueos" => $bloqueos,
    "advertencias" => $advertencias,
    "siguiente_paso" => empty($bloqueos)
        ? "Folio candidato para RES-T010; ejecutar recepcion real solo con respaldo/autorizacion y UAT controlado."
        : "Resolver bloqueos antes de recibir."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function resumenRespuesta($respuesta) {
    return array(
        "error" => valor($respuesta, array("error"), null),
        "tipo" => valor($respuesta, array("tipo"), null),
        "mensaje" => valor($respuesta, array("mensaje"), null),
        "schema_pendiente" => valor($respuesta, array("depurar", "schema_pendiente"), null)
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
