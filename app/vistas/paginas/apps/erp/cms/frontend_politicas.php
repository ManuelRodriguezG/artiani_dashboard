<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-08-19.
 * Proposito: entrada operativa CMS > Frontend > Politicas.
 * Impacto: permite preparar politicas publicas como privacidad, envios, devoluciones y terminos.
 * Contrato: editor local; no escribe BD, no publica cambios reales y no permite JS libre.
 */
$cmsFrontendTitulo = "CMS - Frontend Politicas";
$cmsFrontendHeading = "CMS / Frontend / Politicas";
$cmsFrontendSubtitulo = "Administra politicas publicas, avisos y textos legales del ecommerce";
$cmsFrontendGrupoInicial = "politicas";
$cmsFrontendAvisoTitulo = "Politicas del ecommerce";
$cmsFrontendAvisoTexto = "Aqui se preparan textos de privacidad, envios, devoluciones y terminos. Por ahora el editor genera preview JSON local; la revision legal y persistencia real quedan pendientes.";
require __DIR__ . "/frontend_actual.php";
