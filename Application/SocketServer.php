<?php

namespace App;

use swoole_websocket_server;
use App\Gateway\Gateway;

class SocketServer 
{
    private $gateway;

    private $socketServer;

    public function __construct()
    {
        $this->socketServer = new swoole_websocket_server(getenv('HOST'), getenv('PORT'));
        $this->socketServer->set([
            'worker_num' => 4,
            'daemonize'  => false,
        ]);
        $this->gateway = new Gateway($this->socketServer);
        
        $this->socketServer->on('open', [$this, 'onOpen']);
        $this->socketServer->on('message', [$this, 'message']);
        $this->socketServer->on('close', [$this, 'onClose']);
    }

    public function message($socketServer, $message)
    {
        
    }

    public function onOpen($socketServer, $request)
    {
        $clientId = $request->fd;
        $this->gateway->join($clientId);
        $welcome = [
            'type' => 'welcome',
            'message' => "welcome, {$clientId} join."
        ];
        $this->gateway->sendToAll(json_encode($welcome));
    }

    public function onClose($socketServer, $clientId)
    {
        $this->gateway->leave($clientId);
        $leave = [
            'type' => 'leave',
            'message' => "{$clientId} has leave."
        ];
        $this->gateway->sendToAll(json_encode($leave));
    }

    public function start()
    {
        $this->socketServer->start();
    }
}