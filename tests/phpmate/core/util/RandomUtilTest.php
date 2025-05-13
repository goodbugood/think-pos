<?php

namespace shali\phpmate\tests\core\util;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\util\RandomUtil;

class RandomUtilTest extends TestCase
{
    public function testRandomString()
    {
        $randomStr = RandomUtil::randomString();
        self::assertIsString($randomStr);
        self::assertEquals(16, strlen($randomStr));
        $this->assertNotEquals(RandomUtil::randomString(), RandomUtil::randomString());
    }
}
