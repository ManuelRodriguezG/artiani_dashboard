<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: audita categorias maestras y heredadas sin modificar datos.
 * Impacto: Catalogo ERP; permite cerrar el CRUD/calidad de categorias sin resolver productos.
 * Contrato: solo lectura; devuelve conteos, riesgos y propuestas de correccion de texto.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasMaestroReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $categorias = $db->query("SELECT c.id_categoria_erp, c.id_categoria_padre, c.codigo, c.nombre, c.descripcion,
        c.ruta, c.nivel, c.tipo_categoria, c.origen, c.permite_productos, c.estatus,
        (SELECT COUNT(*) FROM erp_catalogo_categorias h WHERE h.id_categoria_padre=c.id_categoria_erp) AS total_hijas,
        (SELECT COUNT(*) FROM erp_catalogo_producto_categorias pc WHERE pc.id_categoria_erp=c.id_categoria_erp) AS total_productos
      FROM erp_catalogo_categorias c
      ORDER BY c.id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC);

    $porTipo = array();
    $porEstatus = array();
    $raices = 0;
    $hojasProducto = 0;
    $estructurales = 0;
    $orphanPadres = array();
    $textoDanado = array();
    $duplicadosCodigo = array();
    $duplicadosNombrePadre = array();
    $rutasInconsistentes = array();
    $ids = array();
    $porCodigo = array();
    $porNombrePadre = array();

    foreach ($categorias as $categoria) {
      $ids[intval($categoria["id_categoria_erp"])] = true;
      $porTipo[$categoria["tipo_categoria"]] = isset($porTipo[$categoria["tipo_categoria"]]) ? $porTipo[$categoria["tipo_categoria"]] + 1 : 1;
      $porEstatus[$categoria["estatus"]] = isset($porEstatus[$categoria["estatus"]]) ? $porEstatus[$categoria["estatus"]] + 1 : 1;
      if (empty($categoria["id_categoria_padre"])) {
        $raices++;
      }
      if (intval($categoria["permite_productos"]) === 1) {
        $hojasProducto++;
      } else {
        $estructurales++;
      }
      $codigo = strtoupper(trim((string)$categoria["codigo"]));
      if ($codigo !== "") {
        $porCodigo[$codigo][] = $categoria;
      }
      $claveNombrePadre = intval($categoria["id_categoria_padre"]) . "|" . mb_strtolower(trim((string)$categoria["nombre"]), "UTF-8");
      $porNombrePadre[$claveNombrePadre][] = $categoria;
      if ($this->textoPareceDanado($categoria["nombre"]) || $this->textoPareceDanado($categoria["ruta"]) || $this->textoPareceDanado($categoria["descripcion"])) {
        $textoDanado[] = array(
          "id_categoria_erp" => intval($categoria["id_categoria_erp"]),
          "codigo" => $categoria["codigo"],
          "nombre" => $categoria["nombre"],
          "hex_nombre" => strtoupper(bin2hex((string)$categoria["nombre"])),
          "nombre_propuesto" => $this->repararMojibake($categoria["nombre"]),
          "ruta" => $categoria["ruta"],
          "hex_ruta" => strtoupper(bin2hex((string)$categoria["ruta"])),
          "ruta_propuesta" => $this->repararMojibake($categoria["ruta"]),
          "tipo_categoria" => $categoria["tipo_categoria"],
          "origen" => $categoria["origen"]
        );
      }
    }

    foreach ($categorias as $categoria) {
      $padre = intval($categoria["id_categoria_padre"]);
      if ($padre > 0 && !isset($ids[$padre])) {
        $orphanPadres[] = $this->resumenCategoria($categoria);
      }
      $rutaCalculada = $this->rutaEsperada($categorias, $categoria);
      if ($rutaCalculada !== "" && trim((string)$categoria["ruta"]) !== $rutaCalculada) {
        $rutasInconsistentes[] = $this->resumenCategoria($categoria) + array("ruta_esperada" => $rutaCalculada);
      }
    }

    foreach ($porCodigo as $codigo => $items) {
      if (count($items) > 1) {
        $duplicadosCodigo[$codigo] = array_map(array($this, "resumenCategoria"), $items);
      }
    }
    foreach ($porNombrePadre as $clave => $items) {
      if (count($items) > 1) {
        $duplicadosNombrePadre[$clave] = array_map(array($this, "resumenCategoria"), $items);
      }
    }

    return array(
      "resumen" => array(
        "total_categorias" => count($categorias),
        "por_tipo" => $porTipo,
        "por_estatus" => $porEstatus,
        "raices" => $raices,
        "permiten_productos" => $hojasProducto,
        "estructurales" => $estructurales,
        "texto_danado" => count($textoDanado),
        "padres_inexistentes" => count($orphanPadres),
        "codigos_duplicados" => count($duplicadosCodigo),
        "nombres_duplicados_mismo_padre" => count($duplicadosNombrePadre),
        "rutas_inconsistentes" => count($rutasInconsistentes)
      ),
      "texto_danado_muestra" => array_slice($textoDanado, 0, 40),
      "padres_inexistentes" => array_slice($orphanPadres, 0, 40),
      "codigos_duplicados" => $duplicadosCodigo,
      "nombres_duplicados_mismo_padre" => $duplicadosNombrePadre,
      "rutas_inconsistentes_muestra" => array_slice($rutasInconsistentes, 0, 40),
      "nota" => "UAT solo lectura; no modifica categorias ni relaciones de productos."
    );
  }

  private function resumenCategoria($categoria) {
    return array(
      "id_categoria_erp" => intval($categoria["id_categoria_erp"]),
      "id_categoria_padre" => intval($categoria["id_categoria_padre"]),
      "codigo" => $categoria["codigo"],
      "nombre" => $categoria["nombre"],
      "ruta" => $categoria["ruta"],
      "tipo_categoria" => $categoria["tipo_categoria"],
      "origen" => $categoria["origen"],
      "estatus" => $categoria["estatus"]
    );
  }

  private function textoPareceDanado($texto) {
    $texto = (string)$texto;
    return $texto !== "" && preg_match('/(Ã|Â|�|├|┬|â€™|â€œ|â€)/u', $texto) === 1;
  }

  private function repararMojibake($texto) {
    return strtr((string)$texto, $this->mapaTextoDanado());
  }

  private function mapaTextoDanado() {
    return array(
      "├â┬í" => "á",
      "├â┬¡" => "í",
      "├â┬│" => "ó",
      "├â┬®" => "é",
      "├â┬║" => "ú",
      "├â┬▒" => "ñ",
      "├â┬ü" => "Á",
      "├â┬ì" => "Í",
      "├â┬ô" => "Ó",
      "├â┬ë" => "É",
      "├â┬Ü" => "Ú",
      "├â┬æ" => "Ñ"
    );
  }

  private function rutaEsperada($categorias, $categoria) {
    $nombres = array(trim((string)$categoria["nombre"]));
    $visitados = array(intval($categoria["id_categoria_erp"]) => true);
    $padre = intval($categoria["id_categoria_padre"]);
    while ($padre > 0) {
      $padreCategoria = null;
      foreach ($categorias as $item) {
        if (intval($item["id_categoria_erp"]) === $padre) {
          $padreCategoria = $item;
          break;
        }
      }
      if (!$padreCategoria || isset($visitados[$padre])) {
        return "";
      }
      array_unshift($nombres, trim((string)$padreCategoria["nombre"]));
      $visitados[$padre] = true;
      $padre = intval($padreCategoria["id_categoria_padre"]);
    }
    return implode(" / ", array_filter($nombres, function ($nombre) { return $nombre !== ""; }));
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasMaestroReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
