<?php declare(strict_types=1);

namespace think\pos;

use BadMethodCallException;
use think\pos\dto\request\MerchantRequestDto;
use think\pos\dto\request\PosRequestDto;
use think\pos\dto\request\SimRequestDto;
use think\pos\dto\response\PosInfoResponse;
use think\pos\dto\response\PosProviderResponse;

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
    ];

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

    /**
     * 取消会员
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     */
    function cancelVip(PosRequestDto $dto): PosProviderResponse
    {
        throw new BadMethodCallException(sprintf('服务商[%s]暂未接入取消会员功能', static::providerName()));
    }

    /**
     * 初始化pos配置
     * 例如设置是否购买会员，交易费率，押金啥的
     * @param PosRequestDto $dto
     * @return PosProviderResponse
     */
    function initPosConfig(PosRequestDto $dto): PosProviderResponse
    {
        throw new BadMethodCallException(sprintf('服务商[%s]暂未接入初始化pos配置功能', static::providerName()));
    }

    /**
     * 获取 pos 的信息
     */
    function getPosInfo(PosRequestDto $dto): PosInfoResponse
    {
        throw new BadMethodCallException(sprintf('服务商[%s]暂未接入获取pos信息功能', static::providerName()));
    }

    /**
     * 设置 pos 的押金
     */
    function setPosDeposit(PosRequestDto $dto): PosProviderResponse
    {
        throw new BadMethodCallException(sprintf('服务商[%s]暂未接入设置押金功能', static::providerName()));
    }

    /**
     * 设置费率：
     * 提现手续费
     * 刷卡费率
     * 交易费率（贷记卡，支付宝，微信）
     * 扫码费率
     */
    function setPosRate(PosRequestDto $dto): PosProviderResponse
    {
        throw new BadMethodCallException(sprintf('服务商[%s]暂未接入设置费率功能', static::providerName()));
    }

    /**
     * 设置流量卡费用
     */
    function setSimFee(SimRequestDto $dto): PosProviderResponse
    {
        throw new BadMethodCallException(sprintf('服务商[%s]暂未接入设置流量卡费用功能', static::providerName()));
    }

    /**
     * 设置商户费率
     */
    function setMerchantRate(MerchantRequestDto $dto): PosProviderResponse
    {
        throw new BadMethodCallException(sprintf('服务商[%s]暂未接入设置商户费率功能', static::providerName()));
    }
}
