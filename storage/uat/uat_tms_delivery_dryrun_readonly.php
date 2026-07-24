<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: validar solicitud TMS Delivery en dry-run sin crear servicio.
 * Impacto: prueba contrato logistico independiente de Ventas, productos y garantias.
 * Contrato: read-only/dry-run; no inserta servicio, no mueve inventario y no cancela ventas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$modelo = new TmsDelivery();

$datosValidos = array(
  "tipo_servicio" => "entrega_express",
  "prioridad" => "express",
  "estatus_cobro" => "por_cobrar",
  "solicitado_por_modulo" => "manual",
  "solicitado_por_tipo" => "solicitud_manual",
  "cliente_nombre_snapshot" => "Cliente TMS UAT",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion UAT sin escritura",
  "fecha_programada" => date("Y-m-d"),
  "ventana_inicio" => "12:00",
  "ventana_fin" => "13:00",
  "precio_cobrado" => 80,
  "detalle" => array(array(
    "descripcion_snapshot" => "Paquete UAT Delivery",
    "cantidad" => 1,
    "requiere_cuidado_especial" => 1
  ))
);
$listado = $modelo->listarServicios(array("limite" => 10));
$schemaPendiente = isset($listado["depurar"]["schema_pendiente"]) && $listado["depurar"]["schema_pendiente"] === true;

$respuesta = array(
  "error" => false,
  "tipo" => "success",
  "mensaje" => "Validacion read-only/dry-run TMS Delivery",
  "depurar" => array(
    "catalogos" => $modelo->catalogosTms(),
    "servicios_listado" => $listado,
    "solicitud_valida" => $modelo->servicioDryRun($datosValidos),
    "guardar_bloqueado_por_schema" => $schemaPendiente ? $modelo->guardarServicio($datosValidos, 0) : array(
      "error" => false,
      "tipo" => "info",
      "mensaje" => "Prueba de guardado omitida porque el esquema ya existe y este UAT debe seguir read-only",
      "depurar" => array("schema_pendiente" => false)
    ),
    "solicitud_bloqueada" => $modelo->servicioDryRun(array(
      "tipo_servicio" => "entrega_local",
      "prioridad" => "normal",
      "estatus_cobro" => "bonificada",
      "cliente_nombre_snapshot" => "Cliente sin motivo bonificacion",
      "direccion_snapshot" => "Direccion UAT"
    )),
    "acciones_contrato" => $modelo->accionesContratoReadOnly(),
    "contrato" => array(
      "read_only" => true,
      "no_crea_servicio" => true,
      "no_confirma_venta" => true,
      "no_cancela_venta" => true,
      "no_mueve_inventario" => true,
      "no_decide_garantia" => true
    )
  )
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
