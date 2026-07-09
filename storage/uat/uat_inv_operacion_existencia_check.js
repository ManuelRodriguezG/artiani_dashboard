"use strict";

const fs = require("fs");

const js = fs.readFileSync("public/assets/js/custom/apps/erp/inventarios/operacion_erp.js", "utf8");

const checks = [
  ["detecta salida/traspaso", js.includes("function esSalidaOperacion()")],
  ["consulta existencias ERP por SKU/almacen", js.includes("/inventario/existencias_erp?")],
  ["filtra disponibilidad positiva", js.includes("Number(existencia.cantidad_disponible || 0) > 0")],
  ["guarda id_existencia_inventario", js.includes("partida.id_existencia_inventario = existencia.id_existencia_inventario")],
  ["renderiza selector de existencia", js.includes("data-partida-existencia")],
  ["valida existencia fisica en salidas", js.includes("Selecciona la existencia fisica")],
  ["limpia partidas al cambiar almacen origen", js.includes("inventario_almacen_origen") && js.includes("partidas = [];")],
  ["permite multiples lineas del mismo SKU", !js.includes("partidas.some(function (p) { return String(p.id_sku) === String(item.id_sku); })")],
  ["valida acumulado por existencia", js.includes("acumuladoPorExistencia")],
  ["usa incremento minimo como paso", js.includes("function pasoPartida(item)")],
  ["muestra regla fraccionaria", js.includes("Fraccionario")],
  ["muestra regla de etiqueta", js.includes("Etiqueta")],
  ["bloquea decimales para SKU entero", js.includes("debe ser entera")],
  ["envia documento de operacion", js.includes("data.documento_operacion = documentoOperacionActual()")],
  ["envia motivo de ajuste", js.includes("data.motivo_ajuste = document.getElementById(\"inventario_motivo_ajuste\").value")],
  ["valida motivo de ajuste", js.includes("Selecciona motivo de ajuste")],
  ["incluye motivos operativos", js.includes("merma") && js.includes("caducado") && js.includes("sobrante_conteo") && js.includes("faltante_conteo")],
  ["exige referencia de inventario inicial", js.includes("INV-INICIAL-")],
  ["valida lote requerido", js.includes("Captura lote para")],
  ["valida caducidad requerida", js.includes("Captura caducidad para")],
  ["limpia partidas al cambiar documento", js.includes("inventario_documento_operacion") && js.includes("actualizarDocumentoOperacion")]
];

const failed = checks.filter(([, ok]) => !ok);

if (failed.length) {
  console.error(JSON.stringify({ ok: false, failed: failed.map(([name]) => name) }, null, 2));
  process.exit(1);
}

console.log(JSON.stringify({ ok: true, checks: checks.map(([name]) => name) }, null, 2));
