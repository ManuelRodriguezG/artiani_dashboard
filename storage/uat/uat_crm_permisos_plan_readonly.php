<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: generar plan read-only para sembrar permisos CRM.
 * Impacto: habilita visibilidad/control de /crm/clientes sin aplicar cambios aun.
 * Contrato: no inserta roles, permisos ni relaciones.
 */

$permisos = crm_permisos_plan();

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "token_apply" => "CRM_PERMISOS_BASE",
  "rol" => array(
    "rol" => "crm",
    "descripcion" => "Administra clientes, contactos, segmentos, historial comercial y relacion postventa"
  ),
  "permisos" => $permisos,
  "roles_a_vincular" => array("direccion", "administrador_erp", "ventas", "crm"),
  "alcance" => array(
    "crea_rol_crm_si_falta" => true,
    "crea_permisos_crm" => true,
    "vincula_roles_base" => true,
    "asigna_usuarios" => false,
    "toca_clientes" => false,
    "toca_ventas" => false
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function crm_permisos_plan() {
  return array(
    array("modulo" => "crm", "accion" => "ver", "permiso" => "crm.ver", "descripcion" => "Consultar clientes, ficha, historial y relaciones CRM"),
    array("modulo" => "crm", "accion" => "crear", "permiso" => "crm.crear", "descripcion" => "Crear clientes y altas express autorizadas"),
    array("modulo" => "crm", "accion" => "editar", "permiso" => "crm.editar", "descripcion" => "Editar ficha, contactos, direcciones y condiciones CRM"),
    array("modulo" => "crm", "accion" => "fusionar", "permiso" => "crm.fusionar", "descripcion" => "Fusionar duplicados de clientes con trazabilidad"),
    array("modulo" => "crm", "accion" => "auditoria", "permiso" => "crm.auditoria", "descripcion" => "Auditar fuentes, migraciones, duplicados y esquema CRM")
  );
}
