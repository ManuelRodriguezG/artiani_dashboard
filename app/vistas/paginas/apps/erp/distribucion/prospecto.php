<?php
$distSeccionActiva = 'prospectos';
$distTituloActivo = isset($datos['titulo_prospecto_distribucion']) ? $datos['titulo_prospecto_distribucion'] : 'Expediente del prospecto';
$distClienteId = isset($datos['id_prospecto_distribucion']) ? intval($datos['id_prospecto_distribucion']) : 0;
$distContenidoVista = __DIR__ . '/_prospecto_contenido.php';
include __DIR__ . '/_layout.php';
