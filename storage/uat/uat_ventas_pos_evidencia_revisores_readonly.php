<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: listar usuarios candidatos para revisar evidencia de caja POS sin modificar datos.
 * Impacto: ayuda a seleccionar un revisor distinto al creador de la evidencia.
 * Contrato: read-only; no asigna permisos ni revisa evidencias.
 */

$args = isset($argv) ? $argv : array();
$idEvidencia = 0;
$permiso = "ventas.autorizar_excepcion_comercial";

foreach ($args as $arg) {
    if (strpos($arg, "--id_evidencia_caja=") === 0) {
        $idEvidencia = intval(trim(substr($arg, 20), "\"' "));
    } elseif (strpos($arg, "--permiso=") === 0) {
        $permiso = trim(substr($arg, 10), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosEvidenciaRevisoresDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosEvidenciaRevisoresDb())->db();
$creadoPor = 0;
$evidencia = null;

if ($idEvidencia > 0 && tablaExiste($db, "erp_pos_movimientos_caja_evidencias")) {
    $stmt = $db->prepare("SELECT id_evidencia_caja, id_movimiento_caja, estatus, creado_por
        FROM erp_pos_movimientos_caja_evidencias
        WHERE id_evidencia_caja=:evidencia
        LIMIT 1");
    $stmt->execute(array(":evidencia" => $idEvidencia));
    $evidencia = $stmt->fetch(PDO::FETCH_ASSOC);
    $creadoPor = $evidencia ? intval($evidencia["creado_por"]) : 0;
}

$stmt = $db->prepare("SELECT su.id_usuario,
        TRIM(CONCAT_WS(' ', su.nombres, su.apellido_paterno, su.apellido_materno)) nombre,
        GROUP_CONCAT(DISTINCT r.rol ORDER BY r.rol SEPARATOR ', ') roles,
        MAX(CASE WHEN p.permiso=:permiso THEN 1 ELSE 0 END) tiene_permiso,
        CASE WHEN su.id_usuario=:creado_por THEN 1 ELSE 0 END es_creador_evidencia
    FROM sys_usuarios su
    INNER JOIN sys_usuarios_roles ur ON ur.id_usuario=su.id_usuario AND ur.estatus=1
    INNER JOIN sys_roles r ON r.id_rol=ur.id_rol AND r.estatus=1
    INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
    INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso AND p.estatus=1
    WHERE su.estatus=1
    GROUP BY su.id_usuario
    HAVING tiene_permiso=1
    ORDER BY es_creador_evidencia ASC, su.id_usuario ASC");
$stmt->execute(array(":permiso" => $permiso, ":creado_por" => $creadoPor));
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_evidencia_revisores_readonly",
    "read_only" => true,
    "permiso" => $permiso,
    "evidencia" => $evidencia,
    "creado_por" => $creadoPor,
    "total_candidatos" => count($usuarios),
    "candidatos" => $usuarios,
    "regla" => "Preferir un id_usuario distinto a creado_por; usar permitir_mismo_usuario=1 solo para UAT controlada."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}
