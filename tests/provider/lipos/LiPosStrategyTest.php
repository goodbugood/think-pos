<?php

namespace think\pos\tests\provider\lipos;

use PHPUnit\Framework\TestCase;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
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
}
