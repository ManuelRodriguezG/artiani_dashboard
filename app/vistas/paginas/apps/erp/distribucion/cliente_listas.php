<?php
$distSeccionActiva = 'cliente_listas';
$distTituloActivo = isset($datos['titulo_cliente_distribucion']) ? $datos['titulo_cliente_distribucion'] : 'Listas del cliente';
$distClienteId = isset($datos['id_cliente_distribucion']) ? intval($datos['id_cliente_distribucion']) : 0;
$distContenidoVista = __DIR__ . '/_cliente_listas_contenido.php';
include __DIR__ . '/_layout.php';
