<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-10-13.
 * Proposito: configurar cajas POS en regularizacion activa y habilitar politicas PINV por almacen/origen.
 * Impacto: actualiza `erp_pos_cajas` y upserta politicas en `erp_pos_politicas_venta_inventario`; no abre turnos, no cobra, no mueve kardex y no modifica existencias.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_REGULARIZACION_MULTI_ALMACEN, respaldo vigente, ids de cajas y almacenes.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 1;
$idCajas = array();
$idAlmacenesPinv = array();
$cantidadMaxima = 10.0;
$montoMaximo = 10000.0;
$motivo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = max(1, intval(substr($arg, 13)));
    } elseif (strpos($arg, "--id_cajas=") === 0) {
        $idCajas = parsearIds(substr($arg, 11));
    } elseif (strpos($arg, "--id_almacenes_pinv=") === 0) {
        $idAlmacenesPinv = parsearIds(substr($arg, strlen("--id_almacenes_pinv=")));
    } elseif (strpos($arg, "--cantidad_maxima=") === 0) {
        $cantidadMaxima = round(max(0, floatval(substr($arg, 18))), 6);
    } elseif (strpos($arg, "--monto_maximo=") === 0) {
        $montoMaximo = round(max(0, floatval(substr($arg, 15))), 6);
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if (
    $autorizar !== "VENTAS_POS_REGULARIZACION_MULTI_ALMACEN"
    || !$validacionRespaldo["ok"]
    || empty($idCajas)
    || empty($idAlmacenesPinv)
    || $idUsuario <= 0
    || $cantidadMaxima <= 0
) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se configuro POS regularizacion activa multi-almacen. Falta autorizacion, respaldo, cajas, almacenes o usuario valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_REGULARIZACION_MULTI_ALMACEN",
            "--respaldo=RESPALDO_PRODUCTIVO_VIGENTE",
            "--id_usuario=1",
            "--id_cajas=1,2",
            "--id_almacenes_pinv=3,4,5"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

if (PHP_SAPI === "cli") {
    $_SERVER["SERVER_NAME"] = "panel.com.local";
    $_SERVER["HTTP_HOST"] = "panel.com.local";
    if (empty($_SERVER["REQUEST_METHOD"])) {
        $_SERVER["REQUEST_METHOD"] = "GET";
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class ConexionRegularizacionMultiAlmacenPos extends CRUD {
    public function pdo() {
        return $this->getConexion();
    }
}

$conexion = new ConexionRegularizacionMultiAlmacenPos();
$db = $conexion->pdo();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "conexion_no_disponible",
        "mensaje" => "No fue posible conectar a la base de datos para configurar POS."
    ));
}

$tablasRequeridas = array("erp_pos_cajas", "erp_almacenes", "erp_pos_politicas_venta_inventario");
foreach ($tablasRequeridas as $tabla) {
    if (!tablaExisteEnConexion($db, $tabla)) {
        responder(array(
            "ok" => false,
            "modo" => "schema_pendiente",
            "mensaje" => "Falta tabla requerida para configurar regularizacion POS en la conexion activa.",
            "tabla" => $tabla
        ));
    }
}

try {
    $db->beginTransaction();

    $cajasAntes = seleccionarCajas($db, $idCajas);
    if (count($cajasAntes) !== count($idCajas)) {
        throw new Exception("Una o mas cajas solicitadas no existen.");
    }
    $almacenes = seleccionarAlmacenes($db, $idAlmacenesPinv);
    if (count($almacenes) !== count($idAlmacenesPinv)) {
        throw new Exception("Uno o mas almacenes solicitados no existen o estan inactivos.");
    }

    $motivoCaja = $motivo !== ""
        ? "[Regularizacion POS multi-almacen] " . $motivo
        : "[Regularizacion POS multi-almacen] Cambio autorizado";

    $stmtCaja = $db->prepare("UPDATE erp_pos_cajas
        SET afectar_inventario=1,
            modo_operacion_inventario='regularizacion_activa',
            generar_alertas_inventario=1,
            observaciones=TRIM(CONCAT(COALESCE(observaciones, ''), '\n', :motivo)),
            fecha_actualizacion=NOW()
        WHERE id_caja=:caja");
    foreach ($idCajas as $idCaja) {
        $stmtCaja->execute(array(
            ":motivo" => $motivoCaja,
            ":caja" => $idCaja
        ));
    }

    $politicas = array();
    foreach ($almacenes as $almacen) {
        $politicas[] = upsertPoliticaPinvAlmacen(
            $db,
            $almacen,
            $idUsuario,
            $cantidadMaxima,
            $montoMaximo,
            $motivo
        );
    }

    $cajasDespues = seleccionarCajas($db, $idCajas);
    $db->commit();

    responder(array(
        "ok" => true,
        "modo" => "regularizacion_activa_multi_almacen_configurada",
        "respaldo_ref" => $respaldo,
        "id_usuario" => $idUsuario,
        "cajas_antes" => $cajasAntes,
        "cajas_despues" => $cajasDespues,
        "politicas_pinv" => $politicas,
        "nota_operativa" => "La bodega puede recibir politica PINV como origen de partida POS aunque no sea caja vendible. La caja cobra; el almacen de origen descuenta o genera pendiente."
    ));
} catch (Exception $e) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "error",
        "mensaje" => "No se pudo configurar regularizacion POS multi-almacen.",
        "error" => $e->getMessage()
    ));
}

function parsearIds($valor) {
    $ids = array();
    foreach (explode(",", trim($valor, "\"' ")) as $parte) {
        $id = intval(trim($parte));
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function tablaExisteEnConexion($db, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        return false;
    }
    $stmt = $db->prepare("SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :tabla
        LIMIT 1");
    $stmt->execute(array(":tabla" => $tabla));
    return $stmt->fetchColumn() !== false;
}

function seleccionarCajas($db, $ids) {
    if (empty($ids)) {
        return array();
    }
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $db->prepare("SELECT id_caja, codigo, nombre, id_almacen, afectar_inventario,
            modo_operacion_inventario, generar_alertas_inventario, estatus
        FROM erp_pos_cajas
        WHERE id_caja IN ($placeholders)
        ORDER BY id_caja ASC");
    $stmt->execute($ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function seleccionarAlmacenes($db, $ids) {
    if (empty($ids)) {
        return array();
    }
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $db->prepare("SELECT id_almacen, codigo_almacen, almacen, tipo_almacen,
            permite_venta, permite_preparacion, estatus
        FROM erp_almacenes
        WHERE id_almacen IN ($placeholders)
          AND COALESCE(estatus, 'activo')='activo'
        ORDER BY id_almacen ASC");
    $stmt->execute($ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function upsertPoliticaPinvAlmacen($db, $almacen, $idUsuario, $cantidadMaxima, $montoMaximo, $motivo) {
    $idAlmacen = intval($almacen["id_almacen"]);
    $codigo = "PINV-A" . $idAlmacen . "-GLOBAL-POS";
    $nombre = "Inventario pendiente POS " . trim((string) $almacen["almacen"]);
    $motivoFinal = $motivo !== ""
        ? $motivo
        : "Politica global POS inventario pendiente para almacen " . $idAlmacen;
    $snapshot = array(
        "origen" => "pos_regularizacion_multi_almacen",
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "almacen" => $almacen,
        "alcance" => "almacen",
        "canal" => "pos",
        "cantidad_maxima_pendiente" => $cantidadMaxima,
        "monto_maximo" => $montoMaximo,
        "motivo" => $motivoFinal,
        "fecha" => date("Y-m-d H:i:s")
    );

    $stmt = $db->prepare("SELECT id_politica_inventario_pos
        FROM erp_pos_politicas_venta_inventario
        WHERE codigo=:codigo
        LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $idPolitica = intval($stmt->fetchColumn());

    if ($idPolitica > 0) {
        $stmt = $db->prepare("UPDATE erp_pos_politicas_venta_inventario
            SET nombre=:nombre, id_almacen=:almacen, id_sku_erp=NULL, canal='pos',
                permite_inventario_pendiente=1, cantidad_maxima_pendiente=:cantidad, monto_maximo=:monto,
                requiere_autorizacion=1, permiso_requerido='ventas.pos.inventario_pendiente.autorizar',
                motivo_obligatorio=1, estatus='activa', autorizado_por=:usuario, fecha_autorizacion=NOW(),
                observaciones=:motivo, datos_snapshot=:snapshot, fecha_actualizacion=NOW()
            WHERE id_politica_inventario_pos=:id");
        $stmt->execute(array(
            ":nombre" => $nombre,
            ":almacen" => $idAlmacen,
            ":cantidad" => $cantidadMaxima,
            ":monto" => $montoMaximo,
            ":usuario" => $idUsuario,
            ":motivo" => $motivoFinal,
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ":id" => $idPolitica
        ));
        $accion = "actualizada";
    } else {
        $stmt = $db->prepare("INSERT INTO erp_pos_politicas_venta_inventario
            (codigo, nombre, id_almacen, id_sku_erp, canal, permite_inventario_pendiente,
             cantidad_maxima_pendiente, monto_maximo, requiere_autorizacion, permiso_requerido,
             motivo_obligatorio, estatus, creado_por, autorizado_por, fecha_autorizacion, observaciones, datos_snapshot)
            VALUES
            (:codigo, :nombre, :almacen, NULL, 'pos', 1,
             :cantidad, :monto, 1, 'ventas.pos.inventario_pendiente.autorizar',
             1, 'activa', :usuario, :usuario_autoriza, NOW(), :motivo, :snapshot)");
        $stmt->execute(array(
            ":codigo" => $codigo,
            ":nombre" => $nombre,
            ":almacen" => $idAlmacen,
            ":cantidad" => $cantidadMaxima,
            ":monto" => $montoMaximo,
            ":usuario" => $idUsuario,
            ":usuario_autoriza" => $idUsuario,
            ":motivo" => $motivoFinal,
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ));
        $idPolitica = intval($db->lastInsertId());
        $accion = "creada";
    }

    return array(
        "accion" => $accion,
        "id_politica_inventario_pos" => $idPolitica,
        "codigo" => $codigo,
        "id_almacen" => $idAlmacen,
        "cantidad_maxima_pendiente" => $cantidadMaxima,
        "monto_maximo" => $montoMaximo
    );
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

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
