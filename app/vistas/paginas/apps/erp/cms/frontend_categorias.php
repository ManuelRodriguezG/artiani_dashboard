<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-08-19.
 * Proposito: entrada operativa CMS > Frontend > Categorias.
 * Impacto: permite preparar imagenes, SEO, orden y destacados de categorias publicas.
 * Contrato: editor local; no escribe BD, no modifica catalogo ERP, precios ni inventario.
 */
$cmsFrontendTitulo = "CMS - Frontend Categorias";
$cmsFrontendHeading = "CMS / Frontend / Categorias";
$cmsFrontendSubtitulo = "Administra imagenes, banners, SEO, visibilidad y orden editorial de categorias";
$cmsFrontendGrupoInicial = "categorias";
$cmsFrontendAvisoTitulo = "Categorias publicas del ecommerce";
$cmsFrontendAvisoTexto = "Aqui se prepara la capa editorial de categorias: imagen card, banner, textos SEO, destacado y orden. Las categorias reales seguiran viniendo del ERP/API publica; el CMS solo las enriquece.";
require __DIR__ . "/frontend_actual.php";
