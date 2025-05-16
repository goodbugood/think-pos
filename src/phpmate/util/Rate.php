<?php declare(strict_types=1);

namespace shali\phpmate\util;

/**
 * 费率，封装费率计算逻辑
 * 关于费率有的人喜欢用小数，有的人喜欢用百分数，很头疼
 */
final class Rate
{
    private const MAX_SCALE = 6;

    /**
     * 费率值，单位小数
     * @var string
     */
    private $value;

    /**
     * 小数构费率
     */
    public static function valueOfDecimal(string $decimal): Rate
    {
        $rate = new self();
        $rate->value = $decimal;
        return $rate;
    }

    /**
     * 百分数构建费率
     */
    public static function valueOfPercentage(string $percentage): Rate
    {
        return self::valueOfDecimal(bcdiv($percentage, '100', self::MAX_SCALE));
    }

    /**
     * 获取小数费率
     * @param int $scale 默认保留 4 位小数，万分之几
     * @return string
     */
    public function toDecimal(int $scale = 4): string
    {
        return bcmul($this->value, '1', $scale);
    }

    /**
     * 获取百分数费率
     * @param int $scale 默认保留 2 位小数，如 0.05%，则传 2
     * @return string
     */
    public function toPercentage(int $scale = 2): string
    {
        return bcmul($this->value, '100', $scale);
    }

    // 禁止 new
    private function __construct()
    {
    }
}
