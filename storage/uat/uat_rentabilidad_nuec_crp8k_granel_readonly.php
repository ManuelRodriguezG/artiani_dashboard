<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-24
 * Proposito: auditar flujo de costo para NUEC-CRP8K-GRANEL desde incidencia de Catalogo y resolutor de Rentabilidad.
 * Impacto: diagnostica SKUs internos de apertura/granel sin escribir Catalogo, Inventario, Listas ni Ventas.
 * Contrato: solo lectura; no actualiza incidencias ni recetas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

class UatNuecCrp8kReader extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$lector = new UatNuecCrp8kReader();
$db = $lector->db();
$modelo = new RentabilidadErp();
$skuCodigo = isset($argv[1]) && trim($argv[1]) !== "" ? trim($argv[1]) : "NUEC-CRP8K-GRANEL";

$skusRelacionados = consultarTodos($db, "SELECT s.id_sku, s.sku, s.nombre, s.id_producto_erp, p.nombre producto,
        s.id_unidad_base, u.codigo unidad, s.factor_unidad_base, s.tipo_inventario, s.estatus
    FROM erp_catalogo_skus s
    LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
    WHERE s.sku LIKE :q OR s.nombre LIKE :q OR p.nombre LIKE :q
    ORDER BY s.sku", array(":q" => "%NUEC-CRP%"));

$sku = consultarUno($db, "SELECT s.id_sku, s.sku, s.nombre, s.id_producto_erp, p.nombre producto,
        s.id_unidad_base, u.codigo unidad, s.factor_unidad_base, s.tipo_inventario, s.estatus
    FROM erp_catalogo_skus s
    LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
    WHERE s.sku=:sku
    LIMIT 1", array(":sku" => $skuCodigo));

$idSku = $sku ? intval($sku["id_sku"]) : 0;
$incidencias = consultarTodos($db, "SELECT id_notificacion, tipo, estatus, area_responsable, titulo, payload_json,
        fecha_registro, fecha_actualizacion, fecha_resolucion
    FROM erp_notificaciones
    WHERE tipo='catalogo_sku_derivado_costo_pendiente'
      AND payload_json LIKE :sku
    ORDER BY id_notificacion DESC", array(":sku" => "%" . $skuCodigo . "%"));

$aperturasCatalogo = $idSku > 0 ? consultarTodos($db, "SELECT ae.*, ori.sku sku_origen, ori.nombre nombre_origen,
        ori.factor_unidad_base factor_origen
    FROM erp_catalogo_sku_aperturas_empaque ae
    LEFT JOIN erp_catalogo_skus ori ON ori.id_sku=ae.id_sku_origen
    WHERE ae.id_sku_destino=:sku
    ORDER BY ae.id_apertura_empaque DESC", array(":sku" => $idSku)) : array();

$presentaciones = $idSku > 0 ? consultarTodos($db, "SELECT pr.*, base.sku sku_origen, base.factor_unidad_base factor_origen
    FROM erp_catalogo_sku_presentaciones pr
    LEFT JOIN erp_catalogo_skus base ON base.id_sku=pr.id_sku_base
    WHERE pr.id_sku_presentacion=:sku
    ORDER BY pr.id_sku_presentacion_regla DESC", array(":sku" => $idSku)) : array();

$paquetes = $idSku > 0 ? consultarTodos($db, "SELECT * FROM erp_catalogo_sku_paquetes WHERE id_sku_paquete=:sku", array(":sku" => $idSku)) : array();

$resoluciones = $idSku > 0 ? array(
    "auto" => $modelo->resolverCostoVigenteSku($idSku, array()),
    "apertura_empaque" => $modelo->resolverCostoVigenteSku($idSku, array("tipo" => "apertura_empaque")),
    "granel" => $modelo->resolverCostoVigenteSku($idSku, array("tipo" => "granel"))
) : array();

$preResolucionIncidencia = null;
if (!empty($incidencias)) {
    $preResolucionIncidencia = $modelo->preResolverIncidenciaCostoDerivado(array(
        "id_notificacion" => intval($incidencias[0]["id_notificacion"])
    ));
}

$fallas = array();
$resGranel = isset($resoluciones["granel"]["depurar"]) ? $resoluciones["granel"]["depurar"] : array();
$resAuto = isset($resoluciones["auto"]["depurar"]) ? $resoluciones["auto"]["depurar"] : array();
$pre = isset($preResolucionIncidencia["depurar"]) ? $preResolucionIncidencia["depurar"] : array();
if (!$sku) {
    $fallas[] = array("id" => "COST-NUEC-CRP8K-001", "mensaje" => "SKU granel no encontrado");
}
if ($sku && empty($aperturasCatalogo)) {
    $fallas[] = array("id" => "COST-NUEC-CRP8K-002", "mensaje" => "SKU granel sin receta de apertura en Catalogo");
}
if ($sku && abs(floatval(isset($resGranel["costo"]) ? $resGranel["costo"] : 0) - 111.875) > 0.01) {
    $fallas[] = array("id" => "COST-NUEC-CRP8K-003", "mensaje" => "Modo granel debe resolver costo 895 / 8 = 111.875");
}
if ($sku && (isset($resGranel["fuente"]) ? $resGranel["fuente"] : "") !== "derivado_apertura_receta") {
    $fallas[] = array("id" => "COST-NUEC-CRP8K-004", "mensaje" => "Modo granel debe usar fuente derivado_apertura_receta");
}
if ($sku && abs(floatval(isset($resAuto["costo"]) ? $resAuto["costo"] : 0) - floatval(isset($resGranel["costo"]) ? $resGranel["costo"] : 0)) > 0.01) {
    $fallas[] = array("id" => "COST-NUEC-CRP8K-005", "mensaje" => "Modo auto y modo granel deben coincidir para apertura/granel derivado");
}
if ($preResolucionIncidencia && (isset($pre["estatus_propuesto"]) ? $pre["estatus_propuesto"] : "") !== "resuelta") {
    $fallas[] = array("id" => "COST-NUEC-CRP8K-006", "mensaje" => "La incidencia debe quedar resoluble en dry-run");
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_nuec_crp8k_granel_readonly",
    "sku_buscado" => $skuCodigo,
    "fallas" => $fallas,
    "sku" => $sku,
    "skus_relacionados" => $skusRelacionados,
    "incidencias" => decodificarPayloads($incidencias),
    "aperturas_catalogo" => $aperturasCatalogo,
    "presentaciones" => $presentaciones,
    "paquetes" => $paquetes,
    "resoluciones" => $resoluciones,
    "pre_resolucion_incidencia" => $preResolucionIncidencia,
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_modifica_listas" => true,
        "no_toca_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function consultarUno($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function consultarTodos($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function decodificarPayloads($rows) {
    foreach ($rows as &$row) {
        $payload = json_decode(isset($row["payload_json"]) ? $row["payload_json"] : "{}", true);
        $row["payload"] = is_array($payload) ? $payload : array();
        unset($row["payload_json"]);
    }
    return $rows;
}
