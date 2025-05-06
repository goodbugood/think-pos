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
    public static function signBySHA256WithRsaToBase64(string $privateKey, string $params): string
    {
        if (openssl_sign($params, $sign, $privateKey, OPENSSL_ALGO_SHA256)) {
            return base64_encode($sign);
        }

        throw new PhpMateException('SHA256WithRSA 签名失败：' . openssl_error_string());
    }

    /**
     * 验签
     * @param string $publicKey 标准格式的公钥
     * @param string $sign base64 编码的签名
     * @param string $params
     * @return bool
     * @throws PhpMateException
     */
    public static function verifySignBySHA256WithRsaToBase64(string $publicKey, string $sign, string $params): bool
    {
        $res = openssl_verify($params, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
        if (false === $res) {
            throw new PhpMateException('SHA256WithRSA 验签失败：' . openssl_error_string());
        }

        return 1 === $res;
    }

    /**
     * 将 base 64 编码的字符串私钥转成标准格式的私钥
     * @param string $base64PrivateKey
     * @return string
     */
    public static function toPrivateKeyByBase64Key(string $base64PrivateKey): string
    {
        return sprintf("-----BEGIN RSA PRIVATE KEY-----\n%s\n-----END RSA PRIVATE KEY-----", wordwrap($base64PrivateKey, 64, "\n", true));
    }

    public static function toPublicKeyByBase64(string $base64PublicKey): string
    {
        return sprintf("-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----", wordwrap($base64PublicKey, 64, "\n", true));
    }
}
