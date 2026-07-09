<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-27
 * Proposito: generar respaldo externo de BD antes de aplicar DDL base de Garantias.
 * Impacto: Garantias ERP; no modifica BD, solo crea archivo SQL fuera del proyecto.
 * Contrato: requiere --output=RUTA fuera del proyecto; no imprime credenciales y elimina archivo temporal de opciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";

$opciones = getopt("", array("output:"));
$output = isset($opciones["output"]) ? trim((string) $opciones["output"]) : "";
$raizProyecto = realpath(__DIR__ . "/../..");

function responder($error, $mensaje, $depurar = array()) {
    echo json_encode(array(
        "error" => $error,
        "tipo" => $error ? "danger" : "success",
        "mensaje" => $mensaje,
        "depurar" => $depurar
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit($error ? 1 : 0);
}

if ($output === "") {
    responder(true, "Indica --output con ruta externa");
}

$directorio = dirname($output);
if (!is_dir($directorio)) {
    responder(true, "El directorio de respaldo no existe", array("directorio" => $directorio));
}

$realDirectorio = realpath($directorio);
if ($raizProyecto && $realDirectorio && stripos($realDirectorio, $raizProyecto) === 0) {
    responder(true, "El respaldo debe estar fuera del proyecto", array(
        "directorio" => $realDirectorio,
        "proyecto" => $raizProyecto
    ));
}

$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
if (!file_exists($mysqldump)) {
    responder(true, "No se encontro mysqldump", array("ruta" => $mysqldump));
}

$archivoOpciones = $directorio . DIRECTORY_SEPARATOR . "mysqldump_garantias_" . getmypid() . ".cnf";
$contenidoOpciones = "[client]\n"
    . "user=" . MYSQLUSER . "\n"
    . "password=" . MYSQLPASS . "\n"
    . "host=" . MYSQLHOST . "\n";

file_put_contents($archivoOpciones, $contenidoOpciones);

$cmd = escapeshellarg($mysqldump)
    . " --defaults-extra-file=" . escapeshellarg($archivoOpciones)
    . " --databases " . escapeshellarg(MYSQLBASE)
    . " --result-file=" . escapeshellarg($output);

$salida = array();
$codigo = 0;
exec($cmd, $salida, $codigo);
@unlink($archivoOpciones);

if ($codigo !== 0) {
    responder(true, "mysqldump fallo", array("codigo" => $codigo, "salida" => $salida));
}

if (!file_exists($output) || filesize($output) <= 0) {
    responder(true, "El respaldo no se genero correctamente", array("output" => $output));
}

responder(false, "Respaldo externo generado", array(
    "output" => $output,
    "bytes" => filesize($output),
    "base" => MYSQLBASE
));
