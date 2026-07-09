<?php

class Sucursales extends CRUD {

    private $archivo_portada;
    private $url_origen;
    private $identificador;
    private $nombre;
    private $pais;
    private $ciudad;
    private $colonia;
    private $codigo_postal;
    private $calle;
    private $numero_exterior;
    private $numero_interior;
    private $url_imagen;
    private $incrustado;
    private $tabla_erp_sucursales = "erp_sucursales";
    private $recursos_productos = "media/apps/erp/sucursales/";

    public function obtener_sucursales() {
        $campos = array(
            "id_sucursal",
            "sucursal",
            "pais",
            "ciudad",
            "colonia",
            "codigo_postal",
            "calle",
            "numero_exterior",
            "numero_interior",
            "'sucursal' as tipo_establecimiento"
        );
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_erp_sucursales);
        return $this->listar();
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
        $this->setTabla("erp_sucursales");
        $columnas = array(
            "identificador",
            "sucursal",
            "pais",
            "ciudad",
            "colonia",
            "codigo_postal",
            "calle",
            "numero_exterior",
            "numero_interior",
            "url_imagen",
            "incrustado",
            "fch_r"
        );
        $valores = array(
            $this->getIdentificador(),
            $this->getNombre(),
            $this->getPais(),
            $this->getCiudad(),
            $this->getColonia(),
            $this->getCodigo_postal(),
            $this->getCalle(),
            $this->getNumero_exterior(),
            $this->getNumero_interior(),
            $this->getUrl_imagen(),
            $this->getIncrustado(),
            DATE_NOW
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function getArchivo_portada() {
        return $this->archivo_portada;
    }

    public function getUrl_origen() {
        return $this->url_origen;
    }

    public function getIdentificador() {
        return $this->identificador;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function getPais() {
        return $this->pais;
    }

    public function getCiudad() {
        return $this->ciudad;
    }

    public function getColonia() {
        return $this->colonia;
    }

    public function getCodigo_postal() {
        return $this->codigo_postal;
    }

    public function getCalle() {
        return $this->calle;
    }

    public function getNumero_exterior() {
        return $this->numero_exterior;
    }

    public function getNumero_interior() {
        return $this->numero_interior;
    }

    public function setArchivo_portada($archivo_portada): void {
        $this->archivo_portada = $archivo_portada;
    }

    public function setUrl_origen($url_origen): void {
        $this->url_origen = $url_origen;
    }

    public function setIdentificador($identificador): void {
        $this->identificador = $identificador;
    }

    public function setNombre($nombre): void {
        $this->nombre = $nombre;
    }

    public function setPais($pais): void {
        $this->pais = $pais;
    }

    public function setCiudad($ciudad): void {
        $this->ciudad = $ciudad;
    }

    public function setColonia($colonia): void {
        $this->colonia = $colonia;
    }

    public function setCodigo_postal($codigo_postal): void {
        $this->codigo_postal = $codigo_postal;
    }

    public function setCalle($calle): void {
        $this->calle = $calle;
    }

    public function setNumero_exterior($numero_exterior): void {
        $this->numero_exterior = $numero_exterior;
    }

    public function setNumero_interior($numero_interior): void {
        $this->numero_interior = $numero_interior;
    }

    public function getUrl_imagen() {
        return $this->url_imagen;
    }

    public function setUrl_imagen($url_imagen): void {
        $this->url_imagen = $url_imagen;
    }
    public function getIncrustado() {
        return $this->incrustado;
    }

    public function setIncrustado($incrustado): void {
        $this->incrustado = $incrustado;
    }


}
