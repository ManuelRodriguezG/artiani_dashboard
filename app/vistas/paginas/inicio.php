<?php 
	
	include "../app/vistas/includes/librerias.php";
	include "../app/vistas/includes/header.php";
?>
<!DOCTYPE html>
<html>
<head>
  <title>Black & Whitte Bettas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" type="text/css" href="<?php echo RUTA_URL; ?>/library/bootstrap-4.5.0/css/bootstrap.min.css">
</head>
<body style="background: #dadadac9;">
	<?php 
		include "../app/vistas/includes/portada.php";
	?>
	<div class="container">
  	<?php 
  		/*$contentIndex = array(
  			array(
  			),
			array(
				"include"=>"../app/vistas/paginas/modules/carousel/carousel-single.php",
				"style"=>array(
					"http://localhost/WebPage/mvc/css/carousel/carousel-single/carousel-single.css"
				),
				"script"=>array(
					"http://localhost/WebPage/mvc/js/carousel/carousel-single/carousel-single.js"
				)
			)
		);*/
		include "../app/vistas/paginas/modules/carousel/carousel-single.php";
		include "../app/vistas/paginas/modules/categories/categories-gallery.php";
		include "../app/vistas/paginas/modules/reviews/social/social-reviews-single.php";
		include "../app/vistas/includes/cart/cart-single.php";
	?>
	</div>
	<?php 
	
		
		include "../app/vistas/includes/footer.php";
	?>
</body>
</html>
    
    
    
    
    
    
    
    