<?php

class SistemaConfiguracion extends CRUD {

  private $tabla_parametros = "sys_configuracion_parametros";
  private $tabla_historial = "sys_configuracion_historial";
  private $logoFallbackPrincipal = "/assets/media/logos/default-dark.svg";
  private $logoFallbackCompacto = "/assets/media/logos/default-small.svg";
  private $logoFallbackLogin = "/assets/media/logos/default-dark.svg";
  private $faviconFallback = "/assets/media/logos/favicon.svg";

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-08
   * Proposito: obtener branding visual no sensible para header/sidebar.
   * Impacto: Layout ERP; centraliza nombre y logos configurables con fallback local.
   * Contrato: si el esquema no existe o el archivo configurado falta, devuelve assets por defecto.
   */
  public function obtenerBranding() {
    $branding = array(
      "nombre_sistema" => "ERP Artiani",
      "logo_principal" => $this->logoFallbackPrincipal,
      "logo_compacto" => $this->logoFallbackCompacto,
      "logo_login" => $this->logoFallbackLogin,
      "favicon" => $this->faviconFallback,
      "login_titulo" => "Iniciar sesion",
      "login_subtitulo" => "Acceso al panel operativo"
    );

    if (!$this->tablaParametrosExiste()) {
      return $branding;
    }

    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT clave, valor
                            FROM {$this->tabla_parametros}
                            WHERE clave IN ('branding.nombre_sistema', 'branding.logo_principal', 'branding.logo_compacto', 'branding.logo_login', 'branding.favicon', 'branding.login_titulo', 'branding.login_subtitulo')
                              AND estatus=1");
      $stmt->execute();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $clave = $fila["clave"];
        $valor = trim((string) $fila["valor"]);
        if ($clave === "branding.nombre_sistema" && $valor !== "") {
          $branding["nombre_sistema"] = mb_substr($valor, 0, 80, "UTF-8");
        }
        if ($clave === "branding.logo_principal" && $this->assetPublicoExiste($valor)) {
          $branding["logo_principal"] = $valor;
        }
        if ($clave === "branding.logo_compacto" && $this->assetPublicoExiste($valor)) {
          $branding["logo_compacto"] = $valor;
        }
        if ($clave === "branding.logo_login" && $this->assetPublicoExiste($valor)) {
          $branding["logo_login"] = $valor;
        }
        if ($clave === "branding.favicon" && $this->assetPublicoExiste($valor)) {
          $branding["favicon"] = $valor;
        }
        if ($clave === "branding.login_titulo" && $valor !== "") {
          $branding["login_titulo"] = mb_substr($valor, 0, 100, "UTF-8");
        }
        if ($clave === "branding.login_subtitulo" && $valor !== "") {
          $branding["login_subtitulo"] = mb_substr($valor, 0, 180, "UTF-8");
        }
      }
    } catch (Exception $e) {
      return $branding;
    }

    return $branding;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-23
   * Proposito: exponer un diagnostico saneado de entorno, URLs, BD e impresion POS sin mostrar secretos.
   * Impacto: SYS/Administracion; sirve como punto inicial para operar local/productivo y preparar tickets.
   * Contrato: nunca devuelve MYSQLPASS ni credenciales completas; solo estado, host/base/usuario y recomendaciones.
   */
  public function diagnosticoSistema() {
    $serverName = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "";
    $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
      || (isset($_SERVER["SERVER_PORT"]) && intval($_SERVER["SERVER_PORT"]) === 443);
    $ambiente = $this->clasificarAmbiente($serverName);
    $conexion = $this->diagnosticarConexion();

    return $this->crudResponse(false, "success", "Diagnostico de configuracion generado", array(
      "ambiente" => array(
        "server_name" => $serverName,
        "tipo" => $ambiente,
        "protocolo" => $https ? "https" : "http",
        "ruta_app" => defined("RUTA_APP") ? RUTA_APP : "",
        "ruta_url" => defined("RUTA_URL") ? RUTA_URL : "",
        "timezone" => defined("APP_TIMEZONE") ? APP_TIMEZONE : date_default_timezone_get()
      ),
      "base_datos" => array(
        "host" => defined("MYSQLHOST") ? MYSQLHOST : "",
        "base" => defined("MYSQLBASE") ? MYSQLBASE : "",
        "usuario" => defined("MYSQLUSER") ? $this->enmascararUsuario(MYSQLUSER) : "",
        "conectada" => $conexion["conectada"],
        "version" => $conexion["version"],
        "mensaje" => $conexion["mensaje"]
      ),
      "impresion" => array(
        "estado" => $ambiente === "local" ? "compatible_local" : "requiere_puente_local",
        "recomendacion" => $ambiente === "local"
          ? "La impresion directa de tickets debe resolverse desde esta computadora o una terminal POS local."
          : "En productivo conviene usar un agente/puente local por sucursal para enviar tickets a la impresora instalada."
      ),
      "pendientes" => array(
        "Definir perfiles explicitos de entorno antes de guardar cambios de configuracion.",
        "Separar configuracion sensible de credenciales y no editarla desde la UI general.",
        "Disenar el conector local de impresora de tickets para POS con auditoria y prueba de impresion."
      )
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-23
   * Proposito: listar parametros configurables de SYS con diagnostico operativo.
   * Impacto: Administracion/SYS; alimenta la UI editable de configuracion.
   * Contrato: si el esquema no existe responde lista vacia y bandera `requiere_esquema`.
   */
  public function consultarConfiguracion() {
    $diagnostico = $this->diagnosticoSistema();
    if (!$this->tablaParametrosExiste()) {
      $diagnostico["depurar"]["parametros"] = array();
      $diagnostico["depurar"]["requiere_esquema"] = true;
      return $diagnostico;
    }

    try {
      $this->asegurarParametrosBrandingBase();
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT id_configuracion_parametro, grupo, clave, tipo_dato, valor, descripcion, editable_ui, sensible, estatus
                            FROM {$this->tabla_parametros}
                            WHERE estatus=1
                            ORDER BY grupo, clave");
      $stmt->execute();
      $diagnostico["depurar"]["parametros"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $diagnostico["depurar"]["requiere_esquema"] = false;
      return $diagnostico;
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-23
   * Proposito: guardar parametros SYS editables con historial y auditoria funcional.
   * Impacto: Administracion/SYS; permite configurar entorno operativo e impresion POS.
   * Contrato: solo claves existentes, no sensibles y `editable_ui=1`; no crea parametros arbitrarios desde POST.
   */
  public function guardarConfiguracion($valores, $idUsuario, $motivo = "") {
    if (!$this->tablaParametrosExiste()) {
      return $this->crudResponse(true, "warning", "Primero aplica el esquema de configuracion SYS");
    }
    if (!is_array($valores) || empty($valores)) {
      return $this->crudResponse(true, "warning", "No hay parametros para guardar");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $claves = array_keys($valores);
      $marcadores = implode(",", array_fill(0, count($claves), "?"));
      $stmt = $db->prepare("SELECT id_configuracion_parametro, clave, tipo_dato, valor, editable_ui, sensible
                            FROM {$this->tabla_parametros}
                            WHERE clave IN ({$marcadores}) AND estatus=1
                            FOR UPDATE");
      $stmt->execute($claves);
      $parametros = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $porClave = array();
      foreach ($parametros as $parametro) {
        $porClave[$parametro["clave"]] = $parametro;
      }

      $actualizados = array();
      foreach ($valores as $clave => $valorNuevo) {
        if (!isset($porClave[$clave])) {
          throw new Exception("Parametro no permitido: " . $clave);
        }
        $parametro = $porClave[$clave];
        if (intval($parametro["editable_ui"]) !== 1 || intval($parametro["sensible"]) === 1) {
          throw new Exception("Parametro protegido: " . $clave);
        }
        $valorLimpio = $this->normalizarValor($parametro["tipo_dato"], $valorNuevo);
        $valorAntes = (string) $parametro["valor"];
        if ($valorAntes === $valorLimpio) {
          continue;
        }

        $stmtUpdate = $db->prepare("UPDATE {$this->tabla_parametros}
                                    SET valor=:valor, fecha_actualizacion=CURRENT_TIMESTAMP, id_usuario_actualizacion=:usuario
                                    WHERE id_configuracion_parametro=:id");
        $stmtUpdate->execute(array(
          ":valor" => $valorLimpio,
          ":usuario" => intval($idUsuario),
          ":id" => intval($parametro["id_configuracion_parametro"])
        ));

        $stmtHist = $db->prepare("INSERT INTO {$this->tabla_historial}
          (id_configuracion_parametro, clave, valor_antes, valor_despues, motivo, id_usuario)
          VALUES (:id, :clave, :antes, :despues, :motivo, :usuario)");
        $stmtHist->execute(array(
          ":id" => intval($parametro["id_configuracion_parametro"]),
          ":clave" => $clave,
          ":antes" => $valorAntes,
          ":despues" => $valorLimpio,
          ":motivo" => trim($motivo),
          ":usuario" => intval($idUsuario)
        ));

        $actualizados[] = array("clave" => $clave, "valor_antes" => $valorAntes, "valor_despues" => $valorLimpio);
      }

      $db->commit();
      return $this->crudResponse(false, "success", "Configuracion guardada correctamente", array(
        "total_actualizados" => count($actualizados),
        "actualizados" => $actualizados
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-08
   * Proposito: guardar un archivo de marca validado y enlazarlo a un parametro SYS de branding.
   * Impacto: Administracion/SYS y layout global; reemplaza logos/favicon rotos sin tocar credenciales.
   * Contrato: acepta `principal`, `compacto`, `login` o `favicon`; imagenes publicas de maximo 2 MB.
   */
  public function guardarLogo($tipoLogo, $archivo, $idUsuario, $motivo = "") {
    if (!$this->tablaParametrosExiste()) {
      return $this->crudResponse(true, "warning", "Primero aplica el esquema de configuracion SYS");
    }

    $tipoLogo = trim((string) $tipoLogo);
    if ($tipoLogo === "favicon") {
      $clave = "branding.favicon";
    } elseif ($tipoLogo === "login") {
      $clave = "branding.logo_login";
    } else {
      $clave = $tipoLogo === "compacto" ? "branding.logo_compacto" : "branding.logo_principal";
    }

    try {
      $this->asegurarParametroBrandingLogo($clave);
      $this->validarArchivoLogo($archivo);
      $mime = $this->detectarMimeLogo($archivo["tmp_name"]);
      $extension = $this->extensionLogo($mime);
      $directorio = $this->directorioLogos();
      if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
        throw new Exception("No fue posible crear el directorio de logos");
      }

      $prefijo = "principal";
      if ($clave === "branding.logo_compacto") {
        $prefijo = "compacto";
      }
      if ($clave === "branding.favicon") {
        $prefijo = "favicon";
      }
      if ($clave === "branding.logo_login") {
        $prefijo = "login";
      }
      $nombre = "erp-" . $prefijo . "-" . date("YmdHis") . "." . $extension;
      $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . $nombre;
      if (!move_uploaded_file($archivo["tmp_name"], $rutaAbsoluta)) {
        throw new Exception("No fue posible guardar el archivo de logo");
      }

      $rutaPublica = "/assets/media/logos/" . $nombre;
      $respuesta = $this->guardarConfiguracion(array($clave => $rutaPublica), $idUsuario, $motivo);
      if ($respuesta["error"]) {
        @unlink($rutaAbsoluta);
        return $respuesta;
      }

      $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
      $depurar["logo"] = array("clave" => $clave, "ruta" => $rutaPublica, "mime" => $mime);
      return $this->crudResponse(false, "success", $clave === "branding.favicon" ? "Favicon guardado correctamente" : "Logo guardado correctamente", $depurar);
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  private function clasificarAmbiente($serverName) {
    $serverName = strtolower(trim($serverName));
    if ($serverName === "localhost" || substr($serverName, -6) === ".local") {
      return "local";
    }
    if ($serverName === "") {
      return "sin_server_name";
    }
    return "productivo";
  }

  private function diagnosticarConexion() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return array("conectada" => false, "version" => "", "mensaje" => "No hay conexion PDO disponible");
      }
      $version = $db->query("SELECT VERSION()")->fetchColumn();
      return array("conectada" => true, "version" => $version, "mensaje" => "Conexion activa");
    } catch (Exception $e) {
      return array("conectada" => false, "version" => "", "mensaje" => $e->getMessage());
    }
  }

  private function tablaParametrosExiste() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return false;
      }
      $stmt = $db->prepare("SELECT TABLE_NAME
                            FROM INFORMATION_SCHEMA.TABLES
                            WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla
                            LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $this->tabla_parametros));
      return !empty($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return false;
    }
  }

  private function normalizarValor($tipo, $valor) {
    $valor = is_scalar($valor) ? trim((string) $valor) : "";
    if ($tipo === "numero") {
      return (string) max(0, intval($valor));
    }
    if ($tipo === "ruta") {
      if ($valor !== "" && !preg_match('/^\/assets\/media\/logos\/[a-zA-Z0-9_.-]+\.(png|jpg|jpeg|webp|svg|ico)$/i', $valor)) {
        throw new Exception("La ruta del archivo de marca no es valida");
      }
      return $valor;
    }
    if ($tipo === "url" && $valor !== "" && !preg_match('/^https?:\/\//i', $valor)) {
      throw new Exception("La URL debe iniciar con http:// o https://");
    }
    if ($tipo === "opcion") {
      return preg_replace('/[^a-zA-Z0-9_.-]/', '', $valor);
    }
    return mb_substr($valor, 0, 1000, "UTF-8");
  }

  private function enmascararUsuario($usuario) {
    $usuario = trim((string) $usuario);
    if ($usuario === "") {
      return "";
    }
    if (strlen($usuario) <= 2) {
      return str_repeat("*", strlen($usuario));
    }
    return substr($usuario, 0, 2) . str_repeat("*", max(2, strlen($usuario) - 2));
  }

  private function validarArchivoLogo($archivo) {
    if (!is_array($archivo) || !isset($archivo["error"]) || intval($archivo["error"]) !== UPLOAD_ERR_OK) {
      throw new Exception("Selecciona un archivo de logo valido");
    }
    if (empty($archivo["tmp_name"]) || !is_uploaded_file($archivo["tmp_name"])) {
      throw new Exception("La carga del logo no es valida");
    }
    $tamano = intval(isset($archivo["size"]) ? $archivo["size"] : 0);
    if ($tamano <= 0 || $tamano > 2 * 1024 * 1024) {
      throw new Exception("El logo debe pesar entre 1 byte y 2 MB");
    }
  }

  private function asegurarParametroBrandingLogo($clave) {
    $db = $this->getConexion();
    $valor = $this->logoFallbackPrincipal;
    $descripcion = "Logo principal del sidebar en modo expandido";
    if ($clave === "branding.logo_compacto") {
      $valor = $this->logoFallbackCompacto;
      $descripcion = "Logo compacto para sidebar minimizado y vista movil";
    }
    if ($clave === "branding.favicon") {
      $valor = $this->faviconFallback;
      $descripcion = "Icono del navegador para identificar el sistema";
    }
    if ($clave === "branding.logo_login") {
      $valor = $this->logoFallbackLogin;
      $descripcion = "Logo para la pantalla de inicio de sesion sobre fondo claro";
    }
    $stmt = $db->prepare("INSERT INTO {$this->tabla_parametros}
      (grupo, clave, tipo_dato, valor, descripcion, editable_ui, sensible, estatus)
      VALUES ('branding', :clave, 'ruta', :valor, :descripcion, 1, 0, 1)
      ON DUPLICATE KEY UPDATE grupo=VALUES(grupo), tipo_dato=VALUES(tipo_dato), descripcion=VALUES(descripcion), editable_ui=1, sensible=0, estatus=1");
    $stmt->execute(array(
      ":clave" => $clave,
      ":valor" => $valor,
      ":descripcion" => $descripcion
    ));
  }

  private function asegurarParametrosBrandingBase() {
    $semillas = array(
      array("clave" => "branding.nombre_sistema", "tipo" => "texto", "valor" => "ERP Artiani", "descripcion" => "Nombre visible en header, sidebar, login y vistas generales del panel"),
      array("clave" => "branding.logo_principal", "tipo" => "ruta", "valor" => $this->logoFallbackPrincipal, "descripcion" => "Logo principal del sidebar en modo expandido"),
      array("clave" => "branding.logo_compacto", "tipo" => "ruta", "valor" => $this->logoFallbackCompacto, "descripcion" => "Logo compacto para sidebar minimizado y vista movil"),
      array("clave" => "branding.logo_login", "tipo" => "ruta", "valor" => $this->logoFallbackLogin, "descripcion" => "Logo para pantalla de inicio de sesion sobre fondo claro"),
      array("clave" => "branding.favicon", "tipo" => "ruta", "valor" => $this->faviconFallback, "descripcion" => "Icono del navegador para identificar el sistema"),
      array("clave" => "branding.login_titulo", "tipo" => "texto", "valor" => "Iniciar sesion", "descripcion" => "Titulo principal mostrado en la pantalla de login"),
      array("clave" => "branding.login_subtitulo", "tipo" => "texto", "valor" => "Acceso al panel operativo", "descripcion" => "Texto de apoyo mostrado bajo el titulo de login")
    );
    $db = $this->getConexion();
    $stmt = $db->prepare("INSERT INTO {$this->tabla_parametros}
      (grupo, clave, tipo_dato, valor, descripcion, editable_ui, sensible, estatus)
      VALUES ('branding', :clave, :tipo, :valor, :descripcion, 1, 0, 1)
      ON DUPLICATE KEY UPDATE grupo=VALUES(grupo), tipo_dato=VALUES(tipo_dato), descripcion=VALUES(descripcion), editable_ui=1, sensible=0, estatus=1");
    foreach ($semillas as $semilla) {
      $stmt->execute(array(
        ":clave" => $semilla["clave"],
        ":tipo" => $semilla["tipo"],
        ":valor" => $semilla["valor"],
        ":descripcion" => $semilla["descripcion"]
      ));
    }
  }

  private function detectarMimeLogo($rutaTemporal) {
    $mime = function_exists("mime_content_type") ? mime_content_type($rutaTemporal) : "";
    $permitidos = array("image/png", "image/jpeg", "image/webp", "image/vnd.microsoft.icon", "image/x-icon");
    if (!in_array($mime, $permitidos, true)) {
      throw new Exception("Tipo de archivo no permitido. Usa PNG, JPG, WEBP o ICO");
    }
    return $mime;
  }

  private function extensionLogo($mime) {
    if ($mime === "image/png") {
      return "png";
    }
    if ($mime === "image/webp") {
      return "webp";
    }
    if ($mime === "image/vnd.microsoft.icon" || $mime === "image/x-icon") {
      return "ico";
    }
    return "jpg";
  }

  private function directorioLogos() {
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "media" . DIRECTORY_SEPARATOR . "logos";
  }

  private function assetPublicoExiste($rutaPublica) {
    $rutaPublica = trim((string) $rutaPublica);
    if ($rutaPublica === "" || !preg_match('/^\/assets\/media\/logos\/[a-zA-Z0-9_.-]+\.(png|jpg|jpeg|webp|svg|ico)$/i', $rutaPublica)) {
      return false;
    }
    $rutaRelativa = ltrim(str_replace("/", DIRECTORY_SEPARATOR, $rutaPublica), DIRECTORY_SEPARATOR);
    return is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . $rutaRelativa);
  }
}
