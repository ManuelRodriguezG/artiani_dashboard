<?php 
    include 'Session.php';
    include 'Imagenes.php';
    class Vehiculos{
        function __construct(){
            $this->db = new CRUD;
            $this->session = new Session;
            //var_dump("hola");
        }
        
        function main(){
            //var_dump("conectado");
            $data = isset($_POST["data"]) ? json_decode($_POST["data"]) : (isset($_GET["data"]) ? json_decode($_GET["data"]) :  null);
            
            $resp = $this->session->sessionActive();
           
            if($resp['sessionActive'] == "true"){
                if(isset($data) && $data->action == 'codeVehiculos'){
                    echo json_encode($this->codigoSeccionVehiculos());
                }elseif(isset($data) && $data->action == 'nuevaMarca'){
                     //var_dump($resp);
                    echo json_encode($this->nuevaMarca($data));
                }elseif(isset($data) && $data->action == 'actualizarMarca'){
                     //var_dump($resp);
                    echo json_encode($this->actualizarMarca($data));
                }elseif(isset($data) && $data->action == 'update-modelo'){
                     //var_dump($resp);
                    echo json_encode($this->actualizarModelo($data));
                }elseif(isset($data) && $data->action == 'insert-modelo'){
                     //var_dump($resp);
                    echo json_encode($this->insertarModelo($data));
                }
            }else{
                echo json_encode($resp);
            }
        }
        
        function insertarModelo($data){
            //echo $this->db->estructuraFunciones();
            $idMarca = $data->idMarca;
            $modelo = addslashes(strtoupper($data->modelo));
            $resp = $this->db->insert("vehiculos_modelo",array("modeloVehiculo","idMarcaVehiculo"),array($modelo,$idMarca));
            if($resp["estado"] == "success"){
                $idModelo = $resp["respuesta"];
                $respuesta = array("error"=>"false","msg"=>"Modelo registrado correctamente","modelo"=>$modelo,"idModelo"=>$idModelo,"idMarca"=>$idMarca);    
            }else{
                $respuesta = array("error"=>"true","msg"=>"Ocurrió un error al registrar el modelo");
            }
            
            return $respuesta;
        }
        
        function actualizarModelo($data){
            
            
            //var_dump($data);
            $id = $data->idModelo;
            $modeloVehiculo = addslashes(strtoupper($data->modelo));
            $resp = $this->db->update("vehiculos_modelo",array("modeloVehiculo","id"),array($modeloVehiculo,$id));
            //var_dump($resp);
            if($resp["estado"] == "Success"){
                $respuesta = array("error"=>"false","msg"=>"Modelo actualizado correctamente","modelo"=>$modeloVehiculo,"action"=>"update","idModelo"=>$id);
            }else{
                $respuesta = array("error"=>"true","msg"=>"Ocurrió un error al actualizar el modelo");
            }
            return $respuesta;
        }

        
        function actualizarMarca($data){
            //var_dump($data);
            //echo $this->db->estructuraFunciones();
            $marca = strtoupper($data->infoVehiculo->marca);
            $idMarca = $data->infoVehiculo->idMarca;
            //var_dump($idMarca);
            $resp = $this->db->update("vehiculos_marca",array("marcaVehiculo","id"),array($marca,$idMarca));
            //var_dump($resp);
            
            if($resp["estado"] == "Success"){
                if(isset($data->infoImage)){
                    $this->imagen = new Imagenes;
                    if($data->infoVehiculo->idImagen != "0"){
                        //actualizacion de informacion de la imagen
                        
                        $respImage = $this->imagen->actualizarImagen($data->infoImage);
                        //$respuesta = array("error"=>"false","msg"=>"Registro actualizado con éxito");  Pendiente
                        if($respImage["estado"] == "Success"){
                            $respuesta = array("error"=>"false","msg"=>"Registro actualizado con éxito");
                        }else{
                            $respuesta = array("error"=>"false","msg"=>"Ocurrió un error al registrar la imagen");
                        }
                    }else{
                        //insert de la imagen
                        $respImage = $this->imagen->guardarImagen($data->infoImage);
                        
                        //var_dump($respImage);
                        if($respImage["estado"] == "success"){
                            //actualizar id en vehiculos_marca
                            $idImagen = $respImage["respuesta"];
                            $respMarca = $this->db->update("vehiculos_marca",array("idImagen","id"),array($idImagen,$idMarca));
                            if($respMarca["estado"] == "Success"){
                                $respuesta = array("error"=>"false","msg"=>"Registro actualizado con éxito");
                            }else{
                                $respuesta = array("error"=>"false","msg"=>"Ocurrió un error al registrar la imagen en la Marca");
                            }
                        }
                    }
                    
                }else{
                    $respuesta = array("error"=>"false","msg"=>"Registro actualizado con éxito");
                }
                
            }else{
                $respuesta = array("error"=>"true","msg"=>"Ocurrió un error al registrar la Marca");
            }
            return $respuesta;
        }
        
        function codigoSeccionVehiculos(){
            //public function listar($campos, $tabla, $where = NULL, $orderBy = NULL, $AscDesc = NULL, $limit = NULL)
            //echo $this->db->estructuraFunciones();
            $resp = $this->db->listar("marcaVehiculo,vm.id,url, idImagen","vehiculos_marca as vm","LEFT JOIN imagenes as img ON img.id = vm.idImagen");
            //var_dump($resp);
            if($resp["estado"] == "success"){
                foreach($resp['respuesta'] as $indice => $val){
                    $marcas[$val['id']] = $val['marcaVehiculo'];
                    $idsImagen[$val["id"]] = $val["idImagen"];
                    if(isset($val["url"])){
                        $imagenesMarcas[$val['id']] = $val['url'];    
                    }else{
                        $imagenesMarcas[$val['id']] = "null";    
                    }
                    
                }
                //listar modelos
                $respM = $this->db->listar("id,modeloVehiculo,idMarcaVehiculo","vehiculos_modelo");
                if($respM["estado"] == "success"){
                    foreach($respM['respuesta'] as $indiceM => $valM){
                        $modelos[] = array("id"=>$valM['id'],"modelo"=>$valM['modeloVehiculo'],'idMarca'=>$valM['idMarcaVehiculo']);
                    }
                }else{
                    $modelos = array("error"=>"true");
                }
                //var_dump($modelos);
                $code ='<div class="container">
            <div id="marcas-vehiculos" style="background:white;padding:10px;">
                <!-- Select marcas -->
                <div class="marca row" style="margin:0;" >
                    <label style="margin: 0;width:100%;">Marca</label>
                    <div id="containerSelectMarcas" style="width:100%;">
                    </div>
                    
                    
                    
                </div>
                <!-- Edit zone marcas -->
                <div id="editable-marca">
                    <div class="row" style="margin:0;padding: 10px;"> 
                        <div class="col-md-8" style="padding:10px;">
                        
                            <input class="form-control" style="margin: 0;" tabindex="-1" id="texto-marca-editable">   
                        </div>
                        <div class="col-md-2">
                            <div style="height:100%;">
                                <label class="input-preview input-preview1" for="update-image-file">
                                    <p class="insert-image-marcas" style="color: blue;">Insertar Imagen</p>
                                    <i class="far fa-edit edit-icon"></i>
                                  <input class="input-preview__src input-preview__src1 img-file"  name="update-image-file" id="update-image-file" type="file"/>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-2" style="padding:10px;text-align:right;">
                            <button type="button" class="btn btn-primary btn-sm" onclick="actualizarMarcaVehiculo();" tabindex="-1"><i class="fas fa-sync"></i></button>
                        </div>
                    </div>
                    <!--<div class="row" style="margin:0;padding: 10px;justify-content:center;"> 
                        <button type="button" class="btn btn-primary btn-sm" tabindex="-1">Actualizar Marca</button>
                    </div>-->
                </div>
                <!-- Select modelos -->
                <div class="marca row" style="margin:0;" id="select-modelo-vehiculo">
                    <label style="margin: 0;width:100%;">Modelo</label>
                    <div id="containerSelectModelos" style="width:100%;">
                    </div>
                    
                    
                    
                </div>
                <!-- Edit zone modelos -->
                <div id="editable-modelo">
                    <div class="row" style="margin:0;padding: 10px;"> 
                        <div class="col-md-10" style="padding:10px;">
                        
                            <input class="form-control" style="margin: 0;" tabindex="-1" id="texto-modelo-editable">   
                        </div>
                        <div class="col-md-2" style="padding:10px;text-align:right;">
                            <button type="button" onclick="controlAccionesDeModelos(this);" class="btn btn-primary btn-sm" tabindex="-1" id="button-modelo"><i class="fas fa-sync"></i></button>
                        </div>
                    </div>
                    
                </div>
                <div id="agregar-modelo-marca" style="text-align:center;">
                    <div>
                        <label>Insertar Imagen de la marca y el modelo</label>
                    </div>
                    <div>
                        <div style="width:200px;display:inline-block;padding:10px;">
                            <label class="input-preview input-preview2" for="insert-image-file">
                                <p class="insert-image-marcas" style="color: blue;">Insertar Imagen</p>
                                <i class="far fa-edit edit-icon"></i>
                                <input class="input-preview__src input-preview__src2 img-file" name="insert-image-file" id="insert-image-file" type="file"/>
                            </label>
                        </div>
                        <div style="width:200px;display:inline-block;padding:10px;">
                            <div class="input-group mb-3">
                              <input type="text" class="form-control" placeholder="modelo" aria-label="Recipients username" aria-describedby="button-addon2" id="agregar-modelo">
                              <div class="input-group-append">
                                <button class="btn btn-outline-primary upload-image" type="button" id="button-addon2"><i class="fas fa-plus"></i></button>
                              </div>
                            </div>
                        </div>
                    </div>
                
                    
                    
                    
                </div>
            
        </div>
    </div>';
                $respuesta = array('error'=>'false','data'=>$marcas,'code'=>$code,"modelos"=>$modelos,"imagenesMarcas"=>$imagenesMarcas,"idsImagen"=>$idsImagen);
            }else{
                $respuesta = array('error'=>'true','msg'=>'ocurrio un error al listar las marcas de los vehiculos');
            }
            return $respuesta;
            
        }
        
        function nuevaMarca($data){
            //guardar imagen en base de datos
            $this->imagen = new Imagenes;
            $resp = $this->imagen->guardarImagen($data->infoVehiculo->infoImage);
            $idImagen = $resp["respuesta"];
            if(isset($resp) && $resp["estado"] == "success"){
                //registrar marca
                //var_dump($this->db->estructuraFunciones());
                $marca = strtoupper($data->infoVehiculo->marca);
                $respMarca = $this->db->insert("vehiculos_marca",array("marcaVehiculo"),array(addslashes($marca)));
                 $idMarca = $respMarca["respuesta"];
                if(isset($respMarca) && $respMarca["estado"] == "success"){
                    //registrar modelo
                    $modelo = strtoupper($data->infoVehiculo->modelo);
                    $respModelo = $this->db->insert("vehiculos_modelo",array("modeloVehiculo","idMarcaVehiculo","idImagen"),array(addslashes($modelo),$idMarca,$idImagen));
                    //var_dump($respModelo);
                    $idModelo = $respModelo["respuesta"];
                    if(isset($respModelo) && $respModelo["estado"] == "success"){
                        $respuesta = array("error"=>"false","msg"=>"Registro guardado con éxito","data"=>array("marca"=>$marca,"modelo"=>$modelo,"idMarca"=>$idMarca,"idModelo"=>$idModelo));
                    }else{
                        $respuesta = array("error"=>"true","msg"=>"Ocurrió un error al registrar el Modelo");
                    }
                }else{
                    $respuesta = array("error"=>"true","msg"=>"Ocurrió un error al registrar la Marca");
                }
            }else{
                $respuesta = array("error"=>"true","msg"=>"Ocurrió un error al registrar la imagen");
            }
            
            return $respuesta;
        }
        
    }
?>