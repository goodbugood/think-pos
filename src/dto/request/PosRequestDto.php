<?php declare(strict_types=1);

namespace think\pos\dto\request;

use think\pos\dto\ProviderRequestTrait;

/**
 * 请求 pos 服务商的 pos 参数
 */
class PosRequestDto
{
    use ProviderRequestTrait;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn;

    /**
     * @var string 贷记卡刷卡交易费率，单位是 %，如 0.5%，则传 0.005
     * @example 0.005
     */
    private $creditRate;

    /**
     * 刷卡后强制性提现到你卡里，所以提现手续费是必收取的，只不过我们默认不收取
     * @var string 提现手续费，单位是元角分的分，如 0.5元，则传 50
     */
    private $withdrawFee;

    /**
     * @var string pos 机押金，单位是元角分的分，如 0.5元，则传 50
     */
    private $deposit;

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

    public function getCreditRate(): string
    {
        return $this->creditRate;
    }

    public function setCreditRate(string $creditRate): void
    {
        $this->creditRate = $creditRate;
    }

    public function getWithdrawFee(): string
    {
        return $this->withdrawFee;
    }

    public function setWithdrawFee(string $withdrawFee): void
    {
        $this->withdrawFee = $withdrawFee;
    }

    public function getDeposit(): string
    {
        return $this->deposit;
    }

    public function setDeposit(string $deposit): void
    {
        $this->deposit = $deposit;
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