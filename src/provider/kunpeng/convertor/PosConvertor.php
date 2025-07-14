<?php declare(strict_types=1);

namespace think\pos\provider\kunpeng\convertor;

use DateTime;
use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
use think\pos\constant\PaymentType;
use think\pos\constant\PosStatus;
use think\pos\constant\TransOrderStatus;
use think\pos\constant\TransOrderType;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\exception\ProviderGatewayException;

final class PosConvertor
{
    // 禁止 new
    private function __construct()
    {
    }

    public static function toPosBindCallbackRequest(array $decryptedData): PosBindCallbackRequest
    {
        $request = PosBindCallbackRequest::success();
        // 商户注册信息
        $merchantRegisterCallbackRequest = MerchantRegisterCallbackRequest::success();
        $request->setMerchantRegisterCallbackRequest($merchantRegisterCallbackRequest);
        $merchantRegisterCallbackRequest->setMerchantNo($decryptedData['merchantNo'] ?? StrUtil::NULL);
        $merchantRegisterCallbackRequest->setMerchantName($decryptedData['merchantName'] ?? StrUtil::NULL);
        $merchantRegisterCallbackRequest->setPhoneNo($decryptedData['phoneNo'] ?? StrUtil::NULL);
        $merchantRegisterCallbackRequest->setIdCardNo($decryptedData['idCard'] ?? StrUtil::NULL);
        $merchantRegisterCallbackRequest->setIdCardName($decryptedData['legalName'] ?? StrUtil::NULL);
        $merchantRegisterCallbackRequest->setStatus(MerchantStatus::ENABLED);
        $localDateTime = LocalDateTime::now();
        $dateTime = DateTime::createFromFormat('YmdHis', $decryptedData['createTime'] ?? $localDateTime->format('YmdHis'));
        $merchantRegisterCallbackRequest->setRegDateTime($dateTime->format('Y-m-d H:i:s'));
        // pos 绑定信息
        $request->setMerchantNo($decryptedData['merchantNo'] ?? StrUtil::NULL);
        $request->setDeviceSn($decryptedData['deviceNo'] ?? StrUtil::NULL);
        // 绑定成功
        $request->setStatus(PosStatus::BIND_SUCCESS);
        $request->setModifyTime($localDateTime);
        return $request;
    }

    /**
     * @throws ProviderGatewayException
     */
    public static function toPosUnBindCallbackRequest($decryptedData): PosBindCallbackRequest
    {
        $request = PosBindCallbackRequest::success();
        $localDateTime = LocalDateTime::now();
        // pos 绑定信息
        $request->setMerchantNo($decryptedData['merchantNo'] ?? StrUtil::NULL);
        $request->setDeviceSn($decryptedData['deviceNo'] ?? StrUtil::NULL);
        $request->setAgentNo($decryptedData['agentNo'] ?? StrUtil::NULL);
        // 检查通知业务类型，是绑定还是解绑
        $status = $decryptedData['status'] ?? '';
        if (in_array($status, ['BIND_FALSE', 'BIND_SUCCESS'])) {
            // 绑定通知
            $status = 'BIND_SUCCESS' === ($status ?? '') ? PosStatus::BIND_SUCCESS : PosStatus::BIND_FAILURE;
        } elseif (in_array($status, ['UNBIND_FALSE', 'UNBIND_SUCCESS'])) {
            // 解绑通知
            $status = 'UNBIND_SUCCESS' === ($status ?? '') ? PosStatus::UNBIND_SUCCESS : PosStatus::UNBIND_FAILURE;
        } else {
            // 未知状态
            throw new ProviderGatewayException(sprintf('鲲鹏绑定/解绑通知回调了未知的状态status=%s', $status));
        }
        $request->setStatus($status);
        $request->setModifyTime($localDateTime);
        return $request;
    }

    /**
     * 普通交易通知数据解析
     * freeRate 是商户实际的交易费率
     * baseRate 是商户入网后的基础费率
     * agentRaisePricePate 是代理调价后的费率
     */
    public static function toPosTransCallbackRequestByNormal(array $decryptedData): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setOrderType(TransOrderType::NORMAL);
        $request->setTransNo(strval($decryptedData['orderNo'] ?? StrUtil::NULL));
        $request->setMerchantNo(strval($decryptedData['merchantNo'] ?? StrUtil::NULL));
        $request->setMerchantName(strval($decryptedData['merchantName'] ?? StrUtil::NULL));
        $request->setDeviceSn(strval($decryptedData['deviceNo'] ?? StrUtil::NULL));
        if (empty($decryptedData['successTime'])) {
            $request->setSuccessDateTime(LocalDateTime::now());
        } else {
            $dateTime = DateTime::createFromFormat('YmdHis', $decryptedData['successTime']);
            $request->setSuccessDateTime(LocalDateTime::valueOfString($dateTime->format('Y-m-d H:i:s')));
        }
        $request->setAmount(Money::valueOfYuan(strval($decryptedData['amount'] ?? 0)));
        $request->setFee(Money::valueOfYuan(strval($decryptedData['fee'] ?? 0)));
        $request->setWithdrawFee(Money::valueOfYuan(strval($decryptedData['fixedValue'] ?? 0)));
        $request->setIsFeeCapping('YES' === ($decryptedData['cappingFlag'] ?? StrUtil::NULL));
        // 鲲鹏回调 3 种费率，沟通确认的是使用 feeRate 即可
        $request->setRate(Rate::valueOfDecimal(strval($decryptedData['feeRate'] ?? 0)));
        // 解析支付方式
        $payType = self::parsePayTypeCode($decryptedData['payTypeCode'] ?? StrUtil::NULL);
        $request->setPaymentType($payType);
        $request->setStatus(TransOrderStatus::SUCCESS);
        return $request;
    }

    /**
     * 流量卡交易通知数据解析
     * @param array $decryptedData
     * @return PosTransCallbackRequest
     */
    public static function toPosTransCallbackRequestBySim(array $decryptedData): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setOrderType(TransOrderType::SIM);
        $request->setDeviceSn(strval($decryptedData['deviceNo'] ?? StrUtil::NULL));
        $request->setTransNo(strval($decryptedData['orderNo'] ?? StrUtil::NULL));
        $request->setMerchantNo(strval($decryptedData['merchantNo'] ?? StrUtil::NULL));
        $request->setMerchantName(strval($decryptedData['merchantName'] ?? StrUtil::NULL));
        $request->setAmount(Money::valueOfYuan(strval($decryptedData['amount'] ?? 0)));
        if (empty($decryptedData['successTime'])) {
            $request->setSuccessDateTime(LocalDateTime::now());
        } else {
            $dateTime = DateTime::createFromFormat('YmdHis', $decryptedData['successTime']);
            $request->setSuccessDateTime(LocalDateTime::valueOfString($dateTime->format('Y-m-d H:i:s')));
        }
        $request->setStatus(TransOrderStatus::SUCCESS);
        return $request;
    }

    /**
     * 押金交易通知数据解析
     * @param array $decryptedData
     * @return PosTransCallbackRequest
     */
    public static function toPosTransCallbackRequestByDeposit(array $decryptedData): PosTransCallbackRequest
    {
        $request = PosTransCallbackRequest::success();
        $request->setOrderType(TransOrderType::DEPOSIT);
        $request->setDeviceSn(strval($decryptedData['deviceNo'] ?? StrUtil::NULL));
        $request->setTransNo(strval($decryptedData['orderNo'] ?? StrUtil::NULL));
        $request->setMerchantNo(strval($decryptedData['merchantNo'] ?? StrUtil::NULL));
        $request->setMerchantName(strval($decryptedData['merchantName'] ?? StrUtil::NULL));
        $request->setAmount(Money::valueOfYuan(strval($decryptedData['amount'] ?? 0)));
        if (empty($decryptedData['successTime'])) {
            $request->setSuccessDateTime(LocalDateTime::now());
        } else {
            $dateTime = DateTime::createFromFormat('YmdHis', $decryptedData['successTime']);
            $request->setSuccessDateTime(LocalDateTime::valueOfString($dateTime->format('Y-m-d H:i:s')));
        }
        if ('SUCCESS' === $decryptedData['status']) {
            $request->setStatus(TransOrderStatus::SUCCESS);
        } else {
            $request->setStatus(TransOrderStatus::FAILURE);
        }
        return $request;
    }

    /**
     * 鲲鹏的支付类型转换 think-pos 支付类型
     * @param string $materialsType
     * @return string
     */
    private static function parsePayTypeCode(string $materialsType): string
    {
        if ('WECHAT' === $materialsType) {
            return PaymentType::WECHAT_QR;
        } elseif ('ALIPAY' === $materialsType) {
            return PaymentType::ALIPAY_QR;
        } elseif (in_array($materialsType, ['UNIONPAY_DOWN_CC', 'UNIONPAY_DOWN_DC'])) {
            // 1000 以下的二维码
            return PaymentType::UNION_QR;
        } elseif (in_array($materialsType, ['POS_DC', 'UNIONPAY_UP_DC', 'POS_NC_DC'])) {
            // 借记卡，1000 以上的二维码借记卡，NFC 借记卡
            return PaymentType::DEBIT_CARD;
        } elseif (in_array($materialsType, ['POS_CC', 'UNIONPAY_UP_CC', 'POS_NC_CC', 'JD_PLI',])) {
            // 贷记卡，1000 以上的二维码贷记卡，NFC 贷记卡，京东白条分期
            return PaymentType::CREDIT_CARD;
        }
        return PaymentType::UNKNOWN;
    }
}
