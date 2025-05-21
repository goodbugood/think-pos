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
}
