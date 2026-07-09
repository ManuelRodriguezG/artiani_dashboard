<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$sinFraseCrear = $modelo->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$sinRespaldoCrear = $modelo->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "confirmar_autorizacion" => "AUTORIZO CREAR APROBACION INTERNA"
), 0);

$schemaPendienteCrear = $modelo->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "confirmar_autorizacion" => "AUTORIZO CREAR APROBACION INTERNA",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$sinFraseResolver = $modelo->resolverAprobacionInterna(array(
    "id_aprobacion" => 1,
    "accion" => "aprobar",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$schemaPendienteResolver = $modelo->resolverAprobacionInterna(array(
    "id_aprobacion" => 1,
    "accion" => "aprobar",
    "confirmar_autorizacion" => "AUTORIZO RESOLVER APROBACION INTERNA",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$ok = true;
if (empty($sinFraseCrear["error"]) || $sinFraseCrear["mensaje"] !== "Autorizacion requerida para crear aprobacion comercial interna") {
    $ok = false;
}
if (empty($sinRespaldoCrear["error"]) || strpos($sinRespaldoCrear["mensaje"], "referencia del respaldo externo") === false) {
    $ok = false;
}
if (empty($schemaPendienteCrear["error"]) || $schemaPendienteCrear["mensaje"] !== "El esquema de aprobaciones internas no esta aplicado") {
    $ok = false;
}
if (empty($sinFraseResolver["error"]) || $sinFraseResolver["mensaje"] !== "Autorizacion requerida para resolver aprobacion comercial interna") {
    $ok = false;
}
if (empty($schemaPendienteResolver["error"]) || $schemaPendienteResolver["mensaje"] !== "El esquema de aprobaciones internas no esta aplicado") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "crear_sin_frase" => array("error" => $sinFraseCrear["error"], "mensaje" => $sinFraseCrear["mensaje"]),
    "crear_sin_respaldo" => array("error" => $sinRespaldoCrear["error"], "mensaje" => $sinRespaldoCrear["mensaje"]),
    "crear_schema_pendiente" => array("error" => $schemaPendienteCrear["error"], "mensaje" => $schemaPendienteCrear["mensaje"]),
    "resolver_sin_frase" => array("error" => $sinFraseResolver["error"], "mensaje" => $sinFraseResolver["mensaje"]),
    "resolver_schema_pendiente" => array("error" => $schemaPendienteResolver["error"], "mensaje" => $schemaPendienteResolver["mensaje"])
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

