<?php

if (empty($_SERVER["SERVER_NAME"])) {
    $_SERVER["SERVER_NAME"] = "panel.com.local";
}
if (empty($_SERVER["HTTP_HOST"])) {
    $_SERVER["HTTP_HOST"] = "panel.com.local";
}

/**
 * Documentacion IA: Codex GPT-5, 2026-10-10.
 * Proposito: validar read-only que POS puede simular una autorizacion `precio_conjunto`.
 * Impacto: no crea excepcion, venta, pago, caja, kardex ni inventario.
 * Contrato: usa conexion configurada por el proyecto; no imprime credenciales.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 2;
$totalConjunto = 500;
$motivo = "Readiness POS precio conjunto";
$codigoAutorizacion = "SUP-READINESS";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = max(1, intval(trim(substr($arg, 11), "\"' ")));
    } elseif (strpos($arg, "--total_conjunto=") === 0) {
        $totalConjunto = floatval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--codigo_autorizacion=") === 0) {
        $codigoAutorizacion = trim(substr($arg, 22), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosPrecioConjuntoReadiness extends VentasErp {
    public function conexionReadiness() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosPrecioConjuntoReadiness();
$db = $ventas->conexionReadiness();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "sin_conexion",
        "mensaje" => "No se pudo abrir conexion usando la configuracion del proyecto.",
        "server_name" => $_SERVER["SERVER_NAME"]
    ));
}

$items = array();
for ($i = 0; $i < $cantidad; $i++) {
    $items[] = array(
        "id_sku" => $idSku,
        "cantidad" => 1,
        "modo_salida" => "existencia_agregada",
        "aplica_ajuste_conjunto" => 1
    );
}

$datos = array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "tipo_excepcion" => "precio_conjunto",
    "precio_conjunto_total" => $totalConjunto,
    "motivo" => $motivo,
    "codigo_autorizacion" => $codigoAutorizacion,
    "items" => json_encode($items),
    "id_usuario" => $idUsuario,
    "autorizado_por" => $idUsuario
);

$conteoAntes = contar($db, "erp_ventas_excepciones_comerciales");
$dryRun = $ventas->excepcionComercialDryRun($datos);
$conteoDespues = contar($db, "erp_ventas_excepciones_comerciales");
$politica = consultarPolitica($db, "precio_conjunto", "pos", $idAlmacen);
$tienePermiso = usuarioTienePermiso($db, $idUsuario, "ventas.autorizar_excepcion_comercial");

$bloqueos = array();
$bloqueosDryRun = isset($dryRun["depurar"]["bloqueos"]) && is_array($dryRun["depurar"]["bloqueos"]) ? $dryRun["depurar"]["bloqueos"] : array();
foreach ($bloqueosDryRun as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
if (!$politica) {
    $bloqueos[] = "No existe politica activa precio_conjunto";
}
if (!$tienePermiso) {
    $bloqueos[] = "Usuario sin permiso ventas.autorizar_excepcion_comercial";
}
if ($conteoAntes !== $conteoDespues) {
    $bloqueos[] = "Read-only violado: cambio conteo de excepciones";
}

responder(array(
    "ok" => empty($bloqueos) && empty($dryRun["error"]),
    "modo" => "ventas_pos_precio_conjunto_readiness_readonly",
    "server_name" => $_SERVER["SERVER_NAME"],
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "cantidad_partidas" => $cantidad,
    "total_conjunto" => $totalConjunto,
    "politica" => $politica,
    "autorizador_tiene_permiso" => $tienePermiso,
    "conteo_excepciones_antes" => $conteoAntes,
    "conteo_excepciones_despues" => $conteoDespues,
    "totales" => isset($dryRun["depurar"]["totales"]) ? $dryRun["depurar"]["totales"] : array(),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "siguiente_paso" => empty($bloqueos) ? "Registrar folio real desde UI POS o con autorizacion explicita." : "Resolver bloqueos antes de registrar folio real."
));

function consultarPolitica($db, $tipo, $canal, $idAlmacen) {
    $stmt = $db->prepare("SELECT id_politica_comercial, codigo, nombre, tipo_excepcion, canal, id_almacen,
            descuento_max_porcentaje, descuento_max_monto, requiere_autorizacion, permiso_requerido, estatus
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
    $stmt->execute(array(":usuario" => intval($idUsuario), ":permiso" => $permiso));
    return intval($stmt->fetchColumn()) > 0;
}

function contar($db, $tabla) {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`");
    return intval($stmt->fetchColumn());
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
