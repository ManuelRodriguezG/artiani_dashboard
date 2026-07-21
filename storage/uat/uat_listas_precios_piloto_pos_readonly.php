<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar una combinacion real lista/SKU/cliente/almacen/canal antes de venta POS piloto.
 * Impacto: confirma resolutor, origen, snapshot y que el dry-run no crea ventas ni detalles.
 * Contrato: read-only; no escribe BD, no ejecuta DDL, no modifica listas, CRM, POS, inventario ni ecommerce.
 *
 * Uso:
 * php storage/uat/uat_listas_precios_piloto_pos_readonly.php --id_lista_precio=2 --id_sku=1760 --id_cliente_crm=2 --id_almacen=5 --canal=pos --origen_esperado=lista_segmento_cliente
 */

$params = argumentosPiloto($argv);
$idListaEsperada = intval(valorPiloto($params, "id_lista_precio", 0));
$idSku = intval(valorPiloto($params, "id_sku", 0));
$idClienteCrm = intval(valorPiloto($params, "id_cliente_crm", valorPiloto($params, "id_cliente", 0)));
$idAlmacen = intval(valorPiloto($params, "id_almacen", 0));
$canal = trim((string) valorPiloto($params, "canal", "pos"));
$origenEsperado = trim((string) valorPiloto($params, "origen_esperado", ""));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/VentasErp.php";

class LpPilotoPosReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpPilotoPosReadonlyDb())->db();
$listas = new ListasPreciosErp();
$ventas = new VentasErp();
$baselineAntes = baselineVentasPiloto($db);
$fase1 = $listas->fase1ReadinessReadOnly();
$lista = $idListaEsperada > 0 ? $listas->revisionListaReadOnly($idListaEsperada) : null;
$precio = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => $canal,
    "id_cliente" => $idClienteCrm,
    "items" => array(array("id_sku" => $idSku, "cantidad" => 1))
));
$baselineDespues = baselineVentasPiloto($db);

$partida = valorRutaPiloto($precio, array("depurar", "partidas", 0), array());
$origen = trim((string) valorPiloto($partida, "regla_precio_origen", ""));
$idListaResuelta = intval(valorPiloto($partida, "id_lista_precio", 0));
$snapshot = trim((string) valorPiloto($partida, "lista_precio_snapshot", ""));
$precioAplicado = floatval(valorPiloto($partida, "precio_aplicado", 0));
$bloqueosPrecio = valorRutaPiloto($precio, array("depurar", "bloqueos"), array());
$fase1Ok = !empty($fase1["depurar"]["puede_piloto_pos"]);
$listaOk = !$lista || empty($lista["error"]);
$baselineIntacto = $baselineAntes == $baselineDespues;

$checks = array(
    checkPiloto("conexion_mysql", (bool) $db, "Conexion MySQL disponible"),
    checkPiloto("parametros_minimos", $idSku > 0 && $idAlmacen > 0 && $canal !== "", "Parametros minimos SKU/almacen/canal"),
    checkPiloto("fase1_lista", $fase1Ok, "Semaforo fase 1 permite piloto POS"),
    checkPiloto("lista_revisable", $listaOk, "Lista esperada consultable si se proporciono"),
    checkPiloto("resolutor_sin_error", empty($precio["error"]), "Resolutor POS responde sin error fatal"),
    checkPiloto("resolutor_sin_bloqueos", empty($bloqueosPrecio), "Resolutor no reporta bloqueos de precio"),
    checkPiloto("precio_mayor_cero", $precioAplicado > 0, "Precio aplicado mayor a cero"),
    checkPiloto("snapshot_presente", $snapshot !== "", "Snapshot de lista/origen presente"),
    checkPiloto("origen_esperado", $origenEsperado === "" || $origen === $origenEsperado, "Origen coincide con origen esperado si se indico"),
    checkPiloto("lista_esperada", $idListaEsperada <= 0 || $idListaResuelta === $idListaEsperada, "Lista resuelta coincide con lista esperada si se indico"),
    checkPiloto("sin_ventas_nuevas", $baselineIntacto, "Dry-run no crea ventas ni detalles")
);

$fallos = array_values(array_filter($checks, function ($check) {
    return !$check["ok"];
}));

echo json_encode(array(
    "ok" => empty($fallos),
    "modo" => "read-only",
    "resultado" => empty($fallos) ? "PASS_PILOTO_POS_LISTAS_PRECIOS" : "FAIL_PILOTO_POS_LISTAS_PRECIOS",
    "parametros" => array(
        "id_lista_precio" => $idListaEsperada,
        "id_sku" => $idSku,
        "id_cliente_crm" => $idClienteCrm,
        "id_almacen" => $idAlmacen,
        "canal" => $canal,
        "origen_esperado" => $origenEsperado
    ),
    "resolucion" => array(
        "id_lista_precio" => $idListaResuelta,
        "lista_precio_snapshot" => $snapshot,
        "regla_precio_origen" => $origen,
        "precio_aplicado" => $precioAplicado,
        "partida" => $partida
    ),
    "baseline_ventas" => array(
        "antes" => $baselineAntes,
        "despues" => $baselineDespues,
        "intacto" => $baselineIntacto
    ),
    "checks" => $checks,
    "fallos" => $fallos,
    "bloqueos_resolutor" => $bloqueosPrecio,
    "siguiente_paso_si_pasa" => "Ejecutar venta POS UAT autorizada y validar snapshot persistido en erp_ventas_detalle.",
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_crea_ventas" => true,
        "no_mueve_inventario" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($fallos) ? 0 : 1);

function argumentosPiloto($argv) {
    $params = array();
    foreach (array_slice($argv, 1) as $arg) {
        if (strpos($arg, "--") === 0 && strpos($arg, "=") !== false) {
            $partes = explode("=", substr($arg, 2), 2);
            $params[$partes[0]] = $partes[1];
        }
    }
    return $params;
}

function valorPiloto($array, $key, $default = null) {
    return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
}

function valorRutaPiloto($array, $ruta, $default = null) {
    $actual = $array;
    foreach ($ruta as $key) {
        if (!is_array($actual) || !array_key_exists($key, $actual)) {
            return $default;
        }
        $actual = $actual[$key];
    }
    return $actual;
}

function checkPiloto($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function baselineVentasPiloto($db) {
    if (!$db) {
        return array("ventas_total" => null, "ventas_max_id" => null, "detalle_total" => null, "detalle_max_id" => null);
    }
    return array(
        "ventas_total" => tablaPilotoExiste($db, "erp_ventas") ? intval($db->query("SELECT COUNT(*) FROM erp_ventas")->fetchColumn()) : null,
        "ventas_max_id" => tablaPilotoExiste($db, "erp_ventas") ? intval($db->query("SELECT COALESCE(MAX(id_venta), 0) FROM erp_ventas")->fetchColumn()) : null,
        "detalle_total" => tablaPilotoExiste($db, "erp_ventas_detalle") ? intval($db->query("SELECT COUNT(*) FROM erp_ventas_detalle")->fetchColumn()) : null,
        "detalle_max_id" => tablaPilotoExiste($db, "erp_ventas_detalle") ? intval($db->query("SELECT COALESCE(MAX(id_venta_detalle), 0) FROM erp_ventas_detalle")->fetchColumn()) : null
    );
}

function tablaPilotoExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}
