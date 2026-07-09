$('.owl-carousel').owlCarousel({
	    loop:true,
	    margin:10,
	    nav:true,
	    autoWidth:true,
	    responsive:{
	        0:{
	            items:1
	        },
	        600:{
	            items:3
	        },
	        1000:{
	            items:5
	        }
	    }
	     });
	$("#carousel-single .owl-next").html('<i class="fas fa-chevron-right"></i>');
	$("#carousel-single .owl-prev").html('<i class="fas fa-chevron-left"></i>');
	$("#carousel-single .owl-dots").remove();