var KTAppEcommerceSalesSaveOrder = function () {
// Shared variables
    var table;
    var datatable;
    const initSaveOrder = () => {

// Init datatable --- more info on datatables: https://datatables.net/manual/
        table = document.querySelector('#kt_ecommerce_edit_order_product_table');
        datatable = $(table).DataTable({
            'order': [],
            "scrollY": "400px",
            "scrollCollapse": true,
            "paging": false,
            "info": false,
            'columnDefs': [
                {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
            ]
        });
    }

// Init condition select2
    let initConditionsSelect2 = () => {
// Tnit new repeating condition types
        const allConditionTypes = document.querySelectorAll('[data-kt-ecommerce-catalog-add-product="product_option"]');
        allConditionTypes.forEach(type => {
            if ($(type).hasClass("select2-hidden-accessible")) {
                return;
            } else {
                $(type).select2({
                    minimumResultsForSearch: -1
                });
            }
        });
    }


// Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatable = () => {
        const filterSearch = document.querySelector('[data-kt-ecommerce-edit-order-filter="search"]');
        let respuesta = '', tabla_init = '', contador = 0;
        filterSearch.addEventListener('keyup', async function (e) {
//      console.log(e.target.value);
            console.log(contador);
            contador++;
            await setTimeout(async function () {
                console.log("settimeout-" + contador);
                contador--;
                if (contador == 0) {
                    console.log("peticion");
                    respuesta = await busqueda_productos(e.target.value);
//      console.log(respuesta);
                    if (respuesta.error == false) {
                        tabla_init = await ui_pedido_productos(respuesta.depurar);
                        if (tabla_init) {
                            handleProductSelect();
                        }
                    } else {
                        if (tabla_init) {
                            tabla_init.search(e.target.value).draw();
                        }
                    }
                }
            }, 1000);
        });
    }


// Handle product select
    const handleProductSelect = () => {
// Define variables
        const checkboxes = table.querySelectorAll('[type="checkbox"]');
        const target = document.getElementById('kt_ecommerce_edit_order_selected_products');
        const span_total_costo = document.getElementById('kt_ecommerce_edit_order_total_costo');
        const span_total_precio = document.getElementById('kt_ecommerce_edit_order_total_precio');
        const span_ganancia_total = document.getElementById('kt_ecommerce_edit_order_ganancia_total');
        const span_porcentaje_ganancia_total = document.getElementById('kt_ecommerce_edit_order_porcentaje_ganancia_total');
        const cantidad_producto = () => {
            detectEmpty();
        }



// Loop through all checked products
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', e => {
// Select parent row element
                const parent = checkbox.closest('tr');
                // Clone parent element as variable
                const product = parent.querySelector('[data-kt-ecommerce-edit-order-filter="product"]').cloneNode(true);
                // Create inner wrapper
                const innerWrapper = document.createElement('div');
                //Crear input id producto
                const inputProduct = document.createElement('input');
                inputProduct.classList.add("producto-selected");
                inputProduct.classList.add('d-none');
                // Store inner content
                const innerContent = product.innerHTML;
                // Add & remove classes on parent wrapper
                const wrapperClassesAdd = ['col', 'my-2'];
                const wrapperClassesRemove = ['d-flex', 'align-items-center'];
                // Define additional classes
                const additionalClasses = ['border', 'border-dashed', 'rounded', 'p-3', 'bg-body'];
                // Update parent wrapper classes
                product.classList.remove(...wrapperClassesRemove);
                product.classList.add(...wrapperClassesAdd);
                // Remove parent default content
                product.innerHTML = '';
                // Update inner wrapper classes
                innerWrapper.classList.add(...wrapperClassesRemove);
                innerWrapper.classList.add(...additionalClasses);
                // Apply stored inner content into new inner wrapper
                innerWrapper.innerHTML = innerContent;
                // Append new inner wrapper to parent wrapper
                product.appendChild(innerWrapper);
                // Get product id
                const productId = product.getAttribute('data-kt-ecommerce-edit-order-id');
                //Set product id

                inputProduct.name = "productos";
                inputProduct.value = productId;
                inputProduct.id = "producto-seleccionado-" + productId;
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-costo", product.getAttribute("data-producto-costo"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-tipo-item", product.getAttribute("data-tipo-item"));
                inputProduct.setAttribute("data-producto-existencia", product.getAttribute("data-producto-existencia"));
                inputProduct.setAttribute("data-unidad-venta", product.getAttribute("data-unidad-venta"));
                inputProduct.setAttribute("data-factor", product.getAttribute("data-factor"));
                let unidad_venta = product.getAttribute("data-unidad-venta");
                if (product.getAttribute("data-unidad-venta") != 1) {
                    inputProduct.setAttribute("data-unidad-venta-abreviatura", product.getAttribute("data-unidad-venta-abreviatura"));
                }
//dialer
                const div_dialer = document.createElement('div');
                div_dialer.classList.add("input-group");
                div_dialer.classList.add("w-md-200px");
                div_dialer.setAttribute("data-kt-dialer", "true");
                div_dialer.setAttribute("data-kt-dialer-step", "1");
                //button
                const button_dialer_decrease = document.createElement('button');
                button_dialer_decrease.classList.add("btn");
                button_dialer_decrease.classList.add("btn-icon");
                button_dialer_decrease.classList.add("btn-outline");
                button_dialer_decrease.classList.add("btn-active-color-primary");
                button_dialer_decrease.setAttribute("type", "button");
                button_dialer_decrease.setAttribute("data-kt-dialer-control", "decrease");
                //i increase
                const i_dialer_decrease = document.createElement('i');
                i_dialer_decrease.classList.add("bi");
                i_dialer_decrease.classList.add("bi-dash");
                i_dialer_decrease.classList.add("fs-1");
                //i
                button_dialer_decrease.appendChild(i_dialer_decrease);
                //button
                //input
                const input_dialer = document.createElement('input');
                input_dialer.classList.add("form-control");
                input_dialer.setAttribute("type", "text");
//                input_dialer.setAttribute("readonly", "readonly");
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", "1");
//                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
                input_dialer.setAttribute("data-kt-ecommerce-edit-order-filter", "cantidad");
                input_dialer.addEventListener('change', cantidad_producto);
                //input
                //button
                const button_dialer_increase = document.createElement('button');
                button_dialer_increase.classList.add("btn");
                button_dialer_increase.classList.add("btn-icon");
                button_dialer_increase.classList.add("btn-outline");
                button_dialer_increase.classList.add("btn-active-color-primary");
                button_dialer_increase.setAttribute("type", "button");
                button_dialer_increase.setAttribute("data-kt-dialer-control", "increase");
                //i increase
                const i_dialer_increase = document.createElement('i');
                i_dialer_increase.classList.add("bi");
                i_dialer_increase.classList.add("bi-plus");
                i_dialer_increase.classList.add("fs-1");
                //i
                button_dialer_increase.appendChild(i_dialer_increase);
                //button
                //button
                if (unidad_venta == 1) {
                    div_dialer.appendChild(button_dialer_decrease);
                    div_dialer.appendChild(input_dialer);
                    div_dialer.appendChild(button_dialer_increase);
                } else {
                    button_dialer_decrease.innerHTML = product.getAttribute("data-unidad-venta-abreviatura");
                    div_dialer.appendChild(button_dialer_decrease);
                    div_dialer.appendChild(input_dialer);
                }
//dialer
                product.appendChild(inputProduct);
                product.appendChild(div_dialer);
                if (e.target.checked) {
// Add product to selected product wrapper
                    target.appendChild(product);
//                    KTDialer.createInstances();
                } else {
// Remove product from selected product wrapper
                    const selectedProduct = target.querySelector('[data-kt-ecommerce-edit-order-id="' + productId + '"]');
                    if (selectedProduct) {
                        target.removeChild(selectedProduct);
                    }
                }

// Trigger empty message logic
                detectEmpty();
            });
        });
        // Handle empty list message
        const detectEmpty = () => {
// Select elements
            const message = target.querySelector('span');
            const products = target.querySelectorAll('[data-kt-ecommerce-edit-order-filter="product"]');
            console.log(products);
            // Detect if element is empty
            if (products.length < 1) {
// Show message
                message.classList.remove('d-none');
                // Reset price
                pedido_subtotal.innerText = '0.00';
                // Calculate price
                calculateTotal();
            } else {
// Hide message
                message.classList.add('d-none');
                // Calculate price
                calculateTotalProductos(products);
            }
        }

// Calculate total cost
        const calculateTotalProductos = (products) => {
            let countPrice = 0, countCosto = 0, ganancia = 0, ganancia_producto = 0, ganancia_total = 0;
            // Loop through all selected prodcucts
            let cantidad_productos = products.length;
            products.forEach(product => {
                console.log(product);
                // Get product price
                let price = parseFloat(product.querySelector('[data-kt-ecommerce-edit-order-filter="price"]').innerText);
                let costo = parseFloat(product.querySelector('[data-kt-ecommerce-edit-order-filter="costo"]').innerText);
                let cantidad = parseFloat(product.querySelector('[data-kt-ecommerce-edit-order-filter="cantidad"]').value);
                let precio_total = $(product)[0].getAttribute('data-unidad-venta') > 1 ? (cantidad / $(product)[0].getAttribute('data-factor')) * price : price * cantidad;
                // Add to total
                ganancia_producto = parseFloat(price - costo);
                ganancia = parseFloat(ganancia + ganancia_producto);
                countCosto = parseFloat(countCosto + costo);
                countPrice = parseFloat(countPrice + price);
            });
            // Update subtotal price
            ganancia_total = (ganancia / countPrice)*100;

            span_total_costo.innerText = countCosto.toFixed(2);
            span_total_precio.innerText = countPrice.toFixed(2);
            span_porcentaje_ganancia_total.innerText = ganancia_total.toFixed(2);
            span_ganancia_total.innerText = ganancia.toFixed(2);
        }


    }

// Submit form handler
    const handleSubmit = () => {
// Define variables
        let validator;
        // Get elements
        const form = document.getElementById('kt_ecommerce_edit_order_form');
        const submitButton = document.getElementById('kt_ecommerce_edit_order_submit');
        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validator = FormValidation.formValidation(
                form,
                {
                    fields: {
                        'payment_method': {
                            validators: {
                                notEmpty: {
                                    message: 'Payment method is required'
                                }
                            }
                        },
                        'shipping_method': {
                            validators: {
                                notEmpty: {
                                    message: 'Shipping method is required'
                                }
                            }
                        },
                        'order_date': {
                            validators: {
                                notEmpty: {
                                    message: 'Order date is required'
                                }
                            }
                        },
                        'billing_order_address_1': {
                            validators: {
                                notEmpty: {
                                    message: 'Address line 1 is required'
                                }
                            }
                        },
                        'billing_order_postcode': {
                            validators: {
                                notEmpty: {
                                    message: 'Postcode is required'
                                }
                            }
                        },
                        'billing_order_state': {
                            validators: {
                                notEmpty: {
                                    message: 'State is required'
                                }
                            }
                        },
                        'billing_order_country': {
                            validators: {
                                notEmpty: {
                                    message: 'Country is required'
                                }
                            }
                        }
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: '.fv-row',
                            eleInvalidClass: '',
                            eleValidClass: ''
                        })
                    }
                }
        );
        // Handle submit button
        submitButton.addEventListener('click', e => {
            e.preventDefault();
            console.log(e);
            // Validate form before submit
            if (validator) {
                validator.validate().then(function (status) {
                    console.log('validated!');
                    if (status == 'Valid') {
                        let informacion = informacion_pedido();
//            let informacion = {"cliente": {"nombre": "Manuel Alejandro", "apellido_paterno": "Rodríguez", "apellido_materno": "Gutiérrez", "contacto_1": "3336372508", "contado_2": "3322068429", "datos_facturacion": {"facturar": 1, "uso_cfdi": "Gastos en general", "razon_social": "Manuel Alejandro Rodriguez Gutierrez", "rfc": "ROGM9604248P5", "regimen_fiscal": "Personas Fisicas con Actividad Empresarial", "estado": "Jalisco", "ciudad": "Guadalajara", "colonia": "Oblatos", "calle": "Francisco Javier Mina", "numero_exterior": "967", "numero_interior": null, "codigo_postal": "44700"}, "datos_envio": {"calle": "Francisco Javier Mina", "numero_exterior": "967", "numero_interior": null, "colonia": "Guadalajara", "ciudad": "Guadalajara", "estado": "Jalisco", "codigo_postal": "44700", "referencias": "Entre Francisco Sarabia y Avenida de la paz"}}, "pedido": {"productos": [{"id_producto": "597", "codigo_barras": "7501556480252", "codigo_interno": "null", "codigo_proveedor": null, "cantidad": "1", "precio": 1950, "descuento": "0.00", "iva": 312, "subtotal": 1950, "producto": "Jaula amsterdam II para conejo/Huron"}], "descuento": 0, "envio": 45, "iva": 319.2, "total": 1995, "tipo_entrega": {"id_tipo_entrega": "1", "tipo_entrega": "Recoger en tienda", "puntos_entrega": 1}, "horario_entrega": {"fecha_entrega": "2022-11-04", "hora_entrega": "12:00", "turno_horario": "PM"}}, "pagos": [{"metodo": "1", "cantidad": "1995"}]};
                        console.log(informacion);
                        // validar productos
                        if (informacion.pedido.productos.length > 0) {
                            // validar detalles pedido 
                            let respuesta_validacion_detalles_pedido = validacion_detalles_pedido(informacion);
                            console.log(respuesta_validacion_detalles_pedido);
                            if (respuesta_validacion_detalles_pedido.error == false) {
                                // validar informacion cliente
                                let respuesta_validacion_informacion_cliente = validacion_informacion_cliente(informacion);
                                console.log(respuesta_validacion_informacion_cliente);
                                if (respuesta_validacion_informacion_cliente.error == false) {
                                    // validar informacion envio
                                    let respuesta_validacion_informacion_envio = validacion_informacion_envio(informacion);
                                    console.log(respuesta_validacion_informacion_envio);
                                    if (respuesta_validacion_informacion_envio.error == false) {
                                        //validar datos facturacion
                                        let respuesta_validacion_datos_facturacion = validacion_datos_facturacion(informacion);
                                        console.log(respuesta_validacion_datos_facturacion);
                                        if (respuesta_validacion_datos_facturacion.error == false) {
                                            console.log("Todo bien");
                                            funcion_crear_pedido(informacion);
                                        } else {
                                            Swal.fire({
                                                html: respuesta_validacion_datos_facturacion.mensaje_error,
                                                icon: "error",
                                                buttonsStyling: false,
                                                confirmButtonText: "Intentar de nuevo!",
                                                customClass: {
                                                    confirmButton: "btn btn-primary"
                                                }
                                            });
                                        }
                                    } else {
                                        Swal.fire({
                                            html: respuesta_validacion_informacion_envio.mensaje_error,
                                            icon: "error",
                                            buttonsStyling: false,
                                            confirmButtonText: "Intentar de nuevo!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        });
                                    }
                                } else {
                                    Swal.fire({
                                        html: respuesta_validacion_informacion_cliente.mensaje_error,
                                        icon: "error",
                                        buttonsStyling: false,
                                        confirmButtonText: "Intentar de nuevo!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    });
                                }
                            } else {
                                Swal.fire({
                                    html: respuesta_validacion_detalles_pedido.mensaje_error,
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Intentar de nuevo!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                });
                            }



//                            
//                            console.log(respuesta);
                        } else {
                            Swal.fire({
                                html: "No se puede generar la venta, no hay productos asignados",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Intentar de nuevo!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            });
                        }
//                        else {
//                            Swal.fire({
//                                html: "No se puede generar la venta, no hay productos asignados",
//                                icon: "error",
//                                buttonsStyling: false,
//                                confirmButtonText: "Intentar de nuevo!",
//                                customClass: {
//                                    confirmButton: "btn btn-primary"
//                                }
//                            });
//                        }
//            
//            submitButton.setAttribute('data-kt-indicator', 'on');
                        // Disable submit button whilst loading
//            submitButton.disabled = true;
//                        setTimeout(function () {
//                            submitButton.removeAttribute('data-kt-indicator');
//
//                            Swal.fire({
//                                text: "Form has been successfully submitted!",
//                                icon: "success",
//                                buttonsStyling: false,
//                                confirmButtonText: "Ok, got it!",
//                                customClass: {
//                                    confirmButton: "btn btn-primary"
//                                }
//                            }).then(function (result) {
//                                if (result.isConfirmed) {
//                                    // Enable submit button after loading
//                                    submitButton.disabled = false;
//
//                                    // Redirect to customers list page
//                                    window.location = form.getAttribute("data-kt-redirect");
//                                }
//                            });
//                        }, 2000);
                    } else {
                        Swal.fire({
                            html: "Sorry, looks like there are some errors detected, please try again.",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                    }
                });
            }
        })
    }


// Public methods
    return {
        init: function () {
            initSaveOrder();
            initConditionsSelect2();
            handleSearchDatatable();
            handleProductSelect();
            handleSubmit();
        }
    };
}();
// On document ready
KTUtil.onDOMContentLoaded(function () {

    let data = "";
    let productos = "";
    let metodos_pago = "";
    let tipos_entrega = "";
    let estatus_pedido = "";
//  productos = listar();
//  productos = JSON.parse(productos);
//  ui_pedido_productos(productos.depurar);

    KTAppEcommerceSalesSaveOrder.init();
});
$(document).ready(async function () {
    let almacenes = '', sucursales = '';
//    let respuesta_almacenes = await obtener_almacenes();
//    let respuesta_sucursales = await obtener_sucursales();
//    almacenes = respuesta_almacenes;
//    sucursales = respuesta_sucursales;

//    ui_selects_proveedores("#select_establecimientos", respuesta_proveedores.depurar, 1, 1);
//    ui_selects_sucursales("#select_establecimientos", sucursales.depurar, 0, 0);

});
function ui_selects_proveedores(identificador, data, limpiar, opcion_inicial) {

    let opcion = "";
    opcion = opcion_inicial == 1 ? crear_opcion({value: -1, text: "Seleccionar Proveedor"}) : '';
    limpiar == 1 ? $(identificador).html("") : '';
    $(identificador).append(opcion);
    data.map(function (i) {

        opcion = crear_opcion({value: i.id_proveedor, text: i.proveedor, data_id_lista_proveedor: i.id_lista_proveedor});
        $(identificador).append(opcion);
    });
}

function ui_selects_sucursales(identificador, data, limpiar, opcion_inicial) {
    console.log(data);
    let opcion = "";
    opcion = opcion_inicial == 1 ? crear_opcion({value: -1, text: "Seleccionar Establecimiento"}) : '';
    limpiar == 1 ? $(identificador).html("") : '';
    $(identificador).append(opcion);
    data.map(function (i) {

        opcion = crear_opcion({value: i.id_sucursal, text: i.sucursal, data_tipo_establecimiento: i.tipo_establecimiento});
        $(identificador).append(opcion);
    });
}

function crear_opcion(opciones) {
    let opcion = $('<option>', opciones)[0];
    return opcion;
}

async function obtener_proveedores() {

    let respuesta = await consulta_proveedores();
    return respuesta;
}

async function consulta_proveedores() {

    let respuesta = await consultar_proveedores();
    return respuesta;
}

function consultar_proveedores(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/proveedor/listas_consultar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    })
    $.ajaxSetup({async: true});
    return respuesta;
}

async function obtener_sucursales() {

    let respuesta = await consulta_sucursales();
    return respuesta;
}

async function consulta_sucursales() {

    let respuesta = await consultar_sucursales();
    return respuesta;
}

function consultar_sucursales(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/sucursal/consultar_sucursales", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    })
    $.ajaxSetup({async: true});
    return respuesta;
}

async function busqueda_productos(value) {
    let data = {
        busqueda: value
    }
    let respuesta = await consulta_busqueda(data);
    return respuesta;
}

function consulta_busqueda(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/utilidad/consultar_productos_utilidad_busqueda", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

async function ui_pedido_productos(data) {
//  console.log(data);
    let arreglo_productos = obtener_productos_agregados();
    let codigo_productos = "";
    console.log(arreglo_productos);
    for (let indice in data) {
        console.log(arreglo_productos.includes(data[indice].id_producto));
        codigo_productos += `
        <tr class="odd">
          <!--begin::Product=-->
          <td>
              <div class="d-flex align-items-center" data-kt-ecommerce-edit-order-filter="product" data-producto-descuento="0.00" ${data[indice].factor ? 'data-factor="' + data[indice].factor + '"' : ""} ${data[indice].id_unidad_venta ? 'data-unidad-venta="' + data[indice].id_unidad_venta + '"' : 'data-unidad-venta="1"'} ${data[indice].id_unidad_venta ? 'data-unidad-venta-abreviatura="' + data[indice].abreviatura + '"' : ''} data-producto-existencia="${data[indice].existencia}" data-tipo-item="${data[indice].tipo_item}" data-producto-iva="${data[indice].precio_base * 0.16}" data-producto-precio="${data[indice].precio_base}" data-producto-costo="${data[indice].costo}" data-producto-nombre="${data[indice].nombre}" data-kt-ecommerce-edit-order-id="${data[indice].id_producto ? data[indice].id_producto : data[indice].id_paquete}" data-codigo-interno="${data[indice].codigo_interno}" data-codigo-proveedor="${data[indice].sku}" data-codigo-barras="${data[indice].codigo_barras}">
                  <!--begin::Thumbnail-->
                  <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="symbol symbol-50px">
                      <span class="symbol-label" style="background-image:url('${data[indice].url_imagen}');"></span>
                  </a>
                  <!--end::Thumbnail-->
                  <div class="ms-5">
                      <!--begin::Title-->
                      <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="text-gray-800 text-hover-primary fs-5 fw-bold">${data[indice].nombre}</a>
                      <!--end::Title-->
                      <!--begin::SKU-->
                      <div class="text-muted fs-7">SKU: ${data[indice].sku}</div>
                      <!--end::SKU-->
                      <!--begin::EXSITENCIA-->
                      <!--<div class="text-muted fs-7">EXISTENCIA: {data[indice].existencia}</div>-->
                      <!--end::EXSITENCIA-->
                       <!--begin::Price-->
                      <div class="fw-semibold fs-7">Price: $
                          <span data-kt-ecommerce-edit-order-filter="price" ondblclick="modal_cambiar_precio(this);" id="span_precio_${data[indice].id_producto ? data[indice].id_producto : data[indice].id_paquete}">${data[indice].precio_base}</span></div>
                      <!--end::Price-->
                       <!--begin::Costo-->
                      <div class="fw-semibold fs-7">Costo: $
                          <span data-kt-ecommerce-edit-order-filter="costo" ondblclick="modal_cambiar_precio(this);" id="span_precio_${data[indice].id_producto ? data[indice].id_producto : data[indice].id_paquete}">${data[indice].costo}</span></div>
                      <!--end::Costo-->
                       <!--begin::Ganancia-->
                      <div class="fw-semibold fs-7">Ganancia: $
                          <span data-kt-ecommerce-edit-order-filter="ganancia" ondblclick="modal_cambiar_precio(this);" id="span_precio_${data[indice].id_producto ? data[indice].id_producto : data[indice].id_paquete}">${data[indice].precio_base - data[indice].costo}</span></div>
                      <!--end::Ganancia-->
                       <!--begin::PorcentajeUtilidadBruta-->
                      <div class="fw-semibold fs-7">Porcentaje Ganancia Bruta: 
                          <span data-kt-ecommerce-edit-order-filter="porcentaje_ganancia_bruta" ondblclick="modal_cambiar_precio(this);" id="span_precio_${data[indice].id_producto ? data[indice].id_producto : data[indice].id_paquete}">${(data[indice].precio_base - data[indice].costo) / data[indice].precio_base}
                          %
                          </span></div>
                      <!--end::PorcentajeUtilidadBruta-->
                  </div>
              </div>
          </td>
          <!--end::Product=-->
          <!--begin::Qty=-->
          <td class="text-end pe-5" data-order="9">
              <!--<span class="fw-bold text-warning ms-3">{data[indice].existencia}</span>-->
          </td>
          <!--end::Qty=-->
          <!--begin::Checkbox-->
          <td>
              <div class="form-check form-check-sm form-check-custom form-check-solid">
                  <input class="form-check-input input-check-producto" type="checkbox" ${arreglo_productos.includes(data[indice].id_producto ? data[indice].id_producto : data[indice].id_paquete) ? 'checked="checked"' : ''}>
              </div>
          </td>
          <!--end::Checkbox-->
      </tr>
    `;
    }
//  console.log(codigo_productos);

    return await init_dataTables('kt_ecommerce_edit_order_product_table', codigo_productos);
}

function obtener_productos_agregados() {
    let productos = [], subtotal = 0, iva = 0, cantidad = 0, precio = 0;
    if ($(".producto-selected")) {
        $(".producto-selected").each(function (item) {
            productos.push(parseInt(this.value));
        });
    }

    return productos;
}

function init_dataTables(identificador, codigo_productos) {
    $('#' + identificador).DataTable().destroy();
    $("#pedido_productos").html(codigo_productos);
    // Init datatable --- more info on datatables: https://datatables.net/manual/
    let table = document.querySelector('#' + identificador);
    let datatable = $(table).DataTable({
        'order': [],
        "scrollY": "400px",
        "scrollCollapse": true,
        "paging": false,
        "info": false,
        'columnDefs': [
            {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
        ]
    });
    return datatable;
}

async function guardar_pedido() {
    let informacion = informacion_inventario();
    let respuesta = await registrar_inventario(informacion);
    if (respuesta.error == false) {
        Swal.fire({
            text: respuesta.mensaje,
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Perfecto!",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        })
        setTimeout(function () {
            window.location = "/utilidad/mostrar_grupos";
        }, 2000);
    } else {
        Swal.fire({
            text: respuesta.mensaje,
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Perfecto!",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        })
    }
}

function registrar_inventario(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/utilidad/registrar_grupo_utilidad", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    })
    $.ajaxSetup({async: true});
    return respuesta;
}

async function afectar_inventario() {
    let informacion = await informacion_inventario();
    let respuesta = await afectar_productos_inventario(informacion);
    if (respuesta.error == false) {
        Swal.fire({
            text: respuesta.mensaje,
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Perfecto!",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        })
        setTimeout(function () {
            window.location = "/inventario/mostrar";
        }, 2000);
    } else {
        Swal.fire({
            text: respuesta.mensaje,
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Perfecto!",
            customClass: {
                confirmButton: "btn btn-primary"
            }
        })
    }
}

function afectar_productos_inventario(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/inventario/afectar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    })
    $.ajaxSetup({async: true});
    return respuesta;
}

function informacion_inventario() {


    let productos = obtener_productos_venta();
    let nombre_inventario = $("#inventario_nombre").val() ? $("#inventario_nombre").val() : 0;
    let comentario = $("#inventario_comentario").val() ? $("#inventario_comentario").val() : 0;
    let porcentaje_total = $("#kt_ecommerce_edit_order_porcentaje_ganancia_total").text();
    let data = {
        
        "porcentaje_total":porcentaje_total,
        "nombre_grupo_utilidad": nombre_inventario,
        "productos": productos,
        "comentario": comentario
    };
    return data;
}

function obtener_productos_venta() {
    let productos = [], subtotal = 0, iva = 0, cantidad = 0, precio = 0;
    if ($(".producto-selected")) {
        $(".producto-selected").each(function (item) {
//              "codigo_barras": "2rwef",
//              "codigo_interno": "dfgdf",
//              "codigo_proveedor": "dfgd",
//              "cantidad": 1,
//              "precio": 1950,
//              "descuento": 0.00,
//              "iva": 312.00,
//              "subtotal": 1950,
//              "producto": "Jaula para conejo amsterdam III",

            precio = parseFloat(this.getAttribute("data-producto-precio"));
            costo = parseFloat(this.getAttribute("data-producto-costo"));
            productos.push({
                id_producto: this.value,
                precio: precio,
                costo:costo,
                producto: this.getAttribute("data-producto-nombre"),
            })
        });
    }

    return productos;
}