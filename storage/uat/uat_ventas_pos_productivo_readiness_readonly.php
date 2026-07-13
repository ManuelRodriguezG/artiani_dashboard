<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: auditar readiness productivo de POS sin ejecutar escrituras.
 * Impacto: identifica brechas para operar ventas, caja, inventario pendiente, CRM, garantias y reportes sin tokens UAT.
 * Contrato: read-only; no abre turnos, no vende, no crea pendientes, no modifica permisos y no mueve inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosProductivoReadinessDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$args = isset($argv) ? $argv : array();
$idUsuario = intval(argValor($args, "--id_usuario", 1));
$idAlmacen = intval(argValor($args, "--id_almacen", 5));
$idSku = intval(argValor($args, "--id_sku", 1760));
$cantidad = floatval(argValor($args, "--cantidad", 1));

$dbHelper = new UatVentasPosProductivoReadinessDb();
$db = $dbHelper->db();
$ventas = new VentasErp();

$tablasBase = array(
    "erp_ventas",
    "erp_ventas_detalle",
    "erp_ventas_pagos",
    "erp_ventas_detalle_inventario",
    "erp_ventas_detalle_garantias",
    "erp_pos_cajas",
    "erp_pos_terminales",
    "erp_pos_usuarios_cajas",
    "erp_pos_turnos",
    "erp_pos_movimientos_caja",
    "erp_pos_politicas_venta_inventario",
    "erp_pos_inventario_pendientes",
    "erp_pos_inventario_pendientes_eventos",
    "erp_notificaciones",
    "erp_inventario_existencias",
    "erp_inventario_movimientos",
    "crm_clientes",
    "crm_clientes_saldos_cuentas",
    "crm_clientes_saldos_movimientos",
    "erp_listas_precios",
    "erp_listas_precios_detalle"
);

$permisosEsperados = array(
    "ventas.ver",
    "ventas.operar",
    "ventas.caja_diferencias.ver",
    "ventas.caja_diferencias.resolver",
    "ventas.caja_evidencias.revisar",
    "ventas.autorizar_excepcion_comercial",
    "ventas.pos.inventario_pendiente.autorizar",
    "inventario.ver",
    "crm.ver",
    "crm.crear",
    "ventas.listas.ver"
);

$schema = array();
$bloqueos = array();
$avisos = array();
foreach ($tablasBase as $tabla) {
    $schema[$tabla] = tablaExiste($db, $tabla);
}

foreach (array("erp_ventas", "erp_ventas_detalle", "erp_pos_movimientos_caja", "erp_pos_inventario_pendientes") as $tablaCritica) {
    if (empty($schema[$tablaCritica])) {
        $bloqueos[] = "Falta tabla critica para POS productivo: " . $tablaCritica;
    }
}

$permisos = consultarPermisos($db, $permisosEsperados, $idUsuario);
foreach ($permisos["faltantes_catalogo"] as $permiso) {
    if ($permiso === "ventas.pos.inventario_pendiente.autorizar") {
        $avisos[] = "Falta sembrar permiso fino para autorizar inventario pendiente desde POS productivo: " . $permiso;
    } else {
        $avisos[] = "Permiso no encontrado en catalogo: " . $permiso;
    }
}
foreach ($permisos["faltantes_usuario"] as $permiso) {
    if (in_array($permiso, array("ventas.ver", "ventas.operar"), true)) {
        $bloqueos[] = "Usuario sin permiso base POS: " . $permiso;
    } else {
        $avisos[] = "Usuario sin permiso operativo recomendado: " . $permiso;
    }
}

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$asignacionDepurar = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$asignacionActiva = !empty($asignacionDepurar["asignacion"]);
$turnoAbierto = !empty($asignacionDepurar["turno_abierto"]);
if (!$asignacionActiva) {
    $bloqueos[] = "Usuario sin asignacion POS activa a tienda/caja/terminal";
}
if (!$turnoAbierto) {
    $avisos[] = "No hay turno abierto para el usuario; es normal fuera de horario, pero bloquea cobro real hasta apertura";
}

$dryInventarioPendiente = $ventas->ventaInventarioPendienteDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "canal" => "pos",
    "motivo" => "Readiness productivo POS"
));
$pendientes = consultarPendientesInventarioPos($db);
$notificaciones = consultarNotificacionesPos($db);
$ventasRecientes = consultarVentasRecientes($db);
$turnos = consultarTurnos($db);
$politicas = consultarPoliticasPendiente($db, $idAlmacen, $idSku);

if (empty($politicas["activas"])) {
    $avisos[] = "No hay politica activa de inventario pendiente para almacen/SKU auditado";
}
if (intval($pendientes["abiertos"]) > 0) {
    $avisos[] = "Hay pendientes de inventario abiertos; revisar antes de promover salida productiva";
}
if (intval($notificaciones["abiertas"]) > intval($pendientes["abiertos"]) + 2) {
    $avisos[] = "Hay mas notificaciones abiertas que pendientes POS; revisar cierres de alertas";
}

$contratoProductivo = array(
    "pos_normal_ui" => "Operativo con ventas.operar y turno abierto",
    "inventario_pendiente_ui" => "Solo dry-run en UI; real todavia protegido por sistema.soporte/token UAT",
    "requisito_para_productivo_inventario_pendiente" => array(
        "crear_permiso_catalogo" => "ventas.pos.inventario_pendiente.autorizar",
        "asignar_permiso_a_supervisor" => true,
        "reemplazar_token_uat_por_permiso_y_confirmacion" => true,
        "motivo_obligatorio" => true,
        "auditoria_explicita" => true,
        "mantener_politica_sucursal_sku_canal" => true,
        "no_activar_ecommerce" => true
    )
);

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_productivo_readiness_readonly",
    "read_only" => true,
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad
    ),
    "schema" => $schema,
    "permisos" => $permisos,
    "asignacion_pos" => $asignacionDepurar,
    "estado_operativo" => array(
        "turnos" => $turnos,
        "ventas_recientes" => $ventasRecientes,
        "politicas_inventario_pendiente" => $politicas,
        "pendientes_inventario_pos" => $pendientes,
        "notificaciones_pos" => $notificaciones
    ),
    "dry_run_inventario_pendiente" => $dryInventarioPendiente,
    "contrato_productivo" => $contratoProductivo,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => empty($bloqueos)
        ? "POS base listo para pruebas operativas. Para inventario pendiente productivo falta autorizacion de cambio: permiso fino + endpoint real sin token UAT."
        : "Resolver bloqueos antes de promover POS a pruebas operativas reales."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function argValor($args, $clave, $default) {
    foreach ($args as $arg) {
        if (strpos($arg, $clave . "=") === 0) {
            return trim(substr($arg, strlen($clave) + 1), "\"' ");
        }
    }
    return $default;
}

function tablaExiste($db, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        return false;
    }
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function consultarTodos($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function consultarUno($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function consultarPermisos($db, $permisosEsperados, $idUsuario) {
    $existentes = array();
    $asignados = array();
    if (tablaExiste($db, "sys_permisos")) {
        $placeholders = implode(",", array_fill(0, count($permisosEsperados), "?"));
        $stmt = $db->prepare("SELECT permiso FROM sys_permisos WHERE permiso IN ($placeholders) AND estatus=1");
        $stmt->execute($permisosEsperados);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existentes[] = $row["permiso"];
        }
    }
    if (tablaExiste($db, "sys_usuarios_roles") && tablaExiste($db, "sys_roles_permisos") && tablaExiste($db, "sys_permisos")) {
        $placeholders = implode(",", array_fill(0, count($permisosEsperados), "?"));
        $params = array_merge(array(intval($idUsuario)), $permisosEsperados);
        $stmt = $db->prepare("SELECT DISTINCT p.permiso
            FROM sys_usuarios_roles ur
            INNER JOIN sys_roles_permisos rp ON rp.id_rol=ur.id_rol
            INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso AND p.estatus=1
            WHERE ur.id_usuario=? AND ur.estatus=1 AND p.permiso IN ($placeholders)");
        $stmt->execute($params);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $asignados[] = $row["permiso"];
        }
    }
    return array(
        "esperados" => $permisosEsperados,
        "existentes_catalogo" => array_values($existentes),
        "asignados_usuario" => array_values($asignados),
        "faltantes_catalogo" => array_values(array_diff($permisosEsperados, $existentes)),
        "faltantes_usuario" => array_values(array_diff(array_intersect($permisosEsperados, $existentes), $asignados))
    );
}

function consultarPendientesInventarioPos($db) {
    if (!tablaExiste($db, "erp_pos_inventario_pendientes")) {
        return array("abiertos" => 0, "ultimos" => array());
    }
    $row = consultarUno($db, "SELECT
            SUM(CASE WHEN estatus IN ('pendiente_revision','en_revision') THEN 1 ELSE 0 END) abiertos,
            SUM(CASE WHEN estatus='resuelto' THEN 1 ELSE 0 END) resueltos
        FROM erp_pos_inventario_pendientes");
    $ultimos = consultarTodos($db, "SELECT folio, estatus, id_almacen, id_sku_erp, sku, cantidad_vendida, cantidad_cubierta, cantidad_pendiente, fecha_registro
        FROM erp_pos_inventario_pendientes
        ORDER BY id_inventario_pendiente DESC
        LIMIT 10");
    return array(
        "abiertos" => intval($row["abiertos"] ?? 0),
        "resueltos" => intval($row["resueltos"] ?? 0),
        "ultimos" => $ultimos
    );
}

function consultarNotificacionesPos($db) {
    if (!tablaExiste($db, "erp_notificaciones")) {
        return array("abiertas" => 0, "ultimas" => array());
    }
    $row = consultarUno($db, "SELECT
            SUM(CASE WHEN estatus IN ('pendiente','en_revision','bloqueada') THEN 1 ELSE 0 END) abiertas,
            SUM(CASE WHEN estatus='resuelta' THEN 1 ELSE 0 END) resueltas
        FROM erp_notificaciones
        WHERE tipo='pos_venta_inventario_pendiente'");
    $ultimas = consultarTodos($db, "SELECT id_notificacion, estatus, prioridad, titulo, url_accion, fecha_registro, fecha_resolucion
        FROM erp_notificaciones
        WHERE tipo='pos_venta_inventario_pendiente'
        ORDER BY id_notificacion DESC
        LIMIT 10");
    return array(
        "abiertas" => intval($row["abiertas"] ?? 0),
        "resueltas" => intval($row["resueltas"] ?? 0),
        "ultimas" => $ultimas
    );
}

function consultarVentasRecientes($db) {
    if (!tablaExiste($db, "erp_ventas")) {
        return array("total" => 0, "ultimas" => array());
    }
    $row = consultarUno($db, "SELECT COUNT(*) total FROM erp_ventas WHERE canal='pos'");
    $ultimas = consultarTodos($db, "SELECT folio, estatus, inventario_validacion_estado, total, pagado_total, id_turno_caja, fecha_venta
        FROM erp_ventas
        WHERE canal='pos'
        ORDER BY id_venta DESC
        LIMIT 10");
    return array("total" => intval($row["total"] ?? 0), "ultimas" => $ultimas);
}

function consultarTurnos($db) {
    if (!tablaExiste($db, "erp_pos_turnos")) {
        return array("abiertos" => 0, "ultimos" => array());
    }
    $row = consultarUno($db, "SELECT SUM(CASE WHEN estatus='abierto' THEN 1 ELSE 0 END) abiertos, COUNT(*) total FROM erp_pos_turnos");
    $ultimos = consultarTodos($db, "SELECT id_turno_caja, folio, id_almacen, id_caja, estatus, monto_inicial, monto_esperado, monto_contado, diferencia, fecha_apertura, fecha_cierre
        FROM erp_pos_turnos
        ORDER BY id_turno_caja DESC
        LIMIT 10");
    return array("abiertos" => intval($row["abiertos"] ?? 0), "total" => intval($row["total"] ?? 0), "ultimos" => $ultimos);
}

function consultarPoliticasPendiente($db, $idAlmacen, $idSku) {
    if (!tablaExiste($db, "erp_pos_politicas_venta_inventario")) {
        return array("activas" => array(), "total_activas" => 0);
    }
    $activas = consultarTodos($db, "SELECT id_politica_inventario_pos, codigo, nombre, id_almacen, id_sku_erp, canal,
            cantidad_maxima_pendiente, monto_maximo, requiere_autorizacion, permiso_requerido, estatus
        FROM erp_pos_politicas_venta_inventario
        WHERE estatus='activa'
          AND permite_inventario_pendiente=1
          AND id_almacen=:almacen
          AND (id_sku_erp IS NULL OR id_sku_erp=:sku)
        ORDER BY id_sku_erp DESC, id_politica_inventario_pos DESC
        LIMIT 10", array(":almacen" => intval($idAlmacen), ":sku" => intval($idSku)));
    return array("activas" => $activas, "total_activas" => count($activas));
}
