<?php declare(strict_types=1);

namespace shali\phpmate\tests;

use PHPUnit\Framework\TestCase;

class functionsTest extends TestCase
{
    /**
     * @test
     */
    public function str2time()
    {
        $now = '2022-03-31';
        self::assertNotEquals('2022-02-28', date('Y-m-d', strtotime('-1 month', strtotime($now))));
        self::assertEquals('2022-03-03', date('Y-m-d', strtotime('-1 month', strtotime($now))));
        self::assertNotEquals('2022-04-30', date('Y-m-d', strtotime('+1 month', strtotime($now))));
        self::assertEquals('2022-05-01', date('Y-m-d', strtotime('+1 month', strtotime($now))));
        //
        self::assertEquals('2022-02-28', date('Y-m-d', str2time('-1 month', strtotime($now))));
        self::assertEquals('2022-04-30', date('Y-m-d', str2time('+1 month', strtotime($now))));
    }

    /**
     * @test
     */
    public function equals()
    {
        self::assertTrue(0 == 'php');
        self::assertFalse(equals(0, 'php'));
        // 空值：空格不是空值
        self::assertTrue(empty(0));
        self::assertTrue(empty('0'));
        self::assertTrue(empty(''));
        self::assertFalse(empty(' '));
        self::assertFalse(empty("\r\n"));
        self::assertFalse(empty("\t"));
        self::assertTrue(empty(null));
        self::assertTrue(empty([]));
        // 数字0只与数组不等
        self::assertTrue(equals(0, 0));
        self::assertTrue(0 == '');
        self::assertTrue(equals(0, ''));
        self::assertTrue(0 == ' ');
        self::assertTrue(equals(0, ' '));
        self::assertTrue(0 == '0');
        self::assertTrue(equals(0, '0'));
        self::assertTrue(0 == null);
        self::assertTrue(equals(0, null));
        self::assertFalse(0 == []);
        self::assertFalse(equals(0, []));
        // 空字符串只与null和数字0相等
        self::assertTrue(equals('', ''));
        self::assertFalse('' == ' ');
        self::assertFalse(equals('', ' '));
        self::assertFalse('' == '0');
        self::assertFalse(equals('', '0'));
        self::assertTrue('' == null);
        self::assertTrue(equals('', null));
        self::assertFalse('' == []);
        self::assertFalse(equals('', []));
        // 空格只与数字0相等，与其他值不等
        self::assertTrue(equals(' ', ' '));
        self::assertFalse(' ' == '0');
        self::assertFalse(equals(' ', '0'));
        self::assertFalse(' ' == null);
        self::assertFalse(equals(' ', null));
        self::assertFalse(' ' == []);
        self::assertFalse(equals(' ', []));
        // 字符串0只与数字0相等，与其他值比较不相等
        self::assertTrue(equals('0', '0'));
        self::assertFalse('0' == null);
        self::assertFalse(equals('0', null));
        self::assertFalse('0' == []);
        self::assertFalse(equals('0', []));
        // null只与数字0，空字符，空数组相等
        self::assertTrue(equals(null, null));
        self::assertTrue(null == []);
        self::assertTrue(equals(null, []));
        // 空数组与null比较相等，与其他非数组比较不等
        self::assertTrue(equals([], []));
        //
        self::assertTrue(0 == "\t");
        self::assertTrue(equals(0, "\t"));
    }

    /**
     * @test 测试 csv 字段内容包含英文半角逗号转义问题
     */
    public function escape_commas_of_csv()
    {
        // 规格字段
        // 转义原则：
        // 1. 含有英文半角逗号的，整个字段用双引号包裹
        self::assertSame('"箱,12瓶/箱"', escape_commas_of_csv('箱,12瓶/箱'));
        // 2. 含有英文半角逗号的同时含有半角双引号的，每个引号用 1 个引号进行转义
        self::assertSame('箱"12瓶/箱', escape_commas_of_csv('箱"12瓶/箱'));
        self::assertSame('"箱,""12瓶/箱"', escape_commas_of_csv('箱,"12瓶/箱'));
        self::assertSame('"箱,""""12瓶/箱"', escape_commas_of_csv('箱,""12瓶/箱'));
        self::assertSame('箱，12瓶/箱', escape_commas_of_csv('箱，12瓶/箱'));
    }
}
