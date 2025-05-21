<?php declare(strict_types=1);

namespace shali\phpmate\util;

/**
 * 变量工具类
 */
final class Variables
{
    public static function requireNonNull($obj)
    {
        if (is_null($obj)) {
            throw new \InvalidArgumentException('obj is null');
        }
        return $obj;
    }

    // 禁止实例化
    private function __construct()
    {
    }
}
