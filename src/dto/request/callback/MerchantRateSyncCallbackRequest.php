<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\dto\request\CallbackRequest;

class MerchantRateSyncCallbackRequest extends CallbackRequest
{
    private $merchantNo = '';

    /**
     * 是否可同步商户费率
     */
    private $isSync = false;

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    public function isSync(): bool
    {
        return $this->isSync;
    }

    public function setIsSync(bool $isSync): void
    {
        $this->isSync = $isSync;
    }
}