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
        return;
    }

    foreach (glob(__DIR__ . '/core/*.php') as $archivoCore) {
        $nombreArchivo = pathinfo($archivoCore, PATHINFO_FILENAME);
        if (strtolower($nombreArchivo) === strtolower($nombreClase)) {
            require_once $archivoCore;
            return;
        }
    }
});
