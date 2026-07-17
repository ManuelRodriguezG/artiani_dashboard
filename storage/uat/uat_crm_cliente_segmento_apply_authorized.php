<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: asignar un cliente CRM a un segmento para UAT de listas de precios por segmento.
 * Impacto: crea relacion cliente-segmento y opcionalmente actualiza `id_segmento_default`.
 * Contrato: bloqueado por defecto; no modifica listas, precios, POS ni ventas pasadas.
 */

$opciones = getopt("", array(
    "autorizar::",
    "respaldo::",
    "id_cliente_crm::",
    "id_segmento_crm::",
    "codigo_segmento::",
    "principal::",
    "actualizar_default::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoClienteSegmento($respaldo);

if ($autorizar !== "CRM_CLIENTES_SEGMENTO" || !$validacion["ok"]) {
    responderClienteSegmento(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se asigno segmento al cliente CRM. Falta token o respaldo valido.",
        "requerido" => array(
            "--autorizar=CRM_CLIENTES_SEGMENTO",
            "--respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_YYYYMMDD_HHmmss_antes_listas_precios_segmentos.sql",
            "--id_cliente_crm=ID",
            "--codigo_segmento=RECURRENTE"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => array(
            "crea_relacion_cliente_segmento" => true,
            "actualiza_segmento_default" => true,
            "modifica_listas" => false,
            "modifica_precios" => false,
            "modifica_ventas_pasadas" => false,
            "toca_pos" => false,
            "toca_ecommerce" => false
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ClientesCrm.php";

$modelo = new ClientesCrm();
$idCliente = isset($opciones["id_cliente_crm"]) ? intval($opciones["id_cliente_crm"]) : 0;
$idSegmento = isset($opciones["id_segmento_crm"]) ? intval($opciones["id_segmento_crm"]) : 0;
$codigoSegmento = isset($opciones["codigo_segmento"]) ? trim((string) $opciones["codigo_segmento"]) : "";

if ($idSegmento <= 0 && $codigoSegmento !== "") {
    $idSegmento = buscarIdSegmentoClienteUat($modelo, $codigoSegmento);
}

$payload = array(
    "id_cliente_crm" => $idCliente,
    "id_segmento_crm" => $idSegmento,
    "principal" => isset($opciones["principal"]) ? intval($opciones["principal"]) : 1,
    "actualizar_default" => isset($opciones["actualizar_default"]) ? intval($opciones["actualizar_default"]) : 1,
    "id_usuario" => 0
);

$dryRun = $modelo->segmentoAsignarDryRun($payload);
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    responderClienteSegmento(array(
        "ok" => false,
        "modo" => "preflight",
        "mensaje" => "No se asigno segmento al cliente; dry-run con bloqueos.",
        "validacion_respaldo" => $validacion,
        "payload" => $payload,
        "dry_run" => $dryRun
    ));
}

$resultado = $modelo->segmentoAsignarAutorizado($payload);
responderClienteSegmento(array(
    "ok" => empty($resultado["error"]),
    "modo" => "apply_authorized",
    "mensaje" => isset($resultado["mensaje"]) ? $resultado["mensaje"] : "",
    "validacion_respaldo" => $validacion,
    "payload" => $payload,
    "dry_run" => $dryRun,
    "resultado" => $resultado,
    "siguiente_paso" => "Ejecutar resolutor read-only con este cliente y validar origen lista_segmento_cliente."
));

function buscarIdSegmentoClienteUat($modelo, $codigo) {
    $consulta = $modelo->segmentosCatalogoReadOnly(array("q" => $codigo, "limite" => 20));
    $segmentos = isset($consulta["depurar"]["segmentos"]) && is_array($consulta["depurar"]["segmentos"]) ? $consulta["depurar"]["segmentos"] : array();
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return intval($segmento["id_segmento_crm"]);
        }
    }
    return 0;
}

function validarRespaldoClienteSegmento($respaldo) {
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
    $placeholder = respaldoPlaceholderClienteSegmento($respaldo);
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

function respaldoPlaceholderClienteSegmento($valor) {
    $valor = strtoupper(trim((string) $valor));
    return $valor === ""
        || strpos($valor, "RUTA_O_REFERENCIA") !== false
        || strpos($valor, "YYYYMMDD") !== false
        || strpos($valor, "PLACEHOLDER") !== false;
}

function responderClienteSegmento($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}
