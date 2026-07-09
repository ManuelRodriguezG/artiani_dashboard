<?php
	//var_dump("hoooola");
	//cargamos librerias
	error_reporting(true);
	require_once 'config/configuracion.php';
        require_once 'config/mysql.php';
        

    
	spl_autoload_register(function($nombreClase){
	    if(file_exists('../app/core/'.$nombreClase.'.php')){
	        require_once '../app/core/'.$nombreClase.'.php';    
	    }
		
	});
	?>