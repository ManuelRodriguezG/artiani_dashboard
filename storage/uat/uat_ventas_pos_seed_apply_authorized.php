<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: aplicar semillas POS solo con autorizacion explicita y respaldo validado.
 * Impacto: crea cajas, terminales y asignaciones usuario/caja/terminal despues del DDL.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_SEED, --respaldo y usuario real.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuarioDefault = 0;
$usuariosPorAlmacen = array();
foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuarioDefault = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--usuario_almacen=") === 0) {
        $valor = trim(substr($arg, 18), "\"' ");
        $partes = explode(":", $valor, 2);
        if (count($partes) === 2) {
            $usuariosPorAlmacen[strtoupper(trim($partes[0]))] = intval($partes[1]);
        }
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_SEED" || !$validacionRespaldo["ok"] || ($idUsuarioDefault <= 0 && empty($usuariosPorAlmacen))) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecutaron semillas. Falta autorizacion, respaldo valido o usuario ERP real.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_SEED",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID o --usuario_almacen=CODIGO:ID"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "reglas" => array(
            "Ejecutar solo despues de aplicar DDL Ventas/POS.",
            "No ejecutar asignaciones con id_usuario=0.",
            "Validar con UAT read-only despues de sembrar."
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new VentasErp();
$dbHelper = new UatVentasPosDb();
$db = $dbHelper->db();

$tablasRequeridas = array("erp_pos_cajas", "erp_pos_terminales", "erp_pos_usuarios_cajas");
$faltantes = tablasFaltantes($db, $tablasRequeridas);
if (!empty($faltantes)) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta aplicar DDL antes de sembrar POS.",
        "tablas_faltantes" => $faltantes
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$planCajas = $ventas->planCajasInicialesPos();
$planTerminales = $ventas->planAsignacionTerminalPos(array(
    "id_usuario" => $idUsuarioDefault,
    "usuario_nombre" => $idUsuarioDefault > 0 ? "Usuario POS " . $idUsuarioDefault : "Usuario POS por sucursal"
));

$sentencias = array();
foreach (valor($planCajas, array("depurar", "propuestas"), array()) as $propuesta) {
    $caja = $propuesta["caja_sugerida"];
    $sentencias[] = array(
        "tipo" => "caja",
        "sql" => "INSERT INTO `erp_pos_cajas` (`codigo`, `nombre`, `id_almacen`, `estatus`, `permite_efectivo`, `permite_tarjeta`, `permite_transferencia`) VALUES ("
            . sqlQuote($caja["codigo"]) . ", "
            . sqlQuote($caja["nombre"]) . ", "
            . intval($caja["id_almacen"]) . ", "
            . sqlQuote($caja["estatus"]) . ", "
            . intval($caja["permite_efectivo"]) . ", "
            . intval($caja["permite_tarjeta"]) . ", "
            . intval($caja["permite_transferencia"]) . ")
            ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), id_almacen=VALUES(id_almacen), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP;"
    );
}

foreach (valor($planTerminales, array("depurar", "propuestas"), array()) as $propuesta) {
    $terminal = $propuesta["terminal_sugerida"];
    $asignacion = $propuesta["asignacion_sugerida"];
    $codigoAlmacen = strtoupper((string) $propuesta["codigo_almacen"]);
    $idUsuarioAsignacion = isset($usuariosPorAlmacen[$codigoAlmacen])
        ? intval($usuariosPorAlmacen[$codigoAlmacen])
        : intval($asignacion["id_usuario"]);
    if ($idUsuarioAsignacion <= 0) {
        echo json_encode(array(
            "ok" => false,
            "modo" => "bloqueado",
            "mensaje" => "Asignacion POS sin usuario real.",
            "codigo_almacen" => $codigoAlmacen
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    $sentencias[] = array(
        "tipo" => "terminal",
        "sql" => "INSERT INTO `erp_pos_terminales` (`codigo`, `nombre`, `id_almacen`, `id_caja`, `estatus`) SELECT "
            . sqlQuote($terminal["codigo"]) . ", "
            . sqlQuote($terminal["nombre"]) . ", "
            . intval($terminal["id_almacen"]) . ", "
            . "c.`id_caja`, "
            . sqlQuote($terminal["estatus"]) . " FROM `erp_pos_cajas` c WHERE c.`codigo` = "
            . sqlQuote($terminal["caja_codigo"]) . " LIMIT 1
            ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), id_almacen=VALUES(id_almacen), id_caja=VALUES(id_caja), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP;"
    );
    $sentencias[] = array(
        "tipo" => "asignacion",
        "sql" => "INSERT INTO `erp_pos_usuarios_cajas` (`id_usuario`, `id_almacen`, `id_caja`, `id_terminal_pos`, `estatus`, `prioridad`)
            SELECT " . $idUsuarioAsignacion . ", " . intval($asignacion["id_almacen"]) . ", c.`id_caja`, t.`id_terminal_pos`, " . sqlQuote($asignacion["estatus"]) . ", " . intval($asignacion["prioridad"]) . "
            FROM `erp_pos_cajas` c
            LEFT JOIN `erp_pos_terminales` t ON t.`codigo` = " . sqlQuote($asignacion["terminal_codigo"]) . "
            WHERE c.`codigo` = " . sqlQuote($asignacion["caja_codigo"]) . "
              AND NOT EXISTS (
                SELECT 1 FROM `erp_pos_usuarios_cajas` uc
                WHERE uc.`id_usuario` = " . $idUsuarioAsignacion . "
                  AND uc.`id_almacen` = " . intval($asignacion["id_almacen"]) . "
                  AND uc.`id_caja` = c.`id_caja`
                  AND COALESCE(uc.`id_terminal_pos`, 0) = COALESCE(t.`id_terminal_pos`, 0)
                  AND uc.`estatus` = 'activo'
              )
            LIMIT 1;"
    );
}

$ejecutadas = array();
try {
    $db->beginTransaction();
    foreach ($sentencias as $sentencia) {
        $stmt = $db->prepare($sentencia["sql"]);
        $stmt->execute();
        $ejecutadas[] = array(
            "tipo" => $sentencia["tipo"],
            "filas_afectadas" => $stmt->rowCount()
        );
    }
    $db->commit();
    echo json_encode(array(
        "ok" => true,
        "modo" => "semillas_ejecutadas",
        "respaldo_ref" => $respaldo,
        "sentencias_total" => count($sentencias),
        "ejecutadas" => $ejecutadas,
        "siguiente_paso" => "Ejecutar UAT read-only y validar asignacion actual POS."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage(),
        "sentencias_preparadas" => count($sentencias)
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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

function sqlQuote($valor) {
    return "'" . str_replace("'", "''", (string) $valor) . "'";
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
