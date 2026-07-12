<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar Listas de precios ERP antes de solicitar DDL/semillas/guardado UAT.
 * Impacto: consolida readiness de esquema, permisos, UI y guardrails sin escribir BD.
 * Contrato: read-only; no ejecuta DDL, no inserta permisos, no crea listas y no cambia precios.
 */

$args = isset($argv) ? $argv : array();
$respaldo = "";
foreach ($args as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/SeguridadEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

$esquema = new VentasErpEsquema();
$seguridad = new SeguridadEsquema();
$listas = new ListasPreciosErp();

$auditoriaCrm = $esquema->auditarListasPreciosCrm();
$auditoriaEventos = $esquema->auditarAuditoriaListasPrecios();
$planCrm = $esquema->planActualizarListasPreciosCrm(false);
$planEventos = $esquema->planActualizarAuditoriaListasPrecios(false);
$resumen = $listas->resumenReadOnly(array("limite" => 20));
$permisosListas = permisosListas($seguridad->permisosBaseERP());
$rolesListas = rolesConPermisosListas($seguridad->permisosPorRolBaseERP());
$respaldoValidacion = validarRespaldoReferencia($respaldo);

$bloqueos = array();
$avisos = array();

if (count($permisosListas) !== 8) {
    $bloqueos[] = "Se esperaban 8 permisos ventas.listas.*, encontrados " . count($permisosListas);
}
if (!archivoExiste("../app/vistas/paginas/apps/erp/ventas/listas_precios.php")) {
    $bloqueos[] = "Falta vista de Listas de precios";
}
if (!archivoExiste("../public/assets/js/custom/apps/erp/ventas/listas_precios.js")) {
    $bloqueos[] = "Falta JS de Listas de precios";
}
if (!tablaExisteEnAuditoria($auditoriaEventos, "erp_listas_precios_eventos")) {
    $avisos[] = "Falta aplicar DDL de auditoria erp_listas_precios_eventos";
}
if (!columnaExisteEnAuditoria($auditoriaCrm, "id_cliente_crm")) {
    $avisos[] = "Falta aplicar columna id_cliente_crm en erp_clientes_listas_precios";
}
if (count(extraerSql($planCrm)) <= 0) {
    $avisos[] = "Plan CRM/listas no genero SQL; revisar si ya esta aplicado";
}
if (count(extraerSql($planEventos)) !== 1) {
    $bloqueos[] = "Plan de auditoria deberia generar 1 CREATE TABLE, genero " . count(extraerSql($planEventos));
}
if ($respaldo !== "" && !$respaldoValidacion["ok"]) {
    $bloqueos[] = "Referencia de respaldo no valida";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_preflight_readonly",
    "read_only" => true,
    "respaldo" => $respaldoValidacion,
    "permisos" => array(
        "ventas_listas_total" => count($permisosListas),
        "ventas_listas" => array_values($permisosListas),
        "roles_con_listas" => $rolesListas
    ),
    "schema" => array(
        "auditoria_crm" => $auditoriaCrm,
        "auditoria_eventos" => $auditoriaEventos,
        "plan_crm_sin_ejecutar" => $planCrm,
        "plan_eventos_sin_ejecutar" => $planEventos,
        "sql_crm_total" => count(extraerSql($planCrm)),
        "sql_eventos_total" => count(extraerSql($planEventos))
    ),
    "modulo" => array(
        "resumen_readonly" => $resumen,
        "vista_existe" => archivoExiste("../app/vistas/paginas/apps/erp/ventas/listas_precios.php"),
        "js_existe" => archivoExiste("../public/assets/js/custom/apps/erp/ventas/listas_precios.js")
    ),
    "guardrails" => array(
        "ddl_crm_token" => "VENTAS_LISTAS_PRECIOS_CRM_DDL",
        "ddl_auditoria_token" => "VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL",
        "guardado_uat_token" => "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
        "requiere_respaldo_externo" => true,
        "no_promociones" => true,
        "no_ecommerce_activo" => true
    ),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para solicitar autorizacion de DDL CRM/listas y auditoria; despues permisos."
        : "Resolver bloqueos antes de solicitar autorizacion."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function permisosListas($permisos) {
    $resultado = array();
    foreach ($permisos as $permiso) {
        if (strpos($permiso["permiso"], "ventas.listas.") === 0) {
            $resultado[] = $permiso["permiso"];
        }
    }
    return $resultado;
}

function rolesConPermisosListas($roles) {
    $resultado = array();
    foreach ($roles as $rol => $permisos) {
        $filtrados = array();
        foreach ($permisos as $permiso) {
            if (strpos($permiso, "ventas.listas.") === 0) {
                $filtrados[] = $permiso;
            }
        }
        if (!empty($filtrados)) {
            $resultado[$rol] = $filtrados;
        }
    }
    return $resultado;
}

function tablaExisteEnAuditoria($auditoria, $tabla) {
    $tablas = valor($auditoria, array("depurar", "tablas"), array());
    foreach ($tablas as $item) {
        if (isset($item["tabla"]) && $item["tabla"] === $tabla) {
            return !empty($item["existe"]);
        }
    }
    return false;
}

function columnaExisteEnAuditoria($auditoria, $columna) {
    $columnas = valor($auditoria, array("depurar", "columnas"), array());
    foreach ($columnas as $item) {
        if (isset($item["columna"]) && $item["columna"] === $columna) {
            return !empty($item["existe"]);
        }
    }
    return false;
}

function extraerSql($plan) {
    $sql = array();
    foreach ($plan as $paso) {
        $sentencia = valor($paso, array("depurar", "sql"), "");
        if ($sentencia !== "") {
            $sql[] = $sentencia;
        }
    }
    return $sql;
}

function validarRespaldoReferencia($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = $respaldo === "" || strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "referencia" => $respaldo,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function archivoExiste($ruta) {
    return file_exists($ruta) && is_file($ruta);
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
