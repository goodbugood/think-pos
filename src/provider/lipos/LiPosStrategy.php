<?php declare(strict_types=1);

namespace think\pos\provider\lipos;

use Exception;
use shali\phpmate\core\util\RandomUtil;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\crypto\KeyUtil;
use shali\phpmate\crypto\SignUtil;
use shali\phpmate\http\HttpClient;
use shali\phpmate\PhpMateException;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosCallbackRequest;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosInfoResponse;
use think\pos\dto\response\PosProviderResponse;
use think\pos\exception\ProviderGatewayException;
use think\pos\PosStrategy;
use think\pos\provider\lipos\convertor\PosConvertor;

/**
 * 力 POS 对接
 */
class LiPosStrategy extends PosStrategy
{
    /**
     * 回调成功返回内容
     */
    private const CALLBACK_ACK_CONTENT = 'OK';

    /**
     * 响应 00 表示成功
     */
    private const RESPONSE_CODE_SUCCESS = '00';

    /**
     * 接口方法
     */
    private const API_METHOD = [
        // 查询 pos 终端
        'pos_info' => '/materialsDataQuery',
        // 设置 pos 费率
        'modify_pos_rate' => '/batchSetDefaultRate',
        // 设置 pos 通信服务费
        'modify_pos_sim_fee' => '/simFeeChange',
        // 设置 pos 押金=服务费
        'modify_pos_deposit' => '/machineFeeChange',
        // 设置商户费率
        'modify_merchant_rate' => '/customerRateModify',
    ];

    /**
     * @var HttpClient
     */
    private $httpClient;

    private static function sha256WithRSAVerify(bool $json_encode, string $sign, $publicKey)
    {
    }

    public static function providerName(): string
    {
        return '力POS';
    }

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->httpClient = new HttpClient();
    }

    function getPosInfo(PosRequestDto $dto): PosInfoResponse
    {
        $url = $this->getUrl(self::API_METHOD['pos_info']);
        $params = [
            'materialsNo' => $dto->getDeviceSn(),
        ];
        try {
            $res = $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]查询pos_sn=%s信息失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosInfoResponse::fail($errorMsg);
        }

        return PosConvertor::toPosInfoResponse($res);
    }

    /**
     * 修改 pos 费率
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     */
    function setPosRate(PosRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::API_METHOD['modify_pos_rate']);
        $params = [
            // 该接口支持批量更新，我们还不支持
            'materialsNoList' => [$dto->getDeviceSn(),],
            'materialsDefaultRateList' => [
                [
                    // 信用卡
                    'payTypeViewCode' => 'POS_CC',
                    'rateValue' => $dto->getCreditRate()->toPercentage(6),
                    // 附加费用
                    'fixedValue' => '0.00',
                    // 贷记卡封顶值
                    'cappingValue' => '0.00',
                ],
                [
                    // 借记卡
                    'payTypeViewCode' => 'POS_DC',
                    'rateValue' => $dto->getDebitCardRate()->toPercentage(6),
                    // 附加费用
                    'fixedValue' => '0.00',
                    // 借记卡封顶值
                    'cappingValue' => $dto->getDebitCardCappingValue()->toYuan(),
                ],
            ]
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改pos_sn=%s费率失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    /**
     * 设置 pos 通信服务费，其实就是 pos 机里的 esim 流量费
     * @param SimRequestDto $dto
     * @return PosProviderResponse
     */
    function setSimFee(SimRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::API_METHOD['modify_pos_sim_fee']);
        $params = [
            'materialsNo' => $dto->getDeviceSn(),
            // todo shali [2025/5/9] 具体的套餐信息从何处获取，力 pos 那边还未定下来
            'simDeductionsList' => [
                [
                    // 通讯服务费阶段 integer
                    'simRuleIndex' => null,
                    // 通讯服务费档位 integer
                    'simPhaseIndex' => null,
                    // 通讯服务费扣费起始天数 integer
                    'beginDayNum' => null,
                ]
            ],
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
     * 注意：绑定了商户的 pos 不能设置押金
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     */
    public function setPosDeposit(PosRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::API_METHOD['modify_pos_deposit']);
        $params = [
            'materialsNo' => $dto->getDeviceSn(),
            // 押金档位 integer
            'machinePhaseIndex' => intval($dto->getDepositPackageCode()),
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改pos_sn=%s押金失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    /**
     * 修改商户费率
     * @param MerchantRequestDto $dto
     * @return PosProviderResponse
     */
    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        $url = $this->getUrl(self::API_METHOD['modify_merchant_rate']);
        $params = [
            'customerNo' => $dto->getMerchantNo(),
            'customerRateList' => [
                [
                    // 信用卡
                    'payTypeViewCode' => 'POS_CC',
                    'rateValue' => $dto->getCreditRate()->toPercentage(6),
                    // 附加费用
                    'fixedValue' => '0.00',
                    // 贷记卡封顶值
                    'cappingValue' => '0.00',
                ],
                [
                    // 借记卡
                    'payTypeViewCode' => 'POS_DC',
                    'rateValue' => $dto->getDebitCardRate()->toPercentage(6),
                    // 附加费用
                    'fixedValue' => '0.00',
                    // 借记卡封顶值
                    'cappingValue' => $dto->getDebitCardCappingValue()->toYuan(),
                ],
            ],
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]修改商户merchant_no=%s费率失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }

        return PosProviderResponse::success();
    }

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
     * @throws Exception
     */
    private function post(string $url, array $data): array
    {
        // 加密和签名
        $password = RandomUtil::randomString(16);
        $params['data'] = EncryptUtil::encryptByAES_ECB_PKCS5PaddingToBase64($password, json_encode($data));
        $params['encryptKey'] = EncryptUtil::encryptByRSA_ECB_PKCS1PaddingToBase64(KeyUtil::toPublicKeyValueOfBase64Str($this->config['platformPublicKey']), $password);
        $params['appId'] = $this->config['agentNo'];
        $params['timestamp'] = time();
        $content = StrUtil::httpBuildQuery($params, true);
        $params['sign'] = SignUtil::signBySHA256withRSAToBase64(KeyUtil::toPrivateKeyValueOfBase64Str($this->config['privateKey']), $content);
        try {
            $res = $this->httpClient->post($url, $params, ['Content-Type' => 'application/json']);
        } finally {
            $this->rawRequest = $this->httpClient->getRawRequest();
            $this->rawResponse = $this->httpClient->getRawResponse();
        }
        $res = json_decode($res, true);
        // 验签->检查是否成功->解密
        try {
            if (!empty($res['sign']) && false === $this->verifySign($res)) {
                throw new ProviderGatewayException('验签失败');
            }
            // 检查
            if ($res['code'] !== self::RESPONSE_CODE_SUCCESS) {
                $errorMsg = sprintf('code=%s&msg=%s', $res['code'], $res['msg']);
                throw new ProviderGatewayException($errorMsg);
            }
            $content = $this->decrypt($res['encryptKey'], $res['data']);
            // 日志记录明文
            $this->rawRequest['params'] = $data;
            $this->rawResponse['body'] = $content;
            return json_decode($content, true);
        } catch (PhpMateException $e) {
            $errorMsg = sprintf('请求响应数据解密异常：%s', $e->getMessage());
            throw new ProviderGatewayException($errorMsg);
        }
    }

    /**
     * @throws PhpMateException
     */
    public function verifySign(array $data): bool
    {
        $sign = $data['sign'];
        unset($data['success'], $data['sign']);
        $content = StrUtil::httpBuildQuery($data, true);
        return SignUtil::verifySignBySHA256withRSAToBase64(KeyUtil::toPublicKeyValueOfBase64Str($this->config['platformPublicKey']), $sign, $content);
    }

    /**
     * @throws PhpMateException
     */
    public function decrypt(string $encryptKey, string $encrypted): string
    {
        $password = EncryptUtil::decryptByRSA_ECB_PKCS1PaddingToBase64(KeyUtil::toPrivateKeyValueOfBase64Str($this->config['privateKey']), $encryptKey);
        return EncryptUtil::decryptByAES_ECB_PKCS5PaddingToBase64($password, $encrypted);
    }
}