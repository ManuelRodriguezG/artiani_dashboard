<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: generar paquete read-only de autorizacion para activar listas por segmento.
 * Impacto: centraliza respaldo, comandos, estado actual y orden de ejecucion.
 * Contrato: no escribe BD, no ejecuta DDL, no llama scripts apply_authorized.
 */

$respaldo = isset($argv[1]) ? trim((string) $argv[1]) : "C:\\xampp\\panel_db_backups\\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql";
$idLista = isset($argv[2]) ? intval($argv[2]) : 2;
$codigoSegmento = isset($argv[3]) ? trim((string) $argv[3]) : "RECURRENTE";
$idCliente = isset($argv[4]) ? intval($argv[4]) : 2;
$idSku = isset($argv[5]) ? intval($argv[5]) : 1760;
$idAlmacen = isset($argv[6]) ? intval($argv[6]) : 5;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/ClientesCrm.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

class LpSegmentosAutorizacionReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$validacionRespaldo = validarRespaldoPaqueteSegmentos($respaldo);
$db = (new LpSegmentosAutorizacionReadonlyDb())->db();
$crm = new ClientesCrm();
$listas = new ListasPreciosErp();
$esquema = new VentasErpEsquema();

$segmentos = $crm->segmentosCatalogoReadOnly(array("q" => $codigoSegmento, "limite" => 20));
$segmento = buscarSegmentoPaquete($segmentos, $codigoSegmento);
$cliente = clientePaquete($db, $idCliente);
$asignacionesDirectasCliente = asignacionesDirectasClientePaquete($db, $idCliente);
$consultaLista = $listas->consultarReadOnly($idLista);
$auditoria = $esquema->auditarSegmentosListasPrecios();

$comandos = array(
    array(
        "paso" => 1,
        "nombre" => "Sembrar segmentos CRM base",
        "escritura" => true,
        "token" => "CRM_CLIENTES_SEGMENTO_CATALOGO",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_crm_segmentos_catalogo_apply_authorized.php --autorizar=CRM_CLIENTES_SEGMENTO_CATALOGO --respaldo=" . $respaldo
    ),
    array(
        "paso" => 2,
        "nombre" => "Crear tabla puente de segmentos/listas",
        "escritura" => true,
        "token" => "VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmentos_schema_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL --respaldo=" . $respaldo
    ),
    array(
        "paso" => 3,
        "nombre" => "Vincular lista con segmento",
        "escritura" => true,
        "token" => "VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmento_vinculo_apply_authorized.php --autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL --respaldo=" . $respaldo . " --id_lista_precio=" . $idLista . " --codigo_segmento=" . $codigoSegmento . " --canal=pos --id_almacen=" . $idAlmacen . " --prioridad=100"
    ),
    array(
        "paso" => 4,
        "nombre" => "Asignar cliente UAT al segmento",
        "escritura" => true,
        "token" => "CRM_CLIENTES_SEGMENTO",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_crm_cliente_segmento_apply_authorized.php --autorizar=CRM_CLIENTES_SEGMENTO --respaldo=" . $respaldo . " --id_cliente_crm=" . $idCliente . " --codigo_segmento=" . $codigoSegmento . " --principal=1 --actualizar_default=1"
    ),
    array(
        "paso" => 5,
        "nombre" => "Verificar resolutor read-only",
        "escritura" => false,
        "token" => null,
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmentos_suite_readonly.php " . $idLista . " " . $codigoSegmento . " " . $idSku . " " . $idAlmacen . " " . $idCliente
    )
);

$verificacionesPostApply = array(
    array(
        "paso" => "post-1",
        "nombre" => "Acceptance post-apply",
        "escritura" => false,
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmentos_post_apply_acceptance_readonly.php " . $idLista . " " . $codigoSegmento . " " . $idCliente . " " . $idSku . " " . $idAlmacen . " pos"
    ),
    array(
        "paso" => "post-2",
        "nombre" => "Baseline ventas intacta",
        "escritura" => false,
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmentos_ventas_baseline_compare_readonly.php --ventas_total=23 --ventas_max_id=26 --detalle_total=24 --detalle_max_id=27"
    ),
    array(
        "paso" => "post-3",
        "nombre" => "Suite consolidada post-apply",
        "escritura" => false,
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmentos_post_apply_suite_readonly.php --id_lista_precio=" . $idLista . " --codigo_segmento=" . $codigoSegmento . " --id_cliente_crm=" . $idCliente . " --id_sku=" . $idSku . " --id_almacen=" . $idAlmacen . " --canal=pos --ventas_total=23 --ventas_max_id=26 --detalle_total=24 --detalle_max_id=27"
    )
);

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "validacion_respaldo" => $validacionRespaldo,
    "parametros" => array(
        "id_lista_precio" => $idLista,
        "codigo_segmento" => $codigoSegmento,
        "id_cliente_crm" => $idCliente,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen
    ),
    "estado_actual" => array(
        "segmento_existe" => $segmento !== null,
        "cliente_existe" => $cliente !== null,
        "lista_consultada" => empty($consultaLista["error"]),
        "tabla_puente_existe" => valorPaquete($auditoria, array("depurar", "tablas", 3, "existe"), false)
    ),
    "segmento" => $segmento,
    "cliente" => $cliente,
    "asignaciones_directas_cliente" => $asignacionesDirectasCliente,
    "advertencias_uat" => array_values(array_filter(array(
        !empty($asignacionesDirectasCliente) ? "El cliente UAT tiene lista directa activa; el resolutor debe devolver lista_cliente aunque exista segmento. Para probar lista_segmento_cliente usa cliente sin lista directa o pausa la asignacion directa con autorizacion." : null
    ))),
    "lista" => array(
        "error" => isset($consultaLista["error"]) ? $consultaLista["error"] : null,
        "mensaje" => isset($consultaLista["mensaje"]) ? $consultaLista["mensaje"] : "",
        "lista" => valorPaquete($consultaLista, array("depurar", "lista"), null)
    ),
    "comandos_en_orden" => $comandos,
    "verificaciones_post_apply" => $verificacionesPostApply,
    "autorizacion_requerida" => array(
        "escribir_bd" => true,
        "frase_sugerida" => "Autorizo ejecutar los 4 pasos apply_authorized de listas por segmento CRM usando el respaldo " . $respaldo . " y detenerse si algun paso falla.",
        "alcance" => array(
            "sembrar_segmentos_crm_base",
            "crear_tabla_erp_segmentos_listas_precios",
            "vincular_lista_" . $idLista . "_con_segmento_" . $codigoSegmento,
            "asignar_cliente_" . $idCliente . "_al_segmento_" . $codigoSegmento
        ),
        "no_incluye" => array(
            "modificar_ventas_pasadas",
            "crear_promociones",
            "activar_ecommerce",
            "asignar_listas_directas_a_clientes_masivos"
        )
    ),
    "plan_reversa" => array(
        "documento" => "docs/erp_listas_precios_segmentos_plan_reversa.md",
        "preflight_readonly" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmentos_reversa_preflight_readonly.php",
        "regla" => "No ejecutar DROP TABLE si hay vinculos reales; preferir restaurar respaldo o cancelar vinculos con auditoria."
    ),
    "guardrails" => array(
        "este_script_no_escribe_bd" => true,
        "no_ejecuta_comandos" => true,
        "requiere_tokens_por_paso" => true,
        "detener_si_un_paso_falla" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function clientePaquete($db, $idCliente) {
    if (!$db || intval($idCliente) <= 0) {
        return null;
    }
    $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, id_segmento_default
        FROM crm_clientes_maestro
        WHERE id_cliente_crm=:cliente
        LIMIT 1");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    return $cliente ? $cliente : null;
}

function asignacionesDirectasClientePaquete($db, $idCliente) {
    if (!$db || intval($idCliente) <= 0 || !tablaPaqueteExiste($db, "erp_clientes_listas_precios")) {
        return array();
    }
    if (!columnaPaqueteExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
        return array();
    }
    $stmt = $db->prepare("SELECT cl.id_cliente_lista_precio, cl.id_lista_precio, cl.prioridad, cl.estatus,
            l.codigo, l.nombre, l.canal, l.id_almacen, l.estatus estatus_lista
        FROM erp_clientes_listas_precios cl
        INNER JOIN erp_listas_precios l ON l.id_lista_precio=cl.id_lista_precio
        WHERE cl.id_cliente_crm=:cliente
          AND cl.estatus='activo'
        ORDER BY cl.prioridad ASC, cl.id_cliente_lista_precio DESC
        LIMIT 20");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tablaPaqueteExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaPaqueteExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}

function buscarSegmentoPaquete($respuesta, $codigo) {
    $segmentos = valorPaquete($respuesta, array("depurar", "segmentos"), array());
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}

function validarRespaldoPaqueteSegmentos($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $pareceRuta) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || ($existe && $legible && $tamano !== null && $tamano > 0)),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $pareceRuta ? $existe : null,
        "archivo_legible" => $pareceRuta ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function valorPaquete($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
