<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: auditar compatibilidad del flujo POS base contra tablas existentes de catalogo/inventario.
 * Impacto: detecta columnas faltantes antes de autorizar DDL base y venta UAT real.
 * Contrato: read-only; no crea tablas, no modifica esquema y no mueve inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosBaseCompatDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosBaseCompatDb())->db();
$requeridas = array(
    "erp_catalogo_skus" => array("id_sku", "id_producto_erp", "sku", "nombre", "tipo_inventario", "id_unidad_base", "estatus"),
    "erp_catalogo_productos" => array("id_producto_erp", "nombre", "estatus"),
    "erp_catalogo_sku_reglas_inventario" => array("id_sku", "controla_inventario", "unidad_venta_label"),
    "erp_catalogo_unidades" => array("id_unidad", "abreviatura", "codigo"),
    "erp_inventario_existencias" => array(
        "id_existencia_inventario",
        "id_almacen_clave",
        "cantidad",
        "cantidad_disponible",
        "estatus_existencia",
        "fecha_actualizacion",
        "costo_promedio",
        "codigo_existencia",
        "lote",
        "fecha_caducidad",
        "ubicacion_id",
        "ubicacion",
        "ultimo_movimiento_id"
    ),
    "erp_inventario_unidades" => array(
        "id_inventario_unidad",
        "id_existencia_inventario",
        "id_almacen",
        "estatus",
        "estado_fisico",
        "cantidad_base_disponible",
        "fecha_actualizacion"
    ),
    "erp_inventario_movimientos" => array(
        "id_movimiento_inventario",
        "id_producto",
        "id_sku_erp",
        "id_almacen",
        "tipo_movimiento",
        "origen_tipo",
        "origen_id",
        "id_existencia_inventario",
        "codigo_existencia",
        "lote",
        "fecha_caducidad",
        "ubicacion_id",
        "ubicacion",
        "cantidad",
        "costo_unitario",
        "costo_total",
        "existencia_anterior",
        "existencia_nueva",
        "referencia",
        "observaciones"
    )
);

$resultado = array();
$faltantes = array();
foreach ($requeridas as $tabla => $columnas) {
    $existe = tablaExiste($db, $tabla);
    $columnasExistentes = $existe ? columnasTabla($db, $tabla) : array();
    $faltanColumnas = array_values(array_diff($columnas, $columnasExistentes));
    $resultado[] = array(
        "tabla" => $tabla,
        "existe" => $existe,
        "faltan_columnas" => $faltanColumnas
    );
    if (!$existe) {
        $faltantes[] = "Falta tabla " . $tabla;
    }
    foreach ($faltanColumnas as $columna) {
        $faltantes[] = "Falta columna " . $tabla . "." . $columna;
    }
}

echo json_encode(array(
    "ok" => empty($faltantes),
    "modo" => "read-only",
    "alcance" => "base",
    "resultado" => $resultado,
    "bloqueos" => $faltantes,
    "siguiente_paso" => empty($faltantes)
        ? "Compatibilidad catalogo/inventario lista para DDL base POS."
        : "Resolver faltantes antes de venta POS real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function columnasTabla($db, $tabla) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `" . str_replace("`", "", $tabla) . "`");
    $stmt->execute();
    $columnas = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $columnas[] = $fila["Field"];
    }
    return $columnas;
}
