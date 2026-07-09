<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-27
 * Proposito: validar paquete base de politicas de Garantias ERP sin escribir BD.
 * Impacto: Garantias/Catalogo; prepara autorizacion de politicas reales.
 * Contrato: dry-run; no inserta politicas ni reglas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/GarantiasErp.php";

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
foreach ($politicas as $politica) {
    $resultados[] = array(
        "codigo" => $politica["codigo"],
        "resultado" => $modelo->politicaDryRun($politica)
    );
}

$reglaUat = array(
    "id_garantia_politica" => 1,
    "ambito" => "sku",
    "id_referencia" => 7,
    "prioridad" => 10,
    "canal" => "pos"
);

$respuesta = array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Dry-run de politicas base de Garantias ERP generado",
    "depurar" => array(
        "dry_run" => true,
        "politicas" => $resultados,
        "regla_uat_sugerida" => array(
            "datos" => $reglaUat,
            "resultado" => $modelo->politicaReglaDryRun($reglaUat)
        ),
        "contrato" => array(
            "no_inserta_politicas" => true,
            "no_inserta_reglas" => true,
            "requiere_autorizacion" => "GARANTIAS_POLITICAS_BASE"
        )
    )
);

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
