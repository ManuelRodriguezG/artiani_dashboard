<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: validar catalogo configurable de segmentos CRM sin escribir BD.
 * Impacto: prepara segmentos base para listas de precios, clientes recurrentes y reglas futuras.
 * Contrato: read-only; no crea segmentos, no asigna clientes, no modifica listas ni ventas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ClientesCrm.php";

$modelo = new ClientesCrm();
$actuales = $modelo->segmentosCatalogoReadOnly(array("limite" => 100));
$base = array(
  array("codigo" => "PUBLICO_GENERAL", "nombre" => "Publico general", "tipo" => "comercial", "descripcion" => "Cliente sin relacion recurrente o venta anonima.", "estatus" => "activo"),
  array("codigo" => "RECURRENTE", "nombre" => "Cliente recurrente", "tipo" => "comercial", "descripcion" => "Compra frecuente con beneficio moderado.", "estatus" => "activo"),
  array("codigo" => "MAYOREO", "nombre" => "Mayoreo", "tipo" => "comercial", "descripcion" => "Compra por volumen o cuenta comercial.", "estatus" => "activo"),
  array("codigo" => "VIP", "nombre" => "VIP autorizado", "tipo" => "comercial", "descripcion" => "Cliente con mejores condiciones por autorizacion.", "estatus" => "activo"),
  array("codigo" => "INSTALADOR", "nombre" => "Instalador / tecnico", "tipo" => "comercial", "descripcion" => "Cliente que compra para instalaciones o mantenimiento.", "estatus" => "activo"),
  array("codigo" => "CONVENIO", "nombre" => "Convenio especial", "tipo" => "comercial", "descripcion" => "Acuerdo negociado con vigencia y motivo.", "estatus" => "activo"),
  array("codigo" => "ECOMMERCE_REG", "nombre" => "Ecommerce registrado", "tipo" => "comercial", "descripcion" => "Cliente registrado de ecommerce futuro.", "estatus" => "activo")
);

$dryruns = array();
foreach ($base as $segmento) {
  $dryruns[] = array(
    "codigo" => $segmento["codigo"],
    "resultado" => $modelo->segmentoCatalogoDryRun($segmento)
  );
}

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "segmentos_actuales" => $actuales,
  "segmentos_base_sugeridos" => $base,
  "dryruns_base" => $dryruns,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_segmentos" => true,
    "no_asigna_clientes" => true,
    "no_asigna_listas" => true,
    "no_modifica_ventas_pasadas" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
