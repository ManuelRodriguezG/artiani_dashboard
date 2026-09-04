<?php

class AtencionClienteErp extends CRUD {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-04
   * Proposito: listar reglas operativas base para el asesor comercial de prospectos.
   * Impacto: CRM/Prospectos; centraliza categorias, intenciones y textos sin depender de IA externa.
   * Contrato: read-only; no consulta stock, precios ni promociones.
   */
  public function catalogos() {
    return $this->respuesta(false, "success", "Catalogos comerciales consultados", array(
      "canales" => array("whatsapp", "messenger", "facebook", "telefono", "mostrador"),
      "intenciones" => array(
        "categoria" => "Pregunta por categoria amplia",
        "mayoreo" => "Pregunta por mayoreo",
        "inicio_acuario" => "Quiere iniciar una pecera",
        "kit_especie" => "Quiere productos para una mascota o especie",
        "catalogo" => "Pide catalogo o pagina",
        "producto" => "Pregunta por producto puntual",
        "seguimiento" => "Seguimiento de conversacion",
        "publicacion" => "Texto para publicacion"
      ),
      "categorias" => $this->categorias(),
      "reglas" => array(
        "No inventar stock, precios ni promociones.",
        "Confirmar disponibilidad y precio actualizado antes de cerrar la venta.",
        "Responder como asesor comercial: orientar, ordenar la necesidad y pedir datos clave.",
        "Hacer preguntas utiles cuando la necesidad sea amplia o tecnica.",
        "Mayoreo depende de producto, cantidad y disponibilidad.",
        "Catalogo o pagina web se comparte como referencia, no como confirmacion final."
      )
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-04
   * Proposito: construir una respuesta editable segun intencion, categoria, canal y contexto del prospecto.
   * Impacto: CRM/Prospectos; reduce respuestas improvisadas y mejora consistencia comercial del equipo.
   * Contrato: funcion pura; no escribe BD, no envia mensajes y no promete disponibilidad exacta.
   */
  public function generarRespuesta($filtros = array()) {
    $intencion = $this->normalizarClave($this->valor($filtros, "intencion", "categoria"));
    $categoria = $this->normalizarClave($this->valor($filtros, "categoria", "general"));
    $canal = $this->normalizarClave($this->valor($filtros, "canal", "whatsapp"));
    $mensajeCliente = trim((string) $this->valor($filtros, "mensaje_cliente", ""));
    $producto = trim((string) $this->valor($filtros, "producto", ""));
    $urlCatalogo = trim((string) $this->valor($filtros, "url_catalogo", ""));
    $escenario = "";

    if ($mensajeCliente !== "") {
      $detectado = $this->detectarDesdeMensaje($mensajeCliente);
      if ($categoria === "general" && $detectado["categoria"] !== "") {
        $categoria = $detectado["categoria"];
      }
      if ($intencion === "categoria" && $detectado["intencion"] !== "") {
        $intencion = $detectado["intencion"];
      }
      $escenario = $detectado["escenario"];
    }

    $categorias = $this->categorias();
    $datosCategoria = isset($categorias[$categoria]) ? $categorias[$categoria] : $categorias["general"];
    $respuesta = $this->plantilla($intencion, $datosCategoria, $producto, $urlCatalogo, $escenario);

    return $this->respuesta(false, "success", "Respuesta sugerida generada", array(
      "canal" => $canal,
      "intencion" => $intencion,
      "categoria" => $categoria,
      "escenario" => $escenario,
      "respuesta" => $respuesta,
      "preguntas" => $datosCategoria["preguntas"],
      "recordatorios" => array(
        "Editar antes de enviar segun lo que realmente maneja el negocio.",
        "Confirmar disponibilidad y precio actualizado en el catalogo/inventario.",
        "No cerrar recomendacion tecnica si faltan datos de especie, tamano, equipo actual o cantidad."
      ),
      "variantes" => $this->variantes($datosCategoria, $urlCatalogo)
    ));
  }

  private function plantilla($intencion, $categoria, $producto, $urlCatalogo, $escenario) {
    $nombreCategoria = $categoria["nombre"];
    $productoTexto = $producto !== "" ? " sobre " . $producto : "";

    if ($escenario === "piton_bola") {
      return "Hola, si podemos revisar opciones para armar o completar un habitat para piton bola. Normalmente se revisan terrario, sustrato, refugios, bebedero, calefaccion, control de temperatura/humedad, pinzas y accesorios de seguridad o decoracion. Para recomendarte bien, dime que tamano o edad tiene, si ya cuentas con terrario y que medidas tiene. Con eso revisamos que productos manejamos, disponibilidad y precio actualizado.";
    }
    if ($intencion === "mayoreo") {
      return "Hola, si podemos revisar precio de mayoreo" . $productoTexto . ". Depende del producto, la cantidad y la disponibilidad al momento. Mandanos que producto buscas y cuantas piezas necesitas, y con gusto revisamos la mejor opcion.";
    }
    if ($intencion === "inicio_acuario") {
      return "Hola, con gusto te orientamos para iniciar tu pecera. Para recomendarte bien, cuentanos de cuantos litros seria aproximadamente, que peces te gustaria tener y si ya cuentas con filtro, oxigenacion, calentador o iluminacion.";
    }
    if ($intencion === "catalogo") {
      $extra = $urlCatalogo !== "" ? " Puedes revisarlo aqui: " . $urlCatalogo . "." : " Te podemos compartir catalogo o pagina para que revises opciones.";
      return "Hola, claro." . $extra . " Escribenos por aqui el producto que te interese para confirmar disponibilidad, precio actualizado y opciones de mayoreo si aplica.";
    }
    if ($intencion === "producto") {
      return "Hola, con gusto lo revisamos. Para confirmarte disponibilidad y precio actualizado, mandanos el nombre, marca o foto del producto que buscas" . $productoTexto . ". Si necesitas varias piezas, tambien podemos revisar opcion de mayoreo.";
    }
    if ($intencion === "kit_especie") {
      return "Hola, claro. Podemos ayudarte a revisar lo necesario para " . strtolower($nombreCategoria) . ": " . implode(", ", array_slice($categoria["productos"], 0, 6)) . ". Para recomendarte mejor, dime la especie, tamano aproximado, si ya tienes habitat/equipo y que te falta. Ya con eso revisamos opciones, disponibilidad y precio actualizado.";
    }
    if ($intencion === "seguimiento") {
      return "Hola, seguimos al pendiente. Si nos confirmas el producto, cantidad y para que mascota o uso lo necesitas, revisamos disponibilidad y te damos la mejor opcion posible.";
    }
    if ($intencion === "publicacion") {
      return "Tenemos opciones para " . strtolower($nombreCategoria) . ": " . implode(", ", array_slice($categoria["productos"], 0, 5)) . ". Escribenos para revisar disponibilidad, precio actualizado y opciones segun lo que necesites.";
    }

    return "Hola, con gusto te ayudamos a revisar opciones para " . strtolower($nombreCategoria) . ". Segun lo que necesites podemos ver " . implode(", ", array_slice($categoria["productos"], 0, 5)) . ". Dime para que mascota/equipo es, que ya tienes y que te falta, y revisamos disponibilidad y precio actualizado.";
  }

  private function variantes($categoria, $urlCatalogo) {
    $catalogo = $urlCatalogo !== "" ? " Tambien puedes revisar el catalogo aqui: " . $urlCatalogo . "." : "";
    return array(
      "breve" => "Hola, si podemos revisar opciones para " . strtolower($categoria["nombre"]) . ". Dinos que buscas o que necesitas armar y revisamos disponibilidad y precio actualizado." . $catalogo,
      "pregunta" => "Claro, para ayudarte mejor: " . $categoria["preguntas"][0],
      "asesor" => "Para orientarte bien, dime que mascota o equipo tienes, tamano aproximado y si buscas kit completo o solo algun accesorio.",
      "cierre" => "Mandanos producto y cantidad, y te confirmamos disponibilidad, precio actualizado y mayoreo si aplica."
    );
  }

  private function detectarDesdeMensaje($mensaje) {
    $texto = $this->normalizarTexto($mensaje);
    $resultado = array("categoria" => "", "intencion" => "", "escenario" => "");
    $mapaCategorias = array(
      "acuario" => array("acuario", "pecera", "pez", "peces", "filtro", "anticloro"),
      "perros" => array("perro", "perros", "croqueta", "correa", "collar"),
      "gatos" => array("gato", "gatos", "arena", "arenero", "rascador"),
      "reptiles" => array("reptil", "reptiles", "terrario", "serpiente", "gecko", "iguana", "piton", "python"),
      "roedores" => array("conejo", "hamster", "cuyo", "cobaya", "erizo", "huron", "roedor"),
      "aves" => array("ave", "aves", "pajaro", "perico", "jaula")
    );
    foreach ($mapaCategorias as $clave => $palabras) {
      foreach ($palabras as $palabra) {
        if (strpos($texto, $palabra) !== false) {
          $resultado["categoria"] = $clave;
          break 2;
        }
      }
    }
    if (strpos($texto, "mayoreo") !== false || strpos($texto, "mayorista") !== false) {
      $resultado["intencion"] = "mayoreo";
    } elseif (strpos($texto, "catalogo") !== false || strpos($texto, "pagina") !== false || strpos($texto, "web") !== false) {
      $resultado["intencion"] = "catalogo";
    } elseif (strpos($texto, "iniciar") !== false && (strpos($texto, "pecera") !== false || strpos($texto, "acuario") !== false)) {
      $resultado["intencion"] = "inicio_acuario";
    } elseif (strpos($texto, "necesito") !== false || strpos($texto, "todo") !== false || strpos($texto, "kit") !== false || strpos($texto, "armar") !== false) {
      $resultado["intencion"] = "kit_especie";
    }
    if ((strpos($texto, "piton") !== false || strpos($texto, "python") !== false) && strpos($texto, "bola") !== false) {
      $resultado["categoria"] = "reptiles";
      $resultado["intencion"] = "kit_especie";
      $resultado["escenario"] = "piton_bola";
    }
    return $resultado;
  }

  private function categorias() {
    return array(
      "general" => array(
        "nombre" => "Mascotas y acuariofilia",
        "productos" => array("alimentos", "accesorios", "equipo", "higiene", "mantenimiento"),
        "preguntas" => array("Que producto buscas y para que mascota o uso lo necesitas?")
      ),
      "acuario" => array(
        "nombre" => "Acuario y peces",
        "productos" => array("peceras", "filtros", "bombas", "alimentos", "anticloro"),
        "preguntas" => array("De cuantos litros es tu pecera?", "Que tipo de peces tienes o quieres tener?", "Ya cuentas con filtro y oxigenacion?")
      ),
      "perros" => array(
        "nombre" => "Perros",
        "productos" => array("croquetas", "premios", "correas", "collares", "juguetes"),
        "preguntas" => array("Buscas alimento, accesorio o producto de cuidado?", "Que talla o etapa tiene tu perro?")
      ),
      "gatos" => array(
        "nombre" => "Gatos",
        "productos" => array("alimentos", "premios", "arena", "areneros", "rascadores"),
        "preguntas" => array("Buscas alimento, arena, juguete o accesorio?", "Es para gato cachorro o adulto?")
      ),
      "reptiles" => array(
        "nombre" => "Reptiles y terrarios",
        "productos" => array("terrarios", "sustratos", "refugios", "bebederos", "pinzas", "calefaccion", "termometros/higrometros", "cerraduras", "decoracion"),
        "preguntas" => array("Para que especie es?", "Que tamano o edad tiene?", "Ya tienes terrario y que medidas tiene?", "Buscas kit completo o completar algo que ya tienes?")
      ),
      "roedores" => array(
        "nombre" => "Conejos y pequenos mamiferos",
        "productos" => array("alimentos", "heno", "jaulas", "bebederos", "sustratos"),
        "preguntas" => array("Para que especie es?", "Buscas alimento, jaula, cama, sustrato o accesorio?")
      ),
      "aves" => array(
        "nombre" => "Aves",
        "productos" => array("alimentos", "jaulas", "comederos", "bebederos", "juguetes"),
        "preguntas" => array("Para que tipo de ave es?", "Buscas alimento, jaula o accesorio?")
      ),
      "complementarios" => array(
        "nombre" => "Complementarios",
        "productos" => array("llaveros", "decoracion", "accesorios impresos en 3D", "regalos", "personalizados"),
        "preguntas" => array("Buscas algo para mascota, para el dueno o para decoracion?")
      )
    );
  }

  private function normalizarClave($valor) {
    return preg_replace('/[^a-z0-9_]+/', '', strtolower(trim((string) $valor)));
  }

  private function normalizarTexto($valor) {
    $texto = strtolower(trim((string) $valor));
    $convertido = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
    if (is_string($convertido) && $convertido !== "") {
      $texto = $convertido;
    }
    return $texto;
  }

  private function valor($datos, $clave, $default = "") {
    return isset($datos[$clave]) ? $datos[$clave] : $default;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array(
      "error" => (bool) $error,
      "tipo" => $tipo,
      "mensaje" => $mensaje,
      "depurar" => $depurar
    );
  }
}
