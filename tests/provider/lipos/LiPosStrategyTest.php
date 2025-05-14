<?php declare(strict_types=1);

namespace think\pos\tests\provider\lipos;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\MerchantStatus;
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
        $merchantRequestDto->setCreditRate(Rate::valuePercentage('0.60'));
        $merchantRequestDto->setDebitCardRate(Rate::valuePercentage('0.55'));
        $merchantRequestDto->setDebitCardCappingValue(Money::valueOfYuan('20'));
        // 设置微信费率
        $merchantRequestDto->setWechatRate(Rate::valuePercentage('0.55'));
        $merchantRequestDto->setAlipayRate(Rate::valuePercentage('0.55'));
        $posProviderResponse = $this->posStrategy->setMerchantRate($merchantRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试商户汇率设置成功回调
     * @return void
     */
    function handleCallbackOfMerchantRateSet()
    {
        $content = '{"serviceType":"CUSTOMER_LAST_RATE_NOTIFY","data":"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","appId":"61261936","sign":"K1Dw2u8QiZ1kqt4zhG4jtNbmWHyj4xrZ1cX0jJCiIEHUEMxNq1UT8shrntaLSadvqDBiVnaWyzgno/WWfguU0kPi2dtYjytoz3Lt7nFgzG2gpmtoV4I2edbmlXtmLM3U0PF5Mc7d+XJUX2CTb4IrLb5Ajyii0WsrEgP+C94AUQRgyB15jlDLlYIk3+FLXOJ9gf+/mOIYeD3pVdT/zeUvUXW9iREGiZDH0VlYiJuZyotDfd8PRkOEKHHfUsPv2Pi00WFhpW0cJD1Vm1/mTBCvREzgwq47Tc1GDPBgxtiFWaFdo08anZFSRz54BqptPErVBqERNo7YwM0EOvkjfRqmsg==","encryptKey":"GeKpiRQkc372PjwK+2nheLesaDsFAHEPRGReVFP0PG2qXETrZqhp/azentS6BQLcJ+UVzuCtBiPEec5J9qFqJenIkCtmIPhv8c/tHb0EUdVsRBC7MqnrQKEFVD8EILx/Xd717ktpTMd97wJJVnSsrav+VyYXkXR74gOuhsjTu0PrU+qso5O/Zf/JdIUgfSIltcvUMadu9ULyOOLD8bumaeAFfyDYY6DBKtwTiOE6QUwUtE+bAwTGa+TmF461UwES2k5H9/d0DdnJAhlpbJ2bpdgrBS2oDRFekpmL1bADruG+9gAGS0TrWG+vQfGIxVw0iXK1ekm2Z8PCNsqf16YK+A==","timestamp":"1747037392992","responseId":"JZY1216031860737"}';
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(MerchantRateSetCallbackRequest::class, $callbackRequest);
        self::assertInstanceOf(Rate::class, $callbackRequest->getWechatRate());
        self::assertInstanceOf(Rate::class, $callbackRequest->getAlipayRate());
        self::assertInstanceOf(Rate::class, $callbackRequest->getDebitCardRate());
        self::assertInstanceOf(Rate::class, $callbackRequest->getCreditRate());
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
        $posRequestDto->setCreditRate(Rate::valuePercentage('0.55'));
        $posRequestDto->setDebitCardRate(Rate::valuePercentage('0.55'));
        $posRequestDto->setDebitCardCappingValue(Money::valueOfYuan('20'));
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
        $posRequestDto->setDeposit(Money::valueOfYuan('199'));
        // 押金套餐码
        $posRequestDto->setDepositPackageCode('1');
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
        // 终端绑定商户后，不再返回终端费率
        // self::assertInstanceOf(Rate::class, $posInfoResponse->getCreditRate());
        // 力 pos 不返回提现手续费
        self::assertNull($posInfoResponse->getWithdrawFee());
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
        $simRequestDto->setSimPackageCode('123456789');
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
        $content = '{"serviceType":"CUSTOMER_INFO_REGISTER_NOTIFY","data":"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","appId":"61261936","sign":"ViaUkJQiRBqIN4lpbGk+9KMw9dpFW1iy4AcN/zvQDu0k+08hQKMSJRnYE8e9AsjdkCXBPyuGhAsfYTsXDbO5DI5iBekGmjBFxNZfhUlzDkph1EGH3AItyVbPDkmO/k0Wi2Ezjavn5HHo6lUrZJA/rvOSowwWsXitDpDAda571eMNVm1U/y5IfVLMaTzKr4yEm2PMgo2J69CMyA1i2cWPWu0UMQFxWyi0hFP+tHGbHkC6E3Itq5QAW08GxMn3En3Xf+kob9r1QvLP0AEAsXVkvJZpjm0LdJe2m+8dfvWZ2UkIRsYnPVIg3jGB8MvDqeFVD6lzGQqvECXSDmQ6rEEhzw==","encryptKey":"Th50ONplYwuzf5/1S0+mm1rta6bMU7JFz33DkzECUDZyCsUgYHNzXX9LHobysKaCiyhLbB09G/rRMYyUu9EfcnnXafCNNRJbnsDUcFEPHHSrp1Uy7aL0zFcUUs3aKE05WJCEGLq+rBxe5ZP8HDGdxqsT/KdOPfV2Lz7Wh56QjV8a+9anXK3c+cadE/HsjFE6xhKdxR75Lhcsm7LtIYobvtkvKK41HGpsYhY0yInM7WfcBr4c9w/RXwzC/WgTiDlU+vzqR3o04SesqrctWzX+2ysl2fPCYKGN/r4D0k8hXjGW482Mrns2aJxgeOplkaMHsc/w47mdtHfaW+gZt4l6XA==","timestamp":"1747102515253","responseId":"JZY1310595823617"}';
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
        $content = '{"serviceType":"MATERIAL_BIND_STATUS_NOTIFY","data":"9SIO43lNTsgQHaF5+G8+wWVKLN/rlxIyqV73iiYAbpdXh9+WtcNnK7CWAaGnaAy4n5tupEn5fxJGj++syht7MCsYUTB22TJ7vNtZl6JkTbKVKci+4sqMk4rWFXdbRYUM8CL9yKDhidk8WXEBKFl57ICT8We4UGrLNiIVpesVwvTASc/uIGNPqJ8MLlUO0kEB74lcrK7uDBvCj3zXL1I7bg==","appId":"61261936","sign":"XxaBBrcGfVWKtjzyvPblbvDUiLsF/aI090YX2/GMDToHVBzo/U5TdI+l4f62+QO1rz0KCgdOljozRP0jiNuZvLcRGmdtuG7Xml+ys74E7Hkm3TU76V5pW7xqXszXwkOh0Si/FVOMpBeWTVsOwZsIIyME9xxqaDoivjtPv9bKrWpqWvDHFurafs555IS3KZq3g4qrXxgtVRzZtaMnZeUV0V9fd0QJk8WNe5z7dipbmQRtIqhZefhzoDV3W9iovJ7LnpJjAcavG9L3E8u6Lnsx3AdYtlQ4cd5peU7K9Thg/V+Wb3nhDcHaRavJqgLGx4notX3BY2u69+BIHNj9DD1H4g==","encryptKey":"Mc00JBzqESAmFVtho3Gcx6u9/V0a1iJ75OxkeV+ULQF+BaFyncAVBQss8iYx5Yku+PFqMBXUDnCexv9xUmKaY0rvyz0Yna2x0BEEWKEKFbEvsa4YXs0ek1p6/f9OXQkKRcTPuG+41hP+sRhaKWqYv4wHCltGbbIQG/Txj7D1rmAPBidekDGYjidnx7th9Q7ehmN618Vwwb9BRhlHRkvgawmECKie9KNwUmfZTNQk9k+6BpoKb37eDK5ODQ0J/GcJcfeTL+ZOwit0BbYdzaiNfRyMyRSh2YW1C2XLk8MTNafpoY2Cva1kKl3Z5YfY9LVlv5ff54Y9iiDVreybqX2snw==","timestamp":"1747105746640","responseId":"JZY1311015243266"}';
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
        $agentNo = '11111111';
        $data = [
            'agentNo' => $agentNo,
            'customerNo' => $merchantNo,
            'materialsNo' => $posSn,
            'bindStatus' => 'TRUE',
            'changeTime' => date('Y-m-d H:i:s'),
        ];
        $password = '1234567890123456';
        $params['data'] = $this->posStrategy->encryptData($password, json_encode($data));
        $params['encryptKey'] = $this->posStrategy->encryptPassword($password);
        $params['appId'] = $agentNo;
        $params['timestamp'] = time();
        $params['serviceType'] = 'MATERIAL_BIND_STATUS_NOTIFY';
        $params['sign'] = $this->posStrategy->sign($params);
        $content = json_encode($params);
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosBindCallbackRequest::class, $callbackRequest);
        self::assertNotEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 激活成功回调
     */
    function handleCallbackOfPosActivate()
    {
        // todo shali [2025/5/13] 缺失真实激活回调信息
        $content = '';
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
        $agentNo = '11111111';
        $data = [
            'agentNo' => $agentNo,
            'customerName' => '测试商户',
            'customerNo' => $merchantNo,
            'materialsNo' => $posSn,
            'transType' => 'TRADE',
            'payTypeCode' => 'WECHAT',
            'orderNo' => strval(time()),
            'amount' => 100,
            // 结算金额
            'settleAmount' => 98.00,
            'feeRate' => 2,
            // 手续费
            'fee' => 2.00,
            // 笔数费
            'fixedValue' => 0.00,
            'status' => 'SUCCESS',
            'successTime' => date('Y-m-d H:i:s'),
        ];
        $password = '1234567890123456';
        $params['data'] = $this->posStrategy->encryptData($password, json_encode($data));
        $params['encryptKey'] = $this->posStrategy->encryptPassword($password);
        $params['appId'] = $agentNo;
        $params['timestamp'] = time();
        $params['serviceType'] = 'PAY_ORDER_NOTIFY';
        $params['sign'] = $this->posStrategy->sign($params);
        $content = json_encode($params);
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
