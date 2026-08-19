<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-17
 * Proposito: aplicar categorias principales para los candidatos fijos de alta confianza del lote 04.
 * Impacto: Catalogo ERP; escribe relaciones producto-categoria solo despues de autorizacion explicita.
 * Contrato: lote cerrado despues de su aplicacion; no debe reutilizarse sobre nuevos productos sin crear otro lote.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoClasificacionAsistidaAltasLote04Apply extends CRUD {
  const TOKEN = "CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_04";
  const LOTE_CERRADO = true;

  private $candidatos = array(
    array("id_producto_erp" => 1587, "id_categoria_erp" => 596, "codigo_producto" => "JIR-MINI", "codigo_categoria" => "CAT16-ACUARIO-DECO-RAICES-TRONCOS", "motivo" => "Raiz/tronco decorativo para acuario."),
    array("id_producto_erp" => 1586, "id_categoria_erp" => 596, "codigo_producto" => "JIR-CH", "codigo_categoria" => "CAT16-ACUARIO-DECO-RAICES-TRONCOS", "motivo" => "Raiz/tronco decorativo para acuario."),
    array("id_producto_erp" => 1585, "id_categoria_erp" => 596, "codigo_producto" => "JIR-MD", "codigo_categoria" => "CAT16-ACUARIO-DECO-RAICES-TRONCOS", "motivo" => "Raiz/tronco decorativo para acuario."),
    array("id_producto_erp" => 1584, "id_categoria_erp" => 596, "codigo_producto" => "JIR-GD", "codigo_categoria" => "CAT16-ACUARIO-DECO-RAICES-TRONCOS", "motivo" => "Raiz/tronco decorativo para acuario."),
    array("id_producto_erp" => 1575, "id_categoria_erp" => 597, "codigo_producto" => "INOS-PESS", "codigo_categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "motivo" => "Sustrato para terrario/reptiles."),
    array("id_producto_erp" => 1571, "id_categoria_erp" => 597, "codigo_producto" => "PROD1038", "codigo_categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "motivo" => "Sustrato o corteza para terrario/reptiles."),
    array("id_producto_erp" => 1570, "id_categoria_erp" => 597, "codigo_producto" => "PROD1004", "codigo_categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "motivo" => "Sustrato de coco para terrario/reptiles."),
    array("id_producto_erp" => 1568, "id_categoria_erp" => 597, "codigo_producto" => "PROD0965", "codigo_categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "motivo" => "Sustrato de coco para terrario/reptiles."),
    array("id_producto_erp" => 1561, "id_categoria_erp" => 594, "codigo_producto" => "TEMA-M6090", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1560, "id_categoria_erp" => 594, "codigo_producto" => "TEMA-T6090", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1559, "id_categoria_erp" => 594, "codigo_producto" => "TEMA-M604060", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1558, "id_categoria_erp" => 594, "codigo_producto" => "TEMA-M4060", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1557, "id_categoria_erp" => 594, "codigo_producto" => "TEMA-M3030", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1556, "id_categoria_erp" => 594, "codigo_producto" => "TEMA-T3030", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1443, "id_categoria_erp" => 594, "codigo_producto" => "ECOM-1927", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1442, "id_categoria_erp" => 594, "codigo_producto" => "TERA-C4060", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1441, "id_categoria_erp" => 594, "codigo_producto" => "TERA-C4050", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1439, "id_categoria_erp" => 594, "codigo_producto" => "ECOM-1921", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1378, "id_categoria_erp" => 594, "codigo_producto" => "ECOM-1858", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1360, "id_categoria_erp" => 594, "codigo_producto" => "TERA-5060", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1359, "id_categoria_erp" => 594, "codigo_producto" => "TERA-6060", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1354, "id_categoria_erp" => 448, "codigo_producto" => "ECOM-1834", "codigo_categoria" => "CAT16-GATOS-JUEGO-RASCADORES", "motivo" => "Rascador para gato."),
    array("id_producto_erp" => 1352, "id_categoria_erp" => 464, "codigo_producto" => "ECOM-1832", "codigo_categoria" => "CAT16-ACUARIO-EQUIP-FILTRACION", "motivo" => "Filtro/equipo de filtracion."),
    array("id_producto_erp" => 1347, "id_categoria_erp" => 594, "codigo_producto" => "TERA-2020", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1346, "id_categoria_erp" => 594, "codigo_producto" => "TERA-8035", "codigo_categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "motivo" => "Terrario general para reptiles."),
    array("id_producto_erp" => 1345, "id_categoria_erp" => 458, "codigo_producto" => "ECOM-1825", "codigo_categoria" => "CAT16-ACUARIO-PECERAS-PECERAS", "motivo" => "Pecera como contenedor principal."),
    array("id_producto_erp" => 1344, "id_categoria_erp" => 448, "codigo_producto" => "SP-3791", "codigo_categoria" => "CAT16-GATOS-JUEGO-RASCADORES", "motivo" => "Rascador para gato."),
    array("id_producto_erp" => 1343, "id_categoria_erp" => 448, "codigo_producto" => "ECOM-1823", "codigo_categoria" => "CAT16-GATOS-JUEGO-RASCADORES", "motivo" => "Rascador para gato."),
    array("id_producto_erp" => 1338, "id_categoria_erp" => 439, "codigo_producto" => "ECOM-1818", "codigo_categoria" => "CAT16-GATOS-SALUD-ARENAS", "motivo" => "Arena/control de olores para gato.")
  );

  public function ejecutar($execute, $token, $respaldo) {
    if (self::LOTE_CERRADO) {
      return $this->respuesta("info", "Lote 04 cerrado despues de aplicarse; genera un lote nuevo para mas productos.", array(
        "token_cerrado" => self::TOKEN
      ));
    }

    if ($execute && $token !== self::TOKEN) {
      return $this->respuesta("error", "Token invalido. No se aplicaron cambios.");
    }
    if ($execute && trim($respaldo) === "") {
      return $this->respuesta("error", "Falta referencia de respaldo externo. No se aplicaron cambios.");
    }

    $db = $this->getConexion();
    $resumen = array(
      "modo" => $execute ? "execute" : "preview",
      "token_requerido" => self::TOKEN,
      "respaldo_externo" => $respaldo,
      "candidatos_alta_confianza" => count($this->candidatos),
      "aplicables" => 0,
      "omitidos_con_categoria_previa" => 0,
      "insertados" => 0,
      "errores" => array()
    );
    $preview = array();

    foreach ($this->candidatos as $candidato) {
      $stmt = $db->prepare("SELECT COUNT(*)
        FROM erp_catalogo_producto_categorias
        WHERE id_producto_erp=:producto
          AND es_principal=1");
      $stmt->execute(array(":producto" => intval($candidato["id_producto_erp"])));
      if (intval($stmt->fetchColumn()) > 0) {
        $resumen["omitidos_con_categoria_previa"]++;
        continue;
      }

      $resumen["aplicables"]++;
      $preview[] = $candidato;
    }

    if (!$execute) {
      return $this->respuesta("preview", "Preview generado; no se aplicaron cambios.", array(
        "resumen" => $resumen,
        "candidatos" => $preview
      ));
    }

    try {
      $db->beginTransaction();
      $insert = $db->prepare("INSERT INTO erp_catalogo_producto_categorias
          (id_producto_erp, id_categoria_erp, es_principal)
        VALUES
          (:producto, :categoria, 1)
        ON DUPLICATE KEY UPDATE
          es_principal=VALUES(es_principal)");

      foreach ($preview as $item) {
        $insert->execute(array(
          ":producto" => intval($item["id_producto_erp"]),
          ":categoria" => intval($item["id_categoria_erp"])
        ));
        $resumen["insertados"]++;
      }

      $db->commit();
      return $this->respuesta("success", "Clasificacion asistida aplicada para altas de lote 04.", array("resumen" => $resumen));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      $resumen["errores"][] = $e->getMessage();
      return $this->respuesta("error", "Error al aplicar clasificacion asistida. No se confirmaron cambios.", array("resumen" => $resumen));
    }
  }

  private function respuesta($tipo, $mensaje, $depurar = array()) {
    return array(
      "tipo" => $tipo,
      "error" => $tipo === "error",
      "mensaje" => $mensaje,
      "depurar" => $depurar
    );
  }
}

$execute = in_array("--execute", $argv, true);
$token = "";
$respaldo = "";
foreach ($argv as $arg) {
  if (strpos($arg, "--token=") === 0) {
    $token = substr($arg, 8);
  }
  if (strpos($arg, "--respaldo=") === 0) {
    $respaldo = substr($arg, 11);
  }
}

$uat = new UatCatalogoClasificacionAsistidaAltasLote04Apply();
echo json_encode($uat->ejecutar($execute, $token, $respaldo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
