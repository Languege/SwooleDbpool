<?php

class DbClient
{
    public $client;

    public function __construct()
    {
        $this->client = new swoole_client(SWOOLE_SOCK_TCP);

        if (!$this->client->connect('127.0.0.1', 9508, 4)) {
            exit('event连接失败');
        }
    }

    public function send($data)
    {
        $this->client->send($data);

        $data = $this->client->recv();
        return $data;
    }
}

$client = new DbClient();

$r  = $client->send("SELECT * FROM `univer_admin_user` WHERE user_name='zhaoyuhao'");

var_dump($r);