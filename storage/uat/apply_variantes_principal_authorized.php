<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-10-16
 * Proposito: aplicar exclusivamente las dos columnas autorizadas para referencias de variantes.
 * Impacto: DDL productivo acotado; no configura principales, costos ni precios.
 * Contrato: solo CLI, --apply, respaldo externo completo y SHA256; detiene ante primer error.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
$args = getopt('', array('apply', 'respaldo:', 'sha256:'));
$pasos = array();
try {
    $db = new PDO('mysql:host=' . MYSQLHOST . ';port=' . MYSQLPORT . ';dbname=' . MYSQLBASE . ';charset=utf8mb4', MYSQLUSER, MYSQLPASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $columnas = array(
        'es_principal_variante' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'id_sku_principal_variante' => 'BIGINT NULL'
    );
    $antes = $db->query("SELECT COUNT(*) FROM erp_catalogo_skus")->fetchColumn();
    if (isset($args['apply'])) {
        $archivo = isset($args['respaldo']) ? realpath($args['respaldo']) : false;
        $directorio = realpath('C:/xampp/panel_db_backups');
        if (!$archivo || !$directorio || strcasecmp(dirname($archivo), $directorio) !== 0 || filesize($archivo) < 1024) {
            throw new RuntimeException('Respaldo externo invalido');
        }
        if (empty($args['sha256']) || !hash_equals(strtolower($args['sha256']), hash_file('sha256', $archivo))) {
            throw new RuntimeException('El SHA256 del respaldo no coincide');
        }
        $fh = fopen($archivo, 'rb');
        $inicio = fread($fh, 4096);
        fseek($fh, -min(4096, filesize($archivo)), SEEK_END);
        $final = stream_get_contents($fh);
        fclose($fh);
        if (strpos($inicio, 'Database: ' . MYSQLBASE) === false || strpos($final, '-- Dump completed on') === false) {
            throw new RuntimeException('El respaldo no corresponde a esta base o esta incompleto');
        }
        $db->exec('SET SESSION lock_wait_timeout=15');
    }
    foreach ($columnas as $nombre => $definicion) {
        $stmt = $db->prepare("SELECT DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='erp_catalogo_skus' AND COLUMN_NAME=:columna");
        $stmt->execute(array(':columna' => $nombre));
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($actual) {
            $valida = $nombre === 'es_principal_variante'
                ? ($actual['DATA_TYPE'] === 'tinyint' && $actual['IS_NULLABLE'] === 'NO' && (string)$actual['COLUMN_DEFAULT'] === '0')
                : ($actual['DATA_TYPE'] === 'bigint' && $actual['IS_NULLABLE'] === 'YES');
            if (!$valida) { throw new RuntimeException('Columna existente incompatible: ' . $nombre); }
            $pasos[] = array('columna' => $nombre, 'accion' => 'ya_disponible');
            continue;
        }
        if (isset($args['apply'])) {
            $db->exec('ALTER TABLE erp_catalogo_skus ADD COLUMN `' . $nombre . '` ' . $definicion);
        }
        $pasos[] = array('columna' => $nombre, 'accion' => isset($args['apply']) ? 'creada' : 'pendiente');
    }
    $salida = array('ok' => true, 'aplicado' => isset($args['apply']), 'pasos' => $pasos, 'skus_antes' => intval($antes),
        'skus_despues' => intval($db->query('SELECT COUNT(*) FROM erp_catalogo_skus')->fetchColumn()));
    if (isset($args['apply'])) {
        $salida['relaciones_configuradas'] = intval($db->query('SELECT COUNT(*) FROM erp_catalogo_skus WHERE es_principal_variante<>0 OR id_sku_principal_variante IS NOT NULL')->fetchColumn());
        $salida['respaldo'] = $archivo;
        $salida['sha256'] = hash_file('sha256', $archivo);
    }
    echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    // No imprimir excepciones PDO que puedan revelar detalles internos de conexion.
    echo json_encode(array('ok' => false, 'pasos_completados' => $pasos, 'error' => $e instanceof PDOException ? 'Error de base de datos; ejecucion detenida' : $e->getMessage())) . PHP_EOL;
    exit(1);
}
