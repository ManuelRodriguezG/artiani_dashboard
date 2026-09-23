<?php
/**
 * Marca destinos SEO pendientes como OK si responden correctamente en frontend.
 * Version IA: GPT-5 Codex, 2026-09-23.
 * Proposito: acelerar verificacion manual de "Destino OK" sin marcar origenes 301.
 * Impacto: escribe solo en `erp_ecommerce_seo_verificaciones`; no cambia redirecciones ni sitemap.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

class UatEcommerceDestinosOkHttp extends CRUD
{
  public function db()
  {
    return $this->getConexion();
  }
}

$frontend = isset($argv[1]) && trim((string)$argv[1]) !== "" ? rtrim(trim((string)$argv[1]), "/") : "https://prueba.artiani.com.mx";
$limite = isset($argv[2]) ? max(1, min(5000, intval($argv[2]))) : 5000;
$concurrencia = isset($argv[3]) ? max(1, min(40, intval($argv[3]))) : 18;
$aplicar = in_array("--aplicar", $argv, true);

$helper = new UatEcommerceDestinosOkHttp();
$db = $helper->db();
$modelo = new EcommerceCatalogoPublico();

if (!$db) {
  fwrite(STDERR, "Sin conexion a BD.\n");
  exit(1);
}

$pendientes = listarDestinosPendientes($db, $frontend, $limite);
$verificados = verificarDestinos($pendientes, $concurrencia);

$marcados = array();
$omitidos = array();
foreach ($verificados as $item) {
  if ($item["ok"] && $aplicar) {
    $respuesta = $modelo->seoVerificacionGuardarInterna(array(
      "clave" => $item["clave"],
      "tipo" => "regla_destino",
      "path" => $item["to"],
      "url_origen" => $item["url"],
      "url_destino" => "",
      "status_esperado" => 200,
      "resultado_http" => "ok",
      "status_http" => $item["status"],
      "destino_status_http" => null,
      "probada" => 1,
      "observaciones" => "Marcado automaticamente tras verificacion HTTP staging " . date("Y-m-d H:i:s"),
    ), 0);
    if (!empty($respuesta["error"])) {
      $item["guardar_error"] = isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "No se pudo guardar";
      $omitidos[] = $item;
    } else {
      $marcados[] = $item;
    }
  } elseif ($item["ok"]) {
    $marcados[] = $item;
  } else {
    $omitidos[] = $item;
  }
}

$salida = array(
  "frontend" => $frontend,
  "modo" => $aplicar ? "aplicado" : "dry_run",
  "pendientes_destino" => count($pendientes),
  "destinos_ok" => count(array_filter($verificados, function ($i) { return !empty($i["ok"]); })),
  "marcados" => count($marcados),
  "omitidos" => count($omitidos),
  "omitidos_muestra" => array_slice(array_map(function ($i) {
    return array(
      "from" => $i["from"],
      "to" => $i["to"],
      "status" => $i["status"],
      "problema" => $i["problema"],
      "error" => $i["error"],
    );
  }, $omitidos), 0, 80),
  "marcados_muestra" => array_slice(array_map(function ($i) {
    return array("from" => $i["from"], "to" => $i["to"], "status" => $i["status"]);
  }, $marcados), 0, 80),
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function listarDestinosPendientes(PDO $db, $frontend, $limite)
{
  $verificados = array();
  $stmtV = $db->query("SELECT clave FROM erp_ecommerce_seo_verificaciones WHERE tipo='regla_destino' AND probada=1 LIMIT 10000");
  foreach ($stmtV->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $verificados[(string)$row["clave"]] = true;
  }

  $stmt = $db->prepare("SELECT url_origen, url_destino, status_code, tipo
    FROM erp_ecommerce_seo_redirecciones
    WHERE activo=1 AND status_code IN (301,302,308) AND COALESCE(url_destino, '') <> ''
    ORDER BY fecha_actualizacion DESC, url_origen ASC
    LIMIT :limite");
  $stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
  $stmt->execute();

  $items = array();
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $from = normalizarPathSeo($row["url_origen"]);
    $to = normalizarPathSeo($row["url_destino"]);
    $status = intval($row["status_code"]);
    if ($from === "" || $to === "" || $from === $to || strpos($to, "/ecommercePublico") === 0) {
      continue;
    }
    $clave = substr("regla|" . $from . "|" . $status . "|" . $to, 0, 255);
    if (isset($verificados[$clave])) {
      continue;
    }
    $items[] = array(
      "clave" => $clave,
      "from" => $from,
      "to" => $to,
      "status_esperado" => $status,
      "url" => $frontend . $to,
    );
  }
  return $items;
}

function verificarDestinos($items, $concurrencia)
{
  if (!function_exists("curl_multi_init")) {
    return array_map("verificarDestinoSimple", $items);
  }

  $mh = curl_multi_init();
  $pendientes = $items;
  $activos = array();
  $out = array();

  while (!empty($pendientes) || !empty($activos)) {
    while (!empty($pendientes) && count($activos) < $concurrencia) {
      $item = array_shift($pendientes);
      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $item["url"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 4,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => "Artiani-SEO-DestinoOK/1.0",
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
      $out[] = evaluarDestino($item, intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE)), $body, curl_error($ch));
      curl_multi_remove_handle($mh, $ch);
      curl_close($ch);
      unset($activos[$key]);
    }

    if ($running) {
      curl_multi_select($mh, 0.25);
    }
  }

  curl_multi_close($mh);
  return $out;
}

function verificarDestinoSimple($item)
{
  $ctx = stream_context_create(array(
    "http" => array("timeout" => 15, "header" => "User-Agent: Artiani-SEO-DestinoOK/1.0\r\n"),
    "ssl" => array("verify_peer" => false, "verify_peer_name" => false),
  ));
  $body = @file_get_contents($item["url"], false, $ctx);
  $status = 0;
  if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
    $status = intval($m[1]);
  }
  return evaluarDestino($item, $status, $body === false ? "" : (string)$body, $body === false ? "http_error" : "");
}

function evaluarDestino($item, $status, $body, $error)
{
  $texto = strtolower(strip_tags(substr((string)$body, 0, 180000)));
  $noEncontrado = contieneAlguna($texto, array(
    "producto publico no encontrado",
    "producto público no encontrado",
    "producto no encontrado",
    "pagina no encontrada",
    "página no encontrada",
    "not found",
  ));
  $problema = "";
  if ($error !== "") {
    $problema = "error_http";
  } elseif ($status < 200 || $status >= 400) {
    $problema = "status_no_ok";
  } elseif ($noEncontrado) {
    $problema = "contenido_no_encontrado";
  }
  $item["status"] = $status;
  $item["error"] = $error;
  $item["problema"] = $problema;
  $item["ok"] = $problema === "";
  return $item;
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
  if ($path === "") { return ""; }
  if (preg_match('#^https?://#i', $path)) {
    $parts = parse_url($path);
    $path = isset($parts["path"]) ? $parts["path"] : "/";
  }
  $path = "/" . ltrim($path, "/");
  $path = preg_replace('#/+#', '/', $path);
  return $path;
}
