<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: preparar una UAT robusta de excepcion comercial POS sin escribir BD.
 * Impacto: concentra prerrequisitos, folios propuestos, bloqueos y texto de autorizacion en un solo paquete.
 * Contrato: read-only; no abre turno, no carga stock, no registra excepcion, no vende y no cierra caja.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;
$precioLista = 295;
$precioManual = 285;
$montoInicial = 500;
$telefono = "5550000000";
$codigoAutorizacion = "SUP-UAT-002";
$motivo = "UAT UI precio manual autorizado";
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$referenciaSufijo = "EXC2";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio_lista=") === 0) {
        $precioLista = floatval(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--precio_manual=") === 0) {
        $precioManual = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--telefono=") === 0) {
        $telefono = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--codigo_autorizacion=") === 0) {
        $codigoAutorizacion = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--referencia_sufijo=") === 0) {
        $referenciaSufijo = trim(substr($arg, 20), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class VentasPosUatRobustaPreflightDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new VentasPosUatRobustaPreflightDb())->db();
$ventas = new VentasErp();
$fecha = date("Ymd");
$referenciaSufijo = preg_replace('/[^A-Za-z0-9_-]/', '', $referenciaSufijo);
if ($referenciaSufijo === "") {
    $referenciaSufijo = "EXC2";
}
$referenciaStock = "INV-INICIAL-POS-UAT-" . $fecha . "-A" . $idAlmacen . "-S" . $idSku . "-" . $referenciaSufijo;
$bloqueos = array();
$avisos = array();

$respaldoOk = validarRespaldo($respaldo);
if (!$respaldoOk["ok"]) {
    $bloqueos[] = "Respaldo externo no valido o no legible";
}

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, array("depurar"), array());
$asignacionActiva = !empty($depurarAsignacion["asignacion_activa"]);
$datosAsignacion = valor($depurarAsignacion, array("asignacion"), array());
$turnoAbierto = valor($depurarAsignacion, array("turno_abierto"), null);
foreach (valor($depurarAsignacion, array("bloqueos"), array()) as $bloqueo) {
    if ($bloqueo !== "No hay turno abierto para esta caja") {
        $bloqueos[] = $bloqueo;
    }
}
if (!$asignacionActiva) {
    $bloqueos[] = "Usuario sin asignacion POS activa";
}
if ($turnoAbierto) {
    $bloqueos[] = "Hay un turno abierto; cerrar antes de iniciar UAT robusta nueva";
}

$sku = consultarSku($db, $idSku);
if (!$sku) {
    $bloqueos[] = "SKU no encontrado o inactivo";
} else {
    $precioCatalogo = round(floatval($sku["precio"]), 6);
    if (abs($precioCatalogo - $precioLista) > 0.0001) {
        $avisos[] = "El precio lista esperado " . $precioLista . " difiere del precio catalogo " . $precioCatalogo;
    }
}
if ($precioManual <= 0 || $precioManual >= $precioLista) {
    $bloqueos[] = "Precio manual debe ser mayor a cero y menor al precio lista para esta UAT";
}

$stockRefUsada = referenciaUsada($db, $referenciaStock);
if ($stockRefUsada) {
    $bloqueos[] = "Referencia de stock propuesta ya fue usada: " . $referenciaStock;
}

$itemsExcepcion = json_encode(array(array(
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "modo_salida" => "existencia_agregada"
)));
$dryRunExcepcion = $ventas->excepcionComercialDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $telefono,
    "tipo_excepcion" => "precio_manual",
    "id_sku" => $idSku,
    "precio_manual" => $precioManual,
    "motivo" => $motivo,
    "codigo_autorizacion" => $codigoAutorizacion,
    "items" => $itemsExcepcion,
    "id_usuario" => $idUsuario,
    "autorizado_por" => $idUsuario
));
foreach (valor($dryRunExcepcion, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}

$tienePermiso = usuarioTienePermiso($db, $idUsuario, "ventas.autorizar_excepcion_comercial");
if (!$tienePermiso) {
    $bloqueos[] = "Usuario sin permiso ventas.autorizar_excepcion_comercial";
}
$politica = consultarPolitica($db, "precio_manual", "pos", $idAlmacen);
if (!$politica) {
    $bloqueos[] = "No hay politica activa de precio manual POS";
}

$totales = valor($dryRunExcepcion, array("depurar", "totales"), array());
$totalEsperado = round(floatval(valor($totales, array("total_estimado"), $precioManual * $cantidad)), 6);
$montoContadoCierre = round($montoInicial + $totalEsperado, 6);
$descuento = round(floatval(valor($totales, array("descuento_total_estimado"), ($precioLista - $precioManual) * $cantidad)), 6);

$comandos = array(
    "abrir_turno" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=\"" . $respaldo . "\" --id_usuario=" . $idUsuario . " --monto_inicial=" . $montoInicial,
    "cargar_stock" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_stock_uat_apply_authorized.php --autorizar=VENTAS_POS_STOCK_UAT --respaldo=\"" . $respaldo . "\" --id_usuario=" . $idUsuario . " --id_almacen=" . $idAlmacen . " --id_sku=" . $idSku . " --cantidad=" . $cantidad . " --referencia=" . $referenciaStock,
    "crear_excepcion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_excepcion_registro_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_REAL --respaldo=\"" . $respaldo . "\" --id_usuario=" . $idUsuario . " --id_almacen=" . $idAlmacen . " --id_sku=" . $idSku . " --identificador=" . $telefono . " --tipo=precio_manual --precio_manual=" . $precioManual . " --motivo=\"" . $motivo . "\" --codigo_autorizacion=" . $codigoAutorizacion,
    "venta_real" => "La venta real debe usar el folio EXC nuevo que devuelva el paso crear_excepcion.",
    "cierre_turno" => "Cierre con monto_contado=" . $montoContadoCierre . " despues de confirmar venta real."
);

echo json_encode(array(
    "ok" => empty(array_unique($bloqueos)),
    "modo" => "ventas_pos_excepcion_uat_robusta_preflight_readonly",
    "read_only" => true,
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => intval(valor($datosAsignacion, array("id_caja"), 0)),
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_lista" => $precioLista,
        "precio_manual" => $precioManual,
        "descuento_estimado" => $descuento,
        "total_venta_esperado" => $totalEsperado,
        "monto_inicial" => $montoInicial,
        "monto_contado_cierre_esperado" => $montoContadoCierre,
        "referencia_stock" => $referenciaStock
    ),
    "respaldo" => $respaldoOk,
    "asignacion" => $datosAsignacion,
    "turno_abierto_actual" => $turnoAbierto,
    "sku" => $sku,
    "politica" => $politica,
    "autorizador_tiene_permiso" => $tienePermiso,
    "dry_run_excepcion" => $dryRunExcepcion,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "comandos_planificados" => $comandos,
    "autorizacion_robusta_sugerida" => "AUTORIZO EJECUTAR UAT ROBUSTA POS EXCEPCION COMERCIAL usando respaldo " . $respaldo . " con id_usuario=" . $idUsuario . " id_almacen=" . $idAlmacen . " id_sku=" . $idSku . " cantidad=" . $cantidad . " precio_manual=" . $precioManual . " pago=" . $totalEsperado . " monto_inicial=" . $montoInicial . " monto_contado=" . $montoContadoCierre . " telefono=" . $telefono . " codigo_autorizacion=" . $codigoAutorizacion
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function validarRespaldo($respaldo) {
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    return array(
        "ok" => trim((string) $respaldo) !== "" && (!$pareceRuta || ($existe && is_readable($respaldo))),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $existe,
        "archivo_legible" => $existe ? is_readable($respaldo) : false
    );
}

function consultarSku($db, $idSku) {
    $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku, COALESCE(s.nombre, p.nombre) nombre,
            COALESCE(pr.precio, 0) precio, COALESCE(r.controla_inventario, 1) controla_inventario
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
        LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku
            AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
        WHERE s.id_sku=:sku AND s.estatus='activo' AND p.estatus='activo'
        LIMIT 1");
    $stmt->execute(array(":sku" => intval($idSku)));
    $sku = $stmt->fetch(PDO::FETCH_ASSOC);
    return $sku ?: null;
}

function referenciaUsada($db, $referencia) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=:referencia");
    $stmt->execute(array(":referencia" => $referencia));
    return intval($stmt->fetchColumn()) > 0;
}

function usuarioTienePermiso($db, $idUsuario, $permiso) {
    $stmt = $db->prepare("SELECT COUNT(*)
        FROM sys_usuarios_roles ur
        INNER JOIN sys_roles r ON r.id_rol=ur.id_rol AND r.estatus=1
        INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
        INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso AND p.estatus=1
        WHERE ur.id_usuario=:usuario AND ur.estatus=1 AND p.permiso=:permiso");
    $stmt->execute(array(":usuario" => intval($idUsuario), ":permiso" => $permiso));
    return intval($stmt->fetchColumn()) > 0;
}

function consultarPolitica($db, $tipo, $canal, $idAlmacen) {
    $stmt = $db->prepare("SELECT id_politica_comercial, codigo, nombre, tipo_excepcion, permiso_requerido, estatus
        FROM erp_ventas_politicas_comerciales
        WHERE tipo_excepcion=:tipo AND estatus='activa'
          AND (canal IS NULL OR canal='' OR canal=:canal)
          AND (id_almacen IS NULL OR id_almacen=0 OR id_almacen=:almacen)
        ORDER BY id_almacen DESC, id_politica_comercial DESC
        LIMIT 1");
    $stmt->execute(array(":tipo" => $tipo, ":canal" => $canal, ":almacen" => intval($idAlmacen)));
    $politica = $stmt->fetch(PDO::FETCH_ASSOC);
    return $politica ?: null;
}

function valor($origen, $ruta, $default = null) {
    $actual = $origen;
    foreach ($ruta as $clave) {
        if (!is_array($actual) || !array_key_exists($clave, $actual)) {
            return $default;
        }
        $actual = $actual[$clave];
    }
    return $actual;
}
