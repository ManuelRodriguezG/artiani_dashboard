"use strict";
let TIPOS_ENTREGA = null;
// Class definition
var KTAppEcommerceSalesSaveOrder = function () {
    // Shared variables
    var table;
    var datatable;
    // Private functions
    const initSaveOrder = () => {
        // Init flatpickr
        $('#pedido_fecha').flatpickr({
            altInput: true,
//            altFormat: "d F, Y",
            dateFormat: "Y-m-d",
            enableTime: false
        });
        $("#pedido_hora").flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i K",
        });
//    $("#kt_ecommerce_edit_order_date").daterangepicker({
//      timePicker: true,
//      startDate: moment().startOf("hour"),
//      endDate: moment().startOf("hour").add(32, "hour"),
//      locale: {
//        format: "Y-M-DD H:m A",
////        format: "M/DD hh:mm A"
//      }
//    });

        // Init select2 country options
        // Format options
        const optionFormat = (item) => {
            if (!item.id) {
                return item.text;
            }

            var span = document.createElement('span');
            var template = '';
            template += '<img src="' + item.element.getAttribute('data-kt-select2-country') + '" class="rounded-circle h-20px me-2" alt="image"/>';
            template += item.text;
            span.innerHTML = template;
            return $(span);
        }

        // Init Select2 --- more info: https://select2.org/        
        $('#kt_ecommerce_edit_order_billing_country').select2({
            placeholder: "Select a country",
            minimumResultsForSearch: Infinity,
            templateSelection: optionFormat,
            templateResult: optionFormat
        });
        $('#kt_ecommerce_edit_order_shipping_country').select2({
            placeholder: "Select a country",
            minimumResultsForSearch: Infinity,
            templateSelection: optionFormat,
            templateResult: optionFormat
        });
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

    let initFormRepeater = () => {
        $('#kt_ecommerce_add_product_options').repeater({
            initEmpty: false,
            defaultValues: {
                'text-input': 'foo'
            },
            show: function () {
                $(this).slideDown();
                // Init select2 on new repeated items
                initConditionsSelect2();
            },
            hide: function (deleteElement) {
                $(this).slideUp(deleteElement);
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

    // Handle shipping form
    const handleShippingForm = () => {
        // Select elements
        const element = document.getElementById('kt_ecommerce_edit_order_shipping_form');
        const checkbox = document.getElementById('same_as_billing');
        // Show/hide shipping form
        checkbox.addEventListener('change', e => {
            if (e.target.checked) {
                element.classList.remove('d-none');
            } else {
                element.classList.add('d-none');
            }
        });
    }

    // Handle product select
    const handleProductSelect = () => {
        // Define variables
        const checkboxes = table.querySelectorAll('[type="checkbox"]');
        const target = document.getElementById('kt_ecommerce_edit_order_selected_products');
        const totalPrice = document.getElementById('kt_ecommerce_edit_order_total_price');
        const pedido_envio = document.getElementById('kt_ecommerce_edit_order_envio_price');
        const pedido_descuento = document.getElementById('kt_ecommerce_edit_order_descuento_price');
        const pedido_iva = document.getElementById('kt_ecommerce_edit_order_iva_price');
        const pedido_subtotal = document.getElementById('kt_ecommerce_edit_order_subtotal_price');


        //Add descuento
        const input_descuento = document.getElementById('pedido_descuento');
        input_descuento.addEventListener("keyup", function (event) {
            if (event.keyCode == 13) {
                agregar_descuento();
            }
        });
        const agregar_descuento = () => {
            let descuento_value = parseFloat(document.querySelector('[id="pedido_descuento"]').value);
            pedido_descuento.innerText = descuento_value.toFixed(2);
        }

        // Handle empty list message
        const detectEmpty = () => {
            // Select elements
            const message = target.querySelector('span');
            const products = target.querySelectorAll('[data-kt-ecommerce-edit-order-filter="product"]');
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
                calculateTotal();
            }
        }

        // Calculate total cost
        const calculateTotalProductos = (products) => {
            let countPrice = 0;
            const descuento = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_descuento_price"]').innerText);
            const envio = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_envio_price"]').innerText);
            // Loop through all selected prodcucts
            products.forEach(product => {
                // Get product price
                const price = parseFloat(product.querySelector('[data-kt-ecommerce-edit-order-filter="price"]').innerText);
                const cantidad = parseFloat(product.querySelector('[data-input="cantidad"]').value);
                console.log(price);
                console.log(cantidad);
                // Add to total
                countPrice = parseFloat(countPrice + price) * cantidad;
            });
            // Update subtotal price
            console.log(countPrice);
            pedido_subtotal.innerText = countPrice.toFixed(2);
        }

        const calculateTotal = () => {
            const descuento = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_descuento_price"]').innerText);
            const envio = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_envio_price"]').innerText);
            const subtotal = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_subtotal_price"]').innerText);
            let total = subtotal + envio - descuento;
            let iva = total * 0.16;
            totalPrice.innerText = total;
            pedido_iva.innerText = iva.toFixed(2);
        }

        // Loop through all checked products
        checkboxes.forEach(checkbox => {
            if (checkbox.checked == true) {
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
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-tipo-item", product.getAttribute("data-tipo-item"));
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
                input_dialer.setAttribute("readonly", "readonly");
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", product.getAttribute("data-input-cantidad"));
                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
                input_dialer.setAttribute("data-input", "cantidad");
                input_dialer.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                input_dialer.addEventListener('change', e => {
                    detectEmpty();
                });
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
                div_dialer.appendChild(button_dialer_decrease);
                div_dialer.appendChild(input_dialer);
                div_dialer.appendChild(button_dialer_increase);
                //dialer
                product.appendChild(inputProduct);
                product.appendChild(div_dialer);
                if (checkbox.checked == true) {
                    // Add product to selected product wrapper
                    target.appendChild(product);
                    KTDialer.createInstances();
                } else {
                    // Remove product from selected product wrapper
                    const selectedProduct = target.querySelector('[data-kt-ecommerce-edit-order-id="' + productId + '"]');
                    if (selectedProduct) {
                        target.removeChild(selectedProduct);
                    }
                }

                // Trigger empty message logic
                detectEmpty();
            }
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
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                inputProduct.setAttribute("data-tipo-item", product.getAttribute("data-tipo-item"));
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
                input_dialer.setAttribute("readonly", "readonly");
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", "1");
                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
                input_dialer.setAttribute("data-input", "cantidad");
                input_dialer.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                input_dialer.addEventListener('change', e => {
                    detectEmpty();
                });
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
                div_dialer.appendChild(button_dialer_decrease);
                div_dialer.appendChild(input_dialer);
                div_dialer.appendChild(button_dialer_increase);
                //dialer
                product.appendChild(inputProduct);
                product.appendChild(div_dialer);
                if (e.target.checked) {
                    // Add product to selected product wrapper
                    target.appendChild(product);
                    KTDialer.createInstances();
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
            // Validate form before submit
            if (validator) {
                validator.validate().then(function (status) {
                    if (status == 'Valid') {
                        let informacion = informacion_pedido();
                        console.log(informacion);
//            let informacion = {"cliente": {"nombre": "Manuel Alejandro", "apellido_paterno": "Rodríguez", "apellido_materno": "Gutiérrez", "contacto_1": "3336372508", "contado_2": "3322068429", "datos_facturacion": {"facturar": 1, "uso_cfdi": "Gastos en general", "razon_social": "Manuel Alejandro Rodriguez Gutierrez", "rfc": "ROGM9604248P5", "regimen_fiscal": "Personas Fisicas con Actividad Empresarial", "estado": "Jalisco", "ciudad": "Guadalajara", "colonia": "Oblatos", "calle": "Francisco Javier Mina", "numero_exterior": "967", "numero_interior": null, "codigo_postal": "44700"}, "datos_envio": {"calle": "Francisco Javier Mina", "numero_exterior": "967", "numero_interior": null, "colonia": "Guadalajara", "ciudad": "Guadalajara", "estado": "Jalisco", "codigo_postal": "44700", "referencias": "Entre Francisco Sarabia y Avenida de la paz"}}, "pedido": {"productos": [{"id_producto": "597", "codigo_barras": "7501556480252", "codigo_interno": "null", "codigo_proveedor": null, "cantidad": "1", "precio": 1950, "descuento": "0.00", "iva": 312, "subtotal": 1950, "producto": "Jaula amsterdam II para conejo/Huron"}], "descuento": 0, "envio": 45, "iva": 319.2, "total": 1995, "tipo_entrega": {"id_tipo_entrega": "1", "tipo_entrega": "Recoger en tienda", "puntos_entrega": 1}, "horario_entrega": {"fecha_entrega": "2022-11-04", "hora_entrega": "12:00", "turno_horario": "PM"}}, "pagos": [{"metodo": "1", "cantidad": "1995"}]};
                        crear_pedido(informacion);
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
            initFormRepeater();
            initConditionsSelect2();
            initSaveOrder();
            handleSearchDatatable();
            handleShippingForm();
            handleProductSelect();
            handleSubmit();
        }
    };
}();
// On document ready
let INFORMACION_PEDIDO = false;
KTUtil.onDOMContentLoaded(function () {

    let response = venta_a_editar();
    if (response['error'] == false) {
        INFORMACION_PEDIDO = response.depurar;
        ui_datos_venta(response.depurar);
//    ui_proveedores_producto(consulta.depurar.proveedores.depurar);
    }

    let data = "";
    let productos = "";
    let metodos_pago = "";
    let tipos_entrega = "";
    let id_tipo_entrega = 0;
    let pagos = false;
//    if (response.error == false) {
//        id_tipo_entrega = response.depurar.datos_envio.id_tipo_entrega;
//    }
//    console.log(id_tipo_entrega);
//    pagos = response.depurar.pagos ? response.depurar.pagos : false;
//    metodos_pago = metodos_pago_listar(data);
//    metodos_pago = JSON.parse(metodos_pago);
//    console.log(pagos);
//    if (pagos != false) {
//        ui_pagos(pagos, metodos_pago.depurar);
//    } else {
//        ui_select_metodos_pago(metodos_pago.depurar);
//    }

    //datos entrega
//  productos = listar();
//  productos = JSON.parse(productos);
//  ui_pedido_productos(productos.depurar);

//    tipos_entrega = tipos_entrega_listar(data);
//    tipos_entrega = JSON.parse(tipos_entrega);
//    TIPOS_ENTREGA = tipos_entrega.depurar;
//    ui_select_tipos_entrega(tipos_entrega.depurar, id_tipo_entrega);
    KTAppEcommerceSalesSaveOrder.init();
});

async function busqueda_productos(value) {
    let data = {
        busqueda: value
    }
    let respuesta = await consulta_busqueda(data);
    return respuesta;
}

function ui_form_repeater() {
    $('#kt_ecommerce_add_product_options').repeater({
        initEmpty: false,
        defaultValues: {
            'text-input': 'foo'
        },
        show: function () {
            $(this).slideDown();
            // Init select2 on new repeated items
            initConditionsSelect2();
        },
        hide: function (deleteElement) {
            $(this).slideUp(deleteElement);
        }
    });
}

function ui_pagos(pagos, metodos_pago) {

    let cantidad = 0, code = "", opciones_select = '';
    $("#repeat-list-pagos").html("");
    pagos.map(function (pago) {
        console.log(pago);
        console.log(pago.cantidad);
        console.log(pago.id_metodo_pago);
        cantidad = pago.cantidad;
        opciones_select = select_metodos_pago(metodos_pago, pago.id_metodo_pago);
        code = `
        <div data-repeater-item="" class="form-group d-flex flex-wrap align-items-center gap-5 contenedor-pago">
            <!--begin::Select2-->
            <div class="w-100 w-md-200px">
                <select class="form-select select_metodos_pago" data-name="metodos_pago" name="metodos_pago" data-placeholder="Selecciona un metodo" data-kt-ecommerce-catalog-add-product="product_option">        
                ${opciones_select}
                </select>
            </div>
            <!--end::Select2-->
            <!--begin::Input-->
            <input type="text" value="${cantidad}" class="form-control mw-100 w-200px input-cantidad" name="product_option_value" placeholder="Cantidad" />
            <!--end::Input-->
            <button type="button" data-repeater-delete="" class="btn btn-sm btn-icon btn-light-danger">
                <!--begin::Svg Icon | path: icons/duotune/arrows/arr088.svg-->
                <span class="svg-icon svg-icon-1">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect opacity="0.5" x="7.05025" y="15.5356" width="12" height="2" rx="1" transform="rotate(-45 7.05025 15.5356)" fill="currentColor" />
                    <rect x="8.46447" y="7.05029" width="12" height="2" rx="1" transform="rotate(45 8.46447 7.05029)" fill="currentColor" />
                    </svg>
                </span>
                <!--end::Svg Icon-->
            </button>
        </div>
    `;
        $("#repeat-list-pagos").append(code);
    });
}

function select_metodos_pago(data, id_metodo_pago) {
    console.log(data);
    let opciones_metodos_pago = [], opcion = "", opciones = "";
    data.map(function (metodo) {
        opcion = $('<option>', {
            value: metodo.id_metodo_pago,
            text: metodo.metodo_pago
        })[0];
        if (metodo.id_metodo_pago == id_metodo_pago) {
            opcion = $('<option>', {
                value: metodo.id_metodo_pago,
                text: metodo.metodo_pago,
                selected: 'selected'
            })[0];
        }

        opciones += opcion.outerHTML;
    });
    return opciones;
//    rellenar_select(".select_metodos_pago", opciones_metodos_pago);
}

function ui_datos_venta(info) {
    if (info.pedido) {
        //id_datos_envio
        //id_datos_facturacion
        //id_cliente
        //descuento
        //iva
        //envio
        //subtotal
        //total
        ui_pedido(info.pedido);
    }
    if (info.cliente) {
        ui_datos_cliente(info.cliente);
    }
    if (info.datos_envio) {
        ui_datos_envio(info.datos_envio);
    }
    if (info.datos_facturacion) {

    }
    if (info.pagos) {

    }
    if (info.productos) {
        ui_pedido_productos(info.productos);
    }
}

async function ui_pedido_productos(data) {
    let codigo_productos = "";
    data.map(function (producto) {
        codigo_productos += `
        <tr class="odd">
          <!--begin::Checkbox-->
          <td>
              <div class="form-check form-check-sm form-check-custom form-check-solid">
                  <input class="form-check-input input-check-producto" type="checkbox" ${producto.id_pedido ? 'checked="checked"' : ''}>
              </div>
          </td>
          <!--end::Checkbox-->
          <!--begin::Product=-->
          <td>
              <div class="d-flex align-items-center" data-kt-ecommerce-edit-order-filter="product" data-producto-descuento="0.00" data-tipo-item="${producto.tipo_item}" data-producto-iva="${producto.precio_base * 0.16}" data-producto-precio="${producto.precio_base}" data-producto-nombre="${producto.nombre}" data-kt-ecommerce-edit-order-id="${producto.tipo_item == 'paquete' ? producto.id_paquete : producto.id_producto}" data-codigo-interno="${producto.codigo_interno}" data-codigo-proveedor="${producto.sku}" data-codigo-barras="${producto.codigo_barras}" data-input-cantidad="${producto.cantidad ? producto.cantidad : 1}">
                  <!--begin::Thumbnail-->
                  <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="symbol symbol-50px">
                      <span class="symbol-label" style="background-image:url('${producto.url_imagen}');"></span>
                  </a>
                  <!--end::Thumbnail-->
                  <div class="ms-5">
                      <!--begin::Title-->
                      <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="text-gray-800 text-hover-primary fs-5 fw-bold">${producto.nombre}</a>
                      <!--end::Title-->
                      <!--begin::Price-->
                      <div class="fw-semibold fs-7">Price: $
                          <span data-kt-ecommerce-edit-order-filter="price">${producto.precio_base}</span></div>
                      <!--end::Price-->
                      <!--begin::SKU-->
                      <div class="text-muted fs-7">SKU: ${producto.sku}</div>
                      <!--end::SKU-->
                  </div>
              </div>
          </td>
          <!--end::Product=-->
          <!--begin::Qty=-->
          <td class="text-end pe-5" data-order="9">
              <!--<span class="badge badge-light-warning">Low stock</span>-->
              <span class="fw-bold text-warning ms-3">${producto.existencia}</span>
          </td>
          <!--end::Qty=-->
      </tr>
    `;
    });
    $("#pedido_productos").html(codigo_productos);
}

function ui_datos_facturacion(data) {

}

function ui_pedido(data) {
    $("#identificador_pedido").text("#" + data.id_pedido);
    $("#medio_comunicacion").text(data.medio_comunicacion);
    $("#pedido_envio").val(data.envio);
    $("#pedido_descuento").val(data.descuento);
    document.querySelector('[id="kt_ecommerce_edit_order_envio_price"]').innerText = data.envio;
    document.querySelector('[id="kt_ecommerce_edit_order_descuento_price"]').innerText = data.descuento;
    $("#kt_ecommerce_edit_order_iva_price").val(data.iva);

    $("#pedido_envio").trigger("focusout");
    $("#pedido_descuento").trigger("focusout");
}

function ui_datos_cliente(data) {
    $("#cliente_apellido_paterno").val(data.apellido_paterno);
    $("#cliente_apellido_materno").val(data.apellido_materno);
    $("#cliente_nombres").val(data.nombres);
    $("#cliente_contacto_primero").val(data.contacto1);
    $("#cliente_contacto_segundo").val(data.contacto2);
}

function ui_datos_envio(data) {
    $("#pedido_fecha").val(data.fecha);
    $("#pedido_hora").val(data.hora);
    $("#venta_estado").val(data.estado);
    $("#venta_ciudad").val(data.ciudad);
    $("#venta_colonia").val(data.colonia);
    $("#venta_calle").val(data.calle);
    $("#venta_numero_exterior").val(data.numero_exterior);
    $("#venta_numero_interior").val(data.numero_interior);
    $("#venta_codigo_postal").val(data.codigo_postal);
    $("#venta_referencias").val(data.referencias);
}

function venta_a_editar() {
    let id_pedido = obtener_id_venta();
    let data = {
        id_pedido: id_pedido
    }
    let consulta = consultar_venta_editar(data);

    return consulta;
}

function obtener_id_venta() {
    let router = url_router();
    let id_venta = router.parametros[0];
    return id_venta;
}

//Add envio
const input_envio_costo = document.getElementById('pedido_envio');

//input_envio_costo.addEventListener("keyup", function (event) {
//  if (event.keyCode == 13) {
//    agregar_envio();
//  }
//});
//Add descuento
function agregar_descuento() {
    let pedido_descuento = document.getElementById('kt_ecommerce_edit_order_descuento_price');
    let descuento_value = parseFloat(document.querySelector('[id="pedido_descuento"]').value);
    pedido_descuento.innerText = descuento_value ? descuento_value.toFixed(2) : "0.00";
}

function agregar_envio() {
    let pedido_envio = document.getElementById('kt_ecommerce_edit_order_envio_price');
    let envio_value = parseFloat(document.querySelector('[id="pedido_envio"]').value);
    pedido_envio.innerText = envio_value ? envio_value.toFixed(2) : "0.00";
}

function pedido_totales() {
    const subtotal = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_subtotal_price"]').innerText);
    const envio = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_envio_price"]').innerText);
    const descuento = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_descuento_price"]').innerText);
    const iva = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_iva_price"]').innerText);
    const total = parseFloat(document.querySelector('[id="kt_ecommerce_edit_order_total_price"]').innerText);
    let totales = {
        subtotal: subtotal,
        envio: envio,
        descuento: descuento,
        iva: iva,
        total: total
    }
    return totales;
}

function select_puntos_entrega(select) {
    $(select).find('option:selected').val();
    let tipo_entrega = $("#tipos_entrega option:selected").text();
    let id_punto_entrega = $(select).find('option:selected').val();
    let calle = null, colonia = null, ciudad = null, codigo_postal = null, estado = null, numero_exterior = null;
    if (TIPOS_ENTREGA[tipo_entrega].puntos_entrega == 1) {
        TIPOS_ENTREGA[tipo_entrega].puntos.map(function (punto) {
            if (punto.id_punto_entrega = id_punto_entrega) {
                $("#venta_estado").val(punto.estado);
                $("#venta_ciudad").val(punto.ciudad);
                $("#venta_colonia").val(punto.colonia);
                $("#venta_calle").val(punto.calle);
                $("#venta_numero_exterior").val(punto.numero_exterior);
                $("#venta_codigo_postal").val(punto.codigo_postal);
            }
        });
    }


}

function informacion_pedido() {

    let cliente_apellido_paterno = $("#cliente_apellido_paterno").val() ? $("#cliente_apellido_paterno").val() : null;
    let cliente_apellido_materno = $("#cliente_apellido_materno").val() ? $("#cliente_apellido_materno").val() : null;
    let cliente_nombres = $("#cliente_nombres").val() ? $("#cliente_nombres").val() : null;
    let cliente_contacto_primero = $("#cliente_contacto_primero").val() ? $("#cliente_contacto_primero").val() : null;
    let cliente_contacto_segundo = $("#cliente_contacto_segundo").val() ? $("#cliente_contacto_segundo").val() : null;
    let cliente_facturar = $("#same_as_billing")[0].checked == true ? 1 : 0;

    let fiscal_uso_cfdi = $("#fiscal_uso_cfdi").val() ? $("#fiscal_uso_cfdi").val() : null;
    let fiscal_regimen = $("#fiscal_regimen").val() ? $("#fiscal_regimen").val() : null;
    let fiscal_rfc = $("#fiscal_rfc").val() ? $("#fiscal_rfc").val() : null;
    let fiscal_razon_social = $("#fiscal_razon_social").val() ? $("#fiscal_razon_social").val() : null;
    let fiscal_estado = $("#fiscal_estado").val() ? $("#fiscal_estado").val() : null;
    let fiscal_ciudad = $("#fiscal_ciudad").val() ? $("#fiscal_ciudad").val() : null;
    let fiscal_colonia = $("#fiscal_colonia").val() ? $("#fiscal_colonia").val() : null;
    let fiscal_calle = $("#fiscal_calle").val() ? $("#fiscal_calle").val() : null;
    let fiscal_numero_exterior = $("#fiscal_numero_exterior").val() ? $("#fiscal_numero_exterior").val() : null;
    let fiscal_numero_interior = $("#fiscal_numero_interior").val() ? $("#fiscal_numero_interior").val() : null;
    let fiscal_codigo_postal = $("#fiscal_codigo_postal").val() ? $("#fiscal_codigo_postal").val() : null;

    let envio_estado = $("#venta_estado").val() ? $("#venta_estado").val() : null;
    let envio_ciudad = $("#venta_ciudad").val() ? $("#venta_ciudad").val() : null;
    let envio_colonia = $("#venta_colonia").val() ? $("#venta_colonia").val() : null;
    let envio_calle = $("#venta_calle").val() ? $("#venta_calle").val() : null;
    let envio_numero_exterior = $("#venta_numero_exterior").val() ? $("#venta_numero_exterior").val() : null;
    let envio_numero_interior = $("#venta_numero_interior").val() ? $("#venta_numero_interior").val() : null;
    let envio_codigo_postal = $("#venta_codigo_postal").val() ? $("#venta_codigo_postal").val() : null;
    let envio_referencias = $("#venta_referencias").val() ? $("#venta_referencias").val() : null;

    let venta_estado = $("#venta_estado").val() ? $("#venta_estado").val() : null;
    let venta_ciudad = $("#venta_ciudad").val() ? $("#venta_ciudad").val() : null;
    let venta_colonia = $("#venta_colonia").val() ? $("#venta_colonia").val() : null;
    let venta_calle = $("#venta_calle").val() ? $("#venta_calle").val() : null;
    let venta_numero_exterior = $("#venta_numero_exterior").val() ? $("#venta_numero_exterior").val() : null;
    let venta_numero_interior = $("#venta_numero_interior").val() ? $("#venta_numero_interior").val() : null;
    let venta_codigo_postal = $("#venta_codigo_postal").val() ? $("#venta_codigo_postal").val() : null;
    let venta_referencias = $("#venta_referencias").text() ? $("#venta_referencias").text() : null;

    let productos = obtener_productos_venta();
    let pagos = obtener_pagos();
    let id_puntos_entrega = $("#puntos_entrega opcion:selected").val() != -1 ? $("#puntos_entrega opcion:selected").val() : 0;
    let id_tipo_entrega = $("#tipos_entrega option:selected").val() != -1 ? $("#tipos_entrega option:selected").val() : 0;
    let tipo_entrega = id_tipo_entrega != -1 && id_tipo_entrega ? $("#tipos_entrega option:selected").text() : null;
    let puntos_entrega = tipo_entrega != null ? TIPOS_ENTREGA[tipo_entrega].puntos_entrega : 0;
    let fecha = $("#pedido_fecha").val() ? $("#pedido_fecha").val() : null;
    let hora_entrega = obtener_hora_entrega($("#pedido_hora").val());
    let hora = hora_entrega.hora;
    let turno = hora_entrega.turno;

    //Totales
    let totales = pedido_totales();
    let envio = totales.envio;
    let subtotal = totales.subtotal;
    let descuento = totales.descuento;
    let iva = totales.iva;
    let total = totales.total;

    //ids 
    let id_datos_facturacion = 0;
    let id_datos_envio = 0;
    let id_pedido = 0;
    let id_cliente = 0;

    if (INFORMACION_PEDIDO != false) {
        id_datos_facturacion = INFORMACION_PEDIDO.pedido.id_datos_facturacion;
        id_datos_envio = INFORMACION_PEDIDO.pedido.id_datos_envio;
        id_pedido = INFORMACION_PEDIDO.pedido.id_pedido;
        id_cliente = INFORMACION_PEDIDO.pedido.id_cliente;
    }
    let data = {
        "cliente": {
            "id_cliente": id_cliente,
            "nombre": cliente_nombres,
            "apellido_paterno": cliente_apellido_paterno,
            "apellido_materno": cliente_apellido_materno,
            "contacto_1": cliente_contacto_primero,
            "contado_2": cliente_contacto_segundo,
            "datos_facturacion": {
                "id_datos_facturacion": id_datos_facturacion,
                "facturar": cliente_facturar,
                "uso_cfdi": fiscal_uso_cfdi,
                "razon_social": "Manuel Alejandro Rodriguez Gutierrez",
                "rfc": "ROGM9604248P5",
                "regimen_fiscal": fiscal_regimen,
                "estado": fiscal_estado,
                "ciudad": fiscal_ciudad,
                "colonia": fiscal_colonia,
                "calle": fiscal_calle,
                "numero_exterior": fiscal_numero_exterior,
                "numero_interior": fiscal_numero_interior,
                "codigo_postal": fiscal_codigo_postal
            },
            "datos_envio": {
                "id_datos_envio": id_datos_envio,
                "fecha": fecha,
                "calle": venta_calle,
                "numero_exterior": venta_numero_exterior,
                "numero_interior": venta_numero_interior,
                "colonia": venta_colonia,
                "ciudad": envio_ciudad,
                "estado": envio_estado,
                "codigo_postal": venta_codigo_postal,
                "referencias": venta_referencias
            }

        },
        "pedido": {
            "id_pedido": id_pedido,
            "productos": productos,
            "descuento": descuento,
            "envio": envio,
            "subtotal": subtotal,
            "total": total,
            "tipo_entrega": {
                "id_tipo_entrega": id_tipo_entrega,
                "tipo_entrega": tipo_entrega,
                "puntos_entrega": puntos_entrega
            },
            "horario_entrega": {
                "fecha_entrega": fecha,
                "hora_entrega": hora,
                "turno_horario": turno
            }
        },
        "pagos": pagos
    };
    return data;
}

function obtener_hora_entrega(string) {
    let paragraph = '12:00 AM';
    let horario_entrega = {
        hora: 0,
        turno: null
    };
// any character that is not a word character or whitespace
    const regex = /([01]\d|2[0-3])/g;
    let indice = string.search(regex);
//  console.log(string.substring(indice, indice + 5));
    let hora = string.substring(indice, indice + 5).trim();
    if (hora) {
        let turno = string.substring(indice + 5).trim();
        horario_entrega = {
            hora: hora,
            turno: turno
        };
    }
    return horario_entrega;
}

function obtener_pagos() {
    let pagos = [];
    let cantidad = "";
    let metodo_pago = "";
    if ($("select[data-name='metodos_pago']")) {
        $("select[data-name='metodos_pago']").each(function () {
            metodo_pago = $(this).find('option:selected').val() ? $(this).find('option:selected').val() : null;
            cantidad = obtener_cantidad_pago(this);
            pagos.push({
                metodo: metodo_pago,
                cantidad: cantidad
            })
        })
    }
    return pagos;
}

function obtener_cantidad_pago(elemento) {
    let cantidad = $(elemento).parent().parent().children()[1].value ? $(elemento).parent().parent().children()[1].value : 0;
    return cantidad;
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

            cantidad = $("#pedido-cantidad-" + this.value).val();
            precio = parseFloat(this.getAttribute("data-producto-precio"));
            subtotal = cantidad && precio ? parseInt(cantidad) * precio : 0.00;
            iva = subtotal ? subtotal * 0.16 : 0.00;
            productos.push({
                id_producto: this.value,
                codigo_barras: this.getAttribute("data-codigo-barras") ? this.getAttribute("data-codigo-barras") : null,
                codigo_interno: this.getAttribute("data-codigo-interno") ? this.getAttribute("data-codigo-interno") : null,
                codigo_proveedor: this.getAttribute("data-producto-proveedor") ? this.getAttribute("data-producto-proveedor") : null,
                cantidad: cantidad,
                tipo_item: this.getAttribute("data-tipo-item") ? this.getAttribute("data-tipo-item") : null,
                precio: precio,
                descuento: this.getAttribute("data-producto-descuento") ? this.getAttribute("data-producto-descuento") : 0.00,
                iva: iva,
                subtotal: subtotal,
                producto: this.getAttribute("data-producto-nombre"),
            })
        });
    }

    return productos;
}

function ui_select_tipos_entrega(data, id_tipo_entrega) {
    let opciones_tipos_entrega = [];
    opciones_tipos_entrega.push({
        value: -1,
        text: "Seleccionar tipo de entrega"
    });
    for (var clave in data) {
        // Controlando que json realmente tenga esa propiedad
//    if (json.hasOwnProperty(clave)) {
        // Mostrando en pantalla la clave junto a su valor
//      alert("La clave es " + clave + " y el valor es " + json[clave]);
        if (id_tipo_entrega != 0 && id_tipo_entrega == data[clave].id_tipo_entrega) {
            opciones_tipos_entrega.push({
                value: data[clave].id_tipo_entrega,
                text: data[clave].tipo_entrega,
                selected: "selected"
            });
        } else {
            opciones_tipos_entrega.push({
                value: data[clave].id_tipo_entrega,
                text: data[clave].tipo_entrega
            });
        }

//    }
    }
//  data.map(function (tipo) {
//    opciones_tipos_entrega.push({
//      value: tipo.id_tipo_entrega,
//      text: tipo.tipo_entrega
//    });
//  });
    rellenar_select("#tipos_entrega", opciones_tipos_entrega);
}

$("#tipos_entrega").change(function () {
//  if(TIPOS_ENTREGA[$("#tipos_entrega option:selected").val()].puntos_entrega)
    let opciones_puntos = [];
    let tipo_entrega = TIPOS_ENTREGA[$("#tipos_entrega option:selected").text()];
    let puntos_entrega = tipo_entrega.puntos;
    opciones_puntos.push({
        value: -1,
        text: "Seleccionar punto de entrega"
    });
    if (tipo_entrega.puntos_entrega == 1) {
        puntos_entrega.map(function (punto) {
            opciones_puntos.push({
                value: punto.id_punto_entrega,
                text: punto.descripcion
            });
        });
        rellenar_select("#puntos_entrega", opciones_puntos);
        $("#puntos_entrega").removeClass("d-none");
    } else {
        $("#puntos_entrega").addClass("d-none");
    }
    ui_select2("#puntos_entrega");
});


function ui_select2(identificador) {
    $(identificador).select2({
    });
}

function ui_select_metodos_pago(data) {
    let opciones_metodos_pago = [];
    data.map(function (metodo) {
        opciones_metodos_pago.push({
            value: metodo.id_metodo_pago,
            text: metodo.metodo_pago
        });
    });
    rellenar_select(".select_metodos_pago", opciones_metodos_pago);
}

//function ui_pedido_productos(data) {
////  console.log(data);
//  let codigo_productos = "";
//  data.map(function (producto) {
//    codigo_productos += `
//        <tr class="odd">
//          <!--begin::Checkbox-->
//          <td>
//              <div class="form-check form-check-sm form-check-custom form-check-solid">
//                  <input class="form-check-input" type="checkbox" checked="checked">
//              </div>
//          </td>
//          <!--end::Checkbox-->
//          <!--begin::Product=-->
//          <td>
//              <div class="d-flex align-items-center" data-kt-ecommerce-edit-order-filter="product" data-producto-descuento="0.00" data-producto-iva="${producto.precio_base * 0.16}" data-producto-precio="${producto.precio_base}" data-producto-nombre="${producto.nombre}" data-kt-ecommerce-edit-order-id="${producto.id_producto}" data-codigo-interno="${producto.codigo_interno}" data-codigo-proveedor="${producto.sku}" data-codigo-barras="${producto.codigo_barras}">
//                  <!--begin::Thumbnail-->
//                  <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="symbol symbol-50px">
//                      <span class="symbol-label" style="background-image:url('${producto.url_imagen}');"></span>
//                  </a>
//                  <!--end::Thumbnail-->
//                  <div class="ms-5">
//                      <!--begin::Title-->
//                      <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="text-gray-800 text-hover-primary fs-5 fw-bold">${producto.nombre}</a>
//                      <!--end::Title-->
//                      <!--begin::Price-->
//                      <div class="fw-semibold fs-7">Price: $
//                          <span data-kt-ecommerce-edit-order-filter="price">${producto.precio_base}</span></div>
//                      <!--end::Price-->
//                      <!--begin::SKU-->
//                      <div class="text-muted fs-7">SKU: ${producto.sku}</div>
//                      <!--end::SKU-->
//                  </div>
//              </div>
//          </td>
//          <!--end::Product=-->
//          <!--begin::Qty=-->
//          <td class="text-end pe-5" data-order="9">
//              <!--<span class="badge badge-light-warning">Low stock</span>-->
//              <span class="fw-bold text-warning ms-3">${producto.existencia}</span>
//          </td>
//          <!--end::Qty=-->
//      </tr>
//    `;
//  });
//  $("#pedido_productos").html(codigo_productos);
//}

function obtener_informacion(idForm) {
    let form = $('#' + idForm).serializeObject();
    return form;
}

function crear_pedido(data) {


//se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/ventas/actualizar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
        }
    })

}

$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined)
        {
            if (!o[this.name].push)
            {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else
        {
            o[this.name] = this.value || '';
        }
    });
    return o;
};
