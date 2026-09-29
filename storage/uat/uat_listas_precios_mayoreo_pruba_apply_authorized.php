<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: crear lista MAYOREO_PRUBA con precios calculados a margen bruto objetivo.
 * Impacto: escritura masiva en `erp_listas_precios`, `erp_listas_precios_detalle` y auditoria comercial.
 * Contrato: BLOQUEADO por defecto; requiere autorizacion explicita, id_usuario y respaldo externo existente.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$confirmacion = "";
$respaldo = "";
$idUsuario = 0;
$codigo = "MAYOREO_PRUBA";
$nombre = "mayoreo_pruba";
$canal = "mayoreo";
$prioridad = 50;
$margen = 20.0;
$redondeo = 0.01;
$activar = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--codigo=") === 0) {
        $codigo = strtoupper(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--nombre=") === 0) {
        $nombre = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--canal=") === 0) {
        $canal = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--prioridad=") === 0) {
        $prioridad = intval(trim(substr($arg, 12), "\"' "));
    } elseif (strpos($arg, "--margen=") === 0) {
        $margen = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--redondeo=") === 0) {
        $redondeo = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--activar=") === 0) {
        $activar = intval(trim(substr($arg, 10), "\"' "));
    }
}

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_MAYOREO_PRUBA_REAL" || $confirmacion !== "CREAR LISTA MAYOREO PRUBA" || $idUsuario <= 0 || !respaldoValido($respaldo)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se creo la lista. Falta autorizacion explicita, confirmacion, id_usuario o respaldo externo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_MAYOREO_PRUBA_REAL",
            "--confirmacion=\"CREAR LISTA MAYOREO PRUBA\"",
            "--id_usuario=ID_USUARIO_AUTORIZA",
            "--respaldo=C:\\xampp\\panel_db_backups\\ARCHIVO.sql"
        ),
        "requiere_respaldo_externo" => true,
        "regla_respaldo" => "Escritura masiva sobre base operativa/productiva."
    ));
}

$_SERVER["SERVER_NAME"] = "panel.com.local";
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/RentabilidadErp.php";
require_once __DIR__ . "/uat_listas_precios_mayoreo_pruba_preflight_readonly.php";

class MayoreoPrubaApplyListas extends ListasPreciosErp {
    private $conexionForzada = null;

    public function __construct() {
        parent::__construct();
        $this->conexionForzada = abrirConexionMayoreoPruba();
    }

    protected function getConexion() {
        $db = parent::getConexion();
        return $db ?: $this->conexionForzada;
    }

    public function conexionApply() {
        return $this->getConexion();
    }
}

class MayoreoPrubaApplyRentabilidad extends RentabilidadErp {
    private $conexionForzada = null;

    public function __construct() {
        parent::__construct();
        $this->conexionForzada = abrirConexionMayoreoPruba();
    }

    protected function getConexion() {
        $db = parent::getConexion();
        return $db ?: $this->conexionForzada;
    }

    public function conexionApply() {
        return $this->getConexion();
    }
}

$listas = new MayoreoPrubaApplyListas();
$rentabilidad = new MayoreoPrubaApplyRentabilidad();
$db = $listas->conexionApply();
$bloqueos = array();

if (!$db) {
    $bloqueos[] = "No hay conexion MySQL disponible para aplicar la lista";
}
if ($db && !tablaExiste($db, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta auditoria comercial erp_listas_precios_eventos";
}
if ($db && buscarListaApply($db, $codigo)) {
    $bloqueos[] = "Ya existe una lista con codigo " . $codigo . "; no se duplica automaticamente";
}

$preflight = $db ? prepararPreciosMayoreoPruba($db, $rentabilidad, $codigo, $nombre, $canal, $margen, $redondeo, 0) : array("ok" => false, "resumen" => null, "items" => array());
$items = isset($preflight["items"]) && is_array($preflight["items"]) ? $preflight["items"] : array();
if (count($items) <= 0) {
    $bloqueos[] = "Preflight no genero precios; revisar costos antes de crear lista";
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se creo lista mayoreo_pruba; faltan precondiciones.",
        "bloqueos" => $bloqueos,
        "preflight_resumen" => isset($preflight["resumen"]) ? $preflight["resumen"] : null,
        "respaldo" => $respaldo
    ));
}

$entradaLista = array(
    "codigo" => $codigo,
    "nombre" => $nombre,
    "canal" => $canal,
    "id_almacen" => "",
    "prioridad" => $prioridad,
    "estatus" => "borrador",
    "motivo" => "Creacion masiva lista mayoreo_pruba margen bruto " . $margen . "%",
    "observaciones" => "Lista de prueba para sistema de distribucion. Respaldo: " . $respaldo
);

$resultadoLista = $listas->listaGuardarAutorizado($entradaLista, $idUsuario);
if (!empty($resultadoLista["error"])) {
    responder(array(
        "ok" => false,
        "modo" => "error",
        "mensaje" => "No se creo encabezado de lista",
        "resultado_lista" => $resultadoLista,
        "preflight_resumen" => $preflight["resumen"],
        "respaldo" => $respaldo
    ));
}

$idLista = intval($resultadoLista["depurar"]["id_lista_precio"]);
$guardados = 0;
$errores = array();
$chunks = array_chunk($items, 900);
foreach ($chunks as $chunkIndex => $chunk) {
    $payload = array();
    foreach ($chunk as $item) {
        $payload[] = array(
            "id_sku" => intval($item["id_sku"]),
            "id_producto_erp" => intval($item["id_producto_erp"]),
            "precio" => floatval($item["precio"]),
            "moneda" => "MXN",
            "estatus" => "activo"
        );
    }
    $resultadoLote = $listas->detallesLoteGuardarAutorizado(array(
        "id_lista_precio" => $idLista,
        "precios_json" => json_encode($payload, JSON_UNESCAPED_UNICODE),
        "motivo" => "Carga masiva mayoreo_pruba margen bruto " . $margen . "%"
    ), $idUsuario);
    $guardados += intval(isset($resultadoLote["depurar"]["guardados"]) ? $resultadoLote["depurar"]["guardados"] : 0);
    if (!empty($resultadoLote["error"]) || !empty($resultadoLote["depurar"]["errores"])) {
        $errores[] = array("chunk" => $chunkIndex + 1, "resultado" => $resultadoLote);
    }
}

$resultadoActivacion = null;
if ($activar === 1 && empty($errores)) {
    $listaCreada = buscarListaApply($db, $codigo);
    $entradaActivar = array(
        "id_lista_precio" => $idLista,
        "codigo" => $codigo,
        "nombre" => $nombre,
        "canal" => $canal,
        "id_almacen" => "",
        "prioridad" => $prioridad,
        "fecha_inicio" => isset($listaCreada["fecha_inicio"]) ? $listaCreada["fecha_inicio"] : "",
        "fecha_fin" => isset($listaCreada["fecha_fin"]) ? $listaCreada["fecha_fin"] : "",
        "estatus" => "activa",
        "confirmar_activacion" => "1",
        "motivo" => "Activacion mayoreo_pruba autorizada para prueba distribucion",
        "observaciones" => "Activada desde apply autorizado. Respaldo: " . $respaldo
    );
    $resultadoActivacion = $listas->listaGuardarAutorizado($entradaActivar, $idUsuario);
}

responder(array(
    "ok" => empty($errores) && ($activar !== 1 || empty($resultadoActivacion["error"])),
    "modo" => "listas_precios_mayoreo_pruba_apply_authorized",
    "id_lista_precio" => $idLista,
    "codigo" => $codigo,
    "guardados" => $guardados,
    "errores" => $errores,
    "activacion" => $resultadoActivacion,
    "preflight_resumen" => $preflight["resumen"],
    "omitidos_muestra" => array_slice($preflight["omitidos"], 0, 20),
    "respaldo" => $respaldo,
    "siguiente_paso" => $activar === 1
        ? "Probar resolutor de precios para canal mayoreo/distribucion."
        : "Revisar lista en Comercial y activar si la prueba lo requiere."
));

function respaldoValido($ruta) {
    return $ruta !== "" && preg_match('/^[A-Za-z]:[\\\\\\/]/', $ruta) === 1 && is_file($ruta) && is_readable($ruta) && filesize($ruta) > 1024;
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function buscarListaApply($db, $codigo) {
    $stmt = $db->prepare("SELECT * FROM erp_listas_precios WHERE UPPER(codigo)=UPPER(:codigo) LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
