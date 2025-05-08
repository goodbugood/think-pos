<?php declare(strict_types=1);

namespace think\pos\dto\request;

/**
 * pos 回调请求参数
 */
class PosCallbackRequest
{
    /**
     * @var string 商户号
     */
    private $merchantNo;

    /**
     * @var string pos 设备序列号
     */
    private $posSn;

    /**
     * @var string pos 绑定状态，例如解绑，绑定
     */
    private $bindStatus;

    /**
     * @var string 代理号
     */
    private $agentNo;
}