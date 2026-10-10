<?php
$distSeccionActiva = 'cliente_categorias';
$distTituloActivo = isset($datos['titulo_cliente_distribucion']) ? $datos['titulo_cliente_distribucion'] : 'Preferencias del cliente';
$distClienteId = isset($datos['id_cliente_distribucion']) ? intval($datos['id_cliente_distribucion']) : 0;
$distContenidoVista = __DIR__ . '/_cliente_categorias_contenido.php';
include __DIR__ . '/_layout.php';
