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
   * Fecha: 2026-08-01
   * Proposito: revisar prerequisitos operativos del modulo sin ejecutar cambios.
   * Impacto: Migraciones BD; solo lectura.
   */
  public function selfcheck() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->selfcheckOperativo());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-02
   * Proposito: generar checklist operativo consolidado sin ejecutar cambios.
   * Impacto: Migraciones BD; solo lectura.
   */
  public function checklist_operativo() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $respaldo = isset($_GET["respaldo"]) ? trim($_GET["respaldo"]) : "";
    $paquete = isset($_GET["paquete"]) ? trim($_GET["paquete"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->checklistOperativo($respaldo, $paquete));
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
   * Fecha: 2026-08-01
   * Proposito: perfilar tablas locales para decidir politica de datos.
   * Impacto: Migraciones BD; no lee filas de negocio ni modifica BD.
   */
  public function tablas_perfil_datos() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->perfilarTablasDatos());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: sugerir orden de migracion por dependencias de llaves foraneas.
   * Impacto: Migraciones BD; no lee datos ni ejecuta cambios.
   */
  public function tablas_orden_migracion() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->ordenarTablasPorDependencias());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: resumir politicas, riesgos y candidatas de datos para decision ejecutiva.
   * Impacto: Migraciones BD; solo lectura sobre metadatos.
   */
  public function resumen_decision() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->resumenDecisionMigracion());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: generar manifiesto JSON portable de preparacion de migracion.
   * Impacto: Migraciones BD; no persiste ni ejecuta cambios.
   */
  public function manifiesto_preparacion() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $destino = isset($_GET["destino"]) ? trim($_GET["destino"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->generarManifiestoPreparacion($destino));
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
   * Fecha: 2026-08-01
   * Proposito: validar compuertas antes de aplicar un paquete persistido.
   * Impacto: Migraciones BD; no ejecuta SQL.
   */
  public function paquete_preflight() {
    $this->requerirAlgunPermiso(array("migraciones.aplicar", "sistema.soporte"));
    $codigo = isset($_GET["codigo"]) ? trim($_GET["codigo"]) : "";
    $respaldo = isset($_GET["respaldo"]) ? trim($_GET["respaldo"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->preflightPaqueteAplicacion($codigo, $respaldo));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: listar paquetes persistidos de migracion.
   * Impacto: Migraciones BD; solo lectura.
   */
  public function paquetes_listar() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 50;
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->listarPaquetes($limite));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: consultar detalle read-only de un paquete persistido.
   * Impacto: Migraciones BD; no ejecuta SQL.
   */
  public function paquete_consultar() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.preparar", "sistema.soporte"));
    $codigo = isset($_GET["codigo"]) ? trim($_GET["codigo"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->consultarPaquete($codigo));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: listar ejecuciones de paquetes de migracion.
   * Impacto: Migraciones BD; solo lectura.
   */
  public function ejecuciones_listar() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.aplicar", "sistema.soporte"));
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 50;
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->listarEjecuciones($limite));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: consultar detalle read-only de una ejecucion registrada.
   * Impacto: Migraciones BD; no ejecuta SQL.
   */
  public function ejecucion_consultar() {
    $this->requerirAlgunPermiso(array("migraciones.ver", "migraciones.aplicar", "sistema.soporte"));
    $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->consultarEjecucion($id));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: simular o solicitar aplicacion controlada de un paquete persistido.
   * Impacto: Migraciones BD; ejecucion real exige respaldo, token, confirmacion literal y bandera local.
   */
  public function paquete_aplicar() {
    $this->requerirPermiso("migraciones.aplicar");
    $codigo = isset($_POST["codigo"]) ? trim($_POST["codigo"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim($_POST["respaldo"]) : "";
    $autorizar = isset($_POST["autorizar"]) ? trim($_POST["autorizar"]) : "";
    $confirmacion = isset($_POST["confirmacion"]) ? trim($_POST["confirmacion"]) : "";
    $ejecutar = isset($_POST["ejecutar"]) && $_POST["ejecutar"] == 1;
    $modelo = $this->modelo("MigracionesBd");
    $respuesta = $modelo->aplicarPaqueteControlado($codigo, $respaldo, $autorizar, $confirmacion, $this->usuarioActualId(), $ejecutar);
    SesionSeguridad::registrarAuditoria("migraciones", $ejecutar ? "paquete_aplicar" : "paquete_aplicar_simular", array(
      "entidad" => "sys_migraciones_paquetes",
      "entidad_id" => $codigo,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => array(
        "ejecutar" => $ejecutar,
        "respaldo" => $respaldo,
        "mensaje" => $respuesta["mensaje"],
        "depurar" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
      ),
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-02
   * Proposito: autorizar un paquete persistido antes de permitir aplicacion real.
   * Impacto: Migraciones BD; no ejecuta SQL, solo cambia estatus tecnico con respaldo validado.
   */
  public function paquete_autorizar() {
    $this->requerirPermiso("migraciones.aplicar");
    $codigo = isset($_POST["codigo"]) ? trim($_POST["codigo"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim($_POST["respaldo"]) : "";
    $autorizar = isset($_POST["autorizar"]) ? trim($_POST["autorizar"]) : "";
    $confirmacion = isset($_POST["confirmacion"]) ? trim($_POST["confirmacion"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    $respuesta = $modelo->autorizarPaquete($codigo, $respaldo, $autorizar, $confirmacion, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("migraciones", "paquete_autorizar", array(
      "entidad" => "sys_migraciones_paquetes",
      "entidad_id" => $codigo,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null,
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
   * Fecha: 2026-08-02
   * Proposito: listar respaldos SQL disponibles en la carpeta estandar.
   * Impacto: Migraciones BD; solo lectura de archivos externos al repo.
   */
  public function respaldos_listar() {
    $this->requerirAlgunPermiso(array("migraciones.respaldos", "sistema.soporte"));
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 50;
    $hash = isset($_GET["hash"]) && $_GET["hash"] == 1;
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->listarRespaldos($limite, $hash));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-01
   * Proposito: generar respaldo SQL local antes de DDL o paquetes.
   * Impacto: Migraciones BD; escribe archivo externo al repo y no modifica BD.
   */
  public function respaldo_generar() {
    $this->requerirAlgunPermiso(array("migraciones.respaldos", "sistema.soporte"));
    $alcance = isset($_POST["alcance"]) ? trim($_POST["alcance"]) : "migracion_bd";
    $autorizar = isset($_POST["autorizar"]) ? trim($_POST["autorizar"]) : "";
    $confirmacion = isset($_POST["confirmacion"]) ? trim($_POST["confirmacion"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    $respuesta = $modelo->generarRespaldoLocal($alcance, $autorizar, $confirmacion, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("migraciones", "respaldo_generar", array(
      "entidad" => "backup_sql",
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => isset($respuesta["depurar"]) ? array(
        "ok" => isset($respuesta["depurar"]["ok"]) ? $respuesta["depurar"]["ok"] : false,
        "archivo" => isset($respuesta["depurar"]["archivo"]) ? $respuesta["depurar"]["archivo"] : null,
        "tamano_bytes" => isset($respuesta["depurar"]["tamano_bytes"]) ? $respuesta["depurar"]["tamano_bytes"] : 0,
        "sha256" => isset($respuesta["depurar"]["sha256"]) ? $respuesta["depurar"]["sha256"] : null,
        "codigo_salida" => isset($respuesta["depurar"]["codigo_salida"]) ? $respuesta["depurar"]["codigo_salida"] : null
      ) : null,
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
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
   * Fecha: 2026-08-01
   * Proposito: preparar plan read-only de restauracion desde respaldo.
   * Impacto: Migraciones BD; no ejecuta restauracion.
   */
  public function restauracion_preflight() {
    $this->requerirAlgunPermiso(array("migraciones.respaldos", "sistema.soporte"));
    $respaldo = isset($_GET["respaldo"]) ? trim($_GET["respaldo"]) : "";
    $modelo = $this->modelo("MigracionesBd");
    echo json_encode($modelo->preflightRestauracion($respaldo));
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
