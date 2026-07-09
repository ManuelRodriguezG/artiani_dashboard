


<div class="row-categories-gallery">
	
	<div>
		<label class="label-categories-gallery">Categorias</label>
	</div>
	<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
		<div class="carousel-inner">
			<div class="carousel-item active">
				<div class="wrapper">

					<main class="content">
						<div class="feed-grid">

							<div class="card">
								<img src="image/products/calentador.jpg" alt="img">
								<div class="info-center">Calefacción</div>
							</div>
							<div class="card"><img src="image/products/adorno.jpg" alt="img">
								<div class="info-center">Ceramica</div>
							</div>
							<div class="card"><img src="image/products/filtro.jpg" alt="img">
								<div class="info-center">Filtros</div>
							</div>

							<div class="card"> 
								<img src="image/products/pecera.jpg" alt="img">
								<div class="info-center">Peceras</div>
							</div>

							<div class="card"> 
								<img src="image/portada/IMG_0045.jpg" alt="img">
								<div class="info-center">Bettas</div>
							</div>
							<div class="card">
								<img src="image/products/adorno.jpg" alt="img">
								<div class="info-center">Ceramica</div>
							</div>

						</div>
					</main>
				</div>
			</div>
			<div class="carousel-item ">
				<div class="wrapper">

					<main class="content">
						<div class="feed-grid">
							
							<div class="card">
								<img src="image/products/calentador.jpg" alt="img">
								<div class="info-center">Calefacción</div>
							</div>
							<div class="card"> 
								<img src="image/portada/IMG_0045.jpg" alt="img">
								<div class="info-center">Bettas</div>
							</div>
							<div class="card"> 
								<img src="image/products/pecera.jpg" alt="img">
								<div class="info-center">Peceras</div>
							</div>
							<div class="card">
								<img src="image/products/filtro.jpg" alt="img">
								<div class="info-center">Filtros</div>
							</div>
							<div class="card">
								<img src="image/products/adorno.jpg" alt="img">
								<div class="info-center">Ceramica</div>
							</div>
							<div class="card">
								<img src="image/products/adorno.jpg" alt="img">
								<div class="info-center">Ceramica</div>
							</div>

							
						</div>
					</main>
				</div>
			</div>
			
		</div>
		<a class="carousel-control-prev categories-gallery-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
			<i class="fas fa-chevron-left icon-categories-gallery"></i>
			<span class="sr-only">Previous</span>
		</a>
		<a class="carousel-control-next categories-gallery-control-next" href="#carouselExampleControls" role="button" data-slide="next">
			
			<i class="fas fa-chevron-right icon-categories-gallery"></i>
			<span class="sr-only">Next</span>
		</a>
	</div>
</div>
<script type="text/javascript">
	//width > 1200
	//max-width: 450px
	//min-width: 100px
	//height:150px

	//Extra small    < 576px
	//small          >= 576px
	//Medium         >= 768px
	//Large          >= 992px
	//Extra Large	   >= 1200px
	$(window).resize(function(){
		console.log($(window).width());
		var minimo,maximo,total;
		if($(window).width() >= 1200){
			minimo = 150;
			maximo = 333;
			total = 1000;
		}else if($(window).width() >= 992 && $(window).width() < 1200){
			minimo = 150;
			maximo = 266;
			total = 800;
		}else if($(window).width() >= 768 && $(window).width() < 992){
			minimo = 150;
			maximo = 200;
			total = 600;
		}else if($(window).width() >= 576 && $(window).width() < 768 && $(window).width() > 357){
			minimo = 200;
			maximo = 133;
			total = 400;
		}else if($(window).width() <= 357){
			minimo = 96;
			maximo = 96;
			total = 290;
		}

		var ancle = 1;
		var firstWidth = 0;
		var secondWidth = 0;
		var sumFirstWidth = 0;
		$(".feed-grid .card").each(function(){
			
		    var height = "150px";
		    //$(this).css("height",height);
		    //Math.floor(Math.random() * ((máximo+1) - mínimo) + mínimo)
		    if(ancle == 1 || ancle == 2){
		        firstWidth = Math.floor(Math.random() * ((maximo+1) - minimo) + minimo);
		console.log(firstWidth+"px");
		        $(this).css("width",firstWidth+"px");
		        sumFirstWidth += firstWidth;
		        ancle++;
		    }else if(ancle == 3){
		        secondWidth = total - sumFirstWidth;
		        console.log(secondWidth+"px");
		        $(this).css("width",secondWidth+"px");
		        ancle = 1;
				firstWidth = 0;
				secondWidth = 0;
				sumFirstWidth = 0;
		    }    
		    
		});
	})
	var minimo,maximo,total;
		if($(window).width() >= 1200){
			minimo = 150;
			maximo = 333;
			total = 1000;
		}else if($(window).width() >= 992 && $(window).width() < 1200){
			minimo = 150;
			maximo = 266;
			total = 800;
		}else if($(window).width() >= 768 && $(window).width() < 992){
			minimo = 150;
			maximo = 200;
			total = 600;
		}else if($(window).width() >= 576 && $(window).width() < 768 && $(window).width() > 357){
			minimo = 200;
			maximo = 133;
			total = 400;
		}else if($(window).width() <= 357){
			minimo = 96;
			maximo = 96;
			total = 290;
		}

		var ancle = 1;
		var firstWidth = 0;
		var secondWidth = 0;
		var sumFirstWidth = 0;
		$(".feed-grid .card").each(function(){
			
		    var height = "150px";
		    //$(this).css("height",height);
		    //Math.floor(Math.random() * ((máximo+1) - mínimo) + mínimo)
		    if(ancle == 1 || ancle == 2){
		        firstWidth = Math.floor(Math.random() * ((maximo+1) - minimo) + minimo);
		console.log(firstWidth+"px");
		        $(this).css("width",firstWidth+"px");
		        sumFirstWidth += firstWidth;
		        ancle++;
		    }else if(ancle == 3){
		        secondWidth = total - sumFirstWidth;
		        console.log(secondWidth+"px");
		        $(this).css("width",secondWidth+"px");
		        ancle = 1;
				firstWidth = 0;
				secondWidth = 0;
				sumFirstWidth = 0;
		    }    
		    
		});
</script>






<style type="text/css">
	.label-categories-gallery{
		font-family: Noto Sans;
		margin: 0;
		padding: 10px;
	}
	.row-categories-gallery{
		width: 100%;
	}
	.icon-categories-gallery{
		color: black;
	}
	.categories-gallery-control-prev{
		width: 45px;
		height: 60px;
		top: 50%;
		background: white;
		border-radius: 3px;
		box-shadow: 0px 0px 2px 0px rgba(0,0,0,0.3);
		transform: translateY(-50%);
	}

	.categories-gallery-control-next{
		width: 45px;
		height: 60px;
		top: 50%;
		background: white;
		border-radius: 3px;
		box-shadow: 0px 0px 2px 0px rgba(0,0,0,0.3);
		transform: translateY(-50%);
	}

	.wrapper {
		display: -webkit-box;
		display: flex;
		background-color: pink;
		-webkit-box-flex: 1;
		flex-grow: 1;
	}

	.content {
		width: 100%;
		
		background-color: #e1e1e1;
	}

	.feed-grid {
		display: -webkit-box;
		display: flex;
		max-width: 1170px;
		margin: 0 auto;
		-webkit-box-orient: horizontal;
		-webkit-box-direction: normal;
		flex-flow: row wrap;
		-webkit-box-pack: center;
		justify-content: center;
		align-content: flex-start;
	}

	.navigation {
		display: -webkit-box;
		display: flex;
		-webkit-box-align: center;
		align-items: center;
	}

	.logotype {
		display: -webkit-box;
		display: flex;
		width: 120px;
		min-width: 120px;
		height: 65px;
		color: #ffffff;
		background-color: #2196f3;
		font-weight: bold;
		-webkit-box-align: center;
		align-items: center;
		-webkit-box-pack: center;
		justify-content: center;
	}

	input[type="search"] {
		display: -webkit-box;
		display: flex;
		width: 300px;
		height: 15px;
		margin-right: 20px;
		padding: 10px;
		border: 1px solid #e6e6e6;
		border-radius: 5px;
		outline: none;
		font-size: 12px;
		-webkit-box-pack: end;
		justify-content: flex-end;
	}

	.categories {
		display: -webkit-box;
		display: flex;
		margin-left: 45px;
	}

	.category-item {
		margin: 0 10px;
	}

	.card {
		position: relative;
		width: 320px;
		height: 150px;
		margin: 7px;
		transition: 2s;
		background-color: #ffffff;
		box-shadow: 0px 0px 10px 0px rgba(110, 123, 140, 0.3);
		-webkit-box-flex: 1;
		flex: auto;
	}
	.card img {
		width: 100%;
		height: 100%;
		-o-object-fit: cover;
		object-fit: cover;
	}
	/*.card:nth-child(5) {
		-webkit-box-flex: 545px;
		flex: 545px;
	}*/

	.card-half {
		display: -webkit-box;
		display: flex;
		width: 320px;
		height: 320px;
		margin: 7px;
		background-color: #ffffff;
		box-shadow: 0px 0px 10px 0px rgba(110, 123, 140, 0.3);
		-webkit-box-orient: vertical;
		-webkit-box-direction: normal;
		flex-flow: column wrap;
		-webkit-box-pack: end;
		justify-content: flex-end;
		-webkit-box-flex: 1;
		flex: auto;
	}

	.wide {
		width: 480px;
	}

	.card-img {
		position: relative;
		height: 160px;
		-webkit-box-flex: 1;
		flex: auto;
	}
	.card-img img {
		width: 100%;
		height: 100%;
		-o-object-fit: cover;
		object-fit: cover;
	}

	.label {
		position: absolute;
		top: 20px;
		right: 20px;
		color: #fdd701;
		font-size: 20px;
	}

	.card-text {
		padding: 0 20px;
	}
	.card-text p {
		margin: 0;
		padding: 0;
		color: #888888;
	}

	.card-tools {
		display: -webkit-box;
		display: flex;
		height: 50px;
		padding: 15px 20px;
		-webkit-box-orient: horizontal;
		-webkit-box-direction: normal;
		flex-flow: row wrap;
		-webkit-box-align: end;
		align-items: flex-end;
	}

	.tools-item {
		margin-right: 20px;
		cursor: pointer;
		-webkit-transition: opacity 200ms ease;
		transition: opacity 200ms ease;
	}
	.tools-item:hover {
		opacity: 0.7;
	}

	.tools-count {
		padding: 0 5px;
		color: #888888;
		font-size: 14px;
	}

	.share {
		color: #333333;
	}

	.like {
		color: #df2324;
	}

	.info-center {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		display: -webkit-box;
		display: flex;
		padding: 0 10px;
		text-align: center;
		color: white;
		background: rgba(0, 0, 0, 0.3);
		text-shadow: 1px 1px 10px rgba(0, 0, 0, 0.2);
		font-size: 24px;
		font-weight: bold;
		-webkit-box-pack: center;
		justify-content: center;
		-webkit-box-align: center;
		align-items: center;
	}


	@media only screen and (max-width:992px){
		.card{
			width: 150px;
			height:150px;
		}
		.content{
			padding:0px !important;
		}
	}

</style>