<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\dto\request\CallbackRequest;

/**
 * 商户注册回调参数
 */
class MerchantRegisterCallbackRequest extends CallbackRequest
{
    // 代理信息
    /**
     * @var string 代理号
     */
    private $agentNo;

    // 商户信息
    /**
     * @var string 商户号
     */
    private $merchantNo;

    /**
     * @var string 注册时间
     */
    private $regDateTime;

    // todo shali [2025/5/7] 按照我们的商户需要维护的信息进行抽象
}