<?php declare(strict_types=1);

namespace think\pos\constant;

interface SettleType
{
    /**
     * 交易手续费结算
     */
    public const TRANSACTION_FEE = 'transaction_fee';

    /**
     * 提现手续费结算
     */
    public const WITHDRAW_FEE = 'withdraw_fee';
}
