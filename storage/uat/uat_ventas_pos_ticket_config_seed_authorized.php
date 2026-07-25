<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: sembrar configuracion base editable para ticket POS.
 * Impacto: crea/actualiza empresa principal y configuracion global de ticket; no crea ventas, no imprime y no toca inventario/caja.
 * Contrato: escritura bloqueada por token, respaldo vigente y datos minimos autorizados.
 */

$args = isset($argv) ? $argv : array();
$datos = array(
    "autorizar" => "",
    "respaldo" => "",
    "id_usuario" => 0,
    "nombre_comercial" => "",
    "ticket_ancho_mm" => "80",
    "ticket_columnas" => 42,
    "impresion_modo" => "navegador"
);

foreach ($args as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) {
        continue;
    }
    $partes = explode("=", substr($arg, 2), 2);
    $clave = $partes[0];
    $valor = trim($partes[1], "\"' ");
    if (array_key_exists($clave, $datos)) {
        $datos[$clave] = $valor;
    }
}

$datos["id_usuario"] = intval($datos["id_usuario"]);
$datos["ticket_columnas"] = intval($datos["ticket_columnas"]);
$datos["nombre_comercial"] = trim((string) $datos["nombre_comercial"]);
$datos["ticket_ancho_mm"] = trim((string) $datos["ticket_ancho_mm"]);
$datos["impresion_modo"] = trim((string) $datos["impresion_modo"]);

$validacionRespaldo = validarRespaldo($datos["respaldo"]);
$bloqueos = array();
if ($datos["autorizar"] !== "VENTAS_POS_TICKET_CONFIG_SEED") {
    $bloqueos[] = "token_invalido";
}
if (!$validacionRespaldo["ok"]) {
    $bloqueos[] = "respaldo_invalido";
}
if ($datos["id_usuario"] <= 0) {
    $bloqueos[] = "usuario_obligatorio";
}
if ($datos["nombre_comercial"] === "") {
    $bloqueos[] = "nombre_comercial_obligatorio";
}
if (!in_array($datos["ticket_ancho_mm"], array("58", "80", "personalizado"), true)) {
    $bloqueos[] = "ancho_ticket_invalido";
}
if ($datos["ticket_columnas"] < 30 || $datos["ticket_columnas"] > 60) {
    $bloqueos[] = "columnas_ticket_fuera_rango";
}
if (!in_array($datos["impresion_modo"], array("navegador", "pdf", "escpos_bridge"), true)) {
    $bloqueos[] = "modo_impresion_invalido";
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se sembro configuracion base de ticket POS.",
        "bloqueos" => $bloqueos,
        "validacion_respaldo" => $validacionRespaldo,
        "requerido" => array(
            "--autorizar=VENTAS_POS_TICKET_CONFIG_SEED",
            "--respaldo=UAT POS vigente o archivo .sql existente",
            "--id_usuario=1",
            "--nombre_comercial=ARTIANI",
            "--ticket_ancho_mm=80",
            "--ticket_columnas=42",
            "--impresion_modo=navegador"
        ),
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

try {
    $crud = new CRUD();
    $ref = new ReflectionClass($crud);
    $method = $ref->getMethod("getConexion");
    $method->setAccessible(true);
    $db = $method->invoke($crud);
    if (!$db instanceof PDO) {
        throw new Exception("Conexion MySQL no disponible");
    }

    foreach (array("erp_empresa_configuracion", "erp_pos_ticket_configuracion") as $tabla) {
        if (!tablaExiste($db, $tabla)) {
            throw new Exception("Tabla requerida no existe: " . $tabla);
        }
    }

    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM erp_empresa_configuracion WHERE clave_empresa='principal' LIMIT 1");
    $stmt->execute();
    $empresaAntes = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($empresaAntes) {
        $stmt = $db->prepare("UPDATE erp_empresa_configuracion
            SET nombre_comercial=:nombre,
                leyenda_ticket_general=COALESCE(NULLIF(leyenda_ticket_general, ''), :leyenda_general),
                leyenda_no_fiscal=COALESCE(NULLIF(leyenda_no_fiscal, ''), :leyenda_no_fiscal),
                estatus='activa',
                actualizado_por=:usuario,
                fecha_actualizacion=NOW()
            WHERE id_empresa_configuracion=:id");
        $stmt->execute(array(
            ":nombre" => $datos["nombre_comercial"],
            ":leyenda_general" => "Gracias por su compra.",
            ":leyenda_no_fiscal" => "Ticket no fiscal. Conserve este comprobante.",
            ":usuario" => $datos["id_usuario"],
            ":id" => intval($empresaAntes["id_empresa_configuracion"])
        ));
        $idEmpresa = intval($empresaAntes["id_empresa_configuracion"]);
        $accionEmpresa = "actualizada";
    } else {
        $stmt = $db->prepare("INSERT INTO erp_empresa_configuracion
            (clave_empresa, nombre_comercial, leyenda_ticket_general, leyenda_no_fiscal, estatus, creado_por, fecha_registro)
            VALUES ('principal', :nombre, :leyenda_general, :leyenda_no_fiscal, 'activa', :usuario, NOW())");
        $stmt->execute(array(
            ":nombre" => $datos["nombre_comercial"],
            ":leyenda_general" => "Gracias por su compra.",
            ":leyenda_no_fiscal" => "Ticket no fiscal. Conserve este comprobante.",
            ":usuario" => $datos["id_usuario"]
        ));
        $idEmpresa = intval($db->lastInsertId());
        $accionEmpresa = "creada";
    }

    $stmt = $db->prepare("SELECT * FROM erp_pos_ticket_configuracion
        WHERE id_almacen IS NULL AND id_caja IS NULL AND id_terminal_pos IS NULL
        ORDER BY prioridad ASC, id_ticket_configuracion ASC
        LIMIT 1");
    $stmt->execute();
    $ticketAntes = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ticketAntes) {
        $stmt = $db->prepare("UPDATE erp_pos_ticket_configuracion
            SET id_empresa_configuracion=:empresa,
                nombre_configuracion='Ticket POS global',
                prioridad=100,
                ticket_ancho_mm=:ancho,
                ticket_columnas=:columnas,
                fuente='monospace',
                mostrar_logo=0,
                logo_modo='texto',
                impresion_modo=:modo,
                copias_venta=1,
                copias_devolucion=1,
                margen_mm=0,
                qr_ticket=0,
                estatus='activa',
                actualizado_por=:usuario,
                fecha_actualizacion=NOW()
            WHERE id_ticket_configuracion=:id");
        $stmt->execute(array(
            ":empresa" => $idEmpresa,
            ":ancho" => $datos["ticket_ancho_mm"],
            ":columnas" => $datos["ticket_columnas"],
            ":modo" => $datos["impresion_modo"],
            ":usuario" => $datos["id_usuario"],
            ":id" => intval($ticketAntes["id_ticket_configuracion"])
        ));
        $idTicket = intval($ticketAntes["id_ticket_configuracion"]);
        $accionTicket = "actualizada";
    } else {
        $stmt = $db->prepare("INSERT INTO erp_pos_ticket_configuracion
            (id_empresa_configuracion, id_almacen, id_caja, id_terminal_pos, nombre_configuracion, prioridad, ticket_ancho_mm, ticket_columnas, fuente, mostrar_logo, logo_modo, impresion_modo, copias_venta, copias_devolucion, margen_mm, qr_ticket, estatus, creado_por, fecha_registro)
            VALUES (:empresa, NULL, NULL, NULL, 'Ticket POS global', 100, :ancho, :columnas, 'monospace', 0, 'texto', :modo, 1, 1, 0, 0, 'activa', :usuario, NOW())");
        $stmt->execute(array(
            ":empresa" => $idEmpresa,
            ":ancho" => $datos["ticket_ancho_mm"],
            ":columnas" => $datos["ticket_columnas"],
            ":modo" => $datos["impresion_modo"],
            ":usuario" => $datos["id_usuario"]
        ));
        $idTicket = intval($db->lastInsertId());
        $accionTicket = "creada";
    }

    $stmt = $db->prepare("SELECT e.*, t.*
        FROM erp_pos_ticket_configuracion t
        LEFT JOIN erp_empresa_configuracion e ON e.id_empresa_configuracion=t.id_empresa_configuracion
        WHERE t.id_ticket_configuracion=:id");
    $stmt->execute(array(":id" => $idTicket));
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    $db->commit();

    responder(array(
        "ok" => true,
        "modo" => "ventas_pos_ticket_config_seed_authorized",
        "mensaje" => "Configuracion base de ticket POS sembrada",
        "respaldo_ref" => $datos["respaldo"],
        "validacion_respaldo" => $validacionRespaldo,
        "empresa" => array("id" => $idEmpresa, "accion" => $accionEmpresa, "nombre_comercial" => $datos["nombre_comercial"]),
        "ticket_configuracion" => array("id" => $idTicket, "accion" => $accionTicket, "ticket_ancho_mm" => $datos["ticket_ancho_mm"], "ticket_columnas" => $datos["ticket_columnas"], "impresion_modo" => $datos["impresion_modo"]),
        "post" => $post,
        "contrato" => contrato(true),
        "siguiente_paso" => "Preparar resolutor read-only de configuracion efectiva y conectar ticketVentaFormalReadOnly/formatearTicketVenta al ancho y leyendas configuradas."
    ));
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "error",
        "mensaje" => "No se pudo sembrar configuracion base de ticket POS",
        "error" => $e->getMessage(),
        "contrato" => contrato(false)
    ));
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "UAT POS vigente") {
        return array("ok" => true, "tipo" => "referencia_operativa", "referencia" => $respaldo);
    }
    $esRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    if ($esRuta) {
        return array(
            "ok" => is_file($respaldo) && is_readable($respaldo),
            "tipo" => "archivo",
            "ruta" => $respaldo,
            "existe" => is_file($respaldo),
            "legible" => is_readable($respaldo),
            "tamano" => is_file($respaldo) ? filesize($respaldo) : null
        );
    }
    return array("ok" => false, "tipo" => "invalido", "recibido" => $respaldo);
}

function contrato($aplica) {
    return array(
        "aplica_seed" => $aplica,
        "no_crea_venta" => true,
        "no_modifica_importes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_toca_ecommerce" => true,
        "no_configura_impresora_so" => true,
        "no_imprime" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}