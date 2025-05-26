<?php declare(strict_types=1);

namespace shali\phpmate\core\date;

/**
 * @see 参考 java.time.LocalDateTime
 * @author shali
 */
final class LocalDateTime
{
    private $timestamp;

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public static function valueOfString(string $dateTimeString): self
    {
        return new self(strtotime($dateTimeString));
    }

    /**
     * @param string $delimiter 日期分隔符
     */
    public function getDate(string $delimiter = '-')
    {
        return $this->format(sprintf('Y%sm%sd', $delimiter, $delimiter));
    }

    public function getTime(string $delimiter = ':')
    {
        return $this->format(sprintf('H%si%ss', $delimiter, $delimiter));
    }

    public function format(string $format = 'Y-m-d H:i:s')
    {
        return date($format, $this->timestamp);
    }

    public function toString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function __toString()
    {
        return $this->toString();
    }

    public static function now(): self
    {
        return new self(time());
    }

    /**
     * 偏移指定天数，正数向未来偏移，负数向历史偏移
     */
    public function offsetDays(int $days): self
    {
        return LocalDateTime::valueOfString(date('Y-m-d H:i:s', strtotime("$days day", $this->getTimestamp())));
    }

    /**
     * 两个日期相差的天数
     */
    public function betweenDays(self $localDateTime): int
    {
        return intval(($localDateTime->getTimestamp() - $this->getTimestamp()) / 86400);
    }

    /**
     * 昨天这个时间
     */
    public function yesterday(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-d H:i:s', strtotime('-1 day', $this->getTimestamp())));
    }

    /**
     * 明天这个时间
     */
    public function tomorrow(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-d H:i:s', strtotime('+1 day', $this->getTimestamp())));
    }

    /**
     * 上周这个时间
     */
    public function lastWeek(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-d H:i:s', strtotime('-1 week', $this->getTimestamp())));
    }

    /**
     * 下周这个时间
     * @return self
     */
    public function nextWeek(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-d H:i:s', strtotime('+1 week', $this->getTimestamp())));
    }

    /**
     * 是否需要对其月的结束
     * 不需要跨月的场景如下：分表计算，金融报表，账单周期，合同到期时间
     * 需要跨月的场景：会员到期时间，必须给足够的天数
     * 如：true 平年 03-30 上个月是 02-28，false 03-30 上个月是 03-02
     * @param bool $alignToMonthEnd 是否对齐到当月的结束，默认对齐到当月的结束
     * @return self
     */
    public function lastMonth(bool $alignToMonthEnd = true): self
    {
        if ($alignToMonthEnd) {
            $lastMonthEnd = $this->beginOfMonth()->yesterday();
            if ($this->dayOfMonth() > $lastMonthEnd->getDaysOfMonth()) {
                return $lastMonthEnd;
            }
        }
        return LocalDateTime::valueOfString(date('Y-m-d H:i:s', strtotime('-1 month', $this->getTimestamp())));
    }

    public function nextMonth(bool $alignToMonthEnd = true): self
    {
        if ($alignToMonthEnd) {
            $nextMonthEnd = $this->endOfMonth()->tomorrow()->endOfMonth();
            if ($this->dayOfMonth() > $nextMonthEnd->getDaysOfMonth()) {
                return $nextMonthEnd;
            }
        }
        return LocalDateTime::valueOfString(date('Y-m-d H:i:s', strtotime('+1 month', $this->getTimestamp())));
    }

    /**
     * 当天开始的时间
     */
    public function beginOfDay(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-d 00:00:00', $this->getTimestamp()));
    }

    public function endOfDay(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-d 23:59:59', $this->getTimestamp()));
    }

    public function beginOfMonth(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-01 00:00:00', $this->getTimestamp()));
    }

    public function endOfMonth(): self
    {
        return LocalDateTime::valueOfString(date('Y-m-t 23:59:59', $this->getTimestamp()));
    }

    public function beginOfYear(): self
    {
        return LocalDateTime::valueOfString(date('Y-01-01 00:00:00', $this->getTimestamp()));
    }

    public function endOfYear(): self
    {
        return LocalDateTime::valueOfString(date('Y-12-31 23:59:59', $this->getTimestamp()));
    }

    /**
     * 获取本月是第几天
     */
    public function dayOfMonth(): int
    {
        return intval(date('d', $this->getTimestamp()));
    }

    /**
     * 计算本月有几天
     */
    public function getDaysOfMonth(): int
    {
        return intval(date('t', $this->getTimestamp()));
    }

    /**
     * 当前是否是本月最后一天
     */
    public function isLastDayOfMonth(): bool
    {
        return $this->dayOfMonth() === $this->getDaysOfMonth();
    }

    /**
     * 当前时间所属年份是否是闰年
     * 闰年：能被4整除，但不能被100整除，或者能被400整除
     */
    public function isLeapYear(): bool
    {
        return date('L', $this->getTimestamp()) === '1';
    }

    /**
     * 比较两个时间大小
     */
    public function compareTo(self $localDateTime): int
    {
        return $this->getTimestamp() <=> $localDateTime->getTimestamp();
    }

    /**
     * 判断当前时间是否已经过期
     */
    public function isExpired(): bool
    {
        return $this->getTimestamp() < time();
    }

    // 禁止 new
    private function __construct(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }
}
