function tipos_entrega_listar(data) {
  let respuesta = [];
  //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
  $.ajaxSetup({async: false});
  $.ajax({
    type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
    url: "/Sistema/puntos_entrega_listar", //url guarda la ruta hacia donde se hace la peticion
    data: data, // data recive un objeto con la informacion que se enviara al servidor
    success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
      console.log(datos);
      respuesta = datos;
    }
  });
  $.ajaxSetup({async: true});
  return respuesta;
}