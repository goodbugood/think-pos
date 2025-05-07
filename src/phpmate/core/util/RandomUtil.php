<?php declare(strict_types=1);

namespace shali\phpmate\core\util;

class RandomUtil
{
    /**
     * 随机一个指定长度的字符串，包含数字字母，区分大小写
     * @param int $length
     * @return string
     */
    public static function randomString(int $length = 16): string
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 5)), 0, $length);
    }

    // 禁止 new
    private function __construct()
    {
    }
}
