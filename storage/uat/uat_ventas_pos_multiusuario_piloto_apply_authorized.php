<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: habilitar usuarios POS piloto asignando rol ventas y asignacion usuario/caja/terminal con autorizacion explicita.
 * Impacto: permite que varios operadores usen su propio usuario en la misma sucursal/caja/terminal durante piloto controlado.
 * Contrato: escribe BD solo con token VENTAS_POS_MULTIUSUARIO_PILOTO, respaldo referenciado, usuarios validos, rol ventas existente y motivo implicito UAT.
 */

$token = "";
$respaldo = "";
$idUsuarios = array();
$idRolVentas = 0;
$idAlmacen = 5;
$idCaja = 2;
$idTerminal = 2;
$idUsuarioEjecuta = 1;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $token = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuarios=") === 0) {
        $idUsuarios = array_filter(array_map("intval", explode(",", trim(substr($arg, 14), "\"' "))));
    } elseif (strpos($arg, "--id_rol_ventas=") === 0) {
        $idRolVentas = intval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_terminal=") === 0) {
        $idTerminal = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_usuario_ejecuta=") === 0) {
        $idUsuarioEjecuta = intval(trim(substr($arg, 21), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";
require_once "../app/modelos/VentasErp.php";

$seguridad = new SeguridadPermisos();
$ventas = new VentasErp();
$bloqueos = array();
if ($token !== "VENTAS_POS_MULTIUSUARIO_PILOTO") {
    $bloqueos[] = "Token de autorizacion invalido";
}
if (strlen($respaldo) < 8) {
    $bloqueos[] = "Referencia de respaldo obligatoria";
}
if (empty($idUsuarios)) {
    $bloqueos[] = "Falta --id_usuarios=ID,ID";
}
if ($idRolVentas <= 0) {
    $bloqueos[] = "Falta --id_rol_ventas";
}
if ($idAlmacen <= 0 || $idCaja <= 0 || $idTerminal <= 0) {
    $bloqueos[] = "Faltan id_almacen/id_caja/id_terminal validos";
}

$roles = $seguridad->listarRoles();
$rolVentasOk = false;
foreach (valor($roles, "depurar", array()) as $rol) {
    if (intval(valor($rol, "id_rol", 0)) === $idRolVentas && valor($rol, "rol", "") === "ventas" && intval(valor($rol, "estatus", 0)) === 1) {
        $rolVentasOk = true;
        break;
    }
}
if (!$rolVentasOk) {
    $bloqueos[] = "id_rol_ventas no corresponde a rol ventas activo";
}

$preflight = ejecutarPreflight($idUsuarios, $idAlmacen, $idCaja, $idTerminal);
if (empty(valor($preflight, "ok", false))) {
    $bloqueos[] = "Preflight multiusuario no devolvio salida valida";
}

$usuariosValidacion = validarUsuariosObjetivo($idUsuarios, $seguridad);
if (!empty(valor($usuariosValidacion, "bloqueos", array()))) {
    $bloqueos = array_merge($bloqueos, valor($usuariosValidacion, "bloqueos", array()));
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "guardrail",
        "mensaje" => "No se habilitaron usuarios POS piloto",
        "bloqueos" => array_values(array_unique($bloqueos)),
        "preflight" => $preflight,
        "usuarios_validacion" => $usuariosValidacion,
        "contrato" => contrato()
    ), 1);
}

$resultados = array();
foreach ($idUsuarios as $idUsuario) {
    $rol = $seguridad->asignarRolUsuario($idUsuario, $idRolVentas);
    $asignacion = $ventas->configuracionAsignacionGuardarReal(array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_terminal_pos" => $idTerminal,
        "prioridad" => 10,
        "observaciones" => "Piloto POS multiusuario autorizado con respaldo " . $respaldo
    ), $idUsuarioEjecuta);
    $resultados[] = array(
        "id_usuario" => $idUsuario,
        "rol" => $rol,
        "asignacion" => $asignacion
    );
}

$post = ejecutarPreflight($idUsuarios, $idAlmacen, $idCaja, $idTerminal);
responder(array(
    "ok" => true,
    "modo" => "ventas_pos_multiusuario_piloto_apply_authorized",
    "mensaje" => "Usuarios POS piloto habilitados",
    "respaldo" => $respaldo,
    "usuarios_validacion" => $usuariosValidacion,
    "resultados" => $resultados,
    "post_preflight" => $post,
    "contrato" => contrato()
));

function validarUsuariosObjetivo($idUsuarios, $seguridad) {
    $usuariosRoles = $seguridad->listarUsuariosRoles();
    $filas = valor($usuariosRoles, "depurar", array());
    $indexados = array();
    foreach (is_array($filas) ? $filas : array() as $fila) {
        $indexados[intval(valor($fila, "id_usuario", 0))] = $fila;
    }

    $bloqueos = array();
    $usuarios = array();
    foreach ($idUsuarios as $idUsuario) {
        $fila = isset($indexados[$idUsuario]) ? $indexados[$idUsuario] : array();
        $estatus = intval(valor($fila, "estatus", 0));
        if (empty($fila)) {
            $bloqueos[] = "Usuario " . $idUsuario . " no existe";
        } elseif ($estatus !== 1) {
            $bloqueos[] = "Usuario " . $idUsuario . " no esta activo";
        }
        $usuarios[] = array(
            "id_usuario" => $idUsuario,
            "existe" => !empty($fila),
            "estatus" => $estatus
        );
    }

    return array(
        "ok" => empty($bloqueos),
        "usuarios" => $usuarios,
        "bloqueos" => $bloqueos
    );
}

function ejecutarPreflight($usuarios, $idAlmacen, $idCaja, $idTerminal) {
    $cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . "uat_ventas_pos_multiusuario_preflight_readonly.php")
        . " " . escapeshellarg("--usuarios=" . implode(",", $usuarios))
        . " " . escapeshellarg("--id_almacen=" . $idAlmacen)
        . " " . escapeshellarg("--id_caja=" . $idCaja)
        . " " . escapeshellarg("--id_terminal=" . $idTerminal);
    $lineas = array();
    $codigo = 0;
    exec($cmd, $lineas, $codigo);
    $json = json_decode(implode("\n", $lineas), true);
    return is_array($json) ? $json : array("ok" => false, "exit_code" => $codigo);
}

function contrato() {
    return array(
        "requiere_token" => "VENTAS_POS_MULTIUSUARIO_PILOTO",
        "requiere_respaldo" => true,
        "asigna_rol_ventas" => true,
        "crea_asignacion_pos" => true,
        "no_abre_turno" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_crea_venta" => true
    );
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
