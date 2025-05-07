<?php declare(strict_types=1);

namespace shali\phpmate\core;

class StrUtil
{
    /**
     * 数组转成 & 拼接，字典序
     * @param array $params
     * @param bool $sort 是否字典序排序
     * @return string
     * @example $params = ['a' => 1, 'c' => 3, 'b' => 2]; $sort = true; return 'a=1&b=2&c=3';
     */
    public static function httpBuildQuery(array $params, bool $sort = false): string
    {
        $sort && ksort($params);
        return http_build_query($params);
    }
}
