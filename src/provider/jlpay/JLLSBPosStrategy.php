<?php declare(strict_types=1);

namespace think\pos\provider\jlpay;

use Exception;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\http\HttpClient;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosInfoResponse;
use think\pos\dto\response\PosProviderResponse;
use think\pos\extend\MD5withRSAUtils;
use think\pos\extend\RsaUtils;
use think\pos\PosStrategy;

/**
 * 立刷 pos 服务商对接
 * @author shali
 * @date 2025/04/30
 */
class JLLSBPosStrategy extends PosStrategy
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * pos 操作访问路径
     */
    private const METHOD_POS = '/FEE003';

    /**
     * 商户操作访问路径
     */
    private const METHOD_MERCHANT = '/FEE002';

    // 查询 pos 信息方法
    private const METHOD_POS_QUERY = '/FEE009';

    // 流量费档位：68元/年，绑定 7 天后收取
    public const SIM_PACKAGE_68_AFTER_7DAYS = '331332';

    /**
     * 立刷接口响应成功码为 00
     */
    private const RET_CODE_SUCCESS = '00';

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->httpClient = new HttpClient();
    }

    public static function providerName(): string
    {
        return '立刷B版';
    }

    function cancelVip(PosRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::METHOD_POS);
        $data = [
            'agentId' => $this->config['agentId'],
            'deviceSn' => $dto->getDeviceSn(),
            'signMethod' => $this->config['signMethod'],
            'feeList' => [
                'feeCalcType' => 'N3',
                'fee_flag' => '0',
            ]
        ];
        $data['signData'] = $this->createSign($data);
        try {
            $res = $this->post($url, $data);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s取消会员失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        if (self::RET_CODE_SUCCESS !== $res['ret_code']) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s取消会员失败：%s', self::providerName(), $dto->getDeviceSn(), $res['ret_msg']);
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    function initPosConfig(PosRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::METHOD_POS);
        $data = [
            'agentId' => $this->config['agentId'],
            'deviceSn' => $dto->getDeviceSn(),
            'feeList' => [
                // 禁止购买会员
                [
                    'feeCalcType' => 'N3',
                    'fee_flag' => '0',
                ],
                // 设置流量套餐
                [
                    'feeCalcType' => 'N2',
                    // 鉴于目前套餐未入库都是固定
                    'message_fee_package_id' => self::SIM_PACKAGE_68_AFTER_7DAYS,
                ],
                // 设置押金
                [
                    'feeCalcType' => 'N1',
                    'fixed' => '1',
                    'rate' => $dto->getDeposit()->toFen(),
                ],
                // 设置刷卡交易费率
                [
                    'feeCalcType' => 'M5',
                    'fixed' => '0',
                    'rate' => $dto->getCreditRate()->toPercentage(),
                ],
                // 设置提现手续费
                [
                    'feeCalcType' => 'T0',
                    'fixed' => '1',
                    'rate' => $dto->getWithdrawFee()->toFen(),
                ],
            ],
            'signMethod' => $this->config['signMethod'],
        ];
        $data['signData'] = $this->createSign($data, false);
        try {
            $res = $this->post($url, $data);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s初始化配置失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        if (self::RET_CODE_SUCCESS !== $res['ret_code']) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s初始化配置失败：%s', self::providerName(), $dto->getDeviceSn(), $res['ret_msg']);
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    function getPosInfo(PosRequestDto $dto): PosInfoResponse
    {
        $url = $this->getUrl(self::METHOD_POS_QUERY);
        $data = [
            'agentId' => $this->config['agentId'],
            'deviceSn' => $dto->getDeviceSn(),
        ];
        $data['signData'] = $this->createSign($data);
        // 这个立刷签名挺有意思，签名方法一会参与签名一会不参与
        $data['signMethod'] = $this->config['signMethod'];
        try {
            $res = $this->post($url, $data);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]查询pos_sn=%s信息失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosInfoResponse::fail($errorMsg);
        }
        if (self::RET_CODE_SUCCESS !== $res['ret_code']) {
            $errorMsg = sprintf('pos服务商[%s]查询pos_sn=%s信息失败：%s', self::providerName(), $dto->getDeviceSn(), $res['ret_msg']);
            return PosInfoResponse::fail($errorMsg);
        }

        // 解析响应值 {"deposit_amount":0,"device_sn":"N3500D00146101","message_fee_package_id":"331332","quick_fee":0,"ret_code":"00","ret_msg":"处理成功","total":0,"trans_rate":0.6,"vip_fee_flag":"0"}
        return self::toPosInfoResponse($res);
    }

    function setPosDeposit(PosRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::METHOD_POS);
        $data = [
            'agentId' => $this->config['agentId'],
            'deviceSn' => $dto->getDeviceSn(),
            'signMethod' => $this->config['signMethod'],
            'feeList' => [
                'feeCalcType' => 'N1',
                'fixed' => '1',
                'rate' => $dto->getDeposit()->toFen(),
            ],
        ];
        $data['signData'] = $this->createSign($data);
        try {
            $res = $this->post($url, $data);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置押金失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        if (self::RET_CODE_SUCCESS !== $res['ret_code']) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置押金失败：%s', self::providerName(), $dto->getDeviceSn(), $res['ret_msg']);
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    function setPosRate(PosRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::METHOD_POS);
        $data = [
            'agentId' => $this->config['agentId'],
            'deviceSn' => $dto->getDeviceSn(),
            'feeList' => [
                // 设置刷卡交易费率
                [
                    'feeCalcType' => 'M5',
                    'fixed' => '0',
                    'rate' => $dto->getCreditRate()->toPercentage(),
                ],
                // 设置提现手续费
                [
                    'feeCalcType' => 'T0',
                    'fixed' => '1',
                    'rate' => $dto->getWithdrawFee()->toFen(),
                ],
            ],
            'signMethod' => $this->config['signMethod'],
        ];
        $data['signData'] = $this->createSign($data, false);
        try {
            $res = $this->post($url, $data);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置费率失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        if (self::RET_CODE_SUCCESS !== $res['ret_code']) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置费率失败：%s', self::providerName(), $dto->getDeviceSn(), $res['ret_msg']);
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    function setSimFee(SimRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::METHOD_POS);
        $data = [
            'agentId' => $this->config['agentId'],
            'deviceSn' => $dto->getDeviceSn(),
            'signMethod' => $this->config['signMethod'],
            'feeList' => [
                'feeCalcType' => 'N2',
                'message_fee_package_id' => $dto->getSimPackageCode(),
            ],
        ];
        $data['signData'] = $this->createSign($data);
        try {
            $res = $this->post($url, $data);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置套餐失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        if (self::RET_CODE_SUCCESS !== $res['ret_code']) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置套餐失败：%s', self::providerName(), $dto->getDeviceSn(), $res['ret_msg']);
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::METHOD_MERCHANT);
        $data = [
            'agentId' => $this->config['agentId'],
            'merchNo' => $dto->getMerchantNo(),
            'deviceSn' => $dto->getDeviceSn(),
            'feeList' => [
                // 设置刷卡交易费率
                [
                    'feeCalcType' => 'M5',
                    'fixed' => '0',
                    'rate' => $dto->getCreditRate()->toPercentage(),
                ],
                // 设置提现手续费
                [
                    'feeCalcType' => 'T0',
                    'fixed' => '1',
                    'rate' => $dto->getWithdrawFee()->toFen(),
                ],
            ],
            'signMethod' => $this->config['signMethod'],
        ];
        $data['signData'] = $this->createSign($data, false);
        try {
            $res = $this->post($url, $data);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置商户费率失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        if (self::RET_CODE_SUCCESS !== $res['ret_code']) {
            $errorMsg = sprintf('pos服务商[%s]给pos_sn=%s设置商户费率失败：%s', self::providerName(), $dto->getDeviceSn(), $res['ret_msg']);
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    /**
     * 获取请求地址，通过业务方法，网关拼接
     * @param string $bizMethod 业务方法
     * @return string
     */
    private function getUrl(string $bizMethod): string
    {
        $gateway = $this->config['gateway'];
        if ($this->config['test']) {
            $gateway = $this->config['testGateway'];
        }

        return $gateway . $bizMethod;
    }

    private static function toPosInfoResponse(array $res): PosInfoResponse
    {
        $posInfoResponse = PosInfoResponse::success();
        $money = Money::valueOfFen(strval($res['deposit_amount'] ?? 0));
        $posInfoResponse->setDeposit($money);
        $posInfoResponse->setDeviceNo($res['device_sn']);
        $posInfoResponse->setSimPackageCode($res['message_fee_package_id']);
        $posInfoResponse->setWithdrawFee(Money::valueOfFen(strval($res['quick_fee'] ?? 0)));
        // 百分数转小数
        $posInfoResponse->setCreditRate(Rate::valueOfPercentage(strval($res['trans_rate'] ?? 0)));
        $posInfoResponse->setIsVip('1' === ($res['vip_fee_flag'] ?? StrUtil::NULL));

        return $posInfoResponse;
    }

    private function createSign($data, $sortParams = true)
    {
        $str = "";
        if ($sortParams) {
            ksort($data);
            foreach ($data as $value) {
                if (is_object($value) || is_array($value)) {
                    $value = is_object($value) ? (array)$value : $value;
                    ksort($value);
                    $str .= implode('', $value);
                } else {
                    $str .= $value;
                }
            }
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $str .= implode('', $v);
                    }
                } else {
                    $str .= $value;
                }
            }
        }

        return RsaUtils::rsaSign($str, MD5withRSAUtils::getPrivateKey($this->config['privateKey']));
    }

    /**
     * @throws Exception
     */
    private function post(string $url, array $data, array $headers = ['Content-Type' => 'application/json'])
    {
        try {
            $res = $this->httpClient->post($url, $data, $headers);
        } finally {
            $this->rawRequest = $this->httpClient->getRawRequest();
            $this->rawResponse = $this->httpClient->getRawResponse();
        }
        return json_decode($res, true);
    }
}
