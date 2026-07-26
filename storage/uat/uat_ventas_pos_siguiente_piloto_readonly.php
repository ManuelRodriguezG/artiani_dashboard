<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: recomendar el siguiente ciclo piloto POS con el menor numero de acciones reales.
 * Impacto: prioriza SKU con stock y precio vigente, turno, evidencias y pendientes sin escribir BD.
 * Contrato: read-only; no abre turno, no cobra, no carga stock, no resuelve pendientes y no mueve inventario/caja.
 */

date_default_timezone_set("America/Mexico_City");

$args = parseArgs($argv);
$idUsuario = entero($args, "id_usuario", 1);
$idAlmacen = entero($args, "id_almacen", 5);
$idSkuPreferido = entero($args, "id_sku", 1760);
$cantidad = decimal($args, "cantidad", 1);
$montoInicial = decimal($args, "monto_inicial", 500);
$usuarios = texto($args, "usuarios", "1,2,3");
$cliente = texto($args, "cliente", "Cliente piloto POS");

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosSiguientePilotoDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosSiguientePilotoDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "ventas_pos_siguiente_piloto_readonly",
        "read_only" => true,
        "mensaje" => "Sin conexion BD; no se pudo evaluar siguiente piloto.",
        "contrato" => contrato(),
    ), 1);
}

$preferido = skuResumen($db, $idAlmacen, $idSkuPreferido);
$candidatos = candidatos($db, $idAlmacen, $cantidad);
$seleccion = seleccionarSku($preferido, $candidatos, $cantidad);
$turnos = turnosAbiertos($db, $idAlmacen);
$evidencias = evidenciasPendientes($db);
$pendientesPreferido = pendientesInventario($db, $idAlmacen, $idSkuPreferido);
$pendientesSeleccion = $seleccion ? pendientesInventario($db, $idAlmacen, (int) $seleccion["id_sku"]) : array();

$bloqueos = array();
$avisos = array();
if (!$seleccion) {
    $bloqueos[] = "No se encontro SKU con stock y precio vigente para piloto normal en almacen {$idAlmacen}.";
}
if (empty($turnos)) {
    $bloqueos[] = "No hay turno abierto; debe abrirse antes de cobrar.";
}
if ($seleccion && (float) $seleccion["precio"] <= 0) {
    $bloqueos[] = "El SKU recomendado no tiene precio vigente mayor a cero.";
}
if ($seleccion && (float) $seleccion["disponible"] < $cantidad) {
    $bloqueos[] = "El SKU recomendado no cubre la cantidad solicitada.";
}
if (!empty($pendientesPreferido)) {
    $avisos[] = "El SKU preferido {$idSkuPreferido} mantiene pendiente(s) de inventario POS; no usarlo para piloto limpio hasta resolverlos.";
}
if (!empty($evidencias)) {
    $avisos[] = "Hay evidencia(s) de caja pendientes; no bloquean una prueba controlada, pero deben cerrarse administrativamente.";
}

$precio = $seleccion ? (float) $seleccion["precio"] : 0.0;
$total = redondear($precio * $cantidad);
$acciones = array();
if (!empty($evidencias)) {
    $e = $evidencias[0];
    $ref = trim((string) $e["referencia"]) !== "" ? $e["referencia"] : ("MOV-" . $e["id_movimiento_caja"]);
    $acciones[] = accion("cerrar_evidencia_caja", "Caja", "Registrar/revisar evidencia administrativa pendiente {$ref}.", "AUTORIZO REGISTRAR EVIDENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario={$idUsuario} id_movimiento_caja={$e["id_movimiento_caja"]} tipo_evidencia=comprobante_caja referencia_externa={$ref} descripcion=\"Evidencia administrativa previa a piloto POS\"");
}
if (empty($turnos)) {
    $acciones[] = accion("abrir_turno", "Caja", "Abrir turno para ejecutar venta real desde POS.", "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario={$idUsuario} y monto_inicial={$montoInicial} observaciones=\"Apertura piloto POS con SKU recomendado\"");
}
if ($seleccion) {
    $acciones[] = accion("venta_piloto", "POS", "Cobrar venta piloto con SKU recomendado y stock disponible.", "AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_sku={$seleccion["id_sku"]} cantidad={$cantidad} precio={$precio} pago={$total} cliente=\"{$cliente}\"");
    $montoContado = redondear($montoInicial + $total);
    $acciones[] = accion("cerrar_turno", "Caja", "Cerrar turno con monto contado real; si no cuadra, dejar diferencia visible.", "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} monto_contado={$montoContado} observaciones=\"Cierre piloto POS SKU {$seleccion["id_sku"]}\"");
}

$decision = $seleccion
    ? (empty($turnos) ? "listo_para_piloto_al_abrir_turno" : "listo_para_venta_piloto")
    : "requiere_stock_o_precio_antes_de_piloto";

responder(array(
    "ok" => true,
    "modo" => "ventas_pos_siguiente_piloto_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku_preferido" => $idSkuPreferido,
        "cantidad" => $cantidad,
        "usuarios" => $usuarios,
    ),
    "decision" => $decision,
    "sku_preferido" => $preferido,
    "sku_recomendado" => $seleccion,
    "turnos_abiertos" => $turnos,
    "pendientes_inventario_sku_preferido" => $pendientesPreferido,
    "pendientes_inventario_sku_recomendado" => $pendientesSeleccion,
    "evidencias_caja_pendientes" => $evidencias,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "acciones_recomendadas" => $acciones,
    "checks_posteriores" => array(
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_operacion_basica_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku=" . ($seleccion ? $seleccion["id_sku"] : $idSkuPreferido) . " --usuarios={$usuarios} --compact=1",
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku=" . ($seleccion ? $seleccion["id_sku"] : $idSkuPreferido),
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12",
    ),
    "contrato" => contrato(),
));

function skuResumen($db, $idAlmacen, $idSku) {
    $stmt = $db->prepare("SELECT
            s.id_sku,
            s.sku,
            COALESCE(s.nombre, '') descripcion,
            COALESCE(pr.precio, 0) precio,
            COALESCE(SUM(e.cantidad_disponible), 0) disponible,
            COUNT(e.id_existencia_inventario) existencias
        FROM erp_catalogo_skus s
        LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
        LEFT JOIN erp_inventario_existencias e ON e.id_sku_erp=s.id_sku AND e.id_almacen_clave=:almacen AND e.estatus_existencia='disponible'
        WHERE s.id_sku=:sku
        GROUP BY s.id_sku, s.sku, s.nombre, pr.precio
        LIMIT 1");
    $stmt->execute(array(":almacen" => $idAlmacen, ":sku" => $idSku));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

function candidatos($db, $idAlmacen, $cantidad) {
    $stmt = $db->prepare("SELECT
            s.id_sku,
            s.sku,
            COALESCE(s.nombre, '') descripcion,
            COALESCE(pr.precio, 0) precio,
            SUM(e.cantidad_disponible) disponible,
            MIN(e.id_existencia_inventario) id_existencia_inventario
        FROM erp_inventario_existencias e
        INNER JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp AND s.estatus='activo'
        INNER JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo' AND pr.precio>0
        WHERE e.id_almacen_clave=:almacen
          AND e.estatus_existencia='disponible'
          AND e.cantidad_disponible>=:cantidad
        GROUP BY s.id_sku, s.sku, s.nombre, pr.precio
        ORDER BY SUM(e.cantidad_disponible) DESC, s.id_sku ASC
        LIMIT 10");
    $stmt->execute(array(":almacen" => $idAlmacen, ":cantidad" => $cantidad));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function seleccionarSku($preferido, $candidatos, $cantidad) {
    if ($preferido && (float) $preferido["disponible"] >= $cantidad && (float) $preferido["precio"] > 0) {
        $preferido["origen_recomendacion"] = "sku_preferido";
        return $preferido;
    }
    if (!empty($candidatos)) {
        $candidatos[0]["origen_recomendacion"] = "fallback_con_stock_precio";
        return $candidatos[0];
    }
    return null;
}

function turnosAbiertos($db, $idAlmacen) {
    $stmt = $db->prepare("SELECT id_turno_caja, folio, id_almacen, id_caja, id_usuario_apertura, monto_inicial, monto_esperado, fecha_apertura
        FROM erp_pos_turnos
        WHERE estatus='abierto' AND id_almacen=:almacen
        ORDER BY fecha_apertura DESC, id_turno_caja DESC");
    $stmt->execute(array(":almacen" => $idAlmacen));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function pendientesInventario($db, $idAlmacen, $idSku) {
    $stmt = $db->prepare("SELECT id_inventario_pendiente, folio, cantidad_pendiente, estatus
        FROM erp_pos_inventario_pendientes
        WHERE id_almacen=:almacen AND id_sku_erp=:sku AND estatus IN ('pendiente_revision','en_revision')
        ORDER BY id_inventario_pendiente DESC");
    $stmt->execute(array(":almacen" => $idAlmacen, ":sku" => $idSku));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function evidenciasPendientes($db) {
    $stmt = $db->query("SELECT id_movimiento_caja, id_turno_caja, tipo, categoria, motivo, monto, referencia, evidencia_estado
        FROM erp_pos_movimientos_caja
        WHERE requiere_evidencia=1 AND evidencia_estado IN ('pendiente','correccion_solicitada')
        ORDER BY id_movimiento_caja ASC
        LIMIT 5");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function accion($codigo, $area, $descripcion, $autorizacion) {
    return array(
        "codigo" => $codigo,
        "area" => $area,
        "descripcion" => $descripcion,
        "requiere_autorizacion_bd" => true,
        "autorizacion_sugerida" => $autorizacion,
    );
}

function parseArgs($argv) {
    $out = array();
    foreach (array_slice($argv ?: array(), 1) as $arg) {
        if (strpos($arg, "--") !== 0) {
            continue;
        }
        $partes = explode("=", substr($arg, 2), 2);
        $out[$partes[0]] = isset($partes[1]) ? trim($partes[1], "\"' ") : true;
    }
    return $out;
}

function entero($args, $clave, $default) {
    return isset($args[$clave]) ? (int) $args[$clave] : $default;
}

function decimal($args, $clave, $default) {
    return isset($args[$clave]) ? (float) $args[$clave] : $default;
}

function texto($args, $clave, $default) {
    return isset($args[$clave]) ? (string) $args[$clave] : $default;
}

function redondear($valor) {
    return rtrim(rtrim(number_format((float) $valor, 2, ".", ""), "0"), ".");
}

function contrato() {
    return array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_carga_stock" => true,
        "no_resuelve_inventario" => true,
        "no_registra_evidencias" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    );
}

function responder($respuesta, $exitCode = 0) {
    echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($exitCode);
}
