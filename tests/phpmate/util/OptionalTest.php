<?php declare(strict_types=1);

namespace shali\phpmate\tests\util;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use shali\phpmate\util\Optional;
use stdClass;
use function PHPUnit\Framework\assertEquals;

error_reporting(E_ALL);

class User
{
    public $score;

    public $info;

    public function __construct()
    {
        $this->score = new Score();
    }

    public function getMathScore()
    {
        return $this->score->math;
    }
}

class Info
{
    public $name = 'shali';

    public $age;
}

class Score
{
    public $math = 95;
}

/**
 * @see https://github.com/laravel/framework/blob/12.x/src/Illuminate/Support/Optional.php
 * @see java.util.Optional
 */
class OptionalTest extends TestCase
{
    public function testToString()
    {
        $name = 'shali';
        $optional = Optional::of($name);
        $this->assertEquals($name, $optional->toString());
        $this->assertEquals('null', Optional::empty()->toString());
        $this->assertEquals('null', Optional::ofNullable(null)->toString());
        $this->assertEquals('{"name":"shali"}', Optional::of(['name' => 'shali'])->toString());
        $this->assertEquals('{"name":"shali"}', Optional::ofNullable(['name' => 'shali'])->toString());
        $obj = new stdClass();
        $obj->name = 'shali';
        $this->assertEquals('{"name":"shali"}', Optional::of($obj)->toString());
        self::assertEquals('123', Optional::of(123)->toString());
        self::assertEquals('{"score":{"math":95},"info":null}', Optional::of(new User())->toString());
    }

    public function test__toString()
    {
        $optional = Optional::of("shali");
        $this->assertEquals('shali', $optional->toString());
        // __toString
        $this->assertEquals('shali', sprintf('%s', $optional));
        $this->assertEquals('{"score":{"math":95},"info":null}', sprintf('%s', Optional::of(new User())));
    }

    public function testEmpty()
    {
        // empty 是单例
        self::assertSame(Optional::empty(), Optional::empty());
        $optional = Optional::empty();
        $this->assertFalse($optional->isPresent());
        $this->expectException(RuntimeException::class);
        $optional->get();
    }

    /**
     * 检查是否存在
     * @return void
     */
    public function testIsPresent()
    {
        $optional = Optional::of(new User());
        $this->assertTrue($optional->isPresent());
        self::assertFalse(Optional::ofNullable(null)->isPresent());
        self::assertFalse(Optional::empty()->isPresent());
    }

    /**
     * 检查是否存在，存在则执行回调方法
     * @return void
     */
    public function testIfPresentConsumer()
    {
        $optional = Optional::of(new User());
        // 1 次
        self::assertTrue($optional->isPresent());
        $optional->ifPresentConsumer(function (User $user) {
            // 1 次
            $this->assertEquals(95, $user->score->math);
        });
        Optional::empty()->ifPresentConsumer(function () {
            // 不会执行
            self::assertTrue(true);
        });
        self::assertEquals(2, self::getCount());
    }

    public function testIfPresentOrElse()
    {
        $optional = Optional::of(new User());
        $optional->ifPresentOrElse(function (User $user) {
            // 1 次
            $this->assertEquals(95, $user->score->math);
        }, function () {
            // 未执行
            self::assertTrue(true);
            self::assertTrue(true);
        });
        $optional = Optional::empty();
        $optional->ifPresentOrElse(function () {
            $this->fail();
        }, function () {
            // 1 次
            $this->assertTrue(true);
        });
        // 测试方法调用次数
        self::assertEquals(2, self::getCount());
    }

    public function testMap()
    {
        $optional = Optional::of(new User());
        $this->assertEquals(95, $optional->map(function (User $user) {
            return $user->score->math;
        })->get());
        self::assertInstanceOf(Optional::class, $optional->map(function (User $user) {
            return $user->score;
        }));
        // 函数返回 Optional 时会造成嵌套
        self::assertInstanceOf(Optional::class, $optional->map(function (User $user) {
            return Optional::of($user->score);
        })->get());
    }

    public function testFlatMap()
    {
        $optional = Optional::of(new User());
        $this->assertEquals(95, $optional->flatMap(function (User $user) {
            return $user->score->math;
        })->get());
        self::assertInstanceOf(Optional::class, $optional->flatMap(function (User $user) {
            return $user->score;
        }));
        // 函数返回 Optional 时不会造成嵌套
        self::assertInstanceOf(Score::class, $optional->flatMap(function (User $user) {
            return Optional::of($user->score);
        })->get());
    }

    public function testGet()
    {
        $optional = Optional::of(new User());
        $this->assertEquals(95, $optional->get()->score->math);
        // 空值会抛出异常
        self::expectException(RuntimeException::class);
        self::assertNull(Optional::empty()->get());
    }

    public function testOrElse()
    {
        self::assertEquals(95, Optional::ofNullable(95)->orElse(59));
        self::assertEquals(59, Optional::ofNullable(null)->orElse(59));
    }

    public function testOrElseGet()
    {
        assertEquals(95, Optional::ofNullable(95)->orElseGet(function () {
            return 59;
        }));
        assertEquals(59, Optional::ofNullable(null)->orElseGet(function () {
            return 59;
        }));
    }

    //region 对象运算符
    public function test__isset()
    {
        $optional = Optional::of(new User());
        self::assertTrue(isset($optional->score));
        self::assertTrue(isset($optional['score']));
        self::assertTrue(isset($optional->score->math));
        self::assertTrue(isset($optional['score']['math']));
        $optional1 = Optional::of(['name' => 'shali']);
        self::assertTrue(isset($optional1['name']));
        self::assertTrue(isset($optional1->name));
    }

    public function test__unset()
    {
        $optional = Optional::of(new User());
        unset($optional['score']);
        self::assertFalse(isset($optional->score));
        self::assertFalse(isset($optional->score->math));
        $optional1 = Optional::of(['name' => 'shali']);
        unset($optional1['name']);
        self::assertFalse(isset($optional1->name));
    }

    public function test__set()
    {
        $stdClass = new stdClass();
        $optional = Optional::of($stdClass);
        $optional->name = 'shali';
        // 包装的是引用类型，会更改引用类型变量的值
        assertEquals('shali', $stdClass->name);
        $optional['name'] = 'shali2';
        assertEquals('shali2', $stdClass->name);
        $arr = [];
        $optional = Optional::of($arr);
        $optional['name'] = 'shali0';
        assertEquals('shali0', $optional['name']);
        $optional->name = 'shali';
        assertEquals('shali', $optional->name);
    }

    public function test__get()
    {
        $optional = Optional::of(new User());
        $this->assertEquals(95, $optional->score->math->get());
        $this->assertEquals(95, $optional['score']['math']->get());
        $optional1 = Optional::of(['score' => ['math' => 95]]);
        $this->assertEquals(95, $optional1['score']['math']->get());
        // 数组也可以当对象访问
        self::assertEquals(95, $optional1->score->math->get());
    }

    public function test__call()
    {
        $optional = Optional::of(new User());
        self::assertEquals(95, $optional->getMathScore()->get());
        $optional1 = Optional::of(['name' => 'shali']);
        self::assertEquals(23, $optional1->getName()->orElse(23));
    }

    //endregion

    public function testFilter()
    {
        $ages = [25, 30, 35, 14, 55];
        self::assertEquals(Optional::empty(), Optional::of($ages)->filter(function ($ages) {
            return count($ages) > 5;
        }));
        $age = 35;
        $optional = Optional::of($age);
        $this->assertEquals($age, $optional->filter(function ($age) {
            return $age >= 35;
        })->get());
        self::expectException(RuntimeException::class);
        $this->assertEquals($age, $optional->filter(function ($age) {
            return $age < 35;
        })->get());
    }

    public function testOf()
    {
        $name = 'shali';
        $optional = Optional::of($name);
        $this->assertEquals($name, $optional->get());
        $this->assertTrue($optional->isPresent());
        // 设置 null 值，会抛出异常
        $name = null;
        $this->expectException(InvalidArgumentException::class);
        Optional::of($name);
    }

    public function testOfNullable()
    {
        $name = 'shali';
        $optional = Optional::ofNullable($name);
        $this->assertEquals($name, $optional->get());
        $this->assertTrue($optional->isPresent());
        $name = null;
        $optional = Optional::ofNullable($name);
        $this->assertFalse($optional->isPresent());
        $this->expectException(RuntimeException::class);
        $this->assertNull($optional->get());
    }

    public function testEquals()
    {
        $name = 'shali';
        $optional = Optional::of($name);
        $this->assertTrue($optional->equals(Optional::of($name)));
        self::assertTrue(Optional::empty()->equals(Optional::empty()));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testOrElseThrow()
    {
        $name = 'shali';
        $optional = Optional::of($name);
        $this->assertEquals($name, $optional->orElseThrow(function () {
            return new Exception('error');
        }));
        $this->expectException(Exception::class);
        $optional = Optional::empty();
        $optional->orElseThrow(function () {
            return new Exception('error');
        });
    }
}
