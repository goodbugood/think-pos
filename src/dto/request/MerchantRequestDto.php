<?php declare(strict_types=1);

namespace think\pos\dto\request;

use shali\phpmate\util\Money;
use think\pos\dto\ProviderRequestTrait;
use think\pos\dto\RateTrait;

/**
 * 请求 pos 服务商接口的商户参数
 */
class MerchantRequestDto
{
    use ProviderRequestTrait;
    use RateTrait;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn;

    /**
     * @var string 商户号
     */
    private $merchantNo;

    /**
     * 刷卡后强制性提现到你卡里，所以提现手续费是必收取的，只不过我们默认不收取
     * @var Money|null 提现手续费
     */
    private $withdrawFee;

    public function getDeviceSn(): string
    {
        return $this->deviceSn;
    }

    public function setDeviceSn(string $deviceSn): void
    {
        $this->deviceSn = $deviceSn;
    }

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    /**
     * @return Money|null
     */
    public function getWithdrawFee(): ?Money
    {
        return $this->withdrawFee;
    }

    public function setWithdrawFee(?Money $withdrawFee): void
    {
        $this->withdrawFee = $withdrawFee;
    }
}