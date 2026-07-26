<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: auditar lenguaje visible de Ventas/POS para evitar textos tecnicos de UAT en pantallas operativas.
 * Impacto: protege UX de POS, configuracion, tablero, caja, reportes, devoluciones, pedidos y checador.
 * Contrato: read-only; no consulta BD, no escribe BD, no invoca endpoints.
 */

$root = realpath(__DIR__ . "/../..");
$archivos = array(
    "app/vistas/paginas/apps/erp/ventas/pos.php",
    "app/vistas/paginas/apps/erp/ventas/manual_pos.php",
    "app/vistas/paginas/apps/erp/ventas/listado.php",
    "app/vistas/paginas/apps/erp/ventas/caja_turnos.php",
    "app/vistas/paginas/apps/erp/ventas/caja_movimientos.php",
    "app/vistas/paginas/apps/erp/ventas/devoluciones.php",
    "app/vistas/paginas/apps/erp/ventas/pedidos.php",
    "app/vistas/paginas/apps/erp/ventas/pos_configuracion.php",
    "app/vistas/paginas/apps/erp/ventas/reportes.php",
    "app/vistas/paginas/apps/erp/ventas/checador_precios.php",
    "public/assets/js/custom/apps/erp/ventas/pos.js",
    "public/assets/js/custom/apps/erp/ventas/listado.js",
    "public/assets/js/custom/apps/erp/ventas/caja_turnos.js",
    "public/assets/js/custom/apps/erp/ventas/caja_movimientos.js",
    "public/assets/js/custom/apps/erp/ventas/devoluciones.js",
    "public/assets/js/custom/apps/erp/ventas/pedidos.js",
    "public/assets/js/custom/apps/erp/ventas/pos_configuracion.js",
    "public/assets/js/custom/apps/erp/ventas/reportes.js",
    "public/assets/js/custom/apps/erp/ventas/checador_precios.js"
);

$prohibidos = array(
    "Consulta read-only",
    "Read-only:",
    "read-only:",
    "Dry-run",
    "dry-run",
    "Simular devolucion",
    "simular una devolucion",
    "Contrato: backend",
    "Contrato: bloqueo",
    "AUTORIZO ",
    "usando respaldo",
    "respaldo UAT POS vigente"
);

$permitidosInternos = array(
    "id=\"pos_caja_readiness_",
    "pos_readiness_readonly_erp",
    "function consultarReadiness",
    "function renderReadiness",
    "function readinessKpi",
    "pos_caja_apertura_dryrun",
    "pos_caja_corte_dryrun",
    "pos_mov_simular",
    "dev_simular",
    "ped_reserva_simular",
    "ped_abono_simular",
    "pos_cliente_precio_simular",
    "pos_atenciones_simular",
    "function simular",
    "function simularApertura",
    "function simularCorte",
    "function simularReserva",
    "function simularAbono",
    "Contrato: todos los datos operativos",
    "Contrato: vista informativa"
);

$bloqueos = array();
$detalle = array();
foreach ($archivos as $relativo) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativo);
    $existe = is_file($path);
    $detalle[$relativo] = array("existe" => $existe, "hallazgos" => array());
    if (!$existe) {
        $bloqueos[] = "Archivo no encontrado: " . $relativo;
        continue;
    }
    $lineas = file($path, FILE_IGNORE_NEW_LINES);
    foreach ($lineas as $idx => $linea) {
        foreach ($prohibidos as $patron) {
            if (strpos($linea, $patron) === false) {
                continue;
            }
            $permitido = false;
            foreach ($permitidosInternos as $interno) {
                if (strpos($linea, $interno) !== false) {
                    $permitido = true;
                    break;
                }
            }
            if ($permitido) {
                continue;
            }
            $hallazgo = array(
                "linea" => $idx + 1,
                "patron" => $patron,
                "texto" => trim($linea)
            );
            $detalle[$relativo]["hallazgos"][] = $hallazgo;
            $bloqueos[] = $relativo . ":" . ($idx + 1) . " contiene texto tecnico visible: " . $patron;
        }
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_lenguaje_operativo_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "archivos_revisados" => count($archivos),
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);
