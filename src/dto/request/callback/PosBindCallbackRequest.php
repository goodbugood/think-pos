<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use think\pos\constant\PosStatus;
use think\pos\dto\request\CallbackRequest;

class PosBindCallbackRequest extends CallbackRequest
{
    /**
     * 代理编号
     */
    private $agentNo;

    /**
     * 商户编号
     */
    private $merchantNo;

    /**
     * 终端编号
     */
    private $deviceSn;

    /**
     * @var string 状态
     * @see PosStatus
     */
    private $status;

    /**
     * @return mixed
     */
    public function getAgentNo()
    {
        return $this->agentNo;
    }

    /**
     * @param mixed $agentNo
     */
    public function setAgentNo($agentNo): void
    {
        $this->agentNo = $agentNo;
    }

    /**
     * @return mixed
     */
    public function getMerchantNo()
    {
        return $this->merchantNo;
    }

    /**
     * @param mixed $merchantNo
     */
    public function setMerchantNo($merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    /**
     * @return mixed
     */
    public function getDeviceSn()
    {
        return $this->deviceSn;
    }

    /**
     * @param mixed $deviceSn
     */
    public function setDeviceSn($deviceSn): void
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
}