<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-08-14.
 * Proposito: entrada operativa CMS > Frontend > Home.
 * Impacto: reutiliza el editor alineado al contrato actual, pero presentado como pagina Home.
 * Contrato: no escribe BD, no edita archivos del frontend y no modifica catalogo/precios/inventario.
 */
$cmsFrontendTitulo = "CMS - Frontend Home";
$cmsFrontendHeading = "CMS / Frontend / Home";
$cmsFrontendSubtitulo = "Administra hero, categorias, productos destacados, colecciones y banner de Home";
$cmsFrontendGrupoInicial = "home";
$cmsFrontendAvisoTitulo = "Home del ecommerce";
$cmsFrontendAvisoTexto = "Aqui se configura el contenido visual que necesita la portada del frontend. Por ahora el editor es local y genera el JSON esperado; despues se conectara a media, persistencia y API publica.";
require __DIR__ . "/frontend_actual.php";
