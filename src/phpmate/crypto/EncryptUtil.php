<?php declare(strict_types=1);

namespace shali\phpmate\crypto;

use shali\phpmate\PhpMateException;

/**
 * 加密工具类
 */
class EncryptUtil
{
    /**
     * aes 对称加密，ECB 模式 + PKCS5Padding 填充方式
     * @param string $password 密钥，加解密密钥相同
     * @param string $data
     * @return string
     * @throws PhpMateException
     */
    public static function encryptByAES_ECB_PKCS5PaddingToBase64(string $password, string $data): string
    {
        // PKCS5Padding 实际使用 PKCS7 填充（PHP 原生支持）
        $encrypted = openssl_encrypt($data, 'AES-128-ECB', $password, OPENSSL_RAW_DATA);
        if (!$encrypted) {
            throw new PhpMateException('AES-ECB-PKCS5Padding 加密失败：' . openssl_error_string());
        }
        return base64_encode($encrypted);
    }

    /**
     * @param string $password 对称加解密的密钥
     * @param string $data 待解密数据
     * @return string
     * @throws PhpMateException
     */
    public static function decryptByAES_ECB_PKCS5PaddingToBase64(string $password, string $data): string
    {
        $decrypted = openssl_decrypt(base64_decode($data), 'AES-128-ECB', $password, OPENSSL_RAW_DATA);
        if (!$decrypted) {
            throw new PhpMateException('AES-ECB-PKCS5Padding 解密失败：' . openssl_error_string());
        }
        return $decrypted;
    }

    /**
     * rsa 非对称加密，ECB 模式 PKCS1 填充方式
     * @throws PhpMateException
     */
    public static function encryptByRSA_ECB_PKCS1PaddingToBase64(string $publicKey, string $data): string
    {
        if (!openssl_public_encrypt($data, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)) {
            throw new PhpMateException('RSA-ECB-PKCS1Padding 加密失败：' . openssl_error_string());
        }
        return base64_encode($encrypted);
    }

    /**
     * @throws PhpMateException
     */
    public static function decryptByRSA_ECB_PKCS1PaddingToBase64(string $privateKey, string $data): string
    {
        if (!openssl_private_decrypt(base64_decode($data), $decrypted, $privateKey, OPENSSL_PKCS1_PADDING)) {
            throw new PhpMateException('RSA-ECB-PKCS1Padding 解密失败：' . openssl_error_string());
        }
        return $decrypted;
    }
}
