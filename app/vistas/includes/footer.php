<style type="text/css">
.footer-single{
    background: black;
}
.footer-single .footer-single-link{
    color: white;
    text-decoration: none;
    font-size: 16px;
    font-weight: 100;
}
.footer-single .footer-single-list{
    list-style: none;
    padding: 0;
}
.footer-single .footer-single-social{
    font-size: 30px;
    padding: 10px;
}
.footer-single .footer-single-info-row{
    text-align: center;
}
.footer-single .footer-single-info-row-center{
    justify-content: center;
}
.footer-single .footer-single-company{
    color: white;
    font-style: italic;
}
.footer-single-row{
    margin: 0 !important;
}


</style>
<div class="row row-footer footer-single footer-single-row">

    <div class="col-md-12">

        <footer class="page-footer deep-purple center-on-small-only pt-0 ">



            <div class="row pt-3 d-flex footer-single-info-row footer-single-info-row-center">

                <div class="col-md-3">
                    <ul class="footer-single-list">
                        <li>
                            <h6 class="title font-bold ">
                                <a class="footer-single-link" href="#" style="">¿Quiénes Somos?</a>
                            </h6>
                        </li>

                    </ul>
                </div>
                <div class="col-md-3">
                    <ul class="footer-single-list">
                        <li>
                            <h6 class="title font-bold ">
                                <a class="footer-single-link" href="#" style="">Contáctanos</a>
                            </h6>
                        </li>
                    </ul>
                </div>

            </div>
            
            <div class="row " >

                <div class="col-md-12">
                    <div class="footer-socials mb-3 flex-center" style="text-align:center;color:white">

                        <a target="_blank" class="footer-single-link"  href="https://www.facebook.com/panoramexgdl/">
                            <i class="footer-single-social fab fa-facebook-square"></i>
                        </a>

                        <a target="_blank" class="footer-single-link"  href="https://www.instagram.com/panoramex/">
                            <i class="footer-single-social fab fa-instagram"></i>
                        </a>

                        <a target="_blank" class="footer-single-link"  href="https://twitter.com/PanoramexTours">
                            <i class="footer-single-social fab fa-twitter"></i>
                        </a>

                        <a target="_blank"  class="footer-single-link" href="mailto:info@panoramex.mx">
                            <i class="footer-single-social far fa-envelope"></i>
                        </a>

                        <!--<a   href="https://api.whatsapp.com/send?phone=5213315876695&text=Hola%20mi%20nombre%20es...%20y%20mi%20pregunta%20es...&source=&data="><i class="iconS fab fa-whatsapp"></i></a>-->
                        <a class="footer-single-link" data-toggle="modal" data-target="#msg-whts">
                            <i class="footer-single-social fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>

            </div>
            <div class="row text-center" style="justify-content:center;">
                <label class="footer-single-company" ">Black & White Bettas</label>
            </div>







        </footer>

    </div>

</div>
<!-- Modal -->

<div class="modal fade" id="msg-whts" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"><?php echo $textModal[$lan][0]; ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:black;">
          <span aria-hidden="true">&times;</span>
      </button>
  </div>
  <div class="modal-body">
    <div class="form-group">
        <label for="exampleInputEmail1"><?php echo $textModal[$lan][1]; ?></label>
        <textarea type="email" class="form-control" require id="whats-msg"></textarea>
        <small id="small-msg" class="form-text text-danger" style="display:none;"></small>
    </div>


</div>
<div class="modal-footer">

    <button type="button" class="btn btn-primary" onclick="enviarWhatsApp();"><?php echo $textModal[$lan][2]; ?></button>
</div>
</div>
</div>
</div>
<script>
    function enviarWhatsApp(){
        var mensaje = $('#whats-msg').val();
        if(mensaje){
            console.log(mensaje);
            var url = "https://api.whatsapp.com/send?phone=5213315876695&text="+mensaje+"&source=&data=";
            $('#msg-whts').modal('hide');
            window.open(url, '_blank');
        }else{
            $('#small-msg').html('Mensaje vacio');
            $('#small-msg').css('display','block');
            setTimeout(function(){
                $('#small-msg').css('display','none');    
            },3000)
        }
    }

</script>
