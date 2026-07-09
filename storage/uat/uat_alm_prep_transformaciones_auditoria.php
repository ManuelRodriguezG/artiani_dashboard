<?php

chdir(__DIR__ . '/../../public');
require '../app/iniciador.php';
require '../app/modelos/AlmacenEsquema.php';

$esquema = new AlmacenEsquema();
$resultado = $esquema->auditarAlmacenInventario();

echo json_encode(array(
    'error' => $resultado['error'],
    'tipo' => $resultado['tipo'],
    'mensaje' => $resultado['mensaje'],
    'tiene_pendientes' => isset($resultado['depurar']['tiene_pendientes']) ? $resultado['depurar']['tiene_pendientes'] : null,
    'pendientes' => isset($resultado['depurar']['pendientes']) ? $resultado['depurar']['pendientes'] : null
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
