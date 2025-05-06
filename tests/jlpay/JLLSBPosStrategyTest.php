<?php

namespace think\pos\tests\jlpay;

use PHPUnit\Framework\TestCase;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\response\PosInfoResponse;
use think\pos\dto\response\PosProviderResponse;
use think\pos\PosStrategy;
use think\pos\PosStrategyFactory;

class JLLSBPosStrategyTest extends TestCase
{
    /**
     * @var PosStrategy
     */
    private $posStrategy;

    protected function setUp(): void
    {
        $this->posStrategy = PosStrategyFactory::create('lishuaB');
    }

    /**
     * @test 测试取消会员
     * @return void
     */
    function cancelVip()
    {
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn(env('lishuaB.posSn'));
        $posProviderResponse = $this->posStrategy->cancelVip($posRequestDto);
        self::assertInstanceOf(PosProviderResponse::class, $posProviderResponse);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    public function testGetPosInfo()
    {
        $posSn = env('lishuaB.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posInfoResponse = $this->posStrategy->getPosInfo($posRequestDto);
        // 请求成功
        self::assertInstanceOf(PosInfoResponse::class, $posInfoResponse);
        self::assertTrue($posInfoResponse->isSuccess(), $posInfoResponse->getErrorMsg() ?? '');
        // 返回值检查
        self::assertInstanceOf(Money::class, $posInfoResponse->getDeposit());
        self::assertEquals($posSn, $posInfoResponse->getDeviceNo());
        self::assertNotEmpty($posInfoResponse->getSimPackageCode());
        self::assertInstanceOf(Rate::class, $posInfoResponse->getCreditRate());
        self::assertInstanceOf(Money::class, $posInfoResponse->getWithdrawFee());
    }
}
