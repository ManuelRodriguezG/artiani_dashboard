<?php
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once __DIR__ . "/../../app/iniciador.php";
require_once RUTA_APP . "/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$ref = new ReflectionClass($modelo);
$metodoConexion = $ref->getMethod("getConexion");
$metodoConexion->setAccessible(true);
$db = $metodoConexion->invoke($modelo);

$stmt = $db->query("SELECT id_auditoria_evento, datos_antes FROM sys_auditoria_eventos WHERE accion='publicacion_lote_publicar' ORDER BY id_auditoria_evento DESC LIMIT 1");
$evento = $stmt->fetch(PDO::FETCH_ASSOC);
$datos = json_decode((string) $evento["datos_antes"], true);
$idSkus = isset($datos["id_skus"]) ? (string) $datos["id_skus"] : "";

$metodo = $ref->getMethod("normalizarIdsSkuLote");
$metodo->setAccessible(true);
$normalizados = $metodo->invoke($modelo, $idSkus);

echo json_encode(array(
  "id_auditoria_evento" => isset($evento["id_auditoria_evento"]) ? intval($evento["id_auditoria_evento"]) : null,
  "ids_en_request" => $idSkus === "" ? 0 : count(preg_split('/[\r\n,]+/', $idSkus)),
  "ids_normalizados" => count($normalizados),
  "incluye_1868" => in_array(1868, $normalizados, true),
  "primeros_5" => array_slice($normalizados, 0, 5),
  "ultimos_5" => array_slice($normalizados, -5)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
