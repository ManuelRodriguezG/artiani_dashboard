<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: consolidar semaforos read-only del cierre operativo POS.
 * Impacto: permite revisar MySQL, piloto, UI, caja, ticket, reportes, CRM, listas y reversas sin ejecutar acciones reales.
 * Contrato: read-only; no abre turnos, no cobra, no crea devoluciones, no ajusta inventario y no escribe BD.
 */

date_default_timezone_set("America/Mexico_City");

$root = dirname(__DIR__, 2);
$php = "C:\\xampp\\php\\php.exe";
$args = parseArgs($argv);
$compacto = !empty($args["compact"]);
$scripts = array(
    "mysql_health" => "storage\\uat\\uat_ventas_pos_mysql_health_readonly.php",
    "preflight_piloto" => "storage\\uat\\uat_ventas_pos_piloto_preflight_compacto_readonly.php",
    "salida_operativa" => "storage\\uat\\uat_ventas_pos_salida_operativa_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --id_atencion=2 --cantidad=1 --usuarios=1,2,3 --compact=1",
    "postcheck_piloto" => "storage\\uat\\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760",
    "navegacion" => "storage\\uat\\uat_ventas_pos_navegacion_readiness_readonly.php",
    "atajos" => "storage\\uat\\uat_ventas_pos_atajos_ui_readiness_readonly.php",
    "ux_operativa" => "storage\\uat\\uat_ventas_pos_ux_operativa_readiness_readonly.php",
    "escaner" => "storage\\uat\\uat_ventas_pos_escaner_ui_readiness_readonly.php",
    "impresion" => "storage\\uat\\uat_ventas_pos_impresion_readiness_readonly.php",
    "caja_turnos" => "storage\\uat\\uat_ventas_pos_caja_turnos_ui_readiness_readonly.php",
    "reportes" => "storage\\uat\\uat_ventas_pos_reportes_piloto_readiness_readonly.php",
    "productivo" => "storage\\uat\\uat_ventas_pos_productivo_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760",
    "inventario_sku" => "storage\\uat\\uat_ventas_pos_inventario_sku_readonly.php --id_almacen=5 --id_sku=1760 --cantidad=1",
    "pendientes_piloto" => "storage\\uat\\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=1760 --usuarios=1,2,3",
    "plan_accion_piloto" => "storage\\uat\\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --usuarios=1,2,3",
    "paquete_autorizacion_piloto" => "storage\\uat\\uat_ventas_pos_piloto_paquete_autorizacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --cantidad_fisica=CONTEO_REAL --monto_contado=MONTO_CONTADO_REAL",
    "salida_operacion_doc" => "storage\\uat\\uat_ventas_pos_salida_operacion_doc_readonly.php",
    "pedidos_apartados" => "storage\\uat\\uat_ventas_pos_pedidos_apartados_readonly.php",
    "reversa_saldo_favor" => "storage\\uat\\uat_ventas_pos_reversa_readiness_readonly.php --folio=POS-20260717-000001 --id_venta_detalle=26 --cantidad=1 --decision_financiera=saldo_favor --decision_inventario=cuarentena",
    "ticket_venta" => "storage\\uat\\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260717-000001",
    "ticket_devolucion" => "storage\\uat\\uat_ventas_pos_ticket_devolucion_readonly.php --folio=DEV-20260707-000001",
    "crm_contrato" => "storage\\uat\\uat_ventas_pos_crm_contrato_readonly.php",
    "listas_resolutor" => "storage\\uat\\uat_listas_precios_resolutor_conexion_guard_readonly.php",
    "listas_ui" => "storage\\uat\\uat_listas_precios_segmentos_ui_readiness_readonly.php",
    "encoding_bom" => "storage\\uat\\uat_ventas_pos_encoding_bom_readonly.php",
    "guardrails" => "storage\\uat\\uat_ventas_pos_guardrails_readonly.php",
);

$resultados = array();
$bloqueos = array();
$avisos = array();

foreach ($scripts as $clave => $script) {
    $resultado = ejecutarScript($php, $root, $script);
    $resultados[$clave] = $resultado;
    if (!$resultado["ok"]) {
        $bloqueos[] = $clave . ": " . $resultado["mensaje"];
    }
    foreach ($resultado["avisos"] as $aviso) {
        $avisos[] = $clave . ": " . $aviso;
    }
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_cierre_ampliado_readonly",
    "read_only" => true,
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "resumen" => array(
        "scripts_total" => count($scripts),
        "bloqueos_total" => count($bloqueos),
        "avisos_total" => count($avisos),
        "decision" => empty($bloqueos) ? "pos_apto_para_piloto_controlado_con_condiciones" : "pos_requiere_atencion_previa",
    ),
    "bloqueos" => $bloqueos,
    "avisos" => array_values(array_unique($avisos)),
    "resultados" => $compacto ? resumenResultados($resultados) : $resultados,
    "condiciones_vigentes" => array(
        "Abrir turno antes de cobrar.",
        "Usar stock disponible o resolver/cargar inventario con autorizacion.",
        "Mantener identificado o resolver PINV-20260717-000001.",
        "Cerrar administrativamente GASTO-UAT-001.",
        "No usar devoluciones reales ni inventario pendiente como operacion cotidiana durante primer piloto."
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_crea_pedido" => true,
        "no_crea_devolucion" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);

function ejecutarScript($php, $root, $script)
{
    $cmd = '"' . $php . '" ' . $script;
    $cwd = getcwd();
    chdir($root);
    $output = array();
    $exitCode = 1;
    exec($cmd . " 2>&1", $output, $exitCode);
    chdir($cwd);

    $texto = trim(implode("\n", $output));
    $json = json_decode($texto, true);
    $okJson = is_array($json);
    $avisos = array();
    if ($okJson) {
        $avisos = extraerAvisos($json);
    }

    return array(
        "ok" => $exitCode === 0 && $okJson && !empty($json["ok"]),
        "exit_code" => $exitCode,
        "script" => $script,
        "mensaje" => $okJson ? resumenMensaje($json) : "Salida no JSON",
        "avisos" => $avisos,
        "json" => $okJson ? resumenJson($json) : null,
        "salida_no_json" => $okJson ? "" : $texto,
    );
}

function resumenMensaje($json)
{
    if (isset($json["decision"])) {
        return (string) $json["decision"];
    }
    if (isset($json["resultado"])) {
        return (string) $json["resultado"];
    }
    if (isset($json["mensaje"])) {
        return (string) $json["mensaje"];
    }
    if (isset($json["modo"])) {
        return (string) $json["modo"];
    }
    return "ok";
}

function extraerAvisos($json)
{
    $avisos = array();
    foreach (array("avisos", "hallazgos") as $clave) {
        if (!empty($json[$clave]) && is_array($json[$clave])) {
            foreach ($json[$clave] as $aviso) {
                if (is_scalar($aviso)) {
                    $avisos[] = (string) $aviso;
                }
            }
        }
    }
    return array_slice(array_values(array_unique($avisos)), 0, 12);
}

function resumenJson($json)
{
    $salida = array(
        "ok" => isset($json["ok"]) ? (bool) $json["ok"] : null,
        "modo" => isset($json["modo"]) ? $json["modo"] : "",
    );
    foreach (array("decision", "resultado", "resumen", "bloqueos", "avisos") as $clave) {
        if (array_key_exists($clave, $json)) {
            $salida[$clave] = $json[$clave];
        }
    }
    return $salida;
}

function parseArgs($argv)
{
    $args = array();
    foreach (array_slice($argv, 1) as $arg) {
        if (strpos($arg, "--") !== 0) {
            continue;
        }
        $arg = substr($arg, 2);
        $partes = explode("=", $arg, 2);
        $args[$partes[0]] = isset($partes[1]) ? $partes[1] : true;
    }
    return $args;
}

function resumenResultados($resultados)
{
    $salida = array();
    foreach ($resultados as $clave => $resultado) {
        $salida[$clave] = array(
            "ok" => !empty($resultado["ok"]),
            "exit_code" => isset($resultado["exit_code"]) ? (int) $resultado["exit_code"] : null,
            "mensaje" => isset($resultado["mensaje"]) ? $resultado["mensaje"] : "",
            "avisos_total" => !empty($resultado["avisos"]) && is_array($resultado["avisos"]) ? count($resultado["avisos"]) : 0,
        );
    }
    return $salida;
}
