<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-14
 * Proposito: preparar el arbol operativo 1-6 de categorias para reclasificar Catalogo ERP.
 * Impacto: Catalogo ERP; crea/actualiza categorias maestras sin asignar productos.
 * Contrato: preview por defecto; aplica solo con --execute, token y respaldo externo existente.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasArbol16Apply extends CRUD {
  private $tokenEsperado = "CATALOGO_CATEGORIAS_ARBOL_1_6_APLICAR";

  public function ejecutar($argv) {
    $args = $this->parseArgs($argv);
    $execute = isset($args["execute"]);
    $token = isset($args["token"]) ? (string)$args["token"] : "";
    $respaldo = isset($args["respaldo"]) ? (string)$args["respaldo"] : "";

    $db = $this->getConexion();
    if (!$db) {
      return array("error" => true, "mensaje" => "Sin conexion a BD.");
    }

    $categorias = $this->aplanarArbol($this->arbol());
    $preview = $this->previsualizar($db, $categorias);
    $bloqueo = $this->bloqueo($execute, $token, $respaldo);

    if (!$execute || $bloqueo !== "") {
      return array(
        "error" => false,
        "modo" => "preview",
        "requiere" => array(
          "execute" => true,
          "token" => $this->tokenEsperado,
          "respaldo_externo_existente" => "ruta real fuera del proyecto"
        ),
        "motivo_bloqueo" => $bloqueo,
        "resumen" => $preview["resumen"],
        "muestra_crear" => array_slice($preview["crear"], 0, 40),
        "muestra_actualizar" => array_slice($preview["actualizar"], 0, 40),
        "nota" => "No se modifico BD."
      );
    }

    $db->beginTransaction();
    try {
      $stmt = $db->prepare("INSERT INTO erp_catalogo_categorias
        (id_categoria_padre, codigo, nombre, descripcion, ruta, nivel, tipo_categoria, origen, permite_productos, estatus)
        VALUES (:padre, :codigo, :nombre, :descripcion, :ruta, :nivel, 'maestra', 'erp', :permite, 'activa')
        ON DUPLICATE KEY UPDATE id_categoria_erp=LAST_INSERT_ID(id_categoria_erp),
          id_categoria_padre=VALUES(id_categoria_padre), nombre=VALUES(nombre), descripcion=VALUES(descripcion),
          ruta=VALUES(ruta), nivel=VALUES(nivel), tipo_categoria='maestra', origen='erp',
          permite_productos=VALUES(permite_productos), estatus='activa', fecha_actualizacion=CURRENT_TIMESTAMP");

      $ids = array();
      $creadas = 0;
      $actualizadas = 0;
      foreach ($categorias as $categoria) {
        $padreCodigo = $categoria["padre_codigo"];
        $padre = $padreCodigo !== "" && isset($ids[$padreCodigo]) ? $ids[$padreCodigo] : null;
        $existeAntes = $this->categoriaExistePorCodigo($db, $categoria["codigo"]);
        $stmt->execute(array(
          ":padre" => $padre,
          ":codigo" => $categoria["codigo"],
          ":nombre" => $categoria["nombre"],
          ":descripcion" => $categoria["descripcion"],
          ":ruta" => $categoria["ruta"],
          ":nivel" => $categoria["nivel"],
          ":permite" => $categoria["permite_productos"]
        ));
        $ids[$categoria["codigo"]] = intval($db->lastInsertId());
        if ($existeAntes) {
          $actualizadas++;
        } else {
          $creadas++;
        }
      }

      $db->commit();
      return array(
        "error" => false,
        "modo" => "execute",
        "mensaje" => "Arbol operativo 1-6 preparado.",
        "respaldo_usado" => $respaldo,
        "categorias_definidas" => count($categorias),
        "categorias_creadas" => $creadas,
        "categorias_actualizadas" => $actualizadas
      );
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return array(
        "error" => true,
        "modo" => "execute",
        "mensaje" => "No se pudo preparar el arbol operativo 1-6.",
        "depurar" => $e->getMessage()
      );
    }
  }

  private function previsualizar($db, $categorias) {
    $crear = array();
    $actualizar = array();
    foreach ($categorias as $categoria) {
      $actual = $this->consultarCategoriaPorCodigo($db, $categoria["codigo"]);
      if (!$actual) {
        $crear[] = $categoria;
        continue;
      }
      $diferencias = array();
      foreach (array("nombre", "ruta", "nivel", "permite_productos") as $campo) {
        if ((string)$actual[$campo] !== (string)$categoria[$campo]) {
          $diferencias[$campo] = array("actual" => $actual[$campo], "nuevo" => $categoria[$campo]);
        }
      }
      if (!empty($diferencias)) {
        $categoria["id_categoria_erp"] = intval($actual["id_categoria_erp"]);
        $categoria["diferencias"] = $diferencias;
        $actualizar[] = $categoria;
      }
    }
    return array(
      "resumen" => array(
        "categorias_definidas" => count($categorias),
        "categorias_a_crear" => count($crear),
        "categorias_a_actualizar" => count($actualizar)
      ),
      "crear" => $crear,
      "actualizar" => $actualizar
    );
  }

  private function consultarCategoriaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT id_categoria_erp, codigo, nombre, ruta, nivel, permite_productos
      FROM erp_catalogo_categorias
      WHERE codigo=:codigo
      LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  private function categoriaExistePorCodigo($db, $codigo) {
    return $this->consultarCategoriaPorCodigo($db, $codigo) !== null;
  }

  private function aplanarArbol($arbol) {
    $items = array();
    foreach ($arbol as $raiz) {
      $this->aplanarNodo($raiz, "", "", 0, $items);
    }
    return $items;
  }

  private function aplanarNodo($nodo, $padreCodigo, $rutaPadre, $nivel, &$items) {
    $ruta = $rutaPadre === "" ? $nodo["nombre"] : $rutaPadre . " / " . $nodo["nombre"];
    $hijas = isset($nodo["hijas"]) ? $nodo["hijas"] : array();
    $items[] = array(
      "codigo" => $nodo["codigo"],
      "padre_codigo" => $padreCodigo,
      "nombre" => $nodo["nombre"],
      "descripcion" => $nivel === 0 ? "Raiz operativa del arbol 1-6 de Catalogo ERP" : "Categoria operativa del arbol 1-6 de Catalogo ERP",
      "ruta" => $ruta,
      "nivel" => $nivel,
      "permite_productos" => empty($hijas) ? 1 : 0
    );
    foreach ($hijas as $hija) {
      $this->aplanarNodo($hija, $nodo["codigo"], $ruta, $nivel + 1, $items);
    }
  }

  private function arbol() {
    return array(
      $this->n("CAT16-PERROS", "Perros", array(
        $this->n("CAT16-PERROS-ALIM", "Alimentacion", array(
          $this->n("CAT16-PERROS-ALIM-ALIMENTOS", "Alimentos"),
          $this->n("CAT16-PERROS-ALIM-PREMIOS", "Premios y snacks"),
          $this->n("CAT16-PERROS-ALIM-COMEDEROS", "Comederos y bebederos"),
          $this->n("CAT16-PERROS-ALIM-CONTENEDORES", "Contenedores"),
          $this->n("CAT16-PERROS-ALIM-TAZONES", "Tazones")
        )),
        $this->n("CAT16-PERROS-SALUD", "Salud e higiene", array(
          $this->n("CAT16-PERROS-SALUD-HIGIENE", "Higiene y limpieza"),
          $this->n("CAT16-PERROS-SALUD-PREVENCION", "Prevencion y cuidado"),
          $this->n("CAT16-PERROS-SALUD-ANTIPULGAS", "Antipulgas y control externo")
        )),
        $this->n("CAT16-PERROS-HAB", "Habitat y descanso", array(
          $this->n("CAT16-PERROS-HAB-CAMAS", "Camas y colchones"),
          $this->n("CAT16-PERROS-HAB-CASAS", "Casas"),
          $this->n("CAT16-PERROS-HAB-JAULAS", "Jaulas"),
          $this->n("CAT16-PERROS-HAB-CORRALES", "Corrales y vallas")
        )),
        $this->n("CAT16-PERROS-JUEGO", "Juego y enriquecimiento", array(
          $this->n("CAT16-PERROS-JUEGO-JUGUETES", "Juguetes"),
          $this->n("CAT16-PERROS-JUEGO-MORDEDERAS", "Mordederas"),
          $this->n("CAT16-PERROS-JUEGO-INTERACTIVOS", "Interactivos y entrenamiento mental")
        )),
        $this->n("CAT16-PERROS-TRANS", "Transporte, paseo y entrenamiento", array(
          $this->n("CAT16-PERROS-TRANS-TRANSPORTADORAS", "Transportadoras"),
          $this->n("CAT16-PERROS-TRANS-MOCHILAS", "Mochilas transportadoras"),
          $this->n("CAT16-PERROS-TRANS-SUJECION", "Paseo y sujecion"),
          $this->n("CAT16-PERROS-TRANS-ENTRENAMIENTO", "Entrenamiento")
        )),
        $this->n("CAT16-PERROS-ACCESORIOS", "Accesorios generales")
      )),
      $this->n("CAT16-GATOS", "Gatos", array(
        $this->n("CAT16-GATOS-ALIM", "Alimentacion", array(
          $this->n("CAT16-GATOS-ALIM-ALIMENTOS", "Alimentos"),
          $this->n("CAT16-GATOS-ALIM-PREMIOS", "Premios y snacks"),
          $this->n("CAT16-GATOS-ALIM-COMEDEROS", "Comederos y bebederos"),
          $this->n("CAT16-GATOS-ALIM-CONTENEDORES", "Contenedores"),
          $this->n("CAT16-GATOS-ALIM-TAZONES", "Tazones")
        )),
        $this->n("CAT16-GATOS-SALUD", "Salud e higiene", array(
          $this->n("CAT16-GATOS-SALUD-HIGIENE", "Higiene y limpieza"),
          $this->n("CAT16-GATOS-SALUD-ARENEROS", "Areneros"),
          $this->n("CAT16-GATOS-SALUD-SANITARIOS", "Accesorios sanitarios"),
          $this->n("CAT16-GATOS-SALUD-ARENAS", "Control de olores y arenas")
        )),
        $this->n("CAT16-GATOS-HAB", "Habitat y descanso", array(
          $this->n("CAT16-GATOS-HAB-CAMAS", "Camas y colchones"),
          $this->n("CAT16-GATOS-HAB-CASAS", "Casas"),
          $this->n("CAT16-GATOS-HAB-CONTENCION", "Contencion", array(
            $this->n("CAT16-GATOS-HAB-CONT-JAULAS", "Jaulas"),
            $this->n("CAT16-GATOS-HAB-CONT-CORRALES", "Corrales y vallas")
          ))
        )),
        $this->n("CAT16-GATOS-JUEGO", "Juego y enriquecimiento", array(
          $this->n("CAT16-GATOS-JUEGO-JUGUETES", "Juguetes"),
          $this->n("CAT16-GATOS-JUEGO-RASCADORES", "Rascadores"),
          $this->n("CAT16-GATOS-JUEGO-INTERACTIVOS", "Interactivos y enriquecimiento")
        )),
        $this->n("CAT16-GATOS-TRANS", "Transporte, paseo y entrenamiento", array(
          $this->n("CAT16-GATOS-TRANS-TRANSPORTADORAS", "Transportadoras"),
          $this->n("CAT16-GATOS-TRANS-MOCHILAS", "Mochilas transportadoras"),
          $this->n("CAT16-GATOS-TRANS-SUJECION", "Paseo y sujecion"),
          $this->n("CAT16-GATOS-TRANS-ENTRENAMIENTO", "Entrenamiento")
        )),
        $this->n("CAT16-GATOS-ACCESORIOS", "Accesorios generales")
      )),
      $this->n("CAT16-ACUARIO", "Acuario y peces", array(
        $this->n("CAT16-ACUARIO-PECERAS", "Peceras, acuarios y muebles", array(
          $this->n("CAT16-ACUARIO-PECERAS-PECERAS", "Peceras"),
          $this->n("CAT16-ACUARIO-PECERAS-ACUARIOS", "Acuarios"),
          $this->n("CAT16-ACUARIO-PECERAS-EQUIPADAS", "Peceras equipadas"),
          $this->n("CAT16-ACUARIO-PECERAS-BETTA", "Peceras para betta"),
          $this->n("CAT16-ACUARIO-PECERAS-BASES", "Bases y muebles")
        )),
        $this->n("CAT16-ACUARIO-EQUIP", "Equipamiento tecnico", array(
          $this->n("CAT16-ACUARIO-EQUIP-FILTRACION", "Filtracion y oxigenacion"),
          $this->n("CAT16-ACUARIO-EQUIP-BOMBAS", "Bombas y circulacion"),
          $this->n("CAT16-ACUARIO-EQUIP-CALEFACCION", "Calefaccion"),
          $this->n("CAT16-ACUARIO-EQUIP-ILUMINACION", "Iluminacion")
        )),
        $this->n("CAT16-ACUARIO-DECO", "Decoracion y ambientacion", array(
          $this->n("CAT16-ACUARIO-DECO-PECES", "Decoracion para peces"),
          $this->n("CAT16-ACUARIO-DECO-SUSTRATOS", "Sustratos, gravas y arenas"),
          $this->n("CAT16-ACUARIO-DECO-PLANTAS-ART", "Plantas artificiales")
        )),
        $this->n("CAT16-ACUARIO-ALIM", "Alimentacion", array(
          $this->n("CAT16-ACUARIO-ALIM-PECES", "Alimentos para peces"),
          $this->n("CAT16-ACUARIO-ALIM-BETTA", "Alimentos para betta"),
          $this->n("CAT16-ACUARIO-ALIM-ACUARIO", "Alimentos de acuario"),
          $this->n("CAT16-ACUARIO-ALIM-FONDO", "Alimentos de fondo"),
          $this->n("CAT16-ACUARIO-ALIM-AJOLOTES", "Alimentos para ajolotes")
        )),
        $this->n("CAT16-ACUARIO-VIVOS", "Animales vivos y plantas naturales", array(
          $this->n("CAT16-ACUARIO-VIVOS-PECES", "Peces"),
          $this->n("CAT16-ACUARIO-VIVOS-PLANTAS", "Plantas acuaticas")
        )),
        $this->n("CAT16-ACUARIO-REP", "Repuestos, aditamentos y accesorios", array(
          $this->n("CAT16-ACUARIO-REP-REPUESTOS", "Repuestos para peceras"),
          $this->n("CAT16-ACUARIO-REP-ACCESORIOS", "Accesorios generales"),
          $this->n("CAT16-ACUARIO-REP-ADITAMENTOS", "Aditamentos")
        ))
      )),
      $this->n("CAT16-REPTILES", "Reptiles y tortugas", array(
        $this->n("CAT16-REPTILES-GRAL", "Reptiles generales", array(
          $this->n("CAT16-REPTILES-GRAL-ALIMENTOS", "Alimentos para reptiles"),
          $this->n("CAT16-REPTILES-GRAL-VIVOS", "Alimentos vivos"),
          $this->n("CAT16-REPTILES-GRAL-CALEFACCION", "Calefaccion"),
          $this->n("CAT16-REPTILES-GRAL-DECO", "Decoracion y aditamentos"),
          $this->n("CAT16-REPTILES-GRAL-TERRARIOS", "Terrarios", array(
            $this->n("CAT16-REPTILES-GRAL-TERR-MADERA", "Madera"),
            $this->n("CAT16-REPTILES-GRAL-TERR-ALUMINIO", "Aluminio")
          ))
        )),
        $this->n("CAT16-REPTILES-TORTUGAS", "Tortugas", array(
          $this->n("CAT16-REPTILES-TORT-ALIMENTOS", "Alimentos para tortugas"),
          $this->n("CAT16-REPTILES-TORT-TORTUGUEROS", "Tortugueros"),
          $this->n("CAT16-REPTILES-TORT-ACCESORIOS", "Aditamentos y accesorios")
        )),
        $this->n("CAT16-REPTILES-IGUANAS", "Iguanas", array(
          $this->n("CAT16-REPTILES-IGUANAS-ALIMENTOS", "Alimentos para iguana"),
          $this->n("CAT16-REPTILES-IGUANAS-TERRARIOS", "Terrarios", array(
            $this->n("CAT16-REPTILES-IGUANAS-TERR-MADERA", "Madera"),
            $this->n("CAT16-REPTILES-IGUANAS-TERR-ALUMINIO", "Aluminio")
          ))
        )),
        $this->n("CAT16-REPTILES-TRANS", "Transporte", array(
          $this->n("CAT16-REPTILES-TRANS-TRANSPORTADORAS", "Transportadoras")
        )),
        $this->n("CAT16-REPTILES-ACCESORIOS", "Accesorios generales")
      )),
      $this->n("CAT16-AVES", "Aves", array(
        $this->n("CAT16-AVES-ALIM", "Alimentacion", array(
          $this->n("CAT16-AVES-ALIM-ALIMENTOS", "Alimentos"),
          $this->n("CAT16-AVES-ALIM-VIVO-DESH", "Alimento vivo y deshidratado", array(
            $this->n("CAT16-AVES-ALIM-VIVO", "Alimento vivo"),
            $this->n("CAT16-AVES-ALIM-DESH", "Alimento deshidratado")
          )),
          $this->n("CAT16-AVES-ALIM-PREMIOS", "Premios y snacks"),
          $this->n("CAT16-AVES-ALIM-SUPLEMENTOS", "Suplementos y vitaminas", array(
            $this->n("CAT16-AVES-ALIM-SUP-GENERALES", "Generales"),
            $this->n("CAT16-AVES-ALIM-SUP-POSTURA", "Postura"),
            $this->n("CAT16-AVES-ALIM-SUP-CANTO", "Canto")
          )),
          $this->n("CAT16-AVES-ALIM-COLIBRI", "Bebederos y aditamentos para colibri"),
          $this->n("CAT16-AVES-ALIM-BEB-COM", "Bebederos y comederos", array(
            $this->n("CAT16-AVES-ALIM-BEBEDEROS", "Bebederos"),
            $this->n("CAT16-AVES-ALIM-COMEDEROS", "Comederos")
          ))
        )),
        $this->n("CAT16-AVES-SALUD", "Salud e higiene", array(
          $this->n("CAT16-AVES-SALUD-LIMPIEZA", "Limpieza de habitat"),
          $this->n("CAT16-AVES-SALUD-ACAROS", "Prevencion y control de acaros"),
          $this->n("CAT16-AVES-SALUD-SUSTRATOS", "Sustratos y fondos")
        )),
        $this->n("CAT16-AVES-HAB", "Habitat", array(
          $this->n("CAT16-AVES-HAB-JAULAS", "Jaulas"),
          $this->n("CAT16-AVES-HAB-ACCESORIOS", "Accesorios para jaulas")
        )),
        $this->n("CAT16-AVES-JUEGO", "Juego y enriquecimiento", array(
          $this->n("CAT16-AVES-JUEGO-JUGUETES", "Juguetes")
        )),
        $this->n("CAT16-AVES-TRANS", "Transporte", array(
          $this->n("CAT16-AVES-TRANS-TRANSPORTADORAS", "Transportadoras")
        )),
        $this->n("CAT16-AVES-ACCESORIOS", "Accesorios generales")
      )),
      $this->n("CAT16-MAMIFEROS", "Pequenos mamiferos", array(
        $this->especieMamifero("HAMSTER", "Hamster", true, true),
        $this->especieMamifero("CONEJO", "Conejo", false, true, true),
        $this->especieMamifero("CUYO", "Cuyo", false, true),
        $this->especieMamifero("ERIZO", "Erizo", true, false),
        $this->especieMamifero("CHINCHILLA", "Chinchilla", false, true),
        $this->especieMamifero("HURON", "Huron", false, false, false, true),
        $this->n("CAT16-MAMIFEROS-ACCESORIOS", "Accesorios generales")
      ))
    );
  }

  private function especieMamifero($codigo, $nombre, $alimentoVivo, $desgasteDental, $camas = false, $premios = false) {
    $alimentacion = array($this->n("CAT16-MAMIFEROS-" . $codigo . "-ALIM-ALIMENTOS", "Alimentos"));
    if ($premios || $desgasteDental) {
      $alimentacion[] = $this->n("CAT16-MAMIFEROS-" . $codigo . "-ALIM-PREMIOS", "Premios y snacks");
    }
    if ($desgasteDental) {
      $alimentacion[] = $this->n("CAT16-MAMIFEROS-" . $codigo . "-ALIM-DESGASTE", "Desgaste dental");
    }
    if ($alimentoVivo) {
      $alimentacion[] = $this->n("CAT16-MAMIFEROS-" . $codigo . "-ALIM-VIVO-DESH", "Alimento vivo y deshidratado", array(
        $this->n("CAT16-MAMIFEROS-" . $codigo . "-ALIM-VIVO", "Alimento vivo"),
        $this->n("CAT16-MAMIFEROS-" . $codigo . "-ALIM-DESH", "Alimento deshidratado")
      ));
    }
    $hijas = array(
      $this->n("CAT16-MAMIFEROS-" . $codigo . "-ALIM", "Alimentacion", $alimentacion),
      $this->n("CAT16-MAMIFEROS-" . $codigo . "-HAB", "Habitat y jaulas")
    );
    if ($camas) {
      $hijas[] = $this->n("CAT16-MAMIFEROS-" . $codigo . "-CAMAS", "Camas, casas y colchones");
    }
    $hijas[] = $this->n("CAT16-MAMIFEROS-" . $codigo . "-SALUD", "Salud e higiene", array(
      $this->n("CAT16-MAMIFEROS-" . $codigo . "-SALUD-SUSTRATOS", "Sustratos")
    ));
    $hijas[] = $this->n("CAT16-MAMIFEROS-" . $codigo . "-TRANS", "Transporte");
    if ($codigo === "HAMSTER") {
      array_splice($hijas, 2, 0, array($this->n("CAT16-MAMIFEROS-HAMSTER-ACCESORIOS", "Accesorios y aditamentos")));
    }
    return $this->n("CAT16-MAMIFEROS-" . $codigo, $nombre, $hijas);
  }

  private function n($codigo, $nombre, $hijas = array()) {
    return array("codigo" => $codigo, "nombre" => $nombre, "hijas" => $hijas);
  }

  private function bloqueo($execute, $token, $respaldo) {
    if (!$execute) {
      return "";
    }
    if ($token !== $this->tokenEsperado) {
      return "Token invalido.";
    }
    if ($respaldo === "" || !file_exists($respaldo)) {
      return "Respaldo externo inexistente o no indicado.";
    }
    $proyecto = realpath(__DIR__ . "/../..");
    $respaldoReal = realpath($respaldo);
    if ($proyecto && $respaldoReal && stripos($respaldoReal, $proyecto) === 0) {
      return "El respaldo no puede estar dentro del proyecto.";
    }
    return "";
  }

  private function parseArgs($argv) {
    $args = array();
    foreach ($argv as $arg) {
      if ($arg === "--execute") {
        $args["execute"] = true;
        continue;
      }
      if (strpos($arg, "--") === 0 && strpos($arg, "=") !== false) {
        list($key, $value) = explode("=", substr($arg, 2), 2);
        $args[$key] = $value;
      }
    }
    return $args;
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasArbol16Apply())->ejecutar($argv), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
