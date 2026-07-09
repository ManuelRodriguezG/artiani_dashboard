function url_router() {
  let href = window.location.href;
  let protocoloLength = window.location.protocol.length + 2;
  console.log(href.substring(protocoloLength).split("/"));
  let array_url = [];
  array_url = href.substring(protocoloLength).split("/");
  let legth_array = array_url.length;
  let dominio = array_url[0];
  let modulo = array_url[1];
  let accion = array_url[2];
  array_url = array_url.slice(3, legth_array);
  let data_url = {
    dominio: dominio,
    modulo: modulo,
    accion: accion,
    parametros: array_url
  }
  return data_url;
}

