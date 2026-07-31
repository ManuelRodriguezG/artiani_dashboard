<?php

class MigracionBd extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: abrir la consola tecnica de migraciones de base de datos.
   * Impacto: Administracion/SYS; solo consulta y prepara dry-runs en Fase 1.
   * Contrato: requiere `migraciones.ver`; la aplicacion real no existe en esta fase.
   */
  public function index() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    $this->vista("apps/erp/sistema/migraciones_bd", array(
      "diagnostico" => $modelo->diagnosticoInicial()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: consultar diagnostico seguro de ambientes y tablas locales.
   * Impacto: Migraciones BD; no escribe BD ni expone secretos.
   */
  public function diagnostico() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->diagnosticoInicial());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: listar ambientes configurados sin passwords.
   * Impacto: Migraciones BD; permite seleccionar destino para comparar.
   */
  public function ambientes_listar() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->listarAmbientes());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: generar politicas sugeridas por tabla.
   * Impacto: Migraciones BD; no persiste decisiones.
   */
  public function tablas_clasificar() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->clasificarTablas());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: comparar esquema local contra ambiente destino.
   * Impacto: Migraciones BD; solo lectura.
   */
  public function comparar_ambientes() {
    $this->requerirAlgunPermiso(array("migraciones.preparar", "sistema.soporte"));
    $destino = isset($_GET["destino"]) ? trim($_GET["destino"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->compararAmbientes($destino));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: generar SQL dry-run de diferencias de esquema.
   * Impacto: Migraciones BD; no ejecuta SQL ni crea paquetes persistentes.
   */
  public function sql_dry_run() {
    $this->requerirAlgunPermiso(array("migraciones.preparar", "sistema.soporte"));
    $destino = isset($_GET["destino"]) ? trim($_GET["destino"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->generarSqlDryRun($destino));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: guardar politicas tecnicas por tabla cuando el esquema del modulo existe.
   * Impacto: Migraciones BD; no aplica migraciones ni toca tablas operativas.
   * Contrato: `politicas` debe ser JSON arreglo con tabla/politica/incluye_datos.
   */
  public function politicas_guardar() {
    $this->requerirAlgunPermiso(array("migraciones.preparar", "sistema.soporte"));
    $politicas = isset($_POST["politicas"]) ? json_decode($_POST["politicas"], true) : array();
    $modelo = $this->modelo("MigracionesBd");
    $respuesta = $modelo->guardarPoliticas($politicas, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("migraciones", "politicas_guardar", array(
      "entidad" => "sys_migraciones_tablas_politicas",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null,
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: crear paquete de migracion en modo dry-run.
   * Impacto: Migraciones BD; guarda evidencia si existen tablas SYS, pero no ejecuta SQL.
   * Contrato: requiere destino y opcionalmente tablas JSON seleccionadas.
   */
  public function paquete_dry_run_crear() {
    $this->requerirAlgunPermiso(array("migraciones.preparar", "sistema.soporte"));
    $destino = isset($_POST["destino"]) ? trim($_POST["destino"]) : "";
    $tablas = isset($_POST["tablas"]) ? json_decode($_POST["tablas"], true) : array();
    $modelo = $this->modelo("MigracionesBd");
    $respuesta = $modelo->crearPaqueteDryRun($destino, $tablas, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("migraciones", "paquete_dry_run_crear", array(
      "entidad" => "sys_migraciones_paquetes",
      "entidad_id" => isset($respuesta["depurar"]["codigo"]) ? $respuesta["depurar"]["codigo"] : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => isset($respuesta["depurar"]) ? array(
        "persistido" => isset($respuesta["depurar"]["persistido"]) ? $respuesta["depurar"]["persistido"] : false,
        "codigo" => isset($respuesta["depurar"]["codigo"]) ? $respuesta["depurar"]["codigo"] : null,
        "hash_plan" => isset($respuesta["depurar"]["hash_plan"]) ? $respuesta["depurar"]["hash_plan"] : null,
        "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null
      ) : null,
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-31
   * Proposito: validar respaldo externo antes de DDL o migraciones autorizadas.
   * Impacto: Migraciones BD; no crea archivos ni modifica BD.
   * Contrato: recibe `respaldo` por GET y devuelve validacion saneada.
   */
  public function respaldo_validar() {
    $this->requerirAlgunPermiso(array("migraciones.respaldos", "sistema.soporte"));
    $respaldo = isset($_GET["respaldo"]) ? trim($_GET["respaldo"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->validarRespaldo($respaldo));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-31
   * Proposito: entregar checklist de activacion del esquema tecnico sin ejecutar DDL.
   * Impacto: Migraciones BD; prepara respaldo/autorizacion para una aplicacion posterior.
   * Contrato: recibe `respaldo` opcional y siempre opera readonly.
   */
  public function activacion_preflight() {
    $this->requerirAlgunPermiso(array("migraciones.respaldos", "sistema.soporte"));
    $respaldo = isset($_GET["respaldo"]) ? trim($_GET["respaldo"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->preflightActivacion($respaldo));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: auditar DDL del modulo Migraciones BD.
   * Impacto: SYS; prepara tablas tecnicas propias del modulo.
   * Contrato: sin `ejecutar=1` solo devuelve SQL generado.
   */
  public function esquema_actualizar() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && $_POST["ejecutar"] == 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim($_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim($_POST["respaldo"]) : "";
      $confirmacion = isset($_POST["confirmacion"]) ? trim($_POST["confirmacion"]) : "";
      $modelo = $this->modelo("MigracionesBd");
      $validacionRespaldo = $modelo->validarRespaldo($respaldo);
      $confirmacionOk = stripos($confirmacion, "AUTORIZO CREAR ESQUEMA TECNICO MIGRACIONES BD") !== false
        && stripos($confirmacion, "sys_migraciones_") !== false;

      if ($autorizar !== "MIGRACIONES_BD_SCHEMA" || $validacionRespaldo["error"] || !$confirmacionOk) {
        $respuesta = array(
          "error" => true,
          "tipo" => "warning",
          "mensaje" => "No se puede aplicar el esquema tecnico sin token, respaldo valido y confirmacion literal",
          "depurar" => array(
            "token_ok" => $autorizar === "MIGRACIONES_BD_SCHEMA",
            "respaldo" => $validacionRespaldo["depurar"],
            "confirmacion_ok" => $confirmacionOk
          )
        );
        SesionSeguridad::registrarAuditoria("migraciones", "esquema_actualizar_bloqueado", array(
          "entidad" => "sys_migraciones_*",
          "resultado" => "bloqueado",
          "datos_despues" => $respuesta["depurar"],
          "mensaje" => $respuesta["mensaje"]
        ));
        echo json_encode($respuesta);
        return;
      }
    }

    $esquema = $this->modelo("MigracionesBdEsquema");
    $respuesta = $esquema->planActualizarMigracionesBd($ejecutar);
    SesionSeguridad::registrarAuditoria("migraciones", $ejecutar ? "esquema_actualizar" : "esquema_dry_run", array(
      "entidad" => "sys_migraciones_*",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => array(
        "ejecutar" => $ejecutar,
        "total_plan" => isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? count($respuesta["depurar"]) : 0,
        "respaldo" => $ejecutar && isset($_POST["respaldo"]) ? $_POST["respaldo"] : null
      ),
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }
}
