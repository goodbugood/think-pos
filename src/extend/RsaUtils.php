<?php

namespace think\pos\extend;

class RsaUtils
{

    /**
     * 签名算法，SHA256WithRSA
     */
    const SIGNATURE_ALGORITHM = OPENSSL_ALGO_SHA256;

    /**
     * RSA最大加密明文大小
     */
    const MAX_ENCRYPT_BLOCK = 117;

    /**
     * RSA最大解密密文大小
     */
    const MAX_DECRYPT_BLOCK = 128;

    /**
     * 使用公钥将数据加密
     * @param $data string 需要加密的数据
     * @param $publicKey string 公钥
     * @return string 返回加密串(base64编码)
     */
    public static function publicEncrypt($data, $publicKey)
    {
        $data = str_split($data, self::MAX_ENCRYPT_BLOCK);

        $encrypted = '';
        foreach ($data as & $chunk) {
            if (!openssl_public_encrypt($chunk, $encryptData, $publicKey)) {
                return '';
            } else {
                $encrypted .= $encryptData;
            }
        }
        return self::urlSafeBase64encode($encrypted);
    }

    /**
     * 使用私钥解密
     * @param $data string 需要解密的数据
     * @param $privateKey string 私钥
     * @return string 返回解密串
     */
    public static function privateDecrypt($data, $privateKey)
    {
        $data = str_split(self::urlSafeBase64decode($data), self::MAX_DECRYPT_BLOCK);

        $decrypted = '';
        foreach ($data as & $chunk) {
            if (!openssl_private_decrypt($chunk, $decryptData, $privateKey)) {
                return '';
            } else {
                $decrypted .= $decryptData;
            }
        }

        return $decrypted;
    }

    /**
     * 使用私钥将数据加密
     * @param $data string 需要加密的数据
     * @param $privateKey string 私钥
     * @return string 返回加密串(base64编码)
     */
    public static function privateEncrypt($data, $privateKey)
    {
        $data = str_split(json_encode($data), self::MAX_ENCRYPT_BLOCK);

        $encrypted = '';
        foreach ($data as & $chunk) {
            if (!openssl_private_encrypt($chunk, $encryptData, $privateKey)) {
                return '';
            } else {
                $encrypted .= $encryptData;
            }
        }
        return self::urlSafeBase64encode($encrypted);
    }


    /**
     * 使用公钥解密
     * @param $data string 需要解密的数据
     * @param $publicKey string 公钥
     * @return string 返回解密串
     */
    public static function publicDecrypt($data, $publicKey)
    {
        $data = str_split(self::urlSafeBase64decode($data), self::MAX_DECRYPT_BLOCK);

        $decrypted = '';
        foreach ($data as & $chunk) {
            if (!openssl_public_decrypt($chunk, $decryptData, $publicKey)) {
                return '';
            } else {
                $decrypted .= $decryptData;
            }
        }
        return $decrypted;
    }


    /**
     * 私钥加签名
     * @param string $data 被加签数据
     * @param string $privateKey 私钥
     * @return mixed|string
     */
    public static function rsaSign($data, $privateKey)
    {
        if (openssl_sign($data, $sign, $privateKey, self::SIGNATURE_ALGORITHM)) {
//            return self::urlSafeBase64encode($sign);
            return base64_encode($sign);
        }

        return '';
    }

    /**
     * 公钥验签
     * @param $data
     * @param $sign
     * @param $publicKey
     * @return bool
     */
    public static function verifySign($data, $sign, $publicKey)
    {
        return (1 == openssl_verify($data, self::urlSafeBase64decode($sign), $publicKey, self::SIGNATURE_ALGORITHM));
    }

    /**
     * url base64编码
     * @param $string
     * @return mixed|string
     */
    public static function urlSafeBase64encode($string)
    {
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($string));
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

    /* 代付签名获取
     *param $type pay代付  state代付结果 balance余额  upload上传图片
     **/
    public static function getMd5Info($data, $md5Key)
    {
        $sign = '';

        $args = array_filter($data);//过滤掉空值
        ksort($args);
        $query = '';
        foreach ($args as $k => $v) {
            if ($k == 'md5info') {
                continue;
            }
            if ($query) {
                $query .= '&' . $k . '=' . $v;
            } else {//第一个不要&
                $query = $k . '=' . $v;
            }
        }
        //dump($query);
        $sign = md5($query . $md5Key);
        //$sign = md5($query);

        return strtoupper($sign);
    }

    /**
     * 加密参数
     * @param $str
     * @param $pri_key
     * @return bool|string
     */
    public static function sha256WithRSAEncrypt($content, $privateKey)
    {


        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        $key = openssl_get_privatekey($privateKey);

        openssl_sign($content, $signature, $key, "SHA256");
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;

    }

    //验证 sha256WithRSA 签名
    public static function sha256WithRSAVerify($content, $sign, $publicKey)
    {

        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        $key = openssl_get_publickey($publicKey);
        $ok = openssl_verify($content, base64_decode($sign), $key, 'SHA256');
        openssl_free_key($key);
        return $ok;
    }

    //Ascii排序
    public static function Ascii($args)
    {
        ksort($args);
        $query = '';
        foreach ($args as $k => $v) {
            if ($query) {
                $query .= '&' . $k . '=' . urlEncode($v);
            } else {//第一个不要&
                $query = $k . '=' . urlEncode($v);
            }
        }
        return $query;
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
}