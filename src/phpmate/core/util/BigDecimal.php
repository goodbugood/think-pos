<?php declare(strict_types=1);

namespace shali\phpmate\core\util;

use ArithmeticError;
use InvalidArgumentException;
use shali\phpmate\core\util\constant\RoundingMode;

/**
 * 参考 java.math.BigDecimal api
 * 四舍五入模式参考 \shali\phpmate\core\util\constant\RoundingMode
 */
class BigDecimal
{
    /**
     * 数值的字符串表示（不包含小数点）
     * @var string
     */
    private $value;

    /**
     * 小数点后的位数（标度）
     * @var int
     */
    private $scale;

    /**
     * 构造函数
     */
    public function __construct($value, ?int $scale = null)
    {
        if (is_string($value)) {
            $this->initFromString($value);
        } elseif (is_int($value) || is_float($value)) {
            $this->initFromNumber($value, $scale);
        } else {
            throw new InvalidArgumentException('Unsupported value type');
        }
    }

    /**
     * 从字符串初始化
     */
    private function initFromString(string $value): void
    {
        $value = trim($value);

        if (!preg_match('/^-?\d*\.?\d*$/', $value)) {
            throw new InvalidArgumentException('Invalid number format');
        }

        if (strpos($value, '.') !== false) {
            [$intPart, $decPart] = explode('.', $value);
            $this->scale = strlen($decPart);
            $this->value = $intPart . $decPart;
        } else {
            $this->scale = 0;
            $this->value = $value;
        }

        // 移除前导零，但保留至少一位数字
        $this->value = preg_replace('/^(-?)0+/', '$1', $this->value);
        if ($this->value === '' || $this->value === '-') {
            $this->value = '0';
        }
    }

    /**
     * 从数字初始化
     */
    private function initFromNumber($value, ?int $scale = null): void
    {
        if (is_float($value)) {
            $stringValue = number_format($value, 10, '.', '');
            $stringValue = rtrim($stringValue, '0');
            $stringValue = rtrim($stringValue, '.');
            $this->initFromString($stringValue);
        } else {
            $this->value = (string)$value;
            $this->scale = $scale ?? 0;
        }
    }

    /**
     * 创建 BigDecimal 实例的静态方法
     */
    public static function valueOf($value, ?int $scale = null): self
    {
        return new self($value, $scale);
    }

    /**
     * 获取标度（小数点后的位数）
     */
    public function scale(): int
    {
        return $this->scale;
    }

    /**
     * 获取精度（总位数）
     */
    public function precision(): int
    {
        $absValue = ltrim($this->value, '-');
        return strlen($absValue);
    }

    /**
     * 转换为字符串
     */
    public function toString(): string
    {
        if ($this->scale <= 0) {
            return $this->value;
        }

        $absValue = ltrim($this->value, '-');
        $isNegative = $this->value[0] === '-';

        $intPartLength = strlen($absValue) - $this->scale;

        if ($intPartLength <= 0) {
            $result = '0.' . str_repeat('0', -$intPartLength) . $absValue;
        } else {
            $intPart = substr($absValue, 0, $intPartLength);
            $decPart = substr($absValue, $intPartLength);
            $result = $intPart . '.' . $decPart;
        }

        return $isNegative ? '-' . $result : $result;
    }

    /**
     * 魔术方法，自动转换为字符串
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * 转换为浮点数
     */
    public function floatValue(): float
    {
        return (float)$this->toString();
    }

    /**
     * 获取符号
     */
    public function signum(): int
    {
        if ($this->value === '0') {
            return 0;
        }
        return $this->value[0] === '-' ? -1 : 1;
    }

    /**
     * 获取绝对值
     */
    public function abs(): self
    {
        if ($this->signum() >= 0) {
            return $this;
        }

        $result = new self('0');
        $result->value = ltrim($this->value, '-');
        $result->scale = $this->scale;
        return $result;
    }

    /**
     * 取负值
     */
    public function negate(): self
    {
        if ($this->value === '0') {
            return $this;
        }

        $result = new self('0');
        $result->scale = $this->scale;

        if ($this->value[0] === '-') {
            $result->value = substr($this->value, 1);
        } else {
            $result->value = '-' . $this->value;
        }

        return $result;
    }

    /**
     * 加法运算
     */
    public function add(BigDecimal $other): self
    {
        $thisValue = $this->toString();
        $otherValue = $other->toString();

        $result = bcadd($thisValue, $otherValue, max($this->scale, $other->scale));

        return new self($result);
    }

    /**
     * 减法运算
     */
    public function subtract(BigDecimal $other): self
    {
        $thisValue = $this->toString();
        $otherValue = $other->toString();

        $result = bcsub($thisValue, $otherValue, max($this->scale, $other->scale));

        return new self($result);
    }

    /**
     * 乘法运算
     */
    public function multiply(BigDecimal $other): self
    {
        $thisValue = $this->toString();
        $otherValue = $other->toString();

        $result = bcmul($thisValue, $otherValue, $this->scale + $other->scale);

        return new self($result);
    }

    /**
     * 除法运算
     */
    public function divide(BigDecimal $other, ?int $scale = null, string $roundingMode = RoundingMode::HALF_UP): self
    {
        if ($other->signum() === 0) {
            throw new ArithmeticError('Division by zero');
        }

        $targetScale = $scale ?? max($this->scale, $other->scale, 10);

        $thisValue = $this->toString();
        $otherValue = $other->toString();

        $result = bcdiv($thisValue, $otherValue, $targetScale);

        if ($scale !== null && $roundingMode !== RoundingMode::UNNECESSARY) {
            $bigDecimalResult = new self($result);
            return $bigDecimalResult->setScale($scale, $roundingMode);
        }

        return new self($result);
    }

    /**
     * 比较两个 BigDecimal
     * 返回 -1, 0, 1 分别表示小于、等于、大于
     */
    public function compareTo(BigDecimal $other): int
    {
        $thisValue = $this->toString();
        $otherValue = $other->toString();

        return bccomp($thisValue, $otherValue, max($this->scale, $other->scale));
    }

    /**
     * 检查两个 BigDecimal 是否相等
     */
    public function equals(BigDecimal $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * 大于
     */
    public function greaterThan(BigDecimal $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * 大于等于
     */
    public function greaterThanOrEquals(BigDecimal $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * 小于
     */
    public function lessThan(BigDecimal $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * 小于等于
     */
    public function lessThanOrEquals(BigDecimal $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * 设置标度（小数点后位数）
     */
    public function setScale(int $scale, string $roundingMode = RoundingMode::HALF_UP): self
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale cannot be negative');
        }

        if ($scale === $this->scale) {
            return $this;
        }

        $thisValue = $this->toString();

        if ($scale > $this->scale) {
            // 扩展精度，直接补零
            $result = bcadd($thisValue, '0', $scale);
        } else {
            // 缩减精度，需要舍入
            $result = $this->roundValue($thisValue, $scale, $roundingMode);
        }

        return new self($result);
    }

    /**
     * 舍入辅助方法
     */
    private function roundValue(string $value, int $scale, string $roundingMode): string
    {
        switch ($roundingMode) {
            case RoundingMode::UP:
                $result = $this->roundUp($value, $scale);
                break;
            case RoundingMode::DOWN:
                $result = $this->roundDown($value, $scale);
                break;
            case RoundingMode::CEILING:
                $result = $this->roundCeiling($value, $scale);
                break;
            case RoundingMode::FLOOR:
                $result = $this->roundFloor($value, $scale);
                break;
            case RoundingMode::HALF_UP:
                $result = bcadd($value, '0', $scale);
                break;
            case RoundingMode::HALF_DOWN:
                $result = $this->roundHalfDown($value, $scale);
                break;
            case RoundingMode::HALF_EVEN:
                $result = $this->roundHalfEven($value, $scale);
                break;
            case RoundingMode::UNNECESSARY:
                if (bccomp($value, bcadd($value, '0', $scale)) !== 0) {
                    throw new ArithmeticError('Rounding necessary');
                }
                $result = $value;
                break;
            default:
                throw new InvalidArgumentException('Invalid rounding mode');
        }

        return $result;
    }

    /**
     * 远离零方向舍入
     */
    private function roundUp(string $value, int $scale): string
    {
        $truncated = bcadd($value, '0', $scale);
        if (bccomp($value, $truncated) === 0) {
            return $truncated;
        }

        $increment = bcpow('10', '-' . $scale);
        if (bccomp($value, '0') < 0) {
            $increment = '-' . $increment;
        }

        return bcadd($truncated, $increment, $scale);
    }

    /**
     * 向零方向舍入
     */
    private function roundDown(string $value, int $scale): string
    {
        return bcadd($value, '0', $scale);
    }

    /**
     * 向正无穷方向舍入
     */
    private function roundCeiling(string $value, int $scale): string
    {
        $truncated = bcadd($value, '0', $scale);
        if (bccomp($value, $truncated) === 0 || bccomp($value, '0') < 0) {
            return $truncated;
        }

        $increment = bcpow('10', '-' . $scale);
        return bcadd($truncated, $increment, $scale);
    }

    /**
     * 向负无穷方向舍入
     */
    private function roundFloor(string $value, int $scale): string
    {
        $truncated = bcadd($value, '0', $scale);
        if (bccomp($value, $truncated) === 0 || bccomp($value, '0') > 0) {
            return $truncated;
        }

        $increment = bcpow('10', '-' . $scale);
        return bcsub($truncated, $increment, $scale);
    }

    /**
     * 五舍六入
     */
    private function roundHalfDown(string $value, int $scale): string
    {
        // 临时实现，使用标准的四舍五入逻辑
        return bcadd($value, '0', $scale);
    }

    /**
     * 银行家舍入法
     */
    private function roundHalfEven(string $value, int $scale): string
    {
        // 临时实现，使用标准的四舍五入逻辑
        return bcadd($value, '0', $scale);
    }

    /**
     * 求余运算
     */
    public function remainder(BigDecimal $other): self
    {
        if ($other->signum() === 0) {
            throw new ArithmeticError('Division by zero');
        }

        $thisValue = $this->toString();
        $otherValue = $other->toString();

        $result = bcmod($thisValue, $otherValue);

        return new self($result);
    }

    /**
     * 幂运算
     */
    public function pow(int $exponent): self
    {
        if ($exponent < 0) {
            throw new InvalidArgumentException('Negative exponent not supported');
        }

        $thisValue = $this->toString();
        $result = bcpow($thisValue, (string)$exponent, $this->scale * $exponent);

        return new self($result);
    }
}
