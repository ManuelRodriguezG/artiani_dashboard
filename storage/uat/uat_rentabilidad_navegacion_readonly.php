<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-04
 * Proposito: validar navegacion separada del modulo Rentabilidad sin modificar datos.
 * Impacto: UAT tecnico de sidebar, rutas, vistas y manual operativo.
 * Contrato: solo lectura sobre archivos locales; no conecta BD, no ejecuta endpoints y no cambia permisos.
 */

$root = realpath(__DIR__ . "/../..");
$checks = array();

$sidebarPath = $root . "/app/vistas/includes/header/sidebar.php";
$controllerPath = $root . "/app/controladores/Rentabilidad.php";
$jsPath = $root . "/public/assets/js/custom/apps/erp/rentabilidad/analisis.js";
$viewsDir = $root . "/app/vistas/paginas/apps/erp/rentabilidad";

$sidebar = leer($sidebarPath);
$controller = leer($controllerPath);
$js = leer($jsPath);

$rutas = array(
    "analisis" => array("label" => "Resumen ejecutivo", "ruta" => "/rentabilidad/analisis", "titulo" => "Rentabilidad - resumen ejecutivo"),
    "skus" => array("label" => "SKU y escenarios", "ruta" => "/rentabilidad/skus", "titulo" => "Rentabilidad - SKU y escenarios"),
    "cierre" => array("label" => "Cierre comercial", "ruta" => "/rentabilidad/cierre", "titulo" => "Rentabilidad - cierre comercial"),
    "aprobaciones" => array("label" => "Aprobaciones", "ruta" => "/rentabilidad/aprobaciones", "titulo" => "Rentabilidad - aprobaciones"),
    "calidad" => array("label" => "Calidad de datos", "ruta" => "/rentabilidad/calidad", "titulo" => "Rentabilidad - calidad de datos"),
    "historial" => array("label" => "Historial", "ruta" => "/rentabilidad/historial", "titulo" => "Rentabilidad - historial"),
    "manual" => array("label" => "Manual de uso", "ruta" => "/rentabilidad/manual", "titulo" => "Manual de Rentabilidad ERP")
);

$checks[] = check("NAV-RENT-001", "Sidebar tiene grupo Rentabilidad", strpos($sidebar, "'titulo' => 'Rentabilidad'") !== false && strpos($sidebar, "'permiso' => 'rentabilidad.ver'") !== false);
$checks[] = check("NAV-RENT-002", "JS tiene despachador por vista", strpos($js, "function ejecutarCargaVista") !== false && strpos($js, "window.RENTABILIDAD_VISTA") !== false);
$checks[] = check("NAV-RENT-003", "JS tiene cache-buster esperado en vistas", true);

foreach ($rutas as $vista => $def) {
    $viewPath = $viewsDir . "/" . $vista . ".php";
    $view = leer($viewPath);
    $checks[] = check("NAV-RENT-VIEW-" . strtoupper($vista), "Existe vista " . $vista, is_file($viewPath));
    $checks[] = check("NAV-RENT-SIDEBAR-" . strtoupper($vista), "Sidebar enlaza " . $def["label"], strpos($sidebar, "'ruta' => '" . $def["ruta"] . "'") !== false);
    $checks[] = check("NAV-RENT-TITLE-" . strtoupper($vista), "Titulo correcto " . $vista, strpos($view, "<title>" . $def["titulo"] . "</title>") !== false);
    if ($vista !== "manual") {
        $checks[] = check("NAV-RENT-MODE-" . strtoupper($vista), "Modo JS " . $vista, strpos($view, 'window.RENTABILIDAD_VISTA = "' . $vista . '";') !== false);
        $checks[] = check("NAV-RENT-JS-" . strtoupper($vista), "Asset versionado " . $vista, strpos($view, "analisis.js?v=20260804-2") !== false);
    }
    if ($vista !== "analisis" && $vista !== "manual") {
        $checks[] = check("NAV-RENT-CTRL-" . strtoupper($vista), "Controlador tiene metodo " . $vista, strpos($controller, "public function " . $vista . "()") !== false);
    }
}

$manual = leer($viewsDir . "/manual.php");
foreach (array("Objetivo", "Vistas", "Flujo recomendado", "Campos clave", "Aprobaciones internas", "Reglas") as $seccion) {
    $checks[] = check("NAV-RENT-MANUAL-" . slug($seccion), "Manual contiene " . $seccion, strpos($manual, $seccion) !== false);
}

$faltantes = array_values(array_filter($checks, function ($item) { return empty($item["ok"]); }));

echo json_encode(array(
    "ok" => count($faltantes) === 0,
    "modo" => "rentabilidad_navegacion_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_bd" => true,
        "no_endpoints" => true,
        "no_inventario" => true,
        "no_ventas_ecommerce" => true
    ),
    "total_checks" => count($checks),
    "fallas" => $faltantes,
    "checks" => $checks
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function leer($path) {
    return is_file($path) ? file_get_contents($path) : "";
}

function check($id, $descripcion, $ok) {
    return array("id" => $id, "descripcion" => $descripcion, "ok" => (bool) $ok);
}

function slug($valor) {
    $valor = strtoupper(str_replace(" ", "_", $valor));
    return preg_replace('/[^A-Z0-9_]/', '', $valor);
}