<?php
$_SERVER["SERVER_NAME"] = "panel.com.local";
require __DIR__ . "/../../app/iniciador.php";
require __DIR__ . "/../../app/modelos/ComprasSugeridosCompraErp.php";
$m = new ComprasSugeridosCompraErp();
foreach (array(3,9,1) as $id) {
  $r = $m->productosProveedor(array("id_proveedor"=>$id,"q"=>"","limite"=>10));
  echo "PROV $id error=" . ($r["error"] ? "1" : "0") . " msg=" . $r["mensaje"] . " total=" . (isset($r["depurar"]["total"]) ? $r["depurar"]["total"] : "na") . "\n";
  if (!empty($r["depurar"]["items"][0])) echo json_encode($r["depurar"]["items"][0], JSON_UNESCAPED_UNICODE) . "\n";
}