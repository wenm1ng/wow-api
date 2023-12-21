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

use App\Utility\Database\Container;
use Psr\Container\ContainerInterface;

class PoolFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DbPool[]
     */
    protected $pools = [];

    public function getPool(string $name): DbPool
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }
        $pool = new DbPool(Container::getInstance(), $name);
        return $this->pools[$name] = $pool;
    }
}
