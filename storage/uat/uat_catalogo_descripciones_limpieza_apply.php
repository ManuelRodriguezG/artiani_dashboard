<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-02
 * Proposito: aplicar limpieza segura de HTML/entidades en descripciones de productos ERP para ecommerce.
 * Impacto: modifica `erp_catalogo_productos.descripcion`; no toca SKUs, atributos, categorias, imagenes, precios, costos ni ecommerce.
 * Contrato: requiere token CATALOGO_DESCRIPCIONES_HTML_LIMPIAR, respaldo externo existente y confirmacion explicita.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoDescripcionesLimpiezaApplyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function argumento_catalogo_descripciones($nombre, $default = '') {
  global $argv;
  $prefijo = '--' . $nombre . '=';
  foreach ($argv as $arg) {
    if (strpos($arg, $prefijo) === 0) {
      return substr($arg, strlen($prefijo));
    }
  }
  return $default;
}

function ruta_esta_dentro_proyecto_catalogo($ruta) {
  $real = realpath($ruta);
  $proyecto = realpath(dirname(__DIR__, 2));
  if ($real === false || $proyecto === false) {
    return false;
  }
  return stripos($real, $proyecto) === 0;
}

function catalogo_descripcion_decodificar_apply($texto) {
  $texto = (string) $texto;
  for ($i = 0; $i < 2; $i++) {
    $decodificado = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($decodificado === $texto) {
      break;
    }
    $texto = $decodificado;
  }
  return $texto;
}

function catalogo_descripcion_limpia_apply($texto) {
  $texto = catalogo_descripcion_decodificar_apply($texto);
  $texto = preg_replace('/<\s*(br|\/p|\/div|\/li|\/tr)\s*\/?>/i', "\n", $texto);
  $texto = preg_replace('/<\s*(p|div|li|tr|td|th)[^>]*>/i', "\n", $texto);
  $texto = preg_replace('/<\s*(script|style)[^>]*>.*?<\s*\/\s*\1\s*>/is', ' ', $texto);
  $texto = strip_tags($texto);
  $texto = catalogo_descripcion_decodificar_apply($texto);
  $texto = str_replace(array("\xc2\xa0", '&nbsp;'), ' ', $texto);
  $texto = preg_replace('/[ \t]+/', ' ', $texto);
  $texto = preg_replace('/\R{3,}/', "\n\n", $texto);
  $lineas = array_map('trim', preg_split('/\R/', $texto));
  $lineas = array_values(array_filter($lineas, function ($linea) {
    return $linea !== '';
  }));
  return trim(implode("\n", $lineas));
}

function catalogo_descripcion_requiere_revision_apply($texto) {
  return preg_match('/(Ã|Â|�|â€™|â€œ|â€|├|┬)/u', (string) $texto) === 1;
}

$token = argumento_catalogo_descripciones('autorizar');
$respaldo = argumento_catalogo_descripciones('respaldo');
$confirmacion = argumento_catalogo_descripciones('confirmacion');

if ($token !== 'CATALOGO_DESCRIPCIONES_HTML_LIMPIAR') {
  fwrite(STDERR, "Falta token: --autorizar=CATALOGO_DESCRIPCIONES_HTML_LIMPIAR\n");
  exit(1);
}
if ($confirmacion !== 'APLICAR_DESCRIPCIONES_LIMPIAS') {
  fwrite(STDERR, "Falta confirmacion: --confirmacion=APLICAR_DESCRIPCIONES_LIMPIAS\n");
  exit(1);
}
if ($respaldo === '' || !file_exists($respaldo) || ruta_esta_dentro_proyecto_catalogo($respaldo)) {
  fwrite(STDERR, "El respaldo externo debe existir y estar fuera del proyecto.\n");
  exit(1);
}

$db = (new CatalogoDescripcionesLimpiezaApplyDb())->db();
$productos = $db->query("
  SELECT id_producto_erp, codigo_producto, descripcion
  FROM erp_catalogo_productos
  WHERE estatus <> 'fusionado'
  ORDER BY id_producto_erp
")->fetchAll(PDO::FETCH_ASSOC);

$actualizados = 0;
$omitidos = 0;
$sinCambios = 0;
$muestras = array();

try {
  $db->beginTransaction();
  $stmt = $db->prepare("UPDATE erp_catalogo_productos
    SET descripcion=:descripcion, fecha_actualizacion=CURRENT_TIMESTAMP
    WHERE id_producto_erp=:producto");

  foreach ($productos as $producto) {
    $original = (string) $producto['descripcion'];
    if (catalogo_descripcion_requiere_revision_apply($original)) {
      $omitidos++;
      continue;
    }
    $limpia = catalogo_descripcion_limpia_apply($original);
    if (trim($original) === $limpia) {
      $sinCambios++;
      continue;
    }
    $stmt->execute(array(
      ':descripcion' => $limpia,
      ':producto' => intval($producto['id_producto_erp'])
    ));
    $actualizados++;
    if (count($muestras) < 20) {
      $muestras[] = array(
        'id_producto_erp' => intval($producto['id_producto_erp']),
        'codigo_producto' => $producto['codigo_producto'],
        'antes' => mb_substr($original, 0, 180, 'UTF-8'),
        'despues' => mb_substr($limpia, 0, 180, 'UTF-8')
      );
    }
  }

  $db->commit();
} catch (Exception $e) {
  if ($db->inTransaction()) {
    $db->rollBack();
  }
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}

echo json_encode(array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'apply',
  'proyecto' => 'C:\\xampp\\htdocs\\panel_de_control',
  'respaldo' => $respaldo,
  'actualizados' => $actualizados,
  'omitidos_revision' => $omitidos,
  'sin_cambios' => $sinCambios,
  'muestras' => $muestras
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
