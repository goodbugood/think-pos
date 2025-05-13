<?php declare(strict_types=1);

namespace shali\phpmate\crypto;

use shali\phpmate\PhpMateException;

/**
 * 签名工具类
 */
class SignUtil
{
    /**
     * 非对称签名
     * @throws PhpMateException
     */
    public static function signBySHA256withRSAToBase64(string $privateKey, string $params): string
    {
        if (openssl_sign($params, $sign, $privateKey, OPENSSL_ALGO_SHA256)) {
            return base64_encode($sign);
        }

        throw new PhpMateException('SHA256withRSA 签名失败：' . openssl_error_string());
    }

    /**
     * 验签
     * @param string $publicKey 标准格式的公钥
     * @param string $sign base64 编码的签名
     * @param string $params
     * @return bool
     * @throws PhpMateException
     */
    public static function verifySignBySHA256withRSAToBase64(string $publicKey, string $sign, string $params): bool
    {
        $res = openssl_verify($params, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
        if (false === $res) {
            throw new PhpMateException('SHA256withRSA 验签失败：' . openssl_error_string());
        }

        return 1 === $res;
    }
}
