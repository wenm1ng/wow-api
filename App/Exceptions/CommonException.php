<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 11:46
 */
namespace App\Exceptions;

use Common\CodeKey;

class CommonException extends \Exception
{
    public function __construct(int $code = 0, string $message = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param string $message
     * @param int $code
     * @throws CommonException
     */
    public static function msgException(string $message, int $code = CodeKey::FAIL)
    {
        throw new self($code, $message);
    }

    /**
     * @param int $code
     * @throws BusinessException
     */
    public static function throwException(int $code)
    {
        $msg = config('error_code.'.$code);
        throw new self($code, $msg);
    }
}