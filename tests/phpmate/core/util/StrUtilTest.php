<?php

namespace shali\phpmate\tests\core\util;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\util\StrUtil;

class StrUtilTest extends TestCase
{
    /**
     * @test 测试数组转成 & 拼接，字典序
     */
    function httpBuildQuery()
    {
        $params = [
            'b' => '2',
            'a' => '1',
            'c' => '3',
        ];
        self::assertEquals('b=2&a=1&c=3', StrUtil::httpBuildQuery($params));
        $this->assertEquals('a=1&b=2&c=3', StrUtil::httpBuildQuery($params, true));
    }

    /**
     * @test 测试是否是json
     * @return void
     */
    function isJsonVar()
    {
        $json1 = 123;
        $json2 = '{"a":1,"b":2,"c":3}';
        self::assertFalse(StrUtil::isJson($json1));
        self::assertTrue(StrUtil::isJson($json2));
        self::assertFalse(StrUtil::isJson(''));
        self::assertFalse(StrUtil::isJson('null'));
        self::assertFalse(StrUtil::isJson('"name"'));
    }

    /**
     * @test 测试字符串是否以某个字符串开头
     * @return void
     */
    function startWith()
    {
        self::assertTrue(StrUtil::startWith('123456', '123'));
        self::assertFalse(StrUtil::startWith('123456', '124'));
        self::assertTrue(StrUtil::startWith('abc', 'ab'));
        self::assertFalse(StrUtil::startWith('abc', 'Ab'));
        self::assertFalse(StrUtil::startWith('abc', ' '));
        self::assertFalse(StrUtil::startWith('abc', ''));
        self::assertTrue(StrUtil::startWith('', ''));
        self::assertFalse(StrUtil::startWith('', 'abc'));
        self::assertFalse(StrUtil::startWith('', ' '));
        self::assertTrue(StrUtil::startWith(' ', ' '));
        self::assertFalse(StrUtil::startWith(' ', ''));
    }

    /**
     * @test 测试字符串是否以某个字符串结尾
     * @return void
     */
    function endWith()
    {
        self::assertTrue(StrUtil::endWith('123456', '456'));
        self::assertFalse(StrUtil::endWith('123456', '457'));
        self::assertTrue(StrUtil::endWith('abc', 'bc'));
        self::assertFalse(StrUtil::endWith('abc', 'Bc'));
        self::assertFalse(StrUtil::endWith('abc', ' '));
        self::assertFalse(StrUtil::endWith('abc', ''));
        self::assertTrue(StrUtil::endWith('', ''));
        self::assertFalse(StrUtil::endWith('', 'abc'));
        self::assertFalse(StrUtil::endWith('', ' '));
        self::assertTrue(StrUtil::endWith(' ', ' '));
        self::assertFalse(StrUtil::endWith(' ', ''));
    }
}
