<?php declare(strict_types=1);

namespace think\pos\provider\lipos\convertor;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;

final class MerchantConvertor
{
    public static function toMerchantRateSetCallbackRequest(array $decryptedData): MerchantRateSetCallbackRequest
    {
        $request = MerchantRateSetCallbackRequest::success();
        $request->setMerchantNo($decryptedData['customerNo'] ?? 'null');
        foreach ($decryptedData['rateNotifyList'] as $rateNotify) {
            $rate = $rateNotify['rateValue'] ?? 0;
            if ('ALIPAY' === $rateNotify['payTypeViewCode']) {
                $request->setAlipayRate(Rate::valueOfPercentage(strval($rate)));
            } elseif ('WECHAT' === $rateNotify['payTypeViewCode']) {
                $request->setWechatRate(Rate::valueOfPercentage(strval($rate)));
            } elseif ('POS_DC' === $rateNotify['payTypeViewCode']) {
                // 借记卡
                $request->setDebitCardRate(Rate::valueOfPercentage(strval($rate)));
                $request->setDebitCardCappingValue(Money::valueOfYuan(strval($rateNotify['cappingValue'] ?? 0)));
            } elseif ('POS_CC' === $rateNotify['payTypeViewCode']) {
                // 贷记卡
                $request->setCreditRate(Rate::valueOfPercentage(strval($rate)));
            }
        }

        return $request;
    }

    public static function toMerchantRegisterCallbackRequest(array $decryptedData): MerchantRegisterCallbackRequest
    {
        $request = MerchantRegisterCallbackRequest::success();
        $request->setAgentNo($decryptedData['customerInfoNotify']['agentNo'] ?? 'null');
        // 商户
        $request->setMerchantNo($decryptedData['customerInfoNotify']['customerNo'] ?? 'null');
        $request->setMerchantName($decryptedData['customerInfoNotify']['customerName'] ?? 'null');
        $request->setRegDateTime($decryptedData['customerInfoNotify']['createTime'] ?? 'null');
        // 法人
        $request->setIdCardName($decryptedData['customerInfoNotify']['idCardName'] ?? 'null');
        $request->setIdCardNo($decryptedData['customerInfoNotify']['idCardNo'] ?? 'null');
        $request->setIdCardExpireDate($decryptedData['customerInfoNotify']['idCardEffectiveEnd'] ?? 'null');
        $request->setPhoneNo($decryptedData['customerInfoNotify']['phoneNo'] ?? 'null');
        // 营业执照，上游 business 拼写错误
        $request->setBusinessName($decryptedData['customerInfoNotify']['bussinessName'] ?? 'null');
        // 结算卡
        $request->setBankAccountName($decryptedData['customerSettleCardNotify']['accountName'] ?? 'null');
        $request->setBankAccountNo($decryptedData['customerSettleCardNotify']['accountNo'] ?? 'null');
        if ('TRUE' === $decryptedData['customerInfoNotify']['status'] ?? 'null') {
            $request->setStatus(MerchantStatus::ENABLED);
        } else {
            $request->setStatus(MerchantStatus::DISABLED);
        }

        return $request;
    }

    // 禁止 new
    private function __construct()
    {
    }
}