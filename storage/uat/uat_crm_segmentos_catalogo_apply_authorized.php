<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: sembrar segmentos CRM base configurables solo con token y respaldo externo.
 * Impacto: crea segmentos comerciales iniciales para Listas de precios, sin asignar clientes ni listas.
 * Contrato: bloqueado por defecto; no toca ventas, POS, ecommerce ni precios.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoCrmSegmentos($respaldo);

if ($autorizar !== "CRM_CLIENTES_SEGMENTO_CATALOGO" || !$validacion["ok"]) {
    responderCrmSegmentos(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se sembraron segmentos CRM. Falta token o respaldo valido.",
        "requerido" => array(
            "--autorizar=CRM_CLIENTES_SEGMENTO_CATALOGO",
            "--respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_YYYYMMDD_HHmmss_antes_listas_precios_segmentos.sql"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => array(
            "crea_segmentos_crm" => true,
            "asigna_clientes" => false,
            "asigna_listas" => false,
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
$base = segmentosBaseCrmPrecios();
$resultados = array();
$ok = true;

foreach ($base as $segmento) {
    $existente = buscarSegmentoCrmPorCodigo($modelo, $segmento["codigo"]);
    if ($existente && isset($existente["id_segmento_crm"])) {
        $segmento["id_segmento_crm"] = intval($existente["id_segmento_crm"]);
    }
    $resultado = $modelo->segmentoCatalogoGuardarAutorizado($segmento);
    if (!empty($resultado["error"]) || (isset($resultado["tipo"]) && $resultado["tipo"] === "warning")) {
        $ok = false;
    }
    $resultados[] = array(
        "codigo" => $segmento["codigo"],
        "resultado" => $resultado
    );
}

responderCrmSegmentos(array(
    "ok" => $ok,
    "modo" => "apply_authorized",
    "mensaje" => $ok ? "Segmentos CRM base sembrados o actualizados" : "Segmentos CRM base con bloqueos; revisar resultados",
    "validacion_respaldo" => $validacion,
    "resultados" => $resultados,
    "guardrails" => array(
        "no_asigna_clientes" => true,
        "no_asigna_listas" => true,
        "no_modifica_precios" => true,
        "no_modifica_ventas_pasadas" => true
    )
));

function buscarSegmentoCrmPorCodigo($modelo, $codigo) {
    $consulta = $modelo->segmentosCatalogoReadOnly(array("q" => $codigo, "limite" => 20));
    $segmentos = isset($consulta["depurar"]["segmentos"]) && is_array($consulta["depurar"]["segmentos"]) ? $consulta["depurar"]["segmentos"] : array();
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}

function segmentosBaseCrmPrecios() {
    return array(
        array("codigo" => "PUBLICO_GENERAL", "nombre" => "Publico general", "tipo" => "comercial", "descripcion" => "Cliente sin relacion recurrente o venta anonima.", "estatus" => "activo"),
        array("codigo" => "RECURRENTE", "nombre" => "Cliente recurrente", "tipo" => "comercial", "descripcion" => "Compra frecuente con beneficio moderado.", "estatus" => "activo"),
        array("codigo" => "MAYOREO", "nombre" => "Mayoreo", "tipo" => "comercial", "descripcion" => "Compra por volumen o cuenta comercial.", "estatus" => "activo"),
        array("codigo" => "VIP", "nombre" => "VIP autorizado", "tipo" => "comercial", "descripcion" => "Cliente con mejores condiciones por autorizacion.", "estatus" => "activo"),
        array("codigo" => "INSTALADOR", "nombre" => "Instalador / tecnico", "tipo" => "comercial", "descripcion" => "Cliente que compra para instalaciones o mantenimiento.", "estatus" => "activo"),
        array("codigo" => "CONVENIO", "nombre" => "Convenio especial", "tipo" => "comercial", "descripcion" => "Acuerdo negociado con vigencia y motivo.", "estatus" => "activo"),
        array("codigo" => "ECOMMERCE_REG", "nombre" => "Ecommerce registrado", "tipo" => "comercial", "descripcion" => "Cliente registrado de ecommerce futuro.", "estatus" => "activo")
    );
}

function validarRespaldoCrmSegmentos($respaldo) {
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
    $placeholder = respaldoPlaceholderCrmSegmentos($respaldo);
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

function respaldoPlaceholderCrmSegmentos($valor) {
    $valor = strtoupper(trim((string) $valor));
    return $valor === ""
        || strpos($valor, "RUTA_O_REFERENCIA") !== false
        || strpos($valor, "YYYYMMDD") !== false
        || strpos($valor, "PLACEHOLDER") !== false;
}

function responderCrmSegmentos($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}
