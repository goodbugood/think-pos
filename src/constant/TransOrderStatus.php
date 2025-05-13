<?php declare(strict_types=1);

namespace think\pos\constant;

interface TransOrderStatus
{
    /**
     * 交易成功
     */
    public const SUCCESS = 'success';

    /**
     * 交易失败
     */
    public const FAILURE = 'failure';
}
