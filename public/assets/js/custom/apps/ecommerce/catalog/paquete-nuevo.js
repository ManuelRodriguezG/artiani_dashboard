"use strict";
$(document).ready(function () {
//    let respuesta_categorias = consultar_categorias();
//    ui_categorias(respuesta_categorias.respuesta);
});



function ui_categorias(datos) {
    $("#categorias").html("");
    datos.map(function (elemento) {
        console.log(elemento);
        $("#categorias").append('<option value="' + elemento.id_categoria + '">' + elemento.categoria + '</option>');
    })
}

// Class definition
var KTAppEcommerceSaveProduct = function () {

    // Shared variables
    var table;
    var datatable;

    // Private functions
    const initProductsTable = () => {

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
    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatable = () => {
        const filterSearch = document.querySelector('[data-kt-ecommerce-edit-order-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
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
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-producto-existencia", product.getAttribute("data-producto-existencia"));
                inputProduct.setAttribute("data-unidad-venta", product.getAttribute("data-unidad-venta"));
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
                if (unidad_venta == 1) {
//                    input_dialer.setAttribute("readonly", "readonly");
                }
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", "1");
//                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
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
                    if (unidad_venta == 1) {
//                        KTDialer.createInstances();
                    }
//                    KTDialer.createInstances();
                } else {
                    // Remove product from selected product wrapper
                    const selectedProduct = target.querySelector('[data-kt-ecommerce-edit-order-id="' + productId + '"]');
                    if (selectedProduct) {
                        target.removeChild(selectedProduct);
                    }
                }
            });
        });

    }

    // Init quill editor
    const initQuill = () => {
        // Define all elements for quill editor
        const elements = [
            '#kt_ecommerce_add_package_description',
            '#kt_ecommerce_add_product_meta_description'
        ];

        // Loop all elements
        elements.forEach(element => {
            // Get quill element
            let quill = document.querySelector(element);

            // Break if element not found
            if (!quill) {
                return;
            }

            // Init quill --- more info: https://quilljs.com/docs/quickstart/
            quill = new Quill(element, {
                modules: {
                    toolbar: [
                        [{
                                header: [1, 2, false]
                            }],
                        ['bold', 'italic', 'underline'],
                        ['image', 'code-block']
                    ]
                },
                placeholder: 'Type your text here...',
                theme: 'snow' // or 'bubble'
            });
        });
    }

    // Init tagify
    const initTagify = () => {
        // Define all elements for tagify
        const elements = [
            '#kt_ecommerce_add_product_category',
            '#kt_ecommerce_add_product_tags'
        ];

        // Loop all elements
        elements.forEach(element => {
            // Get tagify element
            const tagify = document.querySelector(element);

            // Break if element not found
            if (!tagify) {
                return;
            }

            // Init tagify --- more info: https://yaireo.github.io/tagify/
            new Tagify(tagify, {
                whitelist: ["new", "trending", "sale", "discounted", "selling fast", "last 10"],
                dropdown: {
                    maxItems: 20, // <- mixumum allowed rendered suggestions
                    classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                    enabled: 0, // <- show suggestions on focus
                    closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
                }
            });
        });
    }

    // Init form repeater --- more info: https://github.com/DubFriend/jquery.repeater
    const initFormRepeater = () => {
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

    // Init condition select2
    const initConditionsSelect2 = () => {
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

    // Init DropzoneJS --- more info:
    const initDropzone = () => {
        var myDropzone = new Dropzone("#kt_ecommerce_add_product_media", {
            url: "https://keenthemes.com/scripts/void.php", // Set the url for your upload script location
            paramName: "file", // The name that will be used to transfer the file
            maxFiles: 10,
            maxFilesize: 10, // MB
            addRemoveLinks: true,
            accept: function (file, done) {
                if (file.name == "wow.jpg") {
                    done("Naha, you don't.");
                } else {
                    done();
                }
            }
        });
    }

    // Handle discount options
    const handleDiscount = () => {
        const discountOptions = document.querySelectorAll('input[name="discount_option"]');
        const percentageEl = document.getElementById('kt_ecommerce_add_product_discount_percentage');
        const fixedEl = document.getElementById('kt_ecommerce_add_product_discount_fixed');

        discountOptions.forEach(option => {
            option.addEventListener('change', e => {
                const value = e.target.value;

                switch (value) {
                    case '2':
                    {
                        percentageEl.classList.remove('d-none');
                        fixedEl.classList.add('d-none');
                        break;
                    }
                    case '3':
                    {
                        percentageEl.classList.add('d-none');
                        fixedEl.classList.remove('d-none');
                        break;
                    }
                    default:
                    {
                        percentageEl.classList.add('d-none');
                        fixedEl.classList.add('d-none');
                        break;
                    }
                }
            });
        });
    }

    // Shipping option handler
    const handleShipping = () => {
        const shippingOption = document.getElementById('kt_ecommerce_add_product_shipping_checkbox');
        const shippingForm = document.getElementById('kt_ecommerce_add_product_shipping');

        shippingOption.addEventListener('change', e => {
            const value = e.target.checked;

            if (value) {
                shippingForm.classList.remove('d-none');
            } else {
                shippingForm.classList.add('d-none');
            }
        });
    }

    // Category status handler
    const handleStatus = () => {
        const target = document.getElementById('kt_ecommerce_add_product_status');
        const select = document.getElementById('kt_ecommerce_add_product_status_select');
        const statusClasses = ['bg-success', 'bg-warning', 'bg-danger'];

        $(select).on('change', function (e) {
            const value = e.target.value;

            switch (value) {
                case "published":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-success');
                    hideDatepicker();
                    break;
                }
                case "scheduled":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-warning');
                    showDatepicker();
                    break;
                }
                case "inactive":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-danger');
                    hideDatepicker();
                    break;
                }
                case "draft":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-primary');
                    hideDatepicker();
                    break;
                }
                default:
                    break;
            }
        });


        // Handle datepicker
        const datepicker = document.getElementById('kt_ecommerce_add_product_status_datepicker');

        // Init flatpickr --- more info: https://flatpickr.js.org/
        $('#kt_ecommerce_add_product_status_datepicker').flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });

        const showDatepicker = () => {
            datepicker.parentNode.classList.remove('d-none');
        }

        const hideDatepicker = () => {
            datepicker.parentNode.classList.add('d-none');
        }
    }

    // Condition type handler
    const handleConditions = () => {
        const allConditions = document.querySelectorAll('[name="method"][type="radio"]');
        const conditionMatch = document.querySelector('[data-kt-ecommerce-catalog-add-category="auto-options"]');
        allConditions.forEach(radio => {
            radio.addEventListener('change', e => {
                if (e.target.value === '1') {
                    conditionMatch.classList.remove('d-none');
                } else {
                    conditionMatch.classList.add('d-none');
                }
            });
        })
    }

    // Submit form handler
    const handleSubmit = () => {
        // Define variables
        let validator;

        // Get elements
        const form = document.getElementById('kt_ecommerce_add_product_form');
        const submitButton = document.getElementById('kt_ecommerce_add_product_submit');

        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validator = FormValidation.formValidation(
                form,
                {
                    fields: {
                        'paquete_nombre': {
                            validators: {
                                notEmpty: {
                                    message: 'Nombre del producto es requerido'
                                }
                            }
                        },
                        'codigo_unico': {
                            validators: {
                                notEmpty: {
                                    message: 'Código único es requerido'
                                }
                            }
                        },
                        'codigo_interno': {
                            validators: {
                                notEmpty: {
                                    message: 'Código proveedor es requerido'
                                }
                            }
                        },
                        'precio_base': {
                            validators: {
                                notEmpty: {
                                    message: 'Precio base es requerido'
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
                validator.validate().then(async function (status) {
                    console.log('validated!');

                    if (status == 'Valid') {
                        let informacion = obtener_informacion();
                        console.log(informacion);
                        console.log(informacion.productos.length);
                        if (informacion.productos.length > 0) {
                            let response = crear_paquete(informacion);
                            if (response.error == false) {
                                if ($("#avatar")[0].files.length > 0) {
                                    let id_paquete = response.depurar;
                                    let imgPortada = document.getElementById('avatar').files[0];
                                    let formSubirPortada = new FormData();
                                    formSubirPortada.append('portada', imgPortada);
                                    formSubirPortada.append('id_paquete', id_paquete);
                                    formSubirPortada.append('tipo_imagen', 'portada');
                                    let respuesta = await actualizar_paquete_producto_portada(formSubirPortada);
                                    console.log(respuesta);
                                    if (respuesta.error == false) {
                                        Swal.fire({
                                            text: respuesta.mensaje,
                                            icon: "success",
                                            buttonsStyling: false,
                                            confirmButtonText: "Perfecto!",
                                            customClass: {
                                                confirmButton: "btn btn-primary"
                                            }
                                        }).then(function (result) {
                                            if (result.isConfirmed) {
                                                // Enable submit button after loading
                                                submitButton.disabled = false;

                                                // Redirect to customers list page
//                  window.location = form.getAttribute("data-kt-redirect");
                                            }
                                        });
                                    }
                                } else {
                                    submitButton.removeAttribute('data-kt-indicator');
                                    Swal.fire({
                                        text: response.mensaje,
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Perfecto!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    }).then(function (result) {
                                        if (result.isConfirmed) {
                                            // Enable submit button after loading
                                            submitButton.disabled = false;

                                            // Redirect to customers list page
//                  window.location = form.getAttribute("data-kt-redirect");
                                        }
                                    });
                                }
                            } else {
                                submitButton.removeAttribute('data-kt-indicator');
                                Swal.fire({
                                    text: response.mensaje,
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Perfecto!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                }).then(function (result) {
                                    if (result.isConfirmed) {
                                        // Enable submit button after loading
                                        submitButton.disabled = false;

                                        // Redirect to customers list page
//                  window.location = form.getAttribute("data-kt-redirect");
                                    }
                                });
                            }
                        } else {
                            Swal.fire({
                                html: "Productos no seleccionados",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Intentar nuevamente!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            });
                        }
                    } else {
                        Swal.fire({
                            html: "Sorry, looks like there are some errors detected, please try again. <br/><br/>Please note that there may be errors in the <strong>General</strong> or <strong>Advanced</strong> tabs",
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
            initProductsTable();
            handleSearchDatatable();
            handleProductSelect();
            // Init forms
            initQuill();
            initTagify();
//            initSlider();
            initFormRepeater();
            initDropzone();
            initConditionsSelect2();

            // Handle forms
            handleStatus();
            handleConditions();
            handleDiscount();
            handleShipping();
            handleSubmit();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    let data = "";
    let productos = "";
    productos = listar();
    productos = JSON.parse(productos);
    ui_pedido_productos(productos.depurar);
    KTAppEcommerceSaveProduct.init();
});

function obtener_productos_paquete() {
    let productos = [], subtotal = 0, iva = 0, cantidad = 0, precio = 0;
    if ($(".producto-selected")) {
        $(".producto-selected").each(function (item) {
            console.log(item);
            console.log(this);
            console.log($(this));
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
                existencia: this.getAttribute("data-producto-existencia") ? this.getAttribute("data-producto-existencia") : null,
                codigo_barras: this.getAttribute("data-codigo-barras") ? this.getAttribute("data-codigo-barras") : null,
                codigo_interno: this.getAttribute("data-codigo-interno") ? this.getAttribute("data-codigo-interno") : null,
                codigo_proveedor: this.getAttribute("data-producto-proveedor") ? this.getAttribute("data-producto-proveedor") : null,
                cantidad: cantidad,
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

function ui_pedido_productos(data) {
//  console.log(data);
    let codigo_productos = "";
    data.map(function (producto) {
        codigo_productos += `
        <tr class="odd">
          <!--begin::Checkbox-->
          <td>
              <div class="form-check form-check-sm form-check-custom form-check-solid">
                  <input class="form-check-input input-check-producto" type="checkbox">
              </div>
          </td>
          <!--end::Checkbox-->
          <!--begin::Product=-->
          <td>
              <div class="d-flex align-items-center" data-kt-ecommerce-edit-order-filter="product" data-producto-descuento="0.00" ${producto.id_unidad_venta ? 'data-unidad-venta="' + producto.id_unidad_venta + '"' : 'data-unidad-venta="1"'} ${producto.id_unidad_venta ? 'data-unidad-venta-abreviatura="' + producto.abreviatura + '"' : ''} data-producto-existencia="${producto.existencia}" data-producto-iva="${producto.precio_base * 0.16}" data-producto-precio="${producto.precio_base}" data-producto-nombre="${producto.nombre}" data-kt-ecommerce-edit-order-id="${producto.id_producto}" data-codigo-interno="${producto.codigo_interno}" data-codigo-proveedor="${producto.sku}" data-codigo-barras="${producto.codigo_barras}">
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

function obtener_informacion() {
    let quill = new Quill("#kt_ecommerce_add_package_description");
    let descripcion_quill = quill.root.innerHTML;
    let paquete_nombre = $("#paquete_nombre").val();
    let descripcion = descripcion_quill ? descripcion_quill : null;
    let siempre_disponible = $("#siempre_disponible")[0].checked == true ? 1 : 0;
    let precio_base = $("#precio_base").val() ? $("#precio_base").val() : 0;
    let codigo_unico = $("#codigo_unico").val() ? $("#codigo_unico").val() : null;
    let codigo_interno = $("#codigo_interno").val() ? $("#codigo_interno").val() : null;
    let codigo_barras = $("#codigo_barras").val() ? $("#codigo_barras").val() : null;
    let cantidad = $("#cantidad").val() ? $("#cantidad").val() : 0;
    let productos = obtener_productos_paquete();

    let informacion = {
        paquete_nombre: paquete_nombre,
        descripcion: descripcion,
        siempre_disponible: siempre_disponible,
        precio_base: precio_base,
        codigo_unico: codigo_unico,
        codigo_interno: codigo_interno,
        codigo_barras: codigo_barras,
        cantidad: cantidad,
        productos
    };
    return informacion;
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