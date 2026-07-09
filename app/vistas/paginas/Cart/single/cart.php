
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php 
	include "../app/vistas/includes/librerias.php";
	include "../app/vistas/includes/header.php";
	?>
</head>
<body>
	<div class="container">
		<div>
			<div style="display: flex;padding: 1rem;" class="">
				<div style="width: 200px;height: 200px;" class="">
					<img src="image/products/adorno.jpg" style="width: 100%;object-fit: contain;">
				</div>
				<div style="width: 100%;">
					<div>
						<div style="width: 100%;position: relative;">
							<label style="font-size: 18px;-webkit-line-clamp: 1;overflow: hidden;display: -webkit-box;-webkit-box-orient: vertical;width: 90%;">
							Figura de resina (Buzo)</label>
							<label style="color: gray;font-size: 14px;padding-left: 10px;float: right;position: absolute;top: 0;right: 0;">$65.00</label>
						</div>
					</div>
					<div style="width: 100%;height: 30px;">
						<div class="number">
							<button class="number__btn number__btn--down"></button>
							<input class="number__field" type="number" id="number1" min="1" step="1" value="0">
							<button class="number__btn number__btn--up"></button>
						</div>
					</div>
					<div>
						<button class="btn btn-primary" style="background: white;color: #007bff;border: none;outline: none;">Eliminar</button>
					</div>
				</div>
			</div>
			<div style="display: flex;padding: 1rem;" class="">
				<div style="width: 200px;height: 200px;" class="">
					<img src="image/products/Filtro_cascada_280L.png" style="width: 100%;object-fit: contain;">
				</div>
				<div style="width: 100%;">
					<div style="width: 100%;position: relative;">
						<label style="font-size: 18px;-webkit-line-clamp: 1;overflow: hidden;display: -webkit-box;-webkit-box-orient: vertical;width: 90%;">
						Filtro de Cascada 200L/H </label>
						<label style="color: gray;font-size: 14px;padding-left: 10px;float: right;position: absolute;top: 0;right: 0;">$350.00</label>
					</div>
					<div style="width: 100%;height: 30px;">
						<div class="number">
							<button class="number__btn number__btn--down"></button>
							<input class="number__field" type="number" id="number1" min="1" step="1" value="0">
							<button class="number__btn number__btn--up"></button>
						</div>
					</div>
					<div>
						<button class="btn btn-primary" style="background: white;color: #007bff;border: none;outline: none;">Eliminar</button>
					</div>
				</div>
			</div>
			<div style="display: flex;padding: 1rem;" class="">
				<div style="width: 200px;height: 200px;" class="">
					<img src="image/products/pecera.jpg" style="width: 100%;object-fit: contain;">
				</div>
				<div style="width: 100%;">
					<div style="width: 100%;position: relative;">
						<label style="font-size: 18px;-webkit-line-clamp: 1;overflow: hidden;display: -webkit-box;-webkit-box-orient: vertical;width: 90%;">
						Pecera de 50 cm</label>
						<label style="color: gray;font-size: 14px;padding-left: 10px;float: right;position: absolute;top: 0;right: 0;">$650.00</label>
					</div>
					<div style="width: 100%;height: 30px;">
						<div class="number">
							<button class="number__btn number__btn--down"></button>
							<input class="number__field" type="number" id="number1" min="1" step="1" value="0">
							<button class="number__btn number__btn--up"></button>
						</div>
					</div>
					<div>
						<button class="btn btn-primary" style="background: white;color: #007bff;border: none;outline: none;">Eliminar</button>
					</div>
				</div>
			</div>
		</div>
		
	</div>

</div>
</div>
<div class="container" style="position: sticky;bottom: 0;background: white;padding: 1rem;box-shadow: 0px -4px 5px -4px rgba(0,0,0,0.3);" id="content-total">
	<!-- Cantidad de Productos -->
	<div style="text-align: right;justify-content: right;">

		<label>Productos</label>
		<label>(3)</label>
	</div>
	<div style="text-align: right;">
		<label>Total:</label>
		<label>$1,200.00</label>
		<label id="position">$1,200.00</label>
	</div>
</div>
<style type="text/css">
	.number {
		display: inline-block;
		font-size: 0;
		background: #f2f2f2;
		height: 30px;
		float: left;
		margin-right: 10px;
		border-radius: 20px;
	}

	.number__field {
		-moz-appearance:textfield;
		display: inline-block;
		vertical-align: top;
		width: 55px;
		height: 30px;
		padding: 8px 15px;
		font-family: 'PT Sans', sans-serif;
		font-size: 14px;
		font-weight: 700;
		line-height: 26px;
		background: transparent;
		border: none;
		text-align: center;
		outline: none;
	}

	.number__btn {
		display: inline-block;
		vertical-align: top;
		position: relative;
		width: 30px;
		height: 30px;
		padding: 0;
		border: none;
		background: transparent;
	} 

	.number__btn--down::before {
		content: '';
		position: absolute; 
		display: block;
		left: 13px;
		top: 15px;
		height: 3px; 
		width: 10px;
		background: #a9a9a9;
	}
	.number__btn--up::before {
		content: '';
		position: absolute; 
		display: block;
		left: 10px;
		top: 15px;
		height: 3px; 
		width: 9px;
		background: #a9a9a9;
	}

	.number__btn--up::after {
		content: '';
		position: absolute; 
		display: block;
		left: 10px;
		top: 15px;
		height: 3px; 
		width: 9px;
		background: #a9a9a9;
		transform: rotate(90deg);
	}

	.number__field::-webkit-outer-spin-button,
	.number__field::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
	}
</style>
<script type="text/javascript">
	jQuery('.number').each(function() {
		var spinner = jQuery(this),
		input = spinner.find('.number__field'),
		btnUp = spinner.find('.number__btn.number__btn--up'),
		btnDown = spinner.find('.number__btn.number__btn--down'),
		min = input.attr('min'),
		max = input.attr('max'),
		step = input.attr('step'); 
		btnUp.click(function() {
			var oldValue = parseFloat(input.val());
			if (oldValue >= max) {
				var newVal = oldValue;
			} else {
				var newVal = oldValue + parseFloat(step);
			}
			spinner.find("input").val(newVal);
			spinner.find("input").trigger("change");
		});

		btnDown.click(function() {
			var oldValue = parseFloat(input.val());
			if (oldValue <= min) {
				var newVal = oldValue;
			} else {
				var newVal = oldValue - parseFloat(step);
			}
			spinner.find("input").val(newVal);
			spinner.find("input").trigger("change");
		});
	});
</script>
<script type="text/javascript">
	console.log($(window).scrollTop());
	console.log($("#content-total")[0].offsetTop);
	console.log($("#content-total")[0].offsetTop - $(window).scrollTop() );
	var diff = $("#content-total")[0].offsetTop - $(window).scrollTop();
	$(window).scroll(function(){
		console.log($(window).scrollTop());
	console.log($("#content-total")[0].offsetTop);
	console.log($("#content-total")[0].offsetTop - $(window).scrollTop() );
		var newdiff = $("#content-total")[0].offsetTop - $(window).scrollTop();
		if(newdiff < diff){
			$("#content-total").css("box-shadow","none");
		}else{
			$("#content-total").css("box-shadow","0px -4px 5px -4px rgba(0,0,0,0.3)");
		}
	});
</script>
<?php 
	include "../app/vistas/includes/footer.php";
?>
</body>
</html>

