<?php declare(strict_types=1);

namespace think\pos\dto\request;

use shali\phpmate\util\Money;
use think\pos\constant\AccountType;
use think\pos\constant\SettleType;
use think\pos\dto\ProviderRequestTrait;

/**
 * 代付申请请求DTO
 */
class WithdrawRequestDto
{
    use ProviderRequestTrait;

    /**
     * @var string 提现订单编号
     */
    private $orderNo = '';

    /**
     * @var Money|null 提现金额
     */
    private $amount;

    /**
     * @var string 账户类型
     * @see AccountType
     */
    private $accountType = '';

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
     * @var string 结算银行总行名称
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
     * @var Money|null 报税金额
     */
    private $entrustAmount;

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

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): void
    {
        $this->accountType = $accountType;
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

    public function getEntrustAmount(): ?Money
    {
        return $this->entrustAmount;
    }

    public function setEntrustAmount(?Money $entrustAmount): void
    {
        $this->entrustAmount = $entrustAmount;
    }
}