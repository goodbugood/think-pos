<?php declare(strict_types=1);

namespace think\pos\dto\request;

use think\pos\dto\ProviderRequestTrait;

/**
 * sim卡套餐请求参数
 */
class SimRequestDto
{
    use ProviderRequestTrait;

    /**
     * 对 pos 机具进行流量卡套餐设置的平台：
     * 1. 力 POS
     * @var string 设备号
     */
    public $deviceSn = '';

    /**
     * 对商户进行流量卡套餐设置的平台：
     * 1. 移联POS平台
     * @var string 商户号，某些平台是对商户号进行流量卡套餐设置
     */
    private $merchantNo = '';

    /**
     * @var string 流量卡套餐码
     */
    private $simPackageCode = '';

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

    public function getSimPackageCode(): string
    {
        return $this->simPackageCode;
    }

    public function setSimPackageCode(string $simPackageCode): void
    {
        $this->simPackageCode = $simPackageCode;
    }
}
