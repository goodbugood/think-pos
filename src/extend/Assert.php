<?php declare(strict_types=1);

namespace think\pos\extend;

use shali\phpmate\core\util\StrUtil;
use think\pos\exception\ProviderGatewayException;

/**
 * think-pos 断言扩展类
 */
final class Assert
{
    // 禁止 new
    private function __construct()
    {
    }

    /**
     * @throws ProviderGatewayException
     */
    public static function isNull($value, string $message = ''): void
    {
        if (!is_null($value)) {
            throw new ProviderGatewayException($message);
        }
    }

    /**
     * @throws ProviderGatewayException
     */
    public static function notNull($value, string $message = ''): void
    {
        if (is_null($value)) {
            throw new ProviderGatewayException($message);
        }
    }

    /**
     * @throws ProviderGatewayException
     */
    public static function notEmpty($value, string $message = ''): void
    {
        if (empty($value)) {
            throw new ProviderGatewayException($message);
        }
    }

    /**
     * 断言是非空白字符串，null，'0' 不算空白字符
     * @throws ProviderGatewayException
     */
    public static function notBlank($value, string $message = ''): void
    {
        if (is_string($value) && trim($value) === StrUtil::EMPTY) {
            throw new ProviderGatewayException($message);
        }
    }

    /**
     * @throws ProviderGatewayException
     */
    public static function keyExists(string $key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new ProviderGatewayException($message);
        }
    }

    /**
     * @throws ProviderGatewayException
     */
    public static function inArray($needle, array $haystack, string $message = ''): void
    {
        if (!in_array($needle, $haystack)) {
            throw new ProviderGatewayException($message);
        }
    }
}
