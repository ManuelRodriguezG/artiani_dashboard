<?php 

	/**
	* Mapear la url ingresada en el navegador,
	1.- controlador
	2.- método (funcion)
	3.- parametros
	Ejemplo: /articulo/actualizar/4
	*/

	class Core{
		protected $controladorActual = 'Inicio';
		protected $metodoActual = 'index';
		protected $parametros = [];

		//contructor
		public function __construct(){
			
			$url = $this->getUrl();
			//echo "hola";
			//var_dump($this->getUrl());
            
			//evaluar si el archivo existe
			//ucwords convierte primera letra a mayuscula
			//buscar en controladores si el controlador existe
			
			if(count($url) != 0){
			    
				//var_dump(file_exists('../app/controladores/'.ucwords($url[0].".php")));
    			if(file_exists('../app/controladores/'.ucwords($url[0]).'.php')){
    				//si existe se setea como controlador por defecto
    				$this->controladorActual = ucwords($url[0]);
    				
    
    				//unset indice
    				unset($url[0]);
    			}else{
    			    
    			  
    			    
    			    
    			}
            }
            //var_dump($url);
			///requerir el controlador
			
			require_once '../app/controladores/'.$this->controladorActual.'.php';
			$nombreControlador = $this->controladorActual;
			$controladoresProtegidos = array(
				'Almacen', 'Archivos', 'Busqueda', 'CatalogoErp', 'Categoria', 'Clientes', 'Comercial', 'Crm', 'Compra', 'Compra_venta',
				'Costo', 'Dashboard', 'Empresa', 'Garantias', 'Inicio', 'Inventario', 'Link', 'Marca', 'Panel',
				'Paquetes', 'Producto', 'Proveedor', 'Rentabilidad', 'Sistema', 'Sucursal', 'Users', 'Usuario',
				'Utilidad', 'Ventas'
			);
			if (in_array($this->controladorActual, $controladoresProtegidos, true)) {
				SesionSeguridad::requerirSesion();
			}
			//var_dump(new $this->controladorActual);
			$this->controladorActual = new $this->controladorActual;
            //var_dump(count($url) != 0);
			//chequear la segunda parte de la url que seria el metodo
			//var_dump(method_exists($this->controladorActual, $url[1]));
			if(count($url) != 0){
    			if(isset($url[1])){
    				if(method_exists($this->controladorActual, $url[1])){
    					//checaamos el metodo
    					$this->metodoActual = $url[1];
    					//unset indice
    					unset($url[1]);
    				}
    			}
			}
			//para probar traer metodo
			//echo $this->metodoActual;

			//obtener los posibles parametros

			$this->parametros = count($url) != 0 ? ($url) : [];
            //var_dump($this->parametros);
			//llamar callback con parametros array

			$esPostAutenticado = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && SesionSeguridad::autenticado();
			$csrfExentos = array('Autenticacion.inicio_session', 'Autenticacion.reautenticar_session');
			$auditoriaExplicita = array(
				'Sistema.seguridad_usuario_rol_asignar',
				'Sistema.seguridad_usuario_rol_quitar',
				'Sistema.seguridad_rol_permisos_guardar',
				'Sistema.seguridad_usuario_estatus',
				'Sistema.seguridad_usuario_crear',
				'Sistema.seguridad_usuario_editar',
				'CatalogoErp.registrar',
				'CatalogoErp.actualizar',
				'CatalogoErp.agregar_sku',
				'CatalogoErp.actualizar_sku',
				'CatalogoErp.guardar_variantes',
				'CatalogoErp.guardar_imagen',
				'CatalogoErp.desactivar_imagen',
				'CatalogoErp.guardar_sku_proveedor',
				'CatalogoErp.incidencia_migracion_resolver',
				'CatalogoErp.incidencia_migracion_vincular_existente',
				'CatalogoErp.incidencia_migracion_descartar',
				'CatalogoErp.incidencia_nombre_resolver',
				'CatalogoErp.propuesta_nombre_resolver',
				'CatalogoErp.fusionar_productos',
				'CatalogoErp.propuestas_costos_aplicar',
				'CatalogoErp.relaciones_proveedor_sincronizar',
				'CatalogoErp.propuestas_reorden_aplicar',
				'CatalogoErp.metadatos_sincronizar',
				'CatalogoErp.metadatos_revision_aplicar',
				'CatalogoErp.taxonomia_ecommerce_sincronizar',
				'CatalogoErp.categorias_arbol_preparar',
				'CatalogoErp.categorias_relaciones_sincronizar',
				'CatalogoErp.auxiliar_guardar',
				'EcommercePublico.publicaciones_guardar_borrador_erp',
				'Almacen.guardar_recepcion',
				'Almacen.etiqueta_marcar_impresa_erp',
				'Almacen.etiquetas_marcar_impresas_erp',
				'Almacen.etiqueta_marcar_pegada_erp',
				'Almacen.etiquetas_marcar_pegadas_erp',
				'Compra.solicitud_guardar_erp',
				'Compra.solicitud_estatus_erp',
				'Compra.orden_guardar_erp',
				'Compra.orden_pago_registrar_erp',
				'Compra.orden_pago_cancelar_erp',
				'Compra.orden_nota_credito_registrar_erp',
				'Compra.orden_nota_credito_cancelar_erp',
				'Compra.orden_adjunto_subir_erp',
				'Compra.orden_adjunto_cancelar_erp',
				'Compra.orden_cancelar_erp',
				'Compra.orden_generar_desde_solicitud_erp',
				'Compra.orden_xml_importar_erp',
				'Compra.orden_xml_resolver_concepto_erp',
				'Proveedor.proveedor_generales_guardar_erp',
				'Proveedor.proveedor_fiscal_guardar_erp',
				'Proveedor.proveedor_contacto_guardar_erp',
				'Proveedor.proveedor_condicion_guardar_erp',
				'Proveedor.proveedor_documento_guardar_erp',
				'Proveedor.proveedor_lista_guardar_erp',
				'Proveedor.proveedor_lista_detalle_guardar_erp',
				'Proveedor.proveedor_lista_matching_decidir_erp',
				'Proveedor.proveedor_sku_relacion_aplicar_erp',
				'Proveedor.proveedor_costo_aplicar_erp',
				'Proveedor.proveedor_incidencia_crear_erp',
				'Garantias.esquema_actualizar_garantias_erp',
				'Garantias.venta_snapshot_dryrun_erp',
				'Garantias.politica_dryrun_erp',
				'Garantias.politica_regla_dryrun_erp',
				'Garantias.politica_guardar_erp',
				'Garantias.politica_regla_guardar_erp',
				'Garantias.reclamo_dryrun_erp',
				'Ventas.pos_excepcion_comercial_registrar_erp',
				'Ventas.pos_confirmar_erp',
				'Ventas.pos_configuracion_caja_guardar_erp',
				'Ventas.pos_configuracion_terminal_guardar_erp',
				'Ventas.pos_configuracion_asignacion_guardar_erp',
				'Ventas.pos_configuracion_desactivar_erp',
				'Ventas.reportes_diferencia_caja_resolver_erp',
				'Inventario.ajustar_erp',
				'Inventario.traspasar_erp',
				'Rentabilidad.snapshot_guardar_erp',
				'Rentabilidad.recomendaciones_guardar_erp',
				'Rentabilidad.recomendacion_resolver_erp'
			);
			$rutaAccion = $nombreControlador . '.' . $this->metodoActual;
			if ($esPostAutenticado && !in_array($rutaAccion, $csrfExentos, true)) {
				SesionSeguridad::requerirCsrf();
			}

			$resultado = call_user_func_array([$this->controladorActual,$this->metodoActual],$this->parametros);
			if ($esPostAutenticado && strpos($rutaAccion, 'Autenticacion.') !== 0 && !in_array($rutaAccion, $auditoriaExplicita, true)) {
				SesionSeguridad::registrarAuditoria(strtolower($nombreControlador), $this->metodoActual, array(
					'resultado' => http_response_code() >= 400 ? 'error' : 'ok',
					'mensaje' => 'Peticion POST ejecutada',
					'datos_despues' => array('campos_recibidos' => array_values(array_diff(array_keys($_POST), array('contrasenia', 'confirmar_contrasenia', '_csrf'))))
				));
			}
			echo $resultado;

		}

		public function getUrl(){

			//echo $_GET['url']; 
            $url = [];
			if(isset($_GET['url'])){
				$url = rtrim($_GET['url'],'/');
				$url = filter_var($url,FILTER_SANITIZE_URL);
				$url = explode('/',$url);
				
			}
			return $url;
			
		}
	}
