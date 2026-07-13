<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: consolidar el paquete read-only de activacion ecommerce publico Fase 1.
 * Impacto: entrega una sola salida para decidir si el frontend externo puede pasar de mock a datos reales.
 * Contrato: no ejecuta DDL, no escribe configuracion, no crea publicaciones y no toca inventario.
 */

$opciones = getopt("", array(
  "base::",
  "respaldo::",
  "whatsapp::",
  "cors::",
  "url::",
  "lote::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$loteLimite = isset($opciones["lote"]) ? max(5, min(30, intval($opciones["lote"]))) : 10;
$configValores = array(
  "whatsapp_numero_principal" => isset($opciones["whatsapp"]) ? trim((string) $opciones["whatsapp"]) : "",
  "cors_origenes_permitidos" => isset($opciones["cors"]) ? trim((string) $opciones["cors"]) : "",
  "url_sitio_publico" => isset($opciones["url"]) ? trim((string) $opciones["url"]) : ""
);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$esquema = new EcommercePublicoEsquema();
$api = new EcommerceCatalogoPublico();

$respaldoValidado = validarRespaldoBundle($respaldo);
$schemaAuditoria = $esquema->auditarEcommercePublico();
$schemaPlan = $esquema->planActualizarEcommercePublico(false);
$estado = $api->estadoApiPublica();
$contratos = $api->contratosApiPublicos();
$configActual = $api->configuracionPublica();
$configPlan = $api->planConfiguracionInicial($configValores);
$publicabilidad = $api->auditarPublicabilidad(array("limite" => 30, "solo_publicables" => 1));
$lote = sugerirLoteBundle($api, $loteLimite);
$planPrimeraPublicacion = array();
$planPublicarPrimera = array();
if (!empty($lote)) {
  $planPrimeraPublicacion = $api->planGuardarPublicacion(array("id_sku" => intval($lote[0]["id_sku"])));
  $planPublicarPrimera = $api->planPublicarBorrador(array(
    "id_sku" => intval($lote[0]["id_sku"]),
    "confirmar_revision" => 1,
    "confirmar_agotado" => 1
  ));
}
$http = smokeHttpBundle($base);

$endpoints = valorBundle($contratos, array("depurar", "endpoints_publicos"), array());
$ddlPendiente = valorBundle($estado, array("depurar", "schema", "ddl_pendiente"), true);
$publicadas = intval(valorBundle($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
$whatsappActual = trim((string) valorBundle($configActual, array("depurar", "configuracion", "whatsapp_numero_principal"), ""));
$corsActual = trim((string) valorBundle($configActual, array("depurar", "configuracion", "cors_origenes_permitidos"), ""));

$bloqueosVerde = array();
if (!$http["ok"]) {
  $bloqueosVerde[] = "http_api_publica_no_verificada";
}
if ($ddlPendiente) {
  $bloqueosVerde[] = "ddl_ecommerce_publico_pendiente";
}
if ($publicadas <= 0) {
  $bloqueosVerde[] = "sin_publicaciones_activas";
}
if ($whatsappActual === "") {
  $bloqueosVerde[] = "whatsapp_no_configurado";
}
if ($corsActual === "") {
  $bloqueosVerde[] = "cors_origenes_permitidos_no_configurado";
}

$bloqueosActivacion = array();
if (!$respaldoValidado["ok"]) {
  $bloqueosActivacion[] = "respaldo_externo_requerido_para_apply";
}
if (intval(valorBundle($schemaPlan, array("depurar", "ddl_total"), 0)) !== 5) {
  $bloqueosActivacion[] = "plan_ddl_inesperado";
}
if (count($endpoints) < 8) {
  $bloqueosActivacion[] = "contratos_api_incompletos";
}
if (intval(valorBundle($publicabilidad, array("depurar", "resumen", "skus_publicables_fase_1"), 0)) <= 0) {
  $bloqueosActivacion[] = "sin_skus_publicables";
}

$senal = empty($bloqueosVerde) ? "verde_datos_reales" : "amarillo_mock_contratos";

echo json_encode(array(
  "ok" => empty($bloqueosActivacion),
  "modo" => "read-only",
  "senal_frontend" => $senal,
  "base_url_verificada" => $base,
  "frontend_base_correcta" => $base . "/ecommercePublico",
  "respaldo" => $respaldoValidado,
  "http" => $http,
  "schema" => array(
    "tablas_faltantes" => intval(valorBundle($schemaAuditoria, array("depurar", "tablas_faltantes"), 0)),
    "ddl_total" => intval(valorBundle($schemaPlan, array("depurar", "ddl_total"), 0)),
    "ddl_pendiente" => $ddlPendiente
  ),
  "api" => array(
    "version" => valorBundle($contratos, array("depurar", "api", "version"), ""),
    "endpoints_total" => count($endpoints),
    "ready" => valorBundle($estado, array("depurar", "ready"), false)
  ),
  "configuracion" => array(
    "actual" => array(
      "configurado" => valorBundle($configActual, array("depurar", "configurado"), false),
      "whatsapp_configurado" => $whatsappActual !== "",
      "cors_configurado" => $corsActual !== ""
    ),
    "plan_readonly" => array(
      "sql_total" => intval(valorBundle($configPlan, array("depurar", "sql_total"), 0)),
      "sha256_sql" => valorBundle($configPlan, array("depurar", "sha256_sql"), ""),
      "bloqueos_datos_reales" => valorBundle($configPlan, array("depurar", "bloqueos_datos_reales"), array())
    )
  ),
  "publicaciones" => array(
    "publicadas" => $publicadas,
    "skus_publicables_fase_1" => intval(valorBundle($publicabilidad, array("depurar", "resumen", "skus_publicables_fase_1"), 0)),
    "lote_sugerido_total" => count($lote),
    "primer_sku_plan" => array(
      "id_sku" => !empty($lote) ? intval($lote[0]["id_sku"]) : 0,
      "bloqueos" => valorBundle($planPrimeraPublicacion, array("depurar", "bloqueos_publicacion"), array()),
      "sha256_sql" => valorBundle($planPrimeraPublicacion, array("depurar", "sha256_sql"), "")
    ),
    "primer_sku_publicar_plan" => array(
      "id_sku" => !empty($lote) ? intval($lote[0]["id_sku"]) : 0,
      "requiere_revision" => true,
      "confirmar_agotado_usado_en_plan" => true,
      "bloqueos" => valorBundle($planPublicarPrimera, array("depurar", "bloqueos_publicacion"), array()),
      "sha256_sql" => valorBundle($planPublicarPrimera, array("depurar", "sha256_sql"), "")
    )
  ),
  "bloqueos_para_verde_datos_reales" => $bloqueosVerde,
  "bloqueos_para_solicitar_apply" => $bloqueosActivacion,
  "siguiente_paso" => empty($bloqueosVerde)
    ? "Ya se puede avisar que el frontend externo puede integrar datos reales."
    : "Aun no avisar verde. Resolver bloqueos y ejecutar applys autorizados con respaldo.",
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_crea_publicaciones" => true,
    "no_registra_cotizaciones" => true,
    "no_mueve_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function sugerirLoteBundle($api, $limite) {
  $auditoria = $api->auditarPublicabilidad(array("limite" => max(30, $limite * 4), "solo_publicables" => 1));
  $candidatos = valorBundle($auditoria, array("depurar", "candidatos"), array());
  $lote = array();
  foreach ($candidatos as $fila) {
    if (count($lote) >= $limite) {
      break;
    }
    $lote[] = array(
      "id_sku" => intval(valorBundle($fila, array("id_sku"), 0)),
      "sku" => valorBundle($fila, array("sku"), ""),
      "nombre" => valorBundle($fila, array("nombre_publico"), ""),
      "publicable_fase_1" => intval(valorBundle($fila, array("publicable_fase_1"), 0)) === 1,
      "disponibilidad_publica_sugerida" => valorBundle($fila, array("disponibilidad_publica_sugerida"), "")
    );
  }
  return $lote;
}

function smokeHttpBundle($base) {
  $pruebas = array(
    "estado" => requestHttpBundle($base . "/ecommercePublico/estado"),
    "contratos" => requestHttpBundle($base . "/ecommercePublico/contratos"),
    "catalogo" => requestHttpBundle($base . "/ecommercePublico/catalogo"),
    "cotizacion_dryrun" => requestHttpBundle($base . "/ecommercePublico/cotizacion_dryrun", "POST", array(
      "items" => array(array("id_publicacion" => 1, "cantidad" => 1))
    ))
  );
  $ok = true;
  foreach ($pruebas as $prueba) {
    if (empty($prueba["json_valido"])) {
      $ok = false;
    }
  }
  return array("ok" => $ok, "pruebas" => $pruebas);
}

function requestHttpBundle($url, $method = "GET", $body = null) {
  $headers = "Accept: application/json\r\n";
  $content = null;
  if ($body !== null) {
    $content = json_encode($body);
    $headers .= "Content-Type: application/json\r\n";
  }
  $context = stream_context_create(array(
    "http" => array(
      "method" => $method,
      "header" => $headers,
      "content" => $content,
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  return array(
    "method" => $method,
    "url" => $url,
    "json_valido" => is_array($json),
    "tipo" => is_array($json) ? valorBundle($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorBundle($json, array("mensaje"), "") : "",
    "api_version" => is_array($json) ? valorBundle($json, array("api", "version"), "") : "",
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function validarRespaldoBundle($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $okReferencia = strlen($respaldo) >= 8;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}

function valorBundle($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
