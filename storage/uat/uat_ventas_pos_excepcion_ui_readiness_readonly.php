<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: validar readiness del registro real de excepcion comercial desde POS UI sin escribir BD.
 * Impacto: confirma endpoint, permisos, politica, cliente/precio y respaldo antes de probar en navegador.
 * Contrato: read-only; no crea excepcion, venta, pago, caja ni inventario.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;
$identificador = "3312345678";
$tipo = "precio_manual";
$precioManual = 285;
$descuentoMonto = 0;
$descuentoPorcentaje = 0;
$motivo = "UAT POS UI precio manual";
$codigoAutorizacion = "SUP-UI-001";
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--precio_manual=") === 0) {
        $precioManual = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--descuento_monto=") === 0) {
        $descuentoMonto = floatval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--descuento_porcentaje=") === 0) {
        $descuentoPorcentaje = floatval(trim(substr($arg, 23), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--codigo_autorizacion=") === 0) {
        $codigoAutorizacion = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/controladores/Ventas.php";

class VentasPosExcepcionUiReadinessDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new VentasPosExcepcionUiReadinessDb();
$db = $ventas->db();
$bloqueos = array();
$avisos = array();
$respaldoValidado = validarRespaldo($respaldo);

if (!$respaldoValidado["ok"]) {
    $bloqueos[] = "Respaldo externo no valido o no legible";
}

$endpointExiste = method_exists("Ventas", "pos_excepcion_comercial_registrar_erp");
if (!$endpointExiste) {
    $bloqueos[] = "No existe endpoint Ventas.pos_excepcion_comercial_registrar_erp";
}

$tablas = array("erp_ventas_politicas_comerciales", "erp_ventas_excepciones_comerciales");
$tablasEstado = array();
foreach ($tablas as $tabla) {
    $existe = tablaExiste($db, $tabla);
    $tablasEstado[$tabla] = $existe;
    if (!$existe) {
        $bloqueos[] = "Falta tabla " . $tabla;
    }
}

$columnasExcepcion = array("id_excepcion_comercial", "folio", "id_politica_comercial", "tipo_excepcion", "estatus", "motivo", "autorizacion_codigo", "solicitado_por", "autorizado_por", "datos_snapshot");
$columnasEstado = array();
if (!empty($tablasEstado["erp_ventas_excepciones_comerciales"])) {
    foreach ($columnasExcepcion as $columna) {
        $existe = columnaExiste($db, "erp_ventas_excepciones_comerciales", $columna);
        $columnasEstado[$columna] = $existe;
        if (!$existe) {
            $bloqueos[] = "Falta columna erp_ventas_excepciones_comerciales." . $columna;
        }
    }
}

$permisoOperar = usuarioTienePermiso($db, $idUsuario, "ventas.operar");
$permisoAutorizar = usuarioTienePermiso($db, $idUsuario, "ventas.autorizar_excepcion_comercial");
if (!$permisoOperar) {
    $bloqueos[] = "Usuario sin permiso ventas.operar";
}
if (!$permisoAutorizar) {
    $bloqueos[] = "Usuario sin permiso ventas.autorizar_excepcion_comercial";
}

$items = json_encode(array(array(
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "modo_salida" => "existencia_agregada"
)));
$datos = array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "tipo_excepcion" => $tipo,
    "id_sku" => $tipo === "descuento_general" ? "" : $idSku,
    "precio_manual" => $precioManual,
    "descuento_monto" => $descuentoMonto,
    "descuento_porcentaje" => $descuentoPorcentaje,
    "motivo" => $motivo,
    "codigo_autorizacion" => $codigoAutorizacion,
    "items" => $items,
    "id_usuario" => $idUsuario,
    "solicitado_por" => $idUsuario,
    "autorizado_por" => $idUsuario
);

$dryRun = $ventas->excepcionComercialDryRun($datos);
foreach (valor($dryRun, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}

$politica = consultarPolitica($db, $tipo, "pos", $idAlmacen);
if (!$politica) {
    $bloqueos[] = "No existe politica comercial activa para " . $tipo;
}

$conteoAntes = tablaExiste($db, "erp_ventas_excepciones_comerciales") ? contar($db, "erp_ventas_excepciones_comerciales") : null;

echo json_encode(array(
    "ok" => empty(array_unique($bloqueos)),
    "modo" => "ventas_pos_excepcion_ui_readiness_readonly",
    "read_only" => true,
    "endpoint" => array(
        "ruta" => "/ventas/pos_excepcion_comercial_registrar_erp",
        "controlador_metodo_existe" => $endpointExiste,
        "requiere_csrf_post" => true,
        "requiere_sesion" => true
    ),
    "respaldo" => $respaldoValidado,
    "parametros" => $datos,
    "schema" => array(
        "tablas" => $tablasEstado,
        "columnas_excepcion" => $columnasEstado
    ),
    "permisos" => array(
        "ventas.operar" => $permisoOperar,
        "ventas.autorizar_excepcion_comercial" => $permisoAutorizar
    ),
    "politica" => $politica,
    "dry_run" => $dryRun,
    "conteo_excepciones_antes" => $conteoAntes,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "siguiente_paso" => empty($bloqueos)
        ? "Probar desde navegador autenticado: Autorizacion > Registrar folio autorizado > Aplicar folio."
        : "Resolver bloqueos antes de probar registro real desde POS UI."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function validarRespaldo($respaldo) {
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    return array(
        "ok" => trim((string) $respaldo) !== "" && (!$pareceRuta || ($existe && is_readable($respaldo))),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $existe,
        "archivo_legible" => $existe ? is_readable($respaldo) : false,
        "tamano_bytes" => $existe ? filesize($respaldo) : null
    );
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function columnaExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `" . str_replace("`", "", $tabla) . "` LIKE :columna");
    $stmt->execute(array(":columna" => $columna));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
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
    if (!tablaExiste($db, "erp_ventas_politicas_comerciales")) {
        return null;
    }
    $stmt = $db->prepare("SELECT id_politica_comercial, codigo, nombre, tipo_excepcion, descuento_max_porcentaje,
            descuento_max_monto, requiere_autorizacion, permiso_requerido, estatus
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

function contar($db, $tabla) {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`");
    return intval($stmt->fetchColumn());
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
