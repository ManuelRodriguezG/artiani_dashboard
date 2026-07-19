<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar atajos operativos POS de bajo conflicto para venta rapida.
 * Impacto: POS/UI; asegura busqueda, escaner, pagos, prevalidacion, cobro y navegacion sin escribir BD.
 * Contrato: read-only; no invoca navegador, no cobra, no mueve caja ni inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$archivoJs = $root . "/public/assets/js/custom/apps/erp/ventas/pos.js";
$archivoVista = $root . "/app/vistas/paginas/apps/erp/ventas/pos.php";

$checksJs = array(
    "function registrarAtajosPos" => "registrador de atajos",
    "keydown" => "escucha teclado",
    "ctrlKey || event.metaKey" => "atajos ctrl/cmd",
    "key.toLowerCase() === \"k\"" => "Ctrl+K enfoca buscador",
    "key === \"F2\"" => "F2 enfoca buscador",
    "key === \"F3\"" => "F3 abre escaner",
    "abrirEscanerPos()" => "atajo escaner POS",
    "event.altKey && key === \"1\"" => "Alt+1 pago efectivo",
    "event.altKey && key === \"2\"" => "Alt+2 pago tarjeta",
    "event.altKey && key === \"3\"" => "Alt+3 pago transferencia",
    "key === \"Enter\"" => "Ctrl/Cmd+Enter cobrar",
    "cobrarReal()" => "atajo llama cobro real con validaciones",
    "key === \"F4\"" => "F4 cliente/autorizacion",
    "abrirClienteAutorizacion(\"cliente\")" => "atajo abre cliente",
    "key === \"F6\"" => "F6 monto pago",
    "key === \"F8\"" => "F8 movimientos caja",
    "window.location.href = \"/ventas/caja_movimientos\"" => "navegacion movimientos caja",
    "key === \"F9\"" => "F9 prevalidar",
    "prevalidar()" => "atajo llama prevalidacion",
    "key === \"F10\"" => "F10 pedidos",
    "window.location.href = \"/ventas/pedidos\"" => "navegacion pedidos",
    "registrarAtajosPos();" => "registro al iniciar POS",
);

$checksVista = array(
    "pos_buscar" => "buscador principal",
    "pos_scan_camera_btn" => "boton camara",
    "pos_cobrar_real" => "boton cobrar",
    "data-pos-pago-rapido=\"efectivo\"" => "pago rapido efectivo",
    "data-pos-pago-rapido=\"tarjeta\"" => "pago rapido tarjeta",
    "data-pos-pago-rapido=\"transferencia\"" => "pago rapido transferencia",
);

$bloqueos = array();
$detalle = array(
    "js" => revisarArchivo($archivoJs, $root, $checksJs, $bloqueos),
    "vista" => revisarArchivo($archivoVista, $root, $checksVista, $bloqueos),
);

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_atajos_ui_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "atajos_operativos" => array(
        "F2" => "buscar producto",
        "Ctrl+K" => "buscar producto",
        "F3" => "abrir camara POS",
        "Alt+1" => "agregar pago efectivo",
        "Alt+2" => "agregar pago tarjeta",
        "Alt+3" => "agregar pago transferencia",
        "F6" => "editar monto de pago",
        "F9" => "prevalidar venta",
        "Ctrl+Enter" => "cobrar con validaciones",
        "F8" => "ir a movimientos caja",
        "F10" => "ir a pedidos",
    ),
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function revisarArchivo($archivo, $root, $checks, &$bloqueos)
{
    $contenido = is_file($archivo) ? file_get_contents($archivo) : "";
    $resultado = array(
        "archivo" => str_replace("\\", "/", str_replace($root . DIRECTORY_SEPARATOR, "", $archivo)),
        "existe" => is_file($archivo),
        "checks" => array(),
    );
    if (!is_file($archivo)) {
        $bloqueos[] = "No existe " . $resultado["archivo"];
        return $resultado;
    }
    foreach ($checks as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $resultado["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = "Falta " . $descripcion . " [" . $token . "]";
        }
    }
    return $resultado;
}
