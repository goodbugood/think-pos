<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use shali\phpmate\core\date\LocalDateTime;
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
    private $agentNo = '';

    /**
     * @var string 商户编号
     */
    private $merchantNo = '';

    /**
     * @var string 商户名称
     */
    private $merchantName = '';

    /**
     * @var string 终端编号
     */
    private $deviceSn = '';

    /**
     * @var string 交易流水号
     */
    private $transNo = '';

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
     * 交易手续费是否达到封顶金额，目前仅有借记卡交易才会存在封顶交易手续费
     * @var bool true:达到封顶金额
     */
    private $isFeeCapping = false;

    /**
     * @var Money|null 提现手续费：你刷完卡，只是扣了交易手续费，钱到你账户，还要扣这个提现手续费
     */
    private $withdrawFee;

    /**
     * @var LocalDateTime|null 交易成功时间
     */
    private $successDateTime;

    /**
     * @var string 交易状态
     * @see TransOrderStatus
     */
    private $status = '';

    /**
     * @var string 订单类型
     * @see TransOrderType
     */
    private $orderType = '';

    /**
     * 有的服务商会将多个订单放在一个回调中推送
     * 第二个订单类型
     * @var string
     * @see TransOrderType
     */
    private $secondOrderType = '';

    /**
     * @var string 第二个交易流水号，通常是止付订单号，例如多个订单一起下发（交易和流量卡订单）
     */
    private $secondTransNo = '';

    /**
     * 附加的第二个订单金额
     * @var Money|null
     */
    private $secondOrderAmount;

    /**
     * @var string 支付方式，这是支付方式不是支付渠道
     * @see PaymentType
     */
    private $paymentType = '';

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

    public function isFeeCapping(): bool
    {
        return $this->isFeeCapping;
    }

    public function setIsFeeCapping(bool $isFeeCapping): void
    {
        $this->isFeeCapping = $isFeeCapping;
    }

    public function getWithdrawFee(): ?Money
    {
        return $this->withdrawFee;
    }

    public function setWithdrawFee(?Money $withdrawFee): void
    {
        $this->withdrawFee = $withdrawFee;
    }

    public function getSuccessDateTime(): ?LocalDateTime
    {
        return $this->successDateTime;
    }

    public function setSuccessDateTime(?LocalDateTime $successDateTime): void
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

    public function getSecondOrderType(): string
    {
        return $this->secondOrderType;
    }

    public function setSecondOrderType(string $secondOrderType): void
    {
        $this->secondOrderType = $secondOrderType;
    }

    public function getSecondTransNo(): string
    {
        return $this->secondTransNo;
    }

    public function setSecondTransNo(string $secondTransNo): void
    {
        $this->secondTransNo = $secondTransNo;
    }

    public function getSecondOrderAmount(): ?Money
    {
        return $this->secondOrderAmount;
    }

    public function setSecondOrderAmount(?Money $secondOrderAmount): void
    {
        $this->secondOrderAmount = $secondOrderAmount;
    }
}