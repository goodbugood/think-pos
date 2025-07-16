<?php declare(strict_types=1);

namespace think\pos\provider\kunpeng;

use Exception;
use shali\phpmate\core\util\RandomUtil;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\crypto\KeyUtil;
use shali\phpmate\crypto\SignUtil;
use shali\phpmate\http\HttpClient;
use shali\phpmate\PhpMateException;
use think\pos\dto\request\CallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosProviderResponse;
use think\pos\exception\ProviderGatewayException;
use think\pos\PosStrategy;
use think\pos\provider\kunpeng\convertor\PosConvertor;

/**
 * 鲲鹏平台
 * 注意：鲲鹏的接口乱
 * 1. 金额都是元
 * 2. 请求接口费率是百分数
 * 3. 回调通知，费率都是小数
 */
class KunPengPosPlatform extends PosStrategy
{
    /**
     * 回调成功返回内容
     */
    protected const CALLBACK_ACK_CONTENT = 'OK';

    /**
     * 响应 00 表示成功
     */
    private const RESPONSE_CODE_SUCCESS = '00';

    /**
     * @var HttpClient
     */
    private $httpClient;

    public static function providerName(): string
    {
        return '鲲鹏/钱宝';
    }

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->httpClient = new HttpClient();
    }

    //<editor-fold desc="商户类接口">

    /**
     * 修改商户费率
     * @param MerchantRequestDto $dto
     * @return PosProviderResponse
     */
    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl('/gateway/customer/api/customer/modify/rate');
        $params = [
            'materialsNo' => $dto->getDeviceSn(),
            'rate' => [
                [
                    // 费率类型
                    'payTypeViewCode' => 'POS_CC',
                    // 费率
                    'rateValue' => $dto->getCreditRate()->toPercentage(6),
                    // 贷记卡封顶值
                    'cappingValue' => '0.00',
                ],
                // 借记卡
                [
                    'payTypeViewCode' => 'POS_DC',
                    'rateValue' => $dto->getDebitCardRate()->toPercentage(6),
                    'cappingValue' => $dto->getDebitCardCappingValue()->toYuan(),
                ],
                // 微信，NFC 统一使用微信费率
                [
                    'payTypeViewCode' => 'WECHAT',
                    'rateValue' => $dto->getWechatRate()->toPercentage(6),
                    'cappingValue' => '0.00',
                ],
                [
                    'payTypeViewCode' => 'NFC',
                    'rateValue' => $dto->getWechatRate()->toPercentage(6),
                    'cappingValue' => '0.00',
                ],
                // 支付宝
                [
                    'payTypeViewCode' => 'ALIPAY',
                    'rateValue' => $dto->getAlipayRate()->toPercentage(6),
                    'cappingValue' => '0.00',
                ],
            ],
        ];
        try {
            $data = $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改商户pos_sn=%s费率失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        // 检查审核结果
        if ('TRUE' === ($data['result'] ?? '')) {
            $errorMsg = sprintf('pos服务商[%s]修改商户pos_sn=%s费率失败：%s', self::providerName(), $dto->getMerchantNo(), $data['reason'] ?? '');
            return PosProviderResponse::fail($errorMsg);
        }
        // 修改贷记卡的提现手续费
        $withdrawFeeUrl = $this->getUrl('/gateway/customer/api/customer/modify/orderAddRate');
        try {
            $withdrawFeeRes = $this->post($withdrawFeeUrl, [
                'materialsNo' => $dto->getDeviceSn(),
                'rate' => [
                    [
                        'payTypeViewCode' => 'POS_CC',
                        'fixedValue' => $dto->getWithdrawFee()->toYuan(2),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改商户pos_sn=%s贷记卡提现手续费失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        // 检查审核结果
        if ('TRUE' === ($withdrawFeeRes['result'] ?? '')) {
            $errorMsg = sprintf('pos服务商[%s]修改商户pos_sn=%s费率失败：%s', self::providerName(), $dto->getMerchantNo(), $withdrawFeeRes['reason'] ?? '');
            return PosProviderResponse::fail($errorMsg);
        }

        return PosProviderResponse::success();
    }
    //</editor-fold>

    //<editor-fold desc="pos类接口">
    /**
     * pos 初始化
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     */
    public function initPosConfig(PosRequestDto $dto): PosProviderResponse
    {
        // 初始化代理押金
        return $this->setPosDeposit($dto);
    }

    /**
     * 鲲鹏终端变更政策接口
     * 鲲鹏的终端押金&通讯费打包成套餐，通过此接口进行修改
     * 注意：code=98&msg=绑定、激活设备暂不支持变更政策
     * @param SimRequestDto $dto
     * @return PosProviderResponse
     */
    function setSimFee(SimRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl('/gateway/admin/api/materialsApi/updateMaterialsPolicy');
        $params = [
            'materialsNo' => $dto->getDeviceSn(),
            // 非区间终端编号，进行变更
            'migrateType' => 'NO_ORDER',
            'agentNo' => $this->config['appId'],
            'materialsNoList' => [
                $dto->getDeviceSn(),
            ],
            'policyId' => $dto->getSimPackageCode(),
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改pos_sn=%s通信费失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    /**
     * 设置终端押金 = 服务费设置
     * 鲲鹏的终端押金&通讯费打包成套餐，通过此接口进行修改
     * 注意：绑定了商户的 pos 不能设置押金
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     */
    public function setPosDeposit(PosRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl('/gateway/admin/api/materialsApi/updateMaterialsPolicy');
        $params = [
            'materialsNo' => $dto->getDeviceSn(),
            // 非区间终端编号，进行变更
            'migrateType' => 'NO_ORDER',
            'agentNo' => $this->config['appId'],
            'materialsNoList' => [
                $dto->getDeviceSn(),
            ],
            'policyId' => $dto->getDepositPackageCode(),
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改pos_sn=%s服务费失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    /**
     * 商户绑定 pos
     */
    public function bindPos(MerchantRequestDto $merchantRequestDto, PosRequestDto $posRequestDto): PosProviderResponse
    {
        $url = $this->getUrl('/gateway/admin/api/materialsApi/materialsOperate');
        $params = [
            'customerNo' => $merchantRequestDto->getMerchantNo(),
            'agentNo' => $this->config['appId'],
            'materialsNo' => $posRequestDto->getDeviceSn(),
            'materialsOperate' => 'BINDED',
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]解绑pos_sn=%s失败：%s', self::providerName(), $posRequestDto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }

        return PosProviderResponse::success();
    }

    /**
     * 商户解绑 pos
     * @param MerchantRequestDto $merchantRequestDto
     * @param PosRequestDto $posRequestDto
     * @return PosProviderResponse
     */
    public function unbindPos(MerchantRequestDto $merchantRequestDto, PosRequestDto $posRequestDto): PosProviderResponse
    {
        $url = $this->getUrl('/gateway/admin/api/materialsApi/materialsOperate');
        $params = [
            'customerNo' => $merchantRequestDto->getMerchantNo(),
            'agentNo' => $this->config['appId'],
            'materialsNo' => $posRequestDto->getDeviceSn(),
            'materialsOperate' => 'UN_BIND',
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            // {"code":"98","msg":"商户信息不存在"}
            if (false !== mb_strpos($e->getMessage(), '信息不存在', 0, 'utf-8')) {
                // 应对用户在鲲鹏平台解绑后，再调用解绑接口
                return PosProviderResponse::success();
            }
            $errorMsg = sprintf('pos服务商[%s]解绑pos_sn=%s失败：%s', self::providerName(), $posRequestDto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }

        return PosProviderResponse::success();
    }
    //</editor-fold>

    //<editor-fold desc="回调通知处理">
    /**
     * @throws ProviderGatewayException
     */
    public function handleCallback(string $content)
    {
        $data = json_decode($content, true);
        $this->rawRequest = $data;
        if (empty($data['serviceType']) || empty($data['data']) || empty($data['encryptKey']) || empty($data['appId']) || empty($data['timestamp'])) {
            // 非鲲鹏 回调，拉倒吧不处理
            return CallbackRequest::fail(sprintf('非%s平台回调，无法处理', self::providerName()));
        }
        try {
            // 验签
            if (!empty($data['sign']) && false === $this->verifySign($data)) {
                $errorMsg = sprintf('%s平台 %s 回调验签失败', self::providerName(), $data['serviceType']);
                return CallbackRequest::fail($errorMsg);
            }
            // 解密
            $decrypted = $this->decrypt($data['encryptKey'], $data['data']);
        } catch (PhpMateException $e) {
            $errorMsg = sprintf('%s平台回调请求数据验签->解密异常：%s', self::providerName(), $e->getMessage());
            throw new ProviderGatewayException($errorMsg);
        }
        $decryptedData = json_decode($decrypted, true);
        $this->rawRequest['data'] = $decryptedData;
        $this->rawResponse = $this->getCallbackAckContent();
        if ('CUSTOMER_REGISTER' === $data['serviceType']) {
            // 鲲鹏这里虽然业务叫做商户注册，其实本质是终端绑定回调，仅商户第一次绑定的时候会触发，后续绑定不会触发
            return PosConvertor::toPosBindCallbackRequest($decryptedData);
        } elseif ('DEPOSIT_STOP_ORDER' === $data['serviceType']) {
            return PosConvertor::toPosTransCallbackRequestByDeposit($decryptedData);
        } elseif ('PAY_ORDER' === $data['serviceType']) {
            return PosConvertor::toPosTransCallbackRequestByNormal($decryptedData);
        } elseif ('SIM_STOP_ORDER' === $data['serviceType']) {
            return PosConvertor::toPosTransCallbackRequestBySim($decryptedData);
        } elseif ('MATERIAL_OPERATE_NOTIFY' === $data['serviceType']) {
            // 绑定解绑回调：鲲鹏技术反馈非首次绑定和解绑
            return PosConvertor::toPosUnBindCallbackRequest($decryptedData);
        }

        $errorMsg = sprintf('pos服务商[%s]通知了未知的业务类型：serviceType=%s', self::providerName(), $data['serviceType']);
        $this->rawResponse = $errorMsg;
        return CallbackRequest::fail($errorMsg);
    }

    //</editor-fold>

    private function getUrl(string $apiMethod): string
    {
        $gateway = $this->isTestMode() ? $this->config['testGateway'] : $this->config['gateway'];
        return $gateway . $apiMethod;
    }

    /**
     * aes 对称加密，填充模式 PKCS5Padding
     * 1. 随机一个 16 位的密钥，并使用该密钥进行 aes-ecb-pkcs5padding 对称加密请求 json 数据
     * 2. 利用平台的公钥对随机密钥进行非对称加密，并提交给平台
     * 签名：
     * 使用 SHA256WithRSA 进行签名
     * @param string $url
     * @param array $data
     * @return array
     * @throws PhpMateException
     * @throws ProviderGatewayException
     */
    private function post(string $url, array $data): array
    {
        // 加密和签名
        $password = RandomUtil::randomString(16);
        $params['data'] = $this->encryptData($password, json_encode($data));
        $params['encryptKey'] = $this->encryptPassword($password);
        $params['appId'] = $this->config['appId'];
        $params['timestamp'] = time();
        $params['sign'] = $this->sign($params);
        try {
            $rawRes = $this->httpClient->post($url, $params, ['Content-Type' => 'application/json']);
        } catch (Exception $e) {
            throw new ProviderGatewayException($e->getMessage());
        } finally {
            $this->rawRequest = $this->httpClient->getRawRequest();
            // 请求参数记录明文
            $this->rawRequest['params']['data'] = $data;
            $this->rawResponse = $this->httpClient->getRawResponse();
        }
        $res = json_decode($rawRes, true);
        // 验签->检查是否成功->解密
        try {
            // 检查
            if ($res['code'] !== self::RESPONSE_CODE_SUCCESS) {
                $errorMsg = sprintf('code=%s&msg=%s', $res['code'], $res['msg']);
                throw new ProviderGatewayException($errorMsg);
            }
            if (!empty($res['sign']) && false === $this->verifySign($res)) {
                throw new ProviderGatewayException('验签失败');
            }
            // 注意绑定和解绑，code = 00，data 可能缺失导致，解密报错
            if (empty($res['data'])) {
                return [];
            }
            $content = $this->decrypt($res['encryptKey'], $res['data']);
            // 日志记录明文
            $this->rawRequest['params'] = $data;
            $this->rawResponse['decryptedBody'] = $content;
            // 25年6月24日，鲲鹏很多接口，返回消息内容格式不统一，这里做兼容
            return StrUtil::isJson($content) ? json_decode($content, true) : [$content];
        } catch (PhpMateException $e) {
            $errorMsg = sprintf('请求响应数据验签->解密异常：%s', $e->getMessage());
            throw new ProviderGatewayException($errorMsg);
        }
    }

    /**
     * @throws PhpMateException
     */
    private function sign(array $data): string
    {
        // 字典序
        unset($data['sign'], $data['success']);
        $content = StrUtil::httpBuildQuery($data, true);
        return SignUtil::signBySHA256withRSAToBase64(KeyUtil::toPrivateKeyValueOfBase64Str($this->config['privateKey']), $content);
    }

    /**
     * @throws PhpMateException
     */
    private function verifySign(array $data): bool
    {
        $sign = $data['sign'];
        unset($data['success'], $data['sign']);
        $content = StrUtil::httpBuildQuery($data, true);
        return SignUtil::verifySignBySHA256withRSAToBase64(KeyUtil::toPublicKeyValueOfBase64Str($this->config['platformPublicKey']), $sign, $content);
    }

    /**
     * @throws PhpMateException
     */
    private function decrypt(string $encryptKey, string $encrypted): string
    {
        $password = EncryptUtil::decryptByRSA_ECB_PKCS1PaddingToBase64(KeyUtil::toPrivateKeyValueOfBase64Str($this->config['privateKey']), $encryptKey);
        return EncryptUtil::decryptByAES_ECB_PKCS5PaddingToBase64($password, $encrypted);
    }

    /**
     * 使用对称加密密码加密数据
     * @throws PhpMateException
     */
    private function encryptData(string $password, string $json): string
    {
        return EncryptUtil::encryptByAES_ECB_PKCS5PaddingToBase64($password, $json);
    }

    /**
     * 加密对称加密数据的密码
     * @throws PhpMateException
     */
    private function encryptPassword(string $password): string
    {
        return EncryptUtil::encryptByRSA_ECB_PKCS1PaddingToBase64(KeyUtil::toPublicKeyValueOfBase64Str($this->config['platformPublicKey']), $password);
    }
}
