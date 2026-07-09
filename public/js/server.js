var WebSocketServer = require('ws').Server
wss = new WebSocketServer({ port: 9000 });
wss.on('connection', function connection(ws) {
    ws.on('message', function incoming(message) {
    console.log('received: %s', message);
    ws.send(message);
    });
    ws.send('Conectado');
});