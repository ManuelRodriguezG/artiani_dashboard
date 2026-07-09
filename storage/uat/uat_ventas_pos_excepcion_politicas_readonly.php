<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: proponer politicas UAT de excepcion comercial POS sin escribir BD.
 * Impacto: prepara precio manual/descuentos con margen, permiso y autorizacion formal.
 * Contrato: read-only; no inserta politicas, ventas, excepciones, caja ni inventario.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosExcepcionPoliticasReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosExcepcionPoliticasReadonlyDb())->db();
$tablas = array("erp_ventas_politicas_comerciales", "erp_ventas_excepciones_comerciales");
$faltantes = tablasFaltantes($db, $tablas);

$politicas = politicasUat($idAlmacen, $idUsuario);
$existentes = array();
if (empty($faltantes)) {
    foreach ($politicas as $politica) {
        $stmt = $db->prepare("SELECT id_politica_comercial, codigo, nombre, tipo_excepcion, estatus
            FROM erp_ventas_politicas_comerciales
            WHERE codigo=:codigo
            LIMIT 1");
        $stmt->execute(array(":codigo" => $politica["codigo"]));
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($actual) {
            $existentes[] = $actual;
        }
    }
}

$bloqueos = array();
if (!empty($faltantes)) {
    $bloqueos[] = "Falta DDL excepcion comercial: " . implode(", ", $faltantes);
}
if ($idUsuario <= 0) {
    $bloqueos[] = "id_usuario requerido para semilla autorizada";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_excepcion_politicas_readonly",
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "politicas_propuestas" => $politicas,
    "politicas_existentes" => $existentes,
    "bloqueos" => $bloqueos,
    "comando_autorizado_futuro" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_excepcion_politicas_apply_authorized.php --autorizar=VENTAS_POS_EXCEPCION_POLITICAS --respaldo=C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=" . $idUsuario . " --id_almacen=" . $idAlmacen,
    "siguiente_paso" => empty($bloqueos) ? "Solicitar autorizacion para sembrar politicas UAT de excepcion comercial." : "Resolver bloqueos antes de solicitar autorizacion."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function politicasUat($idAlmacen, $idUsuario) {
    return array(
        array(
            "codigo" => "POS_PRECIO_MANUAL_UAT",
            "nombre" => "Precio manual POS UAT",
            "tipo_excepcion" => "precio_manual",
            "canal" => "pos",
            "id_almacen" => $idAlmacen,
            "descuento_max_porcentaje" => 0,
            "descuento_max_monto" => 0,
            "margen_minimo_porcentaje" => 0,
            "requiere_autorizacion" => 1,
            "permiso_requerido" => "ventas.autorizar_excepcion_comercial",
            "creado_por" => $idUsuario,
            "observaciones" => "UAT: permite simular/aplicar precio manual solo con autorizacion de supervisor."
        ),
        array(
            "codigo" => "POS_DESCUENTO_PARTIDA_UAT",
            "nombre" => "Descuento por partida POS UAT",
            "tipo_excepcion" => "descuento_partida",
            "canal" => "pos",
            "id_almacen" => $idAlmacen,
            "descuento_max_porcentaje" => 0.100000,
            "descuento_max_monto" => 50,
            "margen_minimo_porcentaje" => 0,
            "requiere_autorizacion" => 1,
            "permiso_requerido" => "ventas.autorizar_excepcion_comercial",
            "creado_por" => $idUsuario,
            "observaciones" => "UAT: descuento por partida limitado y autorizado."
        ),
        array(
            "codigo" => "POS_DESCUENTO_GENERAL_UAT",
            "nombre" => "Descuento general POS UAT",
            "tipo_excepcion" => "descuento_general",
            "canal" => "pos",
            "id_almacen" => $idAlmacen,
            "descuento_max_porcentaje" => 0.100000,
            "descuento_max_monto" => 100,
            "margen_minimo_porcentaje" => 0,
            "requiere_autorizacion" => 1,
            "permiso_requerido" => "ventas.autorizar_excepcion_comercial",
            "creado_por" => $idUsuario,
            "observaciones" => "UAT: descuento general limitado y autorizado."
        )
    );
}

function tablasFaltantes($db, $tablas) {
    $faltantes = array();
    foreach ($tablas as $tabla) {
        $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
        $stmt->execute(array(":tabla" => $tabla));
        if (!$stmt->fetchColumn()) {
            $faltantes[] = $tabla;
        }
    }
    return $faltantes;
}
