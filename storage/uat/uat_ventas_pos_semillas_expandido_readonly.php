<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: proponer semillas UAT para clientes, listas de precios y politicas POS sin escribir BD.
 * Impacto: prepara pruebas de cliente rapido, lista especial y apartado despues del DDL expandido.
 * Contrato: read-only; no inserta clientes, precios, politicas, ventas ni atenciones.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$precio = 295;
$telefono = "5550000000";
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = round(floatval(trim(substr($arg, 9), "\"' ")), 6);
    } elseif (strpos($arg, "--telefono=") === 0) {
        $telefono = trim(substr($arg, 11), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosSemillasExpandidoDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosSemillasExpandidoDb())->db();
$tablas = array(
    "erp_ventas_politicas_apartado",
    "erp_clientes",
    "erp_clientes_identificadores",
    "erp_listas_precios",
    "erp_listas_precios_detalle",
    "erp_clientes_listas_precios"
);
$faltantes = tablasFaltantes($db, $tablas);

$normalizado = normalizarIdentificadorCliente($telefono);
$sku = consultarSku($db, $idSku);
$conteos = array();
foreach ($tablas as $tabla) {
    $conteos[$tabla] = empty($faltantes) || !in_array($tabla, $faltantes, true) ? contar($db, $tabla) : null;
}
$yaSembrado = empty($faltantes);
foreach ($conteos as $conteo) {
    if (intval($conteo) <= 0) {
        $yaSembrado = false;
    }
}

$semillas = array(
    "politica_apartado" => array(
        "codigo" => "POS_APARTADO_UAT",
        "nombre" => "Politica apartado UAT",
        "porcentaje_anticipo_minimo" => 0.2,
        "dias_vigencia" => 30
    ),
    "cliente" => array(
        "codigo_cliente" => "CL-UAT-POS-001",
        "nombre_publico" => "Cliente UAT POS",
        "identificador_tipo" => "telefono",
        "identificador_valor" => $telefono,
        "identificador_normalizado" => $normalizado
    ),
    "lista_precio" => array(
        "codigo" => "LP-UAT-POS",
        "nombre" => "Lista UAT POS",
        "canal" => "pos",
        "id_almacen" => $idAlmacen,
        "prioridad" => 10
    ),
    "lista_precio_detalle" => array(
        "id_sku" => $idSku,
        "sku" => isset($sku["sku"]) ? $sku["sku"] : null,
        "precio" => $precio,
        "moneda" => "MXN"
    )
);

$bloqueos = array();
if (!empty($faltantes)) {
    $bloqueos[] = "Falta DDL expandido: " . implode(", ", $faltantes);
}
if (!$sku) {
    $bloqueos[] = "SKU UAT no encontrado: " . $idSku;
}
if ($idUsuario <= 0) {
    $bloqueos[] = "id_usuario requerido para semilla autorizada";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "conteos_actuales" => $conteos,
    "semillas_propuestas" => $semillas,
    "bloqueos" => $bloqueos,
    "comando_autorizado_futuro" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_semillas_expandido_apply_authorized.php --autorizar=VENTAS_POS_SEED_EXPANDIDO --respaldo=\"C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql\" --id_usuario=" . $idUsuario . " --id_almacen=" . $idAlmacen . " --id_sku=" . $idSku . " --precio=" . $precio . " --telefono=" . $telefono,
    "siguiente_paso" => $yaSembrado
        ? "Semillas UAT expandidas presentes; validar cliente/precio, atencion persistente y abonos."
        : (empty($bloqueos) ? "Solicitar autorizacion para sembrar datos UAT expandidos." : "Resolver bloqueos antes de solicitar autorizacion.")
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function tablasFaltantes($db, $tablas) {
    $faltantes = array();
    foreach ($tablas as $tabla) {
        $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
        $stmt->execute(array(":tabla" => $tabla));
        if (!$stmt->fetchColumn()) {
            $faltantes[] = $tabla;
        }
    }
    return $faltantes;
}

function contar($db, $tabla) {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`");
    return intval($stmt->fetchColumn());
}

function consultarSku($db, $idSku) {
    $stmt = $db->prepare("SELECT id_sku, sku FROM erp_catalogo_skus WHERE id_sku=:sku LIMIT 1");
    $stmt->execute(array(":sku" => $idSku));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function normalizarIdentificadorCliente($valor) {
    $soloDigitos = preg_replace('/\D+/', '', (string) $valor);
    return $soloDigitos !== "" ? $soloDigitos : strtolower(trim((string) $valor));
}
