<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar enlaces cruzados entre pantallas operativas de Ventas/POS.
 * Impacto: mejora UX de supervisor/cajero sin tocar BD ni permisos.
 * Contrato: read-only; no invoca HTTP ni modifica datos.
 */

$base = dirname(__DIR__, 2);
$vistas = array(
    "caja_turnos" => array(
        "archivo" => "app/vistas/paginas/apps/erp/ventas/caja_turnos.php",
        "rutas" => array("/ventas/mostrar", "/ventas/caja_movimientos", "/ventas/caja_evidencias", "/ventas/reportes", "/ventas/pos_configuracion", "/ventas/pos")
    ),
    "caja_movimientos" => array(
        "archivo" => "app/vistas/paginas/apps/erp/ventas/caja_movimientos.php",
        "rutas" => array("/ventas/mostrar", "/ventas/caja_turnos", "/ventas/caja_evidencias", "/ventas/reportes", "/ventas/pos_configuracion", "/ventas/pos")
    ),
    "caja_evidencias" => array(
        "archivo" => "app/vistas/paginas/apps/erp/ventas/caja_evidencias.php",
        "rutas" => array("/ventas/mostrar", "/ventas/caja_turnos", "/ventas/caja_movimientos", "/ventas/reportes", "/ventas/pos_configuracion", "/ventas/pos")
    ),
    "devoluciones" => array(
        "archivo" => "app/vistas/paginas/apps/erp/ventas/devoluciones.php",
        "rutas" => array("/ventas/mostrar", "/ventas/reportes", "/ventas/pos")
    ),
    "reportes" => array(
        "archivo" => "app/vistas/paginas/apps/erp/ventas/reportes.php",
        "rutas" => array("/ventas/mostrar", "/ventas/caja_turnos", "/ventas/caja_movimientos", "/ventas/pos_configuracion", "/ventas/pos")
    )
);

$bloqueos = array();
$detalle = array();
foreach ($vistas as $clave => $config) {
    $rutaArchivo = $base . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $config["archivo"]);
    $contenido = is_file($rutaArchivo) ? file_get_contents($rutaArchivo) : "";
    $detalle[$clave] = array(
        "archivo" => $config["archivo"],
        "existe" => is_file($rutaArchivo),
        "rutas" => array()
    );
    if (!is_file($rutaArchivo)) {
        $bloqueos[] = "Falta vista " . $config["archivo"];
        continue;
    }
    foreach ($config["rutas"] as $ruta) {
        $ok = strpos($contenido, 'href="' . $ruta . '"') !== false || strpos($contenido, "href='" . $ruta . "'") !== false || strpos($contenido, $ruta) !== false;
        $detalle[$clave]["rutas"][$ruta] = $ok;
        if (!$ok) {
            $bloqueos[] = "Falta enlace " . $ruta . " en " . $config["archivo"];
        }
    }
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_crosslinks_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "vistas_revisadas" => count($vistas),
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_modifica_permisos" => true
    )
), empty($bloqueos) ? 0 : 1);

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
