<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: auditar prerequisitos de Almacen/Apertura de empaques para un SKU cerrado sin escribir BD.
 * Impacto: valida Catalogo, ubicaciones, existencias y folios APE antes de ejecutar UAT real.
 * Contrato: read-only; no crea recetas, no habilita almacenes, no crea existencias y no confirma aperturas.
 */

$skuCerrado = "USA654";
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--sku=") === 0) {
        $skuCerrado = trim(substr($arg, 6), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";

class UatAlmacenAperturaDb extends CRUD {
    public function conexion() {
        return $this->getConexion();
    }
}

$db = new UatAlmacenAperturaDb();
$pdo = $db->conexion();
$bloqueos = array();

$sku = fetchOne($pdo, "SELECT s.id_sku, s.sku, s.nombre, s.id_producto_erp, s.id_unidad_base,
        u.codigo AS unidad, s.tipo_inventario, s.estatus
    FROM erp_catalogo_skus s
    LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
    WHERE s.sku=:sku
    LIMIT 1", array(":sku" => $skuCerrado));

if (!$sku) {
    $bloqueos[] = "SKU cerrado no encontrado";
}

$componentesCandidatos = array();
$paquete = null;
$componentes = array();
$existencias = array();
if ($sku) {
    $componentesCandidatos = fetchAll($pdo, "SELECT s.id_sku, s.sku, s.nombre, u.codigo AS unidad, s.estatus
        FROM erp_catalogo_skus s
        LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
        WHERE s.sku LIKE :prefijo AND s.sku<>:sku
        ORDER BY s.sku", array(":prefijo" => $skuCerrado . "-%", ":sku" => $skuCerrado));

    $paquete = fetchOne($pdo, "SELECT p.id_paquete, p.id_sku_paquete, p.tipo_paquete,
            p.modo_disponibilidad, p.permite_desarmar, p.requiere_armado_almacen,
            p.estatus
        FROM erp_catalogo_sku_paquetes p
        WHERE p.id_sku_paquete=:sku
        LIMIT 1", array(":sku" => intval($sku["id_sku"])));

    if ($paquete) {
        $componentes = fetchAll($pdo, "SELECT c.id_componente, c.id_sku_componente, sc.sku,
                sc.nombre, c.cantidad, u.codigo AS unidad, c.factor_conversion, c.orden, c.estatus
            FROM erp_catalogo_sku_paquete_componentes c
            INNER JOIN erp_catalogo_skus sc ON sc.id_sku=c.id_sku_componente
            LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=c.id_unidad
            WHERE c.id_paquete=:paquete AND c.estatus='activo'
            ORDER BY c.orden, sc.sku", array(":paquete" => intval($paquete["id_paquete"])));
    }

    $existencias = fetchAll($pdo, "SELECT exi.id_existencia_inventario, exi.codigo_existencia,
            exi.id_almacen_clave, alm.codigo_almacen, alm.almacen, exi.lote,
            exi.fecha_caducidad, exi.cantidad_disponible, exi.estatus_existencia,
            COUNT(uni.id_inventario_unidad) AS unidades_fisicas
        FROM erp_inventario_existencias exi
        LEFT JOIN erp_almacenes alm ON alm.id_almacen=exi.id_almacen_clave
        LEFT JOIN erp_inventario_unidades uni ON uni.id_existencia_inventario=exi.id_existencia_inventario
            AND uni.estatus IN ('disponible','impresa','pegada')
        WHERE exi.id_sku_erp=:sku
        GROUP BY exi.id_existencia_inventario, exi.codigo_existencia, exi.id_almacen_clave,
            alm.codigo_almacen, alm.almacen, exi.lote, exi.fecha_caducidad,
            exi.cantidad_disponible, exi.estatus_existencia
        ORDER BY alm.codigo_almacen, exi.id_existencia_inventario", array(":sku" => intval($sku["id_sku"])));
}

$ubicaciones = fetchAll($pdo, "SELECT id_almacen, codigo_almacen, almacen, tipo_almacen,
        permite_venta, permite_recepcion, permite_preparacion, permite_apertura_empaque, estatus
    FROM erp_almacenes
    WHERE estatus='activo'
    ORDER BY codigo_almacen");

$aperturas = fetchOne($pdo, "SELECT COUNT(*) AS total FROM erp_almacen_aperturas_empaque");

if ($sku && trim($sku["estatus"]) !== "activo") {
    $bloqueos[] = "SKU cerrado no esta activo";
}
if ($sku && count($componentesCandidatos) === 0) {
    $bloqueos[] = "No hay SKUs candidatos de piezas/sabores con prefijo " . $skuCerrado . "-";
}
if (!$paquete) {
    $bloqueos[] = "No existe receta de paquete para el SKU cerrado";
} elseif (intval($paquete["permite_desarmar"]) !== 1) {
    $bloqueos[] = "La receta existe pero no tiene permite_desarmar=1";
}
if ($paquete && count($componentes) === 0) {
    $bloqueos[] = "La receta no tiene componentes activos";
}
if (!hayUbicacionApertura($ubicaciones)) {
    $bloqueos[] = "Ninguna ubicacion activa tiene Apertura empaque habilitada";
}
if (count($existencias) === 0) {
    $bloqueos[] = "No existe stock cerrado del SKU para abrir";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_apertura_empaques_preflight_readonly",
    "host" => "panel.com.local",
    "sku_cerrado" => $skuCerrado,
    "resumen" => array(
        "sku_encontrado" => !empty($sku),
        "componentes_candidatos" => count($componentesCandidatos),
        "receta_existe" => !empty($paquete),
        "receta_permite_desarmar" => $paquete ? intval($paquete["permite_desarmar"]) === 1 : false,
        "componentes_activos" => count($componentes),
        "ubicaciones_con_apertura" => contarUbicacionesApertura($ubicaciones),
        "existencias_cerradas" => count($existencias),
        "folios_ape" => intval(valor($aperturas, "total", 0))
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "sku" => $sku,
    "componentes_candidatos" => $componentesCandidatos,
    "paquete" => $paquete,
    "componentes" => $componentes,
    "ubicaciones" => $ubicaciones,
    "existencias" => $existencias,
    "contrato" => array(
        "read_only" => true,
        "no_crea_recetas" => true,
        "no_habilita_almacenes" => true,
        "no_crea_existencias" => true,
        "no_confirma_aperturas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);

function fetchOne($pdo, $sql, $params = array()) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}

function fetchAll($pdo, $sql, $params = array()) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function hayUbicacionApertura($ubicaciones) {
    return contarUbicacionesApertura($ubicaciones) > 0;
}

function contarUbicacionesApertura($ubicaciones) {
    $total = 0;
    foreach (is_array($ubicaciones) ? $ubicaciones : array() as $ubicacion) {
        if (intval(valor($ubicacion, "permite_apertura_empaque", 0)) === 1) {
            $total++;
        }
    }
    return $total;
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
