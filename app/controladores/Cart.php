<?php 
	class Cart extends Controlador{
		function __construct(){
			//var_dump("conectado");
		}

		function index(){
			//var_dump("conectado");
			$this->vista("Cart/single/cart");
		}
	}
?>