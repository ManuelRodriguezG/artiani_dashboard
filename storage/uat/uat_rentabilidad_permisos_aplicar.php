<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/SeguridadEsquema.php";

$modelo = new SeguridadEsquema();
$respuesta = $modelo->planActualizarSeguridad(true);

$permisosRentabilidad = array();
$rolesRentabilidad = array();
$db = (new class extends CRUD {
    public function db() { return $this->getConexion(); }
})->db();

$stmt = $db->query("SELECT permiso FROM sys_permisos WHERE modulo='rentabilidad' ORDER BY permiso");
$permisosRentabilidad = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $db->query("SELECT sr.rol, sp.permiso
    FROM sys_roles sr
    INNER JOIN sys_roles_permisos srp ON srp.id_rol=sr.id_rol
    INNER JOIN sys_permisos sp ON sp.id_permiso=srp.id_permiso
    WHERE sp.modulo='rentabilidad'
    ORDER BY sr.rol, sp.permiso");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    if (!isset($rolesRentabilidad[$fila["rol"]])) {
        $rolesRentabilidad[$fila["rol"]] = array();
    }
    $rolesRentabilidad[$fila["rol"]][] = $fila["permiso"];
}

echo json_encode(array(
    "ok" => !$respuesta["error"] && count($permisosRentabilidad) === 3,
    "mensaje" => $respuesta["mensaje"],
    "permisos" => $permisosRentabilidad,
    "roles" => $rolesRentabilidad
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

