<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: consultar excepciones comerciales POS registradas sin modificar BD.
 * Impacto: permite verificar folio, politica, cliente, SKU, estatus y montos despues de una autorizacion real.
 * Contrato: read-only; no crea ni actualiza excepciones.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$limite = 5;
$esperarIdClienteCrm = 0;
$esperarTotal = null;
$esperarEstatus = "";
$ultimoAutorizado = false;
$idUsuario = 0;
$idSku = 0;
foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = max(1, min(50, intval(trim(substr($arg, 9), "\"' "))));
    } elseif (strpos($arg, "--esperar_id_cliente_crm=") === 0) {
        $esperarIdClienteCrm = intval(trim(substr($arg, 25), "\"' "));
    } elseif (strpos($arg, "--esperar_total=") === 0) {
        $esperarTotal = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--esperar_estatus=") === 0) {
        $esperarEstatus = trim(substr($arg, 18), "\"' ");
    } elseif (strpos($arg, "--ultimo_autorizado=") === 0) {
        $ultimoAutorizado = intval(trim(substr($arg, 20), "\"' ")) === 1;
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosExcepcionRegistroReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosExcepcionRegistroReadonlyDb())->db();
$params = array();
$wherePartes = array();
if ($folio !== "") {
    $wherePartes[] = "e.folio=:folio";
    $params[":folio"] = $folio;
}
if ($ultimoAutorizado) {
    $wherePartes[] = "e.estatus='autorizada'";
    $limite = 1;
}
if ($esperarIdClienteCrm > 0) {
    $wherePartes[] = "e.id_cliente_crm=:cliente_crm";
    $params[":cliente_crm"] = $esperarIdClienteCrm;
}
if ($idUsuario > 0) {
    $wherePartes[] = "(e.solicitado_por=:usuario OR e.autorizado_por=:usuario)";
    $params[":usuario"] = $idUsuario;
}
if ($idSku > 0) {
    $wherePartes[] = "e.id_sku_erp=:sku";
    $params[":sku"] = $idSku;
}
$where = count($wherePartes) ? "WHERE " . implode(" AND ", $wherePartes) : "";
$stmt = $db->prepare("SELECT e.id_excepcion_comercial, e.folio, e.tipo_excepcion, e.alcance, e.estatus,
        e.id_venta, e.id_venta_detalle, e.id_cliente, e.id_cliente_crm,
        e.cliente_codigo_snapshot, e.cliente_nombre_snapshot, e.cliente_identificador_snapshot, e.cliente_origen_snapshot,
        c.codigo_cliente, c.nombre_publico,
        crm.codigo_cliente crm_codigo_cliente, crm.nombre_publico crm_nombre_publico,
        e.id_sku_erp, s.sku, e.precio_base, e.precio_lista, e.precio_solicitado,
        e.precio_aplicado, e.descuento_total, e.subtotal_antes, e.total_despues,
        e.motivo, e.autorizacion_codigo, e.solicitado_por, e.autorizado_por,
        e.fecha_solicitud, e.fecha_autorizacion, p.codigo politica_codigo
    FROM erp_ventas_excepciones_comerciales e
    LEFT JOIN erp_ventas_politicas_comerciales p ON p.id_politica_comercial=e.id_politica_comercial
    LEFT JOIN erp_clientes c ON c.id_cliente=e.id_cliente
    LEFT JOIN crm_clientes_maestro crm ON crm.id_cliente_crm=e.id_cliente_crm
    LEFT JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
    $where
    ORDER BY e.id_excepcion_comercial DESC
    LIMIT " . intval($limite));
$stmt->execute($params);
$excepciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
$hallazgos = array();

if ($folio !== "" && count($excepciones) === 0) {
    $hallazgos[] = "No se encontro el folio solicitado";
}
if ($ultimoAutorizado && count($excepciones) === 0) {
    $hallazgos[] = "No se encontro excepcion autorizada pendiente con los filtros indicados";
}
foreach ($excepciones as $excepcion) {
    $prefijo = $excepcion["folio"] . ": ";
    if ($esperarEstatus !== "" && $excepcion["estatus"] !== $esperarEstatus) {
        $hallazgos[] = $prefijo . "estatus esperado " . $esperarEstatus . ", actual " . $excepcion["estatus"];
    }
    if ($esperarIdClienteCrm > 0 && intval($excepcion["id_cliente_crm"]) !== $esperarIdClienteCrm) {
        $hallazgos[] = $prefijo . "id_cliente_crm esperado " . $esperarIdClienteCrm . ", actual " . $excepcion["id_cliente_crm"];
    }
    if ($esperarIdClienteCrm > 0 && trim((string) $excepcion["cliente_codigo_snapshot"]) === "") {
        $hallazgos[] = $prefijo . "falta cliente_codigo_snapshot";
    }
    if ($esperarTotal !== null && abs(round(floatval($excepcion["total_despues"]), 6) - round($esperarTotal, 6)) > 0.0001) {
        $hallazgos[] = $prefijo . "total esperado " . $esperarTotal . ", actual " . $excepcion["total_despues"];
    }
    if ($excepcion["estatus"] === "autorizada" && (intval($excepcion["id_venta"]) > 0 || intval($excepcion["id_venta_detalle"]) > 0)) {
        $hallazgos[] = $prefijo . "estatus autorizada pero ya tiene venta o detalle ligado";
    }
}

echo json_encode(array(
    "ok" => empty($hallazgos),
    "modo" => "ventas_pos_excepcion_registro_readonly",
    "folio" => $folio,
    "ultimo_autorizado" => $ultimoAutorizado,
    "total_consultado" => count($excepciones),
    "excepciones" => $excepciones,
    "hallazgos" => $hallazgos,
    "siguiente_paso" => empty($hallazgos)
        ? "Si existe una excepcion autorizada, validarla contra carrito/pagos desde POS o dry-run de consumo."
        : "Revisar hallazgos antes de consumir la excepcion."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
