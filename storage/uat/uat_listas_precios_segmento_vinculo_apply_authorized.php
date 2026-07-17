<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: vincular una lista de precios a un segmento CRM con token y respaldo externo.
 * Impacto: permite UAT real del resolutor por segmento sin asignar clientes uno por uno.
 * Contrato: bloqueado por defecto; no crea segmentos, no cambia precios ni modifica ventas pasadas.
 */

$opciones = getopt("", array(
    "autorizar::",
    "respaldo::",
    "id_lista_precio::",
    "id_segmento_crm::",
    "codigo_segmento::",
    "canal::",
    "id_almacen::",
    "prioridad::",
    "fecha_inicio::",
    "fecha_fin::",
    "estatus::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoVinculoSegmento($respaldo);

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL" || !$validacion["ok"]) {
    responderVinculoSegmento(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se vinculo segmento/lista. Falta token o respaldo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL",
            "--respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_YYYYMMDD_HHmmss_antes_listas_precios_segmentos.sql",
            "--id_lista_precio=ID",
            "--codigo_segmento=RECURRENTE"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => array(
            "crea_vinculo_segmento_lista" => true,
            "crea_segmentos_crm" => false,
            "modifica_precios" => false,
            "modifica_clientes" => false,
            "modifica_ventas_pasadas" => false,
            "toca_pos" => false,
            "toca_ecommerce" => false
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

$modelo = new ListasPreciosErp();
$idLista = isset($opciones["id_lista_precio"]) ? intval($opciones["id_lista_precio"]) : 0;
$idSegmento = isset($opciones["id_segmento_crm"]) ? intval($opciones["id_segmento_crm"]) : 0;
$codigoSegmento = isset($opciones["codigo_segmento"]) ? trim((string) $opciones["codigo_segmento"]) : "";

if ($idSegmento <= 0 && $codigoSegmento !== "") {
    $idSegmento = buscarIdSegmentoCrmVinculo($modelo, $codigoSegmento);
}

$payload = array(
    "id_lista_precio" => $idLista,
    "id_segmento_crm" => $idSegmento,
    "canal" => isset($opciones["canal"]) ? trim((string) $opciones["canal"]) : "pos",
    "id_almacen" => isset($opciones["id_almacen"]) ? intval($opciones["id_almacen"]) : 5,
    "prioridad" => isset($opciones["prioridad"]) ? intval($opciones["prioridad"]) : 100,
    "fecha_inicio" => isset($opciones["fecha_inicio"]) ? trim((string) $opciones["fecha_inicio"]) : "",
    "fecha_fin" => isset($opciones["fecha_fin"]) ? trim((string) $opciones["fecha_fin"]) : "",
    "estatus" => isset($opciones["estatus"]) ? trim((string) $opciones["estatus"]) : "activo",
    "motivo" => "UAT autorizado de lista de precios por segmento CRM"
);

$dryRun = $modelo->asignacionSegmentoDryRun($payload);
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    responderVinculoSegmento(array(
        "ok" => false,
        "modo" => "preflight",
        "mensaje" => "No se vinculo segmento/lista; dry-run con bloqueos.",
        "validacion_respaldo" => $validacion,
        "payload" => $payload,
        "dry_run" => $dryRun
    ));
}

$resultado = $modelo->asignacionSegmentoGuardarAutorizado($payload, 0);
responderVinculoSegmento(array(
    "ok" => empty($resultado["error"]),
    "modo" => "apply_authorized",
    "mensaje" => isset($resultado["mensaje"]) ? $resultado["mensaje"] : "",
    "validacion_respaldo" => $validacion,
    "payload" => $payload,
    "dry_run" => $dryRun,
    "resultado" => $resultado,
    "siguiente_paso" => "Ejecutar dry-run POS con cliente que tenga id_segmento_default y validar regla_precio_origen=lista_segmento_cliente."
));

function buscarIdSegmentoCrmVinculo($modelo, $codigo) {
    $consulta = $modelo->segmentosCrmReadOnly(array("q" => $codigo, "limite" => 20));
    $segmentos = isset($consulta["depurar"]["segmentos"]) && is_array($consulta["depurar"]["segmentos"]) ? $consulta["depurar"]["segmentos"] : array();
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return intval($segmento["id_segmento_crm"]);
        }
    }
    return 0;
}

function validarRespaldoVinculoSegmento($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $pareceRuta) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $placeholder = respaldoPlaceholderVinculoSegmento($respaldo);
    return array(
        "ok" => $respaldo !== "" && strlen($respaldo) >= 8 && !$placeholder && (!$pareceRuta || ($existe && $legible && $tamano !== null && $tamano > 0)),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $pareceRuta ? $existe : null,
        "archivo_legible" => $pareceRuta ? $legible : null,
        "tamano_bytes" => $tamano,
        "placeholder_bloqueado" => $placeholder
    );
}

function respaldoPlaceholderVinculoSegmento($valor) {
    $valor = strtoupper(trim((string) $valor));
    return $valor === ""
        || strpos($valor, "RUTA_O_REFERENCIA") !== false
        || strpos($valor, "YYYYMMDD") !== false
        || strpos($valor, "PLACEHOLDER") !== false;
}

function responderVinculoSegmento($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}
