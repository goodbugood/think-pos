<?php

namespace shali\phpmate\tests\core\date;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\date\LocalDateTime;

class LocalDateTimeTest extends TestCase
{
    private $now = '2025-05-26 10:23:45';

    /**
     * @var LocalDateTime
     */
    private $localDateTime;

    protected function setUp(): void
    {
        $this->nowLocalDateTime = LocalDateTime::valueOfString($this->now);
    }

    public function testToString()
    {
        $this->assertEquals($this->now, $this->nowLocalDateTime->toString());
    }

    public function testGetDaysOfMonth()
    {
        $this->assertEquals(31, $this->nowLocalDateTime->getDaysOfMonth());
        $this->assertEquals(30, LocalDateTime::valueOfString('2025-04-03 10:23:45')->getDaysOfMonth());
        self::assertEquals(28, LocalDateTime::valueOfString('2025-02-03 10:23:45')->getDaysOfMonth());
    }

    public function testGetDate()
    {
        $this->assertEquals('2025-05-26', $this->nowLocalDateTime->getDate());
    }

    public function testNextWeek()
    {
        $this->assertEquals('2025-06-02 10:23:45', $this->nowLocalDateTime->nextWeek()->toString());
    }

    public function testIsExpired()
    {
        $this->assertTrue($this->nowLocalDateTime->isExpired());
        $this->assertFalse(LocalDateTime::valueOfString('2099-05-26 10:23:45')->isExpired());
    }

    public function testGetTimestamp()
    {
        $this->assertEquals(strtotime($this->now), $this->nowLocalDateTime->getTimestamp());
    }

    public function test__toString()
    {
        $this->assertEquals($this->now, (string)$this->nowLocalDateTime);
    }

    public function testFormat()
    {
        $this->assertEquals('2025-05-26', $this->nowLocalDateTime->format('Y-m-d'));
        $this->assertEquals('2025-05-26 10:23:45', $this->nowLocalDateTime->format());
        $this->assertEquals('2025-05', $this->nowLocalDateTime->format('Y-m'));
    }

    public function testBeginOfDay()
    {
        $this->assertEquals('2025-05-26 00:00:00', $this->nowLocalDateTime->beginOfDay()->toString());
        self::assertEquals('2025-07-26 00:00:00', LocalDateTime::valueOfString('2025-07-26 10:23:45')->beginOfDay()->toString());
    }

    public function testOffsetDays()
    {
        $this->assertEquals('2025-05-25 10:23:45', $this->nowLocalDateTime->offsetDays(-1)->toString());
        self::assertEquals('2025-07-27 10:23:45', LocalDateTime::valueOfString('2025-07-26 10:23:45')->offsetDays(1)->toString());
    }

    public function testNow()
    {
        $this->assertEquals(date('Y-m-d H:i:s'), LocalDateTime::now()->toString());
        self::assertEquals(time(), LocalDateTime::now()->getTimestamp());
    }

    public function testLastWeek()
    {
        $this->assertEquals('2025-05-19 10:23:45', $this->nowLocalDateTime->lastWeek()->toString());
        self::assertEquals('2025-07-19 10:23:45', LocalDateTime::valueOfString('2025-07-26 10:23:45')->lastWeek()->toString());
    }

    public function testGetTime()
    {
        $this->assertEquals('10:23:45', $this->nowLocalDateTime->getTime());
        self::assertEquals('10:23:45', LocalDateTime::valueOfString('2025-05-26 10:23:45')->getTime());
        self::assertEquals('10_23_45', LocalDateTime::valueOfString('2025-05-26 10:23:45')->getTime('_'));
    }

    public function testEndOfDay()
    {
        self::assertEquals('2025-05-26 23:59:59', $this->nowLocalDateTime->endOfDay()->toString());
        self::assertEquals('2025-07-26 23:59:59', LocalDateTime::valueOfString('2025-07-26 10:23:45')->endOfDay()->toString());
    }

    public function testYesterday()
    {
        self::assertEquals('2025-02-28', LocalDateTime::valueOfString('2025-02-29 10:23:45')->yesterday()->getDate());
        self::assertEquals('20250525', LocalDateTime::valueOfString('2025-05-26 10:23:45')->yesterday()->getDate(''));
    }

    public function testTomorrow()
    {
        self::assertEquals('2025-03-01', LocalDateTime::valueOfString('2025-02-28 10:23:45')->tomorrow()->getDate());
        self::assertEquals('2025/05/27', LocalDateTime::valueOfString('2025-05-26 10:23:45')->tomorrow()->getDate('/'));
    }

    public function testIsLeapYear()
    {
        self::assertTrue(LocalDateTime::valueOfString('2008-05-26 10:23:45')->isLeapYear());
        self::assertFalse(LocalDateTime::valueOfString('2009-05-26 10:23:45')->isLeapYear());
        self::assertFalse(LocalDateTime::valueOfString('2010-05-26 10:23:45')->isLeapYear());
        self::assertFalse(LocalDateTime::valueOfString('2011-05-26 10:23:45')->isLeapYear());
        self::assertTrue(LocalDateTime::valueOfString('2012-05-26 10:23:45')->isLeapYear());
    }

    /**
     * 计算一年的结束时间
     * @return void
     */
    public function testEndOfYear()
    {
        self::assertEquals('2025-12-31 23:59:59', LocalDateTime::valueOfString('2025-05-26 10:23:45')->endOfYear()->toString());
        self::assertEquals('2025-12-31 23:59:59', LocalDateTime::valueOfString('2025-12-31 10:23:45')->endOfYear()->toString());
        self::assertEquals('2026-12-31 23:59:59', LocalDateTime::valueOfString('2026-12-31 10:23:45')->endOfYear()->toString());
    }

    public function testValueOfString()
    {
        self::assertEquals('2025-05-26 10:23:45', LocalDateTime::valueOfString('2025-05-26 10:23:45')->toString());
    }

    public function testBeginOfYear()
    {
        self::assertEquals('2025-01-01 00:00:00', LocalDateTime::valueOfString('2025-05-26 10:23:45')->beginOfYear()->toString());
        self::assertEquals('2025-01-01 00:00:00', LocalDateTime::valueOfString('2025-01-01 10:23:45')->beginOfYear()->toString());
    }

    public function testIsLastDayOfMonth()
    {
        self::assertTrue(LocalDateTime::valueOfString('2025-05-31 10:23:45')->isLastDayOfMonth());
        self::assertFalse(LocalDateTime::valueOfString('2025-05-30 10:23:45')->isLastDayOfMonth());
        self::assertTrue(LocalDateTime::valueOfString('2025-02-28 10:23:45')->isLastDayOfMonth());
        self::assertFalse(LocalDateTime::valueOfString('2025-02-29 10:23:45')->isLastDayOfMonth());
    }

    public function testBetweenDays()
    {
        self::assertEquals(2, LocalDateTime::valueOfString('2025-05-26 10:23:45')->betweenDays(LocalDateTime::valueOfString('2025-05-28 10:23:45')));
        // 少 1 秒少 1 天
        self::assertEquals(1, LocalDateTime::valueOfString('2025-05-26 10:23:45')->betweenDays(LocalDateTime::valueOfString('2025-05-28 10:23:44')));
        self::assertEquals(-1, LocalDateTime::valueOfString('2025-05-27 10:23:45')->betweenDays(LocalDateTime::valueOfString('2025-05-26 10:23:45')));
        self::assertEquals(0, LocalDateTime::valueOfString('2025-05-26 10:23:45')->betweenDays(LocalDateTime::valueOfString('2025-05-26 10:23:45')));
    }

    public function testBeginOfMonth()
    {
        self::assertEquals('2025-05-01 00:00:00', LocalDateTime::valueOfString('2025-05-26 10:23:45')->beginOfMonth()->toString());
        self::assertEquals('2025-05-01 00:00:00', LocalDateTime::valueOfString('2025-05-01 10:23:45')->beginOfMonth()->toString());
        self::assertEquals('2025-06-01 00:00:00', LocalDateTime::valueOfString('2025-05-26 10:23:45')->nextMonth()->beginOfMonth()->toString());
    }

    public function testEndOfMonth()
    {
        self::assertEquals('2025-05-31 23:59:59', LocalDateTime::valueOfString('2025-05-26 10:23:45')->endOfMonth()->toString());
        self::assertEquals('2025-05-31 23:59:59', LocalDateTime::valueOfString('2025-05-31 10:23:45')->endOfMonth()->toString());
        self::assertEquals('2025-06-30 23:59:59', LocalDateTime::valueOfString('2025-05-26 10:23:45')->nextMonth()->endOfMonth()->toString());
        self::assertEquals('2008-02-29', LocalDateTime::valueOfString('2008-02-01')->endOfMonth()->getDate());
        self::assertEquals('2009-02-28', LocalDateTime::valueOfString('2009-02-01')->endOfMonth()->getDate());
    }

    public function testDayOfMonth()
    {
        self::assertEquals(26, LocalDateTime::valueOfString('2025-05-26 10:23:45')->dayOfMonth());
        self::assertEquals(1, LocalDateTime::valueOfString('2025-05-01 10:23:45')->dayOfMonth());
        self::assertEquals(31, LocalDateTime::valueOfString('2025-05-31 10:23:45')->dayOfMonth());
        self::assertEquals(1, LocalDateTime::valueOfString('2025-06-01 10:23:45')->nextMonth()->dayOfMonth());
    }

    public function testCompareTo()
    {
        self::assertEquals(-1, LocalDateTime::valueOfString('2025-05-26 10:23:45')->compareTo(LocalDateTime::valueOfString('2025-05-27 10:23:45')));
        self::assertEquals(1, LocalDateTime::valueOfString('2025-05-27 10:23:45')->compareTo(LocalDateTime::valueOfString('2025-05-26 10:23:45')));
        self::assertEquals(0, LocalDateTime::valueOfString('2025-05-26 10:23:45')->compareTo(LocalDateTime::valueOfString('2025-05-26 10:23:45')));
    }

    public function testLastMonth()
    {
        self::assertEquals('2025-04-26 10:23:45', LocalDateTime::valueOfString('2025-05-26 10:23:45')->lastMonth()->toString());
        self::assertEquals('2025-03-26 10:23:45', LocalDateTime::valueOfString('2025-04-26 10:23:45')->lastMonth()->toString());
        // 平年2月28天
        self::assertEquals('2025-02-28', LocalDateTime::valueOfString('2025-03-31')->lastMonth()->getDate());
        self::assertEquals('2025-03-03', LocalDateTime::valueOfString('2025-03-31')->lastMonth(false)->getDate());
        // 闰年2月29天
        self::assertEquals('2024-02-29', LocalDateTime::valueOfString('2024-03-31')->lastMonth()->getDate());
        self::assertEquals('2024-03-02', LocalDateTime::valueOfString('2024-03-31')->lastMonth(false)->getDate());
    }

    public function testNextMonth()
    {
        self::assertEquals('2025-06-26 10:23:45', LocalDateTime::valueOfString('2025-05-26 10:23:45')->nextMonth()->toString());
        self::assertEquals('2025-07-26 10:23:45', LocalDateTime::valueOfString('2025-06-26 10:23:45')->nextMonth()->toString());
        self::assertEquals('2008-02-29', LocalDateTime::valueOfString('2008-01-31')->nextMonth()->getDate());
        self::assertEquals('2008-03-02', LocalDateTime::valueOfString('2008-01-31')->nextMonth(false)->getDate());
        self::assertEquals('2009-02-28', LocalDateTime::valueOfString('2009-01-31')->nextMonth()->getDate());
        self::assertEquals('2009-03-03', LocalDateTime::valueOfString('2009-01-31')->nextMonth(false)->getDate());
    }
}
