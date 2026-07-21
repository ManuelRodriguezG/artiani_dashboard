<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: preparar paquete de autorizacion para piloto POS real sin ejecutar escrituras.
 * Impacto: lista scripts apply_authorized, parametros requeridos, orden de ejecucion y postchecks.
 * Contrato: read-only; no invoca aplicadores, no escribe BD, no abre turno, no cobra y no mueve inventario.
 */

date_default_timezone_set("America/Mexico_City");

$args = parseArgs($argv);
$idUsuario = entero($args, "id_usuario", 1);
$idAlmacen = entero($args, "id_almacen", 5);
$idSku = entero($args, "id_sku", 1760);
$cantidad = decimal($args, "cantidad", 1);
$precio = decimal($args, "precio", 295);
$montoInicial = decimal($args, "monto_inicial", 500);
$cliente = texto($args, "cliente", "Cliente piloto POS");
$folioPendiente = texto($args, "folio_pendiente", "PINV-20260717-000001");
$conteoFisico = texto($args, "cantidad_fisica", "CONTEO_REAL");
$montoContado = texto($args, "monto_contado", "MONTO_CONTADO_REAL");
$idMovimientoCaja = entero($args, "id_movimiento_caja", 5);
$referenciaEvidencia = texto($args, "referencia_evidencia", "GASTO-UAT-001");
$respaldoTecnico = texto($args, "respaldo", "RUTA_RESPALDO_UAT_POS_VIGENTE.sql");
$fechaReferencia = texto($args, "fecha", "20260720");

$scripts = array(
    "resolver_pendiente" => "storage\\uat\\uat_inventario_pos_pendiente_resolver_apply_authorized.php",
    "registrar_evidencia_caja" => "storage\\uat\\uat_ventas_pos_caja_evidencia_apply_authorized.php",
    "cargar_stock" => "storage\\uat\\uat_ventas_pos_stock_uat_apply_authorized.php",
    "abrir_turno" => "storage\\uat\\uat_ventas_pos_turno_apertura_apply_authorized.php",
    "venta" => "storage\\uat\\uat_ventas_pos_venta_apply_authorized.php",
    "cerrar_turno" => "storage\\uat\\uat_ventas_pos_turno_cierre_apply_authorized.php",
);

$root = dirname(__DIR__, 2);
$checksScripts = array();
foreach ($scripts as $clave => $ruta) {
    $checksScripts[$clave] = array(
        "ruta" => $ruta,
        "existe" => is_file($root . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $ruta)),
    );
}

$referenciaStock = "INV-INICIAL-POS-PILOTO-{$fechaReferencia}-A{$idAlmacen}-S{$idSku}";
$totalVenta = redondear($cantidad * $precio);

$pasos = array(
    array(
        "orden" => 1,
        "codigo" => "resolver_pendiente_inventario",
        "obligatorio_para_piloto_amplio" => true,
        "puede_posponerse_para_piloto_controlado" => true,
        "autorizacion_humana" => "AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario={$idUsuario} folio={$folioPendiente} cantidad_fisica={$conteoFisico} decision=ajustar_a_conteo confirmacion=\"RESOLVER PENDIENTE\" motivo=\"Resolver mini inventario POS pendiente antes de piloto\"",
        "comando_tecnico" => "C:\\xampp\\php\\php.exe {$scripts["resolver_pendiente"]} --autorizar=INVENTARIO_POS_PENDIENTE_RESOLVER_REAL --respaldo=\"{$respaldoTecnico}\" --id_usuario={$idUsuario} --folio={$folioPendiente} --cantidad_fisica={$conteoFisico} --decision=ajustar_a_conteo --confirmacion=\"RESOLVER PENDIENTE\" --motivo=\"Resolver mini inventario POS pendiente antes de piloto\"",
    ),
    array(
        "orden" => 2,
        "codigo" => "registrar_evidencia_caja",
        "obligatorio_para_piloto_amplio" => true,
        "puede_posponerse_para_piloto_controlado" => true,
        "autorizacion_humana" => "AUTORIZO REGISTRAR EVIDENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario={$idUsuario} id_movimiento_caja={$idMovimientoCaja} tipo_evidencia=comprobante_caja referencia_externa={$referenciaEvidencia} descripcion=\"Evidencia administrativa previa a piloto POS\"",
        "comando_tecnico" => "C:\\xampp\\php\\php.exe {$scripts["registrar_evidencia_caja"]} --autorizar=VENTAS_POS_CAJA_EVIDENCIA_REAL --respaldo=\"{$respaldoTecnico}\" --id_usuario={$idUsuario} --id_movimiento_caja={$idMovimientoCaja} --tipo_evidencia=comprobante_caja --referencia_externa={$referenciaEvidencia} --descripcion=\"Evidencia administrativa previa a piloto POS\"",
    ),
    array(
        "orden" => 3,
        "codigo" => "cargar_stock",
        "obligatorio_para_piloto_amplio" => true,
        "puede_posponerse_para_piloto_controlado" => false,
        "autorizacion_humana" => "AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_almacen={$idAlmacen} id_sku={$idSku} cantidad={$cantidad} referencia={$referenciaStock}",
        "comando_tecnico" => "C:\\xampp\\php\\php.exe {$scripts["cargar_stock"]} --autorizar=VENTAS_POS_STOCK_UAT --respaldo=\"{$respaldoTecnico}\" --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSku} --cantidad={$cantidad} --referencia={$referenciaStock}",
    ),
    array(
        "orden" => 4,
        "codigo" => "abrir_turno",
        "obligatorio_para_piloto_amplio" => true,
        "puede_posponerse_para_piloto_controlado" => false,
        "autorizacion_humana" => "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario={$idUsuario} y monto_inicial={$montoInicial} observaciones=\"Apertura piloto POS\"",
        "comando_tecnico" => "C:\\xampp\\php\\php.exe {$scripts["abrir_turno"]} --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=\"{$respaldoTecnico}\" --id_usuario={$idUsuario} --monto_inicial={$montoInicial} --observaciones=\"Apertura piloto POS\"",
    ),
    array(
        "orden" => 5,
        "codigo" => "venta_piloto",
        "obligatorio_para_piloto_amplio" => true,
        "puede_posponerse_para_piloto_controlado" => false,
        "autorizacion_humana" => "AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_sku={$idSku} cantidad={$cantidad} precio={$precio} pago={$totalVenta} cliente=\"{$cliente}\"",
        "comando_tecnico" => "C:\\xampp\\php\\php.exe {$scripts["venta"]} --autorizar=VENTAS_POS_VENTA_REAL --respaldo=\"{$respaldoTecnico}\" --id_usuario={$idUsuario} --id_sku={$idSku} --cantidad={$cantidad} --precio={$precio} --pago={$totalVenta} --cliente=\"{$cliente}\"",
    ),
    array(
        "orden" => 6,
        "codigo" => "cerrar_turno",
        "obligatorio_para_piloto_amplio" => true,
        "puede_posponerse_para_piloto_controlado" => false,
        "autorizacion_humana" => "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} monto_contado={$montoContado} observaciones=\"Cierre piloto POS\"",
        "comando_tecnico" => "C:\\xampp\\php\\php.exe {$scripts["cerrar_turno"]} --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=\"{$respaldoTecnico}\" --id_usuario={$idUsuario} --monto_contado={$montoContado} --observaciones=\"Cierre piloto POS\"",
    ),
);

$bloqueos = array();
foreach ($checksScripts as $clave => $check) {
    if (!$check["existe"]) {
        $bloqueos[] = "Falta script " . $check["ruta"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_paquete_autorizacion_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "resumen" => array(
        "pasos_total" => count($pasos),
        "bloqueos_total" => count($bloqueos),
        "decision" => empty($bloqueos) ? "paquete_autorizacion_preparado" : "paquete_requiere_correccion",
        "nota_respaldo" => "La autorizacion humana puede decir UAT POS vigente; el comando tecnico requiere ruta real del respaldo si se ejecuta por CLI.",
    ),
    "bloqueos" => $bloqueos,
    "scripts" => $checksScripts,
    "pasos" => $pasos,
    "postchecks" => array(
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen={$idAlmacen} --id_sku={$idSku} --usuarios=1,2,3",
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1",
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSku}",
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_invoca_apply_authorized" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_mueve_inventario" => true,
        "no_mueve_caja" => true,
    ),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function parseArgs($argv) {
    $out = array();
    foreach (array_slice($argv, 1) as $arg) {
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
