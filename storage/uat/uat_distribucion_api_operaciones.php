<?php

require_once __DIR__ . "/../../app/iniciador.php";
define("DISTRIBUCION_API_DEBUG", true);
require_once RUTA_APP . "/modelos/DistribucionClientesApi.php";
require_once RUTA_APP . "/modelos/DistribucionCotizacionesApi.php";
require_once RUTA_APP . "/modelos/DistribucionCatalogoApi.php";

class UatDistribucionApiDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$dbModel = new UatDistribucionApiDb();
$db = $dbModel->db();
$clientes = new DistribucionClientesApi();
$cotizaciones = new DistribucionCotizacionesApi();
$catalogo = new DistribucionCatalogoApi();
$sufijo = date("YmdHis");
$correo = "codex.dist.uat+" . $sufijo . "@example.invalid";
$ids = array("solicitud" => null, "cliente" => null, "cotizacion" => null, "canal_vinculo" => null);
$resultado = array("checks" => array(), "cleanup" => array());

function uatOk($respuesta) {
  return is_array($respuesta) && empty($respuesta["error"]);
}

try {
  $lista = $db->query("SELECT id_lista_precio, nombre FROM erp_listas_precios WHERE estatus='activa' ORDER BY prioridad ASC, id_lista_precio ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  $sku = $db->query("SELECT s.id_sku, s.sku
    FROM erp_catalogo_canales_vinculos cv
    INNER JOIN erp_catalogo_skus s ON s.id_sku=cv.id_sku
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    WHERE cv.canal='distribucion'
      AND cv.sincronizar_catalogo=1
      AND cv.sincronizar_precio=1
      AND cv.estatus IN ('activo','publicado','aprobado')
      AND p.estatus='activo'
      AND s.estatus='activo'
    ORDER BY s.id_sku ASC
    LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if (!$sku && $lista) {
    $stmtSku = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      INNER JOIN erp_listas_precios_detalle d ON d.id_sku=s.id_sku
      WHERE s.estatus='activo'
        AND p.estatus='activo'
        AND d.id_lista_precio=:lista
        AND d.estatus='activo'
        AND d.precio>0
      ORDER BY s.id_sku ASC
      LIMIT 1");
    $stmtSku->execute(array(":lista" => intval($lista["id_lista_precio"])));
    $sku = $stmtSku->fetch(PDO::FETCH_ASSOC);
    if ($sku) {
      $idExterno = "uat-dist-" . $sufijo;
      $stmtVinculo = $db->prepare("INSERT INTO erp_catalogo_canales_vinculos
        (id_producto_erp, id_sku, canal, id_externo, sku_externo, sincronizar_catalogo, sincronizar_precio, sincronizar_existencia, estatus, fecha_registro, fecha_actualizacion)
        VALUES (:producto, :sku, 'distribucion', :externo, :sku_externo, 1, 1, 1, 'activo', NOW(), NOW())");
      $stmtVinculo->execute(array(
        ":producto" => intval($sku["id_producto_erp"]),
        ":sku" => intval($sku["id_sku"]),
        ":externo" => $idExterno,
        ":sku_externo" => $sku["sku"]
      ));
      $ids["canal_vinculo"] = intval($db->lastInsertId());
    }
  }

  $registro = $clientes->registrarSolicitud(array(
    "nombre" => "Cliente UAT Distribucion",
    "empresa" => "Empresa UAT Distribucion",
    "correo" => $correo,
    "telefono" => "5555555555",
    "tipo_interes" => "revendedor",
    "mensaje" => "Alta UAT temporal"
  ));
  $resultado["checks"]["registro_solicitud"] = $registro;
  $folio = isset($registro["depurar"]["folio"]) ? $registro["depurar"]["folio"] : "";
  $stmt = $db->prepare("SELECT id_solicitud_distribucion FROM erp_distribucion_solicitudes WHERE folio=:folio LIMIT 1");
  $stmt->execute(array(":folio" => $folio));
  $ids["solicitud"] = intval($stmt->fetchColumn());

  $aprobacion = $clientes->clienteAprobarPlanInterno(array(
    "id_solicitud_distribucion" => $ids["solicitud"],
    "contrasenia" => "UatDist123!"
  ), 0);
  $resultado["checks"]["aprobar_cliente"] = $aprobacion;
  $ids["cliente"] = isset($aprobacion["depurar"]["id_cliente_distribucion"]) ? intval($aprobacion["depurar"]["id_cliente_distribucion"]) : 0;

  $resultado["checks"]["asignar_tipo"] = $clientes->tipoClientePlanInterno(array(
    "id_cliente_distribucion" => $ids["cliente"],
    "tipo_cliente" => "mayorista"
  ), 0);

  if ($lista) {
    $resultado["checks"]["asignar_lista"] = $clientes->listaPrecioPlanInterno(array(
      "id_cliente_distribucion" => $ids["cliente"],
      "id_lista_precio" => intval($lista["id_lista_precio"])
    ), 0);
  } else {
    $resultado["checks"]["asignar_lista"] = array("omitido" => true, "motivo" => "sin_lista_activa");
  }

  $permisos = array(
    "distribucion.catalogo.ver",
    "distribucion.catalogo.ver_detalle",
    "distribucion.precio.ver_lista_asignada",
    "distribucion.inventario.ver_disponibilidad",
    "distribucion.cotizacion.solicitar"
  );
  $resultado["checks"]["asignar_permisos"] = $clientes->permisosPlanInterno(array(
    "id_cliente_distribucion" => $ids["cliente"],
    "permisos" => $permisos
  ), 0);

  $login = $clientes->login(array("correo" => $correo, "contrasenia" => "UatDist123!"));
  $resultado["checks"]["login"] = array(
    "ok" => uatOk($login),
    "mensaje" => isset($login["mensaje"]) ? $login["mensaje"] : null,
    "token_emitido" => !empty($login["depurar"]["token"])
  );

  if ($sku && !empty($login["depurar"]["perfil"])) {
    $contexto = $login["depurar"]["perfil"];
    $contexto["autenticado"] = true;
    $contexto["canal"] = "distribucion";
    $datosCotizacion = array(
      "items" => array(array("id_sku" => intval($sku["id_sku"]), "cantidad" => 1)),
      "comentarios" => "Cotizacion UAT temporal"
    );
    try {
      $stmtPrecioDebug = $db->prepare("SELECT d.id_sku, d.precio, COALESCE(NULLIF(d.moneda, ''), 'MXN') moneda,
          l.id_lista_precio, l.codigo lista_codigo, l.nombre lista_nombre, l.prioridad
        FROM erp_listas_precios_detalle d
        INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio
        WHERE d.id_sku=:sku
          AND d.estatus='activo'
          AND d.precio>0
          AND COALESCE(NULLIF(d.moneda, ''), 'MXN')='MXN'
          AND l.id_lista_precio=:lista
          AND l.estatus='activa'
          AND (d.fecha_inicio IS NULL OR d.fecha_inicio<=NOW())
          AND (d.fecha_fin IS NULL OR d.fecha_fin>=NOW())
          AND (l.fecha_inicio IS NULL OR l.fecha_inicio<=NOW())
          AND (l.fecha_fin IS NULL OR l.fecha_fin>=NOW())
        LIMIT 1");
      $stmtPrecioDebug->execute(array(":sku" => intval($sku["id_sku"]), ":lista" => intval($lista["id_lista_precio"])));
      $resultado["checks"]["precio_sql_debug"] = $stmtPrecioDebug->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $resultado["checks"]["precio_sql_debug"] = array("error" => true, "mensaje" => $e->getMessage());
    }
    $resultado["checks"]["resolver_precios"] = $catalogo->resolverPrecios($datosCotizacion, $contexto);
    $resultado["checks"]["resolver_disponibilidad"] = $catalogo->resolverDisponibilidad($datosCotizacion, $contexto);
    $resultado["checks"]["dryrun_cotizacion"] = $cotizaciones->dryRun($datosCotizacion, $contexto);
    $cotizacion = $cotizaciones->registrar($datosCotizacion, $contexto);
    $resultado["checks"]["registrar_cotizacion"] = $cotizacion;
    $ids["cotizacion"] = isset($cotizacion["depurar"]["id_cotizacion_distribucion"]) ? intval($cotizacion["depurar"]["id_cotizacion_distribucion"]) : 0;
    if ($ids["cotizacion"] > 0) {
      $resultado["checks"]["accion_cotizacion"] = $cotizaciones->cotizacionAccionPlanInterna(array(
        "id_cotizacion_distribucion" => $ids["cotizacion"],
        "accion" => "tomar",
        "nota" => "UAT temporal"
      ), 0);
    }
  } else {
    $resultado["checks"]["registrar_cotizacion"] = array("omitido" => true, "motivo" => "sin_sku_visible_o_login");
  }
} finally {
  if (!empty($ids["cotizacion"])) {
    $db->prepare("DELETE FROM erp_distribucion_cotizacion_items WHERE id_cotizacion_distribucion=:id")->execute(array(":id" => $ids["cotizacion"]));
    $resultado["cleanup"]["cotizacion_items"] = true;
    $db->prepare("DELETE FROM erp_distribucion_cotizaciones WHERE id_cotizacion_distribucion=:id")->execute(array(":id" => $ids["cotizacion"]));
    $resultado["cleanup"]["cotizacion"] = true;
    $db->prepare("DELETE FROM erp_distribucion_auditoria WHERE entidad='cotizacion' AND id_entidad=:id")->execute(array(":id" => $ids["cotizacion"]));
  }
  if (!empty($ids["cliente"])) {
    $db->prepare("DELETE FROM erp_distribucion_tokens WHERE id_cliente_distribucion=:id")->execute(array(":id" => $ids["cliente"]));
    $db->prepare("DELETE FROM erp_distribucion_cliente_permisos WHERE id_cliente_distribucion=:id")->execute(array(":id" => $ids["cliente"]));
    $db->prepare("DELETE FROM erp_distribucion_cliente_listas WHERE id_cliente_distribucion=:id")->execute(array(":id" => $ids["cliente"]));
    $db->prepare("DELETE FROM erp_distribucion_auditoria WHERE id_cliente_distribucion=:id")->execute(array(":id" => $ids["cliente"]));
    $db->prepare("DELETE FROM erp_distribucion_clientes WHERE id_cliente_distribucion=:id")->execute(array(":id" => $ids["cliente"]));
    $resultado["cleanup"]["cliente"] = true;
  }
  if (!empty($ids["solicitud"])) {
    $db->prepare("DELETE FROM erp_distribucion_solicitudes WHERE id_solicitud_distribucion=:id")->execute(array(":id" => $ids["solicitud"]));
    $resultado["cleanup"]["solicitud"] = true;
  }
  if (!empty($ids["canal_vinculo"])) {
    $db->prepare("DELETE FROM erp_catalogo_canales_vinculos WHERE id_canal_vinculo=:id AND canal='distribucion' AND id_externo LIKE 'uat-dist-%'")->execute(array(":id" => $ids["canal_vinculo"]));
    $resultado["cleanup"]["canal_vinculo"] = true;
  }
}

$resultado["ids_temporales"] = $ids;
echo json_encode($resultado, JSON_PRETTY_PRINT);
