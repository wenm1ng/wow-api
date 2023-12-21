<?php
declare(strict_types=1);

namespace App\Utility\Database;

use App\Utility\Database\Pool\PoolFactory;
use EasySwoole\Component\Singleton;
use EasySwoole\Pool\Manager;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coroutine;

class ConnectionResolver implements ConnectionResolverInterface
{
    use Singleton;

    /**
     * The default connection name.
     *
     * @var string
     */
    protected $default = 'service';

    public function __construct()
    {

    }

    /**
     * Get a database connection instance.
     * @param null $name
     * @return ConnectionInterface|mixed|null
     * @throws \Throwable
     */
    public function connection($name = null)
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        $connection = null;
        $id = $this->getContextKey($name);
        if (Context::has($id)) {
            $connection = Context::get($id);
//            $connection = $connection->getActiveConnection();
//            $connection = $connection->refresh($connection);
//            Context::set($id, $connection);
        }

        if (! $connection instanceof ConnectionInterface) {
            $pool = (new PoolFactory())->getPool($name);
            $connection = $pool->get();
            try {
                // PDO is initialized as an anonymous function, so there is no IO exception,
                // but if other exceptions are thrown, the connection will not return to the connection pool properly.
                $connection = $connection->getConnection();
                Context::set($id, $connection);
            } finally {
                if (Coroutine::inCoroutine()) {
                    defer(function () use ($connection, $id) {
                        Context::set($id, null);
                        $connection->release();
                    });
                }
            }
        }

        return $connection;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default;
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     */
    public function setDefaultConnection($name)
    {
        $this->default = $name;
    }

    /**
     * The key to identify the connection object in coroutine context.
     * @param mixed $name
     * @return string
     */
    private function getContextKey($name): string
    {
        return sprintf('database.connection.%s', $name);
    }
}
