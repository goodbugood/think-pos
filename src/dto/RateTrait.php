<?php declare(strict_types=1);

namespace think\pos\dto;

use shali\phpmate\util\Money;
use shali\phpmate\util\Rate;

trait RateTrait
{
    /**
     * @var Rate|null 贷记卡刷卡交易费率
     */
    private $creditRate;

    /**
     * @var Rate|null 借记卡刷卡交易费率
     */
    private $debitCardRate;

    /**
     * @var Money|null 借记卡封顶值
     */
    private $debitCardCappingValue;

    /**
     * @var Rate|null 微信费率
     */
    private $wechatRate;

    /**
     * @var Rate|null 支付宝费率
     */
    private $alipayRate;

    public function getCreditRate(): ?Rate
    {
        return $this->creditRate;
    }

    public function setCreditRate(?Rate $creditRate): void
    {
        $this->creditRate = $creditRate;
    }

    public function getDebitCardRate(): ?Rate
    {
        return $this->debitCardRate;
    }

    public function setDebitCardRate(?Rate $debitCardRate): void
    {
        $this->debitCardRate = $debitCardRate;
    }

    public function getDebitCardCappingValue(): ?Money
    {
        return $this->debitCardCappingValue;
    }

    public function setDebitCardCappingValue(?Money $debitCardCappingValue): void
    {
        $this->debitCardCappingValue = $debitCardCappingValue;
    }

    public function getWechatRate(): ?Rate
    {
        return $this->wechatRate;
    }

    public function setWechatRate(?Rate $wechatRate): void
    {
        $this->wechatRate = $wechatRate;
    }

    public function getAlipayRate(): ?Rate
    {
        return $this->alipayRate;
    }

    public function setAlipayRate(?Rate $alipayRate): void
    {
        $this->alipayRate = $alipayRate;
    }
}
