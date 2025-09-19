<?php declare(strict_types=1);

namespace shali\phpmate\core\util\constant;

/**
 * 舍入模式
 * @see java.math.RoundingMode jdk11
 */
final class RoundingMode
{
    /**
     * 远离零方向舍入（绝对值变大）
     */
    public const UP = 'UP';

    /**
     *  Towards zero方向舍入（绝对值变小）
     */
    public const DOWN = 'DOWN';

    /**
     * 四舍五入
     */
    public const HALF_UP = 'HALF_UP';

    /**
     * 五舍六入
     */
    public const HALF_DOWN = 'HALF_DOWN';

    /**
     * 银行家舍入法：向最近的偶数舍入
     */
    public const HALF_EVEN = 'HALF_EVEN';

    /**
     * 向正无穷方向舍入：1.1 -> 2，-1.1 -> -1
     */
    public const CEILING = 'CEILING';
    
    /**
     * 向负无穷方向舍入：1.1 -> 1，-1.1 -> -2
     */
    public const FLOOR = 'FLOOR';

    /**
     * 不需要舍入，因为数值已经精确
     */
    public const UNNECESSARY = 'UNNECESSARY';

    /** 私有构造方法 */
    private function __construct()
    {
    }

    /**
     * 不允许实例化
     */
    private function __clone()
    {
    }
}
