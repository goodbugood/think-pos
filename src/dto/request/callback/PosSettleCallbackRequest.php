<?php declare(strict_types=1);

namespace think\pos\dto\request\callback;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use think\pos\constant\SettleType;
use think\pos\constant\TransOrderStatus;
use think\pos\dto\request\CallbackRequest;

/**
 * 结算通知请求
 */
class PosSettleCallbackRequest extends CallbackRequest
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
     * @var string 终端编号
     */
    private $deviceSn = '';

    /**
     * 结算类型
     * @var string
     * @uses \think\pos\constant\SettleType 参考此常量
     */
    private $settleType = SettleType::WITHDRAW_FEE;

    /**
     * @var string 结算单号
     */
    private $orderNo = '';

    /**
     * @var string 结算订单关联的交易订单号
     */
    private $transNo = '';

    /**
     * 结算订单状态
     * @var string
     * @uses \think\pos\constant\TransOrderStatus 参考此常量
     */
    private $status = TransOrderStatus::SUCCESS;

    /**
     * 结算时间
     * @var LocalDateTime|null
     */
    private $settleDateTime;

    /**
     * 结算金额
     * @var Money|null
     */
    private $amount;

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

    public function getSettleType(): string
    {
        return $this->settleType;
    }

    public function setSettleType(string $settleType): void
    {
        $this->settleType = $settleType;
    }

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): void
    {
        $this->orderNo = $orderNo;
    }

    public function getTransNo(): string
    {
        return $this->transNo;
    }

    public function setTransNo(string $transNo): void
    {
        $this->transNo = $transNo;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSettleDateTime(): ?LocalDateTime
    {
        return $this->settleDateTime;
    }

    public function setSettleDateTime(?LocalDateTime $settleDateTime): void
    {
        $this->settleDateTime = $settleDateTime;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function setAmount(?Money $amount): void
    {
        $this->amount = $amount;
    }

    public function check(): void
    {
    }
}
