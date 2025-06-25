<?php declare(strict_types=1);

namespace think\pos\tests\provider\kunpeng;

use PHPUnit\Framework\TestCase;
use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\TransOrderType;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\dto\request\CallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\exception\UnsupportedBusinessException;
use think\pos\PosStrategy;
use think\pos\PosStrategyFactory;

class KunPengPosPlatformTest extends TestCase
{
    /**
     * @var PosStrategy
     */
    private $posStrategy;

    protected function setUp(): void
    {
        $this->posStrategy = PosStrategyFactory::create('kunpeng');
    }

    //<editor-fold desc="商户接口测试">

    /**
     * @test 测试设置商户费率
     * @return void
     * @throws UnsupportedBusinessException
     */
    function setMerchantRate()
    {
        $posSn = env('kunpeng.posSn');
        self::assertNotEmpty($posSn, 'kunpeng.posSn is empty');
        $merchantRequestDto = new MerchantRequestDto();
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

    //<editor-fold desc="测试终端操作接口">
    /**
     * @test 测试设置 pos sim 流量费
     * @return void
     * @throws UnsupportedBusinessException
     */
    function setPosSimFee()
    {
        // $posSn = env('kunpeng.posSn');
        $posSn = '00007302825222004400';
        self::assertNotEmpty($posSn, 'kunpeng.posSn is empty');
        $simRequestDto = new SimRequestDto();
        $simRequestDto->setDeviceSn($posSn);
        // 套餐这个固定？
        $simRequestDto->setSimPackageCode('20250526160300270');
        $posProviderResponse = $this->posStrategy->setSimFee($simRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试设置 pos 押金
     * @return void
     * @throws UnsupportedBusinessException
     */
    function setPosDeposit()
    {
        // $posSn = env('kunpeng.posSn');
        $posSn = '00007302825222004400';
        self::assertNotEmpty($posSn, 'kunpeng.posSn is empty');
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posRequestDto->setDeposit(Money::valueOfYuan('199'));
        $posRequestDto->setDepositPackageCode('20250429152200252');
        $posProviderResponse = $this->posStrategy->setPosDeposit($posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试 pos 绑定
     * @return void
     * @throws UnsupportedBusinessException
     */
    function posBind()
    {
        $posSn = env('kunpeng.posSn');
        self::assertNotEmpty($posSn, 'kunpeng.posSn is empty');
        $merchantNo = env('kunpeng.merchantNo');
        self::assertNotEmpty($merchantNo, 'kunpeng.merchantNo is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $merchantRequestDto->setMerchantNo($merchantNo);
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posProviderResponse = $this->posStrategy->bindPos($merchantRequestDto, $posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }

    /**
     * @test 测试 pos 解绑
     * @throws UnsupportedBusinessException
     */
    function posUnbind()
    {
        $posSn = env('kunpeng.posSn');
        self::assertNotEmpty($posSn, 'kunpeng.posSn is empty');
        $merchantNo = env('kunpeng.merchantNo');
        self::assertNotEmpty($merchantNo, 'kunpeng.merchantNo is empty');
        $merchantRequestDto = new MerchantRequestDto();
        $merchantRequestDto->setMerchantNo($merchantNo);
        $posRequestDto = new PosRequestDto();
        $posRequestDto->setDeviceSn($posSn);
        $posProviderResponse = $this->posStrategy->unbindPos($merchantRequestDto, $posRequestDto);
        self::assertTrue($posProviderResponse->isSuccess(), $posProviderResponse->getErrorMsg() ?? '');
    }
    //</editor-fold>

    //<editor-fold desc="测试商户回调接口">
    /**
     * @test 测试 pos 绑定回调
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfPosBind()
    {
        // pos 平台官方回调
        $content = '{"serviceType":"CUSTOMER_REGISTER","data":"aSiTRWOqnLUKy7JE7Y1KLiiosR6ghge8llSk7HLChAjo2+auSxPV7tvc+yQTb6V0+qhRGDR/WAWpwoyseoZG5eh8eiijnIKFaoMGNlFBvm9j3WRjsskQx8qLDmF4eHWQk1Vs8HlT1/jDnV6M89gb2em2RsGWebrWURKuIaHiO2yAPGFbLU3+5ezFajFb3GXXbtARGhKETExp3hCuOR8bSaFxRL4hX4qSc+tpKYW5/XGf7H9mNLkvzpg+O+EdH0lDKU1Bz2lDrS0+A7uISCbf97TEFhWzNGvf0CdXgHN9JYxYDxoAZpUvxddl5lv5gidM","appId":"72275876","sign":"cIEoSIHZ86i9DtIQmCxrh1SRkTzPAZXWrwm6ogchEVlbm7X7ybkTswt1NmTleuP/NRv2DMt1Xz7iv/Uq9MFoGC1/oRgkGKJJyES9tiTGRFi2B3pSBMZ3K10QBvr6Xa8VW45h/c649hLgWp4d3Bp0gTKZOycBljmEA0Pcx3w39lR1eN630WZVu/1ItuRjYmAtbAnl4/kOzoYj86kR+4bf+SzAVVIKsrc5wawmJkp7sLBNDjL9IDyl6zLXQB7jvpB97jz7Vw09BMcX7YUxkzslqv2zRlj2yL5idBLlbXz/SsTT9rBycm+/5IaT/DDVWGVnlRmoE11c46IlAPnYatfFkw==","encryptKey":"B5f3zJcvsMIiFElLrESSO1SoSlqz7woTJnOxf6isIWM615GO2mWhX7SLx/AKZy9/4ft6KwG7YBIKuElrDkO3qZCDDYsxgbfZCV4a9z0yRvPkegr+h39ojBm+VEUSEp8WwinHV1tCJyJrS1A3JN2WHGuNbNTyR0lEMDyyq/G9V72M+uCnNTF+d/qdRZDGl/BITHKJwx4VKaAsBrhv9zsunNFlIiPy4kBM6k7MLVDQ65dRvAsVgY8AnCXbVCenSsnwixsnMFN4jL+f8y03/8CCqeVDPLiLgT9X/xhCxGKdVrpvygtD3qV6fqe33BAeuAiAzOjtSx7NfUXiZYkdWNgjYg==","timestamp":"1750745974669"}';
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosBindCallbackRequest::class, $callbackRequest);
        self::assertEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertNotEmpty($callbackRequest->getMerchantRegisterCallbackRequest());
        self::assertInstanceOf(MerchantRegisterCallbackRequest::class, $callbackRequest->getMerchantRegisterCallbackRequest());
        self::assertInstanceOf(LocalDateTime::class, $callbackRequest->getModifyTime());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 解绑回调
     */
    function handleCallbackOfPosUnbind()
    {
        self::markTestSkipped('没有测试参数');
    }

    /**
     * @test 测试 pos 交易成功回调
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfPosTrans()
    {
        // 普通交易回调
        $content = '{"serviceType":"PAY_ORDER","data":"bRh0lZPDE47P+QAWSB9e6Ba4/eehDBjqJZG9JS8MKhmSKZ2hSGBzY59IrZkQXUMW98bGvpreHO0TM/k8ATrdp+FLz6Eptf2iYr+yr0tye88rqvktjODOxGUE8yDmx9jgY2Cxrr/ogYzIh85olqtHt49uEiT7hF1c9MlD0Gw4314zbWzV//ogL9JEMi0+6knrM7MPu4kHA6CAwSVFf3QoWOkrcCtI0JfEkM4P0+CehktJ7SHLtk2S0fAElkHo6tqOw7JN5lPyNaXBPGRMGYbWxVwOwoMLC6fZ7slGLFGYSqwxDGI48vipDf4wBzUP2MvZTGdploRDV57Ncg9gIdTx9zjhTqOHd5GuArsduoitL2+ElsO6xMq8lNqBndO5RlWPZdkB5XukjtwRsBa8xqEwRTPh1ZjKxf6B20VKjCPb8K81w1iOI7LDRxbMF7c7VnuyLSbWbZe45S8tZP5zdrLhWJuSwDuvcG5I84+2XeWTW29tn+kaQijmKK5GN/p/aBJhD7wmoH4NoIDHQ4f3IHssI8V3jeoJq5lKHLQmhnPYQMdJfb3OGSQz/oMgbx+KaH7zIWKSLZr6bS5QO1xh9eLgTPejnFBC0NIXKTUdECISxMwCg0kjE6JRFpHdQk69s+Sa8MQc92QrTWjO4DTPk+Sy3w==","appId":"72275876","sign":"SCt3SNeFVAvvrw9Zad9kIjftqNRmBRc1LByN83ihwCoaGEa9B6p6vjNobNmQW6QT9m6Tc+c1FWQHqJp/U8Cbl8STsC4H+oBtFjy831xG1x2ha+GY7qGrgjyxU/otVRZUS9ZGxZj7DXq9y6xA8H00G91ODE8UQCAgB61sLxePujmi/Jy61u7GJAA3SiFcvgoGSr3Svpo7bTzGXCh9r0vOanodN6SKeP0TLcT9EHT6SYlvqoZi3+CXt/rg1Dm1ED04l5CNvWaJMESYyBQvGcOOkU75tFGU1zA7KEErQ59qnxziiN5wIBq7WLb5Qv/nuYXLRcp5acgar7i5+McWQ6Ouzg==","encryptKey":"Oyi6/3I6VmLp5DlSDyteD4Hv+CjnQZoh80HXd45dhWzrHvwU+Lo5WtBM2V0sWPkA/eDWqb2wtp795XIB/czrhZncZ7ngsLP/8HNCB508g9IqeDdzAaw3407GqrkhryxneTq9yfKbocq8JF/XbE6TtgaJZ1wnqY8fYNZMFaNlGalIOO1NGWAPFo2IS0P06+Q+SX2vZizgM0uNxHDOJqr2HEmp5OcAtUC0y//S0Bh3tbG/3msxECS9CVZnwAJb0NC4AD5c71nf0gp6RaLhpIN0XfirjzUiu2pKZ+vNKi0F09oVdHCjRLfZ3tCl+Zl/AtWVoPjqPtFzcqAZ9uKKiKZjYw==","timestamp":"1750747321881"}';
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosTransCallbackRequest::class, $callbackRequest);
        self::assertEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertEquals('339512734587322368', $callbackRequest->getTransNo());
        self::assertNotEmpty($callbackRequest->getAmount());
        self::assertNull($callbackRequest->getSettleAmount());
        self::assertNotEmpty($callbackRequest->getRate());
        self::assertNotEmpty($callbackRequest->getFee());
        self::assertFalse($callbackRequest->isFeeCapping());
        self::assertInstanceOf(LocalDateTime::class, $callbackRequest->getSuccessDateTime());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 押金交易成功回调
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfPosDeposit()
    {
        $content = '{"serviceType":"DEPOSIT_STOP_ORDER","data":"KMpVGCNVA1DqgmcJZTqZbo/7lEtVkQb+LfvYKRDM9QLPIBpxrvy2UefeCSmVf59tUyKS0gWjSrRFeo+YFTCnYm+fRyDV7ChIJtuANe+RR9i0lP6SVij8xSs59VPc6FzqL5kDx9AM4pstoa/Yfyv5vSBXfgA7CAlWK/VeBSFgVgL8y8X99KQ49I1oqcIHkFhsVh2EQ6BoNUbAWjzaz6TQ71msJpGhQs/2V8dF92kd94riDrl9giBYuspzsIwPzKv68wLajln3JhEw/XRpzbbltlVOrGuvdIOW3+T+1i0sJEYpGSagzLI/Iwlx+MuzpcB6skQawefrzWijffpvPALdwQok9W8q8eK7jhS1PNX9ofzfVDG9G8BG0SyoqLzQokk0Q+S1HNNi+tAJDGQxIGg1GQ==","appId":"72275876","sign":"ECjrvvcnICFMTqLPQ9L2/ZU6UYuLVLbnbUIrSwHIUNbmJ3v+Io1LUrKrE7+ZEJE322FhPHugdTxiP8/XaCgcPQ1IytynLplCEKI0jg+s2S+oEIt2Xe3ICrJeNB4ewwC44QulYK1IDkcqTYLafe10Alrv8M1GDxJarCqZz7DVTG0oastblzbkJW2Eek8H/GWNXlshH3PRz/wz1LFTJ6/aeFCwEoY5tdpMBn2Vagty7fnte3dPjl4kPcYKZusKDQSc1xfyI93YjDkWy41jV/vtpdzklvQmSsD5lj32BN2y3ugyGb9ucOTsKtt8jjYxyLHJYvRGaETsANO6JRPddFfGoA==","encryptKey":"U5aGr1NyzHNNg5q8jbYQ1ZdHPd17FqSfdTM43gd94gy+6gvV8gbMpkjNgczGq984w13vJnczFemNpYpPA+KuOqhQPAs3cIp8tdIcPwu/+qrt3wbkFmhi7RZPhrbERWQdIKRKe+S3RwJn0zuFC/azetdsXsZ05eOE5FBRvetp/IabAEXFYSq3CwLvGVcfaX8bOnw5vhIzX72A0efXNQTpY0pSmYpubvBRLW69aUBs3cPe8x5+Xo7TCAY0uRcuOMi2yRFVtr3cwdMIuIVEWmMVvEK4kl8xvfq+b9sJt4bzeQa7DYePG2GYtpOi3YVtgScMFM90HLXlwXl5uATy7SMJRQ==","timestamp":"1750756850080"}';
        $callbackRequest = $this->posStrategy->handleCallback($content);
        self::assertInstanceOf(CallbackRequest::class, $callbackRequest);
        self::assertEquals(TransOrderType::DEPOSIT, $callbackRequest->getOrderType());
        self::assertTrue($callbackRequest->isSuccess(), $callbackRequest->getErrorMsg() ?? '');
        self::assertInstanceOf(PosTransCallbackRequest::class, $callbackRequest);
        self::assertEmpty($callbackRequest->getAgentNo());
        self::assertNotEmpty($callbackRequest->getMerchantNo());
        self::assertNotEmpty($callbackRequest->getDeviceSn());
        self::assertNotEmpty($callbackRequest->getTransNo());
        self::assertEquals('ZY0000000001937425547449798656_01', $callbackRequest->getTransNo());
        self::assertNotEmpty($callbackRequest->getAmount());
        self::assertNull($callbackRequest->getSettleAmount());
        self::assertNull($callbackRequest->getRate());
        self::assertNull($callbackRequest->getFee());
        self::assertFalse($callbackRequest->isFeeCapping());
        self::assertInstanceOf(LocalDateTime::class, $callbackRequest->getSuccessDateTime());
        self::assertNotEmpty($callbackRequest->getStatus());
        self::assertEquals('OK', $this->posStrategy->getCallbackAckContent());
    }

    /**
     * @test 测试 pos 流量卡交易成功回调
     */
    function handleCallbackOfPosSim()
    {
        self::markTestSkipped('没有流量卡扣费交易通知测试数据');
    }
    //</editor-fold>
}
