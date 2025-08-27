<?php declare(strict_types=1);

namespace think\pos\dto\response;

use shali\phpmate\util\Money;
use think\pos\constant\AccountStatus;
use think\pos\constant\AccountType;
use think\pos\dto\ResponseTrait;

/**
 * 账户余额响应
 */
class AccountBalanceResponse
{
    use ResponseTrait;

    /**
     * @var AccountInfo[] 账户信息列表
     */
    private $accounts = [];

    public function getAccounts(): array
    {
        return $this->accounts;
    }

    public function setAccounts(array $accounts): void
    {
        $this->accounts = $accounts;
    }

    public function addAccount(AccountInfo $account): void
    {
        $this->accounts[] = $account;
    }
}

/**
 * 账户信息
 */
class AccountInfo
{
    /**
     * @var string 账户编号
     */
    private $accountNo = '';

    /**
     * @var string 账户类型
     * @see AccountType
     */
    private $accountType = '';

    /**
     * @var string 账户状态
     * @see AccountStatus
     */
    private $status = '';

    /**
     * @var Money|null 总余额
     */
    private $balance;

    /**
     * @var Money|null 在途金额
     */
    private $transitBalance;

    /**
     * @var Money|null 冻结金额
     */
    private $freezeBalance;

    /**
     * @var Money|null 可用金额
     */
    private $availableBalance;

    /**
     * @var Money|null 累计收入金额
     */
    private $incomeBalance;

    /**
     * @var Money|null 累计支出金额
     */
    private $spendBalance;

    public function getAccountNo(): string
    {
        return $this->accountNo;
    }

    public function setAccountNo(string $accountNo): void
    {
        $this->accountNo = $accountNo;
    }

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): void
    {
        $this->accountType = $accountType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getBalance(): ?Money
    {
        return $this->balance;
    }

    public function setBalance(?Money $balance): void
    {
        $this->balance = $balance;
    }

    public function getTransitBalance(): ?Money
    {
        return $this->transitBalance;
    }

    public function setTransitBalance(?Money $transitBalance): void
    {
        $this->transitBalance = $transitBalance;
    }

    public function getFreezeBalance(): ?Money
    {
        return $this->freezeBalance;
    }

    public function setFreezeBalance(?Money $freezeBalance): void
    {
        $this->freezeBalance = $freezeBalance;
    }

    public function getAvailableBalance(): ?Money
    {
        return $this->availableBalance;
    }

    public function setAvailableBalance(?Money $availableBalance): void
    {
        $this->availableBalance = $availableBalance;
    }

    public function getIncomeBalance(): ?Money
    {
        return $this->incomeBalance;
    }

    public function setIncomeBalance(?Money $incomeBalance): void
    {
        $this->incomeBalance = $incomeBalance;
    }

    public function getSpendBalance(): ?Money
    {
        return $this->spendBalance;
    }

    public function setSpendBalance(?Money $spendBalance): void
    {
        $this->spendBalance = $spendBalance;
    }
}