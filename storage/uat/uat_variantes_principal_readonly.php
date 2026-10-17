<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-10-16
 * Proposito: probar reglas de referencias y auditar columnas/SKU 11310 sin escrituras.
 * Contrato: --db agrega solo SELECT; sin ese argumento las pruebas son aisladas.
 */
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
require_once '../app/modelos/CatalogoErpDatos.php';
$ref = new ReflectionClass('CatalogoErpDatos');
$modelo = $ref->newInstanceWithoutConstructor();
$validar = $ref->getMethod('validarReferenciasVariantes');
$validar->setAccessible(true);
$skus = array(
    1 => array('id_unidad_base' => 1, 'factor_unidad_base' => 1),
    2 => array('id_unidad_base' => 1, 'factor_unidad_base' => 1),
    3 => array('id_unidad_base' => 1, 'factor_unidad_base' => 2)
);
$casos = array(
    array('principal y variante', array(1 => 'principal', 2 => '1', 3 => ''), true),
    array('varios principales', array(1 => 'principal', 2 => 'principal', 3 => ''), true),
    array('sin configurar', array(1 => '', 2 => '', 3 => ''), true),
    array('autorreferencia', array(1 => '1'), false),
    array('ciclo', array(1 => '2', 2 => '1'), false),
    array('origen ajeno', array(1 => '99'), false),
    array('origen no principal', array(1 => '', 2 => '1'), false),
    array('contenido diferente', array(1 => 'principal', 3 => '1'), false),
    array('valor malformado', array(1 => '1abc'), false)
);
$resultados = array();
foreach ($casos as $caso) {
    $aceptado = true;
    try { $validar->invoke($modelo, $skus, $caso[1]); }
    catch (Exception $e) { $aceptado = false; }
    $resultados[$caso[0]] = $aceptado === $caso[2];
}
$salida = array('ok' => !in_array(false, $resultados, true), 'pruebas' => $resultados, 'read_only' => true);
if (in_array('--db', $argv, true)) {
    $conexion = $ref->getMethod('getConexion');
    $conexion->setAccessible(true);
    $db = $conexion->invoke(new CatalogoErpDatos());
    $salida['columnas'] = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='erp_catalogo_skus' AND COLUMN_NAME IN ('es_principal_variante','id_sku_principal_variante')")->fetchAll(PDO::FETCH_COLUMN);
    $salida['sku_11310'] = $db->query("SELECT s.id_sku, s.sku, s.id_producto_erp, s.nombre, s.estatus FROM erp_catalogo_skus s WHERE s.sku='11310'")->fetchAll(PDO::FETCH_ASSOC);
    $salida['skus_vinculados_11310'] = $db->query("SELECT s.id_sku, s.sku, s.nombre, s.id_unidad_base, s.factor_unidad_base, s.estatus FROM erp_catalogo_skus s WHERE s.id_producto_erp IN (SELECT p.id_producto_erp FROM erp_catalogo_skus p WHERE p.sku='11310') ORDER BY s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($salida['sku_11310'])) {
        $consulta = $ref->getMethod('consultarReferenciasVariantes');
        $consulta->setAccessible(true);
        $salida['referencias_11310'] = $consulta->invoke($modelo, $db, intval($salida['sku_11310'][0]['id_producto_erp']));
    }
}
echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($salida['ok'] ? 0 : 1);
