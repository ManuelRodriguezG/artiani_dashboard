<?php
$distSeccionActiva = 'cliente';
$distTituloActivo = isset($datos['titulo_cliente_distribucion']) ? $datos['titulo_cliente_distribucion'] : 'Atender cliente';
$distClienteId = isset($datos['id_cliente_distribucion']) ? intval($datos['id_cliente_distribucion']) : 0;
$distContenidoVista = __DIR__ . '/_cliente_contenido.php';
include __DIR__ . '/_layout.php';
