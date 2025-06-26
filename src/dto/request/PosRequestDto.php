<?php declare(strict_types=1);

namespace think\pos\dto\request;

use shali\phpmate\util\Money;
use think\pos\dto\ProviderRequestTrait;
use think\pos\dto\RateTrait;
use think\pos\exception\MissingParameterException;

/**
 * // todo shali [2025/6/26] 这个 dto 本来是给 pos 初始化使用的，涉及通讯费，服务费，交易费率，但是目前也被用于服务费设置，3.0 的升级中，这个类就不应该被用于服务费设置了
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

    /**
     * @throws MissingParameterException
     */
    public function checkDeposit(): void
    {
        if (empty($this->deviceSn)) {
            throw new MissingParameterException('设备序列号 deviceSn 不能为空');
        } elseif (empty($this->deposit)) {
            throw new MissingParameterException('押金 deposit 不能为空');
        } elseif (empty($this->depositPackageCode)) {
            throw new MissingParameterException('押金套餐简码 depositPackageCode 不能为空');
        }
    }
}