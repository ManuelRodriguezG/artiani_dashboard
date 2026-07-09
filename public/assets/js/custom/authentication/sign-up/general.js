"use strict";

// Class definition
var KTSignupGeneral = function () {
    // Elements
    var form;
    var submitButton;
    var validator;
    var passwordMeter;

    // Handle form
    var handleForm = function (e) {
        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validator = FormValidation.formValidation(
                form,
                {
                    fields: {
                        'nombres': {
                            validators: {
                                notEmpty: {
                                    message: 'El nombre es requerido'
                                }
                            }
                        },
                        'celular': {
                            validators: {
                                notEmpty: {
                                    message: 'El celular es requerido'
                                }
                            }
                        },
                        'contrasenia': {
                            validators: {
                                notEmpty: {
                                    message: 'La contraseña es requerida'
                                },
                                callback: {
                                    message: 'Please enter valid password',
                                    callback: function (input) {
                                        if (input.value.length > 0) {
                                            return validatePassword();
                                        }
                                    }
                                }
                            }
                        },
                        'confirmar-contrasenia': {
                            validators: {
                                notEmpty: {
                                    message: 'La contrasenia es requerida'
                                },
                                identical: {
                                    compare: function () {
                                        return form.querySelector('[name="contrasenia"]').value;
                                    },
                                    message: 'La contraseña no es identica'
                                }
                            }
                        }
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger({
                            event: {
                                password: false
                            }
                        }),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: '.fv-row',
                            eleInvalidClass: '', // comment to enable invalid state icons
                            eleValidClass: '' // comment to enable valid state icons
                        })
                    }
                }
        );

        // Handle form submit
        submitButton.addEventListener('click', function (e) {
            e.preventDefault();

            validator.revalidateField('contrasenia');

            validator.validate().then(function (status) {
                if (status == 'Valid') {

                    // Show loading indication
                    submitButton.setAttribute('data-kt-indicator', 'on');

                    // Disable button to avoid multiple click 
                    submitButton.disabled = true;

                    let data = obtener_informacion();
                    let respuesta = crear_cuenta(data);
                    if (respuesta.error == false) {
                        // Hide loading indication
                        submitButton.removeAttribute('data-kt-indicator');

                        // Enable button
                        submitButton.disabled = false;

                        // Show message popup. For more info check the plugin's official documentation: https://sweetalert2.github.io/
                        Swal.fire({
                            text: respuesta.mensaje,
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "De acuerdo!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                form.reset();  // reset form                    
                                passwordMeter.reset();  // reset password meter
                                //form.submit();

                                //form.submit(); // submit form
                                var redirectUrl = form.getAttribute('data-kt-redirect-url');
                                if (redirectUrl) {
                                    location.href = redirectUrl;
                                }
                            }
                        });
                    } else {
                        Swal.fire({
                            text: respuesta.mensaje,
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Volver a intentarlo!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                // Hide loading indication
                                submitButton.removeAttribute('data-kt-indicator');

                                // Enable button
                                submitButton.disabled = false;
                            }
                        });
                    }
                } else {
                    // Show error popup. For more info check the plugin's official documentation: https://sweetalert2.github.io/
                    Swal.fire({
                        text: "Lo siento, hay algunos errores",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Volver a intentarlo!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            // Hide loading indication
                            submitButton.removeAttribute('data-kt-indicator');

                            // Enable button
                            submitButton.disabled = false;
                        }
                    });
                }
            });
        });

        // Handle password input
        form.querySelector('input[name="contrasenia"]').addEventListener('input', function () {
            if (this.value.length > 0) {
                validator.updateFieldStatus('contrasenia', 'NotValidated');
            }
        });
    }

    // Password input validation
    var validatePassword = function () {
        console.log(passwordMeter.getScore());
        return (passwordMeter.getScore() > 10);
    }

    // Public functions
    return {
        // Initialization
        init: function () {
            // Elements
            form = document.querySelector('#kt_sign_up_form');
            submitButton = document.querySelector('#kt_sign_up_submit');
            passwordMeter = KTPasswordMeter.getInstance(form.querySelector('[data-kt-password-meter="true"]'));

            handleForm();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTSignupGeneral.init();
});

function crear_cuenta(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/autenticacion/registrar_usuario", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function obtener_informacion() {
    let apellido_paterno = document.querySelector('#apellido-paterno').value ? document.querySelector('#apellido-paterno').value : null;
    let apellido_materno = document.querySelector('#apellido-materno').value ? document.querySelector('#apellido-materno').value : null;
    let nombres = document.querySelector('#nombres').value ? document.querySelector('#nombres').value : null;
    let celular = document.querySelector('#celular').value ? document.querySelector('#celular').value : 0;
    let contrasenia = document.querySelector('#contrasenia').value ? document.querySelector('#contrasenia').value : null;
    let confirmar_contrasenia = document.querySelector('#confirmar-contrasenia').value ? document.querySelector('#confirmar-contrasenia').value : null;
    let data = {
        apellido_paterno,
        apellido_materno,
        nombres,
        celular,
        contrasenia,
        confirmar_contrasenia
    };
    return data;
}
