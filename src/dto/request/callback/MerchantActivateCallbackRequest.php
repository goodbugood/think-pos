<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use shali\phpmate\core\date\LocalDateTime;
use think\pos\dto\ExtInfoTrait;
use think\pos\dto\request\CallbackRequest;

class MerchantActivateCallbackRequest extends CallbackRequest
{
    /**
     * 商户激活扩展信息
     */
    use ExtInfoTrait;

    /**
     * @var string 商户编号
     */
    private $merchantNo = '';

    /**
     * @var string 机具编号
     */
    private $deviceSn = '';

    /**
     * @var LocalDateTime|null 激活时间
     */
    private $activateTime;

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    public function getDeviceSn(): string
    {
        return $this->deviceSn;
    }

    public function setDeviceSn(string $deviceSn): void
    {
        $this->deviceSn = $deviceSn;
    }

    public function getActivateTime(): ?LocalDateTime
    {
        return $this->activateTime;
    }

    public function setActivateTime(?LocalDateTime $activateTime): void
    {
        $this->activateTime = $activateTime;
    }
}
