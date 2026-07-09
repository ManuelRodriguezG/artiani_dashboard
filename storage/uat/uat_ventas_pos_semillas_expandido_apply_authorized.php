<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: sembrar datos UAT expandidos de POS solo con autorizacion explicita.
 * Impacto: crea cliente UAT, identificador, lista de precio, relacion cliente-lista y politica de apartado.
 * Contrato: BLOQUEADO por defecto; no toca inventario, ventas, pagos, caja ni atenciones.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAlmacen = 5;
$idSku = 1760;
$precio = 295;
$telefono = "5550000000";
foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = round(floatval(trim(substr($arg, 9), "\"' ")), 6);
    } elseif (strpos($arg, "--telefono=") === 0) {
        $telefono = trim(substr($arg, 11), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_SEED_EXPANDIDO" || !$validacionRespaldo["ok"] || $idUsuario <= 0) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecutaron semillas expandidas. Falta autorizacion, respaldo valido o usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_SEED_EXPANDIDO",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "reglas" => array(
            "Ejecutar solo despues de aplicar DDL expandido POS.",
            "No crea ventas, pagos ni movimientos de inventario.",
            "Validar cliente/precio y apartado dry-run despues de sembrar."
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosSemillasExpandidoApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosSemillasExpandidoApplyDb())->db();
$tablas = array(
    "erp_ventas_politicas_apartado",
    "erp_clientes",
    "erp_clientes_identificadores",
    "erp_listas_precios",
    "erp_listas_precios_detalle",
    "erp_clientes_listas_precios"
);
$faltantes = tablasFaltantes($db, $tablas);
if (!empty($faltantes)) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta aplicar DDL expandido antes de sembrar.",
        "tablas_faltantes" => $faltantes
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$sku = consultarSku($db, $idSku);
if (!$sku) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "SKU UAT no encontrado.",
        "id_sku" => $idSku
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$normalizado = normalizarIdentificadorCliente($telefono);
$ejecutadas = array();

try {
    $db->beginTransaction();

    ejecutar($db, $ejecutadas, "politica_apartado", "INSERT INTO erp_ventas_politicas_apartado
        (codigo, nombre, porcentaje_anticipo_minimo, monto_anticipo_minimo, dias_vigencia, permite_abonos, permite_entrega_sin_liquidar, politica_cancelacion, estatus, fecha_actualizacion)
        VALUES ('POS_APARTADO_UAT', 'Politica apartado UAT', 0.200000, 0, 30, 1, 0, 'UAT: cancelar libera reserva; penalizacion pendiente de politica final.', 'activa', CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), porcentaje_anticipo_minimo=VALUES(porcentaje_anticipo_minimo), dias_vigencia=VALUES(dias_vigencia), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP");

    ejecutar($db, $ejecutadas, "cliente", "INSERT INTO erp_clientes
        (codigo_cliente, tipo_cliente, nombre_publico, estatus, calidad_datos, creado_desde, id_sucursal_alta, creado_por, fecha_actualizacion)
        VALUES ('CL-UAT-POS-001', 'persona', 'Cliente UAT POS', 'activo', 'express', 'pos', " . intval($idAlmacen) . ", " . intval($idUsuario) . ", CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE nombre_publico=VALUES(nombre_publico), estatus=VALUES(estatus), calidad_datos=VALUES(calidad_datos), id_sucursal_alta=VALUES(id_sucursal_alta), fecha_actualizacion=CURRENT_TIMESTAMP");

    $idCliente = idPorCampo($db, "erp_clientes", "id_cliente", "codigo_cliente", "CL-UAT-POS-001");

    ejecutar($db, $ejecutadas, "cliente_identificador", "INSERT INTO erp_clientes_identificadores
        (id_cliente, tipo, valor, valor_normalizado, principal, estatus)
        SELECT " . intval($idCliente) . ", 'telefono', " . sqlQuote($telefono) . ", " . sqlQuote($normalizado) . ", 1, 'activo'
        WHERE NOT EXISTS (
            SELECT 1 FROM erp_clientes_identificadores
            WHERE tipo='telefono' AND valor_normalizado=" . sqlQuote($normalizado) . " AND estatus='activo'
        )");

    ejecutar($db, $ejecutadas, "lista_precio", "INSERT INTO erp_listas_precios
        (codigo, nombre, canal, id_almacen, prioridad, estatus, observaciones, fecha_actualizacion)
        VALUES ('LP-UAT-POS', 'Lista UAT POS', 'pos', " . intval($idAlmacen) . ", 10, 'activa', 'Lista UAT para validar resolutor POS.', CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), canal=VALUES(canal), id_almacen=VALUES(id_almacen), prioridad=VALUES(prioridad), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP");

    $idLista = idPorCampo($db, "erp_listas_precios", "id_lista_precio", "codigo", "LP-UAT-POS");

    ejecutar($db, $ejecutadas, "lista_precio_detalle", "INSERT INTO erp_listas_precios_detalle
        (id_lista_precio, id_sku, id_producto_erp, precio, moneda, estatus)
        SELECT " . intval($idLista) . ", " . intval($idSku) . ", id_producto_erp, " . formatoDecimal($precio) . ", 'MXN', 'activo'
        FROM erp_catalogo_skus WHERE id_sku=" . intval($idSku) . "
        AND NOT EXISTS (
            SELECT 1 FROM erp_listas_precios_detalle
            WHERE id_lista_precio=" . intval($idLista) . " AND id_sku=" . intval($idSku) . " AND estatus='activo'
        )");

    ejecutar($db, $ejecutadas, "cliente_lista", "INSERT INTO erp_clientes_listas_precios
        (id_cliente, id_lista_precio, prioridad, estatus, creado_por, observaciones)
        SELECT " . intval($idCliente) . ", " . intval($idLista) . ", 1, 'activo', " . intval($idUsuario) . ", 'UAT POS'
        WHERE NOT EXISTS (
            SELECT 1 FROM erp_clientes_listas_precios
            WHERE id_cliente=" . intval($idCliente) . " AND id_lista_precio=" . intval($idLista) . " AND estatus='activo'
        )");

    $db->commit();
    echo json_encode(array(
        "ok" => true,
        "modo" => "semillas_expandido_ejecutadas",
        "respaldo_ref" => $respaldo,
        "id_cliente" => $idCliente,
        "id_lista_precio" => $idLista,
        "id_sku" => $idSku,
        "precio" => $precio,
        "ejecutadas" => $ejecutadas,
        "siguiente_paso" => "Ejecutar cliente/precio dry-run y validar que resuelve lista_cliente."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage(),
        "ejecutadas_antes_error" => $ejecutadas
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function ejecutar($db, &$ejecutadas, $tipo, $sql) {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $ejecutadas[] = array("tipo" => $tipo, "filas_afectadas" => $stmt->rowCount());
}

function idPorCampo($db, $tabla, $campoId, $campoFiltro, $valor) {
    $stmt = $db->prepare("SELECT `$campoId` FROM `$tabla` WHERE `$campoFiltro`=:valor LIMIT 1");
    $stmt->execute(array(":valor" => $valor));
    return intval($stmt->fetchColumn());
}

function validarRespaldo($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "referencia_presente" => $okReferencia,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function tablasFaltantes($db, $tablas) {
    $faltantes = array();
    foreach ($tablas as $tabla) {
        $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
        $stmt->execute(array(":tabla" => $tabla));
        if (!$stmt->fetchColumn()) {
            $faltantes[] = $tabla;
        }
    }
    return $faltantes;
}

function consultarSku($db, $idSku) {
    $stmt = $db->prepare("SELECT id_sku, sku FROM erp_catalogo_skus WHERE id_sku=:sku LIMIT 1");
    $stmt->execute(array(":sku" => $idSku));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function normalizarIdentificadorCliente($valor) {
    $soloDigitos = preg_replace('/\D+/', '', (string) $valor);
    return $soloDigitos !== "" ? $soloDigitos : strtolower(trim((string) $valor));
}

function sqlQuote($valor) {
    return "'" . str_replace("'", "''", (string) $valor) . "'";
}

function formatoDecimal($valor) {
    return number_format(floatval($valor), 6, ".", "");
}
