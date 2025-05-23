<?php declare(strict_types=1);

namespace think\pos\dto\request;

use think\pos\dto\ProviderRequestTrait;
use think\pos\dto\RateTrait;

/**
 * 请求 pos 服务商接口的商户参数
 */
class MerchantRequestDto
{
    use ProviderRequestTrait;
    use RateTrait;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn = '';

    /**
     * @var string 商户号
     */
    private $merchantNo = '';

    public function getDeviceSn(): string
    {
        return $this->deviceSn;
    }

    public function setDeviceSn(string $deviceSn): void
    {
        $this->deviceSn = $deviceSn;
    }

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }
}