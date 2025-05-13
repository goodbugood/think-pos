<?php declare(strict_types=1);

namespace think\pos\constant;

/**
 * 交易订单类型
 * @author shali
 * @date 2025/05/13
 */
interface TransOrderType
{
    /**
     * 押金订单，就是卖机器给你的钱，说是押金，其实不是，因为退不了，顶多返现
     */
    const DEPOSIT = 'deposit';

    /**
     * 流量费订单
     */
    const SIM = 'sim';

    /**
     * 刷卡扫码产生普通订单
     */
    const NORMAL = 'normal';
}
