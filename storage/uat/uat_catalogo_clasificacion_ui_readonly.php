<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-12
 * Proposito: validar controles UI de clasificacion pendiente sin modificar datos.
 * Impacto: Catalogo ERP; confirma que la captura masiva por visibles esta publicada y enlazada.
 * Contrato: solo lectura sobre archivos de vista/JS; no consulta ni escribe BD.
 */

$vista = file_get_contents(__DIR__ . "/../../app/vistas/paginas/apps/erp/catalogo/configuracion.php");
$js = file_get_contents(__DIR__ . "/../../public/assets/js/custom/apps/erp/catalogo/configuracion.js");

$validaciones = array(
  "boton_seleccionar_visibles" => strpos($vista, "catalogo_clasificacion_seleccionar_visibles") !== false,
  "boton_limpiar_seleccion" => strpos($vista, "catalogo_clasificacion_limpiar_seleccion") !== false,
  "contador_seleccion" => strpos($vista, "catalogo_clasificacion_seleccion_info") !== false,
  "contenedor_sugerencias" => strpos($vista, "catalogo_clasificacion_sugerencias") !== false,
  "funcion_seleccionar_visibles" => strpos($js, "function seleccionarClasificacionVisible") !== false,
  "seleccion_solo_pagina_visible" => strpos($js, "pagina = visibles.slice") !== false,
  "funcion_limpiar_seleccion" => strpos($js, "function limpiarSeleccionClasificacion") !== false,
  "funcion_actualizar_contador" => strpos($js, "function actualizarSeleccionClasificacion") !== false,
  "funcion_sugerencias" => strpos($js, "function sugerenciasClasificacion") !== false,
  "listener_sugerencias" => strpos($js, "data-clasificacion-sugerencia") !== false,
  "atajos_marcas_ambiguas" => strpos($js, "data-clasificacion-marca-rapida") !== false,
  "funcion_candidatas_marca" => strpos($js, "function renderCandidatasMarca") !== false,
  "confirmacion_guardado" => strpos($js, "Confirmar asignaciones") !== false,
  "resumen_guardado" => strpos($js, "function resumenAsignacionesMetadatos") !== false,
  "enlace_producto" => strpos($js, "/catalogoerp?id_producto_erp=") !== false,
  "listeners_conectados" => strpos($js, "catalogo_clasificacion_seleccionar_visibles") !== false && strpos($js, "catalogo_clasificacion_limpiar_seleccion") !== false,
  "asset_version_publicada" => strpos($vista, "configuracion.js?v=20260712-maestros-8") !== false
);

echo json_encode(array(
  "ok" => !in_array(false, $validaciones, true),
  "modo" => "catalogo_clasificacion_ui_readonly",
  "validaciones" => $validaciones,
  "nota" => "Solo lectura; no modifica catalogos, productos ni asignaciones."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
