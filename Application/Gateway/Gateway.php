<?php

namespace App\Gateway;

use swoole_websocket_server;
use Swoole\Table;

class Gateway
{
    protected $server;

    protected $userList;

    public function __construct(swoole_websocket_server $server)
    {
        $this->server = $server;
        $this->initTable();
    }


    public function sendToClient($client, $message)
    {
        $message = json_encode($message);
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
        $this->userList->set($client, ['client_id' => $client]);
    }

    public function leave($client)
    {
        $this->userList->del($client);
    }

    protected function initTable()
    {
        $userList = new Table(65536);
        $userList->column('client_id', Table::TYPE_STRING, 4);
        $userList->create();
        $this->userList = $userList;
    }
}