let COMPRA_VENTA_P = {error: true};
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
                        'producto_nombre': {
                            validators: {
                                notEmpty: {
                                    message: 'Nombre del producto es requerido'
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
                        submitButton.disabled = true;
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        let informacion = obtener_informacion();
                        let response = actualizar(informacion);
                        if (response.error == false) {
                            if ($("#avatar")[0].files.length > 0) {
                                let imgPortada = document.getElementById('avatar').files[0];
                                let formSubirPortada = new FormData();
                                formSubirPortada.append('portada', imgPortada);
                                formSubirPortada.append('id_producto', informacion.id_producto);
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

                                        // Redirect to customers list page
//                  window.location = form.getAttribute("data-kt-redirect");
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




                        // Disable submit button whilst loading
//            submitButton.disabled = true;
//
//            setTimeout(function () {
//              submitButton.removeAttribute('data-kt-indicator');
//
//              Swal.fire({
//                text: "Form has been successfully submitted!",
//                icon: "success",
//                buttonsStyling: false,
//                confirmButtonText: "Ok, got it!",
//                customClass: {
//                  confirmButton: "btn btn-primary"
//                }
//              }).then(function (result) {
//                if (result.isConfirmed) {
//                  // Enable submit button after loading
//                  submitButton.disabled = false;
//
//                  // Redirect to customers list page
////                  window.location = form.getAttribute("data-kt-redirect");
//                }
//              });
//            }, 2000);
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
            handleShipping();
            handleSubmit();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {

    let response = producto_a_editar();
    if (response['error'] == false) {
        ui_datos_formulario(response.depurar.info.depurar);
//    ui_proveedores_producto(consulta.depurar.proveedores.depurar);
    }

    let imagenes = response.depurar.imagenes;
    if (imagenes.error == false) {
        ui_imagenes(imagenes.depurar);
    }

    let categorias_p = [];
    if (response.depurar.categorias.error == false) {
        categorias_p = categorias_producto(response.depurar.categorias.depurar);
    }
    let categorias = consultar_categorias();
    if (categorias.error == false) {
        ui_select_categorias(categorias.depurar, categorias_p);
    }

    KTAppEcommerceSaveProduct.init();
});

function calcular_cantidad_sin_impuesto(cantidad, impuesto) {
    let decimal_impuesto = impuesto / 100;
    let divisor_impuesto = decimal_impuesto + 1;

    let cantidad_sin_impuesto = cantidad / divisor_impuesto;
    return cantidad_sin_impuesto;
}

$("#precio_sugerido").on("keyup", function () {
    let porcentaje = calcular_porcentaje_utilidad_libre_de_impuestos();
    $("#utlidad_bruta").val(porcentaje);
});

$("#incluye_impuesto").on("change", function () {
    let costo = $("#precio_base").val();
    if ($("#incluye_impuesto")[0].checked == true) {
        let impuesto = $("#porcentaje_impuesto").val();
        let cantidad = calcular_cantidad_sin_impuesto(costo, impuesto);
        $("#precio_sin_impuestos").val(cantidad);
    } else {
        $("#precio_sin_impuestos").val(costo);
    }

});

function calcular_precio_sugerido() {

}

function calcular_porcentaje_utilidad_libre_de_impuestos() {
    let costo = $("#precio_base").val();
    let impuesto = $("#porcentaje_impuesto").val();
    let precio_venta = $("#precio_sugerido").val();
    let decimal_impuesto = impuesto / 100;

    let costo_sin_impuesto = costo / (decimal_impuesto + 1);
    console.log(costo_sin_impuesto);
    let precio_venta_sin_impuesto = precio_venta / (decimal_impuesto + 1);
    console.log(precio_venta_sin_impuesto);
    let utilidad_bruta = precio_venta_sin_impuesto - costo_sin_impuesto;
    console.log(utilidad_bruta);
    let porcentaje = (utilidad_bruta / precio_venta) * 100;
    return porcentaje;
}

function ui_imagenes(imagenes) {
    //style background-url
    //$(".image-input-wrapper")
    let img;
    imagenes.map(function (imagen) {
        if (imagen.tipo_imagen == "portada") {
//      img = elemento_imagen({"src": imagen.url_imagen});
            ui_pluggin_imagen(imagen.url_imagen);
        } else {

        }
    })

}

function ui_unidades_compra() {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar Unidad compra"});
    $("#unidad_compra").html("");
    $("#unidad_compra").append(opcion);
    Object.entries(UNIDADES_COMPRA_VENTA).forEach((unidad_compra) => {
        if (COMPRA_VENTA_P.error == false) {
            if (COMPRA_VENTA_P.depurar.id_unidad_compra == unidad_compra[1].id_unidad_compra) {
                opcion = crear_opcion({value: unidad_compra[1].id_unidad_compra, text: unidad_compra[1].abreviatura + "-" + unidad_compra[1].unidad_compra, selected: "selected"});
                $("#unidad_compra").append(opcion);
            } else {
                opcion = crear_opcion({value: unidad_compra[1].id_unidad_compra, text: unidad_compra[1].abreviatura + "-" + unidad_compra[1].unidad_compra});
                $("#unidad_compra").append(opcion);
            }
        } else {
            opcion = crear_opcion({value: unidad_compra[1].id_unidad_compra, text: unidad_compra[1].abreviatura + "-" + unidad_compra[1].unidad_compra});
            $("#unidad_compra").append(opcion);
        }

    });
    if (COMPRA_VENTA_P.error == false) {
        $("#factor_compra").val(COMPRA_VENTA_P.depurar.factor);
    }
    $("#unidad_compra").trigger("change");
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
                if (COMPRA_VENTA_P.error == false) {
                    if (COMPRA_VENTA_P.depurar.id_unidad_venta == unidad_venta[1].id_unidad_venta) {
                        opcion = crear_opcion({value: unidad_venta[0], text: unidad_venta[1].abreviatura + "-" + unidad_venta[1].unidad_venta, selected: "selected"});
                        $("#unidad_venta").append(opcion);
                    } else {
                        opcion = crear_opcion({value: unidad_venta[0], text: unidad_venta[1].abreviatura + "-" + unidad_venta[1].unidad_venta});
                        $("#unidad_venta").append(opcion);
                    }
                } else {
                    opcion = crear_opcion({value: unidad_venta[0], text: unidad_venta[1].abreviatura + "-" + unidad_venta[1].unidad_venta});
                    $("#unidad_venta").append(opcion);
                }

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

function elemento_imagen(json) {
//  $('<img />', { 'src': '/imagenes/yoda.png', 'id': 'miImagen', 'class':'miClase' }).appendTo('#contenedor');
    let img = $('<img />', json)[0];
    return img;
}

function ui_pluggin_imagen(elemento_imagen) {
    console.log(elemento_imagen);
//  var imageInput = new KTImageInput(elemento_imagen);
    $(".image-input-wrapper").css("background-image", `url('${elemento_imagen}')`);
    $(".image-input").addClass("image-input-changed");
    $(".image-input").removeClass("image-input-empty");
    $(".image-input").removeAttr("data-kt-image-input");
}

function ui_select_categorias(data_categorias, data_categorias_productos) {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar categoria"});
    $("#select_categorias").html("");
    $("#select_categorias").append(opcion);
    data_categorias.map(function (categoria) {
        if (data_categorias_productos.includes(categoria.id_categoria)) {
            opcion = crear_opcion({value: categoria.id_categoria, text: categoria.categoria, selected: true});
        } else {
            opcion = crear_opcion({value: categoria.id_categoria, text: categoria.categoria});
        }
        $("#select_categorias").append(opcion);
    });
}

function ui_select_proveedores(data_proveedores, data_proveedores_productos) {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar proveedor"});
    $("#select_proveedores").html("");
    $("#select_proveedores").append(opcion);
    data_proveedores.map(function (proveedor) {
        if (data_proveedores_productos.includes(proveedor.id_proveedor)) {
            opcion = crear_opcion({value: proveedor.id_proveedor, text: proveedor.proveedor, selected: true});
        } else {
            opcion = crear_opcion({value: proveedor.id_proveedor, text: proveedor.proveedor});
        }
        $("#select_proveedores").append(opcion);
    });
}

function crear_opcion(opciones) {
    let opcion = $('<option>', opciones)[0];
    return opcion;
}

function producto_a_editar() {
    let id_producto = obtener_id_producto();
    let data = {
        id_producto: id_producto
    }
    let consulta = JSON.parse(consultar_producto_editar(data));

    console.log(consulta);
    return consulta;
}

function categorias_producto(categorias) {
    let values = [];
    categorias.map(function (categoria) {
        values.push(categoria.id_categoria);
    });
//  console.log(values);
//  console.log(JSON.stringify(values));
//  $('#select_proveedores').select2({data: data});
//  selected_dinamic_values('#select_proveedores', values);
    return values;
}

function proveedores_producto(proveedores) {
    let values = [];
    proveedores.map(function (proveedor) {
        values.push(proveedor.id_proveedor);
    });
//  console.log(values);
//  console.log(JSON.stringify(values));
//  $('#select_proveedores').select2({data: data});
//  selected_dinamic_values('#select_proveedores', values);
    return values;
}

function selected_dinamic_values(identificador, values) {
    setTimeout(function () {
        $(identificador).val(values).trigger('change');
    }, 2000);

}

function ui_datos_formulario(datos) {
    $("#producto_nombre").val(datos.nombre);
    $("#kt_ecommerce_add_product_description").html(datos.descripcion);
    $("#precio_base").val(datos.costo);
    $("#codigo_unico").val(datos.sku);
    $("#codigo_interno").val(datos.codigo_interno);
    $("#codigo_barras").val(datos.codigo_barras);
    $("#cantidad").val(datos.existencia);
    $("#piezas_por_caja").val(datos.piezas_por_caja);
    $("#rotacion").val(datos.rotacion);
    $("#porcentaje_impuesto").val(datos.porcentaje_impuesto);
    $("#precio_sin_impuestos").val(datos.precio_sin_impuestos);
    $("#precio_sugerido").val(datos.precio_sugerido);
    $("#utlidad_bruta").val(datos.porcentaje_utilidad_bruta);
    if (datos.incluye_impuesto == 0) {
        $("#incluye_impuesto")[0].checked = false;
    } else {
        $("#incluye_impuesto")[0].checked = true;
    }

}

function obtener_id_producto() {
    let router = url_router();
    let id_producto = router.parametros[0];
    return id_producto;
}

function form_data() {
    let form = document.getElementById("kt_ecommerce_add_product_form");
    let data = new FormData(form);
    return data;
}

function obtener_informacion() {
    let quill = new Quill("#kt_ecommerce_add_product_description");
    let descripcion_quill = quill.root.innerHTML;

    let id_producto = obtener_id_producto();
    let producto_nombre = $("#producto_nombre").val();
    let descripcion = descripcion_quill ? descripcion_quill : null;
    let costo = $("#precio_base").val() ? $("#precio_base").val() : 0;
    let sku = $("#codigo_unico").val() ? $("#codigo_unico").val() : null;
    let codigo_barras = $("#codigo_barras").val() ? $("#codigo_barras").val() : null;
    let codigo_interno = $("#codigo_interno").val() ? $("#codigo_interno").val() : null;
    let existencia = $("#cantidad").val() ? $("#cantidad").val() : 0;
    let piezas_por_caja = $("#piezas_por_caja").val() ? $("#piezas_por_caja").val() : 0;
    let rotacion = $("#rotacion").val() ? $("#rotacion").val() : 0;
    let categorias = $("#select_categorias").val();
    let porcentaje_impuesto = $("#porcentaje_impuesto").val() ? $("#porcentaje_impuesto").val() : 0;
    let precio_sin_impuestos = $("#precio_sin_impuestos").val() ? $("#precio_sin_impuestos").val() : 0;
    let precio_sugerido = $("#precio_sugerido").val() ? $("#precio_sugerido").val() : 0;
    let utlidad_bruta = $("#utlidad_bruta").val() ? $("#utlidad_bruta").val() : 0;
    let incluye_impuesto = $("#incluye_impuesto")[0].checked == true ? 1 : 0;


    let informacion = {
        precio_sugerido: precio_sugerido,
        porcentaje_impuesto: porcentaje_impuesto,
        precio_sin_impuestos: precio_sin_impuestos,
        utlidad_bruta: utlidad_bruta,
        incluye_impuesto: incluye_impuesto,
        id_producto: id_producto,
        producto_nombre: producto_nombre,
        descripcion: descripcion,
        costo: costo,
        sku: sku,
        codigo_barras: codigo_barras,
        existencia: existencia,
        codigo_interno: codigo_interno,
        piezas_por_caja: piezas_por_caja,
        rotacion: rotacion,
        categorias: categorias
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

function obtenerDatosFormulario(idFormulario) {
    let datos = jQuery(idFormulario).serializeArray();
    let a_datos = new Object();
    for (i = 0; i < datos.length; i++) {
        let llave = datos[i]["name"];
        let valor = datos[i]["value"];
        a_datos[llave] = valor;
    }
    return a_datos;
}