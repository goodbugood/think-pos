<?php declare(strict_types=1);

namespace think\pos\provider\yilian;

use Exception;
use shali\phpmate\core\date\LocalDateTime;
use shali\phpmate\core\util\StrUtil;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\http\HttpClient;
use shali\phpmate\PhpMateException;
use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;
use think\pos\constant\PaymentType;
use think\pos\constant\PosStatus;
use think\pos\dto\request\callback\MerchantActivateCallbackRequest;
use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;
use think\pos\dto\request\callback\MerchantRateSyncCallbackRequest;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\dto\request\callback\PosSettleCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosDepositRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosDepositResponse;
use think\pos\dto\response\PosProviderResponse;
use think\pos\dto\response\SimInfoResponse;
use think\pos\exception\MissingParameterException;
use think\pos\exception\ProviderGatewayException;
use think\pos\PosStrategy;
use think\pos\provider\yilian\convertor\MerchantConvertor;
use think\pos\provider\yilian\convertor\PosConvertor;
use think\pos\provider\yilian\convertor\PosSettleConvertor;

/**
 * 注意：
 * 1. 移联的接口文档涉及金额的，单位均为元
 * 2. 涉及费率的，上行接口均为百分数，通知数据是小数，太他妈乱了
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
     * 不要调整顺序，微信扫码和银二大额必须间隔 5-6s 请求
     * 交易类型=支付方式分组
     * WX_SCAN，ZFB_SCAN，JSAPI 仅需要设置一种
     * 25/9/18 移联反馈只有 POS_STANDARD，POS_VIP，CLOUD_QUICK_PASS 3 种交易类型不需要扫码报件
     */
    private const PARAMS_TRANS_TYPE_MAP = [
        // 微信扫码，主扫和被扫
        'wx_scan' => 'WX_SCAN',
        // POS刷卡-标准类
        'pos_standard' => 'POS_STANDARD',
        // POS刷卡-VIP，移联对接技术反馈 VIP 暂时没有
        // 'pos_vip' => 'POS_VIP',
        // POS刷卡-云闪付，25/7/11 海科云闪付执行扫码费率，非海科执行贷记卡费率
        'cloud_quick_pass' => 'CLOUD_QUICK_PASS',
        // 支付宝扫码，主扫和被扫
        // 'zfb_scan' => 'ZFB_SCAN',
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
        // 银联二维码大额
        'yl_code_more' => 'YL_CODE_MORE',
        // 京东白条
        'jd_baitiao' => 'JD_BAR',
    ];

    /**
     * 限制最大费率的交易类型 map
     */
    private const LIMIT_MAX_RATE_TRANS_TYPE_MAP = [
        self::PARAMS_TRANS_TYPE_MAP['yl_code_more'],
        self::PARAMS_TRANS_TYPE_MAP['yl_jsapi_more'],
    ];

    /**
     * @var HttpClient
     */
    private $httpClient;

    public static function providerName(): string
    {
        return '移联POS平台';
    }

    /**
     * @var array[] 配置文件 demo
     */
    protected $config = [
        'testGateway' => null,
        'gateway' => null,
        // 区分银行卡种类，贷记卡和借记卡单独设置的渠道列表
        'use_bankcard_type_channel_list' => [
            'HLB'
        ],
        'channel_list' => [
            // 海科渠道
            'HK' => [
                // 海科渠道在用政策列表
                'policy_list' => [
                    '海科买断版',
                ],
                'trans_type_map' => [
                    // 刷卡交易
                    'card' => [
                        YiLianPosPlatform::PARAMS_TRANS_TYPE_MAP['pos_standard'],
                        YiLianPosPlatform::PARAMS_TRANS_TYPE_MAP['yl_code_more'],
                        YiLianPosPlatform::PARAMS_TRANS_TYPE_MAP['yl_jsapi_more'],
                    ],
                    // 扫码交易
                    'scan' => [
                        YiLianPosPlatform::PARAMS_TRANS_TYPE_MAP['wx_scan'],
                        YiLianPosPlatform::PARAMS_TRANS_TYPE_MAP['yl_code_less'],
                        YiLianPosPlatform::PARAMS_TRANS_TYPE_MAP['yl_jsapi_less'],
                        YiLianPosPlatform::PARAMS_TRANS_TYPE_MAP['cloud_quick_pass'],// 费率 0.3-0.48
                    ],
                ],
            ],
        ]
    ];

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->httpClient = new HttpClient();
    }

    /**
     * 转换交易类型编码为 think-pos 统一的支付类型
     * @param string $groupType 交易类型
     * @param string $cardType 刷卡类型，扫码交易类型时，此字段为空
     * @param string $policyName 政策名称，不同政策的云闪付走不同费率
     * @return string
     */
    public function toPaymentType(string $groupType, string $cardType, string $policyName): string
    {
        if ('ZFB_SCAN' === $groupType) {
            return PaymentType::ALIPAY_QR;
        }
        $channelCode = $this->getChannelCodeByPolicyName($policyName);
        if ($this->isBankCardType($groupType, $channelCode)) {
            // 把大额扫码归属到刷卡，待验证
            return 'CREDIT' === $cardType ? PaymentType::CREDIT_CARD : PaymentType::DEBIT_CARD;
        }
        // 除了微信，支付宝扫码，刷卡，其他的默认为微信扫码
        return PaymentType::WECHAT_QR;
    }

    //<editor-fold desc="pos操作方法">

    /**
     * pos 入库初始化
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     * @throws MissingParameterException
     */
    public function initPosConfig(PosRequestDto $dto): PosProviderResponse
    {
        return $this->setPosDeposit($dto);
    }

    /**
     * @return array [{"channelName":"海科支付","channelCode":"HK"},{"channelName":"合利宝","channelCode":"HLB"},{"channelName":"银盛","channelCode":"YS"},{"channelName":"中付","channelCode":"ZF"}]
     * @throws ProviderGatewayException
     */
    private function getPosDepositList(string $posSn): array
    {
        $url = $this->getUrl('/basic/queryRegisterChannel');
        $params = ['sn' => $posSn];
        try {
            $res = $this->post($url, $params);
        } catch (ProviderGatewayException $e) {
            $errorMsg = sprintf('pos服务商[%s]查询机具pos_sn=%s押金列表失败：%s', self::providerName(), $posSn, $e->getMessage());
            throw new ProviderGatewayException($errorMsg);
        }
        return $res;
    }

    /**
     * 获取商户的渠道扫码报件状态
     * @param string $merchantNo
     * @return bool 返回商户扫码报件成功状态，多渠道商户有一个渠道扫码报件成功即返回成功
     * @throws ProviderGatewayException
     */
    private function getMerchantReportStatus(string $merchantNo): bool
    {
        $url = $this->getUrl('/merchant/scanReportStatusQuery');
        $params = ['merchantNo' => $merchantNo];
        try {
            // [{"merchantAbbrName":"格青衣物清洗店","acqNo":"10016","reportStatus":"SUCCESS","needSign":"0","channelCode":"ZF_YK"},{"merchantAbbrName":"咏绿休闲饮品店","acqNo":"10017","reportStatus":"SUCCESS","needSign":"1","channelCode":"LKL_SCAN"},{"merchantAbbrName":"银言鞋服综合店","acqNo":"10015","reportStatus":"SUCCESS","needSign":"0","channelCode":"YS_SCAN"}]
            $res = $this->post($url, $params);
        } catch (ProviderGatewayException $e) {
            $errorMsg = sprintf('pos服务商[%s]查询商户%s的渠道扫码报件状态失败：%s', self::providerName(), $merchantNo, $e->getMessage());
            throw new ProviderGatewayException($errorMsg);
        }
        // 25/9/16 移联反馈多渠道商户，如果有一个扫码渠道报件成功，就可以修改扫码支付方式的费率
        foreach ($res as $item) {
            if ('SUCCESS' === $item['reportStatus']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取商户的渠道借记卡封顶值
     */
    private function getTopTransFee(string $merchantNo): Money
    {
        $url = $this->getUrl('/agent/merchantFeeRateList');
        $params = ['merchantNo' => $merchantNo];
        try {
            $res = $this->post($url, $params);
        } catch (ProviderGatewayException $e) {
            $errorMsg = sprintf('pos服务商[%s]查询商户%s的渠道借记卡封顶值失败：%s', self::providerName(), $merchantNo, $e->getMessage());
            throw new ProviderGatewayException($errorMsg);
        }
        foreach ($res as $item) {
            if ('DEBIT' === $item['cardType']) {
                return Money::valueOfYuan($item['topTransFee']);
            }
        }
        return $dto->getDebitCardCappingValue();
    }

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
        // 解析所有渠道押金列表
        // [{"channelCode":"ZF","activityCashNo":"ACN0000140184"},{"channelCode":"HK","activityCashNo":"ACN0000140179"},{"channelCode":"LS","activityCashNo":"ACN0000138296"}]
        $allChannelDepositList = json_decode($dto->getDepositPackageCode(), true);
        $channelActivateNoMap = array_column($allChannelDepositList, 'activityCashNo', 'channelCode');
        $params = [
            'sns' => $dto->getDeviceSn(),
            // 活动编号(机具政策为⾮融合版政策时，必传)
            'activityCashNo' => '',
            'operNo' => $this->config['agentNo'],
            'operName' => sprintf('代理编号%s', $this->config['agentNo']),
        ];
        try {
            // 先查询押金列表
            $depositList = $this->getPosDepositList($dto->getDeviceSn());
            if (empty($depositList)) {
                throw new ProviderGatewayException(sprintf('pos服务商[%s]查询机具pos_sn=%s支持的押金列表为空，无法设置押金', self::providerName(), $dto->getDeviceSn()));
            }
            $channelPolicy = [];
            foreach ($depositList as $item) {
                $channelCode = $item['channelCode'];
                $activityCashNo = $channelActivateNoMap[$channelCode] ?? null;
                if (null === $activityCashNo) {
                    throw new ProviderGatewayException(sprintf('pos服务商[%s]机具pos_sn=%s支持[%s]渠道，但未提交对应渠道的押金政策', self::providerName(), $dto->getDeviceSn(), $item['channelName']));
                }
                $channelPolicy[] = [
                    'channelCode' => $item['channelCode'],
                    'activityCashNo' => $activityCashNo,
                ];
            }
            if (1 == count($channelPolicy)) {
                // 单渠道数据
                $params['activityCashNo'] = $channelPolicy[0]['activityCashNo'];
            } else {
                // 多渠道数据
                $params['channelPolicy'] = $channelPolicy;
            }
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
     * @throws MissingParameterException|ProviderGatewayException
     */
    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        $debitTopTransFee = null;
        // 必备参数检查
        $dto->check();
        $deviceSn = $dto->getDeviceSn();
        // 先查询押金列表
        $depositList = $this->getPosDepositList($deviceSn);
        if (empty($depositList)) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]查询机具pos_sn=%s支持的押金列表为空，无法设置商户费率', self::providerName(), $deviceSn));
        } elseif (empty($depositList[0]['channelCode'])) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]查询机具pos_sn=%s支持的押金列表缺失channelCode，无法设置商户费率', self::providerName(), $deviceSn));
        }
        $channelCode = $depositList[0]['channelCode'];
        if (empty($this->config['channel_list'][$channelCode])) {
            throw new ProviderGatewayException(sprintf('pos服务商[%s]暂不支持对商户 %s 的 %s 渠道设置商户费率，请配置', self::providerName(), $dto->getMerchantNo(), $channelCode));
        }
        // 查询商户的渠道扫码报件状态
        $scanReportStatus = $this->getMerchantReportStatus($dto->getMerchantNo());
        $params = [];
        foreach (self::PARAMS_TRANS_TYPE_MAP as $transType) {
            // 有些交易类型的费率修改的前提是渠道扫码报件成功
            if (self::isYiLianScanType($transType) && !$scanReportStatus) {
                // 跳过的渠道扫码交易类型，只能依靠扫码报件状态推送再来修改
                continue;
            }
            $item = [
                'merchantNo' => $dto->getMerchantNo(),
                // 交易类型
                'groupType' => $transType,
                // 费率百分数
                'transRate' => null,
                // 提现费率，移联比较特殊，除了大额刷卡，小额扫码的都固定了提现费率 0.03%
                'withdrawRate' => $this->getScanWithdrawRate($transType),
                // 提现费单位类型，FIXED 固定金额，PERCENT 百分比
                'withdrawRateUnit' => $this->getScanWithdrawRateUnit($transType),
            ];
            if (self::isBankCardType($transType, $channelCode)) {
                $useBankCardType = $this->useBankCardType($transType, $channelCode);
                foreach (self::PARAMS_CARD_TYPE_MAP as $cardType) {
                    // 检查是否区分银行卡类型来设置费率
                    $item['cardType'] = $useBankCardType ? $cardType : 'UNLIMIT';
                    if ($useBankCardType && self::PARAMS_CARD_TYPE_MAP['debit'] === $cardType) {
                        if (is_null($debitTopTransFee)) {
                            // 通过接口获取商户的借记卡封顶值
                            $debitTopTransFee = $this->getTopTransFee($dto->getMerchantNo());
                            $dto->setDebitCardCappingValue($debitTopTransFee);
                        }
                        // 借记卡交易手续费封顶值
                        $item['topTransFee'] = $dto->getDebitCardCappingValue()->toYuan();
                        // 借记卡交易无提现手续费
                        $item['withdrawRateUnit'] = 'FIXED';
                        $item['withdrawRate'] = '0';
                        // 刷卡限制最大费率
                        $rate = $this->limitBankCardRate($transType, $dto->getCreditRate());
                        $item['transRate'] = $rate->toPercentage();
                    } else {
                        // 信用卡交易无手续费封顶值，移除
                        unset($item['topTransFee']);
                        // 仅贷记卡支持提现手续费，且固定金额
                        $item['withdrawRateUnit'] = $this->getBankCardWithdrawRateUnit($transType);
                        $item['withdrawRate'] = $this->getBankCardWithdrawRate($transType, $dto->getWithdrawFee());
                        // 刷卡限制最大费率
                        $rate = $this->limitBankCardRate($transType, $dto->getCreditRate());
                        $item['transRate'] = $rate->toPercentage();
                    }
                    $params[] = $item;
                    if (!$useBankCardType) {
                        break;
                    }
                }
            } elseif ((!is_null($dto->getWechatRate()) || !is_null($dto->getAlipayRate())) && $this->isScanType($transType, $channelCode)) {
                // 不传递扫码费率
                $item['transRate'] = $dto->getWechatRate() ? $dto->getWechatRate()->toPercentage() : $dto->getAlipayRate()->toPercentage();
                $params[] = $item;
            }
        }
        $errorMsgs = [];
        $url = $this->getUrl('/agent/changeMerchantFeeRate');
        foreach ($params as $item) {
            try {
                $res = $this->post($url, $item);
                if ('0' !== ($res['errorCount'] ?? '')) {
                    $errorMsgs[] = sprintf('修改 %s 费率失败;', $item['groupType']);
                }
            } catch (Exception $e) {
                $errorMsg = sprintf('pos服务商[%s]修改商户merchant_no=%s - %s 费率失败：%s', self::providerName(), $dto->getMerchantNo(), $item['groupType'], $e->getMessage());
                return PosProviderResponse::fail($errorMsg);
            }
        }
        if (count($errorMsgs) > 0) {
            return PosProviderResponse::fail(implode(' | ', $errorMsgs));
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
        if (null !== $dto->getFreeDays()) {
            $params['freeDays'] = strval($dto->getFreeDays());
        }
        try {
            $res = $this->post($url, $params);
            if (self::RESPONSE_CODE_SUCCESS !== $res['code']) {
                throw new ProviderGatewayException(sprintf('code=%s&message=%s', $res['code'], $res['message']));
            }
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]设置商户merchant_no=%s sim卡套餐失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return PosProviderResponse::fail($errorMsg);
        }
        return PosProviderResponse::success();
    }

    /**
     * 获取商户的sim卡套餐信息
     * @param SimRequestDto $dto
     * @return SimInfoResponse
     */
    public function getMerchantSimFeeInfo(SimRequestDto $dto): SimInfoResponse
    {
        $url = $this->getUrl('/agent/queryMerchantFlowInfo');
        $params = [
            'merchantNo' => $dto->getMerchantNo(),
        ];
        try {
            $this->post($url, $params);
        } catch (Exception $e) {
            $errorMsg = sprintf('pos服务商[%s]获取商户merchant_no=%s sim卡套餐失败：%s', self::providerName(), $dto->getMerchantNo(), $e->getMessage());
            return SimInfoResponse::fail($errorMsg);
        }
        $simInfoResponse = SimInfoResponse::success();
        $simInfoResponse->setBody($this->rawResponse['decryptedBody']);
        return $simInfoResponse;
    }

    function unbindPos(MerchantRequestDto $merchantRequestDto, PosRequestDto $posRequestDto): PosProviderResponse
    {
        $url = $this->getUrl('/agent/terminalUnBind');
        $params = [
            'sns' => $posRequestDto->getDeviceSn(),
        ];
        try {
            $res = $this->post($url, $params);
            if ('0' !== $res['failNum']) {
                throw new ProviderGatewayException(sprintf('code=%s&message=%s', $res['code'] ?? StrUtil::NULL, $res['failResaon'] ?? StrUtil::NULL));
            }
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
     * @deprecated 由于移联的注册信息和绑定一起推送，目前二者合并为绑定回调，以绑定为准
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
    function handleCallbackOfMerchantActivate(string $content): MerchantActivateCallbackRequest
    {
        $data = $this->decryptAndVerifySign('商户激活', $content);
        return MerchantConvertor::toMerchantActivateCallbackRequest($data);
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
        $unbindDateTime = $data['createTime'] ? LocalDateTime::valueOfString($data['createTime']) : LocalDateTime::now();
        $callbackRequest->setModifyTime($unbindDateTime);
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
     * 处理扫码报件状态推送
     * @param string $content
     * @return MerchantRateSyncCallbackRequest
     * @throws ProviderGatewayException
     */
    function handleCallbackOfMerchantRateSync(string $content): MerchantRateSyncCallbackRequest
    {
        $data = $this->decryptAndVerifySign('扫码报件状态推送', $content);
        return MerchantConvertor::toMerchantRateSyncCallbackRequest($data);
    }

    /**
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfTrans(string $content): PosTransCallbackRequest
    {
        $data = $this->decryptAndVerifySign('普通交易信息', $content);
        return PosConvertor::toPosTransCallbackRequest($data, $this);
    }

    /**
     * 流量卡止付订单通知
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfSimTrans(string $content): PosTransCallbackRequest
    {
        $data = $this->decryptAndVerifySign('流量费扣费推送', $content);
        return PosConvertor::toPosTransCallbackRequestByLakala($data);
    }

    /**
     * @throws ProviderGatewayException
     */
    public function handleCallbackOfWithdrawSettle(string $content): PosSettleCallbackRequest
    {
        $data = $this->decryptAndVerifySign('提现结算推送', $content);
        return PosSettleConvertor::toPosSettleCallbackRequest($data);
    }
    //</editor-fold>

    /**
     * 判断交易类型是否为银行卡类型
     * 云闪付小额属于扫码，大额属于刷卡
     * 银联云闪付小额属于扫码，大额属于刷卡
     * @param string $transType
     * @param string $channelCode
     * @return bool
     */
    private function isBankCardType(string $transType, string $channelCode): bool
    {
        return in_array($transType, $this->config['channel_list'][$channelCode]['trans_type_map']['card']);
    }

    /**
     * 判断交易类型在指定政策下是否为扫码类型
     * @param string $transType
     * @param string $channel 渠道
     * @return bool
     */
    private function isScanType(string $transType, string $channel): bool
    {
        return in_array($transType, $this->config['channel_list'][$channel]['trans_type_map']['scan']);
    }

    /**
     * 移联扫码交易类型判断
     * 扫码类型需要报件成功方可修改费率
     * @param string $transType
     * @return bool
     */
    private function isYiLianScanType(string $transType): bool
    {
        return in_array($transType, [
            self::PARAMS_TRANS_TYPE_MAP['wx_scan'],
            self::PARAMS_TRANS_TYPE_MAP['yl_code_more'],
            self::PARAMS_TRANS_TYPE_MAP['yl_code_less'],
            self::PARAMS_TRANS_TYPE_MAP['yl_jsapi_more'],
            self::PARAMS_TRANS_TYPE_MAP['yl_jsapi_less'],
            self::PARAMS_TRANS_TYPE_MAP['jd_baitiao'],
        ]);
    }

    /**
     * 判断交易类型在指定政策下是否区分借贷记卡进行费率设置
     * @param string $transType
     * @param string $channelCode
     * @return bool
     */
    private function useBankCardType(string $transType, string $channelCode): bool
    {
        return self::PARAMS_TRANS_TYPE_MAP['pos_standard'] == $transType && in_array($channelCode, $this->config['use_bankcard_type_channel_list']);
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

    /**
     * 刷卡费率限制
     * 由于银联刷卡费率限制，所以该方法会检查刷卡费率是否超出移联最大刷卡费率，如果超出，你的刷卡费率会被替换为移联最大刷卡费率
     * @param string $transType 交易类型
     * @param Rate $rate
     * @return Rate
     */
    private function limitBankCardRate(string $transType, Rate $rate): Rate
    {
        // 25.6.30 仅银联二维码大额，银联云闪付大额限制最大交易费率
        if (!in_array($transType, self::LIMIT_MAX_RATE_TRANS_TYPE_MAP)) {
            return $rate;
        }
        $maxRate = Rate::valueOfPercentage($this->config['maxBankCardRate'] ?? '0.63');
        if (bccomp($rate->toPercentage(), $maxRate->toPercentage(), 2) > 0) {
            return $maxRate;
        }
        return $rate;
    }

    /**
     * 25/7/10移联反馈，提现手续费率取决于单位，固定金额使用正数，百分比使用小数
     * @param string $groupType
     * @return string
     */
    private function getScanWithdrawRate(string $groupType): string
    {
        if (self::PARAMS_TRANS_TYPE_MAP['cloud_quick_pass'] === $groupType) {
            // 移联目前 pos 刷卡-云闪付无交易提现手续费，这点不同扫码
            $withdrawRate = '0.00';
        } else {
            $withdrawRate = $this->config['scanTypeWithdrawRate'] ?? '0.03';
        }
        $scale = 'PERCENT' === $this->getScanWithdrawRateUnit($groupType) ? 2 : 0;
        return Rate::valueOfPercentage($withdrawRate)->toPercentage($scale);
    }

    private function getScanWithdrawRateUnit(string $transType): string
    {
        if (self::PARAMS_TRANS_TYPE_MAP['cloud_quick_pass'] === $transType) {
            return 'FIXED';
        }
        return 'PERCENT';
    }

    private function getBankCardWithdrawRateUnit(string $transType): string
    {
        if (in_array($transType, [
            self::PARAMS_TRANS_TYPE_MAP['yl_code_more'],
            self::PARAMS_TRANS_TYPE_MAP['yl_jsapi_more'],
        ])) {
            // YL_CODE_MORE 和 YL_JSAPI_MORE 的提现费率单位都是百分比，其他的提现费率单位都是固定金额
            return 'PERCENT';
        }
        return 'FIXED';
    }

    private function getBankCardWithdrawRate(string $transType, Money $withdrawFee): string
    {
        if (in_array($transType, [
            self::PARAMS_TRANS_TYPE_MAP['yl_code_more'],
            self::PARAMS_TRANS_TYPE_MAP['yl_jsapi_more'],
            self::PARAMS_TRANS_TYPE_MAP['cloud_quick_pass'],
        ])) {
            // YL_CODE_MORE 和 YL_JSAPI_MORE 的提现费率使用扫码的提现费率，其他刷卡使用贷记卡的提现费率
            return $this->getScanWithdrawRate($transType);
        }
        $withdrawRate = $withdrawFee->toYuan();
        $scale = 'PERCENT' === $this->getBankCardWithdrawRateUnit($transType) ? 2 : 0;
        return Rate::valueOfPercentage($withdrawRate)->toPercentage($scale);
    }

    private function getChannelCodeByPolicyName(string $policyName): ?string
    {
        foreach ($this->config['channel_list'] as $channelCode => $channelConfig) {
            if (in_array($policyName, $channelConfig['policy_list'])) {
                return $channelCode;
            }
        }
        return null;
    }
}
