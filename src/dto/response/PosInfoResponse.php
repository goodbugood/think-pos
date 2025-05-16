<?php declare(strict_types=1);

namespace think\pos\dto\response;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\dto\ResponseTrait;

class PosInfoResponse
{
    use ResponseTrait;

    /**
     * @var null|Money 押金
     */
    private $deposit;

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
     * @var null|Rate 贷记卡费率
     */
    private $creditRate;

    /**
     * @var null|Money 提现手续费
     */
    private $withdrawFee;

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

    public function getCreditRate(): ?Rate
    {
        return $this->creditRate;
    }

    public function setCreditRate(?Rate $creditRate): void
    {
        $this->creditRate = $creditRate;
    }

    public function getWithdrawFee(): ?Money
    {
        return $this->withdrawFee;
    }

    public function setWithdrawFee(?Money $withdrawFee): void
    {
        $this->withdrawFee = $withdrawFee;
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
