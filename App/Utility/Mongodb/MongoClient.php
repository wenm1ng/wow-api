<?php

namespace App\Utility\Mongodb;

use EasySwoole\Component\Singleton;
use EasySwoole\SyncInvoker\SyncInvoker;

class MongoClient extends SyncInvoker
{
    use Singleton;
}