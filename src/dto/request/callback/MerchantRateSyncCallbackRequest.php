<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\dto\request\CallbackRequest;

class MerchantRateSyncCallbackRequest extends CallbackRequest
{
    private $merchantNo = '';

    /**
     * 是否可同步商户费率
     * 商户费率同步准备工作是否准备完毕，是否可以同步
     */
    private $canSync = false;

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    public function canSync(): bool
    {
        return $this->canSync;
    }

    public function setCanSync(bool $canSync): void
    {
        $this->canSync = $canSync;
    }
}