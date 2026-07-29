<?php

class ComprasDocumentosPlantillas extends CRUD {

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-28
     * Proposito: consultar y mantener plantillas imprimibles de Compras por audiencia.
     * Impacto: Solicitudes/Ordenes; controla que documentos para proveedor no expongan costos internos por defecto.
     * Contrato: crea solo configuracion base idempotente; no genera documentos ni modifica solicitudes/ordenes.
     */
    public function consultar($tipoDocumento = "") {
        try {
            $db = $this->getConexion();
            $this->asegurarPlantillasBase($db, null);

            $sql = "SELECT p.id_plantilla_documento, p.codigo, p.tipo_documento,
                    p.audiencia, p.nombre, p.descripcion, p.estatus, p.es_default,
                    c.id_plantilla_config, c.mostrar_logo, c.logo_ruta,
                    c.mostrar_costos, c.mostrar_impuestos, c.mostrar_totales,
                    c.mostrar_sku_erp, c.mostrar_sku_proveedor,
                    c.mostrar_nombre_erp, c.mostrar_nombre_proveedor,
                    c.mostrar_observaciones_internas,
                    c.mostrar_observaciones_publicas,
                    c.columnas_json, c.estilos_json, c.pie_pagina
                FROM erp_compras_documentos_plantillas p
                LEFT JOIN erp_compras_documentos_plantillas_config c
                    ON c.id_plantilla_documento=p.id_plantilla_documento";
            $params = array();
            $tipoDocumento = trim((string) $tipoDocumento);
            if ($tipoDocumento !== "") {
                $sql .= " WHERE p.tipo_documento=:tipo";
                $params[":tipo"] = $tipoDocumento;
            }
            $sql .= " ORDER BY p.tipo_documento ASC, p.audiencia ASC, p.codigo ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($plantillas as &$plantilla) {
                $plantilla["columnas"] = $this->jsonSeguro($plantilla["columnas_json"]);
                $plantilla["estilos"] = $this->jsonSeguro($plantilla["estilos_json"]);
            }

            return $this->respuesta(false, "success", "Plantillas consultadas", $plantillas);
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function guardarConfig($datos, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->asegurarPlantillasBase($db, $idUsuario);

            $codigo = $this->texto($datos, "codigo", 80);
            $idPlantilla = intval(isset($datos["id_plantilla_documento"]) ? $datos["id_plantilla_documento"] : 0);
            if ($codigo === "" && $idPlantilla <= 0) {
                throw new Exception("Selecciona una plantilla valida");
            }

            $where = $idPlantilla > 0 ? "id_plantilla_documento=:id" : "codigo=:codigo";
            $stmt = $db->prepare("SELECT id_plantilla_documento, audiencia, nombre, descripcion, estatus
                FROM erp_compras_documentos_plantillas WHERE {$where} LIMIT 1");
            $stmt->execute($idPlantilla > 0 ? array(":id" => $idPlantilla) : array(":codigo" => $codigo));
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plantilla) {
                throw new Exception("Plantilla no encontrada");
            }
            $idPlantilla = intval($plantilla["id_plantilla_documento"]);
            $nombre = $this->texto($datos, "nombre", 150);
            $descripcion = $this->texto($datos, "descripcion");
            $estatus = $this->texto($datos, "estatus", 30);

            $mostrarCostos = $this->boolInt($datos, "mostrar_costos");
            $mostrarImpuestos = $this->boolInt($datos, "mostrar_impuestos");
            $mostrarTotales = $this->boolInt($datos, "mostrar_totales");

            $stmt = $db->prepare("UPDATE erp_compras_documentos_plantillas
                SET nombre=:nombre, descripcion=:descripcion, estatus=:estatus,
                    actualizado_por=:usuario, fecha_actualizacion=NOW()
                WHERE id_plantilla_documento=:id");
            $stmt->execute(array(
                ":nombre" => $nombre !== "" ? $nombre : $plantilla["nombre"],
                ":descripcion" => $descripcion !== "" ? $descripcion : $plantilla["descripcion"],
                ":estatus" => $estatus !== "" ? $this->estatus($estatus) : $plantilla["estatus"],
                ":usuario" => intval($idUsuario) ?: null,
                ":id" => $idPlantilla
            ));

            $stmt = $db->prepare("UPDATE erp_compras_documentos_plantillas_config
                SET mostrar_logo=:mostrar_logo,
                    logo_ruta=:logo_ruta,
                    mostrar_costos=:mostrar_costos,
                    mostrar_impuestos=:mostrar_impuestos,
                    mostrar_totales=:mostrar_totales,
                    mostrar_sku_erp=:mostrar_sku_erp,
                    mostrar_sku_proveedor=:mostrar_sku_proveedor,
                    mostrar_nombre_erp=:mostrar_nombre_erp,
                    mostrar_nombre_proveedor=:mostrar_nombre_proveedor,
                    mostrar_observaciones_internas=:mostrar_observaciones_internas,
                    mostrar_observaciones_publicas=:mostrar_observaciones_publicas,
                    columnas_json=:columnas_json,
                    estilos_json=:estilos_json,
                    pie_pagina=:pie_pagina,
                    fecha_actualizacion=NOW()
                WHERE id_plantilla_documento=:id");
            $stmt->execute(array(
                ":mostrar_logo" => $this->boolInt($datos, "mostrar_logo", 1),
                ":logo_ruta" => $this->texto($datos, "logo_ruta", 255),
                ":mostrar_costos" => $mostrarCostos,
                ":mostrar_impuestos" => $mostrarImpuestos,
                ":mostrar_totales" => $mostrarTotales,
                ":mostrar_sku_erp" => $this->boolInt($datos, "mostrar_sku_erp"),
                ":mostrar_sku_proveedor" => $this->boolInt($datos, "mostrar_sku_proveedor", 1),
                ":mostrar_nombre_erp" => $this->boolInt($datos, "mostrar_nombre_erp"),
                ":mostrar_nombre_proveedor" => $this->boolInt($datos, "mostrar_nombre_proveedor", 1),
                ":mostrar_observaciones_internas" => $this->boolInt($datos, "mostrar_observaciones_internas"),
                ":mostrar_observaciones_publicas" => $this->boolInt($datos, "mostrar_observaciones_publicas", 1),
                ":columnas_json" => $this->jsonDesdeEntrada($datos, "columnas_json"),
                ":estilos_json" => $this->jsonDesdeEntrada($datos, "estilos_json"),
                ":pie_pagina" => $this->texto($datos, "pie_pagina"),
                ":id" => $idPlantilla
            ));

            $db->commit();
            return $this->respuesta(false, "success", "Plantilla actualizada", array(
                "id_plantilla_documento" => $idPlantilla
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function obtenerPorCodigo($codigo) {
        try {
            $db = $this->getConexion();
            $this->asegurarPlantillasBase($db, null);
            $codigo = trim((string) $codigo);
            if ($codigo === "") {
                throw new Exception("Codigo de plantilla no valido");
            }

            $stmt = $db->prepare("SELECT p.id_plantilla_documento, p.codigo,
                    p.tipo_documento, p.audiencia, p.nombre, p.descripcion,
                    p.estatus, p.es_default, c.id_plantilla_config,
                    c.mostrar_logo, c.logo_ruta, c.mostrar_costos,
                    c.mostrar_impuestos, c.mostrar_totales, c.mostrar_sku_erp,
                    c.mostrar_sku_proveedor, c.mostrar_nombre_erp,
                    c.mostrar_nombre_proveedor,
                    c.mostrar_observaciones_internas,
                    c.mostrar_observaciones_publicas,
                    c.columnas_json, c.estilos_json, c.pie_pagina
                FROM erp_compras_documentos_plantillas p
                LEFT JOIN erp_compras_documentos_plantillas_config c
                    ON c.id_plantilla_documento=p.id_plantilla_documento
                WHERE p.codigo=:codigo AND p.estatus='activa'
                LIMIT 1");
            $stmt->execute(array(":codigo" => $codigo));
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plantilla) {
                throw new Exception("Plantilla no encontrada");
            }
            $plantilla["columnas"] = $this->jsonSeguro($plantilla["columnas_json"]);
            $plantilla["estilos"] = $this->jsonSeguro($plantilla["estilos_json"]);
            return $this->respuesta(false, "success", "Plantilla consultada", $plantilla);
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function asegurarPlantillasBase($db, $idUsuario) {
        foreach ($this->plantillasBase() as $plantilla) {
            $stmt = $db->prepare("SELECT id_plantilla_documento
                FROM erp_compras_documentos_plantillas WHERE codigo=:codigo LIMIT 1");
            $stmt->execute(array(":codigo" => $plantilla["codigo"]));
            $idPlantilla = intval($stmt->fetchColumn());

            if ($idPlantilla <= 0) {
                $stmt = $db->prepare("INSERT INTO erp_compras_documentos_plantillas
                    (codigo, tipo_documento, audiencia, nombre, descripcion,
                     estatus, es_default, creado_por)
                    VALUES (:codigo, :tipo, :audiencia, :nombre, :descripcion,
                            'activa', 1, :usuario)");
                $stmt->execute(array(
                    ":codigo" => $plantilla["codigo"],
                    ":tipo" => $plantilla["tipo_documento"],
                    ":audiencia" => $plantilla["audiencia"],
                    ":nombre" => $plantilla["nombre"],
                    ":descripcion" => $plantilla["descripcion"],
                    ":usuario" => intval($idUsuario) ?: null
                ));
                $idPlantilla = intval($db->lastInsertId());
            }

            $stmt = $db->prepare("SELECT id_plantilla_config
                FROM erp_compras_documentos_plantillas_config
                WHERE id_plantilla_documento=:id LIMIT 1");
            $stmt->execute(array(":id" => $idPlantilla));
            if (!$stmt->fetchColumn()) {
                $config = $plantilla["config"];
                $stmt = $db->prepare("INSERT INTO erp_compras_documentos_plantillas_config
                    (id_plantilla_documento, mostrar_logo, mostrar_costos,
                     mostrar_impuestos, mostrar_totales, mostrar_sku_erp,
                     mostrar_sku_proveedor, mostrar_nombre_erp,
                     mostrar_nombre_proveedor, mostrar_observaciones_internas,
                     mostrar_observaciones_publicas, columnas_json, estilos_json,
                     pie_pagina)
                    VALUES (:id, :logo, :costos, :impuestos, :totales,
                            :sku_erp, :sku_proveedor, :nombre_erp,
                            :nombre_proveedor, :obs_internas, :obs_publicas,
                            :columnas, :estilos, :pie)");
                $stmt->execute(array(
                    ":id" => $idPlantilla,
                    ":logo" => $config["mostrar_logo"],
                    ":costos" => $config["mostrar_costos"],
                    ":impuestos" => $config["mostrar_impuestos"],
                    ":totales" => $config["mostrar_totales"],
                    ":sku_erp" => $config["mostrar_sku_erp"],
                    ":sku_proveedor" => $config["mostrar_sku_proveedor"],
                    ":nombre_erp" => $config["mostrar_nombre_erp"],
                    ":nombre_proveedor" => $config["mostrar_nombre_proveedor"],
                    ":obs_internas" => $config["mostrar_observaciones_internas"],
                    ":obs_publicas" => $config["mostrar_observaciones_publicas"],
                    ":columnas" => json_encode($config["columnas"]),
                    ":estilos" => json_encode(array()),
                    ":pie" => ""
                ));
            }
        }
    }

    private function plantillasBase() {
        return array(
            $this->plantillaBase("solicitud_compra_interna", "solicitud_compra", "interna", "Solicitud interna", true),
            $this->plantillaBase("solicitud_compra_proveedor", "solicitud_compra", "proveedor", "Solicitud para proveedor", false),
            $this->plantillaBase("orden_compra_interna", "orden_compra", "interna", "Orden interna", true),
            $this->plantillaBase("orden_compra_proveedor", "orden_compra", "proveedor", "Orden para proveedor", false)
        );
    }

    private function plantillaBase($codigo, $tipo, $audiencia, $nombre, $interna) {
        $columnas = $interna
            ? array("sku_proveedor", "nombre_proveedor", "sku_erp", "cantidad", "unidad", "costo", "impuestos", "total")
            : array("sku_proveedor", "nombre_proveedor", "cantidad", "unidad", "observacion_publica");
        return array(
            "codigo" => $codigo,
            "tipo_documento" => $tipo,
            "audiencia" => $audiencia,
            "nombre" => $nombre,
            "descripcion" => $interna
                ? "Documento operativo interno con costos, impuestos y trazabilidad."
                : "Documento para proveedor sin costos internos por defecto.",
            "config" => array(
                "mostrar_logo" => 1,
                "mostrar_costos" => $interna ? 1 : 0,
                "mostrar_impuestos" => $interna ? 1 : 0,
                "mostrar_totales" => $interna ? 1 : 0,
                "mostrar_sku_erp" => $interna ? 1 : 0,
                "mostrar_sku_proveedor" => 1,
                "mostrar_nombre_erp" => $interna ? 1 : 0,
                "mostrar_nombre_proveedor" => 1,
                "mostrar_observaciones_internas" => $interna ? 1 : 0,
                "mostrar_observaciones_publicas" => 1,
                "columnas" => $columnas
            )
        );
    }

    private function boolInt($datos, $campo, $default = 0) {
        if (!isset($datos[$campo])) {
            return intval($default);
        }
        return in_array($datos[$campo], array(1, "1", true, "true", "on", "si"), true) ? 1 : 0;
    }

    private function texto($datos, $campo, $limite = 0) {
        $valor = trim(isset($datos[$campo]) ? (string) $datos[$campo] : "");
        return $limite > 0 ? mb_substr($valor, 0, $limite) : $valor;
    }

    private function estatus($estatus) {
        return in_array($estatus, array("activa", "inactiva"), true) ? $estatus : "activa";
    }

    private function jsonSeguro($valor) {
        $datos = json_decode((string) $valor, true);
        return is_array($datos) ? $datos : array();
    }

    private function jsonDesdeEntrada($datos, $campo) {
        if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === "") {
            return null;
        }
        $valor = $datos[$campo];
        if (is_array($valor)) {
            return json_encode($valor);
        }
        $json = json_decode((string) $valor, true);
        if (!is_array($json)) {
            throw new Exception("El campo {$campo} debe ser JSON valido");
        }
        return json_encode($json);
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
