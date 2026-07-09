<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: prevalidar registro real de excepcion comercial POS sin escribir BD.
 * Impacto: confirma politica, permiso, cliente/lista/precio y limites antes de autorizar escritura.
 * Contrato: read-only; no crea excepcion, venta, pago, caja ni inventario.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$identificador = "5550000000";
$tipo = "precio_manual";
$precioManual = 285;
$descuentoMonto = 20;
$motivo = "UAT excepcion comercial POS";
$codigoAutorizacion = "SUP-UAT-001";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--precio_manual=") === 0) {
        $precioManual = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--descuento_monto=") === 0) {
        $descuentoMonto = floatval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--codigo_autorizacion=") === 0) {
        $codigoAutorizacion = trim(substr($arg, 22), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class VentasErpExcepcionPreflightUat extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventas = new VentasErpExcepcionPreflightUat();
$db = $ventas->conexionUat();
$items = json_encode(array(array("id_sku" => $idSku, "cantidad" => 1, "modo_salida" => "existencia_agregada")));
$datos = array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "tipo_excepcion" => $tipo,
    "id_sku" => $idSku,
    "precio_manual" => $precioManual,
    "descuento_monto" => $tipo === "precio_manual" ? 0 : $descuentoMonto,
    "motivo" => $motivo,
    "codigo_autorizacion" => $codigoAutorizacion,
    "items" => $items,
    "id_usuario" => $idUsuario,
    "autorizado_por" => $idUsuario
);

$dryRun = $ventas->excepcionComercialDryRun($datos);
$politica = consultarPolitica($db, $tipo, "pos", $idAlmacen);
$tienePermiso = usuarioTienePermiso($db, $idUsuario, "ventas.autorizar_excepcion_comercial");
$conteoAntes = contar($db, "erp_ventas_excepciones_comerciales");
$bloqueos = array();
$bloqueosDryRun = isset($dryRun["depurar"]["bloqueos"]) && is_array($dryRun["depurar"]["bloqueos"]) ? $dryRun["depurar"]["bloqueos"] : array();
foreach ($bloqueosDryRun as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
if (!$politica) {
    $bloqueos[] = "No existe politica comercial activa";
}
if (!$tienePermiso) {
    $bloqueos[] = "Usuario sin permiso ventas.autorizar_excepcion_comercial";
}

echo json_encode(array(
    "ok" => empty($bloqueos) && empty($dryRun["error"]),
    "modo" => "ventas_pos_excepcion_registro_preflight_readonly",
    "datos" => $datos,
    "dry_run" => $dryRun,
    "politica" => $politica,
    "autorizador_tiene_permiso" => $tienePermiso,
    "conteo_excepciones_antes" => $conteoAntes,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "siguiente_paso" => empty($bloqueos) ? "Solicitar autorizacion VENTAS_POS_EXCEPCION_REAL para registrar la excepcion." : "Resolver bloqueos antes de autorizar registro real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function consultarPolitica($db, $tipo, $canal, $idAlmacen) {
    $stmt = $db->prepare("SELECT id_politica_comercial, codigo, nombre, tipo_excepcion, descuento_max_porcentaje,
            descuento_max_monto, requiere_autorizacion, permiso_requerido, estatus
        FROM erp_ventas_politicas_comerciales
        WHERE tipo_excepcion=:tipo AND estatus='activa'
          AND (canal IS NULL OR canal='' OR canal=:canal)
          AND (id_almacen IS NULL OR id_almacen=0 OR id_almacen=:almacen)
        ORDER BY id_almacen DESC, id_politica_comercial DESC
        LIMIT 1");
    $stmt->execute(array(":tipo" => $tipo, ":canal" => $canal, ":almacen" => $idAlmacen));
    $politica = $stmt->fetch(PDO::FETCH_ASSOC);
    return $politica ?: null;
}

function usuarioTienePermiso($db, $idUsuario, $permiso) {
    $stmt = $db->prepare("SELECT COUNT(*)
        FROM sys_usuarios_roles ur
        INNER JOIN sys_roles r ON r.id_rol=ur.id_rol AND r.estatus=1
        INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
        INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso AND p.estatus=1
        WHERE ur.id_usuario=:usuario AND ur.estatus=1 AND p.permiso=:permiso");
    $stmt->execute(array(":usuario" => $idUsuario, ":permiso" => $permiso));
    return intval($stmt->fetchColumn()) > 0;
}

function contar($db, $tabla) {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`");
    return intval($stmt->fetchColumn());
}
