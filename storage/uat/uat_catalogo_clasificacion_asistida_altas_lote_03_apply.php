<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-15
 * Proposito: aplicar categorias principales para los candidatos fijos de alta confianza del lote 03.
 * Impacto: Catalogo ERP; escribe relaciones producto-categoria solo despues de autorizacion explicita.
 * Contrato: lote cerrado despues de su aplicacion; no debe reutilizarse sobre nuevos productos sin crear otro lote.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoClasificacionAsistidaAltasLote03Apply extends CRUD {
  const TOKEN = "CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_03";
  const LOTE_CERRADO = true;

  private $candidatos = array(
    array("id_producto_erp" => 1532, "codigo_producto" => "PECPESQUINA", "nombre" => "Pecera panoramica esquinera", "id_categoria_erp" => 458, "codigo_categoria" => "CAT16-ACUARIO-PECERAS-PECERAS", "ruta_categoria" => "Acuario y peces / Peceras, acuarios y muebles / Peceras", "motivo" => "Pecera como contenedor principal."),
    array("id_producto_erp" => 1531, "codigo_producto" => "PECP5030-KITB", "nombre" => "Pecera panoramica equipo basico 50 litros", "id_categoria_erp" => 460, "codigo_categoria" => "CAT16-ACUARIO-PECERAS-EQUIPADAS", "ruta_categoria" => "Acuario y peces / Peceras, acuarios y muebles / Peceras equipadas", "motivo" => "Pecera con equipo incluido."),
    array("id_producto_erp" => 1530, "codigo_producto" => "KITPECPAN2023-15L", "nombre" => "Pecera panoramica equipada 15 litros", "id_categoria_erp" => 460, "codigo_categoria" => "CAT16-ACUARIO-PECERAS-EQUIPADAS", "ruta_categoria" => "Acuario y peces / Peceras, acuarios y muebles / Peceras equipadas", "motivo" => "Pecera equipada."),
    array("id_producto_erp" => 1529, "codigo_producto" => "KITPECPAN20-6L", "nombre" => "Pecera panoramica equipada 6 litros", "id_categoria_erp" => 460, "codigo_categoria" => "CAT16-ACUARIO-PECERAS-EQUIPADAS", "ruta_categoria" => "Acuario y peces / Peceras, acuarios y muebles / Peceras equipadas", "motivo" => "Pecera equipada."),
    array("id_producto_erp" => 1401, "codigo_producto" => "ECOM-1882", "nombre" => "Papilla alimento para baby birds para pajaros", "id_categoria_erp" => 508, "codigo_categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "ruta_categoria" => "Aves / Alimentacion / Alimentos", "motivo" => "Alimento para aves/pajaros."),
    array("id_producto_erp" => 1400, "codigo_producto" => "ECOM-1881", "nombre" => "Alimento para parrots para pajaros", "id_categoria_erp" => 508, "codigo_categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "ruta_categoria" => "Aves / Alimentacion / Alimentos", "motivo" => "Alimento para aves/pajaros."),
    array("id_producto_erp" => 1399, "codigo_producto" => "ECOM-1880", "nombre" => "Alimento parakeets para pajaros", "id_categoria_erp" => 508, "codigo_categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "ruta_categoria" => "Aves / Alimentacion / Alimentos", "motivo" => "Alimento para aves/pajaros."),
    array("id_producto_erp" => 1387, "codigo_producto" => "ECOM-1867", "nombre" => "Nido casa mediana para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1386, "codigo_producto" => "ECOM-1866", "nombre" => "Nido esfera de yute mediano para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1385, "codigo_producto" => "ECOM-1865", "nombre" => "Nido esfera de yute chico para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1384, "codigo_producto" => "ECOM-1864", "nombre" => "Nido rectangular mediano con conpuerta para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1383, "codigo_producto" => "ECOM-1863", "nombre" => "Nido rectangular grande con conpuerta para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1382, "codigo_producto" => "ECOM-1862", "nombre" => "Nido casa chica para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1381, "codigo_producto" => "ECOM-1861", "nombre" => "Nido cilindro para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1380, "codigo_producto" => "ECOM-1860", "nombre" => "Nido externo para jaula de pajaros", "id_categoria_erp" => 527, "codigo_categoria" => "CAT16-AVES-HAB-ACCESORIOS", "ruta_categoria" => "Aves / Habitat / Accesorios para jaulas", "motivo" => "Nido o accesorio de jaula para aves."),
    array("id_producto_erp" => 1364, "codigo_producto" => "ECOM-1844", "nombre" => "Alimentador de semillas farol para aves 6 estaciones", "id_categoria_erp" => 520, "codigo_categoria" => "CAT16-AVES-ALIM-COMEDEROS", "ruta_categoria" => "Aves / Alimentacion / Bebederos y comederos / Comederos", "motivo" => "Alimentador/comedor para aves."),
    array("id_producto_erp" => 1363, "codigo_producto" => "ECOM-1843", "nombre" => "Alimentador de semillas cilindro para aves 6 estaciones", "id_categoria_erp" => 520, "codigo_categoria" => "CAT16-AVES-ALIM-COMEDEROS", "ruta_categoria" => "Aves / Alimentacion / Bebederos y comederos / Comederos", "motivo" => "Alimentador/comedor para aves."),
    array("id_producto_erp" => 1362, "codigo_producto" => "ECOM-1842", "nombre" => "Alimentador de semillas farolito para aves 6 estaciones", "id_categoria_erp" => 520, "codigo_categoria" => "CAT16-AVES-ALIM-COMEDEROS", "ruta_categoria" => "Aves / Alimentacion / Bebederos y comederos / Comederos", "motivo" => "Alimentador/comedor para aves."),
    array("id_producto_erp" => 1361, "codigo_producto" => "PERA-C5040", "nombre" => "Pecera cubo 50 x 50 x 40 cm altura 100 litros 6 mm", "id_categoria_erp" => 458, "codigo_categoria" => "CAT16-ACUARIO-PECERAS-PECERAS", "ruta_categoria" => "Acuario y peces / Peceras, acuarios y muebles / Peceras", "motivo" => "Pecera como contenedor principal.")
  );

  public function ejecutar($execute, $token, $respaldo) {
    if (self::LOTE_CERRADO) {
      return $this->respuesta("info", "Lote 03 cerrado despues de aplicarse; genera un lote nuevo para mas productos.", array(
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
      return $this->respuesta("success", "Clasificacion asistida aplicada para altas de lote 03.", array(
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

$uat = new UatCatalogoClasificacionAsistidaAltasLote03Apply();
echo json_encode($uat->ejecutar($execute, $token, $respaldo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
