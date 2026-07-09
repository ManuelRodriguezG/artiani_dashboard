<?php 
header("Access-Control-Allow-Origin: *");
?>
<!--<!DOCTYPE html>
<html><head><title>WebSocket</title>
<style type="text/css">
html,body {
	font:normal 0.9em arial,helvetica;
}
#log {
	width:600px; 
	height:300px; 
	border:1px solid #7F9DB9; 
	overflow:auto;
}
#msg {
	width:400px;
}
</style>

<script type="text/javascript">

var socket;

function init() {
    document.cookie = "SameSite=None";
    document.cookie = 'same-site-cookie=foo; SameSite=Lax'; 
    document.cookie = 'cross-site-cookie=bar; SameSite=None; Secure';
    //console.log(document.cookie);
	// Apuntar a la IP/Puerto configurado en el contructor del WebServerSocket, que es donde está escuchando el socket.
	var host = "wss://107.180.46.158:1339"; 
	//var host = "wss://panoramex.mx:9000"; // SET THIS TO YOUR SERVER
	try {
		socket = new WebSocket(host);
		
		console.log(socket);
		log('WebSocket - status '+socket.readyState);
		socket.onopen    = function(msg) { 
		    console.log(msg)
							   log("Welcome - status "+this.readyState); 
						   };
		socket.onmessage = function(msg) { 
							   log("Received: "+msg.data); 
						   };
		socket.onclose   = function(msg) { 
							   log("Disconnected - status "+this.readyState); 
						   };
	}
	catch(ex){ 
		log(ex); 
	}
	$("msg").focus();
}

function send(){
	var txt,msg;
	txt = $("msg");
	msg = txt.value;
	if(!msg) { 
		alert("Message can not be empty"); 
		return; 
	}
	txt.value="";
	txt.focus();
	try { 
		socket.send(msg); 
		log('Sent: '+msg); 
	} catch(ex) { 
		log(ex); 
	}
}
function quit(){
	if (socket != null) {
		log("Goodbye!");
		socket.close();
		socket=null;
	}
}

function reconnect() {
	quit();
	init();
}

// Utilities
function $(id){ return document.getElementById(id); }
function log(msg){ $("log").innerHTML+="<br>"+msg; }
function onkey(event){ if(event.keyCode==13){ send(); } }
</script>

</head>
<body onload="init()">
    <script>
//<![CDATA[
document.cookie = 'same-site-cookie=foo; SameSite=Lax'; 
document.cookie = 'cross-site-cookie=bar; SameSite=None; Secure';
//]]>
</script>
<h3>WebSocket v2.00</h3>
<div id="log"></div>
<input id="msg" type="textbox" onkeypress="onkey(event)"/>
<button onclick="send()">Send</button>
<button onclick="quit()">Quit</button>
<button onclick="reconnect()">Reconnect</button>
</body>
</html>-->

<meta charset="utf-8" >
    <title>Prueba WebSocket</title>
    <script language="javascript" type="text/javascript">
      var wsUri = "wss://panoramex.mx:9000";
      var output;

      function init(){
          output = document.getElementById("output");
          testWebSocket();
      }
      function testWebSocket(){

          websocket = new WebSocket(wsUri);

          websocket.onopen = onOpen;

          websocket.onclose = onClose;

          websocket.onmessage = onMessage;

          websocket.onerror = onError;

      }

      function onOpen(evt){
          writeToScreen("CONECTADO");
          doSend("WebSocket funciona");
      }

      function onClose(evt){
          writeToScreen("DESCONECTADO");
      }

      function onMessage(evt){
          writeToScreen('<span style="color: blue;">RESPUESTA: ' + evt.data + '</span>');
          websocket.close();
      }

      function onError(evt){
          writeToScreen('<span style="color: red;">ERROR:</span> ' + evt.data);
      }

      function doSend(message){
          writeToScreen("ENVIADO: " + message);
          websocket.send(message);
      }

      function writeToScreen(message){
          var pre = document.createElement("p");
          pre.style.wordWrap = "break-word";
          pre.innerHTML = message;
          output.appendChild(pre);
      }

      window.addEventListener("load", init, false);

    </script>
  </head>
  <body>
    <h2>Prueba WebSocket</h2>
    <div id="output"></div>
  </body>
</html>