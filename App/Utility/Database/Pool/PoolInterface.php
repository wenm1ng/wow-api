<?php
/**
 * Class PoolInterface
 * @Auhtor zp
 * @Time 2021/9/28 18:32
 */

namespace App\Utility\Database\Pool;


use Hyperf\Contract\ConnectionInterface;

interface PoolInterface
{
    /**
     * Get a connection from the connection pool.
     */
    public function get(): ConnectionInterface;

    /**
     * Release a connection back to the connection pool.
     * @param ConnectionInterface $connection
     */
    public function release(ConnectionInterface $connection): void;

    /**
     * Close and clear the connection pool.
     */
    public function flush(): void;
}