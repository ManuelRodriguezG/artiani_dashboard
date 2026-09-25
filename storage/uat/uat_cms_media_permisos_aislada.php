<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-09-25
 * Proposito: comprobar permisos publicos Linux y errores aun al ejecutar UAT en Windows.
 * Impacto: helper real de Media, sin BD ni archivos de la biblioteca.
 * Contrato: CLI; intercepta solo chmod/fileperms con fixtures temporales propios.
 */
namespace CmsMediaPermisosUat;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
const PHP_OS_FAMILY = 'Linux';

/** IA: Codex GPT-6 | Fecha: 2026-09-25 | Estado POSIX simulado y registro de cambios solicitados. */
class Escenario {
  public static $modo = 0600;
  public static $fallo = '';
  public static $intentos = array();
}

/** IA: Codex GPT-6 | Fecha: 2026-09-25 | Simula syscall denegada o permiso no aplicado sin afectar medios reales. */
function chmod($ruta, $modo) {
  Escenario::$intentos[] = $modo;
  if (Escenario::$fallo === 'chmod') return false;
  if (Escenario::$fallo !== 'sin_cambio') Escenario::$modo = $modo;
  return true;
}

/** IA: Codex GPT-6 | Fecha: 2026-09-25 | Devuelve el resultado POSIX simulado, incluido stat fallido. */
function fileperms($ruta) { return Escenario::$fallo === 'stat' ? false : Escenario::$modo; }

/** IA: Codex GPT-6 | 2026-09-25 | Simula un archivo que PHP no puede leer, conservando existencia real. */
function is_readable($ruta) { return Escenario::$fallo !== 'lectura' && \is_readable($ruta); }

/** IA: Codex GPT-6 | Fecha: 2026-09-25 | Una expectativa incumplida falla la suite. */
function exigir($condicion, $mensaje) { if (!$condicion) throw new \RuntimeException($mensaje); }

$codigo = file_get_contents(__DIR__ . '/../../app/core/CmsMediaArchivo.php');
eval('namespace CmsMediaPermisosUat; use \Throwable; ' . preg_replace('/^<\?php\s*/', '', $codigo));
$ruta = tempnam(sys_get_temp_dir(), 'media_permisos_');
if (!$ruta) throw new \RuntimeException('No se pudo crear el fixture');
try {
  file_put_contents($ruta, 'fixture privado');
  foreach (array('' => true, 'chmod' => false, 'sin_cambio' => false, 'stat' => false) as $fallo => $esperado) {
    Escenario::$fallo = $fallo; Escenario::$modo = 0600; Escenario::$intentos = array();
    exigir(CmsMediaArchivo::asegurarLecturaPublica($ruta) === $esperado, 'Respuesta incorrecta para ' . ($fallo ?: 'publicacion'));
    exigir(Escenario::$intentos === array(0644), 'Solicito escritura publica o permisos distintos de 0644');
    exigir(file_get_contents($ruta) === 'fixture privado', 'Modifico bytes del archivo');
    if ($esperado) exigir(Escenario::$modo === 0644, 'Conservo permisos privados');
    echo 'OK permisos: ' . ($fallo ?: '0600 pasa a 0644 sin cambiar bytes') . PHP_EOL;
  }
  Escenario::$fallo = ''; Escenario::$intentos = array();
  exigir(!CmsMediaArchivo::asegurarLecturaPublica($ruta . '_ausente'), 'Acepto archivo ausente');
  exigir(!CmsMediaArchivo::asegurarLecturaPublica(dirname($ruta)), 'Acepto un directorio');
  exigir(Escenario::$intentos === array(), 'Intento cambiar permisos fuera de un archivo regular');
  echo 'OK rechaza archivos ausentes y directorios sin alterar permisos' . PHP_EOL;
  // IA: Codex GPT-6 | 2026-09-25 | Diagnostico de solo lectura distingue permisos, ausencia y corrupcion.
  $bytes = filesize($ruta); $hash = hash_file('sha256', $ruta);
  foreach (array(
    array('completo', '', 0644, $ruta, $bytes, $hash, 'verificado'),
    array('ausente', '', 0644, $ruta . '_ausente', $bytes, $hash, 'ausente'),
    array('privado', '', 0600, $ruta, $bytes, $hash, 'permisos_restringidos'),
    array('PHP sin lectura', 'lectura', 0644, $ruta, $bytes, $hash, 'no_legible'),
    array('peso distinto', '', 0644, $ruta, $bytes + 1, $hash, 'contenido_distinto'),
    array('hash distinto con mismo peso', '', 0644, $ruta, $bytes, str_repeat('a', 64), 'contenido_distinto'),
    array('sin hash registrado', '', 0644, $ruta, $bytes, '', 'no_verificable'),
    array('stat fallido', 'stat', 0644, $ruta, $bytes, $hash, 'no_verificable')
  ) as [$titulo, $fallo, $modo, $archivo, $esperadoBytes, $esperadoHash, $estado]) {
    Escenario::$fallo = $fallo; Escenario::$modo = $modo; Escenario::$intentos = array();
    $informe = CmsMediaArchivo::verificarGuardado($archivo, $esperadoBytes, $esperadoHash);
    exigir($informe['estado'] === $estado && $informe['ok'] === ($estado === 'verificado'), 'Diagnostico incorrecto: ' . $titulo);
    exigir(Escenario::$intentos === array() && file_get_contents($ruta) === 'fixture privado', 'Diagnostico modifico el archivo');
    exigir(strpos(json_encode($informe), basename($ruta)) === false, 'Diagnostico revela ruta privada');
    echo 'OK diagnostico: ' . $titulo . PHP_EOL;
  }
} finally {
  if (is_file($ruta)) unlink($ruta);
}
