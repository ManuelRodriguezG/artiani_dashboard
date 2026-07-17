<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: consolidar un semaforo read-only para cierre funcional del modulo POS.
 * Impacto: ayuda a avanzar varias tareas a la vez sin escribir BD; revisa codigo, scripts UAT, asignacion, atenciones e inventario pendiente.
 * Contrato: solo lectura; no abre turno, no carga stock, no cobra, no cierra caja, no resuelve pendientes.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idAtencion = 2;
$idSku = 1760;
$compact = true;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--compact=") === 0) {
        $compact = intval(trim(substr($arg, 10), "\"' ")) === 1;
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/InventarioErp.php";

$ventas = new VentasErp();
$inventario = new InventarioErp();
$root = realpath(__DIR__ . "/../..");

$scriptsCriticos = array(
    "readiness_pos" => "storage/uat/uat_ventas_pos_readiness_readonly.php",
    "abrir_turno" => "storage/uat/uat_ventas_pos_turno_apertura_apply_authorized.php",
    "cargar_stock" => "storage/uat/uat_ventas_pos_stock_uat_apply_authorized.php",
    "cobrar_atencion" => "storage/uat/uat_ventas_pos_atencion_cobro_apply_authorized.php",
    "preflight_atencion" => "storage/uat/uat_ventas_pos_atencion_cobro_preflight_readonly.php",
    "bandeja_atenciones" => "storage/uat/uat_ventas_pos_atenciones_bandeja_readonly.php",
    "detalle_atencion" => "storage/uat/uat_ventas_pos_atencion_detalle_readonly.php",
    "ciclo_atencion_multiusuario" => "storage/uat/uat_ventas_pos_atencion_multiusuario_ciclo_apply_authorized.php",
    "cerrar_turno" => "storage/uat/uat_ventas_pos_turno_cierre_apply_authorized.php",
    "post_atencion_conversion" => "storage/uat/uat_ventas_pos_atencion_conversion_post_readonly.php",
    "post_venta" => "storage/uat/uat_ventas_pos_post_venta_readonly.php",
    "ticket" => "storage/uat/uat_ventas_pos_ticket_formal_readonly.php"
);

$scripts = array();
foreach ($scriptsCriticos as $clave => $relativo) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
    $scripts[$clave] = array(
        "ruta" => $relativo,
        "existe" => file_exists($ruta),
        "tamano" => file_exists($ruta) ? filesize($ruta) : null
    );
}

$metodos = array(
    "ventas" => array(
        "readinessPosReadOnly" => method_exists($ventas, "readinessPosReadOnly"),
        "asignacionActualTerminalPos" => method_exists($ventas, "asignacionActualTerminalPos"),
        "atencionesBandejaDryRun" => method_exists($ventas, "atencionesBandejaDryRun"),
        "atencionDetalleReadOnly" => method_exists($ventas, "atencionDetalleReadOnly"),
        "confirmarVentaPosDryRun" => method_exists($ventas, "confirmarVentaPosDryRun"),
        "confirmarVentaPosReal" => method_exists($ventas, "confirmarVentaPosReal"),
        "ticketVentaFormalReadOnly" => method_exists($ventas, "ticketVentaFormalReadOnly")
    ),
    "inventario" => array(
        "listarPendientesPosInventario" => method_exists($inventario, "listarPendientesPosInventario"),
        "resolucionPendientePosInventarioDryRun" => method_exists($inventario, "resolucionPendientePosInventarioDryRun")
    )
);

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$bandeja = $ventas->atencionesBandejaDryRun(array("id_almacen" => $idAlmacen));
$detalleAtencion = $idAtencion > 0 ? $ventas->atencionDetalleReadOnly(array("id_atencion" => $idAtencion, "id_almacen" => $idAlmacen)) : null;
$readiness = method_exists($ventas, "readinessPosReadOnly") ? $ventas->readinessPosReadOnly(array("id_usuario" => $idUsuario, "id_almacen" => $idAlmacen)) : null;
$pendientesInventario = method_exists($inventario, "listarPendientesPosInventario")
    ? $inventario->listarPendientesPosInventario(array("id_almacen" => $idAlmacen, "estatus" => "pendiente_revision"))
    : null;

$preflightAtencion = null;
$atencionConvertida = false;
if ($detalleAtencion && empty($detalleAtencion["error"])) {
    $depurarDetalle = valor($detalleAtencion, "depurar", array());
    $atencion = valor($depurarDetalle, "atencion", array());
    $atencionConvertida = valor($atencion, "estatus", "") === "convertida";
    $partidas = valor($depurarDetalle, "partidas", array());
    $depurarAsignacion = valor($asignacion, "depurar", array());
    $datosAsignacion = valor($depurarAsignacion, "asignacion", array());
    $turno = valor($depurarAsignacion, "turno_abierto", array());
    $items = array();
    foreach ($partidas as $partida) {
        $items[] = array(
            "id_sku" => intval(valor($partida, "id_sku", 0)),
            "cantidad" => floatval(valor($partida, "cantidad", 0)),
            "precio_unitario" => floatval(valor($partida, "precio_unitario", 0)),
            "modo_salida" => valor($partida, "modo_salida", "existencia_agregada")
        );
    }
    if (!$atencionConvertida) {
        $preflightAtencion = $ventas->confirmarVentaPosDryRun(array(
            "id_usuario" => $idUsuario,
            "id_atencion" => $idAtencion,
            "id_almacen" => intval(valor($atencion, "id_almacen", $idAlmacen)),
            "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
            "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
            "canal" => "pos",
            "tipo_documento" => "venta",
            "cliente_nombre_publico" => valor($atencion, "cliente_nombre_publico", ""),
            "identificador_cliente" => valor($atencion, "cliente_identificador_publico", ""),
            "items" => json_encode($items),
            "pagos" => json_encode(array(array("id_metodo_pago" => 1, "monto" => floatval(valor($atencion, "total", 0)), "referencia" => "READINESS-ATN-" . $idAtencion))),
            "exigir_pago_completo" => 1
        ));
    }
}

$bloqueos = array();
$avisos = array();
foreach ($scripts as $clave => $script) {
    if (empty($script["existe"])) {
        $bloqueos[] = "Falta script critico: " . $clave;
    }
}
if (!empty(valor($asignacion, "error", false))) {
    $bloqueos[] = "Asignacion POS no disponible para usuario";
}
$depurarAsignacion = valor($asignacion, "depurar", array());
if (!$atencionConvertida && empty($depurarAsignacion["turno_abierto"])) {
    $bloqueos[] = "No hay turno abierto para usuario/caja";
}
if (!$atencionConvertida) {
    foreach (valor(valor($preflightAtencion, "depurar", array()), "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = $bloqueo;
    }
} else {
    $avisos[] = "Atencion ya convertida; se omite preflight de cobro y turno abierto para este semaforo post-ciclo";
}$totalAtenciones = count(valor(valor($bandeja, "depurar", array()), "atenciones", array()));
if ($totalAtenciones <= 0) {
    $avisos[] = "No hay atenciones pendientes visibles en la bandeja";
}
$totalPendientesInv = intval(valor(valor($pendientesInventario, "depurar", array()), "total", 0));
if ($totalPendientesInv > 0) {
    $avisos[] = "Hay pendientes POS de inventario abiertos: " . $totalPendientesInv;
}

$bloqueos = array_values(array_unique(array_filter($bloqueos)));
$avisos = array_values(array_unique(array_filter($avisos)));

$siguientesAutorizaciones = $atencionConvertida
    ? array(
        "No repetir ciclo real sobre la misma atencion: ya esta convertida.",
        "Siguiente paso: ejecutar piloto operativo controlado desde UI con turno nuevo, venta normal y cierre de caja.",
        "Mantener fuera del primer piloto: inventario pendiente productivo, devoluciones reales, descuentos libres y apartados nuevos."
    )
    : array(
        'AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS cobro atencion multiusuario"',
        "AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-ATENCION-MULTI-1760",
        "AUTORIZO EJECUTAR UAT REAL COBRAR ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_TOMAR_COBRAR_REAL id_usuario=1 id_atencion=2 pago=295 para UAT POS",
        'AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS atencion multiusuario convertida"',
        "OPCION AGRUPADA: AUTORIZO EJECUTAR CICLO REAL ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL id_usuario=1 id_almacen=5 id_sku=1760 id_atencion=2 cantidad_stock=1 pago=295 monto_inicial=500 monto_contado=795 para UAT POS",
        "POST-READONLY esperado: C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_atencion_conversion_post_readonly.php --id_atencion=2 --compact=1"
    );
$salidaCompleta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_cierre_modulo_readiness_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_atencion" => $idAtencion,
        "id_sku" => $idSku
    ),
    "scripts" => $scripts,
    "metodos" => $metodos,
    "asignacion" => $asignacion,
    "readiness_pos" => $readiness,
    "atenciones_bandeja" => $bandeja,
    "atencion_detalle" => $detalleAtencion,
    "preflight_atencion" => $preflightAtencion,
    "ciclo_real_completo" => $atencionConvertida,
    "pendientes_inventario" => $pendientesInventario,
    "bloqueos_para_cobro_atencion" => $bloqueos,
    "avisos" => $avisos,
    "siguientes_autorizaciones_agrupadas" => $siguientesAutorizaciones
);

if (!$compact) {
    echo json_encode($salidaCompleta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}

$scriptsFaltantes = array();
foreach ($scripts as $clave => $script) {
    if (empty($script["existe"])) {
        $scriptsFaltantes[] = $clave;
    }
}
$metodosFaltantes = array();
foreach ($metodos as $grupo => $lista) {
    foreach ($lista as $metodo => $existe) {
        if (!$existe) {
            $metodosFaltantes[] = $grupo . "." . $metodo;
        }
    }
}
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());
$depurarDetalle = is_array($detalleAtencion) ? valor($detalleAtencion, "depurar", array()) : array();
$atencion = valor($depurarDetalle, "atencion", array());
$partidas = valor($depurarDetalle, "partidas", array());

$salidaCompacta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_cierre_modulo_readiness_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_atencion" => $idAtencion,
        "id_sku" => $idSku
    ),
    "scripts_faltantes" => $scriptsFaltantes,
    "metodos_faltantes" => $metodosFaltantes,
    "asignacion_pos" => array(
        "ok" => empty(valor($asignacion, "error", true)),
        "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
        "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
        "turno_abierto" => !empty($turno),
        "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
        "folio_turno" => valor($turno, "folio_turno", null)
    ),
    "atenciones" => array(
        "total_bandeja" => $totalAtenciones,
        "detalle_ok" => is_array($detalleAtencion) && empty(valor($detalleAtencion, "error", true)),
        "folio" => valor($atencion, "folio", null),
        "estatus" => valor($atencion, "estatus", null),
        "total" => floatval(valor($atencion, "total", 0)),
        "partidas" => count($partidas)
    ),
    "preflight_atencion" => array(
        "ok" => $atencionConvertida || (is_array($preflightAtencion) && empty(valor($preflightAtencion, "error", true))),
        "mensaje" => $atencionConvertida ? "Atencion ya convertida; no requiere preflight" : (is_array($preflightAtencion) ? valor($preflightAtencion, "mensaje", null) : null),
        "bloqueos" => valor(valor($preflightAtencion, "depurar", array()), "bloqueos", array())
    ),
    "pendientes_inventario_abiertos" => $totalPendientesInv,
    "bloqueos_para_cobro_atencion" => $bloqueos,
    "avisos" => $avisos,
    "siguientes_autorizaciones_agrupadas" => $siguientesAutorizaciones
);

echo json_encode($salidaCompacta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
