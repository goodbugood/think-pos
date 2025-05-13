<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\constant\PosStatus;
use think\pos\dto\request\CallbackRequest;

/**
 * pos 终端激活回调请求
 */
class PosActivateCallbackRequest extends CallbackRequest
{
    /**
     * @var string 商户编号
     */
    private $merchantNo;

    /**
     * @var string 商户名称
     */
    private $merchantName;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn;

    /**
     * @var string pos 状态
     * @see PosStatus
     */
    private $status;

    /**
     * @var string pos 激活时间
     */
    private $activateDateTime;
}