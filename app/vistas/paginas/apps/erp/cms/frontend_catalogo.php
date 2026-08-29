<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-08-28.
 * Proposito: entrada operativa CMS > Frontend > Catalogo.
 * Impacto: permite preparar encabezado, SEO y estados editoriales del listado publico.
 * Contrato: editor protegido; no modifica productos, precios, filtros ni inventario.
 */
$cmsFrontendTitulo = "CMS - Frontend Catalogo";
$cmsFrontendHeading = "CMS / Frontend / Catalogo";
$cmsFrontendSubtitulo = "Administra encabezado, SEO y textos de apoyo del listado publico de productos";
$cmsFrontendGrupoInicial = "catalogo";
$cmsFrontendVistaDedicada = true;
$cmsFrontendAvisoTitulo = "Catalogo publico";
$cmsFrontendAvisoTexto = "Aqui se configura la capa editorial del listado: titulo, textos SEO y mensajes publicos. Los productos, filtros, precios e inventario siguen viniendo de sus APIs actuales.";
require __DIR__ . "/frontend_actual.php";
