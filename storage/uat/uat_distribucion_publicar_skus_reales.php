<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once RUTA_APP . "/modelos/DistribucionCatalogoApi.php";

$autorizar = isset($argv[1]) ? (string) $argv[1] : "";
$limite = isset($argv[2]) ? max(1, min(20, intval($argv[2]))) : 6;

if ($autorizar !== "DISTRIBUCION_PUBLICAR_SKUS") {
  echo json_encode(array(
    "error" => true,
    "mensaje" => "Token de autorizacion invalido",
    "uso" => "php storage/uat/uat_distribucion_publicar_skus_reales.php DISTRIBUCION_PUBLICAR_SKUS 6"
  ), JSON_PRETTY_PRINT);
  exit(1);
}

$catalogo = new DistribucionCatalogoApi();
$candidatos = $catalogo->skusPublicablesInternos(array("limite" => $limite, "solo_con_precio" => 1));
$items = isset($candidatos["depurar"]["items"]) && is_array($candidatos["depurar"]["items"]) ? $candidatos["depurar"]["items"] : array();
$publicados = array();

foreach ($items as $item) {
  $activo = in_array((string) $item["canal_estatus"], array("activo", "publicado", "aprobado"), true) && intval($item["sincronizar_catalogo"]) === 1;
  if ($activo) {
    $publicados[] = array(
      "id_sku" => intval($item["id_sku"]),
      "sku" => $item["sku"],
      "omitido" => true,
      "motivo" => "ya_activo",
      "id_externo" => $item["id_externo"]
    );
    continue;
  }
  $respuesta = $catalogo->publicarSkuInterno(array("id_sku" => intval($item["id_sku"])), 0);
  $publicados[] = array(
    "id_sku" => intval($item["id_sku"]),
    "sku" => $item["sku"],
    "respuesta" => $respuesta
  );
}

echo json_encode(array(
  "error" => false,
  "mensaje" => "Publicacion permanente Distribucion procesada",
  "limite" => $limite,
  "total_candidatos" => count($items),
  "publicados" => $publicados
), JSON_PRETTY_PRINT);
