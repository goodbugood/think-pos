<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\dto\MerchantTrait;
use think\pos\dto\request\CallbackRequest;

/**
 * 商户注册回调参数
 */
class MerchantRegisterCallbackRequest extends CallbackRequest
{
    use MerchantTrait;

    // 代理信息
    /**
     * @var string 代理号
     */
    private $agentNo;

    public function getAgentNo(): string
    {
        return $this->agentNo;
    }

    public function setAgentNo(string $agentNo): void
    {
        $this->agentNo = $agentNo;
    }
}