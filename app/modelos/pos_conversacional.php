<?php

/**
 * IA: Codex GPT-6 | Fecha: 2026-11-17
 * Propósito: simular las acciones estructuradas del asistente sobre el dominio POS existente.
 * Impacto: servicio interno sin ruta HTTP ni acceso directo a la conexión de base de datos.
 * Contrato: el llamador debe autenticar al operador y exigir ventas.operar antes de invocarlo.
 */
final class PosConversacional {
    private const ALMACEN = 6;
    private const CAJA = 4;

    private VentasErp $ventas;

    public function __construct(VentasErp $ventas) {
        $this->ventas = $ventas;
    }

    /** Procesa una acción JSON ya decodificada. El ID del operador viene del llamador autenticado. */
    public function simular(array $solicitud, int $idUsuario): array {
        if ($idUsuario <= 0) {
            return $this->respuesta('BLOQUEADA', 'Se requiere un operador autenticado.');
        }

        try {
            $accion = $solicitud['accion'] ?? null;
            if (!is_string($accion)) {
                throw new InvalidArgumentException('Indica una acción conversacional.');
            }
            switch ($accion) {
                case 'buscar':
                    $this->validarCampos($solicitud, ['accion', 'q']);
                    $q = $solicitud['q'] ?? null;
                    if (!is_string($q) || strlen(trim($q)) < 2 || strlen($q) > 120) {
                        throw new InvalidArgumentException('Escribe entre 2 y 120 bytes de SKU, código o descripción.');
                    }
                    $resultado = $this->datosPos($this->ventas->buscarSkusPos([
                        'q' => trim($q), 'id_almacen' => self::ALMACEN, 'limite' => 10
                    ]));
                    $coincidencias = array_map(static function (array $sku): array {
                        return array_intersect_key($sku, array_flip([
                            'id_sku', 'sku', 'nombre_sku', 'precio', 'moneda', 'unidad_venta_label', 'existencia_disponible'
                        ]));
                    }, array_slice($resultado, 0, 5));
                    return $this->respuesta('BORRADOR', $coincidencias ? 'Selecciona el producto.' : 'Sin coincidencias.', [
                        'coincidencias' => $coincidencias
                    ]);
                case 'agregar_partida':
                    $this->validarCampos($solicitud, ['accion', 'partida']);
                    return $this->simularPartida($this->partidaPos($solicitud['partida'] ?? null), $idUsuario);
                case 'consultar_cuenta':
                    $this->validarCampos($solicitud, ['accion', 'id_atencion']);
                    return $this->consultarCuenta($this->idPos($solicitud['id_atencion'] ?? null));
                case 'prevalidar':
                    $this->validarCampos($solicitud, ['accion', 'items', 'pagos']);
                    return $this->prevalidar($solicitud['items'] ?? null, $solicitud['pagos'] ?? [], $idUsuario);
                case 'confirmar_cobro':
                    $this->validarCampos($solicitud, ['accion']);
                    return $this->respuesta('BLOQUEADA', 'El cobro conversacional está deshabilitado en esta fase.');
                default:
                    throw new InvalidArgumentException('Acción conversacional desconocida.');
            }
        } catch (InvalidArgumentException $e) {
            return $this->respuesta('REQUIERE_DATOS', $e->getMessage());
        } catch (RuntimeException $e) {
            return $this->respuesta('BLOQUEADA', 'El POS no pudo completar la consulta o simulación.');
        }
    }

    /** Valida la partida con el POS y prepara un estimado de atención sin caja ni turno. */
    private function simularPartida(array $partida, int $idUsuario): array {
        $avisos = [];
        if (($partida['tipo_partida'] ?? '') === 'venta_rapida') {
            $validacion = $this->datosPos($this->ventas->ventaRapidaControladaDryRun([
                'id_almacen' => self::ALMACEN, 'id_usuario' => $idUsuario,
                'descripcion' => $partida['descripcion_manual']
            ] + $partida));
            if (!empty($validacion['bloqueos'])) {
                return $this->respuesta('REQUIERE_DATOS', 'Completa los datos del producto por clasificar.', [
                    'bloqueos' => $validacion['bloqueos']
                ]);
            }
            $avisos = $validacion['avisos'] ?? [];
        }
        $simulacion = $this->datosPos($this->ventas->atencionPersistenteDryRun([
            'id_almacen' => self::ALMACEN, 'id_usuario' => $idUsuario, 'origen' => 'pos_conversacional',
            'items' => [$partida]
        ]));
        $simulacion['avisos'] = array_merge($avisos, $simulacion['avisos'] ?? []);
        $bloqueos = array_merge($simulacion['bloqueos'] ?? [], $simulacion['partidas'][0]['bloqueos'] ?? []);
        return $this->resumen($simulacion, $bloqueos, 'BORRADOR', 'Partida simulada para la cuenta local.');
    }

    /** Consulta una atención del almacén central y devuelve un resumen apto para el asistente. */
    private function consultarCuenta(int $idAtencion): array {
        $datos = $this->datosPos($this->ventas->atencionDetalleReadOnly([
            'id_atencion' => $idAtencion, 'id_almacen' => self::ALMACEN
        ]));
        $cuenta = $datos['atencion'] ?? [];
        if ((int) ($cuenta['id_almacen'] ?? 0) !== self::ALMACEN) {
            return $this->respuesta('BLOQUEADA', 'Atención central no disponible.');
        }

        $cuenta = array_intersect_key($cuenta, array_flip(['id_atencion_pos', 'folio_temporal', 'estatus', 'total']));
        $partidas = array_map(static function (array $partida): array {
            return [
                'unidad_venta' => $partida['unidad_venta'] ?? $partida['snapshot']['unidad_venta']
                    ?? $partida['snapshot']['unidad_venta_label'] ?? null,
                'tipo_partida' => $partida['snapshot']['tipo_partida'] ?? null
            ] + $partida;
        }, $datos['partidas'] ?? []);
        $datos = ['cuenta' => $cuenta, 'partidas' => $this->partidasResumen($partidas)];
        return $this->respuesta('CUENTA_CONSULTADA', 'Atención central consultada.', $datos);
    }

    /** Contrasta validación de pago completo y esquema de confirmación con el turno del operador. */
    private function prevalidar($items, $pagos, int $idUsuario): array {
        if (!is_array($items) || !$items || array_values($items) !== $items || !is_array($pagos) || array_values($pagos) !== $pagos) {
            throw new InvalidArgumentException('Envía una lista de partidas y una lista de pagos declarados.');
        }
        $items = array_map([$this, 'partidaPos'], $items);
        foreach ($pagos as $pago) {
            $this->validarCampos($pago, ['id_metodo_pago', 'monto', 'referencia']);
            $this->idPos($pago['id_metodo_pago'] ?? null);
            if (!$this->decimalPos($pago['monto'] ?? null) || !is_string($pago['referencia'] ?? '')) {
                throw new InvalidArgumentException('Indica un importe positivo y, si corresponde, la referencia del pago.');
            }
        }

        // Usa la misma selección por prioridad que el cobro POS y comprueba su destino.
        $contexto = $this->datosPos($this->ventas->asignacionActualTerminalPos(['id_usuario' => $idUsuario]));
        $asignacion = $contexto['asignacion'] ?? [];
        if ((int) ($asignacion['id_almacen'] ?? 0) !== self::ALMACEN || (int) ($asignacion['id_caja'] ?? 0) !== self::CAJA) {
            return $this->respuesta('BLOQUEADA', 'El operador requiere una asignación POS activa a la caja central.');
        }
        $idTurno = (int) ($contexto['turno_abierto']['id_turno_caja'] ?? 0);
        if ($idTurno <= 0) {
            return $this->respuesta('BLOQUEADA', 'Se requiere un turno abierto en la caja central.');
        }
        $datos = [
            'id_almacen' => self::ALMACEN, 'id_caja' => self::CAJA, 'id_turno_caja' => $idTurno, 'canal' => 'pos',
            'exigir_pago_completo' => 1, 'items' => $items, 'pagos' => $pagos
        ];
        $carrito = $this->datosPos($this->ventas->prevalidarCarritoPos($datos));
        $confirmacion = $this->datosPos($this->ventas->confirmarVentaPosDryRun($datos));
        $this->datosPos($confirmacion['prevalidacion'] ?? []);
        $bloqueos = array_merge($carrito['bloqueos'] ?? [], $confirmacion['bloqueos'] ?? []);
        if (empty($carrito['pagos']) || !isset($carrito['totales']['total_estimado']) || $carrito['totales']['total_estimado'] <= 0) {
            $bloqueos[] = 'El resumen requiere un total positivo y al menos un pago prevalidado.';
        }
        $carrito['id_turno_caja'] = $idTurno;
        return $this->resumen($carrito, $bloqueos, 'LISTA_PARA_CONFIRMAR', 'Resumen simulado por el POS.');
    }

    /** Acepta los campos de intención de partida y conserva la validación comercial en VentasErp. */
    private function partidaPos($partida): array {
        $esRapida = is_array($partida) && ($partida['tipo_partida'] ?? '') === 'venta_rapida';
        if ($esRapida) {
            $this->validarCampos($partida, ['tipo_partida', 'cantidad', 'descripcion_manual', 'unidad_venta', 'precio_unitario', 'motivo']);
            if (!is_string($partida['descripcion_manual'] ?? null) || !is_string($partida['motivo'] ?? null)
                || !in_array($partida['unidad_venta'] ?? null, ['pza', 'kg', 'g', 'l', 'ml', 'm', 'cm', 'paquete', 'servicio'], true)
                || !$this->decimalPos($partida['precio_unitario'] ?? null)) {
                throw new InvalidArgumentException('Indica descripción, unidad, motivo y precio unitario provisional para la venta rápida.');
            }
        } else {
            $this->validarCampos($partida, ['id_sku', 'cantidad']);
            $this->idPos($partida['id_sku'] ?? null);
        }
        if (!$this->decimalPos($partida['cantidad'] ?? null)) {
            throw new InvalidArgumentException('Indica una cantidad positiva con hasta seis decimales.');
        }
        return $partida;
    }

    /** Valida una forma cerrada para evitar propagar parámetros privilegiados al POS. */
    private function validarCampos($datos, array $campos): void {
        if (!is_array($datos) || array_diff_key($datos, array_flip($campos))) {
            throw new InvalidArgumentException('La acción contiene campos fuera del contrato conversacional.');
        }
    }

    /** El contrato JSON usa enteros positivos para identidad de recursos. */
    private function idPos($id): int {
        if (!is_int($id) || $id <= 0) {
            throw new InvalidArgumentException('Selecciona un identificador válido del ERP.');
        }
        return $id;
    }

    /** Acepta decimales positivos dentro de la precisión de importes y cantidades del POS. */
    private function decimalPos($valor): bool {
        return (is_string($valor) || is_int($valor) || is_float($valor))
            && preg_match('/\A[0-9]{1,12}(\.[0-9]{1,6})?\z/', (string) $valor)
            && (float) $valor > 0;
    }

    /** Lee respuestas de dominio sin propagar excepciones ni diagnósticos internos al chat. */
    private function datosPos(array $resultado): array {
        if (($resultado['error'] ?? true) || !is_array($resultado['depurar'] ?? null)) {
            throw new RuntimeException('Fallo de consulta POS');
        }
        return $resultado['depurar'];
    }

    /** Proyecta los campos comerciales y excluye snapshots y datos de clientes. */
    private function partidasResumen(array $partidas): array {
        $campos = array_flip(['id_sku', 'sku', 'descripcion', 'tipo_partida', 'cantidad', 'unidad_venta',
            'precio_unitario', 'subtotal', 'bloqueos', 'avisos']);
        return array_map(static function (array $partida) use ($campos): array {
            return array_intersect_key($partida, $campos);
        }, $partidas);
    }

    /** Resume importes y bloqueos emitidos por POS, sin exponer referencias de pago. */
    private function resumen(array $datos, array $bloqueos, string $estado, string $mensaje): array {
        $camposPago = array_flip(['id_metodo_pago', 'metodo_pago', 'monto']);
        return $this->respuesta($bloqueos ? 'BLOQUEADA' : $estado, $bloqueos ? 'El POS detectó bloqueos.' : $mensaje, [
            'id_turno_caja' => $datos['id_turno_caja'] ?? null,
            'partidas' => $this->partidasResumen($datos['partidas'] ?? []),
            'totales' => $datos['totales'] ?? [],
            'pagos' => array_map(static function (array $pago) use ($camposPago): array {
                return array_intersect_key($pago, $camposPago);
            }, $datos['pagos'] ?? []),
            'bloqueos' => array_values(array_unique($bloqueos)),
            'avisos' => $datos['avisos'] ?? []
        ]);
    }

    /** Sigue el sobre JSON ERP y marca explícitamente que esta simulación no habilita cobros. */
    private function respuesta(string $estado, string $mensaje, array $datos = []): array {
        $bloqueada = in_array($estado, ['BLOQUEADA', 'REQUIERE_DATOS'], true);
        return [
            'error' => $bloqueada, 'tipo' => $bloqueada ? 'warning' : 'success', 'mensaje' => $mensaje,
            'depurar' => [
                'estado' => $estado, 'dry_run' => true, 'habilita_cobro' => false,
                'id_almacen' => self::ALMACEN, 'id_caja' => self::CAJA
            ] + $datos
        ];
    }
}
