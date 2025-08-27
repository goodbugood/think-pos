<?php declare(strict_types=1);

namespace think\pos\dto\response;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\AccountType;
use think\pos\constant\SettleType;
use think\pos\constant\WithdrawStatus;
use think\pos\dto\ResponseTrait;

/**
 * 代付申请/查询响应
 */
class WithdrawResponse
{
    use ResponseTrait;

    /**
     * @var string 提现订单编号
     */
    private $orderNo = '';

    /**
     * @var Money|null 提现金额
     */
    private $amount;

    /**
     * @var Money|null 提现总手续费
     */
    private $withdrawFeeTotal;

    /**
     * @var Money|null 单笔提现手续费
     */
    private $withdrawFee;

    /**
     * @var Rate|null 提现税率
     */
    private $taxRate;

    /**
     * @var Money|null 提现税费
     */
    private $withdrawTaxFee;

    /**
     * @var Money|null 账户扣款金额
     */
    private $deductionAmount;

    /**
     * @var string 提现状态
     * @see WithdrawStatus
     */
    private $status = '';

    /**
     * @var LocalDateTime|null 提现时间
     */
    private $createTime;

    /**
     * @var LocalDateTime|null 提现成功时间
     */
    private $successTime;

    /**
     * @var string 结算方式
     * @see SettleType
     */
    private $settleType = '';

    /**
     * @var string 结算账户
     */
    private $accountNo = '';

    /**
     * @var string 结算账户名称
     */
    private $accountName = '';

    /**
     * @var string 结算银行名称
     */
    private $bankName = '';

    /**
     * @var string 结算账户支行名称
     */
    private $branchName = '';

    /**
     * @var string 结算账户支行编码
     */
    private $unionCode = '';

    /**
     * @var string 结算账户手机号
     */
    private $phoneNo = '';

    /**
     * @var string 结算账户身份证号
     */
    private $idCard = '';

    /**
     * @var string 账户类型
     * @see AccountType
     */
    private $accountType = '';

    /**
     * @var string 失败原因
     */
    private $failReason = '';

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): void
    {
        $this->orderNo = $orderNo;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function setAmount(?Money $amount): void
    {
        $this->amount = $amount;
    }

    public function getWithdrawFeeTotal(): ?Money
    {
        return $this->withdrawFeeTotal;
    }

    public function setWithdrawFeeTotal(?Money $withdrawFeeTotal): void
    {
        $this->withdrawFeeTotal = $withdrawFeeTotal;
    }

    public function getWithdrawFee(): ?Money
    {
        return $this->withdrawFee;
    }

    public function setWithdrawFee(?Money $withdrawFee): void
    {
        $this->withdrawFee = $withdrawFee;
    }

    public function getTaxRate(): ?Rate
    {
        return $this->taxRate;
    }

    public function setTaxRate(?Rate $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function getWithdrawTaxFee(): ?Money
    {
        return $this->withdrawTaxFee;
    }

    public function setWithdrawTaxFee(?Money $withdrawTaxFee): void
    {
        $this->withdrawTaxFee = $withdrawTaxFee;
    }

    public function getDeductionAmount(): ?Money
    {
        return $this->deductionAmount;
    }

    public function setDeductionAmount(?Money $deductionAmount): void
    {
        $this->deductionAmount = $deductionAmount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreateTime(): ?LocalDateTime
    {
        return $this->createTime;
    }

    public function setCreateTime(?LocalDateTime $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getSuccessTime(): ?LocalDateTime
    {
        return $this->successTime;
    }

    public function setSuccessTime(?LocalDateTime $successTime): void
    {
        $this->successTime = $successTime;
    }

    public function getSettleType(): string
    {
        return $this->settleType;
    }

    public function setSettleType(string $settleType): void
    {
        $this->settleType = $settleType;
    }

    public function getAccountNo(): string
    {
        return $this->accountNo;
    }

    public function setAccountNo(string $accountNo): void
    {
        $this->accountNo = $accountNo;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function setAccountName(string $accountName): void
    {
        $this->accountName = $accountName;
    }

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function setBankName(string $bankName): void
    {
        $this->bankName = $bankName;
    }

    public function getBranchName(): string
    {
        return $this->branchName;
    }

    public function setBranchName(string $branchName): void
    {
        $this->branchName = $branchName;
    }

    public function getUnionCode(): string
    {
        return $this->unionCode;
    }

    public function setUnionCode(string $unionCode): void
    {
        $this->unionCode = $unionCode;
    }

    public function getPhoneNo(): string
    {
        return $this->phoneNo;
    }

    public function setPhoneNo(string $phoneNo): void
    {
        $this->phoneNo = $phoneNo;
    }

    public function getIdCard(): string
    {
        return $this->idCard;
    }

    public function setIdCard(string $idCard): void
    {
        $this->idCard = $idCard;
    }

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): void
    {
        $this->accountType = $accountType;
    }

    public function getFailReason(): string
    {
        return $this->failReason;
    }

    public function setFailReason(string $failReason): void
    {
        $this->failReason = $failReason;
    }
}