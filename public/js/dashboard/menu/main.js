    $(document).ready( function () {
    var languages = '';;
    //Construye menu dashboard (otra opcion mas tardado)
    //$.get('https://panoramex.mx/dashboard/accessControl',`data=${JSON.stringify({
    //    action:'menuDashboard'
    //})}`,function(response){
    //    console.log(response);
    //    //$('.items-navbar-menu-panoramex').html(response);
    //});
    
    $('#Menu').click(function(){
        
        $.get('https://panoramex.mx/dashboard/tools',`data=${JSON.stringify({
           action:'codigo_construccion_menu' 
        })}`,function(response){
            $('#content-dashboard').html(response);
            //response = JSON.parse(response);
            //if(response.error == 'false'){
            //    $('#content-dashboard').html(response.code);     
            //    
            //    
            //}else{
            //    if(response.error == 'true'){
            //        if(response.action == 'redireccionar'){
            //            window.location = response.url;
            //        }
            //    }
            //}
            
        });
    });
    
    
});

    var lista = document.getElementById('menu-list');
    console.log(lista);
    var arregloItems = [];
    var elemento = 'null';
    var elementoDrag = 'null';
    var evitarEliminarElMismo = 'null';
    var elementoAuxUpdate = '';
    var mouse = 'null';
    var cantidadElementos = 0;
    
    
    function enterDrag(e){
            e.preventDefault();
        }
    
    function agregarElemento(){
           var url = $('#value-url').val();
           var nombre = $('#value-nombre').val();
           
           if(url && nombre){
               
           
           
               var item = document.createElement('li');
               
               item.classList.add('item-list-menu');
               item.classList.add('item-list-cont');
               item.classList.add('principal');
               item.setAttribute('draggable','true');
               //item.setAttribute('title',"<div><input class='form-control' placeholder=''></div>");
               item.setAttribute('data-url',url);
               item.setAttribute('onmouseover','overMouse(this);');
               item.setAttribute('onmouseout','outMouse(this);');
               item.setAttribute('id','item-menu-'+cantidadElementos);
               
               
               item.innerHTML= '<div class="span-item">'+nombre+'</div><ul class="listas-interior nivel" id="nivel-'+cantidadElementos+'"><div class="agregar-sub-menu"></div></ul>'+
               '<div class="cont-btn-item"><button onclick="editItem(this);" class="btn collapsed" data-toggle="collapse" data-target="#cont-edit-item-menu-'+cantidadElementos+'" id="edit-item-menu-'+cantidadElementos+'"><i class="far fa-edit"></i></button></div>'+
               '<div id="cont-edit-item-menu-'+cantidadElementos+'" class="collapse">'+
               '<div>'+
                                    '<div class="row" style="padding: 10px;">'+
                                        '<div class="col-4" style="text-align: right;">'+
                                            '<label class="label-input-menu">URL</label>'+
                                        '</div>'+
                                        '<div class="col-8">'+
                                            '<input id="value-url-'+cantidadElementos+'" class="form-control form-control-sm input-menu" placeholder="https://">'+
                                        '</div>'+
                                    '</div>'+
                                    '<div class="row" style="padding: 10px;">'+
                                        '<div class="col-4" style="text-align: right;">'+
                                            '<label class="label-input-menu">Link Text</label>'+
                                        '</div>'+
                                        '<div class="col-8">'+
                                            '<input id="value-nombre-'+cantidadElementos+'" class="form-control form-control-sm input-menu" placeholder="nombre link">'+
                                        '</div>'+
                                    '</div>'+
                                '<div class="" style="padding: 10px;text-align: right;/* justify-content: right; *//* display: flex; */">'+
                                    '<a class="" style="padding: 0;cursor:pointer;" onclick="updateItem(this);" id="update-item-menu-'+cantidadElementos+'">'+
                                        '<label style="text-decoration: underline;color: #0073aa;margin: 0;cursor:pointer;" class="text-info">Update</label>'+
                                    '</a>'+
                                    '<label style="margin: 0px 4px 0px 6px;">|</label>'+
                                    '<a class="" style="padding: 0;cursor:pointer;" onclick="removeItem(this);" id="remove-item-menu-'+cantidadElementos+'">'+
                                        '<label style="text-decoration: underline;color: #a00;margin: 0;cursor:pointer;" class="text-danger">Remove</label>'+
                                    '</a>'+
                                    '</div>'+
                                    '</div>'+
               '</div>';
               cantidadElementos++;
               //item.addEventListener('dragstart',inicializaDrag,false);
               //item.addEventListener('dragend',finalizaDrag,false);
               //item.addEventListener('drop',manejarDrop,false);
               
               $('#menu-list').append(item);
               
               $('#menu-list .item-list-menu').each(function(){
                   console.log($(this));
                   $(this)
                    $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                    
                    //$(this)[0].addEventListener('drop',manejarDrop,false);
                    $(this)[0].addEventListener('dragover',overDragSub,false);
                    $(this)[0].addEventListener('dragleave',leaveDragSub,false);
               });
               
               $('#menu-list .item-list-menu .span-item').each(function(){
                   console.log($(this));
                   $(this)
                    $(this)[0].addEventListener('dragover',overDrag,false);
                    $(this)[0].addEventListener('dragleave',leaveDrag,false);
                    $(this)[0].addEventListener('drop',manejarDrop,false);
                    $(this)[0].addEventListener('dragend',finalizaDrag,false);
                    
               })
               $('#menu-list .item-list-menu .listas-interior').each(function(){
                    console.log($(this)[0].childNodes[0]);
                    $(this)[0].childNodes[0].addEventListener('dragover',overDrag,false);
                    $(this)[0].childNodes[0].addEventListener('dragleave',leaveDrag,false);
                    $(this)[0].addEventListener('drop',manejarDropInterior,false);
                    $(this)[0].addEventListener('dragend',finalizaDrag,false);
                    
               })
               $('#value-nombre').val('');
           }else{
                if(!url){
                    $('#value-url').addClass('input-warning');   
                }else{
                    $('#value-url').removeClass('input-warning'); 
                }
                if(!nombre){
                   $('#value-nombre').addClass('input-warning');
                }else{
                    $('#value-nombre').removeClass('input-warning');
                }
           }
       }
    
    function updateItem(element){
            elementoAuxUpdate = element;
            console.log(element.id.substring(17));
            $('#'+element.id.substring(7))[0].dataset.url = $('#value-url-'+element.id.substring(17)).val();
            $('#'+element.id.substring(7))[0].childNodes[0].innerText = $('#value-nombre-'+element.id.substring(17)).val();
            element.childNodes[0].innerHTML = '<i class="fas fa-check-circle"></i>';
            setTimeout(function(){
                element.childNodes[0].innerHTML = 'Update';
            },3000);
        }
      
    function removeItem(element){
            console.log(element.childNodes[0].innerText);
            if(element.childNodes[0].innerText == 'Remove'){
                element.childNodes[0].innerText = 'Yes?';
                setTimeout(function(){
                    element.childNodes[0].innerHTML = 'Remove';
                },3000);
            }else if(element.childNodes[0].innerText == 'Yes?'){
                console.log('#item-menu-'+element.id.substring(17));
                $('#item-menu-'+element.id.substring(17)).remove();
            }
        }
      
    function editItem(element){
           console.log(element);
           console.log(element.id.substring(5));
           console.log($('#'+element.id.substring(5))[0].childNodes[0].innerText);
           console.log('#value-url-'+element.id.substring(15));
           $('#value-nombre-'+element.id.substring(15)).val($('#'+element.id.substring(5))[0].childNodes[0].innerText);
           $('#value-url-'+element.id.substring(15)).val($('#'+element.id.substring(5))[0].dataset.url);
       }
      
    function leaveDragSub(e){
           if(e.preventDefault) { e.preventDefault(); }
            if(e.stopPropagation) { e.stopPropagation(); }
           console.log('leaveDragSub');
           console.log(this.childNodes);
           //this.childNodes[1].style.display = 'none';
       }
      
    function overDragSub(e){
           if(e.preventDefault) { e.preventDefault(); }
            if(e.stopPropagation) { e.stopPropagation(); }
           console.log('overDragSub');
           console.log(this.id);
           //$('.listas-interior').each(function(){
           //     if($(this)[0].childNodes.length > 1){
           //        $(this)[0].style.display = 'block'; 
           //     }else{
           //         $(this)[0].style.display = 'none'; 
           //     }
           //})
           //this.childNodes[1].style.display = 'block';
       }
      
    function overMouse(element){
          //console.log(mouse);
          if(mouse == 'null'){
              mouse = element.id;
          }
       }
      
    function outMouse(element){
           mouse = 'null';
       }
      
    function overDrag(e){
           if(e.preventDefault) { e.preventDefault(); }
            if(e.stopPropagation) { e.stopPropagation(); }
           console.log('dragover');
           console.log(e);
           this.style.border = '1px dotted gray';
           //var target = $(e.target);
           //console.log('indice over '+target.index());
           //var oldIndex = e.dataTransfer.getData('oldIndex');
           
           console.log(window.event.clientY);
           return false;
       }
      
    function leaveDrag(e){
           if(e.preventDefault) { e.preventDefault(); }
            if(e.stopPropagation) { e.stopPropagation(); }
           this.style.border = '1px solid transparent';
       }
      
    function manejarDropInterior(e){
            if(e.preventDefault) { e.preventDefault(); }
            if(e.stopPropagation) { e.stopPropagation(); }
           //$('.listas-interior').each(function(){
           //     if($(this)[0].childNodes.length > 1){
           //        $(this)[0].style.display = 'block'; 
           //     }else{
           //         $(this)[0].style.display = 'none'; 
           //     }
           //})
           this.style.border = '1px solid transparent';
           if(elemento != 'null'){
               
           
               console.log('manejarDropInterior');
               var target = $(e.target);
               console.log(target[0].id);
               console.log(target);
               //target.css('height','auto');
               console.log(elemento.id);
               var id = elemento.id;
               var oldIndex = e.dataTransfer.getData('oldIndex');
               //var dropped = $(this).children().eq(oldIndex);
               if(evitarEliminarElMismo != target.parent()[0].id){
                    if(target[0].localName == 'div'){
                       var idNew = target.parent()[0].id.substring(6);
                    }else{
                       idNew = target[0].id.substring(6);
                    }
                    console.log(elemento.id.substring(10));
                    console.log(idNew);
                    if(elemento.id.substring(10) != idNew){
                       
                   
                       console.log(elemento.id);
                       console.log(target[0].localName);
                        elemento.remove();
                        var datos = e.dataTransfer.getData('text');
                        console.log(target);
                        target.parent().append(datos);
                        target.parent().addClass('nivel');
                        console.log('cantidadItems '+$('#'+id+' .'+'listas-interior')[0].childNodes.length);
                        if($('#'+id+' .'+'listas-interior')[0].childNodes.length == 1){
                            $('#'+id+' .'+'listas-interior')[0].classList.remove('nivel');
                        }
                        if($('#'+id).hasClass('principal')){
                           $('#'+id).removeClass('principal');
                        }
                       
                        if($('#'+id).hasClass('item-list-menu')){
                            $('#'+id).removeClass('item-list-menu');    
                            $('#'+id).addClass('item-list-menu-interior');    
                       }
                       
                       
                        
                        $('#menu-list .item-list-menu .span-item').each(function(){
                         
                            
                            $(this)[0].addEventListener('drop',manejarDrop,false);
                            
                       })
                       $('#menu-list .item-list-menu').each(function(){
                           console.log($(this));
                           
                            $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                            $(this)[0].addEventListener('dragend',finalizaDrag,false);
                            //$(this)[0].addEventListener('drop',manejarDrop,false);
                            //$(this)[0].addEventListener('dragover',overDrag,false);
                       })
                       $('#menu-list .listas-interior').each(function(){
                         
                            $(this)[0].addEventListener('dragover',overDrag,false);
                            $(this)[0].addEventListener('dragleave',leaveDrag,false);
                            $(this)[0].addEventListener('drop',manejarDropInterior,false);
                            
                       });
                       
                       $('#menu-list .item-list-menu-interior').each(function(){
                           console.log($(this));
                           $(this)
                            $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                            $(this)[0].addEventListener('dragend',finalizaDrag,false);
                            //$(this)[0].addEventListener('drop',manejarDrop,false);
                            //$(this)[0].addEventListener('dragover',overDrag,false);
                            //$(this)[0].addEventListener('dragover',overDragSub,false);
                            //$(this)[0].addEventListener('dragleave',leaveDragSub,false);
                       });
                       this.style.display = 'block';
                       elemento = 'null';
                       $('.agregar-sub-menu').each(function(){
                               $(this)[0].style.border = '1px solid transparent'; 
                       });
                       $('.item-list-menu').each(function(){
                               $(this)[0].style.border = '1px solid transparent'; 
                       });
                       $('.listas-interior').each(function(){
                               $(this)[0].style.border = '1px solid transparent'; 
                       });
                    }
               }
               
           }
           
       }
      
    function regrescaDragAndDrop(){
            $('#menu-list .item-list-menu .span-item').each(function(){
                     
                        
                        $(this)[0].addEventListener('drop',manejarDrop,false);
                        
                   })
                   $('#menu-list .listas-interior').each(function(){
                     
                        $(this)[0].addEventListener('dragover',overDrag,false);
                        $(this)[0].addEventListener('drop',manejarDropInterior,false);
                        
                   });
                   $('#menu-list .item-list-menu-interior').each(function(){
                       console.log($(this));
                       $(this)
                        $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                        $(this)[0].addEventListener('dragend',finalizaDrag,false);
                        //$(this)[0].addEventListener('drop',manejarDrop,false);
                        $(this)[0].addEventListener('dragover',overDrag,false);
                   });
                   $('#menu-list .item-list-menu').each(function(){
                       console.log($(this));
                       
                        $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                        $(this)[0].addEventListener('dragend',finalizaDrag,false);
                        //$(this)[0].addEventListener('drop',manejarDrop,false);
                        $(this)[0].addEventListener('dragover',overDrag,false);
                   })
       }
      
    function manejarDrop(e){
            if(e.preventDefault) { e.preventDefault(); }
            if(e.stopPropagation) { e.stopPropagation(); }
           //$('.listas-interior').each(function(){
           //     if($(this)[0].childNodes.length > 1){
           //        $(this)[0].style.display = 'block'; 
           //     }else{
           //         $(this)[0].style.display = 'none'; 
           //     }
           //})
           this.style.border = '1px solid transparent';
           if(elemento != 'null'){
               
           
                console.log('manejarDrop');
                var target = $(e.target).parent();
                console.log(target);
                var id = elemento.id;
                var newIndex = target.index();
                var oldIndex = e.dataTransfer.getData('oldIndex');
                console.log(oldIndex);
                e.preventDefault();
                var datos = e.dataTransfer.getData('text');
                console.log(datos);
                //this.style.display = 'block';
                
                var dropped = $(this).parent().parent().children().eq(oldIndex);
                if(elemento.id != target[0].id){
                    elemento.remove();
                    if(newIndex < oldIndex){
                        console.log('before');
                        console.log(datos);
                        target.before(datos);
                    }else{
                        target.after(datos);
                    }    
                
                
                
                
                    console.log('this');
                    console.log(this);
                    if($('#'+id).parent()[0].id == 'menu-list'){
                        
                    
                       if($('#'+id).hasClass('item-list-menu-interior')){
                            $('#'+id).removeClass('item-list-menu-interior');    
                            $('#'+id).addClass('item-list-menu'); 
                            $('#'+id).addClass('principal');
                       }
                    }else{
                        $('#'+id).removeClass('principal');
                        $('#'+id).addClass('item-list-menu-interior');    
                            $('#'+id).removeClass('item-list-menu'); 
                    }
                    $('#menu-list .item-list-menu .span-item').each(function(){
                     
                        $(this)[0].addEventListener('dragover',overDrag,false);
                        $(this)[0].addEventListener('dragleave',leaveDrag,false);
                        $(this)[0].addEventListener('drop',manejarDrop,false);
                        
                   })
                   $('#menu-list .listas-interior').each(function(){
                     
                        $(this)[0].addEventListener('dragover',overDrag,false);
                        $(this)[0].addEventListener('drop',manejarDropInterior,false);
                        
                   });
                   $('#menu-list .item-list-menu-interior').each(function(){
                       console.log($(this));
                       $(this)
                        $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                        $(this)[0].addEventListener('dragend',finalizaDrag,false);
                        //$(this)[0].addEventListener('drop',manejarDrop,false);
                        //$(this)[0].addEventListener('dragover',overDrag,false);
                   });
                   $('#menu-list .item-list-menu').each(function(){
                       console.log($(this));
                       
                        $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                        $(this)[0].addEventListener('dragend',finalizaDrag,false);
                        //$(this)[0].addEventListener('drop',manejarDrop,false);
                        //$(this)[0].addEventListener('dragover',overDrag,false);
                   })
                   elemento = 'null';
                   $('.agregar-sub-menu').each(function(){
                           $(this)[0].style.border = '1px solid transparent'; 
                   });
                   $('.item-list-menu').each(function(){
                           $(this)[0].style.border = '1px solid transparent'; 
                   })
                   $('.listas-interior').each(function(){
                           $(this)[0].style.border = '1px solid transparent'; 
                   })
                }
            }
            //this += datos;
        }
    
    function inicializaDrag(e){
          
           this.style.border = '1px solid transparent';
           console.log(this);
                   
                if(this.id == mouse){
                    elemento = this; 
                    evitarEliminarElMismo = this.id;
                    console.log(elemento.id);
                    //this.style.backgroundColor = 'blue';
                   var target = $(e.target);
                   console.log(this);
                   console.log(target.index());
                   e.dataTransfer.setData('oldIndex',target.index());
                   console.log
                   //this.style.display = 'none';
                   var padre = document.createElement('p');
                   var clone = this.cloneNode(true);
                   padre.appendChild(clone);
                   e.dataTransfer.setData('text',padre.innerHTML);
                }else{
                   
                }
           
           
       }
      
    function finalizaDrag(e){
           if(e.preventDefault) { e.preventDefault(); }
            if(e.stopPropagation) { e.stopPropagation(); }
           //$('.listas-interior').each(function(){
           //     if($(this)[0].childNodes.length > 1){
           //        $(this)[0].style.display = 'block'; 
           //     }else{
           //         $(this)[0].style.display = 'none'; 
           //     }
           //})
           this.style.border = '1px solid transparent';
           //this.style.backgroundColor = 'gray';
           //$('#menu-list').css('background','white');
           
       }
      
    function cargarMenu(){
            //$('#sitio-menu').val($('select[id=select-sitio]').val());
            changeSelectLanguage();
            var valueSitio = $('select[id=select-sitio]').val();
            var valueLanguage = $('select[id=select-language-sitio]').val();
            if(valueSitio != '-1' && valueLanguage != '-1'){
                console.log('ambos insertados');
                $('#sitio-menu').val($('select[id=select-sitio]').val());
                $.get('https://panoramex.mx/dashboard/tools',`data=${JSON.stringify({
                   action:'recuperaMenu',
                   valueSitio,
                   valueLanguage
                })}`,function(response){
                    
                   $('#menu-list').html(response);
                   $('#menu-list .item-list-menu .span-item').each(function(){
                     
                        $(this)[0].addEventListener('dragover',overDrag,false);
                        $(this)[0].addEventListener('dragleave',leaveDrag,false);
                        $(this)[0].addEventListener('drop',manejarDrop,false);
                        
                   })
                   $('#menu-list .item-list-menu').each(function(){
                       console.log($(this));
                       
                        $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                        $(this)[0].addEventListener('dragend',finalizaDrag,false);
                        //$(this)[0].addEventListener('drop',manejarDrop,false);
                        $(this)[0].addEventListener('dragover',overDrag,false);
                   })
                   $('#menu-list .listas-interior').each(function(){
                     
                        $(this)[0].addEventListener('dragover',overDrag,false);
                        $(this)[0].addEventListener('dragleave',leaveDrag,false);
                        $(this)[0].addEventListener('drop',manejarDropInterior,false);
                        
                   });
                   
                   $('#menu-list .item-list-menu-interior').each(function(){
                       console.log($(this));
                       $(this)
                        $(this)[0].addEventListener('dragstart',inicializaDrag,false);
                        $(this)[0].addEventListener('dragend',finalizaDrag,false);
                        //$(this)[0].addEventListener('drop',manejarDrop,false);
                        $(this)[0].addEventListener('dragover',overDrag,false);
                   });
                   cantidadElementos = $('.item-list-cont').length;
                   var number = 0;
                   $('.item-list-cont').each(function(){
                    //console.log($(this)[0].id.substring(10)); 
                       if($(this)[0].id.substring(10) > number){
                           number = $(this)[0].id.substring(10);
                           
                       }
                       
                   });
                   //console.log(number);
                   cantidadElementos = parseInt(number)+1;
                });
            }else{
             
                if(valueSitio != '-1'){
                   $('#sitio-menu').val($('select[id=select-sitio]').val());
                }else{
                    $('#sitio-menu').val('');
                    $('#menu-list').html('');
                }
                if(valueLanguage != '-1'){
                    changeSelectLanguage();
                }else{
                    $('#select-language-selection > option[value=0]').attr('selected',true);
                    $('#menu-list').html('');
                }
               
            }
           
       }
      
    function changeSelectLanguage(){
            var language = $('select[id=select-language-sitio]').val();
            var len = $('select[id=select-language-selection]')[0].childNodes.length;
            var nodes = $('select[id=select-language-selection]')[0].childNodes;
            for(var i = 0; i<len; i++){
                 if(nodes[i].localName == 'option'){
                     console.log(nodes[i].value);
                     console.log(language);
                     if(nodes[i].value == language){
                         nodes[i].setAttribute('selected','selected');
                         console.log(nodes[i]);
                         //$('#select-language-selection option[value="'+language+'"]').attr('selected','selected');
                     }else{
                        nodes[i].removeAttribute('selected');
                     }
                 }
                
            }
       }
      
    function crearMenu(e){
        
       var arregloAux = [];
        var listDrop ='';
        var finalItems ='';
        var item = '';
        var arrAux = [];
        $('.collapse').each(function(){$(this).removeClass('show')});
        $('.principal').each(function(){
           var start = $('#'+$(this)[0].id+' .nivel').length-1; 
           console.log('---------------');
           console.log($('#'+$(this)[0].id+' .nivel'))
           //recorre las listas
           var principal = $(this);
            for(var i = start; i >= 0;i--){
                
              
          
                //recorre los elementos dentro de una lista
                var idList = $('#'+$(this)[0].id+' .nivel')[i].id;
                console.log($('#'+$(this)[0].id+' .nivel')[i].id);
                $('#'+$('#'+$(this)[0].id+' .nivel')[i].id+' .item-list-menu-interior').each(function(){
                    console.log($('#'+$(this)[0].id+' .span-item')[0].innerText);
                    console.log($(this)[0]);
                    if(arregloAux[$(this)[0].id]){
                        item  = '<li class="item-list-drop"><a class="btn-drop btn-nav-link dropdown-item dropdown-item-nav drop-item-a"  role="button" >'+
                        $('#'+$(this)[0].id+' .span-item')[0].innerText+
                        '<div class="cont-row-nav"><i class="icon-navs fas fa-sort-down"></i></div></a><div class="dropdown-menu nav-drop-right " aria-labelledby="navbarDropdownrv"><ul class="list-drop">'+arregloAux[$(this)[0].id]+'</ul></div></li>';
                    }else{
                        item  = '<li class="item-list-drop"><a class="dropdown-item dropdown-item-nav" href="'+$(this)[0].dataset.url+'">'+$('#'+$(this)[0].id+' .span-item')[0].innerText+'</a></li>';    
                    
                        
                    }
                    if(arregloAux[$(this).parent().parent()[0].id]){
                        console.log(arrAux.includes(arregloAux[$(this).parent().parent()[0].id]));
                        if(!arrAux.includes(item)){
                            arregloAux[$(this).parent().parent()[0].id] = arregloAux[$(this).parent().parent()[0].id]+item;
                            arrAux.push(item);
                            item = '';    
                        }
                        
                    }else{
                        console.log(arrAux.includes(arregloAux[$(this).parent().parent()[0].id]));
                        if(!arrAux.includes(item)){
                            arregloAux[$(this).parent().parent()[0].id] = item;    
                            arrAux.push(item);
                            item = '';    
                        }
                        
                    }
                    
                            
                });
                
                listDrop += '<ul class="list-drop">'+arregloAux[idList]+'</ul>';
                console.log('---------------');
            }
            console.log(arrAux);
            console.log(arregloAux);
            console.log('---------------');
            console.log($(this)[0].id.substring(10)+'identificador para ultimo nivel');
            if($('#nivel-'+$(this)[0].id.substring(10)).hasClass('nivel')){
                
                var len = $('#nivel-'+$(this)[0].id.substring(10))[0].childNodes.length;
                var nodes = $('#nivel-'+$(this)[0].id.substring(10))[0].childNodes;
                var itemExt = '';
                for(var j = 0; j< len;j++){
                    if($('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].localName == 'li'){
                        //console.log($('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j]); 
                        console.log($('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j]);
                        if(arregloAux[$('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].id]){
                           itemExt  += '<li class="item-list-drop"><a class="btn-drop btn-nav-link dropdown-item dropdown-item-nav drop-item-a"  role="button" >'+
                        $('#'+$('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].id+' .span-item')[0].innerText+
                        '<div class="cont-row-nav"><i class="icon-navs fas fa-sort-down"></i></div></a><div class="dropdown-menu nav-drop-right " aria-labelledby="navbarDropdownrv"><ul class="list-drop">'+
                        arregloAux[$('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].id]+'</ul></div></li>';
                        }else{
                            itemExt  += '<li class="item-list-drop"><a class="dropdown-item dropdown-item-nav" href="'+$('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].dataset.url+'">'+$('#'+$('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].id+' .span-item')[0].innerText+'</a></li>';    
                        }
                        var idAux = $('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].parentNode.parentNode.id;
                        
                        console.log($('#nivel-'+$(this)[0].id.substring(10))[0].childNodes[j].parentNode.parentNode.id);
                    }
                    
                }
                arregloAux[idAux] = itemExt;
            }
            console.log($(this)[0].dataset.url);
            if(arregloAux[$(this)[0].id]){
                finalItems += '<li class="nav-item item-list-drop"><a class="btn-drop btn-nav-link nav-link"   role="button" >'+
                $(this)[0].childNodes[0].innerText+
                '<div class="cont-row-nav"><i class="icon-navs fas fa-sort-down"></i></div></a><div class="dropdown-menu nav-drop" aria-labelledby="navbarDropdownr"><ul class="list-drop">'+
                arregloAux[$(this)[0].id]
                +'</ul></div></li>';
            }else{
                finalItems += '<li class="nav-item"><a class="nav-link" href="'+$(this)[0].dataset.url+'">'+
                $(this)[0].childNodes[0].innerText+
                '</a></li>';
            }
            //var finalItems += 
            listDrop +='<li class="nav-item item-list-drop">'+$('#'+principal[0].id+' .span-item')[0].innerText+'</li>';
            console.log(finalItems);
            console.log(arregloAux);
            var itemPrin = '';
            arregloAux = [];
        });
        
        const regex = /"/gi;
        const regexx = /#/gi;
        finalItems = String(finalItems).replace(regex,"'");
        finalItems = String(finalItems).replace(regexx,"");
        var editable = $('#menu-list')[0].innerHTML.replace(regex,"'");
        console.log(finalItems);
        var sitioMenu = $('#sitio-menu').val();
        var languageMenu = $('select[id=select-language-selection]').val();
        if(sitioMenu && languageMenu != '0' && finalItems){
            console.log('hola');
            $('#sitio-menu').removeClass('input-warning');
            $('#select-language-selection').removeClass('input-warning');
        
            $.post('https://panoramex.mx/dashboard/tools',`data=${JSON.stringify({
                action:'saveMenu',
                sitioMenu,
                languageMenu,
                finalItems,
                editable
            })}`,function(response){
                console.log(response);
                response = JSON.parse(response);
                if(response.error == 'true'){
                        
                            $('.alerts').css('background','red');
                            $('.alerts').css('color','white');
                            $('.alerts').html('Error al guardar');
                            $('.alerts').css('display','block');
                            setTimeout(function(){ 
                                
                               $('.alerts').css('display','none');
                               $('.alerts').html('');
                            }, 3000);
                        
                        
                    }else if(response.error == 'false'){
                        $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html('Guardado Correctamente');
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                            
                           $('.alerts').css('display','none');
                           $('.alerts').html('');
                        }, 3000);
                    }
                
            });
        }else{
                if(!sitioMenu){
                   $('#sitio-menu').addClass('input-warning');
                   $('#menu-list').html('');
                }
                if(languageMenu == '0'){
                    $('#select-language-selection').addClass('input-warning');
                    $('#menu-list').html('');
                }
                if(!finalItems){
                     $('.alerts').css('background','red');
                            $('.alerts').css('color','white');
                            $('.alerts').html('No se encuentra construido ningun menu');
                            $('.alerts').css('display','block');
                            setTimeout(function(){ 
                                
                               $('.alerts').css('display','none');
                               $('.alerts').html('');
                            }, 3000);
                }
        }
    }