<?php

namespace shali\phpmate\tests\crypto;

use PHPUnit\Framework\TestCase;
use shali\phpmate\crypto\EncryptUtil;
use shali\phpmate\crypto\KeyUtil;
use shali\phpmate\PhpMateException;

class EncryptUtilTest extends TestCase
{
    /**
     * 明文
     */
    private const PLAIN_TEXT = 'hello world';

    /**
     * @var string 密码，对称加密，解密的密码
     */
    private const PASSWORD = '1234567890123456';

    private const PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAukkM725cFg/vyEIRsJvwCT+CpM+ZYkowDqS+za6vOAEHgQlwdgdiE8a3FL97ZwwRtr8BPXwgODBbH0ujOraPexsE1DpGwYovrQ0YVlUtFHEuib3QALjDiHWQ5l1o9Zi1Te9QElrtUBDMbsQKHsZHWgsSrhDrJq3bhRm8+c8F/hnUxddESX/cUWZQzPW/0QC9xhSe7oACFjlGOB71dtwoFbGisTaJGMurAPivlGNYs8Ou2r2Jyn0iZHQBN9hpsV8cRmODyohdHjf9qCY+hbmKTwBnQ0felQ5DtWXweKLPRVQBC6EOxopSinmFnGKvEyWhQO7LBGlfJ34OeCagMEkb2wIDAQAB';

    /**
     * 私钥解密，签名
     */
    private const PRIVATE_KEY = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC6SQzvblwWD+/IQhGwm/AJP4Kkz5liSjAOpL7Nrq84AQeBCXB2B2ITxrcUv3tnDBG2vwE9fCA4MFsfS6M6to97GwTUOkbBii+tDRhWVS0UcS6JvdAAuMOIdZDmXWj1mLVN71ASWu1QEMxuxAoexkdaCxKuEOsmrduFGbz5zwX+GdTF10RJf9xRZlDM9b/RAL3GFJ7ugAIWOUY4HvV23CgVsaKxNokYy6sA+K+UY1izw67avYnKfSJkdAE32GmxXxxGY4PKiF0eN/2oJj6FuYpPAGdDR96VDkO1ZfB4os9FVAELoQ7GilKKeYWcYq8TJaFA7ssEaV8nfg54JqAwSRvbAgMBAAECggEARvRdL3OvMp4WXIZB263Bw5wDxIfoagZNAL7iiFCBoAjQVWeFhQdx5Yt6n7YBqHHx61QcglFdqllM1AJI5au0whS8BaQ+4Cgk2brTqsqdtZwYuFFqwWOe4sK5Eu3AdU+Zu1osexlUK/uCCqy0GB24/sSZ9GAwWVa+dxejIdmndC22YF0gzlLsfQvP6PSaJ474kel1DQKrg1vaLXirCKyhGwyaWMuaFV1syy1tz7XnJcQ+/8x3CGif2NjwpMPvTzpVEd/OyWcvLx2YPUwPQwf9OY9Hm19AQmy+B9MSffBGw8uSJtwIMpU5KqZAljPd8sCqnJR3DNHXqbHTGZ/cOiKJ4QKBgQDx8dgfJmTCpsIX1YUtPar1P6OGXBhxrKtj/54Oe371rWcqFvRkyBNL2CpNYP+zLEXoysoqAWfuDczh4qasIU+gqJXQXcI9jwYeIpmIG8zGE0L4yanLANRf3oGbhfrG/DCWdUlMIFrVRzeHRiuWhB06+iR3N5EC7OAr3auYoXEfOwKBgQDFG3N0areDHNuU32AXTd50mfe+kivp1saDdnknfrfsZF23Zbcm4nbo9G3+TTcZQnAR3ACdl0Fka8/WG3Sn9UZHiudzU5uFgpp9YkdAksedIQuPev+QE9q3IrGLPrExhuh/7e/xzA832wh9ONLiR+fJ7kE8t68swaT1Vy4XDH1r4QKBgQCepucAri1+ktlNxb8Zxol3Xq69aWDCEecloLYlawf61CWFLR4/hA9bObmrmgKynEKPf4MH/noHWVdTfEutLf7ILCRpSUIZGdN6KVgiL5CdBn5xI9RKgRXCc+brc/TZTQATeX+CAultV9DqzLHCdomwZd1Jq89Uar4pJafjY2IJhwKBgBzzbT/aNN4jLPVu4dRKcbQ6sTLikWSlUT8Z9a2hZS5ph4JahE5H0SNiU42YldE1+vQElmqPPuGbHEnceoP4+LulYV2FGEDB8CMefkyzwnIH2oTWkhb9c5CWnfFP4gLeR+QSdL3VNR8FIvgRkpf968OzJQ3gBPDT+IC4r2JfSUTBAoGBAO6a2/aZViAgNAr6YrZAz+F6psA5HGDMXnq5a2GNSm41E4zUA+rBVRM2/BxMN2lC37OhOUUnLYZe47gcXVQVujxloO3WEzaxl2PUKGczdPpx5TERpi+ikn13mMgKX1zZQ9NPx+IdXlVzt7TOsCetYJ/jMjAF7+JNGpcXxchX5spg';

    /**
     * @throws PhpMateException
     */
    public function testEncryptByAESPKCS5Padding()
    {
        $encrypted = EncryptUtil::encryptByAES_ECB_PKCS5PaddingToBase64(self::PASSWORD, self::PLAIN_TEXT);
        $this->assertEquals('roLzT3GBhVQw22WrUPAdsw==', $encrypted);
        // 验证力 pos 的对称加密
        self::assertEquals('I48dk3beynOy3aqbMHA6chZVE+fJUGwFfhd3skEi+lM=', EncryptUtil::encryptByAES_ECB_PKCS5PaddingToBase64("VOb9YLDTvIh9roFV", '{"name":"shali"}'));
    }

    /**
     * @throws PhpMateException
     */
    public function testDecryptByAESPKCS5Padding()
    {
        $decrypted = EncryptUtil::decryptByAES_ECB_PKCS5PaddingToBase64(self::PASSWORD, 'roLzT3GBhVQw22WrUPAdsw==');
        $this->assertEquals(self::PLAIN_TEXT, $decrypted);
        // 验证力 pos 的对称解密
        self::assertEquals('{"name":"shali"}', EncryptUtil::decryptByAES_ECB_PKCS5PaddingToBase64("VOb9YLDTvIh9roFV", 'I48dk3beynOy3aqbMHA6chZVE+fJUGwFfhd3skEi+lM='));
    }

    /**
     * @throws PhpMateException
     */
    public function testDecryptByRSA()
    {
        $encrypted = 'IUhqGDYwbwLT25swaAPPO4r6Fckg458rpyPd57LUoYweAXUVfu3qQEaZfFig1OIB4yyKFqRi6lcEPterVjasrIu1xNa5jv+5vbDjCyj2s1/3s/RsbB+pUa3eAGJGk4Y1UxITGAHuAIMVBpK0//LZbFKE9TRlt4PB+2xtcHO5f3CNxyHivC4WUZDDnUiSRsW2Rp4EWZOCgOSH9simMYzI792AU5J/M4zwDLYBZLpG9hvlQPAU35FYilP7CJHjLHRChFipj1QS4vw+xgc8d5mk4L5I3r/xO89gLY5X6RKUeE17yENxuPOOGz1EaxYrAbUW+TteZ1O3KTQ4p6C3wDBkUQ==';
        $privateKey = KeyUtil::toPrivateKeyValueOfBase64Str(self::PRIVATE_KEY);
        $decrypted = EncryptUtil::decryptByRSA_ECB_PKCS1PaddingToBase64($privateKey, $encrypted);
        self::assertEquals(self::PLAIN_TEXT, $decrypted);
    }

    /**
     * 非对称加密 rsa 加密
     * @return void
     * @throws PhpMateException
     */
    public function testEncryptByRSA()
    {
        $publicKey = KeyUtil::toPublicKeyValueOfBase64Str(self::PUBLIC_KEY);
        $encrypted = EncryptUtil::encryptByRSA_ECB_PKCS1PaddingToBase64($publicKey, self::PLAIN_TEXT);
        $privateKey = KeyUtil::toPrivateKeyValueOfBase64Str(self::PRIVATE_KEY);
        self::assertEquals(self::PLAIN_TEXT, EncryptUtil::decryptByRSA_ECB_PKCS1PaddingToBase64($privateKey, $encrypted));
    }
}
