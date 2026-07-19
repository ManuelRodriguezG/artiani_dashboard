<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: revisar identidad visual de operadores POS antes del piloto.
 * Impacto: detecta nombres vacios o con mojibake que podrian confundir quien esta cobrando.
 * Contrato: read-only; no corrige usuarios, no asigna roles, no abre turno y no escribe BD.
 */

date_default_timezone_set("America/Mexico_City");

$idUsuarios = array(1, 2, 3);
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--usuarios=") === 0) {
        $idUsuarios = array_filter(array_map("intval", explode(",", trim(substr($arg, 11), "\"' "))));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";

$seguridad = new SeguridadPermisos();
$usuariosRoles = $seguridad->listarUsuariosRoles();
$usuarios = indexarUsuarios(valor($usuariosRoles, "depurar", array()));

$bloqueos = array();
$avisos = array();
$detalle = array();

foreach ($idUsuarios as $idUsuario) {
    $usuario = isset($usuarios[$idUsuario]) ? $usuarios[$idUsuario] : array();
    $nombre = nombreUsuario($usuario);
    $problemas = array();
    if (empty($usuario)) {
        $problemas[] = "usuario_no_encontrado";
    }
    if (trim($nombre) === "") {
        $problemas[] = "nombre_visible_vacio";
    }
    if (tieneMojibake($nombre)) {
        $problemas[] = "posible_mojibake";
    }
    if (strlen($nombre) > 45) {
        $problemas[] = "nombre_largo_para_badge_pos";
    }
    if (in_array("usuario_no_encontrado", $problemas, true) || in_array("nombre_visible_vacio", $problemas, true)) {
        $bloqueos[] = "Usuario " . $idUsuario . " no tiene identidad visual operable.";
    } elseif (!empty($problemas)) {
        $avisos[] = "Usuario " . $idUsuario . " requiere revision visual: " . implode(",", $problemas);
    }
    $detalle[] = array(
        "id_usuario" => $idUsuario,
        "nombre_visible" => $nombre,
        "estatus" => valor($usuario, "estatus", null),
        "problemas" => $problemas,
        "apto_visual_pos" => empty($problemas),
    );
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_operadores_identidad_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "usuarios_revisados" => array_values($idUsuarios),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "detalle" => $detalle,
    "recomendacion" => "Si hay mojibake, corregir datos maestros de usuario desde Seguridad/Usuarios con autorizacion, no desde POS.",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_corrige_usuarios" => true,
        "no_asigna_roles" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);

function indexarUsuarios($filas)
{
    $out = array();
    foreach (is_array($filas) ? $filas : array() as $fila) {
        $out[intval(valor($fila, "id_usuario", 0))] = $fila;
    }
    return $out;
}

function nombreUsuario($usuario)
{
    $partes = array(valor($usuario, "nombres", ""), valor($usuario, "apellido_paterno", ""), valor($usuario, "apellido_materno", ""));
    $nombre = trim(implode(" ", array_filter($partes)));
    return $nombre !== "" ? $nombre : valor($usuario, "nombre", "");
}

function tieneMojibake($texto)
{
    return preg_match('/Ã|Â|â|�|├|┬|�|&Atilde;|&Acirc;/', $texto) === 1;
}

function valor($datos, $campo, $default = null)
{
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

