<?php

class Venta extends CRUD {

    //ecom_pedidos
    private $id_pedido;
    private $descuento;
    private $envio;
    private $iva;
    private $subtotal;
    private $total;
    private $ip;
    private $navegador;
    private $pedido_estatus;
    private $id_cliente;
    private $tabla_ecom_pedidos = "ecom_pedidos";
    private $tabla_crm_clientes = "crm_clientes";
    //ecom_pedidos_datos_envio
    private $id_datos_envio;
    private $hora_inicial;
    private $hora_final;
    private $envio_estado;
    private $envio_ciudad;
    private $envio_colonia;
    private $envio_calle;
    private $envio_numero_exterior;
    private $envio_numero_interior;
    private $envio_referencias;
    private $envio_codigo_postal;
    private $tabla_ecom_pedidos_datos_envio = "ecom_pedidos_datos_envio";
    //ecom_pedidos_datos_facturacion
    private $id_datos_facturacion;
    private $facturacion_estado;
    private $facturacion_ciudad;
    private $facturacion_colonia;
    private $facturacion_calle;
    private $facturacion_numero_exterior;
    private $facturacion_numero_interior;
    private $facturacion_referencias;
    private $facturacion_codigo_postal;
    private $razon_social;
    private $rfc;
    private $regimen_fiscal;
    private $uso_cfdi;
    private $tabla_ecom_pedidos_datos_facturacion = "ecom_pedidos_datos_facturacion";
    //ecom_pedidos_pagos
    private $id_metodo_pago;
    private $metodo_pago_estatus;
    private $cantidad_pago;
    private $tabla_ecom_pedidos_pagos = "ecom_pedidos_pagos";
    //ecom_pedidos_productos
    private $id_producto;
    private $id_estatus_pedido;
    private $item_tipo;
    private $fecha_entrega;
    private $codigo_barras;
    private $codigo_interno;
    private $codigo_proveedor;
    private $producto;
    private $cantidad;
    private $precio;
    private $producto_descuento;
    private $producto_iva;
    private $producto_subtotal;
    private $tabla_ecom_pedidos_productos = "ecom_pedidos_productos";
    private $tabla_ecom_productos = "ecom_productos";
    private $tabla_ecom_productos_imagenes = "ecom_productos_imagenes";
    private $tabla_erp_estatus_pedidos = "erp_estatus_pedidos";
    private $tabla_ecom_pedidos_estatus = "ecom_pedidos_estatus";
    private $array_pedido_estatus = array(
        "pedido_nuevo" => 0,
        "pedido_pagado" => 1,
        "proceso_de_entrega" => 2,
        "pedido_en_ruta" => 3,
        "pedido_entregado" => 4,
        "pedido_cancelado" => 5,
    );

    public function registrar_pedido() {
        $campos = array(
            "id_datos_envio",
            "id_datos_facturacion",
            "descuento",
            "envio",
            "iva",
            "subtotal",
            "total",
            "ip",
            "navegador",
            "estatus",
            "id_cliente"
        );
        $valores = array(
            $this->getId_datos_envio(),
            $this->getId_datos_facturacion(),
            $this->getDescuento(),
            $this->getEnvio(),
            $this->getIva(),
            $this->getSubtotal(),
            $this->getTotal(),
            $this->getIp(),
            $this->getNavegador(),
            $this->getEstatus(),
            $this->getId_cliente()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_pedidos);
        return $this->insertar();
    }

    public function actualizar_pedido() {
        $campos = array(
            "id_datos_envio",
            "id_datos_facturacion",
            "descuento",
            "envio",
            "iva",
            "subtotal",
            "total",
            "ip",
            "navegador",
            "estatus",
            "id_cliente"
        );
        $valores = array(
            $this->getId_datos_envio(),
            $this->getId_datos_facturacion(),
            $this->getDescuento(),
            $this->getEnvio(),
            $this->getIva(),
            $this->getSubtotal(),
            $this->getTotal(),
            $this->getIp(),
            $this->getNavegador(),
            $this->array_pedido_estatus[$this->getEstatus()],
            $this->getId_cliente()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setWhere('id_pedido = ' . $this->getId_pedido());
        $this->setTabla($this->tabla_ecom_pedidos);
        return $this->update();
    }

    public function consultar_cliente() {
        $campos = array(
            "id_cliente",
            "nombres",
            "apellido_paterno",
            "apellido_materno",
            "correo",
            "contacto1",
            "contacto2",
            "estatus"
        );
        $this->setColumnas($campos);
        $this->setWhere("id_cliente = " . $this->getId_cliente());
        $this->setTabla($this->tabla_crm_clientes);
        return $this->buscarRegistro();
    }

    public function consultar_pedido() {
        $campos = array(
            "id_pedido",
            "id_datos_envio",
            "id_cliente",
            "id_datos_facturacion",
            "medio_comunicacion",
            "descuento",
            "envio",
            "iva",
            "subtotal",
            "total"
        );
        $this->setColumnas($campos);
        $this->setWhere("id_pedido = " . $this->getId_pedido());
        $this->setTabla($this->tabla_ecom_pedidos);
        return $this->buscarRegistro();
    }

    public function consultar_datos_facturacion() {
        $campos = array(
            "id_datos_facturacion",
            "razon_social",
            "rfc",
            "regimen_fiscal",
            "uso_cfdi",
            "estado",
            "ciudad",
            "colonia",
            "calle",
            "numero_exterior",
            "numero_interior",
            "codigo_postal"
        );
        $this->setColumnas($campos);
        $this->setWhere("id_datos_configuracion = " . $this->getId_datos_facturacion());
        $this->setTabla($this->tabla_ecom_pedidos_datos_facturacion);
        return $this->buscarRegistro();
    }

    public function registrar_datos_facturacion() {
        $campos = array(
            "razon_social",
            "rfc",
            "regimen_fiscal",
            "uso_cfdi",
            "estado",
            "ciudad",
            "colonia",
            "calle",
            "numero_exterior",
            "numero_interior",
            "codigo_postal"
        );
        $valores = array(
            $this->getRazon_social(),
            $this->getRfc(),
            $this->getRegimen_fiscal(),
            $this->getUso_cfdi(),
            $this->getFacturacion_estado(),
            $this->getFacturacion_ciudad(),
            $this->getFacturacion_colonia(),
            $this->getFacturacion_calle(),
            $this->getFacturacion_numero_exterior(),
            $this->getFacturacion_numero_interior(),
            $this->getFacturacion_codigo_postal()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_pedidos_datos_facturacion);
        return $this->insertar();
    }

    public function actualizar_datos_facturacion() {
        $campos = array(
            "razon_social",
            "rfc",
            "regimen_fiscal",
            "uso_cfdi",
            "estado",
            "ciudad",
            "colonia",
            "calle",
            "numero_exterior",
            "numero_interior",
            "codigo_postal"
        );
        $valores = array(
            $this->getRazon_social(),
            $this->getRfc(),
            $this->getRegimen_fiscal(),
            $this->getUso_cfdi(),
            $this->getFacturacion_estado(),
            $this->getFacturacion_ciudad(),
            $this->getFacturacion_colonia(),
            $this->getFacturacion_calle(),
            $this->getFacturacion_numero_exterior(),
            $this->getFacturacion_numero_interior(),
            $this->getFacturacion_codigo_postal()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setWhere("id_datos_facturacion = " . $this->getId_datos_facturacion());
        $this->setTabla($this->tabla_ecom_pedidos_datos_facturacion);
        return $this->insertar();
    }

    public function consultar_datos_envio() {
        $campos = array(
            "id_datos_envio",
            "fecha_entrega",
            "id_tipo_entrega",
            "estado",
            "ciudad",
            "colonia",
            "calle",
            "numero_exterior",
            "numero_interior",
            "referencias",
            "codigo_postal"
        );
        $this->setColumnas($campos);
        $this->setWhere("id_datos_envio = " . $this->getId_datos_envio());
        $this->setTabla($this->tabla_ecom_pedidos_datos_envio);
        return $this->buscarRegistro();
    }

    public function registrar_datos_envio() {
        $campos = array(
            "fecha_entrega",
            "hora_inicial",
            "hora_final",
            "estado",
            "ciudad",
            "colonia",
            "calle",
            "numero_exterior",
            "numero_interior",
            "referencias",
            "codigo_postal"
        );
        $valores = array(
            $this->getFecha_entrega(),
            $this->getHora_inicial(),
            $this->getHora_final(),
            $this->getEnvio_estado(),
            $this->getEnvio_ciudad(),
            $this->getEnvio_colonia(),
            $this->getEnvio_calle(),
            $this->getEnvio_numero_exterior(),
            $this->getEnvio_numero_interior(),
            $this->getEnvio_referencias(),
            $this->getEnvio_codigo_postal()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_pedidos_datos_envio);
        return $this->insertar();
    }

    public function actualizar_datos_envio() {
        $campos = array(
            "fecha_entrega",
            "estado",
            "ciudad",
            "colonia",
            "calle",
            "numero_exterior",
            "numero_interior",
            "referencias",
            "codigo_postal"
        );
        $valores = array(
            $this->getFecha_entrega(),
            $this->getEnvio_estado(),
            $this->getEnvio_ciudad(),
            $this->getEnvio_colonia(),
            $this->getEnvio_calle(),
            $this->getEnvio_numero_exterior(),
            $this->getEnvio_numero_interior(),
            $this->getEnvio_referencias(),
            $this->getEnvio_codigo_postal()
        );
        $this->setColumnas($campos);
        $this->setWhere('id_datos_envio = ' . $this->getId_datos_envio());
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_pedidos_datos_envio);
        return $this->update();
    }

    public function consultar_productos() {
        $campos = array(
            "ecompp.id_pedido",
            "ecompp.codigo_producto",
            "ecompp.id_producto",
            "ecompp.producto",
            "ecompp.precio",
            "ecompp.cantidad",
            "ecompp.iva",
            "ecompp.subtotal",
            "ecompp.descuento",
            "ecompp.tipo"
        );

        $this->setColumnas($campos);
        $this->setWhere("id_pedido = " . $this->getId_pedido());
        $this->setTabla($this->tabla_ecom_pedidos_productos . " ecompp");
//    $this->setInnerJoin($this->tabla_ecom_productos . " ecomp ON ecomp.id_producto = ecompp.id_producto");
//    $this->setLeftJoin($this->tabla_ecom_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        return $this->listar();
    }

    public function eliminar_productos() {
        $this->setWhere('id_pedido = ' . $this->getId_pedido());
        $this->setTabla($this->tabla_ecom_pedidos_productos);
        return $this->eliminar();
    }

    public function registrar_productos() {
        $campos = array(
            "id_pedido",
            "id_producto",
            "producto",
            "cantidad",
            "precio",
            "descuento",
            "iva",
            "subtotal",
            "tipo"
        );
        $valores = array(
            $this->getId_pedido(),
            $this->getId_producto(),
            $this->getProducto(),
            $this->getCantidad(),
            $this->getPrecio(),
            $this->getDescuento(),
            $this->getIva(),
            $this->getSubtotal(),
            $this->getItem_tipo()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_pedidos_productos);
        return $this->insertar();
    }

    public function consultar_existencia_estatus_pedido() {
        $campos = array(
            "id_estatus_pedido"
        );
        $this->setColumnas($campos);
        $this->setWhere("id_estatus_pedido = " . $this->getId_estatus_pedido());
        $this->setAnd("id_pedido = " . $this->getId_pedido());
        $this->setTabla($this->tabla_ecom_pedidos_estatus);
        return $this->buscarRegistro();
    }

    public function insertar_estatus_pedido() {
        $campos = array(
            "id_pedido",
            "id_estatus_pedido",
            "fch_r"
        );
        $valores = array(
            $this->getId_pedido(),
            $this->getId_estatus_pedido(),
            DATE_NOW
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_pedidos_estatus);
        return $this->insertar();
    }

    public function eliminar_pagos() {
        $this->setWhere('id_pedido = ' . $this->getId_pedido());
        $this->setTabla($this->tabla_ecom_pedidos_pagos);
        return $this->eliminar();
    }

    public function obtener_estatus() {
        $campos = array(
            "id_estatus_pedido",
            "estatus_pedido"
        );
        $this->setColumnas($campos);
        $this->setWhere("visible != 0 && (estatus = 2 || estatus = 1)");
        $this->setTabla($this->tabla_erp_estatus_pedidos);
        return $this->listar();
    }

    public function consultar_pagos() {
        $campos = array(
            "id_pedido",
            "id_metodo_pago",
            "cantidad",
            "estatus"
        );
        $this->setColumnas($campos);
        $this->setWhere('id_pedido = ' . $this->getId_pedido());
        $this->setTabla($this->tabla_ecom_pedidos_pagos);
        return $this->listar();
    }

    public function registrar_pagos() {
        $campos = array(
            "id_pedido",
            "id_metodo_pago",
            "cantidad",
            "estatus"
        );
        $valores = array(
            $this->getId_pedido(),
            $this->getId_metodo_pago(),
            $this->getCantidad_pago(),
            1
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_ecom_pedidos_pagos);
        return $this->insertar();
    }

    public function consultar_lista_ventas() {
//    SELECT ecomp.id_pedido,ecomp.total,ecomp.estatus, crmc.nombres FROM ecom_pedidos ecomp
//LEFT JOIN crm_clientes crmc ON crmc.id_cliente = ecomp.id_cliente
        $this->setColumnas(array(
            "ecomp.id_pedido",
            "ecomp.total",
            "ecomp.estatus",
            "crmc.nombres",
            "ecomp.fch_r"
        ));
        $this->setTabla($this->tabla_ecom_pedidos . " ecomp");
        $this->setInnerJoin($this->tabla_crm_clientes . " crmc ON crmc.id_cliente = ecomp.id_cliente");
        $this->setOrderBy("ecomp.id_pedido");
        $this->setAscDesc("DESC");
        return $this->listar();
    }

    public function getId_estatus_pedido() {
        return $this->id_estatus_pedido;
    }

    public function setId_estatus_pedido($id_estatus_pedido): void {
        $this->id_estatus_pedido = $id_estatus_pedido;
    }

    public function getHora_inicial() {
        return $this->hora_inicial;
    }

    public function getHora_final() {
        return $this->hora_final;
    }

    public function setHora_inicial($hora_inicial): void {
        $this->hora_inicial = $hora_inicial;
    }

    public function setHora_final($hora_final): void {
        $this->hora_final = $hora_final;
    }

    public function getFecha_entrega() {
        return $this->fecha_entrega;
    }

    public function setFecha_entrega($fecha_entrega): void {
        $this->fecha_entrega = $fecha_entrega;
    }

    public function getId_producto() {
        return $this->id_producto;
    }

    public function getItem_tipo() {
        return $this->item_tipo;
    }

    public function setId_producto($id_producto): void {
        $this->id_producto = $id_producto;
    }

    public function setItem_tipo($item_tipo): void {
        $this->item_tipo = $item_tipo;
    }

    public function getId_cliente() {
        return $this->id_cliente;
    }

    public function setId_cliente($id_cliente): void {
        $this->id_cliente = $id_cliente;
    }

    public function getId_metodo_pago() {
        return $this->id_metodo_pago;
    }

    public function getCodigo_barras() {
        return $this->codigo_barras;
    }

    public function getCodigo_interno() {
        return $this->codigo_interno;
    }

    public function getCodigo_proveedor() {
        return $this->codigo_proveedor;
    }

    public function setId_metodo_pago($id_metodo_pago): void {
        $this->id_metodo_pago = $id_metodo_pago;
    }

    public function setCodigo_barras($codigo_barras): void {
        $this->codigo_barras = $codigo_barras;
    }

    public function setCodigo_interno($codigo_interno): void {
        $this->codigo_interno = $codigo_interno;
    }

    public function setCodigo_proveedor($codigo_proveedor): void {
        $this->codigo_proveedor = $codigo_proveedor;
    }

    public function getCantidad_pago() {
        return $this->cantidad_pago;
    }

    public function setCantidad_pago($cantidad_pago): void {
        $this->cantidad_pago = $cantidad_pago;
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

    public function getPedido_estatus() {
        return $this->pedido_estatus;
    }

    public function getEnvio_estado() {
        return $this->envio_estado;
    }

    public function getEnvio_ciudad() {
        return $this->envio_ciudad;
    }

    public function getEnvio_colonia() {
        return $this->envio_colonia;
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

    public function getEnvio_referencias() {
        return $this->envio_referencias;
    }

    public function getEnvio_codigo_postal() {
        return $this->envio_codigo_postal;
    }

    public function getFacturacion_estado() {
        return $this->facturacion_estado;
    }

    public function getFacturacion_ciudad() {
        return $this->facturacion_ciudad;
    }

    public function getFacturacion_colonia() {
        return $this->facturacion_colonia;
    }

    public function getFacturacion_calle() {
        return $this->facturacion_calle;
    }

    public function getFacturacion_numero_exterior() {
        return $this->facturacion_numero_exterior;
    }

    public function getFacturacion_numero_interior() {
        return $this->facturacion_numero_interior;
    }

    public function getFacturacion_referencias() {
        return $this->facturacion_referencias;
    }

    public function getFacturacion_codigo_postal() {
        return $this->facturacion_codigo_postal;
    }

    public function getMetodo_pago_estatus() {
        return $this->metodo_pago_estatus;
    }

    public function getTabla_ecom_pedidos_pagos() {
        return $this->tabla_ecom_pedidos_pagos;
    }

    public function getProducto() {
        return $this->producto;
    }

    public function getCantidad() {
        return $this->cantidad;
    }

    public function getPrecio() {
        return $this->precio;
    }

    public function getProducto_descuento() {
        return $this->producto_descuento;
    }

    public function getProducto_iva() {
        return $this->producto_iva;
    }

    public function getProducto_subtotal() {
        return $this->producto_subtotal;
    }

    public function setPedido_estatus($pedido_estatus): void {
        $this->pedido_estatus = $pedido_estatus;
    }

    public function setEnvio_estado($envio_estado): void {
        $this->envio_estado = $envio_estado;
    }

    public function setEnvio_ciudad($envio_ciudad): void {
        $this->envio_ciudad = $envio_ciudad;
    }

    public function setEnvio_colonia($envio_colonia): void {
        $this->envio_colonia = $envio_colonia;
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

    public function setEnvio_referencias($envio_referencias): void {
        $this->envio_referencias = $envio_referencias;
    }

    public function setEnvio_codigo_postal($envio_codigo_postal): void {
        $this->envio_codigo_postal = $envio_codigo_postal;
    }

    public function setFacturacion_estado($facturacion_estado): void {
        $this->facturacion_estado = $facturacion_estado;
    }

    public function setFacturacion_ciudad($facturacion_ciudad): void {
        $this->facturacion_ciudad = $facturacion_ciudad;
    }

    public function setFacturacion_colonia($facturacion_colonia): void {
        $this->facturacion_colonia = $facturacion_colonia;
    }

    public function setFacturacion_calle($facturacion_calle): void {
        $this->facturacion_calle = $facturacion_calle;
    }

    public function setFacturacion_numero_exterior($facturacion_numero_exterior): void {
        $this->facturacion_numero_exterior = $facturacion_numero_exterior;
    }

    public function setFacturacion_numero_interior($facturacion_numero_interior): void {
        $this->facturacion_numero_interior = $facturacion_numero_interior;
    }

    public function setFacturacion_referencias($facturacion_referencias): void {
        $this->facturacion_referencias = $facturacion_referencias;
    }

    public function setFacturacion_codigo_postal($facturacion_codigo_postal): void {
        $this->facturacion_codigo_postal = $facturacion_codigo_postal;
    }

    public function setMetodo_pago_estatus($metodo_pago_estatus): void {
        $this->metodo_pago_estatus = $metodo_pago_estatus;
    }

    public function setTabla_ecom_pedidos_pagos($tabla_ecom_pedidos_pagos): void {
        $this->tabla_ecom_pedidos_pagos = $tabla_ecom_pedidos_pagos;
    }

    public function setProducto($producto): void {
        $this->producto = $producto;
    }

    public function setCantidad($cantidad): void {
        $this->cantidad = $cantidad;
    }

    public function setPrecio($precio): void {
        $this->precio = $precio;
    }

    public function setProducto_descuento($producto_descuento): void {
        $this->producto_descuento = $producto_descuento;
    }

    public function setProducto_iva($producto_iva): void {
        $this->producto_iva = $producto_iva;
    }

    public function setProducto_subtotal($producto_subtotal): void {
        $this->producto_subtotal = $producto_subtotal;
    }

    public function getId_pedido() {
        return $this->id_pedido;
    }

    public function getId_datos_envio() {
        return $this->id_datos_envio;
    }

    public function getId_datos_facturacion() {
        return $this->id_datos_facturacion;
    }

    public function getDescuento() {
        return $this->descuento;
    }

    public function getEnvio() {
        return $this->envio;
    }

    public function getIva() {
        return $this->iva;
    }

    public function getSubtotal() {
        return $this->subtotal;
    }

    public function getTotal() {
        return $this->total;
    }

    public function getIp() {
        return $this->ip;
    }

    public function getNavegador() {
        return $this->navegador;
    }

    public function getEstatus() {
        return $this->estatus;
    }

    public function setId_pedido($id_pedido): void {
        $this->id_pedido = $id_pedido;
    }

    public function setId_datos_envio($id_datos_envio): void {
        $this->id_datos_envio = $id_datos_envio;
    }

    public function setId_datos_facturacion($id_datos_facturacion): void {
        $this->id_datos_facturacion = $id_datos_facturacion;
    }

    public function setDescuento($descuento): void {
        $this->descuento = $descuento;
    }

    public function setEnvio($envio): void {
        $this->envio = $envio;
    }

    public function setIva($iva): void {
        $this->iva = $iva;
    }

    public function setSubtotal($subtotal): void {
        $this->subtotal = $subtotal;
    }

    public function setTotal($total): void {
        $this->total = $total;
    }

    public function setIp($ip): void {
        $this->ip = $ip;
    }

    public function setNavegador($navegador): void {
        $this->navegador = $navegador;
    }

    public function setEstatus($estatus): void {
        $this->estatus = $estatus;
    }
}
