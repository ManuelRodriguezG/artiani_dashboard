<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: diagnosticar por codigo/SKU por que POS permite o bloquea inventario pendiente.
 * Impacto: solo lectura sobre catalogo, politicas POS e inventario; no crea ventas ni alertas.
 * Contrato: read-only; no escribe BD, no mueve kardex, no abre/cierra caja.
 */

$codigo = "spf-600";
$idAlmacen = 5;
$cantidad = 1;

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--codigo=") === 0) {
        $codigo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(str_replace(",", ".", trim(substr($arg, 11), "\"' ")));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatPosInventarioPendienteSkuDiagnosticoDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatPosInventarioPendienteSkuDiagnosticoDb())->db();
$ventas = new VentasErp();

$skus = consultarTodos($db, "SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre, s.tipo_inventario,
        s.permite_venta_sin_existencia, s.estatus, p.nombre producto, p.estatus producto_estatus,
        r.controla_inventario, r.permite_existencia_negativa, r.permite_venta_fraccionaria,
        r.unidad_venta_label, r.stock_minimo
    FROM erp_catalogo_skus s
    LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
    WHERE LOWER(s.sku)=LOWER(:codigo)
       OR s.id_sku IN (
            SELECT c.id_sku
            FROM erp_catalogo_sku_codigos c
            WHERE LOWER(c.codigo)=LOWER(:codigo2)
       )
    ORDER BY s.id_sku
    LIMIT 20", array(":codigo" => $codigo, ":codigo2" => $codigo));

$detalle = array();
foreach ($skus as $sku) {
    $idSku = intval($sku["id_sku"]);
    $columnasPolitica = columnasExistentes($db, "erp_pos_politicas_venta_inventario", array(
        "id_politica_inventario_pos",
        "codigo",
        "nombre",
        "id_almacen",
        "id_sku_erp",
        "canal",
        "permite_inventario_pendiente",
        "cantidad_maxima_pendiente",
        "monto_maximo",
        "requiere_autorizacion",
        "permiso_requerido",
        "prioridad",
        "fecha_inicio",
        "fecha_fin",
        "estatus"
    ));
    $selectPolitica = implode(", ", array_map(function ($columna) {
        return "`" . str_replace("`", "", $columna) . "`";
    }, $columnasPolitica));

    $politicas = consultarTodos($db, "SELECT " . $selectPolitica . "
        FROM erp_pos_politicas_venta_inventario
        WHERE id_sku_erp=:sku
        ORDER BY id_politica_inventario_pos DESC
        LIMIT 20", array(":sku" => $idSku));

    $politicasAlmacen = consultarTodos($db, "SELECT " . $selectPolitica . "
        FROM erp_pos_politicas_venta_inventario
        WHERE id_sku_erp=:sku AND id_almacen=:almacen
        ORDER BY id_politica_inventario_pos DESC
        LIMIT 20", array(":sku" => $idSku, ":almacen" => $idAlmacen));

    $existencias = consultarTodos($db, "SELECT id_almacen_clave id_almacen, SUM(cantidad) cantidad,
            SUM(cantidad_disponible) disponible, SUM(cantidad_apartada) apartada,
            GROUP_CONCAT(DISTINCT estatus_existencia ORDER BY estatus_existencia SEPARATOR ',') estatus
        FROM erp_inventario_existencias
        WHERE id_sku_erp=:sku
        GROUP BY id_almacen_clave
        ORDER BY id_almacen_clave", array(":sku" => $idSku));

    $dryRun = $ventas->ventaInventarioPendienteDryRun(array(
        "id_usuario" => 1,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "canal" => "pos",
        "motivo" => "Diagnostico read-only " . $codigo
    ));

    $detalle[] = array(
        "sku" => $sku,
        "existencias" => $existencias,
        "politicas_todas_para_sku" => $politicas,
        "politicas_en_almacen_consultado" => $politicasAlmacen,
        "dry_run" => array(
            "error" => isset($dryRun["error"]) ? $dryRun["error"] : null,
            "tipo" => isset($dryRun["tipo"]) ? $dryRun["tipo"] : null,
            "mensaje" => isset($dryRun["mensaje"]) ? $dryRun["mensaje"] : null,
            "estado" => isset($dryRun["depurar"]["estado"]) ? $dryRun["depurar"]["estado"] : null,
            "bloqueos" => isset($dryRun["depurar"]["bloqueos"]) ? $dryRun["depurar"]["bloqueos"] : array(),
            "advertencias" => isset($dryRun["depurar"]["advertencias"]) ? $dryRun["depurar"]["advertencias"] : array()
        )
    );
}

responder(array(
    "ok" => true,
    "modo" => "pos_inventario_pendiente_sku_diagnostico_readonly",
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "codigo" => $codigo,
        "id_almacen" => $idAlmacen,
        "cantidad" => $cantidad
    ),
    "skus_encontrados" => count($skus),
    "detalle" => $detalle,
    "lectura_operativa" => array(
        "catalogo_permite_existencia_negativa" => "Es regla informativa/base del SKU.",
        "pos_requiere_politica_activa" => "Para vender faltante en POS se requiere politica activa por almacen/SKU/canal en erp_pos_politicas_venta_inventario.",
        "alerta_inventario" => "Se genera al cobrar por flujo real de inventario pendiente, no al agregar al carrito."
    )
));

function consultarTodos($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function columnasExistentes($db, $tabla, $columnas) {
    $existentes = array();
    foreach ($columnas as $columna) {
        $stmt = $db->prepare("SHOW COLUMNS FROM `" . str_replace("`", "", $tabla) . "` LIKE :columna");
        $stmt->execute(array(":columna" => $columna));
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $existentes[] = $columna;
        }
    }
    return $existentes;
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}
