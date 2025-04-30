<?php

namespace shali\phpmate\tests\util;

use PHPUnit\Framework\TestCase;
use shali\phpmate\util\Money;

class MoneyTest extends TestCase
{

    public function testToFen()
    {
        $money = Money::valueYuan('1.23');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('123', $money->toFen());
        self::assertEquals('123.0000', $money->toFen(4));

    }

    public function testToYuan()
    {
        $money = Money::valueYuan('1.23');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('1.23', $money->toYuan());
        self::assertEquals('1.2300', $money->toYuan(4));
    }

    public function testValueYuan()
    {
        $money = Money::valueYuan('1.23369');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('1.23', $money->toYuan());
    }

    public function testValueFen()
    {
        $money = Money::valueFen('123.369');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('123', $money->toFen());
        self::assertEquals('123.3', $money->toFen(1));
    }
}
