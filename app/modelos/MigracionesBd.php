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
      $politicas[] = $this->politicaSugerida($tabla["tabla"], intval($tabla["filas_estimadas"]));
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
          "riesgo" => "medio",
          "sql" => $create . ";"
        );
      }
    }

    foreach ($comparacion["depurar"]["comparacion"]["columnas_faltantes_destino"] as $columna) {
      $sql[] = array(
        "orden" => $orden++,
        "tipo" => "add_column",
        "tabla" => $columna["tabla"],
        "riesgo" => "medio",
        "sql" => "ALTER TABLE `" . $columna["tabla"] . "` ADD COLUMN `" . $columna["columna"] . "` " . $columna["definicion"] . ";"
      );
    }

    foreach ($comparacion["depurar"]["comparacion"]["indices_faltantes_destino"] as $indice) {
      $sql[] = array(
        "orden" => $orden++,
        "tipo" => "add_index",
        "tabla" => $indice["tabla"],
        "riesgo" => "bajo",
        "sql" => "ALTER TABLE `" . $indice["tabla"] . "` ADD " . $indice["definicion"] . ";"
      );
    }

    foreach ($comparacion["depurar"]["comparacion"]["foraneas_faltantes_destino"] as $foranea) {
      $sql[] = array(
        "orden" => $orden++,
        "tipo" => "add_foreign_key",
        "tabla" => $foranea["tabla"],
        "riesgo" => "alto",
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

    $resumen = array(
      "origen" => "local",
      "destino" => $aliasDestino,
      "tablas_solicitadas" => $tablasNormalizadas,
      "sentencias" => count($sentencias),
      "incluye_datos" => false,
      "fase" => "dry_run_esquema"
    );
    $hash = hash("sha256", json_encode(array("resumen" => $resumen, "sentencias" => $sentencias)));
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
        foreach ($tablasNormalizadas as $tabla) {
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
   * Fecha: 2026-07-31
   * Proposito: preparar checklist y comandos sugeridos para activar esquema tecnico del modulo.
   * Impacto: Migraciones BD; no ejecuta DDL ni crea respaldos.
   * Contrato: devuelve preflight, comando mysqldump sugerido y texto de autorizacion.
   */
  public function preflightActivacion($respaldo = "") {
    $esquema = $this->estadoEsquemaTecnico();
    $validacionRespaldo = $this->validarRespaldo($respaldo);
    $nombreSugerido = $this->nombreRespaldoSugerido("migracion_bd_schema");
    $rutaSugerida = "C:\\xampp\\panel_db_backups\\" . $nombreSugerido;
    $comandoRespaldo = "C:\\xampp\\mysql\\bin\\mysqldump.exe --host=" . MYSQLHOST . " --user=" . MYSQLUSER . " --result-file=\"" . $rutaSugerida . "\" " . MYSQLBASE;
    $comandoAplicacion = "POST /migracionBd/esquema_actualizar con ejecutar=1";
    $textoAutorizacion = "AUTORIZO CREAR ESQUEMA TECNICO MIGRACIONES BD usando respaldo [RUTA_RESPALDO] con alcance exclusivo sys_migraciones_*. Entiendo que solo crea tablas tecnicas de preparacion, politicas, paquetes y ejecuciones; no migra catalogo, proveedores, compras, ventas, inventario, clientes, usuarios operativos ni productivo.";

    return $this->respuesta(false, "success", "Preflight de activacion generado", array(
      "esquema_tecnico" => $esquema,
      "respaldo" => $validacionRespaldo["depurar"],
      "puede_aplicar" => !empty($validacionRespaldo["depurar"]["ok"]),
      "ruta_respaldo_sugerida" => $rutaSugerida,
      "comando_respaldo_sugerido" => $comandoRespaldo,
      "comando_aplicacion" => $comandoAplicacion,
      "token" => "MIGRACIONES_BD_SCHEMA",
      "texto_autorizacion" => $textoAutorizacion
    ));
  }

  private function ambientesDisponibles() {
    $ambientes = array($this->ambienteLocalSaneado());
    foreach ($this->leerConfigAmbientes() as $alias => $ambiente) {
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
    return isset($ambientes[$alias]) && is_array($ambientes[$alias]) ? $ambientes[$alias] : null;
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
        $soloOrigen[] = array("tabla" => $nombre, "filas_estimadas" => $tabla["filas_estimadas"], "politica" => $this->politicaSugerida($nombre, $tabla["filas_estimadas"]));
        continue;
      }
      foreach ($tabla["columnas"] as $nombreColumna => $columna) {
        if (!isset($tablasDestino[$nombre]["columnas"][$nombreColumna])) {
          $columnasFaltantes[] = array(
            "tabla" => $nombre,
            "columna" => $nombreColumna,
            "definicion" => $this->definicionColumna($columna)
          );
          continue;
        }
        $columnaDestino = $tablasDestino[$nombre]["columnas"][$nombreColumna];
        if ($this->firmaColumna($columna) !== $this->firmaColumna($columnaDestino)) {
          $columnasDiferentes[] = array(
            "tabla" => $nombre,
            "columna" => $nombreColumna,
            "origen" => $this->firmaColumna($columna),
            "destino" => $this->firmaColumna($columnaDestino)
          );
        }
      }
      foreach ($tabla["indices"] as $nombreIndice => $indice) {
        if ($nombreIndice === "PRIMARY") {
          continue;
        }
        if (!isset($tablasDestino[$nombre]["indices"][$nombreIndice])) {
          $indicesFaltantes[] = array(
            "tabla" => $nombre,
            "indice" => $nombreIndice,
            "unico" => intval($indice["no_unico"]) === 0,
            "columnas" => $indice["columnas"],
            "definicion" => $this->definicionIndice($indice)
          );
        }
      }
      foreach ($tabla["foraneas"] as $nombreForanea => $foranea) {
        if (!isset($tablasDestino[$nombre]["foraneas"][$nombreForanea])) {
          $foraneasFaltantes[] = array(
            "tabla" => $nombre,
            "restriccion" => $nombreForanea,
            "tabla_referencia" => $foranea["tabla_referencia"],
            "columnas" => $foranea["columnas"],
            "columnas_referencia" => $foranea["columnas_referencia"],
            "regla_update" => $foranea["regla_update"],
            "regla_delete" => $foranea["regla_delete"],
            "definicion" => $this->definicionForanea($foranea)
          );
        }
      }
    }

    foreach ($tablasDestino as $nombre => $tabla) {
      if (!isset($tablasOrigen[$nombre])) {
        $soloDestino[] = array("tabla" => $nombre, "filas_estimadas" => $tabla["filas_estimadas"]);
      }
    }

    return array(
      "resumen" => array(
        "tablas_solo_origen" => count($soloOrigen),
        "tablas_solo_destino" => count($soloDestino),
        "columnas_faltantes_destino" => count($columnasFaltantes),
        "columnas_diferentes" => count($columnasDiferentes),
        "indices_faltantes_destino" => count($indicesFaltantes),
        "foraneas_faltantes_destino" => count($foraneasFaltantes)
      ),
      "tablas_solo_origen" => $soloOrigen,
      "tablas_solo_destino" => $soloDestino,
      "columnas_faltantes_destino" => $columnasFaltantes,
      "columnas_diferentes" => $columnasDiferentes,
      "indices_faltantes_destino" => $indicesFaltantes,
      "foraneas_faltantes_destino" => $foraneasFaltantes
    );
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

  private function politicaPersistida($tabla) {
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
    return $this->politicaSugerida($tabla, 0);
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

  private function respuesta($error, $tipo, $mensaje, $depurar = null) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
