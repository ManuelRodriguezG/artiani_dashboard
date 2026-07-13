<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-12
 * Proposito: previsualizar o aplicar reparacion deterministica de texto danado en categorias.
 * Impacto: Catalogo ERP; mejora legibilidad del arbol maestro sin tocar productos ni relaciones.
 * Contrato: por defecto es preview read-only; solo actualiza con --execute, respaldo externo y token autorizado.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasTextoRepararApply extends CRUD {
  const TOKEN = "CATALOGO_CATEGORIAS_TEXTO_REPARAR";

  public function ejecutar($argv) {
    $opciones = $this->opciones($argv);
    $execute = !empty($opciones["execute"]);
    $token = isset($opciones["token"]) ? trim((string)$opciones["token"]) : "";
    $respaldo = isset($opciones["respaldo"]) ? trim((string)$opciones["respaldo"]) : "";
    $limite = isset($opciones["limit"]) ? intval($opciones["limit"]) : 60;
    $limite = max(1, min(200, $limite));
    $autorizado = $execute && $token === self::TOKEN && $this->respaldoValido($respaldo);

    $db = $this->getConexion();
    $categorias = $db->query("SELECT id_categoria_erp, codigo, nombre, descripcion, ruta
      FROM erp_catalogo_categorias
      ORDER BY id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC);

    $cambios = $this->detectarCambios($categorias);
    if (!$autorizado) {
      return array(
        "ok" => !$execute,
        "modo" => $execute ? "bloqueado" : "preview",
        "requiere" => array(
          "execute" => true,
          "token" => self::TOKEN,
          "respaldo_externo" => "ruta o referencia real fuera del proyecto"
        ),
        "motivo_bloqueo" => $execute ? $this->motivoBloqueo($token, $respaldo) : "",
        "cambios_detectados" => count($cambios),
        "muestra" => array_slice($cambios, 0, $limite),
        "nota" => "No se modifico BD."
      );
    }

    $stmt = $db->prepare("UPDATE erp_catalogo_categorias
      SET nombre=:nombre, descripcion=:descripcion, ruta=:ruta, fecha_actualizacion=CURRENT_TIMESTAMP
      WHERE id_categoria_erp=:id");

    $db->beginTransaction();
    try {
      foreach ($cambios as $cambio) {
        $stmt->execute(array(
          ":nombre" => $cambio["nombre_despues"],
          ":descripcion" => $cambio["descripcion_despues"],
          ":ruta" => $cambio["ruta_despues"],
          ":id" => intval($cambio["id_categoria_erp"])
        ));
      }
      $db->commit();
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }

    return array(
      "ok" => true,
      "modo" => "aplicado",
      "actualizadas" => count($cambios),
      "respaldo" => $respaldo,
      "muestra" => array_slice($cambios, 0, $limite),
      "nota" => "No se modificaron productos, SKUs ni relaciones de categorias."
    );
  }

  private function detectarCambios($categorias) {
    $cambios = array();
    foreach ($categorias as $categoria) {
      $nombre = $this->repararCampo($categoria["nombre"]);
      $descripcion = $this->repararCampo($categoria["descripcion"]);
      $ruta = $this->repararCampo($categoria["ruta"]);
      if ($nombre === $categoria["nombre"] && $descripcion === $categoria["descripcion"] && $ruta === $categoria["ruta"]) {
        continue;
      }
      $cambios[] = array(
        "id_categoria_erp" => intval($categoria["id_categoria_erp"]),
        "codigo" => $categoria["codigo"],
        "nombre_antes" => $categoria["nombre"],
        "nombre_despues" => $nombre,
        "descripcion_despues" => $descripcion,
        "ruta_antes" => $categoria["ruta"],
        "ruta_despues" => $ruta
      );
    }
    return $cambios;
  }

  private function repararTexto($texto) {
    return strtr((string)$texto, $this->mapaTextoDanado());
  }

  private function repararCampo($valor) {
    if ($valor === null) {
      return null;
    }
    return $this->repararTexto($valor);
  }

  private function mapaTextoDanado() {
    return array(
      "\xE2\x94\x9C\xC2\xA1" => "\xC3\xAD",
      "\xE2\x94\x9C\xC3\xAD" => "\xC3\xA1",
      "\xE2\x94\x9C\xE2\x94\x82" => "\xC3\xB3",
      "\xE2\x94\x9C\xC2\xAE" => "\xC3\xA9",
      "\xE2\x94\x9C\xE2\x95\x91" => "\xC3\xBA",
      "\xE2\x94\x9C\xE2\x96\x92" => "\xC3\xB1",
      "\xE2\x94\x9C\xC3\xBC" => "\xC3\x81",
      "\xE2\x94\x9C\xC3\xAC" => "\xC3\x8D",
      "\xE2\x94\x9C\xC3\xB4" => "\xC3\x93",
      "\xE2\x94\x9C\xC3\xAB" => "\xC3\x89",
      "\xE2\x94\x9C\xC3\x9C" => "\xC3\x9A",
      "\xE2\x94\x9C\xC3\xA6" => "\xC3\x91",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xAD" => "\xC3\xA1",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC2\xA1" => "\xC3\xAD",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xE2\x94\x82" => "\xC3\xB3",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC2\xAE" => "\xC3\xA9",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xE2\x95\x91" => "\xC3\xBA",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xE2\x96\x92" => "\xC3\xB1",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xBC" => "\xC3\x81",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xAC" => "\xC3\x8D",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xB4" => "\xC3\x93",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xAB" => "\xC3\x89",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\x9C" => "\xC3\x9A",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xA6" => "\xC3\x91"
    );
  }

  private function opciones($argv) {
    $opciones = array();
    foreach ($argv as $arg) {
      if ($arg === "--execute") {
        $opciones["execute"] = true;
        continue;
      }
      if (strpos($arg, "--") === 0 && strpos($arg, "=") !== false) {
        list($key, $value) = explode("=", substr($arg, 2), 2);
        $opciones[$key] = $value;
      }
    }
    return $opciones;
  }

  private function respaldoValido($respaldo) {
    $respaldo = trim((string)$respaldo);
    if ($respaldo === "" || stripos($respaldo, "ruta real") !== false || stripos($respaldo, "placeholder") !== false) {
      return false;
    }
    $normalizado = str_replace("/", "\\", $respaldo);
    return stripos($normalizado, "\\panel_de_control\\") === false && stripos($normalizado, "\\panel\\") === false;
  }

  private function motivoBloqueo($token, $respaldo) {
    $motivos = array();
    if ($token !== self::TOKEN) {
      $motivos[] = "token invalido";
    }
    if (!$this->respaldoValido($respaldo)) {
      $motivos[] = "respaldo externo invalido";
    }
    return implode("; ", $motivos);
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasTextoRepararApply())->ejecutar($argv), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
