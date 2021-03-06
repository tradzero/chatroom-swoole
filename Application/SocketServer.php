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
        $this->socketServer->on('workerStart', [$this, 'onWorkerStart']);
        $this->socketServer->on('message', [$this, 'message']);
        $this->socketServer->on('close', [$this, 'onClose']);
    }

    public function message($socketServer, $request)
    {
        $client = $request->fd;
        $message = json_decode($request->data);
        switch ($message->type) {
            case 'chat':
                $this->gateway->sendToAll(json_encode($message));
                $success = [
                    'type' => 'notice',
                    'message' => 'successfuly send.'
                ];
                $successMessage = json_encode($success);
                $this->gateway->sendToClient($client, $successMessage);
                break;
            case 'pong':
                $this->gateway->pong($client);
                break;
        }
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

    public function onWorkerStart($server, $workerId)
    {
        $startHeartbeat = config()->get('gateway.heartbeat');
        if ($workerId == 0 && $startHeartbeat) {
            swoole_timer_tick(2000, function () {
                $this->gateway->ping();
            });
        }
    }
}
