<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar sin escritura que el catalogo CRM acepta cambios de estatus de segmentos desde UI.
 * Impacto: confirma que pausar/activar/cancelar tipos de cliente no requiere scripts manuales para prevalidar.
 * Contrato: read-only; no guarda segmentos, no asigna clientes, no modifica listas ni ventas.
 */

$codigo = obtenerArgTextoCrmSeg("codigo", "RECURRENTE");

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ClientesCrm.php";

class CrmSegmentosCatalogoDryrunDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new CrmSegmentosCatalogoDryrunDb())->db();
$modelo = new ClientesCrm();
$segmento = $db ? segmentoCrmDryrun($db, $codigo) : null;
$bloqueos = array();
$dryruns = array();

if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
}
if (!$segmento) {
    $bloqueos[] = "segmento_no_encontrado";
}

if ($segmento) {
    foreach (array("pausado", "activo", "cancelado") as $estatus) {
        $payload = array(
            "id_segmento_crm" => intval($segmento["id_segmento_crm"]),
            "codigo" => $segmento["codigo"],
            "nombre" => $segmento["nombre"],
            "tipo" => $segmento["tipo"],
            "descripcion" => (string) $segmento["descripcion"],
            "estatus" => $estatus
        );
        $respuesta = $modelo->segmentoCatalogoDryRun($payload);
        $puedeGuardar = empty($respuesta["error"]) && !empty($respuesta["depurar"]["puede_guardar"]);
        if (!$puedeGuardar) {
            $bloqueos[] = "dryrun_estatus_" . $estatus . "_bloqueado";
        }
        $dryruns[] = array(
            "estatus" => $estatus,
            "puede_guardar" => $puedeGuardar,
            "payload" => $payload,
            "respuesta" => $respuesta
        );
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_CRM_SEGMENTOS_ESTATUS_DRYRUN" : "FAIL_CRM_SEGMENTOS_ESTATUS_DRYRUN",
    "codigo" => $codigo,
    "segmento" => $segmento,
    "dryruns" => $dryruns,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_guarda_segmentos" => true,
        "no_asigna_clientes" => true,
        "no_modifica_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function segmentoCrmDryrun($db, $codigo) {
    $stmt = $db->prepare("SELECT id_segmento_crm, codigo, nombre, tipo, descripcion, estatus
        FROM crm_clientes_segmentos
        WHERE codigo=:codigo
        LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function obtenerArgTextoCrmSeg($nombre, $default) {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefijo) === 0) {
            return trim((string) substr($arg, strlen($prefijo)));
        }
    }
    return $default;
}
