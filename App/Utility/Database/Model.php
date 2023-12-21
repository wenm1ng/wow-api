<?php
declare(strict_types=1);

namespace App\Utility\Database;

use App\Utility\Database\ConnectionResolver;
use App\Utility\Database\Container;
use Hyperf\Utils\Context;
use Hyperf\Database\ConnectionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

class Model extends \Hyperf\Database\Model\Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $connection = 'service';

    /**
     * @var string the full namespace of repository class
     */
    protected $repository;

    /**
     * Get the database connection for the model.
     * @throws Throwable
     */
    public function getConnection(): ConnectionInterface
    {
        $connectionName = $this->getConnectionName();
        return ConnectionResolver::getInstance()->connection($connectionName);
    }

    /**
     * @throws RuntimeException when the model does not define the repository class
     */
    public function getRepository()
    {
        if (! $this->repository || ! class_exists($this->repository) && ! interface_exists($this->repository)) {
            throw new RuntimeException(sprintf('Cannot detect the repository of %s', static::class));
        }
        return $this->getContainer()->get($this->repository);
    }

    protected function getContainer(): ContainerInterface
    {
        return Container::getInstance();
    }

    public function beginTransactionDB()
    {
        Db::connection($this->connection)->beginTransaction();
    }

    public function commitDB()
    {
        Db::connection($this->connection)->commit();
    }

    public function rollbackDB()
    {
        Db::connection($this->connection)->rollBack();
    }

    private function getAgentDbContextKey($name): string
    {
        return sprintf('mysql.agent.connection.%s', $name);
    }
}