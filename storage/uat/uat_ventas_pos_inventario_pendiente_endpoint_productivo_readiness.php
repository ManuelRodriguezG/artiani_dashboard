<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: preparar el pase de inventario pendiente POS desde token UAT a endpoint productivo con permisos.
 * Impacto: audita controlador, modelo, UI, permiso y politica activa sin habilitar cobro real ni escribir BD.
 * Contrato: read-only; no crea ventas, no abre/cierra turnos, no mueve caja, no mueve inventario y no crea pendientes.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(str_replace(",", ".", trim(substr($arg, 11), "\"' ")));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosInventarioPendienteEndpointProductivoDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$dbHelper = new UatVentasPosInventarioPendienteEndpointProductivoDb();
$db = $dbHelper->db();
$ventas = new VentasErp();

$root = realpath(__DIR__ . "/../..");
$controllerPath = $root . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "controladores" . DIRECTORY_SEPARATOR . "Ventas.php";
$modelPath = $root . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "modelos" . DIRECTORY_SEPARATOR . "VentasErp.php";
$jsPath = $root . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "pos.js";

$controller = is_file($controllerPath) ? file_get_contents($controllerPath) : "";
$model = is_file($modelPath) ? file_get_contents($modelPath) : "";
$js = is_file($jsPath) ? file_get_contents($jsPath) : "";
$permisos = consultarPermisos($db, $idUsuario, array(
    "ventas.operar",
    "ventas.pos.inventario_pendiente.autorizar",
    "inventario.ver"
));
$politicas = consultarPoliticas($db, $idAlmacen, $idSku);
$dryRun = $ventas->ventaInventarioPendienteDryRun(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "canal" => "pos",
    "motivo" => "Readiness endpoint productivo POS"
));

$checks = array(
    "controlador_uat_actual" => strpos($controller, "public function pos_inventario_pendiente_real_erp") !== false
        && strpos($controller, "VENTAS_POS_INVENTARIO_PENDIENTE_REAL") !== false
        && strpos($controller, "sistema.soporte") !== false,
    "controlador_productivo_preparado" => strpos($controller, "public function pos_inventario_pendiente_cobrar_erp") !== false
        && strpos($controller, "ventas.pos.inventario_pendiente.autorizar") !== false
        && strpos($controller, "AUTORIZAR INVENTARIO PENDIENTE") !== false
        && strpos($controller, "auditarInventarioPendientePos") !== false,
    "modelo_real_transaccional" => strpos($model, "public function ventaInventarioPendienteReal") !== false
        && strpos($model, "beginTransaction") !== false
        && strpos($model, "registrarNotificacionInventarioPendientePos") !== false,
    "ui_dryrun_y_accion_supervisada" => strpos($js, "/ventas/pos_inventario_pendiente_dryrun_erp") !== false
        && strpos($js, "/ventas/pos_inventario_pendiente_cobrar_erp") !== false
        && strpos($js, "data-pos-inventario-pendiente-cobrar") !== false
        && strpos($js, "function cobrarInventarioPendienteReal") !== false,
    "permiso_catalogo" => in_array("ventas.pos.inventario_pendiente.autorizar", $permisos["existentes_catalogo"], true),
    "permiso_usuario" => in_array("ventas.pos.inventario_pendiente.autorizar", $permisos["asignados_usuario"], true),
    "politica_pos_activa" => !empty($politicas),
    "dry_run_autorizable" => empty($dryRun["error"])
        && isset($dryRun["depurar"]["estado"])
        && $dryRun["depurar"]["estado"] === "pendiente_autorizable"
);

$bloqueos = array();
foreach ($checks as $nombre => $ok) {
    if (!$ok) {
        $bloqueos[] = "No cumple check: " . $nombre;
    }
}

$tareasEndpoint = array(
    "Endpoint productivo separado en Ventas.php preparado.",
    "Permisos ventas.operar y ventas.pos.inventario_pendiente.autorizar preparados.",
    "Endpoint UAT con token/respaldo se conserva como herramienta de soporte.",
    "Confirmacion exacta AUTORIZAR INVENTARIO PENDIENTE y motivo obligatorio preparados.",
    "Validacion por politica almacen/SKU/canal se delega al modelo transaccional.",
    "Auditoria explicita preparada desde controlador.",
    "UI POS muestra accion supervisada solo despues de dry-run autorizable.",
    "Mantener ecommerce fuera del flujo."
);

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_inventario_pendiente_endpoint_productivo_readiness",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad
    ),
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "permisos" => $permisos,
    "politicas_activas" => $politicas,
    "dry_run_estado" => isset($dryRun["depurar"]["estado"]) ? $dryRun["depurar"]["estado"] : null,
    "tareas_endpoint_productivo" => $tareasEndpoint,
    "autorizacion_siguiente" => "AUTORIZO EJECUTAR UAT PRODUCTIVA VENTA INVENTARIO PENDIENTE POS usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_PRODUCTIVO_REAL id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 pago=295 motivo=\"UAT endpoint productivo inventario pendiente POS\" confirmacion=\"AUTORIZAR INVENTARIO PENDIENTE\" para UAT POS/Inventario",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_habilita_endpoint" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_crea_pendientes" => true
    )
), empty($bloqueos) ? 0 : 1);

function consultarPermisos($db, $idUsuario, $permisos) {
    $existentes = array();
    if (tablaExiste($db, "sys_permisos")) {
        $placeholders = implode(",", array_fill(0, count($permisos), "?"));
        $stmt = $db->prepare("SELECT permiso FROM sys_permisos WHERE permiso IN (" . $placeholders . ") AND estatus=1");
        $stmt->execute($permisos);
        $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $asignados = array();
    if (tablaExiste($db, "sys_permisos") && tablaExiste($db, "sys_roles_permisos") && tablaExiste($db, "sys_usuarios_roles")) {
        $placeholders = implode(",", array_fill(0, count($permisos), "?"));
        $sql = "SELECT DISTINCT p.permiso
            FROM sys_usuarios_roles ur
            INNER JOIN sys_roles_permisos rp ON rp.id_rol=ur.id_rol
            INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso AND p.estatus=1
            WHERE ur.id_usuario=? AND p.permiso IN (" . $placeholders . ")";
        $params = array_merge(array($idUsuario), $permisos);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return array(
        "esperados" => $permisos,
        "existentes_catalogo" => array_values($existentes),
        "asignados_usuario" => array_values($asignados),
        "faltantes_catalogo" => array_values(array_diff($permisos, $existentes)),
        "faltantes_usuario" => array_values(array_diff($permisos, $asignados))
    );
}

function consultarPoliticas($db, $idAlmacen, $idSku) {
    if (!tablaExiste($db, "erp_pos_politicas_venta_inventario")) {
        return array();
    }
    $stmt = $db->prepare("SELECT id_politica_inventario_pos, codigo, nombre, cantidad_maxima_pendiente, monto_maximo,
            requiere_autorizacion, permiso_requerido, estatus
        FROM erp_pos_politicas_venta_inventario
        WHERE id_almacen=:almacen
          AND id_sku_erp=:sku
          AND canal='pos'
          AND permite_inventario_pendiente=1
          AND estatus='activa'
        ORDER BY id_politica_inventario_pos DESC");
    $stmt->execute(array(":almacen" => $idAlmacen, ":sku" => $idSku));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tablaExiste($db, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        return false;
    }
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
