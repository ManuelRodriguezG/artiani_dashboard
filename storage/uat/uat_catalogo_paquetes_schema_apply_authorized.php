<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: aplica DDL acotado para paquetes configurables con token explicito.
 * Impacto: Catalogo ERP; crea solo las 4 tablas de paquetes configurables.
 * Contrato: requiere --token=CATALOGO_PAQUETES_CONFIGURABLES_DDL y --respaldo externo valido; no toca recepcion variable, legacy ni otros modulos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpEsquema.php";

class UatCatalogoPaquetesSchemaApplyAuthorized extends CRUD {
  const TOKEN = "CATALOGO_PAQUETES_CONFIGURABLES_DDL";

  private $tablas = array(
    "erp_catalogo_sku_paquetes",
    "erp_catalogo_sku_paquete_componentes",
    "erp_catalogo_sku_paquete_grupos",
    "erp_catalogo_sku_paquete_grupo_opciones"
  );

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

    $preflight = $this->preflightEsquema();
    if (!$preflight["ok"]) {
      return array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "Preflight de esquema no coincide con el alcance autorizado; no se ejecuto DDL.",
        "depurar" => array(
          "autorizacion" => $validacion,
          "preflight" => $preflight
        )
      );
    }

    $db = $this->getConexion();
    $resultados = array();
    $resultados[] = $this->crearTablaPaquetes($db);
    $resultados[] = $this->crearTablaComponentes($db);
    $resultados[] = $this->crearTablaGrupos($db);
    $resultados[] = $this->crearTablaOpciones($db);
    $postflight = $this->preflightEsquema();

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "DDL acotado de paquetes configurables aplicado.",
      "depurar" => array(
        "respaldo" => $validacion["respaldo"],
        "preflight" => $preflight,
        "resultados" => $resultados,
        "postflight" => $postflight,
        "alcance" => $this->tablas
      )
    );
  }

  private function crearTablaPaquetes($db) {
    $sql = "CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquetes` (
      `id_paquete` BIGINT NOT NULL AUTO_INCREMENT,
      `id_sku_paquete` BIGINT NOT NULL,
      `tipo_paquete` VARCHAR(30) NOT NULL DEFAULT 'simple',
      `modo_disponibilidad` VARCHAR(30) NOT NULL DEFAULT 'por_componentes',
      `permite_configuracion_cliente` TINYINT(1) NOT NULL DEFAULT 0,
      `permite_desarmar` TINYINT(1) NOT NULL DEFAULT 0,
      `requiere_armado_almacen` TINYINT(1) NOT NULL DEFAULT 0,
      `observaciones` TEXT NULL,
      `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
      `creado_por` INT NULL,
      `actualizado_por` INT NULL,
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NULL,
      PRIMARY KEY (`id_paquete`),
      UNIQUE KEY `idx_catalogo_paquete_sku` (`id_sku_paquete`),
      KEY `idx_catalogo_paquete_tipo` (`tipo_paquete`),
      KEY `idx_catalogo_paquete_estatus` (`estatus`),
      CONSTRAINT `fk_catalogo_paquete_sku` FOREIGN KEY (`id_sku_paquete`) REFERENCES `erp_catalogo_skus` (`id_sku`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $db->exec($sql);
    return array("tabla" => "erp_catalogo_sku_paquetes", "existe" => $this->tablaExisteLocal($db, "erp_catalogo_sku_paquetes"));
  }

  private function crearTablaComponentes($db) {
    $sql = "CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquete_componentes` (
      `id_componente` BIGINT NOT NULL AUTO_INCREMENT,
      `id_paquete` BIGINT NOT NULL,
      `id_sku_componente` BIGINT NOT NULL,
      `cantidad` DECIMAL(18,6) NOT NULL,
      `id_unidad` INT NULL,
      `factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
      `orden` INT NOT NULL DEFAULT 0,
      `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NULL,
      PRIMARY KEY (`id_componente`),
      KEY `idx_catalogo_paquete_componente_paquete` (`id_paquete`),
      KEY `idx_catalogo_paquete_componente_sku` (`id_sku_componente`),
      CONSTRAINT `fk_catalogo_paquete_componente_paquete` FOREIGN KEY (`id_paquete`) REFERENCES `erp_catalogo_sku_paquetes` (`id_paquete`),
      CONSTRAINT `fk_catalogo_paquete_componente_sku` FOREIGN KEY (`id_sku_componente`) REFERENCES `erp_catalogo_skus` (`id_sku`),
      CONSTRAINT `fk_catalogo_paquete_componente_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $db->exec($sql);
    return array("tabla" => "erp_catalogo_sku_paquete_componentes", "existe" => $this->tablaExisteLocal($db, "erp_catalogo_sku_paquete_componentes"));
  }

  private function crearTablaGrupos($db) {
    $sql = "CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquete_grupos` (
      `id_grupo` BIGINT NOT NULL AUTO_INCREMENT,
      `id_paquete` BIGINT NOT NULL,
      `codigo` VARCHAR(80) NOT NULL,
      `nombre` VARCHAR(150) NOT NULL,
      `descripcion` VARCHAR(255) NULL,
      `min_selecciones` INT NOT NULL DEFAULT 1,
      `max_selecciones` INT NOT NULL DEFAULT 1,
      `modo_cantidad` VARCHAR(30) NOT NULL DEFAULT 'cantidad_fija',
      `cantidad_total_grupo` DECIMAL(18,6) NULL,
      `obligatorio` TINYINT(1) NOT NULL DEFAULT 1,
      `orden` INT NOT NULL DEFAULT 0,
      `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NULL,
      PRIMARY KEY (`id_grupo`),
      UNIQUE KEY `idx_catalogo_paquete_grupo_codigo` (`id_paquete`, `codigo`),
      KEY `idx_catalogo_paquete_grupo_paquete` (`id_paquete`),
      CONSTRAINT `fk_catalogo_paquete_grupo_paquete` FOREIGN KEY (`id_paquete`) REFERENCES `erp_catalogo_sku_paquetes` (`id_paquete`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $db->exec($sql);
    return array("tabla" => "erp_catalogo_sku_paquete_grupos", "existe" => $this->tablaExisteLocal($db, "erp_catalogo_sku_paquete_grupos"));
  }

  private function crearTablaOpciones($db) {
    $sql = "CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquete_grupo_opciones` (
      `id_opcion` BIGINT NOT NULL AUTO_INCREMENT,
      `id_grupo` BIGINT NOT NULL,
      `id_sku_opcion` BIGINT NOT NULL,
      `cantidad_default` DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
      `cantidad_minima` DECIMAL(18,6) NULL,
      `cantidad_maxima` DECIMAL(18,6) NULL,
      `id_unidad` INT NULL,
      `factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
      `permite_cantidad_editable` TINYINT(1) NOT NULL DEFAULT 0,
      `orden` INT NOT NULL DEFAULT 0,
      `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NULL,
      PRIMARY KEY (`id_opcion`),
      KEY `idx_catalogo_paquete_opcion_grupo` (`id_grupo`),
      KEY `idx_catalogo_paquete_opcion_sku` (`id_sku_opcion`),
      CONSTRAINT `fk_catalogo_paquete_opcion_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `erp_catalogo_sku_paquete_grupos` (`id_grupo`),
      CONSTRAINT `fk_catalogo_paquete_opcion_sku` FOREIGN KEY (`id_sku_opcion`) REFERENCES `erp_catalogo_skus` (`id_sku`),
      CONSTRAINT `fk_catalogo_paquete_opcion_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $db->exec($sql);
    return array("tabla" => "erp_catalogo_sku_paquete_grupo_opciones", "existe" => $this->tablaExisteLocal($db, "erp_catalogo_sku_paquete_grupo_opciones"));
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

  private function preflightEsquema() {
    $auditoria = (new CatalogoErpEsquema())->auditarCatalogoErp();
    $detalle = isset($auditoria["depurar"]["auditoria"]) ? $auditoria["depurar"]["auditoria"] : array();
    $resumen = isset($auditoria["depurar"]["resumen"]) ? $auditoria["depurar"]["resumen"] : array();
    $faltantes = array();

    foreach ($detalle as $tabla => $info) {
      $faltanColumnas = isset($info["faltantes"]["columnas"]) ? array_keys($info["faltantes"]["columnas"]) : array();
      $faltanIndices = isset($info["faltantes"]["indices"]) ? array_keys($info["faltantes"]["indices"]) : array();
      $faltanIndicesColumnas = isset($info["faltantes"]["indices_columnas"]) ? array_keys($info["faltantes"]["indices_columnas"]) : array();
      if (empty($info["existe"]) || !empty($faltanColumnas) || !empty($faltanIndices) || !empty($faltanIndicesColumnas)) {
        $faltantes[$tabla] = array(
          "existe" => !empty($info["existe"]),
          "columnas_faltantes" => $faltanColumnas,
          "indices_faltantes" => $faltanIndices,
          "indices_columnas_distintas" => $faltanIndicesColumnas
        );
      }
    }

    $faltantesExactos = array_keys($faltantes) === $this->tablas || empty($faltantes);
    return array(
      "ok" => empty($auditoria["error"]) && $faltantesExactos,
      "resumen" => $resumen,
      "faltantes_exactos_o_cero" => $faltantesExactos,
      "faltantes" => $faltantes
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
echo json_encode((new UatCatalogoPaquetesSchemaApplyAuthorized())->ejecutar($token, $respaldo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
