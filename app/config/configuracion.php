<?php

error_reporting(1);
date_default_timezone_set('America/Mexico_City');
define('APP_TIMEZONE', 'America/Mexico_City');
//Ruta de la aplicacion
define('RUTA_APP', dirname(dirname(__FILE__)));
define('SESSION_TIMEOUT_SECONDS', 1800);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT_SECONDS);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();
$server_name_local = "panel.com.local";
//Ruta url Ejemplo: http://localhost/ExamplePanoramex/
if ($_SERVER["SERVER_NAME"] == $server_name_local) {
    define('RUTA_URL', 'http://panel.com.local/');
    define('RUTA_URL_FRONT', 'http://panel.com.local/');
    define('RUTA_RECURSOS', 'http://panel.com.local/');
    define('RUTA_RECURSOS_IMG', 'http://panel.com.local/');
} else if ($_SERVER["SERVER_NAME"] == "dashboard_mgebike.com.local") {
    define('RUTA_URL', 'http://dashboard_mgebike.com.local/');
    define('RUTA_URL_FRONT', 'http://dashboard_mgebike.com.local/');
    define('RUTA_RECURSOS', 'http://dashboard_mgebike.com.local/');
    define('RUTA_RECURSOS_IMG', 'http://dashboard_mgebike.com.local/');
} else if ($_SERVER["SERVER_NAME"] == "panel.artiani.com.mx") {
    define('RUTA_URL', 'https://panel.artiani.com.mx/');
    define('RUTA_RECURSOS', 'https://panel.artiani.com.mx/');
    define('RUTA_URL_FRONT', 'https://artiani.com.mx/');
    define('RUTA_RECURSOS_IMG', 'https://panel.artiani.com.mx/');
} else if ($_SERVER["SERVER_NAME"] == "panel.mgebikes.com") {
    define('RUTA_URL', 'https://panel.mgebikes.com/');
    define('RUTA_RECURSOS', 'https://panel.mgebikes.com/');
    define('RUTA_URL_FRONT', 'https://mgebikes.com.mx/');
    define('RUTA_RECURSOS_IMG', 'https://panel.mgebikes.com/');
} else if ($_SERVER["SERVER_NAME"] == "sys.artiani.com.mx") {
    define('RUTA_URL', 'https://sys.artiani.com.mx/');
    define('RUTA_RECURSOS', 'https://sys.artiani.com.mx/');
    define('RUTA_URL_FRONT', 'https://artiani.com.mx/');
    define('RUTA_RECURSOS_IMG', 'https://sys.artiani.com.mx/');
}


//define('NOMBRE_SITIO', 'Black & White Bettas');
//Fecha
$date = new DateTime("now", new DateTimeZone('America/Mexico_City'));
$date = $date->format("Y-m-d H:i:s");
define('DATE_NOW', $date);

//Constantes de nivel de rango
define('TERCERO', 1);
define('USER', 2);
define('JEFE_DEPARTAMENTO', 3);
define('CONTABILIDAD', 4);
define('ADMINISTRATIVO', 9);
define('SOCIO', 10);
define('PROGRAMADOR', 11);

//define('LANGUAGES',array('ES','EN'));
	
	
