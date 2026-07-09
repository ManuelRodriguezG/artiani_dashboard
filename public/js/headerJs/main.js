 var cont = 0;
$(document).ready(function() {
    
    $(document).scroll(function(){
        $('.nav-drop').each(function(){
           $(this).removeClass('show-list-drop');
           
        });
        $('.nav-drop-right').each(function(){
           $(this).removeClass('show-list-drop');
           
        });
    });
    
    if($(document).width() <= 991){
        $('#language-drop').removeClass('dropdown-menu-right');
    }else{
        $('#language-drop').addClass('dropdown-menu-right');
    }
    $( window ).resize(function() {
        $('.nav-drop').each(function(){
           $(this).removeClass('show-list-drop');
           
        });
        $('.nav-drop-right').each(function(){
           $(this).removeClass('show-list-drop');
           
        });
        if($(document).width() <= 991){
            $('#language-drop').removeClass('dropdown-menu-right');
        }else{
            $('#language-drop').addClass('dropdown-menu-right');
        }
    });
    
    $('.btn-drop').click(function(e){
                //console.log(e);
                //console.log($(this)[0].offsetWidth);
               //console.log($(this).siblings('.nav-drop').addClass('show-list-drop'));
               var width = $(this)[0].offsetWidth;
               var height = $(this)[0].offsetHeight;
               //console.log(width);
               if($(this).siblings('.nav-drop').length == 1){
                   //console.log('nav-drop');
                   var validation = 'false';
                   if(!$(this).siblings('.nav-drop').hasClass('show-list-drop')){
                       validation = 'true';
                   }
                    $('.nav-drop').each(function(){
                      $(this).removeClass('show-list-drop');
                      
                    });
                   $('.nav-drop-right').each(function(){
                      $(this).removeClass('show-list-drop');
                      
                   });
                    if(validation == 'true'){
                        $(this).siblings('.nav-drop').toggleClass('show-list-drop');    
                    }
                   
                   
               }else if($(this).siblings('.nav-drop-right').length == 1){
                   //console.log('nav-drop-right');
                  //console.log($(this).siblings('.nav-drop-right'));
                  //console.log($(document).width());
                  //console.log($(this));
                    if($(document).width() <= 991){
                        $(this).siblings('.nav-drop-right').toggleClass('show-list-drop');
                        //$(this).siblings('.nav-drop-right').css('position','absolute');  
                        $(this).siblings('.nav-drop-right').css('transform','translate3d(0px,9px,0px)');  
                        
                    }else{
                        $(this).siblings('.nav-drop-right').toggleClass('show-list-drop');
                        $(this).siblings('.nav-drop-right').css('transform','translate3d('+width+'px,-9px,0px)');  
                    }
                   
                   recursiva($(this));
               }
               
            });
    
    localStorage.setItem('updateCart','false');
    var zero = 0;
    $(window).on('scroll',function(){
        if($(window).scrollTop() < 100){
            
            $('.nav-bar').removeClass('ocult-nav');
        }else{
            if(window.location.href != 'https://panoramex.mx/'){
                $('.nav-bar').removeClass('static');
            }
            $('.nav-bar').toggleClass('ocult-nav',$(window).scrollTop() > zero);
        zero = $(window).scrollTop();
        }
        
    })

   
            updateCart();    

    
    
    //Comenzar con menu light si es el index
    console.log(window.location.href);
    if(window.location.href == 'http://panoramex.mx/'){
        $('.menu').addClass('menu-light');
        $('header').addClass('fixed-top');
        $('.xs-menu-cont').addClass('menu-light');
    }
    
    
       
        
        
        //agregar al carrito
        /*$('.buttonCar').click(function(event){
            console.log($(this)[0].attributes.urlimg.value);
            console.log($(this)[0].attributes.nameproduct.value);
            var url = $(this)[0].attributes.urlimg.value;
            var name = $(this)[0].attributes.nameproduct.value;
            var price = $(this)[0].attributes.price.value;
            var valCart = $(".labelCart").text();
            if(valCart == 0){
                $('tbody').html('');
                
            }
            //   
            $('.contInfo').css('overflow','auto');
           $('tbody').append('<tr class="element " id="e'+cont+'"><td class="align-left "><img class="imgElement" src="'+url+'"></td><td class="align-center "><div class="contLabelCart"><label>'+name+'</label></div></td><td><label>'+'$' + price.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')+'</label></td><td class="align-center "><div class="contDelete"><div class="trash" onclick="trash(this);" id="trash'+cont+'" style="display: block;"><i class="far fa-trash-alt"></i></div><div class="delete" onclick="deletee(this);" id="delete'+cont+'" style="display: none;"><i class="fas fa-times"></i></div></div></td></tr>');
            $('.tfootCard').css('display','table-footer-group'); 
           
            
            
            cont++;
            
            valCart++;
             var width =$(window).width();
             var height = $('.contInfo').height();
             console.log(height);
             if(width > 600){
                 if(height >= 378){
                    $('.contInfo').css('height','400px');     
                 }else{
                     $('.contInfo').css('height','auto');
                 }
                
                $('.contInfo').css('min-width','400px');     
             }else{
                
                
                if(width <=600){
                    var newWidth = (80/100)*width;
                    $('.contInfo').css('min-width',newWidth+'px');
                }
                if(height >= 200){
                    $('.contInfo').css('height','200px');
                 }else{
                     $('.contInfo').css('height','auto');
                 }
                
                  
             }
            $(".labelCart").text(valCart);
            //Cambiar contador del carrito
            var elements = document.getElementsByClassName('element');
            //$(".element").each(function(){
       		//    $(this).attr('id');
       		//});
            
        });*/
        
				//responsive menu toggle
				$("#menutoggle").click(function() {
					$('.xs-menu').toggleClass('displaynone');

					});
				//add actives class on menu
				$('ul .elementNavN').click(function(e) {
				
					$('li').removeClass('actives');
					$(this).addClass('actives');
				});
			//drop down menu	
					$(".drop-down").hover(function() {
						$('.mega-menu').addClass('display-on');
					});
					$(".drop-down").mouseleave(function() {
						$('.mega-menu').removeClass('display-on');
					});
					
					var scroll = 'null';
					/*$(window).scroll(function (event) {
                        console.log($(window).scrollTop()+" top");
                        console.log(scroll+' scroll');
                        var alto = $(window).height()/2;
                        //if($(window).scrollTop() >= alto){
                        //    $('.divCart').css('top','25%');
                        //}else{
                        //    $('.divCart').css('top','50%');
                        //}
                        console.log($(window).height()+' height');
                        if(window.location.href == 'http://panoramex.mx/'){
                            $('header').addClass('fixed-top');
                            if($(window).scrollTop() < 100){
                                 
                                
                            //$('header').addClass('collap');
                            $('.menu').addClass('menu-light');
                            $('.xs-menu-cont').addClass('menu-light');
                            $('.xs-menu').addClass('menu-light');
                            $('header').removeClass('mtrsler');
                            $('header').removeClass('collap');
                            }else{
                                
                                
                            if(scroll == 'null'){
                                //$('.menu').removeClass('menu-light');
                                $('header').removeClass('mtrsler');
                                $('header').addClass('collap');  
                                scroll = $(window).scrollTop();
                                console.log('scroll de inicio');
                            }else{
                                if($(window).scrollTop()> scroll){
                                    scroll = $(window).scrollTop();
                                    console.log('pintar color azul');
                                    //$('.menu').removeClass('menu-light');
                                    $('header').removeClass('mtrsler');
                                    $('header').addClass('collap');
                                    if($(window).scrollTop() >= 80 ){
                                        $('.menu').removeClass('menu-light');  
                                        $('.xs-menu').removeClass('menu-light');
                                        $('.xs-menu-cont').removeClass('menu-light');    
                                    }
                                    
                                }else{
                                    console.log('mostrar menu');
                                    $('header').removeClass('collap');
                                    $('header').addClass('mtrsler');
                                    scroll = $(window).scrollTop();
                                }
                            }
                            
                        }
                        }else{
                             if($(window).scrollTop() == 0){
                                //$('header').addClass('collap');
                                
                                $('header').removeClass('fixed-top');
                                
                                $('header').removeClass('mtrsler');
                                $('header').removeClass('collap');
                                }else{
                                    $('header').addClass('fixed-top');
                                if(scroll == 'null'){
                                    //$('.menu').removeClass('menu-light');
                                    $('header').removeClass('mtrsler');
                                    $('header').addClass('collap');  
                                    scroll = $(window).scrollTop();
                                    console.log('scroll de inicio');
                                }else{
                                    if($(window).scrollTop()> scroll){
                                        scroll = $(window).scrollTop();
                                        console.log('pintar color azul');
                                        //$('.menu').removeClass('menu-light');
                                        $('header').removeClass('mtrsler');
                                        $('header').addClass('collap');
                                        if($(window).scrollTop() >= 80 ){
                                              
                                            
                                                
                                        }
                                        
                                    }else{
                                        console.log('mostrar menu');
                                        $('header').removeClass('collap');
                                        $('header').addClass('mtrsler');
                                        scroll = $(window).scrollTop();
                                    }
                                }
                            
                        }
                            
                        }
                        
                    // Do something
                    });*/
                    
                    
                    $("#inpt_search").on('focus', function () {
                    	$(this).parent('label').addClass('active');
                    });
                    
                    $("#inpt_search").on('blur', function () {
                    	if($(this).val().length == 0)
                    		$(this).parent('label').removeClass('active');
                    });
                    $("#inpt_searchh").on('focus', function () {
                    	$(this).parent('label').addClass('active');
                    });
                    
                    $("#inpt_searchh").on('blur', function () {
                    	if($(this).val().length == 0)
                    		$(this).parent('label').removeClass('active');
                    });
			
			});
			
function recursiva(elemento){
    
    //console.log(elemento.siblings('.nav-drop-right').hasClass('show-list-drop'));
    //console.log(elemento);
    if(!elemento.siblings('.nav-drop-right').hasClass('show-list-drop')){
        //console.log('Es momento de ocultar');
        //console.log(elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes.length);
        if(elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes.length == 2){
            (elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes[1].classList.remove('show-list-drop'));    
            //console.log(elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes);
            //console.log($(elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes[0]));
            recursiva($(elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes[0]));
            //console.log(document.querySelectorAll(elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes[0]));
            //recursiva(elemento.siblings('.nav-drop-right')[0].childNodes[0].childNodes[0].childNodes);
        }
        
    }
}
			
function closeP(){
    $('.modalP').css('display','none');
}

function updateCart(){
    //Reconstruir carrito
    if(localStorage.getItem('objectCar')){
        
        var objectCar = JSON.parse(localStorage.getItem('objectCar'));
        $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
            objectCar,
            action:'updateCar',
            lan: $('html')[0].lang.toUpperCase()
        })}`,function(response){
            //console.log(response);
            response = JSON.parse(response);
            objectCar = response;
            var len = objectCar.length;
            for(var i = 0;i<len;i++){
                //console.log((objectCar[i]['price']));
                var url = objectCar[i]['urlImagen'];
                var name = objectCar[i]['name'];
                var price = objectCar[i]['price'];
                var idElement = objectCar[i]['id'];
                var currency = objectCar[i]['currency'];
                var urlHref = objectCar[i]['urlHref'];
            
                
                //localStorage.removeItem('objectCar');
                var valCart = $(".labelCart").text();
                if(valCart == 0){
                    $('.tableCart tbody').html('');
                    
                }
                //   
                $('.contInfo').css('overflow','auto');
               $('.tableCart tbody').append('<tr class="element " id="e'+cont+'"><td class="align-left "><img class="imgElement" src="'+url+'"></td><td class="align-center "><div class="contLabelCart"><a href="'+urlHref+'" ><label style="cursor:pointer;">'+name+'</label></a></div></td><td><label>'+'$' + price.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')+' '+currency+'</label></td><td class="align-center "><div class="contDelete"><div class="trash" onclick="trash(this);" id="trash'+cont+'" style="display: block;"><i class="far fa-trash-alt"></i></div><div class="delete" onclick="deletee(this);" idElement="'+idElement+'" id="delete'+cont+'" style="display: none;"><i class="fas fa-times"></i></div></div></td></tr>');
                $('.tfootCard').css('display','table-footer-group'); 
               
                
                
                cont++;
                
                valCart++;
                 var width =$(window).width();
                 var height = $('.contInfo').height();
                 console.log(height);
                 if(width > 600){
                     if(height >= 378){
                        $('.contInfo').css('height','400px');     
                     }else{
                         $('.contInfo').css('height','auto');
                     }
                    
                    $('.contInfo').css('min-width','400px');     
                 }else{
                    
                    
                    if(width <=600){
                        var newWidth = (80/100)*width;
                        $('.contInfo').css('min-width',newWidth+'px');
                    }
                    if(height >= 200){
                        $('.contInfo').css('height','200px');
                     }else{
                         $('.contInfo').css('height','auto');
                     }
                    
                      
                 }
                $(".labelCart").text(valCart);
                //Cambiar contador del carrito
                var elements = document.getElementsByClassName('element');
            }
            
        })
        
    }
}

function addCar(element){
    
            localStorage.setItem('updateCart','true');
   
            //console.log(localStorage.getItem('objectCar'));
            
    
            //console.log(element);
            //console.log(element.getAttribute('urlImg'));
            
            
            
            var url = element.getAttribute('urlimg');
            var name = element.getAttribute('nameproduct');
            var price = element.getAttribute('price');
            var idTour = element.getAttribute('idTour');
            var currency = element.getAttribute('currency');
            var urlHref = element.getAttribute('urlHref');
            
            /*Verificar si existe el tour que desean agregar*/
            if(localStorage.getItem('objectCar')){
                
            
             var objectCar = JSON.parse(localStorage.getItem('objectCar'));
            //console.log(objectCar); 
            var arrIds = [];
            var len = objectCar.length;
            for(var clave in objectCar){
                //console.log(objectCar[clave]['id']);
                arrIds.push(objectCar[clave]['id']);
            }
            //console.log(arrIds); 
            //console.log(arrIds.includes("35"));
            
            //------------------------------------------------
            if(arrIds.includes(idTour)){
                //console.log('si se encuentra');
                $('.delete').each(function(){
                    //console.log($(this)[0].getAttribute('idElement'));
                    if($(this)[0].getAttribute('idElement') == idTour){
                        $('.infoCart').css('display','block');
                         $('.infoCart').addClass('show');
                            $(this).parent().parent().parent().css('background','#a2a2a2');    
                        setTimeout(function(){
                            $('.element').each(function(){
                                $(this).css('background','white');
                            })
                        },2000)        ;
                        
                    }
                })
            }else{
                var item = {
                    currency,
                    id:idTour,
                    name,
                    price,
                    urlImagen:url,
                    urlHref
                };
            var objectCar = [];
            if(localStorage.getItem('objectCar')){
                objectCar = JSON.parse(localStorage.getItem('objectCar'));
                objectCar.push(item);
                localStorage.setItem('objectCar',JSON.stringify(objectCar));
                //console.log('si esta insertado');
            }else{
                //console.log('no esta insertado');
                
                
                objectCar.push(item);
                
                localStorage.setItem('objectCar',JSON.stringify(objectCar));
            }
            console.log(localStorage.getItem('objectCar'));
            //localStorage.removeItem('objectCar');
            var valCart = $(".labelCart").text();
            if(valCart == 0){
                $('.tableCart tbody').html('');
                
            }
            //   
            $('.contInfo').css('overflow','auto');
           $('.tableCart tbody').append('<tr class="element " id="e'+cont+'"><td class="align-left "><img class="imgElement" src="'+url+'"></td><td class="align-center "><div class="contLabelCart"><a href="'+urlHref+'"><label style="cursor:pointer;">'+name+'</label></a></div></td><td><label>'+'$' + price.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')+' '+currency+'</label></td><td class="align-center "><div class="contDelete"><div class="trash" onclick="trash(this);" idElement="'+idTour+'" id="trash'+cont+'" style="display: block;"><i class="far fa-trash-alt"></i></div><div class="delete" idElement="'+idTour+'" onclick="deletee(this);" id="delete'+cont+'" style="display: none;"><i class="fas fa-times"></i></div></div></td></tr>');
            $('.tfootCard').css('display','table-footer-group'); 
           
            
            
            cont++;
            
            valCart++;
             var width =$(window).width();
             var height = $('.contInfo').height();
             //console.log(height);
             if(width > 600){
                 if(height >= 378){
                    $('.contInfo').css('height','400px');     
                 }else{
                     $('.contInfo').css('height','auto');
                 }
                
                $('.contInfo').css('min-width','400px');     
             }else{
                
                
                if(width <=600){
                    var newWidth = (80/100)*width;
                    $('.contInfo').css('min-width',newWidth+'px');
                }
                if(height >= 200){
                    $('.contInfo').css('height','200px');
                 }else{
                     $('.contInfo').css('height','auto');
                 }
                
                  
             }
            $(".labelCart").text(valCart);
            //Cambiar contador del carrito
            var elements = document.getElementsByClassName('element');
            $('.infoCart').css('display','block');
                         $('.infoCart').addClass('show');
            }
            }else{
                var item = {
                    currency,
                    id:idTour,
                    name,
                    price,
                    urlImagen:url,
                    urlHref
                };
            var objectCar = [];
            if(localStorage.getItem('objectCar')){
                objectCar = JSON.parse(localStorage.getItem('objectCar'));
                objectCar.push(item);
                localStorage.setItem('objectCar',JSON.stringify(objectCar));
                //console.log('si esta insertado');
            }else{
                //console.log('no esta insertado');
                
                
                objectCar.push(item);
                
                localStorage.setItem('objectCar',JSON.stringify(objectCar));
            }
            //console.log(localStorage.getItem('objectCar'));
            //localStorage.removeItem('objectCar');
            var valCart = $(".labelCart").text();
            if(valCart == 0){
                $('.tableCart tbody').html('');
                
            }
            //   
            $('.contInfo').css('overflow','auto');
           $('.tableCart tbody').append('<tr class="element " id="e'+cont+'"><td class="align-left "><img class="imgElement" src="'+url+'"></td><td class="align-center "><div class="contLabelCart"><label>'+name+'</label></div></td><td><label>'+'$' + price.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')+' '+currency+'</label></td><td class="align-center "><div class="contDelete"><div class="trash" onclick="trash(this);" idElement="'+idTour+'" id="trash'+cont+'" style="display: block;"><i class="far fa-trash-alt"></i></div><div class="delete" idElement="'+idTour+'" onclick="deletee(this);" id="delete'+cont+'" style="display: none;"><i class="fas fa-times"></i></div></div></td></tr>');
            $('.tfootCard').css('display','table-footer-group'); 
           
            
            
            cont++;
            
            valCart++;
             var width =$(window).width();
             var height = $('.contInfo').height();
             //console.log(height);
             if(width > 600){
                 if(height >= 378){
                    $('.contInfo').css('height','400px');     
                 }else{
                     $('.contInfo').css('height','auto');
                 }
                
                $('.contInfo').css('min-width','400px');     
             }else{
                
                
                if(width <=600){
                    var newWidth = (80/100)*width;
                    $('.contInfo').css('min-width',newWidth+'px');
                }
                if(height >= 200){
                    $('.contInfo').css('height','200px');
                 }else{
                     $('.contInfo').css('height','auto');
                 }
                
                  
             }
            $(".labelCart").text(valCart);
            //Cambiar contador del carrito
            var elements = document.getElementsByClassName('element');
            $('.infoCart').css('display','block');
                         $('.infoCart').addClass('show');
            }
            
        }