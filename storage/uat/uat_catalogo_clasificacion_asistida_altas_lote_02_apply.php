<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-15
 * Proposito: aplicar categorias principales para los candidatos fijos de alta confianza del lote 02.
 * Impacto: Catalogo ERP; escribe relaciones producto-categoria solo despues de autorizacion explicita.
 * Contrato: lote cerrado despues de su aplicacion; no debe reutilizarse sobre nuevos productos sin crear otro lote.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoClasificacionAsistidaAltasLote02Apply extends CRUD {
  const TOKEN = "CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_02";
  const LOTE_CERRADO = true;

  private $candidatos = array(
    array(
      "id_producto_erp" => 1395,
      "codigo_producto" => "ECOM-1876",
      "nombre" => "Alimento mix frutal para hamster",
      "id_categoria_erp" => 536,
      "codigo_categoria" => "CAT16-MAMIFEROS-HAMSTER-ALIM-ALIMENTOS",
      "ruta_categoria" => "Pequenos mamiferos / Hamster / Alimentacion / Alimentos",
      "motivo" => "Alimento para hamster."
    ),
    array(
      "id_producto_erp" => 1394,
      "codigo_producto" => "ECOM-1875",
      "nombre" => "Alimento pet food para hamster",
      "id_categoria_erp" => 536,
      "codigo_categoria" => "CAT16-MAMIFEROS-HAMSTER-ALIM-ALIMENTOS",
      "ruta_categoria" => "Pequenos mamiferos / Hamster / Alimentacion / Alimentos",
      "motivo" => "Alimento para hamster."
    ),
    array(
      "id_producto_erp" => 1393,
      "codigo_producto" => "ECOM-1874",
      "nombre" => "Alimento mix zanahorita para hamster",
      "id_categoria_erp" => 536,
      "codigo_categoria" => "CAT16-MAMIFEROS-HAMSTER-ALIM-ALIMENTOS",
      "ruta_categoria" => "Pequenos mamiferos / Hamster / Alimentacion / Alimentos",
      "motivo" => "Alimento para hamster."
    )
  );

  public function ejecutar($execute, $token, $respaldo) {
    if (self::LOTE_CERRADO) {
      return $this->respuesta("info", "Lote 02 cerrado despues de aplicarse; genera un lote nuevo para mas productos.", array(
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
      return $this->respuesta("success", "Clasificacion asistida aplicada para altas de lote 02.", array(
        "resumen" => $resumen
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      $resumen["errores"][] = $e->getMessage();
      return $this->respuesta("error", "Error al aplicar clasificacion asistida. No se confirmaron cambios.", array(
        "resumen" => $resumen
      ));
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

$uat = new UatCatalogoClasificacionAsistidaAltasLote02Apply();
echo json_encode($uat->ejecutar($execute, $token, $respaldo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
