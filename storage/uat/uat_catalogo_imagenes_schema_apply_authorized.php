<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: aplica DDL acotado para imagenes de marcas/categorias con token explicito.
 * Impacto: Catalogo ERP; crea solo erp_catalogo_marca_imagenes y erp_catalogo_categoria_imagenes.
 * Contrato: requiere --token=CATALOGO_IMAGENES_MARCAS_CATEGORIAS y --respaldo externo valido; no toca paquetes ni productos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoImagenesSchemaApplyAuthorized extends CRUD {
  const TOKEN = "CATALOGO_IMAGENES_MARCAS_CATEGORIAS";

  public function ejecutar($token, $respaldo) {
    $validacion = $this->validarAutorizacion($token, $respaldo);
    if (!$validacion["ok"]) {
      return array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "Autorizacion incompleta; no se ejecuto DDL.",
        "depurar" => $validacion
      );
    }

    $db = $this->getConexion();
    $resultados = array();
    $resultados[] = $this->crearTablaMarcaImagenes($db);
    $resultados[] = $this->crearTablaCategoriaImagenes($db);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "DDL acotado de imagenes de marcas/categorias aplicado.",
      "depurar" => array(
        "respaldo" => $validacion["respaldo"],
        "resultados" => $resultados,
        "alcance" => array(
          "erp_catalogo_marca_imagenes",
          "erp_catalogo_categoria_imagenes"
        )
      )
    );
  }

  private function crearTablaMarcaImagenes($db) {
    $sql = "CREATE TABLE IF NOT EXISTS `erp_catalogo_marca_imagenes` (
      `id_marca_imagen` BIGINT NOT NULL AUTO_INCREMENT,
      `id_marca_erp` INT NOT NULL,
      `tipo_imagen` VARCHAR(30) NOT NULL DEFAULT 'logo',
      `url_imagen` VARCHAR(700) NOT NULL,
      `texto_alternativo` VARCHAR(255) NULL,
      `orden` INT NOT NULL DEFAULT 0,
      `estatus` VARCHAR(20) NOT NULL DEFAULT 'activo',
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NULL,
      PRIMARY KEY (`id_marca_imagen`),
      KEY `idx_marca_imagen_marca` (`id_marca_erp`, `estatus`),
      KEY `idx_marca_imagen_tipo` (`tipo_imagen`, `estatus`),
      CONSTRAINT `fk_marca_imagen_marca` FOREIGN KEY (`id_marca_erp`) REFERENCES `erp_catalogo_marcas` (`id_marca_erp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $db->exec($sql);
    return array("tabla" => "erp_catalogo_marca_imagenes", "existe" => $this->tablaExisteLocal($db, "erp_catalogo_marca_imagenes"));
  }

  private function crearTablaCategoriaImagenes($db) {
    $sql = "CREATE TABLE IF NOT EXISTS `erp_catalogo_categoria_imagenes` (
      `id_categoria_imagen` BIGINT NOT NULL AUTO_INCREMENT,
      `id_categoria_erp` INT NOT NULL,
      `tipo_imagen` VARCHAR(30) NOT NULL DEFAULT 'icono',
      `url_imagen` VARCHAR(700) NOT NULL,
      `texto_alternativo` VARCHAR(255) NULL,
      `orden` INT NOT NULL DEFAULT 0,
      `estatus` VARCHAR(20) NOT NULL DEFAULT 'activo',
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NULL,
      PRIMARY KEY (`id_categoria_imagen`),
      KEY `idx_categoria_imagen_categoria` (`id_categoria_erp`, `estatus`),
      KEY `idx_categoria_imagen_tipo` (`tipo_imagen`, `estatus`),
      CONSTRAINT `fk_categoria_imagen_categoria` FOREIGN KEY (`id_categoria_erp`) REFERENCES `erp_catalogo_categorias` (`id_categoria_erp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $db->exec($sql);
    return array("tabla" => "erp_catalogo_categoria_imagenes", "existe" => $this->tablaExisteLocal($db, "erp_catalogo_categoria_imagenes"));
  }

  private function validarAutorizacion($token, $respaldo) {
    $respaldo = trim((string)$respaldo);
    $placeholder = preg_match('/(PENDIENTE|RUTA_O|REFERENCIA_EXTERNA|<ruta|ruta real)/i', $respaldo) === 1;
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $esRutaLocal ? file_exists($respaldo) : null;
    $legible = $esRutaLocal ? ($existe && is_readable($respaldo)) : null;
    $tamano = $esRutaLocal && $existe ? filesize($respaldo) : null;
    $dentroProyecto = $esRutaLocal && stripos(str_replace("/", "\\", $respaldo), "C:\\xampp\\htdocs\\panel") === 0;
    $respaldoOk = strlen($respaldo) >= 8 && !$placeholder && !$dentroProyecto && (!$esRutaLocal || ($existe && $legible && $tamano > 0));
    return array(
      "ok" => $token === self::TOKEN && $respaldoOk,
      "token_ok" => $token === self::TOKEN,
      "respaldo" => array(
        "referencia" => $respaldo,
        "placeholder_detectado" => $placeholder,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $existe,
        "archivo_legible" => $legible,
        "tamano_bytes" => $tamano,
        "dentro_del_proyecto" => $dentroProyecto,
        "ok" => $respaldoOk
      )
    );
  }

  private function tablaExisteLocal($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
  }
}

$token = "";
$respaldo = "";
foreach (isset($argv) ? $argv : array() as $arg) {
  if (strpos($arg, "--token=") === 0) {
    $token = trim(substr($arg, 8), "\"' ");
  }
  if (strpos($arg, "--respaldo=") === 0) {
    $respaldo = trim(substr($arg, 11), "\"' ");
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoImagenesSchemaApplyAuthorized())->ejecutar($token, $respaldo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
