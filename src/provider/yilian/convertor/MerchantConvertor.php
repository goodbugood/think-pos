<?php declare(strict_types=1);

namespace think\pos\provider\yilian\convertor;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
use think\pos\constant\PosStatus;
use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\provider\yilian\YiLianPosPlatform;

class MerchantConvertor
{
    public static function toMerchantRegisterCallbackRequest(array $decryptedData): MerchantRegisterCallbackRequest
    {
        $callbackRequest = MerchantRegisterCallbackRequest::success();
        $callbackRequest->setAgentNo($decryptedData['agentNo'] ?? 'null');
        // 商户
        $callbackRequest->setMerchantNo($decryptedData['merchantNo'] ?? 'null');
        $callbackRequest->setMerchantName($decryptedData['merchantName'] ?? 'null');
        $callbackRequest->setRegDateTime($decryptedData['bindTime'] ?? 'null');
        // 法人
        $callbackRequest->setIdCardName($decryptedData['realName'] ?? 'null');
        $callbackRequest->setIdCardNo($decryptedData['idCardNo'] ?? 'null');
        $callbackRequest->setPhoneNo($decryptedData['merchantPhone'] ?? 'null');
        $callbackRequest->setStatus(MerchantStatus::ENABLED);
        return $callbackRequest;
    }

    public static function toPosBindCallbackRequest(array $decryptedData): PosBindCallbackRequest
    {
        $request = PosBindCallbackRequest::success();
        $request->setAgentNo($decryptedData['agentNo'] ?? 'null');
        $request->setMerchantNo($decryptedData['merchantNo'] ?? 'null');
        // 机具号
        $request->setDeviceSn($decryptedData['terminalId'] ?? 'null');
        // 回调即绑定成功
        $request->setStatus(PosStatus::BIND_SUCCESS);
        // 状态变更时间
        if (empty($decryptedData['bindTime'])) {
            $request->setModifyTime(LocalDateTime::now());
        } else {
            $request->setModifyTime(LocalDateTime::valueOfString($decryptedData['bindTime']));
        }
        return $request;
    }

    /**
     * 移联反馈，商户费率修改通知，仅通知扫码费率的变更，刷卡费率的变更不会通知
     * @param array $decryptedData
     * @return MerchantRateSetCallbackRequest
     */
    public static function toMerchantRateSetCallbackRequest(array $decryptedData): MerchantRateSetCallbackRequest
    {
        $request = MerchantRateSetCallbackRequest::success();
        $request->setMerchantNo($decryptedData['merchantNo'] ?? 'null');
        foreach ($decryptedData['feeRateList'] as $rateNotify) {
            // 移联不会通知刷卡费率的变更
            if (!YiLianPosPlatform::isBankCardType($rateNotify['payTypeViewCode'])) {
                $rate = Rate::valueOfPercentage(strval($rateNotify['rateValue']));
                $request->setWechatRate($rate);
                $request->setAlipayRate($rate);
                $request->setWithdrawFee(Money::valueOfYuan(strval($rateNotify['withdrawRate'] ?? 0)));
                break;
            }
        }

        return $request;
    }
}
