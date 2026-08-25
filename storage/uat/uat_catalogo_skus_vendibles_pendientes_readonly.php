<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-21
 * Proposito: probar auditorias read-only de SKUs vendibles pendientes para Listas/Rentabilidad.
 * Impacto: UAT local; no escribe BD, no crea notificaciones ni modifica precios/costos.
 * Contrato: usar `--modo=precio|costo`, `--limite=N` y opcional `--q=texto`.
 */

$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpDatos.php";

$opciones = getopt("", array("modo::", "limite::", "q::"));
$modo = isset($opciones["modo"]) ? trim((string) $opciones["modo"]) : "precio";
$modelo = new CatalogoErpDatos();

if ($modo === "costo") {
  $respuesta = $modelo->auditarSkusVendiblesSinCostoResoluble($opciones);
} else {
  $respuesta = $modelo->auditarSkusVendiblesSinPrecioLista($opciones);
}

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

