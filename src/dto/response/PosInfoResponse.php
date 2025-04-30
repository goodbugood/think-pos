<?php declare(strict_types=1);

namespace think\pos\dto\response;

use think\pos\dto\ResponseTrait;

class PosInfoResponse
{
    use ResponseTrait;

    /**
     * @var int 押金，单位为分，0 表示无押金
     */
    private $deposit;

    /**
     * @var string 设备号
     */
    private $deviceNo;

    /**
     * @var string sim 套餐简码
     */
    private $simPackageCode;

    /**
     * @var string 贷记卡费率，单位是小数不是百分数，0.01 表示 1%
     */
    private $creditRate;

    /**
     * @var int 提现手续费，单位为分，0 表示免手续费
     */
    private $withdrawFee;

    /**
     * @var boolean 是否开了 vip 会员
     */
    private $isVip;

    public function getDeposit(): int
    {
        return $this->deposit;
    }

    public function setDeposit(int $deposit): void
    {
        $this->deposit = $deposit;
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

    public function getCreditRate(): string
    {
        return $this->creditRate;
    }

    public function setCreditRate(string $creditRate): void
    {
        $this->creditRate = $creditRate;
    }

    public function getWithdrawFee(): int
    {
        return $this->withdrawFee;
    }

    public function setWithdrawFee(int $withdrawFee): void
    {
        $this->withdrawFee = $withdrawFee;
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
