<?php 


    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    // Load Composer's autoloader
    include '../app/librerias/PHPMailer/vendor/autoload.php';
    
    
    //main
    $data = isset($_POST["data"]) ? json_decode($_POST["data"]) : (isset($_GET["data"]) ? json_decode($_GET["data"]) :  null);
             //var_dump($data);
    if($data->action == 'sendMail'){
        //var_dump($data);
        
        $email = new Email;
        $email->to = isset($data->to) ? $data->to : 'null' ;
        $email->from = isset($data->from) ? $data->from : 'null' ;
        if(isset($data->fromAlias)){
            $email->fromAlias = $data->fromAlias;
        }
            
        
        $email->cc = isset($data->cc) ? $data->cc : 'null';
        $email->bcc = isset($data->bcc) ? $data->bcc : 'null';
        $email->html = isset($data->html) ? $data->html : 'null';
        $email->bodyEmail = isset($data->bodyEmail) ? $data->bodyEmail : 'null';
        //var_dump($email->bodyEmail);
        $email->altEmail = isset($data->altEmail) ? $data->altEmail : 'null';
        $email->subject = isset($data->subject) ? $data->subject : 'null';
        $email->arrayFiles = isset($data->arrayFiles) ? $data->arrayFiles : 'null';
        $email->fileName = isset($data->fileName) ? $data->fileName : 'null';
        if($email->to == 'null' || $email->from == 'null'){
            echo json_encode(array('msg'=>'Error to send (to) or (from)','error'=>'true'));
        }else{
            echo json_encode($email->send());     
        }
	   
    }
    
    class Email{
        public $to = 'null';
        public $toAlias = 'null';
        public $fromAlias = 'holas';
        public $from = 'null';
        public $subject = '';
        public $html = false;
        public $bodyEmail = 'null';
        public $altEmail = '';
        public $cc = array();
        public $bcc = array();
        public $arrayFiles = 'null';
        public $fileName = 'null';
        
        function insertCC($mail){
            
        }
        
        function insertBCC($mail){
            
        }
        
        function __construct(){
            
        }
        
        function send(){
            try {
                //Create a new PHPMailer instance
                // Instantiation and passing `true` enables exceptions
                
                $mail = new PHPMailer(true);
                //Server settings
                //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
                //$mail->isSMTP();             // Send using SMTP
                //$mail->SMTPOptions = array(
                //    'ssl' => array(
                //    'verify_peer' => false,
                //    'verify_peer_name' => false,
                //    'allow_self_signed' => true
                //    )
                //    );
                $mail->Host = 'melorautopartes.com';                  // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                $mail->Username   = 'info@melorautopartes.com';                     // SMTP username
                $mail->Password   = 'H$Om1F_9IXb0';                               // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  
                //var_dump(gethostbyname('panoramex.mx').' host');
                $mail->Port       = 465;                                    // TCP port to connect to
                //Recipients
                //var_dump($this->fromAlias);
                if($this->fromAlias != 'null'){
                    $mail->setFrom($this->from, $this->fromAlias);
                }else{
                    $mail->setFrom($this->from);
                }
                //var_dump($this->toAlias);
                if($this->toAlias != 'null'){
                    $mail->addAddress($this->to, $this->toAlias);     // Add a recipient    
                }else{
                    $mail->addAddress($this->to);     // Add a recipient
                }
                
                //$mail->addAddress('ellen@example.com');               // Name is optional
                //$mail->addReplyTo('alejandro_ro_drig@hotmail.com', 'Information');
                if(count($this->cc) > 0){
                    foreach($this->cc as $correo){
                        $mail->addCC($correo);    
                    }
                }
                if(count($this->bcc) > 0){
                    foreach($this->bcc as $correo){
                        $mail->addBCC($correo);    
                    }
                }
                
                //$mail->addCC('manuurguez1996@gmail.com');
                //$mail->addBCC('bcc@example.com');
            
                // Attachments
                //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
                //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            
                // Content
                if($this->html == true){
                    $mail->isHTML(true);                                  // Set email format to HTML    
                }
                
                $mail->Subject = $this->subject;
                $codeBody = ($this->bodyEmail);
                $mail->Body = str_replace("Ampersand","&",$codeBody);
                if($this->arrayFiles != 'null'){
                    foreach($this->arrayFiles as $nameFile){
                        $mail->addAttachment($nameFile);
                    }
                }else{
                    if($this->fileName != 'null'){
                        $mail->addAttachment($this->fileName);
                    }    
                }
                
                $mail->AltBody = $this->altEmail;
                $mail->CharSet = 'UTF-8';
                $mail->send();
                if($this->arrayFiles != 'null'){
                    foreach($this->arrayFiles as $nameFile){
                        
                        unlink($nameFile);
                    }
                }
                
                
                return array('msg'=>'sent','error'=>'false');
            } catch (Exception $e) {
                return array("msg"=>"Message could not be sent. Mailer Error: {$mail->ErrorInfo}",'error'=>'true');
            }
        }
        
        function getTemplate(){
            $dir = '../app/helpers/emailTemplate/template.html';
            $template = file_get_contents($dir,true);
            return $template;
        }
        
        function mergeContentTemplate($str, $args){
             if (is_object($args)) {
                    $args = get_object_vars($args);
                }
                //var_dump($args);
                $map = array_flip(array_keys($args));
                //var_dump(key($map));
                $this->id = key($map);
                
                $new_str = preg_replace_callback('/(^|[^%])%([a-zA-Z0-9_-]+)\$s/',
                        function($m) use ($map) { 
                            
                            
                            
                            if(in_array($this->id,$m)){
                                return $m[1].'%'.($map[$m[2]] + 1).'$s';     
                            }else{
                                return '';
                            }
                            //var_dump($m[1].'%'.($map[$m[2]] + 1).'$');
                            
                            
                        },
                        $str);
                        //var_dump($new_str);
                return vsprintf($new_str, $args);
        }
        
        function vksprintf($str, $args){
                //var_dump(is_object($args));
                if (is_object($args)) {
                    $args = get_object_vars($args);
                }
                //var_dump($args);
                $map = array_flip(array_keys($args));
                //var_dump(key($map));
                $this->id = key($map);
                
                $new_str = preg_replace_callback('/(^|[^%])%([a-zA-Z0-9_-]+)\$s/',
                        function($m) use ($map) { 
                            
                            
                            
                            if(in_array($this->id,$m)){
                                return $m[1].'%'.($map[$m[2]] + 1).'$s';     
                            }else{
                                return '';
                            }
                            //var_dump($m[1].'%'.($map[$m[2]] + 1).'$');
                            
                            
                        },
                        $str);
                        //var_dump($new_str);
                return vsprintf($new_str, $args);
            }
        
    }
    
    
    
    // $subject = "<h1>Link de cambio de contraseña</h1>
    //	   
    //	   <p>Si usted solicito el cambio de contraseña
    //	   <a href='https://www.gdltours.com/adminVentas/cambioContrasena.html?AU=".$salt."'>Click aquí</a></p>
    //	   <p><strong>Nota:</strong></p>
    //	   <p><p>Todos los correos enviados con anterioridad para reestablecer contraseña quedan invalidados por este último</p></p>";
    //$fromName="Cambio de contraseña";
    //$to="alejandro_ro_drig@hotmail.com";
    //$from = "rigo@panoramex.mx";
    //$headers = "From: PANORAMEX TOURS & TRAVEL"." <".$from.">\n";
    //		$headers .= "MIME-Version: 1.0" . "\r\n";
    //		$headers .= "Content-type:text/html;charset=UTF-8" . "\n";
    //		$messageCopia=$subject;
    //		$response = mail($to, $fromName, $subject, $headers);




?>