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
     * @var string 设备号
     */
    public $deviceSn;

    /**
     * @var string 流量卡套餐码
     */
    private $simPackageCode;

    public function getDeviceSn(): string
    {
        return $this->deviceSn;
    }

    public function setDeviceSn(string $deviceSn): void
    {
        $this->deviceSn = $deviceSn;
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
