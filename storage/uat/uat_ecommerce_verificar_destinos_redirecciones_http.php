<?php
/**
 * Verificacion HTTP masiva de destinos de redirecciones SEO.
 * Version IA: GPT-5 Codex, 2026-09-22.
 * Proposito: detectar destinos 301 que no responden en frontend staging o muestran producto no encontrado.
 * Impacto: solo lectura; no modifica BD ni reglas SEO. Puede escribir un reporte JSON local si se indica.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatEcommerceVerificarDestinosRedireccionesHttp extends CRUD
{
  public function db()
  {
    return $this->getConexion();
  }
}

$base = isset($argv[1]) && trim((string)$argv[1]) !== "" ? rtrim(trim((string)$argv[1]), "/") : "https://prueba.artiani.com.mx";
$limite = isset($argv[2]) ? max(1, min(5000, intval($argv[2]))) : 5000;
$concurrencia = isset($argv[3]) ? max(1, min(40, intval($argv[3]))) : 18;
$guardar = in_array("--guardar", $argv, true);

$db = (new UatEcommerceVerificarDestinosRedireccionesHttp())->db();
$stmt = $db->prepare("SELECT url_origen, url_destino, status_code, tipo, motivo
  FROM erp_ecommerce_seo_redirecciones
  WHERE activo=1 AND status_code IN (301,302,308) AND COALESCE(url_destino, '') <> ''
  ORDER BY url_destino ASC, url_origen ASC
  LIMIT :limite");
$stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
$stmt->execute();

$porDestino = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $destino = normalizarPathSeo($row["url_destino"]);
  if ($destino === "" || $destino === "/" || strpos($destino, "/ecommercePublico") === 0) {
    continue;
  }
  if (!isset($porDestino[$destino])) {
    $porDestino[$destino] = array("path" => $destino, "url" => $base . $destino, "origenes" => array());
  }
  $porDestino[$destino]["origenes"][] = array(
    "from" => normalizarPathSeo($row["url_origen"]),
    "status" => intval($row["status_code"]),
    "tipo" => (string)$row["tipo"],
    "motivo" => (string)$row["motivo"],
  );
}

$items = array_values($porDestino);
$resultados = verificarUrls($items, $concurrencia);
$problemas = array();
foreach ($resultados as $item) {
  if (!empty($item["problema"])) {
    $problemas[] = $item;
  }
}

$salida = array(
  "frontend" => $base,
  "total_destinos_unicos" => count($items),
  "total_problemas" => count($problemas),
  "problemas" => $problemas,
  "resumen" => resumenProblemas($problemas),
);

if ($guardar) {
  $dir = __DIR__ . "/../tmp";
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  $archivo = $dir . "/ecommerce_destinos_redirecciones_http_" . date("Ymd_His") . ".json";
  file_put_contents($archivo, json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  $salida["reporte_json"] = $archivo;
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function verificarUrls($items, $concurrencia)
{
  if (!function_exists("curl_multi_init")) {
    $out = array();
    foreach ($items as $item) {
      $out[] = verificarUrlSimple($item);
    }
    return $out;
  }

  $mh = curl_multi_init();
  $pendientes = $items;
  $activos = array();
  $resultados = array();

  while (!empty($pendientes) || !empty($activos)) {
    while (!empty($pendientes) && count($activos) < $concurrencia) {
      $item = array_shift($pendientes);
      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $item["url"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 4,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERAGENT => "Artiani-SEO-UAT/1.0",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
      ));
      curl_multi_add_handle($mh, $ch);
      $activos[(int)$ch] = array("handle" => $ch, "item" => $item);
    }

    do {
      $status = curl_multi_exec($mh, $running);
    } while ($status === CURLM_CALL_MULTI_PERFORM);

    while ($info = curl_multi_info_read($mh)) {
      $ch = $info["handle"];
      $key = (int)$ch;
      $item = $activos[$key]["item"];
      $body = (string) curl_multi_getcontent($ch);
      $resultados[] = evaluarRespuesta($item, $ch, $body);
      curl_multi_remove_handle($mh, $ch);
      curl_close($ch);
      unset($activos[$key]);
    }

    if ($running) {
      curl_multi_select($mh, 0.3);
    }
  }

  curl_multi_close($mh);
  return $resultados;
}

function verificarUrlSimple($item)
{
  $ctx = stream_context_create(array(
    "http" => array("timeout" => 12, "header" => "User-Agent: Artiani-SEO-UAT/1.0\r\n"),
    "ssl" => array("verify_peer" => false, "verify_peer_name" => false),
  ));
  $body = @file_get_contents($item["url"], false, $ctx);
  $status = 0;
  if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
    $status = intval($m[1]);
  }
  return evaluarContenido($item, $status, "", $body === false ? "" : (string)$body, $body === false ? "http_error" : "");
}

function evaluarRespuesta($item, $ch, $body)
{
  $status = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
  $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
  $error = curl_error($ch);
  return evaluarContenido($item, $status, $final, $body, $error);
}

function evaluarContenido($item, $status, $finalUrl, $body, $error)
{
  $texto = strtolower(strip_tags(substr((string)$body, 0, 160000)));
  $senalNoEncontrado = contieneAlguna($texto, array(
    "producto publico no encontrado",
    "producto público no encontrado",
    "producto no encontrado",
    "pagina no encontrada",
    "página no encontrada",
    "404",
    "not found",
  ));
  $problema = "";
  if ($error !== "") {
    $problema = "error_http";
  } elseif ($status < 200 || $status >= 400) {
    $problema = "status_no_ok";
  } elseif ($senalNoEncontrado) {
    $problema = "contenido_no_encontrado";
  }
  return array(
    "path" => $item["path"],
    "url" => $item["url"],
    "status" => $status,
    "final_url" => $finalUrl,
    "problema" => $problema,
    "error" => $error,
    "origenes" => $item["origenes"],
  );
}

function resumenProblemas($problemas)
{
  $resumen = array();
  foreach ($problemas as $p) {
    $clave = $p["problema"] ?: "desconocido";
    if (!isset($resumen[$clave])) {
      $resumen[$clave] = 0;
    }
    $resumen[$clave]++;
  }
  return $resumen;
}

function contieneAlguna($texto, $agujas)
{
  foreach ($agujas as $aguja) {
    if ($aguja !== "" && strpos($texto, $aguja) !== false) {
      return true;
    }
  }
  return false;
}

function normalizarPathSeo($path)
{
  $path = trim((string)$path);
  if ($path === "") {
    return "";
  }
  if (preg_match('#^https?://#i', $path)) {
    $partes = parse_url($path);
    $path = isset($partes["path"]) ? $partes["path"] : "/";
    if (isset($partes["query"]) && $partes["query"] !== "") {
      $path .= "?" . $partes["query"];
    }
  }
  $path = "/" . ltrim($path, "/");
  $path = preg_replace('#/+#', '/', $path);
  return $path;
}
