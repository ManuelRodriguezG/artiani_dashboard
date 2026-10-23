<?php

class ProduccionPecerasErp extends CRUD {

    private $tablaPeceras = "erp_produccion_peceras";
    private $tablaPiezas = "erp_produccion_peceras_piezas";
    private $tablaPedidos = "erp_produccion_pedidos_vidrio";
    private $tablaPedidoItems = "erp_produccion_pedidos_vidrio_items";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-22
     * Proposito: consultar perfiles persistentes de peceras con sus piezas editadas.
     * Impacto: Produccion/Peceras; reemplaza el almacenamiento local del navegador.
     * Contrato: read-only; devuelve perfiles en el formato que consume la UI.
     */
    public function listarPeceras($filtros = array()) {
        try {
            $db = $this->getConexion();
            if (!$this->esquemaPerfilesCompleto($db)) {
                return $this->respuesta(false, "info", "Esquema de perfiles pendiente", array("schema_pendiente" => true, "perfiles" => array()));
            }
            $buscar = isset($filtros["buscar"]) ? trim((string) $filtros["buscar"]) : "";
            $where = "estatus <> 'cancelada'";
            $params = array();
            if ($buscar !== "") {
                $where .= " AND (codigo LIKE :buscar OR nombre LIKE :buscar OR proveedor_snapshot LIKE :buscar)";
                $params[":buscar"] = "%" . $buscar . "%";
            }
            $stmt = $db->prepare("SELECT * FROM {$this->tablaPeceras} WHERE {$where} ORDER BY id_pecera DESC LIMIT 300");
            $stmt->execute($params);
            $perfiles = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $perfil) {
                $perfiles[] = $this->formatearPerfil($db, $perfil);
            }
            return $this->respuesta(false, "success", "Perfiles consultados", array("schema_pendiente" => false, "perfiles" => $perfiles));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-22
     * Proposito: crear o actualizar un perfil de pecera y sus piezas.
     * Impacto: Produccion/Peceras; conserva las medidas tecnicas fuera del navegador.
     * Contrato: recibe `datos` y `piezas` como JSON o arreglos; usa transaccion propia.
     */
    public function guardarPecera($datos, $piezas, $idUsuario = 0) {
        try {
            $db = $this->getConexion();
            if (!$this->esquemaPerfilesCompleto($db)) {
                return $this->respuesta(true, "warning", "Esquema de perfiles pendiente", array("schema_pendiente" => true));
            }
            $datos = $this->jsonOArreglo($datos);
            $piezas = $this->jsonOArreglo($piezas);
            $nombre = trim((string) ($datos["nombre"] ?? ""));
            if ($nombre === "") {
                $nombre = "Pecera " . ($datos["largo"] ?? "0") . "x" . ($datos["fondo"] ?? "0") . "x" . ($datos["alto"] ?? "0");
            }
            if ((float) ($datos["largo"] ?? 0) <= 0 || (float) ($datos["fondo"] ?? 0) <= 0 || (float) ($datos["alto"] ?? 0) <= 0) {
                return $this->respuesta(true, "warning", "Captura medidas validas para la pecera");
            }
            $idPecera = intval($datos["id"] ?? $datos["id_pecera"] ?? 0);
            $db->beginTransaction();
            $params = array(
                ":nombre" => $nombre,
                ":codigo" => trim((string) ($datos["codigo"] ?? "")) ?: null,
                ":proveedor" => trim((string) ($datos["proveedor"] ?? "")) ?: null,
                ":largo" => (float) ($datos["largo"] ?? 0),
                ":fondo" => (float) ($datos["fondo"] ?? 0),
                ":alto" => (float) ($datos["alto"] ?? 0),
                ":espesor" => (float) ($datos["espesor"] ?? 0),
                ":cantidad" => max(1, intval($datos["cantidad"] ?? 1)),
                ":base" => (string) ($datos["base"] ?? "interior"),
                ":holgura" => (float) ($datos["holgura"] ?? 0),
                ":m2" => (float) ($datos["costo_m2"] ?? 0),
                ":corte" => (float) ($datos["costo_corte"] ?? 0),
                ":area" => $this->areaPiezas($piezas),
                ":total" => $this->totalPiezas($piezas, max(1, intval($datos["cantidad"] ?? 1))),
                ":volumen" => ((float) ($datos["largo"] ?? 0) * (float) ($datos["fondo"] ?? 0) * (float) ($datos["alto"] ?? 0)) / 1000,
                ":observaciones" => trim((string) ($datos["observaciones"] ?? "")) ?: null,
                ":payload" => json_encode(array("datos" => $datos, "piezas" => $piezas), JSON_UNESCAPED_UNICODE),
                ":usuario" => intval($idUsuario) > 0 ? intval($idUsuario) : null
            );
            if ($idPecera > 0) {
                $params[":id"] = $idPecera;
                $stmt = $db->prepare("UPDATE {$this->tablaPeceras} SET codigo=:codigo, nombre=:nombre, proveedor_snapshot=:proveedor, largo_cm=:largo, fondo_cm=:fondo, alto_cm=:alto, espesor_mm=:espesor, cantidad_peceras=:cantidad, tipo_base=:base, holgura_mm=:holgura, costo_m2_estimado=:m2, costo_corte_estimado=:corte, area_total_m2=:area, piezas_total=:total, volumen_litros=:volumen, observaciones=:observaciones, payload_json=:payload, actualizado_por=:usuario, fecha_actualizacion=NOW() WHERE id_pecera=:id");
                $stmt->execute($params);
                $db->prepare("DELETE FROM {$this->tablaPiezas} WHERE id_pecera=:id")->execute(array(":id" => $idPecera));
            } else {
                $folio = "PEC-" . date("YmdHis") . "-" . mt_rand(100, 999);
                $params[":folio"] = $folio;
                $stmt = $db->prepare("INSERT INTO {$this->tablaPeceras} (folio, codigo, nombre, proveedor_snapshot, largo_cm, fondo_cm, alto_cm, espesor_mm, cantidad_peceras, tipo_base, holgura_mm, costo_m2_estimado, costo_corte_estimado, area_total_m2, piezas_total, volumen_litros, estatus, origen, observaciones, payload_json, creado_por) VALUES (:folio, :codigo, :nombre, :proveedor, :largo, :fondo, :alto, :espesor, :cantidad, :base, :holgura, :m2, :corte, :area, :total, :volumen, 'borrador', 'calculadora', :observaciones, :payload, :usuario)");
                $stmt->execute($params);
                $idPecera = intval($db->lastInsertId());
            }
            $this->insertarPiezas($db, $idPecera, $piezas);
            $db->commit();
            return $this->respuesta(false, "success", "Perfil guardado correctamente", array("id_pecera" => $idPecera, "perfil" => $this->consultarPecera($idPecera)));
        } catch (Exception $e) {
            if (isset($db) && $db && $db->inTransaction()) { $db->rollBack(); }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-22
     * Proposito: guardar pedidos multiples y sus metricas comparables.
     * Impacto: Produccion/Pedido de vidrio; elimina el historial aislado por navegador.
     * Contrato: recibe un snapshot JSON del pedido, conserva configuracion y partidas.
     */
    public function guardarPedidoVidrio($pedido, $idUsuario = 0) {
        try {
            $db = $this->getConexion();
            if (!$this->esquemaPedidosCompleto($db)) {
                return $this->respuesta(true, "warning", "Esquema de pedidos de vidrio pendiente", array("schema_pendiente" => true));
            }
            $pedido = $this->jsonOArreglo($pedido);
            $nombre = trim((string) ($pedido["nombre"] ?? "")) ?: "Pedido vidrio " . date("Y-m-d H:i");
            $idPedido = intval($pedido["id_pedido_vidrio"] ?? 0);
            $db->beginTransaction();
            $params = array(
                ":nombre" => $nombre,
                ":acomodo" => (string) ($pedido["tipo_acomodo"] ?? "columnas_ancho"),
                ":desperdicio" => (float) ($pedido["desperdicio_promedio"] ?? 0),
                ":hojas" => max(0, intval($pedido["hojas_total"] ?? 0)),
                ":piezas" => max(0, intval($pedido["piezas_total"] ?? 0)),
                ":area" => (float) ($pedido["area_total_m2"] ?? 0),
                ":payload" => json_encode($pedido, JSON_UNESCAPED_UNICODE),
                ":usuario" => intval($idUsuario) > 0 ? intval($idUsuario) : null
            );
            if ($idPedido > 0) {
                $params[":id"] = $idPedido;
                $stmt = $db->prepare("UPDATE {$this->tablaPedidos} SET nombre=:nombre, tipo_acomodo=:acomodo, desperdicio_promedio=:desperdicio, hojas_total=:hojas, piezas_total=:piezas, area_total_m2=:area, payload_json=:payload, actualizado_por=:usuario, fecha_actualizacion=NOW() WHERE id_pedido_vidrio=:id");
                $stmt->execute($params);
                $db->prepare("DELETE FROM {$this->tablaPedidoItems} WHERE id_pedido_vidrio=:id")->execute(array(":id" => $idPedido));
            } else {
                $folio = "VID-" . date("YmdHis") . "-" . mt_rand(100, 999);
                $params[":folio"] = $folio;
                $stmt = $db->prepare("INSERT INTO {$this->tablaPedidos} (folio, nombre, tipo_acomodo, desperdicio_promedio, hojas_total, piezas_total, area_total_m2, estatus, payload_json, creado_por) VALUES (:folio, :nombre, :acomodo, :desperdicio, :hojas, :piezas, :area, 'borrador', :payload, :usuario)");
                $stmt->execute($params);
                $idPedido = intval($db->lastInsertId());
            }
            foreach ((array) ($pedido["items"] ?? array()) as $item) {
                $stmt = $db->prepare("INSERT INTO {$this->tablaPedidoItems} (id_pedido_vidrio, id_pecera, cantidad, perfil_snapshot) VALUES (:pedido, :pecera, :cantidad, :perfil)");
                $stmt->execute(array(
                    ":pedido" => $idPedido,
                    ":pecera" => intval($item["perfil"]["id_pecera"] ?? $item["perfil"]["id"] ?? 0) ?: null,
                    ":cantidad" => max(1, intval($item["cantidad"] ?? 1)),
                    ":perfil" => json_encode($item["perfil"] ?? array(), JSON_UNESCAPED_UNICODE)
                ));
            }
            $db->commit();
            return $this->respuesta(false, "success", "Pedido de vidrio guardado correctamente", array("id_pedido_vidrio" => $idPedido));
        } catch (Exception $e) {
            if (isset($db) && $db && $db->inTransaction()) { $db->rollBack(); }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-22
     * Proposito: listar pedidos historicos ordenados por menor desperdicio.
     * Impacto: Produccion/Pedido de vidrio; permite elegir antecedentes eficientes.
     * Contrato: read-only; devuelve snapshot completo para cargar o analizar.
     */
    public function listarPedidosVidrio() {
        try {
            $db = $this->getConexion();
            if (!$this->esquemaPedidosCompleto($db)) {
                return $this->respuesta(false, "info", "Esquema de pedidos de vidrio pendiente", array("schema_pendiente" => true, "pedidos" => array()));
            }
            $stmt = $db->query("SELECT id_pedido_vidrio, folio, nombre, tipo_acomodo, desperdicio_promedio, hojas_total, piezas_total, area_total_m2, estatus, payload_json, fecha_registro FROM {$this->tablaPedidos} WHERE estatus <> 'cancelada' ORDER BY desperdicio_promedio ASC, id_pedido_vidrio DESC LIMIT 300");
            $pedidos = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $snapshot = $this->jsonOArreglo($row["payload_json"]);
                $snapshot["id_pedido_vidrio"] = intval($row["id_pedido_vidrio"]);
                $snapshot["folio"] = $row["folio"];
                $snapshot["nombre"] = $row["nombre"];
                $snapshot["tipo_acomodo"] = $row["tipo_acomodo"];
                $snapshot["desperdicio_promedio"] = (float) $row["desperdicio_promedio"];
                $snapshot["hojas_total"] = intval($row["hojas_total"]);
                $snapshot["piezas_total"] = intval($row["piezas_total"]);
                $snapshot["fecha"] = $row["fecha_registro"];
                $pedidos[] = $snapshot;
            }
            return $this->respuesta(false, "success", "Pedidos historicos consultados", array("schema_pendiente" => false, "pedidos" => $pedidos));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-22
     * Proposito: archivar logicamente un perfil sin borrar su historial tecnico.
     * Impacto: Produccion/Peceras; conserva trazabilidad para pedidos anteriores.
     * Contrato: modifica solo el estatus del perfil indicado.
     */
    public function cancelarPecera($idPecera) {
        try {
            $db = $this->getConexion();
            if (!$this->esquemaPerfilesCompleto($db) || intval($idPecera) <= 0) {
                return $this->respuesta(true, "warning", "Perfil no valido o esquema pendiente");
            }
            $stmt = $db->prepare("UPDATE {$this->tablaPeceras} SET estatus='cancelada', fecha_actualizacion=NOW() WHERE id_pecera=:id");
            $stmt->execute(array(":id" => intval($idPecera)));
            return $this->respuesta(false, "success", "Perfil archivado");
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function consultarPecera($idPecera) {
        $db = $this->getConexion();
        $stmt = $db->prepare("SELECT * FROM {$this->tablaPeceras} WHERE id_pecera=:id LIMIT 1");
        $stmt->execute(array(":id" => intval($idPecera)));
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        return $perfil ? $this->formatearPerfil($db, $perfil) : array();
    }

    private function formatearPerfil($db, $perfil) {
        $stmt = $db->prepare("SELECT id_pecera_pieza id, nombre, largo_cm largo, ancho_cm ancho, espesor_mm espesor, cantidad_por_pecera cantidad, acabado, observaciones, estatus FROM {$this->tablaPiezas} WHERE id_pecera=:id AND estatus <> 'cancelada' ORDER BY orden, id_pecera_pieza");
        $stmt->execute(array(":id" => intval($perfil["id_pecera"])));
        $datos = $this->jsonOArreglo($perfil["payload_json"]);
        $datos = is_array($datos["datos"] ?? null) ? $datos["datos"] : array();
        $datos["id_pecera"] = intval($perfil["id_pecera"]);
        $datos["codigo"] = $perfil["codigo"];
        $datos["nombre"] = $perfil["nombre"];
        $datos["proveedor"] = $perfil["proveedor_snapshot"];
        return array("id" => intval($perfil["id_pecera"]), "id_pecera" => intval($perfil["id_pecera"]), "codigo" => $perfil["codigo"], "nombre" => $perfil["nombre"], "fecha" => $perfil["fecha_registro"], "datos" => $datos, "piezas" => $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function insertarPiezas($db, $idPecera, $piezas) {
        $stmt = $db->prepare("INSERT INTO {$this->tablaPiezas} (id_pecera, orden, nombre, largo_cm, ancho_cm, espesor_mm, cantidad_por_pecera, cantidad_total, area_m2, acabado, observaciones, estatus) VALUES (:pecera, :orden, :nombre, :largo, :ancho, :espesor, :cantidad, :total, :area, :acabado, :observaciones, 'activa')");
        foreach (array_values($piezas) as $orden => $pieza) {
            $largo = (float) ($pieza["largo"] ?? $pieza["largo_cm"] ?? 0);
            $ancho = (float) ($pieza["ancho"] ?? $pieza["ancho_cm"] ?? 0);
            $cantidad = max(1, intval($pieza["cantidad"] ?? $pieza["cantidad_por_pecera"] ?? 1));
            $stmt->execute(array(":pecera" => $idPecera, ":orden" => $orden + 1, ":nombre" => (string) ($pieza["nombre"] ?? "Pieza"), ":largo" => $largo, ":ancho" => $ancho, ":espesor" => (float) ($pieza["espesor"] ?? $pieza["espesor_mm"] ?? 0), ":cantidad" => $cantidad, ":total" => $cantidad, ":area" => ($largo * $ancho * $cantidad) / 10000, ":acabado" => (string) ($pieza["acabado"] ?? "filo_muerto"), ":observaciones" => (string) ($pieza["observaciones"] ?? "")));
        }
    }

    private function esquemaPerfilesCompleto($db) { return $this->tablasCompletas($db, array($this->tablaPeceras, $this->tablaPiezas)); }
    private function esquemaPedidosCompleto($db) { return $this->tablasCompletas($db, array($this->tablaPeceras, $this->tablaPiezas, $this->tablaPedidos, $this->tablaPedidoItems)); }
    private function tablasCompletas($db, $tablas) {
        if (!$db) { return false; }
        foreach ($tablas as $tabla) {
            $stmt = $db->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
            $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
            if (!$stmt->fetchColumn()) { return false; }
        }
        return true;
    }
    private function jsonOArreglo($valor) {
        if (is_array($valor)) { return $valor; }
        $decodificado = json_decode((string) $valor, true);
        return is_array($decodificado) ? $decodificado : array();
    }
    private function areaPiezas($piezas) { $area = 0; foreach ((array) $piezas as $pieza) { $area += ((float) ($pieza["largo"] ?? $pieza["largo_cm"] ?? 0) * (float) ($pieza["ancho"] ?? $pieza["ancho_cm"] ?? 0) * max(1, intval($pieza["cantidad"] ?? $pieza["cantidad_por_pecera"] ?? 1))) / 10000; } return $area; }
    private function totalPiezas($piezas, $cantidad) { $total = 0; foreach ((array) $piezas as $pieza) { $total += max(1, intval($pieza["cantidad"] ?? $pieza["cantidad_por_pecera"] ?? 1)) * $cantidad; } return $total; }
    private function respuesta($error, $tipo, $mensaje, $depurar = array()) { return array("error" => (bool) $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar); }
}

