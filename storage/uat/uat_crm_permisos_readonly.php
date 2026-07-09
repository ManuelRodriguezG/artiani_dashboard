<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: auditar permisos CRM en seguridad sin sembrarlos.
 * Impacto: permite saber si /crm/clientes sera visible en sidebar para usuarios.
 * Contrato: read-only; no crea roles ni permisos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisos = array("crm.ver", "crm.crear", "crm.editar", "crm.fusionar", "crm.auditoria");
$resultado = array();
$tablaExiste = false;

if ($db) {
  $stmtTabla = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME='sys_permisos' LIMIT 1");
  $stmtTabla->execute(array(":base" => MYSQLBASE));
  $tablaExiste = (bool) $stmtTabla->fetch(PDO::FETCH_ASSOC);
}

if ($db && $tablaExiste) {
  $stmt = $db->prepare("SELECT permiso, modulo, accion, estatus FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
  foreach ($permisos as $permiso) {
    $stmt->execute(array(":permiso" => $permiso));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado[] = array(
      "permiso" => $permiso,
      "existe" => (bool) $fila,
      "registro" => $fila ?: null
    );
  }
}

echo json_encode(array(
  "ok" => (bool) $db,
  "modo" => "read-only",
  "sys_permisos_existe" => $tablaExiste,
  "permisos" => $resultado,
  "pendientes" => array_values(array_filter($resultado, function ($item) {
    return empty($item["existe"]);
  })),
  "siguiente_paso" => "Si hay pendientes, ejecutar plan de seguridad autorizado para sembrar crm.* antes de usar /crm/clientes desde UI."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
