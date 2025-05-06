<?php declare(strict_types=1);

namespace shali\phpmate\tests\util;

use PHPUnit\Framework\TestCase;
use shali\phpmate\util\Money;

/**
 * 货币工具类测试
 * @author shali
 * @date 2025/04/30
 */
class MoneyTest extends TestCase
{

    public function testToFen()
    {
        $money = Money::valueOfYuan('1.23');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('123', $money->toFen());
        self::assertEquals('123.0000', $money->toFen(4));

    }

    public function testToYuan()
    {
        $money = Money::valueOfYuan('1.23');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('1.23', $money->toYuan());
        self::assertEquals('1.2300', $money->toYuan(4));
    }

    public function testValueYuan()
    {
        $money = Money::valueOfYuan('1.23369');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('1.23', $money->toYuan());
    }

    public function testValueFen()
    {
        $money = Money::valueOfFen('123.369');
        self::assertInstanceOf(Money::class, $money);
        self::assertEquals('123', $money->toFen());
        self::assertEquals('123.3', $money->toFen(1));
    }

    public function testAdd()
    {
        $money = Money::valueOfYuan('1.23')->add(Money::valueOfYuan('0.01'));
        self::assertEquals('1.24', $money->toYuan());
        self::assertEquals('124', $money->toFen());
    }

    public function testSub()
    {
        $money = Money::valueOfYuan('1.23')->sub(Money::valueOfYuan('0.01'));
        self::assertEquals('1.22', $money->toYuan());
        self::assertEquals('122', $money->toFen());
    }

    public function testMul()
    {
        $money = Money::valueOfYuan('1.23')->mul('2');
        self::assertEquals('2.46', $money->toYuan());
        self::assertEquals('246', $money->toFen());
    }

    public function testDiv()
    {
        $money = Money::valueOfYuan('1.23')->div('2');
        self::assertEquals('0.61', $money->toYuan());
        self::assertEquals('61', $money->toFen());
    }
}
