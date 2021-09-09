<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\Chat;

    require dirname(__DIR__) . '/vendor/autoload.php';

    //On browser
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Chat()
            )
        ),
        8080
    );

    //On console
//    $server = IoServer::factory(
//        new Chat(),
//        8080
//    );

    $server->run();