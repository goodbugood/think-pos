<?php declare(strict_types=1);

namespace think\pos\provider\yilian;

use Exception;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\http\HttpClient;
use shali\phpmate\PhpMateException;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosProviderResponse;
use think\pos\exception\ProviderGatewayException;
use think\pos\PosStrategy;

/**
 * 注意：
 * 1. 移联的接口文档涉及金额的，单位均为元
 * 2. 涉及费率的，均为百分数
 */
class YiLianPosPlatform extends PosStrategy
{
    private const RESPONSE_CODE_SUCCESS = '200';

    private const API_METHOD = [
        // 商户绑定 pos
        'bind_pos' => '',
        // 商户解绑 pos
        'unbind_pos' => '',
        // 查询 pos 终端
        'pos_info' => '',
        // 设置 pos 费率
        'modify_pos_rate' => '',
        // 设置 pos 通信服务费
        'modify_pos_sim_fee' => '/agent/updateMerchantFlowInfo',
        // 设置 pos 押金=服务费
        'modify_pos_deposit' => '',
        // 设置商户费率
        'modify_merchant_rate' => '/agent/changeMerchantFeeRate',
    ];

    /**
     * 银行卡类型
     */
    private const PARAMS_CARD_TYPE_MAP = [
        // 借记卡
        'debit' => 'DEBIT',
        // 信用卡
        'credit' => 'CREDIT',
    ];

    /**
     * 交易类型=支付方式分组
     * WX_SCAN，ZFB_SCAN，JSAPI 仅需要设置一种
     */
    private const PARAMS_TRANS_TYPE_MAP = [
        // POS刷卡-标准类
        'pos_standard' => 'POS_STANDARD',
        // POS刷卡-VIP，移联对接技术反馈 VIP 暂时没有
        // 'pos_vip' => 'POS_VIP',
        // POS刷卡-云闪付
        'cloud_quick_pass' => 'CLOUD_QUICK_PASS',
        // 微信扫码，主扫和被扫
        'wx_scan' => 'WX_SCAN',
        // 支付宝扫码，主扫和被扫
        // 'zfb_scan' => 'ZFB_SCAN',
        // 银联二维码大额
        'yl_code_more' => 'YL_CODE_MORE',
        // 银联二维码小额
        'yl_code_less' => 'YL_CODE_LESS',
        // 微信公众号和支付宝服务窗
        // 'jsapi' => 'JSAPI',
        // 银联云闪付小额
        'yl_jsapi_less' => 'YL_JSAPI_LESS',
        // 银联云闪付大额
        'yl_jsapi_more' => 'YL_JSAPI_MORE',
        // 条码收款，移联技术反馈这个用不到
        // 'bar_code' => 'BAR_CODE',
    ];

    /**
     * @var HttpClient
     */
    private $httpClient;

    public static function providerName(): string
    {
        return '移联POS平台';
    }

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->httpClient = new HttpClient();
    }

    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::API_METHOD['modify_merchant_rate']);
        $params = [];
        foreach (self::PARAMS_TRANS_TYPE_MAP as $transType) {
            $item = [
                'merchantNo' => $dto->getMerchantNo(),
                // 交易类型
                'groupType' => $transType,
                // 费率百分数
                'transRate' => null,
                // 提现费率
                'withdrawRate' => '0.00',
                // 提现费单位类型，FIXED 固定金额，PERCENT 百分比
                'withdrawRateUnit' => 'FIXED',
            ];
            if ($this->isBankCardType($transType)) {
                foreach (self::PARAMS_CARD_TYPE_MAP as $cardType) {
                    $item['cardType'] = $cardType;
                    if (self::PARAMS_CARD_TYPE_MAP['debit'] === $cardType) {
                        // 借记卡交易手续费封顶值
                        $item['topTransFee'] = $dto->getDebitCardCappingValue()->toYuan();
                        $item['transRate'] = $dto->getDebitCardRate()->toPercentage();
                    } else {
                        // 信用卡交易无手续费封顶值，移除
                        unset($item['topTransFee']);
                        // 仅贷记卡支持提现手续费
                        $item['withdrawRate'] = $dto->getWithdrawFee()->toYuan();
                        $item['transRate'] = $dto->getCreditRate()->toPercentage();
                    }
                    $params[] = $item;
                }
            } else {
                // 扫码费率
                $item['transRate'] = $dto->getWechatRate() ? $dto->getWechatRate()->toPercentage() : $dto->getAlipayRate()->toPercentage();
                $params[] = $item;
            }
        }
        try {
            foreach ($params as $item) {
                $this->post($url, $item);
            }
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改商户merchant_no=%s费率失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }

        return PosProviderResponse::success();
    }

    function setSimFee(SimRequestDto $dto): PosProviderResponse
    {
        $params = [
            'merchantNo' => $dto->getMerchantNo(),
            // 免收期，x 天，我们不配置，统一去 pos 平台配置
            // 'freeDays' => null,
            'merchantFlowList' => json_decode($dto->getSimPackageCode(), true),
        ];
        try {
            $url = $this->getUrl(self::API_METHOD['modify_pos_sim_fee']);
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]设置商户merchant_no=%s sim卡套餐失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    function unbindPos(MerchantRequestDto $merchantRequestDto, PosRequestDto $posRequestDto): PosProviderResponse
    {
        parent::unbindPos($merchantRequestDto, $posRequestDto); // TODO: Change the autogenerated stub
    }

    function handleCallback(string $content)
    {
        parent::handleCallback($content); // TODO: Change the autogenerated stub
        // todo shali [2025/6/19] 解密
        // todo shali [2025/6/19] 验签
        // todo shali [2025/6/19] 解析回调报文
    }

    function getCallbackAckContent(): string
    {
        return parent::getCallbackAckContent(); // TODO: Change the autogenerated stub
    }

    /**
     * 判断交易类型是否为银行卡类型
     * 云闪付小额属于扫码，大额属于刷卡
     * @param string $transType
     * @return bool
     */
    private function isBankCardType(string $transType): bool
    {
        return in_array($transType, [
            // POS刷卡-标准类
            self::PARAMS_TRANS_TYPE_MAP['pos_standard'],
            // POS刷卡-VIP
            self::PARAMS_TRANS_TYPE_MAP['pos_vip'],
            // POS刷卡-云闪付
            self::PARAMS_TRANS_TYPE_MAP['cloud_quick_pass'],
            // 银联二维码大额
            self::PARAMS_TRANS_TYPE_MAP['yl_code_more'],
            // 银联云闪付大额
            self::PARAMS_TRANS_TYPE_MAP['yl_jsapi_more'],
        ]);
    }

    private function getUrl(string $apiMethod): string
    {
        $gateway = $this->isTestMode() ? $this->config['testGateway'] : $this->config['gateway'];
        return $gateway . $apiMethod;
    }

    /**
     * @throws ProviderGatewayException
     */
    private function post(string $url, array $data): array
    {
        try {
            // 加密
            $encryptData = $this->encryptData(json_encode($data));
        } catch (PhpMateException $e) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]加密请求数据失败：%s', self::providerName(), $e->getMessage()));
        }
        $params = [
            'agentNo' => $this->config['agentNo'],
            'jsonData' => $encryptData,
            // 签名
            'sign' => $this->sign($encryptData),
        ];
        try {
            $res = $this->httpClient->post($url, $params, ['Content-Type' => 'application/json']);
        } catch (Exception $e) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]请求失败：%s', self::providerName(), $e->getMessage()));
        } finally {
            $this->rawRequest = $this->httpClient->getRawRequest();
            // 请求参数记录明文
            $this->rawRequest['params']['data'] = $data;
            $this->rawResponse = $this->httpClient->getRawResponse();
        }
        $res = json_decode($res, true);
        // 检查
        if ($res['code'] !== self::RESPONSE_CODE_SUCCESS) {
            $errorMsg = sprintf('code=%s&message=%s', $res['code'], $res['message']);
            throw new ProviderGatewayException($errorMsg);
        }
        // 验签
        if (!$this->verifySign($res['data']['sign'], $res['data']['jsonData'])) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]验签失败：%s', self::providerName(), $res['message']));
        }
        // 解密
        try {
            $jsonData = $this->decryptData($res['data']['jsonData']);
            // 日志记录明文
            $this->rawRequest['params'] = $data;
            $this->rawResponse['decryptedBody'] = $jsonData;
            return json_decode($jsonData, true);
        } catch (PhpMateException $e) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]解密数据失败：%s', self::providerName(), $e->getMessage()));
        }
    }

    /**
     * 使用对称加密密码加密数据
     * @throws PhpMateException
     */
    public function encryptData(string $jsonData): string
    {
        $password = $this->config['aesKey'];
        return EncryptUtil::encryptByAES_ECB_PKCS5PaddingToBase64($password, $jsonData);
    }

    /**
     * @throws PhpMateException
     */
    public function decryptData(string $encryptData): string
    {
        $password = $this->config['aesKey'];
        return EncryptUtil::decryptByAES_ECB_PKCS5PaddingToBase64($password, $encryptData);
    }

    /**
     * 签名算法：md5(密文 + aesKey)
     * @param string $encryptData
     * @return string
     */
    private function sign(string $encryptData): string
    {
        $password = $this->config['aesKey'];
        return md5($encryptData . $password);
    }

    /**
     * 验签
     */
    public function verifySign(string $sign, string $jsonData): bool
    {
        $sign1 = $this->sign($jsonData);
        return $sign === $sign1;
    }
}
