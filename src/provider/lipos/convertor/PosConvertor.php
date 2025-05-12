<?php declare(strict_types=1);

namespace think\pos\provider\lipos\convertor;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
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
}
