<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: diagnosticar entorno local requerido para validar API ecommerce publico.
 * Impacto: separa fallas de XAMPP/MySQL/Apache de fallas del contrato ecommerce.
 * Contrato: read-only; no inicia servicios, no repara tablas y no escribe BD.
 */

$opciones = getopt("", array(
  "base::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$mysql = probarTcpEntorno("127.0.0.1", 3306, 2);
$http = probarHttpEntorno($base . "/ecommercePublico/estado", 5);
$mysqlLog = leerLogEntorno("C:\\xampp\\mysql\\data\\mysql_error.log", 80);
$apacheLog = leerLogEntorno("C:\\xampp\\apache\\logs\\error.log", 40);

$bloqueos = array();
$advertencias = array();
if (!$mysql["ok"]) {
  $bloqueos[] = "mysql_no_acepta_conexion_tcp_3306";
}
if (!$http["json_valido"]) {
  $bloqueos[] = "api_http_no_responde_json";
}
if (contieneEntorno($mysqlLog["ultimas_lineas"], "mysql.plugin") || contieneEntorno($mysqlLog["ultimas_lineas"], "corrupt")) {
  $advertencias[] = "mysql_log_indica_tabla_sistema_corrupta_revisar_si_reaparece";
  if (!$mysql["ok"] || !$http["json_valido"]) {
    $bloqueos[] = "mysql_log_indica_tabla_sistema_corrupta";
  }
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base_url" => $base,
  "mysql_tcp" => $mysql,
  "api_http_estado" => $http,
  "logs" => array(
    "mysql_error_log" => $mysqlLog,
    "apache_error_log" => $apacheLog
  ),
  "bloqueos" => $bloqueos,
  "advertencias" => $advertencias,
  "recomendacion" => empty($bloqueos)
    ? "Entorno local listo para validar API ecommerce."
    : "Resolver salud de XAMPP/MySQL antes de interpretar fallas de contratos o lote inicial.",
  "guardrails" => array(
    "no_repara_tablas" => true,
    "no_inicia_servicios" => true,
    "no_escribe_bd" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function probarTcpEntorno($host, $puerto, $timeout) {
  $errno = 0;
  $errstr = "";
  $inicio = microtime(true);
  $socket = @fsockopen($host, $puerto, $errno, $errstr, $timeout);
  $ms = round((microtime(true) - $inicio) * 1000, 2);
  $ok = is_resource($socket);
  if ($ok) {
    fclose($socket);
  }
  return array(
    "host" => $host,
    "puerto" => $puerto,
    "ok" => $ok,
    "errno" => $errno,
    "error" => $errstr,
    "duracion_ms" => $ms
  );
}

function probarHttpEntorno($url, $timeout) {
  $context = stream_context_create(array(
    "http" => array(
      "method" => "GET",
      "header" => "Accept: application/json\r\n",
      "ignore_errors" => true,
      "timeout" => $timeout
    )
  ));
  $inicio = microtime(true);
  $raw = @file_get_contents($url, false, $context);
  $ms = round((microtime(true) - $inicio) * 1000, 2);
  $json = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "json_valido" => is_array($json),
    "tipo" => is_array($json) && isset($json["tipo"]) ? $json["tipo"] : "",
    "mensaje" => is_array($json) && isset($json["mensaje"]) ? $json["mensaje"] : "",
    "raw_inicio" => substr((string) $raw, 0, 120),
    "duracion_ms" => $ms
  );
}

function leerLogEntorno($ruta, $lineas) {
  if (!is_readable($ruta)) {
    return array("ruta" => $ruta, "legible" => false, "ultimas_lineas" => array());
  }
  $contenido = file($ruta, FILE_IGNORE_NEW_LINES);
  if (!is_array($contenido)) {
    $contenido = array();
  }
  return array(
    "ruta" => $ruta,
    "legible" => true,
    "ultimas_lineas" => array_slice($contenido, -1 * intval($lineas))
  );
}

function contieneEntorno($lineas, $texto) {
  foreach ($lineas as $linea) {
    if (stripos((string) $linea, $texto) !== false) {
      return true;
    }
  }
  return false;
}
