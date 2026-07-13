<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: validar contrato final del resolutor POS despues del UAT de listas.
 * Impacto: confirma precio, origen y snapshot sin crear ventas ni modificar listas.
 * Contrato: read-only; no crea clientes, listas, detalles, asignaciones ni ventas.
 */

$args = isset($argv) ? $argv : array();
$codigoLista = "LP-UAT-BORRADOR-01";
$idSku = 1760;
$idAlmacen = 5;
$idClienteCrm = 1;
$canal = "pos";
$precioEsperado = 315.00;
$esperarSinClienteTarget = false;

foreach ($args as $arg) {
    if (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--canal=") === 0) {
        $canal = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--precio_esperado=") === 0) {
        $precioEsperado = round(floatval(trim(substr($arg, 19), "\"' ")), 6);
    } elseif ($arg === "--esperar_sin_cliente_target=1") {
        $esperarSinClienteTarget = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/VentasErp.php";

class VentasErpListasPostUat extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventas = new VentasErpListasPostUat();
$db = $ventas->conexionUat();
$lista = buscarListaPorCodigo($db, $codigoLista);
$idLista = intval(valor($lista, "id_lista_precio", 0));
$detalle = $idLista > 0 ? buscarDetalleSku($db, $idLista, $idSku) : null;
$asignacion = $idLista > 0 ? buscarAsignacionCliente($db, $idLista, $idClienteCrm) : null;

$sinCliente = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => $canal,
    "id_cliente" => 0,
    "items" => json_encode(array(array("id_sku" => $idSku, "cantidad" => 1)))
));
$conCliente = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => $canal,
    "id_cliente" => $idClienteCrm,
    "items" => json_encode(array(array("id_sku" => $idSku, "cantidad" => 1)))
));

$partidaSinCliente = extraerPrimeraPartida($sinCliente);
$partidaConCliente = extraerPrimeraPartida($conCliente);
$bloqueos = array();

if (!$lista) {
    $bloqueos[] = "No existe la lista UAT objetivo";
} elseif (valor($lista, "estatus", "") !== "activa") {
    $bloqueos[] = "La lista UAT existe pero no esta activa";
}
if (!$detalle) {
    $bloqueos[] = "No existe detalle activo del SKU en la lista UAT";
} elseif (abs(floatval(valor($detalle, "precio", 0)) - $precioEsperado) > 0.0001) {
    $bloqueos[] = "El precio del detalle UAT no coincide con el esperado";
}
if (!$asignacion) {
    $bloqueos[] = "No existe asignacion activa cliente CRM/lista UAT";
}
if (!empty($conCliente["error"])) {
    $bloqueos[] = "Dry-run con cliente CRM devolvio error: " . valor($conCliente, "mensaje", "");
}
if (intval(valor($partidaConCliente, "id_lista_precio", 0)) !== $idLista) {
    $bloqueos[] = "Con cliente CRM no gano la lista UAT objetivo";
}
if (valor($partidaConCliente, "regla_precio_origen", "") !== "lista_cliente") {
    $bloqueos[] = "Con cliente CRM se esperaba regla lista_cliente";
}
$precioConCliente = valor($partidaConCliente, "precio_aplicado", valor($partidaConCliente, "precio_unitario", 0));
if (abs(floatval($precioConCliente) - $precioEsperado) > 0.0001) {
    $bloqueos[] = "Con cliente CRM no se obtuvo el precio esperado";
}
if (trim((string) valor($partidaConCliente, "lista_precio_snapshot", "")) === "") {
    $bloqueos[] = "Con cliente CRM falta lista_precio_snapshot";
}
if ($esperarSinClienteTarget && intval(valor($partidaSinCliente, "id_lista_precio", 0)) !== $idLista) {
    $bloqueos[] = "Sin cliente no gano la lista UAT objetivo";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_resolutor_post_uat_readonly",
    "read_only" => true,
    "entrada" => array(
        "codigo_lista" => $codigoLista,
        "id_lista_precio" => $idLista,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "id_cliente_crm" => $idClienteCrm,
        "canal" => $canal,
        "precio_esperado" => $precioEsperado,
        "esperar_sin_cliente_target" => $esperarSinClienteTarget
    ),
    "lista" => $lista,
    "detalle" => $detalle,
    "asignacion" => $asignacion,
    "sin_cliente" => $sinCliente,
    "con_cliente_crm" => $conCliente,
    "precio_con_cliente_detectado" => $precioConCliente,
    "bloqueos" => $bloqueos,
    "contrato" => array(
        "backend_resuelve_precio" => true,
        "cliente_crm_gana_sobre_canal" => true,
        "snapshot_requerido" => true,
        "no_escribe_bd" => true
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Resolver POS validado; pasar a venta UAT real solo con autorizacion operativa."
        : "Completar DDL, permisos, lista, detalle, asignacion y activacion antes de venta real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function buscarListaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT * FROM erp_listas_precios WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function buscarDetalleSku($db, $idLista, $idSku) {
    $stmt = $db->prepare("SELECT * FROM erp_listas_precios_detalle
        WHERE id_lista_precio=:lista AND id_sku=:sku AND estatus='activo'
        ORDER BY id_lista_precio_detalle DESC LIMIT 1");
    $stmt->execute(array(":lista" => intval($idLista), ":sku" => intval($idSku)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function buscarAsignacionCliente($db, $idLista, $idClienteCrm) {
    if (!columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
        return null;
    }
    $stmt = $db->prepare("SELECT * FROM erp_clientes_listas_precios
        WHERE id_lista_precio=:lista AND id_cliente_crm=:cliente AND estatus='activo'
        ORDER BY prioridad ASC, id_cliente_lista_precio DESC LIMIT 1");
    $stmt->execute(array(":lista" => intval($idLista), ":cliente" => intval($idClienteCrm)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function columnaExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetchColumn();
}

function extraerPrimeraPartida($respuesta) {
    if (!is_array($respuesta) || empty($respuesta["depurar"]["partidas"]) || !is_array($respuesta["depurar"]["partidas"])) {
        return array();
    }
    return $respuesta["depurar"]["partidas"][0];
}

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}
