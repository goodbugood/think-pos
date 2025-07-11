<?php

namespace think\pos\tests\provider\yilian;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
use think\pos\constant\PosStatus;
use think\pos\constant\SettleType;
use think\pos\constant\TransOrderStatus;
use think\pos\constant\TransOrderType;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosSettleCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosDepositRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\exception\UnsupportedBusinessException;
use think\pos\PosStrategy;
use think\pos\PosStrategyFactory;
use think\pos\provider\yilian\YiLianPosPlatform;

/**
 * // 目前剩余，待验证
 * 1. 商户费率通知
 */
class YiLianPosPlatformTest extends TestCase
{
    /**
     * @var PosStrategy
     */
    private $posStrategy;

    private const AGENT_NO = '8140122446';

    /**
     * 1. 加密，解密
     * 2. 请求接口签名使用
     */
    private const AES_KEY = 'yya9xts8lqdz7m3n';

    /**
     * 回调通知，验签使用
     */
    private const MD5_KEY = 'a5e46fb618c34d66b85ab62b610dd756';

    protected function setUp(): void
    {
        $this->posStrategy = PosStrategyFactory::create('yilian');
        // 反射修改公私钥
        $reflection = new ReflectionClass($this->posStrategy);
        $reflectionProperty = $reflection->getProperty('config');
        $reflectionProperty->setAccessible(true);
        $posProviderConfig = $reflectionProperty->getValue($this->posStrategy);
        $posProviderConfig['aesKey'] = self::AES_KEY;
        $posProviderConfig['md5Key'] = self::MD5_KEY;
        $posProviderConfig['agentNo'] = self::AGENT_NO;
        $reflectionProperty->setValue($this->posStrategy, $posProviderConfig);
    }

    //<editor-fold desc="商户操作接口">

    /**
     * @test 测试获取商户流量卡套餐信息
     * @return void
     * @throws UnsupportedBusinessException
     */
    function getMerchantSimFeeInfo()
    {
        $merchantNo = env('yilian.merchantNo');
        $this->assertNotEmpty($merchantNo);
        $simRequestDto = new SimRequestDto();
        $simRequestDto->setMerchantNo($merchantNo);
        $response = $this->posStrategy->getMerchantSimFeeInfo($simRequestDto);
        self::assertTrue($response->isSuccess(), $response->getErrorMsg() ?? '');
        $decrypted = '{"merchantNo":"","minIntervalDays":"","maxIntervalDays":"","minTransAmount":"","maxTransAmount":"","minVasRate":"","maxVasRate":"","minFreeDays":"","maxFreeDays":"","freeDays":"","effectiveTimeStart":"","effectiveTimeEnd":"","transTime":"","activityTime":"","merchantFlowList":[]}';
        self::assertEquals($decrypted, $response->getBody());
    }

    /**
     * @throws UnsupportedBusinessException
     */
    public function testSetMerchantRate()
    {
        $merchantNo = env('yilian.merchantNo');
        self::assertNotEmpty($merchantNo, 'yilian.merchantNo is empty');
        $posSn = env('yilian.posSn');
        self::assertNotEmpty($posSn, 'yilian.posSn is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $merchantRequestDto->setMerchantNo($merchantNo);
        $merchantRequestDto->setDeviceSn($posSn);
        $merchantRequestDto->setWithdrawFee(Money::valueOfYuan('3'));
        $merchantRequestDto->setCreditRate(Rate::valueOfPercentage('0.66'));
        $merchantRequestDto->setDebitCardRate(Rate::valueOfPercentage('0.8'));
        $merchantRequestDto->setDebitCardCappingValue(Money::valueOfYuan('20'));
        // 设置微信费率
        $merchantRequestDto->setWechatRate(Rate::valueOfPercentage('0.37'));
        $merchantRequestDto->setAlipayRate(Rate::valueOfPercentage('0.37'));
        // 设置扩展信息
        $merchantRequestDto->setExtInfo([
            'ratePolicy' => '海科买断版',
        ]);
        $posProviderResponse = $this->posStrategy->setMerchantRate($merchantRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * 商户解绑机具
     * @return void
     * @throws UnsupportedBusinessException
     */
    public function testUnbindPos()
    {
        $posSn = env('yilian.posSn');
        self::assertNotEmpty($posSn, 'yilian.posSn is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posProviderResponse = $this->posStrategy->unbindPos($merchantRequestDto, $posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    //</editor-fold>

    //<editor-fold desc="POS操作接口">
    /**
     * 测试获取押金列表
     * @throws UnsupportedBusinessException
     */
    public function testGetPosDepositList()
    {
        $posSn = env('yilian.posSn');
        self::assertNotEmpty($posSn, 'yilian.posSn is empty');
        $depositRequestDto = new PosDepositRequestDto();
        $depositRequestDto->setDeviceSn($posSn);
        $depositRequestDto->setDeposit(Money::valueOfYuan('100'));
        $depositRequestDto->setDepositPackageCode('不需要');
        $posDepositResponse = $this->posStrategy->getPosDeposit($depositRequestDto);
        self::assertTrue($posDepositResponse->isSuccess(), $posDepositResponse->getErrorMsg() ?? '');
        self::assertNotEmpty($posDepositResponse->getDeviceNo());
        // 押金列表不为空
        self::assertNotEmpty($posDepositResponse->getDepositPackageCode());
    }

    /**
     * @throws UnsupportedBusinessException
     */
    function testSetPosDeposit()
    {
        $posSn = env('yilian.posSn');
        self::assertNotEmpty($posSn, 'yilian.posSn is empty');
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posRequestDto->setDeposit(Money::valueOfYuan('100'));
        $posRequestDto->setDepositPackageCode(json_encode([
            [
                // 中付
                'channelCode' => 'ZF',
                // 0 押金 ACN0000140183，99 押金 ACN0000135916，199 押金 ACN0000140184，299 押金 ACN0000135917
                'activityCashNo' => 'ACN0000135916',
            ],
            [
                // 海科支付
                'channelCode' => 'HK',
                // 0 押金 ACN0000140178，99 押金 ACN0000135911，199 押金 ACN0000140179，299 押金 ACN0000135912
                'activityCashNo' => 'ACN0000135912',
            ],
        ]));
        $posProviderResponse = $this->posStrategy->setPosDeposit($posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    //</editor-fold>

    //<editor-fold desc="回调通知">

    /**
     * @throws UnsupportedBusinessException
     */
    public function testHandleMerchantRegisterCallback()
    {
        $content = 'data=e9euP4X4CjNWAhYLuhZFrhFQwxJeABYDVclXCU2H%2FpfWXCnFDW7kso1dw60q8wuuQvKqzNRskXkqSq%2BA2uh0s15u91TX88PUktVjkg8CSgLRKxwmqtWHFzQ3AcpVrfX2Ga3RI8wGKuxTO%2Fkgy6RHkS7g3YwjUMAYkGKqI%2BAu1DlRVG5WqsGDgKgMb6AwTT43AkfJWP2ng3W5b9OaWFyoHFZqHiArKOudt%2FQMGdCfimnZ8add7nnMHD58Fsl5P1fvBj6Z%2Ff53vNZjNAQQwzPtDzwr6bxh1QZ1vMH7pNcdYBVRDiFeMkO%2BgQbyMHS0Izmy8Laf9yTW%2FJvz1Wr%2BnSwKsfwKbcjN%2BqhLBlWXbq1cwq7Qg%2BxBPtUBrjOpmK%2Fy15KwF%2FXEld4Jmvp8KwdC7cHrfC3p%2Bi09UOuNjp76eg5qzDumjum9j1rkcSa2PcwDTMtRfNHMB2lwDQSlq6OHJN7SiXSPUtEm4N4TAokddaio30AIHSeBwFpdze7756aNJ0grgMsqV%2Fs5x2gthz6iT2IohnBjmUrn8nh%2BMMCOIOmA%2FLC5xinOy4HG0kHJl1x4uWwvmtajcNadc1Gvj%2Fc2Ryaog%2BoZ6HFHaSX7%2FHyNnBMJGzHvTp7vpEtKROrmMQd7IYr13OXRqy4dvwfd5s66jCxW7eyBMnwXpZ%2F87jiAJ4zDeKldakhY8u0dNygINlAOAcxAh9C2d65cQ60dskmJxc43a9wv1awVZe9GG73BNG8%2FQMgl%2BjNVTgIdLNMA9oOlc2myFsS1Hj8RmXyc4VZlirfJXrJ0v%2BpiV1VomTBISPUQ0qFy4eqvJRTAtIXBR%2BaLpXrD4dBGecV5A4ilINcUCi9HaOZ8X1tZr4tlTafC2UfLl9B%2FdQbZlvV5X8WmkPIeqADvJtdWZcELKj7%2FDRdC2%2BD6GOYg7iSWIHidp5%2FFPhiUJR%2FntL0w0jlHwlVStBPYs8RgxhiRdziCpmxcsHoG0ej%2FwH9SGcMpWYxyXz8txlljciyMDAguH%2FFrAyEKrBbkWLbyPw%2BT2co807jFvRj%2FVqsBNtcVF%2F%2FdK9fWHiS1abo3%2Bk7buqttaAW7lt2cYHPuxg19AWhaobruDfTefppeDyf8ym5IfQtrtVJDcygdiTg4fW13reKEXF8VStjYxM9Hphjew45dFWjzyZer6TVSM0n9HC%2BADZ940zW84LNjItAZ3oloCPIeggqXMmY0pSIjm71Pfom4V66Pn64zeLnX7tSR4R6I1DJJinvc9hndb%2FsN52gujYk48v9fHhuEndFvU70sEKKRHdAOwEXdEM4Ks6x1syNi4kj4ykg96qXsRSUDXKiyqQhfaxqwCRzYpKaqS%2BAZfTQtMnsnACqX7HWN46Y3NcjFxMCPbKdRsbPPSaeDoW1Nn%2FV1RhuLLS9UJKEEQzFRNeFThVrsMRKI%2BJ43XIzhlM5zrg0U1jsG8LPbjrjHuIrXB%2BqFkdCOOuh5Hz0h80kv3C%2FVrBVl70YbvcE0bz9AyCX6M1VOAh0s0wD2g6VzabIWxLUePxGZfJzhVmWKt8lesnS%2F6mJXVWiZMEhI9RDSoZl04bUh0xyzzNp22nfR8aJUvPApdALzEjarsMbAnZDIDtQZZcfFs%2FAy9pK%2BWE2J%2FrmBxoCNGttyWXFllbDf0QAin%2FOuXQwdsGbSAzFMTS6q13CErwG9Cle4NIF03ZlAmnC8ynUU%2F%2Bw%2BMCrQM7ncXFZs4oIy1QqY1xoLTAXtkmfBuX85XFfCuXfkbf9%2BsTN6DlMX64tE%2FV78MgHiwXcWL0b%2BTYsvqhcmkkQTeIiaDnbaFT9DF1%2BYcwKaGOuSb7tUUuLuUcOjZLEHnQoCics%2BbUzl1PCrTdL%2F9eOSh%2FffGYeRYBpILHlTOvvcA5LUVn1Cp5103U9Am6lwIP1ioAXDrJOLxY7z9vKWMIZkvM%2FO8y5MKAOKPVopjmg3O7iuhZOGhl7iDknU3AjSpnlTZagUEBnUlEHAxS5PCEglHMJQWeRlIjbRaQJ8JD%2BEeVflevqcqtizSYsMg59C9U1ISAcduiSsZYTpMJhYI4iNYIVG3IvONsn5KQ6ud7wgMH3St9soxOyo1QfesSdYI5u48fXvi%2FCZfujRWUV%2FD0majQhsbvLj9f01FBsrlFllWrHG8kUI%2ByQpuT9dOBVPLgG%2Bdd5Tm%2Fnxkcim6tL1M6zr6U%2FF%2FnCAnXpKlTd9730qOeGMMCCN4ibbXOM%2Bm%2BLmlYFltUodFUmYjn5gqsyyCbNcustYdaqvsk4h8duaTqJwevNggGPPz3gSUeCng2rh%2BA1huQxbah0%3D';
        $callbackRequest = $this->posStrategy->handleCallbackOfMerchantRegister($content);
        self::assertNotEmpty($callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getMerchantName());
        self::assertNotEmpty($callbackRequest->getIdCardName());
        self::assertNotEmpty($callbackRequest->getIdCardNo());
        self::assertNotEmpty($callbackRequest->getPhoneNo());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertNotEmpty($callbackRequest->getExtInfo());
        self::assertArrayHasKey('ratePolicy', $callbackRequest->getExtInfo());
        self::assertEquals(MerchantStatus::ENABLED, $callbackRequest->getStatus());
    }

    /**
     * @return void
     * @throws UnsupportedBusinessException
     * @test 测试商户绑定回调
     */
    function handleCallbackOfPosBind()
    {
        $content = 'data=e9euP4X4CjNWAhYLuhZFrhFQwxJeABYDVclXCU2H%2FpfWXCnFDW7kso1dw60q8wuuQvKqzNRskXkqSq%2BA2uh0s15u91TX88PUktVjkg8CSgLRKxwmqtWHFzQ3AcpVrfX2Ga3RI8wGKuxTO%2Fkgy6RHkS7g3YwjUMAYkGKqI%2BAu1DlRVG5WqsGDgKgMb6AwTT43AkfJWP2ng3W5b9OaWFyoHFZqHiArKOudt%2FQMGdCfimnZ8add7nnMHD58Fsl5P1fvBj6Z%2Ff53vNZjNAQQwzPtDzwr6bxh1QZ1vMH7pNcdYBVRDiFeMkO%2BgQbyMHS0Izmy8Laf9yTW%2FJvz1Wr%2BnSwKsfwKbcjN%2BqhLBlWXbq1cwq7Qg%2BxBPtUBrjOpmK%2Fy15KwF%2FXEld4Jmvp8KwdC7cHrfC3p%2Bi09UOuNjp76eg5qzDumjum9j1rkcSa2PcwDTMtRfNHMB2lwDQSlq6OHJN7SiXSPUtEm4N4TAokddaio30AIHSeBwFpdze7756aNJ0grgMsqV%2Fs5x2gthz6iT2IohnBjmUrn8nh%2BMMCOIOmA%2FLC5xinOy4HG0kHJl1x4uWwvmtajcNadc1Gvj%2Fc2Ryaog%2BoZ6HFHaSX7%2FHyNnBMJGzHvTp7vpEtKROrmMQd7IYr13OXRqy4dvwfd5s66jCxW7eyBMnwXpZ%2F87jiAJ4zDeKldakhY8u0dNygINlAOAcxAh9C2d65cQ60dskmJxc43a9wv1awVZe9GG73BNG8%2FQMgl%2BjNVTgIdLNMA9oOlc2myFsS1Hj8RmXyc4VZlirfJXrJ0v%2BpiV1VomTBISPUQ0qFy4eqvJRTAtIXBR%2BaLpXrD4dBGecV5A4ilINcUCi9HaOZ8X1tZr4tlTafC2UfLl9B%2FdQbZlvV5X8WmkPIeqADvJtdWZcELKj7%2FDRdC2%2BD6GOYg7iSWIHidp5%2FFPhiUJR%2FntL0w0jlHwlVStBPYs8RgxhiRdziCpmxcsHoG0ej%2FwH9SGcMpWYxyXz8txlljciyMDAguH%2FFrAyEKrBbkWLbyPw%2BT2co807jFvRj%2FVqsBNtcVF%2F%2FdK9fWHiS1abo3%2Bk7buqttaAW7lt2cYHPuxg19AWhaobruDfTefppeDyf8ym5IfQtrtVJDcygdiTg4fW13reKEXF8VStjYxM9Hphjew45dFWjzyZer6TVSM0n9HC%2BADZ940zW84LNjItAZ3oloCPIeggqXMmY0pSIjm71Pfom4V66Pn64zeLnX7tSR4R6I1DJJinvc9hndb%2FsN52gujYk48v9fHhuEndFvU70sEKKRHdAOwEXdEM4Ks6x1syNi4kj4ykg96qXsRSUDXKiyqQhfaxqwCRzYpKaqS%2BAZfTQtMnsnACqX7HWN46Y3NcjFxMCPbKdRsbPPSaeDoW1Nn%2FV1RhuLLS9UJKEEQzFRNeFThVrsMRKI%2BJ43XIzhlM5zrg0U1jsG8LPbjrjHuIrXB%2BqFkdCOOuh5Hz0h80kv3C%2FVrBVl70YbvcE0bz9AyCX6M1VOAh0s0wD2g6VzabIWxLUePxGZfJzhVmWKt8lesnS%2F6mJXVWiZMEhI9RDSoZl04bUh0xyzzNp22nfR8aJUvPApdALzEjarsMbAnZDIDtQZZcfFs%2FAy9pK%2BWE2J%2FrmBxoCNGttyWXFllbDf0QAin%2FOuXQwdsGbSAzFMTS6q13CErwG9Cle4NIF03ZlAmnC8ynUU%2F%2Bw%2BMCrQM7ncXFZs4oIy1QqY1xoLTAXtkmfBuX85XFfCuXfkbf9%2BsTN6DlMX64tE%2FV78MgHiwXcWL0b%2BTYsvqhcmkkQTeIiaDnbaFT9DF1%2BYcwKaGOuSb7tUUuLuUcOjZLEHnQoCics%2BbUzl1PCrTdL%2F9eOSh%2FffGYeRYBpILHlTOvvcA5LUVn1Cp5103U9Am6lwIP1ioAXDrJOLxY7z9vKWMIZkvM%2FO8y5MKAOKPVopjmg3O7iuhZOGhl7iDknU3AjSpnlTZagUEBnUlEHAxS5PCEglHMJQWeRlIjbRaQJ8JD%2BEeVflevqcqtizSYsMg59C9U1ISAcduiSsZYTpMJhYI4iNYIVG3IvONsn5KQ6ud7wgMH3St9soxOyo1QfesSdYI5u48fXvi%2FCZfujRWUV%2FD0majQhsbvLj9f01FBsrlFllWrHG8kUI%2ByQpuT9dOBVPLgG%2Bdd5Tm%2Fnxkcim6tL1M6zr6U%2FF%2FnCAnXpKlTd9730qOeGMMCCN4ibbXOM%2Bm%2BLmlYFltUodFUmYjn5gqsyyCbNcustYdaqvsk4h8duaTqJwevNggGPPz3gSUeCng2rh%2BA1huQxbah0%3D';
        $callbackOfPosBind = $this->posStrategy->handleCallbackOfPosBind($content);
        self::assertInstanceOf(MerchantRegisterCallbackRequest::class, $callbackOfPosBind->getMerchantRegisterCallbackRequest());
        $registerCallbackRequest = $callbackOfPosBind->getMerchantRegisterCallbackRequest();
        self::assertNotEmpty($registerCallbackRequest->getAgentNo());
        self::assertNotEmpty($registerCallbackRequest->getMerchantNo());
        self::assertNotEmpty($registerCallbackRequest->getMerchantName());
        self::assertNotEmpty($registerCallbackRequest->getIdCardName());
        self::assertNotEmpty($registerCallbackRequest->getIdCardNo());
        self::assertNotEmpty($registerCallbackRequest->getPhoneNo());
        self::assertNotEmpty($registerCallbackRequest->getStatus());
        self::assertEquals(MerchantStatus::ENABLED, $registerCallbackRequest->getStatus());
        self::assertNotEmpty($registerCallbackRequest->getExtInfo());
        self::assertArrayHasKey('ratePolicy', $registerCallbackRequest->getExtInfo());
        // 绑定信息检查
        self::assertNotEmpty($callbackOfPosBind->getAgentNo());
        self::assertNotEmpty($callbackOfPosBind->getMerchantNo());
        self::assertNotEmpty($callbackOfPosBind->getDeviceSn());
        self::assertEquals(PosStatus::BIND_SUCCESS, $callbackOfPosBind->getStatus());
        self::assertNotEmpty($callbackOfPosBind->getModifyTime());
    }

    /**
     * 测试机具解绑
     * @throws UnsupportedBusinessException
     */
    public function testHandlePosUnbindCallback()
    {
        $content = 'data=e9euP4X4CjNWAhYLuhZFrhFQwxJeABYDVclXCU2H%2FpfWXCnFDW7kso1dw60q8wuuQvKqzNRskXkqSq%2BA2uh0s15u91TX88PUktVjkg8CSgLRKxwmqtWHFzQ3AcpVrfX2Ga3RI8wGKuxTO%2Fkgy6RHkS7g3YwjUMAYkGKqI%2BAu1DmrZUNljC0prTwbKjJlPSv0LmOlfFDqfH0JTDTzhRRJToN%2FpnDLBsGwNyl22LkEVq7ntPywWOWafozUwu%2Ffupsixtbw1gYIEyccysDQdDHU3REpBFvUfilXzBjX5BL7A1I66csOZIkvQECom5j6Er0igmb7Un8wy%2BgzkdxlLz6WFo2p1APx05DhyBnYiZhFARv0%2Fxd9vlNo34nJZOXonPIwEQYOmjz12CK4jKKLCIy7wkd%2BkzZsJ3k0EzLTCSvJbQVzqOxDoBMHBQ%2F0VNwI%2FivgUzPNCFR0rQRgy6gHBDcOD6YBui5FUCsUvjgyAZOl6fpWLCTkkx7nvZqMgI1avtA8grollVLujKIquzzmxz18sUYw2oO0wAMWoC%2FMCtEH%2BxDsyzgNKY5weDlUd72OcT3n%2B2gFILRpr9Ip673G50rnhlTls0T5zpGhGVcIuzzd1zGq9FwUa%2BGxswHCzHFlxK38XS%2FwUHIFAWgmPX5KQhzDLwP93fuePwe51M4RB4itssMAf2rXYOX3GUAa2G70rAeVy3HH7DhWp%2BhMKShz3hekAkL9LHQ0wUbloWEHWHgw2BdGh55rxK1s3vLI99mNzXiE';
        $callbackRequest = $this->posStrategy->handleCallbackOfPosUnbind($content);
        self::assertNotEmpty($callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals(PosStatus::UNBIND_SUCCESS, $callbackRequest->getStatus());
        self::assertEquals('2025-06-30 14:02:32', $callbackRequest->getModifyTime()->toString());
        // 绑定时间
        self::assertNotEmpty($callbackRequest->getModifyTime());
    }

    /**
     * @test 商户费率修改成功通知
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfMerchantRateSet()
    {
        // todo shali [2025/6/28] 未拿到移联商户费率修改成功通知
        $content = '';
        $callbackRequest = $this->posStrategy->handleCallbackOfMerchantRateSet($content);
        self::assertNotEmpty($callbackRequest);
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEquals(StrUtil::NULL, $callbackRequest->getMerchantNo());
    }

    /**
     * @test 测试普通交易通知
     * @return void
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfNormalTrans()
    {
        // 普通交易通知
        $content = 'data=e9euP4X4CjNWAhYLuhZFrpOBm%2FkEalJR1vBkq6F8uqS0b4ZvLEAf7akA7BZRoUA3t2HQF7PSr6zMq29%2F8eItUmLDM0xUbCSRDwCwwEE3%2FAjfoeOQldYQsAqSNvPbC7kCrzlGlb0imi%2F5zTEoQAWh%2B7Yaeyvdir6tQ4nTiao7XsGZ5NA2NL11XY%2BNNbM7xi4lMcMYkQxQVMYmEeMwM%2FB3KwNys3LJJ3JLvhnuj256CLkezEEII9KGNf2jvaG5S3sH1PzjBUcRShGNB046u1AAv2O02maH8NKq8kYokuIyHq0slsGsmu30yV1m9B43I0X6tOtbs1hifBH4c81XllrfzfNKs5yitvIw1%2BWhO5YdMH0MaKhvp41SKk0WArEN7qycNQbRN%2BVBnZdgHjEt3pRrxFNvxNjY0t4ROlESz%2BHTRvo9813PUD7GlSlcB7X7jkUacPxPVHkC%2BruWeRArHIjVhqY7%2FCe9UZYEXhR4ixXFxkKSt%2Fwfm2qBmNrlxS%2BjUr2K1EfdEeT4GjygqIb8evIo8SDAuPswk7kFEby4dwVe%2BVoiDUPbWhr%2B%2FMfpu39Gi4xftYnm4yDt2na9lDGVpXFT7MWw4olmeIaRCcVfsL3M8p15B5Y3mUyyCdJmnPMSXkvjzZL%2FzcClE06kRWWmfE0P3ZwmqALywdhuq8EXGrVnisHSmKHEvR4MUasVAguuM0%2Flobkjq6%2F%2FCUx%2FQ1EZvEJI5xV0MRDNUuyRzEugR4pOMAo0NpjzageEqo33B13Ogv%2FVNwQqRfCTWzu3emef%2FKq3%2BgkXRgK52dec3eHaRXzzoZS9aT4mIIXj%2BdtKcFsK8Z5ELsaK9ORKMQHEEpAbQ4%2FDOTX3UqCW31VI%2FXc2rcaflOi88PVROrCuT1z0H0%2F03ItD1Jm56%2B3czD9bEAIvFZXJzHT3BUWNEJxdp%2Bc6AMhITKGjyTbDlDGqrHcGueVJqECbIx5xHOdKPLorc6c3OQCdcX82mGAwLQQnLSqnc%2Fj40e6b44HAQBpvw%2F9WXBmPKMF5syIbjY%2F%2B7Yx4WXXkuJyMzUBKrDQxlt1ElGKYNpkRV2wSXVE8mvpu1ErYad7GECmi';
        $callbackRequest = $this->posStrategy->handleCallbackOfTrans($content);
        self::assertNotEmpty($callbackRequest);
        self::assertInstanceOf(PosTransCallbackRequest::class, $callbackRequest);
        self::assertEquals(TransOrderType::NORMAL, $callbackRequest->getOrderType());
        self::assertEquals('100.00', $callbackRequest->getAmount()->toYuan());
        self::assertEquals('0.60', $callbackRequest->getRate()->toPercentage());
        self::assertNotEmpty($callbackRequest->getFee());
        self::assertEquals(TransOrderStatus::SUCCESS, $callbackRequest->getStatus());
        self::assertNotEmpty($callbackRequest->getPaymentType());
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getMerchantName());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertNotEmpty($callbackRequest->getSuccessDateTime());
    }

    /**
     * @test 押金订单交易通知
     * @return void
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfDepositTrans()
    {
        $content = 'data=e9euP4X4CjNWAhYLuhZFrpOBm%2FkEalJR1vBkq6F8uqRcszr4D2MFnx2oJ%2BHaGIr3t2HQF7PSr6zMq29%2F8eItUmLDM0xUbCSRDwCwwEE3%2FAjfoeOQldYQsAqSNvPbC7kCrzlGlb0imi%2F5zTEoQAWh%2B7Yaeyvdir6tQ4nTiao7XsGZ5NA2NL11XY%2BNNbM7xi4lMcMYkQxQVMYmEeMwM%2FB3KwNys3LJJ3JLvhnuj256CLkezEEII9KGNf2jvaG5S3sH1PzjBUcRShGNB046u1AAv2O02maH8NKq8kYokuIyHq0slsGsmu30yV1m9B43I0X6tOtbs1hifBH4c81XllrfzfNKs5yitvIw1%2BWhO5YdMH0MaKhvp41SKk0WArEN7qycNQbRN%2BVBnZdgHjEt3pRrxFNvxNjY0t4ROlESz%2BHTRvo9813PUD7GlSlcB7X7jkUacPxPVHkC%2BruWeRArHIjVhqY7%2FCe9UZYEXhR4ixXFxkKSt%2Fwfm2qBmNrlxS%2BjUr2K1EfdEeT4GjygqIb8evIo8SDAuPswk7kFEby4dwVe%2BVoiDUPbWhr%2B%2FMfpu39Gi4xftYnm4yDt2na9lDGVpXFT7MWw4olmeIaRCcVfsL3M8p15B5Y3mUyyCdJmnPMSXkvjzZL%2FzcClE06kRWWmfE0P3RhFQM2%2FXEXL9hSyNI%2FcHsDSmKHEvR4MUasVAguuM0%2Flobkjq6%2F%2FCUx%2FQ1EZvEJI5xV0MRDNUuyRzEugR4pOMAo0NpjzageEqo33B13Ogv%2FVNwQqRfCTWzu3emef%2FKq3%2BjFdMBdMx71MjIbH1Z0e%2BQ2z0boFPa1wj7LEAPgNiA8YLsaK9ORKMQHEEpAbQ4%2FDOVr1XltL3UDXmpgIbe8sMGS2SQLHlFBAAbPVei8vy01F4lZlFuN8sA52w9KOJ7k%2BHnT3BUWNEJxdp%2Bc6AMhITKGjyTbDlDGqrHcGueVJqECbIx5xHOdKPLorc6c3OQCdcX82mGAwLQQnLSqnc%2Fj40e5YMdbIqOnodZE32V1fWq0WGVEGu85SV5LqYypiF2FRAmO3cYuv6sV3u62TVjepaAnIZzzanyIa%2BA0KBR3W2bvS';
        $callbackRequest = $this->posStrategy->handleCallbackOfTrans($content);
        self::assertNotEmpty($callbackRequest);
        self::assertInstanceOf(PosTransCallbackRequest::class, $callbackRequest);
        self::assertEquals(TransOrderType::DEPOSIT, $callbackRequest->getOrderType());
        // 押金特点，费率是 1，手续费金额 == 交易金额，即全是手续费
        self::assertEquals('299.00', $callbackRequest->getAmount()->toYuan());
        self::assertEquals('1.00', $callbackRequest->getRate()->toDecimal(2));
        self::assertEquals($callbackRequest->getFee()->toYuan(), $callbackRequest->getAmount()->toYuan());
        self::assertEquals(TransOrderStatus::SUCCESS, $callbackRequest->getStatus());
        self::assertNotEmpty($callbackRequest->getPaymentType());
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getMerchantName());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertNotEmpty($callbackRequest->getSuccessDateTime());
    }

    /**
     * @test 普通交易和流量卡交易合并通知
     * @return void
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfNormalTransAndSimTrans()
    {
        // 普通交易通知
        $content = 'data=e9euP4X4CjNWAhYLuhZFrpOBm%2FkEalJR1vBkq6F8uqS0b4ZvLEAf7akA7BZRoUA3t2HQF7PSr6zMq29%2F8eItUmLDM0xUbCSRDwCwwEE3%2FAjfoeOQldYQsAqSNvPbC7kCrzlGlb0imi%2F5zTEoQAWh%2B7Yaeyvdir6tQ4nTiao7XsGZ5NA2NL11XY%2BNNbM7xi4lMcMYkQxQVMYmEeMwM%2FB3KwNys3LJJ3JLvhnuj256CLkezEEII9KGNf2jvaG5S3sH1MYUJS3KxGBykjXNr9ewwMctkEebjLML4TJUhkBYSLhCx17g7ZraCPsTi1FuY4wuWG9YhCJNCI1H72U54aY7nXD8T1R5Avq7lnkQKxyI1YbjiCUtx3g6UIC3UiZwRErMHEMnIXWtM0fvFYjrXrxihbflw2qr6xbsYmNTltDP%2FKHP34GXHsLNcVU6E1lHxmz%2B2aJHrpgUhURS2DPnbYpDeEasOL7z42T0Jjqhfu2PD%2FSnPdjR4Wk%2FYE7SaaI3%2BCTLd99ST69jz%2FcR0eHUzLQoxxxkd1YunaBRfB8aoF0l3SrtYCdo0zjgeLZ%2B%2FCeS9YJV8d%2BroGN6wEkdz7wyhr%2BFWZx0LHqo4bxU18JS5Dlc%2Bb8pIK4M0zTX4Un3JZXWDYRYcppBQ2V9%2BmtL%2FG3ywqOSzu6g3b1f5muQEieIhj22bwXZjeOI70%2B70hfqkJ4SPbFzNnB%2BEN40j%2B84PXdFs%2FGCoexLMNz1bu7ShYPDHNM6uv9tXwmTxxXk7arfPZMNKX7y%2BRFFBTJN1CCP9zGb%2FClQVsP9RrLLCfXv6h4JtZHJ%2F%2BHKnbC%2FwctE2k7RCVX0k8Wa7CBCKwp60B1CBBqMOKyqRMZouCgxIp6xzl5HyNuE%2FQ83QTsgQjbVeXH0yFUJqxfasN%2BdVY%2FnTch%2F3f07MbhsB6hl70tr54wBRXKqH1Oz0DHlzOKZTf89MmXcr6x9ClA%2BImso014SBdzZmBNvt6bg1AbRpE2iPXaWfnX2Jki9eutZwGJ31HT%2BfQaBy9EjYSmMPf2dpGa0zIKz15GmrmDci0oWgAU8EcA%2BkgfnkWQBRn0oteLJdASO%2FSDR3bpishyv%2F%2F881mq5MgnSH2hptBGLThtgHbOQLN3SLScKDGOlOJWmF8%2B9mBc2004Jb53165yE';
        $callbackRequest = $this->posStrategy->handleCallbackOfTrans($content);
        self::assertNotEmpty($callbackRequest);
        self::assertInstanceOf(PosTransCallbackRequest::class, $callbackRequest);
        self::assertEquals(TransOrderType::NORMAL, $callbackRequest->getOrderType());
        self::assertEquals('101.00', $callbackRequest->getAmount()->toYuan());
        self::assertEquals('0.63', $callbackRequest->getRate()->toPercentage());
        self::assertNotEmpty($callbackRequest->getFee());
        self::assertEquals(TransOrderStatus::SUCCESS, $callbackRequest->getStatus());
        self::assertNotEmpty($callbackRequest->getPaymentType());
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getMerchantName());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertNotEmpty($callbackRequest->getSuccessDateTime());
        // 参杂的流量卡交易
        self::assertEquals(TransOrderType::SIM, $callbackRequest->getSecondOrderType());
        self::assertEquals($callbackRequest->getTransNo(), $callbackRequest->getSecondTransNo());
        self::assertEquals('69.00', $callbackRequest->getSecondOrderAmount()->toYuan());
    }

    /**
     * @test 流量卡扣费回调通知
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfSimTrans()
    {
        // 目前还未收到过移联的流量费推送
        $content = '';
        $callbackRequest = $this->posStrategy->handleCallbackOfSimTrans($content);
        self::assertNotEmpty($callbackRequest);
        self::assertInstanceOf(PosTransCallbackRequest::class, $callbackRequest);
        self::assertEquals(TransOrderType::SIM, $callbackRequest->getOrderType());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertNotEmpty($callbackRequest->getAmount());
        self::assertNotEmpty($callbackRequest->getSuccessDateTime());
        self::assertEquals(TransOrderStatus::SUCCESS, $callbackRequest->getStatus());
    }

    /**
     * @test 测试结算回调通知
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfWithdrawSettle()
    {
        $content = 'data=e9euP4X4CjNWAhYLuhZFrjF4Iy31scANI4ia98g%2F1u38XwTFzjpGdP5Ul5PnVDl36VSeTzv3pX3XX5U31utF5UnJmZ21EkYeJ0smu9KpYXjncWqsx2l%2FC8bUgenFEK%2Bk%2BHKpBkb5fPa8sUiTBisF4bVEtVA3e9Nk5%2FP%2F4MsT7UdfBDoJysWU8P5eS4M4gYLCkbwdE4uQWRonSmqeGC5v8aE%2BQz7KPr9bfkpI%2FsYqkQo43rPQVlut%2FHHD1o1wglHMyQbAcgOXxFSvtRDjQhVdpdRacOXZxd%2BT3jM7dWxHBSCa3MhO1Y0m1Hg2bfmC5%2ByhyUAATiPH4HVlpjldR95NrPbRQpF%2F52J1XZfzdljV%2BmZZ4NpBofchoxlx0SsMAChKV8oIMlJmMeiOKkEh02fKrc3Ff4CPgZjFIjDP99MS1s20UaWrZ1IA2zS0Q9bKzq08WtZLNJ8Nl5w9GTRYr%2FFYZBLrn6pooywVXSr8lHQnmhc%3D';
        $callbackRequest = $this->posStrategy->handleCallbackOfWithdrawSettle($content);
        self::assertNotEmpty($callbackRequest);
        self::assertInstanceOf(PosSettleCallbackRequest::class, $callbackRequest);
        self::assertEquals('3.00', $callbackRequest->getAmount()->toYuan());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertEquals(TransOrderStatus::SUCCESS, $callbackRequest->getStatus());
        self::assertNotEmpty($callbackRequest->getSettleType());
        self::assertEquals(SettleType::WITHDRAW_FEE, $callbackRequest->getSettleType());
        self::assertNotEmpty($callbackRequest->getSettleDateTime());
    }

    //</editor-fold>

    //<editor-fold desc="签名验签方法验证">

    /**
     * @throws ReflectionException
     */
    public function testSign()
    {
        $obj = $this->posStrategy;
        $reflection = new ReflectionMethod(YiLianPosPlatform::class, 'sign');
        $reflection->setAccessible(true);
        self::assertEquals('c497357edc60dbafc3853f8461a4a369', $reflection->invoke($obj, 'cc4agltHMJwrm4eTwK9+6FYfd34U0CTcfNwWP6xHn1c=', 'yya9xts8lqdz7m3n'));
    }

    /**
     * 测试验签
     * @return void
     * @throws ReflectionException
     */
    function testVerifySign()
    {
        $obj = $this->posStrategy;
        $reflection = new ReflectionMethod(YiLianPosPlatform::class, 'verifySign');
        $reflection->setAccessible(true);
        $aesKey = 'yya9xts8lqdz7m3n';
        $sign = '0fb75443bd6a0b2e5fbafcd692a5149c';
        self::assertTrue($reflection->invoke($obj, $sign, '7fpv4gvwr7iZnw16Sq212LGon0IG4v7oymlGufm9m+MeNQvsIsRhpsVDbkv6amT2hyBxts05ZuKRtqMu+XAtNFIhd+3LsjB5zDzU9qCeKFJ5tY4sRIUlUeMZZGHag5eS9lFHzInY34DoF/Kh7MIR36+nO62jFoHciQ16sf/cTDDqCS94hLhqZObqF/uVopYaVZfDiZBAwINipPyo/oRT1yScEGi25H+Xzy/4Kv6VBitc6MVSGa+VRi4G23j4EdLPmnKpz09uer5XW1DSUQrYNCwo9NRMkA16AMCLX3IJGwIO+Eeqdijcb+vQCRhP9Hf7UiF37cuyMHnMPNT2oJ4oUnm1jixEhSVR4xlkYdqDl5L2UUfMidjfgOgX8qHswhHftckKE2z/I5M/FHdqQU1cAuiwb1DkGVyw8MtEAr91WKlVl8OJkEDAg2Kk/Kj+hFPXJJwQaLbkf5fPL/gq/pUGK5L/kFTrkFC+F+kC6eptZDA=', $aesKey));
    }

    /**
     * 测试解密数据
     * @return void
     * @throws ReflectionException
     */
    public function testDecrypt()
    {
        $obj = $this->posStrategy;
        $reflection = new ReflectionMethod(YiLianPosPlatform::class, 'decryptData');
        $reflection->setAccessible(true);
        $decrypted = '7fpv4gvwr7iZnw16Sq212LGon0IG4v7oymlGufm9m+MeNQvsIsRhpsVDbkv6amT2hyBxts05ZuKRtqMu+XAtNFIhd+3LsjB5zDzU9qCeKFJ5tY4sRIUlUeMZZGHag5eS9lFHzInY34DoF/Kh7MIR36+nO62jFoHciQ16sf/cTDDqCS94hLhqZObqF/uVopYaVZfDiZBAwINipPyo/oRT1yScEGi25H+Xzy/4Kv6VBitc6MVSGa+VRi4G23j4EdLPmnKpz09uer5XW1DSUQrYNCwo9NRMkA16AMCLX3IJGwIO+Eeqdijcb+vQCRhP9Hf7UiF37cuyMHnMPNT2oJ4oUnm1jixEhSVR4xlkYdqDl5L2UUfMidjfgOgX8qHswhHftckKE2z/I5M/FHdqQU1cAuiwb1DkGVyw8MtEAr91WKlVl8OJkEDAg2Kk/Kj+hFPXJJwQaLbkf5fPL/gq/pUGK5L/kFTrkFC+F+kC6eptZDA=';
        self::assertEquals('[{"activityCashNo":"ACN0000135911","activityAmount":"99","policyName":"海科买断版"},{"activityCashNo":"ACN0000135912","activityAmount":"299","policyName":"海科买断版"},{"activityCashNo":"ACN0000140178","activityAmount":"0","policyName":"海科买断版"},{"activityCashNo":"ACN0000140179","activityAmount":"199","policyName":"海科买断版"}]', $reflection->invoke($obj, $decrypted));
    }
    //</editor-fold>

    //<editor-fold desc="内部逻辑方法测试">
    /**
     * @test 扫码费率计算逻辑
     * @return void
     * @throws ReflectionException
     */
    function getScanWithdrawRate()
    {
        $obj = $this->posStrategy;
        $reflection = new ReflectionMethod(YiLianPosPlatform::class, 'getScanWithdrawRate');
        $reflection->setAccessible(true);
        // 云闪付，提现费率是正数
        self::assertEquals('0', $reflection->invoke($obj, 'CLOUD_QUICK_PASS'));
        // 移联扫码费率最大 0.03
        self::assertEquals('0.03', $reflection->invoke($obj, 'YL_CODE_LESS'));
        self::assertEquals('0.03', $reflection->invoke($obj, 'WX_SCAN'));
    }

    /**
     * @test 测试银行卡提现费率计算逻辑
     * @return void
     * @throws ReflectionException
     */
    function getBankCardWithdrawRate()
    {
        $obj = $this->posStrategy;
        $reflection = new ReflectionMethod(YiLianPosPlatform::class, 'getBankCardWithdrawRate');
        $reflection->setAccessible(true);
        // YL_CODE_MORE 和 YL_JSAPI_MORE 的提现费率使用扫码的提现费率
        self::assertEquals('0.03', $reflection->invoke($obj, 'YL_CODE_MORE', Money::valueOfYuan('3.6')));
        self::assertEquals('0.03', $reflection->invoke($obj, 'YL_JSAPI_MORE', Money::valueOfYuan('3.6')));
        // pos_standard 使用的是固定费率
        self::assertEquals('3', $reflection->invoke($obj, 'POS_STANDARD', Money::valueOfYuan('3.6')));
    }
    //</editor-fold>
}
