<?php

class ListasPreciosErp extends CRUD {

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: entregar resumen read-only del modulo Listas de precios.
     * Impacto: permite construir UI operativa sin escribir listas, detalles ni asignaciones.
     * Contrato: solo consulta BD; tolera tablas/columnas pendientes.
     */
    public function resumenReadOnly($filtros = array()) {
        try {
            $db = $this->getConexion();
            $schema = $this->schemaListas($db);
            if (!$schema["listas"] || !$schema["detalle"]) {
                return $this->respuesta(false, "warning", "Esquema de listas incompleto", array(
                    "schema" => $schema,
                    "kpis" => $this->kpisVacios(),
                    "listas" => array(),
                    "conflictos" => array()
                ));
            }

            $listas = $this->listarListasInterno($db, $filtros);
            $conflictos = $this->detectarConflictosInterno($db);
            return $this->respuesta(false, "success", "Listas de precios consultadas", array(
                "schema" => $schema,
                "kpis" => $this->kpisListas($db, $schema),
                "listas" => $listas,
                "conflictos" => $conflictos
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: listar encabezados de listas con conteos operativos.
     * Impacto: UI read-only del modulo Comercial/Ventas.
     * Contrato: no modifica precios; filtros acotados.
     */
    public function listarReadOnly($filtros = array()) {
        try {
            $db = $this->getConexion();
            if (!$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle")) {
                return $this->respuesta(false, "warning", "Falta esquema de listas de precios", array("listas" => array()));
            }
            return $this->respuesta(false, "success", "Listas consultadas", array(
                "listas" => $this->listarListasInterno($db, $filtros)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: consultar detalle read-only de una lista de precios.
     * Impacto: permite revisar SKUs, vigencias y asignaciones CRM antes del CRUD real.
     * Contrato: no modifica encabezado, detalle ni clientes.
     */
    public function consultarReadOnly($idLista) {
        try {
            $db = $this->getConexion();
            $idLista = intval($idLista);
            if ($idLista <= 0) {
                return $this->respuesta(true, "warning", "Lista obligatoria");
            }
            if (!$this->tablaExiste($db, "erp_listas_precios")) {
                return $this->respuesta(true, "warning", "Falta tabla de listas");
            }

            $stmt = $db->prepare("SELECT * FROM erp_listas_precios WHERE id_lista_precio=:lista LIMIT 1");
            $stmt->execute(array(":lista" => $idLista));
            $lista = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lista) {
                return $this->respuesta(true, "warning", "Lista no encontrada");
            }

            return $this->respuesta(false, "success", "Lista consultada", array(
                "lista" => $lista,
                "detalles" => $this->listarDetalles($db, $idLista),
                "asignaciones" => $this->listarAsignaciones($db, $idLista),
                "conflictos" => $this->detectarConflictosLista($db, $idLista)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: detectar conflictos de listas antes de permitir escritura real.
     * Impacto: prepara validaciones de CRUD, UAT y migracion CRM/listas.
     * Contrato: solo lectura; devuelve hallazgos accionables.
     */
    public function conflictosReadOnly($filtros = array()) {
        try {
            $db = $this->getConexion();
            return $this->respuesta(false, "success", "Conflictos de listas consultados", array(
                "conflictos" => $this->detectarConflictosInterno($db),
                "schema" => $this->schemaListas($db)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: validar alta/edicion futura de encabezado de lista sin guardar.
     * Impacto: prepara CRUD de Listas de precios con reglas de vigencia, canal y unicidad.
     * Contrato: dry-run; no crea ni actualiza listas.
     */
    public function listaDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idLista = intval($this->valor($datos, "id_lista_precio", 0));
            $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
            $nombre = trim((string) $this->valor($datos, "nombre", ""));
            $canal = trim((string) $this->valor($datos, "canal", ""));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $prioridad = intval($this->valor($datos, "prioridad", 100));
            $estatus = trim((string) $this->valor($datos, "estatus", "borrador"));
            $fechaInicio = trim((string) $this->valor($datos, "fecha_inicio", ""));
            $fechaFin = trim((string) $this->valor($datos, "fecha_fin", ""));
            $bloqueos = array();
            $avisos = array();

            if (!$this->tablaExiste($db, "erp_listas_precios")) {
                $bloqueos[] = "Falta tabla erp_listas_precios";
            }
            if ($codigo === "" || !preg_match('/^[A-Z0-9._-]{3,50}$/', $codigo)) {
                $bloqueos[] = "Codigo obligatorio de 3 a 50 caracteres; usar letras, numeros, punto, guion o guion bajo";
            }
            if ($nombre === "" || strlen($nombre) > 150) {
                $bloqueos[] = "Nombre obligatorio de maximo 150 caracteres";
            }
            if ($canal !== "" && !in_array($canal, array("pos", "pedido_tienda", "ecommerce", "mayoreo", "general"), true)) {
                $bloqueos[] = "Canal no permitido para fase 1";
            }
            if ($idAlmacen < 0) {
                $bloqueos[] = "Almacen invalido";
            }
            if ($prioridad <= 0 || $prioridad > 9999) {
                $bloqueos[] = "Prioridad debe estar entre 1 y 9999";
            }
            if (!in_array($estatus, array("borrador", "activa", "pausada", "cancelada"), true)) {
                $bloqueos[] = "Estatus no permitido";
            }
            if (!$this->fechaValida($fechaInicio)) {
                $bloqueos[] = "Fecha inicio invalida";
            }
            if (!$this->fechaValida($fechaFin)) {
                $bloqueos[] = "Fecha fin invalida";
            }
            if ($fechaInicio !== "" && $fechaFin !== "" && strtotime($fechaInicio) > strtotime($fechaFin)) {
                $bloqueos[] = "Fecha inicio no puede ser posterior a fecha fin";
            }
            if ($codigo !== "" && $this->tablaExiste($db, "erp_listas_precios")) {
                $stmt = $db->prepare("SELECT id_lista_precio FROM erp_listas_precios WHERE codigo=:codigo AND id_lista_precio<>:id LIMIT 1");
                $stmt->execute(array(":codigo" => $codigo, ":id" => $idLista));
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $bloqueos[] = "Ya existe una lista con ese codigo";
                }
            }
            if ($estatus === "activa" && $this->valor($datos, "confirmar_activacion", "") !== "1") {
                $avisos[] = "Activar una lista real debera requerir permiso fino y auditoria";
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Lista valida en dry-run" : "Lista con bloqueos", array(
                "dry_run" => true,
                "puede_guardar" => empty($bloqueos),
                "lista_normalizada" => array(
                    "id_lista_precio" => $idLista,
                    "codigo" => $codigo,
                    "nombre" => $nombre,
                    "canal" => $canal === "general" ? "" : $canal,
                    "id_almacen" => $idAlmacen > 0 ? $idAlmacen : null,
                    "prioridad" => $prioridad,
                    "fecha_inicio" => $fechaInicio !== "" ? $fechaInicio : null,
                    "fecha_fin" => $fechaFin !== "" ? $fechaFin : null,
                    "estatus" => $estatus
                ),
                "bloqueos" => $bloqueos,
                "avisos" => $avisos
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: validar detalle futuro de lista sin guardar precio.
     * Impacto: protege contra precios invalidos, duplicados y alcance ambiguo antes del CRUD real.
     * Contrato: dry-run; no crea ni actualiza detalles.
     */
    public function detalleDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idDetalle = intval($this->valor($datos, "id_lista_precio_detalle", 0));
            $idLista = intval($this->valor($datos, "id_lista_precio", 0));
            $idSku = intval($this->valor($datos, "id_sku", 0));
            $idProducto = intval($this->valor($datos, "id_producto_erp", 0));
            $precio = round(floatval($this->valor($datos, "precio", 0)), 6);
            $moneda = strtoupper(trim((string) $this->valor($datos, "moneda", "MXN")));
            $fechaInicio = trim((string) $this->valor($datos, "fecha_inicio", ""));
            $fechaFin = trim((string) $this->valor($datos, "fecha_fin", ""));
            $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
            $bloqueos = array();
            $avisos = array();

            if (!$this->tablaExiste($db, "erp_listas_precios_detalle")) {
                $bloqueos[] = "Falta tabla erp_listas_precios_detalle";
            }
            if (!$this->existeLista($db, $idLista)) {
                $bloqueos[] = "Lista no existe";
            }
            if ($idSku <= 0 && $idProducto <= 0) {
                $bloqueos[] = "Indica SKU o producto ERP";
            }
            if ($idSku > 0 && !$this->existeSku($db, $idSku)) {
                $bloqueos[] = "SKU no existe o no esta activo";
            }
            if ($idProducto > 0 && !$this->existeProducto($db, $idProducto)) {
                $bloqueos[] = "Producto ERP no existe o no esta activo";
            }
            if ($idSku <= 0 && $idProducto > 0) {
                $avisos[] = "Precio por producto aplicara a variantes si el resolutor no encuentra precio por SKU";
            }
            if ($precio <= 0) {
                $bloqueos[] = "Precio debe ser mayor a cero";
            }
            if (!preg_match('/^[A-Z]{3}$/', $moneda)) {
                $bloqueos[] = "Moneda debe usar codigo de 3 letras";
            }
            if (!$this->fechaValida($fechaInicio)) {
                $bloqueos[] = "Fecha inicio invalida";
            }
            if (!$this->fechaValida($fechaFin)) {
                $bloqueos[] = "Fecha fin invalida";
            }
            if ($fechaInicio !== "" && $fechaFin !== "" && strtotime($fechaInicio) > strtotime($fechaFin)) {
                $bloqueos[] = "Fecha inicio no puede ser posterior a fecha fin";
            }
            if (!in_array($estatus, array("activo", "pausado", "cancelado"), true)) {
                $bloqueos[] = "Estatus de detalle no permitido";
            }
            $duplicados = $this->buscarDetallesDuplicados($db, $idLista, $idDetalle, $idSku, $idProducto, $moneda);
            if (!empty($duplicados)) {
                $bloqueos[] = "Ya existe detalle activo equivalente para lista/SKU/producto/moneda";
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Detalle valido en dry-run" : "Detalle con bloqueos", array(
                "dry_run" => true,
                "puede_guardar" => empty($bloqueos),
                "detalle_normalizado" => array(
                    "id_lista_precio_detalle" => $idDetalle,
                    "id_lista_precio" => $idLista,
                    "id_sku" => $idSku > 0 ? $idSku : null,
                    "id_producto_erp" => $idProducto > 0 ? $idProducto : null,
                    "precio" => $precio,
                    "moneda" => $moneda,
                    "fecha_inicio" => $fechaInicio !== "" ? $fechaInicio : null,
                    "fecha_fin" => $fechaFin !== "" ? $fechaFin : null,
                    "estatus" => $estatus
                ),
                "duplicados" => $duplicados,
                "bloqueos" => $bloqueos,
                "avisos" => $avisos
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: validar asignacion futura de lista a cliente CRM sin guardar.
     * Impacto: prepara contrato CRM/listas antes de habilitar asignacion real.
     * Contrato: dry-run; no modifica clientes ni relaciones.
     */
    public function asignacionClienteDryRun($datos = array()) {
        try {
            $db = $this->getConexion();
            $idAsignacion = intval($this->valor($datos, "id_cliente_lista_precio", 0));
            $idLista = intval($this->valor($datos, "id_lista_precio", 0));
            $idClienteCrm = intval($this->valor($datos, "id_cliente_crm", $this->valor($datos, "id_cliente", 0)));
            $prioridad = intval($this->valor($datos, "prioridad", 1));
            $fechaInicio = trim((string) $this->valor($datos, "fecha_inicio", ""));
            $fechaFin = trim((string) $this->valor($datos, "fecha_fin", ""));
            $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
            $bloqueos = array();
            $avisos = array();

            if (!$this->tablaExiste($db, "erp_clientes_listas_precios")) {
                $bloqueos[] = "Falta tabla erp_clientes_listas_precios";
            }
            if (!$this->columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
                $avisos[] = "Falta columna id_cliente_crm; la asignacion real debe esperar DDL CRM/listas";
            }
            if (!$this->existeLista($db, $idLista)) {
                $bloqueos[] = "Lista no existe";
            }
            if (!$this->existeClienteCrm($db, $idClienteCrm)) {
                $bloqueos[] = "Cliente CRM no existe o no esta activo";
            }
            if ($prioridad <= 0 || $prioridad > 9999) {
                $bloqueos[] = "Prioridad debe estar entre 1 y 9999";
            }
            if (!$this->fechaValida($fechaInicio)) {
                $bloqueos[] = "Fecha inicio invalida";
            }
            if (!$this->fechaValida($fechaFin)) {
                $bloqueos[] = "Fecha fin invalida";
            }
            if ($fechaInicio !== "" && $fechaFin !== "" && strtotime($fechaInicio) > strtotime($fechaFin)) {
                $bloqueos[] = "Fecha inicio no puede ser posterior a fecha fin";
            }
            if (!in_array($estatus, array("activo", "pausado", "cancelado"), true)) {
                $bloqueos[] = "Estatus de asignacion no permitido";
            }
            $duplicados = $this->buscarAsignacionesDuplicadas($db, $idAsignacion, $idLista, $idClienteCrm);
            if (!empty($duplicados)) {
                $bloqueos[] = "Ya existe asignacion activa equivalente para cliente/lista";
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Asignacion valida en dry-run" : "Asignacion con bloqueos", array(
                "dry_run" => true,
                "puede_guardar" => empty($bloqueos),
                "asignacion_normalizada" => array(
                    "id_cliente_lista_precio" => $idAsignacion,
                    "id_lista_precio" => $idLista,
                    "id_cliente_crm" => $idClienteCrm,
                    "prioridad" => $prioridad,
                    "fecha_inicio" => $fechaInicio !== "" ? $fechaInicio : date("Y-m-d H:i:s"),
                    "fecha_fin" => $fechaFin !== "" ? $fechaFin : null,
                    "estatus" => $estatus
                ),
                "duplicados" => $duplicados,
                "bloqueos" => $bloqueos,
                "avisos" => $avisos
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: guardar encabezado de lista de precios con transaccion y auditoria comercial.
     * Impacto: crea/edita `erp_listas_precios`; no afecta ventas pasadas ni recalcula POS por si mismo.
     * Contrato: usar solo desde controlador con permiso fino, respaldo y autorizacion explicita.
     */
    public function listaGuardarAutorizado($datos = array(), $idUsuario = 0) {
        $db = null;
        try {
            $db = $this->getConexion();
            $validacion = $this->listaDryRun($datos);
            if (!empty($validacion["error"]) || empty($validacion["depurar"]["puede_guardar"])) {
                return $validacion;
            }
            if (!$this->tablaExiste($db, "erp_listas_precios_eventos")) {
                return $this->respuesta(true, "warning", "Falta auditoria comercial de listas; no se permite guardar sin trazabilidad");
            }

            $lista = $validacion["depurar"]["lista_normalizada"];
            $idLista = intval($lista["id_lista_precio"]);
            $antes = $idLista > 0 ? $this->obtenerFilaPorId($db, "erp_listas_precios", "id_lista_precio", $idLista) : null;
            if ($idLista > 0 && !$antes) {
                return $this->respuesta(true, "warning", "Lista no encontrada para edicion");
            }

            $db->beginTransaction();
            if ($idLista > 0) {
                $stmt = $db->prepare("UPDATE erp_listas_precios
                    SET codigo=:codigo, nombre=:nombre, canal=:canal, id_almacen=:almacen, prioridad=:prioridad,
                        fecha_inicio=:fecha_inicio, fecha_fin=:fecha_fin, estatus=:estatus, observaciones=:observaciones,
                        fecha_actualizacion=NOW()
                    WHERE id_lista_precio=:id");
                $stmt->execute(array(
                    ":codigo" => $lista["codigo"],
                    ":nombre" => $lista["nombre"],
                    ":canal" => $lista["canal"],
                    ":almacen" => $lista["id_almacen"],
                    ":prioridad" => $lista["prioridad"],
                    ":fecha_inicio" => $lista["fecha_inicio"],
                    ":fecha_fin" => $lista["fecha_fin"],
                    ":estatus" => $lista["estatus"],
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                    ":id" => $idLista
                ));
                $accion = "editar_lista";
            } else {
                $stmt = $db->prepare("INSERT INTO erp_listas_precios
                    (codigo, nombre, canal, id_almacen, prioridad, fecha_inicio, fecha_fin, estatus, observaciones)
                    VALUES (:codigo, :nombre, :canal, :almacen, :prioridad, :fecha_inicio, :fecha_fin, :estatus, :observaciones)");
                $stmt->execute(array(
                    ":codigo" => $lista["codigo"],
                    ":nombre" => $lista["nombre"],
                    ":canal" => $lista["canal"],
                    ":almacen" => $lista["id_almacen"],
                    ":prioridad" => $lista["prioridad"],
                    ":fecha_inicio" => $lista["fecha_inicio"],
                    ":fecha_fin" => $lista["fecha_fin"],
                    ":estatus" => $lista["estatus"],
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", ""))
                ));
                $idLista = intval($db->lastInsertId());
                $accion = "crear_lista";
            }
            $despues = $this->obtenerFilaPorId($db, "erp_listas_precios", "id_lista_precio", $idLista);
            $this->registrarEventoLista($db, array(
                "id_lista_precio" => $idLista,
                "entidad" => "erp_listas_precios",
                "entidad_id" => $idLista,
                "accion" => $accion,
                "resumen" => "Guardado de encabezado de lista de precios",
                "motivo" => trim((string) $this->valor($datos, "motivo", "")),
                "datos_antes" => $antes,
                "datos_despues" => $despues,
                "creado_por" => intval($idUsuario)
            ));
            $db->commit();

            return $this->respuesta(false, "success", "Lista de precios guardada", array("id_lista_precio" => $idLista, "lista" => $despues));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: guardar precio de SKU/producto dentro de una lista con auditoria.
     * Impacto: cambia precio base futuro del resolutor; ventas ya emitidas conservan snapshot.
     * Contrato: usar solo desde controlador con `ventas.listas.editar`, respaldo y autorizacion.
     */
    public function detalleGuardarAutorizado($datos = array(), $idUsuario = 0) {
        $db = null;
        try {
            $db = $this->getConexion();
            $validacion = $this->detalleDryRun($datos);
            if (!empty($validacion["error"]) || empty($validacion["depurar"]["puede_guardar"])) {
                return $validacion;
            }
            if (!$this->tablaExiste($db, "erp_listas_precios_eventos")) {
                return $this->respuesta(true, "warning", "Falta auditoria comercial de listas; no se permite guardar sin trazabilidad");
            }

            $detalle = $validacion["depurar"]["detalle_normalizado"];
            $idDetalle = intval($detalle["id_lista_precio_detalle"]);
            $antes = $idDetalle > 0 ? $this->obtenerFilaPorId($db, "erp_listas_precios_detalle", "id_lista_precio_detalle", $idDetalle) : null;
            if ($idDetalle > 0 && !$antes) {
                return $this->respuesta(true, "warning", "Detalle no encontrado para edicion");
            }

            $db->beginTransaction();
            if ($idDetalle > 0) {
                $stmt = $db->prepare("UPDATE erp_listas_precios_detalle
                    SET id_lista_precio=:lista, id_sku=:sku, id_producto_erp=:producto, precio=:precio, moneda=:moneda,
                        fecha_inicio=:fecha_inicio, fecha_fin=:fecha_fin, estatus=:estatus
                    WHERE id_lista_precio_detalle=:id");
                $stmt->execute(array(
                    ":lista" => $detalle["id_lista_precio"],
                    ":sku" => $detalle["id_sku"],
                    ":producto" => $detalle["id_producto_erp"],
                    ":precio" => $detalle["precio"],
                    ":moneda" => $detalle["moneda"],
                    ":fecha_inicio" => $detalle["fecha_inicio"],
                    ":fecha_fin" => $detalle["fecha_fin"],
                    ":estatus" => $detalle["estatus"],
                    ":id" => $idDetalle
                ));
                $accion = "editar_detalle";
            } else {
                $stmt = $db->prepare("INSERT INTO erp_listas_precios_detalle
                    (id_lista_precio, id_sku, id_producto_erp, precio, moneda, fecha_inicio, fecha_fin, estatus)
                    VALUES (:lista, :sku, :producto, :precio, :moneda, :fecha_inicio, :fecha_fin, :estatus)");
                $stmt->execute(array(
                    ":lista" => $detalle["id_lista_precio"],
                    ":sku" => $detalle["id_sku"],
                    ":producto" => $detalle["id_producto_erp"],
                    ":precio" => $detalle["precio"],
                    ":moneda" => $detalle["moneda"],
                    ":fecha_inicio" => $detalle["fecha_inicio"],
                    ":fecha_fin" => $detalle["fecha_fin"],
                    ":estatus" => $detalle["estatus"]
                ));
                $idDetalle = intval($db->lastInsertId());
                $accion = "crear_detalle";
            }
            $despues = $this->obtenerFilaPorId($db, "erp_listas_precios_detalle", "id_lista_precio_detalle", $idDetalle);
            $this->registrarEventoLista($db, array(
                "id_lista_precio" => intval($detalle["id_lista_precio"]),
                "id_lista_precio_detalle" => $idDetalle,
                "entidad" => "erp_listas_precios_detalle",
                "entidad_id" => $idDetalle,
                "accion" => $accion,
                "resumen" => "Guardado de detalle de lista de precios",
                "motivo" => trim((string) $this->valor($datos, "motivo", "")),
                "datos_antes" => $antes,
                "datos_despues" => $despues,
                "creado_por" => intval($idUsuario)
            ));
            $db->commit();

            return $this->respuesta(false, "success", "Detalle de lista guardado", array("id_lista_precio_detalle" => $idDetalle, "detalle" => $despues));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: guardar asignacion de lista de precios a cliente CRM con prioridad y vigencia.
     * Impacto: puede cambiar resolucion futura de precios para el cliente; no toca ventas pasadas.
     * Contrato: requiere esquema CRM canonico con `id_cliente_crm` y auditoria comercial.
     */
    public function asignacionClienteGuardarAutorizado($datos = array(), $idUsuario = 0) {
        $db = null;
        try {
            $db = $this->getConexion();
            $validacion = $this->asignacionClienteDryRun($datos);
            if (!empty($validacion["error"]) || empty($validacion["depurar"]["puede_guardar"])) {
                return $validacion;
            }
            if (!$this->tablaExiste($db, "erp_listas_precios_eventos")) {
                return $this->respuesta(true, "warning", "Falta auditoria comercial de listas; no se permite guardar sin trazabilidad");
            }
            if (!$this->columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
                return $this->respuesta(true, "warning", "Falta id_cliente_crm en asignaciones; no se permite guardar contrato CRM ambiguo");
            }

            $asignacion = $validacion["depurar"]["asignacion_normalizada"];
            $idAsignacion = intval($asignacion["id_cliente_lista_precio"]);
            $antes = $idAsignacion > 0 ? $this->obtenerFilaPorId($db, "erp_clientes_listas_precios", "id_cliente_lista_precio", $idAsignacion) : null;
            if ($idAsignacion > 0 && !$antes) {
                return $this->respuesta(true, "warning", "Asignacion no encontrada para edicion");
            }

            $db->beginTransaction();
            if ($idAsignacion > 0) {
                $stmt = $db->prepare("UPDATE erp_clientes_listas_precios
                    SET id_cliente_crm=:cliente_crm, id_cliente=NULL, id_lista_precio=:lista, prioridad=:prioridad,
                        fecha_inicio=:fecha_inicio, fecha_fin=:fecha_fin, estatus=:estatus, creado_por=:usuario,
                        observaciones=:observaciones
                    WHERE id_cliente_lista_precio=:id");
                $stmt->execute(array(
                    ":cliente_crm" => $asignacion["id_cliente_crm"],
                    ":lista" => $asignacion["id_lista_precio"],
                    ":prioridad" => $asignacion["prioridad"],
                    ":fecha_inicio" => $asignacion["fecha_inicio"],
                    ":fecha_fin" => $asignacion["fecha_fin"],
                    ":estatus" => $asignacion["estatus"],
                    ":usuario" => intval($idUsuario),
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                    ":id" => $idAsignacion
                ));
                $accion = "editar_asignacion_cliente";
            } else {
                $stmt = $db->prepare("INSERT INTO erp_clientes_listas_precios
                    (id_cliente, id_cliente_crm, id_lista_precio, prioridad, fecha_inicio, fecha_fin, estatus, creado_por, observaciones)
                    VALUES (NULL, :cliente_crm, :lista, :prioridad, :fecha_inicio, :fecha_fin, :estatus, :usuario, :observaciones)");
                $stmt->execute(array(
                    ":cliente_crm" => $asignacion["id_cliente_crm"],
                    ":lista" => $asignacion["id_lista_precio"],
                    ":prioridad" => $asignacion["prioridad"],
                    ":fecha_inicio" => $asignacion["fecha_inicio"],
                    ":fecha_fin" => $asignacion["fecha_fin"],
                    ":estatus" => $asignacion["estatus"],
                    ":usuario" => intval($idUsuario),
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", ""))
                ));
                $idAsignacion = intval($db->lastInsertId());
                $accion = "crear_asignacion_cliente";
            }
            $despues = $this->obtenerFilaPorId($db, "erp_clientes_listas_precios", "id_cliente_lista_precio", $idAsignacion);
            $this->registrarEventoLista($db, array(
                "id_lista_precio" => intval($asignacion["id_lista_precio"]),
                "id_cliente_lista_precio" => $idAsignacion,
                "entidad" => "erp_clientes_listas_precios",
                "entidad_id" => $idAsignacion,
                "accion" => $accion,
                "resumen" => "Guardado de asignacion cliente CRM/lista",
                "motivo" => trim((string) $this->valor($datos, "motivo", "")),
                "datos_antes" => $antes,
                "datos_despues" => $despues,
                "creado_por" => intval($idUsuario)
            ));
            $db->commit();

            return $this->respuesta(false, "success", "Asignacion cliente/lista guardada", array("id_cliente_lista_precio" => $idAsignacion, "asignacion" => $despues));
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function listarListasInterno($db, $filtros) {
        $where = array("1=1");
        $params = array();
        $q = trim((string) $this->valor($filtros, "q", ""));
        $estatus = trim((string) $this->valor($filtros, "estatus", ""));
        $canal = trim((string) $this->valor($filtros, "canal", ""));
        $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
        $limite = max(10, min(100, intval($this->valor($filtros, "limite", 50))));

        if ($q !== "") {
            $where[] = "(l.codigo LIKE :q OR l.nombre LIKE :q)";
            $params[":q"] = "%" . $q . "%";
        }
        if ($estatus !== "") {
            $where[] = "l.estatus=:estatus";
            $params[":estatus"] = $estatus;
        }
        if ($canal !== "") {
            $where[] = "(l.canal=:canal OR (:canal_general='general' AND (l.canal IS NULL OR l.canal='')))";
            $params[":canal"] = $canal;
            $params[":canal_general"] = $canal;
        }
        if ($idAlmacen > 0) {
            $where[] = "(l.id_almacen=:almacen OR l.id_almacen IS NULL OR l.id_almacen=0)";
            $params[":almacen"] = $idAlmacen;
        }

        $selectAsignaciones = $this->tablaExiste($db, "erp_clientes_listas_precios")
            ? "(SELECT COUNT(*) FROM erp_clientes_listas_precios cl WHERE cl.id_lista_precio=l.id_lista_precio AND cl.estatus='activo') asignaciones_activas"
            : "0 asignaciones_activas";
        $sql = "SELECT l.*,
                (SELECT COUNT(*) FROM erp_listas_precios_detalle d WHERE d.id_lista_precio=l.id_lista_precio) detalles_total,
                (SELECT COUNT(*) FROM erp_listas_precios_detalle d WHERE d.id_lista_precio=l.id_lista_precio AND d.estatus='activo') detalles_activos,
                (SELECT MIN(d.precio) FROM erp_listas_precios_detalle d WHERE d.id_lista_precio=l.id_lista_precio AND d.estatus='activo') precio_min,
                (SELECT MAX(d.precio) FROM erp_listas_precios_detalle d WHERE d.id_lista_precio=l.id_lista_precio AND d.estatus='activo') precio_max,
                $selectAsignaciones
            FROM erp_listas_precios l
            WHERE " . implode(" AND ", $where) . "
            ORDER BY l.estatus='activa' DESC, l.prioridad ASC, l.id_lista_precio DESC
            LIMIT " . intval($limite);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarDetalles($db, $idLista) {
        if (!$this->tablaExiste($db, "erp_listas_precios_detalle")) {
            return array();
        }
        $stmt = $db->prepare("SELECT d.*, s.sku, COALESCE(s.nombre, p.nombre) nombre_sku, p.codigo_producto, p.nombre producto
            FROM erp_listas_precios_detalle d
            LEFT JOIN erp_catalogo_skus s ON s.id_sku=d.id_sku
            LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=COALESCE(d.id_producto_erp, s.id_producto_erp)
            WHERE d.id_lista_precio=:lista
            ORDER BY d.estatus='activo' DESC, d.id_sku IS NOT NULL DESC, d.id_lista_precio_detalle DESC
            LIMIT 300");
        $stmt->execute(array(":lista" => intval($idLista)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listarAsignaciones($db, $idLista) {
        if (!$this->tablaExiste($db, "erp_clientes_listas_precios")) {
            return array();
        }
        $tieneCrm = $this->columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm");
        $joinCrm = $tieneCrm
            ? "LEFT JOIN crm_clientes_maestro c ON c.id_cliente_crm=cl.id_cliente_crm"
            : "LEFT JOIN crm_clientes_maestro c ON c.id_cliente_crm=cl.id_cliente";
        $selectCrm = $tieneCrm ? "cl.id_cliente_crm" : "cl.id_cliente id_cliente_crm";
        $stmt = $db->prepare("SELECT cl.*, $selectCrm, c.codigo_cliente, c.nombre_publico, c.estatus estatus_cliente
            FROM erp_clientes_listas_precios cl
            $joinCrm
            WHERE cl.id_lista_precio=:lista
            ORDER BY cl.estatus='activo' DESC, cl.prioridad ASC, cl.id_cliente_lista_precio DESC
            LIMIT 200");
        $stmt->execute(array(":lista" => intval($idLista)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function detectarConflictosLista($db, $idLista) {
        $todos = $this->detectarConflictosInterno($db);
        return array_values(array_filter($todos, function ($item) use ($idLista) {
            return intval($this->valor($item, "id_lista_precio", 0)) === intval($idLista) || intval($this->valor($item, "id_lista_precio_2", 0)) === intval($idLista);
        }));
    }

    private function detectarConflictosInterno($db) {
        $conflictos = array();
        if (!$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle")) {
            $conflictos[] = array("tipo" => "schema", "severidad" => "alta", "mensaje" => "Faltan tablas base de listas de precios");
            return $conflictos;
        }

        $this->agregarConflictos($conflictos, $db, "lista_sin_detalle", "media", "SELECT l.id_lista_precio, l.codigo, l.nombre,
                'Lista activa sin detalles activos' mensaje
            FROM erp_listas_precios l
            WHERE l.estatus='activa'
              AND NOT EXISTS (SELECT 1 FROM erp_listas_precios_detalle d WHERE d.id_lista_precio=l.id_lista_precio AND d.estatus='activo')
            LIMIT 50");

        $this->agregarConflictos($conflictos, $db, "detalle_sin_alcance", "alta", "SELECT d.id_lista_precio, d.id_lista_precio_detalle,
                'Detalle activo sin SKU ni producto' mensaje
            FROM erp_listas_precios_detalle d
            WHERE d.estatus='activo' AND d.id_sku IS NULL AND d.id_producto_erp IS NULL
            LIMIT 50");

        $this->agregarConflictos($conflictos, $db, "precio_no_valido", "alta", "SELECT d.id_lista_precio, d.id_lista_precio_detalle, d.precio,
                'Detalle activo con precio menor o igual a cero' mensaje
            FROM erp_listas_precios_detalle d
            WHERE d.estatus='activo' AND d.precio<=0
            LIMIT 50");

        $this->agregarConflictos($conflictos, $db, "detalle_duplicado", "alta", "SELECT d.id_lista_precio, MIN(d.id_lista_precio_detalle) id_lista_precio_detalle,
                COALESCE(d.id_sku, 0) id_sku, COALESCE(d.id_producto_erp, 0) id_producto_erp, d.moneda, COUNT(*) repeticiones,
                'Detalle activo duplicado por lista/SKU/producto/moneda' mensaje
            FROM erp_listas_precios_detalle d
            WHERE d.estatus='activo'
            GROUP BY d.id_lista_precio, COALESCE(d.id_sku, 0), COALESCE(d.id_producto_erp, 0), d.moneda
            HAVING COUNT(*)>1
            LIMIT 50");

        if ($this->tablaExiste($db, "erp_clientes_listas_precios")) {
            if (!$this->columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
                $conflictos[] = array(
                    "tipo" => "schema_crm",
                    "severidad" => "media",
                    "mensaje" => "Falta id_cliente_crm en erp_clientes_listas_precios; el resolutor opera en compatibilidad temporal"
                );
            }
            $this->agregarConflictos($conflictos, $db, "asignacion_lista_inactiva", "media", "SELECT cl.id_lista_precio, cl.id_cliente_lista_precio,
                    'Asignacion activa apunta a lista no activa' mensaje
                FROM erp_clientes_listas_precios cl
                LEFT JOIN erp_listas_precios l ON l.id_lista_precio=cl.id_lista_precio
                WHERE cl.estatus='activo' AND (l.id_lista_precio IS NULL OR l.estatus<>'activa')
                LIMIT 50");
        }

        return $conflictos;
    }

    private function agregarConflictos(&$conflictos, $db, $tipo, $severidad, $sql) {
        $stmt = $db->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $fila["tipo"] = $tipo;
            $fila["severidad"] = $severidad;
            $conflictos[] = $fila;
        }
    }

    private function existeLista($db, $idLista) {
        if (!$this->tablaExiste($db, "erp_listas_precios") || intval($idLista) <= 0) {
            return false;
        }
        $stmt = $db->prepare("SELECT id_lista_precio FROM erp_listas_precios WHERE id_lista_precio=:id LIMIT 1");
        $stmt->execute(array(":id" => intval($idLista)));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function existeSku($db, $idSku) {
        if (!$this->tablaExiste($db, "erp_catalogo_skus") || intval($idSku) <= 0) {
            return false;
        }
        $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE id_sku=:id AND estatus='activo' LIMIT 1");
        $stmt->execute(array(":id" => intval($idSku)));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function existeProducto($db, $idProducto) {
        if (!$this->tablaExiste($db, "erp_catalogo_productos") || intval($idProducto) <= 0) {
            return false;
        }
        $stmt = $db->prepare("SELECT id_producto_erp FROM erp_catalogo_productos WHERE id_producto_erp=:id AND estatus='activo' LIMIT 1");
        $stmt->execute(array(":id" => intval($idProducto)));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function existeClienteCrm($db, $idClienteCrm) {
        if (!$this->tablaExiste($db, "crm_clientes_maestro") || intval($idClienteCrm) <= 0) {
            return false;
        }
        $stmt = $db->prepare("SELECT id_cliente_crm FROM crm_clientes_maestro WHERE id_cliente_crm=:id AND estatus='activo' LIMIT 1");
        $stmt->execute(array(":id" => intval($idClienteCrm)));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function buscarDetallesDuplicados($db, $idLista, $idDetalle, $idSku, $idProducto, $moneda) {
        if (!$this->tablaExiste($db, "erp_listas_precios_detalle") || intval($idLista) <= 0) {
            return array();
        }
        $whereAlcance = intval($idSku) > 0 ? "id_sku=:sku" : "COALESCE(id_producto_erp, 0)=:producto";
        $params = array(
            ":lista" => intval($idLista),
            ":detalle" => intval($idDetalle),
            ":moneda" => $moneda
        );
        if (intval($idSku) > 0) {
            $params[":sku"] = intval($idSku);
        } else {
            $params[":producto"] = intval($idProducto);
        }
        $stmt = $db->prepare("SELECT id_lista_precio_detalle, id_lista_precio, id_sku, id_producto_erp, precio, moneda
            FROM erp_listas_precios_detalle
            WHERE id_lista_precio=:lista
              AND id_lista_precio_detalle<>:detalle
              AND estatus='activo'
              AND $whereAlcance
              AND moneda=:moneda
            LIMIT 10");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarAsignacionesDuplicadas($db, $idAsignacion, $idLista, $idClienteCrm) {
        if (!$this->tablaExiste($db, "erp_clientes_listas_precios") || intval($idLista) <= 0 || intval($idClienteCrm) <= 0) {
            return array();
        }
        $tieneCrm = $this->columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm");
        $campoCliente = $tieneCrm ? "COALESCE(id_cliente_crm, id_cliente)" : "id_cliente";
        $stmt = $db->prepare("SELECT id_cliente_lista_precio, id_lista_precio, id_cliente, " . ($tieneCrm ? "id_cliente_crm," : "NULL id_cliente_crm,") . " prioridad, estatus
            FROM erp_clientes_listas_precios
            WHERE id_cliente_lista_precio<>:asignacion
              AND id_lista_precio=:lista
              AND $campoCliente=:cliente
              AND estatus='activo'
            LIMIT 10");
        $stmt->execute(array(
            ":asignacion" => intval($idAsignacion),
            ":lista" => intval($idLista),
            ":cliente" => intval($idClienteCrm)
        ));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obtenerFilaPorId($db, $tabla, $pk, $id) {
        if (!$this->tablaExiste($db, $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $pk) || intval($id) <= 0) {
            return null;
        }
        $stmt = $db->prepare("SELECT * FROM `$tabla` WHERE `$pk`=:id LIMIT 1");
        $stmt->execute(array(":id" => intval($id)));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function registrarEventoLista($db, $evento) {
        if (!$this->tablaExiste($db, "erp_listas_precios_eventos")) {
            throw new Exception("Falta tabla de auditoria erp_listas_precios_eventos");
        }
        $stmt = $db->prepare("INSERT INTO erp_listas_precios_eventos
            (id_lista_precio, id_lista_precio_detalle, id_cliente_lista_precio, entidad, entidad_id, accion,
             tipo_evento, resultado, resumen, motivo, datos_antes, datos_despues, origen, creado_por)
            VALUES (:lista, :detalle, :cliente_lista, :entidad, :entidad_id, :accion,
             :tipo_evento, :resultado, :resumen, :motivo, :datos_antes, :datos_despues, :origen, :usuario)");
        $stmt->execute(array(
            ":lista" => $this->valor($evento, "id_lista_precio", null),
            ":detalle" => $this->valor($evento, "id_lista_precio_detalle", null),
            ":cliente_lista" => $this->valor($evento, "id_cliente_lista_precio", null),
            ":entidad" => $this->valor($evento, "entidad", ""),
            ":entidad_id" => (string) $this->valor($evento, "entidad_id", ""),
            ":accion" => $this->valor($evento, "accion", ""),
            ":tipo_evento" => $this->valor($evento, "tipo_evento", "operacion"),
            ":resultado" => $this->valor($evento, "resultado", "ok"),
            ":resumen" => $this->valor($evento, "resumen", ""),
            ":motivo" => $this->valor($evento, "motivo", ""),
            ":datos_antes" => $this->jsonSeguro($this->valor($evento, "datos_antes", null)),
            ":datos_despues" => $this->jsonSeguro($this->valor($evento, "datos_despues", null)),
            ":origen" => $this->valor($evento, "origen", "erp_ventas_listas_precios"),
            ":usuario" => intval($this->valor($evento, "creado_por", 0))
        ));
    }

    private function jsonSeguro($valor) {
        if ($valor === null) {
            return null;
        }
        $json = json_encode($valor, JSON_UNESCAPED_UNICODE);
        return $json === false ? null : $json;
    }

    private function fechaValida($fecha) {
        $fecha = trim((string) $fecha);
        if ($fecha === "") {
            return true;
        }
        return strtotime($fecha) !== false;
    }

    private function kpisListas($db, $schema) {
        return array(
            "listas_activas" => $this->contar($db, "erp_listas_precios", "estatus='activa'"),
            "listas_total" => $this->contar($db, "erp_listas_precios", "1=1"),
            "detalles_activos" => $this->contar($db, "erp_listas_precios_detalle", "estatus='activo'"),
            "asignaciones_activas" => $schema["clientes_listas"] ? $this->contar($db, "erp_clientes_listas_precios", "estatus='activo'") : 0
        );
    }

    private function kpisVacios() {
        return array("listas_activas" => 0, "listas_total" => 0, "detalles_activos" => 0, "asignaciones_activas" => 0);
    }

    private function contar($db, $tabla, $where) {
        if (!$this->tablaExiste($db, $tabla)) {
            return 0;
        }
        return intval($db->query("SELECT COUNT(*) FROM `$tabla` WHERE $where")->fetchColumn());
    }

    private function schemaListas($db) {
        return array(
            "listas" => $this->tablaExiste($db, "erp_listas_precios"),
            "detalle" => $this->tablaExiste($db, "erp_listas_precios_detalle"),
            "clientes_listas" => $this->tablaExiste($db, "erp_clientes_listas_precios"),
            "cliente_crm_columna" => $this->columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm"),
            "catalogo_fallback" => $this->tablaExiste($db, "erp_catalogo_sku_precios")
        );
    }

    private function tablaExiste($db, $tabla) {
        if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
            return false;
        }
        $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function columnaExiste($db, $tabla, $columna) {
        if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
            return false;
        }
        $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function valor($datos, $clave, $default = null) {
        return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = null) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
