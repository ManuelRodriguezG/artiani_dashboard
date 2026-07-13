<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: generar plan read-only de configuracion inicial ecommerce publico.
 * Impacto: prepara WhatsApp, CORS y textos visibles para el frontend externo sin escribir BD.
 * Contrato: no ejecuta SQL, no configura canal, no crea publicaciones y no toca inventario.
 */

$opciones = getopt("", array(
  "whatsapp::",
  "cors::",
  "url::",
  "moneda::",
  "mensaje::"
));

$valores = array();
if (isset($opciones["whatsapp"])) {
  $valores["whatsapp_numero_principal"] = trim((string) $opciones["whatsapp"]);
}
if (isset($opciones["cors"])) {
  $valores["cors_origenes_permitidos"] = trim((string) $opciones["cors"]);
}
if (isset($opciones["url"])) {
  $valores["url_sitio_publico"] = trim((string) $opciones["url"]);
}
if (isset($opciones["moneda"])) {
  $valores["moneda_default"] = trim((string) $opciones["moneda"]);
}
if (isset($opciones["mensaje"])) {
  $valores["whatsapp_mensaje_base"] = trim((string) $opciones["mensaje"]);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$respuesta = $modelo->planConfiguracionInicial($valores);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "entrada" => array(
    "whatsapp" => isset($valores["whatsapp_numero_principal"]) ? $valores["whatsapp_numero_principal"] : "",
    "cors" => isset($valores["cors_origenes_permitidos"]) ? $valores["cors_origenes_permitidos"] : "",
    "url" => isset($valores["url_sitio_publico"]) ? $valores["url_sitio_publico"] : "",
    "moneda" => isset($valores["moneda_default"]) ? $valores["moneda_default"] : "MXN"
  ),
  "respuesta" => $respuesta,
  "siguiente_paso" => "Revisar bloqueos_datos_reales y SQL. Aplicar configuracion solo despues de DDL y autorizacion explicita."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
