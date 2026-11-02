<?php
/** IA: Codex GPT-6.1-sol | 2026-11-01. Proposito: probar Blog real sin demo/fixtures ni red/BD.
 * Impacto: adaptador del frontend; respuestas en memoria y URLs Media de contenido/relaciones.
 */
if (PHP_SAPI !== 'cli') exit;
$root = 'C:/xampp/htdocs/frontend/ecommerce-publico';
require $root . '/app/Config/env.php'; require $root . '/app/helpers.php'; require $root . '/app/Api/ErpEcommerceApi.php';
putenv('APP_ENV=production'); putenv('ERP_API_BASE_URL=http://api.test'); putenv('API_BASE_URL='); putenv('API_ASSET_BASE_URL=http://media.test'); putenv('APP_USE_FIXTURES=false'); putenv('ERP_API_CACHE_SECONDS=0');
$_SERVER['HTTP_HOST'] = 'artiani.com.mx'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$api = new ErpEcommerceApi();
$post = array('id' => 1, 'titulo' => 'Piton bola', 'slug' => 'piton-bola-preparar-habitat', 'imagen_portada' => array('url' => '/assets/media/cms/ecommerce/portada.webp'), 'contenido_html' => '<p>Texto <a href="/categoria/reptiles">Categoria</a></p><img src="/assets/media/cms/ecommerce/apoyo.webp" alt="Piton">');
$detail = array('error' => false, 'depurar' => array('publicacion' => $post, 'imagenes' => array(array('url' => '/assets/media/cms/ecommerce/apoyo.webp')), 'bloques_interactivos' => array(array('tipo' => 'imagen_productos','imagen' => array('url' => '/assets/media/cms/ecommerce/escena.webp'), 'puntos' => array(array('x' => 30,'y' => 40))))));
$memo = new ReflectionProperty($api, 'requestMemo'); $memo->setAccessible(true);
$memo->setValue($api, array('http://api.test/ecommercePublico/blog?limite=12' => array('error' => false,'depurar' => array('items' => array($post))), 'http://api.test/ecommercePublico/blog/piton-bola-preparar-habitat' => $detail));
$list = $api->getBlog(array('limite' => '12')); $d = $api->getBlogPost($post['slug']); $p = $d['depurar']['item'];
$checks = array(
  'listado_real_sin_demo' => ($list['depurar']['items'][0]['id'] ?? 0) === 1,
  'detalle_real_sin_demo' => ($p['id'] ?? 0) === 1,
  'portada_host_media' => $p['imagen_portada']['url'] === 'http://media.test/assets/media/cms/ecommerce/portada.webp',
  'html_imagen_host_media' => strpos($p['contenido_html'], 'src="http://media.test/assets/media/cms/ecommerce/apoyo.webp"') !== false,
  'categoria_permanece_frontend' => strpos($p['contenido_html'], 'href="/categoria/reptiles"') !== false,
  'apoyos_host_media' => $p['imagenes'][0]['url'] === 'http://media.test/assets/media/cms/ecommerce/apoyo.webp',
  'bloque_host_media' => $p['bloques_interactivos'][0]['imagen']['url'] === 'http://media.test/assets/media/cms/ecommerce/escena.webp'
);
echo json_encode(array('ok' => !in_array(false,$checks,true), 'checks' => $checks, 'red' => false, 'bd' => false), JSON_PRETTY_PRINT); exit(in_array(false,$checks,true) ? 1 : 0);
