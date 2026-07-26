<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: diagnosticar si una reversa DDL TMS seria viable sin ejecutarla.
 * Impacto: TMS Delivery; evita borrar esquema si ya existen servicios, eventos, costos o evidencias.
 * Contrato: read-only; no ejecuta DROP, no borra datos y no toca Ventas/Garantias/Inventario.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$modeloEsquema = new TmsEsquema();
$tablas = $modeloEsquema->tablasTms();
$ordenBorrado = array(
  "erp_tms_evidencias",
  "erp_tms_eventos",
  "erp_tms_servicios_costos",
  "erp_tms_servicios_detalle",
  "erp_tms_servicios"
);
$resultado = array();
$totalFilas = 0;
$tablasExistentes = 0;
$errores = array();

if ($db) {
  foreach ($tablas as $tabla) {
    $existe = tabla_existe($db, $tabla);
    $conteo = null;
    if ($existe) {
      $tablasExistentes++;
      try {
        $stmt = $db->query("SELECT COUNT(*) total FROM `" . str_replace("`", "", $tabla) . "`");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $conteo = $fila ? intval($fila["total"]) : null;
        $totalFilas += intval($conteo);
      } catch (Exception $e) {
        $errores[] = array("tabla" => $tabla, "mensaje" => $e->getMessage());
      }
    }
    $resultado[] = array(
      "tabla" => $tabla,
      "existe" => $existe,
      "filas" => $conteo
    );
  }
}

$schemaNoAplicado = (bool) $db && $tablasExistentes === 0;
$schemaParcial = $tablasExistentes > 0 && $tablasExistentes < count($tablas);
$reversaViable = $tablasExistentes === count($tablas) && $totalFilas === 0 && empty($errores);
$estado = "sin_conexion_mysql";
if ($db && $schemaNoAplicado) {
  $estado = "reversa_no_aplica_schema_pendiente";
} elseif ($schemaParcial) {
  $estado = "reversa_bloqueada_schema_parcial";
} elseif ($reversaViable) {
  $estado = "reversa_tecnicamente_viable_solo_con_autorizacion_futura";
} elseif ($tablasExistentes === count($tablas) && $totalFilas > 0) {
  $estado = "reversa_bloqueada_hay_datos_tms";
}

echo json_encode(array(
  "ok" => (bool) $db && empty($errores),
  "modo" => "read-only",
  "estado" => $estado,
  "conexion" => (bool) $db,
  "resumen" => array(
    "tablas_esperadas" => count($tablas),
    "tablas_existentes" => $tablasExistentes,
    "total_filas_tms" => $totalFilas,
    "schema_no_aplicado" => $schemaNoAplicado,
    "schema_parcial" => $schemaParcial,
    "reversa_tecnicamente_viable" => $reversaViable
  ),
  "tablas" => $resultado,
  "orden_borrado_si_se_autorizara_en_futuro" => $ordenBorrado,
  "errores" => $errores,
  "reglas" => array(
    "no_hay_token_reversa_activo" => true,
    "no_ejecuta_drop" => true,
    "si_hay_datos_no_borrar_schema" => true,
    "no_toca_ventas" => true,
    "no_toca_garantias" => true,
    "no_toca_inventario" => true
  ),
  "siguiente_paso" => "Usar solo como diagnostico. Si hubiera que revertir, preparar solicitud separada con respaldo reciente y evidencia de tablas vacias."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function tabla_existe($db, $tabla) {
  $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
  $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
  return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}
