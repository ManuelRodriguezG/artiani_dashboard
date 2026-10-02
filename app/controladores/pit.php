<?php

class pit extends Controlador {

  /**
   * Documentacion IA: Codex GPT-6 | Fecha: 2026-09-30
   * Proposito: abrir PIT como modulo independiente para preparar imagenes antes de asignarlas a otros modulos.
   * Impacto: PIT/Media; reutiliza la biblioteca media existente sin modificar Catalogo ni CMS.
   * Contrato: vista protegida; no ejecuta DDL ni crea relaciones con productos en esta fase.
   */
  public function index() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/pit/index");
  }

  /**
   * Documentacion IA: Codex GPT-6 | Fecha: 2026-09-30
   * Proposito: entregar permisos/capacidades de PIT usando Media como almacenamiento inicial.
   * Impacto: PIT; mantiene endpoints propios para no acoplar la UI al controlador CMS.
   * Contrato: GET protegido; solo consulta estado y permisos efectivos.
   */
  public function media_preflight_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $plan = $this->modelo("EcommercePublicoEsquema")->planActualizarCmsMediaBiblioteca(false);
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->mediaAdminPreflightInterno($plan);
    $seguridad = $this->modelo("SeguridadPermisos");
    $id = $this->usuarioActualId();
    $puedeCatalogo = $seguridad->usuarioTienePermiso($id, "catalogo.editar");
    $respuesta["depurar"]["modulo"] = "pit";
    $respuesta["depurar"]["pantalla"] = "/pit";
    $respuesta["depurar"]["permisos"] = array(
      "editar" => $puedeCatalogo || $seguridad->usuarioTienePermiso($id, "cms.editar"),
      "publicar" => $puedeCatalogo || $seguridad->usuarioTienePermiso($id, "cms.publicar"),
      "asignar" => false
    );
    $respuesta["depurar"]["endpoints"] = array(
      "listar" => "/pit/media_listar_erp",
      "subir" => "/pit/media_subir_erp",
      "usos" => "/pit/media_usos_erp"
    );
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-6 | Fecha: 2026-09-30
   * Proposito: listar imagenes reutilizables desde PIT sin depender del controlador CMS.
   * Impacto: PIT/Media; no modifica datos.
   * Contrato: GET protegido; respeta paginacion/filtros del modelo compartido.
   */
  public function media_listar_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->mediaAdminListarInterno($_GET), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-6 | Fecha: 2026-09-30
   * Proposito: subir imagenes procesadas desde PIT a la biblioteca media compartida.
   * Impacto: PIT/Media; crea un asset reutilizable, no asigna a Catalogo ni CMS.
   * Contrato: POST con CSRF global, permisos de edicion y auditoria explicita.
   */
  public function media_subir_erp() {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    $this->pitRequerirPost();
    $post = $_POST;
    $post["origen"] = "pit";
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->mediaAdminSubirInterno(
      isset($_FILES["archivo"]) ? $_FILES["archivo"] : array(),
      $post,
      $this->usuarioActualId()
    );
    Sesionseguridad::registrarAuditoria("pit", "media_subir_erp", array(
      "id_registro" => isset($respuesta["depurar"]["id_media_archivo"]) ? $respuesta["depurar"]["id_media_archivo"] : null,
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "datos_despues" => array(
        "error" => isset($respuesta["error"]) ? (bool) $respuesta["error"] : true,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "codigo" => isset($respuesta["depurar"]["codigo"]) ? $respuesta["depurar"]["codigo"] : "",
        "url" => isset($respuesta["depurar"]["url"]) ? $respuesta["depurar"]["url"] : "",
        "uso" => isset($post["uso"]) ? $post["uso"] : "",
        "tipo" => isset($post["tipo"]) ? $post["tipo"] : ""
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-6 | Fecha: 2026-09-30
   * Proposito: consultar usos guardados de un asset desde PIT.
   * Impacto: PIT/Media; ayuda a decidir si una imagen esta libre o reutilizada.
   * Contrato: GET protegido; no crea asignaciones nuevas.
   */
  public function media_usos_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->mediaAdminUsosInterno($_GET), JSON_UNESCAPED_UNICODE);
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-30
   * Proposito: bloquear mutaciones PIT fuera de POST.
   * Impacto: altas de imagenes; conserva validacion CSRF global.
   * Contrato: responde JSON/405 y detiene la accion.
   */
  private function pitRequerirPost() {
    if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "POST") {
      return;
    }
    http_response_code(405);
    header("Allow: POST");
    echo json_encode(array(
      "error" => true,
      "tipo" => "warning",
      "mensaje" => "Esta accion requiere POST.",
      "depurar" => array()
    ), JSON_UNESCAPED_UNICODE);
    exit;
  }
}
