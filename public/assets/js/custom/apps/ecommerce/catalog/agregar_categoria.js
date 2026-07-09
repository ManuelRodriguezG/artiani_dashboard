"use strict";
let UNIDADES_COMPRA_VENTA = false;
$(document).ready(function () {

});



function crear_opcion(opciones) {
    let opcion = $('<option>', opciones)[0];
    return opcion;
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


    // Init DropzoneJS --- more info:
//    const initDropzone = () => {
//        var myDropzone = new Dropzone("#kt_ecommerce_add_product_media", {
//            url: "https://keenthemes.com/scripts/void.php", // Set the url for your upload script location
//            paramName: "file", // The name that will be used to transfer the file
//            maxFiles: 10,
//            maxFilesize: 10, // MB
//            addRemoveLinks: true,
//            accept: function (file, done) {
//                if (file.name == "wow.jpg") {
//                    done("Naha, you don't.");
//                } else {
//                    done();
//                }
//            }
//        });
//    }

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
//    const handleShipping = () => {
//        const shippingOption = document.getElementById('kt_ecommerce_add_product_shipping_checkbox');
//        const shippingForm = document.getElementById('kt_ecommerce_add_product_shipping');
//
//        shippingOption.addEventListener('change', e => {
//            const value = e.target.checked;
//
//            if (value) {
//                shippingForm.classList.remove('d-none');
//            } else {
//                shippingForm.classList.add('d-none');
//            }
//        });
//    }

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
                            let response = crear_categoria(informacion);
                            if (response.error == false) {
                                let id_producto = response.depurar;
                                if ($("#avatar")[0].files.length > 0) {
                                    let imgPortada = document.getElementById('avatar').files[0];
                                    let formSubirPortada = new FormData();
                                    formSubirPortada.append('portada', imgPortada);
                                    formSubirPortada.append('id_categoria', id_producto);
                                    formSubirPortada.append('tipo_imagen', 'portada');
                                    let respuesta = await actualizar_categoria_portada(formSubirPortada);
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
//            initDropzone();
            initConditionsSelect2();

            // Handle forms
            handleStatus();
            handleConditions();
            handleDiscount();
//            handleShipping();
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
    let categoria_nombre = $("#categoria_nombre").val();
    let descripcion = descripcion_quill ? descripcion_quill : null;

    let informacion = {
        categoria: categoria_nombre,
        descripcion: descripcion
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