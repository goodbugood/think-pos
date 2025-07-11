<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\dto\ExtInfoTrait;
use think\pos\dto\MerchantTrait;
use think\pos\dto\request\CallbackRequest;
use think\pos\exception\ProviderGatewayException;
use think\pos\extend\Assert;

/**
 * 商户注册回调参数
 */
class MerchantRegisterCallbackRequest extends CallbackRequest
{
    use MerchantTrait;
    use ExtInfoTrait;

    // 代理信息
    /**
     * @var string 代理号
     */
    private $agentNo = '';

    public function getAgentNo(): string
    {
        return $this->agentNo;
    }

    public function setAgentNo(string $agentNo): void
    {
        $this->agentNo = $agentNo;
    }

    /**
     * 必须的字段检查
     * @return void
     * @throws ProviderGatewayException
     */
    public function check(): void
    {
        Assert::notEmpty($this->merchantNo, 'merchantNo 不能为空');
        Assert::notEmpty($this->idCardName, 'idCardName 不能为空');
        Assert::notEmpty($this->idCardNo, 'idCardNo 不能为空');
    }
}