<?php

$_SERVER["SERVER_NAME"] = "sys.artiani.com.mx";
chdir(dirname(__DIR__, 2) . "/public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";
$modelo = new EcommerceCatalogoPublico();
$config = $modelo->metaFeedConfiguracionInterna();
$feed = $modelo->feedMetaPublico();
$lineas = preg_split('/\r\n|\r|\n/', trim((string) $feed["body"]));
echo json_encode(array(
  "config_error" => !empty($config["error"]),
  "total_excluidos" => intval($config["depurar"]["total_skus_excluidos"] ?? 0),
  "feed_status" => intval($feed["status"]),
  "filas_feed" => max(0, count($lineas) - 1),
  "encabezado" => $lineas[0] ?? "",
  "primera_fila" => $lineas[1] ?? ""
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
