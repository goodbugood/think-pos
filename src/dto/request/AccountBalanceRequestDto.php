<?php declare(strict_types=1);

namespace think\pos\dto\request;

use think\pos\constant\AccountType;
use think\pos\dto\ProviderRequestTrait;

/**
 * 账户余额查询请求DTO
 */
class AccountBalanceRequestDto
{
    use ProviderRequestTrait;

    /**
     * @var string 账户类型
     * @see AccountType
     */
    private $accountType = '';

    public function getAccountType(): string
    {
        return $this->accountType;
    }

    public function setAccountType(string $accountType): void
    {
        $this->accountType = $accountType;
    }
}