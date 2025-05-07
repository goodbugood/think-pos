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
}
