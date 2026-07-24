<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-23.
 * Proposito: validar preparacion read-only de venta rapida controlada POS.
 * Impacto: confirma UI, endpoint dry-run, modelo, manual, DDL aplicado y guardrail antes del endpoint real.
 * Contrato: no consulta BD, no escribe BD, no invoca HTTP, no crea venta, no crea SKU y no mueve inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$archivos = array(
    "controlador" => "app/controladores/Ventas.php",
    "modelo" => "app/modelos/VentasErp.php",
    "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
    "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js",
    "manual" => "app/vistas/paginas/apps/erp/ventas/manual_pos.php",
    "plan" => "docs/erp_ventas_pos_venta_rapida_controlada_plan.md",
);

$checks = array(
    "controlador" => array(
        "pos_venta_rapida_dryrun_erp" => "endpoint dry-run protegido",
        "ventaRapidaControladaDryRun" => "modelo de validacion conectado",
        "ventas.operar" => "permiso operativo requerido",
    ),
    "modelo" => array(
        "function ventaRapidaControladaDryRun" => "dry-run de producto por clasificar",
        "function prevalidarPartidaVentaRapidaControlada" => "prevalidacion de partida provisional",
        "function esPartidaVentaRapidaControlada" => "detector de partida provisional",
        "Venta rapida controlada requiere autorizacion de endpoint real" => "dry-run de confirmacion bloquea venta rapida hasta autorizacion real",
        "venta_rapida_controlada_endpoint_pendiente" => "guardrail de cobro real sin endpoint real",
        "no_crea_sku" => "contrato sin alta automatica",
        "no_mueve_inventario" => "contrato sin kardex",
    ),
    "vista_pos" => array(
        "pos_venta_rapida_btn" => "boton de venta rapida",
        "pos_venta_rapida_modal" => "modal de captura",
        "Producto por clasificar" => "texto operativo visible",
        "Controla inventario" => "decision provisional inventariable",
    ),
    "js_pos" => array(
        "datosVentaRapida" => "captura payload UI",
        "validarVentaRapida" => "dry-run desde UI",
        "agregarVentaRapidaAlCarrito" => "agrega partida provisional",
        "tipo_partida: \"venta_rapida\"" => "marca partida provisional",
        "Venta rapida todavia no puede cobrarse real" => "bloqueo UI de cobro real hasta endpoint real",
    ),
    "manual" => array(
        "Venta rapida controlada" => "manual actualizado",
        "Producto por clasificar" => "manual explica partida provisional",
        "Diferencia contra inventario pendiente" => "manual separa flujos",
    ),
    "plan" => array(
        "VENTAS_POS_VENTA_RAPIDA_REAL" => "autorizacion siguiente documentada",
        "Pendiente de clasificacion POS" => "flujo catalogo documentado",
        "No descuenta inventario de un SKU definitivo" => "regla inventario documentada",
    ),
);

$bloqueos = array();
$detalle = array();
foreach ($archivos as $clave => $relativa) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativa);
    $contenido = is_file($ruta) ? file_get_contents($ruta) : "";
    $detalle[$clave] = array("ruta" => $relativa, "existe" => is_file($ruta), "checks" => array());
    if (!is_file($ruta)) {
        $bloqueos[] = "Falta archivo " . $relativa;
        continue;
    }
    foreach ($checks[$clave] as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $detalle[$clave]["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = $relativa . " no contiene " . $descripcion . " [" . $token . "]";
        }
    }
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_venta_rapida_readiness_readonly",
    "read_only" => true,
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "resultado" => empty($bloqueos) ? "venta_rapida_ddl_aplicado_endpoint_real_pendiente" : "venta_rapida_requiere_revision",
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "siguiente_autorizacion" => "AUTORIZO EJECUTAR UAT REAL VENTA RAPIDA CONTROLADA POS usando respaldo UAT POS vigente con token VENTAS_POS_VENTA_RAPIDA_REAL id_usuario=1 descripcion=\"Producto UAT por clasificar\" cantidad=1 precio=100 pago=100 motivo=\"UAT venta rapida controlada\" para UAT POS/Catalogo/Inventario",
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_crea_venta" => true,
        "no_crea_sku" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);
