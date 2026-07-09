$(document).ready(function(){
    //alert('hola');
	var height=$('.text-white').height();
	console.log(height);
	$('.vacio').css('height',height);
	
	/*Dinamismo pagina busqueda
	var url = window.location;
	const url1 = window.location.href;
    const url_string = url1.slice(url1.indexOf('?') + 1, url1.length);
    const searchParams = new URLSearchParams(url_string);
    var paramBusqueda = searchParams.get("busqueda");
	
	if(!paramBusqueda){
	    $('.containerM').html("<div class='infoSearch text-center'>"+
				"</div>");
	    var heightWindow = $(window).height();
	    var heightFooter = $('.hola').height();
	    var heightheader = $('header').height();
	    var heightContainer = $('.alturaM').height();
	    var heightContainerWordsSearch = $('.containerM').height();
	    var heightt = heightWindow - (heightFooter /1.15 ) - heightheader - heightContainer;
	    console.log(heightWindow);
	    if (heightt > 125) {
	        $('.infoSearch').css('height',heightt+'px');
	    }
	}
	Termina Dinamismo pagina busqueda*/
	
	/*Busqueda*/
	 $("#busqueda").focus(function() {
    $(".search-box").addClass("border-searching");
    $(".search-icon").addClass("si-rotate");
  });
  $("#busqueda").blur(function() {
    $(".search-box").removeClass("border-searching");
    $(".search-icon").removeClass("si-rotate");
  });
  $("#busqueda").keyup(function() {
    if($(this).val().length > 0) {
      $(".go-icon").addClass("go-in");
    }
    else {
      $(".go-icon").removeClass("go-in");
    }
  });
  $(".go-icon").click(function(){
    $(".search-form").submit();
  });
  /*Termina Javascript para la seccion de busqueda*/
  
  $.get('https://panoramex.mx/busquedas/show',`data=${JSON.stringify({
                action:'charts',
                hola:'hola'
               
           })}`,function(response){
               console.log(response);
           });
  $.ajax({
           type: "GET",
           url: 'https://panoramex.mx/busquedas/show/',
           data: `data=${JSON.stringify({
                action:'charts',
                hola:'hola'
               
           })}`,
           
           beforeSend: function() {
            console.log('response');
           },
           
           success: function(response) {
            console.log(response); 
            console.log('response');
           }
      });  
  
});