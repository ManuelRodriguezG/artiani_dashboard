<?php

class ComprasDocumentosPlantillas extends CRUD {

    private $tablaParametros = "sys_configuracion_parametros";
    private $tablaHistorial = "sys_configuracion_historial";

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-28
     * Proposito: consultar y mantener plantillas imprimibles de Compras por audiencia.
     * Impacto: Solicitudes/Ordenes; controla que documentos para proveedor no expongan costos internos por defecto.
     * Contrato: crea solo configuracion base idempotente; no genera documentos ni modifica solicitudes/ordenes.
     *
     * Actualizacion IA: Codex GPT-5 | Fecha: 2026-08-13
     * Proposito: permitir limpiar descripcion/subtitulo desde UI sin restaurar textos por defecto.
     * Impacto: Documentos imprimibles; respeta que un campo vacio capturado por el usuario sea una decision valida.
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
                    c.titulo_documento, c.subtitulo_documento,
                    c.empresa_nombre, c.empresa_rfc, c.empresa_contacto,
                    c.empresa_email, c.empresa_telefono, c.empresa_direccion,
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
            $datosNegocio = $this->datosNegocioCompartidos($db);
            foreach ($plantillas as &$plantilla) {
                $plantilla = $this->aplicarDatosNegocioCompartidos($plantilla, $datosNegocio);
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
                ":descripcion" => $descripcion,
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
                    titulo_documento=:titulo_documento,
                    subtitulo_documento=:subtitulo_documento,
                    empresa_nombre=:empresa_nombre,
                    empresa_rfc=:empresa_rfc,
                    empresa_contacto=:empresa_contacto,
                    empresa_email=:empresa_email,
                    empresa_telefono=:empresa_telefono,
                    empresa_direccion=:empresa_direccion,
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
                ":titulo_documento" => $this->texto($datos, "titulo_documento", 150),
                ":subtitulo_documento" => $this->texto($datos, "subtitulo_documento", 255),
                ":empresa_nombre" => $this->texto($datos, "empresa_nombre", 180),
                ":empresa_rfc" => $this->texto($datos, "empresa_rfc", 20),
                ":empresa_contacto" => $this->texto($datos, "empresa_contacto", 120),
                ":empresa_email" => $this->texto($datos, "empresa_email", 120),
                ":empresa_telefono" => $this->texto($datos, "empresa_telefono", 60),
                ":empresa_direccion" => $this->texto($datos, "empresa_direccion"),
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
                    c.titulo_documento, c.subtitulo_documento,
                    c.empresa_nombre, c.empresa_rfc, c.empresa_contacto,
                    c.empresa_email, c.empresa_telefono, c.empresa_direccion,
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
            $plantilla = $this->aplicarDatosNegocioCompartidos($plantilla, $this->datosNegocioCompartidos($db));
            $plantilla["columnas"] = $this->jsonSeguro($plantilla["columnas_json"]);
            $plantilla["estilos"] = $this->jsonSeguro($plantilla["estilos_json"]);
            return $this->respuesta(false, "success", "Plantilla consultada", $plantilla);
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-13
     * Proposito: consultar datos compartidos del negocio para documentos imprimibles de Compras.
     * Impacto: Solicitudes/Ordenes; evita configurar logo y datos fiscales/contacto por cada plantilla.
     * Contrato: usa `sys_configuracion_parametros`; no modifica plantillas ni documentos de compra.
     */
    public function consultarDatosNegocio() {
        try {
            $db = $this->getConexion();
            return $this->respuesta(false, "success", "Datos de negocio consultados", $this->datosNegocioCompartidos($db));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-13
     * Proposito: guardar datos compartidos del negocio usados por documentos de Compras.
     * Impacto: Plantillas imprimibles; centraliza nombre, RFC, contacto, direccion y logo.
     * Contrato: solo actualiza claves permitidas de `sys_configuracion_parametros` y conserva historial cuando existe.
     */
    public function guardarDatosNegocio($datos, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->asegurarParametrosDocumentosBase($db);

            $mapa = array(
                "empresa_nombre" => "empresa.nombre_comercial",
                "empresa_razon_social" => "empresa.razon_social",
                "empresa_rfc" => "empresa.rfc",
                "empresa_contacto" => "empresa.contacto_compras",
                "empresa_email" => "empresa.email_compras",
                "empresa_telefono" => "empresa.telefono",
                "empresa_direccion" => "empresa.direccion"
            );
            $stmtSelect = $db->prepare("SELECT id_configuracion_parametro, valor FROM {$this->tablaParametros} WHERE clave=:clave LIMIT 1");
            $stmtUpdate = $db->prepare("UPDATE {$this->tablaParametros}
                SET valor=:valor, fecha_actualizacion=NOW(), id_usuario_actualizacion=:usuario
                WHERE clave=:clave");
            $stmtHist = $this->tablaHistorialExiste($db)
                ? $db->prepare("INSERT INTO {$this->tablaHistorial}
                    (id_configuracion_parametro, clave, valor_antes, valor_despues, motivo, id_usuario)
                    VALUES (:id, :clave, :antes, :despues, :motivo, :usuario)")
                : null;

            $actualizados = array();
            foreach ($mapa as $campo => $clave) {
                $valorNuevo = $this->texto($datos, $campo, $campo === "empresa_direccion" ? 0 : 180);
                $stmtSelect->execute(array(":clave" => $clave));
                $actual = $stmtSelect->fetch(PDO::FETCH_ASSOC);
                if (!$actual) {
                    continue;
                }
                $valorAntes = (string) $actual["valor"];
                if ($valorAntes === $valorNuevo) {
                    continue;
                }
                $stmtUpdate->execute(array(
                    ":valor" => $valorNuevo,
                    ":usuario" => intval($idUsuario) ?: null,
                    ":clave" => $clave
                ));
                if ($stmtHist) {
                    $stmtHist->execute(array(
                        ":id" => intval($actual["id_configuracion_parametro"]),
                        ":clave" => $clave,
                        ":antes" => $valorAntes,
                        ":despues" => $valorNuevo,
                        ":motivo" => "Actualizacion de datos compartidos de documentos de Compras",
                        ":usuario" => intval($idUsuario) ?: null
                    ));
                }
                $actualizados[] = $clave;
            }

            $db->commit();
            return $this->respuesta(false, "success", "Datos del negocio guardados", array(
                "actualizados" => $actualizados,
                "datos_negocio" => $this->datosNegocioCompartidos($db)
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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
                     mostrar_observaciones_publicas, titulo_documento,
                     subtitulo_documento, empresa_nombre, empresa_rfc,
                     empresa_contacto, empresa_email, empresa_telefono,
                     empresa_direccion, columnas_json, estilos_json, pie_pagina)
                    VALUES (:id, :logo, :costos, :impuestos, :totales,
                            :sku_erp, :sku_proveedor, :nombre_erp,
                            :nombre_proveedor, :obs_internas, :obs_publicas,
                            :titulo, :subtitulo, :empresa_nombre, :empresa_rfc,
                            :empresa_contacto, :empresa_email, :empresa_telefono,
                            :empresa_direccion, :columnas, :estilos, :pie)");
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
                    ":titulo" => $config["titulo_documento"],
                    ":subtitulo" => $config["subtitulo_documento"],
                    ":empresa_nombre" => "",
                    ":empresa_rfc" => "",
                    ":empresa_contacto" => "",
                    ":empresa_email" => "",
                    ":empresa_telefono" => "",
                    ":empresa_direccion" => "",
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
                "titulo_documento" => $this->tituloBase($tipo, $audiencia),
                "subtitulo_documento" => $interna
                    ? "Documento interno de Compras"
                    : "Favor de confirmar disponibilidad, precio vigente y tiempo estimado de entrega.",
                "columnas" => $columnas
            )
        );
    }

    private function tituloBase($tipo, $audiencia) {
        if ($tipo === "solicitud_compra" && $audiencia === "proveedor") {
            return "Solicitud de cotizacion";
        }
        if ($tipo === "orden_compra" && $audiencia === "proveedor") {
            return "Orden de compra";
        }
        return $tipo === "orden_compra" ? "Orden de compra interna" : "Solicitud de compra interna";
    }

    private function datosNegocioCompartidos($db) {
        if (!$this->tablaParametrosExiste($db)) {
            return $this->datosNegocioVacios();
        }
        $this->asegurarParametrosDocumentosBase($db);
        $claves = array(
            "branding.logo_principal",
            "empresa.nombre_comercial",
            "empresa.razon_social",
            "empresa.rfc",
            "empresa.contacto_compras",
            "empresa.email_compras",
            "empresa.telefono",
            "empresa.direccion"
        );
        $marcadores = implode(",", array_fill(0, count($claves), "?"));
        $stmt = $db->prepare("SELECT clave, valor FROM {$this->tablaParametros} WHERE clave IN ({$marcadores}) AND estatus=1");
        $stmt->execute($claves);
        $valores = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $valores[$fila["clave"]] = trim((string) $fila["valor"]);
        }

        $nombreComercial = isset($valores["empresa.nombre_comercial"]) ? $valores["empresa.nombre_comercial"] : "";
        $razonSocial = isset($valores["empresa.razon_social"]) ? $valores["empresa.razon_social"] : "";
        return array(
            "logo_ruta" => isset($valores["branding.logo_principal"]) ? $valores["branding.logo_principal"] : "",
            "empresa_nombre" => $nombreComercial !== "" ? $nombreComercial : $razonSocial,
            "empresa_razon_social" => $razonSocial,
            "empresa_rfc" => isset($valores["empresa.rfc"]) ? $valores["empresa.rfc"] : "",
            "empresa_contacto" => isset($valores["empresa.contacto_compras"]) ? $valores["empresa.contacto_compras"] : "",
            "empresa_email" => isset($valores["empresa.email_compras"]) ? $valores["empresa.email_compras"] : "",
            "empresa_telefono" => isset($valores["empresa.telefono"]) ? $valores["empresa.telefono"] : "",
            "empresa_direccion" => isset($valores["empresa.direccion"]) ? $valores["empresa.direccion"] : ""
        );
    }

    private function aplicarDatosNegocioCompartidos($plantilla, $datosNegocio) {
        foreach (array("logo_ruta", "empresa_nombre", "empresa_rfc", "empresa_contacto", "empresa_email", "empresa_telefono", "empresa_direccion") as $campo) {
            if (!isset($plantilla[$campo]) || trim((string) $plantilla[$campo]) === "") {
                $plantilla[$campo] = isset($datosNegocio[$campo]) ? $datosNegocio[$campo] : "";
            }
        }
        return $plantilla;
    }

    private function datosNegocioVacios() {
        return array(
            "logo_ruta" => "",
            "empresa_nombre" => "",
            "empresa_razon_social" => "",
            "empresa_rfc" => "",
            "empresa_contacto" => "",
            "empresa_email" => "",
            "empresa_telefono" => "",
            "empresa_direccion" => ""
        );
    }

    private function asegurarParametrosDocumentosBase($db) {
        $semillas = array(
            array("grupo" => "empresa", "clave" => "empresa.nombre_comercial", "tipo" => "texto", "valor" => "", "descripcion" => "Nombre comercial visible en documentos para proveedor"),
            array("grupo" => "empresa", "clave" => "empresa.razon_social", "tipo" => "texto", "valor" => "", "descripcion" => "Razon social visible en documentos del negocio"),
            array("grupo" => "empresa", "clave" => "empresa.rfc", "tipo" => "texto", "valor" => "", "descripcion" => "RFC del negocio para documentos externos"),
            array("grupo" => "empresa", "clave" => "empresa.contacto_compras", "tipo" => "texto", "valor" => "", "descripcion" => "Contacto de Compras visible para proveedor"),
            array("grupo" => "empresa", "clave" => "empresa.email_compras", "tipo" => "email", "valor" => "", "descripcion" => "Email de Compras visible para proveedor"),
            array("grupo" => "empresa", "clave" => "empresa.telefono", "tipo" => "texto", "valor" => "", "descripcion" => "Telefono visible en documentos externos"),
            array("grupo" => "empresa", "clave" => "empresa.direccion", "tipo" => "texto", "valor" => "", "descripcion" => "Direccion visible en documentos externos")
        );
        $stmt = $db->prepare("INSERT INTO {$this->tablaParametros}
            (grupo, clave, tipo_dato, valor, descripcion, editable_ui, sensible, estatus)
            VALUES (:grupo, :clave, :tipo, :valor, :descripcion, 1, 0, 1)
            ON DUPLICATE KEY UPDATE grupo=VALUES(grupo), tipo_dato=VALUES(tipo_dato), descripcion=VALUES(descripcion), editable_ui=1, sensible=0, estatus=1");
        foreach ($semillas as $semilla) {
            $stmt->execute(array(
                ":grupo" => $semilla["grupo"],
                ":clave" => $semilla["clave"],
                ":tipo" => $semilla["tipo"],
                ":valor" => $semilla["valor"],
                ":descripcion" => $semilla["descripcion"]
            ));
        }
    }

    private function tablaParametrosExiste($db) {
        $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $this->tablaParametros));
        return !empty($stmt->fetch(PDO::FETCH_ASSOC));
    }

    private function tablaHistorialExiste($db) {
        $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $this->tablaHistorial));
        return !empty($stmt->fetch(PDO::FETCH_ASSOC));
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
