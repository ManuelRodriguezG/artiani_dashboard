<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-08-19.
 * Proposito: entrada operativa CMS > Frontend > Navegacion.
 * Impacto: permite preparar menu principal, topbar, footer y CTAs del ecommerce publico.
 * Contrato: editor local; no escribe BD, no edita archivos frontend y no expone rutas internas ERP.
 */
$cmsFrontendTitulo = "CMS - Frontend Navegacion";
$cmsFrontendHeading = "CMS / Frontend / Navegacion";
$cmsFrontendSubtitulo = "Administra topbar, menu principal, footer y CTAs globales del ecommerce";
$cmsFrontendGrupoInicial = "navegacion";
$cmsFrontendAvisoTitulo = "Navegacion del ecommerce";
$cmsFrontendAvisoTexto = "Aqui se prepara la estructura visible de header y footer que despues entregara /ecommercePublico/configuracion_inicial. Por ahora el editor genera preview JSON local.";
require __DIR__ . "/frontend_actual.php";
