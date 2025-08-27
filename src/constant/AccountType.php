<?php declare(strict_types=1);

namespace think\pos\constant;

/**
 * 账户类型常量
 */
interface AccountType
{
    /**
     * 分润
     */
    const SHARE = 'SHARE';

    /**
     * 服务费返现
     */
    const ACTIVITY_SUBSIDY = 'ACTIVITY_SUBSIDY';

    /**
     * 流量卡
     */
    const SIM_CARD = 'SIM_CARD';

    /**
     * 分成返现
     */
    const COMMISSION = 'COMMISSION';
    /**
     * 活动返现
     */
    const ACTIVITY_CASHBACK = 'ACTIVITY_CASHBACK';
    /**
     * 充值账户
     */
    const ACTIVITY_RECHARGE = 'ACTIVITY_RECHARGE';
}