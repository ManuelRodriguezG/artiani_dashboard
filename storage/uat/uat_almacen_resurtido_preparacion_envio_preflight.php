<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: prevalidar un folio RES-* antes de preparar/enviar en RES-T009.
 * Impacto: revisa contrato documental, detalle autorizado, preparacion/envio y guardrails sin escribir BD.
 * Contrato: read-only; no autoriza, no prepara, no envia, no aparta stock y no mueve inventario.
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
$contrato = $modelo->preparacion_envio_resurtido_contrato_readonly(array());

$bloqueos = array();
$advertencias = array();
$depurar = empty($consulta["error"]) && isset($consulta["depurar"]) ? $consulta["depurar"] : array();
$schemaPendiente = intval(valor($depurar, array("schema_pendiente"), 0));

if (!empty($consulta["error"])) {
    $bloqueos[] = array("id" => "RES-PRE-001", "mensaje" => valor($consulta, array("mensaje"), "Consulta de folio fallo"));
}
if ($schemaPendiente === 1) {
    $advertencias[] = array("id" => "RES-PRE-002", "mensaje" => "Esquema pendiente; no se puede prevalidar un folio real todavia");
}

$encabezado = valor($depurar, array("encabezado"), null);
$detalle = valor($depurar, array("detalle"), array());
$preparacion = valor($depurar, array("preparacion"), array());
$envios = valor($depurar, array("envios"), array());
$recepciones = valor($depurar, array("recepciones"), array());
$diferencias = valor($depurar, array("diferencias"), array());

if ($schemaPendiente === 0) {
    if ($encabezado === null) {
        $bloqueos[] = array("id" => "RES-PRE-003", "mensaje" => "Folio no encontrado");
    } else {
        $estatus = trim((string) valor($encabezado, array("estatus"), ""));
        if (!in_array($estatus, array("autorizado", "preparando", "preparado"), true)) {
            $bloqueos[] = array("id" => "RES-PRE-004", "mensaje" => "Folio no esta en estado valido para preparacion/envio", "estatus" => $estatus);
        }
        if (in_array($estatus, array("rechazado", "cancelado", "cerrado", "recibido"), true)) {
            $bloqueos[] = array("id" => "RES-PRE-005", "mensaje" => "Folio en estado terminal o no preparable", "estatus" => $estatus);
        }
    }

    if (empty($detalle)) {
        $bloqueos[] = array("id" => "RES-PRE-006", "mensaje" => "Folio sin detalle");
    }
    $lineasAutorizadas = 0;
    foreach ($detalle as $idx => $linea) {
        $cantidadAutorizada = round(floatval(valor($linea, array("cantidad_autorizada"), 0)), 6);
        $estatusLinea = trim((string) valor($linea, array("estatus"), ""));
        if ($cantidadAutorizada > 0 && !in_array($estatusLinea, array("rechazada", "cancelada"), true)) {
            $lineasAutorizadas++;
        }
        if ($cantidadAutorizada < 0) {
            $bloqueos[] = array("id" => "RES-PRE-007", "linea" => $idx, "mensaje" => "Cantidad autorizada negativa");
        }
    }
    if ($lineasAutorizadas <= 0) {
        $bloqueos[] = array("id" => "RES-PRE-008", "mensaje" => "No hay lineas autorizadas para preparar");
    }
    if (!empty($recepciones)) {
        $bloqueos[] = array("id" => "RES-PRE-009", "mensaje" => "Folio ya tiene recepciones; no corresponde RES-T009");
    }
    if (!empty($diferencias)) {
        $advertencias[] = array("id" => "RES-PRE-010", "mensaje" => "Folio ya tiene diferencias registradas antes de RES-T010");
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_resurtido_preparacion_envio_preflight",
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
        "no_prepara" => true,
        "no_envia" => true,
        "no_mueve_kardex" => true,
        "no_modifica_unidades" => true,
        "no_toca_pos_ecommerce" => true
    ),
    "bloqueos" => $bloqueos,
    "advertencias" => $advertencias,
    "siguiente_paso" => empty($bloqueos)
        ? "Folio candidato para RES-T009; ejecutar accion real solo con respaldo/autorizacion y UAT controlado."
        : "Resolver bloqueos antes de preparar/enviar."
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
