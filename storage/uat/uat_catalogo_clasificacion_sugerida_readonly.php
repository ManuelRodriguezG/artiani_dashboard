<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-15
 * Proposito: sugerir categorias principales para productos sin clasificar usando reglas trazables.
 * Impacto: Catalogo ERP; acelera reclasificacion sin escribir BD ni reemplazar decisiones del operador.
 * Contrato: solo lectura; devuelve sugerencias con confianza alta/media/baja y permite salida resumida por CLI.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoClasificacionSugeridaReadonly extends CRUD {
  private $categoriasPorCodigo = array();

  public function ejecutar($limite = 200) {
    $db = $this->getConexion();
    $this->cargarCategorias($db);

    $stmt = $db->prepare("SELECT p.id_producto_erp, p.codigo_producto, p.nombre, p.tipo_producto, p.estatus,
        COALESCE(m.nombre, '') marca
      FROM erp_catalogo_productos p
      LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
      WHERE p.estatus<>'fusionado'
        AND NOT EXISTS (
          SELECT 1
          FROM erp_catalogo_producto_categorias pc
          WHERE pc.id_producto_erp=p.id_producto_erp
            AND pc.es_principal=1
        )
      ORDER BY p.id_producto_erp DESC
      LIMIT :limite");
    $stmt->bindValue(":limite", max(1, min(500, intval($limite))), PDO::PARAM_INT);
    $stmt->execute();

    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sugerencias = array();
    $resumen = array("alta" => 0, "media" => 0, "baja" => 0, "sin_sugerencia" => 0);
    $faltantes = array();

    foreach ($productos as $producto) {
      $sugerencia = $this->sugerirProducto($producto);
      $sugerencias[] = $sugerencia;
      $resumen[$sugerencia["confianza"]]++;
      foreach ($sugerencia["categorias_faltantes"] as $faltante) {
        $faltantes[$faltante] = isset($faltantes[$faltante]) ? $faltantes[$faltante] + 1 : 1;
      }
    }

    arsort($faltantes);

    return array(
      "resumen" => array(
        "productos_analizados" => count($productos),
        "confianza" => $resumen,
        "categorias_asignables" => count($this->categoriasPorCodigo)
      ),
      "categorias_faltantes_detectadas" => $faltantes,
      "sugerencias" => $sugerencias
    );
  }

  private function cargarCategorias($db) {
    $categorias = $db->query("SELECT id_categoria_erp, codigo, ruta
      FROM erp_catalogo_categorias
      WHERE estatus='activa'
        AND tipo_categoria='maestra'
        AND permite_productos=1")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categorias as $categoria) {
      $this->categoriasPorCodigo[$categoria["codigo"]] = array(
        "id_categoria_erp" => intval($categoria["id_categoria_erp"]),
        "codigo" => $categoria["codigo"],
        "ruta" => $categoria["ruta"]
      );
    }
  }

  private function sugerirProducto($producto) {
    $texto = $this->normalizarTexto($producto["codigo_producto"] . " " . $producto["nombre"] . " " . $producto["marca"]);
    $faltantes = array();
    $coincidencias = array();

    $reglas = $this->reglas();
    foreach ($reglas as $regla) {
      if ($this->coincide($texto, $regla["incluye"], isset($regla["excluye"]) ? $regla["excluye"] : array())) {
        $coincidencias[] = $regla;
      }
    }

    if (empty($coincidencias)) {
      return $this->formatoSugerencia($producto, null, "sin_sugerencia", "Sin regla confiable para este nombre/SKU.", array());
    }

    usort($coincidencias, function ($a, $b) {
      return $this->pesoConfianza($b["confianza"]) - $this->pesoConfianza($a["confianza"]);
    });

    $regla = $coincidencias[0];
    if (!empty($regla["faltante"])) {
      $faltantes[] = $regla["faltante"];
    }

    $categoria = isset($this->categoriasPorCodigo[$regla["categoria"]]) ? $this->categoriasPorCodigo[$regla["categoria"]] : null;
    if (!$categoria) {
      $faltantes[] = "Categoria asignable no existe para codigo " . $regla["categoria"];
      return $this->formatoSugerencia($producto, null, "baja", $regla["motivo"], $faltantes);
    }

    return $this->formatoSugerencia($producto, $categoria, $regla["confianza"], $regla["motivo"], $faltantes);
  }

  private function reglas() {
    return array(
      array("incluye" => array("gabinete"), "categoria" => "CAT16-ACUARIO-PECERAS-BASES", "confianza" => "alta", "motivo" => "Gabinete/mueble para pecera o acuario."),
      array("incluye" => array("mueble"), "categoria" => "CAT16-ACUARIO-PECERAS-BASES", "confianza" => "alta", "motivo" => "Gabinete/mueble para pecera o acuario."),
      array("incluye" => array("pecera", "acuario"), "categoria" => "CAT16-ACUARIO-PECERAS-PECERAS", "confianza" => "alta", "motivo" => "Pecera/acuario como producto principal."),
      array("incluye" => array("pecera", "equipada"), "categoria" => "CAT16-ACUARIO-PECERAS-EQUIPADAS", "confianza" => "alta", "motivo" => "Pecera equipada."),
      array("incluye" => array("pecera", "equipo"), "categoria" => "CAT16-ACUARIO-PECERAS-EQUIPADAS", "confianza" => "alta", "motivo" => "Pecera con equipo incluido."),
      array("incluye" => array("pecera"), "excluye" => array("base", "gabinete", "mueble"), "categoria" => "CAT16-ACUARIO-PECERAS-PECERAS", "confianza" => "alta", "motivo" => "Pecera como contenedor principal."),
      array("incluye" => array("aqua", "pack"), "categoria" => "CAT16-ACUARIO-PECERAS-EQUIPADAS", "confianza" => "media", "motivo" => "Pack de acuario/pecera; revisar si incluye equipo."),
      array("incluye" => array("planta"), "categoria" => "CAT16-ACUARIO-DECO-PLANTAS-ART", "confianza" => "alta", "motivo" => "Planta artificial o decorativa para acuario."),
      array("incluye" => array("medusa"), "categoria" => "CAT16-ACUARIO-DECO-PECES", "confianza" => "alta", "motivo" => "Decoracion para acuario."),
      array("incluye" => array("medusas"), "categoria" => "CAT16-ACUARIO-DECO-PECES", "confianza" => "alta", "motivo" => "Decoracion para acuario."),
      array("incluye" => array("grava"), "categoria" => "CAT16-ACUARIO-DECO-SUSTRATOS", "confianza" => "alta", "motivo" => "Grava/sustrato para acuario."),
      array("incluye" => array("piedra", "mar"), "categoria" => "CAT16-ACUARIO-DECO-SUSTRATOS", "confianza" => "media", "motivo" => "Piedra decorativa; validar si es acuario o terrario."),
      array("incluye" => array("raiz"), "categoria" => "CAT16-ACUARIO-DECO-RAICES-TRONCOS", "confianza" => "alta", "motivo" => "Raiz/tronco decorativo para acuario."),
      array("incluye" => array("tronco"), "categoria" => "CAT16-ACUARIO-DECO-RAICES-TRONCOS", "confianza" => "alta", "motivo" => "Tronco decorativo para acuario."),
      array("incluye" => array("filtro"), "categoria" => "CAT16-ACUARIO-EQUIP-FILTRACION", "confianza" => "alta", "motivo" => "Filtro/equipo de filtracion."),
      array("incluye" => array("aereador"), "categoria" => "CAT16-ACUARIO-EQUIP-FILTRACION", "confianza" => "alta", "motivo" => "Oxigenacion/aereacion de acuario."),
      array("incluye" => array("rasqueton"), "categoria" => "CAT16-ACUARIO-REP-ACCESORIOS", "confianza" => "alta", "motivo" => "Accesorio de limpieza para vidrio/acuario."),
      array("incluye" => array("term"), "categoria" => "CAT16-ACUARIO-EQUIP-CALEFACCION", "confianza" => "media", "motivo" => "Termometro/termico; revisar si es medicion o calefaccion."),
      array("incluye" => array("base", "pecera"), "categoria" => "CAT16-ACUARIO-PECERAS-BASES", "confianza" => "alta", "motivo" => "Base para pecera/acuario."),
      array("incluye" => array("acuario"), "categoria" => "CAT16-ACUARIO-PECERAS-ACUARIOS", "confianza" => "alta", "motivo" => "Acuario como contenedor principal."),
      array("incluye" => array("terrario"), "categoria" => "CAT16-REPTILES-GRAL-TERR-GENERALES", "confianza" => "alta", "motivo" => "Terrario general para reptiles."),

      array("incluye" => array("cat", "toilet"), "categoria" => "CAT16-GATOS-SALUD-ARENEROS", "confianza" => "alta", "motivo" => "Arenero/sanitario para gato."),
      array("incluye" => array("arenero"), "categoria" => "CAT16-GATOS-SALUD-ARENEROS", "confianza" => "alta", "motivo" => "Arenero para gato."),
      array("incluye" => array("arena", "gato"), "categoria" => "CAT16-GATOS-SALUD-ARENAS", "confianza" => "alta", "motivo" => "Arena/control de olores para gato."),
      array("incluye" => array("premios", "gato"), "categoria" => "CAT16-GATOS-ALIM-PREMIOS", "confianza" => "alta", "motivo" => "Premio/snack para gato."),
      array("incluye" => array("premio", "gato"), "categoria" => "CAT16-GATOS-ALIM-PREMIOS", "confianza" => "alta", "motivo" => "Premio/snack para gato."),
      array("incluye" => array("mansion"), "categoria" => "CAT16-GATOS-HAB-CASAS", "confianza" => "media", "motivo" => "Casa/mansion para mascota; revisar especie si no dice gato."),
      array("incluye" => array("rascador"), "categoria" => "CAT16-GATOS-JUEGO-RASCADORES", "confianza" => "alta", "motivo" => "Rascador para gato."),

      array("incluye" => array("premios", "perros"), "categoria" => "CAT16-PERROS-ALIM-PREMIOS", "confianza" => "alta", "motivo" => "Premio/snack para perro."),
      array("incluye" => array("premio", "perros"), "categoria" => "CAT16-PERROS-ALIM-PREMIOS", "confianza" => "alta", "motivo" => "Premio/snack para perro."),
      array("incluye" => array("carda"), "categoria" => "CAT16-PERROS-SALUD-HIGIENE", "confianza" => "media", "motivo" => "Cepillo/carda de higiene; revisar si aplica tambien a gato."),
      array("incluye" => array("peine"), "categoria" => "CAT16-PERROS-SALUD-HIGIENE", "confianza" => "media", "motivo" => "Peine de higiene; revisar especie."),
      array("incluye" => array("recogedor", "heces"), "categoria" => "CAT16-PERROS-SALUD-HIGIENE", "confianza" => "alta", "motivo" => "Higiene y limpieza para perro."),
      array("incluye" => array("click"), "categoria" => "CAT16-PERROS-TRANS-ENTRENAMIENTO", "confianza" => "alta", "motivo" => "Clicker/entrenador."),
      array("incluye" => array("clicket"), "categoria" => "CAT16-PERROS-TRANS-ENTRENAMIENTO", "confianza" => "alta", "motivo" => "Clicker/entrenador."),
      array("incluye" => array("entrenador"), "categoria" => "CAT16-PERROS-TRANS-ENTRENAMIENTO", "confianza" => "media", "motivo" => "Producto de entrenamiento; revisar especie."),
      array("incluye" => array("jaladera"), "categoria" => "CAT16-PERROS-JUEGO-MORDEDERAS", "confianza" => "media", "motivo" => "Juguete jaladera/mordedera para perro."),
      array("incluye" => array("caucho", "argolla"), "categoria" => "CAT16-PERROS-JUEGO-MORDEDERAS", "confianza" => "media", "motivo" => "Mordedera/juguete de caucho para perro."),
      array("incluye" => array("cama"), "categoria" => "CAT16-PERROS-HAB-CAMAS", "confianza" => "media", "motivo" => "Cama elevada; revisar si se vendera multiespecie."),
      array("incluye" => array("corral"), "categoria" => "CAT16-PERROS-HAB-CORRALES", "confianza" => "media", "motivo" => "Corral/valla; revisar especie."),
      array("incluye" => array("transportadora"), "categoria" => "CAT16-PERROS-TRANS-TRANSPORTADORAS", "confianza" => "media", "motivo" => "Transportadora; revisar especie o uso multiespecie."),
      array("incluye" => array("transp"), "categoria" => "CAT16-PERROS-TRANS-TRANSPORTADORAS", "confianza" => "media", "motivo" => "Transportadora abreviada; revisar especie."),

      array("incluye" => array("chinchilla"), "categoria" => "CAT16-MAMIFEROS-VIVOS", "confianza" => "media", "motivo" => "Animal vivo de pequeno mamifero; revisar si el producto es alimento o ejemplar vivo."),
      array("incluye" => array("erizo"), "categoria" => "CAT16-MAMIFEROS-VIVOS", "confianza" => "media", "motivo" => "Animal vivo de pequeno mamifero; revisar si el producto es alimento o ejemplar vivo."),
      array("incluye" => array("cuyo"), "categoria" => "CAT16-MAMIFEROS-VIVOS", "confianza" => "media", "motivo" => "Animal vivo de pequeno mamifero; revisar si el producto es alimento o ejemplar vivo."),
      array("incluye" => array("jaula", "hamster"), "categoria" => "CAT16-MAMIFEROS-HAMSTER-HAB", "confianza" => "alta", "motivo" => "Jaula/habitat para hamster."),
      array("incluye" => array("jaula", "mamifero"), "categoria" => "CAT16-MAMIFEROS-HAB-GENERAL", "confianza" => "media", "motivo" => "Jaula para pequeno mamifero sin especie definida."),
      array("incluye" => array("alimento", "hamster"), "categoria" => "CAT16-MAMIFEROS-HAMSTER-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para hamster."),
      array("incluye" => array("alfalfa"), "categoria" => "CAT16-MAMIFEROS-CONEJO-ALIM-ALIMENTOS", "confianza" => "media", "motivo" => "Alimento/fibra para pequenos mamiferos; revisar especie destino."),
      array("incluye" => array("nutricubos"), "categoria" => "CAT16-MAMIFEROS-CONEJO-ALIM-ALIMENTOS", "confianza" => "media", "motivo" => "Alimento para pequenos mamiferos; revisar especie destino."),

      array("incluye" => array("alimento", "aves"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves."),
      array("incluye" => array("alimento", "pajaros"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/pajaros."),
      array("incluye" => array("alimento", "parrots"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/parrots."),
      array("incluye" => array("alimento", "parakeets"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/parakeets."),
      array("incluye" => array("alimento", "canarios"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/canarios."),
      array("incluye" => array("alimento", "periquitos"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/periquitos."),
      array("incluye" => array("alimento", "ninfas"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/ninfas."),
      array("incluye" => array("alimento", "agapornis"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/agapornis."),
      array("incluye" => array("alimento", "loro"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/loro."),
      array("incluye" => array("alimento", "guacamaya"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Alimento para aves/guacamaya."),
      array("incluye" => array("papilla", "pajaros"), "categoria" => "CAT16-AVES-ALIM-ALIMENTOS", "confianza" => "alta", "motivo" => "Papilla/alimento para aves jovenes."),
      array("incluye" => array("alimentador", "aves"), "categoria" => "CAT16-AVES-ALIM-COMEDEROS", "confianza" => "alta", "motivo" => "Alimentador/comedor para aves."),
      array("incluye" => array("comedero", "aves"), "categoria" => "CAT16-AVES-ALIM-COMEDEROS", "confianza" => "alta", "motivo" => "Comedero para aves."),
      array("incluye" => array("nido", "pajaros"), "categoria" => "CAT16-AVES-HAB-ACCESORIOS", "confianza" => "alta", "motivo" => "Nido o accesorio de jaula para aves."),
      array("incluye" => array("grillo"), "categoria" => "CAT16-REPTILES-GRAL-VIVOS", "confianza" => "media", "motivo" => "Alimento vivo para reptiles, aves o pequenos mamiferos; revisar canal principal."),
      array("incluye" => array("zophoba"), "categoria" => "CAT16-REPTILES-GRAL-VIVOS", "confianza" => "media", "motivo" => "Alimento vivo para reptiles, aves o pequenos mamiferos; revisar canal principal."),
      array("incluye" => array("artemia"), "categoria" => "CAT16-ACUARIO-ALIM-PECES", "confianza" => "media", "motivo" => "Artemia como alimento para peces; revisar si vivo/deshidratado."),
      array("incluye" => array("peat", "moss"), "categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "confianza" => "alta", "motivo" => "Sustrato para terrario/reptiles."),
      array("incluye" => array("fibra", "coco"), "categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "confianza" => "alta", "motivo" => "Sustrato de coco para terrario/reptiles."),
      array("incluye" => array("chip", "coco"), "categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "confianza" => "alta", "motivo" => "Sustrato de coco para terrario/reptiles."),
      array("incluye" => array("corteza"), "categoria" => "CAT16-REPTILES-GRAL-SUSTRATOS", "confianza" => "alta", "motivo" => "Sustrato o corteza para terrario/reptiles.")
    );
  }

  private function coincide($texto, $incluye, $excluye) {
    foreach ($incluye as $palabra) {
      if (!$this->contieneToken($texto, $palabra)) {
        return false;
      }
    }
    foreach ($excluye as $palabra) {
      if ($this->contieneToken($texto, $palabra)) {
        return false;
      }
    }
    return true;
  }

  private function contieneToken($texto, $palabra) {
    $token = trim($this->normalizarTexto($palabra));
    if ($token === "") {
      return false;
    }
    return strpos(" " . $texto . " ", " " . $token . " ") !== false;
  }

  private function formatoSugerencia($producto, $categoria, $confianza, $motivo, $faltantes) {
    return array(
      "id_producto_erp" => intval($producto["id_producto_erp"]),
      "codigo_producto" => $producto["codigo_producto"],
      "nombre" => $producto["nombre"],
      "marca" => $producto["marca"],
      "confianza" => $confianza,
      "id_categoria_erp" => $categoria ? $categoria["id_categoria_erp"] : null,
      "codigo_categoria" => $categoria ? $categoria["codigo"] : null,
      "ruta_categoria" => $categoria ? $categoria["ruta"] : null,
      "motivo" => $motivo,
      "categorias_faltantes" => array_values(array_unique($faltantes))
    );
  }

  private function pesoConfianza($confianza) {
    if ($confianza === "alta") {
      return 3;
    }
    if ($confianza === "media") {
      return 2;
    }
    if ($confianza === "baja") {
      return 1;
    }
    return 0;
  }

  private function normalizarTexto($texto) {
    $texto = strtolower((string)$texto);
    $texto = str_replace(
      array("á", "é", "í", "ó", "ú", "ü", "ñ"),
      array("a", "e", "i", "o", "u", "u", "n"),
      $texto
    );
    return preg_replace("/[^a-z0-9]+/", " ", $texto);
  }
}

if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
  $limite = 200;
  $soloResumen = in_array("--solo-resumen", $argv, true);
  foreach ($argv as $arg) {
    if (strpos($arg, "--limite=") === 0) {
      $limite = intval(substr($arg, 9));
    }
  }

  $uat = new UatCatalogoClasificacionSugeridaReadonly();
  $resultado = $uat->ejecutar($limite);
  if ($soloResumen) {
    $resultado = array(
      "resumen" => $resultado["resumen"],
      "categorias_faltantes_detectadas" => $resultado["categorias_faltantes_detectadas"],
      "altas" => array_values(array_filter($resultado["sugerencias"], function ($item) {
        return $item["confianza"] === "alta";
      }))
    );
  }
  echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
