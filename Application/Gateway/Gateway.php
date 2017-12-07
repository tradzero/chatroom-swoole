<?php

namespace App\Gateway;

use swoole_websocket_server;
use Swoole\Table;

class Gateway
{
    protected $server;

    protected $userList;

    protected $pingNotResponseLimit = 2;

    public function __construct(swoole_websocket_server $server)
    {
        $this->server = $server;
        $this->initTable();
    }


    public function sendToClient($client, $message)
    {
        $this->server->push($client, $message);
    }

    public function sendToAll($message)
    {
        $userList = $this->userList;
        foreach ($userList as $user) {
            $this->server->push($user['client_id'], $message);
        }
    }

    public function join($client)
    {
        $this->userList->set($client, ['client_id' => $client, 'not_response_ping_count' => 0]);
    }

    public function leave($client)
    {
        $this->userList->del($client);
    }

    protected function initTable()
    {
        $userList = new Table(65536);
        $userList->column('client_id', Table::TYPE_STRING, 4);
        $userList->column('not_response_ping_count', Table::TYPE_INT, 1);
        $userList->create();
        $this->userList = $userList;
    }

    public function ping()
    {
        $pingData = ['type' => 'ping', 'message' => 'ping'];
        foreach ($this->userList as $user) {
            $notResponsePingCount = $user['not_response_ping_count'];
            $client = $user['client_id'];
            if ($notResponsePingCount > 0 && $notResponsePingCount >= $this->pingNotResponseLimit * 2) {
                $this->server->close($client);
                $this->leave($client);
                continue;
            }
            $this->userList->incr($client, 'not_response_ping_count');
            if ($notResponsePingCount >= 1) {
                $this->sendToClient($client, json_encode($pingData));
            }
        }
    }

    public function pong($clientId)
    {
        if ($this->userList->exist($clientId)) {
            $newData = [
                'client_id' => $clientId,
                'not_response_ping_count' => -1,
            ];
            $this->userList->set($clientId, $newData);
        }
    }
}
