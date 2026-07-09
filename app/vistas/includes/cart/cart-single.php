<div class="container-cart-single" id="cart-single">
	<div class="container-button-cart">
		<button class="btn-icon-cart" style="position: relative;">
			<i class="fas fa-shopping-cart"></i>
			<label  class="label_cant">3</label>
			<label class="label_total">
				$590.00
			</label>
		</button>

	</div>
	<style type="text/css">
		.no_products{
			text-align: center;
		}
		.label_no_products{
			margin: 0;
			padding: 10px;
		}
	</style>
	<div class="container-info-cart">
		<div class="no_products">
			<label class="label_no_products">No se han agregado productos</label>
		</div>
		<div class="container-items">
		<!--<div class="container-item-cart">
				<div class="icon-trash-container">
					<label class="label-icon-trash icon-trash">
						<i class="fas fa-trash-alt icon-trash"></i>
					</label>
				</div>
				<div>
					<img src="http://localhost/WebPage/mvc/image/products/comida_hamster.png" class="image-cart">
				</div>
				<div class="container-info-item">

					<div class="info-item">
						<label>Alimento para Hámster</label>
						<label class="label_total_item">$65</label>
					</div>
					<div class="container-cant-item">
						<div class="number">
							<input class="number__field" type="number" id="number1" min="1"  step="1" value="2">
						</div>
						<label class="label_price_item">$65.00</label>
					</div>

				</div>
			</div>
			<div class="container-item-cart">
				<div class="icon-trash-container">
					<label class="label-icon-trash icon-trash">
						<i class="fas fa-trash-alt icon-trash"></i>
					</label>
				</div>
				<div>
					<img src="http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm.png" class="image-cart">
				</div>
				<div class="container-info-item">

					<div class="info-item">
						<label>Pecera Panorámica 5L</label>
						<label class="label_total_item">$65</label>
					</div>
					<div class="container-cant-item">
						<div class="number">
							<input class="number__field" type="number" id="number1" min="1"  step="1" value="2">
						</div>
						<label class="label_price_item">$65.00</label>
					</div>

				</div>
			</div>
			<div class="container-item-cart">
				<div class="icon-trash-container">
					<label class="label-icon-trash icon-trash">
						<i class="fas fa-trash-alt icon-trash"></i>
					</label>
				</div>
				<div>
					<img src="http://localhost/WebPage/mvc/image/products/calentador.jpg" class="image-cart">
				</div>
				<div class="container-info-item">

					<div class="info-item">
						<label>Calentador 200W</label>
						<label class="label_total_item">$65</label>
					</div>
					<div class="container-cant-item">
						<div class="number">
							<input class="number__field" type="number" id="number1" min="1"  step="1" value="2">
						</div>
						<label class="label_price_item">$65.00</label>
					</div>

				</div>
			</div>-->
		</div>
		<div class="container-btns">
			<div class="container-btns-cart">
				<button class="btn btn-primary btns_footer_cart btn-clean-shopping">Vaciar</button>
				<button class="btn btn-primary btns_footer_cart btn-continue-shopping">Ver carrito</button>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		//$(".label-icon-trash").click(function(){
		//	console.log("Change icon");
		//	if($(this).hasClass("icon-trash")){
		//		$(this).html('<i class="fas fa-times icon-times"></i>');
		//		$(this).addClass("icon-times");
		//		$(this).removeClass("icon-trash");
		//	}else if($(this).hasClass("icon-times")){
		//		$(this).html('<i class="fas fa-trash-alt icon-trash"></i>');
		//		$(this).addClass("icon-trash");
		//		$(this).removeClass("icon-times");
		//	}
		//	//setTimeout(function(){
		//	//	$(this).html('<i class="fas fa-trash-alt icon-trash"></i>');
		//	//	$(this).addClass("icon-trash");
		//	//	$(this).removeClass("icon-times");
		//	//},2000);
		//});
	})
</script>
<style type="text/css">

	
	.container-items{
		display: none;
		max-height: 288px;
		overflow-y: auto;
		transition: 2s;
	}
	.container-btns{
		display: none;
	}
	.info-item{
		display: block;
	}
	.label_total_item{
		margin: 0;
		color: gray;
		font-size: 13px;
		padding-left: 5px;
		float: right;
	}
	.container-cant-item{
		display: block;
		text-align: center;
	}
	.label_price_item{
		float: right;
	}
	.container-info-item{
		padding-left: 10px;
		width:100%;
	}
	.image-cart{
		width: 50px;
		height: 50px;
		object-fit: contain;
	}
	.label_total{
		margin: 0;
		cursor: pointer;
		color: black;
		font-size: 13px;
		position: relative;
		padding: 5px;
		display: none;
	}
	.label_cant{
		position: absolute;
		display: none;
		cursor: pointer;
		margin: 0;
		top: 0;
		background: black;
		padding: 0px 3px;
		color: white;
		right: 8px;
		border-radius: 0px 0px 3px 3px;
	}
	.btns_footer_cart:hover{
		background: #b0ded3;
		border: 2px solid #b0ded3;
	}
	.btns_footer_cart{
		border: 2px solid #b0ded3;
		border-radius: 20px;
		background: white;
		color: gray;
		margin: 10px;
	}
	.container-btns-cart{
		text-align: center;
	}
	.icon-trash{
		padding-top: 13px;
		cursor: pointer;
		color: #007bff;
	}
	.icon-times{
		padding-top: 13px;
		cursor: pointer;
		color: #dc3545;
		font-size:21px;
	}
	.label-icon-trash{
		padding: 10px 10px 10px 10px;
		margin: 0;
	}
	.icon-trash-container{
		background: white;

	}
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


	input[type="checkbox"].switch_1{
		-webkit-appearance: none;
		-moz-appearance: none;
		float: right;
		appearance: none;
		width: 3.5em;
		height: 1.5em;
		background: #ddd;
		border-radius: 3em;
		position: relative;
		cursor: pointer;
		outline: none;
		-webkit-transition: all .2s ease-in-out;
		transition: all .2s ease-in-out;
	}

	input[type="checkbox"].switch_1:checked{
		background: #0ebeff;
	}

	input[type="checkbox"].switch_1:checked + .number{
		display: none;
	}

	input[type="checkbox"].switch_1:after{
		position: absolute;
		content: "";
		width: 1.5em;
		height: 1.5em;
		border-radius: 50%;
		background: #fff;
		-webkit-box-shadow: 0 0 .25em rgba(0,0,0,.3);
		box-shadow: 0 0 .25em rgba(0,0,0,.3);
		-webkit-transform: scale(.7);
		transform: scale(.7);
		left: 0;
		-webkit-transition: all .2s ease-in-out;
		transition: all .2s ease-in-out;
	}

	input[type="checkbox"].switch_1:checked:after{
		left: calc(100% - 1.5em);
	}
	@media only screen and (max-width: 1200px) {
		.container-items{
			max-height: 192px;
		}
	}
</style>
<script type="text/javascript">
	
	/*jQuery('.number').each(function() {
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
	});*/
</script>
<style type="text/css">
	.container-cart-single{
		position: fixed;
		top: 40vh;
		right: 0;
		z-index: 10;
	}
	.container-button-cart{
		width: 100%;
		margin-bottom: 2px;
		justify-content: right;
		text-align: right;
	}
	.btn-icon-cart{
		padding: 1rem;
		box-shadow: 0px 1px 2px 0px rgb(136 136 136 / 30%);
		border: none;
		background: white;
		outline: none !important;
	}
	.show-info-cart{
		transform:translateX(0px) !important;

	}
	.container-info-cart{
		background:white;
		display: none;
		transform:translateX(500px);
		transition: 1s;
		max-width: 250px;
	}
	.container-item-cart{
		position: relative;
		display: flex;
		padding: 1rem;
		transition: 2s;
	}
	.show_section_cart{
		display: inherit !important;s
	}
	.effect-new-item{
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		background: #94949485;
		position: absolute;
		transition: 2s;
		opacity: 1;
	}

	.effect-delete-item{
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		background: #94949485;
		position: absolute;
		transition: 2s;
		opacity: ;
	}

	.show-effect{
		opacity: 0;
	}
</style>

<script type="text/javascript">
	//$(".btn-icon-cart").click(function(){
	//	if($(".container-info-cart").hasClass("show-info-cart")){
	//		$(".container-info-cart").removeClass("show-info-cart");
	//	}else{
	//		$(".container-info-cart").addClass("show-info-cart");
	//	}
	//})
</script>
<script type="text/javascript">
	//local Storage
	//localStorage["cart-single"]
	class Cart{
		constructor(data = null){
			console.log(!localStorage["cart"]);
			var defaults = {
				total:0,
				data:null,
				discount:0,
				items_cant:0,
				url_cart:"http://localhost/WebPage/mvc/cart"
			};
			
			if(!localStorage["cart"] && data != null){
				this.options = $.extend({},defaults,data);
				localStorage["cart"] = JSON.stringify(this.options);
			}else if(data != null){
				this.options = $.extend({},defaults,data);
				localStorage["cart"] = JSON.stringify(this.options);
			}else if(localStorage["cart"]){
				this.options = JSON.parse(localStorage["cart"]);
			}else{
				this.options = defaults;
				localStorage["cart"] = JSON.stringify(defaults);
			}
			
			this.general_events();
			this.ready();
			this.updateCart();
			this.updateHeadCart();
		}

		addItem(data){
			console.log(localStorage["cart"]);
			console.log(this.options.data == null);
			if (this.options.data == null) {
				var newData = [];
				newData.push(data);
				this.options.data = newData;
				console.log(this.options);
				this.updateHeadCart();
				this.addItemCode(data);
				this.showItemsCart();
			}else{
				console.log(this.options);
				this.options.data.push(data);
				this.updateHeadCart();
				this.addItemCode(data);
				this.showItemsCart();
			}
		}

		addItemCode(data,efect = true){
			console.log(data);
			
			var code = 
			'<div class="container-item-cart">'+
				'<div class="icon-trash-container">'+
					'<label class="label-icon-trash icon-trash" id="item-'+data.item_id+'">'+
						'<i class="fas fa-trash-alt icon-trash"></i>'+
					'</label>'+
				'</div>'+
				'<div>'+
					'<img src="'+data.url_image+'" class="image-cart">'+
				'</div>'+
				'<div class="container-info-item">'+
					'<div class="info-item">'+
						'<label>'+data.item_name+'</label>'+
						'<label class="label_total_item">'+this.numberFormat(data.item_price)+'</label>'+
					'</div>'+
					'<div class="container-cant-item">'+
						'<div class="number">'+
							'<button class="number__btn number__btn--down" id="button-down-'+data.item_id+'"></button><input class="number__field" type="number" id="number1" min="1" step="1" value="'+data.quantity+'"><button class="number__btn number__btn--up" id="button-up-'+data.item_id+'"></button>'+
						'</div>'+
						'<label class="label_price_item">'+this.numberFormat(data.item_price*data.quantity)+'</label>'+
					'</div>'+
				'</div>'+
			'</div>';
			$(".container-items").append(code);
			
			$("#item-"+data.item_id)[0].addEventListener("click",this.deleteItem);
			$("#button-up-"+data.item_id)[0].addEventListener("click",this.buttonUp);
			$("#button-down-"+data.item_id)[0].addEventListener("click",this.buttonDown);
			if(efect == true){
				if(!$(".container-info-cart").hasClass("show-info-cart")){
					$(".btn-icon-cart").trigger("click");
				}
				
				this.showEffectItemAdd($("#item-"+data.item_id)[0]);
			}

		}

		buttonUp(){
			console.log(this);
			var $this,data,idItem,element;
			$this = window["cart"];
			$this.btnsControl(this.parentNode,"up");
			console.log($this.options);
			console.log(this.id.substring(10));
			data = $this.options.data;
			console.log(data);
			idItem = this.id.substring(10);
			element = this;
			data.forEach(function(elemento,index,data){
				if(elemento.item_id == idItem){
					elemento.quantity = elemento.quantity + 1;
					$(element.parentNode.parentNode).find(".label_price_item").text($this.numberFormat(elemento.quantity*elemento.item_price));
				}
			});
			console.log(data);
			$this.options.data = data;
			localStorage["cart"] = JSON.stringify($this.options);
			$this.updateHeadCart();
		}

		buttonDown(){
			console.log(this);
			var $this,data,idItem,element;
			$this = window["cart"];
			$this.btnsControl(this.parentNode,"down");
			console.log($this.options);
			console.log(this.id.substring(12));
			data = $this.options.data;
			console.log(data);
			idItem = this.id.substring(12);
			element = this;
			data.forEach(function(elemento,index,data){
				if(elemento.item_id == idItem){
					console.log(elemento.quantity);
					if(elemento.quantity > 1){
						elemento.quantity = elemento.quantity - 1;
						$(element.parentNode.parentNode).find(".label_price_item").text($this.numberFormat(elemento.quantity*elemento.item_price));
					}
				}
			});
			console.log(data);
			$this.options.data = data;
			localStorage["cart"] = JSON.stringify($this.options);
			$this.updateHeadCart();
		}

		btnsControl(elemento,direction){
			//jQuery('.number').each(function() {
				var spinner = jQuery(elemento),
				input = spinner.find('.number__field'),
				btnUp = spinner.find('.number__btn.number__btn--up'),
				btnDown = spinner.find('.number__btn.number__btn--down'),
				min = input.attr('min'),
				max = input.attr('max'),
				step = input.attr('step'); 
				if(direction == "up"){
					var oldValue = parseFloat(input.val());
					if (oldValue >= max) {
						var newVal = oldValue;
					} else {
						var newVal = oldValue + parseFloat(step);
					}
					spinner.find("input").val(newVal);
					spinner.find("input").trigger("change");
				}else if(direction == "down"){
					var oldValue = parseFloat(input.val());
					if (oldValue <= min) {
						var newVal = oldValue;
					} else {
						var newVal = oldValue - parseFloat(step);
					}
					spinner.find("input").val(newVal);
					spinner.find("input").trigger("change");
				}
				
			//});
		}

		showEffectItemAdd(elemento){
			var div = document.createElement("div");
				div.classList.add("effect-new-item");
			$(elemento.parentNode.parentNode).append(div);
			setTimeout(function(){
				div.classList.add("show-effect");
			},1000);
			
			setTimeout(function(){
				div.remove();
			},3000);
		}

		deleteItem(){
			var $this,
			$this = window["cart"];
			console.log("delete item");
			if($(this).hasClass("icon-trash")){
				$(this).html('<i class="fas fa-times icon-times"></i>');
				$(this).addClass("icon-times");
				$(this).removeClass("icon-trash");
			}else if($(this).hasClass("icon-times")){
				$(this).html('<i class="fas fa-trash-alt icon-trash"></i>');
				$(this).addClass("icon-trash");
				$(this).removeClass("icon-times");
				console.log(this.id);
				var data = $this.options.data;
				var id = this.id.substring(5);
				console.log(id);

				data.forEach(function(element,index,data){
					console.log(element);
					console.log(element.item_id);
					if(element.item_id == id){
						data.splice(index,1);

					}
				});
				console.log(data);
				if(data.length == 0){
					$this.options.data = null;
					$this.updateHeadCart();
					localStorage["cart"] = JSON.stringify($this.options);
					$this.hideEffectItemAdd(this);
					$this.hideItemsCart();
				}else{
					$this.options.data = data;
					$this.updateHeadCart();
					localStorage["cart"] = JSON.stringify($this.options);
					$this.hideEffectItemAdd(this);
				}
			}
		}

		hideEffectItemAdd(elemento){
			
			$(elemento.parentNode.parentNode)[0].classList.add("show-effect");
			setTimeout(function(){
				$(elemento.parentNode.parentNode).remove();
			},3000);
		}

		/*
			Update price and items cant
		*/
		updateHeadCart(){
			var data = this.options.data;
			var total = 0;
			var items_cant = 0;
			if(data != null){

			
			
				[].forEach.call(data,function(elemento){
					total += (elemento.item_price * elemento.quantity);
					items_cant++;
				});

				if(total > 0){
					this.options.items_cant = items_cant;
					this.options.total = total;
					$(".label_cant").text(this.options.items_cant);
					$(".label_total").text(this.numberFormat(this.options.total));
					localStorage["cart"] = JSON.stringify(this.options);
					this.showInfoHead();
				}
			}else{
				this.options.items_cant = items_cant;
				this.options.total = total;
				$(".label_cant").text(this.options.items_cant);
				$(".label_total").text(this.numberFormat(this.options.total));
				this.hideInfoHead();
			}
			console.log(this.options);
		}

		hideItemsCart(){
			$(".no_products").css("display","inherit");
			$(".container-items").removeClass("show_section_cart");
			$(".container-btns").removeClass("show_section_cart");
		}

		showItemsCart(){
			$(".no_products").css("display","none");
			$(".container-items").addClass("show_section_cart");
			$(".container-btns").addClass("show_section_cart");
		}

		hideInfoHead(){
			$(".label_cant").removeClass("show_section_cart");
			$(".label_total").removeClass("show_section_cart");
		}

		showInfoHead(){
			$(".label_cant").addClass("show_section_cart");
			$(".label_total").addClass("show_section_cart");
		}

		updateCart(){
			
			console.log(this.options);
			if(this.options.data != null){
				var data,$this;
				data = this.options.data;
				console.log(data);
				$this = this;
				data.forEach(function(elemento,index,data){
					console.log(elemento);
					$this.addItemCode(elemento,false);
					//$this.updateHeadCart();
					
				});
				$this.showItemsCart();
			}
		}

		ready(){
			var $this = this; 
			$(document).ready(function(){
				$(".container-info-cart").css("display","inherit");
			});
			//asign url cart button
			$(".btn-continue-shopping").click(function(){
				window.location = $this.options.url_cart;
			});
			//asign event clean shooping cart
			$(".btn-clean-shopping").click(function(){
				$this.cleanCart();
			});

		}

		cleanCart(){
			var $this = this;
			var defaults = {
				total:0,
				data:null,
				discount:0,
				items_cant:0,
				url_cart:"http://localhost/WebPage/mvc/cart"
			};
			this.options = defaults;
			localStorage["cart"] = JSON.stringify(this.options);
			$(".container-items")[0].classList.add("show-effect");
			setTimeout(function(){
				$(".container-items").remove();
				$this.hideItemsCart();
				$this.updateHeadCart();
			},3000);
		}

		numberFormat(number){
			var formato = { style: 'currency', currency: 'USD' };
	    	var getFormat = new Intl.NumberFormat('en-US', formato);
			return getFormat.format(number);
		}
		

		general_events(){
			/*
				Cart information container transition
				*/
				$(".btn-icon-cart").click(function(){
					//Extra small    < 576px
					//small          >= 576px
					//Medium         >= 768px
					//Large          >= 992px
					//Extra Large	   >= 1200px
					console.log($(document).width());
					var width = $(document).width();
					/*--- SMALL ---*/
					if(width <= 576){
						$(".container-info-cart").css("width",+width+"px");
						$(".container-info-cart").css("max-width",+width+"px");
						if($(".container-info-cart").hasClass("show-info-cart")){
							$(".container-info-cart").removeClass("show-info-cart");
							$(".container-info-cart").css("transform","translateX("+width+"px)");
						}else{
							$(".container-info-cart").addClass("show-info-cart");
						}
						/*--- MEDIUM - EXTRA LARGE ---*/
					}else if(width > 576){
						$(".container-info-cart").css("width","auto");
						$(".container-info-cart").css("max-width","500px");
						if($(".container-info-cart").hasClass("show-info-cart")){
							$(".container-info-cart").removeClass("show-info-cart");
							$(".container-info-cart").css("transform","translateX("+$(".container-info-cart").width()+"px)");
						}else{
							$(".container-info-cart").addClass("show-info-cart");
						}
					}
					//if($(".container-info-cart").hasClass("show-info-cart")){
					//	$(".container-info-cart").removeClass("show-info-cart");
					//}else{
					//	$(".container-info-cart").addClass("show-info-cart");
					//}
				});

				
			}
		}
		//localStorage.removeItem("cart");
		window["cart"] = new Cart(/*{
			discount:0,
			data:{
				0:{
					item_name:"Alimento para Hámster",
					item_price:65,
					quantity:2,
					url_image:"http://localhost/WebPage/mvc/image/products/comida_hamster.png",
				},
				1:{
					item_name:"Pecera Panorámica 5L",
					item_price:65,
					quantity:2,
					url_image:"http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm.png",
				},
				2:{
					item_name:"Calentador 200W",
					item_price:65,
					quantity:2,
					url_image:"http://localhost/WebPage/mvc/image/products/calentador.jpg",
				}
			}
		}*/);

		/*-- Add product Simulation --*/
		var infoProduct = <?php echo json_encode(
			array(
				array(
					"item_name"=>"Calentador 200W",
					"item_price"=>65,
					"quantity"=>2,
					"url_image"=>"http://localhost/WebPage/mvc/image/products/calentador.jpg",
					"item_id"=>1
				),
				array(
					"item_name"=>"Pecera Panorámica 5L",
					"item_price"=>65,
					"quantity"=>2,
					"url_image"=>"http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm.png",
					"item_id"=>2
				),
				array(
					"item_name"=>"Alimento para Hámster",
					"item_price"=>65,
					"quantity"=>2,
					"url_image"=>"http://localhost/WebPage/mvc/image/products/comida_hamster.png",
					"item_id"=>3
				)
			)
		); ?>;
		console.log(infoProduct);
		var info = {
			item_name:"Alimento para Hámster",
			item_price:65,
			quantity:2,
			item_id:555,
			url_image:"http://localhost/WebPage/mvc/image/products/comida_hamster.png",
		};
		//window["cart"].addItem(info);
	</script>