<?php

namespace App\Utility\Mq;


/**
 * Class KafkaProducerClient
 * @package App\Utility\Mq
 */
class KafkaProducerClient
{
    private static $instance;

    /**
     * @param mixed ...$args
     * @return \App\Utility\Mq\KafkaProducerClient
     * @throws \Exception
     */
    static function getInstance(...$args)
    {
        $config = $args[0];
        $key = md5($config['host'].$config['user'].$config['pass'].$config['topic'].$config['group']);
        if(!isset(self::$instance[$key])){
            self::$instance[$key] = new static(...$args);
        }
        return self::$instance[$key];
    }

    const TRY_TIMES = 3;

    private $config;
    private $producer;
    private $topic;

    function __construct($args)
    {
        $this->config = $args;

        if(isset($args['vpc']) and $args['vpc'] == true){
            $conf = new \RdKafka\Conf();
            $conf->set('api.version.request', 'true');
            $conf->set('message.send.max.retries', 5);

            // we need to maintain this for backwards compatibility with existing applications
            $conf->set('metadata.broker.list', $args['host']);
            
            $this->producer = new \RdKafka\Producer($conf);
            $this->producer->addBrokers($args['host']);
            $this->topic = $this->producer->newTopic($args['topic']);
        } else {
            $conf = new \RdKafka\Conf();
            $conf->set('sasl.mechanisms', 'PLAIN');
            $conf->set('api.version.request', 'true');
            $conf->set('sasl.username', $args['user']);
            $conf->set('sasl.password', $args['pass']);
            $conf->set('security.protocol', 'SASL_SSL');
            $conf->set('ssl.ca.location', $args['ssl_ca_location']);

            $conf->set('metadata.broker.list', $args['host']);

            $this->producer = new \RdKafka\Producer($conf);

            $this->topic = $this->producer->newTopic($args['topic']);
        }

    }

    public function put($message)
    {
        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
        $this->producer->poll(0);

        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }
    }
}
