<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar sin escritura los payloads que usa la UI para pausar/activar/cancelar vinculos segmento/lista.
 * Impacto: confirma que las acciones rapidas de Comercial/Listas no chocan con reglas backend antes de usarse.
 * Contrato: read-only; no guarda vinculos, no modifica listas, segmentos, clientes ni ventas.
 */

$idLista = obtenerArgEstatus("id_lista_precio", 2);
$codigoSegmento = obtenerArgTextoEstatus("codigo_segmento", "RECURRENTE");
$idAlmacen = obtenerArgEstatus("id_almacen", 5);
$canal = obtenerArgTextoEstatus("canal", "pos");

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ListasPreciosErp.php";

class LpSegmentosEstatusUiReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpSegmentosEstatusUiReadonlyDb())->db();
$modelo = new ListasPreciosErp();
$bloqueos = array();

$segmento = $db ? segmentoPorCodigoEstatus($db, $codigoSegmento) : null;
$vinculo = $db && $segmento ? vinculoEstatus($db, $idLista, intval($segmento["id_segmento_crm"]), $canal, $idAlmacen) : null;
$dryruns = array();

if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
}
if (!$segmento) {
    $bloqueos[] = "segmento_no_encontrado";
}
if (!$vinculo) {
    $bloqueos[] = "vinculo_segmento_lista_no_encontrado";
}

if ($vinculo) {
    foreach (array("pausado", "activo", "cancelado") as $estatus) {
        $payload = array(
            "id_segmento_lista_precio" => intval($vinculo["id_segmento_lista_precio"]),
            "id_segmento_crm" => intval($vinculo["id_segmento_crm"]),
            "id_lista_precio" => intval($vinculo["id_lista_precio"]),
            "canal" => (string) $vinculo["canal"],
            "id_almacen" => intval($vinculo["id_almacen"]),
            "prioridad" => intval($vinculo["prioridad"]),
            "fecha_inicio" => fechaSoloEstatus($vinculo["fecha_inicio"]),
            "fecha_fin" => fechaSoloEstatus($vinculo["fecha_fin"]),
            "estatus" => $estatus,
            "motivo" => "Dry-run UI de cambio de estatus segmento/lista"
        );
        $respuesta = $modelo->asignacionSegmentoDryRun($payload);
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
    "resultado" => empty($bloqueos) ? "PASS_ESTATUS_UI_DRYRUN" : "FAIL_ESTATUS_UI_DRYRUN",
    "parametros" => array(
        "id_lista_precio" => $idLista,
        "codigo_segmento" => $codigoSegmento,
        "canal" => $canal,
        "id_almacen" => $idAlmacen
    ),
    "segmento" => $segmento,
    "vinculo" => $vinculo,
    "dryruns" => $dryruns,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_guarda_vinculos" => true,
        "no_modifica_clientes" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function segmentoPorCodigoEstatus($db, $codigo) {
    if (!tablaEstatusExiste($db, "crm_clientes_segmentos")) {
        return null;
    }
    $stmt = $db->prepare("SELECT id_segmento_crm, codigo, nombre, tipo, estatus
        FROM crm_clientes_segmentos
        WHERE codigo=:codigo
        LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function vinculoEstatus($db, $idLista, $idSegmento, $canal, $idAlmacen) {
    if (!tablaEstatusExiste($db, "erp_segmentos_listas_precios")) {
        return null;
    }
    $stmt = $db->prepare("SELECT *
        FROM erp_segmentos_listas_precios
        WHERE id_lista_precio=:lista
          AND id_segmento_crm=:segmento
          AND canal=:canal
          AND COALESCE(id_almacen, 0)=:almacen
        ORDER BY estatus='activo' DESC, id_segmento_lista_precio DESC
        LIMIT 1");
    $stmt->execute(array(
        ":lista" => intval($idLista),
        ":segmento" => intval($idSegmento),
        ":canal" => $canal,
        ":almacen" => intval($idAlmacen)
    ));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function tablaEstatusExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function fechaSoloEstatus($valor) {
    if (!$valor) {
        return "";
    }
    return substr((string) $valor, 0, 10);
}

function obtenerArgEstatus($nombre, $default) {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefijo) === 0) {
            return intval(substr($arg, strlen($prefijo)));
        }
    }
    return intval($default);
}

function obtenerArgTextoEstatus($nombre, $default) {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefijo) === 0) {
            return trim((string) substr($arg, strlen($prefijo)));
        }
    }
    return $default;
}
