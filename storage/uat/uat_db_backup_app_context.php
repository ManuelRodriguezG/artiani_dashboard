<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: generar respaldo mysqldump usando el contexto real de conexion de la app sin imprimir credenciales.
 * Impacto: respaldo operativo externo para cambios aplicados desde CLI.
 * Contrato: escribe solo en C:\xampp\panel_db_backups y no modifica BD.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentos($argv);
$archivo = isset($args["archivo"]) ? trim($args["archivo"]) : "";
if ($archivo === "" || stripos($archivo, "C:\\xampp\\panel_db_backups\\") !== 0) {
  salida(false, "Ruta de respaldo invalida", array(
    "ejecutado" => false,
    "ruta_requerida" => "C:\\xampp\\panel_db_backups\\[archivo].sql"
  ));
}

$dump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
if (!is_file($dump)) {
  salida(false, "mysqldump no encontrado", array("ejecutado" => false, "binario" => $dump));
}

$cmd = array(
  $dump,
  "--host=" . MYSQLHOST,
  "--port=" . MYSQLPORT,
  "--user=" . MYSQLUSER,
  "--password=" . MYSQLPASS,
  "--result-file=" . $archivo,
  MYSQLBASE
);

$descriptor = array(
  0 => array("pipe", "r"),
  1 => array("pipe", "w"),
  2 => array("pipe", "w")
);
$proceso = proc_open($cmd, $descriptor, $pipes);
if (!is_resource($proceso)) {
  salida(false, "No se pudo iniciar mysqldump", array("ejecutado" => false));
}
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$codigo = proc_close($proceso);

$existe = is_file($archivo);
$tamano = $existe ? filesize($archivo) : 0;
salida($codigo === 0 && $existe && $tamano > 0, $codigo === 0 ? "Respaldo generado" : "Respaldo fallo", array(
  "ejecutado" => true,
  "codigo_salida" => $codigo,
  "base" => MYSQLBASE,
  "archivo" => $archivo,
  "archivo_existe" => $existe,
  "tamano_bytes" => $tamano,
  "stdout" => trim($stdout),
  "stderr" => trim($stderr) !== "" ? "mysqldump_reporto_error" : "",
  "credenciales_ocultas" => true
));

function argumentos($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
