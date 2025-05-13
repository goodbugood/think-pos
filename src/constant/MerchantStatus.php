<?php declare(strict_types=1);

namespace think\pos\constant;

/**
 * 商户状态枚举
 */
interface MerchantStatus
{
    /**
     * 商户状态：可用
     */
    public const ENABLE = 1;

    /**
     * 商户状态：禁用
     */
    public const DISABLE = 2;

    /**
     * 商户状态：删除
     */
    public const DELETE = 3;
}
