<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: auditar en modo read-only si varios usuarios pueden operar POS en la misma sucursal/caja/terminal.
 * Impacto: prepara piloto multiusuario sin asignar roles, sin crear asignaciones POS y sin abrir turno.
 * Contrato: solo lectura; no escribe BD, no modifica roles, no asigna cajas, no mueve caja ni inventario.
 */

$idUsuarios = array(1, 2, 3);
$idAlmacen = 5;
$idCaja = 2;
$idTerminal = 2;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--usuarios=") === 0) {
        $idUsuarios = array_filter(array_map("intval", explode(",", trim(substr($arg, 11), "\"' "))));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_terminal=") === 0) {
        $idTerminal = intval(trim(substr($arg, 14), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/SeguridadPermisos.php";

$ventas = new VentasErp();
$seguridad = new SeguridadPermisos();
$usuariosRoles = $seguridad->listarUsuariosRoles();
$usuarios = indexarUsuarios(valor($usuariosRoles, "depurar", array()));
$roles = $seguridad->listarRoles();
$idRolVentas = buscarRol(valor($roles, "depurar", array()), "ventas");
$idRolAdministrador = buscarRol(valor($roles, "depurar", array()), "administrador_erp");

$resultados = array();
$bloqueos = array();
$avisos = array();
foreach ($idUsuarios as $idUsuario) {
    $usuario = isset($usuarios[$idUsuario]) ? $usuarios[$idUsuario] : array();
    $autorizacion = $seguridad->autorizacionUsuario($idUsuario);
    $permisos = array_fill_keys(valor($autorizacion, "permisos", array()), true);
    $rolesUsuario = valor($autorizacion, "roles", array());
    $asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
    $depurarAsignacion = valor($asignacion, "depurar", array());
    $datosAsignacion = valor($depurarAsignacion, "asignacion", array());
    $tienePermisoOperar = !empty($permisos["ventas.operar"]);
    $tienePermisoVer = !empty($permisos["ventas.ver"]);
    $asignadoObjetivo = !empty($datosAsignacion)
        && intval(valor($datosAsignacion, "id_almacen", 0)) === $idAlmacen
        && intval(valor($datosAsignacion, "id_caja", 0)) === $idCaja
        && intval(valor($datosAsignacion, "id_terminal_pos", 0)) === $idTerminal;
    $faltantes = array();
    if (empty($usuario)) {
        $faltantes[] = "usuario_no_encontrado";
    }
    if (!$tienePermisoVer || !$tienePermisoOperar) {
        $faltantes[] = "rol_o_permisos_ventas";
    }
    if (!$asignadoObjetivo) {
        $faltantes[] = "asignacion_pos_objetivo";
    }
    $puedePiloto = empty($faltantes);
    if (!$puedePiloto) {
        $bloqueos[] = "Usuario " . $idUsuario . " no listo: " . implode(",", $faltantes);
    }
    $resultados[] = array(
        "id_usuario" => $idUsuario,
        "nombre" => nombreUsuario($usuario),
        "estatus" => valor($usuario, "estatus", null),
        "roles" => $rolesUsuario,
        "ventas_ver" => $tienePermisoVer,
        "ventas_operar" => $tienePermisoOperar,
        "asignacion_activa" => !empty(valor($depurarAsignacion, "asignacion_activa", false)),
        "asignacion_objetivo" => $asignadoObjetivo,
        "turno_abierto_en_caja" => !empty(valor($depurarAsignacion, "turno_abierto", array())),
        "faltantes" => $faltantes,
        "puede_participar_piloto" => $puedePiloto,
        "asignacion_actual" => $datosAsignacion
    );
}

if ($idRolVentas <= 0) {
    $avisos[] = "No se encontro rol ventas; revisar Seguridad antes de habilitar usuarios POS.";
}
if ($idRolAdministrador <= 0) {
    $avisos[] = "No se encontro rol administrador_erp; revisar Seguridad.";
}

responder(array(
    "ok" => true,
    "modo" => "ventas_pos_multiusuario_preflight_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "objetivo" => array(
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_terminal_pos" => $idTerminal,
        "usuarios" => array_values($idUsuarios)
    ),
    "roles_referencia" => array(
        "ventas" => $idRolVentas,
        "administrador_erp" => $idRolAdministrador
    ),
    "usuarios" => $resultados,
    "bloqueos_para_piloto_multiusuario" => array_values(array_unique($bloqueos)),
    "avisos" => $avisos,
    "autorizacion_sugerida_si_se_quiere_habilitar" => "AUTORIZO HABILITAR USUARIOS POS PILOTO usando respaldo UAT POS vigente con token VENTAS_POS_MULTIUSUARIO_PILOTO id_usuarios=2,3 id_rol_ventas=" . $idRolVentas . " id_almacen=" . $idAlmacen . " id_caja=" . $idCaja . " id_terminal=" . $idTerminal . " para UAT POS",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_asigna_roles" => true,
        "no_crea_asignaciones" => true,
        "no_abre_turno" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    )
));

function indexarUsuarios($filas) {
    $out = array();
    foreach (is_array($filas) ? $filas : array() as $fila) {
        $out[intval(valor($fila, "id_usuario", 0))] = $fila;
    }
    return $out;
}

function buscarRol($roles, $nombre) {
    foreach (is_array($roles) ? $roles : array() as $rol) {
        if (valor($rol, "rol", "") === $nombre) {
            return intval(valor($rol, "id_rol", 0));
        }
    }
    return 0;
}

function nombreUsuario($usuario) {
    $partes = array(valor($usuario, "nombres", ""), valor($usuario, "apellido_paterno", ""), valor($usuario, "apellido_materno", ""));
    $nombre = trim(implode(" ", array_filter($partes)));
    return $nombre !== "" ? $nombre : valor($usuario, "nombre", "");
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}