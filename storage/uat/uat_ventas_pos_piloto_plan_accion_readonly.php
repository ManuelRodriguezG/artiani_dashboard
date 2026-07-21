<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: generar plan accionable para pasar POS de UAT controlado a piloto real acotado.
 * Impacto: consolida pendientes, autorizaciones sugeridas y checks posteriores sin escribir BD.
 * Contrato: read-only; no abre turnos, no carga stock, no corrige usuarios, no resuelve inventario y no registra evidencias.
 */

date_default_timezone_set("America/Mexico_City");

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";

class UatVentasPosPilotoPlanDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$args = parseArgs($argv);
$idUsuario = entero($args, "id_usuario", 1);
$idAlmacen = entero($args, "id_almacen", 5);
$idSku = entero($args, "id_sku", 1760);
$cantidad = decimal($args, "cantidad", 1);
$precio = decimal($args, "precio", 295);
$montoInicial = decimal($args, "monto_inicial", 500);
$cliente = texto($args, "cliente", "Cliente piloto POS");
$usuarios = listaEnteros(texto($args, "usuarios", "1,2,3"));

$db = (new UatVentasPosPilotoPlanDb())->db();
$seguridad = new SeguridadPermisos();

$turnosAbiertos = todos($db, "SELECT id_turno_caja, folio, id_almacen, id_caja, id_usuario_apertura, monto_inicial, monto_esperado, fecha_apertura
    FROM erp_pos_turnos
    WHERE estatus='abierto'
    ORDER BY fecha_apertura DESC, id_turno_caja DESC");
$stock = uno($db, "SELECT COALESCE(SUM(cantidad_disponible),0) disponible
    FROM erp_inventario_existencias
    WHERE id_almacen_clave=:almacen AND id_sku_erp=:sku", array(":almacen" => $idAlmacen, ":sku" => $idSku));
$disponible = isset($stock["disponible"]) ? (float) $stock["disponible"] : 0.0;
$pendientesInventario = todos($db, "SELECT id_inventario_pendiente, folio, cantidad_pendiente, estatus
    FROM erp_pos_inventario_pendientes
    WHERE id_almacen=:almacen AND id_sku_erp=:sku AND estatus IN ('pendiente_revision','en_revision')
    ORDER BY id_inventario_pendiente DESC", array(":almacen" => $idAlmacen, ":sku" => $idSku));
$evidenciasPendientes = todos($db, "SELECT id_movimiento_caja, id_turno_caja, tipo, categoria, motivo, monto, referencia, evidencia_estado
    FROM erp_pos_movimientos_caja
    WHERE requiere_evidencia=1 AND evidencia_estado IN ('pendiente','correccion_solicitada')
    ORDER BY id_movimiento_caja ASC");

$usuariosDetalle = usuariosDetalle($seguridad, $usuarios);
$pendientes = pendientes($turnosAbiertos, $disponible, $pendientesInventario, $evidenciasPendientes, $usuariosDetalle, $idSku, $idAlmacen);
$acciones = accionesRecomendadas($idUsuario, $idAlmacen, $idSku, $cantidad, $precio, $montoInicial, $cliente, $turnosAbiertos, $disponible, $pendientesInventario, $evidenciasPendientes, $usuariosDetalle);

$respuesta = array(
    "ok" => true,
    "modo" => "ventas_pos_piloto_plan_accion_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio" => $precio,
        "monto_inicial" => $montoInicial,
        "usuarios" => $usuarios,
    ),
    "resumen" => array(
        "decision" => empty($pendientes) ? "listo_para_piloto_sin_pendientes_operativos" : "listo_para_piloto_con_pendientes_accionables",
        "pendientes_total" => count($pendientes),
        "acciones_total" => count($acciones),
        "requiere_autorizacion_bd" => true,
        "turnos_abiertos" => count($turnosAbiertos),
        "stock_disponible_sku" => $disponible,
        "pendientes_inventario" => count($pendientesInventario),
        "evidencias_caja_pendientes" => count($evidenciasPendientes),
    ),
    "pendientes" => $pendientes,
    "acciones_recomendadas" => $acciones,
    "checks_readonly_post_accion" => array(
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen={$idAlmacen} --id_sku={$idSku} --usuarios=" . implode(",", $usuarios),
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1",
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_piloto_preflight_compacto_readonly.php",
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_resuelve_pendientes" => true,
        "no_registra_evidencias" => true,
        "no_corrige_usuarios" => true,
        "no_cobra" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function accionesRecomendadas($idUsuario, $idAlmacen, $idSku, $cantidad, $precio, $montoInicial, $cliente, $turnosAbiertos, $disponible, $pendientesInventario, $evidenciasPendientes, $usuariosDetalle) {
    $acciones = array();
    foreach ($pendientesInventario as $pendiente) {
        $acciones[] = accion(
            "resolver_pendiente_inventario",
            "Inventario",
            "Resolver mini inventario POS vigente con conteo fisico real antes de ampliar piloto.",
            "AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario={$idUsuario} folio={$pendiente["folio"]} cantidad_fisica=CONTEO_REAL decision=ajustar_a_conteo confirmacion=\"RESOLVER PENDIENTE\" motivo=\"Resolver mini inventario POS pendiente antes de piloto\""
        );
    }
    foreach ($evidenciasPendientes as $evidencia) {
        $referencia = $evidencia["referencia"] !== "" ? $evidencia["referencia"] : ("MOV-" . $evidencia["id_movimiento_caja"]);
        $acciones[] = accion(
            "registrar_evidencia_caja",
            "Caja",
            "Adjuntar o registrar evidencia administrativa del movimiento de caja pendiente.",
            "AUTORIZO REGISTRAR EVIDENCIA CAJA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_EVIDENCIA_REAL id_usuario={$idUsuario} id_movimiento_caja={$evidencia["id_movimiento_caja"]} tipo_evidencia=comprobante_caja referencia_externa={$referencia} descripcion=\"Evidencia administrativa previa a piloto POS\""
        );
    }
    foreach ($usuariosDetalle as $usuario) {
        if (!empty($usuario["problemas"])) {
            $acciones[] = array(
                "codigo" => "corregir_usuario_visual",
                "area" => "Seguridad",
                "descripcion" => "Corregir nombre visible del usuario " . $usuario["id_usuario"] . " desde Seguridad > Usuarios.",
                "requiere_autorizacion_bd" => true,
                "autorizacion_sugerida" => "Usar UI Seguridad > Usuarios para corregir nombre visible de usuario " . $usuario["id_usuario"] . " con respaldo UAT POS vigente y evidencia antes/despues.",
                "prioridad" => "media",
            );
        }
    }
    if ($disponible < $cantidad) {
        $acciones[] = accion(
            "cargar_stock_piloto",
            "Inventario",
            "Cargar stock UAT suficiente por kardex antes de venta piloto normal.",
            "AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_almacen={$idAlmacen} id_sku={$idSku} cantidad={$cantidad} referencia=INV-INICIAL-POS-PILOTO-" . date("Ymd") . "-A{$idAlmacen}-S{$idSku}"
        );
    }
    if (empty($turnosAbiertos)) {
        $acciones[] = accion(
            "abrir_turno",
            "Caja",
            "Abrir turno de caja para iniciar prueba real.",
            "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario={$idUsuario} y monto_inicial={$montoInicial} observaciones=\"Apertura piloto POS\""
        );
    }
    $total = redondear($cantidad * $precio);
    $acciones[] = accion(
        "venta_piloto_controlada",
        "POS",
        "Ejecutar una venta controlada solo despues de turno abierto y stock/resolucion listos.",
        "AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_sku={$idSku} cantidad={$cantidad} precio={$precio} pago={$total} cliente=\"{$cliente}\""
    );
    $acciones[] = array(
        "codigo" => "cerrar_turno_con_diferencia_si_aplica",
        "area" => "Caja",
        "descripcion" => "Cerrar turno manualmente; puede cerrar aunque no cuadre en cero, dejando diferencia para reportes/revision.",
        "requiere_autorizacion_bd" => true,
        "autorizacion_sugerida" => "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} monto_contado=MONTO_CONTADO_REAL observaciones=\"Cierre piloto POS\"",
        "prioridad" => "alta",
    );
    return $acciones;
}

function pendientes($turnosAbiertos, $disponible, $pendientesInventario, $evidenciasPendientes, $usuariosDetalle, $idSku, $idAlmacen) {
    $pendientes = array();
    if (empty($turnosAbiertos)) {
        $pendientes[] = itemPendiente("TURNO_ABIERTO", "Caja", "No hay turno abierto para cobrar.");
    }
    if ($disponible <= 0) {
        $pendientes[] = itemPendiente("STOCK_SKU", "Inventario", "SKU {$idSku} sin disponible en almacen {$idAlmacen}.");
    }
    foreach ($pendientesInventario as $item) {
        $pendientes[] = itemPendiente("INVENTARIO_PENDIENTE", "Inventario", "Pendiente {$item["folio"]} abierto por " . redondear($item["cantidad_pendiente"]) . ".");
    }
    foreach ($evidenciasPendientes as $item) {
        $ref = $item["referencia"] !== "" ? $item["referencia"] : ("movimiento " . $item["id_movimiento_caja"]);
        $pendientes[] = itemPendiente("EVIDENCIA_CAJA", "Caja", "Evidencia pendiente {$ref} por $" . redondear($item["monto"]) . ".");
    }
    foreach ($usuariosDetalle as $usuario) {
        if (!empty($usuario["problemas"])) {
            $pendientes[] = itemPendiente("USUARIO_NOMBRE_VISUAL", "Seguridad", "Usuario {$usuario["id_usuario"]} requiere correccion visual: {$usuario["nombre_visible"]}.");
        }
    }
    return $pendientes;
}

function usuariosDetalle($seguridad, $usuarios) {
    $respuesta = $seguridad->listarUsuariosRoles();
    $index = array();
    foreach (valor($respuesta, "depurar", array()) as $usuario) {
        $index[(int) valor($usuario, "id_usuario", 0)] = $usuario;
    }
    $out = array();
    foreach ($usuarios as $id) {
        $usuario = isset($index[$id]) ? $index[$id] : array("id_usuario" => $id, "nombres" => "", "apellido_paterno" => "", "apellido_materno" => "", "estatus" => "no_encontrado");
        $nombre = trim(implode(" ", array_filter(array(valor($usuario, "nombres", ""), valor($usuario, "apellido_paterno", ""), valor($usuario, "apellido_materno", "")))));
        $problemas = array();
        if ($nombre === "") {
            $problemas[] = "nombre_vacio";
        }
        if (preg_match('/Ãƒ|Ã‚|Ã|Â|├|┬|â”œ|â”¬|ï¿½/', $nombre)) {
            $problemas[] = "posible_mojibake";
        }
        $out[] = array(
            "id_usuario" => $id,
            "nombre_visible" => $nombre,
            "estatus" => valor($usuario, "estatus", ""),
            "problemas" => $problemas,
        );
    }
    return $out;
}

function accion($codigo, $area, $descripcion, $autorizacion) {
    return array(
        "codigo" => $codigo,
        "area" => $area,
        "descripcion" => $descripcion,
        "requiere_autorizacion_bd" => true,
        "autorizacion_sugerida" => $autorizacion,
        "prioridad" => "alta",
    );
}

function itemPendiente($codigo, $area, $mensaje) {
    return array("codigo" => $codigo, "area" => $area, "mensaje" => $mensaje);
}

function parseArgs($argv) {
    $out = array();
    foreach (array_slice($argv, 1) as $arg) {
        if (strpos($arg, "--") !== 0) {
            continue;
        }
        $partes = explode("=", substr($arg, 2), 2);
        $out[$partes[0]] = isset($partes[1]) ? trim($partes[1], "\"' ") : true;
    }
    return $out;
}

function entero($args, $clave, $default) {
    return isset($args[$clave]) ? (int) $args[$clave] : $default;
}

function decimal($args, $clave, $default) {
    return isset($args[$clave]) ? (float) $args[$clave] : $default;
}

function texto($args, $clave, $default) {
    return isset($args[$clave]) ? (string) $args[$clave] : $default;
}

function listaEnteros($texto) {
    $out = array();
    foreach (explode(",", $texto) as $item) {
        $id = (int) trim($item);
        if ($id > 0) {
            $out[] = $id;
        }
    }
    return $out ?: array(1);
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

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}

function redondear($valor) {
    return rtrim(rtrim(number_format((float) $valor, 2, ".", ""), "0"), ".");
}
