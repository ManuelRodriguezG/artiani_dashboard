<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-08
 * Proposito: auditar atributos actuales de Catalogo ERP contra un vocabulario canonico inicial.
 * Impacto: solo lectura; no modifica catalogo, productos, SKUs, atributos ni unidades.
 * Contrato: imprime JSON con atributos, usos, muestras y recomendacion de mapeo para revision humana.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosBaseReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function normalizar_nombre_atributo($texto) {
  $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
  $buscar = array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ');
  $reemplazar = array('a', 'e', 'i', 'o', 'u', 'u', 'n');
  return str_replace($buscar, $reemplazar, $texto);
}

function recomendacion_atributo($nombre) {
  $n = normalizar_nombre_atributo($nombre);
  $mapa = array(
    'absorcion' => array('canonico' => 'absorcion', 'unidad' => null, 'tipo' => 'texto/numero', 'accion' => 'revisar contexto; puede aplicar a sustratos o absorbentes'),
    'alto' => array('canonico' => 'alto', 'unidad' => 'cm', 'tipo' => 'numero', 'accion' => 'conservar canonico'),
    'ancho' => array('canonico' => 'ancho', 'unidad' => 'cm', 'tipo' => 'numero', 'accion' => 'conservar canonico'),
    'atributo ecommerce' => array('canonico' => null, 'unidad' => null, 'tipo' => 'texto', 'accion' => 'revisar valores; probable atributo legado no canonico'),
    'calibre' => array('canonico' => 'calibre', 'unidad' => null, 'tipo' => 'texto/lista', 'accion' => 'conservar si aplica a malla/vidrio; no mezclar con grosor'),
    'altura_maxima' => array('canonico' => 'altura_maxima', 'unidad' => 'm', 'tipo' => 'numero', 'accion' => 'conservar canonico para bombas/filtros'),
    'cantidad' => array('canonico' => null, 'unidad' => null, 'tipo' => 'numero', 'accion' => 'revisar; puede ser contenido, piezas incluidas o cantidad por paquete'),
    'capacidad' => array('canonico' => null, 'unidad' => 'l/ml/g/kg segun familia', 'tipo' => 'numero', 'accion' => 'mapear por categoria: capacidad_volumen, capacidad_acuario_min/max o capacidad'),
    'capacidad_acuario_max' => array('canonico' => 'capacidad_acuario_max', 'unidad' => 'l', 'tipo' => 'numero', 'accion' => 'conservar canonico como capacidad maxima recomendada'),
    'capacidad_acuario_min' => array('canonico' => 'capacidad_acuario_min', 'unidad' => 'l', 'tipo' => 'numero', 'accion' => 'conservar canonico como capacidad minima recomendada'),
    'caudal' => array('canonico' => 'caudal', 'unidad' => 'l/h', 'tipo' => 'numero', 'accion' => 'conservar canonico para filtros/bombas'),
    'color' => array('canonico' => 'color', 'unidad' => null, 'tipo' => 'lista/color', 'accion' => 'conservar canonico'),
    'consumo_electrico' => array('canonico' => 'consumo_electrico', 'unidad' => 'w', 'tipo' => 'numero', 'accion' => 'conservar canonico'),
    'contendio' => array('canonico' => 'contenido_neto', 'unidad' => 'g/kg/ml/l segun valor', 'tipo' => 'numero', 'accion' => 'corregir error ortografico y mapear'),
    'contenido' => array('canonico' => 'contenido_neto', 'unidad' => 'g/kg/ml/l segun valor', 'tipo' => 'numero', 'accion' => 'mapear a contenido_neto, peso_contenido o volumen_contenido'),
    'contenido pza' => array('canonico' => 'contenido_piezas', 'unidad' => null, 'tipo' => 'numero', 'accion' => 'crear canonico solo si hay uso real'),
    'contennido' => array('canonico' => 'contenido_neto', 'unidad' => 'g/kg/ml/l segun valor', 'tipo' => 'numero', 'accion' => 'corregir error ortografico y mapear'),
    'cotenido' => array('canonico' => 'contenido_neto', 'unidad' => 'g/kg/ml/l segun valor', 'tipo' => 'numero', 'accion' => 'corregir error ortografico y mapear'),
    'diametro' => array('canonico' => 'diametro', 'unidad' => 'cm', 'tipo' => 'numero', 'accion' => 'conservar canonico'),
    'diseno' => array('canonico' => 'diseno', 'unidad' => null, 'tipo' => 'texto/lista', 'accion' => 'conservar si se usa como variante visual'),
    'grosor' => array('canonico' => 'grosor', 'unidad' => 'mm/cm segun familia', 'tipo' => 'numero', 'accion' => 'mapear a grosor o grosor_vidrio si es pecera'),
    'largo' => array('canonico' => 'largo', 'unidad' => 'cm', 'tipo' => 'numero', 'accion' => 'conservar canonico'),
    'longuitd' => array('canonico' => 'largo', 'unidad' => 'cm', 'tipo' => 'numero', 'accion' => 'corregir error y migrar si aplica'),
    'longuitud' => array('canonico' => 'largo', 'unidad' => 'cm', 'tipo' => 'numero', 'accion' => 'corregir error y migrar si aplica'),
    'medida' => array('canonico' => null, 'unidad' => 'cm/mm segun valor', 'tipo' => 'texto compuesto', 'accion' => 'extraer a largo/ancho/alto/diametro cuando sea posible'),
    'medidas' => array('canonico' => null, 'unidad' => 'cm/mm segun valor', 'tipo' => 'texto compuesto', 'accion' => 'extraer a largo/ancho/alto/diametro cuando sea posible'),
    'medidas con mueble' => array('canonico' => null, 'unidad' => 'cm', 'tipo' => 'texto compuesto', 'accion' => 'crear grupo de medidas con mueble o conservar como texto tecnico revisado'),
    'peso' => array('canonico' => 'peso_producto', 'unidad' => 'g/kg', 'tipo' => 'numero', 'accion' => 'mapear a peso_producto o peso_contenido segun categoria'),
    'peso maximo' => array('canonico' => 'peso_maximo_soportado', 'unidad' => 'kg', 'tipo' => 'numero', 'accion' => 'mapear canonico'),
    'potencia' => array('canonico' => 'consumo_electrico', 'unidad' => 'w', 'tipo' => 'numero', 'accion' => 'mapear a consumo_electrico si el valor esta en watts'),
    'presentacion' => array('canonico' => 'presentacion', 'unidad' => null, 'tipo' => 'texto/lista', 'accion' => 'conservar; diferenciar de presentaciones operativas de SKU'),
    'subida' => array('canonico' => 'altura_maxima', 'unidad' => 'cm/m', 'tipo' => 'numero', 'accion' => 'mapear si describe altura maxima de bomba/filtro'),
    'talla' => array('canonico' => 'talla', 'unidad' => null, 'tipo' => 'lista/texto', 'accion' => 'conservar canonico')
  );
  return isset($mapa[$n])
    ? $mapa[$n]
    : array('canonico' => null, 'unidad' => null, 'tipo' => null, 'accion' => 'atributo no mapeado; revisar manualmente');
}

$db = (new CatalogoAtributosBaseReadonlyDb())->db();

$sql = "
  SELECT
    a.id_atributo_erp,
    a.codigo,
    a.nombre,
    a.tipo_dato,
    a.unidad,
    a.es_variante,
    a.estatus,
    COUNT(sa.id_sku_atributo) AS usos,
    COUNT(DISTINCT sa.id_sku) AS skus,
    GROUP_CONCAT(DISTINCT NULLIF(TRIM(sa.valor), '') ORDER BY sa.valor SEPARATOR ' | ') AS valores
  FROM erp_catalogo_atributos a
  LEFT JOIN erp_catalogo_sku_atributos sa ON sa.id_atributo_erp = a.id_atributo_erp
  GROUP BY a.id_atributo_erp, a.codigo, a.nombre, a.tipo_dato, a.unidad, a.es_variante, a.estatus
  ORDER BY a.estatus DESC, a.nombre
";

$atributos = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'atributos_total' => count($atributos),
  'atributos' => array()
);

foreach ($atributos as $atributo) {
  $valores = array();
  if (!empty($atributo['valores'])) {
    $valores = array_slice(explode(' | ', $atributo['valores']), 0, 8);
  }
  $salida['atributos'][] = array(
    'id_atributo_erp' => intval($atributo['id_atributo_erp']),
    'codigo' => $atributo['codigo'],
    'nombre' => $atributo['nombre'],
    'tipo_dato' => $atributo['tipo_dato'],
    'unidad_actual' => $atributo['unidad'],
    'es_variante' => intval($atributo['es_variante']),
    'estatus' => $atributo['estatus'],
    'usos' => intval($atributo['usos']),
    'skus' => intval($atributo['skus']),
    'muestras_valor' => $valores,
    'recomendacion' => recomendacion_atributo($atributo['nombre'])
  );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
