<?php

class Cliente extends CRUD {

    private $nombres;
    private $apellido_materno;
    private $apellido_paterno;
    private $correo;
    private $contacto1;
    private $contacto2;
    private $id_cliente;
    private $tabla_erp_clientes = "crm_clientes";
    private $tabla_crm_clientes_imagenes_compras = "crm_clientes_imagenes_compras";
    private $tabla_crm_productos_cliente_imagen_compra = "crm_productos_cliente_imagen_compra";
    private $id_producto;
    private $archivo_portada;
    private $url_origen;
    private $id_imagen_compra;
    private $url_imagen_compra;
    private $titulo;
    private $identificador;
    private $recursos_productos = "media/apps/customers/ventas/";

    function consultar_cliente() {
        $campos = array(
            "id_cliente"
        );
        $valores = array(
            $this->getNombres(),
            $this->getApellido_materno(),
            $this->getApellido_paterno(),
            $this->getCorreo(),
            $this->getContacto1(),
            $this->getContacto2()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setWhere("contacto1 = '" . $this->getContacto1() . "'");
        $this->setTabla($this->tabla_erp_clientes);
        return $this->buscarRegistro();
    }

    function registrar_cliente() {
        $campos = array(
            "nombres",
            "apellido_materno",
            "apellido_paterno",
            "correo",
            "contacto1",
            "contacto2"
        );
        $valores = array(
            $this->getNombres(),
            $this->getApellido_materno(),
            $this->getApellido_paterno(),
            $this->getCorreo(),
            $this->getContacto1(),
            $this->getContacto2()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_erp_clientes);
        return $this->insertar();
    }

    public function guardar_imagenes() {
        $urlRecursos = $this->recursos_productos;
        $urlDestino = $urlRecursos . $this->getArchivo_portada();
        $archivo_guardado = false;
//    if (!file_exists($urlDestino)) {
        //validar directorio
        $urlOrigen = $this->getUrl_origen();
        if (is_dir($urlRecursos)) {
            $archivo_guardado = move_uploaded_file($this->getUrl_origen(), $urlDestino);
        } else {
//      var_dump($urlRecursos);
            $archivo_guardado = mkdir($urlRecursos, 0777, true);
            $archivo_guardado = move_uploaded_file($this->getUrl_origen(), $urlDestino);
//      var_dump($this->getUrl_origen());
//      var_dump($urlDestino);
//      var_dump($archivo_guardado);
        }
//    }
//    } else {
//      //Archivo no existe
//      $return = error(true, 'danger', 'El archivo de origen no existe');
//    }
        if ($archivo_guardado == true) {
            $return = array('error' => false, 'tipo' => 'success', 'mensaje' => 'El archivo fue guardado con éxito', 'depurar' => $urlDestino);
        } else {
            $return = array('error' => true, 'tipo' => 'danger', 'mensaje' => 'El archivo no fue guardado');
        }
        return $return;
    }

    public function registrar_imagen_compra() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_crm_clientes_imagenes_compras);
        $columnas = array(
            "url_imagen_compra",
            "titulo",
            "identificador",
            "fch_r"
        );
        $valores = array(
            $this->getUrl_imagen_compra(),
            $this->getTitulo(),
            $this->getIdentificador(),
            DATE_NOW
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_producto_imagen_compra() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_crm_productos_cliente_imagen_compra);
        $columnas = array(
            "id_imagen_compra",
            "id_producto",
            "fch_r"
        );
        $valores = array(
            $this->getId_imagen_compra(),
            $this->getId_producto(),
            DATE_NOW
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    function actualizar_cliente() {
        $campos = array(
            "nombres",
            "apellido_materno",
            "apellido_paterno",
            "correo",
            "contacto1",
            "contacto2"
        );
        $valores = array(
            $this->getNombres(),
            $this->getApellido_materno(),
            $this->getApellido_paterno(),
            $this->getCorreo(),
            $this->getContacto1(),
            $this->getContacto2()
        );
        $this->setWhere("id_cliente = " . $this->getId_cliente());
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_erp_clientes);
        return $this->update();
    }

    public function getId_cliente() {
        return $this->id_cliente;
    }

    public function setId_cliente($id_cliente): void {
        $this->id_cliente = $id_cliente;
    }

    public function getNombres() {
        return $this->nombres;
    }

    public function getApellido_materno() {
        return $this->apellido_materno;
    }

    public function getApellido_paterno() {
        return $this->apellido_paterno;
    }

    public function getCorreo() {
        return $this->correo;
    }

    public function getContacto1() {
        return $this->contacto1;
    }

    public function getContacto2() {
        return $this->contacto2;
    }

    public function setNombres($nombres): void {
        $this->nombres = $nombres;
    }

    public function setApellido_materno($apellido_materno): void {
        $this->apellido_materno = $apellido_materno;
    }

    public function setApellido_paterno($apellido_paterno): void {
        $this->apellido_paterno = $apellido_paterno;
    }

    public function setCorreo($correo): void {
        $this->correo = $correo;
    }

    public function setContacto1($contacto1): void {
        $this->contacto1 = $contacto1;
    }

    public function setContacto2($contacto2): void {
        $this->contacto2 = $contacto2;
    }

    public function getId_producto() {
        return $this->id_producto;
    }

    public function getArchivo_portada() {
        return $this->archivo_portada;
    }

    public function getUrl_origen() {
        return $this->url_origen;
    }

    public function getRecursos_productos() {
        return $this->recursos_productos;
    }

    public function setId_producto($id_producto): void {
        $this->id_producto = $id_producto;
    }

    public function setArchivo_portada($archivo_portada): void {
        $this->archivo_portada = $archivo_portada;
    }

    public function setUrl_origen($url_origen): void {
        $this->url_origen = $url_origen;
    }

    public function setRecursos_productos($recursos_productos): void {
        $this->recursos_productos = $recursos_productos;
    }

    public function getUrl_imagen_compra() {
        return $this->url_imagen_compra;
    }

    public function setUrl_imagen_compra($url_imagen_compra): void {
        $this->url_imagen_compra = $url_imagen_compra;
    }

    public function getTitulo() {
        return $this->titulo;
    }

    public function setTitulo($titulo): void {
        $this->titulo = $titulo;
    }

    public function getId_imagen_compra() {
        return $this->id_imagen_compra;
    }

    public function setId_imagen_compra($id_imagen_compra): void {
        $this->id_imagen_compra = $id_imagen_compra;
    }

    public function getIdentificador() {
        return $this->identificador;
    }

    public function setIdentificador($identificador): void {
        $this->identificador = $identificador;
    }
}
