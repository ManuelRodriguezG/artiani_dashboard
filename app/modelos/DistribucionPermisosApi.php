<?php

class DistribucionPermisosApi extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: construir contexto comercial externo sin usar la sesion interna del ERP.
   * Impacto: API Distribucion; separa usuario ERP de cliente externo B2B.
   * Contrato: lee headers Authorization/X-Canal-Comercial y devuelve perfil publico seguro.
   */
  public function contextoDesdeRequest() {
    $canal = isset($_SERVER["HTTP_X_CANAL_COMERCIAL"]) ? trim((string) $_SERVER["HTTP_X_CANAL_COMERCIAL"]) : "distribucion";
    $token = $this->tokenBearer();
    $perfil = $token !== "" ? $this->perfilPorToken($token) : null;
    if (is_array($perfil)) {
      $permisos = $this->valor($perfil, "permisos", array());
      return array(
        "canal" => $canal === "" ? "distribucion" : $canal,
        "token_presente" => true,
        "autenticado" => true,
        "id_cliente_distribucion" => intval($this->valor($perfil, "id_cliente_distribucion", 0)),
        "tipo_cliente" => $this->valor($perfil, "tipo_cliente", "registrado"),
        "estatus" => $this->valor($perfil, "estatus", null),
        "id_lista_precio" => $this->valor($perfil, "id_lista_precio", null),
        "permisos" => $permisos,
        "acciones" => $this->accionesPermitidas($permisos)
      );
    }
    return array(
      "canal" => $canal === "" ? "distribucion" : $canal,
      "token_presente" => $token !== "",
      "autenticado" => false,
      "id_cliente_distribucion" => null,
      "tipo_cliente" => "publico",
      "estatus" => null,
      "id_lista_precio" => null,
      "permisos" => array(),
      "acciones" => $this->accionesPermitidas(array())
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar permisos comerciales reconocidos por contrato Distribucion.
   * Impacto: Frontend Distribucion y admin ERP; evita inventar permisos en UI.
   * Contrato: devuelve codigos estables, no asigna permisos.
   */
  public function permisosComerciales() {
    return array(
      "distribucion.catalogo.ver",
      "distribucion.catalogo.ver_detalle",
      "distribucion.precio.ver_publico",
      "distribucion.precio.ver_mayoreo",
      "distribucion.precio.ver_lista_asignada",
      "distribucion.inventario.ver_disponibilidad",
      "distribucion.cotizacion.solicitar",
      "distribucion.pedido.preliminar",
      "distribucion.catalogo.descargar",
      "distribucion.cuenta.editar"
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: resolver acciones UI desde permisos comerciales externos.
   * Impacto: Frontend Distribucion; centraliza botones permitidos por contrato.
   * Contrato: recibe arreglo de permisos y devuelve banderas booleanas.
   */
  public function accionesPermitidas($permisos) {
    $permisos = is_array($permisos) ? $permisos : array();
    return array(
      "ver_catalogo" => in_array("distribucion.catalogo.ver", $permisos, true),
      "ver_detalle" => in_array("distribucion.catalogo.ver_detalle", $permisos, true),
      "ver_precio" => in_array("distribucion.precio.ver_publico", $permisos, true) || in_array("distribucion.precio.ver_mayoreo", $permisos, true) || in_array("distribucion.precio.ver_lista_asignada", $permisos, true),
      "ver_disponibilidad" => in_array("distribucion.inventario.ver_disponibilidad", $permisos, true),
      "agregar_cotizacion" => in_array("distribucion.cotizacion.solicitar", $permisos, true),
      "pedido_preliminar" => in_array("distribucion.pedido.preliminar", $permisos, true),
      "descargar_catalogo" => in_array("distribucion.catalogo.descargar", $permisos, true),
      "editar_cuenta" => in_array("distribucion.cuenta.editar", $permisos, true)
    );
  }

  private function tokenBearer() {
    $header = "";
    if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
      $header = trim((string) $_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
      $header = trim((string) $_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
    }
    if (stripos($header, "Bearer ") === 0) {
      return trim(substr($header, 7));
    }
    return "";
  }

  private function perfilPorToken($token) {
    require_once RUTA_APP . "/modelos/DistribucionClientesApi.php";
    return (new DistribucionClientesApi())->perfilPorToken($token);
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }
}
