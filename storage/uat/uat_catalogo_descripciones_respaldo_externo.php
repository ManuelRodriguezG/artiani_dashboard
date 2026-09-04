<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-02
 * Proposito: generar respaldo externo antes de limpiar descripciones de Catalogo ERP.
 * Impacto: solo crea archivo SQL fuera del proyecto; no modifica base de datos.
 * Contrato: usa constantes de conexion cargadas por el proyecto y no imprime credenciales.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

$backupDir = 'C:\\xampp\\panel_db_backups';
$stamp = date('Ymd_His');
$backupFile = $backupDir . '\\artianicom_sys_panel_de_control_' . $stamp . '_antes_catalogo_descripciones_limpieza.sql';

if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true)) {
  responder_respaldo_descripciones(true, 'No se pudo crear el directorio externo de respaldos', array(
    'directorio' => $backupDir
  ));
}

$resultado = ejecutar_mysqldump_descripciones($backupFile);
responder_respaldo_descripciones(!empty($resultado['error']), !empty($resultado['error']) ? 'No se pudo generar respaldo externo' : 'Respaldo externo generado', array(
  'archivo' => $backupFile,
  'existe' => is_file($backupFile),
  'bytes' => is_file($backupFile) ? filesize($backupFile) : 0,
  'codigo' => isset($resultado['code']) ? $resultado['code'] : null,
  'stderr_resumen' => isset($resultado['stderr']) ? substr(trim((string) $resultado['stderr']), 0, 300) : ''
));

function ejecutar_mysqldump_descripciones($backupFile) {
  $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
  if (!is_file($mysqldump)) {
    return array('error' => true, 'mensaje' => 'mysqldump_no_encontrado');
  }
  $cmd = escapeshellarg($mysqldump)
    . ' --host=' . escapeshellarg(MYSQLHOST)
    . ' --port=' . escapeshellarg(MYSQLPORT)
    . ' --user=' . escapeshellarg(MYSQLUSER)
    . ' --password=' . escapeshellarg(MYSQLPASS)
    . ' --single-transaction --routines --triggers '
    . escapeshellarg(MYSQLBASE);

  $descriptor = array(
    0 => array('pipe', 'r'),
    1 => array('file', $backupFile, 'w'),
    2 => array('pipe', 'w')
  );
  $process = proc_open($cmd, $descriptor, $pipes);
  if (!is_resource($process)) {
    return array('error' => true, 'mensaje' => 'proc_open_fallo');
  }
  fclose($pipes[0]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);
  $code = proc_close($process);
  return array(
    'error' => $code !== 0 || !is_file($backupFile) || filesize($backupFile) <= 0,
    'code' => $code,
    'stderr' => $stderr
  );
}

function responder_respaldo_descripciones($error, $mensaje, $depurar) {
  echo json_encode(array(
    'error' => (bool) $error,
    'tipo' => $error ? 'danger' : 'success',
    'mensaje' => $mensaje,
    'depurar' => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($error ? 1 : 0);
}
