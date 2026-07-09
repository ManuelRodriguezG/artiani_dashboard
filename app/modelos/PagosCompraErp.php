<?php

class PagosCompraErp extends CRUD {

    public function consultar($idOrden) {
        try {
            $db = $this->getConexion();
            $idOrden = intval($idOrden);
            $resumen = $this->calcularResumen($db, $idOrden, false);

            $stmt = $db->prepare("SELECT id_pago_orden, metodo_pago, estado_pago, referencia,
                monto, fecha_pago, observaciones, creado_por, cancelado_por,
                fecha_cancelacion, fecha_registro
                FROM erp_compras_ordenes_pagos
                WHERE id_orden_compra=:id
                ORDER BY id_pago_orden DESC");
            $stmt->execute(array(":id" => $idOrden));
            $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT id_nota_credito_orden, referencia, monto, estatus,
                fecha_aplicacion, observaciones, creado_por, cancelado_por,
                fecha_cancelacion, fecha_registro
                FROM erp_compras_ordenes_notas_credito
                WHERE id_orden_compra=:id
                ORDER BY id_nota_credito_orden DESC");
            $stmt->execute(array(":id" => $idOrden));
            $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->respuesta(false, "success", "Movimientos financieros consultados", array(
                "id_orden_compra" => $idOrden,
                "resumen" => $resumen,
                "pagos" => $pagos,
                "notas_credito" => $notas
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function registrarPago($datos, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $idOrden = intval(isset($datos["id_orden_compra"]) ? $datos["id_orden_compra"] : 0);
            $metodo = isset($datos["metodo_pago"]) ? $datos["metodo_pago"] : "";
            $estado = isset($datos["estado_pago"]) ? $datos["estado_pago"] : "aplicado";
            $monto = round(floatval(isset($datos["monto"]) ? $datos["monto"] : 0), 2);
            $metodos = array("tarjeta_debito", "tarjeta_credito", "transferencia", "efectivo");
            $estados = array("pendiente", "aplicado", "conciliado");
            if (!in_array($metodo, $metodos, true)) {
                throw new Exception("Metodo de pago no valido");
            }
            if (!in_array($estado, $estados, true)) {
                throw new Exception("Estado de pago no valido");
            }
            if ($monto <= 0) {
                throw new Exception("El monto del pago debe ser mayor a cero");
            }
            $referencia = $this->texto($datos, "referencia", 150);
            if ($metodo !== "efectivo" && $referencia === "") {
                throw new Exception("La referencia es obligatoria para pagos con tarjeta o transferencia");
            }

            $resumen = $this->calcularResumen($db, $idOrden, true);
            if (in_array($estado, array("aplicado", "conciliado"), true) &&
                $monto > $resumen["saldo_pendiente"] + 0.009) {
                throw new Exception("El pago excede el saldo pendiente de la orden");
            }

            $fecha = $this->normalizarFecha(isset($datos["fecha_pago"]) ? $datos["fecha_pago"] : "");
            $stmt = $db->prepare("INSERT INTO erp_compras_ordenes_pagos
                (id_orden_compra, metodo_pago, estado_pago, referencia, monto,
                 fecha_pago, observaciones, creado_por)
                VALUES (:orden, :metodo, :estado, :referencia, :monto,
                        :fecha, :observaciones, :usuario)");
            $stmt->execute(array(
                ":orden" => $idOrden,
                ":metodo" => $metodo,
                ":estado" => $estado,
                ":referencia" => $referencia,
                ":monto" => $monto,
                ":fecha" => $fecha,
                ":observaciones" => $this->texto($datos, "observaciones"),
                ":usuario" => intval($idUsuario) ?: null
            ));
            $idPago = intval($db->lastInsertId());
            $resumen = $this->actualizarSaldo($db, $idOrden);
            $db->commit();
            return $this->respuesta(false, "success", "Pago registrado", array(
                "id_orden_compra" => $idOrden,
                "id_pago_orden" => $idPago,
                "resumen" => $resumen
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function cancelarPago($idOrden, $idPago, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->calcularResumen($db, intval($idOrden), true);
            $stmt = $db->prepare("UPDATE erp_compras_ordenes_pagos
                SET estado_pago='cancelado', cancelado_por=:usuario, fecha_cancelacion=NOW()
                WHERE id_pago_orden=:pago AND id_orden_compra=:orden
                AND estado_pago<>'cancelado'");
            $stmt->execute(array(
                ":usuario" => intval($idUsuario) ?: null,
                ":pago" => intval($idPago),
                ":orden" => intval($idOrden)
            ));
            if ($stmt->rowCount() !== 1) {
                throw new Exception("El pago no existe o ya fue cancelado");
            }
            $resumen = $this->actualizarSaldo($db, intval($idOrden));
            $db->commit();
            return $this->respuesta(false, "success", "Pago cancelado", array(
                "id_orden_compra" => intval($idOrden),
                "id_pago_orden" => intval($idPago),
                "resumen" => $resumen
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function registrarNotaCredito($datos, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $idOrden = intval(isset($datos["id_orden_compra"]) ? $datos["id_orden_compra"] : 0);
            $estatus = isset($datos["estatus"]) ? $datos["estatus"] : "aplicada";
            $monto = round(floatval(isset($datos["monto"]) ? $datos["monto"] : 0), 2);
            if (!in_array($estatus, array("pendiente", "aplicada"), true)) {
                throw new Exception("Estado de nota de credito no valido");
            }
            if ($monto <= 0) {
                throw new Exception("El monto de la nota debe ser mayor a cero");
            }
            $referencia = $this->texto($datos, "referencia", 150);
            if ($referencia === "") {
                throw new Exception("La referencia o folio de la nota de credito es obligatorio");
            }
            $resumen = $this->calcularResumen($db, $idOrden, true);
            if ($estatus === "aplicada" && $monto > $resumen["saldo_pendiente"] + 0.009) {
                throw new Exception("La nota de credito excede el saldo pendiente de la orden");
            }

            $fecha = $this->normalizarFecha(isset($datos["fecha_aplicacion"]) ? $datos["fecha_aplicacion"] : "");
            $stmt = $db->prepare("INSERT INTO erp_compras_ordenes_notas_credito
                (id_orden_compra, referencia, monto, estatus, fecha_aplicacion,
                 observaciones, creado_por)
                VALUES (:orden, :referencia, :monto, :estatus, :fecha,
                        :observaciones, :usuario)");
            $stmt->execute(array(
                ":orden" => $idOrden,
                ":referencia" => $referencia,
                ":monto" => $monto,
                ":estatus" => $estatus,
                ":fecha" => $fecha,
                ":observaciones" => $this->texto($datos, "observaciones"),
                ":usuario" => intval($idUsuario) ?: null
            ));
            $idNota = intval($db->lastInsertId());
            $resumen = $this->actualizarSaldo($db, $idOrden);
            $db->commit();
            return $this->respuesta(false, "success", "Nota de credito registrada", array(
                "id_orden_compra" => $idOrden,
                "id_nota_credito_orden" => $idNota,
                "resumen" => $resumen
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function cancelarNotaCredito($idOrden, $idNota, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->calcularResumen($db, intval($idOrden), true);
            $stmt = $db->prepare("UPDATE erp_compras_ordenes_notas_credito
                SET estatus='cancelada', cancelado_por=:usuario, fecha_cancelacion=NOW()
                WHERE id_nota_credito_orden=:nota AND id_orden_compra=:orden
                AND estatus<>'cancelada'");
            $stmt->execute(array(
                ":usuario" => intval($idUsuario) ?: null,
                ":nota" => intval($idNota),
                ":orden" => intval($idOrden)
            ));
            if ($stmt->rowCount() !== 1) {
                throw new Exception("La nota de credito no existe o ya fue cancelada");
            }
            $resumen = $this->actualizarSaldo($db, intval($idOrden));
            $db->commit();
            return $this->respuesta(false, "success", "Nota de credito cancelada", array(
                "id_orden_compra" => intval($idOrden),
                "id_nota_credito_orden" => intval($idNota),
                "resumen" => $resumen
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function recalcularSaldo($idOrden) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->calcularResumen($db, intval($idOrden), true);
            $resumen = $this->actualizarSaldo($db, intval($idOrden));
            $db->commit();
            return $this->respuesta(false, "success", "Saldo recalculado", array(
                "id_orden_compra" => intval($idOrden),
                "resumen" => $resumen
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function calcularResumen($db, $idOrden, $bloquear) {
        $sql = "SELECT total, estatus FROM erp_compras_ordenes
            WHERE id_orden_compra=:id" . ($bloquear ? " FOR UPDATE" : "");
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":id" => intval($idOrden)));
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orden) {
            throw new Exception("Orden de compra no encontrada");
        }
        if ($bloquear && $orden["estatus"] === "cancelada") {
            throw new Exception("La orden cancelada no admite movimientos financieros");
        }
        if ($bloquear && $orden["estatus"] === "borrador") {
            throw new Exception("Los movimientos financieros se registran cuando la orden ya fue enviada");
        }

        $stmt = $db->prepare("SELECT COALESCE(SUM(monto),0)
            FROM erp_compras_ordenes_pagos
            WHERE id_orden_compra=:id AND estado_pago IN ('aplicado','conciliado')");
        $stmt->execute(array(":id" => intval($idOrden)));
        $pagos = round(floatval($stmt->fetchColumn()), 2);

        $stmt = $db->prepare("SELECT COALESCE(SUM(monto),0)
            FROM erp_compras_ordenes_notas_credito
            WHERE id_orden_compra=:id AND estatus='aplicada'");
        $stmt->execute(array(":id" => intval($idOrden)));
        $notas = round(floatval($stmt->fetchColumn()), 2);
        $total = round(floatval($orden["total"]), 2);

        return array(
            "total_orden" => $total,
            "pagos_aplicados" => $pagos,
            "notas_aplicadas" => $notas,
            "total_aplicado" => round($pagos + $notas, 2),
            "saldo_pendiente" => max(0, round($total - $pagos - $notas, 2)),
            "pagada" => ($total > 0 && round($total - $pagos - $notas, 2) <= 0.009)
        );
    }

    private function actualizarSaldo($db, $idOrden) {
        $resumen = $this->calcularResumen($db, $idOrden, false);
        $stmt = $db->prepare("UPDATE erp_compras_ordenes
            SET saldo_pendiente=:saldo, fecha_actualizacion=NOW()
            WHERE id_orden_compra=:id");
        $stmt->execute(array(
            ":saldo" => $resumen["saldo_pendiente"],
            ":id" => intval($idOrden)
        ));
        return $resumen;
    }

    private function normalizarFecha($fecha) {
        $fecha = trim((string) $fecha);
        if ($fecha === "") {
            return date("Y-m-d H:i:s");
        }
        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            throw new Exception("Fecha no valida");
        }
        return date("Y-m-d H:i:s", $timestamp);
    }

    private function texto($datos, $campo, $limite = 0) {
        $valor = trim(isset($datos[$campo]) ? (string) $datos[$campo] : "");
        return $limite > 0 ? mb_substr($valor, 0, $limite) : $valor;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
