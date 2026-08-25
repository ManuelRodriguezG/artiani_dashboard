<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-21
 * Proposito: probar en modo read-only el contrato de contexto vendible de Catalogo ERP.
 * Impacto: UAT local; no escribe BD, no modifica precios, costos ni inventario.
 * Contrato: ejecutar con `--id_sku=ID`; si se omite toma el SKU activo mas reciente.
 */

$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpDatos.php";

$opciones = getopt("", array("id_sku::"));
$modelo = new CatalogoErpDatos();
$idSku = isset($opciones["id_sku"]) ? intval($opciones["id_sku"]) : 0;

if ($idSku <= 0) {
  $db = new PDO(
    "mysql:host=" . MYSQLHOST . ";dbname=" . MYSQLBASE . ";port=" . MYSQLPORT . ";charset=utf8",
    MYSQLUSER,
    MYSQLPASS
  );
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $idSku = intval($db->query("SELECT id_sku FROM erp_catalogo_skus WHERE estatus='activo' ORDER BY id_sku DESC LIMIT 1")->fetchColumn());
}

$respuesta = $modelo->resolverContextoSkuVendible($idSku);
echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
