<?php declare(strict_types=1);

namespace shali\phpmate\core\util;

class StrUtil
{
    const EMPTY = '';

    const SPACE = ' ';

    const NULL = 'null';

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
        return urldecode(http_build_query($params));
    }

    /**
     * @param mixed $jsonStr
     * @return bool
     */
    public static function isJson($jsonStr): bool
    {
        if (!is_string($jsonStr)) {
            return false;
        }
        $json = json_decode($jsonStr, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($json);
    }

    /**
     * 判断字符串是否以指定字符串开头，且严格区分大小写
     * @param string $str
     * @param string $prefix
     * @return bool
     */
    public static function startWith(string $str, string $prefix): bool
    {
        if (self::EMPTY === $prefix) {
            return self::EMPTY === $str;
        }
        return strpos($str, $prefix) === 0;
    }

    /**
     * 判断字符串是否以指定字符串结尾，且严格区分大小写
     * @param string $str
     * @param string $suffix
     * @return bool
     */
    public static function endWith(string $str, string $suffix): bool
    {
        if (self::EMPTY === $suffix) {
            return self::EMPTY === $str;
        }
        return strrpos($str, $suffix) === strlen($str) - strlen($suffix);
    }
}
