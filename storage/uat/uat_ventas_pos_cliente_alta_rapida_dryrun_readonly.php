<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: validar alta rapida de cliente POS contra CRM canonico sin escribir BD.
 * Impacto: prepara clientes robustos para POS, listas, apartados, garantias y recompensas futuras.
 * Contrato: read-only; no crea cliente, identificador, consentimiento ni evento.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$nombre = "Cliente Alta Rapida UAT";
$identificador = "5551112222";
$consentimiento = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--nombre=") === 0) {
        $nombre = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--consentimiento=") === 0) {
        $consentimiento = intval(trim(substr($arg, 17), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ClientesCrm.php";

$crm = new ClientesCrm();
$respuesta = $crm->altaRapidaDryRun(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "nombre_publico" => $nombre,
    "identificador" => $identificador,
    "consentimiento_contacto" => $consentimiento,
    "origen_alta" => "pos_uat"
));

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "cliente_alta_rapida_dryrun_readonly",
    "respuesta" => $respuesta,
    "siguiente_paso" => "Si puede_crear=true, usar aplicador autorizado CRM/POS con respaldo externo."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
