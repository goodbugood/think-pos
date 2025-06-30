<?php declare(strict_types=1);

namespace think\pos\provider\yilian\convertor;

use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\util\Money;
use think\pos\constant\SettleType;
use think\pos\constant\TransOrderStatus;
use think\pos\dto\request\callback\PosSettleCallbackRequest;

class PosSettleConvertor
{
    public static function toPosSettleCallbackRequest(array $data): PosSettleCallbackRequest
    {
        $callbackRequest = PosSettleCallbackRequest::success();
        // 仅结算成功通知
        $callbackRequest->setStatus(TransOrderStatus::SUCCESS);
        $callbackRequest->setSettleType(SettleType::WITHDRAW_FEE);
        $callbackRequest->setMerchantNo($data['merchantNo'] ?? StrUtil::NULL);
        $callbackRequest->setOrderNo($data['settleOrderNo'] ?? StrUtil::NULL);
        $callbackRequest->setTransNo($data['transOrderNo'] ?? StrUtil::NULL);
        $callbackRequest->setAmount(Money::valueOfYuan(strval($data['feeAmount'] ?? 0)));
        $settleDateTime = empty($data['createTime']) ? LocalDateTime::now() : LocalDateTime::valueOfString($data['createTime']);
        $callbackRequest->setSettleDateTime($settleDateTime);
        return $callbackRequest;
    }
}