<?php declare(strict_types=1);

namespace think\pos;

use think\pos\dto\request\callback\MerchantRateSetCallbackRequest;
use think\pos\dto\request\callback\MerchantRegisterCallbackRequest;
use think\pos\dto\request\callback\PosActivateCallbackRequest;
use think\pos\dto\request\callback\PosBindCallbackRequest;
use think\pos\dto\request\callback\PosTransCallbackRequest;
use think\pos\dto\request\CallbackRequest;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosDepositRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosDepositResponse;
use think\pos\dto\response\PosInfoResponse;
use think\pos\dto\response\PosProviderResponse;
use think\pos\exception\UnsupportedBusinessException;

/**
 * pos 服务商策略接口
 */
abstract class PosStrategy
{
    /**
     * @var array 配置信息
     */
    protected $config = [
        // 测试模式默认关闭
        'test' => false,
        'gateway' => null,
        // 测试网关地址
        'testGateway' => null,
        // pos 服务商给分配的机构号/商户号
        'agentId' => null,
        // 签名方法
        'signMethod' => null,
        // 私钥签名
        'privateKey' => null,
        // 公钥验签
        'publicKey' => null,
    ];

    /**
     * @var array 拼装给 pos 服务商的请求参数
     */
    protected $rawRequest = [
        'url' => null,
        // 请求参数
        'params' => null,
    ];

    /**
     * @var array pos 服务商返回的响应数据
     */
    protected $rawResponse = [
        // http status code
        'statusCode' => null,
        // http status message
        'message' => null,
        // http body
        'body' => null,
        // 解密后的响应数据
        'decryptedBody' => null,
    ];

    /**
     * 回调成功返回内容
     * 此处私有属性，就是强迫子类重写成功返回内容
     */
    private const CALLBACK_ACK_CONTENT = 'OK';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 当前对接的 pos 服务商是否处于测试模式
     * @return bool
     */
    public function isTestMode(): bool
    {
        return true === $this->config['test'];
    }

    /**
     * 对接 pos 服务商必须指明 pos 服务商名称
     * @return string
     */
    public abstract static function providerName(): string;

    /**
     * 获取埋点的请求响应参数
     * @param string $bizName 业务名称
     * @return string pos 服务商请求日志
     */
    public final function getLog(string $bizName = ''): string
    {
        return sprintf('pos 服务商[%s][%s]请求参数：%s，响应数据：%s', static::providerName(), $bizName, json_encode($this->rawRequest, JSON_UNESCAPED_UNICODE), json_encode($this->rawResponse, JSON_UNESCAPED_UNICODE));
    }

    //<editor-fold desc="pos相关接口和通知">

    /**
     * pos 平台回调，用来通知代理平台商户绑定 pos 成功，并激活成功
     * pos 激活成功发生在绑定之后
     * @throws UnsupportedBusinessException
     * @deprecated 这个很少用，因为 pos 平台有自己的激活标准，我们也有自己的激活标准，所以这个回调接口基本用不到
     */
    function handleCallbackOfPosActive(string $content): PosActivateCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入pos激活回调功能', static::providerName()));
    }

    /**
     * 取消会员
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     * @throws UnsupportedBusinessException
     */
    function cancelVip(PosRequestDto $dto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入取消会员功能', static::providerName()));
    }

    /**
     * 初始化pos配置
     * 例如设置是否购买会员，交易费率，押金啥的
     * 代理的 pos 入库后，需要代理进行 pos 的初始化，避免 pos 划拨给用户后执行 pos 平台的默认费率
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     * @throws UnsupportedBusinessException
     */
    function initPosConfig(PosRequestDto $dto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入初始化pos配置功能', static::providerName()));
    }

    /**
     * 获取 pos 的信息
     * 适用于有些平台，一个接口能够返回服务费，通讯费，费率
     * @throws UnsupportedBusinessException
     */
    function getPosInfo(PosRequestDto $dto): PosInfoResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入获取pos信息功能', static::providerName()));
    }

    /**
     * 获取 pos 押金列表
     * 鉴于大部分代理接入 pos 平台时，获取押金列表仅仅参考，很少入库的
     * @throws UnsupportedBusinessException
     */
    function getPosDeposit(PosDepositRequestDto $dto): PosDepositResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入获取可用押金功能', static::providerName()));
    }

    /**
     * 设置 pos 的押金
     * @throws UnsupportedBusinessException
     */
    function setPosDeposit(PosRequestDto $dto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入设置押金功能', static::providerName()));
    }

    /**
     * 设置费率：
     * 提现手续费
     * 刷卡费率
     * 交易费率（贷记卡，支付宝，微信）
     * 扫码费率
     * @throws UnsupportedBusinessException
     */
    function setPosRate(PosRequestDto $dto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入设置费率功能', static::providerName()));
    }

    /**
     * 设置 pos 流量卡费用
     * @throws UnsupportedBusinessException
     */
    function setSimFee(SimRequestDto $dto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入设置流量卡费用功能', static::providerName()));
    }
    //</editor-fold>

    //<editor-fold desc="商户pos公共业务">
    /**
     * 商户绑定终端
     * @throws UnsupportedBusinessException
     */
    function bindPos(MerchantRequestDto $merchantRequestDto, PosRequestDto $posRequestDto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入商户绑定终端功能', static::providerName()));
    }

    /**
     * 商户解绑终端
     * @throws UnsupportedBusinessException
     */
    function unbindPos(MerchantRequestDto $merchantRequestDto, PosRequestDto $posRequestDto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入商户解绑终端功能', static::providerName()));
    }

    /**
     * pos 平台回调，用来通知代理平台商户绑定 pos 成功
     * @param string $content 回调内容
     * @return mixed
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfPosBind(string $content): PosBindCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入pos绑定回调功能', static::providerName()));
    }

    /**
     * pos 平台回调，统一处理
     * @param string $content
     * @return CallbackRequest 由于 php7.3 不支持返回协变返回类型，所以没法返回 CallbackRequest
     * @throws UnsupportedBusinessException
     */
    function handleCallback(string $content)
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入pos回调入口处理功能', static::providerName()));
    }

    /**
     * pos 平台回调，用来通知代理平台商户解绑 pos 成功
     * @param string $content
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfPosUnbind(string $content): PosBindCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入pos解绑回调功能', static::providerName()));
    }
    //</editor-fold>

    //<editor-fold desc="商户相关接口和通知">

    /**
     * 设置商户费率
     * @throws UnsupportedBusinessException
     */
    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入设置商户费率功能', static::providerName()));
    }

    /**
     * @throws UnsupportedBusinessException
     */
    function setMerchantSimFee(SimRequestDto $dto): PosProviderResponse
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入设置商户流量卡费用功能', static::providerName()));
    }

    /**
     * pos 平台回调，用来通知代理平台商户注册成功的商户信息
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfMerchantRegister(string $content): MerchantRegisterCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入商户注册回调功能', static::providerName()));
    }

    /**
     * pos 平台回调代理平台商户费率设置成功
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfMerchantRateSet(string $content): MerchantRateSetCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入商户费率设置回调功能', static::providerName()));
    }
    //</editor-fold>

    //<editor-fold desc="交易通知处理">

    /**
     * 混合交易通知回调，例如，普通，流量卡，押金多个交易一起推下来
     * @param string $content
     * @return PosTransCallbackRequest
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfTrans(string $content): PosTransCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入交易回调功能', static::providerName()));
    }

    /**
     * 普通交易回调
     * @param string $content
     * @return PosTransCallbackRequest
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfGeneralTrans(string $content): PosTransCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入普通交易回调功能', static::providerName()));
    }

    /**
     * 流量卡交易回调
     * @param string $content
     * @return PosTransCallbackRequest
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfSimTrans(string $content): PosTransCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入流量卡交易回调功能', static::providerName()));
    }

    /**
     * 押金交易回调
     * @param string $content
     * @return PosTransCallbackRequest
     * @throws UnsupportedBusinessException
     */
    function handleCallbackOfDepositTrans(string $content): PosTransCallbackRequest
    {
        throw new UnsupportedBusinessException(sprintf('服务商[%s]暂未接入押金交易回调功能', static::providerName()));
    }
    //</editor-fold>

    /**
     * pos 平台回调 ack 内容，用来通知平台停止回调
     * @return string
     */
    function getCallbackAckContent(): string
    {
        return static::CALLBACK_ACK_CONTENT;
    }
}
