<?php declare(strict_types=1);

namespace think\pos\provider\yilian\convertor;

use shali\phpmate\core\date\LocalDateTime;
use think\pos\constant\MerchantStatus;
use think\pos\constant\PosStatus;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;

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
}
