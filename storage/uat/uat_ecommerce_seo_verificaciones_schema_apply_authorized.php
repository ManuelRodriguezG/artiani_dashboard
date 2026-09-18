<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-18.
 * Proposito: crear la tabla de marcas de verificacion SEO con autorizacion explicita y respaldo externo.
 * Impacto: Ecommerce SEO; permite guardar URLs probadas sin depender de localStorage.
 * Contrato: apply_authorized; crea solo `erp_ecommerce_seo_verificaciones`; no modifica reglas SEO, sitemap, frontend ni catalogo.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentos($argv);
$token = isset($args["autorizar"]) ? $args["autorizar"] : "";
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";
$tokenEsperado = "ECOMMERCE_SEO_VERIFICACIONES_DDL";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) {
  salida(false, "Respaldo externo valido requerido en C:\\xampp\\panel_db_backups", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo
  ));
}

$errorConexion = "";
$db = conexionPdo($errorConexion);
if (!$db) {
  salida(false, "Conexion MySQL no disponible", array(
    "ejecutado" => false,
    "base" => defined("MYSQLBASE") ? MYSQLBASE : "",
    "host" => defined("MYSQLHOST") ? MYSQLHOST : "",
    "port" => defined("MYSQLPORT") ? MYSQLPORT : "",
    "error" => $errorConexion
  ));
}

$antes = tablaExiste($db, "erp_ecommerce_seo_verificaciones");
$sql = "CREATE TABLE IF NOT EXISTS `erp_ecommerce_seo_verificaciones` (
  `id_verificacion` BIGINT NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(255) NOT NULL,
  `tipo` VARCHAR(30) NOT NULL,
  `path` VARCHAR(500) NOT NULL,
  `url_origen` VARCHAR(700) NULL,
  `url_destino` VARCHAR(700) NULL,
  `status_esperado` SMALLINT NULL,
  `resultado_http` VARCHAR(40) NULL,
  `status_http` SMALLINT NULL,
  `destino_status_http` SMALLINT NULL,
  `probada` TINYINT(1) NOT NULL DEFAULT 1,
  `observaciones` TEXT NULL,
  `fecha_verificacion` DATETIME NULL,
  `verificado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_verificacion`),
  UNIQUE KEY `idx_ecom_seo_verif_clave` (`clave`),
  KEY `idx_ecom_seo_verif_tipo` (`tipo`, `probada`),
  KEY `idx_ecom_seo_verif_fecha` (`fecha_verificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

try {
  $db->exec($sql);
} catch (Exception $e) {
  salida(false, $e->getMessage(), array(
    "ejecutado" => false,
    "respaldo" => $respaldo,
    "tabla_antes" => $antes
  ));
}

$despues = tablaExiste($db, "erp_ecommerce_seo_verificaciones");
salida($despues, $despues ? "Tabla de verificacion SEO disponible" : "Tabla de verificacion SEO pendiente", array(
  "modo" => "apply_authorized",
  "ejecutado" => true,
  "base" => MYSQLBASE,
  "respaldo" => $respaldo,
  "tabla" => "erp_ecommerce_seo_verificaciones",
  "tabla_antes" => $antes,
  "tabla_despues" => $despues,
  "guardrails" => array(
    "solo_crea_tabla_verificaciones" => true,
    "no_modifica_redirecciones" => true,
    "no_modifica_sitemap" => true,
    "no_toca_catalogo" => true,
    "no_toca_inventario" => true
  )
));

function conexionPdo(&$errorConexion = "") {
  try {
    return new PDO(
      "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE,
      MYSQLUSER,
      MYSQLPASS,
      array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
      )
    );
  } catch (Exception $e) {
    $errorConexion = $e->getMessage();
    return null;
  }
}

function tablaExiste($db, $tabla) {
  $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
  $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
  return (bool) $stmt->fetchColumn();
}

function argumentos($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
