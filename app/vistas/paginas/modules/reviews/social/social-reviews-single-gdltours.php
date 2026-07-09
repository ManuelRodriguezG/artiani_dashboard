<?php
	/**
	* Este documento require de headComentarios.php
	*/ 
	include 'headComentarios.php';
	date_default_timezone_set('America/Mexico_City');
	$idPage = 'panoramexgdl';
	$key = 'EAAHeZC0qwxsIBAEW5rup3CB2rdXZB6am0nu2tZAeM4EeSeMjKZBfcvUTaVX8RhprvzI7JdhqM4Ueu5d4cZCI1ZCGXZAquXwMLask4kWiWaZAZAyQ1A436MX10rSuQttbn7prut0KdBRRtANzznppCrZAqj1plZAvKgxQj6O6ZBZCjXFKZCDFHannTAvTGr';
	$post = 'https://graph.facebook.com/v7.0/'.$idPage.'?fields=engagement,picture,overall_star_rating,ratings{open_graph_story}'.'&'.'access_token='.$key;
//$contenidoFacebook = file_get_contents($post);
//$json = json_decode($contenidoFacebook);
	$servidor = $_SERVER['SERVER_NAME'];
	if ($servidor != 'localhost') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $post);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($ch);
		curl_close ($ch);
		$json = json_decode($json);
	}else{
		$contenidoFacebook = file_get_contents($post);
		$json = json_decode($contenidoFacebook);
	}

file_put_contents("a_comentarios.log",json_encode($json));

	function comentarios($json){
		foreach ($json as $clavePrimerNivel => $valoresPrimerNivel) {
			if ($clavePrimerNivel == 'engagement') {
				foreach ($valoresPrimerNivel as $claveSegundoNivel  => $valoresSegundoNivel) {
					if ($claveSegundoNivel == 'social_sentence') {
						$socialSentence = $valoresSegundoNivel;
					}
				}
			}elseif ($clavePrimerNivel == 'picture') {
				foreach ($valoresPrimerNivel as $claveSegundoNivelPicture  => $valoresSegundoNivelPicture) {
					if ($claveSegundoNivelPicture == 'data') {
						foreach ($valoresSegundoNivelPicture as $claveTercerNivelPicture => $valoresTercerNivelPicture) {
							if ($claveTercerNivelPicture == 'url') {
								$urlPicture = $valoresTercerNivelPicture;
							}
						}
					}
				}
			}elseif ($clavePrimerNivel == 'overall_star_rating') {
				$ratingGeneral = $valoresPrimerNivel;
			}elseif ($clavePrimerNivel == 'ratings') {
				foreach ($valoresPrimerNivel as $claveSegundoNivelRatings => $valoresSegundoNivelRatings) {
					if ($claveSegundoNivelRatings == 'data') {
						foreach ($valoresSegundoNivelRatings as $claveTercerNivelRatings => $valoresTercerNivelRatings) {
							foreach ($valoresTercerNivelRatings as $claveCuartoNivelRatings => $valoresCuartoNivelRatings) {
								if ($claveCuartoNivelRatings == 'open_graph_story') {
									foreach ($valoresCuartoNivelRatings as $claveQuintoNivelRatings => $valoresQuintoNivelRatings) {
										if ($claveQuintoNivelRatings == 'id') {
											$idPost = $valoresQuintoNivelRatings;
										}elseif ($claveQuintoNivelRatings == 'message') {
											$mensaje = $valoresQuintoNivelRatings;
										}elseif ($claveQuintoNivelRatings == 'start_time') {
											$fechaPost = $valoresQuintoNivelRatings;
											$fechaPost = date_create($fechaPost);
											$fechaPost = date_format($fechaPost, "Y-m-d H:i:s");
											$hoy = date("Y-m-d H:i:s");
											$fechaPost = dateDiff($fechaPost, $hoy);
//Get number of days deference between current date and given date.											
										}elseif ($claveQuintoNivelRatings == 'data') {
											foreach ($valoresQuintoNivelRatings as $claveSextoNivelRatings => $valoresSextoNivelRatings) {
												if ($claveSextoNivelRatings == 'recommendation_type') {
													$tipoMensaje = $valoresSextoNivelRatings;
												}
											}
										}
									}
								}
							}
						//$postsFacebook[] = array('idPost' =>$idPost,'mensaje'=>$mensaje, 'fechaPost'=>$fechaPost,'tipoMensaje'=>$tipoMensaje);
							if ($tipoMensaje == 'positive') {

								$postsFacebook[] = "<div class='item itemM'>

								<div class='row rowM rowComentarioM'>

								<div class='col contenedorTextoMensajeM '>
								<p class='textoMensajeM'>".$mensaje."</p>

								</div>

								</div>
								<div class='row rowM rowFooterItemM'>
								<div class='contenedorIconFacebookM'>
								<i class='fab fa-facebook-square fabM'></i>
								<div class='text-hide'>Comentario Facebook</div>
								</div>
								<div class='fechaPublicacionM'>
								<a class='efectoAM' target='_blank' rel='noopener' href='https://www.facebook.com/".$idPost."' >".$fechaPost."</a>
								</div>
								</div>

								</div>";
							}
						}
					}
				}
			}
		}
		
		$width = ($ratingGeneral/5)*100;

	//$ArregloComentarios = array('urlPicture' => $urlPicture,'socialSentence' => $socialSentence, 'ratingGeneral' => $ratingGeneral, $postsFacebook);
		$postPrincipal = "<div class='item itemM '>
		<div class='row rowM '>
		<div class='row rowM contenedorheaderFacebookM'>
		<div class=' contenedorImagenFacebookM col'>
		<a href='https://www.facebook.com/panoramexgdl/' target='_blank' rel='noopener'>
		<img class='imagenFacebookM' src='".$urlPicture."' alt='Imagen Panoramex Facebook'>
		</a>
		</div>
		<div class=' col'>
		<div class='col-xs-6 contenedorIframeM'>
		<iframe  title='Icono Me gusta Facebook' src='https://www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fpanoramexgdl%2F&width=79&layout=button&action=like&size=small&show_faces=false&share=false&height=65&appId=526663027836610' width='79' height='65' style='border:none;overflow:hidden' scrolling='no' frameborder='0' allowTransparency='true' allow='encrypted-media'></iframe>
		</div>
		<div class='col-xs-6'>
		<p class='contenedorLikeThisM'>".$socialSentence."</p>		
		</div>

		</div>

		</div>
		<div class='row rowM contenedorRatingM'>

		<div>
		<p class='contenedorRatingGeneralM'>".$ratingGeneral."</p>
		</div>

		<div class='contenedorStars1M'>

		<i class='far fa-star'></i>
		<i class='far fa-star'></i>
		<i class='far fa-star'></i>
		<i class='far fa-star'></i>
		<i class='far fa-star'></i>
		<div class='contenedorStarsM' style='width:".$width."%;'>

		<i class='fas fa-star'></i>
		<i class='fas fa-star'></i>
		<i class='fas fa-star'></i>
		<i class='fas fa-star'></i>
		<i class='fas fa-star'></i>
		</div>
		</div>					

		</div>
		</div>

		</div>";
		//Descomentar esta linea y comentar la de abajo para cuando se vuelva acomodar las recomendaciones en la parte de abajo de la página
		//echo "<section class='seccionComentariosM pb-5'>
		echo "<section class='seccionComentariosM '>
		<div class='owl-carouselM owl-carousel owl-theme  '>";
		echo $postPrincipal;
		foreach ($postsFacebook as $value) {
			echo $value;
		}
		echo "</div>
	
		</section>";
	}
	$respuesta = comentarios($json);
	function dateDiff($start, $end) {
		$start_ts = strtotime($start);
		$end_ts = strtotime($end);
		$diff = $end_ts - $start_ts;
		if (round($diff / 86400) == 0) {
			$horaStart = date_create($start);
			$horaStart = date_format($horaStart,"H");
			$horaEnd = date_create($end);
			$horaEnd = date_format($horaEnd,"H");
			$difHora = $horaEnd - $horaStart+6;

			if ($difHora == 0) {
				$minutoStart = date_create($start);
				$minutoStart = date_format($minutoStart,"i");
				$minutoEnd = date_create($end);
				$minutoEnd = date_format($minutoEnd,"i");
				$difMinuto = $minutoEnd - $minutoStart;
				if ($difMinuto == 0) {
					$seguntoStart = date_create($start);
					$seguntoStart = date_format($seguntoStart,"s");
					$segundoEnd = date_create($end);
					$segundoEnd = date_format($segundoEnd,"s");
					$difSegundo = $segundoEnd - $seguntoStart;
					if ($difSegundo == 0) {
						return "Justo Ahora";
					}else{
						if ($difSegundo == 1) {
							return "Hace ".$difSegundo." segundo";
						}else{
							return "Hace ".$difSegundo." segundos";
						}
					}
				}else{
					if ($difMinuto == 1) {
						return "Hace ".$difMinuto." minuto";
					}else{
						return "Hace ".$difMinuto." minutos";
					}
				}
			}else{
				if ($difHora == 1) {
					return "Hace ".$difHora." hora";
				}else{
					return "Hace ".$difHora." horas";
				}
			}
		}else{
			if (round($diff / 86400) >= 30 && round($diff / 86400) <= 31) {
				return "Hace un mes";
			}elseif(round($diff / 86400) >= 32 && round($diff / 86400) <= 61){
				return "Hace dos meses";
			}elseif (round($diff / 86400)<30) {
				return "Hace ".round($diff / 86400)." dias";
			}else{
				$fecha = date_create($start);
				$fecha = date_format($fecha,"d-m-Y");
				return $fecha;
			}
		}
	}

	echo "<script type='text/javascript'>
	jQuery(document).ready(function($) {
	$('.owl-carouselM').owlCarousel({
		loop:true,
		margin:10,
		responsiveClass:true,
		responsive:{
			0:{
				items:1,
				nav:true
			},
			600:{
				items:3,
				nav:false
			},
			1000:{
				items:5,
				nav:true,
				loop:true
			}
		}
	});
	
	$('.custom1').owlCarousel({
        loop: true,
        margin: 10,
        autoWidth:true,
        autoplay: true,
        autoplayTimeout: 6000,
        autoplayHoverPause: true,
        responsiveClass:true,
		responsive:{
			0:{
				items:1,
				nav:true
			},
			600:{
				items:3,
				nav:false
			},
			1000:{
				items:5,
				nav:true,
				loop:true
			}
		}
	});
	
	});
	

	
	$('.owl-prev').append('<div class='+'text-hide'+'>'+'Anterior'+'</div>');
	$('.owl-next').append('<div class='+'text-hide'+'>'+'Siguiente'+'</div>');
	</script>";
	?>
	<?php
	echo "	
	<!-- Owlcarousel -->
	<link rel='stylesheet' type='text/css' href='https://www.gdltours.com/Comentarios/librerias/owlcarousel/owl.carousel.min.css'>
	<link rel='stylesheet' type='text/css' href='https://www.gdltours.com/Comentarios/librerias/owlcarousel/owl.theme.default.min.css'>
	
	<!-- Jquery -->
	<script type='text/javascript' src='https://www.gdltours.com/Comentarios/librerias/owlcarousel/owl.carousel.min.js'></script>
	<!-- fontawesome -->
	<link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.6.3/css/all.css' integrity='sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/' crossorigin='anonymous'>
	<!-- Css -->
	<link rel='stylesheet' type='text/css' href='https://www.gdltours.com/Comentarios/css/styleComents.css'>
	";
?>
/* Import*/
@import url('https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300');
@import url('https://fonts.googleapis.com/css?family=Nunito');
section{
	
}
.seccionComentariosM{
        background-color: #dadbdd;
}
.rowM{
	margin: 0;
	padding: 3px;

}
.owl-carouselM{
	padding: 10px;
}
.itemM{
	background-color: white;
	-webkit-box-shadow: 7px 10px 5px -6px rgba(0,0,0,0.22);
	-moz-box-shadow: 7px 10px 5px -6px rgba(0,0,0,0.22);
	box-shadow: 7px 10px 5px -6px rgba(0,0,0,0.22);
	height: 160px;
	border-radius: 10px;
}
.owl-stage{
	height: 165px;
}

.owl-carousel .owl-item img {
	padding: 0;
	
	height: 70px;
	width: 70px;
border-radius: 100%;
	

}
.contenedorRatingM{
	justify-content:right;
	padding-top: 60px;

	
}
.contenedorheaderFacebookM{
	height: 50px;
	width: 100%;
}
.contenedorPrincipalFacebookM{
	height: 160px;
}
.efectoAM{
	color: #4267b2;
}


.likeM{
	padding: 10px;
}
.contenedorStars1M{
	padding-top: 10px;
	display: inline-block;
	position: relative;
}
.contenedorStars1M::before{
	
}
.contenedorStarsM{
	padding-top: 10px;
	position: absolute;
	top: 0;
	left: 0;
	overflow: hidden;
	white-space: nowrap;  
	color: #f8ce0b;
	width: 95%;
}
.contenedorIframeM{
	height: 20px;
}

.contenedorRatingGeneralM{
	padding-top: 10px;
	padding-right: 3px; 
}
.tituloFacebookM{
	font-size: 15px;
	font-family: 'Open Sans Condensed', sans-serif;
}
.contenedorLikeThisM{
	padding-left: 5px;
	font-size: 13px;
	

	font-family: 'Arimo', sans-serif;


}
.contenedorIconFacebookM{
	
	
	padding-top: 2px;
	color: blue;
	
	width: 25px;
	height: 25px;
	
	}
.fabM{
	
	color: #4267b2;
	width: 20px;
	height: 20px;
	
	font-size: 20px;
	
	

}
.fechaPublicacionM{
	flex: none;
	text-align: right;
	padding-left: 30px;
}
.rowComentarioM{
	overflow:hidden;
	height: 123px;

	
}
.iconFaceM{
	padding: 0;
}
.contenedorTextoMensajeM{    
	height: 10px;
	width: 100%;



}
.textoMensajeM{

	width: 100%;
	margin:0;
	position: absolute;
	left: 0;
	top: 0;
	right: -20px;
	bottom: 0;
	padding: 5px;
	overflow-y: scroll;
	height: 123px;
	font-family: 'Nunito', sans-serif;
	font-size: 15px;
}
.rowFooterItemM{
	padding-left: 10px;
	justify-content:center;
}

/*Modificaciones a la libreria*/

.owl-next span{
	color: black;
	font-size: 50px; 
}
.owl-next{
	position: absolute;
	top: 27px;
	right: 0;
	width: 40px;
	margin:0;
	
}

.owl-prev span{
	color: black;
	font-size: 50px; 
}
.owl-prev{

	position: absolute;
	top: 27px;
	left:0;
	width: 40px;
	margin:0;
	
}
.owl-theme .owl-nav [class*="owl-"]{
margin:0;
}
.owl-theme .owl-nav [class*="owl-"]:hover{
	background-color: rgba(248,249,250,0.5);
	
}
.owl-dots{
	display: none;
}