<?php

namespace think\pos\extend;

class MD5withRSAUtils
{
    /**
     * 签名算法，MD5WithRSA
     */
    const SIGNATURE_ALGORITHM = OPENSSL_ALGO_MD5;

    /**
     * RSA最大加密明文大小
     */
    const MAX_ENCRYPT_BLOCK = 117;

    /**
     * RSA最大解密密文大小
     */
    const MAX_DECRYPT_BLOCK = 128;

    /**
     * 生成签名
     * @param $data
     * @param $privateKey
     * @return mixed|string
     */
    public static function getSign($data, $privateKey)
    {
        if (openssl_sign($data, $sign, $privateKey, self::SIGNATURE_ALGORITHM)) {
            return base64_encode($sign);
        }
        return '';
    }

    /**
     * 校验签名
     * @param $publicKey
     * @param $sign
     * @param $data
     * @return bool
     */
    public static function checkSign($publicKey, $sign, $data)
    {
        return (1 == openssl_verify($data, self::urlSafeBase64decode($sign), $publicKey, self::SIGNATURE_ALGORITHM));
    }

    /**
     * 格式化公钥
     * @param $publicKey
     * @return string
     */
    public static function getPublicKey($publicKey)
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true)
            . "\n-----END PUBLIC KEY-----";

        return $publicKey;
    }

    /**
     * 格式化私钥
     * @param $privateKey
     * @return string
     */
    public static function getPrivateKey($privateKey)
    {
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        return $privateKey;
    }


    /**
     * 对参数进行加密
     * @param $data
     * @param $publicKey
     * @return string
     */
    public static function encrypt($data, $publicKey)
    {
        $_rawData = urldecode($data);

        $_encryptedList = array();
        $_step = self::MAX_ENCRYPT_BLOCK;

        for ($_i = 0, $_len = strlen($_rawData); $_i < $_len; $_i += $_step) {
            $_data = substr($_rawData, $_i, $_step);
            $_encrypted = '';

            openssl_public_encrypt($_data, $_encrypted, $publicKey);
            $_encryptedList [] = ($_encrypted);
        }
        $_data = base64_encode(join('', $_encryptedList));
        return $_data;
    }

    /**
     * 对参数进行解密
     * @param $encryptedData
     * @param $privateKey
     * @return string
     */
    public static function decrypt($encryptedData, $privateKey)
    {
        $_encryptedData = base64_decode($encryptedData);

        $_decryptedList = array();
        $_step = self::MAX_DECRYPT_BLOCK;
        if (strlen($privateKey) > 1000) {
            $_step = 256;
        }
        for ($_i = 0, $_len = strlen($_encryptedData); $_i < $_len; $_i += $_step) {
            $_data = substr($_encryptedData, $_i, $_step);
            $_decrypted = '';
            openssl_private_decrypt($_data, $_decrypted, $privateKey);
            $_decryptedList [] = $_decrypted;
        }

        return join('', $_decryptedList);
    }

    /**
     * url base64编码
     * @param $string
     * @return mixed|string
     */
    public static function urlSafeBase64encode($string)
    {
        $data = str_replace(array('+', '/'), array('-', '_'), base64_encode($string));
        return $data;
    }

    /**
     * url base64解码
     * @param $string
     * @return bool|string
     */
    public static function urlSafeBase64decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}