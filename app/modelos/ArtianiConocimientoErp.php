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
        $productosCatalogo = $this->productosCatalogoRelacionados($especie);
        return $this->respuesta(false, "success", "Ficha Artiani consultada", array(
          "especie" => $especie,
          "productos_relacionados_pendientes" => $this->productosRelacionadosPendientes($especie),
          "productos_catalogo_candidatos" => $productosCatalogo,
          "habitats_catalogo_analisis" => $slug === "hamster" ? $this->analizarHabitatsHamsterCatalogo() : array("disponible" => false, "productos" => array()),
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
        "resumen" => "Mascota nocturna y territorial; en Artiani se separa por hámster sirio y hámster ruso/chino para recomendar habitat, rueda y productos correctos.",
        "perfil_cliente" => "Conviene para hogares que aceptan actividad nocturna, manejo paciente y habitat individual. La recomendacion cambia segun si es sirio o ruso/chino.",
        "habitat" => array("Habitat individual y ventilado", "Base continua recomendada: minimo operativo 100 x 50 cm de piso util cuando sea posible", "Sustrato absorbente con profundidad para excavar", "Rueda segura segun talla", "Refugio oscuro", "Bebedero o plato estable", "Bano de arena si se maneja como rutina de higiene seca"),
        "alimentacion" => array("Alimento completo especifico para hamster como base", "Mezclas con semillas pueden requerir control para evitar seleccion de solo semillas grasosas", "Premios con moderacion", "Agua limpia diaria", "Verduras/extra solo como complemento seguro y en porciones pequenas"),
        "cuidados" => array("Desgaste dental con mordederas, madera segura, ramas tratadas para mascotas o juguetes para roer", "Limpieza parcial para no retirar todo el olor de golpe", "Evitar caidas y manipulacion brusca", "No juntar adultos sin evaluacion experta", "Enriquecimiento con tuneles, escondites y mordederas", "Observar dientes, apetito, pelaje y actividad nocturna"),
        "preguntas_clave" => array("Es hámster sirio o ruso/chino?", "Ya tienen habitat y que medidas tiene?", "Sera un solo hamster?", "La rueda hace que su espalda quede recta?", "Buscan alimento, cama, rueda o kit completo?", "Quien lo va a manipular y con que supervision?"),
        "alertas" => array("No vender como mascota que deba convivir en pareja; la recomendacion segura inicial es habitat individual.", "No recomendar ruedas pequeñas que arqueen la espalda, especialmente en sirios.", "Evitar jaulas con barrotes muy separados para ruso/chino porque pueden escapar.", "Explicar que son nocturnos y pueden morder si se despiertan o manipulan mal.", "Si hay heridas, diarrea, letargo, secreciones o perdida de apetito, sugerir veterinario de exoticos."),
        "productos_puente" => array("jaula", "habitat", "sustrato papel", "aserrin prensado", "sustrato maiz", "viruta", "alimento hamster", "rueda", "bebedero", "comedero", "mordedera", "casita", "tunel", "arena de baño", "transportadora"),
        "variantes" => array(
          array(
            "clave" => "hamster_sirio",
            "nombre" => "Hámster sirio",
            "alias" => array("sirio", "golden"),
            "tamano" => "Mayor talla corporal; requiere accesorios mas amplios.",
            "convivencia" => "Debe recomendarse individual. Es territorial al crecer.",
            "habitat_especifico" => array("Habitat mas amplio; usar 100 x 50 cm como minimo operativo y preferir mas grande si el cliente puede", "Rueda 28-30 cm o mayor; la espalda debe verse recta al correr", "Entrada de refugios y tuneles mas amplia", "Transportadora rigida ventilada aprox. 30-40 cm para traslados cortos", "Capa de sustrato 15-20 cm minimo practico; ideal 25 cm o mas si el habitat lo permite"),
            "productos_favorables" => array("rueda grande 28-30 cm+", "jaula amplia", "habitat amplio", "casita grande", "tunel grande", "bebedero", "sustrato papel", "aserrin prensado", "alimento hamster", "mordedera"),
            "evitar" => array("Ruedas mini", "Tuneles angostos", "Casitas pequeñas", "Barrotes o accesorios pensados solo para enanos"),
            "mensaje_venta" => "Para sirio hay que cuidar talla: rueda, refugio y tuneles deben quedarle amplios."
          ),
          array(
            "clave" => "hamster_ruso_chino",
            "nombre" => "Hámster ruso o chino",
            "alias" => array("ruso", "chino", "enano", "dwarf"),
            "tamano" => "Menor talla corporal; requiere seguridad contra escapes y accesorios de acceso facil.",
            "convivencia" => "Puede parecer mas sociable, pero la recomendacion comercial segura sigue siendo habitat individual salvo manejo experto.",
            "habitat_especifico" => array("Habitat con barrotes estrechos o paredes lisas seguras; usar 100 x 50 cm como referencia cuando sea posible", "Rueda 20-25 cm para ruso y 25-28 cm si el chino arquea la espalda; superficie continua", "Refugios bajos y accesibles", "Transportadora pequena rigida ventilada aprox. 20-30 cm para traslados cortos", "Sustrato profundo para excavar, idealmente 15-20 cm o mas"),
            "productos_favorables" => array("rueda mediana 20-25 cm", "jaula barrotes estrechos", "habitat seguro", "sustrato papel", "aserrin prensado", "arena de baño", "casita", "tunel", "bebedero pequeño", "alimento hamster"),
            "evitar" => array("Barrotes separados", "Accesorios altos sin proteccion", "Ruedas con espacios donde pueda atorarse", "Casas o bebederos inestables"),
            "mensaje_venta" => "Para ruso/chino pesa mas la seguridad: que no escape, que no se atore y que alcance comedero/bebedero."
          )
        ),
        "comparativa" => array(
          array("criterio" => "Talla", "hamster_sirio" => "Mas grande", "hamster_ruso_chino" => "Mas pequeño"),
          array("criterio" => "Rueda", "hamster_sirio" => "28-30 cm o mayor; espalda recta", "hamster_ruso_chino" => "20-25 cm en ruso; chino puede requerir 25-28 cm; superficie segura"),
          array("criterio" => "Habitat", "hamster_sirio" => "100 x 50 cm como minimo operativo, mejor si es mayor", "hamster_ruso_chino" => "100 x 50 cm como referencia, con barrotes estrechos o paredes lisas"),
          array("criterio" => "Convivencia", "hamster_sirio" => "Individual", "hamster_ruso_chino" => "Recomendar individual salvo manejo experto"),
          array("criterio" => "Riesgo comun", "hamster_sirio" => "Accesorios chicos", "hamster_ruso_chino" => "Escape o atoramiento")
        ),
        "productos_por_necesidad" => array(
          array("necesidad" => "Habitat o jaula", "sirio" => "Amplia, ventilada, con puerta segura, piso continuo y espacio para rueda 28-30 cm+.", "ruso_chino" => "Segura contra escapes; barrotes estrechos o paredes lisas; piso continuo.", "keywords" => array("hamster jaula", "hamster habitat", "jaula hamster")),
          array("necesidad" => "Rueda", "sirio" => "28-30 cm o mayor; si arquea espalda, no funciona.", "ruso_chino" => "20-25 cm en ruso; chino puede requerir 25-28 cm; que no atore patas.", "keywords" => array("rueda hamster", "rueda")),
          array("necesidad" => "Sustrato o cama", "sirio" => "Absorbente y suficiente para excavar; papel o aserrin prensado suelen ser opciones faciles de explicar.", "ruso_chino" => "Absorbente, suave y profundo; evitar polvo y particulas que irriten.", "keywords" => array("sustrato hamster", "cama hamster", "viruta", "papel", "aserrin", "maiz")),
          array("necesidad" => "Alimento", "sirio" => "Mezcla o pellet para hamster; controlar premios.", "ruso_chino" => "Alimento para hamster; cuidar premios azucarados.", "keywords" => array("alimento hamster", "hamster")),
          array("necesidad" => "Bebedero/comedero", "sirio" => "Estable y accesible para talla grande.", "ruso_chino" => "Pequeño, bajo y facil de alcanzar.", "keywords" => array("bebedero hamster", "comedero hamster", "bebedero")),
          array("necesidad" => "Desgaste dental", "sirio" => "Mordederas resistentes y piezas de madera segura; revisar si deja de comer o babea.", "ruso_chino" => "Mordederas pequeñas, ramas/juguetes seguros y alimento completo; revisar dientes si baja consumo.", "keywords" => array("mordedera hamster", "madera hamster", "juguete hamster")),
          array("necesidad" => "Refugios, tuneles y enriquecimiento", "sirio" => "Entradas amplias y mordederas resistentes.", "ruso_chino" => "Tuneles seguros, bajos y sin huecos de atoramiento.", "keywords" => array("casa hamster", "tunel hamster", "mordedera hamster", "juguete hamster")),
          array("necesidad" => "Transportadora", "sirio" => "Rigida, ventilada, aprox. 30-40 cm para traslados cortos; con sustrato y refugio ligero.", "ruso_chino" => "Rigida, ventilada, aprox. 20-30 cm; revisar que no escape por ranuras.", "keywords" => array("transportadora hamster", "transportadora roedor", "transportadora"))
        ),
        "especificaciones" => array(
          "habitat" => array(
            "base" => "Recomendacion Artiani: usar 100 x 50 cm de piso util como minimo operativo cuando el producto lo permita; mas grande es mejor.",
            "altura" => "Preferir altura suficiente para rueda, refugios y 15-20 cm de sustrato; si permite 25 cm o mas de sustrato, mejor.",
            "barrotes" => "Sirio tolera separaciones mayores que un enano, pero siempre debe evitarse escape. Ruso/chino necesita barrotes estrechos o paredes lisas.",
            "limpieza" => "Preferir limpieza parcial y por zonas; no retirar todo el olor salvo limpieza profunda necesaria."
          ),
          "sustrato" => array(
            array("tipo" => "Papel", "uso" => "Muy recomendable como base por suavidad y baja irritacion si es sin aroma y bajo polvo.", "sirio" => "Bueno para excavar si se coloca profundo.", "ruso_chino" => "Bueno por suavidad y control de polvo.", "alerta" => "Evitar papel perfumado o polvoso."),
            array("tipo" => "Aserrin prensado", "uso" => "Puede ayudar a controlar olor y humedad; explicarlo como sustrato absorbente.", "sirio" => "Util como capa absorbente, ideal combinar con material suave si es muy compacto.", "ruso_chino" => "Usar con cuidado si la particula es dura o polvosa.", "alerta" => "Revisar que sea bajo polvo y seguro para pequeños mamiferos."),
            array("tipo" => "Sustrato de maiz", "uso" => "Absorbente; puede servir en zonas de baño o areas humedas.", "sirio" => "Puede funcionar como complemento, no necesariamente como unica cama profunda.", "ruso_chino" => "Vigilar humedad y limpieza frecuente.", "alerta" => "Si se humedece, cambiar para evitar olor/moho."),
            array("tipo" => "Viruta", "uso" => "Solo recomendar si es segura para pequeños mamiferos, seca, sin aroma y con poco polvo.", "sirio" => "Puede usarse como sustrato economico si cumple seguridad.", "ruso_chino" => "Cuidar polvo por vias respiratorias.", "alerta" => "Evitar viruta aromatica o muy polvosa; no prometer seguridad si no se conoce madera/tratamiento.")
          ),
          "rueda" => array(
            "sirio" => "28-30 cm o mayor; espalda recta al correr; superficie continua.",
            "ruso_chino" => "20-25 cm para ruso; chino puede necesitar 25-28 cm; superficie continua y sin huecos.",
            "alerta" => "Si la espalda se arquea, la rueda es chica. Evitar ruedas de barrotes o rejilla."
          ),
          "dental" => array(
            "regla" => "Los incisivos de roedores crecen continuamente; necesitan roer para desgaste dental.",
            "productos" => array("mordederas", "madera segura para mascotas", "juguetes para roer", "tuneles de carton sin tintas peligrosas", "alimento completo con textura adecuada"),
            "alertas" => array("Dientes demasiado largos", "babeo", "baja de apetito", "perdida de peso", "heridas en boca", "dejar de roer")
          ),
          "alimento" => array(
            "base" => "Preferir alimento completo para hamster. Si es mezcla de semillas, explicar que el animal puede seleccionar solo lo que mas le gusta.",
            "proteina_fibra" => "Revisar que sea para hamster, no solo para conejo/cuyo. Evitar dietas improvisadas.",
            "premios" => "Usar con moderacion; en ruso/chino cuidar premios muy dulces.",
            "preguntas" => array("Que alimento come actualmente?", "Come todo o selecciona semillas?", "Ha bajado de peso?", "Tiene premios o fruta diaria?")
          )
        ),
        "criterio_comercial" => array(
          "objetivo" => "Vender segun lo que existe comercialmente, explicando el nivel de recomendacion sin presentar un producto pequeño como ideal permanente.",
          "habitat" => array(
            array(
              "nivel" => "Comercial recomendado",
              "sirio" => "Jaulas/habitats tipo 100 x 55 cm o 120 x 60 cm funcionan mejor para venta responsable; permiten rueda grande y mas sustrato.",
              "ruso_chino" => "80 x 44 cm puede funcionar comercialmente si es seguro contra escapes; 100 x 55 cm o mas es mejor.",
              "uso" => "Habitat principal."
            ),
            array(
              "nivel" => "Comercial aceptable con advertencia",
              "sirio" => "80 x 44 cm puede venderse solo aclarando que es una opcion compacta y que conviene enriquecer/sacar a zona segura supervisada.",
              "ruso_chino" => "80 x 44 cm suele ser una opcion comercial razonable si no hay barrotes separados ni accesorios peligrosos.",
              "uso" => "Habitat principal compacto, con seguimiento del cliente."
            ),
            array(
              "nivel" => "No recomendar como permanente",
              "sirio" => "Jaulas mini o tipo 23 x 17 x 16 cm no deben venderse como casa definitiva.",
              "ruso_chino" => "Jaulas mini tampoco deben venderse como habitat definitivo; pueden servir para traslado, cuarentena corta o exhibicion temporal controlada.",
              "uso" => "Traslado o uso temporal, no vivienda."
            )
          ),
          "rueda_y_ejercicio" => array(
            array(
              "producto" => "Rueda continua",
              "criterio" => "Preferirla sobre metal/rejilla. Medir diametro real antes de asignar a sirio o ruso/chino.",
              "sirio" => "Si no llega a 28-30 cm, vender con advertencia o buscar opcion mayor.",
              "ruso_chino" => "20-25 cm suele funcionar para ruso; chino puede necesitar mas si arquea espalda."
            ),
            array(
              "producto" => "Rueda de metal",
              "criterio" => "Comercialmente existe, pero revisar superficie. Evitar si tiene huecos o riesgo de patas atoradas.",
              "sirio" => "Solo si diametro y superficie son seguros.",
              "ruso_chino" => "Cuidar mas el riesgo de patas por talla pequeña."
            ),
            array(
              "producto" => "Esfera de ejercicio",
              "criterio" => "No vender como sustituto de rueda ni como ejercicio principal. Si se vende, explicarla como uso corto y supervisado.",
              "sirio" => "18 cm suele quedar chica para sirio adulto; usar mucha cautela.",
              "ruso_chino" => "11.5-14.5 cm solo para ejemplares pequeños y supervisado; revisar ventilacion y estres."
            )
          ),
          "sustratos_comerciales" => array(
            array("tipo" => "Papel", "prioridad" => "Primera recomendacion comercial", "argumento" => "Suave, absorbente y facil de explicar; buen punto de partida para cliente nuevo."),
            array("tipo" => "Viruta libre de polvo", "prioridad" => "Opcion comercial si cumple seguridad", "argumento" => "Vender solo si es natural, sin aroma y baja en polvo; ideal explicar vigilancia respiratoria."),
            array("tipo" => "Sustrato de maiz", "prioridad" => "Complemento o alternativa absorbente", "argumento" => "Util para zonas humedas o baños; cambiar si se humedece para evitar olor/moho."),
            array("tipo" => "Aserrin prensado", "prioridad" => "Opcion por control de olor/humedad", "argumento" => "Vender como absorbente; revisar polvo, dureza y comodidad. Puede combinarse con papel si queda muy compacto.")
          ),
          "alimentos_comerciales" => array(
            array("tipo" => "Croqueta/pellet para hamster", "criterio" => "Buena base porque reduce seleccion de solo semillas.", "venta" => "Recomendar como alimento principal si el cliente busca rutina simple."),
            array("tipo" => "Mezcla de semillas", "criterio" => "Comercialmente atractiva, pero el hamster puede seleccionar lo mas grasoso.", "venta" => "Vender explicando porcion, variedad y control de premios."),
            array("tipo" => "Mix frutal o premios", "criterio" => "No debe ser base diaria.", "venta" => "Ofrecer como complemento moderado; mas cuidado en ruso/chino por dulces.")
          ),
          "bebederos_y_accesorios" => array(
            array("producto" => "Bebedero 60 ml", "uso" => "Adecuado para hamster si queda a buena altura y no gotea."),
            array("producto" => "Bebedero 800 ml", "uso" => "Puede ser excesivo para habitat chico; revisar peso, altura y estabilidad."),
            array("producto" => "Casas 10-16 cm", "uso" => "Revisar talla: pueden funcionar para ruso/chino; para sirio adulto pueden quedar chicas."),
            array("producto" => "Bañera 13 x 9 x 9 cm", "uso" => "Mas viable para ruso/chino; para sirio puede quedar justa.")
          )
        ),
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
    $productos = isset($especie["productos_puente"]) && is_array($especie["productos_puente"]) ? $especie["productos_puente"] : array();
    foreach ($productos as $producto) {
      $items[] = array(
        "necesidad" => $producto,
        "estatus" => "pendiente_vincular_sku",
        "criterio" => "Relacionar despues contra Catalogo ERP por categoria, SKU o etiqueta de uso"
      );
    }
    return $items;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: buscar candidatos reales de Catalogo ERP para necesidades de una ficha Artiani sin vincularlos aun.
   * Impacto: Artiani/Catalogo ERP; permite sugerir productos disponibles en catalogo con revision humana posterior.
   * Contrato: read-only; no confirma stock ni precio y no escribe relaciones.
   */
  private function productosCatalogoRelacionados($especie) {
    $db = $this->getConexion();
    $resultado = array(
      "disponible" => false,
      "modo" => "sin_catalogo",
      "grupos" => array()
    );
    if (!$db || !$this->tablaExisteArtiani($db, "erp_catalogo_skus") || !$this->tablaExisteArtiani($db, "erp_catalogo_productos")) {
      return $resultado;
    }

    $necesidades = array();
    if (isset($especie["productos_por_necesidad"]) && is_array($especie["productos_por_necesidad"])) {
      foreach ($especie["productos_por_necesidad"] as $item) {
        $necesidades[] = $item;
      }
    } else {
      foreach ($especie["productos_puente"] as $producto) {
        $necesidades[] = array("necesidad" => $producto, "keywords" => array($producto));
      }
    }

    $resultado["disponible"] = true;
    $resultado["modo"] = "candidatos_catalogo_readonly";
    foreach ($necesidades as $necesidad) {
      $keywords = isset($necesidad["keywords"]) && is_array($necesidad["keywords"]) ? $necesidad["keywords"] : array($necesidad["necesidad"]);
      $resultado["grupos"][] = array(
        "necesidad" => $necesidad["necesidad"],
        "sirio" => isset($necesidad["sirio"]) ? $necesidad["sirio"] : "",
        "ruso_chino" => isset($necesidad["ruso_chino"]) ? $necesidad["ruso_chino"] : "",
        "keywords" => $keywords,
        "candidatos" => $this->buscarSkusCatalogoArtiani($db, $keywords, 6)
      );
    }

    return $resultado;
  }

  private function buscarSkusCatalogoArtiani($db, $keywords, $limite = 6) {
    $candidatos = array();
    $vistos = array();
    $limite = max(1, min(10, intval($limite)));
    foreach ($keywords as $keyword) {
      $keyword = trim((string) $keyword);
      if ($keyword === "" || strlen($keyword) < 3) {
        continue;
      }
      try {
        $stmt = $db->prepare("SELECT s.id_sku, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) AS nombre,
            p.nombre AS producto, p.codigo_producto, s.estatus AS estatus_sku, p.estatus AS estatus_producto
          FROM erp_catalogo_skus s
          INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
          WHERE s.estatus IN ('activo','borrador','en_revision')
            AND p.estatus IN ('activo','borrador','en_revision')
            AND (s.sku LIKE :q OR s.nombre LIKE :q OR p.nombre LIKE :q OR p.codigo_producto LIKE :q)
          ORDER BY CASE WHEN s.nombre LIKE :prefijo THEN 0 WHEN p.nombre LIKE :prefijo THEN 1 ELSE 2 END,
                   s.estatus='activo' DESC, p.nombre, s.nombre
          LIMIT " . intval($limite));
        $stmt->execute(array(
          ":q" => "%" . $keyword . "%",
          ":prefijo" => $keyword . "%"
        ));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $id = intval($fila["id_sku"]);
          if (isset($vistos[$id])) {
            continue;
          }
          $vistos[$id] = true;
          $candidatos[] = array(
            "id_sku" => $id,
            "sku" => $fila["sku"],
            "nombre" => $fila["nombre"],
            "producto" => $fila["producto"],
            "codigo_producto" => $fila["codigo_producto"],
            "estatus_sku" => $fila["estatus_sku"],
            "estatus_producto" => $fila["estatus_producto"],
            "keyword" => $keyword,
            "requiere_revision" => true
          );
          if (count($candidatos) >= $limite) {
            return $candidatos;
          }
        }
      } catch (Exception $e) {
        return array();
      }
    }
    return $candidatos;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: clasificar jaulas/habitats reales del Catalogo ERP para hámster segun medidas comerciales.
   * Impacto: Artiani/Catalogo ERP; ayuda al vendedor a distinguir vivienda principal, opcion compacta y uso temporal.
   * Contrato: read-only; interpreta medidas desde nombre de SKU/producto y requiere revision humana antes de publicar.
   */
  private function analizarHabitatsHamsterCatalogo() {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExisteArtiani($db, "erp_catalogo_skus") || !$this->tablaExisteArtiani($db, "erp_catalogo_productos")) {
      return array("disponible" => false, "productos" => array());
    }
    try {
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) AS nombre,
          p.nombre AS producto, p.codigo_producto, s.estatus AS estatus_sku, p.estatus AS estatus_producto
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        WHERE s.estatus IN ('activo','borrador','en_revision')
          AND p.estatus IN ('activo','borrador','en_revision')
          AND (s.nombre LIKE '%jaula%' OR p.nombre LIKE '%jaula%' OR s.nombre LIKE '%habitat%' OR p.nombre LIKE '%habitat%')
          AND (s.nombre LIKE '%hamster%' OR p.nombre LIKE '%hamster%' OR s.nombre LIKE '%hámster%' OR p.nombre LIKE '%hámster%'
            OR s.nombre LIKE '%roedor%' OR p.nombre LIKE '%roedor%' OR s.nombre LIKE '%mamifero%' OR p.nombre LIKE '%mamifero%'
            OR s.nombre LIKE '%mamífero%' OR p.nombre LIKE '%mamífero%' OR s.nombre LIKE '%huronera%' OR p.nombre LIKE '%huronera%'
            OR s.nombre LIKE '%conejera%' OR p.nombre LIKE '%conejera%' OR s.nombre LIKE '%amper%' OR p.nombre LIKE '%amper%')
          AND s.nombre NOT LIKE '%ave%' AND p.nombre NOT LIKE '%ave%'
          AND s.nombre NOT LIKE '%pajaro%' AND p.nombre NOT LIKE '%pajaro%'
          AND s.nombre NOT LIKE '%pájaro%' AND p.nombre NOT LIKE '%pájaro%'
          AND s.nombre NOT LIKE '%loro%' AND p.nombre NOT LIKE '%loro%'
          AND s.nombre NOT LIKE '%perro%' AND p.nombre NOT LIKE '%perro%'
          AND s.nombre NOT LIKE '%kennel%' AND p.nombre NOT LIKE '%kennel%'
        ORDER BY p.estatus='activo' DESC, s.estatus='activo' DESC, p.nombre, s.nombre
        LIMIT 160");
      $stmt->execute();
      $productos = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $dimensiones = $this->extraerDimensionesProducto($fila["nombre"] . " " . $fila["producto"]);
        $productos[] = $this->clasificarHabitatHamster($fila, $dimensiones);
      }
      usort($productos, function ($a, $b) {
        if ($a["orden"] === $b["orden"]) {
          return $b["area_base_cm2"] <=> $a["area_base_cm2"];
        }
        return $a["orden"] <=> $b["orden"];
      });
      return array(
        "disponible" => true,
        "productos" => $productos,
        "regla" => "Clasificacion automatica por medidas detectadas en nombre de SKU/producto; validar fisicamente barrotes, accesorios y profundidad real."
      );
    } catch (Exception $e) {
      return array("disponible" => false, "productos" => array(), "error" => $e->getMessage());
    }
  }

  private function extraerDimensionesProducto($texto) {
    $normalizado = strtolower((string) $texto);
    $normalizado = str_replace(array("×", "�", "*"), "x", $normalizado);
    if (preg_match('/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)(?:\s*x\s*(\d+(?:\.\d+)?))?\s*cm/i', $normalizado, $m)) {
      return array(
        "largo_cm" => floatval($m[1]),
        "ancho_cm" => floatval($m[2]),
        "alto_cm" => isset($m[3]) && $m[3] !== "" ? floatval($m[3]) : null,
        "texto" => trim($m[0])
      );
    }
    if (preg_match('/(\d+(?:\.\d+)?)\s*cm/i', $normalizado, $m)) {
      return array(
        "largo_cm" => floatval($m[1]),
        "ancho_cm" => null,
        "alto_cm" => null,
        "texto" => trim($m[0])
      );
    }
    return array("largo_cm" => null, "ancho_cm" => null, "alto_cm" => null, "texto" => "");
  }

  private function clasificarHabitatHamster($fila, $dimensiones) {
    $largo = $dimensiones["largo_cm"];
    $ancho = $dimensiones["ancho_cm"];
    $area = ($largo && $ancho) ? round($largo * $ancho, 2) : 0;
    $nivel = "revision_manual";
    $uso = "Revisar medidas fisicas antes de recomendar.";
    $sirio = "Revisar fisicamente.";
    $ruso = "Revisar fisicamente.";
    $orden = 9;

    if ($largo && $ancho) {
      if ($largo >= 80 && $ancho >= 40) {
        $nivel = "principal_recomendado_comercial";
        $uso = "Habitat principal comercial recomendable.";
        $sirio = "Compatible comercialmente, revisar rueda grande y accesorios amplios.";
        $ruso = "Compatible comercialmente, revisar seguridad contra escapes.";
        $orden = 1;
      } elseif ($largo >= 57 && $ancho >= 31) {
        $nivel = "principal_compacto";
        $uso = "Habitat principal compacto con advertencia y enriquecimiento.";
        $sirio = "Solo con advertencia: compacto para sirio; revisar rueda y espacio real.";
        $ruso = "Puede funcionar comercialmente si barrotes/accesorios son seguros.";
        $orden = 2;
      } elseif ($largo >= 45 && $ancho >= 29) {
        $nivel = "compacto_ruso_chino";
        $uso = "Opcion compacta principalmente para ruso/chino.";
        $sirio = "No ideal para sirio adulto como vivienda permanente.";
        $ruso = "Puede venderse con advertencia de espacio y enriquecimiento.";
        $orden = 3;
      } elseif ($largo >= 35 && $ancho >= 26) {
        $nivel = "temporal_o_inicial";
        $uso = "Uso inicial, traslado interno o temporal; no posicionar como ideal.";
        $sirio = "No recomendar como permanente.";
        $ruso = "Solo temporal o inicial con advertencia.";
        $orden = 4;
      } else {
        $nivel = "solo_temporal_traslado";
        $uso = "Traslado, exhibicion temporal o complemento; no vivienda definitiva.";
        $sirio = "No recomendar como habitat.";
        $ruso = "No recomendar como habitat permanente.";
        $orden = 5;
      }
    }

    return array(
      "id_sku" => intval($fila["id_sku"]),
      "sku" => $fila["sku"],
      "nombre" => $fila["nombre"],
      "producto" => $fila["producto"],
      "estatus_sku" => $fila["estatus_sku"],
      "estatus_producto" => $fila["estatus_producto"],
      "dimensiones" => $dimensiones,
      "area_base_cm2" => $area,
      "nivel" => $nivel,
      "uso_recomendado" => $uso,
      "sirio" => $sirio,
      "ruso_chino" => $ruso,
      "orden" => $orden,
      "requiere_revision" => true
    );
  }

  private function tablaExisteArtiani($db, $tabla) {
    if (!is_string($tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return false;
    }
    try {
      $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function coincideBusqueda($especie, $q) {
    $texto = $this->normalizarTexto(implode(" ", array(
      $especie["nombre"],
      $especie["grupo"],
      $especie["dificultad"],
      $especie["resumen"],
      implode(" ", $especie["habitat"]),
      implode(" ", $especie["alimentacion"]),
      implode(" ", $especie["productos_puente"]),
      isset($especie["variantes"]) ? json_encode($especie["variantes"]) : "",
      isset($especie["comparativa"]) ? json_encode($especie["comparativa"]) : ""
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
