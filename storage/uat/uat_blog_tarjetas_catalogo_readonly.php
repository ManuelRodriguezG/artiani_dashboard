<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-11-02
 * Proposito: comprobar igualdad real Blog/catalogo y orden editorial de productos relacionados.
 * Impacto: contrato de tarjetas para frontend; detecta perdida de imagen, precio, marca e identidad.
 * Contrato: CLI solo lectura, sin publicar, sin analytics ni escrituras. No imprime errores de conexion.
 */
if (PHP_SAPI !== 'cli') { exit; }
$_SERVER['SERVER_NAME'] = 'panel.com.local';
require_once __DIR__ . '/../../app/iniciador.php';
require_once __DIR__ . '/../../app/modelos/EcommerceBlogPublico.php';
require_once __DIR__ . '/../../app/modelos/EcommerceCatalogoPublico.php';
try {
  $blog = new EcommerceBlogPublico();
  $catalogo = new EcommerceCatalogoPublico();
  $detalle = $blog->adminConsultar(1);
  $productos = $detalle['depurar']['relaciones']['productos'] ?? array();
  $checks = array('cinco_productos_reales' => empty($detalle['error']) && count($productos) === 5,
    'conserva_borrador' => ($detalle['depurar']['publicacion']['estado'] ?? '') === 'borrador',
    'ids_invalidos_vacios' => $catalogo->tarjetasPublicasPorIds(array(0, -1, '1 OR 1=1', array(1), null, 1.5)) === array());
  $iguales = true; $completos = true; $sinDatosInternos = true; $faltantesCatalogo = array();
  foreach ($productos as $producto) {
    $normal = $catalogo->catalogoPublico(array('producto_id' => $producto['id_producto_erp'], 'agrupacion' => 'sku', 'vista' => 'card', 'limite' => 60));
    $coincidente = null;
    foreach ($normal['depurar']['items'] ?? array() as $item) {
      if ($item['id_publicacion'] === $producto['id_publicacion']) { $coincidente = $item; break; }
    }
    $iguales = $iguales && empty($normal['error']) && $producto === $coincidente;
    foreach (array('id_publicacion', 'id_producto_erp', 'id_sku', 'sku', 'nombre', 'slug_publico', 'url_publica', 'imagen', 'imagen_alt', 'precio', 'moneda', 'mostrar_precio', 'disponibilidad', 'marca', 'marca_obj', 'categoria_obj', 'grupo_producto', 'permite_cotizacion', 'permite_whatsapp') as $campo) {
      $completos = $completos && array_key_exists($campo, $producto);
    }
    $completos = $completos && !empty($producto['sku']);
    // Un dato ausente tambien en catalogo no se inventa desde Blog; se reporta como calidad de origen.
    foreach (array('imagen', 'marca') as $campo) {
      if (empty($producto[$campo])) { $faltantesCatalogo[] = array('id_publicacion' => $producto['id_publicacion'], 'campo' => $campo); }
    }
    foreach (array('existencia_disponible', 'cantidad_disponible', 'stock', 'costo', 'lista_codigo') as $campo) {
      $sinDatosInternos = $sinDatosInternos && !array_key_exists($campo, $producto);
    }
  }
  $checks['igualdad_exacta_con_catalogo_card'] = $iguales && count($productos) > 0;
  $checks['tarjetas_completas'] = $completos && count($productos) > 0;
  $checks['sin_stock_exacto_ni_costos'] = $sinDatosInternos;
  $ids = array_reverse(array_column($productos, 'id_publicacion'));
  $reordenados = $catalogo->tarjetasPublicasPorIds(array_merge($ids, $ids, array(2147483647)));
  $checks['orden_sin_duplicados_ni_inexistentes'] = array_column($reordenados, 'id_publicacion') === $ids;
  $publico = $blog->blogDetallePublico('piton-bola-preparar-habitat');
  $checks['no_publica_borrador'] = !empty($publico['error']) && empty($publico['depurar']['publicacion']);
  $ok = !in_array(false, $checks, true);
  echo json_encode(array('ok' => $ok, 'modo' => 'read-only', 'productos' => count($productos), 'checks' => $checks, 'faltantes_en_catalogo' => $faltantesCatalogo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit($ok ? 0 : 1);
} catch (Throwable $e) {
  echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo completar la comprobacion read-only; revisar conexion y contrato sin exponer secretos.')) . PHP_EOL;
  exit(1);
}
