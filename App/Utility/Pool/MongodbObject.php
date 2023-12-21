<?php

namespace App\Utility\Pool;

use EasySwoole\Pool\ObjectInterface;
use MongoDB\Client;

class MongodbObject extends Client implements ObjectInterface
{
    public function gc()
    {
        // TODO: Implement gc() method.
        // client 不支持关闭链接
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
