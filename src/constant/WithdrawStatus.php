<?php declare(strict_types=1);

namespace think\pos\constant;

/**
 * 提现状态常量
 */
final class WithdrawStatus
{
    /**
     * 待处理
     * @deprecated 废弃，不明确的语义，请使用 WAIT_CONFIRM
     */
    const INIT = 'INIT';

    /**
     * 待处理
     */
    const WAIT_CONFIRM = 'WAIT_CONFIRM';

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

    // 私有构造和 clone
    private function __construct()
    {
    }

    private function __clone()
    {
    }
}