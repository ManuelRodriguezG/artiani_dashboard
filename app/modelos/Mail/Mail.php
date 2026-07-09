<?php 
    class Mail{
        
        
        public $to;
        public $from = 'info@panoramex.mx';
        public $fromAlias = 'Panoramex Tour ＆ Travel';
        public $subject;
        public $html = true;
        public $bodyEmail;
        public $altEmail = 'Sino puede visualizar este correo comunicarse con nosotros MX:3315780421 USA:3232838223';
        public $cc = array();
        public $bcc = array();
        public $stringAttachment = 'null';
        public $fileName = 'null';
        
        function __construct(){
            
        }
        public function prueba(){
            //var_dump('conectado a correo');
        }
        
        public function enviaCorreo(){
		    $dataUser = array(
                'action' =>'sendMail',
                'to' => $this->to,
                'from' => $this->from,
                'fromAlias'=>$this->fromAlias,
                'subject' => $this->subject,
                'html' => $this->html,
                'bodyEmail'=>$this->bodyEmail,
                'altEmail'=> $this->altEmail,
                'arrayFiles'=>$this->arrayFiles,
                'fileName'=>$this->fileName,
                'cc' => $this->cc,
                'bcc' =>$this->bcc,
            );
		    
            //var_dump($dataUser);
            $post = json_encode($dataUser);
            //var_dump($post);
            $ch = curl_init('https://www.gdltours.com/Email/Email.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='.$post);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            // execute!
            $response = curl_exec($ch);
            //
            //// close the connection, release resources used
            curl_close($ch);
            //
            //// do anything you want with your response
            //var_dump($response);
            $response = json_decode($response);
            return $response;
		}
		
		public function setTo($to){
		    $this->to = $to;
		}
        public function setFrom($from){
            $this->from = $from;
        }
        public function setFromAlias($fromAlias){
            $this->fromAlias = $fromAlias;
        }
        public function setSubject($subject){
            $this->subject = $subject;
        }
        public function setHtml($html){
            $this->html = $html;
        }
        public function setBodyEmail($bodyEmail){
            $this->bodyEmail = $bodyEmail;
        }
        public function setAltEmail($altEmail){
            $this->altEmail = $altEmail;
        }
        public function setCc($cc){
            $this->cc = $cc;
        }
        public function setBcc($bcc){
            $this->bcc = $bcc;
        }
		
		//public function getTo(){
		//    return $this->to;
		//}
        //public function getFrom(){
		//    return $this->from;
        //}
        //public function getFromAlias(){
        //    return $this->fromAlias;
        //}
        //public function getSubject(){
        //    return $this->subject;
        //}
        //public function getHtml(){
        //    return $this->html;
        //}
        //public function getBodyEmail(){
        //    return $this->bodyEmail;
        //}
        //public function getAltEmail(){
        //    return $this->altEmail;
        //}
        //public function getCc(){
        //    return $this->cc;
        //}
        //public function getBcc(){
        //    return $this->bcc;
        //}
        
    }
?>