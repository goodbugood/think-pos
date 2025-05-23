<?php declare(strict_types=1);

namespace think\pos\tests\provider\lipos;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
use think\pos\constant\PosStatus;
use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosActivateCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\dto\request\CallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosInfoResponse;
use think\pos\PosStrategy;
use think\pos\PosStrategyFactory;

class LiPosStrategyTest extends TestCase
{
    /**
     * @var PosStrategy
     */
    private $posStrategy;

    protected function setUp(): void
    {
        $this->posStrategy = PosStrategyFactory::create('lipos');
    }

    /**
     * @test 测试设置商户费率
     * @return void
     */
    function setMerchantRate()
    {
        $merchantNo = env('lipos.merchantNo');
        self::assertNotEmpty($merchantNo, 'lishuaB.merchantNo is empty');
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

    /**
     * @test 测试商户汇率设置成功回调
     * @return void
     */
    function handleCallbackOfMerchantRateSet()
    {
        $content = '{"serviceType":"CUSTOMER_LAST_RATE_NOTIFY","data":"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","appId":"62261998","sign":"WythogDhr2LziHdQuxTjd3lsjmlSBjX3hcQtUQ+HVrogXD+xsazOuQyEhyvBNL5YSmUSMDZn1Qeiltb7RO+ipCfq+qxkbmPa5Gg79K240vdAACEwy6cpMpvvvZdpZ8eBoFGXUV1crTk4MeTSK2ERLaWXPvbq2hgsMmA3odV3RmZF8HsFhGDpNnxgxgXVVOFBtnKTLbbmwkWZSnMjSWKuxmGoTv189TK6UoJ0EWnbOe1bjHFGMIHJwiLUbfjtPSldysTlYhSQWwQc3zKisJwMeirkXQY9q8tnoHizHvW/rjjUijqRlg8j7cT8tyv3ywmm3jItoFuqLn74K2Tu3YA3gQ==","encryptKey":"5BrZVeDPloaRHf1MJsXL/OfMhOE5Aurab0aHTuybZyu174NtUlE48AB5oUUmI/BCiBFCx6/Z6cJNPPbADzLa7lcAivJoUYlbGjoXpO6myX6lZ8uKnNNKNLGArZIHDQKGOiqxVBxC/4cTmTyRaDU5/t4v1VroIo102PW6ZF2JUovu6wbGFHoOZwOY1owrSfWs30hOy9EE3c1umD+BmibxVj6a6hmR8dKo4OsJsbAcu8y7IcmWRFS8nFZKf3eYNtHq7uaTn8uCdfViVJVVJgj7K0oDH8s5kHwid7nRGF+JFWIpAHdxcXA93wrbUM5cE4BASIzJj473fMMu6F+FmB2Ngw==","timestamp":"1747901856577","responseId":"JZY2216102527490"}';
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(MerchantRateSetCallbackRequest::class, $callbackRequest);
        self::assertInstanceOf(Rate::class, $callbackRequest->getWechatRate());
        self::assertInstanceOf(Rate::class, $callbackRequest->getAlipayRate());
        self::assertInstanceOf(Rate::class, $callbackRequest->getDebitCardRate());
        self::assertInstanceOf(Money::class, $callbackRequest->getDebitCardCappingValue());
        // 贷记卡费率和提现手续费
        self::assertInstanceOf(Rate::class, $callbackRequest->getCreditRate());
        self::assertInstanceOf(Money::class, $callbackRequest->getWithdrawFee());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * 已经绑定商户的 pos 不允许修改 pos 费率
     * @test 测试设置 pos 费率
     * @return void
     */
    function setPosRate()
    {
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posRequestDto->setCreditRate(Rate::valueOfPercentage('0.57'));
        $posRequestDto->setWithdrawFee(Money::valueOfYuan('2.5'));
        $posRequestDto->setDebitCardRate(Rate::valueOfPercentage('0.57'));
        // 必须设置 20，否则 {"code":"98","msg":"机具默认封顶值需大于本级代理成本","success":false}
        $posRequestDto->setDebitCardCappingValue(Money::valueOfYuan('20'));
        // 微信扫码
        $scanRate = Rate::valueOfPercentage('0.37');
        $posRequestDto->setWechatRate($scanRate);
        $posRequestDto->setAlipayRate($scanRate);
        $posProviderResponse = $this->posStrategy->setPosRate($posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试设置 pos 押金
     * @return void
     */
    function setPosDeposit()
    {
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        // 押金套餐码：0 - 免费，1 - 199，2 - 299
        $posRequestDto->setDeposit(Money::valueOfYuan('199'));
        $posRequestDto->setDepositPackageCode('2');
        $posProviderResponse = $this->posStrategy->setPosDeposit($posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    public function testGetPosInfo()
    {
        $posSn = env('lipos.posSn');
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
        // 检查押金
        self::assertEquals('199.00', $posInfoResponse->getDeposit()->toYuan());
        // 终端绑定商户后，不再返回终端费率
        self::assertEquals('0.59', $posInfoResponse->getCreditRate()->toPercentage());
        self::assertEquals('2.00', $posInfoResponse->getWithdrawFee()->toYuan());
        self::assertEquals('0.60', $posInfoResponse->getDebitCardRate()->toPercentage());
        self::assertEquals('23.00', $posInfoResponse->getDebitCardCappingValue()->toYuan());
        self::assertEquals('0.38', $posInfoResponse->getWechatRate()->toPercentage());
        self::assertEquals('0.38', $posInfoResponse->getAlipayRate()->toPercentage());
    }

    /**
     * @test 测试设置 pos sim 流量费
     * @return void
     */
    function setPosSimFee()
    {
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $simRequestDto = new SimRequestDto();
        $simRequestDto->setDeviceSn($posSn);
        // 套餐这个固定？
        $simRequestDto->setSimPackageCode(json_encode([
            // 通讯服务费阶段
            'simRuleIndex' => 1,
            // 通讯服务费扣费起始天数
            'beginDayNum' => 7,
            // 通讯服务费档位
            'simPhaseIndex' => 69,
        ]));
        $posProviderResponse = $this->posStrategy->setSimFee($simRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试验签和解密
     * @return void
     */
    function verifySign()
    {
        $res = '{"appId":"61261936","code":"00","data":"d3HVQWxPTQIRNxce9oXU3ORrbp4DRaSYCIqQXQ0Mg8zMtO5FpOTnRRfSIzdPF4xIT8HQ8YItHmG8xXp9Oy5dTOHCVC/+wyDMxybSJUXxhsccQWkMHTnyN0XqyuupC3ivSW3da6RI2ET1ouF+CA43lfQf5eGt1qpxm9oI0rTvUv48WjMK5ojXjGub3VL/jMC4ld3RHUGjQMpMjuCi0Cri/NqI+j77YBMqmz3vxhe9cWiSjyxYSNSwyaG6wCdXcPzXStLKeAcG69EEnyql0TqygNk5RCFVbt2n6196wAdK5OFNxpKpFX5AyoE58dTrlLNYTXTNEgtrdbL6ieMQ979FfL/hE7tYPCK6QP4cAGxJ4ThKuq41AtasXhhTjkl/dVYoxK3t83O27cJn5CFSpzVc3ZUmFTVmS8T3xmIpHtqYykIV9iZfQuhpcE1XWhpSkjnVaQr5aVJVU+efzdrWvsM74DRsfBCZ4sld1w+eHqUT0F2iaGce8Sh6o3pcnAqfB6wDNd2P6UlWOzWJIw2nyNWUpIZ15y0gjNxj/WzzM2R7AY6IYO+vWf4crSkiHwQFHfKdROFK4O4YATIIUyzoWBBmEytwklRB0VLth/CrrcHrpCfaiOhKNzvBShtbmwIfxpDa0mP1dGnR0xT0cb17KCHFIqE/7RNb/LTvGshKews6EkTcdyoPh0D4GqDQMd4Reigwh+LRxNXcY96ElGTE6ugfiOScvgFIZoKeym4igVYPFimK8DFOMJUc7mQRs8nV3M1fVAOGAALUVQImWzftldl31Sofz2IfViPYBfIrD38burJ5ubpbn63MUtjtrhbWh0SpRlMLu3l+Akz//rC+cN4KBUioi7mUIkKUAGfe/9Kk1cIVMiHXG9zLX5t2+DFbmQjwtf/EXB6KMpZpfV3WBizyUHr8MfgGvhW2Wwbs3Nr1bKeC7ihksEBfqy8SUX1GLYSHsD6XaWtwQ7YksoidKrqqkzDbqbpKrabNx05yOozG0iezLT/e6azjEsk8Cq4u1MBTP14o1itKNlWI3PYzZdHJw41PZF4j9vDXbtooqMRnjCJuNkojZJCJPxBNr+VtjYaIZezZxysoEHOm916H80YO5LA89cqXaisknyVq18nHLn2i+V3zmugJc4aM+ROTqw8eQwTq4ruW3Lx+xgLRTUSoM7mhoakCexFZpJMu3QqLHCzWaM/ygyF8Dy0ImNIrun9sZezZxysoEHOm916H80YO5LA89cqXaisknyVq18nHLn2i+V3zmugJc4aM+ROTqw8eQwTq4ruW3Lx+xgLRTUSoMx1M+GguZ+L53uboxN1c6gl3dkWTQJHNVWRnZ50KwkTjZezZxysoEHOm916H80YO5LA89cqXaisknyVq18nHLn2i+V3zmugJc4aM+ROTqw8eQwTq4ruW3Lx+xgLRTUSoMyCC2H5HDVaEJ3XhbgAHy1MJGmtt3YSHVe2zdlG+XqADSKiLuZQiQpQAZ97/0qTVwhUyIdcb3Mtfm3b4MVuZCPC1/8RcHooylml9XdYGLPJQaRCRpCc+A1F4aP0mcS7NlP/isI4ja89mj+1GiQSWYMv8ZiMpC/n9EvRCYe9IRrJR45uCLsYnkDiVlqTeSzhevmhglVnzqZ44IMetT0GmKIQSZEdLdbrLfDjHsUO8/zGWbCYRPdYiDC/8TKJ0hTT+WA==","encryptKey":"oI6YkvYsPG2CAK2DcGh4yil7LEHpdwlDqOBNOOvaE8E5yCDICCwAQyL9lWvilKw+M2czcLm9sYBnqZSWzMd9cstXy1YEytB7HmEkWB+NQNIbmTQi4EHHGioMnkeaVNYDTeJbu8ulGbLw2I8mpRwa3G1Htxob6HlH7HTcawS02dcRUVdNQ24Mnpp9s0mMacLKMUWHMsSuE6kYtjdD5HPW34Ln/P8TdXkhkmpg1ewYuKmsHQz55l1XIwkzJbkOekkfK2V4ln8hsH3n5+HHIIGjVzvxlHHH59QSpIyXZjp7axVLN/g/SRHIpH83+vk9KkbgI7cp3/pTKVYSsuEAojZgpA==","msg":"成功","responseId":"JZY0815093727234","sign":"OJXh+S8itEfswr2+yQS+skYGog60uCqVa7x+VqkN5wnLQbIaYEcxv2O+OsVvmsvJObGn8KkY4wZKV1D8r7d+Pn2OF9+jLREUWb2vANeZrnMUguwhfACu54+oNyT2zNk11Cut33433S8jSfyCjQeEhblbGr67l1biGuXiNI4vX0z5I1tvejHzedjKBHMW8PRuGy/II8vPmqZKVP7WZvKEHcMorFLHAwB3SQ5d+6nzfvO9bULHqoeep4T+EX5AxC1ZtVmOcVSjHzQeoPwWmrNsbTHEHMm5uT5Ye+Rs6lBRRuUa0bLYlS1jti1u/K30KG4sJ7d+4kruNxJOF8bEf5UcMA==","success":true,"timestamp":"1746689403693"}';
        $data = json_decode($res, true);
        self::assertTrue($this->posStrategy->verifySign($data));
        $decrypted = $this->posStrategy->decrypt($data['encryptKey'], $data['data']);
        $data = json_decode($decrypted, true);
        self::assertIsArray($data);
        self::assertArrayHasKey('materialsNo', $data);
    }

    /**
     * @test 测试商户注册成功回调
     */
    function handleCallbackOfMerchantRegister()
    {
        // 自己模拟的回调信息
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $merchantNo = env('lipos.merchantNo');
        self::assertNotEmpty($merchantNo, 'lishuaB.merchantNo is empty');
        // 立刷自己的回调
        $content = '{"serviceType":"CUSTOMER_INFO_REGISTER_NOTIFY","data":"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","appId":"62261998","sign":"EMFr4FusHZ4jALiARaKUsycsJx5eSETxZubK+ORypkiag2MSHRkzqdWpWagKPtBkWogqgo4ulU3gNB1qDj/Uop5qtFIlvOXsPefVTB/Ww5/Brk0aJ3kn0ioO7KhbTIqoIwl6PboRmj2AS7H+MzJl0EEeIsqhJo02IvSfJyeRyIT4H+MdWiESXQIycHXIj6t51bkg8ONuSqpQsfGiHvF0JOhv8iO+NlblABAg2YuSbyYh9pFyJeYj9uatsOu5/UZkjLf8ApYfqbaY7C6MjqnrYmYhQtIktdbb0IO6DYLgJ+fak9B5J9jrYqkmqeaqvBMJmYtl+FpRIgoSxsqjM0A1GA==","encryptKey":"E+pHYw9xOGAjfTaUEQeKxgoEw6hBiuERaIpf0mRa1LBFI6GLSZR54i8E90VBG1v6dBFiBn/mv5EqY+KfltyWZ5tL89iU2Y9rXOThP/jWugVtjUeLeTwLykiyWwqg/l3JGuQOZ/ycWMS5MKm9PPH3UvAAkqEbO2DzIwoDmUay7UReG1PKEO1vdaQ5j5tsTF+f7OtZ9ji3Q45O8Q741dk8x3kT1dqll4oCsjEDU64IyFXD9JMkvtd3aAPR8BkxEH4YP2wlaN7L/AV1mrD+P3JwAn9Fkzbn3BF8/kz+lR12/8D83U2jg3tj58bghVk1Rl9ETz4OMQfOD9mLZGBR2JiAEA==","timestamp":"1747812714280","responseId":"JZY2115212280834"}';
        // 自定义回调
        // $content = '{"serviceType":"CUSTOMER_INFO_REGISTER_NOTIFY","data":"{\"customerInfoNotify\":{\"agentNo\":\"61261936\",\"bussinessName\":\"\u7504\u9009\u94b1\u5546\u4e03\u5341\u4e94\",\"category\":\"5712\",\"categoryName\":\"\u5bb6\u5177\u5e97\",\"createTime\":\"2025-05-07 16:48:32\",\"customerName\":\"\u7504\u9009\u94b1\u5546\u4e03\u5341\u4e94\",\"customerNo\":\"825814187159639\",\"customerType\":\"SMALL\",\"idCardEffectiveBegin\":\"2018-01-03\",\"idCardEffectiveEnd\":\"2035-01-03\",\"idCardName\":\"\u8c2d**\",\"idCardNo\":\"450881********7747\",\"idType\":\"ID\",\"phoneNo\":\"150****1075\",\"registerAddress\":\"\u5e78\u798f\u5bb6\u56ed1\u53f7\u697c3\u5355\u5143\",\"status\":\"TRUE\",\"unionOrgCode\":\"1611\"},\"customerSettleCardNotify\":{\"accountName\":\"\u8c2d**\",\"accountNo\":\"621700********1850\",\"accountType\":\"TO_PRIVATE\",\"bankName\":\"\u4e2d\u56fd\u5efa\u8bbe\u94f6\u884c\",\"cardType\":\"DC\",\"phoneNo\":\"135****8227\"},\"rateNotifyList\":[{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"WECHAT\",\"rateValue\":0.55,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"ALIPAY\",\"rateValue\":0.55,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":20.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"POS_DC\",\"rateValue\":0.55,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"POS_CC\",\"rateValue\":0.6,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"POS_DISCOUNT_CC\",\"rateValue\":0.6,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"POS_DISCOUNT_GF_CC\",\"rateValue\":0.6,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"POS_DISCOUNT_PA_CC\",\"rateValue\":0.6,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"POS_DISCOUNT_MS_CC\",\"rateValue\":0.6,\"rateVersion\":\"82581418715963920250512160951867\"},{\"cappingValue\":0.00,\"fixedValue\":0.00,\"payTypeViewCode\":\"UNIONPAY_DOWN_CC\",\"rateValue\":0.55,\"rateVersion\":\"82581418715963920250512160951867\"}]}","appId":"61261936","sign":"ViaUkJQiRBqIN4lpbGk+9KMw9dpFW1iy4AcN\/zvQDu0k+08hQKMSJRnYE8e9AsjdkCXBPyuGhAsfYTsXDbO5DI5iBekGmjBFxNZfhUlzDkph1EGH3AItyVbPDkmO\/k0Wi2Ezjavn5HHo6lUrZJA\/rvOSowwWsXitDpDAda571eMNVm1U\/y5IfVLMaTzKr4yEm2PMgo2J69CMyA1i2cWPWu0UMQFxWyi0hFP+tHGbHkC6E3Itq5QAW08GxMn3En3Xf+kob9r1QvLP0AEAsXVkvJZpjm0LdJe2m+8dfvWZ2UkIRsYnPVIg3jGB8MvDqeFVD6lzGQqvECXSDmQ6rEEhzw==","encryptKey":"HD8D1XNkBCiLmplL","timestamp":"1747102515253","responseId":"JZY1310595823617"}';
        // $data = json_decode($content, true);
        // $data['data'] = json_decode($data['data'], true);
        // $data['data']['customerInfoNotify']['customerNo'] = $merchantNo;
        // $data['data']['customerInfoNotify']['customerName'] = '测试商户';
        // $data['encryptKey'] = '1234567890123456';
        // $data['data'] = $this->posStrategy->encryptData($data['encryptKey'], json_encode($data['data'], JSON_UNESCAPED_UNICODE));
        // $data['encryptKey'] = $this->posStrategy->encryptPassword($data['encryptKey']);
        // $data['sign'] = $this->posStrategy->sign($data);
        // $content = json_encode($data);
        // exit($content);
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(MerchantRegisterCallbackRequest::class, $callbackRequest);
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getIdCardNo());
        self::assertNotEmpty($callbackRequest->getIdCardName());
        self::assertNotEmpty($callbackRequest->getPhoneNo());
        self::assertEquals(MerchantStatus::ENABLED, $callbackRequest->getStatus());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 绑定
     * @return void
     */
    function posBind()
    {
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $merchantNo = env('lipos.merchantNo');
        self::assertNotEmpty($merchantNo, 'lishuaB.merchantNo is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $merchantRequestDto->setMerchantNo($merchantNo);
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posProviderResponse = $this->posStrategy->bindPos($merchantRequestDto, $posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试 pos 绑定回调
     */
    function handleCallbackOfPosBind()
    {
        // pos 平台官方回调
        $content = '{
    "data": "00HcJ4VNDg3hdqUXODwkmqsRHznriWtIt+hf6UexoOXZEW0zfDlzSP8a0cllo4SFSA/G6U5R6uDveFPbOxtVE8EBdLRDbp4jS8dIMH8Q7iZtEKdGFzYFMkej+hM20mho2jHTFd582yLtVgvT06CyDrC4Gm7QMesRXQ4xSbuZ/oRFNtGXHBC+QGX/xMvQZv00HpT2W5PC1GXSwnO5IvYk+A==",
    "encryptKey": "yR+Z5u8cGyCIAZfTUYTXfI9shleWiTq2neWyIqz3YQN4gL7rKGH5+a2o5LhMWmD6mXNJ0mHZ9Y/sBvz903JQhBymEolvraqviRI7dpS0bAS6BNi1E4y/zuqQmDv12W7J0qiTL7svgocX/jv+dssZZqbDa7N3uvtTovr5c6/gwnaJRcei9B3mDuh8NmICsqeKnJGQAxUwd5s7l8sTtgqMiYbMY9ncpYtXVwIJarR24BG+/iRZ6BFhKwkbnWd76UABRyExgWS+9KuPj3O4zqwe7lBFaMOxmTv/5zyc5AiWcxDns06vnNPGrAo760Jzpg7kH0lKmaSvTrWGy6FfO3TZnA==",
    "appId": "62261998",
    "timestamp": 1747812714525,
    "serviceType": "MATERIAL_BIND_STATUS_NOTIFY",
    "sign": "YX7JtpFzV8Bya5bdKo/el3s0cqxmqcQGn3dREnsJLn+Tl36Vowf9q2ETRCuKLMTQ0A0voU2dif4YYsk7uRE1Gf1ZwtRTQ1f8ifdOae4jqeqLk7wuMkFA7S/aNCWMhT9ntX3U1H8yhcRu1JZFhGNEcwAxCI0Xwo0A0tPzVSvnR/F3HZC1hUZfAqC/iP8hecxv+eTs43DNpYkvVK2tJwY/lgwW21OU8uKnsqDllI15/zp2cPuZ7aNI24KQ4VW1DBpiQJN+LzEvbpan0K2nlch/GfI7sBJm3OjsOE4qrq9wi2fhjqTNGbCFzEELQLuVkJP+zD5Ej3rMYDd5Vdt21lo5WQ==",
    "responseId": "JZY2115237292545"
}';
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosBindCallbackRequest::class, $callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertInstanceOf(LocalDateTime::class, $callbackRequest->getModifyTime());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 解绑
     */
    function posUnbind()
    {
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $merchantNo = env('lipos.merchantNo');
        self::assertNotEmpty($merchantNo, 'lishuaB.merchantNo is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $merchantRequestDto->setMerchantNo($merchantNo);
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posProviderResponse = $this->posStrategy->unbindPos($merchantRequestDto, $posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试 pos 解绑回调
     */
    function handleCallbackOfPosUnbind()
    {
        // 自己模拟的回调信息
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $merchantNo = env('lipos.merchantNo');
        self::assertNotEmpty($merchantNo, 'lishuaB.merchantNo is empty');
        $content = '{"serviceType":"MATERIAL_BIND_STATUS_NOTIFY","data":"YJl2DOkiGyu+gCGXomxPxOr6SU25hFzXO0Huo1I8A7z9ks8IlrbVz+ZpmHcV/QSGCKTZpxAWoNYMjrS94ch3IWqgXMGc+uQxsCvRXbz8WpaHvCsGM3YRENNgTPoJlSkYZXiwX60F7ZQIiOINDJCw+bJJ17hlpJLuiJUP0s9CBJc=","appId":"62261998","sign":"loJJrXrsN92W1WxRyLAe5pqK69aTPeU55I0wATQCP6q2WRf6f1Hi+LEoNHiZJhdyuaZWH5jVQcBTlWCthGSNgOgkSsOhzzg1JZeYsBdHc98jM/f8ISQIJj4TQzMKG6l/cRoxlAtUT9vF3dBRg8c8H+hxbHNZ75X13r5ImGPRfM9VtxTruQvv4dBUDoXGEoi98Gw7TzHHTdhZyfTkGErRsSitrzLneKVUdXb8yG1vTsBugX3pAkR2qrfrDh1x8vvZ0712sNaVrCDVLDSr8dWnl3p2Aox2m3oz0fXouYsXD/gVeQbMoFEU4y5RZwsayiYxU5ZP1doUFltUf8JMsVMRCw==","encryptKey":"MDA8GK4enkLeMGrCspQuTFz4CmkpxtEJgwWlTb8fgzuGLRCwIy6vVFJBWgUS3z3xo0faSJnvF4t539zi+IF1zWmCf5bcerbXatCLz9saBHTUjUIVvu3x77zj/6NuSbvfepCmdctzbQcxmcRCZHO4sWpM/kCc27z7cW00mFqg8q4dnr4K0C4teguipExG3QX6UHsqniGGZqt6e+T0ERysE0Rff1yhGltXTNhhyJpruNfepjV9fezgfY9TRC+9g2PUSdjMpfL8MosvoKWabsNU1uxZfBDqT5ZQ7K2VanJkgSqqx6sIK6DDCa4PtqVivGl38SD2HfFbfgaBpfdXKXNtGA==","timestamp":"1747820674549","responseId":"JZY2117001990145"}';
        // $agentNo = '11111111';
        // $data = [
        //     'agentNo' => $agentNo,
        //     'customerNo' => $merchantNo,
        //     'materialsNo' => $posSn,
        //     'bindStatus' => 'TRUE',
        //     'changeTime' => date('Y-m-d H:i:s'),
        // ];
        // $password = '1234567890123456';
        // $params['data'] = $this->posStrategy->encryptData($password, json_encode($data));
        // $params['encryptKey'] = $this->posStrategy->encryptPassword($password);
        // $params['appId'] = $agentNo;
        // $params['timestamp'] = time();
        // $params['serviceType'] = 'MATERIAL_BIND_STATUS_NOTIFY';
        // $params['sign'] = $this->posStrategy->sign($params);
        // $content = json_encode($params);
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosBindCallbackRequest::class, $callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals(PosStatus::UNBIND_SUCCESS, $callbackRequest->getStatus());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 激活成功回调
     */
    function handleCallbackOfPosActivate()
    {
        $content = '{"serviceType":"MATERIALS_ACTIVATION_NOTIFY","data":"7Tlc380qmb24AlAWNaHAe3/q+8QPnVMP0kWVPUT5OcQGfWDof+5ChV3BwAwClwzma6eZTrp4bH9G/44VOQzoNsyRH8ISgJczkOnualgsuYuOS/O3sHTWbp0s+jVdvsPxv2WBU98OfNAUrAapODu6SMXmb1CIPvRW4/0cT2LPHT5EIA+IMofothdx6w9MdL1B2us4wMno8LUIlr8LtDQWCDc/mjSB/SUKJ4HQKhxiYn4=","appId":"62261998","sign":"QCqkQrxhPKeWq9qGoF3TA+gox+8m/Nmh1UOUI4LwYSLBRXKm0EsAhHbg9niibjjuPFbvHSl3bSNDHiPgycWs6lapbk2xSPmmFD2XimpUiRQGq6icg/vlVSuVQpmIT7iVyFBCwEoqAjfyvKonGZ/QGjPYz5xKDALm3Nx17Pd/NK5CaZ35dDkjWxY1jpca+alj8HtYI4R5QJKGh6fSXz0fgNBGVU14Zl4zs+mZhNkXgN8R7kbYk7RzZuRSKn3geFmui/tzytHTlULXOyvoBUoZGmIQFIQpWApThunh20Q64+bfCbPYYM/euhnS/rII9zBWMDYkpniVrRxHO1qfB/Hg4A==","encryptKey":"ItvvgzufI1XG1UvEBf/vih41ssNSQrKTCiZUdZ/lhc0iYsBQc/khxERUfmiaA/voIlTXEng0OuEjvJT+Q7B/dQJVS7KFK9xhABrFfXn7QKTyox6dxYh/tBQfXm6HWbDAjHuhDPje7/dIfhfRPdvww9t8dYcJqNh2VrkHW7Kvx3DtnfoBsWvgBPKGp9q0QonRmJ/Dzxyb/B98acSrlvTB0VwiEDqzqyPp+z2Z4J57h1V2E4hhDS79bGPLhWKxVizyqxXuykKJi4ZQ100LnS7TIEMsNphrgCNlYKNYar+Ozs1QYDAW8c2/9HuTQmeGaGbRWDO9GIXfveXnQooKpPUCBg==","timestamp":"1747885302440","responseId":"JZY2211017927169"}';
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosActivateCallbackRequest::class, $callbackRequest);
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 交易成功回调
     */
    function handleCallbackOfPosTrans()
    {
        // 自己模拟的回调信息
        $posSn = env('lipos.posSn');
        self::assertNotEmpty($posSn, 'lishuaB.posSn is empty');
        $merchantNo = env('lipos.merchantNo');
        self::assertNotEmpty($merchantNo, 'lishuaB.merchantNo is empty');
        // 力pos回调
        $content = '{"serviceType":"PAY_ORDER_NOTIFY","data":"M+fETCV0yTPqz4jsmdzSJ8Om+/D8RL/zm79Ld4oXA6kYCnAuuiRkARBmIkMyrzBXkv/3ME1Nuc2/ht9TICfrh9A1gGtcGBf+x1rm1AVIRXQTvj05vj8ELlUU0WkxGIOdLS52q5aMQoR+j33/9vGIwN4E3zch6hP/eSCqK7C2sBXs45E02lIj93Nb41j6ruAWLkULGDI/N6s5tkXbsCBOXHBTWu7hRa6mYCO4M58FeRIfg4wSXAj0k/5piiQAe/0CfnwIpO3E06+12IKh8x+KgR1fDuKxX7alzCnxXEc4S/A8vJbq4A/agSjVj6BywGTV6JXRirxsGOU7gI99NnoU8Dd+YZzVYp0/PJrdItOPcHO5p809wOX7CEEl4sLZ/qqRtjeEtdqC3sQKeFCe/IGA0SVdExf5+NPjfoyYtQrgF/Ir8OMkqdP2wuRKs5GYUMKw2AdFljjQvoUjeklVL1l7wr+mHu3PRK6AFr8DxMNEdCekvoAc3W8IPjEJjXwvQFRXewB/E0W+3VbVJHOB8By1Qmtf7AU13U6yBkL6JCcnOdGIWsYin6VNVYq8ne3fj3arHCOTjf7R2QRfZkXz/2Z1tAN1n7SakYbfqBYV7sOvC0gH9hdlsFoC2G46ZiAVzapVz15+gDPHF3o7IKfYhqoTwE33dgi5udGnzDlC8QQTSTk=","appId":"62261998","sign":"NF5nG5FmTSVlowds8f3p+Du5ZHcnWUR0E99N1/PMG/50LA1SGJNFb87H44Ct+ZnSI73z7kOYXZ5Ha+1n5OrhKTA44yilsaF+hkgm7PNr41tIuQC+Y9pbFSHjMeswP9U34MeTtRjtn4Jhas1Xma1JWzK00AtHy4ESligynvy66+ZL456UV29or+hd0N+ABNFwWe5b0T1qkfCA8oo32k+3qZmziZAS/zX1J5fg2W1J4IvdMExgWPyNAl5NqA3kERU8b7Nu9PQGeodGW/20/6bCQtjo8q7m38a7LhHqmJYnzVYqzhY7nFn4gMdHc5W/7F/uhobc10MzbhoUH5J4Wb3A/A==","encryptKey":"KXJgv+VjBsxVEg0/AzYLOfiT42md9cVZZfDcU3xNCKK1yfoLdLzblr8q2epaO90UxKdTITqaQn7tLCRYRGImHf9jQRIKz9E2D1ISqm+5GVghjG1HhSH1V2bt3D68DsM9fwb0Pe22jQgMnFujF9nOXJtVXZG3/BH7EIgcuNnF7T/Ue13pDPN6OyLrORjdKG1ENdPchQnZN0aYnh/Wkk3MB+g7zaRf1jALRQ6URsUo1WM4Xa6k7dBJxS8HtV/TXv/Alo6tVxb3UqXpVUlhG85Dp854cSq1KiEtA+mousp2hWmB7f3zkR7fueX2zNDlWlsomDYmSZjVjaDAx4+mgS9C+g==","timestamp":"1747897611864","responseId":"JZY2215485812737"}';
        // $agentNo = '11111111';
        // $data = [
        //     'agentNo' => $agentNo,
        //     'customerName' => '测试商户',
        //     'customerNo' => $merchantNo,
        //     'materialsNo' => $posSn,
        //     'transType' => 'TRADE',
        //     'payTypeCode' => 'WECHAT',
        //     'orderNo' => strval(time()),
        //     'amount' => 299,
        //     // 结算金额
        //     'settleAmount' => 98.00,
        //     'feeRate' => 2,
        //     // 手续费
        //     'fee' => 2.00,
        //     // 笔数费
        //     'fixedValue' => 0.00,
        //     'status' => 'SUCCESS',
        //     'successTime' => date('Y-m-d H:i:s'),
        //     // 模拟押金订单
        //     'stopPayType' => 'MACHINE',
        //     'stopPayAmount' => 299.00
        // ];
        // $password = '1234567890123456';
        // $params['data'] = $this->posStrategy->encryptData($password, json_encode($data));
        // $params['encryptKey'] = $this->posStrategy->encryptPassword($password);
        // $params['appId'] = $agentNo;
        // $params['timestamp'] = time();
        // $params['serviceType'] = 'PAY_ORDER_NOTIFY';
        // $params['sign'] = $this->posStrategy->sign($params);
        // $content = json_encode($params);
        // exit($content);
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosTransCallbackRequest::class, $callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertNotEmpty($callbackRequest->getAmount());
        self::assertNotEmpty($callbackRequest->getSettleAmount());
        self::assertNotEmpty($callbackRequest->getRate());
        self::assertNotEmpty($callbackRequest->getFee());
        self::assertInstanceOf(LocalDateTime::class, $callbackRequest->getSuccessDateTime());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }
}
