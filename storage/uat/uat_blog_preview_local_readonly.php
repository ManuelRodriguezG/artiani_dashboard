<?php
/** IA: Codex GPT-6.1-sol | 2026-11-01. Proposito: verificar acceso local privado y ausencia de publicacion.
 * Contrato: GET unicamente; no muestra la clave ni modifica BD/caches/configuracion.
 */
if (PHP_SAPI !== 'cli') exit;
$config = json_decode(file_get_contents(__DIR__ . '/../cms_blog_preview_local/access.json'), true);
/** IA: Codex GPT-6.1-sol | 2026-11-01. Transporte de prueba sin redirecciones y sin registro de headers secretos. */
function consultaPreview($path, $key = '', $host = 'panel.com.local') {
  $h = curl_init('http://127.0.0.1/ecommercePublico/' . $path);
  $headers = array('Host: ' . $host); if ($key !== '') $headers[] = 'X-CMS-Blog-Preview-Key: ' . $key;
  curl_setopt_array($h,array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,CURLOPT_TIMEOUT => 15,CURLOPT_HTTPHEADER => $headers));
  $body=curl_exec($h);$code=curl_getinfo($h,CURLINFO_HTTP_CODE);curl_close($h);return array('status'=>$code,'data'=>json_decode((string)$body,true));
}
$url='blog_preview_local/piton-bola-preparar-habitat';
$missing=consultaPreview($url);$wrong=consultaPreview($url,'incorrecta');$valid=consultaPreview($url,$config['key']);$other=consultaPreview('blog_preview_local/otro-articulo',$config['key']);$host=consultaPreview($url,$config['key'],'localhost');$public=consultaPreview('blog/piton-bola-preparar-habitat');
$checks=array('sin_clave_404'=>$missing['status']===404,'clave_invalida_404'=>$wrong['status']===404,'valido_id1'=>$valid['status']===200&&($valid['data']['depurar']['publicacion']['id']??0)===1,'otro_slug_404'=>$other['status']===404,'otro_host_no_accede'=>$host['status']!==200||empty($host['data']['depurar']['publicacion']),'sigue_borrador'=>($valid['data']['depurar']['publicacion']['estado']??'')==='borrador','publico_no_entrega_borrador'=>!empty($public['data']['error']));
echo json_encode(array('ok'=>!in_array(false,$checks,true),'checks'=>$checks),JSON_PRETTY_PRINT);exit(in_array(false,$checks,true)?1:0);
