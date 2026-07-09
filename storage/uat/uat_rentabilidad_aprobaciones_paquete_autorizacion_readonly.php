<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$rentabilidad = new RentabilidadErp();
$respuesta = $rentabilidad->paqueteAutorizacionAprobaciones(array(
    "q" => "TP-40372",
    "canal" => "menudeo",
    "limite" => 20
));

$depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$resumen = isset($depurar["resumen"]) ? $depurar["resumen"] : array();
$acciones = isset($depurar["acciones"]) ? $depurar["acciones"] : array();
$auth = isset($depurar["autorizaciones_requeridas"]) ? $depurar["autorizaciones_requeridas"] : array();

$ok = empty($respuesta["error"])
    && isset($resumen["estado"])
    && intval(isset($resumen["schema_pendiente"]) ? $resumen["schema_pendiente"] : -1) >= 0
    && isset($auth["aplicar_esquema"])
    && count($acciones) >= 2;

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
    "resumen" => array(
        "estado" => isset($resumen["estado"]) ? $resumen["estado"] : "",
        "schema_disponible" => intval(isset($resumen["schema_disponible"]) ? $resumen["schema_disponible"] : 0),
        "schema_pendiente" => intval(isset($resumen["schema_pendiente"]) ? $resumen["schema_pendiente"] : 0),
        "aprobaciones_creables" => intval(isset($resumen["aprobaciones_creables"]) ? $resumen["aprobaciones_creables"] : 0),
        "aprobaciones_bloqueadas" => intval(isset($resumen["aprobaciones_bloqueadas"]) ? $resumen["aprobaciones_bloqueadas"] : 0),
        "construccion" => isset($resumen["construccion"]) ? $resumen["construccion"] : "",
        "uso_comercial" => isset($resumen["uso_comercial"]) ? $resumen["uso_comercial"] : ""
    ),
    "acciones" => array_map(function ($item) {
        return array(
            "id" => isset($item["id"]) ? $item["id"] : "",
            "estado" => isset($item["estado"]) ? $item["estado"] : "",
            "prioridad" => isset($item["prioridad"]) ? $item["prioridad"] : ""
        );
    }, $acciones),
    "frase_esquema" => isset($auth["aplicar_esquema"]) ? $auth["aplicar_esquema"] : "",
    "reglas" => isset($depurar["reglas"]) ? $depurar["reglas"] : array()
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

