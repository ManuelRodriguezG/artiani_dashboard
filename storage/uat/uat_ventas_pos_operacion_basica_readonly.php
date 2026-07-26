<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: resumir en lenguaje operativo si POS puede cobrar ahora en una tienda/caja.
 * Impacto: consolida turno, asignacion, stock, ticket, pendientes y evidencias sin ejecutar acciones reales.
 * Contrato: consulta; no abre turnos, no cobra, no carga stock, no ajusta inventario y no mueve caja.
 */

date_default_timezone_set("America/Mexico_City");

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosOperacionBasicaDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$args = parseArgs($argv);
$compacto = !empty($args["compact"]);
$idUsuario = entero($args, "id_usuario", 1);
$idAlmacen = entero($args, "id_almacen", 5);
$idSku = entero($args, "id_sku", 1760);
$cantidad = decimal($args, "cantidad", 1);
$usuarios = listaEnteros(valor($args, "usuarios", (string) $idUsuario));

$db = (new UatVentasPosOperacionBasicaDb())->db();
$ventas = new VentasErp();

if (!$db instanceof PDO) {
    responderSinConexion($compacto, $idUsuario, $idAlmacen, $idSku, $cantidad, $usuarios);
}

$tablas = array(
    "erp_pos_cajas",
    "erp_pos_terminales",
    "erp_pos_usuarios_cajas",
    "erp_pos_turnos",
    "erp_pos_movimientos_caja",
    "erp_ventas",
    "erp_ventas_detalle",
    "erp_inventario_existencias",
    "erp_pos_inventario_pendientes",
    "erp_empresa_configuracion",
    "erp_pos_ticket_configuracion"
);

$schema = array();
foreach ($tablas as $tabla) {
    $schema[$tabla] = tablaExiste($db, $tabla);
}

$bloqueos = array();
$avisos = array();
$acciones = array();

foreach (array("erp_pos_turnos", "erp_ventas", "erp_ventas_detalle", "erp_inventario_existencias") as $tablaCritica) {
    if (empty($schema[$tablaCritica])) {
        $bloqueos[] = "Falta estructura critica: " . $tablaCritica;
    }
}

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$asignacionDepurar = valor($asignacion, "depurar", array());
$asignacionActual = valor($asignacionDepurar, "asignacion", null);
$turnoActual = valor($asignacionDepurar, "turno_abierto", null);
$operadores = operadoresPos($ventas, $usuarios, $idAlmacen, $asignacionActual);

if (empty($asignacionActual)) {
    $bloqueos[] = "Usuario sin tienda/caja/terminal asignada.";
    $acciones[] = "Asignar usuario a tienda/caja/terminal en Ventas > Configuracion POS.";
}

if (empty($turnoActual)) {
    $bloqueos[] = "No hay turno abierto para cobrar.";
    $acciones[] = "Abrir turno desde Ventas > Caja y turnos con el monto inicial real.";
}

$stockDisponible = 0.0;
if (!empty($schema["erp_inventario_existencias"])) {
    $stock = uno($db, "SELECT COALESCE(SUM(cantidad_disponible),0) disponible
        FROM erp_inventario_existencias
        WHERE id_almacen_clave=:almacen AND id_sku_erp=:sku", array(":almacen" => $idAlmacen, ":sku" => $idSku));
    $stockDisponible = decimalValor(valor($stock, "disponible", 0));
}

if ($stockDisponible < $cantidad) {
    $bloqueos[] = "SKU " . $idSku . " no tiene disponible suficiente en almacen " . $idAlmacen . ".";
    $acciones[] = "Usar un SKU con existencia, cargar stock autorizado o vender con politica de inventario pendiente si aplica.";
}

$pendientesInventario = array();
if (!empty($schema["erp_pos_inventario_pendientes"])) {
    $pendientesInventario = todos($db, "SELECT folio, cantidad_pendiente, estatus
        FROM erp_pos_inventario_pendientes
        WHERE id_almacen=:almacen AND id_sku_erp=:sku AND estatus IN ('pendiente_revision','en_revision')
        ORDER BY id_inventario_pendiente DESC", array(":almacen" => $idAlmacen, ":sku" => $idSku));
    foreach ($pendientesInventario as $pendiente) {
        $avisos[] = "Inventario pendiente abierto: " . $pendiente["folio"] . " por " . formatoNumero($pendiente["cantidad_pendiente"]) . ".";
    }
}

$evidenciasPendientes = array();
if (!empty($schema["erp_pos_movimientos_caja"])) {
    $evidenciasPendientes = todos($db, "SELECT id_movimiento_caja, referencia, monto, evidencia_estado
        FROM erp_pos_movimientos_caja
        WHERE requiere_evidencia=1 AND evidencia_estado IN ('pendiente','correccion_solicitada')
        ORDER BY id_movimiento_caja ASC");
    foreach ($evidenciasPendientes as $evidencia) {
        $avisos[] = "Evidencia de caja pendiente: " . ($evidencia["referencia"] ?: ("movimiento " . $evidencia["id_movimiento_caja"])) . " por $" . formatoNumero($evidencia["monto"]) . ".";
    }
}

$ticket = array();
$ticketConfigurado = false;
if (!empty($schema["erp_empresa_configuracion"]) && !empty($schema["erp_pos_ticket_configuracion"])) {
    $scopeTicket = array(
        "id_almacen" => $idAlmacen,
        "id_caja" => enteroValor(valor($asignacionActual, "id_caja", 0)),
        "id_terminal_pos" => enteroValor(valor($asignacionActual, "id_terminal_pos", 0))
    );
    $respuestaTicket = $ventas->ticketConfiguracionEfectivaReadOnly($scopeTicket);
    $ticket = valor(valor($respuestaTicket, "depurar", array()), "configuracion", array());
    $ticketConfigurado = empty($respuestaTicket["error"]) && !empty($ticket);
    if (!$ticketConfigurado) {
        $avisos[] = "Ticket sin configuracion activa.";
        $acciones[] = "Configurar datos del negocio y formato en Ventas > Configuracion POS > Ticket y negocio.";
    }
} else {
    $avisos[] = "Estructura de ticket no disponible para validar configuracion.";
}

$ventasHoy = array("ventas" => 0, "total" => 0.0);
if (!empty($schema["erp_ventas"])) {
    $ventasHoy = uno($db, "SELECT COUNT(*) ventas, COALESCE(SUM(total),0) total
        FROM erp_ventas
        WHERE DATE(fecha_venta)=CURDATE() AND estatus NOT IN ('cancelada','anulada')");
}

$puedeCobrarAhora = empty($bloqueos);
if ($puedeCobrarAhora) {
    $acciones[] = "Puede cobrar venta normal con stock disponible. Al terminar, cerrar turno con monto contado real.";
}

$respuesta = array(
    "ok" => true,
    "modo" => "ventas_pos_operacion_basica_consulta",
    "consulta" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad
    ),
    "resumen_operativo" => array(
        "puede_cobrar_ahora" => $puedeCobrarAhora,
        "decision" => $puedeCobrarAhora ? "listo_para_cobro_normal" : "requiere_acciones_antes_de_cobrar",
        "turno_abierto" => !empty($turnoActual),
        "stock_disponible_sku" => $stockDisponible,
        "ticket_configurado" => $ticketConfigurado,
        "ticket_nombre_comercial" => valor($ticket, "nombre_comercial", ""),
        "ticket_ancho_mm" => valor($ticket, "ticket_ancho_mm", ""),
        "pendientes_inventario" => count($pendientesInventario),
        "evidencias_caja_pendientes" => count($evidenciasPendientes),
        "ventas_hoy" => enteroValor(valor($ventasHoy, "ventas", 0)),
        "total_ventas_hoy" => decimalValor(valor($ventasHoy, "total", 0))
    ),
    "contexto" => array(
        "asignacion" => $asignacionActual,
        "turno" => $turnoActual,
        "operadores" => $operadores,
        "schema" => $schema
    ),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "acciones_siguientes" => array_values(array_unique($acciones)),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_carga_stock" => true,
        "no_resuelve_inventario" => true,
        "no_mueve_caja" => true
    )
);

if ($compacto) {
    $respuesta = array(
        "ok" => true,
        "modo" => "ventas_pos_operacion_basica_consulta",
        "consulta" => true,
        "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
        "host" => "panel.com.local",
        "resumen_operativo" => $respuesta["resumen_operativo"],
        "operadores" => $operadores,
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "acciones_siguientes" => array_values(array_unique($acciones)),
        "contrato" => $respuesta["contrato"]
    );
}

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function parseArgs($argv) {
    $out = array();
    foreach ($argv as $arg) {
        if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) {
            continue;
        }
        list($k, $v) = explode("=", substr($arg, 2), 2);
        $out[$k] = trim($v, "\"' ");
    }
    return $out;
}

function entero($args, $key, $default) {
    return isset($args[$key]) ? (int) $args[$key] : $default;
}

function decimal($args, $key, $default) {
    return isset($args[$key]) ? (float) $args[$key] : $default;
}

function listaEnteros($valor) {
    $salida = array();
    foreach (explode(",", (string) $valor) as $item) {
        $id = (int) trim($item);
        if ($id > 0 && !in_array($id, $salida, true)) {
            $salida[] = $id;
        }
    }
    return $salida;
}

function enteroValor($valor) {
    return (int) $valor;
}

function decimalValor($valor) {
    return (float) $valor;
}

function tablaExiste($db, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        return false;
    }
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function uno($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: array();
}

function todos($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function formatoNumero($valor) {
    return number_format((float) $valor, 2, ".", "");
}

function responderSinConexion($compacto, $idUsuario, $idAlmacen, $idSku, $cantidad, $usuarios) {
    $respuesta = array(
        "ok" => false,
        "modo" => "ventas_pos_operacion_basica_consulta",
        "consulta" => true,
        "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
        "host" => "panel.com.local",
        "parametros" => array(
            "id_usuario" => $idUsuario,
            "id_almacen" => $idAlmacen,
            "id_sku" => $idSku,
            "cantidad" => $cantidad,
            "usuarios" => $usuarios
        ),
        "resumen_operativo" => array(
            "puede_cobrar_ahora" => false,
            "decision" => "sin_conexion_bd",
            "turno_abierto" => false,
            "stock_disponible_sku" => null,
            "ticket_configurado" => false,
            "pendientes_inventario" => null,
            "evidencias_caja_pendientes" => null,
            "ventas_hoy" => null,
            "total_ventas_hoy" => null
        ),
        "operadores" => array(),
        "bloqueos" => array("No hay conexion disponible con MariaDB/MySQL para consultar el POS."),
        "avisos" => array("Verifica que MySQL este levantado en XAMPP y que el host panel.com.local apunte a C:\\xampp\\htdocs\\panel_de_control."),
        "acciones_siguientes" => array("Levantar MySQL en XAMPP y repetir el semaforo operativo."),
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_abre_turno" => true,
            "no_cobra" => true,
            "no_carga_stock" => true,
            "no_resuelve_inventario" => true,
            "no_mueve_caja" => true
        )
    );
    if (!$compacto) {
        $respuesta["contexto"] = array("schema" => array());
    }
    echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($compacto ? 0 : 1);
}

function operadoresPos($ventas, $usuarios, $idAlmacen, $asignacionBase) {
    $baseCaja = enteroValor(valor($asignacionBase, "id_caja", 0));
    $baseTerminal = enteroValor(valor($asignacionBase, "id_terminal_pos", 0));
    $salida = array();
    foreach ($usuarios as $idUsuario) {
        $asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
        $depurar = valor($asignacion, "depurar", array());
        $actual = valor($depurar, "asignacion", array());
        $problemas = array();
        if (empty($actual)) {
            $problemas[] = "sin_asignacion_pos";
        } else {
            if (enteroValor(valor($actual, "id_almacen", 0)) !== (int) $idAlmacen) {
                $problemas[] = "almacen_distinto";
            }
            if ($baseCaja > 0 && enteroValor(valor($actual, "id_caja", 0)) !== $baseCaja) {
                $problemas[] = "caja_distinta";
            }
            if ($baseTerminal > 0 && enteroValor(valor($actual, "id_terminal_pos", 0)) !== $baseTerminal) {
                $problemas[] = "terminal_distinta";
            }
        }
        $salida[] = array(
            "id_usuario" => $idUsuario,
            "listo_misma_caja" => empty($problemas),
            "id_almacen" => enteroValor(valor($actual, "id_almacen", 0)),
            "id_caja" => enteroValor(valor($actual, "id_caja", 0)),
            "id_terminal_pos" => enteroValor(valor($actual, "id_terminal_pos", 0)),
            "caja" => valor($actual, "caja_nombre", ""),
            "terminal" => valor($actual, "terminal_nombre", ""),
            "problemas" => $problemas
        );
    }
    return $salida;
}
