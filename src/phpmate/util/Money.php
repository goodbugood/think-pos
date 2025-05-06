<?php declare(strict_types=1);

namespace shali\phpmate\util;

/**
 * 货币工具类
 * @author shali
 * @date 2025/04/30
 */
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
     * @var int 精度
     */
    private $scale;

    /**
     * @param string $fenMoney 金额，单位分
     * @param int $scale 精度
     * @return Money
     * @deprecated 语意不清晰，废弃，请使用 Money::valueOfFen()
     * @see Money::valueOfFen()
     */
    public static function valueFen(string $fenMoney, int $scale = self::MAX_SCALE): Money
    {
        $money = new self();
        $money->value = $fenMoney;
        $money->scale = $scale;
        return $money;
    }

    /**
     * @param string $fenMoney 金额，单位分
     * @param int $scale 精度
     * @return Money
     * @since v2.3.5
     */
    public static function valueOfFen(string $fenMoney, int $scale = self::MAX_SCALE): Money
    {
        $money = new self();
        $money->value = $fenMoney;
        $money->scale = $scale;
        return $money;
    }

    /**
     * @param string $yuanMoney 金额，单位元
     * @param int $scale
     * @return Money
     * @deprecated 语意不清晰，废弃，请使用 Money::valueOfYuan()
     * @see Money::valueOfYuan()
     */
    public static function valueYuan(string $yuanMoney, int $scale = self::MAX_SCALE): Money
    {
        return self::valueFen(bcmul($yuanMoney, '100', $scale));
    }

    /**
     * @param string $yuanMoney 金额，单位元
     * @param int $scale
     * @return Money
     * @since v2.3.5
     */
    public static function valueOfYuan(string $yuanMoney, int $scale = self::MAX_SCALE): Money
    {
        return self::valueOfFen(bcmul($yuanMoney, '100', $scale));
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

    /**
     * 加法
     * @param Money $money 被加数
     * @return $this
     */
    public function add(Money $money): Money
    {
        $this->value = bcadd($this->value, $money->toFen($this->scale), $this->scale);
        return $this;
    }

    /**
     * 减法
     * @param Money $money 被减数
     * @return $this
     */
    public function sub(Money $money): Money
    {
        $this->value = bcsub($this->value, $money->toFen($this->scale), $this->scale);
        return $this;
    }

    /**
     * 乘法
     * @param string $num 被乘数
     * @return $this
     */
    public function mul(string $num): Money
    {
        $this->value = bcmul($this->value, $num, $this->scale);
        return $this;
    }

    /**
     * 除法
     * @param string $num 被除数
     * @return $this
     */
    public function div(string $num): Money
    {
        $this->value = bcdiv($this->value, $num, $this->scale);
        return $this;
    }

    // 禁止 new
    private function __construct()
    {
    }
}
