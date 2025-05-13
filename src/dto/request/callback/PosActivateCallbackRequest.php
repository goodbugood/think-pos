<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\constant\PosStatus;
use think\pos\dto\request\CallbackRequest;

/**
 * pos 终端激活回调请求
 */
class PosActivateCallbackRequest extends CallbackRequest
{
    /**
     * @var string 商户编号
     */
    private $merchantNo;

    /**
     * @var string 商户名称
     */
    private $merchantName;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn;

    /**
     * @var string pos 状态
     * @see PosStatus
     */
    private $status;

    /**
     * @var string pos 激活时间
     */
    private $activateDateTime;

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    public function getMerchantName(): string
    {
        return $this->merchantName;
    }

    public function setMerchantName(string $merchantName): void
    {
        $this->merchantName = $merchantName;
    }

    public function getDeviceSn(): string
    {
        return $this->deviceSn;
    }

    public function setDeviceSn(string $deviceSn): void
    {
        $this->deviceSn = $deviceSn;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getActivateDateTime(): string
    {
        return $this->activateDateTime;
    }

    public function setActivateDateTime(string $activateDateTime): void
    {
        $this->activateDateTime = $activateDateTime;
    }
}