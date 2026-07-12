<?php

class VentasErp extends CRUD {

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: resumir estado operativo del modulo Ventas/POS sin depender de vistas legacy.
     * Impacto: alimenta el tablero ERP nuevo y expone si el esquema productivo aun esta pendiente.
     * Contrato: read-only; si faltan tablas ERP devuelve contadores en cero y bandera `schema_pendiente`.
     */
    public function resumenVentasModulo($filtros = array()) {
        try {
            $db = $this->getConexion();
            $schemaPendiente = !$this->tablasVentasDisponibles($db);
            if ($schemaPendiente) {
                return $this->respuesta(false, "warning", "Esquema Ventas/POS pendiente de autorizacion", array(
                    "schema_pendiente" => true,
                    "ventas_hoy" => 0,
                    "pedidos_abiertos" => 0,
                    "reservas_pendientes" => 0,
                    "total_hoy" => 0,
                    "turnos_abiertos" => 0
                ));
            }

            $hoy = date("Y-m-d");
            $stmt = $db->prepare("SELECT
                    SUM(CASE WHEN tipo_documento='venta' AND DATE(fecha_venta)=:hoy THEN 1 ELSE 0 END) ventas_hoy,
                    SUM(CASE WHEN tipo_documento='venta' AND DATE(fecha_venta)=:hoy THEN total ELSE 0 END) total_hoy,
                    SUM(CASE WHEN tipo_documento='pedido' AND estatus IN ('borrador','reservado','pendiente_pago','pagado') THEN 1 ELSE 0 END) pedidos_abiertos,
                    SUM(CASE WHEN tipo_documento IN ('pedido','apartado') AND estatus IN ('reservado','pendiente_pago') THEN 1 ELSE 0 END) reservas_pendientes
                FROM erp_ventas");
            $stmt->execute(array(":hoy" => $hoy));
            $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
            $turnos = 0;
            if ($this->tablaExiste($db, "erp_pos_turnos")) {
                $turnos = intval($db->query("SELECT COUNT(*) FROM erp_pos_turnos WHERE estatus='abierto'")->fetchColumn());
            }
            $resumen["schema_pendiente"] = false;
            $resumen["turnos_abiertos"] = $turnos;
            return $this->respuesta(false, "success", "Resumen Ventas/POS consultado", $resumen);
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: listar ventas/pedidos ERP para el tablero operativo nuevo.
     * Impacto: evita leer `ecom_pedidos` desde la nueva seccion de Ventas.
     * Contrato: read-only; tolera tablas faltantes y no mezcla informacion legacy.
     */
    public function listarVentasErp($filtros = array()) {
        try {
            $db = $this->getConexion();
            if (!$this->tablasVentasDisponibles($db)) {
                return $this->respuesta(false, "warning", "Esquema Ventas/POS pendiente de autorizacion", array(
                    "schema_pendiente" => true,
                    "ventas" => array(),
                    "total_registros" => 0
                ));
            }

            $tipo = trim((string) $this->valor($filtros, "tipo", ""));
            $estatus = trim((string) $this->valor($filtros, "estatus", ""));
            $q = trim((string) $this->valor($filtros, "q", ""));
            $limite = max(20, min(100, intval($this->valor($filtros, "limite", 50))));
            $where = array("1=1");
            $params = array();
            if ($tipo !== "") {
                $where[] = "v.tipo_documento=:tipo";
                $params[":tipo"] = $tipo;
            }
            if ($estatus !== "") {
                $where[] = "v.estatus=:estatus";
                $params[":estatus"] = $estatus;
            }
            if ($q !== "") {
                $where[] = "(v.folio LIKE :q OR v.cliente_nombre_publico LIKE :q)";
                $params[":q"] = "%" . $q . "%";
            }

            $sql = "SELECT v.id_venta, v.folio, v.canal, v.tipo_documento, v.estatus,
                    v.id_almacen, a.almacen, v.cliente_nombre_publico, v.subtotal,
                    v.descuento_total, v.impuestos_total, v.total, v.pagado_total,
                    v.saldo_total, v.fecha_venta, v.fecha_entrega_compromiso,
                    COUNT(d.id_venta_detalle) partidas
                FROM erp_ventas v
                LEFT JOIN erp_almacenes a ON a.id_almacen=v.id_almacen
                LEFT JOIN erp_ventas_detalle d ON d.id_venta=v.id_venta
                WHERE " . implode(" AND ", $where) . "
                GROUP BY v.id_venta
                ORDER BY v.fecha_venta DESC, v.id_venta DESC
                LIMIT " . intval($limite);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->respuesta(false, "success", "Ventas ERP consultadas", array(
                "schema_pendiente" => false,
                "ventas" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: consultar reporte gerencial read-only de caja POS y diferencias.
     * Impacto: permite supervision por turno, empleado, sucursal y caja sin mover dinero ni inventario.
     * Contrato: solo lectura; no corrige ni resuelve diferencias.
     */
    public function reporteCajaPosReadOnly($filtros = array()) {
        try {
            $db = $this->getConexion();
            foreach (array("erp_pos_turnos", "erp_pos_cajas") as $tabla) {
                if (!$this->tablaExiste($db, $tabla)) {
                    return $this->respuesta(false, "warning", "Esquema de reportes POS pendiente", array(
                        "schema_pendiente" => true,
                        "turnos" => array(),
                        "resumen" => array()
                    ));
                }
            }

            $fechaDesde = trim((string) $this->valor($filtros, "fecha_desde", date("Y-m-d", strtotime("-30 days"))));
            $fechaHasta = trim((string) $this->valor($filtros, "fecha_hasta", date("Y-m-d")));
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            $idCaja = intval($this->valor($filtros, "id_caja", 0));
            $soloDiferencias = intval($this->valor($filtros, "solo_diferencias", 0)) === 1;
            $limite = max(20, min(200, intval($this->valor($filtros, "limite", 100))));

            $where = array("DATE(t.fecha_apertura) BETWEEN :desde AND :hasta");
            $params = array(":desde" => $fechaDesde, ":hasta" => $fechaHasta);
            if ($idAlmacen > 0) {
                $where[] = "t.id_almacen=:almacen";
                $params[":almacen"] = $idAlmacen;
            }
            if ($idCaja > 0) {
                $where[] = "t.id_caja=:caja";
                $params[":caja"] = $idCaja;
            }
            if ($soloDiferencias) {
                $where[] = "ABS(COALESCE(t.diferencia, 0)) > 0.0001";
            }

            $sql = "SELECT t.id_turno_caja, t.folio, t.id_almacen, a.codigo_almacen, a.almacen,
                    t.id_caja, c.codigo caja_codigo, c.nombre caja_nombre,
                    t.id_usuario_apertura, ua.nombre_mostrar usuario_apertura,
                    t.id_usuario_cierre, uc.nombre_mostrar usuario_cierre,
                    t.monto_inicial, t.monto_esperado, t.monto_contado, t.diferencia,
                    CASE
                        WHEN COALESCE(t.diferencia, 0) > 0.0001 THEN 'sobrante'
                        WHEN COALESCE(t.diferencia, 0) < -0.0001 THEN 'faltante'
                        ELSE 'cuadrado'
                    END estado_diferencia,
                    t.estatus, t.fecha_apertura, t.fecha_cierre, t.observaciones_cierre,
                    COALESCE(v.operaciones, 0) ventas_operaciones,
                    COALESCE(v.total, 0) ventas_total,
                    COALESCE(mc.movimientos, 0) movimientos_count
                FROM erp_pos_turnos t
                INNER JOIN erp_pos_cajas c ON c.id_caja=t.id_caja
                LEFT JOIN erp_almacenes a ON a.id_almacen=t.id_almacen
                LEFT JOIN sys_usuarios ua ON ua.id_usuario=t.id_usuario_apertura
                LEFT JOIN sys_usuarios uc ON uc.id_usuario=t.id_usuario_cierre
                LEFT JOIN (
                    SELECT id_turno_caja, COUNT(*) operaciones, COALESCE(SUM(total), 0) total
                    FROM erp_ventas
                    WHERE estatus NOT IN ('cancelada')
                    GROUP BY id_turno_caja
                ) v ON v.id_turno_caja=t.id_turno_caja
                LEFT JOIN (
                    SELECT id_turno_caja, COUNT(*) movimientos
                    FROM erp_pos_movimientos_caja
                    GROUP BY id_turno_caja
                ) mc ON mc.id_turno_caja=t.id_turno_caja
                WHERE " . implode(" AND ", $where) . "
                ORDER BY t.fecha_apertura DESC, t.id_turno_caja DESC
                LIMIT " . intval($limite);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resumen = array(
                "turnos" => count($turnos),
                "turnos_con_diferencia" => 0,
                "faltantes_total" => 0,
                "sobrantes_total" => 0,
                "diferencia_neta" => 0,
                "ventas_total" => 0,
                "ventas_operaciones" => 0,
                "movimientos_count" => 0,
                "faltante_promedio" => 0,
                "sobrante_promedio" => 0
            );
            $porUsuario = array();
            $porCaja = array();
            foreach ($turnos as $turno) {
                $diferencia = round(floatval($turno["diferencia"]), 6);
                if (abs($diferencia) > 0.0001) {
                    $resumen["turnos_con_diferencia"]++;
                }
                if ($diferencia < 0) {
                    $resumen["faltantes_total"] += abs($diferencia);
                } elseif ($diferencia > 0) {
                    $resumen["sobrantes_total"] += $diferencia;
                }
                $resumen["diferencia_neta"] += $diferencia;
                $resumen["ventas_total"] += round(floatval($this->valor($turno, "ventas_total", 0)), 6);
                $resumen["ventas_operaciones"] += intval($this->valor($turno, "ventas_operaciones", 0));
                $resumen["movimientos_count"] += intval($this->valor($turno, "movimientos_count", 0));

                $usuarioKey = intval($this->valor($turno, "id_usuario_cierre", 0));
                if ($usuarioKey <= 0) {
                    $usuarioKey = intval($this->valor($turno, "id_usuario_apertura", 0));
                }
                if (!isset($porUsuario[$usuarioKey])) {
                    $porUsuario[$usuarioKey] = array(
                        "id_usuario" => $usuarioKey,
                        "usuario" => $this->valor($turno, "usuario_cierre", "") ?: $this->valor($turno, "usuario_apertura", "") ?: ("Usuario " . $usuarioKey),
                        "turnos" => 0,
                        "turnos_con_diferencia" => 0,
                        "faltantes_total" => 0,
                        "sobrantes_total" => 0,
                        "diferencia_neta" => 0,
                        "porcentaje_turnos_con_diferencia" => 0
                    );
                }
                $porUsuario[$usuarioKey]["turnos"]++;
                if (abs($diferencia) > 0.0001) {
                    $porUsuario[$usuarioKey]["turnos_con_diferencia"]++;
                }
                if ($diferencia < 0) {
                    $porUsuario[$usuarioKey]["faltantes_total"] += abs($diferencia);
                } elseif ($diferencia > 0) {
                    $porUsuario[$usuarioKey]["sobrantes_total"] += $diferencia;
                }
                $porUsuario[$usuarioKey]["diferencia_neta"] += $diferencia;

                $cajaKey = intval($this->valor($turno, "id_almacen", 0)) . "-" . intval($this->valor($turno, "id_caja", 0));
                if (!isset($porCaja[$cajaKey])) {
                    $porCaja[$cajaKey] = array(
                        "id_almacen" => intval($this->valor($turno, "id_almacen", 0)),
                        "almacen" => $this->valor($turno, "almacen", ""),
                        "codigo_almacen" => $this->valor($turno, "codigo_almacen", ""),
                        "id_caja" => intval($this->valor($turno, "id_caja", 0)),
                        "caja_codigo" => $this->valor($turno, "caja_codigo", ""),
                        "caja_nombre" => $this->valor($turno, "caja_nombre", ""),
                        "turnos" => 0,
                        "turnos_con_diferencia" => 0,
                        "faltantes_total" => 0,
                        "sobrantes_total" => 0,
                        "diferencia_neta" => 0,
                        "porcentaje_turnos_con_diferencia" => 0
                    );
                }
                $porCaja[$cajaKey]["turnos"]++;
                if (abs($diferencia) > 0.0001) {
                    $porCaja[$cajaKey]["turnos_con_diferencia"]++;
                }
                if ($diferencia < 0) {
                    $porCaja[$cajaKey]["faltantes_total"] += abs($diferencia);
                } elseif ($diferencia > 0) {
                    $porCaja[$cajaKey]["sobrantes_total"] += $diferencia;
                }
                $porCaja[$cajaKey]["diferencia_neta"] += $diferencia;
            }
            $faltantesCount = 0;
            $sobrantesCount = 0;
            foreach ($turnos as $turnoPromedio) {
                $difPromedio = round(floatval($this->valor($turnoPromedio, "diferencia", 0)), 6);
                if ($difPromedio < -0.0001) {
                    $faltantesCount++;
                } elseif ($difPromedio > 0.0001) {
                    $sobrantesCount++;
                }
            }
            $resumen["faltante_promedio"] = $faltantesCount > 0 ? round($resumen["faltantes_total"] / $faltantesCount, 6) : 0;
            $resumen["sobrante_promedio"] = $sobrantesCount > 0 ? round($resumen["sobrantes_total"] / $sobrantesCount, 6) : 0;
            foreach (array("faltantes_total", "sobrantes_total", "diferencia_neta", "ventas_total", "faltante_promedio", "sobrante_promedio") as $campo) {
                $resumen[$campo] = round($resumen[$campo], 6);
            }
            foreach ($porUsuario as $key => $usuario) {
                foreach (array("faltantes_total", "sobrantes_total", "diferencia_neta") as $campo) {
                    $porUsuario[$key][$campo] = round($porUsuario[$key][$campo], 6);
                }
                $turnosUsuario = max(1, intval($porUsuario[$key]["turnos"]));
                $porUsuario[$key]["porcentaje_turnos_con_diferencia"] = round(($porUsuario[$key]["turnos_con_diferencia"] / $turnosUsuario) * 100, 2);
            }
            foreach ($porCaja as $key => $caja) {
                foreach (array("faltantes_total", "sobrantes_total", "diferencia_neta") as $campo) {
                    $porCaja[$key][$campo] = round($porCaja[$key][$campo], 6);
                }
                $turnosCaja = max(1, intval($porCaja[$key]["turnos"]));
                $porCaja[$key]["porcentaje_turnos_con_diferencia"] = round(($porCaja[$key]["turnos_con_diferencia"] / $turnosCaja) * 100, 2);
            }
            usort($porUsuario, function ($a, $b) {
                $absA = abs(floatval($a["diferencia_neta"]));
                $absB = abs(floatval($b["diferencia_neta"]));
                if ($absA === $absB) {
                    return intval($b["turnos_con_diferencia"]) - intval($a["turnos_con_diferencia"]);
                }
                return $absA < $absB ? 1 : -1;
            });
            usort($porCaja, function ($a, $b) {
                $absA = abs(floatval($a["diferencia_neta"]));
                $absB = abs(floatval($b["diferencia_neta"]));
                if ($absA === $absB) {
                    return intval($b["turnos_con_diferencia"]) - intval($a["turnos_con_diferencia"]);
                }
                return $absA < $absB ? 1 : -1;
            });

            return $this->respuesta(false, "success", "Reporte caja POS consultado", array(
                "read_only" => true,
                "schema_pendiente" => false,
                "filtros" => array(
                    "fecha_desde" => $fechaDesde,
                    "fecha_hasta" => $fechaHasta,
                    "id_almacen" => $idAlmacen,
                    "id_caja" => $idCaja,
                    "solo_diferencias" => $soloDiferencias
                ),
                "resumen" => $resumen,
                "turnos" => $turnos,
                "por_usuario" => array_values($porUsuario),
                "por_caja" => array_values($porCaja),
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_cierra_turno" => true,
                    "no_resuelve_diferencias" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", "No se pudo consultar reporte de caja POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: listar diferencias de caja pendientes de seguimiento formal.
     * Impacto: permite ver faltantes/sobrantes cerrados por turno antes de crear flujo de resolucion.
     * Contrato: solo lectura; no resuelve, no ajusta caja y tolera tabla futura de revision.
     */
    public function diferenciasCajaPendientesReadOnly($filtros = array()) {
        try {
            $db = $this->getConexion();
            if (!$this->tablaExiste($db, "erp_pos_turnos")) {
                return $this->respuesta(false, "warning", "Esquema de turnos POS pendiente", array(
                    "read_only" => true,
                    "schema_pendiente" => true,
                    "diferencias" => array()
                ));
            }

            $fechaDesde = trim((string) $this->valor($filtros, "fecha_desde", date("Y-m-d", strtotime("-30 days"))));
            $fechaHasta = trim((string) $this->valor($filtros, "fecha_hasta", date("Y-m-d")));
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            $idCaja = intval($this->valor($filtros, "id_caja", 0));
            $estado = trim((string) $this->valor($filtros, "estado_revision", "pendiente_revision"));
            $limite = max(20, min(200, intval($this->valor($filtros, "limite", 100))));
            $tieneRevision = $this->tablaExiste($db, "erp_pos_turnos_diferencias_revision");

            $where = array("t.estatus='cerrado'", "ABS(COALESCE(t.diferencia, 0)) > 0.0001", "DATE(t.fecha_cierre) BETWEEN :desde AND :hasta");
            $params = array(":desde" => $fechaDesde, ":hasta" => $fechaHasta);
            if ($idAlmacen > 0) {
                $where[] = "t.id_almacen=:almacen";
                $params[":almacen"] = $idAlmacen;
            }
            if ($idCaja > 0) {
                $where[] = "t.id_caja=:caja";
                $params[":caja"] = $idCaja;
            }
            if ($tieneRevision && $estado !== "" && $estado !== "todos") {
                $where[] = "COALESCE(dr.estatus, 'pendiente_revision')=:estado_revision";
                $params[":estado_revision"] = $estado;
            }

            $joinRevision = $tieneRevision
                ? "LEFT JOIN erp_pos_turnos_diferencias_revision dr ON dr.id_turno_caja=t.id_turno_caja"
                : "";
            $selectRevision = $tieneRevision
                ? "dr.id_diferencia_revision, dr.folio folio_revision, COALESCE(dr.estatus, 'pendiente_revision') estado_revision,
                   dr.motivo, dr.responsable_revision, dr.fecha_revision, dr.fecha_resolucion,
                   dr.decision, dr.evidencia_referencia"
                : "NULL id_diferencia_revision, NULL folio_revision, 'pendiente_revision' estado_revision,
                   NULL motivo, NULL responsable_revision, NULL fecha_revision, NULL fecha_resolucion,
                   NULL decision, NULL evidencia_referencia";

            $sql = "SELECT t.id_turno_caja, t.folio, t.id_almacen, a.codigo_almacen, a.almacen,
                    t.id_caja, c.codigo caja_codigo, c.nombre caja_nombre,
                    t.id_usuario_apertura, t.id_usuario_cierre,
                    ua.nombre_mostrar usuario_apertura, uc.nombre_mostrar usuario_cierre,
                    t.monto_inicial, t.monto_esperado, t.monto_contado, t.diferencia,
                    CASE
                        WHEN COALESCE(t.diferencia, 0) > 0.0001 THEN 'sobrante'
                        WHEN COALESCE(t.diferencia, 0) < -0.0001 THEN 'faltante'
                        ELSE 'cuadrado'
                    END tipo_diferencia,
                    t.fecha_apertura, t.fecha_cierre, t.observaciones_cierre,
                    COALESCE(v.operaciones, 0) ventas_operaciones,
                    COALESCE(v.total, 0) ventas_total,
                    " . $selectRevision . "
                FROM erp_pos_turnos t
                LEFT JOIN erp_almacenes a ON a.id_almacen=t.id_almacen
                LEFT JOIN erp_pos_cajas c ON c.id_caja=t.id_caja
                LEFT JOIN sys_usuarios ua ON ua.id_usuario=t.id_usuario_apertura
                LEFT JOIN sys_usuarios uc ON uc.id_usuario=t.id_usuario_cierre
                LEFT JOIN (
                    SELECT id_turno_caja, COUNT(*) operaciones, COALESCE(SUM(total), 0) total
                    FROM erp_ventas
                    WHERE estatus NOT IN ('cancelada')
                    GROUP BY id_turno_caja
                ) v ON v.id_turno_caja=t.id_turno_caja
                " . $joinRevision . "
                WHERE " . implode(" AND ", $where) . "
                ORDER BY ABS(COALESCE(t.diferencia, 0)) DESC, t.fecha_cierre DESC
                LIMIT " . intval($limite);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $diferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resumen = array(
                "total_registros" => count($diferencias),
                "faltantes_total" => 0,
                "sobrantes_total" => 0,
                "diferencia_neta" => 0,
                "por_estado" => array()
            );
            foreach ($diferencias as $item) {
                $diferencia = round(floatval($this->valor($item, "diferencia", 0)), 6);
                if ($diferencia < 0) {
                    $resumen["faltantes_total"] += abs($diferencia);
                } elseif ($diferencia > 0) {
                    $resumen["sobrantes_total"] += $diferencia;
                }
                $resumen["diferencia_neta"] += $diferencia;
                $estadoItem = (string) $this->valor($item, "estado_revision", "pendiente_revision");
                if (!isset($resumen["por_estado"][$estadoItem])) {
                    $resumen["por_estado"][$estadoItem] = array("registros" => 0, "diferencia_neta" => 0);
                }
                $resumen["por_estado"][$estadoItem]["registros"]++;
                $resumen["por_estado"][$estadoItem]["diferencia_neta"] += $diferencia;
            }
            foreach (array("faltantes_total", "sobrantes_total", "diferencia_neta") as $campo) {
                $resumen[$campo] = round($resumen[$campo], 6);
            }
            foreach ($resumen["por_estado"] as $key => $datosEstado) {
                $resumen["por_estado"][$key]["diferencia_neta"] = round($datosEstado["diferencia_neta"], 6);
            }

            return $this->respuesta(false, "success", "Diferencias de caja consultadas", array(
                "read_only" => true,
                "schema_revision_pendiente" => !$tieneRevision,
                "filtros" => array(
                    "fecha_desde" => $fechaDesde,
                    "fecha_hasta" => $fechaHasta,
                    "id_almacen" => $idAlmacen,
                    "id_caja" => $idCaja,
                    "estado_revision" => $estado,
                    "limite" => $limite
                ),
                "resumen" => $resumen,
                "diferencias" => $diferencias,
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_resuelve_diferencias" => true,
                    "no_ajusta_caja" => true,
                    "prepara_flujo_revision" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", "No se pudieron consultar diferencias de caja", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: registrar expediente formal para revisar una diferencia de caja ya cerrada.
     * Impacto: crea seguimiento administrativo sin modificar `erp_pos_turnos`, caja, ventas ni inventario.
     * Contrato: escritura transaccional; requiere turno cerrado con diferencia distinta de cero.
     */
    public function registrarRevisionDiferenciaCajaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
        $motivo = trim((string) $this->valor($datos, "motivo", ""));
        $diagnostico = trim((string) $this->valor($datos, "diagnostico", ""));
        $responsable = trim((string) $this->valor($datos, "responsable", $this->valor($datos, "responsable_revision", "")));
        $evidencia = trim((string) $this->valor($datos, "evidencia_referencia", ""));

        if ($idUsuario <= 0) {
            return $this->respuesta(false, "warning", "Usuario obligatorio para registrar revision", array("bloqueos" => array("usuario_obligatorio")));
        }
        if ($idTurno <= 0) {
            return $this->respuesta(false, "warning", "Turno obligatorio para registrar revision", array("bloqueos" => array("turno_obligatorio")));
        }
        if ($motivo === "") {
            return $this->respuesta(false, "warning", "Motivo obligatorio para registrar revision", array("bloqueos" => array("motivo_obligatorio")));
        }
        if (!$this->tablaExiste($db, "erp_pos_turnos") || !$this->tablaExiste($db, "erp_pos_turnos_diferencias_revision")) {
            return $this->respuesta(false, "warning", "Esquema de revision de diferencias pendiente", array("bloqueos" => array("schema_revision_diferencias_pendiente")));
        }
        if (!$this->usuarioTienePermisoDb($db, $idUsuario, "ventas.caja_diferencias.resolver")) {
            return $this->respuesta(false, "warning", "Permiso insuficiente para resolver diferencia de caja", array(
                "bloqueos" => array("permiso_insuficiente"),
                "permiso_requerido" => "ventas.caja_diferencias.resolver"
            ));
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_pos_turnos WHERE id_turno_caja=:turno LIMIT 1 FOR UPDATE");
            $stmt->execute(array(":turno" => $idTurno));
            $turno = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$turno) {
                throw new Exception("Turno no encontrado");
            }
            if ($this->valor($turno, "estatus", "") !== "cerrado") {
                throw new Exception("Solo se puede revisar una diferencia de turno cerrado");
            }
            $diferencia = round(floatval($this->valor($turno, "diferencia", 0)), 6);
            if (abs($diferencia) <= 0.0001) {
                throw new Exception("El turno no tiene diferencia para revisar");
            }

            $stmt = $db->prepare("SELECT * FROM erp_pos_turnos_diferencias_revision WHERE id_turno_caja=:turno LIMIT 1");
            $stmt->execute(array(":turno" => $idTurno));
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existente) {
                $db->commit();
                return $this->respuesta(false, "info", "El turno ya tiene expediente de revision", array(
                    "id_diferencia_revision" => intval($existente["id_diferencia_revision"]),
                    "folio" => $this->valor($existente, "folio", ""),
                    "id_turno_caja" => $idTurno,
                    "estatus" => $this->valor($existente, "estatus", ""),
                    "no_duplica_expediente" => true
                ));
            }

            $folio = $this->generarFolioRevisionDiferenciaCaja($db);
            $tipo = $diferencia < 0 ? "faltante" : "sobrante";
            $snapshot = array(
                "turno" => $turno,
                "motivo" => $motivo,
                "responsable_revision" => $responsable,
                "evidencia_referencia" => $evidencia,
                "registrado_por" => $idUsuario
            );

            $stmt = $db->prepare("INSERT INTO erp_pos_turnos_diferencias_revision
                (folio, id_turno_caja, id_almacen, id_caja, tipo_diferencia, monto_diferencia,
                 estatus, motivo, diagnostico, evidencia_referencia, responsable_revision,
                 solicitado_por, fecha_revision, datos_snapshot, fecha_actualizacion)
                VALUES
                (:folio, :turno, :almacen, :caja, :tipo, :monto,
                 'pendiente_revision', :motivo, :diagnostico, :evidencia, :responsable,
                 :usuario, NOW(), :snapshot, NOW())");
            $stmt->execute(array(
                ":folio" => $folio,
                ":turno" => $idTurno,
                ":almacen" => intval($this->valor($turno, "id_almacen", 0)),
                ":caja" => intval($this->valor($turno, "id_caja", 0)),
                ":tipo" => $tipo,
                ":monto" => $diferencia,
                ":motivo" => $motivo,
                ":diagnostico" => $diagnostico,
                ":evidencia" => $evidencia,
                ":responsable" => $responsable,
                ":usuario" => $idUsuario,
                ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE)
            ));
            $idRevision = intval($db->lastInsertId());
            $db->commit();

            return $this->respuesta(false, "success", "Revision de diferencia registrada", array(
                "id_diferencia_revision" => $idRevision,
                "folio" => $folio,
                "id_turno_caja" => $idTurno,
                "turno_folio" => $this->valor($turno, "folio", ""),
                "tipo_diferencia" => $tipo,
                "monto_diferencia" => $diferencia,
                "estatus" => "pendiente_revision",
                "motivo" => $motivo,
                "responsable_revision" => $responsable,
                "evidencia_referencia" => $evidencia,
                "no_modifica_turno" => true,
                "no_mueve_caja" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo registrar revision de diferencia", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: resolver administrativamente un expediente de diferencia de caja POS.
     * Impacto: cambia solo el seguimiento de la diferencia; conserva el cierre historico y no mueve dinero.
     * Contrato: escritura transaccional; requiere expediente pendiente/en revision, decision valida y motivo.
     */
    public function resolverRevisionDiferenciaCajaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $folio = trim((string) $this->valor($datos, "folio", ""));
        $idRevision = intval($this->valor($datos, "id_diferencia_revision", 0));
        $decision = trim((string) $this->valor($datos, "decision", ""));
        $motivo = trim((string) $this->valor($datos, "motivo", $this->valor($datos, "motivo_resolucion", "")));
        $diagnostico = trim((string) $this->valor($datos, "diagnostico", ""));
        $evidencia = trim((string) $this->valor($datos, "evidencia_referencia", ""));
        $permitidas = array("explicada", "aceptada", "ajustada", "escalada", "cancelada");

        if ($idUsuario <= 0) {
            return $this->respuesta(false, "warning", "Usuario obligatorio para resolver revision", array("bloqueos" => array("usuario_obligatorio")));
        }
        if ($folio === "" && $idRevision <= 0) {
            return $this->respuesta(false, "warning", "Folio o expediente obligatorio", array("bloqueos" => array("revision_obligatoria")));
        }
        if (!in_array($decision, $permitidas, true)) {
            return $this->respuesta(false, "warning", "Decision invalida para diferencia de caja", array(
                "bloqueos" => array("decision_invalida"),
                "permitidas" => $permitidas
            ));
        }
        if ($motivo === "") {
            return $this->respuesta(false, "warning", "Motivo obligatorio para resolver revision", array("bloqueos" => array("motivo_obligatorio")));
        }
        if (!$this->tablaExiste($db, "erp_pos_turnos") || !$this->tablaExiste($db, "erp_pos_turnos_diferencias_revision")) {
            return $this->respuesta(false, "warning", "Esquema de revision de diferencias pendiente", array("bloqueos" => array("schema_revision_diferencias_pendiente")));
        }

        try {
            $db->beginTransaction();
            if ($idRevision > 0) {
                $stmt = $db->prepare("SELECT dr.*, t.folio turno_folio, t.diferencia diferencia_turno
                    FROM erp_pos_turnos_diferencias_revision dr
                    INNER JOIN erp_pos_turnos t ON t.id_turno_caja=dr.id_turno_caja
                    WHERE dr.id_diferencia_revision=:id
                    LIMIT 1 FOR UPDATE");
                $stmt->execute(array(":id" => $idRevision));
            } else {
                $stmt = $db->prepare("SELECT dr.*, t.folio turno_folio, t.diferencia diferencia_turno
                    FROM erp_pos_turnos_diferencias_revision dr
                    INNER JOIN erp_pos_turnos t ON t.id_turno_caja=dr.id_turno_caja
                    WHERE dr.folio=:folio
                    LIMIT 1 FOR UPDATE");
                $stmt->execute(array(":folio" => $folio));
            }
            $revision = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$revision) {
                throw new Exception("Expediente de diferencia no encontrado");
            }

            $estatusActual = (string) $this->valor($revision, "estatus", "");
            if (!in_array($estatusActual, array("pendiente_revision", "en_revision"), true)) {
                throw new Exception("El expediente ya no admite resolucion directa");
            }

            $diagnosticoFinal = $diagnostico !== "" ? $diagnostico : $motivo;
            $evidenciaFinal = $evidencia !== "" ? $evidencia : (string) $this->valor($revision, "evidencia_referencia", "");
            $snapshot = array(
                "revision_anterior" => $revision,
                "decision" => $decision,
                "motivo_resolucion" => $motivo,
                "diagnostico_resolucion" => $diagnosticoFinal,
                "evidencia_referencia" => $evidenciaFinal,
                "resuelto_por" => $idUsuario
            );

            $stmt = $db->prepare("UPDATE erp_pos_turnos_diferencias_revision
                SET estatus=:estatus,
                    decision=:decision,
                    diagnostico=:diagnostico,
                    evidencia_referencia=:evidencia,
                    resuelto_por=:usuario,
                    fecha_resolucion=NOW(),
                    datos_snapshot=:snapshot,
                    fecha_actualizacion=NOW()
                WHERE id_diferencia_revision=:id");
            $stmt->execute(array(
                ":estatus" => $decision,
                ":decision" => $decision,
                ":diagnostico" => $diagnosticoFinal,
                ":evidencia" => $evidenciaFinal,
                ":usuario" => $idUsuario,
                ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                ":id" => intval($revision["id_diferencia_revision"])
            ));
            $db->commit();

            return $this->respuesta(false, "success", "Revision de diferencia resuelta", array(
                "id_diferencia_revision" => intval($revision["id_diferencia_revision"]),
                "folio" => $this->valor($revision, "folio", ""),
                "id_turno_caja" => intval($revision["id_turno_caja"]),
                "turno_folio" => $this->valor($revision, "turno_folio", ""),
                "monto_diferencia" => round(floatval($this->valor($revision, "monto_diferencia", 0)), 6),
                "decision" => $decision,
                "estatus" => $decision,
                "motivo_resolucion" => $motivo,
                "evidencia_referencia" => $evidenciaFinal,
                "turno_diferencia_historica" => round(floatval($this->valor($revision, "diferencia_turno", 0)), 6),
                "no_modifica_turno" => true,
                "no_mueve_caja" => true,
                "no_mueve_inventario" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo resolver revision de diferencia", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: auditar cobertura del modulo Ventas/POS/Pedidos sin escribir datos.
     * Impacto: muestra pendientes de esquema, cajas, turnos, almacenes POS y trazabilidad.
     * Contrato: read-only; no usa legacy como fuente de operacion.
     */
    public function diagnosticoModuloVentas() {
        try {
            $db = $this->getConexion();
            if (!$db) {
                return $this->respuesta(false, "warning", "Diagnostico Ventas/POS/Pedidos generado sin conexion MySQL", array(
                    "almacenes_pos" => array(),
                    "cajas_pos" => array(),
                    "turnos_abiertos" => array(),
                    "tablas" => array(),
                    "hallazgos" => array(
                        array("id" => "VENTAS-DIAG-000", "severidad" => "alta", "mensaje" => "Conexion MySQL no disponible para auditoria operativa")
                    ),
                    "legacy_ecommerce_separado" => true,
                    "conexion_mysql" => false
                ));
            }
            $tablas = array(
                "erp_pos_cajas",
                "erp_pos_terminales",
                "erp_pos_usuarios_cajas",
                "erp_pos_turnos",
                "erp_pos_movimientos_caja",
                "erp_ventas",
                "erp_ventas_detalle",
                "erp_ventas_detalle_inventario",
                "erp_ventas_pagos",
                "erp_ventas_devoluciones",
                "erp_ventas_devoluciones_detalle",
                "erp_inventario_reservas",
                "erp_inventario_movimientos",
                "erp_inventario_unidades",
                "erp_inventario_existencias"
            );
            $estadoTablas = array();
            foreach ($tablas as $tabla) {
                $estadoTablas[] = array("tabla" => $tabla, "existe" => $this->tablaExiste($db, $tabla));
            }
            $almacenes = $this->listarAlmacenesVenta($db);
            $cajas = $this->listarCajasPos($db);
            $turnos = $this->listarTurnosAbiertosPos($db);
            $hallazgos = array();
            if (empty($almacenes)) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-001", "severidad" => "alta", "mensaje" => "No hay almacenes con permite_venta=1");
            }
            if (!$this->tablaExiste($db, "erp_pos_cajas")) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-002", "severidad" => "alta", "mensaje" => "Falta esquema de cajas POS");
            } elseif (empty($cajas)) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-003", "severidad" => "alta", "mensaje" => "No hay cajas POS activas");
            }
            if (!$this->tablaExiste($db, "erp_pos_terminales")) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-007", "severidad" => "alta", "mensaje" => "Falta esquema de terminales POS");
            }
            if (!$this->tablaExiste($db, "erp_pos_usuarios_cajas")) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-008", "severidad" => "alta", "mensaje" => "Falta esquema de asignacion usuario/caja/terminal");
            }
            if (!$this->tablaExiste($db, "erp_pos_turnos")) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-004", "severidad" => "alta", "mensaje" => "Falta esquema de turnos POS");
            } elseif (empty($turnos)) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-005", "severidad" => "media", "mensaje" => "No hay turnos POS abiertos");
            }
            if (!$this->schemaVentaPosCompleto($db)) {
                $hallazgos[] = array("id" => "VENTAS-DIAG-006", "severidad" => "alta", "mensaje" => "Falta esquema completo de venta POS real");
            }
            return $this->respuesta(false, empty($hallazgos) ? "success" : "warning", "Diagnostico Ventas/POS/Pedidos generado", array(
                "almacenes_pos" => $almacenes,
                "cajas_pos" => $cajas,
                "turnos_abiertos" => $turnos,
                "tablas" => $estadoTablas,
                "hallazgos" => $hallazgos,
                "legacy_ecommerce_separado" => true
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: proponer configuracion inicial de cajas POS por almacen de venta.
     * Impacto: deja listo el seed operativo multi-sucursal sin escribir BD.
     * Contrato: read-only; no crea cajas ni modifica almacenes.
     */
    public function planCajasInicialesPos($filtros = array()) {
        try {
            $db = $this->getConexion();
            if (!$db) {
                return $this->respuesta(true, "warning", "No se puede proponer cajas POS sin conexion MySQL", array(
                    "dry_run" => true,
                    "schema_cajas_pendiente" => true,
                    "propuestas" => array(),
                    "bloqueos" => array("Conexion MySQL no disponible para leer almacenes con permite_venta=1")
                ));
            }
            $almacenes = $this->listarAlmacenesVenta($db);
            $cajasExistentes = $this->listarCajasPos($db);
            $porAlmacen = array();
            foreach ($cajasExistentes as $caja) {
                $porAlmacen[intval($caja["id_almacen"])][] = $caja;
            }
            $propuestas = array();
            foreach ($almacenes as $almacen) {
                $idAlmacen = intval($almacen["id_almacen"]);
                $codigoAlmacen = strtoupper(trim((string) $almacen["codigo_almacen"]));
                $existentes = isset($porAlmacen[$idAlmacen]) ? $porAlmacen[$idAlmacen] : array();
                $propuestas[] = array(
                    "id_almacen" => $idAlmacen,
                    "codigo_almacen" => $codigoAlmacen,
                    "almacen" => $almacen["almacen"],
                    "nombre_comercial" => $almacen["nombre_comercial"],
                    "cajas_existentes" => count($existentes),
                    "crear" => empty($existentes),
                    "caja_sugerida" => array(
                        "codigo" => "CJ-" . preg_replace('/[^A-Z0-9]/', '', $codigoAlmacen) . "-01",
                        "nombre" => "Caja principal " . ($almacen["nombre_comercial"] ?: $almacen["almacen"]),
                        "id_almacen" => $idAlmacen,
                        "estatus" => "activa",
                        "permite_efectivo" => 1,
                        "permite_tarjeta" => 1,
                        "permite_transferencia" => 1
                    )
                );
            }
            return $this->respuesta(false, "success", "Plan inicial de cajas POS generado", array(
                "dry_run" => true,
                "schema_cajas_pendiente" => !$this->tablaExiste($db, "erp_pos_cajas"),
                "propuestas" => $propuestas,
                "siguiente_paso" => "Autorizar respaldo externo, crear erp_pos_cajas y registrar cajas sugeridas"
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: proponer asignacion persistente usuario/terminal/caja/sucursal sin escribir BD.
     * Impacto: prepara POS para no permitir seleccion libre de sucursal en operacion real.
     * Contrato: dry-run; no crea terminal, caja ni asignacion de usuario.
     */
    public function planAsignacionTerminalPos($datos = array()) {
        try {
            $db = $this->getConexion();
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $usuarioNombre = trim((string) $this->valor($datos, "usuario_nombre", ""));
            $planCajas = $this->planCajasInicialesPos();
            if (!empty($planCajas["error"])) {
                return $this->respuesta(true, "warning", "No se puede proponer terminales sin plan de cajas POS", array(
                    "dry_run" => true,
                    "schema_terminales_pendiente" => true,
                    "schema_usuarios_cajas_pendiente" => true,
                    "usuario" => array("id_usuario" => $idUsuario, "nombre" => $usuarioNombre),
                    "propuestas" => array(),
                    "bloqueos" => $this->valor($this->valor($planCajas, "depurar", array()), "bloqueos", array())
                ));
            }
            $depurarPlanCajas = $this->valor($planCajas, "depurar", array());
            $propuestasCajas = isset($depurarPlanCajas["propuestas"]) && is_array($depurarPlanCajas["propuestas"])
                ? $depurarPlanCajas["propuestas"]
                : array();
            $propuestas = array();
            foreach ($propuestasCajas as $propuestaCaja) {
                $codigoAlmacen = $propuestaCaja["codigo_almacen"];
                $caja = $propuestaCaja["caja_sugerida"];
                $codigoTerminal = "TERM-" . preg_replace('/[^A-Z0-9]/', '', strtoupper($codigoAlmacen)) . "-01";
                $propuestas[] = array(
                    "id_usuario" => $idUsuario,
                    "usuario_nombre" => $usuarioNombre,
                    "id_almacen" => $propuestaCaja["id_almacen"],
                    "codigo_almacen" => $codigoAlmacen,
                    "terminal_sugerida" => array(
                        "codigo" => $codigoTerminal,
                        "nombre" => "Terminal principal " . ($propuestaCaja["nombre_comercial"] ?: $propuestaCaja["almacen"]),
                        "id_almacen" => $propuestaCaja["id_almacen"],
                        "caja_codigo" => $caja["codigo"],
                        "estatus" => "activa"
                    ),
                    "asignacion_sugerida" => array(
                        "id_usuario" => $idUsuario,
                        "id_almacen" => $propuestaCaja["id_almacen"],
                        "caja_codigo" => $caja["codigo"],
                        "terminal_codigo" => $codigoTerminal,
                        "estatus" => "activo",
                        "prioridad" => 1
                    )
                );
            }
            return $this->respuesta(false, "success", "Plan de asignacion terminal POS generado", array(
                "dry_run" => true,
                "schema_terminales_pendiente" => !$this->tablaExiste($db, "erp_pos_terminales"),
                "schema_usuarios_cajas_pendiente" => !$this->tablaExiste($db, "erp_pos_usuarios_cajas"),
                "usuario" => array("id_usuario" => $idUsuario, "nombre" => $usuarioNombre),
                "propuestas" => $propuestas,
                "regla_operativa" => "En operacion real el POS debe abrir con la asignacion activa del usuario/terminal; el selector libre queda solo para configuracion autorizada."
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: consultar asignacion activa usuario/caja/terminal para POS real.
     * Impacto: permite abrir POS ligado al operador sin seleccion libre de sucursal.
     * Contrato: read-only; si falta esquema o asignacion devuelve pendiente/bloqueos sin escribir BD.
     */
    public function asignacionActualTerminalPos($datos = array()) {
        try {
            $db = $this->getConexion();
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $schemaPendiente = !$this->tablaExiste($db, "erp_pos_usuarios_cajas")
                || !$this->tablaExiste($db, "erp_pos_terminales")
                || !$this->tablaExiste($db, "erp_pos_cajas");

            if ($schemaPendiente) {
                return $this->respuesta(false, "warning", "Asignacion POS pendiente de esquema autorizado", array(
                    "schema_pendiente" => true,
                    "asignacion_activa" => false,
                    "id_usuario" => $idUsuario,
                    "bloqueos" => array("Falta esquema de terminales/asignaciones POS"),
                    "modo_ui" => "configuracion_local_uat"
                ));
            }
            if ($idUsuario <= 0) {
                return $this->respuesta(false, "warning", "Usuario ERP no identificado para POS", array(
                    "schema_pendiente" => false,
                    "asignacion_activa" => false,
                    "id_usuario" => $idUsuario,
                    "bloqueos" => array("Sesion sin id_usuario valido"),
                    "modo_ui" => "bloqueado"
                ));
            }

            $sql = "SELECT uc.id_usuario_caja, uc.id_usuario, uc.id_almacen, uc.id_caja,
                    uc.id_terminal_pos, uc.estatus, uc.prioridad,
                    c.codigo caja_codigo, c.nombre caja_nombre,
                    t.codigo terminal_codigo, t.nombre terminal_nombre,
                    a.codigo_almacen, a.almacen, a.nombre_comercial
                FROM erp_pos_usuarios_cajas uc
                INNER JOIN erp_pos_cajas c ON c.id_caja=uc.id_caja
                    AND c.id_almacen=uc.id_almacen
                    AND COALESCE(c.estatus, 'activa')='activa'
                LEFT JOIN erp_pos_terminales t ON t.id_terminal_pos=uc.id_terminal_pos
                    AND COALESCE(t.estatus, 'activa')='activa'
                LEFT JOIN erp_almacenes a ON a.id_almacen=uc.id_almacen
                WHERE uc.id_usuario=:usuario
                  AND uc.estatus='activo'
                  AND (uc.fecha_fin IS NULL OR uc.fecha_fin >= NOW())
                ORDER BY uc.prioridad ASC, uc.fecha_inicio DESC, uc.id_usuario_caja DESC
                LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(":usuario" => $idUsuario));
            $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$asignacion) {
                return $this->respuesta(false, "warning", "Usuario sin asignacion POS activa", array(
                    "schema_pendiente" => false,
                    "asignacion_activa" => false,
                    "id_usuario" => $idUsuario,
                    "bloqueos" => array("Configura asignacion usuario/caja/terminal antes de operar POS"),
                    "modo_ui" => "configuracion_autorizada_requerida"
                ));
            }

            $turno = null;
            if ($this->tablaExiste($db, "erp_pos_turnos")) {
                $stmtTurno = $db->prepare("SELECT id_turno_caja, folio, id_caja, id_almacen, fecha_apertura
                    FROM erp_pos_turnos
                    WHERE id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'
                    ORDER BY fecha_apertura DESC, id_turno_caja DESC
                    LIMIT 1");
                $stmtTurno->execute(array(
                    ":caja" => intval($asignacion["id_caja"]),
                    ":almacen" => intval($asignacion["id_almacen"])
                ));
                $turno = $stmtTurno->fetch(PDO::FETCH_ASSOC);
            }

            return $this->respuesta(false, "success", "Asignacion POS activa consultada", array(
                "schema_pendiente" => false,
                "asignacion_activa" => true,
                "modo_ui" => "asignacion_oficial",
                "asignacion" => $asignacion,
                "turno_abierto" => $turno ?: null,
                "bloqueos" => $turno ? array() : array("No hay turno abierto para esta caja")
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: simular apertura de turno POS para validar caja/almacen/monto inicial.
     * Impacto: define contrato de apertura antes de crear `erp_pos_turnos`.
     * Contrato: dry-run; no inserta turno ni movimiento de caja.
     */
    public function aperturaTurnoDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $montoInicial = round(floatval($this->valor($datos, "monto_inicial", 0)), 6);
            $bloqueos = array();
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona tienda/almacen";
            }
            if (!$this->tablaExiste($db, "erp_pos_cajas")) {
                $bloqueos[] = "Esquema de cajas pendiente";
            } elseif (!empty($this->validarCajaOperativa($db, $idAlmacen, $idCaja))) {
                $bloqueos[] = "Selecciona caja activa de la tienda";
            }
            if (!$this->tablaExiste($db, "erp_pos_turnos")) {
                $bloqueos[] = "Esquema de turnos pendiente";
            } elseif ($idAlmacen > 0 && $idCaja > 0) {
                $stmt = $db->prepare("SELECT folio
                    FROM erp_pos_turnos
                    WHERE id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'
                    ORDER BY fecha_apertura DESC, id_turno_caja DESC
                    LIMIT 1");
                $stmt->execute(array(
                    ":caja" => $idCaja,
                    ":almacen" => $idAlmacen
                ));
                $turnoAbierto = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($turnoAbierto) {
                    $bloqueos[] = "Ya existe turno abierto para esta caja: " . $this->valor($turnoAbierto, "folio", "");
                }
            }
            if ($montoInicial < 0) {
                $bloqueos[] = "El monto inicial no puede ser negativo";
            }
            $folioSugerido = "TUR-" . date("Ymd") . "-" . str_pad((string) max(1, $idCaja), 3, "0", STR_PAD_LEFT);
            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de apertura valido" : "Dry-run de apertura bloqueado", array(
                "dry_run" => true,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "monto_inicial" => $montoInicial,
                "folio_sugerido" => $folioSugerido,
                "bloqueos" => $bloqueos,
                "contrato_apertura" => array(
                    "crear_turno_abierto" => true,
                    "registrar_monto_inicial" => true,
                    "ligar_usuario_apertura" => true,
                    "impedir_doble_turno_abierto_por_caja" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: simular cierre de turno POS y calcular diferencia esperada/contada.
     * Impacto: define contrato de corte de caja sin modificar turnos ni movimientos.
     * Contrato: dry-run; no cierra turno, no inserta diferencias y no registra auditoria operativa.
     */
    public function cierreTurnoDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $montoContado = round(floatval($this->valor($datos, "monto_contado", 0)), 6);
            $montoEsperadoCapturado = array_key_exists("monto_esperado", $datos);
            $montoEsperado = round(floatval($this->valor($datos, "monto_esperado", 0)), 6);
            $bloqueos = array();
            $turno = null;
            $ventas = array("ventas" => 0, "total" => 0, "pagado" => 0, "saldo" => 0);
            $pagosPorMetodo = array();
            $movimientosPorTipo = array();
            $esperadoPorMovimientos = 0;
            $avisos = array();
            if (!$db) {
                return $this->respuesta(true, "warning", "No se puede simular cierre sin conexion MySQL", array(
                    "dry_run" => true,
                    "bloqueos" => array("Conexion MySQL no disponible para cierre de turno")
                ));
            }
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona tienda/almacen";
            }
            if (!$this->tablaExiste($db, "erp_pos_turnos")) {
                $bloqueos[] = "Esquema de turnos pendiente";
            } else {
                $turnoBloqueos = $this->validarTurnoOperativo($db, $idAlmacen, $idCaja, $idTurno);
                foreach ($turnoBloqueos as $bloqueo) {
                    $bloqueos[] = $bloqueo;
                }
            }
            if (empty($bloqueos)) {
                $stmt = $db->prepare("SELECT id_turno_caja, folio, id_caja, id_almacen, id_usuario_apertura,
                        monto_inicial, monto_esperado, monto_contado, diferencia, estatus,
                        fecha_apertura, fecha_cierre
                    FROM erp_pos_turnos
                    WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen
                    LIMIT 1");
                $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja, ":almacen" => $idAlmacen));
                $turno = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$turno) {
                    $bloqueos[] = "No se encontro el turno para calcular corte";
                } elseif ($turno["estatus"] !== "abierto") {
                    $bloqueos[] = "El turno no esta abierto";
                }
            }
            if (empty($bloqueos)) {
                if ($this->tablaExiste($db, "erp_ventas")) {
                    $stmt = $db->prepare("SELECT
                            COUNT(*) ventas,
                            COALESCE(SUM(total), 0) total,
                            COALESCE(SUM(pagado_total), 0) pagado,
                            COALESCE(SUM(saldo_total), 0) saldo
                        FROM erp_ventas
                        WHERE id_turno_caja=:turno AND id_caja=:caja
                            AND estatus NOT IN ('cancelada')");
                    $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja));
                    $ventas = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                if ($this->tablaExiste($db, "erp_ventas_pagos")) {
                    $stmt = $db->prepare("SELECT metodo_pago, tipo_pago,
                            COUNT(*) operaciones,
                            COALESCE(SUM(monto), 0) monto
                        FROM erp_ventas_pagos
                        WHERE id_turno_caja=:turno AND id_caja=:caja
                            AND estatus='registrado'
                        GROUP BY metodo_pago, tipo_pago
                        ORDER BY metodo_pago, tipo_pago");
                    $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja));
                    $pagosPorMetodo = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                if ($this->tablaExiste($db, "erp_pos_movimientos_caja")) {
                    $stmt = $db->prepare("SELECT tipo, motivo,
                            COUNT(*) operaciones,
                            COALESCE(SUM(monto), 0) monto
                        FROM erp_pos_movimientos_caja
                        WHERE id_turno_caja=:turno
                        GROUP BY tipo, motivo
                        ORDER BY tipo, motivo");
                    $stmt->execute(array(":turno" => $idTurno));
                    $movimientosPorTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($movimientosPorTipo as $movimiento) {
                        $tipo = strtolower(trim((string) $movimiento["tipo"]));
                        $monto = round(floatval($movimiento["monto"]), 6);
                        if (in_array($tipo, array("entrada", "ingreso", "abono", "liquidacion"))) {
                            $esperadoPorMovimientos += $monto;
                        } elseif (in_array($tipo, array("salida", "retiro", "gasto", "reembolso", "devolucion", "vale"))) {
                            $esperadoPorMovimientos -= $monto;
                        } else {
                            $avisos[] = "Tipo de movimiento no clasificado para esperado: " . $tipo;
                        }
                    }
                    $esperadoPorMovimientos = round($esperadoPorMovimientos, 6);
                }
                if (!$montoEsperadoCapturado) {
                    $montoEsperado = round(floatval($turno["monto_esperado"]), 6);
                }
            }
            if ($montoContado < 0 || $montoEsperado < 0) {
                $bloqueos[] = "Los montos de corte no pueden ser negativos";
            }
            $diferencia = round($montoContado - $montoEsperado, 6);
            if (abs($diferencia) > 0.0001) {
                $avisos[] = $diferencia > 0
                    ? "Cierre con sobrante de caja; se permite cerrar y debe revisarse en reportes"
                    : "Cierre con faltante de caja; se permite cerrar y debe revisarse en reportes";
            }
            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de cierre valido" : "Dry-run de cierre bloqueado", array(
                "dry_run" => true,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "monto_esperado" => $montoEsperado,
                "monto_contado" => $montoContado,
                "diferencia" => $diferencia,
                "turno" => $turno ?: null,
                "resumen" => array(
                    "ventas" => array(
                        "operaciones" => intval($this->valor($ventas, "ventas", 0)),
                        "total" => round(floatval($this->valor($ventas, "total", 0)), 6),
                        "pagado" => round(floatval($this->valor($ventas, "pagado", 0)), 6),
                        "saldo" => round(floatval($this->valor($ventas, "saldo", 0)), 6)
                    ),
                    "pagos_por_metodo" => $pagosPorMetodo,
                    "movimientos_por_tipo" => $movimientosPorTipo,
                    "esperado_por_movimientos" => $esperadoPorMovimientos,
                    "esperado_origen" => $montoEsperadoCapturado ? "capturado" : "turno.monto_esperado"
                ),
                "avisos" => $avisos,
                "bloqueos" => $bloqueos,
                "contrato_cierre" => array(
                    "validar_turno_abierto" => true,
                    "calcular_ventas_y_movimientos" => true,
                    "registrar_monto_contado" => true,
                    "guardar_diferencia" => true,
                    "permite_cerrar_con_diferencia" => true,
                    "diferencia_alimenta_reportes" => true,
                    "impedir_ventas_en_turno_cerrado" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-04.
     * Proposito: cerrar turno POS real desde UI con validacion transaccional y arqueo capturado.
     * Impacto: actualiza `erp_pos_turnos` con monto contado, esperado, diferencia, usuario y fecha de cierre.
     * Contrato: escribe BD; requiere usuario con asignacion activa, turno abierto, dry-run sin bloqueos y confirmacion `CERRAR TURNO`.
     */
    public function cerrarTurnoRealPos($datos = array()) {
        $db = null;
        try {
            $db = $this->getConexion();
            if (!$db) {
                return $this->respuesta(true, "warning", "No se puede cerrar turno sin conexion MySQL", array(
                    "bloqueos" => array("Conexion MySQL no disponible para cierre real")
                ));
            }

            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $montoContado = round(floatval($this->valor($datos, "monto_contado", 0)), 6);
            $observaciones = trim((string) $this->valor($datos, "observaciones", ""));
            $confirmacion = strtoupper(trim((string) $this->valor($datos, "confirmacion", "")));
            $bloqueos = array();

            if ($idUsuario <= 0) {
                $bloqueos[] = "Usuario invalido para cierre";
            }
            if ($montoContado < 0) {
                $bloqueos[] = "El monto contado no puede ser negativo";
            }
            if ($confirmacion !== "CERRAR TURNO") {
                $bloqueos[] = "Escribe CERRAR TURNO para confirmar";
            }
            foreach (array("erp_pos_cajas", "erp_pos_usuarios_cajas", "erp_pos_turnos", "erp_pos_movimientos_caja") as $tabla) {
                if (!$this->tablaExiste($db, $tabla)) {
                    $bloqueos[] = "Falta tabla requerida: " . $tabla;
                }
            }

            $asignacionRespuesta = $idUsuario > 0 ? $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario)) : array();
            $depurarAsignacion = $this->valor($asignacionRespuesta, "depurar", array());
            $asignacion = $this->valor($depurarAsignacion, "asignacion", array());
            $turnoAsignado = $this->valor($depurarAsignacion, "turno_abierto", array());
            if (empty($depurarAsignacion["asignacion_activa"]) || empty($asignacion)) {
                $bloqueos[] = "Usuario sin asignacion POS activa";
            } else {
                $idAlmacenAsignado = intval($this->valor($asignacion, "id_almacen", 0));
                $idCajaAsignada = intval($this->valor($asignacion, "id_caja", 0));
                if ($idAlmacen <= 0) {
                    $idAlmacen = $idAlmacenAsignado;
                }
                if ($idCaja <= 0) {
                    $idCaja = $idCajaAsignada;
                }
                if ($idAlmacen !== $idAlmacenAsignado || $idCaja !== $idCajaAsignada) {
                    $bloqueos[] = "El cierre debe corresponder a la caja asignada al usuario";
                }
            }
            if (empty($turnoAsignado)) {
                $bloqueos[] = "Usuario sin turno abierto";
            } elseif ($idTurno <= 0) {
                $idTurno = intval($this->valor($turnoAsignado, "id_turno_caja", 0));
            } elseif ($idTurno !== intval($this->valor($turnoAsignado, "id_turno_caja", 0))) {
                $bloqueos[] = "El turno solicitado no coincide con el turno abierto de la caja asignada";
            }

            if (!empty($bloqueos)) {
                return $this->respuesta(false, "warning", "Cierre real bloqueado", array(
                    "bloqueos" => array_values(array_unique($bloqueos)),
                    "asignacion" => $depurarAsignacion
                ));
            }

            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id_turno_caja, folio, estatus
                FROM erp_pos_turnos
                WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen
                LIMIT 1 FOR UPDATE");
            $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja, ":almacen" => $idAlmacen));
            $turnoBloqueado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$turnoBloqueado || $turnoBloqueado["estatus"] !== "abierto") {
                throw new Exception("El turno ya no esta abierto para la caja asignada");
            }

            $cierre = $this->cierreTurnoDryRun(array(
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "monto_contado" => $montoContado
            ));
            $depurarCierre = $this->valor($cierre, "depurar", array());
            $bloqueosCierre = $this->valor($depurarCierre, "bloqueos", array());
            if (!empty($cierre["error"]) || !empty($bloqueosCierre)) {
                throw new Exception("Cierre bloqueado por dry-run: " . implode("; ", $bloqueosCierre));
            }

            $montoEsperado = round(floatval($this->valor($depurarCierre, "monto_esperado", 0)), 6);
            $diferencia = round(floatval($this->valor($depurarCierre, "diferencia", 0)), 6);

            $stmt = $db->prepare("UPDATE erp_pos_turnos
                SET id_usuario_cierre=:usuario,
                    monto_esperado=:esperado,
                    monto_contado=:contado,
                    diferencia=:diferencia,
                    estatus='cerrado',
                    fecha_cierre=NOW(),
                    observaciones_cierre=:observaciones
                WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'");
            $stmt->execute(array(
                ":usuario" => $idUsuario,
                ":esperado" => $montoEsperado,
                ":contado" => $montoContado,
                ":diferencia" => $diferencia,
                ":observaciones" => $observaciones,
                ":turno" => $idTurno,
                ":caja" => $idCaja,
                ":almacen" => $idAlmacen
            ));
            if ($stmt->rowCount() !== 1) {
                throw new Exception("No se actualizo el turno; posible cambio concurrente");
            }

            $db->commit();
            return $this->respuesta(false, "success", "Turno POS cerrado correctamente", array(
                "id_usuario" => $idUsuario,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "folio" => $this->valor($turnoBloqueado, "folio", ""),
                "monto_esperado" => $montoEsperado,
                "monto_contado" => $montoContado,
                "diferencia" => $diferencia,
                "resumen" => $this->valor($depurarCierre, "resumen", array()),
                "avisos" => $this->valor($depurarCierre, "avisos", array()),
                "contrato" => array(
                    "cerrado_por_usuario_asignado" => true,
                    "permite_diferencia" => true,
                    "no_crea_movimientos_caja" => true,
                    "no_mueve_inventario" => true
                )
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: simular movimientos de caja no venta sin escribir BD.
     * Impacto: prepara caja chica, retiros, vales, reembolsos e ingresos extraordinarios con corte trazable.
     * Contrato: dry-run; no inserta `erp_pos_movimientos_caja` ni modifica esperado del turno.
     */
    public function movimientoCajaDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $tipoSolicitud = strtolower(trim((string) $this->valor($datos, "tipo_movimiento", "")));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            $monto = round(floatval($this->valor($datos, "monto", 0)), 6);
            $referencia = trim((string) $this->valor($datos, "referencia", ""));
            $responsable = trim((string) $this->valor($datos, "responsable", ""));
            $observaciones = trim((string) $this->valor($datos, "observaciones", ""));
            $bloqueos = array();
            $avisos = array();

            $catalogo = $this->catalogoTiposMovimientoCaja();
            if (!$db) {
                return $this->respuesta(true, "warning", "No se puede simular movimiento de caja sin conexion MySQL", array(
                    "dry_run" => true,
                    "bloqueos" => array("Conexion MySQL no disponible para caja")
                ));
            }
            if (!isset($catalogo[$tipoSolicitud])) {
                $bloqueos[] = "Tipo de movimiento de caja invalido";
            }
            if ($monto <= 0) {
                $bloqueos[] = "El monto debe ser mayor a cero";
            }
            if ($motivo === "") {
                $bloqueos[] = "Captura motivo del movimiento";
            }
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona tienda/almacen";
            }
            if (!$this->tablaExiste($db, "erp_pos_movimientos_caja")) {
                $bloqueos[] = "Esquema de movimientos de caja pendiente";
            }
            foreach ($this->validarCajaOperativa($db, $idAlmacen, $idCaja) as $bloqueo) {
                $bloqueos[] = $bloqueo;
            }
            foreach ($this->validarTurnoOperativo($db, $idAlmacen, $idCaja, $idTurno) as $bloqueo) {
                $bloqueos[] = $bloqueo;
            }

            $regla = isset($catalogo[$tipoSolicitud]) ? $catalogo[$tipoSolicitud] : array();
            if (!empty($regla)) {
                if (!empty($regla["requiere_referencia"]) && $referencia === "") {
                    $bloqueos[] = "Captura referencia para " . $regla["nombre"];
                }
                if (!empty($regla["requiere_responsable"]) && $responsable === "") {
                    $bloqueos[] = "Captura responsable para " . $regla["nombre"];
                }
                if (!empty($regla["requiere_autorizacion"])) {
                    $avisos[] = "Este movimiento debe requerir autorizacion antes de ejecutarse en flujo real";
                }
                if (!empty($regla["requiere_evidencia"])) {
                    $avisos[] = "Este movimiento debe permitir adjuntar evidencia/comprobante en flujo real";
                }
            }

            $signo = isset($regla["signo"]) ? intval($regla["signo"]) : 0;
            $montoImpacto = round($monto * $signo, 6);
            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de movimiento de caja valido" : "Dry-run de movimiento de caja bloqueado", array(
                "dry_run" => true,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "tipo_movimiento" => $tipoSolicitud,
                "movimiento" => array(
                    "tipo_caja" => isset($regla["tipo_caja"]) ? $regla["tipo_caja"] : "",
                    "motivo_caja" => isset($regla["motivo_caja"]) ? $regla["motivo_caja"] : $motivo,
                    "nombre" => isset($regla["nombre"]) ? $regla["nombre"] : "",
                    "monto" => $monto,
                    "impacto_esperado" => $montoImpacto,
                    "referencia" => $referencia,
                    "responsable" => $responsable,
                    "observaciones" => $observaciones
                ),
                "bloqueos" => array_values(array_unique($bloqueos)),
                "avisos" => array_values(array_unique($avisos)),
                "catalogo_tipos" => array_values($catalogo),
                "contrato_movimiento_caja" => array(
                    "validar_turno_abierto" => true,
                    "registrar_usuario" => true,
                    "actualizar_monto_esperado" => true,
                    "clasificar_entrada_salida" => true,
                    "guardar_referencia_y_observaciones" => true,
                    "auditar_cancelacion" => true,
                    "no_reemplazar_compras_finanzas" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: simular persistencia de una cuenta local como atencion compartida POS.
     * Impacto: prepara cuentas multiusuario/multidispositivo sin crear ventas, reservas ni movimientos de inventario.
     * Contrato: dry-run; no inserta `erp_pos_atenciones` ni detalle.
     */
    public function atencionPersistenteDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $cliente = trim((string) $this->valor($datos, "cliente_nombre_publico", ""));
            $origen = trim((string) $this->valor($datos, "origen", "pos"));
            $items = $this->decodificarItems($this->valor($datos, "items", array()));
            $bloqueos = array();
            $avisos = array();
            $partidas = array();
            $subtotal = 0;
            $schemaPendiente = !$this->schemaAtencionesPosCompleto($db);

            if (!$db) {
                return $this->respuesta(true, "warning", "No se puede simular atencion sin conexion MySQL", array(
                    "dry_run" => true,
                    "bloqueos" => array("Conexion MySQL no disponible para atenciones POS")
                ));
            }
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona tienda/almacen";
            }
            if (empty($items)) {
                $bloqueos[] = "Agrega partidas antes de crear atencion";
            }
            if ($schemaPendiente) {
                $bloqueos[] = "Esquema de atenciones persistentes pendiente de autorizacion";
            }
            if ($idCaja > 0 || $idTurno > 0) {
                foreach ($this->validarCajaOperativa($db, $idAlmacen, $idCaja) as $bloqueo) {
                    $bloqueos[] = $bloqueo;
                }
                foreach ($this->validarTurnoOperativo($db, $idAlmacen, $idCaja, $idTurno) as $bloqueo) {
                    $bloqueos[] = $bloqueo;
                }
            } else {
                $avisos[] = "Una pantalla movil de vendedor puede crear atencion sin caja, pero caja debe revalidar al cobrar";
            }

            foreach ($items as $indice => $item) {
                $validacion = $this->prevalidarPartida($db, $item, $idAlmacen, $indice + 1);
                $partidas[] = $validacion;
                $subtotal += floatval($validacion["subtotal"]);
                if (!empty($validacion["bloqueos"])) {
                    foreach ($validacion["bloqueos"] as $bloqueo) {
                        $avisos[] = "Disponibilidad: " . $bloqueo;
                    }
                }
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de atencion persistente valido" : "Dry-run de atencion persistente bloqueado", array(
                "dry_run" => true,
                "schema_pendiente" => $schemaPendiente,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "id_usuario" => $idUsuario,
                "cliente_nombre_publico" => $cliente,
                "origen" => $origen !== "" ? $origen : "pos",
                "folio_temporal_sugerido" => "ATN-" . date("Ymd-His"),
                "partidas" => $partidas,
                "totales" => array(
                    "subtotal" => round($subtotal, 6),
                    "total_estimado" => round($subtotal, 6),
                    "partidas" => count($partidas)
                ),
                "bloqueos" => array_values(array_unique($bloqueos)),
                "avisos" => array_values(array_unique($avisos)),
                "contrato_atencion" => array(
                    "crear_encabezado_borrador" => true,
                    "crear_detalle_sin_reserva" => true,
                    "no_descontar_inventario" => true,
                    "no_generar_venta" => true,
                    "permitir_lista_para_cobro" => true,
                    "revalidar_stock_al_convertir" => true,
                    "convertir_a_venta_pedido_o_apartado" => true,
                    "registrar_eventos_y_bloqueos" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: crear una atencion POS persistente para flujo multiusuario/multidispositivo.
     * Impacto: permite que vendedores levanten cuentas y caja las consulte sin crear venta ni mover inventario.
     * Contrato: transaccional; inserta atencion y detalle, no reserva, no descuenta y no registra pagos.
     */
    public function crearAtencionPersistente($datos = array()) {
        $db = $this->getConexion();
        if (!$db) {
            return $this->respuesta(true, "warning", "No hay conexion MySQL para crear atencion");
        }

        $validacion = $this->atencionPersistenteDryRun($datos);
        $depurarValidacion = isset($validacion["depurar"]) && is_array($validacion["depurar"]) ? $validacion["depurar"] : array();
        $bloqueos = isset($depurarValidacion["bloqueos"]) && is_array($depurarValidacion["bloqueos"]) ? $depurarValidacion["bloqueos"] : array();
        if (!empty($validacion["error"]) || !empty($bloqueos)) {
            return $this->respuesta(false, "warning", "Atencion no creada por bloqueos de prevalidacion", array(
                "prevalidacion" => $validacion,
                "bloqueos" => $bloqueos
            ));
        }

        try {
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idTerminal = intval($this->valor($datos, "id_terminal_pos", 0));
            $clienteNombre = trim((string) $this->valor($datos, "cliente_nombre_publico", ""));
            $identificadorCliente = trim((string) $this->valor($datos, "identificador_cliente", ""));
            $origen = trim((string) $this->valor($datos, "origen", "pos"));
            $estatus = trim((string) $this->valor($datos, "estatus", "abierta"));
            if (!in_array($estatus, array("abierta", "lista_para_cobro"), true)) {
                $estatus = "abierta";
            }
            $cliente = $this->resolverClienteDryRun($db, intval($this->valor($datos, "id_cliente", 0)), $identificadorCliente, !$this->schemaClientesCrmDisponible($db));
            $idCliente = intval($this->valor($cliente, "id_cliente", 0));
            if ($clienteNombre === "" && !empty($cliente["nombre_publico"])) {
                $clienteNombre = $cliente["nombre_publico"];
            }

            $partidas = isset($depurarValidacion["partidas"]) && is_array($depurarValidacion["partidas"]) ? $depurarValidacion["partidas"] : array();
            $totales = isset($depurarValidacion["totales"]) && is_array($depurarValidacion["totales"]) ? $depurarValidacion["totales"] : array();
            $folio = $this->generarFolioAtencion($db);

            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO erp_pos_atenciones
                (folio_temporal, id_almacen, id_caja, id_terminal_pos, id_turno_caja, id_usuario, id_cliente,
                 cliente_nombre_publico, cliente_identificador_publico, estatus, origen, subtotal, descuento_total,
                 impuestos_total, total, pagos_temporales_total, creado_por, fecha_actualizacion)
                VALUES
                (:folio, :almacen, :caja, :terminal, :turno, :usuario, :cliente,
                 :cliente_nombre, :cliente_identificador, :estatus, :origen, :subtotal, 0,
                 0, :total, 0, :creado_por, CURRENT_TIMESTAMP)");
            $stmt->execute(array(
                ":folio" => $folio,
                ":almacen" => $idAlmacen,
                ":caja" => $idCaja > 0 ? $idCaja : null,
                ":terminal" => $idTerminal > 0 ? $idTerminal : null,
                ":turno" => $idTurno > 0 ? $idTurno : null,
                ":usuario" => $idUsuario > 0 ? $idUsuario : null,
                ":cliente" => $idCliente > 0 ? $idCliente : null,
                ":cliente_nombre" => $clienteNombre !== "" ? $clienteNombre : null,
                ":cliente_identificador" => $identificadorCliente !== "" ? $identificadorCliente : null,
                ":estatus" => $estatus,
                ":origen" => $origen !== "" ? $origen : "pos",
                ":subtotal" => round(floatval($this->valor($totales, "subtotal", 0)), 6),
                ":total" => round(floatval($this->valor($totales, "total_estimado", 0)), 6),
                ":creado_por" => $idUsuario > 0 ? $idUsuario : null
            ));
            $idAtencion = intval($db->lastInsertId());

            $stmtDetalle = $db->prepare("INSERT INTO erp_pos_atenciones_detalle
                (id_atencion_pos, renglon, id_producto_erp, id_sku_erp, sku, descripcion, controla_inventario,
                 modo_salida, cantidad_venta, unidad_venta, cantidad_base, unidad_base, precio_unitario,
                 descuento, impuestos, subtotal, total, estatus, datos_snapshot, fecha_actualizacion)
                VALUES
                (:atencion, :renglon, NULL, :sku_id, :sku, :descripcion, :controla,
                 :modo, :cantidad, NULL, :cantidad_base, NULL, :precio,
                 0, 0, :subtotal, :total, 'activa', :snapshot, CURRENT_TIMESTAMP)");
            $itemsOriginales = $this->decodificarItems($this->valor($datos, "items", array()));
            foreach ($partidas as $partida) {
                $indicePartida = max(0, intval($this->valor($partida, "renglon", 1)) - 1);
                $itemOriginal = isset($itemsOriginales[$indicePartida]) && is_array($itemsOriginales[$indicePartida]) ? $itemsOriginales[$indicePartida] : array();
                $modoSalida = (string) $this->valor($itemOriginal, "modo_salida", "existencia_agregada");
                $stmtDetalle->execute(array(
                    ":atencion" => $idAtencion,
                    ":renglon" => intval($this->valor($partida, "renglon", 1)),
                    ":sku_id" => intval($this->valor($partida, "id_sku", 0)),
                    ":sku" => (string) $this->valor($partida, "sku", ""),
                    ":descripcion" => (string) $this->valor($partida, "descripcion", ""),
                    ":controla" => intval($this->valor($partida, "controla_inventario", 0)),
                    ":modo" => $modoSalida,
                    ":cantidad" => round(floatval($this->valor($partida, "cantidad", 0)), 6),
                    ":cantidad_base" => round(floatval($this->valor($partida, "cantidad", 0)), 6),
                    ":precio" => round(floatval($this->valor($partida, "precio_unitario", 0)), 6),
                    ":subtotal" => round(floatval($this->valor($partida, "subtotal", 0)), 6),
                    ":total" => round(floatval($this->valor($partida, "subtotal", 0)), 6),
                    ":snapshot" => json_encode($partida, JSON_UNESCAPED_UNICODE)
                ));
            }
            $db->commit();

            return $this->respuesta(false, "success", "Atencion POS creada", array(
                "id_atencion_pos" => $idAtencion,
                "folio_temporal" => $folio,
                "id_cliente" => $idCliente,
                "cliente_nombre_publico" => $clienteNombre,
                "partidas" => count($partidas),
                "totales" => $totales,
                "no_reserva_inventario" => true,
                "no_descuenta_inventario" => true,
                "prevalidacion" => $validacion
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: consultar bandeja de atenciones persistentes si el esquema existe.
     * Impacto: prepara POS caja para tomar/cobrar cuentas creadas por vendedores.
     * Contrato: read-only; no bloquea, no toma atenciones y no convierte documentos.
     */
    public function atencionesBandejaDryRun($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            $schemaPendiente = !$this->schemaAtencionesPosCompleto($db);
            if ($schemaPendiente) {
                return $this->respuesta(false, "warning", "Bandeja de atenciones pendiente de esquema", array(
                    "dry_run" => true,
                    "schema_pendiente" => true,
                    "atenciones" => array(),
                    "bloqueos" => array("Aplicar DDL expandido de atenciones antes de compartir cuentas entre dispositivos")
                ));
            }
            $where = array("a.estatus IN ('abierta','borrador','lista_para_cobro','tomada_por_caja')");
            $params = array();
            if ($idAlmacen > 0) {
                $where[] = "a.id_almacen=:almacen";
                $params[":almacen"] = $idAlmacen;
            }
            $sql = "SELECT a.id_atencion_pos, a.folio_temporal, a.id_almacen, al.almacen,
                    a.id_caja, a.id_turno_caja, a.id_usuario, a.cliente_nombre_publico,
                    a.estatus, a.origen, a.total, a.fecha_apertura,
                    COUNT(d.id_atencion_detalle) partidas
                FROM erp_pos_atenciones a
                LEFT JOIN erp_almacenes al ON al.id_almacen=a.id_almacen
                LEFT JOIN erp_pos_atenciones_detalle d ON d.id_atencion_pos=a.id_atencion_pos AND d.estatus='activa'
                WHERE " . implode(" AND ", $where) . "
                GROUP BY a.id_atencion_pos
                ORDER BY a.fecha_apertura ASC
                LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->respuesta(false, "success", "Bandeja de atenciones consultada", array(
                "dry_run" => true,
                "schema_pendiente" => false,
                "atenciones" => $stmt->fetchAll(PDO::FETCH_ASSOC),
                "contrato_bandeja" => array(
                    "tomar_atencion_con_bloqueo" => true,
                    "liberar_atencion" => true,
                    "convertir_a_venta_pedido_apartado" => true,
                    "revalidar_stock_y_precios" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: listar movimientos de caja que requieren evidencia sin modificar registros.
     * Impacto: permite auditar reembolsos/gastos/vales pendientes de comprobante despues del cierre.
     * Contrato: read-only; no adjunta archivos, no aprueba evidencia y no cambia estatus de caja.
     */
    public function evidenciasCajaPendientesReadOnly($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $estado = trim((string) $this->valor($datos, "evidencia_estado", "pendiente"));
            $limite = intval($this->valor($datos, "limite", 50));
            $limite = $limite > 0 && $limite <= 200 ? $limite : 50;

            if (!$this->tablaExiste($db, "erp_pos_movimientos_caja")) {
                return $this->respuesta(false, "warning", "Esquema de movimientos de caja pendiente", array(
                    "read_only" => true,
                    "bloqueos" => array("schema_caja_pendiente")
                ));
            }

            $where = array("mc.requiere_evidencia=1");
            $params = array();
            if ($estado !== "" && $estado !== "todos") {
                $where[] = "mc.evidencia_estado=:estado";
                $params[":estado"] = $estado;
            }
            if ($idAlmacen > 0) {
                $where[] = "mc.id_almacen=:almacen";
                $params[":almacen"] = $idAlmacen;
            }
            if ($idCaja > 0) {
                $where[] = "mc.id_caja=:caja";
                $params[":caja"] = $idCaja;
            }
            if ($idTurno > 0) {
                $where[] = "mc.id_turno_caja=:turno";
                $params[":turno"] = $idTurno;
            }

            $stmt = $db->prepare("SELECT mc.*, t.folio turno_folio, t.estatus turno_estatus,
                    v.folio folio_venta, d.folio folio_devolucion,
                    a.nombre_comercial, c.codigo caja_codigo, c.nombre caja_nombre
                FROM erp_pos_movimientos_caja mc
                LEFT JOIN erp_pos_turnos t ON t.id_turno_caja=mc.id_turno_caja
                LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
                LEFT JOIN erp_ventas_devoluciones d ON d.id_movimiento_caja=mc.id_movimiento_caja
                LEFT JOIN erp_almacenes a ON a.id_almacen=mc.id_almacen
                LEFT JOIN erp_pos_cajas c ON c.id_caja=mc.id_caja
                WHERE " . implode(" AND ", $where) . "
                ORDER BY mc.fecha_registro DESC, mc.id_movimiento_caja DESC
                LIMIT " . intval($limite));
            $stmt->execute($params);
            $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resumen = array(
                "total_registros" => count($pendientes),
                "monto_total" => 0,
                "por_estado" => array()
            );
            foreach ($pendientes as $movimiento) {
                $resumen["monto_total"] += floatval($this->valor($movimiento, "monto", 0));
                $estadoMovimiento = (string) $this->valor($movimiento, "evidencia_estado", "sin_estado");
                if (!isset($resumen["por_estado"][$estadoMovimiento])) {
                    $resumen["por_estado"][$estadoMovimiento] = array("operaciones" => 0, "monto" => 0);
                }
                $resumen["por_estado"][$estadoMovimiento]["operaciones"]++;
                $resumen["por_estado"][$estadoMovimiento]["monto"] += floatval($this->valor($movimiento, "monto", 0));
            }
            $resumen["monto_total"] = $this->redondearPosReal($resumen["monto_total"]);
            foreach ($resumen["por_estado"] as $clave => $datosEstado) {
                $resumen["por_estado"][$clave]["monto"] = $this->redondearPosReal($datosEstado["monto"]);
            }

            return $this->respuesta(false, "success", "Evidencias de caja consultadas", array(
                "read_only" => true,
                "filtros" => array(
                    "id_almacen" => $idAlmacen,
                    "id_caja" => $idCaja,
                    "id_turno_caja" => $idTurno,
                    "evidencia_estado" => $estado,
                    "limite" => $limite
                ),
                "resumen" => $resumen,
                "pendientes" => $pendientes,
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_adjunta_archivos" => true,
                    "no_aprueba_evidencia" => true,
                    "incluye_reembolsos_caja" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: registrar evidencia documental de un movimiento sensible de caja POS.
     * Impacto: inserta evidencia y actualiza estado del movimiento de caja de pendiente a recibida.
     * Contrato: escritura real; requiere usuario, movimiento existente, evidencia requerida y esquema autorizado.
     */
    public function registrarEvidenciaCajaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $idMovimiento = intval($this->valor($datos, "id_movimiento_caja", 0));
        $tipoEvidencia = trim((string) $this->valor($datos, "tipo_evidencia", "comprobante"));
        $titulo = trim((string) $this->valor($datos, "titulo", ""));
        $descripcion = trim((string) $this->valor($datos, "descripcion", ""));
        $archivoRuta = trim((string) $this->valor($datos, "archivo_ruta", ""));
        $archivoNombre = trim((string) $this->valor($datos, "archivo_nombre", ""));
        $archivoMime = trim((string) $this->valor($datos, "archivo_mime", ""));
        $archivoHash = trim((string) $this->valor($datos, "archivo_hash", ""));
        $referenciaExterna = trim((string) $this->valor($datos, "referencia_externa", ""));
        $archivoTamano = $this->valor($datos, "archivo_tamano", null);
        $archivoTamano = $archivoTamano === null || $archivoTamano === "" ? null : intval($archivoTamano);

        if ($idUsuario <= 0) {
            return $this->respuesta(false, "warning", "Usuario obligatorio para registrar evidencia", array("bloqueos" => array("usuario_obligatorio")));
        }
        if ($idMovimiento <= 0) {
            return $this->respuesta(false, "warning", "Movimiento de caja obligatorio", array("bloqueos" => array("movimiento_obligatorio")));
        }
        if ($tipoEvidencia === "") {
            return $this->respuesta(false, "warning", "Tipo de evidencia obligatorio", array("bloqueos" => array("tipo_evidencia_obligatorio")));
        }
        if ($archivoRuta === "" && $referenciaExterna === "" && $descripcion === "") {
            return $this->respuesta(false, "warning", "Captura archivo, referencia externa o descripcion de evidencia", array("bloqueos" => array("evidencia_sin_contenido")));
        }
        if (!$this->tablaExiste($db, "erp_pos_movimientos_caja") || !$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias")) {
            return $this->respuesta(false, "warning", "Esquema de evidencias de caja pendiente", array("bloqueos" => array("schema_evidencias_caja_pendiente")));
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT *
                FROM erp_pos_movimientos_caja
                WHERE id_movimiento_caja=:movimiento
                FOR UPDATE");
            $stmt->execute(array(":movimiento" => $idMovimiento));
            $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$movimiento) {
                throw new Exception("Movimiento de caja no encontrado");
            }
            if (intval($this->valor($movimiento, "requiere_evidencia", 0)) !== 1) {
                throw new Exception("El movimiento no requiere evidencia");
            }
            if (in_array($this->valor($movimiento, "evidencia_estado", ""), array("aprobada"), true)) {
                throw new Exception("El movimiento ya tiene evidencia aprobada; usa un flujo de correccion autorizado");
            }
            if (in_array($this->valor($movimiento, "estatus", ""), array("cancelado", "rechazado"), true)) {
                throw new Exception("El movimiento no admite evidencia por su estatus");
            }

            $snapshot = json_encode(array(
                "movimiento" => $movimiento,
                "captura" => array(
                    "tipo_evidencia" => $tipoEvidencia,
                    "titulo" => $titulo,
                    "descripcion" => $descripcion,
                    "archivo_ruta" => $archivoRuta,
                    "archivo_nombre" => $archivoNombre,
                    "archivo_mime" => $archivoMime,
                    "archivo_tamano" => $archivoTamano,
                    "archivo_hash" => $archivoHash,
                    "referencia_externa" => $referenciaExterna,
                    "creado_por" => $idUsuario
                )
            ), JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja_evidencias
                (id_movimiento_caja, tipo_evidencia, estatus, titulo, descripcion,
                 archivo_ruta, archivo_nombre, archivo_mime, archivo_tamano, archivo_hash,
                 referencia_externa, datos_snapshot, creado_por, fecha_actualizacion)
                VALUES (:movimiento, :tipo, 'recibida', :titulo, :descripcion,
                 :ruta, :nombre, :mime, :tamano, :hash, :referencia, :snapshot, :usuario, NOW())");
            $stmt->execute(array(
                ":movimiento" => $idMovimiento,
                ":tipo" => $tipoEvidencia,
                ":titulo" => $titulo !== "" ? $titulo : null,
                ":descripcion" => $descripcion !== "" ? $descripcion : null,
                ":ruta" => $archivoRuta !== "" ? $archivoRuta : null,
                ":nombre" => $archivoNombre !== "" ? $archivoNombre : null,
                ":mime" => $archivoMime !== "" ? $archivoMime : null,
                ":tamano" => $archivoTamano,
                ":hash" => $archivoHash !== "" ? $archivoHash : null,
                ":referencia" => $referenciaExterna !== "" ? $referenciaExterna : null,
                ":snapshot" => $snapshot,
                ":usuario" => $idUsuario
            ));
            $idEvidencia = intval($db->lastInsertId());

            $db->prepare("UPDATE erp_pos_movimientos_caja
                SET evidencia_estado='recibida',
                    evidencia_ruta=COALESCE(:ruta, evidencia_ruta),
                    fecha_actualizacion=NOW()
                WHERE id_movimiento_caja=:movimiento")
                ->execute(array(
                    ":ruta" => $archivoRuta !== "" ? $archivoRuta : null,
                    ":movimiento" => $idMovimiento
                ));

            $db->commit();
            return $this->respuesta(false, "success", "Evidencia de caja registrada", array(
                "id_evidencia_caja" => $idEvidencia,
                "id_movimiento_caja" => $idMovimiento,
                "evidencia_estado" => "recibida",
                "tipo_evidencia" => $tipoEvidencia,
                "referencia_externa" => $referenciaExterna,
                "requiere_revision" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: consultar evidencias capturadas para movimientos sensibles de caja POS.
     * Impacto: permite revisar comprobantes y estados sin modificar caja, dinero o inventario.
     * Contrato: read-only; no aprueba, no rechaza y no adjunta archivos.
     */
    public function evidenciasCajaDetalleReadOnly($datos = array()) {
        try {
            $db = $this->getConexion();
            $idEvidencia = intval($this->valor($datos, "id_evidencia_caja", 0));
            $idMovimiento = intval($this->valor($datos, "id_movimiento_caja", 0));
            $estado = trim((string) $this->valor($datos, "estatus", ""));
            $limite = intval($this->valor($datos, "limite", 50));
            $limite = $limite > 0 && $limite <= 200 ? $limite : 50;

            if (!$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias")) {
                return $this->respuesta(false, "warning", "Esquema de evidencias de caja pendiente", array(
                    "read_only" => true,
                    "bloqueos" => array("schema_evidencias_caja_pendiente")
                ));
            }
            $tieneCorrecciones = $this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias_correcciones");

            $where = array("1=1");
            $params = array();
            if ($idEvidencia > 0) {
                $where[] = "ev.id_evidencia_caja=:evidencia";
                $params[":evidencia"] = $idEvidencia;
            }
            if ($idMovimiento > 0) {
                $where[] = "ev.id_movimiento_caja=:movimiento";
                $params[":movimiento"] = $idMovimiento;
            }
            if ($estado !== "" && $estado !== "todos") {
                $where[] = "ev.estatus=:estado";
                $params[":estado"] = $estado;
            }

            $selectCorreccion = "";
            $joinCorreccion = "";
            if ($tieneCorrecciones) {
                $selectCorreccion = ",
                    COALESCE(corr_original.id_correccion_evidencia_caja, corr_nueva.id_correccion_evidencia_caja) correccion_id,
                    COALESCE(corr_original.folio, corr_nueva.folio) correccion_folio,
                    COALESCE(corr_original.estatus, corr_nueva.estatus) correccion_estatus,
                    COALESCE(corr_original.tipo_correccion, corr_nueva.tipo_correccion) correccion_tipo,
                    COALESCE(corr_original.decision, corr_nueva.decision) correccion_decision,
                    COALESCE(corr_original.id_evidencia_caja_nueva, corr_nueva.id_evidencia_caja_nueva) correccion_id_evidencia_nueva,
                    CASE
                        WHEN corr_original.id_correccion_evidencia_caja IS NOT NULL THEN 'evidencia_original'
                        WHEN corr_nueva.id_correccion_evidencia_caja IS NOT NULL THEN 'evidencia_correctiva'
                        ELSE ''
                    END correccion_relacion";
                $joinCorreccion = "
                LEFT JOIN (
                    SELECT c1.*
                    FROM erp_pos_movimientos_caja_evidencias_correcciones c1
                    INNER JOIN (
                        SELECT id_evidencia_caja, MAX(id_correccion_evidencia_caja) id_correccion_evidencia_caja
                        FROM erp_pos_movimientos_caja_evidencias_correcciones
                        GROUP BY id_evidencia_caja
                    ) ult ON ult.id_correccion_evidencia_caja=c1.id_correccion_evidencia_caja
                ) corr_original ON corr_original.id_evidencia_caja=ev.id_evidencia_caja
                LEFT JOIN (
                    SELECT c2.*
                    FROM erp_pos_movimientos_caja_evidencias_correcciones c2
                    INNER JOIN (
                        SELECT id_evidencia_caja_nueva, MAX(id_correccion_evidencia_caja) id_correccion_evidencia_caja
                        FROM erp_pos_movimientos_caja_evidencias_correcciones
                        WHERE id_evidencia_caja_nueva IS NOT NULL
                        GROUP BY id_evidencia_caja_nueva
                    ) ult2 ON ult2.id_correccion_evidencia_caja=c2.id_correccion_evidencia_caja
                ) corr_nueva ON corr_nueva.id_evidencia_caja_nueva=ev.id_evidencia_caja";
            }

            $stmt = $db->prepare("SELECT ev.*,
                    mc.tipo movimiento_tipo, mc.categoria movimiento_categoria, mc.monto, mc.referencia, mc.evidencia_estado,
                    mc.id_turno_caja, mc.id_almacen, mc.id_caja,
                    t.folio turno_folio, v.folio folio_venta, d.folio folio_devolucion" . $selectCorreccion . "
                FROM erp_pos_movimientos_caja_evidencias ev
                INNER JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=ev.id_movimiento_caja
                LEFT JOIN erp_pos_turnos t ON t.id_turno_caja=mc.id_turno_caja
                LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
                LEFT JOIN erp_ventas_devoluciones d ON d.id_movimiento_caja=mc.id_movimiento_caja" . $joinCorreccion . "
                WHERE " . implode(" AND ", $where) . "
                ORDER BY ev.fecha_registro DESC, ev.id_evidencia_caja DESC
                LIMIT " . intval($limite));
            $stmt->execute($params);
            $evidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->respuesta(false, "success", "Detalle de evidencias de caja consultado", array(
                "read_only" => true,
                "filtros" => array(
                    "id_evidencia_caja" => $idEvidencia,
                    "id_movimiento_caja" => $idMovimiento,
                    "estatus" => $estado,
                    "limite" => $limite
                ),
                "total_registros" => count($evidencias),
                "evidencias" => $evidencias,
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_aprueba_evidencia" => true,
                    "incluye_correcciones" => $tieneCorrecciones,
                    "no_mueve_dinero" => true,
                    "no_mueve_inventario" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: revisar evidencia documental de un movimiento sensible de caja POS.
     * Impacto: cambia solo el estado de evidencia y del movimiento; no modifica importes, turnos, pagos, kardex ni inventario.
     * Contrato: escritura real; requiere permiso de supervisor y evidencia previamente recibida.
     */
    public function revisarEvidenciaCajaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $idEvidencia = intval($this->valor($datos, "id_evidencia_caja", 0));
        $decision = trim((string) $this->valor($datos, "decision", ""));
        $motivo = trim((string) $this->valor($datos, "motivo", ""));
        $permitirMismoUsuario = intval($this->valor($datos, "permitir_mismo_usuario", 0)) === 1;

        if ($idUsuario <= 0) {
            return $this->respuesta(false, "warning", "Usuario revisor obligatorio", array("bloqueos" => array("usuario_obligatorio")));
        }
        if ($idEvidencia <= 0) {
            return $this->respuesta(false, "warning", "Evidencia obligatoria", array("bloqueos" => array("evidencia_obligatoria")));
        }
        if (!in_array($decision, array("aprobada", "rechazada"), true)) {
            return $this->respuesta(false, "warning", "Decision invalida para evidencia", array("bloqueos" => array("decision_invalida")));
        }
        if ($decision === "rechazada" && $motivo === "") {
            return $this->respuesta(false, "warning", "Motivo obligatorio para rechazar evidencia", array("bloqueos" => array("motivo_rechazo_obligatorio")));
        }
        if (!$this->tablaExiste($db, "erp_pos_movimientos_caja") || !$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias")) {
            return $this->respuesta(false, "warning", "Esquema de evidencias de caja pendiente", array("bloqueos" => array("schema_evidencias_caja_pendiente")));
        }
        $tienePermisoFino = $this->usuarioTienePermisoDb($db, $idUsuario, "ventas.caja_evidencias.revisar");
        $tienePermisoLegacy = $this->usuarioTienePermisoDb($db, $idUsuario, "ventas.autorizar_excepcion_comercial");
        if (!$tienePermisoFino && !$tienePermisoLegacy) {
            return $this->respuesta(false, "warning", "Usuario sin permiso para revisar evidencia de caja", array(
                "bloqueos" => array("permiso_revisor_requerido"),
                "permiso_requerido" => "ventas.caja_evidencias.revisar",
                "permiso_compatibilidad" => "ventas.autorizar_excepcion_comercial"
            ));
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT ev.*, mc.id_movimiento_caja, mc.requiere_evidencia, mc.evidencia_estado, mc.estatus movimiento_estatus
                FROM erp_pos_movimientos_caja_evidencias ev
                INNER JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=ev.id_movimiento_caja
                WHERE ev.id_evidencia_caja=:evidencia
                FOR UPDATE");
            $stmt->execute(array(":evidencia" => $idEvidencia));
            $evidencia = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$evidencia) {
                throw new Exception("Evidencia de caja no encontrada");
            }
            if (intval($this->valor($evidencia, "requiere_evidencia", 0)) !== 1) {
                throw new Exception("El movimiento no requiere evidencia");
            }
            if ($this->valor($evidencia, "estatus", "") !== "recibida") {
                throw new Exception("Solo se pueden revisar evidencias en estado recibida");
            }
            if (in_array($this->valor($evidencia, "movimiento_estatus", ""), array("cancelado"), true)) {
                throw new Exception("El movimiento de caja no admite revision por su estatus");
            }
            if (!$permitirMismoUsuario && intval($this->valor($evidencia, "creado_por", 0)) === $idUsuario) {
                throw new Exception("La evidencia debe revisarla un usuario distinto al que la registro");
            }

            $db->prepare("UPDATE erp_pos_movimientos_caja_evidencias
                SET estatus=:decision,
                    revisado_por=:usuario,
                    fecha_revision=NOW(),
                    motivo_rechazo=:motivo,
                    fecha_actualizacion=NOW()
                WHERE id_evidencia_caja=:evidencia")
                ->execute(array(
                    ":decision" => $decision,
                    ":usuario" => $idUsuario,
                    ":motivo" => $decision === "rechazada" ? $motivo : null,
                    ":evidencia" => $idEvidencia
                ));

            $db->prepare("UPDATE erp_pos_movimientos_caja
                SET evidencia_estado=:decision,
                    fecha_actualizacion=NOW()
                WHERE id_movimiento_caja=:movimiento")
                ->execute(array(
                    ":decision" => $decision,
                    ":movimiento" => intval($this->valor($evidencia, "id_movimiento_caja", 0))
                ));

            $db->commit();
            return $this->respuesta(false, "success", "Evidencia de caja revisada", array(
                "id_evidencia_caja" => $idEvidencia,
                "id_movimiento_caja" => intval($this->valor($evidencia, "id_movimiento_caja", 0)),
                "decision" => $decision,
                "revisado_por" => $idUsuario,
                "requiere_accion" => $decision === "rechazada"
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: solicitar correccion de una evidencia de caja ya aprobada sin modificar la evidencia historica.
     * Impacto: crea folio de correccion auditable; no cambia dinero, inventario, turno ni estado de caja.
     * Contrato: escritura real; requiere permiso fino, evidencia aprobada y motivo obligatorio.
     */
    public function solicitarCorreccionEvidenciaCajaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $idEvidencia = intval($this->valor($datos, "id_evidencia_caja", 0));
        $tipoCorreccion = trim((string) $this->valor($datos, "tipo_correccion", "reemplazo_evidencia"));
        $motivo = trim((string) $this->valor($datos, "motivo", ""));

        if ($idUsuario <= 0) {
            return $this->respuesta(false, "warning", "Usuario obligatorio para solicitar correccion", array("bloqueos" => array("usuario_obligatorio")));
        }
        if ($idEvidencia <= 0) {
            return $this->respuesta(false, "warning", "Evidencia obligatoria", array("bloqueos" => array("evidencia_obligatoria")));
        }
        if (!in_array($tipoCorreccion, array("reemplazo_evidencia", "correccion_datos", "anulacion_operativa"), true)) {
            return $this->respuesta(false, "warning", "Tipo de correccion invalido", array("bloqueos" => array("tipo_correccion_invalido")));
        }
        if ($motivo === "") {
            return $this->respuesta(false, "warning", "Motivo obligatorio para solicitar correccion", array("bloqueos" => array("motivo_obligatorio")));
        }
        if (!$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias") || !$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias_correcciones")) {
            return $this->respuesta(false, "warning", "Esquema de correcciones de evidencias pendiente", array("bloqueos" => array("schema_correcciones_evidencias_pendiente")));
        }
        if (!$this->usuarioTienePermisoDb($db, $idUsuario, "ventas.caja_evidencias.revisar")) {
            return $this->respuesta(false, "warning", "Usuario sin permiso para solicitar correccion de evidencia", array(
                "bloqueos" => array("permiso_correccion_requerido"),
                "permiso_requerido" => "ventas.caja_evidencias.revisar"
            ));
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT ev.*, mc.evidencia_estado, mc.estatus movimiento_estatus,
                    mc.tipo movimiento_tipo, mc.categoria movimiento_categoria, mc.referencia movimiento_referencia
                FROM erp_pos_movimientos_caja_evidencias ev
                INNER JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=ev.id_movimiento_caja
                WHERE ev.id_evidencia_caja=:evidencia
                FOR UPDATE");
            $stmt->execute(array(":evidencia" => $idEvidencia));
            $evidencia = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$evidencia) {
                throw new Exception("Evidencia no encontrada");
            }
            if ($this->valor($evidencia, "estatus", "") !== "aprobada" || $this->valor($evidencia, "evidencia_estado", "") !== "aprobada") {
                throw new Exception("Solo se puede solicitar correccion sobre evidencia aprobada");
            }
            if (in_array($this->valor($evidencia, "movimiento_estatus", ""), array("cancelado"), true)) {
                throw new Exception("El movimiento de caja no admite correccion por su estatus");
            }

            $stmt = $db->prepare("SELECT COUNT(*)
                FROM erp_pos_movimientos_caja_evidencias_correcciones
                WHERE id_evidencia_caja=:evidencia
                  AND estatus IN ('solicitada','en_revision')");
            $stmt->execute(array(":evidencia" => $idEvidencia));
            if (intval($stmt->fetchColumn()) > 0) {
                throw new Exception("Ya existe una correccion abierta para esta evidencia");
            }

            $folio = $this->generarFolioCorreccionEvidenciaCaja($db);
            $snapshot = json_encode(array(
                "evidencia" => $evidencia,
                "solicitud" => array(
                    "tipo_correccion" => $tipoCorreccion,
                    "motivo" => $motivo,
                    "solicitado_por" => $idUsuario
                )
            ), JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja_evidencias_correcciones
                (folio, id_evidencia_caja, id_movimiento_caja, estatus, tipo_correccion, motivo,
                 evidencia_estado_anterior, datos_snapshot, solicitado_por, fecha_actualizacion)
                VALUES (:folio, :evidencia, :movimiento, 'solicitada', :tipo, :motivo,
                 :estado_anterior, :snapshot, :usuario, NOW())");
            $stmt->execute(array(
                ":folio" => $folio,
                ":evidencia" => $idEvidencia,
                ":movimiento" => intval($this->valor($evidencia, "id_movimiento_caja", 0)),
                ":tipo" => $tipoCorreccion,
                ":motivo" => $motivo,
                ":estado_anterior" => $this->valor($evidencia, "estatus", "aprobada"),
                ":snapshot" => $snapshot,
                ":usuario" => $idUsuario
            ));
            $idCorreccion = intval($db->lastInsertId());

            $db->commit();
            return $this->respuesta(false, "success", "Correccion de evidencia solicitada", array(
                "id_correccion_evidencia_caja" => $idCorreccion,
                "folio" => $folio,
                "id_evidencia_caja" => $idEvidencia,
                "id_movimiento_caja" => intval($this->valor($evidencia, "id_movimiento_caja", 0)),
                "estatus" => "solicitada",
                "requiere_resolucion" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: registrar una evidencia correctiva ligada a un folio de correccion abierto.
     * Impacto: agrega evidencia nueva sin modificar la evidencia original ni cambiar el estado del movimiento de caja.
     * Contrato: escritura real; requiere permiso fino, correccion abierta y contenido de evidencia.
     */
    public function registrarEvidenciaCorrectivaCajaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $folio = trim((string) $this->valor($datos, "folio", ""));
        $idCorreccion = intval($this->valor($datos, "id_correccion_evidencia_caja", 0));
        $tipoEvidencia = trim((string) $this->valor($datos, "tipo_evidencia", "evidencia_correctiva"));
        $descripcion = trim((string) $this->valor($datos, "descripcion", ""));
        $referenciaExterna = trim((string) $this->valor($datos, "referencia_externa", ""));
        $archivoRuta = trim((string) $this->valor($datos, "archivo_ruta", ""));
        $archivoNombre = trim((string) $this->valor($datos, "archivo_nombre", ""));
        $archivoMime = trim((string) $this->valor($datos, "archivo_mime", ""));
        $archivoHash = trim((string) $this->valor($datos, "archivo_hash", ""));
        $archivoTamano = $this->valor($datos, "archivo_tamano", null);
        $archivoTamano = $archivoTamano === null || $archivoTamano === "" ? null : intval($archivoTamano);

        if ($idUsuario <= 0) {
            return $this->respuesta(false, "warning", "Usuario obligatorio para registrar evidencia correctiva", array("bloqueos" => array("usuario_obligatorio")));
        }
        if ($idCorreccion <= 0 && $folio === "") {
            return $this->respuesta(false, "warning", "Folio o id de correccion obligatorio", array("bloqueos" => array("correccion_obligatoria")));
        }
        if ($tipoEvidencia === "") {
            return $this->respuesta(false, "warning", "Tipo de evidencia obligatorio", array("bloqueos" => array("tipo_evidencia_obligatorio")));
        }
        if ($archivoRuta === "" && $referenciaExterna === "" && $descripcion === "") {
            return $this->respuesta(false, "warning", "Captura archivo, referencia externa o descripcion de evidencia correctiva", array("bloqueos" => array("evidencia_sin_contenido")));
        }
        if (!$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias") || !$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias_correcciones")) {
            return $this->respuesta(false, "warning", "Esquema de correcciones de evidencias pendiente", array("bloqueos" => array("schema_correcciones_evidencias_pendiente")));
        }
        if (!$this->usuarioTienePermisoDb($db, $idUsuario, "ventas.caja_evidencias.revisar")) {
            return $this->respuesta(false, "warning", "Usuario sin permiso para registrar evidencia correctiva", array(
                "bloqueos" => array("permiso_correccion_requerido"),
                "permiso_requerido" => "ventas.caja_evidencias.revisar"
            ));
        }

        try {
            $db->beginTransaction();
            $where = $idCorreccion > 0 ? "corr.id_correccion_evidencia_caja=:referencia" : "corr.folio=:referencia";
            $stmt = $db->prepare("SELECT corr.*, ev.estatus evidencia_estatus,
                    mc.evidencia_estado movimiento_evidencia_estado, mc.estatus movimiento_estatus
                FROM erp_pos_movimientos_caja_evidencias_correcciones corr
                INNER JOIN erp_pos_movimientos_caja_evidencias ev ON ev.id_evidencia_caja=corr.id_evidencia_caja
                INNER JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=corr.id_movimiento_caja
                WHERE {$where}
                FOR UPDATE");
            $stmt->execute(array(":referencia" => $idCorreccion > 0 ? $idCorreccion : $folio));
            $correccion = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$correccion) {
                throw new Exception("Correccion de evidencia no encontrada");
            }
            if (!in_array($this->valor($correccion, "estatus", ""), array("solicitada", "en_revision"), true)) {
                throw new Exception("La correccion no admite evidencia por su estatus");
            }
            if (intval($this->valor($correccion, "id_evidencia_caja_nueva", 0)) > 0) {
                throw new Exception("La correccion ya tiene evidencia correctiva registrada");
            }
            if ($this->valor($correccion, "evidencia_estatus", "") !== "aprobada" || $this->valor($correccion, "movimiento_evidencia_estado", "") !== "aprobada") {
                throw new Exception("La evidencia original debe permanecer aprobada para registrar correccion");
            }
            if (in_array($this->valor($correccion, "movimiento_estatus", ""), array("cancelado"), true)) {
                throw new Exception("El movimiento de caja no admite correccion por su estatus");
            }

            $snapshot = json_encode(array(
                "correccion" => $correccion,
                "captura" => array(
                    "tipo_evidencia" => $tipoEvidencia,
                    "descripcion" => $descripcion,
                    "referencia_externa" => $referenciaExterna,
                    "archivo_ruta" => $archivoRuta,
                    "archivo_nombre" => $archivoNombre,
                    "archivo_mime" => $archivoMime,
                    "archivo_tamano" => $archivoTamano,
                    "archivo_hash" => $archivoHash,
                    "creado_por" => $idUsuario
                )
            ), JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja_evidencias
                (id_movimiento_caja, tipo_evidencia, estatus, titulo, descripcion,
                 archivo_ruta, archivo_nombre, archivo_mime, archivo_tamano, archivo_hash,
                 referencia_externa, datos_snapshot, creado_por, fecha_actualizacion)
                VALUES (:movimiento, :tipo, 'recibida_correccion', :titulo, :descripcion,
                 :ruta, :nombre, :mime, :tamano, :hash, :referencia, :snapshot, :usuario, NOW())");
            $stmt->execute(array(
                ":movimiento" => intval($this->valor($correccion, "id_movimiento_caja", 0)),
                ":tipo" => $tipoEvidencia,
                ":titulo" => "Correccion " . $this->valor($correccion, "folio", ""),
                ":descripcion" => $descripcion !== "" ? $descripcion : null,
                ":ruta" => $archivoRuta !== "" ? $archivoRuta : null,
                ":nombre" => $archivoNombre !== "" ? $archivoNombre : null,
                ":mime" => $archivoMime !== "" ? $archivoMime : null,
                ":tamano" => $archivoTamano,
                ":hash" => $archivoHash !== "" ? $archivoHash : null,
                ":referencia" => $referenciaExterna !== "" ? $referenciaExterna : null,
                ":snapshot" => $snapshot,
                ":usuario" => $idUsuario
            ));
            $idEvidenciaNueva = intval($db->lastInsertId());

            $db->prepare("UPDATE erp_pos_movimientos_caja_evidencias_correcciones
                SET estatus='en_revision',
                    id_evidencia_caja_nueva=:nueva,
                    fecha_actualizacion=NOW()
                WHERE id_correccion_evidencia_caja=:correccion")
                ->execute(array(
                    ":nueva" => $idEvidenciaNueva,
                    ":correccion" => intval($this->valor($correccion, "id_correccion_evidencia_caja", 0))
                ));

            $db->commit();
            return $this->respuesta(false, "success", "Evidencia correctiva registrada", array(
                "id_correccion_evidencia_caja" => intval($this->valor($correccion, "id_correccion_evidencia_caja", 0)),
                "folio" => $this->valor($correccion, "folio", ""),
                "id_evidencia_caja_original" => intval($this->valor($correccion, "id_evidencia_caja", 0)),
                "id_evidencia_caja_nueva" => $idEvidenciaNueva,
                "id_movimiento_caja" => intval($this->valor($correccion, "id_movimiento_caja", 0)),
                "estatus" => "en_revision",
                "evidencia_original_intacta" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: resolver una correccion de evidencia de caja POS en revision.
     * Impacto: cierra el folio de correccion y marca la evidencia correctiva sin alterar caja, dinero ni inventario.
     * Contrato: escritura real; requiere permiso fino, correccion en revision y motivo de resolucion.
     */
    public function resolverCorreccionEvidenciaCajaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $folio = trim((string) $this->valor($datos, "folio", ""));
        $idCorreccion = intval($this->valor($datos, "id_correccion_evidencia_caja", 0));
        $decision = trim((string) $this->valor($datos, "decision", ""));
        $motivo = trim((string) $this->valor($datos, "motivo_resolucion", $this->valor($datos, "motivo", "")));

        if ($idUsuario <= 0) {
            return $this->respuesta(false, "warning", "Usuario obligatorio para resolver correccion", array("bloqueos" => array("usuario_obligatorio")));
        }
        if ($idCorreccion <= 0 && $folio === "") {
            return $this->respuesta(false, "warning", "Folio o id de correccion obligatorio", array("bloqueos" => array("correccion_obligatoria")));
        }
        if (!in_array($decision, array("aprobada", "rechazada"), true)) {
            return $this->respuesta(false, "warning", "Decision invalida para correccion", array("bloqueos" => array("decision_invalida")));
        }
        if ($motivo === "") {
            return $this->respuesta(false, "warning", "Motivo de resolucion obligatorio", array("bloqueos" => array("motivo_resolucion_obligatorio")));
        }
        if (!$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias") || !$this->tablaExiste($db, "erp_pos_movimientos_caja_evidencias_correcciones")) {
            return $this->respuesta(false, "warning", "Esquema de correcciones de evidencias pendiente", array("bloqueos" => array("schema_correcciones_evidencias_pendiente")));
        }
        if (!$this->usuarioTienePermisoDb($db, $idUsuario, "ventas.caja_evidencias.revisar")) {
            return $this->respuesta(false, "warning", "Usuario sin permiso para resolver correccion de evidencia", array(
                "bloqueos" => array("permiso_correccion_requerido"),
                "permiso_requerido" => "ventas.caja_evidencias.revisar"
            ));
        }

        try {
            $db->beginTransaction();
            $where = $idCorreccion > 0 ? "corr.id_correccion_evidencia_caja=:referencia" : "corr.folio=:referencia";
            $stmt = $db->prepare("SELECT corr.*, ev.estatus evidencia_original_estatus,
                    ev2.estatus evidencia_nueva_estatus,
                    mc.evidencia_estado movimiento_evidencia_estado, mc.estatus movimiento_estatus
                FROM erp_pos_movimientos_caja_evidencias_correcciones corr
                INNER JOIN erp_pos_movimientos_caja_evidencias ev ON ev.id_evidencia_caja=corr.id_evidencia_caja
                LEFT JOIN erp_pos_movimientos_caja_evidencias ev2 ON ev2.id_evidencia_caja=corr.id_evidencia_caja_nueva
                INNER JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=corr.id_movimiento_caja
                WHERE {$where}
                FOR UPDATE");
            $stmt->execute(array(":referencia" => $idCorreccion > 0 ? $idCorreccion : $folio));
            $correccion = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$correccion) {
                throw new Exception("Correccion de evidencia no encontrada");
            }
            if ($this->valor($correccion, "estatus", "") !== "en_revision") {
                throw new Exception("Solo se pueden resolver correcciones en revision");
            }
            if (intval($this->valor($correccion, "id_evidencia_caja_nueva", 0)) <= 0) {
                throw new Exception("La correccion no tiene evidencia correctiva registrada");
            }
            if ($this->valor($correccion, "evidencia_original_estatus", "") !== "aprobada" || $this->valor($correccion, "movimiento_evidencia_estado", "") !== "aprobada") {
                throw new Exception("La evidencia original y el movimiento deben permanecer aprobados");
            }
            if ($this->valor($correccion, "evidencia_nueva_estatus", "") !== "recibida_correccion") {
                throw new Exception("La evidencia correctiva no esta lista para resolucion");
            }

            $estatusCorreccion = $decision === "aprobada" ? "resuelta" : "rechazada";
            $estatusEvidenciaNueva = $decision === "aprobada" ? "aprobada_correccion" : "rechazada_correccion";

            $db->prepare("UPDATE erp_pos_movimientos_caja_evidencias_correcciones
                SET estatus=:estatus,
                    resuelto_por=:usuario,
                    fecha_resolucion=NOW(),
                    decision=:decision,
                    motivo_resolucion=:motivo,
                    fecha_actualizacion=NOW()
                WHERE id_correccion_evidencia_caja=:correccion")
                ->execute(array(
                    ":estatus" => $estatusCorreccion,
                    ":usuario" => $idUsuario,
                    ":decision" => $decision,
                    ":motivo" => $motivo,
                    ":correccion" => intval($this->valor($correccion, "id_correccion_evidencia_caja", 0))
                ));

            $db->prepare("UPDATE erp_pos_movimientos_caja_evidencias
                SET estatus=:estatus,
                    revisado_por=:usuario,
                    fecha_revision=NOW(),
                    motivo_rechazo=:motivo_rechazo,
                    fecha_actualizacion=NOW()
                WHERE id_evidencia_caja=:evidencia")
                ->execute(array(
                    ":estatus" => $estatusEvidenciaNueva,
                    ":usuario" => $idUsuario,
                    ":motivo_rechazo" => $decision === "rechazada" ? $motivo : null,
                    ":evidencia" => intval($this->valor($correccion, "id_evidencia_caja_nueva", 0))
                ));

            $db->commit();
            return $this->respuesta(false, "success", "Correccion de evidencia resuelta", array(
                "id_correccion_evidencia_caja" => intval($this->valor($correccion, "id_correccion_evidencia_caja", 0)),
                "folio" => $this->valor($correccion, "folio", ""),
                "decision" => $decision,
                "estatus" => $estatusCorreccion,
                "id_evidencia_caja_original" => intval($this->valor($correccion, "id_evidencia_caja", 0)),
                "id_evidencia_caja_nueva" => intval($this->valor($correccion, "id_evidencia_caja_nueva", 0)),
                "estatus_evidencia_nueva" => $estatusEvidenciaNueva,
                "movimiento_caja_intacto" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: construir una vista previa de ticket POS a partir de la prevalidacion.
     * Impacto: permite revisar contenido de ticket antes de activar folios y cobro real.
     * Contrato: dry-run; el folio mostrado es temporal y no debe guardarse ni entregarse como venta.
     */
    public function ticketPreviewDryRun($datos = array()) {
        $prevalidacion = $this->prevalidarCarritoPos($datos);
        $depurar = isset($prevalidacion["depurar"]) ? $prevalidacion["depurar"] : array();
        $totales = isset($depurar["totales"]) ? $depurar["totales"] : array();
        $lineas = array();
        $lineas[] = "ARTIANI ERP - TICKET PREVIEW";
        $lineas[] = "Folio: PREVIEW-" . date("Ymd-His");
        $lineas[] = "Almacen: " . $this->valor($datos, "id_almacen", "-") . " Caja: " . $this->valor($datos, "id_caja", "-") . " Turno: " . $this->valor($datos, "id_turno_caja", "-");
        $lineas[] = "--------------------------------";
        foreach ($this->valor($depurar, "partidas", array()) as $partida) {
            $lineas[] = $partida["sku"] . " x " . $partida["cantidad"] . " = $" . number_format(floatval($partida["subtotal"]), 2, ".", "");
            if (!empty($partida["bloqueos"])) {
                $lineas[] = "  BLOQUEO: " . implode("; ", $partida["bloqueos"]);
            }
        }
        $lineas[] = "--------------------------------";
        $lineas[] = "Subtotal: $" . number_format(floatval($this->valor($totales, "subtotal", 0)), 2, ".", "");
        $lineas[] = "Total: $" . number_format(floatval($this->valor($totales, "total_estimado", 0)), 2, ".", "");
        $lineas[] = "Pagado: $" . number_format(floatval($this->valor($totales, "pagado_total", 0)), 2, ".", "");
        $lineas[] = "Saldo: $" . number_format(floatval($this->valor($totales, "saldo_total", 0)), 2, ".", "");
        $lineas[] = "Cambio: $" . number_format(floatval($this->valor($totales, "cambio", 0)), 2, ".", "");
        if (!empty($depurar["bloqueos"])) {
            $lineas[] = "NO ES VENTA CONFIRMADA";
        }
        return $this->respuesta(false, empty($depurar["bloqueos"]) ? "success" : "warning", "Ticket preview generado", array(
            "dry_run" => true,
            "prevalidacion" => $prevalidacion,
            "ticket_texto" => implode("\n", $lineas),
            "bloqueos" => $this->valor($depurar, "bloqueos", array())
        ));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: construir ticket formal read-only desde una venta ERP confirmada.
     * Impacto: permite reimprimir/validar ticket con caja, turno, pagos, precios, lista e inventario sin escribir BD.
     * Contrato: no crea folios, no mueve inventario y no recalcula garantias historicas; usa snapshot si existe.
     */
    public function ticketVentaFormalReadOnly($datos = array()) {
        try {
            $db = $this->getConexion();
            $idVenta = intval($this->valor($datos, "id_venta", 0));
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $folio = trim((string) $this->valor($datos, "folio", ""));
            if ($idVenta <= 0 && $folio === "") {
                return $this->respuesta(false, "warning", "Indica id_venta o folio para generar ticket", array(
                    "bloqueos" => array("referencia_no_indicada")
                ));
            }
            if (!$this->tablasVentasDisponibles($db)) {
                return $this->respuesta(false, "warning", "Esquema Ventas/POS pendiente", array(
                    "bloqueos" => array("schema_ventas_pendiente")
                ));
            }

            $where = $idVenta > 0 ? "v.id_venta=:referencia" : "v.folio=:referencia";
            $stmt = $db->prepare("SELECT v.*, a.almacen, a.nombre_comercial, a.codigo_almacen,
                    c.codigo caja_codigo, c.nombre caja_nombre,
                    t.folio turno_folio, t.fecha_apertura, t.fecha_cierre
                FROM erp_ventas v
                LEFT JOIN erp_almacenes a ON a.id_almacen=v.id_almacen
                LEFT JOIN erp_pos_cajas c ON c.id_caja=v.id_caja
                LEFT JOIN erp_pos_turnos t ON t.id_turno_caja=v.id_turno_caja
                WHERE {$where}
                LIMIT 1");
            $stmt->execute(array(":referencia" => $idVenta > 0 ? $idVenta : $folio));
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$venta) {
                return $this->respuesta(false, "warning", "Venta ERP no encontrada", array(
                    "bloqueos" => array("venta_no_encontrada")
                ));
            }

            $stmt = $db->prepare("SELECT d.*, g.id_venta_detalle_garantia, g.tipo_garantia_snapshot,
                    g.nombre_politica_snapshot, g.duracion_valor_snapshot, g.unidad_duracion_snapshot,
                    g.resumen_ticket, g.fecha_inicio, g.fecha_vencimiento, g.estatus garantia_estatus
                FROM erp_ventas_detalle d
                LEFT JOIN erp_ventas_detalle_garantias g ON g.id_venta_detalle=d.id_venta_detalle
                WHERE d.id_venta=:venta
                ORDER BY d.renglon ASC, d.id_venta_detalle ASC");
            $stmt->execute(array(":venta" => intval($venta["id_venta"])));
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT p.*, mc.tipo movimiento_tipo, mc.categoria movimiento_categoria, mc.motivo movimiento_motivo
                FROM erp_ventas_pagos p
                LEFT JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=p.id_movimiento_caja
                WHERE p.id_venta=:venta AND p.estatus='registrado'
                ORDER BY p.id_venta_pago ASC");
            $stmt->execute(array(":venta" => intval($venta["id_venta"])));
            $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT vi.*, im.codigo_existencia, im.referencia movimiento_referencia,
                    im.existencia_anterior, im.existencia_nueva
                FROM erp_ventas_detalle_inventario vi
                LEFT JOIN erp_inventario_movimientos im ON im.id_movimiento_inventario=vi.id_movimiento_inventario
                WHERE vi.id_venta=:venta
                ORDER BY vi.id_venta_detalle_inventario ASC");
            $stmt->execute(array(":venta" => intval($venta["id_venta"])));
            $trazabilidad = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $ticket = $this->formatearTicketVenta($venta, $detalles, $pagos, $trazabilidad);
            return $this->respuesta(false, "success", "Ticket formal generado", array(
                "read_only" => true,
                "venta" => $venta,
                "detalles" => $detalles,
                "pagos" => $pagos,
                "trazabilidad_inventario" => $trazabilidad,
                "ticket_texto" => $ticket["texto"],
                "ticket_lineas" => $ticket["lineas"],
                "hallazgos" => $ticket["hallazgos"],
                "contrato" => array(
                    "no_es_fiscal" => true,
                    "no_escribe_bd" => true,
                    "usa_snapshot_precio" => true,
                    "usa_snapshot_garantia_si_existe" => true,
                    "no_recalcula_garantia_historica" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: construir ticket formal read-only desde una devolucion/cancelacion POS aplicada.
     * Impacto: permite imprimir/validar devoluciones con venta original, decision financiera, inventario y trazabilidad sin escribir BD.
     * Contrato: no reembolsa, no mueve inventario y no recalcula importes; usa registros historicos de la reversa.
     */
    public function ticketDevolucionFormalReadOnly($datos = array()) {
        try {
            $db = $this->getConexion();
            $idDevolucion = intval($this->valor($datos, "id_devolucion", 0));
            $folio = trim((string) $this->valor($datos, "folio_devolucion", $this->valor($datos, "folio", "")));
            if ($idDevolucion <= 0 && $folio === "") {
                return $this->respuesta(false, "warning", "Indica id_devolucion o folio_devolucion para generar ticket", array(
                    "bloqueos" => array("referencia_no_indicada")
                ));
            }
            if (!$this->tablaExiste($db, "erp_ventas_devoluciones") || !$this->tablaExiste($db, "erp_ventas_devoluciones_detalle")) {
                return $this->respuesta(false, "warning", "Esquema de reversas POS pendiente", array(
                    "bloqueos" => array("schema_reversas_pos_pendiente")
                ));
            }

            $where = $idDevolucion > 0 ? "d.id_devolucion=:referencia" : "d.folio=:referencia";
            $stmt = $db->prepare("SELECT d.*, v.folio folio_venta, v.fecha_venta, v.cliente_nombre_publico,
                    v.total total_venta, v.pagado_total pagado_venta, v.estatus estatus_venta,
                    a.almacen, a.nombre_comercial, a.codigo_almacen,
                    c.codigo caja_codigo, c.nombre caja_nombre,
                    t.folio turno_folio, t.fecha_apertura, t.fecha_cierre
                FROM erp_ventas_devoluciones d
                INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
                LEFT JOIN erp_almacenes a ON a.id_almacen=COALESCE(d.id_almacen, v.id_almacen)
                LEFT JOIN erp_pos_cajas c ON c.id_caja=COALESCE(d.id_caja, v.id_caja)
                LEFT JOIN erp_pos_turnos t ON t.id_turno_caja=COALESCE(d.id_turno_caja, v.id_turno_caja)
                WHERE {$where}
                LIMIT 1");
            $stmt->execute(array(":referencia" => $idDevolucion > 0 ? $idDevolucion : $folio));
            $devolucion = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$devolucion) {
                return $this->respuesta(false, "warning", "Devolucion POS no encontrada", array(
                    "bloqueos" => array("devolucion_no_encontrada")
                ));
            }

            $stmt = $db->prepare("SELECT dd.*, vd.sku, vd.descripcion, vd.unidad_venta,
                    vd.precio_unitario, vd.total total_original, vd.cantidad_venta cantidad_original,
                    im_origen.referencia referencia_movimiento_origen,
                    im_dev.referencia referencia_movimiento_devolucion
                FROM erp_ventas_devoluciones_detalle dd
                LEFT JOIN erp_ventas_detalle vd ON vd.id_venta_detalle=dd.id_venta_detalle
                LEFT JOIN erp_inventario_movimientos im_origen ON im_origen.id_movimiento_inventario=dd.id_movimiento_inventario_origen
                LEFT JOIN erp_inventario_movimientos im_dev ON im_dev.id_movimiento_inventario=dd.id_movimiento_inventario_devolucion
                WHERE dd.id_devolucion=:devolucion
                ORDER BY dd.id_devolucion_detalle ASC");
            $stmt->execute(array(":devolucion" => intval($devolucion["id_devolucion"])));
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT p.*, mc.tipo movimiento_tipo, mc.categoria movimiento_categoria, mc.motivo movimiento_motivo
                FROM erp_ventas_pagos p
                LEFT JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=p.id_movimiento_caja
                WHERE p.id_venta=:venta AND p.referencia=:referencia AND p.estatus='registrado'
                ORDER BY p.id_venta_pago ASC");
            $stmt->execute(array(
                ":venta" => intval($devolucion["id_venta"]),
                ":referencia" => $devolucion["folio"]
            ));
            $pagosReembolso = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $movimientoCaja = null;
            if (intval($this->valor($devolucion, "id_movimiento_caja", 0)) > 0) {
                $stmt = $db->prepare("SELECT * FROM erp_pos_movimientos_caja WHERE id_movimiento_caja=:movimiento LIMIT 1");
                $stmt->execute(array(":movimiento" => intval($devolucion["id_movimiento_caja"])));
                $movimientoCaja = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            $componentesFinancieros = array();
            if ($this->tablaExiste($db, "erp_ventas_devoluciones_finanzas")) {
                $stmt = $db->prepare("SELECT *
                    FROM erp_ventas_devoluciones_finanzas
                    WHERE id_devolucion=:devolucion
                    ORDER BY id_devolucion_finanza ASC");
                $stmt->execute(array(":devolucion" => intval($devolucion["id_devolucion"])));
                $componentesFinancieros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $ticket = $this->formatearTicketDevolucion($devolucion, $detalles, $pagosReembolso, $movimientoCaja, $componentesFinancieros);
            return $this->respuesta(false, "success", "Ticket de devolucion generado", array(
                "read_only" => true,
                "devolucion" => $devolucion,
                "detalles" => $detalles,
                "pagos_reembolso" => $pagosReembolso,
                "movimiento_caja" => $movimientoCaja,
                "componentes_financieros" => $componentesFinancieros,
                "ticket_texto" => $ticket["texto"],
                "ticket_lineas" => $ticket["lineas"],
                "hallazgos" => $ticket["hallazgos"],
                "contrato" => array(
                    "no_es_fiscal" => true,
                    "no_escribe_bd" => true,
                    "usa_importe_historico_devolucion" => true,
                    "no_mueve_inventario" => true,
                    "no_reembolsa_caja" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: listar devoluciones POS con decision fisica pendiente de inventario/almacen.
     * Impacto: permite separar la reversa comercial de la inspeccion fisica posterior sin mover stock.
     * Contrato: read-only; no crea kardex, no cambia devoluciones y no reintegra mercancia.
     */
    public function devolucionesInventarioPendientesReadOnly($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $decision = trim((string) $this->valor($datos, "decision_inventario", "pendientes"));
            $inspeccionEstado = trim((string) $this->valor($datos, "inspeccion_estado", ""));
            $folio = trim((string) $this->valor($datos, "folio", ""));
            $limite = intval($this->valor($datos, "limite", 50));
            $limite = $limite > 0 && $limite <= 200 ? $limite : 50;
            $estadosInspeccionPermitidos = array("pendiente", "cuarentena_confirmada", "resuelta", "todos");
            if ($inspeccionEstado !== "" && !in_array($inspeccionEstado, $estadosInspeccionPermitidos, true)) {
                $inspeccionEstado = "";
            }

            if (!$this->tablaExiste($db, "erp_ventas_devoluciones") || !$this->tablaExiste($db, "erp_ventas_devoluciones_detalle")) {
                return $this->respuesta(false, "warning", "Esquema de devoluciones pendiente", array(
                    "read_only" => true,
                    "bloqueos" => array("schema_devoluciones_pendiente")
                ));
            }
            $tieneInspecciones = $this->tablaExiste($db, "erp_ventas_devoluciones_inspecciones");
            $tieneEstadoInspeccion = $this->columnaExiste($db, "erp_ventas_devoluciones_detalle", "inspeccion_estado");

            $where = array("d.estatus NOT IN ('cancelada','rechazada')");
            $params = array();
            if ($idAlmacen > 0) {
                $where[] = "d.id_almacen=:almacen";
                $params[":almacen"] = $idAlmacen;
            }
            if ($folio !== "") {
                $where[] = "(d.folio=:folio OR v.folio=:folio)";
                $params[":folio"] = $folio;
            }
            if ($decision === "pendientes" || $decision === "") {
                $where[] = "dd.decision_inventario IN ('cuarentena','merma','reintegrar')";
                $where[] = "dd.id_movimiento_inventario_devolucion IS NULL";
                if ($this->columnaExiste($db, "erp_ventas_devoluciones_detalle", "inspeccion_estado")) {
                    $where[] = "dd.inspeccion_estado='pendiente'";
                }
            } elseif ($decision !== "todos") {
                $where[] = "dd.decision_inventario=:decision";
                $params[":decision"] = $decision;
            }
            if ($inspeccionEstado !== "" && $inspeccionEstado !== "todos" && $this->columnaExiste($db, "erp_ventas_devoluciones_detalle", "inspeccion_estado")) {
                $where[] = "dd.inspeccion_estado=:inspeccion_estado";
                $params[":inspeccion_estado"] = $inspeccionEstado;
            }

            $selectInspeccion = $tieneEstadoInspeccion ? "dd.inspeccion_estado, dd.id_inspeccion_fisica,
                    dd.fecha_inspeccion_fisica," : "'pendiente' inspeccion_estado, NULL id_inspeccion_fisica,
                    NULL fecha_inspeccion_fisica,";
            $selectInspeccion .= $tieneInspecciones ? " insp.folio folio_inspeccion, insp.decision_fisica, insp.estatus inspeccion_estatus," : " NULL folio_inspeccion, NULL decision_fisica, NULL inspeccion_estatus,";
            $joinInspeccion = $tieneInspecciones && $tieneEstadoInspeccion ? " LEFT JOIN erp_ventas_devoluciones_inspecciones insp ON insp.id_inspeccion_fisica=dd.id_inspeccion_fisica" : "";

            $stmt = $db->prepare("SELECT d.id_devolucion, d.folio folio_devolucion, d.tipo, d.estatus,
                    d.decision_financiera, d.monto_reembolso, d.monto_saldo_favor, d.fecha_aplicacion,
                    d.id_almacen, d.id_caja, d.id_turno_caja, d.id_movimiento_caja,
                    v.folio folio_venta, v.cliente_nombre_publico, v.id_cliente_crm,
                    dd.id_devolucion_detalle, dd.id_venta_detalle, dd.id_existencia_inventario,
                    dd.id_inventario_unidad, dd.id_movimiento_inventario_devolucion,
                    dd.id_almacen_destino, dd.cantidad_base, dd.importe_reembolso,
                    dd.decision_inventario, " . $selectInspeccion . " dd.estatus detalle_estatus,
                    vd.sku, vd.descripcion, vd.unidad_base, vd.modo_salida,
                    a.nombre_comercial almacen_nombre, c.codigo caja_codigo
                FROM erp_ventas_devoluciones_detalle dd
                INNER JOIN erp_ventas_devoluciones d ON d.id_devolucion=dd.id_devolucion
                INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
                LEFT JOIN erp_ventas_detalle vd ON vd.id_venta_detalle=dd.id_venta_detalle
                " . $joinInspeccion . "
                LEFT JOIN erp_almacenes a ON a.id_almacen=d.id_almacen
                LEFT JOIN erp_pos_cajas c ON c.id_caja=d.id_caja
                WHERE " . implode(" AND ", $where) . "
                ORDER BY d.fecha_aplicacion DESC, d.id_devolucion DESC, dd.id_devolucion_detalle ASC
                LIMIT " . intval($limite));
            $stmt->execute($params);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resumen = array(
                "total_registros" => count($filas),
                "cantidad_total" => 0,
                "importe_total" => 0,
                "por_decision" => array()
            );
            foreach ($filas as $fila) {
                $cantidad = floatval($this->valor($fila, "cantidad_base", 0));
                $importe = floatval($this->valor($fila, "importe_reembolso", 0));
                $dec = (string) $this->valor($fila, "decision_inventario", "sin_decision");
                if (!isset($resumen["por_decision"][$dec])) {
                    $resumen["por_decision"][$dec] = array("partidas" => 0, "cantidad" => 0, "importe" => 0);
                }
                $resumen["total_registros"] = count($filas);
                $resumen["cantidad_total"] += $cantidad;
                $resumen["importe_total"] += $importe;
                $resumen["por_decision"][$dec]["partidas"]++;
                $resumen["por_decision"][$dec]["cantidad"] += $cantidad;
                $resumen["por_decision"][$dec]["importe"] += $importe;
            }
            $resumen["cantidad_total"] = $this->redondearPosReal($resumen["cantidad_total"]);
            $resumen["importe_total"] = $this->redondearPosReal($resumen["importe_total"]);
            foreach ($resumen["por_decision"] as $dec => $datosDecision) {
                $resumen["por_decision"][$dec]["cantidad"] = $this->redondearPosReal($datosDecision["cantidad"]);
                $resumen["por_decision"][$dec]["importe"] = $this->redondearPosReal($datosDecision["importe"]);
            }

            return $this->respuesta(false, "success", "Devoluciones con decision fisica consultadas", array(
                "read_only" => true,
                "filtros" => array(
                    "id_almacen" => $idAlmacen,
                    "decision_inventario" => $decision,
                    "inspeccion_estado" => $inspeccionEstado,
                    "folio" => $folio,
                    "limite" => $limite
                ),
                "resumen" => $resumen,
                "pendientes" => $filas,
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_crea_kardex" => true,
                    "no_reintegra_inventario" => true,
                    "requiere_flujo_almacen_inventario_para_cierre" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: prevalidar una decision fisica sobre una partida devuelta sin escribir BD.
     * Impacto: prepara inspeccion de cuarentena, reintegro, merma o garantia/proveedor con reglas visibles.
     * Contrato: dry-run; no registra inspeccion, no crea kardex, no actualiza devolucion ni inventario.
     */
    public function inspeccionFisicaDevolucionDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idDetalle = intval($this->valor($datos, "id_devolucion_detalle", 0));
            $decision = trim((string) $this->valor($datos, "decision_fisica", "mantener_cuarentena"));
            $condicion = trim((string) $this->valor($datos, "condicion_producto", ""));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            $diagnostico = trim((string) $this->valor($datos, "diagnostico", ""));
            $permitidas = array("mantener_cuarentena", "reintegrar_disponible", "merma", "garantia_proveedor", "reparacion", "rechazo_inspeccion");
            $bloqueos = array();
            $avisos = array();

            if ($idUsuario <= 0) {
                $bloqueos[] = "Usuario inspector obligatorio";
            }
            if ($idDetalle <= 0) {
                $bloqueos[] = "Partida de devolucion obligatoria";
            }
            if (!in_array($decision, $permitidas, true)) {
                $bloqueos[] = "Decision fisica invalida";
            }
            if ($motivo === "") {
                $bloqueos[] = "Motivo de inspeccion obligatorio";
            }
            if (!$this->tablaExiste($db, "erp_ventas_devoluciones_inspecciones") || !$this->columnaExiste($db, "erp_ventas_devoluciones_detalle", "inspeccion_estado")) {
                $bloqueos[] = "Esquema de inspeccion fisica pendiente";
            }

            $partida = null;
            if (empty($bloqueos)) {
                $stmt = $db->prepare("SELECT dd.*, d.folio folio_devolucion, d.estatus devolucion_estatus,
                        d.id_almacen, d.id_caja, d.id_turno_caja, d.id_venta,
                        v.folio folio_venta, v.cliente_nombre_publico,
                        vd.sku, vd.descripcion, vd.controla_inventario, vd.modo_salida, vd.unidad_base
                    FROM erp_ventas_devoluciones_detalle dd
                    INNER JOIN erp_ventas_devoluciones d ON d.id_devolucion=dd.id_devolucion
                    INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
                    LEFT JOIN erp_ventas_detalle vd ON vd.id_venta_detalle=dd.id_venta_detalle
                    WHERE dd.id_devolucion_detalle=:detalle
                    LIMIT 1");
                $stmt->execute(array(":detalle" => $idDetalle));
                $partida = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$partida) {
                    $bloqueos[] = "Partida de devolucion no encontrada";
                }
            }

            if ($partida) {
                if (!in_array($this->valor($partida, "devolucion_estatus", ""), array("aplicada"), true)) {
                    $bloqueos[] = "La devolucion no esta aplicada";
                }
                if (!in_array($this->valor($partida, "estatus", ""), array("aplicada"), true)) {
                    $bloqueos[] = "La partida no esta aplicada";
                }
                if ($this->valor($partida, "inspeccion_estado", "pendiente") !== "pendiente") {
                    $bloqueos[] = "La partida ya tiene inspeccion en estado " . $this->valor($partida, "inspeccion_estado", "");
                }
                if (intval($this->valor($partida, "id_movimiento_inventario_devolucion", 0)) > 0) {
                    $bloqueos[] = "La partida ya tiene movimiento de inventario de devolucion";
                }
                if ($decision === "reintegrar_disponible") {
                    if (intval($this->valor($partida, "controla_inventario", 0)) !== 1) {
                        $bloqueos[] = "La partida no controla inventario";
                    }
                    if (intval($this->valor($partida, "id_existencia_inventario", 0)) <= 0) {
                        $bloqueos[] = "No hay existencia origen para reintegrar";
                    }
                    if (intval($this->valor($partida, "id_inventario_unidad", 0)) > 0) {
                        $avisos[] = "Unidad fisica: el reintegro debe validar estado/cierre de unidad antes de disponible";
                    }
                }
                if ($decision === "merma" && $diagnostico === "") {
                    $avisos[] = "Merma deberia incluir diagnostico o evidencia fisica";
                }
                if (in_array($decision, array("garantia_proveedor", "reparacion"), true)) {
                    $avisos[] = "Esta decision debera ligarse a reclamo de garantia/proveedor antes de cierre completo";
                }
                if ($this->valor($partida, "decision_inventario", "") !== "cuarentena") {
                    $avisos[] = "La decision original no fue cuarentena; revisar antes de inspeccionar";
                }
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de inspeccion fisica valido" : "Dry-run de inspeccion fisica bloqueado", array(
                "dry_run" => true,
                "id_usuario" => $idUsuario,
                "id_devolucion_detalle" => $idDetalle,
                "decision_fisica" => $decision,
                "condicion_producto" => $condicion,
                "motivo" => $motivo,
                "diagnostico" => $diagnostico,
                "bloqueos" => $bloqueos,
                "avisos" => $avisos,
                "partida" => $partida,
                "contrato_ejecucion_futura" => array(
                    "registrar_inspeccion" => true,
                    "actualizar_devolucion_detalle" => true,
                    "crear_kardex_si_reintegra_o_merma" => in_array($decision, array("reintegrar_disponible", "merma"), true),
                    "ligar_garantia_si_aplica" => in_array($decision, array("garantia_proveedor", "reparacion"), true),
                    "no_escribe_bd_en_dry_run" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: registrar inspeccion fisica documental de una partida devuelta sin mover inventario.
     * Impacto: cierra la primera fase de cuarentena dejando folio auditable ligado a devolucion.
     * Contrato: escritura real controlada; por ahora solo permite `mantener_cuarentena` y no crea kardex.
     */
    public function registrarInspeccionFisicaDevolucionPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $idDetalle = intval($this->valor($datos, "id_devolucion_detalle", 0));
        $decision = trim((string) $this->valor($datos, "decision_fisica", "mantener_cuarentena"));
        $condicion = trim((string) $this->valor($datos, "condicion_producto", ""));
        $motivo = trim((string) $this->valor($datos, "motivo", ""));
        $diagnostico = trim((string) $this->valor($datos, "diagnostico", ""));

        if ($decision !== "mantener_cuarentena") {
            return $this->respuesta(false, "warning", "La ejecucion real inicial solo permite mantener cuarentena; reintegro/merma/garantia requieren autorizacion posterior", array(
                "bloqueos" => array("decision_fisica_no_autorizada_para_esta_fase"),
                "decision_fisica" => $decision
            ));
        }

        $dryRun = $this->inspeccionFisicaDevolucionDryRun($datos);
        $bloqueos = $this->valorRutaPosReal($dryRun, array("depurar", "bloqueos"), array());
        if (!empty($bloqueos) || (isset($dryRun["tipo"]) && $dryRun["tipo"] !== "success")) {
            return $this->respuesta(false, "warning", "Inspeccion fisica bloqueada por dry-run", array(
                "bloqueos" => $bloqueos,
                "dry_run" => $dryRun
            ));
        }
        $partidaDry = $this->valorRutaPosReal($dryRun, array("depurar", "partida"), array());
        if (empty($partidaDry)) {
            return $this->respuesta(false, "warning", "Inspeccion sin partida valida", array("bloqueos" => array("partida_invalida")));
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT dd.*, d.folio folio_devolucion, d.estatus devolucion_estatus,
                    d.id_almacen, d.id_venta, v.folio folio_venta
                FROM erp_ventas_devoluciones_detalle dd
                INNER JOIN erp_ventas_devoluciones d ON d.id_devolucion=dd.id_devolucion
                INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
                WHERE dd.id_devolucion_detalle=:detalle
                FOR UPDATE");
            $stmt->execute(array(":detalle" => $idDetalle));
            $partida = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$partida) {
                throw new Exception("Partida de devolucion no encontrada");
            }
            if ($this->valor($partida, "inspeccion_estado", "pendiente") !== "pendiente") {
                throw new Exception("La partida ya tiene inspeccion en estado " . $this->valor($partida, "inspeccion_estado", ""));
            }
            if (intval($this->valor($partida, "id_movimiento_inventario_devolucion", 0)) > 0) {
                throw new Exception("La partida ya tiene movimiento de inventario");
            }

            $folio = $this->generarFolioInspeccionFisicaDevolucion($db);
            $snapshot = json_encode(array(
                "dry_run" => $this->valor($dryRun, "depurar", array()),
                "partida_bloqueada" => $partida,
                "captura" => array(
                    "decision_fisica" => $decision,
                    "condicion_producto" => $condicion,
                    "motivo" => $motivo,
                    "diagnostico" => $diagnostico,
                    "id_usuario" => $idUsuario
                )
            ), JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("INSERT INTO erp_ventas_devoluciones_inspecciones
                (folio, id_devolucion, id_devolucion_detalle, id_venta, id_venta_detalle,
                 id_almacen_origen, id_almacen_destino, id_existencia_inventario, id_inventario_unidad,
                 id_movimiento_inventario, id_reclamo_garantia, decision_fisica, estatus,
                 condicion_producto, cantidad_base, motivo, diagnostico, datos_snapshot,
                 inspeccionado_por, autorizado_por, fecha_inspeccion, fecha_autorizacion, fecha_actualizacion)
                VALUES (:folio, :devolucion, :detalle_dev, :venta, :detalle_venta,
                 :almacen_origen, NULL, :existencia, :unidad,
                 NULL, NULL, :decision, 'registrada',
                 :condicion, :cantidad, :motivo, :diagnostico, :snapshot,
                 :usuario, :usuario, NOW(), NOW(), NOW())");
            $stmt->execute(array(
                ":folio" => $folio,
                ":devolucion" => intval($this->valor($partida, "id_devolucion", 0)),
                ":detalle_dev" => $idDetalle,
                ":venta" => intval($this->valor($partida, "id_venta", 0)),
                ":detalle_venta" => intval($this->valor($partida, "id_venta_detalle", 0)),
                ":almacen_origen" => intval($this->valor($partida, "id_almacen", 0)) ?: null,
                ":existencia" => intval($this->valor($partida, "id_existencia_inventario", 0)) ?: null,
                ":unidad" => intval($this->valor($partida, "id_inventario_unidad", 0)) ?: null,
                ":decision" => $decision,
                ":condicion" => $condicion !== "" ? $condicion : null,
                ":cantidad" => $this->redondearPosReal($this->valor($partida, "cantidad_base", 0)),
                ":motivo" => $motivo,
                ":diagnostico" => $diagnostico !== "" ? $diagnostico : null,
                ":snapshot" => $snapshot,
                ":usuario" => $idUsuario
            ));
            $idInspeccion = intval($db->lastInsertId());

            $db->prepare("UPDATE erp_ventas_devoluciones_detalle
                SET inspeccion_estado='cuarentena_confirmada',
                    id_inspeccion_fisica=:inspeccion,
                    fecha_inspeccion_fisica=NOW()
                WHERE id_devolucion_detalle=:detalle")
                ->execute(array(
                    ":inspeccion" => $idInspeccion,
                    ":detalle" => $idDetalle
                ));

            $db->commit();

            return $this->respuesta(false, "success", "Inspeccion fisica registrada", array(
                "id_inspeccion_fisica" => $idInspeccion,
                "folio" => $folio,
                "id_devolucion_detalle" => $idDetalle,
                "id_devolucion" => intval($this->valor($partida, "id_devolucion", 0)),
                "folio_devolucion" => $this->valor($partida, "folio_devolucion", ""),
                "decision_fisica" => $decision,
                "inspeccion_estado" => "cuarentena_confirmada",
                "no_crea_kardex" => true,
                "no_mueve_inventario" => true,
                "no_crea_garantia" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-09.
     * Proposito: prevalidar el destino final de una partida devuelta que ya esta en cuarentena confirmada.
     * Impacto: permite decidir si procede reintegro, merma, garantia/proveedor o reparacion antes de ejecutar DDL/kardex.
     * Contrato: dry-run/read-only; no actualiza devolucion, no crea kardex, no cambia inventario ni garantia.
     */
    public function destinoFinalCuarentenaDevolucionDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idDetalle = intval($this->valor($datos, "id_devolucion_detalle", 0));
            $destino = trim((string) $this->valor($datos, "destino_final", "reintegrar_disponible"));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            $permitidos = array("reintegrar_disponible", "merma", "garantia_proveedor", "reparacion", "mantener_cuarentena");
            $bloqueos = array();
            $avisos = array();
            $plan = array();

            if ($idUsuario <= 0) {
                $bloqueos[] = "Usuario obligatorio para prevalidar destino final";
            }
            if ($idDetalle <= 0) {
                $bloqueos[] = "Partida de devolucion obligatoria";
            }
            if (!in_array($destino, $permitidos, true)) {
                $bloqueos[] = "Destino final invalido";
            }
            if ($motivo === "") {
                $avisos[] = "El destino final real debera capturar motivo documentado";
            }
            if (!$this->tablaExiste($db, "erp_ventas_devoluciones") || !$this->tablaExiste($db, "erp_ventas_devoluciones_detalle")) {
                $bloqueos[] = "Esquema de devoluciones pendiente";
            }
            if (!$this->tablaExiste($db, "erp_ventas_devoluciones_inspecciones")) {
                $bloqueos[] = "Esquema de inspeccion fisica pendiente";
            }

            $partida = null;
            $existencia = null;
            $unidad = null;
            if (empty($bloqueos)) {
                $stmt = $db->prepare("SELECT dd.*, d.folio folio_devolucion, d.estatus devolucion_estatus,
                        d.id_almacen, d.id_caja, d.id_turno_caja, d.id_venta,
                        v.folio folio_venta, v.cliente_nombre_publico, v.id_cliente_crm,
                        vd.sku, vd.descripcion, vd.controla_inventario, vd.modo_salida, vd.unidad_base,
                        insp.folio folio_inspeccion, insp.decision_fisica, insp.estatus inspeccion_estatus,
                        insp.condicion_producto, insp.diagnostico
                    FROM erp_ventas_devoluciones_detalle dd
                    INNER JOIN erp_ventas_devoluciones d ON d.id_devolucion=dd.id_devolucion
                    INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
                    LEFT JOIN erp_ventas_detalle vd ON vd.id_venta_detalle=dd.id_venta_detalle
                    LEFT JOIN erp_ventas_devoluciones_inspecciones insp ON insp.id_inspeccion_fisica=dd.id_inspeccion_fisica
                    WHERE dd.id_devolucion_detalle=:detalle
                    LIMIT 1");
                $stmt->execute(array(":detalle" => $idDetalle));
                $partida = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$partida) {
                    $bloqueos[] = "Partida de devolucion no encontrada";
                }
            }

            if ($partida) {
                $cantidad = $this->redondearPosReal($this->valor($partida, "cantidad_base", 0));
                $idExistencia = intval($this->valor($partida, "id_existencia_inventario", 0));
                $idUnidad = intval($this->valor($partida, "id_inventario_unidad", 0));

                if ($this->valor($partida, "devolucion_estatus", "") !== "aplicada") {
                    $bloqueos[] = "La devolucion no esta aplicada";
                }
                if ($this->valor($partida, "estatus", "") !== "aplicada") {
                    $bloqueos[] = "La partida no esta aplicada";
                }
                if ($this->valor($partida, "decision_inventario", "") !== "cuarentena") {
                    $bloqueos[] = "Solo se resuelve destino final de partidas en cuarentena";
                }
                if ($this->valor($partida, "inspeccion_estado", "") !== "cuarentena_confirmada") {
                    $bloqueos[] = "La partida debe tener cuarentena confirmada antes de destino final";
                }
                if (intval($this->valor($partida, "id_movimiento_inventario_devolucion", 0)) > 0) {
                    $bloqueos[] = "La partida ya tiene movimiento de inventario de devolucion";
                }
                if ($cantidad <= 0) {
                    $bloqueos[] = "Cantidad de devolucion invalida";
                }

                if ($idExistencia > 0 && $this->tablaExiste($db, "erp_inventario_existencias")) {
                    $stmt = $db->prepare("SELECT id_existencia_inventario, codigo_existencia, id_producto, id_sku_erp,
                            id_almacen_clave, lote, fecha_caducidad, ubicacion_id, ubicacion,
                            cantidad, cantidad_disponible, cantidad_apartada, costo_promedio, estatus_existencia
                        FROM erp_inventario_existencias
                        WHERE id_existencia_inventario=:existencia
                        LIMIT 1");
                    $stmt->execute(array(":existencia" => $idExistencia));
                    $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$existencia) {
                        $bloqueos[] = "Existencia origen no encontrada";
                    }
                }

                if ($idUnidad > 0 && $this->tablaExiste($db, "erp_inventario_unidades")) {
                    $stmt = $db->prepare("SELECT id_inventario_unidad, codigo_unico, codigo_etiqueta_interna,
                            id_existencia_inventario, id_almacen, cantidad_base_original, cantidad_base_disponible,
                            unidad_base, estatus, estado_etiqueta, estado_fisico
                        FROM erp_inventario_unidades
                        WHERE id_inventario_unidad=:unidad
                        LIMIT 1");
                    $stmt->execute(array(":unidad" => $idUnidad));
                    $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$unidad) {
                        $bloqueos[] = "Unidad fisica origen no encontrada";
                    }
                }

                if ($destino === "reintegrar_disponible") {
                    if (intval($this->valor($partida, "controla_inventario", 0)) !== 1) {
                        $bloqueos[] = "El producto no controla inventario";
                    }
                    if ($idExistencia <= 0 || !$existencia) {
                        $bloqueos[] = "Reintegrar requiere existencia origen";
                    }
                    if ($idUnidad > 0) {
                        $bloqueos[] = "Reintegrar unidad fisica requiere flujo especifico de unidad cerrada/abierta antes de disponible";
                    }
                    if ($existencia) {
                        $cantidadAntes = $this->redondearPosReal($this->valor($existencia, "cantidad", 0));
                        $disponibleAntes = $this->redondearPosReal($this->valor($existencia, "cantidad_disponible", 0));
                        $plan = array(
                            "tipo_movimiento" => "entrada",
                            "origen_tipo" => "devolucion_pos_inspeccion",
                            "id_existencia_inventario" => $idExistencia,
                            "id_almacen" => intval($this->valor($existencia, "id_almacen_clave", 0)),
                            "cantidad_base" => $cantidad,
                            "existencia_anterior" => $cantidadAntes,
                            "existencia_nueva" => $this->redondearPosReal($cantidadAntes + $cantidad),
                            "disponible_anterior" => $disponibleAntes,
                            "disponible_nuevo" => $this->redondearPosReal($disponibleAntes + $cantidad),
                            "referencia_sugerida" => "DEV-REINT-" . $this->valor($partida, "folio_devolucion", "")
                        );
                    }
                } elseif ($destino === "merma") {
                    $avisos[] = "Merma final debe exigir causa, evidencia y autorizador; normalmente no incrementa disponible";
                    $plan = array(
                        "tipo_movimiento" => "documental_merma_postventa",
                        "requiere_catalogo_causa" => true,
                        "requiere_evidencia" => true,
                        "impacto_caja" => false,
                        "impacto_disponible" => false
                    );
                } elseif (in_array($destino, array("garantia_proveedor", "reparacion"), true)) {
                    $avisos[] = "Destino final debe crear folio operativo fuera de disponible y ligarse a cliente/venta/SKU";
                    $plan = array(
                        "tipo_movimiento" => "documental_postventa",
                        "requiere_folio" => true,
                        "requiere_responsable" => true,
                        "impacto_caja" => false,
                        "impacto_disponible" => false
                    );
                } else {
                    $plan = array(
                        "tipo_movimiento" => "sin_movimiento",
                        "impacto_caja" => false,
                        "impacto_disponible" => false
                    );
                }
            }

            $ddlRequerido = array();
            if ($this->tablaExiste($db, "erp_ventas_devoluciones_detalle")) {
                foreach (array("destino_final", "fecha_destino_final", "resuelto_por") as $columna) {
                    if (!$this->columnaExiste($db, "erp_ventas_devoluciones_detalle", $columna)) {
                        $ddlRequerido[] = "erp_ventas_devoluciones_detalle." . $columna;
                    }
                }
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de destino final valido" : "Dry-run de destino final bloqueado", array(
                "dry_run" => true,
                "read_only" => true,
                "id_usuario" => $idUsuario,
                "id_devolucion_detalle" => $idDetalle,
                "destino_final" => $destino,
                "motivo" => $motivo,
                "bloqueos" => $bloqueos,
                "avisos" => $avisos,
                "ddl_requerido_para_apply_real" => $ddlRequerido,
                "partida" => $partida,
                "existencia" => $existencia,
                "unidad" => $unidad,
                "plan" => $plan,
                "contrato_apply_futuro" => array(
                    "requiere_transaccion" => true,
                    "requiere_for_update" => true,
                    "requiere_kardex_si_reintegra" => $destino === "reintegrar_disponible",
                    "requiere_evento_postventa" => true,
                    "no_escribe_bd_en_dry_run" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: simular cancelacion/devolucion de venta POS sin escribir BD.
     * Impacto: define reglas de reversa, devolucion y destino de inventario antes de operacion real.
     * Contrato: dry-run; no cancela venta, no registra devolucion y no mueve inventario/kardex.
     */
    public function devolucionDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idVenta = intval($this->valor($datos, "id_venta", 0));
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $folio = trim((string) $this->valor($datos, "folio", ""));
            $tipo = trim((string) $this->valor($datos, "tipo", "devolucion"));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            $decisionInventario = trim((string) $this->valor($datos, "decision_inventario", "cuarentena"));
            $items = $this->decodificarItems($this->valor($datos, "items", array()));
            $bloqueos = array();
            $avisos = array();
            $venta = null;
            $detalles = array();
            $partidas = array();
            $totalEstimado = 0;

            if (!in_array($tipo, array("cancelacion", "devolucion"), true)) {
                $bloqueos[] = "Tipo invalido; usa cancelacion o devolucion";
            }
            if ($idVenta <= 0 && $folio === "") {
                $bloqueos[] = "Indica id_venta o folio";
            }
            if ($motivo === "") {
                $bloqueos[] = "Captura motivo documentado";
            }
            if (!in_array($decisionInventario, array("reintegrar", "cuarentena", "merma", "sin_reingreso"), true)) {
                $bloqueos[] = "Decision de inventario invalida";
            }
            if ($tipo === "devolucion" && empty($items)) {
                $bloqueos[] = "Agrega partidas a devolver";
            }
            if (!$this->tablaExiste($db, "erp_ventas") || !$this->tablaExiste($db, "erp_ventas_devoluciones") || !$this->tablaExiste($db, "erp_ventas_devoluciones_detalle")) {
                $bloqueos[] = "Esquema de devoluciones/cancelaciones pendiente de autorizacion y respaldo externo";
            }
            if (empty($bloqueos)) {
                $venta = $this->consultarVentaParaReversaDryRun($db, $idVenta, $folio);
                if (!$venta) {
                    $bloqueos[] = "Venta ERP no encontrada";
                } else {
                    $idVenta = intval($venta["id_venta"]);
                    $folio = $venta["folio"];
                    if ($venta["canal"] !== "pos") {
                        $bloqueos[] = "Solo se permite reversa POS en este flujo";
                    }
                    if ($venta["tipo_documento"] !== "venta") {
                        $bloqueos[] = "El documento no es venta confirmada";
                    }
                    if (in_array($venta["estatus"], array("cancelada", "devuelta"), true)) {
                        $bloqueos[] = "La venta ya esta cancelada/devuelta";
                    }
                    if (!in_array($venta["estatus"], array("pagada", "pendiente_pago"), true)) {
                        $avisos[] = "Estatus de venta no usual para devolucion: " . $venta["estatus"];
                    }
                    $detalles = $this->detallesVentaParaReversaDryRun($db, $idVenta);
                    if (empty($detalles)) {
                        $bloqueos[] = "La venta no tiene detalle";
                    }
                }
            }

            if (empty($bloqueos) && $tipo === "cancelacion" && empty($items)) {
                foreach ($detalles as $detalle) {
                    $items[] = array(
                        "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
                        "cantidad_base" => floatval($detalle["cantidad_base"])
                    );
                }
            }

            if (empty($bloqueos)) {
                $detalleIndex = array();
                foreach ($detalles as $detalle) {
                    $detalleIndex[intval($detalle["id_venta_detalle"])] = $detalle;
                }
                foreach ($items as $indice => $item) {
                    $idDetalle = intval($this->valor($item, "id_venta_detalle", 0));
                    $cantidad = round(floatval($this->valor($item, "cantidad_base", 0)), 6);
                    if ($idDetalle <= 0) {
                        $bloqueos[] = "Partida " . ($indice + 1) . ": indica id_venta_detalle";
                        continue;
                    }
                    if ($cantidad <= 0) {
                        $bloqueos[] = "Partida " . ($indice + 1) . ": cantidad a devolver debe ser mayor a cero";
                        continue;
                    }
                    if (!isset($detalleIndex[$idDetalle])) {
                        $bloqueos[] = "Partida " . ($indice + 1) . ": detalle no pertenece a la venta";
                        continue;
                    }
                    $detalle = $detalleIndex[$idDetalle];
                    $devueltoPrevio = $this->cantidadDevueltaPreviaDryRun($db, $idVenta, $idDetalle);
                    $vendido = round(floatval($detalle["cantidad_base"]), 6);
                    $disponible = round(max(0, $vendido - $devueltoPrevio), 6);
                    if ($cantidad > $disponible + 0.0001) {
                        $bloqueos[] = "Partida " . ($indice + 1) . ": cantidad excede disponible para devolver";
                    }
                    $proporcion = $vendido > 0 ? min(1, $cantidad / $vendido) : 0;
                    $importe = round(floatval($detalle["total"]) * $proporcion, 6);
                    $totalEstimado += $importe;
                    $partidas[] = array(
                        "id_venta_detalle" => $idDetalle,
                        "id_sku_erp" => intval($detalle["id_sku_erp"]),
                        "sku" => $detalle["sku"],
                        "descripcion" => $detalle["descripcion"],
                        "cantidad_vendida" => $vendido,
                        "cantidad_devuelta_previa" => $devueltoPrevio,
                        "cantidad_disponible_devolver" => $disponible,
                        "cantidad_solicitada" => $cantidad,
                        "importe_estimado" => $importe,
                        "controla_inventario" => intval($detalle["controla_inventario"]),
                        "decision_inventario" => $decisionInventario
                    );
                }
            }
            if (empty($bloqueos) && $totalEstimado > 0) {
                if ($idUsuario > 0) {
                    $asignacionPos = $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
                    $turnoAbierto = $this->valorRutaPosReal($asignacionPos, array("depurar", "turno_abierto"), array());
                    if (empty($turnoAbierto)) {
                        $avisos[] = "El reembolso real requiere turno/caja abierto o registrar saldo a favor segun politica";
                    }
                } else {
                    $avisos[] = "El reembolso real debe ejecutarse con usuario POS para validar caja/turno";
                }
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de devolucion valido" : "Dry-run de devolucion bloqueado", array(
                "dry_run" => true,
                "id_usuario" => $idUsuario,
                "id_venta" => $idVenta,
                "folio" => $folio,
                "tipo" => $tipo,
                "motivo" => $motivo,
                "decision_inventario" => $decisionInventario,
                "items" => $items,
                "venta" => $venta,
                "partidas" => $partidas,
                "totales" => array(
                    "total_venta" => $venta ? round(floatval($venta["total"]), 6) : 0,
                    "pagado_total" => $venta ? round(floatval($venta["pagado_total"]), 6) : 0,
                    "reembolso_estimado" => round($totalEstimado, 6)
                ),
                "bloqueos" => array_values(array_unique($bloqueos)),
                "avisos" => array_values(array_unique($avisos)),
                "contrato_reversa" => array(
                    "no_borrar_venta_original" => true,
                    "registrar_folio_devolucion" => true,
                    "ligar_detalle_original" => true,
                    "crear_kardex_reversa_si_reingresa" => true,
                    "conservar_trazabilidad_unidad_fisica" => true,
                    "registrar_motivo_y_usuario" => true,
                    "registrar_reembolso_caja_si_aplica" => true,
                    "no_recalcular_precio_historico" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: aplicar una reversa POS real con folio, detalle, decision financiera y trazabilidad.
     * Impacto: crea devolucion/cancelacion, puede registrar reembolso de caja o saldo a favor y actualiza estatus de venta.
     * Contrato: escritura real; requiere autorizacion externa, usuario, dry-run valido y esquema de reversas POS completo.
     */
    public function confirmarReversaPosReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $decisionFinanciera = trim((string) $this->valor($datos, "decision_financiera", "saldo_favor"));
        $observaciones = trim((string) $this->valor($datos, "observaciones", ""));
        $permitidasFinanzas = array("reembolso_caja", "saldo_favor", "cambio_producto", "sin_reembolso", "reintegro_saldo_crm", "mixta_saldo_crm");

        if ($idUsuario <= 0) {
            return $this->respuesta(true, "warning", "Usuario operador obligatorio", array("bloqueos" => array("usuario_obligatorio")));
        }
        if (!$this->schemaReversasPosCompleto($db)) {
            return $this->respuesta(true, "warning", "Esquema de reversas POS pendiente", array("bloqueos" => array("schema_reversas_pos_pendiente")));
        }
        if (!in_array($decisionFinanciera, $permitidasFinanzas, true)) {
            return $this->respuesta(false, "warning", "Decision financiera invalida", array("bloqueos" => array("decision_financiera_invalida")));
        }

        $dryRun = $this->devolucionDryRun($datos);
        $bloqueosDryRun = $this->valorRutaPosReal($dryRun, array("depurar", "bloqueos"), array());
        if (!empty($bloqueosDryRun) || (isset($dryRun["tipo"]) && $dryRun["tipo"] !== "success")) {
            return $this->respuesta(false, "warning", "Reversa POS real bloqueada por dry-run", array(
                "bloqueos" => $bloqueosDryRun,
                "dry_run" => $dryRun
            ));
        }

        $ventaDry = $this->valorRutaPosReal($dryRun, array("depurar", "venta"), array());
        $partidasDry = $this->valorRutaPosReal($dryRun, array("depurar", "partidas"), array());
        $totalesDry = $this->valorRutaPosReal($dryRun, array("depurar", "totales"), array());
        $tipo = trim((string) $this->valorRutaPosReal($dryRun, array("depurar", "tipo"), "devolucion"));
        $motivo = trim((string) $this->valorRutaPosReal($dryRun, array("depurar", "motivo"), ""));
        $decisionInventario = trim((string) $this->valorRutaPosReal($dryRun, array("depurar", "decision_inventario"), "cuarentena"));
        $idVenta = intval($this->valor($ventaDry, "id_venta", 0));
        $folioVenta = trim((string) $this->valor($ventaDry, "folio", ""));
        $montoEstimado = $this->redondearPosReal($this->valor($totalesDry, "reembolso_estimado", 0));

        if ($idVenta <= 0 || empty($partidasDry)) {
            return $this->respuesta(false, "warning", "Reversa POS sin venta o partidas validas", array("bloqueos" => array("reversa_sin_partidas")));
        }
        if (in_array($decisionFinanciera, array("reembolso_caja", "reintegro_saldo_crm", "mixta_saldo_crm"), true) && $montoEstimado <= 0) {
            return $this->respuesta(false, "warning", "Reembolso de caja sin monto valido", array("bloqueos" => array("reembolso_sin_monto")));
        }

        $idAlmacen = intval($this->valor($ventaDry, "id_almacen", 0));
        $idCaja = intval($this->valor($ventaDry, "id_caja", 0));
        $idTurno = null;
        $turnoActual = array();
        $componentesFinancieros = $this->prepararComponentesFinancierosReversaPosReal($db, $idVenta, $ventaDry, $decisionFinanciera, $montoEstimado);
        $montoCajaReversa = $this->redondearPosReal($this->valor($componentesFinancieros, "monto_caja", 0));
        $montoSaldoCrmReversa = $this->redondearPosReal($this->valor($componentesFinancieros, "monto_saldo_crm", 0));
        $montoNoCajaReversa = $this->redondearPosReal($this->valor($componentesFinancieros, "monto_no_caja", 0));
        if ($montoCajaReversa > 0) {
            $asignacion = $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
            $turnoActual = $this->valorRutaPosReal($asignacion, array("depurar", "turno_abierto"), array());
            $idTurno = intval($this->valor($turnoActual, "id_turno_caja", 0));
            if ($idTurno <= 0 || intval($this->valor($turnoActual, "id_caja", 0)) !== $idCaja || intval($this->valor($turnoActual, "id_almacen", 0)) !== $idAlmacen) {
                return $this->respuesta(false, "warning", "El reembolso de caja requiere turno abierto de la misma caja/almacen", array(
                    "bloqueos" => array("turno_caja_reembolso_pendiente"),
                    "venta" => array("id_almacen" => $idAlmacen, "id_caja" => $idCaja),
                    "turno_abierto" => $turnoActual
                ));
            }
        }

        try {
            $db->beginTransaction();

            $venta = $this->bloquearVentaParaReversaPosReal($db, $idVenta);
            if (!$venta) {
                throw new Exception("Venta no encontrada al aplicar reversa POS");
            }
            if (in_array($venta["estatus"], array("cancelada", "devuelta"), true)) {
                throw new Exception("La venta ya esta cancelada/devuelta");
            }

            $componentesFinancieros = $this->prepararComponentesFinancierosReversaPosReal($db, $idVenta, $venta, $decisionFinanciera, $montoEstimado);
            $montoCajaReversa = $this->redondearPosReal($this->valor($componentesFinancieros, "monto_caja", 0));
            $montoSaldoCrmReversa = $this->redondearPosReal($this->valor($componentesFinancieros, "monto_saldo_crm", 0));
            $montoNoCajaReversa = $this->redondearPosReal($this->valor($componentesFinancieros, "monto_no_caja", 0));

            if ($montoCajaReversa > 0) {
                if ($idTurno <= 0) {
                    throw new Exception("La reversa con componente caja requiere turno abierto");
                }
                $turnoBloqueado = $this->bloquearTurnoPosReal($db, $idTurno, $idCaja, $idAlmacen);
                if (!$turnoBloqueado) {
                    throw new Exception("El turno ya no esta abierto para reembolso");
                }
            }

            $folioDevolucion = $this->generarFolioDevolucionPosReal($db, $tipo === "cancelacion" ? "CAN" : "DEV");
            $montoReembolso = $montoCajaReversa;
            $montoSaldoFavor = $decisionFinanciera === "saldo_favor" ? $montoEstimado : 0;
            $idClienteCrmReversa = intval($this->valor($componentesFinancieros, "id_cliente_crm", 0));
            $snapshot = json_encode(array(
                "venta" => $ventaDry,
                "partidas" => $partidasDry,
                "totales" => $totalesDry,
                "decision_inventario" => $decisionInventario,
                "decision_financiera" => $decisionFinanciera,
                "componentes_financieros" => $componentesFinancieros,
                "aplicado_por" => $idUsuario
            ), JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("INSERT INTO erp_ventas_devoluciones
                (folio, id_venta, id_cliente_crm, id_caja, id_almacen, id_turno_caja, tipo, estatus, motivo,
                 decision_financiera, monto_reembolso, monto_saldo_favor, monto_reintegro_saldo_crm,
                 monto_no_caja, observaciones,
                 creado_por, autorizado_por, fecha_autorizacion, aplicado_por, fecha_aplicacion,
                 datos_snapshot, fecha_actualizacion)
                VALUES (:folio, :venta, :cliente_crm, :caja, :almacen, :turno, :tipo, 'aplicada', :motivo,
                 :decision_financiera, :reembolso, :saldo_favor, :reintegro_saldo_crm,
                 :monto_no_caja, :observaciones,
                 :usuario, :usuario, NOW(), :usuario, NOW(), :snapshot, NOW())");
            $stmt->execute(array(
                ":folio" => $folioDevolucion,
                ":venta" => $idVenta,
                ":cliente_crm" => $idClienteCrmReversa > 0 ? $idClienteCrmReversa : null,
                ":caja" => $idCaja > 0 ? $idCaja : null,
                ":almacen" => $idAlmacen > 0 ? $idAlmacen : null,
                ":turno" => $idTurno > 0 ? $idTurno : null,
                ":tipo" => $tipo,
                ":motivo" => $motivo,
                ":decision_financiera" => $decisionFinanciera,
                ":reembolso" => $montoReembolso,
                ":saldo_favor" => $montoSaldoFavor,
                ":reintegro_saldo_crm" => $montoSaldoCrmReversa,
                ":monto_no_caja" => $montoNoCajaReversa,
                ":observaciones" => $observaciones,
                ":usuario" => $idUsuario,
                ":snapshot" => $snapshot
            ));
            $idDevolucion = intval($db->lastInsertId());

            $evidenciaDetalle = array();
            foreach ($partidasDry as $partida) {
                $idDetalleVenta = intval($this->valor($partida, "id_venta_detalle", 0));
                $cantidad = $this->redondearPosReal($this->valor($partida, "cantidad_solicitada", 0));
                $importe = $this->redondearPosReal($this->valor($partida, "importe_estimado", 0));
                $trazaOrigen = $this->trazaInventarioVentaDetallePosReal($db, $idVenta, $idDetalleVenta);
                $idMovimientoDevolucion = null;
                $idExistencia = intval($this->valor($trazaOrigen, "id_existencia_inventario", 0));
                $idUnidad = intval($this->valor($trazaOrigen, "id_inventario_unidad", 0));

                if ($decisionInventario === "reintegrar" && intval($this->valor($partida, "controla_inventario", 0)) === 1) {
                    if ($idUnidad > 0) {
                        throw new Exception("Reintegro automatico de unidad fisica requiere flujo de inspeccion; usa cuarentena");
                    }
                    if ($idExistencia <= 0) {
                        throw new Exception("No hay existencia origen para reintegrar inventario");
                    }
                    $idMovimientoDevolucion = $this->aplicarEntradaInventarioReversaPosReal($db, $idDevolucion, $folioDevolucion, $partida, $trazaOrigen, $idAlmacen, $cantidad, $idUsuario);
                }

                $snapshotDetalle = json_encode(array(
                    "partida" => $partida,
                    "traza_origen" => $trazaOrigen,
                    "decision_inventario" => $decisionInventario,
                    "decision_financiera" => $decisionFinanciera
                ), JSON_UNESCAPED_UNICODE);
                $stmt = $db->prepare("INSERT INTO erp_ventas_devoluciones_detalle
                    (id_devolucion, id_venta, id_venta_detalle, id_movimiento_inventario_origen,
                     id_movimiento_inventario_devolucion, id_existencia_inventario, id_inventario_unidad,
                     id_almacen_destino, cantidad_base, importe_reembolso, decision_inventario,
                     estatus, datos_snapshot)
                    VALUES (:devolucion, :venta, :detalle, :mov_origen, :mov_dev, :existencia,
                     :unidad, :almacen_destino, :cantidad, :importe, :decision_inv,
                     'aplicada', :snapshot)");
                $stmt->execute(array(
                    ":devolucion" => $idDevolucion,
                    ":venta" => $idVenta,
                    ":detalle" => $idDetalleVenta,
                    ":mov_origen" => intval($this->valor($trazaOrigen, "id_movimiento_inventario", 0)) ?: null,
                    ":mov_dev" => $idMovimientoDevolucion,
                    ":existencia" => $idExistencia > 0 ? $idExistencia : null,
                    ":unidad" => $idUnidad > 0 ? $idUnidad : null,
                    ":almacen_destino" => $decisionInventario === "reintegrar" ? $idAlmacen : null,
                    ":cantidad" => $cantidad,
                    ":importe" => $importe,
                    ":decision_inv" => $decisionInventario,
                    ":snapshot" => $snapshotDetalle
                ));
                $evidenciaDetalle[] = array(
                    "id_devolucion_detalle" => intval($db->lastInsertId()),
                    "id_venta_detalle" => $idDetalleVenta,
                    "cantidad_base" => $cantidad,
                    "importe_reembolso" => $importe,
                    "id_movimiento_inventario_devolucion" => $idMovimientoDevolucion
                );
            }

            $idMovimientoCaja = null;
            $idVentaPagoReembolso = null;
            $componentesRegistrados = array();
            if ($montoReembolso > 0) {
                $idMovimientoCaja = $this->registrarReembolsoCajaReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, $idAlmacen, $idCaja, $idTurno, $montoReembolso, $motivo, $idUsuario);
                $idVentaPagoReembolso = $this->registrarPagoReembolsoVentaPosReal($db, $idVenta, $idCaja, $idTurno, $idMovimientoCaja, $folioDevolucion, $montoReembolso, $idUsuario);
                $db->prepare("UPDATE erp_ventas_devoluciones SET id_movimiento_caja=:movimiento WHERE id_devolucion=:devolucion")
                    ->execute(array(":movimiento" => $idMovimientoCaja, ":devolucion" => $idDevolucion));
                $db->prepare("UPDATE erp_pos_turnos
                    SET monto_esperado=ROUND(monto_esperado-:monto, 6)
                    WHERE id_turno_caja=:turno")
                    ->execute(array(":monto" => $montoReembolso, ":turno" => $idTurno));
            }
            if ($this->schemaReversasSaldoCrmPosCompleto($db)) {
                $componentesRegistrados = $this->registrarComponentesFinancierosReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, $componentesFinancieros, $idAlmacen, $idCaja, $idTurno, $idMovimientoCaja, $motivo, $idUsuario);
            }

            $estatusVenta = $this->estatusVentaDespuesReversaPosReal($db, $idVenta, $tipo);
            $db->prepare("UPDATE erp_ventas
                SET estatus=:estatus,
                    fecha_cancelacion=CASE WHEN :tipo='cancelacion' THEN NOW() ELSE fecha_cancelacion END,
                    cancelado_por=CASE WHEN :tipo='cancelacion' THEN :usuario ELSE cancelado_por END,
                    motivo_cancelacion=CASE WHEN :tipo='cancelacion' THEN :motivo ELSE motivo_cancelacion END,
                    fecha_actualizacion=NOW()
                WHERE id_venta=:venta")
                ->execute(array(
                    ":estatus" => $estatusVenta,
                    ":tipo" => $tipo,
                    ":usuario" => $idUsuario,
                    ":motivo" => $motivo,
                    ":venta" => $idVenta
                ));

            $db->commit();

            return $this->respuesta(false, "success", "Reversa POS aplicada", array(
                "modo" => "reversa_real_pos",
                "id_devolucion" => $idDevolucion,
                "folio_devolucion" => $folioDevolucion,
                "id_venta" => $idVenta,
                "folio_venta" => $folioVenta,
                "tipo" => $tipo,
                "decision_inventario" => $decisionInventario,
                "decision_financiera" => $decisionFinanciera,
                "monto_reembolso" => $montoReembolso,
                "monto_saldo_favor" => $montoSaldoFavor,
                "monto_reintegro_saldo_crm" => $montoSaldoCrmReversa,
                "monto_no_caja" => $montoNoCajaReversa,
                "id_movimiento_caja" => $idMovimientoCaja,
                "id_venta_pago_reembolso" => $idVentaPagoReembolso,
                "componentes_financieros" => $componentesRegistrados,
                "estatus_venta" => $estatusVenta,
                "detalles" => $evidenciaDetalle
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: entregar catalogos iniciales del POS sin crear ventas ni mover inventario.
     * Impacto: Ventas/POS read-only; usa almacenes oficiales ERP y metodos de pago existentes.
     * Contrato: no modifica datos; devuelve listas tolerantes a tablas faltantes.
     */
    public function catalogosPos() {
        try {
            $db = $this->getConexion();
            return $this->respuesta(false, "success", "Catalogos POS consultados", array(
                "almacenes" => $this->listarAlmacenesVenta($db),
                "cajas" => $this->listarCajasPos($db),
                "turnos_abiertos" => $this->listarTurnosAbiertosPos($db),
                "schema_cajas_pendiente" => !$this->tablaExiste($db, "erp_pos_cajas"),
                "schema_turnos_pendiente" => !$this->tablaExiste($db, "erp_pos_turnos"),
                "schema_clientes_pendiente" => !$this->tablaExiste($db, "erp_clientes"),
                "schema_listas_precios_pendiente" => !$this->tablaExiste($db, "erp_listas_precios"),
                "metodos_pago" => $this->listarMetodosPago($db),
                "canales" => array(
                    array("canal" => "pos", "nombre" => "Punto de venta"),
                    array("canal" => "pedido_tienda", "nombre" => "Pedido tienda")
                ),
                "tipos_documento" => array(
                    array("tipo_documento" => "venta", "nombre" => "Venta"),
                    array("tipo_documento" => "pedido", "nombre" => "Pedido"),
                    array("tipo_documento" => "apartado", "nombre" => "Apartado")
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: buscar SKUs ERP vendibles para POS con imagen, precio, reglas de inventario y disponibilidad.
     * Impacto: reemplaza la busqueda legacy basada en `ecom_productos` para el nuevo POS.
     * Contrato: solo lectura; `existencia_disponible` es informativa y backend debe revalidar antes de cobrar.
     */
    public function buscarSkusPos($filtros = array()) {
        try {
            $db = $this->getConexion();
            $termino = trim((string) $this->valor($filtros, "q", ""));
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            $limite = max(10, min(80, intval($this->valor($filtros, "limite", 40))));

            if (strlen($termino) < 2) {
                return $this->respuesta(false, "success", "Escribe al menos dos caracteres", array());
            }

            $sql = "SELECT s.id_sku, s.id_producto_erp, s.sku, COALESCE(s.nombre, p.nombre) nombre_sku,
                    p.codigo_producto, p.nombre producto,
                    s.tipo_inventario, s.permite_venta_sin_existencia, s.estatus estatus_sku,
                    COALESCE(pr.precio, 0) precio, COALESCE(pr.moneda, 'MXN') moneda,
                    COALESCE(imp.iva_porcentaje, 0) iva_porcentaje,
                    COALESCE(imp.ieps_porcentaje, 0) ieps_porcentaje,
                    COALESCE(imp.incluye_impuestos, 0) incluye_impuestos,
                    COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
                    COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
                    COALESCE(r.precision_decimal, 0) precision_decimal,
                    COALESCE(r.incremento_minimo_venta, 1.000000) incremento_minimo_venta,
                    COALESCE(NULLIF(r.unidad_venta_label, ''), ub.abreviatura, ub.codigo, '') unidad_venta_label,
                    COALESCE(r.generar_etiqueta_interna, 0) generar_etiqueta_interna,
                    COALESCE(r.requiere_lote, 0) requiere_lote,
                    COALESCE(r.requiere_caducidad, 0) requiere_caducidad,
                    COALESCE(r.requiere_escaneo_venta, 0) requiere_escaneo_venta,
                    COALESCE(img.url_imagen, img_producto.url_imagen, '') url_imagen,
                    COALESCE(inv.existencia_disponible, 0) existencia_disponible,
                    COALESCE(inv.cantidad_apartada, 0) cantidad_apartada,
                    COALESCE(inv.unidades_cerradas, 0) unidades_cerradas,
                    COALESCE(inv.unidades_abiertas, 0) unidades_abiertas
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
                LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
                LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku
                    AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
                LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku
                LEFT JOIN (
                    SELECT i.id_producto_erp, i.id_sku, i.url_imagen
                    FROM erp_catalogo_imagenes i
                    INNER JOIN (
                        SELECT COALESCE(id_sku, 0) id_sku_clave, id_producto_erp,
                               MIN(id_imagen_erp) id_imagen_erp
                        FROM erp_catalogo_imagenes
                        WHERE estatus='activo'
                        GROUP BY COALESCE(id_sku, 0), id_producto_erp
                    ) x ON x.id_imagen_erp=i.id_imagen_erp
                ) img ON (img.id_sku=s.id_sku OR (img.id_sku IS NULL AND img.id_producto_erp=s.id_producto_erp))
                LEFT JOIN (
                    SELECT i.id_producto_erp, i.url_imagen
                    FROM erp_catalogo_imagenes i
                    INNER JOIN (
                        SELECT id_producto_erp, MIN(id_imagen_erp) id_imagen_erp
                        FROM erp_catalogo_imagenes
                        WHERE estatus='activo' AND TRIM(COALESCE(url_imagen,''))<>''
                        GROUP BY id_producto_erp
                    ) x ON x.id_imagen_erp=i.id_imagen_erp
                ) img_producto ON img_producto.id_producto_erp=s.id_producto_erp
                LEFT JOIN (
                    SELECT e.id_sku_erp,
                           SUM(CASE WHEN (:almacen=0 OR e.id_almacen_clave=:almacen_filtro) THEN e.cantidad_disponible ELSE 0 END) existencia_disponible,
                           SUM(CASE WHEN (:almacen_ap=0 OR e.id_almacen_clave=:almacen_ap_filtro) THEN e.cantidad_apartada ELSE 0 END) cantidad_apartada,
                           SUM(CASE WHEN u.estado_fisico='cerrada' AND u.estatus='disponible'
                                AND (:almacen_uc=0 OR u.id_almacen=:almacen_uc_filtro) THEN 1 ELSE 0 END) unidades_cerradas,
                           SUM(CASE WHEN u.estado_fisico='abierta' AND u.estatus='disponible'
                                AND (:almacen_ua=0 OR u.id_almacen=:almacen_ua_filtro) THEN 1 ELSE 0 END) unidades_abiertas
                    FROM erp_inventario_existencias e
                    LEFT JOIN erp_inventario_unidades u ON u.id_existencia_inventario=e.id_existencia_inventario
                    WHERE e.estatus_existencia IN ('disponible','agotada')
                    GROUP BY e.id_sku_erp
                ) inv ON inv.id_sku_erp=s.id_sku
                WHERE s.estatus='activo' AND p.estatus='activo'
                  AND (
                    s.sku LIKE :termino OR s.nombre LIKE :termino OR p.nombre LIKE :termino
                    OR p.codigo_producto LIKE :termino
                    OR EXISTS (
                        SELECT 1 FROM erp_catalogo_sku_codigos cod
                        WHERE cod.id_sku=s.id_sku AND cod.estatus='activo' AND cod.codigo LIKE :termino_cod
                    )
                  )
                ORDER BY CASE WHEN s.sku=:exacto THEN 0 ELSE 1 END, p.nombre, s.sku
                LIMIT " . intval($limite);
            $stmt = $db->prepare($sql);
            $params = array(
                ":almacen" => $idAlmacen,
                ":almacen_filtro" => $idAlmacen,
                ":almacen_ap" => $idAlmacen,
                ":almacen_ap_filtro" => $idAlmacen,
                ":almacen_uc" => $idAlmacen,
                ":almacen_uc_filtro" => $idAlmacen,
                ":almacen_ua" => $idAlmacen,
                ":almacen_ua_filtro" => $idAlmacen,
                ":termino" => "%" . $termino . "%",
                ":termino_cod" => "%" . $termino . "%",
                ":exacto" => $termino
            );
            $stmt->execute($params);
            return $this->respuesta(false, "success", "SKUs POS consultados", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: consultar disponibilidad vendible por SKU/almacen separando existencia agregada y unidades fisicas.
     * Impacto: permite al POS mostrar si hay unidad cerrada, unidad abierta o solo saldo agregado.
     * Contrato: solo lectura; no reserva ni descuenta.
     */
    public function disponibilidadSku($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idSku = intval($this->valor($filtros, "id_sku", 0));
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            if ($idSku <= 0) {
                return $this->respuesta(true, "warning", "SKU obligatorio");
            }

            $sku = $this->consultarSkuVenta($db, $idSku);
            if (!$sku) {
                return $this->respuesta(true, "warning", "SKU no encontrado o no activo");
            }

            $existencias = $this->existenciasDisponiblesVenta($db, $idSku, $idAlmacen);
            $unidades = $this->unidadesDisponiblesVenta($db, $idSku, $idAlmacen);

            return $this->respuesta(false, "success", "Disponibilidad POS consultada", array(
                "sku" => $sku,
                "existencias" => $existencias,
                "unidades" => $unidades,
                "resumen" => $this->resumenDisponibilidad($existencias, $unidades)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }



    /**
     * Documentacion IA: Codex GPT-5, 2026-07-11.
     * Proposito: simular una venta POS con inventario pendiente controlado.
     * Impacto: calcula faltante y alerta propuesta sin crear venta, pendiente, notificacion ni kardex.
     * Contrato: read-only; la venta real requiere autorizacion posterior y revalidacion transaccional.
     */
    public function ventaInventarioPendienteDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idSku = intval($this->valor($datos, "id_sku", 0));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $cantidad = round(floatval($this->valor($datos, "cantidad", 0)), 6);
            $canal = trim((string) $this->valor($datos, "canal", "pos"));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            $idCliente = intval($this->valor($datos, "id_cliente", $this->valor($datos, "id_cliente_crm", 0)));
            if (!in_array($canal, array("pos", "pedido_tienda"), true)) {
                $canal = "pos";
            }
            if ($idSku <= 0 || $idAlmacen <= 0 || $cantidad <= 0) {
                return $this->respuesta(true, "warning", "SKU, almacen y cantidad son obligatorios para simular inventario pendiente");
            }
            if (!$this->tablaExiste($db, "erp_pos_inventario_pendientes") || !$this->tablaExiste($db, "erp_pos_inventario_pendientes_eventos")) {
                return $this->respuesta(true, "warning", "Falta aplicar DDL de inventario pendiente POS");
            }

            $sku = $this->consultarSkuVenta($db, $idSku);
            if (!$sku) {
                return $this->respuesta(true, "warning", "SKU no encontrado o no activo");
            }
            $controlaInventario = intval($this->valor($sku, "controla_inventario", 1)) === 1;
            $permiteSinExistencia = intval($this->valor($sku, "permite_venta_sin_existencia", 0)) === 1;
            $permiteExistenciaNegativa = intval($this->valor($sku, "permite_existencia_negativa", 0)) === 1;
            $existencias = $this->existenciasDisponiblesVenta($db, $idSku, $idAlmacen);
            $unidades = $this->unidadesDisponiblesVenta($db, $idSku, $idAlmacen);
            $resumen = $this->resumenDisponibilidad($existencias, $unidades);
            $disponible = $controlaInventario ? round(floatval($this->valor($resumen, "disponible", 0)), 6) : $cantidad;
            $cantidadCubierta = $controlaInventario ? round(min($cantidad, max(0, $disponible)), 6) : $cantidad;
            $cantidadPendiente = $controlaInventario ? round(max(0, $cantidad - $cantidadCubierta), 6) : 0;
            $bloqueos = array();
            $advertencias = array();
            $estado = "normal";
            if (!$controlaInventario) {
                $advertencias[] = "El SKU no controla inventario; no requiere pendiente operativo.";
            } else if ($cantidadPendiente <= 0) {
                $advertencias[] = "Inventario suficiente; se debe vender por flujo normal con kardex.";
            } else {
                $estado = "pendiente_autorizable";
                $advertencias[] = "La venta podria permitirse solo bajo politica de inventario pendiente y generando alerta a Inventario/Existencias.";
                if (!$permiteSinExistencia || !$permiteExistenciaNegativa) {
                    $advertencias[] = "Las banderas globales del SKU no autorizan faltantes; POS usara solo politica por sucursal/canal para no afectar ecommerce.";
                }
            }

            $schemaListasPendiente = !$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle");
            $precio = $this->resolverPrecioSkuDryRun($db, $sku, array("id_cliente" => $idCliente), $canal, $idAlmacen, $schemaListasPendiente);
            $totalEstimado = round($cantidad * floatval($this->valor($precio, "precio_aplicado", 0)), 6);
            $folioPropuesto = "PINV-" . date("Ymd") . "-PREVIEW";

            $politicaPos = $cantidadPendiente > 0
                ? $this->consultarPoliticaInventarioPendientePos($db, $idSku, $idAlmacen, $canal, $cantidadPendiente, round($cantidadPendiente * floatval($this->valor($precio, "precio_aplicado", 0)), 6))
                : array("schema_pendiente" => false, "politica" => null, "bloqueos" => array());
            if ($cantidadPendiente > 0) {
                foreach ($this->valor($politicaPos, "bloqueos", array()) as $bloqueoPolitica) {
                    $bloqueos[] = $bloqueoPolitica;
                    $estado = "bloqueado";
                }
            }
            $pendientePropuesto = null;
            $notificacionPropuesta = null;
            if ($cantidadPendiente > 0) {
                $pendientePropuesto = array(
                    "folio" => $folioPropuesto,
                    "id_almacen" => $idAlmacen,
                    "id_sku_erp" => $idSku,
                    "sku" => $this->valor($sku, "sku", ""),
                    "descripcion" => $this->valor($sku, "nombre_sku", $this->valor($sku, "producto", "")),
                    "cantidad_vendida" => $cantidad,
                    "cantidad_cubierta" => $cantidadCubierta,
                    "cantidad_pendiente" => $cantidadPendiente,
                    "unidad_base" => $this->valor($sku, "unidad_venta_label", ""),
                    "precio_unitario_snapshot" => floatval($this->valor($precio, "precio_aplicado", 0)),
                    "estatus" => "pendiente_revision",
                    "prioridad" => "alta",
                    "origen" => "pos_venta",
                    "motivo" => $motivo
                );
                $notificacionPropuesta = array(
                    "tipo" => "pos_venta_inventario_pendiente",
                    "modulo_origen" => "ventas_pos",
                    "area_responsable" => "inventario",
                    "permiso_requerido" => "inventario.ver",
                    "titulo" => "Venta POS con inventario pendiente",
                    "descripcion" => "Validar existencia fisica del SKU " . $this->valor($sku, "sku", "") . " en almacen " . $idAlmacen,
                    "prioridad" => "alta",
                    "estatus" => "pendiente",
                    "payload_json" => array(
                        "id_sku" => $idSku,
                        "id_almacen" => $idAlmacen,
                        "cantidad_pendiente" => $cantidadPendiente,
                        "cantidad_vendida" => $cantidad
                    )
                );
            }

            return $this->respuesta(false, $estado === "bloqueado" ? "warning" : "success", "Dry-run de inventario pendiente POS generado", array(
                "modo" => "dry_run_inventario_pendiente_pos",
                "read_only" => true,
                "estado" => $estado,
                "bloqueos" => $bloqueos,
                "advertencias" => $advertencias,
                "sku" => $sku,
                "precio" => $precio,
                "cantidad_solicitada" => $cantidad,
                "disponible_actual" => $disponible,
                "cantidad_cubierta" => $cantidadCubierta,
                "cantidad_pendiente" => $cantidadPendiente,
                "politica" => array(
                    "permite_venta_sin_existencia" => $permiteSinExistencia,
                    "permite_existencia_negativa" => $permiteExistenciaNegativa,
                    "politica_pos_schema_pendiente" => !empty($politicaPos["schema_pendiente"]),
                    "politica_pos" => $this->valor($politicaPos, "politica", null),
                    "requiere_politica_pos_sucursal" => true
                ),
                "total_estimado" => $totalEstimado,
                "disponibilidad" => $resumen,
                "pendiente_propuesto" => $pendientePropuesto,
                "notificacion_propuesta" => $notificacionPropuesta,
                "contrato" => array(
                    "no_crea_venta" => true,
                    "no_crea_pendiente" => true,
                    "no_crea_notificacion" => true,
                    "no_mueve_inventario" => true,
                    "requiere_revalidacion_real" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function consultarPoliticaInventarioPendientePos($db, $idSku, $idAlmacen, $canal, $cantidad, $monto) {
        if (!$this->tablaExiste($db, "erp_pos_politicas_venta_inventario")) {
            return array("schema_pendiente" => true, "politica" => null, "bloqueos" => array("Falta esquema de politicas POS para inventario pendiente"));
        }
        $stmt = $db->prepare("SELECT *
            FROM erp_pos_politicas_venta_inventario
            WHERE estatus='activa'
              AND permite_inventario_pendiente=1
              AND id_almacen=:almacen
              AND (id_sku_erp IS NULL OR id_sku_erp=:sku)
              AND (canal IS NULL OR canal='' OR canal=:canal)
              AND (fecha_inicio IS NULL OR fecha_inicio<=CURRENT_TIMESTAMP)
              AND (fecha_fin IS NULL OR fecha_fin>=CURRENT_TIMESTAMP)
            ORDER BY CASE WHEN id_sku_erp=:sku_orden THEN 0 ELSE 1 END, cantidad_maxima_pendiente DESC, id_politica_inventario_pos DESC
            LIMIT 1");
        $stmt->execute(array(
            ":almacen" => intval($idAlmacen),
            ":sku" => intval($idSku),
            ":sku_orden" => intval($idSku),
            ":canal" => $canal
        ));
        $politica = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$politica) {
            return array("schema_pendiente" => false, "politica" => null, "bloqueos" => array("No hay politica POS activa para vender este SKU con inventario pendiente en la sucursal"));
        }
        $bloqueos = array();
        $maxCantidad = round(floatval($this->valor($politica, "cantidad_maxima_pendiente", 0)), 6);
        $maxMonto = round(floatval($this->valor($politica, "monto_maximo", 0)), 6);
        if ($maxCantidad > 0 && round(floatval($cantidad), 6) > $maxCantidad + 0.000001) {
            $bloqueos[] = "La cantidad pendiente supera la politica POS autorizada";
        }
        if ($maxMonto > 0 && round(floatval($monto), 6) > $maxMonto + 0.000001) {
            $bloqueos[] = "El monto pendiente supera la politica POS autorizada";
        }
        return array("schema_pendiente" => false, "politica" => $politica, "bloqueos" => $bloqueos);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: registrar o actualizar politica POS de inventario pendiente por sucursal/SKU/canal.
     * Impacto: habilita una regla operativa controlada para permitir faltantes en POS; no crea ventas ni mueve inventario.
     * Contrato: escritura transaccional invocada solo desde controlador protegido con token/respaldo.
     */
    public function guardarPoliticaInventarioPendientePosReal($datos = array()) {
        try {
            $db = $this->getConexion();
            if (!$this->tablaExiste($db, "erp_pos_politicas_venta_inventario")) {
                return $this->respuesta(true, "warning", "Falta DDL de politicas POS para inventario pendiente");
            }
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idSku = intval($this->valor($datos, "id_sku", $this->valor($datos, "id_sku_erp", 0)));
            $canal = trim((string) $this->valor($datos, "canal", "pos"));
            $cantidadMaxima = round(floatval($this->valor($datos, "cantidad_maxima", $this->valor($datos, "cantidad_maxima_pendiente", 0))), 6);
            $montoMaximo = round(floatval($this->valor($datos, "monto_maximo", 0)), 6);
            $motivo = trim((string) $this->valor($datos, "motivo", "Politica UAT inventario pendiente POS"));
            if (!in_array($canal, array("pos", "pedido_tienda"), true)) {
                $canal = "pos";
            }
            if ($idUsuario <= 0 || $idAlmacen <= 0 || $idSku <= 0 || $cantidadMaxima <= 0) {
                return $this->respuesta(true, "warning", "Usuario, almacen, SKU y cantidad maxima son obligatorios para politica POS");
            }
            if ($this->consultarSkuVenta($db, $idSku) === false) {
                return $this->respuesta(true, "warning", "SKU no encontrado o inactivo para politica POS");
            }
            $codigo = trim((string) $this->valor($datos, "codigo", ""));
            if ($codigo === "") {
                $codigo = "PINV-UAT-A" . $idAlmacen . "-S" . $idSku . "-" . strtoupper($canal);
            }
            $nombre = trim((string) $this->valor($datos, "nombre", ""));
            if ($nombre === "") {
                $nombre = "Politica UAT inventario pendiente POS SKU " . $idSku;
            }

            $snapshot = array(
                "origen" => "pos_inventario_pendiente",
                "id_usuario" => $idUsuario,
                "id_almacen" => $idAlmacen,
                "id_sku" => $idSku,
                "canal" => $canal,
                "cantidad_maxima_pendiente" => $cantidadMaxima,
                "monto_maximo" => $montoMaximo,
                "motivo" => $motivo,
                "fecha" => date("Y-m-d H:i:s")
            );

            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id_politica_inventario_pos FROM erp_pos_politicas_venta_inventario WHERE codigo=:codigo LIMIT 1");
            $stmt->execute(array(":codigo" => $codigo));
            $idPolitica = intval($stmt->fetchColumn());
            if ($idPolitica > 0) {
                $stmt = $db->prepare("UPDATE erp_pos_politicas_venta_inventario
                    SET nombre=:nombre, id_almacen=:almacen, id_sku_erp=:sku, canal=:canal,
                        permite_inventario_pendiente=1, cantidad_maxima_pendiente=:cantidad, monto_maximo=:monto,
                        requiere_autorizacion=1, permiso_requerido='ventas.pos.inventario_pendiente.autorizar',
                        motivo_obligatorio=1, estatus='activa', autorizado_por=:usuario, fecha_autorizacion=NOW(),
                        observaciones=:motivo, datos_snapshot=:snapshot, fecha_actualizacion=NOW()
                    WHERE id_politica_inventario_pos=:id");
                $stmt->execute(array(
                    ":nombre" => $nombre,
                    ":almacen" => $idAlmacen,
                    ":sku" => $idSku,
                    ":canal" => $canal,
                    ":cantidad" => $cantidadMaxima,
                    ":monto" => $montoMaximo,
                    ":usuario" => $idUsuario,
                    ":motivo" => $motivo,
                    ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ":id" => $idPolitica
                ));
                $accion = "actualizada";
            } else {
                $stmt = $db->prepare("INSERT INTO erp_pos_politicas_venta_inventario
                    (codigo, nombre, id_almacen, id_sku_erp, canal, permite_inventario_pendiente,
                     cantidad_maxima_pendiente, monto_maximo, requiere_autorizacion, permiso_requerido,
                     motivo_obligatorio, estatus, creado_por, autorizado_por, fecha_autorizacion, observaciones, datos_snapshot)
                    VALUES
                    (:codigo, :nombre, :almacen, :sku, :canal, 1,
                     :cantidad, :monto, 1, 'ventas.pos.inventario_pendiente.autorizar',
                     1, 'activa', :usuario, :usuario_autoriza, NOW(), :motivo, :snapshot)");
                $stmt->execute(array(
                    ":codigo" => $codigo,
                    ":nombre" => $nombre,
                    ":almacen" => $idAlmacen,
                    ":sku" => $idSku,
                    ":canal" => $canal,
                    ":cantidad" => $cantidadMaxima,
                    ":monto" => $montoMaximo,
                    ":usuario" => $idUsuario,
                    ":usuario_autoriza" => $idUsuario,
                    ":motivo" => $motivo,
                    ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ));
                $idPolitica = intval($db->lastInsertId());
                $accion = "creada";
            }
            $db->commit();
            return $this->respuesta(false, "success", "Politica POS de inventario pendiente " . $accion, array(
                "id_politica_inventario_pos" => $idPolitica,
                "codigo" => $codigo,
                "id_almacen" => $idAlmacen,
                "id_sku" => $idSku,
                "canal" => $canal,
                "cantidad_maxima_pendiente" => $cantidadMaxima,
                "monto_maximo" => $montoMaximo,
                "accion" => $accion
            ));
        } catch (Exception $e) {
            if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo guardar politica POS de inventario pendiente", array("error" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: registrar venta POS real con inventario pendiente autorizado por politica de sucursal/SKU/canal.
     * Impacto: crea venta, detalle, pago, movimiento de caja, expediente pendiente y evento; no descuenta stock inexistente ni ajusta inventario.
     * Contrato: transaccional; requiere turno abierto y pago completo. Si falta caja/turno/pago no escribe datos.
     */
    public function ventaInventarioPendienteReal($datos = array()) {
        $db = null;
        try {
            $db = $this->getConexion();
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idSku = intval($this->valor($datos, "id_sku", 0));
            $idAlmacenSolicitado = intval($this->valor($datos, "id_almacen", 0));
            $cantidad = round(floatval($this->valor($datos, "cantidad", 0)), 6);
            $motivo = trim((string) $this->valor($datos, "motivo", "Venta POS con inventario pendiente"));
            $pagoSolicitado = $this->redondearPosReal($this->valor($datos, "pago", 0));
            $idMetodoPago = intval($this->valor($datos, "id_metodo_pago", 1));
            $clienteNombre = trim((string) $this->valor($datos, "cliente", $this->valor($datos, "cliente_nombre_publico", "Cliente mostrador")));

            if ($idUsuario <= 0 || $idSku <= 0 || $idAlmacenSolicitado <= 0 || $cantidad <= 0) {
                return $this->respuesta(true, "warning", "Usuario, almacen, SKU y cantidad son obligatorios para venta con inventario pendiente");
            }
            if (!$this->tablaExiste($db, "erp_pos_inventario_pendientes") || !$this->tablaExiste($db, "erp_pos_inventario_pendientes_eventos") || !$this->tablaExiste($db, "erp_pos_politicas_venta_inventario")) {
                return $this->respuesta(true, "warning", "Falta esquema POS de inventario pendiente");
            }

            $dryRun = $this->ventaInventarioPendienteDryRun(array(
                "id_sku" => $idSku,
                "id_almacen" => $idAlmacenSolicitado,
                "cantidad" => $cantidad,
                "canal" => "pos",
                "motivo" => $motivo
            ));
            $dry = isset($dryRun["depurar"]) && is_array($dryRun["depurar"]) ? $dryRun["depurar"] : array();
            if (!empty($dryRun["error"]) || $this->valor($dry, "estado", "") !== "pendiente_autorizable" || !empty($dry["bloqueos"])) {
                return $this->respuesta(false, "warning", "Venta POS con inventario pendiente bloqueada por prevalidacion", array(
                    "bloqueos" => $this->valor($dry, "bloqueos", array()),
                    "dry_run" => $dry
                ));
            }

            $asignacion = $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
            $depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
            $datosAsignacion = isset($depurarAsignacion["asignacion"]) && is_array($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();
            $turno = isset($depurarAsignacion["turno_abierto"]) && is_array($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();
            if (empty($datosAsignacion)) {
                return $this->respuesta(false, "warning", "No hay asignacion POS activa para el usuario", array("bloqueos" => array("asignacion_pos_pendiente")));
            }
            if (intval($this->valor($datosAsignacion, "id_almacen", 0)) !== $idAlmacenSolicitado) {
                return $this->respuesta(false, "warning", "La asignacion POS del usuario no corresponde al almacen solicitado", array(
                    "bloqueos" => array("almacen_asignacion_no_coincide"),
                    "asignacion" => $datosAsignacion
                ));
            }
            if (empty($turno)) {
                return $this->respuesta(false, "warning", "No hay turno abierto para la caja asignada", array("bloqueos" => array("turno_abierto_pendiente"), "asignacion" => $datosAsignacion));
            }

            $precio = $this->redondearPosReal($this->valorRutaPosReal($dry, array("precio", "precio_aplicado"), 0));
            $total = $this->redondearPosReal($cantidad * $precio);
            if ($total <= 0) {
                return $this->respuesta(true, "warning", "Precio backend invalido para venta con inventario pendiente", array("dry_run" => $dry));
            }
            if ($pagoSolicitado <= 0) {
                return $this->respuesta(false, "warning", "Pago obligatorio para UAT real de venta con inventario pendiente", array(
                    "bloqueos" => array("pago_obligatorio"),
                    "total_requerido" => $total
                ));
            }
            if (abs($pagoSolicitado - $total) > 0.000001) {
                return $this->respuesta(false, "warning", "El pago debe cubrir exactamente el total para esta UAT", array(
                    "bloqueos" => array("pago_no_cuadra"),
                    "total_requerido" => $total,
                    "pago_recibido" => $pagoSolicitado
                ));
            }
            $metodosPago = $this->metodosPagoIndexados($db);
            $metodo = isset($metodosPago[$idMetodoPago]) ? $metodosPago[$idMetodoPago] : null;
            if (!$metodo) {
                return $this->respuesta(false, "warning", "Metodo de pago invalido para venta POS", array("bloqueos" => array("metodo_pago_invalido")));
            }

            $idAlmacen = intval($this->valor($datosAsignacion, "id_almacen", 0));
            $idCaja = intval($this->valor($datosAsignacion, "id_caja", 0));
            $idTurno = intval($this->valor($turno, "id_turno_caja", 0));
            $sku = $this->valor($dry, "sku", array());
            $politica = $this->valorRutaPosReal($dry, array("politica", "politica_pos"), array());
            $cantidadCubierta = round(floatval($this->valor($dry, "cantidad_cubierta", 0)), 6);
            $cantidadPendiente = round(floatval($this->valor($dry, "cantidad_pendiente", 0)), 6);

            $db->beginTransaction();
            $turnoBloqueado = $this->bloquearTurnoPosReal($db, $idTurno, $idCaja, $idAlmacen);
            if (!$turnoBloqueado) {
                throw new Exception("El turno ya no esta abierto para la caja asignada");
            }

            $folio = $this->generarFolioVentaPosReal($db, "POS");
            $stmt = $db->prepare("INSERT INTO erp_ventas
                (folio, canal, tipo_documento, estatus, inventario_validacion_estado, id_almacen, id_caja, id_turno_caja,
                 id_cliente, cliente_nombre_publico, subtotal, descuento_total, impuestos_total, total,
                 pagado_total, saldo_total, inventario_pendiente_total, creado_por, observaciones)
                VALUES (:folio, 'pos', 'venta', 'pagada', 'pendiente_inventario', :almacen, :caja, :turno,
                 NULL, :cliente, :subtotal, 0, 0, :total, :pagado, 0, :pendiente_total, :usuario, :observaciones)");
            $stmt->execute(array(
                ":folio" => $folio,
                ":almacen" => $idAlmacen,
                ":caja" => $idCaja,
                ":turno" => $idTurno,
                ":cliente" => $clienteNombre !== "" ? $clienteNombre : "Cliente mostrador",
                ":subtotal" => $total,
                ":total" => $total,
                ":pagado" => $total,
                ":pendiente_total" => $cantidadPendiente,
                ":usuario" => $idUsuario,
                ":observaciones" => $motivo
            ));
            $idVenta = intval($db->lastInsertId());

            $stmt = $db->prepare("INSERT INTO erp_ventas_detalle
                (id_venta, renglon, id_producto_erp, id_sku_erp, sku, descripcion,
                 tipo_partida, controla_inventario, modo_salida, inventario_estado, permite_inventario_pendiente,
                 cantidad_venta, unidad_venta, cantidad_base, cantidad_inventario_pendiente, unidad_base,
                 precio_unitario, precio_unitario_sin_impuesto, precio_base, precio_aplicado,
                 id_lista_precio, lista_precio_snapshot, regla_precio_origen, descuento, impuestos, subtotal, total, estatus)
                VALUES (:venta, 1, :producto, :sku_id, :sku, :descripcion,
                 'producto', 1, 'inventario_pendiente_pos', 'pendiente_inventario', 1,
                 :cantidad, :unidad, :cantidad_base, :cantidad_pendiente, :unidad_base,
                 :precio, :precio, :precio_base, :precio_aplicado,
                 :lista_id, :lista_snapshot, :regla_precio, 0, 0, :subtotal, :total, 'confirmada')");
            $stmt->execute(array(
                ":venta" => $idVenta,
                ":producto" => intval($this->valor($sku, "id_producto_erp", 0)),
                ":sku_id" => $idSku,
                ":sku" => $this->valor($sku, "sku", ""),
                ":descripcion" => $this->valor($sku, "nombre_sku", $this->valor($sku, "producto", "")),
                ":cantidad" => $cantidad,
                ":unidad" => $this->valor($sku, "unidad_venta_label", ""),
                ":cantidad_base" => $cantidad,
                ":cantidad_pendiente" => $cantidadPendiente,
                ":unidad_base" => $this->valor($sku, "unidad_venta_label", ""),
                ":precio" => $precio,
                ":precio_base" => $this->redondearPosReal($this->valorRutaPosReal($dry, array("precio", "precio_base"), $precio)),
                ":precio_aplicado" => $precio,
                ":lista_id" => $this->valorRutaPosReal($dry, array("precio", "id_lista_precio"), null),
                ":lista_snapshot" => $this->valorRutaPosReal($dry, array("precio", "lista_precio_snapshot"), null),
                ":regla_precio" => $this->valorRutaPosReal($dry, array("precio", "regla_precio_origen"), "catalogo_general"),
                ":subtotal" => $total,
                ":total" => $total
            ));
            $idDetalle = intval($db->lastInsertId());

            $folioPendiente = $this->generarFolioInventarioPendientePosReal($db);
            $snapshot = array(
                "venta" => array("id_venta" => $idVenta, "folio" => $folio),
                "politica" => $politica,
                "dry_run" => array(
                    "cantidad_cubierta" => $cantidadCubierta,
                    "cantidad_pendiente" => $cantidadPendiente,
                    "disponible_actual" => $this->valor($dry, "disponible_actual", 0)
                )
            );
            $stmt = $db->prepare("INSERT INTO erp_pos_inventario_pendientes
                (folio, id_venta, id_venta_detalle, id_almacen, id_sku_erp, sku, descripcion,
                 cantidad_vendida, cantidad_cubierta, cantidad_pendiente, unidad_base,
                 precio_unitario_snapshot, estatus, prioridad, origen, politica_snapshot, datos_snapshot, creado_por)
                VALUES (:folio, :venta, :detalle, :almacen, :sku_id, :sku, :descripcion,
                 :cantidad_vendida, :cantidad_cubierta, :cantidad_pendiente, :unidad,
                 :precio, 'pendiente_revision', 'alta', 'pos_venta', :politica, :snapshot, :usuario)");
            $stmt->execute(array(
                ":folio" => $folioPendiente,
                ":venta" => $idVenta,
                ":detalle" => $idDetalle,
                ":almacen" => $idAlmacen,
                ":sku_id" => $idSku,
                ":sku" => $this->valor($sku, "sku", ""),
                ":descripcion" => $this->valor($sku, "nombre_sku", $this->valor($sku, "producto", "")),
                ":cantidad_vendida" => $cantidad,
                ":cantidad_cubierta" => $cantidadCubierta,
                ":cantidad_pendiente" => $cantidadPendiente,
                ":unidad" => $this->valor($sku, "unidad_venta_label", ""),
                ":precio" => $precio,
                ":politica" => json_encode($politica, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ":usuario" => $idUsuario
            ));
            $idPendiente = intval($db->lastInsertId());

            $db->prepare("UPDATE erp_ventas_detalle
                SET id_inventario_pendiente=:pendiente
                WHERE id_venta_detalle=:detalle")
                ->execute(array(":pendiente" => $idPendiente, ":detalle" => $idDetalle));
            $db->prepare("INSERT INTO erp_ventas_detalle_inventario
                (id_venta, id_venta_detalle, tipo_asignacion, id_almacen, cantidad_base,
                 cantidad_pendiente_validacion, id_inventario_pendiente, estatus)
                VALUES (:venta, :detalle, 'inventario_pendiente', :almacen, 0,
                 :cantidad_pendiente, :pendiente, 'pendiente_validacion')")
                ->execute(array(
                    ":venta" => $idVenta,
                    ":detalle" => $idDetalle,
                    ":almacen" => $idAlmacen,
                    ":cantidad_pendiente" => $cantidadPendiente,
                    ":pendiente" => $idPendiente
                ));
            $db->prepare("INSERT INTO erp_pos_inventario_pendientes_eventos
                (id_inventario_pendiente, tipo_evento, estatus_anterior, estatus_nuevo,
                 cantidad, referencia, observaciones, datos_snapshot, creado_por)
                VALUES (:pendiente, 'creacion_pos', NULL, 'pendiente_revision',
                 :cantidad, :referencia, :observaciones, :snapshot, :usuario)")
                ->execute(array(
                    ":pendiente" => $idPendiente,
                    ":cantidad" => $cantidadPendiente,
                    ":referencia" => $folio,
                    ":observaciones" => $motivo,
                    ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ":usuario" => $idUsuario
                ));

            $pagos = array(array(
                "id_metodo_pago" => $idMetodoPago,
                "metodo_pago" => $this->valor($metodo, "metodo_pago", "Efectivo"),
                "monto" => $total,
                "referencia" => $this->valor($datos, "referencia_pago", $folio)
            ));
            $evidenciaPagos = $this->registrarPagosPosReal($db, $idVenta, $folio, array(
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno
            ), $pagos, $total, $idUsuario);
            $db->prepare("UPDATE erp_pos_turnos
                SET monto_esperado=ROUND(monto_esperado+:monto, 6)
                WHERE id_turno_caja=:turno")
                ->execute(array(":monto" => $total, ":turno" => $idTurno));

            $db->commit();
            return $this->respuesta(false, "success", "Venta POS con inventario pendiente registrada", array(
                "folio" => $folio,
                "id_venta" => $idVenta,
                "id_venta_detalle" => $idDetalle,
                "folio_pendiente" => $folioPendiente,
                "id_inventario_pendiente" => $idPendiente,
                "id_turno_caja" => $idTurno,
                "id_caja" => $idCaja,
                "id_almacen" => $idAlmacen,
                "total" => $total,
                "cantidad_pendiente" => $cantidadPendiente,
                "pagos" => $evidenciaPagos
            ));
        } catch (Exception $e) {
            if ($db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo registrar venta POS con inventario pendiente", array(
                "error" => $e->getMessage(),
                "rollback" => true
            ));
        }
    }
    /**
     * Documentacion IA: Codex GPT-5, 2026-07-10.
     * Proposito: consultar precio, imagen y disponibilidad para checador POS/celular.
     * Impacto: reutiliza Catalogo/Inventario/Ventas como fuente de verdad sin crear ventas ni reservas.
     * Contrato: read-only; el precio y disponibilidad son informativos y deben revalidarse al cobrar.
     */
    public function checadorPrecioPosReadOnly($filtros = array()) {
        try {
            $db = $this->getConexion();
            $termino = trim((string) $this->valor($filtros, "q", ""));
            $idSku = intval($this->valor($filtros, "id_sku", 0));
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            $canal = trim((string) $this->valor($filtros, "canal", "pos"));
            if (!in_array($canal, array("pos", "pedido_tienda", "ecommerce"), true)) {
                $canal = "pos";
            }
            if ($idSku <= 0 && strlen($termino) < 2) {
                return $this->respuesta(false, "success", "Escanea o escribe al menos dos caracteres", array(
                    "producto" => null,
                    "coincidencias" => array(),
                    "modo" => "esperando_busqueda"
                ));
            }

            $coincidencias = $idSku > 0 ? array() : $this->buscarSkusParaChecador($db, $termino, $idAlmacen, 8);
            if ($idSku <= 0 && !empty($coincidencias)) {
                $idSku = intval($coincidencias[0]["id_sku"]);
            }
            if ($idSku <= 0) {
                return $this->respuesta(false, "warning", "Producto no encontrado", array(
                    "producto" => null,
                    "coincidencias" => array(),
                    "modo" => "sin_resultados"
                ));
            }

            $sku = $this->consultarSkuVenta($db, $idSku);
            if (!$sku) {
                return $this->respuesta(true, "warning", "SKU no encontrado o no activo");
            }
            $visual = $this->consultarVisualSkuChecador($db, $idSku);
            $schemaListasPendiente = !$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle");
            $precio = $this->resolverPrecioSkuDryRun($db, $sku, array(), $canal, $idAlmacen, $schemaListasPendiente);
            $existencias = $this->existenciasDisponiblesVenta($db, $idSku, $idAlmacen);
            $unidades = $this->unidadesDisponiblesVenta($db, $idSku, $idAlmacen);
            $resumen = $this->resumenDisponibilidad($existencias, $unidades);

            $estadoPublico = "agotado";
            if (intval($sku["controla_inventario"]) !== 1) {
                $estadoPublico = "sin_control_inventario";
            } elseif (floatval($resumen["disponible"]) > 3) {
                $estadoPublico = "disponible";
            } elseif (floatval($resumen["disponible"]) > 0) {
                $estadoPublico = "pocas_piezas";
            } elseif (intval($sku["permite_venta_sin_existencia"]) === 1) {
                $estadoPublico = "consultar_disponibilidad";
            }

            $producto = array_merge($sku, $visual, array(
                "precio_base" => $precio["precio_base"],
                "precio_aplicado" => $precio["precio_aplicado"],
                "id_lista_precio" => $precio["id_lista_precio"],
                "lista_precio_snapshot" => $precio["lista_precio_snapshot"],
                "regla_precio_origen" => $precio["regla_precio_origen"],
                "schema_listas_pendiente" => !empty($precio["schema_listas_pendiente"]),
                "disponibilidad" => $resumen,
                "estado_publico" => $estadoPublico,
                "id_almacen_consulta" => $idAlmacen,
                "canal" => $canal
            ));

            return $this->respuesta(false, "success", "Producto consultado", array(
                "producto" => $producto,
                "coincidencias" => $coincidencias,
                "existencias" => $existencias,
                "unidades" => $unidades,
                "modo" => "read_only",
                "contrato" => array(
                    "no_cobra" => true,
                    "no_reserva" => true,
                    "no_mueve_inventario" => true,
                    "revalidar_al_cobrar" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: consultar una etiqueta/unidad fisica para decidir si POS puede venderla cerrada o a granel.
     * Impacto: evita que el POS trate una unidad abierta como cerrada vendible.
     * Contrato: solo lectura; devuelve dictamen operativo.
     */
    public function disponibilidadUnidad($filtros = array()) {
        try {
            $db = $this->getConexion();
            $clave = trim((string) $this->valor($filtros, "clave", ""));
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            if ($clave === "") {
                return $this->respuesta(true, "warning", "Etiqueta o serie obligatoria");
            }

            $sql = "SELECT u.id_inventario_unidad, u.codigo_unico, u.codigo_etiqueta_interna,
                    u.serie_fabricante, u.id_producto, u.id_sku_erp id_sku, s.sku,
                    COALESCE(s.nombre, p.nombre) producto, u.id_existencia_inventario,
                    u.id_almacen, a.almacen, u.lote, u.fecha_caducidad,
                    u.cantidad_base_original, u.cantidad_base_disponible, u.unidad_base,
                    u.estatus, u.estado_etiqueta, u.estado_fisico,
                    COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
                    COALESCE(r.precision_decimal, 0) precision_decimal,
                    COALESCE(r.incremento_minimo_venta, 1.000000) incremento_minimo_venta
                FROM erp_inventario_unidades u
                INNER JOIN erp_catalogo_skus s ON s.id_sku=u.id_sku_erp
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_almacenes a ON a.id_almacen=u.id_almacen
                LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
                WHERE (:almacen=0 OR u.id_almacen=:almacen_filtro)
                  AND (u.codigo_unico=:clave OR u.codigo_etiqueta_interna=:clave_etiqueta OR u.serie_fabricante=:clave_serie)
                LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":almacen" => $idAlmacen,
                ":almacen_filtro" => $idAlmacen,
                ":clave" => $clave,
                ":clave_etiqueta" => $clave,
                ":clave_serie" => $clave
            ));
            $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$unidad) {
                return $this->respuesta(true, "warning", "Unidad no encontrada");
            }
            $unidad["dictamen_pos"] = $this->dictamenUnidadPos($unidad);
            return $this->respuesta(false, "success", "Unidad consultada", $unidad);
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: prevalidar un carrito POS sin cobrar, reservar ni descontar inventario.
     * Impacto: permite probar reglas de cantidad, granel y stock antes de implementar transacciones reales.
     * Contrato: read-only; los resultados no garantizan disponibilidad futura sin revalidacion transaccional.
     */
    public function prevalidarCarritoPos($datos = array()) {
        try {
            $db = $this->getConexion();
            if (!$db) {
                return $this->respuesta(true, "warning", "Conexion de base de datos no disponible", array(
                    "bloqueos" => array("conexion_bd_no_disponible"),
                    "contrato" => array(
                        "no_crea_venta" => true,
                        "no_registra_pago" => true,
                        "no_mueve_inventario" => true
                    )
                ));
            }
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $canal = trim((string) $this->valor($datos, "canal", "pos"));
            $idCliente = intval($this->valor($datos, "id_cliente", 0));
            $identificadorCliente = trim((string) $this->valor($datos, "identificador_cliente", ""));
            $items = $this->decodificarItems($this->valor($datos, "items", array()));
            $pagos = $this->decodificarItems($this->valor($datos, "pagos", array()));
            $exigirPagoCompleto = intval($this->valor($datos, "exigir_pago_completo", 1)) === 1;
            if ($idAlmacen <= 0) {
                return $this->respuesta(true, "warning", "Selecciona almacen/punto de venta");
            }
            if (empty($items)) {
                return $this->respuesta(true, "warning", "Agrega partidas al carrito");
            }

            $partidas = array();
            $bloqueos = array();
            $bloqueosOperativos = $this->validarCajaOperativa($db, $idAlmacen, $idCaja);
            $bloqueosOperativos = array_merge($bloqueosOperativos, $this->validarTurnoOperativo($db, $idAlmacen, $idCaja, $idTurno));
            if (!empty($bloqueosOperativos)) {
                $bloqueos = array_merge($bloqueos, $bloqueosOperativos);
            }
            $schemaClientesPendiente = !$this->schemaClientesCrmDisponible($db);
            $schemaListasPendiente = !$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle");
            $cliente = $this->resolverClienteDryRun($db, $idCliente, $identificadorCliente, $schemaClientesPendiente);
            $subtotal = 0;
            foreach ($items as $indice => $item) {
                $validacion = $this->prevalidarPartida($db, $item, $idAlmacen, $indice + 1, $cliente, $canal, $schemaListasPendiente);
                $partidas[] = $validacion;
                if (!empty($validacion["bloqueos"])) {
                    $bloqueos = array_merge($bloqueos, $validacion["bloqueos"]);
                }
                $subtotal += floatval($validacion["subtotal"]);
            }
            $bloqueosAcumulado = $this->validarPlanSalidaAcumuladoPos($partidas);
            if (!empty($bloqueosAcumulado)) {
                $bloqueos = array_merge($bloqueos, $bloqueosAcumulado);
            }
            $validacionPagos = $this->prevalidarPagosPos($db, $pagos, $subtotal, $exigirPagoCompleto);
            if (!empty($validacionPagos["bloqueos"])) {
                $bloqueos = array_merge($bloqueos, $validacionPagos["bloqueos"]);
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Carrito prevalidado" : "Carrito con bloqueos", array(
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "canal" => $canal,
                "cliente" => $cliente,
                "partidas" => $partidas,
                "pagos" => $validacionPagos["pagos"],
                "bloqueos" => $bloqueos,
                "bloqueos_operativos" => $bloqueosOperativos,
                "totales" => array(
                    "subtotal" => round($subtotal, 6),
                    "total_estimado" => round($subtotal, 6),
                    "pagado_total" => $validacionPagos["pagado_total"],
                    "saldo_total" => $validacionPagos["saldo_total"],
                    "cambio" => $validacionPagos["cambio"]
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: validar el contrato completo de confirmacion POS sin ejecutar escrituras.
     * Impacto: deja listo el punto de integracion para venta real con folio, pagos, caja, kardex y trazabilidad.
     * Contrato: dry-run; no inserta venta, no registra pagos, no reserva y no mueve inventario.
     */
    public function confirmarVentaPosDryRun($datos = array()) {
        $datos["exigir_pago_completo"] = 0;
        $prevalidacion = $this->prevalidarCarritoPos($datos);
        $schemaPendiente = !$this->schemaVentaPosCompleto($this->getConexion());
        $bloqueos = array();
        if (isset($prevalidacion["depurar"]["bloqueos"]) && is_array($prevalidacion["depurar"]["bloqueos"])) {
            $bloqueos = $prevalidacion["depurar"]["bloqueos"];
        }
        if ($schemaPendiente) {
            $bloqueos[] = "Esquema Ventas/POS pendiente de autorizacion y respaldo externo";
        }
        return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de venta valido" : "Dry-run bloqueado", array(
            "dry_run" => true,
            "schema_pendiente" => $schemaPendiente,
            "prevalidacion" => $prevalidacion,
            "bloqueos" => $bloqueos,
            "contrato_confirmacion" => array(
                "requiere_id_almacen" => true,
                "requiere_id_caja" => true,
                "requiere_id_turno_caja" => true,
                "requiere_folio_erp" => true,
                "requiere_kardex" => true,
                "requiere_trazabilidad_detalle_inventario" => true
            )
        ));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: confirmar una venta POS real desde la UI con transaccion, caja, inventario y trazabilidad.
     * Impacto: crea venta ERP, detalle, pagos, movimiento de caja, kardex, snapshots de garantia y consume excepcion comercial si aplica.
     * Contrato: escritura real; requiere usuario, almacen, caja, turno abierto, pago completo y revalidacion backend.
     */
    public function confirmarVentaPosReal($datos = array()) {
        require_once __DIR__ . "/GarantiasErp.php";

        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $idAtencion = intval($this->valor($datos, "id_atencion", 0));
        $folioExcepcion = trim((string) $this->valor($datos, "folio_excepcion", ""));
        $clienteNombre = trim((string) $this->valor($datos, "cliente_nombre_publico", $this->valor($datos, "cliente", "Cliente mostrador")));
        $observaciones = trim((string) $this->valor($datos, "observaciones", "Venta POS UI"));

        if ($idUsuario <= 0) {
            return $this->respuesta(true, "warning", "Usuario operador obligatorio", array("bloqueos" => array("usuario_obligatorio")));
        }
        if (!$this->schemaVentaPosCompleto($db)) {
            return $this->respuesta(true, "warning", "Esquema Ventas/POS pendiente", array("bloqueos" => array("schema_ventas_pos_pendiente")));
        }

        $asignacion = $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
        $depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
        $datosAsignacion = isset($depurarAsignacion["asignacion"]) && is_array($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();
        $turno = isset($depurarAsignacion["turno_abierto"]) && is_array($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();

        if (empty($datosAsignacion)) {
            return $this->respuesta(true, "warning", "No hay asignacion POS activa para el usuario", array("bloqueos" => array("asignacion_pos_pendiente")));
        }
        if (empty($turno)) {
            return $this->respuesta(true, "warning", "No hay turno abierto para la caja asignada", array("bloqueos" => array("turno_abierto_pendiente")));
        }

        $datosVenta = array(
            "id_almacen" => intval($this->valor($datosAsignacion, "id_almacen", 0)),
            "id_caja" => intval($this->valor($datosAsignacion, "id_caja", 0)),
            "id_turno_caja" => intval($this->valor($turno, "id_turno_caja", 0)),
            "canal" => "pos",
            "id_cliente" => intval($this->valor($datos, "id_cliente", 0)),
            "identificador_cliente" => trim((string) $this->valor($datos, "identificador_cliente", "")),
            "items" => $this->valor($datos, "items", array()),
            "pagos" => $this->valor($datos, "pagos", array()),
            "exigir_pago_completo" => 1
        );

        $atencionOrigen = null;
        if ($idAtencion > 0) {
            $atencionOrigen = $this->cargarAtencionPosReal($db, $idAtencion);
            if (!$atencionOrigen) {
                return $this->respuesta(true, "warning", "Atencion POS no encontrada", array("bloqueos" => array("atencion_no_encontrada")));
            }
            $datosVenta["items"] = json_encode($this->valorRutaPosReal($atencionOrigen, array("items"), array()));
            $datosVenta["id_cliente"] = intval($this->valorRutaPosReal($atencionOrigen, array("atencion", "id_cliente"), 0));
            $datosVenta["identificador_cliente"] = trim((string) $this->valorRutaPosReal($atencionOrigen, array("atencion", "cliente_identificador_publico"), ""));
            $clienteNombre = trim((string) $this->valorRutaPosReal($atencionOrigen, array("atencion", "cliente_nombre_publico"), $clienteNombre));
            $observaciones = "Cobro POS UI de atencion " . $this->valorRutaPosReal($atencionOrigen, array("atencion", "folio_temporal"), $idAtencion);
        }

        $consumoExcepcion = null;
        if ($folioExcepcion !== "") {
            $datosExcepcion = $datosVenta;
            $datosExcepcion["folio_excepcion"] = $folioExcepcion;
            $datosExcepcion["id_usuario"] = $idUsuario;
            $consumoExcepcion = $this->excepcionComercialConsumoDryRun($datosExcepcion);
            $prevalidacion = $this->valorRutaPosReal($consumoExcepcion, array("depurar", "prevalidacion_base"), array());
        } else {
            $prevalidacion = $this->prevalidarCarritoPos($datosVenta);
        }
        $confirmacion = $folioExcepcion !== ""
            ? $consumoExcepcion
            : $this->confirmarVentaPosDryRun($datosVenta);
        $clientePrecio = $this->clientePrecioDryRun(array(
            "id_almacen" => $datosVenta["id_almacen"],
            "canal" => "pos",
            "id_cliente" => $datosVenta["id_cliente"],
            "identificador_cliente" => $datosVenta["identificador_cliente"],
            "items" => $datosVenta["items"]
        ));

        $bloqueos = array();
        foreach (array(
            $this->valorRutaPosReal($asignacion, array("depurar", "bloqueos"), array()),
            $this->valorRutaPosReal($prevalidacion, array("depurar", "bloqueos"), array()),
            $this->valorRutaPosReal($confirmacion, array("depurar", "bloqueos"), array()),
            $this->valorRutaPosReal($clientePrecio, array("depurar", "bloqueos"), array())
        ) as $listaBloqueos) {
            foreach ($listaBloqueos as $bloqueo) {
                $bloqueos[] = $bloqueo;
            }
        }
        if (!empty(array_unique($bloqueos))) {
            return $this->respuesta(false, "warning", "Venta POS real bloqueada por prevalidacion", array(
                "bloqueos" => array_values(array_unique($bloqueos)),
                "contexto" => array(
                    "id_usuario" => $idUsuario,
                    "id_almacen" => $datosVenta["id_almacen"],
                    "id_caja" => $datosVenta["id_caja"],
                    "id_turno_caja" => $datosVenta["id_turno_caja"]
                )
            ));
        }

        $partidas = $folioExcepcion !== ""
            ? $this->valorRutaPosReal($consumoExcepcion, array("depurar", "partidas"), array())
            : $this->valorRutaPosReal($prevalidacion, array("depurar", "partidas"), array());
        $partidasPrecio = $this->indexarPreciosPorRenglonPosReal($this->valorRutaPosReal($clientePrecio, array("depurar", "partidas"), array()));
        $pagosPrevalidados = $this->valorRutaPosReal($prevalidacion, array("depurar", "pagos"), array());
        $totales = $this->valorRutaPosReal($prevalidacion, array("depurar", "totales"), array());
        if ($folioExcepcion !== "") {
            $totalesExcepcion = $this->valorRutaPosReal($consumoExcepcion, array("depurar", "totales"), array());
            $totales = array(
                "subtotal" => $this->valorRutaPosReal($totalesExcepcion, array("subtotal_original"), 0),
                "descuento_total" => $this->valorRutaPosReal($totalesExcepcion, array("descuento_total"), 0),
                "total_estimado" => $this->valorRutaPosReal($totalesExcepcion, array("total_con_excepcion"), 0),
                "pagado_total" => $this->valorRutaPosReal($totalesExcepcion, array("pagado_total"), 0),
                "saldo_total" => $this->valorRutaPosReal($totalesExcepcion, array("saldo_total"), 0),
                "cambio" => $this->valorRutaPosReal($totalesExcepcion, array("cambio"), 0)
            );
        }

        try {
            $db->beginTransaction();

            if ($idAtencion > 0) {
                $atencionBloqueada = $this->bloquearAtencionPosReal($db, $idAtencion);
                if (!$atencionBloqueada || !in_array($atencionBloqueada["estatus"], array("abierta", "lista_para_cobro", "tomada_por_caja"), true)) {
                    throw new Exception("La atencion ya no esta disponible para cobro");
                }
            }

            $turnoBloqueado = $this->bloquearTurnoPosReal($db, $datosVenta["id_turno_caja"], $datosVenta["id_caja"], $datosVenta["id_almacen"]);
            if (!$turnoBloqueado) {
                throw new Exception("El turno ya no esta abierto para la caja asignada");
            }

            $excepcionBloqueada = null;
            if ($folioExcepcion !== "") {
                $excepcionBloqueada = $this->bloquearExcepcionComercialPosReal($db, $folioExcepcion);
                if (!$excepcionBloqueada) {
                    throw new Exception("Excepcion comercial no encontrada al confirmar venta POS");
                }
                if ($excepcionBloqueada["estatus"] !== "autorizada" || intval($excepcionBloqueada["id_venta"]) > 0 || intval($excepcionBloqueada["id_venta_detalle"]) > 0) {
                    throw new Exception("La excepcion comercial ya no esta disponible para consumo");
                }
            }

            $idClienteVenta = intval($datosVenta["id_cliente"]);
            $clienteVenta = $clienteNombre !== "" ? $clienteNombre : "Cliente mostrador";
            $identificadorClienteVenta = $datosVenta["identificador_cliente"];
            $idClienteCrmVenta = 0;
            $clienteCodigoSnapshot = "";
            $clienteOrigenSnapshot = "";
            $clienteSnapshot = null;
            if ($excepcionBloqueada && intval($this->valor($excepcionBloqueada, "id_cliente"), 0) > 0) {
                $clienteExcepcion = $this->consultarClienteErpPosReal($db, intval($excepcionBloqueada["id_cliente"]));
                $idClienteVenta = intval($excepcionBloqueada["id_cliente"]);
                if ($clienteExcepcion) {
                    $clienteVenta = $this->valor($clienteExcepcion, "nombre_publico", $clienteVenta);
                    $identificadorClienteVenta = $this->valor($clienteExcepcion, "identificador", $identificadorClienteVenta);
                }
            }
            if ($excepcionBloqueada && intval($this->valor($excepcionBloqueada, "id_cliente_crm"), 0) > 0) {
                $idClienteCrmVenta = intval($excepcionBloqueada["id_cliente_crm"]);
                $clienteCodigoSnapshot = trim((string) $this->valor($excepcionBloqueada, "cliente_codigo_snapshot", ""));
                $clienteVenta = trim((string) $this->valor($excepcionBloqueada, "cliente_nombre_snapshot", $clienteVenta));
                $identificadorClienteVenta = trim((string) $this->valor($excepcionBloqueada, "cliente_identificador_snapshot", $identificadorClienteVenta));
                $clienteOrigenSnapshot = trim((string) $this->valor($excepcionBloqueada, "cliente_origen_snapshot", "crm"));
                $clienteSnapshot = json_encode(array(
                    "id_cliente_crm" => $idClienteCrmVenta,
                    "codigo_cliente" => $clienteCodigoSnapshot,
                    "nombre_publico" => $clienteVenta,
                    "identificador" => $identificadorClienteVenta,
                    "origen_cliente" => $clienteOrigenSnapshot
                ), JSON_UNESCAPED_UNICODE);
            }
            if ($idClienteCrmVenta <= 0 && intval($datosVenta["id_cliente"]) > 0) {
                $clienteCrm = $this->consultarClienteCrmPosReal($db, intval($datosVenta["id_cliente"]));
                if ($clienteCrm) {
                    $idClienteCrmVenta = intval($clienteCrm["id_cliente_crm"]);
                    $clienteCodigoSnapshot = trim((string) $this->valor($clienteCrm, "codigo_cliente", ""));
                    $clienteVenta = trim((string) $this->valor($clienteCrm, "nombre_publico", $clienteVenta));
                    $identificadorClienteVenta = trim((string) $this->valor($clienteCrm, "identificador", $identificadorClienteVenta));
                    $clienteOrigenSnapshot = "crm";
                    $clienteSnapshot = json_encode(array(
                        "id_cliente_crm" => $idClienteCrmVenta,
                        "codigo_cliente" => $clienteCodigoSnapshot,
                        "nombre_publico" => $clienteVenta,
                        "identificador" => $identificadorClienteVenta,
                        "origen_cliente" => $clienteOrigenSnapshot,
                        "estatus" => $this->valor($clienteCrm, "estatus", ""),
                        "calidad_datos" => $this->valor($clienteCrm, "calidad_datos", "")
                    ), JSON_UNESCAPED_UNICODE);
                }
            }

            $columnasClienteCrm = "";
            $valoresClienteCrm = "";
            $paramsClienteCrm = array();
            if ($this->columnaExiste($db, "erp_ventas", "id_cliente_crm")) {
                $columnasClienteCrm = ", id_cliente_crm, cliente_codigo_snapshot, cliente_origen_snapshot, cliente_snapshot";
                $valoresClienteCrm = ", :id_cliente_crm, :cliente_codigo_snapshot, :cliente_origen_snapshot, :cliente_snapshot";
                $paramsClienteCrm = array(
                    ":id_cliente_crm" => $idClienteCrmVenta > 0 ? $idClienteCrmVenta : null,
                    ":cliente_codigo_snapshot" => $clienteCodigoSnapshot !== "" ? $clienteCodigoSnapshot : null,
                    ":cliente_origen_snapshot" => $clienteOrigenSnapshot !== "" ? $clienteOrigenSnapshot : null,
                    ":cliente_snapshot" => $clienteSnapshot
                );
            }

            $folio = $this->generarFolioVentaPosReal($db, "POS");
            $subtotal = $this->redondearPosReal($this->valorRutaPosReal($totales, array("subtotal"), 0));
            $descuentoTotal = $this->redondearPosReal($this->valorRutaPosReal($totales, array("descuento_total"), 0));
            $total = $this->redondearPosReal($this->valorRutaPosReal($totales, array("total_estimado"), $subtotal));
            $pagadoTotal = $this->redondearPosReal(min($total, $this->valorRutaPosReal($totales, array("pagado_total"), 0)));
            $saldoTotal = $this->redondearPosReal(max(0, $total - $pagadoTotal));
            $estatus = $saldoTotal <= 0.0001 ? "pagada" : "pendiente_pago";

            $stmt = $db->prepare("INSERT INTO erp_ventas
                (folio, canal, tipo_documento, estatus, id_almacen, id_caja, id_turno_caja,
                 id_cliente$columnasClienteCrm, cliente_nombre_publico, cliente_identificador_publico,
                 subtotal, descuento_total, impuestos_total, total,
                 pagado_total, saldo_total, creado_por, observaciones, descuento_motivo,
                 autorizado_comercial_por, fecha_autorizacion_comercial)
                VALUES (:folio, 'pos', 'venta', :estatus, :almacen, :caja, :turno,
                 :id_cliente$valoresClienteCrm, :cliente, :identificador_cliente, :subtotal, :descuento_total, 0, :total, :pagado, :saldo, :usuario, :observaciones,
                 :descuento_motivo, :autorizado_comercial_por, :fecha_autorizacion_comercial)");
            $stmt->execute(array_merge(array(
                ":folio" => $folio,
                ":estatus" => $estatus,
                ":almacen" => $datosVenta["id_almacen"],
                ":caja" => $datosVenta["id_caja"],
                ":turno" => $datosVenta["id_turno_caja"],
                ":id_cliente" => $idClienteVenta > 0 ? $idClienteVenta : null,
                ":cliente" => $clienteVenta,
                ":identificador_cliente" => $identificadorClienteVenta !== "" ? $identificadorClienteVenta : null,
                ":subtotal" => $subtotal,
                ":descuento_total" => $descuentoTotal,
                ":total" => $total,
                ":pagado" => $pagadoTotal,
                ":saldo" => $saldoTotal,
                ":usuario" => $idUsuario,
                ":observaciones" => $observaciones,
                ":descuento_motivo" => $excepcionBloqueada ? $this->valor($excepcionBloqueada, "motivo", null) : null,
                ":autorizado_comercial_por" => $excepcionBloqueada ? intval($this->valor($excepcionBloqueada, "autorizado_por", 0)) : null,
                ":fecha_autorizacion_comercial" => $excepcionBloqueada ? $this->valor($excepcionBloqueada, "fecha_autorizacion", null) : null
            ), $paramsClienteCrm));
            $idVenta = intval($db->lastInsertId());

            $evidenciaInventario = array();
            $detallesGarantia = array();
            foreach ($partidas as $partida) {
                $sku = $this->consultarSkuVenta($db, intval($this->valor($partida, "id_sku", 0)));
                if (!$sku) {
                    throw new Exception("SKU no encontrado durante venta real");
                }
                $modoSalida = $this->valorRutaPosReal($partida, array("plan_salida_inventario", "modo"), "existencia_agregada");
                $precioPartida = isset($partidasPrecio[intval($this->valor($partida, "renglon", 0))]) ? $partidasPrecio[intval($this->valor($partida, "renglon", 0))] : array();
                $aplicaExcepcion = !empty($partida["aplica_excepcion_comercial"]);
                $precioOriginal = $this->redondearPosReal($this->valor($partida, "precio_unitario_original", $this->valor($partida, "precio_unitario", 0)));
                $precioFinal = $this->redondearPosReal($this->valor($partida, "precio_unitario_final", $this->valor($partida, "precio_unitario", 0)));
                $descuentoPartida = $this->redondearPosReal($this->valor($partida, "descuento_excepcion", 0));
                $subtotalPartida = $this->redondearPosReal($this->valor($partida, "subtotal_original", $this->valor($partida, "subtotal", 0)));
                $totalPartida = $this->redondearPosReal($this->valor($partida, "total_final", $this->valor($partida, "subtotal", 0)));

                $stmt = $db->prepare("INSERT INTO erp_ventas_detalle
                    (id_venta, renglon, id_producto_erp, id_sku_erp, sku, descripcion,
                     tipo_partida, controla_inventario, modo_salida, cantidad_venta,
                     unidad_venta, cantidad_base, unidad_base, precio_unitario,
                     precio_unitario_sin_impuesto, precio_base, precio_aplicado, id_lista_precio,
                     lista_precio_snapshot, regla_precio_origen, descuento, impuestos, subtotal, total, estatus,
                     id_excepcion_comercial, tipo_excepcion_comercial, motivo_excepcion_comercial,
                     autorizado_comercial_por, fecha_autorizacion_comercial)
                    VALUES (:venta, :renglon, :producto, :sku_id, :sku, :descripcion,
                     'producto', :controla, :modo, :cantidad, :unidad_venta, :cantidad_base,
                     :unidad_base, :precio, :precio, :precio_base, :precio_aplicado, :lista_id,
                     :lista_snapshot, :regla_precio, :descuento, 0, :subtotal, :total, 'confirmada',
                     :id_excepcion, :tipo_excepcion, :motivo_excepcion, :autorizado_comercial_por,
                     :fecha_autorizacion_comercial)");
                $stmt->execute(array(
                    ":venta" => $idVenta,
                    ":renglon" => intval($this->valor($partida, "renglon", 0)),
                    ":producto" => intval($sku["id_producto_erp"]),
                    ":sku_id" => intval($sku["id_sku"]),
                    ":sku" => $sku["sku"],
                    ":descripcion" => $this->valor($partida, "descripcion", $sku["nombre_sku"]),
                    ":controla" => intval($this->valor($partida, "controla_inventario", 0)),
                    ":modo" => $modoSalida,
                    ":cantidad" => $this->redondearPosReal($this->valor($partida, "cantidad", 0)),
                    ":unidad_venta" => $sku["unidad_venta_label"],
                    ":cantidad_base" => $this->redondearPosReal($this->valor($partida, "cantidad", 0)),
                    ":unidad_base" => $sku["unidad_venta_label"],
                    ":precio" => $precioFinal,
                    ":precio_base" => $this->redondearPosReal($this->valor($precioPartida, "precio_base", $precioOriginal)),
                    ":precio_aplicado" => $precioFinal,
                    ":lista_id" => $this->valor($precioPartida, "id_lista_precio", null),
                    ":lista_snapshot" => $this->valor($precioPartida, "lista_precio_snapshot", null),
                    ":regla_precio" => $this->valor($precioPartida, "regla_precio_origen", "catalogo_general"),
                    ":descuento" => $descuentoPartida,
                    ":subtotal" => $subtotalPartida,
                    ":total" => $totalPartida,
                    ":id_excepcion" => $aplicaExcepcion ? intval($this->valor($partida, "id_excepcion_comercial", 0)) : null,
                    ":tipo_excepcion" => $aplicaExcepcion ? $this->valor($partida, "tipo_excepcion_comercial", null) : null,
                    ":motivo_excepcion" => $aplicaExcepcion && $excepcionBloqueada ? $this->valor($excepcionBloqueada, "motivo", null) : null,
                    ":autorizado_comercial_por" => $aplicaExcepcion && $excepcionBloqueada ? intval($this->valor($excepcionBloqueada, "autorizado_por", 0)) : null,
                    ":fecha_autorizacion_comercial" => $aplicaExcepcion && $excepcionBloqueada ? $this->valor($excepcionBloqueada, "fecha_autorizacion", null) : null
                ));
                $idDetalle = intval($db->lastInsertId());
                if ($aplicaExcepcion && $excepcionBloqueada) {
                    $db->prepare("UPDATE erp_ventas_excepciones_comerciales
                        SET id_venta=:venta, id_venta_detalle=:detalle, estatus='aplicada',
                            aplicado_por=:usuario, fecha_aplicacion=NOW(), fecha_actualizacion=NOW()
                        WHERE id_excepcion_comercial=:excepcion AND estatus='autorizada'")
                        ->execute(array(
                            ":venta" => $idVenta,
                            ":detalle" => $idDetalle,
                            ":usuario" => $idUsuario,
                            ":excepcion" => intval($excepcionBloqueada["id_excepcion_comercial"])
                        ));
                }
                $detallesGarantia[] = array(
                    "id_venta_detalle" => $idDetalle,
                    "id_producto_erp" => intval($sku["id_producto_erp"]),
                    "id_sku_erp" => intval($sku["id_sku"])
                );
                if (intval($this->valor($partida, "controla_inventario", 0)) === 1) {
                    foreach ($this->valorRutaPosReal($partida, array("plan_salida_inventario", "asignaciones"), array()) as $asignacionInv) {
                        $evidenciaInventario[] = $this->aplicarSalidaInventarioPosReal($db, $idVenta, $idDetalle, $folio, $sku, $asignacionInv, $datosVenta["id_almacen"], $idUsuario);
                    }
                }
            }

            $garantias = new GarantiasErp();
            $snapshotsGarantia = $garantias->guardarSnapshotsVenta($db, array(
                "id_venta" => $idVenta,
                "id_almacen" => $datosVenta["id_almacen"],
                "canal" => "pos",
                "fecha" => date("Y-m-d"),
                "detalles" => $detallesGarantia
            ));
            if (!empty($snapshotsGarantia["error"])) {
                throw new Exception("No se pudo guardar snapshot de garantia: " . $snapshotsGarantia["mensaje"]);
            }
            $bloqueosGarantia = $this->valorRutaPosReal($snapshotsGarantia, array("depurar", "bloqueos"), array());
            if (!empty($bloqueosGarantia)) {
                throw new Exception("Snapshot de garantia bloqueado: " . implode("; ", $bloqueosGarantia));
            }

            $evidenciaPagos = $this->registrarPagosPosReal($db, $idVenta, $folio, $datosVenta, $pagosPrevalidados, $total, $idUsuario, $idClienteCrmVenta, $clienteSnapshot);
            if ($idAtencion > 0) {
                $db->prepare("UPDATE erp_pos_atenciones
                    SET estatus='convertida', fecha_conversion=CURRENT_TIMESTAMP, id_venta_convertida=:venta, fecha_actualizacion=CURRENT_TIMESTAMP
                    WHERE id_atencion_pos=:atencion")
                    ->execute(array(":venta" => $idVenta, ":atencion" => $idAtencion));
            }
            $montoCaja = 0;
            foreach ($evidenciaPagos as $pago) {
                if (!isset($pago["mueve_caja"]) || !empty($pago["mueve_caja"])) {
                    $montoCaja += floatval($pago["monto_aplicado"]);
                }
            }
            $db->prepare("UPDATE erp_pos_turnos
                SET monto_esperado=ROUND(monto_esperado+:monto, 6)
                WHERE id_turno_caja=:turno")
                ->execute(array(":monto" => $this->redondearPosReal($montoCaja), ":turno" => $datosVenta["id_turno_caja"]));

            $db->commit();
            return $this->respuesta(false, "success", "Venta POS confirmada", array(
                "modo" => "venta_real_pos_ui",
                "folio" => $folio,
                "id_venta" => $idVenta,
                "id_atencion_convertida" => $idAtencion,
                "estatus" => $estatus,
                "cliente" => array(
                    "id_cliente" => $idClienteVenta > 0 ? $idClienteVenta : null,
                    "id_cliente_crm" => $idClienteCrmVenta > 0 ? $idClienteCrmVenta : null,
                    "nombre_publico" => $clienteVenta,
                    "identificador" => $identificadorClienteVenta,
                    "origen" => $clienteOrigenSnapshot
                ),
                "totales" => array(
                    "subtotal" => $subtotal,
                    "descuento_total" => $descuentoTotal,
                    "total" => $total,
                    "pagado_total" => $pagadoTotal,
                    "saldo_total" => $saldoTotal
                ),
                "inventario" => $evidenciaInventario,
                "garantias" => $this->valorRutaPosReal($snapshotsGarantia, array("depurar", "guardados"), array()),
                "pagos" => $evidenciaPagos,
                "excepcion_comercial" => $excepcionBloqueada ? array(
                    "folio" => $folioExcepcion,
                    "id_excepcion_comercial" => intval($excepcionBloqueada["id_excepcion_comercial"]),
                    "estatus" => "aplicada",
                    "descuento_total" => $descuentoTotal
                ) : null
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage(), array(
                "rollback" => true,
                "modo" => "venta_real_pos_ui"
            ));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: consultar configuracion operativa POS para pantallas dedicadas de caja y administracion.
     * Impacto: separa tiendas/cajas/terminales/turnos de la pantalla de cobro sin escribir datos.
     * Contrato: read-only; si falta alguna tabla devuelve listas vacias y banderas de esquema pendiente.
     */
    public function configuracionPosReadOnly() {
        try {
            $db = $this->getConexion();
            $tablas = array(
                "erp_pos_cajas",
                "erp_pos_terminales",
                "erp_pos_usuarios_cajas",
                "erp_pos_turnos",
                "erp_pos_movimientos_caja"
            );
            $schema = array();
            foreach ($tablas as $tabla) {
                $schema[$tabla] = $this->tablaExiste($db, $tabla);
            }
            $cajas = $this->listarCajasPos($db);
            $terminales = $this->listarTerminalesPos($db);
            $asignaciones = $this->listarAsignacionesPos($db);
            $turnosAbiertos = $this->listarTurnosAbiertosPos($db);
            $movimientos = $this->listarMovimientosCajaRecientes($db, 25);
            return $this->respuesta(false, "success", "Configuracion POS consultada", array(
                "schema" => $schema,
                "schema_pendiente" => in_array(false, $schema, true),
                "almacenes" => $this->listarAlmacenesVenta($db),
                "cajas" => $cajas,
                "terminales" => $terminales,
                "asignaciones" => $asignaciones,
                "turnos_abiertos" => $turnosAbiertos,
                "movimientos_recientes" => $movimientos,
                "resumen" => array(
                    "cajas" => count($cajas),
                    "terminales" => count($terminales),
                    "asignaciones" => count($asignaciones),
                    "turnos_abiertos" => count($turnosAbiertos),
                    "movimientos_recientes" => count($movimientos)
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", "No se pudo consultar configuracion POS", array(
                "excepcion" => $e->getMessage()
            ));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: validar propuesta de caja POS antes de crear/editar registros reales.
     * Impacto: prepara CRUD de cajas con control de sucursal, codigo y metodos de pago.
     * Contrato: dry-run; no inserta ni actualiza `erp_pos_cajas`.
     */
    public function configuracionCajaDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $bloqueos = array();
            $avisos = array();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
            $nombre = trim((string) $this->valor($datos, "nombre", ""));
            $metodos = array(
                "efectivo" => intval($this->valor($datos, "permite_efectivo", 1)) ? 1 : 0,
                "tarjeta" => intval($this->valor($datos, "permite_tarjeta", 1)) ? 1 : 0,
                "transferencia" => intval($this->valor($datos, "permite_transferencia", 1)) ? 1 : 0
            );
            $almacen = $this->almacenVentaPorId($db, $idAlmacen);
            if (!$almacen) {
                $bloqueos[] = "Selecciona una tienda/almacen vendible activo";
            }
            if ($codigo === "") {
                $codigo = $almacen ? ("CJ-" . strtoupper((string) $this->valor($almacen, "codigo_almacen", "POS")) . "-01") : "";
                $avisos[] = "Se sugiere codigo de caja automaticamente";
            }
            if ($nombre === "") {
                $nombre = $almacen ? ("Caja principal " . (string) $this->valor($almacen, "nombre_comercial", $this->valor($almacen, "almacen", ""))) : "";
                $avisos[] = "Se sugiere nombre de caja automaticamente";
            }
            if ($codigo === "" || !preg_match('/^[A-Z0-9_-]{3,40}$/', $codigo)) {
                $bloqueos[] = "El codigo de caja debe tener 3 a 40 caracteres y usar letras, numeros, guion o guion bajo";
            }
            if ($nombre === "" || strlen($nombre) > 120) {
                $bloqueos[] = "El nombre de caja es obligatorio y no debe superar 120 caracteres";
            }
            if (!$metodos["efectivo"] && !$metodos["tarjeta"] && !$metodos["transferencia"]) {
                $bloqueos[] = "La caja debe permitir al menos un metodo de pago";
            }
            if ($this->codigoExisteEnTabla($db, "erp_pos_cajas", "codigo", $codigo)) {
                $bloqueos[] = "Ya existe una caja POS con ese codigo";
            }
            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Caja POS valida para alta futura" : "Caja POS requiere ajustes", array(
                "dry_run" => true,
                "bloqueos" => $bloqueos,
                "avisos" => $avisos,
                "propuesta" => array(
                    "id_almacen" => $idAlmacen,
                    "codigo" => $codigo,
                    "nombre" => $nombre,
                    "permite_efectivo" => $metodos["efectivo"],
                    "permite_tarjeta" => $metodos["tarjeta"],
                    "permite_transferencia" => $metodos["transferencia"],
                    "estatus" => "activa"
                ),
                "almacen" => $almacen
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", "No se pudo validar caja POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: validar propuesta de terminal POS sin persistirla.
     * Impacto: prepara identidad de equipo/navegador ligada a tienda y caja oficial.
     * Contrato: dry-run; no inserta ni actualiza `erp_pos_terminales`.
     */
    public function configuracionTerminalDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $bloqueos = array();
            $avisos = array();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
            $nombre = trim((string) $this->valor($datos, "nombre", ""));
            $identificador = trim((string) $this->valor($datos, "identificador_terminal", ""));
            $almacen = $this->almacenVentaPorId($db, $idAlmacen);
            $caja = $this->cajaPosPorId($db, $idCaja);
            if (!$almacen) {
                $bloqueos[] = "Selecciona una tienda/almacen vendible activo";
            }
            if (!$caja) {
                $bloqueos[] = "Selecciona una caja POS activa";
            } elseif (intval($this->valor($caja, "id_almacen", 0)) !== $idAlmacen) {
                $bloqueos[] = "La caja seleccionada no pertenece a la tienda de la terminal";
            }
            if ($codigo === "") {
                $codigo = $almacen ? ("TERM-" . strtoupper((string) $this->valor($almacen, "codigo_almacen", "POS")) . "-01") : "";
                $avisos[] = "Se sugiere codigo de terminal automaticamente";
            }
            if ($nombre === "") {
                $nombre = $almacen ? ("Terminal principal " . (string) $this->valor($almacen, "nombre_comercial", $this->valor($almacen, "almacen", ""))) : "";
                $avisos[] = "Se sugiere nombre de terminal automaticamente";
            }
            if ($codigo === "" || !preg_match('/^[A-Z0-9_-]{3,60}$/', $codigo)) {
                $bloqueos[] = "El codigo de terminal debe tener 3 a 60 caracteres y usar letras, numeros, guion o guion bajo";
            }
            if ($nombre === "" || strlen($nombre) > 150) {
                $bloqueos[] = "El nombre de terminal es obligatorio y no debe superar 150 caracteres";
            }
            if ($this->codigoExisteEnTabla($db, "erp_pos_terminales", "codigo", $codigo)) {
                $bloqueos[] = "Ya existe una terminal POS con ese codigo";
            }
            if ($identificador === "") {
                $avisos[] = "El identificador fisico/local queda pendiente; en productivo debe capturarse para evitar selector libre";
            }
            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Terminal POS valida para alta futura" : "Terminal POS requiere ajustes", array(
                "dry_run" => true,
                "bloqueos" => $bloqueos,
                "avisos" => $avisos,
                "propuesta" => array(
                    "id_almacen" => $idAlmacen,
                    "id_caja" => $idCaja,
                    "codigo" => $codigo,
                    "nombre" => $nombre,
                    "identificador_terminal" => $identificador,
                    "estatus" => "activa"
                ),
                "almacen" => $almacen,
                "caja" => $caja
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", "No se pudo validar terminal POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: validar propuesta de asignacion usuario/caja/terminal sin escribir BD.
     * Impacto: prepara POS que abre ya configurado por sucursal/caja del operador.
     * Contrato: dry-run; no inserta ni modifica `erp_pos_usuarios_cajas`.
     */
    public function configuracionAsignacionDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $bloqueos = array();
            $avisos = array();
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTerminal = intval($this->valor($datos, "id_terminal_pos", 0));
            $prioridad = max(1, intval($this->valor($datos, "prioridad", 1)));
            $almacen = $this->almacenVentaPorId($db, $idAlmacen);
            $caja = $this->cajaPosPorId($db, $idCaja);
            $terminal = $this->terminalPosPorId($db, $idTerminal);
            if ($idUsuario <= 0) {
                $bloqueos[] = "Captura id de usuario";
            }
            if (!$almacen) {
                $bloqueos[] = "Selecciona una tienda/almacen vendible activo";
            }
            if (!$caja) {
                $bloqueos[] = "Selecciona una caja POS activa";
            } elseif (intval($this->valor($caja, "id_almacen", 0)) !== $idAlmacen) {
                $bloqueos[] = "La caja no pertenece a la tienda seleccionada";
            }
            if ($idTerminal > 0) {
                if (!$terminal) {
                    $bloqueos[] = "La terminal seleccionada no existe o no esta activa";
                } elseif (intval($this->valor($terminal, "id_almacen", 0)) !== $idAlmacen || intval($this->valor($terminal, "id_caja", 0)) !== $idCaja) {
                    $bloqueos[] = "La terminal no pertenece a la tienda/caja seleccionada";
                }
            } else {
                $avisos[] = "La asignacion sin terminal puede servir para UAT, pero productivo debe amarrar terminal";
            }
            if ($this->asignacionActivaExiste($db, $idUsuario, $idAlmacen, $idCaja, $idTerminal)) {
                $bloqueos[] = "Ya existe una asignacion activa para este usuario, tienda, caja y terminal";
            }
            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Asignacion POS valida para alta futura" : "Asignacion POS requiere ajustes", array(
                "dry_run" => true,
                "bloqueos" => $bloqueos,
                "avisos" => $avisos,
                "propuesta" => array(
                    "id_usuario" => $idUsuario,
                    "id_almacen" => $idAlmacen,
                    "id_caja" => $idCaja,
                    "id_terminal_pos" => $idTerminal ?: null,
                    "prioridad" => $prioridad,
                    "estatus" => "activo"
                ),
                "almacen" => $almacen,
                "caja" => $caja,
                "terminal" => $terminal
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", "No se pudo validar asignacion POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: crear caja POS real con validaciones del dry-run.
     * Impacto: habilita CRUD administrativo de configuracion POS sin abrir turnos ni mover caja.
     * Contrato: escritura transaccional; requiere autorizacion externa en scripts/endpoints antes de invocarse.
     */
    public function configuracionCajaGuardarReal($datos = array(), $idUsuario = 0) {
        try {
            $db = $this->getConexion();
            if (!$db || !$this->tablaExiste($db, "erp_pos_cajas")) {
                return $this->respuesta(true, "danger", "Esquema de cajas POS no disponible");
            }
            $idUsuario = intval($idUsuario);
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $validacion = $this->configuracionCajaValidarParaGuardar($db, $datos, $idCaja);
            if (!empty($validacion["bloqueos"])) {
                return $this->respuesta(false, "warning", "Caja POS requiere ajustes", array(
                    "bloqueos" => $validacion["bloqueos"],
                    "avisos" => $validacion["avisos"],
                    "propuesta" => $validacion["propuesta"]
                ));
            }

            $db->beginTransaction();
            if ($idCaja > 0) {
                $cajaActual = $this->cajaPosPorId($db, $idCaja);
                if (!$cajaActual) {
                    throw new Exception("Caja POS no encontrada o inactiva");
                }
                if (intval($cajaActual["id_almacen"]) !== intval($validacion["propuesta"]["id_almacen"])
                    && $this->cajaTieneDependenciasOperativas($db, $idCaja)) {
                    throw new Exception("No se puede cambiar la tienda de una caja con turnos, ventas o movimientos");
                }
                $stmt = $db->prepare("UPDATE erp_pos_cajas
                    SET codigo=:codigo, nombre=:nombre, id_almacen=:almacen,
                        permite_efectivo=:efectivo, permite_tarjeta=:tarjeta,
                        permite_transferencia=:transferencia, observaciones=:observaciones,
                        fecha_actualizacion=NOW()
                    WHERE id_caja=:id");
                $stmt->execute(array(
                    ":codigo" => $validacion["propuesta"]["codigo"],
                    ":nombre" => $validacion["propuesta"]["nombre"],
                    ":almacen" => $validacion["propuesta"]["id_almacen"],
                    ":efectivo" => $validacion["propuesta"]["permite_efectivo"],
                    ":tarjeta" => $validacion["propuesta"]["permite_tarjeta"],
                    ":transferencia" => $validacion["propuesta"]["permite_transferencia"],
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                    ":id" => $idCaja
                ));
                $accion = "actualizada";
            } else {
                $stmt = $db->prepare("INSERT INTO erp_pos_cajas
                    (codigo, nombre, id_almacen, estatus, permite_efectivo, permite_tarjeta, permite_transferencia, observaciones, fecha_registro, fecha_actualizacion)
                    VALUES (:codigo, :nombre, :almacen, 'activa', :efectivo, :tarjeta, :transferencia, :observaciones, NOW(), NOW())");
                $stmt->execute(array(
                    ":codigo" => $validacion["propuesta"]["codigo"],
                    ":nombre" => $validacion["propuesta"]["nombre"],
                    ":almacen" => $validacion["propuesta"]["id_almacen"],
                    ":efectivo" => $validacion["propuesta"]["permite_efectivo"],
                    ":tarjeta" => $validacion["propuesta"]["permite_tarjeta"],
                    ":transferencia" => $validacion["propuesta"]["permite_transferencia"],
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", ""))
                ));
                $idCaja = intval($db->lastInsertId());
                $accion = "creada";
            }
            $db->commit();
            return $this->respuesta(false, "success", "Caja POS " . $accion, array(
                "id_caja" => $idCaja,
                "accion" => $accion,
                "propuesta" => $validacion["propuesta"],
                "id_usuario" => $idUsuario,
                "no_abre_turno" => true,
                "no_mueve_caja" => true
            ));
        } catch (Exception $e) {
            if (isset($db) && $db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo guardar caja POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: crear terminal POS real validando pertenencia tienda/caja.
     * Impacto: prepara terminales oficiales para quitar selector libre del POS.
     * Contrato: escritura transaccional; no abre turnos ni mueve caja.
     */
    public function configuracionTerminalGuardarReal($datos = array(), $idUsuario = 0) {
        try {
            $db = $this->getConexion();
            if (!$db || !$this->tablaExiste($db, "erp_pos_terminales")) {
                return $this->respuesta(true, "danger", "Esquema de terminales POS no disponible");
            }
            $idUsuario = intval($idUsuario);
            $idTerminal = intval($this->valor($datos, "id_terminal_pos", 0));
            $validacion = $this->configuracionTerminalValidarParaGuardar($db, $datos, $idTerminal);
            if (!empty($validacion["bloqueos"])) {
                return $this->respuesta(false, "warning", "Terminal POS requiere ajustes", array(
                    "bloqueos" => $validacion["bloqueos"],
                    "avisos" => $validacion["avisos"],
                    "propuesta" => $validacion["propuesta"]
                ));
            }

            $db->beginTransaction();
            if ($idTerminal > 0) {
                $terminalActual = $this->terminalPosPorId($db, $idTerminal);
                if (!$terminalActual) {
                    throw new Exception("Terminal POS no encontrada o inactiva");
                }
                if ((intval($terminalActual["id_almacen"]) !== intval($validacion["propuesta"]["id_almacen"])
                    || intval($terminalActual["id_caja"]) !== intval($validacion["propuesta"]["id_caja"]))
                    && $this->terminalTieneTurnoAbierto($db, $idTerminal)) {
                    throw new Exception("No se puede mover una terminal con turno abierto");
                }
                $stmt = $db->prepare("UPDATE erp_pos_terminales
                    SET codigo=:codigo, nombre=:nombre, id_almacen=:almacen, id_caja=:caja,
                        identificador_terminal=:identificador, observaciones=:observaciones,
                        fecha_actualizacion=NOW()
                    WHERE id_terminal_pos=:id");
                $stmt->execute(array(
                    ":codigo" => $validacion["propuesta"]["codigo"],
                    ":nombre" => $validacion["propuesta"]["nombre"],
                    ":almacen" => $validacion["propuesta"]["id_almacen"],
                    ":caja" => $validacion["propuesta"]["id_caja"],
                    ":identificador" => $validacion["propuesta"]["identificador_terminal"],
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                    ":id" => $idTerminal
                ));
                $accion = "actualizada";
            } else {
                $stmt = $db->prepare("INSERT INTO erp_pos_terminales
                    (codigo, nombre, id_almacen, id_caja, identificador_terminal, estatus, observaciones, fecha_registro, fecha_actualizacion)
                    VALUES (:codigo, :nombre, :almacen, :caja, :identificador, 'activa', :observaciones, NOW(), NOW())");
                $stmt->execute(array(
                    ":codigo" => $validacion["propuesta"]["codigo"],
                    ":nombre" => $validacion["propuesta"]["nombre"],
                    ":almacen" => $validacion["propuesta"]["id_almacen"],
                    ":caja" => $validacion["propuesta"]["id_caja"],
                    ":identificador" => $validacion["propuesta"]["identificador_terminal"],
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", ""))
                ));
                $idTerminal = intval($db->lastInsertId());
                $accion = "creada";
            }
            $db->commit();
            return $this->respuesta(false, "success", "Terminal POS " . $accion, array(
                "id_terminal_pos" => $idTerminal,
                "accion" => $accion,
                "propuesta" => $validacion["propuesta"],
                "id_usuario" => $idUsuario,
                "no_abre_turno" => true,
                "no_mueve_caja" => true
            ));
        } catch (Exception $e) {
            if (isset($db) && $db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo guardar terminal POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: crear asignacion usuario/caja/terminal real.
     * Impacto: permite que el POS abra amarrado a sucursal/caja del operador.
     * Contrato: escritura transaccional; no abre turno ni modifica permisos.
     */
    public function configuracionAsignacionGuardarReal($datos = array(), $idUsuarioEjecuta = 0) {
        try {
            $db = $this->getConexion();
            if (!$db || !$this->tablaExiste($db, "erp_pos_usuarios_cajas")) {
                return $this->respuesta(true, "danger", "Esquema de asignaciones POS no disponible");
            }
            $idAsignacion = intval($this->valor($datos, "id_usuario_caja", 0));
            $validacion = $this->configuracionAsignacionValidarParaGuardar($db, $datos, $idAsignacion);
            if (!empty($validacion["bloqueos"])) {
                return $this->respuesta(false, "warning", "Asignacion POS requiere ajustes", array(
                    "bloqueos" => $validacion["bloqueos"],
                    "avisos" => $validacion["avisos"],
                    "propuesta" => $validacion["propuesta"]
                ));
            }

            $db->beginTransaction();
            if ($idAsignacion > 0) {
                $stmt = $db->prepare("UPDATE erp_pos_usuarios_cajas
                    SET id_usuario=:usuario, id_almacen=:almacen, id_caja=:caja,
                        id_terminal_pos=:terminal, prioridad=:prioridad, observaciones=:observaciones
                    WHERE id_usuario_caja=:id AND estatus='activo'");
                $stmt->execute(array(
                    ":usuario" => $validacion["propuesta"]["id_usuario"],
                    ":almacen" => $validacion["propuesta"]["id_almacen"],
                    ":caja" => $validacion["propuesta"]["id_caja"],
                    ":terminal" => $validacion["propuesta"]["id_terminal_pos"],
                    ":prioridad" => $validacion["propuesta"]["prioridad"],
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                    ":id" => $idAsignacion
                ));
                if ($stmt->rowCount() <= 0) {
                    throw new Exception("Asignacion POS no encontrada o no activa");
                }
                $accion = "actualizada";
            } else {
                $stmt = $db->prepare("INSERT INTO erp_pos_usuarios_cajas
                    (id_usuario, id_almacen, id_caja, id_terminal_pos, estatus, prioridad, fecha_inicio, fecha_fin, creado_por, observaciones)
                    VALUES (:usuario, :almacen, :caja, :terminal, 'activo', :prioridad, NOW(), NULL, :creado_por, :observaciones)");
                $stmt->execute(array(
                    ":usuario" => $validacion["propuesta"]["id_usuario"],
                    ":almacen" => $validacion["propuesta"]["id_almacen"],
                    ":caja" => $validacion["propuesta"]["id_caja"],
                    ":terminal" => $validacion["propuesta"]["id_terminal_pos"],
                    ":prioridad" => $validacion["propuesta"]["prioridad"],
                    ":creado_por" => intval($idUsuarioEjecuta),
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", ""))
                ));
                $idAsignacion = intval($db->lastInsertId());
                $accion = "creada";
            }
            $db->commit();
            return $this->respuesta(false, "success", "Asignacion POS " . $accion, array(
                "id_usuario_caja" => $idAsignacion,
                "accion" => $accion,
                "propuesta" => $validacion["propuesta"],
                "id_usuario_ejecuta" => intval($idUsuarioEjecuta),
                "no_abre_turno" => true,
                "no_mueve_caja" => true
            ));
        } catch (Exception $e) {
            if (isset($db) && $db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo guardar asignacion POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: desactivar caja, terminal o asignacion POS con guardrails.
     * Impacto: permite baja logica sin borrar historial ni afectar turnos abiertos.
     * Contrato: escritura transaccional; motivo obligatorio.
     */
    public function configuracionPosDesactivarReal($datos = array(), $idUsuario = 0) {
        try {
            $db = $this->getConexion();
            $tipo = trim((string) $this->valor($datos, "tipo", ""));
            $id = intval($this->valor($datos, "id", 0));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            if ($id <= 0 || $motivo === "" || !in_array($tipo, array("caja", "terminal", "asignacion"), true)) {
                return $this->respuesta(false, "warning", "Desactivacion POS requiere tipo, id y motivo", array(
                    "bloqueos" => array("Captura tipo valido, id y motivo obligatorio")
                ));
            }

            $db->beginTransaction();
            if ($tipo === "caja") {
                if ($this->cajaTieneTurnoAbierto($db, $id)) {
                    throw new Exception("No se puede desactivar una caja con turno abierto");
                }
                $stmt = $db->prepare("UPDATE erp_pos_cajas
                    SET estatus='inactiva', observaciones=:observaciones, fecha_actualizacion=NOW()
                    WHERE id_caja=:id AND COALESCE(estatus, 'activa')='activa'");
            } elseif ($tipo === "terminal") {
                if ($this->terminalTieneTurnoAbierto($db, $id)) {
                    throw new Exception("No se puede desactivar una terminal con turno abierto");
                }
                $stmt = $db->prepare("UPDATE erp_pos_terminales
                    SET estatus='inactiva', observaciones=:observaciones, fecha_actualizacion=NOW()
                    WHERE id_terminal_pos=:id AND COALESCE(estatus, 'activa')='activa'");
            } else {
                if ($this->asignacionTieneTurnoAbierto($db, $id)) {
                    throw new Exception("No se puede desactivar una asignacion usada por turno abierto");
                }
                $stmt = $db->prepare("UPDATE erp_pos_usuarios_cajas
                    SET estatus='inactivo', fecha_fin=NOW(), observaciones=:observaciones
                    WHERE id_usuario_caja=:id AND estatus='activo'");
            }
            $stmt->execute(array(
                ":observaciones" => "Desactivado por usuario " . intval($idUsuario) . ": " . $motivo,
                ":id" => $id
            ));
            if ($stmt->rowCount() <= 0) {
                throw new Exception("Registro POS no encontrado o ya inactivo");
            }
            $db->commit();
            return $this->respuesta(false, "success", "Configuracion POS desactivada", array(
                "tipo" => $tipo,
                "id" => $id,
                "motivo" => $motivo,
                "id_usuario" => intval($idUsuario),
                "baja_logica" => true,
                "no_abre_turno" => true,
                "no_mueve_caja" => true
            ));
        } catch (Exception $e) {
            if (isset($db) && $db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", "No se pudo desactivar configuracion POS", array("excepcion" => $e->getMessage()));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: simular pedido/apartado con reserva sin afectar inventario.
     * Impacto: define el contrato de Pedidos ERP antes de crear tablas o reservas reales.
     * Contrato: dry-run; no inserta pedido, no reserva y no mueve kardex.
     */
    public function pedidoReservaDryRun($datos = array()) {
        $datos["exigir_pago_completo"] = 0;
        $db = $this->getConexion();
        if (!$db) {
            return $this->respuesta(false, "warning", "Dry-run de pedido bloqueado", array(
                "dry_run" => true,
                "bloqueos" => array("conexion_bd_no_disponible"),
                "contrato" => array(
                    "no_crea_pedido" => true,
                    "no_registra_pago" => true,
                    "no_reserva_inventario" => true,
                    "no_mueve_kardex" => true
                )
            ));
        }
        $prevalidacion = $this->prevalidarCarritoPos($datos);
        $tipoDocumento = trim((string) $this->valor($datos, "tipo_documento", "pedido"));
        $fechaCompromiso = trim((string) $this->valor($datos, "fecha_entrega_compromiso", ""));
        $cliente = trim((string) $this->valor($datos, "cliente_nombre_publico", ""));
        $identificadorCliente = trim((string) $this->valor($datos, "identificador_cliente", ""));
        $idCliente = intval($this->valor($datos, "id_cliente", 0));
        $bloqueos = array();
        $avisos = array();
        $schemaCompleto = $this->schemaVentaPosCompleto($db) && $this->tablaExiste($db, "erp_inventario_reservas");
        $politicaApartado = $schemaCompleto ? $this->politicaApartadoActiva($db) : null;
        $totales = isset($prevalidacion["depurar"]["totales"]) ? $prevalidacion["depurar"]["totales"] : array();
        $totalEstimado = round(floatval($this->valor($totales, "total_estimado", 0)), 6);
        $pagadoTotal = round(floatval($this->valor($totales, "pagado_total", 0)), 6);
        $anticipoMinimo = 0;
        $fechaMaximaCompromiso = null;
        if (!in_array($tipoDocumento, array("pedido", "apartado"), true)) {
            $bloqueos[] = "Tipo de documento invalido para reserva";
        }
        if ($cliente === "" && $identificadorCliente === "" && $idCliente <= 0) {
            $bloqueos[] = "Captura cliente o identificador publico del pedido";
        }
        if ($fechaCompromiso === "") {
            $bloqueos[] = "Captura fecha compromiso de entrega/recogida";
        } elseif (!$this->fechaPedidoValida($fechaCompromiso)) {
            $bloqueos[] = "La fecha compromiso no es valida";
        } elseif (substr($fechaCompromiso, 0, 10) < date("Y-m-d")) {
            $bloqueos[] = "La fecha compromiso no puede ser anterior a hoy";
        }
        if (isset($prevalidacion["depurar"]["bloqueos"]) && is_array($prevalidacion["depurar"]["bloqueos"])) {
            $bloqueos = array_merge($bloqueos, $prevalidacion["depurar"]["bloqueos"]);
        }
        if (!$schemaCompleto) {
            $bloqueos[] = "Esquema de pedidos/reservas pendiente de autorizacion y respaldo externo";
        } elseif ($tipoDocumento === "apartado" && !$politicaApartado) {
            $bloqueos[] = "No existe politica activa de apartado";
        }
        if ($tipoDocumento === "apartado" && $politicaApartado) {
            $anticipoMinimo = $this->calcularAnticipoMinimoApartado($totalEstimado, $politicaApartado);
            if ($anticipoMinimo > 0 && $pagadoTotal + 0.0001 < $anticipoMinimo) {
                $bloqueos[] = "El anticipo no cubre el minimo requerido para apartado";
            }
            $diasVigencia = intval($this->valor($politicaApartado, "dias_vigencia", 0));
            if ($diasVigencia > 0) {
                $fechaMaximaCompromiso = date("Y-m-d", strtotime("+" . $diasVigencia . " days"));
                if ($fechaCompromiso !== "" && $this->fechaPedidoValida($fechaCompromiso) && substr($fechaCompromiso, 0, 10) > $fechaMaximaCompromiso) {
                    $bloqueos[] = "La fecha compromiso excede la vigencia de la politica de apartado";
                }
            }
            if (intval($this->valor($politicaApartado, "permite_abonos", 1)) !== 1 && $pagadoTotal + 0.0001 < $totalEstimado) {
                $bloqueos[] = "La politica de apartado no permite abonos; debe liquidarse al crear";
            }
        }
        if ($tipoDocumento === "pedido" && $pagadoTotal <= 0) {
            $avisos[] = "Pedido sin anticipo: valido solo si la politica comercial permite reservar sin pago inicial";
        }
        $propuestaReserva = $this->propuestaReservaDesdePrevalidacion($prevalidacion);
        return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de pedido valido" : "Dry-run de pedido bloqueado", array(
            "dry_run" => true,
            "tipo_documento" => $tipoDocumento,
            "cliente_nombre_publico" => $cliente,
            "identificador_cliente" => $identificadorCliente,
            "id_cliente" => $idCliente,
            "fecha_entrega_compromiso" => $fechaCompromiso,
            "fecha_maxima_compromiso" => $fechaMaximaCompromiso,
            "politica_apartado" => $politicaApartado,
            "anticipo_minimo" => $anticipoMinimo,
            "pagado_total" => $pagadoTotal,
            "saldo_estimado" => round(max(0, $totalEstimado - $pagadoTotal), 6),
            "propuesta_reserva" => $propuestaReserva,
            "prevalidacion" => $prevalidacion,
            "bloqueos" => $bloqueos,
            "avisos" => $avisos,
            "contrato_reserva" => array(
                "requiere_folio_pedido" => true,
                "requiere_reserva_inventario" => true,
                "requiere_id_almacen" => true,
                "requiere_vencimiento_reserva" => true,
                "requiere_evento_pedido" => true,
                "requiere_consumir_o_liberar_reserva" => true,
                "requiere_trazabilidad_kardex_al_entregar" => true
            )
        ));
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: simular resolucion de cliente y precio POS sin crear clientes ni aplicar descuentos.
     * Impacto: prepara listas de precios por cliente/segmento/canal antes de implementar escritura real.
     * Contrato: read-only; devuelve precio final estimado y snapshots requeridos para venta.
     */
    public function clientePrecioDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $canal = trim((string) $this->valor($datos, "canal", "pos"));
            $idCliente = intval($this->valor($datos, "id_cliente", 0));
            $identificador = trim((string) $this->valor($datos, "identificador_cliente", ""));
            $items = $this->decodificarItems($this->valor($datos, "items", array()));
            $schemaClientesPendiente = !$this->schemaClientesCrmDisponible($db);
            $schemaListasPendiente = !$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle");
            $bloqueos = array();
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona almacen/punto de venta";
            }
            if (empty($items)) {
                $bloqueos[] = "Agrega partidas para resolver precios";
            }

            $cliente = $this->resolverClienteDryRun($db, $idCliente, $identificador, $schemaClientesPendiente);
            $partidas = array();
            $total = 0;
            foreach ($items as $indice => $item) {
                $idSku = intval($this->valor($item, "id_sku", 0));
                $cantidad = round(floatval($this->valor($item, "cantidad", 1)), 6);
                $sku = $this->consultarSkuVenta($db, $idSku);
                if (!$sku) {
                    $partidas[] = array(
                        "renglon" => $indice + 1,
                        "id_sku" => $idSku,
                        "bloqueos" => array("SKU no encontrado o inactivo")
                    );
                    $bloqueos[] = "SKU no encontrado o inactivo: " . $idSku;
                    continue;
                }
                $precio = $this->resolverPrecioSkuDryRun($db, $sku, $cliente, $canal, $idAlmacen, $schemaListasPendiente);
                $importe = round(max(0, $cantidad) * floatval($precio["precio_aplicado"]), 6);
                $total += $importe;
                $partidas[] = array(
                    "renglon" => $indice + 1,
                    "id_sku" => intval($sku["id_sku"]),
                    "sku" => $sku["sku"],
                    "descripcion" => $sku["nombre_sku"],
                    "cantidad" => $cantidad,
                    "precio_base" => $precio["precio_base"],
                    "precio_aplicado" => $precio["precio_aplicado"],
                    "importe" => $importe,
                    "regla_precio_origen" => $precio["regla_precio_origen"],
                    "id_lista_precio" => $precio["id_lista_precio"],
                    "lista_precio_snapshot" => $precio["lista_precio_snapshot"],
                    "requiere_snapshot_venta" => true
                );
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Cliente/precio resuelto en dry-run" : "Cliente/precio con observaciones", array(
                "dry_run" => true,
                "schema_clientes_pendiente" => $schemaClientesPendiente,
                "schema_listas_precios_pendiente" => $schemaListasPendiente,
                "cliente" => $cliente,
                "canal" => $canal,
                "id_almacen" => $idAlmacen,
                "partidas" => $partidas,
                "totales" => array(
                    "subtotal" => round($total, 6),
                    "total" => round($total, 6)
                ),
                "bloqueos" => array_values(array_unique($bloqueos)),
                "contrato" => array(
                    "backend_resuelve_precio" => true,
                    "venta_guarda_id_cliente_si_existe" => true,
                    "venta_guarda_id_lista_precio" => true,
                    "detalle_guarda_precio_base_aplicado_y_origen" => true,
                    "js_no_decide_descuentos" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-28.
     * Proposito: simular precio manual y descuentos POS sin permitir que el JS decida el precio final.
     * Impacto: prepara permisos, motivo, autorizacion y snapshot antes de activar escritura real.
     * Contrato: dry-run; no modifica carrito, venta, lista de precios, caja ni inventario.
     */
    public function excepcionComercialDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $canal = trim((string) $this->valor($datos, "canal", "pos"));
            $idCliente = intval($this->valor($datos, "id_cliente", 0));
            $identificador = trim((string) $this->valor($datos, "identificador_cliente", ""));
            $tipoExcepcion = trim((string) $this->valor($datos, "tipo_excepcion", ""));
            $idSkuObjetivo = intval($this->valor($datos, "id_sku", 0));
            $precioManual = floatval($this->valor($datos, "precio_manual", 0));
            $descuentoMonto = floatval($this->valor($datos, "descuento_monto", 0));
            $descuentoPorcentaje = floatval($this->valor($datos, "descuento_porcentaje", 0));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            $codigoAutorizacion = trim((string) $this->valor($datos, "codigo_autorizacion", ""));
            $items = $this->decodificarItems($this->valor($datos, "items", array()));
            $schemaClientesPendiente = !$this->schemaClientesCrmDisponible($db);
            $schemaListasPendiente = !$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle");
            $bloqueos = array();
            $avisos = array();

            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona almacen/punto de venta";
            }
            if (empty($items)) {
                $bloqueos[] = "Agrega partidas para simular la excepcion comercial";
            }
            if (!in_array($tipoExcepcion, array("precio_manual", "descuento_partida", "descuento_general"), true)) {
                $bloqueos[] = "Selecciona tipo de excepcion comercial";
            }
            if ($motivo === "") {
                $bloqueos[] = "Captura motivo obligatorio de la excepcion comercial";
            }
            if ($codigoAutorizacion === "") {
                $bloqueos[] = "Falta codigo/autorizacion de supervisor";
            }

            $cliente = $this->resolverClienteDryRun($db, $idCliente, $identificador, $schemaClientesPendiente);
            $partidas = array();
            $subtotalBase = 0;
            $subtotalAplicado = 0;
            $descuentoTotal = 0;

            foreach ($items as $indice => $item) {
                $idSku = intval($this->valor($item, "id_sku", 0));
                $cantidad = round(floatval($this->valor($item, "cantidad", 1)), 6);
                $sku = $this->consultarSkuVenta($db, $idSku);
                if (!$sku) {
                    $partidas[] = array(
                        "renglon" => $indice + 1,
                        "id_sku" => $idSku,
                        "bloqueos" => array("SKU no encontrado o inactivo")
                    );
                    $bloqueos[] = "SKU no encontrado o inactivo: " . $idSku;
                    continue;
                }

                $precio = $this->resolverPrecioSkuDryRun($db, $sku, $cliente, $canal, $idAlmacen, $schemaListasPendiente);
                $precioBase = round(floatval($precio["precio_base"]), 6);
                $precioLista = round(floatval($precio["precio_aplicado"]), 6);
                $precioFinal = $precioLista;
                $descuentoPartida = 0;
                $aplicaPartida = $tipoExcepcion === "descuento_general" || $idSkuObjetivo <= 0 || $idSkuObjetivo === intval($sku["id_sku"]);
                $bloqueosPartida = array();

                if ($tipoExcepcion === "precio_manual" && $aplicaPartida) {
                    if ($precioManual <= 0) {
                        $bloqueosPartida[] = "Precio manual debe ser mayor a cero";
                    } else {
                        $precioFinal = round($precioManual, 6);
                    }
                }
                if ($tipoExcepcion === "descuento_partida" && $aplicaPartida) {
                    if ($descuentoMonto <= 0 && $descuentoPorcentaje <= 0) {
                        $bloqueosPartida[] = "Captura descuento por monto o porcentaje";
                    }
                    $descuentoPartida = $this->calcularDescuentoComercial($precioLista * max(0, $cantidad), $descuentoMonto, $descuentoPorcentaje);
                }

                $importeLista = round(max(0, $cantidad) * max(0, $precioLista), 6);
                $importeFinal = round(max(0, $cantidad) * max(0, $precioFinal), 6);
                if ($tipoExcepcion === "descuento_partida" && $aplicaPartida) {
                    $importeFinal = round(max(0, $importeFinal - $descuentoPartida), 6);
                }
                $subtotalBase += round(max(0, $cantidad) * max(0, $precioBase), 6);
                $subtotalAplicado += $importeLista;
                $descuentoTotal += max(0, $importeLista - $importeFinal);

                if ($aplicaPartida && $precioFinal < $precioBase) {
                    $avisos[] = "La excepcion deja el SKU " . $sku["sku"] . " por debajo del precio base; validar margen minimo antes de venta real";
                }

                $partidas[] = array(
                    "renglon" => $indice + 1,
                    "id_sku" => intval($sku["id_sku"]),
                    "sku" => $sku["sku"],
                    "descripcion" => $sku["nombre_sku"],
                    "cantidad" => $cantidad,
                    "aplica_excepcion" => $aplicaPartida,
                    "precio_base" => $precioBase,
                    "precio_lista" => $precioLista,
                    "precio_final_estimado" => $precioFinal,
                    "descuento_estimado" => round(max(0, $importeLista - $importeFinal), 6),
                    "importe_lista" => $importeLista,
                    "importe_final_estimado" => $importeFinal,
                    "regla_precio_origen" => $precio["regla_precio_origen"],
                    "id_lista_precio" => $precio["id_lista_precio"],
                    "lista_precio_snapshot" => $precio["lista_precio_snapshot"],
                    "bloqueos" => $bloqueosPartida
                );
                if (!empty($bloqueosPartida)) {
                    $bloqueos = array_merge($bloqueos, $bloqueosPartida);
                }
            }

            if ($tipoExcepcion === "descuento_general") {
                if ($descuentoMonto <= 0 && $descuentoPorcentaje <= 0) {
                    $bloqueos[] = "Captura descuento general por monto o porcentaje";
                }
                $descuentoTotal = $this->calcularDescuentoComercial($subtotalAplicado, $descuentoMonto, $descuentoPorcentaje);
            }
            $totalEstimado = round(max(0, $subtotalAplicado - $descuentoTotal), 6);

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Excepcion comercial simulada" : "Excepcion comercial con bloqueos", array(
                "dry_run" => true,
                "id_almacen" => $idAlmacen,
                "canal" => $canal,
                "cliente" => $cliente,
                "tipo_excepcion" => $tipoExcepcion,
                "requiere_autorizacion" => true,
                "motivo" => $motivo,
                "codigo_autorizacion_recibido" => $codigoAutorizacion !== "",
                "partidas" => $partidas,
                "totales" => array(
                    "subtotal_base" => round($subtotalBase, 6),
                    "subtotal_lista" => round($subtotalAplicado, 6),
                    "descuento_total_estimado" => round($descuentoTotal, 6),
                    "total_estimado" => $totalEstimado
                ),
                "bloqueos" => array_values(array_unique($bloqueos)),
                "avisos" => array_values(array_unique($avisos)),
                "contrato" => array(
                    "backend_resuelve_precio_base_y_lista" => true,
                    "js_no_decide_precio_final" => true,
                    "venta_debe_guardar_precio_base" => true,
                    "venta_debe_guardar_precio_lista" => true,
                    "venta_debe_guardar_precio_aplicado" => true,
                    "venta_debe_guardar_descuento_motivo" => true,
                    "venta_debe_guardar_autorizacion" => true,
                    "venta_real_bloqueada_sin_permiso_supervisor" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: registrar una excepcion comercial autorizada sin crear venta ni mover caja/inventario.
     * Impacto: deja trazabilidad formal de precio manual/descuento para que una venta futura pueda consumirla.
     * Contrato: escribe solo `erp_ventas_excepciones_comerciales`; requiere politica activa y autorizador con permiso.
     */
    public function registrarExcepcionComercialAutorizada($datos = array()) {
        $db = $this->getConexion();
        try {
            $tablas = array("erp_ventas_politicas_comerciales", "erp_ventas_excepciones_comerciales");
            $faltantes = array();
            foreach ($tablas as $tabla) {
                if (!$this->tablaExiste($db, $tabla)) {
                    $faltantes[] = $tabla;
                }
            }
            if (!empty($faltantes)) {
                return $this->respuesta(false, "warning", "Esquema de excepciones comerciales pendiente", array(
                    "bloqueos" => array("Faltan tablas: " . implode(", ", $faltantes))
                ));
            }

            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $canal = trim((string) $this->valor($datos, "canal", "pos"));
            $tipoExcepcion = trim((string) $this->valor($datos, "tipo_excepcion", ""));
            $idUsuarioSolicita = intval($this->valor($datos, "solicitado_por", $this->valor($datos, "id_usuario", 0)));
            $idUsuarioAutoriza = intval($this->valor($datos, "autorizado_por", $this->valor($datos, "id_usuario_autoriza", $idUsuarioSolicita)));
            $codigoAutorizacion = trim((string) $this->valor($datos, "codigo_autorizacion", ""));
            $motivo = trim((string) $this->valor($datos, "motivo", ""));
            $bloqueos = array();

            if ($idUsuarioSolicita <= 0) {
                $bloqueos[] = "Usuario solicitante obligatorio";
            }
            if ($idUsuarioAutoriza <= 0) {
                $bloqueos[] = "Usuario autorizador obligatorio";
            }
            if ($idUsuarioAutoriza > 0 && !$this->usuarioTienePermisoDb($db, $idUsuarioAutoriza, "ventas.autorizar_excepcion_comercial")) {
                $bloqueos[] = "El usuario autorizador no tiene permiso ventas.autorizar_excepcion_comercial";
            }

            $dryRun = $this->excepcionComercialDryRun($datos);
            $depurar = isset($dryRun["depurar"]) && is_array($dryRun["depurar"]) ? $dryRun["depurar"] : array();
            $bloqueosDryRun = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
            if (!empty($bloqueosDryRun)) {
                $bloqueos = array_merge($bloqueos, $bloqueosDryRun);
            }

            $politica = $this->consultarPoliticaComercial($db, $tipoExcepcion, $canal, $idAlmacen);
            if (!$politica) {
                $bloqueos[] = "No existe politica comercial activa para la excepcion";
            }

            $totales = isset($depurar["totales"]) && is_array($depurar["totales"]) ? $depurar["totales"] : array();
            $subtotalLista = round(floatval($this->valor($totales, "subtotal_lista", 0)), 6);
            $descuentoTotal = round(floatval($this->valor($totales, "descuento_total_estimado", 0)), 6);
            $porcentajeDescuento = $subtotalLista > 0 ? round($descuentoTotal / $subtotalLista, 6) : 0;
            if ($politica) {
                $maxPorcentaje = round(floatval($this->valor($politica, "descuento_max_porcentaje", 0)), 6);
                $maxMonto = round(floatval($this->valor($politica, "descuento_max_monto", 0)), 6);
                if ($tipoExcepcion !== "precio_manual" && $maxPorcentaje > 0 && $porcentajeDescuento > $maxPorcentaje + 0.000001) {
                    $bloqueos[] = "El descuento excede el porcentaje maximo de la politica";
                }
                if ($tipoExcepcion !== "precio_manual" && $maxMonto > 0 && $descuentoTotal > $maxMonto + 0.000001) {
                    $bloqueos[] = "El descuento excede el monto maximo de la politica";
                }
            }

            if (!empty($bloqueos)) {
                return $this->respuesta(false, "warning", "Excepcion comercial no registrada por bloqueos", array(
                    "dry_run" => $dryRun,
                    "politica" => $politica,
                    "bloqueos" => array_values(array_unique($bloqueos))
                ));
            }

            $partidas = isset($depurar["partidas"]) && is_array($depurar["partidas"]) ? $depurar["partidas"] : array();
            $partidaAplicada = $this->partidaExcepcionPrincipal($partidas);
            $clienteDepurar = $this->valor($depurar, "cliente", array());
            $idCliente = intval($this->valor($clienteDepurar, "id_cliente", 0));
            $idClienteCrm = intval($this->valor($clienteDepurar, "id_cliente_crm", 0));
            $clienteCodigo = trim((string) $this->valor($clienteDepurar, "codigo_cliente", ""));
            $clienteNombre = trim((string) $this->valor($clienteDepurar, "nombre_publico", ""));
            $clienteIdentificador = trim((string) $this->valor($clienteDepurar, "identificador", ""));
            $clienteOrigen = trim((string) $this->valor($clienteDepurar, "origen_cliente", ""));
            $alcance = $tipoExcepcion === "descuento_general" ? "venta" : "partida";
            $precioBase = round(floatval($this->valor($partidaAplicada, "precio_base", 0)), 6);
            $precioLista = round(floatval($this->valor($partidaAplicada, "precio_lista", 0)), 6);
            $precioFinal = round(floatval($this->valor($partidaAplicada, "precio_final_estimado", $precioLista)), 6);
            $idSku = intval($this->valor($partidaAplicada, "id_sku", $this->valor($datos, "id_sku", 0)));
            $totalDespues = round(floatval($this->valor($totales, "total_estimado", 0)), 6);
            $duplicada = $this->buscarExcepcionComercialDuplicadaReciente($db, array(
                "id_cliente" => $idCliente,
                "id_cliente_crm" => $idClienteCrm,
                "id_sku" => $idSku,
                "tipo_excepcion" => $tipoExcepcion,
                "motivo" => $motivo,
                "codigo_autorizacion" => $codigoAutorizacion,
                "solicitado_por" => $idUsuarioSolicita,
                "autorizado_por" => $idUsuarioAutoriza,
                "precio_aplicado" => $precioFinal,
                "descuento_total" => $descuentoTotal,
                "subtotal_antes" => $subtotalLista,
                "total_despues" => $totalDespues
            ));
            if ($duplicada) {
                return $this->respuesta(false, "success", "Excepcion comercial autorizada registrada previamente", array(
                    "id_excepcion_comercial" => intval($duplicada["id_excepcion_comercial"]),
                    "folio" => $duplicada["folio"],
                    "estatus" => $duplicada["estatus"],
                    "id_politica_comercial" => intval($duplicada["id_politica_comercial"]),
                    "id_cliente_crm" => $idClienteCrm,
                    "tipo_excepcion" => $tipoExcepcion,
                    "alcance" => $alcance,
                    "totales" => $totales,
                    "duplicado_reciente" => true,
                    "no_crea_venta" => true,
                    "no_mueve_caja" => true,
                    "no_mueve_inventario" => true
                ));
            }
            $folio = $this->generarFolioExcepcionComercial($db);
            $columnasClienteCrm = "";
            $valoresClienteCrm = "";
            $paramsClienteCrm = array();
            if ($this->columnaExiste($db, "erp_ventas_excepciones_comerciales", "id_cliente_crm")) {
                $columnasClienteCrm = ", id_cliente_crm, cliente_codigo_snapshot, cliente_nombre_snapshot, cliente_identificador_snapshot, cliente_origen_snapshot";
                $valoresClienteCrm = ", :cliente_crm, :cliente_codigo, :cliente_nombre, :cliente_identificador, :cliente_origen";
                $paramsClienteCrm = array(
                    ":cliente_crm" => $idClienteCrm > 0 ? $idClienteCrm : null,
                    ":cliente_codigo" => $clienteCodigo !== "" ? $clienteCodigo : null,
                    ":cliente_nombre" => $clienteNombre !== "" ? $clienteNombre : null,
                    ":cliente_identificador" => $clienteIdentificador !== "" ? $clienteIdentificador : null,
                    ":cliente_origen" => $clienteOrigen !== "" ? $clienteOrigen : null
                );
            }

            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO erp_ventas_excepciones_comerciales
                (folio, id_politica_comercial, id_cliente$columnasClienteCrm, id_sku_erp, tipo_excepcion, alcance, estatus,
                 precio_base, precio_lista, precio_solicitado, precio_aplicado,
                 descuento_monto, descuento_porcentaje, descuento_total, subtotal_antes, total_despues,
                 margen_estimado_porcentaje, motivo, autorizacion_codigo, solicitado_por, autorizado_por,
                 fecha_autorizacion, datos_snapshot, observaciones, fecha_actualizacion)
                VALUES
                (:folio, :politica, :cliente$valoresClienteCrm, :sku, :tipo, :alcance, 'autorizada',
                 :precio_base, :precio_lista, :precio_solicitado, :precio_aplicado,
                 :descuento_monto, :descuento_porcentaje, :descuento_total, :subtotal_antes, :total_despues,
                 NULL, :motivo, :codigo, :solicitado_por, :autorizado_por,
                 CURRENT_TIMESTAMP, :snapshot, :observaciones, CURRENT_TIMESTAMP)");
            $paramsInsert = array(
                ":folio" => $folio,
                ":politica" => intval($politica["id_politica_comercial"]),
                ":cliente" => $idCliente > 0 ? $idCliente : null,
                ":sku" => $idSku > 0 ? $idSku : null,
                ":tipo" => $tipoExcepcion,
                ":alcance" => $alcance,
                ":precio_base" => $precioBase,
                ":precio_lista" => $precioLista,
                ":precio_solicitado" => $tipoExcepcion === "precio_manual" ? $precioFinal : $precioLista,
                ":precio_aplicado" => $precioFinal,
                ":descuento_monto" => round(floatval($this->valor($datos, "descuento_monto", 0)), 6),
                ":descuento_porcentaje" => round(floatval($this->valor($datos, "descuento_porcentaje", 0)), 6),
                ":descuento_total" => $descuentoTotal,
                ":subtotal_antes" => $subtotalLista,
                ":total_despues" => $totalDespues,
                ":motivo" => $motivo,
                ":codigo" => $codigoAutorizacion,
                ":solicitado_por" => $idUsuarioSolicita,
                ":autorizado_por" => $idUsuarioAutoriza,
                ":snapshot" => json_encode($depurar, JSON_UNESCAPED_UNICODE),
                ":observaciones" => trim((string) $this->valor($datos, "observaciones", "UAT excepcion comercial autorizada sin venta"))
            );
            $stmt->execute(array_merge($paramsInsert, $paramsClienteCrm));
            $idExcepcion = intval($db->lastInsertId());
            $db->commit();

            return $this->respuesta(false, "success", "Excepcion comercial autorizada registrada", array(
                "id_excepcion_comercial" => $idExcepcion,
                "folio" => $folio,
                "estatus" => "autorizada",
                "id_politica_comercial" => intval($politica["id_politica_comercial"]),
                "id_cliente_crm" => $idClienteCrm,
                "tipo_excepcion" => $tipoExcepcion,
                "alcance" => $alcance,
                "totales" => $totales,
                "no_crea_venta" => true,
                "no_mueve_caja" => true,
                "no_mueve_inventario" => true
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: simular el consumo de una excepcion comercial autorizada por una venta POS.
     * Impacto: define el contrato de descuento/precio manual antes de ligar la excepcion a venta/detalle.
     * Contrato: read-only; no actualiza excepcion, venta, detalle, caja ni inventario.
     */
    public function excepcionComercialConsumoDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $folio = trim((string) $this->valor($datos, "folio_excepcion", $this->valor($datos, "folio", "")));
            $idExcepcion = intval($this->valor($datos, "id_excepcion_comercial", 0));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $itemsOriginales = $this->decodificarItems($this->valor($datos, "items", array()));
            $pagos = $this->decodificarItems($this->valor($datos, "pagos", array()));
            $bloqueos = array();
            $avisos = array();

            if (!$this->tablaExiste($db, "erp_ventas_excepciones_comerciales")) {
                $bloqueos[] = "Esquema de excepciones comerciales pendiente";
            }
            if ($folio === "" && $idExcepcion <= 0) {
                $bloqueos[] = "Indica folio o id de excepcion comercial";
            }
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona almacen/punto de venta";
            }
            if ($idUsuario <= 0) {
                $bloqueos[] = "Usuario operador obligatorio";
            }
            if (empty($itemsOriginales)) {
                $bloqueos[] = "Agrega partidas al carrito";
            }

            $excepcion = null;
            if (empty($bloqueos)) {
                $excepcion = $this->consultarExcepcionComercialConsumo($db, $folio, $idExcepcion);
                if (!$excepcion) {
                    $bloqueos[] = "Excepcion comercial no encontrada";
                } else {
                    if ($excepcion["estatus"] !== "autorizada") {
                        $bloqueos[] = "La excepcion comercial no esta autorizada";
                    }
                    if (intval($excepcion["id_venta"]) > 0 || intval($excepcion["id_venta_detalle"]) > 0) {
                        $bloqueos[] = "La excepcion comercial ya fue consumida por una venta";
                    }
                    if (!in_array($excepcion["tipo_excepcion"], array("precio_manual", "descuento_partida", "descuento_general"), true)) {
                        $bloqueos[] = "Tipo de excepcion comercial no soportado para POS";
                    }
                }
            }

            $itemsBase = $this->normalizarItemsParaPrecioBackend($itemsOriginales);
            $prevalidacionBase = $this->prevalidarCarritoPos(array(
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "canal" => "pos",
                "id_cliente" => intval($this->valor($datos, "id_cliente", 0)),
                "identificador_cliente" => trim((string) $this->valor($datos, "identificador_cliente", "")),
                "items" => json_encode($itemsBase),
                "pagos" => json_encode($pagos),
                "exigir_pago_completo" => 0
            ));
            $depurarBase = isset($prevalidacionBase["depurar"]) && is_array($prevalidacionBase["depurar"]) ? $prevalidacionBase["depurar"] : array();
            $partidasBase = isset($depurarBase["partidas"]) && is_array($depurarBase["partidas"]) ? $depurarBase["partidas"] : array();
            $bloqueosBase = isset($depurarBase["bloqueos"]) && is_array($depurarBase["bloqueos"]) ? $depurarBase["bloqueos"] : array();
            foreach ($bloqueosBase as $bloqueoBase) {
                if (stripos($bloqueoBase, "Precio enviado por POS no coincide") === false) {
                    $bloqueos[] = $bloqueoBase;
                }
            }

            $partidasAjustadas = array();
            $subtotalBase = 0;
            $totalAjustado = 0;
            $descuentoTotal = 0;
            $renglonObjetivo = 0;

            if ($excepcion) {
                $idSkuExcepcion = intval($excepcion["id_sku_erp"]);
                foreach ($partidasBase as $partida) {
                    $subtotalPartidaBase = round(floatval($this->valor($partida, "subtotal", 0)), 6);
                    $subtotalBase += $subtotalPartidaBase;
                    $cantidad = round(floatval($this->valor($partida, "cantidad", 0)), 6);
                    $precioBaseBackend = round(floatval($this->valor($partida, "precio_unitario", 0)), 6);
                    $precioFinal = $precioBaseBackend;
                    $descuentoPartida = 0;
                    $aplica = $excepcion["tipo_excepcion"] === "descuento_general"
                        || ($idSkuExcepcion > 0 && $idSkuExcepcion === intval($this->valor($partida, "id_sku", 0)));

                    if ($aplica && $renglonObjetivo === 0) {
                        $renglonObjetivo = intval($this->valor($partida, "renglon", 0));
                    }
                    if ($aplica && $excepcion["tipo_excepcion"] === "precio_manual") {
                        $precioFinal = round(floatval($excepcion["precio_aplicado"]), 6);
                        $descuentoPartida = round(max(0, $subtotalPartidaBase - ($precioFinal * $cantidad)), 6);
                    } elseif ($aplica && $excepcion["tipo_excepcion"] === "descuento_partida") {
                        $descuentoPartida = round(floatval($excepcion["descuento_total"]), 6);
                    }

                    $totalPartida = $aplica && $excepcion["tipo_excepcion"] === "precio_manual"
                        ? round(max(0, $precioFinal * $cantidad), 6)
                        : round(max(0, ($precioFinal * $cantidad) - $descuentoPartida), 6);
                    $descuentoTotal += $descuentoPartida;
                    $totalAjustado += $totalPartida;
                    $partida["aplica_excepcion_comercial"] = $aplica;
                    $partida["id_excepcion_comercial"] = $aplica ? intval($excepcion["id_excepcion_comercial"]) : null;
                    $partida["folio_excepcion_comercial"] = $aplica ? $excepcion["folio"] : null;
                    $partida["tipo_excepcion_comercial"] = $aplica ? $excepcion["tipo_excepcion"] : null;
                    $partida["precio_unitario_original"] = $precioBaseBackend;
                    $partida["precio_unitario_final"] = $precioFinal;
                    $partida["descuento_excepcion"] = round($descuentoPartida, 6);
                    $partida["subtotal_original"] = $subtotalPartidaBase;
                    $partida["total_final"] = $totalPartida;
                    $partidasAjustadas[] = $partida;
                }

                if ($excepcion["tipo_excepcion"] === "descuento_general") {
                    $descuentoGeneral = round(floatval($excepcion["descuento_total"]), 6);
                    $descuentoTotal = $descuentoGeneral;
                    $totalAjustado = round(max(0, $subtotalBase - $descuentoGeneral), 6);
                }
                if ($renglonObjetivo === 0 && $excepcion["tipo_excepcion"] !== "descuento_general") {
                    $bloqueos[] = "La excepcion comercial no corresponde a ningun SKU del carrito";
                }
                if ($excepcion["tipo_excepcion"] === "precio_manual") {
                    $descuentoEsperado = round(floatval($excepcion["descuento_total"]), 6);
                    if (abs(round($descuentoTotal, 6) - $descuentoEsperado) > 0.0001) {
                        $avisos[] = "El descuento calculado difiere del snapshot autorizado; validar cantidad/precio antes de venta real";
                    }
                }
            }

            $pagadoTotal = 0;
            foreach ($pagos as $pago) {
                $pagadoTotal += max(0, floatval($this->valor($pago, "monto", 0)));
            }
            $saldo = round(max(0, $totalAjustado - $pagadoTotal), 6);
            $cambio = round(max(0, $pagadoTotal - $totalAjustado), 6);
            if ($totalAjustado > 0 && $pagadoTotal + 0.0001 < $totalAjustado && intval($this->valor($datos, "exigir_pago_completo", 1)) === 1) {
                $bloqueos[] = "Pago insuficiente para total con excepcion comercial";
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Consumo de excepcion comercial simulado" : "Consumo de excepcion comercial con bloqueos", array(
                "read_only" => true,
                "excepcion" => $excepcion,
                "prevalidacion_base" => $prevalidacionBase,
                "partidas" => $partidasAjustadas,
                "bloqueos" => array_values(array_unique($bloqueos)),
                "avisos" => array_values(array_unique($avisos)),
                "totales" => array(
                    "subtotal_original" => round($subtotalBase, 6),
                    "descuento_total" => round($descuentoTotal, 6),
                    "total_con_excepcion" => round($totalAjustado, 6),
                    "pagado_total" => round($pagadoTotal, 6),
                    "saldo_total" => $saldo,
                    "cambio" => $cambio
                ),
                "contrato_consumo_real" => array(
                    "bloquear_excepcion_for_update" => true,
                    "validar_estatus_autorizada" => true,
                    "validar_no_consumida" => true,
                    "insertar_detalle_con_id_excepcion" => true,
                    "actualizar_excepcion_a_aplicada_con_venta_y_detalle" => true,
                    "recalcular_totales_en_backend" => true,
                    "no_confiar_en_precio_js" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-04.
     * Proposito: generar corte formal de caja POS para impresion/reimpresion sin escribir BD.
     * Impacto: permite auditar turno, esperado, contado, diferencia, ventas, pagos y movimientos.
     * Contrato: read-only; no cierra turno, no ajusta caja, no crea evidencia y no mueve inventario.
     */
    public function corteTurnoFormalReadOnly($datos = array()) {
        try {
            $db = $this->getConexion();
            if (!$db || !$this->tablaExiste($db, "erp_pos_turnos")) {
                return $this->respuesta(false, "warning", "Esquema de turnos POS pendiente", array(
                    "read_only" => true,
                    "hallazgos" => array("Sin tabla erp_pos_turnos")
                ));
            }
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $folio = trim((string) $this->valor($datos, "folio", ""));
            if ($idTurno <= 0 && $folio === "") {
                return $this->respuesta(false, "warning", "Indica id_turno_caja o folio para generar corte", array(
                    "read_only" => true,
                    "hallazgos" => array("Sin turno solicitado")
                ));
            }

            $where = $idTurno > 0 ? "t.id_turno_caja=:referencia" : "t.folio=:referencia";
            $stmt = $db->prepare("SELECT t.*, a.codigo_almacen, a.almacen, a.nombre_comercial,
                    c.codigo caja_codigo, c.nombre caja_nombre,
                    ua.nombre_mostrar usuario_apertura, uc.nombre_mostrar usuario_cierre
                FROM erp_pos_turnos t
                LEFT JOIN erp_almacenes a ON a.id_almacen=t.id_almacen
                LEFT JOIN erp_pos_cajas c ON c.id_caja=t.id_caja
                LEFT JOIN sys_usuarios ua ON ua.id_usuario=t.id_usuario_apertura
                LEFT JOIN sys_usuarios uc ON uc.id_usuario=t.id_usuario_cierre
                WHERE {$where}
                LIMIT 1");
            $stmt->execute(array(":referencia" => $idTurno > 0 ? $idTurno : $folio));
            $turno = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$turno) {
                return $this->respuesta(false, "warning", "Turno POS no encontrado", array(
                    "read_only" => true,
                    "hallazgos" => array("Turno no encontrado")
                ));
            }

            $idTurno = intval($this->valor($turno, "id_turno_caja", 0));
            $idCaja = intval($this->valor($turno, "id_caja", 0));
            $ventas = array("operaciones" => 0, "subtotal" => 0, "descuento" => 0, "impuestos" => 0, "total" => 0, "pagado" => 0, "saldo" => 0);
            $pagos = array();
            $movimientos = array();

            if ($this->tablaExiste($db, "erp_ventas")) {
                $stmt = $db->prepare("SELECT COUNT(*) operaciones,
                        COALESCE(SUM(subtotal), 0) subtotal,
                        COALESCE(SUM(descuento_total), 0) descuento,
                        COALESCE(SUM(impuestos_total), 0) impuestos,
                        COALESCE(SUM(total), 0) total,
                        COALESCE(SUM(pagado_total), 0) pagado,
                        COALESCE(SUM(saldo_total), 0) saldo
                    FROM erp_ventas
                    WHERE id_turno_caja=:turno AND id_caja=:caja
                        AND estatus NOT IN ('cancelada')");
                $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja));
                $ventas = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if ($this->tablaExiste($db, "erp_ventas_pagos")) {
                $stmt = $db->prepare("SELECT metodo_pago, tipo_pago, COUNT(*) operaciones, COALESCE(SUM(monto), 0) monto
                    FROM erp_ventas_pagos
                    WHERE id_turno_caja=:turno AND id_caja=:caja AND estatus='registrado'
                    GROUP BY metodo_pago, tipo_pago
                    ORDER BY metodo_pago, tipo_pago");
                $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja));
                $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            if ($this->tablaExiste($db, "erp_pos_movimientos_caja")) {
                $stmt = $db->prepare("SELECT tipo, motivo, COUNT(*) operaciones, COALESCE(SUM(monto), 0) monto
                    FROM erp_pos_movimientos_caja
                    WHERE id_turno_caja=:turno
                    GROUP BY tipo, motivo
                    ORDER BY tipo, motivo");
                $stmt->execute(array(":turno" => $idTurno));
                $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $corte = $this->formatearCorteTurno($turno, $ventas, $pagos, $movimientos);
            return $this->respuesta(false, "success", "Corte de caja generado", array(
                "read_only" => true,
                "turno" => $turno,
                "ventas" => $ventas,
                "pagos_por_metodo" => $pagos,
                "movimientos_por_tipo" => $movimientos,
                "corte_texto" => $corte["texto"],
                "corte_lineas" => $corte["lineas"],
                "hallazgos" => $corte["hallazgos"],
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_cierra_turno" => true,
                    "no_ajusta_caja" => true,
                    "no_mueve_inventario" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: simular alta rapida de cliente POS antes de autorizar escritura real.
     * Impacto: evita duplicados y mantiene cliente como entidad robusta, no como telefono suelto.
     * Contrato: dry-run; no crea cliente, identificador, consentimiento ni evento.
     */
    public function clienteAltaRapidaDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $nombre = trim((string) $this->valor($datos, "nombre_publico", $this->valor($datos, "nombre", "")));
            $identificador = trim((string) $this->valor($datos, "identificador", $this->valor($datos, "telefono", "")));
            $tipoIdentificador = trim((string) $this->valor($datos, "tipo_identificador", ""));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $consentimientoContacto = intval($this->valor($datos, "consentimiento_contacto", 0)) === 1;
            $normalizado = $this->normalizarIdentificadorCliente($identificador);
            $bloqueos = array();
            $avisos = array();
            $tablasRequeridas = array("erp_clientes", "erp_clientes_identificadores");
            $faltantes = array();

            foreach ($tablasRequeridas as $tabla) {
                if (!$this->tablaExiste($db, $tabla)) {
                    $faltantes[] = $tabla;
                }
            }
            if (!empty($faltantes)) {
                $bloqueos[] = "Esquema de clientes POS pendiente de autorizacion";
            }
            if ($nombre === "" || strlen($nombre) < 2) {
                $bloqueos[] = "Captura nombre publico o alias del cliente";
            }
            if ($identificador === "" || $normalizado === "") {
                $bloqueos[] = "Captura telefono, correo o codigo de cliente";
            }
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona sucursal/almacen de alta";
            }
            if ($tipoIdentificador === "") {
                $tipoIdentificador = $this->tipoIdentificadorCliente($identificador);
            }
            if (!in_array($tipoIdentificador, array("telefono", "correo", "codigo"), true)) {
                $bloqueos[] = "Tipo de identificador no soportado para alta rapida";
            }
            if ($tipoIdentificador === "telefono" && strlen($normalizado) < 10) {
                $bloqueos[] = "Telefono incompleto para alta rapida";
            }
            if ($tipoIdentificador === "correo" && strpos($normalizado, "@") === false) {
                $bloqueos[] = "Correo invalido para alta rapida";
            }
            if (!$consentimientoContacto) {
                $avisos[] = "Sin consentimiento de contacto: usar solo para identificacion operativa, no marketing";
            }

            $coincidencias = array();
            if (empty($faltantes) && $normalizado !== "") {
                $stmt = $db->prepare("SELECT c.id_cliente, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
                           i.tipo, i.valor, i.valor_normalizado
                    FROM erp_clientes_identificadores i
                    INNER JOIN erp_clientes c ON c.id_cliente=i.id_cliente
                    WHERE i.valor_normalizado=:valor AND i.estatus='activo'
                    ORDER BY i.principal DESC, c.id_cliente ASC
                    LIMIT 10");
                $stmt->execute(array(":valor" => $normalizado));
                $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($coincidencias)) {
                    $bloqueos[] = "Ya existe cliente con ese identificador; selecciona coincidencia antes de crear";
                }
            }

            $codigoSugerido = empty($faltantes) ? $this->sugerirCodigoClientePos($db) : "CL-POS-" . date("Ymd") . "-###";
            $clientePropuesto = array(
                "codigo_cliente" => $codigoSugerido,
                "tipo_cliente" => "persona",
                "nombre_publico" => $nombre,
                "estatus" => "activo",
                "calidad_datos" => "express",
                "creado_desde" => "pos",
                "id_sucursal_alta" => $idAlmacen > 0 ? $idAlmacen : null,
                "creado_por" => $idUsuario > 0 ? $idUsuario : null
            );
            $identificadorPropuesto = array(
                "tipo" => $tipoIdentificador,
                "valor" => $identificador,
                "valor_normalizado" => $normalizado,
                "principal" => 1,
                "estatus" => "activo"
            );

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Alta rapida de cliente validada en dry-run" : "Alta rapida de cliente con bloqueos", array(
                "dry_run" => true,
                "puede_crear" => empty($bloqueos),
                "bloqueos" => array_values(array_unique($bloqueos)),
                "avisos" => array_values(array_unique($avisos)),
                "faltantes" => $faltantes,
                "coincidencias" => $coincidencias,
                "cliente_propuesto" => $clientePropuesto,
                "identificador_propuesto" => $identificadorPropuesto,
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "crear_erp_clientes" => true,
                    "crear_erp_clientes_identificadores" => true,
                    "no_mezcla_legacy_ecommerce" => true,
                    "requiere_autorizacion_para_apply" => true,
                    "venta_puede_continuar_sin_cliente" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-28.
     * Proposito: crear cliente express POS con identificador principal despues de autorizacion externa.
     * Impacto: Clientes/POS; habilita historial, listas, apartados y garantias sin mezclar legacy/ecommerce.
     * Contrato: escribe BD; debe ejecutarse dentro de flujo autorizado con respaldo externo.
     */
    public function clienteAltaRapidaCrearAutorizado($datos = array()) {
        $db = $this->getConexion();
        $lockName = null;
        try {
            $preflight = $this->clienteAltaRapidaDryRun($datos);
            $depurar = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
            $bloqueos = isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array();
            if (!empty($preflight["error"]) || !empty($bloqueos) || empty($depurar["puede_crear"])) {
                return $this->respuesta(false, "warning", "Alta rapida bloqueada por preflight", array(
                    "preflight" => $preflight,
                    "bloqueos" => $bloqueos
                ));
            }

            $cliente = $depurar["cliente_propuesto"];
            $identificador = $depurar["identificador_propuesto"];
            $normalizado = trim((string) $identificador["valor_normalizado"]);
            $lockName = "erp_cliente_ident_" . sha1($normalizado);
            $stmtLock = $db->prepare("SELECT GET_LOCK(:lock_name, 10)");
            $stmtLock->execute(array(":lock_name" => $lockName));
            if (intval($stmtLock->fetchColumn()) !== 1) {
                return $this->respuesta(false, "warning", "No se pudo bloquear identificador para alta rapida", array(
                    "bloqueos" => array("lock_identificador_no_disponible")
                ));
            }

            $db->beginTransaction();
            $stmtDuplicado = $db->prepare("SELECT c.id_cliente, c.codigo_cliente, c.nombre_publico
                FROM erp_clientes_identificadores i
                INNER JOIN erp_clientes c ON c.id_cliente=i.id_cliente
                WHERE i.valor_normalizado=:valor AND i.estatus='activo'
                LIMIT 1
                FOR UPDATE");
            $stmtDuplicado->execute(array(":valor" => $normalizado));
            $duplicado = $stmtDuplicado->fetch(PDO::FETCH_ASSOC);
            if ($duplicado) {
                throw new Exception("Ya existe cliente con ese identificador: " . $duplicado["codigo_cliente"]);
            }

            $codigo = $this->sugerirCodigoClientePos($db);
            $stmtCliente = $db->prepare("INSERT INTO erp_clientes
                (codigo_cliente, tipo_cliente, nombre_publico, estatus, calidad_datos,
                 creado_desde, id_sucursal_alta, creado_por, fecha_actualizacion)
                VALUES (:codigo, :tipo, :nombre, 'activo', 'express',
                 'pos', :almacen, :usuario, CURRENT_TIMESTAMP)");
            $stmtCliente->execute(array(
                ":codigo" => $codigo,
                ":tipo" => $cliente["tipo_cliente"],
                ":nombre" => $cliente["nombre_publico"],
                ":almacen" => $cliente["id_sucursal_alta"],
                ":usuario" => $cliente["creado_por"]
            ));
            $idCliente = intval($db->lastInsertId());

            $stmtIdentificador = $db->prepare("INSERT INTO erp_clientes_identificadores
                (id_cliente, tipo, valor, valor_normalizado, principal, estatus)
                VALUES (:cliente, :tipo, :valor, :normalizado, 1, 'activo')");
            $stmtIdentificador->execute(array(
                ":cliente" => $idCliente,
                ":tipo" => $identificador["tipo"],
                ":valor" => $identificador["valor"],
                ":normalizado" => $normalizado
            ));
            $idIdentificador = intval($db->lastInsertId());

            $db->commit();
            $this->liberarLockCliente($db, $lockName);
            return $this->respuesta(false, "success", "Cliente POS creado en alta rapida", array(
                "id_cliente" => $idCliente,
                "codigo_cliente" => $codigo,
                "id_cliente_identificador" => $idIdentificador,
                "nombre_publico" => $cliente["nombre_publico"],
                "identificador" => $identificador,
                "contrato" => array(
                    "crea_erp_clientes" => true,
                    "crea_erp_clientes_identificadores" => true,
                    "no_crea_contactos_completos" => true,
                    "no_mezcla_legacy_ecommerce" => true,
                    "calidad_datos" => "express"
                )
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            if ($lockName !== null) {
                $this->liberarLockCliente($db, $lockName);
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: simular anticipo/abono de apartado sin registrar pagos ni movimientos de caja.
     * Impacto: prepara flujo de apartados con saldo, caja, turno y trazabilidad.
     * Contrato: dry-run; no crea abono, no reduce saldo y no mueve inventario.
     */
    public function apartadoAbonoDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idCaja = intval($this->valor($datos, "id_caja", 0));
            $idTurno = intval($this->valor($datos, "id_turno_caja", 0));
            $folio = trim((string) $this->valor($datos, "folio", ""));
            $idVenta = intval($this->valor($datos, "id_venta", 0));
            $monto = round(floatval($this->valor($datos, "monto_abono", 0)), 6);
            $idMetodoPago = intval($this->valor($datos, "id_metodo_pago", 0));
            $referencia = trim((string) $this->valor($datos, "referencia", ""));
            $bloqueos = array();
            $avisos = array();
            $venta = null;
            $schemaPendiente = !$this->schemaVentaPosCompleto($db) || !$this->tablaExiste($db, "erp_ventas_eventos");
            if ($idAlmacen <= 0) {
                $bloqueos[] = "Selecciona almacen/punto de venta";
            }
            if ($monto <= 0) {
                $bloqueos[] = "El abono debe ser mayor a cero";
            }
            if ($idVenta <= 0 && $folio === "") {
                $bloqueos[] = "Indica folio o id_venta del apartado";
            }
            $bloqueos = array_merge($bloqueos, $this->validarCajaOperativa($db, $idAlmacen, $idCaja));
            $bloqueos = array_merge($bloqueos, $this->validarTurnoOperativo($db, $idAlmacen, $idCaja, $idTurno));
            $metodo = null;
            foreach ($this->listarMetodosPago($db) as $metodoActual) {
                if (intval($metodoActual["id_metodo_pago"]) === $idMetodoPago) {
                    $metodo = $metodoActual;
                    break;
                }
            }
            if (!$metodo) {
                $bloqueos[] = "Metodo de pago invalido para abono";
            } elseif (stripos($metodo["metodo_pago"], "transfer") !== false && $referencia === "") {
                $bloqueos[] = "Captura referencia de transferencia";
            }
            if ($schemaPendiente) {
                $bloqueos[] = "Esquema de apartados/abonos pendiente de autorizacion";
            }
            if (!$schemaPendiente && ($idVenta > 0 || $folio !== "")) {
                $where = $idVenta > 0 ? "id_venta=:referencia" : "folio=:referencia";
                $stmt = $db->prepare("SELECT id_venta, folio, tipo_documento, estatus, total, pagado_total, saldo_total, id_almacen, id_caja
                    FROM erp_ventas
                    WHERE {$where}
                    LIMIT 1");
                $stmt->execute(array(":referencia" => $idVenta > 0 ? $idVenta : $folio));
                $venta = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$venta) {
                    $bloqueos[] = "Pedido/apartado no encontrado";
                } else {
                    $tipoDocumento = trim((string) $this->valor($venta, "tipo_documento", ""));
                    $estatus = trim((string) $this->valor($venta, "estatus", ""));
                    $saldo = round(floatval($this->valor($venta, "saldo_total", 0)), 6);
                    if (!in_array($tipoDocumento, array("pedido", "apartado"), true)) {
                        $bloqueos[] = "El folio no corresponde a pedido/apartado";
                    }
                    if (in_array($estatus, array("cancelado", "devuelta", "entregado"), true)) {
                        $bloqueos[] = "El pedido/apartado no admite abonos por estatus " . $estatus;
                    }
                    if ($saldo <= 0) {
                        $bloqueos[] = "El pedido/apartado no tiene saldo pendiente";
                    } elseif ($monto > $saldo) {
                        $avisos[] = "El abono excede el saldo pendiente; el flujo real debe tratarlo como liquidacion/cambio o bloquearlo segun politica";
                    }
                }
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de abono valido" : "Dry-run de abono bloqueado", array(
                "dry_run" => true,
                "schema_pendiente" => $schemaPendiente,
                "folio" => $folio,
                "id_venta" => $idVenta,
                "venta" => $venta ?: null,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "abono" => array(
                    "tipo_pago" => "abono",
                    "id_metodo_pago" => $idMetodoPago,
                    "metodo_pago" => $metodo ? $metodo["metodo_pago"] : "",
                    "monto" => $monto,
                    "referencia" => $referencia
                ),
                "bloqueos" => array_values(array_unique($bloqueos)),
                "avisos" => array_values(array_unique($avisos)),
                "contrato_abono" => array(
                    "crear_erp_ventas_pagos_tipo_abono" => true,
                    "crear_erp_pos_movimientos_caja_ingreso" => true,
                    "crear_erp_ventas_eventos" => true,
                    "actualizar_pagado_total_y_saldo_total" => true,
                    "no_crear_venta_nueva" => true,
                    "no_mover_inventario_hasta_entrega_o_politica_definida" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-05.
     * Proposito: crear pedido/apartado POS real con reserva y anticipo opcional.
     * Impacto: inserta venta ERP, detalle, reservas, pago/caja y eventos; no descuenta kardex de salida hasta entrega.
     * Contrato: escritura real transaccional; requiere usuario, asignacion POS, turno abierto si hay anticipo y prevalidacion sin bloqueos.
     */
    public function pedidoGuardarReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        if ($idUsuario <= 0) {
            return $this->respuesta(true, "warning", "Usuario operador obligatorio", array("bloqueos" => array("usuario_obligatorio")));
        }
        if (!$this->schemaPedidosApartadosCompleto($db)) {
            return $this->respuesta(true, "warning", "Esquema pedidos/apartados pendiente", array("bloqueos" => array("schema_pedidos_apartados_pendiente")));
        }

        $asignacion = $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
        $depurarAsignacion = $this->valor($asignacion, "depurar", array());
        $datosAsignacion = $this->valor($depurarAsignacion, "asignacion", array());
        $turno = $this->valor($depurarAsignacion, "turno_abierto", array());
        if (empty($datosAsignacion)) {
            return $this->respuesta(true, "warning", "No hay asignacion POS activa para el usuario", array("bloqueos" => array("asignacion_pos_pendiente")));
        }
        if (empty($turno)) {
            return $this->respuesta(true, "warning", "No hay turno abierto para registrar anticipo/reserva POS", array("bloqueos" => array("turno_abierto_pendiente")));
        }

        $datos["id_almacen"] = intval($this->valor($datosAsignacion, "id_almacen", 0));
        $datos["id_caja"] = intval($this->valor($datosAsignacion, "id_caja", 0));
        $datos["id_turno_caja"] = intval($this->valor($turno, "id_turno_caja", 0));
        $datos["canal"] = "pedido_tienda";
        $dryRun = $this->pedidoReservaDryRun($datos);
        $bloqueos = $this->valorRutaPosReal($dryRun, array("depurar", "bloqueos"), array());
        if (!empty($bloqueos)) {
            return $this->respuesta(false, "warning", "Pedido/apartado real bloqueado por prevalidacion", array("bloqueos" => $bloqueos));
        }

        $tipoDocumento = trim((string) $this->valor($datos, "tipo_documento", "apartado"));
        $clienteNombre = trim((string) $this->valor($datos, "cliente_nombre_publico", $this->valor($datos, "cliente", "Cliente mostrador")));
        $identificadorCliente = trim((string) $this->valor($datos, "identificador_cliente", ""));
        $idCliente = intval($this->valor($datos, "id_cliente", 0));
        $fechaCompromiso = trim((string) $this->valor($datos, "fecha_entrega_compromiso", ""));
        $prevalidacion = $this->valorRutaPosReal($dryRun, array("depurar", "prevalidacion"), array());
        $partidas = $this->valorRutaPosReal($prevalidacion, array("depurar", "partidas"), array());
        $pagos = $this->valorRutaPosReal($prevalidacion, array("depurar", "pagos"), array());
        $totales = $this->valorRutaPosReal($prevalidacion, array("depurar", "totales"), array());
        $politica = $this->valorRutaPosReal($dryRun, array("depurar", "politica_apartado"), array());
        $propuestaReserva = $this->valorRutaPosReal($dryRun, array("depurar", "propuesta_reserva", "reservas"), array());

        try {
            $db->beginTransaction();
            $turnoBloqueado = $this->bloquearTurnoPosReal($db, intval($datos["id_turno_caja"]), intval($datos["id_caja"]), intval($datos["id_almacen"]));
            if (!$turnoBloqueado) {
                throw new Exception("El turno ya no esta abierto para pedidos/apartados");
            }

            $folio = $this->generarFolioVentaPosReal($db, $tipoDocumento === "apartado" ? "APT" : "PED");
            $subtotal = $this->redondearPosReal($this->valor($totales, "subtotal", 0));
            $total = $this->redondearPosReal($this->valor($totales, "total_estimado", $subtotal));
            $pagado = $this->redondearPosReal(min($total, $this->valor($totales, "pagado_total", 0)));
            $saldo = $this->redondearPosReal(max(0, $total - $pagado));
            $estatus = $saldo <= 0.0001 ? "pagado" : ($pagado > 0 ? "pendiente_pago" : "reservado");
            $fechaVencimiento = null;
            if (!empty($politica) && intval($this->valor($politica, "dias_vigencia", 0)) > 0) {
                $fechaVencimiento = date("Y-m-d H:i:s", strtotime("+" . intval($politica["dias_vigencia"]) . " days"));
            }

            $stmt = $db->prepare("INSERT INTO erp_ventas
                (folio, canal, tipo_documento, estatus, id_almacen, id_caja, id_turno_caja,
                 id_cliente, cliente_nombre_publico, cliente_identificador_publico,
                 subtotal, descuento_total, impuestos_total, total, pagado_total, saldo_total,
                 anticipo_minimo, fecha_vencimiento, politica_apartado_snapshot,
                 fecha_entrega_compromiso, creado_por, observaciones)
                VALUES (:folio, 'pedido_tienda', :tipo, :estatus, :almacen, :caja, :turno,
                 :cliente_id, :cliente, :identificador, :subtotal, 0, 0, :total, :pagado, :saldo,
                 :anticipo_minimo, :vencimiento, :politica, :compromiso, :usuario, :observaciones)");
            $stmt->execute(array(
                ":folio" => $folio,
                ":tipo" => $tipoDocumento,
                ":estatus" => $estatus,
                ":almacen" => intval($datos["id_almacen"]),
                ":caja" => intval($datos["id_caja"]),
                ":turno" => intval($datos["id_turno_caja"]),
                ":cliente_id" => $idCliente > 0 ? $idCliente : null,
                ":cliente" => $clienteNombre !== "" ? $clienteNombre : "Cliente mostrador",
                ":identificador" => $identificadorCliente !== "" ? $identificadorCliente : null,
                ":subtotal" => $subtotal,
                ":total" => $total,
                ":pagado" => $pagado,
                ":saldo" => $saldo,
                ":anticipo_minimo" => $this->redondearPosReal($this->valorRutaPosReal($dryRun, array("depurar", "anticipo_minimo"), 0)),
                ":vencimiento" => $fechaVencimiento,
                ":politica" => !empty($politica) ? json_encode($politica, JSON_UNESCAPED_UNICODE) : null,
                ":compromiso" => $fechaCompromiso !== "" ? substr($fechaCompromiso, 0, 10) . " 23:59:59" : null,
                ":usuario" => $idUsuario,
                ":observaciones" => trim((string) $this->valor($datos, "observaciones", "Pedido/apartado POS"))
            ));
            $idVenta = intval($db->lastInsertId());

            $reservasPorRenglon = array();
            foreach ($propuestaReserva as $reserva) {
                $renglon = intval($this->valor($reserva, "renglon", 0));
                if (!isset($reservasPorRenglon[$renglon])) {
                    $reservasPorRenglon[$renglon] = array();
                }
                $reservasPorRenglon[$renglon][] = $reserva;
            }

            $reservasCreadas = array();
            foreach ($partidas as $partida) {
                $sku = $this->consultarSkuVenta($db, intval($this->valor($partida, "id_sku", 0)));
                if (!$sku) {
                    throw new Exception("SKU no encontrado al crear pedido/apartado");
                }
                $renglon = intval($this->valor($partida, "renglon", 0));
                $stmt = $db->prepare("INSERT INTO erp_ventas_detalle
                    (id_venta, renglon, id_producto_erp, id_sku_erp, sku, descripcion,
                     tipo_partida, controla_inventario, modo_salida, cantidad_venta,
                     unidad_venta, cantidad_base, unidad_base, precio_unitario,
                     precio_unitario_sin_impuesto, precio_base, precio_aplicado,
                     descuento, impuestos, subtotal, total, estatus)
                    VALUES (:venta, :renglon, :producto, :sku_id, :sku, :descripcion,
                     'producto', :controla, :modo, :cantidad, :unidad, :cantidad_base,
                     :unidad, :precio, :precio, :precio, :precio, 0, 0, :subtotal, :total, 'reservada')");
                $stmt->execute(array(
                    ":venta" => $idVenta,
                    ":renglon" => $renglon,
                    ":producto" => intval($sku["id_producto_erp"]),
                    ":sku_id" => intval($sku["id_sku"]),
                    ":sku" => $sku["sku"],
                    ":descripcion" => $this->valor($partida, "descripcion", $sku["nombre_sku"]),
                    ":controla" => intval($this->valor($partida, "controla_inventario", 0)),
                    ":modo" => $this->valorRutaPosReal($partida, array("plan_salida_inventario", "modo"), "existencia_agregada"),
                    ":cantidad" => $this->redondearPosReal($this->valor($partida, "cantidad", 0)),
                    ":cantidad_base" => $this->redondearPosReal($this->valor($partida, "cantidad", 0)),
                    ":unidad" => $sku["unidad_venta_label"],
                    ":precio" => $this->redondearPosReal($this->valor($partida, "precio_unitario", 0)),
                    ":subtotal" => $this->redondearPosReal($this->valor($partida, "subtotal", 0)),
                    ":total" => $this->redondearPosReal($this->valor($partida, "subtotal", 0))
                ));
                $idDetalle = intval($db->lastInsertId());
                foreach ($this->valor($reservasPorRenglon, $renglon, array()) as $reservaPlan) {
                    $reservasCreadas[] = $this->crearReservaPedidoPosReal($db, $idVenta, $idDetalle, $folio, $reservaPlan, $fechaVencimiento, $idUsuario);
                }
            }

            $pagosRegistrados = $this->registrarPagoPedidoPosReal($db, $idVenta, $folio, $datos, $pagos, $pagado, $pagado >= $total - 0.0001 ? "liquidacion" : "anticipo", $idUsuario);
            $montoCaja = 0;
            foreach ($pagosRegistrados as $pago) {
                $montoCaja += floatval($pago["monto_aplicado"]);
            }
            if ($montoCaja > 0) {
                $db->prepare("UPDATE erp_pos_turnos SET monto_esperado=ROUND(monto_esperado+:monto,6) WHERE id_turno_caja=:turno")
                    ->execute(array(":monto" => $this->redondearPosReal($montoCaja), ":turno" => intval($datos["id_turno_caja"])));
            }
            $this->registrarEventoVentaPosReal($db, $idVenta, $folio, $tipoDocumento . "_creado", null, $estatus, $pagado, "reservas:" . count($reservasCreadas), array("reservas" => $reservasCreadas), $idUsuario);

            $db->commit();
            return $this->respuesta(false, "success", ucfirst($tipoDocumento) . " POS creado", array(
                "folio" => $folio,
                "id_venta" => $idVenta,
                "estatus" => $estatus,
                "totales" => array("total" => $total, "pagado_total" => $pagado, "saldo_total" => $saldo),
                "reservas" => $reservasCreadas,
                "pagos" => $pagosRegistrados
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage(), array("rollback" => true, "modo" => "pedido_apartado_real"));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-05.
     * Proposito: registrar abono/liquidacion real para pedido/apartado POS.
     * Impacto: inserta pago, movimiento de caja, actualiza saldo/estatus y registra evento.
     * Contrato: escritura real transaccional; no mueve inventario.
     */
    public function apartadoAbonoReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        if ($idUsuario <= 0) {
            return $this->respuesta(true, "warning", "Usuario operador obligatorio");
        }
        $asignacion = $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
        $depurarAsignacion = $this->valor($asignacion, "depurar", array());
        $datosAsignacion = $this->valor($depurarAsignacion, "asignacion", array());
        $turno = $this->valor($depurarAsignacion, "turno_abierto", array());
        if (empty($datosAsignacion) || empty($turno)) {
            return $this->respuesta(true, "warning", "Asignacion POS y turno abierto son obligatorios");
        }
        $datos["id_almacen"] = intval($this->valor($datosAsignacion, "id_almacen", 0));
        $datos["id_caja"] = intval($this->valor($datosAsignacion, "id_caja", 0));
        $datos["id_turno_caja"] = intval($this->valor($turno, "id_turno_caja", 0));
        $dryRun = $this->apartadoAbonoDryRun($datos);
        $bloqueos = $this->valorRutaPosReal($dryRun, array("depurar", "bloqueos"), array());
        if (!empty($bloqueos)) {
            return $this->respuesta(false, "warning", "Abono real bloqueado por prevalidacion", array("bloqueos" => $bloqueos));
        }
        try {
            $db->beginTransaction();
            $venta = $this->bloquearPedidoApartadoPosReal($db, intval($this->valor($datos, "id_venta", 0)), trim((string) $this->valor($datos, "folio", "")));
            if (!$venta) {
                throw new Exception("Pedido/apartado no encontrado");
            }
            $saldo = $this->redondearPosReal($this->valor($venta, "saldo_total", 0));
            $monto = $this->redondearPosReal($this->valor($datos, "monto_abono", 0));
            if ($monto <= 0 || $monto > $saldo + 0.0001) {
                throw new Exception("Monto de abono invalido para el saldo pendiente");
            }
            $tipoPago = $monto + 0.0001 >= $saldo ? "liquidacion" : "abono";
            $pagos = array(array(
                "id_metodo_pago" => intval($this->valor($datos, "id_metodo_pago", 0)),
                "metodo_pago" => $this->valorRutaPosReal($dryRun, array("depurar", "abono", "metodo_pago"), ""),
                "monto" => $monto,
                "referencia" => trim((string) $this->valor($datos, "referencia", ""))
            ));
            $pagosRegistrados = $this->registrarPagoPedidoPosReal($db, intval($venta["id_venta"]), $venta["folio"], array(
                "id_almacen" => intval($datos["id_almacen"]),
                "id_caja" => intval($datos["id_caja"]),
                "id_turno_caja" => intval($datos["id_turno_caja"])
            ), $pagos, $monto, $tipoPago, $idUsuario);
            $nuevoPagado = $this->redondearPosReal(floatval($venta["pagado_total"]) + $monto);
            $nuevoSaldo = $this->redondearPosReal(max(0, floatval($venta["total"]) - $nuevoPagado));
            $nuevoEstatus = $nuevoSaldo <= 0.0001 ? "pagado" : "pendiente_pago";
            $db->prepare("UPDATE erp_ventas
                SET pagado_total=:pagado, saldo_total=:saldo, estatus=:estatus, fecha_actualizacion=NOW()
                WHERE id_venta=:venta")
                ->execute(array(":pagado" => $nuevoPagado, ":saldo" => $nuevoSaldo, ":estatus" => $nuevoEstatus, ":venta" => intval($venta["id_venta"])));
            $db->prepare("UPDATE erp_pos_turnos SET monto_esperado=ROUND(monto_esperado+:monto,6) WHERE id_turno_caja=:turno")
                ->execute(array(":monto" => $monto, ":turno" => intval($datos["id_turno_caja"])));
            $this->registrarEventoVentaPosReal($db, intval($venta["id_venta"]), $venta["folio"], $tipoPago . "_registrada", $venta["estatus"], $nuevoEstatus, $monto, "", array("saldo_anterior" => $saldo, "saldo_nuevo" => $nuevoSaldo), $idUsuario);
            $db->commit();
            return $this->respuesta(false, "success", "Abono POS registrado", array(
                "folio" => $venta["folio"],
                "id_venta" => intval($venta["id_venta"]),
                "estatus" => $nuevoEstatus,
                "pagado_total" => $nuevoPagado,
                "saldo_total" => $nuevoSaldo,
                "pagos" => $pagosRegistrados
            ));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage(), array("rollback" => true, "modo" => "abono_apartado_real"));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-05.
     * Proposito: entregar pedido/apartado POS liquidado consumiendo reservas y generando kardex.
     * Impacto: cierra reservas, descuenta existencia apartada y deja trazabilidad por detalle.
     * Contrato: escritura real transaccional; no registra pagos.
     */
    public function pedidoEntregarReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        try {
            $db->beginTransaction();
            $venta = $this->bloquearPedidoApartadoPosReal($db, intval($this->valor($datos, "id_venta", 0)), trim((string) $this->valor($datos, "folio", "")));
            if (!$venta) {
                throw new Exception("Pedido/apartado no encontrado");
            }
            if (!in_array($venta["tipo_documento"], array("pedido", "apartado"), true) || in_array($venta["estatus"], array("entregado", "cancelado"), true)) {
                throw new Exception("El documento no admite entrega");
            }
            $politica = json_decode($this->valor($venta, "politica_apartado_snapshot", "[]"), true);
            $permiteSinLiquidar = is_array($politica) && intval($this->valor($politica, "permite_entrega_sin_liquidar", 0)) === 1;
            if (!$permiteSinLiquidar && floatval($venta["saldo_total"]) > 0.0001) {
                throw new Exception("No se puede entregar con saldo pendiente");
            }
            $reservas = $this->reservasActivasPedidoPosReal($db, intval($venta["id_venta"]));
            if (empty($reservas)) {
                throw new Exception("No hay reservas activas para entregar");
            }
            $movimientos = array();
            foreach ($reservas as $reserva) {
                $movimientos[] = $this->consumirReservaPedidoPosReal($db, $venta, $reserva, $idUsuario);
            }
            $db->prepare("UPDATE erp_ventas_detalle SET estatus='entregada', fecha_actualizacion=NOW() WHERE id_venta=:venta AND estatus<>'cancelada'")
                ->execute(array(":venta" => intval($venta["id_venta"])));
            $db->prepare("UPDATE erp_ventas SET estatus='entregado', fecha_actualizacion=NOW() WHERE id_venta=:venta")
                ->execute(array(":venta" => intval($venta["id_venta"])));
            $this->registrarEventoVentaPosReal($db, intval($venta["id_venta"]), $venta["folio"], "pedido_entregado", $venta["estatus"], "entregado", 0, "", array("movimientos" => $movimientos), $idUsuario);
            $db->commit();
            return $this->respuesta(false, "success", "Pedido/apartado entregado", array("folio" => $venta["folio"], "movimientos" => $movimientos));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage(), array("rollback" => true, "modo" => "pedido_entrega_real"));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-05.
     * Proposito: cancelar pedido/apartado POS no entregado liberando reservas.
     * Impacto: devuelve disponibilidad apartada y deja pagos para decision financiera posterior.
     * Contrato: escritura real transaccional; no reembolsa ni mueve caja.
     */
    public function pedidoCancelarReal($datos = array()) {
        $db = $this->getConexion();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $motivo = trim((string) $this->valor($datos, "motivo", ""));
        if ($motivo === "") {
            return $this->respuesta(true, "warning", "Motivo de cancelacion obligatorio");
        }
        try {
            $db->beginTransaction();
            $venta = $this->bloquearPedidoApartadoPosReal($db, intval($this->valor($datos, "id_venta", 0)), trim((string) $this->valor($datos, "folio", "")));
            if (!$venta) {
                throw new Exception("Pedido/apartado no encontrado");
            }
            if ($venta["estatus"] === "entregado") {
                throw new Exception("No se puede cancelar un documento entregado");
            }
            $liberadas = array();
            foreach ($this->reservasActivasPedidoPosReal($db, intval($venta["id_venta"])) as $reserva) {
                $liberadas[] = $this->liberarReservaPedidoPosReal($db, $reserva, $motivo, $idUsuario);
            }
            $db->prepare("UPDATE erp_ventas
                SET estatus='cancelado', cancelado_por=:usuario, motivo_cancelacion=:motivo,
                    fecha_cancelacion=NOW(), fecha_actualizacion=NOW()
                WHERE id_venta=:venta")
                ->execute(array(":usuario" => $idUsuario, ":motivo" => $motivo, ":venta" => intval($venta["id_venta"])));
            $db->prepare("UPDATE erp_ventas_detalle SET estatus='cancelada', fecha_actualizacion=NOW() WHERE id_venta=:venta AND estatus<>'entregada'")
                ->execute(array(":venta" => intval($venta["id_venta"])));
            $this->registrarEventoVentaPosReal($db, intval($venta["id_venta"]), $venta["folio"], "pedido_cancelado", $venta["estatus"], "cancelado", 0, "", array("reservas_liberadas" => $liberadas, "pagado_total" => $venta["pagado_total"], "decision_financiera_pendiente" => floatval($venta["pagado_total"]) > 0), $idUsuario);
            $db->commit();
            return $this->respuesta(false, "success", "Pedido/apartado cancelado", array("folio" => $venta["folio"], "reservas_liberadas" => $liberadas));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage(), array("rollback" => true, "modo" => "pedido_cancelacion_real"));
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: consolidar readiness UAT/POS desde backend sin escribir BD.
     * Impacto: permite revisar turno, ticket, pedidos y devoluciones antes de pedir autorizacion.
     * Contrato: read-only; no cierra turno, no crea pedido, no abona, no reserva y no mueve kardex.
     */
    public function readinessPosReadOnly($datos = array()) {
        try {
            $idUsuario = intval($this->valor($datos, "id_usuario", 0));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $idSku = intval($this->valor($datos, "id_sku", 1760));
            $cantidad = round(floatval($this->valor($datos, "cantidad", 1)), 6);
            $precio = round(floatval($this->valor($datos, "precio", 295)), 6);
            $montoContado = round(floatval($this->valor($datos, "monto_contado", 0)), 6);
            $montoAbono = round(floatval($this->valor($datos, "monto_abono", 100)), 6);
            $folioVenta = trim((string) $this->valor($datos, "folio_venta", ""));
            $folioApartado = trim((string) $this->valor($datos, "folio_apartado", "APT-UAT-000001"));
            $cliente = trim((string) $this->valor($datos, "cliente_nombre_publico", "Cliente UAT POS"));
            $identificadorCliente = trim((string) $this->valor($datos, "identificador_cliente", "3312345678"));

            $asignacion = $this->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
            $depurarAsignacion = $this->valor($asignacion, "depurar", array());
            $datosAsignacion = $this->valor($depurarAsignacion, "asignacion", array());
            $turno = $this->valor($depurarAsignacion, "turno_abierto", array());
            $idCaja = intval($this->valor($datosAsignacion, "id_caja", 0));
            $idTurno = intval($this->valor($turno, "id_turno_caja", 0));
            if (intval($this->valor($datosAsignacion, "id_almacen", 0)) > 0) {
                $idAlmacen = intval($this->valor($datosAsignacion, "id_almacen", $idAlmacen));
            }
            if ($montoContado <= 0 && !empty($turno)) {
                $montoContado = round(floatval($this->valor($turno, "monto_esperado", 0)), 6);
            }

            $cierre = $idTurno > 0 ? $this->cierreTurnoDryRun(array(
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "monto_contado" => $montoContado
            )) : $this->respuesta(false, "warning", "Sin turno abierto para cierre dry-run", array("bloqueos" => array("Sin turno abierto")));

            $ticket = $folioVenta !== "" ? $this->ticketVentaFormalReadOnly(array("folio" => $folioVenta)) : $this->respuesta(false, "warning", "Sin folio de venta para ticket", array("hallazgos" => array("Sin folio de venta")));

            $reserva = $this->pedidoReservaDryRun(array(
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "canal" => "pedido_tienda",
                "tipo_documento" => "apartado",
                "cliente_nombre_publico" => $cliente,
                "identificador_cliente" => $identificadorCliente,
                "fecha_entrega_compromiso" => date("Y-m-d", strtotime("+7 days")),
                "items" => array(array(
                    "id_sku" => $idSku,
                    "cantidad" => $cantidad,
                    "precio_unitario" => $precio,
                    "modo_salida" => "existencia_agregada"
                )),
                "pagos" => array(array(
                    "id_metodo_pago" => 1,
                    "monto" => $montoAbono,
                    "referencia" => "READINESS-PED-RESERVA"
                ))
            ));

            $abono = $this->apartadoAbonoDryRun(array(
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_turno_caja" => $idTurno,
                "folio" => $folioApartado,
                "monto_abono" => $montoAbono,
                "id_metodo_pago" => 1,
                "referencia" => "READINESS-PED-ABONO"
            ));

            $devoluciones = $this->devolucionesInventarioPendientesReadOnly(array(
                "id_almacen" => 0,
                "decision_inventario" => "pendientes",
                "limite" => 50
            ));

            $cierreBloqueos = $this->valor($this->valor($cierre, "depurar", array()), "bloqueos", array());
            $ticketHallazgos = $this->valor($this->valor($ticket, "depurar", array()), "hallazgos", array());
            $reservaBloqueos = $this->valor($this->valor($reserva, "depurar", array()), "bloqueos", array());
            $abonoBloqueos = $this->valor($this->valor($abono, "depurar", array()), "bloqueos", array());
            $devolucionesResumen = $this->valor($this->valor($devoluciones, "depurar", array()), "resumen", array());
            $hallazgos = array();
            if (empty($depurarAsignacion["asignacion_activa"])) {
                $hallazgos[] = "Sin asignacion POS activa";
            }
            if (empty($turno)) {
                $hallazgos[] = "Sin turno abierto";
            }
            if (!empty($cierreBloqueos)) {
                $hallazgos[] = "Cierre dry-run bloqueado: " . implode("; ", $cierreBloqueos);
            }
            if (!empty($ticketHallazgos)) {
                $hallazgos[] = "Ticket con hallazgos: " . implode("; ", $ticketHallazgos);
            }
            if (!empty($reservaBloqueos)) {
                $hallazgos[] = "Reserva dry-run bloqueada: " . implode("; ", $reservaBloqueos);
            }
            if (!empty($abonoBloqueos)) {
                $hallazgos[] = "Abono dry-run bloqueado: " . implode("; ", $abonoBloqueos);
            }
            if (intval($this->valor($devolucionesResumen, "total_registros", 0)) > 0) {
                $hallazgos[] = "Devoluciones fisicas pendientes: " . intval($this->valor($devolucionesResumen, "total_registros", 0));
            }

            return $this->respuesta(false, "success", "Readiness POS consultado", array(
                "read_only" => true,
                "contexto" => array(
                    "id_usuario" => $idUsuario,
                    "id_almacen" => $idAlmacen,
                    "id_caja" => $idCaja,
                    "id_turno_caja" => $idTurno,
                    "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
                    "turno_abierto" => !empty($turno)
                ),
                "resumen" => array(
                    "cierre_diferencia" => $this->valor($this->valor($cierre, "depurar", array()), "diferencia", null),
                    "cierre_bloqueos" => $cierreBloqueos,
                    "cierre_requiere_revision" => abs(floatval($this->valor($this->valor($cierre, "depurar", array()), "diferencia", 0))) > 0.0001,
                    "ticket_lineas" => count($this->valor($this->valor($ticket, "depurar", array()), "ticket_lineas", array())),
                    "reserva_bloqueos" => $reservaBloqueos,
                    "abono_bloqueos" => $abonoBloqueos,
                    "devoluciones_fisicas_pendientes" => intval($this->valor($devolucionesResumen, "total_registros", 0))
                ),
                "hallazgos" => $hallazgos,
                "siguiente_recomendado" => empty($cierreBloqueos) && !empty($turno)
                    ? "Autorizar cierre real del turno o cargar stock para UAT de pedidos/apartados reales."
                    : "Resolver bloqueos de turno/asignacion antes de autorizar operaciones reales.",
                "detalle" => array(
                    "cierre" => $cierre,
                    "ticket" => $ticket,
                    "reserva" => $reserva,
                    "abono" => $abono,
                    "devoluciones" => $devoluciones
                ),
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_cierra_turno" => true,
                    "no_crea_pedido" => true,
                    "no_registra_abono" => true,
                    "no_reserva_inventario" => true,
                    "no_mueve_kardex" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", "No se pudo consultar readiness POS", array("excepcion" => $e->getMessage()));
        }
    }

    private function listarAlmacenesVenta($db) {
        if (!$db) {
            return array();
        }
        $sql = "SELECT id_almacen, codigo_almacen, almacen, nombre_comercial, tipo_almacen,
                permite_venta, estatus
            FROM erp_almacenes
            WHERE COALESCE(estatus,'activo')='activo'
              AND COALESCE(permite_venta, 0)=1
            ORDER BY orden ASC, almacen ASC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: concentrar tipos operativos de movimientos de caja POS.
     * Impacto: evita que UI, dry-run y aplicadores usen categorias distintas.
     * Contrato: no consulta BD; devuelve reglas base para clasificacion de corte.
     */
    private function catalogoTiposMovimientoCaja() {
        return array(
            "entrada_extraordinaria" => array(
                "codigo" => "entrada_extraordinaria",
                "nombre" => "Entrada extraordinaria",
                "tipo_caja" => "entrada",
                "motivo_caja" => "entrada_extraordinaria",
                "signo" => 1,
                "requiere_referencia" => false,
                "requiere_responsable" => false,
                "requiere_autorizacion" => true,
                "requiere_evidencia" => false
            ),
            "retiro_efectivo" => array(
                "codigo" => "retiro_efectivo",
                "nombre" => "Retiro de efectivo",
                "tipo_caja" => "retiro",
                "motivo_caja" => "retiro_efectivo",
                "signo" => -1,
                "requiere_referencia" => true,
                "requiere_responsable" => true,
                "requiere_autorizacion" => true,
                "requiere_evidencia" => false
            ),
            "gasto_caja" => array(
                "codigo" => "gasto_caja",
                "nombre" => "Gasto de caja",
                "tipo_caja" => "gasto",
                "motivo_caja" => "gasto_caja",
                "signo" => -1,
                "requiere_referencia" => false,
                "requiere_responsable" => false,
                "requiere_autorizacion" => true,
                "requiere_evidencia" => true
            ),
            "vale_interno" => array(
                "codigo" => "vale_interno",
                "nombre" => "Vale interno",
                "tipo_caja" => "vale",
                "motivo_caja" => "vale_interno",
                "signo" => -1,
                "requiere_referencia" => true,
                "requiere_responsable" => true,
                "requiere_autorizacion" => true,
                "requiere_evidencia" => false
            ),
            "reembolso_cliente" => array(
                "codigo" => "reembolso_cliente",
                "nombre" => "Reembolso a cliente",
                "tipo_caja" => "reembolso",
                "motivo_caja" => "reembolso_cliente",
                "signo" => -1,
                "requiere_referencia" => true,
                "requiere_responsable" => false,
                "requiere_autorizacion" => true,
                "requiere_evidencia" => true
            )
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: listar cajas POS por almacen cuando el esquema ERP ya exista.
     * Impacto: prepara operacion multi-sucursal sin depender de caja legacy.
     * Contrato: read-only; si falta `erp_pos_cajas` devuelve lista vacia.
     */
    private function listarCajasPos($db) {
        if (!$db) {
            return array();
        }
        if (!$this->tablaExiste($db, "erp_pos_cajas")) {
            return array();
        }
        $sql = "SELECT c.id_caja, c.codigo, c.nombre, c.id_almacen, a.almacen,
                c.estatus, c.permite_efectivo, c.permite_tarjeta, c.permite_transferencia
            FROM erp_pos_cajas c
            LEFT JOIN erp_almacenes a ON a.id_almacen=c.id_almacen
            WHERE COALESCE(c.estatus, 'activa')='activa'
            ORDER BY a.almacen ASC, c.codigo ASC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: listar terminales POS configuradas sin editar registros.
     * Impacto: alimenta pantalla Configuracion POS y evita configurar terminal desde caja de cobro.
     * Contrato: read-only; si falta tabla devuelve lista vacia.
     */
    private function listarTerminalesPos($db) {
        if (!$db || !$this->tablaExiste($db, "erp_pos_terminales")) {
            return array();
        }
        $joinCaja = $this->tablaExiste($db, "erp_pos_cajas")
            ? "LEFT JOIN erp_pos_cajas c ON c.id_caja=t.id_caja"
            : "";
        $sql = "SELECT t.id_terminal_pos, t.codigo, t.nombre, t.id_almacen, t.id_caja,
                t.identificador_terminal, t.estatus, t.fecha_registro, a.almacen,
                " . ($joinCaja ? "c.codigo AS caja_codigo, c.nombre AS caja_nombre" : "NULL AS caja_codigo, NULL AS caja_nombre") . "
            FROM erp_pos_terminales t
            LEFT JOIN erp_almacenes a ON a.id_almacen=t.id_almacen
            $joinCaja
            ORDER BY COALESCE(t.estatus, 'activa') ASC, a.almacen ASC, t.codigo ASC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: listar asignaciones usuario/caja/terminal para auditoria operativa POS.
     * Impacto: permite ver quien puede operar cada caja antes de crear CRUD real.
     * Contrato: read-only; no cambia asignaciones.
     */
    private function listarAsignacionesPos($db) {
        if (!$db || !$this->tablaExiste($db, "erp_pos_usuarios_cajas")) {
            return array();
        }
        $joinTerminal = $this->tablaExiste($db, "erp_pos_terminales")
            ? "LEFT JOIN erp_pos_terminales t ON t.id_terminal_pos=uc.id_terminal_pos"
            : "";
        $joinCaja = $this->tablaExiste($db, "erp_pos_cajas")
            ? "LEFT JOIN erp_pos_cajas c ON c.id_caja=uc.id_caja"
            : "";
        $sql = "SELECT uc.id_usuario_caja, uc.id_usuario, uc.id_almacen, uc.id_caja,
                uc.id_terminal_pos, uc.estatus, uc.prioridad, uc.fecha_inicio, uc.fecha_fin,
                a.almacen,
                " . ($joinCaja ? "c.codigo AS caja_codigo, c.nombre AS caja_nombre," : "NULL AS caja_codigo, NULL AS caja_nombre,") . "
                " . ($joinTerminal ? "t.codigo AS terminal_codigo, t.nombre AS terminal_nombre" : "NULL AS terminal_codigo, NULL AS terminal_nombre") . "
            FROM erp_pos_usuarios_cajas uc
            LEFT JOIN erp_almacenes a ON a.id_almacen=uc.id_almacen
            $joinCaja
            $joinTerminal
            ORDER BY COALESCE(uc.estatus, 'activo') ASC, uc.prioridad ASC, uc.id_usuario ASC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: listar turnos abiertos de caja para amarrar ventas a corte operativo.
     * Impacto: prepara control por tienda/caja/turno sin escribir movimientos.
     * Contrato: read-only; si falta `erp_pos_turnos` devuelve lista vacia.
     */
    private function listarTurnosAbiertosPos($db) {
        if (!$db) {
            return array();
        }
        if (!$this->tablaExiste($db, "erp_pos_turnos")) {
            return array();
        }
        $sql = "SELECT t.id_turno_caja, t.folio, t.id_caja, t.id_almacen,
                t.id_usuario_apertura, t.monto_inicial, t.estatus, t.fecha_apertura
            FROM erp_pos_turnos t
            WHERE t.estatus='abierto'
            ORDER BY t.fecha_apertura DESC, t.id_turno_caja DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: consultar movimientos recientes de caja para tablero de turnos.
     * Impacto: permite revisar fondo, ventas, reembolsos y gastos sin registrar movimientos nuevos.
     * Contrato: read-only; no modifica caja ni turno.
     */
    private function listarMovimientosCajaRecientes($db, $limite = 25) {
        if (!$db || !$this->tablaExiste($db, "erp_pos_movimientos_caja")) {
            return array();
        }
        $limite = max(1, min(100, intval($limite)));
        $joinTurno = $this->tablaExiste($db, "erp_pos_turnos")
            ? "LEFT JOIN erp_pos_turnos t ON t.id_turno_caja=m.id_turno_caja"
            : "";
        $sql = "SELECT m.id_movimiento_caja, m.id_turno_caja, m.tipo, m.motivo, m.monto,
                m.referencia, m.creado_por, m.fecha_registro,
                " . ($this->columnaExiste($db, "erp_pos_movimientos_caja", "categoria") ? "m.categoria" : "NULL AS categoria") . ",
                " . ($this->columnaExiste($db, "erp_pos_movimientos_caja", "estatus") ? "m.estatus" : "NULL AS estatus") . ",
                " . ($this->columnaExiste($db, "erp_pos_movimientos_caja", "id_caja") ? "m.id_caja" : "NULL AS id_caja") . ",
                " . ($this->columnaExiste($db, "erp_pos_movimientos_caja", "id_almacen") ? "m.id_almacen" : "NULL AS id_almacen") . ",
                " . ($joinTurno ? "t.folio AS turno_folio" : "NULL AS turno_folio") . "
            FROM erp_pos_movimientos_caja m
            $joinTurno
            ORDER BY m.fecha_registro DESC, m.id_movimiento_caja DESC
            LIMIT " . $limite;
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: obtener un almacen vendible activo para validaciones POS dry-run.
     * Impacto: evita configurar cajas/terminales sobre bodegas o almacenes no vendibles.
     * Contrato: read-only.
     */
    private function almacenVentaPorId($db, $idAlmacen) {
        if (!$db || $idAlmacen <= 0 || !$this->tablaExiste($db, "erp_almacenes")) {
            return null;
        }
        $stmt = $db->prepare("SELECT id_almacen, codigo_almacen, almacen, nombre_comercial, tipo_almacen, permite_venta, estatus
            FROM erp_almacenes
            WHERE id_almacen=:id
              AND COALESCE(permite_venta, 0)=1
              AND COALESCE(estatus, 'activo')='activo'
            LIMIT 1");
        $stmt->execute(array(":id" => intval($idAlmacen)));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: obtener caja POS activa por id.
     * Impacto: valida terminales y asignaciones contra caja real.
     * Contrato: read-only.
     */
    private function cajaPosPorId($db, $idCaja) {
        if (!$db || $idCaja <= 0 || !$this->tablaExiste($db, "erp_pos_cajas")) {
            return null;
        }
        $stmt = $db->prepare("SELECT id_caja, codigo, nombre, id_almacen, estatus,
                permite_efectivo, permite_tarjeta, permite_transferencia
            FROM erp_pos_cajas
            WHERE id_caja=:id AND COALESCE(estatus, 'activa')='activa'
            LIMIT 1");
        $stmt->execute(array(":id" => intval($idCaja)));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function configuracionCajaValidarParaGuardar($db, $datos, $idCaja = 0) {
        $bloqueos = array();
        $avisos = array();
        $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
        $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
        $nombre = trim((string) $this->valor($datos, "nombre", ""));
        $metodos = array(
            "efectivo" => intval($this->valor($datos, "permite_efectivo", 1)) ? 1 : 0,
            "tarjeta" => intval($this->valor($datos, "permite_tarjeta", 1)) ? 1 : 0,
            "transferencia" => intval($this->valor($datos, "permite_transferencia", 1)) ? 1 : 0
        );
        $almacen = $this->almacenVentaPorId($db, $idAlmacen);
        if (!$almacen) {
            $bloqueos[] = "Selecciona una tienda/almacen vendible activo";
        }
        if ($codigo === "") {
            $codigo = $almacen ? ("CJ-" . strtoupper((string) $this->valor($almacen, "codigo_almacen", "POS")) . "-01") : "";
            $avisos[] = "Se sugiere codigo de caja automaticamente";
        }
        if ($nombre === "") {
            $nombre = $almacen ? ("Caja principal " . (string) $this->valor($almacen, "nombre_comercial", $this->valor($almacen, "almacen", ""))) : "";
            $avisos[] = "Se sugiere nombre de caja automaticamente";
        }
        if ($codigo === "" || !preg_match('/^[A-Z0-9_-]{3,40}$/', $codigo)) {
            $bloqueos[] = "El codigo de caja debe tener 3 a 40 caracteres y usar letras, numeros, guion o guion bajo";
        }
        if ($nombre === "" || strlen($nombre) > 120) {
            $bloqueos[] = "El nombre de caja es obligatorio y no debe superar 120 caracteres";
        }
        if (!$metodos["efectivo"] && !$metodos["tarjeta"] && !$metodos["transferencia"]) {
            $bloqueos[] = "La caja debe permitir al menos un metodo de pago";
        }
        if ($this->codigoExisteEnTablaExcepto($db, "erp_pos_cajas", "codigo", $codigo, "id_caja", $idCaja)) {
            $bloqueos[] = "Ya existe una caja POS con ese codigo";
        }
        return array(
            "bloqueos" => $bloqueos,
            "avisos" => $avisos,
            "propuesta" => array(
                "id_caja" => $idCaja > 0 ? $idCaja : null,
                "id_almacen" => $idAlmacen,
                "codigo" => $codigo,
                "nombre" => $nombre,
                "permite_efectivo" => $metodos["efectivo"],
                "permite_tarjeta" => $metodos["tarjeta"],
                "permite_transferencia" => $metodos["transferencia"],
                "estatus" => "activa"
            )
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: obtener terminal POS activa por id.
     * Impacto: valida asignaciones oficiales usuario/caja.
     * Contrato: read-only.
     */
    private function terminalPosPorId($db, $idTerminal) {
        if (!$db || $idTerminal <= 0 || !$this->tablaExiste($db, "erp_pos_terminales")) {
            return null;
        }
        $stmt = $db->prepare("SELECT id_terminal_pos, codigo, nombre, id_almacen, id_caja,
                identificador_terminal, estatus
            FROM erp_pos_terminales
            WHERE id_terminal_pos=:id AND COALESCE(estatus, 'activa')='activa'
            LIMIT 1");
        $stmt->execute(array(":id" => intval($idTerminal)));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function configuracionTerminalValidarParaGuardar($db, $datos, $idTerminal = 0) {
        $bloqueos = array();
        $avisos = array();
        $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
        $idCaja = intval($this->valor($datos, "id_caja", 0));
        $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
        $nombre = trim((string) $this->valor($datos, "nombre", ""));
        $identificador = trim((string) $this->valor($datos, "identificador_terminal", ""));
        $almacen = $this->almacenVentaPorId($db, $idAlmacen);
        $caja = $this->cajaPosPorId($db, $idCaja);
        if (!$almacen) {
            $bloqueos[] = "Selecciona una tienda/almacen vendible activo";
        }
        if (!$caja) {
            $bloqueos[] = "Selecciona una caja POS activa";
        } elseif (intval($this->valor($caja, "id_almacen", 0)) !== $idAlmacen) {
            $bloqueos[] = "La caja seleccionada no pertenece a la tienda de la terminal";
        }
        if ($codigo === "") {
            $codigo = $almacen ? ("TERM-" . strtoupper((string) $this->valor($almacen, "codigo_almacen", "POS")) . "-01") : "";
            $avisos[] = "Se sugiere codigo de terminal automaticamente";
        }
        if ($nombre === "") {
            $nombre = $almacen ? ("Terminal principal " . (string) $this->valor($almacen, "nombre_comercial", $this->valor($almacen, "almacen", ""))) : "";
            $avisos[] = "Se sugiere nombre de terminal automaticamente";
        }
        if ($codigo === "" || !preg_match('/^[A-Z0-9_-]{3,60}$/', $codigo)) {
            $bloqueos[] = "El codigo de terminal debe tener 3 a 60 caracteres y usar letras, numeros, guion o guion bajo";
        }
        if ($nombre === "" || strlen($nombre) > 150) {
            $bloqueos[] = "El nombre de terminal es obligatorio y no debe superar 150 caracteres";
        }
        if ($this->codigoExisteEnTablaExcepto($db, "erp_pos_terminales", "codigo", $codigo, "id_terminal_pos", $idTerminal)) {
            $bloqueos[] = "Ya existe una terminal POS con ese codigo";
        }
        if ($identificador === "") {
            $avisos[] = "El identificador fisico/local queda pendiente; en productivo debe capturarse para evitar selector libre";
        }
        return array(
            "bloqueos" => $bloqueos,
            "avisos" => $avisos,
            "propuesta" => array(
                "id_terminal_pos" => $idTerminal > 0 ? $idTerminal : null,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "codigo" => $codigo,
                "nombre" => $nombre,
                "identificador_terminal" => $identificador,
                "estatus" => "activa"
            )
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: validar duplicados por codigo en tablas POS.
     * Impacto: evita preparar altas que fallarian por indices unicos.
     * Contrato: read-only.
     */
    private function codigoExisteEnTabla($db, $tabla, $columna, $codigo) {
        if (!$db || $codigo === "" || !$this->tablaExiste($db, $tabla)) {
            return false;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
            return false;
        }
        $stmt = $db->prepare("SELECT 1 FROM `$tabla` WHERE `$columna`=:codigo LIMIT 1");
        $stmt->execute(array(":codigo" => $codigo));
        return (bool) $stmt->fetchColumn();
    }

    private function codigoExisteEnTablaExcepto($db, $tabla, $columna, $codigo, $pk, $idExcluir = 0) {
        if (!$db || $codigo === "" || !$this->tablaExiste($db, $tabla)) {
            return false;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)
            || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)
            || !preg_match('/^[a-zA-Z0-9_]+$/', $pk)) {
            return false;
        }
        $sql = "SELECT 1 FROM `$tabla` WHERE `$columna`=:codigo";
        $params = array(":codigo" => $codigo);
        if (intval($idExcluir) > 0) {
            $sql .= " AND `$pk`<>:id";
            $params[":id"] = intval($idExcluir);
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    private function configuracionAsignacionValidarParaGuardar($db, $datos, $idAsignacion = 0) {
        $bloqueos = array();
        $avisos = array();
        $idUsuario = intval($this->valor($datos, "id_usuario", 0));
        $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
        $idCaja = intval($this->valor($datos, "id_caja", 0));
        $idTerminal = intval($this->valor($datos, "id_terminal_pos", 0));
        $prioridad = max(1, intval($this->valor($datos, "prioridad", 1)));
        $almacen = $this->almacenVentaPorId($db, $idAlmacen);
        $caja = $this->cajaPosPorId($db, $idCaja);
        $terminal = $this->terminalPosPorId($db, $idTerminal);
        if (!$this->usuarioPosActivo($db, $idUsuario)) {
            $bloqueos[] = "Selecciona un usuario activo";
        }
        if (!$almacen) {
            $bloqueos[] = "Selecciona una tienda/almacen vendible activo";
        }
        if (!$caja) {
            $bloqueos[] = "Selecciona una caja POS activa";
        } elseif (intval($this->valor($caja, "id_almacen", 0)) !== $idAlmacen) {
            $bloqueos[] = "La caja no pertenece a la tienda seleccionada";
        }
        if ($idTerminal > 0) {
            if (!$terminal) {
                $bloqueos[] = "La terminal seleccionada no existe o no esta activa";
            } elseif (intval($this->valor($terminal, "id_almacen", 0)) !== $idAlmacen || intval($this->valor($terminal, "id_caja", 0)) !== $idCaja) {
                $bloqueos[] = "La terminal no pertenece a la tienda/caja seleccionada";
            }
        } else {
            $avisos[] = "La asignacion sin terminal puede servir para UAT, pero productivo debe amarrar terminal";
        }
        if ($this->asignacionActivaExisteExcepto($db, $idUsuario, $idAlmacen, $idCaja, $idTerminal, $idAsignacion)) {
            $bloqueos[] = "Ya existe una asignacion activa para este usuario, tienda, caja y terminal";
        }
        return array(
            "bloqueos" => $bloqueos,
            "avisos" => $avisos,
            "propuesta" => array(
                "id_usuario_caja" => $idAsignacion > 0 ? $idAsignacion : null,
                "id_usuario" => $idUsuario,
                "id_almacen" => $idAlmacen,
                "id_caja" => $idCaja,
                "id_terminal_pos" => $idTerminal ?: null,
                "prioridad" => $prioridad,
                "estatus" => "activo"
            )
        );
    }

    private function usuarioPosActivo($db, $idUsuario) {
        if (!$db || $idUsuario <= 0 || !$this->tablaExiste($db, "sys_usuarios")) {
            return false;
        }
        $stmt = $db->prepare("SELECT 1 FROM sys_usuarios WHERE id_usuario=:id AND COALESCE(estatus, 1)=1 LIMIT 1");
        $stmt->execute(array(":id" => intval($idUsuario)));
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-01.
     * Proposito: detectar asignacion activa duplicada en configuracion POS.
     * Impacto: protege el modelo usuario/caja/terminal antes de crear CRUD real.
     * Contrato: read-only.
     */
    private function asignacionActivaExiste($db, $idUsuario, $idAlmacen, $idCaja, $idTerminal) {
        if (!$db || !$this->tablaExiste($db, "erp_pos_usuarios_cajas") || $idUsuario <= 0 || $idAlmacen <= 0 || $idCaja <= 0) {
            return false;
        }
        $sql = "SELECT 1 FROM erp_pos_usuarios_cajas
            WHERE id_usuario=:usuario
              AND id_almacen=:almacen
              AND id_caja=:caja
              AND COALESCE(estatus, 'activo')='activo'";
        $params = array(
            ":usuario" => intval($idUsuario),
            ":almacen" => intval($idAlmacen),
            ":caja" => intval($idCaja)
        );
        if ($idTerminal > 0) {
            $sql .= " AND id_terminal_pos=:terminal";
            $params[":terminal"] = intval($idTerminal);
        } else {
            $sql .= " AND id_terminal_pos IS NULL";
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    private function asignacionActivaExisteExcepto($db, $idUsuario, $idAlmacen, $idCaja, $idTerminal, $idAsignacion = 0) {
        if (!$db || !$this->tablaExiste($db, "erp_pos_usuarios_cajas") || $idUsuario <= 0 || $idAlmacen <= 0 || $idCaja <= 0) {
            return false;
        }
        $sql = "SELECT 1 FROM erp_pos_usuarios_cajas
            WHERE id_usuario=:usuario
              AND id_almacen=:almacen
              AND id_caja=:caja
              AND COALESCE(estatus, 'activo')='activo'";
        $params = array(
            ":usuario" => intval($idUsuario),
            ":almacen" => intval($idAlmacen),
            ":caja" => intval($idCaja)
        );
        if ($idTerminal > 0) {
            $sql .= " AND id_terminal_pos=:terminal";
            $params[":terminal"] = intval($idTerminal);
        } else {
            $sql .= " AND id_terminal_pos IS NULL";
        }
        if (intval($idAsignacion) > 0) {
            $sql .= " AND id_usuario_caja<>:id_asignacion";
            $params[":id_asignacion"] = intval($idAsignacion);
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    private function cajaTieneDependenciasOperativas($db, $idCaja) {
        if (!$db || $idCaja <= 0) {
            return false;
        }
        foreach (array(
            "erp_pos_turnos" => "id_caja",
            "erp_pos_movimientos_caja" => "id_caja",
            "erp_ventas" => "id_caja"
        ) as $tabla => $columna) {
            if (!$this->tablaExiste($db, $tabla) || !$this->columnaExiste($db, $tabla, $columna)) {
                continue;
            }
            $stmt = $db->prepare("SELECT 1 FROM `$tabla` WHERE `$columna`=:id LIMIT 1");
            $stmt->execute(array(":id" => intval($idCaja)));
            if ($stmt->fetchColumn()) {
                return true;
            }
        }
        return false;
    }

    private function cajaTieneTurnoAbierto($db, $idCaja) {
        if (!$db || $idCaja <= 0 || !$this->tablaExiste($db, "erp_pos_turnos")) {
            return false;
        }
        $stmt = $db->prepare("SELECT 1 FROM erp_pos_turnos WHERE id_caja=:id AND estatus='abierto' LIMIT 1");
        $stmt->execute(array(":id" => intval($idCaja)));
        return (bool) $stmt->fetchColumn();
    }

    private function terminalTieneTurnoAbierto($db, $idTerminal) {
        if (!$db || $idTerminal <= 0 || !$this->tablaExiste($db, "erp_pos_usuarios_cajas") || !$this->tablaExiste($db, "erp_pos_turnos")) {
            return false;
        }
        $stmt = $db->prepare("SELECT 1
            FROM erp_pos_usuarios_cajas uc
            INNER JOIN erp_pos_turnos t ON t.id_caja=uc.id_caja AND t.id_almacen=uc.id_almacen AND t.estatus='abierto'
            WHERE uc.id_terminal_pos=:id AND uc.estatus='activo'
            LIMIT 1");
        $stmt->execute(array(":id" => intval($idTerminal)));
        return (bool) $stmt->fetchColumn();
    }

    private function asignacionTieneTurnoAbierto($db, $idAsignacion) {
        if (!$db || $idAsignacion <= 0 || !$this->tablaExiste($db, "erp_pos_usuarios_cajas") || !$this->tablaExiste($db, "erp_pos_turnos")) {
            return false;
        }
        $stmt = $db->prepare("SELECT 1
            FROM erp_pos_usuarios_cajas uc
            INNER JOIN erp_pos_turnos t ON t.id_caja=uc.id_caja AND t.id_almacen=uc.id_almacen AND t.estatus='abierto'
            WHERE uc.id_usuario_caja=:id AND uc.estatus='activo'
            LIMIT 1");
        $stmt->execute(array(":id" => intval($idAsignacion)));
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: verificar tablas minimas de atenciones persistentes POS.
     * Impacto: separa cuentas locales de cuentas compartidas multiusuario.
     * Contrato: solo metadata; no crea ni modifica tablas.
     */
    private function schemaAtencionesPosCompleto($db) {
        if (!$db) {
            return false;
        }
        foreach (array("erp_pos_atenciones", "erp_pos_atenciones_detalle", "erp_pos_atenciones_pagos_temporales") as $tabla) {
            if (!$this->tablaExiste($db, $tabla)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: validar que el carrito POS este asociado a caja de la misma tienda.
     * Impacto: evita diseÃ±ar ventas sin contexto tienda/almacen/caja.
     * Contrato: read-only; antes de existir esquema devuelve bloqueo operativo informativo.
     */
    private function validarCajaOperativa($db, $idAlmacen, $idCaja) {
        if (!$db) {
            return array("Conexion MySQL no disponible para validar caja POS");
        }
        if (!$this->tablaExiste($db, "erp_pos_cajas")) {
            return array("Configura cajas POS antes de cobrar; esta prevalidacion no genera venta");
        }
        if ($idCaja <= 0) {
            return array("Selecciona caja POS");
        }
        $stmt = $db->prepare("SELECT id_caja FROM erp_pos_cajas
            WHERE id_caja=:caja AND id_almacen=:almacen AND COALESCE(estatus, 'activa')='activa'
            LIMIT 1");
        $stmt->execute(array(":caja" => intval($idCaja), ":almacen" => intval($idAlmacen)));
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return array("La caja seleccionada no pertenece al almacen de venta o no esta activa");
        }
        return array();
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: validar que la preventa POS tenga turno abierto de la misma caja/tienda.
     * Impacto: evita ventas reales fuera de corte de caja.
     * Contrato: read-only; antes de existir esquema devuelve bloqueo operativo informativo.
     */
    private function validarTurnoOperativo($db, $idAlmacen, $idCaja, $idTurno) {
        if (!$db) {
            return array("Conexion MySQL no disponible para validar turno POS");
        }
        if (!$this->tablaExiste($db, "erp_pos_turnos")) {
            return array("Abre turno de caja antes de cobrar; esta prevalidacion no genera venta");
        }
        if ($idTurno <= 0) {
            return array("Selecciona turno abierto de caja");
        }
        $stmt = $db->prepare("SELECT id_turno_caja FROM erp_pos_turnos
            WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'
            LIMIT 1");
        $stmt->execute(array(
            ":turno" => intval($idTurno),
            ":caja" => intval($idCaja),
            ":almacen" => intval($idAlmacen)
        ));
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return array("El turno seleccionado no esta abierto para esa caja y almacen");
        }
        return array();
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: comprobar si el esquema minimo de Ventas ERP existe antes de listar/cobrar.
     * Impacto: permite construir UI nueva sin consultar tablas legacy ni fallar por migracion pendiente.
     * Contrato: solo verifica INFORMATION_SCHEMA; no crea tablas.
     */
    private function tablasVentasDisponibles($db) {
        return $this->tablaExiste($db, "erp_ventas")
            && $this->tablaExiste($db, "erp_ventas_detalle")
            && $this->tablaExiste($db, "erp_ventas_pagos");
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: comprobar el esquema completo requerido para confirmar venta POS real.
     * Impacto: impide activar cobro si faltan tablas de caja, pagos, ventas o trazabilidad.
     * Contrato: solo verifica metadata; no crea ni modifica tablas.
     */
    private function schemaVentaPosCompleto($db) {
        $tablas = array(
            "erp_pos_cajas",
            "erp_pos_turnos",
            "erp_pos_movimientos_caja",
            "erp_ventas",
            "erp_ventas_detalle",
            "erp_ventas_detalle_inventario",
            "erp_ventas_pagos"
        );
        foreach ($tablas as $tabla) {
            if (!$this->tablaExiste($db, $tabla)) {
                return false;
            }
        }
        return true;
    }

    private function listarMetodosPago($db) {
        if (!$db) {
            return array();
        }
        if (!$this->tablaExiste($db, "erp_metodos_pago")) {
            return array();
        }
        return $db->query("SELECT * FROM erp_metodos_pago")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function schemaClientesCrmDisponible($db) {
        return $this->tablaExiste($db, "crm_clientes_maestro") && $this->tablaExiste($db, "crm_clientes_identificadores");
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-07.
     * Proposito: consultar saldo CRM disponible para mostrarlo en POS antes de cobrar.
     * Impacto: mejora UX de cajero sin decidir precios, descuentos ni movimientos de saldo.
     * Contrato: read-only; no crea cuentas, no crea movimientos y no bloquea registros.
     */
    public function clienteSaldoCrmReadOnly($datos = array()) {
        $db = $this->getConexion();
        $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", $this->valor($datos, "id", 0)));
        $avisos = array();
        if ($idClienteCrm <= 0) {
            return array(
                "error" => true,
                "tipo" => "warning",
                "mensaje" => "Indica cliente CRM.",
                "depurar" => array("read_only" => true)
            );
        }
        if (!$this->schemaClientesCrmDisponible($db)) {
            return array(
                "error" => true,
                "tipo" => "warning",
                "mensaje" => "CRM clientes no esta disponible.",
                "depurar" => array("read_only" => true, "id_cliente_crm" => $idClienteCrm)
            );
        }
        if (!$this->tablaExiste($db, "crm_clientes_saldos_cuentas") || !$this->tablaExiste($db, "crm_clientes_saldos_movimientos")) {
            return array(
                "error" => false,
                "tipo" => "info",
                "mensaje" => "Cliente sin modulo de saldos CRM disponible.",
                "depurar" => array(
                    "read_only" => true,
                    "id_cliente_crm" => $idClienteCrm,
                    "saldo_disponible" => 0,
                    "cuenta_activa" => null,
                    "movimientos_recientes" => array(),
                    "avisos" => array("Faltan tablas de saldos CRM")
                )
            );
        }

        $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, calidad_datos
            FROM crm_clientes_maestro
            WHERE id_cliente_crm=:cliente
            LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            return array(
                "error" => true,
                "tipo" => "warning",
                "mensaje" => "Cliente CRM no encontrado.",
                "depurar" => array("read_only" => true, "id_cliente_crm" => $idClienteCrm)
            );
        }

        $stmt = $db->prepare("SELECT id_cliente_saldo_cuenta, id_cliente_crm, moneda, saldo_disponible,
                saldo_retenido, saldo_total, estatus, fecha_actualizacion
            FROM crm_clientes_saldos_cuentas
            WHERE id_cliente_crm=:cliente AND moneda='MXN' AND estatus='activa'
            ORDER BY id_cliente_saldo_cuenta DESC
            LIMIT 1");
        $stmt->execute(array(":cliente" => $idClienteCrm));
        $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cuenta) {
            $avisos[] = "Cliente sin cuenta activa de saldos MXN";
        }

        $movimientos = array();
        if ($cuenta) {
            $stmt = $db->prepare("SELECT folio, tipo, naturaleza, monto, saldo_resultante,
                    origen_modulo, origen_tipo, origen_id, fecha_registro
                FROM crm_clientes_saldos_movimientos
                WHERE id_cliente_saldo_cuenta=:cuenta
                ORDER BY id_cliente_saldo_movimiento DESC
                LIMIT 5");
            $stmt->execute(array(":cuenta" => intval($cuenta["id_cliente_saldo_cuenta"])));
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Saldo cliente CRM consultado",
            "depurar" => array(
                "read_only" => true,
                "cliente" => $cliente,
                "id_cliente_crm" => $idClienteCrm,
                "saldo_disponible" => $cuenta ? round(floatval($cuenta["saldo_disponible"]), 6) : 0,
                "saldo_retenido" => $cuenta ? round(floatval($cuenta["saldo_retenido"]), 6) : 0,
                "saldo_total" => $cuenta ? round(floatval($cuenta["saldo_total"]), 6) : 0,
                "cuenta_activa" => $cuenta ?: null,
                "movimientos_recientes" => $movimientos,
                "avisos" => $avisos,
                "contrato" => array(
                    "no_escribe_bd" => true,
                    "no_crea_cuenta" => true,
                    "no_crea_movimientos" => true,
                    "no_mueve_caja" => true
                )
            )
        );
    }

    private function resolverClienteDryRun($db, $idCliente, $identificador, $schemaPendiente) {
        $cliente = array(
            "id_cliente" => 0,
            "id_cliente_crm" => 0,
            "origen_cliente" => "publico_general",
            "nombre_publico" => "",
            "identificador" => $identificador,
            "estatus" => "publico_general",
            "calidad_datos" => "sin_cliente",
            "requiere_alta_rapida" => false,
            "coincidencias" => array()
        );
        if ($schemaPendiente) {
            $cliente["schema_pendiente"] = true;
            $cliente["requiere_alta_rapida"] = trim($identificador) !== "";
            return $cliente;
        }
        if ($idCliente > 0) {
            $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, calidad_datos,
                    id_lista_precio_default, id_segmento_default
                FROM crm_clientes_maestro
                WHERE id_cliente_crm=:cliente
                LIMIT 1");
            $stmt->execute(array(":cliente" => $idCliente));
            $encontrado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($encontrado) {
                $encontrado["id_cliente"] = 0;
                $encontrado["origen_cliente"] = "crm";
                $encontrado["identificador"] = $identificador;
                $encontrado["coincidencias"] = array();
                $encontrado["requiere_alta_rapida"] = false;
                return $encontrado;
            }
        }
        $normalizado = $this->normalizarIdentificadorCliente($identificador);
        if ($normalizado === "") {
            return $cliente;
        }
        $stmt = $db->prepare("SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
                c.id_lista_precio_default, c.id_segmento_default, i.tipo, i.valor
            FROM crm_clientes_identificadores i
            INNER JOIN crm_clientes_maestro c ON c.id_cliente_crm=i.id_cliente_crm
            WHERE i.valor_normalizado=:valor AND i.estatus='activo'
            ORDER BY i.principal DESC, c.id_cliente_crm ASC
            LIMIT 5");
        $stmt->execute(array(":valor" => $normalizado));
        $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($coincidencias)) {
            $cliente = $coincidencias[0];
            $cliente["id_cliente"] = 0;
            $cliente["origen_cliente"] = "crm";
            $cliente["identificador"] = $identificador;
            $cliente["coincidencias"] = $coincidencias;
            $cliente["requiere_alta_rapida"] = false;
            return $cliente;
        }
        $cliente["requiere_alta_rapida"] = true;
        return $cliente;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: resolver precio base por prioridad comercial sin delegar decisiones al POS.
     * Impacto: POS, checador de precios, pedidos y futuras listas CRM/ecommerce.
     * Contrato: no modifica datos; devuelve precio, lista, origen y fuente para snapshot de venta.
     */
    private function resolverPrecioSkuDryRun($db, $sku, $cliente, $canal, $idAlmacen, $schemaListasPendiente) {
        $precioBase = round(floatval($sku["precio"]), 6);
        $resultado = array(
            "precio_base" => $precioBase,
            "precio_aplicado" => $precioBase,
            "id_lista_precio" => null,
            "lista_precio_snapshot" => "general",
            "regla_precio_origen" => "catalogo_general",
            "fuente_precio" => "erp_catalogo_sku_precios"
        );
        if ($schemaListasPendiente) {
            $resultado["schema_listas_pendiente"] = true;
            return $resultado;
        }
        $idCliente = intval($this->valor($cliente, "id_cliente", 0));
        $idClienteCrm = intval($this->valor($cliente, "id_cliente_crm", $this->valor($cliente, "id", 0)));
        $idListaDefaultCrm = intval($this->valor($cliente, "id_lista_precio_default", 0));
        $params = array(
            ":sku" => intval($sku["id_sku"]),
            ":producto" => intval($sku["id_producto_erp"]),
            ":ahora" => date("Y-m-d H:i:s"),
            ":canal" => $canal,
            ":almacen" => intval($idAlmacen)
        );
        $whereCliente = "";
        $selectCliente = "0 es_cliente, 0 prioridad_cliente";
        if (($idClienteCrm > 0 || $idCliente > 0) && $this->tablaExiste($db, "erp_clientes_listas_precios")) {
            $condicionesCliente = array();
            $tieneClienteCrmEnLista = $this->columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm");
            if ($idClienteCrm > 0 && $tieneClienteCrmEnLista) {
                $condicionesCliente[] = "(cl.id_cliente_crm=:cliente_crm OR (cl.id_cliente_crm IS NULL AND cl.id_cliente=:cliente_crm_compat))";
                $params[":cliente_crm"] = $idClienteCrm;
                $params[":cliente_crm_compat"] = $idClienteCrm;
            }
            if ($idClienteCrm > 0 && !$tieneClienteCrmEnLista) {
                $condicionesCliente[] = "cl.id_cliente=:cliente_crm_compat";
                $params[":cliente_crm_compat"] = $idClienteCrm;
            }
            if ($idCliente > 0) {
                $condicionesCliente[] = "cl.id_cliente=:cliente_erp";
                $params[":cliente_erp"] = $idCliente;
            }
            if (!empty($condicionesCliente)) {
                $whereCliente = "LEFT JOIN erp_clientes_listas_precios cl ON cl.id_lista_precio=l.id_lista_precio
                    AND (" . implode(" OR ", $condicionesCliente) . ")
                    AND cl.estatus='activo'
                    AND cl.fecha_inicio<=:ahora_cliente
                    AND (cl.fecha_fin IS NULL OR cl.fecha_fin>=:ahora_cliente)";
                $selectCliente = "CASE WHEN cl.id_cliente_lista_precio IS NOT NULL THEN 1 ELSE 0 END es_cliente,
                    COALESCE(cl.prioridad, 9999) prioridad_cliente";
            }
            $params[":ahora_cliente"] = date("Y-m-d H:i:s");
        }
        $sql = "SELECT l.id_lista_precio, l.nombre, l.canal, l.id_almacen, l.prioridad, d.precio, $selectCliente
            FROM erp_listas_precios l
            INNER JOIN erp_listas_precios_detalle d ON d.id_lista_precio=l.id_lista_precio
            $whereCliente
            WHERE l.estatus='activa'
              AND d.estatus='activo'
              AND (d.id_sku=:sku OR d.id_producto_erp=:producto)
              AND (l.canal IS NULL OR l.canal='' OR l.canal=:canal)
              AND (l.id_almacen IS NULL OR l.id_almacen=0 OR l.id_almacen=:almacen)
              AND (l.fecha_inicio IS NULL OR l.fecha_inicio<=:ahora)
              AND (l.fecha_fin IS NULL OR l.fecha_fin>=:ahora)
              AND (d.fecha_inicio IS NULL OR d.fecha_inicio<=:ahora)
              AND (d.fecha_fin IS NULL OR d.fecha_fin>=:ahora)
            ORDER BY
              es_cliente DESC,
              CASE WHEN l.id_lista_precio=:lista_default_crm THEN 1 ELSE 0 END DESC,
              CASE WHEN l.canal=:canal AND l.id_almacen=:almacen THEN 1 ELSE 0 END DESC,
              CASE WHEN (l.canal IS NULL OR l.canal='') AND (l.id_almacen IS NULL OR l.id_almacen=0) THEN 1 ELSE 0 END ASC,
              prioridad_cliente ASC,
              l.prioridad ASC,
              d.id_sku DESC,
              d.id_lista_precio_detalle DESC
            LIMIT 1";
        $params[":lista_default_crm"] = $idListaDefaultCrm;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $precio = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($precio) {
            $resultado["precio_aplicado"] = round(floatval($precio["precio"]), 6);
            $resultado["id_lista_precio"] = intval($precio["id_lista_precio"]);
            $resultado["lista_precio_snapshot"] = $precio["nombre"];
            $resultado["fuente_precio"] = "erp_listas_precios";
            if (intval($precio["es_cliente"]) === 1) {
                $resultado["regla_precio_origen"] = "lista_cliente";
            } elseif ($idListaDefaultCrm > 0 && intval($precio["id_lista_precio"]) === $idListaDefaultCrm) {
                $resultado["regla_precio_origen"] = "lista_cliente_default";
            } elseif ((string) $precio["canal"] === "" && intval($precio["id_almacen"]) === 0) {
                $resultado["regla_precio_origen"] = "lista_general_erp";
            } else {
                $resultado["regla_precio_origen"] = "lista_canal_sucursal";
            }
            $resultado["criterio_precio"] = array(
                "id_cliente_crm" => $idClienteCrm,
                "id_cliente_erp" => $idCliente,
                "id_lista_precio_default_crm" => $idListaDefaultCrm,
                "canal" => $canal,
                "id_almacen" => intval($idAlmacen),
                "prioridad_lista" => intval($precio["prioridad"]),
                "prioridad_cliente" => intval($precio["prioridad_cliente"])
            );
        }
        return $resultado;
    }

    private function calcularDescuentoComercial($base, $monto, $porcentaje) {
        $base = max(0, floatval($base));
        $monto = max(0, floatval($monto));
        $porcentaje = max(0, floatval($porcentaje));
        $descuento = 0;
        if ($porcentaje > 0) {
            $descuento += round($base * min($porcentaje, 100) / 100, 6);
        }
        if ($monto > 0) {
            $descuento += round($monto, 6);
        }
        return round(min($base, $descuento), 6);
    }

    private function consultarPoliticaComercial($db, $tipoExcepcion, $canal, $idAlmacen) {
        if (!$this->tablaExiste($db, "erp_ventas_politicas_comerciales")) {
            return null;
        }
        $stmt = $db->prepare("SELECT *
            FROM erp_ventas_politicas_comerciales
            WHERE tipo_excepcion=:tipo
              AND estatus='activa'
              AND (canal IS NULL OR canal='' OR canal=:canal)
              AND (id_almacen IS NULL OR id_almacen=0 OR id_almacen=:almacen)
              AND (fecha_inicio IS NULL OR fecha_inicio<=CURRENT_TIMESTAMP)
              AND (fecha_fin IS NULL OR fecha_fin>=CURRENT_TIMESTAMP)
            ORDER BY id_almacen DESC, fecha_inicio DESC, id_politica_comercial DESC
            LIMIT 1");
        $stmt->execute(array(
            ":tipo" => $tipoExcepcion,
            ":canal" => $canal,
            ":almacen" => intval($idAlmacen)
        ));
        $politica = $stmt->fetch(PDO::FETCH_ASSOC);
        return $politica ?: null;
    }

    private function usuarioTienePermisoDb($db, $idUsuario, $permiso) {
        $stmt = $db->prepare("SELECT COUNT(*)
            FROM sys_usuarios_roles ur
            INNER JOIN sys_roles r ON r.id_rol=ur.id_rol AND r.estatus=1
            INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
            INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso AND p.estatus=1
            WHERE ur.id_usuario=:usuario
              AND ur.estatus=1
              AND p.permiso=:permiso");
        $stmt->execute(array(":usuario" => intval($idUsuario), ":permiso" => $permiso));
        return intval($stmt->fetchColumn()) > 0;
    }

    private function partidaExcepcionPrincipal($partidas) {
        foreach ($partidas as $partida) {
            if (!empty($partida["aplica_excepcion"])) {
                return $partida;
            }
        }
        return !empty($partidas) ? $partidas[0] : array();
    }

    private function buscarExcepcionComercialDuplicadaReciente($db, $datos) {
        if (!$this->tablaExiste($db, "erp_ventas_excepciones_comerciales")) {
            return null;
        }
        $usaClienteCrm = $this->columnaExiste($db, "erp_ventas_excepciones_comerciales", "id_cliente_crm");
        $whereCliente = "((id_cliente IS NULL AND :cliente IS NULL) OR id_cliente=:cliente)";
        $params = array(
            ":cliente" => intval($this->valor($datos, "id_cliente", 0)) > 0 ? intval($this->valor($datos, "id_cliente", 0)) : null,
            ":sku" => intval($this->valor($datos, "id_sku", 0)) > 0 ? intval($this->valor($datos, "id_sku", 0)) : null,
            ":tipo" => trim((string) $this->valor($datos, "tipo_excepcion", "")),
            ":motivo" => trim((string) $this->valor($datos, "motivo", "")),
            ":codigo" => trim((string) $this->valor($datos, "codigo_autorizacion", "")),
            ":solicitado" => intval($this->valor($datos, "solicitado_por", 0)),
            ":autorizado" => intval($this->valor($datos, "autorizado_por", 0)),
            ":precio" => round(floatval($this->valor($datos, "precio_aplicado", 0)), 6),
            ":descuento" => round(floatval($this->valor($datos, "descuento_total", 0)), 6),
            ":subtotal" => round(floatval($this->valor($datos, "subtotal_antes", 0)), 6),
            ":total" => round(floatval($this->valor($datos, "total_despues", 0)), 6)
        );
        if ($usaClienteCrm) {
            $whereCliente .= " AND ((id_cliente_crm IS NULL AND :cliente_crm IS NULL) OR id_cliente_crm=:cliente_crm)";
            $params[":cliente_crm"] = intval($this->valor($datos, "id_cliente_crm", 0)) > 0 ? intval($this->valor($datos, "id_cliente_crm", 0)) : null;
        }
        $stmt = $db->prepare("SELECT id_excepcion_comercial, folio, estatus, id_politica_comercial
            FROM erp_ventas_excepciones_comerciales
            WHERE estatus='autorizada'
              AND id_venta IS NULL
              AND id_venta_detalle IS NULL
              AND $whereCliente
              AND ((id_sku_erp IS NULL AND :sku IS NULL) OR id_sku_erp=:sku)
              AND tipo_excepcion=:tipo
              AND motivo=:motivo
              AND autorizacion_codigo=:codigo
              AND solicitado_por=:solicitado
              AND autorizado_por=:autorizado
              AND ABS(precio_aplicado - :precio) < 0.0001
              AND ABS(descuento_total - :descuento) < 0.0001
              AND ABS(subtotal_antes - :subtotal) < 0.0001
              AND ABS(total_despues - :total) < 0.0001
              AND fecha_autorizacion >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 MINUTE)
            ORDER BY id_excepcion_comercial DESC
            LIMIT 1");
        $stmt->execute($params);
        $duplicada = $stmt->fetch(PDO::FETCH_ASSOC);
        return $duplicada ?: null;
    }

    private function consultarExcepcionComercialConsumo($db, $folio, $idExcepcion) {
        $where = $idExcepcion > 0 ? "e.id_excepcion_comercial=:referencia" : "e.folio=:referencia";
        $stmt = $db->prepare("SELECT e.*, p.codigo politica_codigo, p.nombre politica_nombre,
                p.descuento_max_porcentaje, p.descuento_max_monto, p.margen_minimo_porcentaje,
                p.permiso_requerido
            FROM erp_ventas_excepciones_comerciales e
            LEFT JOIN erp_ventas_politicas_comerciales p ON p.id_politica_comercial=e.id_politica_comercial
            WHERE {$where}
            LIMIT 1");
        $stmt->execute(array(":referencia" => $idExcepcion > 0 ? $idExcepcion : $folio));
        $excepcion = $stmt->fetch(PDO::FETCH_ASSOC);
        return $excepcion ?: null;
    }

    private function normalizarItemsParaPrecioBackend($items) {
        $normalizados = array();
        foreach ($items as $item) {
            $normalizados[] = array(
                "id_sku" => intval($this->valor($item, "id_sku", 0)),
                "cantidad" => round(floatval($this->valor($item, "cantidad", 0)), 6),
                "modo_salida" => trim((string) $this->valor($item, "modo_salida", "")),
                "id_inventario_unidad" => intval($this->valor($item, "id_inventario_unidad", 0))
            );
        }
        return $normalizados;
    }

    private function normalizarIdentificadorCliente($valor) {
        $valor = trim(strtolower((string) $valor));
        if ($valor === "") {
            return "";
        }
        if (strpos($valor, "@") !== false) {
            return preg_replace('/\s+/', '', $valor);
        }
        $soloDigitos = preg_replace('/\D+/', '', $valor);
        return $soloDigitos !== "" ? $soloDigitos : preg_replace('/\s+/', ' ', $valor);
    }

    private function tipoIdentificadorCliente($valor) {
        $texto = trim((string) $valor);
        if ($texto === "") {
            return "";
        }
        if (strpos($texto, "@") !== false) {
            return "correo";
        }
        $soloDigitos = preg_replace('/\D+/', '', $texto);
        if ($soloDigitos !== "" && strlen($soloDigitos) >= 7) {
            return "telefono";
        }
        return "codigo";
    }

    private function sugerirCodigoClientePos($db) {
        $prefijo = "CL-POS-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_clientes WHERE codigo_cliente LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 4, "0", STR_PAD_LEFT);
    }

    private function liberarLockCliente($db, $lockName) {
        if (!$db || trim((string) $lockName) === "") {
            return;
        }
        try {
            $stmt = $db->prepare("SELECT RELEASE_LOCK(:lock_name)");
            $stmt->execute(array(":lock_name" => $lockName));
        } catch (Exception $e) {
        }
    }

    private function consultarSkuVenta($db, $idSku) {
        $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku, COALESCE(s.nombre, p.nombre) nombre_sku,
                p.nombre producto, s.tipo_inventario, s.permite_venta_sin_existencia, s.estatus,
                COALESCE(pr.precio, 0) precio, COALESCE(pr.moneda, 'MXN') moneda,
                COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
                COALESCE(r.permite_existencia_negativa, 0) permite_existencia_negativa,
                COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
                COALESCE(r.precision_decimal, 0) precision_decimal,
                COALESCE(r.incremento_minimo_venta, 1.000000) incremento_minimo_venta,
                COALESCE(NULLIF(r.unidad_venta_label, ''), ub.abreviatura, ub.codigo, '') unidad_venta_label,
                COALESCE(r.requiere_escaneo_venta, 0) requiere_escaneo_venta,
                COALESCE(r.generar_etiqueta_interna, 0) generar_etiqueta_interna
            FROM erp_catalogo_skus s
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
            LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
            LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku
                AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
            WHERE s.id_sku=:sku AND s.estatus='activo' AND p.estatus='activo'
            LIMIT 1");
        $stmt->execute(array(":sku" => intval($idSku)));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function consultarVisualSkuChecador($db, $idSku) {
        $stmt = $db->prepare("SELECT p.codigo_producto,
                COALESCE(m.nombre, '') marca,
                COALESCE(cat.nombre, '') categoria,
                COALESCE(cod.codigo, '') codigo_barras,
                COALESCE(img.url_imagen, img_producto.url_imagen, '') url_imagen
            FROM erp_catalogo_skus s
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
            LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
            LEFT JOIN erp_catalogo_categorias cat ON cat.id_categoria_erp=pc.id_categoria_erp
            LEFT JOIN erp_catalogo_sku_codigos cod ON cod.id_sku=s.id_sku AND cod.estatus='activo'
                AND cod.id_sku_codigo = (
                    SELECT c2.id_sku_codigo FROM erp_catalogo_sku_codigos c2
                    WHERE c2.id_sku=s.id_sku AND c2.estatus='activo'
                    ORDER BY c2.es_principal DESC, c2.tipo_codigo IN ('codigo_barras','barras') DESC, c2.id_sku_codigo DESC
                    LIMIT 1
                )
            LEFT JOIN erp_catalogo_imagenes img ON img.id_sku=s.id_sku AND img.estatus='activo'
                AND img.id_imagen_erp = (
                    SELECT i2.id_imagen_erp FROM erp_catalogo_imagenes i2
                    WHERE i2.id_sku=s.id_sku AND i2.estatus='activo'
                    ORDER BY i2.tipo_imagen='portada' DESC, i2.id_imagen_erp ASC
                    LIMIT 1
                )
            LEFT JOIN erp_catalogo_imagenes img_producto ON img_producto.id_producto_erp=s.id_producto_erp AND img_producto.estatus='activo'
                AND img_producto.id_imagen_erp = (
                    SELECT i3.id_imagen_erp FROM erp_catalogo_imagenes i3
                    WHERE i3.id_producto_erp=s.id_producto_erp AND i3.estatus='activo'
                    ORDER BY i3.tipo_imagen='portada' DESC, i3.id_imagen_erp ASC
                    LIMIT 1
                )
            WHERE s.id_sku=:sku
            LIMIT 1");
        $stmt->execute(array(":sku" => intval($idSku)));
        $visual = $stmt->fetch(PDO::FETCH_ASSOC);
        return $visual ?: array("codigo_producto" => "", "marca" => "", "categoria" => "", "codigo_barras" => "", "url_imagen" => "");
    }

    private function buscarSkusParaChecador($db, $termino, $idAlmacen, $limite = 8) {
        $termino = trim((string) $termino);
        if (strlen($termino) < 2) {
            return array();
        }
        $sql = "SELECT s.id_sku, s.sku, COALESCE(s.nombre, p.nombre) nombre_sku,
                p.nombre producto, COALESCE(cod.codigo, '') codigo_barras,
                COALESCE(pr.precio, 0) precio,
                COALESCE(inv.disponible, 0) existencia_disponible
            FROM erp_catalogo_skus s
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku
                AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
            LEFT JOIN erp_catalogo_sku_codigos cod ON cod.id_sku=s.id_sku AND cod.estatus='activo'
                AND cod.id_sku_codigo = (
                    SELECT c2.id_sku_codigo FROM erp_catalogo_sku_codigos c2
                    WHERE c2.id_sku=s.id_sku AND c2.estatus='activo'
                    ORDER BY c2.es_principal DESC, c2.tipo_codigo IN ('codigo_barras','barras') DESC, c2.id_sku_codigo DESC
                    LIMIT 1
                )
            LEFT JOIN (
                SELECT id_sku_erp, SUM(cantidad_disponible) disponible
                FROM erp_inventario_existencias
                WHERE (:almacen=0 OR id_almacen_clave=:almacen_filtro)
                GROUP BY id_sku_erp
            ) inv ON inv.id_sku_erp=s.id_sku
            WHERE s.estatus='activo' AND p.estatus='activo'
              AND (
                s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar OR p.codigo_producto LIKE :buscar
                OR EXISTS (
                    SELECT 1 FROM erp_catalogo_sku_codigos c
                    WHERE c.id_sku=s.id_sku AND c.estatus='activo' AND c.codigo LIKE :buscar_codigo
                )
              )
            ORDER BY
                CASE WHEN s.sku=:exacto THEN 0
                     WHEN cod.codigo=:exacto_codigo THEN 1
                     WHEN s.sku LIKE :prefijo THEN 2
                     ELSE 3 END,
                p.nombre, s.sku
            LIMIT " . intval(max(1, min(20, $limite)));
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            ":almacen" => intval($idAlmacen),
            ":almacen_filtro" => intval($idAlmacen),
            ":buscar" => "%" . $termino . "%",
            ":buscar_codigo" => "%" . $termino . "%",
            ":exacto" => $termino,
            ":exacto_codigo" => $termino,
            ":prefijo" => $termino . "%"
        ));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function existenciasDisponiblesVenta($db, $idSku, $idAlmacen) {
        $sql = "SELECT e.id_existencia_inventario, e.codigo_existencia, e.id_almacen_clave id_almacen,
                a.almacen, e.lote, e.fecha_caducidad, e.ubicacion_id, e.ubicacion,
                e.cantidad, e.cantidad_apartada, e.cantidad_disponible, e.estatus_existencia,
                e.costo_promedio
            FROM erp_inventario_existencias e
            LEFT JOIN erp_almacenes a ON a.id_almacen=e.id_almacen_clave
            WHERE e.id_sku_erp=:sku
              AND (:almacen=0 OR e.id_almacen_clave=:almacen_filtro)
              AND (e.cantidad<>0 OR e.cantidad_disponible<>0 OR e.cantidad_apartada<>0)
            ORDER BY e.fecha_caducidad_clave ASC, e.fecha_registro ASC, e.id_existencia_inventario ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":sku" => intval($idSku), ":almacen" => intval($idAlmacen), ":almacen_filtro" => intval($idAlmacen)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function unidadesDisponiblesVenta($db, $idSku, $idAlmacen) {
        $sql = "SELECT u.id_inventario_unidad, u.codigo_unico, u.codigo_etiqueta_interna, u.serie_fabricante,
                u.id_existencia_inventario, u.id_almacen, u.lote, u.fecha_caducidad,
                u.cantidad_base_original, u.cantidad_base_disponible, u.unidad_base,
                u.estatus, u.estado_etiqueta, u.estado_fisico
            FROM erp_inventario_unidades u
            WHERE u.id_sku_erp=:sku
              AND (:almacen=0 OR u.id_almacen=:almacen_filtro)
              AND u.estatus='disponible'
              AND u.cantidad_base_disponible > 0
            ORDER BY FIELD(u.estado_fisico, 'cerrada', 'abierta', 'agotada', 'consumida', 'vendida'), u.fecha_caducidad, u.id_inventario_unidad";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":sku" => intval($idSku), ":almacen" => intval($idAlmacen), ":almacen_filtro" => intval($idAlmacen)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function resumenDisponibilidad($existencias, $unidades) {
        $cantidad = 0;
        $disponible = 0;
        $apartada = 0;
        foreach ($existencias as $existencia) {
            $cantidad += floatval($existencia["cantidad"]);
            $disponible += floatval($existencia["cantidad_disponible"]);
            $apartada += floatval($existencia["cantidad_apartada"]);
        }
        $cerradas = 0;
        $abiertas = 0;
        $contenidoUnidades = 0;
        foreach ($unidades as $unidad) {
            if ($unidad["estado_fisico"] === "cerrada") {
                $cerradas++;
            } elseif ($unidad["estado_fisico"] === "abierta") {
                $abiertas++;
            }
            $contenidoUnidades += floatval($unidad["cantidad_base_disponible"]);
        }
        return array(
            "cantidad" => round($cantidad, 6),
            "disponible" => round($disponible, 6),
            "apartada" => round($apartada, 6),
            "unidades_cerradas" => $cerradas,
            "unidades_abiertas" => $abiertas,
            "contenido_unidades_disponible" => round($contenidoUnidades, 6)
        );
    }

    private function dictamenUnidadPos($unidad) {
        if ($unidad["estatus"] !== "disponible" || floatval($unidad["cantidad_base_disponible"]) <= 0) {
            return array("vendible" => false, "modo" => "bloqueada", "mensaje" => "La unidad no esta disponible para venta");
        }
        if ($unidad["estado_fisico"] === "cerrada") {
            return array("vendible" => true, "modo" => "unidad_cerrada", "mensaje" => "Unidad cerrada disponible para venta completa");
        }
        if ($unidad["estado_fisico"] === "abierta" && intval($unidad["permite_venta_fraccionaria"]) === 1) {
            return array("vendible" => true, "modo" => "granel_unidad_abierta", "mensaje" => "Unidad abierta disponible solo para venta a granel POS");
        }
        if ($unidad["estado_fisico"] === "abierta") {
            return array("vendible" => false, "modo" => "abierta_no_granel", "mensaje" => "La unidad abierta no puede venderse como unidad cerrada");
        }
        return array("vendible" => false, "modo" => "bloqueada", "mensaje" => "Estado fisico no vendible en POS");
    }

    private function prevalidarPartida($db, $item, $idAlmacen, $renglon, $cliente = array(), $canal = "pos", $schemaListasPendiente = true) {
        $idSku = intval($this->valor($item, "id_sku", 0));
        $cantidad = round(floatval($this->valor($item, "cantidad", 0)), 6);
        $modo = trim((string) $this->valor($item, "modo_salida", ""));
        $idUnidad = intval($this->valor($item, "id_inventario_unidad", 0));
        $bloqueos = array();

        $sku = $this->consultarSkuVenta($db, $idSku);
        if (!$sku) {
            return array(
                "renglon" => $renglon,
                "id_sku" => $idSku,
                "cantidad" => $cantidad,
                "subtotal" => 0,
                "bloqueos" => array("SKU no encontrado o inactivo")
            );
        }

        if ($cantidad <= 0) {
            $bloqueos[] = "La cantidad debe ser mayor a cero";
        }

        if (intval($sku["permite_venta_fraccionaria"]) !== 1 && abs($cantidad - intval($cantidad)) > 0.000001) {
            $bloqueos[] = "El SKU no permite venta fraccionaria";
        }

        if (intval($sku["permite_venta_fraccionaria"]) === 1) {
            $precision = intval($sku["precision_decimal"]);
            $incremento = floatval($sku["incremento_minimo_venta"]);
            if ($this->decimalesNumero($cantidad) > $precision) {
                $bloqueos[] = "La cantidad excede la precision decimal permitida";
            }
            if ($incremento > 0) {
                $multiplo = round($cantidad / $incremento, 6);
                if (abs($multiplo - round($multiplo)) > 0.0001) {
                    $bloqueos[] = "La cantidad no respeta el incremento minimo de venta";
                }
            }
        }

        $disponibilidad = $this->resumenDisponibilidad(
            $this->existenciasDisponiblesVenta($db, $idSku, $idAlmacen),
            $this->unidadesDisponiblesVenta($db, $idSku, $idAlmacen)
        );
        $planSalida = $this->planSalidaInventario($db, $idSku, $idAlmacen, $cantidad, $modo, $idUnidad);

        if (intval($sku["controla_inventario"]) === 1 && $cantidad > floatval($disponibilidad["disponible"]) + 0.0001) {
            $cantidadPendiente = round(max(0, $cantidad - floatval($disponibilidad["disponible"])), 6);
            $precioReferencia = round(floatval($this->valor($item, "precio_unitario", $this->valor($sku, "precio", 0))), 6);
            $politicaPendiente = $this->consultarPoliticaInventarioPendientePos($db, $idSku, $idAlmacen, $canal, $cantidadPendiente, round($cantidadPendiente * $precioReferencia, 6));
            if (!empty($politicaPendiente["bloqueos"])) {
                foreach ($politicaPendiente["bloqueos"] as $bloqueoPolitica) {
                    $bloqueos[] = $bloqueoPolitica;
                }
            } else {
                $bloqueos[] = "La politica POS autoriza inventario pendiente, pero este cobro debe pasar por el flujo real de inventario pendiente con alerta y trazabilidad";
            }
        }

        if ($idUnidad > 0 || $modo === "unidad_cerrada" || $modo === "granel_unidad_abierta") {
            $unidad = $idUnidad > 0 ? $this->consultarUnidadPorId($db, $idUnidad, $idAlmacen) : null;
            if (!$unidad) {
                $bloqueos[] = "Selecciona una unidad fisica valida";
            } else {
                $dictamen = $this->dictamenUnidadPos($unidad);
                if (!$dictamen["vendible"]) {
                    $bloqueos[] = $dictamen["mensaje"];
                } elseif ($modo === "unidad_cerrada" && $dictamen["modo"] !== "unidad_cerrada") {
                    $bloqueos[] = "La unidad no puede venderse como cerrada";
                } elseif ($modo === "granel_unidad_abierta" && $dictamen["modo"] !== "granel_unidad_abierta") {
                    $bloqueos[] = "La unidad no esta en modo granel abierto";
                }
                if ($cantidad > floatval($unidad["cantidad_base_disponible"]) + 0.0001) {
                    $bloqueos[] = "La cantidad supera el contenido disponible de la unidad";
                }
            }
        }

        $precioResuelto = $this->resolverPrecioSkuDryRun($db, $sku, is_array($cliente) ? $cliente : array(), $canal, $idAlmacen, $schemaListasPendiente);
        $precioBackend = round(floatval($this->valor($precioResuelto, "precio_aplicado", $sku["precio"])), 6);
        $precioEnviado = round(floatval($this->valor($item, "precio_unitario", $precioBackend)), 6);
        if (abs($precioEnviado - $precioBackend) > 0.000001) {
            $bloqueos[] = "Precio enviado por POS no coincide con el precio autorizado por backend; usa excepcion comercial autorizada";
        }
        $precio = $precioBackend;
        $subtotal = round(max(0, $cantidad) * max(0, $precio), 6);

        return array(
            "renglon" => $renglon,
            "id_sku" => $idSku,
            "sku" => $sku["sku"],
            "descripcion" => $sku["nombre_sku"],
            "cantidad" => $cantidad,
            "precio_unitario" => $precio,
            "precio_enviado_pos" => $precioEnviado,
            "precio_base" => round(floatval($this->valor($precioResuelto, "precio_base", $sku["precio"])), 6),
            "precio_aplicado" => $precioBackend,
            "id_lista_precio" => $this->valor($precioResuelto, "id_lista_precio", null),
            "lista_precio_snapshot" => $this->valor($precioResuelto, "lista_precio_snapshot", "general"),
            "regla_precio_origen" => $this->valor($precioResuelto, "regla_precio_origen", "catalogo_general"),
            "subtotal" => $subtotal,
            "controla_inventario" => intval($sku["controla_inventario"]),
            "permite_venta_fraccionaria" => intval($sku["permite_venta_fraccionaria"]),
            "disponibilidad" => $disponibilidad,
            "plan_salida_inventario" => $planSalida,
            "bloqueos" => $bloqueos
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: resolver la politica activa de apartado para prevalidaciones POS.
     * Impacto: el flujo real de apartados queda condicionado por una regla de negocio versionable.
     * Contrato: solo lectura; no crea ni modifica politicas.
     */
    private function politicaApartadoActiva($db) {
        if (!$this->tablaExiste($db, "erp_ventas_politicas_apartado")) {
            return null;
        }
        $stmt = $db->prepare("SELECT *
            FROM erp_ventas_politicas_apartado
            WHERE estatus='activa'
            ORDER BY id_politica_apartado ASC
            LIMIT 1");
        $stmt->execute();
        $politica = $stmt->fetch(PDO::FETCH_ASSOC);
        return $politica ?: null;
    }

    private function calcularAnticipoMinimoApartado($total, $politica) {
        $total = round(max(0, floatval($total)), 6);
        $porcentaje = round(max(0, floatval($this->valor($politica, "porcentaje_anticipo_minimo", 0))), 6);
        $monto = round(max(0, floatval($this->valor($politica, "monto_anticipo_minimo", 0))), 6);
        return round(max($monto, $total * $porcentaje), 6);
    }

    private function fechaPedidoValida($fecha) {
        $fecha = trim((string) $fecha);
        if ($fecha === "") {
            return false;
        }
        $dt = DateTime::createFromFormat("Y-m-d", substr($fecha, 0, 10));
        return $dt instanceof DateTime && $dt->format("Y-m-d") === substr($fecha, 0, 10);
    }

    private function propuestaReservaDesdePrevalidacion($prevalidacion) {
        $partidas = isset($prevalidacion["depurar"]["partidas"]) && is_array($prevalidacion["depurar"]["partidas"])
            ? $prevalidacion["depurar"]["partidas"]
            : array();
        $reservas = array();
        foreach ($partidas as $partida) {
            $plan = isset($partida["plan_salida_inventario"]) && is_array($partida["plan_salida_inventario"])
                ? $partida["plan_salida_inventario"]
                : array();
            $asignaciones = isset($plan["asignaciones"]) && is_array($plan["asignaciones"]) ? $plan["asignaciones"] : array();
            foreach ($asignaciones as $asignacion) {
                $reservas[] = array(
                    "renglon" => intval($this->valor($partida, "renglon", 0)),
                    "id_sku" => intval($this->valor($partida, "id_sku", 0)),
                    "sku" => $this->valor($partida, "sku", ""),
                    "id_existencia_inventario" => intval($this->valor($asignacion, "id_existencia_inventario", 0)),
                    "id_inventario_unidad" => $this->valor($asignacion, "id_inventario_unidad", null),
                    "cantidad_base" => round(floatval($this->valor($asignacion, "cantidad_base", 0)), 6),
                    "lote" => $this->valor($asignacion, "lote", null),
                    "fecha_caducidad" => $this->valor($asignacion, "fecha_caducidad", null),
                    "modo_reserva" => $this->valor($plan, "modo", "existencia_agregada")
                );
            }
        }
        return array(
            "reservas" => $reservas,
            "total_asignaciones" => count($reservas),
            "cantidad_total" => round(array_sum(array_map(function ($reserva) {
                return floatval($reserva["cantidad_base"]);
            }, $reservas)), 6),
            "contrato" => array(
                "no_escribe_bd" => true,
                "usar_for_update_en_flujo_real" => true,
                "crear_reserva_por_asignacion" => true,
                "actualizar_cantidad_apartada_en_existencia" => true
            )
        );
    }

    private function consultarUnidadPorId($db, $idUnidad, $idAlmacen) {
        $stmt = $db->prepare("SELECT u.*, r.permite_venta_fraccionaria, r.precision_decimal, r.incremento_minimo_venta
            FROM erp_inventario_unidades u
            LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=u.id_sku_erp
            WHERE u.id_inventario_unidad=:unidad
              AND (:almacen=0 OR u.id_almacen=:almacen_filtro)
            LIMIT 1");
        $stmt->execute(array(":unidad" => intval($idUnidad), ":almacen" => intval($idAlmacen), ":almacen_filtro" => intval($idAlmacen)));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: proponer asignacion de salida por existencia/unidad sin descontar inventario.
     * Impacto: prepara kardex y trazabilidad de venta antes de activar transacciones reales.
     * Contrato: read-only; respeta unidad cerrada, granel desde unidad abierta y FIFO por existencia agregada.
     */
    private function planSalidaInventario($db, $idSku, $idAlmacen, $cantidad, $modo, $idUnidad) {
        $cantidad = round(floatval($cantidad), 6);
        if ($cantidad <= 0) {
            return array("modo" => $modo ?: "sin_modo", "asignaciones" => array(), "faltante" => max(0, $cantidad));
        }

        if ($idUnidad > 0) {
            $unidad = $this->consultarUnidadPorId($db, $idUnidad, $idAlmacen);
            if (!$unidad) {
                return array("modo" => $modo ?: "unidad", "asignaciones" => array(), "faltante" => $cantidad);
            }
            $tomar = min($cantidad, floatval($unidad["cantidad_base_disponible"]));
            return array(
                "modo" => $modo ?: $this->dictamenUnidadPos($unidad)["modo"],
                "asignaciones" => array(array(
                    "tipo" => "unidad_fisica",
                    "id_existencia_inventario" => intval($unidad["id_existencia_inventario"]),
                    "id_inventario_unidad" => intval($unidad["id_inventario_unidad"]),
                    "estado_fisico" => $unidad["estado_fisico"],
                    "lote" => $unidad["lote"],
                    "fecha_caducidad" => $unidad["fecha_caducidad"],
                    "cantidad_base" => round($tomar, 6),
                    "cantidad_unidad_antes" => round(floatval($unidad["cantidad_base_disponible"]), 6),
                    "cantidad_unidad_despues" => round(max(0, floatval($unidad["cantidad_base_disponible"]) - $tomar), 6)
                )),
                "faltante" => round(max(0, $cantidad - $tomar), 6)
            );
        }

        $asignaciones = array();
        $pendiente = $cantidad;
        foreach ($this->existenciasDisponiblesVenta($db, $idSku, $idAlmacen) as $existencia) {
            if ($pendiente <= 0) {
                break;
            }
            $disponible = floatval($existencia["cantidad_disponible"]);
            if ($disponible <= 0) {
                continue;
            }
            $tomar = min($pendiente, $disponible);
            $asignaciones[] = array(
                "tipo" => "existencia_agregada",
                "id_existencia_inventario" => intval($existencia["id_existencia_inventario"]),
                "id_inventario_unidad" => null,
                "lote" => $existencia["lote"],
                "fecha_caducidad" => $existencia["fecha_caducidad"],
                "ubicacion_id" => $existencia["ubicacion_id"],
                "ubicacion" => $existencia["ubicacion"],
                "cantidad_base" => round($tomar, 6),
                "cantidad_existencia_antes" => round($disponible, 6),
                "cantidad_existencia_despues" => round(max(0, $disponible - $tomar), 6)
            );
            $pendiente = round($pendiente - $tomar, 6);
        }
        return array(
            "modo" => $modo ?: "existencia_agregada",
            "asignaciones" => $asignaciones,
            "faltante" => round(max(0, $pendiente), 6)
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-06.
     * Proposito: validar acumulado de asignaciones propuestas en dry-run POS.
     * Impacto: evita que partidas duplicadas del mismo SKU/existencia parezcan reservables si en conjunto exceden disponible.
     * Contrato: solo memoria/read-only; no bloquea filas ni modifica inventario.
     */
    private function validarPlanSalidaAcumuladoPos($partidas) {
        $porExistencia = array();
        $porUnidad = array();
        foreach ($partidas as $partida) {
            $plan = isset($partida["plan_salida_inventario"]) && is_array($partida["plan_salida_inventario"])
                ? $partida["plan_salida_inventario"]
                : array();
            $asignaciones = isset($plan["asignaciones"]) && is_array($plan["asignaciones"]) ? $plan["asignaciones"] : array();
            foreach ($asignaciones as $asignacion) {
                $cantidad = round(floatval($this->valor($asignacion, "cantidad_base", 0)), 6);
                $idExistencia = intval($this->valor($asignacion, "id_existencia_inventario", 0));
                $idUnidad = intval($this->valor($asignacion, "id_inventario_unidad", 0));
                if ($idExistencia > 0) {
                    if (!isset($porExistencia[$idExistencia])) {
                        $porExistencia[$idExistencia] = array(
                            "cantidad" => 0,
                            "disponible" => round(floatval($this->valor($asignacion, "cantidad_existencia_antes", $cantidad)), 6)
                        );
                    }
                    $porExistencia[$idExistencia]["cantidad"] = round($porExistencia[$idExistencia]["cantidad"] + $cantidad, 6);
                }
                if ($idUnidad > 0) {
                    if (!isset($porUnidad[$idUnidad])) {
                        $porUnidad[$idUnidad] = array(
                            "cantidad" => 0,
                            "disponible" => round(floatval($this->valor($asignacion, "cantidad_unidad_antes", $cantidad)), 6)
                        );
                    }
                    $porUnidad[$idUnidad]["cantidad"] = round($porUnidad[$idUnidad]["cantidad"] + $cantidad, 6);
                }
            }
        }

        $bloqueos = array();
        foreach ($porExistencia as $idExistencia => $datos) {
            if (floatval($datos["cantidad"]) > floatval($datos["disponible"]) + 0.0001) {
                $bloqueos[] = "La reserva acumulada supera disponibilidad de existencia " . intval($idExistencia);
            }
        }
        foreach ($porUnidad as $idUnidad => $datos) {
            if (floatval($datos["cantidad"]) > floatval($datos["disponible"]) + 0.0001) {
                $bloqueos[] = "La reserva acumulada supera disponibilidad de unidad fisica " . intval($idUnidad);
            }
        }
        return $bloqueos;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-06.
     * Proposito: prevalidar pagos POS sin registrar cobros ni abrir movimientos de caja.
     * Impacto: prepara UX de cobro, incluyendo saldo CRM como pago virtual sin caja.
     * Contrato: read-only; valida metodo activo o pago virtual controlado, monto positivo y saldo/cambio estimado.
     */
    private function prevalidarPagosPos($db, $pagos, $total, $exigirPagoCompleto = true) {
        $resultado = array();
        $bloqueos = array();
        $pagado = 0;
        $metodos = $this->metodosPagoIndexados($db);
        foreach ($pagos as $indice => $pago) {
            $idMetodo = intval($this->valor($pago, "id_metodo_pago", 0));
            $metodoPagoEnviado = trim((string) $this->valor($pago, "metodo_pago", ""));
            $tipoPagoEnviado = trim((string) $this->valor($pago, "tipo_pago", "pago"));
            $esSaldoCrm = $this->esPagoSaldoCrmPos($pago);
            $monto = round(floatval($this->valor($pago, "monto", 0)), 6);
            $referencia = trim((string) $this->valor($pago, "referencia", ""));
            $metodo = isset($metodos[$idMetodo]) ? $metodos[$idMetodo] : null;
            $bloqueosPago = array();
            if (!$metodo && !$esSaldoCrm) {
                $bloqueosPago[] = "Metodo de pago invalido";
            }
            if ($esSaldoCrm && $idMetodo > 0) {
                $bloqueosPago[] = "Saldo CRM debe registrarse como pago virtual sin id_metodo_pago";
            }
            if ($monto <= 0) {
                $bloqueosPago[] = "El monto del pago debe ser mayor a cero";
            }
            if ($esSaldoCrm && $monto - max(0, $total - $pagado) > 0.0001) {
                $bloqueosPago[] = "Saldo CRM no puede generar cambio";
            }
            if ($metodo && stripos($metodo["metodo_pago"], "transfer") !== false && $referencia === "") {
                $bloqueosPago[] = "Captura referencia de transferencia";
            }
            if (!empty($bloqueosPago)) {
                foreach ($bloqueosPago as $bloqueo) {
                    $bloqueos[] = "Pago " . ($indice + 1) . ": " . $bloqueo;
                }
            }
            $pagado += max(0, $monto);
            $resultado[] = array(
                "renglon" => $indice + 1,
                "id_metodo_pago" => $esSaldoCrm ? null : $idMetodo,
                "metodo_pago" => $esSaldoCrm ? "saldo_crm" : ($metodo ? $metodo["metodo_pago"] : $metodoPagoEnviado),
                "tipo_pago" => $esSaldoCrm ? "saldo_cliente" : $tipoPagoEnviado,
                "monto" => $monto,
                "referencia" => $referencia,
                "mueve_caja" => !$esSaldoCrm,
                "requiere_ledger_crm" => $esSaldoCrm,
                "bloqueos" => $bloqueosPago
            );
        }
        if ($exigirPagoCompleto && $total > 0 && $pagado + 0.0001 < $total) {
            $bloqueos[] = "El total pagado no cubre el total estimado";
        }
        return array(
            "pagos" => $resultado,
            "bloqueos" => $bloqueos,
            "pagado_total" => round($pagado, 6),
            "saldo_total" => round(max(0, $total - $pagado), 6),
            "cambio" => round(max(0, $pagado - $total), 6)
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: indexar metodos de pago activos para prevalidacion POS.
     * Impacto: evita registrar pagos con metodos inexistentes cuando se active cobro real.
     * Contrato: read-only y tolerante a tabla faltante.
     */
    private function metodosPagoIndexados($db) {
        $metodos = array();
        foreach ($this->listarMetodosPago($db) as $metodo) {
            if (isset($metodo["estatus"]) && intval($metodo["estatus"]) !== 1 && $metodo["estatus"] !== "activo") {
                continue;
            }
            $metodos[intval($metodo["id_metodo_pago"])] = $metodo;
        }
        return $metodos;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-06.
     * Proposito: reconocer saldo CRM como medio de pago POS virtual para prevalidacion.
     * Impacto: impide que el saldo de cliente se confunda con efectivo, tarjeta o transferencia.
     * Contrato: helper puro; la aplicacion real debe registrar ledger CRM y no crear movimiento de caja.
     */
    private function esPagoSaldoCrmPos($pago) {
        $metodo = strtolower(trim((string) $this->valor($pago, "metodo_pago", "")));
        $tipo = strtolower(trim((string) $this->valor($pago, "tipo_pago", "")));
        return $metodo === "saldo_crm" || $metodo === "saldo cliente" || $tipo === "saldo_cliente";
    }

    private function formatearTicketVenta($venta, $detalles, $pagos, $trazabilidad) {
        $lineas = array();
        $hallazgos = array();
        $ancho = 42;
        $lineas[] = str_pad("ARTIANI ERP", $ancho, " ", STR_PAD_BOTH);
        $lineas[] = str_pad("TICKET POS", $ancho, " ", STR_PAD_BOTH);
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Folio: " . $this->valor($venta, "folio", "");
        $lineas[] = "Fecha: " . $this->valor($venta, "fecha_venta", "");
        $lineas[] = "Tienda: " . $this->textoTicket($this->valor($venta, "nombre_comercial", $this->valor($venta, "almacen", "")), $ancho - 8);
        $lineas[] = "Caja: " . $this->textoTicket(trim((string) $this->valor($venta, "caja_codigo", "") . " " . (string) $this->valor($venta, "caja_nombre", "")), $ancho - 6);
        $lineas[] = "Turno: " . $this->valor($venta, "turno_folio", "");
        $lineas[] = "Cliente: " . ($this->valor($venta, "cliente_nombre_publico", "") ?: "Publico general");
        $lineas[] = str_repeat("-", $ancho);
        foreach ($detalles as $detalle) {
            $sku = $this->valor($detalle, "sku", "");
            $descripcion = $this->textoTicket($this->valor($detalle, "descripcion", ""), $ancho);
            $cantidad = number_format(floatval($this->valor($detalle, "cantidad_venta", 0)), 3, ".", "");
            $unidad = $this->valor($detalle, "unidad_venta", "");
            $precio = number_format(floatval($this->valor($detalle, "precio_unitario", 0)), 2, ".", "");
            $total = number_format(floatval($this->valor($detalle, "total", 0)), 2, ".", "");
            $lineas[] = $sku;
            $lineas[] = $descripcion;
            $lineas[] = $cantidad . " " . $unidad . " x $" . $precio . str_pad("$" . $total, max(1, $ancho - strlen($cantidad . " " . $unidad . " x $" . $precio)), " ", STR_PAD_LEFT);
            $lista = trim((string) $this->valor($detalle, "lista_precio_snapshot", ""));
            if ($lista !== "") {
                $lineas[] = "  Precio: " . $this->textoTicket($lista, $ancho - 10);
            }
            $garantia = trim((string) $this->valor($detalle, "resumen_ticket", ""));
            if ($garantia !== "") {
                $lineas[] = "  Garantia: " . $this->textoTicket($garantia, $ancho - 13);
            } else {
                $lineas[] = "  Garantia: pendiente snapshot";
                $hallazgos[] = array(
                    "id" => "VENTAS-TICKET-001",
                    "severidad" => "media",
                    "mensaje" => "La partida " . $this->valor($detalle, "id_venta_detalle", "") . " no tiene snapshot de garantia guardado"
                );
            }
        }
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = $this->lineaImporte("Subtotal", $this->valor($venta, "subtotal", 0), $ancho);
        if (floatval($this->valor($venta, "descuento_total", 0)) > 0) {
            $lineas[] = $this->lineaImporte("Descuento", $this->valor($venta, "descuento_total", 0), $ancho);
        }
        if (floatval($this->valor($venta, "impuestos_total", 0)) > 0) {
            $lineas[] = $this->lineaImporte("Impuestos", $this->valor($venta, "impuestos_total", 0), $ancho);
        }
        $lineas[] = $this->lineaImporte("TOTAL", $this->valor($venta, "total", 0), $ancho);
        $lineas[] = $this->lineaImporte("Pagado", $this->valor($venta, "pagado_total", 0), $ancho);
        $lineas[] = $this->lineaImporte("Saldo", $this->valor($venta, "saldo_total", 0), $ancho);
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Pagos";
        foreach ($pagos as $pago) {
            $lineas[] = "  " . $this->textoTicket($this->etiquetaPagoPos($pago), 22) . " " . $this->lineaImporte("", $this->valor($pago, "monto", 0), $ancho - 25);
        }
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Operacion: " . strtoupper((string) $this->valor($venta, "estatus", ""));
        $lineas[] = "No fiscal. Conserve este ticket.";
        if (!empty($trazabilidad)) {
            $lineas[] = "Inventario trazado: " . count($trazabilidad) . " mov.";
        }
        $lineas[] = "Gracias por su compra";
        return array(
            "lineas" => $lineas,
            "texto" => implode("\n", $lineas),
            "hallazgos" => $hallazgos
        );
    }

    private function formatearTicketDevolucion($devolucion, $detalles, $pagosReembolso, $movimientoCaja, $componentesFinancieros = array()) {
        $lineas = array();
        $hallazgos = array();
        $ancho = 42;
        $lineas[] = str_pad("ARTIANI ERP", $ancho, " ", STR_PAD_BOTH);
        $lineas[] = str_pad("TICKET DEVOLUCION POS", $ancho, " ", STR_PAD_BOTH);
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Folio dev: " . $this->valor($devolucion, "folio", "");
        $lineas[] = "Venta: " . $this->valor($devolucion, "folio_venta", "");
        $lineas[] = "Fecha: " . $this->valor($devolucion, "fecha_aplicacion", $this->valor($devolucion, "fecha_registro", ""));
        $lineas[] = "Tienda: " . $this->textoTicket($this->valor($devolucion, "nombre_comercial", $this->valor($devolucion, "almacen", "")), $ancho - 8);
        $lineas[] = "Caja: " . $this->textoTicket(trim((string) $this->valor($devolucion, "caja_codigo", "") . " " . (string) $this->valor($devolucion, "caja_nombre", "")), $ancho - 6);
        $lineas[] = "Turno: " . $this->valor($devolucion, "turno_folio", "");
        $lineas[] = "Cliente: " . ($this->valor($devolucion, "cliente_nombre_publico", "") ?: "Publico general");
        $lineas[] = str_repeat("-", $ancho);
        foreach ($detalles as $detalle) {
            $sku = $this->valor($detalle, "sku", "");
            $descripcion = $this->textoTicket($this->valor($detalle, "descripcion", ""), $ancho);
            $cantidad = number_format(floatval($this->valor($detalle, "cantidad_base", 0)), 3, ".", "");
            $unidad = $this->valor($detalle, "unidad_venta", "");
            $importe = number_format(floatval($this->valor($detalle, "importe_reembolso", 0)), 2, ".", "");
            $lineas[] = $sku;
            $lineas[] = $descripcion;
            $lineas[] = $cantidad . " " . $unidad . str_pad("$" . $importe, max(1, $ancho - strlen($cantidad . " " . $unidad)), " ", STR_PAD_LEFT);
            $lineas[] = "  Inventario: " . $this->textoTicket($this->valor($detalle, "decision_inventario", ""), $ancho - 14);
            if (intval($this->valor($detalle, "id_movimiento_inventario_devolucion", 0)) > 0) {
                $lineas[] = "  Kardex entrada: " . $this->valor($detalle, "id_movimiento_inventario_devolucion", "");
            }
        }
        if (empty($detalles)) {
            $hallazgos[] = array(
                "id" => "VENTAS-DEV-TICKET-001",
                "severidad" => "alta",
                "mensaje" => "La devolucion no tiene detalle"
            );
        }
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Decision financiera:";
        $lineas[] = "  " . strtoupper((string) $this->valor($devolucion, "decision_financiera", ""));
        $lineas[] = $this->lineaImporte("Saldo favor", $this->valor($devolucion, "monto_saldo_favor", 0), $ancho);
        $lineas[] = $this->lineaImporte("Reembolso", $this->valor($devolucion, "monto_reembolso", 0), $ancho);
        if (floatval($this->valor($devolucion, "monto_reintegro_saldo_crm", 0)) > 0) {
            $lineas[] = $this->lineaImporte("Reint saldo cliente", $this->valor($devolucion, "monto_reintegro_saldo_crm", 0), $ancho);
        }
        if (floatval($this->valor($devolucion, "monto_no_caja", 0)) > 0) {
            $lineas[] = $this->lineaImporte("No caja", $this->valor($devolucion, "monto_no_caja", 0), $ancho);
        }
        if ($movimientoCaja) {
            $lineas[] = "Caja mov: " . $this->valor($movimientoCaja, "id_movimiento_caja", "");
        }
        if (!empty($componentesFinancieros)) {
            $lineas[] = "Componentes";
            foreach ($componentesFinancieros as $componente) {
                $etiqueta = $this->etiquetaComponenteDevolucionPos($this->valor($componente, "tipo_componente", ""));
                $lineas[] = "  " . $this->textoTicket($etiqueta, 22) . " " . $this->lineaImporte("", $this->valor($componente, "monto", 0), $ancho - 25);
            }
        }
        if (!empty($pagosReembolso)) {
            $lineas[] = "Pagos/reembolsos";
            foreach ($pagosReembolso as $pago) {
                $lineas[] = "  " . $this->textoTicket($this->valor($pago, "metodo_pago", ""), 18) . " " . $this->lineaImporte("", $this->valor($pago, "monto", 0), $ancho - 21);
            }
        }
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Motivo:";
        $lineas[] = $this->textoTicket($this->valor($devolucion, "motivo", ""), $ancho);
        $lineas[] = "Operacion: " . strtoupper((string) $this->valor($devolucion, "estatus", ""));
        $lineas[] = "No fiscal. Conserve este comprobante.";
        $lineas[] = "Gracias";
        return array(
            "lineas" => $lineas,
            "texto" => implode("\n", $lineas),
            "hallazgos" => $hallazgos
        );
    }

    private function etiquetaComponenteDevolucionPos($tipoComponente) {
        $tipo = strtolower(trim((string) $tipoComponente));
        if ($tipo === "reembolso_caja") {
            return "Caja";
        }
        if ($tipo === "reintegro_saldo_crm") {
            return "Saldo cliente";
        }
        if ($tipo === "saldo_favor") {
            return "Saldo favor";
        }
        return $tipoComponente;
    }

    private function formatearCorteTurno($turno, $ventas, $pagos, $movimientos) {
        $lineas = array();
        $hallazgos = array();
        $ancho = 42;
        $estatus = strtolower(trim((string) $this->valor($turno, "estatus", "")));
        $diferencia = round(floatval($this->valor($turno, "diferencia", 0)), 6);
        if ($estatus !== "cerrado") {
            $hallazgos[] = array(
                "id" => "VENTAS-CORTE-001",
                "severidad" => "media",
                "mensaje" => "El corte consultado pertenece a un turno no cerrado"
            );
        }
        if (abs($diferencia) > 0.0001) {
            $hallazgos[] = array(
                "id" => "VENTAS-CORTE-002",
                "severidad" => "media",
                "mensaje" => $diferencia > 0 ? "Corte con sobrante" : "Corte con faltante"
            );
        }
        $lineas[] = str_pad("ARTIANI ERP", $ancho, " ", STR_PAD_BOTH);
        $lineas[] = str_pad("CORTE DE CAJA POS", $ancho, " ", STR_PAD_BOTH);
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Turno: " . $this->valor($turno, "folio", "");
        $lineas[] = "Estatus: " . strtoupper((string) $this->valor($turno, "estatus", ""));
        $lineas[] = "Tienda: " . $this->textoTicket($this->valor($turno, "nombre_comercial", $this->valor($turno, "almacen", "")), $ancho - 8);
        $lineas[] = "Caja: " . $this->textoTicket(trim((string) $this->valor($turno, "caja_codigo", "") . " " . (string) $this->valor($turno, "caja_nombre", "")), $ancho - 6);
        $lineas[] = "Apertura: " . $this->valor($turno, "fecha_apertura", "");
        $lineas[] = "Cierre: " . ($this->valor($turno, "fecha_cierre", "") ?: "Pendiente");
        $usuarioApertura = trim((string) $this->valor($turno, "usuario_apertura", ""));
        $usuarioCierre = trim((string) $this->valor($turno, "usuario_cierre", ""));
        if ($usuarioApertura === "") {
            $usuarioApertura = "Usuario " . $this->valor($turno, "id_usuario_apertura", "");
        }
        if ($usuarioCierre === "") {
            $usuarioCierre = "Usuario " . $this->valor($turno, "id_usuario_cierre", "");
        }
        $lineas[] = "Abre: " . $this->textoTicket($usuarioApertura, $ancho - 6);
        $lineas[] = "Cierra: " . $this->textoTicket($usuarioCierre, $ancho - 8);
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = $this->lineaImporte("Inicial", $this->valor($turno, "monto_inicial", 0), $ancho);
        $lineas[] = $this->lineaImporte("Esperado", $this->valor($turno, "monto_esperado", 0), $ancho);
        $lineas[] = $this->lineaImporte("Contado", $this->valor($turno, "monto_contado", 0), $ancho);
        $lineas[] = $this->lineaImporte("Diferencia", $this->valor($turno, "diferencia", 0), $ancho);
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Ventas";
        $lineas[] = "  Operaciones: " . intval($this->valor($ventas, "operaciones", $this->valor($ventas, "ventas", 0)));
        $lineas[] = "  " . $this->lineaImporte("Subtotal", $this->valor($ventas, "subtotal", 0), $ancho - 2);
        $lineas[] = "  " . $this->lineaImporte("Descuento", $this->valor($ventas, "descuento", 0), $ancho - 2);
        $lineas[] = "  " . $this->lineaImporte("Total", $this->valor($ventas, "total", 0), $ancho - 2);
        $lineas[] = "  " . $this->lineaImporte("Pagado", $this->valor($ventas, "pagado", 0), $ancho - 2);
        $lineas[] = "  " . $this->lineaImporte("Saldo", $this->valor($ventas, "saldo", 0), $ancho - 2);
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Pagos por metodo";
        if (empty($pagos)) {
            $lineas[] = "  Sin pagos registrados";
        }
        $pagosCaja = array();
        $pagosNoCaja = array();
        foreach ($pagos as $pago) {
            if ($this->esPagoSaldoCrmPos($pago)) {
                $pagosNoCaja[] = $pago;
            } else {
                $pagosCaja[] = $pago;
            }
        }
        foreach ($pagosCaja as $pago) {
            $label = $this->textoTicket($this->etiquetaPagoPos($pago), 20);
            $lineas[] = "  " . $label . " " . $this->lineaImporte("", $this->valor($pago, "monto", 0), $ancho - 23);
        }
        if (!empty($pagosNoCaja)) {
            $lineas[] = "Pagos sin caja";
            foreach ($pagosNoCaja as $pago) {
                $label = $this->textoTicket($this->etiquetaPagoPos($pago), 22);
                $lineas[] = "  " . $label . " " . $this->lineaImporte("", $this->valor($pago, "monto", 0), $ancho - 25);
            }
        }
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "Movimientos caja";
        if (empty($movimientos)) {
            $lineas[] = "  Sin movimientos";
        }
        foreach ($movimientos as $movimiento) {
            $label = $this->textoTicket($this->valor($movimiento, "tipo", "") . " " . $this->valor($movimiento, "motivo", ""), 22);
            $lineas[] = "  " . $label . " " . $this->lineaImporte("", $this->valor($movimiento, "monto", 0), $ancho - 25);
        }
        $observaciones = trim((string) $this->valor($turno, "observaciones_cierre", ""));
        if ($observaciones !== "") {
            $lineas[] = str_repeat("-", $ancho);
            $lineas[] = "Obs: " . $this->textoTicket($observaciones, $ancho - 5);
        }
        $lineas[] = str_repeat("-", $ancho);
        $lineas[] = "No fiscal. Corte operativo interno.";
        return array(
            "lineas" => $lineas,
            "texto" => implode("\n", $lineas),
            "hallazgos" => $hallazgos
        );
    }

    private function lineaImporte($etiqueta, $monto, $ancho) {
        $izquierda = $etiqueta !== "" ? $etiqueta . ":" : "";
        $derecha = "$" . number_format(floatval($monto), 2, ".", "");
        return $izquierda . str_pad($derecha, max(1, $ancho - strlen($izquierda)), " ", STR_PAD_LEFT);
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-07.
     * Proposito: mostrar medios de pago POS con una etiqueta operativa clara en tickets y cortes.
     * Impacto: evita que saldo CRM se cuente como efectivo o movimiento de caja durante arqueos.
     * Contrato: helper puro; no altera importes ni persistencia.
     */
    private function etiquetaPagoPos($pago) {
        if ($this->esPagoSaldoCrmPos($pago)) {
            return "Saldo cliente no caja";
        }
        $metodo = trim((string) $this->valor($pago, "metodo_pago", ""));
        $tipo = trim((string) $this->valor($pago, "tipo_pago", ""));
        $label = trim($metodo . " " . $tipo);
        return $label !== "" ? $label : "Pago";
    }

    private function textoTicket($texto, $maximo) {
        $texto = trim(preg_replace('/\s+/', ' ', (string) $texto));
        if ($maximo <= 0 || strlen($texto) <= $maximo) {
            return $texto;
        }
        return substr($texto, 0, max(0, $maximo - 3)) . "...";
    }

    private function bloquearTurnoPosReal($db, $idTurno, $idCaja, $idAlmacen) {
        $stmt = $db->prepare("SELECT * FROM erp_pos_turnos
            WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'
            FOR UPDATE");
        $stmt->execute(array(":turno" => intval($idTurno), ":caja" => intval($idCaja), ":almacen" => intval($idAlmacen)));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function bloquearAtencionPosReal($db, $idAtencion) {
        $stmt = $db->prepare("SELECT * FROM erp_pos_atenciones WHERE id_atencion_pos=:atencion FOR UPDATE");
        $stmt->execute(array(":atencion" => intval($idAtencion)));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function bloquearExcepcionComercialPosReal($db, $folio) {
        $stmt = $db->prepare("SELECT * FROM erp_ventas_excepciones_comerciales WHERE folio=:folio LIMIT 1 FOR UPDATE");
        $stmt->execute(array(":folio" => trim((string) $folio)));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function cargarAtencionPosReal($db, $idAtencion) {
        if (!$this->tablaExiste($db, "erp_pos_atenciones") || !$this->tablaExiste($db, "erp_pos_atenciones_detalle")) {
            return null;
        }
        $stmt = $db->prepare("SELECT * FROM erp_pos_atenciones WHERE id_atencion_pos=:atencion LIMIT 1");
        $stmt->execute(array(":atencion" => intval($idAtencion)));
        $atencion = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$atencion) {
            return null;
        }
        $stmt = $db->prepare("SELECT * FROM erp_pos_atenciones_detalle WHERE id_atencion_pos=:atencion AND estatus='activa' ORDER BY renglon ASC");
        $stmt->execute(array(":atencion" => intval($idAtencion)));
        $items = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
            $items[] = array(
                "id_sku" => intval($detalle["id_sku_erp"]),
                "cantidad" => floatval($detalle["cantidad_venta"]),
                "precio_unitario" => floatval($detalle["precio_unitario"]),
                "modo_salida" => trim((string) $detalle["modo_salida"]) !== "" ? $detalle["modo_salida"] : "existencia_agregada"
            );
        }
        return array("atencion" => $atencion, "items" => $items);
    }

    private function consultarClienteErpPosReal($db, $idCliente) {
        if (!$this->tablaExiste($db, "erp_clientes") || !$this->tablaExiste($db, "erp_clientes_identificadores")) {
            return null;
        }
        $stmt = $db->prepare("SELECT c.id_cliente, c.codigo_cliente, c.nombre_publico, i.valor identificador
            FROM erp_clientes c
            LEFT JOIN erp_clientes_identificadores i ON i.id_cliente=c.id_cliente AND i.estatus='activo'
            WHERE c.id_cliente=:cliente
            ORDER BY i.principal DESC, i.id_cliente_identificador ASC
            LIMIT 1");
        $stmt->execute(array(":cliente" => intval($idCliente)));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cliente ?: null;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: consultar el cliente CRM canonico al confirmar una venta POS real.
     * Impacto: permite guardar `id_cliente_crm` y snapshot historico sin depender de `erp_clientes`.
     * Contrato: read-only dentro de la transaccion de venta; no crea ni modifica clientes.
     */
    private function consultarClienteCrmPosReal($db, $idClienteCrm) {
        if (!$this->schemaClientesCrmDisponible($db) || $idClienteCrm <= 0) {
            return null;
        }
        $stmt = $db->prepare("SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
                i.valor identificador, i.valor_normalizado, i.tipo identificador_tipo
            FROM crm_clientes_maestro c
            LEFT JOIN crm_clientes_identificadores i ON i.id_cliente_crm=c.id_cliente_crm AND i.estatus='activo'
            WHERE c.id_cliente_crm=:cliente
            ORDER BY i.principal DESC, i.id_cliente_identificador ASC
            LIMIT 1");
        $stmt->execute(array(":cliente" => intval($idClienteCrm)));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cliente ?: null;
    }

    private function consultarVentaParaReversaDryRun($db, $idVenta, $folio) {
        $where = $idVenta > 0 ? "id_venta=:referencia" : "folio=:referencia";
        $stmt = $db->prepare("SELECT id_venta, folio, canal, tipo_documento, estatus, id_almacen, id_caja,
                id_turno_caja, id_cliente, id_cliente_crm, cliente_nombre_publico,
                cliente_identificador_publico, subtotal, descuento_total, total,
                pagado_total, saldo_total, fecha_venta, creado_por
            FROM erp_ventas
            WHERE {$where}
            LIMIT 1");
        $stmt->execute(array(":referencia" => $idVenta > 0 ? intval($idVenta) : trim((string) $folio)));
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $venta ?: null;
    }

    private function detallesVentaParaReversaDryRun($db, $idVenta) {
        $stmt = $db->prepare("SELECT id_venta_detalle, id_venta, renglon, id_producto_erp,
                id_sku_erp, sku, descripcion, controla_inventario, modo_salida,
                cantidad_venta, cantidad_base, unidad_base, precio_unitario, subtotal,
                descuento, total, estatus
            FROM erp_ventas_detalle
            WHERE id_venta=:venta AND estatus<>'cancelada'
            ORDER BY renglon ASC, id_venta_detalle ASC");
        $stmt->execute(array(":venta" => intval($idVenta)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function cantidadDevueltaPreviaDryRun($db, $idVenta, $idDetalle) {
        if (!$this->tablaExiste($db, "erp_ventas_devoluciones") || !$this->tablaExiste($db, "erp_ventas_devoluciones_detalle")) {
            return 0;
        }
        $stmt = $db->prepare("SELECT COALESCE(SUM(dd.cantidad_base), 0)
            FROM erp_ventas_devoluciones_detalle dd
            INNER JOIN erp_ventas_devoluciones d ON d.id_devolucion=dd.id_devolucion
            WHERE dd.id_venta=:venta
              AND dd.id_venta_detalle=:detalle
              AND d.estatus NOT IN ('cancelada','rechazada')
              AND dd.estatus NOT IN ('cancelada','rechazada')");
        $stmt->execute(array(":venta" => intval($idVenta), ":detalle" => intval($idDetalle)));
        return round(floatval($stmt->fetchColumn()), 6);
    }

    private function schemaReversasPosCompleto($db) {
        if (!$db) {
            return false;
        }
        foreach (array("erp_ventas", "erp_ventas_detalle", "erp_ventas_devoluciones", "erp_ventas_devoluciones_detalle") as $tabla) {
            if (!$this->tablaExiste($db, $tabla)) {
                return false;
            }
        }
        $columnas = array(
            "erp_ventas_devoluciones" => array(
                "id_caja", "id_almacen", "id_turno_caja", "id_movimiento_caja",
                "decision_financiera", "monto_reembolso", "monto_saldo_favor",
                "autorizado_por", "fecha_autorizacion", "aplicado_por",
                "fecha_aplicacion", "datos_snapshot"
            ),
            "erp_ventas_devoluciones_detalle" => array(
                "id_existencia_inventario", "id_almacen_destino", "importe_reembolso", "datos_snapshot"
            )
        );
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                if (!$this->columnaExiste($db, $tabla, $columna)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function bloquearVentaParaReversaPosReal($db, $idVenta) {
        $stmt = $db->prepare("SELECT * FROM erp_ventas WHERE id_venta=:venta FOR UPDATE");
        $stmt->execute(array(":venta" => intval($idVenta)));
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $venta ?: null;
    }

    private function generarFolioDevolucionPosReal($db, $prefijo) {
        $base = $prefijo . "-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ventas_devoluciones WHERE folio LIKE :folio");
        $stmt->execute(array(":folio" => $base . "%"));
        return $base . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function trazaInventarioVentaDetallePosReal($db, $idVenta, $idDetalle) {
        if (!$this->tablaExiste($db, "erp_ventas_detalle_inventario")) {
            return array();
        }
        $stmt = $db->prepare("SELECT *
            FROM erp_ventas_detalle_inventario
            WHERE id_venta=:venta AND id_venta_detalle=:detalle
            ORDER BY id_venta_detalle_inventario ASC
            LIMIT 1");
        $stmt->execute(array(":venta" => intval($idVenta), ":detalle" => intval($idDetalle)));
        $traza = $stmt->fetch(PDO::FETCH_ASSOC);
        return $traza ?: array();
    }

    private function aplicarEntradaInventarioReversaPosReal($db, $idDevolucion, $folioDevolucion, $partida, $trazaOrigen, $idAlmacen, $cantidad, $idUsuario) {
        $idExistencia = intval($this->valor($trazaOrigen, "id_existencia_inventario", 0));
        if ($idExistencia <= 0) {
            throw new Exception("Existencia origen invalida para reintegro");
        }
        $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias
            WHERE id_existencia_inventario=:existencia AND id_almacen_clave=:almacen
            FOR UPDATE");
        $stmt->execute(array(":existencia" => $idExistencia, ":almacen" => intval($idAlmacen)));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existencia) {
            throw new Exception("Existencia origen no encontrada para reintegro");
        }

        $cantidadAnterior = $this->redondearPosReal($this->valor($existencia, "cantidad", 0));
        $cantidadDisponibleAnterior = $this->redondearPosReal($this->valor($existencia, "cantidad_disponible", 0));
        $cantidadNueva = $this->redondearPosReal($cantidadAnterior + $cantidad);
        $cantidadDisponibleNueva = $this->redondearPosReal($cantidadDisponibleAnterior + $cantidad);
        $db->prepare("UPDATE erp_inventario_existencias
            SET cantidad=:cantidad, cantidad_disponible=:disponible, estatus_existencia='disponible',
                fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:existencia")
            ->execute(array(
                ":cantidad" => $cantidadNueva,
                ":disponible" => $cantidadDisponibleNueva,
                ":existencia" => $idExistencia
            ));

        $costo = $this->redondearPosReal($this->valor($existencia, "costo_promedio", 0));
        $stmt = $db->prepare("INSERT INTO erp_inventario_movimientos
            (id_producto, id_sku_erp, id_almacen, tipo_movimiento, origen_tipo, origen_id,
             id_existencia_inventario, codigo_existencia, lote, fecha_caducidad, ubicacion_id,
             ubicacion, cantidad, costo_unitario, costo_total, existencia_anterior,
             existencia_nueva, referencia, observaciones)
            VALUES (:producto, :sku, :almacen, 'entrada', 'devolucion_pos', :devolucion,
             :existencia, :codigo, :lote, :caducidad, :ubicacion_id, :ubicacion,
             :cantidad, :costo, :costo_total, :anterior, :nueva, :referencia, :observaciones)");
        $stmt->execute(array(
            ":producto" => intval($this->valor($existencia, "id_producto", 0)),
            ":sku" => intval($this->valor($partida, "id_sku_erp", 0)),
            ":almacen" => intval($idAlmacen),
            ":devolucion" => intval($idDevolucion),
            ":existencia" => $idExistencia,
            ":codigo" => $this->valor($existencia, "codigo_existencia", null),
            ":lote" => $this->valor($existencia, "lote", null),
            ":caducidad" => $this->valor($existencia, "fecha_caducidad", null),
            ":ubicacion_id" => $this->valor($existencia, "ubicacion_id", null),
            ":ubicacion" => $this->valor($existencia, "ubicacion", null),
            ":cantidad" => $cantidad,
            ":costo" => $costo,
            ":costo_total" => $this->redondearPosReal($cantidad * $costo),
            ":anterior" => $cantidadAnterior,
            ":nueva" => $cantidadNueva,
            ":referencia" => $folioDevolucion,
            ":observaciones" => "devolucion_pos:" . $folioDevolucion . " | usuario:" . intval($idUsuario)
        ));
        $idMovimiento = intval($db->lastInsertId());
        $db->prepare("UPDATE erp_inventario_existencias SET ultimo_movimiento_id=:movimiento WHERE id_existencia_inventario=:existencia")
            ->execute(array(":movimiento" => $idMovimiento, ":existencia" => $idExistencia));
        return $idMovimiento;
    }

    private function schemaReversasSaldoCrmPosCompleto($db) {
        if (!$db) {
            return false;
        }
        if (!$this->tablaExiste($db, "erp_ventas_devoluciones_finanzas")) {
            return false;
        }
        foreach (array("id_cliente_crm", "monto_reintegro_saldo_crm", "monto_no_caja") as $columna) {
            if (!$this->columnaExiste($db, "erp_ventas_devoluciones", $columna)) {
                return false;
            }
        }
        return true;
    }

    private function prepararComponentesFinancierosReversaPosReal($db, $idVenta, $venta, $decisionFinanciera, $montoEstimado) {
        $montoEstimado = $this->redondearPosReal($montoEstimado);
        $pagos = $this->resumirPagosOriginalesVentaPosReal($db, $idVenta);
        $previas = $this->resumirReversasFinancierasPreviasPosReal($db, $idVenta);
        $cajaDisponible = $this->redondearPosReal(max(0, $this->valor($pagos, "caja_pagada", 0) - $this->valor($previas, "reembolso_caja", 0)));
        $saldoCrmDisponible = $this->redondearPosReal(max(0, $this->valor($pagos, "saldo_crm_pagado", 0) - $this->valor($previas, "reintegro_saldo_crm", 0)));
        $idClienteCrm = intval($this->valor($venta, "id_cliente_crm", 0));
        $montoCaja = 0;
        $montoSaldoCrm = 0;
        $montoSaldoFavor = 0;

        if ($decisionFinanciera === "reembolso_caja") {
            if ($montoEstimado > $cajaDisponible + 0.0001) {
                throw new Exception("Reembolso caja excede caja pagada disponible para esta venta");
            }
            $montoCaja = $montoEstimado;
        } elseif ($decisionFinanciera === "reintegro_saldo_crm") {
            if ($idClienteCrm <= 0) {
                throw new Exception("Reintegro saldo CRM requiere cliente CRM ligado a la venta");
            }
            if ($montoEstimado > $saldoCrmDisponible + 0.0001) {
                throw new Exception("Reintegro saldo CRM excede saldo CRM pagado disponible para esta venta");
            }
            $montoSaldoCrm = $montoEstimado;
        } elseif ($decisionFinanciera === "mixta_saldo_crm") {
            if ($idClienteCrm <= 0) {
                throw new Exception("Reversa mixta saldo CRM requiere cliente CRM ligado a la venta");
            }
            $montoCaja = $this->redondearPosReal(min($montoEstimado, $cajaDisponible));
            $montoSaldoCrm = $this->redondearPosReal($montoEstimado - $montoCaja);
            if ($montoSaldoCrm > $saldoCrmDisponible + 0.0001) {
                throw new Exception("Reversa mixta excede saldo CRM pagado disponible para esta venta");
            }
        } elseif ($decisionFinanciera === "saldo_favor") {
            $montoSaldoFavor = $montoEstimado;
        }

        return array(
            "id_cliente_crm" => $idClienteCrm,
            "decision_financiera" => $decisionFinanciera,
            "monto_estimado" => $montoEstimado,
            "monto_caja" => $this->redondearPosReal($montoCaja),
            "monto_saldo_crm" => $this->redondearPosReal($montoSaldoCrm),
            "monto_saldo_favor" => $this->redondearPosReal($montoSaldoFavor),
            "monto_no_caja" => $this->redondearPosReal($montoSaldoCrm + $montoSaldoFavor),
            "pagos_originales" => $pagos,
            "reversas_previas" => $previas,
            "disponible" => array(
                "caja" => $cajaDisponible,
                "saldo_crm" => $saldoCrmDisponible
            )
        );
    }

    private function resumirPagosOriginalesVentaPosReal($db, $idVenta) {
        $stmt = $db->prepare("SELECT id_movimiento_caja, metodo_pago, tipo_pago, monto
            FROM erp_ventas_pagos
            WHERE id_venta=:venta AND estatus='registrado'
            ORDER BY id_venta_pago ASC");
        $stmt->execute(array(":venta" => intval($idVenta)));
        $caja = 0;
        $saldoCrm = 0;
        $sinCaja = 0;
        $total = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pago) {
            if ($pago["tipo_pago"] === "reembolso") {
                continue;
            }
            $monto = $this->redondearPosReal($this->valor($pago, "monto", 0));
            $total += $monto;
            if ($pago["metodo_pago"] === "saldo_crm" || $pago["tipo_pago"] === "saldo_cliente") {
                $saldoCrm += $monto;
            } elseif (intval($this->valor($pago, "id_movimiento_caja", 0)) > 0) {
                $caja += $monto;
            } else {
                $sinCaja += $monto;
            }
        }
        return array(
            "total_pagado" => $this->redondearPosReal($total),
            "caja_pagada" => $this->redondearPosReal($caja),
            "saldo_crm_pagado" => $this->redondearPosReal($saldoCrm),
            "pagos_sin_caja_no_crm" => $this->redondearPosReal($sinCaja)
        );
    }

    private function resumirReversasFinancierasPreviasPosReal($db, $idVenta) {
        $reembolsoCaja = 0;
        $reintegroSaldoCrm = 0;
        $saldoFavor = 0;
        if ($this->tablaExiste($db, "erp_ventas_devoluciones")) {
            $stmt = $db->prepare("SELECT COALESCE(SUM(monto_reembolso), 0) reembolso,
                    COALESCE(SUM(monto_saldo_favor), 0) saldo_favor
                FROM erp_ventas_devoluciones
                WHERE id_venta=:venta AND estatus NOT IN ('cancelada','rechazada')");
            $stmt->execute(array(":venta" => intval($idVenta)));
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            $reembolsoCaja = $this->redondearPosReal($this->valor($fila, "reembolso", 0));
            $saldoFavor = $this->redondearPosReal($this->valor($fila, "saldo_favor", 0));
        }
        if ($this->tablaExiste($db, "erp_ventas_devoluciones_finanzas")) {
            $stmt = $db->prepare("SELECT tipo_componente, COALESCE(SUM(monto), 0) monto
                FROM erp_ventas_devoluciones_finanzas
                WHERE id_venta=:venta AND estatus NOT IN ('cancelada','rechazada')
                GROUP BY tipo_componente");
            $stmt->execute(array(":venta" => intval($idVenta)));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                if ($fila["tipo_componente"] === "reembolso_caja") {
                    $reembolsoCaja = $this->redondearPosReal(max($reembolsoCaja, $fila["monto"]));
                } elseif ($fila["tipo_componente"] === "reintegro_saldo_crm") {
                    $reintegroSaldoCrm = $this->redondearPosReal($fila["monto"]);
                } elseif ($fila["tipo_componente"] === "saldo_favor") {
                    $saldoFavor = $this->redondearPosReal(max($saldoFavor, $fila["monto"]));
                }
            }
        }
        return array(
            "reembolso_caja" => $reembolsoCaja,
            "reintegro_saldo_crm" => $reintegroSaldoCrm,
            "saldo_favor" => $saldoFavor
        );
    }

    private function registrarComponentesFinancierosReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, $componentes, $idAlmacen, $idCaja, $idTurno, $idMovimientoCaja, $motivo, $idUsuario) {
        $registrados = array();
        $montoCaja = $this->redondearPosReal($this->valor($componentes, "monto_caja", 0));
        $montoSaldoCrm = $this->redondearPosReal($this->valor($componentes, "monto_saldo_crm", 0));
        $montoSaldoFavor = $this->redondearPosReal($this->valor($componentes, "monto_saldo_favor", 0));
        $idClienteCrm = intval($this->valor($componentes, "id_cliente_crm", 0));

        if ($montoCaja > 0) {
            $registrados[] = $this->insertarComponenteFinancieroReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, "reembolso_caja", "egreso", $montoCaja, $idClienteCrm, $idCaja, $idTurno, $idMovimientoCaja, null, null, $motivo, $componentes, $idUsuario);
        }
        if ($montoSaldoCrm > 0) {
            $movimientoSaldo = $this->registrarReintegroSaldoCrmReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, $idClienteCrm, $montoSaldoCrm, $motivo, $componentes, $idUsuario);
            $registrados[] = $this->insertarComponenteFinancieroReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, "reintegro_saldo_crm", "abono", $montoSaldoCrm, $idClienteCrm, $idCaja, $idTurno, null, intval($this->valor($movimientoSaldo, "id_cliente_saldo_cuenta", 0)), intval($this->valor($movimientoSaldo, "id_cliente_saldo_movimiento", 0)), $motivo, $componentes, $idUsuario);
        }
        if ($montoSaldoFavor > 0) {
            $registrados[] = $this->insertarComponenteFinancieroReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, "saldo_favor", "abono", $montoSaldoFavor, $idClienteCrm, $idCaja, $idTurno, null, null, null, $motivo, $componentes, $idUsuario);
        }
        return $registrados;
    }

    private function insertarComponenteFinancieroReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, $tipoComponente, $naturaleza, $monto, $idClienteCrm, $idCaja, $idTurno, $idMovimientoCaja, $idCuentaSaldo, $idMovimientoSaldo, $motivo, $snapshot, $idUsuario) {
        $folio = $this->generarFolioDevolucionFinanzaPosReal($db);
        $stmt = $db->prepare("INSERT INTO erp_ventas_devoluciones_finanzas
            (folio, id_devolucion, id_venta, id_cliente_crm, id_caja, id_turno_caja,
             tipo_componente, naturaleza, monto, moneda, id_movimiento_caja,
             id_cliente_saldo_cuenta, id_cliente_saldo_movimiento, referencia_origen,
             estatus, motivo, datos_snapshot, creado_por, aplicado_por, fecha_aplicacion,
             fecha_actualizacion)
            VALUES (:folio, :devolucion, :venta, :cliente, :caja, :turno,
             :tipo, :naturaleza, :monto, 'MXN', :mov_caja,
             :cuenta_saldo, :mov_saldo, :referencia_origen,
             'aplicado', :motivo, :snapshot, :usuario, :usuario, NOW(), NOW())");
        $stmt->execute(array(
            ":folio" => $folio,
            ":devolucion" => intval($idDevolucion),
            ":venta" => intval($idVenta),
            ":cliente" => intval($idClienteCrm) > 0 ? intval($idClienteCrm) : null,
            ":caja" => intval($idCaja) > 0 ? intval($idCaja) : null,
            ":turno" => intval($idTurno) > 0 ? intval($idTurno) : null,
            ":tipo" => $tipoComponente,
            ":naturaleza" => $naturaleza,
            ":monto" => $this->redondearPosReal($monto),
            ":mov_caja" => intval($idMovimientoCaja) > 0 ? intval($idMovimientoCaja) : null,
            ":cuenta_saldo" => intval($idCuentaSaldo) > 0 ? intval($idCuentaSaldo) : null,
            ":mov_saldo" => intval($idMovimientoSaldo) > 0 ? intval($idMovimientoSaldo) : null,
            ":referencia_origen" => $folioDevolucion,
            ":motivo" => $motivo,
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ":usuario" => intval($idUsuario)
        ));
        return array(
            "id_devolucion_finanza" => intval($db->lastInsertId()),
            "folio" => $folio,
            "tipo_componente" => $tipoComponente,
            "monto" => $this->redondearPosReal($monto),
            "id_movimiento_caja" => intval($idMovimientoCaja) > 0 ? intval($idMovimientoCaja) : null,
            "id_cliente_saldo_movimiento" => intval($idMovimientoSaldo) > 0 ? intval($idMovimientoSaldo) : null
        );
    }

    private function registrarReintegroSaldoCrmReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, $idClienteCrm, $monto, $motivo, $snapshot, $idUsuario) {
        if (!$this->tablaExiste($db, "crm_clientes_saldos_cuentas") || !$this->tablaExiste($db, "crm_clientes_saldos_movimientos")) {
            throw new Exception("Reintegro saldo CRM requiere tablas de saldos CRM");
        }
        if (intval($idClienteCrm) <= 0) {
            throw new Exception("Reintegro saldo CRM requiere cliente CRM");
        }
        $stmt = $db->prepare("SELECT *
            FROM crm_clientes_saldos_cuentas
            WHERE id_cliente_crm=:cliente AND moneda='MXN' AND estatus='activa'
            LIMIT 1
            FOR UPDATE");
        $stmt->execute(array(":cliente" => intval($idClienteCrm)));
        $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cuenta) {
            throw new Exception("Cliente CRM sin cuenta de saldos MXN activa para reintegro");
        }
        $monto = $this->redondearPosReal($monto);
        $saldoAnterior = $this->redondearPosReal($this->valor($cuenta, "saldo_disponible", 0));
        $saldoResultante = $this->redondearPosReal($saldoAnterior + $monto);
        $folioMovimiento = $this->generarFolioSaldoCrmPosReal($db);
        $stmt = $db->prepare("INSERT INTO crm_clientes_saldos_movimientos
            (id_cliente_saldo_cuenta, id_cliente_crm, folio, tipo, naturaleza, moneda,
             monto, saldo_anterior, saldo_resultante, origen_modulo, origen_tipo, origen_id,
             referencia_externa, descripcion, datos_snapshot, estatus, creado_por)
            VALUES
            (:cuenta, :cliente, :folio_mov, 'reintegro_devolucion_pos', 'abono', 'MXN',
             :monto, :saldo_anterior, :saldo_resultante, 'ventas_pos', 'devolucion_pos_reintegro_saldo_crm', :origen,
             :referencia, :descripcion, :snapshot, 'aplicado', :usuario)");
        $stmt->execute(array(
            ":cuenta" => intval($cuenta["id_cliente_saldo_cuenta"]),
            ":cliente" => intval($idClienteCrm),
            ":folio_mov" => $folioMovimiento,
            ":monto" => $monto,
            ":saldo_anterior" => $saldoAnterior,
            ":saldo_resultante" => $saldoResultante,
            ":origen" => $folioDevolucion,
            ":referencia" => $folioDevolucion,
            ":descripcion" => "Reintegro saldo CRM por devolucion POS " . $folioDevolucion,
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ":usuario" => intval($idUsuario)
        ));
        $idMovimientoSaldo = intval($db->lastInsertId());
        $db->prepare("UPDATE crm_clientes_saldos_cuentas
            SET saldo_disponible=:saldo, saldo_total=ROUND(saldo_retenido+:saldo, 6),
                fecha_actualizacion=NOW(), actualizado_por=:usuario
            WHERE id_cliente_saldo_cuenta=:cuenta")
            ->execute(array(
                ":saldo" => $saldoResultante,
                ":usuario" => intval($idUsuario),
                ":cuenta" => intval($cuenta["id_cliente_saldo_cuenta"])
            ));
        $this->registrarEventosReintegroSaldoCrmReversaPosReal($db, $idVenta, $folioDevolucion, $idClienteCrm, $monto, $folioMovimiento, $snapshot, $idUsuario);
        return array(
            "id_cliente_saldo_cuenta" => intval($cuenta["id_cliente_saldo_cuenta"]),
            "id_cliente_saldo_movimiento" => $idMovimientoSaldo,
            "folio_movimiento_saldo" => $folioMovimiento,
            "saldo_anterior" => $saldoAnterior,
            "saldo_resultante" => $saldoResultante
        );
    }

    private function registrarEventosReintegroSaldoCrmReversaPosReal($db, $idVenta, $folioDevolucion, $idClienteCrm, $monto, $folioMovimiento, $snapshot, $idUsuario) {
        if ($this->tablaExiste($db, "erp_ventas_eventos")) {
            $stmt = $db->prepare("INSERT INTO erp_ventas_eventos
                (id_venta, folio, tipo_evento, estatus_anterior, estatus_nuevo, monto, referencia, datos_snapshot, observaciones, creado_por)
                VALUES (:venta, :folio, 'reintegro_saldo_crm_devolucion_pos', NULL, NULL, :monto, :referencia, :snapshot, :observaciones, :usuario)");
            $stmt->execute(array(
                ":venta" => intval($idVenta),
                ":folio" => $folioDevolucion,
                ":monto" => $this->redondearPosReal($monto),
                ":referencia" => $folioMovimiento,
                ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                ":observaciones" => "Reintegro saldo CRM por devolucion POS",
                ":usuario" => intval($idUsuario)
            ));
        }
        if ($this->tablaExiste($db, "crm_clientes_eventos")) {
            $stmt = $db->prepare("INSERT INTO crm_clientes_eventos
                (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
                VALUES (:cliente, 'saldo_crm_reintegrado_devolucion_pos', 'ventas_pos', 'devolucion_pos_reintegro_saldo_crm', :origen, :resumen, :snapshot, :usuario)");
            $stmt->execute(array(
                ":cliente" => intval($idClienteCrm),
                ":origen" => $folioDevolucion,
                ":resumen" => "Saldo CRM reintegrado por devolucion POS " . $folioDevolucion,
                ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                ":usuario" => intval($idUsuario)
            ));
        }
    }

    private function generarFolioDevolucionFinanzaPosReal($db) {
        $prefijo = "DFIN-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ventas_devoluciones_finanzas WHERE folio LIKE :folio");
        $stmt->execute(array(":folio" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function registrarReembolsoCajaReversaPosReal($db, $idDevolucion, $idVenta, $folioDevolucion, $idAlmacen, $idCaja, $idTurno, $monto, $motivo, $idUsuario) {
        $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
            (id_turno_caja, id_caja, id_almacen, tipo, categoria, motivo, monto, estatus,
             referencia, id_venta, requiere_autorizacion, autorizado_por, fecha_autorizacion,
             requiere_evidencia, evidencia_estado, observaciones, creado_por, fecha_registro, fecha_actualizacion)
            VALUES (:turno, :caja, :almacen, 'reembolso', 'reembolso_cliente', 'reembolso_cliente',
             :monto, 'registrado', :referencia, :venta, 1, :usuario, NOW(), 1, 'pendiente',
             :observaciones, :usuario, NOW(), NOW())");
        $stmt->execute(array(
            ":turno" => intval($idTurno),
            ":caja" => intval($idCaja),
            ":almacen" => intval($idAlmacen),
            ":monto" => $this->redondearPosReal($monto),
            ":referencia" => $folioDevolucion,
            ":venta" => intval($idVenta),
            ":usuario" => intval($idUsuario),
            ":observaciones" => "Reembolso POS devolucion " . $folioDevolucion . " | " . $motivo
        ));
        return intval($db->lastInsertId());
    }

    private function registrarPagoReembolsoVentaPosReal($db, $idVenta, $idCaja, $idTurno, $idMovimientoCaja, $folioDevolucion, $monto, $idUsuario) {
        $stmt = $db->prepare("INSERT INTO erp_ventas_pagos
            (id_venta, id_caja, id_turno_caja, id_movimiento_caja, id_metodo_pago,
             metodo_pago, tipo_pago, monto, moneda, referencia, estatus, creado_por)
            VALUES (:venta, :caja, :turno, :movimiento, NULL,
             'Reembolso caja', 'reembolso', :monto, 'MXN', :referencia, 'registrado', :usuario)");
        $stmt->execute(array(
            ":venta" => intval($idVenta),
            ":caja" => intval($idCaja),
            ":turno" => intval($idTurno),
            ":movimiento" => intval($idMovimientoCaja),
            ":monto" => $this->redondearPosReal($monto),
            ":referencia" => $folioDevolucion,
            ":usuario" => intval($idUsuario)
        ));
        return intval($db->lastInsertId());
    }

    private function estatusVentaDespuesReversaPosReal($db, $idVenta, $tipo) {
        if ($tipo === "cancelacion") {
            return "cancelada";
        }
        $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad_base), 0) FROM erp_ventas_detalle
            WHERE id_venta=:venta AND estatus<>'cancelada'");
        $stmt->execute(array(":venta" => intval($idVenta)));
        $vendido = $this->redondearPosReal($stmt->fetchColumn());
        $stmt = $db->prepare("SELECT COALESCE(SUM(dd.cantidad_base), 0)
            FROM erp_ventas_devoluciones_detalle dd
            INNER JOIN erp_ventas_devoluciones d ON d.id_devolucion=dd.id_devolucion
            WHERE dd.id_venta=:venta
              AND d.estatus NOT IN ('cancelada','rechazada')
              AND dd.estatus NOT IN ('cancelada','rechazada')");
        $stmt->execute(array(":venta" => intval($idVenta)));
        $devuelto = $this->redondearPosReal($stmt->fetchColumn());
        return $devuelto + 0.0001 >= $vendido ? "devuelta" : "devolucion_parcial";
    }

    private function indexarPreciosPorRenglonPosReal($partidas) {
        $indexadas = array();
        foreach ($partidas as $partida) {
            if (isset($partida["renglon"])) {
                $indexadas[intval($partida["renglon"])] = $partida;
            }
        }
        return $indexadas;
    }

    private function generarFolioVentaPosReal($db, $prefijo) {
        $base = $prefijo . "-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ventas WHERE folio LIKE :folio");
        $stmt->execute(array(":folio" => $base . "%"));
        return $base . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function generarFolioInventarioPendientePosReal($db) {
        $base = "PINV-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_pos_inventario_pendientes WHERE folio LIKE :folio");
        $stmt->execute(array(":folio" => $base . "%"));
        return $base . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function aplicarSalidaInventarioPosReal($db, $idVenta, $idDetalle, $folio, $sku, $asignacionInv, $idAlmacen, $idUsuario) {
        $idExistencia = intval($this->valorRutaPosReal($asignacionInv, array("id_existencia_inventario"), 0));
        $idUnidad = intval($this->valorRutaPosReal($asignacionInv, array("id_inventario_unidad"), 0));
        $cantidad = $this->redondearPosReal($this->valorRutaPosReal($asignacionInv, array("cantidad_base"), 0));
        if ($idExistencia <= 0 || $cantidad <= 0) {
            throw new Exception("Asignacion de inventario invalida para venta POS");
        }

        $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:existencia AND id_almacen_clave=:almacen FOR UPDATE");
        $stmt->execute(array(":existencia" => $idExistencia, ":almacen" => intval($idAlmacen)));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existencia) {
            throw new Exception("Existencia no encontrada o fuera de almacen POS");
        }
        if (floatval($existencia["cantidad_disponible"]) + 0.0001 < $cantidad) {
            throw new Exception("Existencia insuficiente al confirmar venta POS");
        }

        $cantidadAnterior = $this->redondearPosReal($existencia["cantidad"]);
        $cantidadDisponibleAnterior = $this->redondearPosReal($existencia["cantidad_disponible"]);
        $cantidadNueva = $this->redondearPosReal($cantidadAnterior - $cantidad);
        $cantidadDisponibleNueva = $this->redondearPosReal($cantidadDisponibleAnterior - $cantidad);
        $estatusExistencia = $cantidadNueva > 0.0001 ? "disponible" : "agotada";

        $db->prepare("UPDATE erp_inventario_existencias
            SET cantidad=:cantidad, cantidad_disponible=:disponible, estatus_existencia=:estatus, fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:existencia")
            ->execute(array(":cantidad" => $cantidadNueva, ":disponible" => $cantidadDisponibleNueva, ":estatus" => $estatusExistencia, ":existencia" => $idExistencia));

        $unidadAntes = 0;
        $unidadDespues = 0;
        $estadoUnidadDespues = null;
        if ($idUnidad > 0) {
            $stmt = $db->prepare("SELECT * FROM erp_inventario_unidades
                WHERE id_inventario_unidad=:unidad AND id_existencia_inventario=:existencia AND id_almacen=:almacen
                FOR UPDATE");
            $stmt->execute(array(":unidad" => $idUnidad, ":existencia" => $idExistencia, ":almacen" => intval($idAlmacen)));
            $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$unidad || $unidad["estatus"] !== "disponible") {
                throw new Exception("Unidad fisica no disponible al confirmar venta POS");
            }
            $unidadAntes = $this->redondearPosReal($unidad["cantidad_base_disponible"]);
            if ($unidadAntes + 0.0001 < $cantidad) {
                throw new Exception("Contenido insuficiente en unidad fisica al confirmar venta POS");
            }
            $unidadDespues = $this->redondearPosReal($unidadAntes - $cantidad);
            if ($unidad["estado_fisico"] === "cerrada" && abs($unidadAntes - $cantidad) > 0.0001) {
                throw new Exception("La unidad cerrada debe venderse completa");
            }
            if ($unidadDespues <= 0.0001) {
                $estadoUnidadDespues = $unidad["estado_fisico"] === "cerrada" ? "vendida" : "agotada";
                $estatusUnidad = $estadoUnidadDespues;
            } else {
                $estadoUnidadDespues = "abierta";
                $estatusUnidad = "disponible";
            }
            $db->prepare("UPDATE erp_inventario_unidades
                SET cantidad_base_disponible=:disponible, estado_fisico=:estado, estatus=:estatus, fecha_actualizacion=NOW()
                WHERE id_inventario_unidad=:unidad")
                ->execute(array(":disponible" => $unidadDespues, ":estado" => $estadoUnidadDespues, ":estatus" => $estatusUnidad, ":unidad" => $idUnidad));
        }

        $costo = $this->redondearPosReal($this->valor($existencia, "costo_promedio", 0));
        $stmt = $db->prepare("INSERT INTO erp_inventario_movimientos
            (id_producto, id_sku_erp, id_almacen, tipo_movimiento, origen_tipo, origen_id,
             id_existencia_inventario, codigo_existencia, lote, fecha_caducidad, ubicacion_id,
             ubicacion, cantidad, costo_unitario, costo_total, existencia_anterior,
             existencia_nueva, referencia, observaciones)
            VALUES (:producto, :sku, :almacen, 'salida', 'venta_pos', :venta,
             :existencia, :codigo, :lote, :caducidad, :ubicacion_id, :ubicacion,
             :cantidad, :costo, :costo_total, :anterior, :nueva, :referencia, :observaciones)");
        $stmt->execute(array(
            ":producto" => intval($sku["id_producto_erp"]),
            ":sku" => intval($sku["id_sku"]),
            ":almacen" => intval($idAlmacen),
            ":venta" => intval($idVenta),
            ":existencia" => $idExistencia,
            ":codigo" => $this->valor($existencia, "codigo_existencia", null),
            ":lote" => $this->valor($existencia, "lote", null),
            ":caducidad" => $this->valor($existencia, "fecha_caducidad", null),
            ":ubicacion_id" => $this->valor($existencia, "ubicacion_id", null),
            ":ubicacion" => $this->valor($existencia, "ubicacion", null),
            ":cantidad" => $cantidad,
            ":costo" => $costo,
            ":costo_total" => $this->redondearPosReal($cantidad * $costo),
            ":anterior" => $cantidadAnterior,
            ":nueva" => $cantidadNueva,
            ":referencia" => $folio,
            ":observaciones" => "venta_pos:" . $folio . " | usuario:" . intval($idUsuario)
        ));
        $idMovimiento = intval($db->lastInsertId());

        $db->prepare("UPDATE erp_inventario_existencias SET ultimo_movimiento_id=:movimiento WHERE id_existencia_inventario=:existencia")
            ->execute(array(":movimiento" => $idMovimiento, ":existencia" => $idExistencia));

        $db->prepare("INSERT INTO erp_ventas_detalle_inventario
            (id_venta, id_venta_detalle, id_existencia_inventario, id_inventario_unidad,
             id_movimiento_inventario, id_almacen, lote, fecha_caducidad, ubicacion_id,
             cantidad_base, cantidad_unidad_antes, cantidad_unidad_despues,
             estado_unidad_despues, estatus)
            VALUES (:venta, :detalle, :existencia, :unidad, :movimiento, :almacen,
             :lote, :caducidad, :ubicacion_id, :cantidad, :unidad_antes,
             :unidad_despues, :estado_unidad, 'confirmada')")
            ->execute(array(
                ":venta" => intval($idVenta),
                ":detalle" => intval($idDetalle),
                ":existencia" => $idExistencia,
                ":unidad" => $idUnidad > 0 ? $idUnidad : null,
                ":movimiento" => $idMovimiento,
                ":almacen" => intval($idAlmacen),
                ":lote" => $this->valor($existencia, "lote", null),
                ":caducidad" => $this->valor($existencia, "fecha_caducidad", null),
                ":ubicacion_id" => $this->valor($existencia, "ubicacion_id", null),
                ":cantidad" => $cantidad,
                ":unidad_antes" => $unidadAntes,
                ":unidad_despues" => $unidadDespues,
                ":estado_unidad" => $estadoUnidadDespues
            ));

        return array(
            "id_existencia_inventario" => $idExistencia,
            "id_inventario_unidad" => $idUnidad ?: null,
            "id_movimiento_inventario" => $idMovimiento,
            "cantidad_base" => $cantidad,
            "existencia_anterior" => $cantidadAnterior,
            "existencia_nueva" => $cantidadNueva,
            "unidad_antes" => $unidadAntes,
            "unidad_despues" => $unidadDespues,
            "estado_unidad_despues" => $estadoUnidadDespues
        );
    }

    private function registrarPagosPosReal($db, $idVenta, $folio, $datosVenta, $pagos, $total, $idUsuario, $idClienteCrm = 0, $clienteSnapshot = null) {
        $pendiente = $this->redondearPosReal($total);
        $evidencia = array();
        foreach ($pagos as $pago) {
            if ($pendiente <= 0.0001) {
                break;
            }
            if ($this->esPagoSaldoCrmPos($pago)) {
                $evidenciaSaldo = $this->registrarPagoSaldoCrmPosReal(
                    $db,
                    $idVenta,
                    $folio,
                    $datosVenta,
                    $pago,
                    $pendiente,
                    $idUsuario,
                    $idClienteCrm,
                    $clienteSnapshot
                );
                $evidencia[] = $evidenciaSaldo;
                $pendiente = $this->redondearPosReal($pendiente - floatval($evidenciaSaldo["monto_aplicado"]));
                continue;
            }
            $monto = $this->redondearPosReal(min($pendiente, $this->valor($pago, "monto", 0)));
            if ($monto <= 0) {
                continue;
            }
            $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
                (id_turno_caja, id_caja, id_almacen, tipo, categoria, motivo, monto, estatus, id_venta,
                 referencia, requiere_autorizacion, requiere_evidencia, observaciones, creado_por, fecha_registro, fecha_actualizacion)
                VALUES (:turno, :caja, :almacen, 'ingreso', 'venta_pos', 'venta_pos', :monto, 'registrado', :venta,
                 :referencia, 0, 0, :observaciones, :usuario, NOW(), NOW())");
            $stmt->execute(array(
                ":turno" => $datosVenta["id_turno_caja"],
                ":caja" => $datosVenta["id_caja"],
                ":almacen" => $datosVenta["id_almacen"],
                ":monto" => $monto,
                ":venta" => intval($idVenta),
                ":referencia" => $folio,
                ":observaciones" => "Pago venta POS " . $folio,
                ":usuario" => intval($idUsuario)
            ));
            $idMovimientoCaja = intval($db->lastInsertId());

            $stmt = $db->prepare("INSERT INTO erp_ventas_pagos
                (id_venta, id_caja, id_turno_caja, id_movimiento_caja, id_metodo_pago,
                 metodo_pago, monto, moneda, referencia, estatus, creado_por)
                VALUES (:venta, :caja, :turno, :movimiento, :metodo_id,
                 :metodo, :monto, 'MXN', :referencia, 'registrado', :usuario)");
            $stmt->execute(array(
                ":venta" => intval($idVenta),
                ":caja" => $datosVenta["id_caja"],
                ":turno" => $datosVenta["id_turno_caja"],
                ":movimiento" => $idMovimientoCaja,
                ":metodo_id" => intval($this->valor($pago, "id_metodo_pago", 0)),
                ":metodo" => $this->valor($pago, "metodo_pago", ""),
                ":monto" => $monto,
                ":referencia" => $this->valor($pago, "referencia", null),
                ":usuario" => intval($idUsuario)
            ));
            $evidencia[] = array(
                "id_venta_pago" => intval($db->lastInsertId()),
                "id_movimiento_caja" => $idMovimientoCaja,
                "metodo_pago" => $this->valor($pago, "metodo_pago", ""),
                "monto_aplicado" => $monto,
                "mueve_caja" => true
            );
            $pendiente = $this->redondearPosReal($pendiente - $monto);
        }
        if ($pendiente > 0.0001) {
            throw new Exception("Pagos insuficientes durante registro transaccional");
        }
        return $evidencia;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-07.
     * Proposito: registrar uso de saldo monetario CRM como pago POS sin movimiento de caja.
     * Impacto: descuenta cuenta CRM, crea ledger de saldo, registra pago POS y eventos trazables.
     * Contrato: solo debe ejecutarse dentro de la transaccion de venta POS real.
     */
    private function registrarPagoSaldoCrmPosReal($db, $idVenta, $folio, $datosVenta, $pago, $pendiente, $idUsuario, $idClienteCrm, $clienteSnapshot) {
        if (!$this->tablaExiste($db, "crm_clientes_saldos_cuentas") || !$this->tablaExiste($db, "crm_clientes_saldos_movimientos")) {
            throw new Exception("Saldo CRM requiere tablas de cuenta y movimientos CRM");
        }
        if (intval($idClienteCrm) <= 0) {
            throw new Exception("Saldo CRM requiere cliente CRM ligado a la venta");
        }
        $montoSolicitado = $this->redondearPosReal($this->valor($pago, "monto", 0));
        $monto = $this->redondearPosReal(min($pendiente, $montoSolicitado));
        if ($monto <= 0) {
            throw new Exception("Monto saldo CRM invalido");
        }
        if ($montoSolicitado - $pendiente > 0.0001) {
            throw new Exception("Saldo CRM no puede generar cambio");
        }

        $stmt = $db->prepare("SELECT *
            FROM crm_clientes_saldos_cuentas
            WHERE id_cliente_crm=:cliente AND moneda='MXN' AND estatus='activa'
            LIMIT 1
            FOR UPDATE");
        $stmt->execute(array(":cliente" => intval($idClienteCrm)));
        $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cuenta) {
            throw new Exception("Cliente CRM sin cuenta de saldos MXN activa");
        }

        $saldoAnterior = $this->redondearPosReal($this->valor($cuenta, "saldo_disponible", 0));
        if ($saldoAnterior + 0.0001 < $monto) {
            throw new Exception("Saldo CRM insuficiente al confirmar venta POS");
        }
        $saldoResultante = $this->redondearPosReal($saldoAnterior - $monto);
        $folioMovimiento = $this->generarFolioSaldoCrmPosReal($db);
        $snapshot = array(
            "venta" => array(
                "id_venta" => intval($idVenta),
                "folio" => $folio,
                "id_almacen" => intval($this->valor($datosVenta, "id_almacen", 0)),
                "id_caja" => intval($this->valor($datosVenta, "id_caja", 0)),
                "id_turno_caja" => intval($this->valor($datosVenta, "id_turno_caja", 0))
            ),
            "cliente" => is_string($clienteSnapshot) && $clienteSnapshot !== "" ? json_decode($clienteSnapshot, true) : null,
            "pago" => array(
                "metodo_pago" => "saldo_crm",
                "tipo_pago" => "saldo_cliente",
                "monto" => $monto
            )
        );

        $stmt = $db->prepare("INSERT INTO crm_clientes_saldos_movimientos
            (id_cliente_saldo_cuenta, id_cliente_crm, folio, tipo, naturaleza, moneda,
             monto, saldo_anterior, saldo_resultante, origen_modulo, origen_tipo, origen_id,
             referencia_externa, descripcion, datos_snapshot, estatus, creado_por)
            VALUES
            (:cuenta, :cliente, :folio_mov, 'uso_saldo_pos', 'cargo', 'MXN',
             :monto, :saldo_anterior, :saldo_resultante, 'ventas_pos', 'venta_pos_pago_saldo_crm', :origen,
             :referencia, :descripcion, :snapshot, 'aplicado', :usuario)");
        $stmt->execute(array(
            ":cuenta" => intval($cuenta["id_cliente_saldo_cuenta"]),
            ":cliente" => intval($idClienteCrm),
            ":folio_mov" => $folioMovimiento,
            ":monto" => $monto,
            ":saldo_anterior" => $saldoAnterior,
            ":saldo_resultante" => $saldoResultante,
            ":origen" => $folio,
            ":referencia" => $folio,
            ":descripcion" => "Uso de saldo CRM en venta POS " . $folio,
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ":usuario" => intval($idUsuario)
        ));
        $idMovimientoSaldo = intval($db->lastInsertId());

        $db->prepare("UPDATE crm_clientes_saldos_cuentas
            SET saldo_disponible=:saldo, saldo_total=ROUND(saldo_retenido+:saldo, 6),
                fecha_actualizacion=NOW(), actualizado_por=:usuario
            WHERE id_cliente_saldo_cuenta=:cuenta")
            ->execute(array(
                ":saldo" => $saldoResultante,
                ":usuario" => intval($idUsuario),
                ":cuenta" => intval($cuenta["id_cliente_saldo_cuenta"])
            ));

        $stmtPago = $db->prepare("INSERT INTO erp_ventas_pagos
            (id_venta, id_caja, id_turno_caja, id_movimiento_caja, id_metodo_pago,
             metodo_pago, tipo_pago, monto, moneda, referencia, estatus, creado_por)
            VALUES (:venta, :caja, :turno, NULL, NULL,
             'saldo_crm', 'saldo_cliente', :monto, 'MXN', :referencia, 'registrado', :usuario)");
        $stmtPago->execute(array(
            ":venta" => intval($idVenta),
            ":caja" => intval($this->valor($datosVenta, "id_caja", 0)) ?: null,
            ":turno" => intval($this->valor($datosVenta, "id_turno_caja", 0)) ?: null,
            ":monto" => $monto,
            ":referencia" => $folioMovimiento,
            ":usuario" => intval($idUsuario)
        ));
        $idVentaPago = intval($db->lastInsertId());

        $this->registrarEventosSaldoCrmPosReal($db, $idVenta, $folio, $idClienteCrm, $monto, $folioMovimiento, $snapshot, $idUsuario);

        return array(
            "id_venta_pago" => $idVentaPago,
            "id_movimiento_caja" => null,
            "id_cliente_saldo_movimiento" => $idMovimientoSaldo,
            "folio_movimiento_saldo" => $folioMovimiento,
            "metodo_pago" => "saldo_crm",
            "tipo_pago" => "saldo_cliente",
            "monto_aplicado" => $monto,
            "saldo_anterior" => $saldoAnterior,
            "saldo_resultante" => $saldoResultante,
            "mueve_caja" => false
        );
    }

    private function registrarEventosSaldoCrmPosReal($db, $idVenta, $folio, $idClienteCrm, $monto, $folioMovimiento, $snapshot, $idUsuario) {
        if ($this->tablaExiste($db, "erp_ventas_eventos")) {
            $stmtVenta = $db->prepare("INSERT INTO erp_ventas_eventos
                (id_venta, folio, tipo_evento, estatus_anterior, estatus_nuevo, monto, referencia, datos_snapshot, observaciones, creado_por)
                VALUES (:venta, :folio, 'pago_saldo_crm_aplicado', NULL, NULL, :monto, :referencia, :snapshot, :observaciones, :usuario)");
            $stmtVenta->execute(array(
                ":venta" => intval($idVenta),
                ":folio" => $folio,
                ":monto" => $this->redondearPosReal($monto),
                ":referencia" => $folioMovimiento,
                ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                ":observaciones" => "Pago POS con saldo CRM sin movimiento de caja",
                ":usuario" => intval($idUsuario)
            ));
        }
        if ($this->tablaExiste($db, "crm_clientes_eventos")) {
            $stmtCrm = $db->prepare("INSERT INTO crm_clientes_eventos
                (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
                VALUES (:cliente, 'saldo_crm_usado_pos', 'ventas_pos', 'venta_pos_pago_saldo_crm', :origen, :resumen, :snapshot, :usuario)");
            $stmtCrm->execute(array(
                ":cliente" => intval($idClienteCrm),
                ":origen" => $folio,
                ":resumen" => "Saldo CRM usado en venta POS " . $folio,
                ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                ":usuario" => intval($idUsuario)
            ));
        }
    }

    private function generarFolioSaldoCrmPosReal($db) {
        $prefijo = "CRM-SAL-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_saldos_movimientos WHERE folio LIKE :folio");
        $stmt->execute(array(":folio" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function schemaPedidosApartadosCompleto($db) {
        if (!$this->schemaVentaPosCompleto($db)) {
            return false;
        }
        foreach (array("erp_ventas_eventos", "erp_ventas_politicas_apartado", "erp_inventario_reservas", "erp_inventario_movimientos") as $tabla) {
            if (!$this->tablaExiste($db, $tabla)) {
                return false;
            }
        }
        return true;
    }

    private function bloquearPedidoApartadoPosReal($db, $idVenta, $folio) {
        if ($idVenta > 0) {
            $stmt = $db->prepare("SELECT * FROM erp_ventas WHERE id_venta=:venta FOR UPDATE");
            $stmt->execute(array(":venta" => intval($idVenta)));
        } else {
            $stmt = $db->prepare("SELECT * FROM erp_ventas WHERE folio=:folio FOR UPDATE");
            $stmt->execute(array(":folio" => trim((string) $folio)));
        }
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        return $venta ?: null;
    }

    private function crearReservaPedidoPosReal($db, $idVenta, $idDetalle, $folioVenta, $reservaPlan, $fechaVencimiento, $idUsuario) {
        $idExistencia = intval($this->valor($reservaPlan, "id_existencia_inventario", 0));
        $cantidad = $this->redondearPosReal($this->valor($reservaPlan, "cantidad_base", 0));
        if ($idExistencia <= 0 || $cantidad <= 0) {
            throw new Exception("Asignacion de reserva invalida");
        }
        $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:existencia FOR UPDATE");
        $stmt->execute(array(":existencia" => $idExistencia));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existencia) {
            throw new Exception("Existencia no encontrada para reserva");
        }
        if (floatval($existencia["cantidad_disponible"]) + 0.0001 < $cantidad) {
            throw new Exception("Existencia insuficiente al crear reserva");
        }
        $folioReserva = $this->generarFolioReservaPedidoPosReal($db);
        $stmt = $db->prepare("INSERT INTO erp_inventario_reservas
            (folio, origen_tipo, origen_id, origen_detalle_id, id_existencia_inventario, codigo_existencia,
             id_producto, id_sku_erp, id_almacen, ubicacion_id, lote, fecha_caducidad,
             cantidad_reservada, estatus, fecha_vencimiento, creado_por, observaciones)
            VALUES (:folio, 'pedido_pos', :venta, :detalle, :existencia, :codigo,
             :producto, :sku, :almacen, :ubicacion, :lote, :caducidad,
             :cantidad, 'activa', :vencimiento, :usuario, :observaciones)");
        $stmt->execute(array(
            ":folio" => $folioReserva,
            ":venta" => intval($idVenta),
            ":detalle" => intval($idDetalle),
            ":existencia" => $idExistencia,
            ":codigo" => $existencia["codigo_existencia"],
            ":producto" => intval($existencia["id_producto"]),
            ":sku" => intval($existencia["id_sku_erp"]),
            ":almacen" => intval($existencia["id_almacen_clave"]),
            ":ubicacion" => intval($existencia["ubicacion_id"]) ?: null,
            ":lote" => $existencia["lote"],
            ":caducidad" => $existencia["fecha_caducidad"],
            ":cantidad" => $cantidad,
            ":vencimiento" => $fechaVencimiento,
            ":usuario" => intval($idUsuario) ?: null,
            ":observaciones" => "reserva pedido/apartado " . $folioVenta
        ));
        $idReserva = intval($db->lastInsertId());
        $db->prepare("UPDATE erp_inventario_existencias
            SET cantidad_apartada=ROUND(cantidad_apartada+:cantidad,6),
                cantidad_disponible=ROUND(cantidad_disponible-:cantidad,6),
                fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:existencia")
            ->execute(array(":cantidad" => $cantidad, ":existencia" => $idExistencia));
        return array(
            "id_reserva_inventario" => $idReserva,
            "folio" => $folioReserva,
            "id_existencia_inventario" => $idExistencia,
            "id_venta_detalle" => intval($idDetalle),
            "cantidad_reservada" => $cantidad
        );
    }

    private function registrarPagoPedidoPosReal($db, $idVenta, $folio, $datosVenta, $pagos, $montoObjetivo, $tipoPago, $idUsuario) {
        $pendiente = $this->redondearPosReal($montoObjetivo);
        $evidencia = array();
        if ($pendiente <= 0.0001) {
            return $evidencia;
        }
        foreach ($pagos as $pago) {
            if ($pendiente <= 0.0001) {
                break;
            }
            $monto = $this->redondearPosReal(min($pendiente, $this->valor($pago, "monto", 0)));
            if ($monto <= 0) {
                continue;
            }
            $metodoPago = trim((string) $this->valor($pago, "metodo_pago", ""));
            if ($metodoPago === "") {
                foreach ($this->listarMetodosPago($db) as $metodo) {
                    if (intval($metodo["id_metodo_pago"]) === intval($this->valor($pago, "id_metodo_pago", 0))) {
                        $metodoPago = $metodo["metodo_pago"];
                        break;
                    }
                }
            }
            $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
                (id_turno_caja, id_caja, id_almacen, tipo, categoria, motivo, monto, estatus,
                 referencia, requiere_autorizacion, requiere_evidencia, observaciones, creado_por, fecha_registro, fecha_actualizacion)
                VALUES (:turno, :caja, :almacen, 'ingreso', 'apartado_pos', :motivo, :monto, 'registrado',
                 :referencia, 0, 0, :observaciones, :usuario, NOW(), NOW())");
            $stmt->execute(array(
                ":turno" => intval($this->valor($datosVenta, "id_turno_caja", 0)),
                ":caja" => intval($this->valor($datosVenta, "id_caja", 0)),
                ":almacen" => intval($this->valor($datosVenta, "id_almacen", 0)),
                ":motivo" => $tipoPago,
                ":monto" => $monto,
                ":referencia" => $folio,
                ":observaciones" => $tipoPago . " pedido/apartado " . $folio,
                ":usuario" => intval($idUsuario)
            ));
            $idMovimientoCaja = intval($db->lastInsertId());
            $stmt = $db->prepare("INSERT INTO erp_ventas_pagos
                (id_venta, id_caja, id_turno_caja, id_movimiento_caja, id_metodo_pago,
                 metodo_pago, tipo_pago, monto, moneda, referencia, estatus, creado_por)
                VALUES (:venta, :caja, :turno, :movimiento, :metodo_id,
                 :metodo, :tipo_pago, :monto, 'MXN', :referencia, 'registrado', :usuario)");
            $stmt->execute(array(
                ":venta" => intval($idVenta),
                ":caja" => intval($this->valor($datosVenta, "id_caja", 0)),
                ":turno" => intval($this->valor($datosVenta, "id_turno_caja", 0)),
                ":movimiento" => $idMovimientoCaja,
                ":metodo_id" => intval($this->valor($pago, "id_metodo_pago", 0)) ?: null,
                ":metodo" => $metodoPago !== "" ? $metodoPago : "Metodo no especificado",
                ":tipo_pago" => $tipoPago,
                ":monto" => $monto,
                ":referencia" => $this->valor($pago, "referencia", null),
                ":usuario" => intval($idUsuario)
            ));
            $evidencia[] = array(
                "id_venta_pago" => intval($db->lastInsertId()),
                "id_movimiento_caja" => $idMovimientoCaja,
                "tipo_pago" => $tipoPago,
                "metodo_pago" => $metodoPago,
                "monto_aplicado" => $monto
            );
            $pendiente = $this->redondearPosReal($pendiente - $monto);
        }
        if ($pendiente > 0.0001) {
            throw new Exception("Pagos insuficientes durante registro de " . $tipoPago);
        }
        return $evidencia;
    }

    private function registrarEventoVentaPosReal($db, $idVenta, $folio, $tipoEvento, $estatusAnterior, $estatusNuevo, $monto, $referencia, $snapshot, $idUsuario) {
        if (!$this->tablaExiste($db, "erp_ventas_eventos")) {
            return 0;
        }
        $stmt = $db->prepare("INSERT INTO erp_ventas_eventos
            (id_venta, folio, tipo_evento, estatus_anterior, estatus_nuevo, monto,
             referencia, datos_snapshot, observaciones, creado_por)
            VALUES (:venta, :folio, :tipo, :anterior, :nuevo, :monto,
             :referencia, :snapshot, :observaciones, :usuario)");
        $stmt->execute(array(
            ":venta" => intval($idVenta),
            ":folio" => $folio,
            ":tipo" => $tipoEvento,
            ":anterior" => $estatusAnterior,
            ":nuevo" => $estatusNuevo,
            ":monto" => $this->redondearPosReal($monto),
            ":referencia" => $referencia !== "" ? $referencia : null,
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ":observaciones" => $tipoEvento . " POS",
            ":usuario" => intval($idUsuario) ?: null
        ));
        return intval($db->lastInsertId());
    }

    private function reservasActivasPedidoPosReal($db, $idVenta) {
        $stmt = $db->prepare("SELECT r.*, d.id_venta_detalle, d.sku, d.descripcion
            FROM erp_inventario_reservas r
            LEFT JOIN erp_ventas_detalle d ON d.id_venta_detalle=r.origen_detalle_id
            WHERE r.origen_tipo='pedido_pos'
              AND r.origen_id=:venta
              AND r.estatus='activa'
            ORDER BY r.id_reserva_inventario ASC");
        $stmt->execute(array(":venta" => intval($idVenta)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function consumirReservaPedidoPosReal($db, $venta, $reserva, $idUsuario) {
        $pendiente = $this->redondearPosReal(floatval($reserva["cantidad_reservada"]) - floatval($reserva["cantidad_consumida"]) - floatval($reserva["cantidad_liberada"]));
        if ($pendiente <= 0) {
            throw new Exception("Reserva sin cantidad pendiente");
        }
        $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:existencia FOR UPDATE");
        $stmt->execute(array(":existencia" => intval($reserva["id_existencia_inventario"])));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existencia) {
            throw new Exception("Existencia no encontrada al consumir reserva");
        }
        if ($pendiente > floatval($existencia["cantidad_apartada"]) + 0.0001 || $pendiente > floatval($existencia["cantidad"]) + 0.0001) {
            throw new Exception("Reserva inconsistente contra existencia apartada");
        }
        $cantidadAnterior = $this->redondearPosReal($existencia["cantidad"]);
        $cantidadNueva = $this->redondearPosReal($cantidadAnterior - $pendiente);
        $apartadaNueva = $this->redondearPosReal(floatval($existencia["cantidad_apartada"]) - $pendiente);
        $db->prepare("UPDATE erp_inventario_existencias
            SET cantidad=:cantidad, cantidad_apartada=:apartada,
                estatus_existencia=:estatus, fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:existencia")
            ->execute(array(
                ":cantidad" => $cantidadNueva,
                ":apartada" => $apartadaNueva,
                ":estatus" => $cantidadNueva > 0.0001 ? "disponible" : "agotada",
                ":existencia" => intval($existencia["id_existencia_inventario"])
            ));
        $costo = $this->redondearPosReal($this->valor($existencia, "costo_promedio", 0));
        $stmt = $db->prepare("INSERT INTO erp_inventario_movimientos
            (id_producto, id_sku_erp, id_almacen, tipo_movimiento, origen_tipo, origen_id,
             id_existencia_inventario, codigo_existencia, lote, fecha_caducidad, ubicacion_id,
             ubicacion, cantidad, costo_unitario, costo_total, existencia_anterior,
             existencia_nueva, referencia, observaciones)
            VALUES (:producto, :sku, :almacen, 'salida', 'pedido_pos_entrega', :venta,
             :existencia, :codigo, :lote, :caducidad, :ubicacion_id, :ubicacion,
             :cantidad, :costo, :costo_total, :anterior, :nueva, :referencia, :observaciones)");
        $stmt->execute(array(
            ":producto" => intval($existencia["id_producto"]),
            ":sku" => intval($existencia["id_sku_erp"]),
            ":almacen" => intval($existencia["id_almacen_clave"]),
            ":venta" => intval($venta["id_venta"]),
            ":existencia" => intval($existencia["id_existencia_inventario"]),
            ":codigo" => $existencia["codigo_existencia"],
            ":lote" => $existencia["lote"],
            ":caducidad" => $existencia["fecha_caducidad"],
            ":ubicacion_id" => $existencia["ubicacion_id"],
            ":ubicacion" => $existencia["ubicacion"],
            ":cantidad" => $pendiente,
            ":costo" => $costo,
            ":costo_total" => $this->redondearPosReal($pendiente * $costo),
            ":anterior" => $cantidadAnterior,
            ":nueva" => $cantidadNueva,
            ":referencia" => $venta["folio"],
            ":observaciones" => "entrega pedido/apartado " . $venta["folio"] . " | reserva:" . $reserva["folio"] . " | usuario:" . intval($idUsuario)
        ));
        $idMovimiento = intval($db->lastInsertId());
        $db->prepare("UPDATE erp_inventario_existencias SET ultimo_movimiento_id=:movimiento WHERE id_existencia_inventario=:existencia")
            ->execute(array(":movimiento" => $idMovimiento, ":existencia" => intval($existencia["id_existencia_inventario"])));
        $db->prepare("UPDATE erp_inventario_reservas
            SET cantidad_consumida=ROUND(cantidad_consumida+:cantidad,6),
                estatus='consumida', fecha_cierre=NOW(), cerrado_por=:usuario,
                fecha_actualizacion=NOW()
            WHERE id_reserva_inventario=:reserva")
            ->execute(array(":cantidad" => $pendiente, ":usuario" => intval($idUsuario) ?: null, ":reserva" => intval($reserva["id_reserva_inventario"])));
        $db->prepare("INSERT INTO erp_ventas_detalle_inventario
            (id_venta, id_venta_detalle, id_existencia_inventario, id_reserva_inventario,
             id_movimiento_inventario, id_almacen, lote, fecha_caducidad, ubicacion_id,
             cantidad_base, estatus)
            VALUES (:venta, :detalle, :existencia, :reserva, :movimiento, :almacen,
             :lote, :caducidad, :ubicacion_id, :cantidad, 'confirmada')")
            ->execute(array(
                ":venta" => intval($venta["id_venta"]),
                ":detalle" => intval($reserva["origen_detalle_id"]),
                ":existencia" => intval($existencia["id_existencia_inventario"]),
                ":reserva" => intval($reserva["id_reserva_inventario"]),
                ":movimiento" => $idMovimiento,
                ":almacen" => intval($existencia["id_almacen_clave"]),
                ":lote" => $existencia["lote"],
                ":caducidad" => $existencia["fecha_caducidad"],
                ":ubicacion_id" => $existencia["ubicacion_id"],
                ":cantidad" => $pendiente
            ));
        return array(
            "id_reserva_inventario" => intval($reserva["id_reserva_inventario"]),
            "folio_reserva" => $reserva["folio"],
            "id_movimiento_inventario" => $idMovimiento,
            "cantidad_consumida" => $pendiente
        );
    }

    private function liberarReservaPedidoPosReal($db, $reserva, $motivo, $idUsuario) {
        $pendiente = $this->redondearPosReal(floatval($reserva["cantidad_reservada"]) - floatval($reserva["cantidad_consumida"]) - floatval($reserva["cantidad_liberada"]));
        if ($pendiente <= 0) {
            return array("id_reserva_inventario" => intval($reserva["id_reserva_inventario"]), "cantidad_liberada" => 0);
        }
        $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:existencia FOR UPDATE");
        $stmt->execute(array(":existencia" => intval($reserva["id_existencia_inventario"])));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existencia) {
            throw new Exception("Existencia no encontrada al liberar reserva");
        }
        if ($pendiente > floatval($existencia["cantidad_apartada"]) + 0.0001) {
            throw new Exception("Reserva inconsistente contra apartado actual");
        }
        $db->prepare("UPDATE erp_inventario_existencias
            SET cantidad_apartada=ROUND(cantidad_apartada-:cantidad,6),
                cantidad_disponible=ROUND(cantidad_disponible+:cantidad,6),
                fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:existencia")
            ->execute(array(":cantidad" => $pendiente, ":existencia" => intval($existencia["id_existencia_inventario"])));
        $db->prepare("UPDATE erp_inventario_reservas
            SET cantidad_liberada=ROUND(cantidad_liberada+:cantidad,6),
                estatus='liberada', fecha_cierre=NOW(), cerrado_por=:usuario,
                observaciones=TRIM(CONCAT(COALESCE(observaciones,''), CASE WHEN COALESCE(observaciones,'')<>'' THEN ' | ' ELSE '' END, :observaciones)),
                fecha_actualizacion=NOW()
            WHERE id_reserva_inventario=:reserva")
            ->execute(array(
                ":cantidad" => $pendiente,
                ":usuario" => intval($idUsuario) ?: null,
                ":observaciones" => "cancelacion pedido/apartado: " . $motivo,
                ":reserva" => intval($reserva["id_reserva_inventario"])
            ));
        return array("id_reserva_inventario" => intval($reserva["id_reserva_inventario"]), "folio" => $reserva["folio"], "cantidad_liberada" => $pendiente);
    }

    private function generarFolioReservaPedidoPosReal($db) {
        $prefijo = "RES-PED-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_reservas WHERE folio LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function valorRutaPosReal($datos, $ruta, $default = null) {
        $actual = $datos;
        foreach ($ruta as $segmento) {
            if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
                return $default;
            }
            $actual = $actual[$segmento];
        }
        return $actual;
    }

    private function redondearPosReal($valor) {
        return round(floatval($valor), 6);
    }

    private function decodificarItems($items) {
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        return is_array($items) ? $items : array();
    }

    private function decimalesNumero($numero) {
        $texto = rtrim(rtrim(sprintf("%.10F", floatval($numero)), "0"), ".");
        $partes = explode(".", $texto);
        return count($partes) === 2 ? strlen($partes[1]) : 0;
    }

    private function tablaExiste($db, $tabla) {
        if (!$db) {
            return false;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
            return false;
        }
        $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function columnaExiste($db, $tabla, $columna) {
        if (!$db) {
            return false;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
            return false;
        }
        $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function generarFolioAtencion($db) {
        $prefijo = "ATN-" . date("Ymd") . "-";
        for ($intento = 0; $intento < 5; $intento++) {
            $folio = $prefijo . date("His") . "-" . str_pad((string) mt_rand(1, 999), 3, "0", STR_PAD_LEFT);
            $stmt = $db->prepare("SELECT COUNT(*) FROM erp_pos_atenciones WHERE folio_temporal=:folio");
            $stmt->execute(array(":folio" => $folio));
            if (intval($stmt->fetchColumn()) === 0) {
                return $folio;
            }
            usleep(100000);
        }
        return $prefijo . str_replace(".", "", sprintf("%.4f", microtime(true)));
    }

    private function generarFolioExcepcionComercial($db) {
        $prefijo = "EXC-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ventas_excepciones_comerciales WHERE folio LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function generarFolioCorreccionEvidenciaCaja($db) {
        $prefijo = "COR-EVC-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*)
            FROM erp_pos_movimientos_caja_evidencias_correcciones
            WHERE folio LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function generarFolioInspeccionFisicaDevolucion($db) {
        $prefijo = "IFD-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*)
            FROM erp_ventas_devoluciones_inspecciones
            WHERE folio LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function generarFolioRevisionDiferenciaCaja($db) {
        $prefijo = "DIF-CAJ-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT COUNT(*)
            FROM erp_pos_turnos_diferencias_revision
            WHERE folio LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "%"));
        return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
    }

    private function valor($datos, $campo, $default = null) {
        return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}


