<?php

namespace think\pos\tests\provider\yilian;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
use think\pos\constant\PosStatus;
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

    private const AGENT_NO = '8133393489';

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
        $merchantRequestDto->setCreditRate(Rate::valueOfPercentage('0.6'));
        $merchantRequestDto->setDebitCardRate(Rate::valueOfPercentage('0.6'));
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
        $depositRequestDto->setDepositPackageCode(json_encode([
            'channelCode' => '111',
            'activityCashNo' => '222',
        ]));
        $data = $this->posStrategy->getPosDeposit($depositRequestDto);
        self::assertNotEmpty($data);
        self::assertIsArray($data);
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
            'channelCode' => '111',
            'activityCashNo' => '222',
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

    public function testHandleMerchantRegisterCallback()
    {
        $content = 'data=dOqXz4uO0UezM8gUIa%2B2MAPwB5Y7gc1LiwUFTZ48ntJBu1Qhmiyvmsft1nmhHRab5uR%2BJV5pxCeNm%2FkR0X1kYLUdSRmZmT98GOmBQUzCcV0K0wpUES6zZot%2ByX1zLlvE3CJTGknCacbDd%2FZrH7YHbEl1jq9AnVzpWAxlfrzMcGRG33q3LFLPYw%2B8xeR4WH5AueMbGbD1oRvyb3z6WxBESObTAUGqoNyTnPSYpTSzvmoDinc1CoCx9tECE%2F2zIvt4isK8p9DIu%2FvHiEeshvIFoijjEluTiuQDS%2FLkVfDoqB5MWxOz2Ju6ds21B%2FQdKhQ%2BnbVNW9QxmQpNaAS3pn0NrvnCEhpXE8kjD9t66WxhATkdH1YJju3kWg%2FKmyxNZZ8LA06W9KXKeszhQ1tG%2FlGAHolgoHcuWmRmZV5Y9lu4aapAsisMQ7oIh36zWEZFi7E5ty5wzclRmTOQCJXt%2FjK2W7ZoSIpKzBPgNA0OhTPJmtz73sTvFa7jK5SP4chmNkSCV9YSzaylpfaiaDZF%2BF29M7JDLRP2lHOWvx17hcnqDVna56zodcyQlAi6quPaYrOr2r5ikhyOBVWDRtdny87SsGnuo7xc%2FgTZzIT%2FjX3l3CLEQa%2FtJegXx5%2BUMAlZesgvK8IpdA5nqcsrur88y6xA57NHCW2YzZ%2FaWUfMIqBT7eQ2tULGGbq%2FLxBno%2B0Qf3EH%2F%2FXSpGQKaTX0Dmyz3QdQ1AjciKE33LuN9F2ojBESIQokRWpVo677mYt7mnjBEFxy%2F1cqi9kp0wJGVZG%2FOYbYK9poiJ0V%2FtMV3PgzA8gmpHZa1UupBQfjLRvGUmKCXhYlI%2B2%2BkV%2FTmwFLDY0XA%2BFX0cLt1gDYHk3jatEoGNtrPl4uGH15UvQaSF0Ct2LNU5agVAXJV0%2BN6zLzX0znC5wAU07hbKzTAzQ%2BF0XMux%2F85dCnThhhDbArrUpfJhSVgR01MHGJHzaRsMBpyhrrilh67v2QuAfYRbphypNgwX8IfFMaOA8AJ%2Bp6Aa0sQk%2Fdvb51%2B9B81yZM%2B72QNSdfMP%2FUikXJLC%2FkhbZHWlOH3RWLqo%2BQocPZ2XYSxXk5xUqfiIdC%2F2STEMLkMFGhNOw3qiPFta9Dr5S89h3lIfso3RkuCB1%2BSHNhtMjpdnCFZlRMjkFz6LIp3IO1nX4ASEKUkG314dtA6RQtczxQ5V0NIV45VHoCLHoYw280UeA2toZL0SC7A2MC5ohGHgnC4t4ABx8fXCxE611latn%2BKW3e1fKbPgbP8FpBx04KnB3cgxQY%2FbO4w7QVu1Ii7yTSwY1Vd0kB23lUwBwcmARqEWwkriiW7EpwPGzpBTP2hhmm%2F3Y%2BSOA%2B9IJ19dkNkRAeFqDdC2s%2BOg%3D%3D';
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

    public function testHandleMerchantBindCallback()
    {
        $content = 'data=dOqXz4uO0UezM8gUIa%2B2MAPwB5Y7gc1LiwUFTZ48ntJBu1Qhmiyvmsft1nmhHRab5uR%2BJV5pxCeNm%2FkR0X1kYLUdSRmZmT98GOmBQUzCcV0K0wpUES6zZot%2ByX1zLlvE3CJTGknCacbDd%2FZrH7YHbEl1jq9AnVzpWAxlfrzMcGRG33q3LFLPYw%2B8xeR4WH5AueMbGbD1oRvyb3z6WxBESObTAUGqoNyTnPSYpTSzvmoDinc1CoCx9tECE%2F2zIvt4isK8p9DIu%2FvHiEeshvIFoijjEluTiuQDS%2FLkVfDoqB5MWxOz2Ju6ds21B%2FQdKhQ%2BnbVNW9QxmQpNaAS3pn0NrvnCEhpXE8kjD9t66WxhATkdH1YJju3kWg%2FKmyxNZZ8LA06W9KXKeszhQ1tG%2FlGAHolgoHcuWmRmZV5Y9lu4aapAsisMQ7oIh36zWEZFi7E5ty5wzclRmTOQCJXt%2FjK2W7ZoSIpKzBPgNA0OhTPJmtz73sTvFa7jK5SP4chmNkSCV9YSzaylpfaiaDZF%2BF29M7JDLRP2lHOWvx17hcnqDVna56zodcyQlAi6quPaYrOr2r5ikhyOBVWDRtdny87SsGnuo7xc%2FgTZzIT%2FjX3l3CLEQa%2FtJegXx5%2BUMAlZesgvK8IpdA5nqcsrur88y6xA57NHCW2YzZ%2FaWUfMIqBT7eQ2tULGGbq%2FLxBno%2B0Qf3EH%2F%2FXSpGQKaTX0Dmyz3QdQ1AjciKE33LuN9F2ojBESIQokRWpVo677mYt7mnjBEFxy%2F1cqi9kp0wJGVZG%2FOYbYK9poiJ0V%2FtMV3PgzA8gmpHZa1UupBQfjLRvGUmKCXhYlI%2B2%2BkV%2FTmwFLDY0XA%2BFX0cLt1gDYHk3jatEoGNtrPl4uGH15UvQaSF0Ct2LNU5agVAXJV0%2BN6zLzX0znC5wAU07hbKzTAzQ%2BF0XMux%2F85dCnThhhDbArrUpfJhSVgR01MHGJHzaRsMBpyhrrilh67v2QuAfYRbphypNgwX8IfFMaOA8AJ%2Bp6Aa0sQk%2Fdvb51%2B9B81yZM%2B72QNSdfMP%2FUikXJLC%2FkhbZHWlOH3RWLqo%2BQocPZ2XYSxXk5xUqfiIdC%2F2STEMLkMFGhNOw3qiPFta9Dr5S89h3lIfso3RkuCB1%2BSHNhtMjpdnCFZlRMjkFz6LIp3IO1nX4ASEKUkG314dtA6RQtczxQ5V0NIV45VHoCLHoYw280UeA2toZL0SC7A2MC5ohGHgnC4t4ABx8fXCxE611latn%2BKW3e1fKbPgbP8FpBx04KnB3cgxQY%2FbO4w7QVu1Ii7yTSwY1Vd0kB23lUwBwcmARqEWwkriiW7EpwPGzpBTP2hhmm%2F3Y%2BSOA%2B9IJ19dkNkRAeFqDdC2s%2BOg%3D%3D';
        $callbackRequest = $this->posStrategy->handleCallbackOfPosBind($content);
        self::assertNotEmpty($callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals(PosStatus::BIND_SUCCESS, $callbackRequest->getStatus());
        // 绑定时间
        self::assertNotEmpty($callbackRequest->getModifyTime());
    }

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
    //</editor-fold>
}
