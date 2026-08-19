<?php

class MigracionesBd extends CRUD {

  private $db;

  public function __construct() {
    parent::__construct();
    $this->db = $this->getConexion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: entregar estado inicial seguro de la consola de migraciones.
   * Impacto: Administracion/SYS; no expone passwords ni escribe BD.
   * Contrato: devuelve ambiente local, destinos configurados y resumen de tablas de la BD activa.
   */
  public function diagnosticoInicial() {
    $ambientes = $this->ambientesDisponibles();
    $tablas = $this->snapshotTablas($this->db, defined("MYSQLBASE") ? MYSQLBASE : "");
    return $this->respuesta(false, "success", "Diagnostico de migraciones consultado", array(
      "ambiente_local" => $this->ambienteLocalSaneado(),
      "ambientes" => $ambientes,
      "tablas_locales" => $tablas["error"] ? array() : $tablas["depurar"]["tablas"],
      "totales" => $tablas["error"] ? array("tablas" => 0, "columnas" => 0) : $tablas["depurar"]["totales"],
      "configuracion" => array(
        "archivo_ambientes" => file_exists($this->rutaConfigAmbientes()),
        "ruta_esperada" => "app/config/migraciones_ambientes.local.php",
        "aplicacion_real_habilitada" => false,
        "esquema_tecnico" => $this->estadoEsquemaTecnico()
      )
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: listar ambientes configurados sin devolver secretos.
   * Impacto: Administracion/SYS; prepara comparacion local-productivo.
   * Contrato: lee configuracion local opcional y enmascara host/base/usuario.
   */
  public function listarAmbientes() {
    return $this->respuesta(false, "success", "Ambientes consultados", $this->ambientesDisponibles());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-04
   * Proposito: validar si un destino esta listo para comparacion y paquetes.
   * Impacto: Migraciones BD; solo lectura y sin exponer password.
   * Contrato: valida configuracion, detecta placeholders y prueba conexion si esta completo.
   */
  public function preflightDestino($alias = "productivo") {
    $alias = trim((string) $alias);
    if ($alias === "" || $alias === "local") {
      return $this->respuesta(true, "warning", "Indica un destino distinto a local", array(
        "alias" => $alias
      ));
    }

    $ambiente = $this->ambientePorAlias($alias);
    if (!$ambiente) {
      return $this->respuesta(false, "warning", "Destino no configurado", array(
        "alias" => $alias,
        "configurado" => false,
        "archivo_esperado" => "app/config/migraciones_ambientes.local.php",
        "ejemplo" => "app/config/migraciones_ambientes.example.php",
        "campos_requeridos" => array("tipo", "descripcion", "host", "base", "usuario", "password"),
        "siguiente_paso" => "Agregar el destino `" . $alias . "` en el archivo local no versionado."
      ));
    }

    $host = isset($ambiente["host"]) ? trim((string) $ambiente["host"]) : "";
    $base = isset($ambiente["base"]) ? trim((string) $ambiente["base"]) : "";
    $usuario = isset($ambiente["usuario"]) ? trim((string) $ambiente["usuario"]) : "";
    $password = isset($ambiente["password"]) ? (string) $ambiente["password"] : "";
    $placeholder = $this->valorConfigPlaceholder($host) || $this->valorConfigPlaceholder($base) || $this->valorConfigPlaceholder($usuario)
      || ($password !== "" && $this->valorConfigPlaceholder($password));
    $completo = $host !== "" && $base !== "" && $usuario !== "" && !$placeholder;

    $mismaBaseLocal = defined("MYSQLBASE") && $base === MYSQLBASE
      && defined("MYSQLUSER") && $usuario === MYSQLUSER
      && defined("MYSQLHOST") && in_array($host, array(MYSQLHOST, "localhost", "127.0.0.1"), true);

    $conexion = null;
    if ($completo) {
      $prueba = $this->probarAmbiente($alias);
      $conexion = array(
        "ok" => !$prueba["error"],
        "mensaje" => $prueba["mensaje"],
        "depurar" => isset($prueba["depurar"]) ? $prueba["depurar"] : null
      );
    }

    $bloqueos = array();
    if (!$completo) {
      $bloqueos[] = "configuracion_incompleta_o_placeholder";
    }
    if ($mismaBaseLocal && $alias !== "local_selfcheck") {
      $bloqueos[] = "destino_apunta_a_base_local";
    }
    if ($conexion && empty($conexion["ok"])) {
      $bloqueos[] = "conexion_destino_fallida";
    }

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Destino listo para comparar" : "Destino no listo para paquetes", array(
      "alias" => $alias,
      "configurado" => true,
      "completo" => $completo,
      "placeholder" => $placeholder,
      "misma_base_local" => $mismaBaseLocal,
      "ambiente" => $this->sanearAmbiente(array_merge($ambiente, array("alias" => $alias))),
      "conexion" => $conexion,
      "bloqueos" => $bloqueos,
      "puede_comparar" => empty($bloqueos),
      "siguiente_paso" => empty($bloqueos)
        ? "Seleccionar este destino, comparar ambientes y crear paquete dry-run persistido."
        : "Completar credenciales reales del destino en app/config/migraciones_ambientes.local.php."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-02
   * Proposito: probar conexion de un ambiente configurado sin exponer secretos.
   * Impacto: Migraciones BD; solo consulta metadatos y no lee datos de negocio.
   * Contrato: devuelve version/base/totales de esquema; nunca devuelve password ni PDO.
   */
  public function probarAmbiente($alias) {
    $alias = trim((string) $alias);
    if ($alias === "") {
      return $this->respuesta(true, "warning", "Indica el ambiente a probar");
    }

    if ($alias === "local") {
      $ambiente = $this->ambienteLocalSaneado();
      $conexion = $this->db;
    } else {
      $ambiente = $this->ambientePorAlias($alias);
      if (!$ambiente) {
        return $this->respuesta(true, "warning", "El ambiente no esta configurado", array(
          "alias" => $alias,
          "ambientes" => $this->ambientesDisponibles()
        ));
      }

      $conexionDestino = $this->conectarAmbiente($ambiente);
      if ($conexionDestino["error"]) {
        return $conexionDestino;
      }
      $conexion = $conexionDestino["depurar"]["conexion"];
      $ambiente["alias"] = $alias;
    }

    try {
      $stmt = $conexion->query("SELECT DATABASE() AS base_actual, VERSION() AS version_mysql");
      $servidor = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : array();
      $base = isset($ambiente["base"]) ? $ambiente["base"] : (defined("MYSQLBASE") ? MYSQLBASE : "");
      $snapshot = $this->snapshotTablas($conexion, $base);
      if ($snapshot["error"]) {
        return $snapshot;
      }

      return $this->respuesta(false, "success", "Conexion de ambiente probada correctamente", array(
        "ambiente" => $this->sanearAmbiente($ambiente),
        "servidor" => array(
          "base_actual" => isset($servidor["base_actual"]) ? $servidor["base_actual"] : "",
          "version_mysql" => isset($servidor["version_mysql"]) ? $servidor["version_mysql"] : ""
        ),
        "totales" => $snapshot["depurar"]["totales"],
        "nota" => "Prueba read-only: no lee filas de negocio ni ejecuta cambios."
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No fue posible completar la prueba del ambiente", array(
        "ambiente" => $this->sanearAmbiente($ambiente),
        "mensaje" => $e->getMessage()
      ));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: revisar prerequisitos operativos del modulo sin modificar archivos ni BD.
   * Impacto: Migraciones BD; ayuda a decidir si ya puede activarse esquema tecnico o paquetes.
   * Contrato: solo lectura; no crea directorios, no conecta productivo y no ejecuta mysqldump.
   */
  public function selfcheckOperativo() {
    $ambientes = $this->ambientesDisponibles();
    $destinos = array();
    foreach ($ambientes as $ambiente) {
      if (isset($ambiente["alias"]) && $ambiente["alias"] !== "local") {
        $destinos[] = $ambiente;
      }
    }

    $directorioRespaldos = $this->directorioRespaldos();
    $parentRespaldos = dirname($directorioRespaldos);
    $directorioExiste = is_dir($directorioRespaldos);
    $directorioEscribible = $directorioExiste && is_writable($directorioRespaldos);
    $parentEscribible = !$directorioExiste && is_dir($parentRespaldos) && is_writable($parentRespaldos);
    $mysqldump = $this->rutaMysqldump();
    $esquema = $this->estadoEsquemaTecnico();

    $checks = array(
      $this->checkItem("base_local", defined("MYSQLBASE") && MYSQLBASE !== "", "Base local activa detectada", "No se detecto MYSQLBASE"),
      $this->checkItem("config_ambientes", file_exists($this->rutaConfigAmbientes()), "Archivo local de ambientes existe", "Falta app/config/migraciones_ambientes.local.php", "warning"),
      $this->checkItem("destinos", count($destinos) > 0, "Hay al menos un destino configurado", "No hay destino externo configurado", "warning"),
      $this->checkItem("mysqldump", file_exists($mysqldump) && is_readable($mysqldump), "mysqldump disponible", "No se encontro mysqldump legible", "danger", array("ruta" => $mysqldump)),
      $this->checkItem("directorio_respaldos", $directorioEscribible || $parentEscribible, "Ruta de respaldos disponible o creable", "Ruta de respaldos no escribible", "danger", array("ruta" => $directorioRespaldos)),
      $this->checkItem("esquema_tecnico", !empty($esquema["listo"]), "Esquema tecnico listo", "Esquema tecnico pendiente", "warning", $esquema),
      $this->checkItem("aplicacion_real", $this->aplicacionRealHabilitada(), "Aplicacion real habilitada por configuracion local", "Aplicacion real apagada por seguridad", "info")
    );

    $bloqueantes = array();
    $advertencias = array();
    foreach ($checks as $check) {
      if (!$check["ok"] && $check["nivel"] === "danger") {
        $bloqueantes[] = $check["codigo"];
      } elseif (!$check["ok"]) {
        $advertencias[] = $check["codigo"];
      }
    }

    return $this->respuesta(false, empty($bloqueantes) ? "success" : "warning", "Selfcheck de migraciones generado", array(
      "checks" => $checks,
      "bloqueantes" => $bloqueantes,
      "advertencias" => $advertencias,
      "ambientes" => $ambientes,
      "siguiente_paso" => empty($bloqueantes)
        ? "Generar respaldo real, validarlo y activar esquema tecnico en local si se autoriza."
        : "Resolver bloqueantes antes de intentar respaldos o activaciones."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-02
   * Proposito: generar checklist operativo consolidado antes de activar esquema o aplicar paquetes.
   * Impacto: Migraciones BD; solo lectura, resume compuertas y siguiente accion.
   * Contrato: recibe respaldo y codigo de paquete opcionales.
   */
  public function checklistOperativo($respaldo = "", $codigoPaquete = "") {
    $selfcheck = $this->selfcheckOperativo();
    $validacionRespaldo = $this->validarRespaldo($respaldo);
    $restore = $this->preflightRestauracion($respaldo);
    $activacion = $this->preflightActivacion($respaldo);
    $esquema = $this->estadoEsquemaTecnico();

    $pasos = array();
    $pasos[] = $this->checklistPaso("selfcheck", "Selfcheck operativo", empty($selfcheck["depurar"]["bloqueantes"]), "Resolver bloqueantes de entorno antes de continuar.", $selfcheck["depurar"]);
    $pasos[] = $this->checklistPaso("respaldo", "Respaldo valido", !empty($validacionRespaldo["depurar"]["ok"]), "Generar o seleccionar un respaldo .sql externo al repo.", $validacionRespaldo["depurar"]);
    $pasos[] = $this->checklistPaso("restore", "Plan de restauracion revisado", !empty($restore["depurar"]["mysql_disponible"]) && !empty($validacionRespaldo["depurar"]["ok"]), "Revisar el plan restore antes de cualquier cambio irreversible.", $restore["depurar"]);
    $pasos[] = $this->checklistPaso("esquema_tecnico", "Esquema tecnico sys_migraciones_*", !empty($esquema["listo"]), "Aplicar esquema tecnico en local con respaldo validado.", $esquema);

    $paqueteInfo = null;
    if (trim((string) $codigoPaquete) !== "") {
      $paquetePreflight = $this->preflightPaqueteAplicacion($codigoPaquete, $respaldo);
      $paqueteInfo = $paquetePreflight;
      $pasos[] = $this->checklistPaso("paquete_vigente", "Paquete vigente", !$paquetePreflight["error"] && !empty($paquetePreflight["depurar"]["vigencia"]["ok"]), "Crear nuevo paquete dry-run si el hash ya no coincide.", $paquetePreflight["depurar"]);
      $pasos[] = $this->checklistPaso("paquete_autorizado", "Paquete autorizado", !$paquetePreflight["error"] && !empty($paquetePreflight["depurar"]["estatus_autorizado"]), "Autorizar paquete con respaldo valido antes de aplicar.", $paquetePreflight["depurar"]);
      $pasos[] = $this->checklistPaso("aplicacion_real", "Aplicacion real habilitada", !$paquetePreflight["error"] && !empty($paquetePreflight["depurar"]["puede_aplicar"]), "Mantener apagado hasta ventana de migracion autorizada.", $paquetePreflight["depurar"]);
    }

    $pendientes = array();
    foreach ($pasos as $paso) {
      if (empty($paso["ok"])) {
        $pendientes[] = $paso["codigo"];
      }
    }

    return $this->respuesta(false, "success", "Checklist operativo generado", array(
      "pasos" => $pasos,
      "pendientes" => $pendientes,
      "listo" => empty($pendientes),
      "respaldo" => $validacionRespaldo["depurar"],
      "paquete" => $paqueteInfo,
      "siguiente_paso" => empty($pendientes) ? "Puede continuar segun ventana autorizada." : "Atender el primer pendiente: " . $pendientes[0]
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: clasificar tablas locales con politica inicial sugerida.
   * Impacto: Migraciones BD; ayuda a separar catalogos migrables de operacion sensible.
   * Contrato: no guarda politica; solo devuelve recomendacion heuristica para revision humana.
   */
  public function clasificarTablas() {
    $snapshot = $this->snapshotTablas($this->db, defined("MYSQLBASE") ? MYSQLBASE : "");
    if ($snapshot["error"]) {
      return $snapshot;
    }
    $politicas = array();
    foreach ($snapshot["depurar"]["tablas"] as $tabla) {
      $sugerida = $this->politicaSugerida($tabla["tabla"], intval($tabla["filas_estimadas"]));
      $persistida = $this->politicaPersistida($tabla["tabla"], null);
      if ($persistida) {
        $sugerida["politica"] = $persistida["politica"];
        $sugerida["incluye_datos"] = !empty($persistida["incluye_datos"]);
        $sugerida["llave_natural"] = isset($persistida["llave_natural"]) ? $persistida["llave_natural"] : "";
        $sugerida["descripcion"] = isset($persistida["descripcion"]) ? $persistida["descripcion"] : "";
        $sugerida["persistida"] = true;
      } else {
        $sugerida["llave_natural"] = "";
        $sugerida["descripcion"] = "";
        $sugerida["persistida"] = false;
      }
      $politicas[] = $sugerida;
    }
    return $this->respuesta(false, "success", "Politicas sugeridas generadas", $politicas);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: perfilar tablas locales para decidir si migran datos, esquema o quedan bloqueadas.
   * Impacto: Migraciones BD; ayuda a preparar primera base productiva sin consultar datos sensibles.
   * Contrato: usa metadatos de INFORMATION_SCHEMA; no lee filas reales de negocio.
   */
  public function perfilarTablasDatos() {
    $snapshot = $this->snapshotTablas($this->db, defined("MYSQLBASE") ? MYSQLBASE : "");
    if ($snapshot["error"]) {
      return $snapshot;
    }
    $perfiles = array();
    foreach ($snapshot["depurar"]["tablas"] as $tabla) {
      $perfiles[] = $this->perfilTablaDatos($tabla);
    }
    return $this->respuesta(false, "success", "Perfil de tablas generado", array(
      "total" => count($perfiles),
      "perfiles" => $perfiles
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: sugerir orden de migracion de datos respetando dependencias por FK.
   * Impacto: Migraciones BD; prepara cargas futuras sin leer datos ni ejecutar DDL.
   * Contrato: usa metadatos de llaves foraneas; reporta ciclos o tablas no ordenables.
   */
  public function ordenarTablasPorDependencias() {
    $snapshot = $this->snapshotTablas($this->db, defined("MYSQLBASE") ? MYSQLBASE : "");
    if ($snapshot["error"]) {
      return $snapshot;
    }

    $mapa = $snapshot["depurar"]["mapa"];
    $dependencias = array();
    $dependientes = array();
    foreach ($mapa as $tabla => $info) {
      $dependencias[$tabla] = array();
      $dependientes[$tabla] = array();
    }

    foreach ($mapa as $tabla => $info) {
      foreach ($info["foraneas"] as $foranea) {
        $referencia = $foranea["tabla_referencia"];
        if ($referencia === $tabla || !isset($mapa[$referencia])) {
          continue;
        }
        $dependencias[$tabla][$referencia] = true;
        $dependientes[$referencia][$tabla] = true;
      }
    }

    $pendientes = array();
    foreach ($dependencias as $tabla => $deps) {
      $pendientes[$tabla] = $deps;
    }

    $orden = array();
    $nivel = 0;
    while (!empty($pendientes)) {
      $libres = array();
      foreach ($pendientes as $tabla => $deps) {
        if (empty($deps)) {
          $libres[] = $tabla;
        }
      }
      if (empty($libres)) {
        break;
      }
      sort($libres);
      foreach ($libres as $tabla) {
        $orden[] = array(
          "orden" => count($orden) + 1,
          "nivel" => $nivel,
          "tabla" => $tabla,
          "depende_de" => array_keys($dependencias[$tabla]),
          "dependientes" => array_keys($dependientes[$tabla]),
          "politica" => $this->politicaSugerida($tabla, intval($mapa[$tabla]["filas_estimadas"]))
        );
        unset($pendientes[$tabla]);
      }
      foreach ($pendientes as $tabla => $deps) {
        foreach ($libres as $libre) {
          unset($pendientes[$tabla][$libre]);
        }
      }
      $nivel++;
    }

    $ciclos = array();
    foreach ($pendientes as $tabla => $deps) {
      $ciclos[] = array(
        "tabla" => $tabla,
        "dependencias_pendientes" => array_keys($deps)
      );
    }

    return $this->respuesta(false, "success", "Orden de migracion sugerido", array(
      "total_tablas" => count($mapa),
      "ordenadas" => count($orden),
      "pendientes" => count($ciclos),
      "orden" => $orden,
      "ciclos_o_dependencias_pendientes" => $ciclos
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: resumir decision de migracion por politica, riesgos y candidatos de datos.
   * Impacto: Migraciones BD; ayuda a priorizar primera base productiva sin tocar datos reales.
   * Contrato: usa perfiles read-only de metadatos y devuelve agregados por politica/riesgo.
   */
  public function resumenDecisionMigracion() {
    $perfil = $this->perfilarTablasDatos();
    if ($perfil["error"]) {
      return $perfil;
    }

    $resumenPoliticas = array();
    $resumenRiesgos = array();
    $candidatasDatos = array();
    $bloqueadas = array();
    $sensibles = array();
    $sinLlave = array();

    foreach ($perfil["depurar"]["perfiles"] as $tabla) {
      $politica = $tabla["politica_sugerida"];
      if (!isset($resumenPoliticas[$politica])) {
        $resumenPoliticas[$politica] = 0;
      }
      $resumenPoliticas[$politica]++;

      foreach ($tabla["riesgos"] as $riesgo) {
        if (!isset($resumenRiesgos[$riesgo])) {
          $resumenRiesgos[$riesgo] = 0;
        }
        $resumenRiesgos[$riesgo]++;
      }

      if (!empty($tabla["incluye_datos_sugerido"]) && empty($tabla["columnas_sensibles"])) {
        $candidatasDatos[] = $tabla;
      }
      if ($politica === "blocked" || $politica === "production_owned") {
        $bloqueadas[] = $tabla;
      }
      if (!empty($tabla["columnas_sensibles"])) {
        $sensibles[] = $tabla;
      }
      if (in_array("sin_llave_natural_clara", $tabla["riesgos"], true) || in_array("sin_pk", $tabla["riesgos"], true)) {
        $sinLlave[] = $tabla;
      }
    }

    ksort($resumenPoliticas);
    ksort($resumenRiesgos);

    return $this->respuesta(false, "success", "Resumen de decision de migracion generado", array(
      "total_tablas" => $perfil["depurar"]["total"],
      "politicas" => $resumenPoliticas,
      "riesgos" => $resumenRiesgos,
      "candidatas_datos" => $this->resumenTablasCorto($candidatasDatos),
      "bloqueadas_o_productivo" => $this->resumenTablasCorto($bloqueadas),
      "sensibles" => $this->resumenTablasCorto($sensibles),
      "sin_llave_clara" => $this->resumenTablasCorto($sinLlave),
      "recomendacion" => "Primero revisar candidatas data_merge/data_seed sin columnas sensibles; despues resolver tablas sin llave clara; dejar production_owned para cuando productivo tenga operacion real."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: generar manifiesto JSON de preparacion de migracion sin persistir ni ejecutar cambios.
   * Impacto: Migraciones BD; permite conservar evidencia portable antes de aplicar esquema tecnico.
   * Contrato: incluye metadatos, resumen, perfil y orden; si recibe destino valido agrega comparacion.
   */
  public function generarManifiestoPreparacion($aliasDestino = "") {
    $resumen = $this->resumenDecisionMigracion();
    $perfil = $this->perfilarTablasDatos();
    $orden = $this->ordenarTablasPorDependencias();
    if ($resumen["error"] || $perfil["error"] || $orden["error"]) {
      return $this->respuesta(true, "danger", "No fue posible generar manifiesto", array(
        "resumen" => $resumen,
        "perfil" => $perfil,
        "orden" => $orden
      ));
    }

    $manifiesto = array(
      "version" => "migraciones_bd_fase1",
      "generado_en" => date("c"),
      "origen" => $this->ambienteLocalSaneado(),
      "destino" => trim((string) $aliasDestino),
      "aplicacion_real_habilitada" => $this->aplicacionRealHabilitada(),
      "esquema_tecnico" => $this->estadoEsquemaTecnico(),
      "resumen_decision" => $resumen["depurar"],
      "perfil_datos" => $perfil["depurar"],
      "orden_migracion" => $orden["depurar"]
    );

    if (trim((string) $aliasDestino) !== "") {
      $comparacion = $this->compararAmbientes($aliasDestino);
      $manifiesto["comparacion"] = $comparacion["error"] ? array(
        "error" => true,
        "mensaje" => $comparacion["mensaje"],
        "depurar" => $comparacion["depurar"]
      ) : $comparacion["depurar"];
    }

    $json = json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $this->respuesta(false, "success", "Manifiesto de preparacion generado", array(
      "hash" => hash("sha256", $json),
      "nombre_sugerido" => "migraciones_bd_manifest_" . date("Ymd_His") . ".json",
      "manifiesto" => $manifiesto,
      "json" => $json
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: comparar esquema de la BD activa contra un destino configurado.
   * Impacto: Migraciones BD; permite preparar plan sin ejecutar DDL ni datos.
   * Contrato: si no hay destino configurado, devuelve advertencia segura.
   */
  public function compararAmbientes($aliasDestino) {
    $aliasDestino = trim((string) $aliasDestino);
    if ($aliasDestino === "" || $aliasDestino === "local") {
      return $this->respuesta(true, "warning", "Configura un ambiente destino distinto a local para comparar", array(
        "destino" => $aliasDestino,
        "ambientes" => $this->ambientesDisponibles()
      ));
    }

    $destino = $this->ambientePorAlias($aliasDestino);
    if (!$destino) {
      return $this->respuesta(true, "warning", "El ambiente destino no esta configurado", array("destino" => $aliasDestino));
    }

    $conexionDestino = $this->conectarAmbiente($destino);
    if ($conexionDestino["error"]) {
      return $conexionDestino;
    }

    $origen = $this->snapshotTablas($this->db, MYSQLBASE);
    $objetivo = $this->snapshotTablas($conexionDestino["depurar"]["conexion"], $destino["base"]);
    if ($origen["error"] || $objetivo["error"]) {
      return $this->respuesta(true, "danger", "No fue posible leer los esquemas", array("origen" => $origen, "destino" => $objetivo));
    }

    $comparacion = $this->compararSnapshots($origen["depurar"], $objetivo["depurar"]);
    return $this->respuesta(false, "success", "Comparacion generada en modo seguro", array(
      "origen" => $this->ambienteLocalSaneado(),
      "destino" => $this->sanearAmbiente($destino),
      "comparacion" => $comparacion
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: generar SQL DDL en dry-run para diferencias detectadas.
   * Impacto: Migraciones BD; prepara revision antes de cualquier autorizacion.
   * Contrato: solo genera texto SQL, no crea paquetes persistentes ni ejecuta sentencias.
   */
  public function generarSqlDryRun($aliasDestino) {
    $comparacion = $this->compararAmbientes($aliasDestino);
    if ($comparacion["error"]) {
      return $comparacion;
    }

    $sql = array();
    $orden = 1;
    foreach ($comparacion["depurar"]["comparacion"]["tablas_solo_origen"] as $tabla) {
      $create = $this->showCreateTable($this->db, $tabla["tabla"]);
      if ($create !== "") {
        $sql[] = array(
          "orden" => $orden++,
          "tipo" => "create_table",
          "tabla" => $tabla["tabla"],
          "riesgo" => isset($tabla["riesgo"]) ? $tabla["riesgo"] : "medio",
          "sql" => $create . ";"
        );
      }
    }

    foreach ($comparacion["depurar"]["comparacion"]["columnas_faltantes_destino"] as $columna) {
      $sql[] = array(
        "orden" => $orden++,
        "tipo" => "add_column",
        "tabla" => $columna["tabla"],
        "riesgo" => isset($columna["riesgo"]) ? $columna["riesgo"] : "medio",
        "sql" => "ALTER TABLE `" . $columna["tabla"] . "` ADD COLUMN `" . $columna["columna"] . "` " . $columna["definicion"] . ";"
      );
    }

    foreach ($comparacion["depurar"]["comparacion"]["indices_faltantes_destino"] as $indice) {
      $sql[] = array(
        "orden" => $orden++,
        "tipo" => "add_index",
        "tabla" => $indice["tabla"],
        "riesgo" => isset($indice["riesgo"]) ? $indice["riesgo"] : "bajo",
        "sql" => "ALTER TABLE `" . $indice["tabla"] . "` ADD " . $indice["definicion"] . ";"
      );
    }

    foreach ($comparacion["depurar"]["comparacion"]["foraneas_faltantes_destino"] as $foranea) {
      $sql[] = array(
        "orden" => $orden++,
        "tipo" => "add_foreign_key",
        "tabla" => $foranea["tabla"],
        "riesgo" => isset($foranea["riesgo"]) ? $foranea["riesgo"] : "alto",
        "sql" => "ALTER TABLE `" . $foranea["tabla"] . "` ADD " . $foranea["definicion"] . ";"
      );
    }

    return $this->respuesta(false, "success", "SQL dry-run generado sin ejecutar", array(
      "sentencias" => $sql,
      "total" => count($sql),
      "nota" => "Fase 1 genera DDL para tablas, columnas, indices no primarios y llaves foraneas faltantes. Datos y merge quedan para fases posteriores."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: guardar politicas de migracion por tabla cuando el esquema tecnico existe.
   * Impacto: Migraciones BD; persiste decisiones de preparacion sin aplicar migraciones.
   * Contrato: `politicas` es arreglo de tabla/politica/incluye_datos/llave_natural/descripcion.
   */
  public function guardarPoliticas($politicas, $idUsuario = 0) {
    if (!$this->tablaTecnicaExiste("sys_migraciones_tablas_politicas")) {
      return $this->respuesta(true, "warning", "Falta aplicar el esquema tecnico de Migraciones BD antes de guardar politicas", array(
        "tabla_requerida" => "sys_migraciones_tablas_politicas",
        "dry_run_disponible" => true
      ));
    }
    if (!is_array($politicas) || empty($politicas)) {
      return $this->respuesta(true, "warning", "No hay politicas para guardar");
    }

    $permitidas = $this->politicasPermitidas();
    $guardadas = array();
    try {
      $this->db->beginTransaction();
      $stmt = $this->db->prepare("INSERT INTO sys_migraciones_tablas_politicas
        (tabla, politica, llave_natural, incluye_datos, requiere_revision, descripcion, estatus, fecha_actualizacion)
        VALUES (:tabla, :politica, :llave, :incluye, :revision, :descripcion, 1, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE politica=VALUES(politica), llave_natural=VALUES(llave_natural),
          incluye_datos=VALUES(incluye_datos), requiere_revision=VALUES(requiere_revision),
          descripcion=VALUES(descripcion), estatus=1, fecha_actualizacion=CURRENT_TIMESTAMP");

      foreach ($politicas as $politica) {
        $tabla = isset($politica["tabla"]) ? trim((string) $politica["tabla"]) : "";
        $valorPolitica = isset($politica["politica"]) ? trim((string) $politica["politica"]) : "blocked";
        if (!$this->identificadorTablaValido($tabla) || !in_array($valorPolitica, $permitidas, true)) {
          continue;
        }
        $incluyeDatos = !empty($politica["incluye_datos"]) ? 1 : 0;
        $llave = isset($politica["llave_natural"]) ? trim((string) $politica["llave_natural"]) : "";
        $descripcion = isset($politica["descripcion"]) ? trim((string) $politica["descripcion"]) : "";
        $stmt->execute(array(
          ":tabla" => $tabla,
          ":politica" => $valorPolitica,
          ":llave" => $llave === "" ? null : $llave,
          ":incluye" => $incluyeDatos,
          ":revision" => 1,
          ":descripcion" => $descripcion
        ));
        $guardadas[] = $tabla;
      }
      $this->db->commit();
      return $this->respuesta(false, "success", "Politicas guardadas", array(
        "tablas" => $guardadas,
        "total" => count($guardadas),
        "id_usuario" => $idUsuario
      ));
    } catch (Exception $e) {
      if ($this->db && $this->db->inTransaction()) {
        $this->db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: crear un paquete dry-run con hash de plan y SQL generado.
   * Impacto: Migraciones BD; prepara evidencia revisable sin ejecutar cambios.
   * Contrato: si el esquema tecnico no existe, devuelve paquete temporal no persistido.
   */
  public function crearPaqueteDryRun($aliasDestino, $tablasSeleccionadas, $idUsuario = 0) {
    $sqlDryRun = $this->generarSqlDryRun($aliasDestino);
    if ($sqlDryRun["error"]) {
      return $sqlDryRun;
    }

    $tablasSeleccionadas = is_array($tablasSeleccionadas) ? $tablasSeleccionadas : array();
    $tablasNormalizadas = array();
    foreach ($tablasSeleccionadas as $tabla) {
      $tabla = trim((string) $tabla);
      if ($this->identificadorTablaValido($tabla)) {
        $tablasNormalizadas[] = $tabla;
      }
    }

    $sentencias = array();
    foreach ($sqlDryRun["depurar"]["sentencias"] as $sentencia) {
      if (empty($tablasNormalizadas) || in_array($sentencia["tabla"], $tablasNormalizadas, true)) {
        $sentencias[] = $sentencia;
      }
    }

    $resumen = $this->resumenPaqueteDryRun($aliasDestino, $tablasNormalizadas, $sentencias);
    $hash = $this->hashPlanPaquete($resumen, $sentencias);
    $codigo = "MIGBD_" . date("Ymd_His") . "_" . substr($hash, 0, 8);

    if (!$this->tablaTecnicaExiste("sys_migraciones_paquetes") || !$this->tablaTecnicaExiste("sys_migraciones_paquete_sql")) {
      return $this->respuesta(false, "info", "Paquete dry-run temporal generado; falta aplicar esquema tecnico para persistirlo", array(
        "persistido" => false,
        "codigo" => $codigo,
        "hash_plan" => $hash,
        "resumen" => $resumen,
        "sentencias" => $sentencias
      ));
    }

    try {
      $this->db->beginTransaction();
      $stmtPaquete = $this->db->prepare("INSERT INTO sys_migraciones_paquetes
        (codigo, ambiente_origen, ambiente_destino, estatus, resumen_json, hash_plan, id_usuario_creacion)
        VALUES (:codigo, 'local', :destino, 'borrador', :resumen, :hash, :usuario)");
      $stmtPaquete->execute(array(
        ":codigo" => $codigo,
        ":destino" => $aliasDestino,
        ":resumen" => json_encode($resumen),
        ":hash" => $hash,
        ":usuario" => $idUsuario > 0 ? $idUsuario : null
      ));
      $idPaquete = intval($this->db->lastInsertId());

      if ($this->tablaTecnicaExiste("sys_migraciones_paquete_tablas")) {
        $stmtTabla = $this->db->prepare("INSERT INTO sys_migraciones_paquete_tablas
          (id_migracion_paquete, tabla, politica, incluye_datos, resumen_json)
          VALUES (:paquete, :tabla, :politica, :incluye, :resumen)
          ON DUPLICATE KEY UPDATE politica=VALUES(politica), incluye_datos=VALUES(incluye_datos), resumen_json=VALUES(resumen_json)");
        foreach ($resumen["tablas_incluidas"] as $tablaInfo) {
          $tabla = $tablaInfo["tabla"];
          $politica = $this->politicaPersistida($tabla);
          $stmtTabla->execute(array(
            ":paquete" => $idPaquete,
            ":tabla" => $tabla,
            ":politica" => $politica["politica"],
            ":incluye" => !empty($politica["incluye_datos"]) ? 1 : 0,
            ":resumen" => json_encode($politica)
          ));
        }
      }

      $stmtSql = $this->db->prepare("INSERT INTO sys_migraciones_paquete_sql
        (id_migracion_paquete, orden, tipo, tabla, politica, sql_texto, riesgo, ejecutado)
        VALUES (:paquete, :orden, :tipo, :tabla, :politica, :sql, :riesgo, 0)");
      foreach ($sentencias as $sentencia) {
        $politica = $this->politicaPersistida($sentencia["tabla"]);
        $stmtSql->execute(array(
          ":paquete" => $idPaquete,
          ":orden" => $sentencia["orden"],
          ":tipo" => $sentencia["tipo"],
          ":tabla" => $sentencia["tabla"],
          ":politica" => $politica["politica"],
          ":sql" => $sentencia["sql"],
          ":riesgo" => $sentencia["riesgo"]
        ));
      }
      $this->db->commit();
      return $this->respuesta(false, "success", "Paquete dry-run persistido", array(
        "persistido" => true,
        "id_migracion_paquete" => $idPaquete,
        "codigo" => $codigo,
        "hash_plan" => $hash,
        "resumen" => $resumen,
        "sentencias" => $sentencias
      ));
    } catch (Exception $e) {
      if ($this->db && $this->db->inTransaction()) {
        $this->db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-31
   * Proposito: validar referencia de respaldo externo antes de autorizar DDL o migraciones.
   * Impacto: Migraciones BD; no crea ni modifica archivos, solo valida ruta/referencia.
   * Contrato: acepta ruta .sql existente fuera del repo o referencia externa suficientemente clara.
   */
  public function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $placeholder = $respaldo === ""
      || strpos($respaldo, "[") !== false
      || stripos($respaldo, "RUTA_RESPALDO") !== false
      || stripos($respaldo, "RUTA_O_REFERENCIA") !== false
      || stripos($respaldo, "RUTA_O_REFERENCIA_RESPALDO") !== false
      || strtoupper($respaldo) === "RESPALDO";
    $pareceRuta = preg_match('/^[a-zA-Z]:\\\\|^\\\\\\\\|\\.sql$/i', $respaldo) === 1;
    $repo = realpath(__DIR__ . "/../..");
    $real = $pareceRuta && file_exists($respaldo) ? realpath($respaldo) : false;
    $dentroRepo = $real && $repo && stripos($real, $repo) === 0;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    $legible = $existe ? is_readable($respaldo) : false;
    $tamano = $existe ? filesize($respaldo) : 0;
    $extensionSql = !$pareceRuta || preg_match('/\\.sql$/i', $respaldo) === 1;

    $ok = !$placeholder && $extensionSql;
    if ($pareceRuta) {
      $ok = $ok && $existe && $legible && $tamano > 0 && !$dentroRepo;
    } else {
      $ok = $ok && strlen($respaldo) >= 8;
    }

    return $this->respuesta(!$ok, $ok ? "success" : "warning", $ok ? "Respaldo validado" : "Respaldo no valido para autorizacion", array(
      "ok" => $ok,
      "respaldo" => $respaldo,
      "parece_ruta" => $pareceRuta,
      "existe" => $existe,
      "legible" => $legible,
      "tamano_bytes" => $tamano,
      "dentro_repo" => $dentroRepo,
      "placeholder" => $placeholder,
      "extension_sql" => $extensionSql,
      "ruta_real" => $real ? $real : null
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: generar respaldo SQL local en la ruta estandar antes de DDL o paquetes.
   * Impacto: Migraciones BD; ejecuta mysqldump solo con token y confirmacion explicita.
   * Contrato: escribe fuera del repo en C:\xampp\panel_db_backups y nunca devuelve password.
   */
  public function generarRespaldoLocal($alcance, $autorizar, $confirmacion, $idUsuario = 0) {
    $autorizar = trim((string) $autorizar);
    $confirmacion = trim((string) $confirmacion);
    $confirmacionOk = stripos($confirmacion, "AUTORIZO GENERAR RESPALDO MIGRACIONES BD") !== false
      && stripos($confirmacion, defined("MYSQLBASE") ? MYSQLBASE : "") !== false;
    if ($autorizar !== "MIGRACIONES_BD_RESPALDO" || !$confirmacionOk) {
      return $this->respuesta(true, "warning", "No se puede generar respaldo sin token y confirmacion literal", array(
        "token_ok" => $autorizar === "MIGRACIONES_BD_RESPALDO",
        "confirmacion_ok" => $confirmacionOk,
        "base" => defined("MYSQLBASE") ? MYSQLBASE : ""
      ));
    }

    $directorio = $this->directorioRespaldos();
    $repo = realpath(__DIR__ . "/../..");
    $realDirectorio = file_exists($directorio) ? realpath($directorio) : false;
    if ($realDirectorio && $repo && stripos($realDirectorio, $repo) === 0) {
      return $this->respuesta(true, "danger", "La ruta de respaldo no puede estar dentro del proyecto", array(
        "directorio" => $realDirectorio
      ));
    }
    if (!$realDirectorio && !mkdir($directorio, 0775, true)) {
      return $this->respuesta(true, "danger", "No fue posible crear el directorio de respaldos", array(
        "directorio" => $directorio
      ));
    }

    $mysqldump = $this->rutaMysqldump();
    if (!file_exists($mysqldump)) {
      return $this->respuesta(true, "warning", "No se encontro mysqldump en la ruta configurada", array(
        "mysqldump" => $mysqldump
      ));
    }

    $archivo = rtrim($directorio, "\\/") . DIRECTORY_SEPARATOR . $this->nombreRespaldoSugerido($alcance === "" ? "migracion_bd" : $alcance);
    $args = array(
      $mysqldump,
      "--host=" . MYSQLHOST,
      "--port=" . MYSQLPORT,
      "--user=" . MYSQLUSER,
      "--single-transaction",
      "--routines",
      "--events",
      "--triggers",
      "--default-character-set=utf8mb4",
      "--result-file=" . $archivo,
      MYSQLBASE
    );
    if (defined("MYSQLPASS") && MYSQLPASS !== "") {
      array_splice($args, 4, 0, array("--password=" . MYSQLPASS));
    }

    $inicio = microtime(true);
    $descriptor = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w")
    );
    $proceso = proc_open($args, $descriptor, $pipes);
    if (!is_resource($proceso)) {
      return $this->respuesta(true, "danger", "No fue posible iniciar mysqldump");
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $codigo = proc_close($proceso);

    $existe = file_exists($archivo);
    $tamano = $existe ? filesize($archivo) : 0;
    $ok = $codigo === 0 && $existe && $tamano > 0;
    if (!$ok && $existe && $tamano === 0) {
      @unlink($archivo);
    }

    return $this->respuesta(!$ok, $ok ? "success" : "danger", $ok ? "Respaldo generado" : "No fue posible generar respaldo", array(
      "ok" => $ok,
      "archivo" => $archivo,
      "tamano_bytes" => $tamano,
      "sha256" => $ok ? hash_file("sha256", $archivo) : null,
      "duracion_segundos" => round(microtime(true) - $inicio, 3),
      "codigo_salida" => $codigo,
      "stdout" => trim((string) $stdout),
      "stderr" => trim((string) $stderr),
      "id_usuario" => $idUsuario,
      "comando_saneado" => $this->comandoMysqldumpSaneado($archivo)
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-19
   * Proposito: generar respaldo completo de un ambiente configurado para activacion inicial.
   * Impacto: Migraciones BD; ejecuta mysqldump sobre local o destino, no modifica ninguna BD.
   * Contrato: exige token y confirmacion literal; no devuelve passwords.
   */
  public function generarRespaldoAmbienteCompleto($alias, $autorizar, $confirmacion, $idUsuario = 0) {
    $alias = trim((string) $alias);
    $autorizar = trim((string) $autorizar);
    $confirmacion = trim((string) $confirmacion);
    $ambiente = $this->ambienteConexionConfig($alias);
    if (!$ambiente) {
      return $this->respuesta(true, "warning", "Ambiente no configurado para respaldo", array("alias" => $alias));
    }

    $tokenOk = $autorizar === "MIGRACIONES_BD_RESPALDO_COMPLETO";
    $confirmacionOk = stripos($confirmacion, "AUTORIZO GENERAR RESPALDO COMPLETO MIGRACIONES BD") !== false
      && stripos($confirmacion, "ambiente " . $alias) !== false;
    if (!$tokenOk || !$confirmacionOk) {
      return $this->respuesta(true, "warning", "No se puede generar respaldo completo sin token y confirmacion literal", array(
        "alias" => $alias,
        "token_ok" => $tokenOk,
        "confirmacion_ok" => $confirmacionOk,
        "ambiente" => $this->sanearAmbiente($ambiente)
      ));
    }

    $directorio = $this->directorioRespaldos();
    $repo = realpath(__DIR__ . "/../..");
    $realDirectorio = file_exists($directorio) ? realpath($directorio) : false;
    if ($realDirectorio && $repo && stripos($realDirectorio, $repo) === 0) {
      return $this->respuesta(true, "danger", "La ruta de respaldo no puede estar dentro del proyecto", array("directorio" => $realDirectorio));
    }
    if (!$realDirectorio && !mkdir($directorio, 0775, true)) {
      return $this->respuesta(true, "danger", "No fue posible crear el directorio de respaldos", array("directorio" => $directorio));
    }

    $mysqldump = $this->rutaMysqldump();
    if (!file_exists($mysqldump)) {
      return $this->respuesta(true, "warning", "No se encontro mysqldump en la ruta configurada", array("mysqldump" => $mysqldump));
    }

    $archivo = rtrim($directorio, "\\/") . DIRECTORY_SEPARATOR . $this->nombreRespaldoAmbiente($alias, $ambiente["base"], "promocion_completa");
    $args = array(
      $mysqldump,
      "--host=" . $ambiente["host"],
      "--port=" . $ambiente["port"],
      "--user=" . $ambiente["usuario"],
      "--single-transaction",
      "--routines",
      "--events",
      "--triggers",
      "--add-drop-table",
      "--default-character-set=utf8mb4",
      "--result-file=" . $archivo,
      $ambiente["base"]
    );
    if ($ambiente["password"] !== "") {
      array_splice($args, 4, 0, array("--password=" . $ambiente["password"]));
    }

    $inicio = microtime(true);
    $descriptor = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w")
    );
    $proceso = proc_open($args, $descriptor, $pipes);
    if (!is_resource($proceso)) {
      return $this->respuesta(true, "danger", "No fue posible iniciar mysqldump");
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $codigo = proc_close($proceso);

    $existe = file_exists($archivo);
    $tamano = $existe ? filesize($archivo) : 0;
    $ok = $codigo === 0 && $existe && $tamano > 0;
    if (!$ok && $existe && $tamano === 0) {
      @unlink($archivo);
    }

    return $this->respuesta(!$ok, $ok ? "success" : "danger", $ok ? "Respaldo completo generado" : "No fue posible generar respaldo completo", array(
      "ok" => $ok,
      "alias" => $alias,
      "ambiente" => $this->sanearAmbiente($ambiente),
      "archivo" => $archivo,
      "tamano_bytes" => $tamano,
      "sha256" => $ok ? hash_file("sha256", $archivo) : null,
      "duracion_segundos" => round(microtime(true) - $inicio, 3),
      "codigo_salida" => $codigo,
      "stdout" => trim((string) $stdout),
      "stderr" => trim((string) $stderr),
      "id_usuario" => $idUsuario,
      "comando_saneado" => $this->comandoMysqldumpAmbienteSaneado($ambiente, $archivo)
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-02
   * Proposito: listar respaldos SQL disponibles en la ruta estandar.
   * Impacto: Migraciones BD; solo lectura de archivos .sql fuera del repo.
   * Contrato: no crea directorios ni calcula hash salvo solicitud explicita.
   */
  public function listarRespaldos($limite = 50, $incluirHash = false) {
    $directorio = $this->directorioRespaldos();
    $repo = realpath(__DIR__ . "/../..");
    $realDirectorio = is_dir($directorio) ? realpath($directorio) : false;
    if (!$realDirectorio) {
      return $this->respuesta(true, "warning", "La carpeta de respaldos no existe", array(
        "directorio" => $directorio,
        "respaldos" => array()
      ));
    }
    if ($repo && stripos($realDirectorio, $repo) === 0) {
      return $this->respuesta(true, "danger", "La carpeta de respaldos no puede estar dentro del proyecto", array(
        "directorio" => $realDirectorio,
        "respaldos" => array()
      ));
    }

    $limite = max(1, min(200, intval($limite)));
    $archivos = glob(rtrim($realDirectorio, "\\/") . DIRECTORY_SEPARATOR . "*.sql");
    if (!is_array($archivos)) {
      $archivos = array();
    }
    usort($archivos, function ($a, $b) {
      return filemtime($b) <=> filemtime($a);
    });

    $respaldos = array();
    foreach (array_slice($archivos, 0, $limite) as $archivo) {
      $real = realpath($archivo);
      if (!$real || !is_file($real)) {
        continue;
      }
      $tamano = filesize($real);
      $respaldos[] = array(
        "archivo" => $real,
        "nombre" => basename($real),
        "tamano_bytes" => $tamano,
        "tamano_mb" => round($tamano / 1048576, 3),
        "fecha_modificacion" => date("Y-m-d H:i:s", filemtime($real)),
        "legible" => is_readable($real),
        "sha256" => $incluirHash && is_readable($real) ? hash_file("sha256", $real) : null
      );
    }

    return $this->respuesta(false, "success", "Respaldos consultados", array(
      "directorio" => $realDirectorio,
      "total" => count($respaldos),
      "hash_incluido" => (bool) $incluirHash,
      "respaldos" => $respaldos
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-31
   * Proposito: preparar checklist y comandos sugeridos para activar esquema tecnico del modulo.
   * Impacto: Migraciones BD; no ejecuta DDL ni crea respaldos.
   * Contrato: devuelve preflight, comando mysqldump sugerido y texto de autorizacion.
   */
  public function preflightActivacion($respaldo = "") {
    $esquema = $this->estadoEsquemaTecnico();
    $validacionRespaldo = $this->validarRespaldo($respaldo);
    $planEsquema = null;
    if (!class_exists("DBSchema") && file_exists(__DIR__ . "/../core/DBSchema.php")) {
      require_once __DIR__ . "/../core/DBSchema.php";
    }
    if (!class_exists("MigracionesBdEsquema") && file_exists(__DIR__ . "/MigracionesBdEsquema.php")) {
      require_once __DIR__ . "/MigracionesBdEsquema.php";
    }
    if (class_exists("MigracionesBdEsquema")) {
      try {
        $modeloEsquema = new MigracionesBdEsquema();
        $planEsquema = $modeloEsquema->planActualizarMigracionesBd(false);
      } catch (Exception $e) {
        $planEsquema = $this->respuesta(true, "warning", $e->getMessage());
      }
    }
    $nombreSugerido = $this->nombreRespaldoSugerido("migracion_bd_schema");
    $rutaSugerida = $this->directorioRespaldos() . "\\" . $nombreSugerido;
    $comandoRespaldo = $this->comandoMysqldumpSaneado($rutaSugerida);
    $comandoAplicacion = "POST /migracionBd/esquema_actualizar con ejecutar=1";
    $textoAutorizacionRespaldo = "AUTORIZO GENERAR RESPALDO MIGRACIONES BD de base " . (defined("MYSQLBASE") ? MYSQLBASE : "") . " para activar esquema tecnico sys_migraciones_*. Entiendo que genera un archivo SQL externo al proyecto y no modifica la base.";
    $textoAutorizacion = "AUTORIZO CREAR ESQUEMA TECNICO MIGRACIONES BD usando respaldo [RUTA_RESPALDO] con alcance exclusivo sys_migraciones_*. Entiendo que solo crea tablas tecnicas de preparacion, politicas, paquetes y ejecuciones; no migra catalogo, proveedores, compras, ventas, inventario, clientes, usuarios operativos ni productivo.";

    return $this->respuesta(false, "success", "Preflight de activacion generado", array(
      "esquema_tecnico" => $esquema,
      "plan_esquema" => $planEsquema ? $planEsquema["depurar"] : null,
      "respaldo" => $validacionRespaldo["depurar"],
      "puede_aplicar" => !empty($validacionRespaldo["depurar"]["ok"]),
      "ruta_respaldo_sugerida" => $rutaSugerida,
      "comando_respaldo_sugerido" => $comandoRespaldo,
      "comando_aplicacion" => $comandoAplicacion,
      "token_respaldo" => "MIGRACIONES_BD_RESPALDO",
      "texto_autorizacion_respaldo" => $textoAutorizacionRespaldo,
      "token" => "MIGRACIONES_BD_SCHEMA",
      "texto_autorizacion" => $textoAutorizacion
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-04
   * Proposito: verificar estado post-aplicacion del esquema tecnico sys_migraciones_*.
   * Impacto: Migraciones BD; solo lectura, no ejecuta DDL.
   * Contrato: devuelve tablas existentes/faltantes y plan dry-run restante.
   */
  public function verificarEsquemaTecnicoMigraciones() {
    $esquema = $this->estadoEsquemaTecnico();
    $planEsquema = null;
    if (!class_exists("DBSchema") && file_exists(__DIR__ . "/../core/DBSchema.php")) {
      require_once __DIR__ . "/../core/DBSchema.php";
    }
    if (!class_exists("MigracionesBdEsquema") && file_exists(__DIR__ . "/MigracionesBdEsquema.php")) {
      require_once __DIR__ . "/MigracionesBdEsquema.php";
    }
    if (class_exists("MigracionesBdEsquema")) {
      try {
        $modeloEsquema = new MigracionesBdEsquema();
        $plan = $modeloEsquema->planActualizarMigracionesBd(false);
        $planEsquema = $plan["depurar"];
      } catch (Exception $e) {
        $planEsquema = array(
          "resumen" => array("errores" => 1),
          "error" => $e->getMessage()
        );
      }
    }

    $resumen = isset($planEsquema["resumen"]) ? $planEsquema["resumen"] : array();
    $listo = !empty($esquema["listo"]) && empty($resumen["pendientes"]) && empty($resumen["errores"]);

    return $this->respuesta(false, $listo ? "success" : "warning", $listo ? "Esquema tecnico listo" : "Esquema tecnico pendiente", array(
      "listo" => $listo,
      "esquema_tecnico" => $esquema,
      "plan_esquema" => $planEsquema,
      "siguiente_paso" => $listo
        ? "Ya puedes guardar politicas y persistir paquetes dry-run."
        : "Aplicar esquema tecnico desde la UI con respaldo valido, token y confirmacion literal."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-03
   * Proposito: validar compuertas finales para aplicar esquema tecnico sin ejecutar DDL.
   * Impacto: Migraciones BD; solo lectura, prepara decision antes de `esquema_actualizar`.
   * Contrato: valida respaldo, token, confirmacion literal y plan sys_migraciones_*.
   */
  public function preflightEsquemaTecnicoFinal($respaldo, $autorizar, $confirmacion) {
    $activacion = $this->preflightActivacion($respaldo);
    $d = $activacion["depurar"];
    $plan = isset($d["plan_esquema"]) && is_array($d["plan_esquema"]) ? $d["plan_esquema"] : array();
    $resumen = isset($plan["resumen"]) && is_array($plan["resumen"]) ? $plan["resumen"] : array();
    $respaldoOk = !empty($d["respaldo"]["ok"]);
    $tokenOk = trim((string) $autorizar) === "MIGRACIONES_BD_SCHEMA";
    $confirmacionOk = stripos((string) $confirmacion, "AUTORIZO CREAR ESQUEMA TECNICO MIGRACIONES BD") !== false
      && stripos((string) $confirmacion, "sys_migraciones_") !== false;
    $planSinErrores = empty($resumen["errores"]);
    $hayPendientes = !empty($resumen["pendientes"]);
    $esquemaListo = !empty($d["esquema_tecnico"]["listo"]);

    $bloqueos = array();
    if (!$respaldoOk) {
      $bloqueos[] = "respaldo_no_valido";
    }
    if (!$tokenOk) {
      $bloqueos[] = "token_schema_invalido";
    }
    if (!$confirmacionOk) {
      $bloqueos[] = "confirmacion_schema_invalida";
    }
    if (!$planSinErrores) {
      $bloqueos[] = "plan_schema_con_errores";
    }

    $advertencias = array();
    if (!$hayPendientes && $esquemaListo) {
      $advertencias[] = "esquema_tecnico_ya_listo";
    }

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", "Preflight final de esquema tecnico generado", array(
      "puede_aplicar" => empty($bloqueos) && $hayPendientes,
      "bloqueos" => $bloqueos,
      "advertencias" => $advertencias,
      "token_ok" => $tokenOk,
      "confirmacion_ok" => $confirmacionOk,
      "respaldo_ok" => $respaldoOk,
      "plan_sin_errores" => $planSinErrores,
      "hay_pendientes" => $hayPendientes,
      "esquema_listo" => $esquemaListo,
      "activacion" => $d,
      "siguiente_paso" => empty($bloqueos)
        ? ($hayPendientes ? "Puede solicitar Aplicar esquema tecnico con la misma ruta, token y confirmacion." : "El esquema tecnico ya no tiene acciones pendientes.")
        : "Resolver bloqueos antes de solicitar Aplicar esquema tecnico."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: preparar plan de restauracion desde respaldo sin ejecutarlo.
   * Impacto: Migraciones BD; solo lectura, no invoca mysql ni modifica BD.
   * Contrato: valida respaldo y devuelve comando saneado para ventana de recuperacion.
   */
  public function preflightRestauracion($respaldo = "") {
    $validacionRespaldo = $this->validarRespaldo($respaldo);
    $mysql = $this->rutaMysqlCliente();
    $base = defined("MYSQLBASE") ? MYSQLBASE : "";
    $comando = $this->comandoRestoreSaneado($respaldo);
    $textoAutorizacion = "AUTORIZO RESTAURAR RESPALDO MIGRACIONES BD sobre base " . $base . " usando respaldo [RUTA_RESPALDO]. Entiendo que esto reemplaza/afecta datos y esquema de la base destino y solo debe ejecutarse en una ventana de recuperacion autorizada.";

    return $this->respuesta(false, "success", "Preflight de restauracion generado", array(
      "respaldo" => $validacionRespaldo["depurar"],
      "puede_restaurar" => !empty($validacionRespaldo["depurar"]["ok"]) && file_exists($mysql),
      "mysql_cliente" => $mysql,
      "mysql_disponible" => file_exists($mysql) && is_readable($mysql),
      "base_destino" => $base,
      "comando_restore_saneado" => $comando,
      "token" => "MIGRACIONES_BD_RESTORE",
      "texto_autorizacion" => $textoAutorizacion,
      "nota" => "Este modulo solo prepara el plan de restauracion. La restauracion real debe hacerse fuera del flujo normal, con autorizacion explicita y el sistema en ventana de recuperacion."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-19
   * Proposito: preparar la activacion inicial donde local reemplaza por completo a productivo.
   * Impacto: Migraciones BD; solo lectura, no genera respaldos ni restaura.
   * Contrato: devuelve runbook, compuertas, respaldos requeridos y diferencias actuales.
   */
  public function preflightPromocionCompleta($aliasDestino = "productivo", $respaldoLocal = "", $respaldoDestino = "") {
    $aliasDestino = trim((string) $aliasDestino);
    if ($aliasDestino === "" || $aliasDestino === "local") {
      return $this->respuesta(true, "warning", "Indica un destino productivo distinto a local", array("destino" => $aliasDestino));
    }

    $local = $this->probarAmbiente("local");
    $destino = $this->probarAmbiente($aliasDestino);
    $comparacion = (!$local["error"] && !$destino["error"]) ? $this->compararAmbientes($aliasDestino) : null;
    $validacionLocal = $this->validarRespaldo($respaldoLocal);
    $validacionDestino = $this->validarRespaldo($respaldoDestino);
    $ambienteLocal = $this->ambienteConexionConfig("local");
    $ambienteDestino = $this->ambienteConexionConfig($aliasDestino);

    $bloqueos = array();
    $advertencias = array();
    if ($local["error"]) {
      $bloqueos[] = "local_no_conecta";
    }
    if ($destino["error"]) {
      $bloqueos[] = "destino_no_conecta";
    }
    if (empty($validacionLocal["depurar"]["ok"])) {
      $advertencias[] = "respaldo_local_pendiente";
    }
    if (empty($validacionDestino["depurar"]["ok"])) {
      $advertencias[] = "respaldo_productivo_pendiente";
    }
    if (!$this->promocionCompletaHabilitada()) {
      $advertencias[] = "promocion_completa_deshabilitada";
    }

    $tablasSoloDestino = array();
    $resumenComparacion = array();
    if ($comparacion && !$comparacion["error"]) {
      $c = $comparacion["depurar"]["comparacion"];
      $resumenComparacion = isset($c["resumen"]) ? $c["resumen"] : array();
      foreach ($c["tablas_solo_destino"] as $tabla) {
        $tablasSoloDestino[] = $tabla["tabla"];
      }
      if (!empty($tablasSoloDestino)) {
        $advertencias[] = "productivo_tiene_tablas_no_presentes_en_local";
      }
    } elseif ($comparacion && $comparacion["error"]) {
      $bloqueos[] = "comparacion_no_disponible";
    }

    $rutaLocalSugerida = $ambienteLocal ? $this->directorioRespaldos() . "\\" . $this->nombreRespaldoAmbiente("local", $ambienteLocal["base"], "promocion_completa") : "";
    $rutaDestinoSugerida = $ambienteDestino ? $this->directorioRespaldos() . "\\" . $this->nombreRespaldoAmbiente($aliasDestino, $ambienteDestino["base"], "antes_reemplazo") : "";

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", "Preflight de promocion completa generado", array(
      "modo" => "reemplazo_completo_local_a_productivo",
      "destino" => $aliasDestino,
      "local" => $local["error"] ? $local : $local["depurar"],
      "productivo" => $destino["error"] ? $destino : $destino["depurar"],
      "comparacion_resumen" => $resumenComparacion,
      "tablas_solo_productivo" => $tablasSoloDestino,
      "respaldo_local" => $validacionLocal["depurar"],
      "respaldo_productivo" => $validacionDestino["depurar"],
      "ruta_respaldo_local_sugerida" => $rutaLocalSugerida,
      "ruta_respaldo_productivo_sugerida" => $rutaDestinoSugerida,
      "token_respaldo" => "MIGRACIONES_BD_RESPALDO_COMPLETO",
      "confirmacion_respaldo_local" => "AUTORIZO GENERAR RESPALDO COMPLETO MIGRACIONES BD del ambiente local para promocion completa. Entiendo que no modifica ninguna base.",
      "confirmacion_respaldo_productivo" => "AUTORIZO GENERAR RESPALDO COMPLETO MIGRACIONES BD del ambiente " . $aliasDestino . " antes de reemplazo. Entiendo que no modifica ninguna base.",
      "token_reemplazo" => "MIGRACIONES_BD_REEMPLAZO_COMPLETO",
      "confirmacion_reemplazo" => "AUTORIZO REEMPLAZAR PRODUCTIVO CON BASE LOCAL usando respaldo local [RESPALDO_LOCAL] y respaldo productivo [RESPALDO_PRODUCTIVO]. Entiendo que productivo quedara con esquema y datos de local.",
      "promocion_completa_habilitada" => $this->promocionCompletaHabilitada(),
      "bloqueos" => $bloqueos,
      "advertencias" => array_values(array_unique($advertencias)),
      "puede_generar_respaldos" => empty($bloqueos),
      "puede_reemplazar" => false,
      "runbook" => array(
        "1. Confirmar que productivo aun no tiene operacion real que conservar.",
        "2. Generar respaldo completo de productivo.",
        "3. Generar respaldo completo de local.",
        "4. Validar ambos respaldos por tamano, lectura y hash.",
        "5. Activar ventana de mantenimiento en productivo.",
        "6. Restaurar el dump local sobre productivo con autorizacion literal.",
        "7. Verificar tablas, usuarios, permisos, catalogo, proveedores y configuraciones.",
        "8. Conservar el respaldo productivo para rollback."
      ),
      "nota" => "Este preflight no ejecuta restauracion. El reemplazo completo se mantiene bloqueado hasta una autorizacion final separada."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: validar si un paquete persistido esta listo para aplicacion controlada.
   * Impacto: Migraciones BD; no ejecuta SQL, solo revisa paquete, respaldo y compuertas.
   * Contrato: requiere esquema tecnico existente y codigo/id de paquete persistido.
   */
  public function preflightPaqueteAplicacion($codigo, $respaldo = "") {
    $paquete = $this->consultarPaquetePersistido($codigo);
    $validacionRespaldo = $this->validarRespaldo($respaldo);
    $esquema = $this->estadoEsquemaTecnico();
    $aplicacionHabilitada = $this->aplicacionRealHabilitada();

    if ($paquete["error"]) {
      return $this->respuesta(true, "warning", $paquete["mensaje"], array(
        "paquete" => $paquete["depurar"],
        "respaldo" => $validacionRespaldo["depurar"],
        "esquema_tecnico" => $esquema,
        "aplicacion_real_habilitada" => $aplicacionHabilitada
      ));
    }

    $depurarPaquete = $paquete["depurar"];
    $riesgos = $this->riesgosPaquete($depurarPaquete);
    $vigencia = $this->validarVigenciaPaquete($depurarPaquete);
    $estatusAutorizado = isset($depurarPaquete["paquete"]["estatus"]) && $depurarPaquete["paquete"]["estatus"] === "autorizado";
    $puedePreparar = !empty($esquema["listo"]) && !empty($validacionRespaldo["depurar"]["ok"]) && empty($riesgos["bloqueantes"]) && !empty($vigencia["ok"]);
    $puedeAplicar = $puedePreparar && $aplicacionHabilitada && $estatusAutorizado;

    return $this->respuesta(false, "success", "Preflight de paquete generado", array(
      "paquete" => $depurarPaquete["paquete"],
      "sentencias" => $depurarPaquete["sentencias"],
      "tablas" => $depurarPaquete["tablas"],
      "respaldo" => $validacionRespaldo["depurar"],
      "esquema_tecnico" => $esquema,
      "riesgos" => $riesgos,
      "vigencia" => $vigencia,
      "aplicacion_real_habilitada" => $aplicacionHabilitada,
      "puede_preparar" => $puedePreparar,
      "puede_aplicar" => $puedeAplicar,
      "estatus_autorizado" => $estatusAutorizado,
      "token_autorizacion" => "MIGRACIONES_BD_AUTORIZAR",
      "token_aplicacion" => "MIGRACIONES_BD_APLICAR",
      "texto_autorizacion_paquete" => "AUTORIZO PAQUETE MIGRACIONES BD " . $depurarPaquete["paquete"]["codigo"] . " hacia " . $depurarPaquete["paquete"]["ambiente_destino"] . " usando respaldo [RUTA_RESPALDO]. Entiendo que este paso solo autoriza el paquete y no ejecuta SQL.",
      "texto_autorizacion" => "AUTORIZO APLICAR PAQUETE MIGRACIONES BD " . $depurarPaquete["paquete"]["codigo"] . " hacia " . $depurarPaquete["paquete"]["ambiente_destino"] . " usando respaldo [RUTA_RESPALDO]. Entiendo que ejecuta el SQL persistido del paquete y registra bitacora."
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-03
   * Proposito: consolidar semaforo final antes de preparar, autorizar o aplicar.
   * Impacto: Migraciones BD; solo lectura, no ejecuta SQL ni cambia estatus.
   * Contrato: evalua respaldo, selfcheck, esquema tecnico y paquete opcional.
   */
  public function preflightFinalSemaforo($codigo = "", $respaldo = "") {
    $codigo = trim((string) $codigo);
    $selfcheck = $this->selfcheckOperativo();
    $validacionRespaldo = $this->validarRespaldo($respaldo);
    $esquema = $this->estadoEsquemaTecnico();
    $aplicacionHabilitada = $this->aplicacionRealHabilitada();

    $bloqueos = array();
    $advertencias = array();
    $pasos = array();

    $selfBloqueantes = isset($selfcheck["depurar"]["bloqueantes"]) ? $selfcheck["depurar"]["bloqueantes"] : array();
    $selfAdvertencias = isset($selfcheck["depurar"]["advertencias"]) ? $selfcheck["depurar"]["advertencias"] : array();
    foreach ($selfBloqueantes as $bloqueo) {
      $bloqueos[] = "selfcheck_" . $bloqueo;
    }
    foreach ($selfAdvertencias as $advertencia) {
      $advertencias[] = "selfcheck_" . $advertencia;
    }

    $pasos[] = $this->semaforoPaso("selfcheck", "Selfcheck operativo", empty($selfBloqueantes), empty($selfBloqueantes) ? "Entorno base sin bloqueantes." : "Resolver bloqueantes del selfcheck.", empty($selfBloqueantes) ? "success" : "danger");
    $pasos[] = $this->semaforoPaso("respaldo", "Respaldo valido", !empty($validacionRespaldo["depurar"]["ok"]), !empty($validacionRespaldo["depurar"]["ok"]) ? "Respaldo externo valido." : "Seleccionar o generar respaldo .sql valido.", !empty($validacionRespaldo["depurar"]["ok"]) ? "success" : "warning");
    $pasos[] = $this->semaforoPaso("esquema_tecnico", "Esquema tecnico", !empty($esquema["listo"]), !empty($esquema["listo"]) ? "Tablas sys_migraciones_* listas." : "Activar esquema tecnico antes de persistir politicas o paquetes.", !empty($esquema["listo"]) ? "success" : "warning");

    if (empty($validacionRespaldo["depurar"]["ok"])) {
      $advertencias[] = "respaldo_pendiente";
    }
    if (empty($esquema["listo"])) {
      $advertencias[] = "esquema_tecnico_pendiente";
    }

    $paquetePreflight = null;
    if ($codigo !== "") {
      $paquetePreflight = $this->preflightPaqueteAplicacion($codigo, $respaldo);
      if ($paquetePreflight["error"]) {
        $bloqueos[] = "paquete_no_consultable";
        $pasos[] = $this->semaforoPaso("paquete", "Paquete", false, $paquetePreflight["mensaje"], "danger");
      } else {
        $p = $paquetePreflight["depurar"];
        foreach ($p["riesgos"]["bloqueantes"] as $bloqueo) {
          $bloqueos[] = $bloqueo;
        }
        foreach ($p["riesgos"]["advertencias"] as $advertencia) {
          $advertencias[] = $advertencia;
        }
        $pasos[] = $this->semaforoPaso("paquete_vigente", "Paquete vigente", !empty($p["vigencia"]["ok"]), $p["vigencia"]["mensaje"], !empty($p["vigencia"]["ok"]) ? "success" : "danger");
        $pasos[] = $this->semaforoPaso("paquete_autorizado", "Paquete autorizado", !empty($p["estatus_autorizado"]), !empty($p["estatus_autorizado"]) ? "Listo para aplicacion si las demas compuertas pasan." : "Falta autorizacion de paquete.", !empty($p["estatus_autorizado"]) ? "success" : "warning");
        $pasos[] = $this->semaforoPaso("aplicacion_real", "Aplicacion real", !empty($p["aplicacion_real_habilitada"]), !empty($p["aplicacion_real_habilitada"]) ? "Bandera local habilitada." : "Bandera local apagada por seguridad.", !empty($p["aplicacion_real_habilitada"]) ? "success" : "info");
      }
    } else {
      $advertencias[] = "paquete_no_indicado";
      $pasos[] = $this->semaforoPaso("paquete", "Paquete", false, "Indicar paquete para evaluar autorizacion o aplicacion.", "warning");
    }

    $bloqueos = array_values(array_unique($bloqueos));
    $advertencias = array_values(array_unique($advertencias));
    $puedePreparar = empty($selfBloqueantes) && !empty($validacionRespaldo["depurar"]["ok"]) && !empty($esquema["listo"]);
    $puedeAutorizar = $puedePreparar && $paquetePreflight && !$paquetePreflight["error"] && empty($paquetePreflight["depurar"]["riesgos"]["bloqueantes"]) && !empty($paquetePreflight["depurar"]["vigencia"]["ok"]);
    $puedeAplicar = $puedeAutorizar && !empty($paquetePreflight["depurar"]["estatus_autorizado"]) && !empty($aplicacionHabilitada);

    $estado = "bloqueado";
    $siguiente = "Resolver bloqueos antes de continuar.";
    if (empty($bloqueos)) {
      if ($puedeAplicar) {
        $estado = "puede_aplicar";
        $siguiente = "Solo continuar dentro de ventana autorizada, con token y confirmacion literal.";
      } elseif ($puedeAutorizar) {
        $estado = "puede_autorizar";
        $siguiente = "Autorizar paquete con respaldo valido; aun no ejecuta SQL.";
      } elseif ($puedePreparar) {
        $estado = "puede_preparar";
        $siguiente = "Crear o revisar paquete dry-run persistido.";
      } else {
        $estado = "pendiente";
        $siguiente = "Completar respaldo, esquema tecnico o paquete.";
      }
    }

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", "Semaforo final generado", array(
      "estado" => $estado,
      "puede_preparar" => $puedePreparar,
      "puede_autorizar" => $puedeAutorizar,
      "puede_aplicar" => $puedeAplicar,
      "bloqueos" => $bloqueos,
      "advertencias" => $advertencias,
      "pasos" => $pasos,
      "selfcheck" => $selfcheck["depurar"],
      "respaldo" => $validacionRespaldo["depurar"],
      "esquema_tecnico" => $esquema,
      "paquete" => $paquetePreflight ? $paquetePreflight["depurar"] : null,
      "aplicacion_real_habilitada" => $aplicacionHabilitada,
      "siguiente_paso" => $siguiente
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: preparar o ejecutar aplicacion controlada de SQL persistido en un paquete.
   * Impacto: Migraciones BD; ejecucion real queda bloqueada por respaldo, token, confirmacion y bandera local.
   * Contrato: `$ejecutar=false` solo simula; `$ejecutar=true` intenta ejecutar contra destino configurado si todas las compuertas pasan.
   */
  public function aplicarPaqueteControlado($codigo, $respaldo, $autorizar, $confirmacion, $idUsuario = 0, $ejecutar = false) {
    $preflight = $this->preflightPaqueteAplicacion($codigo, $respaldo);
    if ($preflight["error"]) {
      return $preflight;
    }

    $d = $preflight["depurar"];
    $codigoPaquete = $d["paquete"]["codigo"];
    $destinoAlias = $d["paquete"]["ambiente_destino"];
    $confirmacionOk = stripos($confirmacion, "AUTORIZO APLICAR PAQUETE MIGRACIONES BD") !== false
      && stripos($confirmacion, $codigoPaquete) !== false
      && stripos($confirmacion, $destinoAlias) !== false;
    $tokenOk = trim((string) $autorizar) === "MIGRACIONES_BD_APLICAR";

    if (!$ejecutar) {
      return $this->respuesta(false, "info", "Simulacion de aplicacion generada; no se ejecuto SQL", array(
        "preflight" => $d,
        "token_ok" => $tokenOk,
        "confirmacion_ok" => $confirmacionOk,
        "ejecutar" => false
      ));
    }

    if (!$tokenOk || !$confirmacionOk || empty($d["puede_aplicar"])) {
      return $this->respuesta(true, "warning", "Aplicacion bloqueada: faltan compuertas de seguridad", array(
        "token_ok" => $tokenOk,
        "confirmacion_ok" => $confirmacionOk,
        "puede_aplicar" => !empty($d["puede_aplicar"]),
        "aplicacion_real_habilitada" => !empty($d["aplicacion_real_habilitada"]),
        "respaldo_ok" => !empty($d["respaldo"]["ok"]),
        "riesgos" => $d["riesgos"]
      ));
    }

    $destino = $this->ambientePorAlias($destinoAlias);
    if (!$destino) {
      return $this->respuesta(true, "warning", "El destino del paquete ya no esta configurado", array("destino" => $destinoAlias));
    }
    $conexionDestino = $this->conectarAmbiente($destino);
    if ($conexionDestino["error"]) {
      return $conexionDestino;
    }

    return $this->ejecutarSqlPaquete($conexionDestino["depurar"]["conexion"], $d, $respaldo, $idUsuario);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-02
   * Proposito: autorizar un paquete persistido despues de revisar SQL y validar respaldo.
   * Impacto: Migraciones BD; cambia solo estatus del paquete tecnico, no ejecuta SQL del paquete.
   * Contrato: requiere token, confirmacion literal, respaldo valido y hash estable.
   */
  public function autorizarPaquete($codigo, $respaldo, $autorizar, $confirmacion, $idUsuario = 0) {
    $paquete = $this->consultarPaquetePersistido($codigo);
    $validacionRespaldo = $this->validarRespaldo($respaldo);
    if ($paquete["error"]) {
      return $paquete;
    }

    $d = $paquete["depurar"];
    $cabecera = $d["paquete"];
    $codigoPaquete = $cabecera["codigo"];
    $destinoAlias = $cabecera["ambiente_destino"];
    $tokenOk = trim((string) $autorizar) === "MIGRACIONES_BD_AUTORIZAR";
    $confirmacionOk = stripos($confirmacion, "AUTORIZO PAQUETE MIGRACIONES BD") !== false
      && stripos($confirmacion, $codigoPaquete) !== false
      && stripos($confirmacion, $destinoAlias) !== false;
    $estatusPermitido = in_array($cabecera["estatus"], array("borrador", "revisado", "autorizado"), true);

    $vigencia = $this->validarVigenciaPaquete($d);

    if (!$tokenOk || !$confirmacionOk || $validacionRespaldo["error"] || !$estatusPermitido || empty($vigencia["ok"])) {
      return $this->respuesta(true, "warning", "No se puede autorizar el paquete sin token, respaldo valido, confirmacion y estatus permitido", array(
        "token_ok" => $tokenOk,
        "confirmacion_ok" => $confirmacionOk,
        "respaldo" => $validacionRespaldo["depurar"],
        "estatus_permitido" => $estatusPermitido,
        "estatus_actual" => $cabecera["estatus"],
        "vigencia" => $vigencia
      ));
    }

    try {
      $stmt = $this->db->prepare("UPDATE sys_migraciones_paquetes
                                  SET estatus='autorizado', ruta_respaldo=:respaldo,
                                      id_usuario_autorizacion=:usuario, fecha_autorizacion=CURRENT_TIMESTAMP
                                  WHERE id_migracion_paquete=:paquete");
      $stmt->execute(array(
        ":respaldo" => $respaldo,
        ":usuario" => $idUsuario > 0 ? $idUsuario : null,
        ":paquete" => $cabecera["id_migracion_paquete"]
      ));
      return $this->respuesta(false, "success", "Paquete autorizado para aplicacion controlada", array(
        "codigo" => $codigoPaquete,
        "id_migracion_paquete" => $cabecera["id_migracion_paquete"],
        "estatus" => "autorizado",
        "respaldo" => $validacionRespaldo["depurar"],
        "id_usuario" => $idUsuario
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: listar paquetes persistidos de migracion.
   * Impacto: Migraciones BD; solo lectura sobre tablas tecnicas.
   * Contrato: devuelve advertencia si el esquema tecnico aun no existe.
   */
  public function listarPaquetes($limite = 50) {
    if (!$this->tablaTecnicaExiste("sys_migraciones_paquetes") || !$this->tablaTecnicaExiste("sys_migraciones_paquete_sql")) {
      return $this->respuesta(true, "warning", "Falta aplicar el esquema tecnico para listar paquetes", array(
        "esquema_tecnico" => $this->estadoEsquemaTecnico(),
        "paquetes" => array()
      ));
    }

    $limite = max(1, min(200, intval($limite)));
    try {
      $stmt = $this->db->query("SELECT p.id_migracion_paquete, p.codigo, p.ambiente_origen, p.ambiente_destino,
                                       p.estatus, p.hash_plan, p.ruta_respaldo, p.fecha_registro,
                                       p.fecha_autorizacion, p.fecha_aplicacion,
                                       COUNT(s.id_migracion_paquete_sql) AS total_sentencias,
                                       SUM(CASE WHEN s.riesgo='alto' THEN 1 ELSE 0 END) AS sentencias_riesgo_alto
                                FROM sys_migraciones_paquetes p
                                LEFT JOIN sys_migraciones_paquete_sql s ON s.id_migracion_paquete = p.id_migracion_paquete
                                GROUP BY p.id_migracion_paquete
                                ORDER BY p.id_migracion_paquete DESC
                                LIMIT " . $limite);
      $paquetes = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $this->respuesta(false, "success", "Paquetes consultados", array(
        "total" => count($paquetes),
        "paquetes" => $paquetes
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: listar ejecuciones registradas de migracion.
   * Impacto: Migraciones BD; solo lectura para seguimiento y auditoria operativa.
   * Contrato: devuelve advertencia si el esquema tecnico aun no existe.
   */
  public function listarEjecuciones($limite = 50) {
    if (!$this->tablaTecnicaExiste("sys_migraciones_ejecuciones") || !$this->tablaTecnicaExiste("sys_migraciones_ejecucion_detalle")) {
      return $this->respuesta(true, "warning", "Falta aplicar el esquema tecnico para listar ejecuciones", array(
        "esquema_tecnico" => $this->estadoEsquemaTecnico(),
        "ejecuciones" => array()
      ));
    }

    $limite = max(1, min(200, intval($limite)));
    try {
      $stmt = $this->db->query("SELECT e.id_migracion_ejecucion, e.id_migracion_paquete, p.codigo,
                                       e.ambiente_destino, e.estatus, e.ruta_respaldo, e.mensaje,
                                       e.id_usuario, e.fecha_inicio, e.fecha_fin,
                                       COUNT(d.id_migracion_ejecucion_detalle) AS total_detalles,
                                       SUM(CASE WHEN d.resultado='error' THEN 1 ELSE 0 END) AS detalles_error
                                FROM sys_migraciones_ejecuciones e
                                LEFT JOIN sys_migraciones_paquetes p ON p.id_migracion_paquete = e.id_migracion_paquete
                                LEFT JOIN sys_migraciones_ejecucion_detalle d ON d.id_migracion_ejecucion = e.id_migracion_ejecucion
                                GROUP BY e.id_migracion_ejecucion
                                ORDER BY e.id_migracion_ejecucion DESC
                                LIMIT " . $limite);
      $ejecuciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $this->respuesta(false, "success", "Ejecuciones consultadas", array(
        "total" => count($ejecuciones),
        "ejecuciones" => $ejecuciones
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: consultar detalle completo de un paquete persistido.
   * Impacto: Migraciones BD; solo lectura de tablas, SQL y resumen tecnico.
   * Contrato: acepta codigo o ID del paquete.
   */
  public function consultarPaquete($codigo) {
    return $this->consultarPaquetePersistido($codigo);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: consultar detalle de una ejecucion registrada.
   * Impacto: Migraciones BD; solo lectura para auditoria operativa.
   * Contrato: acepta ID numerico de ejecucion.
   */
  public function consultarEjecucion($idEjecucion) {
    if (!$this->tablaTecnicaExiste("sys_migraciones_ejecuciones") || !$this->tablaTecnicaExiste("sys_migraciones_ejecucion_detalle")) {
      return $this->respuesta(true, "warning", "Falta aplicar el esquema tecnico para consultar ejecuciones", array(
        "esquema_tecnico" => $this->estadoEsquemaTecnico()
      ));
    }

    $idEjecucion = intval($idEjecucion);
    if ($idEjecucion <= 0) {
      return $this->respuesta(true, "warning", "Indica el ID de ejecucion");
    }

    try {
      $stmt = $this->db->prepare("SELECT e.id_migracion_ejecucion, e.id_migracion_paquete, p.codigo,
                                         e.ambiente_destino, e.estatus, e.ruta_respaldo, e.mensaje,
                                         e.id_usuario, e.fecha_inicio, e.fecha_fin
                                  FROM sys_migraciones_ejecuciones e
                                  LEFT JOIN sys_migraciones_paquetes p ON p.id_migracion_paquete = e.id_migracion_paquete
                                  WHERE e.id_migracion_ejecucion=:id
                                  LIMIT 1");
      $stmt->execute(array(":id" => $idEjecucion));
      $ejecucion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$ejecucion) {
        return $this->respuesta(true, "warning", "Ejecucion no encontrada", array("id_migracion_ejecucion" => $idEjecucion));
      }

      $stmtDetalle = $this->db->prepare("SELECT id_migracion_ejecucion_detalle, orden, tabla, sql_texto, resultado, mensaje, fecha_registro
                                         FROM sys_migraciones_ejecucion_detalle
                                         WHERE id_migracion_ejecucion=:id
                                         ORDER BY orden ASC, id_migracion_ejecucion_detalle ASC");
      $stmtDetalle->execute(array(":id" => $idEjecucion));
      $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

      return $this->respuesta(false, "success", "Ejecucion consultada", array(
        "ejecucion" => $ejecucion,
        "detalles" => $detalles
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function validarVigenciaPaquete($paquete) {
    $cabecera = isset($paquete["paquete"]) ? $paquete["paquete"] : array();
    $resumenGuardado = isset($cabecera["resumen"]) && is_array($cabecera["resumen"]) ? $cabecera["resumen"] : array();
    $destino = isset($resumenGuardado["destino"]) ? $resumenGuardado["destino"] : (isset($cabecera["ambiente_destino"]) ? $cabecera["ambiente_destino"] : "");
    $tablas = isset($resumenGuardado["tablas_solicitadas"]) && is_array($resumenGuardado["tablas_solicitadas"]) ? $resumenGuardado["tablas_solicitadas"] : array();
    $hashGuardado = isset($cabecera["hash_plan"]) ? $cabecera["hash_plan"] : "";

    if ($destino === "" || $hashGuardado === "") {
      return array(
        "ok" => false,
        "mensaje" => "Paquete sin destino o hash guardado",
        "hash_guardado" => $hashGuardado,
        "hash_actual" => null
      );
    }

    $sqlDryRun = $this->generarSqlDryRun($destino);
    if ($sqlDryRun["error"]) {
      return array(
        "ok" => false,
        "mensaje" => "No fue posible recalcular dry-run actual: " . $sqlDryRun["mensaje"],
        "hash_guardado" => $hashGuardado,
        "hash_actual" => null,
        "error" => $sqlDryRun["depurar"]
      );
    }

    $sentencias = array();
    foreach ($sqlDryRun["depurar"]["sentencias"] as $sentencia) {
      if (empty($tablas) || in_array($sentencia["tabla"], $tablas, true)) {
        $sentencias[] = $sentencia;
      }
    }

    $resumenActual = $this->resumenPaqueteDryRun($destino, $tablas, $sentencias);
    $hashActual = $this->hashPlanPaquete($resumenActual, $sentencias);
    return array(
      "ok" => hash_equals((string) $hashGuardado, (string) $hashActual),
      "mensaje" => hash_equals((string) $hashGuardado, (string) $hashActual) ? "Paquete vigente" : "El dry-run actual ya no coincide con el paquete guardado",
      "hash_guardado" => $hashGuardado,
      "hash_actual" => $hashActual,
      "sentencias_guardadas" => isset($paquete["sentencias"]) ? count($paquete["sentencias"]) : null,
      "sentencias_actuales" => count($sentencias)
    );
  }

  private function resumenPaqueteDryRun($aliasDestino, $tablasSolicitadas, $sentencias) {
    $tablasSolicitadas = is_array($tablasSolicitadas) ? array_values($tablasSolicitadas) : array();
    $sentencias = is_array($sentencias) ? $sentencias : array();
    $tablasMapa = array();
    foreach ($sentencias as $sentencia) {
      if (isset($sentencia["tabla"]) && $sentencia["tabla"] !== "") {
        $tablasMapa[$sentencia["tabla"]] = true;
      }
    }
    foreach ($tablasSolicitadas as $tabla) {
      if ($tabla !== "") {
        $tablasMapa[$tabla] = true;
      }
    }

    $politicas = array();
    $riesgos = array("bajo" => 0, "medio" => 0, "alto" => 0, "bloqueante" => 0, "revision" => 0);
    $tablasIncluidas = array();
    $tablasConDatos = array();
    $bloqueos = array();

    foreach (array_keys($tablasMapa) as $tabla) {
      $politica = $this->politicaPersistida($tabla);
      $valorPolitica = isset($politica["politica"]) ? $politica["politica"] : "blocked";
      if (!isset($politicas[$valorPolitica])) {
        $politicas[$valorPolitica] = 0;
      }
      $politicas[$valorPolitica]++;
      if (!empty($politica["incluye_datos"])) {
        $llaveNatural = isset($politica["llave_natural"]) ? trim((string) $politica["llave_natural"]) : "";
        $tablasConDatos[] = array(
          "tabla" => $tabla,
          "politica" => $valorPolitica,
          "llave_natural" => $llaveNatural
        );
        if ($valorPolitica === "data_merge" && $llaveNatural === "") {
          $bloqueos[] = "tabla_" . $tabla . "_data_merge_sin_llave_natural";
        }
      }
      if (in_array($valorPolitica, array("blocked", "production_owned"), true)) {
        $bloqueos[] = "tabla_" . $tabla . "_" . $valorPolitica;
      }
      $tablasIncluidas[] = array(
        "tabla" => $tabla,
        "politica" => $valorPolitica,
        "incluye_datos" => !empty($politica["incluye_datos"]),
        "llave_natural" => isset($politica["llave_natural"]) ? $politica["llave_natural"] : "",
        "descripcion" => isset($politica["descripcion"]) ? $politica["descripcion"] : ""
      );
    }

    foreach ($sentencias as $sentencia) {
      $riesgo = isset($sentencia["riesgo"]) ? $sentencia["riesgo"] : "revision";
      if (!isset($riesgos[$riesgo])) {
        $riesgos[$riesgo] = 0;
      }
      $riesgos[$riesgo]++;
      if ($riesgo === "bloqueante") {
        $bloqueos[] = "sentencia_bloqueante_" . (isset($sentencia["tabla"]) ? $sentencia["tabla"] : "sin_tabla");
      }
    }

    usort($tablasIncluidas, function ($a, $b) {
      return strcmp($a["tabla"], $b["tabla"]);
    });

    return array(
      "origen" => "local",
      "destino" => $aliasDestino,
      "tablas_solicitadas" => $tablasSolicitadas,
      "tablas_incluidas" => $tablasIncluidas,
      "sentencias" => count($sentencias),
      "incluye_datos" => !empty($tablasConDatos),
      "tablas_con_datos" => $tablasConDatos,
      "politicas" => $politicas,
      "riesgos" => $riesgos,
      "bloqueos" => array_values(array_unique($bloqueos)),
      "fase" => "dry_run_esquema"
    );
  }

  private function hashPlanPaquete($resumen, $sentencias) {
    return hash("sha256", json_encode(array("resumen" => $resumen, "sentencias" => $sentencias)));
  }

  private function consultarPaquetePersistido($codigo) {
    if (!$this->tablaTecnicaExiste("sys_migraciones_paquetes") || !$this->tablaTecnicaExiste("sys_migraciones_paquete_sql")) {
      return $this->respuesta(true, "warning", "Falta aplicar el esquema tecnico para consultar paquetes persistidos", array(
        "codigo" => $codigo,
        "esquema_tecnico" => $this->estadoEsquemaTecnico()
      ));
    }

    $codigo = trim((string) $codigo);
    if ($codigo === "") {
      return $this->respuesta(true, "warning", "Indica el codigo o ID del paquete");
    }

    try {
      $where = ctype_digit($codigo) ? "id_migracion_paquete = :codigo" : "codigo = :codigo";
      $stmt = $this->db->prepare("SELECT id_migracion_paquete, codigo, ambiente_origen, ambiente_destino, estatus,
                                         resumen_json, hash_plan, ruta_respaldo, fecha_registro, fecha_autorizacion, fecha_aplicacion
                                  FROM sys_migraciones_paquetes
                                  WHERE " . $where . "
                                  LIMIT 1");
      $stmt->execute(array(":codigo" => $codigo));
      $paquete = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$paquete) {
        return $this->respuesta(true, "warning", "Paquete no encontrado", array("codigo" => $codigo));
      }

      $stmtSql = $this->db->prepare("SELECT id_migracion_paquete_sql, orden, tipo, tabla, politica, sql_texto, riesgo, ejecutado
                                     FROM sys_migraciones_paquete_sql
                                     WHERE id_migracion_paquete=:paquete
                                     ORDER BY orden ASC, id_migracion_paquete_sql ASC");
      $stmtSql->execute(array(":paquete" => $paquete["id_migracion_paquete"]));
      $sentencias = $stmtSql->fetchAll(PDO::FETCH_ASSOC);

      $tablas = array();
      if ($this->tablaTecnicaExiste("sys_migraciones_paquete_tablas")) {
        $stmtTablas = $this->db->prepare("SELECT tabla, politica, incluye_datos, llave_natural, resumen_json
                                          FROM sys_migraciones_paquete_tablas
                                          WHERE id_migracion_paquete=:paquete
                                          ORDER BY tabla");
        $stmtTablas->execute(array(":paquete" => $paquete["id_migracion_paquete"]));
        $tablas = $stmtTablas->fetchAll(PDO::FETCH_ASSOC);
      }

      $paquete["resumen"] = json_decode($paquete["resumen_json"], true);
      unset($paquete["resumen_json"]);
      return $this->respuesta(false, "success", "Paquete consultado", array(
        "paquete" => $paquete,
        "sentencias" => $sentencias,
        "tablas" => $tablas
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function riesgosPaquete($paquete) {
    $bloqueantes = array();
    $advertencias = array();
    $sentencias = isset($paquete["sentencias"]) ? $paquete["sentencias"] : array();
    $cabecera = isset($paquete["paquete"]) ? $paquete["paquete"] : array();
    $resumen = isset($cabecera["resumen"]) && is_array($cabecera["resumen"]) ? $cabecera["resumen"] : array();

    if (empty($sentencias)) {
      $bloqueantes[] = "paquete_sin_sql";
    }
    if (!empty($resumen["bloqueos"]) && is_array($resumen["bloqueos"])) {
      foreach ($resumen["bloqueos"] as $bloqueo) {
        $bloqueantes[] = $bloqueo;
      }
    }
    if (!empty($resumen["tablas_con_datos"])) {
      $advertencias[] = "incluye_tablas_con_datos_solicitados";
    }
    if (!isset($cabecera["estatus"]) || !in_array($cabecera["estatus"], array("borrador", "revisado", "autorizado"), true)) {
      $bloqueantes[] = "estatus_no_aplicable";
    }
    foreach ($sentencias as $sentencia) {
      if (isset($sentencia["riesgo"]) && $sentencia["riesgo"] === "bloqueante") {
        $bloqueantes[] = "incluye_sentencias_bloqueantes";
      }
      if (isset($sentencia["riesgo"]) && $sentencia["riesgo"] === "alto") {
        $advertencias[] = "incluye_sentencias_riesgo_alto";
      }
    }
    if (!isset($cabecera["ambiente_destino"]) || !$this->ambientePorAlias($cabecera["ambiente_destino"])) {
      $bloqueantes[] = "destino_no_configurado";
    }

    return array(
      "bloqueantes" => array_values(array_unique($bloqueantes)),
      "advertencias" => array_values(array_unique($advertencias))
    );
  }

  private function aplicacionRealHabilitada() {
    $config = $this->leerConfigAmbientes();
    return !empty($config["_opciones"]["aplicacion_real_habilitada"]);
  }

  private function promocionCompletaHabilitada() {
    $config = $this->leerConfigAmbientes();
    return !empty($config["_opciones"]["promocion_completa_habilitada"]);
  }

  private function ejecutarSqlPaquete($conexionDestino, $preflight, $respaldo, $idUsuario) {
    if (!$this->tablaTecnicaExiste("sys_migraciones_ejecuciones") || !$this->tablaTecnicaExiste("sys_migraciones_ejecucion_detalle")) {
      return $this->respuesta(true, "warning", "Falta esquema tecnico de bitacora de ejecuciones");
    }

    $paquete = $preflight["paquete"];
    $sentencias = $preflight["sentencias"];
    $idEjecucion = null;
    try {
      $stmtEjecucion = $this->db->prepare("INSERT INTO sys_migraciones_ejecuciones
        (id_migracion_paquete, ambiente_destino, estatus, ruta_respaldo, mensaje, id_usuario)
        VALUES (:paquete, :destino, 'iniciada', :respaldo, 'Aplicacion iniciada', :usuario)");
      $stmtEjecucion->execute(array(
        ":paquete" => $paquete["id_migracion_paquete"],
        ":destino" => $paquete["ambiente_destino"],
        ":respaldo" => $respaldo,
        ":usuario" => $idUsuario > 0 ? $idUsuario : null
      ));
      $idEjecucion = intval($this->db->lastInsertId());

      $stmtDetalle = $this->db->prepare("INSERT INTO sys_migraciones_ejecucion_detalle
        (id_migracion_ejecucion, orden, tabla, sql_texto, resultado, mensaje)
        VALUES (:ejecucion, :orden, :tabla, :sql, :resultado, :mensaje)");

      foreach ($sentencias as $sentencia) {
        try {
          $conexionDestino->exec($sentencia["sql_texto"]);
          $stmtDetalle->execute(array(
            ":ejecucion" => $idEjecucion,
            ":orden" => $sentencia["orden"],
            ":tabla" => $sentencia["tabla"],
            ":sql" => $sentencia["sql_texto"],
            ":resultado" => "ok",
            ":mensaje" => "Ejecutado"
          ));
        } catch (Exception $e) {
          $stmtDetalle->execute(array(
            ":ejecucion" => $idEjecucion,
            ":orden" => $sentencia["orden"],
            ":tabla" => $sentencia["tabla"],
            ":sql" => $sentencia["sql_texto"],
            ":resultado" => "error",
            ":mensaje" => $e->getMessage()
          ));
          $this->db->prepare("UPDATE sys_migraciones_ejecuciones SET estatus='fallida', mensaje=:mensaje, fecha_fin=CURRENT_TIMESTAMP WHERE id_migracion_ejecucion=:id")
            ->execute(array(":mensaje" => $e->getMessage(), ":id" => $idEjecucion));
          return $this->respuesta(true, "danger", "Aplicacion detenida por error", array(
            "id_migracion_ejecucion" => $idEjecucion,
            "orden" => $sentencia["orden"],
            "tabla" => $sentencia["tabla"],
            "mensaje" => $e->getMessage()
          ));
        }
      }

      $this->db->prepare("UPDATE sys_migraciones_ejecuciones SET estatus='aplicada', mensaje='Aplicacion completada', fecha_fin=CURRENT_TIMESTAMP WHERE id_migracion_ejecucion=:id")
        ->execute(array(":id" => $idEjecucion));
      $this->db->prepare("UPDATE sys_migraciones_paquetes SET estatus='aplicado', ruta_respaldo=:respaldo, fecha_aplicacion=CURRENT_TIMESTAMP WHERE id_migracion_paquete=:id")
        ->execute(array(":respaldo" => $respaldo, ":id" => $paquete["id_migracion_paquete"]));

      return $this->respuesta(false, "success", "Paquete aplicado", array(
        "id_migracion_ejecucion" => $idEjecucion,
        "sentencias" => count($sentencias)
      ));
    } catch (Exception $e) {
      if ($idEjecucion) {
        try {
          $this->db->prepare("UPDATE sys_migraciones_ejecuciones SET estatus='fallida', mensaje=:mensaje, fecha_fin=CURRENT_TIMESTAMP WHERE id_migracion_ejecucion=:id")
            ->execute(array(":mensaje" => $e->getMessage(), ":id" => $idEjecucion));
        } catch (Exception $ignorar) {
        }
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function ambientesDisponibles() {
    $ambientes = array($this->ambienteLocalSaneado());
    foreach ($this->leerConfigAmbientes() as $alias => $ambiente) {
      if (strpos((string) $alias, "_") === 0) {
        continue;
      }
      $ambiente["alias"] = $alias;
      $ambientes[] = $this->sanearAmbiente($ambiente);
    }
    return $ambientes;
  }

  private function estadoEsquemaTecnico() {
    $tablas = array(
      "sys_migraciones_ambientes",
      "sys_migraciones_tablas_politicas",
      "sys_migraciones_paquetes",
      "sys_migraciones_paquete_tablas",
      "sys_migraciones_paquete_sql",
      "sys_migraciones_ejecuciones",
      "sys_migraciones_ejecucion_detalle"
    );
    $estado = array();
    $faltantes = array();
    foreach ($tablas as $tabla) {
      $existe = $this->tablaTecnicaExiste($tabla);
      $estado[$tabla] = $existe;
      if (!$existe) {
        $faltantes[] = $tabla;
      }
    }
    return array(
      "listo" => empty($faltantes),
      "tablas" => $estado,
      "faltantes" => $faltantes
    );
  }

  private function ambienteLocalSaneado() {
    return array(
      "alias" => "local",
      "tipo" => "local",
      "descripcion" => "Base activa por mysql.php",
      "host" => defined("MYSQLHOST") ? MYSQLHOST : "",
      "base" => defined("MYSQLBASE") ? MYSQLBASE : "",
      "usuario" => defined("MYSQLUSER") ? MYSQLUSER : "",
      "configurado" => true
    );
  }

  private function ambientePorAlias($alias) {
    $ambientes = $this->leerConfigAmbientes();
    if (strpos((string) $alias, "_") === 0) {
      return null;
    }
    return isset($ambientes[$alias]) && is_array($ambientes[$alias]) ? $ambientes[$alias] : null;
  }

  private function ambienteConexionConfig($alias) {
    $alias = trim((string) $alias);
    if ($alias === "local") {
      return array(
        "alias" => "local",
        "tipo" => "local",
        "descripcion" => "Base activa por mysql.php",
        "host" => defined("MYSQLHOST") ? MYSQLHOST : "",
        "port" => defined("MYSQLPORT") ? MYSQLPORT : "3306",
        "base" => defined("MYSQLBASE") ? MYSQLBASE : "",
        "usuario" => defined("MYSQLUSER") ? MYSQLUSER : "",
        "password" => defined("MYSQLPASS") ? MYSQLPASS : ""
      );
    }

    $ambiente = $this->ambientePorAlias($alias);
    if (!$ambiente) {
      return null;
    }
    $ambiente["alias"] = $alias;
    $ambiente["port"] = !empty($ambiente["port"]) ? $ambiente["port"] : "3306";
    $ambiente["password"] = isset($ambiente["password"]) ? $ambiente["password"] : "";
    return $ambiente;
  }

  private function valorConfigPlaceholder($valor) {
    $valor = trim((string) $valor);
    if ($valor === "") {
      return true;
    }
    $marcas = array("CAMBIAR", "base_productiva", "usuario_lectura", "usuario_migracion", "password", "contraseña", "placeholder");
    foreach ($marcas as $marca) {
      if (stripos($valor, $marca) !== false) {
        return true;
      }
    }
    return false;
  }

  private function conectarAmbiente($ambiente) {
    $host = isset($ambiente["host"]) ? $ambiente["host"] : "";
    $base = isset($ambiente["base"]) ? $ambiente["base"] : "";
    $usuario = isset($ambiente["usuario"]) ? $ambiente["usuario"] : "";
    $password = isset($ambiente["password"]) ? $ambiente["password"] : "";
    if ($host === "" || $base === "" || $usuario === "") {
      return $this->respuesta(true, "warning", "El ambiente destino no tiene host/base/usuario completos", $this->sanearAmbiente($ambiente));
    }
    $hostActivo = defined("MYSQLHOST") ? MYSQLHOST : "";
    $baseActiva = defined("MYSQLBASE") ? MYSQLBASE : "";
    $usuarioActivo = defined("MYSQLUSER") ? MYSQLUSER : "";
    if ($this->db && $base === $baseActiva && $usuario === $usuarioActivo && in_array($host, array($hostActivo, "localhost", "127.0.0.1"), true)) {
      return $this->respuesta(false, "success", "Conexion destino reutiliza la conexion local activa", array("conexion" => $this->db));
    }

    try {
      $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $base, $usuario, $password, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 8
      ));
      return $this->respuesta(false, "success", "Conexion destino disponible", array("conexion" => $pdo));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No fue posible conectar al ambiente destino", array(
        "ambiente" => $this->sanearAmbiente($ambiente),
        "mensaje" => $e->getMessage()
      ));
    }
  }

  private function snapshotTablas($conexion, $base) {
    if (!$conexion) {
      return $this->respuesta(true, "danger", "Conexion MySQL no disponible");
    }
    try {
      $stmt = $conexion->prepare("SELECT TABLE_NAME AS tabla, TABLE_ROWS AS filas_estimadas, ENGINE AS motor, TABLE_COLLATION AS collation
                                  FROM INFORMATION_SCHEMA.TABLES
                                  WHERE TABLE_SCHEMA = :base
                                  ORDER BY TABLE_NAME");
      $stmt->execute(array(":base" => $base));
      $tablas = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmtCols = $conexion->prepare("SELECT TABLE_NAME AS tabla, COLUMN_NAME AS columna, COLUMN_TYPE AS tipo,
                                             IS_NULLABLE AS permite_null, COLUMN_DEFAULT AS valor_default,
                                             COLUMN_KEY AS llave, EXTRA AS extra, ORDINAL_POSITION AS posicion
                                      FROM INFORMATION_SCHEMA.COLUMNS
                                      WHERE TABLE_SCHEMA = :base
                                      ORDER BY TABLE_NAME, ORDINAL_POSITION");
      $stmtCols->execute(array(":base" => $base));
      $columnas = $stmtCols->fetchAll(PDO::FETCH_ASSOC);

      $stmtIdx = $conexion->prepare("SELECT TABLE_NAME AS tabla, INDEX_NAME AS indice, NON_UNIQUE AS no_unico,
                                            COLUMN_NAME AS columna, SEQ_IN_INDEX AS secuencia, INDEX_TYPE AS tipo_indice
                                     FROM INFORMATION_SCHEMA.STATISTICS
                                     WHERE TABLE_SCHEMA = :base
                                     ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX");
      $stmtIdx->execute(array(":base" => $base));
      $indices = $stmtIdx->fetchAll(PDO::FETCH_ASSOC);

      $stmtFk = $conexion->prepare("SELECT kcu.TABLE_NAME AS tabla,
                                           kcu.CONSTRAINT_NAME AS restriccion,
                                           kcu.COLUMN_NAME AS columna,
                                           kcu.REFERENCED_TABLE_NAME AS tabla_referencia,
                                           kcu.REFERENCED_COLUMN_NAME AS columna_referencia,
                                           kcu.ORDINAL_POSITION AS posicion,
                                           rc.UPDATE_RULE AS regla_update,
                                           rc.DELETE_RULE AS regla_delete
                                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                                    INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
                                      ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                                     AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                                     AND rc.TABLE_NAME = kcu.TABLE_NAME
                                    WHERE kcu.CONSTRAINT_SCHEMA = :base
                                      AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                                    ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION");
      $stmtFk->execute(array(":base" => $base));
      $foraneas = $stmtFk->fetchAll(PDO::FETCH_ASSOC);

      $porTabla = array();
      foreach ($tablas as $tabla) {
        $tabla["filas_estimadas"] = isset($tabla["filas_estimadas"]) ? intval($tabla["filas_estimadas"]) : 0;
        $porTabla[$tabla["tabla"]] = $tabla;
        $porTabla[$tabla["tabla"]]["columnas"] = array();
        $porTabla[$tabla["tabla"]]["indices"] = array();
        $porTabla[$tabla["tabla"]]["foraneas"] = array();
      }
      foreach ($columnas as $columna) {
        if (isset($porTabla[$columna["tabla"]])) {
          $porTabla[$columna["tabla"]]["columnas"][$columna["columna"]] = $columna;
        }
      }
      foreach ($indices as $indice) {
        if (!isset($porTabla[$indice["tabla"]])) {
          continue;
        }
        if (!isset($porTabla[$indice["tabla"]]["indices"][$indice["indice"]])) {
          $porTabla[$indice["tabla"]]["indices"][$indice["indice"]] = array(
            "indice" => $indice["indice"],
            "no_unico" => intval($indice["no_unico"]),
            "tipo_indice" => $indice["tipo_indice"],
            "columnas" => array()
          );
        }
        $porTabla[$indice["tabla"]]["indices"][$indice["indice"]]["columnas"][] = $indice["columna"];
      }
      foreach ($foraneas as $foranea) {
        if (!isset($porTabla[$foranea["tabla"]])) {
          continue;
        }
        if (!isset($porTabla[$foranea["tabla"]]["foraneas"][$foranea["restriccion"]])) {
          $porTabla[$foranea["tabla"]]["foraneas"][$foranea["restriccion"]] = array(
            "restriccion" => $foranea["restriccion"],
            "tabla_referencia" => $foranea["tabla_referencia"],
            "regla_update" => $foranea["regla_update"],
            "regla_delete" => $foranea["regla_delete"],
            "columnas" => array(),
            "columnas_referencia" => array()
          );
        }
        $porTabla[$foranea["tabla"]]["foraneas"][$foranea["restriccion"]]["columnas"][] = $foranea["columna"];
        $porTabla[$foranea["tabla"]]["foraneas"][$foranea["restriccion"]]["columnas_referencia"][] = $foranea["columna_referencia"];
      }

      return $this->respuesta(false, "success", "Snapshot de esquema consultado", array(
        "base" => $base,
        "tablas" => array_values($porTabla),
        "mapa" => $porTabla,
        "totales" => array("tablas" => count($tablas), "columnas" => count($columnas), "indices" => count($indices), "foraneas" => count($foraneas))
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function compararSnapshots($origen, $destino) {
    $tablasOrigen = $origen["mapa"];
    $tablasDestino = $destino["mapa"];
    $soloOrigen = array();
    $soloDestino = array();
    $columnasFaltantes = array();
    $columnasDiferentes = array();
    $indicesFaltantes = array();
    $foraneasFaltantes = array();

    foreach ($tablasOrigen as $nombre => $tabla) {
      if (!isset($tablasDestino[$nombre])) {
        $politica = $this->politicaSugerida($nombre, $tabla["filas_estimadas"]);
        $riesgo = $this->riesgoDiferenciaEsquema("create_table", $nombre, $politica);
        $soloOrigen[] = array(
          "tabla" => $nombre,
          "filas_estimadas" => $tabla["filas_estimadas"],
          "politica" => $politica,
          "riesgo" => $riesgo["riesgo"],
          "recomendacion" => $riesgo["recomendacion"]
        );
        continue;
      }
      foreach ($tabla["columnas"] as $nombreColumna => $columna) {
        if (!isset($tablasDestino[$nombre]["columnas"][$nombreColumna])) {
          $politica = $this->politicaSugerida($nombre, $tabla["filas_estimadas"]);
          $riesgo = $this->riesgoDiferenciaEsquema("add_column", $nombre, $politica);
          $columnasFaltantes[] = array(
            "tabla" => $nombre,
            "columna" => $nombreColumna,
            "definicion" => $this->definicionColumna($columna),
            "politica" => $politica["politica"],
            "riesgo" => $riesgo["riesgo"],
            "recomendacion" => $riesgo["recomendacion"]
          );
          continue;
        }
        $columnaDestino = $tablasDestino[$nombre]["columnas"][$nombreColumna];
        if ($this->firmaColumna($columna) !== $this->firmaColumna($columnaDestino)) {
          $politica = $this->politicaSugerida($nombre, $tabla["filas_estimadas"]);
          $riesgo = $this->riesgoDiferenciaEsquema("change_column", $nombre, $politica);
          $columnasDiferentes[] = array(
            "tabla" => $nombre,
            "columna" => $nombreColumna,
            "origen" => $this->firmaColumna($columna),
            "destino" => $this->firmaColumna($columnaDestino),
            "politica" => $politica["politica"],
            "riesgo" => $riesgo["riesgo"],
            "recomendacion" => $riesgo["recomendacion"]
          );
        }
      }
      foreach ($tabla["indices"] as $nombreIndice => $indice) {
        if ($nombreIndice === "PRIMARY") {
          continue;
        }
        if (!isset($tablasDestino[$nombre]["indices"][$nombreIndice])) {
          $politica = $this->politicaSugerida($nombre, $tabla["filas_estimadas"]);
          $riesgo = $this->riesgoDiferenciaEsquema("add_index", $nombre, $politica);
          $indicesFaltantes[] = array(
            "tabla" => $nombre,
            "indice" => $nombreIndice,
            "unico" => intval($indice["no_unico"]) === 0,
            "columnas" => $indice["columnas"],
            "definicion" => $this->definicionIndice($indice),
            "politica" => $politica["politica"],
            "riesgo" => $riesgo["riesgo"],
            "recomendacion" => $riesgo["recomendacion"]
          );
        }
      }
      foreach ($tabla["foraneas"] as $nombreForanea => $foranea) {
        if (!isset($tablasDestino[$nombre]["foraneas"][$nombreForanea])) {
          $politica = $this->politicaSugerida($nombre, $tabla["filas_estimadas"]);
          $riesgo = $this->riesgoDiferenciaEsquema("add_foreign_key", $nombre, $politica);
          $foraneasFaltantes[] = array(
            "tabla" => $nombre,
            "restriccion" => $nombreForanea,
            "tabla_referencia" => $foranea["tabla_referencia"],
            "columnas" => $foranea["columnas"],
            "columnas_referencia" => $foranea["columnas_referencia"],
            "regla_update" => $foranea["regla_update"],
            "regla_delete" => $foranea["regla_delete"],
            "definicion" => $this->definicionForanea($foranea),
            "politica" => $politica["politica"],
            "riesgo" => $riesgo["riesgo"],
            "recomendacion" => $riesgo["recomendacion"]
          );
        }
      }
    }

    foreach ($tablasDestino as $nombre => $tabla) {
      if (!isset($tablasOrigen[$nombre])) {
        $soloDestino[] = array(
          "tabla" => $nombre,
          "filas_estimadas" => $tabla["filas_estimadas"],
          "riesgo" => "revision",
          "recomendacion" => "No eliminar ni reemplazar tablas que existen solo en destino sin revision manual."
        );
      }
    }

    $riesgos = $this->resumenRiesgosComparacion(array_merge($soloOrigen, $soloDestino, $columnasFaltantes, $columnasDiferentes, $indicesFaltantes, $foraneasFaltantes));

    return array(
      "resumen" => array(
        "tablas_solo_origen" => count($soloOrigen),
        "tablas_solo_destino" => count($soloDestino),
        "columnas_faltantes_destino" => count($columnasFaltantes),
        "columnas_diferentes" => count($columnasDiferentes),
        "indices_faltantes_destino" => count($indicesFaltantes),
        "foraneas_faltantes_destino" => count($foraneasFaltantes),
        "riesgos" => $riesgos
      ),
      "riesgos" => $riesgos,
      "tablas_solo_origen" => $soloOrigen,
      "tablas_solo_destino" => $soloDestino,
      "columnas_faltantes_destino" => $columnasFaltantes,
      "columnas_diferentes" => $columnasDiferentes,
      "indices_faltantes_destino" => $indicesFaltantes,
      "foraneas_faltantes_destino" => $foraneasFaltantes
    );
  }

  private function riesgoDiferenciaEsquema($tipo, $tabla, $politica) {
    $politicaValor = isset($politica["politica"]) ? $politica["politica"] : "blocked";
    $riesgo = "medio";
    $recomendacion = "Revisar en dry-run antes de incluir en paquete.";

    if ($politicaValor === "blocked" || $politicaValor === "production_owned") {
      return array(
        "riesgo" => "bloqueante",
        "recomendacion" => "No aplicar automaticamente; tabla marcada como " . $politicaValor . "."
      );
    }

    if ($tipo === "add_index") {
      $riesgo = "bajo";
      $recomendacion = "Candidato a aplicar despues de validar respaldo y ventana.";
    } elseif ($tipo === "add_foreign_key" || $tipo === "change_column") {
      $riesgo = "alto";
      $recomendacion = "Requiere revision manual por posible impacto en datos existentes.";
    } elseif ($tipo === "create_table" && $politicaValor === "schema_only") {
      $riesgo = "medio";
      $recomendacion = "Candidato a crear estructura sin datos si el paquete queda vigente.";
    } elseif ($tipo === "add_column" && $politicaValor === "schema_only") {
      $riesgo = "medio";
      $recomendacion = "Candidato a agregar columna tras validar defaults, nullabilidad y respaldo.";
    } elseif ($tipo === "add_column") {
      $riesgo = "medio";
      $recomendacion = "Validar compatibilidad con datos antes de aplicar.";
    }

    return array("riesgo" => $riesgo, "recomendacion" => $recomendacion);
  }

  private function resumenRiesgosComparacion($items) {
    $resumen = array("bajo" => 0, "medio" => 0, "alto" => 0, "bloqueante" => 0, "revision" => 0);
    foreach ($items as $item) {
      $riesgo = isset($item["riesgo"]) ? $item["riesgo"] : "revision";
      if (!isset($resumen[$riesgo])) {
        $resumen[$riesgo] = 0;
      }
      $resumen[$riesgo]++;
    }
    return $resumen;
  }

  private function politicaSugerida($tabla, $filasEstimadas) {
    $politica = "blocked";
    $motivo = "Requiere revision manual antes de migrar.";
    $incluyeDatos = false;

    if (strpos($tabla, "sys_migraciones_") === 0) {
      $politica = "schema_only";
      $motivo = "Tabla tecnica del modulo de migraciones.";
    } elseif (strpos($tabla, "sys_") === 0) {
      $politica = in_array($tabla, array("sys_roles", "sys_permisos", "sys_roles_permisos"), true) ? "data_seed" : "production_owned";
      $incluyeDatos = $politica === "data_seed";
      $motivo = $politica === "data_seed" ? "Semilla controlada de seguridad." : "Tabla SYS sensible o propia del ambiente.";
    } elseif (strpos($tabla, "erp_catalogo_") === 0 || strpos($tabla, "erp_proveedores_") === 0) {
      $politica = "data_merge";
      $incluyeDatos = true;
      $motivo = "Catalogo/proveedores estan siendo regularizados en local y pueden requerir merge controlado.";
    } elseif (strpos($tabla, "erp_compras_") === 0 || strpos($tabla, "erp_ventas_") === 0 || strpos($tabla, "erp_inventario_") === 0 || strpos($tabla, "erp_almacen_") === 0) {
      $politica = "schema_only";
      $motivo = "Modulo operativo en pruebas; migrar estructura, datos solo con decision de arranque productivo.";
    } elseif ($filasEstimadas === 0) {
      $politica = "schema_only";
      $motivo = "Tabla vacia o sin estimacion de filas.";
    }

    return array(
      "tabla" => $tabla,
      "politica" => $politica,
      "incluye_datos" => $incluyeDatos,
      "requiere_revision" => true,
      "motivo" => $motivo
    );
  }

  private function perfilTablaDatos($tabla) {
    $nombre = $tabla["tabla"];
    $columnas = isset($tabla["columnas"]) ? $tabla["columnas"] : array();
    $indices = isset($tabla["indices"]) ? $tabla["indices"] : array();
    $pk = isset($indices["PRIMARY"]["columnas"]) ? $indices["PRIMARY"]["columnas"] : array();
    $unicos = array();
    foreach ($indices as $indice) {
      if ($indice["indice"] !== "PRIMARY" && intval($indice["no_unico"]) === 0) {
        $unicos[] = array("indice" => $indice["indice"], "columnas" => $indice["columnas"]);
      }
    }

    $candidatos = array();
    $fechas = array();
    $sensibles = array();
    foreach ($columnas as $columna) {
      $nombreColumna = strtolower($columna["columna"]);
      if (in_array($nombreColumna, array("codigo", "sku", "slug", "uuid", "rfc", "curp", "folio", "clave", "alias", "correo", "email"), true)
        || strpos($nombreColumna, "codigo") !== false
        || strpos($nombreColumna, "sku") !== false
        || strpos($nombreColumna, "uuid") !== false) {
        $candidatos[] = $columna["columna"];
      }
      if (strpos($nombreColumna, "fecha") !== false || strpos($nombreColumna, "_at") !== false || strpos($nombreColumna, "created") !== false || strpos($nombreColumna, "updated") !== false) {
        $fechas[] = $columna["columna"];
      }
      if (strpos($nombreColumna, "password") !== false || strpos($nombreColumna, "contras") !== false || strpos($nombreColumna, "token") !== false || strpos($nombreColumna, "session") !== false || strpos($nombreColumna, "hash") !== false || strpos($nombreColumna, "secret") !== false) {
        $sensibles[] = $columna["columna"];
      }
    }

    $politica = $this->politicaSugerida($nombre, intval($tabla["filas_estimadas"]));
    $riesgos = array();
    if (empty($pk)) {
      $riesgos[] = "sin_pk";
    }
    if ($politica["incluye_datos"] && empty($unicos) && empty($candidatos)) {
      $riesgos[] = "sin_llave_natural_clara";
    }
    if (!empty($sensibles)) {
      $riesgos[] = "columnas_sensibles";
    }
    if ($politica["politica"] === "production_owned") {
      $riesgos[] = "propiedad_productivo";
    }

    return array(
      "tabla" => $nombre,
      "filas_estimadas" => intval($tabla["filas_estimadas"]),
      "politica_sugerida" => $politica["politica"],
      "incluye_datos_sugerido" => $politica["incluye_datos"],
      "pk" => $pk,
      "unicos" => $unicos,
      "candidatos_llave_natural" => array_values(array_unique($candidatos)),
      "columnas_fecha" => array_slice(array_values(array_unique($fechas)), 0, 8),
      "columnas_sensibles" => array_values(array_unique($sensibles)),
      "riesgos" => $riesgos,
      "motivo" => $politica["motivo"]
    );
  }

  private function resumenTablasCorto($tablas) {
    $salida = array();
    foreach ($tablas as $tabla) {
      $salida[] = array(
        "tabla" => $tabla["tabla"],
        "filas_estimadas" => $tabla["filas_estimadas"],
        "politica" => $tabla["politica_sugerida"],
        "riesgos" => $tabla["riesgos"],
        "pk" => $tabla["pk"],
        "candidatos_llave_natural" => $tabla["candidatos_llave_natural"],
        "columnas_sensibles" => $tabla["columnas_sensibles"]
      );
    }
    return $salida;
  }

  private function politicaPersistida($tabla, $fallbackFilas = 0) {
    if ($this->tablaTecnicaExiste("sys_migraciones_tablas_politicas")) {
      try {
        $stmt = $this->db->prepare("SELECT tabla, politica, incluye_datos, llave_natural, descripcion
                                    FROM sys_migraciones_tablas_politicas
                                    WHERE tabla=:tabla AND estatus=1
                                    LIMIT 1");
        $stmt->execute(array(":tabla" => $tabla));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
          $fila["incluye_datos"] = intval($fila["incluye_datos"]) === 1;
          return $fila;
        }
      } catch (Exception $e) {
      }
    }
    if ($fallbackFilas === null) {
      return null;
    }
    return $this->politicaSugerida($tabla, intval($fallbackFilas));
  }

  private function firmaColumna($columna) {
    return array(
      "tipo" => $columna["tipo"],
      "null" => $columna["permite_null"],
      "default" => $columna["valor_default"],
      "extra" => $columna["extra"]
    );
  }

  private function definicionColumna($columna) {
    $sql = $columna["tipo"];
    $sql .= $columna["permite_null"] === "NO" ? " NOT NULL" : " NULL";
    if ($columna["valor_default"] !== null) {
      $sql .= " DEFAULT " . $this->sqlValor($columna["valor_default"]);
    }
    if ($columna["extra"] !== "") {
      $sql .= " " . $columna["extra"];
    }
    return $sql;
  }

  private function definicionIndice($indice) {
    $columnas = array();
    foreach ($indice["columnas"] as $columna) {
      $columnas[] = "`" . str_replace("`", "``", $columna) . "`";
    }
    $prefijo = intval($indice["no_unico"]) === 0 ? "UNIQUE KEY" : "KEY";
    return $prefijo . " `" . str_replace("`", "``", $indice["indice"]) . "` (" . implode(", ", $columnas) . ")";
  }

  private function definicionForanea($foranea) {
    $columnas = array();
    foreach ($foranea["columnas"] as $columna) {
      $columnas[] = "`" . str_replace("`", "``", $columna) . "`";
    }
    $columnasReferencia = array();
    foreach ($foranea["columnas_referencia"] as $columna) {
      $columnasReferencia[] = "`" . str_replace("`", "``", $columna) . "`";
    }
    $sql = "CONSTRAINT `" . str_replace("`", "``", $foranea["restriccion"]) . "` FOREIGN KEY (" . implode(", ", $columnas) . ") REFERENCES `" . str_replace("`", "``", $foranea["tabla_referencia"]) . "` (" . implode(", ", $columnasReferencia) . ")";
    if (!empty($foranea["regla_delete"]) && strtoupper($foranea["regla_delete"]) !== "RESTRICT") {
      $sql .= " ON DELETE " . $foranea["regla_delete"];
    }
    if (!empty($foranea["regla_update"]) && strtoupper($foranea["regla_update"]) !== "RESTRICT") {
      $sql .= " ON UPDATE " . $foranea["regla_update"];
    }
    return $sql;
  }

  private function showCreateTable($conexion, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return "";
    }
    try {
      $stmt = $conexion->query("SHOW CREATE TABLE `" . $tabla . "`");
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      return isset($fila["Create Table"]) ? $fila["Create Table"] : "";
    } catch (Exception $e) {
      return "";
    }
  }

  private function leerConfigAmbientes() {
    $ruta = $this->rutaConfigAmbientes();
    if (!file_exists($ruta)) {
      return array();
    }
    $ambientes = include $ruta;
    return is_array($ambientes) ? $ambientes : array();
  }

  private function rutaConfigAmbientes() {
    return __DIR__ . "/../config/migraciones_ambientes.local.php";
  }

  private function sanearAmbiente($ambiente) {
    return array(
      "alias" => isset($ambiente["alias"]) ? $ambiente["alias"] : "",
      "tipo" => isset($ambiente["tipo"]) ? $ambiente["tipo"] : "externo",
      "descripcion" => isset($ambiente["descripcion"]) ? $ambiente["descripcion"] : "",
      "host" => isset($ambiente["host"]) ? $ambiente["host"] : "",
      "base" => isset($ambiente["base"]) ? $ambiente["base"] : "",
      "usuario" => isset($ambiente["usuario"]) ? $ambiente["usuario"] : "",
      "configurado" => !empty($ambiente["host"]) && !empty($ambiente["base"]) && !empty($ambiente["usuario"])
    );
  }

  private function sqlValor($valor) {
    if (is_numeric($valor)) {
      return "'" . str_replace("'", "''", (string) $valor) . "'";
    }
    if (strtoupper((string) $valor) === "CURRENT_TIMESTAMP") {
      return "CURRENT_TIMESTAMP";
    }
    return "'" . str_replace("'", "''", (string) $valor) . "'";
  }

  private function tablaTecnicaExiste($tabla) {
    if (!$this->identificadorTablaValido($tabla) || !$this->db) {
      return false;
    }
    try {
      $stmt = $this->db->prepare("SELECT TABLE_NAME
                                  FROM INFORMATION_SCHEMA.TABLES
                                  WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla
                                  LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function identificadorTablaValido($tabla) {
    return is_string($tabla) && preg_match('/^[a-zA-Z0-9_]+$/', $tabla);
  }

  private function politicasPermitidas() {
    return array("schema_only", "data_seed", "data_merge", "data_snapshot", "local_only", "production_owned", "blocked");
  }

  private function nombreRespaldoSugerido($alcance) {
    $base = defined("MYSQLBASE") ? MYSQLBASE : "base";
    return $base . "_panel_" . date("Ymd_His") . "_antes_" . preg_replace('/[^a-zA-Z0-9_]+/', "_", $alcance) . ".sql";
  }

  private function nombreRespaldoAmbiente($alias, $base, $alcance) {
    return preg_replace('/[^a-zA-Z0-9_]+/', "_", $alias)
      . "_" . preg_replace('/[^a-zA-Z0-9_]+/', "_", $base)
      . "_panel_" . date("Ymd_His")
      . "_antes_" . preg_replace('/[^a-zA-Z0-9_]+/', "_", $alcance)
      . ".sql";
  }

  private function directorioRespaldos() {
    $config = $this->leerConfigAmbientes();
    if (!empty($config["_opciones"]["directorio_respaldos"])) {
      return rtrim((string) $config["_opciones"]["directorio_respaldos"], "\\/");
    }
    return "C:\\xampp\\panel_db_backups";
  }

  private function rutaMysqldump() {
    $config = $this->leerConfigAmbientes();
    if (!empty($config["_opciones"]["mysqldump_path"])) {
      return (string) $config["_opciones"]["mysqldump_path"];
    }
    return "C:\\xampp\\mysql\\bin\\mysqldump.exe";
  }

  private function rutaMysqlCliente() {
    $config = $this->leerConfigAmbientes();
    if (!empty($config["_opciones"]["mysql_path"])) {
      return (string) $config["_opciones"]["mysql_path"];
    }
    return "C:\\xampp\\mysql\\bin\\mysql.exe";
  }

  private function comandoMysqldumpSaneado($archivo) {
    return $this->rutaMysqldump()
      . " --host=" . MYSQLHOST
      . " --port=" . MYSQLPORT
      . " --user=" . MYSQLUSER
      . " --single-transaction --routines --events --triggers --default-character-set=utf8mb4"
      . " --result-file=\"" . $archivo . "\" "
      . MYSQLBASE;
  }

  private function comandoMysqldumpAmbienteSaneado($ambiente, $archivo) {
    return $this->rutaMysqldump()
      . " --host=" . (isset($ambiente["host"]) ? $ambiente["host"] : "")
      . " --port=" . (isset($ambiente["port"]) ? $ambiente["port"] : "3306")
      . " --user=" . (isset($ambiente["usuario"]) ? $ambiente["usuario"] : "")
      . " --single-transaction --routines --events --triggers --add-drop-table --default-character-set=utf8mb4"
      . " --result-file=\"" . $archivo . "\" "
      . (isset($ambiente["base"]) ? $ambiente["base"] : "");
  }

  private function comandoRestoreSaneado($archivo) {
    return $this->rutaMysqlCliente()
      . " --host=" . MYSQLHOST
      . " --port=" . MYSQLPORT
      . " --user=" . MYSQLUSER
      . " --default-character-set=utf8mb4 "
      . MYSQLBASE
      . " < \"" . trim((string) $archivo) . "\"";
  }

  private function checkItem($codigo, $ok, $mensajeOk, $mensajeError, $nivel = "warning", $depurar = array()) {
    return array(
      "codigo" => $codigo,
      "ok" => (bool) $ok,
      "nivel" => $ok ? "success" : $nivel,
      "mensaje" => $ok ? $mensajeOk : $mensajeError,
      "depurar" => $depurar
    );
  }

  private function checklistPaso($codigo, $titulo, $ok, $accionPendiente, $depurar = array()) {
    return array(
      "codigo" => $codigo,
      "titulo" => $titulo,
      "ok" => (bool) $ok,
      "estatus" => $ok ? "completo" : "pendiente",
      "accion_pendiente" => $ok ? "" : $accionPendiente,
      "depurar" => $depurar
    );
  }

  private function semaforoPaso($codigo, $titulo, $ok, $mensaje, $nivel) {
    return array(
      "codigo" => $codigo,
      "titulo" => $titulo,
      "ok" => (bool) $ok,
      "nivel" => $nivel,
      "mensaje" => $mensaje
    );
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = null) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
