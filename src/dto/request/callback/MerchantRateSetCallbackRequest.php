<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\dto\RateTrait;
use think\pos\dto\request\CallbackRequest;
use think\pos\exception\ProviderGatewayException;
use think\pos\extend\Assert;

/**
 * 注意部分平台存在仅回调个别支付类型费率情况
 */
class MerchantRateSetCallbackRequest extends CallbackRequest
{
    use RateTrait;

    /**
     * @var string 商户号
     */
    private $merchantNo = '';

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    /**
     * @throws ProviderGatewayException
     */
    public function check(): void
    {
        Assert::notEmpty($this->merchantNo, '商户号 merchantNo 不能为空');
    }
}