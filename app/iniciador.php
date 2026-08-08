<?php
//var_dump("hoooola");
//cargamos librerias
error_reporting(true);
require_once __DIR__ . '/config/configuracion.php';
require_once __DIR__ . '/config/mysql.php';

spl_autoload_register(function($nombreClase) {
    $archivo = __DIR__ . '/core/' . $nombreClase . '.php';
    if (file_exists($archivo)) {
        require_once $archivo;
    }
});
