<?php
$_SERVER["SERVER_NAME"] = "panel.com.local";
require __DIR__ . "/../../app/iniciador.php";
require __DIR__ . "/../../app/modelos/ComprasSugeridosCompraErp.php";
$modelo = new ComprasSugeridosCompraErp();
$respuesta = $modelo->listar(array());
echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);