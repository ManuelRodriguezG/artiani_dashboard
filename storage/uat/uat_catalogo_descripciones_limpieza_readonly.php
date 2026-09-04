<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-02
 * Proposito: auditar descripciones heredadas de Catalogo ERP y proponer limpieza segura para ecommerce.
 * Impacto: solo lectura; no modifica productos, SKUs, atributos, categorias, imagenes ni ecommerce.
 * Contrato: imprime JSON con resumen, muestras y texto sugerido; cualquier aplicacion masiva requiere autorizacion aparte.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoDescripcionesLimpiezaReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function catalogo_descripcion_decodificar($texto) {
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

function catalogo_descripcion_limpia($texto) {
  $texto = catalogo_descripcion_decodificar($texto);
  $texto = preg_replace('/<\s*(br|\/p|\/div|\/li|\/tr)\s*\/?>/i', "\n", $texto);
  $texto = preg_replace('/<\s*(p|div|li|tr|td|th)[^>]*>/i', "\n", $texto);
  $texto = preg_replace('/<\s*(script|style)[^>]*>.*?<\s*\/\s*\1\s*>/is', ' ', $texto);
  $texto = strip_tags($texto);
  $texto = catalogo_descripcion_decodificar($texto);
  $texto = str_replace(array("\xc2\xa0", '&nbsp;'), ' ', $texto);
  $texto = preg_replace('/[ \t]+/', ' ', $texto);
  $texto = preg_replace('/\R{3,}/', "\n\n", $texto);
  $lineas = array_map('trim', preg_split('/\R/', $texto));
  $lineas = array_values(array_filter($lineas, function ($linea) {
    return $linea !== '';
  }));
  return trim(implode("\n", $lineas));
}

function catalogo_descripcion_flags($original, $limpia) {
  $flags = array();
  if (trim((string) $original) === '') {
    $flags[] = 'sin_descripcion';
  }
  if (preg_match('/<\s*\/?\s*[a-z][^>]*>/i', (string) $original)) {
    $flags[] = 'html_incrustado';
  }
  if (preg_match('/&(?:[a-zA-Z][a-zA-Z0-9]+|#[0-9]+|#x[0-9a-fA-F]+);/', (string) $original)) {
    $flags[] = 'entidades_html';
  }
  if (preg_match('/(Ã|Â|�|â€™|â€œ|â€|├|┬)/u', (string) $original)) {
    $flags[] = 'posible_codificacion_danada';
  }
  if (preg_match('/\s{3,}|\R{3,}/', (string) $original)) {
    $flags[] = 'espaciado_irregular';
  }
  if (trim((string) $original) !== $limpia) {
    $flags[] = 'limpieza_sugerida';
  }
  return array_values(array_unique($flags));
}

function catalogo_descripcion_accion($flags) {
  if (in_array('sin_descripcion', $flags, true)) {
    return 'capturar_descripcion';
  }
  if (in_array('posible_codificacion_danada', $flags, true)) {
    return 'revision_humana_codificacion';
  }
  if (in_array('limpieza_sugerida', $flags, true)) {
    return 'aplicar_limpieza_segura';
  }
  return 'sin_cambios';
}

$limiteMuestras = 80;
foreach ($argv as $arg) {
  if (strpos($arg, '--limit=') === 0) {
    $limiteMuestras = max(1, intval(substr($arg, 8)));
  }
}

$db = (new CatalogoDescripcionesLimpiezaReadonlyDb())->db();
$productos = $db->query("
  SELECT id_producto_erp, codigo_producto, nombre, descripcion, estatus
  FROM erp_catalogo_productos
  WHERE estatus <> 'fusionado'
  ORDER BY id_producto_erp
")->fetchAll(PDO::FETCH_ASSOC);

$resumen = array(
  'total_productos' => count($productos),
  'sin_descripcion' => 0,
  'html_incrustado' => 0,
  'entidades_html' => 0,
  'posible_codificacion_danada' => 0,
  'limpieza_sugerida' => 0,
  'aplicar_limpieza_segura' => 0,
  'revision_humana_codificacion' => 0,
  'sin_cambios' => 0
);
$muestras = array();

foreach ($productos as $producto) {
  $original = (string) $producto['descripcion'];
  $limpia = catalogo_descripcion_limpia($original);
  $flags = catalogo_descripcion_flags($original, $limpia);
  $accion = catalogo_descripcion_accion($flags);

  foreach ($flags as $flag) {
    if (isset($resumen[$flag])) {
      $resumen[$flag]++;
    }
  }
  if (isset($resumen[$accion])) {
    $resumen[$accion]++;
  }

  if ($accion !== 'sin_cambios' && count($muestras) < $limiteMuestras) {
    $muestras[] = array(
      'id_producto_erp' => intval($producto['id_producto_erp']),
      'codigo_producto' => $producto['codigo_producto'],
      'nombre' => $producto['nombre'],
      'estatus' => $producto['estatus'],
      'accion_recomendada' => $accion,
      'flags' => $flags,
      'descripcion_actual' => mb_substr($original, 0, 500, 'UTF-8'),
      'descripcion_sugerida' => mb_substr($limpia, 0, 500, 'UTF-8')
    );
  }
}

$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'proyecto' => 'C:\\xampp\\htdocs\\panel_de_control',
  'regla' => 'No modifica BD; cualquier limpieza masiva requiere respaldo externo y autorizacion explicita.',
  'resumen' => $resumen,
  'muestras' => $muestras
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
