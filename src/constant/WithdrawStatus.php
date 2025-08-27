<?php declare(strict_types=1);

namespace think\pos\constant;

/**
 * 提现状态常量
 */
interface WithdrawStatus
{
    /**
     * 待处理
     */
    const INIT = 'INIT';

    /**
     * 处理中
     */
    const WAIT_AUDIT = 'WAIT_AUDIT';

    /**
     * 成功
     */
    const WAIT_PAY = 'WAIT_PAY';

    /**
     * 失败
     */
    const SUCCESS = 'SUCCESS';

    /**
     * 已取消
     */
    const FAIL = 'FAIL';

    const REJECT = 'REJECT';
}