<?php declare(strict_types=1);

namespace think\pos\provider\lipos\convertor;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;

final class MerchantConvertor
{
    public static function toMerchantRateSetCallbackRequest(array $decryptedData): MerchantRateSetCallbackRequest
    {
        $request = MerchantRateSetCallbackRequest::success();
        $request->setMerchantNo($decryptedData['customerNo']);
        foreach ($decryptedData['rateNotifyList'] as $rateNotify) {
            if ('ALIPAY' === $rateNotify['payTypeViewCode']) {
                $request->setAlipayRate(Rate::valueOfPercentage(strval($rateNotify['rateValue'])));
            } elseif ('WECHAT' === $rateNotify['payTypeViewCode']) {
                $request->setWechatRate(Rate::valueOfPercentage(strval($rateNotify['rateValue'])));
            } elseif ('POS_DC' === $rateNotify['payTypeViewCode']) {
                // 借记卡
                $request->setDebitCardRate(Rate::valueOfPercentage(strval($rateNotify['rateValue'])));
                $request->setDebitCardCappingValue(Money::valueOfYuan(strval($rateNotify['cappingValue'])));
            } elseif ('POS_CC' === $rateNotify['payTypeViewCode']) {
                // 贷记卡
                $request->setCreditRate(Rate::valueOfPercentage(strval($rateNotify['rateValue'])));
            }
        }

        return $request;
    }

    // 禁止 new
    private function __construct()
    {
    }
}