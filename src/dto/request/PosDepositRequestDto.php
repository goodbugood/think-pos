<?php declare(strict_types=1);

namespace think\pos\dto\request;

use shali\phpmate\util\Money;
use think\pos\dto\ProviderRequestTrait;
use think\pos\exception\MissingParameterException;

class PosDepositRequestDto
{
    use ProviderRequestTrait;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn = '';

    /**
     * @var Money pos 机押金
     */
    private $deposit;

    /**
     * @var string 押金套餐简码或内容
     */
    private $depositPackageCode = '';

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

    /**
     * @throws MissingParameterException
     */
    public function check(): void
    {
        if (empty($this->deviceSn)) {
            throw new MissingParameterException('设备序列号 deviceSn 不能为空');
        } elseif (is_null($this->deposit)) {
            throw new MissingParameterException('押金 deposit 不能为空');
        } elseif (empty($this->depositPackageCode)) {
            throw new MissingParameterException('押金套餐简码 depositPackageCode 不能为空');
        }
    }
}
