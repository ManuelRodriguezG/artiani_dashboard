<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: diagnosticar disponibilidad local de MariaDB para UAT POS sin reparar ni escribir datos.
 * Impacto: separa fallas de entorno MySQL de fallas reales del modulo POS.
 * Contrato: read-only; no arranca servicios, no repara tablas, no borra PID/logs y no importa respaldos.
 */

date_default_timezone_set("America/Mexico_City");

$dataDir = "C:\\xampp\\mysql\\data";
$errorLog = $dataDir . "\\mysql_error.log";
$pluginMai = $dataDir . "\\mysql\\plugin.MAI";
$pluginMad = $dataDir . "\\mysql\\plugin.MAD";
$ariaLogs = glob($dataDir . "\\aria_log.*");
$pidFiles = glob($dataDir . "\\*.pid");
$mysqladmin = "C:\\xampp\\mysql\\bin\\mysqladmin.exe";

$contenidoLog = is_file($errorLog) ? file_get_contents($errorLog) : "";
$ultimasLineas = ultimasLineas($contenidoLog, 80);
$bloqueos = array();
$avisos = array();
$ping = pingMysql($mysqladmin);

$patronesCriticos = array(
    "Aria recovery failed" => "Recuperacion Aria fallida",
    "Please run aria_chk -r on all Aria tables" => "MariaDB solicita aria_chk -r",
    "Index for table '.\\mysql\\plugin' is corrupt" => "Indice corrupto en mysql.plugin",
    "Could not open mysql.plugin table" => "No se puede abrir mysql.plugin",
    "Failed to initialize plugins" => "Fallo al inicializar plugins",
    "Aborting" => "MariaDB aborta durante arranque",
);

foreach ($patronesCriticos as $patron => $descripcion) {
    if (strpos($contenidoLog, $patron) !== false) {
        if ($ping["ok"]) {
            $avisos[] = "Historico en log: " . $descripcion;
        } else {
            $bloqueos[] = $descripcion;
        }
    }
}

if (!$ping["ok"]) {
    $bloqueos[] = "mysqladmin ping no responde";
}
if (!is_file($errorLog)) {
    $avisos[] = "No se encontro mysql_error.log en " . $errorLog;
}
if (!is_file($pluginMai) || !is_file($pluginMad)) {
    $avisos[] = "No se encontraron archivos mysql.plugin completos.";
}
if (!empty($pidFiles)) {
    $avisos[] = "Existen PID files; pueden ser historicos si MariaDB no esta vivo.";
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_mysql_health_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "mysql_data_dir" => $dataDir,
    "mysql_ping" => $ping,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "archivos" => array(
        "mysql_error_log" => array("existe" => is_file($errorLog), "ruta" => $errorLog, "tamano" => is_file($errorLog) ? filesize($errorLog) : 0),
        "plugin_mai" => array("existe" => is_file($pluginMai), "ruta" => $pluginMai, "tamano" => is_file($pluginMai) ? filesize($pluginMai) : 0),
        "plugin_mad" => array("existe" => is_file($pluginMad), "ruta" => $pluginMad, "tamano" => is_file($pluginMad) ? filesize($pluginMad) : 0),
        "aria_logs" => array_values(array_map("basename", is_array($ariaLogs) ? $ariaLogs : array())),
        "pid_files" => array_values(array_map("basename", is_array($pidFiles) ? $pidFiles : array())),
    ),
    "ultimas_lineas_log" => $ultimasLineas,
    "siguiente_autorizacion_sugerida" => "AUTORIZO RECUPERAR MYSQL UAT POS usando respaldo UAT POS vigente con token MYSQL_UAT_POS_RECOVERY permitiendo respaldo previo de C:\\xampp\\mysql\\data, arranque controlado de MariaDB, diagnostico InnoDB, reparacion Aria con aria_chk y restauracion/importacion solo si es necesario para continuar UAT POS",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_repara_tablas" => true,
        "no_borra_archivos" => true,
        "no_arranca_servicios" => true,
        "no_importa_respaldo" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);

function ultimasLineas($texto, $limite)
{
    $lineas = preg_split('/\r\n|\r|\n/', trim($texto));
    if (!is_array($lineas)) {
        return array();
    }
    return array_slice($lineas, max(0, count($lineas) - $limite));
}

function pingMysql($mysqladmin)
{
    $output = array();
    $codigo = 1;
    if (!is_file($mysqladmin)) {
        return array(
            "ok" => false,
            "exit_code" => 1,
            "output" => array("mysqladmin.exe no existe: " . $mysqladmin),
        );
    }
    exec('"' . $mysqladmin . '" ping -h 127.0.0.1 -u root 2>&1', $output, $codigo);
    return array(
        "ok" => $codigo === 0,
        "exit_code" => $codigo,
        "output" => $output,
    );
}
