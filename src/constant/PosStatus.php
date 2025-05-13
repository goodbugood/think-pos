<?php declare(strict_types=1);

namespace think\pos\constant;

/**
 * pos 终端状态
 */
interface PosStatus
{
    /**
     * 绑定成功
     */
    const BIND_SUCCESS = 'bind_success';

    /**
     * 激活成功
     */
    const ACTIVATE_SUCCESS = 'activate_success';

    /**
     * 解绑成功
     */
    const UNBIND_SUCCESS = 'unbind_success';

    /**
     * 换绑成功
     */
    const REBIND_SUCCESS = 'rebind_success';
}
