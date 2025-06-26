<?php

namespace think\pos\tests\extend;

use PHPUnit\Framework\TestCase;
use think\pos\exception\ProviderGatewayException;
use think\pos\extend\Assert;

class AssertTest extends TestCase
{
    public function testIsNull()
    {
        self::expectException(ProviderGatewayException::class);
        Assert::isNull(1);
    }

    public function testNotNull()
    {
        self::expectException(ProviderGatewayException::class);
        Assert::notNull(null);
    }

    public function testInArray()
    {
        self::expectException(ProviderGatewayException::class);
        Assert::inArray('1', ['2']);
    }

    public function testKeyExists()
    {
        self::expectException(ProviderGatewayException::class);
        Assert::keyExists('key', []);
    }

    public function testNotEmpty()
    {
        self::expectException(ProviderGatewayException::class);
        Assert::notEmpty('0');
    }

    /**
     * @throws ProviderGatewayException
     */
    public function testIsNotBlank()
    {
        // 非空白字符
        Assert::notBlank('0');
        Assert::notBlank(null);
        // 空白字符
        self::expectException(ProviderGatewayException::class);
        Assert::notBlank(' ');
    }
}
