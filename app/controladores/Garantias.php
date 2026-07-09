<?php

class Garantias extends Controlador {

  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: mostrar la consola operativa inicial de politicas y pruebas de Garantias ERP.
   * Impacto: Garantias/Catalogo; permite consulta y dry-run sin cambios de configuracion.
   * Contrato: vista protegida por permiso `garantias.ver`; no escribe BD.
   */
  public function politicas() {
    $this->requerirPermiso("garantias.ver");
    $this->vista("apps/erp/garantias/politicas");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: diagnosticar cobertura tecnica del esquema de Garantias ERP.
   * Impacto: Garantias ERP; permite avanzar con auditoria sin aplicar DDL.
   * Contrato: read-only; no crea tablas ni modifica BD.
   */
  public function esquema_auditar_garantias_erp() {
    $this->requerirPermiso("sistema.soporte");
    return json_encode($this->modelo("GarantiasEsquema")->auditarGarantiasErp());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: generar o ejecutar de forma controlada el plan DDL de Garantias ERP.
   * Impacto: Garantias ERP y snapshot de ventas; no mueve inventario.
   * Contrato: dry-run por defecto; ejecutar=1 requiere token y respaldo externo.
   */
  public function esquema_actualizar_garantias_erp() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacion = $this->validarRespaldoExterno($respaldo);
      if ($autorizar !== "GARANTIAS_DDL_BASE" || !$validacion["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL. Falta autorizacion explicita o respaldo externo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "GARANTIAS_DDL_BASE",
              "respaldo" => "Ruta o referencia fuera del proyecto"
            ),
            "validacion_respaldo" => $validacion
          )
        ));
      }
      SesionSeguridad::registrarAuditoria("garantias", "esquema_actualizar_garantias_erp", array(
        "resultado" => "solicitado",
        "mensaje" => "Ejecucion DDL Garantias ERP autorizada",
        "datos_despues" => array("respaldo" => $respaldo)
      ));
    }
    return json_encode($this->modelo("GarantiasEsquema")->planActualizarGarantiasErp($ejecutar));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: resolver garantia vigente de un SKU para Catalogo, POS o Postventa.
   * Impacto: Garantias ERP; centraliza regla de precedencia y evita duplicacion por modulo.
   * Contrato: read-only; si falta esquema devuelve alerta y `sin_garantia`.
   */
  public function resolver_sku_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->resolverGarantiaSku($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: simular snapshots de garantia para partidas de venta sin confirmar operacion.
   * Impacto: Ventas/POS; prepara integracion futura para guardar snapshot por partida.
   * Contrato: dry-run; no crea ventas, no guarda garantias y no mueve inventario.
   */
  public function venta_snapshot_dryrun_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->ventaSnapshotDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: consultar elegibilidad preliminar de garantia sin crear reclamo.
   * Impacto: Postventa/Garantias; prepara consulta por venta, ticket o snapshot.
   * Contrato: read-only; no crea reclamo ni devolucion.
   */
  public function elegibilidad_consultar_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->elegibilidadConsultar($_REQUEST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: listar politicas de garantia sin modificar configuracion.
   * Impacto: Garantias/Catalogo; alimenta futura UI de politicas.
   * Contrato: read-only.
   */
  public function politicas_listar_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->listarPoliticas($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: listar reglas de asignacion de politicas de garantia para auditoria operativa.
   * Impacto: Garantias/Catalogo; muestra alcance de reglas sin permitir cambios masivos.
   * Contrato: read-only.
   */
  public function politicas_reglas_listar_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->listarReglasPoliticas($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: buscar SKUs desde la consola de Garantias sin exponer reglas internas de Catalogo.
   * Impacto: Garantias/Catalogo; facilita pruebas por SKU real.
   * Contrato: read-only.
   */
  public function skus_buscar_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->buscarSkus($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: buscar referencias para asignar politicas por SKU, producto, categoria, marca o proveedor.
   * Impacto: Garantias/Catalogo; evita captura manual de IDs internos al validar reglas.
   * Contrato: read-only; no crea reglas ni modifica catalogos.
   */
  public function referencias_buscar_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->buscarReferenciasRegla($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: auditar cobertura de politicas sobre SKUs activos antes de asignaciones masivas.
   * Impacto: Garantias/Catalogo; identifica pendientes sin modificar reglas.
   * Contrato: read-only.
   */
  public function cobertura_skus_erp() {
    $this->requerirPermiso("garantias.ver");
    return json_encode($this->modelo("GarantiasErp")->auditarCoberturaSkus($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: validar captura de politica sin guardar datos.
   * Impacto: Garantias/Catalogo; prepara guardado real con reglas claras.
   * Contrato: dry-run; no inserta ni actualiza BD.
   */
  public function politica_dryrun_erp() {
    $this->requerirPermiso("garantias.politicas");
    return json_encode($this->modelo("GarantiasErp")->politicaDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: validar regla de asignacion de garantia sin guardar datos.
   * Impacto: Garantias/Catalogo; protege precedencia antes de persistir reglas.
   * Contrato: dry-run; no inserta ni actualiza BD.
   */
  public function politica_regla_dryrun_erp() {
    $this->requerirPermiso("garantias.politicas");
    return json_encode($this->modelo("GarantiasErp")->politicaReglaDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: previsualizar impacto de una regla de garantia antes de guardarla.
   * Impacto: Garantias/Catalogo; evita asignaciones por categoria/marca/proveedor sin evidencia.
   * Contrato: read-only/dry-run.
   */
  public function politica_regla_impacto_erp() {
    $this->requerirPermiso("garantias.politicas");
    return json_encode($this->modelo("GarantiasErp")->previsualizarImpactoRegla($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: guardar politica de garantia con permiso operativo y auditoria.
   * Impacto: Garantias/Catalogo; cambia reglas que Ventas usara como snapshot.
   * Contrato: escribe BD; no requiere respaldo manual porque es CRUD operativo normal.
   */
  public function politica_guardar_erp() {
    $this->requerirPermiso("garantias.politicas");
    $_POST["usuario_id"] = $this->usuarioActualId();
    SesionSeguridad::registrarAuditoria("garantias", "politica_guardar_erp", array(
      "resultado" => "solicitado",
      "mensaje" => "Guardado operativo de politica",
      "datos_despues" => array(
        "id_garantia_politica" => isset($_POST["id_garantia_politica"]) ? $_POST["id_garantia_politica"] : null,
        "codigo" => isset($_POST["codigo"]) ? $_POST["codigo"] : null
      )
    ));
    return json_encode($this->modelo("GarantiasErp")->guardarPolitica($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: guardar regla de asignacion de garantia con permiso operativo y auditoria.
   * Impacto: Garantias/Catalogo; determina precedencia por SKU/producto/categoria/marca/proveedor.
   * Contrato: escribe BD; no requiere respaldo manual porque es CRUD operativo normal.
   */
  public function politica_regla_guardar_erp() {
    $this->requerirPermiso("garantias.politicas");
    $_POST["usuario_id"] = $this->usuarioActualId();
    SesionSeguridad::registrarAuditoria("garantias", "politica_regla_guardar_erp", array(
      "resultado" => "solicitado",
      "mensaje" => "Guardado operativo de regla de garantia",
      "datos_despues" => array(
        "id_regla_garantia" => isset($_POST["id_regla_garantia"]) ? $_POST["id_regla_garantia"] : null,
        "id_garantia_politica" => isset($_POST["id_garantia_politica"]) ? $_POST["id_garantia_politica"] : null,
        "ambito" => isset($_POST["ambito"]) ? $_POST["ambito"] : null,
        "id_referencia" => isset($_POST["id_referencia"]) ? $_POST["id_referencia"] : null
      )
    ));
    return json_encode($this->modelo("GarantiasErp")->guardarPoliticaRegla($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: activar o desactivar politicas de garantia sin borrado fisico.
   * Impacto: Garantias/Catalogo; una politica inactiva deja de participar en resolucion futura.
   * Contrato: escribe BD; requiere permiso `garantias.politicas` y registra auditoria.
   */
  public function politica_estatus_erp() {
    $this->requerirPermiso("garantias.politicas");
    $_POST["usuario_id"] = $this->usuarioActualId();
    SesionSeguridad::registrarAuditoria("garantias", "politica_estatus_erp", array(
      "resultado" => "solicitado",
      "mensaje" => "Cambio de estatus de politica de garantia",
      "datos_despues" => array(
        "id_garantia_politica" => isset($_POST["id_garantia_politica"]) ? $_POST["id_garantia_politica"] : null,
        "estatus" => isset($_POST["estatus"]) ? $_POST["estatus"] : null
      )
    ));
    return json_encode($this->modelo("GarantiasErp")->cambiarEstatusPolitica($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: activar o desactivar reglas de garantia sin borrado fisico.
   * Impacto: Garantias/Catalogo; una regla inactiva deja de participar en resolucion futura.
   * Contrato: escribe BD; requiere permiso `garantias.politicas` y registra auditoria.
   */
  public function politica_regla_estatus_erp() {
    $this->requerirPermiso("garantias.politicas");
    $_POST["usuario_id"] = $this->usuarioActualId();
    SesionSeguridad::registrarAuditoria("garantias", "politica_regla_estatus_erp", array(
      "resultado" => "solicitado",
      "mensaje" => "Cambio de estatus de regla de garantia",
      "datos_despues" => array(
        "id_regla_garantia" => isset($_POST["id_regla_garantia"]) ? $_POST["id_regla_garantia"] : null,
        "estatus" => isset($_POST["estatus"]) ? $_POST["estatus"] : null
      )
    ));
    return json_encode($this->modelo("GarantiasErp")->cambiarEstatusRegla($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: simular creacion de reclamo de garantia sin crear folio ni mover inventario.
   * Impacto: Postventa/Garantias; valida contrato antes de habilitar transacciones reales.
   * Contrato: dry-run; no escribe BD.
   */
  public function reclamo_dryrun_erp() {
    $this->requerirPermiso("garantias.reclamos.crear");
    return json_encode($this->modelo("GarantiasErp")->reclamoDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-27
   * Proposito: validar que la referencia de respaldo no apunte dentro del proyecto antes de permitir DDL.
   * Impacto: seguridad operativa para cambios de esquema en Garantias ERP.
   * Contrato: no crea respaldo; solo valida que el dato exista y no sea ruta interna del proyecto.
   */
  private function validarRespaldoExterno($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "") {
      return array("ok" => false, "mensaje" => "Indica respaldo externo");
    }

    $normalizado = str_replace("/", "\\", strtolower($respaldo));
    $raizProyecto = str_replace("/", "\\", strtolower(realpath(__DIR__ . "/../..")));
    if ($raizProyecto && strpos($normalizado, $raizProyecto) === 0) {
      return array("ok" => false, "mensaje" => "El respaldo debe estar fuera del proyecto");
    }

    return array("ok" => true, "mensaje" => "Referencia de respaldo aceptada");
  }

}
