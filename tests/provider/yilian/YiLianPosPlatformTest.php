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
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosDepositRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\exception\UnsupportedBusinessException;
use think\pos\PosStrategy;
use think\pos\PosStrategyFactory;
use think\pos\provider\yilian\YiLianPosPlatform;

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
        $merchantRequestDto->setCreditRate(Rate::valueOfPercentage('0.8'));
        $merchantRequestDto->setDebitCardRate(Rate::valueOfPercentage('0.8'));
        $merchantRequestDto->setDebitCardCappingValue(Money::valueOfYuan('20'));
        // 设置微信费率
        $merchantRequestDto->setWechatRate(Rate::valueOfPercentage('0.37'));
        $merchantRequestDto->setAlipayRate(Rate::valueOfPercentage('0.37'));
        $posProviderResponse = $this->posStrategy->setMerchantRate($merchantRequestDto);
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
        $posSn = env('yilian.posSn');
        self::assertNotEmpty($posSn, 'yilian.posSn is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $posRequestDto = new PosRequestDto();
        $posProviderResponse = $this->posStrategy->unbindPos($merchantRequestDto, $posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

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
        // 绑定信息检查
        self::assertNotEmpty($callbackOfPosBind->getAgentNo());
        self::assertNotEmpty($callbackOfPosBind->getMerchantNo());
        self::assertNotEmpty($callbackOfPosBind->getDeviceSn());
        self::assertEquals(PosStatus::BIND_SUCCESS, $callbackOfPosBind->getStatus());
        self::assertNotEmpty($callbackOfPosBind->getModifyTime());
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

    //</editor-fold>

    public function testHandleMerchantUnbindCallback()
    {
        $content = 'data=dOqXz4uO0UezM8gUIa%2B2MAPwB5Y7gc1LiwUFTZ48ntJBu1Qhmiyvmsft1nmhHRab5uR%2BJV5pxCeNm%2FkR0X1kYLUdSRmZmT98GOmBQUzCcV0K0wpUES6zZot%2ByX1zLlvE3CJTGknCacbDd%2FZrH7YHbEl1jq9AnVzpWAxlfrzMcGRG33q3LFLPYw%2B8xeR4WH5AueMbGbD1oRvyb3z6WxBESObTAUGqoNyTnPSYpTSzvmoDinc1CoCx9tECE%2F2zIvt4isK8p9DIu%2FvHiEeshvIFoijjEluTiuQDS%2FLkVfDoqB5MWxOz2Ju6ds21B%2FQdKhQ%2BnbVNW9QxmQpNaAS3pn0NrvnCEhpXE8kjD9t66WxhATkdH1YJju3kWg%2FKmyxNZZ8LA06W9KXKeszhQ1tG%2FlGAHolgoHcuWmRmZV5Y9lu4aapAsisMQ7oIh36zWEZFi7E5ty5wzclRmTOQCJXt%2FjK2W7ZoSIpKzBPgNA0OhTPJmtz73sTvFa7jK5SP4chmNkSCV9YSzaylpfaiaDZF%2BF29M7JDLRP2lHOWvx17hcnqDVna56zodcyQlAi6quPaYrOr2r5ikhyOBVWDRtdny87SsGnuo7xc%2FgTZzIT%2FjX3l3CLEQa%2FtJegXx5%2BUMAlZesgvK8IpdA5nqcsrur88y6xA57NHCW2YzZ%2FaWUfMIqBT7eQ2tULGGbq%2FLxBno%2B0Qf3EH%2F%2FXSpGQKaTX0Dmyz3QdQ1AjciKE33LuN9F2ojBESIQokRWpVo677mYt7mnjBEFxy%2F1cqi9kp0wJGVZG%2FOYbYK9poiJ0V%2FtMV3PgzA8gmpHZa1UupBQfjLRvGUmKCXhYlI%2B2%2BkV%2FTmwFLDY0XA%2BFX0cLt1gDYHk3jatEoGNtrPl4uGH15UvQaSF0Ct2LNU5agVAXJV0%2BN6zLzX0znC5wAU07hbKzTAzQ%2BF0XMux%2F85dCnThhhDbArrUpfJhSVgR01MHGJHzaRsMBpyhrrilh67v2QuAfYRbphypNgwX8IfFMaOA8AJ%2Bp6Aa0sQk%2Fdvb51%2B9B81yZM%2B72QNSdfMP%2FUikXJLC%2FkhbZHWlOH3RWLqo%2BQocPZ2XYSxXk5xUqfiIdC%2F2STEMLkMFGhNOw3qiPFta9Dr5S89h3lIfso3RkuCB1%2BSHNhtMjpdnCFZlRMjkFz6LIp3IO1nX4ASEKUkG314dtA6RQtczxQ5V0NIV45VHoCLHoYw280UeA2toZL0SC7A2MC5ohGHgnC4t4ABx8fXCxE611latn%2BKW3e1fKbPgbP8FpBx04KnB3cgxQY%2FbO4w7QVu1Ii7yTSwY1Vd0kB23lUwBwcmARqEWwkriiW7EpwPGzpBTP2hhmm%2F3Y%2BSOA%2B9IJ19dkNkRAeFqDdC2s%2BOg%3D%3D';
        $callbackRequest = $this->posStrategy->handleCallbackOfPosUnbind($content);
        self::assertNotEmpty($callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals(PosStatus::UNBIND_SUCCESS, $callbackRequest->getStatus());
        // 绑定时间
        self::assertNotEmpty($callbackRequest->getModifyTime());
    }

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
}
