<?php declare(strict_types=1);

namespace think\pos\dto\response;

use shali\phpmate\util\Money;
use think\pos\dto\RateTrait;
use think\pos\dto\ResponseTrait;

class PosInfoResponse
{
    use ResponseTrait;
    use RateTrait;

    /**
     * @var null|Money 押金
     */
    private $deposit;

    /**
     * @var string 押金套餐简码
     */
    private $depositPackageCode = '';

    /**
     * @var string 设备号
     */
    private $deviceNo = '';

    /**
     * @var string sim 流量卡套餐简码/内容
     */
    private $simPackageCode = '';

    /**
     * @var string 流量卡套餐描述
     */
    private $simPackageDesc = '';

    /**
     * @var boolean 是否开了 vip 会员
     */
    private $isVip;

    public function getDeposit(): ?Money
    {
        return $this->deposit;
    }

    public function setDeposit(?Money $deposit): void
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

    public function getDeviceNo(): string
    {
        return $this->deviceNo;
    }

    public function setDeviceNo(string $deviceNo): void
    {
        $this->deviceNo = $deviceNo;
    }

    public function getSimPackageCode(): string
    {
        return $this->simPackageCode;
    }

    public function setSimPackageCode(string $simPackageCode): void
    {
        $this->simPackageCode = $simPackageCode;
    }

    public function getSimPackageDesc(): string
    {
        return $this->simPackageDesc;
    }

    public function setSimPackageDesc(string $simPackageDesc): void
    {
        $this->simPackageDesc = $simPackageDesc;
    }

    public function isVip(): bool
    {
        return $this->isVip;
    }

    public function setIsVip(bool $isVip): void
    {
        $this->isVip = $isVip;
    }
}
