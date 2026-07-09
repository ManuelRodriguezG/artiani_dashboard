<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: audita marcas, unidades y atributos sin modificar datos.
 * Impacto: Catalogo ERP; permite cerrar catalogos auxiliares sin tocar productos.
 * Contrato: solo lectura; no ejecuta DDL ni actualiza relaciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoMaestrosAuxiliaresReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    return array(
      "marcas" => $this->auditarMarcas($db),
      "unidades" => $this->auditarUnidades($db),
      "atributos" => $this->auditarAtributos($db),
      "nota" => "UAT solo lectura; no modifica productos, SKUs ni catalogos auxiliares."
    );
  }

  private function auditarMarcas($db) {
    $items = $db->query("SELECT m.id_marca_erp, m.codigo, m.nombre, m.descripcion, m.estatus,
        (SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.id_marca_erp=m.id_marca_erp AND p.estatus<>'fusionado') AS total_productos
      FROM erp_catalogo_marcas m
      ORDER BY m.nombre")->fetchAll(PDO::FETCH_ASSOC);
    return $this->resumenComun($items, "id_marca_erp", array("codigo", "nombre", "descripcion"), "estatus") + array(
      "sin_productos" => $this->contar($items, function ($item) { return intval($item["total_productos"]) === 0; }),
      "con_productos" => $this->contar($items, function ($item) { return intval($item["total_productos"]) > 0; }),
      "top_uso" => array_slice($this->ordenarPorNumero($items, "total_productos"), 0, 10)
    );
  }

  private function auditarUnidades($db) {
    $items = $db->query("SELECT u.id_unidad, u.codigo, u.nombre, u.abreviatura, u.tipo_magnitud,
        u.decimales_permitidos, u.clave_sat, u.estatus,
        (SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.id_unidad_base=u.id_unidad AND s.estatus<>'fusionado') AS uso_skus,
        (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_unidad_compra=u.id_unidad AND sp.estatus='activo') AS uso_proveedores
      FROM erp_catalogo_unidades u
      ORDER BY u.nombre")->fetchAll(PDO::FETCH_ASSOC);
    return $this->resumenComun($items, "id_unidad", array("codigo", "nombre", "abreviatura", "clave_sat"), "estatus") + array(
      "sin_abreviatura" => $this->contar($items, function ($item) { return trim((string)$item["abreviatura"]) === ""; }),
      "sin_clave_sat" => $this->contar($items, function ($item) { return trim((string)$item["clave_sat"]) === ""; }),
      "permiten_decimales" => $this->contar($items, function ($item) { return intval($item["decimales_permitidos"]) === 1; }),
      "por_magnitud" => $this->agrupar($items, "tipo_magnitud"),
      "top_uso_skus" => array_slice($this->ordenarPorNumero($items, "uso_skus"), 0, 10)
    );
  }

  private function auditarAtributos($db) {
    $items = $db->query("SELECT a.id_atributo_erp, a.codigo, a.nombre, a.tipo_dato, a.unidad,
        a.configuracion_json, a.es_variante, a.estatus,
        (SELECT COUNT(*) FROM erp_catalogo_sku_atributos sa WHERE sa.id_atributo_erp=a.id_atributo_erp) AS uso_skus
      FROM erp_catalogo_atributos a
      ORDER BY a.nombre")->fetchAll(PDO::FETCH_ASSOC);
    $listaSinOpciones = array();
    foreach ($items as $item) {
      if ($item["tipo_dato"] !== "lista") {
        continue;
      }
      $configuracion = json_decode((string)$item["configuracion_json"], true);
      $opciones = is_array($configuracion) && isset($configuracion["opciones"]) && is_array($configuracion["opciones"]) ? $configuracion["opciones"] : array();
      if (count($opciones) === 0) {
        $listaSinOpciones[] = $this->recortar($item);
      }
    }
    return $this->resumenComun($items, "id_atributo_erp", array("codigo", "nombre", "unidad", "configuracion_json"), "estatus") + array(
      "por_tipo_dato" => $this->agrupar($items, "tipo_dato"),
      "variantes" => $this->contar($items, function ($item) { return intval($item["es_variante"]) === 1; }),
      "listas_sin_opciones" => $listaSinOpciones,
      "sin_uso" => $this->contar($items, function ($item) { return intval($item["uso_skus"]) === 0; }),
      "top_uso_skus" => array_slice($this->ordenarPorNumero($items, "uso_skus"), 0, 10)
    );
  }

  private function resumenComun($items, $idKey, $textKeys, $estatusKey) {
    return array(
      "total" => count($items),
      "por_estatus" => $this->agrupar($items, $estatusKey),
      "texto_danado" => $this->filtrarTextoDanado($items, $textKeys),
      "codigos_duplicados" => $this->duplicados($items, "codigo", $idKey),
      "nombres_duplicados" => $this->duplicados($items, "nombre", $idKey),
      "nombres_duplicados_activos" => $this->duplicadosActivos($items, "nombre", $idKey, $estatusKey)
    );
  }

  private function filtrarTextoDanado($items, $keys) {
    $danados = array();
    foreach ($items as $item) {
      $texto = "";
      foreach ($keys as $key) {
        $texto .= " " . (isset($item[$key]) ? $item[$key] : "");
      }
      if (preg_match('/(Ã|Â|�|├|┬|â€™|â€œ|â€)/u', $texto) === 1) {
        $danados[] = $this->recortar($item);
      }
    }
    return $danados;
  }

  private function duplicados($items, $key, $idKey) {
    $grupos = array();
    foreach ($items as $item) {
      $valor = mb_strtolower(trim((string)$item[$key]), "UTF-8");
      if ($valor === "") {
        continue;
      }
      $grupos[$valor][] = array("id" => intval($item[$idKey]), "codigo" => $item["codigo"], "nombre" => $item["nombre"]);
    }
    return array_filter($grupos, function ($grupo) { return count($grupo) > 1; });
  }

  private function duplicadosActivos($items, $key, $idKey, $estatusKey) {
    return $this->duplicados(array_filter($items, function ($item) use ($estatusKey) {
      return in_array((string)$item[$estatusKey], array("activa", "activo"), true);
    }), $key, $idKey);
  }

  private function agrupar($items, $key) {
    $resumen = array();
    foreach ($items as $item) {
      $valor = trim((string)(isset($item[$key]) ? $item[$key] : ""));
      $valor = $valor === "" ? "(vacio)" : $valor;
      $resumen[$valor] = isset($resumen[$valor]) ? $resumen[$valor] + 1 : 1;
    }
    ksort($resumen);
    return $resumen;
  }

  private function contar($items, $callback) {
    $total = 0;
    foreach ($items as $item) {
      if ($callback($item)) {
        $total++;
      }
    }
    return $total;
  }

  private function ordenarPorNumero($items, $key) {
    usort($items, function ($a, $b) use ($key) {
      return intval($b[$key]) <=> intval($a[$key]);
    });
    return array_map(array($this, "recortar"), $items);
  }

  private function recortar($item) {
    $salida = $item;
    unset($salida["descripcion"], $salida["configuracion_json"]);
    return $salida;
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoMaestrosAuxiliaresReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
