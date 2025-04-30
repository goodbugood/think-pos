<?php declare(strict_types=1);

namespace think\pos\dto\request;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
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
     * @var Rate 贷记卡刷卡交易费率
     */
    private $creditRate;

    /**
     * 刷卡后强制性提现到你卡里，所以提现手续费是必收取的，只不过我们默认不收取
     * @var Money 提现手续费
     */
    private $withdrawFee;

    /**
     * @var Money pos 机押金
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

    public function getCreditRate(): Rate
    {
        return $this->creditRate;
    }

    public function setCreditRate(Rate $creditRate): void
    {
        $this->creditRate = $creditRate;
    }

    public function getWithdrawFee(): Money
    {
        return $this->withdrawFee;
    }

    public function setWithdrawFee(Money $withdrawFee): void
    {
        $this->withdrawFee = $withdrawFee;
    }

    public function getDeposit(): Money
    {
        return $this->deposit;
    }

    public function setDeposit(Money $deposit): void
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