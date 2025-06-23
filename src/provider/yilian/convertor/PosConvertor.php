<?php declare(strict_types=1);

namespace think\pos\provider\yilian\convertor;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\TransOrderStatus;
use think\pos\constant\TransOrderType;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\provider\yilian\YiLianPosPlatform;

final class PosConvertor
{
    public static function toPosTransCallbackRequest(array $data): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setAgentNo(strval($decryptedData['agentNo'] ?? 'null'));
        $request->setMerchantNo(strval($decryptedData['merchantNo'] ?? 'null'));
        $request->setMerchantName(strval($decryptedData['merchantName'] ?? 'null'));
        $request->setDeviceSn(strval($decryptedData['terminalId'] ?? 'null'));
        $request->setAmount(Money::valueOfYuan(strval($decryptedData['transAmount'] ?? 0)));
        // $request->setSettleAmount(Money::valueOfYuan(strval($decryptedData['settleAmount'] ?? 0)));
        $request->setRate(Rate::valueOfPercentage(strval($decryptedData['transRate'] ?? 0)));
        $request->setFee(Money::valueOfYuan(strval($decryptedData['transFee'] ?? 0)));
        // 手续费是否封顶，0 否 1 是
        $request->setIsFeeCapping(1 === $decryptedData['feeTop']);
        // $request->setWithdrawFee(Money::valueOfYuan(strval($decryptedData['fixedValue'] ?? 0)));
        if (empty($decryptedData['transTime'])) {
            $request->setSuccessDateTime(LocalDateTime::now());
        } else {
            $request->setSuccessDateTime(LocalDateTime::valueOfString($decryptedData['transTime']));
        }
        // 回调即成功，失败订单不会回调
        $request->setStatus(TransOrderStatus::SUCCESS);
        // 解析订单类型
        if ('1' === $decryptedData['serviceFeeFalg'] ?? 'null') {
            // 是否收取服务费标识
            $request->setOrderType(TransOrderType::DEPOSIT);
        } elseif ('2' === $decryptedData['flowFeeFlag'] ?? 'null') {
            // 是否收取流量费标识
            $request->setOrderType(TransOrderType::SIM);
        } else {
            $request->setOrderType(TransOrderType::NORMAL);
        }
        // 解析支付方式
        $groupType = $decryptedData['groupType'] ?? 'null';
        $cardType = $decryptedData['cardType'] ?? 'null';
        $paymentType = YiLianPosPlatform::toPaymentType($groupType, $cardType);
        $request->setPaymentType($paymentType);
        return $request;
    }
}
