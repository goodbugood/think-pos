<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\dto\RateTrait;
use think\pos\dto\request\CallbackRequest;

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
}