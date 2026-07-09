<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-27
 * Proposito: aplicar politicas base de Garantias ERP solo con autorizacion explicita.
 * Impacto: escribe politicas y una regla UAT de Garantias; no crea ventas, reclamos ni movimientos de inventario.
 * Contrato: requiere --token=GARANTIAS_POLITICAS_BASE, --respaldo=RUTA externa y --sku-uat=ID_SKU.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/GarantiasErp.php";

$opciones = getopt("", array("token:", "respaldo:", "sku-uat::"));
$token = isset($opciones["token"]) ? trim((string) $opciones["token"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$idSkuUat = isset($opciones["sku-uat"]) ? intval($opciones["sku-uat"]) : 7;
$raizProyecto = realpath(__DIR__ . "/../..");

function responderGarantiasPoliticasBase($error, $mensaje, $depurar = array()) {
    echo json_encode(array(
        "error" => $error,
        "tipo" => $error ? "danger" : "success",
        "mensaje" => $mensaje,
        "depurar" => $depurar
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit($error ? 1 : 0);
}

if ($token !== "GARANTIAS_POLITICAS_BASE") {
    responderGarantiasPoliticasBase(true, "Token de autorizacion invalido", array(
        "requerido" => "GARANTIAS_POLITICAS_BASE"
    ));
}

if ($respaldo === "" || !file_exists($respaldo) || filesize($respaldo) <= 0) {
    responderGarantiasPoliticasBase(true, "Indica respaldo externo existente y no vacio", array(
        "respaldo" => $respaldo
    ));
}

$realRespaldo = realpath($respaldo);
if ($raizProyecto && $realRespaldo && stripos($realRespaldo, $raizProyecto) === 0) {
    responderGarantiasPoliticasBase(true, "El respaldo debe estar fuera del proyecto", array(
        "respaldo" => $realRespaldo,
        "proyecto" => $raizProyecto
    ));
}

if ($idSkuUat <= 0) {
    responderGarantiasPoliticasBase(true, "Indica --sku-uat con un SKU ERP valido");
}

$modelo = new GarantiasErp();
$politicas = array(
    array(
        "codigo" => "SIN_GARANTIA",
        "nombre" => "Sin garantia",
        "tipo_garantia" => "sin_garantia",
        "duracion_valor" => 0,
        "unidad_duracion" => "dias"
    ),
    array(
        "codigo" => "GAR_TIENDA_7_DIAS_CAMBIO",
        "nombre" => "Garantia tienda 7 dias cambio",
        "tipo_garantia" => "garantia_tienda",
        "duracion_valor" => 7,
        "unidad_duracion" => "dias",
        "requiere_ticket" => 1,
        "requiere_empaque" => 1,
        "requiere_diagnostico" => 1,
        "permite_cambio" => 1
    ),
    array(
        "codigo" => "GAR_TIENDA_30_DIAS_DIAGNOSTICO",
        "nombre" => "Garantia tienda 30 dias con diagnostico",
        "tipo_garantia" => "garantia_tienda",
        "duracion_valor" => 30,
        "unidad_duracion" => "dias",
        "requiere_ticket" => 1,
        "requiere_cliente" => 1,
        "requiere_diagnostico" => 1,
        "requiere_fotos" => 1,
        "permite_cambio" => 1,
        "permite_reparacion" => 1
    ),
    array(
        "codigo" => "GAR_PROVEEDOR_SEGUN_POLITICA",
        "nombre" => "Garantia proveedor segun politica",
        "tipo_garantia" => "garantia_proveedor",
        "duracion_valor" => 30,
        "unidad_duracion" => "dias",
        "requiere_ticket" => 1,
        "requiere_cliente" => 1,
        "requiere_validacion_proveedor" => 1,
        "permite_envio_proveedor" => 1
    ),
    array(
        "codigo" => "GAR_FABRICANTE_SERIE",
        "nombre" => "Garantia fabricante con serie",
        "tipo_garantia" => "garantia_fabricante",
        "duracion_valor" => 90,
        "unidad_duracion" => "dias",
        "requiere_ticket" => 1,
        "requiere_cliente" => 1,
        "requiere_serie" => 1,
        "requiere_diagnostico" => 1,
        "permite_reparacion" => 1,
        "permite_envio_proveedor" => 1
    ),
    array(
        "codigo" => "CADUCIDAD_CALIDAD_LIMITADA",
        "nombre" => "Caducidad y calidad limitada",
        "tipo_garantia" => "caducidad_calidad",
        "duracion_valor" => 7,
        "unidad_duracion" => "dias",
        "requiere_ticket" => 1,
        "requiere_lote" => 1,
        "requiere_fotos" => 1,
        "requiere_diagnostico" => 1,
        "permite_cambio" => 1
    )
);

$resultados = array();
$idsPorCodigo = array();

foreach ($politicas as $politica) {
    $resultado = $modelo->guardarPolitica($politica);
    $resultados[] = array(
        "codigo" => $politica["codigo"],
        "resultado" => $resultado
    );

    if (!empty($resultado["error"])) {
        responderGarantiasPoliticasBase(true, "No se pudo guardar politica base", array(
            "codigo" => $politica["codigo"],
            "resultado" => $resultado
        ));
    }

    if (isset($resultado["depurar"]["id_garantia_politica"])) {
        $idsPorCodigo[$politica["codigo"]] = intval($resultado["depurar"]["id_garantia_politica"]);
    }
}

if (empty($idsPorCodigo["GAR_TIENDA_7_DIAS_CAMBIO"])) {
    responderGarantiasPoliticasBase(true, "No se obtuvo ID de politica UAT", array(
        "ids" => $idsPorCodigo
    ));
}

$reglaUat = array(
    "id_garantia_politica" => $idsPorCodigo["GAR_TIENDA_7_DIAS_CAMBIO"],
    "ambito" => "sku",
    "id_referencia" => $idSkuUat,
    "prioridad" => 10,
    "canal" => "pos",
    "observaciones" => "Regla UAT inicial autorizada para validar Garantias ERP."
);
$resultadoRegla = $modelo->guardarPoliticaRegla($reglaUat);

if (!empty($resultadoRegla["error"])) {
    responderGarantiasPoliticasBase(true, "No se pudo guardar regla UAT", array(
        "regla" => $reglaUat,
        "resultado" => $resultadoRegla
    ));
}

$resolver = $modelo->resolverGarantiaSku(array(
    "id_sku_erp" => $idSkuUat,
    "canal" => "pos",
    "fecha" => date("Y-m-d")
));

responderGarantiasPoliticasBase(false, "Politicas base de Garantias ERP aplicadas", array(
    "respaldo" => $realRespaldo,
    "politicas" => $resultados,
    "regla_uat" => array(
        "datos" => $reglaUat,
        "resultado" => $resultadoRegla
    ),
    "resolver_uat" => $resolver,
    "contrato" => array(
        "no_crea_ventas" => true,
        "no_crea_reclamos" => true,
        "no_mueve_inventario" => true
    )
));
