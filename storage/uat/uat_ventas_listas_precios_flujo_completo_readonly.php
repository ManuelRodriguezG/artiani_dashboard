<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: consolidar el estado read-only del flujo UAT completo de Listas de precios.
 * Impacto: permite decidir si ya se puede aplicar DDL, permisos y CRUD UAT sin revisar varios scripts.
 * Contrato: read-only; no ejecuta DDL, no inserta permisos, no crea listas y no modifica precios.
 */

$args = isset($argv) ? $argv : array();
$codigoLista = "LP-UAT-BORRADOR-01";
$idSku = 1760;
$idAlmacen = 5;
$idClienteCrm = 1;
$canal = "pos";
$precioEsperado = 315.00;

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
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/SeguridadEsquema.php";
require_once "../app/modelos/SeguridadPermisos.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/VentasErp.php";

class SeguridadPermisosListasFlujo extends SeguridadPermisos {
    public function conexionUat() {
        return $this->getConexion();
    }
}
class ListasPreciosErpFlujo extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventasEsquema = new VentasErpEsquema();
$seguridadEsquema = new SeguridadEsquema();
$seguridad = new SeguridadPermisosListasFlujo();
$listas = new ListasPreciosErpFlujo();
$ventas = new VentasErp();
$db = $listas->conexionUat();

$permisosEsperados = permisosListas($seguridadEsquema->permisosBaseERP());
$permisosBd = consultarPermisosBd($seguridad, $permisosEsperados);
$auditoriaCrm = $ventasEsquema->auditarListasPreciosCrm();
$auditoriaEventos = $ventasEsquema->auditarAuditoriaListasPrecios();
$lista = buscarListaPorCodigo($db, $codigoLista);
$idLista = intval(valor($lista, "id_lista_precio", 0));
$detalle = $idLista > 0 ? buscarDetalleSku($db, $idLista, $idSku) : null;
$asignacion = $idLista > 0 ? buscarAsignacionCliente($db, $idLista, $idClienteCrm) : null;

$dryLista = $listas->listaDryRun(array(
    "codigo" => $codigoLista,
    "nombre" => "Lista UAT borrador",
    "canal" => $canal,
    "id_almacen" => $idAlmacen,
    "prioridad" => 100,
    "estatus" => "borrador"
));
$dryDetalle = $listas->detalleDryRun(array(
    "id_lista_precio" => $idLista,
    "id_sku" => $idSku,
    "precio" => $precioEsperado,
    "moneda" => "MXN",
    "estatus" => "activo"
));
$dryAsignacion = $listas->asignacionClienteDryRun(array(
    "id_lista_precio" => $idLista,
    "id_cliente_crm" => $idClienteCrm,
    "prioridad" => 1,
    "estatus" => "activo"
));
$dryResolutor = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => $canal,
    "id_cliente" => $idClienteCrm,
    "items" => json_encode(array(array("id_sku" => $idSku, "cantidad" => 1)))
));
$partidaResolutor = extraerPrimeraPartida($dryResolutor);
$precioDetectado = valor($partidaResolutor, "precio_aplicado", valor($partidaResolutor, "precio_unitario", null));

$etapas = array(
    "ddl_crm_listas" => columnaExisteEnAuditoria($auditoriaCrm, "id_cliente_crm"),
    "ddl_auditoria_eventos" => tablaExisteEnAuditoria($auditoriaEventos, "erp_listas_precios_eventos"),
    "permisos_sembrados" => empty($permisosBd["faltantes_bd"]),
    "lista_borrador_o_activa" => $lista !== null,
    "detalle_sku_activo" => $detalle !== null,
    "asignacion_cliente_activa" => $asignacion !== null,
    "lista_activa" => $lista !== null && valor($lista, "estatus", "") === "activa",
    "resolutor_cliente_ok" => $idLista > 0
        && intval(valor($partidaResolutor, "id_lista_precio", 0)) === $idLista
        && valor($partidaResolutor, "regla_precio_origen", "") === "lista_cliente"
        && abs(floatval($precioDetectado) - $precioEsperado) <= 0.0001
);

$bloqueos = array();
if (!$etapas["ddl_crm_listas"]) {
    $bloqueos[] = "Falta DDL CRM/listas id_cliente_crm";
}
if (!$etapas["ddl_auditoria_eventos"]) {
    $bloqueos[] = "Falta DDL de auditoria erp_listas_precios_eventos";
}
if (!$etapas["permisos_sembrados"]) {
    $bloqueos[] = "Faltan permisos ventas.listas.* en BD";
}
if (!$etapas["lista_borrador_o_activa"]) {
    $bloqueos[] = "Falta crear lista UAT " . $codigoLista;
}
if (!$etapas["detalle_sku_activo"]) {
    $bloqueos[] = "Falta detalle activo SKU " . $idSku . " en lista UAT";
}
if (!$etapas["asignacion_cliente_activa"]) {
    $bloqueos[] = "Falta asignacion activa cliente CRM/lista UAT";
}
if (!$etapas["lista_activa"]) {
    $bloqueos[] = "Falta activar lista UAT";
}
if (!$etapas["resolutor_cliente_ok"]) {
    $bloqueos[] = "Resolutor con cliente CRM aun no devuelve lista/precio UAT esperado";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_flujo_completo_readonly",
    "read_only" => true,
    "entrada" => array(
        "codigo_lista" => $codigoLista,
        "id_lista_precio" => $idLista,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "id_cliente_crm" => $idClienteCrm,
        "canal" => $canal,
        "precio_esperado" => $precioEsperado
    ),
    "etapas" => $etapas,
    "bloqueos" => $bloqueos,
    "schema" => array(
        "auditoria_crm" => $auditoriaCrm,
        "auditoria_eventos" => $auditoriaEventos
    ),
    "permisos" => $permisosBd,
    "datos_uat" => array(
        "lista" => $lista,
        "detalle" => $detalle,
        "asignacion" => $asignacion
    ),
    "dry_runs" => array(
        "lista" => $dryLista,
        "detalle" => $dryDetalle,
        "asignacion" => $dryAsignacion,
        "resolutor_cliente" => $dryResolutor,
        "precio_detectado" => $precioDetectado
    ),
    "orden_autorizado" => array(
        "schema_apply_con_respaldo",
        "permisos_apply",
        "guardado_borrador_apply",
        "detalle_sku_apply",
        "asignacion_cliente_apply",
        "activar_lista_apply",
        "resolutor_post_uat_readonly",
        "venta_pos_uat_real"
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Flujo UAT listo; autorizar venta POS real solo si operacion lo confirma."
        : "Ejecutar pasos autorizados en orden hasta limpiar bloqueos."
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

function consultarPermisosBd($modelo, $permisos) {
    if (empty($permisos)) {
        return array("permisos_bd" => array(), "faltantes_bd" => array());
    }
    $db = $modelo->conexionUat();
    $lista = array_values($permisos);
    $marcadores = implode(",", array_fill(0, count($lista), "?"));
    $stmt = $db->prepare("SELECT id_permiso, permiso, estatus FROM sys_permisos WHERE permiso IN ($marcadores) ORDER BY permiso");
    $stmt->execute($lista);
    $permisosBd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $set = array();
    foreach ($permisosBd as $permiso) {
        $set[$permiso["permiso"]] = true;
    }
    $faltantes = array();
    foreach ($lista as $permiso) {
        if (!isset($set[$permiso])) {
            $faltantes[] = $permiso;
        }
    }
    return array("permisos_bd" => $permisosBd, "faltantes_bd" => $faltantes);
}

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

function tablaExisteEnAuditoria($auditoria, $tabla) {
    $tablas = valorRuta($auditoria, array("depurar", "tablas"), array());
    foreach ($tablas as $item) {
        if (isset($item["tabla"]) && $item["tabla"] === $tabla) {
            return !empty($item["existe"]);
        }
    }
    return false;
}

function columnaExisteEnAuditoria($auditoria, $columna) {
    $columnas = valorRuta($auditoria, array("depurar", "columnas"), array());
    foreach ($columnas as $item) {
        if (isset($item["columna"]) && $item["columna"] === $columna) {
            return !empty($item["existe"]);
        }
    }
    return false;
}

function extraerPrimeraPartida($respuesta) {
    return valorRuta($respuesta, array("depurar", "partidas", 0), array());
}

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}

function valorRuta($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
