<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: emitir variables de entorno y snippets de proxy para iniciar el frontend ecommerce externo.
 * Impacto: evita usar hosts incorrectos y documenta CORS/proxy sin modificar configuracion del ERP.
 * Contrato: read-only; no escribe archivos ni configura CORS.
 */

$opciones = getopt("", array(
  "base::",
  "frontend::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$frontend = isset($opciones["frontend"]) ? rtrim(trim((string) $opciones["frontend"]), "/") : "http://localhost:5173";

$envVite = implode(PHP_EOL, array(
  "VITE_ERP_API_BASE_URL=" . $base,
  "VITE_ERP_ECOMMERCE_BASE_PATH=/ecommercePublico",
  "VITE_ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12"
));

$envNext = implode(PHP_EOL, array(
  "NEXT_PUBLIC_ERP_API_BASE_URL=" . $base,
  "NEXT_PUBLIC_ERP_ECOMMERCE_BASE_PATH=/ecommercePublico",
  "NEXT_PUBLIC_ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12"
));

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "frontend_origen_sugerido" => $frontend,
  "env" => array(
    "vite" => $envVite,
    "next" => $envNext
  ),
  "vite_proxy_sugerido" => array(
    "server" => array(
      "proxy" => array(
        "/ecommercePublico" => array(
          "target" => $base,
          "changeOrigin" => true,
          "secure" => false
        )
      )
    )
  ),
  "notas" => array(
    "No guardar API secret o HMAC secret en variables publicas del navegador.",
    "Mientras CORS no este configurado en ERP, usar fixtures o proxy local de desarrollo.",
    "El origen CORS futuro debe ser exacto, por ejemplo " . $frontend . ", nunca wildcard.",
    "No usar http://localhost/panel_de_control/ecommercePublico como base de integracion en este entorno."
  ),
  "validacion_erp" => array(
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_package_readonly.php --base=" . $base,
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_cors_preflight_readonly.php --base=" . $base . " --origin=" . $frontend
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
