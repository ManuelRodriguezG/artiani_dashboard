<?php

$_SERVER['SERVER_NAME'] = 'dashboard.com.local';
require __DIR__ . '/../../app/config/configuracion.php';

echo json_encode(array(
    'app_timezone' => defined('APP_TIMEZONE') ? APP_TIMEZONE : null,
    'php_timezone' => date_default_timezone_get(),
    'php_now' => date('Y-m-d H:i:s'),
    'date_now' => defined('DATE_NOW') ? DATE_NOW : null,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
