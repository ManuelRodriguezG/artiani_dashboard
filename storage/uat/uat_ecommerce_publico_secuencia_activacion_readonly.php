<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: generar la secuencia operativa read-only para activar ecommerce publico Fase 1.
 * Impacto: ordena los apply autorizados necesarios para pasar de mock/contratos a datos reales.
 * Contrato: no ejecuta comandos, no escribe BD, no crea publicaciones y no toca inventario.
 */

$opciones = getopt("", array(
  "base::",
  "respaldo::",
  "whatsapp::",
  "cors::",
  "url::",
  "id_sku::",
  "confirmar_agotado::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "RUTA_O_REFERENCIA";
$whatsapp = isset($opciones["whatsapp"]) ? trim((string) $opciones["whatsapp"]) : "NUMERO_WHATSAPP";
$cors = isset($opciones["cors"]) ? trim((string) $opciones["cors"]) : "ORIGEN_FRONTEND";
$url = isset($opciones["url"]) ? trim((string) $opciones["url"]) : "URL_FRONTEND";
$idSku = isset($opciones["id_sku"]) ? intval($opciones["id_sku"]) : 1291;
$confirmarAgotado = isset($opciones["confirmar_agotado"]) ? intval($opciones["confirmar_agotado"]) : 0;

$comandos = array(
  array(
    "paso" => 1,
    "nombre" => "Preflight bundle read-only",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_activacion_bundle_readonly.php --base=" . $base . " --respaldo=" . $respaldo . " --whatsapp=" . $whatsapp . " --cors=" . $cors . " --url=" . $url . " --lote=8",
    "esperado" => "senal_frontend=amarillo_mock_contratos antes de DDL; ok=true; bloqueos claros.",
    "escritura" => false
  ),
  array(
    "paso" => 2,
    "nombre" => "Revisar SQL DDL",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_schema_sql_readonly.php --respaldo=" . $respaldo,
    "esperado" => "ddl_total=5 y sha256_sql registrado.",
    "escritura" => false
  ),
  array(
    "paso" => 3,
    "nombre" => "Aplicar DDL autorizado",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=" . $respaldo,
    "esperado" => "tablas erp_ecommerce_* creadas; no publicaciones aun.",
    "escritura" => true
  ),
  array(
    "paso" => 4,
    "nombre" => "Aplicar configuracion autorizada",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=" . $respaldo . " --whatsapp=" . $whatsapp . " --cors=" . $cors . " --url=" . $url,
    "esperado" => "WhatsApp/CORS/URL configurados sin secretos y sin wildcard.",
    "escritura" => true
  ),
  array(
    "paso" => 5,
    "nombre" => "Planear publicacion borrador",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_plan_readonly.php --id_sku=" . $idSku,
    "esperado" => "sin bloqueos de DDL; estatus normalizado a borrador.",
    "escritura" => false
  ),
  array(
    "paso" => 6,
    "nombre" => "Guardar borrador autorizado",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=" . $respaldo . " --id_sku=" . $idSku,
    "esperado" => "publicacion creada como borrador; no publicada.",
    "escritura" => true
  ),
  array(
    "paso" => 7,
    "nombre" => "Planear publicacion con revision",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicar_borrador_plan_readonly.php --id_sku=" . $idSku . " --confirmar_revision=1" . ($confirmarAgotado === 1 ? " --confirmar_agotado=1" : ""),
    "esperado" => "sin bloqueos; si SKU esta agotado exige confirmar_agotado=1.",
    "escritura" => false
  ),
  array(
    "paso" => 8,
    "nombre" => "Publicar borrador autorizado",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicar_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR --respaldo=" . $respaldo . " --id_sku=" . $idSku . " --confirmar_revision=1" . ($confirmarAgotado === 1 ? " --confirmar_agotado=1" : ""),
    "esperado" => "estatus publicado; visible por /catalogo.",
    "escritura" => true
  ),
  array(
    "paso" => 9,
    "nombre" => "Validar bundle final",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_activacion_bundle_readonly.php --base=" . $base . " --respaldo=" . $respaldo . " --whatsapp=" . $whatsapp . " --cors=" . $cors . " --url=" . $url . " --lote=8",
    "esperado" => "senal_frontend=verde_datos_reales.",
    "escritura" => false
  ),
  array(
    "paso" => 10,
    "nombre" => "Compuerta verde final",
    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=" . $base,
    "esperado" => "ok=true; catalogo con item real; cotizacion_dryrun con item publicado.",
    "escritura" => false
  )
);

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "no_ejecuta_comandos" => true,
  "parametros" => array(
    "base" => $base,
    "respaldo" => $respaldo,
    "whatsapp" => $whatsapp,
    "cors" => $cors,
    "url" => $url,
    "id_sku" => $idSku,
    "confirmar_agotado" => $confirmarAgotado
  ),
  "secuencia" => $comandos,
  "aviso" => "Ejecutar pasos de escritura solo con respaldo real y autorizacion explicita del dueno.",
  "senal_verde_esperada" => "senal_frontend=verde_datos_reales"
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
