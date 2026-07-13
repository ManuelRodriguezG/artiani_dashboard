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
        $this->vista('apps/erp/ventas/listas_precios', array(
            'modulo' => 'comercial',
            'ruta_canonica' => '/comercial/listas_precios'
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

    public function listas_precios_asignacion_dryrun_erp()
    {
        $this->requerirPermiso('ventas.listas.auditoria');
        return json_encode($this->modelo('ListasPreciosErp')->asignacionClienteDryRun($_POST));
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
