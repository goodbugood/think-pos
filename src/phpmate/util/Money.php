<?php declare(strict_types=1);

namespace shali\phpmate\util;

final class Money
{
    /**
     * @var string 金额，单位分，元角分的分
     */
    private $value;

    /**
     * @var int 内部最大精度
     */
    private const MAX_SCALE = 6;

    /**
     * @param string $fenMoney 金额，单位分
     * @return Money
     */
    public static function valueFen(string $fenMoney): Money
    {
        $money = new self();
        $money->value = $fenMoney;
        return $money;
    }

    /**
     * @param string $yuanMoney 金额，单位元
     * @return Money
     */
    public static function valueYuan(string $yuanMoney): Money
    {
        return self::valueFen(bcmul($yuanMoney, '100', self::MAX_SCALE));
    }

    /**
     * @param int $scale 默认保留 0 位小数
     * @return string
     */
    public function toFen(int $scale = 0): string
    {
        return bcmul($this->value, '1', $scale);
    }

    /**
     * @param int $scale 默认保留 2 位小数
     * @return string|null 出错返回 null
     */
    public function toYuan(int $scale = 2): ?string
    {
        return bcdiv($this->value, '100', $scale);
    }

    // 禁止 new
    private function __construct()
    {
    }
}
