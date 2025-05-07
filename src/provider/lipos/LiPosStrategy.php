<?php declare(strict_types=1);

namespace think\pos\provider\lipos;

use Exception;
use shali\phpmate\core\util\RandomUtil;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\crypto\KeyUtil;
use shali\phpmate\crypto\SignUtil;
use shali\phpmate\http\HttpClient;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\response\PosInfoResponse;
use think\pos\PosStrategy;

/**
 * 力 POS 对接
 */
class LiPosStrategy extends PosStrategy
{
    /**
     * 回调成功返回内容
     */
    private const CALLBACK_OK_CONTENT = 'OK';

    /**
     * 响应 00 表示成功
     */
    private const RESPONSE_CODE_SUCCESS = '00';

    /**
     * 接口方法
     */
    private const API_METHOD = [
        'pos_info' => '/materialsDataQuery',
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
            // 失败 {"code":"98","msg":"终端信息不存在","success":false}
            $res = $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]查询pos_sn=%s信息失败：%s', self::providerName(), $dto->getDeviceSn(), $e->getMessage());
            return PosInfoResponse::fail($errorMsg);
        }
        if ($res['code'] !== self::RESPONSE_CODE_SUCCESS) {
            $errorMsg = sprintf('pos服务商[%s]查询pos_sn=%s信息失败：%s', self::providerName(), $dto->getDeviceSn(), $res['msg']);
            return PosInfoResponse::fail($errorMsg);
        }

        return self::toPosInfoResponse($res['data']);
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
    private function post(string $url, array $data, array $headers = ['Content-Type' => 'application/json'])
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
            $res = $this->httpClient->post($url, $params, $headers);
        } finally {
            $this->rawRequest = $this->httpClient->getRawRequest();
            $this->rawResponse = $this->httpClient->getRawResponse();
        }
        return json_decode($res, true);
    }

    /**
     * // todo shali [2025/5/7] 待验证，因为每次都是失败
     * @param $data
     * @return PosInfoResponse
     */
    private static function toPosInfoResponse($data): PosInfoResponse
    {
        $posInfoResponse = PosInfoResponse::success();
        $posInfoResponse->setDeviceNo($data['materialsNo']);
        $posInfoResponse->setSimPackageCode($data['materialsMachineInfo']);

        return $posInfoResponse;
    }
}