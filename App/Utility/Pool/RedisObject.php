<?php

namespace App\Utility\Pool;

use EasySwoole\Pool\ObjectInterface;
use Swoole\Coroutine\Redis;

class RedisObject extends Redis implements ObjectInterface
{
    public function gc()
    {
        // TODO: Implement gc() method.
        $this->close();
    }

    public function objectRestore()
    {
        // TODO: Implement objectRestore() method.
    }

    public function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }
}
