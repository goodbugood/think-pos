<?php declare(strict_types=1);

namespace think\pos\dto\response;

use think\pos\dto\ResponseTrait;

/**
 * 具体的流量卡信息标准的格式是怎样的，还未确定
 */
class SimInfoResponse
{
    use ResponseTrait;

    /**
     * @var string 代理编号
     */
    private $agentNo = '';

    /**
     * @var string 商户编号
     */
    private $merchantNo = '';

    /**
     * @var string 终端编号
     */
    private $deviceSn = '';

    /**
     * pos 平台响应的原生数据
     * @deprecated 为了临时看套餐详情，后面正式使用，此字段考虑移除
     * @var string
     */
    private $body = '';

    public function getAgentNo(): string
    {
        return $this->agentNo;
    }

    public function setAgentNo(string $agentNo): void
    {
        $this->agentNo = $agentNo;
    }

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    public function getDeviceSn(): string
    {
        return $this->deviceSn;
    }

    public function setDeviceSn(string $deviceSn): void
    {
        $this->deviceSn = $deviceSn;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
