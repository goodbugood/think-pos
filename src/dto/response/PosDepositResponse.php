<?php declare(strict_types=1);

namespace think\pos\dto\response;

use shali\phpmate\util\Money;
use think\pos\dto\ResponseTrait;

class PosDepositResponse
{
    use ResponseTrait;

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
}
