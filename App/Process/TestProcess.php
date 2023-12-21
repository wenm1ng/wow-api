<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-06 10:23
 */
namespace App\Process;

namespace App\Process;

use EasySwoole\Component\Process\AbstractProcess;
use Swoole\Process;

class TestProcess extends AbstractProcess
{
    private $isRun = false;
    public function run($arg)
    {
        // TODO: Implement run() method.
        /*
         * 举例，消费redis中的队列数据
         * 定时500ms检测有没有任务，有的话就while死循环执行
         */
        $this->addTick(500,function (){
            if(!$this->isRun){
                $this->isRun = true;
                $redis = new \redis();//此处为伪代码，请自己建立连接或者维护redis连接
                while (true){
                    try{
                        $task = $redis->lPop('task_list');
                        if($task){
                            // do you task
                        }else{
                            break;
                        }
                    }catch (\Throwable $throwable){
                        break;
                    }
                }
                $this->isRun = false;
            }
            var_dump($this->getProcessName().' task run check');
        });
    }

    public function onShutDown()
    {
        // TODO: Implement onShutDown() method.
    }

    public function onReceive(string $str, ...$args)
    {
        // TODO: Implement onReceive() method.
    }
}