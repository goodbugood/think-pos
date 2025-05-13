<?php declare(strict_types=1);

namespace think\pos\provider\lipos\convertor;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\PaymentType;
use think\pos\constant\PosStatus;
use think\pos\constant\TransOrderStatus;
use think\pos\constant\TransOrderType;
use think\pos\dto\request\callback\PosActivateCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\dto\response\PosInfoResponse;

final class PosConvertor
{
    // 禁止 new
    private function __construct()
    {
    }

    /**
     * 解析 pos 终端查询返回数据
     * @param array $data
     * @return PosInfoResponse
     */
    public static function toPosInfoResponse(array $data): PosInfoResponse
    {
        $posInfoResponse = PosInfoResponse::success();
        // 终端号
        $posInfoResponse->setDeviceNo($data['materialsNo'] ?? 'null');
        // 解析流量卡费用
        $money = Money::valueOfFen('0');
        if (!empty($data['materialsSimInfo']['simPhaseList'][0]['simDeductionsList'])) {
            foreach ($data['materialsSimInfo']['simPhaseList'][0]['simDeductionsList'] as $item) {
                if ('YES' === $item['enableStatus']) {
                    $money->add(Money::valueOfYuan(strval($item['deductionAmount'])));
                }
            }
            $posInfoResponse->setSimPackageCode(sprintf(
                '绑定后扣费：免费 %s 天，第 %s-%s 天，计划扣费 %s 元，扣费状态【%s】',
                $data['materialsSimInfo']['simFreeDay'] ?? 'null',
                $data['materialsSimInfo']['simPhaseList'][0]['beginDayNum'] ?? 'null',
                $data['materialsSimInfo']['simPhaseList'][0]['endDayNum'] ?? 'null',
                $money->toYuan(),
                $data['materialsSimInfo']['simPhaseList'][0]['deductionStatus'] ?? 'null'
            ));
        }
        // 解析贷记卡费率，注意，pos 一旦绑定了商户，不再返回终端费率
        $materialsRateList = $data['materialsRateList'] ?? [];
        foreach ($materialsRateList as $item) {
            if ($item['payTypeViewCode'] === 'POS_CC') {
                $posInfoResponse->setCreditRate(Rate::valuePercentage(strval($item['rateValue'] ?? 0)));
            }
        }
        // 解析押金
        $deposit = Money::valueOfFen('0');
        if (isset($data['materialsMachineInfo']['materialsMachineList'])) {
            foreach ($data['materialsMachineInfo']['materialsMachineList'] as $item) {
                if ('YES' === $item['enableStatus']) {
                    $deposit->add(Money::valueOfYuan(strval($item['machineAmount'] ?? 0)));
                }
            }
            $posInfoResponse->setDeposit($deposit);
        }

        return $posInfoResponse;
    }

    public static function toPosActivateCallbackRequest(array $decryptedData): PosActivateCallbackRequest
    {
        $request = PosActivateCallbackRequest::success();
        $request->setMerchantNo($decryptedData['customerNo'] ?? 'null');
        $request->setMerchantName($decryptedData['customerName'] ?? 'null');
        $request->setDeviceSn($decryptedData['materialsNo'] ?? 'null');
        $request->setActivateDateTime($decryptedData['activationTime'] ?? 'null');
        if ('FINISHED' === $decryptedData['activationStatus']) {
            $request->setStatus(PosStatus::ACTIVATE_SUCCESS);
        }
        return $request;
    }

    public static function toPosBindCallbackRequest(array $decryptedData): PosBindCallbackRequest
    {
        $request = PosBindCallbackRequest::success();
        $request->setAgentNo($decryptedData['agentNo'] ?? 'null');
        $request->setMerchantNo($decryptedData['customerNo'] ?? 'null');
        $request->setDeviceSn($decryptedData['materialsNo'] ?? 'null');
        if ('TRUE' === $decryptedData['bindStatus']) {
            // 解绑成功
            $request->setStatus(PosStatus::UNBIND_SUCCESS);
        } elseif ('FORCED_UNBIND' === $decryptedData['bindStatus']) {
            // 强制解绑成功
            $request->setStatus(PosStatus::UNBIND_SUCCESS);
        } elseif ('BINDED' === $decryptedData['bindStatus']) {
            // 绑定成功
            $request->setStatus(PosStatus::BIND_SUCCESS);
        } elseif ('CHANGE_BIND' === $decryptedData['bindStatus']) {
            // 换绑成功
            $request->setStatus(PosStatus::BIND_SUCCESS);
        }
        return $request;
    }

    public static function toPosTransCallbackRequest(array $decryptedData): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setAgentNo($decryptedData['agentNo'] ?? 'null');
        $request->setMerchantNo($decryptedData['customerNo'] ?? 'null');
        $request->setMerchantName($decryptedData['customerName'] ?? 'null');
        $request->setDeviceSn($decryptedData['materialsNo'] ?? 'null');
        $request->setTransNo($decryptedData['orderNo'] ?? 'null');
        $request->setAmount(Money::valueOfYuan(strval($decryptedData['amount'] ?? 0)));
        $request->setSettleAmount(Money::valueOfYuan(strval($decryptedData['settleAmount'] ?? 0)));
        $request->setRate(Rate::valueOfPercentage(strval($decryptedData['feeRate'] ?? 0)));
        $request->setFee(Money::valueOfYuan(strval($decryptedData['fee'] ?? 0)));
        $request->setSuccessDateTime($decryptedData['successTime'] ?? 'null');
        if ('' === $decryptedData['status']) {
            $request->setStatus(TransOrderStatus::SUCCESS);
        } else {
            $request->setStatus(TransOrderStatus::FAILURE);
        }
        // 解析订单类型
        if ('SIM' === $decryptedData['stopPayType' ?? '']) {
            $request->setOrderType(TransOrderType::SIM);
        } elseif ('MACHINE' === $decryptedData['stopPayType'] ?? '') {
            $request->setOrderType(TransOrderType::DEPOSIT);
        } else {
            $request->setOrderType(TransOrderType::NORMAL);
        }
        // 解析支付方式
        if ('Wechat' === $decryptedData['payType'] ?? '') {
            $request->setPaymentType(PaymentType::WECHAT_QR);
        } elseif ('AliPay' === $decryptedData['payType'] ?? '') {
            $request->setPaymentType(PaymentType::ALIPAY_QR);
        } elseif ('UnionQr' === $decryptedData['payType'] ?? '') {
            $request->setPaymentType(PaymentType::UNION_QR);
        } elseif ('QuickPass' === $decryptedData['payType'] ?? '') {
            $request->setPaymentType(PaymentType::NFC);
        } elseif ('DC' === $decryptedData['cardType'] ?? '') {
            $request->setPaymentType(PaymentType::DEBIT_CARD);
        } elseif ('CC' === $decryptedData['cardType'] ?? '') {
            $request->setPaymentType(PaymentType::CREDIT_CARD);
        } elseif ('MobilePay' === $decryptedData['payType'] ?? '') {
            // 力 pos 反馈：手机Pay和闪付性质一样，也是手机贴近POS机，通过NFC读卡信息
            $request->setPaymentType(PaymentType::NFC);
        }
        return $request;
    }
}
