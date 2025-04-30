<?php declare(strict_types=1);

namespace think\pos;

use InvalidArgumentException;

final class PosStrategyFactory
{
    public static function create(string $providerCode): PosStrategy
    {
        if (empty($providerCode)) {
            throw new InvalidArgumentException('构造 pos 服务商策略类，服务商 code 不能为空');
        }
        $providers = config('pos.providers');
        $provider = $providers[$providerCode] ?? null;
        if (is_null($provider)) {
            throw new InvalidArgumentException(sprintf('品牌 %s 未配置 pos 策略', $providerCode));
        }
        $class = $provider['class'];
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('pos 策略类 %s 不存在', $class));
        }
        return new $class($provider['config']);
    }

    // 禁止 new
    private function __construct()
    {
    }
}
