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
namespace App\Utility\Database\DbConnection;

use App\Utility\Logger\Logger;
use Common\Common;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\ConnectionInterface as DbConnectionInterface;
use Hyperf\Database\Connectors\ConnectionFactory;
use App\Utility\Database\Pool\DbPool;
use App\Utility\Database\Pool\PoolConnection;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Utility\Database\DbConnection\DbConnection;


class Connection extends PoolConnection implements ConnectionInterface, DbConnectionInterface
{
    use DbConnection;

    /**
     * @var DbPool
     */
    protected $pool;

    /**
     * @var DbConnectionInterface
     */
    protected $connection;

    /**
     * @var ConnectionFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    protected $transaction = false;

    public function __construct(ContainerInterface $container, DbPool $pool, array $config)
    {
        parent::__construct($container, $pool);
//        $this->factory = $container->get(ConnectionFactory::class);
        $this->factory = (new ConnectionFactory($container));
        $this->config = $config;
        $this->logger = $container->get(StdoutLoggerInterface::class);

        $this->reconnect();
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public function getActiveConnection(): DbConnectionInterface
    {
        if ($this->check()) {
            return $this;
        }

        if (! $this->reconnect()) {
            throw new \Exception('Connection reconnect failed.');
        }

        return $this;
    }

    public function reconnect(): bool
    {
        $this->close();

        $this->connection = $this->factory->make($this->config);

        if ($this->connection instanceof \Hyperf\Database\Connection) {
            // Reset event dispatcher after db reconnect.
            if ($this->container->has(EventDispatcherInterface::class)) {
                $dispatcher = $this->container->get(EventDispatcherInterface::class);
                $this->connection->setEventDispatcher($dispatcher);
            }

            // Reset reconnector after db reconnect.
            $this->connection->setReconnector(function ($connection) {
//                $this->logger->warning('Database connection refreshing.');
                if ($connection instanceof \Hyperf\Database\Connection) {
                    $this->refresh($connection);
                }
            });
        }

        $this->lastUseTime = microtime(true);
        return true;
    }

    public function close(): bool
    {
        if ($this->connection instanceof \Hyperf\Database\Connection) {
            $this->connection->disconnect();
//            $cid = Coroutine::id();
//            Common::log("\n------【cid：{$cid}】关闭数据库连接-------- \n", 'mysql_close', 1);
        }

        unset($this->connection);

        return true;
    }

    public function release(): void
    {
        if ($this->connection instanceof \Hyperf\Database\Connection) {
            // Reset $recordsModified property of connection to false before the connection release into the pool.
            $this->connection->resetRecordsModified();
        }

        if ($this->isTransaction()) {
            $this->rollBack(0);
            Logger::error('Maybe you\'ve forgotten to commit or rollback the MySQL transaction.');
        }

        parent::release();
    }

    public function setTransaction(bool $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function isTransaction(): bool
    {
        return $this->transaction;
    }

    /**
     * Refresh pdo and readPdo for current connection.
     * @param $connection
     * @return
     */
    protected function refresh(\Hyperf\Database\Connection $connection)
    {
        $refresh = $this->factory->make($this->config);
        if ($refresh instanceof \Hyperf\Database\Connection) {
            $connection->disconnect();
            $connection->setPdo($refresh->getPdo());
            $connection->setReadPdo($refresh->getReadPdo());
        }
        return $connection;
//        $this->logger->warning('Database connection refreshed.');
    }
}
