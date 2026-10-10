<?php

if (empty($_SERVER["SERVER_NAME"])) {
    $_SERVER["SERVER_NAME"] = "panel.com.local";
}
if (empty($_SERVER["HTTP_HOST"])) {
    $_SERVER["HTTP_HOST"] = "panel.com.local";
}

/**
 * Documentacion IA: Codex GPT-5, 2026-10-10.
 * Proposito: sembrar politica POS para precio conjunto/paquete manual con autorizacion explicita.
 * Impacto: habilita registro de folios de excepcion comercial tipo `precio_conjunto`; no crea ventas, caja ni inventario.
 * Contrato: BLOQUEADO por defecto; requiere token, respaldo, usuario, almacen y limites comerciales.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAlmacen = 0;
$descuentoMaxPorcentaje = 1;
$descuentoMaxMonto = 999999;
$motivo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--descuento_max_porcentaje=") === 0) {
        $descuentoMaxPorcentaje = floatval(trim(substr($arg, 27), "\"' "));
    } elseif (strpos($arg, "--descuento_max_monto=") === 0) {
        $descuentoMaxMonto = floatval(trim(substr($arg, 22), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_PRECIO_CONJUNTO_POLITICA" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idAlmacen <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se sembro politica POS de precio conjunto. Falta autorizacion, respaldo, usuario o almacen.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_PRECIO_CONJUNTO_POLITICA",
            "--respaldo=RUTA_O_REFERENCIA_RESPALDO",
            "--id_usuario=ID",
            "--id_almacen=ID_ALMACEN"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosPrecioConjuntoPoliticaDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosPrecioConjuntoPoliticaDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se pudo conectar a MySQL desde PHP CLI. Verifica que MariaDB/MySQL este levantado en XAMPP y que el host canonico sea panel.com.local."
    ));
}
$faltantes = tablasFaltantes($db, array("erp_ventas_politicas_comerciales", "erp_ventas_excepciones_comerciales"));
if (!empty($faltantes)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta aplicar DDL de excepciones comerciales antes de sembrar politica precio conjunto.",
        "tablas_faltantes" => $faltantes
    ));
}

$codigo = "POS_PRECIO_CONJUNTO_UAT_A" . $idAlmacen;
$nombre = "Precio conjunto POS UAT almacen " . $idAlmacen;
$observaciones = $motivo !== "" ? $motivo : "UAT: habilita paquete/precio conjunto manual con autorizacion de supervisor.";

try {
    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO erp_ventas_politicas_comerciales
        (codigo, nombre, tipo_excepcion, canal, id_almacen, descuento_max_porcentaje,
         descuento_max_monto, margen_minimo_porcentaje, requiere_autorizacion,
         permiso_requerido, estatus, creado_por, observaciones, fecha_actualizacion)
        VALUES
        (:codigo, :nombre, 'precio_conjunto', 'pos', :almacen, :descuento_porcentaje,
         :descuento_monto, 0, 1,
         'ventas.autorizar_excepcion_comercial', 'activa', :usuario, :observaciones, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            nombre=VALUES(nombre),
            tipo_excepcion=VALUES(tipo_excepcion),
            canal=VALUES(canal),
            id_almacen=VALUES(id_almacen),
            descuento_max_porcentaje=VALUES(descuento_max_porcentaje),
            descuento_max_monto=VALUES(descuento_max_monto),
            margen_minimo_porcentaje=VALUES(margen_minimo_porcentaje),
            requiere_autorizacion=VALUES(requiere_autorizacion),
            permiso_requerido=VALUES(permiso_requerido),
            estatus='activa',
            observaciones=VALUES(observaciones),
            fecha_actualizacion=CURRENT_TIMESTAMP");
    $stmt->execute(array(
        ":codigo" => $codigo,
        ":nombre" => $nombre,
        ":almacen" => $idAlmacen,
        ":descuento_porcentaje" => $descuentoMaxPorcentaje,
        ":descuento_monto" => $descuentoMaxMonto,
        ":usuario" => $idUsuario,
        ":observaciones" => $observaciones
    ));

    $consulta = $db->prepare("SELECT id_politica_comercial, codigo, nombre, tipo_excepcion, canal, id_almacen,
            descuento_max_porcentaje, descuento_max_monto, requiere_autorizacion, permiso_requerido, estatus
        FROM erp_ventas_politicas_comerciales
        WHERE codigo=:codigo
        LIMIT 1");
    $consulta->execute(array(":codigo" => $codigo));
    $politica = $consulta->fetch(PDO::FETCH_ASSOC);
    $db->commit();

    responder(array(
        "ok" => true,
        "modo" => "precio_conjunto_politica_sembrada",
        "respaldo_ref" => $respaldo,
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "filas_afectadas" => $stmt->rowCount(),
        "politica" => $politica,
        "siguiente_paso" => "Validar en POS: marcar partidas, capturar total conjunto, registrar folio, aplicar folio y cobrar."
    ));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage()
    ));
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
    return array("ok" => $okReferencia && $okRuta, "referencia_presente" => $okReferencia, "parece_ruta_local" => $esRutaLocal, "archivo_existe" => $esRutaLocal ? $existe : null, "archivo_legible" => $esRutaLocal ? $legible : null, "tamano_bytes" => $tamano);
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

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
