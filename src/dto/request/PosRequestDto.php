<?php declare(strict_types=1);

namespace think\pos\dto\request;

use shali\phpmate\util\Money;
use think\pos\dto\ProviderRequestTrait;
use think\pos\dto\RateTrait;

/**
 * 请求 pos 服务商的 pos 参数
 */
class PosRequestDto
{
    use ProviderRequestTrait;
    use RateTrait;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn = '';

    /**
     * @var Money pos 机押金
     */
    private $deposit;

    /**
     * @var string 押金套餐简码
     */
    private $depositPackageCode = '';

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

    public function getDeposit(): Money
    {
        return $this->deposit;
    }

    public function setDeposit(Money $deposit): void
    {
        $this->deposit = $deposit;
    }

    public function getDepositPackageCode(): string
    {
        return $this->depositPackageCode;
    }

    public function setDepositPackageCode(string $depositPackageCode): void
    {
        $this->depositPackageCode = $depositPackageCode;
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