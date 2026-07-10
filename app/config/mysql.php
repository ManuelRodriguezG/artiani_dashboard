<?php

if (empty($_SERVER["SERVER_NAME"])) {
    $_SERVER["SERVER_NAME"] = "panel.com.local";
}

if ($_SERVER["SERVER_NAME"] == "localhost") {
    /**
     * Base de datos local
     * * */
    $mysqlHost = "localhost";
    $mysqlBase = "artianilocal";
    $mysqlUser = "root";
    $mysqlPass = '';
} else if ($_SERVER["SERVER_NAME"] == "dashboard.com.local") {
    /**
     * Base de datos local
     * * */
    $mysqlHost = "localhost";
    $mysqlBase = "artianilocal";
    $mysqlUser = "root";
    $mysqlPass = '';
} else if ($_SERVER["SERVER_NAME"] == "panel.com.local") {
    /**
     * Base de datos local
     * * */
    $mysqlHost = "localhost";
    $mysqlBase = "artianilocal";
    $mysqlUser = "root";
    $mysqlPass = '';
} else if ($_SERVER["SERVER_NAME"] == "dashboard_mgebike.com.local") {
    /**
     * Base de datos local
     * * */
    $mysqlHost = "localhost";
    $mysqlBase = "db_mgebikes";
    $mysqlUser = "root";
    $mysqlPass = '';
} else if ($_SERVER["SERVER_NAME"] == "panel.mgebikes.com") {
    /**
     * Base de datos local
     * * */
    $mysqlHost = "201.131.127.234";
    $mysqlBase = "mgbikes_mgbikes";
    $mysqlUser = "mgbikes_mgbikes";
    $mysqlPass = '?!&IOVdsN3wp';
}else if ($_SERVER["SERVER_NAME"] == "sys.artiani.com.mx") {
    /**
     * Base de datos local
     * * */
    $mysqlHost = "201.131.127.234";
    $mysqlBase = "artianicom_sys";
    $mysqlUser = "artianicom_artianicom";
    $mysqlPass = 'N^emH;iTA9Po';
} else {
    /**
     * Base de datos de producción
     * * */
    $mysqlHost = "201.131.127.234";
    $mysqlBase = "artianicom_artiani";
    $mysqlUser = "artianicom_artianicom";
    $mysqlPass = 'N^emH;iTA9Po';
}
//var_dump($_SERVER["SERVER_NAME"]);
define("MYSQLHOST", $mysqlHost);
define("MYSQLBASE", $mysqlBase);
define("MYSQLUSER", $mysqlUser);
define("MYSQLPASS", $mysqlPass);
