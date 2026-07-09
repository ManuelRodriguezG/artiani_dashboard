<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: sembrar permisos finos de diferencias de caja POS solo con autorizacion explicita.
 * Impacto: habilita separacion ver/revisar/resolver; no toca turnos, caja, ventas ni inventario.
 * Contrato: bloqueado por defecto; requiere token VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS y respaldo vigente.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_ventas_pos_permisos($respaldo);

if ($autorizar !== "VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS" || !$validacion["ok"]) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se sembraron permisos de diferencias caja POS. Falta token o respaldo valido.",
        "requerido" => array(
            "autorizar" => "VENTAS_POS_CAJA_DIFERENCIAS_PERMISOS",
            "respaldo" => "RUTA_O_REFERENCIA"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => array(
            "crea_permisos" => true,
            "vincula_roles_base" => true,
            "asigna_usuarios_directo" => false,
            "toca_turnos" => false,
            "mueve_caja" => false,
            "mueve_inventario" => false
        )
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}

$db = (new class extends CRUD {
    public function conexion() {
        return $this->getConexion();
    }
})->conexion();

$permisos = ventas_pos_diferencias_permisos_seed();
$rolesPermisos = array(
    "direccion" => array("ventas.caja_diferencias.ver", "ventas.caja_diferencias.revisar", "ventas.caja_diferencias.resolver"),
    "administrador_erp" => array("ventas.caja_diferencias.ver", "ventas.caja_diferencias.revisar", "ventas.caja_diferencias.resolver"),
    "finanzas_contabilidad" => array("ventas.caja_diferencias.ver", "ventas.caja_diferencias.revisar", "ventas.caja_diferencias.resolver"),
    "auditor" => array("ventas.caja_diferencias.ver"),
    "ventas" => array("ventas.caja_diferencias.ver")
);

try {
    $db->beginTransaction();

    $stmtPermiso = $db->prepare("INSERT INTO sys_permisos (modulo, accion, permiso, descripcion, estatus, fecha_actualizacion)
        VALUES (:modulo, :accion, :permiso, :descripcion, 1, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE modulo=VALUES(modulo), accion=VALUES(accion), descripcion=VALUES(descripcion), estatus=1, fecha_actualizacion=CURRENT_TIMESTAMP");
    foreach ($permisos as $permiso) {
        $stmtPermiso->execute(array(
            ":modulo" => $permiso["modulo"],
            ":accion" => $permiso["accion"],
            ":permiso" => $permiso["permiso"],
            ":descripcion" => $permiso["descripcion"]
        ));
    }

    $rolesObjetivo = array_keys($rolesPermisos);
    $stmtRoles = $db->query("SELECT id_rol, rol FROM sys_roles WHERE rol IN ('" . implode("','", array_map("addslashes", $rolesObjetivo)) . "')");
    $roles = array();
    foreach ($stmtRoles->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $roles[$row["rol"]] = intval($row["id_rol"]);
    }

    $permisosDb = array();
    $stmtPermisos = $db->query("SELECT id_permiso, permiso FROM sys_permisos WHERE permiso LIKE 'ventas.caja_diferencias.%'");
    foreach ($stmtPermisos->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $permisosDb[$row["permiso"]] = intval($row["id_permiso"]);
    }

    $stmtRel = $db->prepare("INSERT IGNORE INTO sys_roles_permisos (id_rol, id_permiso) VALUES (:rol, :permiso)");
    $relaciones = 0;
    foreach ($rolesPermisos as $rol => $listaPermisos) {
        if (empty($roles[$rol])) {
            continue;
        }
        foreach ($listaPermisos as $permiso) {
            if (empty($permisosDb[$permiso])) {
                continue;
            }
            $stmtRel->execute(array(":rol" => $roles[$rol], ":permiso" => $permisosDb[$permiso]));
            $relaciones++;
        }
    }

    $db->commit();
    echo json_encode(array(
        "ok" => true,
        "modo" => "ventas_pos_diferencias_permisos_apply_authorized",
        "mensaje" => "Permisos de diferencias caja POS sembrados",
        "permisos_total" => count($permisos),
        "roles_detectados" => array_keys($roles),
        "relaciones_intentadas" => $relaciones,
        "asigna_usuarios_directo" => false
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array(
        "ok" => false,
        "modo" => "error",
        "mensaje" => $e->getMessage()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

function ventas_pos_diferencias_permisos_seed() {
    return array(
        array("modulo" => "ventas", "accion" => "caja_diferencias_ver", "permiso" => "ventas.caja_diferencias.ver", "descripcion" => "Consultar faltantes y sobrantes de caja POS por turno, usuario y sucursal"),
        array("modulo" => "ventas", "accion" => "caja_diferencias_revisar", "permiso" => "ventas.caja_diferencias.revisar", "descripcion" => "Crear o tomar expedientes de revision de diferencias de caja POS"),
        array("modulo" => "ventas", "accion" => "caja_diferencias_resolver", "permiso" => "ventas.caja_diferencias.resolver", "descripcion" => "Resolver administrativamente diferencias de caja POS sin mover efectivo ni inventario")
    );
}

function validar_respaldo_ventas_pos_permisos($respaldo) {
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
