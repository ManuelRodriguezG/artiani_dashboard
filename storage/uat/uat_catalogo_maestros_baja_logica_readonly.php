<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-12
 * Proposito: validar baja/reactivacion logica de catalogos maestros sin modificar datos.
 * Impacto: Catalogo ERP; confirma que UI y backend estan alineados antes de pruebas reales.
 * Contrato: solo lectura; no llama endpoints POST ni cambia estatus de marcas, categorias, unidades o atributos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoMaestrosBajaLogicaReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $js = file_get_contents(__DIR__ . "/../../public/assets/js/custom/apps/erp/catalogo/configuracion.js");
    $vista = file_get_contents(__DIR__ . "/../../app/vistas/paginas/apps/erp/catalogo/configuracion.php");
    $modelo = file_get_contents(__DIR__ . "/../../app/modelos/CatalogoErpDatos.php");

    $conteos = array(
      "marcas_activas" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_marcas WHERE estatus='activa'"),
      "marcas_inactivas" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_marcas WHERE estatus='inactiva'"),
      "categorias_activas" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_categorias WHERE estatus='activa'"),
      "categorias_inactivas" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_categorias WHERE estatus='inactiva'"),
      "unidades_activas" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_unidades WHERE estatus='activa'"),
      "unidades_inactivas" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_unidades WHERE estatus='inactiva'"),
      "atributos_activos" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_atributos WHERE estatus='activo'"),
      "atributos_inactivos" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_atributos WHERE estatus='inactivo'")
    );

    $ui = array(
      "boton_estatus" => strpos($js, "data-estatus-maestro") !== false,
      "payload_completo" => strpos($js, "payloadCatalogoMaestro") !== false,
      "confirmacion_swal" => strpos($js, "Se inactivara el registro") !== false,
      "usa_auxiliar_guardar" => strpos($js, "/catalogoerp/auxiliar_guardar") !== false,
      "acciones_por_permiso_editar" => strpos($js, "if (!permisos.editar)") !== false,
      "imagenes_solo_marca_categoria" => strpos($js, "tipo === \"marca\" || tipo === \"categoria\"") !== false,
      "asset_version_publicada" => strpos($vista, "configuracion.js?v=20260712-maestros-2") !== false
    );

    $backend = array(
      "guarda_auxiliares" => strpos($modelo, "function guardarCatalogoAuxiliar") !== false,
      "bloquea_marca_en_uso" => strpos($modelo, "contarUsoMarcaCatalogo") !== false,
      "bloquea_categoria_en_uso" => strpos($modelo, "contarUsoCategoriaCatalogo") !== false,
      "bloquea_unidad_en_uso" => strpos($modelo, "contarUsoUnidadCatalogo") !== false,
      "bloquea_atributo_en_uso" => strpos($modelo, "contarUsoAtributoCatalogo") !== false
    );

    $ok = !in_array(false, $ui, true) && !in_array(false, $backend, true);
    return array(
      "ok" => $ok,
      "modo" => "catalogo_maestros_baja_logica_readonly",
      "conteos_actuales" => $conteos,
      "ui" => $ui,
      "backend" => $backend,
      "nota" => "Solo lectura; no se inactivo ni reactivo ningun catalogo maestro."
    );
  }

  private function contar($db, $sql) {
    return intval($db->query($sql)->fetchColumn());
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoMaestrosBajaLogicaReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
