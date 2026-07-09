$(document).ready(function(){
        
    	
    	$('#container').on('click', function() {
              if($('#big_c').hasClass('rotate')) {
                  $('#big_c').removeClass('rotate')
                  
                  
                
                  $('.little_c').removeClass('appear');
                
                 
              }
              
              else {
                $('#big_c').addClass('rotate');
                
                                                              
                
                $('.little_c').addClass('appear');
                
               
              }
            });	
        $(window).scroll(function(){
        	var scroll = $(window).scrollTop();
        	var height = $(window).height();
        	var topeScroll = $(document).height() - $(window).height() - '100';
        	
        	var btnReserva = height;
        	if (scroll >= btnReserva) {
        		
        		if ($('.ScrP').hasClass('Imp') ) {
        			$('.ScrP').removeClass('hidden');
        			if (scroll >= topeScroll) {
        				$('.hola').addClass('ScHo');
        			}else{
        				$('.hola').removeClass('ScHo');
        			}			
        		}else{
        			$('.ScrP').removeClass('hidden');
        			
        		}
        		
        	}else{
        		$('.ScrP').addClass('hidden');
        	}
        
        
        	
        });
        
    });



function mostrarTitle(element){
    
    $('.modal-bodyP').html(
        '<div class="row" style="margin:0px;display:flow-root;">'+
                            '<button class="btn btnP" onclick="closeP();">'+
                                '<span><i class="fas fa-times" style="font-size:25px;"></i></span>'+
                            '</button>'+
                        '</div>'+
                        '<div class="row" style="margin:0px;padding:10px;justify-content:center;">'+
                            '<span style="font-size:20px;font-weight:600;">'+$('#'+element.id).data('title')+'</span>'+
                        '</div>'+
                        '<div class="row" style="margin:0px;justify-content:center;padding:10px;">'+
                            '<span style="font-size:20px;font-weight:600;color:gray;">'+$('#'+element.id).data('tipo')+'</span>'+
                        '</div>'+
                        '<div class="row" style="margin:0px;padding:10px;text-align:justify;">'+
                            element.title+
                        '</div>'
        );
    $('.modalP').css('display','block');
    $('.modal-headerP').css('display','none');
    $('.modal-contentP').css('max-width','600px !important');
    
}