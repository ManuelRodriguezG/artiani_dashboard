<?php

class Usuarios extends CRUD {

    private $tabla_sys_usuarios_mayoreo = "sys_usuarios_mayoreo";
    private $tabla_sys_usuarios_mayoreo_informacion_negocio = "sys_usuarios_mayoreo_informacion_negocio";
    private $tabla_erp_usuarios_mayoreo_listas_mayoreo = "erp_usuarios_mayoreo_listas_mayoreo";
    private $tabla_erp_usuarios_mayoreo_envios = "erp_usuarios_mayoreo_envios";
    private $id_usuario;
    private $alias;
    private $nombres;
    private $apellido_materno;
    private $apellido_paterno;
    private $correo;
    private $telefono;
    private $celular;
    private $razon_social;
    private $rfc;
    private $regimen_fiscal;
    private $uso_cfdi;
    private $fiscal_codigo_postal;
    private $id_tipo_negocio;
    private $calle;
    private $numero_exterior;
    private $numero_interior;
    private $codigo_postal;
    private $colonia;
    private $telefono_fijo;
    private $negocio_celular;
    private $costo_envio;
    private $envio_calle;
    private $envio_numero_exterior;
    private $envio_numero_interior;
    private $envio_colonia;
    private $envio_codigo_postal;
    private $referencias;

    public function consultar_informacion_usuario_mayoreo() {
        $campos = array(
            "sysum.id_usuario",
            "sysum.alias",
            "sysum.nombres",
            "sysum.apellido_materno",
            "sysum.apellido_paterno",
            "sysum.correo",
            "sysum.telefono",
            "sysum.celular",
            "sysum.razon_social",
            "sysum.rfc",
            "sysum.regimen_fiscal",
            "sysum.uso_cfdi",
            "sysum.fiscal_codigo_postal",
            "sysumin.id_usuario_informacion_negocio",
            "sysumin.nombre_negocio",
            "sysumin.id_tipo_negocio",
            "sysumin.calle",
            "sysumin.numero_exterior",
            "sysumin.numero_interior",
            "sysumin.codigo_postal",
            "sysumin.colonia",
            "sysumin.telefono_fijo",
            "sysumin.negocio_celular"
        );
        $this->setColumnas($campos);
        
        $this->setTabla($this->tabla_sys_usuarios_mayoreo . " sysum");
        $this->setWhere("sysum.id_usuario = " . $this->getId_usuario($id_usuario));
        $this->setInnerJoin($this->tabla_sys_usuarios_mayoreo_informacion_negocio . " sysumin ON sysumin.id_usuario_negocio =  sysum.id_usuario");
        $this->setLeftJoin($this->tabla_erp_usuarios_mayoreo_envios . " erpume ON erpume.id_usuario_mayoreo = sysum.id_usuario");

        $respuesta = $this->buscarRegistro();
        return $respuesta;
    }

    public function getId_usuario() {
        return $this->id_usuario;
    }

    public function getAlias() {
        return $this->alias;
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

    public function getTelefono() {
        return $this->telefono;
    }

    public function getCelular() {
        return $this->celular;
    }

    public function getRazon_social() {
        return $this->razon_social;
    }

    public function getRfc() {
        return $this->rfc;
    }

    public function getRegimen_fiscal() {
        return $this->regimen_fiscal;
    }

    public function getUso_cfdi() {
        return $this->uso_cfdi;
    }

    public function getFiscal_codigo_postal() {
        return $this->fiscal_codigo_postal;
    }

    public function getId_tipo_negocio() {
        return $this->id_tipo_negocio;
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

    public function getCodigo_postal() {
        return $this->codigo_postal;
    }

    public function getColonia() {
        return $this->colonia;
    }

    public function getTelefono_fijo() {
        return $this->telefono_fijo;
    }

    public function getNegocio_celular() {
        return $this->negocio_celular;
    }

    public function getCosto_envio() {
        return $this->costo_envio;
    }

    public function getEnvio_calle() {
        return $this->envio_calle;
    }

    public function getEnvio_numero_exterior() {
        return $this->envio_numero_exterior;
    }

    public function getEnvio_numero_interior() {
        return $this->envio_numero_interior;
    }

    public function getEnvio_colonia() {
        return $this->envio_colonia;
    }

    public function getEnvio_codigo_postal() {
        return $this->envio_codigo_postal;
    }

    public function getReferencias() {
        return $this->referencias;
    }

    public function setId_usuario($id_usuario): void {
        $this->id_usuario = $id_usuario;
    }

    public function setAlias($alias): void {
        $this->alias = $alias;
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

    public function setTelefono($telefono): void {
        $this->telefono = $telefono;
    }

    public function setCelular($celular): void {
        $this->celular = $celular;
    }

    public function setRazon_social($razon_social): void {
        $this->razon_social = $razon_social;
    }

    public function setRfc($rfc): void {
        $this->rfc = $rfc;
    }

    public function setRegimen_fiscal($regimen_fiscal): void {
        $this->regimen_fiscal = $regimen_fiscal;
    }

    public function setUso_cfdi($uso_cfdi): void {
        $this->uso_cfdi = $uso_cfdi;
    }

    public function setFiscal_codigo_postal($fiscal_codigo_postal): void {
        $this->fiscal_codigo_postal = $fiscal_codigo_postal;
    }

    public function setId_tipo_negocio($id_tipo_negocio): void {
        $this->id_tipo_negocio = $id_tipo_negocio;
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

    public function setCodigo_postal($codigo_postal): void {
        $this->codigo_postal = $codigo_postal;
    }

    public function setColonia($colonia): void {
        $this->colonia = $colonia;
    }

    public function setTelefono_fijo($telefono_fijo): void {
        $this->telefono_fijo = $telefono_fijo;
    }

    public function setNegocio_celular($negocio_celular): void {
        $this->negocio_celular = $negocio_celular;
    }

    public function setCosto_envio($costo_envio): void {
        $this->costo_envio = $costo_envio;
    }

    public function setEnvio_calle($envio_calle): void {
        $this->envio_calle = $envio_calle;
    }

    public function setEnvio_numero_exterior($envio_numero_exterior): void {
        $this->envio_numero_exterior = $envio_numero_exterior;
    }

    public function setEnvio_numero_interior($envio_numero_interior): void {
        $this->envio_numero_interior = $envio_numero_interior;
    }

    public function setEnvio_colonia($envio_colonia): void {
        $this->envio_colonia = $envio_colonia;
    }

    public function setEnvio_codigo_postal($envio_codigo_postal): void {
        $this->envio_codigo_postal = $envio_codigo_postal;
    }

    public function setReferencias($referencias): void {
        $this->referencias = $referencias;
    }
}
