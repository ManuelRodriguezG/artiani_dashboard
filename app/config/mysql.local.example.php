<?php

return array(
    /*
     * Copiar este archivo como app/config/mysql.local.php solo cuando se quiera
     * que el entorno local use una base distinta a la definida por SERVER_NAME.
     *
     * Este archivo no debe versionarse. Mantener habilitado=false hasta la
     * ventana autorizada de conexion local contra productivo.
     */
    "habilitado" => false,
    "host" => "127.0.0.1",
    "base" => "base_destino",
    "port" => "3306",
    "usuario" => "usuario_destino",
    "password" => "CAMBIAR_EN_ARCHIVO_LOCAL_NO_VERSIONADO"
);
