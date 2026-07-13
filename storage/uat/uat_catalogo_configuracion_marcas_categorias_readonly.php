<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-12
 * Proposito: validar sin escritura el flujo de Configuracion para marcas con codigo automatico y categorias hijas bajo Acuario.
 * Impacto: Catalogo ERP; permite probar la preparacion de UI/backend sin crear registros de prueba.
 * Contrato: solo lectura; no ejecuta INSERT, UPDATE, DELETE ni DDL.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpDatos.php";

class UatCatalogoConfiguracionMarcasCategoriasReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $modelo = new CatalogoErpDatos();
    $codigo = new ReflectionMethod("CatalogoErpDatos", "codigoDesdeTexto");
    $codigo->setAccessible(true);

    $acuario = $this->consultarAcuario($db);
    $candidatosPadre = $this->contarPadresMaestros($db);
    $vista = file_get_contents(__DIR__ . "/../../app/vistas/paginas/apps/erp/catalogo/configuracion.php");
    $js = file_get_contents(__DIR__ . "/../../public/assets/js/custom/apps/erp/catalogo/configuracion.js");

    return array(
      "ok" => !empty($acuario) && $candidatosPadre["maestros_disponibles"] > 0 && $candidatosPadre["legado_ecommerce_en_padres"] === 0,
      "codigo_marca_muestra" => $codigo->invoke($modelo, "Marca prueba UAT", "MAR"),
      "codigo_categoria_muestra" => $codigo->invoke($modelo, "Categoria prueba UAT", "CAT"),
      "acuario" => $acuario,
      "selector_padres" => $candidatosPadre,
      "ui" => array(
        "ayuda_codigo_opcional" => strpos($vista, "En marcas y categorías puede quedar vacío") !== false,
        "ayuda_categoria_acuario" => strpos($vista, "Usa una raíz estructural como Acuario") !== false,
        "boton_nueva_marca" => strpos($vista, "Nueva marca") !== false,
        "boton_nueva_categoria" => strpos($vista, "Nueva categoria") !== false || strpos($vista, "Nueva categoría") !== false,
        "js_codigo_opcional" => strpos($js, "Se genera si lo dejas") !== false,
        "js_excluye_legado_ecommerce" => strpos($js, "categoriaEsLegadoEcommerce") !== false
      ),
      "criterio_manual" => array(
        "marca" => "Crear marca dejando Codigo vacio; debe guardarse con codigo MAR-*.",
        "categoria" => "Crear categoria hija seleccionando Acuario; debe quedar ruta Acuario / <nueva categoria>."
      ),
      "nota" => "UAT solo lectura; no crea marca ni categoria."
    );
  }

  private function consultarAcuario($db) {
    $stmt = $db->prepare("SELECT id_categoria_erp, codigo, nombre, ruta, nivel, tipo_categoria, permite_productos, estatus,
        (SELECT COUNT(*) FROM erp_catalogo_categorias h WHERE h.id_categoria_padre=c.id_categoria_erp AND h.estatus='activa') total_hijas
      FROM erp_catalogo_categorias c
      WHERE c.nombre='Acuario' AND c.tipo_categoria='maestra' AND c.estatus='activa'
      ORDER BY c.id_categoria_erp
      LIMIT 1");
    $stmt->execute();
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
  }

  private function contarPadresMaestros($db) {
    $maestros = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias WHERE tipo_categoria='maestra' AND estatus='activa'")->fetchColumn());
    $legado = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias WHERE tipo_categoria='maestra' AND estatus='activa' AND (codigo LIKE 'ECOM-CAT-%' OR tipo_categoria='legado_canal')")->fetchColumn());
    return array(
      "maestros_disponibles" => $maestros,
      "legado_ecommerce_en_padres" => $legado
    );
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoConfiguracionMarcasCategoriasReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
