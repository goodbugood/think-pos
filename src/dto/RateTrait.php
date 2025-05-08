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
}
