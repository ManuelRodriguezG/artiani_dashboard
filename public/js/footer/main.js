if($( ".containerM" ).height() == 0){
            $( "#footer-page" ).addClass('fixed-bottom');
        }else{
            $( "#footer-page" ).removeClass('fixed-bottom');
        }
    $( window ).resize(function() {
      console.log($( ".containerM" ).height());
      if($( ".containerM" ).height() == 0){
          $( "#footer-page" ).addClass('fixed-bottom');
      }else{
          $( "#footer-page" ).removeClass('fixed-bottom');
      }
        if($('html').height() < $(window).height()){
            $('#footer-page').addClass('fixed-bottom');
        }else{
            $('#footer-page').removeClass('fixed-bottom');
        }
    });
    
    if($('html').height() < $(window).height()){
        $('#footer-page').addClass('fixed-bottom');
    }else{
        $('#footer-page').removeClass('fixed-bottom');
    }