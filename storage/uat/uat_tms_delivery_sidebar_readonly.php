<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: validar navegacion/sidebar TMS Delivery sin ejecutar rutas web.
 * Impacto: TMS Delivery; confirma que los accesos del modulo tienen metodo, vista y permiso.
 * Contrato: read-only; no consulta ni modifica BD.
 */

$root = realpath(__DIR__ . "/../..");
$sidebar = $root . "/app/vistas/includes/header/sidebar.php";
$controlador = $root . "/app/controladores/Tms.php";

$items = array(
  array("titulo" => "Bandeja TMS", "ruta" => "/tms/servicios", "permiso" => "tms.ver", "metodo" => "servicios", "vista" => "app/vistas/paginas/apps/tms/servicios.php"),
  array("titulo" => "Operacion y rutas", "ruta" => "/tms/operacion", "permiso" => "tms.operar", "metodo" => "operacion", "vista" => "app/vistas/paginas/apps/tms/operacion.php"),
  array("titulo" => "Costos logisticos", "ruta" => "/tms/costos", "permiso" => "tms.costos", "metodo" => "costos", "vista" => "app/vistas/paginas/apps/tms/costos.php"),
  array("titulo" => "Reportes delivery", "ruta" => "/tms/reportes", "permiso" => "tms.reportes", "metodo" => "reportes", "vista" => "app/vistas/paginas/apps/tms/reportes.php"),
  array("titulo" => "Configuracion delivery", "ruta" => "/tms/configuracion", "permiso" => "tms.autorizar", "metodo" => "configuracion", "vista" => "app/vistas/paginas/apps/tms/configuracion.php")
);

$sidebarContenido = file_exists($sidebar) ? file_get_contents($sidebar) : "";
$controladorContenido = file_exists($controlador) ? file_get_contents($controlador) : "";
$resultados = array();
$ok = true;

foreach ($items as $item) {
  $vista = $root . "/" . $item["vista"];
  $resultado = array(
    "titulo" => $item["titulo"],
    "ruta" => $item["ruta"],
    "permiso" => $item["permiso"],
    "sidebar_titulo" => strpos($sidebarContenido, "'" . $item["titulo"] . "'") !== false,
    "sidebar_ruta" => strpos($sidebarContenido, "'" . $item["ruta"] . "'") !== false,
    "sidebar_permiso" => strpos($sidebarContenido, "'" . $item["permiso"] . "'") !== false,
    "controlador_metodo" => preg_match('/public function\s+' . preg_quote($item["metodo"], '/') . '\s*\(/', $controladorContenido) === 1,
    "vista_existe" => file_exists($vista)
  );
  $resultado["ok"] = $resultado["sidebar_titulo"]
    && $resultado["sidebar_ruta"]
    && $resultado["sidebar_permiso"]
    && $resultado["controlador_metodo"]
    && $resultado["vista_existe"];
  if (!$resultado["ok"]) {
    $ok = false;
  }
  $resultados[] = $resultado;
}

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "mensaje" => $ok ? "Sidebar TMS consistente" : "Sidebar TMS con pendientes",
  "depurar" => array(
    "grupo" => "TMS > Delivery",
    "items" => $resultados,
    "no_escritura_bd" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
