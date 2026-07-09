<?php

class Carritos extends CRUD {

    private $id_carrito;
    private $codigo;
    private $identificador;
    private $cantidad;
    private $importe;
    private $portada;
    private $precio;
    private $tipo;
    private $nombre;
    private $id_carrito_item;
    private $tabla_ecom_carrito = "ecom_carrito";
    private $tabla_ecom_carrito_items = "ecom_carrito_items";

    public function consultar_registro() {
        $columnas = array(
            "id_carrito_item",
            "codigo",
            "identificador",
            "cantidad"
        );
        $this->setWhere("codigo = '" . $this->getCodigo() . "'");
        $this->setAnd("identificador = '" . $this->getIdentificador() . "'");
        $this->setTabla($this->tabla_ecom_carrito_items);
        $this->setColumnas($columnas);
        return $this->buscarRegistro();
    }

    public function eliminar_item_carrito() {
        $this->setTabla($this->tabla_ecom_carrito_items);
        $this->setWhere("id_carrito_item = " . $this->getId_carrito_item());
        $respuesta = $this->eliminar();
    }

    public function actualizar_item_carrito() {
        $columnas = array(
            "cantidad",
            "importe"
        );
        $valores = array(
            $this->getCantidad(),
            $this->getImporte()
        );
        $this->setWhere("codigo = '" . $this->getCodigo() . "'");
        $this->setAnd("identificador = '" . $this->getIdentificador() . "'");
        $this->setTabla($this->tabla_ecom_carrito_items);
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->update();
    }

    public function consultar_items_carrito() {
        $columnas = array(
            "id_carrito_item",
            "codigo",
            "identificador",
            "cantidad",
            "importe",
            "portada",
            "nombre",
            "precio",
            "tipo"
        );
        $this->setWhere("codigo = '" . $this->getCodigo() . "'");
//    $this->setAnd("identificador = '" . $this->getIdentificador() . "'");
        $this->setTabla($this->tabla_ecom_carrito_items);
        $this->setColumnas($columnas);
        return $this->listar();
    }

    public function registrar_carrito_item() {
        $columnas = array(
            "codigo",
            "identificador",
            "cantidad",
            "importe",
            "portada",
            "nombre",
            "precio",
            "tipo",
            "fch_r"
        );
        $valores = array(
            $this->getCodigo(),
            $this->getIdentificador(),
            $this->getCantidad(),
            $this->getImporte(),
            $this->getPortada(),
            $this->getNombre(),
            $this->getPrecio(),
            $this->getTipo(),
            DATE_NOW
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_carrito_items);
        return $this->insertar();
    }

    public function registrar_carrito() {
        $columnas = array(
            "codigo",
            "fch_r"
        );
        $valores = array(
            $this->getCodigo(),
            DATE_NOW
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_carrito);
        return $this->insertar();
    }

    public function getTipo() {
        return $this->tipo;
    }

    public function setTipo($tipo): void {
        $this->tipo = $tipo;
    }

    public function getId_carrito_item() {
        return $this->id_carrito_item;
    }

    public function setId_carrito_item($id_carrito_item): void {
        $this->id_carrito_item = $id_carrito_item;
    }

    public function getPortada() {
        return $this->portada;
    }

    public function getPrecio() {
        return $this->precio;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setPortada($portada): void {
        $this->portada = $portada;
    }

    public function setPrecio($precio): void {
        $this->precio = $precio;
    }

    public function setNombre($nombre): void {
        $this->nombre = $nombre;
    }

    public function getImporte() {
        return $this->importe;
    }

    public function setImporte($importe): void {
        $this->importe = $importe;
    }

    public function getId_carrito() {
        return $this->id_carrito;
    }

    public function getCodigo() {
        return $this->codigo;
    }

    public function getIdentificador() {
        return $this->identificador;
    }

    public function getCantidad() {
        return $this->cantidad;
    }

    public function setId_carrito($id_carrito): void {
        $this->id_carrito = $id_carrito;
    }

    public function setCodigo($codigo): void {
        $this->codigo = $codigo;
    }

    public function setIdentificador($identificador): void {
        $this->identificador = $identificador;
    }

    public function setCantidad($cantidad): void {
        $this->cantidad = $cantidad;
    }
}
