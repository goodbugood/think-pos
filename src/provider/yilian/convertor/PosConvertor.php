<?php declare(strict_types=1);

namespace think\pos\provider\yilian\convertor;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\TransOrderStatus;
use think\pos\constant\TransOrderType;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\provider\yilian\YiLianPosPlatform;

final class PosConvertor
{
    public static function toPosTransCallbackRequest(array $decryptedData): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setAgentNo(strval($decryptedData['agentNo'] ?? StrUtil::NULL));
        $request->setMerchantNo(strval($decryptedData['merchantNo'] ?? StrUtil::NULL));
        $request->setMerchantName(strval($decryptedData['merchantName'] ?? StrUtil::NULL));
        $request->setDeviceSn(strval($decryptedData['terminalId'] ?? StrUtil::NULL));
        $request->setTransNo(strval($decryptedData['transOrderNo'] ?? StrUtil::NULL));
        $request->setAmount(Money::valueOfYuan(strval($decryptedData['transAmount'] ?? 0)));
        $request->setRate(Rate::valueOfDecimal(strval($decryptedData['transRate'] ?? 0)));
        $request->setFee(Money::valueOfYuan(strval($decryptedData['transFee'] ?? 0)));
        // 手续费是否封顶，0 否 1 是
        $request->setIsFeeCapping(1 === ($decryptedData['feeTop'] ?? ''));
        if (empty($decryptedData['transTime'])) {
            $request->setSuccessDateTime(LocalDateTime::now());
        } else {
            $request->setSuccessDateTime(LocalDateTime::valueOfString($decryptedData['transTime']));
        }
        // 解析订单类型
        if ('1' === ($decryptedData['activityTransFlag'] ?? StrUtil::NULL)) {
            // 是否收取服务费标识
            $request->setOrderType(TransOrderType::DEPOSIT);
            // 回调即成功，失败订单不会回调
        } elseif ('1' === ($decryptedData['flowFeeFlag'] ?? StrUtil::NULL)) {
            $request->setOrderType(TransOrderType::NORMAL);
            // 流量卡订单默认就是已经交易成功，顶多再推送 1 次流量卡支付订单通知
            $request->setSecondOrderType(TransOrderType::SIM);
            $request->setSecondTransNo($request->getTransNo());
            $request->setSecondOrderAmount(Money::valueOfYuan(strval($decryptedData['vasFlowFee'] ?? 0)));
        } else {
            $request->setOrderType(TransOrderType::NORMAL);
        }
        $request->setStatus(TransOrderStatus::SUCCESS);
        // 解析支付方式
        $groupType = $decryptedData['groupType'] ?? StrUtil::NULL;
        $cardType = $decryptedData['cardType'] ?? StrUtil::NULL;
        $policyName = $decryptedData['policyName'] ?? StrUtil::NULL;
        $paymentType = YiLianPosPlatform::toPaymentType($groupType, $cardType, $policyName);
        $request->setPaymentType($paymentType);
        return $request;
    }

    /**
     * 移联流量卡扣费通知
     * @param array $data
     * @return void
     */
    public static function toPosTransCallbackRequestByLakala(array $data): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setOrderType(TransOrderType::SIM);
        // 通知即成功
        $request->setStatus(TransOrderStatus::SUCCESS);
        $request->setTransNo(strval($data['transOrderNo'] ?? StrUtil::NULL));
        $request->setDeviceSn(strval($data['sn'] ?? StrUtil::NULL));
        if (isset($data['transTime'])) {
            $request->setSuccessDateTime(LocalDateTime::valueOfString($data['transTime']));
        } else {
            $request->setSuccessDateTime(LocalDateTime::now());
        }
        $request->setAmount(Money::valueOfYuan(strval($data['vasFlowFee'] ?? 0)));
        return $request;
    }
}
