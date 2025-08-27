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
    /**
     * 对公结算
     */
    public const TO_PUBLIC = 'TO_PUBLIC';

    /**
     * 对私结算
     */
    public const TO_PUBLIC_UNINCORPORATED = 'TO_PUBLIC_UNINCORPORATED';
    /**
     * 法人对私
     * */
    public const TO_PRIVATE = 'TO_PRIVATE';
}
