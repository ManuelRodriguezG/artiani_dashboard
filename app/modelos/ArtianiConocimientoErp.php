<?php

class ArtianiConocimientoErp extends CRUD {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: entregar catalogos de clasificacion para la Enciclopedia Artiani.
   * Impacto: Conocimiento/Atencion; define taxonomia inicial para fichas, filtros y futuro agente IA.
   * Contrato: read-only; no depende de tablas mientras el esquema no este autorizado.
   */
  public function catalogos() {
    return $this->respuesta(false, "success", "Catalogos Artiani consultados", array(
      "grupos" => array(
        "peces" => "Peces y acuariofilia",
        "mamiferos_pequenos" => "Mamiferos pequenos",
        "reptiles" => "Reptiles y terrarios"
      ),
      "dificultades" => array(
        "basica" => "Basica",
        "media" => "Media",
        "avanzada" => "Avanzada",
        "especialista" => "Especialista"
      ),
      "enfoques" => array(
        "cliente" => "Cliente",
        "empleado" => "Empleado",
        "web" => "Pagina web",
        "agente_ia" => "Agente IA"
      ),
      "contrato" => array(
        "nombre_modulo" => "Artiani: modulo de conocimiento, cuidado y asesoria animal",
        "primera_etapa" => "Enciclopedia de especies",
        "siguiente_etapa" => "Productos relacionados por especie, habitat, etapa y necesidad",
        "no_sustituye_veterinario" => true
      )
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: listar fichas de especies con filtros operativos.
   * Impacto: Enciclopedia Artiani; permite busqueda interna para empleados y preparacion de contenido web.
   * Contrato: read-only; devuelve resumenes y senales de calidad de conocimiento.
   */
  public function listarEspecies($filtros = array()) {
    $q = $this->normalizarTexto($this->valor($filtros, "q", ""));
    $grupo = $this->normalizarClave($this->valor($filtros, "grupo", ""));
    $dificultad = $this->normalizarClave($this->valor($filtros, "dificultad", ""));
    $limite = intval($this->valor($filtros, "limite", 60));
    $limite = $limite > 0 && $limite <= 200 ? $limite : 60;
    $resultados = array();

    foreach ($this->especiesBase() as $especie) {
      if ($grupo !== "" && $especie["grupo"] !== $grupo) {
        continue;
      }
      if ($dificultad !== "" && $especie["dificultad"] !== $dificultad) {
        continue;
      }
      if ($q !== "" && !$this->coincideBusqueda($especie, $q)) {
        continue;
      }
      $resultados[] = $this->resumenEspecie($especie);
      if (count($resultados) >= $limite) {
        break;
      }
    }

    return $this->respuesta(false, "success", "Especies Artiani consultadas", array(
      "filtros" => array("q" => $q, "grupo" => $grupo, "dificultad" => $dificultad),
      "total" => count($resultados),
      "especies" => $resultados,
      "fuente" => "semilla_operativa_php",
      "pendiente_bd" => true
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: consultar ficha integral de una especie para asesoria y capacitacion.
   * Impacto: Atencion/Capacitacion/Web; entrega cuidados, preguntas, riesgos y productos puente.
   * Contrato: read-only; `slug` identifica la ficha canonica.
   */
  public function consultarEspecie($slug) {
    $slug = $this->normalizarClave($slug);
    foreach ($this->especiesBase() as $especie) {
      if ($especie["slug"] === $slug) {
        return $this->respuesta(false, "success", "Ficha Artiani consultada", array(
          "especie" => $especie,
          "productos_relacionados_pendientes" => $this->productosRelacionadosPendientes($especie),
          "uso_recomendado" => array(
            "empleados" => "Usar preguntas clave antes de recomendar mascota, habitat o productos.",
            "clientes" => "Publicar version simplificada con advertencias y lista de preparacion.",
            "agente_ia" => "Responder con cautela, pedir datos faltantes y evitar diagnosticos medicos."
          )
        ));
      }
    }
    return $this->respuesta(true, "warning", "No se encontro la especie solicitada", array("slug" => $slug));
  }

  private function especiesBase() {
    return array(
      array(
        "slug" => "peces-agua-dulce",
        "nombre" => "Peces de agua dulce",
        "grupo" => "peces",
        "dificultad" => "media",
        "resumen" => "Categoria amplia para orientar acuarios comunitarios, ciclados, filtracion y compatibilidad.",
        "perfil_cliente" => "Ideal para clientes dispuestos a mantener agua estable y aprender rutinas semanales.",
        "habitat" => array("Acuario ciclado", "Filtro adecuado al litraje", "Oxigenacion segun carga biologica", "Temperatura segun especie"),
        "alimentacion" => array("Alimento especifico por especie", "Raciones pequenas", "Evitar sobrealimentar", "Retirar excedentes"),
        "cuidados" => array("Medir calidad de agua", "Cambios parciales programados", "Aclimatacion gradual", "Evitar mezclar especies incompatibles"),
        "preguntas_clave" => array("De cuantos litros es la pecera?", "Ya esta ciclada?", "Que especies tienes o quieres?", "Tienes filtro, calentador y anticloro?"),
        "alertas" => array("No vender peces para acuarios sin ciclar sin advertencia.", "No prometer compatibilidad sin especie, tamano y litros.", "Evitar recomendaciones de tratamiento si parece enfermedad: sugerir revision especializada."),
        "productos_puente" => array("peceras", "filtros", "anticloro", "bacterias", "alimento", "calentador", "red", "sifon", "test de agua"),
        "contenido_web" => array("guia_inicio" => true, "checklist_compra" => true, "faq" => true)
      ),
      array(
        "slug" => "hamster",
        "nombre" => "Hamster",
        "grupo" => "mamiferos_pequenos",
        "dificultad" => "basica",
        "resumen" => "Mascota pequena, nocturna y territorial; requiere habitat seguro, rueda adecuada y manejo delicado.",
        "perfil_cliente" => "Conviene para hogares que aceptan actividad nocturna y supervision al manipular.",
        "habitat" => array("Jaula o habitat ventilado", "Sustrato absorbente", "Rueda segura", "Refugio", "Bebedero"),
        "alimentacion" => array("Alimento especifico para hamster", "Premios con moderacion", "Agua limpia diaria"),
        "cuidados" => array("Limpieza regular sin retirar todo el olor de golpe", "Evitar caidas", "No juntar adultos territoriales", "Enriquecimiento con tuneles y mordederas"),
        "preguntas_clave" => array("Es para nino o adulto?", "Ya tienen habitat?", "Sera un solo hamster?", "Buscan alimento, cama o kit completo?"),
        "alertas" => array("No recomendar convivencia de hamsters adultos sin revisar especie y sexo.", "Explicar que son nocturnos y pueden morder si se manipulan mal."),
        "productos_puente" => array("jaula", "sustrato", "alimento", "rueda", "bebedero", "mordedera", "casita", "transportadora"),
        "contenido_web" => array("guia_inicio" => true, "checklist_compra" => true, "faq" => true)
      ),
      array(
        "slug" => "cuyo",
        "nombre" => "Cuyo",
        "grupo" => "mamiferos_pequenos",
        "dificultad" => "media",
        "resumen" => "Roedor social que requiere heno constante, vitamina C y espacio mayor al que suele imaginar el cliente.",
        "perfil_cliente" => "Bueno para familias con espacio y rutina diaria de limpieza y alimento fresco.",
        "habitat" => array("Corral o jaula amplia", "Cama absorbente", "Escondites", "Bebedero", "Ventilacion sin corrientes fuertes"),
        "alimentacion" => array("Heno disponible siempre", "Pellet especifico", "Vitamina C segun indicacion", "Verduras permitidas con control"),
        "cuidados" => array("Revision dental indirecta por consumo", "Limpieza frecuente", "Socializacion tranquila", "Corte de unas cuando aplique"),
        "preguntas_clave" => array("Tienen espacio para habitat amplio?", "Sera uno o pareja compatible?", "Ya conocen necesidad de heno diario?", "Buscan alimento o habitat completo?"),
        "alertas" => array("No recomendar dietas sin heno.", "Advertir que requieren mas espacio y limpieza que un hamster."),
        "productos_puente" => array("heno", "pellet cuyo", "vitamina C", "jaula/corral", "sustrato", "bebedero", "comedero", "transportadora"),
        "contenido_web" => array("guia_inicio" => true, "checklist_compra" => true, "faq" => true)
      ),
      array(
        "slug" => "erizo",
        "nombre" => "Erizo",
        "grupo" => "mamiferos_pequenos",
        "dificultad" => "avanzada",
        "resumen" => "Mascota sensible a temperatura, dieta y estres; requiere manejo paciente y ambiente estable.",
        "perfil_cliente" => "Para clientes informados, pacientes y con control de temperatura en habitat.",
        "habitat" => array("Habitat seguro", "Control termico", "Rueda apropiada", "Refugio", "Sustrato seguro"),
        "alimentacion" => array("Dieta especifica supervisada", "Insectos de calidad como complemento si aplica", "Agua limpia"),
        "cuidados" => array("Manejo gradual", "Control de peso", "Limpieza de rueda", "Temperatura estable"),
        "preguntas_clave" => array("Ya conocen el rango de temperatura?", "Tienen calefaccion o termometro?", "Que edad tiene?", "Buscan alimento, habitat o accesorios?"),
        "alertas" => array("No vender como mascota de bajo mantenimiento.", "Si hay letargo, frio o falta de apetito, escalar a veterinario especializado."),
        "productos_puente" => array("habitat", "termometro", "calefaccion", "rueda", "refugio", "alimento", "sustrato", "bebedero"),
        "contenido_web" => array("guia_inicio" => true, "checklist_compra" => true, "faq" => true)
      ),
      array(
        "slug" => "chinchilla",
        "nombre" => "Chinchilla",
        "grupo" => "mamiferos_pequenos",
        "dificultad" => "avanzada",
        "resumen" => "Mascota delicada al calor y humedad; necesita polvo de bano, heno y habitat vertical seguro.",
        "perfil_cliente" => "Para clientes con ambiente fresco, espacio vertical y rutina de limpieza seca.",
        "habitat" => array("Jaula vertical segura", "Ambiente fresco", "Repizas", "Casa/refugio", "Sustrato adecuado"),
        "alimentacion" => array("Heno constante", "Pellet especifico", "Premios muy controlados", "Agua limpia"),
        "cuidados" => array("Bano de polvo", "Evitar calor y humedad", "Mordederas", "Manejo cuidadoso"),
        "preguntas_clave" => array("Tienen lugar fresco?", "Ya cuentan con jaula vertical?", "Conocen el bano de polvo?", "Buscan alimento o accesorios?"),
        "alertas" => array("El calor puede ser riesgo serio.", "No recomendar banos con agua salvo indicacion profesional."),
        "productos_puente" => array("heno", "pellet chinchilla", "polvo de bano", "banera", "jaula", "repisas", "mordederas", "bebedero"),
        "contenido_web" => array("guia_inicio" => true, "checklist_compra" => true, "faq" => true)
      ),
      array(
        "slug" => "huron",
        "nombre" => "Huron",
        "grupo" => "mamiferos_pequenos",
        "dificultad" => "especialista",
        "resumen" => "Carnivoro curioso y activo; requiere dieta adecuada, enriquecimiento, seguridad del hogar y asesorias responsables.",
        "perfil_cliente" => "Para clientes con tiempo de convivencia, presupuesto y disposicion a atencion veterinaria especializada.",
        "habitat" => array("Jaula amplia para descanso", "Area segura para juego", "Arenero si esta entrenado", "Cama o hamaca", "Transportadora"),
        "alimentacion" => array("Alimento especifico para huron", "Alta proteina animal", "Evitar dietas improvisadas", "Agua limpia"),
        "cuidados" => array("Juego supervisado", "Seguridad contra escapes", "Limpieza frecuente", "Revision veterinaria especializada"),
        "preguntas_clave" => array("Ya han tenido hurones?", "Tienen espacio seguro para soltarlo?", "Buscan alimento o habitat?", "Cuentan con veterinario especializado?"),
        "alertas" => array("No vender como mascota simple para ninos sin supervision.", "Evitar consejos medicos; escalar salud y vacunas a especialista."),
        "productos_puente" => array("alimento huron", "jaula", "hamaca", "arenero", "sustrato", "juguetes", "arnes", "transportadora"),
        "contenido_web" => array("guia_inicio" => true, "checklist_compra" => true, "faq" => true)
      ),
      array(
        "slug" => "serpientes",
        "nombre" => "Serpientes",
        "grupo" => "reptiles",
        "dificultad" => "especialista",
        "resumen" => "Reptiles que dependen de habitat controlado, seguridad, temperatura, humedad y manejo responsable por especie.",
        "perfil_cliente" => "Para clientes que aceptan control ambiental, alimentacion especifica y medidas de seguridad.",
        "habitat" => array("Terrario seguro con cierre", "Gradiente termico", "Higrometro/termometro", "Refugios", "Sustrato por especie"),
        "alimentacion" => array("Presa adecuada por tamano y especie", "Frecuencia segun edad", "Agua limpia", "No manipular despues de comer"),
        "cuidados" => array("Control diario de temperatura/humedad", "Muda completa", "Manejo calmado", "Higiene y seguridad"),
        "preguntas_clave" => array("Que especie es?", "Que edad o tamano tiene?", "Ya tienes terrario y medidas?", "Como controlas temperatura y humedad?", "Buscas kit completo o accesorios?"),
        "alertas" => array("No recomendar habitat sin conocer especie.", "No vender equipo termico sin explicar control con termostato/medicion.", "Evitar diagnosticos de salud: escalar a veterinario de exoticos."),
        "productos_puente" => array("terrario", "cerradura", "sustrato", "refugio", "bebedero", "calefaccion", "termostato", "termometro", "higrometro", "pinzas"),
        "contenido_web" => array("guia_inicio" => true, "checklist_compra" => true, "faq" => true)
      )
    );
  }

  private function resumenEspecie($especie) {
    return array(
      "slug" => $especie["slug"],
      "nombre" => $especie["nombre"],
      "grupo" => $especie["grupo"],
      "dificultad" => $especie["dificultad"],
      "resumen" => $especie["resumen"],
      "productos_puente" => array_slice($especie["productos_puente"], 0, 5),
      "alertas_count" => count($especie["alertas"]),
      "preguntas_count" => count($especie["preguntas_clave"])
    );
  }

  private function productosRelacionadosPendientes($especie) {
    $items = array();
    foreach ($especie["productos_puente"] as $producto) {
      $items[] = array(
        "necesidad" => $producto,
        "estatus" => "pendiente_vincular_sku",
        "criterio" => "Relacionar despues contra Catalogo ERP por categoria, SKU o etiqueta de uso"
      );
    }
    return $items;
  }

  private function coincideBusqueda($especie, $q) {
    $texto = $this->normalizarTexto(implode(" ", array(
      $especie["nombre"],
      $especie["grupo"],
      $especie["dificultad"],
      $especie["resumen"],
      implode(" ", $especie["habitat"]),
      implode(" ", $especie["alimentacion"]),
      implode(" ", $especie["productos_puente"])
    )));
    return strpos($texto, $q) !== false;
  }

  private function normalizarClave($valor) {
    return preg_replace('/[^a-z0-9_\\-]+/', '', strtolower(trim((string) $valor)));
  }

  private function normalizarTexto($valor) {
    $texto = strtolower(trim((string) $valor));
    $convertido = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
    if (is_string($convertido) && $convertido !== "") {
      $texto = $convertido;
    }
    return preg_replace('/\\s+/', ' ', $texto);
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
