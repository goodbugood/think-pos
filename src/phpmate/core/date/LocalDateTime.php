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

    public function getDate()
    {
        return $this->format('Y-m-d');
    }

    public function getTime()
    {
        return $this->format('H:i:s');
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

    // 禁止 new
    private function __construct(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }
}
