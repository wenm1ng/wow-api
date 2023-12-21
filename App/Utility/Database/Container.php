<?php
declare(strict_types=1);


namespace App\Utility\Database;


use EasySwoole\Component\Di;
use EasySwoole\Component\Singleton;
use Psr\Container\ContainerInterface;
use Throwable;

class Container implements ContainerInterface
{
    use Singleton;

    /**
     * @param string $id
     * @return callable|mixed|string|null
     * @throws Throwable
     */
    public function get(string $id)
    {
        return Di::getInstance()->get($id);
    }

    /**
     * @param string $id
     * @return callable|mixed|string|null
     * @throws Throwable
     */
    public function has(string $id):bool
    {
        return Di::getInstance()->get($id) != null;
    }
}