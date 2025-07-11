<?php declare(strict_types=1);

namespace think\pos\dto\request;

use think\pos\dto\ExtInfoTrait;
use think\pos\dto\ProviderRequestTrait;
use think\pos\dto\RateTrait;
use think\pos\exception\MissingParameterException;

/**
 * 请求 pos 服务商接口的商户参数
 */
class MerchantRequestDto
{
    use ProviderRequestTrait;
    use RateTrait;
    use ExtInfoTrait;

    /**
     * @var string 设备序列号，pos sn
     */
    private $deviceSn = '';

    /**
     * @var string 商户号
     */
    private $merchantNo = '';

    public function getDeviceSn(): string
    {
        return $this->deviceSn;
    }

    public function setDeviceSn(string $deviceSn): void
    {
        $this->deviceSn = $deviceSn;
    }

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function setMerchantNo(string $merchantNo): void
    {
        $this->merchantNo = $merchantNo;
    }

    /**
     * @throws MissingParameterException
     */
    public function check(): void
    {
        if (empty($this->deviceSn)) {
            throw new MissingParameterException('设备序列号 deviceSn 不能为空');
        } elseif (empty($this->merchantNo)) {
            throw new MissingParameterException('商户号 merchantNo 不能为空');
        }
    }
}