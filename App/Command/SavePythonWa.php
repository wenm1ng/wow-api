<?php
/**
 * @desc
 * @author     文明<736038880@qq.com>
 * @date       2023-08-18 12:06
 */
namespace App\Command;

use EasySwoole\Command\AbstractInterface\CommandHelpInterface;
use EasySwoole\Command\AbstractInterface\CommandInterface;
use EasySwoole\Command\CommandManager;
use EasySwoole\EasySwoole\Command\Utility;
use Wa\Service\WaService;
use App\Utility\Common;

class SavePythonWa implements CommandInterface
{
    public function commandName(): string
    {
        return 'save_python_wa';
    }

    public function exec(): ?string
    {
//        // 获取用户输入的命令参数
//        $argv = CommandManager::getInstance()->getOriginArgv();
//
//        if (count($argv) < 3) {
//            echo "please input the action param!" . PHP_EOL;
//            return null;
//        }
//
//        // remove test
//        array_shift($argv);
//
//        // 获取 action 参数
//        $action = $argv[1];
//
//        // 下面就是对 自定义命令 的一些处理逻辑
//        if (!$action) {
//            echo "please input the action param!" . PHP_EOL;
//            return null;
//        }
//
//        // 获取 option 参数
//        $optionArr = $argv[2] ?? [];
//
//        switch ($action) {
//            case 'echo_string':
//                if ($optionArr) {
//                    $strValue = explode('=', $optionArr);
//                    echo $strValue[1] . PHP_EOL;
//                } else {
//                    echo 'this is test!' . PHP_EOL;
//                }
//                break;
//            case 'echo_date':
//                if ($optionArr) {
//                    $strValue = explode('=', $optionArr);
//                    echo "now is " . date('Y-m-d H:i:s') . ' ' . $strValue[1] . '!' . PHP_EOL;
//                } else {
//                    echo "now is " . date('Y-m-d H:i:s') . '!' . PHP_EOL;
//                }
//                break;
//            case 'echo_logo':
//                echo Utility::easySwooleLog();
//                break;
//            default:
//                echo "the action {$action} is not existed!" . PHP_EOL;
//        }
//        return null;
        WaService::savePythonWa();
        return null;
    }

    public function help(CommandHelpInterface $commandHelp): CommandHelpInterface
    {
        // 添加 自定义action(action 名称及描述)
        $commandHelp->addAction('echo_string', 'print the string');
        $commandHelp->addAction('echo_date', 'print the date');
        $commandHelp->addAction('echo_logo', 'print the logo');
        // 添加 自定义action 可选参数
        $commandHelp->addActionOpt('--str=str_value', 'the string to be printed ');
        return $commandHelp;
    }

    // 设置自定义命令描述
    public function desc(): string
    {
        return 'this is test command!';
    }
}