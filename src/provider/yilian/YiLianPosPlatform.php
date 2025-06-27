<?php declare(strict_types=1);

namespace think\pos\provider\yilian;

use Exception;
use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\http\HttpClient;
use shali\phpmate\PhpMateException;
use shali\phpmate\util\Money;
use think\pos\constant\PaymentType;
use think\pos\constant\PosStatus;
use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosDepositRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosDepositResponse;
use think\pos\dto\response\PosProviderResponse;
use think\pos\exception\MissingParameterException;
use think\pos\exception\ProviderGatewayException;
use think\pos\PosStrategy;
use think\pos\provider\yilian\convertor\MerchantConvertor;
use think\pos\provider\yilian\convertor\PosConvertor;

/**
 * 注意：
 * 1. 移联的接口文档涉及金额的，单位均为元
 * 2. 涉及费率的，均为百分数
 * 3. 请求接口签名使用的 key 和回调时验签使用的签名的 key 不是同一个
 */
class YiLianPosPlatform extends PosStrategy
{
    protected const CALLBACK_ACK_CONTENT = 'OK';

    private const RESPONSE_CODE_SUCCESS = '200';

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

    /**
     * 转换交易类型编码为 think-pos 统一的支付类型
     * @param string $groupType 交易类型
     * @param string $cardType 刷卡类型，扫码交易类型时，此字段为空
     * @return string
     */
    public static function toPaymentType(string $groupType, string $cardType): string
    {
        if ('ZFB_SCAN' === $groupType) {
            return PaymentType::ALIPAY_QR;
        } elseif (self::isBankCardType($groupType)) {
            // 把大额扫码归属到刷卡，待验证
            return 'CREDIT' === $cardType ? PaymentType::CREDIT_CARD : PaymentType::DEBIT_CARD;
        }
        // 除了微信，支付宝扫码，刷卡，其他的默认为微信扫码
        return PaymentType::WECHAT_QR;
    }

    //<editor-fold desc="pos操作方法">

    /**
     * 设置机具押金
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     * @throws MissingParameterException
     */
    public function setPosDeposit(PosRequestDto $dto): PosProviderResponse
    {
        $dto->checkDeposit();
        $url = $this->getUrl('/agent/changeTerminalActivity');
        $params = [
            'sns' => $dto->getDeviceSn(),
            // 活动编号(机具政策为⾮融合版政策时，必传)
            'activityCashNo' => '',
            'operNo' => $this->config['agentNo'],
            'operName' => sprintf('代理编号%s', $this->config['agentNo']),
            'channelPolicy' => json_decode($dto->getDepositPackageCode(), true),
        ];
        try {
            $res = $this->post($url, $params);
            // 解析请求结果
            if ('0' !== $res['errorCount']) {
                $errorMsg = sprintf('pos服务商[%s]设置机具pos_sn=%s押金失败：%s', self::providerName(), $dto->getDeviceSn(), $res['message']);
                return PosProviderResponse::fail($errorMsg);
            }
        } catch (ProviderGatewayException $e) {
            $errorMsg = sprintf('pos服务商[%s]设置机具pos_sn=%s押金失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    /**
     * @throws MissingParameterException
     */
    public function getPosDeposit(PosDepositRequestDto $dto): PosDepositResponse
    {
        $dto->check();
        $url = $this->getUrl('/agent/selectActivityAmountList');
        $params = ['sn' => $dto->getDeviceSn()];
        try {
            $res = $this->post($url, $params);
            // 解析请求结果
            $depositResponse = PosDepositResponse::success();
            $depositResponse->setDeviceNo($dto->getDeviceSn());
            $depositResponse->setDeposit(Money::valueOfYuan(strval($res['activityAmount'] ?? '0')));
            $policyInfo = $res['channelPolicy'] ?? StrUtil::NULL;
            $depositResponse->setDepositPackageCode(json_encode($policyInfo, JSON_UNESCAPED_UNICODE));
        } catch (ProviderGatewayException $e) {
            $errorMsg = sprintf('pos服务商[%s]获取机具pos_sn=%s可用押金政策列表失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosDepositResponse::fail($errorMsg);
        }
        return $depositResponse;
    }
    //</editor-fold>

    //<editor-fold desc="商户操作方法">

    /**
     * @throws MissingParameterException
     */
    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        // 必备参数检查
        $dto->check();
        $url = $this->getUrl('/agent/changeMerchantFeeRate');
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
            if (self::isBankCardType($transType)) {
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
                // todo shali [2025/6/27] 用户反馈设置商户费率存在多次调用，如果某次调用失败了，如何解决
                $this->post($url, $item);
            }
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改商户merchant_no=%s费率失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }

        return PosProviderResponse::success();
    }

    function setMerchantSimFee(SimRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl('/agent/updateMerchantFlowInfo');
        $params = [
            'merchantNo' => $dto->getMerchantNo(),
            // 免收期，x 天，我们不配置，统一去 pos 平台配置
            // 'freeDays' => null,
            'merchantFlowList' => json_decode($dto->getSimPackageCode(), true),
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]设置商户merchant_no=%s sim卡套餐失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    function unbindPos(MerchantRequestDto $merchantRequestDto, PosRequestDto $posRequestDto): PosProviderResponse
    {
        $url = $this->getUrl('/agent/terminalUnBind');
        $params = [
            'sns' => $posRequestDto->getDeviceSn(),
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]解绑机具pos_sn=%s失败：%s', self::providerName(), $posRequestDto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }
    //</editor-fold>

    //<editor-fold desc="通知回调处理">
    /**
     * @param string $content
     * @return MerchantRegisterCallbackRequest
     * @throws ProviderGatewayException
     */
    function handleCallbackOfMerchantRegister(string $content): MerchantRegisterCallbackRequest
    {
        $data = $this->decryptAndVerifySign('商户注册信息', $content);
        return MerchantConvertor::toMerchantRegisterCallbackRequest($data);
    }

    /**
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfPosBind(string $content): PosBindCallbackRequest
    {
        $data = $this->decryptAndVerifySign('机具绑定', $content);
        return MerchantConvertor::toPosBindCallbackRequest($data);
    }

    /**
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfPosUnbind(string $content): PosBindCallbackRequest
    {
        $data = $this->decryptAndVerifySign('机具解绑', $content);
        $callbackRequest = PosBindCallbackRequest::success();
        $callbackRequest->setAgentNo($data['agentNo'] ?? StrUtil::NULL);
        $callbackRequest->setMerchantNo($data['merchantNo'] ?? StrUtil::NULL);
        $callbackRequest->setDeviceSn($data['terminalId'] ?? StrUtil::NULL);
        $callbackRequest->setStatus(PosStatus::UNBIND_SUCCESS);
        $callbackRequest->setModifyTime($data['createTime'] ?? LocalDateTime::now());
        return $callbackRequest;
    }

    /**
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfMerchantRateSet(string $content): MerchantRateSetCallbackRequest
    {
        $data = $this->decryptAndVerifySign('商户费率变更', $content);
        return MerchantConvertor::toMerchantRateSetCallbackRequest($data);
    }

    /**
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfTrans(string $content): PosTransCallbackRequest
    {
        $data = $this->decryptAndVerifySign('普通交易信息', $content);
        return PosConvertor::toPosTransCallbackRequest($data);
    }

    /**
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfSimTrans(string $content): PosTransCallbackRequest
    {
        $data = $this->decryptAndVerifySign('流量费扣费推送', $content);
        return PosConvertor::toPosTransCallbackRequestByLakala($data);
    }
    //</editor-fold>

    /**
     * 判断交易类型是否为银行卡类型
     * 云闪付小额属于扫码，大额属于刷卡
     * 银联云闪付小额属于扫码，大额属于刷卡
     * @param string $transType
     * @return bool
     */
    public static function isBankCardType(string $transType): bool
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

    //<editor-fold desc="请求/响应处理">
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
            'sign' => $this->sign($encryptData, $this->config['aesKey']),
        ];
        try {
            $res = $this->httpClient->post($url, $params, ['Content-Type' => 'application/x-www-form-urlencoded']);
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
        if (!$this->verifySign($res['data']['sign'], $res['data']['jsonData'], $this->config['aesKey'])) {
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
     * @throws ProviderGatewayException
     */
    private function decryptAndVerifySign(string $businessTitle, string $content): array
    {
        parse_str($content, $result);
        try {
            $params = $this->decryptData($result['data']);
        } catch (PhpMateException $e) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]解密[%s]回调数据失败：%s', self::providerName(), $businessTitle, $e->getMessage()));
        }
        $data = json_decode($params, true);
        $this->rawRequest = $data;
        if (empty($data['sign']) || empty($data['jsonData'])) {
            // 非移联标准回调数据格式
            throw new ProviderGatewayException(sprintf('pos服务商[%s][%s]回调数据格式错误', $businessTitle, self::providerName()));
        }
        if (false === $this->verifySign($data['sign'], $data['jsonData'], $this->config['md5Key'])) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s][%s]回调数据验签失败', $businessTitle, self::providerName()));
        }
        $decryptedData = json_decode($data['jsonData'], true);
        $this->rawRequest['jsonData'] = $decryptedData;
        $this->rawResponse = $this->getCallbackAckContent();
        return $decryptedData;
    }
    //</editor-fold>

    //<editor-fold desc="加解密验签">
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
     * @param string $password
     * @return string
     */
    private function sign(string $encryptData, string $password): string
    {
        return md5($encryptData . $password);
    }

    /**
     * 验签
     */
    private function verifySign(string $sign, string $jsonData, string $password): bool
    {
        $sign1 = $this->sign($jsonData, $password);
        return $sign === $sign1;
    }
    //</editor-fold>
}
