<?php declare(strict_types=1);

namespace shali\phpmate\crypto;

class KeyUtil
{
    /**
     * 将 base 64 编码的字符串私钥转成标准格式的私钥
     * @param string $base64PrivateKey
     * @return string
     */
    public static function toPrivateKeyValueOfBase64Str(string $base64PrivateKey): string
    {
        return sprintf("-----BEGIN RSA PRIVATE KEY-----\n%s-----END RSA PRIVATE KEY-----", chunk_split($base64PrivateKey, 64, "\n"));
    }

    /**
     * 将 base 64 编码的字符串公钥转成标准格式的公钥
     * @param string $base64PublicKey
     * @return string
     */
    public static function toPublicKeyValueOfBase64Str(string $base64PublicKey): string
    {
        return sprintf("-----BEGIN PUBLIC KEY-----\n%s-----END PUBLIC KEY-----", chunk_split($base64PublicKey, 64, "\n"));
    }
}
