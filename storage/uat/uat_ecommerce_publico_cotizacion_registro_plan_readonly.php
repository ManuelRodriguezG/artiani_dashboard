<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: describir el plan de activacion del registro interno de cotizaciones ecommerce sin ejecutarlo.
 * Impacto: separa carrito dry-run/WhatsApp de persistencia publica para evitar escrituras prematuras o spam.
 * Contrato: read-only; no registra cotizaciones, no crea prospectos, no descuenta inventario y no crea pedidos.
 */

$opciones = getopt("", array(
  "base::",
  "origin::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://localhost:5173";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$registroBloqueado = $api->cotizacionRegistrarBloqueada(array(
  "contacto" => array("nombre" => "Cliente ejemplo", "telefono" => "5215555555555"),
  "items" => array(array("id_publicacion" => 1, "cantidad" => 1))
));
$payloadPlan = array(
  "contacto" => array(
    "nombre" => "Cliente ejemplo",
    "telefono" => "3322068429",
    "correo" => "cliente@example.com",
    "canal_preferido" => "whatsapp",
    "mensaje" => "Me interesa confirmar disponibilidad."
  ),
  "items" => array(array("id_publicacion" => 1, "cantidad" => 1)),
  "acepta_contacto_whatsapp" => true,
  "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp"),
  "utm" => array("source" => "web", "campaign" => "catalogo_publico")
);
$preflight = $api->cotizacionPreflight($payloadPlan);
$planPersistencia = $api->cotizacionRegistroPersistenciaPlan($payloadPlan);

$ddlPendiente = valorRegistroPlan($estado, array("depurar", "schema", "ddl_pendiente"), true);
$publicadas = intval(valorRegistroPlan($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
$config = valorRegistroPlan($configuracion, array("depurar", "configuracion"), array());
$whatsapp = trim((string) valorRegistroPlan($config, array("whatsapp_numero_principal"), ""));
$cors = trim((string) valorRegistroPlan($config, array("cors_origenes_permitidos"), ""));
$corsPermiteOrigin = $cors !== "" && in_array($origin, array_map("trim", explode(",", $cors)), true);

$bloqueos = array();
if ($ddlPendiente) {
  $bloqueos[] = "ddl_ecommerce_publico_pendiente";
}
if ($publicadas <= 0) {
  $bloqueos[] = "sin_publicaciones_activas_para_validar_items";
}
if ($whatsapp === "") {
  $bloqueos[] = "whatsapp_no_configurado";
}
if ($cors === "") {
  $bloqueos[] = "cors_origenes_permitidos_no_configurado";
} elseif (!$corsPermiteOrigin) {
  $bloqueos[] = "cors_no_permite_origin_" . $origin;
}

$bloqueos[] = "endpoint_registro_permanece_bloqueado_por_politica_fase1";
$bloqueos[] = "pendiente_definir_rate_limit_captcha_o_backend_intermedio";
$bloqueos[] = "pendiente_politica_crm_para_prospecto_cliente_mascota";

echo json_encode(array(
  "ok" => false,
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "endpoint" => "POST /ecommercePublico/cotizacion_registrar",
  "estado_actual" => array(
    "bloqueado" => valorRegistroPlan($registroBloqueado, array("depurar", "bloqueado"), true),
    "mensaje" => valorRegistroPlan($registroBloqueado, array("mensaje"), ""),
    "ddl_pendiente" => $ddlPendiente,
    "publicadas" => $publicadas,
    "whatsapp_configurado" => $whatsapp !== "",
    "cors_configurado" => $cors !== "",
    "origin_probado" => $origin,
    "cors_permite_origin" => $corsPermiteOrigin,
    "preflight_ok" => empty($preflight["error"]) && valorRegistroPlan($preflight, array("depurar", "preflight"), false) === true,
    "plan_persistencia_ok" => empty(valorRegistroPlan($planPersistencia, array("depurar", "bloqueos"), array()))
  ),
  "payload_futuro_sugerido" => $payloadPlan,
  "preflight" => array(
    "tipo" => valorRegistroPlan($preflight, array("tipo"), ""),
    "listo_para_whatsapp" => valorRegistroPlan($preflight, array("depurar", "listo_para_whatsapp"), false),
    "listo_para_registro_futuro" => valorRegistroPlan($preflight, array("depurar", "listo_para_registro_futuro"), false),
    "folio_preliminar" => valorRegistroPlan($preflight, array("depurar", "folio_preliminar"), "")
  ),
  "plan_persistencia" => array(
    "tipo" => valorRegistroPlan($planPersistencia, array("tipo"), ""),
    "folio_planeado" => valorRegistroPlan($planPersistencia, array("depurar", "folio_planeado"), ""),
    "folio_planeado_no_reservado" => valorRegistroPlan($planPersistencia, array("depurar", "folio_planeado_no_reservado"), true),
    "tablas" => valorRegistroPlan($planPersistencia, array("depurar", "tablas"), array()),
    "snapshot" => valorRegistroPlan($planPersistencia, array("depurar", "snapshot"), array()),
    "sql_plan" => valorRegistroPlan($planPersistencia, array("depurar", "sql_plan"), array()),
    "bloqueos" => valorRegistroPlan($planPersistencia, array("depurar", "bloqueos"), array())
  ),
  "persistencia_planeada" => array(
    "encabezado" => "erp_ecommerce_cotizaciones",
    "detalle" => "erp_ecommerce_cotizaciones_detalle",
    "eventos" => "erp_ecommerce_cotizaciones_eventos",
    "snapshots_producto" => true,
    "ip_user_agent_hasheados" => true,
    "sin_inventario" => true,
    "sin_pedido_confirmado" => true,
    "sin_pago_online" => true
  ),
  "activacion_requerida" => array(
    "aplicar_ddl_con_respaldo",
    "configurar_whatsapp_y_cors",
    "publicar_lote_inicial",
    "definir_antispam_rate_limit_captcha_o_backend_intermedio",
    "definir_politica_crm_prospecto_cliente_mascota",
    "crear_pruebas_de_no_inventario_no_pedido_no_pago",
    "desbloquear_endpoint_en_cambio_separado"
  ),
  "bloqueos" => array_values(array_unique($bloqueos)),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_crea_prospecto" => true,
    "no_descuenta_inventario" => true,
    "no_crea_pedido" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorRegistroPlan($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
