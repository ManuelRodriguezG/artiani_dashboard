<?php

class Crm extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: abrir consola inicial CRM Clientes en modo auditoria/read-only.
   * Impacto: permite revisar fuentes, duplicados y esquema antes de autorizar DDL.
   * Contrato: no escribe BD; la vista consume endpoints dry-run/read-only.
   */
  public function clientes() {
    $this->requerirPermiso("crm.ver");
    $this->vista("apps/crm/clientes/listado");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: abrir consola operativa CRM Seguimiento en modo lectura.
   * Impacto: separa tareas e interacciones del listado principal de clientes.
   * Contrato: no escribe BD; la vista consume endpoints read-only y dry-run.
   */
  public function seguimiento() {
    $this->requerirPermiso("crm.ver");
    $this->vista("apps/crm/seguimiento/index");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: abrir consola CRM Recompensas en modo lectura.
   * Impacto: separa programas, cuentas y movimientos del listado principal de clientes.
   * Contrato: no otorga puntos, no redime puntos y no cambia saldos.
   */
  public function recompensas() {
    $this->requerirPermiso("crm.ver");
    $this->vista("apps/crm/recompensas/index");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: abrir ficha operativa CRM de un cliente canonico.
   * Impacto: concentra datos completos fuera del cobro POS.
   * Contrato: vista de trabajo; escrituras reales quedan bloqueadas por endpoints separados.
   */
  public function cliente($idClienteCrm = 0) {
    $this->requerirPermiso("crm.ver");
    $this->vista("apps/crm/clientes/ficha", array("id_cliente_crm" => intval($idClienteCrm)));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: exponer diagnostico read-only del dominio CRM Clientes.
   * Impacto: permite separar Clientes de POS/Ventas y Ecommerce antes de migrar datos.
   * Contrato: no escribe BD y requiere permiso de consulta CRM.
   */
  public function clientes_diagnostico_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->diagnosticoDominioClientes());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: listar clientes CRM canonicos para pantalla operativa.
   * Impacto: evita usar legacy/POS como fuente de listado principal.
   * Contrato: read-only.
   */
  public function clientes_listar_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->listarClientesCanonicos($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: exponer cola read-only de calidad operativa CRM.
   * Impacto: permite priorizar fichas incompletas sin escribir datos ni migrar legacy.
   * Contrato: read-only.
   */
  public function clientes_calidad_cola_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->colaCalidadOperativa($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: consultar ficha completa CRM en modo lectura.
   * Impacto: muestra identidad, identificadores, contacto, fiscal, notas e historial.
   * Contrato: read-only.
   */
  public function cliente_consultar_erp() {
    $this->requerirPermiso("crm.ver");
    $idClienteCrm = isset($_GET["id_cliente_crm"]) ? intval($_GET["id_cliente_crm"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->consultarFicha($idClienteCrm));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: validar edicion basica de ficha CRM sin escribir.
   * Impacto: prepara flujo robusto de actualizacion con permisos y auditoria.
   * Contrato: dry-run; no actualiza BD.
   */
  public function cliente_basico_guardar_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    return json_encode($this->modelo("ClientesCrm")->fichaBasicaGuardarDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: guardar edicion basica CRM solo con respaldo y autorizacion explicita.
   * Impacto: corrige identidad operativa y registra evento de cambio.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_FICHA_BASICA y respaldo valido.
   */
  public function cliente_basico_guardar_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_FICHA_BASICA" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se actualizo ficha CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array(
            "autorizar" => "CRM_CLIENTES_FICHA_BASICA",
            "respaldo" => "RUTA_O_REFERENCIA"
          ),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo actualiza datos basicos del cliente CRM indicado.",
            "No modifica identificadores.",
            "No modifica contactos, fiscales ni direcciones.",
            "No migra legacy ni ecommerce."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->fichaBasicaGuardarAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: validar complementos de ficha CRM sin escribir.
   * Impacto: prepara contactos, direcciones, fiscales, consentimientos y notas.
   * Contrato: dry-run; no inserta BD.
   */
  public function cliente_complemento_guardar_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $tipo = isset($_POST["tipo_complemento"]) ? trim((string) $_POST["tipo_complemento"]) : "";
    return json_encode($this->modelo("ClientesCrm")->complementoGuardarDryRun($tipo, $_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: crear complemento CRM solo con respaldo y autorizacion explicita.
   * Impacto: crece ficha completa con evento de trazabilidad.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_COMPLEMENTO y respaldo valido.
   */
  public function cliente_complemento_guardar_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_COMPLEMENTO" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se creo complemento CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_COMPLEMENTO", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea un complemento del cliente CRM indicado.",
            "No modifica identificadores principales.",
            "No migra legacy ni ecommerce.",
            "No modifica ventas historicas."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    $tipo = isset($_POST["tipo_complemento"]) ? trim((string) $_POST["tipo_complemento"]) : "";
    return json_encode($this->modelo("ClientesCrm")->complementoGuardarAutorizado($tipo, $_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar tarea de seguimiento CRM sin escribir.
   * Impacto: prepara tareas persistentes desde cola de calidad sin crear registros.
   * Contrato: dry-run; no inserta tarea ni notificacion.
   */
  public function cliente_tarea_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->tareaSeguimientoDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear tarea CRM solo con respaldo y autorizacion explicita.
   * Impacto: convierte cola de calidad en seguimiento persistente auditable.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_TAREA y DDL seguimiento aplicado.
   */
  public function cliente_tarea_crear_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_TAREA" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se creo tarea CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_TAREA", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea una tarea CRM para el cliente indicado.",
            "Requiere tabla crm_clientes_tareas creada previamente.",
            "No modifica datos del cliente.",
            "No crea notificaciones SYS.",
            "No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->tareaSeguimientoCrearAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: listar tareas CRM en modo lectura.
   * Impacto: prepara bandeja operativa sin modificar tareas ni clientes.
   * Contrato: read-only.
   */
  public function clientes_tareas_listar_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->tareasSeguimientoListar($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: resumir preparacion comercial CRM sin escribir.
   * Impacto: separa segmentacion/listas/recompensas del flujo POS.
   * Contrato: read-only.
   */
  public function clientes_comercial_resumen_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->resumenComercialClientes($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: exponer reportes operativos CRM en modo lectura.
   * Impacto: mide contactabilidad, elegibilidad comercial y pendientes sin escribir BD.
   * Contrato: read-only.
   */
  public function clientes_reportes_operativos_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->reportesOperativosClientes($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar preferencias de contacto CRM sin escribir.
   * Impacto: prepara canales/horarios preferidos para campanas, recompensas y postventa.
   * Contrato: dry-run; no modifica condiciones ni consentimientos.
   */
  public function cliente_preferencias_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->preferenciasContactoDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: guardar preferencias CRM solo con respaldo y autorizacion explicita.
   * Impacto: registra preferencias comerciales sin tocar consentimiento ni POS.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_PREFERENCIAS y DDL comercial aplicado.
   */
  public function cliente_preferencias_guardar_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_PREFERENCIAS" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se guardaron preferencias CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_PREFERENCIAS", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo guarda preferencias en crm_clientes_condiciones.",
            "No otorga consentimiento legal.",
            "No modifica contactos ni datos del cliente.",
            "No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->preferenciasContactoGuardarAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: resumir recompensas CRM sin escribir.
   * Impacto: prepara valor de cliente y beneficios sin depender aun de ventas.
   * Contrato: read-only.
   */
  public function clientes_recompensas_resumen_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->resumenRecompensasClientes($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: listar detalle de recompensas CRM sin escribir.
   * Impacto: alimenta pantalla dedicada de programas, cuentas y movimientos.
   * Contrato: read-only; no otorga/redime puntos ni modifica clientes.
   */
  public function clientes_recompensas_detalle_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->detalleRecompensasClientes($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar movimiento de recompensas CRM sin escribir.
   * Impacto: define contrato futuro para acumulacion/redencion/ajuste de puntos.
   * Contrato: dry-run; no modifica saldos ni crea movimientos.
   */
  public function cliente_recompensa_movimiento_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->recompensaMovimientoDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: aplicar movimiento de recompensas CRM solo con respaldo y autorizacion explicita.
   * Impacto: actualiza saldo de cuenta e inserta movimiento; no conecta POS/Ventas.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO y respaldo valido.
   */
  public function cliente_recompensa_movimiento_crear_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se creo movimiento de recompensas CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea un movimiento manual/controlado de recompensas CRM.",
            "Actualiza saldo de la cuenta CRM de recompensas.",
            "No conecta POS ni ventas.",
            "No modifica datos maestros del cliente.",
            "No toca ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->recompensaMovimientoCrearAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar programa de recompensas CRM sin escribir.
   * Impacto: permite revisar politica base antes de crear programas reales.
   * Contrato: dry-run; no crea programa, cuentas ni movimientos.
   */
  public function cliente_recompensa_programa_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->recompensaProgramaDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear programa de recompensas CRM solo con respaldo y autorizacion explicita.
   * Impacto: habilita catalogo de programas sin tocar saldos ni ventas.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_RECOMPENSAS_PROGRAMA y respaldo valido.
   */
  public function cliente_recompensa_programa_crear_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_RECOMPENSAS_PROGRAMA" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se creo programa de recompensas CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_RECOMPENSAS_PROGRAMA", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea un programa CRM de recompensas.",
            "No crea cuentas de clientes.",
            "No crea movimientos.",
            "No otorga ni redime puntos.",
            "No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->recompensaProgramaCrearAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar cuenta de recompensas CRM sin escribir.
   * Impacto: prepara elegibilidad por cliente/programa con saldo cero.
   * Contrato: dry-run; no crea cuenta, movimientos ni puntos.
   */
  public function cliente_recompensa_cuenta_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->recompensaCuentaDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear cuenta de recompensas CRM solo con respaldo y autorizacion explicita.
   * Impacto: crea cuenta con saldo cero; no otorga puntos.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_RECOMPENSAS_CUENTA y respaldo valido.
   */
  public function cliente_recompensa_cuenta_crear_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_RECOMPENSAS_CUENTA" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se creo cuenta de recompensas CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_RECOMPENSAS_CUENTA", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea una cuenta CRM de recompensas con saldo cero.",
            "No crea movimientos.",
            "No otorga ni redime puntos.",
            "No modifica datos maestros del cliente.",
            "No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->recompensaCuentaCrearAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar asignacion de segmento CRM sin escribir.
   * Impacto: prepara segmentacion comercial sobre cliente canonico.
   * Contrato: dry-run; no inserta relacion ni modifica cliente.
   */
  public function cliente_segmento_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->segmentoAsignarDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-16
   * Proposito: listar catalogo configurable de segmentos CRM sin escribir.
   * Impacto: permite usar tipos de cliente en Listas de precios sin hardcodear valores.
   * Contrato: read-only; no crea ni modifica segmentos.
   */
  public function segmentos_catalogo_listar_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->segmentosCatalogoReadOnly($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-16
   * Proposito: validar alta/edicion/cancelacion de segmento CRM sin escribir.
   * Impacto: prepara catalogo de tipos de cliente configurable para listas y CRM comercial.
   * Contrato: dry-run; no modifica segmentos ni clientes.
   */
  public function segmento_catalogo_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    return json_encode($this->modelo("ClientesCrm")->segmentoCatalogoDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-16
   * Proposito: guardar segmento CRM configurable solo con respaldo y autorizacion explicita.
   * Impacto: permite crear, editar, pausar o cancelar segmentos sin tocar listas ni ventas.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_SEGMENTO_CATALOGO y respaldo valido.
   */
  public function segmento_catalogo_guardar_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_SEGMENTO_CATALOGO" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se guardo segmento CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_SEGMENTO_CATALOGO", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea/edita/pausa/cancela un segmento CRM.",
            "No asigna clientes al segmento.",
            "No asigna listas de precios.",
            "No modifica ventas pasadas.",
            "No toca POS, ecommerce, recompensas, garantias ni legacy."
          )
        )
      ));
    }
    return json_encode($this->modelo("ClientesCrm")->segmentoCatalogoGuardarAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: asignar segmento CRM solo con respaldo y autorizacion explicita.
   * Impacto: activa segmentacion comercial sin tocar POS ni ventas.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_SEGMENTO y respaldo valido.
   */
  public function cliente_segmento_asignar_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_SEGMENTO" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se asigno segmento CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_SEGMENTO", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea una relacion cliente-segmento CRM.",
            "Opcionalmente actualiza segmento default del cliente indicado.",
            "No modifica listas de precios.",
            "No crea recompensas.",
            "No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->segmentoAsignarAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar cierre/cancelacion de tarea CRM sin escribir.
   * Impacto: evita crear tareas sin ruta controlada de resolucion.
   * Contrato: dry-run; no modifica tarea ni cliente.
   */
  public function cliente_tarea_estatus_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->tareaEstatusDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: cerrar o cancelar una tarea CRM solo con autorizacion explicita.
   * Impacto: completa ciclo de vida de seguimiento sin tocar cliente ni POS.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_TAREA_ESTATUS y respaldo valido.
   */
  public function cliente_tarea_estatus_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_TAREA_ESTATUS" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se actualizo tarea CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_TAREA_ESTATUS", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo cambia estatus de una tarea CRM.",
            "No modifica datos del cliente.",
            "No crea interacciones automaticamente.",
            "No crea notificaciones SYS.",
            "No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->tareaEstatusAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: validar una interaccion CRM sin escribir.
   * Impacto: prepara historial operativo de llamadas, WhatsApp, visitas y correos.
   * Contrato: dry-run; no inserta interaccion ni modifica tareas/clientes.
   */
  public function cliente_interaccion_dryrun_erp() {
    $this->requerirPermiso("crm.editar");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->interaccionDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: crear interaccion CRM solo con respaldo y autorizacion explicita.
   * Impacto: registra historial operativo auditable sin tocar POS ni ventas.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_INTERACCION y DDL seguimiento aplicado.
   */
  public function cliente_interaccion_crear_autorizado_erp() {
    $this->requerirPermiso("crm.editar");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_INTERACCION" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se creo interaccion CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array("autorizar" => "CRM_CLIENTES_INTERACCION", "respaldo" => "RUTA_O_REFERENCIA"),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea una interaccion CRM para el cliente indicado.",
            "Requiere tabla crm_clientes_interacciones creada previamente.",
            "No modifica datos del cliente.",
            "No cierra ni crea tareas.",
            "No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->interaccionCrearAutorizada($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: listar interacciones CRM en modo lectura.
   * Impacto: muestra historial operativo sin modificar seguimiento.
   * Contrato: read-only.
   */
  public function clientes_interacciones_listar_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->interaccionesListar($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: simular busqueda express de clientes por identificador o nombre.
   * Impacto: prepara contrato CRM para POS sin usar legacy como fuente canonica.
   * Contrato: read-only; no crea cliente aunque no exista coincidencia.
   */
  public function clientes_buscar_express_dryrun_erp() {
    $this->requerirPermiso("crm.ver");
    return json_encode($this->modelo("ClientesCrm")->buscarExpressDryRun($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: validar alta express CRM sin escribir datos.
   * Impacto: permite que POS y CRM usen el mismo contrato de cliente canonico.
   * Contrato: dry-run; no crea cliente, identificador, consentimiento ni evento.
   */
  public function clientes_alta_rapida_dryrun_erp() {
    $this->requerirPermiso("crm.crear");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->altaRapidaDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: crear alta express CRM solo con respaldo y autorizacion explicita.
   * Impacto: habilita POS/CRM sobre identidad canonica sin migrar legacy.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_ALTA_EXPRESS y respaldo valido.
   */
  public function clientes_alta_rapida_crear_autorizado_erp() {
    $this->requerirPermiso("crm.crear");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_ALTA_EXPRESS" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se creo cliente CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array(
            "autorizar" => "CRM_CLIENTES_ALTA_EXPRESS",
            "respaldo" => "RUTA_O_REFERENCIA"
          ),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Este apply solo crea un cliente express CRM.",
            "No migra clientes legacy.",
            "No vincula ecommerce.",
            "No crea ficha completa ni datos fiscales."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    return json_encode($this->modelo("ClientesCrm")->altaRapidaCrearAutorizado($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: auditar fuentes legacy/POS/ventas antes de migrar clientes.
   * Impacto: permite dimensionar calidad de datos y duplicados sin tocar BD.
   * Contrato: read-only.
   */
  public function clientes_fuentes_auditar_erp() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrm")->auditarFuentesClientes());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: generar plan de migracion CRM Clientes sin ejecutar cambios.
   * Impacto: establece secuencia segura para pasar de legacy/POS a CRM canonico.
   * Contrato: dry-run; requiere respaldo/autorizacion solo en una fase posterior de apply.
   */
  public function clientes_migracion_plan_dryrun_erp() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrm")->planMigracionClientesDryRun());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: detallar duplicados probables antes de migrar o fusionar clientes.
   * Impacto: evita imponer unicidad CRM sobre datos legacy ambiguos.
   * Contrato: read-only; no marca ni fusiona.
   */
  public function clientes_duplicados_dryrun_erp() {
    $this->requerirPermiso("crm.auditoria");
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 50;
    return json_encode($this->modelo("ClientesCrm")->duplicadosProbablesDryRun($limite));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: revisar detalle de un grupo duplicado sin marcar ni fusionar.
   * Impacto: prepara decisiones humanas antes de migracion legacy.
   * Contrato: read-only.
   */
  public function clientes_duplicado_revision_dryrun_erp() {
    $this->requerirPermiso("crm.auditoria");
    $identificador = isset($_GET["identificador"]) ? trim((string) $_GET["identificador"]) : "";
    return json_encode($this->modelo("ClientesCrm")->duplicadoRevisionDryRun($identificador));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: previsualizar mapeo de legacy crm_clientes hacia CRM canonico.
   * Impacto: permite revisar calidad de datos antes de autorizar migracion.
   * Contrato: dry-run; no crea registros.
   */
  public function clientes_migracion_preview_dryrun_erp() {
    $this->requerirPermiso("crm.auditoria");
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 25;
    return json_encode($this->modelo("ClientesCrm")->previewMigracionLegacyDryRun($limite));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: preparar lote de migracion legacy como borrador no aplicado.
   * Impacto: separa migrables, bloqueados y pendientes antes de pedir autorizacion.
   * Contrato: dry-run; no inserta ni vincula.
   */
  public function clientes_migracion_borrador_dryrun_erp() {
    $this->requerirPermiso("crm.auditoria");
    $offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0;
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 50;
    return json_encode($this->modelo("ClientesCrm")->migracionLegacyBorradorDryRun($offset, $limite));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: aplicar migracion legacy CRM solo con respaldo y autorizacion explicita.
   * Impacto: crea clientes canonicos, identificadores, vinculos y eventos para lotes migrables.
   * Contrato: escribe BD; requiere token CRM_CLIENTES_MIGRACION_LEGACY y respaldo valido.
   */
  public function clientes_migracion_aplicar_autorizado_erp() {
    $this->requerirPermiso("crm.auditoria");
    $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
    $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
    $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
    if ($autorizar !== "CRM_CLIENTES_MIGRACION_LEGACY" || !$validacionRespaldo["ok"]) {
      return json_encode(array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => "No se migro legacy CRM. Falta autorizacion explicita o respaldo valido.",
        "depurar" => array(
          "requerido" => array(
            "autorizar" => "CRM_CLIENTES_MIGRACION_LEGACY",
            "respaldo" => "RUTA_O_REFERENCIA",
            "offset" => "OFFSET",
            "limite" => "LIMITE"
          ),
          "validacion_respaldo" => $validacionRespaldo,
          "reglas" => array(
            "Solo migra registros con estado migrable_borrador.",
            "No migra duplicados legacy.",
            "No modifica la tabla legacy.",
            "No toca ventas, POS ni ecommerce.",
            "Crea vinculo externo y evento de migracion por cliente."
          )
        )
      ));
    }
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    $offset = isset($_POST["offset"]) ? intval($_POST["offset"]) : 0;
    $limite = isset($_POST["limite"]) ? intval($_POST["limite"]) : 25;
    return json_encode($this->modelo("ClientesCrm")->migracionLegacyAplicarAutorizado($offset, $limite, $_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: auditar tablas CRM canonicas y fuentes antiguas de clientes.
   * Impacto: sirve como preflight antes de cualquier DDL o migracion.
   * Contrato: solo lectura.
   */
  public function esquema_auditar_clientes_crm() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrmEsquema")->auditarClientesCrm());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: generar plan DDL CRM Clientes sin ejecutarlo.
   * Impacto: permite revisar el modelo canonico antes de solicitar respaldo y autorizacion.
   * Contrato: no ejecuta DDL desde este endpoint.
   */
  public function esquema_plan_clientes_crm() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarClientesCrm(false));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: generar plan DDL de seguimiento CRM sin ejecutarlo.
   * Impacto: prepara tareas/interacciones como capa operativa posterior a ficha y calidad.
   * Contrato: read-only; no crea tablas ni tareas.
   */
  public function esquema_plan_clientes_seguimiento_crm() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarSeguimientoClientesCrm(false));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: generar plan DDL comercial CRM sin ejecutarlo.
   * Impacto: prepara condiciones/preferencias del cliente fuera de POS.
   * Contrato: read-only; no crea tablas ni actualiza clientes.
   */
  public function esquema_plan_clientes_comercial_crm() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarComercialClientesCrm(false));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: generar plan DDL recompensas CRM sin ejecutarlo.
   * Impacto: prepara cuentas y movimientos de puntos por cliente canonico.
   * Contrato: read-only; no crea tablas ni puntos.
   */
  public function esquema_plan_clientes_recompensas_crm() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarRecompensasClientesCrm(false));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-06
   * Proposito: generar plan DDL saldos/cuenta corriente CRM sin ejecutarlo.
   * Impacto: prepara saldo favor monetario de clientes sin mezclarlo con recompensas.
   * Contrato: read-only; no crea tablas, saldos ni movimientos.
   */
  public function esquema_plan_clientes_saldos_crm() {
    $this->requerirPermiso("crm.auditoria");
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarSaldosClientesCrm(false));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: aplicar DDL CRM Clientes solo con respaldo y autorizacion explicita.
   * Impacto: crea esquema canonico CRM; no migra clientes legacy ni POS.
   * Contrato: requiere token CRM_CLIENTES_DDL_BASE y respaldo valido.
   */
  public function esquema_actualizar_clientes_crm() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
      if ($autorizar !== "CRM_CLIENTES_DDL_BASE" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL CRM. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "CRM_CLIENTES_DDL_BASE",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "Este apply solo crea esquema CRM canonico.",
              "No migra clientes legacy.",
              "No vincula clientes POS/ecommerce.",
              "La migracion sera otro apply separado."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarClientesCrm($ejecutar));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: aplicar DDL de seguimiento CRM solo con respaldo y autorizacion explicita.
   * Impacto: crea tablas para interacciones y tareas; no crea tareas reales.
   * Contrato: requiere token CRM_CLIENTES_SEGUIMIENTO_DDL y respaldo valido.
   */
  public function esquema_actualizar_clientes_seguimiento_crm() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
      if ($autorizar !== "CRM_CLIENTES_SEGUIMIENTO_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL CRM Seguimiento. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "CRM_CLIENTES_SEGUIMIENTO_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "Este apply solo crea tablas CRM de seguimiento.",
              "No crea tareas reales.",
              "No modifica clientes existentes.",
              "No toca POS, ventas, ecommerce, garantias ni legacy."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarSeguimientoClientesCrm($ejecutar));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: aplicar DDL comercial CRM solo con respaldo y autorizacion explicita.
   * Impacto: crea tabla de condiciones comerciales; no cambia clientes ni precios.
   * Contrato: requiere token CRM_CLIENTES_COMERCIAL_DDL y respaldo valido.
   */
  public function esquema_actualizar_clientes_comercial_crm() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
      if ($autorizar !== "CRM_CLIENTES_COMERCIAL_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL CRM Comercial. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "CRM_CLIENTES_COMERCIAL_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "Este apply solo crea tabla CRM de condiciones comerciales.",
              "No modifica clientes existentes.",
              "No crea segmentos ni listas de precios.",
              "No toca POS, ventas, ecommerce, garantias ni legacy."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarComercialClientesCrm($ejecutar));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: aplicar DDL de recompensas CRM solo con respaldo y autorizacion explicita.
   * Impacto: crea tablas de programas, cuentas y movimientos; no otorga puntos.
   * Contrato: requiere token CRM_CLIENTES_RECOMPENSAS_DDL y respaldo valido.
   */
  public function esquema_actualizar_clientes_recompensas_crm() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
      if ($autorizar !== "CRM_CLIENTES_RECOMPENSAS_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL CRM Recompensas. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "CRM_CLIENTES_RECOMPENSAS_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "Este apply solo crea tablas CRM de recompensas.",
              "No crea programas reales.",
              "No crea cuentas ni movimientos.",
              "No otorga ni redime puntos.",
              "No toca POS, ventas, ecommerce, garantias ni legacy."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarRecompensasClientesCrm($ejecutar));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-06
   * Proposito: aplicar DDL CRM saldos solo con respaldo y autorizacion explicita.
   * Impacto: crea tablas de cuenta corriente monetaria; no crea saldos ni movimientos reales.
   * Contrato: requiere token CRM_CLIENTES_SALDOS_DDL y respaldo valido.
   */
  public function esquema_actualizar_clientes_saldos_crm() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoCrm($respaldo);
      if ($autorizar !== "CRM_CLIENTES_SALDOS_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL CRM Saldos. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "CRM_CLIENTES_SALDOS_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "Este apply solo crea tablas CRM de saldos/cuenta corriente.",
              "No crea cuentas reales.",
              "No crea movimientos.",
              "No convierte decisiones POS en saldo.",
              "No mueve caja ni inventario.",
              "No toca recompensas, ecommerce, garantias ni legacy."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("ClientesCrmEsquema")->planActualizarSaldosClientesCrm($ejecutar));
  }

  private function validarRespaldoCrm($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
      $existe = file_exists($respaldo);
      $legible = $existe && is_readable($respaldo);
      $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
      "ok" => $okReferencia && $okRuta,
      "referencia_presente" => $okReferencia,
      "parece_ruta_local" => $esRutaLocal,
      "archivo_existe" => $esRutaLocal ? $existe : null,
      "archivo_legible" => $esRutaLocal ? $legible : null,
      "tamano_bytes" => $tamano
    );
  }
}
