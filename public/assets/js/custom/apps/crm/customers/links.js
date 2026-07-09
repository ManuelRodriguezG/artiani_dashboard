$(document).ready(function () {

});

function generar_link() {
  let asesor = $("#asesor option:selected").val();
  let nombre_cliente = $("#nombre_cliente").val();
  let medio_contacto = $("#medio_contacto option:selected").val();
  let numero_contacto = $("#numero_contacto").val() ? $("#numero_contacto").val() : null;

  let info_link = {
    asesor: asesor,
    nombre_cliente: nombre_cliente,
    medio_contacto: medio_contacto,
    numero_contacto: numero_contacto
  };
  if (asesor && nombre_cliente && medio_contacto) {
    let respuesta = JSON.parse(crear_link(info_link));
    if (respuesta.error == false) {
      $("#kt_clipboard_1").val(respuesta.depurar.link);
      $("#kt_clipboard_1").attr("value", respuesta.depurar.link);
    }
  }
}

function crear_link(data) {
  let respuesta = [];
  //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
  $.ajaxSetup({async: false});
  $.ajax({
    type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
//    contentType: 'multipart/form-data',
    url: "/link/crear", //url guarda la ruta hacia donde se hace la peticion
    data: data, // data recive un objeto con la informacion que se enviara al servidor
    success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
      console.log(datos);
      respuesta = datos;
    }
  });
  $.ajaxSetup({async: true});
  return respuesta;
}

// Select elements
const target = document.getElementById('kt_clipboard_1');
const button = target.nextElementSibling;

// Init clipboard -- for more info, please read the offical documentation: https://clipboardjs.com/
var clipboard = new ClipboardJS(button, {
  target: target,
  text: function () {
    return target.value;
  }
});

// Success action handler
clipboard.on('success', function (e) {
  const currentLabel = button.innerHTML;

  // Exit label update when already in progress
  if (button.innerHTML === 'Copied!') {
    return;
  }

  // Update button label
  button.innerHTML = 'Copied!';

  // Revert button label after 3 seconds
  setTimeout(function () {
    button.innerHTML = currentLabel;
  }, 3000)
});

