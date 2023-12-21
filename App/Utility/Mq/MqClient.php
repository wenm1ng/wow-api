<?php

namespace App\Utility\Mq;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class MqClient
 * @package App\Utility\Mq
 */
class MqClient
{
    private static $instance;

    /**
     * @param mixed ...$args
     * @return \App\Utility\Mq\MqClient
     * @throws \Exception
     */
    static function getInstance(...$args)
    {
        $config = $args[0];
        $key = md5($config['host'].$config['port'].$config['user'].$config['pass'].$config['vhost'].$config['queue']['exchange'].$config['queue']['queue_name']);
        if(!isset(self::$instance[$key])){
            self::$instance[$key] = new static(...$args);
        }
        return self::$instance[$key];
    }

    const TRY_TIMES = 10;

    private $connection;
    private $config;
    private $channel;
    private $exchange;
    private $queueName;

    function __construct($args)
    {
        $this->config = $args;

        $this->exchange = $this->config['queue']['exchange'];
        $this->queueName = $this->config['queue']['queue_name'];
        $this->connect($args);
    }

    private function connect($args)
    {
        $tryTimes = 0;
        try {
            MQ_CLIENT_CONNECT:
            $connectTimeout = isset($this->config['queue']['connect_timeout']) ? $this->config['queue']['connect_timeout'] : 3;
            $readWriteTimeout = isset($this->config['queue']['read_write_timeout']) ? $this->config['queue']['read_write_timeout'] : 3;

            $this->connection = new AMQPStreamConnection($args['host'],$args['port'], $args['user'], $args['pass'], $args['vhost'],
                false,'AMQPLAIN',null,'en_US', $connectTimeout,  $readWriteTimeout,null,false,0,3.0);
            $this->channel = $this->connection->channel();
        } catch (\Exception $e){
            $tryTimes++;
            if($tryTimes < self::TRY_TIMES){
                goto MQ_CLIENT_CONNECT;
            }

            //todo
            throw $e;
        }

    }

    public function basicGet($forceAck = false)
    {
        $tryTimes = 0;
        try{
            MQ_CLIENT_BASIC_GET:
            $this->channel->queue_declare($this->queueName, false, true, false, false);
            $this->channel->exchange_declare( $this->exchange, AMQPExchangeType::DIRECT, false, true, false);
            $message = $this->channel->basic_get($this->queueName);
            if($forceAck){
                $this->basicAck($message);
            }
            return $message;
        } catch (\Exception $e){
            if($e instanceof \PhpAmqpLib\Exception\AMQPChannelClosedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPConnectionBlockedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPProtocolChannelException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPConnectionClosedException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPTimeoutException
            ){
                $tryTimes++;

                try {
                    $this->channel->close();
                    $this->connection->close();
                }catch(\Exception $err){
                    // todo
                    // 忽略掉因关闭连接的异常，导致无法重连
                }

                if($tryTimes < self::TRY_TIMES){
                    $this->connect($this->config);
                    goto MQ_CLIENT_BASIC_GET;
                }
            }

            throw $e;
        }
    }

    public function basicAck($message)
    {
        try{
            $this->channel->basic_ack($message->delivery_info['delivery_tag']);
        } catch (\Exception $e){
            if($e instanceof \PhpAmqpLib\Exception\AMQPChannelClosedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPConnectionBlockedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPProtocolChannelException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPConnectionClosedException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPTimeoutException
            ){
                try {
                    $this->channel->close();
                    $this->connection->close();
                }catch(\Exception $err){
                    // todo
                    // 忽略掉因关闭连接的异常，导致无法重连
                }

                $this->connect($this->config);
            }

            //todo

            throw $e;
        }
    }

    public function basicNack($message,$multiple = false, $requeue = false)
    {
        try{
            $this->channel->basic_nack($message->delivery_info['delivery_tag'],$multiple,$requeue);
        } catch (\Exception $e){
            if($e instanceof \PhpAmqpLib\Exception\AMQPChannelClosedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPConnectionBlockedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPProtocolChannelException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPConnectionClosedException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPTimeoutException
            ){
                try {
                    $this->channel->close();
                    $this->connection->close();
                }catch(\Exception $err){
                    // todo
                    // 忽略掉因关闭连接的异常，导致无法重连
                }

                $this->connect($this->config);
            }

            //todo

            throw $e;
        }
    }

    public function basicPublish($data)
    {
        $tryTimes = 0;
        try{
            MQ_CLIENT_BASIC_PUBLISH:
            $this->channel->queue_declare($this->queueName, false, true, false, false);
            $this->channel->exchange_declare($this->exchange, AMQPExchangeType::DIRECT, false, true, false);
            $this->channel->queue_bind($this->queueName, $this->exchange);
            $message = new AMQPMessage($data, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
            $this->channel->basic_publish($message, $this->exchange);
        } catch (\Exception $e){
            if($e instanceof \PhpAmqpLib\Exception\AMQPChannelClosedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPConnectionBlockedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPProtocolChannelException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPConnectionClosedException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPTimeoutException
            ){
                $tryTimes++;

                try {
                    $this->channel->close();
                    $this->connection->close();
                }catch(\Exception $err){
                    // todo
                    // 忽略掉因关闭连接的异常，导致无法重连
                }

                if($tryTimes < self::TRY_TIMES){
                    $this->connect($this->config);
                    goto MQ_CLIENT_BASIC_PUBLISH;
                }
            }

            //todo

            throw $e;
        }

    }

    public function batchPublish($data)
    {
        $tryTimes = 0;
        try{
            MQ_CLIENT_BATCH_PUBLISH:
            foreach ($data as $v){
                $message = new AMQPMessage($v, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
                $this->channel->batch_basic_publish($message, $this->exchange);
            }
            $this->channel->publish_batch();
        } catch (\Exception $e){
            if($e instanceof \PhpAmqpLib\Exception\AMQPChannelClosedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPConnectionBlockedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPProtocolChannelException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPConnectionClosedException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPTimeoutException
            ){
                $tryTimes++;

                try {
                    $this->channel->close();
                    $this->connection->close();
                }catch(\Exception $err){
                    // todo
                    // 忽略掉因关闭连接的异常，导致无法重连
                }

                if($tryTimes < self::TRY_TIMES){
                    $this->connect($this->config);
                    goto MQ_CLIENT_BATCH_PUBLISH;
                }
            }

            //todo

            throw $e;
        }

    }

    public function count()
    {
        $tryTimes = 0;
        try {
            MQ_CLIENT_COUNT:
            $info = $this->channel->queue_declare($this->queueName, false, true, false, false);
            $queueCount = isset($info[1]) ? $info[1] : 0;
            return $queueCount;
        } catch (\Exception $e){
            if($e instanceof \PhpAmqpLib\Exception\AMQPChannelClosedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPConnectionBlockedException
                || $e instanceof \PhpAmqpLib\Exception\AMQPProtocolChannelException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPConnectionClosedException
                || $e instanceof  \PhpAmqpLib\Exception\AMQPTimeoutException
            ){
                $tryTimes++;

                try {
                    $this->channel->close();
                    $this->connection->close();
                }catch(\Exception $err){
                    // todo
                    // 忽略掉因关闭连接的异常，导致无法重连
                }

                if($tryTimes < self::TRY_TIMES){
                    $this->connect($this->config);
                    goto MQ_CLIENT_COUNT;
                }
            }

            //todo

            throw $e;
        }
    }
}
