<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: evaluar si una reversa tecnica de DDL ecommerce podria considerarse sin ejecutar DROP.
 * Impacto: protege datos reales; solo permite pensar en reversa si tablas nuevas existen y estan vacias.
 * Contrato: read-only; no borra tablas, no modifica configuracion y no toca inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class EcommerceReversaReadOnly extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$db = (new EcommerceReversaReadOnly())->conexion();
$tablas = array(
  "erp_ecommerce_publicaciones",
  "erp_ecommerce_configuracion",
  "erp_ecommerce_cotizaciones",
  "erp_ecommerce_cotizaciones_detalle",
  "erp_ecommerce_cotizaciones_eventos"
);

$detalle = array();
$bloqueos = array();
$advertencias = array();
$existentes = 0;

if (!$db) {
  $bloqueos[] = "conexion_mysql_no_disponible";
} else {
  foreach ($tablas as $tabla) {
    $existe = tablaExisteReversa($db, $tabla);
    if ($existe) {
      $existentes++;
    }
    $filas = null;
    if ($existe) {
      $filas = contarFilasReversa($db, $tabla);
      if ($filas > 0) {
        $bloqueos[] = "tabla_con_datos_" . $tabla;
      }
    } else {
      $advertencias[] = "tabla_no_existe_" . $tabla;
    }
    $detalle[$tabla] = array(
      "existe" => $existe,
      "filas" => $filas,
      "vacia" => $existe ? $filas === 0 : null
    );
  }
}

$reversaAplica = $existentes > 0;
$dropSql = array(
  "DROP TABLE IF EXISTS `erp_ecommerce_cotizaciones_eventos`;",
  "DROP TABLE IF EXISTS `erp_ecommerce_cotizaciones_detalle`;",
  "DROP TABLE IF EXISTS `erp_ecommerce_cotizaciones`;",
  "DROP TABLE IF EXISTS `erp_ecommerce_configuracion`;",
  "DROP TABLE IF EXISTS `erp_ecommerce_publicaciones`;"
);

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "etapa" => $reversaAplica ? (empty($bloqueos) ? "reversa_tecnica_considerable" : "reversa_bloqueada_por_datos") : "reversa_no_aplica_sin_tablas",
  "puede_considerar_reversa_tecnica" => $reversaAplica && empty($bloqueos),
  "detalle" => $detalle,
  "bloqueos" => $bloqueos,
  "advertencias" => $advertencias,
  "sql_reversa_no_ejecutado" => $dropSql,
  "condiciones" => array(
    "requiere_respaldo_externo" => true,
    "requiere_autorizacion_explicita_posterior" => true,
    "solo_si_tablas_vacias" => true,
    "no_usar_si_hay_publicaciones_o_cotizaciones_reales" => true
  ),
  "guardrails" => array(
    "no_ejecuta_drop" => true,
    "no_escribe_bd" => true,
    "no_toca_ecom_legacy" => true,
    "no_toca_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tablaExisteReversa($db, $tabla) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function contarFilasReversa($db, $tabla) {
  $tablaSegura = str_replace("`", "``", $tabla);
  $stmt = $db->query("SELECT COUNT(*) FROM `" . $tablaSegura . "`");
  return intval($stmt->fetchColumn());
}
