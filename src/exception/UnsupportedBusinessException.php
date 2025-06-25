<?php declare(strict_types=1);

namespace think\pos\exception;

use Exception;

/**
 * 不支持的业务异常
 * 即调用了部分平台不支持未对接的业务
 */
class UnsupportedBusinessException extends Exception
{
}