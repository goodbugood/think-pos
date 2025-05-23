<?php declare(strict_types=1);

namespace shali\phpmate\util;

use ArrayAccess;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Optional 参考 Guava Optional
 * @see https://github.com/laravel/framework/blob/12.x/src/Illuminate/Support/Optional.php
 * @see java.util.Optional JDK 17
 * @template T
 */
final class Optional implements ArrayAccess
{
    /**
     * @var T
     */
    private $value;

    /**
     * @var self<null>
     */
    private static $empty = null;

    /**
     * @return self<null>
     */
    public static function empty(): self
    {
        if (is_null(self::$empty)) {
            self::$empty = new self();
        }
        return self::$empty;
    }

    /**
     * @param T $value
     * @return self<T>
     */
    public static function of($value): self
    {
        $v = Variables::requireNonNull($value);
        $optional = new self();
        $optional->value = $v;
        return $optional;
    }

    /**
     * @param T|null $value
     * @return self<T|null>
     */
    public static function ofNullable($value): self
    {
        return is_null($value) ? self::empty() : self::of($value);
    }

    /**
     * @return T
     */
    public function get()
    {
        if ($this->isNoPresent()) {
            throw new RuntimeException('No value present');
        }
        return $this->value;
    }

    public function isPresent(): bool
    {
        return !is_null($this->value);
    }

    /**
     * 如果没有值，则返回 true
     * @return bool
     */
    private function isNoPresent(): bool
    {
        return !$this->isPresent();
    }

    /**
     * @param callable(T): void $consumer
     * @return void
     */
    public function ifPresentConsumer(callable $consumer): void
    {
        if ($this->isPresent()) {
            $consumer($this->value);
        }
    }

    /**
     * @param callable(T): void $consumer
     * @param callable(mixed...): void $action
     * @return void
     */
    public function ifPresentOrElse(callable $consumer, callable $action): void
    {
        if ($this->isPresent()) {
            $consumer($this->value);
        } else {
            $action();
        }
    }

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): Optional
    {
        if ($this->isNoPresent()) {
            return $this;
        }
        return $predicate($this->value) ? $this : self::empty();
    }

    /**
     * @template R
     * @param callable(T): R $mapper
     * @return self<R>
     */
    public function map(callable $mapper): Optional
    {
        return $this->isNoPresent() ? self::empty() : self::ofNullable($mapper($this->value));
    }

    /**
     * @template R
     * @param callable(T): self<R> $mapper
     * @return self<R|null>
     */
    public function flatMap(callable $mapper): Optional
    {
        if ($this->isNoPresent()) {
            return self::empty();
        }
        $result = $mapper($this->value);
        return $result instanceof Optional ? $result : self::ofNullable($result);
    }

    /**
     * @template R
     * @param callable(): self<T> $supplier
     * @return self<T|R>
     */
    public function or(callable $supplier): Optional
    {
        return $this->isPresent() ? $this : self::ofNullable(Variables::requireNonNull($supplier()));
    }

    /**
     * @param mixed $other
     * @return T|mixed
     */
    public function orElse($other)
    {
        return $this->value ?? $other;
    }

    /**
     * @template R
     * @param callable(): R $supplier
     * @return T|R
     */
    public function orElseGet(callable $supplier)
    {
        return $this->value ?? $supplier();
    }

    /**
     * @return T
     */
    public function orElseThrow(callable $exceptionSupplier)
    {
        $e = $exceptionSupplier();
        if (!$e instanceof Exception) {
            throw new InvalidArgumentException('exceptionSupplier is not a Throwable');
        }
        if ($this->isNoPresent()) {
            throw $e;
        }
        return $this->value;
    }

    /**
     * @param T $obj
     * @return bool
     */
    public function equals($obj): bool
    {
        if ($obj instanceof self) {
            return $this->value === $obj->value;
        }
        return false;
    }

    public function toString()
    {
        if (!$this->isPresent()) {
            return 'null';
        } elseif (is_scalar($this->value)) {
            return strval($this->value);
        } elseif (is_array($this->value)) {
            return json_encode($this->value, JSON_UNESCAPED_UNICODE);
        } elseif (is_object($this->value)) {
            return json_encode($this->value, JSON_UNESCAPED_UNICODE);
        } elseif (is_resource($this->value)) {
            return '{' . get_resource_type($this->value) . '}';
        } elseif (is_bool($this->value)) {
            return $this->value ? 'true' : 'false';
        } elseif (is_callable($this->value)) {
            return '{' . gettype($this->value) . '}';
        }

        return '{' . gettype($this->value) . '}';
    }

    // 魔术方法

    public function __toString(): string
    {
        return $this->toString();
    }

    //region Option 当拥有属性的对象访问

    /**
     * @param string $method
     * @param mixed $params
     * @return self<T|mixed>
     */
    public function __call(string $method, $params): self
    {
        if (is_object($this->value) && is_callable([$this->value, $method])) {
            return self::ofNullable($this->value->$method(...$params));
        } elseif (is_array($this->value) && array_key_exists($method, $this->value) && is_callable($this->value[$method])) {
            return self::ofNullable($this->value[$method](...$params));
        }
        return self::empty();
    }

    /**
     * 获取对象属性
     * @param string $attribute
     * @return self<T>
     */
    public function __get(string $attribute): self
    {
        if (is_object($this->value) && property_exists($this->value, $attribute)) {
            return self::ofNullable($this->value->$attribute);
        } elseif (is_array($this->value) && array_key_exists($attribute, $this->value)) {
            return self::ofNullable($this->value[$attribute]);
        }
        return self::empty();
    }

    public function __set($attribute, $value): void
    {
        if (is_object($this->value)) {
            $this->value->$attribute = $value;
        } elseif (is_array($this->value)) {
            $this->value[$attribute] = $value;
        }
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function __isset(string $attribute): bool
    {
        if (is_object($this->value)) {
            return isset($this->value->$attribute);
        } elseif (is_array($this->value)) {
            return isset($this->value[$attribute]);
        }
        return false;
    }

    public function __unset(string $attribute)
    {
        if (is_object($this->value) && property_exists($this->value, $attribute)) {
            unset($this->value->$attribute);
        } elseif (is_array($this->value) && array_key_exists($attribute, $this->value)) {
            unset($this->value[$attribute]);
        }
    }
    //endregion

    //region Optional 当数组使用
    public function offsetExists($offset): bool
    {
        if (is_object($this->value)) {
            return property_exists($this->value, $offset);
        } elseif (is_array($this->value)) {
            return array_key_exists($offset, $this->value);
        }
        return false;
    }

    /**
     * @param $offset
     * @return self<T>
     */
    public function offsetGet($offset): self
    {
        if (is_object($this->value) && property_exists($this->value, $offset)) {
            return self::ofNullable($this->value->$offset);
        } elseif (is_array($this->value) && array_key_exists($offset, $this->value)) {
            return self::ofNullable($this->value[$offset]);
        }
        return self::empty();
    }

    public function offsetSet($offset, $value): void
    {
        if (is_object($this->value)) {
            $this->value->$offset = $value;
        } elseif (is_array($this->value)) {
            $this->value[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        if (is_object($this->value) && property_exists($this->value, $offset)) {
            unset($this->value->$offset);
        } elseif (is_array($this->value) && array_key_exists($offset, $this->value)) {
            unset($this->value[$offset]);
        }
    }
    //endregion

    // forbid new
    private function __construct()
    {
        $this->value = null;
    }
}
