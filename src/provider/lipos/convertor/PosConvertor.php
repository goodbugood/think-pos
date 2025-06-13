<?php declare(strict_types=1);

namespace think\pos\provider\lipos\convertor;

use shali\phpmate\core\date\LocalDateTime;
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
            $posInfoResponse->setSimPackageDesc(sprintf(
                '绑定后扣费：免费 %s 天，第 %s-%s 天，计划扣费 %s 元，扣费状态【%s】',
                $data['materialsSimInfo']['simFreeDay'] ?? 'null',
                $data['materialsSimInfo']['simPhaseList'][0]['beginDayNum'] ?? 'null',
                $data['materialsSimInfo']['simPhaseList'][0]['endDayNum'] ?? 'null',
                $money->toYuan(),
                $data['materialsSimInfo']['simPhaseList'][0]['deductionStatus'] ?? 'null'
            ));
            // 返回套餐内容，自行解析 {"beginDayMaxRang":7,"beginDayMinRang":1,"beginDayNum":1,"deductionStatus":"NO","endDayNum":60,"simDeductionsList":[{"deductionAmount":19,"enableStatus":"YES","simPhaseIndex":19},{"deductionAmount":29,"enableStatus":"NO","simPhaseIndex":29},{"deductionAmount":39,"enableStatus":"NO","simPhaseIndex":39}],"simRuleIndex":1}
            $posInfoResponse->setSimPackageCode(json_encode($data['materialsSimInfo']));
        }
        // 解析贷记卡费率，注意，pos 一旦绑定了商户，不再返回终端费率
        $materialsRateList = $data['materialsRateList'] ?? [];
        foreach ($materialsRateList as $item) {
            if ($item['payTypeViewCode'] === 'POS_CC') {
                $posInfoResponse->setCreditRate(Rate::valueOfPercentage(strval($item['rateValue'] ?? 0)));
                // 解析提现手续费
                $posInfoResponse->setWithdrawFee(Money::valueOfYuan(strval($item['fixedValue'] ?? 0)));
            } elseif ($item['payTypeViewCode'] === 'POS_DC') {
                $posInfoResponse->setDebitCardRate(Rate::valueOfPercentage(strval($item['rateValue'] ?? 0)));
                $posInfoResponse->setDebitCardCappingValue(Money::valueOfYuan(strval($item['cappingValue'] ?? 0)));
            } elseif ($item['payTypeViewCode'] === 'WECHAT') {
                $posInfoResponse->setWechatRate(Rate::valueOfPercentage(strval($item['rateValue'] ?? 0)));
            } elseif ($item['payTypeViewCode'] === 'ALIPAY') {
                $posInfoResponse->setAlipayRate(Rate::valueOfPercentage(strval($item['rateValue'] ?? 0)));
            }
        }
        // 解析押金
        $deposit = Money::valueOfFen('0');
        if (isset($data['materialsMachineInfo']['materialsMachineList'])) {
            foreach ($data['materialsMachineInfo']['materialsMachineList'] as $item) {
                if ('YES' === $item['enableStatus']) {
                    $deposit->add(Money::valueOfYuan(strval($item['machineAmount'] ?? 0)));
                    $posInfoResponse->setDepositPackageCode(strval($item['machinePhaseIndex'] ?? ''));
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
        if (empty($decryptedData['activationTime'])) {
            $request->setActivateDateTime(LocalDateTime::now());
        } else {
            $request->setActivateDateTime(LocalDateTime::valueOfString($decryptedData['activationTime']));
        }
        if ('FINISHED' === $decryptedData['activationStatus'] ?? 'null') {
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
        $request->setOldDeviceSn($decryptedData['oldMaterialsNo'] ?? 'null');
        $bindStatus = $decryptedData['bindStatus'] ?? 'null';
        if ('TRUE' === $bindStatus) {
            // 解绑成功
            $request->setStatus(PosStatus::UNBIND_SUCCESS);
        } elseif ('FORCED_UNBIND' === $bindStatus) {
            // 强制解绑成功
            $request->setStatus(PosStatus::UNBIND_SUCCESS);
        } elseif ('BINDED' === $bindStatus) {
            // 绑定成功
            $request->setStatus(PosStatus::BIND_SUCCESS);
        } elseif ('CHANGE_BIND' === $bindStatus) {
            // 换绑成功
            $request->setStatus(PosStatus::REBIND_SUCCESS);
        }
        // 状态变更时间
        if (empty($decryptedData['changeTime'])) {
            $request->setModifyTime(LocalDateTime::now());
        } else {
            $request->setModifyTime(LocalDateTime::valueOfString($decryptedData['changeTime']));
        }
        return $request;
    }

    public static function toPosTransCallbackRequest(array $decryptedData): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setAgentNo(strval($decryptedData['agentNo'] ?? 'null'));
        $request->setMerchantNo(strval($decryptedData['customerNo'] ?? 'null'));
        $request->setMerchantName(strval($decryptedData['customerName'] ?? 'null'));
        $request->setDeviceSn(strval($decryptedData['materialsNo'] ?? 'null'));
        $request->setAmount(Money::valueOfYuan(strval($decryptedData['amount'] ?? 0)));
        $request->setSettleAmount(Money::valueOfYuan(strval($decryptedData['settleAmount'] ?? 0)));
        $request->setRate(Rate::valueOfPercentage(strval($decryptedData['feeRate'] ?? 0)));
        $request->setFee(Money::valueOfYuan(strval($decryptedData['fee'] ?? 0)));
        $request->setIsFeeCapping('YES' === $decryptedData['isCapping']);
        $request->setWithdrawFee(Money::valueOfYuan(strval($decryptedData['fixedValue'] ?? 0)));
        if (empty($decryptedData['successTime'])) {
            $request->setSuccessDateTime(LocalDateTime::now());
        } else {
            $request->setSuccessDateTime(LocalDateTime::valueOfString($decryptedData['successTime']));
        }
        if ('SUCCESS' === $decryptedData['status']) {
            $request->setStatus(TransOrderStatus::SUCCESS);
        } else {
            $request->setStatus(TransOrderStatus::FAILURE);
        }
        // 解析订单类型
        $stopPayType = $decryptedData['stopPayType'] ?? 'null';
        if ('SIM' === $stopPayType) {
            // 第一笔订单：交易订单
            $request->setOrderType(TransOrderType::NORMAL);
            $request->setTransNo(strval($decryptedData['orderNo'] ?? 'null'));
            // 第二笔订单：流量卡订单
            $request->setSecondOrderType(TransOrderType::SIM);
            $request->setSecondTransNo(strval($decryptedData['stopOrderNo'] ?? 'null'));
            $request->setSecondOrderAmount(Money::valueOfYuan(strval($decryptedData['stopPayAmount'] ?? 0)));
        } elseif ('MACHINE' === $stopPayType) {
            // 押金订单，押金订单不是混合订单，虽然有止付订单号 stopOrderNo，我们决定参照普通交易订单统一取 orderNo
            $request->setOrderType(TransOrderType::DEPOSIT);
            $request->setTransNo(strval($decryptedData['orderNo'] ?? 'null'));
        } else {
            // 交易订单
            $request->setOrderType(TransOrderType::NORMAL);
            $request->setTransNo(strval($decryptedData['orderNo'] ?? 'null'));
        }
        // 解析支付方式
        $payType = $decryptedData['payTypeCode'] ?? 'null';
        if ('WECHAT' === $payType) {
            $request->setPaymentType(PaymentType::WECHAT_QR);
        } elseif ('ALIPAY' === $payType) {
            $request->setPaymentType(PaymentType::ALIPAY_QR);
        } elseif ('UNIONPAY_DOWN_CC' === $payType) {
            // 力 pos 反馈云闪付1000-可以当做微信和支付宝
            $request->setPaymentType(PaymentType::UNION_QR);
        } elseif ('POS_DC' === $payType) {
            $request->setPaymentType(PaymentType::DEBIT_CARD);
        } elseif (in_array($payType, ['POS_CC', 'POS_DISCOUNT_CC', 'POS_DISCOUNT_GF_CC', 'POS_DISCOUNT_MS_CC', 'POS_DISCOUNT_PA_CC'])) {
            // 力 pos 反馈 4 个特惠类型用贷记卡
            $request->setPaymentType(PaymentType::CREDIT_CARD);
        }
        return $request;
    }
}
