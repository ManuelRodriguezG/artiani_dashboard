<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-23.
 * Proposito: validar preparacion read-only del modelo real de venta rapida controlada POS.
 * Impacto: confirma que el cobro puede crear detalle provisional, pendiente, evento y notificacion cuando se autorice UAT real.
 * Contrato: no escribe BD, no cobra, no crea SKU, no mueve caja ni inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$archivos = array(
    "controlador" => "app/controladores/Ventas.php",
    "modelo" => "app/modelos/VentasErp.php",
    "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js",
    "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
    "plan" => "docs/erp_ventas_pos_venta_rapida_controlada_plan.md",
    "estado" => "docs/erp_ventas_pos_estado_cierre_modulo.md",
);

$checks = array(
    "controlador" => array(
        "function pos_confirmar_erp" => "endpoint real POS",
        "habilitarVentaRapidaPosUiSiAplica" => "token interno desde controlador",
        "VENTAS_POS_VENTA_RAPIDA_REAL_MODELO" => "token modelo no expuesto al JS",
        "VENTAS_POS_VENTA_RAPIDA_REAL" => "token real no expuesto al JS",
    ),
    "modelo" => array(
        "function confirmarVentaPosReal" => "cobro POS real existe",
        "function registrarPendienteVentaRapidaPosReal" => "pendiente venta rapida transaccional",
        "function registrarEventoVentaRapidaPosReal" => "evento venta rapida",
        "function registrarNotificacionVentaRapidaPosReal" => "notificacion a Catalogo",
        "function generarFolioVentaRapidaPendientePosReal" => "folio VRP",
        "function schemaVentaRapidaControladaCompleto" => "guardrail de esquema",
        "function ventaRapidaRealAutorizada" => "autorizacion explicita",
        "VENTAS_POS_VENTA_RAPIDA_REAL" => "token ejecucion UAT real",
        "VENTAS_POS_VENTA_RAPIDA_REAL_MODELO" => "token preparacion modelo",
        "'venta_rapida'" => "detalle marcado como venta rapida",
        "'venta_rapida_controlada'" => "origen de partida controlado",
        "erp_pos_venta_rapida_pendientes" => "tabla pendiente usada",
        "erp_pos_venta_rapida_eventos" => "tabla eventos usada",
        "no_mueve_inventario" => "contrato sin kardex para SKU inexistente",
        "No se crea SKU definitivo automatico" => "regla maestra conservada",
    ),
    "js_pos" => array(
        "Cobrar venta rapida" => "UI permite cobro real de venta rapida",
        "se creara pendiente a Catalogo/Inventario" => "confirmacion explica pendiente",
        "no se movera kardex" => "confirmacion explica inventario",
    ),
    "vista_pos" => array(
        "20260723-venta-rapida-ui-real" => "asset JS versionado para UI real",
    ),
    "plan" => array(
        "VENTAS_POS_VENTA_RAPIDA_REAL_MODELO" => "siguiente paso documentado",
        "DDL aplicado en UAT POS" => "DDL ya no se repite",
    ),
    "estado" => array(
        "DDL aplicado el 2026-07-23" => "estado cierre actualizado",
        "Falta UAT UI desde navegador" => "siguiente paso operativo claro",
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

$schema = array("ok" => false, "nota" => "No consultado");
try {
    chdir($root . "/public");
    require_once "../app/iniciador.php";
    require_once "../app/modelos/VentasErpEsquema.php";
    $modelo = new VentasErpEsquema();
    $auditoria = $modelo->auditarVentaRapidaControladaPos();
    $depurar = isset($auditoria["depurar"]) && is_array($auditoria["depurar"]) ? $auditoria["depurar"] : array();
    $faltantes = 0;
    foreach (array("tablas", "columnas", "indices") as $grupo) {
        foreach (isset($depurar[$grupo]) && is_array($depurar[$grupo]) ? $depurar[$grupo] : array() as $item) {
            if (empty($item["existe"])) {
                $faltantes++;
            }
        }
    }
    $schema = array("ok" => $faltantes === 0, "faltantes" => $faltantes);
    if ($faltantes > 0) {
        $bloqueos[] = "Schema venta rapida tiene faltantes: " . $faltantes;
    }
} catch (Exception $e) {
    $schema = array("ok" => false, "error" => $e->getMessage());
    $bloqueos[] = "No se pudo auditar schema venta rapida: " . $e->getMessage();
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_venta_rapida_modelo_real_readiness_readonly",
    "read_only" => true,
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "resultado" => empty($bloqueos) ? "modelo_real_preparado_ui_real_expuesta" : "modelo_real_requiere_revision",
    "schema" => $schema,
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "siguiente_autorizacion" => "AUTORIZO UAT UI REAL VENTA RAPIDA POS usando respaldo UAT POS vigente con token VENTAS_POS_VENTA_RAPIDA_UI_REAL id_usuario=1 monto_inicial=500 descripcion=\"Producto UAT UI por clasificar\" cantidad=1 precio=100 pago=100 motivo=\"UAT UI venta rapida controlada\" para UAT POS/Catalogo/Inventario",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cobra" => true,
        "no_crea_sku" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);
