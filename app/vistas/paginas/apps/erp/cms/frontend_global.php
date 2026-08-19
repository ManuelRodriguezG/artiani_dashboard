<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-08-19.
 * Proposito: entrada operativa CMS > Frontend > Global.
 * Impacto: permite capturar configuracion global del ecommerce publico.
 * Contrato: editor local; no escribe BD, no expone secretos y no edita archivos frontend.
 */
$cmsFrontendTitulo = "CMS - Frontend Global";
$cmsFrontendHeading = "CMS / Frontend / Global";
$cmsFrontendSubtitulo = "Administra negocio, contacto, logos, horarios, redes, SEO y navegacion global";
$cmsFrontendGrupoInicial = "global";
$cmsFrontendAvisoTitulo = "Configuracion global del ecommerce";
$cmsFrontendAvisoTexto = "Aqui se preparan los datos que despues alimentaran /ecommercePublico/configuracion_inicial: negocio, ubicacion, horarios, redes, SEO y navegacion. Por ahora el editor genera preview JSON local.";
require __DIR__ . "/frontend_actual.php";
