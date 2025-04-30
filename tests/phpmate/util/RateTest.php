<?php

namespace shali\phpmate\tests\util;

use PHPUnit\Framework\TestCase;
use shali\phpmate\util\Rate;

class RateTest extends TestCase
{
    public function testToPercentage()
    {
        // 百分比构建
        $rate = Rate::valuePercentage('0.5');
        $this->assertEquals('0.50', $rate->toPercentage());
        $this->assertEquals('0.5', $rate->toPercentage(1));
        self::assertEquals('0.0050', $rate->toDecimal());
        self::assertEquals('0.005', $rate->toDecimal(3));
    }

    public function testToDecimal()
    {
        // 小数构建
        $rate = Rate::valueDecimal('0.5');
        $this->assertEquals('0.5000', $rate->toDecimal());
        $this->assertEquals('0.500', $rate->toDecimal(3));
        self::assertEquals('50.00', $rate->toPercentage());
    }

    public function testValuePercentage()
    {
        // 百分比构建
        $rate = Rate::valuePercentage('0.5');
        self::assertInstanceOf(Rate::class, $rate);
        $this->assertEquals('0.50', $rate->toPercentage());
        $this->assertEquals('0.5', $rate->toPercentage(1));
        self::assertEquals('0.0050', $rate->toDecimal());
        self::assertEquals('0.005', $rate->toDecimal(3));
    }

    public function testValueDecimal()
    {
        // 小数构建
        $rate = Rate::valueDecimal('0.5');
        self::assertInstanceOf(Rate::class, $rate);
        $this->assertEquals('0.5000', $rate->toDecimal());
        $this->assertEquals('0.500', $rate->toDecimal(3));
        self::assertEquals('50.00', $rate->toPercentage());
    }
}
