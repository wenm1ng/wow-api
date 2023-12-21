<?php
/**
 * Class Pool
 * @Auhtor zp
 * @Time 2021/9/28 19:35
 */

namespace App\Utility\Database\Pool;

use Common\Common;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Contract\FrequencyInterface;
use Hyperf\Contract\PoolOptionInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerInterface;
use App\Utility\Database\Pool\Channel;
use App\Utility\Database\Pool\PoolOption;
use App\Utility\Database\Pool\LowFrequencyInterface;
use RuntimeException;
use Throwable;


abstract class Pool implements \Hyperf\Contract\PoolInterface
{
    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PoolOptionInterface
     */
    protected $option;

    /**
     * @var int
     */
    protected $currentConnections = 0;

    /**
     * @var LowFrequencyInterface
     */
    protected $frequency;


    public function __construct(ContainerInterface $container, array $config = [])
    {
        $this->container = $container;
        $this->initOption($config);
        $this->channel = make(Channel::class, ['size' => $this->option->getMaxConnections()]);
    }

    /**
     * @return ConnectionInterface
     * @throws Throwable
     */
    public function get(): ConnectionInterface
    {
        $connection = $this->getConnection();

//        try {
//            if ($this->frequency instanceof FrequencyInterface) {
//                $this->frequency->hit();
//            }
//
//            if ($this->frequency instanceof LowFrequencyInterface) {
//                if ($this->frequency->isLowFrequency()) {
//                    $this->flush();
//                }
//            }
//        } catch (\Throwable $exception) {
//            if ($this->container->has(StdoutLoggerInterface::class) && $logger = $this->container->get(StdoutLoggerInterface::class)) {
//                $logger->error((string) $exception);
//            }
//        }
        return $connection;
    }

    /**
     * TODO 修改为使用完就删除：20211013
     * @param ConnectionInterface $connection
     */
    public function release(ConnectionInterface $connection): void
    {
        $connection->close();
//        $this->channel->push($connection);
    }

    public function flush(): void
    {
        $num = $this->getConnectionsInChannel();

        if ($num > 0) {
            while ($this->currentConnections > $this->option->getMinConnections() && $conn = $this->channel->pop(0.001)) {
                try {
                    $conn->close();
                } catch (\Throwable $e) {
                    Common::log($e->getMessage());
                } finally {
                    --$this->currentConnections;
                    --$num;
                }

                if ($num <= 0) {
                    // Ignore connections queued during flushing.
                    break;
                }
            }
        }
    }

    public function flushOne(bool $must = false): void
    {
        $num = $this->getConnectionsInChannel();
        if ($num > 0 && $conn = $this->channel->pop(0.001)) {
            if ($must || ! $conn->check()) {
                try {
                    $conn->close();
                } catch (\Throwable $e) {
                    Common::log($e->getMessage());
                } finally {
                    --$this->currentConnections;
                }
            } else {
                $this->release($conn);
            }
        }
    }

    public function getCurrentConnections(): int
    {
        return $this->currentConnections;
    }

    public function getOption(): PoolOptionInterface
    {
        return $this->option;
    }

    public function getConnectionsInChannel(): int
    {
        return $this->channel->length();
    }

    protected function initOption(array $options = []): void
    {
        $this->option = make(PoolOption::class, [
            'minConnections' => $options['min_connections'] ?? 1,
            'maxConnections' => $options['max_connections'] ?? 10,
            'connectTimeout' => $options['connect_timeout'] ?? 10.0,
            'waitTimeout' => $options['wait_timeout'] ?? 3.0,
            'heartbeat' => $options['heartbeat'] ?? -1,
            'maxIdleTime' => $options['max_idle_time'] ?? 60.0,
        ]);
    }

    abstract protected function createConnection(): ConnectionInterface;

    /**
     * @return ConnectionInterface
     * @throws Throwable
     */
    private function getConnection(): ConnectionInterface
    {
        $num = $this->getConnectionsInChannel();
        try {
            if ($num === 0 && $this->currentConnections < $this->option->getMaxConnections()) {
                ++$this->currentConnections;
                return $this->createConnection();
            }
        } catch (Throwable $throwable) {
            --$this->currentConnections;
            throw $throwable;
        }

        $connection = $this->channel->pop($this->option->getWaitTimeout());
        if (! $connection instanceof ConnectionInterface) {
            throw new RuntimeException('Connection pool exhausted. Cannot establish new connection before wait_timeout.');
        }
        return $connection;
    }
}