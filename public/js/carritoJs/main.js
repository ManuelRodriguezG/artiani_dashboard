if($(window).width() <=600){
            var newwHeight = (80/100)*$(window).width();
                $('.contInfo').css('min-width',newwHeight+'px');
        }
        //acomodar ventana carrito
        $(window).resize(function(){
            //aqui el codigo que se ejecutara cuando se redimencione la ventana
            var height=$(window).width();
            console.log(height);
            if(height <=600){
                var newHeight = (80/100)*height;
                $('.contInfo').css('min-width',newHeight+'px');
            }
           
        
        })
        
        
        //eliminar del carrito
        $('.delete').click(function(){
            var id = $(this)[0].id.substr(6);
            $('#e'+id).remove();
        })
        
        
        
        
        
        //eventos pestaña del carrito
        $('.btnCart').click(function(event){
            if($(this)[0].id == 'cnsrv'){
                if($('.contMail').hasClass('active')){
                    $('.contMail').css('display','none');    
                    $('.contMail').removeClass('active');
                }else{
                    $('.contMail').css('display','table-row');
                    $('.contMail').addClass('active');
                    $(".contInfo").animate({
                        scrollTop: 10000
                    }, 2000);
                }
                
            }
        });
        
        
        $('.contLabel').click(function(event){
            if($('.infoCart').hasClass('show')){
                $('.infoCart').removeClass('show');
                $('.infoCart').css('display','none');
            }else{
                $('.infoCart').css('display','block');
                $('.infoCart').addClass('show');
                //oculta contenedor de conservar datos
                if($('.contMail').hasClass('active')){
                    $('.contMail').css('display','none');    
                    $('.contMail').removeClass('active');
                }
            
            }
             $('.trash').css('display','block');
            $('.delete').css('display','none');
            
        });
        /* function trash(element){
            
            console.log('hola');
            console.log(element.id.substr(5));
            var id = element.id.substr(5);
            $('#'+element.id).css('display','none');
            $('#delete'+id).css('display','block');
            setTimeout(function(){ 
                
                $('#'+element.id).css('display','block');
            $('#delete'+id).css('display','none');
            }, 3000);
        }
        function deletee(element){
            
            console.log('delete');
            console.log(element.id.substr(6));
            var id = element.id.substr(6);
            $('#e'+id).remove();
            var valCart = $(".labelCart").text();
            valCart--;
            if(valCart == 0){
                 $('.contInfo').css('height','50px');
                $('.contInfo').css('min-width','270px');  
                $('.tfootCard').css('display','none'); 
                $('.contInfo').css('overflow','hidden');
                $('tbody').html('<td class="nItms"><label>No se hay elementos agregados</label></td>');
            }
            $(".labelCart").text(valCart);
            //acomodar si se van eliminando los elementos que se recorra el tooltip
            var width =$(window).width();
            var height = $('.tableCart').height();
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
            //$('#delete'+id).css('display','block');
        }*/