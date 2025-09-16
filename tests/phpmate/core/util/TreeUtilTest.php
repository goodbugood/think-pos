<?php

namespace shali\phpmate\tests\core\util;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\util\TreeUtil;

class TreeUtilTest extends TestCase
{
    public function testBuildTreeForArray()
    {
        $data = [
            ['id' => 1, 'parent_id' => 0],
            ['id' => 3, 'parent_id' => 1],
            ['id' => 2, 'parent_id' => 1],
            ['id' => 5, 'parent_id' => 0],
            ['id' => 4, 'parent_id' => 0],
        ];
        $tree = TreeUtil::buildTreeForArray($data, 'id', 'parent_id');
        $this->assertEquals([
            ['id' => 1, 'parent_id' => 0, 'children' => [['id' => 3, 'parent_id' => 1], ['id' => 2, 'parent_id' => 1]]],
            ['id' => 5, 'parent_id' => 0],
            ['id' => 4, 'parent_id' => 0],
        ], $tree);
        self::assertEquals($data, TreeUtil::flattenTreeToArray($tree));
    }
}
