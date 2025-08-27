<?php declare(strict_types=1);

namespace think\pos\provider\lipos\convertor;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\AccountStatus;
use think\pos\constant\AccountType;
use think\pos\constant\SettleType;
use think\pos\constant\WithdrawStatus;
use think\pos\dto\response\AccountBalanceResponse;
use think\pos\dto\response\AccountInfo;
use think\pos\dto\response\WithdrawResponse;

final class WithdrawConvertor
{
    // 禁止 new
    private function __construct()
    {
    }

    /**
     * 转换账户余额查询响应
     * @param array $data
     * @return AccountBalanceResponse
     */
    public static function toAccountBalanceResponse(array $data): AccountBalanceResponse
    {
        $response = AccountBalanceResponse::success();
        
        if (empty($data) || !is_array($data)) {
            return $response;
        }

        foreach ($data as $item) {
            $accountInfo = new AccountInfo();
            $accountInfo->setAccountNo($item['accountNo'] ?? 'null');
            $accountInfo->setAccountType($item['accountType'] ?? AccountType::SHARE);
            $accountInfo->setStatus($item['status'] ?? AccountStatus::NORMAL);
            $accountInfo->setBalance(Money::valueOfYuan(strval($item['balance'] ?? 0)));
            $accountInfo->setTransitBalance(Money::valueOfYuan(strval($item['transitBalance'] ?? 0)));
            $accountInfo->setFreezeBalance(Money::valueOfYuan(strval($item['freezeBalance'] ?? 0)));
            $accountInfo->setAvailableBalance(Money::valueOfYuan(strval($item['availableBalance'] ?? 0)));
            $accountInfo->setIncomeBalance(Money::valueOfYuan(strval($item['incomeBalance'] ?? 0)));
            $accountInfo->setSpendBalance(Money::valueOfYuan(strval($item['spendBalance'] ?? 0)));
            
            $response->addAccount($accountInfo);
        }

        return $response;
    }

    /**
     * 转换代付申请响应
     * @param array $data
     * @return WithdrawResponse
     */
    public static function toWithdrawResponse(array $data): WithdrawResponse
    {
        $response = WithdrawResponse::success();
        
        $response->setOrderNo($data['orderNo'] ?? 'null');
        $response->setAmount(Money::valueOfYuan(strval($data['amount'] ?? 0)));
        $response->setWithdrawFeeTotal(Money::valueOfYuan(strval($data['withdrawFeeTotal'] ?? 0)));
        $response->setWithdrawFee(Money::valueOfYuan(strval($data['withdrawFee'] ?? 0)));
        $response->setTaxRate(Rate::valueOfPercentage(strval($data['taxRate'] ?? 0)));
        $response->setWithdrawTaxFee(Money::valueOfYuan(strval($data['withdrawTaxFee'] ?? 0)));
        $response->setDeductionAmount(Money::valueOfYuan(strval($data['deductionAmount'] ?? 0)));
        $response->setAccountNo($data['accountNo'] ?? 'null');
        $response->setBankName($data['bankName'] ?? 'null');

        return $response;
    }

    /**
     * 转换代付查询响应
     * @param array $data
     * @return WithdrawResponse
     */
    public static function toWithdrawQueryResponse(array $data): WithdrawResponse
    {
        $response = WithdrawResponse::success();
        
        $response->setOrderNo($data['orderNo'] ?? 'null');
        $response->setAmount(Money::valueOfYuan(strval($data['amount'] ?? 0)));
        $response->setWithdrawFeeTotal(Money::valueOfYuan(strval($data['withdrawFeeTotal'] ?? 0)));
        $response->setWithdrawFee(Money::valueOfYuan(strval($data['withdrawFee'] ?? 0)));
        $response->setTaxRate(Rate::valueOfPercentage(strval($data['taxRate'] ?? 0)));
        $response->setWithdrawTaxFee(Money::valueOfYuan(strval($data['withdrawTaxFee'] ?? 0)));
        $response->setDeductionAmount(Money::valueOfYuan(strval($data['deductionAmount'] ?? 0)));
        
        // 状态转换
        $status = $data['status'] ?? 'WAIT_AUDIT';
        $response->setStatus(self::convertWithdrawStatus($status));
        
        // 时间转换
        if (!empty($data['createTime'])) {
            $response->setCreateTime(LocalDateTime::valueOfString($data['createTime']));
        }
        if (!empty($data['successTime'])) {
            $response->setSuccessTime(LocalDateTime::valueOfString($data['successTime']));
        }
        
        // 结算信息
        $response->setSettleType($data['settleType'] ?? SettleType::TO_PUBLIC_UNINCORPORATED);
        $response->setAccountNo($data['accountNo'] ?? 'null');
        $response->setAccountName($data['accountName'] ?? 'null');
        $response->setBankName($data['bankName'] ?? 'null');
        $response->setBranchName($data['branchName'] ?? 'null');
        $response->setUnionCode($data['unionCode'] ?? 'null');
        $response->setPhoneNo($data['phoneNo'] ?? 'null');
        $response->setIdCard($data['idCard'] ?? 'null');
        $response->setAccountType($data['accountType'] ?? AccountType::SHARE);
        $response->setFailReason($data['failReason'] ?? 'null');

        return $response;
    }

    /**
     * 转换力pos提现状态到标准状态
     * @param string $liposStatus
     * @return string
     */
    private static function convertWithdrawStatus(string $liposStatus): string
    {
        switch ($liposStatus) {
            case 'INIT':
                return WithdrawStatus::INIT;
            case 'WAIT_AUDIT':
                return WithdrawStatus::WAIT_AUDIT;
            case 'WAIT_PAY':
                return WithdrawStatus::WAIT_PAY;
            case 'FAIL':
                return WithdrawStatus::FAIL;
            case 'SUCCESS':
                return WithdrawStatus::SUCCESS;
            default:
                return WithdrawStatus::REJECT;
        }
    }
}