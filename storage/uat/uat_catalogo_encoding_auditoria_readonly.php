<?php
/**
 * IA: GPT-5 Codex
 * Fecha: 2026-07-12
 * Proposito: auditar codificacion de Catalogo ERP sin modificar datos.
 * Impacto: solo lectura sobre configuracion de conexion, collations y muestras HEX de catalogos.
 * Contrato: ejecutar por CLI; devuelve JSON y no realiza UPDATE/INSERT/DDL.
 */

$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'panel.com.local';

require_once __DIR__ . '/../../app/config/configuracion.php';
require_once __DIR__ . '/../../app/config/mysql.php';
require_once __DIR__ . '/../../app/core/CRUD.php';

class UatCatalogoEncodingAuditoria extends CRUD
{
    public function rows(string $sql): array
    {
        return $this->getConexion()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

$db = new UatCatalogoEncodingAuditoria();

$catalogTables = [
    'erp_catalogo_categorias',
    'erp_catalogo_marcas',
    'erp_catalogo_atributos',
    'erp_catalogo_skus',
    'erp_catalogo_unidades',
];

$tableList = "'" . implode("','", $catalogTables) . "'";

$payload = [
    'conexion' => $db->rows(
        "SELECT @@character_set_client cliente,
                @@character_set_connection conexion,
                @@character_set_results resultados,
                @@collation_connection collation_conexion,
                @@character_set_database bd_charset,
                @@collation_database bd_collation"
    ),
    'tablas_catalogo' => $db->rows(
        "SELECT TABLE_NAME tabla, TABLE_COLLATION collation_tabla
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME IN ($tableList)
         ORDER BY TABLE_NAME"
    ),
    'columnas_texto_catalogo' => $db->rows(
        "SELECT TABLE_NAME tabla,
                COLUMN_NAME columna,
                CHARACTER_SET_NAME charset_columna,
                COLLATION_NAME collation_columna
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME IN ($tableList)
           AND CHARACTER_SET_NAME IS NOT NULL
         ORDER BY TABLE_NAME, COLUMN_NAME"
    ),
    'categorias_con_patron_mojibake' => $db->rows(
        "SELECT id_categoria_erp,
                codigo,
                nombre,
                HEX(nombre) hex_nombre,
                ruta,
                HEX(ruta) hex_ruta
         FROM erp_catalogo_categorias
         WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, ruta) > 0
            OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, ruta) > 0
            OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, ruta) > 0
            OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, ruta) > 0
         ORDER BY id_categoria_erp
         LIMIT 30"
    ),
    'conteos_mojibake' => $db->rows(
        "SELECT 'categorias' entidad, COUNT(*) total
         FROM erp_catalogo_categorias
         WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, ruta) > 0
            OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, ruta) > 0
            OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, ruta) > 0
            OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, ruta) > 0
         UNION ALL
         SELECT 'marcas' entidad, COUNT(*) total
         FROM erp_catalogo_marcas
         WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, nombre) > 0
         UNION ALL
         SELECT 'unidades' entidad, COUNT(*) total
         FROM erp_catalogo_unidades
         WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, nombre) > 0
            OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, nombre) > 0"
    ),
    'origenes_ecommerce' => [
        'tablas_disponibles' => $db->rows(
            "SELECT TABLE_NAME tabla
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN ('ecom_clasificaciones', 'ecom_categorias')
             ORDER BY TABLE_NAME"
        ),
        'conteos_mojibake' => $db->rows(
            "SELECT 'ecom_clasificaciones' entidad, COUNT(*) total
             FROM ecom_clasificaciones
             WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, clasificacion) > 0
                OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, clasificacion) > 0
                OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, clasificacion) > 0
                OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, clasificacion) > 0
             UNION ALL
             SELECT 'ecom_categorias' entidad, COUNT(*) total
             FROM ecom_categorias
             WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, categoria) > 0
                OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, categoria) > 0
                OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, categoria) > 0
                OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, categoria) > 0"
        ),
        'muestras' => $db->rows(
            "SELECT 'ecom_clasificaciones' entidad,
                    id_clasificacion id_origen,
                    clasificacion texto,
                    HEX(clasificacion) hex_texto
             FROM ecom_clasificaciones
             WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, clasificacion) > 0
                OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, clasificacion) > 0
                OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, clasificacion) > 0
                OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, clasificacion) > 0
             UNION ALL
             SELECT 'ecom_categorias' entidad,
                    id_categoria id_origen,
                    categoria texto,
                    HEX(categoria) hex_texto
             FROM ecom_categorias
             WHERE LOCATE(_utf8mb4'Ã' COLLATE utf8mb4_bin, categoria) > 0
                OR LOCATE(_utf8mb4'Â' COLLATE utf8mb4_bin, categoria) > 0
                OR LOCATE(_utf8mb4'├' COLLATE utf8mb4_bin, categoria) > 0
                OR LOCATE(_utf8mb4'┬' COLLATE utf8mb4_bin, categoria) > 0
             ORDER BY entidad, id_origen
             LIMIT 30"
        ),
    ],
];

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
