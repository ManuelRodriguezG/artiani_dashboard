<?php 

include "../app/vistas/includes/librerias.php";

include "../app/vistas/includes/header.php";

?>


<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;
	charset=utf-8">
	<meta name="viewport" content="width=device-width">
 <link rel="stylesheet" type="text/css" href="<?php echo RUTA_URL; ?>/css/reviews/social/social-reviews-single.css">
 <title>Threendy universal eBay Listing template</title>
</head>
<body>
	<table align="center" style="border-spacing: 0px;width: 100%;">
		<tbody>
			<tr>
				<td>
					<div id="ds_div">
						<main id="main" class="wrapper">
							<div class="container">
								<div class="product-gallery-main">
									<div id="product-container" data-editor="content">
                                        <div class="variant04">
                                            <input class="open-check open-modal" id="modal" name="modal" type="checkbox">
                                            <input class="open-switch" id="open-switch" type="checkbox">
                                            <div class="product-box">
                                                <div class="product-images">
                                                    <div class="fade-gallery gallery-all" data-editor="gallery" data-link="false" data-max="16"> 
                                                        <span class="empty-marker">http://localhost/WebPage/mvc/image/products/comida_hamster.png</span>
                                                        <span class="empty-marker">http://localhost/WebPage/mvc/image/products/comida_hamster_2.png</span>
                                                        <span class="empty-marker">http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm.png</span>
                                                        <span class="empty-marker">http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm_2.png</span> 
                                                        <input class="open-check" id="fade-gallery-0" name="fade-gallery" checked="checked" type="radio">
                                                        <input class="open-check" id="fade-gallery-1" name="fade-gallery" type="radio">
                                                        <input class="open-check" id="fade-gallery-2" name="fade-gallery" type="radio">
                                                        <input class="open-check" id="fade-gallery-3" name="fade-gallery" type="radio">
                                                        <div class="holder-img img-box"> 
                                                            <img src="http://localhost/WebPage/mvc/image/products/comida_hamster.png" alt="image">
                                                            <img src="http://localhost/WebPage/mvc/image/products/comida_hamster_2.png" alt="image">
                                                            <img src="http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm.png" alt="image">
                                                            <img src="http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm_2.png" alt="image">
                                                            <label for="modal" class="text-label"> 
                                                                <span>To enlarge please click on the picture</span>
                                                                <span class="close"><i class="fa fa-times" aria-hidden="true"></i></span> 
                                                            </label>
                                                        </div>
                                                        <div class="switcher">
                                                            <div class="thumbnail">
                                                                <label for="fade-gallery-0"> 
                                                                    <img src="http://localhost/WebPage/mvc/image/products/comida_hamster.png" alt="image"> 
                                                                </label>
                                                            </div>
                                                            <div class="thumbnail">
                                                                <label for="fade-gallery-1"> 
                                                                    <img src="http://localhost/WebPage/mvc/image/products/comida_hamster_2.png" alt="image"> 
                                                                </label>
                                                            </div>
                                                            <div class="thumbnail">
                                                                <label for="fade-gallery-2"> 
                                                                    <img src="http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm.png" alt="image"> 
                                                                </label>
                                                            </div>
                                                            <div class="thumbnail">
                                                                <label for="fade-gallery-3"> 
                                                                    <img src="http://localhost/WebPage/mvc/image/products/pecera_panoramica_20cm_2.png" alt="image"> 
                                                                </label>
                                                            </div>
                                                            <label for="open-switch" class="switch-label"><i class="fa fa-angle-down" aria-hidden="true"></i></label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="product-description">
                                                    <div class="box-edit">
                                                       <h1 data-editor="edit" class="ItemName">Alimento para Hámster Redkite</h1>
                                                   </div>
                                                   <div class="col-d">
                                                    <div class="box-edit main-info" data-editor="editMCE">
                                                        <h2>Descripción del Producto</h2>
                                                        <div class="ShortDescription">
                                                            <p>
                                                                El Alimento para Hámster de Redkite provee a su mascota todos los nutrientes eseciales para un desarrollo y mantenimiento óptimo.
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="prise-wrap">
                                                        <div class="box-edit">
                                                            <strong class="item-prise" data-editor="edit,dell" contenteditable="false">
                                                                <span class="PriceInfo">MXN 75.00</span>
                                                                <button class="btn btn-primary btn-carousel-single">
                                                                    <i class="fas fa-shopping-cart icon-cart-carousel-single"></i>
                                                                </button>

                                                            </strong>
                                                        </div>
                                                        <!--<div class="box-edit">
                                                            <span class="paragraph" data-editor="edit,dell">sample VAT.</span>
                                                        </div>
                                                        <div class="box-edit paragraph-green" data-editor="editMCE,dell">
                                                         <span class="paragraph green"><i class="fa fa-check" aria-hidden="true"></i><span>Immediately available</span></span>
                                                     </div>-->
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 <div>
                                    <div>
                                        <h2>Reseñas del Producto</h2>
                                    </div>
                                    <div>
                                        <div class="owl-carousel owl-theme" id="carousel-social-reviews-single">
                                            <div class="item ">
                                                <div class="row container-profile-social-page">
                                                    <div class="image-profile-social-page">
                                                        <img class="img-profile-social-single" src="https://scontent.fgdl5-2.fna.fbcdn.net/v/t1.0-1/cp0/p50x50/73196204_2526055317490246_8914104120613273600_n.jpg?_nc_cat=109&_nc_sid=dbb9e7&_nc_ohc=THHVEn4bIRgAX9up4Ll&_nc_ht=scontent.fgdl5-2.fna&oh=4778a56fae6fcf4ab6e15cdd793d88ba&oe=5F326FDB">
                                                    </div>
                                                    <div class="container-section-social-single">
                                                        <label>Black White Bettas</label>
                                                    </div>
                                                    <div class="container-section-social-single" style="width: 100%;display: flex;align-content: center;">
                                                        <div class="contenedorStars1M" style="width: 108px;position: relative;">
                                                            <i class="far fa-star"></i>
                                                            <i class="far fa-star"></i>
                                                            <i class="far fa-star"></i>
                                                            <i class="far fa-star"></i>
                                                            <i class="far fa-star"></i>
                                                            <div class="contenedorStarsM" style="width: 100%;position: absolute;top: 0px;left: 0;white-space: nowrap;color: #ffd200;overflow: hidden;">
                                                                <i class="fas fa-star"></i>
                                                                <i class="fas fa-star"></i>
                                                                <i class="fas fa-star"></i>
                                                                <i class="fas fa-star"></i>
                                                                <i class="fas fa-star"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="container-section-social-single">
                                                        <label>A 943 personas les gusta esto</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <div class="container-profile-social-page">
                                                    <div class="section-text-social-single">
                                                        <p>
                                                            Excelente servicio a domicilio y amabilidad tienen mucha variedad de peceras a costos muy accesibles ... Mora está súper feliz Gracias
                                                        </p>
                                                    </div>
                                                    <div class="section-time-social-single" style="display: flex;padding: 8.5px;justify-content: center;">
                                                        <div style="border-radius: 100%;color: white;background: #4267b2;width: 29px;text-align: center;padding: 6px;">
                                                            <i class="fab fa-facebook-f" style="font-size: 18px;border-radius: 100%;"></i>
                                                        </div>
                                                        <div style="padding-left: 20px;">
                                                            <a href="#">2 min</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <div class="container-profile-social-page">
                                                    <div class="section-text-social-single">
                                                        <p>
                                                            Me encanto tienen productos de excelente calidad y precio , su servicio a domicilio y comunicación con el cliente es excelente y rápido muchísimas gracias ! 🤩
                                                        </p>
                                                    </div>
                                                    <div class="section-time-social-single" style="display: flex;padding: 8.5px;justify-content: center;">
                                                        <div style="border-radius: 100%;color: white;background: #4267b2;width: 29px;text-align: center;padding: 6px;">
                                                            <i class="fab fa-facebook-f" style="font-size: 18px;border-radius: 100%;"></i>
                                                        </div>
                                                        <div style="padding-left: 20px;">
                                                            <a href="#">1h</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <div class="container-profile-social-page">
                                                    <div class="section-text-social-single">
                                                        <p>
                                                            excelente servicio y amabilidad sobre todo muy recomendable y lo más importante de todo productos de calidad.
                                                        </p>
                                                    </div>
                                                    <div class="section-time-social-single" style="display: flex;padding: 8.5px;justify-content: center;">
                                                        <div style="border-radius: 100%;color: white;background: #4267b2;width: 29px;text-align: center;padding: 6px;">
                                                            <i class="fab fa-facebook-f" style="font-size: 18px;border-radius: 100%;"></i>
                                                        </div>
                                                        <div style="padding-left: 20px;">
                                                            <a href="#">3 d</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="item">
                                                <div class="container-profile-social-page">
                                                    <div class="section-text-social-single">
                                                        <p>
                                                            Excelente servicio! Peceras de muy buena calidad y rapidez en el envío ☺️
                                                        </p>
                                                    </div>
                                                    <div class="section-time-social-single" style="display: flex;padding: 8.5px;justify-content: center;">
                                                        <div style="border-radius: 100%;color: white;background: #4267b2;width: 29px;text-align: center;padding: 6px;">
                                                            <i class="fab fa-facebook-f" style="font-size: 18px;border-radius: 100%;"></i>
                                                        </div>
                                                        <div style="padding-left: 20px;">
                                                            <a href="#">2020/04/24</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> 
                                    </div>
                                </div>
                            </div>
                        </main>
                    </div>
                </td>
            </tr>
            <script type="text/javascript" src="<?php echo RUTA_URL;?>/js/reviews/social/social-reviews-single.js"></script>
        </tbody>
    </table>
    <span id="closeHtml"></span>
    <script type="text/javascript">
        var contador = 0;
        $(".open-check").each(function(){
            console.log(this);
            console.log(this.checked);
            console.log($(this).hasClass("open-modal"));
            if(this.checked == true && $(this).hasClass("open-modal") == false){
                var count = 0;
                $(".open-check").each(function(){
                 if($(this).hasClass("open-modal") == false){
                    $(".thumbnail")[count].children[0].classList.remove("visible");
                    count++
                }
            })
                $(".thumbnail")[contador].children[0].classList.add("visible");
                $(".holder-img img")[contador].classList.add("view-image");
                contador++


                $(this).click(function(){

                    $(".holder-img img").each(function(){
                        this.classList.remove("view-image");
                    });
                    //this.children[0].classList.add("visible");
                    console.log(this.id.split("-")[2]);
                    var index = this.id.split("-")[2];
                    var count = 0;
                    $(".open-check").each(function(){
                        if($(this).hasClass("open-modal") == false){
                            $(".thumbnail")[count].children[0].classList.remove("visible");
                            count++
                        }
                    })
                    $(".thumbnail")[index].children[0].classList.add("visible");
                    $(".holder-img img")[index].classList.add("view-image");
                })
            }else if($(this).hasClass("open-modal") == false){

                $(this).click(function(){

                    $(".holder-img img").each(function(){
                        this.classList.remove("view-image");
                    });
                    console.log(this.id.split("-")[2]);
                    var index = this.id.split("-")[2];
                    var count = 0;
                    $(".open-check").each(function(){
                        if($(this).hasClass("open-modal") == false){
                            $(".thumbnail")[count].children[0].classList.remove("visible");
                            count++
                        }
                    })
                    $(".thumbnail")[index].children[0].classList.add("visible");
                    $(".holder-img img")[index].classList.add("view-image");
                })
                contador++
            }

            
        })

    </script>	
    <?php 


    include "../app/vistas/includes/footer.php";
    ?>
</body>
</html>
<style type="text/css">
.view-image{
    position:relative !important;
    left:auto !important;
}
.visible{
    opacity:1 !important;
}
.container-btn-carousel-single{
    padding: 20px 0px;
    text-align: center;
    justify-content: center;
}
.icon-cart-carousel-single{
    padding: 4px;
}
.btn-carousel-single{
    background: white;
    border-radius: 20px;
    border:2px solid #b0ded3;
    color: gray;
}
.btn-carousel-single:hover{
    background: #b0ded3;
    color: black;
    border-color: #b0ded3 !important;
}



#carousel-social-reviews-single{
 margin-bottom: 15px;
}
#carousel-social-reviews-single .container-profile-social-page:hover{
 box-shadow: 0px 4px 5px 0px rgba(0, 0, 0, 0.3);
}
#carousel-social-reviews-single .container-profile-social-page{
 border: 2px solid #cecece;
 background: white;
 margin: 10px;
 padding: 10px;
 max-width: 300px;
}
#carousel-social-reviews-single .image-profile-social-page{
 justify-content: center;
 text-align: center;
 width: 100%;
}
#carousel-social-reviews-single .img-profile-social-single{
 display: inline;
 width: 50px;
 height: 50px;
}
#carousel-social-reviews-single .container-section-social-single{
 justify-content: center;
 text-align: center;
 width: 100%;
}
#carousel-social-reviews-single .section-text-social-single{
 height: 91px;
 overflow: auto;
}
</style>
<style type="text/css">

@media all{
 .main-info{
  margin: 48px 0px 0 0px;
}
.wrapper{
  font:14px/28px 'Montserrat', sans-serif;
  margin:0;
  min-width:320px;
  color:#000;
}
.wrapper strong{
  font-weight:600;
}
.wrapper input{
  vertical-align:middle;
  -webkit-border-radius:0;
  -moz-border-radius:0;
  border-radius:0;
  -webkit-box-shadow:inset rgba(0, 0, 0, 0) 0 0 0;
  -moz-box-shadow:inset rgba(0, 0, 0, 0) 0 0 0;
  box-shadow:inset rgba(0, 0, 0, 0) 0 0 0;
  -webkit-appearance:none;
}
.wrapper input::-moz-focus-inner{
  padding:0;
  border:0;
}
.wrapper label{
  cursor:pointer;
}
.wrapper figure,.wrapper nav{
  display:block;
}
.wrapper figure{
  margin:0;
}
.wrapper img{
  border:0;
  border-style:none;
  vertical-align:top;
  -ms-interpolation-mode:bicubic;
  vertical-align:baseline;
  font-weight:inherit;
  font-family:inherit;
  font-style:inherit;
  font-size:100%;
  border:0 none;
  outline:0;
  padding:0;
  margin:0;
}
.wrapper a,.wrapper a:link,.wrapper a:visited{
  color:#000;
  text-decoration:none;
}
.wrapper a:focus,.wrapper a:hover{
  outline:none;
  outline:0;
}
.container{
  max-width:1270px;
  margin:0 auto;
  padding:0 20px;
}
.wrapper p{
  margin:0 0 12px;
}
.wrapper h2{
  font-size:32px;
  line-height:52px;
  margin:0 0 88px;
  font-weight:700;
  text-align:center;
  text-transform:uppercase;
}
.wrapper h2 span{
  color:#de183c;
}
.wrapper h3{
  font-size:20px;
  line-height:26px;
  font-weight:600;
  margin:0 0 30px;
  text-transform:uppercase;
}


.open-check{
  display:none;
}



#main{
  overflow:hidden;
}
.product-box{
  margin:0 0 113px;
  position:relative;
  overflow:hidden;
  padding:0 0 197px;
  -webkit-transition:padding .2s ease-in-out;
  -moz-transition:padding .2s ease-in-out;
  -ms-transition:padding .2s ease-in-out;
  -o-transition:padding .2s ease-in-out;
  transition:padding .2s ease-in-out;
}
.product-images{
  float:left;
  width:62%;
}
.product-description{
  width:38%;
  float:left;
  padding:0 20px 0 0;
  -webkit-box-sizing:border-box;
  -moz-box-sizing:border-box;
  box-sizing:border-box;
}
.product-description h1{
  margin:0 0 1px;
  font:42px/49px 'Montserrat', sans-serif;
  font-weight:700;
}
.wrapper .product-description h2{
  margin:0 0 30px 5px;
  text-align:left;
  color:#71ceb8;
  font-size:20px;
  line-height:26px;
  font-weight:600;
  text-transform:uppercase;
}
.product-description h3{
  font-size:14px;
  line-height:16px;
  margin:0 0 11px 9px;
  text-align:left;
}
.prise-wrap{
  margin:0 0 40px 6px;
}
.item-prise{
  font:40px/50px 'Montserrat', sans-serif;
  display:block;
  margin:0 0 9px;
}
.paragraph{
  display:block;
  margin:0 0 10px 7px;
  font-size:10px;
  line-height:12px;
  color:#808080;
}
.paragraph.green{
  color:#0c8416;
}
.paragraph .fa{
  margin:0 5px 0 0;
}




.open-check{
  display:none;
}
.fade-gallery{
  margin:0 0 20px;
  font-size:0px;
  line-height:0px;
}
.fade-gallery label{
  cursor:pointer;
}
.fade-gallery .switcher{
  position:absolute;
  bottom:43px;
  left:0;
  height:110px;
  overflow:hidden;
  -webkit-transition:height .2s ease-in-out;
  -moz-transition:height .2s ease-in-out;
  -ms-transition:height .2s ease-in-out;
  -o-transition:height .2s ease-in-out;
  transition:height .2s ease-in-out;
}
.open-switch{
  display:none;
}
.switch-label{
  outline:0 none;
  position:absolute;
  bottom:-15px;
  left:50%;
  transform: translateX(-50%);
  margin:0 0 0 -15px;
  -webkit-transition:transform .2s ease-in-out;
  -moz-transition:transform .2s ease-in-out;
  -ms-transition:transform .2s ease-in-out;
  -o-transition:transform .2s ease-in-out;
  transition:transform .2s ease-in-out;
}
.switch-label .fa{
  font-size:50px;
  color:#ccc1af;
}
#open-switch:checked ~ .product-box{
  padding-bottom:327px;
}
#open-switch:checked ~ .product-box .fade-gallery .switcher{
  height:240px;
}
#open-switch:checked ~ .product-box .switch-label{
  -webkit-transform:rotate(200grad);
  -moz-transform:rotate(200grad);
  -ms-transform:rotate(200grad);
  -o-transform:rotate(200grad);
  transform:rotate(200grad);
}
.thumbnail{
  display:inline-block;
  vertical-align:top;
  width:12.5%;
  padding:0 5px;
  margin:0 0 20px;
  -webkit-box-sizing:border-box;
  -moz-box-sizing:border-box;
  box-sizing:border-box;
  text-align:center;
  position:relative;
}
.thumbnail label{
  height:50px;
  width:50px;
  display:inline-block;
  vertical-align:top;
  position:relative;
  -webkit-box-sizing:border-box;
  -moz-box-sizing:border-box;
  box-sizing:border-box;
  opacity:0.5;
  -webkit-transition:opacity .2s ease-in-out;
  -moz-transition:opacity .2s ease-in-out;
  -ms-transition:opacity .2s ease-in-out;
  -o-transition:opacity .2s ease-in-out;
  transition:opacity .2s ease-in-out;
}
.thumbnail label:before{
  content:'';
  position:absolute;
  top:0;
  left:0;
  width:100%;
  height:100%;
  border:2px solid #de183c;
  -webkit-box-sizing:border-box;
  -moz-box-sizing:border-box;
  box-sizing:border-box;
  z-index:1;
  opacity:0;
  -webkit-transition:all .2s ease-in-out;
  -moz-transition:all .2s ease-in-out;
  -ms-transition:all .2s ease-in-out;
  -o-transition:all .2s ease-in-out;
  transition:all .2s ease-in-out;
}
.thumbnail label img{
  max-width:100%;
  max-height:100%;
  position:relative;
  top:50%;
  -webkit-transform:translateY(-50%);
  -moz-transform:translateY(-50%);
  -ms-transform:translateY(-50%);
  -o-transform:translateY(-50%);
  transform:translateY(-50%);
}
.fade-gallery .holder-img{
  width:100%;
  z-index:1;
  position:relative;
  height:715px;
  text-align:center;
}
.fade-gallery .holder-img img{
  max-width:100%;
  max-height:100%;
  position:absolute;
  left:-99999px;
  top:50%;
  -webkit-transform:translateY(-50%) scale(1);
  -moz-transform:translateY(-50%) scale(1);
  -ms-transform:translateY(-50%) scale(1);
  -o-transform:translateY(-50%) scale(1);
  transform:translateY(-50%) scale(1);
  -webkit-transition:transform .2s ease-in-out;
  -moz-transition:transform .2s ease-in-out;
  -ms-transition:transform .2s ease-in-out;
  -o-transition:transform .2s ease-in-out;
  transition:transform .2s ease-in-out;
}
 /* .fade-gallery > .open-check:nth-of-type(1):checked ~ .holder-img img:nth-of-type(1),.fade-gallery > .open-check:nth-of-type(2):checked ~ .holder-img img:nth-of-type(2),.fade-gallery > .open-check:nth-of-type(3):checked ~ .holder-img img:nth-of-type(3),.fade-gallery > .open-check:nth-of-type(4):checked ~ .holder-img img:nth-of-type(4),.fade-gallery > .open-check:nth-of-type(5):checked ~ .holder-img img:nth-of-type(5),.fade-gallery > .open-check:nth-of-type(6):checked ~ .holder-img img:nth-of-type(6),.fade-gallery > .open-check:nth-of-type(7):checked ~ .holder-img img:nth-of-type(7),.fade-gallery > .open-check:nth-of-type(8):checked ~ .holder-img img:nth-of-type(8),.fade-gallery > .open-check:nth-of-type(9):checked ~ .holder-img img:nth-of-type(9),.fade-gallery > .open-check:nth-of-type(10):checked ~ .holder-img img:nth-of-type(10),.fade-gallery > .open-check:nth-of-type(11):checked ~ .holder-img img:nth-of-type(11),.fade-gallery > .open-check:nth-of-type(12):checked ~ .holder-img img:nth-of-type(12),.fade-gallery > .open-check:nth-of-type(13):checked ~ .holder-img img:nth-of-type(13),.fade-gallery > .open-check:nth-of-type(14):checked ~ .holder-img img:nth-of-type(14),.fade-gallery > .open-check:nth-of-type(15):checked ~ .holder-img img:nth-of-type(15),.fade-gallery > .open-check:nth-of-type(16):checked ~ .holder-img img:nth-of-type(16){
      position:relative;
      left:auto;
      }*/
  /*.thumbnail:hover label,.fade-gallery > .open-check:nth-of-type(1):checked ~ .switcher .thumbnail:nth-of-type(1) label,.fade-gallery > .open-check:nth-of-type(2):checked ~ .switcher .thumbnail:nth-of-type(2) label,.fade-gallery > .open-check:nth-of-type(3):checked ~ .switcher .thumbnail:nth-of-type(3) label,.fade-gallery > .open-check:nth-of-type(4):checked ~ .switcher .thumbnail:nth-of-type(4) label,.fade-gallery > .open-check:nth-of-type(5):checked ~ .switcher .thumbnail:nth-of-type(5) label,.fade-gallery > .open-check:nth-of-type(6):checked ~ .switcher .thumbnail:nth-of-type(6) label,.fade-gallery > .open-check:nth-of-type(7):checked ~ .switcher .thumbnail:nth-of-type(7) label,.fade-gallery > .open-check:nth-of-type(8):checked ~ .switcher .thumbnail:nth-of-type(8) label,.fade-gallery > .open-check:nth-of-type(9):checked ~ .switcher .thumbnail:nth-of-type(9) label,.fade-gallery > .open-check:nth-of-type(10):checked ~ .switcher .thumbnail:nth-of-type(10) label,.fade-gallery > .open-check:nth-of-type(11):checked ~ .switcher .thumbnail:nth-of-type(11) label,.fade-gallery > .open-check:nth-of-type(12):checked ~ .switcher .thumbnail:nth-of-type(12) label,.fade-gallery > .open-check:nth-of-type(13):checked ~ .switcher .thumbnail:nth-of-type(13) label,.fade-gallery > .open-check:nth-of-type(14):checked ~ .switcher .thumbnail:nth-of-type(14) label,.fade-gallery > .open-check:nth-of-type(15):checked ~ .switcher .thumbnail:nth-of-type(15) label,.fade-gallery > .open-check:nth-of-type(16):checked ~ .switcher .thumbnail:nth-of-type(16) label{
      opacity:1;
      }*/

      .fade-gallery label[for="modal"]{
          position:absolute;
          top:0px;
          left:0px;
          width:100%;
          height:100%;
          z-index:2;
      }
      .fade-gallery label[for="modal"] > span:first-child{
          position:absolute;
          top:50%;
          left:50%;
          color:#fff;
          background:rgba(134,134,132,0.8);
          padding:2px 5px;
          height:auto;
          z-index:2;
          opacity:0;
          font-size:14px;
          line-height:16px;
          margin:-9px 0 0 -80px;
          -webkit-transition:opacity .2s ease-in-out;
          -moz-transition:opacity .2s ease-in-out;
          -ms-transition:opacity .2s ease-in-out;
          -o-transition:opacity .2s ease-in-out;
          transition:opacity .2s ease-in-out;
      }
      .fade-gallery label[for="modal"]:hover > span:first-child{
          opacity:1;
      }
      .product-description{
          -ms-transform:scale(1);
          -webkit-transform:scale(1);
          transform:scale(1);
          -webkit-transition:transform .3s ease-in-out;
          -moz-transition:transform .3s ease-in-out;
          -ms-transition:transform .3s ease-in-out;
          -o-transition:transform .3s ease-in-out;
          transition:transform .3s ease-in-out;
      }
      .product-images{
          -webkit-transition:width .2s ease-in-out;
          -moz-transition:width .2s ease-in-out;
          -ms-transition:width .2s ease-in-out;
          -o-transition:width .2s ease-in-out;
          transition:width .2s ease-in-out;
      }
      .fade-gallery .close{
          position:absolute;
          display:none;
          top:0px;
          z-index:2;
          right:0;
          width:30px;
          height:30px;
          padding:3px 0 6px;
          text-align:center;
          border:1px solid #f00;
          background:#d61c21;
          color:#fff;
          text-align:center;
          box-sizing:border-box;
          -moz-box-sizing:border-box;
          -o-box-sizing:border-box;
          -webkit-box-sizing:border-box;
          -webkit-transition:background .2s ease-in-out, color .2s ease-in-out;
          -moz-transition:background .2s ease-in-out, color .2s ease-in-out;
          -ms-transition:background .2s ease-in-out, color .2s ease-in-out;
          -o-transition:background .2s ease-in-out, color .2s ease-in-out;
          transition:background .2s ease-in-out, color .2s ease-in-out;
      }
      .fade-gallery .close .fa{
          font-size:20px;
      }
      .fade-gallery > .empty-marker{
          position:absolute;
          top:-99999px;
          left:-99999px;
      }

      #main .open-modal:checked ~ .product-box .product-images{
          width:100%;
      }
      #main .open-modal:checked ~ .product-box .fade-gallery .holder-img{
          height:75vh;
          margin:0;
          padding: 30px;
      }
      #main .open-modal:checked ~ .product-box .fade-gallery .close{
          display:block;
      }
      #main .open-modal:checked ~ .product-box .fade-gallery label[for="modal"] > span:first-child{
          display:none;
      }
      #main .open-modal:checked ~ .product-box .product-description{
          position:absolute;
          top:0;
          right:0;
          -webkit-transform:scale(0);
          -moz-transform:scale(0);
          -ms-transform:scale(0);
          -o-transform:scale(0);
          transform:scale(0);
      }
      .thumbnail:hover label{
          opacity:1;
      }
      .thumbnail:hover label:before{
          opacity:1;
      }
      .thumbnail:hover label{
          opacity:1;
      }
      .thumbnail:hover label:before{
          opacity:1;
      }
      #product-container .variant04 .fade-gallery .switcher{
          position:relative;
          bottom:auto;
          left:auto;
          height:110px;
          padding:0 0 30px;
      }
      #product-container .variant04 #open-switch:checked ~ .product-box .fade-gallery .switcher{
          height:500px;
      }
      #product-container .variant04 .fade-gallery .switcher:before{
          content:'';
          position:absolute;
          bottom:0;
          left:0;
          width:100%;
          height:30px;
          background:#fff;
          z-index:1;
      }
      #product-container .variant04 .fade-gallery .switcher .switch-label{
          z-index:3;
      }
      #product-container .variant04 .thumbnail{
          width:60px;
      }
      #product-container .variant04 .fade-gallery .holder-img{
          height:400px;
          padding:30px;
      }
      #product-container .variant04 .product-box,#product-container .variant04 #open-switch:checked ~ .product-box{
          padding:10px;
          margin:0 0 30px;
      }
      #product-container .variant04 .product-description{
          width:45%;
      }
      #product-container .variant04 .product-images{
          width:55%;
      }




      /**/



      .open-check{
          display:none;
      }
      .open-check{
          display:none;
      }

      @media (max-width: 1270px){

         .container{
             width:95vw;
         }
     }
     @media (max-width: 1200px){

         .container{
             width:91vw;
         }
     }
     @media (max-width: 1024px){

         .container{
             width:89vw;
         }

         .product-description,.product-images{
             width:50%;
         }
         .product-description h1{
             font-size:40px;
             line-height:45px;
         }
         .thumbnail label{
             width:50px;
             height:50px;
         }
         .fade-gallery .switcher{
             height:105px;
         }
         #open-switch:checked ~ .product-box .fade-gallery .switcher{
             height:220px;
         }
         .product-box{
             padding:0 0 170px;
             margin:0 0 50px;
         }
         #open-switch:checked ~ .product-box{
             padding-bottom:285px;
         }

     }
     @media (max-width: 850px){



         .thumbnail label{
             width:70px;
             height:70px;
         }
         .fade-gallery .switcher{
             height:80px;
         }
         #open-switch:checked ~ .product-box .fade-gallery .switcher{
             height:180px;
         }
         .product-box{
             padding:0 0 140px;
             margin:0 0 50px;
         }
         #open-switch:checked ~ .product-box{
             padding-bottom:240px;
         }

         .product-description,.product-images,#product-container .variant04 .product-images,#product-container .variant04 .product-description{
             float:none;
             width:100%;
         }
         #main .open-modal:checked ~ .product-box .product-images{
             width:100%;
         }
         .fade-gallery .holder-img,#main .open-modal:checked ~ .product-box .fade-gallery .holder-img,#product-container .variant04 .fade-gallery .holder-img{
             height:400px;
         }
         .fade-gallery .close,#main .open-modal:checked ~ .product-box .fade-gallery .close{
             display:none;
         }
         .fade-gallery label[for="modal"] > span:first-child,#main .open-modal:checked ~ .product-box .fade-gallery label[for="modal"] > span:first-child{
             display:none;
         }
         #main .open-modal:checked ~ .product-box .product-description{
             position:static;
             -webkit-transform:scale(1);
             -moz-transform:scale(1);
             -ms-transform:scale(1);
             -o-transform:scale(1);
             transform:scale(1);
         }
                /*#main .open-modal:checked ~ .product-box .fade-gallery .holder-img img{
                -webkit-transform:translateY(-50%) scale(1);
                -moz-transform:translateY(-50%) scale(1);
                -ms-transform:translateY(-50%) scale(1);
                -o-transform:translateY(-50%) scale(1);
                transform:translateY(-50%) scale(1);
                }*/
                .product-box,#open-switch:checked ~ .product-box{
                	padding:0;
                }
                .fade-gallery .switcher,#product-container .variant04 .fade-gallery .switcher{
                	position:static;
                	height:109px;
                }
                #product-container .variant04 .fade-gallery .switcher{
                	position:relative;
                }
                #open-switch:checked ~ .product-box .fade-gallery .switcher,#product-container .variant04 #open-switch:checked ~ .product-box .fade-gallery .switcher{
                	height:142px;
                }
                .switch-label{
                	position:static;
                	display:block;
                	text-align:center;
                	width:40px;
                	margin:0 auto;
                }
                #product-container .variant04 .fade-gallery .switcher .switch-label{
                	position:absolute;
                }
                #product-container .variant04 .thumbnail{
                	width:12.5%;
                }
                .product-description h1{
                	font-size:32px;
                	line-height:35px;
                }
                
                .thumbnail label{
                	width:60px;
                	height:60px;
                }
                .wrapper .product-description h2{
                	margin:0 0 20px;
                }
                .product-description ul,.prise-wrap{
                	margin:0 0 20px;
                }
                .product-description ul li{
                	padding:0 0 0 20px;
                	margin:0 0 10px;
                }
                .item-prise{
                	font-size:30px;
                	line-height:35px;
                }
                .product-box{
                	margin:0 0 20px;
                }
                
            }

            @media (max-width: 650px){



             .thumbnail,#product-container .variant04 .thumbnail{
                 width:25%;
                 margin:0 0 10px;
             }
             #open-switch:checked ~ .product-box .fade-gallery .switcher,#product-container .variant04 #open-switch:checked ~ .product-box .fade-gallery .switcher{
                 height:270px;
             }

         }
         @media (max-width: 500px){


             .fade-gallery .holder-img,#main .open-modal:checked ~ .product-box .fade-gallery .holder-img,#product-container .variant04 .fade-gallery .holder-img{
                 height:300px;
             }
             .container{
                 padding:0 10px;
             }
         }
         
         @media (max-width: 500px){

             #main .product-gallery-main{
                 padding:0 10px;
             }
             
         }
     }
        /*! CSS Used from: https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css ;
        media=all */
        @media all{

        	.fa{
        		display:inline-block;
        		font:normal normal normal 14px/1 FontAwesome;
        		font-size:inherit;
        		text-rendering:auto;
        		-webkit-font-smoothing:antialiased;
        		-moz-osx-font-smoothing:grayscale;
        	}
        	.fa-check:before{
        		content:"\f00c";
        	}
        	.fa-close:before,.fa-times:before{
        		content:"\f00d";
        	}
        	.fa-headphones:before{
        		content:"\f025";
        	}
        	.fa-shopping-cart:before{
        		content:"\f07a";
        	}
        	.fa-envelope:before{
        		content:"\f0e0";
        	}
        	.fa-angle-left:before{
        		content:"\f104";
        	}
        	.fa-angle-right:before{
        		content:"\f105";
        	}
        	.fa-angle-down:before{
        		content:"\f107";
        	}
        	.fa-mobile:before{
        		content:"\f10b";
        	}
        	.fa-puzzle-piece:before{
        		content:"\f12e";
        	}
        	.fa-shield:before{
        		content:"\f132";
        	}
        	.fa-rocket:before{
        		content:"\f135";
        	}
        	.fa-paint-brush:before{
        		content:"\f1fc";
        	}
        	.fa-diamond:before{
        		content:"\f219";
        	}
        }
        
        /*! CSS Used fontfaces */
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Light'), local('Montserrat-Light'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_cJD3gTD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Light'), local('Montserrat-Light'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_cJD3g3D_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Light'), local('Montserrat-Light'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_cJD3gbD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Light'), local('Montserrat-Light'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_cJD3gfD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Light'), local('Montserrat-Light'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_cJD3gnD_vx3rCs.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Regular'), local('Montserrat-Regular'), url(https://fonts.gstatic.com/s/montserrat/v12/JTUSjIg1_i6t8kCHKm459WRhyyTh89ZNpQ.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Regular'), local('Montserrat-Regular'), url(https://fonts.gstatic.com/s/montserrat/v12/JTUSjIg1_i6t8kCHKm459W1hyyTh89ZNpQ.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Regular'), local('Montserrat-Regular'), url(https://fonts.gstatic.com/s/montserrat/v12/JTUSjIg1_i6t8kCHKm459WZhyyTh89ZNpQ.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Regular'), local('Montserrat-Regular'), url(https://fonts.gstatic.com/s/montserrat/v12/JTUSjIg1_i6t8kCHKm459WdhyyTh89ZNpQ.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Regular'), local('Montserrat-Regular'), url(https://fonts.gstatic.com/s/montserrat/v12/JTUSjIg1_i6t8kCHKm459WlhyyTh89Y.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Medium'), local('Montserrat-Medium'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_ZpC3gTD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Medium'), local('Montserrat-Medium'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_ZpC3g3D_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Medium'), local('Montserrat-Medium'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_ZpC3gbD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Medium'), local('Montserrat-Medium'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_ZpC3gfD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Medium'), local('Montserrat-Medium'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_ZpC3gnD_vx3rCs.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_bZF3gTD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_bZF3g3D_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_bZF3gbD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_bZF3gfD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_bZF3gnD_vx3rCs.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:700;
        	src:local('Montserrat Bold'), local('Montserrat-Bold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_dJE3gTD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:700;
        	src:local('Montserrat Bold'), local('Montserrat-Bold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_dJE3g3D_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:700;
        	src:local('Montserrat Bold'), local('Montserrat-Bold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_dJE3gbD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:700;
        	src:local('Montserrat Bold'), local('Montserrat-Bold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_dJE3gfD_vx3rCubqg.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat';
        	font-style:normal;
        	font-weight:700;
        	src:local('Montserrat Bold'), local('Montserrat-Bold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_dJE3gnD_vx3rCs.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Alternates Light'), local('MontserratAlternates-Light'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xQIXFCrxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Alternates Light'), local('MontserratAlternates-Light'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xQIXFA7xG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Alternates Light'), local('MontserratAlternates-Light'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xQIXFCLxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Alternates Light'), local('MontserratAlternates-Light'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xQIXFCbxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:300;
        	src:local('Montserrat Alternates Light'), local('MontserratAlternates-Light'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xQIXFB7xG-GNxkg.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Alternates Regular'), local('MontserratAlternates-Regular'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTvWacfw6zH4dthXcyms1lPpC8I_b0juU055qfQKp5L0ll4.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Alternates Regular'), local('MontserratAlternates-Regular'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTvWacfw6zH4dthXcyms1lPpC8I_b0juU0576fQKp5L0ll4.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Alternates Regular'), local('MontserratAlternates-Regular'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTvWacfw6zH4dthXcyms1lPpC8I_b0juU055KfQKp5L0ll4.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Alternates Regular'), local('MontserratAlternates-Regular'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTvWacfw6zH4dthXcyms1lPpC8I_b0juU055afQKp5L0ll4.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:400;
        	src:local('Montserrat Alternates Regular'), local('MontserratAlternates-Regular'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTvWacfw6zH4dthXcyms1lPpC8I_b0juU0566fQKp5L0g.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Alternates Medium'), local('MontserratAlternates-Medium'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xGITFCrxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Alternates Medium'), local('MontserratAlternates-Medium'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xGITFA7xG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Alternates Medium'), local('MontserratAlternates-Medium'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xGITFCLxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Alternates Medium'), local('MontserratAlternates-Medium'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xGITFCbxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:500;
        	src:local('Montserrat Alternates Medium'), local('MontserratAlternates-Medium'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xGITFB7xG-GNxkg.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat Alternates SemiBold'), local('MontserratAlternates-SemiBold'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xNIPFCrxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat Alternates SemiBold'), local('MontserratAlternates-SemiBold'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xNIPFA7xG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat Alternates SemiBold'), local('MontserratAlternates-SemiBold'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xNIPFCLxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0102-0103, U+0110-0111, U+1EA0-1EF9, U+20AB;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat Alternates SemiBold'), local('MontserratAlternates-SemiBold'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xNIPFCbxG-GNxklNd.woff2) format('woff2');
        	unicode-range:U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face{
        	font-family:'Montserrat Alternates';
        	font-style:normal;
        	font-weight:600;
        	src:local('Montserrat Alternates SemiBold'), local('MontserratAlternates-SemiBold'), url(https://fonts.gstatic.com/s/montserratalternates/v9/mFTiWacfw6zH4dthXcyms1lPpC8I_b0juU0xNIPFB7xG-GNxkg.woff2) format('woff2');
        	unicode-range:U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        @font-face{
        	font-family:'FontAwesome';
        	src:url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.eot?v=4.7.0');
        	src:url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.eot#iefix&v=4.7.0') format('embedded-opentype'),url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.woff2?v=4.7.0') format('woff2'),url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.woff?v=4.7.0') format('woff'),url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.ttf?v=4.7.0') format('truetype'),url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/fonts/fontawesome-webfont.svg?v=4.7.0#fontawesomeregular') format('svg');
        	font-weight:normal;
        	font-style:normal;
        }
    </style>
