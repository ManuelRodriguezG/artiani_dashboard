"use strict";

const fs = require("fs");
const vm = require("vm");

function escapeHtml(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

class Element {
  constructor(id) {
    this.id = id;
    this.innerHTML = "";
    this.value = "";
    this.checked = false;
    this.listeners = {};
  }

  addEventListener(event, callback) {
    this.listeners[event] = callback;
  }

  set textContent(value) {
    this.innerHTML = escapeHtml(value);
  }
}

const elements = {};
[
  "inventario_filtro_buscar",
  "inventario_filtro_almacen",
  "inventario_filtro_estado_fisico",
  "inventario_filtro_agotadas",
  "inventario_recargar",
  "inventario_diagnostico",
  "inventario_resumen",
  "inventario_existencias",
  "inventario_movimientos",
  "inventario_unidades_resumen",
  "inventario_unidades",
  "inventario_valuacion_resumen",
  "inventario_valuacion",
  "inventario_trazabilidad_modal",
  "inventario_trazabilidad_titulo",
  "inventario_trazabilidad_body"
].forEach((id) => {
  elements[id] = new Element(id);
});
elements.inventario_filtro_buscar.value = "PREP-20260621-0003";

let domReady = null;
const context = {
  console,
  setTimeout,
  clearTimeout,
  URLSearchParams,
  Promise,
  Number,
  String,
  document: {
    createElement: () => new Element("created"),
    getElementById: (id) => elements[id],
    querySelector: () => null,
    addEventListener: (event, callback) => {
      if (event === "DOMContentLoaded") {
        domReady = callback;
      }
    }
  },
  window: { location: { hash: "" } },
  bootstrap: {
    Tab: { getOrCreateInstance: () => ({ show: () => {} }) },
    Modal: { getOrCreateInstance: () => ({ show: () => {} }) }
  },
  Swal: { fire: (options) => { throw new Error(options.text || "Swal.fire"); } },
  fetch: (url) => {
    const urlTexto = String(url);
    let payload = { error: false, tipo: "success", mensaje: "UAT", depurar: [] };
    if (urlTexto.indexOf("/inventario/catalogos_erp") === 0) {
      payload.depurar = {
        almacenes: [{ id_almacen: 3, almacen: "Francisco Javier Mina 971 - Bodega trasera" }]
      };
    } else if (urlTexto.indexOf("/inventario/existencias_erp") === 0) {
      payload.depurar = [
        {
          sku: "TP-40372-100GR",
          nombre_sku: "Presentacion 100 g",
          producto: "TP-40372",
          codigo_existencia: "EXI-50-30",
          almacen: "Francisco Javier Mina 971 - Bodega trasera",
          lote: "L1",
          fecha_caducidad: "2026-10-30",
          ubicacion: "E1-C2-P1-A1-N3",
          cantidad: "5.0000",
          cantidad_disponible: "5.0000",
          cantidad_apartada: "0.0000",
          costo_promedio: "73.7069",
          unidades_total: "5",
          unidades_disponibles: "5",
          unidades_cerradas: "5",
          unidades_abiertas: "0",
          unidades_consumidas: "0",
          etiquetas_pendientes: "0",
          etiquetas_impresas: "0",
          etiquetas_pegadas: "5",
          contenido_base_original: "5.000000",
          contenido_base_disponible: "5.000000",
          unidad_base_trazable: "pza",
          diferencia_contenido_unidades: "0.000000"
        },
        {
          sku: "TP-40372-500GR",
          nombre_sku: "Presentacion 500 g",
          producto: "TP-40372",
          codigo_existencia: "EXI-50-29",
          almacen: "Francisco Javier Mina 971 - Bodega trasera",
          lote: "L1",
          fecha_caducidad: "2026-10-30",
          ubicacion: "E1-C2-P1-A1-N3",
          cantidad: "0.0000",
          cantidad_disponible: "0.0000",
          cantidad_apartada: "0.0000",
          costo_promedio: "368.5345",
          unidades_total: "1",
          unidades_disponibles: "0",
          unidades_cerradas: "0",
          unidades_abiertas: "0",
          unidades_consumidas: "1",
          etiquetas_pendientes: "0",
          etiquetas_impresas: "0",
          etiquetas_pegadas: "1",
          contenido_base_original: "1.000000",
          contenido_base_disponible: "0.000000",
          unidad_base_trazable: "pza",
          diferencia_contenido_unidades: "0.000000"
        },
        {
          sku: "TP-40372",
          nombre_sku: "Alimento churro blanco para peces agranel",
          producto: "TP-40372",
          codigo_existencia: "EXI-50-26",
          almacen: "Francisco Javier Mina 971 - Bodega trasera",
          lote: "L1",
          fecha_caducidad: "2026-10-30",
          ubicacion: "E1-C2-P1-A1-N3",
          cantidad: "14.9500",
          cantidad_disponible: "14.9500",
          cantidad_apartada: "0.0000",
          costo_promedio: "184.2672",
          unidades_total: "1",
          unidades_disponibles: "1",
          unidades_cerradas: "0",
          unidades_abiertas: "1",
          unidades_consumidas: "0",
          etiquetas_pendientes: "0",
          etiquetas_impresas: "0",
          etiquetas_pegadas: "1",
          contenido_base_original: "14.975000",
          contenido_base_disponible: "14.950000",
          unidad_base_trazable: "kg",
          diferencia_contenido_unidades: "0.000000"
        }
      ];
    } else if (urlTexto.indexOf("/inventario/movimientos_erp") === 0) {
      payload.depurar = [
        {
          id_movimiento_inventario: 42,
          fecha_registro: "2026-06-21 23:04:39",
          tipo_movimiento: "entrada",
          origen_tipo: "preparacion_presentacion",
          referencia: "PREP-20260621-0003",
          almacen: "Francisco Javier Mina 971 - Bodega trasera",
          sku: "TP-40372-100GR",
          producto: "Presentacion 100 g",
          codigo_existencia: "EXI-50-30",
          cantidad: "5.0000",
          existencia_anterior: "0.0000",
          existencia_nueva: "5.0000"
        },
        {
          id_movimiento_inventario: 41,
          fecha_registro: "2026-06-21 23:04:39",
          tipo_movimiento: "salida",
          origen_tipo: "preparacion_presentacion",
          referencia: "PREP-20260621-0003",
          almacen: "Francisco Javier Mina 971 - Bodega trasera",
          sku: "TP-40372-500GR",
          producto: "Presentacion 500 g",
          codigo_existencia: "EXI-50-29",
          cantidad: "1.0000",
          existencia_anterior: "1.0000",
          existencia_nueva: "0.0000"
        }
      ];
    } else if (urlTexto.indexOf("/inventario/unidades_erp") === 0) {
      if (urlTexto.indexOf("TP-40372-500GR") === -1) {
        payload.depurar = [
          {
            codigo_etiqueta_interna: "P100-P000004-0001",
            tipo_identidad: "etiqueta_interna",
            sku: "TP-40372-100GR",
            producto: "Presentacion 100 g",
            almacen: "Francisco Javier Mina 971 - Bodega trasera",
            ubicacion: "E1-C2-P1-A1-N3",
            lote: "L1",
            fecha_caducidad: "2026-10-30",
            folio_recepcion: "PREP-20260621-0003",
            folio_orden_compra: "Preparacion/Empaque",
            estado_etiqueta: "pegada",
            estatus: "disponible",
            cantidad_base_original: "1.000000",
            cantidad_base_disponible: "1.000000",
            unidad_base: "pza",
            estado_fisico: "cerrada",
            id_almacen: 3
          },
          {
            codigo_etiqueta_interna: "UAT-EXI-26-20260625-001",
            tipo_identidad: "etiqueta_interna",
            sku: "TP-40372",
            producto: "Alimento churro blanco para peces agranel",
            almacen: "Francisco Javier Mina 971 - Bodega trasera",
            ubicacion: "E1-C2-P1-A1-N3",
            lote: "L1",
            fecha_caducidad: "2026-10-30",
            folio_recepcion: "PREP-20260625-0002",
            folio_orden_compra: "Preparacion/Empaque",
            estado_etiqueta: "pegada",
            estatus: "disponible",
            cantidad_base_original: "14.975000",
            cantidad_base_disponible: "14.950000",
            unidad_base: "kg",
            estado_fisico: "abierta",
            id_almacen: 3
          }
        ];
      }
    } else if (urlTexto.indexOf("/inventario/diagnostico_erp") === 0) {
      payload.depurar = {
        resumen: { total_hallazgos: 1, criticos: 0, advertencias: 0, informativos: 1 },
        hallazgos: [
          {
            id: "INV-DIAG-ETQ",
            severidad: "info",
            titulo: "Etiquetas pendientes de ciclo fisico",
            total: 20,
            items: [
              {
                estado_etiqueta: "impresa",
                total: 20,
                primer_codigo: "P25-P000001-0001",
                ultimo_codigo: "P25-P000001-0020"
              }
            ]
          }
        ]
      };
    } else if (urlTexto.indexOf("/inventario/valuacion_erp") === 0) {
      payload.depurar = {
        resumen: {
          skus: 2,
          cantidad_total: 5,
          disponible_total: 5,
          apartada_total: 0,
          valor_total: 368.5345
        },
        items: [
          {
            sku: "TP-40372-100GR",
            producto: "Presentacion 100 g",
            almacen: "Francisco Javier Mina 971 - Bodega trasera",
            existencias: 1,
            cantidad_total: "5.0000",
            disponible_total: "5.0000",
            apartada_total: "0.0000",
            costo_promedio_estimado: "73.7069",
            valor_total: "368.5345"
          }
        ]
      };
    }
    return Promise.resolve({ json: () => Promise.resolve(payload) });
  }
};

vm.createContext(context);
vm.runInContext(fs.readFileSync("public/assets/js/custom/apps/erp/inventarios/existencias_erp.js", "utf8"), context);

if (!domReady) {
  throw new Error("No se registro DOMContentLoaded");
}

domReady();

setTimeout(() => {
  const checks = [
    ["existencias incluye 100GR", elements.inventario_existencias.innerHTML.includes("TP-40372-100GR")],
    ["existencias incluye 500GR agotada", elements.inventario_existencias.innerHTML.includes("TP-40372-500GR") && elements.inventario_existencias.innerHTML.includes("Agotada")],
    ["existencias muestra unidad abierta", elements.inventario_existencias.innerHTML.includes("Abiertas 1") && elements.inventario_existencias.innerHTML.includes("Trazable 14.9500 kg")],
    ["resumen cuenta agotadas", elements.inventario_resumen.innerHTML.includes("Agotadas 1")],
    ["resumen cuenta estados fisicos", elements.inventario_resumen.innerHTML.includes("Unid. cerradas 5") && elements.inventario_resumen.innerHTML.includes("Unid. abiertas 1")],
    ["kardex muestra origen preparacion", elements.inventario_movimientos.innerHTML.includes("preparacion_presentacion")],
    ["kardex muestra referencia", elements.inventario_movimientos.innerHTML.includes("PREP-20260621-0003")],
    ["unidades muestra etiqueta pegada", elements.inventario_unidades.innerHTML.includes("P100-P000004-0001") && elements.inventario_unidades.innerHTML.includes("Pegada")],
    ["unidades muestra estado fisico abierto", elements.inventario_unidades.innerHTML.includes("UAT-EXI-26-20260625-001") && elements.inventario_unidades.innerHTML.includes("Abierta") && elements.inventario_unidades.innerHTML.includes("14.9500 kg")],
    ["unidades enlaza a etiquetado", elements.inventario_unidades.innerHTML.includes("/almacen/etiquetado?") && elements.inventario_unidades.innerHTML.includes("estado_etiqueta=pegada")],
    ["existencias tiene trazabilidad", elements.inventario_existencias.innerHTML.includes("data-trazabilidad")],
    ["kardex tiene trazabilidad", elements.inventario_movimientos.innerHTML.includes("data-trazabilidad")],
    ["unidades tiene trazabilidad", elements.inventario_unidades.innerHTML.includes("data-trazabilidad")],
    ["diagnostico muestra etiquetas pendientes", elements.inventario_diagnostico.innerHTML.includes("INV-DIAG-ETQ") && elements.inventario_diagnostico.innerHTML.includes("Etiquetas pendientes")],
    ["valuacion muestra valor", elements.inventario_valuacion_resumen.innerHTML.includes("Valor") && elements.inventario_valuacion.innerHTML.includes("TP-40372-100GR")]
  ];
  const failed = checks.filter(([, ok]) => !ok);
  if (failed.length) {
    console.error(JSON.stringify({ ok: false, failed: failed.map(([name]) => name), html: {
      resumen: elements.inventario_resumen.innerHTML,
      existencias: elements.inventario_existencias.innerHTML,
      movimientos: elements.inventario_movimientos.innerHTML,
      unidades: elements.inventario_unidades.innerHTML
    }}, null, 2));
    process.exit(1);
  }

  console.log(JSON.stringify({
    ok: true,
    checks: checks.map(([name]) => name),
    rendered: {
      resumen: elements.inventario_resumen.innerHTML,
      existencias: elements.inventario_existencias.innerHTML,
      movimientos: elements.inventario_movimientos.innerHTML,
      unidades: elements.inventario_unidades.innerHTML
    }
  }, null, 2));
  elements.inventario_filtro_buscar.value = "TP-40372-500GR";
  elements.inventario_filtro_buscar.listeners.input();
  setTimeout(() => {
    const mensajeUnidades = elements.inventario_unidades.innerHTML.includes("Sin unidades etiquetadas") &&
      elements.inventario_unidades.innerHTML.includes("Existencias o Kardex");
    if (!mensajeUnidades) {
      console.error(JSON.stringify({ ok: false, failed: ["mensaje unidades sin etiqueta"], html: elements.inventario_unidades.innerHTML }, null, 2));
      process.exit(1);
    }
    console.log(JSON.stringify({
      ok: true,
      check: "unidades sin etiqueta aclara consultar Existencias o Kardex",
      rendered: elements.inventario_unidades.innerHTML
    }, null, 2));
  }, 350);
}, 500);
