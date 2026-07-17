<?php
/**
 * IA: GPT-5 Codex
 * Fecha: 2026-07-16
 * Proposito: previsualizar o aplicar relacion masiva controlada de imagenes ecommerce hacia productos ERP sin imagen activa.
 * Impacto: Catalogo ERP; inserta imagenes en erp_catalogo_imagenes solo para productos sin imagen activa y define una portada por producto.
 * Contrato: por defecto es preview read-only; solo escribe con --execute, token autorizado y respaldo externo fuera del proyecto.
 */

$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'panel.com.local';

require_once __DIR__ . '/../../app/config/configuracion.php';
require_once __DIR__ . '/../../app/config/mysql.php';
require_once __DIR__ . '/../../app/core/CRUD.php';

class UatCatalogoImagenesProductosPortadasApply extends CRUD
{
    private const TOKEN = 'CATALOGO_IMAGENES_PRODUCTOS_PORTADAS';

    public function ejecutar(array $argv): array
    {
        $opciones = $this->opciones($argv);
        $execute = !empty($opciones['execute']);
        $token = isset($opciones['token']) ? trim((string) $opciones['token']) : '';
        $respaldo = isset($opciones['respaldo']) ? trim((string) $opciones['respaldo']) : '';
        $limit = isset($opciones['limit']) ? max(1, min(500, (int) $opciones['limit'])) : 120;
        $autorizado = $execute && $token === self::TOKEN && $this->respaldoValido($respaldo);

        $db = $this->getConexion();
        if (!$this->tablaExiste($db, 'ecom_productos_imagenes')) {
            return [
                'ok' => false,
                'modo' => 'bloqueado',
                'mensaje' => 'No existe ecom_productos_imagenes en esta base.',
            ];
        }

        $candidatasDetectadas = $this->candidatas($db);
        $candidatas = $this->filtrarImagenesExistentes($candidatasDetectadas);
        $porProducto = $this->agruparPorProducto($candidatas);
        $resumen = $this->resumen($db, $candidatas, $porProducto, count($candidatasDetectadas));

        if (!$autorizado) {
            return [
                'ok' => !$execute,
                'modo' => $execute ? 'bloqueado' : 'preview',
                'requiere' => [
                    'execute' => true,
                    'token' => self::TOKEN,
                    'respaldo_externo' => 'ruta o referencia real fuera del proyecto',
                ],
                'motivo_bloqueo' => $execute ? $this->motivoBloqueo($token, $respaldo) : '',
                'resumen' => $resumen,
                'muestra_productos' => array_slice(array_values($porProducto), 0, $limit),
                'nota' => 'No se modifico BD.',
            ];
        }

        $stmtInsert = $db->prepare("INSERT INTO erp_catalogo_imagenes
            (id_producto_erp, id_sku, tipo_imagen, url_imagen, texto_alternativo, orden, fuente, id_externo, estatus)
            VALUES (:producto, NULL, :tipo, :url, :alt, :orden, 'ecommerce', :externo, 'activo')");

        $insertadas = 0;
        $portadas = 0;
        $db->beginTransaction();
        try {
            foreach ($porProducto as $producto) {
                foreach ($producto['imagenes'] as $orden => $imagen) {
                    $tipo = $orden === 0 ? 'portada' : 'galeria';
                    $stmtInsert->execute([
                        ':producto' => (int) $producto['id_producto_erp'],
                        ':tipo' => $tipo,
                        ':url' => substr((string) $imagen['url_imagen'], 0, 700),
                        ':alt' => $producto['nombre_producto'] ?: null,
                        ':orden' => $orden,
                        ':externo' => (string) $imagen['id_producto_imagen'],
                    ]);
                    $insertadas++;
                    if ($tipo === 'portada') {
                        $portadas++;
                    }
                }
            }
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return [
            'ok' => true,
            'modo' => 'aplicado',
            'respaldo' => $respaldo,
            'productos_actualizados' => count($porProducto),
            'imagenes_insertadas' => $insertadas,
            'portadas_insertadas' => $portadas,
            'nota' => 'Solo se insertaron imagenes en productos que no tenian imagen activa antes del proceso.',
        ];
    }

    private function candidatas(PDO $db): array
    {
        $sql = "SELECT *
            FROM (
                SELECT p.id_producto_erp,
                       p.codigo_producto,
                       p.nombre AS nombre_producto,
                       e.id_producto_imagen,
                       e.id_producto AS id_producto_ecom,
                       e.tipo_imagen AS tipo_imagen_ecom,
                       e.url_imagen,
                       ROW_NUMBER() OVER (
                         PARTITION BY p.id_producto_erp
                         ORDER BY e.tipo_imagen='portada' DESC, e.id_producto_imagen
                       ) AS orden_producto
                FROM erp_catalogo_productos p
                INNER JOIN erp_catalogo_canales_vinculos v
                  ON v.id_producto_erp=p.id_producto_erp
                 AND v.canal='ecommerce'
                 AND v.id_externo REGEXP '^[0-9]+$'
                INNER JOIN ecom_productos_imagenes e
                  ON e.id_producto=CAST(v.id_externo AS UNSIGNED)
                 AND TRIM(COALESCE(e.url_imagen,''))<>''
                 AND e.url_imagen LIKE 'media/%'
                LEFT JOIN erp_catalogo_imagenes existente
                  ON existente.fuente='ecommerce'
                 AND existente.id_externo=CAST(e.id_producto_imagen AS CHAR)
                WHERE p.estatus<>'fusionado'
                  AND existente.id_imagen_erp IS NULL
                  AND NOT EXISTS (
                    SELECT 1
                    FROM erp_catalogo_imagenes activa
                    WHERE activa.id_producto_erp=p.id_producto_erp
                      AND activa.estatus='activo'
                  )
            ) candidatas
            ORDER BY id_producto_erp, orden_producto";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function filtrarImagenesExistentes(array $candidatas): array
    {
        $filtradas = [];
        foreach ($candidatas as $fila) {
            $fila['archivo_local_existe'] = $this->archivoLocalExiste((string) $fila['url_imagen']) ? 1 : 0;
            if ((int) $fila['archivo_local_existe'] !== 1) {
                continue;
            }
            $filtradas[] = $fila;
        }
        return $filtradas;
    }

    private function archivoLocalExiste(string $url): bool
    {
        if (!preg_match('/^media\//i', $url)) {
            return true;
        }
        $ruta = __DIR__ . '/../../public/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $url);
        return is_file($ruta);
    }

    private function agruparPorProducto(array $candidatas): array
    {
        $productos = [];
        foreach ($candidatas as $fila) {
            $id = (int) $fila['id_producto_erp'];
            if (!isset($productos[$id])) {
                $productos[$id] = [
                    'id_producto_erp' => $id,
                    'codigo_producto' => $fila['codigo_producto'],
                    'nombre_producto' => $fila['nombre_producto'],
                    'imagenes_total' => 0,
                    'portada_sugerida' => null,
                    'imagenes' => [],
                ];
            }
            $imagen = [
                'id_producto_imagen' => (int) $fila['id_producto_imagen'],
                'id_producto_ecom' => (int) $fila['id_producto_ecom'],
                'tipo_imagen_ecom' => $fila['tipo_imagen_ecom'],
                'url_imagen' => $fila['url_imagen'],
            ];
            $productos[$id]['imagenes'][] = $imagen;
            $productos[$id]['imagenes_total']++;
            if ($productos[$id]['portada_sugerida'] === null) {
                $productos[$id]['portada_sugerida'] = $imagen;
            }
        }
        return $productos;
    }

    private function resumen(PDO $db, array $candidatas, array $porProducto, int $candidatasDetectadas): array
    {
        return [
            'productos_sin_imagen_activa' => (int) $db->query("SELECT COUNT(*)
                FROM erp_catalogo_productos p
                WHERE p.estatus<>'fusionado'
                  AND NOT EXISTS (
                    SELECT 1 FROM erp_catalogo_imagenes i
                    WHERE i.id_producto_erp=p.id_producto_erp AND i.estatus='activo'
                  )")->fetchColumn(),
            'productos_con_imagen_ecommerce_disponible' => count($porProducto),
            'imagenes_candidatas_detectadas' => $candidatasDetectadas,
            'imagenes_candidatas_insertables' => count($candidatas),
            'imagenes_candidatas_sin_archivo_local' => max(0, $candidatasDetectadas - count($candidatas)),
            'portadas_a_insertar' => count($porProducto),
        ];
    }

    private function tablaExiste(PDO $db, string $tabla): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
        $stmt->execute([':tabla' => $tabla]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function opciones(array $argv): array
    {
        $opciones = [];
        foreach ($argv as $arg) {
            if ($arg === '--execute') {
                $opciones['execute'] = true;
                continue;
            }
            if (strpos($arg, '--') === 0 && strpos($arg, '=') !== false) {
                [$key, $value] = explode('=', substr($arg, 2), 2);
                $opciones[$key] = $value;
            }
        }
        return $opciones;
    }

    private function respaldoValido(string $respaldo): bool
    {
        if ($respaldo === ''
            || stripos($respaldo, 'ruta real') !== false
            || stripos($respaldo, 'referencia') !== false
            || stripos($respaldo, 'placeholder') !== false
            || strpos($respaldo, '<') !== false
            || strpos($respaldo, '>') !== false) {
            return false;
        }
        $normalizado = str_replace('/', '\\', $respaldo);
        return stripos($normalizado, '\\panel_de_control\\') === false && stripos($normalizado, '\\panel\\') === false;
    }

    private function motivoBloqueo(string $token, string $respaldo): string
    {
        $motivos = [];
        if ($token !== self::TOKEN) {
            $motivos[] = 'token invalido';
        }
        if (!$this->respaldoValido($respaldo)) {
            $motivos[] = 'respaldo externo invalido';
        }
        return implode('; ', $motivos);
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode((new UatCatalogoImagenesProductosPortadasApply())->ejecutar($argv), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
