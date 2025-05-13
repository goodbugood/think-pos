<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\PaymentType;
use think\pos\constant\TransOrderStatus;
use think\pos\constant\TransOrderType;
use think\pos\dto\request\CallbackRequest;

class PosTransCallbackRequest extends CallbackRequest
{
    /**
     * @var string 代理编号
     */
    private $agentNo;

    /**
     * @var string 商户编号
     */
    private $merchantNo;

    /**
     * @var string 商户名称
     */
    private $merchantName;

    /**
     * @var string 终端编号
     */
    private $deviceSn;

    /**
     * @var string 交易流水号
     */
    private $transNo;

    /**
     * @var Money|null 交易金额
     */
    private $amount;

    /**
     * @var Money|null 结算金额
     */
    private $settleAmount;

    /**
     * @var Rate|null 交易费率
     */
    private $rate;

    /**
     * @var Money|null 交易手续费
     */
    private $fee;

    /**
     * @var string 交易成功时间
     */
    private $successDateTime;

    /**
     * @var string 交易状态
     * @see TransOrderStatus
     */
    private $status;

    /**
     * @var string 订单类型
     * @see TransOrderType
     */
    private $orderType;

    /**
     * @var string 支付方式，这是支付方式不是支付渠道
     * @see PaymentType
     */
    private $paymentType;

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

    public function getTransNo(): string
    {
        return $this->transNo;
    }

    public function setTransNo(string $transNo): void
    {
        $this->transNo = $transNo;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function setAmount(?Money $amount): void
    {
        $this->amount = $amount;
    }

    public function getSettleAmount(): ?Money
    {
        return $this->settleAmount;
    }

    public function setSettleAmount(?Money $settleAmount): void
    {
        $this->settleAmount = $settleAmount;
    }

    public function getRate(): ?Rate
    {
        return $this->rate;
    }

    public function setRate(?Rate $rate): void
    {
        $this->rate = $rate;
    }

    public function getFee(): ?Money
    {
        return $this->fee;
    }

    public function setFee(?Money $fee): void
    {
        $this->fee = $fee;
    }

    public function getSuccessDateTime(): string
    {
        return $this->successDateTime;
    }

    public function setSuccessDateTime(string $successDateTime): void
    {
        $this->successDateTime = $successDateTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getOrderType(): string
    {
        return $this->orderType;
    }

    public function setOrderType(string $orderType): void
    {
        $this->orderType = $orderType;
    }

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): void
    {
        $this->paymentType = $paymentType;
    }
}