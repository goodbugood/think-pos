<?php

namespace think\pos\tests\provider\yilian;

use PHPUnit\Framework\TestCase;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\PosStrategy;
use think\pos\PosStrategyFactory;

class YiLianPosPlatformTest extends TestCase
{
    /**
     * @var PosStrategy
     */
    private $posStrategy;

    protected function setUp(): void
    {
        $this->posStrategy = PosStrategyFactory::create('yilian');
    }

    public function testSetMerchantRate()
    {
        $merchantNo = env('yilian.merchantNo');
        self::assertNotEmpty($merchantNo, 'yilian.merchantNo is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $merchantRequestDto->setMerchantNo($merchantNo);
        $merchantRequestDto->setWithdrawFee(Money::valueOfYuan('3'));
        $merchantRequestDto->setCreditRate(Rate::valueOfPercentage('0.6'));
        $merchantRequestDto->setDebitCardRate(Rate::valueOfPercentage('0.6'));
        $merchantRequestDto->setDebitCardCappingValue(Money::valueOfYuan('20'));
        // 设置微信费率
        $merchantRequestDto->setWechatRate(Rate::valueOfPercentage('0.37'));
        $merchantRequestDto->setAlipayRate(Rate::valueOfPercentage('0.37'));
        $posProviderResponse = $this->posStrategy->setMerchantRate($merchantRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    public function testSetSimFee()
    {
        $merchantNo = env('yilian.merchantNo');
        self::assertNotEmpty($merchantNo, 'yilian.merchantNo is empty');
        $simRequestDto = new SimRequestDto();
        $simRequestDto->setMerchantNo($merchantNo);
        // 套餐这个固定？
        $simRequestDto->setSimPackageCode(json_encode([
            'cycleNo' => 1,
            // 开始执行的天数
            'startDays' => null,
            // 结束执行的天数
            'endDays' => null,
            // 周期类型，单次 SINGLE，循环 RENEW
            'cycleType' => null,
            // 流量包金额，单位元
            'vasRate' => null,
            // 最小交易金额，单位元
            'minTransAmount' => null,
        ]));
        $posProviderResponse = $this->posStrategy->setSimFee($simRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    public function testUnbindPos()
    {

    }

    public function testHandleCallback()
    {

    }
}
