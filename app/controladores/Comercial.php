<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: concentrar las pantallas comerciales ERP sin acoplarlas visualmente a Ventas/POS.
 * Impacto: agrega la ruta conceptual /comercial/listas_precios y conserva los endpoints tecnicos actuales en Ventas.
 * Contrato: el precio sigue resolviendose en backend; este controlador solo organiza navegacion y vistas.
 */
class Comercial extends Controlador
{
    public function index()
    {
        $this->listas_precios();
    }

    public function listas_precios()
    {
        $this->requerirPermiso('ventas.listas.ver');
        $this->vista('apps/erp/ventas/listas_precios_inicio', array(
            'modulo' => 'comercial',
            'ruta_canonica' => '/comercial/listas_precios'
        ));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-20.
     * Proposito: separar el editor operativo del listado de Listas de precios.
     * Impacto: permite abrir una pantalla dedicada para crear una lista nueva sin perder contexto.
     * Contrato: la vista reutiliza backend Comercial; guardar sigue protegido por permisos y auditoria.
     */
    public function listas_precios_nueva()
    {
        $this->requerirPermiso('ventas.listas.crear');
        $this->vista('apps/erp/ventas/listas_precios', array(
            'modulo' => 'comercial',
            'ruta_canonica' => '/comercial/listas_precios_nueva',
            'modo_editor' => 'nueva'
        ));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-20.
     * Proposito: abrir una lista existente en un editor dedicado por id.
     * Impacto: evita que el usuario mezcle consulta general con edicion de una lista concreta.
     * Contrato: la seleccion inicial se hace por query `id_lista_precio`; permisos finos aplican al guardar.
     */
    public function listas_precios_editar()
    {
        $this->requerirPermiso('ventas.listas.ver');
        $this->vista('apps/erp/ventas/listas_precios', array(
            'modulo' => 'comercial',
            'ruta_canonica' => '/comercial/listas_precios_editar',
            'modo_editor' => 'editar'
        ));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-20.
     * Proposito: mostrar manual operativo de Comercial/Listas de precios.
     * Impacto: facilita arranque fase 1 y capacitacion sin depender de documentacion tecnica.
     * Contrato: vista read-only; no modifica listas, POS, CRM ni ecommerce.
     */
    public function listas_precios_manual()
    {
        $this->requerirPermiso('ventas.listas.ver');
        $this->vista('apps/erp/ventas/listas_precios_manual', array(
            'modulo' => 'comercial',
            'ruta_canonica' => '/comercial/listas_precios_manual'
        ));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-13.
     * Proposito: exponer endpoint canonico Comercial para resumen read-only de Listas de precios.
     * Impacto: la UI deja de depender de rutas visuales de Ventas, manteniendo compatibilidad en Ventas.
     * Contrato: no escribe BD; requiere `ventas.listas.ver`.
     */
    public function listas_precios_resumen_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        return json_encode($this->modelo('ListasPreciosErp')->resumenReadOnly($_GET));
    }

    public function listas_precios_listar_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        return json_encode($this->modelo('ListasPreciosErp')->listarReadOnly($_GET));
    }

    public function listas_precios_consultar_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        $idLista = isset($_GET['id_lista_precio']) ? intval($_GET['id_lista_precio']) : 0;
        return json_encode($this->modelo('ListasPreciosErp')->consultarReadOnly($idLista));
    }

    public function listas_precios_conflictos_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        return json_encode($this->modelo('ListasPreciosErp')->conflictosReadOnly($_GET));
    }

    public function listas_precios_auditoria_erp()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('ListasPreciosErp')->auditoriaReadOnly($_GET));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-16.
     * Proposito: mostrar segmentos CRM candidatos para listas de precios por tipo de cliente.
     * Impacto: prepara el flujo escalable sin obligar asignacion cliente por cliente.
     * Contrato: read-only; no crea vinculos segmento/lista ni modifica CRM.
     */
    public function listas_precios_segmentos_crm_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        return json_encode($this->modelo('ListasPreciosErp')->segmentosCrmReadOnly($_GET));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-15.
     * Proposito: alimentar la mesa operativa de productos/precios con costo y margen.
     * Impacto: permite editar listas desde Comercial sin depender de IDs sueltos ni vistas de POS.
     * Contrato: solo consulta catalogo/lista; el margen mostrado se recalcula en backend al guardar.
     */
    public function listas_precios_productos_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        return json_encode($this->modelo('ListasPreciosErp')->productosParaListaReadOnly($_GET));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-15.
     * Proposito: mostrar semaforo de activacion para una lista de precios.
     * Impacto: evita activar listas vacias, duplicadas o con precios invalidos desde Comercial.
     * Contrato: read-only; el guardado activo vuelve a validar en backend.
     */
    public function listas_precios_revision_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        $idLista = isset($_GET['id_lista_precio']) ? intval($_GET['id_lista_precio']) : 0;
        return json_encode($this->modelo('ListasPreciosErp')->revisionListaReadOnly($idLista));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-20.
     * Proposito: exponer semaforo read-only de fase 1 para arranque operativo de Listas/POS.
     * Impacto: ayuda a decidir si el modulo esta listo para piloto real sin escribir BD.
     * Contrato: solo lectura; no activa listas, no crea ventas y no toca ecommerce.
     */
    public function listas_precios_fase1_readiness_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        return json_encode($this->modelo('ListasPreciosErp')->fase1ReadinessReadOnly());
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-16.
     * Proposito: buscar clientes CRM para asignarlos a una lista de precios sin capturar IDs manuales.
     * Impacto: mejora flujo Comercial/Listas y mantiene CRM como dueno de identidad del cliente.
     * Contrato: read-only; no crea clientes ni modifica asignaciones.
     */
    public function listas_precios_clientes_buscar_erp()
    {
        $this->requerirPermiso('ventas.listas.asignar_cliente');
        return json_encode($this->modelo('ClientesCrm')->buscarExpressDryRun($_GET));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-16.
     * Proposito: previsualizar el precio que resolveria POS para SKU/cliente/canal/almacen.
     * Impacto: permite validar listas antes de activarlas sin duplicar reglas ni escribir ventas.
     * Contrato: read-only; delega en VentasErp::clientePrecioDryRun.
     */
    public function listas_precios_precio_preview_erp()
    {
        $this->requerirPermiso('ventas.listas.ver');
        return json_encode($this->modelo('VentasErp')->clientePrecioDryRun($_POST));
    }

    public function listas_precios_lista_dryrun_erp()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('ListasPreciosErp')->listaDryRun($_POST));
    }

    public function listas_precios_detalle_dryrun_erp()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('ListasPreciosErp')->detalleDryRun($_POST));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-18.
     * Proposito: prevalidar lote de precios desde Comercial antes de guardar cambios masivos.
     * Impacto: permite detectar errores y margen en servidor sin escribir BD.
     * Contrato: read-only; no modifica listas, detalles ni ventas.
     */
    public function listas_precios_detalles_lote_dryrun_erp()
    {
        $this->requerirPermiso('ventas.listas.editar');
        return json_encode($this->modelo('ListasPreciosErp')->detallesLoteDryRun($_POST));
    }

    public function listas_precios_asignacion_dryrun_erp()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('ListasPreciosErp')->asignacionClienteDryRun($_POST));
    }

    public function listas_precios_segmento_dryrun_erp()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('ListasPreciosErp')->asignacionSegmentoDryRun($_POST));
    }

    public function listas_precios_lista_guardar_erp()
    {
        $this->requerirPermisoListaPreciosGuardar('lista');
        $validacion = $this->validarAutorizacionListasPreciosGuardar();
        if (!empty($validacion['error'])) {
            return json_encode($validacion);
        }
        return json_encode($this->modelo('ListasPreciosErp')->listaGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    public function listas_precios_detalle_guardar_erp()
    {
        $this->requerirPermiso('ventas.listas.editar');
        $validacion = $this->validarAutorizacionListasPreciosGuardar();
        if (!empty($validacion['error'])) {
            return json_encode($validacion);
        }
        return json_encode($this->modelo('ListasPreciosErp')->detalleGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    public function listas_precios_asignacion_guardar_erp()
    {
        $this->requerirPermiso('ventas.listas.asignar_cliente');
        $validacion = $this->validarAutorizacionListasPreciosGuardar();
        if (!empty($validacion['error'])) {
            return json_encode($validacion);
        }
        return json_encode($this->modelo('ListasPreciosErp')->asignacionClienteGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-15.
     * Proposito: guardar encabezado desde el flujo operativo Comercial sin token UAT visible.
     * Impacto: convierte Listas de precios en CRUD funcional bajo permisos finos y auditoria.
     * Contrato: no toca ventas pasadas; activar lista requiere permiso `ventas.listas.activar`.
     */
    public function listas_precios_lista_guardar_operativo_erp()
    {
        $this->requerirPermisoListaPreciosGuardar('lista');
        return json_encode($this->modelo('ListasPreciosErp')->listaGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-15.
     * Proposito: guardar precio SKU/lista desde la tabla operativa de productos.
     * Impacto: permite construir listas por producto con margen visible y auditoria por partida.
     * Contrato: el precio final sigue resolviendose en backend y POS solo consume el resultado.
     */
    public function listas_precios_detalle_guardar_operativo_erp()
    {
        $this->requerirPermiso('ventas.listas.editar');
        return json_encode($this->modelo('ListasPreciosErp')->detalleGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-15.
     * Proposito: guardar varios precios modificados desde la mesa operativa.
     * Impacto: reduce captura repetitiva y conserva auditoria por partida.
     * Contrato: cada precio se valida como detalle individual; no activa listas ni toca ventas pasadas.
     */
    public function listas_precios_detalles_lote_guardar_operativo_erp()
    {
        $this->requerirPermiso('ventas.listas.editar');
        return json_encode($this->modelo('ListasPreciosErp')->detallesLoteGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-15.
     * Proposito: guardar asignacion cliente CRM/lista desde el flujo operativo.
     * Impacto: conecta listas comerciales con CRM sin exponer formulario tecnico UAT.
     * Contrato: requiere permiso fino y auditoria; no cambia ventas emitidas.
     */
    public function listas_precios_asignacion_guardar_operativo_erp()
    {
        $this->requerirPermiso('ventas.listas.asignar_cliente');
        return json_encode($this->modelo('ListasPreciosErp')->asignacionClienteGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-16.
     * Proposito: guardar vinculacion segmento CRM/lista cuando el DDL puente ya exista.
     * Impacto: permite escalar precios por tipo de cliente sin asignar clientes uno por uno.
     * Contrato: requiere permiso de asignacion de listas; no crea segmentos, clientes ni cambia ventas pasadas.
     */
    public function listas_precios_segmento_guardar_operativo_erp()
    {
        $this->requerirPermiso('ventas.listas.asignar_cliente');
        return json_encode($this->modelo('ListasPreciosErp')->asignacionSegmentoGuardarAutorizado($_POST, $this->usuarioActualId()));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-13.
     * Proposito: auditar contrato CRM/listas desde el modulo Comercial.
     * Impacto: valida si asignaciones cliente-lista soportan `id_cliente_crm`.
     * Contrato: read-only; no ejecuta DDL ni modifica listas.
     */
    public function esquema_auditar_listas_precios_crm()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('VentasErpEsquema')->auditarListasPreciosCrm());
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-13.
     * Proposito: exponer actualizacion DDL CRM/listas desde Comercial con guardrails.
     * Impacto: prepara `id_cliente_crm`; no crea listas ni modifica precios.
     * Contrato: solo ejecuta con token `VENTAS_LISTAS_PRECIOS_CRM_DDL` y respaldo externo valido.
     */
    public function esquema_actualizar_listas_precios_crm()
    {
        $this->requerirPermiso('sistema.soporte');
        $ejecutar = isset($_POST['ejecutar']) && intval($_POST['ejecutar']) === 1;
        if ($ejecutar) {
            $autorizar = isset($_POST['autorizar']) ? trim((string) $_POST['autorizar']) : '';
            $respaldo = isset($_POST['respaldo']) ? trim((string) $_POST['respaldo']) : '';
            $validacionRespaldo = $this->validarRespaldoListasPrecios($respaldo);
            if ($autorizar !== 'VENTAS_LISTAS_PRECIOS_CRM_DDL' || !$validacionRespaldo['ok']) {
                return json_encode(array(
                    'error' => true,
                    'tipo' => 'danger',
                    'mensaje' => 'No se ejecuto DDL CRM de listas de precios. Falta autorizacion explicita o respaldo valido.',
                    'depurar' => array(
                        'requerido' => array('autorizar' => 'VENTAS_LISTAS_PRECIOS_CRM_DDL', 'respaldo' => 'RUTA_O_REFERENCIA'),
                        'validacion_respaldo' => $validacionRespaldo,
                        'reglas' => array('No ejecutar sin respaldo externo verificado.', 'No crea listas ni precios.', 'No modifica clientes CRM.')
                    )
                ));
            }
        }
        return json_encode($this->modelo('VentasErpEsquema')->planActualizarListasPreciosCrm($ejecutar));
    }

    public function esquema_auditar_auditoria_listas_precios()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('VentasErpEsquema')->auditarAuditoriaListasPrecios());
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-13.
     * Proposito: exponer DDL de auditoria comercial de listas desde Comercial con guardrails.
     * Impacto: prepara `erp_listas_precios_eventos`; no cambia listas ni ventas pasadas.
     * Contrato: solo ejecuta con token `VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL` y respaldo externo valido.
     */
    public function esquema_actualizar_auditoria_listas_precios()
    {
        $this->requerirPermiso('sistema.soporte');
        $ejecutar = isset($_POST['ejecutar']) && intval($_POST['ejecutar']) === 1;
        if ($ejecutar) {
            $autorizar = isset($_POST['autorizar']) ? trim((string) $_POST['autorizar']) : '';
            $respaldo = isset($_POST['respaldo']) ? trim((string) $_POST['respaldo']) : '';
            $validacionRespaldo = $this->validarRespaldoListasPrecios($respaldo);
            if ($autorizar !== 'VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL' || !$validacionRespaldo['ok']) {
                return json_encode(array(
                    'error' => true,
                    'tipo' => 'danger',
                    'mensaje' => 'No se ejecuto DDL de auditoria de listas de precios. Falta autorizacion explicita o respaldo valido.',
                    'depurar' => array(
                        'requerido' => array('autorizar' => 'VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL', 'respaldo' => 'RUTA_O_REFERENCIA'),
                        'validacion_respaldo' => $validacionRespaldo,
                        'reglas' => array('No ejecutar sin respaldo externo verificado.', 'No cambia precios vigentes.', 'No modifica ventas pasadas.')
                    )
                ));
            }
        }
        return json_encode($this->modelo('VentasErpEsquema')->planActualizarAuditoriaListasPrecios($ejecutar));
    }

    public function esquema_auditar_segmentos_listas_precios()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('VentasErpEsquema')->auditarSegmentosListasPrecios());
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-16.
     * Proposito: exponer DDL planeado para vincular segmentos CRM con listas de precios.
     * Impacto: prepara `erp_segmentos_listas_precios`; no crea segmentos, listas ni cambia ventas.
     * Contrato: solo ejecuta con token `VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL` y respaldo externo valido.
     */
    public function esquema_actualizar_segmentos_listas_precios()
    {
        $this->requerirPermiso('sistema.soporte');
        $ejecutar = isset($_POST['ejecutar']) && intval($_POST['ejecutar']) === 1;
        if ($ejecutar) {
            $autorizar = isset($_POST['autorizar']) ? trim((string) $_POST['autorizar']) : '';
            $respaldo = isset($_POST['respaldo']) ? trim((string) $_POST['respaldo']) : '';
            $validacionRespaldo = $this->validarRespaldoListasPrecios($respaldo);
            if ($autorizar !== 'VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL' || !$validacionRespaldo['ok']) {
                return json_encode(array(
                    'error' => true,
                    'tipo' => 'danger',
                    'mensaje' => 'No se ejecuto DDL de segmentos/listas. Falta autorizacion explicita o respaldo valido.',
                    'depurar' => array(
                        'requerido' => array('autorizar' => 'VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL', 'respaldo' => 'RUTA_O_REFERENCIA'),
                        'validacion_respaldo' => $validacionRespaldo,
                        'reglas' => array('No ejecutar sin respaldo externo verificado.', 'No crea segmentos CRM.', 'No asigna listas ni modifica ventas pasadas.')
                    )
                ));
            }
        }
        return json_encode($this->modelo('VentasErpEsquema')->planActualizarSegmentosListasPrecios($ejecutar));
    }

    private function requerirPermisoListaPreciosGuardar($tipo)
    {
        if ($tipo === 'lista') {
            $id = isset($_POST['id_lista_precio']) ? intval($_POST['id_lista_precio']) : 0;
            $estatus = isset($_POST['estatus']) ? trim((string) $_POST['estatus']) : '';
            $this->requerirPermiso($id > 0 ? 'ventas.listas.editar' : 'ventas.listas.crear');
            if ($estatus === 'activa') {
                $this->requerirPermiso('ventas.listas.activar');
            } elseif ($estatus === 'pausada') {
                $this->requerirPermiso('ventas.listas.pausar');
            } elseif ($estatus === 'cancelada') {
                $this->requerirPermiso('ventas.listas.cancelar');
            }
        }
        return true;
    }

    private function validarAutorizacionListasPreciosGuardar()
    {
        $autorizar = isset($_POST['autorizar']) ? trim((string) $_POST['autorizar']) : '';
        if ($autorizar !== 'VENTAS_LISTAS_PRECIOS_GUARDAR_UAT') {
            return array(
                'error' => true,
                'tipo' => 'danger',
                'mensaje' => 'No se guardo lista de precios. Falta autorizacion UAT.',
                'depurar' => array(
                    'requerido' => array('autorizar' => 'VENTAS_LISTAS_PRECIOS_GUARDAR_UAT'),
                    'reglas' => array(
                        'No guardar sin permisos finos sembrados.',
                        'No guardar sin auditoria comercial `erp_listas_precios_eventos`.',
                        'No modifica ventas pasadas; POS conserva snapshot por partida.',
                        'El respaldo externo se reserva para cambios de esquema/DDL.'
                    )
                )
            );
        }
        return array('error' => false, 'tipo' => 'success', 'mensaje' => 'Autorizacion UAT validada');
    }

    private function validarRespaldoListasPrecios($respaldo)
    {
        $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
        $existe = false;
        $legible = false;
        $tamano = null;
        if ($respaldo !== '' && $esRutaLocal) {
            $existe = file_exists($respaldo);
            $legible = $existe && is_readable($respaldo);
            $tamano = $existe ? filesize($respaldo) : null;
        }
        $okReferencia = strlen($respaldo) >= 8;
        $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
        return array(
            'ok' => $okReferencia && $okRuta,
            'referencia_presente' => $okReferencia,
            'parece_ruta_local' => $esRutaLocal,
            'archivo_existe' => $esRutaLocal ? $existe : null,
            'archivo_legible' => $esRutaLocal ? $legible : null,
            'tamano_bytes' => $tamano
        );
    }
}
