<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: auditar permisos finos de Configuracion POS sin escribir BD.
 * Impacto: confirma preparacion antes de exponer CRUD real de cajas, terminales y asignaciones.
 * Contrato: read-only; no crea permisos, roles ni relaciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/SeguridadPermisos.php";

$opciones = getopt("", array("id_usuario::", "compact::"));
$idUsuario = isset($opciones["id_usuario"]) ? intval($opciones["id_usuario"]) : 1;
$compact = isset($opciones["compact"]) && intval($opciones["compact"]) === 1;

$db = (new class extends CRUD {
    public function conexion() {
        return $this->getConexion();
    }
})->conexion();

$permisos = ventas_pos_config_permisos_seed();
$seguridad = new SeguridadPermisos();
$permisosDb = array();
$rolesPorPermiso = array();
$usuarioPermisos = array();

foreach ($permisos as $permiso) {
    $permisosDb[$permiso["permiso"]] = false;
    $rolesPorPermiso[$permiso["permiso"]] = array();
    $usuarioPermisos[$permiso["permiso"]] = $seguridad->usuarioTienePermiso($idUsuario, $permiso["permiso"]);
}

$stmt = $db->query("SELECT id_permiso, permiso FROM sys_permisos WHERE permiso LIKE 'ventas.pos_config.%'");
$idsPermiso = array();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $permisosDb[$row["permiso"]] = true;
    $idsPermiso[intval($row["id_permiso"])] = $row["permiso"];
}

if (!empty($idsPermiso)) {
    $stmtRoles = $db->query("SELECT rp.id_permiso, r.rol
        FROM sys_roles_permisos rp
        INNER JOIN sys_roles r ON r.id_rol=rp.id_rol
        WHERE rp.id_permiso IN (" . implode(",", array_keys($idsPermiso)) . ")
        ORDER BY r.rol");
    foreach ($stmtRoles->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $permiso = $idsPermiso[intval($row["id_permiso"])];
        $rolesPorPermiso[$permiso][] = $row["rol"];
    }
}

$faltantes = array();
foreach ($permisosDb as $permiso => $existe) {
    if (!$existe) {
        $faltantes[] = $permiso;
    }
}

$salida = array(
    "ok" => empty($faltantes),
    "modo" => "ventas_pos_configuracion_permisos_readonly",
    "read_only" => true,
    "id_usuario" => $idUsuario,
    "permisos_existentes" => $permisosDb,
    "roles_por_permiso" => $rolesPorPermiso,
    "usuario_permisos" => $usuarioPermisos,
    "faltantes" => $faltantes,
    "autorizacion_sugerida" => empty($faltantes) ? null : "AUTORIZO SEMBRAR PERMISOS CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_PERMISOS para UAT POS",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_asigna_roles" => true,
        "no_abre_turno" => true,
        "no_mueve_caja" => true
    )
);

echo json_encode($salida, ($compact ? 0 : JSON_PRETTY_PRINT) | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function ventas_pos_config_permisos_seed() {
    return array(
        array("modulo" => "ventas", "accion" => "pos_config_ver", "permiso" => "ventas.pos_config.ver", "descripcion" => "Consultar configuracion POS de tiendas, cajas, terminales y asignaciones"),
        array("modulo" => "ventas", "accion" => "pos_config_crear", "permiso" => "ventas.pos_config.crear", "descripcion" => "Crear cajas, terminales y asignaciones POS sin abrir turnos ni mover caja"),
        array("modulo" => "ventas", "accion" => "pos_config_editar", "permiso" => "ventas.pos_config.editar", "descripcion" => "Editar cajas, terminales y asignaciones POS con trazabilidad"),
        array("modulo" => "ventas", "accion" => "pos_config_desactivar", "permiso" => "ventas.pos_config.desactivar", "descripcion" => "Desactivar configuracion POS con baja logica y motivo obligatorio"),
        array("modulo" => "ventas", "accion" => "pos_config_asignar_usuario", "permiso" => "ventas.pos_config.asignar_usuario", "descripcion" => "Asignar usuarios a tienda, caja y terminal POS oficial")
    );
}
