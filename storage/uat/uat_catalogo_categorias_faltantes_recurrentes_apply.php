<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-17
 * Proposito: preparar alta controlada de categorias faltantes recurrentes detectadas por clasificacion asistida.
 * Impacto: Catalogo ERP; escribe nuevas categorias maestras activas solo con autorizacion explicita.
 * Contrato: preview por defecto; execute requiere token y respaldo externo; no modifica productos ni relaciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasFaltantesRecurrentesApply extends CRUD {
  const TOKEN = "CATALOGO_CATEGORIAS_FALTANTES_RECURRENTES";

  private $categorias = array(
    array(
      "codigo" => "CAT16-REPTILES-GRAL-TERR-GENERALES",
      "nombre" => "Terrarios generales",
      "padre_codigo" => "CAT16-REPTILES-GRAL-TERRARIOS",
      "descripcion" => "Terrarios para reptiles generales cuando no corresponde clasificarlos por material."
    ),
    array(
      "codigo" => "CAT16-MAMIFEROS-VIVOS",
      "nombre" => "Animales vivos",
      "padre_codigo" => "CAT16-MAMIFEROS",
      "descripcion" => "Animales vivos de pequenos mamiferos cuando no se clasifica por especie en catalogo."
    ),
    array(
      "codigo" => "CAT16-ACUARIO-DECO-RAICES-TRONCOS",
      "nombre" => "Raices y troncos",
      "padre_codigo" => "CAT16-ACUARIO-DECO",
      "descripcion" => "Raices, troncos y maderas decorativas para acuario."
    ),
    array(
      "codigo" => "CAT16-REPTILES-GRAL-SUSTRATOS",
      "nombre" => "Sustratos",
      "padre_codigo" => "CAT16-REPTILES-GRAL",
      "descripcion" => "Sustratos para terrarios y reptiles generales."
    ),
    array(
      "codigo" => "CAT16-MAMIFEROS-HAB-GENERAL",
      "nombre" => "Habitat y jaulas generales",
      "padre_codigo" => "CAT16-MAMIFEROS",
      "descripcion" => "Habitat y jaulas para pequenos mamiferos cuando no se conoce o no aplica una especie especifica."
    )
  );

  public function ejecutar($execute, $token, $respaldo) {
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
      "definidas" => count($this->categorias),
      "a_crear" => 0,
      "existentes" => 0,
      "errores" => array(),
      "creadas" => 0
    );
    $preview = array();

    foreach ($this->categorias as $categoria) {
      $existente = $this->buscarCategoria($db, $categoria["codigo"]);
      $padre = $this->buscarCategoria($db, $categoria["padre_codigo"]);

      if ($existente) {
        $resumen["existentes"]++;
        $preview[] = array_merge($categoria, array(
          "accion" => "existente",
          "ruta" => $existente["ruta"],
          "nivel" => intval($existente["nivel"])
        ));
        continue;
      }

      if (!$padre) {
        $resumen["errores"][] = "No existe padre " . $categoria["padre_codigo"] . " para " . $categoria["codigo"];
        continue;
      }

      $ruta = trim($padre["ruta"]) . " / " . $categoria["nombre"];
      $nivel = intval($padre["nivel"]) + 1;
      $resumen["a_crear"]++;
      $preview[] = array_merge($categoria, array(
        "accion" => "crear",
        "id_categoria_padre" => intval($padre["id_categoria_erp"]),
        "ruta" => $ruta,
        "nivel" => $nivel
      ));
    }

    if (!$execute || !empty($resumen["errores"])) {
      return $this->respuesta(empty($resumen["errores"]) ? "preview" : "error", "Preview generado; no se aplicaron cambios.", array(
        "resumen" => $resumen,
        "categorias" => $preview
      ));
    }

    try {
      $db->beginTransaction();
      $insert = $db->prepare("INSERT INTO erp_catalogo_categorias
          (id_categoria_padre, codigo, nombre, descripcion, ruta, nivel, tipo_categoria, origen, permite_productos, estatus)
        VALUES
          (:padre, :codigo, :nombre, :descripcion, :ruta, :nivel, 'maestra', 'erp', 1, 'activa')");

      foreach ($preview as $item) {
        if ($item["accion"] !== "crear") {
          continue;
        }

        $insert->execute(array(
          ":padre" => intval($item["id_categoria_padre"]),
          ":codigo" => $item["codigo"],
          ":nombre" => $item["nombre"],
          ":descripcion" => $item["descripcion"],
          ":ruta" => $item["ruta"],
          ":nivel" => intval($item["nivel"])
        ));
        $resumen["creadas"]++;
      }

      $db->commit();
      return $this->respuesta("success", "Categorias faltantes recurrentes creadas.", array(
        "resumen" => $resumen,
        "categorias" => $preview
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      $resumen["errores"][] = $e->getMessage();
      return $this->respuesta("error", "Error al crear categorias faltantes. No se confirmaron cambios.", array(
        "resumen" => $resumen
      ));
    }
  }

  private function buscarCategoria($db, $codigo) {
    $stmt = $db->prepare("SELECT id_categoria_erp, codigo, nombre, ruta, nivel
      FROM erp_catalogo_categorias
      WHERE codigo=:codigo
      LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    return $categoria ? $categoria : null;
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

$uat = new UatCatalogoCategoriasFaltantesRecurrentesApply();
echo json_encode($uat->ejecutar($execute, $token, $respaldo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
