function consultar_marcas() {
    let categorias = [];
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/marca/consultar", //url guarda la ruta hacia donde se hace la peticion
//    data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            let data = JSON.parse(datos);
            categorias = data;
//            
        }
    });
    $.ajaxSetup({async: true});
    return categorias;
}

function crear_marca(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/marca/registrar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    })
    $.ajaxSetup({async: true});
    return respuesta;
}

async function actualizar_marca_portada(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
//  $.ajaxSetup({async: false});
    respuesta = await fetch("/marca/actualizar_portada", {method: 'POST', body: data})
            .then((response) => response.json())
            .then((data) => {
                return data;
            });

//  $.ajaxSetup({async: true});
    return respuesta;
}