<?php declare(strict_types=1);

namespace think\pos\provider\yilian\convertor;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
use think\pos\constant\PosStatus;
use think\pos\dto\request\callback\MerchantActivateCallbackRequest;
use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\provider\yilian\YiLianPosPlatform;

class MerchantConvertor
{
    public static function toMerchantRegisterCallbackRequest(array $decryptedData): MerchantRegisterCallbackRequest
    {
        $callbackRequest = MerchantRegisterCallbackRequest::success();
        $callbackRequest->setAgentNo($decryptedData['agentNo'] ?? StrUtil::NULL);
        // 商户
        $callbackRequest->setMerchantNo($decryptedData['merchantNo'] ?? StrUtil::NULL);
        $callbackRequest->setMerchantName($decryptedData['merchantName'] ?? StrUtil::NULL);
        $callbackRequest->setRegDateTime($decryptedData['bindTime'] ?? StrUtil::NULL);
        $callbackRequest->setBusinessName($callbackRequest->getMerchantName());
        // 法人
        $callbackRequest->setIdCardName($decryptedData['realName'] ?? StrUtil::NULL);
        $callbackRequest->setIdCardNo($decryptedData['idCardNo'] ?? StrUtil::NULL);
        $callbackRequest->setPhoneNo($decryptedData['merchantPhone'] ?? StrUtil::NULL);
        $callbackRequest->setStatus(MerchantStatus::ENABLED);
        // 需要对接方回传的扩展信息
        $callbackRequest->setExtInfo([
            'ratePolicy' => $decryptedData['policyName'] ?? StrUtil::NULL,
        ]);
        return $callbackRequest;
    }

    public static function toPosBindCallbackRequest(array $decryptedData): PosBindCallbackRequest
    {
        // 商户注册信息
        $registerCallbackRequest = self::toMerchantRegisterCallbackRequest($decryptedData);
        // 绑定信息
        $request = PosBindCallbackRequest::success();
        $request->setMerchantRegisterCallbackRequest($registerCallbackRequest);
        $request->setAgentNo($decryptedData['agentNo'] ?? StrUtil::NULL);
        $request->setMerchantNo($decryptedData['merchantNo'] ?? StrUtil::NULL);
        // 机具号
        $request->setDeviceSn($decryptedData['terminalId'] ?? StrUtil::NULL);
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
        $request->setMerchantNo($decryptedData['merchantNo'] ?? StrUtil::NULL);
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

    /**
     * 解析商户激活推送信息
     * @param array $data
     * @return MerchantActivateCallbackRequest
     */
    public static function toMerchantActivateCallbackRequest(array $data): MerchantActivateCallbackRequest
    {
        $request = MerchantActivateCallbackRequest::success();
        $request->setMerchantNo($data['merchantNo'] ?? StrUtil::NULL);
        $request->setDeviceSn($data['sn'] ?? StrUtil::NULL);
        // 需要对接方回传的扩展信息
        $request->setExtInfo([
            'ratePolicy' => $data['policyName'] ?? StrUtil::NULL,
        ]);
        return $request;
    }
}
