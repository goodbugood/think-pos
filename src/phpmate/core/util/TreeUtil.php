<?php declare(strict_types=1);

namespace shali\phpmate\core\util;

final class TreeUtil
{
    /**
     * 将扁平数组转换为树状结构数组
     *
     * @param array $list 扁平数组数据
     * @param string $idKey ID 字段名
     * @param string $parentIdKey 父级 ID 字段名
     * @param string $childrenKey 子节点字段名
     * @return array 树状结构数组
     */
    public static function buildTreeForArray(array $list, string $idKey = 'id', string $parentIdKey = 'parentId', string $childrenKey = 'children'): array
    {
        if (empty($list)) {
            return [];
        }

        // 建立 ID 到元素的映射，便于快速查找
        $itemMap = [];
        foreach ($list as $item) {
            $id = $item[$idKey] ?? null;
            if (null === $id) {
                continue;
            }
            $itemMap[$id] = $item;
        }

        $tree = [];
        foreach ($list as $item) {
            $currentId = $item[$idKey] ?? null;
            if (null === $currentId) {
                continue;
            }

            $parentId = $item[$parentIdKey] ?? null;

            // 如果没有父节点或父节点不存在，则为根节点
            if ($parentId === null || !isset($itemMap[$parentId])) {
                $tree[] = &$itemMap[$currentId];
            } else {
                // 添加到父节点的 children 中
                if (!isset($itemMap[$parentId][$childrenKey])) {
                    $itemMap[$parentId][$childrenKey] = [];
                }
                $itemMap[$parentId][$childrenKey][] = &$itemMap[$currentId];
            }
        }

        return $tree;
    }

    /**
     * 将树状结构数组转换为扁平数组
     *
     * @param array $tree 树状结构数组
     * @param string $childrenKey 子节点字段名
     * @return array 扁平数组
     */
    public static function flattenTreeToArray(array $tree, string $childrenKey = 'children'): array
    {
        $result = [];

        foreach ($tree as $node) {
            $children = $node[$childrenKey] ?? [];
            unset($node[$childrenKey]);
            $result[] = $node;
            if (!empty($children)) {
                $result = array_merge($result, self::flattenTreeToArray($children, $childrenKey));
            }
        }

        return $result;
    }

    // 私有构造方法和克隆方法
    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
