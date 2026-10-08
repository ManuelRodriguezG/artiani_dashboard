<?php
$distSeccionActiva = 'cliente_entrega';
$distTituloActivo = isset($datos['titulo_cliente_distribucion']) ? $datos['titulo_cliente_distribucion'] : 'Entrega del cliente';
$distClienteId = isset($datos['id_cliente_distribucion']) ? intval($datos['id_cliente_distribucion']) : 0;
$distContenidoVista = __DIR__ . '/_cliente_entrega_contenido.php';
include __DIR__ . '/_layout.php';
