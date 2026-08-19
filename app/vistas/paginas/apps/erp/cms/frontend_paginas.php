<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-08-19.
 * Proposito: entrada operativa CMS > Frontend > Paginas.
 * Impacto: permite preparar paginas estaticas publicas como ayuda, contacto o facturacion.
 * Contrato: editor local; no escribe BD, no edita archivos frontend y no permite JS libre.
 */
$cmsFrontendTitulo = "CMS - Frontend Paginas";
$cmsFrontendHeading = "CMS / Frontend / Paginas";
$cmsFrontendSubtitulo = "Administra paginas informativas del ecommerce sin editar archivos del frontend";
$cmsFrontendGrupoInicial = "paginas";
$cmsFrontendAvisoTitulo = "Paginas estaticas del ecommerce";
$cmsFrontendAvisoTexto = "Aqui se preparan paginas como Como comprar, Facturacion o Contacto. Por ahora el editor genera preview JSON local; la persistencia y API publica se conectaran despues.";
require __DIR__ . "/frontend_actual.php";
