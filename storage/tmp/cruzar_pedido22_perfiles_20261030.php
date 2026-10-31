<?php
require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/modelos/produccionpeceraserp.php";
$modelo = new ProduccionPecerasErp();
$respuesta = $modelo->listarPeceras(array());
foreach (($respuesta["depurar"]["perfiles"] ?? array()) as $perfil) {
    $piezas = array();
    foreach (($perfil["piezas"] ?? array()) as $pieza) {
        $piezas[] = ($pieza["nombre"] ?? "") . "=" . ($pieza["largo"] ?? "") . "x" . ($pieza["ancho"] ?? "") . "x" . ($pieza["espesor"] ?? "");
    }
    echo ($perfil["codigo"] ?? "") . " | " . implode("; ", $piezas) . PHP_EOL;
}
