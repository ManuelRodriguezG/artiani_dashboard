<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-08
 * Proposito: sugerir familias/categorias canonicas para productos de Catalogo ERP a partir de nombre y categoria actual.
 * Impacto: solo lectura; no modifica productos, categorias ni relaciones.
 * Contrato: imprime JSON con resumen por familia y muestras de productos para revision humana.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoCategoriasSugeridasReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function texto_normalizado_categoria($texto) {
  $texto = mb_strtolower((string) $texto, 'UTF-8');
  $buscar = array('á','é','í','ó','ú','ü','ñ','├í','├│','├®','├║','├▒');
  $reemplazar = array('a','e','i','o','u','u','n','a','o','e','u','n');
  $texto = str_replace($buscar, $reemplazar, $texto);
  return preg_replace('/\s+/', ' ', trim($texto));
}

function contiene_categoria($texto, $palabras) {
  foreach ($palabras as $palabra) {
    if (strpos($texto, $palabra) !== false) {
      return true;
    }
  }
  return false;
}

function sugerir_familia_categoria($nombre, $categoriaActual) {
  $t = texto_normalizado_categoria($nombre . ' ' . $categoriaActual);

  $reglas = array(
    array('familia' => 'Acuario', 'categoria' => 'Acuario / Filtracion y oxigenacion', 'palabras' => array('filtro', 'bomba', 'cabeza de poder', 'oxigen', 'canister', 'cascada', 'aireador', 'spf-')),
    array('familia' => 'Acuario', 'categoria' => 'Acuario / Peceras, tortugueros y terrarios', 'palabras' => array('pecera', 'tortuguero', 'terrario', 'acuario equipado', 'panoramica equipada')),
    array('familia' => 'Acuario', 'categoria' => 'Acuario / Iluminacion y calefaccion', 'palabras' => array('lampara', 'luz', 'calentador', 'termometro', 'calefaccion')),
    array('familia' => 'Acuario', 'categoria' => 'Acuario / Alimentos para peces', 'palabras' => array('goldfish', 'hojuela', 'pellet', 'granulado', 'alimento tropical', 'alimento para peces')),
    array('familia' => 'Acuario', 'categoria' => 'Acuario / Decoracion y sustratos', 'palabras' => array('grava', 'piedra de mar', 'decorativa', 'raiz', 'tronco', 'sustrato', 'peat moss', 'fibra de coco', 'chip de coco', 'polvillo')),
    array('familia' => 'Reptiles', 'categoria' => 'Reptiles / Alimentos y presas', 'palabras' => array('tenebrio', 'zophoba', 'grillo', 'larva', 'mosca soldado', 'artemia', 'iguana bits')),
    array('familia' => 'Reptiles', 'categoria' => 'Reptiles / Terrarios y habitat', 'palabras' => array('terrario', 'malla metalica', 'malla de tela')),
    array('familia' => 'Aves', 'categoria' => 'Aves / Alimento y suplementos', 'palabras' => array('aves', 'loros', 'cacatuas', 'periquitos', 'ninfas', 'tonico para aves', 'levadura de cerveza')),
    array('familia' => 'Aves', 'categoria' => 'Aves / Jaulas y accesorios', 'palabras' => array('jaula para aves', 'jaula redonda', 'juguete para aves', 'paradero')),
    array('familia' => 'Roedores y pequenos mamiferos', 'categoria' => 'Roedores / Jaulas y habitats', 'palabras' => array('hamster', 'hamsters', 'cuyo', 'conejo', 'chinchilla', 'erizo', 'rata', 'raton', 'jaula hamster')),
    array('familia' => 'Roedores y pequenos mamiferos', 'categoria' => 'Roedores / Alimento y sustratos', 'palabras' => array('alfalfa', 'nutricubos', 'salvado', 'cavia')),
    array('familia' => 'Perros', 'categoria' => 'Perro / Alimento, premios y snacks', 'palabras' => array('nucan', 'nupec', 'premio snack', 'carnaza', 'lata ', 'alimento humedo')),
    array('familia' => 'Perros', 'categoria' => 'Perro / Camas, casas y descanso', 'palabras' => array('cama ', 'colchon', 'tapete refrescante')),
    array('familia' => 'Perros', 'categoria' => 'Perro / Correas, collares y entrenamiento', 'palabras' => array('correa', 'bozal', 'collar', 'arnes')),
    array('familia' => 'Perros y gatos', 'categoria' => 'Perros y gatos / Higiene y salud', 'palabras' => array('shampoo', 'refrescante de aliento', 'bravecto', 'sanitarias', 'isabelino')),
    array('familia' => 'Gatos', 'categoria' => 'Gato / Areneros e higiene', 'palabras' => array('arenero', 'arena para gato')),
    array('familia' => 'Gatos', 'categoria' => 'Gato / Rascadores y juguetes', 'palabras' => array('rascadero', 'juguete para gato', 'cana ')),
    array('familia' => 'Transportadoras', 'categoria' => 'Transportadoras / Transportadoras y mochilas', 'palabras' => array('transportadora', 'mochila transportadora', 'maletin')),
    array('familia' => 'Alimentacion', 'categoria' => 'Alimentacion / Comederos, bebederos y dispensadores', 'palabras' => array('comedero', 'bebedero', 'dispensador', 'tazon')),
    array('familia' => 'Accesorios generales', 'categoria' => 'Accesorios generales / Varios', 'palabras' => array('llavero', 'bolsas sanitarias'))
  );

  foreach ($reglas as $regla) {
    if (contiene_categoria($t, $regla['palabras'])) {
      return array(
        'familia_sugerida' => $regla['familia'],
        'categoria_sugerida' => $regla['categoria'],
        'confianza' => 'media',
        'motivo' => 'coincidencia por nombre/categoria actual'
      );
    }
  }

  return array(
    'familia_sugerida' => 'Revision manual',
    'categoria_sugerida' => 'Revision manual',
    'confianza' => 'baja',
    'motivo' => 'sin regla clara'
  );
}

$db = (new CatalogoCategoriasSugeridasReadonlyDb())->db();
$sql = "
  SELECT
    p.id_producto_erp,
    p.codigo_producto,
    p.nombre,
    p.estatus,
    COALESCE(c.ruta, c.nombre, '') AS categoria_actual,
    CASE WHEN pc.id_producto_categoria IS NULL THEN 0 ELSE 1 END AS tiene_categoria_principal
  FROM erp_catalogo_productos p
  LEFT JOIN erp_catalogo_producto_categorias pc
    ON pc.id_producto_erp = p.id_producto_erp AND pc.es_principal = 1
  LEFT JOIN erp_catalogo_categorias c
    ON c.id_categoria_erp = pc.id_categoria_erp
  WHERE p.estatus NOT IN ('fusionado', 'inactivo')
  ORDER BY p.id_producto_erp DESC
";

$productos = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$resumen = array();
$muestras = array();
$sinCategoria = 0;

foreach ($productos as $producto) {
  $sugerencia = sugerir_familia_categoria($producto['nombre'], $producto['categoria_actual']);
  $clave = $sugerencia['categoria_sugerida'];
  if (!isset($resumen[$clave])) {
    $resumen[$clave] = array(
      'familia' => $sugerencia['familia_sugerida'],
      'categoria_sugerida' => $sugerencia['categoria_sugerida'],
      'total' => 0,
      'sin_categoria_principal' => 0
    );
    $muestras[$clave] = array();
  }
  $resumen[$clave]['total']++;
  if (intval($producto['tiene_categoria_principal']) !== 1) {
    $resumen[$clave]['sin_categoria_principal']++;
    $sinCategoria++;
  }
  if (count($muestras[$clave]) < 12) {
    $muestras[$clave][] = array(
      'id_producto_erp' => intval($producto['id_producto_erp']),
      'codigo_producto' => $producto['codigo_producto'],
      'nombre' => $producto['nombre'],
      'categoria_actual' => $producto['categoria_actual'],
      'tiene_categoria_principal' => intval($producto['tiene_categoria_principal']),
      'confianza' => $sugerencia['confianza']
    );
  }
}

uasort($resumen, function($a, $b) {
  return $b['total'] <=> $a['total'];
});

$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'productos_total' => count($productos),
  'productos_sin_categoria_principal' => $sinCategoria,
  'categorias_sugeridas' => array_values($resumen),
  'muestras' => $muestras
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
