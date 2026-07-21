<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: resumir pendientes operativos antes de iniciar piloto POS.
 * Impacto: evita confundir condiciones de operacion con fallas de codigo.
 * Contrato: read-only; no abre turnos, no corrige usuarios, no adjunta evidencias, no ajusta inventario.
 */

date_default_timezone_set("America/Mexico_City");

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";

class UatVentasPosPendientesPilotoDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosPendientesPilotoDb())->db();
$idAlmacen = leerEntero("id_almacen", 5);
$idSku = leerEntero("id_sku", 1760);
$usuarios = leerUsuarios("usuarios", array(1, 2, 3));

$pendientes = array();
$avisos = array();
$seguridad = new SeguridadPermisos();
$usuariosRoles = $seguridad->listarUsuariosRoles();
$usuariosIndexados = indexarUsuarios(valor($usuariosRoles, "depurar", array()));

$turnosAbiertos = contar($db, "SELECT COUNT(*) FROM erp_pos_turnos WHERE estatus='abierto'");
if ($turnosAbiertos <= 0) {
    $pendientes[] = pendiente("TURNO_ABIERTO", "Abrir turno antes de cobrar.", "operativo", "Ventas > Caja y turnos");
}

$stock = consultarUno($db, "SELECT COALESCE(SUM(cantidad_disponible),0) disponible
    FROM erp_inventario_existencias
    WHERE id_almacen_clave=:almacen AND id_sku_erp=:sku", array(":almacen" => $idAlmacen, ":sku" => $idSku));
$disponible = isset($stock["disponible"]) ? (float) $stock["disponible"] : 0.0;
if ($disponible <= 0) {
    $pendientes[] = pendiente("STOCK_SKU", "SKU " . $idSku . " sin disponible en almacen " . $idAlmacen . ".", "inventario", "Inventario/Existencias");
}

$pendientesInv = consultarTodos($db, "SELECT folio, cantidad_pendiente, estatus
    FROM erp_pos_inventario_pendientes
    WHERE id_almacen=:almacen AND id_sku_erp=:sku AND estatus IN ('pendiente_revision','en_revision')
    ORDER BY id_inventario_pendiente DESC", array(":almacen" => $idAlmacen, ":sku" => $idSku));
foreach ($pendientesInv as $item) {
    $pendientes[] = pendiente("INVENTARIO_PENDIENTE", "Resolver o mantener identificado " . $item["folio"] . " (" . (float) $item["cantidad_pendiente"] . ").", "inventario", "Inventario/Existencias#pendientes-pos");
}

$evidencias = consultarTodos($db, "SELECT id_movimiento_caja, referencia, monto, evidencia_estado
    FROM erp_pos_movimientos_caja
    WHERE requiere_evidencia=1 AND evidencia_estado IN ('pendiente','correccion_solicitada')
    ORDER BY id_movimiento_caja ASC");
foreach ($evidencias as $item) {
    $pendientes[] = pendiente("EVIDENCIA_CAJA", "Cerrar evidencia " . ($item["referencia"] ?: ("movimiento " . $item["id_movimiento_caja"])) . " por $" . formato($item["monto"]) . ".", "administrativo", "Ventas > Evidencias caja");
}

$usuariosDetalle = array();
foreach ($usuarios as $idUsuario) {
    $usuario = isset($usuariosIndexados[$idUsuario]) ? $usuariosIndexados[$idUsuario] : null;
    if (!$usuario) {
        $pendientes[] = pendiente("USUARIO_NO_EXISTE", "Usuario " . $idUsuario . " no encontrado.", "seguridad", "Seguridad > Usuarios");
        continue;
    }
    $nombreVisible = nombreUsuario($usuario);
    $problemas = detectarProblemasNombre($nombreVisible);
    $usuariosDetalle[] = array(
        "id_usuario" => (int) $usuario["id_usuario"],
        "nombre_visible" => $nombreVisible,
        "estatus" => $usuario["estatus"],
        "problemas" => $problemas,
    );
    if (!empty($problemas)) {
        $pendientes[] = pendiente("USUARIO_NOMBRE_VISUAL", "Corregir nombre visible de usuario " . $idUsuario . ": " . $nombreVisible, "seguridad", "Seguridad > Usuarios");
    }
}

if (empty($pendientes)) {
    $avisos[] = "Sin pendientes operativos detectados para piloto controlado.";
}

$respuesta = array(
    "ok" => true,
    "modo" => "ventas_pos_pendientes_piloto_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "usuarios" => $usuarios,
    ),
    "resumen" => array(
        "pendientes_total" => count($pendientes),
        "turnos_abiertos" => $turnosAbiertos,
        "sku_disponible" => $disponible,
        "inventario_pendientes_abiertos" => count($pendientesInv),
        "evidencias_pendientes" => count($evidencias),
    ),
    "pendientes" => $pendientes,
    "usuarios" => $usuariosDetalle,
    "avisos" => $avisos,
    "autorizaciones_sugeridas" => array(
        "abrir_turno" => "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones=\"Piloto POS\"",
        "cargar_stock" => "AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=" . $idAlmacen . " id_sku=" . $idSku . " cantidad=1 referencia=INV-INICIAL-POS-UAT",
        "resolver_pendiente" => "AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario=1 folio=PINV-20260717-000001 cantidad_fisica=CONTEO_REAL decision=ajustar_a_conteo confirmacion=\"RESOLVER PENDIENTE\" motivo=\"Resolver mini inventario POS pendiente\"",
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_resuelve_pendientes" => true,
        "no_corrige_usuarios" => true,
        "no_adjunta_evidencias" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function leerEntero($nombre, $default) {
    global $argv;
    foreach ($argv as $arg) {
        if (strpos($arg, "--" . $nombre . "=") === 0) {
            return (int) trim(substr($arg, strlen($nombre) + 3), "\"' ");
        }
    }
    return $default;
}

function leerUsuarios($nombre, $default) {
    global $argv;
    foreach ($argv as $arg) {
        if (strpos($arg, "--" . $nombre . "=") === 0) {
            $valor = trim(substr($arg, strlen($nombre) + 3), "\"' ");
            $ids = array();
            foreach (explode(",", $valor) as $id) {
                $id = (int) trim($id);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            return empty($ids) ? $default : $ids;
        }
    }
    return $default;
}

function consultarUno($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

function consultarTodos($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contar($db, $sql) {
    $stmt = $db->query($sql);
    return (int) $stmt->fetchColumn();
}

function pendiente($codigo, $mensaje, $area, $ruta) {
    return array("codigo" => $codigo, "area" => $area, "mensaje" => $mensaje, "ruta_sugerida" => $ruta);
}

function formato($monto) {
    return number_format((float) $monto, 2, ".", "");
}

function detectarProblemasNombre($nombre) {
    $problemas = array();
    if (preg_match('/Ã|Â|├|┬|�/', $nombre)) {
        $problemas[] = "posible_mojibake";
    }
    if (strlen(trim($nombre)) < 3) {
        $problemas[] = "nombre_corto";
    }
    return $problemas;
}

function indexarUsuarios($filas) {
    $out = array();
    foreach (is_array($filas) ? $filas : array() as $fila) {
        $out[(int) valor($fila, "id_usuario", 0)] = $fila;
    }
    return $out;
}

function nombreUsuario($usuario) {
    $partes = array(valor($usuario, "nombres", ""), valor($usuario, "apellido_paterno", ""), valor($usuario, "apellido_materno", ""));
    $nombre = trim(implode(" ", array_filter($partes)));
    if ($nombre !== "") {
        return $nombre;
    }
    $fallback = valor($usuario, "nombre", "");
    return trim((string) $fallback);
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
