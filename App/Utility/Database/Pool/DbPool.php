<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Utility\Database\Pool;

use EasySwoole\EasySwoole\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use App\Utility\Database\DbConnection\Connection;
use App\Utility\Database\Pool\Frequency;
use App\Utility\Database\Pool\Pool;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;

class DbPool extends Pool
{
    protected $name;

    protected $config;

    public function __construct(ContainerInterface $container, string $name)
    {
        $this->name = $name;
//        $config = redis()->get('YXB_DB_CONFIG_'.$name);
        $config = Config::getInstance()->getConf('mysql.'.$name);
        if (empty($config)){
            throw new \Exception("Empty Config Database[{$name}].");
        }
        $config = $this->parseDbConfig($config);
        $config  = $config ?: [];
        $config['name'] = $name;
        $this->config = $config;
        $options = Arr::get($this->config, 'pool', []);
        $this->frequency = make(Frequency::class, [$this]);
        parent::__construct($container, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function createConnection(): ConnectionInterface
    {
        return new Connection($this->container, $this, $this->config);
    }

    private function parseDbConfig(array $dbConfig)
    {
        $config = [
            'driver'    => $dbConfig['driver'] ?? 'mysql',
            'host'      => $dbConfig['host'] ?? '127.0.0.1',
            'port'      => $dbConfig['port'] ?? 3306,
            'database'  => $dbConfig['database'] ?? '',
            'username'  => $dbConfig['user'] ?? '',
            'password'  => $dbConfig['password'] ?? '',
            'charset'   => $dbConfig['charset'] ?? 'utf8mb4',
            'collation' => $dbConfig['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => $dbConfig['prefix'] ?? '',
            'pool' => [
                'min_connections' => (int)($dbConfig['pool']['minnum'] ?? 3),
                'max_connections' => (int)($dbConfig['pool']['maxnum'] ?? 10),
                'connect_timeout' => (float)($dbConfig['pool']['connect_timeout'] ?? 60),
                'wait_timeout' => (float)($dbConfig['pool']['wait_timeout'] ?? 10),
                'heartbeat' => -1,
                'max_idle_time' => (float)($dbConfig['pool']['idletime'] ?? 60),
            ],
        ];
        if (!empty($dbConfig['read'])){
            $config['read'] = $dbConfig['read'];
            unset($config['host'],$config['port'],$config['user'],$config['password']);
        }
        if (!empty($dbConfig['write'])){
            $config['write'] = $dbConfig['write'];
        }
        return $config;
    }
}
