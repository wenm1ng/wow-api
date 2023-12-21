<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-23 15:28
 */
Class websocket{
    protected $redis;
    public function __construct()
    {
        // 注册 Redis 连接池
//        $this->redis = new \Redis();
    }

    public function start(){
        $websocketServer = new \Swoole\WebSocket\Server("0.0.0.0", 9501);
//客户端握手成功事件
        $websocketServer->on('open', function (\Swoole\WebSocket\Server $websocketServer, $frame) {
            echo "{$frame->fd} 已经握手成功.\n";
            var_dump($frame);
//    redis()->zadd('chat_room', $frame->fd, );
        });
//客户端发送消息事件
        $websocketServer->on('message', function (\Swoole\WebSocket\Server $websocketServer, $frame) {
            echo "{$frame->fd} 发送了数据:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $websocketServer->push($frame->fd, "this is server");
        });
//客户端关闭事件
        $websocketServer->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });
//开启websocket服务
        $websocketServer->start();
    }

}

(new websocket())->start();