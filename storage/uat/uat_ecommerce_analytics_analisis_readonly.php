<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-29.
 * Proposito: validar endpoint interno de analisis detallado Ecommerce Analytics sin escribir BD.
 * Impacto: comprueba sesiones, page views, productos, busquedas, WhatsApp y eventos.
 * Contrato: solo SELECT; no expone session_id completo ni modifica datos productivos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$analytics = new EcommerceAnalyticsErp();
$secciones = array("sesiones", "page_views", "productos", "busquedas", "whatsapp", "eventos");
$salida = array(
  "ok" => true,
  "modo" => "read-only",
  "secciones" => array(),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_expone_session_id_completo" => true,
    "no_pii" => true,
    "no_stock_exacto" => true
  )
);

foreach ($secciones as $seccion) {
  $respuesta = $analytics->analyticsAnalisisInterno(array(
    "seccion" => $seccion,
    "desde" => date("Y-m-d", strtotime("-7 days")),
    "hasta" => date("Y-m-d"),
    "limite" => 5
  ));
  $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
  $salida["secciones"][$seccion] = array(
    "error" => !empty($respuesta["error"]),
    "configurado" => !empty($depurar["configurado"]),
    "columnas" => isset($depurar["columnas"]) ? $depurar["columnas"] : array(),
    "items" => isset($depurar["items"]) && is_array($depurar["items"]) ? count($depurar["items"]) : 0,
    "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array()
  );
  if (!empty($respuesta["error"])) { $salida["ok"] = false; }
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
