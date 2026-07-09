 //Ready
 $('#modalConstruccion').modal('show');
 //$('#modalConstruccion').modal('hide');
 	
    	        
    	        
    	       
     var height=$(window).height();
    var width=$(window).width();
    $('#contPortada').height(height/2);
    
 /**
  * Resize
 */
    $(window).resize(function(){
        //aqui el codigo que se ejecutara cuando se redimencione la ventana
        var height=$(window).height();
        var width=$(window).width();
        $('#contPortada').height(height/2);
        
        console.log(height);
        console.log(width);
    
    })

    $('.image').click(function(){
        //alert('hola');
    })
            
  /*carrusel de imagenes tipo card*/
  $('#owl-carouselR').owlCarousel({
    //loop:true,
    responsiveClass:true,
    //center:true,
    margin:10,
    autoWidth:true,
    dots:false,
    //nav:true,
    
    responsive:{
      0:{
        items:2,
        
      },
      600:{
        items:3,
        
      },
      1000:{
        items:3,
        
        //loop:true
      }
    }
  })
  
      
  function callback(event){
    
  }
  
 

  $('#owl-carouselD').owlCarousel({
    //loop:true,
    nav:true,
    //center:true,
    autoWidth:true,
    margin:15,
    dots:false,
    responsive:{
      0:{
        items:2,
        
      },
      600:{
        items:3,
        
      },
      1000:{
        items:3,
        
       
      }
    }
  });
  $('#owl-carouselPueblosMagicos').owlCarousel({
    loop:true,
    //nav:true,
    center:true,
    autoWidth:true,
    responsiveClass:true,
    margin:10,
    dots:false,
    responsive:{
      0:{
        items:2,
        
      },
      600:{
        items:3,
        
      },
      1000:{
        items:5,
        
       
      }
    }
  })
  $('#owl-carouselTrenes').owlCarousel({
    //loop:true,
    nav:true,
    //center:true,
    autoWidth:true,
    //responsiveClass:true,
    margin:10,
    dots:false,
    onInitialized:callbackk,
    responsive:{
      0:{
        items:2,
        
      },
      600:{
        items:3,
        
      },
      1000:{
        items:2,
        
       
      }
    }
  })
  function callbackk(){
      //recorrer items 
    	       
  }
  $('.owl-stage-outer').css('height','100% ');
  $('.owl-stage').css('height','100% ');
  
  $('.owl-item').css('height','100%');
  $('.item').css('height','100% ');
  /*carrusel de imagenes tipo card*/


	$('.owl-prev').html('<i class="fas fa-chevron-left"></i>');
	$('.owl-next').html('<i class="fas fa-chevron-right"></i>');
    
    /**
     * Javscript style of icon cart
    */
    //$('.buttonCar').hover(function(){
    //    
    //    if(this.childNodes[0].nodeName == 'svg'){
    //        this.childNodes[0].style.fill = 'white';
    //    }else if(this.childNodes[1].nodeName = 'svg'){
    //        this.childNodes[1].style.fill = 'white';    
    //    }
    //    
    //  
    //});
    //$('.buttonCar').mouseout(function(){
    //    if(this.childNodes[0].nodeName == 'svg'){
    //        this.childNodes[0].style.fill = 'blue';
    //    }else if(this.childNodes[1].nodeName = 'svg'){
    //        this.childNodes[1].style.fill = 'blue';   
    //    }
    //    
    //  
    //});
	
	
	/*
	    funciones
	*/
	function openDestiny(element){
	    console.log(element.dataset['ids']);
	    
	    var ids = element.dataset['ids'];
	    var title = element.dataset['title'];
	    
	    $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
	        action:'creaCardsModalDestinos',
	        ids,
	        lan:$(document)[0].documentElement.lang.toUpperCase()
	    })}`,function(response){
	        $("#cont-modal-destino").html(response);
	       $('#title-modal-cards').html(title);
    	       
	            $('#modalDestinos').modal('show');   
	             
    	          
	    })
	    
	    
	    
	    
	          
	     
	    
	}
	
function prueba(element){
		            //console.log('//----------------------elemento----------------------------');
		            //console.log(element);
		            //console.log('//------------------------------------------------------------');
		            //console.log('//----------------------siblings----------------------------');
		            //console.log(element.siblings('.cont-images-animation').find('.item-image'));
		            //console.log('//------------------------------------------------------------');
		            animation(element.siblings('.cont-images-animation').find('.item-image')[0],element.siblings('.cont-images-animation').find('.item-image'),0,element);
		        }
		        
		        function animation(item,arr,pos,element){
		            console.log(element[0].classList.contains('slide-images'));
		        item.classList.remove('out');
		            item.classList.add('in');
		            //console.log(item);
		            //console.log(arr);
		            //console.log(pos);
		            var prev = '';
		            if(pos == 0){
		                prev = arr.length-1;  
		            }else{
		                prev = pos-1;  
		            }
		             
		            arr[prev].classList.remove('in');
		            arr[prev].classList.add('out');
		            
		            
		           
		            
		            
		            var len = arr.length;
		            //console.log(len);
		            var acceso = '';
		            if(aux == 1){
		                
		                acceso = 'true';
		            }else if(aux == 0){
		                element[0].classList.remove('continue');
		                acceso = 'false';
		            }
		            setTimeout(function(){
		                var comparacion = len-1;
		                var next = pos+1;
    		             if(comparacion == pos){
    		             next = 0;
    		             }
    		             if(element[0].classList.contains('continue') == true){
    		                animation(arr[next],arr,next,element);         
    		             }else{
    		                 return;
    		             }
		                
		            },2000);
		        }
		        
		        var aux = 0;
$(document).ready(function(){
    $('.slide-images').hover(function(){
        $(this).addClass('continue');
        var arr = $(this).siblings('.cont-images-animation').find('.item-image');
		                var len = $(this).siblings('.cont-images-animation').find('.item-image').length;
		                for(var i = 0; i<len;i++){
		                    //console.log($(this).siblings('.cont-images-animation').find('.item-image')[i]);
		                    $(this).siblings('.cont-images-animation').find('.item-image')[i].classList.add('out');
		                    $(this).siblings('.cont-images-animation').find('.item-image')[i].classList.remove('in');
		                }
		                aux = 1;
		                
		                if($(this).siblings('.cont-images-animation').find('.item-image').length <=1){
		                    $(this).siblings('.cont-images-animation').find('.item-image')[0].classList.remove('out');
		                    $(this).siblings('.cont-images-animation').find('.item-image')[0].classList.add('in');
		                    
		                }else{
		                   
        		                prueba($(this));    
        		           
		                    
		                }
		               
		                ////console.log($(this).siblings('.cont-images-animation').find('.item-image'));
		                //var items = $(this).siblings('.cont-images-animation').find('.item-image');
		                //var len = $(this).siblings('.cont-images-animation').find('.item-image').length;
		                //aux = 1;
		                //for(var i = 0;i<len; i++){
		                //    setTimeout(function(){
		                //        if(i < len){
    		            //        var auxElement = parseInt(i)-1;
    		            //        console.log();
    		            //        $(this).siblings('.cont-images-animation').find('.item-image')[i].classList.add('active');
    		            //        if(i>0){
    		            //            $(this).siblings('.cont-images-animation').find('.item-image')[auxElement].classList.remove('active');    
    		            //        }
    		            //        
    		            //            //$(this).siblings('.cont-images-animation').find('.item-image')[auxElement].removeClass('active');
    		            //            //$(this).siblings('.cont-images-animation').find('.item-image')[i].addClass('active');
    		            //        }else{
    		            //            i = 0;
    		            //        }
		                //    },2000);
		                //    
		                //}
		              //$(this).siblings('.cont-images-animation').find('.item-image').each(function(){
		              //  $(this).before('.item-image').removeClass('active');
		              //  $(this).addClass('active');
		              //  
		              //   //console.log($(this).addClass('active')); 
		              //});
		               
		            });
		            $('.slide-images').mouseout(function(){
		                aux = 0;
		                $(this).removeClass('continue');
		                //console.log($(this).siblings('.cont-images-animation').find('.item-image'));
		                var arr = $(this).siblings('.cont-images-animation').find('.item-image');
		                var len = $(this).siblings('.cont-images-animation').find('.item-image').length;
		                for(var i = 0; i<len;i++){
		                    //console.log($(this).siblings('.cont-images-animation').find('.item-image')[i]);
		                    $(this).siblings('.cont-images-animation').find('.item-image')[i].classList.add('out');
		                    $(this).siblings('.cont-images-animation').find('.item-image')[i].classList.remove('in');
		                }
		              
		            })
})