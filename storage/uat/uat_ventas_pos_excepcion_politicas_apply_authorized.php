<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: sembrar politicas UAT de excepcion comercial POS solo con autorizacion explicita.
 * Impacto: habilita reglas formales para precio manual/descuentos; no crea ventas ni excepciones aplicadas.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_EXCEPCION_POLITICAS, respaldo e id_usuario.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAlmacen = 5;
foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_EXCEPCION_POLITICAS" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idAlmacen <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se sembraron politicas de excepcion comercial. Falta autorizacion, respaldo, usuario o almacen.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_EXCEPCION_POLITICAS",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--id_almacen=ID_ALMACEN"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosExcepcionPoliticasApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosExcepcionPoliticasApplyDb())->db();
$faltantes = tablasFaltantes($db, array("erp_ventas_politicas_comerciales", "erp_ventas_excepciones_comerciales"));
if (!empty($faltantes)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta aplicar DDL de excepciones comerciales antes de sembrar politicas.",
        "tablas_faltantes" => $faltantes
    ));
}

$politicas = politicasUat($idAlmacen, $idUsuario);
$ejecutadas = array();

try {
    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO erp_ventas_politicas_comerciales
        (codigo, nombre, tipo_excepcion, canal, id_almacen, descuento_max_porcentaje,
         descuento_max_monto, margen_minimo_porcentaje, requiere_autorizacion,
         permiso_requerido, estatus, creado_por, observaciones, fecha_actualizacion)
        VALUES
        (:codigo, :nombre, :tipo, :canal, :almacen, :descuento_porcentaje,
         :descuento_monto, :margen_minimo, :requiere_autorizacion,
         :permiso, 'activa', :usuario, :observaciones, CURRENT_TIMESTAMP)
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
    foreach ($politicas as $politica) {
        $stmt->execute(array(
            ":codigo" => $politica["codigo"],
            ":nombre" => $politica["nombre"],
            ":tipo" => $politica["tipo_excepcion"],
            ":canal" => $politica["canal"],
            ":almacen" => $politica["id_almacen"],
            ":descuento_porcentaje" => $politica["descuento_max_porcentaje"],
            ":descuento_monto" => $politica["descuento_max_monto"],
            ":margen_minimo" => $politica["margen_minimo_porcentaje"],
            ":requiere_autorizacion" => $politica["requiere_autorizacion"],
            ":permiso" => $politica["permiso_requerido"],
            ":usuario" => $politica["creado_por"],
            ":observaciones" => $politica["observaciones"]
        ));
        $ejecutadas[] = array("codigo" => $politica["codigo"], "filas_afectadas" => $stmt->rowCount());
    }
    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "ventas_pos_excepcion_politicas_ejecutadas",
        "respaldo_ref" => $respaldo,
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "ejecutadas" => $ejecutadas,
        "siguiente_paso" => "Validar politicas read-only y preparar registro real de excepcion comercial con autorizacion separada."
    ));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage(),
        "ejecutadas_antes_error" => $ejecutadas
    ));
}

function politicasUat($idAlmacen, $idUsuario) {
    return array(
        array("codigo" => "POS_PRECIO_MANUAL_UAT", "nombre" => "Precio manual POS UAT", "tipo_excepcion" => "precio_manual", "canal" => "pos", "id_almacen" => $idAlmacen, "descuento_max_porcentaje" => 0, "descuento_max_monto" => 0, "margen_minimo_porcentaje" => 0, "requiere_autorizacion" => 1, "permiso_requerido" => "ventas.autorizar_excepcion_comercial", "creado_por" => $idUsuario, "observaciones" => "UAT: permite simular/aplicar precio manual solo con autorizacion de supervisor."),
        array("codigo" => "POS_DESCUENTO_PARTIDA_UAT", "nombre" => "Descuento por partida POS UAT", "tipo_excepcion" => "descuento_partida", "canal" => "pos", "id_almacen" => $idAlmacen, "descuento_max_porcentaje" => 0.100000, "descuento_max_monto" => 50, "margen_minimo_porcentaje" => 0, "requiere_autorizacion" => 1, "permiso_requerido" => "ventas.autorizar_excepcion_comercial", "creado_por" => $idUsuario, "observaciones" => "UAT: descuento por partida limitado y autorizado."),
        array("codigo" => "POS_DESCUENTO_GENERAL_UAT", "nombre" => "Descuento general POS UAT", "tipo_excepcion" => "descuento_general", "canal" => "pos", "id_almacen" => $idAlmacen, "descuento_max_porcentaje" => 0.100000, "descuento_max_monto" => 100, "margen_minimo_porcentaje" => 0, "requiere_autorizacion" => 1, "permiso_requerido" => "ventas.autorizar_excepcion_comercial", "creado_por" => $idUsuario, "observaciones" => "UAT: descuento general limitado y autorizado.")
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
