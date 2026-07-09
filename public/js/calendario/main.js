var auxMes = 0;
var auxiliar = new Date();
var dia = auxiliar.getDate();
var mes = auxiliar.getMonth() + 1;
var año = auxiliar.getFullYear();
var mesActual = mes;
var mesAux = mes;
var bandera = 0;
var conteoClicks = 0;
var reinicio = 0;
var diasSemanaInactivos = [];
var fechasInactivas;
var diaInactivo;
var mesInactivo;
var añoInactivo;
var arrayMesInactivo = [];
var lastMonth = [];
var lengthArray;
var BanderaAux = 0;
var excepciones = [];
var Banderas = 0;
var Banderita = 0;
var diasInactivos   = [];
var auxNextYear = 12;
var auxPreviewYear = 12;
var añoActualList = año - 1;
var rango;
var excepcion = [];
var valoresUnico = [];
var diasAnteriores;
var idInput;
var dateSelect;
var auxDI = 0;
var idInputSalida;

//diasInactivoss();
//diasInactivosFuncion(mes,año);
$('#preview').css('opacity','0.3');

function nexto(){
    console.log("hola");
    
        $('#preview').css('opacity','1');

    //console.log(mesActual);
        if(mesActual == 12){
            mesActual = 1; 
            auxMes = 0 ;
            año = parseInt(año);
            año = año + 1;
            mes = 1 ;
        }else{
        auxMes = auxMes + 1;
        mesActual = mes + auxMes;
        }
        
          $.get('https://panoramex.mx/public/calendarioClass.php', 'data=' + JSON.stringify({
			action: 'nextCalendario',
			mesActual,
			año,
			dia,
			rango,
			
		}),function(response){
			$('#rowPrincipal').html(response);  
			diasInactivosFuncion();
			diasAbiertos();
			
			
			if(conteoClicks == 1){
			  var fechaInicio = sessionStorage.getItem("fechaInicioCambio");
			  var diaCorto = sessionStorage.getItem("dia");
			  $('#' + fechaInicio).html("<p class='selectCalendario'>" + diaCorto + "</p>");
			}
			else if(conteoClicks == 2){
         	    //console.log(mesActual + año);
         if(mesActual == 2 && año == '2020' || mesActual == 2 && año == '2024' || mesActual == 2 && año == '2028'){
              var limiteInicio=["","31","29","31","30","31","30","31","31","30","31","30","31"];
              //console.log("2999999999999999999");
         }
         else{
             var limiteInicio=["","31","28","31","30","31","30","31","31","30","31","30","31"];  
             //console.log("28888888888888888");
         }
             //console.log(limiteInicio);
             
         	 var diaInicio = sessionStorage.getItem("dia");
         	 var mesInicio =  sessionStorage.getItem("mes"); 
         	 var añoInicio =  sessionStorage.getItem("año");
         	 
         	 var diaFin = sessionStorage.getItem("diaFin");
         	 var mesFin = sessionStorage.getItem("mesFin"); 
         	 var añoFin = sessionStorage.getItem("añoFin"); 
         	 
         	 
         	 //console.log(diaInicio + mesInicio + añoInicio);
         	 
         	 //var limite = limiteInicio[11];
         	 var limiteMesIicio = limiteInicio[mesActual];
         	 limiteMesInicio = parseInt(limiteMesIicio);
         	 //if(diaInicio < 10)
         	 //var diaInicioFor = parseInt(diaInicio.substr(1,1));
         	 //else
         	 var diaInicioFor = parseInt(diaInicio);
         	 var diferencia = limiteMesInicio - diaInicioFor;
         	     diferencia = parseInt(diferencia);
         	 //console.log(diaInicioFor);
         	 
         	 
         	 
         	 /*---------Fecha de un mes en otro*/
         	 
         	 if(mesInicio != mesFin)
         	 {
         	     
               if(mesActual == mesInicio){

                    //console.log(diferencia);
                     for(var xp = 0; xp <= diferencia; xp ++){
                         //console.log(xp + " hola");
                          if(diaInicioFor < 10){
                              $('#0' + diaInicioFor + mesInicio + añoInicio).html("<p class='selectCalendario'>" + diaInicioFor + "</p");
                              diaInicioFor = diaInicioFor + 1;
                          }
                          else{
                              $('#' + diaInicioFor + mesInicio + añoInicio).html("<p class='selectCalendario'>" + diaInicioFor + "</p");
                              diaInicioFor = diaInicioFor + 1;
                              //console.log('#' + diaInicio + mesInicio + añoInicio);
                          } 
                        
                        
                     }
                 }
             else if(mesActual == mesFin){
                     for(var auxM = 1; auxM <= diaFin; auxM ++){
                          if(auxM < 10){
                              $('#0' + auxM + mesFin + añoFin).html("<p class='selectCalendario'>" + auxM + "</p");
                              //console.log("#0" + auxM + mesFin + añoFin);
                          }
                          else{
                              $('#' + xp + mesFin + añoFin).html("<p class='selectCalendario'>" + xp + "</p");
                              //console.log("#" + auxM + mesFin + añoFin);

                          } 
                        
                        
                     }
                 
             }
         	 }
         	 //---------Fecha del mismo mes
         	 else{

         	     diaInicio = parseInt(diaInicio);
         	     diaFin = parseInt(diaFin);
         	     
         	     //console.log(diaInicio + " dia Inicio")
         	     //console.log(diaFin + " dia fin");
         	      for(var auxM = 0; diaInicio <= diaFin; diaInicio ++){
                          if(diaInicio < 10){
                              $('#0' + diaInicio + mesFin + añoFin).html("<p class='selectCalendario'>" + diaInicio + "</p");
                              //console.log("#0" + diaInicio + mesFin + añoFin);
                          }
                          else{
                              $('#' + diaInicio + mesFin + añoFin).html("<p class='selectCalendario'>" + diaInicio + "</p");
                              //console.log("#" + diaInicio + mesFin + añoFin);

                          } 
                     }
         	     
         	 }
         	 
         	 
         	 
         	}
			
			
			
			
			
		});
}

function preview(){//

    //conteoClicks = 0; 
    if(mesActual == 1){
        mesActual = 12;
        auxMes = 0;
        año = año - 1;
        mes = 12;

    }else{
        auxMes = auxMes - 1;
        mesActual = mes + auxMes;
    }       
        
        $.get('https://panoramex.mx/public/calendarioClass.php', 'data=' + JSON.stringify({
			action: 'nextCalendario',
			mesActual,
			año,
			dia,
			rango,
			
		}),function(response){
			$('#rowPrincipal').html(response);
			diasInactivosFuncion();
			diasAbiertos();
		    if(mesActual == mesAux){
              $('#divPreview').html("<i class='fas fa-caret-left'  id='preview' style='cursor:pointer;'></i>");
              $('#preview').css('opacity','0.3');
              
   /* if(mesActual == mes){
        dia = parseInt(dia);
        for(var ox = 1 ; ox <= dia;ox++){
            
            if(ox < 10){
                $('#0' + ox + mes + año).html("<p class='inactiveCalendario'>" + ox + "</p>");
                //console.log('#0' + ox + mes + año);
               $('#0' + ox + mes + año).removeAttr('onclick');
            }else{
                $('#' + ox + mes + año).html("<p class='inactiveCalendario'>" + ox + "</p>");
                //console.log('#' + ox + mes + año);
                 $('#' + ox + mes + año).removeAttr('onclick');
            }
        }
        
    }*/
            }
         	if(conteoClicks == 1){
         	  
			  var fechaInicio = sessionStorage.getItem("fechaInicioCambio");
			  var diaCorto = sessionStorage.getItem("dia");
			  //console.log("fecha inicio : "+ fechaInicio);
			  //console.log("Diacorto : " + diaCorto);
			  $('#' + fechaInicio).html("<p class='selectCalendario'>" + diaCorto + "</p>");
         	}
         	
         	else if(conteoClicks == 2){
         	    
         if(mesActual == 2 && año == '2020' || mesActual == 2 && año == '2024' || mesActual == 2 && año == '2028'){
             var limiteInicio=["","31","29","31","30","31","30","31","31","30","31","30","31"];  
             //console.log("adios 29");
         }
         else{
             var limiteInicio=["","31","28","31","30","31","30","31","31","30","31","30","31"];  
             //console.log("adios 28");
         }  
             //console.log(limiteInicio);
             
         	 var diaInicio = sessionStorage.getItem("dia");
         	 var mesInicio =  sessionStorage.getItem("mes"); 
         	 var añoInicio =  sessionStorage.getItem("año");
         	 
         	 var diaFin = sessionStorage.getItem("diaFin");
         	 var mesFin = sessionStorage.getItem("mesFin"); 
         	 var añoFin = sessionStorage.getItem("añoFin"); 
         	 
         	 
         	 //console.log(diaInicio + mesInicio + añoInicio);
         	 
         	 //var limite = limiteInicio[11];
         	 var limiteMesIicio = limiteInicio[mesActual];
         	 limiteMesInicio = parseInt(limiteMesIicio);
         	 //if(diaInicio < 10)
         	 //var diaInicioFor = parseInt(diaInicio.substr(1,1));
         	 //else
         	 var diaInicioFor = parseInt(diaInicio);
         	 var diferencia = limiteMesInicio - diaInicioFor;
         	 var diferencia = parseInt(diferencia);
         	 //console.log(diaInicioFor);
         	 
         	 
         	 
         	 /*---------Fecha de un mes en otro*/
         	 
         	 if(mesInicio != mesFin)
         	 {
         	     
               if(mesActual == mesInicio){

                    //console.log(diferencia);
                     for(var xp = 0; xp <= diferencia; xp ++){
                         //console.log(xp + " hola");
                          if(diaInicioFor < 10){
                              $('#0' + diaInicioFor + mesInicio + añoInicio).html("<p class='selectCalendario'>" + diaInicioFor + "</p");
                              //console.log("#" + diaFin + mesFin + añoFin);
                              diaInicioFor = diaInicioFor + 1;
                              //console.log(diaInicioFor)
                          }
                          else{
                              $('#' + diaInicioFor + mesInicio + añoInicio).html("<p class='selectCalendario'>" + diaInicioFor + "</p");
                              diaInicioFor = diaInicioFor + 1;
                              //console.log('#' + diaInicio + mesInicio + añoInicio);
                              //console.log(diaInicioFor); 
                          } 
                        
                        
                     }
                 }else if(mesActual == mesFin){
                 //console.log("hola prro");
                     for(var auxM = 1; auxM <= diaFin; auxM ++){
                          if(auxM < 10){
                              //console.log("hola prrote");
                              $('#0' + auxM + mesFin + añoFin).html("<p class='selectCalendario'>" + auxM + "</p");
                              //console.log("#0" + auxM + mesFin + añoFin);
                          }
                          else{
                              $('#' + xp + mesFin + añoFin).html("<p class='selectCalendario'>" + xp + "</p");
                              //console.log("#" + auxM + mesFin + añoFin);

                          } 
                        
                        
                     }
                 
             }
         	 }
         	 //---------Fecha del mismo mes
         	 else{
         	     
         	     diaInicio = parseInt(diaInicio);
         	     diaFin = parseInt(diaFin);
         	     
         	     //console.log(diaInicio + " dia Inicio")
         	     //console.log(diaFin + " dia fin");
         	      for(var auxM = 0; diaInicio <= diaFin; diaInicio ++){
                          if(diaInicio < 10){
                              $('#0' + diaInicio + mesFin + añoFin).html("<p class='selectCalendario'>" + diaInicio + "</p");
                              //console.log("#0" + diaInicio + mesFin + añoFin);
                          }
                          else{
                              $('#' + diaInicio + mesFin + añoFin).html("<p class='selectCalendario'>" + diaInicio + "</p");
                              //console.log("#" + diaInicio + mesFin + añoFin);

                          } 
                     }
         	     
         	 }
         	 
         	 
         	 
         	}
			
		});
    
}

function seleccionarCalendario(id){
    if(bandera == 1){
        var last = sessionStorage.getItem("last");
        console.log(last);
        //console.log("holaaaaaaaaa" + last.length);
        var diaAux = last.substr(-20,2);
        
        if(diaAux < 10){
            diaAux = diaAux.substr(1,1);
            $('#'+ last).html("<p class='activeCalendario'>"+ diaAux + "</p></div>");
        }else{
            $('#'+ last).html("<p class='activeCalendario'>"+ diaAux + "</p></div>");
        }
        
        
        
    }
    //console.log(id.id);
    var x = id.id;
    dateSelect = id.id;
    console.log(dateSelect);
    var dia = x.substr(-20,2);
    var mes = x.substr(2,2);
    var año = x.substr(4,4);
    
    if(mes < 10){
       mes = mes.substr(1,1); 
    }
    
     var diaElegido = nombreDia(dia,mes,año,true);
     var mesElegido = nombreDia(dia,mes,año,false);
    
    if(dia < 10){
        var diaCorto = dia.substr(1,1);
        $('#' + x).html("<p class='activeCalendario' style='background-color:rgb(0, 132, 137);color:white'>"+ diaCorto + "</p></div>")
        $('#colDiaElegido').html("<p style='font-size: 13px;margin-bottom:0px;'>"+ diaElegido+ ", " + diaCorto + " "+ mesElegido + " "+año+"</p>");
    }
    
    else{
        $('#' + x).html("<p class='activeCalendario' style='background-color:rgb(0, 132, 137);color:white;'>"+ dia + "</p></div>")
        $('#colDiaElegido').html("<p style='font-size: 13px;margin-bottom:0px;'>"+ diaElegido+ ", " + dia + " "+ mesElegido + " "+año+"</p>");
    }
    
    $('#colInfo').css('display','block');
    sessionStorage.setItem("last", x);
    bandera = 1;
    
    //console.log(diaElegido);
}

function rangos(id){
    
 if(conteoClicks == 0){

     $('#colInfo').css('display','none');
    //////console.log(id);
    var x = id.id;
    var dia = x.substr(-20,2);
    var mes = x.substr(2,2);
    var año = x.substr(4,4);
    sessionStorage.setItem("dia", dia);
    sessionStorage.setItem("mes", mes);
    sessionStorage.setItem("año", año);
    //////console.log(año+'-'+mes+'-'+dia);
    var fechaInicio = new Date(año+'-'+mes+'-'+dia).getTime();
    //////console.log(fechaInicio);
    sessionStorage.setItem("fechaInicio", fechaInicio);
    sessionStorage.setItem("fechaInicioCambio",dia + mes + año);
     
     
    if(dia < 10){
        var diaCorto = dia.substr(1,1);
        sessionStorage.setItem("dia", diaCorto);
        console.log('#' + x)
        $('#' + x).html("<p class='selectCalendario'>"+ diaCorto + "</p></div>");

    }else{
        console.log('#' + x);
         $('#' + x).html("<p class='selectCalendario'>"+ dia + "</p></div>");
    }
     
     
     conteoClicks = 1;
     reinicio = 1;
     
 }else if(conteoClicks == 1){
 /*----------Mes Incio---------------*/    
    var fechaInicio = sessionStorage.getItem("fechaInicio");
    var diaInicio = sessionStorage.getItem("dia");
    var mesInicio = sessionStorage.getItem("mes");
    var añoInicio = sessionStorage.getItem("año");
    var fechaCompletaInicio = diaInicio+mesInicio+añoInicio;
    ////////console.log(diaInicio);
    var diaCortoInicio = diaInicio;
    if(diaInicio < 10){
    diaCortoInicio = diaInicio.substr(1,1);
    }
     
/*------------Mes Fin-------------*/
    var x = id.id;
    var diaFin = x.substr(-20,2); 
    var mesFin = x.substr(2,2);
    var añoFin = x.substr(4,4);
    
    sessionStorage.setItem("diaFin", diaFin);
    sessionStorage.setItem("mesFin", mesFin);
    sessionStorage.setItem("añoFin", añoFin);
    
    var diasInactivos = JSON.parse(sessionStorage.getItem("diasInactivos"));
    
    
 /*---------------Operacion para saber la diferencía de días-----------------*/
    var fechaFin  =  new Date(añoFin+'-'+mesFin+'-'+diaFin).getTime();
    var diff = fechaFin - fechaInicio;
    diff= diff/(1000*60*60*24);
    if(diff <= 0){
      conteoClicks = 0;
      if(diaInicio < 10){
        $('#0' + diaInicio + mesInicio + añoInicio).html("<p class='activeCalendario'>" + diaInicio + "</p>");
      }else{
        $('#' + diaInicio + mesInicio + añoInicio).html("<p class='activeCalendario'>" + diaInicio + "</p>");
      }
      
      rangos(id);
    }else{
        
    
    if(mesInicio < 10)
    var mesDias = mesInicio.substr(1,1);
    if(diaFin < 10)
    $('#' + diaFin + mesFin + añoFin).html("<p class='selectCalendario'>" + diaFin.substr(1,1) + "</p");
    else
    $('#' + diaFin + mesFin + añoFin).html("<p class='selectCalendario'>" + diaFin + "</p");
    

    if(mesDias == 2 && añoInicio == '2020' || mesDias == 2 && añoInicio == '2024' || mesDias == 2 && añoInicio == '2028'){
         var limiteInicio=["","31","29","31","30","31","30","31","31","30","31","30","31"];  
         ////console.log("Hola 29");
       if( mesInicio != mesFin){ 
         for(y = 1 ; y = diaFin ; diaFin--){
             
             if(diaFin < 10){
             $('#0' + diaFin + mesFin + añoFin).html("<p class='selectCalendario'>" + diaFin + "</p");
             ////console.log("#0" + diaFin + mesFin + añoFin);
             }
             else
              $('#' + diaFin + mesFin + añoFin).html("<p class='selectCalendario'>" + diaFin + "</p");
              ////console.log('#' + diaFin + mesFin + añoFin);
         }
        }
    }
    else{
         var limiteInicio=["","31","28","31","30","31","30","31","31","30","31","30","31"];  
         ////console.log("hola 28");
         
         var fechaComplete;
         //diaFin = sessionStorage.getItem("diaFin");
         ////console.log(diaFin)
         diaFin = parseInt(diaFin);
         
         
        if( mesInicio != mesFin){ 
         for(y = 1 ; y = diaFin ; diaFin--){
             if(diaFin < 10){
             $('#0' + diaFin + mesFin + añoFin).html("<p class='selectCalendario'>" + diaFin + "</p");
             ////console.log("#0" + diaFin + mesFin + añoFin);
             }
             else{
              $('#' + diaFin + mesFin + añoFin).html("<p class='selectCalendario'>" + diaFin + "</p");
              ////console.log('#' + diaFin + mesFin + añoFin);
             }
         }
        }
    }
    
    valoresUnico = diasInactivos.filter (
        
    
        (value,pos,self) => {
    
            return pos === self.indexOf(value);
    
        }
    
    );
    diasAbiertos();
    
    ////////console.log(diaInicio+mesInicio+añoInicio);
    ////console.log(limiteInicio[mesInicio]);
    resultado = parseInt(diaInicio);
    for(var z = 1; z <= diff; z++){
        resultado = resultado + 1;
        ////console.log(resultado  + "       : resultado");
        if(resultado < 10){
           for(var i = 0 ; i <= valoresUnico.length; i++){

                 fechaComplete = "0" +resultado + mesFin + añoFin;
                 ////console.log(fechaComplete);
                 if(BanderaAux == 0){
                 if(valoresUnico[i] == fechaComplete ){    
                   alert("No es posible ya que hay un dia que no esta disponible ");
                 //  alert("hola");
                   ////console.log(fechaComplete);
                   Banderas = 1;
                   BanderaAux = 1;
                 }      
                 }
             }
         $('#0'+ resultado + mesInicio + añoInicio).html("<p class='selectCalendario'>"+ resultado + "</p></div>");
        }   
        else{
            for(var i = 0 ; i <= valoresUnico.length; i++){
                if(resultado < 10)
                 fechaComplete = "0" +resultado + mesFin + añoFin;
                else 
                fechaComplete = resultado + mesFin + añoFin; 
                if(BanderaAux == 0){
            if(resultado > limiteInicio[mesInicio]){
                   resultado = 1;
               }
                    ////console.log("Dias inactivos : " + diasInactivos[i]);
                    ////console.log("fecha complete : " + fechaComplete);
                 if(valoresUnico[i] == fechaComplete ){
                   alert("No es posible ya que hay un dia que no esta disponible");
                   //alert("adios");
                   ////console.log(fechaComplete); 
                   Banderas = 1;
                   BanderaAux = 1;
                 }
                }
             }
         $('#'+ resultado + mesInicio + añoInicio).html("<p class='selectCalendario'>"+ resultado + "</p></div>");
        }
    }
    
    if(mesInicio < 10)
     mesInicio = mesInicio.substr(1,1);
     
     var diaElegido = nombreDia(diaInicio,mesInicio,añoInicio,true);
     var mesElegido = nombreDia(diaInicio,mesInicio,añoInicio,false);
     
     
     diaFin = sessionStorage.getItem("diaFin");
     mesFin = sessionStorage.getItem("mesFin");
     añoFin = sessionStorage.getItem("añoFin");
     if(añoFin < 10)
      añoFin = añoFin.substr(1,1);
     if(mesFin < 10){
      console.log(mesFin);
      var mesPFin =  mesFin;     
      mesFin = mesFin.substr(1,1);
     }
      if(añoInicio < 10)
      añoInicio = añoInicio.substr(1,1);
     if(mesInicio < 10){
      console.log(mesInicio);
      var mesPInicio = "0" + mesInicio;
      mesInicio = mesInicio.substr(1,1);

     }
      
     
     
     var diaElegidoFin = nombreDia(diaFin,mesFin,añoFin,true);
     var mesElegidoFin = nombreDia(diaFin,mesFin,añoFin,false);
     
     ////console.log(diaElegido + diaInicio + mesElegido + añoInicio);
     ////console.log(diaElegidoFin + diaFin + mesElegidoFin + añoFin);
    

    $('#colDiaElegido').html("<p style='font-size: 13px;margin-bottom:0px;' name='diaInicioInfo' id='"+diaInicio + "-" + mesPInicio + "-" +añoInicio+"'>"+ diaElegido+ ", " + diaInicio + " "+ mesElegido + " "+añoInicio+" -</p>");
    $('#colDiaElegido').append("<p style='font-size: 13px;margin-bottom:0px;' name='diaFinInfo' id='"+diaFin + "-" + mesPFin + "-" + añoFin+"'>"+ diaElegidoFin+ ", " + diaFin + " "+ mesElegidoFin+ " "+añoFin+"</p>");
    
    ////console.log(diaInicio);
    if(diaInicio < 10)
    $('#0' + diaInicio + mesInicio + añoInicio).html("<p class='selectCalendario'>"+ diaInicio +"</p>");
    else
    $('#' + diaInicio + mesInicio + añoInicio).html("<p class='selectCalendario'>"+ diaInicio +"</p>");
    

    $('#colInfo').css('display','block');
   // ////console.log("BANDERAAAAAAAAAAA" + Banderas);
   
   ////console.log(Banderas);
    if(Banderas == 1){
        //////console.log("si entra wtf");
        resultado = parseInt(diaInicio);
        
    for(var z = 1; z <= diff; z++){
        for(var i = 0 ; i <= diasInactivos.length; i++){
            
            if(mesFin < 10){
                if(resultado < 10)
                 fechaComplete = "0" +resultado + "0" + mesFin + añoFin;
                else
                fechaComplete = resultado + "0" + mesFin + añoFin;
            }else{
                if(resultado < 10)
                 fechaComplete = "0" +resultado + mesFin + añoFin;
                else
                fechaComplete = resultado + mesFin + añoFin;
            }
              
             //  console.log(fechaComplete + "          xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx")
                 if(diasInactivos[i] == fechaComplete){
                   // console.log(diasInactivos[i]);
                    
                     ////console.log(diasInactivos[i].substr(0,2));
                   if(diasInactivos[i].substr(0,2) < 10){
                     console.log('#' + diasInactivos[i]);
                   $('#' + diasInactivos[i]).html("<p class='inactiveCalendario'>" + diasInactivos[i].substr(1,1)+"</p>");
                   }
                   else{
                   $('#' + diasInactivos[i]).html("<p class='inactiveCalendario'>" + diasInactivos[i].substr(0,2)+"</p>");
                   }
                 }
        }

        resultado = resultado + 1;

        mesInicio = sessionStorage.getItem("mes");
        if(resultado < 10){

         $('#0'+ resultado + mesInicio + añoInicio).html("<p class='activeCalendario'>"+ resultado + "</p></div>");
        // ////console.log('#0'+ resultado + mesInicio + añoInicio);
        }
        else{
         $('#'+ resultado + mesInicio + añoInicio).html("<p class='activeCalendario'>"+ resultado + "</p></div>");
         //////console.log('#'+ resultado + mesInicio + añoInicio);
        }
        
    }
    
    if(mesInicio != mesFin){

    for(let po = 1 ; po <= diaFin ; po++){
        ////console.log("holaaaaaaaps");
        if(mesFin < 10){
        if(po < 10){
            $('#0' + po + "0" + mesFin + añoFin).html("<p class='activeCalendario'>"+ po + "</p></div>");
            ////console.log('#0' + po + "0" + mesFin + añoFin);
          }
        else{
         $('#' + po + "0" + mesFin + añoFin).html("<p class='activeCalendario'>"+ po + "</p></div>");
         ////console.log('#' + po + "0" + mesFin + añoFin);
           }
        }else{
            
        if(po < 10){
            $('#0' + po + mesFin + añoFin).html("<p class='activeCalendario'>"+ po + "</p></div>");
            ////console.log('#0' + po + mesFin + añoFin);
          }
        else{
         $('#' + po + mesFin + añoFin).html("<p class='activeCalendario'>"+ po + "</p></div>");
         ////console.log('#' + po + mesFin + añoFin);
           }
        }
    }
    diasInactivosFuncion(0);
    diasAbiertos();
     
    }
    
    diasInactivosFuncion();
    diasAbiertos();
    ////console.log(mesFin + añoFin);
    conteoClicks = 0;
    Banderas = 0;
    BanderaAux = 0;
    $('#colInfo').css('display','none');
    }else{
     conteoClicks = 2;
    }    
    }
 }
 else if(conteoClicks == 2){
     var x = id.id;
     var mesActual = x.substr(2,2);
     var año = x.substr(4,4);
     var diaSelect = x.substr(-20,2)
     
     if(mesActual < 10)
     var mesActual = mesActual.substr(1,1);
      
     ////console.log(año);
     ////console.log(mesActual);
     ////console.log(diaSelect);
     
        $.get('https://panoramex.mx/public/calendarioClass.php', 'data=' + JSON.stringify({
			action: 'nextCalendario',
			mesActual,
			año,
			dia,
			rango,

			
		}),function(response){
		    var mess = auxiliar.getMonth() + 1;
			$('#rowPrincipal').html(response);
             diasInactivosFuncion();
             diasAbiertos();
			if(mesActual == mess){
			    $('#preview').css('opacity','0.3'); 
			    $('#preview').removeAttr('onclick');
			}
			if(diaSelect < 10)
			diaSelectCorto = diaSelect.substr(1,1);
			
			
			if(mesActual < 10){
			  if(diaSelect < 10){
			    $('#' + diaSelect + "0" + mesActual + año).html("<p class='selectCalendario' >" + diaSelectCorto + "</p>");
			  }
			  else{
			    $('#' + diaSelect + "0" + mesActual + año).html("<p class='selectCalendario'>" + diaSelect + "</p>");
			  }
		  }else{
			  if(diaSelect < 10){
			    $('#' + diaSelect + mesActual + año).html("<p class='selectCalendario'>" + diaSelectCorto + "</p>");
			  }
			  else{
			    $('#' + diaSelect + mesActual + año).html("<p class='selectCalendario'>" + diaSelect + "</p>");
			  }
			}
	  var mes = auxiliar.getMonth() + 1;
      ////console.log(mesActual + mes);
	  if(mesActual == mes){
	    var dia = auxiliar.getDate();
        dia = parseInt(dia);
        ////console.log(dia);
        ////console.log(diasAnteriores);
        if(diasAnteriores == false){
        for(var ox = 1 ; ox < dia;ox++){
            if(ox < 10){
                $('#0' + ox + mes + año).html("<p class='inactiveCalendario'>" + ox + "</p>");
                ////console.log('#0' + ox + mes + año);
               $('#0' + ox + mes + año).removeAttr('onclick');
            }else{
                $('#' + ox + mes + año).html("<p class='inactiveCalendario'>" + ox + "</p>");
                ////console.log('#' + ox + mes + año);
                 $('#' + ox + mes + año).removeAttr('onclick');
            }
        }
        
	  }
        
    }
    
			//diasInactivosFuncion(mesActual,año);
		});
     
    conteoClicks = 0 ;
	rangos(id);
     
}
}

function nombreDia(dia,mes,año,bol){
    
    var diasName=["Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado"];
    var mesName=["","Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    var mesElegido = mesName[mes];
    var dg = "/";
    var dateComplete = año + dg + mes + dg + dia;
    var dt = new Date(dateComplete);
    var diaElegido = diasName[dt.getUTCDay()];
    
    
    if(bol == true)
      return diaElegido;
    else if(bol == false)
      return mesElegido;
}

function crearCalendario(data){
    console.log(data);
    idInput = data.id;
    excepcion = data.excepcion;
    //console.log(data.daysWeekClose);
    //console.log(data.datesClose);
    //console.log(data.rango);
    
    if(data.datesClose == undefined)
     data.datesClose = [];
     
    if(data.rango == undefined)
      data.rango = false;
    
    if(data.excepcion == undefined)
      data.excepcion = [];
    
    if(data.daysWeekClose == undefined)
      data.daysWeekClose = [];
      
    if(data.id == undefined)
      data.id = 0;
      
    if(data.diasAnteriores == undefined)
      data.diasAnteriores = true;
      
    if(data.idSalida == undefined)
      data.idSalida = "";
    
    idInputSalida = data.idSalida;
    diasAnteriores = [];
    diasAnteriores = data.diasAnteriores;
    rango = data.rango;
    var idElemento = idElemento;
    var x;
    var diasInactivos = [];
    diasInactivos = data.daysWeekClose;
    var respuesta = '';
    excepciones = [];
    excepciones = data.excepcion;
    fechasInactivas = [];
    console.log(data.datesClose);
    fechasInactivas = data.datesClose;
    diasSemanaInactivos = [];
    diasSemanaInactivos = data.daysWeekClose;
    lengthArray = fechasInactivas.length;
    
    var data = JSON.stringify(data);
    
    
    ////console.log(diasSemanaInactivos);
    
    
    
   $.ajaxSetup({async:false});
   $.get('https://panoramex.mx/public/calendarioClass.php', 'data=' + JSON.stringify({
		action: 'instanciarCalendario',
		data,
	}),function(response){
        respuesta = response;
	});
	$.ajaxSetup({async:true});
 
    return respuesta;  
}

function diasInactivosFuncion(x){
    
    if(x != undefined)
      auxDI = 0;
    

if(diasSemanaInactivos != undefined){
    let limiteArray = fechasInactivas.length;
     ////console.log(fechasInactivas);
    fechasInactivas.splice(lengthArray,limiteArray);
    ////console.log(fechasInactivas);


    let mesActual = $('#mesActualInput').val();
    let añoActual = $('#añoActualInput').val(); 
    let numLimite = document.getElementById('mesActualInput').name;
    ////console.log(añoActual);

    if(auxDI != 0){
        if(mesActual < 10)
           mesActual = "0" + mesActual;
    }     
        
        console.log(fechasInactivas);

        
        for( let x = 1; x <= numLimite ; x++){
            console.log("año actual : " + añoActual + " Mes Actual : " + mesActual);
            aux = new Date(añoActual + "-" + mesActual + "-" + x);
            aux = 1 + aux.getDay();
            
              
               if(diasSemanaInactivos.includes(aux) == 1){

                if(x < 10){
                     fechasInactivas.push(añoActual + "-" +mesActual + "-" + "0" + x);
                     diasInactivos.push("0" + x + mesActual + añoActual);
                }
                else{
                     fechasInactivas.push(añoActual + "-" +mesActual + "-"  + x);
                     diasInactivos.push( x + mesActual + añoActual);
                }
            }
        }
    auxDI++;
}

console.log(fechasInactivas);

 for(x = 0 ; x < fechasInactivas.length;x++){
        let dia = fechasInactivas[x].substr(8,2);
        let mes = fechasInactivas[x].substr(5,2);
        let año = fechasInactivas[x].substr(-20,4);
        
        if(dia <10){
        $('#' + dia + mes + año).html("<p class='inactiveCalendario'>" + dia.substr(1,1) + "</p>");
        }
        else{
        $('#' + dia + mes + año).html("<p class='inactiveCalendario'>" + dia + "</p>");
        }
        $('#' + dia + mes + año).removeAttr('onclick');
        //////console.log('#' + dia + mes + año)
        diasInactivos.push(dia+mes+año);
}

   valoresUnico = diasInactivos.filter (
    

    (value,pos,self) => {

        return pos === self.indexOf(value);

    }

);

 ////console.log(valoresUnico);
  sessionStorage.setItem("diasInactivos", JSON.stringify(valoresUnico));
  diasAbiertos();

}

function fechaAños(){
    let añoActual = año - 1;
    ////console.log(añoActual);
    
    $('#rowPrincipal').html("");
    
    $('#rowPrincipal').append("<div class='col-12' style='text-align: end;font-size: 30px;color: darkgray;'>"+
    "<i class='fas fa-caret-up' style='cursor:pointer;' onclick='previewYear();' ></i>"+
    "<i class='fas fa-caret-down' style='margin-left: 10px;cursor:pointer;' onclick='nextYear();' ></i>"+
    "</div>");
    
    for(let x = 1 ; x <= 4 ; x++){

        for(let y = 1 ; y <= 3 ; y++ ){
            $('#rowPrincipal').append("<div class='col-4 LM'  onclick='yearSelect(this);'  id='"+añoActual+"' ><p  class='pAño'>" + añoActual + "</div>" );
            añoActual = añoActual - 1;
        }
    }
    año = parseInt(año);
    añoActualList = año;
    //sessionStorage.setItem("princioAño", princioAño);
    //sessionStorage.setItem("finAño", finAño);
    
}

function nextYear(){
    //console.log(añoActualList);
    añoActualList = añoActualList + 12;
    //console.log(añoActualList);
    
    $('#rowPrincipal').html("");
    
    $('#rowPrincipal').append("<div class='col-12' style='text-align: end;font-size: 30px;color: darkgray;'>"+
    "<i class='fas fa-caret-up' style='cursor:pointer;' onclick='previewYear();' ></i>"+
    "<i class='fas fa-caret-down' style='margin-left: 10px;cursor:pointer;' onclick='nextYear();' ></i>"+
    "</div>");
    
    for(let x = 1 ; x <= 4 ; x++){

        for(let y = 1 ; y <= 3 ; y++ ){
            $('#rowPrincipal').append("<div class='col-4 LM'  onclick='yearSelect(this);'  id='"+añoActualList+"'><p class='pAño'>" + añoActualList + "</div>" );
            añoActualList = añoActualList - 1;
        }
    }
    añoActualList = añoActualList + 12;
}

function previewYear(){
     
     añoActualList = añoActualList - 12;
    
    
    $('#rowPrincipal').html("");
    
    $('#rowPrincipal').append("<div class='col-12' style='text-align: end;font-size: 30px;color: darkgray;'>"+
    "<i class='fas fa-caret-up' style='cursor:pointer;' onclick='previewYear();' ></i>"+
    "<i class='fas fa-caret-down' style='margin-left: 10px;cursor:pointer;' onclick='nextYear();' ></i>"+
    "</div>");
    
    for(let x = 1 ; x <= 4 ; x++){

        for(let y = 1 ; y <= 3 ; y++ ){
            //console.log(añoActualList);
            $('#rowPrincipal').append("<div class='col-4 LM'  onclick='yearSelect(this);'  id='"+añoActualList+"'><p class='pAño'>" + añoActualList + "</div>" );
            añoActualList = añoActualList - 1;
        }
    }
    añoActualList = añoActualList + 12;
    
}

function yearSelect(id){
    mesActual = mes;
    //console.log(mes);
    año = id.id;
    dia = 0;
    
    
$.get('https://panoramex.mx/public/calendarioClass.php', 'data=' + JSON.stringify({
	 action: 'nextCalendario',
	 mesActual,
	 año,
	 dia,
	 rango,
	}),function(response){
	    $('#rowPrincipal').html(response);
	    
	})
	
	
}

function diasAbiertos(){
   if(rango == false)
      var auxRango = "seleccionarCalendario(this)";
    else
      var auxRango = "rangos(this)";
    
    var excepcionAux = [];
    exepcionAux = excepcion;
    let limit = exepcionAux.length;
   console.log(exepcionAux);
   
   if(limit == 1 && exepcionAux == ""){
       return 0;
   }else{
    
    for(let x = 0 ; x < limit ; x++){
        let dia = exepcionAux[x].substr(8,2);
        let mes = exepcionAux[x].substr(5,2);
        let año = exepcionAux[x].substr(-20,4);
        
    
        
          if(dia <10){
              console.log(('#' + dia + mes + año))
              $('#' + dia + mes + año).html("<p class='activeCalendario'>" + dia.substr(1,1) + "</p>");
              $('#' + dia + mes + año).attr("onclick",auxRango);
                          
              var indice = valoresUnico.indexOf(dia + mes + año); // obtenemos el indice
             // //console.log(dia + mes + año);
             // //console.log(indice);  
              valoresUnico.splice(indice, 1); // 1 es la cantidad de elemento a eliminar
              
              
               indice = diasInactivos.indexOf(dia + mes + año); // obtenemos el indice
              //console.log(dia + mes + año);
              //console.log(indice);
              //console.log(diasInactivos);
              diasInactivos.splice(indice, 1); // 1 es la cantidad de elemento a eliminar              
          }
          else{
              $('#' + dia + mes + año).html("<p class='activeCalendario'>" + dia + "</p>");
              $('#' + dia + mes + año).attr("onclick",auxRango);
              
                            
              var indice = valoresUnico.indexOf(dia + mes + año); // obtenemos el indice
             // //console.log(dia + mes + año);
             // //console.log(indice);
              valoresUnico.splice(indice, 1); // 1 es la cantidad de elemento a eliminar
              
               indice = diasInactivos.indexOf(dia + mes + año); // obtenemos el indice
              //console.log(dia + mes + año);
              //console.log(indice);  
              //console.log(diasInactivos);
              diasInactivos.splice(indice, 1); // 1 es la cantidad de elemento a eliminar 
          }
    }   
    
   }
}
    
function diaSelects(){
    
    let dia = dateSelect.substr(0,2);
    let mes = dateSelect.substr(2,2);
    let año = dateSelect.substr(4,4);
    //console.log(idInput);
    //console.log(dia + mes + año);
    
    $("#" + idInput).val(año + "-" + mes + "-" + dia);
    
    sessionStorage.removeItem("last");
    bandera = 0;
}

function diasRango(){
    //var elName =  $('p[name ="diaInicioInfo"]');  
    
    var diaInicio = document.getElementsByName("diaInicioInfo");
    var idInicio = diaInicio[0].getAttribute( 'id' );
    var diaFin = document.getElementsByName("diaFinInfo");
    var idFin = diaFin[0].getAttribute('id');
    
    console.log(idFin);
    
    $("#" + idInput).val(idInicio);
    $("#" + idInputSalida).val(idFin);

}






