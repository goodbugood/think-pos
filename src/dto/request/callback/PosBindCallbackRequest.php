<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use shali\phpmate\core\date\LocalDateTime;
use think\pos\constant\PosStatus;
use think\pos\dto\request\CallbackRequest;

class PosBindCallbackRequest extends CallbackRequest
{
    /**
     * 由于有的平台没有商户注册回调，仅有绑定回调，绑定回调的同时携带商户的一些注册信息
     * @var MerchantRegisterCallbackRequest
     */
    private $merchantRegisterCallbackRequest;

    /**
     * 代理编号
     * @var string
     */
    private $agentNo = '';

    /**
     * 商户编号
     * @var string
     */
    private $merchantNo = '';

    /**
     * [C] 旧设备号，换绑时才会存在此字段，终端执行换绑时，会通知代理平台进行解绑旧设备操作
     * @var string
     */
    private $oldDeviceSn = '';

    /**
     * 终端编号
     * @var string
     */
    private $deviceSn = '';

    /**
     * @var string 状态
     * @see PosStatus
     */
    private $status = '';

    /**
     * 状态变更时间
     * @var LocalDateTime|null 格式 YYYY-MM-DD HH:mm:ss
     */
    private $modifyTime;

    public function getAgentNo(): string
    {
        return $this->agentNo;
    }

    public function setAgentNo(string $agentNo): void
    {
        $this->agentNo = $agentNo;
    }

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

    public function getOldDeviceSn(): string
    {
        return $this->oldDeviceSn;
    }

    public function setOldDeviceSn(string $oldDeviceSn): void
    {
        $this->oldDeviceSn = $oldDeviceSn;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getModifyTime(): ?LocalDateTime
    {
        return $this->modifyTime;
    }

    public function setModifyTime(?LocalDateTime $modifyTime): void
    {
        $this->modifyTime = $modifyTime;
    }

    public function getMerchantRegisterCallbackRequest(): ?MerchantRegisterCallbackRequest
    {
        return $this->merchantRegisterCallbackRequest;
    }

    public function setMerchantRegisterCallbackRequest(MerchantRegisterCallbackRequest $merchantRegisterCallbackRequest): void
    {
        $this->merchantRegisterCallbackRequest = $merchantRegisterCallbackRequest;
    }
}