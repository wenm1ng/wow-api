<?php

namespace App\Utility\Mq;


/**
 * Class KafkaConsumeClient
 * @package App\Utility\Mq
 */
class KafkaConsumeClient
{
    private static $instance;

    /**
     * @param mixed ...$args
     * @return \App\Utility\Mq\KafkaConsumeClient
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
    private $consumer;

    function __construct($args)
    {
        $this->config = $args;

        if(isset($args['vpc']) and $args['vpc'] == true){
            $conf = new \RdKafka\Conf();
            $conf->set('api.version.request', 'true');
            $conf->set('group.id', $args['group']);
            $conf->set('metadata.broker.list', $args['host']);
            $this->consumer = new \RdKafka\KafkaConsumer($conf);
            $this->consumer->subscribe([$args['topic']]);
        } else {
            $conf = new \RdKafka\Conf();
            $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
                switch ($err) {
                    case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                        $kafka->assign($partitions);
                        break;

                    case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                        $kafka->assign(NULL);
                        break;
                    default:
                        throw new \Exception($err);
                }
            });
            $conf->set('sasl.mechanisms', 'PLAIN');
            $conf->set('api.version.request', 'true');
            $conf->set('sasl.username', $args['user']);
            $conf->set('sasl.password', $args['pass']);
            $conf->set('security.protocol', 'SASL_SSL');
            $conf->set('ssl.ca.location', $args['ssl_ca_location']);

            $conf->set('group.id', $args['group']);
            $conf->set('metadata.broker.list', $args['host']);
            $conf->set('auto.offset.reset', 'smallest');

            $this->consumer = new \RdKafka\KafkaConsumer($conf);
            $this->consumer->subscribe([$args['topic']]);
        }

    }


    public function get()
    {
        $message = $this->consumer->consume(120*1000);
        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                // echo 'no error' . PHP_EOL;
                break;
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                //echo "No more messages; will wait for more\n";
                \co::sleep(10);
                break;
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                // echo "Timed out\n";
                break;
            default:
                throw new \Exception($message->errstr(), $message->err);
                break;
        }
        return $message;
    }
}
