<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-11.
 * Proposito: validar cierre read-only de Inventario Inicial Real.
 * Impacto: consulta referencias UAT de unidad abierta, unidad compra y unidad cerrada.
 * Contrato: no escribe BD, no mueve kardex, no modifica etiquetas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/InventarioErp.php";

class UatInvInicialRealCierreDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatInvInicialRealCierreDb())->db();
if (!$db) {
    responder(false, array("Conexion MySQL no disponible"));
}

$casos = array(
    "abierta" => array(
        "referencia" => "INV-INICIAL-ACUARIO-20260710-UAT-ABIERTA",
        "cantidad" => 2.5,
        "unidades" => 1,
        "cerradas" => 0,
        "abiertas" => 1,
        "diferencia" => 0.0
    ),
    "unidad_compra" => array(
        "referencia" => "INV-INICIAL-ACUARIO-20260710-UAT-CAJA",
        "cantidad" => 20.0,
        "unidades" => 0,
        "cerradas" => 0,
        "abiertas" => 0,
        "diferencia" => 20.0
    ),
    "cerrada" => array(
        "referencia" => "INV-INICIAL-ACUARIO-20260711-UAT-CERRADA",
        "cantidad" => 8.0,
        "unidades" => 2,
        "cerradas" => 2,
        "abiertas" => 0,
        "diferencia" => 0.0
    )
);

$modelo = new InventarioErp();
$resultados = array();
$fallos = array();

foreach ($casos as $nombre => $esperado) {
    $referencia = $esperado["referencia"];
    $movimiento = consultarUno($db, "SELECT id_movimiento_inventario, referencia, codigo_existencia, cantidad, origen_tipo
        FROM erp_inventario_movimientos WHERE referencia=:referencia LIMIT 1", array(":referencia" => $referencia));
    if (!$movimiento) {
        $fallos[] = "No existe movimiento para {$referencia}";
        $resultados[$nombre] = array("referencia" => $referencia, "movimiento" => null);
        continue;
    }

    $existencias = $modelo->listarExistencias(array(
        "q" => $referencia,
        "id_almacen" => 3,
        "incluir_agotadas" => 1
    ));
    $items = empty($existencias["error"]) && isset($existencias["depurar"]) ? $existencias["depurar"] : array();
    $item = count($items) ? $items[0] : array();
    if (empty($item)) {
        $fallos[] = "No existe existencia visible para {$referencia}";
    } else {
        compararDecimal($fallos, $referencia, "cantidad", $esperado["cantidad"], $item["cantidad"]);
        compararDecimal($fallos, $referencia, "cantidad_disponible", $esperado["cantidad"], $item["cantidad_disponible"]);
        compararEntero($fallos, $referencia, "unidades_total", $esperado["unidades"], $item["unidades_total"]);
        compararEntero($fallos, $referencia, "unidades_cerradas", $esperado["cerradas"], $item["unidades_cerradas"]);
        compararEntero($fallos, $referencia, "unidades_abiertas", $esperado["abiertas"], $item["unidades_abiertas"]);
        compararDecimal($fallos, $referencia, "diferencia_contenido_unidades", $esperado["diferencia"], $item["diferencia_contenido_unidades"]);
    }

    $unidades = consultarTodos($db, "SELECT codigo_unico, cantidad_base_original, cantidad_base_disponible, unidad_base,
            estatus, estado_etiqueta, estado_fisico
        FROM erp_inventario_unidades
        WHERE origen_tipo='inventario_inicial' AND origen_id=:movimiento
        ORDER BY id_inventario_unidad", array(":movimiento" => intval($movimiento["id_movimiento_inventario"])));

    if (count($unidades) !== intval($esperado["unidades"])) {
        $fallos[] = "{$referencia}: unidades esperadas {$esperado["unidades"]}, obtenidas " . count($unidades);
    }

    foreach ($unidades as $unidad) {
        if ($unidad["estado_etiqueta"] !== "pendiente_impresion") {
            $fallos[] = "{$referencia}: unidad {$unidad["codigo_unico"]} no esta pendiente_impresion";
        }
        if ($unidad["estatus"] !== "disponible") {
            $fallos[] = "{$referencia}: unidad {$unidad["codigo_unico"]} no esta disponible";
        }
    }

    $resultados[$nombre] = array(
        "referencia" => $referencia,
        "movimiento" => $movimiento,
        "existencia" => $item,
        "unidades" => $unidades
    );
}

responder(empty($fallos), $fallos, $resultados);

function consultarUno($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function consultarTodos($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function compararDecimal(&$fallos, $ref, $campo, $esperado, $actual) {
    if (abs(floatval($esperado) - floatval($actual)) > 0.0001) {
        $fallos[] = "{$ref}: {$campo} esperado {$esperado}, obtenido {$actual}";
    }
}

function compararEntero(&$fallos, $ref, $campo, $esperado, $actual) {
    if (intval($esperado) !== intval($actual)) {
        $fallos[] = "{$ref}: {$campo} esperado {$esperado}, obtenido {$actual}";
    }
}

function responder($ok, $fallos = array(), $resultados = array()) {
    echo json_encode(array(
        "ok" => $ok,
        "modo" => "inventario_inicial_real_cierre_readonly",
        "read_only" => true,
        "fallos" => $fallos,
        "resultados" => $resultados,
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_mueve_kardex" => true,
            "no_modifica_etiquetas" => true
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($ok ? 0 : 1);
}
