"use strict";
let UNIDADES_COMPRA_VENTA = false;
$(document).ready(function () {

    let consulta_unidades_compra_venta = consultar_unidades_compra_venta();
    if (consulta_unidades_compra_venta.error == false) {
        UNIDADES_COMPRA_VENTA = consulta_unidades_compra_venta.depurar;
        ui_unidades_compra();
    }

    let respuesta_categorias = consultar_categorias();
    ui_categorias(respuesta_categorias.depurar);
    let proveedores = proveedor_listar();
    proveedores = JSON.parse(proveedores);
    if (proveedores.error == false) {
        ui_select_proveedores(proveedores.depurar);
    }
});

function ui_unidades_compra() {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar Unidad compra"});
    $("#unidad_compra").html("");
    $("#unidad_compra").append(opcion);
    Object.entries(UNIDADES_COMPRA_VENTA).forEach((unidad_compra) => {
        opcion = crear_opcion({value: unidad_compra[1].id_unidad_compra, text: unidad_compra[1].abreviatura + "-" + unidad_compra[1].unidad_compra});
        $("#unidad_compra").append(opcion);
    });

//    UNIDADES_COMPRA_VENTA.map(function (unidad_compra) {
//        opcion = crear_opcion({value: unidad_compra.id_unidad_compra, text: unidad_compra.abreviatura + " " + unidad_compra.unidad_compra});
//        $("#unidad_compra").append(opcion);
//    });
}

function ui_unidades_venta() {
    let opcion = "";
    let id_unidad_compra = $('#unidad_compra option:selected').val();
    $("#unidad_venta").html("");
    Object.entries(UNIDADES_COMPRA_VENTA).forEach((unidad_compra) => {
        if (unidad_compra[1].id_unidad_compra == id_unidad_compra) {
            Object.entries(unidad_compra[1].unidades_venta).forEach((unidad_venta) => {
                opcion = crear_opcion({value: unidad_venta[0], text: unidad_venta[1].abreviatura + "-" + unidad_venta[1].unidad_venta});
                $("#unidad_venta").append(opcion);
            });
        }

    });
    ui_factor_compra(id_unidad_compra);
}

function ui_factor_compra(id_unidad_compra) {
    if (id_unidad_compra == 2) {
        $("#factor_compra").removeAttr("disabled");
        $("#solo_en_punto_de_venta")[0].checked = true;
    } else {
        $("#factor_compra").val(1);
        $("#factor_compra").attr("disabled", "disabled");
        $("#solo_en_punto_de_venta")[0].checked = false;
    }
}

function ui_select_proveedores(data_proveedores) {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar proveedor"});
    $("#proveedores").html("");
    $("#proveedores").append(opcion);
    data_proveedores.map(function (proveedor) {
        opcion = crear_opcion({value: proveedor.id_proveedor, text: proveedor.proveedor});
        $("#proveedores").append(opcion);
    });
}

function crear_opcion(opciones) {
    let opcion = $('<option>', opciones)[0];
    return opcion;
}

function ui_categorias(datos) {
    $("#categorias").html("");
    datos.map(function (elemento) {
        console.log(elemento);
        $("#categorias").append('<option value="' + elemento.id_categoria + '">' + elemento.categoria + '</option>');
    })
}

// Class definition
var KTAppEcommerceSaveProduct = function () {

    // Private functions

    // Init quill editor
    const initQuill = () => {
        // Define all elements for quill editor
        const elements = [
            '#kt_ecommerce_add_product_description',
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


    // Init noUIslider
//    const initSlider = () => {
//        var slider = document.querySelector("#kt_ecommerce_add_product_discount_slider");
//        var value = document.querySelector("#kt_ecommerce_add_product_discount_label");
//
//        noUiSlider.create(slider, {
//            start: [10],
//            connect: true,
//            range: {
//                "min": 1,
//                "max": 100
//            }
//        });
//
//        slider.noUiSlider.on("update", function (values, handle) {
//            value.innerHTML = Math.round(values[handle]);
//            if (handle) {
//                value.innerHTML = Math.round(values[handle]);
//            }
//        });
//    }

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
                        'producto_nombre': {
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
//                        let compra_venta = {
//                            unidad_compra: $("#unidad_compra option:selected").val(),
//                            unidad_venta: $("#unidad_venta option:selected").val(),
//                            factor: $("#factor_compra").val()
//                        };
                        console.log(informacion);
                        if (informacion.compra_venta.unidad_compra != -1 && informacion.compra_venta.unidad_venta != -1 && informacion.compra_venta.factor != 0 && informacion.compra_venta.factor) {
                            let response = crear_producto(informacion);
                            if (response.error == false) {
                                let id_producto = response.depurar;
                                if ($("#avatar")[0].files.length > 0) {
                                    let imgPortada = document.getElementById('avatar').files[0];
                                    let formSubirPortada = new FormData();
                                    formSubirPortada.append('portada', imgPortada);
                                    formSubirPortada.append('id_producto', id_producto);
                                    formSubirPortada.append('tipo_imagen', 'portada');
                                    let respuesta = await actualizar_producto_portada(formSubirPortada);
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

                                        }
                                    });
                                }
                            } else {
                                Swal.fire({
                                    text: response.mensaje,
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                });
                            }
                        } else {
                            Swal.fire({
                                html: "Valores de compra venta incompletos",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
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
            handleSubmit();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTAppEcommerceSaveProduct.init();
});



function obtener_informacion() {
    let quill = new Quill("#kt_ecommerce_add_product_description");
    let descripcion_quill = quill.root.innerHTML;
    let producto_nombre = $("#producto_nombre").val();
    let descripcion = descripcion_quill ? descripcion_quill : null;
    let siempre_disponible = $("#siempre_disponible")[0].checked == true ? 1 : 0;
    let precio_base = $("#precio_base").val() ? $("#precio_base").val() : 0;
    let codigo_unico = $("#codigo_unico").val() ? $("#codigo_unico").val() : null;
    let codigo_interno = $("#codigo_interno").val() ? $("#codigo_interno").val() : null;
    let codigo_barras = $("#codigo_barras").val() ? $("#codigo_barras").val() : null;
    let existencia = $("#existencia").val() ? $("#existencia").val() : 0;
    let minima_existencia = $("#minima_existencia").val() ? $("#minima_existencia").val() : 0;
    let maxima_existencia = $("#maxima_existencia").val() ? $("#maxima_existencia").val() : 0;
    let proveedores = $("#proveedores").val();
    let categorias = $("#categorias").val();
    let compra_venta = {
        unidad_compra: $("#unidad_compra option:selected").val(),
        unidad_venta: $("#unidad_venta option:selected").val(),
        solo_en_punto_de_venta: $("#solo_en_punto_de_venta")[0].checked == true ? 1 : 0,
        factor: $("#factor_compra").val()
    };
    let informacion = {
        producto_nombre: producto_nombre,
        descripcion: descripcion,
        siempre_disponible: siempre_disponible,
        precio_base: precio_base,
        codigo_unico: codigo_unico,
        codigo_interno: codigo_interno,
        codigo_barras: codigo_barras,
        existencia: existencia,
        minima_existencia: minima_existencia,
        maxima_existencia: maxima_existencia,
        proveedores: proveedores,
        categorias: categorias,
        compra_venta
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